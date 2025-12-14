<?php
/**
 * ============================================================================
 * sgiT Education - Dame v1.0
 * ============================================================================
 * 
 * Klassisches Brettspiel für 2 Spieler
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
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚫ Dame - sgiT Education</title>
    <!-- Zentrale Multiplayer CSS -->
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <style>
        /* ===========================================
           Dame-Spezifische Styles
           =========================================== */
        :root {
            --light-square: #d4a76a;
            --dark-square: #8b5a2b;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: var(--mp-bg-card);
            border-radius: 12px;
            margin-bottom: 20px;
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
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn.secondary { background: var(--card-bg); color: var(--text); border: 2px solid var(--accent); }
        
        .divider { display: flex; align-items: center; margin: 20px 0; color: var(--text-muted); }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--text-muted); opacity: 0.3; }
        .divider span { padding: 0 15px; }
        
        .game-code-display { background: var(--card-bg); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        .game-code { font-size: 2.5rem; font-weight: bold; color: var(--accent); letter-spacing: 8px; font-family: monospace; }
        
        .players-waiting { display: flex; gap: 20px; justify-content: center; margin: 20px 0; }
        .player-slot { background: var(--bg); border: 2px dashed var(--text-muted); border-radius: 12px; padding: 20px; text-align: center; min-width: 150px; }
        .player-slot.filled { border-style: solid; border-color: var(--accent); }
        .player-slot .color-icon { font-size: 2rem; margin-bottom: 5px; }
        
        /* Game */
        .game-container { display: grid; grid-template-columns: 1fr 250px; gap: 20px; }
        @media (max-width: 800px) { .game-container { grid-template-columns: 1fr; } }
        
        .board-area { background: var(--card-bg); border-radius: 16px; padding: 20px; display: flex; justify-content: center; }
        
        .board {
            display: grid;
            grid-template-columns: repeat(8, 50px);
            grid-template-rows: repeat(8, 50px);
            border: 4px solid #5d3a1a;
            border-radius: 4px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        }
        
        .cell {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
        }
        .cell.light { background: var(--light-square); }
        .cell.dark { background: var(--dark-square); }
        .cell.selected { box-shadow: inset 0 0 0 3px var(--mp-accent); }
        .cell.valid-move { animation: mp-fieldPulse 1s ease infinite; }
        .cell.valid-move::after {
            content: '';
            width: 20px;
            height: 20px;
            background: var(--mp-accent-glow);
            border-radius: 50%;
            position: absolute;
        }
        .cell.capture-move::after {
            background: rgba(231, 76, 60, 0.6);
            width: 30px;
            height: 30px;
        }
        
        .piece {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            position: relative;
            transition: var(--mp-transition);
            box-shadow: 0 4px 8px rgba(0,0,0,0.4);
        }
        .piece:hover { transform: scale(1.1); }
        .piece.moving { animation: mp-pieceMove 0.4s ease; }
        .piece.captured { animation: mp-pieceCapture 0.5s ease forwards; }
        .piece.black { background: linear-gradient(135deg, #2c2c2c, #1a1a1a); border: 3px solid #444; }
        .piece.white { background: linear-gradient(135deg, #f5f5f5, #d0d0d0); border: 3px solid #999; }
        .piece.king::after {
            content: '👑';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.2rem;
        }
        .piece.selectable { animation: pulse 0.8s infinite; }
        @keyframes pulse { 50% { transform: scale(1.1); } }
        .piece.must-move { box-shadow: 0 0 15px 5px rgba(231, 76, 60, 0.8); }
        
        /* Sidebar */
        .sidebar { display: flex; flex-direction: column; gap: 15px; }
        .info-card { background: var(--card-bg); border-radius: 12px; padding: 15px; }
        .info-card h3 { color: var(--accent); margin-bottom: 10px; font-size: 1rem; }
        
        .turn-info { text-align: center; padding: 15px; background: var(--bg); border-radius: 10px; }
        .turn-info.my-turn { border: 2px solid var(--accent); }
        .turn-info .label { font-size: 0.85rem; color: var(--text-muted); }
        .turn-info .name { font-size: 1.2rem; font-weight: bold; margin-top: 5px; }
        
        .score-row { display: flex; align-items: center; justify-content: space-between; padding: 10px; background: var(--bg); border-radius: 8px; margin-bottom: 8px; }
        .score-row.active { border: 2px solid var(--accent); }
        
        .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 15px 25px; border-radius: 12px; font-weight: 600; z-index: 1000; }
        .toast.success { background: var(--accent); color: var(--primary); }
        .toast.error { background: #e74c3c; color: white; }
        .toast.info { background: var(--card-bg); border: 2px solid var(--accent); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="multiplayer.php" class="back-link">← Multiplayer</a>
                <h1>⚫ <span>Dame</span></h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>
        
        <!-- LOBBY -->
        <div id="lobbyScreen" class="screen active">
            <div class="lobby-container">
                <div style="font-size: 4rem; margin-bottom: 10px;">⚫</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Dame</h1>
                <p style="color: var(--text-muted); margin-bottom: 25px;">Das klassische Brettspiel für 2 Spieler</p>
                
                <div class="lobby-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2>👤 Dein Name</h2>
                    <div class="input-group">
                        <input type="text" id="playerNameInput" placeholder="Name..." maxlength="20">
                    </div>
                    <button class="btn" onclick="setPlayerName()">Weiter →</button>
                </div>
                
                <div class="lobby-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🎮 Neues Spiel</h2>
                    <p style="color: var(--text-muted); margin-bottom: 15px;">Du spielst mit ⚫ Schwarz (beginnst)</p>
                    <button class="btn" onclick="createGame()">Spiel erstellen</button>
                </div>
                
                <div class="divider"><span>oder</span></div>
                
                <div class="lobby-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🔗 Beitreten</h2>
                    <p style="color: var(--text-muted); margin-bottom: 15px;">Du spielst mit ⚪ Weiß</p>
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
                        <div class="player-slot"><div class="color-icon">⚫</div>Wartet...</div>
                        <div class="player-slot"><div class="color-icon">⚪</div>Wartet...</div>
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
                        <h3>📊 Steine</h3>
                        <div class="score-row" id="blackScore">
                            <span>⚫ Schwarz</span>
                            <span id="blackCount">12</span>
                        </div>
                        <div class="score-row" id="whiteScore">
                            <span>⚪ Weiß</span>
                            <span id="whiteCount">12</span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3>📜 Regeln</h3>
                        <ul style="font-size: 0.85rem; color: var(--text-muted); list-style: none;">
                            <li>↗️ Diagonal vorwärts ziehen</li>
                            <li>💥 Über Gegner springen = schlagen</li>
                            <li>⚠️ Schlagzwang!</li>
                            <li>👑 Am Ende = Dame (kann rückwärts)</li>
                        </ul>
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
                    <button class="btn" onclick="location.reload()">🔄 Neues Spiel</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='multiplayer.php'">← Zurück</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const API_URL = '/api/dame.php';
        const POLL_INTERVAL = 800;
        
        let gameState = {
            gameId: null,
            playerId: null,
            gameCode: null,
            isHost: false,
            myColor: null,
            myTurn: false,
            status: 'lobby',
            selectedCell: null,
            validMoves: []
        };
        
        let playerName = '<?php echo addslashes($userName); ?>';
        let playerAvatar = '<?php echo addslashes($userAvatar); ?>';
        let walletChildId = <?php echo $walletChildId ?: 'null'; ?>;
        let pollInterval = null;
        let currentBoard = {};
        
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
            
            if (game.status === 'waiting') {
                updateWaitingRoom(data.players);
                document.getElementById('startBtn').disabled = data.players.length < 2;
            }
            else if (game.status === 'playing') {
                if (prevStatus !== 'playing') showScreen('game');
                renderBoard(data);
            }
            else if (game.status === 'finished') {
                showScreen('result');
                const winnerPlayer = data.players.find(p => p.color === game.winner);
                document.getElementById('winnerText').textContent = winnerPlayer 
                    ? `${game.winner === 'black' ? '⚫' : '⚪'} ${winnerPlayer.player_name} gewinnt!` 
                    : 'Spiel beendet!';
                stopPolling();
            }
        }
        
        function updateWaitingRoom(players) {
            const black = players.find(p => p.color === 'black');
            const white = players.find(p => p.color === 'white');
            
            document.getElementById('playersWaiting').innerHTML = `
                <div class="player-slot ${black ? 'filled' : ''}">
                    <div class="color-icon">⚫</div>
                    ${black ? `${black.avatar} ${escapeHtml(black.player_name)}` : 'Wartet...'}
                </div>
                <div class="player-slot ${white ? 'filled' : ''}">
                    <div class="color-icon">⚪</div>
                    ${white ? `${white.avatar} ${escapeHtml(white.player_name)}` : 'Wartet...'}
                </div>
            `;
        }
        
        function renderBoard(data) {
            const game = data.game;
            const board = data.board;
            
            // Turn Info
            const currentPlayer = data.players.find(p => p.color === game.current_player);
            const turnInfo = document.getElementById('turnInfo');
            turnInfo.className = 'turn-info' + (gameState.myTurn ? ' my-turn' : '');
            document.getElementById('currentPlayerName').innerHTML = currentPlayer 
                ? `${game.current_player === 'black' ? '⚫' : '⚪'} ${escapeHtml(currentPlayer.player_name)}`
                : '---';
            
            // Scores
            document.getElementById('blackCount').textContent = data.piece_count.black;
            document.getElementById('whiteCount').textContent = data.piece_count.white;
            document.getElementById('blackScore').className = 'score-row' + (game.current_player === 'black' ? ' active' : '');
            document.getElementById('whiteScore').className = 'score-row' + (game.current_player === 'white' ? ' active' : '');
            
            // Board rendern
            const boardEl = document.getElementById('board');
            boardEl.innerHTML = '';
            
            for (let row = 0; row < 8; row++) {
                for (let col = 0; col < 8; col++) {
                    const cell = document.createElement('div');
                    const isLight = (row + col) % 2 === 0;
                    cell.className = `cell ${isLight ? 'light' : 'dark'}`;
                    cell.dataset.pos = `${row},${col}`;
                    
                    // Ausgewählt?
                    if (gameState.selectedCell === `${row},${col}`) {
                        cell.classList.add('selected');
                    }
                    
                    // Gültiger Zug?
                    const validMove = gameState.validMoves.find(m => m.to === `${row},${col}`);
                    if (validMove) {
                        cell.classList.add(validMove.capture ? 'capture-move' : 'valid-move');
                    }
                    
                    // Figur?
                    const pos = `${row},${col}`;
                    if (board[pos]) {
                        const piece = document.createElement('div');
                        piece.className = `piece ${board[pos].color}`;
                        if (board[pos].king) piece.classList.add('king');
                        
                        // Muss mit diesem Stein ziehen?
                        if (game.must_capture_from === pos) {
                            piece.classList.add('must-move');
                        }
                        // Klickbar wenn mein Stein und mein Zug
                        else if (gameState.myTurn && board[pos].color === gameState.myColor) {
                            piece.classList.add('selectable');
                        }
                        
                        cell.appendChild(piece);
                    }
                    
                    cell.onclick = () => handleCellClick(row, col);
                    boardEl.appendChild(cell);
                }
            }
        }
        
        function handleCellClick(row, col) {
            if (!gameState.myTurn) return;
            
            const pos = `${row},${col}`;
            const piece = currentBoard[pos];
            
            // Auf gültigen Zug geklickt?
            const validMove = gameState.validMoves.find(m => m.to === pos);
            if (validMove && gameState.selectedCell) {
                makeMove(gameState.selectedCell, pos);
                return;
            }
            
            // Eigener Stein ausgewählt?
            if (piece && piece.color === gameState.myColor) {
                gameState.selectedCell = pos;
                gameState.validMoves = calculateValidMoves(pos, piece);
                pollStatus(); // Re-render
            } else {
                gameState.selectedCell = null;
                gameState.validMoves = [];
                pollStatus();
            }
        }
        
        function calculateValidMoves(pos, piece) {
            const [row, col] = pos.split(',').map(Number);
            const moves = [];
            const direction = piece.color === 'black' ? 1 : -1;
            const directions = piece.king ? [[-1,-1],[-1,1],[1,-1],[1,1]] : [[direction,-1],[direction,1]];
            
            // Erst Schlagzüge prüfen
            const captures = [];
            const allDirs = [[-1,-1],[-1,1],[1,-1],[1,1]]; // Alle Richtungen für Schlagen
            
            for (const [dr, dc] of allDirs) {
                const midRow = row + dr;
                const midCol = col + dc;
                const toRow = row + 2*dr;
                const toCol = col + 2*dc;
                
                if (toRow >= 0 && toRow <= 7 && toCol >= 0 && toCol <= 7) {
                    const midPos = `${midRow},${midCol}`;
                    const toPos = `${toRow},${toCol}`;
                    
                    if (currentBoard[midPos] && currentBoard[midPos].color !== piece.color && !currentBoard[toPos]) {
                        captures.push({ to: toPos, capture: true });
                    }
                }
            }
            
            if (captures.length > 0) return captures;
            
            // Normale Züge
            for (const [dr, dc] of directions) {
                const toRow = row + dr;
                const toCol = col + dc;
                
                if (toRow >= 0 && toRow <= 7 && toCol >= 0 && toCol <= 7) {
                    const toPos = `${toRow},${toCol}`;
                    if (!currentBoard[toPos]) {
                        moves.push({ to: toPos, capture: false });
                    }
                }
            }
            
            return moves;
        }
        
        async function makeMove(from, to) {
            const res = await fetch(`${API_URL}?action=move`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ 
                    game_id: gameState.gameId, 
                    player_id: gameState.playerId,
                    from: from,
                    to: to
                })
            });
            const data = await res.json();
            
            gameState.selectedCell = null;
            gameState.validMoves = [];
            
            if (data.success) {
                if (data.captured) showToast('Geschlagen!', 'success');
                if (data.must_continue) showToast('Weiterschlagen!', 'info');
                if (data.winner) showToast('🎉 GEWONNEN!', 'success');
            } else {
                showToast(data.error, 'error');
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
        
        // Enter-Listener
        document.getElementById('playerNameInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') setPlayerName(); });
        document.getElementById('gameCodeInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') joinGame(); });
    </script>
</body>
</html>
