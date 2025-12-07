<?php
/**
 * sgiT Education - Zeichnungen Galerie
 * Zeigt gespeicherte Kunstwerke des Users
 * Version: 1.0
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /adaptive_learning.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Künstler';

// Zeichnungen aus DB laden
$drawings = [];
try {
    $dbPath = __DIR__ . '/../AI/data/questions.db';
    $db = new PDO('sqlite:' . $dbPath);
    
    $stmt = $db->prepare("SELECT * FROM drawings WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$userId]);
    $drawings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tabelle existiert noch nicht
}

// Gesamte Sats aus Zeichnungen
$totalSats = array_sum(array_column($drawings, 'sats_earned'));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🖼️ Meine Galerie - sgiT Education</title>
    <style>
        :root {
            --sgit-dark: #1A3503;
            --sgit-green: #43D240;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .header {
            background: var(--sgit-dark);
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 1.8em; }
        .header .stats {
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 10px;
        }
        .header .stats span {
            color: var(--sgit-green);
            font-weight: bold;
            font-size: 1.2em;
        }
        .header .back-btn {
            background: var(--sgit-green);
            color: var(--sgit-dark);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .gallery-item {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .gallery-item:hover {
            transform: scale(1.03);
        }
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 3px solid var(--sgit-green);
        }
        .gallery-item .info {
            padding: 15px;
        }
        .gallery-item .date {
            color: #666;
            font-size: 0.85em;
        }
        .gallery-item .sats {
            background: var(--sgit-green);
            color: var(--sgit-dark);
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .gallery-item .tutorial-badge {
            background: var(--sgit-dark);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        .empty-gallery {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-gallery h2 { margin-bottom: 15px; color: var(--sgit-dark); }
        .empty-gallery a {
            display: inline-block;
            margin-top: 20px;
            background: var(--sgit-green);
            color: var(--sgit-dark);
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🖼️ Meine Galerie</h1>
        <div class="stats">
            📊 <?= count($drawings) ?> Werke | 
            <span>+<?= $totalSats ?> Sats</span> verdient
        </div>
        <a href="index.php" class="back-btn">← Zurück</a>
    </div>
    
    <?php if (empty($drawings)): ?>
    <div class="empty-gallery">
        <h2>🎨 Noch keine Kunstwerke!</h2>
        <p>Erstelle dein erstes Meisterwerk und es erscheint hier.</p>
        <a href="canvas.php?mode=free">Jetzt zeichnen!</a>
    </div>
    <?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($drawings as $drawing): ?>
        <div class="gallery-item">
            <img src="/uploads/drawings/<?= $userId ?>/<?= htmlspecialchars($drawing['filename']) ?>" 
                 alt="Zeichnung" loading="lazy"
                 onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22><rect fill=%22%23f0f0f0%22 width=%22200%22 height=%22200%22/><text x=%2250%%22 y=%2250%%22 text-anchor=%22middle%22 fill=%22%23999%22>🖼️</text></svg>'">
            <div class="info">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span class="date">
                        <?= date('d.m.Y H:i', strtotime($drawing['created_at'])) ?>
                    </span>
                    <span class="sats">+<?= $drawing['sats_earned'] ?> Sats</span>
                </div>
                <?php if ($drawing['tutorial_id']): ?>
                <span class="tutorial-badge">📚 <?= htmlspecialchars($drawing['tutorial_id']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</body>
</html>
