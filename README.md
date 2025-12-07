# 🎓 sgiT Education Platform

Eine umfassende Lernplattform für Kinder und Jugendliche (5-21 Jahre) mit Bitcoin-Wallet-Integration, Gamification und KI-gestützter Fragengenerierung.

![Version](https://img.shields.io/badge/version-3.15.6-green)
![PHP](https://img.shields.io/badge/PHP-8.3-blue)
![License](https://img.shields.io/badge/license-GPL--3.0-orange)
![Questions](https://img.shields.io/badge/Fragen-3.400+-brightgreen)
![Modules](https://img.shields.io/badge/Module-21-blue)

## 🌟 Features

- **21 Lernmodule**: Mathematik, Englisch, Physik, Geschichte, Biologie, Chemie, Zeichnen, Kochen, Logik und mehr
- **Adaptives Lernsystem**: Fragen werden altersgerecht angepasst (5 Altersgruppen)
- **Bitcoin-Wallet**: Belohnungssystem mit Satoshis (Test-Modus)
- **Leaderboard & Achievements**: Gamification für mehr Motivation
- **KI-Integration**: Ollama mit **Gemma2:2b** für dynamische Fragengenerierung
- **CSV Generator**: AI-gestützte Fragen-Generierung mit Few-Shot Learning
- **Foxy Chatbot**: Interaktiver Lern-Assistent
- **Bot-System**: Automatisierte Tests (Security, Load, Function, AI Generator)

## 🛠️ Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| Backend | PHP 8.3 |
| Datenbank | SQLite (WAL-Modus) |
| Webserver | nginx + PHP-FPM (Docker) |
| KI | **Ollama mit Gemma2:2b** |
| Container | Docker & Docker Compose |

## 🚀 Installation

### Voraussetzungen

- Docker & Docker Compose
- Git
- ~4 GB freier Speicher (für Gemma2:2b Modell)

### Quick Start

```bash
# Repository klonen
git clone https://github.com/guenthersteven-byte/sgit-education.git
cd sgit-education

# Docker Container starten
cd docker
docker-compose up -d

# AI-Modell installieren (WICHTIG!)
docker exec sgit_ollama ollama pull gemma2:2b

# Fertig! Öffne im Browser:
# http://localhost:8080
```


## 🤖 AI-Modell Konfiguration

### Empfohlenes Modell: Gemma2:2b

Nach ausführlichen Tests ist **Gemma2:2b** das beste Modell für diese Plattform:

| Modell | Größe | CPU-Zeit | Qualität | Empfehlung |
|--------|-------|----------|----------|------------|
| **gemma2:2b** | 1.6 GB | ~60-100s | ⭐⭐⭐⭐⭐ | ✅ **EMPFOHLEN** |
| llama3.2:1b | 1.3 GB | ~10s | ⭐⭐⭐ | ⚠️ Akzeptabel |
| tinyllama | 637 MB | ~5s | ⭐⭐ | ❌ Zu einfach |
| mistral:7b | 4.4 GB | 10-30 Min | ⭐⭐⭐⭐ | ❌ Nur mit GPU! |

### Modell installieren

```bash
# Empfohlen
docker exec sgit_ollama ollama pull gemma2:2b

# Alternative (schneller, aber geringere Qualität)
docker exec sgit_ollama ollama pull llama3.2:1b
```

### Wichtige Hinweise

- **Ohne GPU**: Große Modelle (7B+) sind auf CPU nicht praktikabel (10-30 Min pro Anfrage)
- **Mit GPU (CUDA)**: Mistral und größere Modelle werden deutlich schneller
- Der CSV Generator erkennt automatisch verfügbare Modelle

## 📁 Projektstruktur

```
sgit-education/
├── AI/data/questions.db      # 3.400+ Fragen
├── adaptive_learning.php     # Haupt-Lernplattform
├── admin_v4.php              # Admin Dashboard
├── bots/                     # Test-Bot-System
├── clippy/                   # Foxy Chatbot
├── docker/                   # Docker Setup
├── kochen/                   # Kochen-Modul (interaktiv)
├── logik/                    # Logik & Rätsel (interaktiv)
├── questions/                # CSV Generator + generierte Fragen
│   ├── generate_module_csv.php
│   └── generated/            # AI-generierte CSVs
├── zeichnen/                 # Zeichnen-Modul (Fabric.js)
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
| **CSV Generator** | http://localhost:8080/questions/generate_module_csv.php |
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

## 📊 Lernmodule (21)

### Quiz-Module (18)

| Modul | Icon | Fragen |
|-------|------|--------|
| Mathematik | 🔢 | 286 |
| Englisch | 🇬🇧 | 251 |
| Lesen | 📖 | 228 |
| Physik | ⚛️ | 220 |
| Erdkunde | 🌍 | 212 |
| Wissenschaft | 🔬 | 211 |
| Geschichte | 📜 | 205 |
| Computer | 💻 | 206 |
| Chemie | ⚗️ | 200 |
| Biologie | 🧬 | 197 |
| Musik | 🎵 | 191 |
| Programmieren | 👨‍💻 | 190 |
| Bitcoin | ₿ | 189 |
| Finanzen | 💰 | 185 |
| Kunst | 🎨 | 177 |
| Verkehr | 🚗 | 121 |
| Sport | 🏃 | 70 |
| Unnützes Wissen | 🤯 | 68 |

### Interaktive Module (3)

| Modul | Icon | Beschreibung |
|-------|------|--------------|
| Zeichnen | ✏️ | Canvas mit Fabric.js, 20+ Tutorials |
| Logik & Rätsel | 🧩 | Muster, Ausreißer, Zahlenreihen |
| Kochen | 🍳 | Quiz, Zuordnen, Küchenwissen |

**Gesamt: 3.400+ Fragen in 21 Modulen**

## 🤝 Contributing

Beiträge sind willkommen! Bitte beachte:

1. Fork das Repository
2. Erstelle einen Feature-Branch (`git checkout -b feature/AmazingFeature`)
3. Committe deine Änderungen (`git commit -m 'Add AmazingFeature'`)
4. Push zum Branch (`git push origin feature/AmazingFeature`)
5. Öffne einen Pull Request

## 📝 Lizenz

Dieses Projekt ist unter der **GNU General Public License v3.0** lizenziert.

## 📞 Kontakt

**sgiT Solution Engineering & IT Services**  
Website: [sgit.space](https://sgit.space)

---

*Entwickelt mit ❤️ für Bildung und Bitcoin*
