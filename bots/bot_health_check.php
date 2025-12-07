<?php
/**
 * ============================================================================
 * sgiT Education - Bot Health Check
 * ============================================================================
 * 
 * BUG-030 FIX: Graceful Degradation für alle Bots
 * 
 * Features:
 * - Server-Erreichbarkeitsprüfung vor Tests
 * - Retry-Logik mit Exponential Backoff
 * - Saubere Fehlermeldungen statt Abbruch
 * - Automatische Docker/XAMPP-Erkennung
 * 
 * @version 1.0
 * @date 08.12.2025
 * @author sgiT Solution Engineering & IT Services
 * ============================================================================
 */

class BotHealthCheck {
    
    // Konfiguration
    private static $config = [
        'maxRetries' => 3,
        'initialDelayMs' => 500,
        'maxDelayMs' => 5000,
        'timeoutSeconds' => 5
    ];
    
    // Status-Konstanten
    const STATUS_OK = 'ok';
    const STATUS_DEGRADED = 'degraded';
    const STATUS_OFFLINE = 'offline';
    
    /**
     * Ermittelt die Base-URL (Docker oder XAMPP)
     */
    public static function detectBaseUrl() {
        if (file_exists('/var/www/html')) {
            return 'http://nginx/';
        }
        return 'http://localhost:8080/';
    }
    
    /**
     * Führt einen Health-Check durch
     * 
     * @param string $baseUrl Die Base-URL zum Testen
     * @param callable|null $logger Optionale Log-Funktion
     * @return array ['status' => string, 'message' => string, 'responseTime' => int]
     */
    public static function check($baseUrl = null, $logger = null) {
        $baseUrl = $baseUrl ?? self::detectBaseUrl();
        $log = $logger ?? function($msg) { /* silent */ };
        
        $log("🏥 Health-Check: $baseUrl");
        
        $startTime = microtime(true);
        
        $ch = curl_init($baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::$config['timeoutSeconds'],
            CURLOPT_CONNECTTIMEOUT => self::$config['timeoutSeconds'],
            CURLOPT_NOBODY => true,  // HEAD Request für Schnelligkeit
            CURLOPT_USERAGENT => 'sgiT BotHealthCheck/1.0'
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        // Auswertung
        if ($errno !== 0) {
            $errorMessages = [
                CURLE_COULDNT_CONNECT => 'Verbindung verweigert - Server nicht erreichbar',
                CURLE_OPERATION_TIMEDOUT => 'Timeout - Server antwortet nicht',
                CURLE_COULDNT_RESOLVE_HOST => 'Host nicht gefunden - DNS-Problem',
                CURLE_SSL_CONNECT_ERROR => 'SSL-Verbindungsfehler'
            ];
            
            $message = $errorMessages[$errno] ?? "cURL Fehler: $error";
            
            return [
                'status' => self::STATUS_OFFLINE,
                'message' => $message,
                'responseTime' => $responseTime,
                'httpCode' => 0,
                'curlError' => $errno
            ];
        }
        
        if ($httpCode >= 500) {
            return [
                'status' => self::STATUS_DEGRADED,
                'message' => "Server-Fehler (HTTP $httpCode)",
                'responseTime' => $responseTime,
                'httpCode' => $httpCode
            ];
        }
        
        if ($httpCode >= 200 && $httpCode < 400) {
            return [
                'status' => self::STATUS_OK,
                'message' => "Server erreichbar ({$responseTime}ms)",
                'responseTime' => $responseTime,
                'httpCode' => $httpCode
            ];
        }
        
        return [
            'status' => self::STATUS_DEGRADED,
            'message' => "Unerwarteter Status (HTTP $httpCode)",
            'responseTime' => $responseTime,
            'httpCode' => $httpCode
        ];
    }
    
    /**
     * Wartet auf Server-Verfügbarkeit mit Exponential Backoff
     * 
     * @param string $baseUrl Die Base-URL
     * @param callable|null $logger Optionale Log-Funktion
     * @return array Health-Check-Ergebnis
     */
    public static function waitForServer($baseUrl = null, $logger = null) {
        $baseUrl = $baseUrl ?? self::detectBaseUrl();
        $log = $logger ?? function($msg) { echo "$msg\n"; };
        
        $delay = self::$config['initialDelayMs'];
        
        for ($attempt = 1; $attempt <= self::$config['maxRetries']; $attempt++) {
            $result = self::check($baseUrl);
            
            if ($result['status'] === self::STATUS_OK) {
                $log("✅ Server erreichbar nach $attempt Versuch(en)");
                return $result;
            }
            
            if ($attempt < self::$config['maxRetries']) {
                $log("⏳ Versuch $attempt/" . self::$config['maxRetries'] . 
                     " fehlgeschlagen: " . $result['message']);
                $log("   Warte {$delay}ms vor erneutem Versuch...");
                
                usleep($delay * 1000);
                
                // Exponential Backoff (verdopple Wartezeit)
                $delay = min($delay * 2, self::$config['maxDelayMs']);
            }
        }
        
        // Alle Versuche fehlgeschlagen
        $log("❌ Server nach " . self::$config['maxRetries'] . " Versuchen nicht erreichbar");
        
        return [
            'status' => self::STATUS_OFFLINE,
            'message' => 'Server nach mehreren Versuchen nicht erreichbar',
            'attempts' => self::$config['maxRetries'],
            'finalError' => $result['message'] ?? 'Unbekannter Fehler'
        ];
    }
    
    /**
     * Führt einen HTTP-Request mit Retry-Logik aus
     * 
     * @param string $url Die vollständige URL
     * @param array $options cURL Optionen
     * @param callable|null $logger Optionale Log-Funktion
     * @return array ['success' => bool, 'response' => string, 'httpCode' => int, ...]
     */
    public static function requestWithRetry($url, $options = [], $logger = null) {
        $log = $logger ?? function($msg) { /* silent */ };
        
        $delay = self::$config['initialDelayMs'];
        $lastError = null;
        
        for ($attempt = 1; $attempt <= self::$config['maxRetries']; $attempt++) {
            $ch = curl_init($url);
            
            // Standard-Optionen
            $defaultOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT => 'sgiT Bot/1.0'
            ];
            
            curl_setopt_array($ch, array_replace($defaultOptions, $options));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            
            // Erfolg?
            if ($errno === 0 && $httpCode >= 200 && $httpCode < 500) {
                return [
                    'success' => true,
                    'response' => $response,
                    'httpCode' => $httpCode,
                    'attempts' => $attempt
                ];
            }
            
            $lastError = $errno !== 0 ? $error : "HTTP $httpCode";
            
            if ($attempt < self::$config['maxRetries']) {
                $log("   ⚠️ Versuch $attempt fehlgeschlagen ($lastError), wiederhole...");
                usleep($delay * 1000);
                $delay = min($delay * 2, self::$config['maxDelayMs']);
            }
        }
        
        return [
            'success' => false,
            'response' => null,
            'httpCode' => $httpCode ?? 0,
            'error' => $lastError,
            'attempts' => self::$config['maxRetries']
        ];
    }
    
    /**
     * Generiert eine benutzerfreundliche Fehlermeldung
     */
    public static function getHelpMessage($status, $baseUrl) {
        $messages = [
            self::STATUS_OFFLINE => [
                "╔══════════════════════════════════════════════════════════════╗",
                "║  ❌ SERVER NICHT ERREICHBAR                                   ║",
                "╠══════════════════════════════════════════════════════════════╣",
                "║  URL: $baseUrl",
                "║                                                              ║",
                "║  Mögliche Ursachen:                                          ║",
                "║  • Docker-Container nicht gestartet                          ║",
                "║  • XAMPP Apache/nginx nicht aktiv                            ║",
                "║  • Firewall blockiert Port 8080                              ║",
                "║                                                              ║",
                "║  Lösung:                                                     ║",
                "║  1. docker-compose up -d  (für Docker)                       ║",
                "║  2. XAMPP Control Panel → Apache starten                     ║",
                "║  3. Browser: $baseUrl testen           ║",
                "╚══════════════════════════════════════════════════════════════╝"
            ],
            self::STATUS_DEGRADED => [
                "╔══════════════════════════════════════════════════════════════╗",
                "║  ⚠️ SERVER ANTWORTET MIT FEHLERN                              ║",
                "╠══════════════════════════════════════════════════════════════╣",
                "║  Der Server ist erreichbar, aber liefert Fehler.             ║",
                "║  Tests werden mit eingeschränkter Funktionalität ausgeführt. ║",
                "╚══════════════════════════════════════════════════════════════╝"
            ]
        ];
        
        return $messages[$status] ?? [];
    }
    
    /**
     * Konfiguration anpassen
     */
    public static function configure($options) {
        self::$config = array_merge(self::$config, $options);
    }
}
