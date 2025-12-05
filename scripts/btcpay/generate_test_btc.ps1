# =============================================================================
# sgiT Education - Test-Bitcoin Generieren (Regtest) v1.1
# =============================================================================
# Generiert Test-Bitcoin für Entwicklung
# Fix: -rpcwallet Parameter für Bitcoin Core 26.0+
# =============================================================================

param(
    [int]$blocks = 101,
    [string]$address = ""
)

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  sgiT Education - Test-Bitcoin Generator (Regtest) v1.1" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Container-Name
$container = "sgit-bitcoind"
$wallet = "testwallet"

# Bitcoin CLI Basis-Befehl
$btcCli = "bitcoin-cli -regtest -rpcuser=btcpay -rpcpassword=btcpay2025"

# Prüfen ob Container läuft
$running = docker ps --filter "name=$container" --format "{{.Names}}"
if ($running -ne $container) {
    Write-Host "[X] Bitcoin Container laeuft nicht!" -ForegroundColor Red
    Write-Host "    Starte zuerst: .\start_btcpay.ps1" -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

Write-Host "[i] Bitcoin Container aktiv" -ForegroundColor Green
Write-Host ""

# Wallet erstellen falls nicht vorhanden
Write-Host "[1/4] Pruefe/Erstelle Wallet..." -ForegroundColor Cyan
$walletExists = docker exec $container $btcCli listwallets 2>$null | Select-String -Pattern $wallet
if (-not $walletExists) {
    # Versuche Wallet zu erstellen
    $createResult = docker exec $container $btcCli createwallet $wallet 2>&1
    if ($createResult -match "already exists") {
        # Wallet existiert, aber nicht geladen - versuche zu laden
        docker exec $container $btcCli loadwallet $wallet 2>$null
    }
    Write-Host "       Wallet '$wallet' erstellt/geladen" -ForegroundColor White
} else {
    Write-Host "       Wallet '$wallet' bereits aktiv" -ForegroundColor White
}

# Neue Adresse generieren falls keine angegeben (MIT -rpcwallet!)
if ($address -eq "") {
    Write-Host "[2/4] Generiere neue Adresse..." -ForegroundColor Cyan
    $address = docker exec $container $btcCli -rpcwallet=$wallet getnewaddress 2>&1
    
    if ($address -match "error" -or $address -eq "") {
        Write-Host "[!] Fehler bei Adressgenerierung, versuche alternative Methode..." -ForegroundColor Yellow
        # Fallback: Direkt im Container ausführen
        $address = docker exec $container sh -c "$btcCli -rpcwallet=$wallet getnewaddress"
    }
    
    Write-Host "       Adresse: $address" -ForegroundColor White
}

# Blöcke minen (MIT -rpcwallet!)
Write-Host "[3/4] Mine $blocks Bloecke..." -ForegroundColor Cyan
$result = docker exec $container $btcCli -rpcwallet=$wallet generatetoaddress $blocks $address 2>&1

if ($LASTEXITCODE -eq 0 -and $result -notmatch "error") {
    # Balance prüfen
    Write-Host "[4/4] Pruefe Balance..." -ForegroundColor Cyan
    $balance = docker exec $container $btcCli -rpcwallet=$wallet getbalance
    
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host "  ERFOLG!" -ForegroundColor Green
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "  Bloecke gemined:  $blocks" -ForegroundColor White
    Write-Host "  Wallet Balance:   $balance BTC" -ForegroundColor Yellow
    Write-Host "  Adresse:          $address" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  Diese BTC sind NUR fuer Tests (Regtest)!" -ForegroundColor Cyan
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "[X] Fehler beim Mining!" -ForegroundColor Red
    Write-Host "    Output: $result" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "    Versuche manuell:" -ForegroundColor Gray
    Write-Host "    docker exec $container $btcCli -rpcwallet=$wallet getnewaddress" -ForegroundColor Gray
    Write-Host ""
}

# Blockchain Info
Write-Host "============================================================" -ForegroundColor Gray
Write-Host "  Blockchain Info:" -ForegroundColor Gray
Write-Host "============================================================" -ForegroundColor Gray
docker exec $container $btcCli getblockchaininfo | Select-String -Pattern "chain|blocks"
Write-Host ""
