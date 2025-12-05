<?php
/**
 * sgiT Education - Duplikat-Bereinigung
 * Findet und entfernt doppelte Fragen pro Modul
 */

$dbPath = __DIR__ . '/AI/data/questions.db';

if (!file_exists($dbPath)) {
    die("❌ DB nicht gefunden: $dbPath\n");
}

$db = new SQLite3($dbPath);

echo "=================================================================\n";
echo "DUPLIKAT-BEREINIGUNG\n";
echo "=================================================================\n\n";

// 1. Statistik VORHER
$result = $db->query('SELECT COUNT(*) as cnt FROM questions');
$before = $result->fetchArray()['cnt'];
echo "Fragen VORHER: $before\n\n";

// 2. Finde Duplikate
echo "Suche nach Duplikaten...\n";

$duplikate = [];
$result = $db->query("
    SELECT question, module, COUNT(*) as cnt, GROUP_CONCAT(id) as ids
    FROM questions
    GROUP BY question, module
    HAVING cnt > 1
    ORDER BY cnt DESC
");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $duplikate[] = $row;
    echo "  → " . substr($row['question'], 0, 50) . "... (" . $row['cnt'] . "x in " . $row['module'] . ")\n";
}

if (empty($duplikate)) {
    echo "\n✅ Keine Duplikate gefunden! Datenbank ist sauber.\n\n";
    echo "=================================================================\n";
    $db->close();
    exit(0);
}

echo "\n" . count($duplikate) . " Duplikat-Gruppen gefunden.\n\n";

// 3. Lösche Duplikate (behalte jeweils die ERSTE)
echo "Lösche Duplikate (behalte jeweils das Original)...\n";

$deleted = 0;
foreach ($duplikate as $dup) {
    $ids = explode(',', $dup['ids']);
    // Behalte erste ID, lösche Rest
    $firstId = array_shift($ids);
    
    if (!empty($ids)) {
        $idsToDelete = implode(',', $ids);
        $db->exec("DELETE FROM questions WHERE id IN ($idsToDelete)");
        $deleted += count($ids);
        echo "  → Behalten: ID $firstId, Gelöscht: " . count($ids) . " Duplikate\n";
    }
}

// 4. Statistik NACHHER
$result = $db->query('SELECT COUNT(*) as cnt FROM questions');
$after = $result->fetchArray()['cnt'];

echo "\n=================================================================\n";
echo "ERGEBNIS\n";
echo "=================================================================\n\n";

echo "Fragen VORHER:  $before\n";
echo "Fragen NACHHER: $after\n";
echo "Gelöscht:       $deleted Duplikate\n\n";

if ($deleted > 0) {
    echo "✅ Duplikate erfolgreich entfernt!\n";
    echo "\nJetzt: Erstelle UNIQUE Index für Zukunft...\n";
    
    try {
        $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_unique_question ON questions(question, module)');
        echo "✅ UNIQUE Index erstellt!\n";
        echo "   → Zukünftig werden Duplikate automatisch verhindert.\n";
    } catch (Exception $e) {
        echo "⚠️  Index konnte nicht erstellt werden: " . $e->getMessage() . "\n";
        echo "   (Vielleicht existiert er schon?)\n";
    }
} else {
    echo "ℹ️  Keine Duplikate gefunden.\n";
}

echo "\n=================================================================\n";
echo "STATISTIK PRO MODUL\n";
echo "=================================================================\n\n";

$result = $db->query('
    SELECT module, COUNT(*) as cnt
    FROM questions
    GROUP BY module
    ORDER BY cnt DESC
');

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo str_pad($row['module'], 20) . " → " . $row['cnt'] . " Fragen\n";
}

echo "\n=================================================================\n";

$db->close();
?>
