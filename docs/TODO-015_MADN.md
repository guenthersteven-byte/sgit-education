# TODO-015: Mensch ärgere dich nicht - ERLEDIGT

**Version:** 3.35.0 | **Datum:** 13. Dezember 2025 | **Commit:** 94ec773

---

## ✅ Was wurde implementiert

### 🎲 Mensch ärgere dich nicht (`/madn.php`)

Das klassische deutsche Brettspiel für 2-4 Spieler.

| Feature | Status |
|---------|--------|
| 🎫 Lobby mit 6-stelligem Code | ✅ |
| 🎲 Würfel-System (1-6) | ✅ |
| 🎮 Spielbrett (40 Felder) | ✅ |
| 🏠 Startbereiche (je 4 Figuren) | ✅ |
| 🎯 Zielbereiche (je 4 Felder) | ✅ |
| 👥 2-4 Spieler | ✅ |
| 🔴🔵🟢🟡 4 Farben | ✅ |
| 💥 Figuren schlagen | ✅ |
| 🔄 Bei 6 nochmal würfeln | ✅ |
| 🏆 Gewinner-Erkennung | ✅ |
| 📊 Live-Scoreboard | ✅ |

**Zugriff:** http://localhost:8080/madn.php

---

## 🎮 Spielregeln

1. **Start:** Alle 4 Figuren im Startbereich
2. **Würfeln:** Klicke auf den Würfel
3. **Figur rausbringen:** Bei 6 kann eine Figur auf Startfeld
4. **Ziehen:** Figur um gewürfelte Augen bewegen
5. **Schlagen:** Landet auf Gegner → Gegner zurück zum Start
6. **6 gewürfelt:** Nochmal würfeln erlaubt
7. **Ziel:** Alle 4 Figuren ins Zielhaus bringen

---

## 📁 Dateien

| Datei | Zeilen | Beschreibung |
|-------|--------|--------------|
| `/madn.php` | 730+ | Frontend + JavaScript |
| `/api/madn.php` | 600+ | REST API Backend |
| `/wallet/madn.db` | - | SQLite Datenbank |

---

## 🔗 API Endpoints

| Endpoint | Methode | Beschreibung |
|----------|---------|--------------|
| `?action=create` | POST | Spiel erstellen |
| `?action=join` | POST | Spiel beitreten |
| `?action=start` | POST | Spiel starten (Host) |
| `?action=status` | GET | Spielstatus abrufen |
| `?action=roll` | POST | Würfeln |
| `?action=move` | POST | Figur bewegen |
| `?action=leave` | POST | Spiel verlassen |

---

## 📊 Session-Zusammenfassung (13.12.2025)

| TODO | Feature | Status |
|------|---------|--------|
| TODO-013 | Schach-Puzzles | ✅ |
| TODO-014 | Montagsmaler | ✅ |
| TODO-015 | Mensch ärgere dich nicht | ✅ |
| - | Multiplayer Spiele-Hub | ✅ |

**Heute implementiert:** 3 Spiele + Hub

---

## 🎲 Noch offen (Multiplayer-Spiele)

| Spiel | Aufwand |
|-------|---------|
| 🃏 Mau Mau | ~6-8h |
| ⚫ Dame | ~6-8h |
| ♟️ Schach (PvP) | ~8-10h |
| 🎴 Rommé | ~10-12h |
| 🎰 Poker | ~12-15h |

---

*Dokumentation erstellt am 13.12.2025*
