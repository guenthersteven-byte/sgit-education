# =============================================================================
# sgiT Education - BTCPay Server STOP Script
# =============================================================================
# Stoppt den kompletten BTCPay Stack
# =============================================================================

Write-Host ""
Write-Host "============================================================" -ForegroundColor Yellow
Write-Host "  sgiT Education - BTCPay Server STOPPEN" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Yellow
Write-Host ""

# Zum Script-Verzeichnis wechseln
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $scriptPath

Write-Host "[1/2] Stoppe BTCPay Server Stack..." -ForegroundColor Cyan
Write-Host ""

docker-compose down

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "[2/2] BTCPay Server gestoppt!" -ForegroundColor Green
    Write-Host ""
    Write-Host "  Daten bleiben erhalten (Volumes)." -ForegroundColor Gray
    Write-Host "  Zum Loeschen: docker-compose down -v" -ForegroundColor Gray
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "[X] Fehler beim Stoppen!" -ForegroundColor Red
    Write-Host ""
}
