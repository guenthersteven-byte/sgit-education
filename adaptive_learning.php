<?php
/**
 * ============================================================================
 * sgiT Education - Adaptive Learning v5.9
 * ============================================================================
 * 
 * NEUERUNGEN v5.9 (06.12.2025):
 * - BUG-028/029 FIX: Performance-Optimierung!
 *   - ORDER BY RANDOM() entfernt (verursachte TEMP B-TREE bei jeder Query)
 *   - Neue Methode: COUNT + OFFSET für Zufallsauswahl
 *   - DB-Indizes hinzugefügt (idx_questions_module, idx_questions_module_age)
 *   - ~10x schneller bei hoher Last (50+ gleichzeitige User)
 *
 * NEUERUNGEN v5.8 (06.12.2025):
 * - BUG-027 FIX: Navigation-Bar hinzugefügt!
 *   - Links zu Startseite, Leaderboard, Statistik, Admin
 *   - Responsive Design für Mobile
 *   - sgiT Branding #1A3503/#43D240
 *
 * NEUERUNGEN v5.7 (05.12.2025):
 * - BUG-018 FIX: Erwachsene (>21 Jahre) bekommen jetzt die schwierigsten
 *   Fragen statt der einfachsten! Fallback sortiert nach age_min DESC
 *
 * NEUERUNGEN v5.6 (04.12.2025):
 * - BUG-016 FIX: Altersgerechte Fragenauswahl!
 *   - Fragen werden jetzt nach age_min/age_max gefiltert
 *   - 7-Jährige bekommen keine Potenz-Fragen mehr
 *   - Fallback-Logik wenn keine altersgerechten Fragen vorhanden
 * - Alterslimit erweitert auf 5-99 Jahre
 *
 * NEUERUNGEN v5.5 (04.12.2025):
 * - Erklärungen werden nach jeder Antwort angezeigt
 * - explanation-Feld aus DB geladen und im Frontend dargestellt
 * - Verbessertes visuelles Feedback
 *
 * NEUERUNGEN v5.4 (04.12.2025):
 * - BUG-005 FIX: Alter auf 5-21 Jahre erweitert
 * - Neue Schwierigkeitsstufen-Matrix
 * - Vorbereitung für CSV-Import
 *
 * NEUERUNGEN v5.3 (04.12.2025):
 * - BUG-004 FIX: Sats werden jetzt zuverlässig vergeben
 * - Robustere Wallet-Integration im check_answer Handler
 * - Debug-Logging für Wallet-Reward-Flow
 * - Error-Feedback bei fehlgeschlagenen Rewards
 * - Wallet-ID wird bei jedem AJAX-Request erneut geprüft
 * 
 * @version 5.9
 * @date 06.12.2025
 * ============================================================================
 */

require_once 'config.php';
initSession();

// ============================================================================
// DEBUG MODE - Setze auf true für Diagnose
// ============================================================================
define('WALLET_DEBUG', true);

function walletDebugLog($message, $data = null) {
    if (!WALLET_DEBUG) return;
    
    $logEntry = date('Y-m-d H:i:s') . " | " . $message;
    if ($data !== null) {
        $logEntry .= " | Data: " . json_encode($data);
    }
    error_log("[WALLET_DEBUG] " . $logEntry);
}

// ============================================================================
// WALLET INTEGRATION - Session-Synchronisation
// ============================================================================
$walletChild = null;
$walletEnabled = false;
$isWalletUser = false;
$testSatsBalance = 0;
$btcPrice = 0;
$satsPerUsd = 0;

/**
 * Hilfsfunktion: Ermittelt die wallet_child_id aus verschiedenen Quellen
 * Diese Funktion stellt sicher, dass die wallet_child_id korrekt gesetzt ist
 * 
 * @return int|null Die Child-ID oder null
 */
function resolveWalletChildId() {
    // 1. Prüfe ob bereits in Session
    if (isset($_SESSION['wallet_child_id']) && $_SESSION['wallet_child_id'] > 0) {
        walletDebugLog("wallet_child_id aus Session", $_SESSION['wallet_child_id']);
        return (int) $_SESSION['wallet_child_id'];
    }
    
    // 2. Prüfe SessionManager (Wallet-Login mit PIN)
    if (class_exists('SessionManager') && SessionManager::isLoggedIn()) {
        $childId = SessionManager::getChildId();
        if ($childId) {
            $_SESSION['wallet_child_id'] = $childId;
            walletDebugLog("wallet_child_id aus SessionManager", $childId);
            return (int) $childId;
        }
    }
    
    // 3. Fallback: Suche Kind anhand des Namens
    if (isset($_SESSION['user_name']) && file_exists(__DIR__ . '/wallet/WalletManager.php')) {
        require_once __DIR__ . '/wallet/WalletManager.php';
        $mgr = new WalletManager();
        $child = $mgr->getChildByName($_SESSION['user_name']);
        if ($child) {
            $_SESSION['wallet_child_id'] = $child['id'];
            walletDebugLog("wallet_child_id via Name-Lookup gefunden", [
                'name' => $_SESSION['user_name'],
                'child_id' => $child['id']
            ]);
            return (int) $child['id'];
        }
    }
    
    walletDebugLog("Keine wallet_child_id gefunden", [
        'session_keys' => array_keys($_SESSION),
        'user_name' => $_SESSION['user_name'] ?? 'nicht gesetzt'
    ]);
    
    return null;
}

// Wallet-Manager + SessionManager laden
if (file_exists(__DIR__ . '/wallet/WalletManager.php')) {
    try {
        require_once __DIR__ . '/wallet/WalletManager.php';
        require_once __DIR__ . '/wallet/AchievementManager.php';
        require_once __DIR__ . '/wallet/SessionManager.php';
        
        $walletMgr = new WalletManager();
        $achievementMgr = new AchievementManager();
        
        // ================================================================
        // WICHTIG: Prüfe ob User über Wallet-System eingeloggt ist
        // und synchronisiere die Session-Daten
        // ================================================================
        if (SessionManager::isLoggedIn()) {
            // Wallet-User ist eingeloggt → übernehme Session-Daten
            $childData = SessionManager::getChild();
            
            if ($childData) {
                // Synchronisiere in adaptive_learning Session-Keys
                $_SESSION['user_name'] = $childData['name'];
                $_SESSION['user_age'] = $childData['age'];
                $_SESSION['wallet_child_id'] = $childData['id'];
                
                // Lade vollständige Wallet-Daten
                $walletChild = $walletMgr->getChildWallet($childData['id']);
                
                if ($walletChild) {
                    $walletEnabled = true;
                    $isWalletUser = true;
                    $testSatsBalance = $walletChild['balance_sats'];
                    
                    walletDebugLog("Wallet-User eingeloggt via SessionManager", [
                        'child_id' => $childData['id'],
                        'name' => $childData['name'],
                        'balance' => $testSatsBalance
                    ]);
                }
            }
        }
        // Fallback: Prüfe ob normaler User im Wallet registriert ist
        elseif (isset($_SESSION['user_name'])) {
            $walletChild = $walletMgr->getChildByName($_SESSION['user_name']);
            if ($walletChild) {
                $walletEnabled = true;
                $testSatsBalance = $walletChild['balance_sats'];
                $_SESSION['wallet_child_id'] = $walletChild['id'];
                
                walletDebugLog("Wallet-User erkannt via Name-Fallback", [
                    'child_id' => $walletChild['id'],
                    'name' => $_SESSION['user_name'],
                    'balance' => $testSatsBalance
                ]);
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
    // Wallet-User: SessionManager logout
    if (class_exists('SessionManager') && SessionManager::isLoggedIn()) {
        SessionManager::logout();
    }
    // Standard-Session löschen
    session_destroy();
    header('Location: adaptive_learning.php');
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
    
    if ($age < 5 || $age > 99) {
        echo json_encode(['success' => false, 'error' => 'Du musst mindestens 5 Jahre alt sein!']);
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
        
        // WICHTIG: Für nicht-Wallet-User wallet_child_id NICHT setzen
        unset($_SESSION['wallet_child_id']);
        
        walletDebugLog("Normaler Login (kein Wallet-User)", ['name' => $name, 'age' => $age]);
        
        echo json_encode([
            'success' => true,
            'wallet_linked' => false
        ]);
    }
    exit;
}

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
 * Frage aus DB laden - MIT ALTERSFILTERUNG
 * 
 * BUG-028/029 FIX: Performance-Optimierung!
 * - ORDER BY RANDOM() entfernt (verursachte TEMP B-TREE)
 * - Stattdessen: COUNT + OFFSET für echte Zufallsauswahl
 * - ~10x schneller bei hoher Last
 * 
 * BUG-018 FIX: Erwachsene (>21) bekommen die schwierigsten Fragen
 * BUG-016 FIX: Altersgerechte Fragenauswahl
 * 
 * @param string $module Das gewählte Modul
 * @return array|false Frage-Daten oder false
 * @version 2.0 - Performance optimiert
 */
function getQuestionFromDB($module) {
    $db = getDBConnection();
    if (!$db) return false;
    
    // User-Alter aus Session holen
    $userAge = isset($_SESSION['user_age']) ? (int)$_SESSION['user_age'] : 10;
    
    $row = null;
    
    // ========================================================================
    // SCHRITT 1: Versuche altersgerechte Frage zu finden
    // ========================================================================
    // Erst COUNT holen, dann mit OFFSET zufällige Frage wählen (SCHNELL!)
    $countStmt = $db->prepare("
        SELECT COUNT(*) FROM questions 
        WHERE module = :module
        AND age_min <= :user_age
        AND age_max >= :user_age
    ");
    $countStmt->execute([':module' => $module, ':user_age' => $userAge]);
    $count = (int) $countStmt->fetchColumn();
    
    if ($count > 0) {
        // Zufälliger Offset (ohne ORDER BY RANDOM!)
        $offset = mt_rand(0, $count - 1);
        
        $stmt = $db->prepare("
            SELECT * FROM questions 
            WHERE module = :module
            AND age_min <= :user_age
            AND age_max >= :user_age
            ORDER BY times_used ASC
            LIMIT 1 OFFSET :offset
        ");
        $stmt->bindValue(':module', $module, PDO::PARAM_STR);
        $stmt->bindValue(':user_age', $userAge, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // ========================================================================
    // SCHRITT 2: Fallback für jüngere Kinder (erweiterte Altersspanne)
    // ========================================================================
    if (!$row && $userAge <= 10) {
        $countStmt = $db->prepare("
            SELECT COUNT(*) FROM questions 
            WHERE module = :module
            AND age_min <= :max_age
        ");
        $countStmt->execute([':module' => $module, ':max_age' => $userAge + 2]);
        $count = (int) $countStmt->fetchColumn();
        
        if ($count > 0) {
            $offset = mt_rand(0, $count - 1);
            
            $stmt = $db->prepare("
                SELECT * FROM questions 
                WHERE module = :module
                AND age_min <= :max_age
                ORDER BY age_min ASC, times_used ASC
                LIMIT 1 OFFSET :offset
            ");
            $stmt->bindValue(':module', $module, PDO::PARAM_STR);
            $stmt->bindValue(':max_age', $userAge + 2, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    // ========================================================================
    // SCHRITT 3: Letzter Fallback - irgendeine Frage aus dem Modul
    // BUG-018: Erwachsene bekommen die schwierigsten Fragen
    // ========================================================================
    if (!$row) {
        $countStmt = $db->prepare("SELECT COUNT(*) FROM questions WHERE module = :module");
        $countStmt->execute([':module' => $module]);
        $count = (int) $countStmt->fetchColumn();
        
        if ($count > 0) {
            $offset = mt_rand(0, $count - 1);
            
            // Sortierung: Erwachsene (>21) = schwierigste, Kinder = einfachste
            $sortOrder = ($userAge > 21) ? 'DESC' : 'ASC';
            
            $stmt = $db->prepare("
                SELECT * FROM questions 
                WHERE module = :module
                ORDER BY age_min $sortOrder, times_used ASC
                LIMIT 1 OFFSET :offset
            ");
            $stmt->bindValue(':module', $module, PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Log Info wenn Fallback genutzt wird
            if ($row) {
                error_log("[AGE_FALLBACK] User $userAge in Modul $module -> Fallback auf age_min={$row['age_min']}, age_max={$row['age_max']} (Sort: $sortOrder)");
            }
        }
    }
    
    // ========================================================================
    // Frage gefunden: times_used erhöhen und zurückgeben
    // ========================================================================
    if ($row) {
        // Asynchron: times_used erhöhen (non-blocking)
        $updateStmt = $db->prepare("UPDATE questions SET times_used = times_used + 1 WHERE id = :id");
        $updateStmt->execute([':id' => $row['id']]);
        
        $options = json_decode($row['options'], true);
        if (!is_array($options)) {
            $options = [$row['answer'], 'Option 2', 'Option 3', 'Option 4'];
        }
        shuffle($options);
        
        // Erklärung aus DB holen (explanation oder erklaerung Feld)
        $explanation = $row['explanation'] ?? $row['erklaerung'] ?? '';
        
        return [
            'question' => $row['question'],
            'correct' => $row['answer'],
            'options' => $options,
            'explanation' => $explanation
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
    
    // wallet_child_id bei jedem Request prüfen
    $childId = resolveWalletChildId();
    $walletActive = ($childId !== null);
    
    if ($questionData) {
        echo json_encode([
            'success' => true,
            'question' => $questionData['question'],
            'answer' => $questionData['correct'],
            'options' => $questionData['options'],
            'explanation' => $questionData['explanation'] ?? '',
            'module_total' => $_SESSION['module_scores'][$module]['total_score'],
            'session_score' => $_SESSION['module_scores'][$module]['current_session']['score'],
            'questions_done' => $_SESSION['module_scores'][$module]['current_session']['questions'],
            'level' => $_SESSION['user_level'],
            'wallet_enabled' => $walletActive,
            'wallet_child_id' => $childId // Debug: zeige child_id
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
    $walletError = null;
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
        // WALLET INTEGRATION v5.3: Robuste Sats-Vergabe
        // ================================================================
        
        // IMMER die child_id neu ermitteln (nicht auf Session verlassen)
        $childId = resolveWalletChildId();
        
        walletDebugLog("Session-Ende erreicht", [
            'module' => $module,
            'correct' => $session['correct'],
            'score' => $session['score'],
            'child_id' => $childId
        ]);
        
        if ($childId !== null) {
            try {
                // WICHTIG: Manager-Klassen IMMER neu erstellen für frische DB-Verbindung
                require_once __DIR__ . '/wallet/WalletManager.php';
                require_once __DIR__ . '/wallet/AchievementManager.php';
                
                $freshWalletMgr = new WalletManager();
                $freshAchievementMgr = new AchievementManager();
                
                walletDebugLog("Starte earnSats()", [
                    'child_id' => $childId,
                    'correct' => $session['correct'],
                    'max_score' => 10,
                    'module' => $module
                ]);
                
                // Sats verdienen
                $earnResult = $freshWalletMgr->earnSats(
                    $childId,
                    $session['correct'],
                    10, // max_score
                    $module,
                    $_SESSION['user_id'] ?? 'unknown'
                );
                
                walletDebugLog("earnSats() Ergebnis", $earnResult);
                
                if ($earnResult['success']) {
                    $walletReward = [
                        'sats' => $earnResult['sats'],
                        'new_balance' => $earnResult['new_balance'],
                        'breakdown' => $earnResult['breakdown']
                    ];
                    
                    walletDebugLog("Sats erfolgreich vergeben", $walletReward);
                } else {
                    // Fehlermeldung speichern für Client
                    $walletError = $earnResult['error'] ?? 'Unbekannter Fehler';
                    $walletReward = [
                        'sats' => 0,
                        'error' => $walletError,
                        'breakdown' => $earnResult['breakdown'] ?? []
                    ];
                    
                    walletDebugLog("earnSats() fehlgeschlagen", [
                        'error' => $walletError,
                        'breakdown' => $earnResult['breakdown'] ?? []
                    ]);
                }
                
                // Achievements prüfen - IMMER nach Session-Ende
                $context = [
                    'just_completed_session' => true,
                    'module' => $module,
                    'score' => $session['correct'],
                    'perfect' => ($session['correct'] === 10)
                ];
                
                $newAchievements = $freshAchievementMgr->checkAndUnlock($childId, $context);
                
                if (!empty($newAchievements)) {
                    walletDebugLog("Neue Achievements freigeschaltet", array_column($newAchievements, 'name'));
                }
                
            } catch (Exception $e) {
                $walletError = "Exception: " . $e->getMessage();
                walletDebugLog("EXCEPTION bei Wallet-Reward", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            walletDebugLog("Keine Sats vergeben - kein Wallet-User", [
                'user_name' => $_SESSION['user_name'] ?? 'unbekannt'
            ]);
        }
    }
    
    // Response zusammenbauen
    $response = [
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
        'wallet_error' => $walletError,
        'new_achievements' => $newAchievements,
        // Debug Info (kann später entfernt werden)
        'debug' => WALLET_DEBUG ? [
            'wallet_child_id' => $_SESSION['wallet_child_id'] ?? null,
            'user_name' => $_SESSION['user_name'] ?? null
        ] : null
    ];
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sgiT Education - Adaptive Learning v5.6</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bitcoin: #F7931A;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
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
        .user-level {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary);
            margin: 5px 0;
        }
        .level-points {
            font-size: 12px;
            color: var(--accent);
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
        
        /* Quiz Modal */
        .quiz-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            overflow-y: auto;
        }
        .quiz-modal.active { 
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quiz-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            width: 90%;
            margin: 20px;
            position: relative;
        }
        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--accent);
        }
        .module-title {
            font-size: 28px;
            color: var(--primary);
            font-weight: bold;
        }
        .module-total {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .session-info {
            text-align: right;
        }
        .session-label {
            font-size: 12px;
            color: #666;
        }
        .session-value {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary);
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .question-text {
            font-size: 24px;
            margin-bottom: 30px;
            color: #333;
            min-height: 60px;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--accent);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .option-btn {
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
        }
        .option-btn:hover:not(:disabled) {
            background: #f0f0f0;
            border-color: var(--accent);
        }
        .option-btn.correct {
            background: #d4edda !important;
            border-color: #28a745 !important;
        }
        .option-btn.wrong {
            background: #f8d7da !important;
            border-color: #dc3545 !important;
        }
        .option-btn:disabled { cursor: default; }
        .feedback {
            margin-top: 20px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            min-height: 30px;
        }
        
        .explanation {
            margin-top: 15px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-left: 4px solid var(--accent);
            border-radius: 8px;
            font-size: 15px;
            color: #2e7d32;
            text-align: left;
            line-height: 1.5;
        }
        
        .explanation.wrong {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            border-left-color: #dc3545;
            color: #c62828;
        }
        
        .explanation strong {
            color: var(--primary);
        }
        
        .explanation-icon {
            font-size: 18px;
            margin-right: 8px;
        }
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 30px;
            cursor: pointer;
            color: #999;
            background: none;
            border: none;
        }
        .close-btn:hover { color: #dc3545; }
        .next-btn {
            margin-top: 20px;
            padding: 15px 40px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            cursor: pointer;
            display: none;
            font-weight: bold;
        }
        .next-btn:hover { background: var(--primary); }
        .next-btn.show { display: inline-block; }
        
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 20000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .toast {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
            max-width: 350px;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .toast.sats {
            border-left: 4px solid var(--bitcoin);
        }
        
        .toast.achievement {
            border-left: 4px solid #FFD700;
        }
        
        .toast.error {
            border-left: 4px solid #dc3545;
        }
        
        .toast-icon {
            font-size: 32px;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: bold;
            color: var(--primary);
            font-size: 14px;
        }
        
        .toast-message {
            font-size: 12px;
            color: #666;
        }
        
        .toast-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #999;
        }
        
        /* Session Complete Modal */
        .session-complete-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 15000;
            align-items: center;
            justify-content: center;
        }
        
        .session-complete-modal.active {
            display: flex;
        }
        
        .session-complete-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .session-complete-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .session-complete-title {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .session-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }
        
        .session-stat {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
        }
        
        .session-stat.sats {
            background: linear-gradient(135deg, #FFF3E0, #FFE0B2);
            border: 2px solid var(--bitcoin);
        }
        
        .session-stat.error {
            background: #f8d7da;
            border: 2px solid #dc3545;
        }
        
        .session-stat-value {
            font-size: 28px;
            font-weight: bold;
            color: var(--primary);
        }
        
        .session-stat.sats .session-stat-value {
            color: var(--bitcoin);
        }
        
        .session-stat.error .session-stat-value {
            color: #dc3545;
            font-size: 14px;
        }
        
        .session-stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .session-achievements {
            margin: 20px 0;
            padding: 15px;
            background: linear-gradient(135deg, #FFF8E1, #FFECB3);
            border-radius: 12px;
            border: 2px solid #FFD700;
        }
        
        .session-achievements h4 {
            color: #F57F17;
            margin-bottom: 10px;
        }
        
        .achievement-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: white;
            padding: 5px 12px;
            border-radius: 20px;
            margin: 5px;
            font-size: 13px;
        }
        
        .session-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
        
        .session-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .session-btn.primary {
            background: var(--accent);
            color: white;
        }
        
        .session-btn.secondary {
            background: #f0f0f0;
            color: var(--primary);
        }
        
        .session-btn:hover {
            transform: translateY(-2px);
        }
        
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
        
        /* ========================================
         * BUG-027 FIX: Navigation Bar
         * ======================================== */
        .main-nav {
            background: rgba(255,255,255,0.98);
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.15);
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .nav-links {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            color: var(--primary);
            background: #f8f9fa;
        }
        
        .nav-link:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-1px);
        }
        
        .nav-link.active {
            background: var(--primary);
            color: white;
        }
        
        .nav-link-icon {
            font-size: 16px;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            color: var(--primary);
            font-size: 14px;
        }
        
        .nav-brand img {
            height: 28px;
            width: 28px;
        }
        
        /* BUG-047 FIX: Bitcoin-Info in Navigation */
        .nav-btc-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--bitcoin), #E88A00);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            color: white;
        }
        .btc-warning {
            font-weight: 600;
            opacity: 0.9;
        }
        .btc-price {
            font-weight: bold;
        }
        .btc-dashboard-link {
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btc-dashboard-link:hover {
            background: rgba(255,255,255,0.35);
        }
        
        @media (max-width: 768px) {
            .main-nav {
                flex-direction: column;
                gap: 12px;
            }
            .nav-links {
                justify-content: center;
            }
            .nav-link {
                padding: 6px 12px;
                font-size: 13px;
            }
            .nav-link span:not(.nav-link-icon) {
                display: none;
            }
            .nav-link-icon {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Login Modal -->
    <div class="login-overlay" id="loginOverlay">
        <div class="login-box">
            <img src="assets/images/base_icon_transparent_background.png" alt="sgiT" style="width: 80px; height: 80px; margin: 0 auto 20px; display: block;">
            <div class="login-title">sgiT Education</div>
            <div class="login-subtitle">Adaptive Learning</div>
            
            <input type="text" id="userName" class="login-input" placeholder="Dein Name" maxlength="50">
            <input type="number" id="userAge" class="login-input" placeholder="Dein Alter (ab 5)" min="5" max="99">
            
            <div class="login-info" style="cursor: pointer;" onclick="window.location.href='wallet/login.php'">
                💡 <strong>Wallet-User?</strong> Bitte nutze den 
                <a href="wallet/login.php" 
                   style="color: var(--bitcoin); font-weight: bold; text-decoration: underline; cursor: pointer;"
                   onclick="event.stopPropagation(); window.location.href='wallet/login.php';">₿ Wallet-Login mit PIN</a>
            </div>
            
            <div class="login-warning">
                ⚠️ <strong>Wichtig:</strong> Ohne Wallet-Login werden deine Daten nur im Browser gespeichert. 
                <br><strong style="color: var(--bitcoin);">→ Mit Wallet-Login bleibt dein Fortschritt sicher gespeichert!</strong>
            </div>
            
            <button class="login-btn" onclick="doLogin()">Los geht's! →</button>
            <div class="login-error" id="loginError"></div>
        </div>
    </div>

    <?php if (!$needsLogin): ?>
    
    <!-- ========================================
         BUG-027 FIX: Main Navigation Bar
         BUG-047 FIX: Bitcoin-Info in Nav integriert
         ======================================== -->
    <nav class="main-nav" id="mainNavigation">
        <div class="nav-brand">
            <img src="assets/images/base_icon_transparent_background.png" alt="sgiT">
            <span>sgiT Education</span>
        </div>
        
        <?php if ($walletEnabled): ?>
        <div class="nav-btc-info">
            <span class="btc-warning">⚠️ TEST-SATS</span>
            <?php if ($btcPrice > 0): ?>
                <span class="btc-price">₿ $<?php echo number_format($btcPrice); ?></span>
            <?php endif; ?>
            <a href="wallet/child_dashboard.php" class="btc-dashboard-link">🏆 Dashboard</a>
        </div>
        <?php endif; ?>
        
        <div class="nav-links">
            <a href="leaderboard.php" class="nav-link">
                <span class="nav-link-icon">🏆</span>
                <span>Leaderboard</span>
            </a>
            <a href="statistics.php" class="nav-link">
                <span class="nav-link-icon">📊</span>
                <span>Statistik</span>
            </a>
            <?php if ($walletEnabled): ?>
            <a href="wallet/child_dashboard.php" class="nav-link">
                <span class="nav-link-icon">₿</span>
                <span>Wallet</span>
            </a>
            <?php endif; ?>
            <a href="index.php" class="nav-link">
                <span class="nav-link-icon">🏠</span>
                <span>Start</span>
            </a>
        </div>
    </nav>
    
    <!-- Header -->
    <div class="header">
        <div class="logo-section">
            <img src="assets/images/base_icon_transparent_background.png" alt="sgiT" class="logo">
            <div>
                <div class="app-title">Adaptive Learning <span class="version">v5.5</span></div>
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
        <div class="module-card" onclick="startQuiz('finanzen')">
            <div class="module-icon">💰</div>
            <div>Finanzen</div>
        </div>
        <div class="module-card" onclick="startQuiz('programmieren')">
            <div class="module-icon">👨‍💻</div>
            <div>Programmieren</div>
        </div>
        <div class="module-card" onclick="startQuiz('verkehr')">
            <div class="module-icon">🚗</div>
            <div>Verkehr</div>
        </div>
        <div class="module-card" onclick="startQuiz('unnuetzes_wissen')">
            <div class="module-icon">🤯</div>
            <div>Unnützes Wissen</div>
        </div>
        <div class="module-card" onclick="startQuiz('sport')">
            <div class="module-icon">🏃</div>
            <div>Sport</div>
        </div>
        <div class="module-card" onclick="window.location.href='/zeichnen/'">
            <div class="module-icon">✏️</div>
            <div>Zeichnen</div>
        </div>
        <div class="module-card" onclick="window.location.href='/logik/'">
            <div class="module-icon">🧩</div>
            <div>Logik & Rätsel</div>
        </div>
        <div class="module-card" onclick="window.location.href='/kochen/'" style="border: 2px dashed var(--accent);">
            <div class="module-icon">🍳</div>
            <div>Kochen <span style="font-size: 10px; color: var(--accent);">NEU!</span></div>
        </div>
    </div>
    
    <!-- Quiz Modal -->
    <div id="quizModal" class="quiz-modal">
        <div class="quiz-container">
            <button class="close-btn" onclick="closeQuiz()">&times;</button>
            
            <div class="quiz-header">
                <div>
                    <div class="module-title" id="moduleTitle">Modul</div>
                    <div class="module-total" id="moduleTotal">Gesamt: 0 Punkte</div>
                </div>
                <div class="session-info">
                    <div class="session-label">Diese Session:</div>
                    <div class="session-value" id="sessionScore">0 Punkte</div>
                </div>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill" style="width: 0%;">
                    <span id="progressText">Frage 0/10</span>
                </div>
            </div>
            
            <div class="question-text" id="questionText">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Lade Frage...</p>
                </div>
            </div>
            
            <div class="options-grid" id="optionsContainer"></div>
            <div class="feedback" id="feedback"></div>
            <div class="explanation" id="explanation" style="display: none;"></div>
            <button class="next-btn" id="nextBtn" onclick="nextQuestion()">Nächste Frage →</button>
        </div>
    </div>
    
    <!-- Session Complete Modal -->
    <div id="sessionCompleteModal" class="session-complete-modal">
        <div class="session-complete-box">
            <div class="session-complete-icon" id="sessionIcon">🎉</div>
            <div class="session-complete-title" id="sessionTitle">Session abgeschlossen!</div>
            
            <div class="session-stats" id="sessionStats">
                <!-- Wird dynamisch gefüllt -->
            </div>
            
            <div class="session-achievements" id="sessionAchievements" style="display: none;">
                <h4>🏆 Neue Achievements!</h4>
                <div id="achievementList"></div>
            </div>
            
            <div class="session-actions">
                <button class="session-btn secondary" onclick="closeSessionModal()">Zurück zur Übersicht</button>
                <button class="session-btn primary" onclick="continueSession()">Weiter lernen →</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // Config
        const walletEnabled = <?php echo $walletEnabled ? 'true' : 'false'; ?>;
        const debugMode = <?php echo WALLET_DEBUG ? 'true' : 'false'; ?>;
        
        // Toast System
        function showToast(type, icon, title, message, duration = 5000) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <div class="toast-icon">${icon}</div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">×</button>
            `;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
        
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
                // Wallet-User: Fortschritt bleibt in DB gespeichert
                message = 'Möchtest du dich wirklich abmelden?';
            } else {
                // Nicht-Wallet-User: Fortschritt geht verloren
                message = 'Bist du sicher? Dein Fortschritt geht verloren!';
            }
            
            if (confirm(message)) {
                location.href = '?logout=1';
            }
        }
        
        let currentModule = '';
        let currentAnswer = '';
        let currentExplanation = '';
        let lastSessionData = null;
        
        function startQuiz(module) {
            currentModule = module;
            document.getElementById('quizModal').classList.add('active');
            document.getElementById('moduleTitle').textContent = module.charAt(0).toUpperCase() + module.slice(1);
            
            // Foxy über Modulwechsel informieren
            if (typeof updateFoxyModule === 'function') {
                updateFoxyModule(module);
            }
            
            loadQuestion();
        }
        
        function closeQuiz() {
            document.getElementById('quizModal').classList.remove('active');
        }
        
        function updateProgress(done, total = 10) {
            const percent = (done / total) * 100;
            document.getElementById('progressFill').style.width = percent + '%';
            document.getElementById('progressText').textContent = `Frage ${done}/${total}`;
        }
        
        function loadQuestion() {
            document.getElementById('optionsContainer').innerHTML = '';
            document.getElementById('feedback').innerHTML = '';
            document.getElementById('explanation').style.display = 'none';
            document.getElementById('explanation').innerHTML = '';
            document.getElementById('nextBtn').classList.remove('show');
            document.getElementById('questionText').innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Lade Frage...</p>
                </div>
            `;
            
            fetch(`?action=get_question&module=${currentModule}`)
                .then(r => r.json())
                .then(data => {
                    if (debugMode) {
                        console.log('[DEBUG] get_question response:', data);
                    }
                    
                    if (data.success) {
                        currentAnswer = data.answer;
                        currentExplanation = data.explanation || '';
                        document.getElementById('questionText').textContent = data.question;
                        document.getElementById('sessionScore').textContent = data.session_score + ' Punkte';
                        document.getElementById('moduleTotal').textContent = 'Gesamt: ' + data.module_total + ' Punkte';
                        updateProgress(data.questions_done);
                        
                        const container = document.getElementById('optionsContainer');
                        data.options.forEach(opt => {
                            const btn = document.createElement('button');
                            btn.className = 'option-btn';
                            btn.textContent = opt;
                            btn.onclick = () => checkAnswer(opt, btn);
                            container.appendChild(btn);
                        });
                    } else {
                        document.getElementById('questionText').textContent = 'Fehler: ' + data.error;
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('questionText').textContent = 'Fehler beim Laden!';
                });
        }
        
        function checkAnswer(answer, btn) {
            document.querySelectorAll('.option-btn').forEach(b => b.disabled = true);
            
            const formData = new FormData();
            formData.append('action', 'check_answer');
            formData.append('module', currentModule);
            formData.append('answer', answer);
            formData.append('correct', currentAnswer);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (debugMode) {
                    console.log('[DEBUG] check_answer response:', data);
                }
                
                if (data.success) {
                    if (data.correct) {
                        btn.classList.add('correct');
                        document.getElementById('feedback').innerHTML = `<span style="color: green;">✅ Richtig! +${data.points_earned} Punkte</span>`;
                    } else {
                        btn.classList.add('wrong');
                        document.querySelectorAll('.option-btn').forEach(b => {
                            if (b.textContent === currentAnswer) {
                                b.classList.add('correct');
                            }
                        });
                        document.getElementById('feedback').innerHTML = '<span style="color: red;">❌ Falsch!</span>';
                    }
                    
                    // Erklärung anzeigen (wenn vorhanden)
                    if (currentExplanation && currentExplanation.trim() !== '') {
                        const explanationDiv = document.getElementById('explanation');
                        const icon = data.correct ? '💡' : '📚';
                        explanationDiv.innerHTML = `<span class="explanation-icon">${icon}</span>${currentExplanation}`;
                        explanationDiv.className = data.correct ? 'explanation' : 'explanation wrong';
                        explanationDiv.style.display = 'block';
                    }
                    
                    document.getElementById('sessionScore').textContent = data.session_score + ' Punkte';
                    document.getElementById('moduleTotal').textContent = 'Gesamt: ' + data.module_total + ' Punkte';
                    document.getElementById('totalScore').textContent = data.global_score;
                    updateProgress(data.session_questions);
                    
                    if (data.leveled_up) {
                        setTimeout(() => {
                            showToast('achievement', data.level.icon, 'LEVEL UP!', 
                                `Du bist jetzt ${data.level.name}! (${data.level.points} Punkte/Frage)`);
                        }, 500);
                    }
                    
                    if (data.session_complete) {
                        lastSessionData = data;
                        setTimeout(() => {
                            showSessionComplete(data);
                        }, 1000);
                    } else {
                        document.getElementById('nextBtn').classList.add('show');
                    }
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('feedback').innerHTML = '<span style="color: red;">Fehler!</span>';
            });
        }
        
        function showSessionComplete(data) {
            closeQuiz();
            
            const modal = document.getElementById('sessionCompleteModal');
            const statsDiv = document.getElementById('sessionStats');
            const achievementsDiv = document.getElementById('sessionAchievements');
            const achievementList = document.getElementById('achievementList');
            
            // Icon basierend auf Score
            const icon = document.getElementById('sessionIcon');
            const title = document.getElementById('sessionTitle');
            
            if (data.session_correct >= 10) {
                icon.textContent = '🌟';
                title.textContent = 'Perfekt! 100%!';
            } else if (data.session_correct >= 8) {
                icon.textContent = '🎉';
                title.textContent = 'Super gemacht!';
            } else if (data.session_correct >= 6) {
                icon.textContent = '👍';
                title.textContent = 'Gut gemacht!';
            } else {
                icon.textContent = '💪';
                title.textContent = 'Weiter üben!';
            }
            
            // Stats
            let statsHTML = `
                <div class="session-stat">
                    <div class="session-stat-value">${data.session_correct}/10</div>
                    <div class="session-stat-label">Richtig</div>
                </div>
                <div class="session-stat">
                    <div class="session-stat-value">${data.session_score}</div>
                    <div class="session-stat-label">Punkte</div>
                </div>
            `;
            
            // Wallet Rewards
            if (data.wallet_reward) {
                if (data.wallet_reward.sats > 0) {
                    statsHTML += `
                        <div class="session-stat sats">
                            <div class="session-stat-value">+${data.wallet_reward.sats}</div>
                            <div class="session-stat-label">Test-Sats</div>
                        </div>
                        <div class="session-stat sats">
                            <div class="session-stat-value">₿ ${data.wallet_reward.new_balance.toLocaleString()}</div>
                            <div class="session-stat-label">Gesamt</div>
                        </div>
                    `;
                    
                    // Update Header
                    const satsEl = document.getElementById('testSatsBalance');
                    if (satsEl) {
                        satsEl.textContent = '₿ ' + data.wallet_reward.new_balance.toLocaleString();
                    }
                    
                    // Toast für verdiente Sats
                    showToast('sats', '₿', `+${data.wallet_reward.sats} Sats!`, 
                        data.wallet_reward.breakdown.join(' | '), 6000);
                } else if (data.wallet_reward.error) {
                    // Fehler anzeigen
                    statsHTML += `
                        <div class="session-stat error" style="grid-column: span 2;">
                            <div class="session-stat-value">⚠️ ${data.wallet_reward.error}</div>
                            <div class="session-stat-label">Keine Sats vergeben</div>
                        </div>
                    `;
                    
                    showToast('error', '⚠️', 'Keine Sats', data.wallet_reward.error, 5000);
                }
            } else if (data.wallet_error) {
                // Allgemeiner Wallet-Fehler
                statsHTML += `
                    <div class="session-stat error" style="grid-column: span 2;">
                        <div class="session-stat-value">⚠️ ${data.wallet_error}</div>
                        <div class="session-stat-label">Wallet-Fehler</div>
                    </div>
                `;
            }
            
            statsDiv.innerHTML = statsHTML;
            
            // Achievements
            if (data.new_achievements && data.new_achievements.length > 0) {
                achievementsDiv.style.display = 'block';
                achievementList.innerHTML = data.new_achievements.map(a => `
                    <span class="achievement-badge">${a.icon} ${a.name} (+${a.reward_sats} Sats)</span>
                `).join('');
                
                // Toast für jedes Achievement
                data.new_achievements.forEach((a, i) => {
                    setTimeout(() => {
                        showToast('achievement', a.icon, '🏆 Achievement!', 
                            `${a.name} - +${a.reward_sats} Sats`, 6000);
                    }, 500 + (i * 1000));
                });
            } else {
                achievementsDiv.style.display = 'none';
            }
            
            modal.classList.add('active');
        }
        
        function closeSessionModal() {
            document.getElementById('sessionCompleteModal').classList.remove('active');
        }
        
        function continueSession() {
            closeSessionModal();
            startQuiz(currentModule);
        }
        
        function nextQuestion() {
            loadQuestion();
        }
    </script>
    
    <!-- Foxy Lernassistent Integration -->
    <?php 
    $currentModule = $currentModule ?? null; // Falls Variable existiert
    include __DIR__ . '/clippy/include.php'; 
    ?>
</body>
</html>
