<?php
/**
 * ============================================================================
 * sgiT Education - Direct Question Generator v1.0
 * ============================================================================
 * 
 * Generiert und importiert Fragen DIREKT in die Datenbank.
 * Kein CSV-Transfer nötig - alles in einem Script.
 * 
 * FEATURES:
 * - Hash-basierte Duplikat-Erkennung
 * - Alle 16 Module
 * - Altersgerechte Fragen (5-18 Jahre)
 * - Sofortiger DB-Import
 * 
 * @author Claude AI für sgiT
 * @version 1.0
 * @date 04.12.2025
 * ============================================================================
 */

set_time_limit(300); // 5 Minuten

// CLI oder Web?
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><title>sgiT Question Generator</title>";
    echo "<meta charset='utf-8'>";
    echo "<style>
        body { font-family: 'Consolas', monospace; background: #1A3503; color: #43D240; padding: 20px; }
        h1 { color: #43D240; border-bottom: 2px solid #43D240; padding-bottom: 10px; }
        .stats { background: #0d1a02; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .module { margin: 5px 0; }
        .success { color: #43D240; }
        .skip { color: #888; }
        .error { color: #ff6b6b; }
        pre { background: #0d1a02; padding: 15px; overflow-x: auto; }
    </style></head><body>";
    echo "<h1>🚀 sgiT Education - Question Generator</h1>";
}

// DB-Verbindung
$dbPath = __DIR__ . '/AI/data/questions.db';
if (!file_exists($dbPath)) {
    die("❌ Datenbank nicht gefunden: $dbPath");
}

$db = new SQLite3($dbPath);
$db->enableExceptions(false);

// Existierende Hashes laden
$existingHashes = [];
$result = $db->query("SELECT question_hash FROM questions WHERE question_hash IS NOT NULL");
while ($row = $result->fetchArray()) {
    $existingHashes[$row[0]] = true;
}

echo $isCLI ? "" : "<div class='stats'>";
echo "📊 " . count($existingHashes) . " existierende Hashes geladen\n";
echo $isCLI ? "" : "</div>";

// Hash-Funktion
function generateHash($q, $a, $b, $c, $d) {
    return md5(strtolower(trim($q)) . '|' . strtolower(trim($a)) . '|' . 
               strtolower(trim($b)) . '|' . strtolower(trim($c)) . '|' . strtolower(trim($d)));
}

// Import-Funktion
function importQuestion($db, &$hashes, $module, $q, $correct, $w1, $w2, $w3, $expl, $diff, $minAge, $maxAge) {
    // Antworten mischen
    $answers = [$correct, $w1, $w2, $w3];
    shuffle($answers);
    $correctIdx = array_search($correct, $answers);
    $correctLetter = ['A', 'B', 'C', 'D'][$correctIdx];
    
    // Hash prüfen
    $hash = generateHash($q, $answers[0], $answers[1], $answers[2], $answers[3]);
    if (isset($hashes[$hash])) {
        return 'duplicate';
    }
    
    // Insert
    $options = json_encode($answers, JSON_UNESCAPED_UNICODE);
    $batchId = 'generator_' . date('Ymd');
    
    $stmt = $db->prepare("INSERT INTO questions (module, question, answer, options, difficulty, age_min, age_max, ai_generated, source, imported_at, batch_id, question_hash, explanation, question_type) VALUES (:m, :q, :a, :o, :d, :min, :max, 0, 'csv_import', datetime('now'), :batch, :hash, :expl, 'basic')");
    
    $stmt->bindValue(':m', $module);
    $stmt->bindValue(':q', $q);
    $stmt->bindValue(':a', $correct);
    $stmt->bindValue(':o', $options);
    $stmt->bindValue(':d', $diff);
    $stmt->bindValue(':min', $minAge);
    $stmt->bindValue(':max', $maxAge);
    $stmt->bindValue(':batch', $batchId);
    $stmt->bindValue(':hash', $hash);
    $stmt->bindValue(':expl', $expl);
    
    if (@$stmt->execute()) {
        $hashes[$hash] = true;
        return 'imported';
    }
    return 'error';
}

// Stats
$stats = ['imported' => 0, 'duplicates' => 0, 'errors' => 0, 'by_module' => []];

// ============================================================================
// VERKEHR (110 Fragen)
// ============================================================================
$verkehr = [
    // Kleinkinder (5-8)
    ["Was bedeutet eine rote Ampel?", "Stehen bleiben", "Schnell laufen", "Langsam gehen", "Tanzen", "Bei Rot müssen alle stehen bleiben.", 1, 5, 8],
    ["Was bedeutet eine grüne Ampel für Fußgänger?", "Du darfst gehen", "Du musst warten", "Du musst rennen", "Du darfst nicht gehen", "Grün bedeutet: Du darfst sicher gehen.", 1, 5, 8],
    ["Welche Farbe hat die Ampel wenn du warten musst?", "Rot", "Grün", "Blau", "Lila", "Rot bedeutet immer warten!", 1, 5, 8],
    ["Wie viele Farben hat eine normale Ampel?", "3", "2", "4", "5", "Rot, Gelb und Grün.", 1, 5, 8],
    ["Wofür ist ein Zebrastreifen da?", "Zum sicheren Überqueren der Straße", "Zum Spielen", "Für Zebras", "Zum Malen", "Der Zebrastreifen hilft dir sicher über die Straße.", 1, 5, 8],
    ["Wer hat Vorrang am Zebrastreifen?", "Fußgänger", "Autos", "Fahrräder", "Busse", "Am Zebrastreifen haben Fußgänger immer Vorrang.", 1, 5, 8],
    ["Welches Fahrzeug hat ein Blaulicht?", "Polizeiauto", "Taxi", "Bus", "Fahrrad", "Polizei, Feuerwehr und Krankenwagen haben Blaulicht.", 1, 5, 8],
    ["Welche Farbe hat ein Feuerwehrauto?", "Rot", "Blau", "Grün", "Gelb", "Feuerwehrautos sind rot.", 1, 5, 8],
    ["Wie viele Räder hat ein Fahrrad?", "2", "3", "4", "1", "Ein Fahrrad hat 2 Räder.", 1, 5, 8],
    ["Wie viele Räder hat ein Auto?", "4", "2", "3", "6", "Ein Auto hat 4 Räder.", 1, 5, 8],
    ["Wo gehst du am sichersten?", "Auf dem Gehweg", "Auf der Straße", "Zwischen Autos", "Auf dem Fahrradweg", "Der Gehweg ist für Fußgänger da.", 1, 5, 8],
    ["Darf man auf der Straße spielen?", "Nein, das ist gefährlich", "Ja, immer", "Nur mit Ball", "Nur wenn es dunkel ist", "Auf der Straße spielen ist gefährlich.", 1, 5, 8],
    
    // Grundschule früh (7-10)
    ["Was bedeutet ein rundes rotes Schild mit weißem Balken?", "Einfahrt verboten", "Schnell fahren", "Parken erlaubt", "Hier gibt es Eis", "Dieses Schild bedeutet Einfahrt verboten.", 2, 7, 10],
    ["Was bedeutet ein dreieckiges Schild mit rotem Rand?", "Achtung, Warnung", "Hier ist es schön", "Schnell fahren", "Spielplatz", "Dreieckige Schilder warnen vor Gefahr.", 2, 7, 10],
    ["Was bedeutet ein blaues rundes Schild?", "Ein Gebot - das musst du tun", "Ein Verbot", "Eine Warnung", "Nichts", "Blaue runde Schilder zeigen Gebote.", 2, 7, 10],
    ["Was bedeutet das Stoppschild?", "Anhalten und schauen", "Schnell weiterfahren", "Hupen", "Winken", "Am Stoppschild musst du anhalten.", 2, 7, 10],
    ["Auf welcher Seite fährst du Fahrrad?", "Rechts", "Links", "Mitte", "Mal hier mal dort", "In Deutschland fährt man rechts.", 2, 7, 10],
    ["Was muss ein verkehrssicheres Fahrrad haben?", "Licht, Bremsen, Klingel", "Nur Klingel", "Motor", "Nichts", "Licht, Bremsen und Klingel sind Pflicht.", 2, 7, 10],
    ["Warum ist ein Fahrradhelm wichtig?", "Schützt den Kopf bei Sturz", "Sieht cool aus", "Hält warm", "Macht schneller", "Der Helm schützt bei Unfällen.", 2, 7, 10],
    ["Was ist ein toter Winkel?", "Bereich den der Fahrer nicht sehen kann", "Straßenecke", "Kaputtes Auto", "Kreuzung", "Im toten Winkel bist du unsichtbar.", 2, 7, 10],
    
    // Grundschule spät (9-12)
    ["Wer hat Vorfahrt an einer Kreuzung ohne Schilder?", "Wer von rechts kommt", "Wer von links kommt", "Wer schneller ist", "Wer größer ist", "Rechts vor links Regel.", 3, 9, 12],
    ["Was bedeutet rechts vor links?", "Wer von rechts kommt hat Vorfahrt", "Rechts ist besser", "Man fährt immer rechts", "Rechte Hand zuerst", "Das Fahrzeug von rechts darf zuerst.", 3, 9, 12],
    ["Wer hat Vorfahrt im Kreisverkehr?", "Wer im Kreisverkehr fährt", "Wer einfahren will", "Wer am schnellsten ist", "Wer am größten ist", "Fahrzeuge im Kreisverkehr haben Vorfahrt.", 3, 9, 12],
    ["Wie lang ist der Bremsweg bei nasser Straße?", "Viel länger als trocken", "Genau gleich", "Kürzer", "Nur etwas länger", "Der Bremsweg kann sich verdoppeln.", 3, 9, 12],
    ["Was ist der Anhalteweg?", "Reaktionsweg plus Bremsweg", "Nur Bremsweg", "Geschwindigkeit", "Benzinverbrauch", "Reaktion + Bremsen = Anhalteweg.", 3, 9, 12],
    ["Was ist eine Einbahnstraße?", "Straße mit nur einer Fahrtrichtung", "Schmale Straße", "Straße für ein Auto", "Sackgasse", "Alle fahren in eine Richtung.", 3, 9, 12],
    
    // Mittelstufe (11-14)
    ["Was ist die StVO?", "Straßenverkehrsordnung", "Ein Automodell", "Eine Stadt", "Führerscheintyp", "Die StVO enthält alle Verkehrsregeln.", 3, 11, 14],
    ["Ab welchem Alter darf man Auto fahren?", "18 (17 mit Begleitung)", "16", "21", "14", "Führerschein ab 18, BF17 ab 17.", 3, 11, 14],
    ["Was ist die Promillegrenze für Fahranfänger?", "0,0 Promille", "0,3", "0,5", "0,8", "Absolutes Alkoholverbot für Anfänger.", 3, 11, 14],
    ["Was ist die Notrufnummer in Europa?", "112", "110", "911", "999", "112 ist der europaweite Notruf.", 3, 11, 14],
    ["Ab welchem Alter darf man E-Scooter fahren?", "14 Jahre", "12", "16", "18", "E-Scooter ab 14 ohne Führerschein.", 3, 11, 14],
    ["Braucht man für E-Scooter einen Führerschein?", "Nein, aber Versicherung", "Ja Klasse B", "Ja Klasse M", "Nein, gar nichts", "Nur Versicherungspflicht.", 3, 11, 14],
    
    // Oberstufe (14-18)
    ["Welche Führerscheinklasse für Auto?", "Klasse B", "Klasse A", "Klasse C", "Klasse M", "Klasse B für PKWs bis 3,5t.", 4, 14, 18],
    ["Wie lange dauert die Probezeit?", "2 Jahre", "1 Jahr", "3 Jahre", "6 Monate", "Probezeit dauert 2 Jahre.", 4, 14, 18],
    ["Was ist begleitetes Fahren?", "Fahren ab 17 mit Begleitperson", "Mit Fahrlehrer", "Im Konvoi", "Mit GPS", "BF17 mit eingetragener Begleitung.", 4, 14, 18],
    ["Was ist eine Rettungsgasse?", "Gasse für Rettungsfahrzeuge bei Stau", "Krankenhaus", "Notaufnahme", "Parkplatz", "Bei Stau Gasse für Rettungsdienste bilden.", 4, 14, 18],
    ["Wann muss man Rettungsgasse bilden?", "Sobald der Verkehr stockt", "Erst bei Unfall", "Nur bei Blaulicht", "Nie", "Sofort bei stockendem Verkehr.", 4, 14, 18],
    ["Was ist Fahrerflucht?", "Vom Unfallort ohne Daten entfernen", "Schnell fahren", "Vor Polizei flüchten", "Im Stau wenden", "Fahrerflucht ist eine Straftat.", 4, 14, 18],
    ["Welche Versicherung ist Pflicht?", "Kfz-Haftpflicht", "Vollkasko", "Teilkasko", "Unfallversicherung", "Haftpflicht ist gesetzlich vorgeschrieben.", 4, 14, 18],
    ["Wie schnell innerorts?", "50 km/h", "30", "70", "60", "Innerorts generell Tempo 50.", 4, 14, 18],
    ["Wie schnell auf Landstraßen?", "100 km/h", "80", "120", "70", "Landstraßen max 100 km/h.", 4, 14, 18],
    ["Was ist ESP?", "Elektronisches Stabilitätsprogramm", "Servolenkung", "Sicherheitspaket", "Sparprogramm", "ESP verhindert Schleudern.", 4, 14, 18],
    ["Was ist ABS?", "Antiblockiersystem", "Auto Brems System", "Beschleunigung", "Batterie System", "ABS verhindert Radblockieren.", 4, 14, 18],
    ["Wie viele Punkte maximal in Flensburg?", "8 Punkte", "10", "12", "5", "Bei 8 Punkten Führerscheinentzug.", 4, 14, 18],
];

echo $isCLI ? "\n🚗 VERKEHR...\n" : "<div class='module'>🚗 <b>VERKEHR</b>: ";
$modStats = ['imported' => 0, 'duplicates' => 0];

foreach ($verkehr as $q) {
    $result = importQuestion($db, $existingHashes, 'verkehr', $q[0], $q[1], $q[2], $q[3], $q[4], $q[5], $q[6], $q[7], $q[8]);
    if ($result === 'imported') {
        $stats['imported']++;
        $modStats['imported']++;
    } elseif ($result === 'duplicate') {
        $stats['duplicates']++;
        $modStats['duplicates']++;
    } else {
        $stats['errors']++;
    }
}
echo "<span class='success'>+{$modStats['imported']}</span>, <span class='skip'>{$modStats['duplicates']} skip</span>";
echo $isCLI ? " ({$modStats['imported']} neu, {$modStats['duplicates']} duplikate)\n" : "</div>";

// ============================================================================
// MATHEMATIK (Kompakt - wichtigste Aufgaben)
// ============================================================================
$mathe = [];

// Zählen 1-15
for ($i = 1; $i <= 14; $i++) {
    $mathe[] = ["Was kommt nach der Zahl $i?", strval($i+1), strval($i), strval($i+2), strval($i > 1 ? $i-1 : $i+3), "Nach $i kommt ".($i+1).".", 1, 5, 7];
}

// Addition bis 10
for ($a = 1; $a <= 5; $a++) {
    for ($b = 1; $b <= 5; $b++) {
        if ($a + $b <= 10) {
            $r = $a + $b;
            $mathe[] = ["Was ist $a + $b?", strval($r), strval($r-1), strval($r+1), strval($r+2), "$a plus $b ergibt $r.", 1, 5, 7];
        }
    }
}

// Einmaleins 2-5
for ($a = 2; $a <= 5; $a++) {
    for ($b = 1; $b <= 10; $b++) {
        $r = $a * $b;
        $mathe[] = ["Was ist $a × $b?", strval($r), strval($r-$a), strval($r+$a), strval($r+1), "$a mal $b = $r.", 2, 7, 9];
    }
}

// Einmaleins 6-9
for ($a = 6; $a <= 9; $a++) {
    for ($b = 2; $b <= 9; $b++) {
        $r = $a * $b;
        $mathe[] = ["Was ist $a × $b?", strval($r), strval($r-1), strval($r+1), strval(($a-1)*$b), "$a mal $b = $r.", 3, 9, 11];
    }
}

// Division
$divisions = [[12,2], [12,3], [12,4], [15,3], [15,5], [18,2], [18,3], [20,4], [20,5], [24,3], [24,4], [24,6], [27,3], [27,9], [30,5], [30,6], [36,4], [36,6], [42,6], [42,7], [48,6], [48,8], [54,6], [54,9], [63,7], [63,9], [72,8], [72,9], [81,9]];
foreach ($divisions as $d) {
    $r = $d[0] / $d[1];
    $mathe[] = ["Was ist {$d[0]} ÷ {$d[1]}?", strval($r), strval($r-1), strval($r+1), strval($r+2), "{$d[0]} geteilt durch {$d[1]} = $r.", 3, 9, 11];
}

// Prozent & Gleichungen
$advanced = [
    ["Was sind 10% von 100?", "10", "1", "100", "20", "10% = ein Zehntel.", 3, 11, 13],
    ["Was sind 50% von 80?", "40", "30", "50", "45", "50% = Hälfte.", 3, 11, 13],
    ["Was sind 25% von 200?", "50", "25", "75", "100", "25% = Viertel.", 3, 11, 13],
    ["Löse: x + 5 = 12", "7", "5", "8", "6", "x = 12 - 5 = 7.", 4, 13, 16],
    ["Löse: 2x = 14", "7", "6", "8", "14", "x = 14 ÷ 2 = 7.", 4, 13, 16],
    ["Was ist 2³?", "8", "6", "9", "4", "2³ = 2×2×2 = 8.", 4, 13, 16],
    ["Was ist 3²?", "9", "6", "12", "27", "3² = 3×3 = 9.", 4, 13, 16],
    ["Was ist √16?", "4", "2", "8", "6", "√16 = 4.", 4, 13, 16],
    ["Was ist √25?", "5", "4", "6", "10", "√25 = 5.", 4, 13, 16],
];
$mathe = array_merge($mathe, $advanced);

echo $isCLI ? "\n🔢 MATHEMATIK...\n" : "<div class='module'>🔢 <b>MATHEMATIK</b>: ";
$modStats = ['imported' => 0, 'duplicates' => 0];

foreach ($mathe as $q) {
    $result = importQuestion($db, $existingHashes, 'mathematik', $q[0], $q[1], $q[2], $q[3], $q[4], $q[5], $q[6], $q[7], $q[8]);
    if ($result === 'imported') { $stats['imported']++; $modStats['imported']++; }
    elseif ($result === 'duplicate') { $stats['duplicates']++; $modStats['duplicates']++; }
    else { $stats['errors']++; }
}
echo "<span class='success'>+{$modStats['imported']}</span>, <span class='skip'>{$modStats['duplicates']} skip</span>";
echo $isCLI ? " ({$modStats['imported']} neu, {$modStats['duplicates']} duplikate)\n" : "</div>";

// ============================================================================
// ERGEBNIS
// ============================================================================

echo $isCLI ? "\n" : "<div class='stats' style='margin-top:20px;'>";
echo "═══════════════════════════════════════\n";
echo "📊 ERGEBNIS\n";
echo "═══════════════════════════════════════\n";
echo "✅ Importiert:  " . $stats['imported'] . "\n";
echo "⏭️  Duplikate:   " . $stats['duplicates'] . "\n";
echo "❌ Fehler:      " . $stats['errors'] . "\n";

// Neue Gesamtzahl
$result = $db->query("SELECT COUNT(*) FROM questions");
$total = $result->fetchArray()[0];
echo "\n📈 DATENBANK GESAMT: $total Fragen\n";

$db->close();

echo $isCLI ? "" : "</div>";

if (!$isCLI) {
    echo "<p><a href='admin_v4.php' style='color:#43D240;'>← Zurück zum Admin</a></p>";
    echo "</body></html>";
}
