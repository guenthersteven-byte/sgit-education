<?php
/**
 * ============================================================================
 * sgiT Education - Poker (Texas Hold'em) v1.0
 * ============================================================================
 * 
 * Texas Hold'em für 2-8 Spieler
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
require_once 'includes/version.php';
require_once __DIR__ . '/wallet/SessionManager.php';

$userName = '';
$walletChildId = 0;
$userAvatar = '😀';

if (SessionManager::isLoggedIn()) {
    $childData = SessionManager::getChild();
    if ($childData) {
        $walletChildId = $childData['id'];
        $userName = $childData['name'];
        $userAvatar = $childData['avatar'] ?? '😀';
    }
} elseif (isset($_SESSION['wallet_child_id'])) {
    $walletChildId = $_SESSION['wallet_child_id'];
    $userName = $_SESSION['user_name'] ?? $_SESSION['child_name'] ?? '';
    $userAvatar = $_SESSION['avatar'] ?? '😀';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎰 Poker - sgiT Education</title>
    <!-- Zentrale Multiplayer CSS -->
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <script src="/assets/js/playing-cards.js"></script>
    <style>
        /* ===========================================
           Poker-Spezifische Styles
           =========================================== */
        :root {
            --card-white: #fffef5;
            --table-green: #1a6b3a;
        }
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--mp-bg-dark) 0%, var(--mp-primary) 100%);
            min-height: 100vh;
            color: var(--mp-text);
            margin: 0; padding: 0;
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 15px; }
        
        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 20px; background: var(--mp-bg-card); border-radius: 12px; margin-bottom: 20px;
        }
        .header h1 { font-size: 1.4rem; }
        .header h1 span { color: var(--mp-accent); }
        .back-link { color: var(--mp-accent); text-decoration: none; }
        
        .screen { display: none; }
        .screen.active { display: block; }
        
        .lobby-container { max-width: 500px; margin: 30px auto; text-align: center; }
        .lobby-card { background: var(--mp-bg-card); border-radius: 16px; padding: 25px; margin-bottom: 20px; }
        .lobby-card h2 { color: var(--mp-accent); margin-bottom: 15px; }
        .input-group { margin-bottom: 15px; }
        .input-group input {
            width: 100%; padding: 12px; border: 2px solid transparent; border-radius: 10px;
            background: var(--mp-bg-medium); color: var(--mp-text); font-size: 1rem;
        }
        .input-group input:focus { outline: none; border-color: var(--accent); }
        .game-code-input { font-size: 1.5rem !important; text-align: center; letter-spacing: 8px; text-transform: uppercase; }
        
        .btn {
            background: var(--accent); color: var(--primary); border: none;
            padding: 12px 24px; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn.secondary { background: var(--card-bg); color: var(--text); border: 2px solid var(--accent); }
        .btn.small { padding: 8px 16px; font-size: 0.9rem; }
        .btn.danger { background: var(--red); }
        .btn.full { width: 100%; }
        .btn.gold { background: var(--gold); color: #333; }
        
        .divider { display: flex; align-items: center; margin: 20px 0; color: var(--text-muted); }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--text-muted); opacity: 0.3; }
        .divider span { padding: 0 15px; }
        
        .game-code-display { background: var(--card-bg); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        .game-code { font-size: 2.5rem; font-weight: bold; color: var(--accent); letter-spacing: 8px; font-family: monospace; }
        
        .players-list { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin: 20px 0; }
        .player-slot { background: var(--bg); border: 2px dashed var(--text-muted); border-radius: 12px; padding: 15px 20px; text-align: center; }
        .player-slot.filled { border-style: solid; border-color: var(--accent); }
        
        /* Poker Table */
        .table-container { background: var(--card-bg); border-radius: 16px; padding: 20px; position: relative; }
        
        .poker-table {
            background: linear-gradient(135deg, #0d4f24, var(--table-green), #0d4f24);
            border: 12px solid #5d3a1a;
            border-radius: 150px;
            min-height: 400px;
            position: relative;
            box-shadow: inset 0 0 50px rgba(0,0,0,0.4), 0 8px 32px rgba(0,0,0,0.5);
        }
        
        .community-area {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .community-cards { display: flex; gap: 8px; justify-content: center; margin-bottom: 15px; }
        .pot-display { background: rgba(0,0,0,0.5); padding: 8px 20px; border-radius: 20px; }
        .pot-display .amount { color: var(--gold); font-size: 1.3rem; font-weight: bold; }
        
        /* Player Seats */
        .seat {
            position: absolute;
            width: 120px;
            text-align: center;
            transform: translate(-50%, -50%);
        }
        .seat-0 { top: 95%; left: 50%; }
        .seat-1 { top: 80%; left: 15%; }
        .seat-2 { top: 40%; left: 5%; }
        .seat-3 { top: 10%; left: 20%; }
        .seat-4 { top: 5%; left: 50%; }
        .seat-5 { top: 10%; left: 80%; }
        .seat-6 { top: 40%; left: 95%; }
        .seat-7 { top: 80%; left: 85%; }
        
        .player-seat {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 8px;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        .player-seat.active { border-color: var(--accent); box-shadow: 0 0 15px rgba(67,210,64,0.5); }
        .player-seat.folded { opacity: 0.5; }
        .player-seat.me { border-color: var(--gold); }
        .player-seat .avatar { font-size: 1.5rem; }
        .player-seat .name { font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .player-seat .chips { font-size: 0.7rem; color: var(--gold); }
        .player-seat .bet { position: absolute; font-size: 0.7rem; background: rgba(0,0,0,0.7); padding: 2px 6px; border-radius: 10px; color: var(--gold); }
        .player-seat .dealer-btn { position: absolute; top: -10px; right: -10px; background: white; color: #333; width: 24px; height: 24px; border-radius: 50%; font-size: 0.7rem; font-weight: bold; display: flex; align-items: center; justify-content: center; }
        
        .hole-cards { display: flex; gap: 4px; justify-content: center; margin-top: 5px; }
        
        /* Cards - mit Animationen aus multiplayer-theme.css */
        .card {
            width: 45px; height: 65px;
            background: var(--card-white);
            border-radius: 5px; border: 2px solid #ccc;
            display: flex; flex-direction: column;
            justify-content: space-between; padding: 3px;
            font-size: 0.6rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: var(--mp-transition);
        }
        .card:hover { transform: translateY(-5px); }
        .card.deal { animation: mp-cardDeal 0.3s ease-out forwards; }
        .card.large { width: 55px; height: 80px; font-size: 0.7rem; }
        .card.red { color: #c0392b; }
        .card.black { color: #2c3e50; }
        .card .corner { line-height: 1; }
        .card .corner-bottom { align-self: flex-end; transform: rotate(180deg); }
        .card .center { font-size: 1.2rem; text-align: center; flex: 1; display: flex; align-items: center; justify-content: center; }
        .card.large .center { font-size: 1.6rem; }
        .card-back {
            width: 45px; height: 65px;
            background: linear-gradient(135deg, #1a5f2a, #0d3015);
            border-radius: 5px; border: 2px solid #2a7f3a;
            transition: var(--mp-transition);
        }
        .card-back:hover { transform: scale(1.05); }
        .card-back.large { width: 55px; height: 80px; }
        
        /* Actions */
        .actions-panel {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            align-items: center;
        }
        .raise-input {
            width: 100px;
            padding: 8px;
            border-radius: 8px;
            border: 2px solid var(--accent);
            background: var(--bg);
            color: var(--text);
            text-align: center;
        }
        
        /* Info Panel */
        .info-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .info-card { background: var(--card-bg); border-radius: 12px; padding: 12px; }
        .info-card h3 { color: var(--accent); margin-bottom: 8px; font-size: 0.9rem; }
        
        .my-cards { display: flex; gap: 10px; justify-content: center; align-items: center; }
        .my-cards .card { width: 70px; height: 100px; font-size: 0.9rem; }
        .my-cards .card .center { font-size: 2rem; }
        
        .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 12px 20px; border-radius: 12px; font-weight: 600; z-index: 1000; }
        .toast.success { background: var(--accent); color: var(--primary); }
        .toast.error { background: var(--mp-error); color: white; }
        .toast.info { background: var(--mp-bg-card); border: 2px solid var(--mp-accent); }
        
        /* Mobile Optimierung */
        @media (max-width: 600px) {
            .poker-table {
                min-height: 250px;
                padding: 15px;
            }
            .card { width: 35px; height: 50px; font-size: 0.5rem; }
            .card.large { width: 45px; height: 65px; font-size: 0.6rem; }
            .card .center { font-size: 1rem; }
            .card.large .center { font-size: 1.3rem; }
            .player-seat { padding: 5px; min-width: 70px; }
            .player-seat .name { font-size: 0.7rem; }
            .actions-panel { padding: 10px; gap: 8px; }
            .btn { padding: 10px 16px; font-size: 0.9rem; }
        }
        
        @media (max-width: 400px) {
            .card { width: 30px; height: 45px; }
            .card.large { width: 38px; height: 55px; }
            .player-seat { min-width: 60px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="multiplayer.php" class="back-link">← Multiplayer</a>
                <h1>🎰 <span>Poker</span></h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>
        
        <!-- LOBBY -->
        <div id="lobbyScreen" class="screen active">
            <div class="lobby-container">
                <div style="font-size: 4rem; margin-bottom: 10px;">🎰</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Texas Hold'em</h1>
                <p style="color: var(--text-muted); margin-bottom: 25px;">Poker für 2-8 Spieler</p>
                
                <div class="lobby-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2>👤 Dein Name</h2>
                    <div class="input-group">
                        <input type="text" id="playerNameInput" placeholder="Name..." maxlength="20">
                    </div>
                    <button class="btn full" onclick="setPlayerName()">Weiter →</button>
                </div>
                
                <div class="lobby-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🎮 Neues Spiel</h2>
                    <div class="input-group">
                        <label style="color: var(--text-muted); font-size: 0.9rem;">Buy-In (Chips)</label>
                        <input type="number" id="buyInInput" value="1000" min="100" step="100">
                    </div>
                    <button class="btn full" onclick="createGame()">👥 Gegen Mitspieler</button>
                    <button class="btn secondary full" style="margin-top: 10px;" onclick="location.href='poker_vs_computer.php'">🤖 Gegen Computer</button>
                </div>
                
                <div class="divider"><span>oder</span></div>
                
                <div class="lobby-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🔗 Beitreten</h2>
                    <div class="input-group">
                        <input type="text" id="gameCodeInput" class="game-code-input" placeholder="CODE" maxlength="6">
                    </div>
                    <button class="btn secondary full" onclick="joinGame()">Beitreten →</button>
                </div>
            </div>
        </div>
        
        <!-- WAITING -->
        <div id="waitingScreen" class="screen">
            <div class="lobby-container">
                <div class="game-code-display">
                    <p style="color: var(--text-muted);">Spiel-Code</p>
                    <div class="game-code" id="displayCode">------</div>
                </div>
                <div class="lobby-card">
                    <h2>👥 Spieler (2-8)</h2>
                    <div class="players-list" id="playersWaiting"></div>
                </div>
                <div id="hostControls" style="display: none;">
                    <button class="btn full" onclick="startGame()" id="startBtn" disabled>▶️ Spiel starten</button>
                </div>
                <p id="waitingMsg" style="color: var(--text-muted); display: none;">⏳ Warte auf Host...</p>
                <button class="btn secondary full" style="margin-top: 15px;" onclick="leaveGame()">🚪 Verlassen</button>
            </div>
        </div>
        
        <!-- GAME -->
        <div id="gameScreen" class="screen">
            <div class="table-container">
                <div class="poker-table" id="pokerTable">
                    <!-- Community Cards & Pot -->
                    <div class="community-area">
                        <div class="community-cards" id="communityCards"></div>
                        <div class="pot-display">
                            <span>Pot:</span> <span class="amount" id="potAmount">0</span>
                        </div>
                    </div>
                    <!-- Player Seats (dynamisch) -->
                </div>
            </div>
            
            <!-- My Cards -->
            <div class="info-panel">
                <div class="info-card">
                    <h3>🃏 Deine Karten</h3>
                    <div class="my-cards" id="myCards"></div>
                </div>
                <div class="info-card">
                    <h3>📊 Spielinfo</h3>
                    <p style="font-size: 0.85rem;">
                        Runde: <span id="roundInfo">-</span><br>
                        Blinds: <span id="blindsInfo">-</span><br>
                        Deine Chips: <span id="myChips" style="color: var(--gold);">-</span>
                    </p>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="actions-panel" id="actionsPanel">
                <button class="btn danger small" id="foldBtn" onclick="placeBet('fold')">🚫 Fold</button>
                <button class="btn secondary small" id="checkBtn" onclick="placeBet('check')">✓ Check</button>
                <button class="btn small" id="callBtn" onclick="placeBet('call')">📞 Call <span id="callAmount"></span></button>
                <input type="number" class="raise-input" id="raiseInput" min="0" step="10">
                <button class="btn gold small" id="raiseBtn" onclick="placeBet('raise')">⬆️ Raise</button>
                <button class="btn danger small" id="allinBtn" onclick="placeBet('allin')">💰 All-In</button>
            </div>
        </div>
        
        <!-- RESULT -->
        <div id="resultScreen" class="screen">
            <div class="lobby-container">
                <div class="lobby-card" style="border: 3px solid var(--gold);">
                    <div style="font-size: 5rem;">🏆</div>
                    <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                    <div id="winnersDisplay" style="margin: 20px 0;"></div>
                    <button class="btn full" onclick="newHand()" id="newHandBtn">🔄 Nächste Hand</button>
                    <button class="btn secondary full" style="margin-top: 10px;" onclick="location.href='multiplayer.php'">← Zurück</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const API_URL = '/api/poker.php';
        const POLL_INTERVAL = 800;
        
        const SUITS = { hearts: '♥', diamonds: '♦', clubs: '♣', spades: '♠' };
        const SUIT_COLORS = { hearts: 'red', diamonds: 'red', clubs: 'black', spades: 'black' };
        
        let gameState = {
            gameId: null, playerId: null, gameCode: null, isHost: false,
            mySeat: -1, myTurn: false, status: 'lobby'
        };
        
        let playerName = '<?php echo addslashes($userName); ?>';
        let playerAvatar = '<?php echo addslashes($userAvatar); ?>';
        let walletChildId = <?php echo $walletChildId ?: 'null'; ?>;
        let pollInterval = null;
        
        function showScreen(name) {
            document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
            document.getElementById(name + 'Screen').classList.add('active');
        }
        
        function setPlayerName() {
            const name = document.getElementById('playerNameInput').value.trim();
            if (!name) { showToast('Name eingeben', 'error'); return; }
            playerName = name;
            document.getElementById('nameCard').style.display = 'none';
            document.getElementById('createCard').style.display = 'block';
            document.getElementById('joinCard').style.display = 'block';
        }
        
        async function createGame() {
            const buyIn = parseInt(document.getElementById('buyInInput').value) || 1000;
            const res = await fetch(`${API_URL}?action=create`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ player_name: playerName, avatar: playerAvatar, wallet_child_id: walletChildId, buy_in: buyIn })
            });
            const data = await res.json();
            if (data.success) {
                gameState = { ...gameState, gameId: data.game_id, playerId: data.player_id, gameCode: data.game_code, isHost: true };
                document.getElementById('displayCode').textContent = data.game_code;
                document.getElementById('hostControls').style.display = 'block';
                showScreen('waiting');
                startPolling();
            } else showToast(data.error, 'error');
        }
        
        async function joinGame() {
            const code = document.getElementById('gameCodeInput').value.trim().toUpperCase();
            if (code.length !== 6) { showToast('6-stelligen Code eingeben', 'error'); return; }
            const res = await fetch(`${API_URL}?action=join`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_code: code, player_name: playerName, avatar: playerAvatar, wallet_child_id: walletChildId })
            });
            const data = await res.json();
            if (data.success) {
                gameState = { ...gameState, gameId: data.game_id, playerId: data.player_id, gameCode: code, isHost: false };
                document.getElementById('displayCode').textContent = code;
                document.getElementById('waitingMsg').style.display = 'block';
                showScreen('waiting');
                startPolling();
            } else showToast(data.error, 'error');
        }
        
        async function startGame() {
            const res = await fetch(`${API_URL}?action=start`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId })
            });
            const data = await res.json();
            if (!data.success) showToast(data.error, 'error');
        }
        
        async function newHand() {
            await startGame();
        }
        
        async function leaveGame() {
            if (gameState.gameId) {
                await fetch(`${API_URL}?action=leave`, {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId })
                });
            }
            stopPolling();
            location.reload();
        }
        
        function startPolling() { if (pollInterval) clearInterval(pollInterval); pollInterval = setInterval(pollStatus, POLL_INTERVAL); pollStatus(); }
        function stopPolling() { if (pollInterval) clearInterval(pollInterval); }
        
        async function pollStatus() {
            if (!gameState.gameId) return;
            const res = await fetch(`${API_URL}?action=status&game_id=${gameState.gameId}&player_id=${gameState.playerId}`);
            const data = await res.json();
            if (!data.success) return;
            
            const game = data.game;
            const prevStatus = gameState.status;
            gameState.status = game.status;
            gameState.mySeat = data.my_seat;
            gameState.myTurn = data.my_turn;
            gameState.isHost = data.is_host;
            
            if (game.status === 'waiting') {
                document.getElementById('playersWaiting').innerHTML = data.players.map(p => 
                    `<div class="player-slot filled">${p.avatar} ${escapeHtml(p.name)} (${p.chips}💰)</div>`
                ).join('');
                document.getElementById('startBtn').disabled = data.players.length < 2;
            }
            else if (game.status === 'playing') {
                if (prevStatus !== 'playing') showScreen('game');
                renderTable(data);
                renderActions(data);
            }
            else if (game.status === 'finished') {
                showResult(game, data.players);
            }
        }
        
        function renderTable(data) {
            const game = data.game;
            const players = data.players;
            
            // Community Cards
            const ccEl = document.getElementById('communityCards');
            ccEl.innerHTML = data.community_cards.map(c => renderCard(c, true)).join('');
            for (let i = data.community_cards.length; i < 5; i++) {
                ccEl.innerHTML += '<div class="card-back large"></div>';
            }
            
            // Pot
            document.getElementById('potAmount').textContent = game.pot + ' 💰';
            
            // Round & Blinds Info
            const rounds = { preflop: 'Pre-Flop', flop: 'Flop', turn: 'Turn', river: 'River' };
            document.getElementById('roundInfo').textContent = rounds[game.round] || game.round;
            document.getElementById('blindsInfo').textContent = `${game.small_blind}/${game.big_blind}`;
            
            // Players
            const table = document.getElementById('pokerTable');
            // Alte Seats entfernen
            table.querySelectorAll('.seat').forEach(s => s.remove());
            
            players.forEach((p, i) => {
                const isMe = p.seat === gameState.mySeat;
                const isActive = p.seat === game.current_player && game.status === 'playing';
                const isDealer = p.seat === game.dealer_pos;
                
                const seat = document.createElement('div');
                seat.className = `seat seat-${p.seat}`;
                seat.innerHTML = `
                    <div class="player-seat ${isActive ? 'active' : ''} ${p.folded ? 'folded' : ''} ${isMe ? 'me' : ''}">
                        ${isDealer ? '<div class="dealer-btn">D</div>' : ''}
                        <div class="avatar">${p.avatar}</div>
                        <div class="name">${escapeHtml(p.name)}</div>
                        <div class="chips">${p.chips} 💰</div>
                        ${p.current_bet > 0 ? `<div class="bet">${p.current_bet}</div>` : ''}
                        <div class="hole-cards">
                            ${p.hole_cards ? p.hole_cards.map(c => renderCard(c, false)).join('') : 
                              (p.card_count > 0 && !p.folded ? '<div class="card-back"></div><div class="card-back"></div>' : '')}
                        </div>
                    </div>
                `;
                table.appendChild(seat);
                
                if (isMe) {
                    document.getElementById('myChips').textContent = p.chips + ' 💰';
                    if (p.hole_cards) {
                        document.getElementById('myCards').innerHTML = p.hole_cards.map(c => 
                            `<div class="card ${SUIT_COLORS[c.suit]}" style="width:70px;height:100px;font-size:0.9rem;">
                                <div class="corner">${c.value}<br>${SUITS[c.suit]}</div>
                                <div class="center" style="font-size:2rem;">${SUITS[c.suit]}</div>
                                <div class="corner corner-bottom">${c.value}<br>${SUITS[c.suit]}</div>
                            </div>`
                        ).join('');
                    }
                }
            });
        }
        
        // Mapping for SVG card keys
        const POKER_SUIT_TO_DE = { hearts: 'herz', diamonds: 'karo', clubs: 'kreuz', spades: 'pik' };
        const POKER_VALUE_TO_DE = { 'A': 'ass', 'J': 'bube', 'Q': 'dame', 'K': 'koenig' };

        function getPokerCardKey(card) {
            const suit = POKER_SUIT_TO_DE[card.suit] || card.suit;
            const val = POKER_VALUE_TO_DE[card.value] || card.value;
            return `${suit}_${val}`;
        }

        function renderCard(card, large = false) {
            const w = large ? 70 : 45;
            const h = large ? 100 : 65;
            if (typeof PLAYING_CARD_SVGS !== 'undefined') {
                const key = getPokerCardKey(card);
                const src = PLAYING_CARD_SVGS[key] || PLAYING_CARD_SVGS.back;
                return `<div class="card ${large ? 'large' : ''}" style="padding:0;border:none;background:none;box-shadow:none;width:${w}px;height:${h}px;">
                    <img src="${src}" style="width:100%;height:100%;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.3);" draggable="false">
                </div>`;
            }
            return `<div class="card ${SUIT_COLORS[card.suit]} ${large ? 'large' : ''}">
                <div class="corner">${card.value}<br>${SUITS[card.suit]}</div>
                <div class="center">${SUITS[card.suit]}</div>
                <div class="corner corner-bottom">${card.value}<br>${SUITS[card.suit]}</div>
            </div>`;
        }
        
        function renderActions(data) {
            const game = data.game;
            const actions = data.actions;
            const panel = document.getElementById('actionsPanel');
            
            const me = data.players.find(p => p.seat === gameState.mySeat);
            const toCall = game.current_bet - (me?.current_bet || 0);
            
            document.getElementById('foldBtn').disabled = !actions.includes('fold');
            document.getElementById('checkBtn').disabled = !actions.includes('check');
            document.getElementById('callBtn').disabled = !actions.includes('call');
            document.getElementById('raiseBtn').disabled = !actions.includes('raise');
            document.getElementById('allinBtn').disabled = !actions.includes('allin');
            
            document.getElementById('callAmount').textContent = toCall > 0 ? toCall : '';
            document.getElementById('raiseInput').min = game.min_raise;
            document.getElementById('raiseInput').value = game.min_raise;
            
            panel.style.opacity = gameState.myTurn ? '1' : '0.5';
        }
        
        async function placeBet(action) {
            if (!gameState.myTurn) return;
            
            const amount = parseInt(document.getElementById('raiseInput').value) || 0;
            
            const res = await fetch(`${API_URL}?action=bet`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId, action, amount })
            });
            const data = await res.json();
            if (!data.success) showToast(data.error, 'error');
        }
        
        function showResult(game, players) {
            showScreen('result');
            
            if (game.winners && game.winners.length > 0) {
                const winnersHtml = game.winners.map(w => {
                    const p = players.find(pl => pl.id === w.id);
                    return `<div style="padding:10px;background:var(--bg);border-radius:8px;margin:5px 0;">
                        <span style="font-size:1.5rem;">${p?.avatar || '😀'}</span>
                        <span style="font-weight:bold;">${escapeHtml(p?.name || 'Spieler')}</span><br>
                        <span style="color:var(--gold);">${w.amount} 💰</span>
                        <span style="color:var(--text-muted);font-size:0.85rem;">${w.hand}</span>
                    </div>`;
                }).join('');
                
                document.getElementById('winnerText').textContent = game.winners.length > 1 ? 'Split Pot!' : 'Gewinner!';
                document.getElementById('winnersDisplay').innerHTML = winnersHtml;
            }
            
            // Zeige nächste Hand nur wenn noch genug Spieler
            const activePlayers = players.filter(p => p.chips > 0);
            document.getElementById('newHandBtn').style.display = (gameState.isHost && activePlayers.length >= 2) ? 'block' : 'none';
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        
        function showToast(msg, type = 'info') {
            const toast = document.createElement('div');
            toast.className = 'toast ' + type;
            toast.textContent = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        document.getElementById('playerNameInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') setPlayerName(); });
        document.getElementById('gameCodeInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') joinGame(); });
    </script>
</body>
</html>
