<?php
/**
 * sgiT Education - Performance Analyse & Optimierung
 * 
 * Analysiert DB-Queries und erstellt fehlende Indizes
 * 
 * BUG-028: P99 Latenz bei 50 Usern (6160ms)
 * BUG-029: Chemie/Physik Module langsamer
 * 
 * @version 1.0
 * @date 06.12.2025
 */

echo "<!DOCTYPE html><html><head><title>Performance Analyse</title>
<style>
body { font-family: 'Space Grotesk', system-ui, sans-serif; background: #1A3503; padding: 20px; }
.container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 20px; padding: 40px; }
h1 { color: #1A3503; border-bottom: 3px solid #43D240; padding-bottom: 15px; }
h2 { color: #1A3503; margin-top: 30px; }
.success { color: #28a745; }
.warning { color: #ffc107; }
.error { color: #dc3545; }
.info { color: #17a2b8; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
th { background: #f8f9fa; }
.code { background: #f4f4f4; padding: 15px; border-radius: 8px; font-family: monospace; overflow-x: auto; }
.stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
.stat-box { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; }
.stat-box .number { font-size: 28px; font-weight: bold; color: #1A3503; }
.stat-box .label { font-size: 12px; color: #666; }
</style>
</head><body><div class='container'>";

echo "<h1>🔍 Performance Analyse - BUG-028 & BUG-029</h1>";

// DB Connection
$dbPath = __DIR__ . '/AI/data/questions.db';
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ Datenbank-Verbindung OK</p>";
} catch (PDOException $e) {
    die("<p class='error'>❌ DB-Fehler: " . $e->getMessage() . "</p>");
}

// ============================================================================
// 1. AKTUELLE INDIZES PRÜFEN
// ============================================================================
echo "<h2>📊 1. Aktuelle Index-Struktur</h2>";

$indexes = $db->query("SELECT name, sql FROM sqlite_master WHERE type='index' AND tbl_name='questions'")->fetchAll(PDO::FETCH_ASSOC);

if (empty($indexes)) {
    echo "<p class='error'>❌ KEINE INDIZES auf questions-Tabelle gefunden!</p>";
    echo "<p class='warning'>⚠️ Das ist die Hauptursache für die langsamen Queries!</p>";
} else {
    echo "<table><tr><th>Index-Name</th><th>Definition</th></tr>";
    foreach ($indexes as $idx) {
        echo "<tr><td>{$idx['name']}</td><td><code>{$idx['sql']}</code></td></tr>";
    }
    echo "</table>";
}

// ============================================================================
// 2. TABELLEN-STRUKTUR ANALYSIEREN
// ============================================================================
echo "<h2>📋 2. Tabellen-Struktur</h2>";

$columns = $db->query("PRAGMA table_info(questions)")->fetchAll(PDO::FETCH_ASSOC);
echo "<table><tr><th>Spalte</th><th>Typ</th><th>NotNull</th><th>Default</th><th>PK</th></tr>";
foreach ($columns as $col) {
    echo "<tr><td>{$col['name']}</td><td>{$col['type']}</td><td>{$col['notnull']}</td><td>{$col['dflt_value']}</td><td>{$col['pk']}</td></tr>";
}
echo "</table>";

// ============================================================================
// 3. DATEN-VERTEILUNG PRO MODUL
// ============================================================================
echo "<h2>📈 3. Fragen-Verteilung pro Modul</h2>";

$moduleStats = $db->query("
    SELECT module, 
           COUNT(*) as count,
           AVG(times_used) as avg_used,
           MIN(age_min) as min_age,
           MAX(age_max) as max_age
    FROM questions 
    GROUP BY module 
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

echo "<table><tr><th>Modul</th><th>Fragen</th><th>Ø Verwendet</th><th>Alter Min</th><th>Alter Max</th></tr>";
foreach ($moduleStats as $stat) {
    $count = $stat['count'];
    $color = $count < 100 ? 'warning' : ($count < 150 ? '' : 'success');
    echo "<tr><td>{$stat['module']}</td><td class='$color'>{$count}</td><td>" . round($stat['avg_used'], 1) . "</td><td>{$stat['min_age']}</td><td>{$stat['max_age']}</td></tr>";
}
echo "</table>";

// ============================================================================
// 4. QUERY EXPLAIN ANALYZE
// ============================================================================
echo "<h2>🔬 4. Query-Analyse (EXPLAIN QUERY PLAN)</h2>";

$testQueries = [
    'Hauptquery (Altersfilter)' => "SELECT * FROM questions WHERE module = 'chemie' AND age_min <= 39 AND age_max >= 39 ORDER BY times_used ASC, RANDOM() LIMIT 1",
    'Fallback Query' => "SELECT * FROM questions WHERE module = 'physik' ORDER BY age_min DESC, times_used ASC, RANDOM() LIMIT 1",
    'Modul-Lookup' => "SELECT * FROM questions WHERE module = 'mathematik' LIMIT 1"
];

foreach ($testQueries as $name => $query) {
    echo "<h4>$name</h4>";
    echo "<div class='code'>$query</div>";
    
    $explain = $db->query("EXPLAIN QUERY PLAN " . $query)->fetchAll(PDO::FETCH_ASSOC);
    
    $hasScan = false;
    foreach ($explain as $row) {
        $detail = $row['detail'] ?? '';
        if (strpos($detail, 'SCAN') !== false) {
            $hasScan = true;
            echo "<p class='error'>❌ SCAN: $detail</p>";
        } elseif (strpos($detail, 'SEARCH') !== false || strpos($detail, 'INDEX') !== false) {
            echo "<p class='success'>✅ INDEX: $detail</p>";
        } else {
            echo "<p class='info'>ℹ️ $detail</p>";
        }
    }
    
    if ($hasScan) {
        echo "<p class='warning'>⚠️ Full Table Scan erkannt - Index fehlt!</p>";
    }
}

// ============================================================================
// 5. BENCHMARK: Query-Zeiten messen
// ============================================================================
echo "<h2>⏱️ 5. Query-Benchmark (je 100 Durchläufe)</h2>";

$modules = ['mathematik', 'chemie', 'physik', 'bitcoin', 'wissenschaft'];
$benchResults = [];

foreach ($modules as $module) {
    $times = [];
    
    for ($i = 0; $i < 100; $i++) {
        $start = microtime(true);
        
        $stmt = $db->prepare("
            SELECT * FROM questions 
            WHERE module = :module
            AND age_min <= :age
            AND age_max >= :age
            ORDER BY times_used ASC, RANDOM()
            LIMIT 1
        ");
        $stmt->execute([':module' => $module, ':age' => 39]);
        $stmt->fetch();
        
        $times[] = (microtime(true) - $start) * 1000;
    }
    
    $benchResults[$module] = [
        'avg' => round(array_sum($times) / count($times), 2),
        'min' => round(min($times), 2),
        'max' => round(max($times), 2),
        'p95' => round($times[intval(count($times) * 0.95)], 2)
    ];
}

echo "<table><tr><th>Modul</th><th>Avg (ms)</th><th>Min (ms)</th><th>Max (ms)</th><th>P95 (ms)</th></tr>";
foreach ($benchResults as $module => $stats) {
    $avgClass = $stats['avg'] > 5 ? 'error' : ($stats['avg'] > 2 ? 'warning' : 'success');
    echo "<tr><td>$module</td><td class='$avgClass'>{$stats['avg']}</td><td>{$stats['min']}</td><td>{$stats['max']}</td><td>{$stats['p95']}</td></tr>";
}
echo "</table>";

// ============================================================================
// 6. EMPFOHLENE INDIZES
// ============================================================================
echo "<h2>🛠️ 6. Empfohlene Optimierungen</h2>";

echo "<h4>A) Fehlende Indizes erstellen:</h4>";
echo "<div class='code'>";
echo "-- Index für Modul-Suche (häufigste Query)\n";
echo "CREATE INDEX IF NOT EXISTS idx_questions_module ON questions(module);\n\n";
echo "-- Compound-Index für Altersfilterung\n";
echo "CREATE INDEX IF NOT EXISTS idx_questions_module_age ON questions(module, age_min, age_max);\n\n";
echo "-- Index für times_used Sortierung\n";
echo "CREATE INDEX IF NOT EXISTS idx_questions_module_used ON questions(module, times_used);\n";
echo "</div>";

echo "<h4>B) Query-Optimierung (RANDOM() vermeiden):</h4>";
echo "<div class='code'>";
echo "-- Statt ORDER BY RANDOM() (langsam):\n";
echo "SELECT * FROM questions WHERE module = ? ORDER BY RANDOM() LIMIT 1\n\n";
echo "-- Besser: Zufällige ID auswählen (schnell):\n";
echo "\$count = SELECT COUNT(*) FROM questions WHERE module = ?;\n";
echo "\$offset = rand(0, \$count - 1);\n";
echo "SELECT * FROM questions WHERE module = ? LIMIT 1 OFFSET \$offset;\n";
echo "</div>";

// ============================================================================
// 7. INDIZES ERSTELLEN (auf Knopfdruck)
// ============================================================================
echo "<h2>🚀 7. Indizes jetzt erstellen?</h2>";

if (isset($_POST['create_indexes'])) {
    echo "<h4>Erstelle Indizes...</h4>";
    
    $indexQueries = [
        'idx_questions_module' => 'CREATE INDEX IF NOT EXISTS idx_questions_module ON questions(module)',
        'idx_questions_module_age' => 'CREATE INDEX IF NOT EXISTS idx_questions_module_age ON questions(module, age_min, age_max)',
        'idx_questions_module_used' => 'CREATE INDEX IF NOT EXISTS idx_questions_module_used ON questions(module, times_used)',
        'idx_questions_hash' => 'CREATE INDEX IF NOT EXISTS idx_questions_hash ON questions(hash)'
    ];
    
    foreach ($indexQueries as $name => $sql) {
        try {
            $start = microtime(true);
            $db->exec($sql);
            $time = round((microtime(true) - $start) * 1000, 1);
            echo "<p class='success'>✅ $name erstellt ({$time}ms)</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ $name: " . $e->getMessage() . "</p>";
        }
    }
    
    // ANALYZE ausführen
    echo "<p class='info'>Führe ANALYZE aus...</p>";
    $db->exec("ANALYZE");
    echo "<p class='success'>✅ ANALYZE abgeschlossen</p>";
    
    echo "<p class='success'><strong>✅ Optimierung abgeschlossen! Bitte Seite neu laden für Benchmark.</strong></p>";
} else {
    echo "<form method='post'>";
    echo "<button type='submit' name='create_indexes' style='padding: 15px 30px; background: #43D240; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;'>🚀 Indizes jetzt erstellen</button>";
    echo "</form>";
}

// ============================================================================
// 8. GESAMT-STATISTIKEN
// ============================================================================
echo "<h2>📊 Zusammenfassung</h2>";

$totalQuestions = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$totalModules = $db->query("SELECT COUNT(DISTINCT module) FROM questions")->fetchColumn();

echo "<div class='stat-grid'>";
echo "<div class='stat-box'><div class='number'>$totalQuestions</div><div class='label'>Fragen gesamt</div></div>";
echo "<div class='stat-box'><div class='number'>$totalModules</div><div class='label'>Module</div></div>";
echo "<div class='stat-box'><div class='number'>" . count($indexes) . "</div><div class='label'>Indizes</div></div>";
echo "</div>";

echo "</div></body></html>";
?>
