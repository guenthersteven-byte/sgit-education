# BUG-043: AI Generator Bot DB-Manager Verbesserungen

**Version:** 3.23.1 → 3.23.2  
**Datum:** 11. Dezember 2025  
**Priorität:** MITTEL  
**Datei:** `/bots/tests/AIGeneratorBot.php`

---

## 📋 Übersicht der Änderungen

Basierend auf der UI-Analyse wurden folgende Optimierungen identifiziert:

| # | Änderung | Status |
|---|----------|--------|
| 1 | Statistik-Werte aus zentraler Quelle (wie statistics.php) | ✅ Erledigt |
| 2 | Statistik-Dashboard mit Quick-Links hinzufügen | ✅ Erledigt |
| 3 | Löschen → Deaktivieren (Soft-Delete für Hash-Erhaltung) | ✅ Erledigt |

---

## 🔍 Detailanalyse

### 1. Statistik-Werte korrigieren

**Problem:** Die Werte oben (4043 Fragen, 481 KI-generiert, 18 Module) werden lokal berechnet.

**Lösung:** Gleiche Berechnungslogik wie `statistics.php` verwenden:
- `total` = COUNT(*) FROM questions
- `ai` = COUNT(*) WHERE ai_generated = 1
- `csv` = COUNT(*) WHERE source = 'csv_import'
- `with_explanation` = COUNT(*) WHERE explanation IS NOT NULL

---

### 2. Statistik-Dashboard hinzufügen

**Gewünschte Elemente (wie im Screenshot):**

```
┌─────────────────────────────────────────────────────────────┐
│ 📊 Statistik Dashboard            [Admin] [Lernen] [Foxy]  │
├──────────┬──────────┬──────────┬──────────┬──────────┬──────┤
│ 📝       │ 🤖       │ 📄       │ 💡       │ ₿        │      │
│ 4,114    │ 481      │ 3,627    │ 3,617    │ 10,390   │      │
│ Fragen   │ AI-DB    │ AI-CSV   │ Mit Erkl.│ Sats     │      │
│ gesamt   │          │          │          │ verteilt │      │
└──────────┴──────────┴──────────┴──────────┴──────────┴──────┘
```

---

### 3. Soft-Delete statt Löschen

**Problem:** "Löschen" entfernt Fragen permanent → Hash geht verloren → AI generiert dieselbe Frage erneut.

**Lösung:** 
- Bestehende `is_active` Spalte nutzen (bereits für Flag-System implementiert!)
- "Löschen" → "Deaktivieren" umbenennen
- Fragen werden auf `is_active = 0` gesetzt statt gelöscht
- Hash bleibt erhalten, AI-Generator überspringt deaktivierte Fragen

**Button-Änderungen:**
```
ALT:  [🗑️ Löschen]
NEU:  [⏸️ Deaktivieren]  

ALT:  [🗑️ Alle Fragen dieses Moduls löschen]
NEU:  [⏸️ Alle Fragen deaktivieren]

ALT:  [🤖 Nur KI-generierte löschen]  
NEU:  [🤖 Nur KI-generierte deaktivieren]
```

---

## 📊 Technische Implementierung

### Neue/Geänderte Methoden:

```php
// Statt DELETE: UPDATE mit is_active = 0
public static function deactivateSingleQuestion($id) {
    // UPDATE questions SET is_active = 0 WHERE id = :id
}

public static function deactivateModuleQuestions($module, $onlyAI = false) {
    // UPDATE questions SET is_active = 0 WHERE module = :module
}

// Stats sollen auch deaktivierte zählen (für Transparenz)
public static function getModuleStats() {
    // COUNT(*) as total (alle)
    // COUNT(*) WHERE is_active = 1 (aktive)
    // COUNT(*) WHERE is_active = 0 (deaktivierte)
}
```

---

## ✅ Implementierungsplan

1. **Statistik-Dashboard** im DB-Manager Tab hinzufügen
2. **getModuleStats()** erweitern für aktive/deaktivierte Counts
3. **deleteSingleQuestion()** → **deactivateSingleQuestion()** umbenennen
4. **deleteModuleQuestions()** → **deactivateModuleQuestions()** umbenennen
5. **UI-Buttons** Text und Styling anpassen
6. **Version** auf 3.23.2 erhöhen

---

## 📁 Betroffene Dateien

| Datei | Änderung |
|-------|----------|
| `/bots/tests/AIGeneratorBot.php` | Haupt-Änderungen |
| `/includes/version.php` | Version 3.23.1 → 3.23.2 |
| `/sgit_education_status_report.md` | Status-Update |

---

*Dokumentation erstellt am 11.12.2025*
