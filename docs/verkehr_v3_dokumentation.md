# Verkehr Modul - Fragen-Erweiterung v3

**Erstellt:** 06.12.2025  
**Datei:** `docs/verkehr_v3_neue_themen.csv`

---

## 📊 Übersicht

| Info | Wert |
|------|------|
| **Neue Fragen** | 79 |
| **Bestehend in DB** | 46 |
| **Nach Import** | ~125 (je nach Duplikate) |
| **Ziel** | 150+ |

---

## 🆕 Neue Themen (nicht in v1/v2 enthalten)

| Typ | Anzahl | Beispiele |
|-----|--------|-----------|
| `wetter` | 4 | Glatteis, Nebel, Regen |
| `gefahren` | 3 | Aquaplaning, Sekundenschlaf |
| `autobahn` | 4 | Auffahrt, Standstreifen, Raststätte |
| `fahrzeugpruefung` | 2 | TÜV, Hauptuntersuchung |
| `alkohol` | 3 | Promillegrenze, Fahranfänger |
| `fahrzeugteile` | 12 | Winterreifen, Tacho, Lenkrad, Spiegel |
| `notfall` (erweitert) | 6 | Warndreieck, Erste Hilfe, stabile Seitenlage |
| `parken` (erweitert) | 5 | Parkhaus, Tiefgarage, Behindertenparkplatz |
| `recht` | 2 | Handy am Steuer, Bußgeld |
| `sicherheit` (erweitert) | 6 | Warnweste, Verbandskasten, Motorradschutz |
| `umwelt` (erweitert) | 2 | Umweltzone, Grüne Plakette |
| `verkehrsschilder` (erweitert) | 6 | Wildwechsel, P-Schild, Anlieger frei |
| `regeln` (erweitert) | 8 | Abstandsregeln, Linien, Straßenbahn |
| `fahrzeuge` (erweitert) | 4 | Motorrad, Anhänger, Gefahrgut |
| `strassenarten` (erweitert) | 4 | Tunnel, Fahrstreifen, Sackgasse |
| `bereiche` (erweitert) | 2 | Taxistand |
| `verhalten` (erweitert) | 5 | Wildwechsel, Tunnel, Müdigkeit |
| `dokumente` | 1 | KFZ-Versicherung |
| `fuehrerschein` | 1 | Anhänger-Führerschein |
| `wege` | 1 | Fahrradstraße |

---

## 📈 Altersverteilung

| Altersgruppe | Anzahl |
|--------------|--------|
| 5-7 Jahre | 8 |
| 6-8 Jahre | 21 |
| 7-9 Jahre | 10 |
| 8-10 Jahre | 21 |
| 10-12 Jahre | 14 |
| 12-14 Jahre | 3 |
| 14-16 Jahre | 2 |

---

## 🎯 Schwierigkeitsverteilung

| Schwierigkeit | Anzahl |
|---------------|--------|
| 1 (sehr leicht) | 8 |
| 2 (leicht) | 33 |
| 3 (mittel) | 30 |
| 4 (schwer) | 8 |

---

## 🔧 Import-Anleitung

### Option 1: Über Admin Dashboard
1. Gehe zu: http://localhost/Education/admin_v4.php
2. Navigiere zu "CSV Import"
3. Wähle `docs/verkehr_v3_neue_themen.csv`
4. Modul: `verkehr`
5. Importieren

### Option 2: Über Batch Import
1. URL: http://localhost/Education/batch_import.php
2. Datei auswählen
3. Vorschau prüfen
4. Import starten

---

## ✅ MD5-Hash Duplikat-Prüfung

Der Importer verwendet folgenden Hash-Algorithmus:
```php
$data = strtolower(trim($frage));
$data .= '|' . strtolower(trim($antwort_a));
$data .= '|' . strtolower(trim($antwort_b));
$data .= '|' . strtolower(trim($antwort_c));
$data .= '|' . strtolower(trim($antwort_d));
return md5($data);
```

**Alle 79 Fragen sind komplett NEU und sollten keine Duplikate erzeugen!**

---

## 📝 Nächste Schritte

Nach erfolgreichem Import:
1. [ ] Import durchführen
2. [ ] Status Report aktualisieren
3. [ ] Weiter mit Modul: **Steuern** (123 Fragen → 180+ Ziel)

---

**Erstellt von Claude für sgiT Education Platform**
