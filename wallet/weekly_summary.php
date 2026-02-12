<?php
/**
 * ============================================================================
 * sgiT Education - Wöchentliche Zusammenfassung v1.0
 * ============================================================================
 * 
 * Eltern-Dashboard für wöchentliche Lernfortschritte:
 * - Übersicht aller Kinder
 * - Sats verdient, Sessions, Erfolgsquote
 * - Modul-Verteilung
 * - Wochen-Vergleich (Trend)
 * - Achievements der Woche
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 03.12.2025
 * ============================================================================
 */

session_start();

// Admin-Check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../admin_dashboard.php');
    exit;
}

require_once __DIR__ . '/WalletManager.php';
require_once __DIR__ . '/AchievementManager.php';

$walletMgr = new WalletManager();
$achievementMgr = new AchievementManager();

// Woche auswählen (Standard: diese Woche)
$selectedWeek = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));

// Alle Zusammenfassungen laden
$summaries = $walletMgr->getAllWeeklySummaries($selectedWeek);

// Gesamt-Statistiken berechnen
$totalStats = [
    'sats' => 0,
    'sessions' => 0,
    'questions' => 0,
    'correct' => 0,
    'achievements' => 0
];

foreach ($summaries as $summary) {
    if (!isset($summary['error'])) {
        $totalStats['sats'] += $summary['stats']['total_sats'];
        $totalStats['sessions'] += $summary['stats']['total_sessions'];
        $totalStats['questions'] += $summary['stats']['total_questions'];
        $totalStats['correct'] += $summary['stats']['total_correct'];
        $totalStats['achievements'] += count($summary['achievements']);
    }
}

$totalSuccessRate = $totalStats['questions'] > 0 
    ? round(($totalStats['correct'] / $totalStats['questions']) * 100, 1) 
    : 0;

// Wochentage für Anzeige
$weekDays = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
$weekDates = [];
for ($i = 0; $i < 7; $i++) {
    $weekDates[] = date('Y-m-d', strtotime($selectedWeek . " +$i days"));
}

// Navigation: Vorherige/Nächste Woche
$prevWeek = date('Y-m-d', strtotime($selectedWeek . ' -7 days'));
$nextWeek = date('Y-m-d', strtotime($selectedWeek . ' +7 days'));
$isCurrentWeek = ($selectedWeek === date('Y-m-d', strtotime('monday this week')));

// Modul-Icons
$moduleIcons = [
    'mathematik' => '🔢', 'mathe' => '🔢',
    'reading' => '📖', 'lesen' => '📖',
    'science' => '🔬', 'wissenschaft' => '🔬',
    'geography' => '🌍', 'geographie' => '🌍',
    'english' => '🇬🇧', 'englisch' => '🇬🇧',
    'chemistry' => '⚗️', 'chemie' => '⚗️',
    'physics' => '⚡', 'physik' => '⚡',
    'art' => '🎨', 'kunst' => '🎨',
    'music' => '🎵', 'musik' => '🎵',
    'computer' => '💻', 'informatik' => '💻',
    'bitcoin' => '₿',
    'history' => '📜', 'geschichte' => '📜',
    'biology' => '🧬', 'biologie' => '🧬',
    'taxes' => '💰', 'steuern' => '💰'
];

function getModuleIcon($module) {
    global $moduleIcons;
    $key = strtolower(trim($module));
    return $moduleIcons[$key] ?? '📚';
}

function getTrendIcon($trend) {
    switch ($trend) {
        case 'up': return '<span class="trend-up">📈 ↑</span>';
        case 'down': return '<span class="trend-down">📉 ↓</span>';
        default: return '<span class="trend-same">➡️</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wöchentliche Zusammenfassung - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bg: #f5f5f5;
            --card-bg: #ffffff;
            --text: #333;
            --text-light: #666;
            --border: #e0e0e0;
            --success: #4CAF50;
            --warning: #FF9800;
            --info: #2196F3;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, #2d5a0a 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-nav {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .btn-light {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .btn-light:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .btn-accent {
            background: var(--accent);
            color: var(--primary);
        }
        
        .btn-accent:hover {
            background: #3bc236;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .week-nav {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 8px;
        }
        
        .week-label {
            font-weight: 600;
            min-width: 200px;
            text-align: center;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Gesamt-Statistiken */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .stat-card.highlight {
            background: linear-gradient(135deg, var(--primary) 0%, #2d5a0a 100%);
            color: white;
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-card.highlight .stat-value {
            color: var(--accent);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 5px;
        }
        
        .stat-card.highlight .stat-label {
            color: rgba(255,255,255,0.8);
        }
        
        /* Kind-Karten */
        .children-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .child-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .child-header {
            background: linear-gradient(135deg, var(--primary) 0%, #2d5a0a 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .child-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .child-avatar {
            font-size: 2.5rem;
        }
        
        .child-name {
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .child-streak {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .child-sats {
            text-align: right;
        }
        
        .sats-earned {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
        }
        
        .sats-trend {
            font-size: 0.85rem;
        }
        
        .trend-up { color: #4CAF50; }
        .trend-down { color: #f44336; }
        .trend-same { color: #999; }
        
        .child-body {
            padding: 20px;
        }
        
        /* Wochen-Kalender */
        .week-calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .day-cell {
            text-align: center;
            padding: 10px 5px;
            border-radius: 8px;
            background: #f9f9f9;
        }
        
        .day-cell.active {
            background: linear-gradient(135deg, var(--accent) 0%, #3bc236 100%);
            color: white;
        }
        
        .day-cell.today {
            border: 2px solid var(--accent);
        }
        
        .day-name {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .day-date {
            font-size: 0.7rem;
            opacity: 0.7;
        }
        
        .day-sats {
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 5px;
        }
        
        /* Stats Grid */
        .child-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .mini-stat {
            text-align: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .mini-stat-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .mini-stat-label {
            font-size: 0.7rem;
            color: var(--text-light);
        }
        
        /* Module */
        .module-list {
            margin-bottom: 15px;
        }
        
        .module-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }
        
        .module-item:last-child {
            border-bottom: none;
        }
        
        .module-name {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .module-stats {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        /* Achievements */
        .achievements-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }
        
        .section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 10px;
        }
        
        .achievement-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .achievement-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #FFF3E0;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .achievement-badge.gold {
            background: #FFFDE7;
            border: 1px solid #F9A825;
        }
        
        .achievement-badge.silver {
            background: #F5F5F5;
            border: 1px solid #9E9E9E;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }
        
        .no-data-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .children-grid {
                grid-template-columns: 1fr;
            }
            
            .child-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .week-nav {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        
        .version-badge {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: var(--primary);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Wöchentliche Zusammenfassung</h1>
        
        <div class="week-nav">
            <a href="?week=<?= $prevWeek ?>" class="btn btn-light">← Vorherige</a>
            <div class="week-label">
                <?= date('d.m.', strtotime($selectedWeek)) ?> - <?= date('d.m.Y', strtotime($selectedWeek . ' +6 days')) ?>
                <?php if ($isCurrentWeek): ?>
                    <br><small>Diese Woche</small>
                <?php endif; ?>
            </div>
            <a href="?week=<?= $nextWeek ?>" class="btn btn-light" <?= $isCurrentWeek ? 'style="opacity:0.5;pointer-events:none"' : '' ?>>Nächste →</a>
        </div>
        
        <div class="header-nav">
            <a href="wallet_admin.php" class="btn btn-light">💰 Wallet Admin</a>
            <a href="../admin_dashboard.php" class="btn btn-accent">🏠 Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Gesamt-Statistiken -->
        <div class="stats-overview">
            <div class="stat-card highlight">
                <div class="stat-icon">₿</div>
                <div class="stat-value"><?= number_format($totalStats['sats']) ?></div>
                <div class="stat-label">Sats verdient (gesamt)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value"><?= $totalStats['sessions'] ?></div>
                <div class="stat-label">Sessions abgeschlossen</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?= $totalStats['correct'] ?>/<?= $totalStats['questions'] ?></div>
                <div class="stat-label">Richtige Antworten</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-value"><?= $totalSuccessRate ?>%</div>
                <div class="stat-label">Erfolgsquote</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-value"><?= $totalStats['achievements'] ?></div>
                <div class="stat-label">Neue Achievements</div>
            </div>
        </div>
        
        <!-- Kind-Karten -->
        <?php if (empty($summaries)): ?>
            <div class="no-data">
                <div class="no-data-icon">👶</div>
                <p>Keine Kinder registriert.</p>
                <a href="wallet_admin.php" class="btn btn-accent" style="margin-top:15px">Kind hinzufügen</a>
            </div>
        <?php else: ?>
            <div class="children-grid">
                <?php foreach ($summaries as $childId => $summary): ?>
                    <?php if (isset($summary['error'])) continue; ?>
                    <?php $child = $summary['child']; ?>
                    
                    <div class="child-card">
                        <div class="child-header">
                            <div class="child-info">
                                <span class="child-avatar"><?= htmlspecialchars($child['avatar']) ?></span>
                                <div>
                                    <div class="child-name"><?= htmlspecialchars($child['child_name']) ?></div>
                                    <div class="child-streak">🔥 <?= $summary['streak']['current'] ?> Tage Streak</div>
                                </div>
                            </div>
                            <div class="child-sats">
                                <div class="sats-earned">+<?= number_format($summary['stats']['total_sats']) ?> Sats</div>
                                <div class="sats-trend">
                                    <?= getTrendIcon($summary['comparison']['sats_trend']) ?>
                                    <?php if ($summary['comparison']['sats_diff'] != 0): ?>
                                        <?= $summary['comparison']['sats_diff'] > 0 ? '+' : '' ?><?= $summary['comparison']['sats_diff'] ?> vs. Vorwoche
                                    <?php else: ?>
                                        wie Vorwoche
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="child-body">
                            <!-- Wochen-Kalender -->
                            <div class="week-calendar">
                                <?php foreach ($weekDates as $i => $date): ?>
                                    <?php 
                                    $hasData = isset($summary['daily_breakdown'][$date]);
                                    $daySats = $hasData ? $summary['daily_breakdown'][$date]['sats_earned'] : 0;
                                    $isToday = $date === date('Y-m-d');
                                    ?>
                                    <div class="day-cell <?= $hasData ? 'active' : '' ?> <?= $isToday ? 'today' : '' ?>">
                                        <div class="day-name"><?= $weekDays[$i] ?></div>
                                        <div class="day-date"><?= date('d.', strtotime($date)) ?></div>
                                        <?php if ($hasData): ?>
                                            <div class="day-sats">+<?= $daySats ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Statistiken -->
                            <div class="child-stats">
                                <div class="mini-stat">
                                    <div class="mini-stat-value"><?= $summary['stats']['total_sessions'] ?></div>
                                    <div class="mini-stat-label">Sessions</div>
                                </div>
                                <div class="mini-stat">
                                    <div class="mini-stat-value"><?= $summary['stats']['active_days'] ?>/7</div>
                                    <div class="mini-stat-label">Aktive Tage</div>
                                </div>
                                <div class="mini-stat">
                                    <div class="mini-stat-value"><?= $summary['stats']['success_rate'] ?>%</div>
                                    <div class="mini-stat-label">Erfolgsquote</div>
                                </div>
                                <div class="mini-stat">
                                    <div class="mini-stat-value"><?= $summary['stats']['total_correct'] ?></div>
                                    <div class="mini-stat-label">Richtig</div>
                                </div>
                            </div>
                            
                            <!-- Module -->
                            <?php if (!empty($summary['module_breakdown'])): ?>
                                <div class="section-title">📚 Module diese Woche</div>
                                <div class="module-list">
                                    <?php foreach (array_slice($summary['module_breakdown'], 0, 5) as $mod): ?>
                                        <div class="module-item">
                                            <div class="module-name">
                                                <span><?= getModuleIcon($mod['module']) ?></span>
                                                <span><?= htmlspecialchars(ucfirst($mod['module'])) ?></span>
                                            </div>
                                            <div class="module-stats">
                                                <span><?= $mod['count'] ?>x</span>
                                                <span>+<?= $mod['sats'] ?> Sats</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Achievements -->
                            <?php if (!empty($summary['achievements'])): ?>
                                <div class="achievements-section">
                                    <div class="section-title">🏆 Neue Achievements</div>
                                    <div class="achievement-list">
                                        <?php foreach ($summary['achievements'] as $ach): ?>
                                            <span class="achievement-badge">
                                                <?= htmlspecialchars($ach['achievement_icon']) ?>
                                                <?= htmlspecialchars($ach['achievement_name']) ?>
                                                <?php if ($ach['reward_sats'] > 0): ?>
                                                    <small>(+<?= $ach['reward_sats'] ?>)</small>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (empty($summary['module_breakdown']) && $summary['stats']['total_sessions'] == 0): ?>
                                <div class="no-data" style="padding:20px">
                                    <div class="no-data-icon">😴</div>
                                    <p>Keine Aktivität diese Woche</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="version-badge">Weekly Summary v1.0</div>
</body>
</html>
