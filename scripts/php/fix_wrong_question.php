<?php
/**
 * Fix wrong categorized question
 */

$db = new PDO('sqlite:C:\xampp\htdocs\Education\AI\data\questions.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h1>Korrigiere falsch kategorisierte Frage</h1>";

// Find the wrong question
$stmt = $db->query("SELECT * FROM questions WHERE module = 'physik' AND question LIKE '%Hauptstadt%'");
$wrongQ = $stmt->fetch(PDO::FETCH_ASSOC);

if ($wrongQ) {
    echo "<h2>Gefundene falsche Frage:</h2>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> {$wrongQ['id']}</li>";
    echo "<li><strong>Modul:</strong> {$wrongQ['module']}</li>";
    echo "<li><strong>Frage:</strong> {$wrongQ['question']}</li>";
    echo "</ul>";
    
    // Update to correct module
    $updateStmt = $db->prepare("UPDATE questions SET module = 'erdkunde' WHERE id = :id");
    $updateStmt->execute([':id' => $wrongQ['id']]);
    
    echo "<p style='color: green; font-size: 20px;'>✅ Frage wurde von 'physik' nach 'erdkunde' verschoben!</p>";
} else {
    echo "<p>Keine falsche Frage gefunden.</p>";
}

// Show current state
echo "<h2>Aktuelle Module:</h2><ul>";
$stmt = $db->query("SELECT DISTINCT module, COUNT(*) as count FROM questions GROUP BY module ORDER BY module");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<li>{$row['module']}: {$row['count']} Fragen</li>";
}
echo "</ul>";

echo "<p><a href='adaptive_learning.php'>Zurück zum Adaptive Learning</a></p>";
?>
