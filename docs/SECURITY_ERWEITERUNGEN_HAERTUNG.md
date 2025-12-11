# sgiT Education Platform
## Erweiterungen & Härtung - Analyse und Empfehlungen

**Version:** 3.22.0  
**Analysedatum:** 09. Dezember 2025  
**Autor:** Claude / sgiT Solution Engineering  

---

## 1. Executive Summary

Die sgiT Education Platform befindet sich in einem **soliden Sicherheitszustand** für eine Entwicklungsumgebung. Die Analyse zeigt:

| Kategorie | Status | Bewertung |
|-----------|--------|-----------|
| Basis-Sicherheit | ✅ Implementiert | Gut |
| Input-Validierung | ✅ Vorhanden | Gut |
| Session-Management | ✅ Sicher konfiguriert | Gut |
| Rate-Limiting | ✅ Implementiert | Gut |
| HTTP-Security-Headers | ✅ Gesetzt | Sehr gut |
| Authentifizierung | ⚠️ Verbesserungspotenzial | Mittel |
| API-Sicherheit | ⚠️ Teilweise offen | Mittel |
| Datenverschlüsselung | ❌ Nicht implementiert | Niedrig |

**Gesamtbewertung: 7/10** - Produktionsreif nach Umsetzung der kritischen Empfehlungen.

---

## 2. Aktuelle Sicherheitsinfrastruktur

### 2.1 Vorhandene Sicherheitsmaßnahmen

#### `/includes/security.php` - Zentrale Sicherheitsfunktionen
```
✅ XSS-Schutz via esc(), esc_attr(), esc_url()
✅ CSRF-Token Generation und Validierung
✅ Sichere Session-Cookie-Parameter (HttpOnly, SameSite)
✅ Sichere Integer-Konvertierung
✅ Security-Event-Logging
```

#### `/includes/security_headers.php` - HTTP Security Headers
```
✅ X-Frame-Options: SAMEORIGIN (Clickjacking-Schutz)
✅ X-Content-Type-Options: nosniff
✅ X-XSS-Protection: 1; mode=block
✅ Content-Security-Policy (mit CDN-Whitelist)
✅ Referrer-Policy: strict-origin-when-cross-origin
✅ Permissions-Policy: geolocation=(), microphone=(), camera=()
```

#### `/includes/rate_limiter.php` - Brute-Force-Schutz
```
✅ Session-basiertes Rate-Limiting
✅ Konfigurierbare Limits (Requests/Zeitfenster)
✅ HTTP 429 Response mit Retry-After Header
✅ AJAX-kompatible Fehlerbehandlung
```

#### Docker/nginx Konfiguration
```
✅ Server-Token versteckt (server_tokens off)
✅ Security Headers auf nginx-Ebene
✅ Direkter DB-Zugriff blockiert (*.db, *.sqlite)
✅ Versteckte Dateien blockiert (/.)
✅ Sensible Dateien blockiert (*.md, *.log, *.sh, *.bat)
```

### 2.2 Identifizierte Schwachstellen

| ID | Schwachstelle | Schweregrad | Datei |
|----|---------------|-------------|-------|
| SEC-001 | Hardcodiertes Admin-Passwort | KRITISCH | admin_v4.php |
| SEC-002 | CORS zu permissiv (Access-Control-Allow-Origin: *) | MITTEL | api/flag_question.php |
| SEC-003 | API-Endpoints ohne Authentifizierung | HOCH | api/flag_question.php |
| SEC-004 | SQLite-Datenbanken unverschlüsselt | NIEDRIG | AI/data/*.db |
| SEC-005 | Backup-Dateien ohne Verschlüsselung | NIEDRIG | backups/*.zip |
| SEC-006 | CSRF nicht überall implementiert | MITTEL | Diverse |

---

## 3. Härtungsempfehlungen

### 3.1 KRITISCH - Sofort umsetzen

#### SEC-001: Admin-Passwort aus Code entfernen

**Aktuell (admin_v4.php:26):**
```php
define('ADMIN_PASSWORD', 'sgit2025');
```

**Empfehlung:**
```php
// Option A: Environment Variable
define('ADMIN_PASSWORD', getenv('SGIT_ADMIN_PASSWORD') ?: die('ADMIN_PASSWORD not set'));

// Option B: Separate Config-Datei (nicht in Git!)
// /config/admin_credentials.php (in .gitignore!)
<?php
return [
    'password_hash' => '$2y$10$...' // password_hash('sicheres_passwort', PASSWORD_DEFAULT)
];

// admin_v4.php
$credentials = require __DIR__ . '/config/admin_credentials.php';
if (password_verify($_POST['admin_password'], $credentials['password_hash'])) {
    // Login OK
}
```

**Aufwand:** ~1h | **Priorität:** SOFORT

---

#### SEC-003: API-Authentifizierung implementieren

**Aktuell (api/flag_question.php):**
```php
// Keine Authentifizierung - jeder kann Fragen flaggen/löschen
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Admin-Aktionen ohne Auth!
}
```

**Empfehlung - API-Token-System:**
```php
// /includes/api_auth.php
class ApiAuth {
    public static function requireAdmin(): void {
        session_start();
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
    }
    
    public static function requireUser(): void {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Login required']);
            exit;
        }
    }
}

// api/flag_question.php - Angepasst
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    ApiAuth::requireAdmin();
    // ... Rest des Codes
}
```

**Aufwand:** ~2h | **Priorität:** HOCH

---

### 3.2 HOCH - Diese Woche umsetzen

#### SEC-002: CORS-Policy verschärfen

**Aktuell:**
```php
header('Access-Control-Allow-Origin: *');
```

**Empfehlung:**
```php
// Nur eigene Origin erlauben
$allowed_origins = [
    'http://localhost:8080',
    'https://education.sgit.space' // Produktions-Domain
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
```

---

#### SEC-006: CSRF flächendeckend implementieren

**Neue Middleware erstellen:**
```php
// /includes/csrf_middleware.php
function require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            die(json_encode(['error' => 'CSRF token invalid']));
        }
    }
}

// JavaScript für AJAX-Requests
// assets/js/csrf.js
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': csrfToken,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
});
```

---

### 3.3 MITTEL - Innerhalb 2 Wochen

#### SEC-004: SQLite-Verschlüsselung (SQLCipher)

```bash
# Installation
docker exec sgit_php pecl install sqlcipher

# Verwendung
$db = new SQLite3('/path/to/db.db', SQLITE3_OPEN_READWRITE, 'encryption_key');
```

**Alternative - Application-Level Encryption:**
```php
// Sensible Felder verschlüsseln
class FieldEncryption {
    private static string $key = ''; // Aus ENV laden!
    
    public static function encrypt(string $data): string {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-GCM', self::$key, 0, $iv, $tag);
        return base64_encode($iv . $tag . $encrypted);
    }
    
    public static function decrypt(string $data): string {
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);
        return openssl_decrypt($encrypted, 'AES-256-GCM', self::$key, 0, $iv, $tag);
    }
}
```

---

#### SEC-005: Backup-Verschlüsselung

```php
// backup_manager.php - Erweitert
function createEncryptedBackup(string $source, string $destination, string $password): bool {
    // ZIP erstellen
    $zipPath = $destination . '.zip';
    // ... bestehende ZIP-Logik ...
    
    // Mit GPG verschlüsseln
    $encryptedPath = $destination . '.zip.gpg';
    $cmd = sprintf(
        'gpg --symmetric --cipher-algo AES256 --passphrase %s --batch -o %s %s',
        escapeshellarg($password),
        escapeshellarg($encryptedPath),
        escapeshellarg($zipPath)
    );
    exec($cmd, $output, $returnCode);
    
    // Original-ZIP löschen
    unlink($zipPath);
    
    return $returnCode === 0;
}
```

---

## 4. Erweiterungsempfehlungen

### 4.1 Zwei-Faktor-Authentifizierung (2FA)

**Implementierungskonzept:**

```php
// /includes/TwoFactorAuth.php
class TwoFactorAuth {
    public static function generateSecret(): string {
        return Base32::encode(random_bytes(20));
    }
    
    public static function getQRCodeUrl(string $secret, string $label): string {
        $otpauth = sprintf(
            'otpauth://totp/%s?secret=%s&issuer=sgiT%%20Education',
            urlencode($label),
            $secret
        );
        return 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($otpauth);
    }
    
    public static function verify(string $secret, string $code): bool {
        $timestamp = floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            $expected = self::generateCode($secret, $timestamp + $i);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }
        return false;
    }
}
```

**Aufwand:** ~4-6h | **Nutzen:** Deutlich erhöhte Admin-Sicherheit

---

### 4.2 Audit-Logging-System

**Umfassendes Logging für Compliance und Debugging:**

```php
// /includes/AuditLogger.php
class AuditLogger {
    private static PDO $db;
    
    public static function log(string $action, array $context = []): void {
        $stmt = self::$db->prepare("
            INSERT INTO audit_log (timestamp, action, user_id, ip, user_agent, context)
            VALUES (datetime('now'), :action, :user_id, :ip, :ua, :context)
        ");
        
        $stmt->execute([
            ':action' => $action,
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':ip' => $_SERVER['REMOTE_ADDR'],
            ':ua' => $_SERVER['HTTP_USER_AGENT'],
            ':context' => json_encode($context)
        ]);
    }
}

// Verwendung
AuditLogger::log('ADMIN_LOGIN', ['method' => '2FA']);
AuditLogger::log('QUESTION_DELETED', ['question_id' => 123, 'reason' => 'duplicate']);
AuditLogger::log('WALLET_WITHDRAW', ['child_id' => 5, 'amount' => 100]);
```

**Neue Tabelle:**
```sql
CREATE TABLE audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp DATETIME NOT NULL,
    action TEXT NOT NULL,
    user_id INTEGER,
    ip TEXT,
    user_agent TEXT,
    context TEXT,
    INDEX idx_timestamp (timestamp),
    INDEX idx_action (action)
);
```

---

### 4.3 Content Security Policy (CSP) Reporting

**CSP-Violations überwachen:**

```php
// /api/csp_report.php
<?php
$report = file_get_contents('php://input');
$data = json_decode($report, true);

if ($data && isset($data['csp-report'])) {
    $logEntry = sprintf(
        "[%s] CSP Violation: %s blocked %s on %s\n",
        date('Y-m-d H:i:s'),
        $data['csp-report']['violated-directive'],
        $data['csp-report']['blocked-uri'],
        $data['csp-report']['document-uri']
    );
    file_put_contents(__DIR__ . '/../bots/logs/csp_violations.log', $logEntry, FILE_APPEND);
}

// Header anpassen
header("Content-Security-Policy: ... ; report-uri /api/csp_report.php");
```

---

### 4.4 Automatisiertes Security-Monitoring

**Erweiterung des SecurityBots:**

```php
// Neuer Cron-Job für regelmäßige Scans
// cron: 0 3 * * * /usr/bin/php /var/www/html/bots/cron_security_scan.php

// /bots/cron_security_scan.php
<?php
require_once __DIR__ . '/tests/SecurityBot.php';

$bot = new SecurityBot([
    'verbose' => false,
    'maxPayloadsPerTest' => 3  // Schneller Scan
]);

$results = $bot->run();

// Bei kritischen Funden: E-Mail-Alert
if ($results['stats']['critical'] > 0) {
    mail(
        'admin@sgit.space',
        '[ALERT] sgiT Education Security Issue',
        "Critical vulnerabilities found:\n\n" . json_encode($results['vulnerabilities'], JSON_PRETTY_PRINT)
    );
}
```

---

## 5. Priorisierte Roadmap

### Phase 1: Kritisch (Diese Woche)
| Task | Aufwand | Priorität |
|------|---------|-----------|
| Admin-Passwort hashen & externalisieren | 1h | 🔴 KRITISCH |
| API-Authentifizierung für DELETE-Endpoints | 2h | 🔴 KRITISCH |
| CORS-Policy verschärfen | 30min | 🟠 HOCH |

### Phase 2: Wichtig (Woche 2)
| Task | Aufwand | Priorität |
|------|---------|-----------|
| CSRF flächendeckend | 3h | 🟠 HOCH |
| Audit-Logging implementieren | 4h | 🟠 HOCH |
| Rate-Limiting für alle APIs | 2h | 🟡 MITTEL |

### Phase 3: Empfohlen (Woche 3-4)
| Task | Aufwand | Priorität |
|------|---------|-----------|
| 2FA für Admin | 4-6h | 🟡 MITTEL |
| Backup-Verschlüsselung | 2h | 🟡 MITTEL |
| CSP Reporting | 2h | 🟢 NIEDRIG |

### Phase 4: Nice-to-Have (Langfristig)
| Task | Aufwand | Priorität |
|------|---------|-----------|
| SQLite-Verschlüsselung | 4h | 🟢 NIEDRIG |
| Automatisiertes Security-Monitoring | 3h | 🟢 NIEDRIG |
| Penetration Testing | Extern | 🟢 NIEDRIG |

---

## 6. Feature-Erweiterungen

### 6.1 Geplante Module (aus Status-Report)

| Feature | Aufwand | Beschreibung |
|---------|---------|--------------|
| 🧩 Sudoku | ~4-6h | 4x4 für Kids, 9x9 für Ältere |
| ♟️ Schach | ~6-8h | Puzzles, Grundregeln |
| 🍳 Basis-Rezepte | ~3-4h | 10 einfache Gerichte |
| ✏️ Zeichnen erweitern | ~6-8h | Brushes, Ebenen, Farbkreis |
| 🎯 50% Joker | ~3-4h | 2 falsche Antworten streichen |
| ₿ BTCPay Integration | ~4h | Echte Bitcoin-Auszahlung |

### 6.2 Neue Erweiterungsideen

| Feature | Aufwand | Beschreibung |
|---------|---------|--------------|
| 📱 PWA-Support | ~4h | Offline-Modus, App-Icon |
| 🔔 Push-Notifications | ~6h | Erinnerungen, Achievements |
| 👨‍👩‍👧‍👦 Familien-Dashboard | ~8h | Eltern sehen Fortschritt aller Kinder |
| 📊 Lernanalysen | ~6h | Schwächen erkennen, Empfehlungen |
| 🎮 Multiplayer-Quiz | ~10h | Kinder treten gegeneinander an |
| 🏅 Wettbewerbs-Modus | ~4h | Wöchentliche Challenges |

---

## 7. Zusammenfassung

### Sofort-Maßnahmen (< 4h Aufwand)
1. ✅ Admin-Passwort hashen
2. ✅ API-Auth für Admin-Endpoints
3. ✅ CORS-Policy verschärfen

### Kurzfristig (1-2 Wochen)
1. CSRF-Schutz vervollständigen
2. Audit-Logging implementieren
3. Rate-Limiting erweitern

### Mittelfristig (1 Monat)
1. 2FA für Admin
2. Backup-Verschlüsselung
3. Automatisiertes Monitoring

Die Plattform ist für den aktuellen Entwicklungsstand **gut aufgestellt**. Die kritischen Punkte (hardcodiertes Passwort, offene API-Endpoints) sollten vor einem Produktiv-Einsatz unbedingt behoben werden.

---

*Dokument erstellt: 09. Dezember 2025*  
*Nächste Review empfohlen: Januar 2025*
