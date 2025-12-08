# TODO-001: Bot Features Analyse & Umsetzungsplan

**Erstellt:** 08. Dezember 2025  
**Autor:** Claude (AI Assistant)  
**Version:** 1.0  
**Projekt:** sgiT Education Platform  

---

## 📋 Inhaltsverzeichnis

1. [Executive Summary](#1-executive-summary)
2. [IST-Analyse: FunctionTestBot](#2-ist-analyse-functiontestbot)
3. [IST-Analyse: SecurityBot](#3-ist-analyse-securitybot)
4. [Geplante Verbesserungen](#4-geplante-verbesserungen)
5. [Detaillierter Umsetzungsplan](#5-detaillierter-umsetzungsplan)
6. [Priorisierung & Zeitschätzung](#6-priorisierung--zeitschätzung)
7. [Technische Spezifikationen](#7-technische-spezifikationen)
8. [Anhang: Code-Templates](#8-anhang-code-templates)

---

## 1. Executive Summary

### Ziel
Erweiterung der beiden Test-Bots (FunctionTestBot & SecurityBot) um zusätzliche Testszenarien, bessere Fehlerdiagnose und erweiterte Sicherheitsprüfungen.

### Aktueller Stand
| Bot | Version | Tests | Status |
|-----|---------|-------|--------|
| FunctionTestBot | v1.5 | 7 Tests/Modul | ✅ Funktional |
| SecurityBot | v1.4 | 5 Kategorien | ✅ Funktional |

### Geplante Erweiterungen
- **FunctionTestBot:** 5 neue Features (Edge Cases, DOM-Validierung, Screenshots, Performance-Metriken, Parallelisierung)
- **SecurityBot:** 6 neue Tests (CSRF, Rate-Limiting, Auth Bypass, Header Security, Cookie Security, File Upload)

### Geschätzter Gesamtaufwand
**6-8 Stunden** für vollständige Implementierung

---

## 2. IST-Analyse: FunctionTestBot

### 2.1 Aktuelle Struktur

**Datei:** `/bots/tests/FunctionTestBot.php`  
**Zeilen:** 936  
**Version:** v1.5 (Zentrale Versionsverwaltung)

### 2.2 Aktuelle Test-Kategorien

| Test | Beschreibung | Implementiert |
|------|--------------|---------------|
| HTTP-Status | Prüft ob Module HTTP 200 zurückgeben | ✅ |
| DOM-Struktur | Prüft auf Quiz-Modal, Options-Container, JS-Funktionen | ✅ |
| AJAX API | Testet `get_question` und `check_answer` Endpoints | ✅ |
| Session-Handling | Prüft auf aktive Cookies/Session | ✅ |
| Navigation | Prüft auf UI-Elemente (SPA-konform) | ✅ |

### 2.3 Architektur-Highlights

```php
// Automatische Docker-Erkennung
private function detectBaseUrl() {
    if (file_exists('/var/www/html')) {
        return 'http://nginx/';  // Docker
    }
    return 'http://localhost:8080/';  // XAMPP
}

// Health-Check mit Retry (BUG-030 Fix)
$healthResult = BotHealthCheck::waitForServer($this->baseUrl, ...);

// Test-Session Initialisierung (simuliert Login)
private function initTestSession() { ... }
```

### 2.4 Aktuelle Schwächen

| Schwäche | Impact | Priorität |
|----------|--------|-----------|
| Keine Edge-Case Tests | Mittel | 🟡 |
| DOM-Validierung nur Regex-basiert | Mittel | 🟡 |
| Keine Screenshots bei Fehlern | Niedrig | 🟢 |
| Keine detaillierten Performance-Metriken | Niedrig | 🟢 |
| Sequentielle Ausführung (langsam) | Mittel | 🟡 |

---

## 3. IST-Analyse: SecurityBot

### 3.1 Aktuelle Struktur

**Datei:** `/bots/tests/SecurityBot.php`  
**Zeilen:** 936  
**Version:** v1.4 (Zentrale Versionsverwaltung)

### 3.2 Aktuelle Test-Kategorien

| Kategorie | Payloads | Tests | Status |
|-----------|----------|-------|--------|
| SQL Injection | 10 | Pro Modul | ✅ |
| XSS (Cross-Site Scripting) | 10 | Pro Modul | ✅ |
| Path Traversal | 9 | Systemweit | ✅ |
| Session Security | - | Länge, Entropie, HttpOnly | ✅ |
| Information Disclosure | 7 Pattern | Pro Modul | ✅ |

### 3.3 Payload-Bibliothek (Auszug)

```php
private $payloads = [
    'sql_injection' => [
        "' OR '1'='1",
        "'; DROP TABLE users; --",
        "1' AND SLEEP(2) --",
        // ... 7 weitere
    ],
    'xss' => [
        '<script>alert(1)</script>',
        '<img src=x onerror=alert(1)>',
        // ... 8 weitere
    ],
    'path_traversal' => [
        '../../../etc/passwd',
        '..%2F..%2Fconfig.php',
        // ... 7 weitere
    ]
];
```

### 3.4 Aktuelle Schwächen

| Schwäche | Impact | Priorität |
|----------|--------|-----------|
| Keine CSRF-Token Tests | Hoch | 🔴 |
| Kein Rate-Limiting Test | Hoch | 🔴 |
| Keine Auth Bypass Tests | Mittel | 🟡 |
| Keine Header Security Tests | Mittel | 🟡 |
| Cookie Security unvollständig | Mittel | 🟡 |
| Keine File Upload Tests | Niedrig | 🟢 |

---

## 4. Geplante Verbesserungen

### 4.1 FunctionTestBot Erweiterungen


#### 4.1.1 Mehr Test-Szenarien (Edge Cases)

**Ziel:** Randfälle testen, die in normalen Tests nicht abgedeckt werden.

| Edge Case | Beschreibung | Test-Methode |
|-----------|--------------|--------------|
| Leere Eingaben | `answer=""` | POST mit leerem String |
| Sehr lange Eingaben | 10.000+ Zeichen | Buffer Overflow Test |
| Unicode/Emoji | `答え=🦊` | Encoding-Test |
| Null-Bytes | `answer=%00test` | Injection-Test |
| Negative IDs | `id=-1` | Boundary-Test |
| Concurrent Requests | Gleichzeitige Anfragen | Race Condition |
| Timeout-Verhalten | Künstlich verzögerte Response | Timeout-Handling |

**Implementierung:**
```php
private function testEdgeCases($module) {
    $edgeCases = [
        ['name' => 'empty_input', 'data' => ['answer' => '']],
        ['name' => 'long_input', 'data' => ['answer' => str_repeat('A', 10000)]],
        ['name' => 'unicode', 'data' => ['answer' => '答え🦊']],
        ['name' => 'null_byte', 'data' => ['answer' => "test\x00injection"]],
        ['name' => 'negative_id', 'data' => ['id' => -1]],
        ['name' => 'special_chars', 'data' => ['answer' => '<>&"\'']],
    ];
    
    foreach ($edgeCases as $case) {
        $result = $this->sendRequest($url, $case['data']);
        // Prüfe auf saubere Fehlerbehandlung
    }
}
```

#### 4.1.2 Bessere DOM-Validierung

**Ziel:** Echte DOM-Parsing statt Regex-Matching.

**Aktuelles Problem:**
```php
// Aktuell: Regex-basiert (fehleranfällig)
$pattern = '/id=["\']quizModal["\']/i';
if (preg_match($pattern, $html)) { ... }
```

**Lösung: DOMDocument verwenden**
```php
private function testDomStructureV2($module, $html) {
    $dom = new DOMDocument();
    @$dom->loadHTML($html, LIBXML_NOERROR);
    $xpath = new DOMXPath($dom);
    
    $checks = [
        'quiz_modal' => '//*[@id="quizModal"]',
        'question_text' => '//*[@id="questionText"]',
        'options_container' => '//*[@id="optionsContainer"]',
        'submit_button' => '//button[contains(@onclick, "checkAnswer")]',
        'score_display' => '//*[contains(@class, "score")]',
    ];
    
    foreach ($checks as $name => $xpathQuery) {
        $nodes = $xpath->query($xpathQuery);
        $found = $nodes->length > 0;
        // Logging...
    }
}
```

#### 4.1.3 Screenshot-Funktion bei Fehlern

**Ziel:** Bei Testfehlern automatisch Screenshot für Debug-Zwecke erstellen.

**Technologie:** Headless Chrome via Puppeteer oder wkhtmltoimage

**Implementierung:**
```php
private function captureScreenshot($module, $errorType) {
    $url = $this->baseUrl . $this->learningPage . '?module=' . $module;
    $filename = 'error_' . $module . '_' . date('Ymd_His') . '.png';
    $outputPath = dirname(__DIR__) . '/logs/screenshots/' . $filename;
    
    // Option 1: wkhtmltoimage (leichtgewichtig)
    $cmd = "wkhtmltoimage --quality 80 '$url' '$outputPath' 2>&1";
    
    // Option 2: Puppeteer (Node.js erforderlich)
    // $cmd = "node /path/to/screenshot.js '$url' '$outputPath'";
    
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0) {
        $this->logger->info("📸 Screenshot gespeichert: $filename");
        return $outputPath;
    }
    return null;
}
```

#### 4.1.4 Performance-Metriken pro Test

**Ziel:** Detaillierte Zeitmessungen für jeden Test-Schritt.

**Metriken:**
- DNS Lookup Time
- Connection Time
- Time to First Byte (TTFB)
- Total Response Time
- DOM Parse Time

**Implementierung:**
```php
private function measurePerformance($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
    ]);
    
    $response = curl_exec($ch);
    
    $metrics = [
        'dns_time' => curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME) * 1000,
        'connect_time' => curl_getinfo($ch, CURLINFO_CONNECT_TIME) * 1000,
        'ttfb' => curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME) * 1000,
        'total_time' => curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000,
        'download_size' => curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD),
    ];
    
    curl_close($ch);
    
    // Performance-Schwellwerte
    $thresholds = [
        'ttfb' => 200,      // ms - Time to First Byte
        'total_time' => 500  // ms - Gesamtzeit
    ];
    
    foreach ($thresholds as $metric => $max) {
        if ($metrics[$metric] > $max) {
            $this->logger->warning("⚠️ $metric überschreitet Schwellwert");
        }
    }
    
    return $metrics;
}
```

#### 4.1.5 Parallele Modul-Tests

**Ziel:** Tests parallel ausführen für schnellere Durchlaufzeit.

**Aktuell:** Sequentiell (~30 Sekunden für 15 Module)  
**Ziel:** Parallel (~10 Sekunden)

**Implementierung mit pcntl_fork (Linux) oder Async HTTP:**
```php
// Option 1: Async HTTP mit curl_multi
private function runParallelTests($modules) {
    $multiHandle = curl_multi_init();
    $handles = [];
    
    foreach ($modules as $module) {
        $url = $this->baseUrl . $this->learningPage . '?module=' . $module;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout'],
        ]);
        curl_multi_add_handle($multiHandle, $ch);
        $handles[$module] = $ch;
    }
    
    // Alle parallel ausführen
    do {
        $status = curl_multi_exec($multiHandle, $running);
    } while ($running > 0);
    
    // Ergebnisse sammeln
    $results = [];
    foreach ($handles as $module => $ch) {
        $results[$module] = [
            'response' => curl_multi_getcontent($ch),
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        ];
        curl_multi_remove_handle($multiHandle, $ch);
    }
    
    curl_multi_close($multiHandle);
    return $results;
}
```

---

### 4.2 SecurityBot Erweiterungen


#### 4.2.1 CSRF-Token Tests

**Ziel:** Prüfen ob Formulare vor Cross-Site Request Forgery geschützt sind.

**Test-Szenarien:**
| Szenario | Beschreibung | Erwartetes Verhalten |
|----------|--------------|---------------------|
| Kein Token | Request ohne CSRF-Token | Ablehnung (403) |
| Falscher Token | Ungültiger Token | Ablehnung |
| Abgelaufener Token | Token aus alter Session | Ablehnung |
| Replay Attack | Wiederverwendeter Token | Ablehnung |

**Implementierung:**
```php
private function testCsrfProtection() {
    $this->logger->info("🛡️ Teste CSRF-Schutz...");
    
    // 1. Hole Formular mit Token
    $formResponse = $this->sendRequest($this->baseUrl . 'admin_v4.php');
    $token = $this->extractCsrfToken($formResponse);
    
    // 2. Test ohne Token
    $response = $this->sendRequest($this->baseUrl . 'admin_v4.php', [
        'action' => 'login',
        'password' => 'test'
    ]);
    
    if ($this->isSuccessResponse($response)) {
        $this->logVulnerability('CSRF_MISSING', 'HIGH', 'admin', [
            'suggestion' => 'CSRF-Token für alle state-changing Requests implementieren'
        ]);
    }
    
    // 3. Test mit falschem Token
    $response = $this->sendRequest($this->baseUrl . 'admin_v4.php', [
        'action' => 'login',
        'password' => 'test',
        'csrf_token' => 'invalid_token_12345'
    ]);
    
    if ($this->isSuccessResponse($response)) {
        $this->logVulnerability('CSRF_BYPASS', 'CRITICAL', 'admin', [
            'suggestion' => 'CSRF-Token-Validierung ist fehlerhaft'
        ]);
    }
}

private function extractCsrfToken($html) {
    if (preg_match('/name=["\']csrf_token["\'].*?value=["\']([^"\']+)["\']/i', $html, $m)) {
        return $m[1];
    }
    return null;
}
```

#### 4.2.2 Rate-Limiting Tests

**Ziel:** Prüfen ob die Anwendung vor Brute-Force-Angriffen geschützt ist.

**Test-Szenarien:**
| Szenario | Requests | Zeitraum | Erwartung |
|----------|----------|----------|-----------|
| Normal | 10 | 10 Sek | ✅ Erlaubt |
| Erhöht | 50 | 10 Sek | ⚠️ Warnung |
| Attacke | 100 | 10 Sek | ❌ Blockiert |

**Implementierung:**
```php
private function testRateLimiting() {
    $this->logger->info("⏱️ Teste Rate-Limiting...");
    
    $endpoints = [
        ['url' => 'admin_v4.php', 'params' => ['action' => 'login', 'password' => 'wrong']],
        ['url' => 'adaptive_learning.php', 'params' => ['action' => 'check_answer']],
    ];
    
    foreach ($endpoints as $endpoint) {
        $url = $this->baseUrl . $endpoint['url'];
        $successCount = 0;
        $startTime = microtime(true);
        
        // Sende 50 Requests in schneller Folge
        for ($i = 0; $i < 50; $i++) {
            $response = $this->sendRequest($url, $endpoint['params']);
            $httpCode = $this->lastHttpCode;
            
            if ($httpCode === 200) {
                $successCount++;
            } elseif ($httpCode === 429) {
                // Rate Limited - gut!
                $this->logger->success("   ✅ Rate-Limiting aktiv nach $i Requests");
                break;
            }
        }
        
        $duration = microtime(true) - $startTime;
        
        if ($successCount >= 50) {
            $this->logVulnerability('NO_RATE_LIMIT', 'HIGH', $endpoint['url'], [
                'requests_sent' => 50,
                'duration_sec' => round($duration, 2),
                'suggestion' => 'Rate-Limiting implementieren (z.B. 10 Requests/Min für Login)'
            ]);
        }
    }
}
```

#### 4.2.3 Authentication Bypass Tests

**Ziel:** Prüfen ob Authentifizierung umgangen werden kann.

**Test-Szenarien:**
| Vektor | Beschreibung | Payload |
|--------|--------------|---------|
| Direct Access | Admin-Seiten ohne Login | GET /admin_v4.php |
| Parameter Tampering | Manipulierte User-ID | `user_id=1` |
| Session Fixation | Vorgegebene Session-ID | `PHPSESSID=attacker` |
| Cookie Manipulation | Admin-Flag setzen | `is_admin=true` |

**Implementierung:**
```php
private function testAuthBypass() {
    $this->logger->info("🔓 Teste Authentication Bypass...");
    
    $protectedPages = [
        'admin_v4.php',
        'bots/bot_summary.php',
        'debug_users.php',
        'windows_ai_generator.php',
    ];
    
    foreach ($protectedPages as $page) {
        // Test 1: Direktzugriff ohne Session
        $ch = curl_init($this->baseUrl . $page);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false, // Keine Redirects folgen
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Erwartung: Redirect zu Login oder 401/403
        if ($httpCode === 200 && !$this->containsLoginForm($response)) {
            $this->logVulnerability('AUTH_BYPASS', 'CRITICAL', $page, [
                'method' => 'Direct Access',
                'suggestion' => 'Session-Check am Anfang der Datei hinzufügen'
            ]);
        }
        
        // Test 2: Cookie Manipulation
        $ch = curl_init($this->baseUrl . $page);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => 'is_admin=1; role=admin; user_id=1',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($this->containsAdminContent($response)) {
            $this->logVulnerability('AUTH_BYPASS_COOKIE', 'CRITICAL', $page, [
                'method' => 'Cookie Manipulation',
                'suggestion' => 'Cookies nicht für Authentifizierung verwenden'
            ]);
        }
    }
}
```

#### 4.2.4 Header Security Tests

**Ziel:** Prüfen ob sicherheitsrelevante HTTP-Header gesetzt sind.

**Wichtige Header:**
| Header | Empfohlener Wert | Zweck |
|--------|------------------|-------|
| X-Frame-Options | DENY oder SAMEORIGIN | Clickjacking-Schutz |
| X-Content-Type-Options | nosniff | MIME-Type Sniffing verhindern |
| X-XSS-Protection | 1; mode=block | Browser XSS-Filter aktivieren |
| Content-Security-Policy | script-src 'self' | XSS-Schutz |
| Strict-Transport-Security | max-age=31536000 | HTTPS erzwingen |
| Referrer-Policy | strict-origin | Referrer-Leak verhindern |

**Implementierung:**
```php
private function testSecurityHeaders() {
    $this->logger->info("📋 Teste Security Headers...");
    
    $ch = curl_init($this->baseUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
    ]);
    $headers = curl_exec($ch);
    curl_close($ch);
    
    $requiredHeaders = [
        'X-Frame-Options' => [
            'severity' => 'MEDIUM',
            'pattern' => '/X-Frame-Options:\s*(DENY|SAMEORIGIN)/i',
            'suggestion' => 'Header: X-Frame-Options: DENY in nginx.conf'
        ],
        'X-Content-Type-Options' => [
            'severity' => 'LOW',
            'pattern' => '/X-Content-Type-Options:\s*nosniff/i',
            'suggestion' => 'Header: X-Content-Type-Options: nosniff'
        ],
        'Content-Security-Policy' => [
            'severity' => 'MEDIUM',
            'pattern' => '/Content-Security-Policy:/i',
            'suggestion' => "Header: Content-Security-Policy: default-src 'self'"
        ],
    ];
    
    foreach ($requiredHeaders as $header => $config) {
        $this->stats['tests_total']++;
        
        if (preg_match($config['pattern'], $headers)) {
            $this->stats['tests_passed']++;
            $this->logger->success("   ✅ $header vorhanden");
        } else {
            $this->logVulnerability('MISSING_HEADER', $config['severity'], 'system', [
                'header' => $header,
                'suggestion' => $config['suggestion']
            ]);
        }
    }
}
```


#### 4.2.5 Cookie Security Tests

**Ziel:** Vollständige Prüfung aller Cookie-Sicherheitsflags.

**Cookie-Attribute:**
| Flag | Beschreibung | Wichtigkeit |
|------|--------------|-------------|
| HttpOnly | Kein JavaScript-Zugriff | 🔴 Kritisch |
| Secure | Nur über HTTPS | 🔴 Kritisch (Produktion) |
| SameSite | CSRF-Schutz | 🟠 Hoch |
| Path | Einschränkung auf Pfad | 🟡 Mittel |
| Expires/Max-Age | Session-Timeout | 🟡 Mittel |

**Implementierung:**
```php
private function testCookieSecurity() {
    $this->logger->info("🍪 Teste Cookie Security...");
    
    $ch = curl_init($this->baseUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    // Alle Set-Cookie Header extrahieren
    preg_match_all('/Set-Cookie:\s*([^\r\n]+)/i', $response, $cookies);
    
    foreach ($cookies[1] as $cookie) {
        $name = explode('=', $cookie)[0];
        
        // HttpOnly Check
        if (stripos($cookie, 'HttpOnly') === false) {
            $this->logVulnerability('COOKIE_NO_HTTPONLY', 'HIGH', $name, [
                'cookie' => $cookie,
                'suggestion' => 'session.cookie_httponly = 1 in php.ini'
            ]);
        }
        
        // Secure Check (nur für Produktion relevant)
        if (stripos($cookie, 'Secure') === false) {
            $this->stats['tests_total']++;
            $this->logger->warning("   ⚠️ Cookie '$name' ohne Secure-Flag (OK für localhost)");
        }
        
        // SameSite Check
        if (stripos($cookie, 'SameSite') === false) {
            $this->logVulnerability('COOKIE_NO_SAMESITE', 'MEDIUM', $name, [
                'suggestion' => 'session.cookie_samesite = Strict in php.ini'
            ]);
        } else {
            $this->stats['tests_passed']++;
            $this->logger->success("   ✅ Cookie '$name' hat SameSite-Flag");
        }
    }
}
```

#### 4.2.6 File Upload Vulnerability Tests

**Ziel:** Prüfen ob File-Uploads sicher implementiert sind.

**Test-Szenarien:**
| Angriff | Beschreibung | Payload |
|---------|--------------|---------|
| PHP Shell | Ausführbarer Code | `shell.php` |
| Double Extension | Bypass | `image.php.jpg` |
| Null Byte | Alte PHP-Versionen | `shell.php%00.jpg` |
| SVG XSS | Script in SVG | `<svg onload=alert(1)>` |
| MIME Type Bypass | Falscher Content-Type | `image/jpeg` für PHP |

**Implementierung:**
```php
private function testFileUpload() {
    $this->logger->info("📤 Teste File Upload Sicherheit...");
    
    // Suche nach Upload-Endpunkten
    $uploadEndpoints = [
        'zeichnen/save_drawing.php',
        'admin_v4.php?action=upload',
    ];
    
    $maliciousFiles = [
        [
            'name' => 'shell.php',
            'content' => '<?php echo "VULNERABLE"; ?>',
            'type' => 'application/x-php',
            'expected' => 'reject'
        ],
        [
            'name' => 'image.php.jpg',
            'content' => '<?php echo "VULNERABLE"; ?>',
            'type' => 'image/jpeg',
            'expected' => 'reject'
        ],
        [
            'name' => 'xss.svg',
            'content' => '<svg><script>alert(1)</script></svg>',
            'type' => 'image/svg+xml',
            'expected' => 'sanitize'
        ],
    ];
    
    foreach ($uploadEndpoints as $endpoint) {
        $url = $this->baseUrl . $endpoint;
        
        foreach ($maliciousFiles as $file) {
            $this->stats['tests_total']++;
            
            // Multipart Form Upload simulieren
            $boundary = 'boundary' . md5(time());
            $body = "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$file['name']}\"\r\n";
            $body .= "Content-Type: {$file['type']}\r\n\r\n";
            $body .= $file['content'] . "\r\n";
            $body .= "--$boundary--\r\n";
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: multipart/form-data; boundary=$boundary"
                ],
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Prüfe ob Upload akzeptiert wurde
            if ($httpCode === 200 && strpos($response, 'success') !== false) {
                $this->logVulnerability('UNSAFE_FILE_UPLOAD', 'CRITICAL', $endpoint, [
                    'file' => $file['name'],
                    'suggestion' => 'Whitelist für erlaubte Dateitypen, Inhalt validieren'
                ]);
            } else {
                $this->stats['tests_passed']++;
            }
        }
    }
}
```

---

## 5. Detaillierter Umsetzungsplan

### Phase 1: FunctionTestBot Erweiterungen (3-4h)

| Schritt | Task | Aufwand | Abhängigkeiten |
|---------|------|---------|----------------|
| 1.1 | Edge Case Tests implementieren | 45 Min | - |
| 1.2 | DOM-Validierung mit DOMDocument | 30 Min | - |
| 1.3 | Performance-Metriken hinzufügen | 30 Min | - |
| 1.4 | Screenshot-Funktion (wkhtmltoimage) | 45 Min | Tool installieren |
| 1.5 | Parallele Ausführung (curl_multi) | 45 Min | - |
| 1.6 | Tests & Bug-Fixes | 30 Min | 1.1-1.5 |

### Phase 2: SecurityBot Erweiterungen (3-4h)

| Schritt | Task | Aufwand | Abhängigkeiten |
|---------|------|---------|----------------|
| 2.1 | CSRF-Token Tests | 30 Min | - |
| 2.2 | Rate-Limiting Tests | 30 Min | - |
| 2.3 | Authentication Bypass Tests | 45 Min | - |
| 2.4 | Header Security Tests | 30 Min | - |
| 2.5 | Cookie Security erweitern | 30 Min | - |
| 2.6 | File Upload Tests | 45 Min | - |
| 2.7 | Tests & Bug-Fixes | 30 Min | 2.1-2.6 |

### Phase 3: Integration & Dokumentation (1h)

| Schritt | Task | Aufwand |
|---------|------|---------|
| 3.1 | Bot-Versionen aktualisieren | 15 Min |
| 3.2 | README.md aktualisieren | 15 Min |
| 3.3 | Status-Report aktualisieren | 15 Min |
| 3.4 | Git Commit & Push | 15 Min |

---

## 6. Priorisierung & Zeitschätzung

### Prioritäts-Matrix

| Feature | Impact | Aufwand | Priorität |
|---------|--------|---------|-----------|
| CSRF-Token Tests | 🔴 Hoch | 30 Min | ⭐⭐⭐⭐⭐ |
| Rate-Limiting Tests | 🔴 Hoch | 30 Min | ⭐⭐⭐⭐⭐ |
| Header Security Tests | 🟠 Mittel | 30 Min | ⭐⭐⭐⭐ |
| Auth Bypass Tests | 🟠 Mittel | 45 Min | ⭐⭐⭐⭐ |
| Edge Case Tests | 🟡 Mittel | 45 Min | ⭐⭐⭐ |
| Cookie Security | 🟡 Mittel | 30 Min | ⭐⭐⭐ |
| Performance-Metriken | 🟢 Niedrig | 30 Min | ⭐⭐ |
| DOM-Validierung | 🟢 Niedrig | 30 Min | ⭐⭐ |
| Screenshots | 🟢 Niedrig | 45 Min | ⭐ |
| Parallele Tests | 🟢 Niedrig | 45 Min | ⭐ |
| File Upload Tests | 🟢 Niedrig | 45 Min | ⭐ |

### Empfohlene Reihenfolge

1. **Sofort (Sicherheitskritisch):**
   - CSRF-Token Tests
   - Rate-Limiting Tests
   
2. **Diese Woche:**
   - Header Security Tests
   - Auth Bypass Tests
   - Cookie Security

3. **Später:**
   - Edge Case Tests
   - Performance-Metriken
   - DOM-Validierung
   - Screenshots
   - Parallele Tests
   - File Upload Tests

---

## 7. Technische Spezifikationen

### Dateien-Übersicht nach Änderungen

```
bots/
├── tests/
│   ├── FunctionTestBot.php    # v1.5 → v1.6 (+150 Zeilen)
│   ├── SecurityBot.php        # v1.4 → v1.5 (+250 Zeilen)
│   └── payloads/              # NEU: Ausgelagerte Payloads
│       ├── sql_injection.json
│       ├── xss.json
│       └── path_traversal.json
├── logs/
│   └── screenshots/           # NEU: Screenshot-Ordner
└── bot_config.php             # NEU: Zentrale Bot-Konfiguration
```

### Neue Abhängigkeiten

| Tool | Zweck | Installation |
|------|-------|--------------|
| wkhtmltoimage | Screenshots | `apt install wkhtmltopdf` |
| DOMDocument | DOM-Parsing | PHP-Extension (bereits vorhanden) |

### Konfigurationsoptionen (bot_config.php)

```php
return [
    'function_test' => [
        'parallel_enabled' => true,
        'max_parallel' => 5,
        'screenshot_on_error' => true,
        'edge_case_tests' => true,
        'performance_thresholds' => [
            'ttfb' => 200,      // ms
            'total' => 500,     // ms
        ],
    ],
    'security' => [
        'csrf_test_enabled' => true,
        'rate_limit_test_enabled' => true,
        'rate_limit_threshold' => 50,
        'header_checks' => ['X-Frame-Options', 'CSP', 'HSTS'],
        'file_upload_test' => false,  // Nur bei Bedarf aktivieren
    ],
];
```

---

## 8. Anhang: Code-Templates

### Template A: Neuer Test in FunctionTestBot

```php
/**
 * Test X: [Beschreibung]
 * @since v1.6
 */
private function testXXX($module) {
    $this->stats['total']++;
    
    try {
        // Test-Logik hier
        $result = $this->performTest($module);
        
        if ($result['success']) {
            $this->stats['passed']++;
            $this->logger->success("   ✅ [Test-Name] OK", [
                'module' => $module,
                'test' => 'xxx'
            ]);
        } else {
            $this->stats['failed']++;
            $this->logger->error("   ❌ [Test-Name] FEHLER", [
                'module' => $module,
                'test' => 'xxx',
                'error' => $result['error'],
                'suggestion' => '[Lösungsvorschlag]'
            ]);
        }
    } catch (Exception $e) {
        $this->stats['failed']++;
        $this->logger->error("   ❌ Exception: " . $e->getMessage());
    }
    
    return $result['success'] ?? false;
}
```

### Template B: Neue Vulnerability in SecurityBot

```php
/**
 * Phase X: [Sicherheitstest]
 * @since v1.5
 */
private function testXXX() {
    $this->logger->info("");
    $this->logger->info("═══ PHASE X: [Name] Tests ═══");
    
    // Payloads/Testfälle
    $testCases = [
        ['name' => 'Test 1', 'payload' => '...'],
        ['name' => 'Test 2', 'payload' => '...'],
    ];
    
    foreach ($testCases as $test) {
        $this->stats['tests_total']++;
        
        $response = $this->sendRequest($url, $test['payload']);
        
        if ($this->isVulnerable($response, $test)) {
            $this->logVulnerability('XXX_VULN', 'HIGH', 'target', [
                'test' => $test['name'],
                'payload' => $test['payload'],
                'suggestion' => '[Behebung]'
            ]);
        } else {
            $this->stats['tests_passed']++;
            $this->logger->success("   ✅ {$test['name']} geschützt");
        }
    }
}
```

---

## Zusammenfassung & Nächste Schritte

### Sofort umsetzen (High Priority)

1. **SecurityBot: CSRF-Token Tests** - 30 Min
2. **SecurityBot: Rate-Limiting Tests** - 30 Min

### Diese Session empfohlen

3. **SecurityBot: Header Security Tests** - 30 Min
4. **SecurityBot: Auth Bypass Tests** - 45 Min
5. **SecurityBot: Cookie Security** - 30 Min

### Gesamtaufwand heute: ~3 Stunden

---

**Dokument erstellt:** 08.12.2025  
**Nächstes Review:** Nach Implementierung  
**Verantwortlich:** sgiT Development Team

