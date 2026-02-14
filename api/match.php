<?php
/**
 * ============================================================================
 * sgiT Education Platform - Multiplayer Match API
 * ============================================================================
 * 
 * API für das LAN-Multiplayer Quiz System
 * 
 * Endpoints:
 * - POST create        → Match erstellen (gibt Code zurück)
 * - POST join          → Match beitreten (mit Code)
 * - GET  status        → Match-Status + Spieler + aktuelle Frage
 * - POST ready         → Spieler als bereit markieren
 * - POST start         → Match starten (nur Host)
 * - POST answer        → Antwort abgeben
 * - POST leave         → Match verlassen
 * - GET  history       → Match-History des Users
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 2025-12-12
 */

require_once __DIR__ . '/_security.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
require_once __DIR__ . '/../wallet/SessionManager.php';

// ================================================================
// Konfiguration
// ================================================================
$config = [
    'code_length' => 6,
    'max_players_1v1' => 2,
    'max_players_2v2' => 4,
    'default_questions' => 10,
    'default_time' => 15,
    'min_bet' => 0,
    'max_bet' => 100,
    'points_correct' => 100,
    'points_speed_bonus' => 50,  // Max Bonus für schnelle Antwort
    'elo_k_factor' => 32,
];

// Wallet-User prüfen über SessionManager
$userId = null;

// Prüfe SessionManager zuerst (verwendet sgit_child_id)
if (SessionManager::isLoggedIn()) {
    $childData = SessionManager::getChild();
    if ($childData) {
        $userId = $childData['id'];
        // Sync in Standard-Session
        $_SESSION['wallet_child_id'] = $childData['id'];
    }
}
// Fallback: Standard Session-Key
if (!$userId && isset($_SESSION['wallet_child_id'])) {
    $userId = $_SESSION['wallet_child_id'];
}

if (!$userId) {
    jsonResponse(false, 'Nicht eingeloggt', 'NOT_LOGGED_IN');
}

// Datenbanken verbinden
$walletDb = connectDb(__DIR__ . '/../wallet/wallet.db');
$questionsDb = connectDb(__DIR__ . '/../AI/data/questions.db');

// Action bestimmen
$action = $_GET['action'] ?? $_POST['action'] ?? json_decode(file_get_contents('php://input'), true)['action'] ?? 'status';

// ================================================================
// Hilfsfunktionen
// ================================================================

function connectDb(string $path): PDO {
    if (!file_exists($path)) {
        jsonResponse(false, "DB nicht gefunden: $path");
    }
    $db = new PDO("sqlite:$path");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

function jsonResponse(bool $success, string $message = '', string $code = '', array $data = []): void {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
        'code' => $code,
    ], $data));
    exit;
}

function generateMatchCode(int $length = 6): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Ohne I,O,0,1 (Verwechslungsgefahr)
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function getMatchByCode(PDO $db, string $code): ?array {
    $stmt = $db->prepare("SELECT * FROM matches WHERE match_code = ?");
    $stmt->execute([$code]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function getMatchById(PDO $db, int $id): ?array {
    $stmt = $db->prepare("SELECT * FROM matches WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function getMatchPlayers(PDO $db, int $matchId): array {
    $stmt = $db->prepare("
        SELECT mp.*, cw.child_name, cw.avatar, cw.elo_rating
        FROM match_players mp
        JOIN child_wallets cw ON mp.player_id = cw.id
        WHERE mp.match_id = ?
        ORDER BY mp.team, mp.joined_at
    ");
    $stmt->execute([$matchId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserPlayer(PDO $db, int $matchId, int $userId): ?array {
    $stmt = $db->prepare("SELECT * FROM match_players WHERE match_id = ? AND player_id = ?");
    $stmt->execute([$matchId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function getCurrentQuestion(PDO $db, int $matchId, int $index): ?array {
    $stmt = $db->prepare("SELECT * FROM match_questions WHERE match_id = ? AND question_index = ?");
    $stmt->execute([$matchId, $index]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ================================================================
// Action Router
// ================================================================

switch ($action) {

// ================================================================
// CREATE: Neues Match erstellen
// ================================================================
case 'create':
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $matchType = $input['match_type'] ?? '1v1';
    $module = $input['module'] ?? 'mathematik';
    $questions = min(20, max(5, (int)($input['questions'] ?? $config['default_questions'])));
    $timePerQuestion = min(30, max(5, (int)($input['time'] ?? $config['default_time'])));
    $satsBet = min($config['max_bet'], max($config['min_bet'], (int)($input['sats_bet'] ?? 0)));
    
    // Validate match type
    if (!in_array($matchType, ['1v1', '2v2', 'coop'])) {
        jsonResponse(false, 'Ungültiger Match-Typ', 'INVALID_TYPE');
    }
    
    // Prüfen ob User genug Sats hat
    if ($satsBet > 0) {
        $stmt = $walletDb->prepare("SELECT balance_sats FROM child_wallets WHERE id = ?");
        $stmt->execute([$userId]);
        $balance = $stmt->fetchColumn();
        if ($balance < $satsBet) {
            jsonResponse(false, 'Nicht genug Sats', 'INSUFFICIENT_SATS', ['balance' => $balance]);
        }
    }
    
    // Einzigartigen Code generieren
    do {
        $code = generateMatchCode($config['code_length']);
        $existing = getMatchByCode($walletDb, $code);
    } while ($existing);
    
    // Match erstellen
    $stmt = $walletDb->prepare("
        INSERT INTO matches (match_code, match_type, module, questions_total, time_per_question, sats_bet, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$code, $matchType, $module, $questions, $timePerQuestion, $satsBet, $userId]);
    $matchId = $walletDb->lastInsertId();
    
    // Host als ersten Spieler hinzufügen
    $stmt = $walletDb->prepare("
        INSERT INTO match_players (match_id, player_id, team, is_host, is_ready)
        VALUES (?, ?, 1, 1, 0)
    ");
    $stmt->execute([$matchId, $userId]);
    
    // Sats-Einsatz abziehen
    if ($satsBet > 0) {
        $walletDb->prepare("UPDATE child_wallets SET balance_sats = balance_sats - ? WHERE id = ?")
                 ->execute([$satsBet, $userId]);
        $walletDb->prepare("UPDATE matches SET sats_pool = sats_pool + ? WHERE id = ?")
                 ->execute([$satsBet, $matchId]);
    }
    
    jsonResponse(true, 'Match erstellt', 'CREATED', [
        'match_id' => $matchId,
        'match_code' => $code,
        'match_type' => $matchType,
        'module' => $module,
    ]);
    break;

// ================================================================
// JOIN: Match beitreten
// ================================================================
case 'join':
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $code = strtoupper(trim($input['code'] ?? ''));
    
    if (strlen($code) !== $config['code_length']) {
        jsonResponse(false, 'Ungültiger Code', 'INVALID_CODE');
    }
    
    $match = getMatchByCode($walletDb, $code);
    if (!$match) {
        jsonResponse(false, 'Match nicht gefunden', 'NOT_FOUND');
    }
    
    if ($match['status'] !== 'waiting') {
        jsonResponse(false, 'Match bereits gestartet oder beendet', 'MATCH_NOT_WAITING');
    }
    
    // Prüfen ob schon dabei
    $existing = getUserPlayer($walletDb, $match['id'], $userId);
    if ($existing) {
        jsonResponse(true, 'Bereits im Match', 'ALREADY_JOINED', [
            'match_id' => $match['id'],
            'match_code' => $code,
        ]);
    }
    
    // Max Spieler prüfen
    $players = getMatchPlayers($walletDb, $match['id']);
    $maxPlayers = $match['match_type'] === '2v2' ? $config['max_players_2v2'] : $config['max_players_1v1'];
    
    if (count($players) >= $maxPlayers) {
        jsonResponse(false, 'Match ist voll', 'MATCH_FULL');
    }
    
    // Sats-Einsatz prüfen
    if ($match['sats_bet'] > 0) {
        $stmt = $walletDb->prepare("SELECT balance_sats FROM child_wallets WHERE id = ?");
        $stmt->execute([$userId]);
        $balance = $stmt->fetchColumn();
        if ($balance < $match['sats_bet']) {
            jsonResponse(false, 'Nicht genug Sats für Einsatz', 'INSUFFICIENT_SATS', [
                'required' => $match['sats_bet'],
                'balance' => $balance
            ]);
        }
    }
    
    // Team zuweisen (bei 2v2 auf Team 2 wenn Team 1 voll)
    $team = 1;
    if ($match['match_type'] === '2v2') {
        $team1Count = count(array_filter($players, fn($p) => $p['team'] === 1));
        if ($team1Count >= 2) {
            $team = 2;
        }
    } elseif ($match['match_type'] === '1v1') {
        $team = 2; // Gegner ist immer Team 2
    }
    
    // Beitreten
    $stmt = $walletDb->prepare("
        INSERT INTO match_players (match_id, player_id, team, is_host, is_ready)
        VALUES (?, ?, ?, 0, 0)
    ");
    $stmt->execute([$match['id'], $userId, $team]);
    
    // Sats-Einsatz abziehen
    if ($match['sats_bet'] > 0) {
        $walletDb->prepare("UPDATE child_wallets SET balance_sats = balance_sats - ? WHERE id = ?")
                 ->execute([$match['sats_bet'], $userId]);
        $walletDb->prepare("UPDATE matches SET sats_pool = sats_pool + ? WHERE id = ?")
                 ->execute([$match['sats_bet'], $match['id']]);
    }
    
    jsonResponse(true, 'Match beigetreten', 'JOINED', [
        'match_id' => $match['id'],
        'match_code' => $code,
        'team' => $team,
    ]);
    break;

// ================================================================
// STATUS: Match-Status abrufen (für Polling)
// ================================================================
case 'status':
    $matchId = (int)($_GET['match_id'] ?? 0);
    $code = strtoupper($_GET['code'] ?? '');
    
    if ($matchId) {
        $match = getMatchById($walletDb, $matchId);
    } elseif ($code) {
        $match = getMatchByCode($walletDb, $code);
    } else {
        jsonResponse(false, 'match_id oder code erforderlich', 'MISSING_PARAM');
    }
    
    if (!$match) {
        jsonResponse(false, 'Match nicht gefunden', 'NOT_FOUND');
    }
    
    $players = getMatchPlayers($walletDb, $match['id']);
    $userPlayer = getUserPlayer($walletDb, $match['id'], $userId);
    
    // Aktuelle Frage (wenn Match läuft)
    $currentQuestion = null;
    $answers = [];
    if ($match['status'] === 'running' && $match['current_question'] > 0) {
        $q = getCurrentQuestion($walletDb, $match['id'], $match['current_question']);
        if ($q) {
            // Optionen mischen
            $options = [$q['option_a'], $q['option_b'], $q['option_c'], $q['option_d']];
            shuffle($options);
            
            $currentQuestion = [
                'index' => $q['question_index'],
                'question' => $q['question_text'],
                'options' => $options,
            ];
        }
        
        // Wer hat schon geantwortet?
        $stmt = $walletDb->prepare("
            SELECT player_id, is_correct, points_earned 
            FROM match_answers 
            WHERE match_id = ? AND question_index = ?
        ");
        $stmt->execute([$match['id'], $match['current_question']]);
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Scores berechnen
    $scores = [];
    foreach ($players as $p) {
        $scores[$p['player_id']] = [
            'name' => $p['child_name'],
            'avatar' => $p['avatar'],
            'score' => $p['score'],
            'team' => $p['team'],
            'is_ready' => (bool)$p['is_ready'],
        ];
    }
    
    jsonResponse(true, '', '', [
        'match' => [
            'id' => $match['id'],
            'code' => $match['match_code'],
            'type' => $match['match_type'],
            'module' => $match['module'],
            'status' => $match['status'],
            'questions_total' => $match['questions_total'],
            'current_question' => $match['current_question'],
            'time_per_question' => $match['time_per_question'],
            'sats_pool' => $match['sats_pool'],
            'sats_bet' => $match['sats_bet'],
            'winner_id' => $match['winner_id'],
            'winner_team' => $match['winner_team'],
        ],
        'players' => $players,
        'scores' => $scores,
        'current_question' => $currentQuestion,
        'answers_given' => $answers,
        'user_player' => $userPlayer,
        'is_host' => $userPlayer && $userPlayer['is_host'],
    ]);
    break;

// ================================================================
// READY: Spieler als bereit markieren
// ================================================================
case 'ready':
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $matchId = (int)($input['match_id'] ?? 0);
    
    $match = getMatchById($walletDb, $matchId);
    if (!$match) {
        jsonResponse(false, 'Match nicht gefunden', 'NOT_FOUND');
    }
    
    if ($match['status'] !== 'waiting') {
        jsonResponse(false, 'Match nicht im Wartezustand', 'INVALID_STATUS');
    }
    
    $player = getUserPlayer($walletDb, $matchId, $userId);
    if (!$player) {
        jsonResponse(false, 'Nicht im Match', 'NOT_IN_MATCH');
    }
    
    // Toggle ready status
    $newReady = $player['is_ready'] ? 0 : 1;
    $walletDb->prepare("UPDATE match_players SET is_ready = ? WHERE id = ?")
             ->execute([$newReady, $player['id']]);
    
    // Prüfen ob alle bereit sind
    $players = getMatchPlayers($walletDb, $matchId);
    $allReady = count($players) >= 2 && count(array_filter($players, fn($p) => $p['is_ready'] || $p['player_id'] == $userId && $newReady)) === count($players);
    
    if ($allReady) {
        $walletDb->prepare("UPDATE matches SET status = 'ready' WHERE id = ?")
                 ->execute([$matchId]);
    }
    
    jsonResponse(true, $newReady ? 'Bereit!' : 'Nicht mehr bereit', '', [
        'is_ready' => (bool)$newReady,
        'all_ready' => $allReady,
    ]);
    break;

// ================================================================
// START: Match starten (nur Host)
// ================================================================
case 'start':
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $matchId = (int)($input['match_id'] ?? 0);
    
    $match = getMatchById($walletDb, $matchId);
    if (!$match) {
        jsonResponse(false, 'Match nicht gefunden', 'NOT_FOUND');
    }
    
    $player = getUserPlayer($walletDb, $matchId, $userId);
    if (!$player || !$player['is_host']) {
        jsonResponse(false, 'Nur der Host kann starten', 'NOT_HOST');
    }
    
    if (!in_array($match['status'], ['waiting', 'ready'])) {
        jsonResponse(false, 'Match kann nicht gestartet werden', 'INVALID_STATUS');
    }
    
    $players = getMatchPlayers($walletDb, $matchId);
    if (count($players) < 2) {
        jsonResponse(false, 'Mindestens 2 Spieler erforderlich', 'NOT_ENOUGH_PLAYERS');
    }
    
    // Fragen aus der questions.db laden
    $module = $match['module'];
    $count = $match['questions_total'];
    
    $stmt = $questionsDb->prepare("
        SELECT id, question, answer, options
        FROM questions 
        WHERE module = ? AND is_active = 1
        ORDER BY RANDOM() 
        LIMIT ?
    ");
    $stmt->execute([$module, $count]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($questions) < $count) {
        jsonResponse(false, "Nicht genug Fragen im Modul '$module'", 'NOT_ENOUGH_QUESTIONS', [
            'available' => count($questions),
            'required' => $count
        ]);
    }
    
    // Fragen ins Match speichern
    $stmt = $walletDb->prepare("
        INSERT INTO match_questions (match_id, question_index, question_id, question_text, correct_answer, option_a, option_b, option_c, option_d)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($questions as $i => $q) {
        // Options ist ein JSON-Array, z.B. ["A","B","C","D"]
        $options = json_decode($q['options'], true) ?? [];
        
        // Sicherstellen dass wir 4 Optionen haben
        while (count($options) < 4) {
            $options[] = '';
        }
        
        $stmt->execute([
            $matchId,
            $i + 1,
            $q['id'],
            $q['question'],      // question statt question_text
            $q['answer'],        // answer statt correct_answer
            $options[0] ?? '',
            $options[1] ?? '',
            $options[2] ?? '',
            $options[3] ?? ''
        ]);
    }
    
    // Match starten
    $walletDb->prepare("
        UPDATE matches 
        SET status = 'running', current_question = 1, started_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ")->execute([$matchId]);
    
    jsonResponse(true, 'Match gestartet!', 'STARTED', [
        'questions_loaded' => count($questions),
        'current_question' => 1,
    ]);
    break;

// ================================================================
// ANSWER: Antwort abgeben
// ================================================================
case 'answer':
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $matchId = (int)($input['match_id'] ?? 0);
    $answer = $input['answer'] ?? '';
    $timeTaken = (int)($input['time_taken_ms'] ?? 0);
    $useJoker = (bool)($input['use_joker'] ?? false);
    
    $match = getMatchById($walletDb, $matchId);
    if (!$match || $match['status'] !== 'running') {
        jsonResponse(false, 'Match nicht aktiv', 'INVALID_MATCH');
    }
    
    $player = getUserPlayer($walletDb, $matchId, $userId);
    if (!$player) {
        jsonResponse(false, 'Nicht im Match', 'NOT_IN_MATCH');
    }
    
    $questionIndex = $match['current_question'];
    $question = getCurrentQuestion($walletDb, $matchId, $questionIndex);
    if (!$question) {
        jsonResponse(false, 'Keine aktive Frage', 'NO_QUESTION');
    }
    
    // Prüfen ob schon geantwortet
    $stmt = $walletDb->prepare("SELECT id FROM match_answers WHERE match_id = ? AND player_id = ? AND question_index = ?");
    $stmt->execute([$matchId, $userId, $questionIndex]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Bereits geantwortet', 'ALREADY_ANSWERED');
    }
    
    // Joker verwenden?
    if ($useJoker && $player['joker_used'] === 0) {
        // Joker aus User-Konto prüfen
        $stmt = $walletDb->prepare("SELECT joker_count FROM child_wallets WHERE id = ?");
        $stmt->execute([$userId]);
        $jokerCount = $stmt->fetchColumn();
        
        if ($jokerCount > 0) {
            $walletDb->prepare("UPDATE child_wallets SET joker_count = joker_count - 1 WHERE id = ?")
                     ->execute([$userId]);
            $walletDb->prepare("UPDATE match_players SET joker_used = 1 WHERE id = ?")
                     ->execute([$player['id']]);
        } else {
            $useJoker = false;
        }
    }
    
    // Antwort prüfen
    $isCorrect = ($answer === $question['correct_answer']) ? 1 : 0;
    
    // Punkte berechnen (schneller = mehr Punkte)
    $points = 0;
    if ($isCorrect) {
        $points = $config['points_correct'];
        // Speed-Bonus: Max 50 Punkte bei sofortiger Antwort, 0 bei Timeout
        $maxTime = $match['time_per_question'] * 1000;
        $speedBonus = (int)(($maxTime - $timeTaken) / $maxTime * $config['points_speed_bonus']);
        $points += max(0, $speedBonus);
    }
    
    // Antwort speichern
    $stmt = $walletDb->prepare("
        INSERT INTO match_answers (match_id, player_id, question_index, question_id, answer_given, correct_answer, is_correct, time_taken_ms, points_earned)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$matchId, $userId, $questionIndex, $question['question_id'], $answer, $question['correct_answer'], $isCorrect, $timeTaken, $points]);
    
    // Spieler-Score aktualisieren
    $walletDb->prepare("
        UPDATE match_players 
        SET score = score + ?, 
            correct_answers = correct_answers + ?,
            total_time_ms = total_time_ms + ?
        WHERE id = ?
    ")->execute([$points, $isCorrect, $timeTaken, $player['id']]);
    
    // Prüfen ob alle geantwortet haben
    $players = getMatchPlayers($walletDb, $matchId);
    $stmt = $walletDb->prepare("SELECT COUNT(*) FROM match_answers WHERE match_id = ? AND question_index = ?");
    $stmt->execute([$matchId, $questionIndex]);
    $answersCount = $stmt->fetchColumn();
    
    $allAnswered = ($answersCount >= count($players));
    $isLastQuestion = ($questionIndex >= $match['questions_total']);
    $nextQuestion = null;
    $matchFinished = false;
    
    if ($allAnswered) {
        if ($isLastQuestion) {
            // Match beenden
            $matchFinished = true;
            finishMatch($walletDb, $match, $players, $config);
        } else {
            // Nächste Frage
            $nextQuestionIndex = $questionIndex + 1;
            $walletDb->prepare("UPDATE matches SET current_question = ? WHERE id = ?")
                     ->execute([$nextQuestionIndex, $matchId]);
            $nextQuestion = $nextQuestionIndex;
        }
    }
    
    jsonResponse(true, $isCorrect ? 'Richtig!' : 'Falsch!', '', [
        'is_correct' => (bool)$isCorrect,
        'correct_answer' => $question['correct_answer'],
        'points_earned' => $points,
        'new_score' => $player['score'] + $points,
        'all_answered' => $allAnswered,
        'next_question' => $nextQuestion,
        'match_finished' => $matchFinished,
        'joker_used' => $useJoker,
    ]);
    break;

// ================================================================
// LEAVE: Match verlassen
// ================================================================
case 'leave':
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $matchId = (int)($input['match_id'] ?? 0);
    
    $match = getMatchById($walletDb, $matchId);
    if (!$match) {
        jsonResponse(false, 'Match nicht gefunden', 'NOT_FOUND');
    }
    
    $player = getUserPlayer($walletDb, $matchId, $userId);
    if (!$player) {
        jsonResponse(false, 'Nicht im Match', 'NOT_IN_MATCH');
    }
    
    // Bei laufendem Match: Sats verloren, Niederlage
    if ($match['status'] === 'running') {
        $walletDb->prepare("UPDATE child_wallets SET matches_lost = matches_lost + 1 WHERE id = ?")
                 ->execute([$userId]);
    } else {
        // Sats zurückgeben wenn noch nicht gestartet
        if ($match['sats_bet'] > 0) {
            $walletDb->prepare("UPDATE child_wallets SET balance_sats = balance_sats + ? WHERE id = ?")
                     ->execute([$match['sats_bet'], $userId]);
            $walletDb->prepare("UPDATE matches SET sats_pool = sats_pool - ? WHERE id = ?")
                     ->execute([$match['sats_bet'], $matchId]);
        }
    }
    
    // Spieler entfernen
    $walletDb->prepare("DELETE FROM match_players WHERE id = ?")->execute([$player['id']]);
    
    // Match abbrechen wenn nur noch 1 Spieler
    $remaining = getMatchPlayers($walletDb, $matchId);
    if (count($remaining) < 2 && $match['status'] === 'running') {
        $walletDb->prepare("UPDATE matches SET status = 'cancelled' WHERE id = ?")->execute([$matchId]);
    }
    
    jsonResponse(true, 'Match verlassen', 'LEFT');
    break;

// ================================================================
// HISTORY: Match-History des Users
// ================================================================
case 'history':
    $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
    
    $stmt = $walletDb->prepare("
        SELECT m.*, mp.score, mp.team,
               (SELECT child_name FROM child_wallets WHERE id = m.winner_id) as winner_name
        FROM matches m
        JOIN match_players mp ON m.id = mp.match_id
        WHERE mp.player_id = ? AND m.status = 'finished'
        ORDER BY m.finished_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistiken
    $stmt = $walletDb->prepare("
        SELECT matches_played, matches_won, matches_lost, matches_draw, elo_rating, elo_peak
        FROM child_wallets WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    jsonResponse(true, '', '', [
        'matches' => $matches,
        'stats' => $stats,
    ]);
    break;

// ================================================================
// DEFAULT
// ================================================================
default:
    jsonResponse(false, "Unbekannte Action: $action", 'UNKNOWN_ACTION');
}

// ================================================================
// Hilfsfunktion: Match beenden & Rewards verteilen
// ================================================================
function finishMatch(PDO $db, array $match, array $players, array $config): void {
    $matchId = $match['id'];
    
    // Scores nach Team gruppieren
    $teamScores = [1 => 0, 2 => 0];
    $teamPlayers = [1 => [], 2 => []];
    
    foreach ($players as $p) {
        $teamScores[$p['team']] += $p['score'];
        $teamPlayers[$p['team']][] = $p;
    }
    
    // Gewinner ermitteln
    $winnerId = null;
    $winnerTeam = null;
    $isDraw = false;
    
    if ($match['match_type'] === 'coop') {
        // Coop: Alle gewinnen zusammen
        $winnerId = null;
        $winnerTeam = null;
    } elseif ($teamScores[1] > $teamScores[2]) {
        $winnerTeam = 1;
        $winnerId = $teamPlayers[1][0]['player_id'] ?? null;
    } elseif ($teamScores[2] > $teamScores[1]) {
        $winnerTeam = 2;
        $winnerId = $teamPlayers[2][0]['player_id'] ?? null;
    } else {
        $isDraw = true;
    }
    
    // Match als beendet markieren
    $db->prepare("
        UPDATE matches 
        SET status = 'finished', winner_id = ?, winner_team = ?, finished_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ")->execute([$winnerId, $winnerTeam, $matchId]);
    
    // Sats verteilen
    $satsPool = $match['sats_pool'];
    if ($satsPool > 0) {
        if ($isDraw) {
            // Bei Unentschieden: Einsatz zurück
            foreach ($players as $p) {
                $db->prepare("UPDATE child_wallets SET balance_sats = balance_sats + ? WHERE id = ?")
                   ->execute([$match['sats_bet'], $p['player_id']]);
            }
        } elseif ($match['match_type'] === 'coop') {
            // Coop: Pool gleichmäßig verteilen
            $share = (int)($satsPool / count($players));
            foreach ($players as $p) {
                $db->prepare("UPDATE child_wallets SET balance_sats = balance_sats + ? WHERE id = ?")
                   ->execute([$share, $p['player_id']]);
            }
        } else {
            // Gewinner-Team bekommt Pool
            $winners = $teamPlayers[$winnerTeam] ?? [];
            $share = count($winners) > 0 ? (int)($satsPool / count($winners)) : 0;
            foreach ($winners as $p) {
                $db->prepare("UPDATE child_wallets SET balance_sats = balance_sats + ? WHERE id = ?")
                   ->execute([$share, $p['player_id']]);
            }
        }
    }
    
    // Statistiken & Elo aktualisieren
    foreach ($players as $p) {
        $isWinner = ($p['team'] === $winnerTeam);
        $isLoser = !$isWinner && !$isDraw && $winnerTeam !== null;
        
        // Statistiken
        $db->prepare("
            UPDATE child_wallets SET 
                matches_played = matches_played + 1,
                matches_won = matches_won + ?,
                matches_lost = matches_lost + ?,
                matches_draw = matches_draw + ?
            WHERE id = ?
        ")->execute([
            $isWinner ? 1 : 0,
            $isLoser ? 1 : 0,
            $isDraw ? 1 : 0,
            $p['player_id']
        ]);
        
        // Elo-Berechnung (vereinfacht, 1v1)
        if ($match['match_type'] === '1v1' && count($players) === 2) {
            $opponent = $players[0]['player_id'] === $p['player_id'] ? $players[1] : $players[0];
            
            $myElo = $p['elo_rating'];
            $oppElo = $opponent['elo_rating'];
            
            // Expected Score
            $expected = 1 / (1 + pow(10, ($oppElo - $myElo) / 400));
            
            // Actual Score
            $actual = $isWinner ? 1 : ($isDraw ? 0.5 : 0);
            
            // Neues Elo
            $newElo = (int)round($myElo + $config['elo_k_factor'] * ($actual - $expected));
            $newElo = max(100, $newElo); // Minimum 100
            
            // Peak tracken
            $db->prepare("
                UPDATE child_wallets SET 
                    elo_rating = ?,
                    elo_peak = MAX(elo_peak, ?)
                WHERE id = ?
            ")->execute([$newElo, $newElo, $p['player_id']]);
        }
    }
}
