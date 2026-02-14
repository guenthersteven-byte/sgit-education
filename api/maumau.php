<?php
/**
 * ============================================================================
 * sgiT Education - Mau Mau API v1.0
 * ============================================================================
 * 
 * REST API für das Kartenspiel Mau Mau
 * 
 * Regeln:
 * - Gleiche Farbe oder gleicher Wert legen
 * - 7: Nächster zieht 2 (stapelbar)
 * - 8: Nächster aussetzen
 * - Bube: Wunschfarbe
 * - Ass: Richtungswechsel (bei 2 Spielern = nochmal)
 * - Letzte Karte: "Mau" sagen, sonst 2 Strafkarten
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

// Datenbank
$dbPath = dirname(__DIR__) . '/wallet/maumau.db';
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
        case 'play':
            echo json_encode(playCard($db, $input));
            break;
        case 'draw':
            echo json_encode(drawCard($db, $input));
            break;
        case 'mau':
            echo json_encode(sayMau($db, $input));
            break;
        case 'leave':
            echo json_encode(leaveGame($db, $input));
            break;
        default:
            echo json_encode(['error' => 'Unknown action']);
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
    $db->exec("CREATE TABLE IF NOT EXISTS games (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_code TEXT UNIQUE NOT NULL,
        host_id INTEGER NOT NULL,
        status TEXT DEFAULT 'waiting',
        current_player INTEGER DEFAULT 0,
        direction INTEGER DEFAULT 1,
        draw_stack INTEGER DEFAULT 0,
        skip_next INTEGER DEFAULT 0,
        wish_color TEXT,
        deck TEXT,
        discard_pile TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS players (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_id INTEGER NOT NULL,
        wallet_child_id INTEGER,
        player_name TEXT NOT NULL,
        avatar TEXT DEFAULT '😀',
        player_order INTEGER NOT NULL,
        is_host INTEGER DEFAULT 0,
        hand TEXT DEFAULT '[]',
        said_mau INTEGER DEFAULT 0,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (game_id) REFERENCES games(id)
    )");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mau_code ON games(game_code)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mau_players ON players(game_id)");
}

// Karten: 32 Blatt (7-Ass in 4 Farben)
const COLORS = ['herz', 'karo', 'pik', 'kreuz'];
const VALUES = ['7', '8', '9', '10', 'bube', 'dame', 'koenig', 'ass'];

/**
 * Neues Kartendeck erstellen und mischen
 */
function createDeck() {
    $deck = [];
    foreach (['herz', 'karo', 'pik', 'kreuz'] as $color) {
        foreach (['7', '8', '9', '10', 'bube', 'dame', 'koenig', 'ass'] as $value) {
            $deck[] = ['color' => $color, 'value' => $value];
        }
    }
    shuffle($deck);
    return $deck;
}

/**
 * 6-stelligen Code generieren
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
    
    do {
        $gameCode = generateGameCode();
        $exists = $db->querySingle("SELECT COUNT(*) FROM games WHERE game_code = '$gameCode'");
    } while ($exists > 0);
    
    $stmt = $db->prepare("INSERT INTO games (game_code, host_id, deck, discard_pile) VALUES (?, 0, '[]', '[]')");
    $stmt->bindValue(1, $gameCode);
    $stmt->execute();
    
    $gameId = $db->lastInsertRowID();
    
    $stmt = $db->prepare("INSERT INTO players (game_id, wallet_child_id, player_name, avatar, player_order, is_host) VALUES (?, ?, ?, ?, 0, 1)");
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
        'player_id' => $playerId
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
    
    $game = $db->querySingle("SELECT id, status FROM games WHERE game_code = '$gameCode'", true);
    
    if (!$game) {
        return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    }
    if ($game['status'] !== 'waiting') {
        return ['success' => false, 'error' => 'Spiel hat bereits begonnen'];
    }
    
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = {$game['id']}");
    if ($playerCount >= 4) {
        return ['success' => false, 'error' => 'Spiel ist voll (max. 4)'];
    }
    
    $stmt = $db->prepare("INSERT INTO players (game_id, wallet_child_id, player_name, avatar, player_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $game['id']);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->bindValue(5, $playerCount);
    $stmt->execute();
    
    return [
        'success' => true,
        'game_id' => $game['id'],
        'player_id' => $db->lastInsertRowID()
    ];
}

/**
 * Spiel starten - Karten austeilen
 */
function startGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['host_id'] != $playerId) {
        return ['success' => false, 'error' => 'Nur Host kann starten'];
    }
    
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    if ($playerCount < 2) {
        return ['success' => false, 'error' => 'Min. 2 Spieler'];
    }
    
    // Deck erstellen
    $deck = createDeck();
    
    // Jedem Spieler 5 Karten geben
    $result = $db->query("SELECT id FROM players WHERE game_id = $gameId ORDER BY player_order");
    while ($player = $result->fetchArray()) {
        $hand = array_splice($deck, 0, 5);
        $handJson = json_encode($hand);
        $db->exec("UPDATE players SET hand = '$handJson', said_mau = 0 WHERE id = {$player['id']}");
    }
    
    // Erste Karte aufdecken (keine 7, 8, Bube, Ass als Startkarte)
    $startCard = null;
    foreach ($deck as $i => $card) {
        if (!in_array($card['value'], ['7', '8', 'bube', 'ass'])) {
            $startCard = $card;
            array_splice($deck, $i, 1);
            break;
        }
    }
    if (!$startCard) {
        $startCard = array_shift($deck);
    }
    
    $deckJson = json_encode($deck);
    $discardJson = json_encode([$startCard]);
    
    $db->exec("UPDATE games SET status = 'playing', deck = '$deckJson', discard_pile = '$discardJson', current_player = 0, updated_at = CURRENT_TIMESTAMP WHERE id = $gameId");
    
    return ['success' => true, 'message' => 'Spiel gestartet!'];
}

/**
 * Spielstatus abrufen
 */
function getGameStatus($db, $params) {
    $gameId = $params['game_id'] ?? 0;
    $playerId = $params['player_id'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game) {
        return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    }
    
    $discardPile = json_decode($game['discard_pile'], true) ?: [];
    $topCard = end($discardPile) ?: null;
    
    // Spieler abrufen
    $players = [];
    $result = $db->query("SELECT * FROM players WHERE game_id = $gameId ORDER BY player_order");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $hand = json_decode($row['hand'], true) ?: [];
        $players[] = [
            'id' => $row['id'],
            'name' => $row['player_name'],
            'avatar' => $row['avatar'],
            'order' => $row['player_order'],
            'is_host' => $row['is_host'],
            'card_count' => count($hand),
            'said_mau' => $row['said_mau'],
            // Eigene Hand nur für diesen Spieler
            'hand' => ($row['id'] == $playerId) ? $hand : null
        ];
    }
    
    // Eigene Hand
    $myHand = [];
    $myTurn = false;
    foreach ($players as $p) {
        if ($p['id'] == $playerId) {
            $myHand = $p['hand'] ?: [];
            $myTurn = ($p['order'] == $game['current_player']);
        }
    }
    
    return [
        'success' => true,
        'game' => [
            'id' => $game['id'],
            'code' => $game['game_code'],
            'status' => $game['status'],
            'current_player' => $game['current_player'],
            'direction' => $game['direction'],
            'draw_stack' => $game['draw_stack'],
            'skip_next' => $game['skip_next'],
            'wish_color' => $game['wish_color'],
            'deck_count' => count(json_decode($game['deck'], true) ?: []),
            'top_card' => $topCard
        ],
        'players' => $players,
        'my_hand' => $myHand,
        'my_turn' => $myTurn,
        'is_host' => ($game['host_id'] == $playerId)
    ];
}

/**
 * Prüfen ob Karte gespielt werden kann
 */
function canPlayCard($card, $topCard, $wishColor, $drawStack) {
    // Bei 7er-Stack muss 7 gelegt werden oder gezogen
    if ($drawStack > 0 && $card['value'] !== '7') {
        return false;
    }
    
    // Bube geht immer (außer auf Bube)
    if ($card['value'] === 'bube' && $topCard['value'] !== 'bube') {
        return true;
    }
    
    // Bei Wunschfarbe nur diese Farbe
    if ($wishColor) {
        return $card['color'] === $wishColor || $card['value'] === 'bube';
    }
    
    // Gleiche Farbe oder gleicher Wert
    return $card['color'] === $topCard['color'] || $card['value'] === $topCard['value'];
}

/**
 * Karte spielen
 */
function playCard($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $cardIndex = $input['card_index'] ?? -1;
    $wishColor = $input['wish_color'] ?? null;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing') {
        return ['success' => false, 'error' => 'Spiel läuft nicht'];
    }
    
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['player_order'] != $game['current_player']) {
        return ['success' => false, 'error' => 'Du bist nicht am Zug'];
    }
    
    $hand = json_decode($player['hand'], true);
    if ($cardIndex < 0 || $cardIndex >= count($hand)) {
        return ['success' => false, 'error' => 'Ungültige Karte'];
    }
    
    $discardPile = json_decode($game['discard_pile'], true);
    $topCard = end($discardPile);
    $card = $hand[$cardIndex];
    
    if (!canPlayCard($card, $topCard, $game['wish_color'], $game['draw_stack'])) {
        return ['success' => false, 'error' => 'Karte passt nicht'];
    }
    
    // Karte aus Hand entfernen
    array_splice($hand, $cardIndex, 1);
    $handJson = json_encode($hand);
    
    // Auf Ablagestapel
    $discardPile[] = $card;
    $discardJson = json_encode($discardPile);
    
    // Spezialeffekte
    $drawStack = $game['draw_stack'];
    $skipNext = 0;
    $direction = $game['direction'];
    $newWishColor = null;
    
    switch ($card['value']) {
        case '7':
            $drawStack += 2;
            break;
        case '8':
            $skipNext = 1;
            break;
        case 'bube':
            if ($wishColor) {
                $newWishColor = $wishColor;
            } else {
                return ['success' => false, 'error' => 'Wunschfarbe wählen', 'need_wish' => true];
            }
            break;
        case 'ass':
            $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
            if ($playerCount > 2) {
                $direction *= -1;
            } else {
                $skipNext = 1; // Bei 2 Spielern = nochmal
            }
            break;
    }
    
    // Mau-Check zurücksetzen wenn mehr als 1 Karte
    $saidMau = (count($hand) <= 1) ? $player['said_mau'] : 0;
    
    // Hand speichern
    $db->exec("UPDATE players SET hand = '$handJson', said_mau = $saidMau WHERE id = $playerId");
    
    // Gewonnen?
    if (count($hand) === 0) {
        $db->exec("UPDATE games SET status = 'finished', discard_pile = '$discardJson', updated_at = CURRENT_TIMESTAMP WHERE id = $gameId");
        return ['success' => true, 'winner' => true, 'player_name' => $player['player_name']];
    }
    
    // Nächster Spieler
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    $nextPlayer = ($game['current_player'] + $direction + $playerCount) % $playerCount;
    if ($skipNext) {
        $nextPlayer = ($nextPlayer + $direction + $playerCount) % $playerCount;
    }
    
    $wishColorSql = $newWishColor ? "'$newWishColor'" : "NULL";
    $db->exec("UPDATE games SET 
        discard_pile = '$discardJson', 
        current_player = $nextPlayer, 
        direction = $direction, 
        draw_stack = $drawStack, 
        skip_next = 0,
        wish_color = $wishColorSql,
        updated_at = CURRENT_TIMESTAMP 
        WHERE id = $gameId");
    
    return ['success' => true, 'card' => $card, 'cards_left' => count($hand)];
}

/**
 * Karte(n) ziehen
 */
function drawCard($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing') {
        return ['success' => false, 'error' => 'Spiel läuft nicht'];
    }
    
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['player_order'] != $game['current_player']) {
        return ['success' => false, 'error' => 'Du bist nicht am Zug'];
    }
    
    $deck = json_decode($game['deck'], true);
    $hand = json_decode($player['hand'], true);
    $discardPile = json_decode($game['discard_pile'], true);
    
    // Wie viele ziehen?
    $drawCount = max(1, $game['draw_stack']);
    
    // Deck auffüllen wenn nötig
    while (count($deck) < $drawCount && count($discardPile) > 1) {
        $topCard = array_pop($discardPile);
        $deck = array_merge($deck, $discardPile);
        shuffle($deck);
        $discardPile = [$topCard];
    }
    
    // Karten ziehen
    $drawn = [];
    for ($i = 0; $i < $drawCount && count($deck) > 0; $i++) {
        $card = array_shift($deck);
        $hand[] = $card;
        $drawn[] = $card;
    }
    
    $handJson = json_encode($hand);
    $deckJson = json_encode($deck);
    $discardJson = json_encode($discardPile);
    
    $db->exec("UPDATE players SET hand = '$handJson', said_mau = 0 WHERE id = $playerId");
    
    // Nächster Spieler
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    $nextPlayer = ($game['current_player'] + $game['direction'] + $playerCount) % $playerCount;
    
    $db->exec("UPDATE games SET 
        deck = '$deckJson', 
        discard_pile = '$discardJson',
        current_player = $nextPlayer, 
        draw_stack = 0,
        wish_color = NULL,
        updated_at = CURRENT_TIMESTAMP 
        WHERE id = $gameId");
    
    return ['success' => true, 'drawn' => count($drawn), 'cards' => $drawn];
}

/**
 * "Mau" sagen (bei 2 Karten, vor dem Legen der vorletzten)
 */
function sayMau($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player) {
        return ['success' => false, 'error' => 'Spieler nicht gefunden'];
    }
    
    $hand = json_decode($player['hand'], true);
    
    // Mau bei 2 Karten sagen
    if (count($hand) === 2) {
        $db->exec("UPDATE players SET said_mau = 1 WHERE id = $playerId");
        return ['success' => true, 'message' => 'Mau! 🎉'];
    }
    
    // Mau Mau bei 1 Karte
    if (count($hand) === 1) {
        return ['success' => true, 'message' => 'Mau Mau! 🎉🎉'];
    }
    
    return ['success' => false, 'error' => 'Zu viele Karten für Mau'];
}

/**
 * Mau vergessen - Strafe prüfen (wird beim nächsten Zug des Spielers geprüft)
 */
function checkMauPenalty($db, $gameId, $playerId) {
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    $hand = json_decode($player['hand'], true);
    
    // Hat nur noch 1 Karte aber kein Mau gesagt?
    if (count($hand) === 1 && !$player['said_mau']) {
        // 2 Strafkarten
        $game = $db->querySingle("SELECT deck FROM games WHERE id = $gameId", true);
        $deck = json_decode($game['deck'], true);
        
        if (count($deck) >= 2) {
            $hand[] = array_shift($deck);
            $hand[] = array_shift($deck);
            
            $handJson = json_encode($hand);
            $deckJson = json_encode($deck);
            
            $db->exec("UPDATE players SET hand = '$handJson' WHERE id = $playerId");
            $db->exec("UPDATE games SET deck = '$deckJson' WHERE id = $gameId");
            
            return true; // Strafe erteilt
        }
    }
    
    return false;
}

/**
 * Spiel verlassen
 */
function leaveGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    // Spieler entfernen
    $db->exec("DELETE FROM players WHERE game_id = $gameId AND id = $playerId");
    
    $remaining = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    
    if ($remaining == 0) {
        $db->exec("DELETE FROM games WHERE id = $gameId");
    } elseif ($remaining == 1) {
        $db->exec("UPDATE games SET status = 'finished' WHERE id = $gameId");
    }
    
    return ['success' => true];
}
