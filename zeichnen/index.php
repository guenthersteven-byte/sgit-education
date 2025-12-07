<?php
/**
 * sgiT Education - Zeichnen Modul v2.0
 * Hauptseite / Übersicht mit allen Tutorials
 * 
 * NEUERUNGEN v2.0 (07.12.2025):
 * - 15+ Tutorials für alle Altersgruppen
 * - Kategorien: Grundformen, Tiere, Natur, Fortgeschritten
 * - Foxy-Integration
 * - Verbesserte Aktivitäts-Karten
 * 
 * @version 2.0
 * @date 07.12.2025
 */

session_start();

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
        'grundformen' => ['title' => '📐 Grundformen', 'icon' => '📐', 'tutorials' => []],
        'natur' => ['title' => '🌿 Natur & Pflanzen', 'icon' => '🌿', 'tutorials' => []],
        'tiere' => ['title' => '🐾 Tiere', 'icon' => '🐾', 'tutorials' => []],
        'fahrzeuge' => ['title' => '🚗 Fahrzeuge & Technik', 'icon' => '🚀', 'tutorials' => []],
        'menschen' => ['title' => '👤 Menschen & Gesichter', 'icon' => '👤', 'tutorials' => []],
        'technik' => ['title' => '🎨 Technik & Theorie', 'icon' => '🎓', 'tutorials' => []],
        'spass' => ['title' => '😊 Spaß & Kreativ', 'icon' => '🎉', 'tutorials' => []]
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
        // Altersfilter
        $ageMin = $tutorial['age_min'] ?? 5;
        $ageMax = $tutorial['age_max'] ?? 21;
        
        if ($userAge < $ageMin || $userAge > $ageMax) {
            continue; // Überspringe nicht altersgerechte Tutorials
        }
        
        $id = $tutorial['filename'] ?? $tutorial['id'];
        $cat = $categoryMap[$id] ?? 'spass';
        $categories[$cat]['tutorials'][] = $tutorial;
    }
    
    // Leere Kategorien entfernen
    return array_filter($categories, fn($c) => !empty($c['tutorials']));
}

$allTutorials = loadAllTutorials(__DIR__ . '/tutorials');
$categories = categorizeTutorials($allTutorials, $userAge);

// Statistiken
$totalTutorials = array_sum(array_map(fn($c) => count($c['tutorials']), $categories));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎨 Zeichnen-Studio - sgiT Education</title>
    <style>
        :root {
            --sgit-dark: #1A3503;
            --sgit-green: #43D240;
            --sgit-orange: #E86F2C;
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
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { font-size: 1.8em; }
        .header-stats {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .stat-box {
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-box .value { font-size: 1.3em; font-weight: bold; color: var(--sgit-green); }
        .stat-box .label { font-size: 0.8em; opacity: 0.8; }
        .header .back-btn {
            background: var(--sgit-green);
            color: var(--sgit-dark);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .quick-action {
            background: white;
            border: 3px solid var(--sgit-green);
            border-radius: 15px;
            padding: 20px 30px;
            text-decoration: none;
            color: var(--sgit-dark);
            font-weight: bold;
            font-size: 1.1em;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quick-action:hover {
            background: var(--sgit-green);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(67, 210, 64, 0.4);
        }
        .quick-action.primary {
            background: var(--sgit-green);
        }
        .quick-action .icon { font-size: 1.5em; }

        /* Category Section */
        .category-section {
            margin-bottom: 35px;
        }
        .category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
        }
        .category-header h2 {
            color: var(--sgit-dark);
            font-size: 1.4em;
        }
        .category-header .count {
            background: var(--sgit-green);
            color: var(--sgit-dark);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }
        /* Tutorial Grid */
        .tutorials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
        }
        .tutorial-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            border: 2px solid transparent;
        }
        .tutorial-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(67, 210, 64, 0.25);
            border-color: var(--sgit-green);
        }
        .tutorial-card.special {
            background: linear-gradient(135deg, #FFF8E1, #FFE0B2);
            border-color: var(--sgit-orange);
        }
        .tutorial-card h3 {
            font-size: 1.1em;
            margin-bottom: 6px;
            color: var(--sgit-dark);
        }
        .tutorial-card p {
            color: #666;
            font-size: 0.85em;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        .tutorial-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .tutorial-card .sats {
            background: var(--sgit-green);
            color: var(--sgit-dark);
            padding: 4px 10px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 0.8em;
        }
        .tutorial-card .difficulty {
            font-size: 0.75em;
            padding: 3px 8px;
            border-radius: 10px;
            background: #f0f0f0;
        }
        .tutorial-card .difficulty.leicht { background: #E8F5E9; color: #2E7D32; }
        .tutorial-card .difficulty.mittel { background: #FFF3E0; color: #E65100; }
        .tutorial-card .difficulty.schwer { background: #FFEBEE; color: #C62828; }
        
        /* Welcome Message */
        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        .welcome-section .foxy-avatar {
            font-size: 4em;
        }
        .welcome-section h2 { color: var(--sgit-dark); margin-bottom: 8px; }
        .welcome-section p { color: #666; line-height: 1.5; }

        @media (max-width: 600px) {
            .header { flex-direction: column; text-align: center; }
            .quick-actions { justify-content: center; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>🎨 Zeichnen-Studio</h1>
        <div class="header-stats">
            <div class="stat-box">
                <div class="value"><?= $totalTutorials ?></div>
                <div class="label">Tutorials</div>
            </div>
            <div class="stat-box">
                <div class="value"><?= $userAge ?> J.</div>
                <div class="label">Dein Alter</div>
            </div>
        </div>
        <a href="/adaptive_learning.php" class="back-btn">← Zurück zum Lernen</a>
    </div>
    
    <!-- Welcome -->
    <div class="welcome-section">
        <div class="foxy-avatar">🦊</div>
        <div>
            <h2>Willkommen im Zeichnen-Studio, <?= htmlspecialchars($userName) ?>!</h2>
            <p>Hier lernst du zeichnen - von einfachen Formen bis zu coolen Kunstwerken!<br>
            Für jedes Tutorial bekommst du <strong style="color: var(--sgit-orange);">Satoshis</strong>! Je schwerer, desto mehr! 🎉</p>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="canvas.php?mode=free" class="quick-action primary">
            <span class="icon">🖌️</span>
            <span>Freies Zeichnen</span>
        </a>
        <a href="gallery.php" class="quick-action">
            <span class="icon">🖼️</span>
            <span>Meine Galerie</span>
        </a>
        <?php if ($userAge >= 7): ?>
        <a href="canvas.php?tutorial=fox" class="quick-action" style="border-color: var(--sgit-orange);">
            <span class="icon">🦊</span>
            <span>Zeichne Foxy!</span>
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Tutorial Categories -->
    <?php foreach ($categories as $catId => $category): ?>
    <div class="category-section">
        <div class="category-header">
            <h2><?= $category['title'] ?></h2>
            <span class="count"><?= count($category['tutorials']) ?> Tutorials</span>
        </div>
        <div class="tutorials-grid">
            <?php foreach ($category['tutorials'] as $tutorial): ?>
            <a href="canvas.php?tutorial=<?= htmlspecialchars($tutorial['filename'] ?? $tutorial['id']) ?>" 
               class="tutorial-card <?= !empty($tutorial['special']) ? 'special' : '' ?>">
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
    <div style="background: linear-gradient(135deg, #FFF8E1, #FFE0B2); padding: 20px; border-radius: 15px; margin-top: 30px; display: flex; align-items: center; gap: 15px;">
        <span style="font-size: 3em;">🦊</span>
        <div>
            <strong style="color: var(--sgit-orange);">Foxy's Tipp:</strong>
            <p style="margin: 5px 0 0; color: #666;">
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
</body>
</html>
