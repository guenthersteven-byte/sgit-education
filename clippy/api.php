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
            $userName = $input['user_name'] ?? null;
            $currentQuestion = $input['current_question'] ?? null;
            $history = $input['history'] ?? [];
            
            // Validierung
            $age = max(5, min(21, $age));
            
            $result = $clippy->chat($message, $age, $module, $userName, $currentQuestion, $history);
            
            echo json_encode($result);
            break;
        
        // ================================================================
        // NEUE GEMMA-FEATURES v2.0
        // ================================================================
        
        case 'explain':
            // 🎓 Erklärt warum eine Antwort richtig/falsch ist
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST required');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['question']) || empty($input['correct_answer'])) {
                throw new Exception('question and correct_answer required');
            }
            
            $question = substr(trim($input['question']), 0, 500);
            $correctAnswer = trim($input['correct_answer']);
            $userAnswer = trim($input['user_answer'] ?? $correctAnswer);
            $age = max(5, min(21, intval($input['age'] ?? 10)));
            $userName = $input['user_name'] ?? null;
            
            $result = $clippy->explainAnswer($question, $correctAnswer, $userAnswer, $age, $userName);
            echo json_encode($result);
            break;
            
        case 'hint':
            // 💡 Gibt einen Hinweis ohne Lösung
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST required');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['question']) || empty($input['correct_answer']) || empty($input['options'])) {
                throw new Exception('question, correct_answer and options required');
            }
            
            $question = substr(trim($input['question']), 0, 500);
            $correctAnswer = trim($input['correct_answer']);
            $options = $input['options'];
            $age = max(5, min(21, intval($input['age'] ?? 10)));
            $userName = $input['user_name'] ?? null;
            
            $result = $clippy->getHint($question, $correctAnswer, $options, $age, $userName);
            echo json_encode($result);
            break;
            
        case 'ask':
            // ❓ Beantwortet Wissensfragen
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST required');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['question'])) {
                throw new Exception('question required');
            }
            
            $question = substr(trim($input['question']), 0, 500);
            $age = max(5, min(21, intval($input['age'] ?? 10)));
            $userName = $input['user_name'] ?? null;
            $module = $input['module'] ?? null;
            
            $result = $clippy->askKnowledge($question, $age, $userName, $module);
            echo json_encode($result);
            break;
        
        // ================================================================
        // ENDE GEMMA-FEATURES
        // ================================================================
            
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
    error_log("Clippy API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Ein Fehler ist aufgetreten'
    ]);
}
