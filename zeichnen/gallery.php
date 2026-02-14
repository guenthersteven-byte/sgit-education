<?php
/**
 * sgiT Education - Zeichnungen Galerie v2.0
 * Zeigt gespeicherte Kunstwerke des Users
 * 
 * NEUERUNGEN v2.0 (11.12.2025):
 * - Bilder laden und weiterbearbeiten
 * - Bilder löschen
 * - Dark Theme
 * 
 * @version 2.0
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /adaptive_learning.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Künstler';

// AJAX: Bild löschen
if (isset($_POST['delete_id'])) {
    header('Content-Type: application/json');
    try {
        $dbPath = __DIR__ . '/../AI/data/questions.db';
        $db = new PDO('sqlite:' . $dbPath);
        
        $stmt = $db->prepare("SELECT filename FROM drawings WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['delete_id'], $userId]);
        $drawing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($drawing) {
            // Datei löschen
            $filePath = __DIR__ . '/../uploads/drawings/' . $userId . '/' . $drawing['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // DB-Eintrag löschen
            $stmt = $db->prepare("DELETE FROM drawings WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['delete_id'], $userId]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Nicht gefunden']);
        }
    } catch (Exception $e) {
        error_log("Gallery Delete Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Ein Fehler ist aufgetreten']);
    }
    exit;
}

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
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
        :root {
            --sgit-dark: #1A3503;
            --sgit-green: #43D240;
            --sgit-orange: #E86F2C;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: #1a1a1a;
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
            flex-wrap: wrap;
            gap: 15px;
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
        .header-actions { display: flex; gap: 10px; }
        .header .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .header .btn-primary {
            background: var(--sgit-green);
            color: var(--sgit-dark);
        }
        .header .btn-secondary {
            background: #444;
            color: white;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .gallery-item {
            background: #2a2a2a;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 2px solid transparent;
        }
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(67, 210, 64, 0.2);
            border-color: var(--sgit-green);
        }
        .gallery-item .image-container {
            position: relative;
            cursor: pointer;
        }
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        .gallery-item .overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .gallery-item:hover .overlay { opacity: 1; }
        .gallery-item .overlay button {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 0.9em;
        }
        .gallery-item .overlay .edit-btn {
            background: var(--sgit-green);
            color: var(--sgit-dark);
        }
        .gallery-item .overlay .delete-btn {
            background: #e74c3c;
            color: white;
        }
        .gallery-item .info {
            padding: 15px;
        }
        .gallery-item .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .gallery-item .date {
            color: #888;
            font-size: 0.85em;
        }
        .gallery-item .sats {
            background: var(--sgit-orange);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .gallery-item .tutorial-badge {
            background: var(--sgit-dark);
            color: var(--sgit-green);
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-top: 8px;
            display: inline-block;
        }
        .empty-gallery {
            text-align: center;
            padding: 80px 20px;
            color: #888;
        }
        .empty-gallery h2 { margin-bottom: 15px; color: white; font-size: 2em; }
        .empty-gallery p { margin-bottom: 25px; font-size: 1.1em; }
        .empty-gallery a {
            display: inline-block;
            background: var(--sgit-green);
            color: var(--sgit-dark);
            padding: 15px 35px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1em;
        }
        /* Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.95);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .lightbox.show { display: flex; }
        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
            box-shadow: 0 0 50px rgba(67, 210, 64, 0.3);
        }
        .lightbox .close {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 40px;
            color: white;
            cursor: pointer;
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
        <div class="header-actions">
            <a href="canvas.php?mode=free" class="btn btn-primary">➕ Neues Bild</a>
            <a href="index.php" class="btn btn-secondary">← Zurück</a>
        </div>
    </div>
    
    <?php if (empty($drawings)): ?>
    <div class="empty-gallery">
        <h2>🎨 Noch keine Kunstwerke!</h2>
        <p>Erstelle dein erstes Meisterwerk und es erscheint hier.</p>
        <a href="canvas.php?mode=free">✨ Jetzt zeichnen!</a>
    </div>
    <?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($drawings as $drawing): ?>
        <div class="gallery-item" data-id="<?= $drawing['id'] ?>">
            <div class="image-container" onclick="openLightbox('/uploads/drawings/<?= $userId ?>/<?= htmlspecialchars($drawing['filename']) ?>')">
                <img src="/uploads/drawings/<?= $userId ?>/<?= htmlspecialchars($drawing['filename']) ?>" 
                     alt="Zeichnung" loading="lazy"
                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22><rect fill=%22%232a2a2a%22 width=%22200%22 height=%22200%22/><text x=%2250%%22 y=%2250%%22 text-anchor=%22middle%22 fill=%22%23666%22 font-size=%2240%22>🖼️</text></svg>'">
                <div class="overlay">
                    <button class="edit-btn" onclick="event.stopPropagation(); editDrawing('<?= htmlspecialchars($drawing['filename']) ?>')">✏️ Bearbeiten</button>
                    <button class="delete-btn" onclick="event.stopPropagation(); deleteDrawing(<?= $drawing['id'] ?>)">🗑️ Löschen</button>
                </div>
            </div>
            <div class="info">
                <div class="info-row">
                    <span class="date">
                        <?= date('d.m.Y H:i', strtotime($drawing['created_at'])) ?>
                    </span>
                    <span class="sats">+<?= $drawing['sats_earned'] ?> Sats</span>
                </div>
                <?php if (!empty($drawing['tutorial_id'])): ?>
                <span class="tutorial-badge">📚 <?= htmlspecialchars($drawing['tutorial_id']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Lightbox -->
    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <span class="close">&times;</span>
        <img src="" id="lightboxImg" onclick="event.stopPropagation()">
    </div>
    
    <script>
        function openLightbox(src) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightbox').classList.add('show');
        }
        
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('show');
        }
        
        function editDrawing(filename) {
            // Bild im Canvas öffnen zum Weiterbearbeiten
            window.location.href = 'canvas.php?mode=edit&load=' + encodeURIComponent(filename);
        }
        
        function deleteDrawing(id) {
            if (!confirm('Wirklich löschen? Das kann nicht rückgängig gemacht werden!')) return;
            
            fetch('gallery.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'delete_id=' + id
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`[data-id="${id}"]`).remove();
                    // Wenn keine Bilder mehr, Seite neu laden
                    if (document.querySelectorAll('.gallery-item').length === 0) {
                        location.reload();
                    }
                } else {
                    alert('Fehler: ' + (data.error || 'Unbekannt'));
                }
            });
        }
        
        // ESC schließt Lightbox
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeLightbox();
        });
    </script>
</body>
</html>
