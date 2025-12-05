# sgiT Education Platform – Ein Familienprojekt wird zur vollwertigen Lernplattform

**Veröffentlicht:** 06. Dezember 2025  
**Autor:** Steven Günther, sgiT Solution Engineering & IT Services  
**Projektversion:** 3.7.9

---

## 🎯 Die Idee: Lernen neu gedacht

Was als kleines Nebenprojekt für meine Kinder begann, hat sich innerhalb weniger Wochen zu einer vollwertigen Lernplattform entwickelt. Die **sgiT Education Platform** kombiniert klassisches Lernen mit modernen Gamification-Elementen und – was es einzigartig macht – einer Bitcoin-Wallet-Integration als Belohnungssystem.

Die Grundidee war simpel: Meine Kinder sollen spielerisch lernen, während sie gleichzeitig verstehen, was digitales Geld ist. Keine abstrakten Konzepte, sondern echtes "Verdienen" durch Wissen.

---

## 📊 Der aktuelle Stand

Nach intensiven Entwicklungswochen steht die Plattform auf soliden Beinen:

| Kennzahl | Stand |
|----------|-------|
| **Fragen in der Datenbank** | 3.263 |
| **Lernmodule** | 16 |
| **Altersgruppe** | 5-21 Jahre (und darüber) |
| **Codezeilen** | ~50.000+ |
| **Behobene Bugs** | 33 (und zählend) |

### Die 16 Lernmodule

Die Module decken klassische Schulfächer ab, gehen aber darüber hinaus:

- 🔢 **Mathematik** – Grundrechenarten bis Algebra
- 📖 **Lesen** – Alphabet bis Textverständnis
- 🇬🇧 **Englisch** – Vokabeln und Grammatik
- 🔬 **Wissenschaft** – Experimente und Naturgesetze
- 🌍 **Erdkunde** – Kontinente, Länder, Hauptstädte
- ⚗️ **Chemie** – Atome, Moleküle, Reaktionen
- ⚛️ **Physik** – Newton bis Quantenphysik
- 🎨 **Kunst** – Techniken und Kunstgeschichte
- 🎵 **Musik** – Noten, Instrumente, Komponisten
- 💻 **Computer** – Hardware, Software, Sicherheit
- 👨‍💻 **Programmieren** – Algorithmen, Variablen, Schleifen
- 📜 **Geschichte** – Von den Dinosauriern bis heute
- 🧬 **Biologie** – Zellen, Evolution, Ökosysteme
- 💰 **Finanzen** – Geld, Sparen, Investieren
- ₿ **Bitcoin** – Blockchain, Mining, Austrian Economics
- 🚗 **Verkehr** – Sicherheit, Regeln, Führerschein-Vorbereitung

Besonders stolz bin ich auf die **altersgerechte Fragenauswahl**: Ein 7-Jähriger bekommt keine Potenzrechnung, während ein Erwachsener auch wirklich gefordert wird.

---

## 💡 Das Besondere: Bitcoin-Wallet Integration

Die Plattform nutzt **Test-Satoshis** als Belohnung. Nach jeder 10-Fragen-Session erhalten die Kinder Sats basierend auf ihrer Leistung:

- Perfekte Session (10/10): Bonus-Sats
- Streak-Bonus für tägliches Lernen
- Achievements für besondere Leistungen

Das ist bewusst pädagogisch gestaltet: Kinder lernen nicht nur Schulfächer, sondern auch den Wert von Geld, Sparen und verzögerter Belohnung. Die Integration mit **BTCPay Server** für echte Bitcoin-Auszahlungen ist bereits vorbereitet.

---

## 🛠️ Technische Details

### Architektur

Die Plattform läuft vollständig containerisiert:

```
┌─────────────────────────────────────────────┐
│                  nginx:alpine               │
│               (Reverse Proxy)               │
│                  Port 8080                  │
└─────────────┬───────────────────────────────┘
              │
┌─────────────▼───────────────┐  ┌────────────────────┐
│      PHP-FPM 8.3           │  │   Ollama (LLM)     │
│   (Application Server)     │◄─┤   tinyllama:latest │
│                            │  │   Port 11434       │
└─────────────┬──────────────┘  └────────────────────┘
              │
┌─────────────▼───────────────┐
│         SQLite              │
│   (questions.db, wallet.db) │
└─────────────────────────────┘
```

### Technologie-Stack

- **Backend:** PHP 8.3 mit PDO/SQLite
- **Frontend:** Vanilla JS, CSS3 mit Custom Properties
- **Datenbank:** SQLite mit WAL-Modus für Concurrent Access
- **AI:** Ollama mit tinyllama für automatische Fragen-Generierung
- **Container:** Docker Compose mit nginx, PHP-FPM, Ollama
- **Backup:** Automatisches Dual-Backup (lokal + OneDrive)

### Performance-Optimierungen

In der neuesten Version (3.7.9) wurde die Query-Performance massiv verbessert:

- **Vorher:** P99-Latenz von 6160ms bei 50 gleichzeitigen Nutzern
- **Nachher:** Queries unter 1ms durch Index-Optimierung und Eliminierung von `ORDER BY RANDOM()`

Die neue Methode nutzt `COUNT + OFFSET` statt `ORDER BY RANDOM()`, was die temporären B-Trees eliminiert und die Performance um das 10-fache steigert.

---

## 🤖 Bot-System: Automatisierte Qualitätssicherung

Ein komplettes Test-Framework überwacht die Plattform:

| Bot | Funktion |
|-----|----------|
| **FunctionTestBot** | Prüft alle Module auf Funktionalität |
| **SecurityBot** | SQL-Injection, XSS, Path Traversal Tests |
| **LoadTestBot** | Performance-Tests mit 5-50 simulierten Usern |
| **AIGeneratorBot** | Automatische Fragen-Generierung via Ollama |

Das ermöglicht kontinuierliche Qualitätssicherung ohne manuellen Aufwand.

---

## 🦊 Foxy – Der Lernassistent

Ein animierter Fuchs namens **Foxy** begleitet die Kinder beim Lernen. Er:

- Gibt Tipps zu den aktuellen Fragen
- Erklärt schwierige Konzepte kindgerecht
- Motiviert bei Fehlern und feiert Erfolge

Foxy nutzt eine eigene SQLite-Datenbank mit kontextbezogenen Antworten und wird künftig mit dem LLM für dynamische Dialoge verbunden.

---

## 🎮 Gamification-Elemente

### Level-System

| Level | Name | Punkte/Frage |
|-------|------|--------------|
| 1 | 👶 Baby | 3 |
| 2 | 🧒 Kind | 5 |
| 3 | 👦 Jugend | 7 |
| 4 | 👨 Erwachsen | 10 |
| 5 | 👴 Opa | 15 |

### Achievements

Über 20 freischaltbare Achievements motivieren zum Weitermachen:
- 🌟 "Perfektionist" – 10/10 in einer Session
- 🔥 "Feuer-Streak" – 7 Tage am Stück gelernt
- 🎓 "Mathe-Meister" – 100 Mathe-Fragen richtig

### Leaderboard

Ein Highscore-System zeigt die besten Lerner und fördert freundschaftlichen Wettbewerb in der Familie.

---

## 🔮 Ausblick: Was kommt als Nächstes?

### Kurzfristig (Q1 2026)

- **BTCPay Server Integration** – Echte Satoshi-Auszahlungen
- **Grafana Dashboards** – Visualisierung der Lernstatistiken
- **Multi-User Pro** – Klassenverwaltung für Schulen

### Mittelfristig

- **Mobile App** – Native iOS/Android-App
- **Sprachausgabe** – TTS für jüngere Kinder
- **AR-Module** – Augmented Reality für Naturwissenschaften

### Langfristig

- **Production Deployment** – Live auf sgit.space
- **Open Source** – Veröffentlichung für andere Familien
- **Plugin-System** – Erweiterbare Module

---

## 💭 Persönliches Fazit

Was als Wochenendprojekt begann, ist zu einem vollwertigen Produkt geworden. Die Kombination aus Bildung und Bitcoin-Wallet ist meines Wissens einzigartig und vermittelt Kindern nicht nur Schulwissen, sondern auch **digitale Mündigkeit**.

Die Platform zeigt, was mit modernen Technologien möglich ist – und dass Lernen nicht langweilig sein muss. Wenn meine Kinder eines Tages ihre ersten echten Satoshis verdienen, haben sie nicht nur gelernt, was Bitcoin ist, sondern auch den Wert von Wissen und Ausdauer verstanden.

---

## 🔗 Links & Kontakt

- **Projekt:** sgiT Education Platform v3.7.9
- **Unternehmen:** [sgit.space](https://sgit.space)
- **Technologie:** PHP 8.3, SQLite, Docker, Ollama
- **Status:** In aktiver Entwicklung

---

*Dieser Beitrag wurde am 06. Dezember 2025 veröffentlicht und spiegelt den aktuellen Entwicklungsstand wider.*

---

### Tags
`#Education` `#Bitcoin` `#Gamification` `#PHP` `#Docker` `#OpenSource` `#Familie` `#Lernen` `#sgiT`
