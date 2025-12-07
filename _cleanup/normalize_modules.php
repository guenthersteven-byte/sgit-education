<?php
/**
 * DB Cleanup - Normalize module names to lowercase
 */

$db = new PDO('sqlite:C:\xampp\htdocs\Education\AI\data\questions.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h1>Normalisiere Module-Namen zu Kleinschreibung</h1>";

// Get all distinct modules
$stmt = $db->query("SELECT DISTINCT module FROM questions");
$modules = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Vorher:</h2><ul>";
foreach ($modules as $mod) {
    $count = $db->query("SELECT COUNT(*) FROM questions WHERE module = '$mod'")->fetchColumn();
    echo "<li>$mod ($count Fragen)</li>";
}
echo "</ul>";

// Normalize to lowercase
$updateStmt = $db->prepare("UPDATE questions SET module = LOWER(module)");
$updateStmt->execute();

echo "<h2>Nachher:</h2><ul>";
$stmt = $db->query("SELECT DISTINCT module, COUNT(*) as count FROM questions GROUP BY module");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<li>{$row['module']} ({$row['count']} Fragen)</li>";
}
echo "</ul>";

echo "<p><strong>✅ Fertig! Alle Module sind jetzt kleingeschrieben.</strong></p>";
echo "<p><a href='adaptive_learning.php'>Zurück zum Adaptive Learning</a></p>";
?>
