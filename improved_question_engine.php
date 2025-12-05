<?php
/**
 * VERBESSERTES FRAGEN-SYSTEM
 * - Keine Duplikate in einer Session
 * - Zufällige Position der richtigen Antwort
 * - Bessere Zufallsauswahl aus großem Pool
 * - Session-Memory für gestellte Fragen
 */

session_start();

// ========================================
// UNIVERSELLE FRAGEN-ENGINE
// ========================================
class QuestionEngine {
    private $module_name;
    private $questions_pool;
    private $session_questions;
    private $current_question_index;
    
    public function __construct($module_name) {
        $this->module_name = $module_name;
        $this->loadQuestions();
        $this->initializeSession();
    }
    
    /**
     * Lade Fragen aus JSON oder generiere sie
     */
    private function loadQuestions() {
        $json_file = __DIR__ . "/{$this->module_name}_questions_1000.json";
        
        if (file_exists($json_file)) {
            $this->questions_pool = json_decode(file_get_contents($json_file), true);
        } else {
            // Fallback: Generiere Beispiel-Fragen
            $this->questions_pool = $this->generateSampleQuestions();
        }
        
        // Mische den kompletten Fragen-Pool
        shuffle($this->questions_pool);
    }
    
    /**
     * Initialisiere oder lade Session
     */
    private function initializeSession() {
        $session_key = "questions_{$this->module_name}";
        
        if (!isset($_SESSION[$session_key]) || isset($_GET['new_session'])) {
            // Neue Session starten
            $_SESSION[$session_key] = [
                'asked_indices' => [],
                'current_10' => [],
                'question_number' => 0,
                'correct_answers' => 0,
                'start_time' => time()
            ];
            
            // Wähle 10 zufällige, einzigartige Fragen
            $this->selectUniqueQuestions();
        }
        
        $this->session_questions = &$_SESSION[$session_key];
    }
    
    /**
     * Wähle 10 einzigartige Fragen für diese Session
     */
    private function selectUniqueQuestions() {
        $total_questions = count($this->questions_pool);
        $selected_indices = [];
        $selected_questions = [];
        
        // Stelle sicher, dass wir genug Fragen haben
        $questions_needed = min(10, $total_questions);
        
        // Wähle zufällige, einzigartige Indizes
        while (count($selected_indices) < $questions_needed) {
            $random_index = rand(0, $total_questions - 1);
            
            // Prüfe ob diese Frage schon in dieser oder letzten Sessions war
            if (!in_array($random_index, $selected_indices) && 
                !in_array($random_index, $_SESSION[$session_key]['asked_indices'])) {
                $selected_indices[] = $random_index;
                $selected_questions[] = $this->questions_pool[$random_index];
            }
        }
        
        // Speichere die ausgewählten Fragen
        $_SESSION[$session_key]['current_10'] = $selected_questions;
        $_SESSION[$session_key]['asked_indices'] = array_merge(
            $_SESSION[$session_key]['asked_indices'], 
            $selected_indices
        );
        
        // Reset asked_indices wenn zu viele (über 50% des Pools)
        if (count($_SESSION[$session_key]['asked_indices']) > $total_questions * 0.5) {
            $_SESSION[$session_key]['asked_indices'] = $selected_indices;
        }
    }
    
    /**
     * Hole die aktuelle Frage
     */
    public function getCurrentQuestion() {
        $question_num = $this->session_questions['question_number'];
        
        if ($question_num >= 10) {
            return null; // Session beendet
        }
        
        return $this->session_questions['current_10'][$question_num];
    }
    
    /**
     * Generiere Multiple-Choice Optionen mit zufälliger Position
     */
    public function generateOptions($correct_answer, $type = 'default') {
        $options = [];
        
        switch($type) {
            case 'number':
                $options = $this->generateNumberOptions($correct_answer);
                break;
            case 'text':
                $options = $this->generateTextOptions($correct_answer);
                break;
            case 'color':
                $options = $this->generateColorOptions($correct_answer);
                break;
            default:
                $options = $this->generateDefaultOptions($correct_answer);
        }
        
        // WICHTIG: Mische die Optionen zufällig!
        shuffle($options);
        
        return $options;
    }
    
    /**
     * Generiere Zahlen-Optionen
     */
    private function generateNumberOptions($correct) {
        $correct_num = intval($correct);
        $options = [$correct];
        
        // Generiere plausible falsche Antworten
        $ranges = [
            [-10, -5, -2, -1],
            [1, 2, 5, 10],
            [$correct_num * 2, $correct_num / 2],
            [$correct_num + 10, $correct_num - 10]
        ];
        
        $used = [$correct_num];
        
        while (count($options) < 4) {
            $offset = $ranges[rand(0, count($ranges) - 1)][rand(0, 1)];
            $wrong = $correct_num + $offset;
            
            if (!in_array($wrong, $used) && $wrong >= 0) {
                $options[] = strval($wrong);
                $used[] = $wrong;
            }
        }
        
        // Stelle sicher, dass wir genau 4 Optionen haben
        while (count($options) < 4) {
            $random = rand(0, 100);
            if (!in_array($random, $used)) {
                $options[] = strval($random);
                $used[] = $random;
            }
        }
        
        return array_slice($options, 0, 4);
    }
    
    /**
     * Generiere Text-Optionen basierend auf Kontext
     */
    private function generateTextOptions($correct) {
        // Beispiel-Pools für verschiedene Kontexte
        $pools = [
            'colors' => ['rot', 'blau', 'grün', 'gelb', 'orange', 'lila', 'pink', 'braun', 'schwarz', 'weiß'],
            'animals' => ['Hund', 'Katze', 'Maus', 'Vogel', 'Fisch', 'Pferd', 'Kuh', 'Schwein', 'Huhn', 'Ente'],
            'objects' => ['Tisch', 'Stuhl', 'Lampe', 'Buch', 'Fenster', 'Tür', 'Auto', 'Ball', 'Baum', 'Haus'],
            'verbs' => ['gehen', 'laufen', 'springen', 'sitzen', 'stehen', 'liegen', 'essen', 'trinken', 'schlafen', 'spielen']
        ];
        
        $options = [$correct];
        
        // Finde passenden Pool oder nutze Zufalls-Pool
        $selected_pool = null;
        foreach ($pools as $pool) {
            if (in_array(strtolower($correct), array_map('strtolower', $pool))) {
                $selected_pool = $pool;
                break;
            }
        }
        
        if (!$selected_pool) {
            // Nutze einen zufälligen Pool
            $selected_pool = $pools[array_rand($pools)];
        }
        
        // Füge falsche Optionen hinzu
        foreach ($selected_pool as $option) {
            if (count($options) >= 4) break;
            if (strtolower($option) != strtolower($correct)) {
                $options[] = $option;
            }
        }
        
        // Falls nicht genug Optionen, füge generische hinzu
        $generic = ['Option A', 'Option B', 'Option C', 'Option D', 'Keine Antwort'];
        while (count($options) < 4) {
            $random = $generic[rand(0, count($generic) - 1)];
            if (!in_array($random, $options)) {
                $options[] = $random;
            }
        }
        
        return array_slice($options, 0, 4);
    }
    
    /**
     * Generiere Farb-Optionen
     */
    private function generateColorOptions($correct) {
        $all_colors = ['rot', 'blau', 'grün', 'gelb', 'orange', 'lila', 'pink', 'braun', 'schwarz', 'weiß', 'grau', 'türkis'];
        $options = [$correct];
        
        foreach ($all_colors as $color) {
            if (count($options) >= 4) break;
            if (strtolower($color) != strtolower($correct)) {
                $options[] = $color;
            }
        }
        
        return $options;
    }
    
    /**
     * Generiere Standard-Optionen
     */
    private function generateDefaultOptions($correct) {
        // Versuche intelligente Variationen zu erstellen
        $options = [$correct];
        
        // Wenn es eine Zahl ist
        if (is_numeric($correct)) {
            return $this->generateNumberOptions($correct);
        }
        
        // Für Text-basierte Antworten
        $variations = [];
        
        // Erstelle Variationen basierend auf der Antwort
        if (strlen($correct) > 3) {
            // Ändere Anfangsbuchstaben
            $wrong1 = chr(ord($correct[0]) + 1) . substr($correct, 1);
            $variations[] = $wrong1;
            
            // Ändere Endbuchstaben
            $wrong2 = substr($correct, 0, -1) . chr(ord(substr($correct, -1)) + 1);
            $variations[] = $wrong2;
            
            // Füge oder entferne Buchstaben
            $wrong3 = $correct . 'e';
            $variations[] = $wrong3;
        }
        
        // Füge Variationen hinzu
        foreach ($variations as $var) {
            if (count($options) >= 4) break;
            if ($var != $correct) {
                $options[] = $var;
            }
        }
        
        // Fülle mit generischen Optionen auf
        while (count($options) < 4) {
            $generic = ['Falsch', 'Nicht richtig', 'Anders', 'Keine der Antworten'];
            $random = $generic[rand(0, count($generic) - 1)];
            if (!in_array($random, $options)) {
                $options[] = $random;
            }
        }
        
        return array_slice($options, 0, 4);
    }
    
    /**
     * Prüfe Antwort und gehe zur nächsten Frage
     */
    public function checkAnswer($user_answer) {
        $current_q = $this->getCurrentQuestion();
        
        if (!$current_q) return false;
        
        $is_correct = (strtolower(trim($user_answer)) == strtolower(trim($current_q['a'])));
        
        if ($is_correct) {
            $this->session_questions['correct_answers']++;
        }
        
        // Nächste Frage
        $this->session_questions['question_number']++;
        
        return $is_correct;
    }
    
    /**
     * Hole Session-Statistiken
     */
    public function getStats() {
        return [
            'current' => $this->session_questions['question_number'] + 1,
            'total' => 10,
            'correct' => $this->session_questions['correct_answers'],
            'percentage' => ($this->session_questions['question_number'] > 0) 
                ? round(($this->session_questions['correct_answers'] / $this->session_questions['question_number']) * 100) 
                : 0,
            'time_elapsed' => time() - $this->session_questions['start_time']
        ];
    }
    
    /**
     * Session beenden
     */
    public function isSessionComplete() {
        return $this->session_questions['question_number'] >= 10;
    }
    
    /**
     * Reset Session
     */
    public function resetSession() {
        $session_key = "questions_{$this->module_name}";
        unset($_SESSION[$session_key]);
        $this->initializeSession();
    }
    
    /**
     * Generiere Beispiel-Fragen als Fallback
     */
    private function generateSampleQuestions() {
        $questions = [];
        
        // Generiere 100 verschiedene Mathe-Fragen als Beispiel
        for ($i = 1; $i <= 100; $i++) {
            $a = rand(1, 20);
            $b = rand(1, 20);
            $op = ['+', '-', '*'][rand(0, 2)];
            
            switch($op) {
                case '+':
                    $result = $a + $b;
                    break;
                case '-':
                    $result = $a - $b;
                    break;
                case '*':
                    $result = $a * $b;
                    break;
            }
            
            $questions[] = [
                'q' => "$a $op $b = ?",
                'a' => strval($result),
                'type' => 'math',
                'level' => rand(1, 5)
            ];
        }
        
        return $questions;
    }
}

// ========================================
// BEISPIEL-IMPLEMENTIERUNG FÜR EIN MODUL
// ========================================

// Bestimme welches Modul geladen werden soll
$module = $_GET['module'] ?? 'mathematik';

// Initialisiere die Fragen-Engine
$engine = new QuestionEngine($module);

// Verarbeite Antwort wenn gesendet
$feedback = null;
if (isset($_POST['answer'])) {
    $is_correct = $engine->checkAnswer($_POST['answer']);
    $feedback = $is_correct ? 'correct' : 'incorrect';
}

// Hole aktuelle Frage und Statistiken
$current_question = $engine->getCurrentQuestion();
$stats = $engine->getStats();

// Prüfe ob Session beendet ist
if ($engine->isSessionComplete()) {
    // Zeige Endergebnis
    $show_results = true;
} else {
    $show_results = false;
    // Generiere Optionen für Multiple Choice
    if ($current_question) {
        $options = $engine->generateOptions(
            $current_question['a'], 
            $current_question['type'] ?? 'default'
        );
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($module) ?> - Verbesserte Fragen-Engine</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .module-title {
            font-size: 2.5em;
            color: #1A3503;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            background: #e0e0e0;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #43D240, #6FFF00);
            height: 100%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            color: #1A3503;
            font-weight: bold;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .question-container {
            background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
        }
        
        .question-text {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .option-button {
            background: white;
            border: 3px solid #e0e0e0;
            padding: 20px;
            border-radius: 10px;
            font-size: 1.2em;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option-button:hover {
            background: #43D240;
            color: white;
            border-color: #43D240;
            transform: scale(1.05);
        }
        
        .option-button.selected {
            background: #1A3503;
            color: white;
            border-color: #1A3503;
        }
        
        .submit-button {
            background: linear-gradient(135deg, #43D240, #6FFF00);
            color: white;
            border: none;
            padding: 20px 60px;
            font-size: 1.3em;
            border-radius: 10px;
            cursor: pointer;
            display: block;
            margin: 30px auto;
            transition: all 0.3s;
        }
        
        .submit-button:hover {
            transform: scale(1.1);
            box-shadow: 0 10px 30px rgba(67, 210, 64, 0.5);
        }
        
        .feedback {
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            font-size: 1.2em;
            animation: slideIn 0.5s;
        }
        
        .feedback.correct {
            background: #4caf50;
            color: white;
        }
        
        .feedback.incorrect {
            background: #f44336;
            color: white;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .results-container {
            text-align: center;
            padding: 40px;
        }
        
        .results-title {
            font-size: 2.5em;
            color: #1A3503;
            margin-bottom: 30px;
        }
        
        .results-score {
            font-size: 5em;
            font-weight: bold;
            margin: 30px 0;
        }
        
        .score-excellent { color: #4caf50; }
        .score-good { color: #43D240; }
        .score-ok { color: #ff9800; }
        .score-poor { color: #f44336; }
        
        .play-again-button {
            background: linear-gradient(135deg, #43D240, #6FFF00);
            color: white;
            border: none;
            padding: 20px 60px;
            font-size: 1.3em;
            border-radius: 10px;
            cursor: pointer;
            margin: 20px;
            text-decoration: none;
            display: inline-block;
        }
        
        .back-button {
            background: #1A3503;
            color: white;
            border: none;
            padding: 20px 60px;
            font-size: 1.3em;
            border-radius: 10px;
            cursor: pointer;
            margin: 20px;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($show_results): ?>
            <!-- ERGEBNIS-ANZEIGE -->
            <div class="results-container">
                <h1 class="results-title">🎉 Geschafft!</h1>
                
                <?php 
                $percentage = ($stats['correct'] / 10) * 100;
                $score_class = '';
                $message = '';
                
                if ($percentage >= 90) {
                    $score_class = 'score-excellent';
                    $message = 'Ausgezeichnet! Du bist ein Profi! 🌟';
                } elseif ($percentage >= 70) {
                    $score_class = 'score-good';
                    $message = 'Sehr gut! Weiter so! 👍';
                } elseif ($percentage >= 50) {
                    $score_class = 'score-ok';
                    $message = 'Gut gemacht! Übung macht den Meister! 💪';
                } else {
                    $score_class = 'score-poor';
                    $message = 'Nicht aufgeben! Versuch es nochmal! 🎯';
                }
                ?>
                
                <div class="results-score <?= $score_class ?>">
                    <?= $stats['correct'] ?>/10
                </div>
                
                <p style="font-size: 1.5em; color: #666; margin: 20px 0;">
                    <?= $message ?>
                </p>
                
                <div class="stats-row" style="max-width: 500px; margin: 40px auto;">
                    <div class="stat-box">
                        <div class="stat-number"><?= $percentage ?>%</div>
                        <div class="stat-label">Richtig</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= gmdate("i:s", $stats['time_elapsed']) ?></div>
                        <div class="stat-label">Zeit</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $stats['correct'] * 10 ?></div>
                        <div class="stat-label">Punkte</div>
                    </div>
                </div>
                
                <a href="?module=<?= $module ?>&new_session=1" class="play-again-button">
                    🔄 Nochmal spielen
                </a>
                <a href="../" class="back-button">
                    🏠 Zurück zur Übersicht
                </a>
            </div>
            
        <?php else: ?>
            <!-- FRAGEN-ANZEIGE -->
            <div class="header">
                <h1 class="module-title">📚 <?= ucfirst($module) ?></h1>
                <p style="color: #666;">Frage <?= $stats['current'] ?> von <?= $stats['total'] ?></p>
            </div>
            
            <!-- Progress Bar -->
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= ($stats['current'] - 1) * 10 ?>%;">
                    <?= ($stats['current'] - 1) * 10 ?>%
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-number"><?= $stats['correct'] ?></div>
                    <div class="stat-label">Richtige Antworten</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $stats['percentage'] ?>%</div>
                    <div class="stat-label">Erfolgsrate</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $stats['correct'] * 10 ?></div>
                    <div class="stat-label">Punkte</div>
                </div>
            </div>
            
            <!-- Feedback wenn vorhanden -->
            <?php if ($feedback): ?>
                <div class="feedback <?= $feedback ?>">
                    <?= $feedback == 'correct' ? '✅ Richtig! Sehr gut!' : '❌ Leider falsch. Weiter gehts!' ?>
                </div>
            <?php endif; ?>
            
            <!-- Frage -->
            <?php if ($current_question): ?>
                <div class="question-container">
                    <div class="question-text">
                        <?= htmlspecialchars($current_question['q']) ?>
                    </div>
                    
                    <form method="POST" id="questionForm">
                        <div class="options-grid">
                            <?php foreach ($options as $index => $option): ?>
                                <button type="button" 
                                        class="option-button" 
                                        data-value="<?= htmlspecialchars($option) ?>"
                                        onclick="selectOption(this)">
                                    <?= htmlspecialchars($option) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <input type="hidden" name="answer" id="selectedAnswer" value="">
                        <button type="submit" class="submit-button">
                            Antwort prüfen ✓
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
    
    <script>
        function selectOption(button) {
            // Entferne alle selected Klassen
            document.querySelectorAll('.option-button').forEach(btn => {
                btn.classList.remove('selected');
            });
            
            // Füge selected Klasse hinzu
            button.classList.add('selected');
            
            // Setze den Wert
            document.getElementById('selectedAnswer').value = button.dataset.value;
        }
        
        // Auto-submit nach Feedback
        <?php if ($feedback): ?>
            setTimeout(() => {
                // Nur weitergehen wenn noch Fragen übrig sind
                <?php if (!$show_results): ?>
                    window.location.href = '?module=<?= $module ?>';
                <?php endif; ?>
            }, 1500);
        <?php endif; ?>
    </script>
</body>
</html>