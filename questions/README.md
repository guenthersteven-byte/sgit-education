# sgiT Education - CSV Question Generator

## Übersicht

**Version:** 1.0  
**Datum:** 08. Dezember 2025  
**Pfad:** `/questions/generate_module_csv.php`

---

## 🎯 Zweck

Der CSV Generator ermöglicht die strukturierte Erstellung von Quiz-Fragen per Modul:

1. **AI-gestützte Generierung** via Ollama (tinyllama)
2. **CSV-Export** zur Qualitätskontrolle
3. **Duplikat-Erkennung** via MD5-Hash
4. **Altersgruppen-Segmentierung** (5 Stufen)

---

## 📂 Verzeichnisstruktur

```
C:\xampp\htdocs\Education\questions\
├── generate_module_csv.php    # Haupt-Generator
└── generated\                 # Output-Verzeichnis
    ├── mathematik_age5-8_20251208_100000.csv
    ├── mathematik_age8-11_20251208_100100.csv
    └── ...
```

---

## 🔄 Workflow

### Schritt 1: Generator aufrufen
```
URL: http://localhost:8080/questions/generate_module_csv.php
```

### Schritt 2: Modul auswählen
- Klick auf eines der 18 Quiz-Module
- Generator startet automatisch

### Schritt 3: AI generiert Fragen
Pro Modul werden 5 CSV-Dateien erstellt (eine pro Altersgruppe):

| Altersgruppe | Schwierigkeit | Fragen |
|--------------|---------------|--------|
| 5-8 Jahre | 1 (sehr leicht) | 5 |
| 8-11 Jahre | 2 (leicht) | 5 |
| 11-14 Jahre | 3 (mittel) | 5 |
| 14-18 Jahre | 4 (schwer) | 5 |
| 18+ Jahre | 5 (sehr schwer) | 5 |

**Gesamt pro Modul:** 25 Fragen

### Schritt 4: CSV prüfen
- Dateien in `/questions/generated/` öffnen
- Fragen auf Qualität prüfen
- Ggf. manuell korrigieren
- `DUPLICATE`-markierte Fragen entfernen oder ändern

### Schritt 5: Import
```
URL: http://localhost:8080/batch_import.php
```

---

## 📋 CSV-Format

| Spalte | Beschreibung |
|--------|--------------|
| question | Die Frage |
| correct_answer | Richtige Antwort |
| wrong_answer_1 | Falsche Antwort 1 |
| wrong_answer_2 | Falsche Antwort 2 |
| wrong_answer_3 | Falsche Antwort 3 |
| explanation | Erklärung (max. 100 Zeichen) |
| difficulty | Schwierigkeit (1-5) |
| age_min | Mindestalter |
| age_max | Maximalalter |
| hash | MD5-Hash für Duplikat-Check |
| status | NEW oder DUPLICATE |

---

## 🔐 Duplikat-Erkennung

Der Generator prüft jede Frage gegen existierende Hashes in der Datenbank:

```php
$hash = md5(
    strtolower(trim($question)) . '|' . 
    strtolower(trim($antwort_a)) . '|' . 
    strtolower(trim($antwort_b)) . '|' . 
    strtolower(trim($antwort_c)) . '|' . 
    strtolower(trim($antwort_d))
);
```

- **NEW:** Frage ist neu, kann importiert werden
- **DUPLICATE:** Frage existiert bereits, wird beim Import übersprungen

---

## 📊 Verfügbare Module

| Modul | Themen |
|-------|--------|
| 🔢 Mathematik | Grundrechenarten, Geometrie, Algebra, Brüche |
| 🇬🇧 Englisch | Vokabeln, Grammatik, Zeiten |
| 📖 Lesen | Buchstaben, Silben, Wortarten |
| ⚛️ Physik | Mechanik, Optik, Elektrizität |
| 🌍 Erdkunde | Kontinente, Länder, Hauptstädte |
| 🔬 Wissenschaft | Experimente, Planeten, Naturgesetze |
| 📜 Geschichte | Antike, Mittelalter, Neuzeit |
| 💻 Computer | Hardware, Software, Internet |
| ⚗️ Chemie | Elemente, Reaktionen, Atome |
| 🧬 Biologie | Tiere, Pflanzen, Körper |
| 🎵 Musik | Noten, Instrumente, Komponisten |
| 👨‍💻 Programmieren | Variablen, Schleifen, Funktionen |
| ₿ Bitcoin | Satoshi, Blockchain, Mining |
| 💰 Finanzen | Geld, Sparen, Steuern |
| 🎨 Kunst | Farben, Techniken, Künstler |
| 🚗 Verkehr | Verkehrszeichen, Regeln, Sicherheit |
| 🏃 Sport | Sportarten, Regeln, Olympia |
| 🤯 Unnützes Wissen | Fun Facts, Kurioses, Rekorde |

---

## ⚙️ Technische Details

### Voraussetzungen
- Docker Container laufen (`sgit_php`, `sgit_ollama`)
- Ollama mit `tinyllama` Modell

### Konfiguration
```php
// Docker-Erkennung
$isDocker = (strpos($_SERVER['DOCUMENT_ROOT'], '/var/www/html') !== false);
$ollamaUrl = $isDocker ? 'http://ollama:11434' : 'http://localhost:11434';

// Timeouts
curl_setopt($ch, CURLOPT_TIMEOUT, 120);  // 2 Minuten pro Request
set_time_limit(600);                      // 10 Minuten Gesamtlaufzeit
```

---

## 🐛 Fehlerbehebung

| Problem | Lösung |
|---------|--------|
| "Keine Antwort von Ollama" | Docker-Container prüfen: `docker ps` |
| "Kein JSON gefunden" | AI-Modell liefert ungültiges Format, erneut versuchen |
| Zu wenige neue Fragen | Themengebiete im Prompt erweitern |
| Encoding-Fehler | Umlaute als ae/oe/ue verwenden |

---

## 📝 Changelog

| Version | Datum | Änderungen |
|---------|-------|------------|
| 1.0 | 08.12.2025 | Initial Release |

---

*Dokumentation erstellt von Claude AI für sgiT Education Platform*
