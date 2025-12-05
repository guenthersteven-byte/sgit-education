# 🎓 sgiT Education Platform

Eine umfassende Lernplattform für Kinder und Jugendliche (5-21 Jahre) mit Bitcoin-Wallet-Integration, Gamification und KI-gestützter Fragengenerierung.

![Version](https://img.shields.io/badge/version-3.7.9-green)
![PHP](https://img.shields.io/badge/PHP-8.3-blue)
![License](https://img.shields.io/badge/license-GPL--3.0-orange)
![Questions](https://img.shields.io/badge/Fragen-3.263-brightgreen)

## 🌟 Features

- **16 Lernmodule**: Mathematik, Englisch, Physik, Geschichte, Biologie, Chemie, und mehr
- **Adaptives Lernsystem**: Fragen werden altersgerecht angepasst
- **Bitcoin-Wallet**: Belohnungssystem mit Satoshis (Test-Modus)
- **Leaderboard & Achievements**: Gamification für mehr Motivation
- **KI-Integration**: Ollama (tinyllama) für dynamische Fragengenerierung
- **Foxy Chatbot**: Interaktiver Lern-Assistent
- **Bot-System**: Automatisierte Tests (Security, Load, Function)

## 📸 Screenshots

*Coming soon*

## 🛠️ Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| Backend | PHP 8.3 |
| Datenbank | SQLite |
| Webserver | nginx + PHP-FPM (Docker) |
| KI | Ollama mit tinyllama |
| Container | Docker & Docker Compose |

## 🚀 Installation

### Voraussetzungen

- Docker & Docker Compose
- Git

### Quick Start

```bash
# Repository klonen
git clone https://github.com/DEIN-USERNAME/sgit-education.git
cd sgit-education

# Docker Container starten
cd docker
docker-compose up -d

# Fertig! Öffne im Browser:
# http://localhost:8080
```

### Manuelle Installation (ohne Docker)

1. XAMPP oder ähnlichen Stack installieren (PHP 8.x + SQLite)
2. Repository nach `htdocs/Education` klonen
3. Ollama installieren und tinyllama laden:
   ```bash
   ollama pull tinyllama
   ```
4. `config/backup_config.example.json` kopieren zu `config/backup_config.json`
5. Im Browser öffnen: `http://localhost/Education`

## 📁 Projektstruktur

```
sgit-education/
├── AI/
│   └── data/
│       └── questions.db      # 3.263 Fragen
├── adaptive_learning.php     # Haupt-Lernplattform
├── admin_v4.php              # Admin Dashboard
├── bots/                     # Test-Bot-System
│   ├── tests/
│   │   ├── FunctionTestBot.php
│   │   ├── LoadTestBot.php
│   │   └── SecurityBot.php
│   └── bot_summary.php
├── clippy/                   # Foxy Chatbot
├── config/                   # Konfiguration
├── docker/                   # Docker Setup
│   ├── docker-compose.yml
│   ├── Dockerfile
│   └── nginx/
├── includes/                 # PHP-Includes
├── leaderboard.php           # Ranglisten
├── statistics.php            # Statistiken
└── wallet/                   # Bitcoin-Wallet System
```

## 🔗 URLs (nach Start)

| Seite | URL |
|-------|-----|
| Lernplattform | http://localhost:8080/adaptive_learning.php |
| Admin Dashboard | http://localhost:8080/admin_v4.php |
| Leaderboard | http://localhost:8080/leaderboard.php |
| Statistiken | http://localhost:8080/statistics.php |
| Bot Dashboard | http://localhost:8080/bots/bot_summary.php |

**Admin-Passwort:** `sgit2025`

## 🎨 Branding

Die Plattform nutzt das sgiT Corporate Design:

| Element | Farbe |
|---------|-------|
| Primär (Dunkelgrün) | `#1A3503` |
| Akzent (Neon-Grün) | `#43D240` |

## 📊 Lernmodule

| Modul | Icon | Fragen |
|-------|------|--------|
| Mathematik | 🔢 | 286 |
| Englisch | 🇬🇧 | 251 |
| Physik | ⚛️ | 220 |
| Geschichte | 📜 | 205 |
| Lesen | 📖 | 228 |
| Erdkunde | 🌍 | 212 |
| Wissenschaft | 🔬 | 211 |
| Biologie | 🧬 | 197 |
| Chemie | ⚗️ | 200 |
| Musik | 🎵 | 191 |
| Kunst | 🎨 | 209 |
| Computer | 💻 | 206 |
| Bitcoin | ₿ | 189 |
| Programmieren | 👨‍💻 | 190 |
| Finanzen | 💰 | 185 |
| Verkehr | 🚗 | 121 |

**Gesamt: 3.263 Fragen**

## 🤝 Contributing

Beiträge sind willkommen! Bitte beachte:

1. Fork das Repository
2. Erstelle einen Feature-Branch (`git checkout -b feature/AmazingFeature`)
3. Committe deine Änderungen (`git commit -m 'Add AmazingFeature'`)
4. Push zum Branch (`git push origin feature/AmazingFeature`)
5. Öffne einen Pull Request

## 📝 Lizenz

Dieses Projekt ist unter der **GNU General Public License v3.0** lizenziert.
Siehe [LICENSE](LICENSE) für Details.

## 👨‍💻 Autor

**Steven Günther** - [sgiT Solution Engineering & IT Services](https://sgit.space)

## 🙏 Danksagungen

- [Ollama](https://ollama.ai/) für die lokale KI-Integration
- [SQLite](https://www.sqlite.org/) für die leichtgewichtige Datenbank
- Alle Open-Source-Projekte, die diese Plattform möglich machen

---

Made with ❤️ for education
