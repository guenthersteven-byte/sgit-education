<?php
/**
 * sgiT Education - Mensch aergere dich nicht v1.1
 * @version 1.1
 */
require_once __DIR__ . '/includes/game_header.php';
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
           BUG-052 FIX: MADN Kreuz-Layout v2.0
           =========================================== */
        
        /* Player Colors */
        .mp-lobby-player-slot.red { border-color: var(--mp-player-red); }
        .mp-lobby-player-slot.blue { border-color: var(--mp-player-blue); }
        .mp-lobby-player-slot.green { border-color: var(--mp-player-green); }
        .mp-lobby-player-slot.yellow { border-color: var(--mp-player-yellow); }

        /* Players Grid (MADN-spezifisch: 2x2) */
        .players-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 20px 0;
        }
        .mp-lobby-player-slot .avatar { font-size: 1.8rem; }
        .mp-lobby-player-slot .name { font-weight: 600; margin-top: 5px; }
        .mp-lobby-player-slot .color-badge { font-size: 0.8rem; color: var(--mp-text-muted); }
        
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
        
        /* =========================================
           SPIELBRETT - Kreuz-Layout
           11x11 Grid: 11*32 + 10*6 + 2*12 = 436px
           ========================================= */
        .board {
            position: relative;
            width: 436px;
            height: 436px;
            background: #2d4a1c;
            border-radius: 16px;
            padding: 0;
            border: 4px solid #1a3503;
            box-shadow: 
                inset 0 0 30px rgba(0,0,0,0.3),
                0 8px 32px rgba(0,0,0,0.4);
        }
        
        /* Alle Spielfelder absolut positioniert */
        .cell {
            position: absolute;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            z-index: 1;
        }
        
        /* Wegfelder (beige/weiß) */
        .cell.path {
            background: linear-gradient(135deg, #f5f5dc, #e8e8c8);
            border: 2px solid #666;
            box-shadow: inset 0 2px 4px rgba(255,255,255,0.5);
        }
        
        .cell.path:hover {
            transform: scale(1.1);
            box-shadow: 0 0 10px rgba(67, 210, 64, 0.5);
        }
        
        /* Eingangsfelder - farbige Umrandung */
        .cell.entry-red { border: 3px solid #e74c3c !important; background: linear-gradient(135deg, #ffe0dc, #f5d0cc) !important; }
        .cell.entry-blue { border: 3px solid #3498db !important; background: linear-gradient(135deg, #dceeff, #cce5ff) !important; }
        .cell.entry-green { border: 3px solid #27ae60 !important; background: linear-gradient(135deg, #dcffe0, #ccf5d0) !important; }
        .cell.entry-yellow { border: 3px solid #f1c40f !important; background: linear-gradient(135deg, #fffadc, #fff5cc) !important; }
        
        /* Home-Felder (Zielbahnen) */
        .cell.home-red {
            background: linear-gradient(135deg, #ffcccc, #e74c3c);
            border: 2px solid #c0392b;
        }
        .cell.home-blue {
            background: linear-gradient(135deg, #cce5ff, #3498db);
            border: 2px solid #2980b9;
        }
        .cell.home-green {
            background: linear-gradient(135deg, #ccffcc, #27ae60);
            border: 2px solid #1e8449;
        }
        .cell.home-yellow {
            background: linear-gradient(135deg, #ffffcc, #f1c40f);
            border: 2px solid #d4ac0d;
        }
        
        /* Mittelfeld (4-farbig) */
        .cell.center {
            width: 36px;
            height: 36px;
            background: conic-gradient(
                from 45deg,
                #e74c3c 0deg 90deg,
                #3498db 90deg 180deg,
                #27ae60 180deg 270deg,
                #f1c40f 270deg 360deg
            );
            border: 3px solid #333;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
            z-index: 2;
        }
        
        /* =========================================
           STARTFELDER (im Grid-System)
           ========================================= */
        .cell.start-field {
            background: rgba(255,255,255,0.3);
            border: 2px solid rgba(0,0,0,0.2);
        }
        
        .cell.start-red {
            background: linear-gradient(135deg, #ffcccc, #e74c3c);
            border: 2px solid #c0392b;
        }
        .cell.start-blue {
            background: linear-gradient(135deg, #cce5ff, #3498db);
            border: 2px solid #2980b9;
        }
        .cell.start-green {
            background: linear-gradient(135deg, #ccffcc, #27ae60);
            border: 2px solid #1e8449;
        }
        .cell.start-yellow {
            background: linear-gradient(135deg, #ffffcc, #f1c40f);
            border: 2px solid #d4ac0d;
        }
        
        /* =========================================
           SPIELFIGUREN
           ========================================= */
        .piece {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #333;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 3px 6px rgba(0,0,0,0.3);
            z-index: 10;
        }
        
        .piece.red { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .piece.blue { background: linear-gradient(135deg, #3498db, #2980b9); }
        .piece.green { background: linear-gradient(135deg, #27ae60, #1e8449); }
        .piece.yellow { background: linear-gradient(135deg, #f1c40f, #d4ac0d); }
        
        .piece.selectable {
            animation: pieceBounce 0.5s ease infinite;
            cursor: pointer;
        }
        
        .piece.selectable:hover {
            transform: scale(1.3);
            box-shadow: 0 0 15px rgba(67, 210, 64, 0.8);
        }
        
        @keyframes pieceBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
        
        /* Bewegbare Felder Animation */
        .cell.can-move {
            animation: fieldPulse 0.8s ease infinite;
        }
        
        @keyframes fieldPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(67, 210, 64, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(67, 210, 64, 0); }
        }

        /* =========================================
           SIDEBAR
           ========================================= */
        .turn-indicator {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: var(--mp-bg-medium);
        }
        .turn-indicator .label { font-size: 0.85rem; color: var(--mp-text-muted); }
        .turn-indicator .player { font-size: 1.3rem; font-weight: bold; margin-top: 5px; }
        .turn-indicator.my-turn { border: 2px solid var(--mp-accent); }
        
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
            transition: all 0.2s ease;
        }
        .dice:hover { transform: rotate(10deg) scale(1.05); }
        .dice.rolling { animation: mp-diceRoll 0.3s linear infinite; }
        
        .scoreboard { margin-top: 10px; }
        .mp-score-row .color { font-size: 1.2rem; }
        .mp-score-row .name { flex: 1; }
        .mp-score-row .pieces { font-size: 0.8rem; color: var(--mp-text-muted); }
        
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
        
        /* =========================================
           MOBILE RESPONSIVE
           ========================================= */
        @media (max-width: 500px) {
            .board-area {
                padding: 10px;
            }
            .board {
                width: 320px;
                height: 320px;
            }
            .cell {
                width: 24px;
                height: 24px;
            }
            .cell.center {
                width: 28px;
                height: 28px;
            }
            .start-area {
                width: 58px;
                height: 58px;
                padding: 6px;
                gap: 4px;
            }
            .start-field {
                width: 20px;
                height: 20px;
            }
            .piece {
                width: 18px;
                height: 18px;
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
            .mp-lobby-player-slot {
                padding: 10px;
                min-height: auto;
            }
        }
        
        @media (max-width: 380px) {
            .board {
                width: 280px;
                height: 280px;
            }
            .cell {
                width: 20px;
                height: 20px;
            }
            .cell.center {
                width: 24px;
                height: 24px;
            }
            .start-area {
                width: 50px;
                height: 50px;
            }
            .start-field {
                width: 18px;
                height: 18px;
            }
            .piece {
                width: 16px;
                height: 16px;
            }
        }
    </style>
    <script src="/assets/js/madn-pieces.js"></script>
</head>
<body class="mp-game-body">
    <div class="mp-game-container">
        <div class="mp-game-header">
            <div>
                <a href="multiplayer.php" class="mp-game-header__back">← Spiele-Hub</a>
                <h1 class="mp-header__title">🎲 <span>Mensch ärgere dich nicht</span></h1>
            </div>
            <span class="mp-header__user"><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>

        <!-- LOBBY -->
        <div id="lobbyScreen" class="mp-game-screen active">
            <div class="mp-game-lobby">
                <div style="font-size: 4rem; margin-bottom: 10px;">🎲</div>
                <h1 class="mp-lobby__title">Mensch ärgere dich nicht</h1>
                <p class="mp-lobby__subtitle">Das Klassiker-Brettspiel für 2-4 Spieler</p>

                <!-- Moduswahl -->
                <div class="mp-lobby-card" id="modeCard">
                    <h2>Spielmodus</h2>
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <button class="mp-game-btn" onclick="document.getElementById('modeCard').style.display='none'; document.getElementById('pvpCards').style.display='block';" style="flex: 1; min-width: 180px; display: flex; flex-direction: column; align-items: center; padding: 20px 15px;">
                            <div style="font-size: 2rem; margin-bottom: 8px;">👥</div>
                            <div style="font-weight: 600;">Gegen Spieler</div>
                            <div style="font-size: 0.8rem; color: var(--mp-primary); opacity: 0.8; margin-top: 4px;">2-4 Spieler online</div>
                        </button>
                        <a href="madn_vs_computer.php" class="mp-game-btn mp-game-btn--secondary" style="flex: 1; min-width: 180px; display: flex; flex-direction: column; align-items: center; padding: 20px 15px; text-decoration: none;">
                            <div style="font-size: 2rem; margin-bottom: 8px;">🤖</div>
                            <div style="font-weight: 600;">Gegen Computer</div>
                            <div style="font-size: 0.8rem; opacity: 0.7; margin-top: 4px;">KI mit 3 Stufen</div>
                        </a>
                    </div>
                </div>

                <div id="pvpCards" style="display: none;">
                <div class="mp-lobby-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2 class="mp-card__title">👤 Dein Name</h2>
                    <div class="mp-lobby-input-group">
                        <input type="text" id="playerNameInput" class="mp-input" placeholder="Name eingeben..." maxlength="20">
                    </div>
                    <button class="mp-game-btn mp-btn--full" onclick="setPlayerName()">Weiter →</button>
                </div>

                <div class="mp-lobby-card" id="createCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2 class="mp-card__title">🎮 Neues Spiel</h2>
                    <button class="mp-game-btn mp-btn--full" onclick="createGame()">Spiel erstellen</button>
                </div>

                <div class="mp-game-divider"><span>oder</span></div>

                <div class="mp-lobby-card" id="joinCard" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <h2 class="mp-card__title">🔗 Spiel beitreten</h2>
                    <div class="mp-lobby-input-group">
                        <input type="text" id="gameCodeInput" class="mp-lobby-code-input" placeholder="CODE" maxlength="6">
                    </div>
                    <button class="mp-game-btn mp-game-btn--secondary mp-btn--full" onclick="joinGame()">Beitreten →</button>
                </div>
                </div><!-- /pvpCards -->
            </div>
        </div>
        
        <!-- WAITING -->
        <div id="waitingScreen" class="mp-game-screen">
            <div class="mp-game-lobby">
                <div class="mp-lobby-code-display">
                    <p class="mp-text-muted" style="font-size: 0.9rem;">Spiel-Code</p>
                    <div class="mp-lobby-code mp-animate-pulse" id="displayCode">------</div>
                </div>

                <div class="mp-lobby-card">
                    <h2 class="mp-card__title">👥 Spieler</h2>
                    <div class="players-grid" id="playersGrid">
                        <div class="mp-lobby-player-slot red"><span class="mp-text-muted">🔴 Wartet...</span></div>
                        <div class="mp-lobby-player-slot blue"><span class="mp-text-muted">🔵 Wartet...</span></div>
                        <div class="mp-lobby-player-slot green"><span class="mp-text-muted">🟢 Wartet...</span></div>
                        <div class="mp-lobby-player-slot yellow"><span class="mp-text-muted">🟡 Wartet...</span></div>
                    </div>
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
                <div class="board-area">
                    <div class="board" id="gameBoard"></div>
                </div>
                <div class="mp-game-sidebar">
                    <div class="mp-info-card">
                        <div class="turn-indicator" id="turnIndicator">
                            <div class="label">Am Zug:</div>
                            <div class="player" id="currentPlayerName">---</div>
                        </div>
                    </div>
                    <div class="mp-info-card dice-area">
                        <h3>🎲 Würfel</h3>
                        <div class="dice" id="dice" onclick="rollDice()">?</div>
                        <p id="diceMsg" class="mp-text-muted" style="font-size: 0.9rem;">Klicke zum Würfeln</p>
                    </div>
                    <div class="mp-info-card">
                        <h3>📊 Spieler</h3>
                        <div class="scoreboard" id="scoreboard"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RESULT -->
        <div id="resultScreen" class="mp-game-screen">
            <div class="result-container mp-animate-pop">
                <div class="result-card">
                    <div class="result-icon">🏆</div>
                    <div class="result-title" id="winnerName">Gewinner!</div>
                    <p class="mp-text-muted" style="margin: 20px 0;">hat alle Figuren ins Ziel gebracht!</p>
                    <button class="mp-game-btn mp-btn--full" onclick="location.reload()">🔄 Neues Spiel</button>
                    <button class="mp-game-btn mp-game-btn--secondary mp-btn--full mp-mt-1" onclick="location.href='multiplayer.php'">← Zurück</button>
                </div>
            </div>
        </div>
    </div>

    
    <script>
        /**
         * ============================================
         * BUG-052 FIX: MADN JavaScript v2.0
         * Kreuz-Layout mit absoluter Positionierung
         * ============================================
         */
        
        const API_URL = '/api/madn.php';
        const POLL_INTERVAL = 800;
        const COLORS = ['red', 'blue', 'green', 'yellow'];
        const COLOR_EMOJIS = {'red': '🔴', 'blue': '🔵', 'green': '🟢', 'yellow': '🟡'};
        const COLOR_NAMES = {'red': 'Rot', 'blue': 'Blau', 'green': 'Grün', 'yellow': 'Gelb'};
        
        // ============================================
        // BOARD LAYOUT KONSTANTEN
        // ============================================
        
        // Feldgröße und Abstände (angepasst an CSS)
        const FIELD_SIZE = 32;
        const FIELD_GAP = 6;
        const BOARD_PADDING = 12;
        const CELL_STEP = FIELD_SIZE + FIELD_GAP; // 38px
        
        /**
         * Berechnet Pixel-Position aus Grid-Koordinaten
         */
        function getPixelPos(col, row) {
            return {
                left: BOARD_PADDING + col * CELL_STEP,
                top: BOARD_PADDING + row * CELL_STEP
            };
        }
        
        /**
         * HAUPTWEG: 40 Felder im Uhrzeigersinn
         * 
         * KORRIGIERTES Layout - echtes Kreuz ohne Ecken!
         * Der Weg geht NUR durch die Kreuzform:
         * - Horizontal: row 4,5,6 von col 0-10
         * - Vertikal: col 4,5,6 von row 0-10
         * - NICHT in die Ecken (dort sind Startbereiche)
         * 
         * Rot Entry = Position 0 (links, row 4)
         * Blau Entry = Position 10 (oben, col 6)
         * Grün Entry = Position 20 (rechts, row 6)
         * Gelb Entry = Position 30 (unten, col 4)
         */
        const MAIN_PATH = [
            // 0-9: Rot Entry → links hoch → oben nach rechts → Blau Entry
            {col: 0, row: 4},   // 0: ROT ENTRY
            {col: 1, row: 4},   // 1
            {col: 2, row: 4},   // 2
            {col: 3, row: 4},   // 3
            {col: 4, row: 4},   // 4: Kreuzung oben-links
            {col: 4, row: 3},   // 5: nach oben
            {col: 4, row: 2},   // 6
            {col: 4, row: 1},   // 7
            {col: 4, row: 0},   // 8: Ecke oben
            {col: 5, row: 0},   // 9: nach rechts
            
            // 10-19: Blau Entry → rechts → runter → Grün Entry
            {col: 6, row: 0},   // 10: BLAU ENTRY
            {col: 6, row: 1},   // 11: nach unten
            {col: 6, row: 2},   // 12
            {col: 6, row: 3},   // 13
            {col: 6, row: 4},   // 14: Kreuzung oben-rechts
            {col: 7, row: 4},   // 15: nach rechts
            {col: 8, row: 4},   // 16
            {col: 9, row: 4},   // 17
            {col: 10, row: 4},  // 18: Ecke rechts
            {col: 10, row: 5},  // 19: nach unten
            
            // 20-29: Grün Entry → rechts runter → links → Gelb Entry
            {col: 10, row: 6},  // 20: GRÜN ENTRY
            {col: 9, row: 6},   // 21: nach links
            {col: 8, row: 6},   // 22
            {col: 7, row: 6},   // 23
            {col: 6, row: 6},   // 24: Kreuzung unten-rechts
            {col: 6, row: 7},   // 25: nach unten
            {col: 6, row: 8},   // 26
            {col: 6, row: 9},   // 27
            {col: 6, row: 10},  // 28: Ecke unten
            {col: 5, row: 10},  // 29: nach links
            
            // 30-39: Gelb Entry → links → hoch → zurück zu Rot
            {col: 4, row: 10},  // 30: GELB ENTRY
            {col: 4, row: 9},   // 31: nach oben
            {col: 4, row: 8},   // 32
            {col: 4, row: 7},   // 33
            {col: 4, row: 6},   // 34: Kreuzung unten-links
            {col: 3, row: 6},   // 35: nach links
            {col: 2, row: 6},   // 36
            {col: 1, row: 6},   // 37
            {col: 0, row: 6},   // 38: Ecke links
            {col: 0, row: 5}    // 39: nach oben (vor Rot Entry)
        ];
        
        /**
         * HOME-FELDER: Zielbahnen (je 4 pro Farbe)
         * Position 40-43 im Spielzustand
         */
        const HOME_COORDS = {
            red: [
                {col: 1, row: 5}, {col: 2, row: 5}, {col: 3, row: 5}, {col: 4, row: 5}
            ],
            blue: [
                {col: 5, row: 1}, {col: 5, row: 2}, {col: 5, row: 3}, {col: 5, row: 4}
            ],
            green: [
                {col: 9, row: 5}, {col: 8, row: 5}, {col: 7, row: 5}, {col: 6, row: 5}
            ],
            yellow: [
                {col: 5, row: 9}, {col: 5, row: 8}, {col: 5, row: 7}, {col: 5, row: 6}
            ]
        };
        
        /**
         * MITTE: Zentrales Feld
         */
        const CENTER_COORD = {col: 5, row: 5};
        
        /**
         * ENTRY-POSITIONEN: Index im Hauptweg wo man aufs Brett kommt
         */
        const ENTRY_INDICES = {
            red: 0,
            blue: 10,
            green: 20,
            yellow: 30
        };
        
        // ============================================
        // SPIELZUSTAND
        // ============================================
        
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
        
        // ============================================
        // BOARD RENDERING
        // ============================================
        
        /**
         * START-KOORDINATEN: 4 Felder pro Farbe (2x2 in den Ecken)
         * Diese sind im selben Grid-System wie der Weg!
         */
        const START_COORDS = {
            red: [
                {col: 0, row: 0}, {col: 1, row: 0},
                {col: 0, row: 1}, {col: 1, row: 1}
            ],
            blue: [
                {col: 9, row: 0}, {col: 10, row: 0},
                {col: 9, row: 1}, {col: 10, row: 1}
            ],
            green: [
                {col: 9, row: 9}, {col: 10, row: 9},
                {col: 9, row: 10}, {col: 10, row: 10}
            ],
            yellow: [
                {col: 0, row: 9}, {col: 1, row: 9},
                {col: 0, row: 10}, {col: 1, row: 10}
            ]
        };
        
        /**
         * Initialisiert das Spielbrett mit Kreuz-Layout
         */
        function initBoard() {
            const board = document.getElementById('gameBoard');
            board.innerHTML = '';
            
            // 1. Startfelder erstellen (je 4 pro Farbe, im Grid-System)
            Object.entries(START_COORDS).forEach(([color, coords]) => {
                coords.forEach((coord, index) => {
                    const cell = document.createElement('div');
                    cell.className = `cell start-field start-${color}`;
                    cell.dataset.color = color;
                    cell.dataset.startIndex = index;
                    
                    const pos = getPixelPos(coord.col, coord.row);
                    cell.style.left = pos.left + 'px';
                    cell.style.top = pos.top + 'px';
                    
                    board.appendChild(cell);
                });
            });
            
            // 2. Hauptweg erstellen (40 Felder)
            MAIN_PATH.forEach((coord, index) => {
                const cell = document.createElement('div');
                cell.className = 'cell path';
                cell.dataset.pathIndex = index;
                
                // Entry-Felder markieren
                if (index === ENTRY_INDICES.red) cell.classList.add('entry-red');
                if (index === ENTRY_INDICES.blue) cell.classList.add('entry-blue');
                if (index === ENTRY_INDICES.green) cell.classList.add('entry-green');
                if (index === ENTRY_INDICES.yellow) cell.classList.add('entry-yellow');
                
                const pos = getPixelPos(coord.col, coord.row);
                cell.style.left = pos.left + 'px';
                cell.style.top = pos.top + 'px';
                
                board.appendChild(cell);
            });
            
            // 3. Home-Felder erstellen (Zielbahnen)
            Object.entries(HOME_COORDS).forEach(([color, coords]) => {
                coords.forEach((coord, index) => {
                    const cell = document.createElement('div');
                    cell.className = `cell home-${color}`;
                    cell.dataset.home = color;
                    cell.dataset.homeIndex = index;
                    
                    const pos = getPixelPos(coord.col, coord.row);
                    cell.style.left = pos.left + 'px';
                    cell.style.top = pos.top + 'px';
                    
                    board.appendChild(cell);
                });
            });
            
            // 4. Mittelfeld erstellen
            const center = document.createElement('div');
            center.className = 'cell center';
            const centerPos = getPixelPos(CENTER_COORD.col, CENTER_COORD.row);
            center.style.left = centerPos.left + 'px';
            center.style.top = centerPos.top + 'px';
            board.appendChild(center);
        }

        
        // ============================================
        // UI FUNKTIONEN
        // ============================================
        
        function showScreen(name) {
            document.querySelectorAll('.mp-game-screen').forEach(s => s.classList.remove('active'));
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
        
        // ============================================
        // API FUNKTIONEN
        // ============================================
        
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
        
        // ============================================
        // POLLING
        // ============================================
        
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
                    return `<div class="mp-lobby-player-slot ${color} filled">
                        <span class="avatar">${player.avatar}</span>
                        <span class="name">${escapeHtml(player.player_name)}</span>
                        <span class="color-badge">${COLOR_EMOJIS[color]} ${COLOR_NAMES[color]}</span>
                    </div>`;
                }
                return `<div class="mp-lobby-player-slot ${color}"><span style="color: var(--mp-text-muted);">${COLOR_EMOJIS[color]} Frei</span></div>`;
            }).join('');
        }

        
        // ============================================
        // SPIEL RENDERING
        // ============================================
        
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
                return `<div class="mp-score-row ${isActive ? 'active' : ''}">
                    <span class="color">${COLOR_EMOJIS[p.color]}</span>
                    <span class="name">${escapeHtml(p.player_name)}</span>
                    <span class="pieces">${finished}/4 🏠</span>
                </div>`;
            }).join('');
            
            // Figuren entfernen (nur Figuren, nicht das Board!)
            board.querySelectorAll('.piece').forEach(p => p.remove());
            
            // Figuren rendern
            data.players.forEach(player => {
                const color = player.color;
                const pieces = player.pieces;
                const isMyPiece = player.id == gameState.playerId;
                
                // DEBUG: Was sind die Positionen?
                console.log(`[DEBUG] ${color} pieces:`, pieces);
                
                pieces.forEach((pos, pieceIdx) => {
                    const piece = document.createElement('div');
                    piece.className = `piece ${color}`;
                    piece.dataset.playerId = player.id;
                    piece.dataset.pieceIndex = pieceIdx;

                    // SVG piece rendering
                    if (typeof MADN_PIECE_SVGS !== 'undefined' && MADN_PIECE_SVGS[color]) {
                        piece.style.background = `url('${MADN_PIECE_SVGS[color]}') center/contain no-repeat`;
                        piece.style.borderRadius = '0';
                        piece.style.border = 'none';
                        piece.style.boxShadow = 'none';
                    }
                    
                    let targetElement = null;
                    
                    if (pos < 0) {
                        // Im Startbereich (-1 bis -4)
                        const startIdx = Math.abs(pos) - 1;
                        // Neue Selector für Grid-basierte Startfelder
                        targetElement = board.querySelector(`.cell.start-${color}[data-start-index="${startIdx}"]`);
                        console.log(`[DEBUG] ${color} start pos=${pos}, startIdx=${startIdx}, found:`, !!targetElement);
                    } else if (pos >= 40) {
                        // Im Zielbereich (40-43)
                        const homeIdx = pos - 40;
                        targetElement = board.querySelector(`[data-home="${color}"][data-home-index="${homeIdx}"]`);
                    } else {
                        // Auf dem Hauptweg (0-39)
                        targetElement = board.querySelector(`[data-path-index="${pos}"]`);
                    }
                    
                    if (targetElement) {
                        // Figur klickbar machen wenn am Zug
                        if (isMyPiece && gameState.myTurn && gameState.canMove) {
                            piece.classList.add('selectable');
                            piece.onclick = (e) => {
                                e.stopPropagation();
                                selectPiece(pieceIdx);
                            };
                        }
                        
                        targetElement.appendChild(piece);
                    }
                });
            });
        }
        
        // ============================================
        // SPIELAKTIONEN
        // ============================================
        
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
        
        // ============================================
        // HILFSFUNKTIONEN
        // ============================================
        
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
        
        // Enter-Listener
        document.getElementById('playerNameInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') setPlayerName(); });
        document.getElementById('gameCodeInput')?.addEventListener('keypress', e => { if (e.key === 'Enter') joinGame(); });
    </script>
</body>
</html>
