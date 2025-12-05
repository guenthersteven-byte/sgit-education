<?php
/**
 * ============================================================================
 * FIX für BUG-023: Modul umbenennen steuern -> finanzen
 * ============================================================================
 * 
 * v2.0 - Verbessertes Script mit korrektem Docker-Pfad
 * 
 * @date 06.12.2025
 * ============================================================================
 */

echo "<h1>🔧 BUG-023 FIX: Modul umbenennen steuern → finanzen</h1>";

// Docker-Pfad (da wir auf localhost:8080 laufen)
$dbPath = __DIR__ . '/AI/data/questions.db';

echo "<p><strong>Datenbank-Pfad:</strong> <code>$dbPath</code></p>";

if (!file_exists($dbPath)) {
    die("<p style='color: red;'>❌ FEHLER: Datenbank nicht gefunden!</p>");
}

echo "<p style='color: green;'>✅ Datenbank gefunden!</p>";

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Schritt 1: Aktuellen Stand prüfen
    echo "<h2>📊 Schritt 1: Aktueller Stand</h2>";
    
    $stmt = $db->query("SELECT module, COUNT(*) as count FROM questions GROUP BY module ORDER BY count DESC");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr><th>Modul</th><th>Fragen</th></tr>";
    $steuernExists = false;
    $steuernCount = 0;
    foreach ($modules as $mod) {
        $highlight = ($mod['module'] === 'steuern') ? "style='background: #ffcccc;'" : "";
        echo "<tr $highlight><td>{$mod['module']}</td><td>{$mod['count']}</td></tr>";
        if ($mod['module'] === 'steuern') {
            $steuernExists = true;
            $steuernCount = $mod['count'];
        }
    }
    echo "</table>";
    
    if (!$steuernExists) {
        echo "<p style='color: orange;'>⚠️ Modul 'steuern' existiert nicht (mehr). Eventuell schon umbenannt?</p>";
        echo "<p><a href='admin_v4.php'>→ Zurück zum Admin Dashboard</a></p>";
        exit;
    }
    
    echo "<p><strong>Gefunden:</strong> $steuernCount Fragen im Modul 'steuern'</p>";
    
    // Schritt 2: Umbenennen mit explizitem Commit
    echo "<h2>🔄 Schritt 2: Umbenennung durchführen</h2>";
    
    $db->beginTransaction();
    
    $updateStmt = $db->prepare("UPDATE questions SET module = 'finanzen' WHERE module = 'steuern'");
    $updateStmt->execute();
    $affected = $updateStmt->rowCount();
    
    $db->commit();
    
    echo "<p style='color: green; font-size: 1.2em;'><strong>✅ $affected Fragen umbenannt!</strong></p>";
    
    // Schritt 3: Verifizieren
    echo "<h2>✅ Schritt 3: Verifizierung</h2>";
    
    $checkSteuern = $db->query("SELECT COUNT(*) FROM questions WHERE module = 'steuern'")->fetchColumn();
    $checkFinanzen = $db->query("SELECT COUNT(*) FROM questions WHERE module = 'finanzen'")->fetchColumn();
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr><th>Modul</th><th>Fragen</th><th>Status</th></tr>";
    echo "<tr><td>steuern</td><td>$checkSteuern</td><td>" . ($checkSteuern == 0 ? "✅ Leer (gut!)" : "❌ Noch vorhanden!") . "</td></tr>";
    echo "<tr style='background: #ccffcc;'><td>finanzen</td><td>$checkFinanzen</td><td>" . ($checkFinanzen > 0 ? "✅ Vorhanden" : "❌ Fehlt!") . "</td></tr>";
    echo "</table>";
    
    // Schritt 4: Neue Gesamtübersicht
    echo "<h2>📊 Schritt 4: Neue Modulübersicht</h2>";
    
    $stmt = $db->query("SELECT module, COUNT(*) as count FROM questions GROUP BY module ORDER BY count DESC");
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $icon = ($row['module'] === 'finanzen') ? "💰 " : "";
        echo "<li><strong>$icon{$row['module']}</strong>: {$row['count']} Fragen</li>";
    }
    echo "</ul>";
    
    // Ergebnis
    if ($checkSteuern == 0 && $checkFinanzen > 0) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
        echo "<h2 style='color: #155724; margin: 0;'>🎉 BUG-023 GEFIXT!</h2>";
        echo "<p>Das Modul wurde erfolgreich von 'steuern' zu 'finanzen' umbenannt.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
        echo "<h2 style='color: #721c24; margin: 0;'>❌ Fehler bei der Umbenennung</h2>";
        echo "<p>Bitte prüfe die Datenbank manuell.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Fehler: " . $e->getMessage() . "</p>";
}

echo "<p style='margin-top: 30px;'><a href='admin_v4.php' style='padding: 10px 20px; background: #1A3503; color: white; text-decoration: none; border-radius: 5px;'>→ Zurück zum Admin Dashboard</a></p>";
?>
