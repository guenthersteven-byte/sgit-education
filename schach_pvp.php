<?php
/**
 * ============================================================================
 * sgiT Education - Schach PvP v1.0
 * ============================================================================
 * 
 * Schach Multiplayer für 2 Spieler
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
    <title>♟️ Schach - sgiT Education</title>
    <!-- Zentrale Multiplayer CSS -->
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <style>
        /* ===========================================
           Schach PvP-Spezifische Styles
           =========================================== */
        :root {
            --light-sq: #f0d9b5;
            --dark-sq: #b58863;
            --highlight: rgba(67, 210, 64, 0.5);
            --check: rgba(231, 76, 60, 0.6);
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--mp-bg-dark) 0%, var(--mp-primary) 100%);
            min-height: 100vh;
            color: var(--mp-text);
            margin: 0; padding: 0;
        }
        .container { max-width: 1000px; margin: 0 auto; padding: 15px; }
        
        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 20px; background: var(--mp-bg-card); border-radius: 12px; margin-bottom: 20px;
        }
        .header h1 { font-size: 1.4rem; }
        .header h1 span { color: var(--mp-accent); }
        .back-link { color: var(--mp-accent); text-decoration: none; }
        
        .screen { display: none; }
        .screen.active { display: block; }
        
        /* Lobby */
        .lobby-container { max-width: 500px; margin: 30px auto; text-align: center; }
        .lobby-card { background: var(--card-bg); border-radius: 16px; padding: 25px; margin-bottom: 20px; }
        .lobby-card h2 { color: var(--accent); margin-bottom: 15px; }
        .input-group { margin-bottom: 15px; }
        .input-group input {
            width: 100%; padding: 12px; border: 2px solid transparent; border-radius: 10px;
            background: var(--bg); color: var(--text); font-size: 1rem;
        }
        .input-group input:focus { outline: none; border-color: var(--accent); }
        .game-code-input { font-size: 1.5rem !important; text-align: center; letter-spacing: 8px; text-transform: uppercase; }
        
        .btn {
            background: var(--accent); color: var(--primary); border: none;
            padding: 14px 28px; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn.secondary { background: var(--card-bg); color: var(--text); border: 2px solid var(--accent); }
        .btn.danger { background: #c0392b; }
        
        .divider { display: flex; align-items: center; margin: 20px 0; color: var(--text-muted); }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--text-muted); opacity: 0.3; }
        .divider span { padding: 0 15px; }
        
        .game-code-display { background: var(--card-bg); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        .game-code { font-size: 2.5rem; font-weight: bold; color: var(--accent); letter-spacing: 8px; font-family: monospace; }
        
        .players-waiting { display: flex; gap: 20px; justify-content: center; margin: 20px 0; }
        .player-slot { background: var(--bg); border: 2px dashed var(--text-muted); border-radius: 12px; padding: 20px; text-align: center; min-width: 150px; }
        .player-slot.filled { border-style: solid; border-color: var(--accent); }
        .player-slot .piece { font-size: 2rem; margin-bottom: 5px; }
        
        /* Game */
        .game-container { display: grid; grid-template-columns: 1fr 250px; gap: 20px; }
        @media (max-width: 800px) { .game-container { grid-template-columns: 1fr; } }
        
        .board-area { background: var(--card-bg); border-radius: 16px; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        
        .board {
            display: grid;
            grid-template-columns: repeat(8, 50px);
            grid-template-rows: repeat(8, 50px);
            border: 4px solid #5d4e37;
            border-radius: 4px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        }
        
        .cell {
            width: 50px; height: 50px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; position: relative; font-size: 2.2rem;
            user-select: none;
        }
        .cell.light { background: var(--light-sq); }
        .cell.dark { background: var(--dark-sq); }
        .cell.selected { box-shadow: inset 0 0 0 4px var(--mp-accent); }
        .cell.valid-move { animation: mp-fieldPulse 1s ease infinite; }
        .cell.valid-move::after {
            content: ''; position: absolute;
            width: 18px; height: 18px; background: var(--highlight); border-radius: 50%;
        }
        .cell.capture-move::after {
            width: 44px; height: 44px; background: transparent;
            border: 4px solid var(--highlight); border-radius: 50%;
        }
        .cell.check { background: var(--check) !important; animation: mp-shake 0.3s ease; }
        .cell.last-move { background: rgba(255, 255, 0, 0.3); }
        
        .piece { cursor: pointer; transition: var(--mp-transition); }
        .piece:hover { transform: scale(1.1); }
        .piece.moving { animation: mp-pieceMove 0.4s ease; }
        .piece.captured { animation: mp-pieceCapture 0.5s ease forwards; }
        
        /* BUG-055 FIX: Deutliche Unterscheidung Weiß vs Schwarz */
        .piece.white { 
            color: #FFFFFF;
            text-shadow: 
                -1px -1px 0 #333,
                1px -1px 0 #333,
                -1px 1px 0 #333,
                1px 1px 0 #333,
                0 0 3px #333;
        }
        .piece.black { 
            color: #1a1a1a;
            text-shadow: 
                -1px -1px 0 #888,
                1px -1px 0 #888,
                -1px 1px 0 #888,
                1px 1px 0 #888,
                0 0 2px #aaa;
        }
        
        .coord { position: absolute; font-size: 0.65rem; color: #666; font-weight: bold; }
        .coord.file { bottom: 2px; right: 4px; }
        .coord.rank { top: 2px; left: 4px; }
        .cell.light .coord { color: var(--dark-sq); }
        .cell.dark .coord { color: var(--light-sq); }
        
        /* Sidebar */
        .sidebar { display: flex; flex-direction: column; gap: 15px; }
        .info-card { background: var(--card-bg); border-radius: 12px; padding: 15px; }
        .info-card h3 { color: var(--accent); margin-bottom: 10px; font-size: 1rem; }
        
        .turn-info { text-align: center; padding: 15px; background: var(--bg); border-radius: 10px; }
        .turn-info.my-turn { border: 2px solid var(--accent); }
        .turn-info.check { border: 2px solid #e74c3c; animation: pulse-red 1s infinite; }
        @keyframes pulse-red { 50% { box-shadow: 0 0 20px rgba(231, 76, 60, 0.5); } }
        .turn-info .label { font-size: 0.85rem; color: var(--text-muted); }
        .turn-info .name { font-size: 1.2rem; font-weight: bold; margin-top: 5px; }
        
        .player-row { display: flex; align-items: center; justify-content: space-between; padding: 10px; background: var(--bg); border-radius: 8px; margin-bottom: 8px; }
        .player-row.active { border: 2px solid var(--accent); }
        .player-row .piece-icon { font-size: 1.5rem; margin-right: 10px; }
        
        /* Promotion Modal */
        .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 100; }
        .modal.active { display: flex; }
        .modal-content { background: var(--card-bg); border-radius: 16px; padding: 25px; text-align: center; }
        .modal h2 { margin-bottom: 20px; color: var(--accent); }
        .promo-choice { display: flex; gap: 15px; justify-content: center; }
        .promo-btn { width: 60px; height: 60px; font-size: 2.5rem; border: 3px solid var(--accent); border-radius: 10px; cursor: pointer; background: var(--bg); }
        .promo-btn:hover { transform: scale(1.1); background: var(--card-bg); }
        
        .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 15px 25px; border-radius: 12px; font-weight: 600; z-index: 1000; }
        .toast.success { background: var(--accent); color: var(--primary); }
        .toast.error { background: #e74c3c; color: white; }
        .toast.info { background: var(--card-bg); border: 2px solid var(--mp-accent); }
        
        /* Mobile Optimierung */
        @media (max-width: 500px) {
            .board-area { padding: 10px; }
            .board {
                grid-template-columns: repeat(8, 40px);
                grid-template-rows: repeat(8, 40px);
            }
            .cell {
                width: 40px;
                height: 40px;
                font-size: 1.8rem;
            }
            .sidebar { gap: 10px; }
            .info-card { padding: 10px; }
        }
        
        @media (max-width: 380px) {
            .board {
                grid-template-columns: repeat(8, 35px);
                grid-template-rows: repeat(8, 35px);
            }
            .cell {
                width: 35px;
                height: 35px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="multiplayer.php" class="back-link">← Multiplayer</a>
                <h1>♟️ <span>Schach</span></h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>
        
        <!-- LOBBY -->
        <div id="lobbyScreen" class="screen active">
            <div class="lobby-container">
                <div style="font-size: 4rem; margin-bottom: 10px;">♟️</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Schach</h1>
                <p style="color: var(--text-muted); margin-bottom: 25px;">Das königliche Spiel für 2 Spieler</p>
                
                <div class="lobby-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2>👤 Dein Name</h2>
                    <div class="input-group">
                        <input type="text" id="playerNameInput" placeholder="Name..." maxlength="20">
                    </div>
                    <button class="btn" onclick="setPlayerName()">Weiter →</button>
                </div>
                
                <div class="lobby-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🎮 Neues Spiel</h2>
                    <p style="color: var(--text-muted); margin-bottom: 15px;">Du spielst mit ♔ Weiß (beginnst)</p>
                    <button class="btn" onclick="createGame()">Spiel erstellen</button>
                </div>
                
                <div class="divider"><span>oder</span></div>
                
                <div class="lobby-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🔗 Beitreten</h2>
                    <p style="color: var(--text-muted); margin-bottom: 15px;">Du spielst mit ♚ Schwarz</p>
                    <div class="input-group">
                        <input type="text" id="gameCodeInput" class="game-code-input" placeholder="CODE" maxlength="6">
                    </div>
                    <button class="btn secondary" onclick="joinGame()">Beitreten →</button>
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
                    <h2>👥 Spieler</h2>
                    <div class="players-waiting" id="playersWaiting">
                        <div class="player-slot"><div class="piece">♔</div>Wartet...</div>
                        <div class="player-slot"><div class="piece">♚</div>Wartet...</div>
                    </div>
                </div>
                
                <div id="hostControls" style="display: none;">
                    <button class="btn" onclick="startGame()" id="startBtn" disabled>▶️ Spiel starten</button>
                </div>
                <p id="waitingMsg" style="color: var(--text-muted); display: none;">⏳ Warte auf Host...</p>
                <button class="btn secondary" style="margin-top: 15px;" onclick="leaveGame()">🚪 Verlassen</button>
            </div>
        </div>
        
        <!-- GAME -->
        <div id="gameScreen" class="screen">
            <div class="game-container">
                <div class="board-area">
                    <div class="board" id="board"></div>
                </div>
                
                <div class="sidebar">
                    <div class="info-card">
                        <div class="turn-info" id="turnInfo">
                            <div class="label">Am Zug:</div>
                            <div class="name" id="currentPlayerName">---</div>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3>👥 Spieler</h3>
                        <div class="player-row" id="whitePlayer">
                            <span><span class="piece-icon">♔</span> <span class="pname">Weiß</span></span>
                        </div>
                        <div class="player-row" id="blackPlayer">
                            <span><span class="piece-icon">♚</span> <span class="pname">Schwarz</span></span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <button class="btn danger" onclick="resignGame()">🏳️ Aufgeben</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RESULT -->
        <div id="resultScreen" class="screen">
            <div class="lobby-container">
                <div class="lobby-card" style="border: 3px solid var(--accent);">
                    <div style="font-size: 5rem;" id="resultIcon">🏆</div>
                    <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                    <p style="color: var(--text-muted); margin-bottom: 20px;" id="winReason"></p>
                    <button class="btn" onclick="location.reload()">🔄 Neues Spiel</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='multiplayer.php'">← Zurück</button>
                </div>
            </div>
        </div>
        
        <!-- Promotion Modal -->
        <div class="modal" id="promoModal">
            <div class="modal-content">
                <h2>👑 Bauernumwandlung</h2>
                <p style="color: var(--text-muted); margin-bottom: 20px;">Wähle eine Figur:</p>
                <div class="promo-choice" id="promoChoices"></div>
            </div>
        </div>
    </div>
    
    <script>
        const API_URL = '/api/schach_pvp.php';
        const POLL_INTERVAL = 800;
        
        const PIECES = {
            // BUG-055 FIX: Gleiche Symbole für beide, CSS bestimmt Farbe
            wK: '♚', wQ: '♛', wR: '♜', wB: '♝', wN: '♞', wP: '♟',
            bK: '♚', bQ: '♛', bR: '♜', bB: '♝', bN: '♞', bP: '♟'
        };
        
        let gameState = {
            gameId: null,
            playerId: null,
            gameCode: null,
            isHost: false,
            myColor: null,
            myTurn: false,
            status: 'lobby',
            selectedCell: null,
            validMoves: [],
            lastMove: null
        };
        
        let playerName = '<?php echo addslashes($userName); ?>';
        let playerAvatar = '<?php echo addslashes($userAvatar); ?>';
        let walletChildId = <?php echo $walletChildId ?: 'null'; ?>;
        let pollInterval = null;
        let currentBoard = {};
        let currentCastling = {};
        let currentEnPassant = null;
        
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
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ player_name: playerName, avatar: playerAvatar, wallet_child_id: walletChildId })
            });
            const data = await res.json();
            
            if (data.success) {
                gameState = { ...gameState, gameId: data.game_id, playerId: data.player_id, gameCode: data.game_code, isHost: true, myColor: data.color };
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
                gameState = { ...gameState, gameId: data.game_id, playerId: data.player_id, gameCode: code, isHost: false, myColor: data.color };
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
            currentBoard = data.board;
            currentCastling = data.castling;
            currentEnPassant = game.en_passant;
            
            if (game.status === 'waiting') {
                updateWaitingRoom(data.players);
                document.getElementById('startBtn').disabled = data.players.length < 2;
            }
            else if (game.status === 'playing') {
                if (prevStatus !== 'playing') showScreen('game');
                
                // Bauernumwandlung?
                if (game.pending_promotion && gameState.myTurn) {
                    showPromotionModal();
                }
                
                renderBoard(data);
            }
            else if (game.status === 'finished') {
                showResult(game, data.players);
                stopPolling();
            }
        }
        
        function updateWaitingRoom(players) {
            const white = players.find(p => p.color === 'white');
            const black = players.find(p => p.color === 'black');
            
            document.getElementById('playersWaiting').innerHTML = `
                <div class="player-slot ${white ? 'filled' : ''}">
                    <div class="piece">♔</div>
                    ${white ? `${white.avatar} ${escapeHtml(white.player_name)}` : 'Wartet...'}
                </div>
                <div class="player-slot ${black ? 'filled' : ''}">
                    <div class="piece">♚</div>
                    ${black ? `${black.avatar} ${escapeHtml(black.player_name)}` : 'Wartet...'}
                </div>
            `;
        }
        
        function renderBoard(data) {
            const game = data.game;
            const board = data.board;
            const isFlipped = gameState.myColor === 'black';
            
            // Turn Info
            const currentPlayer = data.players.find(p => p.color === game.current_player);
            const turnInfo = document.getElementById('turnInfo');
            turnInfo.className = 'turn-info' + (gameState.myTurn ? ' my-turn' : '') + (game.check_state === 'check' && gameState.myTurn ? ' check' : '');
            
            let turnText = currentPlayer ? `${game.current_player === 'white' ? '♔' : '♚'} ${escapeHtml(currentPlayer.player_name)}` : '---';
            if (game.check_state === 'check') turnText += ' ⚠️ SCHACH!';
            document.getElementById('currentPlayerName').innerHTML = turnText;
            
            // Players
            const whiteP = data.players.find(p => p.color === 'white');
            const blackP = data.players.find(p => p.color === 'black');
            document.getElementById('whitePlayer').className = 'player-row' + (game.current_player === 'white' ? ' active' : '');
            document.getElementById('blackPlayer').className = 'player-row' + (game.current_player === 'black' ? ' active' : '');
            document.querySelector('#whitePlayer .pname').textContent = whiteP ? whiteP.player_name : 'Weiß';
            document.querySelector('#blackPlayer .pname').textContent = blackP ? blackP.player_name : 'Schwarz';
            
            // Find king in check
            let kingInCheck = null;
            if (game.check_state === 'check') {
                for (const [sq, piece] of Object.entries(board)) {
                    if (piece === (game.current_player === 'white' ? 'wK' : 'bK')) {
                        kingInCheck = sq;
                        break;
                    }
                }
            }
            
            // Render board
            const boardEl = document.getElementById('board');
            boardEl.innerHTML = '';
            
            for (let r = 7; r >= 0; r--) {
                for (let c = 0; c < 8; c++) {
                    const row = isFlipped ? 7 - r : r;
                    const col = isFlipped ? 7 - c : c;
                    const sq = String.fromCharCode(97 + col) + (row + 1);
                    
                    const cell = document.createElement('div');
                    const isLight = (row + col) % 2 === 1;
                    cell.className = `cell ${isLight ? 'light' : 'dark'}`;
                    cell.dataset.sq = sq;
                    
                    // Koordinaten
                    if (col === 0) {
                        const rank = document.createElement('span');
                        rank.className = 'coord rank';
                        rank.textContent = row + 1;
                        cell.appendChild(rank);
                    }
                    if (row === 0) {
                        const file = document.createElement('span');
                        file.className = 'coord file';
                        file.textContent = String.fromCharCode(97 + col);
                        cell.appendChild(file);
                    }
                    
                    // Schach-Hervorhebung
                    if (sq === kingInCheck) cell.classList.add('check');
                    
                    // Ausgewählt
                    if (gameState.selectedCell === sq) cell.classList.add('selected');
                    
                    // Gültige Züge
                    const isValidMove = gameState.validMoves.includes(sq);
                    if (isValidMove) {
                        cell.classList.add(board[sq] ? 'capture-move' : 'valid-move');
                    }
                    
                    // Figur
                    if (board[sq]) {
                        const span = document.createElement('span');
                        span.className = `piece ${board[sq][0] === 'w' ? 'white' : 'black'}`;
                        span.textContent = PIECES[board[sq]];
                        cell.appendChild(span);
                    }
                    
                    cell.onclick = () => handleCellClick(sq);
                    boardEl.appendChild(cell);
                }
            }
        }
        
        function handleCellClick(sq) {
            if (!gameState.myTurn) return;
            
            const piece = currentBoard[sq];
            
            // Gültiger Zug?
            if (gameState.validMoves.includes(sq) && gameState.selectedCell) {
                makeMove(gameState.selectedCell, sq);
                return;
            }
            
            // Eigene Figur auswählen
            if (piece && piece[0] === (gameState.myColor === 'white' ? 'w' : 'b')) {
                gameState.selectedCell = sq;
                gameState.validMoves = calculateValidMoves(sq, piece);
                pollStatus();
            } else {
                gameState.selectedCell = null;
                gameState.validMoves = [];
                pollStatus();
            }
        }
        
        function calculateValidMoves(sq, piece) {
            // Einfache Client-Validierung (Server prüft nochmal)
            const moves = [];
            const [col, row] = [sq.charCodeAt(0) - 97, parseInt(sq[1]) - 1];
            const color = piece[0] === 'w' ? 'white' : 'black';
            const type = piece[1];
            
            const addMove = (c, r) => {
                if (c >= 0 && c <= 7 && r >= 0 && r <= 7) {
                    const target = String.fromCharCode(97 + c) + (r + 1);
                    const targetPiece = currentBoard[target];
                    if (!targetPiece || targetPiece[0] !== piece[0]) {
                        moves.push(target);
                    }
                }
            };
            
            const addSliding = (dirs) => {
                for (const [dc, dr] of dirs) {
                    let c = col + dc, r = row + dr;
                    while (c >= 0 && c <= 7 && r >= 0 && r <= 7) {
                        const target = String.fromCharCode(97 + c) + (r + 1);
                        const targetPiece = currentBoard[target];
                        if (!targetPiece) {
                            moves.push(target);
                        } else {
                            if (targetPiece[0] !== piece[0]) moves.push(target);
                            break;
                        }
                        c += dc; r += dr;
                    }
                }
            };
            
            switch (type) {
                case 'P':
                    const dir = color === 'white' ? 1 : -1;
                    const startRow = color === 'white' ? 1 : 6;
                    // Vorwärts
                    let fwd = String.fromCharCode(97 + col) + (row + dir + 1);
                    if (!currentBoard[fwd]) {
                        moves.push(fwd);
                        if (row === startRow) {
                            let fwd2 = String.fromCharCode(97 + col) + (row + 2*dir + 1);
                            if (!currentBoard[fwd2]) moves.push(fwd2);
                        }
                    }
                    // Schlagen
                    for (const dc of [-1, 1]) {
                        if (col + dc >= 0 && col + dc <= 7) {
                            const diag = String.fromCharCode(97 + col + dc) + (row + dir + 1);
                            if ((currentBoard[diag] && currentBoard[diag][0] !== piece[0]) || diag === currentEnPassant) {
                                moves.push(diag);
                            }
                        }
                    }
                    break;
                case 'N':
                    [[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]].forEach(([dc,dr]) => addMove(col+dc, row+dr));
                    break;
                case 'B':
                    addSliding([[1,1],[1,-1],[-1,1],[-1,-1]]);
                    break;
                case 'R':
                    addSliding([[0,1],[0,-1],[1,0],[-1,0]]);
                    break;
                case 'Q':
                    addSliding([[0,1],[0,-1],[1,0],[-1,0],[1,1],[1,-1],[-1,1],[-1,-1]]);
                    break;
                case 'K':
                    for (let dc = -1; dc <= 1; dc++) {
                        for (let dr = -1; dr <= 1; dr++) {
                            if (dc !== 0 || dr !== 0) addMove(col + dc, row + dr);
                        }
                    }
                    // Rochade
                    const prefix = color === 'white' ? 'w' : 'b';
                    const homeRow = color === 'white' ? 0 : 7;
                    if (row === homeRow && col === 4) {
                        if (currentCastling[prefix + 'K']) {
                            const f = String.fromCharCode(102) + (homeRow + 1);
                            const g = String.fromCharCode(103) + (homeRow + 1);
                            if (!currentBoard[f] && !currentBoard[g]) moves.push(g);
                        }
                        if (currentCastling[prefix + 'Q']) {
                            const d = String.fromCharCode(100) + (homeRow + 1);
                            const c = String.fromCharCode(99) + (homeRow + 1);
                            const b = String.fromCharCode(98) + (homeRow + 1);
                            if (!currentBoard[d] && !currentBoard[c] && !currentBoard[b]) moves.push(c);
                        }
                    }
                    break;
            }
            
            return moves;
        }
        
        async function makeMove(from, to) {
            const res = await fetch(`${API_URL}?action=move`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId, from, to })
            });
            const data = await res.json();
            
            gameState.selectedCell = null;
            gameState.validMoves = [];
            
            if (data.success) {
                if (data.need_promotion) {
                    // Modal wird bei nächstem Poll gezeigt
                }
                if (data.check) showToast('Schach!', 'info');
                if (data.checkmate) showToast('🎉 Schachmatt!', 'success');
            } else {
                showToast(data.error, 'error');
            }
        }
        
        function showPromotionModal() {
            const modal = document.getElementById('promoModal');
            const choices = document.getElementById('promoChoices');
            const prefix = gameState.myColor === 'white' ? 'w' : 'b';
            
            choices.innerHTML = ['Q', 'R', 'B', 'N'].map(p => 
                `<button class="promo-btn" onclick="promoteTo('${p}')">${PIECES[prefix + p]}</button>`
            ).join('');
            
            modal.classList.add('active');
        }
        
        async function promoteTo(piece) {
            document.getElementById('promoModal').classList.remove('active');
            
            const res = await fetch(`${API_URL}?action=promote`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId, piece })
            });
            const data = await res.json();
            
            if (!data.success) showToast(data.error, 'error');
        }
        
        async function resignGame() {
            if (!confirm('Wirklich aufgeben?')) return;
            
            const res = await fetch(`${API_URL}?action=resign`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId })
            });
        }
        
        function showResult(game, players) {
            showScreen('result');
            
            const winner = players.find(p => p.color === game.winner);
            const icon = document.getElementById('resultIcon');
            const text = document.getElementById('winnerText');
            const reason = document.getElementById('winReason');
            
            if (game.win_reason === 'stalemate') {
                icon.textContent = '🤝';
                text.textContent = 'Patt - Unentschieden!';
                reason.textContent = 'Keine legalen Züge mehr möglich';
            } else if (winner) {
                icon.textContent = '🏆';
                text.textContent = `${game.winner === 'white' ? '♔' : '♚'} ${winner.player_name} gewinnt!`;
                
                const reasons = {
                    checkmate: 'Schachmatt',
                    resignation: 'Gegner hat aufgegeben',
                    abandonment: 'Gegner hat verlassen'
                };
                reason.textContent = reasons[game.win_reason] || '';
            }
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
