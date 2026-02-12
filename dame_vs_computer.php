<?php
/**
 * ============================================================================
 * sgiT Education - Dame vs Computer v1.0
 * ============================================================================
 *
 * Dame gegen KI-Gegner mit Minimax + Alpha-Beta Pruning
 * 5 Schwierigkeitsstufen
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
    <title>Dame vs Computer - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <style>
        :root {
            --light-square: #d4c8a0;
            --dark-square: #2a5a0a;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--mp-bg-dark) 0%, var(--mp-primary) 100%);
            min-height: 100vh; color: var(--mp-text); margin: 0; padding: 0;
        }
        .container { max-width: 1000px; margin: 0 auto; padding: 15px; }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 20px; background: var(--mp-bg-card); border-radius: 12px; margin-bottom: 20px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }
        .header h1 { font-size: 1.4rem; }
        .header h1 span { color: var(--mp-accent); }
        .back-link { color: var(--mp-accent); text-decoration: none; }
        .screen { display: none; }
        .screen.active { display: block; }

        /* Setup */
        .setup-container { max-width: 500px; margin: 30px auto; text-align: center; }
        .setup-card {
            background: var(--mp-bg-card); border-radius: 16px; padding: 25px; margin-bottom: 20px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }
        .setup-card h2 { color: var(--mp-accent); margin-bottom: 15px; }
        .difficulty-grid { display: flex; flex-direction: column; gap: 8px; margin: 15px 0; }
        .diff-option {
            background: var(--mp-bg-dark); border: 2px solid transparent; border-radius: 12px;
            padding: 12px 15px; cursor: pointer; text-align: left; transition: all 0.2s;
        }
        .diff-option:hover { border-color: var(--mp-accent); }
        .diff-option.selected { border-color: var(--mp-accent); background: rgba(76,175,80,0.1); }
        .diff-option .diff-name { font-weight: 600; }
        .diff-option .diff-desc { font-size: 0.85rem; color: var(--mp-text-muted); margin-top: 2px; }
        .color-choice { display: flex; gap: 15px; justify-content: center; margin: 15px 0; }
        .color-option {
            padding: 15px 25px; border-radius: 12px; cursor: pointer; border: 2px solid transparent;
            background: var(--mp-bg-dark); transition: all 0.2s; text-align: center;
        }
        .color-option:hover { border-color: var(--mp-accent); }
        .color-option.selected { border-color: var(--mp-accent); background: rgba(76,175,80,0.1); }
        .btn {
            background: var(--mp-accent); color: var(--mp-bg-dark); border: none;
            padding: 14px 28px; border-radius: 10px; font-size: 1rem; font-weight: 600;
            cursor: pointer; width: 100%;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn.secondary { background: var(--mp-bg-card); color: var(--mp-text); border: 2px solid var(--mp-accent); }

        /* Game */
        .game-container { display: grid; grid-template-columns: 1fr 250px; gap: 20px; }
        @media (max-width: 800px) { .game-container { grid-template-columns: 1fr; } }
        .board-area {
            background: var(--mp-bg-card); border-radius: 16px; padding: 20px;
            display: flex; justify-content: center;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }
        .board {
            display: grid; grid-template-columns: repeat(8, 50px); grid-template-rows: repeat(8, 50px);
            border: 4px solid #1a3503; border-radius: 4px; box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        }
        .cell {
            width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; position: relative;
        }
        .cell.light { background: var(--light-square); }
        .cell.dark { background: var(--dark-square); }
        .cell.selected { box-shadow: inset 0 0 0 3px var(--mp-accent); }
        .cell.valid-move::after {
            content: ''; width: 18px; height: 18px; background: rgba(76,175,80,0.5);
            border-radius: 50%; position: absolute;
        }
        .cell.capture-move::after {
            content: ''; width: 30px; height: 30px; background: rgba(231,76,60,0.5);
            border-radius: 50%; position: absolute;
        }
        .cell .piece-img {
            width: 40px; height: 40px; pointer-events: none;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
            transition: all 0.2s;
        }
        .cell:hover .piece-img { transform: scale(1.1); }
        .cell.must-capture .piece-img { filter: drop-shadow(0 0 8px rgba(231,76,60,0.8)); }
        .cell.selectable .piece-img { animation: pulse 0.8s infinite; }
        @keyframes pulse { 50% { transform: scale(1.1); } }

        /* Sidebar */
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
        .score-row { display: flex; align-items: center; justify-content: space-between; padding: 10px; background: var(--mp-bg-dark); border-radius: 8px; margin-bottom: 8px; }
        .score-row.active { border: 2px solid var(--mp-accent); }
        .thinking { color: var(--mp-accent); font-style: italic; }
        .thinking::after { content: '...'; animation: dots 1.5s infinite; }
        @keyframes dots { 0%{content:'.'} 33%{content:'..'} 66%{content:'...'} }

        .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 15px 25px; border-radius: 12px; font-weight: 600; z-index: 1000; }
        .toast.success { background: var(--mp-accent); color: var(--mp-bg-dark); }
        .toast.error { background: var(--mp-error); color: white; }
        .toast.info { background: var(--mp-bg-card); border: 2px solid var(--mp-accent); color: var(--mp-text); }

        .result-card { background: var(--mp-bg-card); border-radius: 16px; padding: 30px; text-align: center; border: 3px solid var(--mp-accent); }

        @media (max-width: 500px) {
            .board { grid-template-columns: repeat(8, 38px); grid-template-rows: repeat(8, 38px); }
            .cell { width: 38px; height: 38px; }
            .cell .piece-img { width: 30px; height: 30px; }
        }
        @media (max-width: 380px) {
            .board { grid-template-columns: repeat(8, 32px); grid-template-rows: repeat(8, 32px); }
            .cell { width: 32px; height: 32px; }
            .cell .piece-img { width: 26px; height: 26px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="dame.php" class="back-link">← Dame</a>
                <h1>⚫ <span>Dame</span> vs Computer</h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>

        <!-- SETUP -->
        <div id="setupScreen" class="screen active">
            <div class="setup-container">
                <div style="font-size: 4rem; margin-bottom: 10px;">⚫</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Dame vs Computer</h1>
                <p style="color: var(--mp-text-muted); margin-bottom: 25px;">Spiele Dame gegen eine KI</p>

                <div class="setup-card">
                    <h2>🎨 Deine Farbe</h2>
                    <div class="color-choice">
                        <div class="color-option selected" onclick="selectColor('green', this)">
                            <div style="font-size: 2rem;">🟢</div>
                            <div>Gruen (beginnt)</div>
                        </div>
                        <div class="color-option" onclick="selectColor('black', this)">
                            <div style="font-size: 2rem;">⚫</div>
                            <div>Schwarz</div>
                        </div>
                    </div>
                </div>

                <div class="setup-card">
                    <h2>🎯 Schwierigkeit</h2>
                    <div class="difficulty-grid">
                        <div class="diff-option" onclick="selectDifficulty(1, this)">
                            <div class="diff-name">😊 Level 1 - Anfaenger</div>
                            <div class="diff-desc">Zufaellige gueltige Zuege</div>
                        </div>
                        <div class="diff-option" onclick="selectDifficulty(2, this)">
                            <div class="diff-name">🤔 Level 2 - Leicht</div>
                            <div class="diff-desc">Schlagzwang + einfache Bewertung</div>
                        </div>
                        <div class="diff-option selected" onclick="selectDifficulty(3, this)">
                            <div class="diff-name">😤 Level 3 - Mittel</div>
                            <div class="diff-desc">Minimax (Tiefe 3)</div>
                        </div>
                        <div class="diff-option" onclick="selectDifficulty(4, this)">
                            <div class="diff-name">😈 Level 4 - Schwer</div>
                            <div class="diff-desc">Minimax + Alpha-Beta (Tiefe 5)</div>
                        </div>
                        <div class="diff-option" onclick="selectDifficulty(5, this)">
                            <div class="diff-name">🏆 Level 5 - Meister</div>
                            <div class="diff-desc">Minimax + Alpha-Beta + Endspiel (Tiefe 7)</div>
                        </div>
                    </div>
                </div>

                <button class="btn" onclick="startGame()">Spiel starten</button>
                <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='dame.php'">← Zurueck</button>
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
                            <div class="name" id="currentPlayerName">Du</div>
                        </div>
                    </div>
                    <div class="info-card">
                        <h3>📊 Steine</h3>
                        <div class="score-row" id="greenScore"><span>🟢 Gruen</span><span id="greenCount">12</span></div>
                        <div class="score-row" id="blackScore"><span>⚫ Schwarz</span><span id="blackCount">12</span></div>
                    </div>
                    <div class="info-card">
                        <h3>📜 Regeln</h3>
                        <ul style="font-size: 0.85rem; color: var(--mp-text-muted); list-style: none; padding: 0;">
                            <li>↗️ Diagonal vorwaerts ziehen</li>
                            <li>💥 Ueber Gegner springen = schlagen</li>
                            <li>⚠️ Schlagzwang!</li>
                            <li>👑 Am Ende = Dame (kann rueckwaerts)</li>
                        </ul>
                    </div>
                    <button class="btn secondary" onclick="undoMove()">↩️ Zug zuruecknehmen</button>
                </div>
            </div>
        </div>

        <!-- RESULT -->
        <div id="resultScreen" class="screen">
            <div class="setup-container">
                <div class="result-card">
                    <div style="font-size: 5rem;" id="resultEmoji">🏆</div>
                    <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                    <button class="btn" onclick="startGame()">🔄 Nochmal spielen</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="showScreen('setup')">⚙️ Einstellungen</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='dame.php'">← Zurueck</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/dame-pieces.js"></script>
    <script>
    (() => {
        let difficulty = 3;
        let playerColor = 'green';   // green or black
        let aiColor = 'black';
        let board = {};
        let currentPlayer = 'green'; // green always starts
        let selectedCell = null;
        let validMoves = [];
        let mustCaptureFrom = null;
        let aiThinking = false;
        let history = [];
        let playerName = '<?php echo addslashes($userName ?: "Du"); ?>';

        window.selectDifficulty = function(level, el) {
            difficulty = level;
            document.querySelectorAll('.diff-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
        };

        window.selectColor = function(color, el) {
            playerColor = color;
            aiColor = color === 'green' ? 'black' : 'green';
            document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
        };

        window.showScreen = function(name) {
            document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
            document.getElementById(name + 'Screen').classList.add('active');
        };

        // ===== BOARD INIT =====
        function createInitialBoard() {
            const b = {};
            for (let r = 0; r < 8; r++) {
                for (let c = 0; c < 8; c++) {
                    if ((r + c) % 2 === 1) {
                        if (r < 3) b[`${r},${c}`] = { color: 'green', king: false };
                        else if (r > 4) b[`${r},${c}`] = { color: 'black', king: false };
                    }
                }
            }
            return b;
        }

        window.startGame = function() {
            board = createInitialBoard();
            currentPlayer = 'green';
            selectedCell = null;
            validMoves = [];
            mustCaptureFrom = null;
            aiThinking = false;
            history = [];
            showScreen('game');
            render();
            if (playerColor !== 'green') {
                scheduleAiTurn();
            }
        };

        // ===== MOVE LOGIC =====
        function getAllMoves(b, color) {
            const captures = [];
            const normals = [];
            for (const pos in b) {
                if (b[pos].color !== color) continue;
                const piece = b[pos];
                const [r, c] = pos.split(',').map(Number);
                const dir = (color === 'green') ? 1 : -1;
                const dirs = piece.king ? [[-1,-1],[-1,1],[1,-1],[1,1]] : [[dir,-1],[dir,1]];

                // Captures (all directions for capturing)
                for (const [dr, dc] of [[-1,-1],[-1,1],[1,-1],[1,1]]) {
                    const mr = r + dr, mc = c + dc;
                    const tr = r + 2*dr, tc = c + 2*dc;
                    if (tr >= 0 && tr <= 7 && tc >= 0 && tc <= 7) {
                        const mp = `${mr},${mc}`, tp = `${tr},${tc}`;
                        if (b[mp] && b[mp].color !== color && !b[tp]) {
                            captures.push({ from: pos, to: tp, capture: mp });
                        }
                    }
                }

                // Normal moves
                for (const [dr, dc] of dirs) {
                    const tr = r + dr, tc = c + dc;
                    if (tr >= 0 && tr <= 7 && tc >= 0 && tc <= 7) {
                        const tp = `${tr},${tc}`;
                        if (!b[tp]) normals.push({ from: pos, to: tp, capture: null });
                    }
                }
            }
            return captures.length > 0 ? captures : normals;
        }

        function getCapturesFrom(b, pos) {
            const piece = b[pos];
            if (!piece) return [];
            const [r, c] = pos.split(',').map(Number);
            const caps = [];
            for (const [dr, dc] of [[-1,-1],[-1,1],[1,-1],[1,1]]) {
                const mr = r + dr, mc = c + dc;
                const tr = r + 2*dr, tc = c + 2*dc;
                if (tr >= 0 && tr <= 7 && tc >= 0 && tc <= 7) {
                    const mp = `${mr},${mc}`, tp = `${tr},${tc}`;
                    if (b[mp] && b[mp].color !== piece.color && !b[tp]) {
                        caps.push({ from: pos, to: tp, capture: mp });
                    }
                }
            }
            return caps;
        }

        function applyMove(b, move) {
            const nb = JSON.parse(JSON.stringify(b));
            const piece = nb[move.from];
            delete nb[move.from];
            if (move.capture) delete nb[move.capture];
            // King promotion
            if ((piece.color === 'green' && move.to.startsWith('7,')) ||
                (piece.color === 'black' && move.to.startsWith('0,'))) {
                piece.king = true;
            }
            nb[move.to] = piece;
            return nb;
        }

        // ===== PLAYER MOVES =====
        function getPlayerMoves(pos) {
            const piece = board[pos];
            if (!piece || piece.color !== playerColor) return [];
            const allMoves = getAllMoves(board, playerColor);
            return allMoves.filter(m => m.from === pos);
        }

        window.handleCellClick = function(row, col) {
            if (aiThinking || currentPlayer !== playerColor) return;
            const pos = `${row},${col}`;

            // Click on valid move target
            const vm = validMoves.find(m => m.to === pos);
            if (vm && selectedCell) {
                executeMove(vm);
                return;
            }

            // Select own piece
            if (board[pos] && board[pos].color === playerColor) {
                if (mustCaptureFrom && pos !== mustCaptureFrom) {
                    showToast('Weiterschlagen!', 'error');
                    return;
                }
                selectedCell = pos;
                validMoves = getPlayerMoves(pos);
                render();
            } else {
                selectedCell = null;
                validMoves = [];
                render();
            }
        };

        function executeMove(move) {
            history.push({ board: JSON.parse(JSON.stringify(board)), currentPlayer, mustCaptureFrom });
            board = applyMove(board, move);
            selectedCell = null;
            validMoves = [];

            // Multi-jump
            if (move.capture) {
                const moreCaps = getCapturesFrom(board, move.to);
                if (moreCaps.length > 0) {
                    mustCaptureFrom = move.to;
                    render();
                    showToast('Weiterschlagen!', 'info');
                    return;
                }
            }

            mustCaptureFrom = null;
            switchTurn();
        }

        function switchTurn() {
            currentPlayer = (currentPlayer === 'green') ? 'black' : 'green';
            const winner = checkWinner();
            if (winner) {
                render();
                endGame(winner);
                return;
            }
            render();
            if (currentPlayer === aiColor) scheduleAiTurn();
        }

        function checkWinner() {
            let gc = 0, bc = 0;
            for (const p in board) {
                if (board[p].color === 'green') gc++;
                else bc++;
            }
            if (gc === 0) return 'black';
            if (bc === 0) return 'green';
            if (getAllMoves(board, currentPlayer).length === 0) {
                return currentPlayer === 'green' ? 'black' : 'green';
            }
            return null;
        }

        window.undoMove = function() {
            if (history.length < 2) return; // Undo both AI and player move
            // Pop AI move and player move
            const prev = history.pop(); // AI state
            const prev2 = history.pop(); // Player state before
            board = prev2.board;
            currentPlayer = prev2.currentPlayer;
            mustCaptureFrom = prev2.mustCaptureFrom;
            selectedCell = null;
            validMoves = [];
            aiThinking = false;
            render();
        };

        // ===== AI =====
        function scheduleAiTurn() {
            aiThinking = true;
            render();
            const delay = [0, 300, 500, 800, 1000, 1500][difficulty] + Math.random() * 300;
            setTimeout(aiTurn, delay);
        }

        function aiTurn() {
            const moves = getAllMoves(board, aiColor);
            if (moves.length === 0) { aiThinking = false; endGame(playerColor); return; }

            let chosen;
            if (difficulty === 1) {
                chosen = moves[Math.floor(Math.random() * moves.length)];
            } else if (difficulty === 2) {
                chosen = aiLevel2(moves);
            } else {
                const depth = [0, 0, 1, 3, 5, 7][difficulty];
                chosen = aiMinimax(moves, depth);
            }

            history.push({ board: JSON.parse(JSON.stringify(board)), currentPlayer, mustCaptureFrom });
            board = applyMove(board, chosen);

            // Multi-jump for AI
            if (chosen.capture) {
                let moreCaps = getCapturesFrom(board, chosen.to);
                while (moreCaps.length > 0) {
                    // Choose best capture
                    const cap = moreCaps.length === 1 ? moreCaps[0] :
                        moreCaps[Math.floor(Math.random() * moreCaps.length)];
                    board = applyMove(board, cap);
                    moreCaps = getCapturesFrom(board, cap.to);
                }
            }

            aiThinking = false;
            mustCaptureFrom = null;
            switchTurn();
        }

        function aiLevel2(moves) {
            // Prefer captures, then evaluate position simply
            const captures = moves.filter(m => m.capture);
            if (captures.length > 0) return captures[Math.floor(Math.random() * captures.length)];
            // Simple: prefer center moves
            let best = moves[0], bestScore = -999;
            for (const m of moves) {
                const [r, c] = m.to.split(',').map(Number);
                const score = -(Math.abs(3.5 - r) + Math.abs(3.5 - c));
                if (score > bestScore) { bestScore = score; best = m; }
            }
            return best;
        }

        function aiMinimax(moves, maxDepth) {
            let bestMove = moves[0], bestScore = -Infinity;
            for (const m of moves) {
                let nb = applyMove(board, m);
                // Handle multi-jump
                if (m.capture) {
                    let moreCaps = getCapturesFrom(nb, m.to);
                    while (moreCaps.length > 0) {
                        const cap = moreCaps[0];
                        nb = applyMove(nb, cap);
                        moreCaps = getCapturesFrom(nb, cap.to);
                    }
                }
                const score = minimax(nb, maxDepth - 1, -Infinity, Infinity, false);
                if (score > bestScore) { bestScore = score; bestMove = m; }
            }
            return bestMove;
        }

        function minimax(b, depth, alpha, beta, isMaximizing) {
            const color = isMaximizing ? aiColor : playerColor;
            const moves = getAllMoves(b, color);
            if (depth === 0 || moves.length === 0) return evaluate(b);

            if (isMaximizing) {
                let maxEval = -Infinity;
                for (const m of moves) {
                    let nb = applyMove(b, m);
                    if (m.capture) {
                        let moreCaps = getCapturesFrom(nb, m.to);
                        while (moreCaps.length > 0) { nb = applyMove(nb, moreCaps[0]); moreCaps = getCapturesFrom(nb, moreCaps[0].to); }
                    }
                    const ev = minimax(nb, depth - 1, alpha, beta, false);
                    maxEval = Math.max(maxEval, ev);
                    alpha = Math.max(alpha, ev);
                    if (beta <= alpha) break;
                }
                return maxEval;
            } else {
                let minEval = Infinity;
                for (const m of moves) {
                    let nb = applyMove(b, m);
                    if (m.capture) {
                        let moreCaps = getCapturesFrom(nb, m.to);
                        while (moreCaps.length > 0) { nb = applyMove(nb, moreCaps[0]); moreCaps = getCapturesFrom(nb, moreCaps[0].to); }
                    }
                    const ev = minimax(nb, depth - 1, alpha, beta, true);
                    minEval = Math.min(minEval, ev);
                    beta = Math.min(beta, ev);
                    if (beta <= alpha) break;
                }
                return minEval;
            }
        }

        function evaluate(b) {
            let score = 0;
            for (const pos in b) {
                const piece = b[pos];
                const [r, c] = pos.split(',').map(Number);
                const val = piece.king ? 3 : 1;
                const centerBonus = (3.5 - Math.abs(3.5 - r)) * 0.1 + (3.5 - Math.abs(3.5 - c)) * 0.1;

                if (piece.color === aiColor) {
                    score += val + centerBonus;
                    // Back row defense bonus
                    if (!piece.king && ((aiColor === 'green' && r === 0) || (aiColor === 'black' && r === 7))) score += 0.3;
                } else {
                    score -= val - centerBonus;
                    if (!piece.king && ((playerColor === 'green' && r === 0) || (playerColor === 'black' && r === 7))) score -= 0.3;
                }
            }
            // Mobility
            if (difficulty >= 5) {
                score += getAllMoves(b, aiColor).length * 0.05;
                score -= getAllMoves(b, playerColor).length * 0.05;
            }
            return score;
        }

        function endGame(winner) {
            const resultEmoji = document.getElementById('resultEmoji');
            const winnerText = document.getElementById('winnerText');
            if (winner === playerColor) {
                resultEmoji.textContent = '🏆';
                winnerText.textContent = 'Du hast gewonnen!';
            } else {
                resultEmoji.textContent = '😔';
                winnerText.textContent = 'Computer gewinnt!';
            }
            setTimeout(() => showScreen('result'), 500);
        }

        // ===== RENDERING =====
        function render() {
            const boardEl = document.getElementById('board');
            boardEl.innerHTML = '';
            const isMyTurn = currentPlayer === playerColor && !aiThinking;
            const allMoves = isMyTurn ? getAllMoves(board, playerColor) : [];
            const hasCaptures = allMoves.some(m => m.capture);

            for (let r = 0; r < 8; r++) {
                for (let c = 0; c < 8; c++) {
                    const cell = document.createElement('div');
                    const isLight = (r + c) % 2 === 0;
                    cell.className = `cell ${isLight ? 'light' : 'dark'}`;

                    if (selectedCell === `${r},${c}`) cell.classList.add('selected');

                    const vm = validMoves.find(m => m.to === `${r},${c}`);
                    if (vm) cell.classList.add(vm.capture ? 'capture-move' : 'valid-move');

                    const pos = `${r},${c}`;
                    if (board[pos]) {
                        const piece = board[pos];
                        const img = document.createElement('img');
                        img.className = 'piece-img';
                        if (piece.color === 'green') {
                            img.src = piece.king ? DAME_PIECE_SVGS.greenKing : DAME_PIECE_SVGS.green;
                        } else {
                            img.src = piece.king ? DAME_PIECE_SVGS.blackKing : DAME_PIECE_SVGS.black;
                        }
                        img.draggable = false;

                        if (mustCaptureFrom === pos) cell.classList.add('must-capture');
                        else if (isMyTurn && piece.color === playerColor) {
                            const hasMoves = allMoves.some(m => m.from === pos);
                            if (hasMoves) cell.classList.add('selectable');
                        }

                        cell.appendChild(img);
                    }

                    cell.onclick = () => handleCellClick(r, c);
                    boardEl.appendChild(cell);
                }
            }

            // Turn info
            const turnInfo = document.getElementById('turnInfo');
            turnInfo.className = 'turn-info' + (isMyTurn ? ' my-turn' : '');
            const nameEl = document.getElementById('currentPlayerName');
            if (aiThinking) {
                nameEl.innerHTML = '<span class="thinking">🤖 Computer denkt</span>';
            } else {
                nameEl.textContent = isMyTurn ? `😊 ${playerName}` : '🤖 Computer';
            }

            // Scores
            let gc = 0, bc = 0;
            for (const p in board) { if (board[p].color === 'green') gc++; else bc++; }
            document.getElementById('greenCount').textContent = gc;
            document.getElementById('blackCount').textContent = bc;
            document.getElementById('greenScore').className = 'score-row' + (currentPlayer === 'green' ? ' active' : '');
            document.getElementById('blackScore').className = 'score-row' + (currentPlayer === 'black' ? ' active' : '');
        }

        function showToast(msg, type) {
            const t = document.createElement('div');
            t.className = 'toast ' + type;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }
    })();
    </script>
</body>
</html>
