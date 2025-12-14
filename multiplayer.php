<?php
/**
 * ============================================================================
 * sgiT Education Platform - Multiplayer Lobby
 * ============================================================================
 * 
 * LAN-Multiplayer Quiz - Match erstellen oder beitreten
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 2025-12-12
 */

session_start();
require_once __DIR__ . '/includes/version.php';
require_once __DIR__ . '/wallet/SessionManager.php';

// Wallet-User prüfen über SessionManager (verwendet sgit_child_id)
$walletUserId = null;
$walletUserName = 'Gast';
$walletUserAvatar = '👤';

// Prüfe SessionManager zuerst
if (SessionManager::isLoggedIn()) {
    $childData = SessionManager::getChild();
    if ($childData) {
        $walletUserId = $childData['id'];
        $walletUserName = $childData['name'];
        $walletUserAvatar = $childData['avatar'] ?? '👤';
        
        // Sync in Standard-Session für API
        $_SESSION['wallet_child_id'] = $childData['id'];
        $_SESSION['user_name'] = $childData['name'];
    }
}
// Fallback: Standard Session-Keys
elseif (isset($_SESSION['wallet_child_id'])) {
    $walletUserId = $_SESSION['wallet_child_id'];
    $walletUserName = $_SESSION['user_name'] ?? 'Gast';
}

// Datenbank für User-Liste
$walletDb = new PDO('sqlite:' . __DIR__ . '/wallet/wallet.db');
$walletDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Alle Wallet-User für Dropdown laden
$users = $walletDb->query("SELECT id, child_name, avatar, elo_rating FROM child_wallets WHERE is_active = 1 ORDER BY child_name")->fetchAll(PDO::FETCH_ASSOC);

// User-Stats laden wenn eingeloggt
$userStats = null;
if ($walletUserId) {
    $stmt = $walletDb->prepare("SELECT * FROM child_wallets WHERE id = ?");
    $stmt->execute([$walletUserId]);
    $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Avatar aus DB holen
    if ($userStats) {
        $walletUserAvatar = $userStats['avatar'] ?? '👤';
    }
}

// Module für Auswahl
$modules = [
    'mathematik' => ['icon' => '🔢', 'name' => 'Mathematik'],
    'englisch' => ['icon' => '🇬🇧', 'name' => 'Englisch'],
    'lesen' => ['icon' => '📖', 'name' => 'Lesen'],
    'wissenschaft' => ['icon' => '🔬', 'name' => 'Wissenschaft'],
    'erdkunde' => ['icon' => '🌍', 'name' => 'Erdkunde'],
    'chemie' => ['icon' => '⚗️', 'name' => 'Chemie'],
    'physik' => ['icon' => '⚛️', 'name' => 'Physik'],
    'geschichte' => ['icon' => '📚', 'name' => 'Geschichte'],
    'biologie' => ['icon' => '🧬', 'name' => 'Biologie'],
    'computer' => ['icon' => '💻', 'name' => 'Computer'],
    'bitcoin' => ['icon' => '₿', 'name' => 'Bitcoin'],
    'finanzen' => ['icon' => '💰', 'name' => 'Finanzen'],
    'kunst' => ['icon' => '🎨', 'name' => 'Kunst'],
    'musik' => ['icon' => '🎵', 'name' => 'Musik'],
    'programmieren' => ['icon' => '👨‍💻', 'name' => 'Programmieren'],
    'verkehr' => ['icon' => '🚗', 'name' => 'Verkehr'],
    'sport' => ['icon' => '🏃', 'name' => 'Sport'],
    'unnuetzes_wissen' => ['icon' => '🤯', 'name' => 'Unnützes Wissen'],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚔️ Multiplayer - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg-dark: #0a0f02;
            --bg-card: #111a05;
            --primary: #1A3503;
            --accent: #43D240;
            --orange: #E86F2C;
            --text: #e8f5e9;
            --text-dim: #81c784;
            --border: #2d4a0a;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, var(--primary) 100%);
            min-height: 100vh;
            color: var(--text);
        }
        
        /* Header */
        .header {
            background: rgba(0,0,0,0.3);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .back-btn {
            background: var(--primary);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: var(--accent);
            color: var(--bg-dark);
        }
        
        .title {
            font-size: 24px;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(0,0,0,0.2);
            padding: 8px 15px;
            border-radius: 20px;
        }
        
        .user-avatar { font-size: 24px; }
        .user-name { font-weight: 600; }
        .user-elo { color: var(--accent); font-size: 14px; }
        
        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        /* Views */
        .view { display: none; }
        .view.active { display: block; }
        
        /* Menu View */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .menu-card {
            background: var(--bg-card);
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .menu-card:hover {
            border-color: var(--accent);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(67, 210, 64, 0.2);
        }
        
        .menu-card.orange:hover {
            border-color: var(--orange);
            box-shadow: 0 10px 30px rgba(232, 111, 44, 0.2);
        }
        
        .menu-icon { font-size: 48px; margin-bottom: 15px; }
        .menu-title { font-size: 20px; font-weight: 700; margin-bottom: 10px; }
        .menu-desc { color: var(--text-dim); font-size: 14px; }
        
        /* Stats Box */
        .stats-box {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 15px;
            text-align: center;
        }
        
        .stat-item .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--accent);
        }
        
        .stat-item .stat-label {
            font-size: 12px;
            color: var(--text-dim);
        }
        
        /* Create/Join Forms */
        .form-section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .form-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dim);
            font-size: 14px;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 16px;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        .code-input {
            font-size: 24px !important;
            text-align: center;
            letter-spacing: 8px;
            text-transform: uppercase;
            font-family: monospace;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #2ecc71);
            color: var(--bg-dark);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(67, 210, 64, 0.4);
        }
        
        .btn-orange {
            background: linear-gradient(135deg, var(--orange), #FF8C42);
            color: white;
        }
        
        .btn-orange:hover {
            box-shadow: 0 5px 20px rgba(232, 111, 44, 0.4);
        }
        
        .btn-secondary {
            background: var(--primary);
            border: 1px solid var(--border);
            color: var(--text);
        }
        
        /* Lobby View */
        .lobby-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .match-code {
            background: rgba(0,0,0,0.3);
            padding: 15px 30px;
            border-radius: 12px;
            text-align: center;
        }
        
        .match-code .label { font-size: 12px; color: var(--text-dim); }
        .match-code .code {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 4px;
            color: var(--accent);
            font-family: monospace;
        }
        
        .players-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .player-card {
            background: var(--bg-card);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        
        .player-card.ready { border-color: var(--accent); }
        .player-card.empty {
            border-style: dashed;
            opacity: 0.5;
        }
        
        .player-avatar { font-size: 40px; margin-bottom: 10px; }
        .player-name { font-weight: 600; margin-bottom: 5px; }
        .player-elo { font-size: 12px; color: var(--text-dim); }
        .player-team {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        .ready-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            color: var(--accent);
            font-size: 20px;
        }
        
        .lobby-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .match-info {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .info-badge {
            background: rgba(0,0,0,0.3);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .info-badge .icon { margin-right: 5px; }
        
        /* Quiz View */
        .quiz-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.3);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .timer {
            font-size: 32px;
            font-weight: 700;
            color: var(--accent);
        }
        
        .timer.warning { color: var(--orange); }
        .timer.danger { color: #e74c3c; animation: pulse 0.5s infinite; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .question-box {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .question-number {
            color: var(--text-dim);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .question-text {
            font-size: 22px;
            font-weight: 600;
            line-height: 1.5;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .option-btn {
            background: var(--bg-card);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            font-size: 16px;
            color: var(--text);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option-btn:hover:not(:disabled) {
            border-color: var(--accent);
            background: rgba(67, 210, 64, 0.1);
        }
        
        .option-btn.selected {
            border-color: var(--accent);
            background: rgba(67, 210, 64, 0.2);
        }
        
        .option-btn.correct {
            border-color: var(--accent);
            background: rgba(67, 210, 64, 0.3);
        }
        
        .option-btn.wrong {
            border-color: #e74c3c;
            background: rgba(231, 76, 60, 0.3);
        }
        
        .option-btn:disabled { cursor: not-allowed; opacity: 0.7; }
        
        .scoreboard {
            display: flex;
            justify-content: space-around;
            background: rgba(0,0,0,0.3);
            padding: 15px;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .score-item {
            text-align: center;
        }
        
        .score-item .avatar { font-size: 24px; }
        .score-item .name { font-size: 14px; margin: 5px 0; }
        .score-item .score { font-size: 20px; font-weight: 700; color: var(--accent); }
        
        /* Result View */
        .result-box {
            text-align: center;
            padding: 40px;
        }
        
        .result-icon { font-size: 80px; margin-bottom: 20px; }
        .result-title { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .result-subtitle { color: var(--text-dim); margin-bottom: 30px; }
        
        .result-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .result-stat {
            background: rgba(0,0,0,0.2);
            padding: 20px;
            border-radius: 12px;
        }
        
        .result-stat .value {
            font-size: 28px;
            font-weight: 700;
            color: var(--accent);
        }
        
        .result-stat .label {
            font-size: 14px;
            color: var(--text-dim);
        }
        
        .final-scores {
            margin: 30px 0;
        }
        
        .final-score-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.2);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 10px;
        }
        
        .final-score-row.winner {
            border: 2px solid var(--accent);
            background: rgba(67, 210, 64, 0.1);
        }
        
        .final-score-row .player {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .final-score-row .score {
            font-size: 24px;
            font-weight: 700;
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 15px 20px;
            display: none;
            z-index: 1000;
            animation: slideIn 0.3s;
        }
        
        .toast.show { display: flex; align-items: center; gap: 10px; }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: var(--text-dim);
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Responsive */
        @media (max-width: 600px) {
            .options-grid { grid-template-columns: 1fr; }
            .menu-grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <a href="/adaptive_learning.php" class="back-btn">← Zurück</a>
            <div class="title">⚔️ Multiplayer Quiz</div>
        </div>
        <div class="user-info">
            <span class="user-avatar"><?= htmlspecialchars($walletUserAvatar) ?></span>
            <span class="user-name"><?= htmlspecialchars($walletUserName) ?></span>
            <?php if ($userStats): ?>
            <span class="user-elo">🏆 <?= $userStats['elo_rating'] ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="main-container">
        
        <!-- LOGIN REQUIRED -->
        <?php if (!$walletUserId): ?>
        <div class="form-section" style="text-align: center;">
            <div style="font-size: 48px; margin-bottom: 20px;">🔐</div>
            <h2>Wallet-Login erforderlich</h2>
            <p style="color: var(--text-dim); margin: 20px 0;">
                Um Multiplayer zu spielen, musst du mit deinem Wallet-Account eingeloggt sein.
            </p>
            <a href="/adaptive_learning.php" class="btn btn-primary">
                Zum Login →
            </a>
        </div>
        <?php else: ?>
        
        <!-- MENU VIEW -->
        <div id="menuView" class="view active">
            
            <!-- Stats -->
            <?php if ($userStats): ?>
            <div class="stats-box">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?= $userStats['matches_played'] ?></div>
                        <div class="stat-label">Matches</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $userStats['matches_won'] ?></div>
                        <div class="stat-label">Siege</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $userStats['matches_lost'] ?></div>
                        <div class="stat-label">Niederlagen</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $userStats['elo_rating'] ?></div>
                        <div class="stat-label">Elo Rating</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $userStats['elo_peak'] ?></div>
                        <div class="stat-label">Elo Peak</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Menu Cards -->
            <div class="menu-grid">
                <div class="menu-card" onclick="showView('createView')">
                    <div class="menu-icon">🎮</div>
                    <div class="menu-title">Match erstellen</div>
                    <div class="menu-desc">Erstelle ein Match und lade Freunde ein</div>
                </div>
                <div class="menu-card orange" onclick="showView('joinView')">
                    <div class="menu-icon">🎫</div>
                    <div class="menu-title">Match beitreten</div>
                    <div class="menu-desc">Tritt einem Match mit Code bei</div>
                </div>
                <div class="menu-card" onclick="showView('historyView'); loadHistory();">
                    <div class="menu-icon">📜</div>
                    <div class="menu-title">Match-History</div>
                    <div class="menu-desc">Deine letzten Duelle ansehen</div>
                </div>
            </div>
            
            <!-- Multiplayer Spiele Hub -->
            <div class="games-hub" style="margin-top: 30px;">
                <h2 style="color: var(--accent); margin-bottom: 20px; font-size: 1.3rem;">🎲 Weitere Multiplayer-Spiele</h2>
                <div class="menu-grid">
                    <a href="/montagsmaler.php" class="menu-card" style="text-decoration: none; color: inherit;">
                        <div class="menu-icon">🎨</div>
                        <div class="menu-title">Montagsmaler</div>
                        <div class="menu-desc">Zeichne & Rate mit Freunden!</div>
                        <span style="position: absolute; top: 10px; right: 10px; background: var(--accent); color: var(--bg-dark); padding: 2px 8px; border-radius: 10px; font-size: 11px;">NEU</span>
                    </a>
                    <a href="/madn.php" class="menu-card" style="text-decoration: none; color: inherit;">
                        <div class="menu-icon">🎲</div>
                        <div class="menu-title">Mensch ärgere dich nicht</div>
                        <div class="menu-desc">Der Klassiker für 2-4 Spieler!</div>
                        <span style="position: absolute; top: 10px; right: 10px; background: var(--accent); color: var(--bg-dark); padding: 2px 8px; border-radius: 10px; font-size: 11px;">NEU</span>
                    </a>
                    <a href="/maumau.php" class="menu-card" style="text-decoration: none; color: inherit;">
                        <div class="menu-icon">🃏</div>
                        <div class="menu-title">Mau Mau</div>
                        <div class="menu-desc">Das Kartenspiel für 2-4 Spieler!</div>
                        <span style="position: absolute; top: 10px; right: 10px; background: var(--accent); color: var(--bg-dark); padding: 2px 8px; border-radius: 10px; font-size: 11px;">NEU</span>
                    </a>
                    <a href="/dame.php" class="menu-card" style="text-decoration: none; color: inherit;">
                        <div class="menu-icon">⚫</div>
                        <div class="menu-title">Dame</div>
                        <div class="menu-desc">Klassiker für 2 Spieler!</div>
                        <span style="position: absolute; top: 10px; right: 10px; background: var(--accent); color: var(--bg-dark); padding: 2px 8px; border-radius: 10px; font-size: 11px;">NEU</span>
                    </a>
                    <a href="/schach_pvp.php" class="menu-card" style="text-decoration: none; color: inherit;">
                        <div class="menu-icon">♟️</div>
                        <div class="menu-title">Schach</div>
                        <div class="menu-desc">Das königliche Spiel für 2!</div>
                        <span style="position: absolute; top: 10px; right: 10px; background: var(--accent); color: var(--bg-dark); padding: 2px 8px; border-radius: 10px; font-size: 11px;">NEU</span>
                    </a>
                    <a href="/romme.php" class="menu-card" style="text-decoration: none; color: inherit;">
                        <div class="menu-icon">🎴</div>
                        <div class="menu-title">Rommé</div>
                        <div class="menu-desc">Kartenspiel für 2-4 Spieler!</div>
                        <span style="position: absolute; top: 10px; right: 10px; background: var(--accent); color: var(--bg-dark); padding: 2px 8px; border-radius: 10px; font-size: 11px;">NEU</span>
                    </a>
                    <a href="/poker.php" class="menu-card" style="text-decoration: none; color: inherit;">
                        <div class="menu-icon">🎰</div>
                        <div class="menu-title">Poker</div>
                        <div class="menu-desc">Texas Hold'em für 2-8!</div>
                        <span style="position: absolute; top: 10px; right: 10px; background: var(--accent); color: var(--bg-dark); padding: 2px 8px; border-radius: 10px; font-size: 11px;">NEU</span>
                    </a>
                </div>
            </div>
        </div>
        <!-- CREATE VIEW -->
        <div id="createView" class="view">
            <div class="form-section">
                <div class="form-title">
                    <span>🎮</span> Neues Match erstellen
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Spielmodus</label>
                        <select id="matchType">
                            <option value="1v1">⚔️ 1 vs 1 - Duell</option>
                            <option value="2v2">👥 2 vs 2 - Team</option>
                            <option value="coop">🤝 Coop - Zusammen</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Modul</label>
                        <select id="moduleSelect">
                            <?php foreach ($modules as $key => $mod): ?>
                            <option value="<?= $key ?>"><?= $mod['icon'] ?> <?= $mod['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Fragen</label>
                        <select id="questionsCount">
                            <option value="5">5 Fragen</option>
                            <option value="10" selected>10 Fragen</option>
                            <option value="15">15 Fragen</option>
                            <option value="20">20 Fragen</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Zeit pro Frage</label>
                        <select id="timePerQuestion">
                            <option value="10">10 Sekunden</option>
                            <option value="15" selected>15 Sekunden</option>
                            <option value="20">20 Sekunden</option>
                            <option value="30">30 Sekunden</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>🎰 Sats-Einsatz (0 = kein Einsatz)</label>
                    <input type="number" id="satsBet" value="0" min="0" max="100" step="5">
                    <small style="color: var(--text-dim);">
                        Dein Guthaben: <?= $userStats['balance_sats'] ?? 0 ?> Sats
                    </small>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button class="btn btn-secondary" onclick="showView('menuView')">Abbrechen</button>
                    <button class="btn btn-primary" onclick="createMatch()">🎮 Match erstellen</button>
                </div>
            </div>
        </div>
        
        <!-- JOIN VIEW -->
        <div id="joinView" class="view">
            <div class="form-section">
                <div class="form-title">
                    <span>🎫</span> Match beitreten
                </div>
                
                <div class="form-group">
                    <label>Match-Code eingeben</label>
                    <input type="text" id="joinCode" class="code-input" maxlength="6" placeholder="ABC123" 
                           oninput="this.value = this.value.toUpperCase()">
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button class="btn btn-secondary" onclick="showView('menuView')">Abbrechen</button>
                    <button class="btn btn-orange" onclick="joinMatch()">🎫 Beitreten</button>
                </div>
            </div>
        </div>
        
        <!-- LOBBY VIEW -->
        <div id="lobbyView" class="view">
            <div class="form-section">
                <div class="lobby-header">
                    <div class="form-title" style="margin: 0;">
                        <span>🏟️</span> Match-Lobby
                    </div>
                    <div class="match-code">
                        <div class="label">Match-Code</div>
                        <div class="code" id="lobbyCode">------</div>
                    </div>
                </div>
                
                <div class="match-info" id="matchInfo">
                    <!-- Wird dynamisch gefüllt -->
                </div>
                
                <div class="players-grid" id="playersGrid">
                    <!-- Wird dynamisch gefüllt -->
                </div>
                
                <div class="lobby-actions">
                    <button class="btn btn-secondary" onclick="leaveMatch()">❌ Verlassen</button>
                    <button class="btn btn-primary" id="readyBtn" onclick="toggleReady()">✅ Bereit</button>
                    <button class="btn btn-orange" id="startBtn" onclick="startMatch()" style="display: none;">
                        🚀 Match starten
                    </button>
                </div>
            </div>
        </div>
        
        <!-- QUIZ VIEW -->
        <div id="quizView" class="view">
            <div class="quiz-header-bar">
                <div>
                    <span id="quizModuleIcon">🔢</span>
                    <span id="quizModuleName">Mathematik</span>
                </div>
                <div class="timer" id="timer">15</div>
                <div>
                    Frage <span id="questionNum">1</span>/<span id="questionTotal">10</span>
                </div>
            </div>
            
            <div class="question-box">
                <div class="question-number" id="questionLabel">Frage 1 von 10</div>
                <div class="question-text" id="questionText">Lade Frage...</div>
            </div>
            
            <div class="options-grid" id="optionsGrid">
                <!-- Wird dynamisch gefüllt -->
            </div>
            
            <div class="scoreboard" id="scoreboard">
                <!-- Wird dynamisch gefüllt -->
            </div>
        </div>
        
        <!-- RESULT VIEW -->
        <div id="resultView" class="view">
            <div class="form-section">
                <div class="result-box">
                    <div class="result-icon" id="resultIcon">🏆</div>
                    <div class="result-title" id="resultTitle">Match beendet!</div>
                    <div class="result-subtitle" id="resultSubtitle">Gut gespielt!</div>
                    
                    <div class="final-scores" id="finalScores">
                        <!-- Wird dynamisch gefüllt -->
                    </div>
                    
                    <div class="result-stats" id="resultStats">
                        <!-- Wird dynamisch gefüllt -->
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <button class="btn btn-primary" onclick="showView('menuView'); resetMatch();">
                            🏠 Zurück zum Menü
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- HISTORY VIEW -->
        <div id="historyView" class="view">
            <div class="form-section">
                <div class="form-title">
                    <span>📜</span> Match-History
                    <button class="btn btn-secondary" style="margin-left: auto;" onclick="showView('menuView')">
                        ← Zurück
                    </button>
                </div>
                <div id="historyList">
                    <div class="loading">
                        <div class="spinner"></div>
                        Lade History...
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
    
    <!-- Toast -->
    <div class="toast" id="toast">
        <span id="toastIcon">ℹ️</span>
        <span id="toastText">Nachricht</span>
    </div>
    
    <script>
        // ================================================================
        // State
        // ================================================================
        let currentMatchId = null;
        let currentMatchCode = null;
        let isHost = false;
        let isReady = false;
        let pollInterval = null;
        let timerInterval = null;
        let currentQuestion = null;
        let timeLeft = 15;
        let questionStartTime = 0;
        let hasAnswered = false;
        
        const modules = <?= json_encode($modules) ?>;
        const userId = <?= $walletUserId ?? 'null' ?>;
        
        // ================================================================
        // View Management
        // ================================================================
        function showView(viewId) {
            document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
            document.getElementById(viewId)?.classList.add('active');
            
            // Polling stoppen wenn nicht in Lobby/Quiz
            if (viewId !== 'lobbyView' && viewId !== 'quizView') {
                stopPolling();
            }
        }
        
        function showToast(message, icon = 'ℹ️') {
            const toast = document.getElementById('toast');
            document.getElementById('toastIcon').textContent = icon;
            document.getElementById('toastText').textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        
        // ================================================================
        // API Calls
        // ================================================================
        async function apiCall(action, data = {}) {
            console.log('API Call:', action, data);
            try {
                const response = await fetch('/api/match.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, ...data })
                });
                console.log('API Response status:', response.status);
                const text = await response.text();
                console.log('API Response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e, 'Text was:', text);
                    return { success: false, error: 'JSON Parse Error: ' + text.substring(0, 100) };
                }
            } catch (err) {
                console.error('API Error:', err);
                return { success: false, error: 'Netzwerkfehler: ' + err.message };
            }
        }
        
        async function apiGet(action, params = {}) {
            const query = new URLSearchParams({ action, ...params }).toString();
            console.log('API GET:', action, params);
            try {
                const response = await fetch(`/api/match.php?${query}`);
                console.log('API GET Response status:', response.status);
                const text = await response.text();
                console.log('API GET Response text:', text.substring(0, 200));
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    return { success: false, error: 'JSON Parse Error' };
                }
            } catch (err) {
                console.error('API GET Error:', err);
                return { success: false, error: 'Netzwerkfehler' };
            }
        }
        
        // ================================================================
        // Create Match
        // ================================================================
        async function createMatch() {
            const data = {
                match_type: document.getElementById('matchType').value,
                module: document.getElementById('moduleSelect').value,
                questions: parseInt(document.getElementById('questionsCount').value),
                time: parseInt(document.getElementById('timePerQuestion').value),
                sats_bet: parseInt(document.getElementById('satsBet').value)
            };
            
            console.log('Creating match with data:', data);
            const result = await apiCall('create', data);
            console.log('Create result:', result);
            
            if (result.success) {
                currentMatchId = result.match_id;
                currentMatchCode = result.match_code;
                isHost = true;
                
                console.log('Match created! Code:', result.match_code, 'ID:', result.match_id);
                
                // Code sofort anzeigen (bevor Polling läuft)
                const codeEl = document.getElementById('lobbyCode');
                console.log('lobbyCode element:', codeEl);
                if (codeEl) {
                    codeEl.textContent = result.match_code || 'NO CODE';
                    console.log('Code set to:', codeEl.textContent);
                }
                
                showToast('Match erstellt! Code: ' + result.match_code, '🎮');
                showView('lobbyView');
                startPolling(); // Polling füllt den Rest der Lobby
            } else {
                console.error('Create failed:', result);
                showToast(result.error || result.message || 'Fehler beim Erstellen', '❌');
            }
        }
        
        // ================================================================
        // Join Match
        // ================================================================
        async function joinMatch() {
            const code = document.getElementById('joinCode').value.trim().toUpperCase();
            
            if (code.length !== 6) {
                showToast('Code muss 6 Zeichen haben', '⚠️');
                return;
            }
            
            const result = await apiCall('join', { code });
            
            if (result.success) {
                currentMatchId = result.match_id;
                currentMatchCode = result.match_code || code;
                isHost = false;
                showToast('Match beigetreten!', '🎫');
                showView('lobbyView');
                startPolling();
            } else {
                showToast(result.error || 'Fehler beim Beitreten', '❌');
            }
        }
        
        // ================================================================
        // Leave Match
        // ================================================================
        async function leaveMatch() {
            if (!currentMatchId) return;
            
            const result = await apiCall('leave', { match_id: currentMatchId });
            resetMatch();
            showView('menuView');
            showToast('Match verlassen', '👋');
        }
        
        function resetMatch() {
            currentMatchId = null;
            currentMatchCode = null;
            isHost = false;
            isReady = false;
            stopPolling();
            stopTimer();
        }
        
        // ================================================================
        // Ready Toggle
        // ================================================================
        async function toggleReady() {
            if (!currentMatchId) return;
            
            const result = await apiCall('ready', { match_id: currentMatchId });
            
            if (result.success) {
                isReady = result.is_ready;
                document.getElementById('readyBtn').textContent = isReady ? '⏳ Warten...' : '✅ Bereit';
                document.getElementById('readyBtn').classList.toggle('btn-secondary', isReady);
                document.getElementById('readyBtn').classList.toggle('btn-primary', !isReady);
            }
        }
        
        // ================================================================
        // Start Match (Host only)
        // ================================================================
        async function startMatch() {
            if (!currentMatchId || !isHost) return;
            
            const result = await apiCall('start', { match_id: currentMatchId });
            
            if (result.success) {
                showToast('Match startet!', '🚀');
            } else {
                showToast(result.error || 'Fehler beim Starten', '❌');
            }
        }
        
        // ================================================================
        // Polling
        // ================================================================
        function startPolling() {
            stopPolling();
            pollMatch(); // Sofort einmal
            pollInterval = setInterval(pollMatch, 500); // Alle 500ms
        }
        
        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }
        
        async function pollMatch() {
            if (!currentMatchId) return;
            
            const result = await apiGet('status', { match_id: currentMatchId });
            console.log('Poll result:', result);
            
            if (!result.success) {
                console.error('Poll error:', result.error);
                return;
            }
            
            // Prüfen ob match-Daten vorhanden sind
            if (!result.match) {
                console.error('Poll: No match data in response');
                return;
            }
            
            const match = result.match;
            
            // Status-basierte View-Wechsel
            if (match.status === 'waiting' || match.status === 'ready') {
                updateLobby(result);
            } else if (match.status === 'running') {
                if (document.getElementById('lobbyView').classList.contains('active')) {
                    showView('quizView');
                    initQuiz(result);
                }
                updateQuiz(result);
            } else if (match.status === 'finished') {
                stopPolling();
                showResult(result);
            } else if (match.status === 'cancelled') {
                stopPolling();
                showToast('Match wurde abgebrochen', '❌');
                resetMatch();
                showView('menuView');
            }
        }
        
        // ================================================================
        // Lobby Update
        // ================================================================
        function updateLobby(data) {
            console.log('updateLobby called with:', data);
            
            // Robuste Prüfung
            if (!data || !data.match) {
                console.error('updateLobby: Invalid data, no match object');
                return;
            }
            
            const match = data.match;
            const players = data.players || [];
            
            // Code anzeigen
            document.getElementById('lobbyCode').textContent = match.code || currentMatchCode;
            currentMatchCode = match.code || currentMatchCode;
            
            // Match-Info
            const mod = modules[match.module] || { icon: '❓', name: match.module };
            document.getElementById('matchInfo').innerHTML = `
                <div class="info-badge"><span class="icon">${mod.icon}</span> ${mod.name}</div>
                <div class="info-badge"><span class="icon">❓</span> ${match.questions_total} Fragen</div>
                <div class="info-badge"><span class="icon">⏱️</span> ${match.time_per_question}s</div>
                <div class="info-badge"><span class="icon">🎮</span> ${match.type}</div>
                ${match.sats_pool > 0 ? `<div class="info-badge"><span class="icon">🎰</span> ${match.sats_pool} Sats Pool</div>` : ''}
            `;
            
            // Spieler anzeigen
            const maxPlayers = match.type === '2v2' ? 4 : 2;
            let html = '';
            
            for (let i = 0; i < maxPlayers; i++) {
                const p = players[i];
                if (p) {
                    const isMe = p.player_id == userId;
                    html += `
                        <div class="player-card ${p.is_ready ? 'ready' : ''}">
                            ${p.is_ready ? '<div class="ready-badge">✅</div>' : ''}
                            <div class="player-team">Team ${p.team}</div>
                            <div class="player-avatar">${p.avatar}</div>
                            <div class="player-name">${p.child_name} ${isMe ? '(Du)' : ''}</div>
                            <div class="player-elo">🏆 ${p.elo_rating}</div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="player-card empty">
                            <div class="player-avatar">❓</div>
                            <div class="player-name">Wartend...</div>
                        </div>
                    `;
                }
            }
            document.getElementById('playersGrid').innerHTML = html;
            
            // Host-Buttons
            isHost = data.is_host;
            const startBtn = document.getElementById('startBtn');
            const allReady = players.length >= 2 && players.every(p => p.is_ready);
            
            if (isHost && players.length >= 2) {
                startBtn.style.display = 'inline-flex';
                startBtn.disabled = !allReady;
            } else {
                startBtn.style.display = 'none';
            }
        }
        
        // ================================================================
        // Quiz Functions
        // ================================================================
        function initQuiz(data) {
            const match = data.match;
            const mod = modules[match.module] || { icon: '❓', name: match.module };
            
            document.getElementById('quizModuleIcon').textContent = mod.icon;
            document.getElementById('quizModuleName').textContent = mod.name;
            document.getElementById('questionTotal').textContent = match.questions_total;
            
            timeLeft = match.time_per_question;
            hasAnswered = false;
        }
        
        function updateQuiz(data) {
            const match = data.match;
            const question = data.current_question;
            const players = data.players || [];
            const answers = data.answers_given || [];
            
            // Frage aktualisieren
            if (question && question.index !== currentQuestion?.index) {
                currentQuestion = question;
                hasAnswered = false;
                questionStartTime = Date.now();
                
                document.getElementById('questionNum').textContent = question.index;
                document.getElementById('questionLabel').textContent = `Frage ${question.index} von ${match.questions_total}`;
                document.getElementById('questionText').textContent = question.question;
                
                // Optionen
                let optHtml = '';
                question.options.forEach(opt => {
                    optHtml += `<button class="option-btn" onclick="submitAnswer('${opt.replace(/'/g, "\\'")}')">${opt}</button>`;
                });
                document.getElementById('optionsGrid').innerHTML = optHtml;
                
                // Timer starten
                timeLeft = match.time_per_question;
                startTimer();
            }
            
            // Prüfen ob wir schon geantwortet haben
            const myAnswer = answers.find(a => a.player_id == userId);
            if (myAnswer && !hasAnswered) {
                hasAnswered = true;
                // Buttons deaktivieren
                document.querySelectorAll('.option-btn').forEach(btn => btn.disabled = true);
            }
            
            // Scoreboard
            let scoreHtml = '';
            players.forEach(p => {
                const answered = answers.some(a => a.player_id == p.player_id);
                scoreHtml += `
                    <div class="score-item">
                        <div class="avatar">${p.avatar}</div>
                        <div class="name">${p.child_name} ${answered ? '✅' : '⏳'}</div>
                        <div class="score">${p.score}</div>
                    </div>
                `;
            });
            document.getElementById('scoreboard').innerHTML = scoreHtml;
        }
        
        // ================================================================
        // Timer
        // ================================================================
        function startTimer() {
            stopTimer();
            updateTimerDisplay();
            timerInterval = setInterval(() => {
                timeLeft--;
                updateTimerDisplay();
                
                if (timeLeft <= 0) {
                    stopTimer();
                    if (!hasAnswered) {
                        submitAnswer(''); // Timeout = keine Antwort
                    }
                }
            }, 1000);
        }
        
        function stopTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
        }
        
        function updateTimerDisplay() {
            const timer = document.getElementById('timer');
            timer.textContent = timeLeft;
            timer.classList.remove('warning', 'danger');
            if (timeLeft <= 5) timer.classList.add('danger');
            else if (timeLeft <= 10) timer.classList.add('warning');
        }
        
        // ================================================================
        // Submit Answer
        // ================================================================
        async function submitAnswer(answer) {
            if (hasAnswered || !currentMatchId) return;
            hasAnswered = true;
            stopTimer();
            
            const timeTaken = Date.now() - questionStartTime;
            
            // Button markieren
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.disabled = true;
                if (btn.textContent === answer) {
                    btn.classList.add('selected');
                }
            });
            
            const result = await apiCall('answer', {
                match_id: currentMatchId,
                answer: answer,
                time_taken_ms: timeTaken
            });
            
            if (result.success) {
                // Richtig/Falsch anzeigen
                document.querySelectorAll('.option-btn').forEach(btn => {
                    if (btn.textContent === result.correct_answer) {
                        btn.classList.add('correct');
                    }
                    if (btn.textContent === answer && !result.is_correct) {
                        btn.classList.add('wrong');
                    }
                });
                
                showToast(
                    result.is_correct ? `+${result.points_earned} Punkte!` : 'Leider falsch!',
                    result.is_correct ? '✅' : '❌'
                );
            }
        }
        
        // ================================================================
        // Show Result
        // ================================================================
        function showResult(data) {
            showView('resultView');
            stopTimer();
            
            const match = data.match;
            const players = data.players || [];
            const userPlayer = data.user_player;
            
            console.log('showResult - match:', match);
            console.log('showResult - winner_id:', match.winner_id, 'userId:', userId);
            console.log('showResult - players:', players);
            
            // Gewinner ermitteln
            const isWinner = match.winner_id && match.winner_id == userId;
            const isDraw = !match.winner_id && match.status === 'finished';
            
            // Icon & Titel
            let icon = '🎮';
            let title = 'Match beendet!';
            let subtitle = 'Gut gespielt!';
            
            if (isWinner) {
                icon = '🏆';
                title = 'Du hast gewonnen!';
                subtitle = match.sats_pool > 0 ? `+${match.sats_pool} Sats!` : 'Herzlichen Glückwunsch!';
            } else if (isDraw) {
                icon = '🤝';
                title = 'Unentschieden!';
                subtitle = match.sats_pool > 0 ? 'Einsatz zurück!' : 'Was ein Kopf-an-Kopf-Rennen!';
            } else if (match.winner_id) {
                icon = '😢';
                title = 'Leider verloren...';
                subtitle = 'Nächstes Mal klappt es!';
            }
            
            document.getElementById('resultIcon').textContent = icon;
            document.getElementById('resultTitle').textContent = title;
            document.getElementById('resultSubtitle').textContent = subtitle;
            
            // Final Scores
            const sorted = [...players].sort((a, b) => b.score - a.score);
            let scoresHtml = '';
            sorted.forEach((p, i) => {
                const isMe = p.player_id == userId;
                const winner = p.player_id == match.winner_id;
                scoresHtml += `
                    <div class="final-score-row ${winner ? 'winner' : ''}">
                        <div class="player">
                            <span>${i + 1}.</span>
                            <span>${p.avatar}</span>
                            <span>${p.child_name} ${isMe ? '(Du)' : ''}</span>
                        </div>
                        <div class="score">${p.score}</div>
                    </div>
                `;
            });
            document.getElementById('finalScores').innerHTML = scoresHtml;
            
            // Stats
            if (userPlayer) {
                document.getElementById('resultStats').innerHTML = `
                    <div class="result-stat">
                        <div class="value">${userPlayer.correct_answers}/${match.questions_total}</div>
                        <div class="label">Richtige Antworten</div>
                    </div>
                    <div class="result-stat">
                        <div class="value">${userPlayer.score}</div>
                        <div class="label">Punkte</div>
                    </div>
                    <div class="result-stat">
                        <div class="value">${(userPlayer.total_time_ms / 1000).toFixed(1)}s</div>
                        <div class="label">Gesamt-Zeit</div>
                    </div>
                `;
            }
        }
        
        // ================================================================
        // History
        // ================================================================
        async function loadHistory() {
            const result = await apiGet('history', { limit: 10 });
            
            if (!result.success) {
                document.getElementById('historyList').innerHTML = '<p>Fehler beim Laden</p>';
                return;
            }
            
            const matches = result.matches || [];
            
            if (matches.length === 0) {
                document.getElementById('historyList').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: var(--text-dim);">
                        <div style="font-size: 48px; margin-bottom: 15px;">🎮</div>
                        <p>Noch keine Matches gespielt!</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            matches.forEach(m => {
                const won = m.winner_id == userId;
                const mod = modules[m.module] || { icon: '❓', name: m.module };
                const date = new Date(m.finished_at).toLocaleDateString('de-DE');
                
                html += `
                    <div class="final-score-row ${won ? 'winner' : ''}" style="margin-bottom: 10px;">
                        <div class="player">
                            <span>${mod.icon}</span>
                            <span>${m.match_type}</span>
                            <span style="color: var(--text-dim);">${date}</span>
                        </div>
                        <div>
                            <span style="margin-right: 15px;">${m.score} Punkte</span>
                            <span>${won ? '🏆 Gewonnen' : (m.winner_id ? '❌ Verloren' : '🤝 Unentschieden')}</span>
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('historyList').innerHTML = html;
        }
        
        // ================================================================
        // URL Parameter check (für direkten Beitritt)
        // ================================================================
        window.addEventListener('load', () => {
            const params = new URLSearchParams(window.location.search);
            const code = params.get('code');
            
            if (code && code.length === 6) {
                document.getElementById('joinCode').value = code.toUpperCase();
                showView('joinView');
            }
        });
    </script>
</body>
</html>
