<?php
/**
 * Kochen Modul - Übersicht v3.0
 * Design angepasst an restliche Module
 */
session_start();

$userAge = $_SESSION['user_age'] ?? 10;
$userName = $_SESSION['child_name'] ?? 'Küchenchef';

$activities = [
    'quiz' => [
        'name' => 'Ernährungs-Quiz',
        'icon' => '🥗',
        'desc' => 'Teste dein Wissen über gesunde Ernährung!',
        'min' => 5, 'max' => 21, 'sats' => '5-8'
    ],
    'zuordnen' => [
        'name' => 'Lebensmittel zuordnen',
        'icon' => '🍎',
        'desc' => 'Ordne die Lebensmittel den richtigen Gruppen zu!',
        'min' => 5, 'max' => 14, 'sats' => '5-8'
    ],
    'kuechenwissen' => [
        'name' => 'Küchenwissen',
        'icon' => '🔪',
        'desc' => 'Lerne Küchengeräte und Maßeinheiten kennen!',
        'min' => 8, 'max' => 21, 'sats' => '6-10'
    ],
    'rezept' => [
        'name' => 'Rezepte entdecken',
        'icon' => '📖',
        'desc' => 'Lerne einfache Rezepte Schritt für Schritt!',
        'min' => 6, 'max' => 21, 'sats' => '10-20', 'soon' => true
    ]
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍳 Kochen - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1A3503; --accent: #43D240; --bg: #0d1f02; --card-bg: #1e3a08; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, var(--bg), var(--primary)); min-height: 100vh; color: #fff; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .back-link { color: var(--accent); text-decoration: none; display: inline-block; margin-bottom: 15px; }
        .back-link:hover { text-decoration: underline; }
        header { text-align: center; margin-bottom: 30px; }
        header h1 { font-size: 2.2rem; margin-bottom: 8px; }
        header h1 span { color: var(--accent); }
        .subtitle { color: #a0a0a0; font-size: 1.1rem; }
        .user-box { background: var(--card-bg); padding: 12px 20px; border-radius: 12px; display: inline-flex; align-items: center; gap: 12px; margin-top: 15px; }
        .user-icon { font-size: 2rem; }
        .user-name { font-weight: bold; }
        .user-age { color: #a0a0a0; font-size: 0.9rem; }
        .activity-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 25px; }
        .activity-card { 
            background: var(--card-bg); border-radius: 14px; padding: 22px; 
            cursor: pointer; transition: all 0.3s; border: 2px solid transparent; position: relative;
        }
        .activity-card:hover { transform: translateY(-4px); border-color: var(--accent); box-shadow: 0 8px 25px rgba(67, 210, 64, 0.15); }
        .activity-card.disabled { opacity: 0.5; cursor: not-allowed; }
        .activity-card.disabled:hover { transform: none; border-color: transparent; box-shadow: none; }
        .activity-icon { font-size: 2.8rem; margin-bottom: 12px; }
        .activity-name { font-size: 1.2rem; font-weight: 600; margin-bottom: 6px; }
        .activity-desc { color: #a0a0a0; font-size: 0.9rem; margin-bottom: 12px; line-height: 1.4; }
        .activity-meta { display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--accent); }
        .badge { background: var(--accent); color: var(--primary); padding: 3px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 700; position: absolute; top: 12px; right: 12px; }
        .badge.soon { background: #666; color: #fff; }
    </style>
</head>
<body>
<div class="container">
    <a href="/adaptive_learning.php" class="back-link">← Zurück zum Lernen</a>
    
    <header>
        <h1>🍳 Kochen & <span>Ernährung</span></h1>
        <p class="subtitle">Lerne kochen und ernähre dich gesund!</p>
        <div class="user-box">
            <div class="user-icon">👨‍🍳</div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                <div class="user-age"><?php echo $userAge; ?> Jahre</div>
            </div>
        </div>
    </header>
    
    <div class="activity-grid">
        <?php foreach ($activities as $key => $act): 
            $available = ($userAge >= $act['min'] && $userAge <= $act['max']);
            $soon = isset($act['soon']) && $act['soon'];
            $clickable = $available && !$soon;
        ?>
        <div class="activity-card <?php echo (!$clickable) ? 'disabled' : ''; ?>"
             <?php if ($clickable): ?>onclick="location.href='<?php echo $key; ?>.php'"<?php endif; ?>>
            <?php if ($soon): ?><span class="badge soon">Bald!</span><?php endif; ?>
            <div class="activity-icon"><?php echo $act['icon']; ?></div>
            <div class="activity-name"><?php echo $act['name']; ?></div>
            <div class="activity-desc"><?php echo $act['desc']; ?></div>
            <div class="activity-meta">
                <span>🌟 <?php echo $act['sats']; ?> Sats</span>
                <span><?php echo $act['min']; ?>-<?php echo $act['max']; ?> Jahre</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
