<?php
/**
 * ============================================================================
 * sgiT Education - Dame API v1.0
 * ============================================================================
 * 
 * REST API für das klassische Brettspiel Dame
 * 
 * Regeln:
 * - 8x8 Brett, nur dunkle Felder
 * - Diagonal ziehen, nur vorwärts (normale Steine)
 * - Schlagen durch Überspringen
 * - Mehrfachsprünge möglich
 * - Dame kann rückwärts ziehen
 * - Schlagzwang
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

$dbPath = dirname(__DIR__) . '/wallet/dame.db';
$db = new SQLite3($dbPath);
$db->busyTimeout(5000);
$db->exec('PRAGMA journal_mode=WAL');

initDatabase($db);

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
        case 'start':
            echo json_encode(startGame($db, $input));
            break;
        case 'status':
            echo json_encode(getGameStatus($db, $_GET));
            break;
        case 'move':
            echo json_encode(movePiece($db, $input));
            break;
        case 'leave':
            echo json_encode(leaveGame($db, $input));
            break;
        default:
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    error_log("Dame API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ein Fehler ist aufgetreten']);
}

$db->close();

function initDatabase($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS games (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_code TEXT UNIQUE NOT NULL,
        host_id INTEGER NOT NULL,
        status TEXT DEFAULT 'waiting',
        current_player TEXT DEFAULT 'black',
        board TEXT,
        must_capture_from TEXT,
        winner TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS players (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_id INTEGER NOT NULL,
        wallet_child_id INTEGER,
        player_name TEXT NOT NULL,
        avatar TEXT DEFAULT '😀',
        color TEXT NOT NULL,
        is_host INTEGER DEFAULT 0,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (game_id) REFERENCES games(id)
    )");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_dame_code ON games(game_code)");
}

/**
 * Startaufstellung erstellen
 * Schwarz oben (Reihen 0-2), Weiß unten (Reihen 5-7)
 */
function createInitialBoard() {
    $board = [];
    for ($row = 0; $row < 8; $row++) {
        for ($col = 0; $col < 8; $col++) {
            // Nur dunkle Felder (Schachbrettmuster)
            if (($row + $col) % 2 === 1) {
                if ($row < 3) {
                    $board["$row,$col"] = ['color' => 'black', 'king' => false];
                } elseif ($row > 4) {
                    $board["$row,$col"] = ['color' => 'white', 'king' => false];
                }
            }
        }
    }
    return $board;
}

function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function createGame($db, $input) {
    $playerName = $input['player_name'] ?? 'Spieler';
    $avatar = $input['avatar'] ?? '😀';
    $walletChildId = $input['wallet_child_id'] ?? null;
    
    do {
        $gameCode = generateGameCode();
        $exists = $db->querySingle("SELECT COUNT(*) FROM games WHERE game_code = '$gameCode'");
    } while ($exists > 0);
    
    $stmt = $db->prepare("INSERT INTO games (game_code, host_id, board) VALUES (?, 0, '{}')");
    $stmt->bindValue(1, $gameCode);
    $stmt->execute();
    
    $gameId = $db->lastInsertRowID();
    
    // Host ist Schwarz (beginnt)
    $stmt = $db->prepare("INSERT INTO players (game_id, wallet_child_id, player_name, avatar, color, is_host) VALUES (?, ?, ?, ?, 'black', 1)");
    $stmt->bindValue(1, $gameId);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->execute();
    
    $playerId = $db->lastInsertRowID();
    $db->exec("UPDATE games SET host_id = $playerId WHERE id = $gameId");
    
    return [
        'success' => true,
        'game_code' => $gameCode,
        'game_id' => $gameId,
        'player_id' => $playerId,
        'color' => 'black'
    ];
}

function joinGame($db, $input) {
    $gameCode = strtoupper($input['game_code'] ?? '');
    $playerName = $input['player_name'] ?? 'Spieler';
    $avatar = $input['avatar'] ?? '😀';
    $walletChildId = $input['wallet_child_id'] ?? null;
    
    $game = $db->querySingle("SELECT id, status FROM games WHERE game_code = '$gameCode'", true);
    
    if (!$game) {
        return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    }
    if ($game['status'] !== 'waiting') {
        return ['success' => false, 'error' => 'Spiel hat bereits begonnen'];
    }
    
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = {$game['id']}");
    if ($playerCount >= 2) {
        return ['success' => false, 'error' => 'Spiel ist voll (max. 2)'];
    }
    
    // Zweiter Spieler ist Weiß
    $stmt = $db->prepare("INSERT INTO players (game_id, wallet_child_id, player_name, avatar, color) VALUES (?, ?, ?, ?, 'white')");
    $stmt->bindValue(1, $game['id']);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->execute();
    
    return [
        'success' => true,
        'game_id' => $game['id'],
        'player_id' => $db->lastInsertRowID(),
        'color' => 'white'
    ];
}

function startGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['host_id'] != $playerId) {
        return ['success' => false, 'error' => 'Nur Host kann starten'];
    }
    
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    if ($playerCount < 2) {
        return ['success' => false, 'error' => 'Brauche 2 Spieler'];
    }
    
    $board = createInitialBoard();
    $boardJson = json_encode($board);
    
    $db->exec("UPDATE games SET status = 'playing', board = '$boardJson', current_player = 'black', updated_at = CURRENT_TIMESTAMP WHERE id = $gameId");
    
    return ['success' => true];
}

function getGameStatus($db, $params) {
    $gameId = $params['game_id'] ?? 0;
    $playerId = $params['player_id'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game) {
        return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    }
    
    $board = json_decode($game['board'], true) ?: [];
    
    $players = [];
    $myColor = null;
    $result = $db->query("SELECT * FROM players WHERE game_id = $gameId");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $players[] = $row;
        if ($row['id'] == $playerId) {
            $myColor = $row['color'];
        }
    }
    
    // Steine zählen
    $blackCount = 0;
    $whiteCount = 0;
    foreach ($board as $piece) {
        if ($piece['color'] === 'black') $blackCount++;
        else $whiteCount++;
    }
    
    return [
        'success' => true,
        'game' => [
            'id' => $game['id'],
            'code' => $game['game_code'],
            'status' => $game['status'],
            'current_player' => $game['current_player'],
            'must_capture_from' => $game['must_capture_from'],
            'winner' => $game['winner']
        ],
        'board' => $board,
        'players' => $players,
        'my_color' => $myColor,
        'my_turn' => ($myColor === $game['current_player']),
        'is_host' => ($game['host_id'] == $playerId),
        'piece_count' => ['black' => $blackCount, 'white' => $whiteCount]
    ];
}

/**
 * Zug ausführen
 */
function movePiece($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $from = $input['from'] ?? '';
    $to = $input['to'] ?? '';
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing') {
        return ['success' => false, 'error' => 'Spiel läuft nicht'];
    }
    
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['color'] !== $game['current_player']) {
        return ['success' => false, 'error' => 'Du bist nicht am Zug'];
    }
    
    $board = json_decode($game['board'], true);
    $myColor = $player['color'];
    
    // Prüfen ob Figur existiert und mir gehört
    if (!isset($board[$from]) || $board[$from]['color'] !== $myColor) {
        return ['success' => false, 'error' => 'Keine eigene Figur'];
    }
    
    // Schlagzwang prüfen
    if ($game['must_capture_from'] && $game['must_capture_from'] !== $from) {
        return ['success' => false, 'error' => 'Du musst mit dem markierten Stein weiterschlagen'];
    }
    
    $piece = $board[$from];
    list($fromRow, $fromCol) = explode(',', $from);
    list($toRow, $toCol) = explode(',', $to);
    $fromRow = (int)$fromRow;
    $fromCol = (int)$fromCol;
    $toRow = (int)$toRow;
    $toCol = (int)$toCol;
    
    // Zielfeld prüfen
    if ($toRow < 0 || $toRow > 7 || $toCol < 0 || $toCol > 7) {
        return ['success' => false, 'error' => 'Außerhalb des Bretts'];
    }
    if (($toRow + $toCol) % 2 === 0) {
        return ['success' => false, 'error' => 'Nur dunkle Felder'];
    }
    if (isset($board[$to])) {
        return ['success' => false, 'error' => 'Feld besetzt'];
    }
    
    $rowDiff = $toRow - $fromRow;
    $colDiff = $toCol - $fromCol;
    
    // Bewegungsrichtung prüfen
    $isKing = $piece['king'];
    $direction = ($myColor === 'black') ? 1 : -1; // Schwarz nach unten, Weiß nach oben
    
    $isCapture = false;
    $capturedPos = null;
    
    // Normaler Zug (1 Feld diagonal)
    if (abs($rowDiff) === 1 && abs($colDiff) === 1) {
        // Richtung prüfen (außer bei Dame)
        if (!$isKing && $rowDiff !== $direction) {
            return ['success' => false, 'error' => 'Nur vorwärts'];
        }
        
        // Schlagzwang: Wenn Schlagen möglich, muss geschlagen werden
        if (hasCaptureMoves($board, $myColor)) {
            return ['success' => false, 'error' => 'Du musst schlagen!'];
        }
    }
    // Schlag (2 Felder diagonal, über Gegner)
    elseif (abs($rowDiff) === 2 && abs($colDiff) === 2) {
        // Richtung prüfen (außer bei Dame)
        if (!$isKing && $rowDiff !== 2 * $direction) {
            // Normale Steine können nur rückwärts schlagen, nicht ziehen
            // Bei Dame-Regeln können normale Steine auch rückwärts schlagen
        }
        
        $midRow = $fromRow + $rowDiff / 2;
        $midCol = $fromCol + $colDiff / 2;
        $midPos = "$midRow,$midCol";
        
        if (!isset($board[$midPos]) || $board[$midPos]['color'] === $myColor) {
            return ['success' => false, 'error' => 'Kein Gegner zum Schlagen'];
        }
        
        $isCapture = true;
        $capturedPos = $midPos;
    }
    else {
        return ['success' => false, 'error' => 'Ungültiger Zug'];
    }
    
    // Zug ausführen
    unset($board[$from]);
    if ($capturedPos) {
        unset($board[$capturedPos]);
    }
    
    // Dame-Umwandlung
    if (($myColor === 'black' && $toRow === 7) || ($myColor === 'white' && $toRow === 0)) {
        $piece['king'] = true;
    }
    
    $board[$to] = $piece;
    
    // Mehrfachsprung prüfen
    $mustCaptureFrom = null;
    if ($isCapture && canCaptureFrom($board, $to, $myColor, $piece['king'])) {
        $mustCaptureFrom = $to;
    }
    
    // Nächster Spieler (nur wenn kein Mehrfachsprung)
    $nextPlayer = $game['current_player'];
    if (!$mustCaptureFrom) {
        $nextPlayer = ($myColor === 'black') ? 'white' : 'black';
    }
    
    // Gewinner prüfen
    $winner = checkWinner($board, $nextPlayer);
    
    $boardJson = json_encode($board);
    $mustCaptureSql = $mustCaptureFrom ? "'$mustCaptureFrom'" : "NULL";
    $winnerSql = $winner ? "'$winner'" : "NULL";
    $statusSql = $winner ? "'finished'" : "'playing'";
    
    $db->exec("UPDATE games SET 
        board = '$boardJson', 
        current_player = '$nextPlayer', 
        must_capture_from = $mustCaptureSql,
        winner = $winnerSql,
        status = $statusSql,
        updated_at = CURRENT_TIMESTAMP 
        WHERE id = $gameId");
    
    return [
        'success' => true, 
        'captured' => $isCapture,
        'must_continue' => $mustCaptureFrom !== null,
        'promoted' => $piece['king'] && !$board[$to]['king'],
        'winner' => $winner
    ];
}

/**
 * Prüfen ob Spieler Schlagzüge hat
 */
function hasCaptureMoves($board, $color) {
    foreach ($board as $pos => $piece) {
        if ($piece['color'] === $color) {
            if (canCaptureFrom($board, $pos, $color, $piece['king'])) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Prüfen ob von Position aus geschlagen werden kann
 */
function canCaptureFrom($board, $pos, $color, $isKing) {
    list($row, $col) = explode(',', $pos);
    $row = (int)$row;
    $col = (int)$col;
    
    $directions = [[-1, -1], [-1, 1], [1, -1], [1, 1]];
    
    foreach ($directions as $dir) {
        $midRow = $row + $dir[0];
        $midCol = $col + $dir[1];
        $toRow = $row + 2 * $dir[0];
        $toCol = $col + 2 * $dir[1];
        
        // Im Brett?
        if ($toRow < 0 || $toRow > 7 || $toCol < 0 || $toCol > 7) continue;
        
        // Richtung prüfen (normale Steine nur vorwärts, außer beim Schlagen)
        // In vielen Varianten können normale Steine auch rückwärts schlagen
        
        $midPos = "$midRow,$midCol";
        $toPos = "$toRow,$toCol";
        
        // Gegner in der Mitte und Zielfeld frei?
        if (isset($board[$midPos]) && $board[$midPos]['color'] !== $color && !isset($board[$toPos])) {
            return true;
        }
    }
    
    return false;
}

/**
 * Gewinner prüfen
 */
function checkWinner($board, $nextPlayer) {
    $blackPieces = 0;
    $whitePieces = 0;
    $blackCanMove = false;
    $whiteCanMove = false;
    
    foreach ($board as $pos => $piece) {
        if ($piece['color'] === 'black') {
            $blackPieces++;
            if (!$blackCanMove && canMove($board, $pos, 'black', $piece['king'])) {
                $blackCanMove = true;
            }
        } else {
            $whitePieces++;
            if (!$whiteCanMove && canMove($board, $pos, 'white', $piece['king'])) {
                $whiteCanMove = true;
            }
        }
    }
    
    // Keine Steine mehr
    if ($blackPieces === 0) return 'white';
    if ($whitePieces === 0) return 'black';
    
    // Kann nicht mehr ziehen
    if ($nextPlayer === 'black' && !$blackCanMove) return 'white';
    if ($nextPlayer === 'white' && !$whiteCanMove) return 'black';
    
    return null;
}

/**
 * Prüfen ob Figur ziehen kann
 */
function canMove($board, $pos, $color, $isKing) {
    list($row, $col) = explode(',', $pos);
    $row = (int)$row;
    $col = (int)$col;
    
    $direction = ($color === 'black') ? 1 : -1;
    $directions = $isKing ? [[-1, -1], [-1, 1], [1, -1], [1, 1]] : [[$direction, -1], [$direction, 1]];
    
    foreach ($directions as $dir) {
        // Normaler Zug
        $toRow = $row + $dir[0];
        $toCol = $col + $dir[1];
        if ($toRow >= 0 && $toRow <= 7 && $toCol >= 0 && $toCol <= 7) {
            if (!isset($board["$toRow,$toCol"])) return true;
        }
        
        // Schlagzug
        $midRow = $row + $dir[0];
        $midCol = $col + $dir[1];
        $jumpRow = $row + 2 * $dir[0];
        $jumpCol = $col + 2 * $dir[1];
        
        if ($jumpRow >= 0 && $jumpRow <= 7 && $jumpCol >= 0 && $jumpCol <= 7) {
            $midPos = "$midRow,$midCol";
            if (isset($board[$midPos]) && $board[$midPos]['color'] !== $color && !isset($board["$jumpRow,$jumpCol"])) {
                return true;
            }
        }
    }
    
    // Rückwärts schlagen für normale Steine
    if (!$isKing) {
        $backDirs = [[-$direction, -1], [-$direction, 1]];
        foreach ($backDirs as $dir) {
            $midRow = $row + $dir[0];
            $midCol = $col + $dir[1];
            $jumpRow = $row + 2 * $dir[0];
            $jumpCol = $col + 2 * $dir[1];
            
            if ($jumpRow >= 0 && $jumpRow <= 7 && $jumpCol >= 0 && $jumpCol <= 7) {
                $midPos = "$midRow,$midCol";
                if (isset($board[$midPos]) && $board[$midPos]['color'] !== $color && !isset($board["$jumpRow,$jumpCol"])) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

function leaveGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    $player = $db->querySingle("SELECT color FROM players WHERE id = $playerId", true);
    
    $db->exec("DELETE FROM players WHERE game_id = $gameId AND id = $playerId");
    
    $remaining = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    
    if ($remaining == 0) {
        $db->exec("DELETE FROM games WHERE id = $gameId");
    } else {
        // Gegner gewinnt
        $winner = ($player['color'] === 'black') ? 'white' : 'black';
        $db->exec("UPDATE games SET status = 'finished', winner = '$winner' WHERE id = $gameId");
    }
    
    return ['success' => true];
}
