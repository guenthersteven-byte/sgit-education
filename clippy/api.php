<?php
/**
 * ============================================================================
 * sgiT Education - Foxy REST API v1.2
 * ============================================================================
 * 
 * FIXES v1.2:
 * - user_name Parameter hinzugefügt
 * - Bessere Greeting mit Kontext
 * 
 * Endpoints:
 * - POST /chat - Nachricht senden
 * - GET /status - Ollama Status
 * - GET /greeting - Begrüßung holen
 * - GET /ping - Health Check
 * - GET /stats - Chat Statistiken
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.2
 * @date 04.12.2025
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/ClippyChat.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'ping';
$clippy = new ClippyChat();

try {
    switch ($action) {
        
        case 'chat':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST required');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['message'])) {
                throw new Exception('Message required');
            }
            
            $message = substr(trim($input['message']), 0, 500);
            $age = intval($input['age'] ?? 10);
            $module = $input['module'] ?? null;
            $userName = $input['user_name'] ?? null; // NEU: Username
            $currentQuestion = $input['current_question'] ?? null;
            $history = $input['history'] ?? [];
            
            // Validierung
            $age = max(5, min(21, $age));
            
            $result = $clippy->chat($message, $age, $module, $userName, $currentQuestion, $history);
            
            echo json_encode($result);
            break;
            
        case 'greeting':
            $age = intval($_GET['age'] ?? 10);
            $module = $_GET['module'] ?? null;
            $userName = $_GET['user_name'] ?? null; // NEU: Username
            
            if (empty($module)) $module = null;
            if (empty($userName)) $userName = null;
            
            $greeting = $clippy->getGreeting($age, $module, $userName);
            
            echo json_encode([
                'success' => true,
                'message' => $greeting,
                'module' => $module,
                'user_name' => $userName
            ]);
            break;
            
        case 'status':
            $status = $clippy->checkOllamaStatus();
            echo json_encode($status);
            break;
            
        case 'stats':
            $stats = $clippy->getStats();
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'ping':
        default:
            echo json_encode([
                'success' => true,
                'message' => 'Foxy API v1.2 🦊',
                'timestamp' => date('c')
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
