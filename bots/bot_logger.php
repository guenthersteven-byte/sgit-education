<?php
/**
 * sgiT Education - Bot Logger v1.3
 * 
 * Zentrales Logging-System für alle Test-Bots
 * Speichert in SQLite + Textdateien für einfache Analyse
 * 
 * v1.3: Docker/nginx/PHP-FPM kompatibles Output (BUG-032 Fix)
 * v1.2: WAL-Modus für bessere Performance aktiviert
 * v1.1: Robustere Fehlerbehandlung wenn DB noch nicht existiert
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.3
 * @date 06.12.2025
 */

class BotLogger {
    
    private $db;
    private $currentRunId = null;
    private $logDir;
    private $botType;
    private $dbInitialized = false;
    
    // Log-Level Konstanten
    const LEVEL_SUCCESS = 'success';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    // Kategorien
    const CAT_AI = 'ai_generator';
    const CAT_FUNCTION = 'function_test';
    const CAT_LOAD = 'load_test';
    const CAT_SECURITY = 'security';
    const CAT_PERFORMANCE = 'performance';
    
    /**
     * Konstruktor - Initialisiert Datenbank und Verzeichnisse
     */
    public function __construct($botType = 'general') {
        $this->botType = $botType;
        $this->logDir = __DIR__ . '/logs';
        
        // Verzeichnis erstellen falls nicht vorhanden
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
        
        $this->initDatabase();
    }
    
    /**
     * Initialisiert SQLite-Datenbank mit Schema
     */
    private function initDatabase() {
        try {
            $dbPath = $this->logDir . '/bot_results.db';
            $this->db = new SQLite3($dbPath);
            $this->db->enableExceptions(true);
            
            // WAL-Modus für bessere Performance
            $this->db->exec('PRAGMA journal_mode = WAL');
            $this->db->exec('PRAGMA synchronous = NORMAL');
            $this->db->exec('PRAGMA busy_timeout = 5000');
            
            // Bot-Runs Tabelle (ein Eintrag pro Bot-Durchlauf)
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS bot_runs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    bot_type TEXT NOT NULL,
                    bot_name TEXT,
                    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    finished_at DATETIME,
                    status TEXT DEFAULT "running",
                    total_tests INTEGER DEFAULT 0,
                    passed INTEGER DEFAULT 0,
                    warnings INTEGER DEFAULT 0,
                    errors INTEGER DEFAULT 0,
                    critical INTEGER DEFAULT 0,
                    summary TEXT,
                    config TEXT
                )
            ');
            
            // Einzelne Ergebnisse
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS bot_results (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    run_id INTEGER,
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    level TEXT NOT NULL,
                    category TEXT,
                    module TEXT,
                    test_name TEXT,
                    message TEXT NOT NULL,
                    details TEXT,
                    suggestion TEXT,
                    duration_ms INTEGER,
                    FOREIGN KEY (run_id) REFERENCES bot_runs(id)
                )
            ');
            
            // Metriken für Performance-Daten
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS bot_metrics (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    run_id INTEGER,
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    metric_name TEXT NOT NULL,
                    metric_value REAL,
                    unit TEXT,
                    module TEXT,
                    context TEXT,
                    FOREIGN KEY (run_id) REFERENCES bot_runs(id)
                )
            ');
            
            // Verbesserungsvorschläge
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS bot_suggestions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    run_id INTEGER,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    priority TEXT DEFAULT "medium",
                    category TEXT,
                    title TEXT NOT NULL,
                    description TEXT,
                    affected_files TEXT,
                    status TEXT DEFAULT "open",
                    FOREIGN KEY (run_id) REFERENCES bot_runs(id)
                )
            ');
            
            // Indizes für schnellere Abfragen
            $this->db->exec('CREATE INDEX IF NOT EXISTS idx_results_run ON bot_results(run_id)');
            $this->db->exec('CREATE INDEX IF NOT EXISTS idx_results_level ON bot_results(level)');
            $this->db->exec('CREATE INDEX IF NOT EXISTS idx_results_module ON bot_results(module)');
            $this->db->exec('CREATE INDEX IF NOT EXISTS idx_metrics_run ON bot_metrics(run_id)');
            
            $this->dbInitialized = true;
            
        } catch (Exception $e) {
            error_log("BotLogger DB Error: " . $e->getMessage());
            $this->db = null;
            $this->dbInitialized = false;
        }
    }
    
    /**
     * Startet einen neuen Bot-Run
     */
    public function startRun($botName, $config = []) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare('
                INSERT INTO bot_runs (bot_type, bot_name, config, status)
                VALUES (:type, :name, :config, "running")
            ');
            
            $stmt->bindValue(':type', $this->botType, SQLITE3_TEXT);
            $stmt->bindValue(':name', $botName, SQLITE3_TEXT);
            $stmt->bindValue(':config', json_encode($config), SQLITE3_TEXT);
            $stmt->execute();
            
            $this->currentRunId = $this->db->lastInsertRowID();
            
            // Log-Start auch in Textdatei
            $this->writeToFile("═══════════════════════════════════════════════════════════");
            $this->writeToFile("🚀 BOT RUN STARTED: $botName");
            $this->writeToFile("   Run ID: {$this->currentRunId}");
            $this->writeToFile("   Time: " . date('Y-m-d H:i:s'));
            $this->writeToFile("   Config: " . json_encode($config));
            $this->writeToFile("═══════════════════════════════════════════════════════════");
            
            return $this->currentRunId;
            
        } catch (Exception $e) {
            error_log("BotLogger startRun Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Beendet den aktuellen Bot-Run
     */
    public function endRun($summary = '') {
        if (!$this->db || !$this->currentRunId) return;
        
        try {
            // Zähle Ergebnisse
            $counts = $this->getRunCounts($this->currentRunId);
            
            $stmt = $this->db->prepare('
                UPDATE bot_runs 
                SET finished_at = CURRENT_TIMESTAMP,
                    status = "completed",
                    total_tests = :total,
                    passed = :passed,
                    warnings = :warnings,
                    errors = :errors,
                    critical = :critical,
                    summary = :summary
                WHERE id = :id
            ');
            
            $stmt->bindValue(':total', $counts['total'], SQLITE3_INTEGER);
            $stmt->bindValue(':passed', $counts['success'], SQLITE3_INTEGER);
            $stmt->bindValue(':warnings', $counts['warning'], SQLITE3_INTEGER);
            $stmt->bindValue(':errors', $counts['error'], SQLITE3_INTEGER);
            $stmt->bindValue(':critical', $counts['critical'], SQLITE3_INTEGER);
            $stmt->bindValue(':summary', $summary, SQLITE3_TEXT);
            $stmt->bindValue(':id', $this->currentRunId, SQLITE3_INTEGER);
            $stmt->execute();
            
            // Log-Ende in Textdatei
            $this->writeToFile("═══════════════════════════════════════════════════════════");
            $this->writeToFile("🏁 BOT RUN COMPLETED");
            $this->writeToFile("   ✅ Passed: {$counts['success']}");
            $this->writeToFile("   ⚠️ Warnings: {$counts['warning']}");
            $this->writeToFile("   ❌ Errors: {$counts['error']}");
            $this->writeToFile("   🔴 Critical: {$counts['critical']}");
            $this->writeToFile("   Total: {$counts['total']}");
            $this->writeToFile("═══════════════════════════════════════════════════════════\n");
            
        } catch (Exception $e) {
            error_log("BotLogger endRun Error: " . $e->getMessage());
        }
    }
    
    /**
     * Loggt ein Ergebnis
     */
    public function log($level, $message, $options = []) {
        $module = $options['module'] ?? null;
        $testName = $options['test'] ?? null;
        $details = $options['details'] ?? null;
        $suggestion = $options['suggestion'] ?? null;
        $category = $options['category'] ?? $this->botType;
        $duration = $options['duration_ms'] ?? null;
        
        // In Datenbank speichern
        if ($this->db && $this->currentRunId) {
            try {
                $stmt = $this->db->prepare('
                    INSERT INTO bot_results 
                    (run_id, level, category, module, test_name, message, details, suggestion, duration_ms)
                    VALUES (:run, :level, :cat, :module, :test, :msg, :details, :suggestion, :duration)
                ');
                
                $stmt->bindValue(':run', $this->currentRunId, SQLITE3_INTEGER);
                $stmt->bindValue(':level', $level, SQLITE3_TEXT);
                $stmt->bindValue(':cat', $category, SQLITE3_TEXT);
                $stmt->bindValue(':module', $module, SQLITE3_TEXT);
                $stmt->bindValue(':test', $testName, SQLITE3_TEXT);
                $stmt->bindValue(':msg', $message, SQLITE3_TEXT);
                $stmt->bindValue(':details', is_array($details) ? json_encode($details) : $details, SQLITE3_TEXT);
                $stmt->bindValue(':suggestion', $suggestion, SQLITE3_TEXT);
                $stmt->bindValue(':duration', $duration, SQLITE3_INTEGER);
                $stmt->execute();
                
            } catch (Exception $e) {
                error_log("BotLogger log Error: " . $e->getMessage());
            }
        }
        
        // In Textdatei schreiben
        $icon = $this->getLevelIcon($level);
        $moduleStr = $module ? "[$module] " : "";
        $testStr = $testName ? "($testName) " : "";
        $durationStr = $duration ? " [{$duration}ms]" : "";
        
        $logLine = "$icon $moduleStr$testStr$message$durationStr";
        $this->writeToFile($logLine);
        
        // Auch zur Konsole/Browser ausgeben für Live-Feedback
        // v1.3: Docker/nginx/PHP-FPM kompatibel
        if (class_exists('BotOutputHelper')) {
            BotOutputHelper::output($logLine);
        } else {
            echo $logLine . "\n";
            if (ob_get_level()) @ob_flush();
            @flush();
        }
        
        // Suggestion separat loggen wenn vorhanden
        if ($suggestion) {
            $this->writeToFile("   💡 Vorschlag: $suggestion");
        }
    }
    
    /**
     * Shortcut-Methoden für verschiedene Log-Level
     */
    public function success($message, $options = []) {
        $this->log(self::LEVEL_SUCCESS, $message, $options);
    }
    
    public function info($message, $options = []) {
        $this->log(self::LEVEL_INFO, $message, $options);
    }
    
    public function warning($message, $options = []) {
        $this->log(self::LEVEL_WARNING, $message, $options);
    }
    
    public function error($message, $options = []) {
        $this->log(self::LEVEL_ERROR, $message, $options);
    }
    
    public function critical($message, $options = []) {
        $this->log(self::LEVEL_CRITICAL, $message, $options);
    }
    
    /**
     * Speichert eine Metrik
     */
    public function metric($name, $value, $unit = '', $module = null, $context = null) {
        if (!$this->db || !$this->currentRunId) return;
        
        try {
            $stmt = $this->db->prepare('
                INSERT INTO bot_metrics (run_id, metric_name, metric_value, unit, module, context)
                VALUES (:run, :name, :value, :unit, :module, :context)
            ');
            
            $stmt->bindValue(':run', $this->currentRunId, SQLITE3_INTEGER);
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':value', $value, SQLITE3_FLOAT);
            $stmt->bindValue(':unit', $unit, SQLITE3_TEXT);
            $stmt->bindValue(':module', $module, SQLITE3_TEXT);
            $stmt->bindValue(':context', $context, SQLITE3_TEXT);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("BotLogger metric Error: " . $e->getMessage());
        }
    }
    
    /**
     * Speichert einen Verbesserungsvorschlag
     */
    public function suggestion($title, $description, $options = []) {
        if (!$this->db || !$this->currentRunId) return;
        
        $priority = $options['priority'] ?? 'medium';
        $category = $options['category'] ?? $this->botType;
        $files = $options['files'] ?? null;
        
        try {
            $stmt = $this->db->prepare('
                INSERT INTO bot_suggestions (run_id, priority, category, title, description, affected_files)
                VALUES (:run, :priority, :cat, :title, :desc, :files)
            ');
            
            $stmt->bindValue(':run', $this->currentRunId, SQLITE3_INTEGER);
            $stmt->bindValue(':priority', $priority, SQLITE3_TEXT);
            $stmt->bindValue(':cat', $category, SQLITE3_TEXT);
            $stmt->bindValue(':title', $title, SQLITE3_TEXT);
            $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
            $stmt->bindValue(':files', is_array($files) ? implode(', ', $files) : $files, SQLITE3_TEXT);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("BotLogger suggestion Error: " . $e->getMessage());
        }
    }
    
    /**
     * Hilfsmethoden
     */
    private function getLevelIcon($level) {
        $icons = [
            'success' => '✅',
            'info' => 'ℹ️',
            'warning' => '⚠️',
            'error' => '❌',
            'critical' => '🔴'
        ];
        return $icons[$level] ?? '•';
    }
    
    private function writeToFile($message) {
        $logFile = $this->logDir . '/' . $this->botType . '.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
    
    private function getRunCounts($runId) {
        $counts = ['total' => 0, 'success' => 0, 'info' => 0, 'warning' => 0, 'error' => 0, 'critical' => 0];
        
        if (!$this->db) return $counts;
        
        try {
            $result = $this->db->query("
                SELECT level, COUNT(*) as cnt 
                FROM bot_results 
                WHERE run_id = $runId 
                GROUP BY level
            ");
            
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $counts[$row['level']] = $row['cnt'];
                $counts['total'] += $row['cnt'];
            }
        } catch (Exception $e) {
            // Ignore
        }
        
        return $counts;
    }
    
    /**
     * Erstellt eine neue Datenbankverbindung für statische Methoden
     */
    private static function getDB() {
        $dbPath = __DIR__ . '/logs/bot_results.db';
        
        // Verzeichnis erstellen falls nicht vorhanden
        $logDir = __DIR__ . '/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        try {
            $db = new SQLite3($dbPath);
            $db->enableExceptions(true);
            
            // WAL-Modus für bessere Performance
            $db->exec('PRAGMA journal_mode = WAL');
            $db->exec('PRAGMA synchronous = NORMAL');
            $db->exec('PRAGMA busy_timeout = 5000');
            
            // Tabellen erstellen falls nicht vorhanden
            $db->exec('
                CREATE TABLE IF NOT EXISTS bot_runs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    bot_type TEXT NOT NULL,
                    bot_name TEXT,
                    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    finished_at DATETIME,
                    status TEXT DEFAULT "running",
                    total_tests INTEGER DEFAULT 0,
                    passed INTEGER DEFAULT 0,
                    warnings INTEGER DEFAULT 0,
                    errors INTEGER DEFAULT 0,
                    critical INTEGER DEFAULT 0,
                    summary TEXT,
                    config TEXT
                )
            ');
            
            $db->exec('
                CREATE TABLE IF NOT EXISTS bot_results (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    run_id INTEGER,
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    level TEXT NOT NULL,
                    category TEXT,
                    module TEXT,
                    test_name TEXT,
                    message TEXT NOT NULL,
                    details TEXT,
                    suggestion TEXT,
                    duration_ms INTEGER
                )
            ');
            
            $db->exec('
                CREATE TABLE IF NOT EXISTS bot_suggestions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    run_id INTEGER,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    priority TEXT DEFAULT "medium",
                    category TEXT,
                    title TEXT NOT NULL,
                    description TEXT,
                    affected_files TEXT,
                    status TEXT DEFAULT "open"
                )
            ');
            
            return $db;
            
        } catch (Exception $e) {
            error_log("BotLogger getDB Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Statische Methoden für Summary-Seite
     */
    public static function getAllRuns($limit = 50) {
        $db = self::getDB();
        if (!$db) return [];
        
        $results = [];
        
        try {
            $query = $db->query("
                SELECT * FROM bot_runs 
                ORDER BY started_at DESC 
                LIMIT $limit
            ");
            
            if ($query) {
                while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
                    $results[] = $row;
                }
            }
        } catch (Exception $e) {
            // Tabelle existiert noch nicht - kein Problem
        }
        
        return $results;
    }
    
    public static function getRunResults($runId) {
        $db = self::getDB();
        if (!$db) return [];
        
        $results = [];
        
        try {
            $stmt = $db->prepare("
                SELECT * FROM bot_results 
                WHERE run_id = :runId 
                ORDER BY timestamp ASC
            ");
            $stmt->bindValue(':runId', $runId, SQLITE3_INTEGER);
            $query = $stmt->execute();
            
            if ($query) {
                while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
                    $results[] = $row;
                }
            }
        } catch (Exception $e) {
            // Ignore
        }
        
        return $results;
    }
    
    public static function getSuggestions($status = 'open') {
        $db = self::getDB();
        if (!$db) return [];
        
        $results = [];
        
        try {
            $query = $db->query("
                SELECT s.*, r.bot_name 
                FROM bot_suggestions s
                LEFT JOIN bot_runs r ON s.run_id = r.id
                WHERE s.status = '$status'
                ORDER BY 
                    CASE s.priority 
                        WHEN 'critical' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        WHEN 'low' THEN 4 
                    END,
                    s.created_at DESC
            ");
            
            if ($query) {
                while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
                    $results[] = $row;
                }
            }
        } catch (Exception $e) {
            // Ignore
        }
        
        return $results;
    }
    
    /**
     * Suggestion als erledigt markieren
     */
    public static function resolveSuggestion($suggestionId) {
        $db = self::getDB();
        if (!$db) return false;
        
        try {
            $stmt = $db->prepare("UPDATE bot_suggestions SET status = 'resolved' WHERE id = :id");
            $stmt->bindValue(':id', $suggestionId, SQLITE3_INTEGER);
            return $stmt->execute() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Alle Suggestions als erledigt markieren
     */
    public static function resolveAllSuggestions() {
        $db = self::getDB();
        if (!$db) return false;
        
        try {
            return $db->exec("UPDATE bot_suggestions SET status = 'resolved' WHERE status = 'open'") !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Suggestion löschen
     */
    public static function deleteSuggestion($suggestionId) {
        $db = self::getDB();
        if (!$db) return false;
        
        try {
            $stmt = $db->prepare("DELETE FROM bot_suggestions WHERE id = :id");
            $stmt->bindValue(':id', $suggestionId, SQLITE3_INTEGER);
            return $stmt->execute() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function getStatistics() {
        $db = self::getDB();
        
        $stats = [
            'total_runs' => 0,
            'total_tests' => 0,
            'total_passed' => 0,
            'total_errors' => 0,
            'avg_success_rate' => 0,
            'by_bot_type' => [],
            'recent_errors' => []
        ];
        
        if (!$db) return $stats;
        
        try {
            // Gesamt-Runs
            $result = $db->querySingle("SELECT COUNT(*) FROM bot_runs");
            $stats['total_runs'] = $result ?? 0;
            
            // Gesamt-Tests
            $result = $db->querySingle("SELECT COALESCE(SUM(total_tests), 0) FROM bot_runs WHERE status = 'completed'");
            $stats['total_tests'] = $result ?? 0;
            
            // Passed
            $result = $db->querySingle("SELECT COALESCE(SUM(passed), 0) FROM bot_runs WHERE status = 'completed'");
            $stats['total_passed'] = $result ?? 0;
            
            // Errors
            $result = $db->querySingle("SELECT COALESCE(SUM(errors + critical), 0) FROM bot_runs WHERE status = 'completed'");
            $stats['total_errors'] = $result ?? 0;
            
            // Success Rate
            if ($stats['total_tests'] > 0) {
                $stats['avg_success_rate'] = round(($stats['total_passed'] / $stats['total_tests']) * 100, 1);
            }
            
            // By Bot Type
            $query = $db->query("
                SELECT bot_type, COUNT(*) as runs, COALESCE(SUM(passed), 0) as passed, COALESCE(SUM(errors), 0) as errors
                FROM bot_runs 
                WHERE status = 'completed'
                GROUP BY bot_type
            ");
            if ($query) {
                while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
                    $stats['by_bot_type'][$row['bot_type']] = $row;
                }
            }
            
            // Recent Errors
            $query = $db->query("
                SELECT r.*, br.bot_name
                FROM bot_results r
                LEFT JOIN bot_runs br ON r.run_id = br.id
                WHERE r.level IN ('error', 'critical')
                ORDER BY r.timestamp DESC
                LIMIT 10
            ");
            if ($query) {
                while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
                    $stats['recent_errors'][] = $row;
                }
            }
            
        } catch (Exception $e) {
            // Tabellen existieren noch nicht - kein Problem, gib leere Stats zurück
            error_log("BotLogger getStatistics: " . $e->getMessage());
        }
        
        return $stats;
    }
}
