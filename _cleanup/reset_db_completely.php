<?php
/**
 * DB KOMPLETT ZURÜCKSETZEN
 * Löscht ALLE Fragen und startet frisch
 */

$dbPath = __DIR__ . '/AI/data/questions.db';

if (!file_exists($dbPath)) {
    die("❌ DB nicht gefunden: $dbPath\n");
}

echo "<h1>🗑️ DB RESET</h1>";

// 1. Backup der alten DB
$backup = $dbPath . '.old.' . date('Y-m-d_H-i-s');
copy($dbPath, $backup);
echo "<p>✅ Backup erstellt: $backup</p>";

// 2. Statistik VORHER
$db = new SQLite3($dbPath);
$result = $db->query('SELECT COUNT(*) as cnt FROM questions');
$before = $result->fetchArray()['cnt'];
echo "<p><strong>Fragen VORHER: $before</strong></p>";

// 3. ALLE Fragen löschen
$db->exec('DELETE FROM questions');
echo "<p>🗑️ ALLE Fragen gelöscht!</p>";

// 4. Statistik NACHHER
$result = $db->query('SELECT COUNT(*) as cnt FROM questions');
$after = $result->fetchArray()['cnt'];
echo "<p><strong>Fragen NACHHER: $after</strong></p>";

// 5. Vacuum (Speicher freigeben)
$db->exec('VACUUM');
echo "<p>✅ Datenbank optimiert!</p>";

$db->close();

echo "<hr>";
echo "<h2>✅ FERTIG!</h2>";
echo "<p>Die Datenbank ist jetzt LEER und bereit für neue Fragen mit v10.2!</p>";

echo "<h3>NÄCHSTE SCHRITTE:</h3>";
echo "<ol>";
echo "<li>Cache leeren: <code>Strg + Shift + R</code></li>";
echo "<li>Generator öffnen: <a href='windows_ai_generator.php'>windows_ai_generator.php</a></li>";
echo "<li>Physik wählen, Alter 10</li>";
echo "<li>Klicke <strong>'Eine Frage generieren'</strong></li>";
echo "<li>Prüfe: Ist es WIRKLICH eine Physik-Frage?</li>";
echo "</ol>";

echo "<p><strong>Backup liegt in:</strong> $backup</p>";
?>
