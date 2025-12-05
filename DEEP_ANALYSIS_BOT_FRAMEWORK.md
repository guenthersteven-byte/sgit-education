# sgiT Education - Deep Analysis Bot Framework

**Erstellt:** 01.12.2025 | **Autor:** Claude Opus 4.5 | **Version:** 1.0

---

## Executive Summary

| Bot | Priorität | Aufwand | Status |
|-----|-----------|---------|--------|
| **Function Test Bot** | 🔴 HOCH | ~2-3h | TODO |
| **Security Bot** | 🔴 HOCH | ~4-5h | TODO |
| **Load Test Bot** | 🟡 MITTEL | ~2-3h | TODO |

---

## Bestehende Architektur

```
bots/
├── bot_logger.php          ✅ Zentrales Logging
├── bot_runner.php          ✅ CLI/Web Runner  
├── bot_summary.php         ✅ Dashboard
├── tests/
│   ├── AIGeneratorBot.php  ✅ LÄUFT
│   ├── FunctionTestBot.php 🔜 TODO
│   ├── SecurityBot.php     🔜 TODO
│   └── LoadTestBot.php     🔜 TODO
└── logs/
    └── bot_results.db      SQLite
```

---

## Design Patterns (aus AIGeneratorBot)

1. **Konfiguration:** `array_merge($default, $config)`
2. **Stop-Signal:** `shouldStop()` mit Datei-Check
3. **Logger:** `BotLogger::CAT_*` Kategorien
4. **Dual-Mode:** Web + CLI Support

---

## Function Test Bot

**Tests pro Modul (x15):**
- HTTP-Status (200 OK?)
- DOM-Struktur (Form, Question, Options, Submit)
- Form-Submit (Antwort verarbeitet?)
- Session (Cookie aktiv?)
- Score (Punkte korrekt?)
- Navigation (Links funktional?)

**Gesamt: ~105 Tests**

---

## Security Bot

**Test-Kategorien:**

| Test | Risiko | Payloads |
|------|--------|----------|
| SQL Injection | 🔴 KRITISCH | `' OR '1'='1` |
| XSS | 🔴 KRITISCH | `<script>alert(1)</script>` |
| Path Traversal | 🔴 KRITISCH | `../config.php` |
| CSRF | 🟡 MITTEL | Token-Tests |
| Session | 🟡 MITTEL | Entropy-Check |

---

## Load Test Bot

**Szenarien:**

| Szenario | User | Erwartung |
|----------|------|-----------|
| Baseline | 5 | < 200ms |
| Normal | 10 | < 500ms |
| Stress | 20 | < 1s |
| Breaking | 50 | Limits finden |

---

## Implementierungsreihenfolge

```
1. FunctionTestBot → Findet kaputte Features
       ↓
2. SecurityBot     → Findet Sicherheitslücken
       ↓  
3. LoadTestBot     → Findet Performance-Probleme
```

---

## Nächster Schritt

**Function Test Bot implementieren** - siehe separates Dokument
