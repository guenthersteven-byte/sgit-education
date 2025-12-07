<?php
/**
 * sgiT Education - AI Generator Bot v1.6
 * 
 * LANGSAMER DAUERLAUF-BOT für Massen-Generierung
 * - Alle 2 Minuten eine Frage pro Modul
 * - Läuft bis manuell gestoppt
 * - Überlastet Ollama nicht
 * 
 * v1.6: + CSV Generator Link, BUG-035 FIX: steuern→finanzen
 * v1.5: FIX - Live-Output für Docker/nginx/PHP-FPM (BUG-032)
 * v1.4: + BUG-019 FIX: Verkehr-Modul hinzugefügt (fehlte in beiden Arrays)
 * v1.3: + Einzelne Fragen löschen, Pagination, mehr Fragen anzeigen
 * v1.2: + Modul-DB-Manager (Einträge löschen)
 * v1.1: + Fehlerbehandlung, Stop-Signal
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.6
 * @date 06.12.2025
 */

// WICHTIG: Korrekter Pfad zum bot_logger (eine Ebene höher)
require_once dirname(__DIR__) . '/bot_logger.php';
require_once dirname(__DIR__) . '/bot_output_helper.php';
require_once dirname(dirname(__DIR__)) . '/windows_ai_generator.php';

class AIGeneratorBot {
    
    private $logger;
    private $generator;
    private $config;
    private $isRunning = false;
    private $stopFile;
    
    // Alle 18 Quiz-Module
    private $modules = [
        'mathematik', 'physik', 'chemie', 'biologie', 'erdkunde',
        'geschichte', 'kunst', 'musik', 'computer', 'programmieren',
        'bitcoin', 'finanzen', 'englisch', 'lesen', 'wissenschaft', 'verkehr',
        'unnuetzes_wissen', 'sport'
    ];
    
    // Standard-Konfiguration für LANGSAMEN Dauerlauf
    private $defaultConfig = [
        'delayBetweenModules' => 120,    // 2 Minuten zwischen Modulen (in Sekunden)
        'delayBetweenQuestions' => 5,    // 5 Sekunden nach jeder Frage
        'ageRange' => [5, 15],           // Altersbereich
        'forceAI' => true,               // Immer AI nutzen
        'maxRetries' => 1,               // Nur 1 Retry (Ollama nicht überlasten)
        'continuous' => true,            // Dauerlauf-Modus
        'questionsPerRound' => 1         // 1 Frage pro Modul pro Runde
    ];
    
    /**
     * Konstruktor
     */
    public function __construct($config = []) {
        $this->config = array_merge($this->defaultConfig, $config);
        $this->logger = new BotLogger(BotLogger::CAT_AI);
        $this->generator = new AIQuestionGeneratorComplete();
        $this->stopFile = dirname(__DIR__) . '/logs/STOP_AI_BOT';
    }
    
    /**
     * Hauptmethode - Startet den Dauerlauf-Bot
     */
    public function run() {
        // Stop-File löschen falls vorhanden
        if (file_exists($this->stopFile)) {
            unlink($this->stopFile);
        }
        
        $this->isRunning = true;
        
        // Start loggen
        $this->logger->startRun('AI Generator Bot (Dauerlauf)', $this->config);
        
        $startTime = microtime(true);
        $totalGenerated = 0;
        $totalFailed = 0;
        $round = 0;
        
        // Ollama-Status prüfen
        $ollamaStatus = $this->generator->getOllamaStatus();
        if (!$ollamaStatus['running']) {
            $this->logger->critical('Ollama nicht erreichbar!', [
                'suggestion' => 'Starte Ollama mit: ollama serve'
            ]);
            $this->logger->endRun('Abgebrochen - Ollama nicht verfügbar');
            return ['error' => 'Ollama nicht verfügbar'];
        }
        
        $this->logger->success("Ollama verbunden: {$ollamaStatus['model']}", [
            'details' => $ollamaStatus
        ]);
        
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("🚀 DAUERLAUF-MODUS GESTARTET");
        $this->logger->info("   Intervall: {$this->config['delayBetweenModules']}s zwischen Modulen");
        $this->logger->info("   Zum Stoppen: Erstelle Datei 'STOP_AI_BOT' in bots/logs/");
        $this->logger->info("   Oder: Lade die Seite mit ?stop=1");
        $this->logger->info("═══════════════════════════════════════════════════════");
        
        // Dauerlauf
        while ($this->isRunning && !$this->shouldStop()) {
            $round++;
            $this->logger->info("");
            $this->logger->info("╔═══════════════════════════════════════════════════════╗");
            $this->logger->info("║  RUNDE $round - " . date('H:i:s'));
            $this->logger->info("╚═══════════════════════════════════════════════════════╝");
            
            // Durch alle Module (langsam!)
            foreach ($this->modules as $index => $module) {
                
                // Check ob gestoppt werden soll
                if ($this->shouldStop()) {
                    $this->logger->warning("⏹️ STOP-Signal empfangen!");
                    break 2; // Beide Schleifen verlassen
                }
                
                $this->logger->info("");
                $this->logger->info("📚 [{$module}] Generiere Frage... (" . ($index + 1) . "/" . count($this->modules) . ")");
                
                // Zufälliges Alter
                $age = rand($this->config['ageRange'][0], $this->config['ageRange'][1]);
                $difficulty = $this->getDifficultyForAge($age);
                
                // Frage generieren
                $questionStart = microtime(true);
                $question = $this->generateWithRetry($module, $difficulty, $age);
                $questionTime = round((microtime(true) - $questionStart) * 1000);
                
                if ($question && $this->validateGeneratedQuestion($question, $module)) {
                    $totalGenerated++;
                    $this->logger->success(
                        "✅ Frage generiert (Alter $age) - {$questionTime}ms",
                        [
                            'module' => $module,
                            'duration_ms' => $questionTime,
                            'details' => [
                                'question' => substr($question['q'], 0, 60) . '...',
                                'answer' => $question['a']
                            ]
                        ]
                    );
                } else {
                    $totalFailed++;
                    $this->logger->error(
                        "❌ Generierung fehlgeschlagen (Alter $age)",
                        [
                            'module' => $module,
                            'duration_ms' => $questionTime
                        ]
                    );
                }
                
                // Metriken
                $this->logger->metric('question_time', $questionTime, 'ms', $module);
                
                // WICHTIG: Pause zwischen Modulen (2 Minuten default)
                if ($index < count($this->modules) - 1 && !$this->shouldStop()) {
                    $waitTime = $this->config['delayBetweenModules'];
                    $this->logger->info("⏳ Warte {$waitTime}s bis zum nächsten Modul...");
                    
                    // In Intervallen warten um Stop-Signal zu checken
                    for ($w = 0; $w < $waitTime; $w += 5) {
                        if ($this->shouldStop()) break;
                        sleep(min(5, $waitTime - $w));
                    }
                }
            }
            
            // Runden-Zusammenfassung
            $runtime = round((microtime(true) - $startTime) / 60, 1);
            $this->logger->info("");
            $this->logger->info("📊 Nach Runde $round: $totalGenerated generiert, $totalFailed fehlgeschlagen");
            $this->logger->info("⏱️ Laufzeit: {$runtime} Minuten");
            
            // Wenn nicht continuous, nach einer Runde aufhören
            if (!$this->config['continuous']) {
                break;
            }
        }
        
        // Ende
        $totalTime = round((microtime(true) - $startTime) / 60, 1);
        $summary = "Dauerlauf beendet nach $round Runden. $totalGenerated generiert, $totalFailed fehlgeschlagen. Laufzeit: {$totalTime} Minuten";
        
        $this->logger->info("");
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->success("🏁 BOT BEENDET");
        $this->logger->info("   Runden: $round");
        $this->logger->info("   Generiert: $totalGenerated");
        $this->logger->info("   Fehlgeschlagen: $totalFailed");
        $this->logger->info("   Laufzeit: {$totalTime} Minuten");
        $this->logger->info("═══════════════════════════════════════════════════════");
        
        $this->logger->endRun($summary);
        
        // Stop-File löschen
        if (file_exists($this->stopFile)) {
            unlink($this->stopFile);
        }
        
        return [
            'rounds' => $round,
            'generated' => $totalGenerated,
            'failed' => $totalFailed,
            'runtime_minutes' => $totalTime
        ];
    }
    
    /**
     * Prüft ob der Bot gestoppt werden soll
     */
    private function shouldStop() {
        // Stop-File prüfen
        if (file_exists($this->stopFile)) {
            return true;
        }
        
        // Connection aborted prüfen (Browser geschlossen)
        if (connection_aborted()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Stoppt den Bot (erstellt Stop-File)
     */
    public static function stop() {
        $stopFile = dirname(__DIR__) . '/logs/STOP_AI_BOT';
        file_put_contents($stopFile, date('Y-m-d H:i:s'));
        return true;
    }
    
    /**
     * Prüft ob Bot läuft
     */
    public static function isRunning() {
        $stopFile = dirname(__DIR__) . '/logs/STOP_AI_BOT';
        // Wenn kein Stop-File existiert und kürzlich Logs geschrieben wurden
        $logFile = dirname(__DIR__) . '/logs/ai_generator.log';
        if (file_exists($logFile)) {
            $lastMod = filemtime($logFile);
            // Wenn Log in den letzten 5 Minuten aktualisiert wurde
            if (time() - $lastMod < 300) {
                return !file_exists($stopFile);
            }
        }
        return false;
    }
    
    /**
     * Generiert Frage mit Wiederholungen bei Fehler
     */
    private function generateWithRetry($module, $difficulty, $age) {
        $maxRetries = $this->config['maxRetries'];
        
        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                $question = $this->generator->generateQuestion(
                    $module, 
                    $difficulty, 
                    $age, 
                    $this->config['forceAI']
                );
                
                if ($question && isset($question['q']) && isset($question['a'])) {
                    return $question;
                }
                
            } catch (Exception $e) {
                $this->logger->warning(
                    "Attempt " . ($attempt + 1) . " failed: " . $e->getMessage(),
                    ['module' => $module]
                );
            }
            
            if ($attempt < $maxRetries) {
                sleep(2); // Kurze Pause vor Retry
            }
        }
        
        return null;
    }
    
    /**
     * Validiert die generierte Frage
     */
    private function validateGeneratedQuestion($question, $module) {
        if (!isset($question['q']) || !isset($question['a']) || !isset($question['options'])) {
            return false;
        }
        
        if (strlen($question['q']) < 10) {
            return false;
        }
        
        if (count($question['options']) < 4) {
            return false;
        }
        
        if (!in_array($question['a'], $question['options'])) {
            return false;
        }
        
        // Keine generischen Fallback-Fragen für andere Module
        if ($module !== 'erdkunde') {
            if (preg_match('/hauptstadt.*deutschland/i', $question['q'])) {
                $this->logger->warning("Generische Fallback-Frage erkannt", ['module' => $module]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Bestimmt Schwierigkeit basierend auf Alter
     */
    private function getDifficultyForAge($age) {
        if ($age <= 7) return 3;
        if ($age <= 10) return 5;
        if ($age <= 13) return 7;
        return 9;
    }
    
    /**
     * Schnelltest - Eine Runde durch alle Module (nicht continuous)
     */
    public function quickTest() {
        $this->config['continuous'] = false;
        $this->config['delayBetweenModules'] = 10; // Nur 10s für Quick Test
        return $this->run();
    }
    
    /**
     * Single Module Test
     */
    public function testModule($module, $count = 3) {
        $this->config['continuous'] = false;
        $this->config['delayBetweenModules'] = 5;
        $this->modules = [$module];
        
        // Mehrere Fragen für ein Modul
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $results[] = $this->run();
        }
        return $results;
    }
    
    // ================================================================
    // v1.2+ NEU: Modul-DB-Manager Funktionen
    // ================================================================
    
    /**
     * Holt Statistiken aller Module aus der DB
     */
    public static function getModuleStats() {
        $dbPath = dirname(dirname(__DIR__)) . '/AI/data/questions.db';
        if (!file_exists($dbPath)) {
            return [];
        }
        
        try {
            $db = new SQLite3($dbPath);
            
            // Alle 18 Quiz-Module mit Fragen zählen
            $allModules = [
                'mathematik', 'physik', 'chemie', 'biologie', 'erdkunde',
                'geschichte', 'kunst', 'musik', 'computer', 'programmieren',
                'bitcoin', 'finanzen', 'englisch', 'lesen', 'wissenschaft', 'verkehr',
                'unnuetzes_wissen', 'sport'
            ];
            
            $stats = [];
            foreach ($allModules as $module) {
                // Case-insensitive Suche
                $stmt = $db->prepare('
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN ai_generated = 1 OR model_used IS NOT NULL THEN 1 ELSE 0 END) as ai_count,
                        MAX(created_at) as last_created
                    FROM questions 
                    WHERE LOWER(module) = LOWER(:module)
                ');
                $stmt->bindValue(':module', $module, SQLITE3_TEXT);
                $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
                
                $stats[$module] = [
                    'total' => intval($result['total'] ?? 0),
                    'ai_count' => intval($result['ai_count'] ?? 0),
                    'last_created' => $result['last_created'] ?? null
                ];
            }
            
            $db->close();
            return $stats;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Löscht alle Fragen eines Moduls
     */
    public static function deleteModuleQuestions($module, $onlyAI = false) {
        $dbPath = dirname(dirname(__DIR__)) . '/AI/data/questions.db';
        if (!file_exists($dbPath)) {
            return ['success' => false, 'error' => 'Datenbank nicht gefunden'];
        }
        
        try {
            $db = new SQLite3($dbPath);
            
            // Erst zählen was gelöscht wird
            if ($onlyAI) {
                $stmt = $db->prepare('
                    SELECT COUNT(*) as count FROM questions 
                    WHERE LOWER(module) = LOWER(:module) 
                    AND (ai_generated = 1 OR model_used IS NOT NULL)
                ');
            } else {
                $stmt = $db->prepare('
                    SELECT COUNT(*) as count FROM questions 
                    WHERE LOWER(module) = LOWER(:module)
                ');
            }
            $stmt->bindValue(':module', $module, SQLITE3_TEXT);
            $countResult = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            $deleteCount = intval($countResult['count'] ?? 0);
            
            if ($deleteCount == 0) {
                $db->close();
                return ['success' => true, 'deleted' => 0, 'message' => 'Keine Fragen zum Löschen gefunden'];
            }
            
            // Jetzt löschen
            if ($onlyAI) {
                $stmt = $db->prepare('
                    DELETE FROM questions 
                    WHERE LOWER(module) = LOWER(:module) 
                    AND (ai_generated = 1 OR model_used IS NOT NULL)
                ');
            } else {
                $stmt = $db->prepare('
                    DELETE FROM questions 
                    WHERE LOWER(module) = LOWER(:module)
                ');
            }
            $stmt->bindValue(':module', $module, SQLITE3_TEXT);
            $stmt->execute();
            
            $db->close();
            
            return [
                'success' => true, 
                'deleted' => $deleteCount, 
                'message' => "$deleteCount Fragen aus '$module' gelöscht"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * v1.3 NEU: Löscht eine einzelne Frage anhand der ID
     */
    public static function deleteSingleQuestion($id) {
        $dbPath = dirname(dirname(__DIR__)) . '/AI/data/questions.db';
        if (!file_exists($dbPath)) {
            return ['success' => false, 'error' => 'Datenbank nicht gefunden'];
        }
        
        try {
            $db = new SQLite3($dbPath);
            
            // Erst prüfen ob die Frage existiert
            $stmt = $db->prepare('SELECT id, question, module FROM questions WHERE id = :id');
            $stmt->bindValue(':id', intval($id), SQLITE3_INTEGER);
            $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            
            if (!$result) {
                $db->close();
                return ['success' => false, 'error' => 'Frage nicht gefunden (ID: ' . $id . ')'];
            }
            
            // Löschen
            $stmt = $db->prepare('DELETE FROM questions WHERE id = :id');
            $stmt->bindValue(':id', intval($id), SQLITE3_INTEGER);
            $stmt->execute();
            
            $db->close();
            
            return [
                'success' => true, 
                'deleted_id' => $id,
                'module' => $result['module'],
                'message' => "Frage #$id gelöscht"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Zeigt Fragen eines Moduls (mit Pagination)
     * v1.3: Mehr Optionen, Offset für Pagination
     */
    public static function getModulePreview($module, $limit = 20, $offset = 0) {
        $dbPath = dirname(dirname(__DIR__)) . '/AI/data/questions.db';
        if (!file_exists($dbPath)) {
            return [];
        }
        
        try {
            $db = new SQLite3($dbPath);
            
            $stmt = $db->prepare('
                SELECT id, question, answer, options, difficulty, ai_generated, model_used, created_at
                FROM questions 
                WHERE LOWER(module) = LOWER(:module)
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ');
            $stmt->bindValue(':module', $module, SQLITE3_TEXT);
            $stmt->bindValue(':limit', intval($limit), SQLITE3_INTEGER);
            $stmt->bindValue(':offset', intval($offset), SQLITE3_INTEGER);
            
            $results = [];
            $result = $stmt->execute();
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $results[] = $row;
            }
            
            $db->close();
            return $results;
            
        } catch (Exception $e) {
            return [];
        }
    }
}

// ============================================================
// STANDALONE AUSFÜHRUNG MIT UI
// ============================================================
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    
    // Unbegrenzte Laufzeit für Dauerlauf
    set_time_limit(0);
    ignore_user_abort(false);
    
    // AJAX Handlers
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        
        // Modul-Stats laden
        if ($_GET['ajax'] === 'get_stats') {
            echo json_encode(AIGeneratorBot::getModuleStats());
            exit;
        }
        
        // Modul-Preview laden (mit Pagination)
        if ($_GET['ajax'] === 'preview' && isset($_GET['module'])) {
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            echo json_encode(AIGeneratorBot::getModulePreview($_GET['module'], $limit, $offset));
            exit;
        }
        
        // Modul komplett löschen
        if ($_GET['ajax'] === 'delete' && isset($_GET['module'])) {
            $onlyAI = isset($_GET['only_ai']) && $_GET['only_ai'] === '1';
            echo json_encode(AIGeneratorBot::deleteModuleQuestions($_GET['module'], $onlyAI));
            exit;
        }
        
        // v1.3 NEU: Einzelne Frage löschen
        if ($_GET['ajax'] === 'delete_single' && isset($_GET['id'])) {
            echo json_encode(AIGeneratorBot::deleteSingleQuestion($_GET['id']));
            exit;
        }
        
        echo json_encode(['error' => 'Unknown action']);
        exit;
    }
    
    // Stop-Befehl verarbeiten
    if (isset($_GET['stop'])) {
        AIGeneratorBot::stop();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?stopped=1');
        exit;
    }
    
    // Modul-Stats für UI laden
    $moduleStats = AIGeneratorBot::getModuleStats();
    $totalQuestions = array_sum(array_column($moduleStats, 'total'));
    $totalAI = array_sum(array_column($moduleStats, 'ai_count'));
    
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>🤖 AI Generator Bot v1.4 - sgiT Education</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: linear-gradient(135deg, #1A3503, #2d5a06); 
            padding: 20px; 
            min-height: 100vh;
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
            border-bottom: 3px solid #43D240; 
            padding-bottom: 15px; 
            margin-bottom: 30px;
        }
        h2 {
            color: #1A3503;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .badge {
            display: inline-block;
            background: #43D240;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        .badge.slow { background: #ffc107; color: #333; }
        .badge.v13 { background: #17a2b8; }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #28a745;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #dc3545;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        select, input[type="number"] {
            padding: 12px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            width: 200px;
        }
        button {
            background: #43D240;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        button:hover { background: #3ab837; }
        button.stop { background: #dc3545; }
        button.stop:hover { background: #c82333; }
        button.secondary { background: #6c757d; }
        button.warning { background: #ffc107; color: #333; }
        button.danger { background: #dc3545; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            margin: 0;
        }
        .results {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #43D240;
        }
        .results.running {
            border-left-color: #ffc107;
            background: #fff8e1;
        }
        .log-output {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-indicator.running { background: #ffc107; animation: pulse 1s infinite; }
        .status-indicator.stopped { background: #dc3545; }
        .status-indicator.idle { background: #6c757d; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* v1.2+: Modul-Manager Styles */
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .module-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .module-card:hover {
            border-color: #43D240;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .module-card.selected {
            border-color: #43D240;
            background: #e8f5e9;
        }
        .module-card.empty {
            opacity: 0.5;
        }
        .module-name {
            font-weight: bold;
            color: #1A3503;
            margin-bottom: 8px;
            text-transform: capitalize;
        }
        .module-stats {
            font-size: 13px;
            color: #666;
        }
        .module-stats .count {
            font-size: 24px;
            font-weight: bold;
            color: #1A3503;
        }
        .module-stats .ai-badge {
            display: inline-block;
            background: #17a2b8;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 5px;
        }
        .preview-panel {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }
        .preview-panel.active {
            display: block;
        }
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .preview-list {
            max-height: 500px;
            overflow-y: auto;
        }
        .preview-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }
        .preview-item:hover {
            border-color: #43D240;
        }
        .preview-item .content {
            flex: 1;
        }
        .preview-item .question {
            font-weight: 500;
            margin-bottom: 5px;
        }
        .preview-item .meta {
            font-size: 12px;
            color: #666;
        }
        .preview-item .options {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }
        .preview-item .actions {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .preview-item .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
        }
        .preview-item .delete-btn:hover {
            background: #c82333;
        }
        .preview-item.deleting {
            opacity: 0.5;
            pointer-events: none;
        }
        .preview-item.deleted {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .action-bar {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .total-stats {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: 12px;
        }
        .total-stat {
            text-align: center;
        }
        .total-stat .number {
            font-size: 36px;
            font-weight: bold;
            color: #1A3503;
        }
        .total-stat .label {
            color: #666;
            font-size: 14px;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            color: #666;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        .tab:hover {
            color: #1A3503;
        }
        .tab.active {
            color: #1A3503;
            font-weight: bold;
            border-bottom-color: #43D240;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        .pagination button {
            padding: 8px 16px;
            font-size: 14px;
        }
        .load-more-info {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🤖 AI Generator Bot <span class="badge slow">Dauerlauf</span> <span class="badge v13">v1.6</span></h1>
    
    <?php if (isset($_GET['stopped'])): ?>
    <div class="success-box">
        ✅ <strong>Stop-Signal gesendet!</strong> Der Bot wird nach der aktuellen Frage beendet.
    </div>
    <?php endif; ?>
    
    <!-- Tabs Navigation -->
    <div class="tabs">
        <button class="tab active" onclick="showTab('generator')">🚀 Generator</button>
        <button class="tab" onclick="window.location.href='/questions/generate_module_csv.php'">📝 CSV Generator</button>
        <button class="tab" onclick="showTab('dbmanager')">🗄️ DB-Manager</button>
    </div>
    
    <!-- Tab 1: Generator (Original) -->
    <div id="tab-generator" class="tab-content active">
        
        <div class="info-box">
            <h4>ℹ️ Was macht dieser Bot?</h4>
            <p>Dieser Bot generiert <strong>langsam und kontinuierlich</strong> Fragen für alle 16 Module:</p>
            <ul>
                <li>🐢 <strong>Alle 2 Minuten</strong> eine Frage pro Modul</li>
                <li>♻️ Läuft in <strong>Dauerschleife</strong> bis gestoppt</li>
                <li>💾 Speichert alle generierten Fragen in der Datenbank</li>
                <li>📊 Loggt Erfolge und Fehler für Analyse</li>
            </ul>
        </div>
        
        <div class="warning-box">
            <strong>⚠️ Hinweis:</strong> Der Dauerlauf-Modus kann <strong>Stunden</strong> laufen. 
            Browser-Tab offen lassen oder CLI nutzen!
        </div>
        
        <?php
        $botRunning = AIGeneratorBot::isRunning();
        ?>
        
        <p>
            <span class="status-indicator <?= $botRunning ? 'running' : 'idle' ?>"></span>
            <strong>Status:</strong> <?= $botRunning ? '🟡 Bot läuft möglicherweise' : '⚪ Bereit' ?>
        </p>
        
        <form method="post">
            <div class="form-group">
                <label>🕐 Intervall zwischen Modulen:</label>
                <select name="interval">
                    <option value="30">30 Sekunden (Test)</option>
                    <option value="60">1 Minute</option>
                    <option value="120" selected>2 Minuten (empfohlen)</option>
                    <option value="300">5 Minuten (sehr langsam)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>🎯 Modus:</label>
                <select name="mode">
                    <option value="continuous">♾️ Dauerlauf (bis Stop)</option>
                    <option value="single_round">1️⃣ Eine Runde (alle Module 1x)</option>
                    <option value="quick">🚀 Quick Test (10s Intervall)</option>
                </select>
            </div>
            
            <button type="submit" name="start_bot">▶️ Bot starten</button>
            <a href="?stop=1"><button type="button" class="stop">⏹️ Bot stoppen</button></a>
            <a href="../bot_summary.php"><button type="button" class="secondary">📊 Summary</button></a>
        </form>
        
        <?php
        if (isset($_POST['start_bot'])) {
            $interval = intval($_POST['interval'] ?? 120);
            $mode = $_POST['mode'] ?? 'continuous';
            
            $config = [
                'delayBetweenModules' => $interval,
                'continuous' => ($mode === 'continuous')
            ];
            
            if ($mode === 'quick') {
                $config['delayBetweenModules'] = 10;
                $config['continuous'] = false;
            }
            
            echo '<div class="results running">';
            echo '<h3>🔄 Bot läuft...</h3>';
            echo '<p>Intervall: ' . $interval . 's | Modus: ' . $mode . '</p>';
            echo '<p><small>Diese Seite zeigt Live-Output. Browser-Tab offen lassen!</small></p>';
            echo '<div class="log-output" id="log">';
            
            // Output buffering für Live-Ausgabe
            ob_implicit_flush(true);
            if (ob_get_level()) ob_end_flush();
            
            $bot = new AIGeneratorBot($config);
            $results = $bot->run();
            
            echo '</div>';
            echo '<h4>Ergebnis:</h4>';
            echo '<ul>';
            echo '<li>Runden: ' . ($results['rounds'] ?? 0) . '</li>';
            echo '<li>Generiert: ' . ($results['generated'] ?? 0) . '</li>';
            echo '<li>Fehlgeschlagen: ' . ($results['failed'] ?? 0) . '</li>';
            echo '<li>Laufzeit: ' . ($results['runtime_minutes'] ?? 0) . ' Minuten</li>';
            echo '</ul>';
            echo '</div>';
        }
        ?>
        
        <h3 style="margin-top: 30px;">📋 CLI-Nutzung</h3>
        <p>Für Dauerlauf im Hintergrund (empfohlen):</p>
        <pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 8px;">
cd C:\xampp\htdocs\Education\bots\tests
php AIGeneratorBot.php --mode=continuous --interval=120

# Zum Stoppen: Erstelle Datei STOP_AI_BOT in bots/logs/
echo. > ..\logs\STOP_AI_BOT
        </pre>
        
    </div>
    
    <!-- Tab 2: DB-Manager (v1.2+) -->
    <div id="tab-dbmanager" class="tab-content">
        
        <h2>🗄️ Modul-Datenbank Manager</h2>
        <p>Verwalte die generierten Fragen pro Modul. <strong>Klicke auf ein Modul</strong> um Fragen zu sehen und einzeln oder alle zu löschen.</p>
        
        <!-- Gesamt-Statistiken -->
        <div class="total-stats">
            <div class="total-stat">
                <div class="number" id="total-questions"><?= $totalQuestions ?></div>
                <div class="label">Fragen gesamt</div>
            </div>
            <div class="total-stat">
                <div class="number" id="total-ai"><?= $totalAI ?></div>
                <div class="label">KI-generiert</div>
            </div>
            <div class="total-stat">
                <div class="number"><?= count(array_filter($moduleStats, fn($m) => $m['total'] > 0)) ?></div>
                <div class="label">Module mit Fragen</div>
            </div>
        </div>
        
        <!-- Modul-Grid -->
        <div class="module-grid">
            <?php foreach ($moduleStats as $module => $stats): ?>
            <div class="module-card <?= $stats['total'] == 0 ? 'empty' : '' ?>" 
                 data-module="<?= $module ?>" 
                 onclick="selectModule('<?= $module ?>')">
                <div class="module-name"><?= ucfirst($module) ?></div>
                <div class="module-stats">
                    <span class="count"><?= $stats['total'] ?></span> Fragen
                    <?php if ($stats['ai_count'] > 0): ?>
                    <span class="ai-badge">🤖 <?= $stats['ai_count'] ?> KI</span>
                    <?php endif; ?>
                </div>
                <?php if ($stats['last_created']): ?>
                <div class="module-stats" style="margin-top: 5px; font-size: 11px;">
                    Letzte: <?= date('d.m.Y H:i', strtotime($stats['last_created'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Preview Panel -->
        <div id="preview-panel" class="preview-panel">
            <div class="preview-header">
                <h3>📋 <span id="preview-module-name"></span> - Fragen</h3>
                <span id="preview-count" style="color: #666;"></span>
            </div>
            
            <div id="preview-content" class="preview-list"></div>
            
            <div class="pagination" id="pagination" style="display: none;">
                <button class="secondary btn-sm" onclick="loadMoreQuestions()">📥 Mehr laden</button>
            </div>
            <div class="load-more-info" id="load-more-info"></div>
            
            <div class="action-bar">
                <button class="warning" onclick="deleteModule(false)">
                    🗑️ Alle Fragen dieses Moduls löschen
                </button>
                <button class="danger" onclick="deleteModule(true)">
                    🤖 Nur KI-generierte löschen
                </button>
                <button class="secondary" onclick="closePreview()">
                    ✖️ Schließen
                </button>
            </div>
            
            <div id="delete-result" style="margin-top: 15px;"></div>
        </div>
        
    </div>
    
</div>

<script>
// Tab-Wechsel
function showTab(tabName) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelector(`[onclick="showTab('${tabName}')"]`).classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
}

// Aktuell gewähltes Modul und Pagination-State
let selectedModule = null;
let currentOffset = 0;
let currentQuestions = [];
const QUESTIONS_PER_PAGE = 20;

// Modul auswählen und Preview laden
function selectModule(module) {
    selectedModule = module;
    currentOffset = 0;
    currentQuestions = [];
    
    // Karten-Selection aktualisieren
    document.querySelectorAll('.module-card').forEach(c => c.classList.remove('selected'));
    document.querySelector(`[data-module="${module}"]`).classList.add('selected');
    
    // Preview laden
    document.getElementById('preview-module-name').textContent = module.charAt(0).toUpperCase() + module.slice(1);
    document.getElementById('preview-content').innerHTML = '<p>⏳ Lade Fragen...</p>';
    document.getElementById('preview-count').textContent = '';
    document.getElementById('delete-result').innerHTML = '';
    document.getElementById('pagination').style.display = 'none';
    document.getElementById('load-more-info').textContent = '';
    document.getElementById('preview-panel').classList.add('active');
    
    loadQuestions(true);
}

// Fragen laden
function loadQuestions(reset = false) {
    if (reset) {
        currentOffset = 0;
        currentQuestions = [];
    }
    
    fetch(`?ajax=preview&module=${selectedModule}&limit=${QUESTIONS_PER_PAGE}&offset=${currentOffset}`)
        .then(r => r.json())
        .then(data => {
            if (data.length === 0 && currentOffset === 0) {
                document.getElementById('preview-content').innerHTML = '<p style="color: #666;">Keine Fragen in diesem Modul.</p>';
                document.getElementById('pagination').style.display = 'none';
                return;
            }
            
            // Neue Fragen zu Liste hinzufügen
            currentQuestions = currentQuestions.concat(data);
            currentOffset += data.length;
            
            // HTML rendern
            renderQuestions();
            
            // Pagination anzeigen wenn mehr Fragen verfügbar sein könnten
            if (data.length >= QUESTIONS_PER_PAGE) {
                document.getElementById('pagination').style.display = 'flex';
                document.getElementById('load-more-info').textContent = `${currentQuestions.length} Fragen geladen`;
            } else {
                document.getElementById('pagination').style.display = 'none';
                document.getElementById('load-more-info').textContent = `Alle ${currentQuestions.length} Fragen geladen`;
            }
            
            document.getElementById('preview-count').textContent = `(${currentQuestions.length} angezeigt)`;
        })
        .catch(err => {
            document.getElementById('preview-content').innerHTML = `<p class="error-box">Fehler beim Laden: ${err}</p>`;
        });
}

// Mehr Fragen laden
function loadMoreQuestions() {
    loadQuestions(false);
}

// Fragen rendern
function renderQuestions() {
    let html = '';
    currentQuestions.forEach(q => {
        const isAI = q.ai_generated == 1 || q.model_used;
        const options = q.options ? JSON.parse(q.options) : [];
        
        html += `
            <div class="preview-item" id="question-${q.id}">
                <div class="content">
                    <div class="question">
                        ${isAI ? '🤖' : '📝'} <strong>#${q.id}</strong> - ${escapeHtml(q.question)}
                    </div>
                    <div class="meta">
                        ✅ Antwort: <strong>${escapeHtml(q.answer)}</strong> | 
                        Schwierigkeit: ${q.difficulty || '?'} | 
                        ${q.model_used ? '🤖 ' + q.model_used : '📝 Manuell'} | 
                        📅 ${q.created_at}
                    </div>
                    ${options.length > 0 ? `<div class="options">Optionen: ${options.map(o => escapeHtml(o)).join(' | ')}</div>` : ''}
                </div>
                <div class="actions">
                    <button class="delete-btn" onclick="deleteSingleQuestion(${q.id})" title="Diese Frage löschen">
                        🗑️ Löschen
                    </button>
                </div>
            </div>
        `;
    });
    document.getElementById('preview-content').innerHTML = html;
}

// v1.3 NEU: Einzelne Frage löschen
function deleteSingleQuestion(id) {
    if (!confirm(`Frage #${id} wirklich löschen?`)) return;
    
    const item = document.getElementById(`question-${id}`);
    if (item) item.classList.add('deleting');
    
    fetch(`?ajax=delete_single&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Aus lokaler Liste entfernen
                currentQuestions = currentQuestions.filter(q => q.id != id);
                
                // UI aktualisieren
                if (item) {
                    item.classList.remove('deleting');
                    item.classList.add('deleted');
                    item.innerHTML = `<div class="content"><em>✅ Frage #${id} gelöscht</em></div>`;
                    
                    // Nach 1s ausblenden
                    setTimeout(() => {
                        item.style.display = 'none';
                        document.getElementById('preview-count').textContent = `(${currentQuestions.length} angezeigt)`;
                    }, 1000);
                }
                
                // Modul-Stats aktualisieren
                updateModuleStats();
                
            } else {
                if (item) item.classList.remove('deleting');
                alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
            }
        })
        .catch(err => {
            if (item) item.classList.remove('deleting');
            alert('Fehler: ' + err);
        });
}

// Preview schließen
function closePreview() {
    document.getElementById('preview-panel').classList.remove('active');
    document.querySelectorAll('.module-card').forEach(c => c.classList.remove('selected'));
    selectedModule = null;
    currentQuestions = [];
    currentOffset = 0;
}

// Modul löschen (alle oder nur AI)
function deleteModule(onlyAI) {
    if (!selectedModule) return;
    
    const typeText = onlyAI ? 'alle KI-generierten Fragen' : 'ALLE Fragen';
    const confirmText = `Wirklich ${typeText} aus "${selectedModule}" löschen?\n\nDiese Aktion kann nicht rückgängig gemacht werden!`;
    
    if (!confirm(confirmText)) return;
    if (!onlyAI && !confirm('LETZTE WARNUNG: Wirklich ALLE Fragen unwiderruflich löschen?')) return;
    
    const resultDiv = document.getElementById('delete-result');
    resultDiv.innerHTML = '<div class="info-box">⏳ Lösche...</div>';
    
    fetch(`?ajax=delete&module=${selectedModule}&only_ai=${onlyAI ? '1' : '0'}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="success-box">✅ ${data.message}</div>`;
                setTimeout(() => location.reload(), 1500);
            } else {
                resultDiv.innerHTML = `<div class="error-box">❌ Fehler: ${data.error}</div>`;
            }
        })
        .catch(err => {
            resultDiv.innerHTML = `<div class="error-box">❌ Fehler: ${err}</div>`;
        });
}

// Modul-Stats aktualisieren (nach Einzellöschung)
function updateModuleStats() {
    fetch('?ajax=get_stats')
        .then(r => r.json())
        .then(data => {
            let totalQ = 0, totalAI = 0;
            
            for (const [module, stats] of Object.entries(data)) {
                totalQ += stats.total;
                totalAI += stats.ai_count;
                
                const card = document.querySelector(`[data-module="${module}"]`);
                if (card) {
                    const countEl = card.querySelector('.count');
                    if (countEl) countEl.textContent = stats.total;
                    
                    // AI Badge aktualisieren
                    let aiBadge = card.querySelector('.ai-badge');
                    if (stats.ai_count > 0) {
                        if (aiBadge) {
                            aiBadge.textContent = `🤖 ${stats.ai_count} KI`;
                        } else {
                            const statsDiv = card.querySelector('.module-stats');
                            statsDiv.innerHTML += ` <span class="ai-badge">🤖 ${stats.ai_count} KI</span>`;
                        }
                    } else if (aiBadge) {
                        aiBadge.remove();
                    }
                    
                    // Empty-Klasse
                    if (stats.total === 0) {
                        card.classList.add('empty');
                    } else {
                        card.classList.remove('empty');
                    }
                }
            }
            
            // Gesamt-Stats aktualisieren
            document.getElementById('total-questions').textContent = totalQ;
            document.getElementById('total-ai').textContent = totalAI;
        });
}

// HTML escapen
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Stats periodisch aktualisieren (alle 60s)
setInterval(() => {
    if (document.getElementById('tab-dbmanager').classList.contains('active')) {
        updateModuleStats();
    }
}, 60000);
</script>

</body>
</html>
<?php 
}

// CLI-Modus
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['mode:', 'interval:', 'help']);
    
    if (isset($options['help'])) {
        echo "\nAI Generator Bot - Langsamer Dauerlauf\n";
        echo "======================================\n\n";
        echo "Optionen:\n";
        echo "  --mode=continuous   Dauerlauf bis Stop-Signal\n";
        echo "  --mode=single       Eine Runde durch alle Module\n";
        echo "  --mode=quick        Schnelltest (10s Intervall)\n";
        echo "  --interval=120      Sekunden zwischen Modulen (default: 120)\n\n";
        echo "Zum Stoppen: Erstelle Datei STOP_AI_BOT in bots/logs/\n\n";
        exit(0);
    }
    
    $mode = $options['mode'] ?? 'continuous';
    $interval = intval($options['interval'] ?? 120);
    
    $config = [
        'delayBetweenModules' => $interval,
        'continuous' => ($mode === 'continuous')
    ];
    
    if ($mode === 'quick') {
        $config['delayBetweenModules'] = 10;
        $config['continuous'] = false;
    }
    
    echo "\n🤖 AI Generator Bot - Langsamer Dauerlauf\n";
    echo "=========================================\n";
    echo "Modus: $mode | Intervall: {$interval}s\n";
    echo "Zum Stoppen: Erstelle Datei STOP_AI_BOT in bots/logs/\n\n";
    
    $bot = new AIGeneratorBot($config);
    $results = $bot->run();
    
    echo "\n\nErgebnis:\n";
    print_r($results);
}
?>
