<?php
/**
 * Zahlenreihen - Logik & Rätsel v1.1
 * Mit Session-Limit (10 Fragen) und Sats-Vergabe
 */
session_start();

$userAge = $_SESSION['user_age'] ?? 10;
$userName = $_SESSION['child_name'] ?? 'Rätselfan';
$childId = $_SESSION['wallet_child_id'] ?? 0;

// Session initialisieren
if (!isset($_SESSION['logik_zahlenreihe'])) {
    $_SESSION['logik_zahlenreihe'] = ['question' => 0, 'correct' => 0, 'total_sats' => 0];
}
$session = &$_SESSION['logik_zahlenreihe'];

// Session beendet?
if ($session['question'] >= 10) {
    $finalScore = $session['correct'];
    $totalSats = $session['total_sats'];
    unset($_SESSION['logik_zahlenreihe']);
}

$session['question']++;
$currentQ = $session['question'];

function generateSequence($age) {
    if ($age <= 10) {
        $seqs = [
            ['nums' => [2, 4, 6, 8], 'ans' => 10, 'rule' => '+2', 'sats' => 8],
            ['nums' => [5, 10, 15, 20], 'ans' => 25, 'rule' => '+5', 'sats' => 8],
            ['nums' => [1, 2, 3, 4], 'ans' => 5, 'rule' => '+1', 'sats' => 6],
            ['nums' => [3, 6, 9, 12], 'ans' => 15, 'rule' => '+3', 'sats' => 8],
            ['nums' => [10, 20, 30, 40], 'ans' => 50, 'rule' => '+10', 'sats' => 8],
            ['nums' => [100, 90, 80, 70], 'ans' => 60, 'rule' => '-10', 'sats' => 10],
            ['nums' => [4, 8, 12, 16], 'ans' => 20, 'rule' => '+4', 'sats' => 8],
        ];
    } elseif ($age <= 14) {
        $seqs = [
            ['nums' => [1, 2, 4, 8], 'ans' => 16, 'rule' => '×2', 'sats' => 12],
            ['nums' => [1, 4, 9, 16], 'ans' => 25, 'rule' => 'n²', 'sats' => 15],
            ['nums' => [1, 1, 2, 3, 5], 'ans' => 8, 'rule' => 'Fibonacci', 'sats' => 15],
            ['nums' => [81, 27, 9, 3], 'ans' => 1, 'rule' => '÷3', 'sats' => 12],
            ['nums' => [2, 6, 18, 54], 'ans' => 162, 'rule' => '×3', 'sats' => 12],
            ['nums' => [1, 3, 6, 10], 'ans' => 15, 'rule' => 'Dreieckszahlen', 'sats' => 15],
        ];
    } else {
        $seqs = [
            ['nums' => [1, 1, 2, 3, 5, 8], 'ans' => 13, 'rule' => 'Fibonacci', 'sats' => 15],
            ['nums' => [2, 3, 5, 7, 11], 'ans' => 13, 'rule' => 'Primzahlen', 'sats' => 18],
            ['nums' => [1, 8, 27, 64], 'ans' => 125, 'rule' => 'n³', 'sats' => 18],
            ['nums' => [1, 4, 9, 16, 25], 'ans' => 36, 'rule' => 'n²', 'sats' => 15],
            ['nums' => [0, 1, 1, 2, 4, 7], 'ans' => 13, 'rule' => 'Tribonacci', 'sats' => 20],
            ['nums' => [2, 4, 8, 16, 32], 'ans' => 64, 'rule' => '2^n', 'sats' => 15],
        ];
    }
    $s = $seqs[array_rand($seqs)];
    // Optionen generieren
    $opts = [$s['ans']];
    while (count($opts) < 4) {
        $wrong = $s['ans'] + rand(-5, 5);
        if ($wrong != $s['ans'] && !in_array($wrong, $opts) && $wrong > 0) $opts[] = $wrong;
    }
    shuffle($opts);
    $s['opts'] = $opts;
    return $s;
}

$seq = generateSequence($userAge);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔢 Zahlenreihen - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1A3503; --accent: #43D240; --bg: #0d1f02; --card-bg: #1e3a08; }
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
        .sequence-box { background: var(--card-bg); border-radius: 14px; padding: 22px; margin-bottom: 18px; }
        .numbers { display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .number { background: #2a4a0e; padding: 12px 18px; border-radius: 8px; font-size: 1.6rem; font-weight: bold; }
        .question-mark { background: var(--accent); color: var(--primary); padding: 12px 18px; border-radius: 8px; font-size: 1.6rem; font-weight: bold; }
        .options { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
        .option { background: #2a4a0e; border: 3px solid transparent; border-radius: 8px; padding: 12px 24px; font-size: 1.4rem; font-weight: bold; cursor: pointer; transition: all 0.2s; }
        .option:hover { border-color: var(--accent); transform: scale(1.05); }
        .option.correct { background: var(--accent); color: var(--primary); }
        .option.wrong { background: #ff4444; }
        .result { margin-top: 15px; padding: 15px; border-radius: 10px; display: none; }
        .result.show { display: block; }
        .result.success { background: rgba(67, 210, 64, 0.2); border: 2px solid var(--accent); }
        .result.fail { background: rgba(255, 68, 68, 0.2); border: 2px solid #ff4444; }
        .rule { font-style: italic; color: #a0a0a0; font-size: 0.9rem; }
        .btn { background: var(--accent); color: var(--primary); border: none; padding: 12px 25px; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; margin: 8px; }
        .final-box { background: var(--card-bg); border-radius: 16px; padding: 30px; margin-top: 20px; }
        .final-score { font-size: 2.8rem; color: var(--accent); font-weight: bold; }
        .sats-earned { font-size: 1.4rem; margin: 12px 0; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Zurück</a>
        
        <?php if (isset($finalScore)): ?>
        <h1>🔢 Zahlenreihen <span>beendet!</span></h1>
        <div class="final-box">
            <div class="final-score"><?php echo $finalScore; ?>/10</div>
            <div class="sats-earned">🌟 <?php echo $totalSats; ?> Sats verdient!</div>
            <p style="color:#a0a0a0;">Klasse, <?php echo htmlspecialchars($userName); ?>!</p>
            <button class="btn" onclick="location.href='zahlenreihe.php'">🔄 Neue Runde</button>
            <button class="btn" onclick="location.href='index.php'" style="background:#2a4a0e;color:#fff;">← Andere Rätsel</button>
        </div>
        <?php else: ?>
        <h1>🔢 Zahlen<span>reihen</span></h1>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo ($currentQ / 10) * 100; ?>%"></div>
            <div class="progress-text">Frage <?php echo $currentQ; ?> / 10</div>
        </div>
        
        <div class="stats">
            <div>✅ Richtig: <span><?php echo $session['correct']; ?></span></div>
            <div>🌟 Sats: <span><?php echo $session['total_sats']; ?></span></div>
        </div>
        
        <div class="sequence-box">
            <div class="numbers">
                <?php foreach ($seq['nums'] as $n): ?>
                    <span class="number"><?php echo $n; ?></span>
                <?php endforeach; ?>
                <span class="question-mark">?</span>
            </div>
            <div class="options">
                <?php foreach ($seq['opts'] as $o): ?>
                    <div class="option" onclick="checkAnswer(this, <?php echo $o; ?>)"><?php echo $o; ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="result" id="result"></div>
        <?php endif; ?>
    </div>

    <?php if (!isset($finalScore)): ?>
    <script>
        const correct = <?php echo $seq['ans']; ?>;
        const rule = <?php echo json_encode($seq['rule']); ?>;
        const sats = <?php echo $seq['sats']; ?>;
        const childId = <?php echo $childId; ?>;
        let answered = false;
        
        function checkAnswer(el, ans) {
            if (answered) return;
            answered = true;
            
            const result = document.getElementById('result');
            const isCorrect = (ans === correct);
            
            if (isCorrect) {
                el.classList.add('correct');
                result.className = 'result show success';
                result.innerHTML = '✅ Richtig! <span class="rule">Regel: ' + rule + '</span><br>+' + sats + ' Sats';
                awardSats(sats);
                updateSession(true, sats);
            } else {
                el.classList.add('wrong');
                document.querySelectorAll('.option').forEach(o => { if (parseInt(o.textContent) === correct) o.classList.add('correct'); });
                result.className = 'result show fail';
                result.innerHTML = '❌ Die Antwort war ' + correct + ' <span class="rule">(Regel: ' + rule + ')</span>';
                updateSession(false, 0);
            }
            setTimeout(() => location.reload(), 1800);
        }
        
        function awardSats(amount) {
            if (childId > 0) {
                fetch('/wallet/api.php?action=earn', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({child_id: childId, score: 1, max_score: 1, module: 'logik_zahlenreihe'})
                });
            }
        }
        
        function updateSession(correct, sats) {
            fetch('api/update_session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type: 'zahlenreihe', correct: correct, sats: sats})
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
