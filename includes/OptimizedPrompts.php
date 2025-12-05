<?php
/**
 * ============================================================================
 * sgiT Education - Optimized Prompt Library v5.0 + ERKLÄRUNGEN
 * ============================================================================
 * 
 * v5.0 NEU (03.12.2025):
 * - NEU: Erklärungsfeld (E:) für kindgerechte Erläuterungen
 * - NEU: Mehr Variabilität durch Seed-Topics
 * - NEU: Randomisierte Beispiele
 * 
 * v4.2 FIX (BUG-007b):
 * - KRITISCH: llama3.2 verweigerte "Wrong answers" als schädlich
 * - Umformuliert zu "Multiple Choice Quiz" mit Optionen B, C, D
 * - Positiver Kontext: "educational quiz for children"
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 5.0
 * @date 03.12.2025
 * ============================================================================
 */

class OptimizedPrompts {
    
    /**
     * v5.0: Seed-Topics für mehr Variabilität
     * Jeder Aufruf wählt zufällig ein Thema aus der Liste
     */
    private static $seedTopics = [
        'mathematik' => [
            'young' => ['Äpfel zählen', 'Bonbons teilen', 'Finger rechnen', 'Tiere zählen', 'Spielzeuge'],
            'medium' => ['Pizza teilen', 'Schulbus Plätze', 'Taschengeld', 'Fußball Teams', 'Blumensträuße'],
            'advanced' => ['Rezept umrechnen', 'Rabatt berechnen', 'Zeitberechnung', 'Strecken planen'],
            'expert' => ['Zinsen', 'Prozentrechnung', 'Geometrie', 'Gleichungen', 'Statistik']
        ],
        'physik' => [
            'young' => ['Spielplatz', 'Bälle werfen', 'Magnete', 'Spiegel', 'Schatten'],
            'medium' => ['Fahrrad', 'Gewitter', 'Taschenlampe', 'Radio', 'Thermometer'],
            'advanced' => ['Stromkreis', 'Hebel', 'Schallwellen', 'Optik', 'Wärme'],
            'expert' => ['Elektromagnetismus', 'Atomphysik', 'Relativität', 'Quantenphysik']
        ],
        'erdkunde' => [
            'young' => ['Berge', 'Flüsse', 'Wälder', 'Meere', 'Wüsten'],
            'medium' => ['Länder Europa', 'Hauptstädte', 'Kontinente', 'Klima', 'Wetter'],
            'advanced' => ['Gebirgsbildung', 'Plattentektonik', 'Klimazonen', 'Bevölkerung'],
            'expert' => ['Globalisierung', 'Nachhaltigkeit', 'Ressourcen', 'Urbanisierung']
        ],
        'biologie' => [
            'young' => ['Haustiere', 'Insekten', 'Blumen', 'Bäume', 'Körperteile'],
            'medium' => ['Verdauung', 'Atmung', 'Pflanzen wachsen', 'Tiere Lebensraum'],
            'advanced' => ['Zellen', 'Evolution', 'Genetik', 'Ökosysteme'],
            'expert' => ['DNA', 'Proteine', 'Nervensystem', 'Immunsystem']
        ],
        'bitcoin' => [
            'young' => ['Spardose', 'Taschengeld', 'Teilen', 'Digitales Geld'],
            'medium' => ['21 Millionen', 'Satoshis', 'Blockchain einfach', 'Ohne Bank'],
            'advanced' => ['Mining', 'Halving', 'Wallet Sicherheit', 'Transaktionen'],
            'expert' => ['Lightning Network', 'Proof of Work', 'Dezentralisierung', 'Kryptografie']
        ]
    ];
    
    public static function getPrompt(string $module, int $age): string {
        $module = strtolower(trim($module));
        
        $prompts = [
            'mathematik' => self::getMathematikPrompt($age),
            'physik' => self::getPhysikPrompt($age),
            'chemie' => self::getChemiePrompt($age),
            'biologie' => self::getBiologiePrompt($age),
            'erdkunde' => self::getErdkundePrompt($age),
            'geschichte' => self::getGeschichtePrompt($age),
            'kunst' => self::getKunstPrompt($age),
            'musik' => self::getMusikPrompt($age),
            'computer' => self::getComputerPrompt($age),
            'programmieren' => self::getProgrammierenPrompt($age),
            'bitcoin' => self::getBitcoinPrompt($age),
            'steuern' => self::getSteuernPrompt($age),
            'englisch' => self::getEnglischPrompt($age),
            'lesen' => self::getLesenPrompt($age),
            'wissenschaft' => self::getWissenschaftPrompt($age),
            'verkehr' => self::getVerkehrPrompt($age),
        ];
        
        return $prompts[$module] ?? self::getGenericPrompt($module, $age);
    }
    
    private static function getAgeGroup(int $age): string {
        if ($age <= 7) return 'young';
        if ($age <= 10) return 'medium';
        if ($age <= 14) return 'advanced';
        return 'expert';
    }
    
    /**
     * v5.0: Wählt zufälliges Seed-Thema für Variabilität
     */
    private static function getSeedTopic(string $module, string $ageGroup): string {
        if (isset(self::$seedTopics[$module][$ageGroup])) {
            $topics = self::$seedTopics[$module][$ageGroup];
            return $topics[array_rand($topics)];
        }
        return '';
    }
    
    /**
     * v5.0: Neue Regeln MIT Erklärungsfeld
     */
    private static function getRules(): string {
        return "OUTPUT FORMAT for multiple choice quiz:
Q: [Question in GERMAN]
A: [Correct answer]
B: [Alternative choice]
C: [Alternative choice]
D: [Alternative choice]
E: [Simple 1-sentence explanation in GERMAN why A is correct]

RULES:
- All text in GERMAN
- Keep answers SHORT (number or few words)
- E: must be a simple, child-friendly explanation (max 20 words)
- Output ONLY these 6 lines, nothing else
- This is for an educational children's quiz app";
    }
    
    // ========================================================================
    // MATHEMATIK - v5.0 mit Erklärung + Seed-Topic
    // ========================================================================
    private static function getMathematikPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        $seedTopic = self::getSeedTopic('mathematik', $group);
        
        if ($group === 'young') {
            return "You are creating a fun educational math quiz for {$age}-year-old children.
THEME: {$seedTopic}

Create ONE multiple choice math question with addition or subtraction (numbers 1-20).

EXAMPLE:
Q: Was ist 7 + 5?
A: 12
B: 11
C: 13
D: 8
E: 7 plus 5 ergibt 12, weil wir 5 zu 7 dazuzählen.

" . self::getRules() . "

Create a NEW math question about {$seedTopic}:
Q: Was ist";
        }
        
        if ($group === 'medium') {
            return "You are creating an educational math quiz for {$age}-year-old students.
THEME: {$seedTopic}

Create ONE multiple choice question with multiplication or division.

EXAMPLE:
Q: Wenn 6 Kinder je 7 Bonbons bekommen, wie viele sind das?
A: 42
B: 48
C: 36
D: 49
E: 6 mal 7 ergibt 42 Bonbons insgesamt.

" . self::getRules() . "

Create a NEW math question about {$seedTopic}:
Q:";
        }
        
        if ($group === 'advanced') {
            return "You are creating an educational math quiz for {$age}-year-old students.
THEME: {$seedTopic}

Create ONE multiple choice question about equations, fractions, or percentages.

EXAMPLE:
Q: Ein Pullover kostet 40€. Mit 25% Rabatt kostet er?
A: 30€
B: 35€
C: 25€
D: 20€
E: 25% von 40€ sind 10€, also 40€ minus 10€ gleich 30€.

" . self::getRules() . "

Create a NEW math question about {$seedTopic}:
Q:";
        }
        
        // Age 15+
        return "You are creating an educational math quiz for {$age}-year-old students.
THEME: {$seedTopic}

Create ONE multiple choice question about algebra, geometry, or word problems.

EXAMPLE:
Q: Ein Auto fährt 180 km in 3 Stunden. Wie schnell ist es?
A: 60 km/h
B: 540 km/h
C: 183 km/h
D: 6 km/h
E: Geschwindigkeit = Strecke durch Zeit, also 180 geteilt durch 3 gleich 60.

" . self::getRules() . "

Create a NEW math question about {$seedTopic}:
Q:";
    }
    
    // ========================================================================
    // PHYSIK - v5.0 mit Erklärung
    // ========================================================================
    private static function getPhysikPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        $seedTopic = self::getSeedTopic('physik', $group);
        
        $examples = [
            'young' => "Q: Warum fällt ein Ball auf den Boden?
A: Wegen der Schwerkraft
B: Wegen dem Wind
C: Weil er schwer ist
D: Weil er rund ist
E: Die Erde zieht alles zu sich heran, das nennt man Schwerkraft.",
            'medium' => "Q: Was leitet Strom am besten?
A: Metall
B: Plastik
C: Gummi
D: Holz
E: Metall hat freie Elektronen, die den Strom leiten können.",
            'advanced' => "Q: Was ist die Einheit für Stromstärke?
A: Ampere
B: Volt
C: Watt
D: Ohm
E: Ampere misst wie viel Strom fließt, benannt nach André-Marie Ampère.",
            'expert' => "Q: Wie lautet die Formel für elektrische Leistung?
A: P = U × I
B: P = U ÷ I
C: P = U + I
D: P = R × I
E: Leistung ist Spannung mal Stromstärke, also P gleich U mal I."
        ];
        
        return "You are creating an educational physics quiz for {$age}-year-old students.
THEME: {$seedTopic}

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE physics question about {$seedTopic}:
Q:";
    }
    
    // ========================================================================
    // CHEMIE - v5.0 mit Erklärung
    // ========================================================================
    private static function getChemiePrompt(int $age): string {
        $group = self::getAgeGroup($age);
        
        $examples = [
            'young' => "Q: Was passiert wenn Eis warm wird?
A: Es schmilzt zu Wasser
B: Es wird zu Luft
C: Es verschwindet
D: Es wird härter
E: Wärme macht aus festem Eis wieder flüssiges Wasser.",
            'medium' => "Q: Was ist das chemische Symbol für Gold?
A: Au
B: Go
C: Gd
D: Ag
E: Au kommt vom lateinischen Wort Aurum für Gold.",
            'advanced' => "Q: Welchen pH-Wert hat eine Säure?
A: Unter 7
B: Über 7
C: Genau 7
D: Genau 14
E: Säuren haben pH-Werte von 0 bis 7, je niedriger desto saurer.",
            'expert' => "Q: Was ist H2SO4?
A: Schwefelsäure
B: Salzsäure
C: Salpetersäure
D: Kohlensäure
E: H2SO4 enthält Wasserstoff, Schwefel und Sauerstoff, also Schwefelsäure."
        ];
        
        return "You are creating an educational chemistry quiz for {$age}-year-old students.

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE chemistry question:
Q:";
    }
    
    // ========================================================================
    // BIOLOGIE - v5.0 mit Erklärung
    // ========================================================================
    private static function getBiologiePrompt(int $age): string {
        $group = self::getAgeGroup($age);
        $seedTopic = self::getSeedTopic('biologie', $group);
        
        $examples = [
            'young' => "Q: Wie viele Beine hat eine Spinne?
A: 8
B: 6
C: 4
D: 10
E: Spinnen sind keine Insekten, sie haben immer 8 Beine.",
            'medium' => "Q: Was brauchen Pflanzen für Photosynthese?
A: Licht und Wasser
B: Nur Erde
C: Nur Wärme
D: Nur Luft
E: Pflanzen nutzen Sonnenlicht und Wasser um Zucker herzustellen.",
            'advanced' => "Q: Wie viele Chromosomen hat ein Mensch?
A: 46
B: 23
C: 48
D: 44
E: Menschen haben 23 Chromosomenpaare, also insgesamt 46 Chromosomen.",
            'expert' => "Q: Was speichert unsere Erbinformation?
A: DNA
B: Proteine
C: Vitamine
D: Hormone
E: Die DNA enthält den genetischen Bauplan für alle Lebewesen."
        ];
        
        return "You are creating an educational biology quiz for {$age}-year-old students.
THEME: {$seedTopic}

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE biology question about {$seedTopic}:
Q:";
    }
    
    // ========================================================================
    // BITCOIN - v5.0 mit Erklärung
    // ========================================================================
    private static function getBitcoinPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        $seedTopic = self::getSeedTopic('bitcoin', $group);
        
        $examples = [
            'young' => "Q: Kann man Bitcoin anfassen?
A: Nein
B: Ja
C: Manchmal
D: Nur mit Handschuhen
E: Bitcoin existiert nur digital im Computer, nicht als echte Münze.",
            'medium' => "Q: Wie viele Bitcoin gibt es maximal?
A: 21 Millionen
B: 100 Millionen
C: Unendlich viele
D: 1 Milliarde
E: Es können niemals mehr als 21 Millionen Bitcoin existieren.",
            'advanced' => "Q: Was passiert beim Bitcoin Halving?
A: Mining-Belohnung wird halbiert
B: Preis wird halbiert
C: Alle Bitcoins werden gelöscht
D: Blockchain wird geteilt
E: Alle 4 Jahre bekommen Miner nur noch halb so viele neue Bitcoin.",
            'expert' => "Q: Was ist Proof of Work?
A: Rechenarbeit zur Validierung
B: Ein Passwort
C: Eine Banküberweisung
D: Ein Aktienkurs
E: Computer lösen schwere Rechenaufgaben um Transaktionen zu bestätigen."
        ];
        
        return "You are creating an educational Bitcoin/cryptocurrency quiz for {$age}-year-old students.
THEME: {$seedTopic}

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE Bitcoin question about {$seedTopic}:
Q:";
    }
    
    // ========================================================================
    // ERDKUNDE - v5.0 mit Erklärung
    // ========================================================================
    private static function getErdkundePrompt(int $age): string {
        $group = self::getAgeGroup($age);
        $seedTopic = self::getSeedTopic('erdkunde', $group);
        
        $examples = [
            'young' => "Q: Was ist die Hauptstadt von Deutschland?
A: Berlin
B: München
C: Hamburg
D: Frankfurt
E: Berlin ist seit 1990 wieder die Hauptstadt von ganz Deutschland.",
            'medium' => "Q: Welcher ist der längste Fluss Deutschlands?
A: Rhein
B: Donau
C: Elbe
D: Main
E: Der Rhein fließt 865 km durch Deutschland, länger als alle anderen.",
            'advanced' => "Q: Wie viele Kontinente gibt es?
A: 7
B: 6
C: 5
D: 8
E: Die 7 Kontinente sind Europa, Asien, Afrika, Nord- und Südamerika, Australien und Antarktis.",
            'expert' => "Q: Warum gibt es Jahreszeiten?
A: Erdachsenneigung
B: Sonnenentfernung
C: Mondphasen
D: Erdrotation
E: Die Erde ist um 23,5 Grad geneigt, dadurch bekommen verschiedene Teile unterschiedlich viel Sonne."
        ];
        
        return "You are creating an educational geography quiz for {$age}-year-old students.
THEME: {$seedTopic}

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE geography question about {$seedTopic}:
Q:";
    }
    
    // ========================================================================
    // GESCHICHTE - v5.0 mit Erklärung
    // ========================================================================
    private static function getGeschichtePrompt(int $age): string {
        $group = self::getAgeGroup($age);
        
        $examples = [
            'young' => "Q: Wo lebten die Ritter?
A: In Burgen
B: In Zelten
C: In Höhlen
D: Auf Schiffen
E: Ritter lebten im Mittelalter in großen Steinburgen.",
            'medium' => "Q: Wann endete der Zweite Weltkrieg?
A: 1945
B: 1939
C: 1918
D: 1950
E: Am 8. Mai 1945 kapitulierte Deutschland und der Krieg war zu Ende.",
            'advanced' => "Q: Wann fiel die Berliner Mauer?
A: 1989
B: 1990
C: 1985
D: 1961
E: Am 9. November 1989 öffneten sich die Grenzen zwischen Ost und West.",
            'expert' => "Q: Wer war der erste Bundeskanzler?
A: Konrad Adenauer
B: Willy Brandt
C: Ludwig Erhard
D: Helmut Schmidt
E: Konrad Adenauer wurde 1949 erster Bundeskanzler der Bundesrepublik Deutschland."
        ];
        
        return "You are creating an educational history quiz for {$age}-year-old students.
FOCUS: German and European history

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE history question:
Q:";
    }
    
    // ========================================================================
    // KUNST - v5.0 mit Erklärung
    // ========================================================================
    private static function getKunstPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        
        $examples = [
            'young' => "Q: Welche Farbe entsteht aus Rot und Gelb?
A: Orange
B: Grün
C: Lila
D: Braun
E: Rot und Gelb gemischt ergibt die Farbe Orange.",
            'medium' => "Q: Was sind die drei Grundfarben?
A: Rot, Gelb, Blau
B: Rot, Grün, Blau
C: Orange, Grün, Lila
D: Schwarz, Weiß, Grau
E: Aus Rot, Gelb und Blau kann man alle anderen Farben mischen.",
            'advanced' => "Q: Wer malte die Mona Lisa?
A: Leonardo da Vinci
B: Pablo Picasso
C: Vincent van Gogh
D: Michelangelo
E: Leonardo da Vinci malte die Mona Lisa vor etwa 500 Jahren in Italien.",
            'expert' => "Q: Was ist typisch für den Impressionismus?
A: Lichteffekte und schnelle Pinselstriche
B: Geometrische Formen
C: Religiöse Motive
D: Schwarze Umrisse
E: Impressionisten malten Licht und Bewegung mit kurzen, sichtbaren Pinselstrichen."
        ];
        
        return "You are creating an educational art quiz for {$age}-year-old students.

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE art question:
Q:";
    }
    
    // ========================================================================
    // MUSIK - v5.0 mit Erklärung
    // ========================================================================
    private static function getMusikPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        
        $examples = [
            'young' => "Q: Welches Instrument hat Tasten?
A: Klavier
B: Gitarre
C: Trompete
D: Trommel
E: Das Klavier hat schwarze und weiße Tasten die man drückt.",
            'medium' => "Q: Wie viele Saiten hat eine Gitarre?
A: 6
B: 4
C: 8
D: 5
E: Eine normale Gitarre hat 6 Saiten von dick bis dünn.",
            'advanced' => "Q: Wer komponierte Für Elise?
A: Beethoven
B: Mozart
C: Bach
D: Haydn
E: Ludwig van Beethoven schrieb dieses berühmte Klavierstück um 1810.",
            'expert' => "Q: Was ist ein Akkord?
A: Mehrere Töne gleichzeitig
B: Ein einzelner Ton
C: Eine Pause
D: Die Lautstärke
E: Ein Akkord besteht aus mindestens drei Tönen die gleichzeitig erklingen."
        ];
        
        return "You are creating an educational music quiz for {$age}-year-old students.

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE music question:
Q:";
    }
    
    // ========================================================================
    // COMPUTER - v5.0 mit Erklärung
    // ========================================================================
    private static function getComputerPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        
        $examples = [
            'young' => "Q: Womit klickt man am Computer?
A: Maus
B: Tastatur
C: Bildschirm
D: Drucker
E: Mit der Maus bewegt man den Zeiger und klickt auf Dinge.",
            'medium' => "Q: Was speichert Daten dauerhaft?
A: Festplatte
B: RAM
C: CPU
D: Monitor
E: Die Festplatte speichert alles auch wenn der Computer aus ist.",
            'advanced' => "Q: Wofür steht USB?
A: Universal Serial Bus
B: United System Base
C: Ultra Speed Byte
D: Universal System Box
E: USB bedeutet Universal Serial Bus, ein Standard zum Anschließen von Geräten.",
            'expert' => "Q: Was ist schneller: SSD oder HDD?
A: SSD
B: HDD
C: Beide gleich
D: CD-ROM
E: SSDs haben keine beweglichen Teile und sind daher viel schneller als Festplatten."
        ];
        
        return "You are creating an educational computer quiz for {$age}-year-old students.

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE computer question:
Q:";
    }
    
    // ========================================================================
    // PROGRAMMIEREN - v5.0 mit Erklärung
    // ========================================================================
    private static function getProgrammierenPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        
        $examples = [
            'young' => "Q: Was macht ein Computerprogramm?
A: Befehle ausführen
B: Bilder malen
C: Musik spielen
D: Essen kochen
E: Ein Programm sagt dem Computer Schritt für Schritt was er tun soll.",
            'medium' => "Q: Was speichert eine Variable?
A: Einen Wert
B: Ein Bild
C: Einen Computer
D: Das Internet
E: Eine Variable ist wie eine Schachtel die einen Wert wie eine Zahl speichert.",
            'advanced' => "Q: Welche Sprache hat ein Schlangen-Logo?
A: Python
B: Java
C: JavaScript
D: C++
E: Python heißt nach der Komiker-Gruppe Monty Python und hat eine Schlange als Logo.",
            'expert' => "Q: Was macht eine For-Schleife?
A: Wiederholt Code
B: Beendet Programm
C: Erstellt Variable
D: Löscht Daten
E: Eine For-Schleife wiederholt einen Codeblock eine bestimmte Anzahl mal."
        ];
        
        return "You are creating an educational programming quiz for {$age}-year-old students.

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE programming question:
Q:";
    }
    
    // ========================================================================
    // STEUERN - v5.0 mit Erklärung
    // ========================================================================
    private static function getSteuernPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        
        $examples = [
            'young' => "Q: Was macht man mit gespartem Geld?
A: Aufbewahren für später
B: Wegwerfen
C: Verbrennen
D: Essen
E: Sparen bedeutet Geld behalten um später etwas Größeres kaufen zu können.",
            'medium' => "Q: Wofür werden Steuern verwendet?
A: Schulen und Straßen
B: Nur für Politiker
C: Wird verbrannt
D: Kommt zurück
E: Steuern bezahlen öffentliche Dinge wie Schulen, Straßen und Krankenhäuser.",
            'advanced' => "Q: Wie hoch ist die normale Mehrwertsteuer in Deutschland?
A: 19%
B: 7%
C: 25%
D: 10%
E: Die reguläre Mehrwertsteuer beträgt 19%, für Lebensmittel nur 7%.",
            'expert' => "Q: Was ist Brutto minus Abzüge?
A: Netto
B: Brutto
C: Steuer
D: Gewinn
E: Netto ist was übrig bleibt wenn Steuern und Versicherungen abgezogen sind."
        ];
        
        return "You are creating an educational finance quiz for {$age}-year-old students.

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE finance question:
Q:";
    }
    
    // ========================================================================
    // ENGLISCH - v5.0 mit Erklärung
    // ========================================================================
    private static function getEnglischPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        
        $examples = [
            'young' => "Q: Was heißt Hund auf Englisch?
A: Dog
B: Cat
C: Bird
D: Fish
E: Hund heißt auf Englisch dog, gesprochen wie dawg.",
            'medium' => "Q: Wie sagt man Ich bin 10 Jahre alt?
A: I am 10 years old
B: I have 10 years
C: I am 10 years
D: I be 10 old
E: Im Englischen sagt man I am, nicht I have für das Alter.",
            'advanced' => "Q: Was ist die Vergangenheit von eat?
A: ate
B: eated
C: eaten
D: eet
E: Eat ist unregelmäßig: eat-ate-eaten, nicht eated.",
            'expert' => "Q: Wann benutzt man since?
A: Bei einem Zeitpunkt
B: Bei einer Zeitdauer
C: Beide sind identisch
D: Nur in Fragen
E: Since benutzt man für Zeitpunkte wie since Monday, for für Zeiträume."
        ];
        
        return "You are creating an educational English quiz for {$age}-year-old German students.

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE English question:
Q:";
    }
    
    // ========================================================================
    // LESEN - v5.0 mit Erklärung
    // ========================================================================
    private static function getLesenPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        
        $examples = [
            'young' => "Q: Welcher Buchstabe kommt nach M?
A: N
B: L
C: O
D: K
E: Das Alphabet geht L-M-N, also kommt N nach M.",
            'medium' => "Q: Wie viele Silben hat Schmetterling?
A: 3
B: 2
C: 4
D: 1
E: Schmet-ter-ling hat drei Silben, man kann klatschen zum Zählen.",
            'advanced' => "Q: Was ist ein Adjektiv?
A: Eigenschaftswort
B: Namenwort
C: Tunwort
D: Bindewort
E: Adjektive beschreiben wie etwas ist: groß, klein, schön, alt.",
            'expert' => "Q: In welchem Fall steht dem Hund?
A: Dativ
B: Akkusativ
C: Nominativ
D: Genitiv
E: Dem zeigt den 3. Fall (Dativ) an, wie bei Wem gebe ich etwas."
        ];
        
        return "You are creating an educational German language quiz for {$age}-year-old students.

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE German language question:
Q:";
    }
    
    // ========================================================================
    // WISSENSCHAFT - v5.0 mit Erklärung
    // ========================================================================
    private static function getWissenschaftPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        
        $examples = [
            'young' => "Q: Warum regnet es?
A: Wolken lassen Wasser fallen
B: Die Sonne weint
C: Der Wind bringt Wasser
D: Weil es kalt ist
E: Wolken bestehen aus winzigen Wassertropfen die zu schwer werden und fallen.",
            'medium' => "Q: Warum schwimmt Holz auf Wasser?
A: Es ist leichter als Wasser
B: Es ist nass
C: Es ist flach
D: Wasser mag Holz
E: Holz ist weniger dicht als Wasser, deshalb schwimmt es oben.",
            'advanced' => "Q: Was ist eine Hypothese?
A: Eine testbare Vermutung
B: Ein bewiesener Fakt
C: Eine Meinung
D: Eine Frage
E: Eine Hypothese ist eine Vermutung die man durch Experimente überprüfen kann.",
            'expert' => "Q: Was macht wissenschaftliche Ergebnisse glaubwürdig?
A: Wiederholbare Experimente
B: Meinungen von Experten
C: Tradition
D: Computer
E: Wenn andere das Experiment wiederholen und das gleiche Ergebnis bekommen."
        ];
        
        return "You are creating an educational science quiz for {$age}-year-old students.

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE science question:
Q:";
    }
    
    // ========================================================================
    // VERKEHR - v5.0 mit Erklärung
    // ========================================================================
    private static function getVerkehrPrompt(int $age): string {
        $group = self::getAgeGroup($age);
        
        $examples = [
            'young' => "Q: Was tust du bei roter Ampel?
A: Stehen bleiben
B: Schnell laufen
C: Langsam gehen
D: Tanzen
E: Bei Rot müssen alle anhalten weil Autos fahren dürfen.",
            'medium' => "Q: Wer hat am Zebrastreifen Vorrang?
A: Fußgänger
B: Autos
C: Fahrräder
D: Niemand
E: Am Zebrastreifen müssen Autos anhalten wenn Fußgänger überqueren wollen.",
            'advanced' => "Q: Was bedeutet ein dreieckiges Schild mit rotem Rand?
A: Achtung Gefahr
B: Verbot
C: Erlaubnis
D: Parken
E: Dreieckige Warnschilder mit rotem Rand warnen vor Gefahren voraus.",
            'expert' => "Q: Ab wann darf man begleitet Auto fahren?
A: 17 Jahre
B: 16 Jahre
C: 18 Jahre
D: 15 Jahre
E: Mit 17 Jahren kann man mit BF17 begleitet fahren, alleine erst mit 18."
        ];
        
        return "You are creating an educational traffic safety quiz for {$age}-year-old students.

EXAMPLE:
{$examples[$group]}

" . self::getRules() . "

Create ONE traffic question:
Q:";
    }
    
    // ========================================================================
    // GENERIC FALLBACK - v5.0 mit Erklärung
    // ========================================================================
    private static function getGenericPrompt(string $module, int $age): string {
        return "You are creating an educational {$module} quiz for {$age}-year-old children.

" . self::getRules() . "

Create ONE question:
Q:";
    }
}
