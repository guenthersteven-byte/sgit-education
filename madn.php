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
    <!-- Zentrale Multiplayer CSS -->
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <style>
        /* ===========================================
           MADN-Spezifische Styles
           =========================================== */
        
        /* Lokale Variablen für MADN (erben von multiplayer-theme) */
        :root {
            --field-bg: #f5f5dc;
            --field-border: #333;
        }
        
        /* Player Colors */
        .player-slot.red { border-color: var(--mp-player-red); }
        .player-slot.blue { border-color: var(--mp-player-blue); }
        .player-slot.green { border-color: var(--mp-player-green); }
        .player-slot.yellow { border-color: var(--mp-player-yellow); }
        
        /* Players Grid (MADN-spezifisch: 2x2) */
        .players-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 20px 0;
        }
        .player-slot {
            background: var(--mp-bg-medium);
            border: 2px dashed var(--mp-text-muted);
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
        .player-slot .avatar { font-size: 1.8rem; }
        .player-slot .name { font-weight: 600; margin-top: 5px; }
        .player-slot .color-badge { font-size: 0.8rem; color: var(--mp-text-muted); }
        
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
            background: var(--mp-bg-card);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Spielbrett - BUG-052 FIX: Klassisches Kreuz-Layout */
        .board {
            display: grid;
            grid-template-columns: repeat(11, 36px);
            grid-template-rows: repeat(11, 36px);
            gap: 2px;
            background: #2d4a1c;
            border-radius: 12px;
            padding: 15px;
            border: 4px solid #1a3503;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.3);
        }
        
        .cell {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--mp-transition);
        }
        
        /* Leere Zellen (transparent) */
        .cell.empty {
            background: transparent;
        }
        
        /* Spielfelder (Laufweg) */
        .cell.field {
            background: var(--field-bg);
            border: 2px solid var(--field-border);
            cursor: pointer;
        }
        .cell.field:hover { transform: scale(1.08); }
        
        /* Startfelder (4 Ecken) */
        .cell.start { cursor: pointer; }
        .cell.start-red { background: var(--mp-player-red); border: 2px solid #a93226; }
        .cell.start-blue { background: var(--mp-player-blue); border: 2px solid #1f618d; }
        .cell.start-green { background: var(--mp-player-green); border: 2px solid #1d8348; }
        .cell.start-yellow { background: var(--mp-player-yellow); border: 2px solid #b7950b; }
        
        /* Zielfelder (innere Bahnen zur Mitte) */
        .cell.home-red { background: rgba(231, 76, 60, 0.4); border: 2px solid var(--mp-player-red); }
        .cell.home-blue { background: rgba(52, 152, 219, 0.4); border: 2px solid var(--mp-player-blue); }
        .cell.home-green { background: rgba(39, 174, 96, 0.4); border: 2px solid var(--mp-player-green); }
        .cell.home-yellow { background: rgba(241, 196, 15, 0.4); border: 2px solid var(--mp-player-yellow); }
        
        /* Eingangsfelder (wo man aufs Spielfeld kommt) */
        .cell.entry-red { border: 3px solid var(--mp-player-red) !important; }
        .cell.entry-blue { border: 3px solid var(--mp-player-blue) !important; }
        .cell.entry-green { border: 3px solid var(--mp-player-green) !important; }
        .cell.entry-yellow { border: 3px solid var(--mp-player-yellow) !important; }
        
        /* Mittelfeld */
        .cell.center {
            background: linear-gradient(135deg, #e74c3c 25%, #3498db 25%, #3498db 50%, #27ae60 50%, #27ae60 75%, #f1c40f 75%);
            border: 3px solid #333;
        }
        
        /* Animation für bewegbare Felder */
        .cell.can-move { animation: mp-fieldPulse 0.8s ease infinite; }
        
        /* Spielfiguren - BUG-052 FIX: In Grid-Zellen zentriert */
        .piece {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 3px solid #333;
            cursor: pointer;
            transition: var(--mp-transition);
            box-shadow: 0 3px 6px rgba(0,0,0,0.3);
            /* Nicht mehr absolut, sondern in Zelle zentriert */
            position: relative !important;
            margin: auto;
        }
        .piece.red { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .piece.blue { background: linear-gradient(135deg, #3498db, #2980b9); }
        .piece.green { background: linear-gradient(135deg, #27ae60, #1e8449); }
        .piece.yellow { background: linear-gradient(135deg, #f1c40f, #d4ac0d); }
        .piece.selectable { animation: mp-bounce 0.5s ease infinite; }
        .piece.moving { animation: mp-pieceMove 0.4s ease; }
        .piece.captured { animation: mp-pieceCapture 0.5s ease forwards; }
        
        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .info-card {
            background: var(--mp-bg-card);
            border-radius: 12px;
            padding: 15px;
        }
        .info-card h3 { color: var(--mp-accent); margin-bottom: 10px; font-size: 1rem; }
        
        .turn-indicator {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: var(--mp-bg-medium);
        }
        .turn-indicator .label { font-size: 0.85rem; color: var(--mp-text-muted); }
        .turn-indicator .player { font-size: 1.3rem; font-weight: bold; margin-top: 5px; }
        .turn-indicator.my-turn { border: 2px solid var(--mp-accent); }
        
        /* Würfel - nutzt zentrale mp-diceRoll Animation */
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
            transition: var(--mp-transition);
        }
        .dice:hover { transform: rotate(10deg) scale(1.05); }
        .dice.rolling { animation: mp-diceRoll 0.3s linear infinite; }
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
            background: var(--mp-bg-medium);
            border-radius: 8px;
            margin-bottom: 6px;
        }
        .score-row.active { border: 2px solid var(--mp-accent); }
        .score-row .color { font-size: 1.2rem; }
        .score-row .name { flex: 1; }
        .score-row .pieces { font-size: 0.8rem; color: var(--mp-text-muted); }
        
        /* Toast - verwendet mp-toast aus theme, aber lokale Overrides */
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
        .toast.success { background: var(--mp-accent); color: var(--mp-text-dark); }
        .toast.error { background: var(--mp-error); color: white; }
        .toast.info { background: var(--mp-bg-card); color: var(--mp-text); border: 2px solid var(--mp-accent); }
        
        /* Result */
        .result-container { max-width: 500px; margin: 50px auto; text-align: center; }
        .result-card {
            background: var(--mp-bg-card);
            border-radius: 20px;
            padding: 30px;
            border: 3px solid var(--mp-accent);
        }
        .result-icon { font-size: 5rem; margin-bottom: 15px; }
        .result-title { font-size: 2rem; margin-bottom: 10px; }
        
        /* Mobile Optimierung - BUG-052 FIX */
        @media (max-width: 500px) {
            .board-area {
                padding: 10px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .board {
                grid-template-columns: repeat(11, 28px);
                grid-template-rows: repeat(11, 28px);
                gap: 1px;
                padding: 10px;
            }
            .cell {
                width: 28px;
                height: 28px;
            }
            .piece {
                width: 20px;
                height: 20px;
            }
            .dice {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
            .sidebar {
                gap: 10px;
            }
            .info-card {
                padding: 10px;
            }
            .players-grid {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            .player-slot {
                padding: 10px;
                min-height: auto;
            }
        }
        
        @media (max-width: 380px) {
            .board {
                grid-template-columns: repeat(11, 24px);
                grid-template-rows: repeat(11, 24px);
            }
            .cell {
                width: 24px;
                height: 24px;
            }
            .piece {
                width: 18px;
                height: 18px;
            }
        }
    </style>
</head>
<body class="mp-body">
    <div class="mp-container">
        <div class="mp-header">
            <div>
                <a href="multiplayer.php" class="mp-back-link">← Multiplayer</a>
                <h1 class="mp-header__title">🎲 <span>Mensch ärgere dich nicht</span></h1>
            </div>
            <span class="mp-header__user"><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>
        
        <!-- LOBBY -->
        <div id="lobbyScreen" class="mp-screen active">
            <div class="mp-lobby">
                <div style="font-size: 4rem; margin-bottom: 10px;">🎲</div>
                <h1 class="mp-lobby__title">Mensch ärgere dich nicht</h1>
                <p class="mp-lobby__subtitle">Das Klassiker-Brettspiel für 2-4 Spieler</p>
                
                <div class="mp-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2 class="mp-card__title">👤 Dein Name</h2>
                    <div class="mp-input-group">
                        <input type="text" id="playerNameInput" class="mp-input" placeholder="Name eingeben..." maxlength="20">
                    </div>
                    <button class="mp-btn mp-btn--full" onclick="setPlayerName()">Weiter →</button>
                </div>
                
                <div class="mp-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2 class="mp-card__title">🎮 Neues Spiel</h2>
                    <button class="mp-btn mp-btn--full" onclick="createGame()">Spiel erstellen</button>
                </div>
                
                <div class="mp-divider"><span>oder</span></div>
                
                <div class="mp-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2 class="mp-card__title">🔗 Spiel beitreten</h2>
                    <div class="mp-input-group">
                        <input type="text" id="gameCodeInput" class="mp-input mp-game-code-input" placeholder="CODE" maxlength="6">
                    </div>
                    <button class="mp-btn mp-btn--secondary mp-btn--full" onclick="joinGame()">Beitreten →</button>
                </div>
            </div>
        </div>
        
        <!-- WAITING -->
        <div id="waitingScreen" class="mp-screen">
            <div class="mp-lobby">
                <div class="mp-game-code-display">
                    <p class="mp-text-muted" style="font-size: 0.9rem;">Spiel-Code</p>
                    <div class="mp-game-code mp-animate-pulse" id="displayCode">------</div>
                </div>
                
                <div class="mp-card">
                    <h2 class="mp-card__title">👥 Spieler</h2>
                    <div class="players-grid" id="playersGrid">
                        <div class="player-slot red"><span class="mp-text-muted">🔴 Wartet...</span></div>
                        <div class="player-slot blue"><span class="mp-text-muted">🔵 Wartet...</span></div>
                        <div class="player-slot green"><span class="mp-text-muted">🟢 Wartet...</span></div>
                        <div class="player-slot yellow"><span class="mp-text-muted">🟡 Wartet...</span></div>
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
        <div id="gameScreen" class="mp-screen">
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
                        <p id="diceMsg" class="mp-text-muted" style="font-size: 0.9rem;">Klicke zum Würfeln</p>
                    </div>
                    <div class="info-card">
                        <h3>📊 Spieler</h3>
                        <div class="scoreboard" id="scoreboard"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RESULT -->
        <div id="resultScreen" class="mp-screen">
            <div class="result-container mp-animate-pop">
                <div class="result-card">
                    <div class="result-icon">🏆</div>
                    <div class="result-title" id="winnerName">Gewinner!</div>
                    <p class="mp-text-muted" style="margin: 20px 0;">hat alle Figuren ins Ziel gebracht!</p>
                    <button class="mp-btn mp-btn--full" onclick="location.reload()">🔄 Neues Spiel</button>
                    <button class="mp-btn mp-btn--secondary mp-btn--full mp-mt-1" onclick="location.href='multiplayer.php'">← Zurück</button>
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
        
        // ============================================
        // BUG-052 FIX: Klassisches Kreuz-Layout
        // 11x11 Grid mit korrekten Feldpositionen
        // ============================================
        
        // Board-Layout Definition (11x11 Grid)
        // 'e' = empty, 'w' = weg (path), 'c' = center
        // 'sr' = start-red, 'sb' = start-blue, 'sg' = start-green, 'sy' = start-yellow
        // 'hr' = home-red, 'hb' = home-blue, 'hg' = home-green, 'hy' = home-yellow
        // 'er' = entry-red, 'eb' = entry-blue, 'eg' = entry-green, 'ey' = entry-yellow
        const BOARD_LAYOUT = [
            ['sr','sr','e', 'e', 'w', 'w', 'eb','e', 'e', 'sb','sb'],
            ['sr','sr','e', 'e', 'w', 'hb','w', 'e', 'e', 'sb','sb'],
            ['e', 'e', 'e', 'e', 'w', 'hb','w', 'e', 'e', 'e', 'e'],
            ['e', 'e', 'e', 'e', 'w', 'hb','w', 'e', 'e', 'e', 'e'],
            ['er','w', 'w', 'w', 'w', 'hb','w', 'w', 'w', 'w', 'w'],
            ['w', 'hr','hr','hr','hr','c', 'hg','hg','hg','hg','w'],
            ['w', 'w', 'w', 'w', 'w', 'hy','w', 'w', 'w', 'w', 'eg'],
            ['e', 'e', 'e', 'e', 'w', 'hy','w', 'e', 'e', 'e', 'e'],
            ['e', 'e', 'e', 'e', 'w', 'hy','w', 'e', 'e', 'e', 'e'],
            ['sy','sy','e', 'e', 'w', 'hy','w', 'e', 'e', 'sg','sg'],
            ['sy','sy','e', 'e', 'ey','w', 'w', 'e', 'e', 'sg','sg']
        ];
        
        // Mapping: Grid-Position (row, col) -> Spielfeld-Index
        // Hauptfelder 0-39 im Uhrzeigersinn, Start bei Rot (links)
        const GRID_TO_INDEX = {};
        const INDEX_TO_GRID = {};
        
        // Hauptweg (40 Felder) - Uhrzeigersinn startend bei Rot-Entry
        // Index 0=Rot Entry, 10=Blau Entry, 20=Grün Entry, 30=Gelb Entry
        const PATH_COORDS = [
            // 0-9: Rot Entry bis Blau Entry (links hoch, dann oben nach rechts)
            [4,0],  // 0: Rot Entry (links mitte)
            [3,0],  // 1
            [2,0],  // 2
            [1,0],  // 3
            [0,0],  // 4: Ecke oben-links
            [0,1],  // 5
            [0,2],  // 6
            [0,3],  // 7
            [0,4],  // 8
            [0,5],  // 9
            // 10-19: Blau Entry bis Grün Entry (oben nach rechts, dann rechts runter)
            [0,6],  // 10: Blau Entry (oben mitte)
            [0,7],  // 11
            [0,8],  // 12
            [0,9],  // 13
            [0,10], // 14: Ecke oben-rechts
            [1,10], // 15
            [2,10], // 16
            [3,10], // 17
            [4,10], // 18
            [5,10], // 19
            // 20-29: Grün Entry bis Gelb Entry (rechts runter, dann unten nach links)
            [6,10], // 20: Grün Entry (rechts mitte)
            [7,10], // 21
            [8,10], // 22
            [9,10], // 23
            [10,10],// 24: Ecke unten-rechts
            [10,9], // 25
            [10,8], // 26
            [10,7], // 27
            [10,6], // 28
            [10,5], // 29
            // 30-39: Gelb Entry bis zurück zu Rot (unten nach links, dann links hoch)
            [10,4], // 30: Gelb Entry (unten mitte)
            [10,3], // 31
            [10,2], // 32
            [10,1], // 33
            [10,0], // 34: Ecke unten-links
            [9,0],  // 35
            [8,0],  // 36
            [7,0],  // 37
            [6,0],  // 38
            [5,0]   // 39: zurück Richtung Rot Entry
        ];
        
        // Startfelder pro Farbe (4 Felder je Ecke)
        const START_COORDS = {
            'red':    [[0,0], [0,1], [1,0], [1,1]],
            'blue':   [[0,9], [0,10], [1,9], [1,10]],
            'green':  [[9,9], [9,10], [10,9], [10,10]],
            'yellow': [[9,0], [9,1], [10,0], [10,1]]
        };
        
        // Home-Felder (Zielbahnen zur Mitte, je 4)
        const HOME_COORDS = {
            'red':    [[5,1], [5,2], [5,3], [5,4]],
            'blue':   [[1,5], [2,5], [3,5], [4,5]],
            'green':  [[5,9], [5,8], [5,7], [5,6]],
            'yellow': [[9,5], [8,5], [7,5], [6,5]]
        };
        
        // Entry-Felder (wo man aufs Brett kommt)
        const ENTRY_POSITIONS = {
            'red': 0,    // Index 0 im Hauptweg
            'blue': 10,  // Index 10
            'green': 20, // Index 20
            'yellow': 30 // Index 30
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
        
        // Board initialisieren - BUG-052 FIX: Grid-basiertes Layout
        function initBoard() {
            const board = document.getElementById('gameBoard');
            board.innerHTML = '';
            
            // 11x11 Grid durchgehen
            for (let row = 0; row < 11; row++) {
                for (let col = 0; col < 11; col++) {
                    const cellType = BOARD_LAYOUT[row][col];
                    const cell = document.createElement('div');
                    cell.className = 'cell';
                    cell.dataset.row = row;
                    cell.dataset.col = col;
                    
                    switch(cellType) {
                        case 'e':
                            cell.classList.add('empty');
                            break;
                        case 'w':
                            cell.classList.add('field');
                            // Hauptweg-Index finden
                            const pathIdx = PATH_COORDS.findIndex(c => c[0] === row && c[1] === col);
                            if (pathIdx !== -1) {
                                cell.dataset.pathIndex = pathIdx;
                            }
                            break;
                        case 'c':
                            cell.classList.add('center');
                            break;
                        case 'sr': cell.classList.add('start', 'start-red'); cell.dataset.start = 'red'; break;
                        case 'sb': cell.classList.add('start', 'start-blue'); cell.dataset.start = 'blue'; break;
                        case 'sg': cell.classList.add('start', 'start-green'); cell.dataset.start = 'green'; break;
                        case 'sy': cell.classList.add('start', 'start-yellow'); cell.dataset.start = 'yellow'; break;
                        case 'hr': cell.classList.add('field', 'home-red'); cell.dataset.home = 'red'; break;
                        case 'hb': cell.classList.add('field', 'home-blue'); cell.dataset.home = 'blue'; break;
                        case 'hg': cell.classList.add('field', 'home-green'); cell.dataset.home = 'green'; break;
                        case 'hy': cell.classList.add('field', 'home-yellow'); cell.dataset.home = 'yellow'; break;
                        case 'er': 
                            cell.classList.add('field', 'entry-red'); 
                            cell.dataset.pathIndex = 0;
                            break;
                        case 'eb': 
                            cell.classList.add('field', 'entry-blue'); 
                            cell.dataset.pathIndex = 10;
                            break;
                        case 'eg': 
                            cell.classList.add('field', 'entry-green'); 
                            cell.dataset.pathIndex = 20;
                            break;
                        case 'ey': 
                            cell.classList.add('field', 'entry-yellow'); 
                            cell.dataset.pathIndex = 30;
                            break;
                    }
                    
                    board.appendChild(cell);
                }
            }
            
            // Home-Indizes setzen
            Object.entries(HOME_COORDS).forEach(([color, coords]) => {
                coords.forEach((coord, idx) => {
                    const cell = board.querySelector(`[data-row="${coord[0]}"][data-col="${coord[1]}"]`);
                    if (cell) {
                        cell.dataset.homeIndex = idx;
                    }
                });
            });
            
            // Start-Indizes setzen
            Object.entries(START_COORDS).forEach(([color, coords]) => {
                coords.forEach((coord, idx) => {
                    const cell = board.querySelector(`[data-row="${coord[0]}"][data-col="${coord[1]}"]`);
                    if (cell) {
                        cell.dataset.startIndex = idx;
                    }
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
            
            // BUG-052 FIX: Figuren in Grid-Zellen platzieren
            data.players.forEach(player => {
                const pieces = player.pieces;
                const color = player.color;
                const isMyPiece = player.id == gameState.playerId;
                
                pieces.forEach((pos, pieceIdx) => {
                    const piece = document.createElement('div');
                    piece.className = `piece ${color}`;
                    piece.dataset.playerId = player.id;
                    piece.dataset.pieceIndex = pieceIdx;
                    piece.style.position = 'relative';
                    
                    let targetCell = null;
                    
                    if (pos < 0) {
                        // Im Startbereich (-1 bis -4)
                        const startIdx = Math.abs(pos) - 1;
                        const coords = START_COORDS[color][startIdx];
                        if (coords) {
                            targetCell = board.querySelector(`[data-row="${coords[0]}"][data-col="${coords[1]}"]`);
                        }
                    } else if (pos >= 40) {
                        // Im Zielbereich (40-43)
                        const homeIdx = pos - 40;
                        const coords = HOME_COORDS[color][homeIdx];
                        if (coords) {
                            targetCell = board.querySelector(`[data-row="${coords[0]}"][data-col="${coords[1]}"]`);
                        }
                    } else {
                        // Auf dem Hauptweg (0-39)
                        const coords = PATH_COORDS[pos];
                        if (coords) {
                            targetCell = board.querySelector(`[data-row="${coords[0]}"][data-col="${coords[1]}"]`);
                        }
                    }
                    
                    if (targetCell) {
                        // Klickbar wenn mein Zug und kann ziehen
                        if (isMyPiece && gameState.myTurn && gameState.canMove) {
                            piece.classList.add('selectable');
                            piece.onclick = (e) => { e.stopPropagation(); selectPiece(pieceIdx); };
                        }
                        targetCell.appendChild(piece);
                    }
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
