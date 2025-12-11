# BUG-038: AI Generator Tab Refactoring

**Erstellt:** 10. Dezember 2025
**Priorität:** MITTEL
**Datei:** `/bots/tests/AIGeneratorBot.php`
**Version:** v1.6 (aktuell)

---

## 📋 Zusammenfassung

Der AI Generator Bot hat derzeit ein **Tab-System mit 3 Tabs**, das überarbeitet werden muss:

1. **"Generator" Tab** → soll zu **"Generatoren"** (Plural) umbenannt werden
2. **Scheduler fehlt** - war mal integriert, ist aber verschwunden
3. **Langsamer Bot** soll entfernt/disabled werden - alles über CSV Generator

---

## 🔍 Aktuelle Situation (IST-Zustand)

### Datei: `/bots/tests/AIGeneratorBot.php` (Zeile ~968-972)

```php
<!-- Tabs Navigation -->
<div class="tabs">
    <button class="tab active" onclick="showTab('generator')">🚀 Generator</button>
    <button class="tab" onclick="window.location.href='/questions/generate_module_csv.php'">📝 CSV Generator</button>
    <button class="tab" onclick="showTab('dbmanager')">🗄️ DB-Manager</button>
</div>
```

### Tab 1: "Generator" (Zeile ~975-1050)
- Enthält den **langsamen Dauerlauf-Bot**
- Generiert alle 2 Minuten eine Frage pro Modul
- info-box mit "Was macht dieser Bot?" Erklärung
- Form mit Intervall/Modus-Auswahl
- CLI-Nutzung Anleitung

### Tab 2: "CSV Generator"
- Nur ein **Link** zu `/questions/generate_module_csv.php`
- Keine eigene Funktion in dieser Datei

### Tab 3: "DB-Manager"
- Modul-Statistiken
- Fragen-Verwaltung pro Modul
- Lösch-Funktionen

---

## 🎯 Gewünschte Änderungen (SOLL-Zustand)

### 1. Tab-Umbenennung
```
ALT:  "🚀 Generator"
NEU:  "⚙️ Generatoren"
```

**Begründung:** Der Tab soll alle Generierungs-Arten beinhalten (Scheduler, CSV, etc.)

### 2. Langsamer Bot entfernen/disablen

Die gesamte info-box und der Dauerlauf-Bot-Bereich soll:
- **Option A:** Komplett entfernt werden
- **Option B:** Als "disabled" geflaggt/versteckt werden (Fallback)

**Betroffener Code (Zeile ~975-1050):**
```php
<div class="info-box">
    <h4>ℹ️ Was macht dieser Bot?</h4>
    <p>Dieser Bot generiert <strong>langsam und kontinuierlich</strong>...</p>
    <ul>
        <li>🐢 <strong>Alle 2 Minuten</strong> eine Frage pro Modul</li>
        <li>♻️ Läuft in <strong>Dauerschleife</strong> bis gestoppt</li>
        ...
    </ul>
</div>
```

**Begründung:** Alles soll über den CSV Generator erstellt werden.

### 3. Scheduler wieder integrieren

Der Scheduler war früher vorhanden, ist aber verschwunden.

**Mögliche Integration:**
- Als Sub-Tab oder Bereich im "Generatoren" Tab
- Verweis auf `/auto_generator.php` (TODO-007)
- Zeitgesteuerte Generierung

---

## 📁 Betroffene Dateien

| Datei | Änderung |
|-------|----------|
| `/bots/tests/AIGeneratorBot.php` | Tab-Umbenennung, Bot-Bereich entfernen |
| `/questions/generate_module_csv.php` | Prüfen ob Scheduler-Verweis vorhanden |
| `/auto_generator.php` | Als Scheduler-Alternative prüfen |

---

## 📊 Analyse der aktuellen Generierungs-Optionen

| Tool | Pfad | Funktion | Status |
|------|------|----------|--------|
| **AI Generator Bot** | `/bots/tests/AIGeneratorBot.php` | Langsamer Dauerlauf (2min/Frage) | ⚠️ Soll disabled werden |
| **CSV Generator** | `/questions/generate_module_csv.php` | Direkte CSV-Erstellung | ✅ Haupttool |
| **Auto-Generator** | `/auto_generator.php` | Zeitgesteuert (1h-24h) | ✅ TODO-007 |
| **Batch Import** | `/batch_import.php` | CSV Drag & Drop Import | ✅ TODO-005 |

---

## ✅ Empfohlene Vorgehensweise

### Phase 1: Tab-Refactoring
1. Tab-Name von "Generator" zu "Generatoren" ändern
2. info-box und Dauerlauf-Bot-Bereich auskommentieren (nicht löschen!)
3. Hinweis einfügen: "Bitte CSV Generator oder Auto-Generator nutzen"

### Phase 2: Scheduler-Integration
1. Prüfen ob auto_generator.php die Scheduler-Funktion erfüllt
2. Falls ja: Link im "Generatoren" Tab hinzufügen
3. Falls nein: Scheduler-Logik neu implementieren

### Phase 3: Cleanup
1. Auskommentierten Code nach Testphase entfernen
2. Version hochzählen (v1.6 → v1.7)
3. Dokumentation aktualisieren

---

## 🔗 Verwandte TODOs/Bugs

- **TODO-005:** CSV Drag & Drop Import (✅ Erledigt)
- **TODO-007:** Auto-Generator mit Zeitsteuerung (✅ Erledigt)
- **BUG-037:** Flag-Button wird nicht angezeigt (⏳ Test erforderlich)

---

## 📝 Notizen

- **Scheduler-Verlust:** Unklar wann/warum der Scheduler verschwunden ist
- **Empfehlung:** auto_generator.php als Scheduler-Ersatz evaluieren
- **Priorität:** MITTEL - Funktionalität ist vorhanden, nur UI-Refactoring

---

*Dokumentation erstellt am: 10.12.2025*
*Implementiert am: 10.12.2025*
*Status: ✅ ERLEDIGT*
