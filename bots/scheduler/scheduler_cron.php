#!/usr/bin/env php
<?php
/**
 * ============================================================================
 * sgiT Education - Bot Scheduler Cron Entry Point
 * ============================================================================
 * 
 * Dieses Script wird regelmäßig via Cron aufgerufen und führt fällige Jobs aus.
 * 
 * CRON-SETUP (empfohlen alle 5 Minuten):
 * */5 * * * * php /var/www/html/bots/scheduler/scheduler_cron.php
 * 
 * DOCKER-CRON:
 * docker exec sgit_php php /var/www/html/bots/scheduler/scheduler_cron.php
 * 
 * WINDOWS TASK SCHEDULER:
 * cd C:\xampp\htdocs\Education\docker && docker exec sgit_php php /var/www/html/bots/scheduler/scheduler_cron.php
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 11.12.2025
 * ============================================================================
 */

// Nur CLI erlauben
if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

// Scheduler laden
require_once __DIR__ . '/BotScheduler.php';

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║           sgiT Bot Scheduler - Cron Job                   ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$scheduler = new BotScheduler();

// Status anzeigen
$status = $scheduler->getStatus();
echo "📊 Status:\n";
echo "   - Jobs gesamt: {$status['total_jobs']}\n";
echo "   - Jobs aktiv: {$status['enabled_jobs']}\n";
echo "   - Jobs fällig: {$status['due_jobs']}\n";
echo "   - Zeitzone: {$status['settings']['timezone']}\n\n";

// Fällige Jobs ausführen
if ($status['due_jobs'] > 0) {
    echo "🚀 Führe fällige Jobs aus...\n\n";
    $results = $scheduler->runDueJobs();
    
    echo "\n📋 Ergebnisse:\n";
    foreach ($results as $result) {
        $icon = $result['success'] ? '✅' : '❌';
        $name = $result['name'] ?? $result['bot'];
        $info = $result['success'] 
            ? "({$result['duration_ms']}ms)" 
            : "Fehler: {$result['error']}";
        echo "   $icon $name $info\n";
    }
} else {
    echo "😴 Keine fälligen Jobs.\n";
}

echo "\n✅ Scheduler-Check abgeschlossen: " . date('Y-m-d H:i:s') . "\n\n";
