<?php
/**
 * ============================================================================
 * sgiT Education Platform - Shared Game Header
 * ============================================================================
 *
 * Gemeinsamer Boilerplate fuer alle Multiplayer-Spiele
 * Stellt Session, Wallet und User-Daten bereit
 *
 * Liefert folgende Variablen:
 *   $userName      - Name des eingeloggten Users
 *   $walletChildId - Wallet-ID des Users (0 = nicht eingeloggt)
 *   $userAvatar    - Emoji-Avatar des Users
 *   $userAge       - Alter des Users (Default: 10)
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 2026-02-14
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/version.php';
require_once dirname(__DIR__) . '/wallet/SessionManager.php';

$userName = '';
$walletChildId = 0;
$userAvatar = "\u{1F600}"; // Default emoji
$userAge = 10;

if (SessionManager::isLoggedIn()) {
    $childData = SessionManager::getChild();
    if ($childData) {
        $walletChildId = $childData['id'];
        $userName = $childData['name'];
        $userAvatar = $childData['avatar'] ?? "\u{1F600}";
        $userAge = $childData['age'] ?? 10;
    }
} elseif (isset($_SESSION['wallet_child_id'])) {
    $walletChildId = $_SESSION['wallet_child_id'];
    $userName = $_SESSION['user_name'] ?? $_SESSION['child_name'] ?? '';
    $userAvatar = $_SESSION['avatar'] ?? "\u{1F600}";
}
