<?php
/**
 * sgiT Education - Zeichnen-Modul
 * Speichert Zeichnungen und vergibt Satoshis
 * 
 * @version 1.0
 * @author Steven Günther / Claude AI
 * @date 07.12.2025
 */

header('Content-Type: application/json');
session_start();

// Prüfe Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt']);
    exit;
}

$userId = $_SESSION['user_id'];

// JSON-Daten lesen
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['image'])) {
    echo json_encode(['success' => false, 'error' => 'Kein Bild übermittelt']);
    exit;
}

// Base64 dekodieren
$imageData = $input['image'];
$imageData = str_replace('data:image/png;base64,', '', $imageData);
$imageData = str_replace(' ', '+', $imageData);
$decodedImage = base64_decode($imageData);

if ($decodedImage === false) {
    echo json_encode(['success' => false, 'error' => 'Ungültiges Bildformat']);
    exit;
}

// Verzeichnis für User erstellen
$userDir = __DIR__ . '/../uploads/drawings/' . $userId;
if (!is_dir($userDir)) {
    mkdir($userDir, 0755, true);
}

// Dateiname generieren
$filename = 'drawing_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.png';
$filepath = $userDir . '/' . $filename;

// Bild speichern
if (file_put_contents($filepath, $decodedImage) === false) {
    echo json_encode(['success' => false, 'error' => 'Speichern fehlgeschlagen']);
    exit;
}

// Thumbnail erstellen (optional)
$thumbnailPath = $userDir . '/thumb_' . $filename;
createThumbnail($filepath, $thumbnailPath, 150);

// Satoshis berechnen
$mode = $input['mode'] ?? 'free';
$satsReward = [
    'free' => 5,
    'tutorial' => 15,
    'shapes' => 10,
    'colors' => 8
][$mode] ?? 5;

// In Datenbank speichern
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../AI/data/questions.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tabelle erstellen falls nicht vorhanden
    $db->exec("CREATE TABLE IF NOT EXISTS drawings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        filename TEXT NOT NULL,
        thumbnail TEXT,
        mode TEXT DEFAULT 'free',
        sats_earned INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Zeichnung speichern
    $stmt = $db->prepare("INSERT INTO drawings (user_id, filename, thumbnail, mode, sats_earned) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $filename, 'thumb_' . $filename, $mode, $satsReward]);
    
    // Wallet aktualisieren (child_wallets Tabelle)
    $walletDb = new PDO('sqlite:' . __DIR__ . '/../wallet/' . $userId . '.db');
    $walletDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Balance erhöhen
    $walletDb->exec("UPDATE child_wallets SET 
        balance_sats = balance_sats + $satsReward,
        total_earned = total_earned + $satsReward
        WHERE id = 1");
    
    // Transaktion loggen
    $walletDb->exec("INSERT INTO transactions (wallet_id, type, amount_sats, description, created_at) 
        VALUES (1, 'earning', $satsReward, 'Zeichnung gespeichert ($mode)', datetime('now'))");
    
    echo json_encode([
        'success' => true,
        'sats' => $satsReward,
        'filename' => $filename,
        'message' => 'Zeichnung gespeichert!'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}

/**
 * Erstellt ein Thumbnail
 */
function createThumbnail($source, $destination, $maxSize) {
    if (!extension_loaded('gd')) return false;
    
    $info = getimagesize($source);
    if ($info === false) return false;
    
    $sourceImage = imagecreatefrompng($source);
    if (!$sourceImage) return false;
    
    $width = $info[0];
    $height = $info[1];
    
    // Skalierung berechnen
    $ratio = $width / $height;
    if ($width > $height) {
        $newWidth = $maxSize;
        $newHeight = $maxSize / $ratio;
    } else {
        $newHeight = $maxSize;
        $newWidth = $maxSize * $ratio;
    }
    
    // Neues Bild erstellen
    $thumb = imagecreatetruecolor((int)$newWidth, (int)$newHeight);
    
    // Transparenz erhalten
    imagealphablending($thumb, false);
    imagesavealpha($thumb, true);
    
    // Skalieren
    imagecopyresampled(
        $thumb, $sourceImage,
        0, 0, 0, 0,
        (int)$newWidth, (int)$newHeight,
        $width, $height
    );
    
    // Speichern
    imagepng($thumb, $destination);
    
    // Speicher freigeben
    imagedestroy($sourceImage);
    imagedestroy($thumb);
    
    return true;
}
?>
