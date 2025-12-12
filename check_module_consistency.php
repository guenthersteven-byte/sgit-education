<?php
/**
 * Module Case Consistency Check
 */

echo "<h1>🔍 Module Case Consistency Check</h1>";

// 1. Check DB modules
$db = new PDO('sqlite:C:\xampp\htdocs\Education\AI\data\questions.db');
$stmt = $db->query("SELECT DISTINCT module FROM questions ORDER BY module");
$dbModules = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>✅ Datenbank (SQLite):</h2><ul>";
foreach ($dbModules as $mod) {
    echo "<li>$mod</li>";
}
echo "</ul>";

// 2. Check adaptive_learning.php modules
echo "<h2>🎯 adaptive_learning.php Module-Cards:</h2>";
echo "<p>Diese werden gesendet:</p><ul>";
$adaptiveModules = [
    'mathematik', 'lesen', 'englisch', 'wissenschaft', 'erdkunde',
    'chemie', 'physik', 'kunst', 'musik', 'computer', 'bitcoin',
    'geschichte', 'biologie', 'steuern', 'programmieren', 'verkehr',
    'unnuetzes_wissen', 'sport'
];
foreach ($adaptiveModules as $mod) {
    $inDb = in_array($mod, $dbModules);
    $icon = $inDb ? '✅' : '❌';
    echo "<li>$icon $mod</li>";
}
echo "</ul>";

// 3. Check windows_ai_generator.php modules
echo "<h2>🤖 windows_ai_generator.php Dropdown:</h2>";
$content = file_get_contents('C:\xampp\htdocs\Education\windows_ai_generator.php');
preg_match_all('/<option value="([^"]+)">/', $content, $matches);
echo "<ul>";
foreach ($matches[1] as $mod) {
    $inDb = in_array($mod, $dbModules);
    $icon = $inDb ? '✅' : '❌';
    echo "<li>$icon $mod</li>";
}
echo "</ul>";

// 4. Summary
$missing = array_diff($adaptiveModules, $dbModules);
if (count($missing) > 0) {
    echo "<div style='background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2 style='color: #dc3545;'>⚠️ WARNUNG: Fehlende Module!</h2>";
    echo "<p>Diese Module haben keine Fragen in der DB:</p><ul>";
    foreach ($missing as $mod) {
        echo "<li><strong>$mod</strong></li>";
    }
    echo "</ul>";
    echo "<p><a href='bots/tests/AIGeneratorBot.php'>→ Generiere Fragen für diese Module!</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2 style='color: #28a745;'>✅ Alles OK!</h2>";
    echo "<p>Alle Module haben Fragen in der DB.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='adaptive_learning.php'>→ Zurück zu Adaptive Learning</a></p>";
echo "<p><a href='bots/tests/AIGeneratorBot.php'>→ Zu AI Generator Bot</a></p>";
?>
