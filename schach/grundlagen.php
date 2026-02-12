<?php
/**
 * ============================================================================
 * sgiT Education - Schach Grundlagen v1.0
 * ============================================================================
 * Lerne die Grundzüge aller Schachfiguren kennen!
 * ============================================================================
 */

session_start();
require_once dirname(__DIR__) . '/includes/version.php';

$userAge = $_SESSION['user_age'] ?? 10;
$userName = $_SESSION['child_name'] ?? 'Schach-Fan';
$childId = $_SESSION['wallet_child_id'] ?? 0;

// Schachfiguren mit Zügen
$pieces = [
    'pawn' => [
        'name' => 'Bauer',
        'symbol' => '♟',
        'moves' => 'Ein Feld vorwärts (beim Start 2 Felder). Schlägt diagonal.',
        'special' => 'Kann sich am Ende in eine andere Figur verwandeln!',
        'sats' => 5
    ],
    'rook' => [
        'name' => 'Turm',
        'symbol' => '♜',
        'moves' => 'Beliebig weit horizontal oder vertikal.',
        'special' => 'Kann rochieren mit dem König!',
        'sats' => 8
    ],
    'knight' => [
        'name' => 'Springer',
        'symbol' => '♞',
        'moves' => 'L-förmig: 2 Felder + 1 Feld im rechten Winkel.',
        'special' => 'Einzige Figur die über andere springen kann!',
        'sats' => 8
    ],
    'bishop' => [
        'name' => 'Läufer',
        'symbol' => '♝',
        'moves' => 'Beliebig weit diagonal.',
        'special' => 'Bleibt immer auf seiner Feldfarbe!',
        'sats' => 8
    ],
    'queen' => [
        'name' => 'Dame',
        'symbol' => '♛',
        'moves' => 'Beliebig weit in alle Richtungen (horizontal, vertikal, diagonal).',
        'special' => 'Die stärkste Figur auf dem Brett!',
        'sats' => 10
    ],
    'king' => [
        'name' => 'König',
        'symbol' => '♚',
        'moves' => 'Ein Feld in jede Richtung.',
        'special' => 'Wenn er geschlagen wird, ist das Spiel verloren!',
        'sats' => 10
    ]
];

// Session für Quiz
if (!isset($_SESSION['schach_grundlagen'])) {
    $_SESSION['schach_grundlagen'] = [
        'current' => 0,
        'correct' => 0,
        'total_sats' => 0,
        'seen' => []
    ];
}
$session = &$_SESSION['schach_grundlagen'];

// Quiz-Modus wenn current > 0
$quizMode = isset($_GET['quiz']);
$pieceKeys = array_keys($pieces);
$currentPiece = $quizMode ? $pieceKeys[$session['current'] % count($pieceKeys)] : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 Schach Grundlagen - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bg: #0d1f02;
            --card-bg: #1e3a08;
            --cell-bg: #2a4a0e;
            --text: #ffffff;
            --text-muted: #a0a0a0;
            --light-sq: #e8d4a8;
            --dark-sq: #b58863;
        }
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, var(--primary) 100%);
            min-height: 100vh;
            color: var(--text);
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .back-link { color: var(--accent); text-decoration: none; display: inline-block; margin-bottom: 12px; }
        .back-link:hover { text-decoration: underline; }
        header { text-align: center; margin-bottom: 25px; }
        header h1 { font-size: 1.8rem; margin-bottom: 8px; }
        header h1 span { color: var(--accent); }
        .subtitle { color: var(--text-muted); }
        
        .pieces-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
            margin-top: 20px;
        }
        .piece-card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 20px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .piece-card:hover {
            border-color: var(--accent);
            transform: translateY(-3px);
        }
        .piece-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 12px;
        }
        .piece-symbol {
            font-size: 3rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .piece-name { font-size: 1.3rem; font-weight: 600; }
        .piece-moves {
            background: var(--cell-bg);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        .piece-special {
            color: var(--accent);
            font-size: 0.85rem;
            font-style: italic;
        }
        .piece-sats {
            margin-top: 10px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .piece-sats span { color: var(--accent); font-weight: 600; }
        
        /* Mini-Schachbrett */
        .mini-board {
            display: grid;
            grid-template-columns: repeat(5, 28px);
            gap: 0;
            margin: 12px auto;
            border: 2px solid var(--accent);
            border-radius: 4px;
            overflow: hidden;
        }
        .mini-board .sq {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .mini-board .light { background: var(--light-sq); }
        .mini-board .dark { background: var(--dark-sq); }
        .mini-board .move-dot {
            width: 10px;
            height: 10px;
            background: var(--accent);
            border-radius: 50%;
            opacity: 0.8;
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
            margin: 5px;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn.secondary { background: var(--cell-bg); color: var(--text); }
        
        .quiz-btn {
            display: block;
            margin: 25px auto 0;
            font-size: 1.1rem;
            padding: 15px 35px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Zurück zu Schach</a>
        
        <header>
            <h1>📚 <span>Grundlagen</span></h1>
            <p class="subtitle">Lerne wie jede Schachfigur zieht!</p>
        </header>
        
        <div class="pieces-grid">
            <?php foreach ($pieces as $key => $piece): ?>
            <div class="piece-card" data-piece="<?php echo $key; ?>">
                <div class="piece-header">
                    <span class="piece-symbol"><?php echo $piece['symbol']; ?></span>
                    <span class="piece-name"><?php echo $piece['name']; ?></span>
                </div>
                <div class="piece-moves">
                    <strong>Zugweise:</strong> <?php echo $piece['moves']; ?>
                </div>
                <div class="piece-special">
                    💡 <?php echo $piece['special']; ?>
                </div>
                <div class="piece-sats">
                    Quiz bestehen: <span>+<?php echo $piece['sats']; ?> Sats</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <button class="btn quiz-btn" onclick="location.href='grundlagen_quiz.php'">
            🎯 Teste dein Wissen!
        </button>
    </div>
</body>
</html>
