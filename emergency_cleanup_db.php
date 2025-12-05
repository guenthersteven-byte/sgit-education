<?php
/**
 * NOTFALL: Lösche alle schlechten Fragen aus DB
 */

$dbPath = __DIR__ . '/AI/data/questions.db';

if (!file_exists($dbPath)) {
    die("❌ DB nicht gefunden: $dbPath\n");
}

$db = new SQLite3($dbPath);

echo "=================================================================\n";
echo "NOTFALL-BEREINIGUNG DER DATENBANK\n";
echo "=================================================================\n\n";

// 1. Zähle aktuelle Fragen
$result = $db->query('SELECT COUNT(*) as cnt FROM questions');
$before = $result->fetchArray()['cnt'];
echo "Fragen VORHER: $before\n\n";

// 2. Lösche Fragen mit Platzhaltern
echo "Lösche Fragen mit Platzhaltern...\n";
$deleted = 0;

// Platzhalter in Frage
$deleted += $db->exec("DELETE FROM questions WHERE question LIKE '%[%]%'");
echo "  → $deleted Fragen mit [brackets] in Frage gelöscht\n";

// Platzhalter in Antworten  
$deleted += $db->exec("DELETE FROM questions WHERE answer LIKE '%[%]%'");
echo "  → Fragen mit [brackets] in Antwort gelöscht\n";

// Platzhalter in Options
$result = $db->query("SELECT id, options FROM questions");
$platzhalterIds = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $options = json_decode($row['options'], true);
    if ($options) {
        foreach ($options as $opt) {
            if (preg_match('/\[.*?\]/', $opt) || preg_match('/\{.*?\}/', $opt)) {
                $platzhalterIds[] = $row['id'];
                break;
            }
        }
    }
}

if (!empty($platzhalterIds)) {
    $ids = implode(',', $platzhalterIds);
    $db->exec("DELETE FROM questions WHERE id IN ($ids)");
    echo "  → " . count($platzhalterIds) . " Fragen mit Platzhaltern in Options gelöscht\n";
}

// 3. Lösche falsch kategorisierte Fragen
echo "\nLösche falsch kategorisierte Fragen...\n";

// Physik mit Erdkunde-Fragen
$cnt = $db->exec("DELETE FROM questions WHERE module='physik' AND (question LIKE '%hauptstadt%' OR question LIKE '%land%' OR question LIKE '%stadt%')");
echo "  → Erdkunde-Fragen aus Physik gelöscht\n";

// Biologie mit Erdkunde-Fragen
$cnt = $db->exec("DELETE FROM questions WHERE module='biologie' AND (question LIKE '%hauptstadt%' OR question LIKE '%land%' OR question LIKE '%stadt%')");
echo "  → Erdkunde-Fragen aus Biologie gelöscht\n";

// Bitcoin mit Erdkunde-Fragen
$cnt = $db->exec("DELETE FROM questions WHERE module='bitcoin' AND (question LIKE '%hauptstadt%' OR question LIKE '%land%' OR question LIKE '%stadt%')");
echo "  → Erdkunde-Fragen aus Bitcoin gelöscht\n";

// Computer mit Erdkunde-Fragen
$cnt = $db->exec("DELETE FROM questions WHERE module='computer' AND (question LIKE '%hauptstadt%' OR question LIKE '%land%' OR question LIKE '%stadt%')");
echo "  → Erdkunde-Fragen aus Computer gelöscht\n";

// 4. Zähle nach Bereinigung
$result = $db->query('SELECT COUNT(*) as cnt FROM questions');
$after = $result->fetchArray()['cnt'];

echo "\n=================================================================\n";
echo "ERGEBNIS\n";
echo "=================================================================\n\n";

echo "Fragen VORHER:  $before\n";
echo "Fragen NACHHER: $after\n";
echo "Gelöscht:       " . ($before - $after) . "\n\n";

if ($after < $before) {
    echo "✅ Bereinigung erfolgreich!\n";
    echo "\nJETZT: Generiere neue saubere Fragen mit v10.0!\n";
    echo "http://localhost/Education/windows_ai_generator.php\n";
} else {
    echo "⚠️  Keine schlechten Fragen gefunden (oder schon gelöscht)\n";
}

echo "\n=================================================================\n";

$db->close();
?>
