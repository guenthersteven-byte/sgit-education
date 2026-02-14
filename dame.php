<?php
/**
 * ============================================================================
 * sgiT Education - Dame v1.1
 * ============================================================================
 *
 * Klassisches Brettspiel fuer 2 Spieler
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.1
 * ============================================================================
 */

require_once __DIR__ . '/includes/game_header.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚫ Dame - sgiT Education</title>
    <!-- Zentrale Multiplayer CSS -->
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <script src="/assets/js/dame-pieces.js"></script>
    <style>
        /* Dame-Spezifische Styles (Shared Styles via multiplayer-theme.css) */
        :root {
            --light-square: #d4c8a0;
            --dark-square: #2a5a0a;
        }

        /* Game Layout */
        .game-container { display: grid; grid-template-columns: 1fr 250px; gap: 20px; }
        @media (max-width: 800px) { .game-container { grid-template-columns: 1fr; } }

        .board-area { background: var(--mp-bg-card); border-radius: 16px; padding: 20px; display: flex; justify-content: center; }

        .board {
            display: grid;
            grid-template-columns: repeat(8, 50px);
            grid-template-rows: repeat(8, 50px);
            border: 4px solid #1a3503;
            border-radius: 4px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        }

        .cell {
            width: 50px; height: 50px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; position: relative;
        }
        .cell.light { background: var(--light-square); }
        .cell.dark { background: var(--dark-square); }
        .cell.selected { box-shadow: inset 0 0 0 3px var(--mp-accent); }
        .cell.valid-move { animation: mp-fieldPulse 1s ease infinite; }
        .cell.valid-move::after {
            content: ''; width: 20px; height: 20px;
            background: var(--mp-accent-glow); border-radius: 50%; position: absolute;
        }
        .cell.capture-move::after { background: rgba(231, 76, 60, 0.6); width: 30px; height: 30px; }

        .piece {
            width: 40px; height: 40px; border-radius: 50%; cursor: pointer;
            position: relative; transition: var(--mp-transition);
            box-shadow: 0 4px 8px rgba(0,0,0,0.4);
        }
        .piece:hover { transform: scale(1.1); }
        .piece.moving { animation: mp-pieceMove 0.4s ease; }
        .piece.captured { animation: mp-pieceCapture 0.5s ease forwards; }
        .piece.black { background: linear-gradient(135deg, #2c2c2c, #1a1a1a); border: 3px solid #444; }
        .piece.white { background: linear-gradient(135deg, #f5f5f5, #d0d0d0); border: 3px solid #999; }
        .piece.king::after {
            content: '\1F451'; position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); font-size: 1.2rem;
        }
        .piece.selectable { animation: pulse 0.8s infinite; }
        @keyframes pulse { 50% { transform: scale(1.1); } }
        .piece.must-move { box-shadow: 0 0 15px 5px rgba(231, 76, 60, 0.8); }

        /* Mobile Board */
        @media (max-width: 500px) {
            .board-area { padding: 10px; }
            .board { grid-template-columns: repeat(8, 38px); grid-template-rows: repeat(8, 38px); }
            .cell { width: 38px; height: 38px; }
            .piece { width: 30px; height: 30px; }
        }
        @media (max-width: 380px) {
            .board { grid-template-columns: repeat(8, 32px); grid-template-rows: repeat(8, 32px); }
            .cell { width: 32px; height: 32px; }
            .piece { width: 26px; height: 26px; }
        }
    </style>
</head>
<body class="mp-game-body">
    <div class="mp-game-container">
        <div class="mp-game-header">
            <div>
                <a href="multiplayer.php" class="mp-game-header__back">&larr; Spiele-Hub</a>
                <h1>&#x26AB; <span>Dame</span></h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>

        <!-- LOBBY -->
        <div id="lobbyScreen" class="mp-game-screen active">
            <div class="mp-game-lobby">
                <div style="font-size: 4rem; margin-bottom: 10px;">&#x26AB;</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Dame</h1>
                <p style="color: var(--mp-text-muted); margin-bottom: 25px;">Das klassische Brettspiel f&uuml;r 2 Spieler</p>

                <div class="mp-lobby-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2>&#x1F464; Dein Name</h2>
                    <div class="mp-lobby-input-group">
                        <input type="text" id="playerNameInput" placeholder="Name..." maxlength="20">
                    </div>
                    <button class="mp-game-btn" onclick="setPlayerName()">Weiter &rarr;</button>
                </div>

                <div class="mp-lobby-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>&#x1F3AE; Neues Spiel</h2>
                    <p style="color: var(--mp-text-muted); margin-bottom: 15px;">Du spielst mit &#x26AB; Schwarz (beginnst)</p>
                    <button class="mp-game-btn" onclick="createGame()">&#x1F465; Gegen Mitspieler</button>
                    <button class="mp-game-btn mp-game-btn--secondary" style="margin-top: 10px;" onclick="location.href='dame_vs_computer.php'">&#x1F916; Gegen Computer</button>
                </div>

                <div class="mp-game-divider"><span>oder</span></div>

                <div class="mp-lobby-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>&#x1F517; Beitreten</h2>
                    <p style="color: var(--mp-text-muted); margin-bottom: 15px;">Du spielst mit &#x26AA; Wei&szlig;</p>
                    <div class="mp-lobby-input-group">
                        <input type="text" id="gameCodeInput" class="mp-lobby-code-input" placeholder="CODE" maxlength="6">
                    </div>
                    <button class="mp-game-btn mp-game-btn--secondary" onclick="joinGame()">Beitreten &rarr;</button>
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
                    <h2>&#x1F465; Spieler</h2>
                    <div class="mp-lobby-players" id="playersWaiting">
                        <div class="mp-lobby-player-slot"><div class="color-icon">&#x26AB;</div>Wartet...</div>
                        <div class="mp-lobby-player-slot"><div class="color-icon">&#x26AA;</div>Wartet...</div>
                    </div>
                </div>

                <div id="hostControls" style="display: none;">
                    <button class="mp-game-btn" onclick="startGame()" id="startBtn" disabled>&#x25B6;&#xFE0F; Spiel starten</button>
                </div>
                <p id="waitingMsg" style="color: var(--mp-text-muted); display: none;">&#x23F3; Warte auf Host...</p>
                <button class="mp-game-btn mp-game-btn--secondary" style="margin-top: 15px;" onclick="leaveGame()">&#x1F6AA; Verlassen</button>
            </div>
        </div>
        
        <!-- GAME -->
        <div id="gameScreen" class="mp-game-screen">
            <div class="game-container">
                <div class="board-area">
                    <div class="board" id="board"></div>
                </div>

                <div class="mp-game-sidebar">
                    <div class="mp-info-card">
                        <div class="mp-turn-info" id="turnInfo">
                            <div class="label">Am Zug:</div>
                            <div class="name" id="currentPlayerName">---</div>
                        </div>
                    </div>
                    
                    <div class="mp-info-card">
                        <h3>&#x1F4CA; Steine</h3>
                        <div class="mp-score-row" id="blackScore">
                            <span>&#x26AB; Schwarz</span>
                            <span id="blackCount">12</span>
                        </div>
                        <div class="mp-score-row" id="whiteScore">
                            <span>&#x26AA; Wei&szlig;</span>
                            <span id="whiteCount">12</span>
                        </div>
                    </div>

                    <div class="mp-info-card">
                        <h3>&#x1F4DC; Regeln</h3>
                        <ul style="font-size: 0.85rem; color: var(--mp-text-muted); list-style: none;">
                            <li>&#x2197;&#xFE0F; Diagonal vorw&auml;rts ziehen</li>
                            <li>&#x1F4A5; &Uuml;ber Gegner springen = schlagen</li>
                            <li>&#x26A0;&#xFE0F; Schlagzwang!</li>
                            <li>&#x1F451; Am Ende = Dame (kann r&uuml;ckw&auml;rts)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESULT -->
        <div id="resultScreen" class="mp-game-screen">
            <div class="mp-game-lobby">
                <div class="mp-lobby-card" style="border: 3px solid var(--mp-accent);">
                    <div style="font-size: 5rem;">&#x1F3C6;</div>
                    <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                    <button class="mp-game-btn" onclick="location.reload()">&#x1F504; Neues Spiel</button>
                    <button class="mp-game-btn mp-game-btn--secondary" style="margin-top: 10px;" onclick="location.href='multiplayer.php'">&larr; Spiele-Hub</button>
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
                <div class="mp-lobby-player-slot ${black ? 'filled' : ''}">
                    <div class="color-icon">\u26AB</div>
                    ${black ? `${black.avatar} ${escapeHtml(black.player_name)}` : 'Wartet...'}
                </div>
                <div class="mp-lobby-player-slot ${white ? 'filled' : ''}">
                    <div class="color-icon">\u26AA</div>
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
            turnInfo.className = 'mp-turn-info' + (gameState.myTurn ? ' my-turn' : '');
            document.getElementById('currentPlayerName').innerHTML = currentPlayer 
                ? `${game.current_player === 'black' ? '⚫' : '⚪'} ${escapeHtml(currentPlayer.player_name)}`
                : '---';
            
            // Scores
            document.getElementById('blackCount').textContent = data.piece_count.black;
            document.getElementById('whiteCount').textContent = data.piece_count.white;
            document.getElementById('blackScore').className = 'mp-score-row' + (game.current_player === 'black' ? ' active' : '');
            document.getElementById('whiteScore').className = 'mp-score-row' + (game.current_player === 'white' ? ' active' : '');
            
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
                        const piece = board[pos];
                        if (typeof DAME_PIECE_SVGS !== 'undefined') {
                            const img = document.createElement('img');
                            img.style.cssText = 'width:40px;height:40px;pointer-events:none;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.5));transition:all 0.2s;';
                            img.draggable = false;
                            if (piece.color === 'black') {
                                img.src = piece.king ? DAME_PIECE_SVGS.blackKing : DAME_PIECE_SVGS.black;
                            } else {
                                img.src = piece.king ? DAME_PIECE_SVGS.greenKing : DAME_PIECE_SVGS.green;
                            }
                            if (game.must_capture_from === pos) {
                                cell.style.boxShadow = '0 0 15px 5px rgba(231,76,60,0.8)';
                            } else if (gameState.myTurn && piece.color === gameState.myColor) {
                                img.style.animation = 'pulse 0.8s infinite';
                            }
                            cell.appendChild(img);
                        } else {
                            const pieceEl = document.createElement('div');
                            pieceEl.className = `piece ${piece.color}`;
                            if (piece.king) pieceEl.classList.add('king');
                            if (game.must_capture_from === pos) pieceEl.classList.add('must-move');
                            else if (gameState.myTurn && piece.color === gameState.myColor) pieceEl.classList.add('selectable');
                            cell.appendChild(pieceEl);
                        }
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
            toast.className = 'mp-game-toast show ' + type;
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
