# TODO-007: Auto-Generator mit Zeitsteuerung - Implementierung

**Datum:** 09.12.2025  
**Status:** 🚧 In Arbeit  
**Geschätzter Aufwand:** 4-6h

---

## 📊 Analyse

### Bestehende Komponenten
| Datei | Funktion | Nutzen für TODO-007 |
|-------|----------|---------------------|
| `windows_ai_generator.php` | AI → direkt DB | Kernlogik wiederverwenden |
| `questions/generate_module_csv.php` | AI → CSV | Alternative Output-Option |
| `includes/OptimizedPrompts.php` | Prompts per Modul | Direkt nutzbar |
| `AI/config/ollama_model.txt` | Modell-Config | Zentral nutzen |

### Anforderungen
1. **Ein-Klick-Start** - Alle 18 Quiz-Module automatisch
2. **Zeitlimits** - 1h, 2h, 3h, 4h, 12h, 24h
3. **Fragen pro Modul** - Konfigurierbar (Standard: 10)
4. **Auto-Rotation** - Modul für Modul durchgehen
5. **Progress-Dashboard** - Live-Status welches Modul läuft
6. **Pause/Resume** - Unterbrechbar
7. **Auto-Import** - Optional direkt in DB

---

## 🎯 Architektur-Entscheidung

**Neue Datei:** `/auto_generator.php`

**Gründe:**
- Bestehende Generatoren bleiben unverändert
- Klare Trennung der Verantwortlichkeiten
- Einfachere Wartung
- Kann beide Backends nutzen (DB oder CSV)

---

## 🏗️ Technische Umsetzung

### Frontend (JavaScript)
```
┌─────────────────────────────────────────────────────────┐
│  AUTO-GENERATOR                              v1.0      │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ⏱️ Zeitlimit:  [1h] [2h] [3h] [4h] [12h] [24h]        │
│  📊 Fragen/Modul: [5] [10] [15] [20]                   │
│  💾 Output:      [○ DB direkt] [● CSV-Dateien]         │
│                                                         │
│  ┌─────────────────────────────────────────────┐       │
│  │  ⏳ Verbleibend: 01:45:32                    │       │
│  │  📈 Fortschritt: ████████░░░░ 67%           │       │
│  │  📝 Generiert:   243 Fragen                 │       │
│  └─────────────────────────────────────────────┘       │
│                                                         │
│  Module-Status:                                         │
│  ✅ Mathematik (15/15)  ✅ Englisch (15/15)            │
│  ✅ Lesen (15/15)       🔄 Physik (8/15) ← Aktuell    │
│  ⏳ Erdkunde (0/15)     ⏳ Wissenschaft (0/15)         │
│  ...                                                    │
│                                                         │
│  [▶️ START]  [⏸️ PAUSE]  [⏹️ STOP]                     │
└─────────────────────────────────────────────────────────┘
```

### Backend (PHP API)
```php
// API-Endpoints
?api=start      - Startet Generator-Session
?api=status     - Aktueller Status
?api=generate   - Generiert nächste Fragen-Batch
?api=pause      - Pausiert Session
?api=resume     - Setzt Session fort
?api=stop       - Beendet Session
```

### Session-State
```php
$_SESSION['auto_gen'] = [
    'active' => true,
    'paused' => false,
    'start_time' => time(),
    'end_time' => time() + 3600, // +1h
    'questions_per_module' => 10,
    'output_mode' => 'db', // oder 'csv'
    'current_module_index' => 5,
    'current_module_progress' => 8,
    'modules_completed' => ['mathematik', 'englisch', ...],
    'total_generated' => 243,
    'errors' => []
];
```

### Ablauf-Logik
```
1. User klickt START
2. Frontend startet Timer + Polling (alle 2s)
3. Backend generiert Fragen batch-weise (5 pro Call)
4. Nach jedem Batch: Status-Update an Frontend
5. Modul fertig → Nächstes Modul
6. Alle Module fertig → Von vorne (wenn Zeit übrig)
7. Zeit abgelaufen ODER User STOP → Ende
```

---

## 📁 Neue Dateien

| Datei | Beschreibung |
|-------|--------------|
| `/auto_generator.php` | Hauptseite mit UI |
| `/includes/AutoGeneratorSession.php` | Session-Management (optional) |

---

## ⏱️ Zeitplan

| Phase | Aufwand | Beschreibung |
|-------|---------|--------------|
| 1. Grundstruktur | 1h | PHP-Seite, API-Endpoints, Session |
| 2. UI | 1h | Timer, Progress, Module-Grid |
| 3. Generator-Integration | 1.5h | Bestehende Logik einbinden |
| 4. Pause/Resume | 0.5h | State-Management |
| 5. Testing | 1h | Alle Szenarien |
| **Gesamt** | **5h** | |

---

## 🔧 Module-Liste (18 Quiz-Module)

```php
$quizModules = [
    'mathematik', 'englisch', 'lesen', 'physik',
    'erdkunde', 'wissenschaft', 'geschichte', 'computer',
    'chemie', 'biologie', 'musik', 'programmieren',
    'bitcoin', 'finanzen', 'kunst', 'verkehr',
    'sport', 'unnuetzes_wissen'
];
```

---

*Analyse erstellt: 09.12.2025*
