<?php
/**
 * ============================================================================
 * sgiT Education - Load Test Bot
 * ============================================================================
 * 
 * Performance- und Last-Tests
 * - Simuliert mehrere gleichzeitige User
 * - Misst Response-Zeiten und Fehlerraten
 * - Findet Bottlenecks und Breaking Points
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
require_once dirname(__DIR__) . '/bot_logger.php';
require_once dirname(__DIR__) . '/bot_output_helper.php';
require_once dirname(__DIR__) . '/bot_health_check.php';  // BUG-030 FIX

class LoadTestBot {
    
    private $logger;
    private $config;
    private $stopFile;
    
    // v1.3: Automatische Docker-Erkennung
    private $baseUrl;
    
    private function detectBaseUrl() {
        // In Docker: /var/www/html existiert
        if (file_exists('/var/www/html')) {
            // Innerhalb Docker-Netzwerk: nginx Service-Name verwenden
            return 'http://nginx/';
        }
        // Lokal (XAMPP/Windows): localhost mit Port
        return 'http://localhost:8080/';
    }
    
    // v1.1: Zentrale Lernseite
    private $learningPage = 'adaptive_learning.php';
    
    // Alle 15 Module
    private $modules = [
        'mathematik', 'physik', 'chemie', 'biologie', 'erdkunde',
        'geschichte', 'kunst', 'musik', 'computer', 'programmieren',
        'bitcoin', 'steuern', 'englisch', 'lesen', 'wissenschaft'
    ];
    
    // Test-Szenarien
    private $scenarios = [
        'baseline' => [
            'users' => 5,
            'duration' => 20,
            'description' => 'Baseline (5 User)',
            'expected_avg' => 200
        ],
        'normal' => [
            'users' => 10,
            'duration' => 30,
            'description' => 'Normal Load (10 User)',
            'expected_avg' => 500
        ],
        'stress' => [
            'users' => 20,
            'duration' => 30,
            'description' => 'Stress Test (20 User)',
            'expected_avg' => 1000
        ],
        'breaking' => [
            'users' => 50,
            'duration' => 20,
            'description' => 'Breaking Point (50 User)',
            'expected_avg' => 3000
        ]
    ];
    
    // Ergebnisse
    private $results = [];
    
    // Standard-Konfiguration
    private $defaultConfig = [
        'timeout' => 15,
        'pauseBetweenWaves' => 500000, // 500ms in Microseconds
        'verbose' => true
    ];
    
    /**
     * Konstruktor
     */
    public function __construct($config = []) {
        // v1.3: Automatische Docker-Erkennung
        $this->baseUrl = $this->detectBaseUrl();
        
        $this->config = array_merge($this->defaultConfig, $config);
        $this->logger = new BotLogger(BotLogger::CAT_LOAD);
        $this->stopFile = dirname(__DIR__) . '/logs/STOP_LOAD_BOT';
    }
    
    /**
     * Hauptmethode - Führt alle Szenarien aus
     */
    public function run($scenarioName = 'all') {
        if (file_exists($this->stopFile)) {
            unlink($this->stopFile);
        }
        
        $this->logger->startRun('Load Test Bot v1.1', $this->config);
        $startTime = microtime(true);
        
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("⚡ LOAD TEST BOT GESTARTET");
        $this->logger->info("   Base URL: {$this->baseUrl}");
        $this->logger->info("   Module: " . count($this->modules));
        $this->logger->info("═══════════════════════════════════════════════════════");
        
        // ================================================================
        // BUG-030 FIX: Health-Check mit Retry-Logik vor Tests
        // ================================================================
        $this->logger->info("");
        $this->logger->info("🏥 Pre-Flight Health-Check...");
        
        $healthResult = BotHealthCheck::waitForServer($this->baseUrl, function($msg) {
            $this->logger->info("   " . $msg);
        });
        
        if ($healthResult['status'] === BotHealthCheck::STATUS_OFFLINE) {
            $this->logger->error("❌ Server nicht erreichbar!");
            foreach (BotHealthCheck::getHelpMessage(BotHealthCheck::STATUS_OFFLINE, $this->baseUrl) as $line) {
                $this->logger->info($line);
            }
            $this->logger->endRun("ABGEBROCHEN - Server offline");
            return ['status' => 'aborted', 'reason' => 'server_offline'];
        }
        
        $this->logger->success("✅ Health-Check bestanden ({$healthResult['responseTime']}ms)");
        $this->logger->info("");
        
        if ($scenarioName === 'all') {
            foreach ($this->scenarios as $name => $config) {
                if ($this->shouldStop()) break;
                $this->runScenario($name, $config);
                sleep(2); // Pause zwischen Szenarien
            }
        } else if (isset($this->scenarios[$scenarioName])) {
            $this->runScenario($scenarioName, $this->scenarios[$scenarioName]);
        } else {
            $this->logger->error("Unbekanntes Szenario: $scenarioName");
        }
        
        // Zusammenfassung
        $totalTime = round((microtime(true) - $startTime), 2);
        $this->generateSummary($totalTime);
        
        $this->logger->endRun("Load Test abgeschlossen in {$totalTime}s");
        
        return $this->results;
    }
    
    /**
     * Führt ein einzelnes Szenario aus
     */
    private function runScenario($name, $config) {
        $this->logger->info("");
        $this->logger->info("╔═══════════════════════════════════════════════════════╗");
        $this->logger->info("║  SZENARIO: {$config['description']}");
        $this->logger->info("║  User: {$config['users']} | Dauer: {$config['duration']}s");
        $this->logger->info("╚═══════════════════════════════════════════════════════╝");
        
        $scenarioResults = [
            'name' => $name,
            'config' => $config,
            'requests' => 0,
            'errors' => 0,
            'responseTimes' => [],
            'byModule' => [],
            'startTime' => microtime(true)
        ];
        
        $endTime = time() + $config['duration'];
        $waveCount = 0;
        
        // Multi-cURL initialisieren
        $multiHandle = curl_multi_init();
        
        while (time() < $endTime && !$this->shouldStop()) {
            $waveCount++;
            $handles = [];
            
            // Erstelle N parallele Requests
            for ($i = 0; $i < $config['users']; $i++) {
                $module = $this->modules[array_rand($this->modules)];
                // v1.1: Neue URL-Struktur!
                $url = $this->baseUrl . $this->learningPage . '?module=' . urlencode($module);
                
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $this->config['timeout'],
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_USERAGENT => 'sgiT LoadTestBot/1.0 (User ' . ($i + 1) . ')'
                ]);
                
                curl_multi_add_handle($multiHandle, $ch);
                $handles[] = [
                    'handle' => $ch,
                    'module' => $module,
                    'start' => microtime(true)
                ];
            }
            
            // Führe alle Requests parallel aus
            $running = null;
            do {
                $status = curl_multi_exec($multiHandle, $running);
                if ($running) {
                    curl_multi_select($multiHandle, 0.1);
                }
            } while ($running > 0 && $status === CURLM_OK);
            
            // Sammle Ergebnisse
            foreach ($handles as $h) {
                $responseTime = round((microtime(true) - $h['start']) * 1000);
                $httpCode = curl_getinfo($h['handle'], CURLINFO_HTTP_CODE);
                $error = curl_error($h['handle']);
                
                $scenarioResults['requests']++;
                $scenarioResults['responseTimes'][] = $responseTime;
                
                // Pro Modul tracken
                if (!isset($scenarioResults['byModule'][$h['module']])) {
                    $scenarioResults['byModule'][$h['module']] = [
                        'times' => [],
                        'errors' => 0
                    ];
                }
                $scenarioResults['byModule'][$h['module']]['times'][] = $responseTime;
                
                if ($httpCode !== 200 || !empty($error)) {
                    $scenarioResults['errors']++;
                    $scenarioResults['byModule'][$h['module']]['errors']++;
                }
                
                curl_multi_remove_handle($multiHandle, $h['handle']);
                curl_close($h['handle']);
            }
            
            // Fortschritt anzeigen (alle 5 Wellen)
            if ($waveCount % 5 === 0) {
                $elapsed = round(time() - ($endTime - $config['duration']));
                $avgSoFar = count($scenarioResults['responseTimes']) > 0 
                    ? round(array_sum($scenarioResults['responseTimes']) / count($scenarioResults['responseTimes']))
                    : 0;
                $this->logger->info("   ⏳ {$elapsed}s | Requests: {$scenarioResults['requests']} | Avg: {$avgSoFar}ms");
            }
            
            // Pause zwischen Wellen
            usleep($this->config['pauseBetweenWaves']);
        }
        
        curl_multi_close($multiHandle);
        
        $scenarioResults['endTime'] = microtime(true);
        $scenarioResults['duration'] = round($scenarioResults['endTime'] - $scenarioResults['startTime'], 2);
        
        // Ergebnisse analysieren
        $this->analyzeScenarioResults($name, $scenarioResults);
        
        $this->results[$name] = $scenarioResults;
    }
    
    /**
     * Analysiert die Ergebnisse eines Szenarios
     */
    private function analyzeScenarioResults($name, &$results) {
        if (empty($results['responseTimes'])) {
            $this->logger->warning("   Keine Requests durchgeführt!");
            return;
        }
        
        $times = $results['responseTimes'];
        sort($times);
        
        // Statistiken berechnen
        $stats = [
            'count' => count($times),
            'avg' => round(array_sum($times) / count($times)),
            'min' => min($times),
            'max' => max($times),
            'p50' => $times[floor(count($times) * 0.50)],
            'p95' => $times[floor(count($times) * 0.95)],
            'p99' => $times[floor(count($times) * 0.99)],
            'errorRate' => round(($results['errors'] / $results['requests']) * 100, 2),
            'throughput' => round($results['requests'] / $results['duration'], 1)
        ];
        
        $results['stats'] = $stats;
        
        // Ergebnisse loggen
        $this->logger->info("");
        $this->logger->info("📊 Ergebnisse für {$this->scenarios[$name]['description']}:");
        $this->logger->info("   Requests:     {$stats['count']}");
        $this->logger->info("   Durchsatz:    {$stats['throughput']} req/s");
        $this->logger->info("   Errors:       {$results['errors']} ({$stats['errorRate']}%)");
        $this->logger->info("");
        $this->logger->info("   Response Times:");
        $this->logger->info("   ├─ Min:       {$stats['min']}ms");
        $this->logger->info("   ├─ Avg:       {$stats['avg']}ms");
        $this->logger->info("   ├─ P50:       {$stats['p50']}ms");
        $this->logger->info("   ├─ P95:       {$stats['p95']}ms");
        $this->logger->info("   ├─ P99:       {$stats['p99']}ms");
        $this->logger->info("   └─ Max:       {$stats['max']}ms");
        
        // Bewertung
        $expected = $this->scenarios[$name]['expected_avg'];
        if ($stats['avg'] <= $expected) {
            $this->logger->success("   ✅ Performance OK (Avg unter {$expected}ms)");
        } elseif ($stats['avg'] <= $expected * 1.5) {
            $this->logger->warning("   ⚠️ Performance akzeptabel (Avg {$stats['avg']}ms > {$expected}ms)");
        } else {
            $this->logger->error("   ❌ Performance kritisch (Avg {$stats['avg']}ms >> {$expected}ms)");
        }
        
        // Fehlerrate bewerten
        if ($stats['errorRate'] > 5) {
            $this->logger->error("   ❌ Hohe Fehlerrate: {$stats['errorRate']}%");
            $this->logger->suggestion(
                "Hohe Fehlerrate bei $name",
                "Prüfe Timeouts, DB-Locks und Ressourcen-Limits",
                ['priority' => 'high', 'category' => 'performance']
            );
        }
        
        // Langsamste Module identifizieren
        $this->identifySlowestModules($results);
        
        // Metriken speichern
        $this->logger->metric("avg_response_$name", $stats['avg'], 'ms');
        $this->logger->metric("p95_response_$name", $stats['p95'], 'ms');
        $this->logger->metric("error_rate_$name", $stats['errorRate'], '%');
        $this->logger->metric("throughput_$name", $stats['throughput'], 'req/s');
    }
    
    /**
     * Identifiziert die langsamsten Module
     */
    private function identifySlowestModules(&$results) {
        $moduleAvgs = [];
        
        foreach ($results['byModule'] as $module => $data) {
            if (!empty($data['times'])) {
                $moduleAvgs[$module] = round(array_sum($data['times']) / count($data['times']));
            }
        }
        
        // Sortieren (langsamstes zuerst)
        arsort($moduleAvgs);
        
        // Top 3 langsamste Module
        $slowest = array_slice($moduleAvgs, 0, 3, true);
        
        if (!empty($slowest)) {
            $this->logger->info("");
            $this->logger->info("   🐢 Langsamste Module:");
            $rank = 1;
            foreach ($slowest as $module => $avg) {
                $this->logger->info("      $rank. $module: {$avg}ms avg");
                $rank++;
            }
        }
        
        $results['slowestModules'] = $slowest;
    }
    
    /**
     * Generiert Zusammenfassung über alle Szenarien
     */
    private function generateSummary($totalTime) {
        $this->logger->info("");
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("📈 GESAMTZUSAMMENFASSUNG");
        $this->logger->info("═══════════════════════════════════════════════════════");
        
        // Tabelle mit allen Szenarien
        $this->logger->info("");
        $this->logger->info("   Szenario        | Users | Avg     | P95     | Errors");
        $this->logger->info("   ─────────────────────────────────────────────────────");
        
        foreach ($this->results as $name => $result) {
            if (isset($result['stats'])) {
                $s = $result['stats'];
                $config = $result['config'];
                $padName = str_pad($config['description'], 16);
                $padUsers = str_pad($config['users'], 5);
                $padAvg = str_pad($s['avg'] . 'ms', 7);
                $padP95 = str_pad($s['p95'] . 'ms', 7);
                $padErr = $s['errorRate'] . '%';
                
                $this->logger->info("   $padName | $padUsers | $padAvg | $padP95 | $padErr");
            }
        }
        
        // Bottleneck-Analyse
        $this->logger->info("");
        $this->logger->info("   🔍 Bottleneck-Analyse:");
        
        // Finde Breaking Point
        $breakingPoint = $this->findBreakingPoint();
        if ($breakingPoint) {
            $this->logger->info("   └─ Empfohlene Max-User: ~{$breakingPoint}");
        }
        
        // Hauptprobleme
        $this->identifyBottlenecks();
        
        $this->logger->info("");
        $this->logger->info("   ⏱️ Gesamt-Laufzeit: {$totalTime}s");
        $this->logger->info("═══════════════════════════════════════════════════════");
    }
    
    /**
     * Findet den Breaking Point
     */
    private function findBreakingPoint() {
        $lastGood = 0;
        
        foreach ($this->results as $name => $result) {
            if (!isset($result['stats'])) continue;
            
            $errorRate = $result['stats']['errorRate'];
            $users = $result['config']['users'];
            
            if ($errorRate < 5) {
                $lastGood = $users;
            } else {
                break;
            }
        }
        
        return $lastGood > 0 ? $lastGood : null;
    }
    
    /**
     * Identifiziert Bottlenecks
     */
    private function identifyBottlenecks() {
        $bottlenecks = [];
        
        // Prüfe auf SQLite-Lock-Probleme
        if (isset($this->results['stress']) && $this->results['stress']['stats']['errorRate'] > 5) {
            $bottlenecks[] = 'SQLite DB-Lock (WAL-Modus aktivieren)';
        }
        
        // Prüfe auf hohe Response-Zeiten
        foreach ($this->results as $name => $result) {
            if (!isset($result['stats'])) continue;
            
            if ($result['stats']['p99'] > 5000) {
                $bottlenecks[] = "Sehr hohe P99-Zeiten bei $name";
            }
        }
        
        // Prüfe auf Module-Probleme
        $allSlowest = [];
        foreach ($this->results as $result) {
            if (isset($result['slowestModules'])) {
                foreach ($result['slowestModules'] as $module => $avg) {
                    if (!isset($allSlowest[$module])) {
                        $allSlowest[$module] = 0;
                    }
                    $allSlowest[$module]++;
                }
            }
        }
        
        // Module die mehrfach langsam waren
        foreach ($allSlowest as $module => $count) {
            if ($count >= 2) {
                $bottlenecks[] = "Modul '$module' konstant langsam";
            }
        }
        
        if (!empty($bottlenecks)) {
            foreach ($bottlenecks as $b) {
                $this->logger->warning("      ⚠️ $b");
                $this->logger->suggestion($b, 'Performance-Optimierung erforderlich', [
                    'priority' => 'high',
                    'category' => 'performance'
                ]);
            }
        } else {
            $this->logger->success("      ✅ Keine kritischen Bottlenecks gefunden");
        }
    }
    
    /**
     * Prüft ob Bot gestoppt werden soll
     */
    private function shouldStop() {
        return file_exists($this->stopFile) || connection_aborted();
    }
    
    /**
     * Stoppt den Bot
     */
    public static function stop() {
        $stopFile = dirname(__DIR__) . '/logs/STOP_LOAD_BOT';
        file_put_contents($stopFile, date('Y-m-d H:i:s'));
        return true;
    }
    
    /**
     * Quick Test - Nur Baseline
     */
    public function quickTest() {
        return $this->run('baseline');
    }
    
    /**
     * Stress Test
     */
    public function stressTest() {
        return $this->run('stress');
    }
}

// ============================================================
// STANDALONE AUSFÜHRUNG MIT UI
// ============================================================
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    
    set_time_limit(600); // 10 Minuten max
    
    if (isset($_GET['stop'])) {
        LoadTestBot::stop();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?stopped=1');
        exit;
    }
    
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>⚡ Load Test Bot - sgiT Education</title>
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
            max-width: 900px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 20px; 
            padding: 40px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
        }
        h1 { 
            color: #1A3503; 
            border-bottom: 3px solid #ffc107; 
            padding-bottom: 15px; 
        }
        .badge {
            display: inline-block;
            background: #ffc107;
            color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        button {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        button:hover { background: #e0a800; }
        button.secondary { background: #6c757d; color: white; }
        button.stop { background: #dc3545; color: white; }
        .log-output {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .scenario-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .scenario-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .scenario-card .users { font-size: 24px; font-weight: bold; color: #1A3503; }
        .scenario-card .label { font-size: 12px; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <h1>⚡ Load Test Bot <span class="badge">v1.3</span></h1>
    
    <div class="info-box">
        <h4>📊 Test-Szenarien</h4>
        <div class="scenario-grid">
            <div class="scenario-card">
                <div class="users">5</div>
                <div class="label">Baseline</div>
            </div>
            <div class="scenario-card">
                <div class="users">10</div>
                <div class="label">Normal</div>
            </div>
            <div class="scenario-card">
                <div class="users">20</div>
                <div class="label">Stress</div>
            </div>
            <div class="scenario-card">
                <div class="users">50</div>
                <div class="label">Breaking</div>
            </div>
        </div>
        <p>Misst Response-Zeiten, Durchsatz und Fehlerraten bei unterschiedlicher Last.</p>
    </div>
    
    <form method="post">
        <button type="submit" name="run_all">🚀 Alle Szenarien</button>
        <button type="submit" name="run_baseline" class="secondary">🐢 Nur Baseline</button>
        <button type="submit" name="run_stress" class="secondary">💪 Nur Stress</button>
        <a href="?stop=1"><button type="button" class="stop">⏹️ Stoppen</button></a>
        <a href="../bot_summary.php"><button type="button" class="secondary">📊 Dashboard</button></a>
    </form>
    
    <?php
    if (isset($_POST['run_all']) || isset($_POST['run_baseline']) || isset($_POST['run_stress'])) {
        echo '<div style="margin-top: 30px;">';
        echo '<h3>🔄 Test läuft...</h3>';
        echo '<div class="log-output" id="live-log">';
        
        // Docker/nginx/PHP-FPM Live-Output Fix
        BotOutputHelper::init();
        
        $bot = new LoadTestBot();
        
        if (isset($_POST['run_baseline'])) {
            $results = $bot->quickTest();
        } elseif (isset($_POST['run_stress'])) {
            $results = $bot->stressTest();
        } else {
            $results = $bot->run();
        }
        
        echo '</div>';
        echo '</div>';
    }
    ?>
    
</div>
</body>
</html>
<?php 
}

// CLI-Modus
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['scenario:', 'help']);
    
    if (isset($options['help'])) {
        echo "\nLoad Test Bot - Performance-Tests\n";
        echo "==================================\n\n";
        echo "Optionen:\n";
        echo "  --scenario=NAME   Einzelnes Szenario (baseline, normal, stress, breaking)\n";
        echo "  (ohne Option)     Alle Szenarien durchführen\n\n";
        exit(0);
    }
    
    $bot = new LoadTestBot();
    
    if (isset($options['scenario'])) {
        $results = $bot->run($options['scenario']);
    } else {
        $results = $bot->run();
    }
}
?>
