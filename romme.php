<?php
/**
 * ============================================================================
 * sgiT Education - Rommé v1.0
 * ============================================================================
 * 
 * Kartenspiel für 2-4 Spieler
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
    <title>🎴 Rommé - sgiT Education</title>
    <!-- Zentrale Multiplayer CSS -->
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <style>
        /* ===========================================
           Rommé-Spezifische Styles
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
        .container { max-width: 1200px; margin: 0 auto; padding: 15px; }
        
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
            background: var(--bg); color: var(--text); font-size: 1rem;
        }
        .input-group input:focus { outline: none; border-color: var(--accent); }
        .game-code-input { font-size: 1.5rem !important; text-align: center; letter-spacing: 8px; text-transform: uppercase; }
        
        .btn {
            background: var(--accent); color: var(--primary); border: none;
            padding: 12px 24px; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn.secondary { background: var(--card-bg); color: var(--text); border: 2px solid var(--accent); }
        .btn.small { padding: 8px 16px; font-size: 0.9rem; }
        .btn.full { width: 100%; }
        
        .divider { display: flex; align-items: center; margin: 20px 0; color: var(--text-muted); }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--text-muted); opacity: 0.3; }
        .divider span { padding: 0 15px; }
        
        .game-code-display { background: var(--card-bg); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        .game-code { font-size: 2.5rem; font-weight: bold; color: var(--accent); letter-spacing: 8px; font-family: monospace; }
        
        .players-list { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin: 20px 0; }
        .player-slot { background: var(--bg); border: 2px dashed var(--text-muted); border-radius: 12px; padding: 15px 20px; text-align: center; }
        .player-slot.filled { border-style: solid; border-color: var(--accent); }
        
        /* Game Layout */
        .game-container { display: grid; grid-template-columns: 1fr 220px; gap: 15px; }
        @media (max-width: 900px) { .game-container { grid-template-columns: 1fr; } }
        
        .play-area { background: var(--card-bg); border-radius: 16px; padding: 15px; }
        
        /* Melds Area */
        .melds-area { background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; margin-bottom: 15px; min-height: 120px; }
        .melds-area h3 { color: var(--accent); font-size: 0.9rem; margin-bottom: 10px; }
        .melds-container { display: flex; gap: 15px; flex-wrap: wrap; }
        .meld { display: flex; gap: -15px; padding: 10px; background: rgba(0,0,0,0.3); border-radius: 8px; }
        .meld.droppable { border: 2px dashed var(--accent); }
        
        /* Draw/Discard Piles */
        .piles-area { display: flex; gap: 30px; justify-content: center; margin: 20px 0; }
        .pile { text-align: center; }
        .pile-label { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px; }
        .pile-cards { position: relative; width: 70px; height: 100px; margin: 0 auto; }
        .pile-cards .card-back {
            position: absolute; width: 70px; height: 100px;
            background: linear-gradient(135deg, #1a5f2a, #0d3015);
            border-radius: 6px; border: 2px solid #2a7f3a;
            cursor: pointer;
        }
        .pile-cards .card-back:nth-child(2) { top: 2px; left: 2px; }
        .pile-cards .card-back:nth-child(3) { top: 4px; left: 4px; }
        .pile-count { font-size: 0.75rem; color: var(--text-muted); margin-top: 5px; }
        
        /* Cards */
        .card {
            width: 60px; height: 90px;
            background: var(--card-white);
            border-radius: 6px; border: 2px solid #ccc;
            display: flex; flex-direction: column;
            justify-content: space-between; padding: 5px;
            cursor: pointer; transition: all 0.15s;
            position: relative; flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            margin-left: -25px;
        }
        .card:first-child { margin-left: 0; }
        .card:hover { transform: translateY(-8px); z-index: 10; }
        .card.selected { transform: translateY(-15px); border-color: var(--accent); box-shadow: 0 0 15px rgba(67, 210, 64, 0.5); z-index: 11; }
        .card.red { color: #c0392b; }
        .card.black { color: #2c3e50; }
        .card .corner { font-size: 0.7rem; line-height: 1; }
        .card .corner-bottom { align-self: flex-end; transform: rotate(180deg); }
        .card .center { font-size: 1.8rem; text-align: center; flex: 1; display: flex; align-items: center; justify-content: center; }
        .card.joker { background: linear-gradient(135deg, #f1c40f, #e67e22); }
        .card.joker .center { font-size: 1.2rem; }
        
        /* Hand */
        .hand-area { background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; }
        .hand-area h3 { color: var(--accent); font-size: 0.9rem; margin-bottom: 10px; }
        .hand { display: flex; justify-content: center; flex-wrap: wrap; padding: 10px; }
        
        /* Action Buttons */
        .actions { display: flex; gap: 10px; justify-content: center; margin-top: 15px; flex-wrap: wrap; }
        
        /* Sidebar */
        .sidebar { display: flex; flex-direction: column; gap: 12px; }
        .info-card { background: var(--card-bg); border-radius: 12px; padding: 12px; }
        .info-card h3 { color: var(--accent); margin-bottom: 8px; font-size: 0.9rem; }
        
        .turn-info { text-align: center; padding: 12px; background: var(--bg); border-radius: 10px; }
        .turn-info.my-turn { border: 2px solid var(--accent); }
        .turn-info .label { font-size: 0.8rem; color: var(--text-muted); }
        .turn-info .name { font-size: 1.1rem; font-weight: bold; margin-top: 5px; }
        .turn-info .phase { font-size: 0.85rem; color: var(--accent); margin-top: 3px; }
        
        .player-row { display: flex; align-items: center; justify-content: space-between; padding: 8px; background: var(--bg); border-radius: 8px; margin-bottom: 6px; font-size: 0.9rem; }
        .player-row.active { border: 2px solid var(--accent); }
        .player-row .cards { background: var(--card-bg); padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
        
        .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 12px 20px; border-radius: 12px; font-weight: 600; z-index: 1000; }
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
                <h1>🎴 <span>Rommé</span></h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>
        
        <!-- LOBBY -->
        <div id="lobbyScreen" class="screen active">
            <div class="lobby-container">
                <div style="font-size: 4rem; margin-bottom: 10px;">🎴</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Rommé</h1>
                <p style="color: var(--text-muted); margin-bottom: 25px;">Das Kartenspiel für 2-4 Spieler</p>
                
                <div class="lobby-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2>👤 Dein Name</h2>
                    <div class="input-group">
                        <input type="text" id="playerNameInput" placeholder="Name..." maxlength="20">
                    </div>
                    <button class="btn full" onclick="setPlayerName()">Weiter →</button>
                </div>
                
                <div class="lobby-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🎮 Neues Spiel</h2>
                    <button class="btn full" onclick="createGame()">Spiel erstellen</button>
                </div>
                
                <div class="divider"><span>oder</span></div>
                
                <div class="lobby-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🔗 Beitreten</h2>
                    <div class="input-group">
                        <input type="text" id="gameCodeInput" class="game-code-input" placeholder="CODE" maxlength="6">
                    </div>
                    <button class="btn secondary full" onclick="joinGame()">Beitreten →</button>
                </div>
                
                <div class="lobby-card" style="margin-top: 20px; text-align: left;">
                    <h3 style="color: var(--accent); margin-bottom: 10px;">📜 Kurzregeln</h3>
                    <ul style="font-size: 0.85rem; color: var(--text-muted); list-style: none;">
                        <li>🎴 Ziehe 1 Karte (Stapel oder Ablage)</li>
                        <li>📤 Lege Sätze (3-4 gleiche) oder Reihen (3+ Folge)</li>
                        <li>🔢 Erstauslage: min. 30 Punkte</li>
                        <li>➕ Danach: An Kombinationen anlegen</li>
                        <li>🎯 Ziel: Alle Karten loswerden!</li>
                    </ul>
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
                    <h2>👥 Spieler (2-4)</h2>
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
            <div class="game-container">
                <div class="play-area">
                    <!-- Ausgelegte Kombinationen -->
                    <div class="melds-area">
                        <h3>📤 Ausgelegte Kombinationen</h3>
                        <div class="melds-container" id="meldsContainer"></div>
                    </div>
                    
                    <!-- Stapel -->
                    <div class="piles-area">
                        <div class="pile" onclick="drawFromDeck()">
                            <div class="pile-label">Stapel</div>
                            <div class="pile-cards">
                                <div class="card-back"></div>
                                <div class="card-back"></div>
                                <div class="card-back"></div>
                            </div>
                            <div class="pile-count" id="deckCount">0</div>
                        </div>
                        <div class="pile" onclick="drawFromDiscard()">
                            <div class="pile-label">Ablage</div>
                            <div class="pile-cards" id="discardPile"></div>
                        </div>
                    </div>
                    
                    <!-- Meine Hand -->
                    <div class="hand-area">
                        <h3>🃏 Deine Karten (<span id="handCount">0</span>) - Klicke zum Auswählen</h3>
                        <div class="hand" id="myHand"></div>
                        <div class="actions">
                            <button class="btn small" id="meldBtn" onclick="meldSelected()" disabled>📤 Auslegen</button>
                            <button class="btn small secondary" id="discardBtn" onclick="discardSelected()" disabled>🗑️ Ablegen</button>
                            <button class="btn small secondary" onclick="clearSelection()">✖️ Abwählen</button>
                        </div>
                    </div>
                </div>
                
                <div class="sidebar">
                    <div class="info-card">
                        <div class="turn-info" id="turnInfo">
                            <div class="label">Am Zug:</div>
                            <div class="name" id="currentPlayerName">---</div>
                            <div class="phase" id="phaseInfo">Ziehen</div>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3>👥 Spieler</h3>
                        <div id="playersList"></div>
                    </div>
                    
                    <div class="info-card">
                        <h3>ℹ️ Info</h3>
                        <p style="font-size: 0.8rem; color: var(--text-muted);" id="gameInfo">
                            Ziehe eine Karte um zu beginnen.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RESULT -->
        <div id="resultScreen" class="screen">
            <div class="lobby-container">
                <div class="lobby-card" style="border: 3px solid var(--accent);">
                    <div style="font-size: 5rem;">🏆</div>
                    <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                    <div id="scoresDisplay" style="margin: 20px 0;"></div>
                    <button class="btn full" onclick="location.reload()">🔄 Neues Spiel</button>
                    <button class="btn secondary full" style="margin-top: 10px;" onclick="location.href='multiplayer.php'">← Zurück</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const API_URL = '/api/romme.php';
        const POLL_INTERVAL = 800;
        
        const SUITS = { hearts: '♥', diamonds: '♦', clubs: '♣', spades: '♠', joker: '🃏' };
        const SUIT_COLORS = { hearts: 'red', diamonds: 'red', clubs: 'black', spades: 'black', joker: 'joker' };
        
        let gameState = {
            gameId: null, playerId: null, gameCode: null, isHost: false,
            myTurn: false, status: 'lobby', phase: 'draw',
            selectedCards: []
        };
        
        let playerName = '<?php echo addslashes($userName); ?>';
        let playerAvatar = '<?php echo addslashes($userAvatar); ?>';
        let walletChildId = <?php echo $walletChildId ?: 'null'; ?>;
        let pollInterval = null;
        let currentMelds = [];
        let myHand = [];
        
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
            const res = await fetch(`${API_URL}?action=create`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ player_name: playerName, avatar: playerAvatar, wallet_child_id: walletChildId })
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
            gameState.myTurn = data.my_turn;
            gameState.phase = game.phase;
            currentMelds = data.melds || [];
            
            if (game.status === 'waiting') {
                document.getElementById('playersWaiting').innerHTML = data.players.map(p => 
                    `<div class="player-slot filled">${p.avatar} ${escapeHtml(p.name)}</div>`
                ).join('');
                document.getElementById('startBtn').disabled = data.players.length < 2;
            }
            else if (game.status === 'playing') {
                if (prevStatus !== 'playing') showScreen('game');
                const me = data.players.find(p => p.hand);
                if (me) myHand = me.hand;
                renderGame(data);
            }
            else if (game.status === 'finished') {
                showResult(game, data.players);
                stopPolling();
            }
        }
        
        function renderGame(data) {
            const game = data.game;
            
            // Turn Info
            const currentPlayer = data.players.find(p => p.order === game.current_player);
            const turnInfo = document.getElementById('turnInfo');
            turnInfo.className = 'turn-info' + (gameState.myTurn ? ' my-turn' : '');
            document.getElementById('currentPlayerName').textContent = currentPlayer ? `${currentPlayer.avatar} ${currentPlayer.name}` : '---';
            document.getElementById('phaseInfo').textContent = game.phase === 'draw' ? '📥 Ziehen' : '📤 Spielen';
            
            // Info
            if (gameState.myTurn) {
                document.getElementById('gameInfo').textContent = game.phase === 'draw' 
                    ? 'Ziehe eine Karte vom Stapel oder Ablagestapel.'
                    : 'Lege Kombinationen aus oder lege eine Karte ab.';
            } else {
                document.getElementById('gameInfo').textContent = `Warte auf ${currentPlayer?.name || 'Spieler'}...`;
            }
            
            // Deck
            document.getElementById('deckCount').textContent = game.deck_count + ' Karten';
            
            // Discard Pile
            const discardPile = document.getElementById('discardPile');
            if (game.top_discard) {
                discardPile.innerHTML = renderCard(game.top_discard, false, false);
            } else {
                discardPile.innerHTML = '<div style="width:70px;height:100px;border:2px dashed #555;border-radius:6px;"></div>';
            }
            
            // Melds
            const meldsContainer = document.getElementById('meldsContainer');
            if (currentMelds.length === 0) {
                meldsContainer.innerHTML = '<p style="color:var(--text-muted);font-size:0.85rem;">Noch keine Kombinationen ausgelegt</p>';
            } else {
                meldsContainer.innerHTML = currentMelds.map((meld, i) => `
                    <div class="meld" data-index="${i}" onclick="layoffToMeld(${i})">
                        ${meld.cards.map(c => renderCard(c, false, false, true)).join('')}
                    </div>
                `).join('');
            }
            
            // Hand
            document.getElementById('handCount').textContent = myHand.length;
            document.getElementById('myHand').innerHTML = myHand.map((card, i) => 
                renderCard(card, gameState.selectedCards.includes(card.id), true)
            ).join('');
            
            // Players
            document.getElementById('playersList').innerHTML = data.players.map(p => `
                <div class="player-row ${p.order === game.current_player ? 'active' : ''}">
                    <span>${p.avatar} ${escapeHtml(p.name)} ${p.has_melded ? '✓' : ''}</span>
                    <span class="cards">${p.card_count} 🃏</span>
                </div>
            `).join('');
            
            // Buttons
            updateButtons();
        }
        
        function renderCard(card, selected = false, clickable = false, small = false) {
            const isJoker = card.value === 'JOKER';
            const colorClass = isJoker ? 'joker' : SUIT_COLORS[card.suit];
            const symbol = SUITS[card.suit];
            const size = small ? 'style="width:50px;height:75px;font-size:0.6rem;"' : '';
            
            return `<div class="card ${colorClass} ${selected ? 'selected' : ''}" ${size}
                        ${clickable ? `onclick="toggleCard('${card.id}')"` : ''}>
                <div class="corner">${card.value}<br>${symbol}</div>
                <div class="center">${symbol}</div>
                <div class="corner corner-bottom">${card.value}<br>${symbol}</div>
            </div>`;
        }
        
        function toggleCard(cardId) {
            if (!gameState.myTurn || gameState.phase === 'draw') return;
            
            const idx = gameState.selectedCards.indexOf(cardId);
            if (idx >= 0) {
                gameState.selectedCards.splice(idx, 1);
            } else {
                gameState.selectedCards.push(cardId);
            }
            pollStatus();
        }
        
        function clearSelection() {
            gameState.selectedCards = [];
            pollStatus();
        }
        
        function updateButtons() {
            const meldBtn = document.getElementById('meldBtn');
            const discardBtn = document.getElementById('discardBtn');
            
            const canAct = gameState.myTurn && gameState.phase === 'play';
            meldBtn.disabled = !canAct || gameState.selectedCards.length < 3;
            discardBtn.disabled = !canAct || gameState.selectedCards.length !== 1;
        }
        
        async function drawFromDeck() {
            if (!gameState.myTurn || gameState.phase !== 'draw') return;
            const res = await fetch(`${API_URL}?action=draw`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId, source: 'deck' })
            });
            const data = await res.json();
            if (data.success) showToast('Karte gezogen', 'info');
            else showToast(data.error, 'error');
        }
        
        async function drawFromDiscard() {
            if (!gameState.myTurn || gameState.phase !== 'draw') return;
            const res = await fetch(`${API_URL}?action=draw`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId, source: 'discard' })
            });
            const data = await res.json();
            if (data.success) showToast('Karte vom Ablagestapel', 'info');
            else showToast(data.error, 'error');
        }
        
        async function meldSelected() {
            if (gameState.selectedCards.length < 3) return;
            const res = await fetch(`${API_URL}?action=meld`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId, card_ids: gameState.selectedCards })
            });
            const data = await res.json();
            if (data.success) {
                if (data.winner) showToast('🎉 GEWONNEN!', 'success');
                else showToast(`${data.type === 'set' ? 'Satz' : 'Reihe'} ausgelegt! (${data.points} Punkte)`, 'success');
                gameState.selectedCards = [];
            } else showToast(data.error, 'error');
        }
        
        async function layoffToMeld(meldIndex) {
            if (!gameState.myTurn || gameState.phase !== 'play') return;
            if (gameState.selectedCards.length !== 1) {
                showToast('Wähle genau 1 Karte zum Anlegen', 'info');
                return;
            }
            const res = await fetch(`${API_URL}?action=layoff`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId, card_id: gameState.selectedCards[0], meld_index: meldIndex })
            });
            const data = await res.json();
            if (data.success) {
                if (data.winner) showToast('🎉 GEWONNEN!', 'success');
                else showToast('Karte angelegt!', 'success');
                gameState.selectedCards = [];
            } else showToast(data.error, 'error');
        }
        
        async function discardSelected() {
            if (gameState.selectedCards.length !== 1) return;
            const res = await fetch(`${API_URL}?action=discard`, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId, card_id: gameState.selectedCards[0] })
            });
            const data = await res.json();
            if (data.success) {
                if (data.winner) showToast('🎉 GEWONNEN!', 'success');
                gameState.selectedCards = [];
            } else showToast(data.error, 'error');
        }
        
        function showResult(game, players) {
            showScreen('result');
            const winner = players.find(p => p.id == game.winner);
            document.getElementById('winnerText').textContent = winner ? `${winner.avatar} ${winner.name} gewinnt!` : 'Spiel beendet!';
            
            document.getElementById('scoresDisplay').innerHTML = players
                .sort((a, b) => b.score - a.score)
                .map(p => `<div style="padding:8px;background:var(--bg);border-radius:8px;margin:5px 0;display:flex;justify-content:space-between;">
                    <span>${p.avatar} ${escapeHtml(p.name)}</span>
                    <span style="color:${p.score >= 0 ? 'var(--accent)' : 'var(--red)'}">${p.score} Punkte</span>
                </div>`).join('');
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
