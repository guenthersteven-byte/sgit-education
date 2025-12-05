# =============================================================================
# sgiT Education - BTCPay Server START Script
# =============================================================================
# Startet den kompletten BTCPay Stack für lokale Entwicklung
# =============================================================================

Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host "  sgiT Education - BTCPay Server (Regtest) STARTEN" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""

# Zum Script-Verzeichnis wechseln
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $scriptPath

# Prüfen ob Docker läuft
$dockerRunning = Get-Process "Docker Desktop" -ErrorAction SilentlyContinue
if (-not $dockerRunning) {
    Write-Host "[!] Docker Desktop ist nicht gestartet!" -ForegroundColor Red
    Write-Host "    Bitte Docker Desktop starten und erneut versuchen." -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

Write-Host "[1/4] Docker Desktop erkannt..." -ForegroundColor Cyan

# Docker Compose starten
Write-Host "[2/4] Starte BTCPay Server Stack..." -ForegroundColor Cyan
Write-Host ""

docker-compose up -d

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "[3/4] Warte auf Services (60 Sekunden)..." -ForegroundColor Cyan
    
    # Fortschrittsanzeige
    for ($i = 1; $i -le 60; $i++) {
        Write-Progress -Activity "Services starten..." -Status "$i von 60 Sekunden" -PercentComplete (($i / 60) * 100)
        Start-Sleep -Seconds 1
    }
    Write-Progress -Activity "Services starten..." -Completed
    
    Write-Host ""
    Write-Host "[4/4] BTCPay Server gestartet!" -ForegroundColor Green
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host "  ZUGRIFF:" -ForegroundColor Green
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "  BTCPay Server:   http://localhost:49392" -ForegroundColor White
    Write-Host "  Bitcoin RPC:     localhost:18443" -ForegroundColor Gray
    Write-Host "  LND REST API:    https://localhost:8080" -ForegroundColor Gray
    Write-Host "  NBXplorer:       http://localhost:32838" -ForegroundColor Gray
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host "  CREDENTIALS:" -ForegroundColor Green
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "  Bitcoin RPC User:     btcpay" -ForegroundColor White
    Write-Host "  Bitcoin RPC Password: btcpay2025" -ForegroundColor White
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host "  NAECHSTE SCHRITTE:" -ForegroundColor Yellow
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "  1. Oeffne http://localhost:49392" -ForegroundColor White
    Write-Host "  2. Erstelle Admin Account" -ForegroundColor White
    Write-Host "  3. Erstelle Store 'sgiT Education'" -ForegroundColor White
    Write-Host "  4. Generiere API Key unter Account Settings" -ForegroundColor White
    Write-Host "  5. Trage API Key in btcpay_config.php ein" -ForegroundColor White
    Write-Host ""
    Write-Host "  Test-Bitcoin generieren:" -ForegroundColor Cyan
    Write-Host "  .\generate_test_btc.ps1" -ForegroundColor Cyan
    Write-Host ""
    
    # Browser öffnen
    $openBrowser = Read-Host "Browser oeffnen? (j/n)"
    if ($openBrowser -eq "j" -or $openBrowser -eq "J") {
        Start-Process "http://localhost:49392"
    }
    
} else {
    Write-Host ""
    Write-Host "[X] Fehler beim Starten!" -ForegroundColor Red
    Write-Host "    Pruefe Docker Desktop und versuche es erneut." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "    Logs anzeigen: docker-compose logs" -ForegroundColor Gray
    Write-Host ""
}
