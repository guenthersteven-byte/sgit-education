<?php
/**
 * ============================================================================
 * sgiT Education Platform - Joker API
 * ============================================================================
 * 
 * Verwaltet die 50/50 Joker pro User (Wallet-User)
 * 
 * Endpoints:
 * - GET  ?action=status     → Joker-Count + letztes Refill
 * - POST action=use         → Joker verbrauchen (-1)
 * - POST action=refill      → Manuelles Auffüllen (Admin)
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 2025-12-12
 */

header('Content-Type: application/json');

// Session für User-ID
session_start();

// Wallet-User ID aus Session holen
$walletUserId = $_SESSION['wallet_user_id'] ?? null;

if (!$walletUserId) {
    echo json_encode([
        'success' => true,  // Erfolg, aber Gast-Modus
        'error' => null,
        'code' => 'GUEST_MODE',
        'joker_count' => 3,
        'wallet_user' => false,  // JS erwartet dieses Flag
        'is_guest' => true
    ]);
    exit;
}

// Datenbank-Verbindung
$dbPath = __DIR__ . '/../wallet/wallet.db';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Wallet-DB nicht gefunden']);
    exit;
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'DB-Fehler: ' . $e->getMessage()]);
    exit;
}

// Action bestimmen
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

switch ($action) {
    
    // ================================================================
    // GET: Joker-Status abrufen
    // ================================================================
    case 'status':
        // Erst alten Stand holen (um zu wissen ob Refill passiert)
        $stmt = $db->prepare("SELECT joker_last_refill FROM child_wallets WHERE id = ?");
        $stmt->execute([$walletUserId]);
        $oldRefill = $stmt->fetchColumn();
        $wasRefilled = ($oldRefill !== date('Y-m-d'));
        
        // Tägliches Refill prüfen & durchführen
        checkDailyRefill($db, $walletUserId);
        
        // Aktuellen Stand holen
        $stmt = $db->prepare("SELECT joker_count, joker_last_refill FROM child_wallets WHERE id = ?");
        $stmt->execute([$walletUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'User nicht gefunden']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'joker_count' => (int)$user['joker_count'],
            'last_refill' => $user['joker_last_refill'],
            'wallet_user' => true,  // BUG-045: JS erwartet dieses Flag
            'is_guest' => false,
            'refilled' => $wasRefilled  // Wurde GERADE aufgefüllt?
        ]);
        break;
    
    // ================================================================
    // POST: Joker verbrauchen
    // ================================================================
    case 'use':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        
        // Aktuellen Stand holen
        $stmt = $db->prepare("SELECT joker_count FROM child_wallets WHERE id = ?");
        $stmt->execute([$walletUserId]);
        $current = $stmt->fetchColumn();
        
        if ($current <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Keine Joker mehr!',
                'code' => 'NO_JOKERS',
                'joker_count' => 0
            ]);
            exit;
        }
        
        // Joker abziehen
        $stmt = $db->prepare("UPDATE child_wallets SET joker_count = joker_count - 1 WHERE id = ?");
        $stmt->execute([$walletUserId]);
        
        echo json_encode([
            'success' => true,
            'joker_count' => $current - 1,
            'message' => 'Joker verwendet!'
        ]);
        break;
    
    // ================================================================
    // POST: Manuelles Refill (Admin oder täglich)
    // ================================================================
    case 'refill':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        
        $stmt = $db->prepare("
            UPDATE child_wallets 
            SET joker_count = 3, joker_last_refill = DATE('now') 
            WHERE id = ?
        ");
        $stmt->execute([$walletUserId]);
        
        echo json_encode([
            'success' => true,
            'joker_count' => 3,
            'message' => 'Joker aufgefüllt!'
        ]);
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Unbekannte Action']);
}

/**
 * Prüft ob heute schon Refill war, wenn nicht → auffüllen
 */
function checkDailyRefill(PDO $db, int $userId): void {
    $today = date('Y-m-d');
    
    $stmt = $db->prepare("SELECT joker_last_refill FROM child_wallets WHERE id = ?");
    $stmt->execute([$userId]);
    $lastRefill = $stmt->fetchColumn();
    
    // Wenn noch nie oder nicht heute → Refill
    if (!$lastRefill || $lastRefill !== $today) {
        $stmt = $db->prepare("
            UPDATE child_wallets 
            SET joker_count = 3, joker_last_refill = ? 
            WHERE id = ?
        ");
        $stmt->execute([$today, $userId]);
    }
}
