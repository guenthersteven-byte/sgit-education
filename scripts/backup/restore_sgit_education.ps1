# ================================================================
# restore_sgit_education.ps1
# sgiT Education Platform - One-Click Restore
# Version: 1.0
# Datum: 03.12.2025
# ================================================================
#
# USAGE:
#   .\restore_sgit_education.ps1 -BackupFile "D:\Backups\sgit-edu-backup-2025-12-03.zip"
#   .\restore_sgit_education.ps1 -BackupFile "..." -TargetPath "C:\xampp\htdocs\Education2"
#   .\restore_sgit_education.ps1 -BackupFile "..." -SkipDatabases
#   .\restore_sgit_education.ps1 -BackupFile "..." -Force
#
# ================================================================

param(
    [Parameter(Mandatory=$true)]
    [string]$BackupFile,
    
    [string]$TargetPath = "C:\xampp\htdocs\Education",
    
    [switch]$SkipDatabases = $false,
    [switch]$SkipConfig = $false,
    [switch]$Force = $false
)

# ================================================================
# BANNER
# ================================================================
Clear-Host
Write-Host ""
Write-Host "  ╔══════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "  ║                                                      ║" -ForegroundColor Cyan
Write-Host "  ║   ███████╗ ██████╗ ██╗████████╗                      ║" -ForegroundColor Cyan
Write-Host "  ║   ██╔════╝██╔════╝ ██║╚══██╔══╝                      ║" -ForegroundColor Cyan
Write-Host "  ║   ███████╗██║  ███╗██║   ██║                         ║" -ForegroundColor Cyan
Write-Host "  ║   ╚════██║██║   ██║██║   ██║                         ║" -ForegroundColor Cyan
Write-Host "  ║   ███████║╚██████╔╝██║   ██║                         ║" -ForegroundColor Cyan
Write-Host "  ║   ╚══════╝ ╚═════╝ ╚═╝   ╚═╝                         ║" -ForegroundColor Cyan
Write-Host "  ║                                                      ║" -ForegroundColor Cyan
Write-Host "  ║   Education Platform - Restore System v1.0           ║" -ForegroundColor Cyan
Write-Host "  ║                                                      ║" -ForegroundColor Cyan
Write-Host "  ╚══════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# ================================================================
# VALIDIERUNG
# ================================================================
Write-Host "Validiere Backup..." -ForegroundColor Cyan

# Prüfe Backup-Datei
if (-not (Test-Path $BackupFile)) {
    Write-Host "❌ FEHLER: Backup-Datei nicht gefunden!" -ForegroundColor Red
    Write-Host "  Pfad: $BackupFile" -ForegroundColor Red
    exit 1
}

$backupSize = [math]::Round((Get-Item $BackupFile).Length / 1MB, 2)
Write-Host "  ✅ Backup gefunden ($backupSize MB)" -ForegroundColor Green

# Prüfe ob Ziel existiert
if (Test-Path $TargetPath) {
    if (-not $Force) {
        Write-Host ""
        Write-Host "⚠️  WARNUNG: Zielverzeichnis existiert bereits!" -ForegroundColor Yellow
        Write-Host "  Pfad: $TargetPath" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "  Optionen:" -ForegroundColor White
        Write-Host "    1. Überschreiben (alte Version wird gesichert)" -ForegroundColor Gray
        Write-Host "    2. Abbrechen" -ForegroundColor Gray
        Write-Host ""
        $confirm = Read-Host "Überschreiben? [j/N]"
        if ($confirm -ne "j" -and $confirm -ne "J") {
            Write-Host "Abgebrochen." -ForegroundColor Red
            exit 0
        }
    }
    
    # Alte Version sichern
    $oldBackupName = "$TargetPath.old-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
    Write-Host ""
    Write-Host "Sichere alte Version..." -ForegroundColor Yellow
    Rename-Item $TargetPath $oldBackupName
    Write-Host "  ✅ Gesichert als: $oldBackupName" -ForegroundColor Gray
}

# ================================================================
# ENTPACKEN
# ================================================================
Write-Host ""
Write-Host "Entpacke Backup..." -ForegroundColor Cyan

$TempDir = Join-Path $env:TEMP "sgit-restore-$(Get-Random)"
New-Item -ItemType Directory -Path $TempDir -Force | Out-Null

try {
    Expand-Archive -Path $BackupFile -DestinationPath $TempDir -Force
    Write-Host "  ✅ Entpackt nach: $TempDir" -ForegroundColor Gray
} catch {
    Write-Host "❌ FEHLER beim Entpacken: $_" -ForegroundColor Red
    exit 1
}

# Backup-Verzeichnis finden
$BackupDir = Get-ChildItem $TempDir -Directory | Select-Object -First 1

if (-not $BackupDir) {
    Write-Host "❌ FEHLER: Ungültiges Backup-Format" -ForegroundColor Red
    Remove-Item $TempDir -Recurse -Force
    exit 1
}

# ================================================================
# MANIFEST LESEN
# ================================================================
$ManifestPath = Join-Path $BackupDir.FullName "backup_manifest.json"
if (Test-Path $ManifestPath) {
    $Manifest = Get-Content $ManifestPath -Raw | ConvertFrom-Json
    
    Write-Host ""
    Write-Host "════════════════════════════════════════════════════════" -ForegroundColor DarkGray
    Write-Host "  Backup-Info:" -ForegroundColor White
    Write-Host "    Datum:        $($Manifest.backup.date)" -ForegroundColor Gray
    Write-Host "    Version:      $($Manifest.project.version)" -ForegroundColor Gray
    Write-Host "    Erstellt auf: $($Manifest.system.hostname)" -ForegroundColor Gray
    Write-Host "    Ollama:       $($Manifest.system.ollama_model)" -ForegroundColor Gray
    Write-Host "════════════════════════════════════════════════════════" -ForegroundColor DarkGray
}

# ================================================================
# SCHRITT 1: QUELLCODE
# ================================================================
Write-Host ""
Write-Host "[1/4] 📦 Stelle Quellcode wieder her..." -ForegroundColor Cyan

$SourceDir = Join-Path $BackupDir.FullName "source"
if (Test-Path $SourceDir) {
    Copy-Item $SourceDir -Destination $TargetPath -Recurse -Force
    $fileCount = (Get-ChildItem $TargetPath -Recurse -File).Count
    Write-Host "  ✅ $fileCount Dateien wiederhergestellt" -ForegroundColor Green
} else {
    Write-Host "  ❌ Quellcode-Verzeichnis nicht gefunden!" -ForegroundColor Red
    exit 1
}

# ================================================================
# SCHRITT 2: DATENBANKEN
# ================================================================
Write-Host ""
if ($SkipDatabases) {
    Write-Host "[2/4] 💾 Datenbanken übersprungen (--SkipDatabases)" -ForegroundColor Yellow
} else {
    Write-Host "[2/4] 💾 Stelle Datenbanken wieder her..." -ForegroundColor Cyan
    
    $DbDir = Join-Path $BackupDir.FullName "databases"
    
    # Verzeichnisse erstellen
    $dbPaths = @(
        "$TargetPath\AI\data",
        "$TargetPath\wallet",
        "$TargetPath\bots\logs"
    )
    
    foreach ($path in $dbPaths) {
        if (-not (Test-Path $path)) {
            New-Item -ItemType Directory -Path $path -Force | Out-Null
        }
    }
    
    # Datenbanken kopieren
    $databases = @(
        @{Name="questions.db"; Target="$TargetPath\AI\data\questions.db"},
        @{Name="wallet.db"; Target="$TargetPath\wallet\wallet.db"},
        @{Name="bot_results.db"; Target="$TargetPath\bots\logs\bot_results.db"}
    )
    
    foreach ($db in $databases) {
        $source = Join-Path $DbDir $db.Name
        if (Test-Path $source) {
            Copy-Item $source -Destination $db.Target -Force
            $size = [math]::Round((Get-Item $source).Length / 1KB, 1)
            Write-Host "  ✅ $($db.Name) ($size KB)" -ForegroundColor Green
        } else {
            Write-Host "  ⚠️ $($db.Name) nicht im Backup" -ForegroundColor Yellow
        }
    }
}

# ================================================================
# SCHRITT 3: KONFIGURATION
# ================================================================
Write-Host ""
if ($SkipConfig) {
    Write-Host "[3/4] ⚙️ Konfiguration übersprungen (--SkipConfig)" -ForegroundColor Yellow
} else {
    Write-Host "[3/4] ⚙️ Stelle Konfiguration wieder her..." -ForegroundColor Cyan
    
    $ConfigDir = Join-Path $BackupDir.FullName "config"
    
    # Konfigurationsdateien
    $configs = @(
        @{Name="config.php"; Target="$TargetPath\config.php"},
        @{Name="db_config.php"; Target="$TargetPath\db_config.php"},
        @{Name="ollama_model.txt"; Target="$TargetPath\AI\config\ollama_model.txt"},
        @{Name="ollama_cloud.php"; Target="$TargetPath\AI\config\ollama_cloud.php"},
        @{Name="btcpay_config.php"; Target="$TargetPath\wallet\btcpay_config.php"}
    )
    
    # Zielverzeichnisse erstellen
    New-Item -ItemType Directory -Path "$TargetPath\AI\config" -Force | Out-Null
    New-Item -ItemType Directory -Path "$TargetPath\wallet" -Force | Out-Null
    
    foreach ($cfg in $configs) {
        $source = Join-Path $ConfigDir $cfg.Name
        if (Test-Path $source) {
            Copy-Item $source -Destination $cfg.Target -Force
            Write-Host "  ✅ $($cfg.Name)" -ForegroundColor Green
        }
    }
}

# ================================================================
# SCHRITT 4: BENUTZERDATEN
# ================================================================
Write-Host ""
Write-Host "[4/4] 👤 Stelle Benutzerdaten wieder her..." -ForegroundColor Cyan

$UserDir = Join-Path $BackupDir.FullName "users"
if (Test-Path $UserDir) {
    $targetUserDir = "$TargetPath\AI\users"
    New-Item -ItemType Directory -Path $targetUserDir -Force | Out-Null
    
    $userFiles = Get-ChildItem $UserDir -Filter "*.json"
    if ($userFiles) {
        Copy-Item "$UserDir\*.json" -Destination $targetUserDir -Force
        Write-Host "  ✅ $($userFiles.Count) Benutzer-Sessions" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️ Keine Benutzer-Sessions im Backup" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ⚠️ Keine Benutzerdaten im Backup" -ForegroundColor Yellow
}

# ================================================================
# AUFRÄUMEN
# ================================================================
Write-Host ""
Write-Host "Räume auf..." -ForegroundColor Gray
Remove-Item $TempDir -Recurse -Force

# ================================================================
# VALIDIERUNG
# ================================================================
Write-Host ""
Write-Host "Validiere Installation..." -ForegroundColor Cyan

$checks = @(
    @{Name="index.php"; Path="$TargetPath\index.php"; Critical=$true},
    @{Name="admin_v4.php"; Path="$TargetPath\admin_v4.php"; Critical=$true},
    @{Name="adaptive_learning.php"; Path="$TargetPath\adaptive_learning.php"; Critical=$true},
    @{Name="config.php"; Path="$TargetPath\config.php"; Critical=$true},
    @{Name="questions.db"; Path="$TargetPath\AI\data\questions.db"; Critical=$false},
    @{Name="wallet.db"; Path="$TargetPath\wallet\wallet.db"; Critical=$false}
)

$allOk = $true
$criticalFail = $false

foreach ($check in $checks) {
    if (Test-Path $check.Path) {
        Write-Host "  ✅ $($check.Name)" -ForegroundColor Green
    } else {
        if ($check.Critical) {
            Write-Host "  ❌ $($check.Name) FEHLT!" -ForegroundColor Red
            $criticalFail = $true
        } else {
            Write-Host "  ⚠️ $($check.Name) nicht vorhanden" -ForegroundColor Yellow
        }
        $allOk = $false
    }
}

# ================================================================
# ERGEBNIS
# ================================================================
Write-Host ""

if ($criticalFail) {
    Write-Host "╔══════════════════════════════════════════════════════╗" -ForegroundColor Red
    Write-Host "║                                                      ║" -ForegroundColor Red
    Write-Host "║   ❌ RESTORE FEHLGESCHLAGEN!                         ║" -ForegroundColor Red
    Write-Host "║                                                      ║" -ForegroundColor Red
    Write-Host "║   Kritische Dateien fehlen.                          ║" -ForegroundColor Red
    Write-Host "║   Prüfe das Backup auf Vollständigkeit.              ║" -ForegroundColor Red
    Write-Host "║                                                      ║" -ForegroundColor Red
    Write-Host "╚══════════════════════════════════════════════════════╝" -ForegroundColor Red
    exit 1
} elseif (-not $allOk) {
    Write-Host "╔══════════════════════════════════════════════════════╗" -ForegroundColor Yellow
    Write-Host "║                                                      ║" -ForegroundColor Yellow
    Write-Host "║   ⚠️  RESTORE MIT WARNUNGEN!                         ║" -ForegroundColor Yellow
    Write-Host "║                                                      ║" -ForegroundColor Yellow
    Write-Host "║   Einige optionale Dateien fehlen.                   ║" -ForegroundColor Yellow
    Write-Host "║   Die Anwendung sollte trotzdem funktionieren.       ║" -ForegroundColor Yellow
    Write-Host "║                                                      ║" -ForegroundColor Yellow
    Write-Host "╚══════════════════════════════════════════════════════╝" -ForegroundColor Yellow
} else {
    Write-Host "╔══════════════════════════════════════════════════════╗" -ForegroundColor Green
    Write-Host "║                                                      ║" -ForegroundColor Green
    Write-Host "║   ✅ RESTORE ERFOLGREICH!                            ║" -ForegroundColor Green
    Write-Host "║                                                      ║" -ForegroundColor Green
    Write-Host "╚══════════════════════════════════════════════════════╝" -ForegroundColor Green
}

Write-Host ""
Write-Host "  📁 Installationspfad: $TargetPath" -ForegroundColor White
Write-Host ""
Write-Host "  Nächste Schritte:" -ForegroundColor Cyan
Write-Host "    1. XAMPP starten (Apache)" -ForegroundColor Gray
Write-Host "    2. Ollama starten: ollama serve" -ForegroundColor Gray
Write-Host "    3. Browser öffnen: http://localhost/Education/" -ForegroundColor Gray
Write-Host ""

if ($oldBackupName) {
    Write-Host "  Alte Version gesichert: $oldBackupName" -ForegroundColor DarkGray
    Write-Host "  (Kann gelöscht werden wenn alles funktioniert)" -ForegroundColor DarkGray
}

Write-Host ""
