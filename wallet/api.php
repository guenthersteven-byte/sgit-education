<?php
/**
 * ============================================================================
 * sgiT Education - Wallet API
 * ============================================================================
 * 
 * JSON API für die Integration in die Lern-Module.
 * Wird aufgerufen wenn eine Session beendet wird.
 * 
 * Endpoints:
 * - POST /earn     - Sats für Session verdienen
 * - GET  /balance  - Kind-Balance abfragen
 * - GET  /config   - Reward-Konfiguration abfragen
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 02.12.2025
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS für CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/WalletManager.php';
require_once __DIR__ . '/AchievementManager.php';

// ============================================================================
// RESPONSE HELPER
// ============================================================================

function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function errorResponse(string $message, int $statusCode = 400): void {
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

// ============================================================================
// WALLET MANAGER INITIALISIEREN
// ============================================================================

try {
    $wallet = new WalletManager();
} catch (Exception $e) {
    error_log("Wallet API Init Error: " . $e->getMessage());
    errorResponse('Wallet-System nicht initialisiert', 500);
}

// ============================================================================
// ROUTING
// ============================================================================

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    
    // ========================================================================
    // EARN: Sats für Session verdienen
    // ========================================================================
    case 'earn':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            errorResponse('POST required', 405);
        }
        
        // Parameter aus POST oder JSON Body
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $childId = (int) ($input['child_id'] ?? 0);
        $score = (int) ($input['score'] ?? 0);
        $maxScore = (int) ($input['max_score'] ?? 10);
        $module = $input['module'] ?? 'unknown';
        $sessionId = $input['session_id'] ?? null;
        
        if ($childId <= 0) {
            errorResponse('child_id erforderlich');
        }
        
        $result = $wallet->earnSats($childId, $score, $maxScore, $module, $sessionId);
        jsonResponse($result);
        break;
    
    // ========================================================================
    // BALANCE: Kind-Balance abfragen
    // ========================================================================
    case 'balance':
        $childId = (int) ($_GET['child_id'] ?? 0);
        
        if ($childId <= 0) {
            errorResponse('child_id erforderlich');
        }
        
        $child = $wallet->getChildWallet($childId);
        
        if (!$child) {
            errorResponse('Kind nicht gefunden', 404);
        }
        
        jsonResponse([
            'success' => true,
            'child_id' => $childId,
            'name' => $child['child_name'],
            'avatar' => $child['avatar'],
            'balance_sats' => $child['balance_sats'],
            'total_earned' => $child['total_earned'],
            'current_streak' => $child['current_streak'],
            'earned_today' => $wallet->getEarnedToday($childId)
        ]);
        break;
    
    // ========================================================================
    // CHILDREN: Alle Kinder auflisten
    // ========================================================================
    case 'children':
        $children = $wallet->getChildWallets();
        
        jsonResponse([
            'success' => true,
            'count' => count($children),
            'children' => array_map(function($c) {
                return [
                    'id' => $c['id'],
                    'name' => $c['child_name'],
                    'avatar' => $c['avatar'],
                    'balance_sats' => $c['balance_sats'],
                    'current_streak' => $c['current_streak']
                ];
            }, $children)
        ]);
        break;
    
    // ========================================================================
    // CALCULATE: Reward vorausberechnen (ohne Ausführung)
    // ========================================================================
    case 'calculate':
        $score = (int) ($_GET['score'] ?? 0);
        $maxScore = (int) ($_GET['max_score'] ?? 10);
        $module = $_GET['module'] ?? 'unknown';
        
        $result = $wallet->calculateReward($score, $maxScore, $module);
        jsonResponse([
            'success' => true,
            'calculation' => $result
        ]);
        break;
    
    // ========================================================================
    // CONFIG: Reward-Konfiguration abfragen
    // ========================================================================
    case 'config':
        $configKeys = [
            'base_sats_per_session',
            'sats_per_correct_answer',
            'perfect_score_bonus',
            'daily_login_bonus',
            'daily_earn_limit',
            'min_score_percent',
            'system_enabled'
        ];
        
        $config = [];
        foreach ($configKeys as $key) {
            $config[$key] = $wallet->getConfig($key);
        }
        
        jsonResponse([
            'success' => true,
            'config' => $config
        ]);
        break;
    
    // ========================================================================
    // STATS: Gesamtstatistiken
    // ========================================================================
    case 'stats':
        $stats = $wallet->getStats();
        jsonResponse([
            'success' => true,
            'stats' => $stats
        ]);
        break;
    
    // ========================================================================
    // TRANSACTIONS: Transaktions-Historie
    // ========================================================================
    case 'transactions':
        $childId = (int) ($_GET['child_id'] ?? 0);
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
        
        if ($childId > 0) {
            $transactions = $wallet->getTransactions($childId, $limit);
        } else {
            $transactions = $wallet->getAllTransactions($limit);
        }
        
        jsonResponse([
            'success' => true,
            'count' => count($transactions),
            'transactions' => $transactions
        ]);
        break;
    
    // ========================================================================
    // ACHIEVEMENTS: Achievement-Liste für Kind
    // ========================================================================
    case 'achievements':
        $childId = (int) ($_GET['child_id'] ?? 0);
        
        if ($childId <= 0) {
            errorResponse('child_id erforderlich');
        }
        
        try {
            $achievementMgr = new AchievementManager();
            $stats = $achievementMgr->getAchievementStats($childId);
            $unlocked = $achievementMgr->getUnlocked($childId);
            
            jsonResponse([
                'success' => true,
                'child_id' => $childId,
                'stats' => $stats,
                'unlocked' => $unlocked
            ]);
        } catch (Exception $e) {
            error_log("Achievement API Error: " . $e->getMessage());
            errorResponse('Achievement-System Fehler', 500);
        }
        break;
    
    // ========================================================================
    // ACHIEVEMENTS_PROGRESS: Fortschritt für alle Achievements
    // ========================================================================
    case 'achievements_progress':
        $childId = (int) ($_GET['child_id'] ?? 0);
        
        if ($childId <= 0) {
            errorResponse('child_id erforderlich');
        }
        
        try {
            $achievementMgr = new AchievementManager();
            $progress = $achievementMgr->getProgress($childId);
            
            jsonResponse([
                'success' => true,
                'child_id' => $childId,
                'progress' => $progress
            ]);
        } catch (Exception $e) {
            error_log("Achievement API Error: " . $e->getMessage());
            errorResponse('Achievement-System Fehler', 500);
        }
        break;
    
    // ========================================================================
    // CHECK_ACHIEVEMENTS: Achievements prüfen und freischalten
    // ========================================================================
    case 'check_achievements':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            errorResponse('POST required', 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $childId = (int) ($input['child_id'] ?? 0);
        $context = $input['context'] ?? [];
        
        if ($childId <= 0) {
            errorResponse('child_id erforderlich');
        }
        
        try {
            $achievementMgr = new AchievementManager();
            $newlyUnlocked = $achievementMgr->checkAndUnlock($childId, $context);
            
            jsonResponse([
                'success' => true,
                'child_id' => $childId,
                'newly_unlocked' => $newlyUnlocked,
                'count' => count($newlyUnlocked),
                'total_reward' => array_sum(array_column($newlyUnlocked, 'reward_sats'))
            ]);
        } catch (Exception $e) {
            error_log("Achievement API Error: " . $e->getMessage());
            errorResponse('Achievement-System Fehler', 500);
        }
        break;
    
    // ========================================================================
    // SYNC_ACHIEVEMENTS: Alle erreichten Achievements freischalten
    // ========================================================================
    case 'sync_achievements':
        $childId = (int) ($_GET['child_id'] ?? $_POST['child_id'] ?? 0);
        
        if ($childId <= 0) {
            errorResponse('child_id erforderlich');
        }
        
        try {
            $achievementMgr = new AchievementManager();
            
            // Prüfe ALLE Achievements ohne speziellen Context
            $newlyUnlocked = $achievementMgr->checkAndUnlock($childId, [
                'force_check' => true,
                'just_completed_session' => true  // Trigger für Session-basierte
            ]);
            
            // Stats nach Sync
            $stats = $achievementMgr->getAchievementStats($childId);
            
            jsonResponse([
                'success' => true,
                'child_id' => $childId,
                'newly_unlocked' => $newlyUnlocked,
                'count' => count($newlyUnlocked),
                'total_reward' => array_sum(array_column($newlyUnlocked, 'reward_sats')),
                'stats_after' => $stats,
                'message' => count($newlyUnlocked) > 0 
                    ? count($newlyUnlocked) . ' Achievement(s) freigeschaltet!' 
                    : 'Keine neuen Achievements'
            ]);
        } catch (Exception $e) {
            error_log("Achievement API Error: " . $e->getMessage());
            errorResponse('Achievement-System Fehler', 500);
        }
        break;
    
    // ========================================================================
    // DEFAULT: API Info
    // ========================================================================
    default:
        jsonResponse([
            'api' => 'sgiT Education Wallet API',
            'version' => '1.2',
            'endpoints' => [
                'POST /api.php?action=earn' => 'Sats für Session verdienen (child_id, score, max_score, module)',
                'GET  /api.php?action=balance&child_id=X' => 'Kind-Balance abfragen',
                'GET  /api.php?action=children' => 'Alle Kinder auflisten',
                'GET  /api.php?action=calculate&score=X&max_score=Y' => 'Reward vorausberechnen',
                'GET  /api.php?action=config' => 'Reward-Konfiguration',
                'GET  /api.php?action=stats' => 'Gesamtstatistiken',
                'GET  /api.php?action=transactions' => 'Transaktions-Historie',
                'GET  /api.php?action=achievements&child_id=X' => 'Achievements für Kind',
                'GET  /api.php?action=achievements_progress&child_id=X' => 'Achievement-Fortschritt',
                'GET  /api.php?action=sync_achievements&child_id=X' => 'Alle erreichten Achievements freischalten',
                'POST /api.php?action=check_achievements' => 'Achievements prüfen (child_id, context)'
            ],
            'status' => $wallet->getConfig('system_enabled') ? 'active' : 'disabled'
        ]);
}
