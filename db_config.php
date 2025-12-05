<?php
/**
 * sgiT Education - Zentrale Datenbank-Konfiguration
 * 
 * Stellt optimierte SQLite-Verbindungen mit WAL-Modus bereit
 * 
 * WAL-Modus Vorteile:
 * - Bessere Concurrency (gleichzeitige Lese-/Schreibzugriffe)
 * - Bessere Performance bei vielen kleinen Schreiboperationen
 * - Absturzsicherheit
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 01.12.2025
 */

class DatabaseConfig {
    
    // Datenbank-Pfade
    const MAIN_DB = __DIR__ . '/AI/data/questions.db';
    const BOT_DB = __DIR__ . '/bots/logs/bot_results.db';
    
    // SQLite Optimierungen
    const PRAGMAS = [
        'journal_mode' => 'WAL',           // Write-Ahead Logging
        'synchronous' => 'NORMAL',         // Gute Balance zwischen Speed und Safety
        'cache_size' => -64000,            // 64MB Cache (negativ = KB)
        'temp_store' => 'MEMORY',          // Temp-Tabellen im RAM
        'mmap_size' => 268435456,          // 256MB Memory-Mapped I/O
        'busy_timeout' => 5000,            // 5 Sekunden Timeout bei Lock
    ];
    
    /**
     * Erstellt eine optimierte SQLite-Verbindung
     * 
     * @param string $dbPath Pfad zur Datenbank
     * @param bool $applyPragmas Pragmas anwenden (Standard: true)
     * @return SQLite3|null
     */
    public static function getConnection($dbPath, $applyPragmas = true) {
        try {
            // Verzeichnis erstellen falls nicht vorhanden
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            
            $db = new SQLite3($dbPath);
            $db->enableExceptions(true);
            
            // Optimierungen anwenden
            if ($applyPragmas) {
                self::applyPragmas($db);
            }
            
            return $db;
            
        } catch (Exception $e) {
            error_log("DatabaseConfig Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Wendet alle Optimierungs-Pragmas an
     * 
     * @param SQLite3 $db
     */
    public static function applyPragmas($db) {
        foreach (self::PRAGMAS as $pragma => $value) {
            try {
                $db->exec("PRAGMA $pragma = $value");
            } catch (Exception $e) {
                // Einige Pragmas könnten fehlschlagen - kein kritischer Fehler
                error_log("Pragma $pragma failed: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Holt die Haupt-Datenbank (questions.db)
     * 
     * @return SQLite3|null
     */
    public static function getMainDB() {
        return self::getConnection(self::MAIN_DB);
    }
    
    /**
     * Holt die Bot-Datenbank (bot_results.db)
     * 
     * @return SQLite3|null
     */
    public static function getBotDB() {
        return self::getConnection(self::BOT_DB);
    }
    
    /**
     * Prüft den aktuellen Journal-Modus einer Datenbank
     * 
     * @param SQLite3 $db
     * @return string
     */
    public static function getJournalMode($db) {
        return $db->querySingle("PRAGMA journal_mode");
    }
    
    /**
     * Gibt Datenbank-Status als Array zurück
     * 
     * @param SQLite3 $db
     * @return array
     */
    public static function getStatus($db) {
        return [
            'journal_mode' => $db->querySingle("PRAGMA journal_mode"),
            'synchronous' => $db->querySingle("PRAGMA synchronous"),
            'cache_size' => $db->querySingle("PRAGMA cache_size"),
            'page_size' => $db->querySingle("PRAGMA page_size"),
            'wal_autocheckpoint' => $db->querySingle("PRAGMA wal_autocheckpoint"),
        ];
    }
    
    /**
     * Führt WAL-Checkpoint durch (räumt WAL-Datei auf)
     * 
     * @param SQLite3 $db
     * @return bool
     */
    public static function checkpoint($db) {
        try {
            $db->exec("PRAGMA wal_checkpoint(TRUNCATE)");
            return true;
        } catch (Exception $e) {
            error_log("Checkpoint failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Optimiert Datenbank (VACUUM)
     * 
     * @param SQLite3 $db
     * @return bool
     */
    public static function optimize($db) {
        try {
            $db->exec("VACUUM");
            $db->exec("ANALYZE");
            return true;
        } catch (Exception $e) {
            error_log("Optimize failed: " . $e->getMessage());
            return false;
        }
    }
}

// ============================================================
// STANDALONE: Wenn direkt aufgerufen, zeige Status
// ============================================================
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: text/html; charset=utf-8');
    
    echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <title>🗄️ DB Status - sgiT Education</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: linear-gradient(135deg, #1A3503, #2d5a06); padding: 20px; min-height: 100vh; margin: 0; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #1A3503; border-bottom: 3px solid #43D240; padding-bottom: 15px; }
        .db-card { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .db-card h3 { margin-top: 0; color: #1A3503; }
        .status-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .status-item { background: white; padding: 10px 15px; border-radius: 6px; }
        .status-item .label { font-size: 12px; color: #666; }
        .status-item .value { font-size: 18px; font-weight: bold; color: #1A3503; }
        .status-item .value.wal { color: #43D240; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; }
        button { background: #43D240; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-size: 14px; margin: 5px; }
        button:hover { background: #3ab837; }
        .nav-links { margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; }
        .nav-links a { color: #1A3503; text-decoration: none; margin-right: 20px; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🗄️ Datenbank-Status</h1>";
    
    // Aktion verarbeiten
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $dbType = $_POST['db'] ?? 'main';
        
        $db = ($dbType === 'bot') ? DatabaseConfig::getBotDB() : DatabaseConfig::getMainDB();
        
        if ($db) {
            if ($action === 'checkpoint') {
                if (DatabaseConfig::checkpoint($db)) {
                    echo "<div class='success'>✅ WAL-Checkpoint erfolgreich durchgeführt!</div>";
                } else {
                    echo "<div class='error'>❌ Checkpoint fehlgeschlagen</div>";
                }
            } elseif ($action === 'optimize') {
                if (DatabaseConfig::optimize($db)) {
                    echo "<div class='success'>✅ Datenbank optimiert (VACUUM + ANALYZE)!</div>";
                } else {
                    echo "<div class='error'>❌ Optimierung fehlgeschlagen</div>";
                }
            }
            $db->close();
        }
    }
    
    // Status der Haupt-DB
    $mainDb = DatabaseConfig::getMainDB();
    if ($mainDb) {
        $mainStatus = DatabaseConfig::getStatus($mainDb);
        $mainDb->close();
        
        echo "<div class='db-card'>
            <h3>📊 Haupt-Datenbank (questions.db)</h3>
            <p style='color: #666; font-size: 13px;'>" . DatabaseConfig::MAIN_DB . "</p>
            <div class='status-grid'>";
        
        foreach ($mainStatus as $key => $value) {
            $isWal = ($key === 'journal_mode' && strtolower($value) === 'wal');
            echo "<div class='status-item'>
                <div class='label'>$key</div>
                <div class='value " . ($isWal ? 'wal' : '') . "'>$value" . ($isWal ? ' ✓' : '') . "</div>
            </div>";
        }
        
        echo "</div>
            <div style='margin-top: 15px;'>
                <form method='post' style='display: inline;'>
                    <input type='hidden' name='db' value='main'>
                    <input type='hidden' name='action' value='checkpoint'>
                    <button type='submit'>🔄 Checkpoint</button>
                </form>
                <form method='post' style='display: inline;'>
                    <input type='hidden' name='db' value='main'>
                    <input type='hidden' name='action' value='optimize'>
                    <button type='submit'>⚡ Optimieren</button>
                </form>
            </div>
        </div>";
    } else {
        echo "<div class='error'>❌ Haupt-Datenbank nicht verfügbar</div>";
    }
    
    // Status der Bot-DB
    $botDb = DatabaseConfig::getBotDB();
    if ($botDb) {
        $botStatus = DatabaseConfig::getStatus($botDb);
        $botDb->close();
        
        echo "<div class='db-card'>
            <h3>🤖 Bot-Datenbank (bot_results.db)</h3>
            <p style='color: #666; font-size: 13px;'>" . DatabaseConfig::BOT_DB . "</p>
            <div class='status-grid'>";
        
        foreach ($botStatus as $key => $value) {
            $isWal = ($key === 'journal_mode' && strtolower($value) === 'wal');
            echo "<div class='status-item'>
                <div class='label'>$key</div>
                <div class='value " . ($isWal ? 'wal' : '') . "'>$value" . ($isWal ? ' ✓' : '') . "</div>
            </div>";
        }
        
        echo "</div>
            <div style='margin-top: 15px;'>
                <form method='post' style='display: inline;'>
                    <input type='hidden' name='db' value='bot'>
                    <input type='hidden' name='action' value='checkpoint'>
                    <button type='submit'>🔄 Checkpoint</button>
                </form>
                <form method='post' style='display: inline;'>
                    <input type='hidden' name='db' value='bot'>
                    <input type='hidden' name='action' value='optimize'>
                    <button type='submit'>⚡ Optimieren</button>
                </form>
            </div>
        </div>";
    } else {
        echo "<div class='error'>❌ Bot-Datenbank nicht verfügbar (wird beim ersten Bot-Run erstellt)</div>";
    }
    
    echo "<div class='db-card'>
        <h3>ℹ️ WAL-Modus Vorteile</h3>
        <ul>
            <li><strong>Bessere Concurrency:</strong> Mehrere Leser gleichzeitig möglich</li>
            <li><strong>Schnellere Writes:</strong> Schreiboperationen sind sequentiell</li>
            <li><strong>Absturzsicherheit:</strong> Daten sind bei Absturz sicher</li>
            <li><strong>Weniger I/O:</strong> Weniger Festplattenzugriffe</li>
        </ul>
    </div>";
    
    echo "<div class='nav-links'>
        <a href='admin_v4.php'>← Admin Dashboard</a>
        <a href='bots/bot_summary.php'>Bot Dashboard</a>
        <a href='setup_database.php'>DB Setup</a>
    </div>
</div>
</body>
</html>";
}
?>
