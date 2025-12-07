# CSV Question Generator v2.0 - Dokumentation

**Erstellt:** 06.12.2025  
**Autor:** Claude AI für sgiT Education  
**Datei:** `/questions/generate_module_csv.php`  
**Größe:** ~33 KB (919 Zeilen)

---

## 🎯 Überblick

Komplett überarbeitete Version des AI Question Generators mit **deutlich verbesserter UX**:

### Vorher (v1.0) - Probleme:
- ❌ Keine Fortschrittsanzeige während Generierung
- ❌ Unklare X-Markierungen ohne Kontext
- ❌ User wusste nicht was gerade passiert
- ❌ Keine Live-Feedback

### Nachher (v2.0) - Verbesserungen:
- ✅ **Echtzeit-Fortschrittsbalken** (0% - 100%)
- ✅ **Live-Status pro Altersgruppe** (Wartet → Generiert → Fertig)
- ✅ **Spinner-Animation** während AI arbeitet
- ✅ **Detailliertes Log** mit Zeitstempeln
- ✅ **Zusammenfassung** am Ende mit Statistiken

---

## 🏗️ Architektur

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND (JavaScript)                     │
├─────────────────────────────────────────────────────────────┤
│  1. checkStatus()     - Prüft Ollama-Verbindung             │
│  2. selectModule()    - Modul-Auswahl                       │
│  3. startGeneration() - Sequentielle API-Calls              │
│  4. addLog()          - Live-Logging                        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼ AJAX Calls
┌─────────────────────────────────────────────────────────────┐
│                    BACKEND API (PHP)                         │
├─────────────────────────────────────────────────────────────┤
│  ?api=status          - Ollama Status Check                 │
│  ?api=generate_single - Einzelne Altersgruppe generieren    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    OLLAMA AI (Docker)                        │
├─────────────────────────────────────────────────────────────┤
│  Model: tinyllama                                           │
│  URL: http://ollama:11434                                   │
│  Timeout: 120 Sekunden pro Request                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 UI-Komponenten

### 1. Status-Box (oben)

```
┌─────────────────────────────────────────────────────┐
│  🟢 ✅ Ollama bereit - Modell: tinyllama           │
└─────────────────────────────────────────────────────┘
```
- **Grün (online):** Ollama läuft, Modell installiert
- **Gelb (warning):** Ollama läuft, aber kein Modell
- **Rot (offline):** Ollama nicht erreichbar

### 2. Modul-Grid
18 Module zur Auswahl mit Icons und Namen.
Klick → Modul wird selektiert → Progress-Panel erscheint.

### 3. Progress-Panel
```
┌─────────────────────────────────────────────────────┐
│  🎯 🏃 Sport                              3 / 5    │
├─────────────────────────────────────────────────────┤
│  ████████████░░░░░░░░░░░░░░░░░░░░░░  60%           │
├─────────────────────────────────────────────────────┤
│  ✅ Kinder (5-8)        5 Fragen in 8.2s    [5]    │
│  ✅ Grundschule (8-11)  5 Fragen in 7.8s    [4]    │
│  🔄 Mittelstufe (11-14) Generiere Fragen...        │
│  ⏸️ Oberstufe (14-18)   Wartet...                  │
│  ⏸️ Erwachsene (18+)    Wartet...                  │
└─────────────────────────────────────────────────────┘
```

### 4. Ergebnis-Zusammenfassung
```
┌─────────────────────────────────────────────────────┐
│  ✅ Generierung abgeschlossen!                      │
├─────────────────────────────────────────────────────┤
│     25           22            3                    │
│   Fragen       Neue       Duplikate                 │
│  generiert    Fragen                                │
└─────────────────────────────────────────────────────┘
```

### 5. Log-Bereich
```
[20:15:32] Starte Generierung für Sport
[20:15:32] Kinder (5-8): Sende Anfrage an AI...
[20:15:40] Kinder (5-8): ✅ 5 Fragen generiert (5 neu)
[20:15:40] Grundschule (8-11): Sende Anfrage an AI...
...
```

---

## 🔧 API Endpoints

### GET ?api=status
Prüft Ollama-Verbindung und installierte Modelle.

**Response:**
```json
{
  "connected": true,
  "model": true,
  "models": ["tinyllama:latest"]
}
```

### GET ?api=generate_single&module=sport&age_group=1
Generiert Fragen für EINE Altersgruppe.

**Response (Erfolg):**
```json
{
  "success": true,
  "age_group": "Kinder (5-8)",
  "questions_generated": 5,
  "filename": "sport_age5-8_20251206_201540.csv",
  "stats": {
    "new": 4,
    "duplicate": 1,
    "invalid": 0
  }
}
```

**Response (Fehler):**
```json
{
  "success": false,
  "error": "Verbindungsfehler: Connection refused"
}
```

---

## 📁 Generierte CSV-Dateien

**Speicherort:** `/questions/generated/`

**Dateiname-Format:** `{modul}_age{min}-{max}_{datum}_{zeit}.csv`

**Beispiel:** `sport_age5-8_20251206_201540.csv`

**CSV-Spalten:**
| Spalte | Beschreibung |
|--------|--------------|
| question | Die Quiz-Frage |
| correct_answer | Richtige Antwort |
| wrong_answer_1-3 | Falsche Antworten |
| explanation | Erklärung |
| difficulty | 1-5 (Schwierigkeit) |
| age_min/max | Altersbereich |
| hash | MD5-Hash für Duplikat-Check |
| status | NEW oder DUPLICATE |

---

## 🚀 Workflow

1. **Öffnen:** `http://localhost:8080/questions/generate_module_csv.php`
2. **Status prüfen:** Grüner Punkt = bereit
3. **Modul wählen:** Klick auf gewünschtes Modul
4. **Starten:** "🚀 [Modul] - 25 Fragen generieren"
5. **Warten:** ~30-60 Sekunden (5 Altersgruppen × ~10s)
6. **Review:** CSVs in `/questions/generated/` prüfen
7. **Import:** Via `batch_import.php`

---

## 🎨 Corporate Identity

- **Dark Green:** #1A3503 (Hintergrund)
- **Neon Green:** #43D240 (Akzente)
- **Background:** Linear gradient
- **Fonts:** Segoe UI, system-ui

---

## 📝 Changelog

### v2.0 (06.12.2025)
- ✅ Komplett neues UI-Design
- ✅ Echtzeit-Fortschrittsbalken
- ✅ Live-Status pro Altersgruppe
- ✅ AJAX-basierte sequentielle Generierung
- ✅ Detailliertes Logging mit Zeitstempeln
- ✅ Statistik-Zusammenfassung am Ende
- ✅ Spinner während AI-Verarbeitung
- ✅ Verbesserte Fehlerbehandlung

### v1.0 (08.12.2025)
- Initiale Version
- Synchrone Generierung ohne Live-Feedback
