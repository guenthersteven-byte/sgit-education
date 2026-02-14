<?php
/**
 * ============================================================================
 * sgiT Education - Mensch ärgere dich nicht API v1.0
 * ============================================================================
 * 
 * REST API für das klassische Brettspiel
 * 
 * Endpoints:
 * - POST create    : Neues Spiel erstellen
 * - POST join      : Spiel beitreten
 * - GET  status    : Spielstatus abrufen
 * - POST roll      : Würfeln
 * - POST move      : Figur bewegen
 * - POST leave     : Spiel verlassen
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

require_once __DIR__ . '/_security.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Datenbank-Verbindung
$dbPath = dirname(__DIR__) . '/wallet/madn.db';
$db = new SQLite3($dbPath);
$db->busyTimeout(5000);
$db->exec('PRAGMA journal_mode=WAL');

// Tabellen erstellen
initDatabase($db);

// Request verarbeiten
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
        case 'roll':
            echo json_encode(rollDice($db, $input));
            break;
        case 'move':
            echo json_encode(movePiece($db, $input));
            break;
        case 'leave':
            echo json_encode(leaveGame($db, $input));
            break;
        case 'start':
            echo json_encode(startGame($db, $input));
            break;
        default:
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    error_log("MADN API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten']);
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
        current_player INTEGER DEFAULT 0,
        current_roll INTEGER DEFAULT 0,
        can_move INTEGER DEFAULT 0,
        winner_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Spieler-Tabelle
    $db->exec("CREATE TABLE IF NOT EXISTS players (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_id INTEGER NOT NULL,
        wallet_child_id INTEGER,
        player_name TEXT NOT NULL,
        avatar TEXT DEFAULT '😀',
        color TEXT NOT NULL,
        player_order INTEGER NOT NULL,
        is_host INTEGER DEFAULT 0,
        pieces TEXT DEFAULT '[-1,-2,-3,-4]',  -- Im Startbereich (nicht auf dem Brett!)
        finished INTEGER DEFAULT 0,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (game_id) REFERENCES games(id)
    )");
    
    // Indizes
    $db->exec("CREATE INDEX IF NOT EXISTS idx_game_code ON games(game_code)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_players_game ON players(game_id)");
}

// Spieler-Farben und Startpositionen
const COLORS = ['red', 'blue', 'green', 'yellow'];
const COLOR_NAMES = ['Rot', 'Blau', 'Grün', 'Gelb'];
const COLOR_EMOJIS = ['🔴', '🔵', '🟢', '🟡'];

// Startfelder für jede Farbe (Eintrittsfeld auf dem Brett)
const START_FIELDS = [0, 10, 20, 30];

// Anzahl Felder auf dem Brett (ohne Ziel)
const BOARD_SIZE = 40;

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
    
    // Einzigartigen Code generieren
    do {
        $gameCode = generateGameCode();
        $exists = $db->querySingle("SELECT COUNT(*) FROM games WHERE game_code = '$gameCode'");
    } while ($exists > 0);
    
    // Spiel erstellen
    $stmt = $db->prepare("INSERT INTO games (game_code, host_id) VALUES (?, 0)");
    $stmt->bindValue(1, $gameCode);
    $stmt->execute();
    
    $gameId = $db->lastInsertRowID();
    
    // Host als ersten Spieler (Rot) - EXPLIZIT pieces setzen!
    $stmt = $db->prepare("INSERT INTO players (game_id, wallet_child_id, player_name, avatar, color, player_order, is_host, pieces) VALUES (?, ?, ?, ?, 'red', 0, 1, '[-1,-2,-3,-4]')");
    $stmt->bindValue(1, $gameId);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->execute();
    
    $playerId = $db->lastInsertRowID();
    
    // Host-ID setzen
    $db->exec("UPDATE games SET host_id = $playerId WHERE id = $gameId");
    
    return [
        'success' => true,
        'game_code' => $gameCode,
        'game_id' => $gameId,
        'player_id' => $playerId,
        'color' => 'red',
        'message' => 'Spiel erstellt! Teile den Code.'
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
    
    // Anzahl Spieler prüfen
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = {$game['id']}");
    
    if ($playerCount >= 4) {
        return ['success' => false, 'error' => 'Spiel ist voll (max. 4 Spieler)'];
    }
    
    // Nächste freie Farbe
    $colors = ['red', 'blue', 'green', 'yellow'];
    $usedColors = [];
    $result = $db->query("SELECT color FROM players WHERE game_id = {$game['id']}");
    while ($row = $result->fetchArray()) {
        $usedColors[] = $row['color'];
    }
    
    $nextColor = null;
    foreach ($colors as $c) {
        if (!in_array($c, $usedColors)) {
            $nextColor = $c;
            break;
        }
    }
    
    // Spieler hinzufügen - EXPLIZIT pieces setzen!
    $stmt = $db->prepare("INSERT INTO players (game_id, wallet_child_id, player_name, avatar, color, player_order, pieces) VALUES (?, ?, ?, ?, ?, ?, '[-1,-2,-3,-4]')");
    $stmt->bindValue(1, $game['id']);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->bindValue(5, $nextColor);
    $stmt->bindValue(6, $playerCount);
    $stmt->execute();
    
    $playerId = $db->lastInsertRowID();
    
    return [
        'success' => true,
        'game_id' => $game['id'],
        'player_id' => $playerId,
        'color' => $nextColor,
        'message' => 'Beigetreten!'
    ];
}

/**
 * Spiel starten
 */
function startGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    // Prüfen ob Host
    $game = $db->querySingle("SELECT host_id FROM games WHERE id = $gameId", true);
    if (!$game || $game['host_id'] != $playerId) {
        return ['success' => false, 'error' => 'Nur der Host kann starten'];
    }
    
    // Mindestens 2 Spieler
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    if ($playerCount < 2) {
        return ['success' => false, 'error' => 'Mindestens 2 Spieler benötigt'];
    }
    
    // Spiel starten - Spieler 0 (Rot) beginnt
    $db->exec("UPDATE games SET status = 'playing', current_player = 0, updated_at = CURRENT_TIMESTAMP WHERE id = $gameId");
    
    return ['success' => true, 'message' => 'Spiel gestartet!'];
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
    $result = $db->query("SELECT * FROM players WHERE game_id = $gameId ORDER BY player_order ASC");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $row['pieces'] = json_decode($row['pieces'], true);
        $players[] = $row;
    }
    
    // Aktueller Spieler
    $currentPlayerData = null;
    $myTurn = false;
    foreach ($players as $p) {
        if ($p['player_order'] == $game['current_player']) {
            $currentPlayerData = $p;
        }
        if ($p['id'] == $playerId && $p['player_order'] == $game['current_player']) {
            $myTurn = true;
        }
    }
    
    // Eigene Spielerinfo
    $myPlayer = null;
    foreach ($players as $p) {
        if ($p['id'] == $playerId) {
            $myPlayer = $p;
            break;
        }
    }
    
    return [
        'success' => true,
        'game' => [
            'id' => $game['id'],
            'code' => $game['game_code'],
            'status' => $game['status'],
            'current_player' => $game['current_player'],
            'current_roll' => $game['current_roll'],
            'can_move' => $game['can_move'],
            'winner_id' => $game['winner_id']
        ],
        'players' => $players,
        'current_player_data' => $currentPlayerData,
        'my_player' => $myPlayer,
        'my_turn' => $myTurn,
        'is_host' => ($game['host_id'] == $playerId)
    ];
}

/**
 * Würfeln
 */
function rollDice($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    // Spielinfo
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing') {
        return ['success' => false, 'error' => 'Spiel läuft nicht'];
    }
    
    // Prüfen ob am Zug
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['player_order'] != $game['current_player']) {
        return ['success' => false, 'error' => 'Du bist nicht am Zug'];
    }
    
    // Bereits gewürfelt und muss ziehen?
    if ($game['can_move']) {
        return ['success' => false, 'error' => 'Du musst erst ziehen'];
    }
    
    // Würfeln (1-6)
    $roll = random_int(1, 6);
    
    // Prüfen ob Spieler ziehen kann
    $pieces = json_decode($player['pieces'], true);
    $canMove = canPlayerMove($pieces, $roll, $player['player_order']);
    
    // Speichern
    $db->exec("UPDATE games SET current_roll = $roll, can_move = " . ($canMove ? 1 : 0) . ", updated_at = CURRENT_TIMESTAMP WHERE id = $gameId");
    
    // Wenn nicht ziehen kann, nächster Spieler (außer bei 6)
    if (!$canMove && $roll != 6) {
        nextPlayer($db, $gameId);
    } elseif (!$canMove && $roll == 6) {
        // Bei 6 aber kann nicht ziehen -> nochmal würfeln erlauben
        $db->exec("UPDATE games SET can_move = 0 WHERE id = $gameId");
    }
    
    return [
        'success' => true,
        'roll' => $roll,
        'can_move' => $canMove
    ];
}

/**
 * Prüfen ob Spieler ziehen kann
 */
function canPlayerMove($pieces, $roll, $playerOrder) {
    $startField = $playerOrder * 10; // 0, 10, 20, 30
    
    foreach ($pieces as $i => $pos) {
        // Figur im Startbereich und 6 gewürfelt
        if ($pos < 0 && $roll == 6) {
            // Prüfen ob Startfeld frei ist (keine eigene Figur)
            if (!in_array($startField, $pieces)) {
                return true;
            }
        }
        
        // Figur auf dem Brett
        if ($pos >= 0 && $pos < 40) {
            $newPos = calculateNewPosition($pos, $roll, $playerOrder);
            // Prüfen ob Zielfeld nicht von eigener Figur besetzt
            if ($newPos !== false && !in_array($newPos, $pieces)) {
                return true;
            }
        }
        
        // Figur im Zielbereich - kann evtl. weiter
        if ($pos >= 40 && $pos < 44) {
            $newPos = $pos + $roll;
            // Prüfen ob Zielfeld frei und gültig
            if ($newPos <= 43 && !in_array($newPos, $pieces)) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Neue Position berechnen
 */
function calculateNewPosition($currentPos, $roll, $playerOrder) {
    $startField = $playerOrder * 10;
    $homeEntry = ($startField + 39) % 40; // Feld vor dem Zieleingang
    
    // Relative Position zum Start
    $relPos = ($currentPos - $startField + 40) % 40;
    $newRelPos = $relPos + $roll;
    
    // Zielbereich erreicht?
    if ($newRelPos >= 40 && $newRelPos <= 43) {
        return 40 + ($newRelPos - 40); // 40-43 = Zielbereich
    }
    
    // Über Ziel hinaus = ungültig
    if ($newRelPos > 43) {
        return false;
    }
    
    // Normale Bewegung auf dem Brett
    return ($startField + $newRelPos) % 40;
}

/**
 * Figur bewegen
 */
function movePiece($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $pieceIndex = $input['piece_index'] ?? 0;
    
    // Spielinfo
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing' || !$game['can_move']) {
        return ['success' => false, 'error' => 'Kein gültiger Zug'];
    }
    
    // Spieler prüfen
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['player_order'] != $game['current_player']) {
        return ['success' => false, 'error' => 'Du bist nicht am Zug'];
    }
    
    $pieces = json_decode($player['pieces'], true);
    $roll = $game['current_roll'];
    $currentPos = $pieces[$pieceIndex];
    
    // Zug berechnen
    $newPos = null;
    $kicked = null;
    
    if ($currentPos < 0 && $roll == 6) {
        // Aus Startbereich auf Startfeld
        $newPos = $player['player_order'] * 10;
    } elseif ($currentPos >= 0 && $currentPos < 40) {
        // Normale Bewegung
        $newPos = calculateNewPosition($currentPos, $roll, $player['player_order']);
    } elseif ($currentPos >= 40 && $currentPos < 44) {
        // Im Zielbereich weiter
        $newPos = $currentPos + $roll;
        if ($newPos > 43) {
            return ['success' => false, 'error' => 'Ungültiger Zug'];
        }
    }
    
    if ($newPos === null || $newPos === false) {
        return ['success' => false, 'error' => 'Ungültiger Zug'];
    }
    
    // BUG-FIX: Prüfen ob Zielfeld von eigener Figur besetzt
    foreach ($pieces as $i => $pos) {
        if ($i !== $pieceIndex && $pos === $newPos) {
            return ['success' => false, 'error' => 'Feld von eigener Figur besetzt'];
        }
    }
    
    // Schlagen prüfen (nur auf Brett, nicht im Ziel)
    if ($newPos >= 0 && $newPos < 40) {
        $kicked = checkAndKick($db, $gameId, $playerId, $newPos);
    }
    
    // Position aktualisieren
    $pieces[$pieceIndex] = $newPos;
    $piecesJson = json_encode($pieces);
    $db->exec("UPDATE players SET pieces = '$piecesJson' WHERE id = $playerId");
    
    // Gewinner prüfen
    $winner = checkWinner($pieces);
    if ($winner) {
        $db->exec("UPDATE games SET status = 'finished', winner_id = $playerId, updated_at = CURRENT_TIMESTAMP WHERE id = $gameId");
        return ['success' => true, 'winner' => true, 'kicked' => $kicked];
    }
    
    // Bei 6 nochmal, sonst nächster Spieler
    if ($roll == 6) {
        $db->exec("UPDATE games SET can_move = 0, current_roll = 0, updated_at = CURRENT_TIMESTAMP WHERE id = $gameId");
    } else {
        nextPlayer($db, $gameId);
    }
    
    return ['success' => true, 'new_position' => $newPos, 'kicked' => $kicked];
}

/**
 * Prüfen und Figur schlagen
 */
function checkAndKick($db, $gameId, $movingPlayerId, $targetPos) {
    // Alle anderen Spieler prüfen
    $result = $db->query("SELECT * FROM players WHERE game_id = $gameId AND id != $movingPlayerId");
    
    while ($other = $result->fetchArray(SQLITE3_ASSOC)) {
        $pieces = json_decode($other['pieces'], true);
        
        foreach ($pieces as $i => $pos) {
            if ($pos == $targetPos && $pos >= 0 && $pos < 40) {
                // Figur zurück in Startbereich
                $pieces[$i] = -($i + 1); // -1, -2, -3, -4
                $piecesJson = json_encode($pieces);
                $db->exec("UPDATE players SET pieces = '$piecesJson' WHERE id = {$other['id']}");
                
                return [
                    'player_id' => $other['id'],
                    'player_name' => $other['player_name'],
                    'color' => $other['color']
                ];
            }
        }
    }
    
    return null;
}

/**
 * Gewinner prüfen (alle 4 Figuren im Ziel)
 */
function checkWinner($pieces) {
    foreach ($pieces as $pos) {
        if ($pos < 40) {
            return false; // Noch nicht alle im Ziel
        }
    }
    return true;
}

/**
 * Nächster Spieler
 */
function nextPlayer($db, $gameId) {
    // Spieler zählen
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    
    // Aktueller Spieler
    $current = $db->querySingle("SELECT current_player FROM games WHERE id = $gameId");
    
    // Nächster (round-robin)
    $next = ($current + 1) % $playerCount;
    
    $db->exec("UPDATE games SET current_player = $next, current_roll = 0, can_move = 0, updated_at = CURRENT_TIMESTAMP WHERE id = $gameId");
}

/**
 * Spiel verlassen
 */
function leaveGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    // Spieler entfernen
    $db->exec("DELETE FROM players WHERE game_id = $gameId AND id = $playerId");
    
    // Prüfen ob noch Spieler übrig
    $remaining = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    
    if ($remaining == 0) {
        // Spiel löschen wenn leer
        $db->exec("DELETE FROM games WHERE id = $gameId");
    } elseif ($remaining == 1) {
        // Nur noch einer -> Spiel beenden
        $db->exec("UPDATE games SET status = 'finished' WHERE id = $gameId");
    }
    
    return ['success' => true, 'message' => 'Spiel verlassen'];
}
