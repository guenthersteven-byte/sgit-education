<?php
/**
 * sgiT Education - Bot Runner v1.0
 * 
 * Zentraler Einstiegspunkt für alle Test-Bots
 * Kann Bots einzeln oder als Suite starten
 * 
 * Nutzung via CLI:
 *   php bot_runner.php --bot=ai_generator --mode=quick
 *   php bot_runner.php --bot=all --mode=full
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 30.11.2025
 */

// Autoload Bots
require_once __DIR__ . '/bot_logger.php';

// Bot-Definitionen
$availableBots = [
    'ai_generator' => [
        'file' => __DIR__ . '/tests/AIGeneratorBot.php',
        'class' => 'AIGeneratorBot',
        'name' => 'AI Generator Bot',
        'description' => 'Generiert AI-Fragen für alle Module'
    ],
    'function_test' => [
        'file' => __DIR__ . '/tests/FunctionTestBot.php',
        'class' => 'FunctionTestBot',
        'name' => 'Function Test Bot',
        'description' => 'Testet alle Modul-Funktionen'
    ],
    'load_test' => [
        'file' => __DIR__ . '/tests/LoadTestBot.php',
        'class' => 'LoadTestBot',
        'name' => 'Load Test Bot',
        'description' => 'Simuliert mehrere gleichzeitige User'
    ],
    'security' => [
        'file' => __DIR__ . '/tests/SecurityBot.php',
        'class' => 'SecurityBot',
        'name' => 'Security Bot',
        'description' => 'Prüft Sicherheitslücken'
    ],
    'dependency' => [
        'file' => __DIR__ . '/tests/DependencyCheckBot.php',
        'class' => 'DependencyCheckBot',
        'name' => 'Dependency Check Bot',
        'description' => 'Analysiert PHP-Abhängigkeiten und findet toten Code'
    ]
];

/**
 * Führt einen einzelnen Bot aus
 */
function runBot($botKey, $mode = 'quick', $options = []) {
    global $availableBots;
    
    if (!isset($availableBots[$botKey])) {
        return ['error' => "Bot '$botKey' nicht gefunden"];
    }
    
    $botDef = $availableBots[$botKey];
    
    if (isset($botDef['status']) && $botDef['status'] === 'TODO') {
        return ['error' => "Bot '{$botDef['name']}' noch nicht implementiert"];
    }
    
    if (!file_exists($botDef['file'])) {
        return ['error' => "Bot-Datei nicht gefunden: {$botDef['file']}"];
    }
    
    require_once $botDef['file'];
    
    $className = $botDef['class'];
    if (!class_exists($className)) {
        return ['error' => "Bot-Klasse '$className' nicht gefunden"];
    }
    
    $bot = new $className($options);
    
    switch ($mode) {
        case 'quick':
            return method_exists($bot, 'quickTest') 
                ? $bot->quickTest() 
                : $bot->run();
        case 'full':
            return method_exists($bot, 'fullTest') 
                ? $bot->fullTest() 
                : $bot->run();
        case 'stress':
            return method_exists($bot, 'stressTest') 
                ? $bot->stressTest() 
                : $bot->run();
        default:
            return $bot->run();
    }
}

/**
 * Führt alle Bots aus
 */
function runAllBots($mode = 'quick') {
    global $availableBots;
    
    $results = [];
    
    foreach ($availableBots as $key => $def) {
        if (isset($def['status']) && $def['status'] === 'TODO') {
            $results[$key] = ['skipped' => true, 'reason' => 'Not implemented'];
            continue;
        }
        
        echo "\n🤖 Starte: {$def['name']}...\n";
        $results[$key] = runBot($key, $mode);
    }
    
    return $results;
}

// ============================================================
// CLI-Modus
// ============================================================
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['bot:', 'mode:', 'help']);
    
    if (isset($options['help']) || empty($options['bot'])) {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║           sgiT Education - Bot Runner v1.0                ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";
        echo "Nutzung:\n";
        echo "  php bot_runner.php --bot=<bot_name> --mode=<mode>\n\n";
        echo "Verfügbare Bots:\n";
        foreach ($availableBots as $key => $def) {
            $status = isset($def['status']) ? " [{$def['status']}]" : "";
            echo "  - $key: {$def['description']}$status\n";
        }
        echo "  - all: Alle Bots nacheinander\n\n";
        echo "Modi:\n";
        echo "  - quick:  Schnelltest (wenige Tests)\n";
        echo "  - full:   Vollständiger Test\n";
        echo "  - stress: Stress-Test (viele Wiederholungen)\n\n";
        echo "Beispiele:\n";
        echo "  php bot_runner.php --bot=ai_generator --mode=quick\n";
        echo "  php bot_runner.php --bot=all --mode=full\n";
        echo "\n";
        exit(0);
    }
    
    $botName = $options['bot'];
    $mode = $options['mode'] ?? 'quick';
    
    echo "\n🚀 sgiT Bot Runner\n";
    echo "─────────────────────────────────\n";
    
    if ($botName === 'all') {
        $results = runAllBots($mode);
    } else {
        $results = runBot($botName, $mode);
    }
    
    echo "\n─────────────────────────────────\n";
    echo "✅ Fertig! Siehe bot_summary.php für Details.\n\n";
    
    exit(0);
}

// ============================================================
// Web-Modus (Redirect zur Summary)
// ============================================================
header('Location: bot_summary.php');
exit;
