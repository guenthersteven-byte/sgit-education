<?php
/**
 * ============================================================================
 * sgiT Education - User Login
 * ============================================================================
 * 
 * Login-Seite für Kinder/Lernende.
 * Zeigt alle registrierten User zur Auswahl + PIN-Eingabe.
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.1 - Fix: Redirect zu adaptive_learning.php
 * @date 02.12.2025
 * ============================================================================
 */

require_once __DIR__ . '/WalletManager.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

// Bereits eingeloggt? Redirect zur Lernplattform
if (SessionManager::isLoggedIn()) {
    $redirect = $_GET['redirect'] ?? '../adaptive_learning.php';
    // Open-Redirect-Schutz: Nur relative Pfade erlauben
    if (preg_match('#^https?://#i', $redirect) || str_contains($redirect, '//')) {
        $redirect = '../adaptive_learning.php';
    }
    header('Location: ' . $redirect);
    exit;
}

// ============================================================================
// INITIALISIERUNG
// ============================================================================
$error = null;
$children = [];
$selectedChild = null;

try {
    $wallet = new WalletManager();
    $children = $wallet->getChildWallets();
} catch (Exception $e) {
    error_log("Login Wallet Error: " . $e->getMessage());
    $error = 'Systemfehler. Bitte versuche es erneut.';
}

// ============================================================================
// LOGIN VERARBEITEN
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    // Rate-Limiting: Max 5 Login-Versuche pro 5 Minuten
    $rateCheck = RateLimiter::check('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 300);
    if (!$rateCheck['allowed']) {
        $error = 'Zu viele Login-Versuche. Bitte warte ' . $rateCheck['reset_in'] . ' Sekunden.';
    }

    $childId = (int) ($_POST['child_id'] ?? 0);
    $pin = $_POST['pin'] ?? '';

    if (!$error && $childId <= 0) {
        $error = 'Bitte waehle deinen Namen aus!';
    } elseif (!$error && strlen($pin) !== 4) {
        $error = 'Bitte gib deine 4-stellige PIN ein!';
        $selectedChild = $childId;
    } elseif (!$error) {
        // PIN verifizieren
        if ($wallet->verifyPin($childId, $pin)) {
            $child = $wallet->getChildWallet($childId);
            
            if ($child) {
                SessionManager::login($child);
                
                // Redirect zur Lernplattform (Open-Redirect-Schutz)
                $redirect = $_GET['redirect'] ?? '../adaptive_learning.php';
                if (preg_match('#^https?://#i', $redirect) || str_contains($redirect, '//')) {
                    $redirect = '../adaptive_learning.php';
                }
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'User nicht gefunden.';
            }
        } else {
            $error = '❌ Falsche PIN! Versuche es nochmal.';
            $selectedChild = $childId;
        }
    }
}

// Logout Action
if (isset($_GET['logout'])) {
    SessionManager::logout();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔑 Login - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, #1A3503 0%, #2d5a06 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 25px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header .logo {
            font-size: 60px;
            margin-bottom: 10px;
        }
        
        .header h1 {
            color: #1A3503;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        
        /* User Selection */
        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .user-card {
            position: relative;
            cursor: pointer;
        }
        
        .user-card input {
            position: absolute;
            opacity: 0;
        }
        
        .user-card .card-inner {
            background: #f8f9fa;
            border: 3px solid transparent;
            border-radius: 15px;
            padding: 20px 15px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .user-card input:checked + .card-inner {
            border-color: #43D240;
            background: #d4edda;
            transform: scale(1.05);
        }
        
        .user-card .card-inner:hover {
            border-color: #43D240;
            transform: scale(1.02);
        }
        
        .user-card .avatar {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .user-card .name {
            font-weight: 600;
            color: #1A3503;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .user-card .sats {
            font-size: 12px;
            color: #f7931a;
            font-weight: 600;
        }
        
        .user-card .streak {
            font-size: 11px;
            color: #666;
        }
        
        /* PIN Section */
        .pin-section {
            display: none;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
        }
        
        .pin-section.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pin-section h3 {
            text-align: center;
            color: #1A3503;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .pin-container {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .pin-container input {
            width: 55px;
            height: 65px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 12px;
            transition: all 0.2s;
        }
        
        .pin-container input:focus {
            border-color: #43D240;
            outline: none;
            box-shadow: 0 0 0 4px rgba(67, 210, 64, 0.1);
        }
        
        /* Buttons */
        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #43D240, #3ab837);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(67, 210, 64, 0.4);
        }
        
        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #1A3503;
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            color: #999;
            font-size: 13px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state .icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .empty-state h2 {
            color: #1A3503;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Quick Links */
        .quick-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .quick-links a {
            color: #1A3503;
            text-decoration: none;
            font-size: 13px;
        }
        
        .quick-links a:hover {
            color: #43D240;
        }
    </style>
</head>
<body>
<div class="container">
    
    <div class="header">
        <div class="logo">👋</div>
        <h1>Wer lernt heute?</h1>
        <p>Wähle deinen Namen und gib deine PIN ein</p>
    </div>
    
    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if (empty($children)): ?>
    <!-- Keine User vorhanden -->
    <div class="empty-state">
        <div class="icon">🤔</div>
        <h2>Noch keine Accounts</h2>
        <p>Es gibt noch keine registrierten Lernenden.<br>Erstelle jetzt deinen ersten Account!</p>
        <a href="register.php" class="btn btn-primary">
            ✨ Jetzt registrieren
        </a>
    </div>
    
    <?php else: ?>
    <!-- User Auswahl -->
    <form method="post" id="loginForm">
        
        <div class="user-grid">
            <?php foreach ($children as $child): ?>
            <label class="user-card">
                <input type="radio" name="child_id" value="<?= $child['id'] ?>" 
                       <?= $selectedChild == $child['id'] ? 'checked' : '' ?>
                       required>
                <div class="card-inner">
                    <div class="avatar"><?= $child['avatar'] ?></div>
                    <div class="name"><?= htmlspecialchars($child['child_name']) ?></div>
                    <div class="sats">⚡ <?= number_format($child['balance_sats']) ?> Sats</div>
                    <?php if ($child['current_streak'] > 0): ?>
                    <div class="streak">🔥 <?= $child['current_streak'] ?> Tage</div>
                    <?php endif; ?>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
        
        <!-- PIN Eingabe -->
        <div class="pin-section" id="pinSection">
            <h3>🔐 Gib deine PIN ein</h3>
            <div class="pin-container">
                <input type="password" maxlength="1" class="pin-digit" data-index="0" inputmode="numeric" pattern="[0-9]">
                <input type="password" maxlength="1" class="pin-digit" data-index="1" inputmode="numeric" pattern="[0-9]">
                <input type="password" maxlength="1" class="pin-digit" data-index="2" inputmode="numeric" pattern="[0-9]">
                <input type="password" maxlength="1" class="pin-digit" data-index="3" inputmode="numeric" pattern="[0-9]">
            </div>
            <input type="hidden" name="pin" id="pinFull">
        </div>
        
        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
            🚀 Los geht's!
        </button>
        
    </form>
    
    <div class="divider">Neu hier?</div>
    
    <a href="register.php" class="btn btn-secondary">
        📝 Neuen Account erstellen
    </a>
    
    <?php endif; ?>
    
    <div class="quick-links">
        <a href="../admin_v4.php">🔧 Admin Center</a>
    </div>
    
</div>

<script>
// User Selection
const userCards = document.querySelectorAll('input[name="child_id"]');
const pinSection = document.getElementById('pinSection');
const submitBtn = document.getElementById('submitBtn');
const pinInputs = document.querySelectorAll('.pin-digit');
const pinFull = document.getElementById('pinFull');

// Show PIN section when user selected
userCards.forEach(input => {
    input.addEventListener('change', function() {
        pinSection.classList.add('show');
        // Focus first PIN input
        setTimeout(() => pinInputs[0].focus(), 100);
        updateSubmitButton();
    });
});

// PIN Input Handling
pinInputs.forEach((input, index) => {
    input.addEventListener('input', function(e) {
        // Nur Zahlen
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Auto-Focus
        if (this.value && index < pinInputs.length - 1) {
            pinInputs[index + 1].focus();
        }
        
        updatePinField();
        updateSubmitButton();
    });
    
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && index > 0) {
            pinInputs[index - 1].focus();
        }
        
        // Enter zum Absenden
        if (e.key === 'Enter' && pinFull.value.length === 4) {
            document.getElementById('loginForm').submit();
        }
    });
    
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const digits = paste.replace(/[^0-9]/g, '').slice(0, 4);
        
        digits.split('').forEach((digit, i) => {
            if (pinInputs[i]) {
                pinInputs[i].value = digit;
            }
        });
        
        updatePinField();
        updateSubmitButton();
        
        if (digits.length === 4) {
            pinInputs[3].focus();
        }
    });
});

function updatePinField() {
    let pin = '';
    pinInputs.forEach(input => pin += input.value);
    pinFull.value = pin;
}

function updateSubmitButton() {
    const userSelected = document.querySelector('input[name="child_id"]:checked');
    const pinComplete = pinFull.value.length === 4;
    submitBtn.disabled = !(userSelected && pinComplete);
}

// If user was pre-selected (after error), show PIN section
<?php if ($selectedChild): ?>
pinSection.classList.add('show');
pinInputs[0].focus();
<?php endif; ?>
</script>

</body>
</html>
