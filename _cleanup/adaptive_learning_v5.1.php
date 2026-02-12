<?php
/**
 * ============================================================================
 * sgiT Education - Adaptive Learning v5.1
 * ============================================================================
 * 
 * NEUERUNGEN v5.1 (02.12.2025):
 * - Wallet-Login Integration (PIN für registrierte User)
 * - Direkter Login für nicht-registrierte User
 * - sgiT Logo im Login-Screen
 * - Unterschiedliche Logout-Meldungen
 * 
 * @version 5.1
 * @date 02.12.2025
 * ============================================================================
 */

require_once 'config.php';
initSession();

// ============================================================================
// WALLET INTEGRATION - Prüfe ob Kind im Wallet-System registriert ist
// ============================================================================
$walletChild = null;
$walletEnabled = false;
$testSatsBalance = 0;
$btcPrice = 0;
$satsPerUsd = 0;
$walletMgr = null;
$achievementMgr = null;

// Wallet-Manager laden (falls vorhanden)
if (file_exists(__DIR__ . '/wallet/WalletManager.php')) {
    try {
        require_once __DIR__ . '/wallet/WalletManager.php';
        require_once __DIR__ . '/wallet/AchievementManager.php';
        require_once __DIR__ . '/wallet/SessionManager.php';
        
        $walletMgr = new WalletManager();
        $achievementMgr = new AchievementManager();
        
        // Prüfen ob User im Wallet registriert ist (über Namen)
        if (isset($_SESSION['user_name'])) {
            $walletChild = $walletMgr->getChildByName($_SESSION['user_name']);
            if ($walletChild) {
                $walletEnabled = true;
                $testSatsBalance = $walletChild['balance_sats'];
                $_SESSION['wallet_child_id'] = $walletChild['id'];
            }
        }
        
        // BTC Preis holen (gecacht)
        $btcCacheFile = sys_get_temp_dir() . '/sgit_btc_price.json';
        $btcCacheValid = file_exists($btcCacheFile) && (time() - filemtime($btcCacheFile)) < 60;
        
        if ($btcCacheValid) {
            $btcData = json_decode(file_get_contents($btcCacheFile), true);
            $btcPrice = $btcData['usd'] ?? 0;
            $satsPerUsd = $btcData['sats_per_usd'] ?? 0;
        } else {
            $priceJson = @file_get_contents('https://mempool.space/api/v1/prices');
            if ($priceJson) {
                $prices = json_decode($priceJson, true);
                $btcPrice = $prices['USD'] ?? 0;
                $satsPerUsd = $btcPrice > 0 ? round(100000000 / $btcPrice) : 0;
                file_put_contents($btcCacheFile, json_encode([
                    'usd' => $btcPrice,
                    'sats_per_usd' => $satsPerUsd,
                    'time' => time()
                ]));
            }
        }
    } catch (Exception $e) {
        error_log("Wallet integration error: " . $e->getMessage());
    }
}

// LOGOUT Handler
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: adaptive_learning_v5.1.php');
    exit;
}

// User nicht eingeloggt → zeige Login
$needsLogin = !isset($_SESSION['user_name']) || !isset($_SESSION['user_age']);

if (!$needsLogin) {
    // User ID
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 'user_' . uniqid();
    }

    // Module Scores (Score bleibt, Session-Counter resetet)
    if (!isset($_SESSION['module_scores'])) {
        $_SESSION['module_scores'] = [];
    }

    // Globaler Score
    if (!isset($_SESSION['total_score'])) {
        $_SESSION['total_score'] = 0;
    }

    // User Level
    if (!isset($_SESSION['user_level'])) {
        $_SESSION['user_level'] = [
            'level' => 1,
            'name' => 'Baby',
            'icon' => '👶',
            'points' => 3
        ];
    }
}

/**
 * LOGIN Handler
 */
if (isset($_POST['action']) && $_POST['action'] == 'login') {
    header('Content-Type: application/json');
    
    $name = trim($_POST['name'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Bitte gib deinen Namen ein!']);
        exit;
    }
    
    if ($age < 5 || $age > 15) {
        echo json_encode(['success' => false, 'error' => 'Alter muss zwischen 5 und 15 Jahren sein!']);
        exit;
    }
    
    // Prüfen ob Kind im Wallet-System existiert
    $walletChild = null;
    if (isset($walletMgr)) {
        $walletChild = $walletMgr->getChildByName($name);
    }
    
    if ($walletChild) {
        // User ist im Wallet registriert → PIN-Login erforderlich
        echo json_encode([
            'success' => false,
            'wallet_user' => true,
            'message' => 'Bitte nutze den Wallet-Login mit PIN!',
            'redirect' => 'wallet/login.php?redirect=' . urlencode($_SERVER['PHP_SELF'])
        ]);
    } else {
        // Einfacher Login für nicht-registrierte User
        $_SESSION['user_name'] = $name;
        $_SESSION['user_age'] = $age;
        $_SESSION['user_id'] = 'user_' . uniqid();
        $_SESSION['module_scores'] = [];
        $_SESSION['total_score'] = 0;
        $_SESSION['user_level'] = [
            'level' => 1,
            'name' => 'Baby',
            'icon' => '👶',
            'points' => 3
        ];
        
        echo json_encode([
            'success' => true,
            'wallet_linked' => false
        ]);
    }
    exit;
}

// Rest der PHP-Logik bleibt gleich...
// (getDBConnection, calculateUserLevel, updateUserLevel, getQuestionFromDB, AJAX-Handler)

/**
 * SQLite Verbindung
 */
function getDBConnection() {
    $dbPath = __DIR__ . '/AI/data/questions.db';
    try {
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        error_log("SQLite Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Berechne User-Level
 */
function calculateUserLevel($totalScore) {
    if ($totalScore >= 5000) {
        return ['level' => 5, 'name' => 'Opa', 'icon' => '👴', 'points' => 15];
    } elseif ($totalScore >= 1000) {
        return ['level' => 4, 'name' => 'Erwachsen', 'icon' => '👨', 'points' => 10];
    } elseif ($totalScore >= 500) {
        return ['level' => 3, 'name' => 'Jugend', 'icon' => '👦', 'points' => 7];
    } elseif ($totalScore >= 100) {
        return ['level' => 2, 'name' => 'Kind', 'icon' => '🧒', 'points' => 5];
    } else {
        return ['level' => 1, 'name' => 'Baby', 'icon' => '👶', 'points' => 3];
    }
}

/**
 * Update User-Level
 */
function updateUserLevel() {
    $newLevel = calculateUserLevel($_SESSION['total_score']);
    $oldLevel = $_SESSION['user_level']['level'];
    $_SESSION['user_level'] = $newLevel;
    return $newLevel['level'] > $oldLevel;
}

/**
 * Frage aus DB laden
 */
function getQuestionFromDB($module) {
    $db = getDBConnection();
    if (!$db) return false;
    
    $stmt = $db->prepare("
        SELECT * FROM questions 
        WHERE module = :module
        ORDER BY times_used ASC, RANDOM()
        LIMIT 1
    ");
    
    $stmt->execute([':module' => $module]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $updateStmt = $db->prepare("UPDATE questions SET times_used = times_used + 1 WHERE id = :id");
        $updateStmt->execute([':id' => $row['id']]);
        
        $options = json_decode($row['options'], true);
        if (!is_array($options)) {
            $options = [$row['answer'], 'Option 2', 'Option 3', 'Option 4'];
        }
        shuffle($options);
        
        return [
            'question' => $row['question'],
            'correct' => $row['answer'],
            'options' => $options
        ];
    }
    
    return false;
}

/**
 * AJAX: Frage abrufen
 */
if (isset($_GET['action']) && $_GET['action'] == 'get_question') {
    header('Content-Type: application/json');
    
    $module = $_GET['module'] ?? 'mathematik';
    
    // Init Module Score
    if (!isset($_SESSION['module_scores'][$module])) {
        $_SESSION['module_scores'][$module] = [
            'total_score' => 0,
            'current_session' => [
                'questions' => 0,
                'correct' => 0,
                'score' => 0
            ]
        ];
    }
    
    $questionData = getQuestionFromDB($module);
    
    if ($questionData) {
        echo json_encode([
            'success' => true,
            'question' => $questionData['question'],
            'answer' => $questionData['correct'],
            'options' => $questionData['options'],
            'module_total' => $_SESSION['module_scores'][$module]['total_score'],
            'session_score' => $_SESSION['module_scores'][$module]['current_session']['score'],
            'questions_done' => $_SESSION['module_scores'][$module]['current_session']['questions'],
            'level' => $_SESSION['user_level'],
            'wallet_enabled' => $walletEnabled
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Keine Frage verfügbar'
        ]);
    }
    exit;
}

/**
 * AJAX: Antwort prüfen
 */
if (isset($_POST['action']) && $_POST['action'] == 'check_answer') {
    header('Content-Type: application/json');
    
    $module = $_POST['module'] ?? '';
    $userAnswer = $_POST['answer'] ?? '';
    $correctAnswer = $_POST['correct'] ?? '';
    
    $isCorrect = ($userAnswer === $correctAnswer);
    
    if (!isset($_SESSION['module_scores'][$module])) {
        $_SESSION['module_scores'][$module] = [
            'total_score' => 0,
            'current_session' => [
                'questions' => 0,
                'correct' => 0,
                'score' => 0
            ]
        ];
    }
    
    $_SESSION['module_scores'][$module]['current_session']['questions']++;
    
    if ($isCorrect) {
        $_SESSION['module_scores'][$module]['current_session']['correct']++;
        
        $pointsEarned = $_SESSION['user_level']['points'];
        $_SESSION['module_scores'][$module]['current_session']['score'] += $pointsEarned;
        $_SESSION['module_scores'][$module]['total_score'] += $pointsEarned;
        $_SESSION['total_score'] += $pointsEarned;
        
        $leveledUp = updateUserLevel();
    } else {
        $pointsEarned = 0;
        $leveledUp = false;
    }
    
    $sessionComplete = $_SESSION['module_scores'][$module]['current_session']['questions'] >= 10;
    
    // Wallet Reward Data
    $walletReward = null;
    $newAchievements = [];
    
    // Nach 10 Fragen: Session-Counter zurücksetzen (Score bleibt!)
    if ($sessionComplete) {
        $session = $_SESSION['module_scores'][$module]['current_session'];
        $_SESSION['module_scores'][$module]['current_session'] = [
            'questions' => 0,
            'correct' => 0,
            'score' => 0
        ];
        
        // ================================================================
        // WALLET INTEGRATION: Sats verdienen bei Session-Ende
        // ================================================================
        if (isset($_SESSION['wallet_child_id']) && isset($walletMgr)) {
            $childId = $_SESSION['wallet_child_id'];
            
            // Sats verdienen
            $earnResult = $walletMgr->earnSats(
                $childId,
                $session['correct'],
                10, // max_score
                $module,
                $_SESSION['user_id']
            );
            
            if ($earnResult['success']) {
                $walletReward = [
                    'sats' => $earnResult['sats'],
                    'new_balance' => $earnResult['new_balance'],
                    'breakdown' => $earnResult['breakdown']
                ];
            }
            
            // Achievements prüfen
            if (isset($achievementMgr)) {
                $context = [
                    'just_completed_session' => true,
                    'module' => $module,
                    'score' => $session['correct'],
                    'perfect' => ($session['correct'] === 10)
                ];
                
                $newAchievements = $achievementMgr->checkAndUnlock($childId, $context);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'correct' => $isCorrect,
        'points_earned' => $pointsEarned,
        'session_score' => $sessionComplete ? $session['score'] : $_SESSION['module_scores'][$module]['current_session']['score'],
        'session_correct' => $sessionComplete ? $session['correct'] : $_SESSION['module_scores'][$module]['current_session']['correct'],
        'session_questions' => $sessionComplete ? 10 : $_SESSION['module_scores'][$module]['current_session']['questions'],
        'module_total' => $_SESSION['module_scores'][$module]['total_score'],
        'global_score' => $_SESSION['total_score'],
        'session_complete' => $sessionComplete,
        'level' => $_SESSION['user_level'],
        'leveled_up' => $leveledUp,
        // Wallet Data
        'wallet_reward' => $walletReward,
        'new_achievements' => $newAchievements
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sgiT Education - Adaptive Learning v5.1</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bitcoin: #F7931A;
        }
        
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Test-Sats Banner */
        .test-sats-banner {
            background: linear-gradient(135deg, var(--bitcoin), #E88A00);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 14px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .test-sats-banner .warning {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .test-sats-banner .btc-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 13px;
        }
        
        .test-sats-banner .dashboard-link {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .test-sats-banner .dashboard-link:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .header {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo { height: 50px; }
        .app-title {
            color: var(--primary);
            font-size: 24px;
            font-weight: bold;
        }
        .version {
            font-size: 12px;
            color: var(--accent);
            font-weight: normal;
        }
        .user-info {
            text-align: right;
        }
        .user-greeting {
            font-size: 16px;
            color: var(--primary);
            margin-bottom: 5px;
        }
        .logout-btn {
            font-size: 12px;
            color: #dc3545;
            cursor: pointer;
            text-decoration: underline;
        }
        
        .scores-row {
            display: flex;
            gap: 20px;
            justify-content: flex-end;
            align-items: center;
            margin-top: 10px;
        }
        
        .score-box {
            text-align: center;
        }
        
        .score-number {
            font-size: 28px;
            font-weight: bold;
            color: var(--primary);
        }
        
        .score-number.sats {
            color: var(--bitcoin);
        }
        
        .score-label {
            font-size: 12px;
            color: #666;
        }
        
        /* Login Modal */
        .login-overlay {
            display: <?php echo $needsLogin ? 'flex' : 'none'; ?>;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.95);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        .login-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
        }
        .login-title {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .login-subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }
        .login-input {
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
        }
        .login-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 14px;
            color: #856404;
        }
        .login-info {
            background: #e3f2fd;
            border: 2px solid #2196F3;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 14px;
            color: #1565C0;
        }
        .login-btn {
            width: 100%;
            padding: 15px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
        }
        .login-btn:hover { background: var(--primary); }
        .login-error {
            color: #dc3545;
            margin-top: 10px;
            display: none;
        }
        
        /* Modules Grid - IDENTISCH ZUR AKTUELLEN VERSION */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .module-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            background: var(--accent);
            color: white;
        }
        .module-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        /* Rest des CSS identisch zur aktuellen Version... */
        /* (Quiz Modal, Toast, Session Complete, etc.) */
        
        @media (max-width: 600px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .user-info {
                text-align: center;
            }
            .scores-row {
                justify-content: center;
            }
            .test-sats-banner {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Login Modal -->
    <div class="login-overlay" id="loginOverlay">
        <div class="login-box">
            <img src="assets/images/base_icon_transparent_background.png" alt="sgiT" class="login-logo">
            <div class="login-title">sgiT Education</div>
            <div class="login-subtitle">Adaptive Learning</div>
            
            <input type="text" id="userName" class="login-input" placeholder="Dein Name" maxlength="50">
            <input type="number" id="userAge" class="login-input" placeholder="Dein Alter (5-15)" min="5" max="15">
            
            <div class="login-info">
                💡 <strong>Wallet-User?</strong> Bitte nutze den 
                <a href="wallet/login.php" style="color: var(--bitcoin);">₿ Wallet-Login mit PIN</a>
            </div>
            
            <div class="login-warning">
                ⚠️ <strong>Wichtig:</strong> Ohne Wallet-Registrierung werden deine Daten nur im Browser gespeichert!
            </div>
            
            <button class="login-btn" onclick="doLogin()">Los geht's! →</button>
            <div class="login-error" id="loginError"></div>
        </div>
    </div>

    <?php if (!$needsLogin): ?>
    
    <!-- Test-Sats Banner (nur wenn Wallet aktiv) -->
    <?php if ($walletEnabled): ?>
    <div class="test-sats-banner">
        <div class="warning">
            <span>⚠️</span>
            <span>TEST-SATS - Keine echten Satoshis!</span>
        </div>
        <div class="btc-info">
            <?php if ($btcPrice > 0): ?>
                <span>₿ BTC: $<?php echo number_format($btcPrice); ?></span>
                <span>|</span>
            <?php endif; ?>
            <a href="wallet/child_dashboard.php" class="dashboard-link">🏆 Mein Dashboard</a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Header -->
    <div class="header">
        <div class="logo-section">
            <img src="assets/images/base_icon_transparent_background.png" alt="sgiT" class="logo">
            <div>
                <div class="app-title">Adaptive Learning <span class="version">v5.1</span></div>
            </div>
        </div>
        <div class="user-info">
            <div class="user-greeting">
                Hallo <?php echo htmlspecialchars($_SESSION['user_name']); ?> 
                (<?php echo $_SESSION['user_age']; ?> Jahre) 
                <span class="logout-btn" onclick="logout()">Abmelden</span>
            </div>
            
            <div class="scores-row">
                <div class="score-box">
                    <div class="score-label">Punkte</div>
                    <div class="score-number" id="totalScore"><?php echo $_SESSION['total_score']; ?></div>
                </div>
                
                <?php if ($walletEnabled): ?>
                <div class="score-box">
                    <div class="score-label">Test-Sats</div>
                    <div class="score-number sats" id="testSatsBalance">₿ <?php echo number_format($testSatsBalance); ?></div>
                </div>
                <?php else: ?>
                <div class="score-box">
                    <div class="score-label">Test-Sats verdienen</div>
                    <div class="score-number" style="font-size: 14px; color: var(--bitcoin);">
                        <a href="wallet/register.php" style="color: var(--bitcoin); text-decoration: none; font-weight: bold;">→ Wallet anmelden</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modules Grid -->
    <div class="modules-grid">
        <div class="module-card" onclick="startQuiz('mathematik')">
            <div class="module-icon">🔢</div>
            <div>Mathematik</div>
        </div>
        <div class="module-card" onclick="startQuiz('lesen')">
            <div class="module-icon">📖</div>
            <div>Lesen</div>
        </div>
        <div class="module-card" onclick="startQuiz('englisch')">
            <div class="module-icon">🇬🇧</div>
            <div>Englisch</div>
        </div>
        <div class="module-card" onclick="startQuiz('wissenschaft')">
            <div class="module-icon">🔬</div>
            <div>Wissenschaft</div>
        </div>
        <div class="module-card" onclick="startQuiz('erdkunde')">
            <div class="module-icon">🌍</div>
            <div>Erdkunde</div>
        </div>
        <div class="module-card" onclick="startQuiz('chemie')">
            <div class="module-icon">⚗️</div>
            <div>Chemie</div>
        </div>
        <div class="module-card" onclick="startQuiz('physik')">
            <div class="module-icon">⚛️</div>
            <div>Physik</div>
        </div>
        <div class="module-card" onclick="startQuiz('kunst')">
            <div class="module-icon">🎨</div>
            <div>Kunst</div>
        </div>
        <div class="module-card" onclick="startQuiz('musik')">
            <div class="module-icon">🎵</div>
            <div>Musik</div>
        </div>
        <div class="module-card" onclick="startQuiz('computer')">
            <div class="module-icon">💻</div>
            <div>Computer</div>
        </div>
        <div class="module-card" onclick="startQuiz('bitcoin')">
            <div class="module-icon">₿</div>
            <div>Bitcoin</div>
        </div>
        <div class="module-card" onclick="startQuiz('geschichte')">
            <div class="module-icon">📚</div>
            <div>Geschichte</div>
        </div>
        <div class="module-card" onclick="startQuiz('biologie')">
            <div class="module-icon">🧬</div>
            <div>Biologie</div>
        </div>
        <div class="module-card" onclick="startQuiz('steuern')">
            <div class="module-icon">💰</div>
            <div>Steuern</div>
        </div>
        <div class="module-card" onclick="startQuiz('programmieren')">
            <div class="module-icon">👨‍💻</div>
            <div>Programmieren</div>
        </div>
        <div class="module-card" onclick="startQuiz('verkehr')">
            <div class="module-icon">🚗</div>
            <div>Verkehr</div>
        </div>
    </div>
    
    <?php endif; ?>
    
    <script>
        // Config
        const walletEnabled = <?php echo $walletEnabled ? 'true' : 'false'; ?>;
        
        // Login
        function doLogin() {
            const name = document.getElementById('userName').value.trim();
            const age = parseInt(document.getElementById('userAge').value);
            const errorDiv = document.getElementById('loginError');
            
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('name', name);
            formData.append('age', age);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else if (data.wallet_user && data.redirect) {
                    // User ist im Wallet registriert → Weiterleitung zum PIN-Login
                    alert(data.message);
                    window.location.href = data.redirect;
                } else {
                    errorDiv.textContent = data.error;
                    errorDiv.style.display = 'block';
                }
            });
        }
        
        function logout() {
            let message;
            if (walletEnabled) {
                message = 'Möchtest du dich wirklich abmelden?';
            } else {
                message = 'Bist du sicher? Dein Fortschritt geht verloren!';
            }
            
            if (confirm(message)) {
                location.href = '?logout=1';
            }
        }
        
        // Rest des JavaScript-Codes identisch zur aktuellen Version...
    </script>
</body>
</html>
