<?php
/**
 * ============================================================================
 * sgiT Education - Sudoku Modul v1.0
 * ============================================================================
 * 
 * Interaktives Sudoku mit altersgerechten Schwierigkeitsstufen:
 * - 4x4 Grid für Kinder (5-10 Jahre)
 * - 6x6 Grid für Mittelstufe (11-14 Jahre)  
 * - 9x9 Grid für Fortgeschrittene (15+ Jahre)
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
require_once dirname(__DIR__) . '/includes/version.php';

// User-Daten aus Session
$userAge = $_SESSION['user_age'] ?? 10;
$userName = $_SESSION['child_name'] ?? 'Sudoku-Fan';
$childId = $_SESSION['wallet_child_id'] ?? 0;

// Grid-Größe basierend auf Alter
function getGridConfig($age) {
    if ($age <= 10) {
        return ['size' => 4, 'blockW' => 2, 'blockH' => 2, 'name' => 'Mini', 'sats' => [15, 25, 35]];
    } elseif ($age <= 14) {
        return ['size' => 6, 'blockW' => 3, 'blockH' => 2, 'name' => 'Medium', 'sats' => [25, 40, 55]];
    } else {
        return ['size' => 9, 'blockW' => 3, 'blockH' => 3, 'name' => 'Classic', 'sats' => [40, 60, 75]];
    }
}

$gridConfig = getGridConfig($userAge);
$gridSize = $gridConfig['size'];
$blockW = $gridConfig['blockW'];
$blockH = $gridConfig['blockH'];

// Session für Sudoku-Runde initialisieren
if (!isset($_SESSION['sudoku_session']) || isset($_GET['new'])) {
    $_SESSION['sudoku_session'] = [
        'puzzles_solved' => 0,
        'total_sats' => 0,
        'start_time' => time(),
        'current_puzzle' => null
    ];
}
$sudokuSession = &$_SESSION['sudoku_session'];

/**
 * Sudoku Generator - Erzeugt ein gültiges Sudoku-Rätsel
 */
class SudokuGenerator {
    private $size;
    private $blockW;
    private $blockH;
    private $grid;
    
    public function __construct($size, $blockW, $blockH) {
        $this->size = $size;
        $this->blockW = $blockW;
        $this->blockH = $blockH;
        $this->grid = array_fill(0, $size, array_fill(0, $size, 0));
    }
    
    // Prüft ob Zahl an Position gültig ist
    private function isValid($row, $col, $num) {
        // Zeile prüfen
        for ($x = 0; $x < $this->size; $x++) {
            if ($this->grid[$row][$x] === $num) return false;
        }
        // Spalte prüfen
        for ($x = 0; $x < $this->size; $x++) {
            if ($this->grid[$x][$col] === $num) return false;
        }
        // Block prüfen
        $startRow = floor($row / $this->blockH) * $this->blockH;
        $startCol = floor($col / $this->blockW) * $this->blockW;
        for ($i = 0; $i < $this->blockH; $i++) {
            for ($j = 0; $j < $this->blockW; $j++) {
                if ($this->grid[$startRow + $i][$startCol + $j] === $num) return false;
            }
        }
        return true;
    }

    // Löst das Sudoku mit Backtracking
    private function solve() {
        for ($row = 0; $row < $this->size; $row++) {
            for ($col = 0; $col < $this->size; $col++) {
                if ($this->grid[$row][$col] === 0) {
                    $nums = range(1, $this->size);
                    shuffle($nums);
                    foreach ($nums as $num) {
                        if ($this->isValid($row, $col, $num)) {
                            $this->grid[$row][$col] = $num;
                            if ($this->solve()) return true;
                            $this->grid[$row][$col] = 0;
                        }
                    }
                    return false;
                }
            }
        }
        return true;
    }
    
    // Entfernt Zahlen um Puzzle zu erstellen
    private function removeNumbers($difficulty) {
        // Difficulty: 0=easy, 1=medium, 2=hard
        $totalCells = $this->size * $this->size;
        $removePercent = [0.35, 0.45, 0.55]; // Easy, Medium, Hard
        $toRemove = floor($totalCells * $removePercent[$difficulty]);
        
        $positions = [];
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                $positions[] = [$r, $c];
            }
        }
        shuffle($positions);
        
        for ($i = 0; $i < $toRemove && $i < count($positions); $i++) {
            list($r, $c) = $positions[$i];
            $this->grid[$r][$c] = 0;
        }
    }

    // Generiert ein neues Puzzle
    public function generate($difficulty = 0) {
        $this->grid = array_fill(0, $this->size, array_fill(0, $this->size, 0));
        $this->solve();
        $solution = $this->grid;
        $this->removeNumbers($difficulty);
        return [
            'puzzle' => $this->grid,
            'solution' => $solution,
            'size' => $this->size,
            'blockW' => $this->blockW,
            'blockH' => $this->blockH
        ];
    }
}

// Schwierigkeit aus GET oder zufällig
$difficulty = isset($_GET['diff']) ? intval($_GET['diff']) : rand(0, 2);
$difficulty = max(0, min(2, $difficulty));
$diffNames = ['Leicht', 'Mittel', 'Schwer'];
$diffEmoji = ['🟢', '🟡', '🔴'];

// Puzzle generieren oder aus Session laden
if (!$sudokuSession['current_puzzle'] || isset($_GET['new'])) {
    $generator = new SudokuGenerator($gridSize, $blockW, $blockH);
    $sudokuSession['current_puzzle'] = $generator->generate($difficulty);
    $sudokuSession['current_puzzle']['difficulty'] = $difficulty;
    $sudokuSession['current_puzzle']['start_time'] = time();
}

$puzzle = $sudokuSession['current_puzzle'];
$currentDiff = $puzzle['difficulty'];
$satsReward = $gridConfig['sats'][$currentDiff];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Sudoku - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/fonts.css">
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
            --error: #ff4444;
            --success: #43D240;
        }
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, var(--primary) 100%);
            min-height: 100vh;
            color: var(--text);
            padding: 15px;
        }
        .container { max-width: 600px; margin: 0 auto; text-align: center; }
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
            gap: 20px;
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
        
        .sudoku-container {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .sudoku-grid {
            display: grid;
            gap: 2px;
            background: var(--primary);
            padding: 3px;
            border-radius: 8px;
            margin: 0 auto;
        }
        .sudoku-cell {
            width: 42px;
            height: 42px;
            background: var(--cell-bg);
            border: none;
            color: var(--text);
            font-size: 1.3rem;
            font-weight: 600;
            text-align: center;
            outline: none;
            transition: all 0.2s;
            border-radius: 4px;
        }
        .sudoku-cell:focus {
            background: var(--primary);
            box-shadow: 0 0 0 2px var(--accent);
        }
        .sudoku-cell.given {
            background: #3a5a1e;
            color: var(--accent);
            cursor: not-allowed;
        }
        .sudoku-cell.error {
            background: rgba(255, 68, 68, 0.3);
            color: var(--error);
        }
        .sudoku-cell.correct {
            background: rgba(67, 210, 64, 0.2);
        }
        
        /* Block-Grenzen */
        .sudoku-cell.block-right { border-right: 3px solid var(--accent); }
        .sudoku-cell.block-bottom { border-bottom: 3px solid var(--accent); }
        
        .number-pad {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
            margin: 15px 0;
        }
        .num-btn {
            width: 40px;
            height: 40px;
            background: var(--cell-bg);
            border: 2px solid transparent;
            border-radius: 8px;
            color: var(--text);
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .num-btn:hover {
            border-color: var(--accent);
            transform: scale(1.1);
        }
        .num-btn.selected {
            background: var(--accent);
            color: var(--primary);
        }
        .num-btn.clear { background: var(--error); }
        
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
        .btn.secondary { background: var(--cell-bg); color: var(--text); }
        
        .result-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
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
        .result-time { color: var(--text-muted); font-size: 0.9rem; }
        
        .timer {
            font-size: 1.1rem;
            font-family: 'Courier New', monospace;
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Zurück zu Logik & Rätsel</a>
        
        <header>
            <h1>📊 <span>Sudoku</span> <?php echo $gridConfig['name']; ?></h1>
            <p class="subtitle"><?php echo $gridSize; ?>×<?php echo $gridSize; ?> Grid für <?php echo htmlspecialchars($userName); ?></p>
        </header>
        
        <div class="info-bar">
            <div class="info-item">
                <?php echo $diffEmoji[$currentDiff]; ?> <span><?php echo $diffNames[$currentDiff]; ?></span>
            </div>
            <div class="info-item">
                ⏱️ <span class="timer" id="timer">00:00</span>
            </div>
            <div class="info-item">
                🌟 <span><?php echo $satsReward; ?> Sats</span>
            </div>
            <div class="info-item">
                ✅ Gelöst: <span id="solved-count"><?php echo $sudokuSession['puzzles_solved']; ?></span>
            </div>
        </div>
        
        <div class="sudoku-container">
            <div class="sudoku-grid" id="sudoku-grid" style="grid-template-columns: repeat(<?php echo $gridSize; ?>, 42px);">
                <?php
                for ($row = 0; $row < $gridSize; $row++) {
                    for ($col = 0; $col < $gridSize; $col++) {
                        $value = $puzzle['puzzle'][$row][$col];
                        $isGiven = $value !== 0;
                        $classes = ['sudoku-cell'];
                        if ($isGiven) $classes[] = 'given';
                        // Block-Grenzen
                        if (($col + 1) % $blockW === 0 && $col < $gridSize - 1) $classes[] = 'block-right';
                        if (($row + 1) % $blockH === 0 && $row < $gridSize - 1) $classes[] = 'block-bottom';
                        
                        echo '<input type="text" class="' . implode(' ', $classes) . '" ';
                        echo 'data-row="' . $row . '" data-col="' . $col . '" ';
                        echo 'maxlength="1" inputmode="numeric" ';
                        if ($isGiven) {
                            echo 'value="' . $value . '" readonly ';
                        }
                        echo '>';
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="number-pad">
            <?php for ($i = 1; $i <= $gridSize; $i++): ?>
                <button class="num-btn" onclick="selectNumber(<?php echo $i; ?>)"><?php echo $i; ?></button>
            <?php endfor; ?>
            <button class="num-btn clear" onclick="selectNumber(0)">✕</button>
        </div>
        
        <div class="actions">
            <button class="btn" onclick="checkSolution()">✓ Prüfen</button>
            <button class="btn secondary" onclick="getHint()">💡 Hinweis</button>
            <button class="btn secondary" onclick="location.href='sudoku.php?new=1'">🔄 Neues Puzzle</button>
        </div>
    </div>
    
    <!-- Erfolgs-Overlay -->
    <div class="result-overlay" id="result-overlay">
        <div class="result-box">
            <div class="result-icon" id="result-icon">🎉</div>
            <div class="result-title" id="result-title">Perfekt gelöst!</div>
            <div class="result-sats" id="result-sats">+<?php echo $satsReward; ?> Sats</div>
            <div class="result-time" id="result-time"></div>
            <div class="actions" style="margin-top:20px;">
                <button class="btn" onclick="location.href='sudoku.php?new=1'">▶️ Weiter</button>
                <button class="btn secondary" onclick="location.href='index.php'">← Zurück</button>
            </div>
        </div>
    </div>
    
    <script>
        // Sudoku-Daten vom Server
        const solution = <?php echo json_encode($puzzle['solution']); ?>;
        const gridSize = <?php echo $gridSize; ?>;
        const satsReward = <?php echo $satsReward; ?>;
        const childId = <?php echo $childId; ?>;
        const startTime = <?php echo $puzzle['start_time']; ?>;
        
        let selectedNumber = null;
        let hintsUsed = 0;
        let timerInterval;
        
        // Timer starten
        function updateTimer() {
            const elapsed = Math.floor(Date.now() / 1000) - startTime;
            const mins = Math.floor(elapsed / 60).toString().padStart(2, '0');
            const secs = (elapsed % 60).toString().padStart(2, '0');
            document.getElementById('timer').textContent = mins + ':' + secs;
        }
        timerInterval = setInterval(updateTimer, 1000);
        updateTimer();
        
        // Zahl auswählen
        function selectNumber(num) {
            document.querySelectorAll('.num-btn').forEach(b => b.classList.remove('selected'));
            if (num > 0) {
                event.target.classList.add('selected');
            }
            selectedNumber = num;
        }
        
        // Zelle klicken
        document.querySelectorAll('.sudoku-cell:not(.given)').forEach(cell => {
            cell.addEventListener('click', function() {
                if (selectedNumber !== null) {
                    this.value = selectedNumber === 0 ? '' : selectedNumber;
                    validateCell(this);
                }
            });
            cell.addEventListener('input', function() {
                let val = this.value.replace(/[^1-9]/g, '');
                if (parseInt(val) > gridSize) val = '';
                this.value = val;
                validateCell(this);
            });
        });
        
        // Zelle validieren (live Feedback)
        function validateCell(cell) {
            const row = parseInt(cell.dataset.row);
            const col = parseInt(cell.dataset.col);
            const val = parseInt(cell.value) || 0;
            
            cell.classList.remove('error', 'correct');
            
            if (val > 0) {
                if (val === solution[row][col]) {
                    cell.classList.add('correct');
                } else {
                    cell.classList.add('error');
                }
            }
        }
        
        // Gesamte Lösung prüfen
        function checkSolution() {
            let allCorrect = true;
            let allFilled = true;
            
            document.querySelectorAll('.sudoku-cell').forEach(cell => {
                const row = parseInt(cell.dataset.row);
                const col = parseInt(cell.dataset.col);
                const val = parseInt(cell.value) || 0;
                
                if (val === 0) {
                    allFilled = false;
                } else if (val !== solution[row][col]) {
                    allCorrect = false;
                    cell.classList.add('error');
                }
            });
            
            if (!allFilled) {
                showToast('Fülle erst alle Felder aus!', 'warning');
                return;
            }
            
            if (allCorrect) {
                showSuccess();
            } else {
                showToast('Noch nicht richtig - schau dir die roten Felder an!', 'error');
            }
        }
        
        // Erfolg anzeigen
        function showSuccess() {
            clearInterval(timerInterval);
            
            const elapsed = Math.floor(Date.now() / 1000) - startTime;
            const mins = Math.floor(elapsed / 60);
            const secs = elapsed % 60;
            
            // Bonus für schnelles Lösen
            let bonus = 0;
            if (elapsed < 60) bonus = Math.round(satsReward * 0.5);
            else if (elapsed < 120) bonus = Math.round(satsReward * 0.25);
            
            const totalSats = satsReward + bonus - (hintsUsed * 5);
            const finalSats = Math.max(5, totalSats);
            
            document.getElementById('result-sats').textContent = '+' + finalSats + ' Sats';
            document.getElementById('result-time').textContent = 
                'Zeit: ' + mins + ':' + secs.toString().padStart(2, '0') + 
                (bonus > 0 ? ' (Zeitbonus: +' + bonus + ')' : '') +
                (hintsUsed > 0 ? ' (Hinweise: -' + (hintsUsed * 5) + ')' : '');
            
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
                        module: 'sudoku_' + gridSize,
                        sats: finalSats
                    })
                });
            }
            
            // Session updaten
            fetch('api/update_session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type: 'sudoku', correct: true, sats: finalSats})
            });
        }
        
        // Hinweis geben
        function getHint() {
            const emptyCells = [];
            document.querySelectorAll('.sudoku-cell:not(.given)').forEach(cell => {
                if (!cell.value || cell.classList.contains('error')) {
                    emptyCells.push(cell);
                }
            });
            
            if (emptyCells.length === 0) {
                showToast('Keine leeren Felder mehr!', 'info');
                return;
            }
            
            const randomCell = emptyCells[Math.floor(Math.random() * emptyCells.length)];
            const row = parseInt(randomCell.dataset.row);
            const col = parseInt(randomCell.dataset.col);
            
            randomCell.value = solution[row][col];
            randomCell.classList.add('correct');
            randomCell.style.animation = 'popIn 0.3s ease';
            
            hintsUsed++;
            showToast('Hinweis! (-5 Sats)', 'info');
        }
        
        // Toast Nachricht
        function showToast(msg, type) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
                background: ${type === 'error' ? '#ff4444' : type === 'warning' ? '#ffaa00' : '#43D240'};
                color: ${type === 'warning' ? '#000' : '#fff'};
                padding: 12px 24px; border-radius: 10px; font-weight: 600;
                z-index: 200; animation: popIn 0.3s ease;
            `;
            toast.textContent = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        }
    </script>
</body>
</html>
