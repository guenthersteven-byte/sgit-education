<?php
/**
 * Quick DB Structure Check
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== sgiT Education DB Structure Check ===\n\n";

// Questions DB
$questionsDb = 'C:\xampp\htdocs\Education\AI\data\questions.db';
if (file_exists($questionsDb)) {
    echo "✅ questions.db gefunden\n\n";
    
    $db = new PDO('sqlite:' . $questionsDb);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tables
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "TABELLEN: " . implode(", ", $tables) . "\n\n";
    
    foreach ($tables as $table) {
        echo "--- $table ---\n";
        
        // Schema
        $schema = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
        $cols = array_column($schema, 'name');
        echo "Spalten: " . implode(", ", $cols) . "\n";
        
        // Count
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "Anzahl: $count\n";
        
        // Sample
        if ($count > 0) {
            $sample = $db->query("SELECT * FROM $table LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            echo "Beispiel:\n";
            foreach ($sample as $k => $v) {
                $v = is_string($v) ? substr($v, 0, 50) : $v;
                echo "  $k: $v\n";
            }
        }
        echo "\n";
    }
    
    // Specific checks
    echo "=== SPEZIFISCHE CHECKS ===\n\n";
    
    // AI Generated
    echo "AI-Generiert Checks:\n";
    $checks = [
        "is_ai_generated = 1" => "SELECT COUNT(*) FROM questions WHERE is_ai_generated = 1",
        "ai_generated = 1" => "SELECT COUNT(*) FROM questions WHERE ai_generated = 1",
        "model_used IS NOT NULL" => "SELECT COUNT(*) FROM questions WHERE model_used IS NOT NULL",
        "source = 'ai'" => "SELECT COUNT(*) FROM questions WHERE source = 'ai'",
    ];
    
    foreach ($checks as $label => $sql) {
        try {
            $count = $db->query($sql)->fetchColumn();
            echo "  $label: $count\n";
        } catch (Exception $e) {
            echo "  $label: ERROR - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // User Answers
    echo "User Answers Checks:\n";
    $answerChecks = [
        "user_answers total" => "SELECT COUNT(*) FROM user_answers",
        "is_correct = 1" => "SELECT COUNT(*) FROM user_answers WHERE is_correct = 1",
        "correct = 1" => "SELECT COUNT(*) FROM user_answers WHERE correct = 1",
    ];
    
    foreach ($answerChecks as $label => $sql) {
        try {
            $count = $db->query($sql)->fetchColumn();
            echo "  $label: $count\n";
        } catch (Exception $e) {
            echo "  $label: ERROR - " . $e->getMessage() . "\n";
        }
    }
    
} else {
    echo "❌ questions.db NICHT gefunden!\n";
}

echo "\n\n=== WALLET DB ===\n\n";

$walletDb = 'C:\xampp\htdocs\Education\wallet\wallet.db';
if (file_exists($walletDb)) {
    echo "✅ wallet.db gefunden\n\n";
    
    $db = new PDO('sqlite:' . $walletDb);
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "$table: $count Einträge\n";
    }
} else {
    echo "❌ wallet.db NICHT gefunden!\n";
}

echo "\n\n=== FOXY DB ===\n\n";

$foxyDb = 'C:\xampp\htdocs\Education\database\foxy_chat.db';
if (file_exists($foxyDb)) {
    echo "✅ foxy_chat.db gefunden\n\n";
    
    $db = new PDO('sqlite:' . $foxyDb);
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "$table: $count Einträge\n";
    }
} else {
    echo "❌ foxy_chat.db NICHT gefunden!\n";
    echo "Pfad: $foxyDb\n";
    
    // Check alternative paths
    $altPaths = [
        'C:\xampp\htdocs\Education\clippy\foxy_chat.db',
        'C:\xampp\htdocs\Education\foxy_chat.db',
    ];
    
    foreach ($altPaths as $path) {
        if (file_exists($path)) {
            echo "✅ Gefunden unter: $path\n";
        }
    }
}

echo "\n\nDone!";
