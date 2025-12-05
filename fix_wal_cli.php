<?php
/**
 * CLI-Script: SQLite WAL-Mode erzwingen
 * 
 * Führe aus mit: php fix_wal_cli.php
 * 
 * Dieses Script erzwingt den WAL-Modus auch wenn
 * das Web-Interface Probleme hat.
 */

echo "=== SQLite WAL-Mode Fix (CLI) ===\n\n";

$databases = [
    'questions.db' => __DIR__ . '/AI/data/questions.db',
    'foxy_chat.db' => __DIR__ . '/clippy/foxy_chat.db',
    'child_wallets.db' => __DIR__ . '/child_wallets.db'
];

foreach ($databases as $name => $path) {
    echo "[$name] ";
    
    if (!file_exists($path)) {
        echo "⚠️  Nicht gefunden\n";
        continue;
    }
    
    try {
        // Direkt mit SQLite3 CLI-Befehl (falls PHP-Erweiterung Probleme macht)
        $db = new SQLite3($path);
        
        // Aktuellen Modus lesen
        $before = $db->querySingle("PRAGMA journal_mode;");
        echo "Vorher: $before → ";
        
        if (strtoupper($before) === 'WAL') {
            echo "✅ Bereits WAL\n";
            $db->close();
            continue;
        }
        
        // Verbindung schließen um alle Transaktionen zu beenden
        $db->close();
        unset($db);
        
        // Kurz warten
        usleep(100000); // 100ms
        
        // Neu öffnen
        $db = new SQLite3($path);
        
        // WAL setzen
        $after = $db->querySingle("PRAGMA journal_mode=WAL;");
        
        if (strtoupper($after) === 'WAL') {
            // Zusätzliche Optimierungen
            $db->exec("PRAGMA synchronous=NORMAL;");
            $db->exec("PRAGMA wal_autocheckpoint=1000;");
            echo "✅ WAL aktiviert!\n";
        } else {
            echo "❌ Fehlgeschlagen (Ergebnis: $after)\n";
        }
        
        $db->close();
        
    } catch (Exception $e) {
        echo "❌ Fehler: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Fertig ===\n";
echo "Prüfe die .db-wal und .db-shm Dateien neben den Datenbanken.\n";
