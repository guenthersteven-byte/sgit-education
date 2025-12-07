<?php
/**
 * sgiT Education - Bot Control API
 * 
 * Steuert Bots (Start/Stop) via AJAX
 * 
 * @version 1.0
 * @date 08.12.2025
 */

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$botType = $_GET['bot'] ?? '';

// Bot-Typen zu Stop-Dateien mapping
$stopFiles = [
    'ai' => __DIR__ . '/logs/STOP_AI_BOT',
    'function' => __DIR__ . '/logs/STOP_FUNCTION_BOT',
    'security' => __DIR__ . '/logs/STOP_SECURITY_BOT',
    'load' => __DIR__ . '/logs/STOP_LOAD_BOT',
    'dependency' => __DIR__ . '/logs/STOP_DEPENDENCY_BOT'
];

$botNames = [
    'ai' => 'AI Generator Bot',
    'function' => 'Function Test Bot',
    'security' => 'Security Bot',
    'load' => 'Load Test Bot',
    'dependency' => 'Dependency Check Bot'
];

// Validierung
if (!isset($stopFiles[$botType])) {
    echo json_encode(['success' => false, 'message' => 'Unbekannter Bot-Typ: ' . $botType]);
    exit;
}

$stopFile = $stopFiles[$botType];
$botName = $botNames[$botType];

switch ($action) {
    case 'stop':
        // Stop-Flag setzen
        file_put_contents($stopFile, date('Y-m-d H:i:s') . ' - Stop requested via Dashboard');
        echo json_encode([
            'success' => true,
            'message' => $botName . ' wird gestoppt...',
            'bot' => $botType,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;
        
    case 'clear':
        // Stop-Flag löschen (Bot kann wieder starten)
        if (file_exists($stopFile)) {
            unlink($stopFile);
        }
        echo json_encode([
            'success' => true,
            'message' => $botName . ' kann wieder gestartet werden',
            'bot' => $botType
        ]);
        break;
        
    case 'status':
        // Status abfragen
        $stopped = file_exists($stopFile);
        echo json_encode([
            'success' => true,
            'bot' => $botType,
            'name' => $botName,
            'stopped' => $stopped,
            'status' => $stopped ? 'stopped' : 'ready'
        ]);
        break;
        
    case 'status_all':
        // Status aller Bots
        $statuses = [];
        foreach ($stopFiles as $type => $file) {
            $statuses[$type] = [
                'name' => $botNames[$type],
                'stopped' => file_exists($file),
                'status' => file_exists($file) ? 'stopped' : 'ready'
            ];
        }
        echo json_encode(['success' => true, 'bots' => $statuses]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Unbekannte Aktion: ' . $action]);
}
