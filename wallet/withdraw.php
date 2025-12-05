<?php
/**
 * ============================================================================
 * sgiT Education - Bitcoin Auszahlung (Withdraw)
 * ============================================================================
 * 
 * Ermöglicht Kindern, verdiente Sats auf ihre Lightning Wallet auszuzahlen.
 * Erfordert Eltern-Genehmigung.
 * 
 * @author sgiT Solution Engineering
 * @version 1.0
 * @date 02.12.2025
 * ============================================================================
 */

session_start();

require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/WalletManager.php';
require_once __DIR__ . '/BTCPayManager.php';

$sessionManager = new SessionManager();

// Kind muss eingeloggt sein
if (!$sessionManager->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$childId = $sessionManager->getChildId();
$walletManager = new WalletManager();
$child = $walletManager->getChildWallet($childId);

if (!$child) {
    header('Location: login.php');
    exit;
}

$btcpay = null;
$btcpayEnabled = false;

try {
    $btcpay = new BTCPayManager();
    $btcpayEnabled = $btcpay->isEnabled();
} catch (Exception $e) {
    // BTCPay nicht verfügbar
}

// BTC Preis
$btcPrice = 0;
try {
    $priceJson = @file_get_contents('https://mempool.space/api/v1/prices');
    if ($priceJson) {
        $priceData = json_decode($priceJson, true);
        $btcPrice = $priceData['EUR'] ?? 0;
    }
} catch (Exception $e) {}

// Auszahlungsanfrage
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdraw'])) {
    $amount = (int) ($_POST['amount_sats'] ?? 0);
    $lightningAddress = trim($_POST['lightning_address'] ?? '');
    
    // Validierung
    if ($amount < 100) {
        $error = 'Minimum: 100 Sats';
    } elseif ($amount > $child['balance_sats']) {
        $error = 'Nicht genügend Sats!';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $lightningAddress)) {
        $error = 'Ungültige Lightning Address!';
    } else {
        // Auszahlungsanfrage in DB speichern
        try {
            $db = new SQLite3(__DIR__ . '/wallet.db');
            
            // Tabelle erstellen falls nicht vorhanden
            $db->exec("
                CREATE TABLE IF NOT EXISTS withdrawal_requests (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    child_id INTEGER NOT NULL,
                    amount_sats INTEGER NOT NULL,
                    lightning_address TEXT NOT NULL,
                    status TEXT DEFAULT 'pending',
                    approved_by TEXT,
                    approved_at DATETIME,
                    paid_at DATETIME,
                    payment_hash TEXT,
                    error_message TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (child_id) REFERENCES child_wallets(id)
                )
            ");
            
            $stmt = $db->prepare("
                INSERT INTO withdrawal_requests (child_id, amount_sats, lightning_address, status)
                VALUES (:child_id, :amount, :address, 'pending')
            ");
            $stmt->bindValue(':child_id', $childId, SQLITE3_INTEGER);
            $stmt->bindValue(':amount', $amount, SQLITE3_INTEGER);
            $stmt->bindValue(':address', $lightningAddress);
            $stmt->execute();
            
            $db->close();
            
            $message = "Auszahlungsanfrage über $amount Sats wurde eingereicht! Deine Eltern müssen diese noch genehmigen.";
            
        } catch (Exception $e) {
            $error = 'Fehler: ' . $e->getMessage();
        }
    }
}

// Bestehende Anfragen laden
$pendingRequests = [];
try {
    $db = new SQLite3(__DIR__ . '/wallet.db');
    $stmt = $db->prepare("
        SELECT * FROM withdrawal_requests 
        WHERE child_id = :id 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $pendingRequests[] = $row;
    }
    $db->close();
} catch (Exception $e) {
    // Tabelle existiert vielleicht noch nicht
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>₿ Auszahlung - sgiT Education</title>
    <style>
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bitcoin: #F7931A;
            --lightning: #792DE4;
            --bg: #0d1117;
            --card: #161b22;
            --border: #30363d;
            --text: #e6edf3;
            --text-muted: #8b949e;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .back-btn {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--accent);
        }
        
        .balance-display {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, var(--lightning), #5a1db3);
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .balance-label {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
        }
        
        .balance-value {
            font-size: 36px;
            font-weight: bold;
            color: white;
        }
        
        .balance-eur {
            font-size: 14px;
            color: rgba(255,255,255,0.6);
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--lightning);
        }
        
        .form-group small {
            color: var(--text-muted);
            font-size: 12px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--lightning), #5a1db3);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(121, 45, 228, 0.3);
        }
        
        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .message-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message-box.success {
            background: rgba(67, 210, 64, 0.1);
            border: 1px solid var(--accent);
            color: var(--accent);
        }
        
        .message-box.error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff4444;
            color: #ff6666;
        }
        
        .info-box {
            background: rgba(121, 45, 228, 0.1);
            border: 1px solid var(--lightning);
            color: var(--lightning);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .request-list {
            list-style: none;
        }
        
        .request-item {
            padding: 15px;
            background: var(--bg);
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .request-amount {
            font-weight: bold;
            color: var(--bitcoin);
        }
        
        .request-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-approved {
            background: rgba(67, 210, 64, 0.2);
            color: var(--accent);
        }
        
        .status-rejected {
            background: rgba(255, 68, 68, 0.2);
            color: #ff6666;
        }
        
        .status-paid {
            background: rgba(121, 45, 228, 0.2);
            color: var(--lightning);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📤 Auszahlung</h1>
            <a href="child_dashboard.php" class="back-btn">← Zurück</a>
        </div>
        
        <!-- Balance -->
        <div class="balance-display">
            <div class="balance-label">Dein Guthaben</div>
            <div class="balance-value">
                <?= number_format($child['balance_sats']) ?> Sats
            </div>
            <?php if ($btcPrice > 0): ?>
                <div class="balance-eur">
                    ≈ <?= number_format($child['balance_sats'] / 100000000 * $btcPrice, 2) ?> EUR
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($message): ?>
            <div class="message-box success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message-box error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Info -->
        <div class="info-box">
            ℹ️ <strong>So funktioniert's:</strong><br>
            1. Gib den Betrag und deine Lightning Address ein<br>
            2. Deine Eltern genehmigen die Auszahlung<br>
            3. Die Sats werden an deine Wallet gesendet!
        </div>
        
        <!-- Formular -->
        <div class="card">
            <h2 class="card-title">⚡ Auszahlung beantragen</h2>
            
            <?php if (!$btcpayEnabled): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">
                    🔧 Echte Auszahlungen sind noch nicht aktiviert.<br>
                    (BTCPay Server wird eingerichtet)
                </p>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="amount_sats">Betrag (Sats)</label>
                        <input type="number" 
                               id="amount_sats" 
                               name="amount_sats" 
                               min="100" 
                               max="<?= $child['balance_sats'] ?>"
                               value="<?= min(1000, $child['balance_sats']) ?>"
                               required>
                        <small>Minimum: 100 Sats | Maximum: <?= number_format($child['balance_sats']) ?> Sats</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="lightning_address">Lightning Address</label>
                        <input type="text" 
                               id="lightning_address" 
                               name="lightning_address" 
                               placeholder="dein-name@walletofsatoshi.com"
                               value="<?= htmlspecialchars($child['lightning_address'] ?? '') ?>"
                               required>
                        <small>z.B. von Wallet of Satoshi, Alby, oder anderen Lightning Wallets</small>
                    </div>
                    
                    <button type="submit" 
                            name="request_withdraw" 
                            class="submit-btn"
                            <?= $child['balance_sats'] < 100 ? 'disabled' : '' ?>>
                        📤 Auszahlung beantragen
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Bestehende Anfragen -->
        <?php if (!empty($pendingRequests)): ?>
            <div class="card">
                <h2 class="card-title">📋 Deine Anfragen</h2>
                <ul class="request-list">
                    <?php foreach ($pendingRequests as $req): ?>
                        <li class="request-item">
                            <div>
                                <span class="request-amount"><?= number_format($req['amount_sats']) ?> Sats</span>
                                <br>
                                <small style="color: var(--text-muted);">
                                    <?= date('d.m.Y H:i', strtotime($req['created_at'])) ?>
                                </small>
                            </div>
                            <span class="request-status status-<?= $req['status'] ?>">
                                <?php
                                    switch ($req['status']) {
                                        case 'pending': echo '⏳ Wartet'; break;
                                        case 'approved': echo '✅ Genehmigt'; break;
                                        case 'rejected': echo '❌ Abgelehnt'; break;
                                        case 'paid': echo '⚡ Ausgezahlt'; break;
                                        default: echo $req['status'];
                                    }
                                ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
