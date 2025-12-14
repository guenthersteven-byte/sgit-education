# ============================================================================
# sgiT Education - BTCPay Server Setup Script
# ============================================================================
# 
# Dieses Skript installiert:
# 1. WSL2 mit Ubuntu
# 2. Docker Desktop
# 3. BTCPay Server (Regtest für Entwicklung)
#
# AUSFÜHRUNG:
# 1. PowerShell als Administrator öffnen
# 2. cd C:\xampp\htdocs\Education\scripts
# 3. Set-ExecutionPolicy Bypass -Scope Process -Force
# 4. .\install_btcpay.ps1
#
# @author sgiT Solution Engineering
# @version 1.0
# @date 02.12.2025
# ============================================================================

Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host "  sgiT Education - BTCPay Server Setup" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""

# Admin-Check
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "[FEHLER] Bitte als Administrator ausfuehren!" -ForegroundColor Red
    Write-Host "Rechtsklick auf PowerShell -> Als Administrator ausfuehren" -ForegroundColor Yellow
    exit 1
}

Write-Host "[OK] Admin-Rechte vorhanden" -ForegroundColor Green

# ============================================================================
# PHASE 1: WSL2 Installation
# ============================================================================

Write-Host ""
Write-Host "--- PHASE 1: WSL2 Installation ---" -ForegroundColor Cyan

# Pruefen ob WSL bereits installiert ist
$wslInstalled = $false
try {
    $wslVersion = wsl --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        $wslInstalled = $true
        Write-Host "[OK] WSL2 ist bereits installiert" -ForegroundColor Green
    }
} catch {
    $wslInstalled = $false
}

if (-not $wslInstalled) {
    Write-Host "[INFO] Installiere WSL2..." -ForegroundColor Yellow
    
    # Windows-Features aktivieren
    Write-Host "  -> Aktiviere Windows Subsystem fuer Linux..." -ForegroundColor Gray
    dism.exe /online /enable-feature /featurename:Microsoft-Windows-Subsystem-Linux /all /norestart
    
    Write-Host "  -> Aktiviere Virtual Machine Platform..." -ForegroundColor Gray
    dism.exe /online /enable-feature /featurename:VirtualMachinePlatform /all /norestart
    
    # WSL2 als Standard setzen
    Write-Host "  -> Setze WSL2 als Standard..." -ForegroundColor Gray
    wsl --set-default-version 2
    
    # Ubuntu installieren
    Write-Host "  -> Installiere Ubuntu..." -ForegroundColor Gray
    wsl --install -d Ubuntu
    
    Write-Host ""
    Write-Host "[WICHTIG] NEUSTART ERFORDERLICH!" -ForegroundColor Red
    Write-Host "Nach dem Neustart dieses Skript erneut ausfuehren." -ForegroundColor Yellow
    Write-Host ""
    
    $restart = Read-Host "Jetzt neustarten? (j/n)"
    if ($restart -eq "j") {
        Restart-Computer
    }
    exit 0
}

# ============================================================================
# PHASE 2: Docker Desktop Installation
# ============================================================================

Write-Host ""
Write-Host "--- PHASE 2: Docker Desktop ---" -ForegroundColor Cyan

# Pruefen ob Docker bereits installiert ist
$dockerInstalled = $false
try {
    $dockerVersion = docker --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        $dockerInstalled = $true
        Write-Host "[OK] Docker ist bereits installiert: $dockerVersion" -ForegroundColor Green
    }
} catch {
    $dockerInstalled = $false
}

if (-not $dockerInstalled) {
    Write-Host "[INFO] Docker Desktop wird heruntergeladen..." -ForegroundColor Yellow
    
    $dockerInstallerUrl = "https://desktop.docker.com/win/main/amd64/Docker%20Desktop%20Installer.exe"
    $dockerInstallerPath = "$env:TEMP\DockerDesktopInstaller.exe"
    
    # Download
    Write-Host "  -> Download laeuft (ca. 500MB)..." -ForegroundColor Gray
    try {
        Invoke-WebRequest -Uri $dockerInstallerUrl -OutFile $dockerInstallerPath -UseBasicParsing
        Write-Host "  -> Download abgeschlossen" -ForegroundColor Green
    } catch {
        Write-Host "[FEHLER] Download fehlgeschlagen!" -ForegroundColor Red
        Write-Host "Bitte manuell herunterladen: https://www.docker.com/products/docker-desktop/" -ForegroundColor Yellow
        exit 1
    }
    
    # Installation starten
    Write-Host "  -> Starte Installation (Silent)..." -ForegroundColor Gray
    Start-Process -FilePath $dockerInstallerPath -ArgumentList "install", "--quiet", "--accept-license" -Wait
    
    Write-Host ""
    Write-Host "[WICHTIG] Docker Desktop wurde installiert!" -ForegroundColor Green
    Write-Host "Bitte:" -ForegroundColor Yellow
    Write-Host "  1. Docker Desktop starten" -ForegroundColor Yellow
    Write-Host "  2. Warten bis Docker laeuft (gruenes Icon)" -ForegroundColor Yellow
    Write-Host "  3. Dieses Skript erneut ausfuehren" -ForegroundColor Yellow
    Write-Host ""
    
    # Docker Desktop starten
    $dockerPath = "C:\Program Files\Docker\Docker\Docker Desktop.exe"
    if (Test-Path $dockerPath) {
        Start-Process $dockerPath
    }
    
    exit 0
}

# Docker Status pruefen
Write-Host "[INFO] Pruefe Docker Status..." -ForegroundColor Yellow
try {
    docker info 2>$null | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "[WARNUNG] Docker laeuft nicht!" -ForegroundColor Red
        Write-Host "Bitte Docker Desktop starten und warten bis es bereit ist." -ForegroundColor Yellow
        exit 1
    }
    Write-Host "[OK] Docker laeuft" -ForegroundColor Green
} catch {
    Write-Host "[WARNUNG] Docker nicht erreichbar" -ForegroundColor Red
    exit 1
}

# ============================================================================
# PHASE 3: BTCPay Server Setup
# ============================================================================

Write-Host ""
Write-Host "--- PHASE 3: BTCPay Server Setup ---" -ForegroundColor Cyan

$btcpayDir = "$env:USERPROFILE\btcpayserver-docker"

# Pruefen ob bereits installiert
if (Test-Path $btcpayDir) {
    Write-Host "[INFO] BTCPay Verzeichnis existiert bereits" -ForegroundColor Yellow
    $overwrite = Read-Host "Ueberschreiben? (j/n)"
    if ($overwrite -eq "j") {
        Remove-Item -Recurse -Force $btcpayDir
    } else {
        Write-Host "[SKIP] Ueberspringe BTCPay Installation" -ForegroundColor Gray
    }
}

if (-not (Test-Path $btcpayDir)) {
    Write-Host "[INFO] Klone BTCPay Server Repository..." -ForegroundColor Yellow
    
    # Git pruefen
    $gitInstalled = $false
    try {
        git --version 2>$null | Out-Null
        if ($LASTEXITCODE -eq 0) {
            $gitInstalled = $true
        }
    } catch {
        $gitInstalled = $false
    }
    
    if (-not $gitInstalled) {
        Write-Host "[INFO] Git wird ueber winget installiert..." -ForegroundColor Yellow
        winget install --id Git.Git -e --source winget --accept-package-agreements --accept-source-agreements
        
        # PATH aktualisieren
        $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
    }
    
    Set-Location $env:USERPROFILE
    git clone https://github.com/btcpayserver/btcpayserver-docker.git
    
    Write-Host "[OK] Repository geklont" -ForegroundColor Green
}

# ============================================================================
# PHASE 4: hosts-Datei aktualisieren
# ============================================================================

Write-Host ""
Write-Host "--- PHASE 4: hosts-Datei ---" -ForegroundColor Cyan

Write-Host "[INFO] Aktualisiere hosts-Datei..." -ForegroundColor Yellow
$hostsFile = "C:\Windows\System32\drivers\etc\hosts"
$hostsEntry = "127.0.0.1 btcpay.local"

$hostsContent = Get-Content $hostsFile -Raw
if ($hostsContent -notmatch "btcpay.local") {
    Add-Content -Path $hostsFile -Value "`n$hostsEntry"
    Write-Host "[OK] hosts-Eintrag hinzugefuegt" -ForegroundColor Green
} else {
    Write-Host "[OK] hosts-Eintrag existiert bereits" -ForegroundColor Green
}

# ============================================================================
# PHASE 5: WSL Setup Script erstellen
# ============================================================================

Write-Host ""
Write-Host "--- PHASE 5: WSL Setup Script ---" -ForegroundColor Cyan

$wslSetupScript = "$env:USERPROFILE\btcpay_wsl_setup.sh"

$wslScriptContent = @'
#!/bin/bash
# ============================================================================
# sgiT Education - BTCPay WSL Setup
# ============================================================================

echo ""
echo "============================================="
echo "  BTCPay Server Setup (Regtest)"
echo "============================================="
echo ""

# Verzeichnis vorbereiten
cd ~

# Falls noch nicht geklont
if [ ! -d "btcpayserver-docker" ]; then
    echo "[INFO] Klone BTCPay Repository..."
    git clone https://github.com/btcpayserver/btcpayserver-docker
fi

cd btcpayserver-docker

# Umgebungsvariablen setzen
echo "[INFO] Setze Konfiguration (Regtest)..."
export BTCPAY_HOST=btcpay.local
export NBITCOIN_NETWORK=regtest
export BTCPAYGEN_CRYPTO1=btc
export BTCPAYGEN_LIGHTNING=lnd
export BTCPAYGEN_REVERSEPROXY=nginx
export BTCPAYGEN_ADDITIONAL_FRAGMENTS=opt-add-regtest

# In .bashrc speichern fuer spaeter
echo "" >> ~/.bashrc
echo "# BTCPay Server Konfiguration" >> ~/.bashrc
echo "export BTCPAY_HOST=btcpay.local" >> ~/.bashrc
echo "export NBITCOIN_NETWORK=regtest" >> ~/.bashrc
echo "export BTCPAYGEN_CRYPTO1=btc" >> ~/.bashrc
echo "export BTCPAYGEN_LIGHTNING=lnd" >> ~/.bashrc
echo "export BTCPAYGEN_REVERSEPROXY=nginx" >> ~/.bashrc
echo "export BTCPAYGEN_ADDITIONAL_FRAGMENTS=opt-add-regtest" >> ~/.bashrc

# BTCPay Setup starten
echo ""
echo "[INFO] Starte BTCPay Server Setup..."
echo "  -> Dies kann 5-10 Minuten dauern..."
echo ""

./btcpay-setup.sh -i

echo ""
echo "============================================="
echo "  FERTIG!"
echo "============================================="
echo ""
echo "BTCPay Server laeuft jetzt unter:"
echo "  https://btcpay.local"
echo ""
echo "Naechste Schritte:"
echo "  1. Browser oeffnen: https://btcpay.local"
echo "  2. Admin-Account erstellen"
echo "  3. Store erstellen"
echo "  4. API Key generieren"
echo ""
'@

$wslScriptContent | Out-File -FilePath $wslSetupScript -Encoding UTF8 -NoNewline
Write-Host "[OK] WSL Setup Script erstellt: $wslSetupScript" -ForegroundColor Green

# ============================================================================
# ZUSAMMENFASSUNG
# ============================================================================

Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host "  SETUP ZUSAMMENFASSUNG" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Installiert/Geprueft:" -ForegroundColor White
Write-Host "  [OK] WSL2" -ForegroundColor Green
Write-Host "  [OK] Docker Desktop" -ForegroundColor Green
Write-Host "  [OK] Git" -ForegroundColor Green
Write-Host "  [OK] BTCPay Repository" -ForegroundColor Green
Write-Host "  [OK] hosts-Datei" -ForegroundColor Green
Write-Host ""
Write-Host "=============================================" -ForegroundColor Yellow
Write-Host "  LETZTER SCHRITT - MANUELL" -ForegroundColor Yellow
Write-Host "=============================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "Oeffne Ubuntu (WSL) und fuehre aus:" -ForegroundColor White
Write-Host ""
Write-Host "  bash ~/btcpay_wsl_setup.sh" -ForegroundColor Cyan
Write-Host ""
Write-Host "Oder manuell:" -ForegroundColor Gray
Write-Host "  cd ~/btcpayserver-docker" -ForegroundColor Gray
Write-Host "  export BTCPAY_HOST=btcpay.local" -ForegroundColor Gray
Write-Host "  export NBITCOIN_NETWORK=regtest" -ForegroundColor Gray
Write-Host "  export BTCPAYGEN_CRYPTO1=btc" -ForegroundColor Gray
Write-Host "  export BTCPAYGEN_LIGHTNING=lnd" -ForegroundColor Gray
Write-Host "  export BTCPAYGEN_REVERSEPROXY=nginx" -ForegroundColor Gray
Write-Host "  export BTCPAYGEN_ADDITIONAL_FRAGMENTS=opt-add-regtest" -ForegroundColor Gray
Write-Host "  ./btcpay-setup.sh -i" -ForegroundColor Gray
Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Nach erfolgreichem Setup:" -ForegroundColor Yellow
Write-Host "  Browser: https://btcpay.local" -ForegroundColor White
Write-Host "  Admin-Account erstellen" -ForegroundColor White
Write-Host "  Store: 'sgiT Education' erstellen" -ForegroundColor White
Write-Host "  API Key generieren (Greenfield API)" -ForegroundColor White
Write-Host ""

# WSL oeffnen Option
$openWsl = Read-Host "WSL (Ubuntu) jetzt oeffnen? (j/n)"
if ($openWsl -eq "j") {
    Start-Process "wsl.exe"
}
