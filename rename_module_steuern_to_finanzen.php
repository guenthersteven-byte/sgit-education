<?php
/**
 * Rename Module: steuern -> finanzen
 * Einmalig ausführen!
 */

// Für Docker und XAMPP
$dbPaths = [
    '/var/www/html/AI/data/questions.db',           // Docker
    'C:\xampp\htdocs\Education\AI\data\questions.db' // XAMPP
];

$dbPath = null;
foreach ($dbPaths as $path) {
    if (file_exists($path)) {
        $dbPath = $path;
        break;
    }
}

if (!$dbPath) {
    die("❌ Datenbank nicht gefunden!");
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔄 Modul umbenennen: steuern → finanzen</h1>";
    
    // Vorher zählen
    $countBefore = $db->query("SELECT COUNT(*) FROM questions WHERE module = 'steuern'")->fetchColumn();
    echo "<p><strong>Vorher:</strong> $countBefore Fragen im Modul 'steuern'</p>";
    
    // Umbenennen
    $stmt = $db->prepare("UPDATE questions SET module = 'finanzen' WHERE module = 'steuern'");
    $stmt->execute();
    $affected = $stmt->rowCount();
    
    // Nachher zählen
    $countAfter = $db->query("SELECT COUNT(*) FROM questions WHERE module = 'finanzen'")->fetchColumn();
    
    echo "<p><strong>Umbenannt:</strong> $affected Fragen</p>";
    echo "<p><strong>Nachher:</strong> $countAfter Fragen im Modul 'finanzen'</p>";
    
    // Alle Module anzeigen
    echo "<h2>📊 Aktuelle Module:</h2><ul>";
    $stmt = $db->query("SELECT module, COUNT(*) as count FROM questions GROUP BY module ORDER BY count DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li><strong>{$row['module']}</strong>: {$row['count']} Fragen</li>";
    }
    echo "</ul>";
    
    echo "<p style='color: green; font-size: 1.5em;'>✅ Fertig!</p>";
    echo "<p><a href='admin_v4.php'>→ Zurück zum Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Fehler: " . $e->getMessage() . "</p>";
}
?>
