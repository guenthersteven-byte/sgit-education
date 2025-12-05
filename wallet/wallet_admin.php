<?php
/**
 * ============================================================================
 * sgiT Education - Wallet Admin v1.3 (Kind-Löschen)
 * ============================================================================
 * 
 * WICHTIG: Nur über Admin-Dashboard zugänglich!
 * Direkter Zugriff wird verweigert.
 * 
 * v1.3 ÄNDERUNGEN (03.12.2025):
 * - NEU: Kinder löschen mit Bestätigungs-Modal
 * - Sats werden beim Löschen zum Family Wallet zurückerstattet
 * - Verbessertes UI für Kind-Karten
 * 
 * v1.2 ÄNDERUNGEN (03.12.2025):
 * - Link zur wöchentlichen Zusammenfassung
 * 
 * v1.1 ÄNDERUNGEN (03.12.2025):
 * - NEU: Achievement-Übersicht für Eltern
 * - Fortschritt pro Kind mit Kategorien
 * - Letzte freigeschaltete Achievements
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.3
 * @date 03.12.2025
 * ============================================================================
 */

// Admin-Session Check
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    die('⛔ Zugriff verweigert! Nur für Administratoren.');
}

require_once __DIR__ . '/WalletManager.php';
require_once __DIR__ . '/AchievementManager.php';

// ============================================================================
// MANAGER INITIALISIEREN
// ============================================================================
$error = null;
$wallet = null;
$achievementMgr = null;
$stats = [];
$children = [];
$transactions = [];
$childAchievements = []; // NEU: Achievements pro Kind

try {
    $wallet = new WalletManager();
    $achievementMgr = new AchievementManager();
    
    $stats = $wallet->getStats();
    $children = $wallet->getChildWallets();
    $transactions = $wallet->getAllTransactions(20);
    
    // NEU: Achievement-Stats für jedes Kind laden
    foreach ($children as $child) {
        $childAchievements[$child['id']] = $achievementMgr->getAchievementStats($child['id']);
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// ============================================================================
// AKTIONEN VERARBEITEN
// ============================================================================
$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $wallet) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'deposit':
            $amount = (int) ($_POST['amount'] ?? 0);
            if ($amount > 0) {
                if ($wallet->depositToFamily($amount)) {
                    $message = "✅ {$amount} Sats zum Family Wallet hinzugefügt!";
                    $messageType = 'success';
                    $stats = $wallet->getStats();
                } else {
                    $message = "❌ Fehler beim Einzahlen";
                    $messageType = 'error';
                }
            }
            break;
            
        case 'test_earn':
            $childId = (int) ($_POST['child_id'] ?? 0);
            $score = (int) ($_POST['score'] ?? 8);
            $maxScore = 10;
            $module = $_POST['module'] ?? 'test';
            
            if ($childId > 0) {
                $result = $wallet->earnSats($childId, $score, $maxScore, $module);
                if ($result['success']) {
                    $message = "⚡ {$result['sats']} Sats verdient! Neue Balance: {$result['new_balance']} Sats";
                    $messageType = 'success';
                    $children = $wallet->getChildWallets();
                    $transactions = $wallet->getAllTransactions(20);
                    $stats = $wallet->getStats();
                    
                    // Achievements nach Reward prüfen
                    if ($achievementMgr) {
                        $newAchievements = $achievementMgr->checkAndUnlock($childId, [
                            'just_completed_session' => true,
                            'module' => $module,
                            'score' => $score,
                            'perfect' => ($score === $maxScore)
                        ]);
                        if (!empty($newAchievements)) {
                            $achNames = array_column($newAchievements, 'name');
                            $message .= " 🏆 Achievements: " . implode(', ', $achNames);
                        }
                        // Refresh achievement stats
                        $childAchievements[$childId] = $achievementMgr->getAchievementStats($childId);
                    }
                } else {
                    $message = "❌ " . ($result['error'] ?? 'Unbekannter Fehler');
                    $messageType = 'error';
                }
            }
            break;
            
        case 'delete_child':
            $childId = (int) ($_POST['child_id'] ?? 0);
            $confirmName = trim($_POST['confirm_name'] ?? '');
            
            if ($childId > 0) {
                // Kind-Daten zur Verifizierung holen
                $childToDelete = $wallet->getChildWallet($childId);
                
                if (!$childToDelete) {
                    $message = "❌ Kind nicht gefunden!";
                    $messageType = 'error';
                } elseif (strtolower($confirmName) !== strtolower($childToDelete['child_name'])) {
                    $message = "❌ Name stimmt nicht überein! Bestätigung fehlgeschlagen.";
                    $messageType = 'error';
                } else {
                    $result = $wallet->deleteChild($childId);
                    if ($result['success']) {
                        $refundMsg = $result['refunded_sats'] > 0 
                            ? " ({$result['refunded_sats']} Sats zurück ins Family Wallet)" 
                            : "";
                        $message = "✅ Kind '{$result['deleted_name']}' wurde gelöscht!{$refundMsg}";
                        $messageType = 'success';
                        // Listen neu laden
                        $children = $wallet->getChildWallets();
                        $stats = $wallet->getStats();
                        $transactions = $wallet->getAllTransactions(20);
                        $childAchievements = [];
                        foreach ($children as $child) {
                            $childAchievements[$child['id']] = $achievementMgr->getAchievementStats($child['id']);
                        }
                    } else {
                        $message = "❌ Fehler: " . ($result['error'] ?? 'Unbekannter Fehler');
                        $messageType = 'error';
                    }
                }
            }
            break;
    }
}

// Tier-Farben Helper
function getTierColor($tier) {
    switch($tier) {
        case 'bronze': return '#CD7F32';
        case 'silver': return '#C0C0C0';
        case 'gold': return '#FFD700';
        case 'master': return '#9B59B6';
        default: return '#666';
    }
}

// Kategorie-Icons Helper
function getCategoryIcon($category) {
    switch($category) {
        case 'learning': return '🎓';
        case 'streak': return '🔥';
        case 'sats': return '₿';
        case 'module': return '📚';
        case 'special': return '⭐';
        default: return '🏆';
    }
}

// ============================================================================
// HTML OUTPUT
// ============================================================================
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>₿ Wallet Admin - sgiT Education</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1A3503 0%, #2d5a06 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h1 {
            color: #1A3503;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 .btc { color: #f7931a; }
        .admin-badge {
            background: #F44336;
            color: white;
            padding: 5px 15px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .version-badge {
            background: #43D240;
            color: white;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            margin-left: 10px;
        }
        
        .nav-links a {
            display: inline-block;
            padding: 10px 20px;
            background: #43D240;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-left: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .nav-links a:hover {
            background: #3ab837;
            transform: translateY(-2px);
        }
        
        /* Message */
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .message.info { background: #d1ecf1; color: #0c5460; }
        
        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: #1A3503;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #43D240;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card.achievement-card {
            border-left: 4px solid #FFD700;
        }
        
        /* Family Wallet Card */
        .family-balance {
            background: linear-gradient(135deg, #f7931a, #e8850f);
            color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .family-balance .amount {
            font-size: 48px;
            font-weight: bold;
        }
        
        .family-balance .label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #1A3503;
        }
        
        .stat-item .label {
            font-size: 11px;
            color: #666;
        }
        
        /* Child Cards */
        .child-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 10px;
        }
        
        .child-avatar {
            font-size: 40px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 50%;
        }
        
        .child-info {
            flex: 1;
        }
        
        .child-name {
            font-weight: bold;
            color: #1A3503;
        }
        
        .child-balance {
            color: #f7931a;
            font-size: 20px;
            font-weight: bold;
        }
        
        .child-streak {
            font-size: 12px;
            color: #666;
        }
        
        /* Achievement Übersicht */
        .achievement-summary {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .achievement-donut {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }
        
        .achievement-donut-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .achievement-donut-percent {
            font-size: 28px;
            font-weight: bold;
            color: #1A3503;
        }
        
        .achievement-donut-label {
            font-size: 11px;
            color: #666;
        }
        
        .category-progress {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .category-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }
        
        .category-icon {
            font-size: 16px;
            width: 24px;
            text-align: center;
        }
        
        .category-bar {
            flex: 1;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .category-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s;
        }
        
        .category-count {
            font-size: 11px;
            color: #666;
            width: 40px;
            text-align: right;
        }
        
        /* Recent Achievements */
        .recent-achievements {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }
        
        .achievement-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .achievement-badge.bronze { background: #FFF3E0; color: #CD7F32; }
        .achievement-badge.silver { background: #F5F5F5; color: #666; }
        .achievement-badge.gold { background: #FFFDE7; color: #F9A825; }
        .achievement-badge.master { background: #F3E5F5; color: #7B1FA2; }
        
        /* Child Achievement Card */
        .child-achievement-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .child-ach-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .child-ach-name {
            font-weight: bold;
            color: #1A3503;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .child-ach-stats {
            display: flex;
            gap: 15px;
            font-size: 12px;
        }
        
        .child-ach-stat {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .child-ach-stat .value {
            font-weight: bold;
            color: #1A3503;
        }
        
        .tier-distribution {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }
        
        .tier-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: white;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-size: 14px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-group input:focus, .form-group select:focus {
            border-color: #43D240;
            outline: none;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-btc {
            background: #f7931a;
            color: white;
        }
        
        .btn-btc:hover {
            background: #e8850f;
        }
        
        .btn-green {
            background: #43D240;
            color: white;
        }
        
        .btn-green:hover {
            background: #3ab837;
        }
        
        /* Transactions */
        .transaction-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .transaction {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .transaction:last-child {
            border-bottom: none;
        }
        
        .tx-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .tx-icon.earn { background: #d4edda; }
        .tx-icon.withdraw { background: #f8d7da; }
        .tx-icon.bonus { background: #fff3cd; }
        .tx-icon.achievement { background: #e8f5e9; }
        
        .tx-info {
            flex: 1;
        }
        
        .tx-child {
            font-weight: 600;
            color: #1A3503;
        }
        
        .tx-reason {
            font-size: 12px;
            color: #666;
        }
        
        .tx-amount {
            font-weight: bold;
        }
        
        .tx-amount.positive { color: #28a745; }
        .tx-amount.negative { color: #dc3545; }
        
        .tx-time {
            font-size: 11px;
            color: #999;
        }
        
        /* Error State */
        .error-card {
            background: #f8d7da;
            border: 2px solid #dc3545;
            padding: 30px;
            text-align: center;
        }
        
        .error-card h2 {
            color: #dc3545;
            border-bottom-color: #dc3545;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        
        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            .achievement-summary {
                grid-template-columns: 1fr;
            }
        }
        
        /* Delete Button */
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 450px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal h3 {
            color: #dc3545;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .modal p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .modal .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #856404;
        }
        
        .modal .form-group {
            text-align: left;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .child-card-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
    </style>
</head>
<body>
<div class="container">
    
    <!-- Header -->
    <div class="header">
        <h1>
            <span class="btc">₿</span> Wallet Admin 
            <span class="admin-badge">ADMIN</span>
            <span class="version-badge">v1.3</span>
        </h1>
        <div class="nav-links">
            <a href="weekly_summary.php">📊 Wochenbericht</a>
            <a href="../admin_v4.php">🏠 Dashboard</a>
            <a href="register.php">📝 Kind registrieren</a>
        </div>
    </div>
    
    <?php if ($error): ?>
    <!-- Error State -->
    <div class="card error-card">
        <h2>⚠️ Fehler</h2>
        <p><?= htmlspecialchars($error) ?></p>
        <br>
        <a href="setup_wallet_db.php" class="btn btn-btc">🔧 Setup ausführen</a>
    </div>
    
    <?php else: ?>
    
    <!-- Message -->
    <?php if ($message): ?>
    <div class="message <?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <!-- Main Grid -->
    <div class="grid">
        
        <!-- Family Wallet -->
        <div class="card">
            <h2>💰 Family Wallet</h2>
            
            <div class="family-balance">
                <div class="amount"><?= number_format($stats['family_balance']) ?></div>
                <div class="label">Sats verfügbar</div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="value"><?= number_format($stats['total_deposited']) ?></div>
                    <div class="label">Eingezahlt</div>
                </div>
                <div class="stat-item">
                    <div class="value"><?= number_format($stats['total_distributed']) ?></div>
                    <div class="label">Verteilt</div>
                </div>
                <div class="stat-item">
                    <div class="value"><?= $stats['active_children'] ?></div>
                    <div class="label">Kinder</div>
                </div>
            </div>
            
            <!-- Deposit Form -->
            <form method="post" style="margin-top: 20px;">
                <input type="hidden" name="action" value="deposit">
                <div class="form-group">
                    <label>Sats einzahlen</label>
                    <input type="number" name="amount" min="1" placeholder="z.B. 10000" required>
                </div>
                <button type="submit" class="btn btn-btc">⚡ Aufladen</button>
            </form>
        </div>
        
        <!-- Kinder-Wallets -->
        <div class="card">
            <h2>👨‍👩‍👧‍👦 Kinder-Wallets</h2>
            
            <?php if (empty($children)): ?>
            <div class="empty-state">
                <div class="icon">👶</div>
                <p>Noch keine Kinder angelegt.<br>Füge dein erstes Kind hinzu!</p>
            </div>
            <?php else: ?>
            <?php foreach ($children as $child): ?>
            <div class="child-card">
                <div class="child-avatar"><?= $child['avatar'] ?></div>
                <div class="child-info">
                    <div class="child-name"><?= htmlspecialchars($child['child_name']) ?></div>
                    <div class="child-streak">
                        <?= $child['age'] ?? '?' ?> Jahre | 🔥 <?= $child['current_streak'] ?> Tage Streak
                    </div>
                </div>
                <div class="child-card-actions">
                    <div class="child-balance">
                        ⚡ <?= number_format($child['balance_sats']) ?>
                    </div>
                    <button type="button" class="btn btn-delete" 
                            onclick="openDeleteModal(<?= $child['id'] ?>, '<?= htmlspecialchars($child['child_name'], ENT_QUOTES) ?>', '<?= $child['avatar'] ?>')">
                        🗑️ Löschen
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;">
                <a href="register.php" class="btn btn-green" style="text-decoration: none;">
                    ➕ Neues Kind registrieren
                </a>
            </div>
        </div>
        
    </div>
    
    <!-- NEU: Achievement-Übersicht -->
    <?php if (!empty($children)): ?>
    <div class="card achievement-card">
        <h2>🏆 Achievement-Übersicht</h2>
        
        <?php foreach ($children as $child): 
            $achStats = $childAchievements[$child['id']] ?? null;
            if (!$achStats) continue;
        ?>
        <div class="child-achievement-card">
            <div class="child-ach-header">
                <div class="child-ach-name">
                    <?= $child['avatar'] ?> <?= htmlspecialchars($child['child_name']) ?>
                </div>
                <div class="child-ach-stats">
                    <div class="child-ach-stat">
                        <span class="value"><?= $achStats['unlocked'] ?>/<?= $achStats['total'] ?></span>
                        <span>Achievements</span>
                    </div>
                    <div class="child-ach-stat">
                        <span class="value"><?= $achStats['percent'] ?>%</span>
                    </div>
                    <div class="child-ach-stat">
                        <span class="value">+<?= number_format($achStats['total_rewards']) ?></span>
                        <span>Sats verdient</span>
                    </div>
                </div>
            </div>
            
            <!-- Kategorie-Fortschritt -->
            <div class="category-progress">
                <?php foreach ($achStats['by_category'] as $cat => $catData): ?>
                <div class="category-row">
                    <span class="category-icon"><?= getCategoryIcon($cat) ?></span>
                    <div class="category-bar">
                        <div class="category-bar-fill" 
                             style="width: <?= $catData['percent'] ?>%; 
                                    background: <?= $catData['percent'] == 100 ? '#43D240' : '#f7931a' ?>;">
                        </div>
                    </div>
                    <span class="category-count"><?= $catData['unlocked'] ?>/<?= $catData['total'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Tier-Verteilung -->
            <div class="tier-distribution">
                <?php foreach ($achStats['by_tier'] as $tier => $tierData): 
                    $tierColor = getTierColor($tier);
                ?>
                <div style="display: flex; align-items: center; gap: 4px; font-size: 11px; color: <?= $tierColor ?>;">
                    <span style="font-size: 14px;">
                        <?php 
                        switch($tier) {
                            case 'bronze': echo '🥉'; break;
                            case 'silver': echo '🥈'; break;
                            case 'gold': echo '🥇'; break;
                            case 'master': echo '👑'; break;
                        }
                        ?>
                    </span>
                    <span><?= $tierData['unlocked'] ?>/<?= $tierData['total'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Letzte Achievements -->
            <?php if (!empty($achStats['recent'])): ?>
            <div class="recent-achievements">
                <?php foreach ($achStats['recent'] as $ach): 
                    $tier = $ach['definition']['tier'] ?? 'bronze';
                ?>
                <span class="achievement-badge <?= $tier ?>">
                    <?= $ach['achievement_icon'] ?> <?= htmlspecialchars($ach['achievement_name']) ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Test & Transactions -->
    <div class="grid">
        
        <!-- Test Reward -->
        <?php if (!empty($children)): ?>
        <div class="card">
            <h2>🧪 Test: Sats verdienen</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                Simuliere eine abgeschlossene Lern-Session:
            </p>
            
            <form method="post">
                <input type="hidden" name="action" value="test_earn">
                
                <div class="form-group">
                    <label>Kind wählen</label>
                    <select name="child_id" required>
                        <?php foreach ($children as $child): ?>
                        <option value="<?= $child['id'] ?>">
                            <?= $child['avatar'] ?> <?= htmlspecialchars($child['child_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Modul</label>
                    <select name="module">
                        <option value="mathematik">Mathematik</option>
                        <option value="lesen">Lesen</option>
                        <option value="englisch">Englisch</option>
                        <option value="bitcoin">Bitcoin</option>
                        <option value="erdkunde">Erdkunde</option>
                        <option value="programmieren">Programmieren</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Score (von 10)</label>
                    <input type="number" name="score" min="0" max="10" value="8">
                </div>
                
                <button type="submit" class="btn btn-btc">⚡ Reward testen</button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Transaktionen -->
        <div class="card">
            <h2>📜 Letzte Transaktionen</h2>
            
            <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <div class="icon">📋</div>
                <p>Noch keine Transaktionen.</p>
            </div>
            <?php else: ?>
            <div class="transaction-list">
                <?php foreach ($transactions as $tx): ?>
                <div class="transaction">
                    <div class="tx-icon <?= $tx['type'] ?>">
                        <?php 
                        switch($tx['type']) {
                            case 'earn': echo '⚡'; break;
                            case 'withdraw': echo '📤'; break;
                            case 'bonus': echo '🏆'; break;
                            case 'achievement': echo '🎖️'; break;
                            default: echo '💫';
                        }
                        ?>
                    </div>
                    <div class="tx-info">
                        <div class="tx-child"><?= $tx['avatar'] ?? '' ?> <?= htmlspecialchars($tx['child_name'] ?? 'Unbekannt') ?></div>
                        <div class="tx-reason"><?= htmlspecialchars($tx['reason'] ?? $tx['module'] ?? '-') ?></div>
                    </div>
                    <div>
                        <div class="tx-amount <?= $tx['type'] === 'withdraw' ? 'negative' : 'positive' ?>">
                            <?= $tx['type'] === 'withdraw' ? '-' : '+' ?><?= number_format($tx['amount_sats']) ?> Sats
                        </div>
                        <div class="tx-time"><?= date('d.m. H:i', strtotime($tx['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
    
    <?php endif; ?>
    
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal">
        <h3>⚠️ Kind löschen</h3>
        <p>
            <span id="deleteChildAvatar" style="font-size: 48px;"></span><br>
            <strong id="deleteChildName"></strong>
        </p>
        <div class="warning">
            ⚠️ <strong>Achtung:</strong> Diese Aktion kann NICHT rückgängig gemacht werden!<br>
            Alle Daten werden gelöscht: Transaktionen, Achievements, Statistiken.
            <br><br>
            💰 Vorhandene Sats werden zum Family Wallet zurücküberwiesen.
        </div>
        <form method="post" id="deleteForm">
            <input type="hidden" name="action" value="delete_child">
            <input type="hidden" name="child_id" id="deleteChildId">
            <div class="form-group">
                <label>Zur Bestätigung den Namen eingeben:</label>
                <input type="text" name="confirm_name" id="confirmNameInput" 
                       placeholder="Name des Kindes" required autocomplete="off">
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn btn-cancel" onclick="closeDeleteModal()">
                    Abbrechen
                </button>
                <button type="submit" class="btn btn-delete">
                    🗑️ Endgültig löschen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeleteModal(childId, childName, avatar) {
    document.getElementById('deleteChildId').value = childId;
    document.getElementById('deleteChildName').textContent = childName;
    document.getElementById('deleteChildAvatar').textContent = avatar;
    document.getElementById('confirmNameInput').value = '';
    document.getElementById('confirmNameInput').placeholder = childName;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

// Close modal on click outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});
</script>

</body>
</html>
