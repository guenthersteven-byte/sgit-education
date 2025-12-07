# 🧩 Modul "Logik & Rätsel" - Implementierung

**Datum:** 07. Dezember 2025  
**Version:** 3.13.0  
**Typ:** Interaktives Modul (wie Zeichnen)  
**Status:** ✅ MVP FERTIG

---

## 📋 Übersicht

Interaktives Modul für logisches Denken mit 3 Rätseltypen, altersgerecht angepasst.

| Info | Wert |
|------|------|
| Modul-Nr | 20 von 21 |
| Ordner | `/logik/` |
| Dateien | 4 PHP-Dateien |
| Rätseltypen | 3 (Muster, Ausreißer, Zahlenreihen) |

---

## 🎮 Implementierte Rätseltypen

### 1. 🎨 Muster fortsetzen (`muster.php`)
- **Alter:** 5-21 Jahre
- **Sats:** 5-20
- **Beispiel:** 🔴🔵🔴🔵🔴? → 🔵

**Altersgruppen:**
- 5-7: Einfache Farbmuster (AB-AB)
- 8-12: Komplexere Muster (Richtungen, Mondphasen)
- 13+: Schwierige Muster (AAB-AAB, verschachtelt)

### 2. 🔍 Was gehört nicht dazu? (`ausreisser.php`)
- **Alter:** 5-12 Jahre
- **Sats:** 5-10
- **Beispiel:** 🍎🍐🍊🚗 → 🚗 (keine Frucht)

**Features:**
- 2x2 Grid-Darstellung
- Kategorie wird nach Lösung angezeigt
- Animiertes Feedback

### 3. 🔢 Zahlenreihen (`zahlenreihe.php`)
- **Alter:** 8-21 Jahre
- **Sats:** 8-35
- **Beispiel:** 2, 4, 6, 8, ? → 10

**Schwierigkeitsgrade:**
- 8-10: +1, +2, +5, +10 Sequenzen
- 11-14: ×2, Quadratzahlen, Fibonacci
- 15+: Primzahlen, Kubikzahlen, Tribonacci

---

## 📁 Dateistruktur

```
/logik/
├── index.php         # Übersicht mit Rätsel-Auswahl (altersbasiert)
├── muster.php        # Muster fortsetzen
├── ausreisser.php    # Was gehört nicht dazu?
├── zahlenreihe.php   # Zahlenreihen
└── data/             # (Für spätere JSON-Daten)
```

---

## 🎨 Design

- Corporate Branding: #1A3503 / #43D240
- Responsive Grid-Layout
- Touch-freundlich
- Animiertes Feedback (correct/wrong)
- Sofortige Neuladung für neues Rätsel

---

## 🔗 Integration

### adaptive_learning.php
```html
<div class="module-card" onclick="window.location.href='/logik/'" 
     style="border: 2px dashed var(--accent);">
    <div class="module-icon">🧩</div>
    <div>Logik & Rätsel <span>NEU!</span></div>
</div>
```

---

## ⏳ Spätere Erweiterungen (Phase 2)

| Rätseltyp | Beschreibung | Aufwand |
|-----------|--------------|---------|
| 📊 Sudoku | 4x4 und 9x9 | ~3h |
| 🗼 Türme von Hanoi | Interaktive Animation | ~2h |
| 🔤 Wortsuche | Buchstaben sortieren | ~2h |
| 🧠 Einstein-Rätsel | Logik-Gitter | ~4h |

---

## 🧪 Test-URLs

```
http://localhost:8080/logik/           # Übersicht
http://localhost:8080/logik/muster.php        # Muster
http://localhost:8080/logik/ausreisser.php    # Ausreißer
http://localhost:8080/logik/zahlenreihe.php   # Zahlenreihen
```

---

*Dokumentation erstellt: 07.12.2025, 12:30 Uhr*
