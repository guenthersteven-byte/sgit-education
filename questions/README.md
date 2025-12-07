# sgiT Education - CSV Question Generator

## Übersicht

**Version:** 2.8  
**Datum:** 07. Dezember 2025  
**Pfad:** `/questions/generate_module_csv.php`

---

## 🎯 Zweck

Der CSV Generator ermöglicht die strukturierte Erstellung von Quiz-Fragen per Modul:

1. **AI-gestützte Generierung** via Ollama mit **Gemma2:2b** (empfohlen)
2. **CSV-Export** zur Qualitätskontrolle
3. **Duplikat-Erkennung** via MD5-Hash
4. **Altersgruppen-Segmentierung** (5 Stufen)
5. **Few-Shot Learning** für bessere Fragen-Qualität

---

## 🤖 AI-Modell Empfehlung

| Modell | Größe | CPU-Zeit | Qualität | Status |
|--------|-------|----------|----------|--------|
| **gemma2:2b** | 1.6 GB | ~60-100s | ⭐⭐⭐⭐⭐ | ✅ **EMPFOHLEN** |
| llama3.2:1b | 1.3 GB | ~10s | ⭐⭐⭐ | ⚠️ Akzeptabel |
| tinyllama | 637 MB | ~5s | ⭐⭐ | ❌ Zu einfach |
| mistral:7b | 4.4 GB | 10-30 Min | ⭐⭐⭐⭐ | ❌ Nur mit GPU! |

### Modell installieren
```bash
docker exec sgit_ollama ollama pull gemma2:2b
```

---

## 📂 Verzeichnisstruktur

```
/questions/
├── generate_module_csv.php    # Haupt-Generator v2.8
├── README.md                  # Diese Datei
└── generated/                 # Output-Verzeichnis
    ├── mathematik_age5-8_20251207_*.csv
    ├── mathematik_age8-11_20251207_*.csv
    └── ...
```

---

## 🔄 Workflow

### Schritt 1: Generator aufrufen
```
URL: http://localhost:8080/questions/generate_module_csv.php
```

### Schritt 2: Modul & Modell wählen
- Modell auf **Gemma2 2B** setzen (Default)
- Klick auf eines der 18 Quiz-Module
- Generator startet automatisch

### Schritt 3: AI generiert Fragen
Pro Modul werden 5 CSV-Dateien erstellt (eine pro Altersgruppe):

| Altersgruppe | Schwierigkeit | Fragen | max_alter |
|--------------|---------------|--------|-----------|
| 5-8 Jahre | 1 (sehr leicht) | 5 | 8 |
| 8-11 Jahre | 2 (leicht) | 5 | 11 |
| 11-14 Jahre | 3 (mittel) | 5 | 14 |
| 14-18 Jahre | 4 (schwer) | 5 | 18 |
| 18+ Jahre | 5 (sehr schwer) | 5 | **99** |

**Gesamt pro Modul:** 25 Fragen (~10 Min mit Gemma2:2b)


### Schritt 4: CSV prüfen
- Klick auf "CSV-Ordner öffnen" → Modal mit Dateiliste
- Windows-Pfad kopieren für Explorer
- Download einzelner CSVs möglich
- Fragen auf Qualität prüfen

### Schritt 5: Import
```
URL: http://localhost:8080/batch_import.php
```

---

## 📋 CSV-Format (v2.7+)

Das Format ist kompatibel mit dem CSVQuestionImporter:

| Spalte | Beschreibung |
|--------|--------------|
| frage | Die Frage |
| antwort_a | Antwort A |
| antwort_b | Antwort B |
| antwort_c | Antwort C |
| antwort_d | Antwort D |
| richtig | A, B, C oder D |
| schwierigkeit | 1-5 |
| min_alter | 5-21 |
| max_alter | 5-99 |
| erklaerung | Kurze Erklärung |
| typ | ai_generated |

**Trennzeichen:** Semikolon (`;`)

---

## 🔐 Duplikat-Erkennung

Duplikate werden vor dem Speichern gefiltert:

```php
$hash = md5(
    strtolower(trim($question)) . '|' . 
    strtolower(trim($antwort_a)) . '|' . 
    strtolower(trim($antwort_b)) . '|' . 
    strtolower(trim($antwort_c)) . '|' . 
    strtolower(trim($antwort_d))
);
```

---

## 📊 Verfügbare Module (18)

| Modul | Icon | Themen |
|-------|------|--------|
| Mathematik | 🔢 | Grundrechenarten, Geometrie, Algebra |
| Englisch | 🇬🇧 | Vokabeln, Grammatik, Zeiten |
| Lesen | 📖 | Buchstaben, Silben, Wortarten |
| Physik | ⚛️ | Mechanik, Optik, Elektrizität |
| Erdkunde | 🌍 | Kontinente, Länder, Hauptstädte |
| Wissenschaft | 🔬 | Experimente, Planeten |
| Geschichte | 📜 | Antike, Mittelalter, Neuzeit |
| Computer | 💻 | Hardware, Software, Internet |
| Chemie | ⚗️ | Elemente, Reaktionen, Atome |
| Biologie | 🧬 | Tiere, Pflanzen, Körper |
| Musik | 🎵 | Noten, Instrumente |
| Programmieren | 👨‍💻 | Variablen, Schleifen |
| Bitcoin | ₿ | Satoshi, Blockchain |
| Finanzen | 💰 | Geld, Sparen, Steuern |
| Kunst | 🎨 | Farben, Techniken |
| Verkehr | 🚗 | Verkehrszeichen, Regeln |
| Sport | 🏃 | Sportarten, Olympia |
| Unnützes Wissen | 🤯 | Fun Facts, Rekorde |

---

## 🐛 Fehlerbehebung

| Problem | Lösung |
|---------|--------|
| "Keine Antwort von Ollama" | `docker ps` prüfen, Ollama neu starten |
| "Netzwerkfehler" | Seite neu laden, erneut versuchen |
| Langsame Generierung | Gemma2:2b statt Mistral nutzen |
| Import-Fehler | CSV-Format prüfen (Semikolon-Trennung) |

---

## 📝 Changelog

| Version | Datum | Änderungen |
|---------|-------|------------|
| 2.8 | 07.12.2025 | Error Handling, Output Buffering |
| 2.7 | 07.12.2025 | Import-kompatibles CSV-Format |
| 2.6 | 07.12.2025 | CSV-Modal mit Dateiliste |
| 2.5 | 07.12.2025 | Few-Shot Learning Prompts |
| 2.0 | 06.12.2025 | Komplettes UX-Redesign |
| 1.0 | 06.12.2025 | Initial Release |

---

*Dokumentation für sgiT Education Platform*
