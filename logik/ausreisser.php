<?php
/**
 * Was gehört nicht dazu? - Logik & Rätsel v1.1
 * Mit Session-Limit (10 Fragen) und Sats-Vergabe
 */
session_start();

$userAge = $_SESSION['user_age'] ?? 10;
$userName = $_SESSION['child_name'] ?? 'Rätselfan';
$childId = $_SESSION['wallet_child_id'] ?? 0;

// Session initialisieren
if (!isset($_SESSION['logik_ausreisser'])) {
    $_SESSION['logik_ausreisser'] = ['question' => 0, 'correct' => 0, 'total_sats' => 0];
}
$session = &$_SESSION['logik_ausreisser'];

// Session beendet?
if ($session['question'] >= 10) {
    $finalScore = $session['correct'];
    $totalSats = $session['total_sats'];
    unset($_SESSION['logik_ausreisser']);
}

$session['question']++;
$currentQ = $session['question'];

function generatePuzzle($age) {
    if ($age <= 7) {
        $puzzles = [
            ['items' => ['🍎','🍐','🍊','🚗'], 'ans' => '🚗', 'cat' => 'Früchte', 'sats' => 5],
            ['items' => ['🐱','🐶','🐭','🌳'], 'ans' => '🌳', 'cat' => 'Tiere', 'sats' => 5],
            ['items' => ['✈️','🚂','🚗','🍕'], 'ans' => '🍕', 'cat' => 'Fahrzeuge', 'sats' => 5],
            ['items' => ['🔴','🔵','🟢','⭐'], 'ans' => '⭐', 'cat' => 'Kreise', 'sats' => 5],
            ['items' => ['👨','👩','👶','🏠'], 'ans' => '🏠', 'cat' => 'Menschen', 'sats' => 5],
            ['items' => ['☀️','🌙','⭐','🌳'], 'ans' => '🌳', 'cat' => 'Am Himmel', 'sats' => 5],
            ['items' => ['🎂','🍰','🧁','📚'], 'ans' => '📚', 'cat' => 'Süßes', 'sats' => 5],
            ['items' => ['🐟','🐠','🐡','🦋'], 'ans' => '🦋', 'cat' => 'Fische', 'sats' => 5],
        ];
    } else {
        $puzzles = [
            ['items' => ['🎹','🎸','🎺','📚'], 'ans' => '📚', 'cat' => 'Musikinstrumente', 'sats' => 8],
            ['items' => ['🍕','🍔','🍟','📱'], 'ans' => '📱', 'cat' => 'Essen', 'sats' => 8],
            ['items' => ['🏀','⚽','🎾','🎨'], 'ans' => '🎨', 'cat' => 'Ballsport', 'sats' => 8],
            ['items' => ['🌍','🌎','🌏','🌙'], 'ans' => '🌙', 'cat' => 'Erde', 'sats' => 8],
            ['items' => ['🦁','🐘','🦒','🐟'], 'ans' => '🐟', 'cat' => 'Safari-Tiere', 'sats' => 8],
            ['items' => ['❄️','🌨️','⛄','🌴'], 'ans' => '🌴', 'cat' => 'Winter', 'sats' => 8],
            ['items' => ['🚀','✈️','🚁','🚲'], 'ans' => '🚲', 'cat' => 'Fliegen', 'sats' => 8],
            ['items' => ['📖','📕','📗','🎮'], 'ans' => '🎮', 'cat' => 'Bücher', 'sats' => 8],
        ];
    }
    $p = $puzzles[array_rand($puzzles)];
    shuffle($p['items']);
    return $p;
}

$puzzle = generatePuzzle($userAge);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Ausreißer - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1A3503; --accent: #43D240; --bg: #0d1f02; --card-bg: #1e3a08; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, var(--bg), var(--primary)); min-height: 100vh; color: #fff; padding: 20px; }
        .container { max-width: 550px; margin: 0 auto; text-align: center; }
        .back-link { color: var(--accent); text-decoration: none; display: inline-block; margin-bottom: 15px; }
        h1 { font-size: 1.6rem; margin-bottom: 8px; }
        h1 span { color: var(--accent); }
        .progress-bar { background: #2a4a0e; border-radius: 20px; height: 22px; margin: 12px 0; overflow: hidden; position: relative; }
        .progress-fill { background: var(--accent); height: 100%; transition: width 0.3s; }
        .progress-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; font-size: 0.8rem; }
        .stats { display: flex; justify-content: center; gap: 25px; margin-bottom: 18px; font-size: 0.9rem; }
        .stats span { color: var(--accent); font-weight: bold; }
        .items-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; max-width: 320px; margin: 0 auto 20px; }
        .item { background: var(--card-bg); border: 3px solid transparent; border-radius: 14px; padding: 22px; font-size: 2.8rem; cursor: pointer; transition: all 0.2s; }
        .item:hover { border-color: var(--accent); transform: scale(1.05); }
        .item.correct { background: var(--accent); }
        .item.wrong { background: #ff4444; }
        .item.highlight { border-color: var(--accent); box-shadow: 0 0 15px var(--accent); }
        .result { margin-top: 15px; padding: 15px; border-radius: 10px; display: none; }
        .result.show { display: block; }
        .result.success { background: rgba(67, 210, 64, 0.2); border: 2px solid var(--accent); }
        .result.fail { background: rgba(255, 68, 68, 0.2); border: 2px solid #ff4444; }
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
        <h1>🔍 Ausreißer <span>beendet!</span></h1>
        <div class="final-box">
            <div class="final-score"><?php echo $finalScore; ?>/10</div>
            <div class="sats-earned">🌟 <?php echo $totalSats; ?> Sats verdient!</div>
            <p style="color:#a0a0a0;">Gut gemacht, <?php echo htmlspecialchars($userName); ?>!</p>
            <button class="btn" onclick="location.href='ausreisser.php'">🔄 Neue Runde</button>
            <button class="btn" onclick="location.href='index.php'" style="background:#2a4a0e;color:#fff;">← Andere Rätsel</button>
        </div>
        <?php else: ?>
        <h1>🔍 Was gehört <span>nicht dazu</span>?</h1>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo ($currentQ / 10) * 100; ?>%"></div>
            <div class="progress-text">Frage <?php echo $currentQ; ?> / 10</div>
        </div>
        
        <div class="stats">
            <div>✅ Richtig: <span><?php echo $session['correct']; ?></span></div>
            <div>🌟 Sats: <span><?php echo $session['total_sats']; ?></span></div>
        </div>
        
        <div class="items-grid">
            <?php foreach ($puzzle['items'] as $item): ?>
                <div class="item" onclick="checkAnswer(this, '<?php echo $item; ?>')"><?php echo $item; ?></div>
            <?php endforeach; ?>
        </div>
        <div class="result" id="result"></div>
        <?php endif; ?>
    </div>

    <?php if (!isset($finalScore)): ?>
    <script>
        const correct = <?php echo json_encode($puzzle['ans']); ?>;
        const category = <?php echo json_encode($puzzle['cat']); ?>;
        const sats = <?php echo $puzzle['sats']; ?>;
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
                result.innerHTML = '✅ Richtig! ' + correct + ' gehört nicht zu "' + category + '"<br>+' + sats + ' Sats';
                awardSats(sats);
                updateSession(true, sats);
            } else {
                el.classList.add('wrong');
                document.querySelectorAll('.item').forEach(i => { if (i.textContent === correct) i.classList.add('highlight'); });
                result.className = 'result show fail';
                result.innerHTML = '❌ ' + correct + ' gehört nicht zu "' + category + '"';
                updateSession(false, 0);
            }
            setTimeout(() => location.reload(), 1800);
        }
        
        function awardSats(amount) {
            if (childId > 0) {
                fetch('/wallet/api.php?action=earn', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({child_id: childId, score: 1, max_score: 1, module: 'logik_ausreisser'})
                });
            }
        }
        
        function updateSession(correct, sats) {
            fetch('api/update_session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type: 'ausreisser', correct: correct, sats: sats})
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
