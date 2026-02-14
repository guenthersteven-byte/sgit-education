<?php
/**
 * ============================================================================
 * sgiT Education Platform - Admin Dashboard
 * ============================================================================
 *
 * Navigation, Bitcoin Ticker, Quick-Links, Claude Fragen-Generator
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
    $rateCheck = RateLimiter::check('admin_login', 5, 60);

    if (!$rateCheck['allowed']) {
        $login_error = "Zu viele Versuche! Bitte warte {$rateCheck['reset_in']} Sekunden.";
    } elseif ($_POST['admin_password'] === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        RateLimiter::reset('admin_login');
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

// ============================================================================
// CLAUDE API ENDPOINTS (AJAX)
// ============================================================================
if (isset($_GET['action']) && in_array($_GET['action'], ['generate', 'save', 'claude_status'])) {
    header('Content-Type: application/json');

    // Claude Config laden
    $claudeConfigPath = __DIR__ . '/includes/claude_config.php';
    $claudeAvailable = false;
    if (file_exists($claudeConfigPath)) {
        require_once $claudeConfigPath;
        $claudeAvailable = defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY !== '';
    }

    require_once __DIR__ . '/includes/ClaudeClient.php';

    if ($_GET['action'] === 'claude_status') {
        if (!$claudeAvailable) {
            echo json_encode(['online' => false, 'error' => 'API Key nicht konfiguriert']);
        } else {
            $client = new ClaudeClient(ANTHROPIC_API_KEY, defined('CLAUDE_MODEL') ? CLAUDE_MODEL : 'claude-sonnet-4-5-20250929');
            echo json_encode($client->testConnection());
        }
        exit;
    }

    if ($_GET['action'] === 'generate') {
        if (!$claudeAvailable) {
            echo json_encode(['success' => false, 'error' => 'API Key nicht konfiguriert. Bitte includes/claude_config.php anlegen.']);
            exit;
        }

        $module = $_POST['module'] ?? '';
        $count = max(1, min(20, intval($_POST['count'] ?? 5)));
        $ageMin = max(5, intval($_POST['age_min'] ?? 8));
        $ageMax = max($ageMin, intval($_POST['age_max'] ?? 12));
        $difficulty = max(1, min(5, intval($_POST['difficulty'] ?? 3)));

        $client = new ClaudeClient(ANTHROPIC_API_KEY, defined('CLAUDE_MODEL') ? CLAUDE_MODEL : 'claude-sonnet-4-5-20250929');
        $result = $client->generate($module, $count, $ageMin, $ageMax, $difficulty);
        echo json_encode($result);
        exit;
    }

    if ($_GET['action'] === 'save') {
        if (!$claudeAvailable) {
            echo json_encode(['success' => false, 'error' => 'API Key nicht konfiguriert']);
            exit;
        }

        $questions = json_decode($_POST['questions'] ?? '[]', true);
        $module = $_POST['module'] ?? '';
        $difficulty = max(1, min(5, intval($_POST['difficulty'] ?? 3)));
        $ageMin = max(5, intval($_POST['age_min'] ?? 8));
        $ageMax = max($ageMin, intval($_POST['age_max'] ?? 12));

        if (empty($questions) || empty($module)) {
            echo json_encode(['success' => false, 'error' => 'Keine Fragen oder Modul angegeben']);
            exit;
        }

        $dbPath = __DIR__ . '/AI/data/questions.db';
        $client = new ClaudeClient(ANTHROPIC_API_KEY);
        $saved = $client->saveToDatabase($dbPath, $module, $questions, $difficulty, $ageMin, $ageMax);

        echo json_encode(['success' => true, 'saved' => $saved, 'total' => count($questions)]);
        exit;
    }
}

// ============================================================================
// DASHBOARD DATA
// ============================================================================

// Claude API Status Check
$claudeConfigPath = __DIR__ . '/includes/claude_config.php';
$claude_configured = false;
if (file_exists($claudeConfigPath)) {
    require_once $claudeConfigPath;
    $claude_configured = defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY !== '';
}

// Module laden
$modules = [];
$moduleDefPath = __DIR__ . '/AI/module_definitions.json';
if (file_exists($moduleDefPath)) {
    $modules = json_decode(file_get_contents($moduleDefPath), true) ?? [];
}

// Bot Status
function getBotStatus() {
    $bots = [
        'ai_generator' => ['name' => 'AI Generator', 'icon' => '🤖', 'file' => 'AIGeneratorBot.php'],
        'dependency' => ['name' => 'Dependency', 'icon' => '📦', 'file' => 'DependencyCheckBot.php'],
        'function_test' => ['name' => 'Function Test', 'icon' => '🧪', 'file' => 'FunctionTestBot.php'],
        'load_test' => ['name' => 'Load Test', 'icon' => '⚡', 'file' => 'LoadTestBot.php'],
        'security' => ['name' => 'Security', 'icon' => '🔒', 'file' => 'SecurityBot.php']
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
        .login-box { background: var(--card); border: 1px solid var(--border); padding: 50px 40px; border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,0.4); max-width: 400px; width: 90%; text-align: center; }
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
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <input type="password" name="admin_password" placeholder="Passwort" required autofocus>
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

        .action-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 25px; transition: all 0.2s; }
        .action-card:hover { transform: translateY(-3px); border-color: var(--accent); box-shadow: 0 5px 25px rgba(67, 210, 64, 0.1); }
        .action-card h3 { font-size: 1.1rem; color: var(--accent); margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
        .action-card p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 15px; }
        .action-card a, .action-card button.card-btn { display: inline-block; padding: 10px 20px; background: var(--accent); color: #000; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; }
        .action-card a:hover, .action-card button.card-btn:hover { background: #35B035; }

        .action-card.orange h3 { color: var(--orange); }
        .action-card.orange a { background: var(--orange); color: #fff; }
        .action-card.orange a:hover { background: #d45a1a; }

        /* Claude Generator Section */
        .claude-section { background: rgba(0, 0, 0, 0.4); border: 1px solid var(--border); border-radius: 16px; padding: 25px; margin-bottom: 25px; }
        .claude-section h2 { font-size: 1.1rem; margin-bottom: 20px; color: var(--accent); display: flex; align-items: center; gap: 10px; }
        .claude-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .claude-form label { display: block; font-size: 0.8rem; color: var(--text-muted); margin-bottom: 6px; }
        .claude-form select, .claude-form input[type="number"] { width: 100%; padding: 10px 12px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 8px; color: #fff; font-size: 0.9rem; }
        .claude-form select:focus, .claude-form input:focus { outline: none; border-color: var(--accent); }
        .claude-form select option { background: #1a3503; color: #fff; }
        .claude-actions { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .btn-generate { padding: 12px 28px; background: var(--accent); color: #000; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .btn-generate:hover { background: #35B035; transform: translateY(-1px); }
        .btn-generate:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn-save-all { padding: 12px 28px; background: #3498db; color: #fff; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; display: none; }
        .btn-save-all:hover { background: #2980b9; }
        .claude-info { font-size: 0.8rem; color: var(--text-muted); margin-left: auto; }

        /* Generated Questions Preview */
        .questions-preview { margin-top: 20px; }
        .q-card { background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 10px; padding: 16px; margin-bottom: 12px; }
        .q-card .q-text { font-weight: 600; color: #fff; margin-bottom: 10px; font-size: 0.95rem; }
        .q-card .q-answers { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px; }
        .q-card .q-answer { padding: 6px 10px; border-radius: 6px; font-size: 0.85rem; }
        .q-card .q-answer.correct { background: rgba(40, 167, 69, 0.25); color: #6cff6c; border: 1px solid rgba(40, 167, 69, 0.4); }
        .q-card .q-answer.wrong { background: rgba(255,255,255,0.05); color: var(--text-muted); border: 1px solid rgba(255,255,255,0.08); }
        .q-card .q-explanation { font-size: 0.8rem; color: var(--text-muted); font-style: italic; }
        .q-card .q-actions { margin-top: 8px; display: flex; gap: 8px; }
        .q-card .q-actions button { padding: 4px 12px; border-radius: 6px; border: none; font-size: 0.8rem; cursor: pointer; }
        .q-card .q-actions .btn-remove { background: rgba(220, 53, 69, 0.3); color: #ff6b6b; }
        .q-card.removed { opacity: 0.3; }

        .loading-spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid rgba(67,210,64,0.3); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite; margin-right: 8px; vertical-align: middle; }
        @keyframes spin { to { transform: rotate(360deg); } }

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
        .status-item { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 12px 20px; display: flex; align-items: center; gap: 10px; color: var(--text); }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; }
        .status-dot.online { background: var(--accent); box-shadow: 0 0 10px var(--accent); }
        .status-dot.offline { background: #e74c3c; }
        .status-dot.unknown { background: #f39c12; }

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
            <a href="adaptive_learning.php" class="nav-secondary">Lernen</a>
            <a href="statistics.php" class="nav-primary">Statistik</a>
            <a href="?logout=1" class="nav-danger">Logout</a>
        </nav>
    </header>

    <div class="container">
        <!-- Bitcoin Ticker -->
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
                <span class="status-dot <?= $claude_configured ? 'unknown' : 'offline' ?>" id="claudeDot"></span>
                <span id="claudeStatusText">Claude API <?= $claude_configured ? 'Key gesetzt' : 'Nicht konfiguriert' ?></span>
            </div>
            <div class="status-item">
                <span>PHP <?= PHP_VERSION ?></span>
            </div>
        </div>

        <!-- Claude Fragen-Generator -->
        <div class="claude-section">
            <h2>Claude Fragen-Generator <span style="font-size:0.7rem;background:var(--accent);color:#000;padding:2px 8px;border-radius:10px;font-weight:600;">Sonnet</span></h2>

            <?php if (!$claude_configured): ?>
            <div style="background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);border-radius:10px;padding:16px;color:#ff6b6b;font-size:0.9rem;">
                API Key nicht konfiguriert. Erstelle <code style="background:rgba(0,0,0,0.3);padding:2px 6px;border-radius:4px;">includes/claude_config.php</code> mit deinem Anthropic API Key.
                <br><small style="color:var(--text-muted);">Key erstellen: <a href="https://console.anthropic.com" target="_blank" style="color:var(--accent);">console.anthropic.com</a></small>
            </div>
            <?php else: ?>
            <div class="claude-form">
                <div>
                    <label>Modul</label>
                    <select id="clModule">
                        <?php foreach ($modules as $key => $mod): ?>
                        <option value="<?= $key ?>"><?= htmlspecialchars($mod['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Anzahl Fragen</label>
                    <select id="clCount">
                        <option value="3">3</option>
                        <option value="5" selected>5</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                    </select>
                </div>
                <div>
                    <label>Altersgruppe</label>
                    <select id="clAge">
                        <option value="5-7">5-7 Jahre</option>
                        <option value="8-10" selected>8-10 Jahre</option>
                        <option value="11-13">11-13 Jahre</option>
                        <option value="14-99">14+ Jahre</option>
                    </select>
                </div>
                <div>
                    <label>Schwierigkeit</label>
                    <select id="clDiff">
                        <option value="1">1 - Sehr leicht</option>
                        <option value="2">2 - Leicht</option>
                        <option value="3" selected>3 - Mittel</option>
                        <option value="4">4 - Schwer</option>
                        <option value="5">5 - Sehr schwer</option>
                    </select>
                </div>
            </div>

            <div class="claude-actions">
                <button class="btn-generate" id="btnGenerate" onclick="generateQuestions()">Generieren</button>
                <button class="btn-save-all" id="btnSaveAll" onclick="saveAllQuestions()">Alle speichern</button>
                <span class="claude-info" id="clInfo"></span>
            </div>

            <div class="questions-preview" id="questionsPreview"></div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="actions-grid">
            <div class="action-card">
                <h3>Backup Manager</h3>
                <p>Datenbanken sichern und wiederherstellen (lokal + OneDrive).</p>
                <a href="backup_manager.php">Backup</a>
            </div>

            <div class="action-card">
                <h3>Bot Dashboard</h3>
                <p>Uebersicht aller Bot-Durchlaeufe und Test-Ergebnisse.</p>
                <a href="bots/bot_summary.php">Dashboard</a>
            </div>

            <div class="action-card" style="border-left: 4px solid #e74c3c;">
                <h3>Cleanup: Gemeldete Fragen</h3>
                <p>Ueberprüfe und verwalte von Lernenden gemeldete fehlerhafte Fragen.</p>
                <a href="admin_cleanup_flags.php">Oeffnen</a>
            </div>

            <div class="action-card">
                <h3>CSV Import</h3>
                <p>Fragen aus CSV-Dateien importieren (Batch-Import fuer alle Module).</p>
                <a href="batch_import.php">Importieren</a>
            </div>

            <div class="action-card orange">
                <h3>Foxy Lernassistent</h3>
                <p>Konfiguration, Animationen und DB-Seeder fuer den Lernfuchs.</p>
                <a href="clippy/test.php">Konfigurieren</a>
            </div>

            <div class="action-card" style="border-left: 4px solid #FFD700;">
                <h3>Leaderboard</h3>
                <p>Highscores, Top-Lerner, Streaks und Modul-Champions fuer die Kids.</p>
                <a href="leaderboard.php">Rangliste</a>
            </div>

            <div class="action-card" style="border-left: 4px solid #3498db;">
                <h3>SQLite WAL Mode Check</h3>
                <p>SQLite Performance unter Last pruefen und WAL-Modus aktivieren.</p>
                <a href="fix_bug026_wal_mode.php">Pruefen</a>
            </div>

            <div class="action-card">
                <h3>User Debug Center</h3>
                <p>User-Management, DB-Analyse und Fehlerdiagnose.</p>
                <a href="debug_users.php">Debug</a>
            </div>

            <div class="action-card">
                <h3>Wallet Admin</h3>
                <p>Kinder-Wallets, Transaktionen und Achievements verwalten.</p>
                <a href="wallet/wallet_admin.php">Verwalten</a>
            </div>
        </div>

        <!-- Bot Zentrale -->
        <div class="bot-section">
            <h2>Bot-Zentrale</h2>
            <div class="bot-grid">
                <?php foreach ($bot_status as $key => $bot): ?>
                <div class="bot-card <?= $bot['running'] ? 'running' : '' ?>">
                    <div class="bot-icon"><?= $bot['icon'] ?></div>
                    <div class="bot-name"><?= $bot['name'] ?></div>
                    <div class="bot-status <?= $bot['running'] ? 'on' : '' ?>"><?= $bot['running'] ? 'Running' : 'Idle' ?></div>
                    <a href="bots/tests/<?= $bot['file'] ?>" target="_blank">Start</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <footer>
        sgiT Education Platform v<?= SGIT_VERSION ?> | <a href="https://sgit.space">sgiT Solution Engineering</a>
    </footer>

    <script>
        // Bitcoin Ticker
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
                document.getElementById('btcEUR').textContent = price.EUR.toLocaleString() + ' EUR';
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

        // Claude API Status Check
        <?php if ($claude_configured): ?>
        fetch('?action=claude_status')
            .then(r => r.json())
            .then(data => {
                const dot = document.getElementById('claudeDot');
                const text = document.getElementById('claudeStatusText');
                if (data.online) {
                    dot.className = 'status-dot online';
                    text.textContent = 'Claude API Online';
                } else {
                    dot.className = 'status-dot offline';
                    text.textContent = 'Claude API Fehler: ' + (data.error || 'Unbekannt');
                }
            })
            .catch(() => {
                document.getElementById('claudeDot').className = 'status-dot offline';
                document.getElementById('claudeStatusText').textContent = 'Claude API Timeout';
            });
        <?php endif; ?>

        // Claude Generator
        let generatedQuestions = [];
        let generatorMeta = {};

        async function generateQuestions() {
            const btn = document.getElementById('btnGenerate');
            const preview = document.getElementById('questionsPreview');
            const info = document.getElementById('clInfo');
            const saveBtn = document.getElementById('btnSaveAll');

            const age = document.getElementById('clAge').value.split('-');

            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner"></span>Generiere...';
            preview.innerHTML = '';
            saveBtn.style.display = 'none';
            info.textContent = '';

            const formData = new FormData();
            formData.append('module', document.getElementById('clModule').value);
            formData.append('count', document.getElementById('clCount').value);
            formData.append('age_min', age[0]);
            formData.append('age_max', age[1] || '99');
            formData.append('difficulty', document.getElementById('clDiff').value);

            try {
                const res = await fetch('?action=generate', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success && data.questions.length > 0) {
                    generatedQuestions = data.questions;
                    generatorMeta = {
                        module: document.getElementById('clModule').value,
                        difficulty: document.getElementById('clDiff').value,
                        age_min: age[0],
                        age_max: age[1] || '99'
                    };

                    renderQuestions(data.questions);
                    saveBtn.style.display = 'inline-block';

                    let infoText = data.raw_count + ' Fragen generiert';
                    if (data.usage) {
                        infoText += ' | ' + data.usage.input_tokens + ' + ' + data.usage.output_tokens + ' Tokens';
                    }
                    if (data.cost_estimate) {
                        infoText += ' | ~' + data.cost_estimate;
                    }
                    info.textContent = infoText;
                } else {
                    preview.innerHTML = '<div style="color:#ff6b6b;padding:16px;">' + (data.error || 'Keine Fragen generiert') + '</div>';
                }
            } catch (e) {
                preview.innerHTML = '<div style="color:#ff6b6b;padding:16px;">Fehler: ' + e.message + '</div>';
            }

            btn.disabled = false;
            btn.textContent = 'Generieren';
        }

        function renderQuestions(questions) {
            const preview = document.getElementById('questionsPreview');
            preview.innerHTML = questions.map((q, i) => {
                const answers = [
                    { text: q.correct, cls: 'correct' },
                    ...q.wrong.map(w => ({ text: w, cls: 'wrong' }))
                ].sort(() => Math.random() - 0.5);

                return `<div class="q-card" id="qcard-${i}">
                    <div class="q-text">${i + 1}. ${escHtml(q.question)}</div>
                    <div class="q-answers">
                        ${answers.map(a => `<div class="q-answer ${a.cls}">${a.cls === 'correct' ? '&#10003; ' : ''}${escHtml(a.text)}</div>`).join('')}
                    </div>
                    ${q.explanation ? `<div class="q-explanation">${escHtml(q.explanation)}</div>` : ''}
                    <div class="q-actions">
                        <button class="btn-remove" onclick="removeQuestion(${i})">Entfernen</button>
                    </div>
                </div>`;
            }).join('');
        }

        function removeQuestion(index) {
            generatedQuestions[index] = null;
            document.getElementById('qcard-' + index).classList.add('removed');

            if (generatedQuestions.filter(q => q !== null).length === 0) {
                document.getElementById('btnSaveAll').style.display = 'none';
            }
        }

        async function saveAllQuestions() {
            const btn = document.getElementById('btnSaveAll');
            const info = document.getElementById('clInfo');
            const toSave = generatedQuestions.filter(q => q !== null);

            if (toSave.length === 0) return;

            btn.disabled = true;
            btn.textContent = 'Speichere...';

            const formData = new FormData();
            formData.append('questions', JSON.stringify(toSave));
            formData.append('module', generatorMeta.module);
            formData.append('difficulty', generatorMeta.difficulty);
            formData.append('age_min', generatorMeta.age_min);
            formData.append('age_max', generatorMeta.age_max);

            try {
                const res = await fetch('?action=save', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    info.textContent = data.saved + '/' + data.total + ' Fragen gespeichert (Duplikate uebersprungen)';
                    btn.style.display = 'none';
                    document.getElementById('questionsPreview').innerHTML =
                        '<div style="background:rgba(40,167,69,0.2);border:1px solid rgba(40,167,69,0.4);border-radius:10px;padding:16px;color:#6cff6c;">' +
                        data.saved + ' Fragen erfolgreich in DB gespeichert!</div>';
                    generatedQuestions = [];
                } else {
                    info.textContent = 'Fehler: ' + data.error;
                }
            } catch (e) {
                info.textContent = 'Fehler: ' + e.message;
            }

            btn.disabled = false;
            btn.textContent = 'Alle speichern';
        }

        function escHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    </script>
</body>
</html>
