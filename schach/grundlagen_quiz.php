<?php
/**
 * ============================================================================
 * sgiT Education - Schach Grundlagen Quiz v1.0
 * ============================================================================
 * Quiz zu den Schachfiguren und ihren Zügen
 * ============================================================================
 */

session_start();
require_once dirname(__DIR__) . '/includes/version.php';

$userAge = $_SESSION['user_age'] ?? 10;
$userName = $_SESSION['child_name'] ?? 'Schach-Fan';
$childId = $_SESSION['wallet_child_id'] ?? 0;

// Quiz-Fragen
$questions = [
    ['q' => 'Wie bewegt sich der Bauer?', 'a' => 'Vorwärts, schlägt diagonal', 'opts' => ['Vorwärts, schlägt diagonal', 'Diagonal in alle Richtungen', 'L-förmig', 'Beliebig weit geradeaus'], 'piece' => '♟', 'sats' => 5],
    ['q' => 'Welche Figur kann über andere springen?', 'a' => 'Springer', 'opts' => ['Dame', 'Springer', 'Läufer', 'Turm'], 'piece' => '♞', 'sats' => 8],
    ['q' => 'Wie bewegt sich der Turm?', 'a' => 'Horizontal und vertikal', 'opts' => ['Nur diagonal', 'Horizontal und vertikal', 'Ein Feld in jede Richtung', 'L-förmig'], 'piece' => '♜', 'sats' => 8],
    ['q' => 'Auf welchen Feldern bleibt der Läufer immer?', 'a' => 'Auf einer Feldfarbe', 'opts' => ['In der Mitte', 'Auf einer Feldfarbe', 'Am Rand', 'Neben dem König'], 'piece' => '♝', 'sats' => 8],
    ['q' => 'Welche Figur ist die stärkste?', 'a' => 'Dame', 'opts' => ['König', 'Dame', 'Turm', 'Springer'], 'piece' => '♛', 'sats' => 10],
    ['q' => 'Wie weit kann der König ziehen?', 'a' => 'Ein Feld', 'opts' => ['Ein Feld', 'Zwei Felder', 'Beliebig weit', 'Nur diagonal'], 'piece' => '♚', 'sats' => 10],
    ['q' => 'Was kann ein Bauer werden, wenn er das Ende erreicht?', 'a' => 'Jede andere Figur außer König', 'opts' => ['Nur Dame', 'Jede andere Figur außer König', 'Nichts', 'Zwei Bauern'], 'piece' => '♟', 'sats' => 10],
    ['q' => 'Wie bewegt sich der Springer?', 'a' => 'L-förmig (2+1 Felder)', 'opts' => ['Diagonal', 'L-förmig (2+1 Felder)', 'Geradeaus', 'Nur ein Feld'], 'piece' => '♞', 'sats' => 8]
];

// Session initialisieren
if (!isset($_SESSION['schach_quiz']) || isset($_GET['new'])) {
    shuffle($questions);
    $_SESSION['schach_quiz'] = [
        'questions' => array_slice($questions, 0, 6),
        'current' => 0,
        'correct' => 0,
        'total_sats' => 0
    ];
}
$session = &$_SESSION['schach_quiz'];

// Quiz beendet?
if ($session['current'] >= count($session['questions'])) {
    $finalScore = $session['correct'];
    $totalSats = $session['total_sats'];
    $totalQ = count($session['questions']);
    unset($_SESSION['schach_quiz']);
} else {
    $currentQ = $session['questions'][$session['current']];
    shuffle($currentQ['opts']);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎯 Schach Quiz - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1A3503; --accent: #43D240; --bg: #0d1f02; --card-bg: #1e3a08; --cell-bg: #2a4a0e; }
        body { font-family: 'Space Grotesk', system-ui, sans-serif; background: linear-gradient(135deg, var(--bg), var(--primary)); min-height: 100vh; color: #fff; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; text-align: center; }
        .back-link { color: var(--accent); text-decoration: none; display: inline-block; margin-bottom: 15px; }
        h1 { font-size: 1.7rem; margin-bottom: 8px; }
        h1 span { color: var(--accent); }
        .progress-bar { background: #2a4a0e; border-radius: 20px; height: 22px; margin: 12px 0; overflow: hidden; position: relative; }
        .progress-fill { background: var(--accent); height: 100%; transition: width 0.3s; }
        .progress-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; font-size: 0.8rem; }
        .stats { display: flex; justify-content: center; gap: 25px; margin-bottom: 18px; font-size: 0.9rem; }
        .stats span { color: var(--accent); font-weight: bold; }
        .question-box { background: var(--card-bg); border-radius: 14px; padding: 25px; margin-bottom: 18px; }
        .piece-icon { font-size: 4rem; margin-bottom: 15px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .question-text { font-size: 1.2rem; margin-bottom: 20px; }
        .options { display: flex; flex-direction: column; gap: 10px; }
        .option { background: var(--cell-bg); border: 3px solid transparent; border-radius: 10px; padding: 14px; font-size: 1rem; cursor: pointer; transition: all 0.2s; text-align: left; }
        .option:hover { border-color: var(--accent); transform: scale(1.02); }
        .option.correct { background: var(--accent); color: var(--primary); }
        .option.wrong { background: #ff4444; }
        .result { margin-top: 15px; padding: 15px; border-radius: 10px; display: none; }
        .result.show { display: block; }
        .result.success { background: rgba(67, 210, 64, 0.2); border: 2px solid var(--accent); }
        .result.fail { background: rgba(255, 68, 68, 0.2); border: 2px solid #ff4444; }
        .btn { background: var(--accent); color: var(--primary); border: none; padding: 12px 25px; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; margin: 8px; }
        .btn:hover { transform: translateY(-2px); }
        .btn.secondary { background: var(--cell-bg); color: #fff; }
        .final-box { background: var(--card-bg); border-radius: 16px; padding: 30px; margin-top: 20px; }
        .final-score { font-size: 2.8rem; color: var(--accent); font-weight: bold; }
        .sats-earned { font-size: 1.4rem; margin: 12px 0; }
    </style>
</head>
<body>
    <div class="container">
        <a href="grundlagen.php" class="back-link">← Zurück</a>
        
        <?php if (isset($finalScore)): ?>
        <h1>🎯 Quiz <span>beendet!</span></h1>
        <div class="final-box">
            <div class="final-score"><?php echo $finalScore; ?>/<?php echo $totalQ; ?></div>
            <div class="sats-earned">🌟 <?php echo $totalSats; ?> Sats verdient!</div>
            <p style="color:#a0a0a0;">Super, <?php echo htmlspecialchars($userName); ?>!</p>
            <button class="btn" onclick="location.href='grundlagen_quiz.php?new=1'">🔄 Nochmal</button>
            <button class="btn secondary" onclick="location.href='index.php'">← Andere Kategorien</button>
        </div>
        <?php else: ?>
        <h1>🎯 Schach-<span>Quiz</span></h1>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo (($session['current'] + 1) / count($session['questions'])) * 100; ?>%"></div>
            <div class="progress-text">Frage <?php echo $session['current'] + 1; ?> / <?php echo count($session['questions']); ?></div>
        </div>
        
        <div class="stats">
            <div>✅ Richtig: <span><?php echo $session['correct']; ?></span></div>
            <div>🌟 Sats: <span><?php echo $session['total_sats']; ?></span></div>
        </div>
        
        <div class="question-box">
            <div class="piece-icon"><?php echo $currentQ['piece']; ?></div>
            <div class="question-text"><?php echo $currentQ['q']; ?></div>
            <div class="options">
                <?php foreach ($currentQ['opts'] as $opt): ?>
                <div class="option" onclick="checkAnswer(this, '<?php echo addslashes($opt); ?>')"><?php echo $opt; ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="result" id="result"></div>
        <?php endif; ?>
    </div>
    
    <?php if (!isset($finalScore)): ?>
    <script>
        const correctAnswer = <?php echo json_encode($currentQ['a']); ?>;
        const sats = <?php echo $currentQ['sats']; ?>;
        const childId = <?php echo $childId; ?>;
        let answered = false;
        
        function checkAnswer(el, answer) {
            if (answered) return;
            answered = true;
            
            const result = document.getElementById('result');
            const isCorrect = (answer === correctAnswer);
            
            if (isCorrect) {
                el.classList.add('correct');
                result.className = 'result show success';
                result.innerHTML = '✅ Richtig! +' + sats + ' Sats';
                updateSession(true, sats);
            } else {
                el.classList.add('wrong');
                document.querySelectorAll('.option').forEach(o => { 
                    if (o.textContent === correctAnswer) o.classList.add('correct'); 
                });
                result.className = 'result show fail';
                result.innerHTML = '❌ Die richtige Antwort war: ' + correctAnswer;
                updateSession(false, 0);
            }
            setTimeout(() => location.reload(), 1800);
        }
        
        function updateSession(correct, earnedSats) {
            fetch('/api/schach_session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type: 'grundlagen', correct: correct, sats: earnedSats, child_id: childId})
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
