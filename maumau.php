<?php
/**
 * sgiT Education - Mau Mau v1.1
 * @version 1.1
 */
require_once __DIR__ . '/includes/game_header.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🃏 Mau Mau - sgiT Education</title>
    <!-- Zentrale Multiplayer CSS -->
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <script src="/assets/js/playing-cards.js"></script>
    <style>
        /* ===========================================
           Mau Mau-Spezifische Styles
           =========================================== */
        :root {
            --card-white: #fffef5;
        }

        /* Mau Mau Button */
        .btn.small { padding: 8px 16px; width: auto; font-size: 0.9rem; }
        .btn.mau { background: #f39c12; animation: pulse 0.5s infinite; }
        @keyframes pulse { 50% { transform: scale(1.05); } }

        /* Game Area */
        .game-container { display: grid; grid-template-columns: 1fr 250px; gap: 20px; }
        @media (max-width: 800px) { .game-container { grid-template-columns: 1fr; } }

        .play-area { background: var(--mp-bg-card); border-radius: 16px; padding: 20px; min-height: 500px; }
        
        /* Spieltisch */
        .table-area { display: flex; flex-direction: column; align-items: center; gap: 20px; }
        
        .opponents { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; margin-bottom: 20px; }
        .opponent { background: var(--mp-bg-medium); border-radius: 12px; padding: 10px 15px; text-align: center; }
        .opponent.active { border: 2px solid var(--mp-accent); }
        .opponent .cards { display: flex; gap: -10px; justify-content: center; margin-top: 5px; }
        .card-back { width: 30px; height: 45px; border-radius: 4px; border: 1px solid #555; margin-left: -15px; background-size: cover; background-position: center; }
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
        .deck-pile img.deck-card { width: 80px; height: 120px; cursor: pointer; position: absolute; border-radius: 8px; }
        .deck-pile img.deck-card:nth-child(2) { top: 2px; left: 2px; }
        .deck-pile img.deck-card:nth-child(3) { top: 4px; left: 4px; }
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

        .special-info { background: var(--mp-bg-medium); border-radius: 10px; padding: 10px; margin-top: 10px; text-align: center; }
        .special-info.warning { border: 2px solid #f39c12; }
        .special-info.danger { border: 2px solid var(--mp-error); }
        
        .rules-hint { font-size: 0.85rem; color: var(--text-muted); }
        .rules-hint ul { list-style: none; margin-top: 10px; }
        .rules-hint li { margin-bottom: 5px; }
        
        /* Wish Color Modal */
        .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 100; }
        .modal.active { display: flex; }
        .modal-content { background: var(--mp-bg-card); border-radius: 16px; padding: 25px; text-align: center; max-width: 350px; }
        .modal h2 { margin-bottom: 20px; color: var(--mp-accent); }
        .color-choice { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .color-btn { width: 60px; height: 60px; border-radius: 50%; font-size: 2rem; border: 3px solid transparent; cursor: pointer; }
        .color-btn:hover { transform: scale(1.1); }
        .color-btn.herz { background: #fee; color: #e74c3c; }
        .color-btn.karo { background: #fee; color: #e74c3c; }
        .color-btn.pik { background: #eee; color: #2c3e50; }
        .color-btn.kreuz { background: #eee; color: #2c3e50; }
        
        /* Mobile Optimierung */
        @media (max-width: 600px) {
            .card { width: 55px; height: 85px; padding: 5px; }
            .card .corner { font-size: 0.7rem; }
            .card .center-symbol { font-size: 1.8rem; }
            .card:hover { transform: translateY(-5px); }
            .card.selected { transform: translateY(-10px); }
            .my-hand { padding: 10px; }
            .my-hand .card { margin-left: -30px; }
            .center-pile { gap: 15px; padding: 15px 20px; }
            .opponents { gap: 10px; }
            .opponent { padding: 8px 10px; }
            .sidebar { gap: 10px; }
        }
        
        @media (max-width: 400px) {
            .card { width: 45px; height: 70px; padding: 3px; }
            .card .corner { font-size: 0.6rem; }
            .card .center-symbol { font-size: 1.4rem; }
            .my-hand .card { margin-left: -25px; }
        }
    </style>
</head>
<body class="mp-game-body">
    <div class="mp-game-container">
        <div class="mp-game-header">
            <div>
                <a href="multiplayer.php" class="mp-game-header__back">← Spiele-Hub</a>
                <h1>🃏 <span>Mau Mau</span></h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>

        <!-- LOBBY -->
        <div id="lobbyScreen" class="mp-game-screen active">
            <div class="mp-game-lobby">
                <div style="font-size: 4rem; margin-bottom: 10px;">🃏</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Mau Mau</h1>
                <p style="color: var(--mp-text-muted); margin-bottom: 25px;">Das Kartenspiel-Klassiker für 2-4 Spieler</p>

                <!-- Moduswahl -->
                <div class="mp-lobby-card" id="modeCard">
                    <h2>Spielmodus</h2>
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <button class="mp-game-btn" onclick="document.getElementById('modeCard').style.display='none'; document.getElementById('pvpCards').style.display='block';" style="flex: 1; min-width: 180px; display: flex; flex-direction: column; align-items: center; padding: 20px 15px;">
                            <div style="font-size: 2rem; margin-bottom: 8px;">👥</div>
                            <div style="font-weight: 600;">Gegen Spieler</div>
                            <div style="font-size: 0.8rem; color: var(--mp-primary); opacity: 0.8; margin-top: 4px;">2-4 Spieler online</div>
                        </button>
                        <a href="maumau_vs_computer.php" class="mp-game-btn mp-game-btn--secondary" style="flex: 1; min-width: 180px; display: flex; flex-direction: column; align-items: center; padding: 20px 15px; text-decoration: none;">
                            <div style="font-size: 2rem; margin-bottom: 8px;">🤖</div>
                            <div style="font-weight: 600;">Gegen Computer</div>
                            <div style="font-size: 0.8rem; opacity: 0.7; margin-top: 4px;">KI mit 3 Stufen</div>
                        </a>
                    </div>
                </div>

                <div id="pvpCards" style="display: none;">
                <div class="mp-lobby-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2>👤 Dein Name</h2>
                    <div class="mp-lobby-input-group">
                        <input type="text" id="playerNameInput" placeholder="Name..." maxlength="20">
                    </div>
                    <button class="mp-game-btn" onclick="setPlayerName()">Weiter →</button>
                </div>

                <div class="mp-lobby-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🎮 Neues Spiel</h2>
                    <button class="mp-game-btn" onclick="createGame()">Spiel erstellen</button>
                </div>

                <div class="mp-game-divider"><span>oder</span></div>

                <div class="mp-lobby-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🔗 Beitreten</h2>
                    <div class="mp-lobby-input-group">
                        <input type="text" id="gameCodeInput" class="mp-lobby-code-input" placeholder="CODE" maxlength="6">
                    </div>
                    <button class="mp-game-btn mp-game-btn--secondary" onclick="joinGame()">Beitreten →</button>
                </div>
                </div><!-- /pvpCards -->
            </div>
        </div>
        
        <!-- WAITING -->
        <div id="waitingScreen" class="mp-game-screen">
            <div class="mp-game-lobby">
                <div class="mp-lobby-code-display">
                    <p style="color: var(--mp-text-muted); font-size: 0.9rem;">Spiel-Code</p>
                    <div class="mp-lobby-code" id="displayCode">------</div>
                </div>

                <div class="mp-lobby-card">
                    <h2>👥 Spieler</h2>
                    <div class="mp-lobby-players" id="playersWaiting"></div>
                </div>

                <div id="hostControls" style="display: none;">
                    <button class="mp-game-btn" onclick="startGame()" id="startBtn" disabled>▶️ Spiel starten (min. 2)</button>
                </div>
                <p id="waitingMsg" style="color: var(--mp-text-muted); display: none;">⏳ Warte auf Host...</p>
                <button class="mp-game-btn mp-game-btn--secondary" style="margin-top: 15px;" onclick="leaveGame()">🚪 Verlassen</button>
            </div>
        </div>
        
        <!-- GAME -->
        <div id="gameScreen" class="mp-game-screen">
            <div class="game-container">
                <div class="play-area">
                    <div class="table-area">
                        <!-- Gegner -->
                        <div class="opponents" id="opponents"></div>

                        <!-- Mitte: Deck + Ablagestapel -->
                        <div class="center-pile">
                            <div class="deck-pile" onclick="drawCards()" id="deckPileContainer">
                                <span class="deck-count" id="deckCount">32</span>
                            </div>
                            <div class="discard-pile" id="discardPile"></div>
                        </div>

                        <!-- Meine Hand -->
                        <div class="my-hand" id="myHand"></div>
                    </div>
                </div>

                <div class="mp-game-sidebar">
                    <div class="mp-info-card">
                        <div class="mp-turn-info" id="turnInfo">
                            <div class="label">Am Zug:</div>
                            <div class="name" id="currentPlayerName">---</div>
                        </div>
                        <div class="special-info" id="specialInfo" style="display: none;"></div>
                    </div>

                    <div class="mp-info-card">
                        <h3>📊 Karten</h3>
                        <div id="cardCounts"></div>
                    </div>

                    <button class="mp-game-btn btn mau" id="mauBtn" onclick="sayMau()" style="display: none;">📢 MAU!</button>
                </div>
            </div>
        </div>

        <!-- RESULT -->
        <div id="resultScreen" class="mp-game-screen">
            <div class="mp-game-lobby">
                <div class="mp-lobby-card" style="border: 3px solid var(--mp-accent);">
                    <div style="font-size: 5rem;">🏆</div>
                    <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                    <button class="mp-game-btn" onclick="location.reload()">🔄 Neues Spiel</button>
                    <button class="mp-game-btn mp-game-btn--secondary" style="margin-top: 10px;" onclick="location.href='multiplayer.php'">← Zurück</button>
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
            document.querySelectorAll('.mp-game-screen').forEach(s => s.classList.remove('active'));
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
                `<div class="mp-lobby-player-slot filled">${p.avatar} ${escapeHtml(p.name)}</div>`
            ).join('');
        }
        
        function renderGame(data) {
            const game = data.game;
            const myHand = data.my_hand || [];
            
            // Turn Info
            const currentPlayer = data.players.find(p => p.order === game.current_player);
            const turnInfo = document.getElementById('turnInfo');
            turnInfo.className = 'mp-turn-info' + (gameState.myTurn ? ' my-turn' : '');
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
                    <div class="cards">${Array(Math.min(p.card_count, 10)).fill('<div class="card-back" style="background-image:url(\'' + PLAYING_CARD_SVGS.back + '\')"></div>').join('')}</div>
                    <div style="font-size: 0.8rem; color: var(--mp-text-muted);">${p.card_count} Karten</div>
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
                `<div class="mp-score-row" style="${p.id === gameState.playerId ? 'color: var(--mp-accent);' : ''}">
                    <span>${p.avatar} ${escapeHtml(p.name)}</span>
                    <span>${p.card_count} 🃏</span>
                </div>`
            ).join('');
            
            // Mau Button
            const mauBtn = document.getElementById('mauBtn');
            mauBtn.style.display = (myHand.length === 2 && gameState.myTurn) ? 'block' : 'none';
        }
        
        function renderCard(card, playable = false, selected = false, index = -1) {
            const key = PLAYING_CARD_SVGS.getKey(card);
            const src = PLAYING_CARD_SVGS[key] || PLAYING_CARD_SVGS.back;
            return `<div class="card ${playable ? 'playable' : ''} ${selected ? 'selected' : ''}"
                        style="padding:0;border:none;background:none;box-shadow:none;overflow:hidden;"
                        ${index >= 0 ? `onclick="selectCard(${index})"` : ''}>
                <img src="${src}" style="width:100%;height:100%;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.3);" draggable="false">
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
            toast.className = 'mp-game-toast ' + type;
            toast.textContent = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        // SVG deck pile init
        function initDeckPile() {
            const dp = document.getElementById('deckPileContainer');
            if (dp && typeof PLAYING_CARD_SVGS !== 'undefined') {
                const backSrc = PLAYING_CARD_SVGS.back;
                const imgs = dp.querySelectorAll('img.deck-card');
                if (imgs.length === 0) {
                    for (let i = 0; i < 3; i++) {
                        const img = document.createElement('img');
                        img.src = backSrc;
                        img.className = 'deck-card';
                        img.draggable = false;
                        dp.insertBefore(img, dp.firstChild);
                    }
                }
            }
        }
        initDeckPile();

        // Enter-Listener
        document.getElementById('playerNameInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') setPlayerName(); });
        document.getElementById('gameCodeInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') joinGame(); });
    </script>
</body>
</html>
