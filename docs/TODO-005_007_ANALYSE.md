# TODO-005 & TODO-007: Generator & Import Modernisierung

**Datum:** 08.12.2025  
**Version:** 1.0  
**Status:** 📋 Analyse

---

## 📊 IST-Zustand

### Aktuelle Seiten

| Seite | Funktion | CI | Navigation |
|-------|----------|-----|------------|
| `windows_ai_generator.php` | AI → direkt DB | Modern | ❌ Keine Tabs |
| `questions/generate_module_csv.php` | AI → CSV | Anders | ❌ Keine Tabs |
| `batch_import.php` | CSV → DB | Anders | ❌ Keine Tabs |

### Probleme
1. **Kein einheitliches CI** - Jede Seite sieht anders aus
2. **Keine Tab-Navigation** - Kein schnelles Wechseln zwischen Funktionen
3. **Kein Drag & Drop** - CSV-Import ist umständlich
4. **Kein Auto-Generator** - Muss manuell Modul für Modul starten
5. **Keine Zeitsteuerung** - Kann nicht sagen "generiere 2h lang"

---

## 🎯 SOLL-Zustand

### TODO-005: Drag & Drop CSV Import
**Datei:** `batch_import.php` erweitern

**Features:**
- [ ] Drag & Drop Zone für CSV-Dateien
- [ ] Auto-Modul-Erkennung aus Dateiname (`mathematik_*.csv` → Mathematik)
- [ ] Multi-File Upload (mehrere CSVs gleichzeitig)
- [ ] Live-Fortschritt pro Datei
- [ ] Validierung vor Import (Vorschau)
- [ ] Hash-Duplikat-Check beibehalten

**Aufwand:** ~3-4h

---

### TODO-007: Auto-Generator mit Zeitsteuerung (NEU)
**Datei:** `questions/generate_module_csv.php` erweitern ODER neue Seite

**Features:**
- [ ] **Ein-Klick-Start:** Alle Module auf einmal starten
- [ ] **10 Fragen pro Modul** (konfigurierbar)
- [ ] **Zeitlimits:** 
  - 1 Stunde
  - 2 Stunden  
  - 3 Stunden
  - 4 Stunden
  - 12 Stunden
  - 24 Stunden
- [ ] **Auto-Rotation:** Modul für Modul durchgehen
- [ ] **Progress-Dashboard:** Welches Modul gerade läuft
- [ ] **Pause/Resume:** Unterbrechbar
- [ ] **Auto-Import:** CSV direkt in DB (optional)

**Logik:**
```
Start → Timer läuft
  → Modul 1: 10 Fragen generieren (~2-5 Min)
  → Modul 2: 10 Fragen generieren (~2-5 Min)
  → ...
  → Modul 18: 10 Fragen generieren
  → Wenn Zeit übrig: Von vorne anfangen
  → Timer abgelaufen: Stopp
```

**Aufwand:** ~4-6h

---

### TODO-008: CI & Navigation Vereinheitlichung (NEU)
**Betroffene Dateien:**
- `windows_ai_generator.php`
- `questions/generate_module_csv.php`  
- `batch_import.php`

**Features:**
- [ ] **Einheitliches Header-Design** (wie admin_v4.php)
- [ ] **Tab-Navigation** zwischen allen Generatoren:
  ```
  [🤖 AI Generator] [📝 CSV Generator] [📥 CSV Import] [🗄️ DB Manager]
  ```
- [ ] **Konsistente Farben:** #1A3503 / #43D240
- [ ] **Gleiche Buttons, Cards, Tabellen**
- [ ] **Breadcrumbs:** Admin → AI Generator → ...

**Aufwand:** ~2-3h

---

## 🏗️ Umsetzungsplan

### Phase 1: CI & Navigation (Basis)
1. Gemeinsamen Header/Navigation als Include erstellen
2. Alle 3 Seiten umbauen auf einheitliches Design
3. Tab-Navigation implementieren

### Phase 2: Drag & Drop (TODO-005)
1. Drop-Zone HTML/CSS
2. JavaScript für File-Handling
3. AJAX-Upload mit Progress
4. Auto-Modul-Erkennung
5. Live-Import-Feedback

### Phase 3: Auto-Generator (TODO-007)
1. UI für Zeitauswahl
2. Backend-Logic für Timer
3. Modul-Rotation
4. Progress-Tracking
5. Pause/Resume

---

## 📁 Dateien-Übersicht

### Bestehend
```
/windows_ai_generator.php      - AI → DB (direkt)
/questions/generate_module_csv.php - AI → CSV
/batch_import.php              - CSV → DB
/includes/CSVQuestionImporter.php - Import-Klasse
```

### Neu/Anpassen
```
/includes/generator_header.php - Gemeinsamer Header (NEU)
/includes/generator_nav.php    - Tab-Navigation (NEU)
/css/generator.css             - Gemeinsames CSS (NEU)
```

---

## ⏱️ Geschätzter Gesamtaufwand

| TODO | Aufwand |
|------|---------|
| TODO-005 (Drag & Drop) | 3-4h |
| TODO-007 (Auto-Generator) | 4-6h |
| TODO-008 (CI/Navigation) | 2-3h |
| **Gesamt** | **9-13h** |

---

## 🚀 Empfehlung

**Reihenfolge:**
1. **TODO-008 zuerst** (CI/Navigation) - Schafft die Basis
2. **TODO-005** (Drag & Drop) - Sofort nützlich
3. **TODO-007** (Auto-Generator) - Komplexer, mehr Nutzen langfristig

**Heute machbar:** TODO-008 + TODO-005 Start

---

*Analyse erstellt: 08.12.2025, 23:45*
