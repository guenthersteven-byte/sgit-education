# sgiT Education Platform - Status Report

**Version:** 3.51.0 | **Datum:** 12. Februar 2026 | **Module:** 21/21 ✅ | **Status:** PRODUCTION READY

---

## 🚀 QUICK START

### Production (LIVE)
```
URL:            https://edu.sgit.space
Admin:          https://edu.sgit.space/admin_v4.php (VPN erforderlich!)
Plattform:      https://edu.sgit.space/adaptive_learning.php
Multiplayer:    https://edu.sgit.space/multiplayer.php
GitHub:         https://github.com/guenthersteven-byte/sgit-education
Credentials:    Bitwarden Collection "sgit.space"
```

### Development (Lokal)
```
Docker starten: cd C:\xampp\htdocs\Education\docker && docker-compose up -d
Admin:          http://localhost:8080/admin_v4.php
Plattform:      http://localhost:8080/adaptive_learning.php
```

**Technologie:** PHP 8.3, SQLite (WAL), Docker/nginx/PHP-FPM, Ollama (Gemma2:2b)  
**Branding:** #1A3503 (Dunkelgrün), #43D240 (Neon-Grün)

---

## 🏭 PRODUCTION DEPLOYMENT

| Info | Details |
|------|---------|
| **Server** | Proxmox VE 8.3.2 - LXC Container CT 105 |
| **Hostname** | sgit-edu-AIassistent |
| **IP (LAN)** | 192.168.200.145 |
| **IP (VPN)** | 10.8.0.x (WireGuard) |
| **Domain** | edu.sgit.space (NPM Proxy → .145:8080) |
| **OS** | Debian 12 (Bookworm) |
| **Resources** | 16GB RAM, 4 CPU Cores, 40GB Disk (erweitert 19.01.2026 für Voice AI) |
| **Docker** | v29.1.3 + Compose v3.0.0 |
| **SSL** | ✅ Let's Encrypt via NPM |
| **Monitoring** | ✅ Uptime Kuma (60s heartbeat) |
| **Backup** | ✅ Täglich 03:00 → QNAP NAS via rsync |
| **Migration** | 21.12.2025 15:20 (von Notebook .113 → Proxmox .145) |

### SSH Zugriff
```bash
# Direkt in CT (Passwort noetig):
ssh root@192.168.200.145
# Passwort: sgit2025

# Oder via Proxmox Host (Key-Auth):
ssh root@192.168.200.130
pct exec 105 -- bash
```
**Proxmox Host:** 192.168.200.130 (pve)
**Code-Verzeichnis:** /opt/education

### Git Deployment (Production)
```bash
# Vom Proxmox Host aus:
ssh root@192.168.200.130
pct exec 105 -- bash -c 'cd /opt/education && git fetch origin main && git checkout origin/main -- <datei>'

# Oder: Alle tracked Files aktualisieren:
pct exec 105 -- bash -c 'cd /opt/education && git fetch origin main && git reset --hard origin/main'
```
**Hinweis:** Git Repo seit 12.02.2026 initialisiert in /opt/education (remote: GitHub)

### Docker Befehle (Production)
```bash
# Ins Container-Directory
cd /opt/education/docker

# Status prüfen
docker ps

# Container neu starten
docker compose restart

# Logs anschauen
docker logs sgit-education-php --tail 50

# In PHP-Container
docker exec -it sgit-education-php bash

# Ollama Modell prüfen
docker exec sgit-education-ollama ollama list
```

---

## 🔐 SECURITY & BACKUP

### Hashed Password System (v3.48.0)
| Info | Details |
|------|---------|
| **Status** | ✅ PRODUCTION (21.12.2025 21:20) |
| **Methode** | Bcrypt-Hashing (60 chars) |
| **Migration** | v3.47.0 (Klartext) → v3.48.0 (Hash) |
| **Dateien** | auth_config.php, auth_functions.php |
| **Admin Password** | In Bitwarden (Collection: sgit.space) |
| **Tools** | /admin_password_hasher.php |
| **Backup** | migration_v3.48.0_2025-12-21_203103 |
| **Docs** | /docs/HASHED_PASSWORD_SYSTEM.md |

### QNAP Backup
| Info | Details |
|------|---------|
| **Status** | ✅ LIVE (21.12.2025 16:00) |
| **Schedule** | Täglich 03:00 Uhr (Cronjob) |
| **Source** | /opt/education (CT 105) |
| **Destination** | QNAP /share/backups/sgit-edu/daily |
| **Methode** | rsync over SSH (passwordless, 4096-bit RSA) |
| **Size** | 87 MB |
| **Speed** | 21 MB/s |
| **Script** | /usr/local/bin/backup-education-to-nas.sh |
| **Log** | /var/log/backup-education.log |
| **Retention** | Managed by QNAP Snapshots |

---

## 📋 OFFENE ITEMS (4 Stück)

### 🔴 BUG-056: Poker River-Karte - OFFEN
| Info | Details |
|------|---------|
| **Priorität** | HOCH |
| **Symptom** | Bei Turn (4 Karten) checken beide → 5. Karte (River) wird nicht aufgedeckt |
| **Datei** | `/api/poker.php` |
| **Aufwand** | ~1h |

### 🟡 FEATURE-001: Auto-Generator Level-Auswahl - OFFEN
| Info | Details |
|------|---------|
| **Priorität** | MITTEL |
| **Wunsch** | Level-Auswahl im Generator, ohne Auswahl = alle Level |
| **Datei** | `/auto_generator.php` |
| **Aufwand** | ~2h |

### 🟡 FEATURE-003: Sats zu EUR/USD Umrechnung - OFFEN
| Info | Details |
|------|---------|
| **Priorität** | HOCH |
| **Wunsch** | Live-Umrechnung Sats → EUR/USD + kleiner Chart |
| **Dateien** | `/wallet/wallet_admin.php`, neues `/api/btc_price.php` |
| **Aufwand** | ~4-6h |

### ⏳ TEST-001: Montagsmaler Fix verifizieren
| Info | Details |
|------|---------|
| **Status** | Test ausstehend |
| **Fix** | v3.45.2 - round_guessed im Polling |
| **Zu testen** | Nach richtigem Raten startet neue Runde nach 3s |

---

## 📊 SYSTEM-STATUS

| Komponente | Version | Status |
|------------|---------|--------|
| Admin Dashboard | v7.3 | ✅ |
| Adaptive Learning | v6.1 | ✅ |
| AI Generator | v11.1 | ✅ |
| Bot-System | v1.5+ | ✅ |
| Foxy Chatbot | v1.4 | ✅ |
| WalletManager | v1.5 | ✅ |
| Multiplayer-Theme | v1.0 | ✅ |
| Hashed Auth System | v1.0 | ✅ |
| Chess Theme (SVG) | v2.0 | ✅ |
| Stockfish.js Engine | v10.0 | ✅ |

### Docker Container (Production)
| Container | Status | Port |
|-----------|--------|------|
| sgit-education-nginx | ✅ Running | 8080 |
| sgit-education-php | ✅ Running | 9000 |
| sgit-education-ollama | ✅ Running | 11434 |
| sgit-voice-whisper | ✅ Running | 9001 |
| sgit-voice-piper | ✅ Running | 10200 |
| sgit-voice-qdrant | ✅ Running | 6333 |

### Multiplayer-Spiele (8 Modi, 7 Spiele)
| Spiel | Version | Beschreibung |
|-------|---------|-------------|
| 🎨 Montagsmaler | v3.34.0 | Zeichnen & Raten |
| 🎲 MADN | v3.46.0 | Mensch aergere Dich nicht |
| 🃏 Mau Mau | v3.36.0 | Kartenspiel |
| ⚫ Dame | v3.37.0 | Brettspiel |
| ♟️ Schach PvP | v3.51.0 | SVG-Figuren, CI-Theme |
| ♟️ Schach vs KI | v3.51.0 | Stockfish.js, 5 Schwierigkeitsstufen |
| 🎴 Rommé | v3.39.0 | Kartenspiel |
| 🎰 Poker | v3.40.0 | Texas Hold'em |

---

## 🔗 QUICK LINKS

### Production
| Bereich | URL |
|---------|-----|
| Admin Dashboard | https://edu.sgit.space/admin_v4.php |
| Lern-Plattform | https://edu.sgit.space/adaptive_learning.php |
| Multiplayer Hub | https://edu.sgit.space/multiplayer.php |
| Bot Dashboard | https://edu.sgit.space/bots/bot_summary.php |
| Bot Scheduler | https://edu.sgit.space/bots/scheduler/scheduler_ui.php |
| AI Generator | https://edu.sgit.space/bots/tests/AIGeneratorBot.php |
| Auto-Generator | https://edu.sgit.space/auto_generator.php |
| Flag Cleanup | https://edu.sgit.space/admin_cleanup_flags.php |
| Password Hasher | https://edu.sgit.space/admin_password_hasher.php |
| Schach PvP | https://edu.sgit.space/schach_pvp.php |
| Schach vs Computer | https://edu.sgit.space/schach_vs_computer.php |
| Schach Hub | https://edu.sgit.space/schach/index.php |

### Development (Lokal)
| Bereich | URL |
|---------|-----|
| Admin Dashboard | http://localhost:8080/admin_v4.php |
| Lern-Plattform | http://localhost:8080/adaptive_learning.php |
| Multiplayer Hub | http://localhost:8080/multiplayer.php |

---

## 🔑 WICHTIGE HINWEISE

### Für neue Chat-Sessions
1. **Diese Datei zuerst lesen** ✅
2. **Archiv bei Bedarf:** `sgit_education_ARCHIVE.md` (alle erledigten Bugs/Sessions/TODOs)
3. **Infrastruktur-Report:** `C:\Users\SG\OneDrive\sgiT\sgiT\projects\sgit-infrastruktur\sgit_status_report_infrastruktur.md`

### Technische Constraints
- **SQLite** (NICHT MySQL!) mit WAL-Modus
- **Docker/nginx/PHP-FPM** - Port 8080 (intern), NPM Proxy extern
- **Ollama** mit Gemma2:2b (Standard, 1.6GB)
- Zentrale Version: `/includes/version.php`
- **VPN MTU:** 1280 (wichtig für Verbindung!)

### Production Docker-Befehle
```bash
# SSH zum Server (via Proxmox Host empfohlen)
ssh root@192.168.200.130
pct exec 105 -- bash
# Oder direkt: ssh root@192.168.200.145

# Container Directory
cd /opt/education/docker

# Status
docker ps

# Restart
docker compose restart

# Logs
docker logs sgit-education-php --tail 50

# PHP Container betreten
docker exec -it sgit-education-php bash

# Hash generieren (für Passwort-Änderung)
docker exec -it sgit-education-php php -r "echo password_hash('NEUES_PASSWORT', PASSWORD_DEFAULT) . PHP_EOL;"

# Ollama Modell prüfen
docker exec sgit-education-ollama ollama list
```

### Backup Commands
```bash
# Manuelles Backup starten
bash /usr/local/bin/backup-education-to-nas.sh

# Backup-Log prüfen
tail -50 /var/log/backup-education.log

# Cronjob prüfen
crontab -l

# QNAP Backup verifizieren
ssh sgit-admin@192.168.200.128 "ls -lah /share/backups/sgit-edu/daily"
```

### Wichtige Pfade (Production)
| Pfad | Beschreibung |
|------|--------------|
| `/opt/education/` | Hauptverzeichnis |
| `/opt/education/docker/` | Docker Compose Files |
| `/opt/education/includes/version.php` | Zentrale Versionsverwaltung |
| `/opt/education/includes/auth_config.php` | Passwort-Hash (NICHT in Git!) |
| `/opt/education/includes/auth_functions.php` | Auth-Bibliothek |
| `/opt/education/AI/config/ollama_model.txt` | AI-Modell Konfiguration |
| `/opt/education/AI/data/questions.db` | Fragen-Datenbank (4,904) |
| `/opt/education/wallet/*.db` | Wallet-Datenbanken |
| `/opt/education/logs/auth_audit.log` | Auth-Audit-Log |
| `/opt/education/assets/js/stockfish/` | Stockfish.js Engine (1.6MB, lokal gehostet) |
| `/opt/education/assets/js/chess-pieces.js` | SVG Staunton-Schachfiguren (12 Stueck) |
| `/opt/education/assets/css/chess-theme.css` | CI-konformes Schach-Design |
| `/opt/education/schach_vs_computer.php` | Schach vs KI (Stockfish, 5 Stufen) |
| `/usr/local/bin/backup-education-to-nas.sh` | Backup-Script |
| `/var/log/backup-education.log` | Backup-Log |

---

## 🎓 MODULE ÜBERSICHT (21/21)

### Quiz-Module (18) - 4,904 Fragen
Mathematik, Englisch, Lesen, Physik, Erdkunde, Wissenschaft, Geschichte, Computer, Chemie, Musik, Programmieren, Bitcoin, Finanzen, Kunst, Verkehr, Sport, Unnützes Wissen, Biologie

**Fragen-Statistik:**
- Gesamt: 4,904 Fragen
- AI-generiert: 1,178
- CSV-Import: 3,720
- Mit Erklärung: 3,710

### Interaktive Module (3)
- ✏️ Zeichnen (v2.0 mit Ebenen, Brushes, Vorlagen)
- 🧩 Logik & Rätsel (inkl. Schach-Puzzles, Sudoku)
- 🍳 Kochen

### Wallet-System
- Total Sats verteilt: 12,082
- Bot-System mit automatischer Belohnung
- Admin-Dashboard für Wallet-Management

---

## 📚 ARCHIV-VERWEIS

Für historische Informationen (70+ erledigte Bugs, alle Sessions, TODOs) siehe:
**`C:\xampp\htdocs\Education\sgit_education_ARCHIVE.md`**

---

## 🤖 VOICE AI PROJEKT (19.01.2026) - ✅ LIVE

### Überblick
CT 105 (sgit-edu-AIassistent) hostet den sgit.space AI Assistant:
- **Telegram Voice Bot** für Kundenanfragen
- **Keyword-basiertes Antwortsystem** (kein LLM wegen Hardware-Limits)
- **Whisper STT** für Sprachnachrichten

### n8n Workflow: sgit Voice Assistant v1.0.0
| Info | Details |
|------|---------|
| **Version** | 1.0.0 |
| **Datei** | `services/n8n/workflows/sgit-voice-assistant.json` |
| **Typ** | Keyword-Matching (nicht LLM-basiert) |
| **Kategorien** | 22 Antwortkategorien |
| **Keywords** | ~500 deutsche Keywords |

**Features:**
- Infos zu Services (E-Mail, Cloud, VPN, Monitoring, Bitcoin, Datenschutz)
- Preisanfragen und Terminvereinbarung
- Kontaktdaten-Erkennung (Telefon + E-Mail via Regex)
- Weiterleitung unbekannter Fragen an Steven (Chat ID: 1241919688)
- Whisper Speech-to-Text für Sprachnachrichten
- VIP-Whitelist für Jane (direkte Weiterleitung)
- Affirmative/Negative Erkennung ("Ja"/"Nein" Konversationsfluss)

### Voice AI Stack ✅ INSTALLIERT (19.01.2026)
| Komponente | Software | Port | Status |
|------------|----------|------|--------|
| Whisper STT | onerahmet/openai-whisper-asr-webservice | 9001 | ✅ Running |
| Piper TTS | rhasspy/wyoming-piper (de_DE-thorsten) | 10200 | ⏸️ Nicht genutzt |
| Qdrant | qdrant/qdrant (Vector DB) | 6333 | ⏸️ Nicht genutzt |
| Ollama | qwen2:0.5b (für Tests) | 11434 | ⏸️ Nicht genutzt |

**Hinweis:** LLM (Ollama) wurde wegen CPU-Last auf Intel N100 durch Keyword-Matching ersetzt.

### Telegram Bot
- **Bot:** @sgit_voice_bot
- **Token:** In Bitwarden (8417548255:...)
- **Whitelist:** User ID 1745045311 (Jane)
- **Steven Chat ID:** 1241919688

### Ressourcen-Upgrade & Hostname ✅ ERLEDIGT (19.01.2026)
- RAM: 8GB → **16GB** ✅
- Disk: 20GB → **40GB** ✅
- Hostname: sgit-edu → **sgit-edu-AIassistent** ✅

### Backup vor Änderungen
```
/opt/education/docker/docker-compose.yml.BACKUP_2026-01-19_pre-voice-ai
```

---

## 📋 VERSION HISTORY

### v3.51.0 (12.02.2026) - SCHACH KI + VISUAL REDESIGN
- ✅ Neuer Modus: Schach vs Computer (Stockfish.js v10, 5 Schwierigkeitsstufen)
- ✅ SVG Staunton-Schachfiguren ersetzen Unicode-Text (chess-pieces.js)
- ✅ CI-konformes Board-Design: Gruen-Spektrum, Glasmorphismus (chess-theme.css)
- ✅ Schach-Hub mit Spielmodus-Karten (Gegen Spieler / Gegen Computer)
- ✅ PvP: Moduswahl im Lobby, SVG-Rendering, neues Theme
- ✅ Stockfish.js lokal gehostet (kein CDN, kein SharedArrayBuffer noetig)
- ✅ Git-Deployment auf Server eingerichtet (via Proxmox pct exec)
- **Neue Dateien:** schach_vs_computer.php, chess-theme.css, chess-pieces.js, stockfish-loader.js, stockfish.js
- **Geaendert:** schach_pvp.php, schach/index.php, includes/version.php

### v3.50.0 (19.01.2026) - VOICE ASSISTANT v1.0.0
- ✅ sgit Voice Assistant Workflow v1.0.0 fertiggestellt
- ✅ Keyword-Matching statt LLM (Hardware-Limits Intel N100)
- ✅ 22 Antwortkategorien, ~500 deutsche Keywords
- ✅ Kontaktdaten-Erkennung (Telefon + E-Mail)
- ✅ Weiterleitung an Steven bei unbekannten Fragen
- ✅ VIP-Whitelist und Affirmative/Negative Erkennung

### v3.49.0 (19.01.2026) - VOICE AI STACK
- ✅ Voice AI Stack installiert (Whisper+Ollama+Piper+Qdrant)
- ✅ Ressourcen-Upgrade durchgeführt (8GB→16GB RAM, 20GB→40GB Disk)
- ✅ Telegram Bot @sgit_voice_bot erstellt
- ✅ LXC Hostname umbenannt: sgit-edu → sgit-edu-AIassistent
- ✅ docker-compose.yml Backup erstellt

### v3.48.0 (21.12.2025) - PRODUCTION SECURITY UPGRADE
- ✅ Hashed Password System (Bcrypt statt Klartext)
- ✅ Migration auf Proxmox Server (CT 105)
- ✅ QNAP Backup automatisiert (täglich 03:00)
- ✅ Uptime Kuma Monitoring
- ✅ Production-Passwort gesetzt
- ✅ Alle Admin-Bereiche auf Hash-Verifikation umgestellt

### v3.47.0 (16.12.2025)
- Letzter Stand vor Production Migration
- Lief auf Notebook sg-dev-113 (.113)
- Klartext-Passwörter (DEPRECATED)

---

*Status-Report aktualisiert am 12.02.2026 - v3.51.0 Schach KI + Visual Redesign*
*Schach: SVG-Figuren, Stockfish.js KI, 5 Schwierigkeitsstufen, CI-konformes Design*
*Archivierte Items: 70+ Bugs, 20 TODOs, 10 Sessions*
