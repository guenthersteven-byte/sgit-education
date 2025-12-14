<?php
/**
 * ============================================================================
 * sgiT Education - Schach PvP API v1.0
 * ============================================================================
 * 
 * REST API für Schach Multiplayer
 * 
 * Figuren: König, Dame, Turm, Läufer, Springer, Bauer
 * Spezialzüge: Rochade, En Passant, Bauernumwandlung
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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$dbPath = dirname(__DIR__) . '/wallet/schach_pvp.db';
$db = new SQLite3($dbPath);
$db->busyTimeout(5000);
$db->exec('PRAGMA journal_mode=WAL');

initDatabase($db);

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($action) {
        case 'create': echo json_encode(createGame($db, $input)); break;
        case 'join': echo json_encode(joinGame($db, $input)); break;
        case 'start': echo json_encode(startGame($db, $input)); break;
        case 'status': echo json_encode(getGameStatus($db, $_GET)); break;
        case 'move': echo json_encode(movePiece($db, $input)); break;
        case 'promote': echo json_encode(promotePawn($db, $input)); break;
        case 'resign': echo json_encode(resignGame($db, $input)); break;
        case 'leave': echo json_encode(leaveGame($db, $input)); break;
        default: echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$db->close();

function initDatabase($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS games (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_code TEXT UNIQUE NOT NULL,
        host_id INTEGER NOT NULL,
        status TEXT DEFAULT 'waiting',
        current_player TEXT DEFAULT 'white',
        board TEXT,
        castling TEXT DEFAULT '{\"wK\":true,\"wQ\":true,\"bK\":true,\"bQ\":true}',
        en_passant TEXT,
        half_moves INTEGER DEFAULT 0,
        full_moves INTEGER DEFAULT 1,
        check_state TEXT,
        pending_promotion TEXT,
        winner TEXT,
        win_reason TEXT,
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
        FOREIGN KEY (game_id) REFERENCES games(id)
    )");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_schach_code ON games(game_code)");
}

// Figuren-Symbole
const PIECES = [
    'wK' => '♔', 'wQ' => '♕', 'wR' => '♖', 'wB' => '♗', 'wN' => '♘', 'wP' => '♙',
    'bK' => '♚', 'bQ' => '♛', 'bR' => '♜', 'bB' => '♝', 'bN' => '♞', 'bP' => '♟'
];

/**
 * Startaufstellung
 */
function createInitialBoard() {
    return [
        'a8' => 'bR', 'b8' => 'bN', 'c8' => 'bB', 'd8' => 'bQ', 'e8' => 'bK', 'f8' => 'bB', 'g8' => 'bN', 'h8' => 'bR',
        'a7' => 'bP', 'b7' => 'bP', 'c7' => 'bP', 'd7' => 'bP', 'e7' => 'bP', 'f7' => 'bP', 'g7' => 'bP', 'h7' => 'bP',
        'a2' => 'wP', 'b2' => 'wP', 'c2' => 'wP', 'd2' => 'wP', 'e2' => 'wP', 'f2' => 'wP', 'g2' => 'wP', 'h2' => 'wP',
        'a1' => 'wR', 'b1' => 'wN', 'c1' => 'wB', 'd1' => 'wQ', 'e1' => 'wK', 'f1' => 'wB', 'g1' => 'wN', 'h1' => 'wR'
    ];
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
    
    // Host ist Weiß (beginnt)
    $stmt = $db->prepare("INSERT INTO players (game_id, wallet_child_id, player_name, avatar, color, is_host) VALUES (?, ?, ?, ?, 'white', 1)");
    $stmt->bindValue(1, $gameId);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->execute();
    
    $playerId = $db->lastInsertRowID();
    $db->exec("UPDATE games SET host_id = $playerId WHERE id = $gameId");
    
    return ['success' => true, 'game_code' => $gameCode, 'game_id' => $gameId, 'player_id' => $playerId, 'color' => 'white'];
}

function joinGame($db, $input) {
    $gameCode = strtoupper($input['game_code'] ?? '');
    $playerName = $input['player_name'] ?? 'Spieler';
    $avatar = $input['avatar'] ?? '😀';
    $walletChildId = $input['wallet_child_id'] ?? null;
    
    $game = $db->querySingle("SELECT id, status FROM games WHERE game_code = '$gameCode'", true);
    if (!$game) return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    if ($game['status'] !== 'waiting') return ['success' => false, 'error' => 'Spiel läuft bereits'];
    
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = {$game['id']}");
    if ($playerCount >= 2) return ['success' => false, 'error' => 'Spiel voll'];
    
    $stmt = $db->prepare("INSERT INTO players (game_id, wallet_child_id, player_name, avatar, color) VALUES (?, ?, ?, ?, 'black')");
    $stmt->bindValue(1, $game['id']);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->execute();
    
    return ['success' => true, 'game_id' => $game['id'], 'player_id' => $db->lastInsertRowID(), 'color' => 'black'];
}

function startGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['host_id'] != $playerId) return ['success' => false, 'error' => 'Nur Host'];
    
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    if ($playerCount < 2) return ['success' => false, 'error' => '2 Spieler nötig'];
    
    $board = createInitialBoard();
    $boardJson = json_encode($board);
    
    $db->exec("UPDATE games SET status = 'playing', board = '$boardJson', current_player = 'white' WHERE id = $gameId");
    
    return ['success' => true];
}

function getGameStatus($db, $params) {
    $gameId = $params['game_id'] ?? 0;
    $playerId = $params['player_id'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game) return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    
    $board = json_decode($game['board'], true) ?: [];
    $castling = json_decode($game['castling'], true) ?: [];
    
    $players = [];
    $myColor = null;
    $result = $db->query("SELECT * FROM players WHERE game_id = $gameId");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $players[] = $row;
        if ($row['id'] == $playerId) $myColor = $row['color'];
    }
    
    return [
        'success' => true,
        'game' => [
            'id' => $game['id'],
            'code' => $game['game_code'],
            'status' => $game['status'],
            'current_player' => $game['current_player'],
            'check_state' => $game['check_state'],
            'pending_promotion' => $game['pending_promotion'],
            'winner' => $game['winner'],
            'win_reason' => $game['win_reason'],
            'en_passant' => $game['en_passant']
        ],
        'board' => $board,
        'castling' => $castling,
        'players' => $players,
        'my_color' => $myColor,
        'my_turn' => ($myColor === $game['current_player']),
        'is_host' => ($game['host_id'] == $playerId)
    ];
}

/**
 * Koordinaten-Helfer
 */
function parseSquare($sq) {
    return [ord($sq[0]) - ord('a'), (int)$sq[1] - 1];
}

function toSquare($col, $row) {
    return chr(ord('a') + $col) . ($row + 1);
}

function getPieceColor($piece) {
    return $piece[0] === 'w' ? 'white' : 'black';
}

function getPieceType($piece) {
    return $piece[1];
}

/**
 * Prüfen ob Feld angegriffen wird
 */
function isSquareAttacked($board, $square, $byColor) {
    list($targetCol, $targetRow) = parseSquare($square);
    
    foreach ($board as $sq => $piece) {
        if (getPieceColor($piece) !== $byColor) continue;
        
        list($col, $row) = parseSquare($sq);
        $type = getPieceType($piece);
        
        switch ($type) {
            case 'P': // Bauer
                $dir = ($byColor === 'white') ? 1 : -1;
                if (abs($col - $targetCol) === 1 && $targetRow - $row === $dir) return true;
                break;
            case 'N': // Springer
                $dc = abs($col - $targetCol);
                $dr = abs($row - $targetRow);
                if (($dc === 2 && $dr === 1) || ($dc === 1 && $dr === 2)) return true;
                break;
            case 'B': // Läufer
                if (abs($col - $targetCol) === abs($row - $targetRow)) {
                    if (isPathClear($board, $sq, $square)) return true;
                }
                break;
            case 'R': // Turm
                if ($col === $targetCol || $row === $targetRow) {
                    if (isPathClear($board, $sq, $square)) return true;
                }
                break;
            case 'Q': // Dame
                if ($col === $targetCol || $row === $targetRow || abs($col - $targetCol) === abs($row - $targetRow)) {
                    if (isPathClear($board, $sq, $square)) return true;
                }
                break;
            case 'K': // König
                if (abs($col - $targetCol) <= 1 && abs($row - $targetRow) <= 1) return true;
                break;
        }
    }
    return false;
}

/**
 * Prüfen ob Pfad frei ist (für Läufer, Turm, Dame)
 */
function isPathClear($board, $from, $to) {
    list($fromCol, $fromRow) = parseSquare($from);
    list($toCol, $toRow) = parseSquare($to);
    
    $stepCol = ($toCol > $fromCol) ? 1 : (($toCol < $fromCol) ? -1 : 0);
    $stepRow = ($toRow > $fromRow) ? 1 : (($toRow < $fromRow) ? -1 : 0);
    
    $col = $fromCol + $stepCol;
    $row = $fromRow + $stepRow;
    
    while ($col !== $toCol || $row !== $toRow) {
        if (isset($board[toSquare($col, $row)])) return false;
        $col += $stepCol;
        $row += $stepRow;
    }
    return true;
}

/**
 * König finden
 */
function findKing($board, $color) {
    $kingPiece = ($color === 'white') ? 'wK' : 'bK';
    foreach ($board as $sq => $piece) {
        if ($piece === $kingPiece) return $sq;
    }
    return null;
}

/**
 * Prüfen ob im Schach
 */
function isInCheck($board, $color) {
    $kingSq = findKing($board, $color);
    if (!$kingSq) return false;
    $enemyColor = ($color === 'white') ? 'black' : 'white';
    return isSquareAttacked($board, $kingSq, $enemyColor);
}

/**
 * Prüfen ob Zug legal ist (lässt König nicht im Schach)
 */
function isMoveLegal($board, $from, $to, $color, $castling, $enPassant) {
    // Zug simulieren
    $testBoard = $board;
    $piece = $testBoard[$from];
    unset($testBoard[$from]);
    
    // En Passant Schlag
    if (getPieceType($piece) === 'P' && $to === $enPassant) {
        $dir = ($color === 'white') ? -1 : 1;
        list($toCol, $toRow) = parseSquare($to);
        unset($testBoard[toSquare($toCol, $toRow + $dir)]);
    }
    
    $testBoard[$to] = $piece;
    
    // Prüfen ob eigener König im Schach
    return !isInCheck($testBoard, $color);
}

/**
 * Gültige Züge für eine Figur
 */
function getValidMoves($board, $from, $castling, $enPassant) {
    if (!isset($board[$from])) return [];
    
    $piece = $board[$from];
    $color = getPieceColor($piece);
    $type = getPieceType($piece);
    list($col, $row) = parseSquare($from);
    
    $moves = [];
    
    switch ($type) {
        case 'P': // Bauer
            $dir = ($color === 'white') ? 1 : -1;
            $startRow = ($color === 'white') ? 1 : 6;
            
            // Vorwärts
            $oneStep = toSquare($col, $row + $dir);
            if ($row + $dir >= 0 && $row + $dir <= 7 && !isset($board[$oneStep])) {
                $moves[] = $oneStep;
                // Doppelschritt
                if ($row === $startRow) {
                    $twoStep = toSquare($col, $row + 2 * $dir);
                    if (!isset($board[$twoStep])) $moves[] = $twoStep;
                }
            }
            // Schlagen diagonal
            foreach ([-1, 1] as $dc) {
                if ($col + $dc < 0 || $col + $dc > 7) continue;
                $diagSq = toSquare($col + $dc, $row + $dir);
                if ($row + $dir >= 0 && $row + $dir <= 7) {
                    if (isset($board[$diagSq]) && getPieceColor($board[$diagSq]) !== $color) {
                        $moves[] = $diagSq;
                    }
                    // En Passant
                    if ($diagSq === $enPassant) $moves[] = $diagSq;
                }
            }
            break;
            
        case 'N': // Springer
            $jumps = [[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]];
            foreach ($jumps as $j) {
                $nc = $col + $j[0];
                $nr = $row + $j[1];
                if ($nc >= 0 && $nc <= 7 && $nr >= 0 && $nr <= 7) {
                    $sq = toSquare($nc, $nr);
                    if (!isset($board[$sq]) || getPieceColor($board[$sq]) !== $color) {
                        $moves[] = $sq;
                    }
                }
            }
            break;
            
        case 'B': // Läufer
            $moves = array_merge($moves, getSlidingMoves($board, $col, $row, $color, [[1,1],[1,-1],[-1,1],[-1,-1]]));
            break;
            
        case 'R': // Turm
            $moves = array_merge($moves, getSlidingMoves($board, $col, $row, $color, [[0,1],[0,-1],[1,0],[-1,0]]));
            break;
            
        case 'Q': // Dame
            $moves = array_merge($moves, getSlidingMoves($board, $col, $row, $color, [[0,1],[0,-1],[1,0],[-1,0],[1,1],[1,-1],[-1,1],[-1,-1]]));
            break;
            
        case 'K': // König
            for ($dc = -1; $dc <= 1; $dc++) {
                for ($dr = -1; $dr <= 1; $dr++) {
                    if ($dc === 0 && $dr === 0) continue;
                    $nc = $col + $dc;
                    $nr = $row + $dr;
                    if ($nc >= 0 && $nc <= 7 && $nr >= 0 && $nr <= 7) {
                        $sq = toSquare($nc, $nr);
                        if (!isset($board[$sq]) || getPieceColor($board[$sq]) !== $color) {
                            $moves[] = $sq;
                        }
                    }
                }
            }
            // Rochade
            $enemyColor = ($color === 'white') ? 'black' : 'white';
            $homeRow = ($color === 'white') ? 0 : 7;
            $prefix = ($color === 'white') ? 'w' : 'b';
            
            if ($row === $homeRow && $col === 4 && !isInCheck($board, $color)) {
                // Königsseite
                if ($castling[$prefix.'K'] ?? false) {
                    $f = toSquare(5, $homeRow);
                    $g = toSquare(6, $homeRow);
                    if (!isset($board[$f]) && !isset($board[$g]) && 
                        !isSquareAttacked($board, $f, $enemyColor) && !isSquareAttacked($board, $g, $enemyColor)) {
                        $moves[] = $g;
                    }
                }
                // Damenseite
                if ($castling[$prefix.'Q'] ?? false) {
                    $d = toSquare(3, $homeRow);
                    $c = toSquare(2, $homeRow);
                    $b = toSquare(1, $homeRow);
                    if (!isset($board[$d]) && !isset($board[$c]) && !isset($board[$b]) &&
                        !isSquareAttacked($board, $d, $enemyColor) && !isSquareAttacked($board, $c, $enemyColor)) {
                        $moves[] = $c;
                    }
                }
            }
            break;
    }
    
    // Nur legale Züge (lassen König nicht im Schach)
    return array_filter($moves, function($to) use ($board, $from, $color, $castling, $enPassant) {
        return isMoveLegal($board, $from, $to, $color, $castling, $enPassant);
    });
}

function getSlidingMoves($board, $col, $row, $color, $directions) {
    $moves = [];
    foreach ($directions as $dir) {
        $nc = $col + $dir[0];
        $nr = $row + $dir[1];
        while ($nc >= 0 && $nc <= 7 && $nr >= 0 && $nr <= 7) {
            $sq = toSquare($nc, $nr);
            if (!isset($board[$sq])) {
                $moves[] = $sq;
            } elseif (getPieceColor($board[$sq]) !== $color) {
                $moves[] = $sq;
                break;
            } else {
                break;
            }
            $nc += $dir[0];
            $nr += $dir[1];
        }
    }
    return $moves;
}

/**
 * Prüfen ob Schachmatt oder Patt
 */
function checkGameEnd($board, $color, $castling, $enPassant) {
    // Prüfen ob irgendein legaler Zug möglich
    foreach ($board as $sq => $piece) {
        if (getPieceColor($piece) !== $color) continue;
        $moves = getValidMoves($board, $sq, $castling, $enPassant);
        if (count($moves) > 0) return null; // Kann ziehen
    }
    
    // Keine Züge möglich
    if (isInCheck($board, $color)) {
        return 'checkmate'; // Schachmatt
    } else {
        return 'stalemate'; // Patt
    }
}

/**
 * Zug ausführen
 */
function movePiece($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $from = strtolower($input['from'] ?? '');
    $to = strtolower($input['to'] ?? '');
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing') {
        return ['success' => false, 'error' => 'Spiel läuft nicht'];
    }
    
    // Warte auf Bauernumwandlung?
    if ($game['pending_promotion']) {
        return ['success' => false, 'error' => 'Bauernumwandlung ausstehend'];
    }
    
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['color'] !== $game['current_player']) {
        return ['success' => false, 'error' => 'Nicht am Zug'];
    }
    
    $board = json_decode($game['board'], true);
    $castling = json_decode($game['castling'], true);
    $enPassant = $game['en_passant'];
    $myColor = $player['color'];
    
    // Figur prüfen
    if (!isset($board[$from]) || getPieceColor($board[$from]) !== $myColor) {
        return ['success' => false, 'error' => 'Keine eigene Figur'];
    }
    
    // Zug gültig?
    $validMoves = getValidMoves($board, $from, $castling, $enPassant);
    if (!in_array($to, $validMoves)) {
        return ['success' => false, 'error' => 'Ungültiger Zug'];
    }
    
    $piece = $board[$from];
    $pieceType = getPieceType($piece);
    $captured = isset($board[$to]);
    $newEnPassant = null;
    
    // Zug ausführen
    unset($board[$from]);
    
    // Spezialzüge
    list($fromCol, $fromRow) = parseSquare($from);
    list($toCol, $toRow) = parseSquare($to);
    
    // Rochade
    if ($pieceType === 'K' && abs($toCol - $fromCol) === 2) {
        $homeRow = ($myColor === 'white') ? 0 : 7;
        if ($toCol === 6) { // Königsseite
            $board[toSquare(5, $homeRow)] = $board[toSquare(7, $homeRow)];
            unset($board[toSquare(7, $homeRow)]);
        } else { // Damenseite
            $board[toSquare(3, $homeRow)] = $board[toSquare(0, $homeRow)];
            unset($board[toSquare(0, $homeRow)]);
        }
    }
    
    // En Passant schlagen
    if ($pieceType === 'P' && $to === $enPassant) {
        $dir = ($myColor === 'white') ? -1 : 1;
        unset($board[toSquare($toCol, $toRow + $dir)]);
        $captured = true;
    }
    
    // En Passant Feld setzen bei Doppelschritt
    if ($pieceType === 'P' && abs($toRow - $fromRow) === 2) {
        $newEnPassant = toSquare($fromCol, ($fromRow + $toRow) / 2);
    }
    
    // Figur setzen
    $board[$to] = $piece;
    
    // Bauernumwandlung?
    $promotionRow = ($myColor === 'white') ? 7 : 0;
    if ($pieceType === 'P' && $toRow === $promotionRow) {
        $pendingPromotion = $to;
        $boardJson = json_encode($board);
        $db->exec("UPDATE games SET board = '$boardJson', pending_promotion = '$pendingPromotion' WHERE id = $gameId");
        return ['success' => true, 'need_promotion' => true];
    }
    
    // Rochaderechte aktualisieren
    $prefix = ($myColor === 'white') ? 'w' : 'b';
    if ($pieceType === 'K') {
        $castling[$prefix.'K'] = false;
        $castling[$prefix.'Q'] = false;
    }
    if ($pieceType === 'R') {
        if ($from === 'a1') $castling['wQ'] = false;
        if ($from === 'h1') $castling['wK'] = false;
        if ($from === 'a8') $castling['bQ'] = false;
        if ($from === 'h8') $castling['bK'] = false;
    }
    
    // Nächster Spieler
    $nextPlayer = ($myColor === 'white') ? 'black' : 'white';
    
    // Schach prüfen
    $checkState = null;
    if (isInCheck($board, $nextPlayer)) {
        $checkState = 'check';
    }
    
    // Spielende prüfen
    $gameEnd = checkGameEnd($board, $nextPlayer, $castling, $newEnPassant);
    $winner = null;
    $winReason = null;
    $status = 'playing';
    
    if ($gameEnd === 'checkmate') {
        $winner = $myColor;
        $winReason = 'checkmate';
        $status = 'finished';
    } elseif ($gameEnd === 'stalemate') {
        $winReason = 'stalemate';
        $status = 'finished';
    }
    
    // Speichern
    $boardJson = json_encode($board);
    $castlingJson = json_encode($castling);
    $enPassantSql = $newEnPassant ? "'$newEnPassant'" : "NULL";
    $checkStateSql = $checkState ? "'$checkState'" : "NULL";
    $winnerSql = $winner ? "'$winner'" : "NULL";
    $winReasonSql = $winReason ? "'$winReason'" : "NULL";
    
    $db->exec("UPDATE games SET 
        board = '$boardJson', 
        castling = '$castlingJson',
        en_passant = $enPassantSql,
        current_player = '$nextPlayer', 
        check_state = $checkStateSql,
        winner = $winnerSql,
        win_reason = $winReasonSql,
        status = '$status',
        updated_at = CURRENT_TIMESTAMP 
        WHERE id = $gameId");
    
    return [
        'success' => true, 
        'captured' => $captured,
        'check' => $checkState === 'check',
        'checkmate' => $gameEnd === 'checkmate',
        'stalemate' => $gameEnd === 'stalemate'
    ];
}

/**
 * Bauernumwandlung
 */
function promotePawn($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $promoteTo = strtoupper($input['piece'] ?? 'Q'); // Q, R, B, N
    
    if (!in_array($promoteTo, ['Q', 'R', 'B', 'N'])) {
        return ['success' => false, 'error' => 'Ungültige Figur'];
    }
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || !$game['pending_promotion']) {
        return ['success' => false, 'error' => 'Keine Umwandlung ausstehend'];
    }
    
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['color'] !== $game['current_player']) {
        return ['success' => false, 'error' => 'Nicht am Zug'];
    }
    
    $board = json_decode($game['board'], true);
    $castling = json_decode($game['castling'], true);
    $promotionSquare = $game['pending_promotion'];
    $myColor = $player['color'];
    $prefix = ($myColor === 'white') ? 'w' : 'b';
    
    // Bauer umwandeln
    $board[$promotionSquare] = $prefix . $promoteTo;
    
    // Nächster Spieler
    $nextPlayer = ($myColor === 'white') ? 'black' : 'white';
    
    // Schach prüfen
    $checkState = null;
    if (isInCheck($board, $nextPlayer)) {
        $checkState = 'check';
    }
    
    // Spielende prüfen
    $gameEnd = checkGameEnd($board, $nextPlayer, $castling, null);
    $winner = null;
    $winReason = null;
    $status = 'playing';
    
    if ($gameEnd === 'checkmate') {
        $winner = $myColor;
        $winReason = 'checkmate';
        $status = 'finished';
    } elseif ($gameEnd === 'stalemate') {
        $winReason = 'stalemate';
        $status = 'finished';
    }
    
    $boardJson = json_encode($board);
    $checkStateSql = $checkState ? "'$checkState'" : "NULL";
    $winnerSql = $winner ? "'$winner'" : "NULL";
    $winReasonSql = $winReason ? "'$winReason'" : "NULL";
    
    $db->exec("UPDATE games SET 
        board = '$boardJson', 
        pending_promotion = NULL,
        current_player = '$nextPlayer', 
        check_state = $checkStateSql,
        winner = $winnerSql,
        win_reason = $winReasonSql,
        status = '$status',
        updated_at = CURRENT_TIMESTAMP 
        WHERE id = $gameId");
    
    return ['success' => true, 'promoted_to' => $promoteTo];
}

/**
 * Aufgeben
 */
function resignGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    $player = $db->querySingle("SELECT color FROM players WHERE id = $playerId", true);
    if (!$player) return ['success' => false, 'error' => 'Spieler nicht gefunden'];
    
    $winner = ($player['color'] === 'white') ? 'black' : 'white';
    
    $db->exec("UPDATE games SET status = 'finished', winner = '$winner', win_reason = 'resignation' WHERE id = $gameId");
    
    return ['success' => true, 'winner' => $winner];
}

/**
 * Spiel verlassen
 */
function leaveGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    $player = $db->querySingle("SELECT color FROM players WHERE id = $playerId", true);
    $game = $db->querySingle("SELECT status FROM games WHERE id = $gameId", true);
    
    $db->exec("DELETE FROM players WHERE game_id = $gameId AND id = $playerId");
    
    $remaining = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    
    if ($remaining == 0) {
        $db->exec("DELETE FROM games WHERE id = $gameId");
    } elseif ($game && $game['status'] === 'playing' && $player) {
        $winner = ($player['color'] === 'white') ? 'black' : 'white';
        $db->exec("UPDATE games SET status = 'finished', winner = '$winner', win_reason = 'abandonment' WHERE id = $gameId");
    }
    
    return ['success' => true];
}
