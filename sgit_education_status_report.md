# sgiT Education Platform - Status Report

**Version:** 3.47.0 | **Datum:** 16. Dezember 2025 | **Module:** 21/21 ✅

---

## 🚀 QUICK START

```
Docker starten: cd C:\xampp\htdocs\Education\docker && docker-compose up -d
Admin:          http://localhost:8080/admin_v4.php (PW: sgit2025)
Plattform:      http://localhost:8080/adaptive_learning.php
Multiplayer:    http://localhost:8080/multiplayer.php
GitHub:         https://github.com/guenthersteven-byte/sgit-education
```

**Technologie:** PHP 8.3, SQLite (WAL), Docker/nginx/PHP-FPM, Ollama (Gemma2:2b)
**Branding:** #1A3503 (Dunkelgrün), #43D240 (Neon-Grün)

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

### Docker Container
| Container | Status |
|-----------|--------|
| sgit-education-nginx | ✅ Running |
| sgit-education-php | ✅ Running |
| sgit-education-ollama | ✅ Running |

### Multiplayer-Spiele (7/7 fertig)
| Spiel | Version |
|-------|---------|
| 🎨 Montagsmaler | v3.34.0 |
| 🎲 MADN | v3.46.0 |
| 🃏 Mau Mau | v3.36.0 |
| ⚫ Dame | v3.37.0 |
| ♟️ Schach PvP | v3.38.0 |
| 🎴 Rommé | v3.39.0 |
| 🎰 Poker | v3.40.0 |

---

## 🔗 QUICK LINKS

| Bereich | URL |
|---------|-----|
| Admin Dashboard | http://localhost:8080/admin_v4.php |
| Lern-Plattform | http://localhost:8080/adaptive_learning.php |
| Multiplayer Hub | http://localhost:8080/multiplayer.php |
| Bot Dashboard | http://localhost:8080/bots/bot_summary.php |
| Bot Scheduler | http://localhost:8080/bots/scheduler/scheduler_ui.php |
| AI Generator | http://localhost:8080/bots/tests/AIGeneratorBot.php |
| Auto-Generator | http://localhost:8080/auto_generator.php |
| Flag Cleanup | http://localhost:8080/admin_cleanup_flags.php |


---

## 🔑 WICHTIGE HINWEISE

### Für neue Chat-Sessions
1. **Diese Datei zuerst lesen** ✅
2. **Archiv bei Bedarf:** `sgit_education_ARCHIVE.md` (alle erledigten Bugs/Sessions/TODOs)

### Technische Constraints
- **SQLite** (NICHT MySQL!) mit WAL-Modus
- **Docker/nginx/PHP-FPM** - Port 8080
- **Ollama** mit Gemma2:2b (Standard)
- Zentrale Version: `/includes/version.php`

### Docker-Befehle
```bash
# Start
cd C:\xampp\htdocs\Education\docker && docker-compose up -d

# Stop
docker-compose down

# Status
docker ps

# Ollama Modell pullen
docker exec sgit-education-ollama ollama pull gemma2:2b
```

### Wichtige Pfade
| Pfad | Beschreibung |
|------|--------------|
| `/includes/version.php` | Zentrale Versionsverwaltung |
| `/AI/config/ollama_model.txt` | AI-Modell Konfiguration |
| `/AI/data/questions.db` | Fragen-Datenbank (4.056) |
| `/wallet/*.db` | Wallet-Datenbanken |
| `/assets/css/multiplayer-theme.css` | Zentrale Multiplayer-Styles |

---

## 🎓 MODULE ÜBERSICHT (21/21)

### Quiz-Module (18) - 4.056 Fragen
Mathematik, Englisch, Lesen, Physik, Erdkunde, Wissenschaft, Geschichte, Computer, Chemie, Musik, Programmieren, Bitcoin, Finanzen, Kunst, Verkehr, Sport, Unnützes Wissen, Biologie

### Interaktive Module (3)
- ✏️ Zeichnen (v2.0 mit Ebenen, Brushes, Vorlagen)
- 🧩 Logik & Rätsel (inkl. Schach-Puzzles, Sudoku)
- 🍳 Kochen

---

## 📚 ARCHIV-VERWEIS

Für historische Informationen (70+ erledigte Bugs, alle Sessions, TODOs) siehe:
**`C:\xampp\htdocs\Education\sgit_education_ARCHIVE.md`**

---

*Status-Report gekürzt am 16.12.2025 - von 1.115 auf ~130 Zeilen*
*Archivierte Items: 70+ Bugs, 20 TODOs, 10 Sessions*
