# sgiT Education - Mass Question Generator Konzept

**Datum:** 05. Dezember 2025  
**Version:** 1.0  
**Status:** PLANUNG

---

## 🎯 ZIEL

Generierung von **150 Fragen pro Modul** für alle 16 Module = **2.400 neue Fragen**

### Aktuelle Situation
- Datenbank: ~2.396 Fragen
- Ziel nach Import: ~4.800 Fragen (Verdopplung!)

---

## 📊 ALTERSVERTEILUNG PRO MODUL (150 Fragen)

Basierend auf dem ALTERSGUIDE.md:

| Altersgruppe | Difficulty | Anzahl | % |
|--------------|------------|--------|---|
| 5-7 Jahre (Vorschule/1.Kl) | 1 | 25 | 17% |
| 7-9 Jahre (2.-3.Kl) | 2 | 35 | 23% |
| 9-12 Jahre (4.-6.Kl) | 3 | 40 | 27% |
| 12-15 Jahre (7.-9.Kl) | 4 | 30 | 20% |
| 15-21 Jahre (Oberstufe+) | 5 | 20 | 13% |
| **GESAMT** | | **150** | **100%** |

---

## 📋 ALLE 16 MODULE

| Nr | Modul | Icon | Template-CSV | Status |
|----|-------|------|--------------|--------|
| 1 | Mathematik | 🔢 | mathe_addition_subtraktion.csv | ✅ |
| 2 | Lesen | 📖 | lesen_grundlagen.csv | ✅ |
| 3 | Englisch | 🇬🇧 | englisch_grundlagen.csv | ✅ |
| 4 | Wissenschaft | 🔬 | wissenschaft_grundlagen.csv | ✅ |
| 5 | Erdkunde | 🌍 | erdkunde_grundlagen.csv | ✅ |
| 6 | Chemie | ⚗️ | chemie_grundlagen.csv | ✅ |
| 7 | Physik | ⚛️ | physik_grundlagen.csv | ✅ |
| 8 | Kunst | 🎨 | kunst_grundlagen.csv | ✅ |
| 9 | Musik | 🎵 | musik_grundlagen.csv | ✅ |
| 10 | Computer | 💻 | computer_grundlagen.csv | ✅ |
| 11 | Bitcoin | ₿ | bitcoin_grundlagen.csv | ✅ |
| 12 | Geschichte | 📚 | geschichte_grundlagen.csv | ✅ |
| 13 | Biologie | 🧬 | biologie_grundlagen.csv | ✅ |
| 14 | Finanzen | 💰 | finanzen_grundlagen.csv | ✅ |
| 15 | Programmieren | 👨‍💻 | programmieren_grundlagen.csv | ✅ |
| 16 | Verkehr | 🚗 | verkehr_grundlagen.csv | ✅ |
| 17 | **Dinosaurier** | 🦕 | dinosaurier_grundlagen.csv | ⏳ NEU |

---

## 🔧 TECHNISCHER ANSATZ

### 1. Hash-basierte Duplikat-Erkennung
```php
function generateHash($q, $a, $b, $c, $d) {
    return md5(strtolower(trim($q)) . '|' . 
               strtolower(trim($a)) . '|' . 
               strtolower(trim($b)) . '|' . 
               strtolower(trim($c)) . '|' . 
               strtolower(trim($d)));
}
```

### 2. Workflow
1. Alle existierenden Hashes aus DB laden
2. Neue Fragen aus Template generieren/erweitern
3. Hash jeder neuen Frage berechnen
4. Nur einfügen wenn Hash NICHT existiert
5. Statistik ausgeben

### 3. Generator-Datei
`generate_questions_mass.php` mit:
- 150 Fragen pro Modul
- Altersgerechte Verteilung
- Hash-Prüfung vor Insert
- Progress-Anzeige
- Detaillierte Statistik

---

## 📁 NEUE DATEIEN

```
C:\xampp\htdocs\Education\
├── generate_questions_mass.php       # Mass Generator (150/Modul)
├── docs/
│   ├── dinosaurier_grundlagen.csv    # Neues Modul Template
│   └── mass_generator_concept.md     # Dieses Dokument
```

---

## 🦕 NEUES MODUL: DINOSAURIER

### Themengebiete
- Dinosaurier-Arten (T-Rex, Velociraptor, Brachiosaurus, Triceratops)
- Fleischfresser vs. Pflanzenfresser
- Erdzeitalter (Trias, Jura, Kreide)
- Fossilien und Paläontologie
- Aussterben (Meteorit, Klimawandel)
- Vögel als Nachfahren
- Größenvergleiche

### Altersverteilung
| Alter | Beispielthemen |
|-------|----------------|
| 5-7 | "Was war der T-Rex?", "Waren Dinosaurier groß oder klein?" |
| 7-9 | "Was fraßen Pflanzenfresser?", "Wann lebten Dinosaurier?" |
| 9-12 | "Was ist ein Fossil?", "Welches Erdzeitalter?" |
| 12-15 | "Warum starben sie aus?", "Wie alt wurden sie?" |
| 15+ | "Evolutionsbiologie", "Paläontologische Methoden" |

---

## 🤖 BOT-ERWEITERUNGEN (TODO)

### Existierende Bots
| Bot | Funktion | Status |
|-----|----------|--------|
| AIGeneratorBot | KI-Fragen generieren | ✅ v1.0 |
| FunctionTestBot | Funktionstest | ✅ v1.0 |
| SecurityBot | Sicherheitstest | ✅ v1.0 |
| LoadTestBot | Lasttest | ✅ v1.0 |

### Geplante Erweiterungen
| Bot | Funktion | Priorität |
|-----|----------|-----------|
| **QuestionQualityBot** | Prüft Fragen auf Qualität, Rechtschreibung | HOCH |
| **DuplicateCheckerBot** | Findet ähnliche/doppelte Fragen | HOCH |
| **AgeValidatorBot** | Prüft ob Alter zur Schwierigkeit passt | MITTEL |
| **ContentBalancerBot** | Analysiert Themenverteilung pro Modul | MITTEL |
| **TranslationBot** | Übersetzt Fragen (DE↔EN) | NIEDRIG |

---

## ⏱️ ZEITSCHÄTZUNG

| Task | Aufwand |
|------|---------|
| Mass Generator PHP erstellen | ~3h |
| 150 Fragen/Modul Templates | ~6h (15 Module) |
| Dinosaurier-Modul komplett | ~2h |
| Bot-Erweiterungen | ~4h |
| Testing & Bugfixing | ~2h |
| **GESAMT** | **~17h** |

---

## 📋 IMPLEMENTIERUNGSREIHENFOLGE

1. ✅ Konzept erstellen (dieses Dokument)
2. ⏳ Mass Generator PHP erstellen
3. ⏳ Modul "Finanzen" umbenennen (Steuern → Finanzen)
4. ⏳ Modul "Dinosaurier" hinzufügen
5. ⏳ 150 Fragen pro Modul generieren
6. ⏳ Bot-Erweiterungen implementieren
7. ⏳ Final Testing

---

**Erstellt von Claude | sgiT Solution Engineering & IT Services**
