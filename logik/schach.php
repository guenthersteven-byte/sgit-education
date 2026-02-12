<?php
/**
 * ============================================================================
 * sgiT Education - Schach Puzzles v1.0
 * ============================================================================
 * 
 * Interaktive Schach-Puzzles mit Matt-in-X Aufgaben:
 * - Matt in 1 (Anfänger)
 * - Matt in 2 (Fortgeschritten)
 * - Matt in 3 (Experte)
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
require_once dirname(__DIR__) . '/includes/version.php';

// User-Daten aus Session
$userAge = $_SESSION['user_age'] ?? 10;
$userName = $_SESSION['child_name'] ?? 'Schach-Fan';
$childId = $_SESSION['wallet_child_id'] ?? 0;

// Schwierigkeit basierend auf Alter
function getDifficultyConfig($age) {
    if ($age <= 10) {
        return ['maxMoves' => 1, 'name' => 'Anfänger', 'sats' => [20, 30, 40]];
    } elseif ($age <= 14) {
        return ['maxMoves' => 2, 'name' => 'Fortgeschritten', 'sats' => [30, 45, 60]];
    } else {
        return ['maxMoves' => 3, 'name' => 'Experte', 'sats' => [40, 60, 80]];
    }
}

$diffConfig = getDifficultyConfig($userAge);

// Session für Schach-Runde
if (!isset($_SESSION['chess_session']) || isset($_GET['new'])) {
    $_SESSION['chess_session'] = [
        'puzzles_solved' => 0,
        'total_sats' => 0,
        'start_time' => time(),
        'current_puzzle' => null,
        'hints_used' => 0
    ];
}
$chessSession = &$_SESSION['chess_session'];

/**
 * Schach-Puzzle Sammlung
 * Format: FEN-ähnlich, aber vereinfacht
 * pieces: Array von [piece, row, col] - row/col 0-7
 * solution: Array von Zügen [fromRow, fromCol, toRow, toCol]
 * playerColor: 'white' oder 'black'
 */
$puzzles = [
    // === MATT IN 1 ===
    [
        'id' => 'm1_01',
        'name' => 'Damenmatt',
        'difficulty' => 1,
        'moves' => 1,
        'playerColor' => 'white',
        'pieces' => [
            ['K', 0, 4], // Weißer König e1
            ['Q', 3, 7], // Weiße Dame h4
            ['k', 7, 4], // Schwarzer König e8
            ['p', 6, 3], // Schwarzer Bauer d7
            ['p', 6, 5], // Schwarzer Bauer f7
        ],
        'solution' => [[3, 7, 7, 3]], // Dame h4-d8 Matt
        'hint' => 'Die Dame kann diagonal angreifen!'
    ],
    [
        'id' => 'm1_02',
        'name' => 'Turmmatt',
        'difficulty' => 1,
        'moves' => 1,
        'playerColor' => 'white',
        'pieces' => [
            ['K', 0, 6], // Weißer König g1
            ['R', 0, 0], // Weißer Turm a1
            ['k', 7, 7], // Schwarzer König h8
        ],
        'solution' => [[0, 0, 7, 0]], // Turm a1-a8 Matt
        'hint' => 'Der Turm kontrolliert die ganze Linie!'
    ],
    [
        'id' => 'm1_03',
        'name' => 'Läufermatt',
        'difficulty' => 1,
        'moves' => 1,
        'playerColor' => 'white',
        'pieces' => [
            ['K', 1, 6], // Weißer König g2
            ['B', 3, 3], // Weißer Läufer d4
            ['B', 4, 4], // Weißer Läufer e5
            ['k', 7, 7], // Schwarzer König h8
            ['p', 6, 6], // Schwarzer Bauer g7
            ['p', 6, 7], // Schwarzer Bauer h7
        ],
        'solution' => [[4, 4, 6, 6]], // Läufer e5-g7 Matt
        'hint' => 'Beide Läufer arbeiten zusammen!'
    ],
    [
        'id' => 'm1_04',
        'name' => 'Springermatt',
        'difficulty' => 1,
        'moves' => 1,
        'playerColor' => 'white',
        'pieces' => [
            ['K', 0, 4], // Weißer König e1
            ['N', 4, 5], // Weißer Springer f5
            ['R', 7, 0], // Weißer Turm a8
            ['k', 7, 7], // Schwarzer König h8
            ['p', 6, 6], // Schwarzer Bauer g7
            ['p', 6, 7], // Schwarzer Bauer h7
        ],
        'solution' => [[4, 5, 6, 6]], // Springer f5-g7 Matt
        'hint' => 'Der Springer kann über Figuren springen!'
    ],
    [
        'id' => 'm1_05',
        'name' => 'Grundreihenmatt',
        'difficulty' => 1,
        'moves' => 1,
        'playerColor' => 'white',
        'pieces' => [
            ['K', 0, 6], // Weißer König g1
            ['R', 3, 4], // Weißer Turm e4
            ['k', 7, 6], // Schwarzer König g8
            ['r', 7, 0], // Schwarzer Turm a8
            ['p', 6, 5], // Schwarzer Bauer f7
            ['p', 6, 6], // Schwarzer Bauer g7
            ['p', 6, 7], // Schwarzer Bauer h7
        ],
        'solution' => [[3, 4, 7, 4]], // Turm e4-e8 Matt
        'hint' => 'Die Bauern blockieren den eigenen König!'
    ],
    
    // === MATT IN 2 ===
    [
        'id' => 'm2_01',
        'name' => 'Opfermatt',
        'difficulty' => 2,
        'moves' => 2,
        'playerColor' => 'white',
        'pieces' => [
            ['K', 0, 6], // Weißer König g1
            ['Q', 3, 7], // Weiße Dame h4
            ['R', 0, 5], // Weißer Turm f1
            ['k', 7, 6], // Schwarzer König g8
            ['p', 6, 5], // Schwarzer Bauer f7
            ['p', 6, 6], // Schwarzer Bauer g7
            ['p', 6, 7], // Schwarzer Bauer h7
        ],
        'solution' => [[3, 7, 6, 7], [0, 5, 7, 5]], // Qh4-h7+ Kxh7, Rf1-f8 Matt
        'hint' => 'Manchmal muss man etwas opfern!'
    ],
    [
        'id' => 'm2_02',
        'name' => 'Ersticktes Matt',
        'difficulty' => 2,
        'moves' => 2,
        'playerColor' => 'white',
        'pieces' => [
            ['K', 0, 4], // Weißer König e1
            ['Q', 4, 4], // Weiße Dame e5
            ['N', 4, 5], // Weißer Springer f5
            ['k', 7, 6], // Schwarzer König g8
            ['r', 7, 5], // Schwarzer Turm f8
            ['p', 6, 6], // Schwarzer Bauer g7
            ['p', 6, 7], // Schwarzer Bauer h7
        ],
        'solution' => [[4, 4, 6, 6], [4, 5, 6, 7]], // Qe5-g7+ Kh8, Nf5-h7 Matt
        'hint' => 'Der Springer ist tödlich in engen Räumen!'
    ],
];

// Puzzle auswählen basierend auf Schwierigkeit
$difficulty = isset($_GET['diff']) ? intval($_GET['diff']) : 1;
$difficulty = max(1, min(3, $difficulty));

// Filter Puzzles nach Schwierigkeit (moves = difficulty level)
$availablePuzzles = array_filter($puzzles, function($p) use ($difficulty) {
    return $p['moves'] <= $difficulty;
});

// Aktuelles Puzzle aus Session oder neues laden
if (!$chessSession['current_puzzle'] || isset($_GET['new'])) {
    $filteredPuzzles = array_values($availablePuzzles);
    $randomIndex = array_rand($filteredPuzzles);
    $chessSession['current_puzzle'] = $filteredPuzzles[$randomIndex];
    $chessSession['current_puzzle']['start_time'] = time();
    $chessSession['current_puzzle']['moves_made'] = [];
    $chessSession['hints_used'] = 0;
}

$puzzle = $chessSession['current_puzzle'];
$satsReward = $diffConfig['sats'][$puzzle['moves'] - 1] ?? $diffConfig['sats'][0];

$diffNames = ['', 'Matt in 1', 'Matt in 2', 'Matt in 3'];
$diffEmoji = ['', '🟢', '🟡', '🔴'];

// Figuren-Mapping für Unicode
$pieceSymbols = [
    'K' => '♔', 'Q' => '♕', 'R' => '♖', 'B' => '♗', 'N' => '♘', 'P' => '♙',
    'k' => '♚', 'q' => '♛', 'r' => '♜', 'b' => '♝', 'n' => '♞', 'p' => '♟'
];

// Figuren-Namen für Tutorial
$pieceNames = [
    'K' => 'König', 'Q' => 'Dame', 'R' => 'Turm', 'B' => 'Läufer', 'N' => 'Springer', 'P' => 'Bauer',
    'k' => 'König', 'q' => 'Dame', 'r' => 'Turm', 'b' => 'Läufer', 'n' => 'Springer', 'p' => 'Bauer'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>♟️ Schach Puzzles - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bg: #0d1f02;
            --card-bg: #1e3a08;
            --text: #ffffff;
            --text-muted: #a0a0a0;
            --error: #ff4444;
            --success: #43D240;
            --light-square: #f0d9b5;
            --dark-square: #b58863;
            --highlight: rgba(67, 210, 64, 0.5);
            --selected: rgba(255, 255, 0, 0.5);
            --possible: rgba(67, 210, 64, 0.3);
        }
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, var(--primary) 100%);
            min-height: 100vh;
            color: var(--text);
            padding: 15px;
        }
        .container { max-width: 700px; margin: 0 auto; text-align: center; }
        .back-link {
            color: var(--accent);
            text-decoration: none;
            display: inline-block;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }
        .back-link:hover { text-decoration: underline; }
        header h1 { font-size: 1.8rem; margin-bottom: 5px; }
        header h1 span { color: var(--accent); }
        .subtitle { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 10px; }
        
        .info-bar {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }
        .info-item {
            background: var(--card-bg);
            padding: 8px 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .info-item span { color: var(--accent); font-weight: 600; }
        
        .puzzle-title {
            background: var(--card-bg);
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: inline-block;
        }
        .puzzle-title h2 { font-size: 1.2rem; margin-bottom: 4px; }
        .puzzle-title p { color: var(--text-muted); font-size: 0.85rem; }
        
        .chess-container {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .chessboard {
            display: grid;
            grid-template-columns: repeat(8, 50px);
            grid-template-rows: repeat(8, 50px);
            border: 4px solid var(--primary);
            border-radius: 4px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        }
        .square {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            cursor: pointer;
            transition: all 0.15s;
            user-select: none;
        }
        .square.light { background: var(--light-square); }
        .square.dark { background: var(--dark-square); }
        .square.selected { background: var(--selected) !important; }
        .square.possible { 
            position: relative;
        }
        .square.possible::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            background: var(--possible);
            border-radius: 50%;
        }
        .square.possible.has-piece::after {
            width: 100%;
            height: 100%;
            background: transparent;
            border: 4px solid var(--accent);
            border-radius: 0;
        }
        .square.highlight { background: var(--highlight) !important; }
        .square.last-move { background: rgba(255, 255, 0, 0.3) !important; }
        .square:hover:not(.selected) { filter: brightness(1.1); }
        
        .piece { pointer-events: none; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        .piece.white { filter: drop-shadow(0 1px 1px rgba(0,0,0,0.5)); }
        .piece.black { filter: drop-shadow(0 1px 1px rgba(0,0,0,0.3)); }
        
        .move-indicator {
            background: var(--card-bg);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 1rem;
        }
        .move-indicator.your-turn { border: 2px solid var(--accent); }
        .move-count { 
            font-size: 1.5rem; 
            font-weight: bold; 
            color: var(--accent);
            margin: 0 5px;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        .btn {
            background: var(--accent);
            color: var(--primary);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(67, 210, 64, 0.3); }
        .btn.secondary { background: var(--card-bg); color: var(--text); border: 2px solid var(--accent); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        
        .result-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 100;
            justify-content: center;
            align-items: center;
        }
        .result-overlay.show { display: flex; }
        .result-box {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px 40px;
            text-align: center;
            border: 3px solid var(--accent);
            animation: popIn 0.3s ease;
        }
        @keyframes popIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .result-icon { font-size: 4rem; margin-bottom: 10px; }
        .result-title { font-size: 1.6rem; margin-bottom: 8px; }
        .result-sats { font-size: 2rem; color: var(--accent); font-weight: bold; margin: 10px 0; }
        .result-sub { color: var(--text-muted); font-size: 0.9rem; }
        
        /* Tutorial Tooltip */
        .tutorial-box {
            background: var(--card-bg);
            border: 2px solid var(--accent);
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            text-align: left;
        }
        .tutorial-box h3 { color: var(--accent); margin-bottom: 8px; font-size: 1rem; }
        .tutorial-box p { font-size: 0.9rem; color: var(--text-muted); }
        .piece-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
            justify-content: center;
        }
        .piece-legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
        }
        .piece-legend-item .symbol { font-size: 1.5rem; }
        
        /* Koordinaten */
        .board-wrapper { position: relative; display: inline-block; }
        .coords-row, .coords-col {
            position: absolute;
            display: flex;
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
        }
        .coords-row {
            flex-direction: column;
            left: -18px;
            top: 0;
            height: 400px;
        }
        .coords-col {
            bottom: -20px;
            left: 0;
            width: 400px;
        }
        .coords-row span, .coords-col span {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .coords-row span { width: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Zurück zu Logik & Rätsel</a>
        
        <header>
            <h1>♟️ <span>Schach</span> Puzzles</h1>
            <p class="subtitle"><?php echo $diffConfig['name']; ?>-Level für <?php echo htmlspecialchars($userName); ?></p>
        </header>
        
        <div class="info-bar">
            <div class="info-item">
                <?php echo $diffEmoji[$puzzle['moves']]; ?> <span><?php echo $diffNames[$puzzle['moves']]; ?></span>
            </div>
            <div class="info-item">
                🌟 <span><?php echo $satsReward; ?> Sats</span>
            </div>
            <div class="info-item">
                ✅ Gelöst: <span id="solved-count"><?php echo $chessSession['puzzles_solved']; ?></span>
            </div>
        </div>
        
        <div class="puzzle-title">
            <h2>📋 <?php echo htmlspecialchars($puzzle['name']); ?></h2>
            <p>Finde den Gewinnzug für <?php echo $puzzle['playerColor'] === 'white' ? 'Weiß ♔' : 'Schwarz ♚'; ?>!</p>
        </div>
        
        <div class="chess-container">
            <div class="board-wrapper">
                <div class="coords-row">
                    <span>8</span><span>7</span><span>6</span><span>5</span>
                    <span>4</span><span>3</span><span>2</span><span>1</span>
                </div>
                <div class="coords-col">
                    <span>a</span><span>b</span><span>c</span><span>d</span>
                    <span>e</span><span>f</span><span>g</span><span>h</span>
                </div>
                <div class="chessboard" id="chessboard">
                    <?php
                    // Board initialisieren
                    for ($row = 7; $row >= 0; $row--) {
                        for ($col = 0; $col < 8; $col++) {
                            $isLight = ($row + $col) % 2 === 1;
                            $squareClass = $isLight ? 'light' : 'dark';
                            echo "<div class='square $squareClass' data-row='$row' data-col='$col'></div>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="move-indicator your-turn" id="moveIndicator">
            Du bist dran! Ziehe mit <strong><?php echo $puzzle['playerColor'] === 'white' ? 'Weiß ♔' : 'Schwarz ♚'; ?></strong>
            <br>
            <small>Zug <span class="move-count" id="currentMove">1</span> von <span class="move-count"><?php echo $puzzle['moves']; ?></span></small>
        </div>
        
        <div class="actions">
            <button class="btn secondary" onclick="showHint()">💡 Hinweis</button>
            <button class="btn secondary" onclick="resetPuzzle()">↩️ Neustart</button>
            <button class="btn" onclick="location.href='schach.php?new=1'">🔄 Neues Puzzle</button>
        </div>
        
        <!-- Tutorial für Anfänger -->
        <?php if ($userAge <= 12): ?>
        <div class="tutorial-box">
            <h3>💡 Schach-Figuren</h3>
            <p>Klicke auf eine Figur um mögliche Züge zu sehen. Klicke dann auf ein grünes Feld zum Ziehen.</p>
            <div class="piece-legend">
                <div class="piece-legend-item"><span class="symbol">♔</span> König</div>
                <div class="piece-legend-item"><span class="symbol">♕</span> Dame</div>
                <div class="piece-legend-item"><span class="symbol">♖</span> Turm</div>
                <div class="piece-legend-item"><span class="symbol">♗</span> Läufer</div>
                <div class="piece-legend-item"><span class="symbol">♘</span> Springer</div>
                <div class="piece-legend-item"><span class="symbol">♙</span> Bauer</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Erfolgs-Overlay -->
    <div class="result-overlay" id="result-overlay">
        <div class="result-box">
            <div class="result-icon" id="result-icon">🎉</div>
            <div class="result-title" id="result-title">Schachmatt!</div>
            <div class="result-sats" id="result-sats">+<?php echo $satsReward; ?> Sats</div>
            <div class="result-sub" id="result-sub"></div>
            <div class="actions" style="margin-top:20px;">
                <button class="btn" onclick="location.href='schach.php?new=1'">▶️ Nächstes Puzzle</button>
                <button class="btn secondary" onclick="location.href='index.php'">← Zurück</button>
            </div>
        </div>
    </div>
    
    <script>
        // Puzzle-Daten vom Server
        const puzzleData = <?php echo json_encode($puzzle); ?>;
        const solution = puzzleData.solution;
        const playerColor = puzzleData.playerColor;
        const satsReward = <?php echo $satsReward; ?>;
        const childId = <?php echo $childId; ?>;
        const maxMoves = puzzleData.moves;
        
        // Figuren-Symbole
        const pieceSymbols = {
            'K': '♔', 'Q': '♕', 'R': '♖', 'B': '♗', 'N': '♘', 'P': '♙',
            'k': '♚', 'q': '♛', 'r': '♜', 'b': '♝', 'n': '♞', 'p': '♟'
        };
        
        // Spielzustand
        let board = Array(8).fill(null).map(() => Array(8).fill(null));
        let selectedSquare = null;
        let possibleMoves = [];
        let movesMade = [];
        let hintsUsed = 0;
        let currentMoveIndex = 0;
        let gameOver = false;
        
        // Board initialisieren
        function initBoard() {
            board = Array(8).fill(null).map(() => Array(8).fill(null));
            puzzleData.pieces.forEach(p => {
                board[p[1]][p[2]] = p[0];
            });
            renderBoard();
        }
        
        // Board rendern
        function renderBoard() {
            document.querySelectorAll('.square').forEach(sq => {
                const row = parseInt(sq.dataset.row);
                const col = parseInt(sq.dataset.col);
                const piece = board[row][col];
                
                sq.innerHTML = '';
                sq.classList.remove('selected', 'possible', 'has-piece', 'highlight', 'last-move');
                
                if (piece) {
                    const pieceEl = document.createElement('span');
                    pieceEl.className = 'piece ' + (piece === piece.toUpperCase() ? 'white' : 'black');
                    pieceEl.textContent = pieceSymbols[piece];
                    sq.appendChild(pieceEl);
                }
            });
            
            // Markiere letzten Zug
            if (movesMade.length > 0) {
                const lastMove = movesMade[movesMade.length - 1];
                highlightSquare(lastMove.from[0], lastMove.from[1], 'last-move');
                highlightSquare(lastMove.to[0], lastMove.to[1], 'last-move');
            }
        }
        
        function highlightSquare(row, col, className) {
            const sq = document.querySelector(`.square[data-row="${row}"][data-col="${col}"]`);
            if (sq) sq.classList.add(className);
        }
        
        // Klick auf Feld
        document.querySelectorAll('.square').forEach(sq => {
            sq.addEventListener('click', () => {
                if (gameOver) return;
                
                const row = parseInt(sq.dataset.row);
                const col = parseInt(sq.dataset.col);
                const piece = board[row][col];
                
                // Wenn ein Feld ausgewählt ist und auf möglichen Zug geklickt wird
                if (selectedSquare && possibleMoves.some(m => m[0] === row && m[1] === col)) {
                    makeMove(selectedSquare.row, selectedSquare.col, row, col);
                    clearSelection();
                    return;
                }
                
                // Wenn auf eigene Figur geklickt wird
                if (piece && isOwnPiece(piece)) {
                    clearSelection();
                    selectedSquare = { row, col };
                    sq.classList.add('selected');
                    showPossibleMoves(row, col, piece);
                } else {
                    clearSelection();
                }
            });
        });
        
        function isOwnPiece(piece) {
            if (playerColor === 'white') {
                return piece === piece.toUpperCase();
            } else {
                return piece === piece.toLowerCase();
            }
        }
        
        function isEnemyPiece(piece) {
            if (!piece) return false;
            return !isOwnPiece(piece);
        }
        
        function clearSelection() {
            selectedSquare = null;
            possibleMoves = [];
            document.querySelectorAll('.square').forEach(s => {
                s.classList.remove('selected', 'possible', 'has-piece');
            });
        }
        
        // Mögliche Züge berechnen (vereinfacht)
        function showPossibleMoves(row, col, piece) {
            possibleMoves = [];
            const type = piece.toUpperCase();
            
            switch (type) {
                case 'K': // König
                    addKingMoves(row, col);
                    break;
                case 'Q': // Dame
                    addRookMoves(row, col);
                    addBishopMoves(row, col);
                    break;
                case 'R': // Turm
                    addRookMoves(row, col);
                    break;
                case 'B': // Läufer
                    addBishopMoves(row, col);
                    break;
                case 'N': // Springer
                    addKnightMoves(row, col);
                    break;
                case 'P': // Bauer
                    addPawnMoves(row, col, piece);
                    break;
            }
            
            // Zeige mögliche Züge
            possibleMoves.forEach(m => {
                const sq = document.querySelector(`.square[data-row="${m[0]}"][data-col="${m[1]}"]`);
                if (sq) {
                    sq.classList.add('possible');
                    if (board[m[0]][m[1]]) sq.classList.add('has-piece');
                }
            });
        }
        
        function addKingMoves(row, col) {
            const dirs = [[-1,-1],[-1,0],[-1,1],[0,-1],[0,1],[1,-1],[1,0],[1,1]];
            dirs.forEach(([dr, dc]) => {
                const nr = row + dr, nc = col + dc;
                if (isValidSquare(nr, nc) && !isOwnPiece(board[nr][nc])) {
                    possibleMoves.push([nr, nc]);
                }
            });
        }
        
        function addRookMoves(row, col) {
            [[1,0],[-1,0],[0,1],[0,-1]].forEach(([dr, dc]) => {
                addLineMoves(row, col, dr, dc);
            });
        }
        
        function addBishopMoves(row, col) {
            [[1,1],[1,-1],[-1,1],[-1,-1]].forEach(([dr, dc]) => {
                addLineMoves(row, col, dr, dc);
            });
        }
        
        function addLineMoves(row, col, dr, dc) {
            let nr = row + dr, nc = col + dc;
            while (isValidSquare(nr, nc)) {
                if (board[nr][nc]) {
                    if (isEnemyPiece(board[nr][nc])) {
                        possibleMoves.push([nr, nc]);
                    }
                    break;
                }
                possibleMoves.push([nr, nc]);
                nr += dr;
                nc += dc;
            }
        }
        
        function addKnightMoves(row, col) {
            const jumps = [[-2,-1],[-2,1],[-1,-2],[-1,2],[1,-2],[1,2],[2,-1],[2,1]];
            jumps.forEach(([dr, dc]) => {
                const nr = row + dr, nc = col + dc;
                if (isValidSquare(nr, nc) && !isOwnPiece(board[nr][nc])) {
                    possibleMoves.push([nr, nc]);
                }
            });
        }
        
        function addPawnMoves(row, col, piece) {
            const dir = piece === 'P' ? 1 : -1;
            const nr = row + dir;
            
            // Vorwärts
            if (isValidSquare(nr, col) && !board[nr][col]) {
                possibleMoves.push([nr, col]);
            }
            // Schlagen diagonal
            [-1, 1].forEach(dc => {
                const nc = col + dc;
                if (isValidSquare(nr, nc) && isEnemyPiece(board[nr][nc])) {
                    possibleMoves.push([nr, nc]);
                }
            });
        }
        
        function isValidSquare(row, col) {
            return row >= 0 && row < 8 && col >= 0 && col < 8;
        }
        
        // Zug ausführen
        function makeMove(fromRow, fromCol, toRow, toCol) {
            const piece = board[fromRow][fromCol];
            const captured = board[toRow][toCol];
            
            // Zug speichern
            movesMade.push({
                from: [fromRow, fromCol],
                to: [toRow, toCol],
                piece: piece,
                captured: captured
            });
            
            // Board aktualisieren
            board[toRow][toCol] = piece;
            board[fromRow][fromCol] = null;
            
            currentMoveIndex++;
            document.getElementById('currentMove').textContent = Math.min(currentMoveIndex + 1, maxMoves);
            
            renderBoard();
            
            // Prüfen ob Lösung korrekt
            checkSolution();
        }
        
        // Lösung prüfen
        function checkSolution() {
            const expectedMove = solution[movesMade.length - 1];
            const lastMove = movesMade[movesMade.length - 1];
            
            // Prüfe ob der letzte Zug korrekt war
            if (expectedMove && 
                lastMove.from[0] === expectedMove[0] && 
                lastMove.from[1] === expectedMove[1] &&
                lastMove.to[0] === expectedMove[2] &&
                lastMove.to[1] === expectedMove[3]) {
                
                // Zug war korrekt
                if (movesMade.length === solution.length) {
                    // Puzzle gelöst!
                    gameOver = true;
                    showSuccess();
                } else {
                    // Gegner zieht (simuliert)
                    setTimeout(() => {
                        showToast('Richtig! Der Gegner zieht...', 'success');
                        simulateOpponentMove();
                    }, 500);
                }
            } else {
                // Falscher Zug
                showToast('Das führt nicht zum Matt! Probiere es nochmal.', 'error');
                setTimeout(() => {
                    undoLastMove();
                }, 1000);
            }
        }
        
        // Gegner simulieren (bei Matt in 2+)
        function simulateOpponentMove() {
            // Für die vereinfachte Version: Gegner macht einen vordefinierten "besten" Zug
            // In echten Puzzles wäre das die einzig logische Verteidigung
            
            // Hier simulieren wir nur, dass der König flieht (vereinfacht)
            // Der nächste Zug des Spielers muss dann das finale Matt sein
            
            document.getElementById('moveIndicator').innerHTML = 
                'Dein Zug! Setze <strong>Matt</strong>!<br>' +
                '<small>Zug <span class="move-count">' + (currentMoveIndex + 1) + '</span> von <span class="move-count">' + maxMoves + '</span></small>';
        }
        
        // Letzten Zug rückgängig
        function undoLastMove() {
            if (movesMade.length === 0) return;
            
            const lastMove = movesMade.pop();
            board[lastMove.from[0]][lastMove.from[1]] = lastMove.piece;
            board[lastMove.to[0]][lastMove.to[1]] = lastMove.captured;
            
            currentMoveIndex = Math.max(0, currentMoveIndex - 1);
            document.getElementById('currentMove').textContent = currentMoveIndex + 1;
            
            renderBoard();
        }
        
        // Puzzle zurücksetzen
        function resetPuzzle() {
            movesMade = [];
            currentMoveIndex = 0;
            gameOver = false;
            document.getElementById('currentMove').textContent = '1';
            document.getElementById('moveIndicator').innerHTML = 
                'Du bist dran! Ziehe mit <strong>' + (playerColor === 'white' ? 'Weiß ♔' : 'Schwarz ♚') + '</strong><br>' +
                '<small>Zug <span class="move-count">1</span> von <span class="move-count">' + maxMoves + '</span></small>';
            initBoard();
        }
        
        // Hinweis anzeigen
        function showHint() {
            hintsUsed++;
            showToast('💡 ' + puzzleData.hint + ' (-5 Sats)', 'info');
            
            // Ersten Zug der Lösung hervorheben
            if (solution.length > 0) {
                const hint = solution[movesMade.length] || solution[0];
                highlightSquare(hint[0], hint[1], 'highlight');
                setTimeout(() => {
                    document.querySelector(`.square[data-row="${hint[0]}"][data-col="${hint[1]}"]`)?.classList.remove('highlight');
                }, 2000);
            }
        }
        
        // Erfolg anzeigen
        function showSuccess() {
            const totalSats = Math.max(5, satsReward - (hintsUsed * 5));
            
            document.getElementById('result-sats').textContent = '+' + totalSats + ' Sats';
            document.getElementById('result-sub').textContent = 
                hintsUsed > 0 ? 'Hinweise verwendet: ' + hintsUsed + ' (-' + (hintsUsed * 5) + ' Sats)' : 'Ohne Hilfe gelöst! 🏆';
            
            document.getElementById('result-overlay').classList.add('show');
            
            // Sats vergeben
            if (childId > 0) {
                fetch('/wallet/api.php?action=earn', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        child_id: childId,
                        score: 1,
                        max_score: 1,
                        module: 'schach_puzzle',
                        sats: totalSats
                    })
                });
            }
            
            // Session updaten
            fetch('api/update_session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type: 'schach', correct: true, sats: totalSats})
            });
        }
        
        // Toast Nachricht
        function showToast(msg, type) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
                background: ${type === 'error' ? '#ff4444' : type === 'success' ? '#43D240' : '#1e3a08'};
                color: #fff;
                padding: 12px 24px; border-radius: 10px; font-weight: 600;
                z-index: 200; animation: popIn 0.3s ease;
                border: 2px solid ${type === 'error' ? '#ff6666' : '#43D240'};
            `;
            toast.textContent = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        // Initialisierung
        initBoard();
    </script>
</body>
</html>
