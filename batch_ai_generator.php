<?php
/**
 * sgiT Education Platform - Batch AI Question Generator FIXED
 * Kompatibel mit windows_ai_generator_fixed.php
 */

require_once 'windows_ai_generator.php';

class BatchAIGenerator {
    
    private $generator;
    private $logFile;
    private $statsFile;
    private $dataPath;
    
    public function __construct() {
        // Nutze die existierende Klasse
        $this->generator = new AIQuestionGeneratorWindows();
        
        $this->dataPath = __DIR__ . '/AI/data';
        $this->logFile = __DIR__ . '/AI/logs/batch_generation.log';
        $this->statsFile = __DIR__ . '/AI/data/generation_stats.json';
        
        // Erstelle Verzeichnisse
        $dirs = [
            dirname($this->logFile),
            dirname($this->statsFile),
            $this->dataPath
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }
    
    /**
     * Generiert Fragen für alle Module
     */
    public function generateForAllModules($questionsPerModule = 100) {
        $modules = ['mathematik', 'lesen', 'englisch', 'wissenschaft', 
                   'erdkunde', 'chemie', 'physik', 'kunst', 'musik', 
                   'computer', 'bitcoin', 'geschichte', 'biologie', 'steuern'];
        
        $ageGroups = [
            ['age' => 6, 'difficulty_range' => [1, 3], 'label' => '5-7 Jahre'],
            ['age' => 9, 'difficulty_range' => [3, 5], 'label' => '8-10 Jahre'],
            ['age' => 12, 'difficulty_range' => [5, 7], 'label' => '11-13 Jahre'],
            ['age' => 14, 'difficulty_range' => [7, 10], 'label' => '14-15 Jahre']
        ];
        
        $totalGenerated = 0;
        $stats = [];
        
        $this->log("=== BATCH GENERATION GESTARTET ===");
        $this->log("Ziel: $questionsPerModule Fragen pro Modul");
        
        // Prüfe Ollama Status
        if (!$this->generator->checkOllama()) {
            $this->log("⚠️ WARNUNG: Ollama läuft nicht! Verwende Fallback-Fragen.");
        }
        
        foreach ($modules as $module) {
            $this->log("\n📚 Starte Modul: $module");
            $moduleStats = [
                'module' => $module,
                'total' => 0,
                'by_age' => [],
                'errors' => 0,
                'start_time' => time()
            ];
            
            $questionsPerAge = ceil($questionsPerModule / count($ageGroups));
            
            foreach ($ageGroups as $group) {
                $this->log("  → Generiere für {$group['label']}...");
                $ageStats = ['generated' => 0, 'failed' => 0];
                
                for ($i = 0; $i < $questionsPerAge; $i++) {
                    $difficulty = rand($group['difficulty_range'][0], $group['difficulty_range'][1]);
                    
                    try {
                        // Nutze die Generator-Methode
                        $question = $this->generator->generateQuestion($module, $difficulty, $group['age']);
                        
                        if ($question && !empty($question['q'])) {
                            // Speichere mit erweiterten Metadaten
                            $this->saveEnhancedQuestion($module, $question, $difficulty, $group['age']);
                            $ageStats['generated']++;
                            $totalGenerated++;
                            
                            if ($ageStats['generated'] % 10 == 0) {
                                $this->log("    ✓ {$ageStats['generated']}/$questionsPerAge generiert");
                            }
                        }
                        
                        usleep(500000); // 0.5 Sekunden Pause
                        
                    } catch (Exception $e) {
                        $ageStats['failed']++;
                        $moduleStats['errors']++;
                        $this->log("    ✗ Fehler: " . $e->getMessage());
                        sleep(2);
                    }
                }
                
                $moduleStats['by_age'][$group['label']] = $ageStats;
            }
            
            $moduleStats['total'] = array_sum(array_column($moduleStats['by_age'], 'generated'));
            $moduleStats['duration'] = time() - $moduleStats['start_time'];
            $stats[$module] = $moduleStats;
            
            $this->log("  ✅ Modul abgeschlossen: {$moduleStats['total']} Fragen in {$moduleStats['duration']} Sekunden");
        }
        
        $this->saveStats($stats);
        $this->log("\n=== GENERATION ABGESCHLOSSEN ===");
        $this->log("Gesamt generiert: $totalGenerated Fragen");
        
        return $stats;
    }
    
    /**
     * Speichert Frage mit erweiterten Metadaten
     */
    private function saveEnhancedQuestion($module, $question, $difficulty, $age) {
        $filename = $this->dataPath . '/ai_' . $module . '_questions.json';
        
        $questions = [];
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $questions = json_decode($content, true) ?? [];
        }
        
        $enhancedQuestion = [
            'id' => uniqid('q_'),
            'question' => $question['q'],
            'answer' => $question['a'],
            'options' => $question['options'],
            'difficulty' => $difficulty,
            'age_group' => $this->getAgeGroup($age),
            'age' => $age,
            'module' => $module,
            'created' => date('Y-m-d H:i:s'),
            'source' => 'batch_generator'
        ];
        
        $questions[] = $enhancedQuestion;
        
        file_put_contents($filename, json_encode($questions, JSON_PRETTY_PRINT));
    }
    
    private function getAgeGroup($age) {
        if ($age <= 7) return '5-7';
        if ($age <= 10) return '8-10';
        if ($age <= 13) return '11-13';
        return '14-15';
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo $message . "\n";
        
        if (php_sapi_name() !== 'cli') {
            echo "<script>console.log('" . addslashes($message) . "');</script>";
            flush();
        }
    }
    
    private function saveStats($stats) {
        $stats['timestamp'] = date('Y-m-d H:i:s');
        $stats['total_questions'] = is_array($stats) ? count($stats) : 0;
        
        $allStats = [];
        if (file_exists($this->statsFile)) {
            $content = file_get_contents($this->statsFile);
            $allStats = json_decode($content, true) ?? [];
        }
        
        $allStats[] = $stats;
        
        if (count($allStats) > 10) {
            $allStats = array_slice($allStats, -10);
        }
        
        file_put_contents($this->statsFile, json_encode($allStats, JSON_PRETTY_PRINT));
    }
    
    public function getStats() {
        if (file_exists($this->statsFile)) {
            $content = file_get_contents($this->statsFile);
            return json_decode($content, true);
        }
        return [];
    }
}

// ========================================
// CLI & Web Interface
// ========================================

if (php_sapi_name() === 'cli') {
    echo "sgiT Education - Batch AI Generator\n";
    echo "=====================================\n\n";
    
    $generator = new BatchAIGenerator();
    $questionsPerModule = isset($argv[1]) ? intval($argv[1]) : 20;
    
    echo "Generiere $questionsPerModule Fragen pro Modul...\n\n";
    $stats = $generator->generateForAllModules($questionsPerModule);
    
    echo "\n\nFertig! Statistiken:\n";
    print_r($stats);
} else {
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>sgiT Education - Batch AI Generator</title>
    <style>
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 20px;
            margin: 0;
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
        }
        .control-panel {
            background: #f5f5f5;
            padding: 30px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }
        select {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 10px;
        }
        button {
            background: #43D240;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
        }
        button:hover {
            background: #3ab837;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .progress {
            display: none;
            margin: 20px 0;
        }
        .progress-bar {
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            height: 30px;
            margin: 10px 0;
        }
        .progress-fill {
            background: linear-gradient(90deg, #43D240, #3ab837);
            height: 100%;
            width: 0%;
            transition: width 0.5s;
        }
        #output {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            white-space: pre-wrap;
            font-family: monospace;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ffeeba;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 sgiT Education - Batch AI Generator</h1>
        
        <?php
        $generator = new BatchAIGenerator();
        $ollamaStatus = $generator->generator->checkOllama();
        
        if (!$ollamaStatus) {
            echo '<div class="warning">';
            echo '⚠️ <strong>Ollama läuft nicht!</strong><br>';
            echo 'Die Generierung wird Fallback-Fragen verwenden.<br>';
            echo 'Für KI-Fragen starte Ollama: <code>ollama serve</code>';
            echo '</div>';
        }
        ?>
        
        <div class="control-panel">
            <h3>Batch-Generierung konfigurieren:</h3>
            
            <label>Anzahl Fragen pro Modul:</label>
            <select id="count">
                <option value="10">10 (Test, ~2 Min)</option>
                <option value="20" selected>20 (Klein, ~5 Min)</option>
                <option value="50">50 (Mittel, ~15 Min)</option>
                <option value="100">100 (Groß, ~30 Min)</option>
            </select>
            
            <br>
            
            <button id="startBtn" onclick="startGeneration()">
                🎲 Generierung starten
            </button>
            
            <div class="progress" id="progress">
                <h4>Generiere...</h4>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressBar"></div>
                </div>
            </div>
        </div>
        
        <div id="output"></div>
        
        <?php
        // Handle AJAX request
        if (isset($_POST['generate'])) {
            $count = intval($_POST['count'] ?? 20);
            $generator = new BatchAIGenerator();
            
            ob_start();
            $stats = $generator->generateForAllModules($count);
            $output = ob_get_clean();
            
            echo json_encode([
                'success' => true,
                'output' => $output,
                'stats' => $stats
            ]);
            exit;
        }
        ?>
    </div>
    
    <script>
        function startGeneration() {
            const count = document.getElementById('count').value;
            const btn = document.getElementById('startBtn');
            const progress = document.getElementById('progress');
            const output = document.getElementById('output');
            
            btn.disabled = true;
            progress.style.display = 'block';
            output.style.display = 'block';
            output.innerHTML = 'Starte Generierung...\n';
            
            // Simulate progress
            let progressValue = 0;
            const progressInterval = setInterval(() => {
                progressValue += Math.random() * 5;
                if (progressValue > 95) progressValue = 95;
                document.getElementById('progressBar').style.width = progressValue + '%';
            }, 1000);
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'generate=1&count=' + count
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                document.getElementById('progressBar').style.width = '100%';
                
                if (data.success) {
                    output.innerHTML = data.output || 'Generierung abgeschlossen!';
                    
                    if (data.stats) {
                        output.innerHTML += '\n\n=== STATISTIKEN ===\n';
                        output.innerHTML += JSON.stringify(data.stats, null, 2);
                    }
                }
                
                btn.disabled = false;
            })
            .catch(error => {
                clearInterval(progressInterval);
                output.innerHTML = '❌ Fehler: ' + error.message;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
    <?php
}
?>