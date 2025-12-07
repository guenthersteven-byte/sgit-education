<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new PDO('sqlite:C:\xampp\htdocs\Education\AI\data\questions.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h1>Datenbank-Struktur Analysis</h1>";

// Get all tables
echo "<h2>Tabellen:</h2>";
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>" . print_r($tables, true) . "</pre>";

// For each table, show schema
foreach ($tables as $table) {
    echo "<h2>Tabelle: $table</h2>";
    $schema = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Name</th><th>Type</th><th>NotNull</th><th>Default</th><th>PK</th></tr>";
    foreach ($schema as $col) {
        echo "<tr>";
        echo "<td>{$col['name']}</td>";
        echo "<td>{$col['type']}</td>";
        echo "<td>{$col['notnull']}</td>";
        echo "<td>{$col['dflt_value']}</td>";
        echo "<td>{$col['pk']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    try {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<p>Anzahl Einträge: $count</p>";
        
        if ($count > 0) {
            $sample = $db->query("SELECT * FROM $table LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Beispiel-Daten:</h3>";
            echo "<pre>" . print_r($sample, true) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Fehler: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}
?>
