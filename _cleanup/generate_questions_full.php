<?php
/**
 * ============================================================================
 * sgiT Education - FULL Question Generator v2.0
 * ============================================================================
 * 
 * Generiert Fragen für ALLE 16 Module direkt in die Datenbank.
 * 
 * @author Claude AI für sgiT
 * @version 2.0
 * @date 04.12.2025
 * ============================================================================
 */

set_time_limit(600);

$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><title>sgiT Full Generator</title><meta charset='utf-8'>";
    echo "<style>
        body { font-family: 'Consolas', monospace; background: #1A3503; color: #43D240; padding: 20px; }
        h1 { color: #43D240; border-bottom: 2px solid #43D240; padding-bottom: 10px; }
        .stats { background: #0d1a02; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .module { margin: 5px 0; padding: 5px; }
        .success { color: #43D240; }
        .skip { color: #888; }
        pre { white-space: pre-wrap; }
    </style></head><body>";
    echo "<h1>🚀 sgiT Education - FULL Question Generator v2.0</h1>";
}

$dbPath = __DIR__ . '/AI/data/questions.db';
$db = new SQLite3($dbPath);
$db->enableExceptions(false);

$existingHashes = [];
$result = $db->query("SELECT question_hash FROM questions WHERE question_hash IS NOT NULL");
while ($row = $result->fetchArray()) { $existingHashes[$row[0]] = true; }

echo "<div class='stats'>📊 " . count($existingHashes) . " existierende Hashes</div>";

function generateHash($q, $a, $b, $c, $d) {
    return md5(strtolower(trim($q)) . '|' . strtolower(trim($a)) . '|' . 
               strtolower(trim($b)) . '|' . strtolower(trim($c)) . '|' . strtolower(trim($d)));
}

function importQ($db, &$hashes, $mod, $q, $cor, $w1, $w2, $w3, $expl, $diff, $min, $max) {
    $ans = [$cor, $w1, $w2, $w3]; shuffle($ans);
    $idx = array_search($cor, $ans);
    $let = ['A','B','C','D'][$idx];
    $hash = generateHash($q, $ans[0], $ans[1], $ans[2], $ans[3]);
    if (isset($hashes[$hash])) return 'dup';
    
    $opt = json_encode($ans, JSON_UNESCAPED_UNICODE);
    $stmt = $db->prepare("INSERT INTO questions (module, question, answer, options, difficulty, age_min, age_max, ai_generated, source, imported_at, batch_id, question_hash, explanation, question_type) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'csv_import', datetime('now'), 'gen_v2_".date('Ymd')."', ?, ?, 'basic')");
    $stmt->bindValue(1, $mod); $stmt->bindValue(2, $q); $stmt->bindValue(3, $cor);
    $stmt->bindValue(4, $opt); $stmt->bindValue(5, $diff); $stmt->bindValue(6, $min);
    $stmt->bindValue(7, $max); $stmt->bindValue(8, $hash); $stmt->bindValue(9, $expl);
    if (@$stmt->execute()) { $hashes[$hash] = true; return 'ok'; }
    return 'err';
}

$stats = ['imported' => 0, 'dup' => 0, 'err' => 0];

function processModule($db, &$hashes, &$stats, $name, $icon, $questions) {
    $m = ['ok' => 0, 'dup' => 0];
    foreach ($questions as $q) {
        $r = importQ($db, $hashes, $name, $q[0], $q[1], $q[2], $q[3], $q[4], $q[5], $q[6], $q[7], $q[8]);
        if ($r === 'ok') { $stats['imported']++; $m['ok']++; }
        elseif ($r === 'dup') { $stats['dup']++; $m['dup']++; }
        else { $stats['err']++; }
    }
    echo "<div class='module'>$icon <b>".strtoupper($name)."</b>: <span class='success'>+{$m['ok']}</span>, <span class='skip'>{$m['dup']} skip</span></div>";
    flush();
}

// ============================================================================
// ERDKUNDE
// ============================================================================
$erdkunde = [
    ["Wie viele Kontinente gibt es?", "7", "5", "6", "8", "Europa, Asien, Afrika, Nord-/Suedamerika, Australien, Antarktis.", 2, 7, 10],
    ["Welcher Kontinent ist der groesste?", "Asien", "Afrika", "Europa", "Nordamerika", "Asien ist der groesste Kontinent.", 2, 7, 10],
    ["Auf welchem Kontinent liegt Deutschland?", "Europa", "Asien", "Afrika", "Nordamerika", "Deutschland liegt in Mitteleuropa.", 2, 7, 10],
    ["Wie viele Ozeane gibt es?", "5", "3", "4", "7", "Pazifik, Atlantik, Indischer, Arktischer, Suedlicher Ozean.", 2, 7, 10],
    ["Welcher Ozean ist der groesste?", "Pazifischer Ozean", "Atlantik", "Indischer Ozean", "Arktischer Ozean", "Der Pazifik bedeckt ein Drittel der Erde.", 2, 7, 10],
    ["Wie heisst die Hauptstadt von Deutschland?", "Berlin", "Muenchen", "Hamburg", "Frankfurt", "Berlin ist seit 1990 Hauptstadt.", 2, 8, 11],
    ["Wie viele Bundeslaender hat Deutschland?", "16", "14", "15", "17", "Deutschland hat 16 Bundeslaender.", 3, 9, 12],
    ["Was ist das groesste Bundesland?", "Bayern", "NRW", "Niedersachsen", "Baden-Wuerttemberg", "Bayern ist flaechenmaessig am groessten.", 3, 9, 12],
    ["An wie viele Laender grenzt Deutschland?", "9", "7", "8", "10", "9 Nachbarlaender.", 3, 9, 12],
    ["Welcher Berg ist der hoechste in Deutschland?", "Zugspitze", "Feldberg", "Brocken", "Watzmann", "Zugspitze mit 2962m.", 3, 9, 12],
    ["Was ist die Hauptstadt von Frankreich?", "Paris", "Lyon", "Marseille", "Nizza", "Paris ist Hauptstadt und groesste Stadt.", 3, 10, 13],
    ["Was ist die Hauptstadt von Italien?", "Rom", "Mailand", "Venedig", "Florenz", "Rom die ewige Stadt.", 3, 10, 13],
    ["Was ist die Hauptstadt von Spanien?", "Madrid", "Barcelona", "Sevilla", "Valencia", "Madrid liegt zentral.", 3, 10, 13],
    ["Was ist die Hauptstadt von Grossbritannien?", "London", "Manchester", "Liverpool", "Edinburgh", "London an der Themse.", 3, 10, 13],
    ["Was ist die Hauptstadt von Oesterreich?", "Wien", "Salzburg", "Graz", "Innsbruck", "Wien an der Donau.", 3, 10, 13],
    ["Was ist die Hauptstadt der Schweiz?", "Bern", "Zuerich", "Genf", "Basel", "Bern ist Bundeshauptstadt.", 3, 10, 13],
    ["Was ist die Hauptstadt von Polen?", "Warschau", "Krakau", "Danzig", "Breslau", "Warschau an der Weichsel.", 3, 10, 13],
    ["Wie viele Laender gehoeren zur EU 2024?", "27", "25", "28", "30", "Nach Brexit 27 Mitglieder.", 3, 10, 13],
    ["Welche Waehrung haben die meisten EU-Laender?", "Euro", "Dollar", "Pfund", "Krone", "Euro in 20 Laendern.", 3, 10, 13],
    ["Wie heisst der laengste Fluss der Welt?", "Nil", "Amazonas", "Jangtse", "Mississippi", "Nil ca. 6650 km.", 3, 10, 14],
    ["Wie heisst der hoechste Berg der Welt?", "Mount Everest", "K2", "Kangchendzoenga", "Mont Blanc", "Everest 8849m.", 3, 10, 14],
    ["Was ist die Hauptstadt der USA?", "Washington D.C.", "New York", "Los Angeles", "Chicago", "Washington seit 1800.", 3, 10, 14],
];
processModule($db, $existingHashes, $stats, 'erdkunde', '🌍', $erdkunde);

// ============================================================================
// BIOLOGIE
// ============================================================================
$biologie = [
    ["Wie viele Beine hat eine Spinne?", "8", "6", "4", "10", "Spinnen haben 8 Beine.", 1, 5, 8],
    ["Wie viele Beine hat ein Insekt?", "6", "4", "8", "2", "Insekten haben 6 Beine.", 1, 5, 8],
    ["Welches Tier gibt uns Milch?", "Kuh", "Huhn", "Schwein", "Schaf", "Kuehe geben Milch.", 1, 5, 8],
    ["Welches Tier legt Eier und kann fliegen?", "Huhn", "Kuh", "Schwein", "Hund", "Huehner legen Eier.", 1, 5, 8],
    ["Wie nennt man ein junges Pferd?", "Fohlen", "Kalb", "Lamm", "Welpe", "Fohlen = Pferdebaby.", 1, 5, 8],
    ["Wie nennt man ein junges Hund?", "Welpe", "Kaetzchen", "Fohlen", "Kalb", "Welpe = Hundebaby.", 1, 5, 8],
    ["Welches Tier macht Honig?", "Biene", "Wespe", "Hummel", "Fliege", "Bienen machen Honig.", 1, 5, 8],
    ["Welches Tier baut einen Damm?", "Biber", "Otter", "Ente", "Fisch", "Biber bauen Daemme.", 1, 5, 8],
    ["Wo lebt ein Fisch?", "Im Wasser", "In der Luft", "Unter der Erde", "Im Baum", "Fische leben im Wasser.", 1, 5, 8],
    ["Was brauchen Pflanzen zum Wachsen?", "Wasser, Licht und Erde", "Nur Wasser", "Nur Licht", "Nichts", "Wasser, Licht, Naehrstoffe.", 2, 7, 10],
    ["Wie heisst der gruene Farbstoff in Pflanzen?", "Chlorophyll", "Haemoglobin", "Melanin", "Carotin", "Chlorophyll fuer Fotosynthese.", 2, 7, 10],
    ["Was produzieren Pflanzen bei der Fotosynthese?", "Sauerstoff", "Kohlendioxid", "Stickstoff", "Wasserstoff", "Pflanzen produzieren O2.", 2, 7, 10],
    ["Welcher Teil der Pflanze nimmt Wasser auf?", "Wurzel", "Blatt", "Bluete", "Stamm", "Wurzeln nehmen Wasser auf.", 2, 7, 10],
    ["Wie viele Knochen hat ein Erwachsener?", "206", "100", "300", "150", "206 Knochen.", 3, 9, 13],
    ["Welches Organ pumpt das Blut?", "Herz", "Lunge", "Leber", "Niere", "Herz pumpt Blut.", 3, 9, 13],
    ["Womit atmen wir?", "Lunge", "Magen", "Herz", "Leber", "Lunge fuer Atmung.", 3, 9, 13],
    ["Welches Organ steuert unseren Koerper?", "Gehirn", "Herz", "Leber", "Magen", "Gehirn steuert alles.", 3, 9, 13],
    ["Wie viele Zaehne hat ein Erwachsener?", "32", "28", "20", "40", "32 Zaehne.", 3, 9, 13],
    ["Was ist DNA?", "Erbinformation in Zellen", "Eine Krankheit", "Ein Vitamin", "Ein Organ", "DNA = genetischer Bauplan.", 4, 13, 17],
    ["Was sind Chromosomen?", "Traeger der Gene", "Proteine", "Vitamine", "Hormone", "46 Chromosomen pro Zelle.", 4, 13, 17],
    ["Was ist Evolution?", "Entwicklung der Arten", "Eine Krankheit", "Ein Experiment", "Planetentheorie", "Darwin: natuerliche Selektion.", 4, 13, 17],
];
processModule($db, $existingHashes, $stats, 'biologie', '🧬', $biologie);

// ============================================================================
// GESCHICHTE
// ============================================================================
$geschichte = [
    ["In welcher Zeit lebten die Dinosaurier?", "Erdmittelalter", "Steinzeit", "Mittelalter", "Neuzeit", "Vor 230-66 Mio Jahren.", 2, 7, 10],
    ["Was ist ein Fossil?", "Versteinerte Ueberreste", "Lebendiges Tier", "Werkzeug", "Pflanze", "Fossilien zeigen frueheres Leben.", 2, 7, 10],
    ["Womit jagten Steinzeitmenschen?", "Mit Speeren und Pfeilen", "Mit Gewehren", "Mit Autos", "Mit Computern", "Werkzeuge aus Stein.", 2, 7, 10],
    ["Wo lebten die alten Roemer?", "In Italien", "In Deutschland", "In Amerika", "In China", "Rom = Zentrum des Reichs.", 2, 8, 11],
    ["Wer baute die Pyramiden?", "Die alten Aegypter", "Die Roemer", "Die Griechen", "Die Chinesen", "Grabstaetten fuer Pharaonen.", 2, 8, 11],
    ["Was erfanden die alten Griechen?", "Die Demokratie", "Das Auto", "Den Computer", "Das Flugzeug", "Erste Demokratie in Athen.", 3, 9, 12],
    ["Wer war Julius Caesar?", "Ein roemischer Feldherr", "Ein Philosoph", "Ein Pharao", "Ein Koenig", "Maechtigster Mann Roms.", 3, 10, 13],
    ["Wann war das Mittelalter?", "Etwa 500-1500 n.Chr.", "Vor 10000 Jahren", "Im 20. Jh.", "Vor 100 Jahren", "Ca. 1000 Jahre.", 3, 9, 12],
    ["Wer lebte in Burgen?", "Ritter und Adlige", "Dinosaurier", "Aerzte", "Lehrer", "Wohnsitze und Verteidigung.", 2, 8, 11],
    ["Was ist ein Ritter?", "Bewaffneter Krieger des Adels", "Koch", "Bauer", "Haendler", "Kaempfte zu Pferd.", 2, 8, 11],
    ["Wann fiel die Berliner Mauer?", "1989", "1961", "2000", "1945", "9. November 1989.", 3, 10, 14],
    ["Wann war die Deutsche Wiedervereinigung?", "1990", "1989", "1945", "2000", "3. Oktober 1990.", 3, 10, 14],
    ["Wann endete der Zweite Weltkrieg?", "1945", "1918", "1939", "1950", "8. Mai 1945.", 3, 11, 15],
    ["Wann begann der Erste Weltkrieg?", "1914", "1918", "1900", "1939", "Attentat von Sarajevo.", 3, 11, 15],
    ["Wer war der erste Bundeskanzler?", "Konrad Adenauer", "Helmut Kohl", "Willy Brandt", "Angela Merkel", "1949-1963.", 4, 12, 16],
    ["Wann entdeckte Kolumbus Amerika?", "1492", "1392", "1592", "1292", "12. Oktober 1492.", 3, 10, 14],
];
processModule($db, $existingHashes, $stats, 'geschichte', '📚', $geschichte);

// ============================================================================
// PHYSIK
// ============================================================================
$physik = [
    ["Was zieht alles nach unten?", "Die Schwerkraft", "Der Wind", "Das Wasser", "Die Sonne", "Schwerkraft zieht zur Erde.", 1, 5, 8],
    ["Was braucht man zum Sehen?", "Licht", "Wind", "Regen", "Schnee", "Ohne Licht nichts sehen.", 1, 5, 8],
    ["Was passiert wenn man Wasser kocht?", "Es verdampft", "Es wird zu Eis", "Es wird rot", "Es verschwindet", "Bei 100C = Dampf.", 2, 7, 10],
    ["Bei wie viel Grad gefriert Wasser?", "0 Grad C", "10 Grad", "100 Grad", "-10 Grad", "Bei 0 Grad = Eis.", 2, 7, 10],
    ["Woraus besteht elektrischer Strom?", "Bewegten Elektronen", "Wasser", "Luft", "Licht", "Fluss von Ladungen.", 3, 9, 12],
    ["Was leitet Strom gut?", "Metall", "Holz", "Plastik", "Gummi", "Kupfer leitet gut.", 3, 9, 12],
    ["Was ist Geschwindigkeit?", "Strecke pro Zeit", "Gewicht", "Groesse", "Farbe", "Weg geteilt durch Zeit.", 3, 9, 12],
    ["Was ist Reibung?", "Widerstand zwischen Flaechen", "Fluessigkeit", "Gas", "Licht", "Bremst Objekte ab.", 3, 10, 13],
    ["Was ist Energie?", "Faehigkeit Arbeit zu verrichten", "Farbe", "Geraeusch", "Geruch", "Kann nicht zerstoert werden.", 3, 10, 13],
    ["Was sind erneuerbare Energien?", "Wind, Sonne, Wasser", "Kohle, Oel, Gas", "Nur Atomkraft", "Nur Feuer", "Gehen nie aus.", 3, 10, 13],
    ["Was ist schneller: Licht oder Schall?", "Licht", "Schall", "Beide gleich", "Wasser", "Licht 1 Mio mal schneller.", 3, 9, 12],
    ["Warum Blitz vor Donner?", "Licht ist schneller als Schall", "Blitz lauter", "Donner heller", "Zufall", "3 Sek pro km fuer Schall.", 3, 9, 12],
    ["Was beschreibt Newtons Gesetz?", "Kraft und Bewegung", "Farben", "Toene", "Geschmack", "F = m mal a.", 4, 12, 16],
    ["Was ist kinetische Energie?", "Bewegungsenergie", "Waerme", "Licht", "Chemische Energie", "Bewegter Koerper hat sie.", 4, 12, 15],
];
processModule($db, $existingHashes, $stats, 'physik', '⚛️', $physik);

// ============================================================================
// CHEMIE
// ============================================================================
$chemie = [
    ["Woraus besteht Wasser?", "Wasserstoff und Sauerstoff", "Nur Sauerstoff", "Nur Wasserstoff", "Kohlenstoff", "H2O.", 2, 8, 11],
    ["Was ist ein Atom?", "Kleinster Baustein der Materie", "Planet", "Tier", "Pflanze", "Alles besteht aus Atomen.", 3, 9, 12],
    ["Was ist ein Molekuel?", "Verbindung aus Atomen", "Einzelnes Atom", "Element", "Gas", "Atome verbinden sich.", 3, 9, 12],
    ["Welches Gas atmen wir?", "Sauerstoff", "Stickstoff", "Kohlendioxid", "Helium", "O2 zum Leben.", 2, 7, 10],
    ["Was atmen wir aus?", "Kohlendioxid", "Sauerstoff", "Stickstoff", "Helium", "CO2.", 2, 8, 11],
    ["Was ist das Symbol fuer Gold?", "Au", "Go", "Gd", "Ag", "Aurum = Gold.", 3, 10, 13],
    ["Was ist das Symbol fuer Eisen?", "Fe", "Ei", "Ir", "Es", "Ferrum = Eisen.", 3, 10, 13],
    ["Was ist das Symbol fuer Silber?", "Ag", "Si", "Sl", "Sr", "Argentum = Silber.", 3, 10, 13],
    ["Was entsteht bei Rost?", "Eisenoxid", "Reines Eisen", "Gold", "Silber", "Eisen + Sauerstoff + Wasser.", 3, 10, 13],
    ["Was passiert beim Verbrennen?", "Reaktion mit Sauerstoff", "Kuehlung", "Nichts", "Mit Wasser", "Oxidation.", 3, 9, 12],
    ["Was ist der pH-Wert?", "Mass fuer Saeure oder Base", "Temperatur", "Gewicht", "Farbe", "pH 7 = neutral.", 4, 11, 14],
    ["Was sind die drei Aggregatzustaende?", "Fest, fluessig, gasfoermig", "Kalt, warm, heiss", "Gross, mittel, klein", "Hell, dunkel, grau", "Wasser kann alle 3.", 2, 8, 11],
];
processModule($db, $existingHashes, $stats, 'chemie', '⚗️', $chemie);

// ============================================================================
// ENGLISCH
// ============================================================================
$englisch = [
    ["Was heisst Hund auf Englisch?", "dog", "cat", "bird", "fish", "Dog = Hund.", 1, 5, 8],
    ["Was heisst Katze auf Englisch?", "cat", "dog", "mouse", "bird", "Cat = Katze.", 1, 5, 8],
    ["Was heisst Haus auf Englisch?", "house", "car", "tree", "book", "House = Haus.", 1, 5, 8],
    ["Was heisst Schule auf Englisch?", "school", "house", "car", "book", "School = Schule.", 1, 5, 8],
    ["Was heisst Buch auf Englisch?", "book", "pen", "table", "chair", "Book = Buch.", 1, 5, 8],
    ["Was heisst rot auf Englisch?", "red", "blue", "green", "yellow", "Red = rot.", 1, 5, 8],
    ["Was heisst blau auf Englisch?", "blue", "red", "green", "black", "Blue = blau.", 1, 5, 8],
    ["Was heisst gruen auf Englisch?", "green", "red", "blue", "white", "Green = gruen.", 1, 5, 8],
    ["Was heisst eins auf Englisch?", "one", "two", "three", "four", "One = eins.", 1, 5, 8],
    ["Was heisst zehn auf Englisch?", "ten", "five", "twenty", "hundred", "Ten = zehn.", 1, 5, 8],
    ["Was heisst hundert auf Englisch?", "hundred", "thousand", "ten", "fifty", "Hundred = hundert.", 2, 7, 9],
    ["Was heisst Hello how are you?", "Hallo wie geht es dir?", "Auf Wiedersehen!", "Guten Morgen!", "Danke!", "How are you = wie geht es dir.", 2, 7, 10],
    ["Was heisst Thank you?", "Danke", "Bitte", "Hallo", "Tschuess", "Thank you = Danke.", 1, 5, 8],
    ["Was heisst Good morning?", "Guten Morgen", "Gute Nacht", "Guten Tag", "Auf Wiedersehen", "Morning = Morgen.", 1, 5, 8],
    ["Was heisst I am hungry?", "Ich bin hungrig", "Ich bin muede", "Ich bin gluecklich", "Ich bin traurig", "Hungry = hungrig.", 2, 7, 10],
    ["Was ist die Vergangenheit von go?", "went", "goed", "goes", "going", "Go-went-gone.", 3, 9, 12],
    ["Was ist die Vergangenheit von eat?", "ate", "eated", "eating", "eats", "Eat-ate-eaten.", 3, 9, 12],
    ["Was ist die Vergangenheit von see?", "saw", "seed", "seeing", "sees", "See-saw-seen.", 3, 9, 12],
];
processModule($db, $existingHashes, $stats, 'englisch', '🇬🇧', $englisch);

// ============================================================================
// BITCOIN
// ============================================================================
$bitcoin = [
    ["Was ist Bitcoin?", "Digitales Geld", "Eine Bank", "Eine Firma", "Ein Spiel", "Dezentrale digitale Waehrung.", 2, 8, 12],
    ["Wer hat Bitcoin erfunden?", "Satoshi Nakamoto", "Elon Musk", "Bill Gates", "Mark Zuckerberg", "Satoshi = Pseudonym.", 3, 10, 14],
    ["Wann wurde Bitcoin erfunden?", "2009", "2000", "2015", "1999", "3. Januar 2009.", 3, 10, 14],
    ["Wie viele Bitcoin maximal?", "21 Millionen", "100 Millionen", "1 Milliarde", "Unendlich", "Limit im Code.", 3, 10, 14],
    ["Was ist ein Satoshi?", "Kleinste Bitcoin-Einheit", "Der Erfinder", "Eine Wallet", "Ein Computer", "1 BTC = 100 Mio Sats.", 3, 10, 14],
    ["Wie viele Satoshi sind 1 Bitcoin?", "100 Millionen", "1 Million", "1000", "1 Milliarde", "100.000.000 Sats.", 3, 10, 14],
    ["Was ist eine Blockchain?", "Kette von Datenbloecken", "Bank", "Computer", "Programm", "Alle Transaktionen gespeichert.", 4, 12, 16],
    ["Was macht ein Bitcoin-Miner?", "Bestaetigt Transaktionen", "Druckt Geld", "Baut Computer", "Schreibt Programme", "Loest Rechenaufgaben.", 4, 12, 16],
    ["Was ist Mining?", "Erstellen neuer Bloecke", "Kaufen von Bitcoin", "Verkaufen", "Speichern", "Miner werden belohnt.", 4, 12, 16],
    ["Was ist eine Bitcoin-Wallet?", "Digitale Geldboerse", "Echte Brieftasche", "Bank", "USB-Stick", "Speichert Private Keys.", 3, 10, 14],
    ["Was ist ein Private Key?", "Geheimer Schluessel", "Passwort", "Email", "Adresse", "Kontrolliert die Bitcoin.", 4, 12, 16],
    ["Warum Private Key nicht teilen?", "Andere koennten stehlen", "Geht kaputt", "Wird geloescht", "Wird ungueltig", "Wer den Key hat, hat die BTC.", 4, 12, 16],
    ["Was bedeutet Inflation?", "Geld verliert Kaufkraft", "Geld wird mehr wert", "Geld verschwindet", "Geld wird gedruckt", "Weniger kaufen koennen.", 3, 11, 15],
    ["Warum ist Bitcoin inflationsgeschuetzt?", "Begrenzte Menge", "Es ist digital", "Es ist neu", "Es ist geheim", "Max 21 Mio BTC.", 4, 12, 16],
    ["Was ist Fiat-Geld?", "Staatliches Papiergeld", "Goldmuenzen", "Digitales Geld", "Kreditkarten", "Euro, Dollar etc.", 4, 12, 16],
    ["Was ist ein Bitcoin-Halving?", "Block-Belohnung halbiert", "BTC geloescht", "Preis halbiert", "Miner halbiert", "Alle 4 Jahre.", 4, 13, 17],
];
processModule($db, $existingHashes, $stats, 'bitcoin', '₿', $bitcoin);

// ============================================================================
// LESEN
// ============================================================================
$lesen = [
    ["Mit welchem Buchstaben beginnt Apfel?", "A", "E", "P", "B", "Apfel beginnt mit A.", 1, 5, 7],
    ["Mit welchem Buchstaben beginnt Ball?", "B", "P", "A", "D", "Ball beginnt mit B.", 1, 5, 7],
    ["Mit welchem Buchstaben beginnt Hund?", "H", "N", "U", "D", "Hund beginnt mit H.", 1, 5, 7],
    ["Mit welchem Buchstaben endet Haus?", "S", "U", "A", "H", "Haus endet mit S.", 1, 5, 7],
    ["Wie viele Buchstaben hat das Alphabet?", "26", "24", "28", "30", "26 Buchstaben.", 1, 5, 7],
    ["Welcher Buchstabe ist ein Vokal?", "A", "B", "C", "D", "A, E, I, O, U sind Vokale.", 1, 6, 8],
    ["Welcher Buchstabe ist ein Konsonant?", "B", "A", "E", "I", "Alle ausser Vokale.", 2, 6, 8],
    ["Wie viele Vokale gibt es?", "5", "3", "8", "6", "A, E, I, O, U.", 2, 6, 8],
    ["Wie viele Silben hat Mama?", "2", "1", "4", "3", "Ma-ma = 2 Silben.", 1, 5, 7],
    ["Wie viele Silben hat Schmetterling?", "3", "4", "2", "5", "Schmet-ter-ling.", 2, 6, 9],
    ["Welches Wort reimt sich auf Maus?", "Haus", "Katze", "Vogel", "Hund", "Maus - Haus.", 1, 5, 8],
    ["Welches Wort reimt sich auf Baum?", "Traum", "Blume", "Wald", "Blatt", "Baum - Traum.", 1, 5, 8],
    ["Welches Zeichen am Ende eines Fragesatzes?", "?", "!", ".", ",", "Fragezeichen.", 2, 7, 9],
    ["Welches Zeichen am Ende eines Aussagesatzes?", ".", "!", "?", ",", "Punkt.", 2, 7, 9],
    ["Was ist ein Nomen?", "Namenwort wie Haus", "Tuwort", "Wiewort", "Bindewort", "Dinge, Menschen, Tiere.", 2, 7, 10],
    ["Was ist ein Verb?", "Tuwort wie laufen", "Zahlwort", "Namenwort", "Wiewort", "Beschreibt Taetigkeiten.", 2, 7, 10],
    ["Was ist ein Adjektiv?", "Wiewort wie gross", "Bindewort", "Tuwort", "Namenwort", "Beschreibt Eigenschaften.", 2, 7, 10],
    ["Was ist ein Maerchen?", "Fantastische Geschichte", "Sachbuch", "Kochbuch", "Zeitung", "Erfundene Geschichten mit Magie.", 2, 7, 10],
];
processModule($db, $existingHashes, $stats, 'lesen', '📖', $lesen);

// ============================================================================
// WISSENSCHAFT
// ============================================================================
$wissenschaft = [
    ["Was ist ein Experiment?", "Test um etwas herauszufinden", "Film", "Spiel", "Buch", "Hilft Fragen zu beantworten.", 2, 7, 10],
    ["Was macht ein Wissenschaftler?", "Erforscht die Welt", "Faehrt Taxi", "Verkauft Dinge", "Baut Haeuser", "Stellt Fragen und sucht Antworten.", 1, 5, 8],
    ["Was ist eine Hypothese?", "Vermutung die man testen kann", "Maerchen", "Luege", "Bewiesene Tatsache", "Wird durch Experimente geprueft.", 3, 10, 13],
    ["Warum ist der Himmel blau?", "Wegen Lichtstreuung", "Weil angemalt", "Wegen Wasser", "Wegen Wolken", "Blaues Licht wird mehr gestreut.", 3, 9, 12],
    ["Warum gibt es Tag und Nacht?", "Die Erde dreht sich", "Mond dreht sich", "Sonne dreht sich", "Magie", "Eine Umdrehung = 24h.", 2, 7, 10],
    ["Warum gibt es Jahreszeiten?", "Erdachse ist geneigt", "Mond aendert sich", "Sonne heisser/kaelter", "Erde groesser/kleiner", "Neigung = unterschiedlich Sonne.", 3, 9, 12],
    ["Wie entsteht ein Regenbogen?", "Licht wird im Wasser gebrochen", "Jemand malt ihn", "Faellt vom Himmel", "Kommt aus Erde", "Wassertropfen zerlegen Licht.", 2, 8, 11],
    ["Wie heisst unser Planet?", "Erde", "Venus", "Mond", "Mars", "Dritter Planet von der Sonne.", 1, 5, 8],
    ["Was ist die Sonne?", "Ein Stern", "Ein Planet", "Eine Lampe", "Ein Mond", "Riesiger Stern aus heissem Gas.", 2, 7, 10],
    ["Wie viele Planeten im Sonnensystem?", "8", "9", "7", "10", "8 seit Pluto kein Planet mehr.", 2, 8, 11],
    ["Was ist der Mond?", "Natuerlicher Satellit der Erde", "Ein Stern", "Eine Sonne", "Ein Planet", "Umkreist die Erde.", 2, 7, 10],
    ["Wie lange braucht die Erde um die Sonne?", "Ein Jahr (365 Tage)", "Ein Monat", "Ein Tag", "Eine Woche", "Vollstaendige Umrundung.", 2, 8, 11],
    ["Was ist ein Teleskop?", "Geraet zum Sterne beobachten", "Computer", "Kamera", "Telefon", "Ferne Objekte sehen.", 2, 7, 10],
    ["Was ist ein Mikroskop?", "Geraet zum Vergroessern kleiner Dinge", "Brille", "Fernseher", "Teleskop", "Zeigt winzige Strukturen.", 2, 7, 10],
];
processModule($db, $existingHashes, $stats, 'wissenschaft', '🔬', $wissenschaft);

// ============================================================================
// KUNST
// ============================================================================
$kunst = [
    ["Welche Farben sind Primaerfarben?", "Rot, Gelb, Blau", "Gruen, Orange, Lila", "Rosa, Braun, Tuerkis", "Schwarz, Weiss, Grau", "Daraus alle anderen mischen.", 1, 5, 8],
    ["Was entsteht wenn man Rot und Gelb mischt?", "Orange", "Gruen", "Lila", "Braun", "Rot + Gelb = Orange.", 1, 5, 8],
    ["Was entsteht wenn man Blau und Gelb mischt?", "Gruen", "Orange", "Lila", "Braun", "Blau + Gelb = Gruen.", 1, 5, 8],
    ["Was entsteht wenn man Rot und Blau mischt?", "Lila/Violett", "Braun", "Gruen", "Orange", "Rot + Blau = Violett.", 1, 5, 8],
    ["Was sind warme Farben?", "Rot, Orange, Gelb", "Blau, Gruen, Violett", "Schwarz, Weiss, Grau", "Alle Farben", "Erinnern an Sonne und Feuer.", 2, 7, 10],
    ["Was sind kalte Farben?", "Blau, Gruen, Violett", "Rot, Orange, Gelb", "Schwarz, Weiss, Grau", "Alle Farben", "Erinnern an Wasser und Eis.", 2, 7, 10],
    ["Was ist eine Collage?", "Bild aus geklebten Teilen", "Foto", "Skulptur", "Gemaelde", "Verschiedene Materialien zusammen.", 2, 7, 10],
    ["Was ist eine Skulptur?", "Dreidimensionales Kunstwerk", "Zeichnung", "Gemaelde", "Foto", "Von allen Seiten betrachten.", 2, 7, 10],
    ["Was ist ein Portrait?", "Bild von einer Person", "Landschaftsbild", "Tierbild", "Stillleben", "Zeigt meist das Gesicht.", 2, 7, 10],
    ["Was ist ein Stillleben?", "Bild von unbewegten Objekten", "Portrait", "Tierbild", "Landschaftsbild", "Oft Fruechte, Blumen, Gefaesse.", 3, 9, 12],
    ["Wer malte die Mona Lisa?", "Leonardo da Vinci", "Claude Monet", "Vincent van Gogh", "Pablo Picasso", "Haengt im Louvre.", 3, 9, 13],
    ["Wer malte die Sternennacht?", "Vincent van Gogh", "Claude Monet", "Leonardo da Vinci", "Pablo Picasso", "1889 gemalt.", 3, 10, 14],
    ["Womit malt man Aquarelle?", "Mit Wasserfarben", "Mit Buntstiften", "Mit Oelfarben", "Mit Kreide", "Mit Wasser verduennt.", 2, 7, 10],
];
processModule($db, $existingHashes, $stats, 'kunst', '🎨', $kunst);

// ============================================================================
// MUSIK
// ============================================================================
$musik = [
    ["Wie viele Noten in der Tonleiter?", "7", "5", "8", "12", "C, D, E, F, G, A, H.", 2, 7, 10],
    ["Was ist ein Takt?", "Rhythmische Einheit", "Lied", "Note", "Instrument", "Gliedert Musik.", 2, 8, 11],
    ["Was bedeutet forte?", "Laut spielen", "Schnell", "Langsam", "Leise", "f = laut.", 2, 8, 11],
    ["Was bedeutet piano in der Musik?", "Leise spielen", "Laut", "Das Klavier", "Schnell", "p = leise.", 2, 8, 11],
    ["Welches Instrument hat 88 Tasten?", "Klavier", "Floete", "Geige", "Gitarre", "Konzertfluegel.", 2, 7, 10],
    ["Welches Instrument hat 6 Saiten?", "Gitarre", "Geige", "Floete", "Klavier", "Standard-Gitarre.", 2, 7, 10],
    ["Welches Instrument ist ein Streichinstrument?", "Geige", "Trommel", "Floete", "Trompete", "Mit Bogen gespielt.", 2, 7, 10],
    ["Welches Instrument ist ein Blasinstrument?", "Trompete", "Geige", "Gitarre", "Schlagzeug", "Toene durch Luft.", 2, 7, 10],
    ["Was ist ein Schlagzeug?", "Sammlung von Trommeln und Becken", "Floete", "Klavier", "Gitarre", "Gibt Rhythmus vor.", 1, 6, 9],
    ["Wer komponierte die Mondscheinsonate?", "Ludwig van Beethoven", "Mozart", "Bach", "Schubert", "1801 komponiert.", 3, 10, 14],
    ["Wer komponierte Die kleine Nachtmusik?", "Wolfgang Amadeus Mozart", "Beethoven", "Bach", "Haydn", "1787 komponiert.", 3, 10, 14],
    ["In welcher Stadt wurde Mozart geboren?", "Salzburg", "Wien", "Berlin", "Muenchen", "1756 in Salzburg.", 3, 10, 14],
    ["Was ist eine Oktave?", "Abstand von 8 Toenen", "Lied", "Instrument", "Takt", "8 Tonstufen.", 3, 10, 13],
    ["Was ist ein Akkord?", "Mehrere Toene gleichzeitig", "Ein Ton", "Rhythmus", "Pause", "Mind. 3 Toene.", 3, 10, 13],
];
processModule($db, $existingHashes, $stats, 'musik', '🎵', $musik);

// ============================================================================
// COMPUTER
// ============================================================================
$computer = [
    ["Was ist ein Computer?", "Elektronische Rechenmaschine", "Fernseher", "Buch", "Spielzeug", "Speichert und verarbeitet Daten.", 1, 5, 8],
    ["Was ist eine Tastatur?", "Eingabegeraet mit Tasten", "Drucker", "Maus", "Bildschirm", "Text eingeben.", 1, 5, 8],
    ["Was ist eine Maus?", "Eingabegeraet zum Zeigen", "Tastatur", "Tier", "Bildschirm", "Bewegt den Zeiger.", 1, 5, 8],
    ["Was zeigt der Monitor?", "Das Bild vom Computer", "Toene", "Papier", "Spiele", "Bildschirm.", 1, 5, 8],
    ["Was ist Hardware?", "Physische Teile des Computers", "Internet", "Programme", "Spiele", "Kann man anfassen.", 2, 7, 10],
    ["Was ist Software?", "Programme und Apps", "Tastatur", "Bildschirm", "Gehaeuse", "Anweisungen fuer Computer.", 2, 7, 10],
    ["Was macht die CPU?", "Gehirn des Computers", "Speichert Daten", "Druckt", "Zeigt Bilder", "Central Processing Unit.", 3, 9, 12],
    ["Was ist RAM?", "Arbeitsspeicher", "Prozessor", "Bildschirm", "Festplatte", "Temporaerer Speicher.", 3, 9, 12],
    ["Wofuer ist die Festplatte?", "Dauerhaftes Speichern", "Drucken", "Anzeigen", "Rechnen", "Behaelt Daten ohne Strom.", 3, 9, 12],
    ["Was ist das Internet?", "Weltweites Computernetzwerk", "Programm", "Spiel", "Einzelner Computer", "Milliarden Computer verbunden.", 2, 8, 11],
    ["Was ist eine Webseite?", "Seite im Internet", "Programm", "Buch", "E-Mail", "Mit Browser ansehen.", 2, 8, 11],
    ["Was ist ein Browser?", "Programm zum Surfen", "Suchmaschine", "Virus", "Computerspiel", "Chrome, Firefox, Safari.", 2, 8, 11],
    ["Was ist eine Suchmaschine?", "Dienst zum Finden von Webseiten", "Motor", "Browser", "Computer", "Google, Bing.", 2, 8, 11],
    ["Was ist WLAN?", "Drahtloses Internet", "Kabel", "Computer", "Programm", "Ohne Kabel.", 2, 8, 11],
    ["Warum braucht man ein Passwort?", "Schutz vor unbefugtem Zugriff", "Bessere Bilder", "Mehr Speicher", "Schneller", "Schuetzt Daten.", 2, 8, 11],
    ["Was ist ein Computervirus?", "Schaedliches Programm", "Fehler", "Update", "Krankheit", "Kann Schaden anrichten.", 3, 9, 12],
];
processModule($db, $existingHashes, $stats, 'computer', '💻', $computer);

// ============================================================================
// PROGRAMMIEREN
// ============================================================================
$programmieren = [
    ["Was ist Programmieren?", "Anweisungen fuer Computer schreiben", "Spiele spielen", "Surfen", "Reparieren", "Sagt was Computer tun soll.", 2, 8, 11],
    ["Was ist ein Algorithmus?", "Schritt-fuer-Schritt-Anleitung", "Webseite", "Spiel", "Computer", "Loest Probleme in Schritten.", 3, 9, 12],
    ["Was ist ein Bug?", "Fehler im Programm", "Insekt", "Feature", "Update", "Muss behoben werden.", 2, 8, 11],
    ["Was bedeutet Debugging?", "Fehler finden und beheben", "Features hinzufuegen", "Starten", "Loeschen", "Wichtiger Teil der Entwicklung.", 3, 9, 12],
    ["Was ist eine Variable?", "Speicherplatz fuer Werte", "Fehler", "Webseite", "Programm", "Speichert Daten.", 3, 9, 12],
    ["Was ist eine Schleife?", "Code der sich wiederholt", "Kommentar", "Variable", "Fehler", "Fuehrt Code mehrfach aus.", 3, 10, 13],
    ["Was ist eine Bedingung (if)?", "Code bei bestimmter Situation", "Fehler", "Immer ausgefuehrt", "Schleife", "Prueft ob etwas wahr ist.", 3, 10, 13],
    ["Was ist eine Funktion?", "Wiederverwendbarer Codeblock", "Datei", "Variable", "Fehler", "Fasst Code zusammen.", 3, 10, 13],
    ["Welche Sprache fuer Einsteiger?", "Python", "C++", "Assembler", "Maschinencode", "Einfach und vielseitig.", 3, 10, 13],
    ["Womit werden Webseiten gestaltet?", "HTML und CSS", "Java", "Python", "C++", "HTML strukturiert, CSS gestaltet.", 3, 10, 13],
    ["Was ist JavaScript?", "Sprache fuer interaktive Webseiten", "Kaffeesorte", "Computer", "Browser", "Macht Webseiten dynamisch.", 3, 11, 14],
    ["Was bedeutet print() in Python?", "Text ausgeben", "Bild malen", "Speichern", "Drucken auf Papier", "Zeigt Text an.", 3, 10, 13],
    ["Was ist Quellcode?", "Text eines Programms", "Datei", "Passwort", "Geheimcode", "Anweisungen in Programmiersprache.", 3, 10, 13],
    ["Was ist Open Source?", "Frei zugaenglicher Quellcode", "Bezahlte Software", "Geheimer Code", "Illegale Software", "Jeder kann sehen und verbessern.", 4, 12, 16],
];
processModule($db, $existingHashes, $stats, 'programmieren', '👨‍💻', $programmieren);

// ============================================================================
// STEUERN / FINANZEN
// ============================================================================
$steuern = [
    ["Was ist Geld?", "Tauschmittel", "Spielzeug", "Nur Metall", "Nur Papier", "Kauft Waren und Dienstleistungen.", 1, 5, 8],
    ["Woher bekommt man Geld?", "Durch Arbeit oder Geschenke", "Vom Himmel", "Aus dem Drucker", "Von Baeumen", "Meist durch Arbeit.", 1, 5, 8],
    ["Was ist Sparen?", "Geld fuer spaeter aufbewahren", "Wegwerfen", "Ausgeben", "Verstecken", "Spaeter groessere Dinge kaufen.", 1, 5, 8],
    ["Was ist ein Sparschwein?", "Behaelter zum Geld sammeln", "Bank", "Echtes Schwein", "Laden", "Muenzen sammeln.", 1, 5, 8],
    ["Was ist ein Preis?", "Was etwas kostet", "Name des Produkts", "Groesse", "Farbe", "Zeigt wie viel zahlen.", 1, 6, 9],
    ["Was ist ein Konto?", "Ort wo Bank Geld aufbewahrt", "Schublade", "Tresor zu Hause", "Tasche", "Geld sicher bei Bank.", 2, 7, 10],
    ["Was ist ein Gehalt?", "Geld fuer Arbeit", "Geschenk", "Kredit", "Strafe", "Monatlich gezahlt.", 2, 8, 11],
    ["Was ist Taschengeld?", "Geld das Kinder regelmaessig bekommen", "Geld in Tasche", "Gehalt", "Geschenk", "Umgang mit Geld lernen.", 1, 6, 9],
    ["Was sind Steuern?", "Geld an den Staat", "Gehalt", "Taschengeld", "Geschenke", "Finanziert Schulen, Strassen.", 3, 10, 13],
    ["Was ist die Mehrwertsteuer?", "Steuer auf gekaufte Waren", "Hundesteuer", "Einkommenssteuer", "Autosteuer", "19% oder 7%.", 3, 10, 13],
    ["Wie hoch ist die normale MwSt in DE?", "19%", "25%", "10%", "7%", "7% fuer Lebensmittel.", 3, 11, 14],
    ["Was ist ein Budget?", "Plan fuer Einnahmen und Ausgaben", "Steuer", "Sparkonto", "Kredit", "Hilft Geld einteilen.", 3, 10, 13],
    ["Was ist ein Kredit?", "Geliehenes Geld zurueckzahlen", "Geschenk", "Gespartes Geld", "Verdientes Geld", "Zahlt man Zinsen.", 3, 11, 14],
    ["Was sind Zinsen?", "Gebuehr fuer geliehenes Geld", "Gehalt", "Steuern", "Geschenk", "Preis fuers Leihen.", 3, 11, 14],
    ["Was ist eine Aktie?", "Anteil an einem Unternehmen", "Rechnung", "Steuer", "Kredit", "Aktionaer = Miteigentuemer.", 4, 13, 17],
    ["Was ist die Boerse?", "Markt fuer Wertpapiere", "Supermarkt", "Laden", "Bank", "Aktien werden gehandelt.", 4, 13, 17],
    ["Was bedeutet Inflation?", "Geld verliert Kaufkraft", "Steuern steigen", "Gehaelter sinken", "Geld wird mehr wert", "Weniger kaufen koennen.", 4, 12, 16],
];
processModule($db, $existingHashes, $stats, 'steuern', '💰', $steuern);

// ============================================================================
// ERGEBNIS
// ============================================================================
echo "<div class='stats' style='margin-top:20px; font-size:1.2em;'>";
echo "═══════════════════════════════════════<br>";
echo "📊 <b>ERGEBNIS</b><br>";
echo "═══════════════════════════════════════<br>";
echo "✅ Importiert: <b style='color:#43D240;'>" . $stats['imported'] . "</b><br>";
echo "⏭️ Duplikate: " . $stats['dup'] . "<br>";
echo "❌ Fehler: " . $stats['err'] . "<br><br>";

$result = $db->query("SELECT COUNT(*) FROM questions");
$total = $result->fetchArray()[0];
echo "📈 <b>DATENBANK GESAMT: $total Fragen</b><br>";
echo "</div>";

// Module-Stats
echo "<div class='stats'><b>📋 Fragen pro Modul:</b><br>";
$result = $db->query("SELECT module, COUNT(*) as cnt FROM questions GROUP BY module ORDER BY cnt DESC");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo sprintf("%-15s: %d<br>", $row['module'], $row['cnt']);
}
echo "</div>";

$db->close();

echo "<p><a href='admin_v4.php' style='color:#43D240;font-size:1.2em;'>← Zurück zum Admin</a></p>";
echo "</body></html>";
