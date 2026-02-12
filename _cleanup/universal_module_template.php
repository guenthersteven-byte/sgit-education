<?php
/**
 * UNIVERSELLES MODUL-TEMPLATE MIT VERBESSERTER FRAGEN-AUSWAHL
 * 
 * Features:
 * - KEINE Duplikate in einer 10-Fragen-Session
 * - Zufällige Position der richtigen Antwort
 * - Intelligente falsche Antworten
 * - Session-Memory verhindert Wiederholungen
 * - Bessere Verteilung aus 1000+ Fragen-Pool
 */

session_start();

// ========================================
// KONFIGURATION
// ========================================
$module_config = [
    'mathematik' => [
        'name' => 'Mathematik',
        'icon' => '🔢',
        'color1' => '#667eea',
        'color2' => '#764ba2',
        'categories' => ['addition', 'subtraktion', 'multiplikation', 'division', 'brüche', 'prozent']
    ],
    'lesen' => [
        'name' => 'Lesen',
        'icon' => '📖',
        'color1' => '#FF6B6B',
        'color2' => '#FFE66D',
        'categories' => ['buchstaben', 'silben', 'wörter', 'sätze', 'texte', 'grammatik']
    ],
    'englisch' => [
        'name' => 'Englisch',
        'icon' => '🇬🇧',
        'color1' => '#4ECDC4',
        'color2' => '#44A08D',
        'categories' => ['vocabulary', 'grammar', 'phrases', 'tenses', 'conversation']
    ],
    'wissenschaft' => [
        'name' => 'Wissenschaft',
        'icon' => '🔬',
        'color1' => '#667eea',
        'color2' => '#764ba2',
        'categories' => ['natur', 'experimente', 'biologie', 'physik', 'chemie']
    ],
    'erdkunde' => [
        'name' => 'Erdkunde',
        'icon' => '🌍',
        'color1' => '#f093fb',
        'color2' => '#f5576c',
        'categories' => ['länder', 'hauptstädte', 'kontinente', 'flüsse', 'berge']
    ],
    'chemie' => [
        'name' => 'Chemie',
        'icon' => '⚗️',
        'color1' => '#fa709a',
        'color2' => '#fee140',
        'categories' => ['elemente', 'verbindungen', 'reaktionen', 'säuren', 'basen']
    ],
    'physik' => [
        'name' => 'Physik',
        'icon' => '⚛️',
        'color1' => '#30cfd0',
        'color2' => '#330867',
        'categories' => ['mechanik', 'elektrizität', 'optik', 'wärme', 'schall']
    ],
    'kunst' => [
        'name' => 'Kunst',
        'icon' => '🎨',
        'color1' => '#a8edea',
        'color2' => '#fed6e3',
        'categories' => ['farben', 'techniken', 'künstler', 'epochen', 'werke']
    ],
    'musik' => [
        'name' => 'Musik',
        'icon' => '🎵',
        'color1' => '#d299c2',
        'color2' => '#fef9d7',
        'categories' => ['noten', 'instrumente', 'komponisten', 'rhythmus', 'theorie']
    ],
    'computer' => [
        'name' => 'Computer',
        'icon' => '💻',
        'color1' => '#89f7fe',
        'color2' => '#66a6ff',
        'categories' => ['hardware', 'software', 'programmierung', 'internet', 'sicherheit']
    ],
    'bitcoin' => [
        'name' => 'Bitcoin',
        'icon' => '₿',
        'color1' => '#F7931A',
        'color2' => '#FDB93C',
        'categories' => ['geld', 'blockchain', 'freiheit', 'österreichische_schule', 'zukunft']
    ],
    'geschichte' => [
        'name' => 'Geschichte',
        'icon' => '📜',
        'color1' => '#8B4513',
        'color2' => '#DEB887',
        'categories' => ['antike', 'mittelalter', 'neuzeit', 'moderne', 'deutschland']
    ],
    'biologie' => [
        'name' => 'Biologie',
        'icon' => '🧬',
        'color1' => '#4CAF50',
        'color2' => '#8BC34A',
        'categories' => ['mensch', 'tiere', 'pflanzen', 'zellen', 'evolution']
    ],
    'steuern' => [
        'name' => 'Steuern & Finanzen',
        'icon' => '💰',
        'color1' => '#FFD700',
        'color2' => '#FFA500',
        'categories' => ['geld', 'steuern', 'sparen', 'investieren', 'wirtschaft']
    ]
];

// Bestimme aktuelles Modul
$current_module = basename(dirname($_SERVER['PHP_SELF']));
$config = $module_config[$current_module] ?? $module_config['mathematik'];

// ========================================
// VERBESSERTE FRAGEN-AUSWAHL
// ========================================
class ImprovedQuestionSelector {
    private $module;
    private $all_questions = [];
    private $session_key;
    
    public function __construct($module) {
        $this->module = $module;
        $this->session_key = "qs_{$module}";
        $this->loadQuestions();
        $this->initSession();
    }
    
    private function loadQuestions() {
        // Lade Fragen aus JSON
        $json_file = __DIR__ . "/{$this->module}_questions_1000.json";
        
        if (file_exists($json_file)) {
            $this->all_questions = json_decode(file_get_contents($json_file), true);
        } else {
            // Generiere Beispiel-Fragen
            $this->all_questions = $this->generateDynamicQuestions();
        }
    }
    
    private function initSession() {
        if (!isset($_SESSION[$this->session_key]) || isset($_GET['reset'])) {
            $_SESSION[$this->session_key] = [
                'current_10' => [],
                'used_indices' => [],
                'question_num' => 0,
                'correct' => 0,
                'start_time' => time(),
                'history' => [] // Speichere letzte 50 Fragen
            ];
            
            $this->selectNew10Questions();
        }
    }
    
    private function selectNew10Questions() {
        $available_indices = [];
        $total = count($this->all_questions);
        
        // Finde verfügbare Indizes (nicht in History)
        for ($i = 0; $i < $total; $i++) {
            if (!in_array($i, $_SESSION[$this->session_key]['history'])) {
                $available_indices[] = $i;
            }
        }
        
        // Falls nicht genug verfügbar, History teilweise leeren
        if (count($available_indices) < 10) {
            $_SESSION[$this->session_key]['history'] = array_slice(
                $_SESSION[$this->session_key]['history'], 
                -30 // Behalte nur die letzten 30
            );
            
            // Neu berechnen
            $available_indices = [];
            for ($i = 0; $i < $total; $i++) {
                if (!in_array($i, $_SESSION[$this->session_key]['history'])) {
                    $available_indices[] = $i;
                }
            }
        }
        
        // Wähle 10 zufällige aus verfügbaren
        shuffle($available_indices);
        $selected = array_slice($available_indices, 0, 10);
        
        // Speichere ausgewählte Fragen
        $questions = [];
        foreach ($selected as $idx) {
            $questions[] = $this->all_questions[$idx];
            $_SESSION[$this->session_key]['history'][] = $idx;
        }
        
        $_SESSION[$this->session_key]['current_10'] = $questions;
        $_SESSION[$this->session_key]['used_indices'] = $selected;
    }
    
    public function getCurrentQuestion() {
        $num = $_SESSION[$this->session_key]['question_num'];
        if ($num >= 10) return null;
        
        return $_SESSION[$this->session_key]['current_10'][$num];
    }
    
    public function generateAnswerOptions($correct_answer, $question_type = '') {
        $options = [];
        
        // Intelligente Option-Generierung basierend auf Typ
        if (is_numeric($correct_answer)) {
            $options = $this->generateNumericOptions($correct_answer);
        } else {
            $options = $this->generateTextOptions($correct_answer, $question_type);
        }
        
        // WICHTIG: Zufällige Reihenfolge!
        shuffle($options);
        
        return $options;
    }
    
    private function generateNumericOptions($correct) {
        $num = floatval($correct);
        $options = [$correct];
        
        // Intelligente falsche Optionen
        $variations = [
            $num + 1,
            $num - 1,
            $num * 2,
            $num / 2,
            $num + 10,
            $num - 10,
            round($num * 1.5),
            round($num * 0.75)
        ];
        
        // Entferne Duplikate und negative Zahlen (außer wenn richtig negativ)
        $variations = array_unique($variations);
        if ($num >= 0) {
            $variations = array_filter($variations, function($v) { return $v >= 0; });
        }
        
        // Wähle 3 verschiedene falsche Antworten
        shuffle($variations);
        $count = 0;
        foreach ($variations as $v) {
            if ($count >= 3) break;
            if ($v != $num) {
                $options[] = strval($v);
                $count++;
            }
        }
        
        // Falls nicht genug, füge zufällige hinzu
        while (count($options) < 4) {
            $random = rand(0, 100);
            if (!in_array(strval($random), $options)) {
                $options[] = strval($random);
            }
        }
        
        return array_slice($options, 0, 4);
    }
    
    private function generateTextOptions($correct, $type = '') {
        // Kontext-basierte Optionen-Pools
        $context_pools = [
            'color' => ['rot', 'blau', 'grün', 'gelb', 'orange', 'lila', 'schwarz', 'weiß', 'braun', 'pink'],
            'animal' => ['Hund', 'Katze', 'Maus', 'Vogel', 'Fisch', 'Pferd', 'Kuh', 'Schwein', 'Huhn', 'Schaf'],
            'country' => ['Deutschland', 'Frankreich', 'England', 'Spanien', 'Italien', 'Polen', 'Schweiz', 'Österreich'],
            'city' => ['Berlin', 'Paris', 'London', 'Rom', 'Madrid', 'Wien', 'Zürich', 'München', 'Hamburg'],
            'element' => ['Wasserstoff', 'Sauerstoff', 'Kohlenstoff', 'Stickstoff', 'Eisen', 'Gold', 'Silber'],
            'verb' => ['gehen', 'laufen', 'springen', 'sitzen', 'stehen', 'liegen', 'schwimmen', 'fliegen']
        ];
        
        $options = [$correct];
        $pool_used = false;
        
        // Versuche passenden Pool zu finden
        foreach ($context_pools as $context => $pool) {
            if (stripos($type, $context) !== false || in_array($correct, $pool)) {
                // Füge falsche Optionen aus diesem Pool hinzu
                shuffle($pool);
                foreach ($pool as $item) {
                    if (count($options) >= 4) break;
                    if (strcasecmp($item, $correct) != 0) {
                        $options[] = $item;
                    }
                }
                $pool_used = true;
                break;
            }
        }
        
        // Falls kein passender Pool, generiere Variationen
        if (!$pool_used) {
            $length = strlen($correct);
            $variations = [];
            
            // Ähnliche Wörter generieren
            if ($length > 3) {
                $variations[] = substr($correct, 0, -1) . 'e';
                $variations[] = substr($correct, 0, -1) . 'er';
                $variations[] = substr($correct, 0, -2) . 'en';
                $variations[] = 'Un' . $correct;
                $variations[] = $correct . 's';
            }
            
            foreach ($variations as $var) {
                if (count($options) >= 4) break;
                if ($var != $correct) {
                    $options[] = $var;
                }
            }
        }
        
        // Fülle mit generischen Optionen auf
        $generic = ['Keine Antwort', 'Weiß nicht', 'Falsch', 'Anders'];
        while (count($options) < 4) {
            $g = $generic[rand(0, count($generic) - 1)];
            if (!in_array($g, $options)) {
                $options[] = $g;
            }
        }
        
        return array_slice($options, 0, 4);
    }
    
    public function checkAnswer($answer) {
        $current = $this->getCurrentQuestion();
        if (!$current) return false;
        
        $correct = strcasecmp(trim($answer), trim($current['a'])) == 0;
        
        if ($correct) {
            $_SESSION[$this->session_key]['correct']++;
        }
        
        $_SESSION[$this->session_key]['question_num']++;
        
        return $correct;
    }
    
    public function getProgress() {
        $s = $_SESSION[$this->session_key];
        return [
            'current' => $s['question_num'] + 1,
            'total' => 10,
            'correct' => $s['correct'],
            'percentage' => $s['question_num'] > 0 
                ? round(($s['correct'] / $s['question_num']) * 100) 
                : 0
        ];
    }
    
    public function isComplete() {
        return $_SESSION[$this->session_key]['question_num'] >= 10;
    }
    
    private function generateDynamicQuestions() {
        // Fallback: Generiere dynamische Fragen
        $questions = [];
        
        for ($i = 0; $i < 100; $i++) {
            $a = rand(1, 50);
            $b = rand(1, 50);
            $questions[] = [
                'q' => "$a + $b = ?",
                'a' => strval($a + $b),
                'type' => 'addition'
            ];
        }
        
        return $questions;
    }
}

// ========================================
// HAUPTLOGIK
// ========================================

$selector = new ImprovedQuestionSelector($current_module);

// Verarbeite Antwort
$feedback = '';
if (isset($_POST['user_answer'])) {
    $is_correct = $selector->checkAnswer($_POST['user_answer']);
    $feedback = $is_correct ? 'correct' : 'wrong';
}

// Hole aktuelle Daten
$question = $selector->getCurrentQuestion();
$progress = $selector->getProgress();
$is_complete = $selector->isComplete();

// Generiere Optionen für aktuelle Frage
$options = [];
if ($question && !$is_complete) {
    $options = $selector->generateAnswerOptions(
        $question['a'], 
        $question['type'] ?? ''
    );
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $config['icon'] ?> <?= $config['name'] ?> - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, <?= $config['color1'] ?> 0%, <?= $config['color2'] ?> 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .main-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            max-width: 900px;
            width: 100%;
            padding: 40px;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .module-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        .module-title {
            font-size: 2.5em;
            background: linear-gradient(135deg, <?= $config['color1'] ?>, <?= $config['color2'] ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .progress-container {
            background: #f0f0f0;
            border-radius: 20px;
            height: 40px;
            overflow: hidden;
            margin: 30px 0;
            position: relative;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #43D240, #6FFF00);
            height: 100%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .question-box {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 20px;
            padding: 40px;
            margin: 30px 0;
        }
        
        .question-text {
            font-size: 1.8em;
            color: #333;
            text-align: center;
            margin-bottom: 40px;
            font-weight: 500;
        }
        
        .answers-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .answer-btn {
            background: white;
            border: 3px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
            font-size: 1.3em;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .answer-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(67, 210, 64, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .answer-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #43D240;
        }
        
        .answer-btn:hover::before {
            left: 100%;
        }
        
        .answer-btn.selected {
            background: linear-gradient(135deg, #43D240, #6FFF00);
            color: white;
            border-color: #43D240;
            transform: scale(1.05);
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #1A3503, #2d5a0a);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 20px 60px;
            font-size: 1.4em;
            cursor: pointer;
            display: block;
            margin: 0 auto;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 35px rgba(26, 53, 3, 0.3);
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            background: linear-gradient(135deg, <?= $config['color1'] ?>, <?= $config['color2'] ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .feedback-overlay {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 40px 80px;
            border-radius: 20px;
            font-size: 2em;
            font-weight: bold;
            color: white;
            z-index: 1000;
            animation: feedbackPulse 1s ease;
        }
        
        @keyframes feedbackPulse {
            0% { transform: translate(-50%, -50%) scale(0); opacity: 0; }
            50% { transform: translate(-50%, -50%) scale(1.2); opacity: 1; }
            100% { transform: translate(-50%, -50%) scale(1); opacity: 0.9; }
        }
        
        .feedback-overlay.correct {
            background: linear-gradient(135deg, #4caf50, #8bc34a);
        }
        
        .feedback-overlay.wrong {
            background: linear-gradient(135deg, #f44336, #e91e63);
        }
        
        .completion-screen {
            text-align: center;
            padding: 60px;
        }
        
        .completion-icon {
            font-size: 6em;
            margin-bottom: 30px;
        }
        
        .completion-title {
            font-size: 3em;
            color: #1A3503;
            margin-bottom: 20px;
        }
        
        .score-display {
            font-size: 5em;
            font-weight: bold;
            margin: 30px 0;
        }
        
        .score-excellent { color: #4caf50; }
        .score-good { color: #8bc34a; }
        .score-ok { color: #ff9800; }
        .score-poor { color: #f44336; }
        
        .action-buttons {
            margin-top: 40px;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #43D240, #6FFF00);
            color: white;
            text-decoration: none;
            padding: 20px 50px;
            border-radius: 15px;
            display: inline-block;
            margin: 10px;
            font-size: 1.2em;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 35px rgba(67, 210, 64, 0.3);
        }
        
        .action-btn.secondary {
            background: linear-gradient(135deg, #1A3503, #2d5a0a);
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php if ($is_complete): ?>
            <!-- Abschluss-Bildschirm -->
            <div class="completion-screen">
                <div class="completion-icon">
                    <?php
                    $percentage = ($progress['correct'] / 10) * 100;
                    if ($percentage >= 90) echo '🏆';
                    elseif ($percentage >= 70) echo '🌟';
                    elseif ($percentage >= 50) echo '👍';
                    else echo '💪';
                    ?>
                </div>
                
                <h1 class="completion-title">Geschafft!</h1>
                
                <div class="score-display <?php
                    if ($percentage >= 90) echo 'score-excellent';
                    elseif ($percentage >= 70) echo 'score-good';
                    elseif ($percentage >= 50) echo 'score-ok';
                    else echo 'score-poor';
                ?>">
                    <?= $progress['correct'] ?> / 10
                </div>
                
                <p style="font-size: 1.5em; color: #666;">
                    <?php
                    if ($percentage >= 90) echo 'Hervorragend! Du bist ein Meister!';
                    elseif ($percentage >= 70) echo 'Sehr gut gemacht! Weiter so!';
                    elseif ($percentage >= 50) echo 'Gut! Mit etwas Übung wird es noch besser!';
                    else echo 'Nicht aufgeben! Übung macht den Meister!';
                    ?>
                </p>
                
                <div class="stats-row" style="max-width: 600px; margin: 40px auto;">
                    <div class="stat-card">
                        <div class="stat-value"><?= $percentage ?>%</div>
                        <div class="stat-label">Erfolgsrate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $progress['correct'] * 10 ?></div>
                        <div class="stat-label">Punkte</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">⭐⭐⭐</div>
                        <div class="stat-label">Level Up!</div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="?reset=1" class="action-btn">
                        🔄 Neue Runde
                    </a>
                    <a href="../" class="action-btn secondary">
                        🏠 Zur Übersicht
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Fragen-Anzeige -->
            <div class="header">
                <div class="module-icon"><?= $config['icon'] ?></div>
                <h1 class="module-title"><?= $config['name'] ?></h1>
                <p style="color: #666; font-size: 1.2em;">
                    Frage <?= $progress['current'] ?> von <?= $progress['total'] ?>
                </p>
            </div>
            
            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-bar" style="width: <?= ($progress['current'] - 1) * 10 ?>%;">
                    <?= ($progress['current'] - 1) * 10 ?>%
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value"><?= $progress['correct'] ?></div>
                    <div class="stat-label">Richtig</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $progress['percentage'] ?>%</div>
                    <div class="stat-label">Quote</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $progress['correct'] * 10 ?></div>
                    <div class="stat-label">Punkte</div>
                </div>
            </div>
            
            <?php if ($question): ?>
                <!-- Frage -->
                <div class="question-box">
                    <div class="question-text">
                        <?= htmlspecialchars($question['q']) ?>
                    </div>
                    
                    <form method="POST" id="answerForm">
                        <div class="answers-grid">
                            <?php foreach ($options as $idx => $option): ?>
                                <button type="button" 
                                        class="answer-btn" 
                                        onclick="selectAnswer(this, '<?= htmlspecialchars($option, ENT_QUOTES) ?>')">
                                    <?= htmlspecialchars($option) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <input type="hidden" name="user_answer" id="selectedAnswer">
                        <button type="submit" class="submit-btn">
                            Antwort prüfen ✓
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
    
    <?php if ($feedback): ?>
        <div class="feedback-overlay <?= $feedback ?>">
            <?= $feedback == 'correct' ? '✅ Richtig!' : '❌ Falsch!' ?>
        </div>
        <script>
            setTimeout(() => {
                window.location.href = '?';
            }, 1200);
        </script>
    <?php endif; ?>
    
    <script>
        function selectAnswer(btn, value) {
            // Entferne alle selected Klassen
            document.querySelectorAll('.answer-btn').forEach(b => {
                b.classList.remove('selected');
            });
            
            // Markiere ausgewählte Antwort
            btn.classList.add('selected');
            
            // Setze Wert
            document.getElementById('selectedAnswer').value = value;
        }
        
        // Verhindere leeres Absenden
        document.getElementById('answerForm').addEventListener('submit', (e) => {
            if (!document.getElementById('selectedAnswer').value) {
                e.preventDefault();
                alert('Bitte wähle eine Antwort!');
            }
        });
    </script>
</body>
</html>