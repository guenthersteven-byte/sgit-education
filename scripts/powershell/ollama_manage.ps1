# ============================================
# sgiT Education - Ollama Management Script
# ============================================
# Verwaltet Ollama: Status, Neustart, Modell-Wechsel
# 
# Verwendung: .\ollama_manage.ps1 [status|restart|test|switch]
# Datum: 03.12.2025
# ============================================

param(
    [Parameter(Position=0)]
    [ValidateSet("status", "restart", "test", "switch", "help")]
    [string]$Action = "status"
)

$configFile = "C:\xampp\htdocs\Education\AI\config\ollama_model.txt"

function Show-Status {
    Write-Host "============================================" -ForegroundColor Cyan
    Write-Host "  Ollama Status" -ForegroundColor Cyan
    Write-Host "============================================" -ForegroundColor Cyan
    
    # Installierte Modelle
    Write-Host "`nInstallierte Modelle:" -ForegroundColor Yellow
    ollama list
    
    # Konfiguriertes Modell
    if (Test-Path $configFile) {
        $model = Get-Content $configFile
        Write-Host "`nKonfiguriertes Modell: $model" -ForegroundColor Green
    }
    
    # RAM-Nutzung
    Write-Host "`nOllama RAM-Nutzung:" -ForegroundColor Yellow
    Get-Process -Name "ollama*" -ErrorAction SilentlyContinue | 
        Select-Object Name, @{Name="RAM (MB)";Expression={[math]::Round($_.WorkingSet64/1MB,0)}} | 
        Format-Table -AutoSize
}

function Restart-Ollama {
    Write-Host "Stoppe Ollama..." -ForegroundColor Yellow
    Stop-Process -Name "ollama*" -Force -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3
    
    Write-Host "Starte Ollama neu..." -ForegroundColor Green
    Write-Host "Bitte Ollama App manuell starten oder 'ollama serve' ausfuehren"
}

function Test-Model {
    $model = "tinyllama:latest"
    if (Test-Path $configFile) {
        $model = Get-Content $configFile
    }
    
    Write-Host "Teste Modell: $model" -ForegroundColor Cyan
    ollama run $model "Sage Hallo auf Deutsch"
}

function Switch-Model {
    Write-Host "Verfuegbare Modelle:" -ForegroundColor Cyan
    ollama list
    
    Write-Host ""
    $newModel = Read-Host "Welches Modell aktivieren? (z.B. tinyllama:latest)"
    
    if ($newModel) {
        Set-Content -Path $configFile -Value $newModel
        Write-Host "Modell geaendert zu: $newModel" -ForegroundColor Green
        
        # Test
        Write-Host "`nTeste neues Modell..." -ForegroundColor Yellow
        ollama run $newModel "Sage Hallo"
    }
}

function Show-Help {
    Write-Host @"
============================================
  Ollama Management - Hilfe
============================================

Verwendung: .\ollama_manage.ps1 [Aktion]

Aktionen:
  status   - Zeigt Ollama-Status und RAM-Nutzung
  restart  - Stoppt alle Ollama-Prozesse
  test     - Testet das konfigurierte Modell
  switch   - Wechselt das aktive Modell
  help     - Diese Hilfe

Beispiele:
  .\ollama_manage.ps1 status
  .\ollama_manage.ps1 test
  .\ollama_manage.ps1 switch
"@
}

# Hauptlogik
switch ($Action) {
    "status"  { Show-Status }
    "restart" { Restart-Ollama }
    "test"    { Test-Model }
    "switch"  { Switch-Model }
    "help"    { Show-Help }
    default   { Show-Status }
}
