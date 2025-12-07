# 🍳 Modul "Kochen" - Planungsdokument

**Datum:** 07. Dezember 2025  
**Version:** 1.0  
**Typ:** Interaktives Modul  
**Ziel:** Modul 21 von 21 - DAS LETZTE!

---

## 📋 Übersicht

Interaktives Kochmodul mit Rezepten, Ernährungswissen und Küchen-Quiz.
Altersgerecht angepasst (5-21 Jahre).

---

## 🎯 Modul-Typen

### 1. 📖 Rezepte (Schritt-für-Schritt)
- Einfache Rezepte für Kinder
- Bebilderte Anleitungen
- Schwierigkeitsgrade

### 2. 🥗 Ernährungs-Quiz
- Gesunde Ernährung
- Lebensmittelgruppen
- Vitamine & Nährstoffe

### 3. 🔪 Küchenwissen
- Küchengeräte erkennen
- Maßeinheiten
- Sicherheit in der Küche

---

## 🎮 Aktivitäten nach Alter

### 👶 Alter 5-7
| Aktivität | Beschreibung | Sats |
|-----------|--------------|------|
| Obst erkennen | Was ist das für ein Obst? | 5 |
| Gemüse sortieren | Ordne nach Farben | 5 |
| Einfache Rezepte | Obstsalat, Smoothie | 10 |

### 🧒 Alter 8-12
| Aktivität | Beschreibung | Sats |
|-----------|--------------|------|
| Ernährungs-Quiz | Lebensmittelgruppen | 8 |
| Rezepte lesen | Zutaten & Schritte | 12 |
| Maßeinheiten | ml, g, TL, EL | 10 |

### 🧑 Alter 13-21
| Aktivität | Beschreibung | Sats |
|-----------|--------------|------|
| Nährstoff-Quiz | Vitamine, Proteine | 12 |
| Rezepte kochen | Komplexere Gerichte | 15 |
| Kalorien schätzen | Ernährungsbewusstsein | 12 |

---

## 🏗️ Technische Architektur

### Dateistruktur
```
/kochen/
├── index.php           # Hauptseite mit Aktivitäts-Auswahl
├── quiz.php            # Ernährungs-Quiz (10 Fragen)
├── rezept.php          # Rezept-Viewer
├── zuordnen.php        # Drag & Drop Zuordnung
├── api/
│   └── update_session.php
└── data/
    ├── rezepte.json    # Rezept-Datenbank
    └── quiz.json       # Quiz-Fragen
```

---

## 🚀 MVP-Scope

1. ✅ **index.php** - Übersicht
2. ✅ **quiz.php** - Ernährungs-Quiz (10 Fragen, Sats)
3. ✅ **zuordnen.php** - Lebensmittel zuordnen
4. ✅ **Sats-Integration**

---

*Dokument erstellt: 07.12.2025*
