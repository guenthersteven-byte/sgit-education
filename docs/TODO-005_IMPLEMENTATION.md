# TODO-005: CSV Drag & Drop Import - Implementierung

**Datum:** 09.12.2025  
**Status:** 🚧 In Arbeit  
**Geschätzter Aufwand:** 3-4h

---

## 📊 Analyse

### Aktueller Stand (batch_import.php v3.0)
| Feature | Status |
|---------|--------|
| Drag & Drop Zone | ✅ Vorhanden (einzelne Datei) |
| Modul-Auswahl | ✅ Manuell (Dropdown) |
| Vorschau | ✅ Vorhanden |
| Generierte CSVs | ✅ Grid mit Klick-Import |
| Hash-Duplikat-Check | ✅ Via CSVQuestionImporter |
| Multi-File Upload | ❌ Fehlt |
| Auto-Modul-Erkennung | ❌ Fehlt |
| Live-Fortschritt | ❌ Fehlt |

### Anforderungen laut Status-Report
1. ✅ Drag & Drop Zone für CSV-Dateien
2. 🔧 **Automatische Modul-Erkennung** aus Dateinamen
3. 🔧 **Multi-File Upload** unterstützen
4. 🔧 **Fortschrittsanzeige** pro Datei
5. ✅ Hash-Duplikat-Check beibehalten
6. ❓ "Verfügbare CSV-Dateien (15) Grid entfernen" - Klärungsbedarf

---

## 🎯 Implementierungsplan

### Phase 1: Multi-File Upload (30 min)
- [ ] `input[type="file"]` auf `multiple` setzen
- [ ] JavaScript für mehrere Dateien anpassen
- [ ] Dateiliste anzeigen vor Upload

### Phase 2: Auto-Modul-Erkennung (45 min)
- [ ] Regex-Pattern für Dateinamen:
  ```
  mathematik_*.csv     → mathematik
  englisch_age5-8_*.csv → englisch
  physik*.csv          → physik
  ```
- [ ] Fallback: Manuelles Dropdown wenn nicht erkannt
- [ ] Visuelle Anzeige welches Modul erkannt wurde

### Phase 3: AJAX-Import mit Fortschritt (1.5h)
- [ ] API-Endpoint für Einzeldatei-Import
- [ ] JavaScript-Queue für mehrere Dateien
- [ ] Fortschrittsbalken pro Datei
- [ ] Gesamtfortschritt
- [ ] Fehlerbehandlung pro Datei

### Phase 4: UI-Verbesserungen (30 min)
- [ ] Bessere Drag & Drop Visualisierung
- [ ] Dateivalidierung vor Upload (.csv only)
- [ ] Ergebnis-Zusammenfassung nach Import
- [ ] "Generated Files" Tab optional behalten

---

## 🔧 Technische Details

### Modul-Erkennung Logik
```php
function detectModuleFromFilename($filename) {
    $modules = ['mathematik', 'englisch', 'lesen', 'physik', ...];
    $filename = strtolower($filename);
    
    foreach ($modules as $module) {
        if (strpos($filename, $module) === 0) {
            return $module;
        }
    }
    return null; // Nicht erkannt
}
```

### API-Endpoint
```
POST /batch_import.php?api=import_single
- file: CSV-Datei (multipart)
- module: Modul-Name (auto oder manuell)
- dry_run: 0|1

Response:
{
    "success": true,
    "imported": 5,
    "duplicates": 2,
    "errors": 0,
    "total": 7
}
```

### JavaScript-Queue
```javascript
async function importFiles(files) {
    for (const file of files) {
        updateProgress(file.name, 'importing');
        const result = await importSingleFile(file);
        updateProgress(file.name, result.success ? 'done' : 'error');
    }
}
```

---

## 📁 Betroffene Dateien

| Datei | Änderung |
|-------|----------|
| `/batch_import.php` | Hauptdatei - Multi-File, AJAX API |
| `/includes/CSVQuestionImporter.php` | Keine Änderung nötig |

---

## ✅ Akzeptanzkriterien

1. [ ] Mehrere CSV-Dateien per Drag & Drop hinzufügen
2. [ ] Modul wird automatisch aus Dateiname erkannt
3. [ ] Fortschrittsanzeige während Import
4. [ ] Zusammenfassung nach Abschluss
5. [ ] Fehlerhafte Dateien stoppen nicht den gesamten Import
6. [ ] Bestehende Funktionalität (Einzelupload) bleibt erhalten

---

*Erstellt: 09.12.2025*
