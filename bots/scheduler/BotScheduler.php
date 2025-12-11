<?php
/**
 * ============================================================================
 * sgiT Education - Bot Auto-Scheduler v1.0 (TODO-009)
 * ============================================================================
 * 
 * Automatische zeitgesteuerte Ausführung von Test-Bots
 * 
 * Features:
 * - Cron-Style Scheduling (täglich, wöchentlich, Intervall)
 * - Job-Queue Management
 * - Automatische Reports
 * - Fehler-Tracking
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 11.12.2025
 * ============================================================================
 */

class BotScheduler {
    
    private string $configFile;
    private string $logFile;
    private array $config;
    private array $availableBots;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->configFile = __DIR__ . '/scheduler_config.json';
        $this->logFile = dirname(__DIR__) . '/logs/scheduler.log';
        $this->loadConfig();
        $this->initAvailableBots();
    }

    
    /**
     * Verfügbare Bots initialisieren
     */
    private function initAvailableBots(): void {
        $this->availableBots = [
            'ai_generator' => [
                'file' => dirname(__DIR__) . '/tests/AIGeneratorBot.php',
                'class' => 'AIGeneratorBot',
                'name' => 'AI Generator Bot',
                'icon' => '🤖',
                'description' => 'Generiert AI-Fragen für alle Module'
            ],
            'function_test' => [
                'file' => dirname(__DIR__) . '/tests/FunctionTestBot.php',
                'class' => 'FunctionTestBot',
                'name' => 'Function Test Bot',
                'icon' => '🧪',
                'description' => 'Testet alle 21 Modul-Funktionen'
            ],
            'security' => [
                'file' => dirname(__DIR__) . '/tests/SecurityBot.php',
                'class' => 'SecurityBot',
                'name' => 'Security Bot',
                'icon' => '🔒',
                'description' => 'Prüft Sicherheitslücken'
            ],
            'load_test' => [
                'file' => dirname(__DIR__) . '/tests/LoadTestBot.php',
                'class' => 'LoadTestBot',
                'name' => 'Load Test Bot',
                'icon' => '⚡',
                'description' => 'Simuliert mehrere gleichzeitige User'
            ],
            'dependency' => [
                'file' => dirname(__DIR__) . '/tests/DependencyCheckBot.php',
                'class' => 'DependencyCheckBot',
                'name' => 'Dependency Check Bot',
                'icon' => '🔍',
                'description' => 'Findet toten Code und Abhängigkeiten'
            ]
        ];
    }

    
    /**
     * Konfiguration laden
     */
    private function loadConfig(): void {
        if (file_exists($this->configFile)) {
            $json = file_get_contents($this->configFile);
            $this->config = json_decode($json, true) ?? $this->getDefaultConfig();
        } else {
            $this->config = $this->getDefaultConfig();
            $this->saveConfig();
        }
    }
    
    /**
     * Konfiguration speichern
     */
    public function saveConfig(): bool {
        $json = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->configFile, $json) !== false;
    }
    
    /**
     * Standard-Konfiguration
     */
    private function getDefaultConfig(): array {
        return [
            'version' => '1.0',
            'created_at' => date('Y-m-d H:i:s'),
            'jobs' => [
                [
                    'id' => 'security-daily',
                    'bot' => 'security',
                    'schedule' => 'daily',
                    'time' => '03:00',
                    'enabled' => true,
                    'notify_on_error' => true,
                    'last_run' => null,
                    'next_run' => null,
                    'last_status' => null
                ],
                [
                    'id' => 'function-weekly',
                    'bot' => 'function_test',
                    'schedule' => 'weekly',
                    'day' => 'sunday',
                    'time' => '04:00',
                    'enabled' => true,
                    'notify_on_error' => true,
                    'last_run' => null,
                    'next_run' => null,
                    'last_status' => null
                ]
            ],
            'settings' => [
                'timezone' => 'Europe/Berlin',
                'max_concurrent_jobs' => 1,
                'job_timeout_minutes' => 30,
                'keep_logs_days' => 30
            ]
        ];
    }

    
    /**
     * Alle Jobs abrufen
     */
    public function getJobs(): array {
        return $this->config['jobs'] ?? [];
    }
    
    /**
     * Verfügbare Bots abrufen
     */
    public function getAvailableBots(): array {
        return $this->availableBots;
    }
    
    /**
     * Job hinzufügen
     */
    public function addJob(array $job): bool {
        // ID generieren falls nicht vorhanden
        if (empty($job['id'])) {
            $job['id'] = $job['bot'] . '-' . time();
        }
        
        // Pflichtfelder prüfen
        if (empty($job['bot']) || !isset($this->availableBots[$job['bot']])) {
            return false;
        }
        
        // Defaults setzen
        $job = array_merge([
            'schedule' => 'daily',
            'time' => '03:00',
            'enabled' => true,
            'notify_on_error' => false,
            'last_run' => null,
            'next_run' => null,
            'last_status' => null
        ], $job);
        
        // Nächste Ausführung berechnen
        $job['next_run'] = $this->calculateNextRun($job);
        
        $this->config['jobs'][] = $job;
        return $this->saveConfig();
    }
    
    /**
     * Job entfernen
     */
    public function removeJob(string $jobId): bool {
        foreach ($this->config['jobs'] as $key => $job) {
            if ($job['id'] === $jobId) {
                unset($this->config['jobs'][$key]);
                $this->config['jobs'] = array_values($this->config['jobs']);
                return $this->saveConfig();
            }
        }
        return false;
    }
    
    /**
     * Job aktivieren/deaktivieren
     */
    public function toggleJob(string $jobId, bool $enabled): bool {
        foreach ($this->config['jobs'] as &$job) {
            if ($job['id'] === $jobId) {
                $job['enabled'] = $enabled;
                if ($enabled) {
                    $job['next_run'] = $this->calculateNextRun($job);
                }
                return $this->saveConfig();
            }
        }
        return false;
    }

    
    /**
     * Nächste Ausführungszeit berechnen
     */
    public function calculateNextRun(array $job): string {
        $tz = new DateTimeZone($this->config['settings']['timezone'] ?? 'Europe/Berlin');
        $now = new DateTime('now', $tz);
        
        switch ($job['schedule']) {
            case 'hourly':
                $next = clone $now;
                $next->modify('+1 hour');
                $next->setTime((int)$next->format('H'), 0, 0);
                break;
                
            case 'daily':
                $time = explode(':', $job['time'] ?? '03:00');
                $next = clone $now;
                $next->setTime((int)$time[0], (int)($time[1] ?? 0), 0);
                if ($next <= $now) {
                    $next->modify('+1 day');
                }
                break;
                
            case 'weekly':
                $day = $job['day'] ?? 'sunday';
                $time = explode(':', $job['time'] ?? '03:00');
                $next = clone $now;
                $next->modify("next {$day}");
                $next->setTime((int)$time[0], (int)($time[1] ?? 0), 0);
                break;
                
            case 'interval':
                $hours = (int)($job['interval_hours'] ?? 6);
                $next = clone $now;
                $next->modify("+{$hours} hours");
                break;
                
            default:
                $next = clone $now;
                $next->modify('+1 day');
        }
        
        return $next->format('Y-m-d H:i:s');
    }
    
    /**
     * Fällige Jobs abrufen
     */
    public function getDueJobs(): array {
        $dueJobs = [];
        $tz = new DateTimeZone($this->config['settings']['timezone'] ?? 'Europe/Berlin');
        $now = new DateTime('now', $tz);
        
        foreach ($this->config['jobs'] as $job) {
            if (!$job['enabled']) continue;
            if (empty($job['next_run'])) continue;
            
            $nextRun = new DateTime($job['next_run'], $tz);
            if ($nextRun <= $now) {
                $dueJobs[] = $job;
            }
        }
        
        return $dueJobs;
    }

    
    /**
     * Einen Bot ausführen
     */
    public function runBot(string $botKey, string $mode = 'quick'): array {
        if (!isset($this->availableBots[$botKey])) {
            return ['success' => false, 'error' => "Bot '$botKey' nicht gefunden"];
        }
        
        $botDef = $this->availableBots[$botKey];
        $startTime = microtime(true);
        
        $this->log("INFO", "Starte Bot: {$botDef['name']} (Mode: $mode)");
        
        try {
            // Bot-Datei laden
            if (!file_exists($botDef['file'])) {
                throw new Exception("Bot-Datei nicht gefunden: {$botDef['file']}");
            }
            
            require_once $botDef['file'];
            
            // Bot instanziieren
            $className = $botDef['class'];
            if (!class_exists($className)) {
                throw new Exception("Bot-Klasse '$className' nicht gefunden");
            }
            
            $bot = new $className();
            
            // Bot ausführen
            $result = match($mode) {
                'quick' => method_exists($bot, 'quickTest') ? $bot->quickTest() : $bot->run(),
                'full' => method_exists($bot, 'fullTest') ? $bot->fullTest() : $bot->run(),
                default => $bot->run()
            };
            
            $duration = round((microtime(true) - $startTime) * 1000);
            
            $this->log("SUCCESS", "Bot {$botDef['name']} fertig in {$duration}ms");
            
            return [
                'success' => true,
                'bot' => $botKey,
                'name' => $botDef['name'],
                'duration_ms' => $duration,
                'result' => $result
            ];
            
        } catch (Exception $e) {
            $this->log("ERROR", "Bot {$botDef['name']} Fehler: " . $e->getMessage());
            
            return [
                'success' => false,
                'bot' => $botKey,
                'name' => $botDef['name'],
                'error' => $e->getMessage()
            ];
        }
    }

    
    /**
     * Alle fälligen Jobs ausführen
     */
    public function runDueJobs(): array {
        $dueJobs = $this->getDueJobs();
        $results = [];
        
        if (empty($dueJobs)) {
            $this->log("INFO", "Keine fälligen Jobs");
            return $results;
        }
        
        $this->log("INFO", count($dueJobs) . " fällige Jobs gefunden");
        
        foreach ($dueJobs as $job) {
            $result = $this->runBot($job['bot']);
            $results[] = $result;
            
            // Job aktualisieren
            $this->updateJobAfterRun($job['id'], $result);
        }
        
        return $results;
    }
    
    /**
     * Job nach Ausführung aktualisieren
     */
    private function updateJobAfterRun(string $jobId, array $result): void {
        foreach ($this->config['jobs'] as &$job) {
            if ($job['id'] === $jobId) {
                $job['last_run'] = date('Y-m-d H:i:s');
                $job['last_status'] = $result['success'] ? 'success' : 'error';
                $job['last_error'] = $result['error'] ?? null;
                $job['next_run'] = $this->calculateNextRun($job);
                break;
            }
        }
        $this->saveConfig();
    }
    
    /**
     * Logging
     */
    private function log(string $level, string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[$timestamp] [$level] $message\n";
        
        // In Datei schreiben
        file_put_contents($this->logFile, $line, FILE_APPEND);
        
        // Bei CLI auch auf Console ausgeben
        if (php_sapi_name() === 'cli') {
            echo $line;
        }
    }
    
    /**
     * Log-Datei lesen
     */
    public function getLog(int $lines = 50): array {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $content = file($this->logFile, FILE_IGNORE_NEW_LINES);
        return array_slice($content, -$lines);
    }
    
    /**
     * Scheduler-Status abrufen
     */
    public function getStatus(): array {
        $jobs = $this->getJobs();
        $dueJobs = $this->getDueJobs();
        
        return [
            'total_jobs' => count($jobs),
            'enabled_jobs' => count(array_filter($jobs, fn($j) => $j['enabled'])),
            'due_jobs' => count($dueJobs),
            'settings' => $this->config['settings'],
            'last_check' => date('Y-m-d H:i:s')
        ];
    }
}
