<?php
/**
 * Kochen Session Update API
 * Speichert Fortschritt zwischen Fragen
 */
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';
$correct = $input['correct'] ?? false;
$sats = (int)($input['sats'] ?? 0);

$sessionKey = 'kochen_' . $type;

if (!isset($_SESSION[$sessionKey])) {
    $_SESSION[$sessionKey] = ['question' => 0, 'correct' => 0, 'total_sats' => 0];
}

if ($correct) {
    $_SESSION[$sessionKey]['correct']++;
    $_SESSION[$sessionKey]['total_sats'] += $sats;
}

echo json_encode([
    'success' => true,
    'session' => $_SESSION[$sessionKey]
]);
