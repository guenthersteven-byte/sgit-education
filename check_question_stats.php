<?php
/**
 * Check actual question counts per module
 */

$dbPath = __DIR__ . '/AI/data/questions.db';

if (!file_exists($dbPath)) {
    die("DB not found: $dbPath\n");
}

$db = new PDO("sqlite:$dbPath");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== sgiT Education - Fragen-Statistik ===\n";
echo "Datum: " . date('Y-m-d H:i:s') . "\n\n";

// Total
$total = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
echo "GESAMT: $total Fragen\n\n";

// Per Module
echo "PRO MODUL:\n";
echo str_repeat("-", 50) . "\n";

$stmt = $db->query("
    SELECT module, COUNT(*) as count 
    FROM questions 
    GROUP BY module 
    ORDER BY count DESC
");

$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($modules as $m) {
    $name = str_pad($m['module'], 20);
    $count = str_pad($m['count'], 5, ' ', STR_PAD_LEFT);
    echo "$name $count\n";
}

echo str_repeat("-", 50) . "\n";
echo "Module gesamt: " . count($modules) . "\n\n";

// Check for potential duplicates (same question text)
echo "DUPLIKAT-CHECK:\n";
$dupes = $db->query("
    SELECT frage, COUNT(*) as cnt 
    FROM questions 
    GROUP BY frage 
    HAVING cnt > 1
    ORDER BY cnt DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

if (count($dupes) > 0) {
    echo "⚠️ " . count($dupes) . " Duplikate gefunden:\n";
    foreach ($dupes as $d) {
        echo "  [{$d['cnt']}x] " . substr($d['frage'], 0, 50) . "...\n";
    }
} else {
    echo "✅ Keine Duplikate gefunden!\n";
}

echo "\n=== FERTIG ===\n";
