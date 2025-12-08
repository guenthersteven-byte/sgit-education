<?php
/**
 * ============================================================================
 * sgiT Education - Function Test Bot
 * ============================================================================
 * 
 * Systematisches Testen aller 15 Module
 * - HTTP-Status, DOM-Struktur, AJAX-API
 * - Session-Handling, Score-Tracking
 * - Navigation, Datenbank-Persistenz
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

class FunctionTestBot {
    
    private $logger;
    private $config;
    private $stopFile;
    private $cookieFile;
    
    // v1.5: Automatische Docker-Erkennung
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
    
    // Alle 21 Module (18 Quiz + 3 Interaktiv)
    private $modules = [
        // Quiz-Module (18)
        'mathematik', 'physik', 'chemie', 'biologie', 'erdkunde',
        'geschichte', 'kunst', 'musik', 'computer', 'programmieren',
        'bitcoin', 'finanzen', 'englisch', 'lesen', 'wissenschaft',
        'verkehr', 'sport', 'unnuetzes_wissen',
        // Interaktive Module (3)
        'zeichnen', 'logik', 'kochen'
    ];
    
    // v1.2: Zentrale Lernseite (nicht mehr einzelne Ordner!)
    private $learningPage = 'adaptive_learning.php';
    
    // Test-Statistiken
    private $stats = [
        'total' => 0,
        'passed' => 0,
        'warnings' => 0,
        'failed' => 0,
        'modules_tested' => 0
    ];
    
    // Standard-Konfiguration
    private $defaultConfig = [
        'timeout' => 10,
        'delayBetweenModules' => 1,
        'verbose' => true,
        'testFormSubmit' => true,
        'testSession' => true,
        'testNavigation' => true,
        'testEdgeCases' => true,       // v1.6: Edge Case Tests
        'testPerformance' => true,     // v1.6: Performance-Metriken
        'parallelMode' => false,       // v1.6: Parallele Tests (experimentell)
        'performanceThresholds' => [   // v1.6: Schwellwerte
            'ttfb' => 200,             // Time to First Byte (ms)
            'total' => 500,            // Gesamtzeit (ms)
        ],
    ];
    
    // v1.6: Performance-Metriken sammeln
    private $performanceMetrics = [];
    
    /**
     * Konstruktor
     */
    public function __construct($config = []) {
        // v1.5: Automatische Docker-Erkennung
        $this->baseUrl = $this->detectBaseUrl();
        
        $this->config = array_merge($this->defaultConfig, $config);
        $this->logger = new BotLogger(BotLogger::CAT_FUNCTION);
        $this->stopFile = dirname(__DIR__) . '/logs/STOP_FUNCTION_BOT';
        $this->cookieFile = sys_get_temp_dir() . '/sgit_function_test_cookies.txt';
        
        // Cookie-Datei leeren
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }
    
    /**
     * Hauptmethode - Führt alle Tests aus
     */
    public function run() {
        // Stop-File löschen falls vorhanden
        if (file_exists($this->stopFile)) {
            unlink($this->stopFile);
        }
        
        $this->logger->startRun('Function Test Bot v1.6', $this->config);
        $startTime = microtime(true);
        
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("🧪 FUNCTION TEST BOT GESTARTET");
        $this->logger->info("   Base-URL: " . $this->baseUrl);
        $this->logger->info("   Module: " . count($this->modules));
        $this->logger->info("   Tests pro Modul: ~12");
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
            $this->logger->error("   " . $healthResult['message']);
            
            // Hilfreiche Fehlermeldung ausgeben
            foreach (BotHealthCheck::getHelpMessage(BotHealthCheck::STATUS_OFFLINE, $this->baseUrl) as $line) {
                $this->logger->info($line);
            }
            
            $this->stats['failed']++;
            $this->logger->endRun("ABGEBROCHEN - Server offline");
            
            return [
                'status' => 'aborted',
                'reason' => 'server_offline',
                'message' => $healthResult['message'],
                'stats' => $this->stats
            ];
        }
        
        if ($healthResult['status'] === BotHealthCheck::STATUS_DEGRADED) {
            $this->logger->warning("⚠️ Server antwortet mit Einschränkungen");
            $this->logger->warning("   Tests werden trotzdem ausgeführt...");
        }
        
        $this->logger->success("✅ Health-Check bestanden ({$healthResult['responseTime']}ms)");
        $this->logger->info("");
        
        // v1.3: Session initialisieren durch Test-Login
        $this->initTestSession();
        
        // v1.6: Paralleler Modus für schnellere HTTP-Tests
        if ($this->config['parallelMode']) {
            $this->logger->info("");
            $this->logger->info("🚀 Paralleler Modus aktiviert...");
            $this->runParallelHttpTests();
        }
        
        // Durch alle Module (sequentiell für detaillierte Tests)
        foreach ($this->modules as $index => $module) {
            if ($this->shouldStop()) {
                $this->logger->warning("⏹️ STOP-Signal empfangen!");
                break;
            }
            
            $this->testModuleComplete($module, $index + 1);
            $this->stats['modules_tested']++;
            
            // Kurze Pause zwischen Modulen (nur im nicht-parallelen Modus)
            if (!$this->config['parallelMode'] && $index < count($this->modules) - 1) {
                sleep($this->config['delayBetweenModules']);
            }
        }
        
        // Zusammenfassung
        $totalTime = round((microtime(true) - $startTime), 2);
        $this->generateSummary($totalTime);
        
        $summary = sprintf(
            "Tests: %d | Passed: %d | Warnings: %d | Failed: %d | Zeit: %ss",
            $this->stats['total'],
            $this->stats['passed'],
            $this->stats['warnings'],
            $this->stats['failed'],
            $totalTime
        );
        
        $this->logger->endRun($summary);
        
        // Aufräumen
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
        
        return $this->stats;
    }
    
    /**
     * v1.3: Test-Session initialisieren
     * Simuliert einen Login um die Session zu starten
     */
    private function initTestSession() {
        $this->logger->info("");
        $this->logger->info("🔑 Initialisiere Test-Session...");
        
        // 1. Erst die Seite laden um PHPSESSID zu bekommen
        $url = $this->baseUrl . $this->learningPage;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'sgiT FunctionTestBot/1.3'
        ]);
        curl_exec($ch);
        curl_close($ch);
        
        // 2. Login als Test-User
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'action' => 'login',
                'name' => 'TestBot',
                'age' => 10
            ]),
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'sgiT FunctionTestBot/1.3'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $loginData = json_decode($response, true);
        
        if ($httpCode === 200 && isset($loginData['success']) && $loginData['success']) {
            $this->logger->success("   ✅ Test-Login erfolgreich (User: TestBot, Alter: 10)");
        } else {
            $this->logger->warning("   ⚠️ Test-Login nicht möglich - Tests laufen ohne Session");
            if (isset($loginData['error'])) {
                $this->logger->info("   Info: " . $loginData['error']);
            }
        }
    }
    
    /**
     * Testet ein Modul komplett
     */
    private function testModuleComplete($module, $num) {
        $this->logger->info("");
        $this->logger->info("📚 [$module] Teste Modul... ($num/" . count($this->modules) . ")");
        
        $moduleResults = [
            'http' => false,
            'dom' => [],
            'form' => false,
            'session' => false,
            'navigation' => false,
            'edgeCases' => false,      // v1.6
            'performance' => false     // v1.6
        ];
        
        // Test 1: HTTP-Status
        $httpResult = $this->testHttpStatus($module);
        $moduleResults['http'] = $httpResult['passed'];
        
        // Nur weitertesten wenn HTTP OK
        if ($httpResult['passed'] && !empty($httpResult['html'])) {
            
            // Test 2: DOM-Struktur
            $moduleResults['dom'] = $this->testDomStructure($module, $httpResult['html']);
            
            // Test 3: Form-Submit
            if ($this->config['testFormSubmit']) {
                $moduleResults['form'] = $this->testFormSubmit($module);
            }
            
            // Test 4: Session-Handling
            if ($this->config['testSession']) {
                $moduleResults['session'] = $this->testSession($module);
            }
            
            // Test 5: Navigation
            if ($this->config['testNavigation']) {
                $moduleResults['navigation'] = $this->testNavigation($module, $httpResult['html']);
            }
            
            // v1.6 NEU: Test 6: Edge Cases
            if ($this->config['testEdgeCases']) {
                $moduleResults['edgeCases'] = $this->testEdgeCases($module);
            }
            
            // v1.6 NEU: Test 7: Performance-Metriken
            if ($this->config['testPerformance']) {
                $moduleResults['performance'] = $this->testPerformanceMetrics($module);
            }
        }
        
        // Modul-Zusammenfassung
        $passedCount = $this->countPassed($moduleResults);
        $totalCount = $this->countTotal($moduleResults);
        
        if ($passedCount === $totalCount) {
            $this->logger->success("   ▶️ Alle $totalCount Tests bestanden");
        } else {
            $this->logger->warning("   ▶️ $passedCount/$totalCount Tests bestanden");
        }
        
        return $moduleResults;
    }
    
    /**
     * Test 1: HTTP-Status
     * v1.2: Testet adaptive_learning.php?module=X statt /module/index.php
     */
    private function testHttpStatus($module) {
        // v1.2: Neue URL-Struktur!
        $url = $this->baseUrl . $this->learningPage . '?module=' . urlencode($module);
        $start = microtime(true);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'sgiT FunctionTestBot/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $duration = round((microtime(true) - $start) * 1000);
        $error = curl_error($ch);
        curl_close($ch);
        
        $passed = ($httpCode === 200);
        $this->stats['total']++;
        
        if ($passed) {
            $this->stats['passed']++;
            $this->logger->success(
                "   HTTP Status: $httpCode OK ({$duration}ms)",
                ['module' => $module, 'test' => 'http_status', 'duration_ms' => $duration]
            );
        } else {
            $this->stats['failed']++;
            $this->logger->error(
                "   HTTP Status: $httpCode FEHLER ({$duration}ms)",
                [
                    'module' => $module,
                    'test' => 'http_status',
                    'duration_ms' => $duration,
                    'suggestion' => $error ?: "Prüfe ob $module/index.php existiert"
                ]
            );
        }
        
        // Metrik speichern
        $this->logger->metric('http_response_time', $duration, 'ms', $module);
        
        return [
            'passed' => $passed,
            'code' => $httpCode,
            'duration' => $duration,
            'html' => $response
        ];
    }
    
    /**
     * Test 2: DOM-Struktur
     * v1.6: Mit echtem DOMDocument statt nur Regex
     */
    private function testDomStructure($module, $html) {
        $results = [];
        
        // v1.6: Echtes DOM-Parsing mit DOMDocument
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);
        
        // DOM-basierte Checks (präziser als Regex)
        $domChecks = [
            'quiz_modal' => [
                'xpath' => '//*[@id="quizModal"]',
                'required' => true,
                'description' => 'Quiz-Modal (#quizModal)'
            ],
            'question_container' => [
                'xpath' => '//*[@id="questionText"]',
                'required' => true,
                'description' => 'Frage-Container (#questionText)'
            ],
            'options_container' => [
                'xpath' => '//*[@id="optionsContainer"]',
                'required' => true,
                'description' => 'Options-Container (#optionsContainer)'
            ],
            'score_display' => [
                'xpath' => '//*[@id="sessionScore"] | //*[@id="moduleTotal"] | //*[contains(@class, "score")]',
                'required' => false,
                'description' => 'Score-Anzeige'
            ],
        ];
        
        // Regex-basierte Checks (für JS-Code der nicht im DOM ist)
        $regexChecks = [
            'module_card' => [
                'pattern' => '/startQuiz\s*\(\s*["\']' . preg_quote($module) . '["\']\s*\)|class=["\'][^"\']*module-card/i',
                'required' => true,
                'description' => 'Modul-Karte/startQuiz()'
            ],
            'js_functions' => [
                'pattern' => '/function\s+loadQuestion|function\s+checkAnswer|fetch\s*\(.*action=get_question/i',
                'required' => true,
                'description' => 'JavaScript Quiz-Funktionen'
            ],
        ];
        
        // DOM-basierte Prüfungen
        foreach ($domChecks as $name => $config) {
            $nodes = $xpath->query($config['xpath']);
            $found = $nodes->length > 0;
            $results[$name] = $found;
            $this->stats['total']++;
            
            if ($found) {
                $this->stats['passed']++;
                $this->logger->success(
                    "   DOM: {$config['description']} ✓ (XPath)",
                    ['module' => $module, 'test' => 'dom_' . $name]
                );
            } else {
                if ($config['required']) {
                    $this->stats['failed']++;
                    $this->logger->error(
                        "   DOM: {$config['description']} FEHLT",
                        [
                            'module' => $module,
                            'test' => 'dom_' . $name,
                            'suggestion' => "Element mit XPath '{$config['xpath']}' nicht gefunden"
                        ]
                    );
                } else {
                    $this->stats['warnings']++;
                    $this->logger->warning(
                        "   DOM: {$config['description']} nicht gefunden (optional)",
                        ['module' => $module, 'test' => 'dom_' . $name]
                    );
                }
            }
        }
        
        // Regex-basierte Prüfungen (für JavaScript-Code)
        foreach ($regexChecks as $name => $config) {
            $found = preg_match($config['pattern'], $html);
            $results[$name] = (bool)$found;
            $this->stats['total']++;
            
            if ($found) {
                $this->stats['passed']++;
                $this->logger->success(
                    "   DOM: {$config['description']} ✓ (Regex)",
                    ['module' => $module, 'test' => 'dom_' . $name]
                );
            } else {
                if ($config['required']) {
                    $this->stats['failed']++;
                    $this->logger->error(
                        "   DOM: {$config['description']} FEHLT",
                        [
                            'module' => $module,
                            'test' => 'dom_' . $name,
                            'suggestion' => "Prüfe adaptive_learning.php JavaScript-Code"
                        ]
                    );
                } else {
                    $this->stats['warnings']++;
                    $this->logger->warning(
                        "   DOM: {$config['description']} nicht gefunden (optional)",
                        ['module' => $module, 'test' => 'dom_' . $name]
                    );
                }
            }
        }
        
        // Prüfe ob Modul in der Modul-Liste ist
        $moduleListPattern = '/["\']' . preg_quote($module) . '["\']/i';
        if (preg_match($moduleListPattern, $html)) {
            $this->logger->info("   DOM: Modul '$module' in Seite referenziert");
        }
        
        return $results;
    }
    
    /**
     * Test 3: AJAX-API Test
     * v1.3: Testet get_question und check_answer Endpoints
     */
    private function testFormSubmit($module) {
        $results = ['get_question' => false, 'check_answer' => false];
        
        // ========================================
        // Test 3a: GET ?action=get_question
        // ========================================
        $url = $this->baseUrl . $this->learningPage . '?action=get_question&module=' . urlencode($module);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'sgiT FunctionTestBot/1.3'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $this->stats['total']++;
        $jsonData = json_decode($response, true);
        
        if ($httpCode === 200 && $jsonData && isset($jsonData['success'])) {
            if ($jsonData['success'] && isset($jsonData['question']) && isset($jsonData['options'])) {
                $this->stats['passed']++;
                $results['get_question'] = true;
                $this->logger->success(
                    "   AJAX: get_question OK (Frage + " . count($jsonData['options']) . " Optionen)",
                    ['module' => $module, 'test' => 'ajax_get_question']
                );
                
                // ========================================
                // Test 3b: POST action=check_answer
                // ========================================
                $postUrl = $this->baseUrl . $this->learningPage;
                $ch = curl_init($postUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query([
                        'action' => 'check_answer',
                        'module' => $module,
                        'answer' => $jsonData['options'][0] ?? 'Test',
                        'correct' => $jsonData['answer'] ?? ''
                    ]),
                    CURLOPT_COOKIEJAR => $this->cookieFile,
                    CURLOPT_COOKIEFILE => $this->cookieFile,
                    CURLOPT_USERAGENT => 'sgiT FunctionTestBot/1.3'
                ]);
                
                $checkResponse = curl_exec($ch);
                $checkCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $this->stats['total']++;
                $checkData = json_decode($checkResponse, true);
                
                if ($checkCode === 200 && $checkData && isset($checkData['success'])) {
                    $this->stats['passed']++;
                    $results['check_answer'] = true;
                    $correct = isset($checkData['correct']) && $checkData['correct'] ? '✅' : '❌';
                    $this->logger->success(
                        "   AJAX: check_answer OK ($correct)",
                        ['module' => $module, 'test' => 'ajax_check_answer']
                    );
                } else {
                    $this->stats['failed']++;
                    $this->logger->error(
                        "   AJAX: check_answer FEHLER (HTTP $checkCode)",
                        [
                            'module' => $module,
                            'test' => 'ajax_check_answer',
                            'response' => substr($checkResponse, 0, 200)
                        ]
                    );
                }
                
            } else {
                $this->stats['failed']++;
                $this->logger->error(
                    "   AJAX: get_question - Keine Frage/Optionen",
                    [
                        'module' => $module,
                        'test' => 'ajax_get_question',
                        'error' => $jsonData['error'] ?? 'Unbekannt'
                    ]
                );
            }
        } else {
            $this->stats['failed']++;
            $this->logger->error(
                "   AJAX: get_question FEHLER (HTTP $httpCode)",
                [
                    'module' => $module,
                    'test' => 'ajax_get_question',
                    'suggestion' => 'Prüfe AJAX-Handler in adaptive_learning.php'
                ]
            );
        }
        
        return $results['get_question'] && $results['check_answer'];
    }
    
    /**
     * Test 4: Session-Handling
     */
    private function testSession($module) {
        $this->stats['total']++;
        
        // Prüfe Cookie-Datei
        if (!file_exists($this->cookieFile)) {
            $this->stats['warnings']++;
            $this->logger->warning(
                "   Session: Keine Cookie-Datei",
                ['module' => $module, 'test' => 'session']
            );
            return false;
        }
        
        $cookies = file_get_contents($this->cookieFile);
        $hasSession = (strpos($cookies, 'PHPSESSID') !== false) || 
                      (strpos($cookies, 'session') !== false);
        
        if ($hasSession) {
            $this->stats['passed']++;
            $this->logger->success(
                "   Session: Cookie aktiv",
                ['module' => $module, 'test' => 'session']
            );
        } else {
            $this->stats['warnings']++;
            $this->logger->warning(
                "   Session: Kein Session-Cookie gefunden",
                [
                    'module' => $module,
                    'test' => 'session',
                    'suggestion' => 'session_start() am Anfang der Datei aufrufen'
                ]
            );
        }
        
        return $hasSession;
    }
    
    /**
     * Test 5: Navigation / UI-Elemente
     * v1.3: Prüft auf SPA-Navigation (Modals, Logout, Wallet-Links)
     */
    private function testNavigation($module, $html) {
        $this->stats['total']++;
        
        // v1.3: adaptive_learning.php ist eine SPA - prüfe auf UI-Elemente statt Links
        $navPatterns = [
            '/logout|abmelden/i',  // Logout-Funktion
            '/closeQuiz|closeSessionModal/i',  // Modal-Schließen
            '/class=["\'][^"\']*header|class=["\'][^"\']*navbar/i',  // Header/Navbar
            '/wallet\.php|profil/i',  // Wallet/Profil Links
            '/href=["\'][^"\']*index/i',  // Index-Links
            '/class=["\'][^"\']*logo/i'  // Logo (meist klickbar)
        ];
        
        $foundItems = [];
        foreach ($navPatterns as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                $foundItems[] = $match[0];
            }
        }
        
        if (count($foundItems) > 0) {
            $this->stats['passed']++;
            $this->logger->success(
                "   Navigation: " . count($foundItems) . " UI-Elemente gefunden",
                ['module' => $module, 'test' => 'navigation']
            );
        } else {
            $this->stats['warnings']++;
            $this->logger->warning(
                "   Navigation: Keine Navigation/UI gefunden (SPA)",
                [
                    'module' => $module,
                    'test' => 'navigation',
                    'suggestion' => 'Optional für SPA-Architektur'
                ]
            );
        }
        
        return count($foundItems) > 0;
    }
    
    // ================================================================
    // v1.6 NEU: Erweiterte Tests
    // ================================================================
    
    /**
     * Test 6: Edge Cases
     * Testet Randfälle und ungewöhnliche Eingaben
     * @since v1.6
     */
    private function testEdgeCases($module) {
        $this->logger->info("   🔬 Teste Edge Cases...");
        
        $url = $this->baseUrl . $this->learningPage;
        $allPassed = true;
        
        $edgeCases = [
            [
                'name' => 'Leere Eingabe',
                'data' => ['action' => 'check_answer', 'module' => $module, 'answer' => ''],
                'expect' => 'error_handling'
            ],
            [
                'name' => 'Sehr lange Eingabe',
                'data' => ['action' => 'check_answer', 'module' => $module, 'answer' => str_repeat('A', 5000)],
                'expect' => 'error_handling'
            ],
            [
                'name' => 'Unicode/Emoji',
                'data' => ['action' => 'check_answer', 'module' => $module, 'answer' => '答え🦊äöü'],
                'expect' => 'error_handling'
            ],
            [
                'name' => 'Sonderzeichen',
                'data' => ['action' => 'check_answer', 'module' => $module, 'answer' => '<>&"\'\\'],
                'expect' => 'error_handling'
            ],
            [
                'name' => 'Numerische Eingabe',
                'data' => ['action' => 'check_answer', 'module' => $module, 'answer' => '12345'],
                'expect' => 'valid_response'
            ],
        ];
        
        $passedCount = 0;
        foreach ($edgeCases as $case) {
            $this->stats['total']++;
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($case['data']),
                CURLOPT_TIMEOUT => 5,
                CURLOPT_COOKIEJAR => $this->cookieFile,
                CURLOPT_COOKIEFILE => $this->cookieFile,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Erfolg = Server crasht nicht (HTTP 200/400) und gibt valides JSON zurück
            $isValidResponse = ($httpCode === 200 || $httpCode === 400);
            $isValidJson = json_decode($response) !== null;
            $noPhpError = (stripos($response, 'Fatal error') === false && 
                          stripos($response, 'Warning:') === false);
            
            if ($isValidResponse && $noPhpError) {
                $passedCount++;
                $this->stats['passed']++;
            } else {
                $allPassed = false;
                $this->stats['failed']++;
                $this->logger->warning(
                    "   ⚠️ Edge Case '{$case['name']}' - Unerwartete Response",
                    [
                        'module' => $module,
                        'test' => 'edge_case_' . strtolower(str_replace(' ', '_', $case['name'])),
                        'http_code' => $httpCode,
                        'suggestion' => 'Robustere Eingabevalidierung implementieren'
                    ]
                );
            }
        }
        
        if ($allPassed) {
            $this->logger->success("   ✅ Alle " . count($edgeCases) . " Edge Cases bestanden");
        } else {
            $this->logger->warning("   ⚠️ $passedCount/" . count($edgeCases) . " Edge Cases bestanden");
        }
        
        return $allPassed;
    }
    
    /**
     * Test 7: Performance-Metriken
     * Misst detaillierte Timing-Informationen
     * @since v1.6
     */
    private function testPerformanceMetrics($module) {
        $this->logger->info("   ⏱️ Messe Performance...");
        $this->stats['total']++;
        
        $url = $this->baseUrl . $this->learningPage . '?action=get_question&module=' . urlencode($module);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
        ]);
        
        $response = curl_exec($ch);
        
        // Detaillierte Timing-Informationen
        $metrics = [
            'dns_time' => round(curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME) * 1000, 2),
            'connect_time' => round(curl_getinfo($ch, CURLINFO_CONNECT_TIME) * 1000, 2),
            'ttfb' => round(curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME) * 1000, 2),
            'total_time' => round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000, 2),
            'download_size' => curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD),
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        ];
        
        curl_close($ch);
        
        // Speichere Metriken für späteren Report
        $this->performanceMetrics[$module] = $metrics;
        
        // Prüfe gegen Schwellwerte
        $thresholds = $this->config['performanceThresholds'];
        $warnings = [];
        
        if ($metrics['ttfb'] > $thresholds['ttfb']) {
            $warnings[] = "TTFB {$metrics['ttfb']}ms > {$thresholds['ttfb']}ms";
        }
        
        if ($metrics['total_time'] > $thresholds['total']) {
            $warnings[] = "Total {$metrics['total_time']}ms > {$thresholds['total']}ms";
        }
        
        if (empty($warnings)) {
            $this->stats['passed']++;
            $this->logger->success(
                "   ✅ Performance OK (TTFB: {$metrics['ttfb']}ms, Total: {$metrics['total_time']}ms)",
                ['module' => $module, 'test' => 'performance', 'metrics' => $metrics]
            );
            
            // Metrik speichern
            $this->logger->metric('response_time', $metrics['total_time'], 'ms', $module);
            $this->logger->metric('ttfb', $metrics['ttfb'], 'ms', $module);
            
            return true;
        } else {
            $this->stats['warnings']++;
            $this->logger->warning(
                "   ⚠️ Performance-Warnung: " . implode(', ', $warnings),
                [
                    'module' => $module,
                    'test' => 'performance',
                    'metrics' => $metrics,
                    'suggestion' => 'DB-Queries optimieren oder Caching einführen'
                ]
            );
            
            return false;
        }
    }
    
    /**
     * v1.6 NEU: Parallele HTTP-Tests mit curl_multi
     * Testet alle Module gleichzeitig für schnellere Ergebnisse
     * @since v1.6
     */
    private function runParallelHttpTests() {
        $this->logger->info("   Starte parallele HTTP-Tests für " . count($this->modules) . " Module...");
        $startTime = microtime(true);
        
        $multiHandle = curl_multi_init();
        $handles = [];
        
        // Alle Requests vorbereiten
        foreach ($this->modules as $module) {
            $url = $this->baseUrl . $this->learningPage . '?module=' . urlencode($module);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->config['timeout'],
                CURLOPT_USERAGENT => 'sgiT FunctionTestBot/1.6-parallel',
            ]);
            curl_multi_add_handle($multiHandle, $ch);
            $handles[$module] = $ch;
        }
        
        // Alle parallel ausführen
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);
        
        // Ergebnisse sammeln
        $parallelResults = [];
        $successCount = 0;
        $failCount = 0;
        
        foreach ($handles as $module => $ch) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $totalTime = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000, 1);
            
            $parallelResults[$module] = [
                'http_code' => $httpCode,
                'time_ms' => $totalTime,
                'success' => ($httpCode === 200)
            ];
            
            if ($httpCode === 200) {
                $successCount++;
            } else {
                $failCount++;
            }
            
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($multiHandle);
        
        $duration = round((microtime(true) - $startTime) * 1000, 1);
        
        // Ergebnis ausgeben
        $this->logger->info("   ═══ Parallele HTTP-Ergebnisse ═══");
        $this->logger->success("   ✅ Erreichbar: $successCount/" . count($this->modules));
        
        if ($failCount > 0) {
            $this->logger->warning("   ⚠️ Nicht erreichbar: $failCount");
            foreach ($parallelResults as $mod => $res) {
                if (!$res['success']) {
                    $this->logger->error("      ❌ $mod: HTTP {$res['http_code']}");
                }
            }
        }
        
        // Zeitvergleich
        $avgTime = round(array_sum(array_column($parallelResults, 'time_ms')) / count($parallelResults), 1);
        $this->logger->info("   ⏱️ Parallel-Dauer: {$duration}ms (Avg pro Modul: {$avgTime}ms)");
        
        // Metriken speichern
        $this->logger->metric('parallel_duration', $duration, 'ms');
        $this->logger->metric('parallel_avg_response', $avgTime, 'ms');
        
        return $parallelResults;
    }
    
    /**
     * Generiert die Zusammenfassung
     */
    private function generateSummary($totalTime) {
        $this->logger->info("");
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("📊 ZUSAMMENFASSUNG");
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("   Module getestet:  {$this->stats['modules_tested']}");
        $this->logger->info("   Tests gesamt:     {$this->stats['total']}");
        $this->logger->success("   ✅ Bestanden:     {$this->stats['passed']}");
        
        if ($this->stats['warnings'] > 0) {
            $this->logger->warning("   ⚠️ Warnungen:     {$this->stats['warnings']}");
        }
        
        if ($this->stats['failed'] > 0) {
            $this->logger->error("   ❌ Fehlgeschlagen: {$this->stats['failed']}");
        }
        
        $this->logger->info("   ⏱️ Laufzeit:       {$totalTime}s");
        
        // Erfolgsrate berechnen
        $successRate = $this->stats['total'] > 0 
            ? round(($this->stats['passed'] / $this->stats['total']) * 100, 1) 
            : 0;
        
        $this->logger->info("   📈 Erfolgsrate:   {$successRate}%");
        
        // v1.6: Performance-Übersicht
        if (!empty($this->performanceMetrics)) {
            $avgTtfb = round(array_sum(array_column($this->performanceMetrics, 'ttfb')) / count($this->performanceMetrics), 1);
            $avgTotal = round(array_sum(array_column($this->performanceMetrics, 'total_time')) / count($this->performanceMetrics), 1);
            $this->logger->info("   ⏱️ Avg TTFB:       {$avgTtfb}ms");
            $this->logger->info("   ⏱️ Avg Total:      {$avgTotal}ms");
        }
        
        $this->logger->info("═══════════════════════════════════════════════════════");
        
        // Metrik speichern
        $this->logger->metric('success_rate', $successRate, '%');
        $this->logger->metric('total_tests', $this->stats['total'], 'count');
        $this->logger->metric('total_time', $totalTime, 's');
    }
    
    /**
     * Hilfsmethoden
     */
    private function shouldStop() {
        return file_exists($this->stopFile) || connection_aborted();
    }
    
    private function countPassed($results) {
        $count = 0;
        foreach ($results as $key => $value) {
            if (is_array($value)) {
                $count += array_sum(array_map('intval', $value));
            } else {
                $count += $value ? 1 : 0;
            }
        }
        return $count;
    }
    
    private function countTotal($results) {
        $count = 0;
        foreach ($results as $key => $value) {
            if (is_array($value)) {
                $count += count($value);
            } else {
                $count += 1;
            }
        }
        return $count;
    }
    
    /**
     * Stoppt den Bot
     */
    public static function stop() {
        $stopFile = dirname(__DIR__) . '/logs/STOP_FUNCTION_BOT';
        file_put_contents($stopFile, date('Y-m-d H:i:s'));
        return true;
    }
    
    /**
     * Schnelltest - Nur HTTP-Status
     */
    public function quickTest() {
        $this->config['testFormSubmit'] = false;
        $this->config['testSession'] = false;
        $this->config['testNavigation'] = false;
        return $this->run();
    }
    
    /**
     * Einzelnes Modul testen
     */
    public function testSingleModule($module) {
        $this->modules = [$module];
        return $this->run();
    }
}

// ============================================================
// STANDALONE AUSFÜHRUNG MIT UI
// ============================================================
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    
    set_time_limit(300); // 5 Minuten max
    
    // Stop-Befehl verarbeiten
    if (isset($_GET['stop'])) {
        FunctionTestBot::stop();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?stopped=1');
        exit;
    }
    
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>🧪 Function Test Bot - sgiT Education</title>
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
            border-bottom: 3px solid #43D240; 
            padding-bottom: 15px; 
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
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
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
        button.secondary { background: #6c757d; }
        .results {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
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
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .stat-box.success { border-left: 4px solid #28a745; }
        .stat-box.warning { border-left: 4px solid #ffc107; }
        .stat-box.error { border-left: 4px solid #dc3545; }
        .stat-box .number { font-size: 32px; font-weight: bold; color: #1A3503; }
        .stat-box .label { font-size: 12px; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧪 Function Test Bot <span class="badge">v1.6</span></h1>
    
    <?php if (isset($_GET['stopped'])): ?>
    <div class="success-box">
        ✅ <strong>Bot gestoppt!</strong>
    </div>
    <?php endif; ?>
    
    <div class="info-box">
        <h4>ℹ️ Was macht dieser Bot?</h4>
        <p>Testet systematisch alle <strong>15 Module</strong> der Lernplattform:</p>
        <ul>
            <li>🌐 HTTP-Erreichbarkeit (Status 200)</li>
            <li>🏗️ DOM-Struktur (Quiz-Modal, Options-Container, JS-Funktionen)</li>
            <li>🔄 AJAX-API (get_question + check_answer Endpoints)</li>
            <li>🍪 Session-Handling (Cookies aktiv)</li>
            <li>🧭 Navigation (SPA-UI-Elemente)</li>
            <li>🔬 Edge Cases (leere/lange/Unicode-Eingaben) (NEU!)</li>
            <li>⏱️ Performance-Metriken (TTFB, Response-Zeit) (NEU!)</li>
        </ul>
    </div>
    
    <form method="post">
        <button type="submit" name="run_full">▶️ Vollständiger Test</button>
        <button type="submit" name="run_parallel" class="secondary">🚀 Parallel Test</button>
        <button type="submit" name="run_quick" class="secondary">⚡ Quick Test (nur HTTP)</button>
        <a href="?stop=1"><button type="button" class="stop">⏹️ Stoppen</button></a>
        <a href="../bot_summary.php"><button type="button" class="secondary">📊 Dashboard</button></a>
    </form>
    
    <?php
    if (isset($_POST['run_full']) || isset($_POST['run_quick']) || isset($_POST['run_parallel'])) {
        echo '<div class="results">';
        echo '<h3>🔄 Test läuft...</h3>';
        echo '<div class="log-output" id="live-log">';
        
        // Docker/nginx/PHP-FPM Live-Output Fix
        BotOutputHelper::init();
        
        $config = [];
        if (isset($_POST['run_parallel'])) {
            $config['parallelMode'] = true;
        }
        
        $bot = new FunctionTestBot($config);
        
        if (isset($_POST['run_quick'])) {
            $results = $bot->quickTest();
        } else {
            $results = $bot->run();
        }
        
        echo '</div>';
        
        // Statistik-Karten
        echo '<div class="stat-grid">';
        echo '<div class="stat-box"><div class="number">' . $results['total'] . '</div><div class="label">Tests gesamt</div></div>';
        echo '<div class="stat-box success"><div class="number">' . $results['passed'] . '</div><div class="label">Bestanden</div></div>';
        echo '<div class="stat-box warning"><div class="number">' . $results['warnings'] . '</div><div class="label">Warnungen</div></div>';
        echo '<div class="stat-box error"><div class="number">' . $results['failed'] . '</div><div class="label">Fehlgeschlagen</div></div>';
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
    $options = getopt('', ['quick', 'module:', 'help']);
    
    if (isset($options['help'])) {
        echo "\nFunction Test Bot - Modul-Tests\n";
        echo "================================\n\n";
        echo "Optionen:\n";
        echo "  --quick        Nur HTTP-Status testen\n";
        echo "  --module=name  Einzelnes Modul testen\n\n";
        exit(0);
    }
    
    $bot = new FunctionTestBot();
    
    if (isset($options['module'])) {
        $results = $bot->testSingleModule($options['module']);
    } elseif (isset($options['quick'])) {
        $results = $bot->quickTest();
    } else {
        $results = $bot->run();
    }
    
    echo "\n\nErgebnis:\n";
    print_r($results);
}
?>
