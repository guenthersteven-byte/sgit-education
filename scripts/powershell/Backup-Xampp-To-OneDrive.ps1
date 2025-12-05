# Backup-Xampp-To-OneDrive.ps1
# Sichert C:\xampp inkrementell nach OneDrive (nur geänderte Dateien)

$Source    = "C:\xampp\htdocs\Education\backups"
$BaseDest  = "C:\Users\SG\OneDrive\sgiT\sgiT\WebsiteSourcefiles_sgitspace\backups"
$Dest      = Join-Path $BaseDest "sgit-EDU-Ai-plattform"   # Fester Zielordner für inkrementelles Backup
$LogDir    = Join-Path $BaseDest "logs"

# Zeitstempel für Logfile
$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$LogFile   = Join-Path $LogDir "backup_$Timestamp.log"

Write-Host "Backup startet: $Timestamp" -ForegroundColor Cyan
Write-Host "Quelle:      $Source"
Write-Host "Ziel:        $Dest"
Write-Host "Logfile:     $LogFile"
Write-Host ""

# Verzeichnisse sicherstellen
foreach ($path in @($BaseDest, $Dest, $LogDir)) {
    if (-not (Test-Path $path)) {
        New-Item -ItemType Directory -Path $path -Force | Out-Null
    }
}

# Robocopy-Parameter:
# /MIR  : Spiegeln (inkl. Löschungen) – wenn du gelöschte Files NICHT löschen willst, nimm /E statt /MIR
# /FFT  : Zeitstempel-Toleranz (Windows/Unix-kompatibel)
# /R:3  : 3 Wiederholungen bei Fehlern
# /W:5  : 5 Sekunden warten zwischen Wiederholungen
# /NP   : Kein Fortschritt in %
# /TEE  : Ausgabe sowohl Konsole als auch Log
# /LOG  : Logfile schreiben
# /XD   : ggf. temporäre Ordner ausschließen (Beispiele auskommentiert)

$roboParams = @(
    $Source
    $Dest
    "/MIR"
    "/FFT"
    "/R:3"
    "/W:5"
    "/NP"
    "/TEE"
    "/LOG:`"$LogFile`""
    # "/XD", "C:\xampp\tmp","C:\xampp\temp"   # Wenn du bestimmte Ordner ausschließen willst
)

# Robocopy ausführen
$cmd = "robocopy " + ($roboParams -join " ")

Write-Host "Starte Robocopy..."
Write-Host $cmd
Write-Host ""

# Robocopy aufrufen
$exitCode = cmd.exe /c $cmd

Write-Host ""
Write-Host "Robocopy ExitCode: $exitCode" -ForegroundColor Yellow
Write-Host "Backup beendet: $(Get-Date -Format "yyyy-MM-dd_HH-mm-ss")" -ForegroundColor Green
