<?php
/**
 * ============================================================================
 * sgiT Education - Wallet Dashboard
 * ============================================================================
 * 
 * Übersicht für Family Wallet und Kind-Wallets
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 02.12.2025
 * ============================================================================
 */

require_once __DIR__ . '/WalletManager.php';

// ============================================================================
// WALLET MANAGER INITIALISIEREN
// ============================================================================
$error = null;
$wallet = null;
$stats = [];
$children = [];
$transactions = [];

try {
    $wallet = new WalletManager();
    $stats = $wallet->getStats();
    $children = $wallet->getChildWallets();
    $transactions = $wallet->getAllTransactions(20);
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
                    $stats = $wallet->getStats(); // Refresh
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
                } else {
                    $message = "❌ " . ($result['error'] ?? 'Unbekannter Fehler');
                    $messageType = 'error';
                }
            }
            break;
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
    <title>₿ Wallet Dashboard - sgiT Education</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, #1A3503 0%, #2d5a06 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
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
        
        .nav-links a {
            display: inline-block;
            padding: 10px 20px;
            background: #43D240;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-left: 10px;
        }
        
        .nav-links a:hover {
            background: #3ab837;
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
            max-height: 400px;
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
        
        /* Avatars */
        .avatar-picker {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .avatar-picker label {
            cursor: pointer;
        }
        
        .avatar-picker input {
            display: none;
        }
        
        .avatar-picker span {
            display: block;
            width: 50px;
            height: 50px;
            font-size: 30px;
            text-align: center;
            line-height: 50px;
            border: 3px solid #ddd;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .avatar-picker input:checked + span {
            border-color: #43D240;
            background: #d4edda;
        }
        
        .avatar-picker span:hover {
            border-color: #43D240;
        }
    </style>
</head>
<body>
<div class="container">
    
    <!-- Header -->
    <div class="header">
        <h1><span class="btc">₿</span> Wallet Dashboard</h1>
        <div class="nav-links">
            <a href="login.php">🔑 Login</a>
            <a href="register.php">📝 Registrieren</a>
            <a href="../admin_v4.php">📊 Admin</a>
            <a href="../index.php">🏠 Start</a>
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
        
        <!-- Kinder -->
        <div class="card">
            <h2>👨‍👩‍👧‍👦 Kinder-Wallets</h2>
            
            <?php if (empty($children)): ?>
            <p style="color: #666; text-align: center; padding: 20px;">
                Noch keine Kinder angelegt.<br>
                Füge dein erstes Kind hinzu! 👇
            </p>
            <?php else: ?>
            <?php foreach ($children as $child): ?>
            <div class="child-card">
                <div class="child-avatar"><?= $child['avatar'] ?></div>
                <div class="child-info">
                    <div class="child-name"><?= htmlspecialchars($child['child_name']) ?></div>
                    <div class="child-streak">
                        <?= $child['age'] ?? '?' ?> Jahre | 🔥 <?= $child['current_streak'] ?> Tage
                    </div>
                </div>
                <div class="child-balance">
                    ⚡ <?= number_format($child['balance_sats']) ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Link zur Registrierung -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;">
                <a href="register.php" class="btn btn-green" style="text-decoration: none; display: inline-block;">
                    ➕ Neues Kind registrieren
                </a>
            </div>
        </div>
        
    </div>
    
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
                        <option value="mathe">Mathematik</option>
                        <option value="lesen">Lesen</option>
                        <option value="bitcoin">Bitcoin</option>
                        <option value="geographie">Geographie</option>
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
            <p style="color: #666; text-align: center; padding: 20px;">
                Noch keine Transaktionen.
            </p>
            <?php else: ?>
            <div class="transaction-list">
                <?php foreach ($transactions as $tx): ?>
                <div class="transaction">
                    <div class="tx-icon <?= $tx['type'] ?>">
                        <?php 
                        switch($tx['type']) {
                            case 'earn': echo '⚡'; break;
                            case 'withdraw': echo '📤'; break;
                            case 'bonus': echo '🎁'; break;
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
</body>
</html>
