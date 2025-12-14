# BUG-052: MADN Spielfeld Redesign

**Status:** ✅ BEHOBEN  
**Version:** 3.45.4 → 3.46.0  
**Datum:** 14. Dezember 2025  
**Aufwand:** ~2h (geplant: 4-6h)

---

## Problem

Das "Mensch ärgere dich nicht"-Spielfeld wurde als 11×11 CSS-Grid gerendert, bei dem alle 121 Zellen sichtbar waren. Die "leeren" Ecken hatten nur transparenten Hintergrund, aber der grüne Board-Container machte das gesamte Brett wie ein **Quadrat** aussehen - statt dem klassischen **Kreuz-Layout**.

### Ist-Zustand (vorher)
```
[ROT][ROT] . . [●][●][●] . . [BLU][BLU]
[ROT][ROT] . . [●][H][●] . . [BLU][BLU]
   .   .  . . [●][H][●] . .   .   .
   ...        (11×11 Grid)        ...
[GEL][GEL] . . [●][●][●] . . [GRN][GRN]
[GEL][GEL] . . [●][●][●] . . [GRN][GRN]
```

Das Problem: Die `.` Bereiche waren transparente Zellen, aber der grüne Container füllte den gesamten Bereich aus.

---

## Lösung

**Ansatz:** Wechsel von CSS-Grid zu **absoluter Positionierung**

### Kernänderungen

1. **CSS-Layout:**
   - Board-Container mit `position: relative`
   - Alle Spielfelder mit `position: absolute`
   - Startbereiche als separate Container in den 4 Ecken
   - Nur tatsächliche Spielfelder werden gerendert

2. **JavaScript-Koordinaten:**
   - `MAIN_PATH`: 40 Felder im Uhrzeigersinn
   - `HOME_COORDS`: 4×4 Zielbahn-Felder
   - `CENTER_COORD`: Mittelfeld
   - `ENTRY_INDICES`: Eingangsfelder pro Farbe

3. **Visuelle Verbesserungen:**
   - 4-farbiges Mittelfeld (conic-gradient)
   - Farbige Entry-Felder markieren Startpunkte
   - Gradient-Hintergründe für Startbereiche
   - Bounce-Animation für wählbare Figuren

---

## Koordinatensystem

### Hauptweg (40 Felder)
```
Index 0-9:   Rot → hoch → oben nach rechts → Blau
Index 10-19: Blau → rechts → runter → Grün  
Index 20-29: Grün → runter → links → Gelb
Index 30-39: Gelb → links → hoch → Rot
```

### Entry-Positionen
| Farbe | Entry-Index | Position |
|-------|-------------|----------|
| Rot   | 0           | Links    |
| Blau  | 10          | Oben     |
| Grün  | 20          | Rechts   |
| Gelb  | 30          | Unten    |

### Spielzustand-Mapping
- `-4 bis -1`: Figur im Startbereich
- `0 bis 39`: Figur auf Hauptweg
- `40 bis 43`: Figur in Zielbahn (Home)

---

## Dateien

| Datei | Änderung |
|-------|----------|
| `/madn.php` | Komplett neu geschrieben (v2.0) |
| `/includes/version.php` | 3.45.4 → 3.46.0 |
| `/api/madn.php` | Keine Änderungen nötig |

---

## Mobile Responsive

| Breakpoint | Board-Größe | Feld-Größe |
|------------|-------------|------------|
| Desktop    | 418px       | 32px       |
| ≤500px     | 320px       | 24px       |
| ≤380px     | 280px       | 20px       |

---

## Testing

### Visuelle Tests
- [x] Kreuzform klar erkennbar
- [x] Startbereiche in allen 4 Ecken
- [x] Zielbahnen farblich hervorgehoben
- [x] Entry-Felder markiert
- [x] Mittelfeld mehrfarbig

### Funktionale Tests
- [x] Figuren starten im Startbereich
- [x] Bei 6 geht Figur aufs Eingangsfeld
- [x] Bewegung im Uhrzeigersinn
- [x] Zielbahn korrekt betreten
- [x] Schlagen funktioniert

---

## Erstellte Dokumentation

- `BUG-052_MADN_Redesign_Analyse.docx` - Vollständige Analyse
- `madn_redesign_styles.css` - CSS-Reference
- `madn_redesign_script.js` - JS-Reference
