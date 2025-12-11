# 🤖 sgiT Education - Bot-System Analyse

**Erstellt:** 11. Dezember 2025  
**Version:** 1.1  
**Status:** TODO-009 Bot Auto-Scheduler in Arbeit

---

## 🎯 AKTUELLER FOKUS: TODO-009 Bot Auto-Scheduler

| Info | Details |
|------|---------|
| **Status** | 🟡 In Arbeit |
| **Geschätzt** | ~2-3h |
| **Ziel** | Test-Bots automatisch zeitgesteuert ausführen |

### Geplante Struktur
```
/bots/scheduler/
├── BotScheduler.php       # Hauptlogik (Klasse)
├── scheduler_config.json  # Jobs-Konfiguration
├── scheduler_cron.php     # CLI Entry-Point für Cron
└── scheduler_ui.php       # Web-Interface im Dark Theme
```

---

## 📊 Übersicht: Aktueller Stand

### Vorhandene Bots (5)

| Bot | Datei | Zeilen | Funktion | Status |
|-----|-------|--------|----------|--------|
| 🤖 **AIGeneratorBot** | `tests/AIGeneratorBot.php` | ~800+ | Generiert Fragen via Ollama AI | ✅ Aktiv |
| 🧪 **FunctionTestBot** | `tests/FunctionTestBot.php` | ~1285 | Testet alle 21 Module | ✅ Aktiv |
| 🔒 **SecurityBot** | `tests/SecurityBot.php` | ~1387 | SQL Injection, XSS, Path Traversal | ✅ Aktiv |
| ⚡ **LoadTestBot** | `tests/LoadTestBot.php` | ~722 | Simuliert Multi-User Last | ✅ Aktiv |
| 🔍 **DependencyCheckBot** | `tests/DependencyCheckBot.php` | ~300+ | Findet toten Code | ✅ Aktiv |

### Infrastruktur-Dateien

| Datei | Funktion |
|-------|----------|
| `bot_summary.php` | Web-Dashboard mit Stats, Run-Historie, Suggestions |
| `bot_runner.php` | CLI-Runner für einzelne Bots oder Suite |
| `bot_control.php` | AJAX-API für Start/Stop via Dashboard |
| `bot_logger.php` | Zentrales Logging in SQLite DB |
| `bot_health_check.php` | Health-Checks vor Bot-Start |
| `bot_output_helper.php` | Formatierte Konsolen-Ausgabe |
| `run_dependency_check.php` | Dependency-Bot Starter |

### Log-Dateien

| Datei | Inhalt |
|-------|--------|
| `logs/bot_results.db` | SQLite mit allen Test-Ergebnissen |
| `logs/ai_generator.log` | AI Generator Logs |
| `logs/function_test.log` | Function Test Logs |
| `logs/security.log` | Security Test Logs |
| `logs/load_test.log` | Load Test Logs |
| `logs/dependency.log` | Dependency Check Logs |
| `logs/STOP_*` | Stop-Flags für einzelne Bots |

---

## 🎯 Geplante Verbesserungen

### 1. Bot Auto-Scheduler (Priorität: HOCH)
**Geschätzter Aufwand:** ~2-3h

| Feature | Beschreibung |
|---------|--------------|
| ⏰ **Zeitgesteuerte Ausführung** | Bots zu festgelegten Zeiten starten |
| 🔄 **Intervall-Modus** | Alle X Stunden/Tage wiederholen |
| 📋 **Job-Queue** | Mehrere Bots nacheinander planen |
| 📧 **Benachrichtigungen** | Bei Fehlern E-Mail/Slack Alert |
| 📊 **Reports** | Automatische tägliche/wöchentliche Reports |

**Implementierung:**
```
/bots/
├── scheduler/
│   ├── BotScheduler.php      # Hauptlogik
│   ├── SchedulerConfig.php   # Konfiguration (JSON)
│   ├── scheduler_cron.php    # Cron-Entry-Point
│   └── scheduler_ui.php      # Web-Interface
```


### 2. Dashboard-Verbesserungen (Priorität: MITTEL)
**Geschätzter Aufwand:** ~3-4h

| Feature | Beschreibung |
|---------|--------------|
| 📈 **Trend-Charts** | Erfolgsrate über Zeit visualisieren |
| 🔔 **Live-Status** | WebSocket/SSE für Echtzeit-Updates |
| 🎨 **Dark Theme** | Konsistent mit Generator-Seiten |
| 📱 **Mobile View** | Responsive für Smartphone |
| 🔍 **Filter & Suche** | Runs nach Bot/Datum/Status filtern |

### 3. Neue Bot-Typen (Priorität: NIEDRIG)
**Geschätzter Aufwand:** Je ~4-6h

| Bot | Funktion |
|-----|----------|
| 📝 **ContentQualityBot** | Prüft Fragen auf Rechtschreibung, Konsistenz |
| 🔗 **LinkCheckerBot** | Testet alle internen/externen Links |
| 📊 **AnalyticsBot** | Sammelt User-Statistiken, Lernfortschritt |
| 🧹 **CleanupBot** | Entfernt alte Logs, temporäre Dateien |
| 🔄 **BackupBot** | Automatische DB-Backups |

---

## 🛠️ Technische Details

### Bot-Architektur

```
┌─────────────────────────────────────────────────────────────┐
│                    bot_summary.php                          │
│                    (Web Dashboard)                          │
└─────────────────────┬───────────────────────────────────────┘
                      │
         ┌────────────┴────────────┐
         ▼                         ▼
┌─────────────────┐     ┌─────────────────────┐
│ bot_control.php │     │   bot_runner.php    │
│   (AJAX API)    │     │      (CLI)          │
└────────┬────────┘     └──────────┬──────────┘
         │                         │
         └─────────┬───────────────┘
                   ▼
         ┌─────────────────┐
         │  bot_logger.php │
         │   (SQLite DB)   │
         └────────┬────────┘
                  │
    ┌─────────────┼─────────────┐
    ▼             ▼             ▼
┌───────┐   ┌──────────┐   ┌──────────┐
│ AI Bot│   │Func.Test │   │ Sec.Bot  │ ...
└───────┘   └──────────┘   └──────────┘
```

### Docker-Integration

Alle Bots erkennen automatisch ob sie in Docker laufen:
- **Docker:** `http://nginx/` (internes Netzwerk)
- **Lokal:** `http://localhost:8080/`

### Stop-Mechanismus

Bots prüfen regelmäßig auf Stop-Flag-Dateien:
```php
if (file_exists($this->stopFile)) {
    // Graceful shutdown
    $this->cleanup();
    exit;
}
```

---

## 📋 Empfohlene Reihenfolge

| Priorität | Task | Aufwand | Nutzen |
|-----------|------|---------|--------|
| 1️⃣ | Bot Auto-Scheduler | ~2-3h | Automatisierte QA ohne manuelle Starts |
| 2️⃣ | Dashboard Dark Theme | ~1h | Visuelle Konsistenz |
| 3️⃣ | Trend-Charts | ~2h | Bessere Übersicht über Zeit |
| 4️⃣ | ContentQualityBot | ~4h | Fragen-Qualität automatisch prüfen |
| 5️⃣ | BackupBot | ~2h | Automatische Sicherungen |

---

## 🚀 Nächster Schritt: Bot Auto-Scheduler

### Vorgeschlagene Struktur

```php
// /bots/scheduler/BotScheduler.php
class BotScheduler {
    private $jobs = [];
    
    public function addJob($botType, $schedule, $options = []);
    public function removeJob($jobId);
    public function getNextRun($jobId);
    public function runDue();           // Führt fällige Jobs aus
    public function getStatus();        // Alle Jobs mit Status
}
```

### Schedule-Formate

| Format | Beispiel | Bedeutung |
|--------|----------|-----------|
| `interval` | `"every 6 hours"` | Alle 6 Stunden |
| `daily` | `"daily at 03:00"` | Täglich um 3 Uhr |
| `weekly` | `"weekly on monday"` | Jeden Montag |
| `cron` | `"0 3 * * *"` | Cron-Syntax |

### Konfiguration (JSON)

```json
{
  "jobs": [
    {
      "id": "security-daily",
      "bot": "security",
      "schedule": "daily at 03:00",
      "enabled": true,
      "notify_on_error": true
    },
    {
      "id": "function-weekly",
      "bot": "function_test",
      "schedule": "weekly on sunday",
      "enabled": true
    }
  ],
  "notifications": {
    "email": "admin@sgit.space",
    "slack_webhook": null
  }
}
```

---

**Dokument erstellt für:** sgiT Education Platform v3.22.3  
**Autor:** Claude (AI Assistant)
