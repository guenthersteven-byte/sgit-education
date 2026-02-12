<?php
/**
 * ============================================================================
 * sgiT Education - AI Generator Bot
 * ============================================================================
 * 
 * LANGSAMER DAUERLAUF-BOT für Massen-Generierung
 * - Alle 2 Minuten eine Frage pro Modul
 * - Läuft bis manuell gestoppt
 * - Überlastet Ollama nicht
 * 
 * Nutzt zentrale Versionsverwaltung via /includes/version.php
 * 
 * @version Siehe SGIT_VERSION
 * @date Siehe SGIT_VERSION_DATE
 * @author sgiT Solution Engineering & IT Services
 * ============================================================================
 */

// Zentrale Versionsverwaltung
require_once dirname(dirname(__DIR__)) . '/includes/version.php';

// WICHTIG: Korrekter Pfad zum bot_logger (eine Ebene höher)
require_once dirname(__DIR__) . '/bot_logger.php';
require_once dirname(__DIR__) . '/bot_output_helper.php';
// HINWEIS: windows_ai_generator.php wurde entfernt - AIGeneratorBot-Klasse ist deprecated
// Die Generierung erfolgt jetzt über CSV Generator, Auto-Generator oder Batch Import

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
        // DEPRECATED: Die alte AIQuestionGeneratorComplete-Klasse existiert nicht mehr
        // $this->generator = new AIQuestionGeneratorComplete();
        $this->generator = null; // Bot ist deprecated - nutze CSV Generator stattdessen
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
     * v1.8 GEÄNDERT: Deaktiviert alle Fragen eines Moduls (Soft-Delete)
     * Hash bleibt erhalten, AI generiert dieselbe Frage nicht erneut!
     */
    public static function deactivateModuleQuestions($module, $onlyAI = false) {
        $dbPath = dirname(dirname(__DIR__)) . '/AI/data/questions.db';
        if (!file_exists($dbPath)) {
            return ['success' => false, 'error' => 'Datenbank nicht gefunden'];
        }
        
        try {
            $db = new SQLite3($dbPath);
            
            // Erst zählen was deaktiviert wird
            if ($onlyAI) {
                $stmt = $db->prepare('
                    SELECT COUNT(*) as count FROM questions 
                    WHERE LOWER(module) = LOWER(:module) 
                    AND (ai_generated = 1 OR model_used IS NOT NULL)
                    AND is_active = 1
                ');
            } else {
                $stmt = $db->prepare('
                    SELECT COUNT(*) as count FROM questions 
                    WHERE LOWER(module) = LOWER(:module)
                    AND is_active = 1
                ');
            }
            $stmt->bindValue(':module', $module, SQLITE3_TEXT);
            $countResult = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            $deactivateCount = intval($countResult['count'] ?? 0);
            
            if ($deactivateCount == 0) {
                $db->close();
                return ['success' => true, 'deactivated' => 0, 'message' => 'Keine aktiven Fragen zum Deaktivieren gefunden'];
            }
            
            // Jetzt deaktivieren (Soft-Delete)
            if ($onlyAI) {
                $stmt = $db->prepare('
                    UPDATE questions SET is_active = 0
                    WHERE LOWER(module) = LOWER(:module) 
                    AND (ai_generated = 1 OR model_used IS NOT NULL)
                ');
            } else {
                $stmt = $db->prepare('
                    UPDATE questions SET is_active = 0
                    WHERE LOWER(module) = LOWER(:module)
                ');
            }
            $stmt->bindValue(':module', $module, SQLITE3_TEXT);
            $stmt->execute();
            
            $db->close();
            
            return [
                'success' => true, 
                'deactivated' => $deactivateCount, 
                'message' => "$deactivateCount Fragen aus '$module' deaktiviert (Hash bleibt erhalten)"
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * v1.3 → v1.8 GEÄNDERT: Deaktiviert eine einzelne Frage (Soft-Delete)
     * Hash bleibt erhalten, AI generiert dieselbe Frage nicht erneut!
     */
    public static function deactivateSingleQuestion($id) {
        $dbPath = dirname(dirname(__DIR__)) . '/AI/data/questions.db';
        if (!file_exists($dbPath)) {
            return ['success' => false, 'error' => 'Datenbank nicht gefunden'];
        }
        
        try {
            $db = new SQLite3($dbPath);
            
            // Erst prüfen ob die Frage existiert
            $stmt = $db->prepare('SELECT id, question, module, is_active FROM questions WHERE id = :id');
            $stmt->bindValue(':id', intval($id), SQLITE3_INTEGER);
            $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            
            if (!$result) {
                $db->close();
                return ['success' => false, 'error' => 'Frage nicht gefunden (ID: ' . $id . ')'];
            }
            
            // Soft-Delete: is_active = 0 statt DELETE
            $stmt = $db->prepare('UPDATE questions SET is_active = 0 WHERE id = :id');
            $stmt->bindValue(':id', intval($id), SQLITE3_INTEGER);
            $stmt->execute();
            
            $db->close();
            
            return [
                'success' => true, 
                'deactivated_id' => $id,
                'module' => $result['module'],
                'message' => "Frage #$id deaktiviert (Hash bleibt erhalten)"
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
        
        // Modul deaktivieren (Soft-Delete)
        if ($_GET['ajax'] === 'deactivate' && isset($_GET['module'])) {
            $onlyAI = isset($_GET['only_ai']) && $_GET['only_ai'] === '1';
            echo json_encode(AIGeneratorBot::deactivateModuleQuestions($_GET['module'], $onlyAI));
            exit;
        }
        
        // v1.8: Einzelne Frage deaktivieren (Soft-Delete)
        if ($_GET['ajax'] === 'deactivate_single' && isset($_GET['id'])) {
            echo json_encode(AIGeneratorBot::deactivateSingleQuestion($_GET['id']));
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
    
    // v1.8 NEU: Erweiterte Statistiken (wie statistics.php)
    $extendedStats = [
        'total' => $totalQuestions,
        'ai_direct' => $totalAI,
        'ai_csv' => 0,
        'with_explanation' => 0,
        'sats_distributed' => 0
    ];
    
    // Fragen-DB für erweiterte Stats
    $questionsDbPath = dirname(dirname(__DIR__)) . '/AI/data/questions.db';
    if (file_exists($questionsDbPath)) {
        try {
            $db = new PDO('sqlite:' . $questionsDbPath);
            $extendedStats['ai_csv'] = $db->query("SELECT COUNT(*) FROM questions WHERE source = 'csv_import'")->fetchColumn() ?: 0;
            $extendedStats['with_explanation'] = $db->query("SELECT COUNT(*) FROM questions WHERE explanation IS NOT NULL AND explanation != ''")->fetchColumn() ?: 0;
        } catch (Exception $e) {}
    }
    
    // Wallet-DB für Sats
    $walletDbPath = dirname(dirname(__DIR__)) . '/wallet/wallet.db';
    if (file_exists($walletDbPath)) {
        try {
            $db = new PDO('sqlite:' . $walletDbPath);
            $extendedStats['sats_distributed'] = $db->query("SELECT COALESCE(SUM(total_earned), 0) FROM child_wallets")->fetchColumn() ?: 0;
        } catch (Exception $e) {}
    }
    
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>🤖 AI Generator Bot v1.8 - sgiT Education</title>
    <style>
        :root {
            --dark-green: #1A3503;
            --neon-green: #43D240;
            --bg-dark: #0d1a02;
            --card-bg: rgba(0,0,0,0.3);
            --border-green: rgba(67, 210, 64, 0.3);
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Space Grotesk', system-ui, sans-serif; 
            background: linear-gradient(135deg, #0d1a02 0%, #1A3503 100%);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: rgba(0,0,0,0.4);
            border-radius: 12px;
            border: 1px solid var(--border-green);
        }
        
        .header-bar .title {
            font-size: 1.2em;
            font-weight: bold;
            color: #fff;
        }
        
        .header-bar .nav-links a {
            display: inline-block;
            padding: 8px 16px;
            margin-left: 10px;
            background: var(--neon-green);
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .header-bar .nav-links a:hover {
            background: #3ab837;
        }
        
        .header-bar .nav-links a.secondary {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        
        h1 { 
            color: var(--neon-green); 
            font-size: 1.8em;
            margin-bottom: 10px;
            text-align: center;
        }
        
        h2 {
            color: var(--neon-green);
            margin-top: 30px;
            margin-bottom: 15px;
        }
        
        h4 {
            color: var(--neon-green);
            margin-bottom: 10px;
        }
        
        .badge {
            display: inline-block;
            background: var(--neon-green);
            color: #000;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
            font-weight: bold;
        }
        
        .badge.v13 { background: #17a2b8; color: #fff; }
        
        /* Info/Success/Warning Boxes */
        .info-box {
            background: var(--card-bg);
            border: 1px solid var(--border-green);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .success-box {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border: 1px solid var(--border-green);
            background: rgba(0,0,0,0.3);
            color: #aaa;
            font-size: 15px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .tab:hover {
            color: #fff;
            border-color: var(--neon-green);
        }
        
        .tab.active {
            color: #000;
            font-weight: bold;
            background: var(--neon-green);
            border-color: var(--neon-green);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .gen-card {
            background: rgba(0,0,0,0.4);
            border: 1px solid var(--border-green);
            border-radius: 16px;
            padding: 25px;
            transition: all 0.3s;
        }
        
        .gen-card:hover {
            border-color: var(--neon-green);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(67, 210, 64, 0.2);
        }
        
        .gen-card .card-title {
            font-size: 1.1em;
            font-weight: bold;
            color: var(--neon-green);
            margin-bottom: 10px;
        }
        
        .gen-card .card-desc {
            color: #ccc;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .gen-card ul {
            color: #aaa;
            font-size: 13px;
            margin: 15px 0;
            padding-left: 20px;
        }
        
        .gen-card ul li {
            margin-bottom: 5px;
        }
        
        .gen-card .btn-card {
            display: block;
            width: 100%;
            padding: 12px;
            text-align: center;
            background: var(--neon-green);
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .gen-card .btn-card:hover {
            background: #3ab837;
        }
        
        .gen-card .btn-card.blue {
            background: #17a2b8;
            color: #fff;
        }
        
        .gen-card .btn-card.blue:hover {
            background: #138496;
        }
        
        /* Quick Links */
        .quick-links {
            margin-top: 30px;
            padding: 20px;
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            border: 1px solid var(--border-green);
        }
        
        .quick-links a {
            display: inline-block;
            padding: 10px 20px;
            margin-right: 10px;
            margin-bottom: 10px;
            background: rgba(255,255,255,0.1);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .quick-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* Module Cards (DB-Manager) */
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .module-card {
            background: rgba(0,0,0,0.4);
            border: 1px solid var(--border-green);
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .module-card:hover {
            border-color: var(--neon-green);
            transform: translateY(-2px);
        }
        
        .module-card.selected {
            border-color: var(--neon-green);
            background: rgba(67, 210, 64, 0.1);
        }
        
        .module-name {
            font-weight: bold;
            color: var(--neon-green);
            margin-bottom: 8px;
        }
        
        .module-stats {
            font-size: 13px;
            color: #aaa;
        }
        
        .module-stats .count {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
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
        
        /* Total Stats */
        .total-stats {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
            padding: 20px;
            background: rgba(67, 210, 64, 0.1);
            border-radius: 12px;
            border: 1px solid var(--border-green);
        }
        
        .total-stat {
            text-align: center;
        }
        
        .total-stat .number {
            font-size: 36px;
            font-weight: bold;
            color: var(--neon-green);
        }
        
        .total-stat .label {
            color: #aaa;
            font-size: 14px;
        }
        
        /* Preview Panel */
        .preview-panel {
            background: rgba(0,0,0,0.4);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            display: none;
            border: 1px solid var(--border-green);
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
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }
        
        .preview-item:hover {
            border-color: var(--neon-green);
        }
        
        .preview-item .content {
            flex: 1;
        }
        
        .preview-item .question {
            font-weight: 500;
            margin-bottom: 5px;
            color: #fff;
        }
        
        .preview-item .meta {
            font-size: 12px;
            color: #888;
        }
        
        .preview-item .options {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        
        .preview-item .deactivate-btn {
            background: #f39c12;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
        }
        
        .preview-item .deactivate-btn:hover {
            background: #d68910;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--neon-green);
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn:hover { background: #3ab837; }
        .btn.secondary { background: rgba(255,255,255,0.1); color: #fff; }
        .btn.danger { background: #dc3545; color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        /* Action Bar */
        .action-bar {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        
        .load-more-info {
            text-align: center;
            color: #888;
            font-size: 13px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<!-- Header Bar -->
<div class="header-bar">
    <div class="title">🤖 sgiT Generator</div>
</div>

<div class="container">
    <h1>🤖 AI Generator Bot <span class="badge v13">v1.8</span></h1>
    
    <?php if (isset($_GET['stopped'])): ?>
    <div class="success-box">
        ✅ <strong>Stop-Signal gesendet!</strong> Der Bot wird nach der aktuellen Frage beendet.
    </div>
    <?php endif; ?>
    
    <!-- Tabs Navigation -->
    <div class="tabs">
        <button class="tab active" onclick="showTab('generator')">⚙️ Generatoren</button>
        <button class="tab" onclick="showTab('dbmanager')">🗄️ DB-Manager</button>
    </div>
    
    <!-- Tab 1: Generatoren (v1.7 - Refactored) -->
    <div id="tab-generator" class="tab-content active">
        
        <div class="info-box">
            <h4>⚙️ Verfügbare Generatoren</h4>
            <p>Wähle den passenden Generator für deine Anforderungen:</p>
        </div>
        
        <!-- Generator Cards -->
        <div class="cards-grid">
            
            <!-- CSV Generator Card -->
            <div class="gen-card">
                <div class="card-title">📝 CSV Generator</div>
                <p class="card-desc">
                    Generiert Fragen und speichert sie als CSV-Datei.<br>
                    <strong>Empfohlen</strong> für kontrollierte Generierung.
                </p>
                <ul>
                    <li>Einzelne Module auswählen</li>
                    <li>Anzahl Fragen bestimmen</li>
                    <li>CSV zur Prüfung vor Import</li>
                </ul>
                <a href="/questions/generate_module_csv.php" class="btn-card">
                    🚀 CSV Generator öffnen
                </a>
            </div>
            
            <!-- Auto-Generator Card -->
            <div class="gen-card">
                <div class="card-title">⏱️ Auto-Generator (Scheduler)</div>
                <p class="card-desc">
                    Zeitgesteuerte Generierung für alle Module.<br>
                    <strong>Ideal</strong> für Massen-Generierung.
                </p>
                <ul>
                    <li>Zeitlimits: 1h bis 24h</li>
                    <li>Alle 18 Quiz-Module</li>
                    <li>Pause/Resume möglich</li>
                </ul>
                <a href="/auto_generator.php" class="btn-card blue">
                    ⏱️ Auto-Generator öffnen
                </a>
            </div>
            
            <!-- Batch Import Card -->
            <div class="gen-card">
                <div class="card-title">📥 Batch Import</div>
                <p class="card-desc">
                    CSV-Dateien per Drag & Drop importieren.<br>
                    <strong>Schnell</strong> mehrere Dateien verarbeiten.
                </p>
                <ul>
                    <li>Drag & Drop Upload</li>
                    <li>Auto-Modul-Erkennung</li>
                    <li>Live-Fortschritt</li>
                </ul>
                <a href="/batch_import.php" class="btn-card">
                    📥 Batch Import öffnen
                </a>
            </div>
            
        </div>
        
        <!-- Quick Links -->
        <div class="quick-links">
            <h4>🔗 Weitere Tools</h4>
            <a href="../bot_summary.php">📊 Bot Summary</a>
            <a href="/admin_v4.php">🏠 Admin Dashboard</a>
            <a href="/statistics.php">📈 Statistiken</a>
        </div>
        
    </div>
    
    <!-- Tab 2: DB-Manager (v1.2+) -->
    <div id="tab-dbmanager" class="tab-content">
        
        <h2>🗄️ Modul-Datenbank Manager</h2>
        <p>Verwalte die generierten Fragen pro Modul. <strong>Klicke auf ein Modul</strong> um Fragen zu sehen und einzeln oder alle zu löschen.</p>
        
        <!-- v1.8: Statistik Dashboard -->
        <div class="stats-dashboard" style="background: rgba(0,0,0,0.4); border: 1px solid var(--border-green); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="color: var(--neon-green); margin: 0;">📊 Statistik Dashboard</h4>
                <div class="quick-nav" style="display: flex; gap: 10px;">
                    <a href="/admin_v4.php" class="btn btn-sm secondary">🏠 Admin</a>
                    <a href="/adaptive_learning.php" class="btn btn-sm secondary">📚 Lernen</a>
                    <a href="/clippy/test.php" class="btn btn-sm" style="background: #f39c12; color: #fff;">🦊 Foxy</a>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px;">
                <div style="text-align: center; padding: 15px; background: rgba(67,210,64,0.1); border-radius: 10px; border: 1px solid var(--border-green);">
                    <div style="font-size: 10px; color: #888; margin-bottom: 5px;">📝</div>
                    <div style="font-size: 24px; font-weight: bold; color: var(--neon-green);"><?= number_format($extendedStats['total']) ?></div>
                    <div style="font-size: 11px; color: #aaa;">Fragen gesamt</div>
                </div>
                <div style="text-align: center; padding: 15px; background: rgba(23,162,184,0.1); border-radius: 10px; border: 1px solid rgba(23,162,184,0.3);">
                    <div style="font-size: 10px; color: #888; margin-bottom: 5px;">🤖</div>
                    <div style="font-size: 24px; font-weight: bold; color: #17a2b8;"><?= number_format($extendedStats['ai_direct']) ?></div>
                    <div style="font-size: 11px; color: #aaa;">AI → direkt in DB</div>
                </div>
                <div style="text-align: center; padding: 15px; background: rgba(40,167,69,0.1); border-radius: 10px; border: 1px solid rgba(40,167,69,0.3);">
                    <div style="font-size: 10px; color: #888; margin-bottom: 5px;">📄</div>
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;"><?= number_format($extendedStats['ai_csv']) ?></div>
                    <div style="font-size: 11px; color: #aaa;">AI → via CSV</div>
                </div>
                <div style="text-align: center; padding: 15px; background: rgba(255,193,7,0.1); border-radius: 10px; border: 1px solid rgba(255,193,7,0.3);">
                    <div style="font-size: 10px; color: #888; margin-bottom: 5px;">💡</div>
                    <div style="font-size: 24px; font-weight: bold; color: #ffc107;"><?= number_format($extendedStats['with_explanation']) ?></div>
                    <div style="font-size: 11px; color: #aaa;">Mit Erklärung</div>
                </div>
                <div style="text-align: center; padding: 15px; background: rgba(247,147,26,0.1); border-radius: 10px; border: 1px solid rgba(247,147,26,0.3);">
                    <div style="font-size: 10px; color: #888; margin-bottom: 5px;">₿</div>
                    <div style="font-size: 24px; font-weight: bold; color: #f7931a;"><?= number_format($extendedStats['sats_distributed']) ?></div>
                    <div style="font-size: 11px; color: #aaa;">Sats verteilt</div>
                </div>
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
                <button class="btn secondary" onclick="deactivateModule(false)">
                    ⏸️ Alle Fragen dieses Moduls deaktivieren
                </button>
                <button class="btn" style="background: #f39c12;" onclick="deactivateModule(true)">
                    🤖 Nur KI-generierte deaktivieren
                </button>
                <button class="btn secondary" onclick="closePreview()">
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
                    <button class="deactivate-btn" onclick="deactivateSingleQuestion(${q.id})" title="Diese Frage deaktivieren (Hash bleibt erhalten)">
                        ⏸️ Deaktiv.
                    </button>
                </div>
            </div>
        `;
    });
    document.getElementById('preview-content').innerHTML = html;
}

// v1.8: Einzelne Frage deaktivieren (Soft-Delete, Hash bleibt erhalten)
function deactivateSingleQuestion(id) {
    if (!confirm(`Frage #${id} wirklich deaktivieren?\n\n(Hash bleibt erhalten - AI generiert diese Frage nicht erneut)`)) return;
    
    const item = document.getElementById(`question-${id}`);
    if (item) item.classList.add('deleting');
    
    fetch(`?ajax=deactivate_single&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Aus lokaler Liste entfernen
                currentQuestions = currentQuestions.filter(q => q.id != id);
                
                // UI aktualisieren
                if (item) {
                    item.classList.remove('deleting');
                    item.classList.add('deleted');
                    item.innerHTML = `<div class="content"><em>⏸️ Frage #${id} deaktiviert (Hash erhalten)</em></div>`;
                    
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

// Modul deaktivieren (alle oder nur AI) - Soft-Delete
function deactivateModule(onlyAI) {
    if (!selectedModule) return;
    
    const typeText = onlyAI ? 'alle KI-generierten Fragen' : 'ALLE Fragen';
    const confirmText = `Wirklich ${typeText} aus "${selectedModule}" deaktivieren?\n\n(Hash bleibt erhalten - AI generiert diese Fragen nicht erneut)`;
    
    if (!confirm(confirmText)) return;
    
    const resultDiv = document.getElementById('delete-result');
    resultDiv.innerHTML = '<div class="info-box">⏳ Deaktiviere...</div>';
    
    fetch(`?ajax=deactivate&module=${selectedModule}&only_ai=${onlyAI ? '1' : '0'}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="success-box">⏸️ ${data.message}</div>`;
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
            
            // Statistik-Dashboard wird bei Page-Reload aktualisiert
            // (Live-Update nicht nötig, da Stats sich selten ändern)
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
