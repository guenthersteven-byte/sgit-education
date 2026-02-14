<?php
/**
 * sgiT Education - Romme v1.1
 * @version 1.1
 */
require_once __DIR__ . '/includes/game_header.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎴 Rommé - sgiT Education</title>
    <!-- Zentrale Multiplayer CSS -->
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <script src="/assets/js/playing-cards.js"></script>
    <style>
        /* ===========================================
           Rommé-Spezifische Styles
           =========================================== */
        :root {
            --card-white: #fffef5;
        }
        
        /* Game Layout */
        .game-container { display: grid; grid-template-columns: 1fr 220px; gap: 15px; }
        @media (max-width: 900px) { .game-container { grid-template-columns: 1fr; } }
        
        .play-area { background: var(--mp-bg-card); border-radius: 16px; padding: 15px; }
        
        /* Melds Area */
        .melds-area { background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; margin-bottom: 15px; min-height: 120px; }
        .melds-area h3 { color: var(--mp-accent); font-size: 0.9rem; margin-bottom: 10px; }
        .melds-container { display: flex; gap: 15px; flex-wrap: wrap; }
        .meld { display: flex; gap: -15px; padding: 10px; background: rgba(0,0,0,0.3); border-radius: 8px; }
        .meld.droppable { border: 2px dashed var(--mp-accent); }
        
        /* Draw/Discard Piles */
        .piles-area { display: flex; gap: 30px; justify-content: center; margin: 20px 0; }
        .pile { text-align: center; }
        .pile-label { font-size: 0.8rem; color: var(--mp-text-muted); margin-bottom: 5px; }
        .pile-cards { position: relative; width: 70px; height: 100px; margin: 0 auto; }
        .pile-cards .card-back {
            position: absolute; width: 70px; height: 100px;
            border-radius: 6px;
            cursor: pointer;
            background-size: cover; background-position: center;
        }
        .pile-cards .card-back:nth-child(2) { top: 2px; left: 2px; }
        .pile-cards .card-back:nth-child(3) { top: 4px; left: 4px; }
        .pile-count { font-size: 0.75rem; color: var(--mp-text-muted); margin-top: 5px; }
        
        /* Cards */
        /* Cards - mit Animationen aus multiplayer-theme.css */
        .card {
            width: 60px; height: 90px;
            background: var(--card-white);
            border-radius: 6px; border: 2px solid #ccc;
            display: flex; flex-direction: column;
            justify-content: space-between; padding: 5px;
            cursor: pointer; transition: var(--mp-transition);
            position: relative; flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            margin-left: -25px;
        }
        .card:first-child { margin-left: 0; }
        .card:hover { transform: translateY(-8px); z-index: 10; }
        .card.deal { animation: mp-cardDeal 0.3s ease-out forwards; }
        .card.selected { transform: translateY(-15px); border-color: var(--mp-accent); box-shadow: 0 0 15px var(--mp-accent-glow); z-index: 11; }
        .card.red { color: #c0392b; }
        .card.black { color: #2c3e50; }
        .card .corner { font-size: 0.7rem; line-height: 1; }
        .card .corner-bottom { align-self: flex-end; transform: rotate(180deg); }
        .card .center { font-size: 1.8rem; text-align: center; flex: 1; display: flex; align-items: center; justify-content: center; }
        .card.joker { background: linear-gradient(135deg, #f1c40f, #e67e22); }
        .card.joker .center { font-size: 1.2rem; }
        
        /* Hand */
        .hand-area { background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; }
        .hand-area h3 { color: var(--mp-accent); font-size: 0.9rem; margin-bottom: 10px; }
        .hand { display: flex; justify-content: center; flex-wrap: wrap; padding: 10px; }
        
        /* BUG-054 FIX: Sortier-Buttons */
        .sort-buttons { display: flex; gap: 8px; margin-bottom: 10px; justify-content: center; }
        .mp-game-btn.tiny { padding: 4px 10px; font-size: 0.75rem; border-radius: 4px; }
        .mp-game-btn.small { padding: 8px 16px; font-size: 0.9rem; }
        .mp-game-btn.full { width: 100%; }

        /* Action Buttons */
        .actions { display: flex; gap: 10px; justify-content: center; margin-top: 15px; flex-wrap: wrap; }

        .mp-score-row .cards { background: var(--mp-bg-card); padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
        .mp-score-row.active { border: 2px solid var(--mp-accent); }
        
        /* Mobile Optimierung */
        @media (max-width: 600px) {
            .card { width: 45px; height: 68px; padding: 3px; margin-left: -18px; }
            .card .corner { font-size: 0.55rem; }
            .card .center { font-size: 1.3rem; }
            .melds-area { padding: 10px; min-height: 90px; }
            .meld { padding: 6px; gap: -12px; }
            .pile-cards, .pile-cards .card-back { width: 50px; height: 72px; }
            .piles-area { gap: 20px; }
            .my-hand { padding: 10px; }
            .sidebar { gap: 10px; }
        }
        
        @media (max-width: 400px) {
            .card { width: 38px; height: 58px; margin-left: -15px; }
            .card .corner { font-size: 0.5rem; }
            .card .center { font-size: 1.1rem; }
        }
    </style>
</head>
<body class="mp-game-body">
    <div class="mp-game-container">
        <div class="mp-game-header">
            <div>
                <a href="multiplayer.php" class="mp-game-header__back">← Spiele-Hub</a>
                <h1>🎴 <span>Rommé</span></h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>
        
        <!-- LOBBY -->
        <div id="lobbyScreen" class="mp-game-screen active">
            <div class="mp-game-lobby">
                <div style="font-size: 4rem; margin-bottom: 10px;">🎴</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Rommé</h1>
                <p style="color: var(--mp-text-muted); margin-bottom: 25px;">Das Kartenspiel für 2-4 Spieler</p>

                <div class="mp-lobby-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2>👤 Dein Name</h2>
                    <div class="mp-lobby-input-group">
                        <input type="text" id="playerNameInput" placeholder="Name..." maxlength="20">
                    </div>
                    <button class="mp-game-btn full" onclick="setPlayerName()">Weiter →</button>
                </div>

                <div class="mp-lobby-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🎮 Neues Spiel</h2>
                    <button class="mp-game-btn full" onclick="createGame()">👥 Gegen Mitspieler</button>
                    <button class="mp-game-btn mp-game-btn--secondary full" style="margin-top: 10px;" onclick="location.href='romme_vs_computer.php'">🤖 Gegen Computer</button>
                </div>

                <div class="mp-game-divider"><span>oder</span></div>

                <div class="mp-lobby-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🔗 Beitreten</h2>
                    <div class="mp-lobby-input-group">
                        <input type="text" id="gameCodeInput" class="mp-lobby-code-input" placeholder="CODE" maxlength="6">
                    </div>
                    <button class="mp-game-btn mp-game-btn--secondary full" onclick="joinGame()">Beitreten →</button>
                </div>

                <div class="mp-lobby-card" style="margin-top: 20px; text-align: left;">
                    <h3 style="color: var(--mp-accent); margin-bottom: 10px;">📜 Kurzregeln</h3>
                    <ul style="font-size: 0.85rem; color: var(--mp-text-muted); list-style: none;">
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
        <div id="waitingScreen" class="mp-game-screen">
            <div class="mp-game-lobby">
                <div class="mp-lobby-code-display">
                    <p style="color: var(--mp-text-muted);">Spiel-Code</p>
                    <div class="mp-lobby-code" id="displayCode">------</div>
                </div>
                <div class="mp-lobby-card">
                    <h2>👥 Spieler (2-4)</h2>
                    <div class="mp-lobby-players" id="playersWaiting"></div>
                </div>
                <div id="hostControls" style="display: none;">
                    <button class="mp-game-btn full" onclick="startGame()" id="startBtn" disabled>▶️ Spiel starten</button>
                </div>
                <p id="waitingMsg" style="color: var(--mp-text-muted); display: none;">⏳ Warte auf Host...</p>
                <button class="mp-game-btn mp-game-btn--secondary full" style="margin-top: 15px;" onclick="leaveGame()">🚪 Verlassen</button>
            </div>
        </div>
        
        <!-- GAME -->
        <div id="gameScreen" class="mp-game-screen">
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
                        <div class="sort-buttons">
                            <button class="mp-game-btn tiny" onclick="sortHand('suit')" title="Nach Farbe sortieren">🎨 Farbe</button>
                            <button class="mp-game-btn tiny" onclick="sortHand('value')" title="Nach Wert sortieren">🔢 Wert</button>
                        </div>
                        <div class="hand" id="myHand"></div>
                        <div class="actions">
                            <button class="mp-game-btn small" id="meldBtn" onclick="meldSelected()" disabled>📤 Auslegen</button>
                            <button class="mp-game-btn mp-game-btn--secondary small" id="discardBtn" onclick="discardSelected()" disabled>🗑️ Ablegen</button>
                            <button class="mp-game-btn mp-game-btn--secondary small" onclick="clearSelection()">✖️ Abwählen</button>
                        </div>
                    </div>
                </div>

                <div class="mp-game-sidebar">
                    <div class="mp-info-card">
                        <div class="mp-turn-info" id="turnInfo">
                            <div class="label">Am Zug:</div>
                            <div class="name" id="currentPlayerName">---</div>
                            <div class="phase" id="phaseInfo">Ziehen</div>
                        </div>
                    </div>

                    <div class="mp-info-card">
                        <h3>👥 Spieler</h3>
                        <div id="playersList"></div>
                    </div>

                    <div class="mp-info-card">
                        <h3>ℹ️ Info</h3>
                        <p style="font-size: 0.8rem; color: var(--mp-text-muted);" id="gameInfo">
                            Ziehe eine Karte um zu beginnen.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RESULT -->
        <div id="resultScreen" class="mp-game-screen">
            <div class="mp-game-lobby">
                <div class="mp-lobby-card" style="border: 3px solid var(--mp-accent);">
                    <div style="font-size: 5rem;">🏆</div>
                    <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                    <div id="scoresDisplay" style="margin: 20px 0;"></div>
                    <button class="mp-game-btn full" onclick="location.reload()">🔄 Neues Spiel</button>
                    <button class="mp-game-btn mp-game-btn--secondary full" style="margin-top: 10px;" onclick="location.href='multiplayer.php'">← Zurück</button>
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
        let sortMode = 'none'; // BUG-054 FIX: Sortier-Modus speichern
        
        // BUG-054 FIX: Karten sortieren
        const SUIT_ORDER = { 'hearts': 0, 'diamonds': 1, 'clubs': 2, 'spades': 3, 'joker': 4 };
        const VALUE_ORDER = { 'A': 1, '2': 2, '3': 3, '4': 4, '5': 5, '6': 6, '7': 7, '8': 8, '9': 9, '10': 10, 'J': 11, 'Q': 12, 'K': 13, 'JOKER': 14 };
        
        function applySorting() {
            if (sortMode === 'suit') {
                myHand.sort((a, b) => {
                    const suitDiff = SUIT_ORDER[a.suit] - SUIT_ORDER[b.suit];
                    if (suitDiff !== 0) return suitDiff;
                    return VALUE_ORDER[a.value] - VALUE_ORDER[b.value];
                });
            } else if (sortMode === 'value') {
                myHand.sort((a, b) => {
                    const valueDiff = VALUE_ORDER[a.value] - VALUE_ORDER[b.value];
                    if (valueDiff !== 0) return valueDiff;
                    return SUIT_ORDER[a.suit] - SUIT_ORDER[b.suit];
                });
            }
        }
        
        function sortHand(mode) {
            sortMode = mode;
            applySorting();
            // Neu rendern
            document.getElementById('myHand').innerHTML = myHand.map((card, i) => 
                renderCard(card, gameState.selectedCards.includes(card.id), true)
            ).join('');
            showToast(`🎴 Sortiert nach ${mode === 'suit' ? 'Farbe' : 'Wert'}`, 'info');
        }
        
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
                    `<div class="mp-lobby-player-slot filled">${p.avatar} ${escapeHtml(p.name)}</div>`
                ).join('');
                document.getElementById('startBtn').disabled = data.players.length < 2;
            }
            else if (game.status === 'playing') {
                if (prevStatus !== 'playing') showScreen('game');
                const me = data.players.find(p => p.hand);
                if (me) {
                    myHand = me.hand;
                    // BUG-054 FIX: Sortierung beibehalten nach Server-Update
                    if (sortMode !== 'none') {
                        applySorting();
                    }
                }
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
            turnInfo.className = 'mp-turn-info' + (gameState.myTurn ? ' my-turn' : '');
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
                meldsContainer.innerHTML = '<p style="color:var(--mp-text-muted);font-size:0.85rem;">Noch keine Kombinationen ausgelegt</p>';
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
                <div class="mp-score-row ${p.order === game.current_player ? 'active' : ''}">
                    <span>${p.avatar} ${escapeHtml(p.name)} ${p.has_melded ? '✓' : ''}</span>
                    <span class="cards">${p.card_count} 🃏</span>
                </div>
            `).join('');
            
            // Buttons
            updateButtons();
        }
        
        // Mapping: Romme API suit names -> playing-cards.js keys
        const SUIT_TO_DE = { hearts: 'herz', diamonds: 'karo', clubs: 'kreuz', spades: 'pik' };
        const VALUE_TO_DE = { 'A': 'ass', 'J': 'bube', 'Q': 'dame', 'K': 'koenig', 'JOKER': 'joker' };

        function getCardSvgKey(card) {
            if (card.value === 'JOKER') return 'joker_red';
            const suit = SUIT_TO_DE[card.suit] || card.suit;
            const val = VALUE_TO_DE[card.value] || card.value;
            return `${suit}_${val}`;
        }

        function renderCard(card, selected = false, clickable = false, small = false) {
            const isJoker = card.value === 'JOKER';
            const w = small ? 50 : 60;
            const h = small ? 75 : 90;

            if (typeof PLAYING_CARD_SVGS !== 'undefined') {
                const key = getCardSvgKey(card);
                const src = PLAYING_CARD_SVGS[key] || PLAYING_CARD_SVGS.back;
                return `<div class="card ${selected ? 'selected' : ''}" style="padding:0;border:none;background:none;box-shadow:none;width:${w}px;height:${h}px;"
                            ${clickable ? `onclick="toggleCard('${card.id}')"` : ''}>
                    <img src="${src}" style="width:100%;height:100%;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.3);" draggable="false">
                </div>`;
            }
            // Fallback to unicode
            const colorClass = isJoker ? 'joker' : SUIT_COLORS[card.suit];
            const symbol = SUITS[card.suit];
            return `<div class="card ${colorClass} ${selected ? 'selected' : ''}" style="width:${w}px;height:${h}px;"
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
                .map(p => `<div style="padding:8px;background:var(--mp-bg-dark);border-radius:8px;margin:5px 0;display:flex;justify-content:space-between;">
                    <span>${p.avatar} ${escapeHtml(p.name)}</span>
                    <span style="color:${p.score >= 0 ? 'var(--mp-accent)' : 'var(--mp-error)'}">${p.score} Punkte</span>
                </div>`).join('');
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
        
        // SVG card back init
        if (typeof PLAYING_CARD_SVGS !== 'undefined') {
            document.querySelectorAll('.pile-cards .card-back').forEach(el => {
                el.style.backgroundImage = `url('${PLAYING_CARD_SVGS.back}')`;
            });
        }

        document.getElementById('playerNameInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') setPlayerName(); });
        document.getElementById('gameCodeInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') joinGame(); });
    </script>
</body>
</html>
