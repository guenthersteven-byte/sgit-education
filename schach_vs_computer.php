<?php
/**
 * ============================================================================
 * sgiT Education - Schach vs Computer v1.0
 * ============================================================================
 *
 * Schach gegen KI (Stockfish.js) mit 5 Schwierigkeitsstufen
 * Komplett client-seitig - kein Server-Roundtrip waehrend des Spiels
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
require_once 'includes/version.php';
require_once __DIR__ . '/wallet/SessionManager.php';

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
    <title>♟️ Schach vs Computer - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <link rel="stylesheet" href="/assets/css/chess-theme.css">
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--mp-bg-dark) 0%, var(--mp-primary) 100%);
            min-height: 100vh;
            color: var(--mp-text);
            margin: 0; padding: 0;
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 15px; }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 20px; background: var(--mp-bg-card); border-radius: 12px; margin-bottom: 20px;
        }
        .header h1 { font-size: 1.4rem; }
        .header h1 span { color: var(--mp-accent); }
        .back-link { color: var(--mp-accent); text-decoration: none; }

        .screen { display: none; }
        .screen.active { display: block; }

        /* Setup */
        .setup-container { max-width: 550px; margin: 20px auto; text-align: center; }
        .setup-card {
            background: rgba(30, 58, 8, 0.5);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(67, 210, 64, 0.15);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .setup-card h2 { color: var(--mp-accent); margin-bottom: 15px; }

        .btn {
            background: var(--mp-accent); color: var(--mp-primary); border: none;
            padding: 14px 28px; border-radius: 10px; font-size: 1rem; font-weight: 600;
            cursor: pointer; width: 100%; transition: all 0.3s ease;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(67, 210, 64, 0.3); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn.secondary { background: transparent; color: var(--mp-text); border: 2px solid var(--mp-accent); }
        .btn.danger { background: #c0392b; }

        .input-group { margin-bottom: 15px; }
        .input-group input {
            width: 100%; padding: 12px; border: 2px solid transparent; border-radius: 10px;
            background: rgba(0, 0, 0, 0.3); color: #fff; font-size: 1rem;
            box-sizing: border-box;
        }
        .input-group input:focus { outline: none; border-color: var(--mp-accent); }

        /* Game - Layout kommt aus chess-theme.css, hier nur Layout-Grid */
        .game-container { display: grid; grid-template-columns: 1fr 270px; gap: 24px; }
        @media (max-width: 800px) { .game-container { grid-template-columns: 1fr; } }

        /* Sidebar */
        .sidebar { display: flex; flex-direction: column; gap: 15px; }
        .info-card h3 { color: var(--mp-accent); margin-bottom: 10px; font-size: 1.05rem; }

        .turn-info { text-align: center; padding: 16px; background: rgba(0,0,0,0.25); border-radius: 12px; }
        .turn-info.my-turn { border: 2px solid var(--mp-accent); box-shadow: 0 0 15px rgba(67, 210, 64, 0.15); }
        .turn-info.check { border: 2px solid #e74c3c; animation: pulse-red 1s infinite; }
        @keyframes pulse-red { 50% { box-shadow: 0 0 20px rgba(231, 76, 60, 0.5); } }
        .turn-info .label { font-size: 0.9rem; color: #a0a0a0; }
        .turn-info .name { font-size: 1.3rem; font-weight: bold; margin-top: 6px; }

        .player-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px; background: rgba(0,0,0,0.25); border-radius: 10px; margin-bottom: 8px;
        }
        .player-row.active { border: 2px solid var(--mp-accent); box-shadow: 0 0 10px rgba(67, 210, 64, 0.1); }
        .player-row .piece-icon { font-size: 1.6rem; margin-right: 10px; }

        .action-btns { display: flex; gap: 10px; flex-direction: column; }

        /* Promotion Modal */
        .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 100; }
        .modal.active { display: flex; }
        .modal-content {
            background: rgba(30, 58, 8, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(67, 210, 64, 0.3);
            border-radius: 16px; padding: 25px; text-align: center;
        }
        .modal h2 { margin-bottom: 20px; color: var(--mp-accent); }
        .promo-choice { display: flex; gap: 15px; justify-content: center; }
        .promo-btn {
            width: 72px; height: 72px;
            border: 3px solid var(--mp-accent); border-radius: 14px;
            cursor: pointer; background: rgba(0,0,0,0.3); transition: all 0.2s ease;
            display: flex; align-items: center; justify-content: center;
        }
        .promo-btn:hover { transform: scale(1.12); background: rgba(67, 210, 64, 0.15); box-shadow: 0 0 15px rgba(67, 210, 64, 0.2); }

        /* Result */
        .result-card {
            background: rgba(30, 58, 8, 0.6);
            backdrop-filter: blur(10px);
            border: 3px solid var(--mp-accent);
            border-radius: 16px; padding: 30px; text-align: center;
            max-width: 500px; margin: 40px auto;
        }

        .toast {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            padding: 15px 25px; border-radius: 12px; font-weight: 600; z-index: 1000;
        }
        .toast.success { background: var(--mp-accent); color: var(--mp-primary); }
        .toast.error { background: #e74c3c; color: white; }
        .toast.info { background: rgba(30, 58, 8, 0.9); border: 2px solid var(--mp-accent); color: #fff; }

        /* Stockfish Lade-Overlay */
        .loading-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.85); display: flex; flex-direction: column;
            align-items: center; justify-content: center; z-index: 200; gap: 15px;
        }
        .loading-overlay .spinner {
            width: 50px; height: 50px; border: 4px solid rgba(67, 210, 64, 0.2);
            border-top-color: #43D240; border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-overlay p { color: #a0a0a0; font-size: 0.95rem; }

        /* Mobile - Groessen kommen aus chess-theme.css via CSS Variables */
        @media (max-width: 550px) {
            .sidebar { gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="schach_pvp.php" class="back-link">&larr; Schach</a>
                <h1>♟️ <span>Schach</span> vs Computer</h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>

        <!-- SETUP -->
        <div id="setupScreen" class="screen active">
            <div class="setup-container">
                <div style="font-size: 4rem; margin-bottom: 10px;">🤖</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Gegen Computer</h1>
                <p style="color: #a0a0a0; margin-bottom: 25px;">Spiele Schach gegen die KI</p>

                <div class="setup-card" id="nameCard" style="<?php echo $userName ? 'display:none' : ''; ?>">
                    <h2>👤 Dein Name</h2>
                    <div class="input-group">
                        <input type="text" id="playerNameInput" placeholder="Name..." maxlength="20">
                    </div>
                    <button class="btn" onclick="setPlayerName()">Weiter &rarr;</button>
                </div>

                <div id="settingsCards" style="<?php echo $userName ? '' : 'display:none'; ?>">
                    <div class="setup-card">
                        <h2>🎯 Schwierigkeit</h2>
                        <div class="chess-difficulty-grid" id="difficultyGrid"></div>
                    </div>

                    <div class="setup-card">
                        <h2>♟️ Deine Farbe</h2>
                        <div class="chess-color-choice">
                            <div class="chess-color-btn active" data-color="white" onclick="selectColor('white')">♔</div>
                            <div class="chess-color-btn" data-color="random" onclick="selectColor('random')">🎲</div>
                            <div class="chess-color-btn" data-color="black" onclick="selectColor('black')">♚</div>
                        </div>
                        <p style="color: #a0a0a0; font-size: 0.85rem; margin-top: 8px;" id="colorLabel">Du spielst mit Weiss</p>
                    </div>

                    <button class="btn" onclick="startGame()" style="margin-top: 10px;">▶️ Spiel starten</button>
                </div>
            </div>
        </div>

        <!-- GAME -->
        <div id="gameScreen" class="screen">
            <div class="game-container">
                <div class="board-area">
                    <div class="board" id="board"></div>
                    <div class="thinking-indicator" id="thinkingIndicator">
                        <div class="thinking-dots"><span></span><span></span><span></span></div>
                        <span>🤖 Computer denkt nach...</span>
                    </div>
                </div>

                <div class="sidebar">
                    <div class="info-card">
                        <div class="turn-info" id="turnInfo">
                            <div class="label">Am Zug:</div>
                            <div class="name" id="currentTurnName">---</div>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>👥 Spieler</h3>
                        <div class="player-row" id="whitePlayer">
                            <span><span class="piece-icon">♔</span> <span class="pname">Weiss</span></span>
                        </div>
                        <div class="player-row" id="blackPlayer">
                            <span><span class="piece-icon">♚</span> <span class="pname">Schwarz</span></span>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>📋 Zuege</h3>
                        <div class="chess-move-list" id="moveList"></div>
                    </div>

                    <div class="info-card action-btns">
                        <button class="btn secondary" onclick="undoMove()" id="undoBtn" disabled>↩️ Zug zuruecknehmen</button>
                        <button class="btn danger" onclick="resignGame()">🏳️ Aufgeben</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESULT -->
        <div id="resultScreen" class="screen">
            <div class="result-card">
                <div style="font-size: 5rem;" id="resultIcon">🏆</div>
                <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                <p style="color: #a0a0a0; margin-bottom: 20px;" id="winReason"></p>
                <button class="btn" onclick="restartGame()">🔄 Nochmal spielen</button>
                <button class="btn secondary" style="margin-top: 10px;" onclick="backToSetup()">⚙️ Einstellungen aendern</button>
                <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='schach_pvp.php'">← Zurueck</button>
            </div>
        </div>

        <!-- Promotion Modal -->
        <div class="modal" id="promoModal">
            <div class="modal-content">
                <h2>👑 Bauernumwandlung</h2>
                <p style="color: #a0a0a0; margin-bottom: 20px;">Waehle eine Figur:</p>
                <div class="promo-choice" id="promoChoices"></div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div class="loading-overlay" id="loadingOverlay" style="display: none;">
            <div class="spinner"></div>
            <p>🤖 Schach-Engine wird geladen...</p>
            <p style="font-size: 0.8rem;">Dies kann einige Sekunden dauern</p>
        </div>
    </div>

    <script src="/assets/js/chess-pieces.js"></script>
    <script src="/assets/js/stockfish/stockfish-loader.js"></script>
    <script>
    // =========================================================================
    // sgiT Education - Schach vs Computer Engine
    // =========================================================================

    const PIECES = {
        wK: '♚', wQ: '♛', wR: '♜', wB: '♝', wN: '♞', wP: '♟',
        bK: '♚', bQ: '♛', bR: '♜', bB: '♝', bN: '♞', bP: '♟'
    };

    const PIECE_FEN = {
        wK: 'K', wQ: 'Q', wR: 'R', wB: 'B', wN: 'N', wP: 'P',
        bK: 'k', bQ: 'q', bR: 'r', bB: 'b', bN: 'n', bP: 'p'
    };

    const DIFFICULTY_ICONS = ['🌱', '🎯', '⚔️', '🔥', '👑'];
    const DIFFICULTY_NAMES = ['Anfaenger', 'Leicht', 'Mittel', 'Schwer', 'Meister'];
    const DIFFICULTY_AGES = ['5-7 J.', '7-10 J.', '10-14 J.', '14-18 J.', '16+ J.'];

    // =========================================================================
    // State
    // =========================================================================

    let playerName = '<?php echo addslashes($userName); ?>';
    let playerAvatar = '<?php echo addslashes($userAvatar); ?>';

    let settings = {
        difficulty: 3,
        playerColor: 'white'
    };

    let game = {
        board: {},
        currentPlayer: 'white',
        castling: { wK: true, wQ: true, bK: true, bQ: true },
        enPassant: null,
        halfMoves: 0,
        fullMoves: 1,
        selectedCell: null,
        validMoves: [],
        lastMove: null,
        checkState: null,
        status: 'setup',  // setup | playing | finished
        history: [],       // Fuer Undo
        moveNotation: [],  // Zugliste
        winner: null,
        winReason: null
    };

    let stockfish = null;
    let stockfishLoaded = false;
    let pendingPromotion = null;

    // =========================================================================
    // Setup Screen
    // =========================================================================

    function initSetup() {
        const grid = document.getElementById('difficultyGrid');
        grid.innerHTML = '';
        for (let i = 1; i <= 5; i++) {
            const btn = document.createElement('div');
            btn.className = 'chess-difficulty-btn' + (i === settings.difficulty ? ' active' : '');
            btn.onclick = () => selectDifficulty(i);
            btn.innerHTML = `
                <div class="level-icon">${DIFFICULTY_ICONS[i-1]}</div>
                <div class="level-name">${DIFFICULTY_NAMES[i-1]}</div>
                <div class="level-elo">${DIFFICULTY_AGES[i-1]}</div>
            `;
            grid.appendChild(btn);
        }
    }

    function selectDifficulty(level) {
        settings.difficulty = level;
        document.querySelectorAll('.chess-difficulty-btn').forEach((b, i) => {
            b.classList.toggle('active', i === level - 1);
        });
    }

    function selectColor(color) {
        settings.playerColor = color;
        document.querySelectorAll('.chess-color-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.color === color);
        });
        const labels = { white: 'Du spielst mit Weiss', black: 'Du spielst mit Schwarz', random: 'Zufaellige Farbe' };
        document.getElementById('colorLabel').textContent = labels[color];
    }

    function setPlayerName() {
        const name = document.getElementById('playerNameInput').value.trim();
        if (!name) { showToast('Name eingeben', 'error'); return; }
        playerName = name;
        document.getElementById('nameCard').style.display = 'none';
        document.getElementById('settingsCards').style.display = 'block';
    }

    // =========================================================================
    // Game Init
    // =========================================================================

    async function startGame() {
        // Farbe bestimmen
        if (settings.playerColor === 'random') {
            settings.playerColor = Math.random() < 0.5 ? 'white' : 'black';
        }

        // Stockfish laden
        document.getElementById('loadingOverlay').style.display = 'flex';

        try {
            if (!stockfishLoaded) {
                stockfish = new StockfishLoader();
                await stockfish.init();
                stockfishLoaded = true;
            }
            stockfish.setDifficulty(settings.difficulty);
            stockfish.newGame();
        } catch (e) {
            console.error('Stockfish init failed:', e);
            document.getElementById('loadingOverlay').style.display = 'none';
            showToast('Schach-Engine konnte nicht geladen werden. Bitte Seite neu laden.', 'error');
            return;
        }

        document.getElementById('loadingOverlay').style.display = 'none';

        // Game State initialisieren
        game.board = createInitialBoard();
        game.currentPlayer = 'white';
        game.castling = { wK: true, wQ: true, bK: true, bQ: true };
        game.enPassant = null;
        game.halfMoves = 0;
        game.fullMoves = 1;
        game.selectedCell = null;
        game.validMoves = [];
        game.lastMove = null;
        game.checkState = null;
        game.status = 'playing';
        game.history = [];
        game.moveNotation = [];
        game.winner = null;
        game.winReason = null;
        pendingPromotion = null;

        // UI aktualisieren
        const cpuColor = settings.playerColor === 'white' ? 'black' : 'white';
        document.querySelector('#whitePlayer .pname').textContent =
            settings.playerColor === 'white' ? (playerName || 'Du') : '🤖 Computer';
        document.querySelector('#blackPlayer .pname').textContent =
            settings.playerColor === 'black' ? (playerName || 'Du') : '🤖 Computer';

        document.getElementById('moveList').innerHTML = '';
        document.getElementById('undoBtn').disabled = true;

        showScreen('game');
        renderBoard();

        // Computer beginnt?
        if (settings.playerColor === 'black') {
            requestAIMove();
        }
    }

    function createInitialBoard() {
        return {
            a8: 'bR', b8: 'bN', c8: 'bB', d8: 'bQ', e8: 'bK', f8: 'bB', g8: 'bN', h8: 'bR',
            a7: 'bP', b7: 'bP', c7: 'bP', d7: 'bP', e7: 'bP', f7: 'bP', g7: 'bP', h7: 'bP',
            a2: 'wP', b2: 'wP', c2: 'wP', d2: 'wP', e2: 'wP', f2: 'wP', g2: 'wP', h2: 'wP',
            a1: 'wR', b1: 'wN', c1: 'wB', d1: 'wQ', e1: 'wK', f1: 'wB', g1: 'wN', h1: 'wR'
        };
    }

    // =========================================================================
    // Board Rendering
    // =========================================================================

    function renderBoard() {
        const isFlipped = settings.playerColor === 'black';
        const boardEl = document.getElementById('board');
        boardEl.innerHTML = '';

        // Koenig im Schach finden
        let kingInCheck = null;
        if (game.checkState === 'check') {
            for (const [sq, piece] of Object.entries(game.board)) {
                if (piece === (game.currentPlayer === 'white' ? 'wK' : 'bK')) {
                    kingInCheck = sq; break;
                }
            }
        }

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
                if (col === (isFlipped ? 7 : 0)) {
                    const rank = document.createElement('span');
                    rank.className = 'coord rank';
                    rank.textContent = row + 1;
                    cell.appendChild(rank);
                }
                if (row === (isFlipped ? 7 : 0)) {
                    const file = document.createElement('span');
                    file.className = 'coord file';
                    file.textContent = String.fromCharCode(97 + col);
                    cell.appendChild(file);
                }

                // Letzter Zug
                if (game.lastMove && (sq === game.lastMove.from || sq === game.lastMove.to)) {
                    cell.classList.add('last-move');
                }

                // Schach
                if (sq === kingInCheck) cell.classList.add('check');

                // Ausgewaehlt
                if (game.selectedCell === sq) cell.classList.add('selected');

                // Gueltige Zuege
                if (game.validMoves.includes(sq)) {
                    cell.classList.add(game.board[sq] ? 'capture-move' : 'valid-move');
                }

                // Figur (SVG)
                if (game.board[sq]) {
                    const img = document.createElement('img');
                    img.className = 'piece-img';
                    img.src = CHESS_PIECE_SVGS[game.board[sq]];
                    img.alt = game.board[sq];
                    img.draggable = false;
                    cell.appendChild(img);
                }

                cell.onclick = () => handleCellClick(sq);
                boardEl.appendChild(cell);
            }
        }

        updateTurnInfo();
    }

    function updateTurnInfo() {
        const isMyTurn = game.currentPlayer === settings.playerColor;
        const turnInfo = document.getElementById('turnInfo');
        turnInfo.className = 'turn-info' + (isMyTurn ? ' my-turn' : '') + (game.checkState === 'check' && isMyTurn ? ' check' : '');

        const icon = game.currentPlayer === 'white' ? '♔' : '♚';
        const name = game.currentPlayer === settings.playerColor ? (playerName || 'Du') : '🤖 Computer';
        let turnText = `${icon} ${escapeHtml(name)}`;
        if (game.checkState === 'check') turnText += ' ⚠️ SCHACH!';
        document.getElementById('currentTurnName').innerHTML = turnText;

        document.getElementById('whitePlayer').className = 'player-row' + (game.currentPlayer === 'white' ? ' active' : '');
        document.getElementById('blackPlayer').className = 'player-row' + (game.currentPlayer === 'black' ? ' active' : '');
    }

    // =========================================================================
    // Move Validation (vollstaendige client-seitige Pruefung)
    // =========================================================================

    function parseSquare(sq) {
        return [sq.charCodeAt(0) - 97, parseInt(sq[1]) - 1];
    }

    function toSquare(col, row) {
        return String.fromCharCode(97 + col) + (row + 1);
    }

    function isSquareAttacked(board, square, byColor) {
        const [targetCol, targetRow] = parseSquare(square);

        for (const [sq, piece] of Object.entries(board)) {
            const pieceColor = piece[0] === 'w' ? 'white' : 'black';
            if (pieceColor !== byColor) continue;

            const [col, row] = parseSquare(sq);
            const type = piece[1];

            switch (type) {
                case 'P': {
                    const dir = byColor === 'white' ? 1 : -1;
                    if (Math.abs(col - targetCol) === 1 && targetRow - row === dir) return true;
                    break;
                }
                case 'N': {
                    const dc = Math.abs(col - targetCol);
                    const dr = Math.abs(row - targetRow);
                    if ((dc === 2 && dr === 1) || (dc === 1 && dr === 2)) return true;
                    break;
                }
                case 'B':
                    if (Math.abs(col - targetCol) === Math.abs(row - targetRow) && col !== targetCol) {
                        if (isPathClear(board, sq, square)) return true;
                    }
                    break;
                case 'R':
                    if ((col === targetCol || row === targetRow) && sq !== square) {
                        if (isPathClear(board, sq, square)) return true;
                    }
                    break;
                case 'Q':
                    if ((col === targetCol || row === targetRow || Math.abs(col - targetCol) === Math.abs(row - targetRow)) && sq !== square) {
                        if (isPathClear(board, sq, square)) return true;
                    }
                    break;
                case 'K':
                    if (Math.abs(col - targetCol) <= 1 && Math.abs(row - targetRow) <= 1 && sq !== square) return true;
                    break;
            }
        }
        return false;
    }

    function isPathClear(board, from, to) {
        const [fromCol, fromRow] = parseSquare(from);
        const [toCol, toRow] = parseSquare(to);

        const stepCol = toCol > fromCol ? 1 : (toCol < fromCol ? -1 : 0);
        const stepRow = toRow > fromRow ? 1 : (toRow < fromRow ? -1 : 0);

        let col = fromCol + stepCol;
        let row = fromRow + stepRow;

        while (col !== toCol || row !== toRow) {
            if (board[toSquare(col, row)]) return false;
            col += stepCol;
            row += stepRow;
        }
        return true;
    }

    function findKing(board, color) {
        const kingPiece = color === 'white' ? 'wK' : 'bK';
        for (const [sq, piece] of Object.entries(board)) {
            if (piece === kingPiece) return sq;
        }
        return null;
    }

    function isInCheck(board, color) {
        const kingSq = findKing(board, color);
        if (!kingSq) return false;
        const enemyColor = color === 'white' ? 'black' : 'white';
        return isSquareAttacked(board, kingSq, enemyColor);
    }

    function isMoveLegal(board, from, to, color, castling, enPassant) {
        const testBoard = { ...board };
        const piece = testBoard[from];
        delete testBoard[from];

        // En Passant Schlag simulieren
        if (piece[1] === 'P' && to === enPassant) {
            const [toCol, toRow] = parseSquare(to);
            const dir = color === 'white' ? -1 : 1;
            delete testBoard[toSquare(toCol, toRow + dir)];
        }

        testBoard[to] = piece;
        return !isInCheck(testBoard, color);
    }

    function getSlidingMoves(board, col, row, color, directions) {
        const moves = [];
        for (const [dc, dr] of directions) {
            let nc = col + dc, nr = row + dr;
            while (nc >= 0 && nc <= 7 && nr >= 0 && nr <= 7) {
                const sq = toSquare(nc, nr);
                if (!board[sq]) {
                    moves.push(sq);
                } else {
                    const pieceColor = board[sq][0] === 'w' ? 'white' : 'black';
                    if (pieceColor !== color) moves.push(sq);
                    break;
                }
                nc += dc; nr += dr;
            }
        }
        return moves;
    }

    function getValidMoves(board, from, castling, enPassant) {
        if (!board[from]) return [];

        const piece = board[from];
        const color = piece[0] === 'w' ? 'white' : 'black';
        const type = piece[1];
        const [col, row] = parseSquare(from);
        let moves = [];

        switch (type) {
            case 'P': {
                const dir = color === 'white' ? 1 : -1;
                const startRow = color === 'white' ? 1 : 6;
                // Vorwaerts
                const oneStep = toSquare(col, row + dir);
                if (row + dir >= 0 && row + dir <= 7 && !board[oneStep]) {
                    moves.push(oneStep);
                    if (row === startRow) {
                        const twoStep = toSquare(col, row + 2 * dir);
                        if (!board[twoStep]) moves.push(twoStep);
                    }
                }
                // Diagonal schlagen
                for (const dc of [-1, 1]) {
                    if (col + dc < 0 || col + dc > 7 || row + dir < 0 || row + dir > 7) continue;
                    const diagSq = toSquare(col + dc, row + dir);
                    if (board[diagSq] && (board[diagSq][0] === 'w' ? 'white' : 'black') !== color) {
                        moves.push(diagSq);
                    }
                    if (diagSq === enPassant) moves.push(diagSq);
                }
                break;
            }
            case 'N': {
                const jumps = [[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]];
                for (const [dc, dr] of jumps) {
                    const nc = col + dc, nr = row + dr;
                    if (nc >= 0 && nc <= 7 && nr >= 0 && nr <= 7) {
                        const sq = toSquare(nc, nr);
                        if (!board[sq] || (board[sq][0] === 'w' ? 'white' : 'black') !== color) {
                            moves.push(sq);
                        }
                    }
                }
                break;
            }
            case 'B':
                moves = getSlidingMoves(board, col, row, color, [[1,1],[1,-1],[-1,1],[-1,-1]]);
                break;
            case 'R':
                moves = getSlidingMoves(board, col, row, color, [[0,1],[0,-1],[1,0],[-1,0]]);
                break;
            case 'Q':
                moves = getSlidingMoves(board, col, row, color, [[0,1],[0,-1],[1,0],[-1,0],[1,1],[1,-1],[-1,1],[-1,-1]]);
                break;
            case 'K': {
                for (let dc = -1; dc <= 1; dc++) {
                    for (let dr = -1; dr <= 1; dr++) {
                        if (dc === 0 && dr === 0) continue;
                        const nc = col + dc, nr = row + dr;
                        if (nc >= 0 && nc <= 7 && nr >= 0 && nr <= 7) {
                            const sq = toSquare(nc, nr);
                            if (!board[sq] || (board[sq][0] === 'w' ? 'white' : 'black') !== color) {
                                moves.push(sq);
                            }
                        }
                    }
                }
                // Rochade
                const enemyColor = color === 'white' ? 'black' : 'white';
                const homeRow = color === 'white' ? 0 : 7;
                const prefix = color === 'white' ? 'w' : 'b';

                if (row === homeRow && col === 4 && !isInCheck(board, color)) {
                    // Koenigsseite
                    if (castling[prefix + 'K']) {
                        const f = toSquare(5, homeRow), g = toSquare(6, homeRow);
                        if (!board[f] && !board[g] &&
                            !isSquareAttacked(board, f, enemyColor) && !isSquareAttacked(board, g, enemyColor)) {
                            moves.push(g);
                        }
                    }
                    // Damenseite
                    if (castling[prefix + 'Q']) {
                        const d = toSquare(3, homeRow), c = toSquare(2, homeRow), b = toSquare(1, homeRow);
                        if (!board[d] && !board[c] && !board[b] &&
                            !isSquareAttacked(board, d, enemyColor) && !isSquareAttacked(board, c, enemyColor)) {
                            moves.push(c);
                        }
                    }
                }
                break;
            }
        }

        // Nur legale Zuege (lassen Koenig nicht im Schach)
        return moves.filter(to => isMoveLegal(board, from, to, color, castling, enPassant));
    }

    function checkGameEnd(board, color, castling, enPassant) {
        for (const [sq, piece] of Object.entries(board)) {
            const pieceColor = piece[0] === 'w' ? 'white' : 'black';
            if (pieceColor !== color) continue;
            const moves = getValidMoves(board, sq, castling, enPassant);
            if (moves.length > 0) return null;
        }
        return isInCheck(board, color) ? 'checkmate' : 'stalemate';
    }

    // =========================================================================
    // FEN Generator
    // =========================================================================

    function boardToFEN() {
        let fen = '';
        for (let r = 7; r >= 0; r--) {
            let empty = 0;
            for (let c = 0; c < 8; c++) {
                const sq = toSquare(c, r);
                const piece = game.board[sq];
                if (piece) {
                    if (empty > 0) { fen += empty; empty = 0; }
                    fen += PIECE_FEN[piece];
                } else {
                    empty++;
                }
            }
            if (empty > 0) fen += empty;
            if (r > 0) fen += '/';
        }

        // Aktive Farbe
        fen += ' ' + (game.currentPlayer === 'white' ? 'w' : 'b');

        // Rochade
        let castleStr = '';
        if (game.castling.wK) castleStr += 'K';
        if (game.castling.wQ) castleStr += 'Q';
        if (game.castling.bK) castleStr += 'k';
        if (game.castling.bQ) castleStr += 'q';
        fen += ' ' + (castleStr || '-');

        // En Passant
        fen += ' ' + (game.enPassant || '-');

        // Halbzuege + Vollzuege
        fen += ' ' + game.halfMoves;
        fen += ' ' + game.fullMoves;

        return fen;
    }

    // =========================================================================
    // Move Execution
    // =========================================================================

    function handleCellClick(sq) {
        if (game.status !== 'playing') return;
        if (game.currentPlayer !== settings.playerColor) return;
        if (pendingPromotion) return;

        const piece = game.board[sq];
        const myPrefix = settings.playerColor === 'white' ? 'w' : 'b';

        // Gueltiger Zug?
        if (game.validMoves.includes(sq) && game.selectedCell) {
            executeMove(game.selectedCell, sq);
            return;
        }

        // Eigene Figur auswaehlen
        if (piece && piece[0] === myPrefix) {
            game.selectedCell = sq;
            game.validMoves = getValidMoves(game.board, sq, game.castling, game.enPassant);
            renderBoard();
        } else {
            game.selectedCell = null;
            game.validMoves = [];
            renderBoard();
        }
    }

    function executeMove(from, to, promotionPiece) {
        const piece = game.board[from];
        if (!piece) return;

        const color = piece[0] === 'w' ? 'white' : 'black';
        const type = piece[1];
        const [fromCol, fromRow] = parseSquare(from);
        const [toCol, toRow] = parseSquare(to);

        // Bauernumwandlung pruefen (Spieler)
        const promoRow = color === 'white' ? 7 : 0;
        if (type === 'P' && toRow === promoRow && !promotionPiece && color === settings.playerColor) {
            pendingPromotion = { from, to };
            showPromotionModal(color);
            return;
        }

        // Snapshot fuer Undo speichern
        game.history.push({
            board: { ...game.board },
            currentPlayer: game.currentPlayer,
            castling: { ...game.castling },
            enPassant: game.enPassant,
            halfMoves: game.halfMoves,
            fullMoves: game.fullMoves,
            checkState: game.checkState,
            lastMove: game.lastMove ? { ...game.lastMove } : null,
            moveNotation: [...game.moveNotation]
        });

        // Zug-Notation vorbereiten
        const captured = !!game.board[to] || (type === 'P' && to === game.enPassant);
        let notation = buildMoveNotation(piece, from, to, captured, promotionPiece);

        // Zug ausfuehren
        delete game.board[from];

        // Rochade
        if (type === 'K' && Math.abs(toCol - fromCol) === 2) {
            const homeRow = color === 'white' ? 0 : 7;
            if (toCol === 6) { // Koenigsseite
                game.board[toSquare(5, homeRow)] = game.board[toSquare(7, homeRow)];
                delete game.board[toSquare(7, homeRow)];
                notation = 'O-O';
            } else { // Damenseite
                game.board[toSquare(3, homeRow)] = game.board[toSquare(0, homeRow)];
                delete game.board[toSquare(0, homeRow)];
                notation = 'O-O-O';
            }
        }

        // En Passant schlagen
        if (type === 'P' && to === game.enPassant) {
            const dir = color === 'white' ? -1 : 1;
            delete game.board[toSquare(toCol, toRow + dir)];
        }

        // En Passant Feld setzen
        game.enPassant = null;
        if (type === 'P' && Math.abs(toRow - fromRow) === 2) {
            game.enPassant = toSquare(fromCol, (fromRow + toRow) / 2);
        }

        // Figur setzen (mit Umwandlung)
        if (promotionPiece) {
            const prefix = color === 'white' ? 'w' : 'b';
            game.board[to] = prefix + promotionPiece;
        } else {
            game.board[to] = piece;
        }

        // Rochaderechte
        const prefix = color === 'white' ? 'w' : 'b';
        if (type === 'K') {
            game.castling[prefix + 'K'] = false;
            game.castling[prefix + 'Q'] = false;
        }
        if (type === 'R') {
            if (from === 'a1') game.castling.wQ = false;
            if (from === 'h1') game.castling.wK = false;
            if (from === 'a8') game.castling.bQ = false;
            if (from === 'h8') game.castling.bK = false;
        }
        // Turm geschlagen -> Rochaderecht weg
        if (to === 'a1') game.castling.wQ = false;
        if (to === 'h1') game.castling.wK = false;
        if (to === 'a8') game.castling.bQ = false;
        if (to === 'h8') game.castling.bK = false;

        // Halbzug-Zaehler
        if (type === 'P' || captured) {
            game.halfMoves = 0;
        } else {
            game.halfMoves++;
        }

        // Naechster Spieler
        const nextPlayer = color === 'white' ? 'black' : 'white';
        if (nextPlayer === 'white') game.fullMoves++;

        game.currentPlayer = nextPlayer;
        game.lastMove = { from, to };
        game.selectedCell = null;
        game.validMoves = [];

        // Schach pruefen
        game.checkState = isInCheck(game.board, nextPlayer) ? 'check' : null;

        // Notation finalisieren
        if (game.checkState === 'check') notation += '+';

        // Spielende pruefen
        const gameEnd = checkGameEnd(game.board, nextPlayer, game.castling, game.enPassant);

        if (gameEnd === 'checkmate') {
            notation = notation.replace(/\+$/, '#');
            game.winner = color;
            game.winReason = 'checkmate';
            game.status = 'finished';
        } else if (gameEnd === 'stalemate') {
            game.winner = null;
            game.winReason = 'stalemate';
            game.status = 'finished';
        }

        // Notation speichern
        game.moveNotation.push(notation);
        updateMoveList();

        // Undo Button
        document.getElementById('undoBtn').disabled = game.history.length === 0;

        renderBoard();

        if (game.status === 'finished') {
            setTimeout(() => showResult(), 800);
            return;
        }

        // KI-Zug anfordern
        if (game.currentPlayer !== settings.playerColor) {
            setTimeout(() => requestAIMove(), 300);
        }
    }

    function buildMoveNotation(piece, from, to, captured, promotion) {
        const type = piece[1];
        const pieceSymbols = { K: 'K', Q: 'D', R: 'T', B: 'L', N: 'S', P: '' };
        let notation = pieceSymbols[type] || '';

        if (type === 'P' && captured) {
            notation += from[0]; // Spalte bei Bauernschlag
        }

        if (captured) notation += 'x';
        notation += to;

        if (promotion) {
            const promoSymbols = { Q: 'D', R: 'T', B: 'L', N: 'S' };
            notation += '=' + (promoSymbols[promotion] || promotion);
        }

        return notation;
    }

    function updateMoveList() {
        const list = document.getElementById('moveList');
        list.innerHTML = '';

        for (let i = 0; i < game.moveNotation.length; i += 2) {
            const moveNum = Math.floor(i / 2) + 1;
            const whiteMove = game.moveNotation[i] || '';
            const blackMove = game.moveNotation[i + 1] || '';

            const row = document.createElement('div');
            row.className = 'chess-move-row';
            row.innerHTML = `
                <span class="chess-move-num">${moveNum}.</span>
                <span class="chess-move-white">${whiteMove}</span>
                <span class="chess-move-black">${blackMove}</span>
            `;
            list.appendChild(row);
        }

        list.scrollTop = list.scrollHeight;
    }

    // =========================================================================
    // AI (Stockfish)
    // =========================================================================

    function requestAIMove() {
        if (game.status !== 'playing' || !stockfish || !stockfishLoaded) return;

        document.getElementById('thinkingIndicator').classList.add('active');

        const fen = boardToFEN();
        stockfish.getBestMove(fen, (uciMove) => {
            document.getElementById('thinkingIndicator').classList.remove('active');

            if (game.status !== 'playing') return;

            const from = uciMove.substring(0, 2);
            const to = uciMove.substring(2, 4);
            const promotion = uciMove.length > 4 ? uciMove[4].toUpperCase() : null;

            executeMove(from, to, promotion);
        });
    }

    // =========================================================================
    // Promotion
    // =========================================================================

    function showPromotionModal(color) {
        const modal = document.getElementById('promoModal');
        const choices = document.getElementById('promoChoices');
        const prefix = color === 'white' ? 'w' : 'b';

        choices.innerHTML = ['Q', 'R', 'B', 'N'].map(p =>
            `<button class="promo-btn" onclick="promoteTo('${p}')"><img src="${CHESS_PIECE_SVGS[prefix + p]}" alt="${p}" draggable="false"></button>`
        ).join('');

        modal.classList.add('active');
    }

    function promoteTo(piece) {
        document.getElementById('promoModal').classList.remove('active');
        if (!pendingPromotion) return;

        const { from, to } = pendingPromotion;
        pendingPromotion = null;
        executeMove(from, to, piece);
    }

    // =========================================================================
    // Undo
    // =========================================================================

    function undoMove() {
        if (game.history.length === 0 || game.status !== 'playing') return;

        // Wenn Computer gerade denkt, stoppen
        if (stockfish) stockfish.stop();
        document.getElementById('thinkingIndicator').classList.remove('active');

        // 2 Zuege zuruecknehmen (Spieler + KI), ausser am Spielanfang
        const undoCount = game.currentPlayer === settings.playerColor ? 2 : 1;

        for (let i = 0; i < undoCount && game.history.length > 0; i++) {
            const snapshot = game.history.pop();
            game.board = snapshot.board;
            game.currentPlayer = snapshot.currentPlayer;
            game.castling = snapshot.castling;
            game.enPassant = snapshot.enPassant;
            game.halfMoves = snapshot.halfMoves;
            game.fullMoves = snapshot.fullMoves;
            game.checkState = snapshot.checkState;
            game.lastMove = snapshot.lastMove;
            game.moveNotation = snapshot.moveNotation;
        }

        game.selectedCell = null;
        game.validMoves = [];
        pendingPromotion = null;

        document.getElementById('undoBtn').disabled = game.history.length === 0;
        updateMoveList();
        renderBoard();

        // Wenn es jetzt Computer-Zug ist, KI anfordern
        if (game.currentPlayer !== settings.playerColor) {
            setTimeout(() => requestAIMove(), 300);
        }
    }

    // =========================================================================
    // Resign / Result
    // =========================================================================

    function resignGame() {
        if (game.status !== 'playing') return;
        if (!confirm('Wirklich aufgeben?')) return;

        if (stockfish) stockfish.stop();
        document.getElementById('thinkingIndicator').classList.remove('active');

        game.winner = settings.playerColor === 'white' ? 'black' : 'white';
        game.winReason = 'resignation';
        game.status = 'finished';
        showResult();
    }

    function showResult() {
        showScreen('result');

        const icon = document.getElementById('resultIcon');
        const text = document.getElementById('winnerText');
        const reason = document.getElementById('winReason');

        if (game.winReason === 'stalemate') {
            icon.textContent = '🤝';
            text.textContent = 'Patt - Unentschieden!';
            reason.textContent = 'Keine legalen Zuege mehr moeglich';
        } else if (game.winner === settings.playerColor) {
            icon.textContent = '🏆';
            text.textContent = 'Du hast gewonnen!';
            const reasons = { checkmate: 'Schachmatt!', resignation: 'Computer hat aufgegeben' };
            reason.textContent = reasons[game.winReason] || '';
        } else {
            icon.textContent = '😔';
            text.textContent = 'Computer gewinnt';
            const reasons = { checkmate: 'Schachmatt!', resignation: 'Du hast aufgegeben' };
            reason.textContent = reasons[game.winReason] || '';
        }
    }

    function restartGame() {
        startGame();
    }

    function backToSetup() {
        game.status = 'setup';
        if (settings.playerColor === 'white' || settings.playerColor === 'black') {
            // Behalte Farbe
        } else {
            settings.playerColor = 'white';
        }
        showScreen('setup');
    }

    // =========================================================================
    // Utilities
    // =========================================================================

    function showScreen(name) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById(name + 'Screen').classList.add('active');
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

    // =========================================================================
    // Init
    // =========================================================================

    document.getElementById('playerNameInput')?.addEventListener('keypress', e => {
        if (e.key === 'Enter') setPlayerName();
    });

    initSetup();
    </script>
</body>
</html>
