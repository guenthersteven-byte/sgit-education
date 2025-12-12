<?php
/**
 * ============================================================================
 * Migration: Joker-Spalten zu child_wallets hinzufügen
 * ============================================================================
 * 
 * BUG-045: Joker war global (localStorage) statt pro User
 * 
 * Neue Spalten:
 * - joker_count: Aktuelle Anzahl Joker (Default: 3)
 * - joker_last_refill: Datum des letzten Refills
 * 
 * @version 1.0
 * @date 12.12.2025
 * ============================================================================
 */

require_once __DIR__ . '/../db_config.php';

$dbPath = __DIR__ . '/../wallet/wallet.db';

try {
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    
    echo "<h2>🎲 BUG-045: Joker Pro User Migration</h2>";
    
    // Prüfe ob Spalten bereits existieren
    $columns = [];
    $result = $db->query("PRAGMA table_info(child_wallets)");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row['name'];
    }
    
    $added = [];
    
    // joker_count hinzufügen
    if (!in_array('joker_count', $columns)) {
        $db->exec("ALTER TABLE child_wallets ADD COLUMN joker_count INTEGER DEFAULT 3");
        $added[] = 'joker_count';
        echo "<p style='color:green'>✅ Spalte 'joker_count' hinzugefügt (Default: 3)</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Spalte 'joker_count' existiert bereits</p>";
    }
    
    // joker_last_refill hinzufügen
    if (!in_array('joker_last_refill', $columns)) {
        $db->exec("ALTER TABLE child_wallets ADD COLUMN joker_last_refill DATE");
        $added[] = 'joker_last_refill';
        echo "<p style='color:green'>✅ Spalte 'joker_last_refill' hinzugefügt</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Spalte 'joker_last_refill' existiert bereits</p>";
    }
    
    // Bestehende User auf 3 Joker setzen (falls NULL)
    $db->exec("UPDATE child_wallets SET joker_count = 3 WHERE joker_count IS NULL");
    $updated = $db->changes();
    echo "<p style='color:blue'>ℹ️ $updated User auf 3 Joker initialisiert</p>";
    
    // Zeige aktuelle User
    echo "<h3>📊 Aktuelle User mit Joker:</h3>";
    echo "<table border='1' style='border-collapse:collapse; padding:5px'>";
    echo "<tr style='background:#1A3503;color:white'><th style='padding:8px'>ID</th><th style='padding:8px'>Name</th><th style='padding:8px'>Joker</th><th style='padding:8px'>Letzter Refill</th></tr>";
    
    $result = $db->query("SELECT id, child_name, joker_count, joker_last_refill FROM child_wallets ORDER BY id");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo "<tr>";
        echo "<td style='padding:8px'>{$row['id']}</td>";
        echo "<td style='padding:8px'>{$row['child_name']}</td>";
        echo "<td style='padding:8px'>{$row['joker_count']}</td>";
        echo "<td style='padding:8px'>" . ($row['joker_last_refill'] ?? 'nie') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $db->close();
    
    echo "<br><p style='color:green; font-weight:bold; font-size:18px'>✅ Migration abgeschlossen!</p>";
    echo "<p><a href='../admin_v4.php' style='color:#43D240'>← Zurück zum Admin</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Fehler: " . $e->getMessage() . "</p>";
}
