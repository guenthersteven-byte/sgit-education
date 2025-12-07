<?php
/**
 * sgiT Education - Cleanup RESTORE Script
 * 
 * Macht den Cleanup rückgängig - verschiebt alle Dateien zurück
 * an ihre ursprünglichen Positionen.
 * 
 * Nutzung: php _cleanup/RESTORE.php
 * 
 * @version 1.0
 * @date 08.12.2025
 */

$cleanupDir = __DIR__;
$projectRoot = dirname(__DIR__);
$logFile = $cleanupDir . '/cleanup_log.json';

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║       sgiT Education - RESTORE Script v1.0                ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Log laden
if (!file_exists($logFile)) {
    echo "❌ FEHLER: cleanup_log.json nicht gefunden!\n";
    echo "   Kann ohne Log-Datei nicht wiederherstellen.\n";
    exit(1);
}

$log = json_decode(file_get_contents($logFile), true);

if (empty($log['moved_files'])) {
    echo "ℹ️ Keine Dateien zum Wiederherstellen gefunden.\n";
    exit(0);
}

echo "📋 Log vom: " . $log['created_at'] . "\n";
echo "📦 Dateien zu wiederherstellen: " . count($log['moved_files']) . "\n\n";

// Bestätigung
echo "⚠️  WARNUNG: Dies wird alle Dateien zurück verschieben!\n";
echo "   Fortfahren? (j/n): ";

// Im CLI-Modus auf Eingabe warten
if (php_sapi_name() === 'cli') {
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) !== 'j' && trim($line) !== 'J') {
        echo "\n❌ Abgebrochen.\n";
        exit(0);
    }
    fclose($handle);
}

echo "\n🔄 Stelle Dateien wieder her...\n";
echo "───────────────────────────────────────────────────────────\n";

$restoredCount = 0;
$errorCount = 0;

foreach ($log['moved_files'] as $entry) {
    $originalPath = $entry['original'];
    $sourcePath = $cleanupDir . '/' . $originalPath;
    $targetPath = $projectRoot . '/' . $originalPath;
    
    // Prüfen ob Datei in _cleanup existiert
    if (!file_exists($sourcePath)) {
        echo "   ⏭️ Nicht gefunden: $originalPath\n";
        continue;
    }
    
    // Zielverzeichnis erstellen falls nötig
    $targetDir = dirname($targetPath);
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Datei zurück verschieben
    if (rename($sourcePath, $targetPath)) {
        echo "   ✓ $originalPath\n";
        $restoredCount++;
    } else {
        echo "   ✗ FEHLER: $originalPath\n";
        $errorCount++;
    }
}

// Leere Unterordner in _cleanup entfernen
$subDirs = ['adaptive_learning_backup', 'profil', 'wallet', 'scripts/php', 'scripts', 'AI/config', 'AI', 'bots'];
foreach ($subDirs as $dir) {
    $dirPath = $cleanupDir . '/' . $dir;
    if (is_dir($dirPath) && count(glob($dirPath . '/*')) === 0) {
        rmdir($dirPath);
    }
}

echo "\n───────────────────────────────────────────────────────────\n";
echo "📊 Zusammenfassung:\n";
echo "   ✅ Wiederhergestellt: $restoredCount Dateien\n";
echo "   ❌ Fehler:           $errorCount Dateien\n";
echo "\n";

if ($restoredCount > 0 && $errorCount === 0) {
    // Log umbenennen (als Backup)
    $backupLogFile = $cleanupDir . '/cleanup_log_restored_' . date('Ymd_His') . '.json';
    rename($logFile, $backupLogFile);
    
    echo "✅ Alle Dateien erfolgreich wiederhergestellt!\n";
    echo "📋 Log-Backup: " . basename($backupLogFile) . "\n";
    echo "\n";
    echo "💡 Du kannst den _cleanup/ Ordner jetzt löschen wenn alles funktioniert.\n";
} else {
    echo "⚠️ Einige Dateien konnten nicht wiederhergestellt werden.\n";
    echo "   Prüfe die Fehler und versuche es erneut.\n";
}

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║  ⚠️  NICHT VERGESSEN: Git Commit machen!                  ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
