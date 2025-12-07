<?php
/**
 * ============================================================================
 * sgiT Education - Leaderboard 🏆
 * ============================================================================
 * 
 * MOTIVIERENDES LEADERBOARD FÜR KIDS
 * Zeigt verschiedene Rankings:
 * - 🏆 Top Lerner (Gesamt-Sats)
 * - 🔥 Diese Woche (Wöchentliche Sats)
 * - 🎯 Beste Trefferquote (% richtig)
 * - ⚡ Längste Streaks
 * - 📚 Modul-Champions
 * 
 * Nutzt zentrale Versionsverwaltung via /includes/version.php
 * 
 * @version Siehe SGIT_VERSION
 * @date Siehe SGIT_VERSION_DATE
 * @author sgiT Solution Engineering & IT Services
 * ============================================================================
 */

session_start();

// Zentrale Versionsverwaltung
require_once __DIR__ . '/includes/version.php';

// ============================================================================
// DATEN LADEN
// ============================================================================

$leaderboard = [
    'top_all_time' => [],      // Gesamt-Sats
    'top_weekly' => [],        // Diese Woche
    'top_accuracy' => [],      // Beste Trefferquote
    'top_streaks' => [],       // Längste Streaks
    'module_champions' => [],  // Pro Modul
    'recent_achievements' => [] // Neueste Achievements
];

$walletDb = __DIR__ . '/wallet/wallet.db';
$questionsDb = __DIR__ . '/AI/data/questions.db';

// Modul-Icons
$moduleIcons = [
    'mathematik' => '🔢', 'physik' => '⚛️', 'chemie' => '🧪', 'biologie' => '🧬',
    'erdkunde' => '🌍', 'geschichte' => '📜', 'kunst' => '🎨', 'musik' => '🎵',
    'computer' => '💻', 'programmieren' => '👨‍💻', 'bitcoin' => '₿', 'steuern' => '💰',
    'englisch' => '🇬🇧', 'lesen' => '📖', 'wissenschaft' => '🔬', 'verkehr' => '🚗'
];

if (file_exists($walletDb)) {
    try {
        $db = new PDO('sqlite:' . $walletDb);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // ====================================================================
        // 1. TOP ALL-TIME (Gesamt-Sats verdient)
        // ====================================================================
        $stmt = $db->query("
            SELECT 
                id,
                child_name as name, 
                avatar, 
                total_earned as sats,
                balance_sats as balance,
                current_streak,
                longest_streak
            FROM child_wallets 
            WHERE is_active = 1 
            ORDER BY total_earned DESC 
            LIMIT 10
        ");
        $leaderboard['top_all_time'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ====================================================================
        // 2. TOP WEEKLY (Diese Woche)
        // ====================================================================
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $stmt = $db->prepare("
            SELECT 
                c.id,
                c.child_name as name,
                c.avatar,
                COALESCE(SUM(d.sats_earned), 0) as weekly_sats,
                COALESCE(SUM(d.sessions_completed), 0) as weekly_sessions
            FROM child_wallets c
            LEFT JOIN daily_stats d ON c.id = d.child_id AND d.stat_date >= :week_start
            WHERE c.is_active = 1
            GROUP BY c.id
            HAVING weekly_sats > 0
            ORDER BY weekly_sats DESC
            LIMIT 10
        ");
        $stmt->execute([':week_start' => $weekStart]);
        $leaderboard['top_weekly'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ====================================================================
        // 3. TOP ACCURACY (Beste Trefferquote - min. 20 Fragen)
        // ====================================================================
        $stmt = $db->query("
            SELECT 
                c.id,
                c.child_name as name,
                c.avatar,
                SUM(d.correct_answers) as correct,
                SUM(d.questions_answered) as total,
                ROUND(CAST(SUM(d.correct_answers) AS FLOAT) / SUM(d.questions_answered) * 100, 1) as accuracy
            FROM child_wallets c
            JOIN daily_stats d ON c.id = d.child_id
            WHERE c.is_active = 1
            GROUP BY c.id
            HAVING total >= 20
            ORDER BY accuracy DESC
            LIMIT 10
        ");
        $leaderboard['top_accuracy'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ====================================================================
        // 4. TOP STREAKS (Längste Lern-Serien)
        // ====================================================================
        $stmt = $db->query("
            SELECT 
                id,
                child_name as name,
                avatar,
                current_streak,
                longest_streak
            FROM child_wallets 
            WHERE is_active = 1 AND longest_streak > 0
            ORDER BY longest_streak DESC, current_streak DESC
            LIMIT 10
        ");
        $leaderboard['top_streaks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ====================================================================
        // 5. MODULE CHAMPIONS (Wer hat pro Modul am meisten Sats?)
        // ====================================================================
        $stmt = $db->query("
            SELECT 
                LOWER(t.module) as module,
                c.id,
                c.child_name as name,
                c.avatar,
                SUM(t.amount_sats) as module_sats,
                COUNT(*) as sessions
            FROM sat_transactions t
            JOIN child_wallets c ON t.child_id = c.id
            WHERE t.type = 'earn' AND t.module IS NOT NULL AND c.is_active = 1
            GROUP BY LOWER(t.module), c.id
            ORDER BY LOWER(t.module), module_sats DESC
        ");
        $moduleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Pro Modul nur den Champion
        $seenModules = [];
        foreach ($moduleData as $row) {
            $mod = strtolower($row['module']);
            if (!isset($seenModules[$mod])) {
                $seenModules[$mod] = true;
                $leaderboard['module_champions'][] = $row;
            }
        }
        
        // ====================================================================
        // 6. RECENT ACHIEVEMENTS
        // ====================================================================
        $stmt = $db->query("
            SELECT 
                a.achievement_name,
                a.achievement_icon,
                a.reward_sats,
                a.unlocked_at,
                c.child_name as name,
                c.avatar
            FROM wallet_achievements a
            JOIN child_wallets c ON a.child_id = c.id
            WHERE c.is_active = 1
            ORDER BY a.unlocked_at DESC
            LIMIT 8
        ");
        $leaderboard['recent_achievements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ====================================================================
        // GESAMTSTATISTIKEN
        // ====================================================================
        $totalLearners = $db->query("SELECT COUNT(*) FROM child_wallets WHERE is_active = 1")->fetchColumn();
        $totalSatsEarned = $db->query("SELECT COALESCE(SUM(total_earned), 0) FROM child_wallets")->fetchColumn();
        $totalSessions = $db->query("SELECT COALESCE(SUM(sessions_completed), 0) FROM daily_stats")->fetchColumn();
        $totalAchievements = $db->query("SELECT COUNT(*) FROM wallet_achievements")->fetchColumn();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Hilfsfunktion: Rang-Badge
function getRankBadge($rank) {
    switch ($rank) {
        case 1: return '<span class="rank-badge gold">🥇</span>';
        case 2: return '<span class="rank-badge silver">🥈</span>';
        case 3: return '<span class="rank-badge bronze">🥉</span>';
        default: return '<span class="rank-badge">' . $rank . '</span>';
    }
}

// Hilfsfunktion: Zeitdifferenz
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Gerade eben';
    if ($diff < 3600) return floor($diff / 60) . ' Min';
    if ($diff < 86400) return floor($diff / 3600) . ' Std';
    if ($diff < 604800) return floor($diff / 86400) . ' Tage';
    return date('d.m.', $time);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏆 Leaderboard - sgiT Education</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ================================================================
         * VARIABLES & RESET
         * ================================================================ */
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --gold: #FFD700;
            --silver: #C0C0C0;
            --bronze: #CD7F32;
            --orange: #E86F2C;
            --blue: #1E3A5F;
            --bitcoin: #F7931A;
            --bg: #f0f4e8;
            --card: #ffffff;
            --text: #333;
            --text-light: #666;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Fredoka', 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            color: var(--text);
        }
        
        /* ================================================================
         * HEADER
         * ================================================================ */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, #2d5a08 50%, var(--accent) 100%);
            color: white;
            padding: 25px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '🏆';
            position: absolute;
            font-size: 120px;
            opacity: 0.1;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }
        
        .header p {
            opacity: 0.9;
            margin-top: 8px;
            font-size: 1.1rem;
        }
        
        .header-nav {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        
        .header-nav a {
            padding: 10px 18px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .header-nav a:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        /* ================================================================
         * STATS OVERVIEW
         * ================================================================ */
        .stats-bar {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-item .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-item .label {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        /* ================================================================
         * CONTAINER & GRID
         * ================================================================ */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 25px;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }
        
        @media (max-width: 1000px) {
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
        }
        
        /* ================================================================
         * CARDS
         * ================================================================ */
        .card {
            background: var(--card);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-3px);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-icon {
            font-size: 2rem;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .card-subtitle {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        /* ================================================================
         * LEADERBOARD LIST
         * ================================================================ */
        .leaderboard-list {
            list-style: none;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 10px;
            background: #f8faf5;
            border-radius: 15px;
            transition: all 0.3s;
        }
        
        .leaderboard-item:hover {
            background: #eef5e8;
            transform: scale(1.02);
        }
        
        .leaderboard-item.rank-1 {
            background: linear-gradient(135deg, #fff9e6, #fff3cc);
            border: 2px solid var(--gold);
        }
        
        .leaderboard-item.rank-2 {
            background: linear-gradient(135deg, #f5f5f5, #e8e8e8);
            border: 2px solid var(--silver);
        }
        
        .leaderboard-item.rank-3 {
            background: linear-gradient(135deg, #fff5eb, #ffe8d6);
            border: 2px solid var(--bronze);
        }
        
        .rank-badge {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 700;
            margin-right: 12px;
            font-size: 0.9rem;
            background: #e0e0e0;
            color: #666;
        }
        
        .rank-badge.gold { background: var(--gold); font-size: 1.2rem; }
        .rank-badge.silver { background: var(--silver); font-size: 1.2rem; }
        .rank-badge.bronze { background: var(--bronze); color: white; font-size: 1.2rem; }
        
        .player-avatar {
            font-size: 2rem;
            margin-right: 12px;
        }
        
        .player-info {
            flex: 1;
        }
        
        .player-name {
            font-weight: 600;
            font-size: 1.05rem;
            color: var(--primary);
        }
        
        .player-meta {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .player-score {
            text-align: right;
        }
        
        .score-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent);
        }
        
        .score-label {
            font-size: 0.75rem;
            color: var(--text-light);
        }
        
        /* ================================================================
         * MODULE CHAMPIONS
         * ================================================================ */
        .champion-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .champion-card {
            background: #f8faf5;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .champion-card:hover {
            border-color: var(--accent);
            transform: scale(1.05);
        }
        
        .champion-module {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        
        .champion-module-name {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 10px;
        }
        
        .champion-avatar {
            font-size: 1.5rem;
        }
        
        .champion-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--primary);
            margin-top: 5px;
        }
        
        .champion-sats {
            font-size: 0.8rem;
            color: var(--bitcoin);
            font-weight: 600;
        }
        
        /* ================================================================
         * ACHIEVEMENTS FEED
         * ================================================================ */
        .achievement-feed {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .achievement-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            gap: 12px;
        }
        
        .achievement-item:last-child {
            border-bottom: none;
        }
        
        .achievement-icon {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--gold), var(--orange));
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .achievement-info {
            flex: 1;
        }
        
        .achievement-name {
            font-weight: 600;
            color: var(--primary);
        }
        
        .achievement-player {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .achievement-time {
            font-size: 0.75rem;
            color: #999;
        }
        
        .achievement-sats {
            font-weight: 700;
            color: var(--bitcoin);
        }
        
        /* ================================================================
         * EMPTY STATE
         * ================================================================ */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }
        
        .empty-state .icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* ================================================================
         * FOOTER
         * ================================================================ */
        footer {
            text-align: center;
            padding: 25px;
            color: #999;
            font-size: 0.85rem;
        }
        
        footer a {
            color: var(--accent);
            text-decoration: none;
        }
        
        /* ================================================================
         * ANIMATIONS
         * ================================================================ */
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            animation: slideIn 0.5s ease-out;
        }
        
        .card:nth-child(2) { animation-delay: 0.1s; }
        .card:nth-child(3) { animation-delay: 0.2s; }
        .card:nth-child(4) { animation-delay: 0.3s; }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .rank-badge.gold {
            animation: pulse 2s infinite;
        }
        
        /* ================================================================
         * RESPONSIVE
         * ================================================================ */
        @media (max-width: 600px) {
            .header h1 { font-size: 1.6rem; }
            .header-nav { position: static; margin-top: 15px; justify-content: center; }
            .stats-bar { gap: 15px; }
            .stat-item .value { font-size: 1.4rem; }
            .container { padding: 15px; }
            .champion-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <!-- ================================================================
     HEADER
     ================================================================ -->
    <header class="header">
        <nav class="header-nav">
            <a href="adaptive_learning.php">📚 Lernen</a>
            <a href="statistics.php">📊 Statistik</a>
            <a href="admin_v4.php">🏠 Admin</a>
        </nav>
        <h1>🏆 Leaderboard</h1>
        <p>Wer ist der beste Lerner?</p>
    </header>
    
    <!-- ================================================================
     STATS BAR
     ================================================================ -->
    <div class="stats-bar">
        <div class="stat-item">
            <div class="value"><?= number_format($totalLearners ?? 0) ?></div>
            <div class="label">👦 Lerner</div>
        </div>
        <div class="stat-item">
            <div class="value"><?= number_format($totalSatsEarned ?? 0) ?></div>
            <div class="label">₿ Sats verdient</div>
        </div>
        <div class="stat-item">
            <div class="value"><?= number_format($totalSessions ?? 0) ?></div>
            <div class="label">📝 Sessions</div>
        </div>
        <div class="stat-item">
            <div class="value"><?= number_format($totalAchievements ?? 0) ?></div>
            <div class="label">🏅 Achievements</div>
        </div>
    </div>
    
    <div class="container">
        <!-- ================================================================
         TOP ROW: All-Time & Weekly
         ================================================================ -->
        <div class="grid-2" style="margin-bottom: 25px;">
            <!-- ALL-TIME CHAMPIONS -->
            <div class="card">
                <div class="card-header">
                    <span class="card-icon">🏆</span>
                    <div>
                        <div class="card-title">Hall of Fame</div>
                        <div class="card-subtitle">Gesamt-Sats verdient</div>
                    </div>
                </div>
                
                <?php if (!empty($leaderboard['top_all_time'])): ?>
                <ul class="leaderboard-list">
                    <?php foreach ($leaderboard['top_all_time'] as $rank => $player): ?>
                    <li class="leaderboard-item rank-<?= $rank + 1 ?>">
                        <?= getRankBadge($rank + 1) ?>
                        <span class="player-avatar"><?= htmlspecialchars($player['avatar']) ?></span>
                        <div class="player-info">
                            <div class="player-name"><?= htmlspecialchars($player['name']) ?></div>
                            <div class="player-meta">🔥 <?= $player['current_streak'] ?> Tage Streak</div>
                        </div>
                        <div class="player-score">
                            <div class="score-value"><?= number_format($player['sats']) ?></div>
                            <div class="score-label">Sats</div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon">🏆</div>
                    <p>Noch keine Lerner registriert</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- WEEKLY CHAMPIONS -->
            <div class="card">
                <div class="card-header">
                    <span class="card-icon">🔥</span>
                    <div>
                        <div class="card-title">Diese Woche</div>
                        <div class="card-subtitle">Ab <?= date('d.m.Y', strtotime('monday this week')) ?></div>
                    </div>
                </div>
                
                <?php if (!empty($leaderboard['top_weekly'])): ?>
                <ul class="leaderboard-list">
                    <?php foreach ($leaderboard['top_weekly'] as $rank => $player): ?>
                    <li class="leaderboard-item rank-<?= $rank + 1 ?>">
                        <?= getRankBadge($rank + 1) ?>
                        <span class="player-avatar"><?= htmlspecialchars($player['avatar']) ?></span>
                        <div class="player-info">
                            <div class="player-name"><?= htmlspecialchars($player['name']) ?></div>
                            <div class="player-meta"><?= $player['weekly_sessions'] ?> Sessions</div>
                        </div>
                        <div class="player-score">
                            <div class="score-value"><?= number_format($player['weekly_sats']) ?></div>
                            <div class="score-label">Sats</div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📅</div>
                    <p>Diese Woche noch keine Aktivität</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ================================================================
         MIDDLE ROW: Accuracy & Streaks
         ================================================================ -->
        <div class="grid-2" style="margin-bottom: 25px;">
            <!-- BEST ACCURACY -->
            <div class="card">
                <div class="card-header">
                    <span class="card-icon">🎯</span>
                    <div>
                        <div class="card-title">Beste Trefferquote</div>
                        <div class="card-subtitle">Mind. 20 Fragen beantwortet</div>
                    </div>
                </div>
                
                <?php if (!empty($leaderboard['top_accuracy'])): ?>
                <ul class="leaderboard-list">
                    <?php foreach ($leaderboard['top_accuracy'] as $rank => $player): ?>
                    <li class="leaderboard-item rank-<?= $rank + 1 ?>">
                        <?= getRankBadge($rank + 1) ?>
                        <span class="player-avatar"><?= htmlspecialchars($player['avatar']) ?></span>
                        <div class="player-info">
                            <div class="player-name"><?= htmlspecialchars($player['name']) ?></div>
                            <div class="player-meta"><?= $player['correct'] ?>/<?= $player['total'] ?> richtig</div>
                        </div>
                        <div class="player-score">
                            <div class="score-value"><?= $player['accuracy'] ?>%</div>
                            <div class="score-label">Quote</div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon">🎯</div>
                    <p>Noch nicht genug Daten</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- LONGEST STREAKS -->
            <div class="card">
                <div class="card-header">
                    <span class="card-icon">⚡</span>
                    <div>
                        <div class="card-title">Längste Streaks</div>
                        <div class="card-subtitle">Tage am Stück gelernt</div>
                    </div>
                </div>
                
                <?php if (!empty($leaderboard['top_streaks'])): ?>
                <ul class="leaderboard-list">
                    <?php foreach ($leaderboard['top_streaks'] as $rank => $player): ?>
                    <li class="leaderboard-item rank-<?= $rank + 1 ?>">
                        <?= getRankBadge($rank + 1) ?>
                        <span class="player-avatar"><?= htmlspecialchars($player['avatar']) ?></span>
                        <div class="player-info">
                            <div class="player-name"><?= htmlspecialchars($player['name']) ?></div>
                            <div class="player-meta">Aktuell: <?= $player['current_streak'] ?> Tage</div>
                        </div>
                        <div class="player-score">
                            <div class="score-value"><?= $player['longest_streak'] ?></div>
                            <div class="score-label">Rekord</div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon">⚡</div>
                    <p>Noch keine Streaks</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ================================================================
         BOTTOM ROW: Module Champions & Achievements
         ================================================================ -->
        <div class="grid-2">
            <!-- MODULE CHAMPIONS -->
            <div class="card">
                <div class="card-header">
                    <span class="card-icon">📚</span>
                    <div>
                        <div class="card-title">Modul-Champions</div>
                        <div class="card-subtitle">Wer hat in welchem Fach die meisten Sats?</div>
                    </div>
                </div>
                
                <?php if (!empty($leaderboard['module_champions'])): ?>
                <div class="champion-grid">
                    <?php foreach ($leaderboard['module_champions'] as $champion): 
                        $mod = strtolower($champion['module']);
                        $icon = $moduleIcons[$mod] ?? '📖';
                    ?>
                    <div class="champion-card">
                        <div class="champion-module"><?= $icon ?></div>
                        <div class="champion-module-name"><?= ucfirst($champion['module']) ?></div>
                        <div class="champion-avatar"><?= htmlspecialchars($champion['avatar']) ?></div>
                        <div class="champion-name"><?= htmlspecialchars($champion['name']) ?></div>
                        <div class="champion-sats"><?= number_format($champion['module_sats']) ?> ₿</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📚</div>
                    <p>Noch keine Modul-Daten</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- RECENT ACHIEVEMENTS -->
            <div class="card">
                <div class="card-header">
                    <span class="card-icon">🏅</span>
                    <div>
                        <div class="card-title">Neueste Achievements</div>
                        <div class="card-subtitle">Frisch freigeschaltet!</div>
                    </div>
                </div>
                
                <?php if (!empty($leaderboard['recent_achievements'])): ?>
                <div class="achievement-feed">
                    <?php foreach ($leaderboard['recent_achievements'] as $ach): ?>
                    <div class="achievement-item">
                        <div class="achievement-icon"><?= htmlspecialchars($ach['achievement_icon']) ?></div>
                        <div class="achievement-info">
                            <div class="achievement-name"><?= htmlspecialchars($ach['achievement_name']) ?></div>
                            <div class="achievement-player"><?= htmlspecialchars($ach['avatar']) ?> <?= htmlspecialchars($ach['name']) ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div class="achievement-sats">+<?= $ach['reward_sats'] ?> ₿</div>
                            <div class="achievement-time"><?= timeAgo($ach['unlocked_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon">🏅</div>
                    <p>Noch keine Achievements freigeschaltet</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer>
        sgiT Education Platform v3.4 | 
        <a href="adaptive_learning.php">📚 Jetzt lernen!</a> | 
        <a href="https://sgit.space">sgit.space</a>
    </footer>
    
    <script>
        // Auto-Refresh alle 60 Sekunden
        setTimeout(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
