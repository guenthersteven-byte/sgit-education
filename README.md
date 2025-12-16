# 🎓 sgiT Education Platform

Eine umfassende Lernmanagement-Plattform für Kinder und Erwachsene (5-21 Jahre) mit KI-gestützter Fragengenerierung und Bitcoin-Belohnungssystem.

![Version](https://img.shields.io/badge/Version-3.47.0-green)
![PHP](https://img.shields.io/badge/PHP-8.3-blue)
![Docker](https://img.shields.io/badge/Docker-Ready-blue)
![License](https://img.shields.io/badge/License-GPL--3.0-red)

## ✨ Features

### 📚 21 Lernmodule
- **18 Quiz-Module:** Mathematik, Englisch, Physik, Chemie, Biologie, Geschichte, Erdkunde, Computer, Programmieren, Bitcoin, Finanzen, Musik, Kunst, Sport, Verkehr, Wissenschaft, Lesen, Unnützes Wissen
- **3 Interaktive Module:** Zeichnen (v2.0), Logik & Rätsel (inkl. Schach-Puzzles, Sudoku), Kochen
- **4.000+ KI-generierte Fragen** mit Ollama/Gemma2:2b

### 🎮 Multiplayer-Spiele (NEU!)
| Spiel | Spieler | Features |
|-------|---------|----------|
| 🎨 **Montagsmaler** | 2-8 | Canvas-Zeichnen, Chat-Raten, Rundenmanagement |
| 🎲 **Mensch ärgere dich nicht** | 2-4 | Klassisches Kreuz-Layout, animierter Würfel |
| 🃏 **Mau Mau** | 2-4 | Sonderkarten (7, 8, Bube), Farbwahl |
| ⚫ **Dame** | 2 | Schlagzwang, Damen-Umwandlung |
| ♟️ **Schach** | 2 | Vollständige Regeln inkl. Rochade, En Passant |
| 🎴 **Rommé** | 2-4 | 2x52 Karten + Joker, Auslegen/Anlegen |
| 🎰 **Poker** | 2-8 | Texas Hold'em mit Blinds und All-In |

### 💰 Bitcoin Wallet System
- Virtuelle Sats als Belohnung für richtige Antworten
- Family Wallet mit Kinder-Unterkonten
- Multiplayer-Quiz mit Sats-Einsatz und Elo-Ranking

### 🦊 Foxy AI-Chatbot
- Erklärt warum Antworten richtig/falsch sind
- Gibt Hinweise ohne die Lösung zu verraten
- 50/50 Joker-System


## 🚀 Installation

### Voraussetzungen
- Docker & Docker Compose
- Git

### Quick Start
```bash
# Repository klonen
git clone https://github.com/guenthersteven-byte/sgit-education.git
cd sgit-education

# Docker Container starten
cd docker
docker-compose up -d

# AI-Modell laden (einmalig)
docker exec sgit-education-ollama ollama pull gemma2:2b
```

### Zugriff
| Service | URL |
|---------|-----|
| **Plattform** | http://localhost:8080/adaptive_learning.php |
| **Admin** | http://localhost:8080/admin_v4.php |
| **Multiplayer** | http://localhost:8080/multiplayer.php |

Admin-Passwort: `sgit2025`

## 🛠️ Technologie-Stack

- **Backend:** PHP 8.3 mit nginx/PHP-FPM
- **Datenbank:** SQLite mit WAL-Modus
- **Container:** Docker (nginx, PHP-FPM, Ollama)
- **KI:** Ollama mit Gemma2:2b Modell
- **Frontend:** Vanilla JS, CSS3 mit Dark Theme

## 📁 Projektstruktur

```
sgit-education/
├── docker/              # Docker-Konfiguration
├── AI/                  # KI-Generator & Datenbank
├── api/                 # REST-API Endpoints
├── assets/css/          # Stylesheets (dark-theme.css, multiplayer-theme.css)
├── bots/                # Bot-System & Scheduler
├── wallet/              # Bitcoin Wallet System
├── logik/               # Interaktive Module (Schach, Sudoku)
└── includes/            # Shared PHP (version.php)
```


## 🎨 Branding

| Farbe | Hex | Verwendung |
|-------|-----|------------|
| Dunkelgrün | `#1A3503` | Header, Primary |
| Neon-Grün | `#43D240` | Akzente, Buttons |

## 📊 Status

- ✅ 21/21 Module aktiv
- ✅ 7/7 Multiplayer-Spiele
- ✅ 4.056 Fragen im Pool
- ✅ Bot-System mit Auto-Scheduler
- ✅ Docker-Ready

## 📝 Changelog

Siehe [sgit_education_ARCHIVE.md](sgit_education_ARCHIVE.md) für die komplette Versions-Historie.

## 📄 Lizenz

GPL-3.0 - Siehe [LICENSE](LICENSE) für Details.

## 👨‍💻 Autor

**Steven Günther** - [sgit.space](https://sgit.space)

---

*Made with ❤️ for education*
