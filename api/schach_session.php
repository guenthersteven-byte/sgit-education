<?php
/**
 * Schach Session Update API
 * Speichert Fortschritt und vergibt Sats
 */
require_once __DIR__ . '/_security.php';
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/wallet/WalletManager.php';

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';
$correct = $input['correct'] ?? false;
$sats = (int)($input['sats'] ?? 0);
$childId = (int)($input['child_id'] ?? 0);

$sessionKey = 'schach_quiz';

if (!isset($_SESSION[$sessionKey])) {
    echo json_encode(['success' => false, 'error' => 'No session']);
    exit;
}

// Fortschritt aktualisieren
$_SESSION[$sessionKey]['current']++;
if ($correct) {
    $_SESSION[$sessionKey]['correct']++;
    $_SESSION[$sessionKey]['total_sats'] += $sats;
    
    // Sats vergeben wenn User eingeloggt
    if ($childId > 0 && $sats > 0) {
        try {
            $wallet = new WalletManager();
            $wallet->addTransaction($childId, $sats, 'earned', 'Schach ' . $type);
        } catch (Exception $e) {
            // Silent fail
        }
    }
}

echo json_encode([
    'success' => true,
    'session' => $_SESSION[$sessionKey]
]);
