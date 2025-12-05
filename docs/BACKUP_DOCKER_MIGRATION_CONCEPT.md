# sgiT Education Platform - Backup & Docker Migration Konzept

**Erstellt:** 03. Dezember 2025  
**Version:** 1.0  
**Autor:** Claude für sgiT Solution Engineering  
**Projekt-Version:** 2.4.8

---

## 📋 Executive Summary

Dieses Dokument beschreibt:
1. **Backup-Strategie** - Vollständige Datensicherung mit One-Click
2. **Disaster Recovery** - Schnelle Wiederherstellung auf neuem System
3. **Docker-Migration** - Von XAMPP zu containerisierter Infrastruktur
4. **Technologie-Bewertung** - PHP vs Python Analyse für Version 3.0

---

## 🗂️ TEIL 1: Projektstruktur-Analyse

### Kritische Komponenten (MÜSSEN gesichert werden)

| Komponente | Pfad | Typ | Größe (ca.) |
|------------|------|-----|-------------|
| **Datenbanken** | | | |
| questions.db | `AI/data/questions.db` | SQLite | ~5 MB |
| wallet.db | `wallet/wallet.db` | SQLite | ~1 MB |
| bot_results.db | `bots/logs/bot_results.db` | SQLite | ~500 KB |
| **Konfiguration** | | | |
| config.php | `/config.php` | PHP | 1 KB |
| db_config.php | `/db_config.php` | PHP | 6 KB |
| ollama_model.txt | `AI/config/ollama_model.txt` | Text | 100 B |
| ollama_cloud.php | `AI/config/ollama_cloud.php` | PHP | 3 KB |
| btcpay_config.php | `wallet/btcpay_config.php` | PHP | 1 KB |
| **Benutzerdaten** | | | |
| User JSONs | `AI/users/*.json` | JSON | ~10 KB |
| **Content** | | | |
| CSV Templates | `docs/*.csv` | CSV | ~100 KB |
| Module Definitions | `AI/module_definitions*.json` | JSON | ~20 KB |
| **Logs (optional)** | | | |
| generator.log | `AI/logs/generator.log` | Log | variabel |
| Bot Logs | `bots/logs/*.log` | Log | variabel |

### Dateistruktur-Übersicht

```
Education/                          ← Hauptverzeichnis (~50 MB gesamt)
├── AI/                             ← AI-System
│   ├── config/                     ← 🔴 KRITISCH: Ollama-Konfiguration
│   ├── data/                       ← 🔴 KRITISCH: Fragen-Datenbank
│   ├── logs/                       ← 🟡 Optional: Generator-Logs
│   └── users/                      ← 🔴 KRITISCH: Benutzer-Sessions
├── assets/                         ← Statische Dateien (CSS/JS/Images)
├── bots/                           ← Bot-Framework
│   ├── logs/                       ← 🔴 KRITISCH: Bot-Datenbank
│   └── tests/                      ← Bot-Implementierungen
├── docs/                           ← 🔴 KRITISCH: CSV-Templates + Doku
├── includes/                       ← PHP-Klassen
├── scripts/                        ← Utility-Scripts
├── wallet/                         ← 🔴 KRITISCH: Wallet-System + DB
├── _DISABLED_*/                    ← Deaktivierte Module (archiv)
└── *.php                           ← Hauptanwendung
```

---

## 💾 TEIL 2: Backup-Strategie

### 2.1 One-Click Backup Script (PowerShell)

```powershell
# ================================================================
# backup_sgit_education.ps1
# sgiT Education Platform - One-Click Backup
# ================================================================

param(
    [string]$BackupPath = "D:\Backups\sgiT-Education",
    [switch]$IncludeLogs = $false,
    [switch]$Compress = $true
)

# Konfiguration
$SourcePath = "C:\xampp\htdocs\Education"
$Timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$BackupName = "sgit-edu-backup-$Timestamp"
$BackupDir = Join-Path $BackupPath $BackupName

Write-Host "==========================================" -ForegroundColor Green
Write-Host "  sgiT Education Backup v1.0" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Quelle:  $SourcePath"
Write-Host "Ziel:    $BackupDir"
Write-Host ""

# Backup-Verzeichnis erstellen
New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null

# 1. KRITISCHE DATENBANKEN
Write-Host "[1/6] Sichere Datenbanken..." -ForegroundColor Cyan
$DbDir = Join-Path $BackupDir "databases"
New-Item -ItemType Directory -Path $DbDir -Force | Out-Null

# SQLite VACUUM vor Backup für Konsistenz
$php = "C:\xampp\php\php.exe"
& $php -r "
    `$dbs = [
        '$SourcePath/AI/data/questions.db',
        '$SourcePath/wallet/wallet.db', 
        '$SourcePath/bots/logs/bot_results.db'
    ];
    foreach(`$dbs as `$db) {
        if(file_exists(`$db)) {
            `$conn = new SQLite3(`$db);
            `$conn->exec('PRAGMA wal_checkpoint(TRUNCATE)');
            `$conn->exec('VACUUM');
            `$conn->close();
        }
    }
"

Copy-Item "$SourcePath\AI\data\questions.db" -Destination "$DbDir\questions.db" -Force
Copy-Item "$SourcePath\wallet\wallet.db" -Destination "$DbDir\wallet.db" -Force -ErrorAction SilentlyContinue
Copy-Item "$SourcePath\bots\logs\bot_results.db" -Destination "$DbDir\bot_results.db" -Force -ErrorAction SilentlyContinue

# 2. KONFIGURATIONSDATEIEN
Write-Host "[2/6] Sichere Konfiguration..." -ForegroundColor Cyan
$ConfigDir = Join-Path $BackupDir "config"
New-Item -ItemType Directory -Path $ConfigDir -Force | Out-Null

Copy-Item "$SourcePath\config.php" -Destination $ConfigDir -Force
Copy-Item "$SourcePath\db_config.php" -Destination $ConfigDir -Force
Copy-Item "$SourcePath\AI\config\*" -Destination $ConfigDir -Force
Copy-Item "$SourcePath\wallet\btcpay_config.php" -Destination $ConfigDir -Force -ErrorAction SilentlyContinue

# 3. BENUTZERDATEN
Write-Host "[3/6] Sichere Benutzerdaten..." -ForegroundColor Cyan
$UserDir = Join-Path $BackupDir "users"
New-Item -ItemType Directory -Path $UserDir -Force | Out-Null
Copy-Item "$SourcePath\AI\users\*" -Destination $UserDir -Force -ErrorAction SilentlyContinue

# 4. CONTENT (CSVs, Module Definitions)
Write-Host "[4/6] Sichere Content..." -ForegroundColor Cyan
$ContentDir = Join-Path $BackupDir "content"
New-Item -ItemType Directory -Path $ContentDir -Force | Out-Null
Copy-Item "$SourcePath\docs\*.csv" -Destination $ContentDir -Force
Copy-Item "$SourcePath\AI\module_definitions*.json" -Destination $ContentDir -Force

# 5. QUELLCODE
Write-Host "[5/6] Sichere Quellcode..." -ForegroundColor Cyan
$CodeDir = Join-Path $BackupDir "source"
New-Item -ItemType Directory -Path $CodeDir -Force | Out-Null

# Wichtige Verzeichnisse kopieren (ohne _DISABLED_ und Logs)
$ExcludePatterns = @("_DISABLED_*", "*.log", "*.db.old*", "*.bak", "__pycache__")
robocopy "$SourcePath" "$CodeDir" /E /XD "_DISABLED_*" /XF "*.log" "*.db.old*" "*.bak" /NFL /NDL /NJH /NJS

# 6. METADATEN
Write-Host "[6/6] Erstelle Backup-Manifest..." -ForegroundColor Cyan
$Manifest = @{
    BackupDate = $Timestamp
    ProjectVersion = "2.4.8"
    SourcePath = $SourcePath
    Components = @{
        Databases = @("questions.db", "wallet.db", "bot_results.db")
        ConfigFiles = (Get-ChildItem $ConfigDir -Name)
        UserFiles = (Get-ChildItem $UserDir -Name -ErrorAction SilentlyContinue)
        ContentFiles = (Get-ChildItem $ContentDir -Name)
    }
    SystemInfo = @{
        Hostname = $env:COMPUTERNAME
        PHPVersion = (& $php -v | Select-Object -First 1)
        OllamaModel = (Get-Content "$SourcePath\AI\config\ollama_model.txt" -ErrorAction SilentlyContinue)
    }
}
$Manifest | ConvertTo-Json -Depth 4 | Out-File "$BackupDir\backup_manifest.json" -Encoding UTF8

# Optional: Komprimieren
if ($Compress) {
    Write-Host ""
    Write-Host "Komprimiere Backup..." -ForegroundColor Yellow
    $ZipPath = "$BackupPath\$BackupName.zip"
    Compress-Archive -Path $BackupDir -DestinationPath $ZipPath -Force
    Remove-Item -Path $BackupDir -Recurse -Force
    
    $ZipSize = (Get-Item $ZipPath).Length / 1MB
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host "  BACKUP ERFOLGREICH!" -ForegroundColor Green
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host "Datei: $ZipPath"
    Write-Host "Größe: $([math]::Round($ZipSize, 2)) MB"
} else {
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host "  BACKUP ERFOLGREICH!" -ForegroundColor Green
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host "Verzeichnis: $BackupDir"
}

Write-Host ""
Write-Host "Tipp: Für Restore nutze restore_sgit_education.ps1" -ForegroundColor Gray
```

### 2.2 One-Click Restore Script (PowerShell)

```powershell
# ================================================================
# restore_sgit_education.ps1
# sgiT Education Platform - One-Click Restore
# ================================================================

param(
    [Parameter(Mandatory=$true)]
    [string]$BackupFile,
    [string]$TargetPath = "C:\xampp\htdocs\Education",
    [switch]$SkipDatabases = $false,
    [switch]$Force = $false
)

Write-Host "==========================================" -ForegroundColor Green
Write-Host "  sgiT Education Restore v1.0" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""

# Prüfen ob Backup existiert
if (-not (Test-Path $BackupFile)) {
    Write-Host "FEHLER: Backup-Datei nicht gefunden: $BackupFile" -ForegroundColor Red
    exit 1
}

# Warnung wenn Ziel existiert
if ((Test-Path $TargetPath) -and -not $Force) {
    Write-Host "WARNUNG: Zielverzeichnis existiert bereits!" -ForegroundColor Yellow
    Write-Host "Pfad: $TargetPath" -ForegroundColor Yellow
    Write-Host ""
    $confirm = Read-Host "Überschreiben? (j/n)"
    if ($confirm -ne "j") {
        Write-Host "Abgebrochen." -ForegroundColor Red
        exit 0
    }
}

# Temporäres Verzeichnis für Entpacken
$TempDir = Join-Path $env:TEMP "sgit-restore-$(Get-Random)"
Write-Host "Entpacke Backup..." -ForegroundColor Cyan
Expand-Archive -Path $BackupFile -DestinationPath $TempDir -Force

# Backup-Verzeichnis finden
$BackupDir = Get-ChildItem $TempDir -Directory | Select-Object -First 1

# Manifest lesen
$ManifestPath = Join-Path $BackupDir.FullName "backup_manifest.json"
if (Test-Path $ManifestPath) {
    $Manifest = Get-Content $ManifestPath | ConvertFrom-Json
    Write-Host ""
    Write-Host "Backup-Info:" -ForegroundColor Cyan
    Write-Host "  Datum:   $($Manifest.BackupDate)"
    Write-Host "  Version: $($Manifest.ProjectVersion)"
    Write-Host "  Host:    $($Manifest.SystemInfo.Hostname)"
    Write-Host ""
}

# 1. QUELLCODE KOPIEREN
Write-Host "[1/4] Stelle Quellcode wieder her..." -ForegroundColor Cyan
$SourceDir = Join-Path $BackupDir.FullName "source"
if (Test-Path $SourceDir) {
    if (Test-Path $TargetPath) {
        # Alte Version sichern
        $OldBackup = "$TargetPath.old-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
        Rename-Item $TargetPath $OldBackup
        Write-Host "  Alte Version gesichert: $OldBackup" -ForegroundColor Gray
    }
    Copy-Item $SourceDir -Destination $TargetPath -Recurse -Force
}

# 2. DATENBANKEN
if (-not $SkipDatabases) {
    Write-Host "[2/4] Stelle Datenbanken wieder her..." -ForegroundColor Cyan
    $DbDir = Join-Path $BackupDir.FullName "databases"
    
    # Verzeichnisse erstellen falls nicht vorhanden
    New-Item -ItemType Directory -Path "$TargetPath\AI\data" -Force | Out-Null
    New-Item -ItemType Directory -Path "$TargetPath\wallet" -Force | Out-Null
    New-Item -ItemType Directory -Path "$TargetPath\bots\logs" -Force | Out-Null
    
    Copy-Item "$DbDir\questions.db" -Destination "$TargetPath\AI\data\" -Force -ErrorAction SilentlyContinue
    Copy-Item "$DbDir\wallet.db" -Destination "$TargetPath\wallet\" -Force -ErrorAction SilentlyContinue
    Copy-Item "$DbDir\bot_results.db" -Destination "$TargetPath\bots\logs\" -Force -ErrorAction SilentlyContinue
} else {
    Write-Host "[2/4] Datenbanken übersprungen (--SkipDatabases)" -ForegroundColor Yellow
}

# 3. KONFIGURATION
Write-Host "[3/4] Stelle Konfiguration wieder her..." -ForegroundColor Cyan
$ConfigDir = Join-Path $BackupDir.FullName "config"
Copy-Item "$ConfigDir\config.php" -Destination $TargetPath -Force -ErrorAction SilentlyContinue
Copy-Item "$ConfigDir\db_config.php" -Destination $TargetPath -Force -ErrorAction SilentlyContinue
Copy-Item "$ConfigDir\ollama_*" -Destination "$TargetPath\AI\config\" -Force -ErrorAction SilentlyContinue
Copy-Item "$ConfigDir\btcpay_config.php" -Destination "$TargetPath\wallet\" -Force -ErrorAction SilentlyContinue

# 4. BENUTZERDATEN
Write-Host "[4/4] Stelle Benutzerdaten wieder her..." -ForegroundColor Cyan
$UserDir = Join-Path $BackupDir.FullName "users"
if (Test-Path $UserDir) {
    New-Item -ItemType Directory -Path "$TargetPath\AI\users" -Force | Out-Null
    Copy-Item "$UserDir\*" -Destination "$TargetPath\AI\users\" -Force
}

# Aufräumen
Remove-Item $TempDir -Recurse -Force

# Validierung
Write-Host ""
Write-Host "Validiere Installation..." -ForegroundColor Cyan
$Checks = @(
    @{Name="index.php"; Path="$TargetPath\index.php"},
    @{Name="admin_v4.php"; Path="$TargetPath\admin_v4.php"},
    @{Name="adaptive_learning.php"; Path="$TargetPath\adaptive_learning.php"},
    @{Name="questions.db"; Path="$TargetPath\AI\data\questions.db"},
    @{Name="config.php"; Path="$TargetPath\config.php"}
)

$AllOk = $true
foreach ($Check in $Checks) {
    if (Test-Path $Check.Path) {
        Write-Host "  ✅ $($Check.Name)" -ForegroundColor Green
    } else {
        Write-Host "  ❌ $($Check.Name) FEHLT!" -ForegroundColor Red
        $AllOk = $false
    }
}

Write-Host ""
if ($AllOk) {
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host "  RESTORE ERFOLGREICH!" -ForegroundColor Green
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Nächste Schritte:"
    Write-Host "1. Apache/XAMPP starten"
    Write-Host "2. http://localhost/Education/ öffnen"
    Write-Host "3. Ollama starten: ollama serve"
    Write-Host ""
} else {
    Write-Host "==========================================" -ForegroundColor Yellow
    Write-Host "  RESTORE MIT WARNUNGEN!" -ForegroundColor Yellow
    Write-Host "==========================================" -ForegroundColor Yellow
    Write-Host "Einige Dateien fehlen. Prüfe das Backup."
}
```

---

## 🐳 TEIL 3: Docker-Migration

### 3.1 Warum Docker?

| Aspekt | XAMPP | Docker |
|--------|-------|--------|
| **Portabilität** | ❌ Windows-only | ✅ Überall |
| **Isolation** | ❌ Systemweit | ✅ Container isoliert |
| **Reproduzierbarkeit** | ❌ Manuelle Installation | ✅ `docker-compose up` |
| **Sicherheit** | ❌ Root-Prozesse | ✅ Eingeschränkte User |
| **Skalierung** | ❌ Single Instance | ✅ Horizontal skalierbar |
| **Updates** | ❌ Manuell | ✅ Image neu pullen |
| **Backup** | ❌ Kompliziert | ✅ Volume-Export |
| **CI/CD** | ❌ Schwierig | ✅ Native Integration |

### 3.2 Docker-Architektur für sgiT Education v3.0

```
┌─────────────────────────────────────────────────────────────────┐
│                    Docker Network: sgit-net                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────────────┐   │
│  │   nginx     │   │  php-fpm    │   │      ollama         │   │
│  │  (Reverse   │──▶│  (PHP 8.2)  │──▶│  (AI Generation)    │   │
│  │   Proxy)    │   │             │   │                     │   │
│  │  Port 80    │   │             │   │  Port 11434         │   │
│  └─────────────┘   └──────┬──────┘   └─────────────────────┘   │
│                           │                                      │
│                           ▼                                      │
│                    ┌──────────────┐                             │
│                    │   volumes    │                             │
│                    ├──────────────┤                             │
│                    │ • app-data   │ ← PHP Source Code           │
│                    │ • db-data    │ ← SQLite Databases          │
│                    │ • user-data  │ ← User Sessions             │
│                    │ • ollama-    │ ← AI Models                 │
│                    │   models     │                             │
│                    └──────────────┘                             │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.3 Docker-Compose (Production-Ready)

```yaml
# docker-compose.yml
# sgiT Education Platform v3.0 - Docker Setup
# ============================================

version: '3.8'

services:
  # ===========================================
  # NGINX - Reverse Proxy & Static Files
  # ===========================================
  nginx:
    image: nginx:1.25-alpine
    container_name: sgit-nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./docker/nginx/sites:/etc/nginx/conf.d:ro
      - ./src:/var/www/html:ro
      - ./docker/nginx/ssl:/etc/nginx/ssl:ro
    depends_on:
      - php
    networks:
      - sgit-net
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health"]
      interval: 30s
      timeout: 10s
      retries: 3

  # ===========================================
  # PHP-FPM - Application Server
  # ===========================================
  php:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    container_name: sgit-php
    volumes:
      - ./src:/var/www/html
      - ./data/databases:/var/www/data/databases
      - ./data/users:/var/www/data/users
      - ./data/logs:/var/www/data/logs
      - ./docker/php/php.ini:/usr/local/etc/php/php.ini:ro
    environment:
      - OLLAMA_HOST=ollama
      - OLLAMA_PORT=11434
      - APP_ENV=production
      - TZ=Europe/Berlin
    depends_on:
      ollama:
        condition: service_healthy
    networks:
      - sgit-net
    restart: unless-stopped

  # ===========================================
  # OLLAMA - AI Generation Service
  # ===========================================
  ollama:
    image: ollama/ollama:latest
    container_name: sgit-ollama
    ports:
      - "11434:11434"
    volumes:
      - ollama-models:/root/.ollama
    environment:
      - OLLAMA_HOST=0.0.0.0
    deploy:
      resources:
        reservations:
          memory: 4G
        limits:
          memory: 8G
    networks:
      - sgit-net
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:11434/api/tags"]
      interval: 30s
      timeout: 10s
      retries: 5
      start_period: 60s

  # ===========================================
  # BACKUP - Scheduled Backups (Optional)
  # ===========================================
  backup:
    image: alpine:3.18
    container_name: sgit-backup
    volumes:
      - ./data:/data:ro
      - ./backups:/backups
      - ./docker/backup/backup.sh:/backup.sh:ro
    entrypoint: /bin/sh
    command: ["-c", "while true; do /backup.sh; sleep 86400; done"]
    networks:
      - sgit-net
    restart: unless-stopped

# ===========================================
# VOLUMES
# ===========================================
volumes:
  ollama-models:
    name: sgit-ollama-models

# ===========================================
# NETWORKS
# ===========================================
networks:
  sgit-net:
    name: sgit-network
    driver: bridge
```

### 3.4 PHP Dockerfile

```dockerfile
# docker/php/Dockerfile
# sgiT Education - PHP 8.2 FPM mit SQLite
# =======================================

FROM php:8.2-fpm-alpine

# System-Dependencies
RUN apk add --no-cache \
    curl \
    sqlite \
    sqlite-dev \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install \
        pdo_sqlite \
        intl \
        mbstring \
        zip \
    && docker-php-ext-enable \
        pdo_sqlite

# Composer (falls später benötigt)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP-Konfiguration optimieren
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Arbeitsverzeichnis
WORKDIR /var/www/html

# Berechtigungen
RUN chown -R www-data:www-data /var/www

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
```

### 3.5 Nginx Konfiguration

```nginx
# docker/nginx/sites/sgit-education.conf

server {
    listen 80;
    server_name localhost sgit.local;
    
    root /var/www/html;
    index index.php index.html;
    
    # Logging
    access_log /var/log/nginx/sgit-access.log;
    error_log /var/log/nginx/sgit-error.log;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Health Check Endpoint
    location /health {
        return 200 "OK";
        add_header Content-Type text/plain;
    }
    
    # Static Files
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
    
    # PHP Processing
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        
        # Timeouts für AI-Generierung
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ \.(db|sqlite|log)$ {
        deny all;
    }
    
    # Default
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

### 3.6 One-Click Docker Deploy

```bash
#!/bin/bash
# deploy.sh - One-Click Docker Deployment
# =======================================

set -e

echo "==========================================="
echo "  sgiT Education v3.0 - Docker Deploy"
echo "==========================================="
echo ""

# Prüfen ob Docker läuft
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker ist nicht gestartet!"
    exit 1
fi

# Verzeichnisstruktur erstellen
echo "[1/5] Erstelle Verzeichnisse..."
mkdir -p data/{databases,users,logs}
mkdir -p backups
mkdir -p docker/{nginx/sites,nginx/ssl,php,backup}

# Ollama Model laden (falls nicht vorhanden)
echo "[2/5] Prüfe Ollama Model..."
if ! docker volume inspect sgit-ollama-models > /dev/null 2>&1; then
    echo "  Lade tinyllama:latest..."
    docker run --rm -v sgit-ollama-models:/root/.ollama ollama/ollama pull tinyllama:latest
fi

# Container starten
echo "[3/5] Starte Container..."
docker-compose up -d --build

# Warten auf Services
echo "[4/5] Warte auf Services..."
sleep 10

# Health Check
echo "[5/5] Health Check..."
if curl -s http://localhost/health > /dev/null; then
    echo "✅ Nginx OK"
else
    echo "❌ Nginx nicht erreichbar"
fi

if curl -s http://localhost:11434/api/tags > /dev/null; then
    echo "✅ Ollama OK"
else
    echo "⚠️ Ollama lädt noch..."
fi

echo ""
echo "==========================================="
echo "  DEPLOYMENT ERFOLGREICH!"
echo "==========================================="
echo ""
echo "URLs:"
echo "  • Web:    http://localhost"
echo "  • Admin:  http://localhost/admin_v4.php"
echo "  • Ollama: http://localhost:11434"
echo ""
echo "Befehle:"
echo "  • Logs:   docker-compose logs -f"
echo "  • Stop:   docker-compose down"
echo "  • Backup: ./backup.sh"
echo ""
```

---

## 🔬 TEIL 4: Technologie-Bewertung

### 4.1 Aktueller Stack vs. Alternativen

| Aspekt | PHP (Aktuell) | Python (Alternative) | Node.js (Alternative) |
|--------|---------------|---------------------|----------------------|
| **Lernkurve Team** | ✅ Bekannt | 🟡 Neu lernen | 🟡 Neu lernen |
| **Web-Entwicklung** | ✅ Nativ | 🟡 Flask/Django | ✅ Express |
| **AI/ML Integration** | 🟡 HTTP zu Ollama | ✅ Native Libraries | 🟡 HTTP zu Ollama |
| **SQLite Support** | ✅ Nativ | ✅ Nativ | ✅ better-sqlite3 |
| **Async/Concurrency** | ❌ Schwach | ✅ asyncio | ✅ Native |
| **Deployment** | ✅ Einfach | 🟡 WSGI/ASGI | ✅ PM2 |
| **Ecosystem** | ✅ Groß | ✅ Sehr groß | ✅ Sehr groß |
| **Typisierung** | 🟡 PHP 8 Types | ✅ Type Hints | ✅ TypeScript |
| **Testing** | 🟡 PHPUnit | ✅ pytest | ✅ Jest |

### 4.2 Empfehlung: Hybrid-Ansatz

```
┌─────────────────────────────────────────────────────────────┐
│                    sgiT Education v3.0                       │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Frontend (Bestehend)         Backend Services (Neu)        │
│  ─────────────────────        ──────────────────────        │
│                                                              │
│  ┌─────────────────┐          ┌─────────────────────┐       │
│  │      PHP        │          │      Python         │       │
│  │  (Web Layer)    │ ──────▶  │  (AI Service)       │       │
│  │                 │   HTTP   │                     │       │
│  │  • Admin UI     │          │  • Ollama Wrapper   │       │
│  │  • Learning UI  │          │  • Question Gen     │       │
│  │  • Wallet UI    │          │  • Quality Check    │       │
│  │  • Session Mgmt │          │  • Analytics        │       │
│  └────────┬────────┘          └─────────────────────┘       │
│           │                                                  │
│           │ SQLite                                          │
│           ▼                                                  │
│  ┌─────────────────┐                                        │
│  │    SQLite       │                                        │
│  │  (Databases)    │                                        │
│  │                 │                                        │
│  │  • questions.db │                                        │
│  │  • wallet.db    │                                        │
│  │  • analytics.db │                                        │
│  └─────────────────┘                                        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### 4.3 Konkrete Empfehlung

**Kurzfristig (v2.x → v2.5):**
- ✅ **PHP beibehalten** für Web-Layer
- ✅ Docker-Migration durchführen
- ✅ Backup-System implementieren
- ✅ Bestehende Bugs fixen

**Mittelfristig (v3.0):**
- 🔄 **Python-Microservice** für AI-Generierung
  - Bessere Ollama-Integration
  - Langchain/LlamaIndex für RAG
  - Async für parallele Generierung
- 🔄 PHP bleibt für Web-UI
- 🔄 REST API zwischen PHP ↔ Python

**Warum kein kompletter Python-Rewrite?**
1. **Aufwand:** 60-80h für kompletten Rewrite
2. **Risiko:** Funktionierende Features neu bauen
3. **ROI:** Hybrid nutzt Stärken beider Sprachen
4. **Pragmatik:** PHP funktioniert für Web-UI perfekt

### 4.4 Python AI-Service Konzept

```python
# ai_service/main.py (FastAPI)
from fastapi import FastAPI, BackgroundTasks
from pydantic import BaseModel
import httpx
import sqlite3
from typing import List, Optional

app = FastAPI(title="sgiT AI Service")

class QuestionRequest(BaseModel):
    module: str
    age_min: int = 5
    age_max: int = 21
    count: int = 10
    difficulty: Optional[int] = None

class Question(BaseModel):
    question: str
    correct_answer: str
    wrong_answers: List[str]
    explanation: str
    difficulty: int
    age_range: str

@app.post("/generate")
async def generate_questions(req: QuestionRequest, background_tasks: BackgroundTasks):
    """Generiert Fragen asynchron mit Ollama"""
    
    # Async Ollama-Aufruf
    async with httpx.AsyncClient() as client:
        response = await client.post(
            "http://ollama:11434/api/generate",
            json={
                "model": "llama3.2:latest",
                "prompt": build_prompt(req),
                "stream": False
            },
            timeout=120.0
        )
    
    # Parsing & Validierung
    questions = parse_ollama_response(response.json())
    
    # In DB speichern (Background)
    background_tasks.add_task(save_to_db, questions, req.module)
    
    return {"status": "ok", "count": len(questions), "questions": questions}

@app.get("/health")
async def health():
    return {"status": "healthy"}
```

---

## 📋 TEIL 5: Migrations-Roadmap

### Phase 1: Backup-System (1-2 Tage)
- [ ] Backup-Script erstellen & testen
- [ ] Restore-Script erstellen & testen
- [ ] Erstes vollständiges Backup anlegen
- [ ] Backup auf externem Speicher ablegen

### Phase 2: Docker-Vorbereitung (2-3 Tage)
- [ ] Docker/Docker-Compose installieren
- [ ] Dockerfile für PHP erstellen
- [ ] docker-compose.yml erstellen
- [ ] Nginx-Konfiguration
- [ ] Lokaler Test ohne Produktionsdaten

### Phase 3: Migration (1 Tag)
- [ ] Finales Backup von XAMPP
- [ ] Container mit Backup-Daten starten
- [ ] Funktionstest aller Module
- [ ] Ollama-Modell in Docker laden

### Phase 4: Python AI-Service (Optional, 3-5 Tage)
- [ ] FastAPI Service aufsetzen
- [ ] Ollama-Integration
- [ ] PHP API-Client
- [ ] Migration AI-Generator zu Python

---

## 🔐 TEIL 6: Sicherheitsempfehlungen

### Docker-Hardening
```yaml
# Zusätzliche Sicherheit in docker-compose.yml
services:
  php:
    security_opt:
      - no-new-privileges:true
    read_only: true
    tmpfs:
      - /tmp
    cap_drop:
      - ALL
```

### Backup-Verschlüsselung
```powershell
# Mit 7-Zip und Passwort
7z a -p"$SecurePassword" -mhe=on "$ZipPath" "$BackupDir"
```

---

## 📞 Support

Bei Fragen zur Migration:
- Status-Report aktualisieren
- Neuen Chat mit aktuellem Stand starten
- Backup VOR größeren Änderungen!

---

**Ende des Konzept-Dokuments**
