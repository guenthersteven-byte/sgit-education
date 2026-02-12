<?php
/**
 * sgiT Education Platform - Bulk Import Script v2.0
 * Importiert alle v2 CSV-Fragen in die Datenbank
 * 
 * Erstellt: 2024-12-04
 * Autor: Claude AI für sgiT
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Modul-Mapping: CSV-Name → Modul-ID in der Datenbank
$moduleMapping = [
    'mathematik_v2.csv' => 'mathematik',
    'lesen_v2.csv' => 'lesen',
    'englisch_v2.csv' => 'englisch',
    'wissenschaft_v2.csv' => 'wissenschaft',
    'erdkunde_v2.csv' => 'erdkunde',
    'chemie_v2.csv' => 'chemie',
    'physik_v2.csv' => 'physik',
    'biologie_v2.csv' => 'biologie',
    'geschichte_v2.csv' => 'geschichte',
    'kunst_v2.csv' => 'kunst',
    'musik_v2.csv' => 'musik',
    'computer_v2.csv' => 'computer',
    'programmieren_v2.csv' => 'programmieren',
    'bitcoin_v2.csv' => 'bitcoin',
    'steuern_v2.csv' => 'steuern',
    'verkehr_v2.csv' => 'verkehr'
];

// HTML Header
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>sgiT Import v2</title>";
echo "<style>
    body { font-family: 'Space Grotesk', system-ui, sans-serif; background: #1a1a2e; color: #eee; padding: 20px; }
    .container { max-width: 1000px; margin: 0 auto; }
    h1 { color: #43D240; }
    .module { background: #16213e; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #43D240; }
    .module.error { border-left-color: #ff4444; }
    .success { color: #43D240; }
    .error { color: #ff4444; }
    .warning { color: #ffaa00; }
    .summary { background: #1A3503; padding: 20px; border-radius: 8px; margin-top: 20px; }
    .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 15px; }
    .stat { background: #16213e; padding: 15px; border-radius: 8px; text-align: center; }
    .stat-value { font-size: 24px; color: #43D240; font-weight: bold; }
    .stat-label { font-size: 12px; color: #888; }
</style></head><body><div class='container'>";

echo "<h1>🚀 sgiT Education - Fragen Import v2.0</h1>";
echo "<p>Importiert alle neuen Fragen aus den v2 CSV-Dateien</p>";

// Datenbank verbinden - KORREKTER PFAD
$dbPath = __DIR__ . '/AI/data/questions.db';
if (!file_exists($dbPath)) {
    die("<p class='error'>❌ Datenbank nicht gefunden: $dbPath</p></body></html>");
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ Datenbank verbunden: $dbPath</p>";
} catch (PDOException $e) {
    die("<p class='error'>❌ Datenbankfehler: " . $e->getMessage() . "</p></body></html>");
}

// Prüfen ob Tabelle existiert
$tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='adaptive_questions'")->fetch();
if (!$tableCheck) {
    // Tabelle erstellen
    $db->exec("
        CREATE TABLE IF NOT EXISTS adaptive_questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            modul TEXT NOT NULL,
            frage TEXT NOT NULL,
            antwort_a TEXT,
            antwort_b TEXT,
            antwort_c TEXT,
            antwort_d TEXT,
            richtig TEXT NOT NULL,
            schwierigkeit INTEGER DEFAULT 1,
            min_alter INTEGER DEFAULT 5,
            max_alter INTEGER DEFAULT 21,
            typ TEXT DEFAULT 'basic',
            erklaerung TEXT,
            quelle TEXT,
            erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='warning'>⚠️ Tabelle adaptive_questions wurde erstellt</p>";
}

// Statistiken
$totalImported = 0;
$totalSkipped = 0;
$totalErrors = 0;
$moduleStats = [];

// CSV-Verzeichnis
$csvDir = __DIR__ . '/docs/';

echo "<h2>📁 Importiere Module...</h2>";

foreach ($moduleMapping as $csvFile => $moduleName) {
    $csvPath = $csvDir . $csvFile;
    
    echo "<div class='module'>";
    echo "<h3>📚 Modul: " . ucfirst($moduleName) . "</h3>";
    
    if (!file_exists($csvPath)) {
        echo "<p class='error'>❌ CSV nicht gefunden: $csvFile</p>";
        $totalErrors++;
        echo "</div>";
        continue;
    }
    
    // CSV lesen
    $handle = fopen($csvPath, 'r');
    if (!$handle) {
        echo "<p class='error'>❌ Kann CSV nicht öffnen: $csvFile</p>";
        $totalErrors++;
        echo "</div>";
        continue;
    }
    
    // Header überspringen
    $header = fgetcsv($handle, 0, ';');
    
    $imported = 0;
    $skipped = 0;
    $errors = 0;
    
    // Prepared Statement
    $stmt = $db->prepare("
        INSERT INTO adaptive_questions 
        (modul, frage, antwort_a, antwort_b, antwort_c, antwort_d, richtig, schwierigkeit, min_alter, max_alter, typ, erklaerung, quelle, erstellt_am)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'csv_import_v2', datetime('now'))
    ");
    
    // Duplikat-Check Statement
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM adaptive_questions WHERE modul = ? AND frage = ?");
    
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        if (count($row) < 11) {
            $errors++;
            continue;
        }
        
        $frage = trim($row[0]);
        $antwortA = trim($row[1]);
        $antwortB = trim($row[2]);
        $antwortC = trim($row[3]);
        $antwortD = trim($row[4]);
        $richtig = strtoupper(trim($row[5]));
        $schwierigkeit = intval($row[6]);
        $minAlter = intval($row[7]);
        $maxAlter = intval($row[8]);
        $typ = trim($row[9]);
        $erklaerung = isset($row[10]) ? trim($row[10]) : '';
        
        // Validierung
        if (empty($frage) || empty($richtig)) {
            $errors++;
            continue;
        }
        
        // Duplikat-Check
        $checkStmt->execute([$moduleName, $frage]);
        if ($checkStmt->fetchColumn() > 0) {
            $skipped++;
            continue;
        }
        
        // Import
        try {
            $stmt->execute([
                $moduleName,
                $frage,
                $antwortA,
                $antwortB,
                $antwortC,
                $antwortD,
                $richtig,
                $schwierigkeit,
                $minAlter,
                $maxAlter,
                $typ,
                $erklaerung
            ]);
            $imported++;
        } catch (PDOException $e) {
            $errors++;
        }
    }
    
    fclose($handle);
    
    // Modul-Statistik
    $moduleStats[$moduleName] = [
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors
    ];
    
    $totalImported += $imported;
    $totalSkipped += $skipped;
    $totalErrors += $errors;
    
    echo "<p><span class='success'>✅ Importiert: $imported</span> | ";
    echo "<span class='warning'>⏭️ Übersprungen: $skipped</span> | ";
    echo "<span class='error'>❌ Fehler: $errors</span></p>";
    echo "</div>";
}

// Gesamtstatistik
echo "<div class='summary'>";
echo "<h2>📊 Import-Zusammenfassung</h2>";
echo "<div class='stats'>";
echo "<div class='stat'><div class='stat-value'>$totalImported</div><div class='stat-label'>Importiert</div></div>";
echo "<div class='stat'><div class='stat-value'>$totalSkipped</div><div class='stat-label'>Übersprungen (Duplikate)</div></div>";
echo "<div class='stat'><div class='stat-value'>$totalErrors</div><div class='stat-label'>Fehler</div></div>";
echo "<div class='stat'><div class='stat-value'>" . count($moduleStats) . "</div><div class='stat-label'>Module</div></div>";
echo "</div>";

// Gesamtanzahl in DB
$totalInDb = $db->query("SELECT COUNT(*) FROM adaptive_questions")->fetchColumn();
echo "<p style='margin-top:20px; font-size:18px;'>📚 <strong>Gesamt Fragen in Datenbank: $totalInDb</strong></p>";

echo "</div>";

// Link zurück
echo "<p style='margin-top:20px;'><a href='admin_v4.php' style='color:#43D240;'>← Zurück zum Admin Dashboard</a></p>";

echo "</div></body></html>";
?>
