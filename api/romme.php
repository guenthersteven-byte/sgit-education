<?php
/**
 * ============================================================================
 * sgiT Education - Rommé API v1.0
 * ============================================================================
 * 
 * REST API für das Kartenspiel Rommé
 * 
 * Regeln:
 * - 2x52 Karten + 2 Joker = 108 Karten
 * - 2-4 Spieler, je 13 Karten
 * - Sätze: 3-4 Karten gleichen Werts
 * - Reihen: 3+ Karten gleicher Farbe in Folge
 * - Erstauslage: min. 30 Punkte
 * - Joker ersetzt jede Karte
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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$dbPath = dirname(__DIR__) . '/wallet/romme.db';
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
        case 'draw': echo json_encode(drawCard($db, $input)); break;
        case 'meld': echo json_encode(meldCards($db, $input)); break;
        case 'layoff': echo json_encode(layoffCard($db, $input)); break;
        case 'discard': echo json_encode(discardCard($db, $input)); break;
        case 'leave': echo json_encode(leaveGame($db, $input)); break;
        default: echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    error_log("Romme API Error: " . $e->getMessage());
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
        current_player INTEGER DEFAULT 0,
        deck TEXT,
        discard_pile TEXT DEFAULT '[]',
        melds TEXT DEFAULT '[]',
        phase TEXT DEFAULT 'draw',
        winner INTEGER,
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
        has_melded INTEGER DEFAULT 0,
        score INTEGER DEFAULT 0,
        FOREIGN KEY (game_id) REFERENCES games(id)
    )");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_romme_code ON games(game_code)");
}

// Kartenwerte für Punkte
const CARD_POINTS = [
    '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7,
    '8' => 8, '9' => 9, '10' => 10, 'J' => 10, 'Q' => 10, 'K' => 10, 'A' => 11, 'JOKER' => 20
];

/**
 * Deck erstellen (2x52 + 2 Joker)
 */
function createDeck() {
    $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
    $values = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    
    $deck = [];
    // 2x Standardkarten
    for ($i = 0; $i < 2; $i++) {
        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $deck[] = ['suit' => $suit, 'value' => $value, 'id' => uniqid()];
            }
        }
    }
    // 2 Joker
    $deck[] = ['suit' => 'joker', 'value' => 'JOKER', 'id' => uniqid()];
    $deck[] = ['suit' => 'joker', 'value' => 'JOKER', 'id' => uniqid()];
    
    shuffle($deck);
    return $deck;
}

function generateGameCode() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function getCardPoints($card) {
    return CARD_POINTS[$card['value']] ?? 0;
}

function createGame($db, $input) {
    $playerName = $input['player_name'] ?? 'Spieler';
    $avatar = $input['avatar'] ?? '😀';
    $walletChildId = $input['wallet_child_id'] ?? null;
    
    do {
        $gameCode = generateGameCode();
        $exists = $db->querySingle("SELECT COUNT(*) FROM games WHERE game_code = '$gameCode'");
    } while ($exists > 0);
    
    $stmt = $db->prepare("INSERT INTO games (game_code, host_id, deck) VALUES (?, 0, '[]')");
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
    
    return ['success' => true, 'game_code' => $gameCode, 'game_id' => $gameId, 'player_id' => $playerId];
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
    if ($playerCount >= 4) return ['success' => false, 'error' => 'Spiel voll (max. 4)'];
    
    $stmt = $db->prepare("INSERT INTO players (game_id, wallet_child_id, player_name, avatar, player_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $game['id']);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->bindValue(5, $playerCount);
    $stmt->execute();
    
    return ['success' => true, 'game_id' => $game['id'], 'player_id' => $db->lastInsertRowID()];
}

function startGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['host_id'] != $playerId) return ['success' => false, 'error' => 'Nur Host'];
    
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    if ($playerCount < 2) return ['success' => false, 'error' => 'Min. 2 Spieler'];
    
    $deck = createDeck();
    
    // Jedem 13 Karten geben
    $result = $db->query("SELECT id FROM players WHERE game_id = $gameId ORDER BY player_order");
    while ($player = $result->fetchArray()) {
        $hand = array_splice($deck, 0, 13);
        $handJson = json_encode($hand);
        $db->exec("UPDATE players SET hand = '$handJson', has_melded = 0, score = 0 WHERE id = {$player['id']}");
    }
    
    // Erste Karte auf Ablagestapel
    $firstCard = array_shift($deck);
    $discardPile = [$firstCard];
    
    $deckJson = json_encode($deck);
    $discardJson = json_encode($discardPile);
    
    $db->exec("UPDATE games SET status = 'playing', deck = '$deckJson', discard_pile = '$discardJson', melds = '[]', current_player = 0, phase = 'draw' WHERE id = $gameId");
    
    return ['success' => true];
}

function getGameStatus($db, $params) {
    $gameId = $params['game_id'] ?? 0;
    $playerId = $params['player_id'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game) return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    
    $deck = json_decode($game['deck'], true) ?: [];
    $discardPile = json_decode($game['discard_pile'], true) ?: [];
    $melds = json_decode($game['melds'], true) ?: [];
    
    $players = [];
    $myOrder = -1;
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
            'has_melded' => $row['has_melded'],
            'score' => $row['score'],
            'hand' => ($row['id'] == $playerId) ? $hand : null
        ];
        if ($row['id'] == $playerId) $myOrder = $row['player_order'];
    }
    
    return [
        'success' => true,
        'game' => [
            'id' => $game['id'],
            'code' => $game['game_code'],
            'status' => $game['status'],
            'current_player' => $game['current_player'],
            'phase' => $game['phase'],
            'deck_count' => count($deck),
            'top_discard' => end($discardPile) ?: null,
            'winner' => $game['winner']
        ],
        'melds' => $melds,
        'players' => $players,
        'my_turn' => ($myOrder === (int)$game['current_player']),
        'is_host' => ($game['host_id'] == $playerId)
    ];
}

/**
 * Karte ziehen (vom Stapel oder Ablagestapel)
 */
function drawCard($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $source = $input['source'] ?? 'deck'; // 'deck' oder 'discard'
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing') return ['success' => false, 'error' => 'Spiel läuft nicht'];
    if ($game['phase'] !== 'draw') return ['success' => false, 'error' => 'Nicht in der Zieh-Phase'];
    
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['player_order'] != $game['current_player']) {
        return ['success' => false, 'error' => 'Nicht am Zug'];
    }
    
    $deck = json_decode($game['deck'], true);
    $discardPile = json_decode($game['discard_pile'], true);
    $hand = json_decode($player['hand'], true);
    
    if ($source === 'discard') {
        if (empty($discardPile)) return ['success' => false, 'error' => 'Ablagestapel leer'];
        $card = array_pop($discardPile);
    } else {
        if (empty($deck)) {
            // Deck neu mischen aus Ablagestapel
            if (count($discardPile) <= 1) return ['success' => false, 'error' => 'Keine Karten mehr'];
            $topCard = array_pop($discardPile);
            $deck = $discardPile;
            shuffle($deck);
            $discardPile = [$topCard];
        }
        $card = array_shift($deck);
    }
    
    $hand[] = $card;
    
    $handJson = json_encode($hand);
    $deckJson = json_encode($deck);
    $discardJson = json_encode($discardPile);
    
    $db->exec("UPDATE players SET hand = '$handJson' WHERE id = $playerId");
    $db->exec("UPDATE games SET deck = '$deckJson', discard_pile = '$discardJson', phase = 'play' WHERE id = $gameId");
    
    return ['success' => true, 'card' => $card];
}

/**
 * Kombination auslegen (Satz oder Reihe)
 */
function meldCards($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $cardIds = $input['card_ids'] ?? []; // Array von Karten-IDs
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing') return ['success' => false, 'error' => 'Spiel läuft nicht'];
    if ($game['phase'] !== 'play') return ['success' => false, 'error' => 'Erst Karte ziehen'];
    
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['player_order'] != $game['current_player']) {
        return ['success' => false, 'error' => 'Nicht am Zug'];
    }
    
    $hand = json_decode($player['hand'], true);
    $melds = json_decode($game['melds'], true);
    
    // Karten aus Hand finden
    $meldCards = [];
    $newHand = [];
    foreach ($hand as $card) {
        if (in_array($card['id'], $cardIds)) {
            $meldCards[] = $card;
        } else {
            $newHand[] = $card;
        }
    }
    
    if (count($meldCards) !== count($cardIds)) {
        return ['success' => false, 'error' => 'Karten nicht in der Hand'];
    }
    
    if (count($meldCards) < 3) {
        return ['success' => false, 'error' => 'Mindestens 3 Karten'];
    }
    
    // Prüfen ob gültige Kombination
    $validation = validateMeld($meldCards);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    
    // Erstauslage prüfen (min. 30 Punkte wenn erste Auslage)
    if (!$player['has_melded']) {
        $points = array_sum(array_map('getCardPoints', $meldCards));
        if ($points < 30) {
            return ['success' => false, 'error' => "Erstauslage min. 30 Punkte (hast $points)"];
        }
    }
    
    // Kombination hinzufügen
    $melds[] = [
        'cards' => $meldCards,
        'type' => $validation['type'],
        'owner' => $playerId
    ];
    
    $handJson = json_encode($newHand);
    $meldsJson = json_encode($melds);
    
    $db->exec("UPDATE players SET hand = '$handJson', has_melded = 1 WHERE id = $playerId");
    $db->exec("UPDATE games SET melds = '$meldsJson' WHERE id = $gameId");
    
    // Gewonnen?
    if (empty($newHand)) {
        return finishRound($db, $gameId, $playerId);
    }
    
    return ['success' => true, 'type' => $validation['type'], 'points' => array_sum(array_map('getCardPoints', $meldCards))];
}

/**
 * Kombination validieren
 */
function validateMeld($cards) {
    $nonJokers = array_filter($cards, fn($c) => $c['value'] !== 'JOKER');
    $jokerCount = count($cards) - count($nonJokers);
    
    if (count($nonJokers) === 0) {
        return ['valid' => false, 'error' => 'Nicht nur Joker'];
    }
    
    // Prüfe Satz (gleicher Wert, verschiedene Farben)
    $values = array_unique(array_column($nonJokers, 'value'));
    if (count($values) === 1) {
        $suits = array_column($nonJokers, 'suit');
        if (count($suits) === count(array_unique($suits)) && count($cards) <= 4) {
            return ['valid' => true, 'type' => 'set'];
        }
    }
    
    // Prüfe Reihe (gleiche Farbe, aufeinanderfolgende Werte)
    $suits = array_unique(array_column($nonJokers, 'suit'));
    if (count($suits) === 1) {
        $order = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        $positions = [];
        foreach ($nonJokers as $card) {
            $pos = array_search($card['value'], $order);
            if ($pos !== false) $positions[] = $pos;
        }
        sort($positions);
        
        // Lücken mit Joker füllen möglich?
        $minPos = min($positions);
        $maxPos = max($positions);
        $neededPositions = range($minPos, $maxPos);
        $missingCount = count($neededPositions) - count($positions);
        
        if ($missingCount <= $jokerCount && count($cards) === count($neededPositions)) {
            return ['valid' => true, 'type' => 'run'];
        }
    }
    
    return ['valid' => false, 'error' => 'Keine gültige Kombination'];
}

/**
 * Karte an bestehende Kombination anlegen
 */
function layoffCard($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $cardId = $input['card_id'] ?? '';
    $meldIndex = $input['meld_index'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing') return ['success' => false, 'error' => 'Spiel läuft nicht'];
    if ($game['phase'] !== 'play') return ['success' => false, 'error' => 'Erst Karte ziehen'];
    
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['player_order'] != $game['current_player']) {
        return ['success' => false, 'error' => 'Nicht am Zug'];
    }
    
    // Muss erst selbst ausgelegt haben
    if (!$player['has_melded']) {
        return ['success' => false, 'error' => 'Erst eigene Kombination auslegen'];
    }
    
    $hand = json_decode($player['hand'], true);
    $melds = json_decode($game['melds'], true);
    
    if (!isset($melds[$meldIndex])) {
        return ['success' => false, 'error' => 'Kombination nicht gefunden'];
    }
    
    // Karte aus Hand finden
    $cardToLayoff = null;
    $newHand = [];
    foreach ($hand as $card) {
        if ($card['id'] === $cardId && $cardToLayoff === null) {
            $cardToLayoff = $card;
        } else {
            $newHand[] = $card;
        }
    }
    
    if (!$cardToLayoff) {
        return ['success' => false, 'error' => 'Karte nicht in der Hand'];
    }
    
    // Prüfen ob Karte passt
    $meld = $melds[$meldIndex];
    $testCards = array_merge($meld['cards'], [$cardToLayoff]);
    $validation = validateMeld($testCards);
    
    if (!$validation['valid']) {
        return ['success' => false, 'error' => 'Karte passt nicht'];
    }
    
    // Karte hinzufügen
    $melds[$meldIndex]['cards'][] = $cardToLayoff;
    
    $handJson = json_encode($newHand);
    $meldsJson = json_encode($melds);
    
    $db->exec("UPDATE players SET hand = '$handJson' WHERE id = $playerId");
    $db->exec("UPDATE games SET melds = '$meldsJson' WHERE id = $gameId");
    
    // Gewonnen?
    if (empty($newHand)) {
        return finishRound($db, $gameId, $playerId);
    }
    
    return ['success' => true];
}

/**
 * Karte ablegen und Zug beenden
 */
function discardCard($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $cardId = $input['card_id'] ?? '';
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing') return ['success' => false, 'error' => 'Spiel läuft nicht'];
    if ($game['phase'] !== 'play') return ['success' => false, 'error' => 'Erst Karte ziehen'];
    
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['player_order'] != $game['current_player']) {
        return ['success' => false, 'error' => 'Nicht am Zug'];
    }
    
    $hand = json_decode($player['hand'], true);
    $discardPile = json_decode($game['discard_pile'], true);
    
    // Karte aus Hand finden
    $cardToDiscard = null;
    $newHand = [];
    foreach ($hand as $card) {
        if ($card['id'] === $cardId && $cardToDiscard === null) {
            $cardToDiscard = $card;
        } else {
            $newHand[] = $card;
        }
    }
    
    if (!$cardToDiscard) {
        return ['success' => false, 'error' => 'Karte nicht in der Hand'];
    }
    
    $discardPile[] = $cardToDiscard;
    
    // Gewonnen? (letzte Karte abgelegt)
    if (empty($newHand)) {
        $db->exec("UPDATE players SET hand = '[]' WHERE id = $playerId");
        return finishRound($db, $gameId, $playerId);
    }
    
    // Nächster Spieler
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    $nextPlayer = ($game['current_player'] + 1) % $playerCount;
    
    $handJson = json_encode($newHand);
    $discardJson = json_encode($discardPile);
    
    $db->exec("UPDATE players SET hand = '$handJson' WHERE id = $playerId");
    $db->exec("UPDATE games SET discard_pile = '$discardJson', current_player = $nextPlayer, phase = 'draw' WHERE id = $gameId");
    
    return ['success' => true, 'cards_left' => count($newHand)];
}

/**
 * Runde beenden - Punkte zählen
 */
function finishRound($db, $gameId, $winnerId) {
    $result = $db->query("SELECT * FROM players WHERE game_id = $gameId");
    $scores = [];
    
    while ($player = $result->fetchArray(SQLITE3_ASSOC)) {
        if ($player['id'] == $winnerId) {
            $scores[$player['id']] = 0;
        } else {
            $hand = json_decode($player['hand'], true) ?: [];
            $penalty = array_sum(array_map('getCardPoints', $hand));
            $scores[$player['id']] = -$penalty;
            $db->exec("UPDATE players SET score = score - $penalty WHERE id = {$player['id']}");
        }
    }
    
    $db->exec("UPDATE games SET status = 'finished', winner = $winnerId WHERE id = $gameId");
    
    return [
        'success' => true,
        'winner' => true,
        'scores' => $scores
    ];
}

/**
 * Spiel verlassen
 */
function leaveGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    $db->exec("DELETE FROM players WHERE game_id = $gameId AND id = $playerId");
    
    $remaining = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
    
    if ($remaining == 0) {
        $db->exec("DELETE FROM games WHERE id = $gameId");
    } elseif ($remaining == 1) {
        $winner = $db->querySingle("SELECT id FROM players WHERE game_id = $gameId");
        $db->exec("UPDATE games SET status = 'finished', winner = $winner WHERE id = $gameId");
    }
    
    return ['success' => true];
}
