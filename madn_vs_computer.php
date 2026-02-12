<?php
/**
 * ============================================================================
 * sgiT Education - Mensch aergere dich nicht vs Computer v1.0
 * ============================================================================
 *
 * Mensch aergere dich nicht gegen 1-3 KI-Gegner
 * Komplett client-seitig, kein Server-Roundtrip
 *
 * KI-Stufen:
 * 1 - Leicht:  Zufaelliger gueltiger Zug
 * 2 - Mittel:  Priorisiert Schlagen > Rauskommen > Richtung Ziel
 * 3 - Schwer:  Bewertet alle Optionen (Schlaggefahr, Blockaden, Zielnähe)
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
require_once 'includes/version.php';
require_once __DIR__ . '/wallet/SessionManager.php';

$userName = '';
$userAvatar = '😀';

if (SessionManager::isLoggedIn()) {
    $childData = SessionManager::getChild();
    if ($childData) {
        $userName = $childData['name'];
        $userAvatar = $childData['avatar'] ?? '😀';
    }
} elseif (isset($_SESSION['wallet_child_id'])) {
    $userName = $_SESSION['user_name'] ?? $_SESSION['child_name'] ?? '';
    $userAvatar = $_SESSION['avatar'] ?? '😀';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensch aergere dich nicht vs Computer - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <style>
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--mp-bg-dark) 0%, var(--mp-primary) 100%);
            min-height: 100vh; color: var(--mp-text); margin: 0; padding: 0;
        }
        .container { max-width: 1050px; margin: 0 auto; padding: 15px; }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 20px; background: var(--mp-bg-card); border-radius: 12px; margin-bottom: 20px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }
        .header h1 { font-size: 1.4rem; margin: 0; }
        .header h1 span { color: var(--mp-accent); }
        .back-link { color: var(--mp-accent); text-decoration: none; font-size: 0.9rem; }
        .screen { display: none; }
        .screen.active { display: block; }

        /* ========== SETUP ========== */
        .setup-container { max-width: 520px; margin: 30px auto; text-align: center; }
        .setup-card {
            background: var(--mp-bg-card); border-radius: 16px; padding: 25px; margin-bottom: 20px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }
        .setup-card h2 { color: var(--mp-accent); margin-bottom: 15px; font-size: 1.1rem; }
        .difficulty-grid { display: flex; flex-direction: column; gap: 8px; margin: 15px 0; }
        .diff-option {
            background: var(--mp-bg-dark); border: 2px solid transparent; border-radius: 12px;
            padding: 12px 15px; cursor: pointer; text-align: left; transition: all 0.2s;
        }
        .diff-option:hover { border-color: var(--mp-accent); }
        .diff-option.selected { border-color: var(--mp-accent); background: rgba(76,175,80,0.1); }
        .diff-option .diff-name { font-weight: 600; }
        .diff-option .diff-desc { font-size: 0.85rem; color: var(--mp-text-muted); margin-top: 2px; }
        .color-choice { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin: 15px 0; }
        .color-option {
            padding: 12px 18px; border-radius: 12px; cursor: pointer; border: 2px solid transparent;
            background: var(--mp-bg-dark); transition: all 0.2s; text-align: center; min-width: 70px;
        }
        .color-option:hover { border-color: var(--mp-accent); }
        .color-option.selected { border-color: var(--mp-accent); background: rgba(76,175,80,0.1); }
        .opponent-choice { display: flex; gap: 12px; justify-content: center; margin: 15px 0; }
        .opp-option {
            padding: 14px 22px; border-radius: 12px; cursor: pointer; border: 2px solid transparent;
            background: var(--mp-bg-dark); transition: all 0.2s; text-align: center; min-width: 60px;
        }
        .opp-option:hover { border-color: var(--mp-accent); }
        .opp-option.selected { border-color: var(--mp-accent); background: rgba(76,175,80,0.1); }
        .btn {
            background: var(--mp-accent); color: var(--mp-bg-dark); border: none;
            padding: 14px 28px; border-radius: 10px; font-size: 1rem; font-weight: 600;
            cursor: pointer; width: 100%; transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn.secondary { background: var(--mp-bg-card); color: var(--mp-text); border: 2px solid var(--mp-accent); }

        /* ========== GAME LAYOUT ========== */
        .game-container { display: grid; grid-template-columns: 1fr 260px; gap: 20px; }
        @media (max-width: 800px) { .game-container { grid-template-columns: 1fr; } }
        .board-area {
            background: var(--mp-bg-card); border-radius: 16px; padding: 20px;
            display: flex; justify-content: center; align-items: center;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }

        /* ========== BOARD ========== */
        .board {
            position: relative;
            width: 436px; height: 436px;
            background: #2d4a1c;
            border-radius: 16px; padding: 0;
            border: 4px solid #1a3503;
            box-shadow: inset 0 0 30px rgba(0,0,0,0.3), 0 8px 32px rgba(0,0,0,0.4);
        }
        .cell {
            position: absolute;
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            z-index: 1;
        }
        .cell.path {
            background: linear-gradient(135deg, #f5f5dc, #e8e8c8);
            border: 2px solid #666;
            box-shadow: inset 0 2px 4px rgba(255,255,255,0.5);
        }
        .cell.path:hover { transform: scale(1.1); box-shadow: 0 0 10px rgba(67,210,64,0.5); }
        .cell.entry-red { border: 3px solid #e74c3c !important; background: linear-gradient(135deg, #ffe0dc, #f5d0cc) !important; }
        .cell.entry-blue { border: 3px solid #3498db !important; background: linear-gradient(135deg, #dceeff, #cce5ff) !important; }
        .cell.entry-green { border: 3px solid #27ae60 !important; background: linear-gradient(135deg, #dcffe0, #ccf5d0) !important; }
        .cell.entry-yellow { border: 3px solid #f1c40f !important; background: linear-gradient(135deg, #fffadc, #fff5cc) !important; }
        .cell.home-red { background: linear-gradient(135deg, #ffcccc, #e74c3c); border: 2px solid #c0392b; }
        .cell.home-blue { background: linear-gradient(135deg, #cce5ff, #3498db); border: 2px solid #2980b9; }
        .cell.home-green { background: linear-gradient(135deg, #ccffcc, #27ae60); border: 2px solid #1e8449; }
        .cell.home-yellow { background: linear-gradient(135deg, #ffffcc, #f1c40f); border: 2px solid #d4ac0d; }
        .cell.center {
            width: 36px; height: 36px;
            background: conic-gradient(from 45deg, #e74c3c 0deg 90deg, #3498db 90deg 180deg, #27ae60 180deg 270deg, #f1c40f 270deg 360deg);
            border: 3px solid #333;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
            z-index: 2;
        }
        .cell.start-field { background: rgba(255,255,255,0.3); border: 2px solid rgba(0,0,0,0.2); }
        .cell.start-red { background: linear-gradient(135deg, #ffcccc, #e74c3c); border: 2px solid #c0392b; }
        .cell.start-blue { background: linear-gradient(135deg, #cce5ff, #3498db); border: 2px solid #2980b9; }
        .cell.start-green { background: linear-gradient(135deg, #ccffcc, #27ae60); border: 2px solid #1e8449; }
        .cell.start-yellow { background: linear-gradient(135deg, #ffffcc, #f1c40f); border: 2px solid #d4ac0d; }

        /* ========== PIECES ========== */
        .piece {
            width: 24px; height: 24px;
            border-radius: 50%;
            border: 2px solid #333;
            cursor: default;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 3px 6px rgba(0,0,0,0.3);
            z-index: 10;
            position: relative;
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
            box-shadow: 0 0 15px rgba(67,210,64,0.8);
        }
        @keyframes pieceBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        /* ========== SIDEBAR ========== */
        .sidebar { display: flex; flex-direction: column; gap: 15px; }
        .info-card {
            background: var(--mp-bg-card); border-radius: 12px; padding: 15px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }
        .info-card h3 { color: var(--mp-accent); margin-bottom: 10px; font-size: 1rem; }
        .turn-info { text-align: center; padding: 15px; background: var(--mp-bg-dark); border-radius: 10px; }
        .turn-info.my-turn { border: 2px solid var(--mp-accent); }
        .turn-info .label { font-size: 0.85rem; color: var(--mp-text-muted); }
        .turn-info .name { font-size: 1.2rem; font-weight: bold; margin-top: 5px; }
        .thinking { color: var(--mp-accent); font-style: italic; }
        .thinking::after { content: '...'; animation: dots 1.5s infinite; }
        @keyframes dots { 0%{content:'.'} 33%{content:'..'} 66%{content:'...'} }

        /* Dice */
        .dice-area { text-align: center; padding: 20px 15px; }
        .dice {
            width: 80px; height: 80px;
            background: white; border-radius: 12px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 2.5rem; font-weight: bold; color: #333;
            margin: 10px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            cursor: pointer; transition: all 0.2s ease;
            user-select: none;
        }
        .dice:hover { transform: rotate(10deg) scale(1.05); }
        .dice.rolling { animation: diceRoll 0.15s linear infinite; }
        .dice.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
        @keyframes diceRoll {
            0% { transform: rotate(0deg) scale(1); }
            25% { transform: rotate(90deg) scale(0.9); }
            50% { transform: rotate(180deg) scale(1); }
            75% { transform: rotate(270deg) scale(0.9); }
            100% { transform: rotate(360deg) scale(1); }
        }

        .score-row {
            display: flex; align-items: center; gap: 10px;
            padding: 8px; background: var(--mp-bg-dark); border-radius: 8px; margin-bottom: 6px;
        }
        .score-row.active { border: 2px solid var(--mp-accent); }
        .score-row .color-dot {
            width: 16px; height: 16px; border-radius: 50%; border: 2px solid #333; flex-shrink: 0;
        }
        .score-row .color-dot.red { background: #e74c3c; }
        .score-row .color-dot.blue { background: #3498db; }
        .score-row .color-dot.green { background: #27ae60; }
        .score-row .color-dot.yellow { background: #f1c40f; }
        .score-row .sname { flex: 1; font-size: 0.9rem; }
        .score-row .pieces-info { font-size: 0.8rem; color: var(--mp-text-muted); }

        /* Log */
        .game-log {
            max-height: 120px; overflow-y: auto; font-size: 0.8rem; color: var(--mp-text-muted);
            padding: 8px; background: var(--mp-bg-dark); border-radius: 8px;
        }
        .game-log p { margin: 3px 0; }

        /* Toast */
        .toast {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            padding: 15px 25px; border-radius: 12px; font-weight: 600; z-index: 1000;
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateX(-50%) translateY(20px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        .toast.success { background: var(--mp-accent); color: var(--mp-bg-dark); }
        .toast.error { background: var(--mp-error); color: white; }
        .toast.info { background: var(--mp-bg-card); border: 2px solid var(--mp-accent); color: var(--mp-text); }

        /* Result */
        .result-card {
            background: var(--mp-bg-card); border-radius: 16px; padding: 30px; text-align: center;
            border: 3px solid var(--mp-accent); max-width: 500px; margin: 50px auto;
        }

        /* ========== MOBILE ========== */
        @media (max-width: 500px) {
            .board { width: 320px; height: 320px; }
            .cell { width: 24px; height: 24px; }
            .cell.center { width: 28px; height: 28px; }
            .piece { width: 18px; height: 18px; }
            .dice { width: 60px; height: 60px; font-size: 2rem; }
        }
        @media (max-width: 380px) {
            .board { width: 280px; height: 280px; }
            .cell { width: 20px; height: 20px; }
            .cell.center { width: 24px; height: 24px; }
            .piece { width: 16px; height: 16px; }
        }
    </style>
    <script src="/assets/js/madn-pieces.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="madn.php" class="back-link">← Mensch aergere dich nicht</a>
                <h1>&#x1F3B2; <span>MADN</span> vs Computer</h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>

        <!-- SETUP SCREEN -->
        <div id="setupScreen" class="screen active">
            <div class="setup-container">
                <div style="font-size: 4rem; margin-bottom: 10px;">&#x1F3B2;</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Mensch aergere dich nicht</h1>
                <p style="color: var(--mp-text-muted); margin-bottom: 25px;">Spiele gegen 1-3 KI-Gegner</p>

                <div class="setup-card">
                    <h2>&#x1F3A8; Deine Farbe</h2>
                    <div class="color-choice" id="colorChoice">
                        <div class="color-option selected" data-color="red" onclick="selectColor('red', this)">
                            <div style="font-size: 2rem;">&#x1F534;</div>
                            <div>Rot</div>
                        </div>
                        <div class="color-option" data-color="blue" onclick="selectColor('blue', this)">
                            <div style="font-size: 2rem;">&#x1F535;</div>
                            <div>Blau</div>
                        </div>
                        <div class="color-option" data-color="green" onclick="selectColor('green', this)">
                            <div style="font-size: 2rem;">&#x1F7E2;</div>
                            <div>Gruen</div>
                        </div>
                        <div class="color-option" data-color="yellow" onclick="selectColor('yellow', this)">
                            <div style="font-size: 2rem;">&#x1F7E1;</div>
                            <div>Gelb</div>
                        </div>
                    </div>
                </div>

                <div class="setup-card">
                    <h2>&#x1F916; Anzahl KI-Gegner</h2>
                    <div class="opponent-choice" id="oppChoice">
                        <div class="opp-option selected" data-count="1" onclick="selectOpponents(1, this)">
                            <div style="font-size: 1.5rem;">1</div>
                            <div style="font-size: 0.8rem;">Gegner</div>
                        </div>
                        <div class="opp-option" data-count="2" onclick="selectOpponents(2, this)">
                            <div style="font-size: 1.5rem;">2</div>
                            <div style="font-size: 0.8rem;">Gegner</div>
                        </div>
                        <div class="opp-option" data-count="3" onclick="selectOpponents(3, this)">
                            <div style="font-size: 1.5rem;">3</div>
                            <div style="font-size: 0.8rem;">Gegner</div>
                        </div>
                    </div>
                </div>

                <div class="setup-card">
                    <h2>&#x1F3AF; Schwierigkeit</h2>
                    <div class="difficulty-grid" id="diffGrid">
                        <div class="diff-option selected" onclick="selectDifficulty(1, this)">
                            <div class="diff-name">&#x1F60A; Level 1 - Leicht</div>
                            <div class="diff-desc">Zufaelliger gueltiger Zug</div>
                        </div>
                        <div class="diff-option" onclick="selectDifficulty(2, this)">
                            <div class="diff-name">&#x1F914; Level 2 - Mittel</div>
                            <div class="diff-desc">Schlagen > Rauskommen > Richtung Ziel</div>
                        </div>
                        <div class="diff-option" onclick="selectDifficulty(3, this)">
                            <div class="diff-name">&#x1F608; Level 3 - Schwer</div>
                            <div class="diff-desc">Bewertet Schlaggefahr, Blockaden, Zielnaehe</div>
                        </div>
                    </div>
                </div>

                <button class="btn" onclick="startGame()">Spiel starten</button>
                <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='madn.php'">← Zurueck</button>
            </div>
        </div>

        <!-- GAME SCREEN -->
        <div id="gameScreen" class="screen">
            <div class="game-container">
                <div class="board-area">
                    <div class="board" id="gameBoard"></div>
                </div>
                <div class="sidebar">
                    <div class="info-card">
                        <div class="turn-info" id="turnInfo">
                            <div class="label">Am Zug:</div>
                            <div class="name" id="currentPlayerName">---</div>
                        </div>
                    </div>
                    <div class="info-card dice-area">
                        <h3>&#x1F3B2; Wuerfel</h3>
                        <div class="dice disabled" id="dice" onclick="handleDiceClick()">?</div>
                        <p id="diceMsg" style="font-size: 0.9rem; color: var(--mp-text-muted);">Warte...</p>
                    </div>
                    <div class="info-card">
                        <h3>&#x1F4CA; Spieler</h3>
                        <div id="scoreboard"></div>
                    </div>
                    <div class="info-card">
                        <h3>&#x1F4DC; Verlauf</h3>
                        <div class="game-log" id="gameLog"></div>
                    </div>
                    <button class="btn secondary" onclick="restartGame()">&#x1F504; Neues Spiel</button>
                </div>
            </div>
        </div>

        <!-- RESULT SCREEN -->
        <div id="resultScreen" class="screen">
            <div class="setup-container">
                <div class="result-card">
                    <div style="font-size: 5rem;" id="resultEmoji">&#x1F3C6;</div>
                    <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                    <p style="color: var(--mp-text-muted); margin-bottom: 20px;">hat alle Figuren ins Ziel gebracht!</p>
                    <button class="btn" onclick="startGame()">&#x1F504; Nochmal spielen</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="showScreen('setup')">&#x2699;&#xFE0F; Einstellungen</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='madn.php'">← Zurueck</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (() => {
        // ============================================================
        // CONSTANTS
        // ============================================================
        const COLORS = ['red', 'blue', 'green', 'yellow'];
        const COLOR_NAMES = { red: 'Rot', blue: 'Blau', green: 'Gruen', yellow: 'Gelb' };
        const COLOR_EMOJIS = { red: '\u{1F534}', blue: '\u{1F535}', green: '\u{1F7E2}', yellow: '\u{1F7E1}' };
        const START_FIELDS = { red: 0, blue: 10, green: 20, yellow: 30 };
        const BOARD_SIZE = 40;

        // Board layout constants (matches madn.php exactly)
        const FIELD_SIZE = 32;
        const FIELD_GAP = 6;
        const BOARD_PADDING = 12;
        const CELL_STEP = FIELD_SIZE + FIELD_GAP; // 38px

        // 40 path fields clockwise (grid coordinates col, row)
        const MAIN_PATH = [
            {col:0,row:4},{col:1,row:4},{col:2,row:4},{col:3,row:4},{col:4,row:4},
            {col:4,row:3},{col:4,row:2},{col:4,row:1},{col:4,row:0},{col:5,row:0},
            {col:6,row:0},{col:6,row:1},{col:6,row:2},{col:6,row:3},{col:6,row:4},
            {col:7,row:4},{col:8,row:4},{col:9,row:4},{col:10,row:4},{col:10,row:5},
            {col:10,row:6},{col:9,row:6},{col:8,row:6},{col:7,row:6},{col:6,row:6},
            {col:6,row:7},{col:6,row:8},{col:6,row:9},{col:6,row:10},{col:5,row:10},
            {col:4,row:10},{col:4,row:9},{col:4,row:8},{col:4,row:7},{col:4,row:6},
            {col:3,row:6},{col:2,row:6},{col:1,row:6},{col:0,row:6},{col:0,row:5}
        ];

        // Home lanes (goal fields, 4 per color)
        const HOME_COORDS = {
            red:    [{col:1,row:5},{col:2,row:5},{col:3,row:5},{col:4,row:5}],
            blue:   [{col:5,row:1},{col:5,row:2},{col:5,row:3},{col:5,row:4}],
            green:  [{col:9,row:5},{col:8,row:5},{col:7,row:5},{col:6,row:5}],
            yellow: [{col:5,row:9},{col:5,row:8},{col:5,row:7},{col:5,row:6}]
        };

        // Start areas (2x2 in corners)
        const START_COORDS = {
            red:    [{col:0,row:0},{col:1,row:0},{col:0,row:1},{col:1,row:1}],
            blue:   [{col:9,row:0},{col:10,row:0},{col:9,row:1},{col:10,row:1}],
            green:  [{col:9,row:9},{col:10,row:9},{col:9,row:10},{col:10,row:10}],
            yellow: [{col:0,row:9},{col:1,row:9},{col:0,row:10},{col:1,row:10}]
        };

        const CENTER_COORD = {col:5, row:5};
        const ENTRY_INDICES = { red: 0, blue: 10, green: 20, yellow: 30 };

        // ============================================================
        // GAME STATE
        // ============================================================
        let settings = {
            playerColor: 'red',
            numOpponents: 1,
            difficulty: 1
        };

        let players = [];       // Array of { color, name, isHuman, pieces: [p0,p1,p2,p3], playerOrder }
        let currentPlayerIdx = 0;
        let currentRoll = 0;
        let gamePhase = 'setup'; // setup, rolling, moving, ai_turn, finished
        let consecutiveSixes = 0;
        let noMoveStreak = {};   // track how many turns with no 6 for stuck players
        let logMessages = [];
        let playerName = '<?php echo addslashes($userName ?: "Du"); ?>';
        let gameActive = false;
        let aiTimers = [];

        // ============================================================
        // SETUP HANDLERS
        // ============================================================
        window.selectColor = function(color, el) {
            settings.playerColor = color;
            document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
        };

        window.selectOpponents = function(count, el) {
            settings.numOpponents = count;
            document.querySelectorAll('.opp-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
        };

        window.selectDifficulty = function(level, el) {
            settings.difficulty = level;
            document.querySelectorAll('.diff-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
        };

        window.showScreen = function(name) {
            document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
            document.getElementById(name + 'Screen').classList.add('active');
        };

        // ============================================================
        // GAME INITIALIZATION
        // ============================================================
        window.startGame = function() {
            // Clear any pending AI timers
            aiTimers.forEach(t => clearTimeout(t));
            aiTimers = [];

            // Build player list based on settings
            const playerOrder = buildPlayerOrder(settings.playerColor, settings.numOpponents);
            players = playerOrder.map((color, idx) => ({
                color: color,
                name: color === settings.playerColor ? playerName : 'KI ' + COLOR_NAMES[color],
                isHuman: color === settings.playerColor,
                pieces: [-1, -2, -3, -4],
                playerOrder: COLORS.indexOf(color)
            }));

            currentPlayerIdx = 0;
            currentRoll = 0;
            consecutiveSixes = 0;
            noMoveStreak = {};
            logMessages = [];
            gameActive = true;
            gamePhase = 'rolling';

            showScreen('game');
            initBoard();
            renderPieces();
            updateSidebar();
            addLog(COLOR_EMOJIS[players[0].color] + ' ' + players[0].name + ' beginnt!');

            if (!players[0].isHuman) {
                gamePhase = 'ai_turn';
                scheduleAiTurn();
            } else {
                enableDice();
            }
        };

        window.restartGame = function() {
            showScreen('setup');
        };

        /**
         * Build the player order: human first, then AI opponents in board order
         */
        function buildPlayerOrder(humanColor, numOpp) {
            const humanIdx = COLORS.indexOf(humanColor);
            const order = [humanColor];
            let next = (humanIdx + 1) % 4;
            for (let i = 0; i < numOpp; i++) {
                order.push(COLORS[next]);
                next = (next + 1) % 4;
                if (next === humanIdx) next = (next + 1) % 4;
            }
            // Sort by board order so movement is clockwise
            order.sort((a, b) => COLORS.indexOf(a) - COLORS.indexOf(b));
            // Find human index in sorted order and rotate so human is first? No -
            // we keep board order but start with the lowest playerOrder
            return order;
        }

        // ============================================================
        // BOARD RENDERING
        // ============================================================
        function getPixelPos(col, row) {
            return {
                left: BOARD_PADDING + col * CELL_STEP,
                top: BOARD_PADDING + row * CELL_STEP
            };
        }

        function initBoard() {
            const board = document.getElementById('gameBoard');
            board.innerHTML = '';

            // Start fields (4 per color, only active colors)
            const activeColors = players.map(p => p.color);
            COLORS.forEach(color => {
                START_COORDS[color].forEach((coord, index) => {
                    const cell = document.createElement('div');
                    const isActive = activeColors.includes(color);
                    cell.className = 'cell start-field start-' + color;
                    if (!isActive) cell.style.opacity = '0.3';
                    cell.dataset.type = 'start';
                    cell.dataset.color = color;
                    cell.dataset.startIndex = index;
                    const pos = getPixelPos(coord.col, coord.row);
                    cell.style.left = pos.left + 'px';
                    cell.style.top = pos.top + 'px';
                    board.appendChild(cell);
                });
            });

            // Main path (40 fields)
            MAIN_PATH.forEach((coord, index) => {
                const cell = document.createElement('div');
                cell.className = 'cell path';
                cell.dataset.type = 'path';
                cell.dataset.pathIndex = index;
                if (index === ENTRY_INDICES.red) cell.classList.add('entry-red');
                if (index === ENTRY_INDICES.blue) cell.classList.add('entry-blue');
                if (index === ENTRY_INDICES.green) cell.classList.add('entry-green');
                if (index === ENTRY_INDICES.yellow) cell.classList.add('entry-yellow');
                const pos = getPixelPos(coord.col, coord.row);
                cell.style.left = pos.left + 'px';
                cell.style.top = pos.top + 'px';
                board.appendChild(cell);
            });

            // Home lanes (4 per color, only active)
            COLORS.forEach(color => {
                const isActive = activeColors.includes(color);
                HOME_COORDS[color].forEach((coord, index) => {
                    const cell = document.createElement('div');
                    cell.className = 'cell home-' + color;
                    if (!isActive) cell.style.opacity = '0.3';
                    cell.dataset.type = 'home';
                    cell.dataset.home = color;
                    cell.dataset.homeIndex = index;
                    const pos = getPixelPos(coord.col, coord.row);
                    cell.style.left = pos.left + 'px';
                    cell.style.top = pos.top + 'px';
                    board.appendChild(cell);
                });
            });

            // Center field
            const center = document.createElement('div');
            center.className = 'cell center';
            const cp = getPixelPos(CENTER_COORD.col, CENTER_COORD.row);
            center.style.left = cp.left + 'px';
            center.style.top = cp.top + 'px';
            board.appendChild(center);
        }

        function renderPieces() {
            const board = document.getElementById('gameBoard');
            board.querySelectorAll('.piece').forEach(p => p.remove());

            const currentPlayer = players[currentPlayerIdx];
            const isHumanTurn = currentPlayer && currentPlayer.isHuman;
            const validMoves = (gamePhase === 'moving' && isHumanTurn) ?
                getValidMoves(currentPlayer, currentRoll) : [];

            players.forEach((player) => {
                player.pieces.forEach((pos, pieceIdx) => {
                    const piece = document.createElement('div');
                    piece.className = 'piece ' + player.color;

                    // SVG piece rendering
                    if (typeof MADN_PIECE_SVGS !== 'undefined' && MADN_PIECE_SVGS[player.color]) {
                        piece.style.background = `url('${MADN_PIECE_SVGS[player.color]}') center/contain no-repeat`;
                        piece.style.borderRadius = '0';
                        piece.style.border = 'none';
                        piece.style.boxShadow = 'none';
                    }

                    let targetCell = null;

                    if (pos < 0) {
                        // In start area
                        const startIdx = Math.abs(pos) - 1;
                        targetCell = board.querySelector(
                            '.cell.start-' + player.color + '[data-start-index="' + startIdx + '"]'
                        );
                    } else if (pos >= 40) {
                        // In home lane
                        const homeIdx = pos - 40;
                        targetCell = board.querySelector(
                            '[data-home="' + player.color + '"][data-home-index="' + homeIdx + '"]'
                        );
                    } else {
                        // On main path
                        targetCell = board.querySelector('[data-path-index="' + pos + '"]');
                    }

                    // Check if this piece can move (for human player)
                    if (isHumanTurn && gamePhase === 'moving') {
                        const canMove = validMoves.some(m => m.pieceIndex === pieceIdx);
                        if (canMove) {
                            piece.classList.add('selectable');
                            piece.onclick = (e) => {
                                e.stopPropagation();
                                humanMovePiece(pieceIdx);
                            };
                        }
                    }

                    if (targetCell) {
                        targetCell.appendChild(piece);
                    }
                });
            });
        }

        // ============================================================
        // GAME LOGIC (matches api/madn.php)
        // ============================================================

        /**
         * Calculate new position for a piece.
         * Returns the new position (0-43) or false if invalid.
         */
        function calculateNewPosition(currentPos, roll, playerOrder) {
            const startField = playerOrder * 10;
            const relPos = ((currentPos - startField) + 400) % 40; // +400 to avoid negative modulo
            const newRelPos = relPos + roll;

            // Reached home area?
            if (newRelPos >= 40 && newRelPos <= 43) {
                return 40 + (newRelPos - 40);
            }

            // Overshot home = invalid
            if (newRelPos > 43) {
                return false;
            }

            // Normal movement on the board
            return (startField + newRelPos) % 40;
        }

        /**
         * Get all valid moves for a player given a dice roll.
         * Returns array of { pieceIndex, from, to, isCapture, capturedPlayer, capturedPiece }
         */
        function getValidMoves(player, roll) {
            const moves = [];
            const startField = START_FIELDS[player.color];
            const po = player.playerOrder;

            player.pieces.forEach((pos, idx) => {
                // Piece in start area, rolled 6
                if (pos < 0 && roll === 6) {
                    if (!player.pieces.includes(startField)) {
                        const capture = findCapture(player, startField);
                        moves.push({
                            pieceIndex: idx,
                            from: pos,
                            to: startField,
                            isCapture: capture !== null,
                            capturedPlayer: capture ? capture.player : null,
                            capturedPiece: capture ? capture.pieceIdx : null
                        });
                    }
                }
                // Piece on the board
                else if (pos >= 0 && pos < 40) {
                    const newPos = calculateNewPosition(pos, roll, po);
                    if (newPos !== false && !player.pieces.includes(newPos)) {
                        const capture = (newPos >= 0 && newPos < 40) ? findCapture(player, newPos) : null;
                        moves.push({
                            pieceIndex: idx,
                            from: pos,
                            to: newPos,
                            isCapture: capture !== null,
                            capturedPlayer: capture ? capture.player : null,
                            capturedPiece: capture ? capture.pieceIdx : null
                        });
                    }
                }
                // Piece in home area, can move further
                else if (pos >= 40 && pos < 44) {
                    const newPos = pos + roll;
                    if (newPos <= 43 && !player.pieces.includes(newPos)) {
                        moves.push({
                            pieceIndex: idx,
                            from: pos,
                            to: newPos,
                            isCapture: false,
                            capturedPlayer: null,
                            capturedPiece: null
                        });
                    }
                }
            });

            return moves;
        }

        /**
         * Check if any opponent piece is on the given field position.
         */
        function findCapture(movingPlayer, targetPos) {
            for (const other of players) {
                if (other.color === movingPlayer.color) continue;
                for (let i = 0; i < other.pieces.length; i++) {
                    if (other.pieces[i] === targetPos && targetPos >= 0 && targetPos < 40) {
                        return { player: other, pieceIdx: i };
                    }
                }
            }
            return null;
        }

        /**
         * Execute a move. Returns true if the player gets another turn (rolled 6).
         */
        function executeMove(player, move) {
            // Capture
            if (move.isCapture && move.capturedPlayer && move.capturedPiece !== null) {
                const cp = move.capturedPlayer;
                const ci = move.capturedPiece;
                cp.pieces[ci] = -(ci + 1); // Back to start area
                addLog(COLOR_EMOJIS[player.color] + ' ' + player.name +
                    ' schlaegt ' + COLOR_EMOJIS[cp.color] + ' ' + cp.name + '!');
            }

            // Move piece
            player.pieces[move.pieceIndex] = move.to;

            // Check for win
            if (checkWinner(player)) {
                gamePhase = 'finished';
                gameActive = false;
                renderPieces();
                updateSidebar();
                endGame(player);
                return false;
            }

            return currentRoll === 6; // Another turn if rolled 6
        }

        function checkWinner(player) {
            return player.pieces.every(p => p >= 40);
        }

        // ============================================================
        // DICE HANDLING
        // ============================================================
        function enableDice() {
            gamePhase = 'rolling';
            const dice = document.getElementById('dice');
            dice.classList.remove('disabled');
            document.getElementById('diceMsg').textContent = 'Klicke zum Wuerfeln!';
            updateSidebar();
            renderPieces();
        }

        function disableDice() {
            const dice = document.getElementById('dice');
            dice.classList.add('disabled');
        }

        window.handleDiceClick = function() {
            if (gamePhase !== 'rolling') return;
            const currentPlayer = players[currentPlayerIdx];
            if (!currentPlayer.isHuman) return;

            gamePhase = 'animating';
            disableDice();
            animateDiceRoll(() => {
                const roll = rollDice();
                currentRoll = roll;
                document.getElementById('dice').textContent = roll;

                const validMoves = getValidMoves(currentPlayer, roll);

                if (validMoves.length === 0) {
                    addLog(COLOR_EMOJIS[currentPlayer.color] + ' ' + currentPlayer.name +
                        ' wuerfelt ' + roll + ' - kein Zug moeglich');

                    if (roll === 6) {
                        // Rolled 6 but no moves: allow re-roll
                        consecutiveSixes++;
                        if (consecutiveSixes >= 3) {
                            addLog(COLOR_EMOJIS[currentPlayer.color] + ' 3x Sechs! Naechster Spieler.');
                            consecutiveSixes = 0;
                            nextTurn();
                        } else {
                            document.getElementById('diceMsg').textContent = 'Nochmal wuerfeln (6)!';
                            enableDice();
                        }
                    } else {
                        consecutiveSixes = 0;
                        showToast('Kein Zug moeglich', 'info');
                        setTimeout(() => nextTurn(), 800);
                    }
                } else if (validMoves.length === 1) {
                    // Only one valid move - auto-execute
                    addLog(COLOR_EMOJIS[currentPlayer.color] + ' ' + currentPlayer.name +
                        ' wuerfelt ' + roll);
                    gamePhase = 'moving';
                    setTimeout(() => humanMovePiece(validMoves[0].pieceIndex), 300);
                } else {
                    addLog(COLOR_EMOJIS[currentPlayer.color] + ' ' + currentPlayer.name +
                        ' wuerfelt ' + roll);
                    gamePhase = 'moving';
                    document.getElementById('diceMsg').textContent = 'Waehle eine Figur!';
                    renderPieces();
                }
            });
        };

        function rollDice() {
            return Math.floor(Math.random() * 6) + 1;
        }

        function animateDiceRoll(callback) {
            const dice = document.getElementById('dice');
            dice.classList.add('rolling');
            let count = 0;
            const interval = setInterval(() => {
                dice.textContent = Math.floor(Math.random() * 6) + 1;
                count++;
                if (count >= 8) {
                    clearInterval(interval);
                    dice.classList.remove('rolling');
                    callback();
                }
            }, 80);
        }

        // ============================================================
        // HUMAN MOVE
        // ============================================================
        function humanMovePiece(pieceIndex) {
            if (gamePhase !== 'moving') return;
            const player = players[currentPlayerIdx];
            if (!player.isHuman) return;

            const validMoves = getValidMoves(player, currentRoll);
            const move = validMoves.find(m => m.pieceIndex === pieceIndex);
            if (!move) {
                showToast('Ungueltiger Zug!', 'error');
                return;
            }

            const anotherTurn = executeMove(player, move);
            if (gamePhase === 'finished') return;

            renderPieces();
            updateSidebar();

            if (anotherTurn) {
                consecutiveSixes++;
                if (consecutiveSixes >= 3) {
                    addLog(COLOR_EMOJIS[player.color] + ' 3x Sechs hintereinander! Naechster Spieler.');
                    consecutiveSixes = 0;
                    setTimeout(() => nextTurn(), 600);
                } else {
                    addLog(COLOR_EMOJIS[player.color] + ' Sechs! Nochmal wuerfeln!');
                    document.getElementById('diceMsg').textContent = 'Nochmal wuerfeln (6)!';
                    enableDice();
                }
            } else {
                consecutiveSixes = 0;
                setTimeout(() => nextTurn(), 400);
            }
        }

        // ============================================================
        // TURN MANAGEMENT
        // ============================================================
        function nextTurn() {
            if (!gameActive) return;
            currentPlayerIdx = (currentPlayerIdx + 1) % players.length;
            currentRoll = 0;
            consecutiveSixes = 0;

            const player = players[currentPlayerIdx];
            document.getElementById('dice').textContent = '?';

            renderPieces();
            updateSidebar();

            if (player.isHuman) {
                enableDice();
            } else {
                gamePhase = 'ai_turn';
                disableDice();
                document.getElementById('diceMsg').textContent = COLOR_NAMES[player.color] + ' ist dran...';
                scheduleAiTurn();
            }
        }

        // ============================================================
        // AI LOGIC
        // ============================================================
        function scheduleAiTurn() {
            if (!gameActive) return;
            const delay = 500 + Math.random() * 1000;
            const t = setTimeout(() => doAiTurn(), delay);
            aiTimers.push(t);
        }

        function doAiTurn() {
            if (!gameActive || gamePhase === 'finished') return;

            const player = players[currentPlayerIdx];
            if (!player || player.isHuman) return;

            // Roll dice
            const roll = rollDice();
            currentRoll = roll;

            // Animate dice display
            const dice = document.getElementById('dice');
            dice.classList.add('rolling');
            let animCount = 0;
            const animInterval = setInterval(() => {
                dice.textContent = Math.floor(Math.random() * 6) + 1;
                animCount++;
                if (animCount >= 6) {
                    clearInterval(animInterval);
                    dice.classList.remove('rolling');
                    dice.textContent = roll;
                    processAiRoll(player, roll);
                }
            }, 80);
        }

        function processAiRoll(player, roll) {
            if (!gameActive || gamePhase === 'finished') return;

            const validMoves = getValidMoves(player, roll);

            if (validMoves.length === 0) {
                addLog(COLOR_EMOJIS[player.color] + ' ' + player.name +
                    ' wuerfelt ' + roll + ' - kein Zug');

                if (roll === 6) {
                    consecutiveSixes++;
                    if (consecutiveSixes >= 3) {
                        addLog(COLOR_EMOJIS[player.color] + ' 3x Sechs! Pech.');
                        consecutiveSixes = 0;
                        setTimeout(() => nextTurn(), 600);
                    } else {
                        // Re-roll on 6
                        const t = setTimeout(() => doAiTurn(), 400 + Math.random() * 400);
                        aiTimers.push(t);
                    }
                } else {
                    consecutiveSixes = 0;
                    setTimeout(() => nextTurn(), 500);
                }
                return;
            }

            // Pick move based on difficulty
            let chosenMove;
            switch (settings.difficulty) {
                case 1: chosenMove = aiLevel1(validMoves); break;
                case 2: chosenMove = aiLevel2(player, validMoves, roll); break;
                case 3: chosenMove = aiLevel3(player, validMoves, roll); break;
                default: chosenMove = aiLevel1(validMoves);
            }

            addLog(COLOR_EMOJIS[player.color] + ' ' + player.name + ' wuerfelt ' + roll);

            const anotherTurn = executeMove(player, chosenMove);
            if (gamePhase === 'finished') return;

            renderPieces();
            updateSidebar();

            if (anotherTurn) {
                consecutiveSixes++;
                if (consecutiveSixes >= 3) {
                    addLog(COLOR_EMOJIS[player.color] + ' 3x Sechs! Naechster.');
                    consecutiveSixes = 0;
                    setTimeout(() => nextTurn(), 600);
                } else {
                    addLog(COLOR_EMOJIS[player.color] + ' Sechs! Nochmal!');
                    const t = setTimeout(() => doAiTurn(), 600 + Math.random() * 800);
                    aiTimers.push(t);
                }
            } else {
                consecutiveSixes = 0;
                setTimeout(() => nextTurn(), 500);
            }
        }

        // --- AI Level 1: Random valid move ---
        function aiLevel1(moves) {
            return moves[Math.floor(Math.random() * moves.length)];
        }

        // --- AI Level 2: Prioritize capture > bring out > move toward goal ---
        function aiLevel2(player, moves, roll) {
            // 1. Capture moves
            const captures = moves.filter(m => m.isCapture);
            if (captures.length > 0) {
                return captures[Math.floor(Math.random() * captures.length)];
            }

            // 2. Bring a piece out (from start to board) if rolled 6
            if (roll === 6) {
                const bringOut = moves.filter(m => m.from < 0);
                if (bringOut.length > 0) {
                    return bringOut[0];
                }
            }

            // 3. Move piece closest to home
            const po = player.playerOrder;
            let bestMove = moves[0];
            let bestProgress = -1;
            for (const m of moves) {
                let progress;
                if (m.to >= 40) {
                    progress = 40 + (m.to - 40); // In home = high priority
                } else {
                    progress = ((m.to - po * 10) + 400) % 40;
                }
                if (progress > bestProgress) {
                    bestProgress = progress;
                    bestMove = m;
                }
            }
            return bestMove;
        }

        // --- AI Level 3: Evaluate all options with scoring ---
        function aiLevel3(player, moves, roll) {
            let bestMove = moves[0];
            let bestScore = -Infinity;
            const po = player.playerOrder;

            for (const m of moves) {
                let score = 0;

                // Capturing is very valuable
                if (m.isCapture) {
                    score += 100;
                    // Extra bonus for capturing pieces that are far along
                    if (m.capturedPlayer) {
                        const capturedPos = m.capturedPlayer.pieces[m.capturedPiece];
                        const capturedProgress = ((capturedPos - m.capturedPlayer.playerOrder * 10) + 400) % 40;
                        score += capturedProgress; // More reward for sending back advanced pieces
                    }
                }

                // Bringing piece out is important
                if (m.from < 0 && m.to >= 0) {
                    score += 80;
                    // Extra bonus if start field was blocked by own piece
                    score += 10;
                }

                // Moving into home lane is great
                if (m.to >= 40) {
                    score += 90 + (m.to - 40) * 5; // Deeper in home = better
                }

                // Progress toward home
                if (m.to >= 0 && m.to < 40) {
                    const progress = ((m.to - po * 10) + 400) % 40;
                    score += progress * 1.5;

                    // Danger assessment: check if any opponent can land on us
                    let dangerScore = 0;
                    for (const other of players) {
                        if (other.color === player.color) continue;
                        for (const otherPos of other.pieces) {
                            if (otherPos < 0 || otherPos >= 40) continue;
                            // Check if opponent is behind us within 6 fields
                            for (let d = 1; d <= 6; d++) {
                                const potentialMove = (otherPos + d) % 40;
                                if (potentialMove === m.to) {
                                    dangerScore += (7 - d) * 5; // Closer = more dangerous
                                }
                            }
                        }
                        // Also check if opponent could bring a piece out onto our position
                        const otherStart = other.playerOrder * 10;
                        if (otherStart === m.to) {
                            const hasInStart = other.pieces.some(p => p < 0);
                            if (hasInStart) dangerScore += 25;
                        }
                    }
                    score -= dangerScore;

                    // Bonus for being on a safe-ish position (close to home entry)
                    if (progress >= 35) {
                        score += 15; // Almost at home
                    }
                }

                // Moving in home area
                if (m.from >= 40 && m.to >= 40) {
                    score += 50 + (m.to - 40) * 10; // Getting deeper into home
                }

                // Slight randomness to avoid predictability
                score += Math.random() * 5;

                if (score > bestScore) {
                    bestScore = score;
                    bestMove = m;
                }
            }

            return bestMove;
        }

        // ============================================================
        // UI UPDATES
        // ============================================================
        function updateSidebar() {
            const player = players[currentPlayerIdx];
            if (!player) return;

            // Turn indicator
            const turnInfo = document.getElementById('turnInfo');
            turnInfo.className = 'turn-info' + (player.isHuman ? ' my-turn' : '');
            const nameEl = document.getElementById('currentPlayerName');
            if (!player.isHuman && gamePhase === 'ai_turn') {
                nameEl.innerHTML = '<span class="thinking">' + COLOR_EMOJIS[player.color] +
                    ' ' + player.name + ' denkt</span>';
            } else {
                nameEl.textContent = COLOR_EMOJIS[player.color] + ' ' + player.name;
            }

            // Scoreboard
            const sb = document.getElementById('scoreboard');
            sb.innerHTML = players.map((p, idx) => {
                const finished = p.pieces.filter(pos => pos >= 40).length;
                const onBoard = p.pieces.filter(pos => pos >= 0 && pos < 40).length;
                const inStart = p.pieces.filter(pos => pos < 0).length;
                const isActive = idx === currentPlayerIdx;
                return '<div class="score-row' + (isActive ? ' active' : '') + '">' +
                    '<div class="color-dot ' + p.color + '"></div>' +
                    '<span class="sname">' + escapeHtml(p.name) + (p.isHuman ? '' : ' \u{1F916}') + '</span>' +
                    '<span class="pieces-info">' + finished + '/4 &#x1F3E0;</span>' +
                    '</div>';
            }).join('');

            // Log
            const logEl = document.getElementById('gameLog');
            logEl.innerHTML = logMessages.slice(-15).map(m => '<p>' + m + '</p>').join('');
            logEl.scrollTop = logEl.scrollHeight;
        }

        function addLog(msg) {
            logMessages.push(msg);
            const logEl = document.getElementById('gameLog');
            if (logEl) {
                logEl.innerHTML = logMessages.slice(-15).map(m => '<p>' + m + '</p>').join('');
                logEl.scrollTop = logEl.scrollHeight;
            }
        }

        function endGame(winner) {
            const resultEmoji = document.getElementById('resultEmoji');
            const winnerText = document.getElementById('winnerText');
            if (winner.isHuman) {
                resultEmoji.textContent = '\u{1F3C6}';
                winnerText.textContent = 'Du hast gewonnen!';
            } else {
                resultEmoji.textContent = '\u{1F614}';
                winnerText.textContent = COLOR_EMOJIS[winner.color] + ' ' + winner.name + ' gewinnt!';
            }
            addLog('\u{1F3C6} ' + winner.name + ' gewinnt das Spiel!');
            setTimeout(() => showScreen('result'), 1000);
        }

        // ============================================================
        // HELPERS
        // ============================================================
        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function showToast(msg, type) {
            const t = document.createElement('div');
            t.className = 'toast ' + (type || 'info');
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }

    })();
    </script>
</body>
</html>
