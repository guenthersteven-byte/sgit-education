<?php
/**
 * ============================================================================
 * sgiT Education - Zeichnen Modul
 * ============================================================================
 * 
 * Hauptseite / Übersicht mit allen Tutorials
 * 15+ Tutorials für alle Altersgruppen
 * Kategorien: Grundformen, Tiere, Natur, Fortgeschritten
 * 
 * BUG-049 FIX: Design an Logik/Kochen Module angepasst
 * - Dunkles sgiT-Theme (#0d1f02 Hintergrund)
 * - Konsistentes Card-Design
 * - Einheitliche Navigation
 * 
 * @version 2.0 (BUG-049 Fix)
 * @date 08.12.2025
 * @author sgiT Solution Engineering & IT Services
 * ============================================================================
 */

session_start();
require_once dirname(__DIR__) . '/includes/version.php';

// Prüfen ob eingeloggt
if (!isset($_SESSION['user_id'])) {
    header('Location: /adaptive_learning.php');
    exit;
}

// User-Daten laden
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Künstler';
$userAge = $_SESSION['user_age'] ?? 10;

// Alle Tutorials laden
function loadAllTutorials($tutorialDir) {
    $tutorials = [];
    $files = glob($tutorialDir . '/*.json');
    
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data) {
            $data['filename'] = basename($file, '.json');
            $tutorials[] = $data;
        }
    }
    return $tutorials;
}

// Tutorials nach Kategorie gruppieren
function categorizeTutorials($tutorials, $userAge) {
    $categories = [
        'grundformen' => ['title' => 'Grundformen', 'icon' => '📐', 'tutorials' => []],
        'natur' => ['title' => 'Natur & Pflanzen', 'icon' => '🌿', 'tutorials' => []],
        'tiere' => ['title' => 'Tiere', 'icon' => '🐾', 'tutorials' => []],
        'fahrzeuge' => ['title' => 'Fahrzeuge & Technik', 'icon' => '🚀', 'tutorials' => []],
        'menschen' => ['title' => 'Menschen & Gesichter', 'icon' => '👤', 'tutorials' => []],
        'technik' => ['title' => 'Technik & Theorie', 'icon' => '🎓', 'tutorials' => []],
        'spass' => ['title' => 'Spaß & Kreativ', 'icon' => '🎉', 'tutorials' => []]
    ];
    
    $categoryMap = [
        'circle' => 'grundformen', 'square' => 'grundformen', 'star' => 'grundformen',
        'heart' => 'grundformen', 'cube3d' => 'grundformen',
        'tree' => 'natur', 'flower' => 'natur', 'sun' => 'natur', 
        'rainbow' => 'natur', 'landscape' => 'natur',
        'cat' => 'tiere', 'fish' => 'tiere', 'butterfly' => 'tiere', 'fox' => 'tiere',
        'car' => 'fahrzeuge', 'rocket' => 'fahrzeuge', 'house' => 'fahrzeuge',
        'portrait' => 'menschen', 'smiley' => 'menschen',
        'colors' => 'technik', 'shading' => 'technik',
    ];
    
    foreach ($tutorials as $tutorial) {
        $ageMin = $tutorial['age_min'] ?? 5;
        $ageMax = $tutorial['age_max'] ?? 99;
        
        if ($userAge < $ageMin || $userAge > $ageMax) {
            continue;
        }
        
        $id = $tutorial['filename'] ?? $tutorial['id'];
        $cat = $categoryMap[$id] ?? 'spass';
        $categories[$cat]['tutorials'][] = $tutorial;
    }
    
    return array_filter($categories, fn($c) => !empty($c['tutorials']));
}

$allTutorials = loadAllTutorials(__DIR__ . '/tutorials');
$categories = categorizeTutorials($allTutorials, $userAge);
$totalTutorials = array_sum(array_map(fn($c) => count($c['tutorials']), $categories));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎨 Zeichnen - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bg: #0d1f02;
            --card-bg: #1e3a08;
            --text: #ffffff;
            --text-muted: #a0a0a0;
            --orange: #F7931A;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, var(--primary) 100%);
            min-height: 100vh;
            color: var(--text);
            padding: 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        
        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--accent);
            text-decoration: none;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        .back-link:hover { text-decoration: underline; }
        
        /* Header */
        header { text-align: center; margin-bottom: 30px; }
        header h1 { font-size: 2.2rem; margin-bottom: 10px; }
        header h1 span { color: var(--accent); }
        .subtitle { color: var(--text-muted); margin-bottom: 15px; }
        
        .user-info {
            background: var(--card-bg);
            padding: 12px 20px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-top: 15px;
        }
        .user-info strong { color: var(--accent); }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 25px 0;
        }
        .quick-btn {
            background: var(--card-bg);
            border: 2px solid var(--accent);
            border-radius: 12px;
            padding: 14px 24px;
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .quick-btn:hover {
            background: var(--accent);
            color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(67, 210, 64, 0.3);
        }
        .quick-btn.primary {
            background: var(--accent);
            color: var(--primary);
        }
        .quick-btn .icon { font-size: 1.3em; }
        
        /* Category Section */
        .category-section { margin-bottom: 30px; }
        .category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(67, 210, 64, 0.3);
        }
        .category-header h2 {
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .category-header .count {
            background: var(--accent);
            color: var(--primary);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* Tutorial Grid */
        .tutorials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
        }
        .tutorial-card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            text-decoration: none;
            color: var(--text);
            display: block;
        }
        .tutorial-card:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            box-shadow: 0 8px 25px rgba(67, 210, 64, 0.2);
        }
        .tutorial-card h3 {
            font-size: 1.1rem;
            margin-bottom: 6px;
            color: var(--text);
        }
        .tutorial-card p {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        .tutorial-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .tutorial-card .sats {
            background: var(--orange);
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .tutorial-card .difficulty {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 8px;
        }
        .difficulty.leicht { background: rgba(67, 210, 64, 0.2); color: var(--accent); }
        .difficulty.mittel { background: rgba(247, 147, 26, 0.2); color: var(--orange); }
        .difficulty.schwer { background: rgba(220, 53, 69, 0.2); color: #ff6b6b; }
        
        /* Foxy Tipp Box */
        .foxy-tipp {
            background: var(--card-bg);
            border: 2px solid var(--orange);
            border-radius: 14px;
            padding: 18px;
            margin-top: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .foxy-tipp .foxy-icon { font-size: 2.5rem; }
        .foxy-tipp strong { color: var(--orange); }
        .foxy-tipp p { color: var(--text-muted); margin-top: 5px; }
        
        /* Stats Bar */
        .stats-bar {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 20px;
        }
        .stat-item {
            background: var(--card-bg);
            padding: 10px 18px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-item .value {
            font-size: 1.4rem;
            font-weight: bold;
            color: var(--accent);
        }
        .stat-item .label {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        @media (max-width: 600px) {
            .quick-actions { flex-direction: column; align-items: center; }
            .quick-btn { width: 100%; justify-content: center; }
            .stats-bar { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/adaptive_learning.php" class="back-link">← Zurück zum Lernen</a>
        
        <header>
            <h1>🎨 Zeichnen-<span>Studio</span></h1>
            <p class="subtitle">Lerne zeichnen - von einfachen Formen bis zu coolen Kunstwerken!</p>
            <div class="user-info">
                <span style="font-size: 1.8rem;">✏️</span>
                <div>
                    <strong><?= htmlspecialchars($userName) ?></strong><br>
                    <small><?= $userAge ?> Jahre</small>
                </div>
            </div>
        </header>
        
        <!-- Stats -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="value"><?= $totalTutorials ?></div>
                <div class="label">Tutorials</div>
            </div>
            <div class="stat-item">
                <div class="value"><?= count($categories) ?></div>
                <div class="label">Kategorien</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="canvas.php?mode=free" class="quick-btn primary">
                <span class="icon">🖌️</span>
                <span>Freies Zeichnen</span>
            </a>
            <a href="gallery.php" class="quick-btn">
                <span class="icon">🖼️</span>
                <span>Meine Galerie</span>
            </a>
            <?php if ($userAge >= 7): ?>
            <a href="canvas.php?tutorial=fox" class="quick-btn" style="border-color: var(--orange);">
                <span class="icon">🦊</span>
                <span>Zeichne Foxy!</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Tutorial Categories -->
        <?php foreach ($categories as $catId => $category): ?>
        <div class="category-section">
            <div class="category-header">
                <h2><?= $category['icon'] ?> <?= $category['title'] ?></h2>
                <span class="count"><?= count($category['tutorials']) ?> Tutorials</span>
            </div>
            <div class="tutorials-grid">
                <?php foreach ($category['tutorials'] as $tutorial): ?>
                <a href="canvas.php?tutorial=<?= htmlspecialchars($tutorial['filename'] ?? $tutorial['id']) ?>" 
                   class="tutorial-card">
                    <h3><?= htmlspecialchars($tutorial['title']) ?></h3>
                    <p><?= htmlspecialchars($tutorial['description']) ?></p>
                    <div class="tutorial-meta">
                        <span class="difficulty <?= strtolower($tutorial['difficulty'] ?? 'leicht') ?>">
                            <?= ucfirst($tutorial['difficulty'] ?? 'Leicht') ?>
                        </span>
                        <span class="sats">+<?= $tutorial['sats_reward'] ?? 5 ?> Sats</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Foxy Tipp -->
        <div class="foxy-tipp">
            <span class="foxy-icon">🦊</span>
            <div>
                <strong>Foxy's Tipp:</strong>
                <p>
                    <?php 
                    $tips = [
                        "Übung macht den Meister! Zeichne jeden Tag ein bisschen! ✏️",
                        "Fang mit einfachen Formen an - alles besteht aus Kreisen, Quadraten und Dreiecken! 📐",
                        "Keine Angst vor Fehlern - der Radierer ist dein Freund! 🧽",
                        "Je mehr Tutorials du schaffst, desto mehr Sats verdienst du! ₿",
                        "Schau dir Dinge genau an bevor du sie zeichnest! 👀"
                    ];
                    echo $tips[array_rand($tips)];
                    ?>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
