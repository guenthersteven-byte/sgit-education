<?php
/**
 * ============================================================================
 * sgiT Education - Mau Mau v1.0
 * ============================================================================
 * 
 * Klassisches Kartenspiel für 2-4 Spieler
 * Regeln: 7=+2, 8=Aussetzen, Bube=Wunschfarbe, Ass=Richtungswechsel
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
require_once 'includes/version.php';
require_once __DIR__ . '/wallet/SessionManager.php';

// User-Daten aus SessionManager (MUSS für Multiplayer!)
$userName = '';
$userAge = 10;
$walletChildId = 0;
$userAvatar = '😀';

if (SessionManager::isLoggedIn()) {
    $childData = SessionManager::getChild();
    if ($childData) {
        $walletChildId = $childData['id'];
        $userName = $childData['name'];
        $userAvatar = $childData['avatar'] ?? '😀';
        $userAge = $childData['age'] ?? 10;
    }
} elseif (isset($_SESSION['wallet_child_id'])) {
    $walletChildId = $_SESSION['wallet_child_id'];
    $userName = $_SESSION['user_name'] ?? $_SESSION['child_name'] ?? '';
    $userAvatar = $_SESSION['avatar'] ?? '😀';
}

// Karten-Symbole
$colorSymbols = ['herz' => '♥', 'karo' => '♦', 'pik' => '♠', 'kreuz' => '♣'];
$colorClasses = ['herz' => 'red', 'karo' => 'red', 'pik' => 'black', 'kreuz' => 'black'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🃏 Mau Mau - sgiT Education</title>
    <!-- Zentrale Multiplayer CSS -->
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <style>
        /* ===========================================
           Mau Mau-Spezifische Styles
           =========================================== */
        :root {
            --card-white: #fffef5;
        }
        body { 
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--mp-bg-dark) 0%, var(--mp-primary) 100%);
            min-height: 100vh;
            color: var(--mp-text);
            margin: 0; padding: 0;
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 15px; }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: var(--mp-bg-card);
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .header h1 { font-size: 1.4rem; }
        .header h1 span { color: var(--accent); }
        .back-link { color: var(--accent); text-decoration: none; }
        
        .screen { display: none; }
        .screen.active { display: block; }
        
        /* Lobby */
        .lobby-container { max-width: 500px; margin: 30px auto; text-align: center; }
        .lobby-card { background: var(--card-bg); border-radius: 16px; padding: 25px; margin-bottom: 20px; }
        .lobby-card h2 { color: var(--accent); margin-bottom: 15px; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; color: var(--text-muted); font-size: 0.9rem; text-align: left; }
        .input-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid transparent;
            border-radius: 10px;
            background: var(--bg);
            color: var(--text);
            font-size: 1rem;
        }
        .input-group input:focus { outline: none; border-color: var(--accent); }
        .game-code-input { font-size: 1.5rem !important; text-align: center; letter-spacing: 8px; text-transform: uppercase; }
        
        .btn {
            background: var(--accent);
            color: var(--primary);
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn.secondary { background: var(--card-bg); color: var(--text); border: 2px solid var(--accent); }
        .btn.small { padding: 8px 16px; width: auto; font-size: 0.9rem; }
        .btn.mau { background: #f39c12; animation: pulse 0.5s infinite; }
        @keyframes pulse { 50% { transform: scale(1.05); } }
        
        .divider { display: flex; align-items: center; margin: 20px 0; color: var(--text-muted); }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--text-muted); opacity: 0.3; }
        .divider span { padding: 0 15px; }
        
        /* Waiting Room */
        .game-code-display { background: var(--card-bg); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        .game-code { font-size: 2.5rem; font-weight: bold; color: var(--accent); letter-spacing: 8px; font-family: monospace; }
        .players-list { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin: 20px 0; }
        .player-slot { background: var(--bg); border: 2px dashed var(--text-muted); border-radius: 12px; padding: 15px 20px; text-align: center; }
        .player-slot.filled { border-style: solid; border-color: var(--accent); }
        
        /* Game Area */
        .game-container { display: grid; grid-template-columns: 1fr 250px; gap: 20px; }
        @media (max-width: 800px) { .game-container { grid-template-columns: 1fr; } }
        
        .play-area { background: var(--card-bg); border-radius: 16px; padding: 20px; min-height: 500px; }
        
        /* Spieltisch */
        .table-area { display: flex; flex-direction: column; align-items: center; gap: 20px; }
        
        .opponents { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; margin-bottom: 20px; }
        .opponent { background: var(--bg); border-radius: 12px; padding: 10px 15px; text-align: center; }
        .opponent.active { border: 2px solid var(--accent); }
        .opponent .cards { display: flex; gap: -10px; justify-content: center; margin-top: 5px; }
        .card-back { width: 30px; height: 45px; background: linear-gradient(135deg, #2c3e50, #34495e); border-radius: 4px; border: 1px solid #555; margin-left: -15px; }
        .card-back:first-child { margin-left: 0; }
        
        .center-pile {
            display: flex;
            gap: 20px;
            align-items: center;
            justify-content: center;
            padding: 30px;
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
        }
        .deck-pile, .discard-pile { position: relative; width: 80px; height: 120px; }
        .deck-pile .card-back { width: 80px; height: 120px; cursor: pointer; position: absolute; }
        .deck-pile .card-back:nth-child(2) { top: 2px; left: 2px; }
        .deck-pile .card-back:nth-child(3) { top: 4px; left: 4px; }
        .deck-count { position: absolute; bottom: -25px; left: 50%; transform: translateX(-50%); font-size: 0.8rem; color: var(--text-muted); }
        
        /* Spielkarten - mit Animationen aus multiplayer-theme.css */
        .card {
            width: 80px;
            height: 120px;
            background: var(--card-white);
            border-radius: 8px;
            border: 2px solid #ccc;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 8px;
            cursor: pointer;
            transition: var(--mp-transition);
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .card:hover { transform: translateY(-10px); }
        .card.deal { animation: mp-cardDeal 0.3s ease-out forwards; }
        .card.red { color: #e74c3c; }
        .card.black { color: #2c3e50; }
        .card .corner { font-size: 0.9rem; line-height: 1; }
        .card .corner-bottom { align-self: flex-end; transform: rotate(180deg); }
        .card .center-symbol { font-size: 2.5rem; text-align: center; flex: 1; display: flex; align-items: center; justify-content: center; }
        .card.playable { border-color: var(--mp-accent); box-shadow: 0 0 15px var(--mp-accent-glow); animation: mp-fieldPulse 1.5s ease infinite; }
        .card.selected { transform: translateY(-20px); border-color: #f39c12; }
        
        .my-hand {
            display: flex;
            gap: -20px;
            justify-content: center;
            flex-wrap: wrap;
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-radius: 15px;
            margin-top: 20px;
        }
        .my-hand .card { margin-left: -20px; }
        .my-hand .card:first-child { margin-left: 0; }
        
        /* Sidebar */
        .sidebar { display: flex; flex-direction: column; gap: 15px; }
        .info-card { background: var(--card-bg); border-radius: 12px; padding: 15px; }
        .info-card h3 { color: var(--accent); margin-bottom: 10px; font-size: 1rem; }
        
        .turn-info { text-align: center; padding: 15px; background: var(--bg); border-radius: 10px; }
        .turn-info.my-turn { border: 2px solid var(--accent); }
        .turn-info .label { font-size: 0.85rem; color: var(--text-muted); }
        .turn-info .name { font-size: 1.2rem; font-weight: bold; margin-top: 5px; }
        
        .special-info { background: var(--bg); border-radius: 10px; padding: 10px; margin-top: 10px; text-align: center; }
        .special-info.warning { border: 2px solid #f39c12; }
        .special-info.danger { border: 2px solid var(--red); }
        
        .rules-hint { font-size: 0.85rem; color: var(--text-muted); }
        .rules-hint ul { list-style: none; margin-top: 10px; }
        .rules-hint li { margin-bottom: 5px; }
        
        /* Wish Color Modal */
        .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 100; }
        .modal.active { display: flex; }
        .modal-content { background: var(--card-bg); border-radius: 16px; padding: 25px; text-align: center; max-width: 350px; }
        .modal h2 { margin-bottom: 20px; color: var(--accent); }
        .color-choice { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .color-btn { width: 60px; height: 60px; border-radius: 50%; font-size: 2rem; border: 3px solid transparent; cursor: pointer; }
        .color-btn:hover { transform: scale(1.1); }
        .color-btn.herz { background: #fee; color: #e74c3c; }
        .color-btn.karo { background: #fee; color: #e74c3c; }
        .color-btn.pik { background: #eee; color: #2c3e50; }
        .color-btn.kreuz { background: #eee; color: #2c3e50; }
        
        /* Toast */
        .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 15px 25px; border-radius: 12px; font-weight: 600; z-index: 1000; }
        .toast.success { background: var(--accent); color: var(--primary); }
        .toast.error { background: var(--red); color: white; }
        .toast.info { background: var(--card-bg); border: 2px solid var(--accent); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="multiplayer.php" class="back-link">← Multiplayer</a>
                <h1>🃏 <span>Mau Mau</span></h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>
        
        <!-- LOBBY -->
        <div id="lobbyScreen" class="screen active">
            <div class="lobby-container">
                <div style="font-size: 4rem; margin-bottom: 10px;">🃏</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Mau Mau</h1>
                <p style="color: var(--text-muted); margin-bottom: 25px;">Das Kartenspiel-Klassiker für 2-4 Spieler</p>
                
                <div class="lobby-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2>👤 Dein Name</h2>
                    <div class="input-group">
                        <input type="text" id="playerNameInput" placeholder="Name..." maxlength="20">
                    </div>
                    <button class="btn" onclick="setPlayerName()">Weiter →</button>
                </div>
                
                <div class="lobby-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🎮 Neues Spiel</h2>
                    <button class="btn" onclick="createGame()">Spiel erstellen</button>
                </div>
                
                <div class="divider"><span>oder</span></div>
                
                <div class="lobby-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🔗 Beitreten</h2>
                    <div class="input-group">
                        <input type="text" id="gameCodeInput" class="game-code-input" placeholder="CODE" maxlength="6">
                    </div>
                    <button class="btn secondary" onclick="joinGame()">Beitreten →</button>
                </div>
                
                <div class="lobby-card rules-hint" style="margin-top: 20px;">
                    <h3 style="color: var(--accent);">📜 Kurzregeln</h3>
                    <ul>
                        <li>🎴 Gleiche Farbe oder Zahl legen</li>
                        <li>7️⃣ = Nächster zieht 2</li>
                        <li>8️⃣ = Nächster aussetzt</li>
                        <li>🃏 Bube = Wunschfarbe</li>
                        <li>🅰️ Ass = Richtungswechsel</li>
                        <li>📢 Bei 2 Karten: "Mau!" rufen</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- WAITING -->
        <div id="waitingScreen" class="screen">
            <div class="lobby-container">
                <div class="game-code-display">
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Spiel-Code</p>
                    <div class="game-code" id="displayCode">------</div>
                </div>
                
                <div class="lobby-card">
                    <h2>👥 Spieler</h2>
                    <div class="players-list" id="playersWaiting"></div>
                </div>
                
                <div id="hostControls" style="display: none;">
                    <button class="btn" onclick="startGame()" id="startBtn" disabled>▶️ Spiel starten (min. 2)</button>
                </div>
                <p id="waitingMsg" style="color: var(--text-muted); display: none;">⏳ Warte auf Host...</p>
                <button class="btn secondary" style="margin-top: 15px;" onclick="leaveGame()">🚪 Verlassen</button>
            </div>
        </div>
        
        <!-- GAME -->
        <div id="gameScreen" class="screen">
            <div class="game-container">
                <div class="play-area">
                    <div class="table-area">
                        <!-- Gegner -->
                        <div class="opponents" id="opponents"></div>
                        
                        <!-- Mitte: Deck + Ablagestapel -->
                        <div class="center-pile">
                            <div class="deck-pile" onclick="drawCards()">
                                <div class="card-back"></div>
                                <div class="card-back"></div>
                                <div class="card-back"></div>
                                <span class="deck-count" id="deckCount">32</span>
                            </div>
                            <div class="discard-pile" id="discardPile"></div>
                        </div>
                        
                        <!-- Meine Hand -->
                        <div class="my-hand" id="myHand"></div>
                    </div>
                </div>
                
                <div class="sidebar">
                    <div class="info-card">
                        <div class="turn-info" id="turnInfo">
                            <div class="label">Am Zug:</div>
                            <div class="name" id="currentPlayerName">---</div>
                        </div>
                        <div class="special-info" id="specialInfo" style="display: none;"></div>
                    </div>
                    
                    <div class="info-card">
                        <h3>📊 Karten</h3>
                        <div id="cardCounts"></div>
                    </div>
                    
                    <button class="btn mau" id="mauBtn" onclick="sayMau()" style="display: none;">📢 MAU!</button>
                </div>
            </div>
        </div>
        
        <!-- RESULT -->
        <div id="resultScreen" class="screen">
            <div class="lobby-container">
                <div class="lobby-card" style="border: 3px solid var(--accent);">
                    <div style="font-size: 5rem;">🏆</div>
                    <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                    <button class="btn" onclick="location.reload()">🔄 Neues Spiel</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='multiplayer.php'">← Zurück</button>
                </div>
            </div>
        </div>
        
        <!-- Wunschfarbe Modal -->
        <div class="modal" id="wishModal">
            <div class="modal-content">
                <h2>🎨 Wunschfarbe wählen</h2>
                <div class="color-choice">
                    <button class="color-btn herz" onclick="selectWishColor('herz')">♥</button>
                    <button class="color-btn karo" onclick="selectWishColor('karo')">♦</button>
                    <button class="color-btn pik" onclick="selectWishColor('pik')">♠</button>
                    <button class="color-btn kreuz" onclick="selectWishColor('kreuz')">♣</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const API_URL = '/api/maumau.php';
        const POLL_INTERVAL = 800;
        const SYMBOLS = { herz: '♥', karo: '♦', pik: '♠', kreuz: '♣' };
        const COLOR_CLASS = { herz: 'red', karo: 'red', pik: 'black', kreuz: 'black' };
        
        let gameState = {
            gameId: null,
            playerId: null,
            gameCode: null,
            isHost: false,
            myTurn: false,
            status: 'lobby',
            selectedCard: null,
            pendingWish: null
        };
        
        let playerName = '<?php echo addslashes($userName); ?>';
        let playerAvatar = '<?php echo addslashes($userAvatar); ?>';
        let walletChildId = <?php echo $walletChildId ?: 'null'; ?>;
        let pollInterval = null;
        
        // UI Funktionen
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
        
        // API Funktionen
        async function createGame() {
            const res = await fetch(`${API_URL}?action=create`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ player_name: playerName, avatar: playerAvatar, wallet_child_id: walletChildId })
            });
            const data = await res.json();
            
            if (data.success) {
                gameState = { ...gameState, gameId: data.game_id, playerId: data.player_id, gameCode: data.game_code, isHost: true };
                document.getElementById('displayCode').textContent = data.game_code;
                document.getElementById('hostControls').style.display = 'block';
                showScreen('waiting');
                startPolling();
            } else {
                showToast(data.error, 'error');
            }
        }
        
        async function joinGame() {
            const code = document.getElementById('gameCodeInput').value.trim().toUpperCase();
            if (code.length !== 6) { showToast('6-stelligen Code eingeben', 'error'); return; }
            
            const res = await fetch(`${API_URL}?action=join`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_code: code, player_name: playerName, avatar: playerAvatar, wallet_child_id: walletChildId })
            });
            const data = await res.json();
            
            if (data.success) {
                gameState = { ...gameState, gameId: data.game_id, playerId: data.player_id, gameCode: code, isHost: false };
                document.getElementById('displayCode').textContent = code;
                document.getElementById('waitingMsg').style.display = 'block';
                showScreen('waiting');
                startPolling();
            } else {
                showToast(data.error, 'error');
            }
        }
        
        async function startGame() {
            const res = await fetch(`${API_URL}?action=start`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId })
            });
            const data = await res.json();
            if (!data.success) showToast(data.error, 'error');
        }
        
        async function leaveGame() {
            if (gameState.gameId) {
                await fetch(`${API_URL}?action=leave`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId })
                });
            }
            stopPolling();
            location.reload();
        }
        
        // Polling
        function startPolling() {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(pollStatus, POLL_INTERVAL);
            pollStatus();
        }
        
        function stopPolling() {
            if (pollInterval) clearInterval(pollInterval);
        }
        
        async function pollStatus() {
            if (!gameState.gameId) return;
            
            const res = await fetch(`${API_URL}?action=status&game_id=${gameState.gameId}&player_id=${gameState.playerId}`);
            const data = await res.json();
            if (!data.success) return;
            
            const game = data.game;
            const prevStatus = gameState.status;
            gameState.status = game.status;
            gameState.myTurn = data.my_turn;
            
            if (game.status === 'waiting') {
                updateWaitingRoom(data.players);
                const canStart = data.players.length >= 2;
                document.getElementById('startBtn').disabled = !canStart;
            }
            else if (game.status === 'playing') {
                if (prevStatus !== 'playing') showScreen('game');
                renderGame(data);
            }
            else if (game.status === 'finished') {
                showScreen('result');
                const winner = data.players.find(p => p.card_count === 0);
                document.getElementById('winnerText').textContent = winner ? `${winner.avatar} ${winner.name} gewinnt!` : 'Spiel beendet!';
                stopPolling();
            }
        }
        
        function updateWaitingRoom(players) {
            document.getElementById('playersWaiting').innerHTML = players.map(p => 
                `<div class="player-slot filled">${p.avatar} ${escapeHtml(p.name)}</div>`
            ).join('');
        }
        
        function renderGame(data) {
            const game = data.game;
            const myHand = data.my_hand || [];
            
            // Turn Info
            const currentPlayer = data.players.find(p => p.order === game.current_player);
            const turnInfo = document.getElementById('turnInfo');
            turnInfo.className = 'turn-info' + (gameState.myTurn ? ' my-turn' : '');
            document.getElementById('currentPlayerName').textContent = currentPlayer ? `${currentPlayer.avatar} ${currentPlayer.name}` : '---';
            
            // Special Info
            const specialInfo = document.getElementById('specialInfo');
            if (game.draw_stack > 0) {
                specialInfo.style.display = 'block';
                specialInfo.className = 'special-info danger';
                specialInfo.innerHTML = `⚠️ ${game.draw_stack} Karten ziehen oder 7 legen!`;
            } else if (game.wish_color) {
                specialInfo.style.display = 'block';
                specialInfo.className = 'special-info warning';
                specialInfo.innerHTML = `🎨 Wunsch: ${SYMBOLS[game.wish_color]} ${game.wish_color.charAt(0).toUpperCase() + game.wish_color.slice(1)}`;
            } else {
                specialInfo.style.display = 'none';
            }
            
            // Deck count
            document.getElementById('deckCount').textContent = game.deck_count;
            
            // Top card
            const discardPile = document.getElementById('discardPile');
            if (game.top_card) {
                discardPile.innerHTML = renderCard(game.top_card, false, false);
            }
            
            // Opponents
            const opponents = data.players.filter(p => p.id !== gameState.playerId);
            document.getElementById('opponents').innerHTML = opponents.map(p => `
                <div class="opponent ${p.order === game.current_player ? 'active' : ''}">
                    <div>${p.avatar} ${escapeHtml(p.name)}</div>
                    <div class="cards">${'<div class="card-back"></div>'.repeat(Math.min(p.card_count, 10))}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">${p.card_count} Karten</div>
                </div>
            `).join('');
            
            // My Hand
            const handEl = document.getElementById('myHand');
            handEl.innerHTML = myHand.map((card, i) => {
                const playable = gameState.myTurn && canPlayCard(card, game.top_card, game.wish_color, game.draw_stack);
                return renderCard(card, playable, gameState.selectedCard === i, i);
            }).join('');
            
            // Card counts
            document.getElementById('cardCounts').innerHTML = data.players.map(p => 
                `<div style="display: flex; justify-content: space-between; padding: 5px 0; ${p.id === gameState.playerId ? 'color: var(--accent);' : ''}">
                    <span>${p.avatar} ${escapeHtml(p.name)}</span>
                    <span>${p.card_count} 🃏</span>
                </div>`
            ).join('');
            
            // Mau Button
            const mauBtn = document.getElementById('mauBtn');
            mauBtn.style.display = (myHand.length === 2 && gameState.myTurn) ? 'block' : 'none';
        }
        
        function renderCard(card, playable = false, selected = false, index = -1) {
            const symbol = SYMBOLS[card.color];
            const colorClass = COLOR_CLASS[card.color];
            const valueDisplay = card.value === 'bube' ? 'B' : card.value === 'dame' ? 'D' : card.value === 'koenig' ? 'K' : card.value === 'ass' ? 'A' : card.value;
            
            return `<div class="card ${colorClass} ${playable ? 'playable' : ''} ${selected ? 'selected' : ''}" 
                        ${index >= 0 ? `onclick="selectCard(${index})"` : ''}>
                <div class="corner">${valueDisplay}<br>${symbol}</div>
                <div class="center-symbol">${symbol}</div>
                <div class="corner corner-bottom">${valueDisplay}<br>${symbol}</div>
            </div>`;
        }
        
        function canPlayCard(card, topCard, wishColor, drawStack) {
            if (!topCard) return true;
            if (drawStack > 0 && card.value !== '7') return false;
            if (card.value === 'bube' && topCard.value !== 'bube') return true;
            if (wishColor) return card.color === wishColor || card.value === 'bube';
            return card.color === topCard.color || card.value === topCard.value;
        }
        
        function selectCard(index) {
            if (!gameState.myTurn) return;
            
            if (gameState.selectedCard === index) {
                // Doppelklick = spielen
                playCard(index);
            } else {
                gameState.selectedCard = index;
                pollStatus(); // Re-render
            }
        }
        
        async function playCard(index, wishColor = null) {
            const res = await fetch(`${API_URL}?action=play`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ 
                    game_id: gameState.gameId, 
                    player_id: gameState.playerId, 
                    card_index: index,
                    wish_color: wishColor
                })
            });
            const data = await res.json();
            
            gameState.selectedCard = null;
            
            if (data.need_wish) {
                gameState.pendingWish = index;
                document.getElementById('wishModal').classList.add('active');
            } else if (data.success) {
                if (data.winner) showToast('🎉 GEWONNEN!', 'success');
            } else {
                showToast(data.error, 'error');
            }
        }
        
        function selectWishColor(color) {
            document.getElementById('wishModal').classList.remove('active');
            if (gameState.pendingWish !== null) {
                playCard(gameState.pendingWish, color);
                gameState.pendingWish = null;
            }
        }
        
        async function drawCards() {
            if (!gameState.myTurn) return;
            
            const res = await fetch(`${API_URL}?action=draw`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId })
            });
            const data = await res.json();
            
            if (data.success) {
                showToast(`${data.drawn} Karte(n) gezogen`, 'info');
            } else {
                showToast(data.error, 'error');
            }
        }
        
        async function sayMau() {
            const res = await fetch(`${API_URL}?action=mau`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId })
            });
            const data = await res.json();
            showToast(data.message || data.error, data.success ? 'success' : 'error');
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
        
        // Enter-Listener
        document.getElementById('playerNameInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') setPlayerName(); });
        document.getElementById('gameCodeInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') joinGame(); });
    </script>
</body>
</html>
