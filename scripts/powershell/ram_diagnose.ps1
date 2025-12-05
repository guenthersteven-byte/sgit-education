# ============================================
# sgiT Education - RAM Diagnose Script
# ============================================
# Zeigt die Top 10 RAM-Fresser im System
# 
# Verwendung: .\ram_diagnose.ps1
# Datum: 03.12.2025
# ============================================

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  sgiT RAM Diagnose" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Gesamt-RAM anzeigen
$totalRAM = (Get-CimInstance Win32_ComputerSystem).TotalPhysicalMemory / 1GB
$freeRAM = (Get-CimInstance Win32_OperatingSystem).FreePhysicalMemory / 1MB
Write-Host "Gesamt RAM: $([math]::Round($totalRAM, 1)) GB" -ForegroundColor Green
Write-Host "Frei RAM:   $([math]::Round($freeRAM / 1024, 1)) GB" -ForegroundColor Yellow
Write-Host ""

# Top 10 RAM-Fresser
Write-Host "Top 10 RAM-Fresser:" -ForegroundColor Cyan
Write-Host "-------------------"
Get-Process | Sort-Object WorkingSet64 -Descending | Select-Object -First 10 Name, @{Name="RAM (MB)";Expression={[math]::Round($_.WorkingSet64/1MB,0)}} | Format-Table -AutoSize

# Ollama-spezifisch
Write-Host ""
Write-Host "Ollama Prozesse:" -ForegroundColor Cyan
Write-Host "----------------"
$ollamaProcesses = Get-Process -Name "ollama*" -ErrorAction SilentlyContinue
if ($ollamaProcesses) {
    $ollamaProcesses | Select-Object Name, @{Name="RAM (MB)";Expression={[math]::Round($_.WorkingSet64/1MB,0)}} | Format-Table -AutoSize
    $totalOllama = ($ollamaProcesses | Measure-Object WorkingSet64 -Sum).Sum / 1MB
    Write-Host "Ollama Gesamt: $([math]::Round($totalOllama, 0)) MB" -ForegroundColor Yellow
} else {
    Write-Host "Keine Ollama-Prozesse gefunden" -ForegroundColor Red
}

# Apache/XAMPP
Write-Host ""
Write-Host "Apache/XAMPP Prozesse:" -ForegroundColor Cyan
Write-Host "----------------------"
$apacheProcesses = Get-Process -Name "httpd*" -ErrorAction SilentlyContinue
if ($apacheProcesses) {
    $apacheProcesses | Select-Object Name, @{Name="RAM (MB)";Expression={[math]::Round($_.WorkingSet64/1MB,0)}} | Format-Table -AutoSize
} else {
    Write-Host "Keine Apache-Prozesse gefunden" -ForegroundColor Red
}
