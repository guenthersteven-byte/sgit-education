<?php
/**
 * ============================================================================
 * sgiT Education - Montagsmaler API v1.0
 * ============================================================================
 * 
 * REST API für das Multiplayer Zeichen-Ratespiel
 * 
 * Endpoints:
 * - POST create    : Neues Spiel erstellen
 * - POST join      : Spiel beitreten
 * - GET  status    : Spielstatus abrufen
 * - POST draw      : Zeichnung aktualisieren
 * - POST guess     : Wort raten
 * - POST next      : Nächste Runde
 * - GET  words     : Wort-Kategorien abrufen
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Datenbank-Verbindung
$dbPath = dirname(__DIR__) . '/wallet/montagsmaler.db';
$db = new SQLite3($dbPath);
$db->busyTimeout(5000);
$db->exec('PRAGMA journal_mode=WAL');

// Tabellen erstellen falls nicht vorhanden
initDatabase($db);

// Request verarbeiten
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($action) {
        case 'create':
            echo json_encode(createGame($db, $input));
            break;
        case 'join':
            echo json_encode(joinGame($db, $input));
            break;
        case 'status':
            echo json_encode(getGameStatus($db, $_GET));
            break;
        case 'draw':
            echo json_encode(updateDrawing($db, $input));
            break;
        case 'guess':
            echo json_encode(submitGuess($db, $input));
            break;
        case 'next':
            echo json_encode(nextRound($db, $input));
            break;
        case 'words':
            echo json_encode(getWordCategories());
            break;
        case 'leave':
            echo json_encode(leaveGame($db, $input));
            break;
        default:
            echo json_encode(['error' => 'Unknown action', 'valid_actions' => 
                ['create', 'join', 'status', 'draw', 'guess', 'next', 'words', 'leave']]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$db->close();

/**
 * Datenbank initialisieren
 */
function initDatabase($db) {
    // Spiele-Tabelle
    $db->exec("CREATE TABLE IF NOT EXISTS games (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_code TEXT UNIQUE NOT NULL,
        host_id INTEGER NOT NULL,
        status TEXT DEFAULT 'waiting',
        current_round INTEGER DEFAULT 0,
        max_rounds INTEGER DEFAULT 5,
        round_time INTEGER DEFAULT 60,
        current_word TEXT,
        current_drawer_id INTEGER,
        drawing_data TEXT,
        round_start_time INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Spieler-Tabelle
    $db->exec("CREATE TABLE IF NOT EXISTS game_players (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_id INTEGER NOT NULL,
        wallet_child_id INTEGER,
        player_name TEXT NOT NULL,
        avatar TEXT DEFAULT '😀',
        score INTEGER DEFAULT 0,
        is_host INTEGER DEFAULT 0,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (game_id) REFERENCES games(id)
    )");
    
    // Raten-Tabelle
    $db->exec("CREATE TABLE IF NOT EXISTS guesses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_id INTEGER NOT NULL,
        round INTEGER NOT NULL,
        player_id INTEGER NOT NULL,
        guess_text TEXT NOT NULL,
        is_correct INTEGER DEFAULT 0,
        points_earned INTEGER DEFAULT 0,
        guessed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (game_id) REFERENCES games(id),
        FOREIGN KEY (player_id) REFERENCES game_players(id)
    )");
    
    // Indizes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_game_code ON games(game_code)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_game_players ON game_players(game_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_guesses_game ON guesses(game_id, round)");
}

/**
 * Wort-Kategorien mit altersgerechten Begriffen
 */
function getWordCategories() {
    return [
        'tiere' => [
            'name' => '🐾 Tiere',
            'easy' => ['Hund', 'Katze', 'Vogel', 'Fisch', 'Maus', 'Elefant', 'Löwe', 'Bär', 'Affe', 'Pinguin'],
            'medium' => ['Giraffe', 'Krokodil', 'Delfin', 'Schmetterling', 'Spinne', 'Schlange', 'Schildkröte', 'Eule'],
            'hard' => ['Chamäleon', 'Flamingo', 'Tintenfisch', 'Seepferdchen', 'Nashorn', 'Gepard']
        ],
        'essen' => [
            'name' => '🍕 Essen',
            'easy' => ['Pizza', 'Apfel', 'Banane', 'Eis', 'Kuchen', 'Brot', 'Käse', 'Ei', 'Salat', 'Burger'],
            'medium' => ['Spaghetti', 'Pommes', 'Sandwich', 'Schokolade', 'Karotte', 'Tomate', 'Brezel'],
            'hard' => ['Croissant', 'Sushi', 'Lasagne', 'Broccoli', 'Aubergine']
        ],
        'sport' => [
            'name' => '⚽ Sport',
            'easy' => ['Fußball', 'Tennis', 'Schwimmen', 'Laufen', 'Fahrrad', 'Basketball', 'Tanzen'],
            'medium' => ['Volleyball', 'Skateboard', 'Skifahren', 'Golf', 'Boxen', 'Reiten'],
            'hard' => ['Fechten', 'Bogenschießen', 'Surfen', 'Klettern', 'Segeln']
        ],
        'berufe' => [
            'name' => '👷 Berufe',
            'easy' => ['Arzt', 'Lehrer', 'Koch', 'Polizist', 'Feuerwehr', 'Bauer', 'Pilot'],
            'medium' => ['Astronaut', 'Mechaniker', 'Fotograf', 'Maler', 'Musiker', 'Clown'],
            'hard' => ['Archäologe', 'Taucher', 'Dirigent', 'Chirurg', 'Detektiv']
        ],
        'objekte' => [
            'name' => '🏠 Objekte',
            'easy' => ['Haus', 'Auto', 'Baum', 'Blume', 'Sonne', 'Mond', 'Stern', 'Herz', 'Wolke', 'Regenbogen'],
            'medium' => ['Flugzeug', 'Schiff', 'Rakete', 'Brille', 'Uhr', 'Schlüssel', 'Lampe', 'Telefon'],
            'hard' => ['Hubschrauber', 'Mikroskop', 'Globus', 'Kompass', 'Fernrohr']
        ],
        'aktionen' => [
            'name' => '🎬 Aktionen',
            'easy' => ['Schlafen', 'Essen', 'Trinken', 'Lachen', 'Weinen', 'Springen', 'Winken'],
            'medium' => ['Kochen', 'Putzen', 'Singen', 'Malen', 'Lesen', 'Schreiben', 'Träumen'],
            'hard' => ['Jonglieren', 'Zaubern', 'Meditieren', 'Komponieren']
        ]
    ];
}

/**
 * Zufälliges Wort basierend auf Alter auswählen
 */
function getRandomWord($age = 10, $category = null) {
    $categories = getWordCategories();
    
    // Schwierigkeit nach Alter
    if ($age <= 8) {
        $difficulty = 'easy';
    } elseif ($age <= 14) {
        $difficulty = 'medium';
    } else {
        $difficulty = 'hard';
    }
    
    // Zufällige Kategorie wenn nicht angegeben
    if (!$category || !isset($categories[$category])) {
        $category = array_rand($categories);
    }
    
    $words = $categories[$category][$difficulty];
    return $words[array_rand($words)];
}

/**
 * 6-stelligen Spielcode generieren
 */
function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * Neues Spiel erstellen
 */
function createGame($db, $input) {
    $playerName = $input['player_name'] ?? 'Spieler';
    $avatar = $input['avatar'] ?? '😀';
    $walletChildId = $input['wallet_child_id'] ?? null;
    $maxRounds = $input['max_rounds'] ?? 5;
    $roundTime = $input['round_time'] ?? 60;
    
    // Einzigartigen Code generieren
    do {
        $gameCode = generateGameCode();
        $exists = $db->querySingle("SELECT COUNT(*) FROM games WHERE game_code = '$gameCode'");
    } while ($exists > 0);
    
    // Spiel erstellen
    $stmt = $db->prepare("INSERT INTO games (game_code, host_id, max_rounds, round_time) VALUES (?, 0, ?, ?)");
    $stmt->bindValue(1, $gameCode);
    $stmt->bindValue(2, $maxRounds);
    $stmt->bindValue(3, $roundTime);
    $stmt->execute();
    
    $gameId = $db->lastInsertRowID();
    
    // Host als Spieler hinzufügen
    $stmt = $db->prepare("INSERT INTO game_players (game_id, wallet_child_id, player_name, avatar, is_host) VALUES (?, ?, ?, ?, 1)");
    $stmt->bindValue(1, $gameId);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->execute();
    
    $playerId = $db->lastInsertRowID();
    
    // Host-ID im Spiel setzen
    $db->exec("UPDATE games SET host_id = $playerId WHERE id = $gameId");
    
    return [
        'success' => true,
        'game_code' => $gameCode,
        'game_id' => $gameId,
        'player_id' => $playerId,
        'message' => 'Spiel erstellt! Teile den Code mit deinen Freunden.'
    ];
}

/**
 * Spiel beitreten
 */
function joinGame($db, $input) {
    $gameCode = strtoupper($input['game_code'] ?? '');
    $playerName = $input['player_name'] ?? 'Spieler';
    $avatar = $input['avatar'] ?? '😀';
    $walletChildId = $input['wallet_child_id'] ?? null;
    
    // Spiel suchen
    $game = $db->querySingle("SELECT id, status FROM games WHERE game_code = '$gameCode'", true);
    
    if (!$game) {
        return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    }
    
    if ($game['status'] !== 'waiting') {
        return ['success' => false, 'error' => 'Spiel hat bereits begonnen'];
    }
    
    // Prüfen ob bereits im Spiel
    $existingPlayer = $db->querySingle("SELECT id FROM game_players WHERE game_id = {$game['id']} AND player_name = '$playerName'");
    if ($existingPlayer) {
        return ['success' => true, 'game_id' => $game['id'], 'player_id' => $existingPlayer, 'message' => 'Willkommen zurück!'];
    }
    
    // Spieler hinzufügen
    $stmt = $db->prepare("INSERT INTO game_players (game_id, wallet_child_id, player_name, avatar) VALUES (?, ?, ?, ?)");
    $stmt->bindValue(1, $game['id']);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->execute();
    
    $playerId = $db->lastInsertRowID();
    
    return [
        'success' => true,
        'game_id' => $game['id'],
        'player_id' => $playerId,
        'message' => 'Erfolgreich beigetreten!'
    ];
}

/**
 * Spielstatus abrufen
 */
function getGameStatus($db, $params) {
    $gameId = $params['game_id'] ?? 0;
    $playerId = $params['player_id'] ?? 0;
    
    // Spielinfo
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game) {
        return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    }
    
    // Spieler abrufen
    $players = [];
    $result = $db->query("SELECT * FROM game_players WHERE game_id = $gameId ORDER BY score DESC, id ASC");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $players[] = $row;
    }
    
    // Letzte Rateversuche der aktuellen Runde
    $guesses = [];
    $result = $db->query("SELECT g.*, p.player_name, p.avatar FROM guesses g 
                          JOIN game_players p ON g.player_id = p.id 
                          WHERE g.game_id = $gameId AND g.round = {$game['current_round']} 
                          ORDER BY g.guessed_at DESC LIMIT 20");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $guesses[] = $row;
    }
    
    // Verbleibende Zeit berechnen
    $timeLeft = 0;
    if ($game['status'] === 'playing' && $game['round_start_time']) {
        $elapsed = time() - $game['round_start_time'];
        $timeLeft = max(0, $game['round_time'] - $elapsed);
    }
    
    // Wort nur für Zeichner sichtbar machen
    $currentWord = null;
    if ($game['current_drawer_id'] == $playerId) {
        $currentWord = $game['current_word'];
    }
    
    // Wort-Länge für alle (als Hinweis)
    $wordLength = $game['current_word'] ? strlen($game['current_word']) : 0;
    
    return [
        'success' => true,
        'game' => [
            'id' => $game['id'],
            'code' => $game['game_code'],
            'status' => $game['status'],
            'current_round' => $game['current_round'],
            'max_rounds' => $game['max_rounds'],
            'round_time' => $game['round_time'],
            'time_left' => $timeLeft,
            'current_drawer_id' => $game['current_drawer_id'],
            'word_length' => $wordLength,
            'current_word' => $currentWord,
            'drawing_data' => $game['drawing_data']
        ],
        'players' => $players,
        'guesses' => array_reverse($guesses),
        'is_drawer' => ($game['current_drawer_id'] == $playerId),
        'is_host' => ($game['host_id'] == $playerId)
    ];
}

/**
 * Zeichnung aktualisieren (für Zeichner)
 */
function updateDrawing($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $drawingData = $input['drawing_data'] ?? '';
    
    // Prüfen ob Spieler der Zeichner ist
    $game = $db->querySingle("SELECT current_drawer_id FROM games WHERE id = $gameId", true);
    if (!$game || $game['current_drawer_id'] != $playerId) {
        return ['success' => false, 'error' => 'Du bist nicht der Zeichner'];
    }
    
    // Zeichnung speichern
    $stmt = $db->prepare("UPDATE games SET drawing_data = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bindValue(1, $drawingData);
    $stmt->bindValue(2, $gameId);
    $stmt->execute();
    
    return ['success' => true];
}

/**
 * Wort raten
 */
function submitGuess($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $guessText = trim($input['guess'] ?? '');
    
    if (empty($guessText)) {
        return ['success' => false, 'error' => 'Kein Wort eingegeben'];
    }
    
    // Spielinfo abrufen
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing') {
        return ['success' => false, 'error' => 'Spiel läuft nicht'];
    }
    
    // Zeichner darf nicht raten
    if ($game['current_drawer_id'] == $playerId) {
        return ['success' => false, 'error' => 'Der Zeichner darf nicht raten'];
    }
    
    // Prüfen ob bereits richtig geraten
    $alreadyCorrect = $db->querySingle("SELECT COUNT(*) FROM guesses WHERE game_id = $gameId AND round = {$game['current_round']} AND player_id = $playerId AND is_correct = 1");
    if ($alreadyCorrect > 0) {
        return ['success' => false, 'error' => 'Du hast bereits richtig geraten'];
    }
    
    // Ist die Antwort richtig?
    $isCorrect = (strtolower($guessText) === strtolower($game['current_word']));
    
    // Punkte berechnen (mehr Punkte für schnelleres Raten)
    $points = 0;
    if ($isCorrect) {
        $elapsed = time() - $game['round_start_time'];
        $timePercent = max(0, ($game['round_time'] - $elapsed) / $game['round_time']);
        $points = 10 + round(40 * $timePercent); // 10-50 Punkte
        
        // Spieler-Score aktualisieren
        $db->exec("UPDATE game_players SET score = score + $points WHERE id = $playerId");
        
        // Zeichner bekommt auch Punkte
        $drawerPoints = round($points / 2);
        $db->exec("UPDATE game_players SET score = score + $drawerPoints WHERE id = {$game['current_drawer_id']}");
    }
    
    // Guess speichern
    $stmt = $db->prepare("INSERT INTO guesses (game_id, round, player_id, guess_text, is_correct, points_earned) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $gameId);
    $stmt->bindValue(2, $game['current_round']);
    $stmt->bindValue(3, $playerId);
    $stmt->bindValue(4, $guessText);
    $stmt->bindValue(5, $isCorrect ? 1 : 0);
    $stmt->bindValue(6, $points);
    $stmt->execute();
    
    return [
        'success' => true,
        'is_correct' => $isCorrect,
        'points' => $points,
        'message' => $isCorrect ? "🎉 Richtig! +$points Punkte" : ''
    ];
}

/**
 * Nächste Runde starten
 */
function nextRound($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $action = $input['action'] ?? 'next'; // 'start', 'next', 'end'
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game) {
        return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    }
    
    // Nur Host kann Spiel starten
    if ($action === 'start' && $game['host_id'] != $playerId) {
        return ['success' => false, 'error' => 'Nur der Host kann das Spiel starten'];
    }
    
    // Spieler abrufen
    $players = [];
    $result = $db->query("SELECT id FROM game_players WHERE game_id = $gameId ORDER BY id ASC");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $players[] = $row['id'];
    }
    
    if (count($players) < 2) {
        return ['success' => false, 'error' => 'Mindestens 2 Spieler benötigt'];
    }
    
    $newRound = $game['current_round'] + 1;
    
    // Spiel beenden?
    if ($newRound > $game['max_rounds']) {
        $db->exec("UPDATE games SET status = 'finished', updated_at = CURRENT_TIMESTAMP WHERE id = $gameId");
        return ['success' => true, 'status' => 'finished', 'message' => 'Spiel beendet!'];
    }
    
    // Nächster Zeichner (Round-Robin)
    $drawerIndex = ($newRound - 1) % count($players);
    $nextDrawerId = $players[$drawerIndex];
    
    // Neues Wort auswählen
    $newWord = getRandomWord(10); // TODO: Alter aus Session
    
    // Runde updaten
    $stmt = $db->prepare("UPDATE games SET 
        status = 'playing',
        current_round = ?,
        current_word = ?,
        current_drawer_id = ?,
        drawing_data = '',
        round_start_time = ?,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = ?");
    $stmt->bindValue(1, $newRound);
    $stmt->bindValue(2, $newWord);
    $stmt->bindValue(3, $nextDrawerId);
    $stmt->bindValue(4, time());
    $stmt->bindValue(5, $gameId);
    $stmt->execute();
    
    return [
        'success' => true,
        'round' => $newRound,
        'drawer_id' => $nextDrawerId,
        'message' => "Runde $newRound beginnt!"
    ];
}

/**
 * Spiel verlassen
 */
function leaveGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    // Spieler entfernen
    $db->exec("DELETE FROM game_players WHERE game_id = $gameId AND id = $playerId");
    
    // Prüfen ob noch Spieler übrig
    $remaining = $db->querySingle("SELECT COUNT(*) FROM game_players WHERE game_id = $gameId");
    
    if ($remaining == 0) {
        // Spiel löschen wenn leer
        $db->exec("DELETE FROM guesses WHERE game_id = $gameId");
        $db->exec("DELETE FROM games WHERE id = $gameId");
    }
    
    return ['success' => true, 'message' => 'Spiel verlassen'];
}
