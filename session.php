<?php
/**
 * sgiT Education Platform - Enhanced Session Management
 * @version 2.0
 */

require_once dirname(__FILE__) . '/config.php';
initSession();

/**
 * Session-Tracking für Fragen pro Modul
 */
function initQuestionSession($module) {
    if (!isset($_SESSION['current_session'][$module])) {
        $_SESSION['current_session'][$module] = [
            'questions_answered' => 0,
            'correct_answers' => 0,
            'wrong_answers' => 0,
            'start_time' => time(),
            'questions' => []
        ];
    }
}

/**
 * Reset Question Session für neuen Durchgang
 */
function resetQuestionSession($module) {
    $_SESSION['current_session'][$module] = [
        'questions_answered' => 0,
        'correct_answers' => 0,
        'wrong_answers' => 0,
        'start_time' => time(),
        'questions' => []
    ];
}

/**
 * Prüft ob Session-Limit erreicht (10 Fragen)
 */
function isSessionComplete($module) {
    return isset($_SESSION['current_session'][$module]['questions_answered']) && 
           $_SESSION['current_session'][$module]['questions_answered'] >= 10;
}

/**
 * Fügt beantwortete Frage hinzu
 */
function addAnsweredQuestion($module, $correct) {
    initQuestionSession($module);
    $_SESSION['current_session'][$module]['questions_answered']++;
    if ($correct) {
        $_SESSION['current_session'][$module]['correct_answers']++;
    } else {
        $_SESSION['current_session'][$module]['wrong_answers']++;
    }
}

/**
 * Holt Session-Statistik
 */
function getSessionStats($module) {
    if (!isset($_SESSION['current_session'][$module])) {
        initQuestionSession($module);
    }
    
    $session = $_SESSION['current_session'][$module];
    $duration = time() - $session['start_time'];
    
    return [
        'total' => $session['questions_answered'],
        'correct' => $session['correct_answers'],
        'wrong' => $session['wrong_answers'],
        'percentage' => $session['questions_answered'] > 0 
            ? round(($session['correct_answers'] / $session['questions_answered']) * 100) 
            : 0,
        'duration' => $duration,
        'points_earned' => $session['correct_answers'] * POINTS_PER_ANSWER
    ];
}

function isLoggedIn() {
    return isset($_SESSION['username']) && !empty($_SESSION['username']);
}

function getUsername() {
    return $_SESSION['username'] ?? '';
}

function getUserAge() {
    return $_SESSION['user_age'] ?? DEFAULT_AGE;
}

function loginUser($username, $age = DEFAULT_AGE) {
    $_SESSION['username'] = htmlspecialchars($username);
    $_SESSION['user_age'] = intval($age);
    
    // Initialisiere alle Module
    if (!isset($_SESSION['scores'])) {
        $_SESSION['scores'] = [
            'math' => 0,
            'reading' => 0,
            'science' => 0,
            'geography' => 0,
            'english' => 0,
            'chemistry' => 0,
            'physics' => 0,
            'art' => 0,
            'music' => 0,
            'computer' => 0,
            'bitcoin' => 0,
            'history' => 0,
            'biology' => 0,
            'taxes' => 0
        ];
    }
    
    if (!isset($_SESSION['current_session'])) {
        $_SESSION['current_session'] = [];
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
}

function getScore($subject) {
    return $_SESSION['scores'][$subject] ?? 0;
}

function addScore($subject, $points = POINTS_PER_ANSWER) {
    if (!isset($_SESSION['scores'][$subject])) {
        $_SESSION['scores'][$subject] = 0;
    }
    $_SESSION['scores'][$subject] += $points;
}

function getTotalScore() {
    return array_sum($_SESSION['scores'] ?? []);
}

function getStreak() {
    return $_SESSION['streak'] ?? 0;
}

function increaseStreak() {
    $_SESSION['streak'] = getStreak() + 1;
    if (!isset($_SESSION['best_streak']) || $_SESSION['streak'] > $_SESSION['best_streak']) {
        $_SESSION['best_streak'] = $_SESSION['streak'];
    }
}

function resetStreak() {
    $_SESSION['streak'] = 0;
}

function saveStats($module, $stats) {
    if (!isset($_SESSION['statistics'])) {
        $_SESSION['statistics'] = [];
    }
    
    if (!isset($_SESSION['statistics'][$module])) {
        $_SESSION['statistics'][$module] = [
            'total_sessions' => 0,
            'total_questions' => 0,
            'total_correct' => 0,
            'best_session' => 0
        ];
    }
    
    $_SESSION['statistics'][$module]['total_sessions']++;
    $_SESSION['statistics'][$module]['total_questions'] += $stats['total'];
    $_SESSION['statistics'][$module]['total_correct'] += $stats['correct'];
    
    if ($stats['percentage'] > $_SESSION['statistics'][$module]['best_session']) {
        $_SESSION['statistics'][$module]['best_session'] = $stats['percentage'];
    }
}
?>
