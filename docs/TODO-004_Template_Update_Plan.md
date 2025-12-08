# TODO-004: Project Templates Update Plan

**Erstellt:** 08. Dezember 2025
**Autor:** deStevie + Claude
**Ziel:** Erkenntnisse aus sgiT Education in Templates einarbeiten

---

## 🎯 ÜBERBLICK

| Info | Details |
|------|---------|
| **Template-Pfad** | `C:\Users\SG\OneDrive\sgiT\sgiT\project templates\sgit_templates_2025_v1` |
| **Aktuelle Version** | v1.1 (2024-12-01) |
| **Neue Version** | v1.2 |
| **Änderungsgrund** | Status-Report Archiv-Strategie für AI-Chat-Stabilität |

---

## 📋 NEUE ERKENNTNIS: Status-Report Archiv-Strategie

### Problem
- Status-Reports wachsen mit der Zeit auf 1.000+ Zeilen
- Große Kontextfenster belasten AI-Assistenten (Claude, ChatGPT)
- Chat-Abstürze bei komplexen Aufgaben + großem Kontext
- Token-Limits werden schnell erreicht

### Lösung
1. **Aktiver Status-Report:** Max. 300-400 Zeilen
   - Nur OFFENE TODOs und Bugs
   - Quick-Start Section ganz oben
   - Aktuelle Session
   - Kompakte Modul-Übersicht

2. **Archiv-Datei:** `[projekt]_ARCHIVE.md`
   - Geschlossene Bugs (alle!)
   - Abgeschlossene Sessions
   - Versions-Historie
   - Erledigte TODOs

### Trigger für Archivierung
- Status-Report > 500 Zeilen → Archiv erstellen
- Monatlich: Alte Sessions archivieren
- Nach Release: Versions-Historie archivieren

---

## 📝 ZU ÄNDERNDE DATEIEN

### 1. `sgit_status_report_template.md`

**Hinzufügen:**
```markdown
## ⚠️ ARCHIV-STRATEGIE

### Wann archivieren?
- Status-Report > 500 Zeilen
- Nach jedem Major Release
- Monatlich für alte Sessions

### Was archivieren?
- ✅ Geschlossene Bugs
- ✅ Abgeschlossene Sessions  
- ✅ Erledigte TODOs
- ✅ Alte Versions-Historie

### Archiv-Datei
`[projektname]_ARCHIVE.md` im selben Ordner

### Struktur Archiv
1. Versions-Historie
2. Geschlossene Bugs (nach Datum)
3. Abgeschlossene Sessions
4. Erledigte TODOs
```

**Quick-Start Section an den ANFANG verschieben**

---

### 2. `sgit_template_usage_guide.md`

**Neuer Abschnitt:**
```markdown
## 📦 STATUS-REPORT ARCHIVIERUNG

### Warum archivieren?
AI-Assistenten (Claude, ChatGPT) haben Token-Limits. 
Ein 1.000+ Zeilen Status-Report:
- Belastet das Kontextfenster
- Führt zu Chat-Abstürzen
- Verlangsamt Responses
- Lässt weniger Platz für eigentliche Arbeit

### Archivierungs-Workflow
1. Prüfe Zeilenzahl: `wc -l status_report.md`
2. Bei >500 Zeilen → Archiv erstellen
3. Geschlossene Bugs → ARCHIVE.md verschieben
4. Alte Sessions → ARCHIVE.md verschieben
5. Aktiven Report auf ~200-300 Zeilen kürzen

### Best Practice
- Quick-Start Section IMMER ganz oben
- Nur OFFENE TODOs im aktiven Report
- Archiv-Verweis am Ende des Reports
```

---

### 3. `README.md`

**Version-Bump + Changelog:**
```markdown
### v1.2 - 2025-12-08 (Archiv-Strategie)
- NEU: Status-Report Archiv-Strategie
- NEU: Quick-Start Section Priorität
- NEU: VERSION.md für Changelog
- Basiert auf: sgiT Education Platform Erfahrungen (21 Module, 3.400 Fragen)
- Grund: AI-Chat-Stabilität bei großen Projekten
```

---

### 4. `sgit_quick_start_guide.md`

**Hinzufügen unter "Status-Report":**
```markdown
### 📦 Report zu groß?
Bei >500 Zeilen:
1. Erstelle `[projekt]_ARCHIVE.md`
2. Verschiebe geschlossene Bugs
3. Verschiebe alte Sessions
4. Behalte nur Offenes im aktiven Report
```

---

### 5. `VERSION.md` (NEU)

**Neue Datei erstellen:**
```markdown
# sgiT Project Templates - Changelog

## v1.2 - 2025-12-08
### Added
- Status-Report Archiv-Strategie
- Quick-Start Section Priorität  
- VERSION.md für Changelog-Tracking

### Changed
- sgit_status_report_template.md: Archiv-Section hinzugefügt
- sgit_template_usage_guide.md: Archivierungs-Workflow
- README.md: Version-Bump

### Reason
Erkenntnisse aus sgiT Education Platform:
- Status-Report wuchs auf 1.622 Zeilen
- Verursachte AI-Chat-Abstürze
- Lösung: Archiv-Strategie reduzierte auf 215 Zeilen

## v1.1 - 2024-12-01
### Added
- Bot-Framework Template
- Polyglot Programming Guide

## v1.0 - 2024-12-01
### Initial Release
- Master Template
- Quick Start Guide
- Status Report Template
- Usage Guide
```

---

## ✅ CHECKLISTE

- [ ] `sgit_status_report_template.md` - Archiv-Section hinzufügen
- [ ] `sgit_status_report_template.md` - Quick-Start nach oben
- [ ] `sgit_template_usage_guide.md` - Archivierungs-Workflow
- [ ] `sgit_quick_start_guide.md` - Archiv-Tipp
- [ ] `README.md` - Version 1.1 → 1.2, Changelog
- [ ] `VERSION.md` - Neue Datei erstellen
- [ ] Alle Dateien: Datum aktualisieren auf 2025-12-08

---

## 📊 ERWARTETES ERGEBNIS

| Metrik | Vorher | Nachher |
|--------|--------|---------|
| Templates | 10 Dateien | 11 Dateien (+VERSION.md) |
| Version | 1.1 | 1.2 |
| Archiv-Dokumentation | ❌ | ✅ |
| AI-Stabilität Hinweise | ❌ | ✅ |

---

*Dokument erstellt für TODO-004*
