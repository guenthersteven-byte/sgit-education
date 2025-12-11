<?php
/**
 * ============================================================================
 * sgiT Education Platform - Admin Dashboard
 * ============================================================================
 * 
 * VEREINFACHT - Nur Navigation, Bitcoin, Quick-Links
 * Statistiken → statistics.php
 * 
 * Nutzt zentrale Versionsverwaltung via /includes/version.php
 * 
 * @version Siehe SGIT_VERSION
 * @date Siehe SGIT_VERSION_DATE
 * ============================================================================
 */

error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();

// Zentrale Versionsverwaltung
require_once __DIR__ . '/includes/version.php';
require_once __DIR__ . '/includes/rate_limiter.php';

define('ADMIN_PASSWORD', 'sgit2025');

// Login/Logout
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin_v4.php'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    // Rate-Limiting: Max 5 Login-Versuche pro Minute
    $rateCheck = RateLimiter::check('admin_login', 5, 60);
    
    if (!$rateCheck['allowed']) {
        $login_error = "Zu viele Versuche! Bitte warte {$rateCheck['reset_in']} Sekunden.";
    } elseif ($_POST['admin_password'] === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        RateLimiter::reset('admin_login'); // Reset bei erfolgreichem Login
        header('Location: admin_v4.php');
        exit();
    } else {
        $login_error = "Falsches Passwort! (Noch {$rateCheck['remaining']} Versuche)";
    }
}

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    showLoginPage($login_error ?? null);
    exit();
}

// Bot Status für Quick-View
function getBotStatus() {
    $bots = [
        'ai_generator' => ['name' => 'AI Generator', 'icon' => '🤖', 'file' => 'AIGeneratorBot.php'],
        'function_test' => ['name' => 'Function Test', 'icon' => '🧪', 'file' => 'FunctionTestBot.php'],
        'security' => ['name' => 'Security', 'icon' => '🔒', 'file' => 'SecurityBot.php'],
        'load_test' => ['name' => 'Load Test', 'icon' => '⚡', 'file' => 'LoadTestBot.php']
    ];
    
    $logsDir = __DIR__ . '/bots/logs/';
    foreach ($bots as $key => &$bot) {
        $bot['running'] = false;
        $logFile = $logsDir . strtolower(str_replace(' ', '_', $bot['name'])) . '.log';
        if (file_exists($logFile) && (time() - filemtime($logFile) < 300)) {
            $bot['running'] = true;
        }
    }
    return $bots;
}

// System Status Quick Check - Ollama via HTTP (Docker-kompatibel!)
function checkOllamaStatus() {
    // Versuche zuerst Docker-Hostnamen, dann localhost
    $hosts = ['ollama', 'localhost', '127.0.0.1'];
    
    foreach ($hosts as $host) {
        $url = "http://{$host}:11434/api/tags";
        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response !== false) {
            return true;
        }
    }
    return false;
}

$ollama_online = checkOllamaStatus();

$bot_status = getBotStatus();

function showLoginPage($error = null) {
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; }
        .login-box { background: var(--card-bg); border: 1px solid var(--border); padding: 50px 40px; border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,0.4); max-width: 400px; width: 90%; text-align: center; }
        .logo { width: 80px; height: 80px; background: rgba(67, 210, 64, 0.2); border: 2px solid var(--border); border-radius: 20px; margin: 0 auto 25px; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; color: var(--accent); }
        h1 { color: var(--accent); margin-bottom: 25px; font-size: 1.5rem; }
        input { width: 100%; padding: 15px; border: 1px solid var(--border); background: rgba(0,0,0,0.3); color: #fff; border-radius: 12px; font-size: 16px; margin-bottom: 20px; }
        input:focus { outline: none; border-color: var(--accent); }
        input::placeholder { color: var(--text-muted); }
        button { width: 100%; padding: 15px; background: var(--accent); color: #000; border: none; border-radius: 12px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.2s; }
        button:hover { background: var(--accent-hover); }
        .error { background: rgba(220, 53, 69, 0.2); color: #ff6b6b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(220, 53, 69, 0.4); }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">sgiT</div>
        <h1>Admin Dashboard v<?= SGIT_VERSION ?></h1>
        <?php if ($error): ?><div class="error">⚠️ <?= $error ?></div><?php endif; ?>
        <form method="POST">
            <input type="password" name="admin_password" placeholder="🔐 Passwort" required autofocus>
            <button type="submit">Einloggen</button>
        </form>
    </div>
</body>
</html>
<?php exit(); }
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sgiT Admin v<?= SGIT_VERSION ?></title>
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <style>
        /* Admin-spezifische Overrides */
        .header { background: rgba(0, 0, 0, 0.4); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid var(--border); }
        .brand { display: flex; align-items: center; gap: 15px; }
        .logo { width: 50px; height: 50px; background: rgba(67, 210, 64, 0.2); border: 1px solid var(--border); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--accent); }
        .brand h1 { font-size: 1.4rem; color: #fff; }
        .brand h1 small { font-size: 0.7rem; opacity: 0.6; margin-left: 8px; }
        
        .header-nav { display: flex; gap: 10px; flex-wrap: wrap; }
        .header-nav a { padding: 10px 18px; border-radius: 10px; text-decoration: none; font-weight: 500; font-size: 0.9rem; transition: all 0.2s; }
        .nav-primary { background: var(--accent); color: #000; }
        .nav-secondary { background: rgba(255,255,255,0.1); color: white; }
        .nav-danger { background: #c0392b; color: white; }
        .header-nav a:hover { transform: translateY(-2px); }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 25px; }
        
        /* Bitcoin Ticker */
        .bitcoin-ticker { background: linear-gradient(135deg, var(--bitcoin), #E88A00); border-radius: 16px; padding: 20px 30px; color: white; margin-bottom: 25px; display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; border: 1px solid rgba(247, 147, 26, 0.5); }
        .btc-stat { text-align: center; min-width: 100px; }
        .btc-stat .label { font-size: 0.75rem; opacity: 0.9; text-transform: uppercase; margin-bottom: 5px; }
        .btc-stat .value { font-size: 1.3rem; font-weight: 700; }
        
        /* Quick Actions Grid */
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px; }
        
        .action-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 25px; transition: all 0.2s; }
        .action-card:hover { transform: translateY(-3px); border-color: var(--accent); box-shadow: 0 5px 25px rgba(67, 210, 64, 0.1); }
        .action-card h3 { font-size: 1.1rem; color: var(--accent); margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
        .action-card p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 15px; }
        .action-card a { display: inline-block; padding: 10px 20px; background: var(--accent); color: #000; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; }
        .action-card a:hover { background: #35B035; }
        
        .action-card.orange h3 { color: var(--orange); }
        .action-card.orange a { background: var(--orange); color: #fff; }
        .action-card.orange a:hover { background: #d45a1a; }
        
        /* Bot Section */
        .bot-section { background: rgba(0, 0, 0, 0.4); border: 1px solid var(--border); border-radius: 16px; padding: 25px; color: white; margin-bottom: 25px; }
        .bot-section h2 { font-size: 1.1rem; margin-bottom: 20px; color: var(--accent); }
        .bot-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        .bot-card { background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 10px; padding: 15px; text-align: center; }
        .bot-card.running { border: 2px solid var(--accent); }
        .bot-icon { font-size: 1.5rem; margin-bottom: 8px; }
        .bot-name { font-weight: 600; font-size: 0.9rem; margin-bottom: 5px; }
        .bot-status { font-size: 0.75rem; color: var(--text-muted); }
        .bot-status.on { color: var(--accent); }
        .bot-card a { display: inline-block; margin-top: 10px; padding: 6px 14px; background: var(--accent); color: #000; text-decoration: none; border-radius: 6px; font-size: 0.8rem; }
        
        /* System Status */
        .status-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px; }
        .status-item { background: var(--card-bg); border: 1px solid var(--border); border-radius: 10px; padding: 12px 20px; display: flex; align-items: center; gap: 10px; color: var(--text); }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; }
        .status-dot.online { background: var(--accent); box-shadow: 0 0 10px var(--accent); }
        .status-dot.offline { background: #e74c3c; }
        
        footer { text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--border); margin-top: 20px; }
        footer a { color: var(--accent); text-decoration: none; }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">
            <div class="logo">sgiT</div>
            <h1>Admin Dashboard <small>v<?= SGIT_VERSION ?></small></h1>
        </div>
        <nav class="header-nav">
            <a href="adaptive_learning.php" class="nav-secondary">📚 Lernen</a>
            <a href="statistics.php" class="nav-primary">📊 Statistik</a>
            <a href="?logout=1" class="nav-danger">🔒 Logout</a>
        </nav>
    </header>
    
    <div class="container">
        <!-- Bitcoin Ticker OBEN -->
        <div class="bitcoin-ticker">
            <div class="btc-stat"><div class="label">USD</div><div class="value" id="btcUSD">...</div></div>
            <div class="btc-stat"><div class="label">EUR</div><div class="value" id="btcEUR">...</div></div>
            <div class="btc-stat"><div class="label">Block</div><div class="value" id="btcBlock">...</div></div>
            <div class="btc-stat"><div class="label">Sats/$</div><div class="value" id="btcSats">...</div></div>
            <div class="btc-stat"><div class="label">Halving</div><div class="value" id="btcHalving">...</div></div>
            <div class="btc-stat"><div class="label">Fees</div><div class="value" id="btcFees">...</div></div>
        </div>
        
        <!-- System Status -->
        <div class="status-row">
            <div class="status-item">
                <span class="status-dot online"></span>
                <span>SQLite Online</span>
            </div>
            <div class="status-item">
                <span class="status-dot <?= $ollama_online ? 'online' : 'offline' ?>"></span>
                <span>Ollama <?= $ollama_online ? 'Online' : 'Offline' ?></span>
            </div>
            <div class="status-item">
                <span>PHP <?= PHP_VERSION ?></span>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="actions-grid">
            <div class="action-card">
                <h3>📊 Statistik Dashboard</h3>
                <p>Alle Statistiken auf einen Blick: Fragen, Module, Wallet, Foxy und mehr.</p>
                <a href="statistics.php">Öffnen →</a>
            </div>
            
            <div class="action-card" style="border-left: 4px solid #FFD700;">
                <h3>🏆 Leaderboard</h3>
                <p>Highscores, Top-Lerner, Streaks und Modul-Champions für die Kids.</p>
                <a href="leaderboard.php">Rangliste →</a>
            </div>
            
            <div class="action-card orange">
                <h3>🦊 Foxy Lernassistent</h3>
                <p>Konfiguration, Animationen und DB-Seeder für den Lernfuchs.</p>
                <a href="clippy/test.php">Konfigurieren →</a>
            </div>
            
            <div class="action-card">
                <h3>📥 CSV Import</h3>
                <p>Fragen aus CSV-Dateien importieren (Batch-Import für alle Module).</p>
                <a href="batch_import.php">Importieren →</a>
            </div>
            
            <div class="action-card">
                <h3>🤖 AI Generator Bot</h3>
                <p>KI-gesteuerte Fragengenerierung mit Ollama (Gemma2:2b).</p>
                <a href="bots/tests/AIGeneratorBot.php">Generator →</a>
            </div>
            
            <div class="action-card">
                <h3>₿ Wallet Admin</h3>
                <p>Kinder-Wallets, Transaktionen und Achievements verwalten.</p>
                <a href="wallet/wallet_admin.php">Verwalten →</a>
            </div>
            
            <div class="action-card">
                <h3>💾 Backup Manager</h3>
                <p>Datenbanken sichern und wiederherstellen (lokal + OneDrive).</p>
                <a href="backup_manager.php">Backup →</a>
            </div>
            
            <div class="action-card">
                <h3>🔍 User Debug Center</h3>
                <p>User-Management, DB-Analyse und Fehlerdiagnose.</p>
                <a href="debug_users.php">Debug →</a>
            </div>
            
            <div class="action-card">
                <h3>📈 Bot Dashboard</h3>
                <p>Übersicht aller Bot-Durchläufe und Test-Ergebnisse.</p>
                <a href="bots/bot_summary.php">Dashboard →</a>
            </div>
            
            <div class="action-card" style="border-left: 4px solid #3498db;">
                <h3>🔧 SQLite WAL Mode Check</h3>
                <p>SQLite Performance unter Last prüfen und WAL-Modus aktivieren.</p>
                <a href="fix_bug026_wal_mode.php">Prüfen →</a>
            </div>
            
            <div class="action-card" style="border-left: 4px solid #e74c3c;">
                <h3>🚩 Cleanup: Gemeldete Fragen</h3>
                <p>Überprüfe und verwalte von Lernenden gemeldete fehlerhafte Fragen.</p>
                <a href="admin_cleanup_flags.php">Öffnen →</a>
            </div>
        </div>
        
        <!-- Bot Zentrale -->
        <div class="bot-section">
            <h2>🤖 Bot-Zentrale</h2>
            <div class="bot-grid">
                <?php foreach ($bot_status as $key => $bot): ?>
                <div class="bot-card <?= $bot['running'] ? 'running' : '' ?>">
                    <div class="bot-icon"><?= $bot['icon'] ?></div>
                    <div class="bot-name"><?= $bot['name'] ?></div>
                    <div class="bot-status <?= $bot['running'] ? 'on' : '' ?>"><?= $bot['running'] ? '● Running' : '○ Idle' ?></div>
                    <a href="bots/tests/<?= $bot['file'] ?>" target="_blank">▶️ Start</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <footer>
        sgiT Education Platform v<?= SGIT_VERSION ?> | <a href="https://sgit.space">sgiT Solution Engineering</a>
    </footer>
    
    <script>
        async function fetchBTC() {
            try {
                const [priceRes, blockRes, feesRes] = await Promise.all([
                    fetch('https://mempool.space/api/v1/prices'),
                    fetch('https://mempool.space/api/blocks/tip/height'),
                    fetch('https://mempool.space/api/v1/fees/recommended')
                ]);
                const price = await priceRes.json();
                const block = await blockRes.text();
                const fees = await feesRes.json();
                
                document.getElementById('btcUSD').textContent = '$' + price.USD.toLocaleString();
                document.getElementById('btcEUR').textContent = '€' + price.EUR.toLocaleString();
                document.getElementById('btcBlock').textContent = parseInt(block).toLocaleString();
                document.getElementById('btcSats').textContent = Math.round(100000000 / price.USD).toLocaleString();
                document.getElementById('btcHalving').textContent = (Math.ceil(parseInt(block) / 210000) * 210000 - parseInt(block)).toLocaleString();
                document.getElementById('btcFees').textContent = fees.halfHourFee + ' sat/vB';
            } catch (e) {
                document.getElementById('btcUSD').textContent = 'Error';
            }
        }
        fetchBTC();
        setInterval(fetchBTC, 30000);
    </script>
</body>
</html>
