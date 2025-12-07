<?php
/**
 * sgiT Education - Cleanup Manager
 * 
 * Verschiebt ungenutzte Dateien in _cleanup Ordner
 * MIT Restore-Funktion!
 * 
 * @version 1.0
 * @date 08.12.2025
 */

// Konfiguration
$projectRoot = dirname(__FILE__);
$cleanupDir = $projectRoot . '/_cleanup';
$logFile = $cleanupDir . '/cleanup_log.json';

// Dateien die verschoben werden sollen (vom Dependency Bot)
$filesToMove = [
    // Update/Patch-Skripte
    'LEVEL_SYSTEM_PATCH.php',
    'PATCH_7_ALL_MODULES.php',
    'apply_v10.2_update.php',
    'apply_v10.3_update.php',
    'mega_update_all_modules.php',
    'patch_generator_v10.php',
    'normalize_modules.php',
    'switch_to_llama32.php',
    'update_fallback_erklaerungen.php',
    
    // Bug-Analyse
    'bug016_analyse.php',
    
    // DB-Diagnose
    'analyze_db.php',
    'check_question_stats.php',
    'check_questions.php',
    'check_schema.php',
    'db_check.php',
    'db_check_quick.php',
    
    // Import-Skripte
    'csv_import.php',
    'import_all_questions.php',
    'import_sport.php',
    'import_unnuetzes_wissen.php',
    'import_v2_questions.php',
    
    // Alte Backups
    'adaptive_learning_backup/adaptive_learning_old.php',
    'profil/index_backup.php',
    'wallet/_wallet_dashboard_old.php',
    
    // Test-Dateien
    'ai_test.php',
    'quick_test.php',
    'sqlite3_test.php',
    'javis_test_bots_simple.php',
    
    // Alte Scripts
    'scripts/php/complete_fix_all_modules.php',
    'scripts/php/db_fix_ai_generated.php',
    'scripts/php/final_complete_fix.php',
    'scripts/php/install_v10_fixes.php',
    'scripts/php/ollama_debug.php',
    
    // Generator/Setup
    'generate_questions.php',
    'generate_questions_full.php',
    'improved_question_engine.php',
    'install_improved_system.php',
    'reset_db_completely.php',
    'seed_zeichnen_foxy.php',
    'universal_module_template.php',
    'remove_duplicates.php',
    'rename_module_steuern_to_finanzen.php',
    
    // Alte AI Config
    'AI/config/ollama_cloud.php',
    
    // Alte Bot-Datei
    'bots/cleanup_suggestions.php',
    
    // Sonstige
    'wallet_debug.php',
    'debug_pattern.php',
];

// NICHT verschieben (manuell prüfen oder für später aufheben):
$keepFiles = [
    'config.php',                    // Könnte noch gebraucht werden
    'session.php',                   // Session-Handling
    'session_start.php',             // Session-Handling
    'includes/functions.php',        // Utility-Funktionen
    'includes/security.php',         // Security-Funktionen
    'wallet/btcpay_setup.php',       // BTCPay für später
    'wallet/btcpay_webhook.php',     // BTCPay für später
    'wallet/deposit.php',            // BTCPay für später
    'wallet/withdraw.php',           // BTCPay für später
    'zeichnen/save.php',             // Könnte per AJAX genutzt werden
    'clippy/include.php',            // Clippy-System
];

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║       sgiT Education - Cleanup Manager v1.0               ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Cleanup-Ordner erstellen
if (!file_exists($cleanupDir)) {
    mkdir($cleanupDir, 0777, true);
    echo "✅ Ordner erstellt: _cleanup/\n";
}

// Log-Array
$log = [
    'created_at' => date('Y-m-d H:i:s'),
    'version' => '3.16.9',
    'moved_files' => [],
    'skipped_files' => [],
    'errors' => []
];

echo "\n📦 Verschiebe Dateien...\n";
echo "───────────────────────────────────────────────────────────\n";

$movedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($filesToMove as $file) {
    $sourcePath = $projectRoot . '/' . $file;
    $targetPath = $cleanupDir . '/' . $file;
    
    // Prüfen ob Datei existiert
    if (!file_exists($sourcePath)) {
        $log['skipped_files'][] = ['file' => $file, 'reason' => 'not_found'];
        $skippedCount++;
        continue;
    }
    
    // Zielverzeichnis erstellen
    $targetDir = dirname($targetPath);
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Datei verschieben
    if (rename($sourcePath, $targetPath)) {
        echo "   ✓ $file\n";
        $log['moved_files'][] = [
            'original' => $file,
            'moved_to' => '_cleanup/' . $file,
            'moved_at' => date('Y-m-d H:i:s')
        ];
        $movedCount++;
    } else {
        echo "   ✗ FEHLER: $file\n";
        $log['errors'][] = ['file' => $file, 'error' => 'move_failed'];
        $errorCount++;
    }
}

// Leere Ordner aufräumen (optional)
$emptyDirs = [
    'adaptive_learning_backup',
    'scripts/php',
    'scripts',
    'AI/config',
    'AI'
];

foreach ($emptyDirs as $dir) {
    $dirPath = $projectRoot . '/' . $dir;
    if (is_dir($dirPath) && count(glob($dirPath . '/*')) === 0) {
        rmdir($dirPath);
        echo "   🗑️ Leerer Ordner entfernt: $dir/\n";
    }
}

// Log speichern
file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n───────────────────────────────────────────────────────────\n";
echo "📊 Zusammenfassung:\n";
echo "   ✅ Verschoben:  $movedCount Dateien\n";
echo "   ⏭️ Übersprungen: $skippedCount Dateien\n";
echo "   ❌ Fehler:      $errorCount Dateien\n";
echo "\n";
echo "📁 Alle Dateien in: _cleanup/\n";
echo "📋 Log gespeichert: _cleanup/cleanup_log.json\n";
echo "🔄 Rückgängig mit:  php _cleanup/RESTORE.php\n";
echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  ⚠️  NICHT VERGESSEN: Git Commit machen!                  ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
