# 🧩 Modul "Logik & Rätsel" - Planungsdokument

**Datum:** 07. Dezember 2025  
**Version:** 1.0  
**Typ:** Interaktives Modul (wie Zeichnen)  
**Ziel:** Modul 20 von 21

---

## 📋 Übersicht

Ein interaktives Modul für logisches Denken mit verschiedenen Rätseltypen,
angepasst an das Alter der Kinder (5-21 Jahre).

---

## 🎯 Rätseltypen nach Alter

### 👶 Alter 5-7 (Einfach)
| Typ | Beschreibung | Beispiel |
|-----|--------------|----------|
| **Muster fortsetzen** | Einfache Farbmuster | 🔴🔵🔴🔵🔴? → 🔵 |
| **Was gehört nicht dazu?** | Kategorien erkennen | 🍎🍐🍊🚗 → 🚗 |
| **Zählen & Vergleichen** | Mehr/Weniger | ⭐⭐⭐ vs ⭐⭐ → Links |
| **Einfache Puzzle** | 2x2 Bildpuzzle | Bild zusammensetzen |

### 🧒 Alter 8-12 (Mittel)
| Typ | Beschreibung | Beispiel |
|-----|--------------|----------|
| **Zahlenreihen** | Muster erkennen | 2, 4, 6, 8, ? → 10 |
| **Sudoku Mini** | 4x4 Sudoku | Zahlen 1-4 einsetzen |
| **Logik-Gitter** | Einfache Kombinatorik | Wer hat welches Haustier? |
| **Wort-Rätsel** | Buchstaben sortieren | USHND → HUNDS |

### 🧑 Alter 13-17 (Fortgeschritten)
| Typ | Beschreibung | Beispiel |
|-----|--------------|----------|
| **Sudoku Classic** | 9x9 Sudoku | Standard-Sudoku |
| **Logik-Puzzles** | Einstein-Rätsel light | 3-4 Kategorien |
| **Zahlenrätsel** | Komplexere Muster | Fibonacci erkennen |
| **Tower of Hanoi** | Türme von Hanoi | 3-4 Scheiben |

### 🎓 Alter 18-21 (Experte)
| Typ | Beschreibung | Beispiel |
|-----|--------------|----------|
| **Sudoku Schwer** | 9x9 mit wenig Vorgaben | Experten-Level |
| **Einstein-Rätsel** | 5 Kategorien | Vollständiges Rätsel |
| **Nonogramm** | Bild-Logik | 10x10 Grid |
| **Mastermind** | Code knacken | 4 Farben, 10 Versuche |

---

## 🏗️ Technische Architektur

### Dateistruktur
```
/logik/
├── index.php           # Hauptseite mit Rätsel-Auswahl
├── muster.php          # Muster fortsetzen
├── sudoku.php          # Sudoku (4x4 und 9x9)
├── zahlenreihe.php     # Zahlenreihen
├── ausreisser.php      # Was gehört nicht dazu?
├── wortsuche.php       # Buchstaben sortieren
├── hanoi.php           # Türme von Hanoi
├── api/
│   ├── generate.php    # Rätsel generieren
│   ├── check.php       # Lösung prüfen
│   └── hint.php        # Hinweis geben
├── js/
│   ├── sudoku.js       # Sudoku-Logik
│   ├── hanoi.js        # Hanoi-Animation
│   └── pattern.js      # Muster-Logik
└── data/
    ├── sudoku_easy.json
    ├── sudoku_medium.json
    └── patterns.json
```

### Datenbank-Erweiterung
```sql
CREATE TABLE IF NOT EXISTS logic_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    puzzle_type TEXT NOT NULL,      -- 'sudoku', 'pattern', 'sequence', etc.
    difficulty TEXT NOT NULL,        -- 'easy', 'medium', 'hard', 'expert'
    puzzle_data TEXT,               -- JSON mit Rätsel-Daten
    solved INTEGER DEFAULT 0,
    hints_used INTEGER DEFAULT 0,
    time_seconds INTEGER,
    sats_earned INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Satoshi-Belohnungen
| Rätseltyp | Einfach | Mittel | Schwer | Experte |
|-----------|---------|--------|--------|---------|
| Muster | 5 | 10 | 15 | 20 |
| Sudoku 4x4 | 10 | - | - | - |
| Sudoku 9x9 | - | 25 | 50 | 75 |
| Zahlenreihe | 5 | 15 | 25 | 35 |
| Hanoi | 10 | 20 | 30 | 40 |
| Ausreißer | 5 | 10 | - | - |

**Bonus:** -5 Sats pro genutztem Hinweis

---

## 🚀 MVP-Scope (Phase 1)

Für den MVP implementiere ich:

1. ✅ **index.php** - Übersicht mit altersgerechten Rätseln
2. ✅ **muster.php** - Muster fortsetzen (alle Alter)
3. ✅ **zahlenreihe.php** - Zahlenfolgen (8+)
4. ✅ **ausreisser.php** - Was gehört nicht dazu? (5-12)
5. ✅ **Sats-Integration** - Belohnungen bei Lösung

**Später (Phase 2):**
- ⏳ Sudoku (4x4 und 9x9)
- ⏳ Türme von Hanoi
- ⏳ Wortsuche

---

## 🎨 UI/UX Design

### Farbschema (sgiT Corporate)
- Hintergrund: #1A3503 (Dunkelgrün)
- Akzent: #43D240 (Neon-Grün)
- Richtig: #43D240
- Falsch: #ff4444
- Neutral: #ffffff

### Interaktions-Elemente
- Drag & Drop für Muster
- Klick-Auswahl für Multiple Choice
- Touch-freundlich für Tablets
- Animierte Feedback-Effekte

---

## ⏱️ Zeitschätzung

| Phase | Aufgabe | Zeit |
|-------|---------|------|
| 1 | Grundstruktur + index.php | 30 min |
| 2 | Muster-Rätsel | 45 min |
| 3 | Zahlenreihen | 30 min |
| 4 | Ausreißer | 30 min |
| 5 | Sats-Integration | 15 min |
| 6 | Tests & Bugfixes | 30 min |
| **Gesamt MVP** | | **~3h** |

---

*Dokument erstellt: 07.12.2025*
