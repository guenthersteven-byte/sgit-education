<?php
/**
 * sgiT Education - DB Fix: AI Generated Flag
 * 
 * Dieses Script korrigiert das ai_generated Flag in der Datenbank.
 * Alle Fragen mit model_used werden auf ai_generated=1 gesetzt.
 * 
 * Aufruf: http://localhost/Education/db_fix_ai_generated.php
 * 
 * @version 1.0
 * @date 01.12.2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbPath = __DIR__ . '/AI/data/questions.db';

if (!file_exists($dbPath)) {
    die("❌ Datenbank nicht gefunden: $dbPath");
}

try {
    $db = new SQLite3($dbPath);
    
    echo "<h1>🔧 sgiT Education - DB Fix</h1>";
    echo "<style>body{font-family:'Segoe UI',sans-serif;padding:20px;max-width:800px;margin:0 auto;} 
          .stat{background:#f5f5f5;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #43D240;}
          .fix{background:#E8F5E9;} .error{background:#FFEBEE;border-left-color:#F44336;}
          pre{background:#1E1E1E;color:#0F0;padding:15px;border-radius:8px;overflow-x:auto;}</style>";
    
    // === ANALYSE ===
    echo "<h2>📊 Aktuelle Situation</h2>";
    
    $total = $db->querySingle("SELECT COUNT(*) FROM questions");
    $aiByFlag = $db->querySingle("SELECT COUNT(*) FROM questions WHERE ai_generated = 1");
    $aiByModel = $db->querySingle("SELECT COUNT(*) FROM questions WHERE model_used IS NOT NULL");
    $aiNull = $db->querySingle("SELECT COUNT(*) FROM questions WHERE ai_generated IS NULL");
    $aiZero = $db->querySingle("SELECT COUNT(*) FROM questions WHERE ai_generated = 0");
    $needsFix = $db->querySingle("SELECT COUNT(*) FROM questions WHERE model_used IS NOT NULL AND (ai_generated IS NULL OR ai_generated = 0)");
    
    echo "<div class='stat'><strong>Gesamt Fragen:</strong> $total</div>";
    echo "<div class='stat'><strong>ai_generated = 1:</strong> $aiByFlag</div>";
    echo "<div class='stat'><strong>model_used gesetzt:</strong> $aiByModel</div>";
    echo "<div class='stat'><strong>ai_generated = NULL:</strong> $aiNull</div>";
    echo "<div class='stat'><strong>ai_generated = 0:</strong> $aiZero</div>";
    echo "<div class='stat " . ($needsFix > 0 ? 'error' : 'fix') . "'><strong>⚠️ Benötigt Fix:</strong> $needsFix Fragen</div>";
    
    // === FIX DURCHFÜHREN ===
    if ($needsFix > 0) {
        echo "<h2>🔧 Fix wird durchgeführt...</h2>";
        
        $sql = "UPDATE questions SET ai_generated = 1 WHERE model_used IS NOT NULL AND (ai_generated IS NULL OR ai_generated = 0)";
        echo "<pre>$sql</pre>";
        
        $db->exec($sql);
        $affected = $db->changes();
        
        echo "<div class='stat fix'><strong>✅ Korrigiert:</strong> $affected Fragen</div>";
        
        // Verifizierung
        $newAiCount = $db->querySingle("SELECT COUNT(*) FROM questions WHERE ai_generated = 1");
        $newNeedsFix = $db->querySingle("SELECT COUNT(*) FROM questions WHERE model_used IS NOT NULL AND (ai_generated IS NULL OR ai_generated = 0)");
        
        echo "<h2>✅ Nach dem Fix</h2>";
        echo "<div class='stat fix'><strong>ai_generated = 1:</strong> $newAiCount</div>";
        echo "<div class='stat fix'><strong>Noch zu fixen:</strong> $newNeedsFix</div>";
        
    } else {
        echo "<h2>✅ Kein Fix notwendig</h2>";
        echo "<div class='stat fix'>Alle Fragen haben korrekte ai_generated Flags!</div>";
    }
    
    // === MODUL-ÜBERSICHT ===
    echo "<h2>📚 Fragen pro Modul</h2>";
    $result = $db->query("SELECT module, COUNT(*) as cnt, SUM(CASE WHEN ai_generated = 1 THEN 1 ELSE 0 END) as ai_cnt FROM questions GROUP BY module ORDER BY cnt DESC");
    
    echo "<table style='width:100%;border-collapse:collapse;'>";
    echo "<tr style='background:#1A3503;color:white;'><th style='padding:10px;'>Modul</th><th>Gesamt</th><th>KI-Generiert</th><th>%</th></tr>";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $pct = $row['cnt'] > 0 ? round($row['ai_cnt'] / $row['cnt'] * 100, 1) : 0;
        echo "<tr style='border-bottom:1px solid #ddd;'>";
        echo "<td style='padding:10px;'>" . htmlspecialchars($row['module']) . "</td>";
        echo "<td style='text-align:center;'>{$row['cnt']}</td>";
        echo "<td style='text-align:center;'>{$row['ai_cnt']}</td>";
        echo "<td style='text-align:center;'>{$pct}%</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $db->close();
    
    echo "<br><a href='admin_v4.php' style='display:inline-block;padding:12px 24px;background:#43D240;color:white;text-decoration:none;border-radius:8px;font-weight:bold;'>← Zurück zum Dashboard</a>";
    
} catch (Exception $e) {
    echo "<div class='stat error'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
