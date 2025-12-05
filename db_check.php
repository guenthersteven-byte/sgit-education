<?php
/**
 * sgiT Education - Datenbank Diagnose
 * Prüft ai_generated Flag und andere Statistiken
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    $db = new SQLite3('AI/data/questions.db');
    
    echo "=== sgiT Education DB Diagnose ===\n\n";
    
    // 1. ai_generated Verteilung
    echo "1. AI_GENERATED VERTEILUNG:\n";
    echo str_repeat('-', 40) . "\n";
    $result = $db->query("SELECT ai_generated, COUNT(*) as cnt FROM questions GROUP BY ai_generated");
    while ($row = $result->fetchArray()) {
        $flag = $row['ai_generated'] === null ? 'NULL' : $row['ai_generated'];
        echo "   ai_generated = $flag : {$row['cnt']} Fragen\n";
    }
    
    // 2. Total questions
    $total = $db->querySingle("SELECT COUNT(*) FROM questions");
    echo "\n   TOTAL: $total Fragen\n";
    
    // 3. Fragen pro Modul
    echo "\n2. FRAGEN PRO MODUL:\n";
    echo str_repeat('-', 40) . "\n";
    $result = $db->query("SELECT module, COUNT(*) as cnt FROM questions GROUP BY module ORDER BY cnt DESC");
    while ($row = $result->fetchArray()) {
        printf("   %-15s : %d\n", $row['module'], $row['cnt']);
    }
    
    // 4. Beispiel-Fragen ohne ai_generated Flag
    echo "\n3. BEISPIEL FRAGEN OHNE AI-FLAG (ai_generated != 1):\n";
    echo str_repeat('-', 40) . "\n";
    $result = $db->query("SELECT id, module, question, ai_generated FROM questions WHERE ai_generated IS NULL OR ai_generated = 0 LIMIT 5");
    while ($row = $result->fetchArray()) {
        $ai = $row['ai_generated'] === null ? 'NULL' : $row['ai_generated'];
        echo "   ID {$row['id']} | {$row['module']} | ai=$ai\n";
        echo "   Q: " . substr($row['question'], 0, 60) . "...\n\n";
    }
    
    // 5. Tabellenstruktur
    echo "\n4. QUESTIONS TABELLEN-STRUKTUR:\n";
    echo str_repeat('-', 40) . "\n";
    $result = $db->query("PRAGMA table_info(questions)");
    while ($row = $result->fetchArray()) {
        printf("   %-20s %s %s\n", 
            $row['name'], 
            $row['type'], 
            $row['pk'] ? '(PK)' : ''
        );
    }
    
    // 6. Vorschlag zum Fixen
    echo "\n5. FIX VORSCHLAG:\n";
    echo str_repeat('-', 40) . "\n";
    $missing = $db->querySingle("SELECT COUNT(*) FROM questions WHERE ai_generated IS NULL OR ai_generated = 0");
    if ($missing > 0) {
        echo "   $missing Fragen haben kein ai_generated=1 Flag.\n";
        echo "   FIX: UPDATE questions SET ai_generated = 1 WHERE ai_generated IS NULL OR ai_generated = 0;\n";
    } else {
        echo "   Alle Fragen haben ai_generated = 1 ✓\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
