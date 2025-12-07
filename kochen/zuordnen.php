<?php
/**
 * Lebensmittel zuordnen - Kochen Modul v3.0
 * Design angepasst an restliche Module
 */
session_start();

$userAge = $_SESSION['user_age'] ?? 10;
$userName = $_SESSION['child_name'] ?? 'Küchenchef';
$childId = $_SESSION['wallet_child_id'] ?? 0;

if (isset($_GET['reset'])) {
    unset($_SESSION['kochen_zuordnen']);
    header('Location: zuordnen.php');
    exit;
}

if (!isset($_SESSION['kochen_zuordnen'])) {
    $_SESSION['kochen_zuordnen'] = ['question' => 0, 'correct' => 0, 'total_sats' => 0];
}
$session = &$_SESSION['kochen_zuordnen'];

$finished = false;
if ($session['question'] >= 10) {
    $finished = true;
    $finalScore = $session['correct'];
    $totalSats = $session['total_sats'];
    unset($_SESSION['kochen_zuordnen']);
} else {
    $session['question']++;
}
$currentQ = $session['question'] ?? 0;

$allTasks = [
    ['icon' => '🍎', 'name' => 'Apfel', 'q' => 'Wozu gehört der Apfel?', 'opts' => ['Obst','Gemüse','Milch','Getreide'], 'ans' => 'Obst', 'sats' => 5],
    ['icon' => '🥕', 'name' => 'Karotte', 'q' => 'Wozu gehört die Karotte?', 'opts' => ['Gemüse','Obst','Milch','Fleisch'], 'ans' => 'Gemüse', 'sats' => 5],
    ['icon' => '🥛', 'name' => 'Milch', 'q' => 'Wozu gehört die Milch?', 'opts' => ['Milchprodukt','Obst','Gemüse','Getreide'], 'ans' => 'Milchprodukt', 'sats' => 5],
    ['icon' => '🍞', 'name' => 'Brot', 'q' => 'Wozu gehört das Brot?', 'opts' => ['Getreide','Obst','Gemüse','Milch'], 'ans' => 'Getreide', 'sats' => 5],
    ['icon' => '🍌', 'name' => 'Banane', 'q' => 'Wozu gehört die Banane?', 'opts' => ['Obst','Gemüse','Fleisch','Milch'], 'ans' => 'Obst', 'sats' => 5],
    ['icon' => '🧀', 'name' => 'Käse', 'q' => 'Wozu gehört der Käse?', 'opts' => ['Milchprodukt','Obst','Getreide','Gemüse'], 'ans' => 'Milchprodukt', 'sats' => 5],
    ['icon' => '🥦', 'name' => 'Brokkoli', 'q' => 'Wozu gehört der Brokkoli?', 'opts' => ['Gemüse','Obst','Getreide','Fleisch'], 'ans' => 'Gemüse', 'sats' => 5],
    ['icon' => '🍗', 'name' => 'Hähnchen', 'q' => 'Was liefert Hähnchen?', 'opts' => ['Proteine','Kohlenhydrate','Ballaststoffe','Vitamin C'], 'ans' => 'Proteine', 'sats' => 8],
    ['icon' => '🍝', 'name' => 'Nudeln', 'q' => 'Was liefern Nudeln?', 'opts' => ['Kohlenhydrate','Proteine','Fette','Vitamine'], 'ans' => 'Kohlenhydrate', 'sats' => 8],
    ['icon' => '🥚', 'name' => 'Ei', 'q' => 'Was liefert ein Ei?', 'opts' => ['Proteine','Kohlenhydrate','Ballaststoffe','Vitamin C'], 'ans' => 'Proteine', 'sats' => 8],
];

if (!$finished) {
    $task = $allTasks[array_rand($allTasks)];
    shuffle($task['opts']);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍎 Zuordnen - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #1A3503; --accent: #43D240; --bg: #0d1f02; --card-bg: #1e3a08; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, var(--bg), var(--primary)); min-height: 100vh; color: #fff; padding: 20px; }
        .container { max-width: 650px; margin: 0 auto; text-align: center; }
        .back-link { color: var(--accent); text-decoration: none; display: inline-block; margin-bottom: 15px; }
        .back-link:hover { text-decoration: underline; }
        h1 { font-size: 1.8rem; margin-bottom: 8px; }
        h1 span { color: var(--accent); }
        .progress-bar { background: #2a4a0e; border-radius: 20px; height: 24px; margin: 15px 0; overflow: hidden; position: relative; }
        .progress-fill { background: var(--accent); height: 100%; transition: width 0.3s; }
        .progress-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; font-size: 0.85rem; }
        .stats { display: flex; justify-content: center; gap: 30px; margin-bottom: 20px; font-size: 0.95rem; }
        .stats span { color: var(--accent); font-weight: bold; }
        .item-box { background: var(--card-bg); border-radius: 14px; padding: 25px; margin-bottom: 20px; }
        .item-icon { font-size: 4rem; margin-bottom: 10px; }
        .item-name { font-size: 1.3rem; color: var(--accent); margin-bottom: 8px; }
        .question { font-size: 1.1rem; color: #a0a0a0; margin-bottom: 20px; }
        .options { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .option { 
            background: #2a4a0e; border: 2px solid transparent; border-radius: 12px; 
            padding: 16px; font-size: 1rem; cursor: pointer; transition: all 0.2s; color: #fff;
        }
        .option:hover { border-color: var(--accent); transform: scale(1.03); }
        .option.correct { background: var(--accent) !important; color: var(--primary); font-weight: bold; }
        .option.wrong { background: #ff4444 !important; }
        .option:disabled { cursor: not-allowed; }
        .result { margin-top: 18px; padding: 18px; border-radius: 10px; display: none; font-size: 1.1rem; }
        .result.show { display: block; }
        .result.success { background: rgba(67, 210, 64, 0.2); border: 2px solid var(--accent); }
        .result.fail { background: rgba(255, 68, 68, 0.2); border: 2px solid #ff4444; }
        .btn { background: var(--accent); color: var(--primary); border: none; padding: 12px 28px; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; margin: 8px; }
        .btn:hover { opacity: 0.9; }
        .btn.secondary { background: #2a4a0e; color: #fff; }
        .final-box { background: var(--card-bg); border-radius: 16px; padding: 35px; margin-top: 20px; }
        .final-score { font-size: 3rem; color: var(--accent); font-weight: bold; }
        .sats-earned { font-size: 1.5rem; margin: 15px 0; }
    </style>
</head>
<body>
<div class="container">
    <a href="index.php" class="back-link">← Zurück zur Übersicht</a>
    
    <?php if ($finished): ?>
    <h1>🍎 Zuordnen <span>beendet!</span></h1>
    <div class="final-box">
        <div class="final-score"><?php echo $finalScore; ?>/10</div>
        <div class="sats-earned">🌟 <?php echo $totalSats; ?> Sats verdient!</div>
        <p style="color:#a0a0a0;">Toll gemacht, <?php echo htmlspecialchars($userName); ?>!</p>
        <button class="btn" onclick="location.href='zuordnen.php'">🔄 Neue Runde</button>
        <button class="btn secondary" onclick="location.href='index.php'">← Andere Aktivitäten</button>
    </div>
    <?php else: ?>
    <h1>🍎 Lebensmittel <span>zuordnen</span></h1>
    
    <div class="progress-bar">
        <div class="progress-fill" style="width: <?php echo ($currentQ / 10) * 100; ?>%"></div>
        <div class="progress-text">Frage <?php echo $currentQ; ?> / 10</div>
    </div>
    
    <div class="stats">
        <div>✅ Richtig: <span><?php echo $session['correct']; ?></span></div>
        <div>🌟 Sats: <span><?php echo $session['total_sats']; ?></span></div>
    </div>
    
    <div class="item-box">
        <div class="item-icon"><?php echo $task['icon']; ?></div>
        <div class="item-name"><?php echo $task['name']; ?></div>
        <div class="question"><?php echo $task['q']; ?></div>
        
        <div class="options">
            <button class="option" onclick="check(this, '<?php echo $task['opts'][0]; ?>')"><?php echo $task['opts'][0]; ?></button>
            <button class="option" onclick="check(this, '<?php echo $task['opts'][1]; ?>')"><?php echo $task['opts'][1]; ?></button>
            <button class="option" onclick="check(this, '<?php echo $task['opts'][2]; ?>')"><?php echo $task['opts'][2]; ?></button>
            <button class="option" onclick="check(this, '<?php echo $task['opts'][3]; ?>')"><?php echo $task['opts'][3]; ?></button>
        </div>
    </div>
    <div id="result" class="result"></div>
    
    <script>
    var correct = "<?php echo $task['ans']; ?>";
    var sats = <?php echo $task['sats']; ?>;
    var childId = <?php echo $childId; ?>;
    var done = false;
    
    function check(el, ans) {
        if (done) return;
        done = true;
        
        var btns = document.querySelectorAll('.option');
        for (var i = 0; i < btns.length; i++) btns[i].disabled = true;
        
        var result = document.getElementById('result');
        
        if (ans === correct) {
            el.classList.add('correct');
            result.className = 'result show success';
            result.innerHTML = '✅ Richtig! +' + sats + ' Sats';
            
            if (childId > 0) {
                fetch('/wallet/api.php?action=earn', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({child_id: childId, score: 1, max_score: 1, module: 'kochen_zuordnen'})
                });
            }
            fetch('api/update_session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type: 'zuordnen', correct: true, sats: sats})
            });
        } else {
            el.classList.add('wrong');
            for (var i = 0; i < btns.length; i++) {
                if (btns[i].textContent === correct) btns[i].classList.add('correct');
            }
            result.className = 'result show fail';
            result.innerHTML = '❌ Falsch! Richtig wäre: ' + correct;
            fetch('api/update_session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type: 'zuordnen', correct: false, sats: 0})
            });
        }
        setTimeout(function() { location.reload(); }, 1800);
    }
    </script>
    <?php endif; ?>
</div>
</body>
</html>
