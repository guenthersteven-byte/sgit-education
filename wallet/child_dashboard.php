<?php
/**
 * ============================================================================
 * sgiT Education - Kind-Dashboard
 * ============================================================================
 * 
 * Persönliches Dashboard für Kinder:
 * - Wallet-Balance (Test-Sats)
 * - Achievement-Galerie
 * - Fortschritts-Anzeige
 * - Streak-Tracking
 * - Transaktions-Historie
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 02.12.2025
 * ============================================================================
 */

session_start();

require_once __DIR__ . '/WalletManager.php';
require_once __DIR__ . '/AchievementManager.php';
require_once __DIR__ . '/SessionManager.php';

// Session prüfen
$sessionMgr = new SessionManager();

if (!$sessionMgr->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$childId = $sessionMgr->getChildId();
$childData = $sessionMgr->getChildData();

// Manager initialisieren
$walletMgr = new WalletManager();
$achievementMgr = new AchievementManager();

// Daten laden
$wallet = $walletMgr->getChildWallet($childId);
$transactions = $walletMgr->getTransactions($childId, 20);
$achievementStats = $achievementMgr->getAchievementStats($childId);
$progress = $achievementMgr->getProgress($childId);
$earnedToday = $walletMgr->getEarnedToday($childId);

// Live BTC Kurs für Test-Sats Anzeige
$btcPrice = 0;
$satsPerUsd = 0;
try {
    $priceData = @file_get_contents('https://mempool.space/api/v1/prices');
    if ($priceData) {
        $prices = json_decode($priceData, true);
        $btcPrice = $prices['USD'] ?? 0;
        $satsPerUsd = $btcPrice > 0 ? round(100000000 / $btcPrice) : 0;
    }
} catch (Exception $e) {}

// Kategorien für Tabs
$categories = [
    'learning' => ['name' => 'Lernen', 'icon' => '🎓'],
    'streak' => ['name' => 'Streaks', 'icon' => '🔥'],
    'sats' => ['name' => 'Sats', 'icon' => '₿'],
    'module' => ['name' => 'Module', 'icon' => '📚'],
    'special' => ['name' => 'Spezial', 'icon' => '⭐']
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Dashboard - sgiT Education</title>
    <style>
        /* BUG-057 FIX: Dark Mode für Child Dashboard */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bitcoin: #F7931A;
            --gold: #FFD700;
            --silver: #C0C0C0;
            --bronze: #CD7F32;
            --master: #9B59B6;
            --bg-dark: #0d1a02;
            --card-bg: rgba(0, 0, 0, 0.3);
            --border: rgba(67, 210, 64, 0.3);
            --text: #ffffff;
            --text-muted: #aaaaaa;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, var(--bg-dark), var(--primary));
            min-height: 100vh;
            padding: 20px;
            color: var(--text);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .avatar {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }
        
        .user-details h1 {
            color: var(--accent);
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .user-details .streak {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
        }
        
        .streak-fire {
            color: #FF6B35;
            font-size: 20px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #35B035);
            color: white;
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .stat-card.sats::before { background: var(--bitcoin); }
        .stat-card.achievements::before { background: var(--gold); }
        .stat-card.streak::before { background: #FF6B35; }
        .stat-card.today::before { background: var(--accent); }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--accent);
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 5px;
        }
        
        .stat-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 8px;
        }
        
        /* Test-Sats Warning */
        .test-sats-banner {
            background: linear-gradient(135deg, var(--bitcoin), #E88A00);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .test-sats-banner .warning {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .test-sats-banner .btc-price {
            font-size: 13px;
            opacity: 0.9;
        }
        
        /* Achievement Section */
        .section {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border);
        }
        
        .section-title {
            color: var(--accent);
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .achievement-progress {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .progress-ring {
            width: 60px;
            height: 60px;
            position: relative;
        }
        
        .progress-ring svg {
            transform: rotate(-90deg);
        }
        
        .progress-ring circle {
            fill: none;
            stroke-width: 6;
        }
        
        .progress-ring .bg {
            stroke: rgba(255,255,255,0.1);
        }
        
        .progress-ring .progress {
            stroke: var(--accent);
            stroke-linecap: round;
            transition: stroke-dashoffset 0.5s;
        }
        
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 14px;
            font-weight: 700;
            color: var(--accent);
        }
        
        /* Category Tabs */
        .category-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 10px 20px;
            border: 2px solid var(--border);
            border-radius: 25px;
            background: var(--card-bg);
            color: var(--text);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn:hover {
            border-color: var(--accent);
        }
        
        .tab-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
        
        .tab-btn .count {
            background: rgba(255,255,255,0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        .tab-btn.active .count {
            background: rgba(255,255,255,0.3);
        }
        
        /* Achievement Grid */
        .achievement-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .achievement-card {
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            transition: all 0.3s;
            background: rgba(0,0,0,0.2);
        }
        
        .achievement-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        
        .achievement-card.unlocked {
            border-color: var(--accent);
            background: rgba(67, 210, 64, 0.1);
        }
        
        .achievement-card.locked {
            opacity: 0.7;
        }
        
        .achievement-card.locked .achievement-icon {
            filter: grayscale(100%);
        }
        
        .achievement-header {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .achievement-icon {
            font-size: 40px;
            flex-shrink: 0;
        }
        
        .achievement-info h3 {
            color: var(--accent);
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .achievement-info p {
            font-size: 13px;
            color: var(--text-muted);
        }
        
        .achievement-tier {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .tier-bronze { background: var(--bronze); color: white; }
        .tier-silver { background: var(--silver); color: #333; }
        .tier-gold { background: var(--gold); color: #333; }
        .tier-master { background: var(--master); color: white; }
        
        .achievement-progress-bar {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 15px;
        }
        
        .achievement-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent), #8AFF8A);
            border-radius: 4px;
            transition: width 0.5s;
        }
        
        .achievement-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 12px;
        }
        
        .achievement-footer .progress-text {
            position: static;
            transform: none;
            font-size: 12px;
        }
        
        .achievement-footer .reward {
            color: var(--bitcoin);
            font-weight: 600;
        }
        
        .unlocked-badge {
            position: absolute;
            top: -8px;
            left: -8px;
            background: var(--accent);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(67, 210, 64, 0.4);
        }
        
        /* Transactions */
        .transaction-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--border);
        }
        
        .transaction-item:last-child {
            border-bottom: none;
        }
        
        .transaction-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .transaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .transaction-icon.earn { background: rgba(67, 210, 64, 0.2); }
        .transaction-icon.bonus { background: rgba(247, 147, 26, 0.2); }
        .transaction-icon.withdraw { background: rgba(220, 53, 69, 0.2); }
        
        .transaction-details h4 {
            font-size: 14px;
            color: var(--text);
            margin-bottom: 3px;
        }
        
        .transaction-details span {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .transaction-amount {
            font-weight: 700;
            font-size: 16px;
        }
        
        .transaction-amount.positive { color: var(--accent); }
        .transaction-amount.negative { color: #dc3545; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .achievement-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="user-info">
                <div class="avatar"><?php echo htmlspecialchars($wallet['avatar'] ?? '👧'); ?></div>
                <div class="user-details">
                    <h1>Hallo <?php echo htmlspecialchars($wallet['child_name']); ?>! 👋</h1>
                    <div class="streak">
                        <span class="streak-fire">🔥</span>
                        <span><?php echo $wallet['current_streak']; ?> Tage Streak</span>
                        <?php if ($wallet['longest_streak'] > $wallet['current_streak']): ?>
                            <span style="color: #999;">(Rekord: <?php echo $wallet['longest_streak']; ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="../adaptive_learning.php" class="btn btn-primary">📚 Weiter lernen</a>
                <a href="login.php?logout=1" class="btn btn-secondary">🚪 Abmelden</a>
            </div>
        </header>
        
        <!-- Test-Sats Banner -->
        <div class="test-sats-banner">
            <div class="warning">
                <span>⚠️</span>
                <span>TEST-SATS - Keine echten Satoshis!</span>
            </div>
            <?php if ($btcPrice > 0): ?>
            <div class="btc-price">
                ₿ BTC: $<?php echo number_format($btcPrice); ?> | 1 USD = ~<?php echo number_format($satsPerUsd); ?> Sats
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card sats">
                <div class="stat-icon">₿</div>
                <div class="stat-value"><?php echo number_format($wallet['balance_sats']); ?></div>
                <div class="stat-label">Test-Sats</div>
                <div class="stat-sub">Gesamt verdient: <?php echo number_format($wallet['total_earned']); ?></div>
            </div>
            
            <div class="stat-card achievements">
                <div class="stat-icon">🏆</div>
                <div class="stat-value"><?php echo $achievementStats['unlocked']; ?>/<?php echo $achievementStats['total']; ?></div>
                <div class="stat-label">Achievements</div>
                <div class="stat-sub"><?php echo $achievementStats['percent']; ?>% freigeschaltet</div>
            </div>
            
            <div class="stat-card streak">
                <div class="stat-icon">🔥</div>
                <div class="stat-value"><?php echo $wallet['current_streak']; ?></div>
                <div class="stat-label">Tage-Streak</div>
                <div class="stat-sub">Rekord: <?php echo $wallet['longest_streak']; ?> Tage</div>
            </div>
            
            <div class="stat-card today">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?php echo $earnedToday; ?></div>
                <div class="stat-label">Heute verdient</div>
                <div class="stat-sub">Max: <?php echo $walletMgr->getConfig('daily_earn_limit', 100); ?> Sats/Tag</div>
            </div>
        </div>
        
        <!-- Achievements Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">🏆 Meine Achievements</h2>
                <div class="achievement-progress">
                    <div class="progress-ring">
                        <svg width="60" height="60">
                            <circle class="bg" cx="30" cy="30" r="24"></circle>
                            <circle class="progress" cx="30" cy="30" r="24" 
                                stroke-dasharray="<?php echo 2 * M_PI * 24; ?>"
                                stroke-dashoffset="<?php echo 2 * M_PI * 24 * (1 - $achievementStats['percent'] / 100); ?>">
                            </circle>
                        </svg>
                        <span class="progress-text"><?php echo $achievementStats['percent']; ?>%</span>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: var(--primary);"><?php echo $achievementStats['unlocked']; ?> von <?php echo $achievementStats['total']; ?></div>
                        <div style="font-size: 12px; color: #666;">+<?php echo number_format($achievementStats['total_rewards']); ?> Sats verdient</div>
                    </div>
                </div>
            </div>
            
            <!-- Category Tabs -->
            <div class="category-tabs">
                <button class="tab-btn active" data-category="all">
                    <span>🌟</span>
                    <span>Alle</span>
                    <span class="count"><?php echo $achievementStats['unlocked']; ?>/<?php echo $achievementStats['total']; ?></span>
                </button>
                <?php foreach ($categories as $key => $cat): ?>
                    <button class="tab-btn" data-category="<?php echo $key; ?>">
                        <span><?php echo $cat['icon']; ?></span>
                        <span><?php echo $cat['name']; ?></span>
                        <span class="count"><?php echo $achievementStats['by_category'][$key]['unlocked']; ?>/<?php echo $achievementStats['by_category'][$key]['total']; ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <!-- Achievement Grid -->
            <div class="achievement-grid" id="achievementGrid">
                <?php foreach ($progress as $key => $item): ?>
                    <?php 
                    $a = $item['achievement'];
                    $isUnlocked = $item['unlocked'];
                    $tier = $a['tier'] ?? 'bronze';
                    ?>
                    <div class="achievement-card <?php echo $isUnlocked ? 'unlocked' : 'locked'; ?>" 
                         data-category="<?php echo $a['category']; ?>">
                        
                        <?php if ($isUnlocked): ?>
                            <div class="unlocked-badge">✓</div>
                        <?php endif; ?>
                        
                        <span class="achievement-tier tier-<?php echo $tier; ?>"><?php echo ucfirst($tier); ?></span>
                        
                        <div class="achievement-header">
                            <div class="achievement-icon"><?php echo $a['icon']; ?></div>
                            <div class="achievement-info">
                                <h3><?php echo htmlspecialchars($a['name']); ?></h3>
                                <p><?php echo htmlspecialchars($a['description']); ?></p>
                            </div>
                        </div>
                        
                        <?php if (!$isUnlocked): ?>
                            <div class="achievement-progress-bar">
                                <div class="achievement-progress-fill" style="width: <?php echo $item['percent']; ?>%"></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="achievement-footer">
                            <span class="progress-text">
                                <?php if ($isUnlocked): ?>
                                    ✅ Freigeschaltet
                                <?php else: ?>
                                    <?php echo $item['current']; ?>/<?php echo $item['required']; ?> (<?php echo $item['percent']; ?>%)
                                <?php endif; ?>
                            </span>
                            <span class="reward">+<?php echo $a['reward_sats']; ?> Sats</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Transaktionen -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">📜 Letzte Aktivitäten</h2>
            </div>
            
            <div class="transaction-list">
                <?php if (empty($transactions)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Noch keine Aktivitäten. Starte deine erste Lern-Session! 🚀
                    </p>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <?php
                        $icon = '💰';
                        $iconClass = 'earn';
                        if ($tx['type'] === 'bonus') { $icon = '🏆'; $iconClass = 'bonus'; }
                        if ($tx['type'] === 'withdraw') { $icon = '📤'; $iconClass = 'withdraw'; }
                        ?>
                        <div class="transaction-item">
                            <div class="transaction-info">
                                <div class="transaction-icon <?php echo $iconClass; ?>"><?php echo $icon; ?></div>
                                <div class="transaction-details">
                                    <h4><?php echo htmlspecialchars($tx['reason'] ?? $tx['type']); ?></h4>
                                    <span>
                                        <?php echo $tx['module'] ? htmlspecialchars($tx['module']) . ' • ' : ''; ?>
                                        <?php echo date('d.m.Y H:i', strtotime($tx['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="transaction-amount <?php echo $tx['type'] === 'withdraw' ? 'negative' : 'positive'; ?>">
                                <?php echo $tx['type'] === 'withdraw' ? '-' : '+'; ?><?php echo number_format($tx['amount_sats']); ?> Sats
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Tab-Filterung
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Aktiven Tab wechseln
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const category = this.dataset.category;
                
                // Cards filtern
                document.querySelectorAll('.achievement-card').forEach(card => {
                    if (category === 'all' || card.dataset.category === category) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
