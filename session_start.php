<?php
/**
 * QUICK START für nächste Chat-Session
 * 
 * Dieses Script zeigt den aktuellen Stand und was zu tun ist
 */

echo "<h1>🎯 sgiT Education - Session Start</h1>";

echo "<h2>✅ Was funktioniert:</h2>";
echo "<ul>";
echo "<li><strong>adaptive_learning.php v4.3</strong> - Login, Levels, Scores → PERFEKT!</li>";
echo "<li><strong>16 Module</strong> - Alle verfügbar</li>";
echo "<li><strong>DB normalisiert</strong> - Kleinschreibung</li>";
echo "</ul>";

echo "<h2>❌ Kritische Probleme:</h2>";
echo "<ul style='color: red;'>";
echo "<li><strong>Ollama-Prompts</strong> - Generiert falsche Fragen! Physik → Erdkunde!</li>";
echo "<li><strong>Platzhalter</strong> - [Wrong answer] statt echte Antworten!</li>";
echo "<li><strong>Batch-Gen</strong> - Nur 1 Frage statt 10!</li>";
echo "</ul>";

echo "<h2>🔧 Nächste Schritte:</h2>";
echo "<ol>";
echo "<li>windows_ai_generator.php Prompts verbessern (modul-spezifisch)</li>";
echo "<li>Validierung gegen Platzhalter einbauen</li>";
echo "<li>Batch-Generierung fixen</li>";
echo "<li>Falsche Fragen aus DB löschen</li>";
echo "<li>Pro Modul 50+ Fragen generieren</li>";
echo "<li>Bot-System für Auto-Generation</li>";
echo "</ol>";

echo "<hr>";

// DB Stats
$db = new PDO('sqlite:C:\xampp\htdocs\Education\AI\data\questions.db');
$stmt = $db->query("SELECT module, COUNT(*) as cnt FROM questions GROUP BY module ORDER BY module");

echo "<h2>📊 Aktuelle DB-Fragen:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Modul</th><th>Anzahl Fragen</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $color = $row['cnt'] < 10 ? 'orange' : 'green';
    echo "<tr><td>{$row['module']}</td><td style='color: $color;'>{$row['cnt']}</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>🔗 Wichtige Links:</h2>";
echo "<ul>";
echo "<li><a href='sgit_education_status_report.md'>📋 Status-Report (ZUERST LESEN!)</a></li>";
echo "<li><a href='adaptive_learning.php'>🎯 Adaptive Learning (FUNKTIONIERT!)</a></li>";
echo "<li><a href='bots/tests/AIGeneratorBot.php'>🤖 AI Generator Bot</a></li>";
echo "<li><a href='check_module_consistency.php'>🔍 Module-Check</a></li>";
echo "</ul>";

?>
