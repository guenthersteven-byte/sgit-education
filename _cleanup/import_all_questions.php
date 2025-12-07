<?php
/**
 * ============================================================================
 * sgiT Education - Bulk Question Import v1.0
 * ============================================================================
 * 
 * Importiert die alle_fragen_v2.csv mit Modul-Feld direkt in die Datenbank.
 * Diese CSV enthält Fragen für ALLE Module in einer Datei.
 * 
 * UNTERSCHIED zur normalen CSV:
 * - Normale CSVs: modul wird beim Import ausgewählt
 * - Diese CSV: modul ist als erste Spalte enthalten
 * 
 * @author Claude AI für sgiT
 * @version 1.0
 * @date 04.12.2025
 * ============================================================================
 */

// Konfiguration
$csvFile = __DIR__ . '/docs/alle_fragen_v2.csv';
$dbPath = __DIR__ . '/AI/data/questions.db';

// CLI oder Web?
$isCLI = php_sapi_name() === 'cli';

function output($msg, $isCLI) {
    if ($isCLI) {
        echo $msg . "\n";
    } else {
        echo $msg . "<br>\n";
        flush();
    }
}

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><title>sgiT Bulk Import</title>";
    echo "<style>body{font-family:monospace;background:#1a1a2e;color:#43D240;padding:20px;}</style></head><body>";
    echo "<h1>🚀 sgiT Education - Bulk Question Import</h1><pre>";
}

// Start
output("=== sgiT Bulk Question Import ===", $isCLI);
output("CSV: $csvFile", $isCLI);
output("DB:  $dbPath", $isCLI);
output("", $isCLI);

// Prüfungen
if (!file_exists($csvFile)) {
    output("❌ FEHLER: CSV-Datei nicht gefunden!", $isCLI);
    exit(1);
}

if (!file_exists($dbPath)) {
    output("❌ FEHLER: Datenbank nicht gefunden!", $isCLI);
    exit(1);
}

// DB öffnen
try {
    $db = new SQLite3($dbPath);
    $db->enableExceptions(false);
} catch (Exception $e) {
    output("❌ DB-Fehler: " . $e->getMessage(), $isCLI);
    exit(1);
}

// Existierende Hashes laden
$existingHashes = [];
$result = $db->query("SELECT question_hash FROM questions WHERE question_hash IS NOT NULL");
while ($row = $result->fetchArray()) {
    $existingHashes[$row[0]] = true;
}
output("📊 " . count($existingHashes) . " existierende Hashes geladen", $isCLI);

// Hash-Funktion (identisch zu CSVQuestionImporter.php)
function generateQuestionHash($question, $a, $b, $c, $d) {
    $data = strtolower(trim($question));
    $data .= '|' . strtolower(trim($a));
    $data .= '|' . strtolower(trim($b));
    $data .= '|' . strtolower(trim($c));
    $data .= '|' . strtolower(trim($d));
    return md5($data);
}

// CSV lesen
$content = file_get_contents($csvFile);
$content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // BOM entfernen
$lines = explode("\n", $content);

$header = null;
$stats = [
    'total' => 0,
    'imported' => 0,
    'duplicates' => 0,
    'errors' => 0,
    'by_module' => []
];

$batchId = 'bulk_import_' . date('Ymd_His');

foreach ($lines as $lineNum => $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    // Header
    if ($header === null) {
        $header = str_getcsv($line, ';');
        $header = array_map('trim', $header);
        
        // Prüfen ob modul-Spalte existiert
        if (!in_array('modul', $header)) {
            output("❌ FEHLER: 'modul'-Spalte fehlt in CSV!", $isCLI);
            exit(1);
        }
        
        output("📋 CSV-Header: " . implode(', ', $header), $isCLI);
        output("", $isCLI);
        continue;
    }
    
    $stats['total']++;
    
    // Zeile parsen
    $values = str_getcsv($line, ';');
    if (count($values) !== count($header)) {
        $stats['errors']++;
        continue;
    }
    
    $row = array_combine($header, $values);
    
    // Modul extrahieren
    $module = strtolower(trim($row['modul']));
    if (empty($module)) {
        $stats['errors']++;
        continue;
    }
    
    // Statistik pro Modul
    if (!isset($stats['by_module'][$module])) {
        $stats['by_module'][$module] = ['imported' => 0, 'duplicates' => 0];
    }
    
    // Hash berechnen
    $hash = generateQuestionHash(
        $row['frage'],
        $row['antwort_a'],
        $row['antwort_b'],
        $row['antwort_c'],
        $row['antwort_d']
    );
    
    // Duplikat-Check
    if (isset($existingHashes[$hash])) {
        $stats['duplicates']++;
        $stats['by_module'][$module]['duplicates']++;
        continue;
    }
    
    // Richtige Antwort auflösen
    $answerMap = [
        'A' => $row['antwort_a'],
        'B' => $row['antwort_b'],
        'C' => $row['antwort_c'],
        'D' => $row['antwort_d']
    ];
    $correctLetter = strtoupper(trim($row['richtig']));
    $correctAnswer = $answerMap[$correctLetter] ?? $row['antwort_a'];
    
    // Options als JSON
    $options = json_encode([
        trim($row['antwort_a']),
        trim($row['antwort_b']),
        trim($row['antwort_c']),
        trim($row['antwort_d'])
    ], JSON_UNESCAPED_UNICODE);
    
    // INSERT
    $stmt = $db->prepare("
        INSERT INTO questions (
            module, question, answer, options, difficulty,
            age_min, age_max, ai_generated, source, imported_at,
            batch_id, question_hash, question_type, explanation
        ) VALUES (
            :module, :question, :answer, :options, :difficulty,
            :age_min, :age_max, 0, 'csv_import', datetime('now'),
            :batch_id, :hash, :type, :explanation
        )
    ");
    
    $stmt->bindValue(':module', $module, SQLITE3_TEXT);
    $stmt->bindValue(':question', trim($row['frage']), SQLITE3_TEXT);
    $stmt->bindValue(':answer', $correctAnswer, SQLITE3_TEXT);
    $stmt->bindValue(':options', $options, SQLITE3_TEXT);
    $stmt->bindValue(':difficulty', (int)$row['schwierigkeit'], SQLITE3_INTEGER);
    $stmt->bindValue(':age_min', (int)$row['min_alter'], SQLITE3_INTEGER);
    $stmt->bindValue(':age_max', (int)$row['max_alter'], SQLITE3_INTEGER);
    $stmt->bindValue(':batch_id', $batchId, SQLITE3_TEXT);
    $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':type', $row['typ'] ?? 'basic', SQLITE3_TEXT);
    $stmt->bindValue(':explanation', $row['erklaerung'] ?? '', SQLITE3_TEXT);
    
    $result = @$stmt->execute();
    
    if ($result) {
        $stats['imported']++;
        $stats['by_module'][$module]['imported']++;
        $existingHashes[$hash] = true; // Für spätere Duplikate in dieser Session
    } else {
        $error = $db->lastErrorMsg();
        if (strpos($error, 'UNIQUE') !== false) {
            $stats['duplicates']++;
            $stats['by_module'][$module]['duplicates']++;
        } else {
            $stats['errors']++;
        }
    }
}

// Ergebnis
output("", $isCLI);
output("=== IMPORT ABGESCHLOSSEN ===", $isCLI);
output("", $isCLI);
output("📊 GESAMT:", $isCLI);
output("   Gelesen:    " . $stats['total'], $isCLI);
output("   Importiert: " . $stats['imported'], $isCLI);
output("   Duplikate:  " . $stats['duplicates'], $isCLI);
output("   Fehler:     " . $stats['errors'], $isCLI);
output("   Batch-ID:   " . $batchId, $isCLI);
output("", $isCLI);

output("📋 PRO MODUL:", $isCLI);
ksort($stats['by_module']);
foreach ($stats['by_module'] as $mod => $modStats) {
    output(sprintf("   %-15s: +%d neu, %d duplikate", 
        $mod, $modStats['imported'], $modStats['duplicates']), $isCLI);
}

// Neue Gesamtzahl
$result = $db->query("SELECT COUNT(*) FROM questions");
$totalNow = $result->fetchArray()[0];
output("", $isCLI);
output("✅ Datenbank enthält jetzt: $totalNow Fragen", $isCLI);

$db->close();

if (!$isCLI) {
    echo "</pre></body></html>";
}
