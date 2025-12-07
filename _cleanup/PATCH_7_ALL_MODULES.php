<?php
/**
 * PATCH 7: Alle 16 Module mit klaren Definitionen
 * Ersetzt getGeneralPrompt() vollständig
 */

// Dies ist der vollständige Ersatz für die getGeneralPrompt() Methode

private function getGeneralPrompt($module, $age, $isGerman = true) {
    
    // ========================================
    // LESEN (Deutsch-Unterricht)
    // ========================================
    if ($module == 'lesen') {
        return "MODUL: LESEN (Deutschunterricht)

Definition: Buchstaben, Wörter, Sätze, deutsche Grammatik, Rechtschreibung

Erstelle eine LESEN/DEUTSCH-Frage auf DEUTSCH für Alter $age.

LESEN IST:
✅ Buchstaben erkennen
✅ Wörter bilden und verstehen
✅ Deutsche Grammatik (Nomen, Verben, Adjektive)
✅ Rechtschreibung
✅ Sätze verstehen

LESEN IST NICHT:
❌ Hauptstädte, Länder (Das ist ERDKUNDE!)
❌ Englische Wörter (Das ist ENGLISCH!)
❌ Rechnen mit Zahlen (Das ist MATHEMATIK!)

Format EXAKT:
Q: [Frage auf Deutsch über Lesen/Grammatik]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

NIEMALS Platzhalter verwenden!";
    }
    
    // ========================================
    // PHYSIK
    // ========================================
    if ($module == 'physik') {
        return "MODUL: PHYSIK

Definition: Mechanik, Energie, Kräfte, Licht, Schall, Elektrizität, Magnetismus, Wärme

Erstelle eine PHYSIK-Frage auf DEUTSCH für Alter $age.

PHYSIK IST:
✅ Schwerkraft, Reibung, Hebel
✅ Lichtgeschwindigkeit, Schall
✅ Elektrizität, Magnetismus
✅ Wärme, Energie, Bewegung

PHYSIK IST NICHT:
❌ Hauptstädte, Länder, Städte (Das ist ERDKUNDE!)
❌ Chemische Formeln (Das ist CHEMIE!)
❌ Lebewesen, Organe (Das ist BIOLOGIE!)
❌ Programmieren (Das ist COMPUTER!)

Format EXAKT:
Q: [Frage auf Deutsch über Physik]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

NIEMALS Fragen über Erdkunde!
NIEMALS Platzhalter verwenden!";
    }
    
    // ========================================
    // BIOLOGIE
    // ========================================
    if ($module == 'biologie') {
        return "MODUL: BIOLOGIE

Definition: Lebewesen, Pflanzen, Tiere, Körper, Zellen, DNA, Evolution

Erstelle eine BIOLOGIE-Frage auf DEUTSCH für Alter $age.

BIOLOGIE IST:
✅ Tiere und Pflanzen
✅ Menschlicher Körper und Organe
✅ Zellen und DNA
✅ Photosynthese, Evolution
✅ Ökosysteme

BIOLOGIE IST NICHT:
❌ Hauptstädte, Länder (Das ist ERDKUNDE!)
❌ Chemische Formeln (Das ist CHEMIE!)
❌ Physikalische Kräfte (Das ist PHYSIK!)

Format EXAKT:
Q: [Frage auf Deutsch über Biologie]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

NIEMALS Fragen über Erdkunde!
NIEMALS Platzhalter verwenden!";
    }
    
    // ========================================
    // BITCOIN
    // ========================================
    if ($module == 'bitcoin') {
        return "MODUL: BITCOIN

Definition: Bitcoin als digitales Geld, Blockchain, Dezentralisierung, keine Banken

Erstelle eine BITCOIN-Frage auf DEUTSCH für Alter $age.

BITCOIN IST:
✅ Digitales Geld
✅ Funktioniert ohne Banken (dezentral)
✅ 21 Millionen Bitcoin Maximum
✅ Blockchain-Technologie
✅ Finanzielle Freiheit

BITCOIN IST NICHT:
❌ Hauptstädte, Länder (Das ist ERDKUNDE!)
❌ Normale Banken und Euro (Das ist STEUERN!)
❌ Programmiersprachen (Das ist PROGRAMMIEREN!)

Format EXAKT:
Q: [Frage auf Deutsch über Bitcoin]
A: [Richtige Antwort]
W1: [Falsche aber plausible Antwort]
W2: [Falsche aber plausible Antwort]
W3: [Falsche aber plausible Antwort]

Keine Platzhalter!
Altersgerecht formulieren!";
    }
    
    // ========================================
    // ERDKUNDE
    // ========================================
    if ($module == 'erdkunde') {
        return "MODUL: ERDKUNDE

Definition: Länder, Hauptstädte, Kontinente, Flüsse, Berge, Landkarten

Erstelle eine ERDKUNDE-Frage auf DEUTSCH für Alter $age.

ERDKUNDE IST:
✅ Länder und Hauptstädte
✅ Kontinente, Ozeane, Meere
✅ Flüsse, Berge, Wüsten
✅ Wo liegt was auf der Erde?
✅ Landkarten lesen

ERDKUNDE IST NICHT:
❌ Physikalische Kräfte (Das ist PHYSIK!)
❌ Chemische Elemente (Das ist CHEMIE!)
❌ Lebewesen (Das ist BIOLOGIE!)
❌ Geschichte von Ländern (Das ist GESCHICHTE!)

Format EXAKT:
Q: [Frage auf Deutsch über Erdkunde]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

Keine Platzhalter!";
    }
    
    // ========================================
    // CHEMIE
    // ========================================
    if ($module == 'chemie') {
        return "MODUL: CHEMIE

Definition: Stoffe, Elemente, chemische Reaktionen, Periodensystem, Moleküle

Erstelle eine CHEMIE-Frage auf DEUTSCH für Alter $age.

CHEMIE IST:
✅ Chemische Elemente (H, O, C, etc.)
✅ Periodensystem
✅ Chemische Reaktionen
✅ Moleküle und Atome
✅ Säuren, Basen, Salze

CHEMIE IST NICHT:
❌ Hauptstädte (Das ist ERDKUNDE!)
❌ Mechanische Kräfte (Das ist PHYSIK!)
❌ Lebende Organismen (Das ist BIOLOGIE!)

Format EXAKT:
Q: [Frage auf Deutsch über Chemie]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

Keine Platzhalter!";
    }
    
    // ========================================
    // WISSENSCHAFT
    // ========================================
    if ($module == 'wissenschaft') {
        return "MODUL: WISSENSCHAFT

Definition: Allgemeine Naturwissenschaften, Experimente, wie funktioniert die Natur

Erstelle eine WISSENSCHAFT-Frage auf DEUTSCH für Alter $age.

WISSENSCHAFT IST:
✅ Wie funktioniert die Natur?
✅ Experimente und Beobachtungen
✅ Mix aus Physik, Chemie, Biologie (grundlegend)
✅ Wissenschaftliche Methoden

WISSENSCHAFT IST NICHT:
❌ Hauptstädte (Das ist ERDKUNDE!)
❌ Nur Physik-Formeln (Das ist PHYSIK!)
❌ Nur Chemie (Das ist CHEMIE!)

Format EXAKT:
Q: [Frage auf Deutsch über Wissenschaft]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

Keine Platzhalter!";
    }
    
    // ========================================
    // KUNST
    // ========================================
    if ($module == 'kunst') {
        return "MODUL: KUNST

Definition: Malen, Zeichnen, Künstler, Kunstgeschichte, Farben, Formen

Erstelle eine KUNST-Frage auf DEUTSCH für Alter $age.

KUNST IST:
✅ Malen und Zeichnen
✅ Künstler und ihre Werke
✅ Farben und Formen in der Kunst
✅ Kunsttechniken (Aquarell, Öl, Skulptur)

KUNST IST NICHT:
❌ Musik und Instrumente (Das ist MUSIK!)
❌ Geometrische Berechnungen (Das ist MATHEMATIK!)
❌ Hauptstädte (Das ist ERDKUNDE!)

Format EXAKT:
Q: [Frage auf Deutsch über Kunst]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

Keine Platzhalter!";
    }
    
    // ========================================
    // MUSIK
    // ========================================
    if ($module == 'musik') {
        return "MODUL: MUSIK

Definition: Musikinstrumente, Noten, Komponisten, Musikstile

Erstelle eine MUSIK-Frage auf DEUTSCH für Alter $age.

MUSIK IST:
✅ Musikinstrumente
✅ Noten lesen
✅ Komponisten und ihre Werke
✅ Musikstile (Klassik, Jazz, Pop)
✅ Rhythmus und Melodie

MUSIK IST NICHT:
❌ Kunst und Malen (Das ist KUNST!)
❌ Mathematische Berechnungen (Das ist MATHEMATIK!)
❌ Hauptstädte (Das ist ERDKUNDE!)

Format EXAKT:
Q: [Frage auf Deutsch über Musik]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

Keine Platzhalter!";
    }
    
    // ========================================
    // COMPUTER
    // ========================================
    if ($module == 'computer') {
        return "MODUL: COMPUTER

Definition: Hardware, Software, Internet, Dateien, Computersicherheit

Erstelle eine COMPUTER-Frage auf DEUTSCH für Alter $age.

COMPUTER IST:
✅ Hardware (CPU, RAM, Festplatte)
✅ Software und Programme
✅ Internet und Netzwerke
✅ Dateien und Ordner
✅ Computersicherheit

COMPUTER IST NICHT:
❌ Programmieren (Das ist PROGRAMMIEREN!)
❌ Hauptstädte (Das ist ERDKUNDE!)
❌ Physikalische Kräfte (Das ist PHYSIK!)

Format EXAKT:
Q: [Frage auf Deutsch über Computer]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

Keine Platzhalter!";
    }
    
    // ========================================
    // GESCHICHTE
    // ========================================
    if ($module == 'geschichte') {
        return "MODUL: GESCHICHTE

Definition: Historische Ereignisse, wichtige Personen, Zeitalter, Kriege, Erfindungen

Erstelle eine GESCHICHTE-Frage auf DEUTSCH für Alter $age.

GESCHICHTE IST:
✅ Historische Ereignisse
✅ Wichtige Personen der Geschichte
✅ Zeitalter (Mittelalter, Neuzeit, etc.)
✅ Kriege und Frieden
✅ Erfindungen und Entdeckungen

GESCHICHTE IST NICHT:
❌ Aktuelle Hauptstädte (Das ist ERDKUNDE!)
❌ Physikalische Gesetze (Das ist PHYSIK!)
❌ Lebende Tiere heute (Das ist BIOLOGIE!)

Format EXAKT:
Q: [Frage auf Deutsch über Geschichte]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

Keine Platzhalter!";
    }
    
    // ========================================
    // STEUERN
    // ========================================
    if ($module == 'steuern') {
        return "MODUL: STEUERN

Definition: Steuern, Geld, Einkommensteuer, MwSt, Budget, Finanzielle Bildung

Erstelle eine STEUERN-Frage auf DEUTSCH für Alter $age.

STEUERN IST:
✅ Steuern und Abgaben
✅ Wie funktioniert Geld?
✅ Einkommensteuer, Mehrwertsteuer
✅ Sparen und Investieren
✅ Budget und Haushaltsplan

STEUERN IST NICHT:
❌ Bitcoin (Das ist BITCOIN!)
❌ Hauptstädte (Das ist ERDKUNDE!)
❌ Geschichte (Das ist GESCHICHTE!)

Format EXAKT:
Q: [Frage auf Deutsch über Steuern]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

Keine Platzhalter!";
    }
    
    // ========================================
    // PROGRAMMIEREN
    // ========================================
    if ($module == 'programmieren') {
        return "MODUL: PROGRAMMIEREN

Definition: Programmiersprachen, Code schreiben, Algorithmen, Schleifen, Funktionen

Erstelle eine PROGRAMMIEREN-Frage auf DEUTSCH für Alter $age.

PROGRAMMIEREN IST:
✅ Programmiersprachen (Python, JavaScript, etc.)
✅ Code schreiben
✅ Algorithmen und Logik
✅ Schleifen und Bedingungen
✅ Funktionen und Variablen

PROGRAMMIEREN IST NICHT:
❌ Hardware (Das ist COMPUTER!)
❌ Hauptstädte (Das ist ERDKUNDE!)
❌ Bitcoin-Technologie (Das ist BITCOIN!)

Format EXAKT:
Q: [Frage auf Deutsch über Programmieren]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

Keine Platzhalter!";
    }
    
    // ========================================
    // VERKEHR
    // ========================================
    if ($module == 'verkehr') {
        return "MODUL: VERKEHR

Definition: Verkehrsregeln, Verkehrsschilder, Fahrrad, Fußgänger, Sicherheit

Erstelle eine VERKEHR-Frage auf DEUTSCH für Alter $age.

VERKEHR IST:
✅ Verkehrsregeln
✅ Verkehrsschilder
✅ Sicheres Verhalten im Straßenverkehr
✅ Fahrrad fahren, Fußgänger
✅ Öffentliche Verkehrsmittel

VERKEHR IST NICHT:
❌ Hauptstädte (Das ist ERDKUNDE!)
❌ Physik des Fahrens (Das ist PHYSIK!)
❌ Geschichte des Autos (Das ist GESCHICHTE!)

Format EXAKT:
Q: [Frage auf Deutsch über Verkehr]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

Keine Platzhalter!";
    }
    
    // ========================================
    // FALLBACK für unbekannte Module
    // ========================================
    return "Create a general knowledge question in German for age $age.

IMPORTANT: No placeholders! Real answers only!

Format:
Q: [question in German]
A: [correct answer]
W1: [wrong answer]
W2: [wrong answer]
W3: [wrong answer]";
}
