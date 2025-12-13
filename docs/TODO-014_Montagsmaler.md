# TODO-014: Montagsmaler - ERLEDIGT

**Version:** 3.34.0 | **Datum:** 13. Dezember 2025 | **Commit:** e087fd1

---

## ✅ Was wurde implementiert

### 🎨 Montagsmaler (`/montagsmaler.php`)

Ein Multiplayer Zeichen-Ratespiel wie das bekannte TV-Format.

| Feature | Status |
|---------|--------|
| 🎫 Lobby mit 6-stelligem Code | ✅ |
| 📝 6 Wort-Kategorien | ✅ |
| ⏱️ Timer (45/60/90 Sek.) | ✅ |
| 💬 Live-Chat zum Raten | ✅ |
| 🏆 Punkte-System | ✅ |
| 🎨 Canvas mit Echtzeit-Übertragung | ✅ |
| ✏️ Zeichenwerkzeuge | ✅ |
| 🔄 Runden-System (3/5/10) | ✅ |
| 🏅 Ergebnis-Bildschirm | ✅ |

**Zugriff:** http://localhost:8080/montagsmaler.php

---

## 🎮 Spielablauf

1. **Lobby:** Host erstellt Spiel → bekommt 6-stelligen Code
2. **Beitreten:** Andere geben Code ein
3. **Start:** Host startet wenn 2+ Spieler da sind
4. **Spielen:**
   - Ein Spieler zeichnet (sieht das Wort)
   - Andere raten im Chat
   - Timer läuft ab
5. **Punkte:** Schneller raten = mehr Punkte
6. **Nächste Runde:** Nächster Spieler zeichnet
7. **Ende:** Rangliste zeigt Gewinner

---

## 📝 Wort-Kategorien

| Kategorie | Easy | Medium | Hard |
|-----------|------|--------|------|
| 🐾 Tiere | Hund, Katze | Giraffe, Delfin | Chamäleon |
| 🍕 Essen | Pizza, Apfel | Spaghetti, Pommes | Sushi, Lasagne |
| ⚽ Sport | Fußball, Tennis | Skateboard, Golf | Fechten, Surfen |
| 👷 Berufe | Arzt, Koch | Astronaut, Clown | Archäologe |
| 🏠 Objekte | Haus, Auto | Flugzeug, Rakete | Hubschrauber |
| 🎬 Aktionen | Schlafen, Essen | Kochen, Malen | Jonglieren |

---

## 🛠️ Technische Details

### API Endpoints (`/api/montagsmaler.php`)

| Endpoint | Methode | Beschreibung |
|----------|---------|--------------|
| `?action=create` | POST | Neues Spiel erstellen |
| `?action=join` | POST | Spiel beitreten |
| `?action=status` | GET | Spielstatus abrufen |
| `?action=draw` | POST | Zeichnung aktualisieren |
| `?action=guess` | POST | Wort raten |
| `?action=next` | POST | Nächste Runde |
| `?action=words` | GET | Wort-Kategorien |
| `?action=leave` | POST | Spiel verlassen |

### Datenbank (`/wallet/montagsmaler.db`)

- `games` - Spiele mit Code, Status, aktuelles Wort
- `game_players` - Spieler mit Score
- `guesses` - Rateversuche pro Runde

### Polling

- 500ms Intervall für Live-Updates
- Canvas wird als Base64 PNG übertragen
- Guesses werden in Echtzeit aktualisiert

---

## 📁 Dateien

| Datei | Zeilen | Beschreibung |
|-------|--------|--------------|
| `/montagsmaler.php` | 960+ | Frontend + JavaScript |
| `/api/montagsmaler.php` | 560+ | REST API Backend |

---

## 🔗 Quick Links

| Seite | URL |
|-------|-----|
| **Montagsmaler** | http://localhost:8080/montagsmaler.php |
| **Multiplayer Quiz** | http://localhost:8080/multiplayer.php |
| **Admin Dashboard** | http://localhost:8080/admin_v4.php |

---

*Dokumentation erstellt am 13.12.2025*
