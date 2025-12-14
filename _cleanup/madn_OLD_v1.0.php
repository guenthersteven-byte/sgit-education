<?php
/**
 * ============================================================================
 * sgiT Education - Mensch ärgere dich nicht v1.0
 * ============================================================================
 * 
 * Klassisches Brettspiel für 2-4 Spieler
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
require_once 'includes/version.php';
require_once __DIR__ . '/wallet/SessionManager.php';

// User-Daten aus SessionManager (wie multiplayer.php)
$userName = '';
$userAge = 10;
$walletChildId = 0;
$userAvatar = '😀';

// SessionManager prüfen (primäre Quelle)
if (SessionManager::isLoggedIn()) {
    $childData = SessionManager::getChild();
    if ($childData) {
        $walletChildId = $childData['id'];
        $userName = $childData['name'];
        $userAvatar = $childData['avatar'] ?? '😀';
        $userAge = $childData['age'] ?? 10;
    }
}
// Fallback: Standard Session-Keys
elseif (isset($_SESSION['wallet_child_id'])) {
    $walletChildId = $_SESSION['wallet_child_id'];
    $userName = $_SESSION['user_name'] ?? $_SESSION['child_name'] ?? '';
    $userAvatar = $_SESSION['avatar'] ?? '😀';
    $userAge = $_SESSION['user_age'] ?? 10;
}

$colors = ['red' => '🔴', 'blue' => '🔵', 'green' => '🟢', 'yellow' => '🟡'];
$colorNames = ['red' => 'Rot', 'blue' => 'Blau', 'green' => 'Grün', 'yellow' => 'Gelb'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎲 Mensch ärgere dich nicht - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bg: #0d1f02;
            --card-bg: #1e3a08;
            --text: #ffffff;
            --text-muted: #a0a0a0;
            --red: #e74c3c;
            --blue: #3498db;
            --green: #27ae60;
            --yellow: #f1c40f;
            --field-bg: #f5f5dc;
            --field-border: #333;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, var(--primary) 100%);
            min-height: 100vh;
            color: var(--text);
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 15px; }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: var(--card-bg);
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .header h1 { font-size: 1.4rem; }
        .header h1 span { color: var(--accent); }
        .back-link { color: var(--accent); text-decoration: none; font-size: 0.9rem; }
        .back-link:hover { text-decoration: underline; }
        
        .screen { display: none; }
        .screen.active { display: block; }
        
        /* Lobby */
        .lobby-container { max-width: 500px; margin: 30px auto; text-align: center; }
        .lobby-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .lobby-card h2 { color: var(--accent); margin-bottom: 15px; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; color: var(--text-muted); font-size: 0.9rem; text-align: left; }
        .input-group input, .input-group select {
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
            transition: all 0.2s;
            width: 100%;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(67, 210, 64, 0.3); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn.secondary { background: var(--card-bg); color: var(--text); border: 2px solid var(--accent); }
        
        .divider { display: flex; align-items: center; margin: 20px 0; color: var(--text-muted); }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--text-muted); opacity: 0.3; }
        .divider span { padding: 0 15px; }
        
        /* Waiting Room */
        .game-code-display {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .game-code { font-size: 2.5rem; font-weight: bold; color: var(--accent); letter-spacing: 8px; font-family: monospace; }
        .players-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 20px 0;
        }
        .player-slot {
            background: var(--bg);
            border: 2px dashed var(--text-muted);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .player-slot.filled { border-style: solid; }
        .player-slot.red { border-color: var(--red); }
        .player-slot.blue { border-color: var(--blue); }
        .player-slot.green { border-color: var(--green); }
        .player-slot.yellow { border-color: var(--yellow); }
        .player-slot .avatar { font-size: 1.8rem; }
        .player-slot .name { font-weight: 600; margin-top: 5px; }
        .player-slot .color-badge { font-size: 0.8rem; color: var(--text-muted); }
        
        /* Game Board Container */
        .game-container {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 20px;
        }
        @media (max-width: 800px) {
            .game-container { grid-template-columns: 1fr; }
        }
        
        .board-area {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Spielbrett */
        .board {
            width: 440px;
            height: 440px;
            background: #2d4a1c;
            border-radius: 10px;
            position: relative;
            border: 4px solid #1a3503;
        }
        
        .field {
            position: absolute;
            width: 36px;
            height: 36px;
            background: var(--field-bg);
            border: 2px solid var(--field-border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.4rem;
        }
        .field:hover { transform: scale(1.1); }
        .field.start-red { background: var(--red); }
        .field.start-blue { background: var(--blue); }
        .field.start-green { background: var(--green); }
        .field.start-yellow { background: var(--yellow); }
        .field.home-red { background: rgba(231, 76, 60, 0.3); border-color: var(--red); }
        .field.home-blue { background: rgba(52, 152, 219, 0.3); border-color: var(--blue); }
        .field.home-green { background: rgba(39, 174, 96, 0.3); border-color: var(--green); }
        .field.home-yellow { background: rgba(241, 196, 15, 0.3); border-color: var(--yellow); }
        .field.entry-red { border-color: var(--red); border-width: 3px; }
        .field.entry-blue { border-color: var(--blue); border-width: 3px; }
        .field.entry-green { border-color: var(--green); border-width: 3px; }
        .field.entry-yellow { border-color: var(--yellow); border-width: 3px; }
        .field.can-move { animation: pulse 0.8s infinite; box-shadow: 0 0 10px var(--accent); }
        @keyframes pulse { 50% { transform: scale(1.15); } }
        
        .piece {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 3px solid #333;
            position: absolute;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 3px 6px rgba(0,0,0,0.3);
        }
        .piece.red { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .piece.blue { background: linear-gradient(135deg, #3498db, #2980b9); }
        .piece.green { background: linear-gradient(135deg, #27ae60, #1e8449); }
        .piece.yellow { background: linear-gradient(135deg, #f1c40f, #d4ac0d); }
        .piece.selectable { animation: bounce 0.5s infinite; }
        @keyframes bounce { 50% { transform: translateY(-5px); } }
        
        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .info-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 15px;
        }
        .info-card h3 { color: var(--accent); margin-bottom: 10px; font-size: 1rem; }
        
        .turn-indicator {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: var(--bg);
        }
        .turn-indicator .label { font-size: 0.85rem; color: var(--text-muted); }
        .turn-indicator .player { font-size: 1.3rem; font-weight: bold; margin-top: 5px; }
        .turn-indicator.my-turn { border: 2px solid var(--accent); }
        
        /* Würfel */
        .dice-area { text-align: center; padding: 20px; }
        .dice {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin: 15px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            cursor: pointer;
            transition: all 0.2s;
        }
        .dice:hover { transform: rotate(10deg) scale(1.05); }
        .dice.rolling { animation: roll 0.3s infinite; }
        @keyframes roll {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(90deg); }
            50% { transform: rotate(180deg); }
            75% { transform: rotate(270deg); }
            100% { transform: rotate(360deg); }
        }
        .dice-dots { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; padding: 10px; }
        .dot {
            width: 14px;
            height: 14px;
            background: #333;
            border-radius: 50%;
        }
        .dot.hidden { visibility: hidden; }
        
        .scoreboard { margin-top: 10px; }
        .score-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            background: var(--bg);
            border-radius: 8px;
            margin-bottom: 6px;
        }
        .score-row.active { border: 2px solid var(--accent); }
        .score-row .color { font-size: 1.2rem; }
        .score-row .name { flex: 1; }
        .score-row .pieces { font-size: 0.8rem; color: var(--text-muted); }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
            border-radius: 12px;
            font-weight: 600;
            z-index: 1000;
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateX(-50%) translateY(20px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        .toast.success { background: var(--accent); color: var(--primary); }
        .toast.error { background: #e74c3c; color: white; }
        .toast.info { background: var(--card-bg); color: var(--text); border: 2px solid var(--accent); }
        
        /* Result */
        .result-container { max-width: 500px; margin: 50px auto; text-align: center; }
        .result-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            border: 3px solid var(--accent);
        }
        .result-icon { font-size: 5rem; margin-bottom: 15px; }
        .result-title { font-size: 2rem; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="multiplayer.php" class="back-link">← Multiplayer</a>
                <h1>🎲 <span>Mensch ärgere dich nicht</span></h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>
        
        <!-- LOBBY -->
        <div id="lobbyScreen" class="screen active">
            <div class="lobby-container">
                <div style="font-size: 4rem; margin-bottom: 10px;">🎲</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Mensch ärgere dich nicht</h1>
                <p style="color: var(--text-muted); margin-bottom: 25px;">Das Klassiker-Brettspiel für 2-4 Spieler</p>
                
                <div class="lobby-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2>👤 Dein Name</h2>
                    <div class="input-group">
                        <input type="text" id="playerNameInput" placeholder="Name eingeben..." maxlength="20">
                    </div>
                    <button class="btn" onclick="setPlayerName()">Weiter →</button>
                </div>
                
                <div class="lobby-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🎮 Neues Spiel</h2>
                    <button class="btn" onclick="createGame()">Spiel erstellen</button>
                </div>
                
                <div class="divider"><span>oder</span></div>
                
                <div class="lobby-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2>🔗 Spiel beitreten</h2>
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
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Spiel-Code</p>
                    <div class="game-code" id="displayCode">------</div>
                </div>
                
                <div class="lobby-card">
                    <h2>👥 Spieler</h2>
                    <div class="players-grid" id="playersGrid">
                        <div class="player-slot red"><span style="color: var(--text-muted);">🔴 Wartet...</span></div>
                        <div class="player-slot blue"><span style="color: var(--text-muted);">🔵 Wartet...</span></div>
                        <div class="player-slot green"><span style="color: var(--text-muted);">🟢 Wartet...</span></div>
                        <div class="player-slot yellow"><span style="color: var(--text-muted);">🟡 Wartet...</span></div>
                    </div>
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
                <div class="board-area">
                    <div class="board" id="gameBoard"></div>
                </div>
                <div class="sidebar">
                    <div class="info-card">
                        <div class="turn-indicator" id="turnIndicator">
                            <div class="label">Am Zug:</div>
                            <div class="player" id="currentPlayerName">---</div>
                        </div>
                    </div>
                    <div class="info-card dice-area">
                        <h3>🎲 Würfel</h3>
                        <div class="dice" id="dice" onclick="rollDice()">?</div>
                        <p id="diceMsg" style="color: var(--text-muted); font-size: 0.9rem;">Klicke zum Würfeln</p>
                    </div>
                    <div class="info-card">
                        <h3>📊 Spieler</h3>
                        <div class="scoreboard" id="scoreboard"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RESULT -->
        <div id="resultScreen" class="screen">
            <div class="result-container">
                <div class="result-card">
                    <div class="result-icon">🏆</div>
                    <div class="result-title" id="winnerName">Gewinner!</div>
                    <p style="color: var(--text-muted); margin: 20px 0;">hat alle Figuren ins Ziel gebracht!</p>
                    <button class="btn" onclick="location.reload()">🔄 Neues Spiel</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='multiplayer.php'">← Zurück</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const API_URL = '/api/madn.php';
        const POLL_INTERVAL = 800;
        const COLORS = ['red', 'blue', 'green', 'yellow'];
        const COLOR_EMOJIS = {'red': '🔴', 'blue': '🔵', 'green': '🟢', 'yellow': '🟡'};
        const COLOR_NAMES = {'red': 'Rot', 'blue': 'Blau', 'green': 'Grün', 'yellow': 'Gelb'};
        
        // Spielfeld-Koordinaten (x, y in Pixel)
        // 40 Felder im Kreis + Startfelder + Zielfelder
        const FIELD_POSITIONS = {};
        
        // Hauptfelder (0-39) - im Uhrzeigersinn
        const mainFields = [
            // Unten links nach oben (Rot Start)
            [42, 202], [42, 162], [42, 122], [42, 82], [82, 42],
            // Oben links nach rechts
            [122, 42], [162, 42], [202, 42], [202, 82],
            // Rechts oben (Blau Start)
            [202, 122], [242, 82], [282, 42], [322, 42], [362, 42],
            // Oben rechts nach unten
            [402, 82], [402, 122], [402, 162], [402, 202], [362, 202],
            // Rechts unten (Grün Start)
            [322, 202], [362, 242], [402, 282], [402, 322], [402, 362],
            // Unten rechts nach links
            [362, 402], [322, 402], [282, 402], [242, 402], [242, 362],
            // Links unten (Gelb Start)
            [242, 322], [202, 362], [162, 402], [122, 402], [82, 402],
            // Unten links nach oben
            [42, 362], [42, 322], [42, 282], [42, 242], [82, 202]
        ];
        
        // Startbereiche (je 4 Figuren) - -1 bis -4 pro Spieler
        const startAreas = {
            'red': [[20, 20], [60, 20], [20, 60], [60, 60]],
            'blue': [[340, 20], [380, 20], [340, 60], [380, 60]],
            'green': [[340, 340], [380, 340], [340, 380], [380, 380]],
            'yellow': [[20, 340], [60, 340], [20, 380], [60, 380]]
        };
        
        // Zielbereiche (40-43 pro Spieler)
        const homeAreas = {
            'red': [[82, 202], [122, 202], [162, 202], [202, 202]],
            'blue': [[202, 82], [202, 122], [202, 162], [202, 202]],
            'green': [[322, 202], [282, 202], [242, 202], [202, 202]],
            'yellow': [[202, 322], [202, 282], [202, 242], [202, 202]]
        };
        
        // Spielzustand
        let gameState = {
            gameId: null,
            playerId: null,
            gameCode: null,
            isHost: false,
            myColor: null,
            status: 'lobby',
            currentRoll: 0,
            canMove: false,
            myTurn: false
        };
        
        let playerName = '<?php echo addslashes($userName); ?>';
        let playerAvatar = '<?php echo addslashes($userAvatar); ?>';
        let walletChildId = <?php echo $walletChildId ?: 'null'; ?>;
        let pollInterval = null;
        
        // Board initialisieren
        function initBoard() {
            const board = document.getElementById('gameBoard');
            board.innerHTML = '';
            
            // Hauptfelder erzeugen
            mainFields.forEach((pos, i) => {
                const field = document.createElement('div');
                field.className = 'field';
                field.dataset.index = i;
                field.style.left = pos[0] + 'px';
                field.style.top = pos[1] + 'px';
                
                // Startfelder markieren
                if (i === 0) field.classList.add('entry-red');
                if (i === 10) field.classList.add('entry-blue');
                if (i === 20) field.classList.add('entry-green');
                if (i === 30) field.classList.add('entry-yellow');
                
                board.appendChild(field);
            });
            
            // Startbereiche
            Object.entries(startAreas).forEach(([color, positions]) => {
                positions.forEach((pos, i) => {
                    const field = document.createElement('div');
                    field.className = `field start-${color}`;
                    field.dataset.start = color;
                    field.dataset.startIndex = i;
                    field.style.left = pos[0] + 'px';
                    field.style.top = pos[1] + 'px';
                    board.appendChild(field);
                });
            });
            
            // Zielbereiche (nur die ersten 3 pro Farbe, da 4. im Zentrum)
            Object.entries(homeAreas).forEach(([color, positions]) => {
                positions.slice(0, 3).forEach((pos, i) => {
                    const field = document.createElement('div');
                    field.className = `field home-${color}`;
                    field.dataset.home = color;
                    field.dataset.homeIndex = i;
                    field.style.left = pos[0] + 'px';
                    field.style.top = pos[1] + 'px';
                    board.appendChild(field);
                });
            });
        }
        
        // UI Funktionen
        function showScreen(name) {
            document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
            document.getElementById(name + 'Screen').classList.add('active');
        }
        
        function setPlayerName() {
            const name = document.getElementById('playerNameInput').value.trim();
            if (!name) { showToast('Bitte Namen eingeben', 'error'); return; }
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
                gameState.gameId = data.game_id;
                gameState.playerId = data.player_id;
                gameState.gameCode = data.game_code;
                gameState.isHost = true;
                gameState.myColor = data.color;
                
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
                gameState.gameId = data.game_id;
                gameState.playerId = data.player_id;
                gameState.gameCode = code;
                gameState.isHost = false;
                gameState.myColor = data.color;
                
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
            gameState.currentRoll = game.current_roll;
            gameState.canMove = game.can_move;
            
            if (game.status === 'waiting') {
                updateWaitingRoom(data.players);
                const canStart = data.players.length >= 2;
                document.getElementById('startBtn').disabled = !canStart;
                document.getElementById('startBtn').textContent = canStart ? '▶️ Spiel starten' : '▶️ Min. 2 Spieler';
            }
            else if (game.status === 'playing') {
                if (prevStatus !== 'playing') {
                    showScreen('game');
                    initBoard();
                }
                renderGame(data);
            }
            else if (game.status === 'finished') {
                showScreen('result');
                const winner = data.players.find(p => p.id == game.winner_id);
                document.getElementById('winnerName').textContent = winner ? `${COLOR_EMOJIS[winner.color]} ${winner.player_name}` : 'Unbekannt';
                stopPolling();
            }
        }
        
        function updateWaitingRoom(players) {
            const grid = document.getElementById('playersGrid');
            grid.innerHTML = COLORS.map(color => {
                const player = players.find(p => p.color === color);
                if (player) {
                    return `<div class="player-slot ${color} filled">
                        <span class="avatar">${player.avatar}</span>
                        <span class="name">${escapeHtml(player.player_name)}</span>
                        <span class="color-badge">${COLOR_EMOJIS[color]} ${COLOR_NAMES[color]}</span>
                    </div>`;
                }
                return `<div class="player-slot ${color}"><span style="color: var(--text-muted);">${COLOR_EMOJIS[color]} Frei</span></div>`;
            }).join('');
        }
        
        function renderGame(data) {
            const board = document.getElementById('gameBoard');
            const currentPlayer = data.current_player_data;
            
            // Turn indicator
            const indicator = document.getElementById('turnIndicator');
            indicator.className = 'turn-indicator' + (gameState.myTurn ? ' my-turn' : '');
            document.getElementById('currentPlayerName').innerHTML = 
                currentPlayer ? `${COLOR_EMOJIS[currentPlayer.color]} ${escapeHtml(currentPlayer.player_name)}` : '---';
            
            // Dice
            document.getElementById('dice').textContent = gameState.currentRoll || '?';
            document.getElementById('diceMsg').textContent = 
                gameState.myTurn ? (gameState.canMove ? 'Wähle eine Figur!' : 'Klicke zum Würfeln') : 'Warte...';
            
            // Scoreboard
            document.getElementById('scoreboard').innerHTML = data.players.map(p => {
                const finished = p.pieces.filter(pos => pos >= 40).length;
                const isActive = p.player_order === data.game.current_player;
                return `<div class="score-row ${isActive ? 'active' : ''}">
                    <span class="color">${COLOR_EMOJIS[p.color]}</span>
                    <span class="name">${escapeHtml(p.player_name)}</span>
                    <span class="pieces">${finished}/4 🏠</span>
                </div>`;
            }).join('');
            
            // Figuren entfernen
            board.querySelectorAll('.piece').forEach(p => p.remove());
            
            // Figuren zeichnen
            data.players.forEach(player => {
                const pieces = player.pieces;
                const color = player.color;
                const playerOrder = player.player_order;
                const isMyPiece = player.id == gameState.playerId;
                
                pieces.forEach((pos, pieceIdx) => {
                    const piece = document.createElement('div');
                    piece.className = `piece ${color}`;
                    piece.dataset.playerId = player.id;
                    piece.dataset.pieceIndex = pieceIdx;
                    
                    let x, y;
                    
                    if (pos < 0) {
                        // Im Startbereich
                        const startIdx = Math.abs(pos) - 1;
                        [x, y] = startAreas[color][startIdx];
                    } else if (pos >= 40) {
                        // Im Zielbereich
                        const homeIdx = pos - 40;
                        [x, y] = homeAreas[color][homeIdx] || homeAreas[color][2];
                    } else {
                        // Auf dem Brett - pos ist bereits absolute Position (von API)
                        // BUG-049 FIX: Überflüssige absPos-Berechnung entfernt
                        [x, y] = mainFields[pos] || [200, 200];
                    }
                    
                    piece.style.left = (x + 4) + 'px';
                    piece.style.top = (y + 4) + 'px';
                    
                    // Klickbar wenn mein Zug und kann ziehen
                    if (isMyPiece && gameState.myTurn && gameState.canMove) {
                        piece.classList.add('selectable');
                        piece.onclick = () => selectPiece(pieceIdx);
                    }
                    
                    board.appendChild(piece);
                });
            });
        }
        
        async function rollDice() {
            if (!gameState.myTurn || gameState.canMove) return;
            
            const dice = document.getElementById('dice');
            dice.classList.add('rolling');
            
            const res = await fetch(`${API_URL}?action=roll`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId })
            });
            const data = await res.json();
            
            setTimeout(() => {
                dice.classList.remove('rolling');
                if (data.success) {
                    dice.textContent = data.roll;
                    if (!data.can_move && data.roll !== 6) {
                        showToast('Kein Zug möglich', 'info');
                    }
                }
            }, 500);
        }
        
        async function selectPiece(pieceIndex) {
            const res = await fetch(`${API_URL}?action=move`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ game_id: gameState.gameId, player_id: gameState.playerId, piece_index: pieceIndex })
            });
            const data = await res.json();
            
            if (data.success) {
                if (data.kicked) {
                    showToast(`${COLOR_EMOJIS[data.kicked.color]} ${data.kicked.player_name} geschlagen!`, 'success');
                }
                if (data.winner) {
                    showToast('🎉 GEWONNEN!', 'success');
                }
            } else {
                showToast(data.error || 'Ungültiger Zug', 'error');
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
