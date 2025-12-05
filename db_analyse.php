<?php
/**
 * sgiT Education - DB Analyse: Nicht-markierte KI-Fragen
 * 
 * Analysiert warum 66 Fragen nicht als KI-generiert markiert sind
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbPath = __DIR__ . '/AI/data/questions.db';

if (!file_exists($dbPath)) {
    die("❌ Datenbank nicht gefunden: $dbPath");
}

echo "<h1>🔍 sgiT Education - DB Analyse</h1>";
echo "<style>
    body{font-family:'Segoe UI',sans-serif;padding:20px;max-width:1200px;margin:0 auto;background:#f5f5f5;} 
    .card{background:white;padding:20px;margin:15px 0;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
    .stat{background:#f8f9fa;padding:12px;margin:8px 0;border-radius:8px;border-left:4px solid #43D240;}
    .warning{border-left-color:#ffc107;background:#fff8e1;}
    .error{border-left-color:#dc3545;background:#ffebee;}
    table{width:100%;border-collapse:collapse;margin:15px 0;}
    th,td{padding:10px;text-align:left;border-bottom:1px solid #eee;}
    th{background:#1A3503;color:white;}
    tr:hover{background:#f5f5f5;}
    .btn{display:inline-block;padding:12px 24px;background:#43D240;color:white;text-decoration:none;border-radius:8px;font-weight:bold;margin:10px 5px 10px 0;}
    .btn:hover{background:#3ab837;}
    .btn-danger{background:#dc3545;}
    pre{background:#2d2d2d;color:#0f0;padding:15px;border-radius:8px;overflow-x:auto;}
    code{background:#e9ecef;padding:2px 6px;border-radius:4px;}
</style>";

try {
    $db = new SQLite3($dbPath);
    
    // === ÜBERSICHT ===
    echo "<div class='card'>";
    echo "<h2>📊 Übersicht</h2>";
    
    $total = $db->querySingle("SELECT COUNT(*) FROM questions");
    $withAiFlag = $db->querySingle("SELECT COUNT(*) FROM questions WHERE ai_generated = 1");
    $withModel = $db->querySingle("SELECT COUNT(*) FROM questions WHERE model_used IS NOT NULL");
    $withBoth = $db->querySingle("SELECT COUNT(*) FROM questions WHERE ai_generated = 1 AND model_used IS NOT NULL");
    $withNeither = $db->querySingle("SELECT COUNT(*) FROM questions WHERE (ai_generated IS NULL OR ai_generated = 0) AND model_used IS NULL");
    
    echo "<div class='stat'><strong>Gesamt:</strong> $total Fragen</div>";
    echo "<div class='stat'><strong>ai_generated = 1:</strong> $withAiFlag</div>";
    echo "<div class='stat'><strong>model_used gesetzt:</strong> $withModel</div>";
    echo "<div class='stat'><strong>Beides gesetzt:</strong> $withBoth</div>";
    echo "<div class='stat warning'><strong>⚠️ WEDER NOCH (das Problem!):</strong> $withNeither Fragen</div>";
    echo "</div>";
    
    // === BEISPIELE DER PROBLEMATISCHEN FRAGEN ===
    echo "<div class='card'>";
    echo "<h2>🔍 Beispiele: Fragen OHNE KI-Markierung</h2>";
    echo "<p>Diese $withNeither Fragen haben weder <code>ai_generated=1</code> noch <code>model_used</code>:</p>";
    
    $result = $db->query("
        SELECT id, module, question, answer, ai_generated, model_used, created_at
        FROM questions 
        WHERE (ai_generated IS NULL OR ai_generated = 0) AND model_used IS NULL
        ORDER BY created_at DESC
        LIMIT 20
    ");
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Modul</th><th>Frage (gekürzt)</th><th>ai_generated</th><th>model_used</th><th>Erstellt</th></tr>";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $question = htmlspecialchars(substr($row['question'], 0, 50)) . '...';
        $aiGen = $row['ai_generated'] === null ? '<em>NULL</em>' : $row['ai_generated'];
        $model = $row['model_used'] ?: '<em>NULL</em>';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['module']}</td>";
        echo "<td>{$question}</td>";
        echo "<td>{$aiGen}</td>";
        echo "<td>{$model}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // === ZEITLICHE ANALYSE ===
    echo "<div class='card'>";
    echo "<h2>📅 Zeitliche Analyse</h2>";
    echo "<p>Wann wurden die problematischen Fragen erstellt?</p>";
    
    $result = $db->query("
        SELECT date(created_at) as day, COUNT(*) as cnt
        FROM questions 
        WHERE (ai_generated IS NULL OR ai_generated = 0) AND model_used IS NULL
        GROUP BY day
        ORDER BY day DESC
        LIMIT 10
    ");
    
    echo "<table>";
    echo "<tr><th>Datum</th><th>Anzahl</th></tr>";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "<tr><td>{$row['day']}</td><td>{$row['cnt']}</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // === MODUL-VERTEILUNG ===
    echo "<div class='card'>";
    echo "<h2>📚 Modul-Verteilung der problematischen Fragen</h2>";
    
    $result = $db->query("
        SELECT module, COUNT(*) as cnt
        FROM questions 
        WHERE (ai_generated IS NULL OR ai_generated = 0) AND model_used IS NULL
        GROUP BY module
        ORDER BY cnt DESC
    ");
    
    echo "<table>";
    echo "<tr><th>Modul</th><th>Anzahl ohne Markierung</th></tr>";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "<tr><td>{$row['module']}</td><td>{$row['cnt']}</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // === FIX VORSCHLAG ===
    echo "<div class='card'>";
    echo "<h2>🔧 Fix-Optionen</h2>";
    
    echo "<p><strong>Option 1:</strong> Alle nicht-markierten Fragen als KI-generiert markieren (wenn du sicher bist, dass alle von KI sind)</p>";
    echo "<pre>UPDATE questions SET ai_generated = 1 WHERE (ai_generated IS NULL OR ai_generated = 0) AND model_used IS NULL;</pre>";
    
    echo "<p><strong>Option 2:</strong> Alle Fragen komplett als KI markieren (Tabula Rasa)</p>";
    echo "<pre>UPDATE questions SET ai_generated = 1;</pre>";
    
    // Fix-Buttons
    if (isset($_GET['fix']) && $_GET['fix'] === 'mark_all_ai') {
        $db->exec("UPDATE questions SET ai_generated = 1 WHERE (ai_generated IS NULL OR ai_generated = 0) AND model_used IS NULL");
        $affected = $db->changes();
        echo "<div class='stat' style='background:#d4edda;border-left-color:#28a745;'><strong>✅ FIX DURCHGEFÜHRT:</strong> $affected Fragen wurden als KI-generiert markiert!</div>";
        echo "<p><a href='db_analyse.php' class='btn'>🔄 Seite neu laden</a></p>";
    } else {
        echo "<p>";
        echo "<a href='?fix=mark_all_ai' class='btn' onclick=\"return confirm('Wirklich alle $withNeither Fragen als KI-generiert markieren?');\">✅ Alle als KI markieren</a>";
        echo "<a href='admin_v4.php' class='btn' style='background:#6c757d;'>← Zurück zum Dashboard</a>";
        echo "</p>";
    }
    
    echo "</div>";
    
    $db->close();
    
} catch (Exception $e) {
    echo "<div class='card error'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
