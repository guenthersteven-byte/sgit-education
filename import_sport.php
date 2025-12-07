<?php
/**
 * Import-Script für Sport Modul
 * Führe aus: docker exec -it sgit_php php /var/www/html/import_sport.php
 */

echo "=== Sport Modul Import ===\n\n";

$dbPath = __DIR__ . '/AI/data/questions.db';
$csvPath = __DIR__ . '/docs/sport_v1.csv';

if (!file_exists($csvPath)) {
    die("ERROR: CSV nicht gefunden: $csvPath\n");
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $handle = fopen($csvPath, 'r');
    $header = fgetcsv($handle, 0, ';');
    
    $imported = 0;
    $skipped = 0;
    $errors = 0;
    
    while (($row = fgetcsv($handle, 0, ';')) !== FALSE) {
        if (count($row) < 10) continue;
        
        $frage = trim($row[0]);
        $a = trim($row[1]);
        $b = trim($row[2]);
        $c = trim($row[3]);
        $d = trim($row[4]);
        $richtigBuchstabe = strtoupper(trim($row[5]));
        $schwierigkeit = trim($row[6]);
        $minAlter = (int)$row[7];
        $maxAlter = (int)$row[8];
        $erklaerung = isset($row[10]) ? trim($row[10]) : '';
        
        // Optionen als JSON
        $options = json_encode(['A' => $a, 'B' => $b, 'C' => $c, 'D' => $d]);
        
        // WICHTIG: Richtige Antwort als TEXT speichern!
        $answerMap = ['A' => $a, 'B' => $b, 'C' => $c, 'D' => $d];
        $richtig = $answerMap[$richtigBuchstabe] ?? $a;
        
        // Schwierigkeit zu Zahl
        $diffMap = ['leicht' => 3, 'mittel' => 5, 'schwer' => 8];
        $diffNum = $diffMap[$schwierigkeit] ?? 5;
        
        // Hash für Duplikat-Check
        $hash = md5(strtolower($frage) . '|' . strtolower($a) . '|' . strtolower($b));
        
        // Duplikat-Check
        $stmt = $db->prepare("SELECT 1 FROM questions WHERE question_hash = ?");
        $stmt->execute([$hash]);
        if ($stmt->fetch()) {
            $skipped++;
            continue;
        }
        
        // Frage einfügen
        $stmt = $db->prepare("INSERT INTO questions (module, question, answer, options, difficulty, age_min, age_max, explanation, erklaerung, question_hash, source, ai_generated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'csv_import', 0)");
        
        try {
            $stmt->execute(['sport', $frage, $richtig, $options, $diffNum, $minAlter, $maxAlter, $erklaerung, $erklaerung, $hash]);
            $imported++;
            echo "✓ " . substr($frage, 0, 55) . "...\n";
        } catch (Exception $e) {
            $errors++;
            echo "✗ Fehler: " . $e->getMessage() . "\n";
        }
    }
    
    fclose($handle);
    
    echo "\n=== ERGEBNIS ===\n";
    echo "✓ Importiert: $imported\n";
    echo "⊘ Übersprungen: $skipped\n";
    echo "✗ Fehler: $errors\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM questions WHERE module = 'sport'");
    echo "\n🏃 Sport hat jetzt: " . $stmt->fetchColumn() . " Fragen\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM questions");
    echo "📊 Gesamt in DB: " . $stmt->fetchColumn() . " Fragen\n";
    
} catch (PDOException $e) {
    die("DB-Fehler: " . $e->getMessage() . "\n");
}
