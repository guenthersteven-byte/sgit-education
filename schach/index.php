<?php
/**
 * ============================================================================
 * sgiT Education - Schach Modul v1.0
 * ============================================================================
 * 
 * Schach-Training mit altersgerechten Puzzle-Kategorien:
 * - Grundregeln (Wie ziehen Figuren?)
 * - Matt-in-1 (Einfache Schachmatt-Puzzles)
 * - Matt-in-2 (Mittlere Puzzles)
 * - Taktik (Gabel, Spieß, Fesselung)
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
require_once dirname(__DIR__) . '/includes/version.php';

$userAge = $_SESSION['user_age'] ?? 10;
$userName = $_SESSION['child_name'] ?? 'Schach-Fan';

// Spiel-Modi
$gameModes = [
    'pvp' => [
        'name' => 'Gegen Spieler',
        'icon' => '👥',
        'description' => 'Spiele Schach gegen einen Freund online!',
        'min_age' => 5, 'max_age' => 99,
        'sats' => '10-50',
        'ready' => true,
        'url' => '/schach_pvp.php'
    ],
    'computer' => [
        'name' => 'Gegen Computer',
        'icon' => '🤖',
        'description' => 'Spiele gegen die KI mit 5 Schwierigkeitsstufen!',
        'min_age' => 5, 'max_age' => 99,
        'sats' => '5-25',
        'ready' => true,
        'url' => '/schach_vs_computer.php'
    ]
];

// Puzzle-Kategorien nach Schwierigkeit
$categories = [
    'grundlagen' => [
        'name' => 'Wie ziehen Figuren?',
        'icon' => '📚',
        'description' => 'Lerne die Grundzuege aller Schachfiguren kennen!',
        'min_age' => 5, 'max_age' => 99,
        'sats' => '5-15',
        'ready' => true
    ],
    'matt1' => [
        'name' => 'Matt in 1 Zug',
        'icon' => '♚',
        'description' => 'Setze den Koenig mit einem einzigen Zug schachmatt!',
        'min_age' => 6, 'max_age' => 99,
        'sats' => '10-25',
        'ready' => true
    ],
    'matt2' => [
        'name' => 'Matt in 2 Zuegen',
        'icon' => '♛',
        'description' => 'Plane voraus und setze den Koenig in 2 Zuegen matt!',
        'min_age' => 10, 'max_age' => 99,
        'sats' => '20-40',
        'ready' => true
    ],
    'taktik' => [
        'name' => 'Taktik-Training',
        'icon' => '⚔️',
        'description' => 'Lerne Gabel, Spiess, Fesselung und mehr!',
        'min_age' => 8, 'max_age' => 99,
        'sats' => '15-35',
        'ready' => true
    ]
];

// Filter nach Alter
$availableCategories = array_filter($categories, function($c) use ($userAge) {
    return $userAge >= $c['min_age'] && $userAge <= $c['max_age'];
});
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>♟️ Schach - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bg: #0d1f02;
            --card-bg: #1e3a08;
            --text: #ffffff;
            --text-muted: #a0a0a0;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, var(--primary) 100%);
            min-height: 100vh;
            color: var(--text);
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        header { text-align: center; margin-bottom: 30px; }
        header h1 { font-size: 2.2rem; margin-bottom: 10px; }
        header h1 span { color: var(--accent); }
        .subtitle { color: var(--text-muted); }
        .user-info {
            background: var(--card-bg);
            padding: 12px 20px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-top: 15px;
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        .category-card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 22px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
        }
        .category-card:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            box-shadow: 0 8px 25px rgba(67, 210, 64, 0.2);
        }
        .category-card.disabled { opacity: 0.5; cursor: not-allowed; }
        .category-card.disabled:hover { transform: none; border-color: transparent; }
        .category-icon { font-size: 2.5rem; margin-bottom: 12px; }
        .category-name { font-size: 1.2rem; font-weight: 600; margin-bottom: 6px; }
        .category-desc { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 12px; }
        .category-meta { display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--accent); }
        .badge {
            background: var(--accent);
            color: var(--primary);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            position: absolute;
            top: 12px;
            right: 12px;
        }
        .badge.coming { background: #666; color: #fff; }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--accent);
            text-decoration: none;
            margin-bottom: 15px;
        }
        .back-link:hover { text-decoration: underline; }
        
        /* Schachbrett-Preview */
        .board-preview {
            display: grid;
            grid-template-columns: repeat(4, 20px);
            gap: 0;
            margin: 10px auto;
            border: 2px solid var(--accent);
            border-radius: 4px;
            overflow: hidden;
        }
        .board-preview .square {
            width: 20px;
            height: 20px;
        }
        .board-preview .light { background: #2a5a0a; }
        .board-preview .dark { background: #1A3503; }

        /* Spielmodus-Karten */
        .game-mode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .game-mode-card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 22px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid rgba(67, 210, 64, 0.3);
            text-align: center;
            text-decoration: none;
            color: var(--text);
            display: block;
        }
        .game-mode-card:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            box-shadow: 0 8px 25px rgba(67, 210, 64, 0.25);
        }
        .section-title {
            font-size: 1.2rem;
            color: var(--accent);
            margin-bottom: 15px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/adaptive_learning.php" class="back-link">← Zurück zum Lernen</a>
        
        <header>
            <h1>♟️ <span>Schach</span>-Training</h1>
            <p class="subtitle">Werde zum Schachmeister mit spannenden Puzzles!</p>
            <div class="user-info">
                <span style="font-size:1.8rem">♔</span>
                <div>
                    <strong><?php echo htmlspecialchars($userName); ?></strong><br>
                    <small><?php echo $userAge; ?> Jahre</small>
                </div>
            </div>
        </header>
        
        <!-- Spielmodi -->
        <h3 class="section-title">🎮 Spielen</h3>
        <div class="game-mode-grid">
            <?php foreach ($gameModes as $key => $mode):
                $available = $userAge >= $mode['min_age'] && $userAge <= $mode['max_age'];
            ?>
            <a class="game-mode-card" href="<?php echo $mode['url']; ?>">
                <div class="category-icon"><?php echo $mode['icon']; ?></div>
                <div class="category-name"><?php echo $mode['name']; ?></div>
                <div class="category-desc"><?php echo $mode['description']; ?></div>
                <div class="category-meta">
                    <span>⭐ <?php echo $mode['sats']; ?> Sats</span>
                    <span><?php echo $mode['min_age']; ?>+ Jahre</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Puzzle-Kategorien -->
        <h3 class="section-title">🧩 Training & Puzzles</h3>
        <div class="category-grid">
            <?php foreach ($categories as $key => $cat): 
                $available = $userAge >= $cat['min_age'] && $userAge <= $cat['max_age'];
                $ready = $cat['ready'] ?? false;
            ?>
            <div class="category-card <?php echo (!$available || !$ready) ? 'disabled' : ''; ?>"
                 <?php if ($available && $ready): ?>onclick="location.href='<?php echo $key; ?>.php'"<?php endif; ?>>
                <?php if (!$ready): ?><span class="badge coming">Bald!</span><?php endif; ?>
                <div class="category-icon"><?php echo $cat['icon']; ?></div>
                <div class="category-name"><?php echo $cat['name']; ?></div>
                <div class="category-desc"><?php echo $cat['description']; ?></div>
                <div class="board-preview">
                    <?php for ($i = 0; $i < 16; $i++): 
                        $row = floor($i / 4);
                        $col = $i % 4;
                        $isLight = ($row + $col) % 2 === 0;
                    ?>
                    <div class="square <?php echo $isLight ? 'light' : 'dark'; ?>"></div>
                    <?php endfor; ?>
                </div>
                <div class="category-meta">
                    <span>⭐ <?php echo $cat['sats']; ?> Sats</span>
                    <span><?php echo $cat['min_age']; ?>+ Jahre</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
