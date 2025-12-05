<?php
/**
 * BUG-026 FIX: SQLite WAL-Mode aktivieren (v1.1)
 * 
 * Problem: 7.31% Fehlerrate bei 20 gleichzeitigen Usern (DB-Lock)
 * Lösung: Write-Ahead Logging (WAL) Mode aktivieren
 * 
 * v1.1: Fix für "cannot change into wal mode from within a transaction"
 *       - Explizites COMMIT vor PRAGMA
 *       - Autocommit-Modus erzwingen
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.1
 * @date 06.12.2025
 */

// Alle relevanten SQLite-Datenbanken
$databases = [
    'questions.db' => __DIR__ . '/AI/data/questions.db',
    'users.db' => __DIR__ . '/users.db',
    'foxy_chat.db' => __DIR__ . '/clippy/foxy_chat.db',
    'child_wallets.db' => __DIR__ . '/child_wallets.db'
];

$results = [];

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <title>BUG-026 Fix: SQLite WAL-Mode</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: linear-gradient(135deg, #1A3503, #2d5a06); 
            padding: 20px; 
            min-height: 100vh;
            margin: 0;
        }
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 20px; 
            padding: 40px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
        }
        h1 { color: #1A3503; border-bottom: 3px solid #43D240; padding-bottom: 15px; }
        h2 { color: #1A3503; margin-top: 30px; }
        .success { background: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #dc3545; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { background: #e7f3ff; border: 1px solid #b6d4fe; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #1A3503; color: white; }
        tr:hover { background: #f5f5f5; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-fail { color: #dc3545; font-weight: bold; }
        .status-skip { color: #6c757d; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
        .btn { 
            display: inline-block; 
            background: #43D240; 
            color: white; 
            padding: 12px 24px; 
            border-radius: 8px; 
            text-decoration: none; 
            margin: 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover { background: #3ab837; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 BUG-026 Fix: SQLite WAL-Mode <span style='font-size: 14px; background: #43D240; color: white; padding: 3px 10px; border-radius: 10px;'>v1.1</span></h1>
    
    <div class='info'>
        <strong>Problem:</strong> 7.31% Fehlerrate bei 20 gleichzeitigen Usern durch SQLite DB-Locks<br>
        <strong>Lösung:</strong> WAL (Write-Ahead Logging) aktivieren für parallele Zugriffe
    </div>
";

// Prüfen ob Fix ausgeführt werden soll
$applyFix = isset($_GET['apply']) && $_GET['apply'] === '1';

echo "<h2>📊 Ergebnisse</h2>";
echo "<table>
    <tr>
        <th>Datenbank</th>
        <th>Vorher</th>
        <th>Nachher</th>
        <th>Status</th>
    </tr>";

foreach ($databases as $name => $path) {
    $exists = file_exists($path);
    $currentMode = '—';
    $newMode = '—';
    $status = '';
    $statusClass = '';
    
    if ($exists) {
        try {
            // SQLite3 direkt verwenden (nicht PDO) für bessere Kontrolle
            $db = new SQLite3($path);
            
            // Aktuellen Modus abfragen
            $currentMode = strtoupper($db->querySingle("PRAGMA journal_mode;"));
            
            if ($applyFix && $currentMode !== 'WAL') {
                // Wichtig: Erst alle Transaktionen beenden
                // SQLite3 hat kein explizites "commit all" aber wir können
                // die Verbindung schließen und neu öffnen
                $db->close();
                
                // Neu öffnen im Autocommit-Modus
                $db = new SQLite3($path);
                
                // WAL-Mode setzen (muss außerhalb einer Transaktion sein)
                $newMode = strtoupper($db->querySingle("PRAGMA journal_mode=WAL;"));
                
                // Zusätzliche WAL-Optimierungen
                $db->exec("PRAGMA synchronous=NORMAL;");  // Schneller, aber sicher genug
                $db->exec("PRAGMA wal_autocheckpoint=1000;");  // Checkpoint alle 1000 Seiten
                
                $db->close();
                
                if ($newMode === 'WAL') {
                    $status = '✅ OK';
                    $statusClass = 'status-ok';
                    $results[$name] = ['success' => true, 'before' => $currentMode, 'after' => $newMode];
                } else {
                    $status = "❌ Konnte nicht auf WAL umstellen (Ergebnis: $newMode)";
                    $statusClass = 'status-fail';
                    $results[$name] = ['success' => false, 'before' => $currentMode, 'after' => $newMode];
                }
            } elseif ($currentMode === 'WAL') {
                $newMode = 'WAL';
                $status = '✅ OK';
                $statusClass = 'status-ok';
                $results[$name] = ['success' => true, 'before' => 'WAL', 'after' => 'WAL', 'note' => 'Bereits WAL'];
                $db->close();
            } else {
                $newMode = $currentMode;
                $status = "⏳ Noch nicht umgestellt";
                $statusClass = 'status-skip';
                $results[$name] = ['success' => false, 'before' => $currentMode, 'after' => $currentMode];
                $db->close();
            }
            
        } catch (Exception $e) {
            $status = '❌ ' . $e->getMessage();
            $statusClass = 'status-fail';
            $results[$name] = ['success' => false, 'error' => $e->getMessage()];
        }
    } else {
        $status = '⚠️ Nicht vorhanden';
        $statusClass = 'status-skip';
        $results[$name] = ['success' => false, 'error' => 'Datei nicht gefunden'];
    }
    
    echo "<tr>
        <td><strong>$name</strong></td>
        <td><code>$currentMode</code></td>
        <td><code>$newMode</code></td>
        <td class='$statusClass'>$status</td>
    </tr>";
}

echo "</table>";

// Zusammenfassung
$successCount = count(array_filter($results, fn($r) => $r['success'] ?? false));
$totalCount = count(array_filter($results, fn($r) => !isset($r['error']) || $r['error'] !== 'Datei nicht gefunden'));

if ($applyFix) {
    if ($successCount === $totalCount && $totalCount > 0) {
        echo "<div class='success'>
            <strong>✅ Vollständig erfolgreich!</strong><br>
            Alle $successCount Datenbanken sind jetzt im WAL-Modus.
        </div>";
    } elseif ($successCount > 0) {
        echo "<div class='warning'>
            <strong>⚠️ Teilweise erfolgreich</strong><br>
            $successCount von $totalCount Datenbanken wurden umgestellt. Siehe Details oben.
        </div>";
    } else {
        echo "<div class='error'>
            <strong>❌ Fehlgeschlagen</strong><br>
            Keine Datenbank konnte umgestellt werden.
        </div>";
    }
} else {
    echo "<div class='info'>
        <strong>ℹ️ Vorschau-Modus</strong><br>
        Klicke auf 'WAL-Mode aktivieren' um die Änderungen durchzuführen.
    </div>";
    
    echo "<p>
        <a href='?apply=1' class='btn'>🚀 WAL-Mode aktivieren</a>
        <a href='admin_v4.php' class='btn btn-secondary'>← Zurück zum Admin</a>
    </p>";
}

// WAL-Erklärung
echo "
<h2>📖 Was macht WAL?</h2>
<table>
    <tr>
        <th>Feature</th>
        <th>DELETE (vorher)</th>
        <th>WAL (jetzt)</th>
    </tr>
    <tr>
        <td>Parallele Leser</td>
        <td>❌ Blockiert bei Schreiben</td>
        <td>✅ Immer möglich</td>
    </tr>
    <tr>
        <td>Schreibgeschwindigkeit</td>
        <td>Langsam (fsync pro Transaktion)</td>
        <td>Schnell (gepuffert)</td>
    </tr>
    <tr>
        <td>Concurrent Users</td>
        <td>~10-15 stabil</td>
        <td>50+ möglich</td>
    </tr>
    <tr>
        <td>Zusätzliche Dateien</td>
        <td>Keine</td>
        <td>.db-wal, .db-shm</td>
    </tr>
</table>
";

// Nächste Schritte
if ($applyFix && $successCount > 0) {
    echo "
    <h2>✅ Nächster Schritt: Testen</h2>
    <div class='info'>
        <p>Führe den LoadTestBot erneut aus um die Verbesserung zu verifizieren:</p>
        <p><code>http://localhost:8080/bots/tests/LoadTestBot.php</code></p>
        <p><strong>Erwartetes Ergebnis:</strong><br>
        Stress (20 User): 0% Fehler (vorher 7.31%)<br>
        Breaking (50 User): <1% Fehler, P99 <1000ms</p>
    </div>
    <p>
        <a href='bots/tests/LoadTestBot.php' class='btn'>🧪 LoadTestBot starten</a>
        <a href='admin_v4.php' class='btn btn-secondary'>← Zurück zum Admin</a>
    </p>
    ";
}

echo "
</div>
</body>
</html>";
