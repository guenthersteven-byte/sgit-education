<?php
/**
 * ============================================================================
 * sgiT Education - Logik & Rätsel Modul v1.0
 * ============================================================================
 * 
 * Interaktives Modul für logisches Denken mit verschiedenen Rätseltypen.
 * Altersgerecht angepasst (5-21 Jahre).
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 07.12.2025
 * ============================================================================
 */

session_start();

// Alter aus Session oder Default
$userAge = $_SESSION['user_age'] ?? 10;
$userName = $_SESSION['child_name'] ?? 'Rätselfan';

// Rätseltypen nach Alter
$puzzleTypes = [
    'muster' => [
        'name' => 'Muster fortsetzen',
        'icon' => '🎨',
        'description' => 'Erkenne das Muster und finde das nächste Element!',
        'min_age' => 5, 'max_age' => 21,
        'sats' => '5-20'
    ],
    'ausreisser' => [
        'name' => 'Was gehört nicht dazu?',
        'icon' => '🔍',
        'description' => 'Finde das Element, das nicht zur Gruppe passt!',
        'min_age' => 5, 'max_age' => 12,
        'sats' => '5-10'
    ],
    'zahlenreihe' => [
        'name' => 'Zahlenreihen',
        'icon' => '🔢',
        'description' => 'Erkenne das Muster und finde die nächste Zahl!',
        'min_age' => 8, 'max_age' => 21,
        'sats' => '10-35'
    ],
    'sudoku' => [
        'name' => 'Sudoku',
        'icon' => '📊',
        'description' => 'Fülle das Gitter mit Zahlen!',
        'min_age' => 8, 'max_age' => 21,
        'sats' => '15-75'
    ]
];

// Filter nach Alter
$availablePuzzles = array_filter($puzzleTypes, function($p) use ($userAge) {
    return $userAge >= $p['min_age'] && $userAge <= $p['max_age'];
});
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧩 Logik & Rätsel - sgiT Education</title>
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
        .puzzle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        .puzzle-card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 22px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
        }
        .puzzle-card:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            box-shadow: 0 8px 25px rgba(67, 210, 64, 0.2);
        }
        .puzzle-card.disabled { opacity: 0.5; cursor: not-allowed; }
        .puzzle-card.disabled:hover { transform: none; border-color: transparent; }
        .puzzle-icon { font-size: 2.5rem; margin-bottom: 12px; }
        .puzzle-name { font-size: 1.2rem; font-weight: 600; margin-bottom: 6px; }
        .puzzle-desc { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 12px; }
        .puzzle-meta { display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--accent); }
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
    </style>
</head>
<body>
    <div class="container">
        <a href="/adaptive_learning.php" class="back-link">← Zurück zum Lernen</a>
        
        <header>
            <h1>🧩 Logik & <span>Rätsel</span></h1>
            <p class="subtitle">Trainiere dein Gehirn mit spannenden Rätseln!</p>
            <div class="user-info">
                <span style="font-size:1.8rem">🧠</span>
                <div>
                    <strong><?php echo htmlspecialchars($userName); ?></strong><br>
                    <small><?php echo $userAge; ?> Jahre</small>
                </div>
            </div>
        </header>
        
        <div class="puzzle-grid">
            <?php 
            $implemented = ['muster', 'ausreisser', 'zahlenreihe'];
            foreach ($puzzleTypes as $key => $puzzle): 
                $available = $userAge >= $puzzle['min_age'] && $userAge <= $puzzle['max_age'];
                $ready = in_array($key, $implemented);
            ?>
            <div class="puzzle-card <?php echo (!$available || !$ready) ? 'disabled' : ''; ?>"
                 <?php if ($available && $ready): ?>onclick="location.href='<?php echo $key; ?>.php'"<?php endif; ?>>
                <?php if (!$ready): ?><span class="badge coming">Bald!</span><?php endif; ?>
                <div class="puzzle-icon"><?php echo $puzzle['icon']; ?></div>
                <div class="puzzle-name"><?php echo $puzzle['name']; ?></div>
                <div class="puzzle-desc"><?php echo $puzzle['description']; ?></div>
                <div class="puzzle-meta">
                    <span>⭐ <?php echo $puzzle['sats']; ?> Sats</span>
                    <span><?php echo $puzzle['min_age']; ?>-<?php echo $puzzle['max_age']; ?> Jahre</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
