<?php
/**
 * sgiT Education - Zeichnung speichern
 * Speichert Canvas als PNG und vergibt Sats
 * Version: 2.0 - Fixed wallet_child_id
 */

session_start();
header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt']);
    exit;
}

$userId = $_SESSION['user_id'];
$userAge = $_SESSION['user_age'] ?? 10;
$childId = $_SESSION['wallet_child_id'] ?? 0; // WICHTIG: wallet_child_id!

// JSON Input
$input = json_decode(file_get_contents('php://input'), true);
$imageData = $input['image'] ?? null;
$tutorial = $input['tutorial'] ?? null;
$mode = $input['mode'] ?? 'free';

if (!$imageData) {
    echo json_encode(['success' => false, 'error' => 'Keine Bilddaten']);
    exit;
}

// Base64 zu Bild
$imageData = str_replace('data:image/png;base64,', '', $imageData);
$imageData = str_replace(' ', '+', $imageData);
$imageDecoded = base64_decode($imageData);

if (!$imageDecoded) {
    echo json_encode(['success' => false, 'error' => 'Ungültige Bilddaten']);
    exit;
}

// Verzeichnis für User erstellen
$userDir = __DIR__ . '/../uploads/drawings/' . $userId;
if (!is_dir($userDir)) {
    mkdir($userDir, 0755, true);
}

// Dateiname generieren
$filename = date('Y-m-d_H-i-s') . '_' . ($tutorial ?: 'free') . '.png';
$filepath = $userDir . '/' . $filename;

// Bild speichern
if (!file_put_contents($filepath, $imageDecoded)) {
    echo json_encode(['success' => false, 'error' => 'Konnte nicht speichern']);
    exit;
}

// Sats berechnen
$baseSats = 5; // Freies Zeichnen

if ($tutorial) {
    // Tutorial-Sats aus JSON laden
    $tutorialFile = __DIR__ . "/tutorials/{$tutorial}.json";
    if (file_exists($tutorialFile)) {
        $tutorialData = json_decode(file_get_contents($tutorialFile), true);
        $baseSats = $tutorialData['sats_reward'] ?? 10;
    } else {
        $baseSats = 10; // Standard für Tutorials
    }
}

// Bonus für jüngere Kinder (Motivation!)
if ($userAge <= 7) {
    $baseSats += 2;
}

// In drawings Datenbank speichern
try {
    $dbPath = __DIR__ . '/../AI/data/questions.db';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tabelle erstellen falls nicht vorhanden
    $db->exec("CREATE TABLE IF NOT EXISTS drawings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        filename TEXT NOT NULL,
        tutorial_id TEXT,
        sats_earned INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Eintrag speichern
    $stmt = $db->prepare("INSERT INTO drawings (user_id, filename, tutorial_id, sats_earned) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $filename, $tutorial, $baseSats]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
    exit;
}

// Wallet aktualisieren via WalletManager (richtige Methode!)
$satsAwarded = 0;
if ($childId > 0) {
    try {
        require_once __DIR__ . '/../wallet/WalletManager.php';
        $wallet = new WalletManager();
        
        // earnSats mit score=1, max=1 (100% = berechtigt)
        $result = $wallet->earnSats($childId, 1, 1, 'zeichnen_' . ($tutorial ?: 'free'));
        
        if ($result['success']) {
            $satsAwarded = $result['sats'] ?? $baseSats;
        }
    } catch (Exception $e) {
        // Wallet-Fehler loggen aber nicht abbrechen
        error_log("Wallet-Fehler Zeichnen: " . $e->getMessage());
    }
}

echo json_encode([
    'success' => true,
    'sats' => $satsAwarded > 0 ? $satsAwarded : $baseSats,
    'filename' => $filename,
    'message' => 'Zeichnung gespeichert!'
]);
