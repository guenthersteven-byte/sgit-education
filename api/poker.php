<?php
/**
 * ============================================================================
 * sgiT Education - Poker (Texas Hold'em) API v1.0
 * ============================================================================
 * 
 * REST API für Texas Hold'em Poker
 * 
 * Regeln:
 * - 2-8 Spieler
 * - 2 Hole Cards pro Spieler
 * - 5 Community Cards (Flop 3, Turn 1, River 1)
 * - Blinds: Small Blind, Big Blind
 * - Betting Rounds: Pre-Flop, Flop, Turn, River
 * - Actions: Fold, Check, Call, Raise, All-In
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

$dbPath = dirname(__DIR__) . '/wallet/poker.db';
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
        case 'bet': echo json_encode(placeBet($db, $input)); break;
        case 'leave': echo json_encode(leaveGame($db, $input)); break;
        default: echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    error_log("Poker API Error: " . $e->getMessage());
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
        deck TEXT DEFAULT '[]',
        community_cards TEXT DEFAULT '[]',
        pot INTEGER DEFAULT 0,
        current_bet INTEGER DEFAULT 0,
        dealer_pos INTEGER DEFAULT 0,
        current_player INTEGER DEFAULT 0,
        round TEXT DEFAULT 'preflop',
        small_blind INTEGER DEFAULT 10,
        big_blind INTEGER DEFAULT 20,
        min_raise INTEGER DEFAULT 20,
        last_raiser INTEGER,
        winners TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS players (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        game_id INTEGER NOT NULL,
        wallet_child_id INTEGER,
        player_name TEXT NOT NULL,
        avatar TEXT DEFAULT '😀',
        seat INTEGER NOT NULL,
        is_host INTEGER DEFAULT 0,
        chips INTEGER DEFAULT 1000,
        hole_cards TEXT DEFAULT '[]',
        current_bet INTEGER DEFAULT 0,
        total_bet INTEGER DEFAULT 0,
        folded INTEGER DEFAULT 0,
        all_in INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        FOREIGN KEY (game_id) REFERENCES games(id)
    )");
    
    $db->exec("CREATE INDEX IF NOT EXISTS idx_poker_code ON games(game_code)");
}

/**
 * Deck erstellen
 */
function createDeck() {
    $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
    $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
    
    $deck = [];
    foreach ($suits as $suit) {
        foreach ($values as $value) {
            $deck[] = ['suit' => $suit, 'value' => $value];
        }
    }
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

function createGame($db, $input) {
    $playerName = $input['player_name'] ?? 'Spieler';
    $avatar = $input['avatar'] ?? '😀';
    $walletChildId = $input['wallet_child_id'] ?? null;
    $buyIn = $input['buy_in'] ?? 1000;
    
    do {
        $gameCode = generateGameCode();
        $exists = $db->querySingle("SELECT COUNT(*) FROM games WHERE game_code = '$gameCode'");
    } while ($exists > 0);
    
    $stmt = $db->prepare("INSERT INTO games (game_code, host_id) VALUES (?, 0)");
    $stmt->bindValue(1, $gameCode);
    $stmt->execute();
    
    $gameId = $db->lastInsertRowID();
    
    $stmt = $db->prepare("INSERT INTO players (game_id, wallet_child_id, player_name, avatar, seat, is_host, chips) VALUES (?, ?, ?, ?, 0, 1, ?)");
    $stmt->bindValue(1, $gameId);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->bindValue(5, $buyIn);
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
    $buyIn = $input['buy_in'] ?? 1000;
    
    $game = $db->querySingle("SELECT id, status FROM games WHERE game_code = '$gameCode'", true);
    if (!$game) return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    if ($game['status'] !== 'waiting') return ['success' => false, 'error' => 'Spiel läuft bereits'];
    
    $playerCount = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = {$game['id']}");
    if ($playerCount >= 8) return ['success' => false, 'error' => 'Spiel voll (max. 8)'];
    
    $stmt = $db->prepare("INSERT INTO players (game_id, wallet_child_id, player_name, avatar, seat, chips) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $game['id']);
    $stmt->bindValue(2, $walletChildId);
    $stmt->bindValue(3, $playerName);
    $stmt->bindValue(4, $avatar);
    $stmt->bindValue(5, $playerCount);
    $stmt->bindValue(6, $buyIn);
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
    
    // Neue Hand starten
    return startNewHand($db, $gameId);
}

function startNewHand($db, $gameId) {
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    $deck = createDeck();
    
    // Alle Spieler zurücksetzen
    $db->exec("UPDATE players SET hole_cards = '[]', current_bet = 0, total_bet = 0, folded = 0, all_in = 0, is_active = 1 WHERE game_id = $gameId AND chips > 0");
    
    // Aktive Spieler zählen
    $activePlayers = [];
    $result = $db->query("SELECT * FROM players WHERE game_id = $gameId AND chips > 0 ORDER BY seat");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $activePlayers[] = $row;
    }
    
    if (count($activePlayers) < 2) {
        // Spiel beendet - nur noch 1 Spieler mit Chips
        $winner = $activePlayers[0] ?? null;
        if ($winner) {
            $db->exec("UPDATE games SET status = 'finished', winners = '{$winner['id']}' WHERE id = $gameId");
        }
        return ['success' => true, 'game_over' => true];
    }
    
    $playerCount = count($activePlayers);
    
    // Dealer rotieren
    $newDealer = ($game['dealer_pos'] + 1) % $playerCount;
    
    // Blinds setzen
    $sbPos = ($newDealer + 1) % $playerCount;
    $bbPos = ($newDealer + 2) % $playerCount;
    
    $smallBlind = $game['small_blind'];
    $bigBlind = $game['big_blind'];
    
    // Small Blind
    $sbPlayer = $activePlayers[$sbPos];
    $sbAmount = min($smallBlind, $sbPlayer['chips']);
    $db->exec("UPDATE players SET chips = chips - $sbAmount, current_bet = $sbAmount, total_bet = $sbAmount WHERE id = {$sbPlayer['id']}");
    
    // Big Blind
    $bbPlayer = $activePlayers[$bbPos];
    $bbAmount = min($bigBlind, $bbPlayer['chips']);
    $db->exec("UPDATE players SET chips = chips - $bbAmount, current_bet = $bbAmount, total_bet = $bbAmount WHERE id = {$bbPlayer['id']}");
    
    $pot = $sbAmount + $bbAmount;
    
    // Hole Cards verteilen
    foreach ($activePlayers as $player) {
        $holeCards = [array_shift($deck), array_shift($deck)];
        $holeCardsJson = json_encode($holeCards);
        $db->exec("UPDATE players SET hole_cards = '$holeCardsJson' WHERE id = {$player['id']}");
    }
    
    // Erster Spieler nach Big Blind
    $firstPlayer = ($bbPos + 1) % $playerCount;
    
    $deckJson = json_encode($deck);
    $db->exec("UPDATE games SET 
        status = 'playing', 
        deck = '$deckJson', 
        community_cards = '[]', 
        pot = $pot, 
        current_bet = $bigBlind,
        dealer_pos = $newDealer,
        current_player = $firstPlayer,
        round = 'preflop',
        min_raise = $bigBlind,
        last_raiser = {$bbPlayer['id']},
        winners = NULL
        WHERE id = $gameId");
    
    return ['success' => true];
}

function getGameStatus($db, $params) {
    $gameId = $params['game_id'] ?? 0;
    $playerId = $params['player_id'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game) return ['success' => false, 'error' => 'Spiel nicht gefunden'];
    
    $communityCards = json_decode($game['community_cards'], true) ?: [];
    
    $players = [];
    $mySeat = -1;
    $result = $db->query("SELECT * FROM players WHERE game_id = $gameId ORDER BY seat");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $holeCards = json_decode($row['hole_cards'], true) ?: [];
        $isMe = ($row['id'] == $playerId);
        
        $players[] = [
            'id' => $row['id'],
            'name' => $row['player_name'],
            'avatar' => $row['avatar'],
            'seat' => $row['seat'],
            'chips' => $row['chips'],
            'current_bet' => $row['current_bet'],
            'total_bet' => $row['total_bet'],
            'folded' => (bool)$row['folded'],
            'all_in' => (bool)$row['all_in'],
            'is_active' => (bool)$row['is_active'],
            'is_host' => (bool)$row['is_host'],
            'hole_cards' => $isMe ? $holeCards : (($game['status'] === 'showdown' || !empty($game['winners'])) ? $holeCards : null),
            'card_count' => count($holeCards)
        ];
        
        if ($isMe) $mySeat = $row['seat'];
    }
    
    // Aktive Spieler (nicht gefoldet, haben Chips)
    $activePlayers = array_filter($players, fn($p) => !$p['folded'] && $p['is_active']);
    $activeCount = count($activePlayers);
    
    // Bin ich am Zug?
    $myTurn = false;
    if ($game['status'] === 'playing' && $mySeat >= 0) {
        $myTurn = ($mySeat == $game['current_player']);
    }
    
    // Mögliche Aktionen
    $actions = [];
    if ($myTurn && $game['status'] === 'playing') {
        $me = null;
        foreach ($players as $p) { if ($p['seat'] == $mySeat) $me = $p; }
        if ($me && !$me['folded'] && !$me['all_in']) {
            $actions[] = 'fold';
            $toCall = $game['current_bet'] - $me['current_bet'];
            if ($toCall <= 0) {
                $actions[] = 'check';
            } else {
                $actions[] = 'call';
            }
            if ($me['chips'] > $toCall) {
                $actions[] = 'raise';
            }
            $actions[] = 'allin';
        }
    }
    
    return [
        'success' => true,
        'game' => [
            'id' => $game['id'],
            'code' => $game['game_code'],
            'status' => $game['status'],
            'round' => $game['round'],
            'pot' => $game['pot'],
            'current_bet' => $game['current_bet'],
            'min_raise' => $game['min_raise'],
            'dealer_pos' => $game['dealer_pos'],
            'current_player' => $game['current_player'],
            'small_blind' => $game['small_blind'],
            'big_blind' => $game['big_blind'],
            'winners' => $game['winners'] ? json_decode($game['winners'], true) : null
        ],
        'community_cards' => $communityCards,
        'players' => $players,
        'my_seat' => $mySeat,
        'my_turn' => $myTurn,
        'actions' => $actions,
        'is_host' => ($game['host_id'] == $playerId)
    ];
}

function placeBet($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    $action = $input['action'] ?? ''; // fold, check, call, raise, allin
    $amount = $input['amount'] ?? 0;
    
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    if (!$game || $game['status'] !== 'playing') return ['success' => false, 'error' => 'Spiel läuft nicht'];
    
    $player = $db->querySingle("SELECT * FROM players WHERE id = $playerId", true);
    if (!$player || $player['seat'] != $game['current_player']) {
        return ['success' => false, 'error' => 'Nicht am Zug'];
    }
    if ($player['folded'] || $player['all_in']) {
        return ['success' => false, 'error' => 'Keine Aktion möglich'];
    }
    
    $toCall = $game['current_bet'] - $player['current_bet'];
    $pot = $game['pot'];
    $currentBet = $game['current_bet'];
    $minRaise = $game['min_raise'];
    $lastRaiser = $game['last_raiser'];
    
    switch ($action) {
        case 'fold':
            $db->exec("UPDATE players SET folded = 1 WHERE id = $playerId");
            break;
            
        case 'check':
            if ($toCall > 0) return ['success' => false, 'error' => 'Kann nicht checken'];
            break;
            
        case 'call':
            $callAmount = min($toCall, $player['chips']);
            $db->exec("UPDATE players SET chips = chips - $callAmount, current_bet = current_bet + $callAmount, total_bet = total_bet + $callAmount WHERE id = $playerId");
            $pot += $callAmount;
            if ($player['chips'] <= $callAmount) {
                $db->exec("UPDATE players SET all_in = 1 WHERE id = $playerId");
            }
            break;
            
        case 'raise':
            if ($amount < $minRaise) return ['success' => false, 'error' => "Min. Raise: $minRaise"];
            $totalBet = $currentBet + $amount;
            $totalToAdd = $totalBet - $player['current_bet'];
            if ($totalToAdd > $player['chips']) return ['success' => false, 'error' => 'Nicht genug Chips'];
            
            $db->exec("UPDATE players SET chips = chips - $totalToAdd, current_bet = $totalBet, total_bet = total_bet + $totalToAdd WHERE id = $playerId");
            $pot += $totalToAdd;
            $currentBet = $totalBet;
            $minRaise = $amount;
            $lastRaiser = $playerId;
            break;
            
        case 'allin':
            $allInAmount = $player['chips'];
            $newBet = $player['current_bet'] + $allInAmount;
            $db->exec("UPDATE players SET chips = 0, current_bet = $newBet, total_bet = total_bet + $allInAmount, all_in = 1 WHERE id = $playerId");
            $pot += $allInAmount;
            if ($newBet > $currentBet) {
                $raiseAmount = $newBet - $currentBet;
                if ($raiseAmount >= $minRaise) {
                    $minRaise = $raiseAmount;
                    $lastRaiser = $playerId;
                }
                $currentBet = $newBet;
            }
            break;
            
        default:
            return ['success' => false, 'error' => 'Unbekannte Aktion'];
    }
    
    // Nächsten Spieler finden
    $nextResult = findNextPlayer($db, $gameId, $player['seat'], $lastRaiser);
    
    if ($nextResult['round_complete']) {
        // Runde beenden, nächste Phase
        advanceRound($db, $gameId, $pot, $currentBet, $minRaise);
    } else {
        $db->exec("UPDATE games SET pot = $pot, current_bet = $currentBet, min_raise = $minRaise, last_raiser = " . ($lastRaiser ?: 'NULL') . ", current_player = {$nextResult['next_seat']} WHERE id = $gameId");
    }
    
    return ['success' => true, 'action' => $action];
}

function findNextPlayer($db, $gameId, $currentSeat, $lastRaiser) {
    $players = [];
    $result = $db->query("SELECT * FROM players WHERE game_id = $gameId ORDER BY seat");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $players[$row['seat']] = $row;
    }
    
    $playerCount = count($players);
    $activePlayers = array_filter($players, fn($p) => !$p['folded'] && $p['is_active']);
    
    // Nur noch ein Spieler übrig?
    if (count($activePlayers) <= 1) {
        return ['round_complete' => true, 'next_seat' => -1];
    }
    
    // Nächsten aktiven Spieler finden
    for ($i = 1; $i <= $playerCount; $i++) {
        $nextSeat = ($currentSeat + $i) % $playerCount;
        if (isset($players[$nextSeat])) {
            $p = $players[$nextSeat];
            // Zurück beim Raiser? Runde vorbei
            if ($p['id'] == $lastRaiser) {
                return ['round_complete' => true, 'next_seat' => $nextSeat];
            }
            // Kann der Spieler noch agieren?
            if (!$p['folded'] && !$p['all_in'] && $p['is_active'] && $p['chips'] > 0) {
                return ['round_complete' => false, 'next_seat' => $nextSeat];
            }
        }
    }
    
    // Alle haben gecallt oder sind all-in
    return ['round_complete' => true, 'next_seat' => -1];
}

function advanceRound($db, $gameId, $pot, $currentBet, $minRaise) {
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    $deck = json_decode($game['deck'], true);
    $communityCards = json_decode($game['community_cards'], true);
    
    // Spieler current_bet zurücksetzen
    $db->exec("UPDATE players SET current_bet = 0 WHERE game_id = $gameId");
    
    // Aktive Spieler zählen (nicht gefoldet)
    $activePlayers = [];
    $result = $db->query("SELECT * FROM players WHERE game_id = $gameId AND folded = 0 ORDER BY seat");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $activePlayers[] = $row;
    }
    
    // Nur noch 1 Spieler? Gewonnen!
    if (count($activePlayers) == 1) {
        $winner = $activePlayers[0];
        $db->exec("UPDATE players SET chips = chips + $pot WHERE id = {$winner['id']}");
        $winners = json_encode([['id' => $winner['id'], 'amount' => $pot, 'hand' => 'Letzter Spieler']]);
        $db->exec("UPDATE games SET status = 'finished', pot = 0, winners = '$winners' WHERE id = $gameId");
        return;
    }
    
    // Nächste Runde
    $round = $game['round'];
    $newRound = '';
    
    switch ($round) {
        case 'preflop':
            // Flop: 3 Karten
            $communityCards[] = array_shift($deck);
            $communityCards[] = array_shift($deck);
            $communityCards[] = array_shift($deck);
            $newRound = 'flop';
            break;
        case 'flop':
            // Turn: 1 Karte
            $communityCards[] = array_shift($deck);
            $newRound = 'turn';
            break;
        case 'turn':
            // River: 1 Karte
            $communityCards[] = array_shift($deck);
            $newRound = 'river';
            break;
        case 'river':
            // Showdown
            determineWinner($db, $gameId, $pot);
            return;
    }
    
    // Erster Spieler nach Dealer
    $dealerPos = $game['dealer_pos'];
    $firstPlayer = -1;
    for ($i = 1; $i <= count($activePlayers); $i++) {
        $seat = ($dealerPos + $i) % count($activePlayers);
        foreach ($activePlayers as $p) {
            if ($p['seat'] == $seat && !$p['all_in'] && $p['chips'] > 0) {
                $firstPlayer = $seat;
                break 2;
            }
        }
    }
    
    // Alle sind all-in? Direkt zum Showdown
    if ($firstPlayer == -1) {
        // Restliche Karten aufdecken
        while (count($communityCards) < 5) {
            $communityCards[] = array_shift($deck);
        }
        $communityJson = json_encode($communityCards);
        $deckJson = json_encode($deck);
        $db->exec("UPDATE games SET deck = '$deckJson', community_cards = '$communityJson', round = 'river' WHERE id = $gameId");
        determineWinner($db, $gameId, $pot);
        return;
    }
    
    $communityJson = json_encode($communityCards);
    $deckJson = json_encode($deck);
    $db->exec("UPDATE games SET 
        deck = '$deckJson', 
        community_cards = '$communityJson', 
        pot = $pot, 
        current_bet = 0, 
        round = '$newRound',
        current_player = $firstPlayer,
        last_raiser = NULL
        WHERE id = $gameId");
}

/**
 * Gewinner ermitteln
 */
function determineWinner($db, $gameId, $pot) {
    $game = $db->querySingle("SELECT * FROM games WHERE id = $gameId", true);
    $communityCards = json_decode($game['community_cards'], true);
    
    $players = [];
    $result = $db->query("SELECT * FROM players WHERE game_id = $gameId AND folded = 0 ORDER BY seat");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $holeCards = json_decode($row['hole_cards'], true);
        $allCards = array_merge($holeCards, $communityCards);
        $hand = evaluateHand($allCards);
        $players[] = [
            'id' => $row['id'],
            'name' => $row['player_name'],
            'hand' => $hand,
            'hole_cards' => $holeCards
        ];
    }
    
    // Nach Handstärke sortieren
    usort($players, function($a, $b) {
        if ($a['hand']['rank'] !== $b['hand']['rank']) {
            return $b['hand']['rank'] - $a['hand']['rank'];
        }
        // Bei gleichem Rang: Kicker vergleichen
        for ($i = 0; $i < count($a['hand']['kickers']); $i++) {
            if (($a['hand']['kickers'][$i] ?? 0) !== ($b['hand']['kickers'][$i] ?? 0)) {
                return ($b['hand']['kickers'][$i] ?? 0) - ($a['hand']['kickers'][$i] ?? 0);
            }
        }
        return 0;
    });
    
    // Gewinner (können mehrere sein bei Split)
    $winners = [];
    $bestHand = $players[0]['hand'];
    foreach ($players as $p) {
        if ($p['hand']['rank'] === $bestHand['rank'] && $p['hand']['kickers'] === $bestHand['kickers']) {
            $winners[] = $p;
        } else {
            break;
        }
    }
    
    // Pot aufteilen
    $share = intval($pot / count($winners));
    $winnersData = [];
    foreach ($winners as $w) {
        $db->exec("UPDATE players SET chips = chips + $share WHERE id = {$w['id']}");
        $winnersData[] = ['id' => $w['id'], 'amount' => $share, 'hand' => $w['hand']['name']];
    }
    
    $winnersJson = json_encode($winnersData);
    $db->exec("UPDATE games SET status = 'finished', pot = 0, winners = '$winnersJson' WHERE id = $gameId");
}

/**
 * Hand bewerten (gibt Rang und Kicker zurück)
 */
function evaluateHand($cards) {
    $valueOrder = ['2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'10'=>10,'J'=>11,'Q'=>12,'K'=>13,'A'=>14];
    
    // Alle 7 Karten nach Wert sortieren
    usort($cards, fn($a, $b) => $valueOrder[$b['value']] - $valueOrder[$a['value']]);
    
    // Werte und Farben sammeln
    $values = array_map(fn($c) => $valueOrder[$c['value']], $cards);
    $suits = array_map(fn($c) => $c['suit'], $cards);
    
    // Zählen
    $valueCounts = array_count_values($values);
    $suitCounts = array_count_values($suits);
    
    arsort($valueCounts);
    
    // Flush prüfen
    $flushSuit = null;
    foreach ($suitCounts as $suit => $count) {
        if ($count >= 5) { $flushSuit = $suit; break; }
    }
    
    // Straight prüfen
    $uniqueValues = array_unique($values);
    sort($uniqueValues);
    $straight = findStraight($uniqueValues);
    
    // Straight Flush / Royal Flush
    if ($flushSuit) {
        $flushCards = array_filter($cards, fn($c) => $c['suit'] === $flushSuit);
        $flushValues = array_unique(array_map(fn($c) => $valueOrder[$c['value']], $flushCards));
        sort($flushValues);
        $straightFlush = findStraight($flushValues);
        
        if ($straightFlush) {
            if ($straightFlush === 14) {
                return ['rank' => 10, 'name' => 'Royal Flush', 'kickers' => [14]];
            }
            return ['rank' => 9, 'name' => 'Straight Flush', 'kickers' => [$straightFlush]];
        }
    }
    
    // Four of a Kind
    $quads = array_keys(array_filter($valueCounts, fn($c) => $c === 4));
    if ($quads) {
        $kicker = max(array_diff($values, $quads));
        return ['rank' => 8, 'name' => 'Vierling', 'kickers' => [$quads[0], $kicker]];
    }
    
    // Full House
    $trips = array_keys(array_filter($valueCounts, fn($c) => $c === 3));
    $pairs = array_keys(array_filter($valueCounts, fn($c) => $c === 2));
    if ($trips && ($pairs || count($trips) > 1)) {
        $tripVal = max($trips);
        $pairVal = $pairs ? max($pairs) : min($trips);
        return ['rank' => 7, 'name' => 'Full House', 'kickers' => [$tripVal, $pairVal]];
    }
    
    // Flush
    if ($flushSuit) {
        $flushCards = array_filter($cards, fn($c) => $c['suit'] === $flushSuit);
        $flushValues = array_map(fn($c) => $valueOrder[$c['value']], $flushCards);
        rsort($flushValues);
        return ['rank' => 6, 'name' => 'Flush', 'kickers' => array_slice($flushValues, 0, 5)];
    }
    
    // Straight
    if ($straight) {
        return ['rank' => 5, 'name' => 'Straße', 'kickers' => [$straight]];
    }
    
    // Three of a Kind
    if ($trips) {
        $kickers = array_values(array_diff($values, $trips));
        return ['rank' => 4, 'name' => 'Drilling', 'kickers' => array_merge($trips, array_slice($kickers, 0, 2))];
    }
    
    // Two Pair
    if (count($pairs) >= 2) {
        rsort($pairs);
        $kicker = max(array_diff($values, $pairs));
        return ['rank' => 3, 'name' => 'Zwei Paare', 'kickers' => [$pairs[0], $pairs[1], $kicker]];
    }
    
    // One Pair
    if ($pairs) {
        $kickers = array_values(array_diff($values, $pairs));
        return ['rank' => 2, 'name' => 'Paar', 'kickers' => array_merge($pairs, array_slice($kickers, 0, 3))];
    }
    
    // High Card
    return ['rank' => 1, 'name' => 'High Card', 'kickers' => array_slice($values, 0, 5)];
}

function findStraight($values) {
    // A-2-3-4-5 (Wheel)
    if (in_array(14, $values)) {
        $values[] = 1;
    }
    sort($values);
    
    $consecutive = 1;
    $highest = $values[0];
    
    for ($i = 1; $i < count($values); $i++) {
        if ($values[$i] === $values[$i-1] + 1) {
            $consecutive++;
            $highest = $values[$i];
            if ($consecutive >= 5) {
                return $highest === 1 ? 5 : $highest; // Wheel gibt 5 zurück
            }
        } elseif ($values[$i] !== $values[$i-1]) {
            $consecutive = 1;
            $highest = $values[$i];
        }
    }
    
    return $consecutive >= 5 ? $highest : null;
}

/**
 * Spiel verlassen
 */
function leaveGame($db, $input) {
    $gameId = $input['game_id'] ?? 0;
    $playerId = $input['player_id'] ?? 0;
    
    $game = $db->querySingle("SELECT status FROM games WHERE id = $gameId", true);
    
    if ($game && $game['status'] === 'playing') {
        // Im Spiel: Fold und inaktiv setzen
        $db->exec("UPDATE players SET folded = 1, is_active = 0 WHERE id = $playerId");
        
        // Prüfen ob noch genug Spieler
        $active = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId AND is_active = 1 AND folded = 0");
        if ($active <= 1) {
            $pot = $db->querySingle("SELECT pot FROM games WHERE id = $gameId");
            $winner = $db->querySingle("SELECT id FROM players WHERE game_id = $gameId AND is_active = 1 AND folded = 0", true);
            if ($winner) {
                $db->exec("UPDATE players SET chips = chips + $pot WHERE id = {$winner['id']}");
                $winnersJson = json_encode([['id' => $winner['id'], 'amount' => $pot, 'hand' => 'Letzter Spieler']]);
                $db->exec("UPDATE games SET status = 'finished', pot = 0, winners = '$winnersJson' WHERE id = $gameId");
            }
        }
    } else {
        // Im Warten: Spieler löschen
        $db->exec("DELETE FROM players WHERE id = $playerId");
        
        $remaining = $db->querySingle("SELECT COUNT(*) FROM players WHERE game_id = $gameId");
        if ($remaining == 0) {
            $db->exec("DELETE FROM games WHERE id = $gameId");
        }
    }
    
    return ['success' => true];
}
