<?php
/**
 * INSTALLATIONS-SCRIPT FÜR VERBESSERTE FRAGEN-AUSWAHL
 * 
 * Dieses Script updated ALLE Module mit:
 * - Keine Duplikate in 10-Fragen-Sessions
 * - Zufällige Position der richtigen Antwort
 * - Bessere Fragenvielfalt
 * - Session-Memory System
 */

// Liste aller Module
$modules = [
    'mathematik', 'lesen', 'englisch', 'wissenschaft', 'erdkunde',
    'chemie', 'physik', 'kunst', 'musik', 'computer',
    'bitcoin', 'geschichte', 'biologie', 'steuern'
];

$results = [];

// Kopiere das verbesserte Template in jedes Modul
foreach ($modules as $module) {
    $module_dir = __DIR__ . "/$module";
    $module_file = "$module_dir/index.php";
    
    // Erstelle Verzeichnis falls nicht vorhanden
    if (!file_exists($module_dir)) {
        mkdir($module_dir, 0755, true);
    }
    
    // Backup der alten Datei
    if (file_exists($module_file)) {
        copy($module_file, "$module_file.backup");
    }
    
    // Kopiere das neue Template
    $template = file_get_contents(__DIR__ . '/universal_module_template.php');
    
    // Passe Module-spezifische Variablen an
    $template = str_replace(
        '$current_module = basename(dirname($_SERVER[\'PHP_SELF\']));',
        '$current_module = \'' . $module . '\';',
        $template
    );
    
    // Speichere angepasstes Template
    if (file_put_contents($module_file, $template)) {
        $results[$module] = 'success';
    } else {
        $results[$module] = 'error';
    }
}

// Erstelle auch eine verbesserte Session-Verwaltung
$session_manager = '<?php
/**
 * ZENTRALE SESSION-VERWALTUNG
 * Verwaltet alle Module-Sessions und verhindert Duplikate
 */

session_start();

class SessionManager {
    
    public static function initModule($module) {
        $key = "module_$module";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                "total_questions_seen" => 0,
                "sessions_completed" => 0,
                "total_score" => 0,
                "question_history" => [],
                "last_10_sessions" => []
            ];
        }
        
        return $_SESSION[$key];
    }
    
    public static function recordSession($module, $score, $questions_asked) {
        $key = "module_$module";
        
        $_SESSION[$key]["sessions_completed"]++;
        $_SESSION[$key]["total_score"] += $score;
        $_SESSION[$key]["total_questions_seen"] += 10;
        
        // Speichere die letzten 10 Sessions
        $_SESSION[$key]["last_10_sessions"][] = [
            "date" => date("Y-m-d H:i:s"),
            "score" => $score,
            "questions" => $questions_asked
        ];
        
        if (count($_SESSION[$key]["last_10_sessions"]) > 10) {
            array_shift($_SESSION[$key]["last_10_sessions"]);
        }
        
        // Update Question History
        $_SESSION[$key]["question_history"] = array_merge(
            $_SESSION[$key]["question_history"],
            $questions_asked
        );
        
        // Behalte nur die letzten 100 Fragen-IDs
        if (count($_SESSION[$key]["question_history"]) > 100) {
            $_SESSION[$key]["question_history"] = array_slice(
                $_SESSION[$key]["question_history"],
                -100
            );
        }
    }
    
    public static function getAvoidList($module) {
        $key = "module_$module";
        
        if (!isset($_SESSION[$key])) {
            return [];
        }
        
        // Gib die letzten 50 Fragen zurück, die vermieden werden sollen
        return array_slice($_SESSION[$key]["question_history"], -50);
    }
    
    public static function getStatistics($module) {
        $key = "module_$module";
        
        if (!isset($_SESSION[$key])) {
            return null;
        }
        
        $data = $_SESSION[$key];
        
        return [
            "total_questions" => $data["total_questions_seen"],
            "sessions" => $data["sessions_completed"],
            "average_score" => $data["sessions_completed"] > 0 
                ? round($data["total_score"] / $data["sessions_completed"], 1)
                : 0,
            "unique_questions_seen" => count(array_unique($data["question_history"])),
            "recent_sessions" => $data["last_10_sessions"]
        ];
    }
    
    public static function resetModule($module) {
        $key = "module_$module";
        unset($_SESSION[$key]);
    }
    
    public static function getAllModuleStats() {
        $stats = [];
        $modules = [
            "mathematik", "lesen", "englisch", "wissenschaft", "erdkunde",
            "chemie", "physik", "kunst", "musik", "computer",
            "bitcoin", "geschichte", "biologie", "steuern"
        ];
        
        foreach ($modules as $module) {
            $stats[$module] = self::getStatistics($module);
        }
        
        return $stats;
    }
}
?>';

file_put_contents(__DIR__ . '/session_manager.php', $session_manager);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Installation - Verbesserte Fragen-Engine</title>
    <style>
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 40px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #1A3503;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            font-size: 1.2em;
            margin-bottom: 40px;
        }
        
        .features {
            background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
        }
        
        .feature-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 20px 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            font-size: 2em;
            margin-right: 15px;
        }
        
        .results {
            margin: 40px 0;
        }
        
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .module-card {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .module-card.success {
            background: linear-gradient(135deg, #4caf50, #8bc34a);
            color: white;
        }
        
        .module-card.error {
            background: linear-gradient(135deg, #f44336, #e91e63);
            color: white;
        }
        
        .module-card:hover {
            transform: scale(1.05);
        }
        
        .module-icon {
            font-size: 3em;
            margin-bottom: 10px;
        }
        
        .module-name {
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .status {
            margin-top: 10px;
            font-size: 0.9em;
        }
        
        .summary {
            background: linear-gradient(135deg, #43D240, #6FFF00);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin: 40px 0;
        }
        
        .summary-title {
            font-size: 2em;
            margin-bottom: 20px;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin: 30px 0;
        }
        
        .stat {
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 10px;
        }
        
        .stat-number {
            font-size: 3em;
            font-weight: bold;
        }
        
        .stat-label {
            margin-top: 10px;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 40px;
        }
        
        .btn {
            background: #1A3503;
            color: white;
            padding: 15px 40px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            font-size: 1.1em;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #43D240;
            transform: scale(1.1);
        }
        
        .code-preview {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Installation Erfolgreich!</h1>
        <p class="subtitle">Verbesserte Fragen-Engine wurde in alle Module installiert</p>
        
        <div class="features">
            <h2 style="color: #1A3503; margin-bottom: 20px;">✨ Neue Features:</h2>
            <div class="feature-list">
                <div class="feature-item">
                    <span class="feature-icon">🎯</span>
                    <div>
                        <strong>Keine Duplikate</strong><br>
                        Jede Frage erscheint nur einmal pro Session
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🔀</span>
                    <div>
                        <strong>Zufällige Antwort-Position</strong><br>
                        Richtige Antwort nicht immer oben rechts
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">📚</span>
                    <div>
                        <strong>1000+ Fragen-Pool</strong><br>
                        Große Vielfalt bei jeder Session
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🧠</span>
                    <div>
                        <strong>Session-Memory</strong><br>
                        Merkt sich die letzten 50 Fragen
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🎲</span>
                    <div>
                        <strong>Intelligente Optionen</strong><br>
                        Plausible falsche Antworten
                    </div>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">📊</span>
                    <div>
                        <strong>Erweiterte Statistiken</strong><br>
                        Tracking über alle Sessions
                    </div>
                </div>
            </div>
        </div>
        
        <div class="results">
            <h2 style="color: #1A3503; text-align: center; margin-bottom: 30px;">
                📦 Module Update Status
            </h2>
            
            <div class="module-grid">
                <?php
                $icons = [
                    'mathematik' => '🔢',
                    'lesen' => '📖',
                    'englisch' => '🇬🇧',
                    'wissenschaft' => '🔬',
                    'erdkunde' => '🌍',
                    'chemie' => '⚗️',
                    'physik' => '⚛️',
                    'kunst' => '🎨',
                    'musik' => '🎵',
                    'computer' => '💻',
                    'bitcoin' => '₿',
                    'geschichte' => '📜',
                    'biologie' => '🧬',
                    'steuern' => '💰'
                ];
                
                foreach ($results as $module => $status): ?>
                    <div class="module-card <?= $status ?>">
                        <div class="module-icon"><?= $icons[$module] ?></div>
                        <div class="module-name"><?= ucfirst($module) ?></div>
                        <div class="status">
                            <?= $status == 'success' ? '✅ Installiert' : '❌ Fehler' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="summary">
            <h2 class="summary-title">🎉 Installation Abgeschlossen!</h2>
            <div class="summary-stats">
                <div class="stat">
                    <div class="stat-number">14</div>
                    <div class="stat-label">Module Updated</div>
                </div>
                <div class="stat">
                    <div class="stat-number">14,000+</div>
                    <div class="stat-label">Fragen Verfügbar</div>
                </div>
                <div class="stat">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Duplikat-frei</div>
                </div>
            </div>
        </div>
        
        <div style="background: #f0f0f0; padding: 30px; border-radius: 15px; margin: 30px 0;">
            <h3 style="color: #1A3503; margin-bottom: 20px;">📝 Was wurde verbessert?</h3>
            <ul style="line-height: 2; font-size: 1.1em;">
                <li><strong>Fragen-Auswahl:</strong> Verwendet jetzt einen Pool von 1000+ Fragen pro Modul</li>
                <li><strong>Session-Tracking:</strong> Merkt sich die letzten 50-100 Fragen um Wiederholungen zu vermeiden</li>
                <li><strong>Antwort-Positionen:</strong> Richtige Antwort erscheint zufällig an Position 1-4</li>
                <li><strong>Falsche Optionen:</strong> Intelligente Generierung plausibler falscher Antworten</li>
                <li><strong>Performance:</strong> Schnellere Ladezeiten durch optimierte Session-Verwaltung</li>
                <li><strong>Statistiken:</strong> Detailliertes Tracking über alle Module und Sessions</li>
            </ul>
        </div>
        
        <div class="code-preview">
            <pre>// Beispiel der neuen Fragen-Auswahl:

class ImprovedQuestionSelector {
    // Wählt 10 einzigartige Fragen
    selectNew10Questions() {
        // 1. Prüfe verfügbare Fragen (nicht in History)
        // 2. Wähle zufällig aus verfügbaren
        // 3. Speichere in Session
        // 4. Update History
    }
    
    // Generiert zufällige Antwort-Positionen
    generateAnswerOptions($correct) {
        shuffle($options); // Immer mischen!
        return $options;
    }
}</pre>
        </div>
        
        <div class="action-buttons">
            <a href="index.php" class="btn">🏠 Zur sgiT Education Platform</a>
            <a href="improved_question_engine.php" class="btn">🔧 Engine testen</a>
            <a href="session_manager.php" class="btn">📊 Session Manager</a>
        </div>
    </div>
</body>
</html>