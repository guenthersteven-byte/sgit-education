<?php
/**
 * ============================================================================
 * sgiT Education - Bitcoin Einzahlung (Deposit)
 * ============================================================================
 * 
 * Ermöglicht Eltern, echte Sats ins Family Wallet einzuzahlen.
 * Unterstützt Lightning und On-Chain Zahlungen via BTCPay Server.
 * 
 * @author sgiT Solution Engineering
 * @version 1.0
 * @date 02.12.2025
 * ============================================================================
 */

session_start();

// Admin-Check (nur über Admin-Dashboard erreichbar)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_v4.php');
    exit;
}

require_once __DIR__ . '/BTCPayManager.php';
require_once __DIR__ . '/WalletManager.php';

$btcpay = null;
$btcpayEnabled = false;
$btcpayError = null;

try {
    $btcpay = new BTCPayManager();
    $btcpayEnabled = $btcpay->isEnabled();
} catch (Exception $e) {
    $btcpayError = $e->getMessage();
}

$walletManager = new WalletManager();
$familyWallet = $walletManager->getFamilyWallet();

// BTC Preis holen (Mempool API)
$btcPrice = 0;
try {
    $priceJson = @file_get_contents('https://mempool.space/api/v1/prices');
    if ($priceJson) {
        $priceData = json_decode($priceJson, true);
        $btcPrice = $priceData['EUR'] ?? 0;
    }
} catch (Exception $e) {
    // Ignorieren
}

// Invoice erstellen
$invoice = null;
$invoiceError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $amountSats = (int) ($_POST['amount_sats'] ?? 0);
    
    if ($amountSats < 1000) {
        $invoiceError = 'Minimum: 1.000 Sats';
    } elseif ($amountSats > 1000000) {
        $invoiceError = 'Maximum: 1.000.000 Sats';
    } elseif (!$btcpayEnabled) {
        $invoiceError = 'BTCPay Server nicht konfiguriert';
    } else {
        $result = $btcpay->createDepositInvoice($amountSats, 'Family Wallet Einzahlung');
        
        if ($result['success']) {
            $invoice = $result;
        } else {
            $invoiceError = $result['error'] ?? 'Fehler beim Erstellen der Invoice';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>₿ Einzahlung - sgiT Education</title>
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .back-btn {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-btn:hover {
            border-color: var(--accent);
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
            background: linear-gradient(135deg, var(--primary), #2a5a0a);
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .balance-label {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 5px;
        }
        
        .balance-value {
            font-size: 36px;
            font-weight: bold;
            color: white;
        }
        
        .balance-value .currency {
            font-size: 20px;
            color: var(--bitcoin);
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
            font-size: 18px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--bitcoin);
        }
        
        .quick-amounts {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .quick-amount {
            padding: 10px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            cursor: pointer;
            text-align: center;
            font-size: 14px;
        }
        
        .quick-amount:hover {
            border-color: var(--bitcoin);
            background: rgba(247, 147, 26, 0.1);
        }
        
        .eur-preview {
            text-align: right;
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 5px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--bitcoin), #e88a00);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(247, 147, 26, 0.3);
        }
        
        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .error-box {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff4444;
            color: #ff6666;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .warning-box {
            background: rgba(247, 147, 26, 0.1);
            border: 1px solid var(--bitcoin);
            color: var(--bitcoin);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .invoice-display {
            text-align: center;
            padding: 30px;
        }
        
        .invoice-amount {
            font-size: 28px;
            font-weight: bold;
            color: var(--bitcoin);
            margin-bottom: 20px;
        }
        
        .pay-btn {
            display: inline-block;
            padding: 18px 40px;
            background: linear-gradient(135deg, var(--lightning), #5a1db3);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .pay-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(121, 45, 228, 0.3);
        }
        
        .btcpay-disabled {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }
        
        .btcpay-disabled h3 {
            margin-bottom: 15px;
            color: var(--bitcoin);
        }
        
        .network-badge {
            display: inline-block;
            padding: 4px 12px;
            background: var(--lightning);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .network-badge.regtest {
            background: #00bcd4;
        }
        
        .network-badge.testnet {
            background: #ff9800;
        }
        
        .network-badge.mainnet {
            background: var(--bitcoin);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                ₿ Einzahlung
                <?php if ($btcpayEnabled): ?>
                    <span class="network-badge <?= $btcpay->getNetwork() ?>">
                        <?= strtoupper($btcpay->getNetwork()) ?>
                    </span>
                <?php endif; ?>
            </h1>
            <a href="wallet_admin.php" class="back-btn">← Zurück</a>
        </div>
        
        <!-- Aktuelle Balance -->
        <div class="balance-display">
            <div class="balance-label">Family Wallet Balance</div>
            <div class="balance-value">
                <?= number_format($familyWallet['balance_sats'] ?? 0) ?>
                <span class="currency">Sats</span>
            </div>
            <?php if ($btcPrice > 0): ?>
                <div class="balance-eur">
                    ≈ <?= number_format(($familyWallet['balance_sats'] ?? 0) / 100000000 * $btcPrice, 2) ?> EUR
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($btcpayError): ?>
            <div class="error-box">
                <strong>BTCPay Fehler:</strong> <?= htmlspecialchars($btcpayError) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$btcpayEnabled): ?>
            <div class="card">
                <div class="btcpay-disabled">
                    <h3>🔧 BTCPay Server nicht konfiguriert</h3>
                    <p>Um echte Sats einzuzahlen, muss BTCPay Server eingerichtet werden.</p>
                    <br>
                    <p>Siehe: <code>docs/btcpay_integration_concept.md</code></p>
                </div>
            </div>
        <?php elseif ($invoice): ?>
            <!-- Invoice erstellt -->
            <div class="card">
                <h2 class="card-title">⚡ Invoice erstellt!</h2>
                <div class="invoice-display">
                    <div class="invoice-amount">
                        <?= number_format($invoice['amount_sats']) ?> Sats
                    </div>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">
                        ≈ <?= number_format($invoice['amount_sats'] / 100000000 * $btcPrice, 2) ?> EUR
                    </p>
                    <a href="<?= htmlspecialchars($invoice['checkout_link']) ?>" 
                       target="_blank" 
                       class="pay-btn">
                        ⚡ Jetzt bezahlen
                    </a>
                    <p style="color: var(--text-muted); margin-top: 20px; font-size: 14px;">
                        Invoice ID: <?= htmlspecialchars($invoice['invoice_id']) ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <!-- Einzahlungsformular -->
            <div class="card">
                <h2 class="card-title">💰 Sats einzahlen</h2>
                
                <?php if ($invoiceError): ?>
                    <div class="error-box"><?= htmlspecialchars($invoiceError) ?></div>
                <?php endif; ?>
                
                <?php if ($btcpay->getNetwork() === 'regtest'): ?>
                    <div class="warning-box">
                        ⚠️ <strong>REGTEST MODUS</strong> - Dies sind keine echten Sats!
                        Perfekt zum Testen.
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="amount_sats">Betrag in Sats</label>
                        <input type="number" 
                               id="amount_sats" 
                               name="amount_sats" 
                               min="1000" 
                               max="1000000" 
                               step="100"
                               value="10000"
                               required>
                        <div class="quick-amounts">
                            <button type="button" class="quick-amount" onclick="setAmount(5000)">5.000</button>
                            <button type="button" class="quick-amount" onclick="setAmount(10000)">10.000</button>
                            <button type="button" class="quick-amount" onclick="setAmount(50000)">50.000</button>
                            <button type="button" class="quick-amount" onclick="setAmount(100000)">100.000</button>
                        </div>
                        <?php if ($btcPrice > 0): ?>
                            <div class="eur-preview" id="eurPreview">
                                ≈ <?= number_format(10000 / 100000000 * $btcPrice, 2) ?> EUR
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" name="create_invoice" class="submit-btn">
                        ⚡ Invoice erstellen
                    </button>
                </form>
            </div>
            
            <div class="card" style="font-size: 14px; color: var(--text-muted);">
                <p><strong>Zahlungsmethoden:</strong></p>
                <p>⚡ Lightning Network (sofort)</p>
                <p>🔗 On-Chain Bitcoin (~10-60 Min)</p>
                <br>
                <p><strong>Limits:</strong></p>
                <p>Minimum: 1.000 Sats | Maximum: 1.000.000 Sats</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        const btcPrice = <?= $btcPrice ?>;
        
        function setAmount(sats) {
            document.getElementById('amount_sats').value = sats;
            updatePreview();
        }
        
        function updatePreview() {
            if (btcPrice > 0) {
                const sats = parseInt(document.getElementById('amount_sats').value) || 0;
                const eur = (sats / 100000000 * btcPrice).toFixed(2);
                document.getElementById('eurPreview').textContent = '≈ ' + eur + ' EUR';
            }
        }
        
        document.getElementById('amount_sats')?.addEventListener('input', updatePreview);
    </script>
</body>
</html>
