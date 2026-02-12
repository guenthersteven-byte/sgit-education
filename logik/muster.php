<?php
/**
 * Muster fortsetzen - Logik & Rätsel v1.1
 * Mit Session-Limit (10 Fragen) und Sats-Vergabe
 */
session_start();

$userAge = $_SESSION['user_age'] ?? 10;
$userName = $_SESSION['child_name'] ?? 'Rätselfan';
$childId = $_SESSION['wallet_child_id'] ?? 0;

// Session für Logik-Rätsel initialisieren
if (!isset($_SESSION['logik_muster'])) {
    $_SESSION['logik_muster'] = [
        'question' => 0,
        'correct' => 0,
        'total_sats' => 0
    ];
}

$session = &$_SESSION['logik_muster'];

// Session beendet?
if ($session['question'] >= 10) {
    $finalScore = $session['correct'];
    $totalSats = $session['total_sats'];
    // Reset für neue Runde
    unset($_SESSION['logik_muster']);
}

// Nächste Frage
$session['question']++;
$currentQ = $session['question'];

// Muster generieren
function generatePattern($age) {
    if ($age <= 7) {
        $patterns = [
            ['seq' => ['🔴','🔵','🔴','🔵','🔴'], 'ans' => '🔵', 'opts' => ['🔴','🔵','🟢','🟡'], 'sats' => 5],
            ['seq' => ['⭐','⭐','🌙','⭐','⭐'], 'ans' => '🌙', 'opts' => ['⭐','🌙','☀️','🌟'], 'sats' => 5],
            ['seq' => ['🍎','🍐','🍎','🍐','🍎'], 'ans' => '🍐', 'opts' => ['🍎','🍐','🍊','🍋'], 'sats' => 5],
            ['seq' => ['🐱','🐶','🐱','🐶','🐱'], 'ans' => '🐶', 'opts' => ['🐱','🐶','🐭','🐹'], 'sats' => 5],
            ['seq' => ['❤️','💙','❤️','💙','❤️'], 'ans' => '💙', 'opts' => ['❤️','💙','💚','💛'], 'sats' => 5],
        ];
    } elseif ($age <= 12) {
        $patterns = [
            ['seq' => ['🔴','🔴','🔵','🔴','🔴'], 'ans' => '🔵', 'opts' => ['🔴','🔵','🟢','🟡'], 'sats' => 8],
            ['seq' => ['⬆️','➡️','⬇️','⬅️','⬆️'], 'ans' => '➡️', 'opts' => ['⬆️','➡️','⬇️','⬅️'], 'sats' => 8],
            ['seq' => ['🌑','🌓','🌕','🌗','🌑'], 'ans' => '🌓', 'opts' => ['🌑','🌓','🌕','🌗'], 'sats' => 8],
            ['seq' => ['🔺','🔻','🔺','🔻','🔺'], 'ans' => '🔻', 'opts' => ['🔺','🔻','⬛','⬜'], 'sats' => 8],
        ];
    } else {
        $patterns = [
            ['seq' => ['🔴','🔵','🔵','🔴','🔵','🔵'], 'ans' => '🔴', 'opts' => ['🔴','🔵','🟢','🟡'], 'sats' => 12],
            ['seq' => ['⭐','⭐','🌙','⭐','⭐','🌙','⭐'], 'ans' => '⭐', 'opts' => ['⭐','🌙','☀️','🌟'], 'sats' => 12],
            ['seq' => ['🔺','⬛','🔺','🔺','⬛','🔺','🔺','🔺'], 'ans' => '⬛', 'opts' => ['🔺','⬛','🔻','⬜'], 'sats' => 15],
        ];
    }
    return $patterns[array_rand($patterns)];
}

$pattern = generatePattern($userAge);
$showResult = isset($_SESSION['logik_muster_done']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎨 Muster - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1A3503; --accent: #43D240; --bg: #0d1f02; --card-bg: #1e3a08; }
        body { font-family: 'Space Grotesk', system-ui, sans-serif; background: linear-gradient(135deg, var(--bg), var(--primary)); min-height: 100vh; color: #fff; padding: 20px; }
        .container { max-width: 650px; margin: 0 auto; text-align: center; }
        .back-link { color: var(--accent); text-decoration: none; display: inline-block; margin-bottom: 15px; }
        h1 { font-size: 1.8rem; margin-bottom: 8px; }
        h1 span { color: var(--accent); }
        .progress-bar { background: #2a4a0e; border-radius: 20px; height: 24px; margin: 15px 0; overflow: hidden; position: relative; }
        .progress-fill { background: var(--accent); height: 100%; transition: width 0.3s; }
        .progress-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; font-size: 0.85rem; }
        .stats { display: flex; justify-content: center; gap: 30px; margin-bottom: 20px; font-size: 0.95rem; }
        .stats span { color: var(--accent); font-weight: bold; }
        .sequence-box { background: var(--card-bg); border-radius: 14px; padding: 25px; margin-bottom: 20px; }
        .sequence { display: flex; justify-content: center; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 2.2rem; margin-bottom: 18px; }
        .question-mark { background: var(--accent); color: var(--primary); border-radius: 10px; padding: 8px 18px; font-weight: bold; font-size: 1.8rem; }
        .options { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }
        .option { background: #2a4a0e; border: 3px solid transparent; border-radius: 10px; padding: 12px 22px; font-size: 2rem; cursor: pointer; transition: all 0.2s; }
        .option:hover { border-color: var(--accent); transform: scale(1.08); }
        .option.correct { background: var(--accent); }
        .option.wrong { background: #ff4444; }
        .result { margin-top: 18px; padding: 18px; border-radius: 10px; display: none; }
        .result.show { display: block; }
        .result.success { background: rgba(67, 210, 64, 0.2); border: 2px solid var(--accent); }
        .result.fail { background: rgba(255, 68, 68, 0.2); border: 2px solid #ff4444; }
        .btn { background: var(--accent); color: var(--primary); border: none; padding: 12px 28px; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 12px; }
        .final-box { background: var(--card-bg); border-radius: 16px; padding: 35px; margin-top: 20px; }
        .final-score { font-size: 3rem; color: var(--accent); font-weight: bold; }
        .sats-earned { font-size: 1.5rem; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Zurück zur Übersicht</a>
        
        <?php if (isset($finalScore)): ?>
        <!-- FINALE ANZEIGE -->
        <h1>🎨 Muster <span>beendet!</span></h1>
        <div class="final-box">
            <div class="final-score"><?php echo $finalScore; ?>/10</div>
            <div class="sats-earned">🌟 <?php echo $totalSats; ?> Sats verdient!</div>
            <p style="color:#a0a0a0;">Super gemacht, <?php echo htmlspecialchars($userName); ?>!</p>
            <button class="btn" onclick="location.href='muster.php'">🔄 Neue Runde</button>
            <button class="btn" onclick="location.href='index.php'" style="background:#2a4a0e;color:#fff;">← Andere Rätsel</button>
        </div>
        <?php else: ?>
        <!-- QUIZ -->
        <h1>🎨 Muster <span>fortsetzen</span></h1>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo ($currentQ / 10) * 100; ?>%"></div>
            <div class="progress-text">Frage <?php echo $currentQ; ?> / 10</div>
        </div>
        
        <div class="stats">
            <div>✅ Richtig: <span id="correctCount"><?php echo $session['correct']; ?></span></div>
            <div>🌟 Sats: <span id="satsCount"><?php echo $session['total_sats']; ?></span></div>
        </div>
        
        <div class="sequence-box">
            <div class="sequence">
                <?php foreach ($pattern['seq'] as $item): ?>
                    <span><?php echo $item; ?></span>
                <?php endforeach; ?>
                <span class="question-mark">?</span>
            </div>
            <div class="options">
                <?php 
                $opts = $pattern['opts'];
                shuffle($opts);
                foreach ($opts as $o): ?>
                    <div class="option" onclick="checkAnswer(this, '<?php echo $o; ?>')"><?php echo $o; ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="result" id="result"></div>
        <?php endif; ?>
    </div>

    <?php if (!isset($finalScore)): ?>
    <script>
        const correct = <?php echo json_encode($pattern['ans']); ?>;
        const sats = <?php echo $pattern['sats']; ?>;
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
                result.innerHTML = '✅ Richtig! +' + sats + ' Sats';
                // Sats vergeben
                awardSats(sats);
                updateSession(true, sats);
            } else {
                el.classList.add('wrong');
                document.querySelectorAll('.option').forEach(o => { if (o.textContent === correct) o.classList.add('correct'); });
                result.className = 'result show fail';
                result.innerHTML = '❌ Falsch! Richtig war: ' + correct;
                updateSession(false, 0);
            }
            
            setTimeout(() => location.reload(), 1500);
        }
        
        function awardSats(amount) {
            if (childId > 0) {
                fetch('/wallet/api.php?action=earn', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({child_id: childId, score: 1, max_score: 1, module: 'logik_muster'})
                });
            }
        }
        
        function updateSession(correct, sats) {
            fetch('api/update_session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type: 'muster', correct: correct, sats: sats})
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
