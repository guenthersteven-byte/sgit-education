# ================================================================
# backup_sgit_education.ps1
# sgiT Education Platform - One-Click Backup
# Version: 1.0
# Datum: 03.12.2025
# ================================================================
#
# USAGE:
#   .\backup_sgit_education.ps1                    # Standard-Backup nach D:\Backups
#   .\backup_sgit_education.ps1 -BackupPath "E:\MyBackups"  # Anderer Pfad
#   .\backup_sgit_education.ps1 -IncludeLogs      # Mit Logs
#   .\backup_sgit_education.ps1 -NoCompress       # Ohne ZIP
#
# ================================================================

param(
    [string]$BackupPath = "D:\Backups\sgiT-Education",
    [switch]$IncludeLogs = $false,
    [switch]$NoCompress = $false
)

# ================================================================
# KONFIGURATION
# ================================================================
$SourcePath = "C:\xampp\htdocs\Education"
$PhpExe = "C:\xampp\php\php.exe"
$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$BackupName = "sgit-edu-backup-$Timestamp"
$BackupDir = Join-Path $BackupPath $BackupName

# ================================================================
# BANNER
# ================================================================
Clear-Host
Write-Host ""
Write-Host "  ╔══════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "  ║                                                      ║" -ForegroundColor Green
Write-Host "  ║   ███████╗ ██████╗ ██╗████████╗                      ║" -ForegroundColor Green
Write-Host "  ║   ██╔════╝██╔════╝ ██║╚══██╔══╝                      ║" -ForegroundColor Green
Write-Host "  ║   ███████╗██║  ███╗██║   ██║                         ║" -ForegroundColor Green
Write-Host "  ║   ╚════██║██║   ██║██║   ██║                         ║" -ForegroundColor Green
Write-Host "  ║   ███████║╚██████╔╝██║   ██║                         ║" -ForegroundColor Green
Write-Host "  ║   ╚══════╝ ╚═════╝ ╚═╝   ╚═╝                         ║" -ForegroundColor Green
Write-Host "  ║                                                      ║" -ForegroundColor Green
Write-Host "  ║   Education Platform - Backup System v1.0            ║" -ForegroundColor Green
Write-Host "  ║                                                      ║" -ForegroundColor Green
Write-Host "  ╚══════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""

# ================================================================
# VALIDIERUNG
# ================================================================
Write-Host "Validiere Voraussetzungen..." -ForegroundColor Cyan

# Prüfe Quellverzeichnis
if (-not (Test-Path $SourcePath)) {
    Write-Host "❌ FEHLER: Quellverzeichnis nicht gefunden: $SourcePath" -ForegroundColor Red
    exit 1
}
Write-Host "  ✅ Quellverzeichnis gefunden" -ForegroundColor Gray

# Prüfe PHP
if (-not (Test-Path $PhpExe)) {
    Write-Host "  ⚠️ PHP nicht gefunden - SQLite Optimierung übersprungen" -ForegroundColor Yellow
    $PhpExe = $null
} else {
    Write-Host "  ✅ PHP gefunden" -ForegroundColor Gray
}

# Erstelle Backup-Verzeichnis
if (-not (Test-Path $BackupPath)) {
    New-Item -ItemType Directory -Path $BackupPath -Force | Out-Null
    Write-Host "  ✅ Backup-Verzeichnis erstellt: $BackupPath" -ForegroundColor Gray
}

Write-Host ""
Write-Host "════════════════════════════════════════════════════════" -ForegroundColor DarkGray
Write-Host "  Quelle:     $SourcePath" -ForegroundColor White
Write-Host "  Ziel:       $BackupDir" -ForegroundColor White
Write-Host "  Mit Logs:   $IncludeLogs" -ForegroundColor White
Write-Host "  Komprimiert: $(-not $NoCompress)" -ForegroundColor White
Write-Host "════════════════════════════════════════════════════════" -ForegroundColor DarkGray
Write-Host ""

# Erstelle temporäres Backup-Verzeichnis
New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null

# ================================================================
# SCHRITT 1: DATENBANKEN
# ================================================================
Write-Host "[1/6] 💾 Sichere Datenbanken..." -ForegroundColor Cyan
$DbDir = Join-Path $BackupDir "databases"
New-Item -ItemType Directory -Path $DbDir -Force | Out-Null

# SQLite WAL-Checkpoint und VACUUM für Konsistenz
if ($PhpExe) {
    $phpScript = @"
<?php
`$dbs = [
    '$SourcePath/AI/data/questions.db',
    '$SourcePath/wallet/wallet.db',
    '$SourcePath/bots/logs/bot_results.db'
];
foreach(`$dbs as `$dbPath) {
    if(file_exists(`$dbPath)) {
        try {
            `$db = new SQLite3(`$dbPath);
            `$db->exec('PRAGMA wal_checkpoint(TRUNCATE)');
            `$db->close();
            echo "OK: `$dbPath\n";
        } catch(Exception `$e) {
            echo "SKIP: `$dbPath\n";
        }
    }
}
?>
"@
    $phpScript | & $PhpExe 2>$null | ForEach-Object { Write-Host "  $_" -ForegroundColor Gray }
}

# Kopiere Datenbanken
$databases = @(
    @{Name="questions.db"; Source="$SourcePath\AI\data\questions.db"},
    @{Name="wallet.db"; Source="$SourcePath\wallet\wallet.db"},
    @{Name="bot_results.db"; Source="$SourcePath\bots\logs\bot_results.db"}
)

foreach ($db in $databases) {
    if (Test-Path $db.Source) {
        Copy-Item $db.Source -Destination "$DbDir\$($db.Name)" -Force
        $size = [math]::Round((Get-Item $db.Source).Length / 1KB, 1)
        Write-Host "  ✅ $($db.Name) ($size KB)" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️ $($db.Name) nicht vorhanden" -ForegroundColor Yellow
    }
}

# ================================================================
# SCHRITT 2: KONFIGURATION
# ================================================================
Write-Host ""
Write-Host "[2/6] ⚙️ Sichere Konfiguration..." -ForegroundColor Cyan
$ConfigDir = Join-Path $BackupDir "config"
New-Item -ItemType Directory -Path $ConfigDir -Force | Out-Null

$configs = @(
    "$SourcePath\config.php",
    "$SourcePath\db_config.php",
    "$SourcePath\AI\config\ollama_model.txt",
    "$SourcePath\AI\config\ollama_cloud.php",
    "$SourcePath\wallet\btcpay_config.php"
)

foreach ($cfg in $configs) {
    if (Test-Path $cfg) {
        Copy-Item $cfg -Destination $ConfigDir -Force
        Write-Host "  ✅ $(Split-Path $cfg -Leaf)" -ForegroundColor Green
    }
}

# ================================================================
# SCHRITT 3: BENUTZERDATEN
# ================================================================
Write-Host ""
Write-Host "[3/6] 👤 Sichere Benutzerdaten..." -ForegroundColor Cyan
$UserDir = Join-Path $BackupDir "users"
New-Item -ItemType Directory -Path $UserDir -Force | Out-Null

$userFiles = Get-ChildItem "$SourcePath\AI\users\*.json" -ErrorAction SilentlyContinue
if ($userFiles) {
    Copy-Item "$SourcePath\AI\users\*.json" -Destination $UserDir -Force
    Write-Host "  ✅ $($userFiles.Count) Benutzer-Sessions" -ForegroundColor Green
} else {
    Write-Host "  ⚠️ Keine Benutzer-Sessions gefunden" -ForegroundColor Yellow
}

# ================================================================
# SCHRITT 4: CONTENT (CSVs, Definitionen)
# ================================================================
Write-Host ""
Write-Host "[4/6] 📚 Sichere Content..." -ForegroundColor Cyan
$ContentDir = Join-Path $BackupDir "content"
New-Item -ItemType Directory -Path $ContentDir -Force | Out-Null

# CSV-Dateien
$csvFiles = Get-ChildItem "$SourcePath\docs\*.csv" -ErrorAction SilentlyContinue
if ($csvFiles) {
    Copy-Item "$SourcePath\docs\*.csv" -Destination $ContentDir -Force
    Write-Host "  ✅ $($csvFiles.Count) CSV-Templates" -ForegroundColor Green
}

# Modul-Definitionen
$moduleFiles = Get-ChildItem "$SourcePath\AI\module_definitions*.json" -ErrorAction SilentlyContinue
if ($moduleFiles) {
    Copy-Item "$SourcePath\AI\module_definitions*.json" -Destination $ContentDir -Force
    Write-Host "  ✅ $($moduleFiles.Count) Modul-Definitionen" -ForegroundColor Green
}

# ================================================================
# SCHRITT 5: QUELLCODE
# ================================================================
Write-Host ""
Write-Host "[5/6] 📦 Sichere Quellcode..." -ForegroundColor Cyan
$CodeDir = Join-Path $BackupDir "source"
New-Item -ItemType Directory -Path $CodeDir -Force | Out-Null

# Robocopy für effizientes Kopieren
$excludeDirs = "_DISABLED_*"
$excludeFiles = "*.log", "*.db.old*", "*.bak", "*.tmp"

if ($IncludeLogs) {
    $excludeFiles = "*.db.old*", "*.bak", "*.tmp"
}

$robocopyArgs = @(
    $SourcePath,
    $CodeDir,
    "/E",                    # Rekursiv
    "/XD", "_DISABLED_*",    # Exclude disabled modules
    "/XF", "*.log", "*.db.old*", "*.bak", "*.tmp",
    "/NFL", "/NDL", "/NJH", "/NJS", "/NP"  # Minimale Ausgabe
)

$robocopyResult = robocopy @robocopyArgs

# Zähle kopierte Dateien
$fileCount = (Get-ChildItem $CodeDir -Recurse -File).Count
Write-Host "  ✅ $fileCount Dateien kopiert" -ForegroundColor Green

# ================================================================
# SCHRITT 6: MANIFEST
# ================================================================
Write-Host ""
Write-Host "[6/6] 📋 Erstelle Backup-Manifest..." -ForegroundColor Cyan

# Projektversion aus Status-Report extrahieren
$projectVersion = "2.4.8"
$statusReport = "$SourcePath\sgit_education_status_report.md"
if (Test-Path $statusReport) {
    $content = Get-Content $statusReport -Head 10 -Raw
    if ($content -match "Version:\*\*\s*([\d.]+)") {
        $projectVersion = $Matches[1]
    }
}

# Ollama-Modell
$ollamaModel = "unknown"
$modelFile = "$SourcePath\AI\config\ollama_model.txt"
if (Test-Path $modelFile) {
    $ollamaModel = (Get-Content $modelFile).Trim()
}

$Manifest = @{
    backup = @{
        date = $Timestamp
        type = "full"
        version = "1.0"
    }
    project = @{
        name = "sgiT Education Platform"
        version = $projectVersion
        source_path = $SourcePath
    }
    components = @{
        databases = (Get-ChildItem $DbDir -Name -ErrorAction SilentlyContinue)
        config_files = (Get-ChildItem $ConfigDir -Name -ErrorAction SilentlyContinue)
        user_sessions = (Get-ChildItem $UserDir -Name -ErrorAction SilentlyContinue)
        content_files = (Get-ChildItem $ContentDir -Name -ErrorAction SilentlyContinue)
        source_files = $fileCount
    }
    system = @{
        hostname = $env:COMPUTERNAME
        username = $env:USERNAME
        php_path = $PhpExe
        ollama_model = $ollamaModel
        os = [System.Environment]::OSVersion.VersionString
    }
    restore = @{
        script = "restore_sgit_education.ps1"
        command = ".\restore_sgit_education.ps1 -BackupFile `"<path-to-zip>`""
    }
}

$Manifest | ConvertTo-Json -Depth 5 | Out-File "$BackupDir\backup_manifest.json" -Encoding UTF8
Write-Host "  ✅ Manifest erstellt" -ForegroundColor Green

# ================================================================
# KOMPRIMIEREN
# ================================================================
if (-not $NoCompress) {
    Write-Host ""
    Write-Host "Komprimiere Backup..." -ForegroundColor Yellow
    
    $ZipPath = "$BackupPath\$BackupName.zip"
    
    try {
        Compress-Archive -Path $BackupDir -DestinationPath $ZipPath -Force -CompressionLevel Optimal
        
        # Temporäres Verzeichnis löschen
        Remove-Item -Path $BackupDir -Recurse -Force
        
        $ZipSize = (Get-Item $ZipPath).Length
        $ZipSizeMB = [math]::Round($ZipSize / 1MB, 2)
        
        Write-Host ""
        Write-Host "╔══════════════════════════════════════════════════════╗" -ForegroundColor Green
        Write-Host "║                                                      ║" -ForegroundColor Green
        Write-Host "║   ✅ BACKUP ERFOLGREICH ERSTELLT!                    ║" -ForegroundColor Green
        Write-Host "║                                                      ║" -ForegroundColor Green
        Write-Host "╚══════════════════════════════════════════════════════╝" -ForegroundColor Green
        Write-Host ""
        Write-Host "  📁 Datei:   $ZipPath" -ForegroundColor White
        Write-Host "  📊 Größe:   $ZipSizeMB MB" -ForegroundColor White
        Write-Host ""
        Write-Host "  Restore mit:" -ForegroundColor Gray
        Write-Host "  .\restore_sgit_education.ps1 -BackupFile `"$ZipPath`"" -ForegroundColor Cyan
        
    } catch {
        Write-Host "❌ Komprimierung fehlgeschlagen: $_" -ForegroundColor Red
        Write-Host "Backup-Verzeichnis: $BackupDir" -ForegroundColor Yellow
    }
} else {
    $DirSize = [math]::Round((Get-ChildItem $BackupDir -Recurse | Measure-Object Length -Sum).Sum / 1MB, 2)
    
    Write-Host ""
    Write-Host "╔══════════════════════════════════════════════════════╗" -ForegroundColor Green
    Write-Host "║                                                      ║" -ForegroundColor Green
    Write-Host "║   ✅ BACKUP ERFOLGREICH ERSTELLT!                    ║" -ForegroundColor Green
    Write-Host "║                                                      ║" -ForegroundColor Green
    Write-Host "╚══════════════════════════════════════════════════════╝" -ForegroundColor Green
    Write-Host ""
    Write-Host "  📁 Verzeichnis: $BackupDir" -ForegroundColor White
    Write-Host "  📊 Größe:       $DirSize MB" -ForegroundColor White
}

Write-Host ""
