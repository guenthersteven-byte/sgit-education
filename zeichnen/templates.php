<?php
/**
 * sgiT Education - Vorlagen/Ausmalbilder
 * @version 1.0
 * @date 11.12.2025
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /adaptive_learning.php');
    exit;
}

$userAge = $_SESSION['user_age'] ?? 10;

// Alle Vorlagen laden
$templates = [];
$templateDir = __DIR__ . '/templates';
if (is_dir($templateDir)) {
    foreach (glob($templateDir . '/*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data && (!isset($data['ageMin']) || $userAge >= $data['ageMin'])) {
            $templates[] = $data;
        }
    }
}

// Nach Schwierigkeit sortieren
usort($templates, function($a, $b) {
    $order = ['leicht' => 1, 'mittel' => 2, 'schwer' => 3];
    return ($order[$a['difficulty']] ?? 2) - ($order[$b['difficulty']] ?? 2);
});
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🖼️ Ausmalbilder - sgiT Education</title>
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
        }
        .header h1 { font-size: 1.8em; }
        .header a {
            background: #444;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .template-card {
            background: #2a2a2a;
            border-radius: 15px;
            overflow: hidden;
            border: 2px solid #333;
            transition: all 0.3s;
        }
        .template-card:hover {
            border-color: var(--sgit-green);
            transform: translateY(-5px);
        }
        .template-preview {
            background: white;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .template-preview svg {
            max-width: 100%;
            max-height: 100%;
        }
        .template-info {
            padding: 15px;
        }
        .template-title {
            color: white;
            font-size: 1.2em;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .template-desc {
            color: #888;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .template-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .difficulty {
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        .difficulty.leicht { background: #27ae60; color: white; }
        .difficulty.mittel { background: #f39c12; color: white; }
        .difficulty.schwer { background: #e74c3c; color: white; }
        .sats-reward {
            color: var(--sgit-orange);
            font-weight: bold;
        }
        .start-btn {
            display: block;
            background: var(--sgit-green);
            color: var(--sgit-dark);
            text-align: center;
            padding: 12px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 10px;
            border-radius: 8px;
        }
        .start-btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🖼️ Ausmalbilder</h1>
        <a href="index.php">← Zurück</a>
    </div>
    
    <div class="templates-grid">
        <?php foreach ($templates as $t): ?>
        <div class="template-card">
            <div class="template-preview">
                <?= $t['svgContent'] ?>
            </div>
            <div class="template-info">
                <div class="template-title">
                    <span><?= $t['icon'] ?></span>
                    <?= htmlspecialchars($t['name']) ?>
                </div>
                <div class="template-desc"><?= htmlspecialchars($t['description']) ?></div>
                <div class="template-meta">
                    <span class="difficulty <?= $t['difficulty'] ?>"><?= ucfirst($t['difficulty']) ?></span>
                    <span class="sats-reward">+<?= $t['satsReward'] ?> Sats</span>
                </div>
                <a href="canvas.php?mode=template&template=<?= $t['id'] ?>" class="start-btn">
                    🎨 Ausmalen starten
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
