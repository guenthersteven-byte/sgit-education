<?php
/**
 * sgiT Education - Montagsmaler v1.1
 * @version 1.1
 */
require_once __DIR__ . '/includes/game_header.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎨 Montagsmaler - sgiT Education</title>
    <!-- Zentrale Multiplayer CSS -->
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <style>
        /* ===========================================
           Montagsmaler-Spezifische Styles
           =========================================== */

        /* Lobby Screen (lokale Erweiterungen) */
        .lobby-title {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .lobby-subtitle {
            color: var(--mp-text-muted);
            margin-bottom: 30px;
        }

        /* Waiting Room */
        .waiting-container {
            max-width: 600px;
            margin: 30px auto;
            text-align: center;
        }
        .players-list {
            background: var(--mp-bg-card);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .players-list h3 {
            margin-bottom: 15px;
            color: var(--mp-accent);
        }
        .player-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background: var(--mp-bg-medium);
            border-radius: 10px;
            margin-bottom: 8px;
        }
        .player-avatar { font-size: 1.8rem; }
        .player-name { flex: 1; text-align: left; }
        .player-host {
            background: var(--mp-accent);
            color: var(--mp-primary);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Game Screen */
        .game-container {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            height: calc(100vh - 150px);
        }
        @media (max-width: 900px) {
            .game-container {
                grid-template-columns: 1fr;
                height: auto;
            }
        }
        
        .canvas-area {
            background: var(--mp-bg-card);
            border-radius: 16px;
            padding: 15px;
            display: flex;
            flex-direction: column;
        }
        .canvas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .round-info {
            font-size: 0.9rem;
            color: var(--mp-text-muted);
        }
        .round-info strong { color: var(--mp-accent); }
        .timer {
            background: var(--mp-bg-medium);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.2rem;
            transition: var(--mp-transition);
        }
        .timer.warning { color: var(--mp-warning); }
        .timer.danger { color: var(--mp-error); animation: mp-timerWarning 0.5s infinite; }
        
        .word-display {
            text-align: center;
            padding: 15px;
            background: var(--mp-bg-medium);
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .word-display.drawer {
            background: linear-gradient(135deg, var(--mp-primary), var(--mp-bg-card));
            border: 2px solid var(--mp-accent);
        }
        .word-label { color: var(--mp-text-muted); font-size: 0.85rem; margin-bottom: 5px; }
        .word-text { font-size: 1.8rem; font-weight: bold; color: var(--mp-accent); animation: mp-wordReveal 0.5s ease-out; }
        .word-hint {
            font-size: 1.5rem;
            letter-spacing: 5px;
            font-family: monospace;
        }
        
        .canvas-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            min-height: 400px;
        }
        #drawCanvas {
            background: #fff;
            cursor: crosshair;
            touch-action: none;
        }
        
        .drawing-tools {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            flex-wrap: wrap;
            justify-content: center;
        }
        .tool-btn {
            width: 40px;
            height: 40px;
            border: 2px solid transparent;
            border-radius: 10px;
            background: var(--mp-bg-medium);
            color: var(--mp-text);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tool-btn:hover { border-color: var(--mp-accent); }
        .tool-btn.active { background: var(--mp-accent); color: var(--mp-primary); }
        .color-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 3px solid transparent;
            cursor: pointer;
        }
        .color-btn.active { border-color: var(--mp-text); transform: scale(1.2); }
        .size-slider {
            width: 100px;
            accent-color: var(--mp-accent);
        }
        
        /* Chat/Guess Area */
        .chat-area {
            background: var(--mp-bg-card);
            border-radius: 16px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 150px);
        }
        .chat-header {
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 10px;
        }
        .chat-header h3 { font-size: 1rem; color: var(--mp-accent); }
        
        .scoreboard {
            background: var(--mp-bg-medium);
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 15px;
            max-height: 150px;
            overflow-y: auto;
        }
        .score-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px;
            font-size: 0.85rem;
        }
        .score-item.drawing { background: rgba(67, 210, 64, 0.2); border-radius: 6px; }
        .score-points {
            margin-left: auto;
            color: var(--mp-accent);
            font-weight: 600;
        }
        
        .guess-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            background: var(--mp-bg-medium);
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .guess-item {
            padding: 8px 12px;
            margin-bottom: 6px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            font-size: 0.9rem;
            animation: mp-fadeIn 0.3s ease-out;
        }
        .guess-item.correct {
            background: var(--mp-success-bg);
            border: 1px solid var(--mp-accent);
            animation: mp-correctFlash 0.5s ease, mp-fadeIn 0.3s ease-out;
        }
        .guess-item .player { color: var(--mp-accent); font-weight: 600; }
        .guess-item .text { color: var(--mp-text); }
        .guess-item .points { color: var(--mp-warning); font-size: 0.8rem; animation: mp-scorePop 0.4s ease; }
        
        .guess-input-area {
            display: flex;
            gap: 10px;
        }
        .guess-input-area input {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--mp-bg-medium);
            color: var(--mp-text);
            font-size: 1rem;
        }
        .guess-input-area input:focus { outline: 2px solid var(--mp-accent); }
        .guess-input-area button {
            padding: 12px 20px;
            background: var(--mp-accent);
            color: var(--mp-primary);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Result Screen */
        .result-container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
        }
        .result-card {
            background: var(--mp-bg-card);
            border-radius: 20px;
            padding: 30px;
            border: 3px solid var(--mp-accent);
        }
        .result-icon { font-size: 5rem; margin-bottom: 15px; }
        .result-title { font-size: 2rem; margin-bottom: 20px; }
        .final-scores {
            background: var(--mp-bg-medium);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .final-score-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            margin-bottom: 8px;
            background: var(--mp-bg-card);
            border-radius: 10px;
        }
        .final-score-item.winner {
            background: linear-gradient(135deg, var(--mp-primary), var(--mp-accent));
            border: 2px solid var(--mp-accent);
        }
        .rank { font-size: 1.5rem; width: 40px; }
        .final-avatar { font-size: 2rem; }
        .final-name { flex: 1; text-align: left; font-weight: 600; }
        .final-points { font-size: 1.3rem; color: var(--mp-accent); font-weight: bold; }
        
        
        /* Mobile Optimierung */
        @media (max-width: 800px) {
            .game-layout { grid-template-columns: 1fr; }
            .sidebar { order: 2; }
        }
        
        @media (max-width: 500px) {
            .canvas-container { padding: 10px; }
            #drawCanvas { 
                max-width: 100%; 
                height: auto;
                touch-action: none;
            }
            .toolbar { 
                flex-wrap: wrap; 
                gap: 8px;
                padding: 10px;
            }
            .toolbar button, .color-btn {
                min-width: 40px;
                min-height: 40px;
            }
            .brush-size { width: 80px; }
            .guess-input { padding: 12px; font-size: 16px; }
            .timer { font-size: 2rem; }
            .word-display { font-size: 1.5rem; letter-spacing: 6px; }
            .players-list { gap: 8px; }
            .player-card { padding: 10px; min-width: 100px; }
        }
        
        /* Touch-Optimierung für Canvas */
        @media (pointer: coarse) {
            #drawCanvas {
                touch-action: none;
            }
            .color-btn {
                width: 36px;
                height: 36px;
            }
        }
    </style>
</head>
<body class="mp-game-body">
    <div class="mp-game-container">
        <!-- Header -->
        <div class="mp-game-header">
            <div>
                <a href="multiplayer.php" class="mp-game-header__back">← Spiele-Hub</a>
                <h1>🎨 <span>Montagsmaler</span></h1>
            </div>
            <div class="header-info">
                <span id="headerRound"></span>
                <span id="headerUser"><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
            </div>
        </div>
        
        <!-- ==================== LOBBY SCREEN ==================== -->
        <div id="lobbyScreen" class="mp-game-screen active">
            <div class="mp-game-lobby">
                <div class="lobby-title">🎨</div>
                <h1 style="font-size: 2rem; margin-bottom: 5px;">Montagsmaler</h1>
                <p class="lobby-subtitle">Zeichne & Rate mit Freunden!</p>

                <!-- Name eingeben -->
                <div class="mp-lobby-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2>👤 Wie heißt du?</h2>
                    <div class="mp-lobby-input-group">
                        <input type="text" id="playerNameInput" placeholder="Dein Name..." maxlength="20">
                    </div>
                    <button class="mp-game-btn" onclick="setPlayerName()">Weiter →</button>
                </div>

                <!-- Spiel erstellen -->
                <div class="mp-lobby-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🎮 Neues Spiel erstellen</h2>
                    <div class="mp-lobby-input-group">
                        <label>Anzahl Runden</label>
                        <select id="roundsSelect">
                            <option value="3">3 Runden (Schnell)</option>
                            <option value="5" selected>5 Runden (Normal)</option>
                            <option value="10">10 Runden (Lang)</option>
                        </select>
                    </div>
                    <div class="mp-lobby-input-group">
                        <label>Zeit pro Runde</label>
                        <select id="timeSelect">
                            <option value="45">45 Sekunden</option>
                            <option value="60" selected>60 Sekunden</option>
                            <option value="90">90 Sekunden</option>
                        </select>
                    </div>
                    <button class="mp-game-btn" onclick="createGame()">🎨 Spiel erstellen</button>
                </div>

                <div class="mp-game-divider"><span>oder</span></div>

                <!-- Spiel beitreten -->
                <div class="mp-lobby-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🔗 Spiel beitreten</h2>
                    <div class="mp-lobby-input-group">
                        <label>Spiel-Code eingeben</label>
                        <input type="text" id="gameCodeInput" class="mp-lobby-code-input" placeholder="ABC123" maxlength="6">
                    </div>
                    <button class="mp-game-btn mp-game-btn--secondary" onclick="joinGame()">Beitreten →</button>
                </div>
            </div>
        </div>
        
        <!-- ==================== WAITING ROOM ==================== -->
        <div id="waitingScreen" class="mp-game-screen">
            <div class="waiting-container">
                <div class="mp-lobby-code-display">
                    <h2>🎫 Spiel-Code</h2>
                    <div class="mp-lobby-code" id="displayGameCode">------</div>
                    <p style="color: var(--mp-text-muted); margin-top: 10px; font-size: 0.9rem;">
                        Teile diesen Code mit deinen Freunden!
                    </p>
                </div>

                <div class="players-list">
                    <h3>👥 Spieler (<span id="playerCount">0</span>)</h3>
                    <div id="playersList"></div>
                </div>

                <div id="hostControls" style="display: none;">
                    <button class="mp-game-btn" onclick="startGame()" id="startBtn" disabled>
                        ▶️ Spiel starten (min. 2 Spieler)
                    </button>
                </div>

                <div id="guestMessage" style="display: none;">
                    <p style="color: var(--mp-text-muted);">⏳ Warte auf den Host...</p>
                </div>

                <button class="mp-game-btn mp-game-btn--secondary" style="margin-top: 15px;" onclick="leaveGame()">
                    🚪 Spiel verlassen
                </button>
            </div>
        </div>
        
        <!-- ==================== GAME SCREEN ==================== -->
        <div id="gameScreen" class="mp-game-screen">
            <div class="game-container">
                <!-- Canvas Bereich -->
                <div class="canvas-area">
                    <div class="canvas-header">
                        <div class="round-info">
                            Runde <strong id="currentRound">1</strong> / <span id="maxRounds">5</span>
                        </div>
                        <div class="timer" id="timer">60</div>
                    </div>
                    
                    <!-- Wort-Anzeige -->
                    <div class="word-display" id="wordDisplay">
                        <div class="word-label">Dein Wort:</div>
                        <div class="word-text" id="wordText">---</div>
                    </div>
                    
                    <!-- Canvas -->
                    <div class="canvas-wrapper">
                        <canvas id="drawCanvas" width="600" height="400"></canvas>
                    </div>
                    
                    <!-- Zeichenwerkzeuge (nur für Zeichner) -->
                    <div class="drawing-tools" id="drawingTools" style="display: none;">
                        <button class="tool-btn active" data-tool="brush" title="Pinsel">✏️</button>
                        <button class="tool-btn" data-tool="eraser" title="Radierer">🧽</button>
                        <button class="tool-btn" data-tool="fill" title="Füllen">🪣</button>
                        <button class="tool-btn" data-tool="clear" title="Alles löschen">🗑️</button>
                        <div style="width: 1px; background: var(--mp-text-muted); margin: 0 5px;"></div>
                        <button class="color-btn active" data-color="#000000" style="background: #000000;"></button>
                        <button class="color-btn" data-color="#ff0000" style="background: #ff0000;"></button>
                        <button class="color-btn" data-color="#0000ff" style="background: #0000ff;"></button>
                        <button class="color-btn" data-color="#00aa00" style="background: #00aa00;"></button>
                        <button class="color-btn" data-color="#ffaa00" style="background: #ffaa00;"></button>
                        <button class="color-btn" data-color="#aa00aa" style="background: #aa00aa;"></button>
                        <div style="width: 1px; background: var(--mp-text-muted); margin: 0 5px;"></div>
                        <input type="range" class="size-slider" id="brushSize" min="2" max="30" value="5" title="Pinselgröße">
                    </div>
                </div>
                
                <!-- Chat/Rate Bereich -->
                <div class="chat-area">
                    <div class="chat-header">
                        <h3>🏆 Punktestand</h3>
                    </div>
                    
                    <div class="scoreboard" id="scoreboard"></div>
                    
                    <div class="chat-header">
                        <h3>💬 Raten</h3>
                    </div>
                    
                    <div class="guess-list" id="guessList"></div>
                    
                    <div class="guess-input-area" id="guessInputArea">
                        <input type="text" id="guessInput" placeholder="Was ist das?" maxlength="50" autocomplete="off">
                        <button onclick="submitGuess()">Raten!</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ==================== RESULT SCREEN ==================== -->
        <div id="resultScreen" class="mp-game-screen">
            <div class="result-container">
                <div class="result-card">
                    <div class="result-icon">🏆</div>
                    <div class="result-title">Spiel beendet!</div>

                    <div class="final-scores" id="finalScores"></div>

                    <div style="display: flex; gap: 15px; justify-content: center; margin-top: 20px;">
                        <button class="mp-game-btn" onclick="location.reload()">🔄 Neues Spiel</button>
                        <button class="mp-game-btn mp-game-btn--secondary" onclick="location.href='adaptive_learning.php'">← Zurück</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // ==================== KONFIGURATION ====================
        const API_URL = '/api/montagsmaler.php';
        const POLL_INTERVAL = 500; // ms
        
        // ==================== SPIELZUSTAND ====================
        let gameState = {
            gameId: null,
            playerId: null,
            gameCode: null,
            isHost: false,
            isDrawer: false,
            status: 'lobby',
            currentWord: null,
            players: [],
            guesses: [],
            roundEnded: false  // BUG-049 FIX
        };
        
        let playerName = '<?php echo addslashes($userName); ?>';
        let playerAvatar = '<?php echo addslashes($userAvatar); ?>';
        let walletChildId = <?php echo $walletChildId ?: 'null'; ?>;
        
        let pollInterval = null;
        let timerInterval = null;
        let timeLeft = 60;
        
        // Canvas
        let canvas, ctx;
        let isDrawing = false;
        let currentTool = 'brush';
        let currentColor = '#000000';
        let brushSize = 5;
        let lastX, lastY;
        let drawingHistory = [];
        
        // ==================== INITIALISIERUNG ====================
        document.addEventListener('DOMContentLoaded', () => {
            canvas = document.getElementById('drawCanvas');
            ctx = canvas.getContext('2d');
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            
            setupCanvas();
            setupEventListeners();
            
            // Enter-Taste für Inputs
            document.getElementById('playerNameInput').addEventListener('keypress', e => {
                if (e.key === 'Enter') setPlayerName();
            });
            document.getElementById('gameCodeInput').addEventListener('keypress', e => {
                if (e.key === 'Enter') joinGame();
            });
            document.getElementById('guessInput').addEventListener('keypress', e => {
                if (e.key === 'Enter') submitGuess();
            });
        });
        
        function setupCanvas() {
            // Touch-Events
            canvas.addEventListener('mousedown', startDraw);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDraw);
            canvas.addEventListener('mouseout', stopDraw);
            
            canvas.addEventListener('touchstart', e => {
                e.preventDefault();
                const touch = e.touches[0];
                const rect = canvas.getBoundingClientRect();
                startDraw({ offsetX: touch.clientX - rect.left, offsetY: touch.clientY - rect.top });
            });
            canvas.addEventListener('touchmove', e => {
                e.preventDefault();
                const touch = e.touches[0];
                const rect = canvas.getBoundingClientRect();
                draw({ offsetX: touch.clientX - rect.left, offsetY: touch.clientY - rect.top });
            });
            canvas.addEventListener('touchend', stopDraw);
        }
        
        function setupEventListeners() {
            // Tool-Buttons
            document.querySelectorAll('.tool-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tool = btn.dataset.tool;
                    if (tool === 'clear') {
                        clearCanvas();
                    } else {
                        currentTool = tool;
                        document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                    }
                });
            });
            
            // Farb-Buttons
            document.querySelectorAll('.color-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    currentColor = btn.dataset.color;
                    document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                });
            });
            
            // Pinselgröße
            document.getElementById('brushSize').addEventListener('input', e => {
                brushSize = parseInt(e.target.value);
            });
        }
        
        // ==================== ZEICHNEN ====================
        function startDraw(e) {
            if (!gameState.isDrawer) return;
            isDrawing = true;
            lastX = e.offsetX;
            lastY = e.offsetY;
        }
        
        function draw(e) {
            if (!isDrawing || !gameState.isDrawer) return;
            
            ctx.beginPath();
            ctx.strokeStyle = currentTool === 'eraser' ? '#ffffff' : currentColor;
            ctx.lineWidth = currentTool === 'eraser' ? brushSize * 3 : brushSize;
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(e.offsetX, e.offsetY);
            ctx.stroke();
            
            // Für Übertragung speichern
            drawingHistory.push({
                tool: currentTool,
                color: currentColor,
                size: brushSize,
                from: { x: lastX, y: lastY },
                to: { x: e.offsetX, y: e.offsetY }
            });
            
            lastX = e.offsetX;
            lastY = e.offsetY;
        }
        
        function stopDraw() {
            if (isDrawing) {
                isDrawing = false;
                // Zeichnung an Server senden
                sendDrawingUpdate();
            }
        }
        
        function clearCanvas() {
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            drawingHistory = [];
            sendDrawingUpdate();
        }
        
        async function sendDrawingUpdate() {
            if (!gameState.isDrawer) return;
            
            const drawingData = canvas.toDataURL('image/png', 0.5);
            
            await fetch(`${API_URL}?action=draw`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    game_id: gameState.gameId,
                    player_id: gameState.playerId,
                    drawing_data: drawingData
                })
            });
        }
        
        function loadDrawing(dataUrl) {
            if (!dataUrl) return;
            const img = new Image();
            img.onload = () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0);
            };
            img.src = dataUrl;
        }
        
        // ==================== LOBBY FUNKTIONEN ====================
        function setPlayerName() {
            const name = document.getElementById('playerNameInput').value.trim();
            if (!name) {
                showToast('Bitte gib einen Namen ein!', 'error');
                return;
            }
            playerName = name;
            document.getElementById('headerUser').textContent = playerAvatar + ' ' + name;
            document.getElementById('nameCard').style.display = 'none';
            document.getElementById('createCard').style.display = 'block';
            document.getElementById('joinCard').style.display = 'block';
        }
        
        async function createGame() {
            const maxRounds = document.getElementById('roundsSelect').value;
            const roundTime = document.getElementById('timeSelect').value;
            
            try {
                const res = await fetch(`${API_URL}?action=create`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        player_name: playerName,
                        avatar: playerAvatar,
                        wallet_child_id: walletChildId,
                        max_rounds: parseInt(maxRounds),
                        round_time: parseInt(roundTime)
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    gameState.gameId = data.game_id;
                    gameState.playerId = data.player_id;
                    gameState.gameCode = data.game_code;
                    gameState.isHost = true;
                    
                    showScreen('waiting');
                    document.getElementById('displayGameCode').textContent = data.game_code;
                    document.getElementById('hostControls').style.display = 'block';
                    startPolling();
                    showToast('Spiel erstellt!', 'success');
                } else {
                    showToast(data.error || 'Fehler beim Erstellen', 'error');
                }
            } catch (err) {
                showToast('Verbindungsfehler', 'error');
            }
        }
        
        async function joinGame() {
            const code = document.getElementById('gameCodeInput').value.trim().toUpperCase();
            if (code.length !== 6) {
                showToast('Bitte 6-stelligen Code eingeben!', 'error');
                return;
            }
            
            try {
                const res = await fetch(`${API_URL}?action=join`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        game_code: code,
                        player_name: playerName,
                        avatar: playerAvatar,
                        wallet_child_id: walletChildId
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    gameState.gameId = data.game_id;
                    gameState.playerId = data.player_id;
                    gameState.gameCode = code;
                    gameState.isHost = false;
                    
                    showScreen('waiting');
                    document.getElementById('displayGameCode').textContent = code;
                    document.getElementById('guestMessage').style.display = 'block';
                    startPolling();
                    showToast('Beigetreten!', 'success');
                } else {
                    showToast(data.error || 'Spiel nicht gefunden', 'error');
                }
            } catch (err) {
                showToast('Verbindungsfehler', 'error');
            }
        }
        
        async function leaveGame() {
            if (gameState.gameId) {
                await fetch(`${API_URL}?action=leave`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        game_id: gameState.gameId,
                        player_id: gameState.playerId
                    })
                });
            }
            stopPolling();
            location.reload();
        }
        
        async function startGame() {
            try {
                const res = await fetch(`${API_URL}?action=next`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        game_id: gameState.gameId,
                        player_id: gameState.playerId,
                        action: 'start'
                    })
                });
                const data = await res.json();
                
                if (!data.success) {
                    showToast(data.error || 'Fehler beim Starten', 'error');
                }
            } catch (err) {
                showToast('Verbindungsfehler', 'error');
            }
        }
        
        // ==================== POLLING ====================
        function startPolling() {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(pollGameStatus, POLL_INTERVAL);
            pollGameStatus();
        }
        
        function stopPolling() {
            if (pollInterval) clearInterval(pollInterval);
            if (timerInterval) clearInterval(timerInterval);
        }
        
        async function pollGameStatus() {
            if (!gameState.gameId) return;
            
            try {
                const res = await fetch(`${API_URL}?action=status&game_id=${gameState.gameId}&player_id=${gameState.playerId}`);
                const data = await res.json();
                
                if (!data.success) return;
                
                const game = data.game;
                const prevStatus = gameState.status;
                
                gameState.status = game.status;
                gameState.isDrawer = data.is_drawer;
                gameState.isHost = data.is_host;
                gameState.players = data.players;
                gameState.guesses = data.guesses;
                
                // UI Updates
                updatePlayersList(data.players);
                updateScoreboard(data.players, game.current_drawer_id);
                updateGuesses(data.guesses);
                
                // Status-Wechsel
                if (game.status === 'waiting') {
                    showScreen('waiting');
                    const canStart = data.players.length >= 2;
                    document.getElementById('startBtn').disabled = !canStart;
                    document.getElementById('startBtn').textContent = canStart ? '▶️ Spiel starten' : '▶️ Min. 2 Spieler';
                } 
                else if (game.status === 'playing') {
                    if (prevStatus !== 'playing') {
                        showScreen('game');
                        clearCanvas();
                        startTimer(game.round_time);
                    }
                    
                    document.getElementById('currentRound').textContent = game.current_round;
                    document.getElementById('maxRounds').textContent = game.max_rounds;
                    document.getElementById('headerRound').textContent = `Runde ${game.current_round}/${game.max_rounds}`;
                    
                    // Wort-Anzeige
                    const wordDisplay = document.getElementById('wordDisplay');
                    const wordText = document.getElementById('wordText');
                    
                    if (gameState.isDrawer) {
                        wordDisplay.classList.add('drawer');
                        wordText.textContent = game.current_word || '???';
                        document.querySelector('.word-label').textContent = '✏️ Zeichne:';
                        document.getElementById('drawingTools').style.display = 'flex';
                        document.getElementById('guessInputArea').style.display = 'none';
                        canvas.style.cursor = 'crosshair';
                    } else {
                        wordDisplay.classList.remove('drawer');
                        wordText.innerHTML = '_ '.repeat(game.word_length).trim();
                        wordText.classList.add('word-hint');
                        document.querySelector('.word-label').textContent = `🤔 ${game.word_length} Buchstaben`;
                        document.getElementById('drawingTools').style.display = 'none';
                        document.getElementById('guessInputArea').style.display = 'flex';
                        canvas.style.cursor = 'default';
                        
                        // Zeichnung laden
                        if (game.drawing_data) {
                            loadDrawing(game.drawing_data);
                        }
                    }
                    
                    // Timer aktualisieren
                    timeLeft = game.time_left;
                    updateTimerDisplay();
                    
                    // BUG-049 FIX: Prüfen ob Runde bereits erraten wurde
                    if (game.round_guessed && !gameState.roundEnded) {
                        gameState.roundEnded = true;
                        clearInterval(timerInterval);
                        showToast(`🎉 ${game.round_guessed_by || 'Jemand'} hat es erraten!`, 'success');
                        
                        if (gameState.isHost) {
                            // Host startet nächste Runde nach kurzer Pause
                            setTimeout(() => {
                                gameState.roundEnded = false;
                                nextRound();
                            }, 3000);
                        }
                    }
                    
                    // Reset roundEnded bei neuer Runde
                    if (!game.round_guessed) {
                        gameState.roundEnded = false;
                    }
                }
                else if (game.status === 'finished') {
                    showScreen('result');
                    showFinalResults(data.players);
                    stopPolling();
                }
            } catch (err) {
                console.error('Poll error:', err);
            }
        }
        
        // ==================== UI UPDATES ====================
        function showScreen(screenName) {
            document.querySelectorAll('.mp-game-screen').forEach(s => s.classList.remove('active'));
            document.getElementById(screenName + 'Screen').classList.add('active');
        }
        
        function updatePlayersList(players) {
            const list = document.getElementById('playersList');
            list.innerHTML = players.map(p => `
                <div class="player-item">
                    <span class="player-avatar">${p.avatar}</span>
                    <span class="player-name">${escapeHtml(p.player_name)}</span>
                    ${p.is_host ? '<span class="player-host">HOST</span>' : ''}
                </div>
            `).join('');
            document.getElementById('playerCount').textContent = players.length;
        }
        
        function updateScoreboard(players, drawerId) {
            const board = document.getElementById('scoreboard');
            board.innerHTML = players.map(p => `
                <div class="score-item ${p.id == drawerId ? 'drawing' : ''}">
                    <span>${p.avatar}</span>
                    <span>${escapeHtml(p.player_name)}</span>
                    ${p.id == drawerId ? '<span style="color: var(--mp-accent);">✏️</span>' : ''}
                    <span class="score-points">${p.score}</span>
                </div>
            `).join('');
        }
        
        function updateGuesses(guesses) {
            const list = document.getElementById('guessList');
            list.innerHTML = guesses.map(g => `
                <div class="guess-item ${g.is_correct ? 'correct' : ''}">
                    <span class="player">${g.avatar} ${escapeHtml(g.player_name)}:</span>
                    <span class="text">${g.is_correct ? '✅ Richtig!' : escapeHtml(g.guess_text)}</span>
                    ${g.points_earned > 0 ? `<span class="points">+${g.points_earned}</span>` : ''}
                </div>
            `).join('');
            list.scrollTop = list.scrollHeight;
        }
        
        function showFinalResults(players) {
            const sorted = [...players].sort((a, b) => b.score - a.score);
            const ranks = ['🥇', '🥈', '🥉'];
            
            document.getElementById('finalScores').innerHTML = sorted.map((p, i) => `
                <div class="final-score-item ${i === 0 ? 'winner' : ''}">
                    <span class="rank">${ranks[i] || (i + 1) + '.'}</span>
                    <span class="final-avatar">${p.avatar}</span>
                    <span class="final-name">${escapeHtml(p.player_name)}</span>
                    <span class="final-points">${p.score} Pkt</span>
                </div>
            `).join('');
        }
        
        // ==================== TIMER ====================
        function startTimer(duration) {
            timeLeft = duration;
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                timeLeft--;
                updateTimerDisplay();
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    if (gameState.isHost) {
                        // Host startet nächste Runde
                        setTimeout(() => nextRound(), 2000);
                    }
                }
            }, 1000);
        }
        
        function updateTimerDisplay() {
            const timer = document.getElementById('timer');
            timer.textContent = timeLeft;
            timer.classList.remove('warning', 'danger');
            if (timeLeft <= 10) timer.classList.add('danger');
            else if (timeLeft <= 20) timer.classList.add('warning');
        }
        
        async function nextRound() {
            try {
                await fetch(`${API_URL}?action=next`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        game_id: gameState.gameId,
                        player_id: gameState.playerId,
                        action: 'next'
                    })
                });
            } catch (err) {
                console.error('Next round error:', err);
            }
        }
        
        // ==================== RATEN ====================
        async function submitGuess() {
            const input = document.getElementById('guessInput');
            const guess = input.value.trim();
            
            if (!guess) return;
            if (gameState.isDrawer) {
                showToast('Du bist der Zeichner!', 'error');
                return;
            }
            
            try {
                const res = await fetch(`${API_URL}?action=guess`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        game_id: gameState.gameId,
                        player_id: gameState.playerId,
                        guess: guess
                    })
                });
                const data = await res.json();
                
                input.value = '';
                
                if (data.is_correct) {
                    showToast(data.message, 'success');
                    
                    // BUG-047 FIX: Bei korrektem Raten nächste Runde starten
                    if (data.round_ended) {
                        // Kurze Pause, dann nächste Runde (nur Host startet)
                        showToast(`🎉 ${data.guesser_name || 'Jemand'} hat es erraten!`, 'success');
                        if (gameState.isHost) {
                            setTimeout(() => nextRound(), 3000);
                        }
                    }
                }
            } catch (err) {
                showToast('Fehler beim Senden', 'error');
            }
        }
        
        // ==================== HILFSFUNKTIONEN ====================
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;')
                      .replace(/"/g, '&quot;');
        }
        
        function showToast(msg, type = 'info') {
            const toast = document.createElement('div');
            toast.className = 'mp-game-toast mp-game-toast--' + type;
            toast.textContent = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>
