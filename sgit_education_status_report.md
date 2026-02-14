# sgiT Education Platform - Status Report

**Version:** 3.56.0 | **Datum:** 14. Februar 2026 | **Module:** 22/22 | **Status:** PRODUCTION

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

**Technologie:** PHP 8.3, SQLite (WAL), Docker/nginx/PHP-FPM
**Branding:** #1A3503 (Dunkelgruen), #43D240 (Neon-Gruen), Font: Space Grotesk

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
| **Security Headers** | CSP, HSTS, X-Frame-Options, X-XSS-Protection (v3.53.2) |
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
**Hinweis:** Git Repo in /opt/education (remote: GitHub). questions.db ist NICHT im Git (seit 13.02.2026 aus Tracking entfernt).

### Docker Befehle (Production)
```bash
# Ins Container-Directory
cd /opt/education/docker

# Status pruefen
docker ps

# Container neu starten
docker compose restart

# Logs anschauen
docker logs sgit-education-php --tail 50

# In PHP-Container
docker exec -it sgit-education-php bash
```

---

## 🔐 SECURITY & BACKUP

### Hashed Password System (v3.48.0)
| Info | Details |
|------|---------|
| **Status** | ✅ PRODUCTION (21.12.2025 21:20) |
| **Methode** | Bcrypt-Hashing (60 chars), Legacy-Klartext entfernt (13.02.2026) |
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

## 📋 OFFENE ITEMS (5 Stück)

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

### ⏳ TEST-002: Hausaufgaben-System v3.53.0 testen
| Info | Details |
|------|---------|
| **Status** | Tests ausstehend |
| **Zu testen** | 1) Mobile Kamera-Capture oeffnet Rueckkamera 2) 5MB Foto wird komprimiert 3) +15 SATs pro Upload 4) Filter nach Fach/Schuljahr 5) Tageslimit (11. Upload abgelehnt) 6) Achievement "Erste Hausaufgabe" 7) EXIF-Rotation 8) OCR-Text bei deutschem Text 9) Schulinfo speichern/laden 10) Nav-Link sichtbar |

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
| Foxy Chatbot | v3.0 (DB-only) | Running |
| WalletManager | v1.6 | ✅ |
| HausaufgabenManager | v1.0 | ✅ |
| Multiplayer-Theme | v1.0 | ✅ |
| Hashed Auth System | v1.0 | ✅ |
| Chess Theme (SVG) | v2.0 | ✅ |
| Stockfish.js Engine | v10.0 | ✅ |
| Playing Cards SVG | v1.0 | ✅ |
| Dame Pieces SVG | v1.0 | ✅ |
| MADN Pieces SVG | v1.0 | ✅ |
| Poker AI Engine | v1.0 | ✅ |

### Docker Container (Production)
| Container | Status | Port |
|-----------|--------|------|
| sgit-education-nginx | Running | 8080 |
| sgit-education-php | Running | 9000 |
| sgit-voice-whisper | Running | 9001 |
| sgit-voice-piper | Running | 10200 |
| sgit-voice-qdrant | Running | 6333 |

**Entfernt (v3.55.0):** Ollama Container + 6.2GB Models (Disk 69% -> 30%)

### Multiplayer-Spiele (13 Modi, 7 Spiele)
| Spiel | Version | Beschreibung |
|-------|---------|-------------|
| 🎨 Montagsmaler | v3.34.0 | Zeichnen & Raten |
| 🎲 MADN PvP | v3.52.0 | Mensch aergere Dich nicht (SVG-Figuren) |
| 🎲 MADN vs KI | v3.52.0 | 1-3 KI-Gegner, 3 Schwierigkeitsstufen |
| 🃏 Mau Mau PvP | v3.52.0 | Kartenspiel (SVG-Karten) |
| 🃏 Mau Mau vs KI | v3.52.0 | 3 Schwierigkeitsstufen |
| ⚫ Dame PvP | v3.52.0 | Brettspiel (SVG-Steine, CI-Gruen) |
| ⚫ Dame vs KI | v3.52.0 | Minimax + Alpha-Beta, 5 Stufen |
| ♟️ Schach PvP | v3.51.0 | SVG-Figuren, CI-Theme |
| ♟️ Schach vs KI | v3.51.0 | Stockfish.js, 5 Schwierigkeitsstufen |
| 🎴 Rommé PvP | v3.52.0 | Kartenspiel (SVG-Karten) |
| 🎴 Rommé vs KI | v3.52.0 | Meld-Finding KI, 3 Stufen |
| 🎰 Poker PvP | v3.52.0 | Texas Hold'em (SVG-Karten) |
| 🎰 Poker vs KI | v3.52.0 | 2-4 KI-Gegner, 3 Stufen |

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
| Mau Mau vs Computer | https://edu.sgit.space/maumau_vs_computer.php |
| Dame vs Computer | https://edu.sgit.space/dame_vs_computer.php |
| MADN vs Computer | https://edu.sgit.space/madn_vs_computer.php |
| Romme vs Computer | https://edu.sgit.space/romme_vs_computer.php |
| Poker vs Computer | https://edu.sgit.space/poker_vs_computer.php |
| Hausaufgaben | https://edu.sgit.space/hausaufgaben/ |

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
- **Ollama entfernt** (v3.55.0) - Foxy nutzt jetzt DB-only Matching
- **TTS:** Web Speech API (Browser-nativ, kein Server-Load)
- Zentrale Version: `/includes/version.php`
- **VPN MTU:** 1280 (wichtig fuer Verbindung!)

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

# Hash generieren (fuer Passwort-Aenderung)
docker exec -it sgit-education-php php -r "echo password_hash('NEUES_PASSWORT', PASSWORD_DEFAULT) . PHP_EOL;"
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
| `/opt/education/AI/data/questions.db` | Fragen-Datenbank (3,716 aktiv / 4,904 gesamt) |
| `/opt/education/wallet/*.db` | Wallet-Datenbanken |
| `/opt/education/logs/auth_audit.log` | Auth-Audit-Log |
| `/opt/education/assets/js/stockfish/` | Stockfish.js Engine (1.6MB, lokal gehostet) |
| `/opt/education/assets/js/chess-pieces.js` | SVG Staunton-Schachfiguren (12 Stueck) |
| `/opt/education/assets/js/playing-cards.js` | SVG Spielkarten-Bibliothek (52+Joker) |
| `/opt/education/assets/js/dame-pieces.js` | SVG Dame-Spielsteine (CI-Gruen) |
| `/opt/education/assets/js/madn-pieces.js` | SVG MADN-Spielfiguren (4 Farben) |
| `/opt/education/assets/js/poker-ai.js` | Poker KI-Engine (Hand-Evaluation) |
| `/opt/education/hausaufgaben/` | Hausaufgaben-Upload-System (Manager, API, UI) |
| `/opt/education/hausaufgaben/hausaufgaben.db` | Hausaufgaben-Datenbank (auto-created) |
| `/opt/education/uploads/hausaufgaben/` | Upload-Verzeichnis (nach Kind/Schuljahr/Fach) |
| `/opt/education/assets/css/chess-theme.css` | CI-konformes Schach-Design |
| `/opt/education/schach_vs_computer.php` | Schach vs KI (Stockfish, 5 Stufen) |
| `/opt/education/maumau_vs_computer.php` | Mau Mau vs KI (3 Stufen) |
| `/opt/education/dame_vs_computer.php` | Dame vs KI (Minimax, 5 Stufen) |
| `/opt/education/madn_vs_computer.php` | MADN vs 1-3 KI-Gegner |
| `/opt/education/romme_vs_computer.php` | Romme vs KI (Meld-Finding) |
| `/opt/education/poker_vs_computer.php` | Poker vs 2-4 KI-Gegner |
| `/usr/local/bin/backup-education-to-nas.sh` | Backup-Script |
| `/var/log/backup-education.log` | Backup-Log |

---

## 🎓 MODULE ÜBERSICHT (22/22)

### Quiz-Module (18) - 4,904 Fragen
Mathematik, Englisch, Lesen, Physik, Erdkunde, Wissenschaft, Geschichte, Computer, Chemie, Musik, Programmieren, Bitcoin, Finanzen, Kunst, Verkehr, Sport, Unnützes Wissen, Biologie

**Fragen-Statistik:**
- Gesamt: 4,904 Fragen (3,725 aktiv nach Cleanup 13.02.2026)
- AI-generiert: 1,178 (alle deaktiviert - Qualitaetsprobleme)
- CSV-Import: 3,720
- Mit Erklärung: 3,710

### Interaktive Module (4)
- ✏️ Zeichnen (v2.0 mit Ebenen, Brushes, Vorlagen)
- 🧩 Logik & Rätsel (inkl. Schach-Puzzles, Sudoku)
- 🍳 Kochen
- 📝 Hausaufgaben (Upload, OCR, +15 SATs/Upload)

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

### Voice AI Stack (19.01.2026, aktualisiert 14.02.2026)
| Komponente | Software | Port | Status |
|------------|----------|------|--------|
| Whisper STT | onerahmet/openai-whisper-asr-webservice | 9001 | Running |
| Piper TTS | rhasspy/wyoming-piper (de_DE-thorsten-high) | 10200 | Running |
| Qdrant | qdrant/qdrant (Vector DB) | 6333 | Running |

**Ollama ENTFERNT** (v3.55.0): Container, Volume (6.2GB), Images geloescht. Disk 69% -> 30%.

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

### v3.56.0 (14.02.2026) - TTS VORLESEN-FUNKTION (WEB SPEECH API)
- Text-to-Speech fuer alle Quiz-Fragen via Web Speech API (Browser-nativ, kein Server-Load)
- Vorlesen-Button (Lautsprecher-Icon) neben jeder Frage zum manuellen Vorlesen
- Auto-Vorlesen: Jede neue Frage wird automatisch auf Deutsch vorgelesen (Standard: AN)
- Auto-Vorlesen Toggle im Quiz-Header (Checkbox)
- Erklaerung wird nach Antwort vorgelesen ("Richtig!" / "Falsch! Die richtige Antwort ist...")
- Deutsche Stimme bevorzugt, Sprechgeschwindigkeit 0.9x
- Sprache stoppt automatisch beim Quiz-Schliessen oder naechste Frage laden
- Touch-optimiert: Button 56px auf Mobile fuer Kinderhaende
- Sprechanimation: Button pulsiert gruen waehrend des Vorlesens
- **Geaendert:** adaptive_learning.php (CSS + HTML + JS), includes/version.php

### v3.55.0 (14.02.2026) - MULTIPLAYER GAME HUB + OLLAMA REMOVAL
- **Multiplayer Game Hub:** multiplayer.php komplett redesigned als kindgerechter Spiele-Hub
  - 3 Kategorien: Brettspiele, Kartenspiele, Kreativ & Quiz
  - Grosse bunte Game-Cards mit Emoji-Icons, Hover-Animationen
  - Direkte "Gegen Freunde" / "Gegen Computer" Buttons auf jeder Karte
  - Touch-optimiert: min 56px Targets, responsive Grid
- **CSS Deduplizierung:** ~1050 Zeilen redundantes Inline-CSS aus 7 Spielen entfernt
  - Gemeinsame Styles in assets/css/multiplayer-theme.css verschoben
  - Lobby, Buttons, Header, Input-Gruppen, Player-Slots vereinheitlicht
- **Shared PHP Header:** includes/game_header.php fuer alle 7 Multiplayer-Spiele
- **Ollama komplett entfernt:** Container, Volume (6.2GB), Images geloescht
  - Disk-Nutzung CT 105: 69% (26GB) -> 30% (12GB)
  - docker-compose.yml: Ollama Service, Volume, Env-Vars entfernt
- **Foxy Chatbot v3.0 (DB-only):** ClippyChat.php komplett umgeschrieben
  - Alle Ollama/AI-Aufrufe entfernt
  - Chat: Trigger-Word-Matching aus foxy_responses DB
  - Explain: questions.explanation Feld aus questions DB
  - Hint: Elimination-Workflow (entfernt falsche Antworten)
  - Ask: Keyword-Suche in questions DB
  - Status: mode "database" (kein AI-Modell mehr)
- **Statistics-Optimierung:** statistics.php komplett ueberarbeitet
  - 42 Altersgruppen -> 9 sinnvolle Gruppen (ab 5, ab 6, ... ab 18+)
  - Fehlende Modul-Icons ergaenzt (Sport, Finanzen, Kochen, etc.)
  - Foxy-Tags: Dark Theme fixes
  - Chart.js: Dark Theme Defaults, Prozent-Tooltips
- **Geaendert:** multiplayer.php, 7 Spiele (dame, madn, maumau, poker, montagsmaler, schach_pvp, romme), clippy/ClippyChat.php, statistics.php, docker/docker-compose.yml, includes/version.php
- **Neu:** includes/game_header.php, assets/css/multiplayer-theme.css

### v3.54.0 (14.02.2026) - BUGFIXES, SECURITY-AUDIT, FRAGEN-QUALITAET, BRANDING

**Hausaufgaben-Upload Fix:**
- Root Cause: `uploads/hausaufgaben/` Verzeichnis gehoerte root:root statt www-data:www-data
- PHP konnte keine Dateien schreiben → 500 Internal Server Error
- Fix: Ownership korrigiert + GD Extension Check + besseres Error-Logging in upload.php und HausaufgabenManager.php

**Fragen-Qualitaet (4.856 aktive Fragen):**
- 671 Buchstaben-Antworten (A/B/C/D) automatisch auf Textantworten korrigiert
- 15 kaputte KI-generierte Fragen deaktiviert (Templates, Nonsense, falsche Fakten)
- 89 neue handkuratierte Fragen eingefuegt (44 Sport + 45 Unnuetzes Wissen)
  - Faktengepruefte, korrekte Antworten mit Erklaerungen
  - 4 Altersgruppen: 5-8, 8-11, 11-14, 14+ Jahre
  - Source: manual_curated, Model: claude-opus-4
- Analyse-Scripts erstellt: analyze_questions.py, fix_letter_answers.py, delete_broken_questions.py
- Sport: 140 → 184 aktive Fragen | Unnuetzes Wissen: 132 → 177 aktive Fragen

**Security-Audit (22 Findings):**
- 6 KRITISCH: SQL-Injection in HausaufgabenManager (Zeile 651), DB-Dateien ohne .htaccess-Schutz,
  Session-Fixation (fehlende session_regenerate_id), Hardcoded Legacy-Passwort, Path-Traversal-Risiko,
  Shell-Injection-Haertung bei Tesseract OCR
- 8 HOCH: Fehlende CSRF-Protection bei APIs, schwache PIN-Komplexitaet (4 Digits),
  kein Rate-Limiting bei Login, XSS-Risiko bei Quiz-Ausgabe, Directory-Listing,
  fehlende Security-Headers in APIs, CSP unsafe-inline, Docker-Pfade hardcoded
- 5 MITTEL: Open Redirect bei Login, Session-Timeout fehlt, kein Security-Logging,
  Input-Validation Birthdate, unverschluesselte Backups
- 3 NIEDRIG: Error-Reporting in Production, SameSite-Cookie inkonsistent, HEIC-Support ohne ImageMagick

**Branding-Konsistenz:**
- profil/index.php komplett umgeschrieben: Comic Sans + Light Theme → Space Grotesk + Dark Theme
- Alle 22+ Seiten jetzt einheitlich: Dark Theme, Space Grotesk, #1A3503/#43D240
- style.css (alt, Comic Sans) wird von keiner Seite mehr verwendet

**CT 105 Live-Status:**
- 5 Docker-Container laufen (nginx, php, whisper, qdrant, piper) - Ollama entfernt in v3.55.0
- Disk: 30% belegt (12GB/40GB) - nach Ollama-Removal (vorher 69%)
- CPU/RAM: Minimal (~4%)
- Git-Deploy: Commit 9687b22 + pct push fuer v3.56.0

**Geaendert:** HausaufgabenManager.php, upload.php, profil/index.php, 6 neue Scripts
**Erstellt:** insert_quality_questions.py, analyze_questions.py, fix_letter_answers.py, delete_broken_questions.py, check_gd.php, QUESTION_QUALITY_REPORT.md, FINDINGS_SUMMARY.md

### v3.53.4 (13.02.2026) - DIFFICULTY FILTER & FRAGEN-QUALITAET
- Altersbasierter Schwierigkeitsfilter: 5-6J=Diff1, 7-8J=Diff2, 9-10J=Diff3, 11-12J=Diff4, 13+=alle
- Difficulty-Filter in ALLEN 4 Fallback-Schritten (SCHRITT 1-4) der Fragenauswahl
- SCHRITT 3 Fallback begrenzt auf max userAge+3 (Kinder bekommen keine Erwachsenen-Fragen)
- 9 defekte Fragen deaktiviert (falsche Antworten, Antwort nicht in Optionen, KI-Muell)
- Vorher: 5-Jaehriger konnte 3716 Fragen bekommen (inkl. Difficulty 5, age_min 18)
- Nachher: 5-Jaehriger bekommt max 749 Fragen (nur Difficulty 1, age_min<=8)
- Davon 391 altersgerecht (SCHRITT 1), Rest ueber kontrollierte Fallbacks
- Aktive Fragen: 3716 (vorher 3725)
- **Geaendert:** adaptive_learning.php (PHP Queries + Bindings), questions.db (9 deaktiviert)

### v3.53.3 (13.02.2026) - TTS VORLESE-FUNKTION
- Text-to-Speech fuer alle Quiz-Fragen via Web Speech API (Browser-nativ, kein Server-Load)
- Lautsprecher-Button (SVG) neben jeder Frage zum manuellen Vorlesen
- Auto-Vorlesen: Jede neue Frage wird automatisch auf Deutsch vorgelesen (Frage + Antwortoptionen)
- Auto-Vorlesen Toggle im Quiz-Header (Einstellung in localStorage gespeichert)
- Deutsche Stimme bevorzugt, Sprechgeschwindigkeit 0.85x
- Sprache stoppt automatisch beim Quiz-Schliessen
- **Geaendert:** adaptive_learning.php (CSS + HTML + JS)

### v3.53.2 (13.02.2026) - SECURITY & QUALITY HARDENING
- Legacy-Klartext-Passwort komplett aus auth_config.php entfernt (inkl. Kommentare)
- Comic Sans MS durch Space Grotesk ersetzt (CI-konform)
- Doppelte Navigation entfernt (Hausaufgaben-Kachel, Dashboard-Link)
- Security Headers in nginx: CSP, HSTS, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy
- Session Cookie: secure-Flag auf true (statt isset HTTPS) fuer NPM Reverse Proxy
- SQLite PRAGMA foreign_keys=ON hinzugefuegt (referentielle Integritaet)
- Alle DB-Pragmas verifiziert: foreign_keys=1, busy_timeout=5000, cache_size=64MB, journal_mode=WAL
- **Geaendert:** auth_config.php, style.css, adaptive_learning.php, education.conf, security.php, db_config.php (6 Dateien)

### v3.53.1 (13.02.2026) - FRAGEN-DB CLEANUP
- 1.132 fehlerhafte Fragen deaktiviert (is_active=0):
  - 647 auto_generator mit Buchstaben-Antworten (A/B/C/D statt Text)
  - 473 ai_generated mit Formatproblemen (Englisch-Mix, Nonsens-Antworten)
  - 12 csv_import mit Buchstaben-Antworten
- 13 Unicode-Encoding-Probleme in Options gefixt (u00e4 -> ae etc.)
- questions.db aus Git-Tracking entfernt (.gitignore)
- Backup: questions.db.bak.post_cleanup_20260213
- 1 Frage mit Metadaten in Options gefixt (ID 4588: Schwierigkeitsgrad als Option)
- 2 weitere fehlerhafte Fragen deaktiviert (doppelte Optionen, unsinnige Antworten)
- **Verbleibend:** 3.725 aktive Fragen in 19 Modulen

### v3.53.0 (12.02.2026) - HAUSAUFGABEN-UPLOAD SYSTEM
- ✅ Neues Modul: Hausaufgaben-Fotos vom Handy hochladen (Kamera + Galerie)
- ✅ Sortierung nach Fach (15 Faecher), Klassenstufe (1-13) und Schuljahr
- ✅ Pro Upload +15 SATs (via creditSats, umgeht calculateReward)
- ✅ Tesseract OCR: Text-Extraktion aus Fotos (Deutsch + Englisch)
- ✅ EXIF-Rotation, Skalierung (max 1920px), JPEG-Kompression (85%)
- ✅ Schulinfo-Banner: Klassenstufe + Schuljahr pro Kind speicherbar
- ✅ Galerie mit Filter (Fach-Pills, Schuljahr-Dropdown)
- ✅ Detail-Modal mit OCR-Text-Anzeige
- ✅ 6 neue Achievements (homework-Kategorie): Erste HA, 10/50/100 Uploads, Allrounder, 7-Tage-Streak
- ✅ WalletManager v1.6: creditSats(), current_grade + current_school_year Migrationen
- ✅ Docker: Tesseract 5.5.1 (deu+eng) + EXIF Extension installiert
- ✅ nginx: client_max_body_size 10m, camera=(self) Permission
- ✅ Mobile-first Dark Theme, SATs-Animation nach Upload
- ⏳ Tests ausstehend (Mobile-Kamera, Upload, OCR, Achievements, Filter)
- **Neue Dateien:** HausaufgabenManager.php, upload.php, api.php, index.php, hausaufgaben.css, hausaufgaben.js (6 Dateien)
- **Geaendert:** adaptive_learning.php, WalletManager.php, AchievementManager.php, Dockerfile, education.conf, security_headers.php, functions.php, version.php, .gitignore (9 Dateien)

### v3.52.0 (12.02.2026) - MULTIPLAYER KI-GEGNER + SVG REDESIGN
- ✅ Alle 5 Multiplayer-Spiele mit KI-Gegner erweitert (Mau Mau, Dame, MADN, Romme, Poker)
- ✅ SVG Spielkarten-Bibliothek (playing-cards.js): 52 Karten + Joker + Kartenrueckseite
- ✅ SVG Dame-Spielsteine (dame-pieces.js): CI-Gruen + Schwarz, Krone fuer Damen
- ✅ SVG MADN-Spielfiguren (madn-pieces.js): 4 Farben als Kegel-Pins
- ✅ Poker KI-Engine (poker-ai.js): Hand-Evaluation, Pot Odds, Bet-Decisions
- ✅ Mau Mau vs KI: 3 Schwierigkeitsstufen (Zufall, Sonderkarten-Prio, Kartenzaehlen)
- ✅ Dame vs KI: 5 Stufen (Zufall bis Minimax+Alpha-Beta Tiefe 7)
- ✅ MADN vs KI: 1-3 KI-Gegner, 3 Stufen (Zufall, Priorisierung, Gefahrenbewertung)
- ✅ Romme vs KI: 3 Stufen (Zufall, Meld-Finding, Vorausplanung)
- ✅ Poker vs KI: 2-4 KI-Gegner, 3 Stufen (Loose-Passive, Tight-Aggressive, Advanced)
- ✅ Alle PvP-Spiele: SVG-Grafiken + "Gegen Computer" Button in Lobby
- ✅ Dame PvP: CI-Gruen Board-Redesign (#2a5a0a/#d4c8a0)
- **Neue Dateien:** playing-cards.js, dame-pieces.js, madn-pieces.js, poker-ai.js, maumau_vs_computer.php, dame_vs_computer.php, madn_vs_computer.php, romme_vs_computer.php, poker_vs_computer.php (9 Dateien, 6.173 Zeilen)
- **Geaendert:** maumau.php, dame.php, madn.php, romme.php, poker.php, includes/version.php

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

*Status-Report aktualisiert am 14.02.2026 - v3.56.0 TTS Vorlesen + Multiplayer Hub + Ollama Removal*
*Ollama entfernt (Disk 69%->30%), Foxy DB-only v3.0, Web Speech API TTS fuer Kinder*
