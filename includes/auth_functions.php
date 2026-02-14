<?php
/**
 * ============================================================================
 * sgiT Education Platform - Auth Functions (Gehashed)
 * ============================================================================
 * 
 * Zentrale Authentifizierungs-Funktionen für alle Admin-Bereiche.
 * 
 * Features:
 * - Sichere Passwort-Verifizierung mit password_verify()
 * - Passwort-Stärke Validierung
 * - Hash-Generierung für neue Passwörter
 * - Legacy-Fallback für Migration
 * - Audit-Logging
 * 
 * @version 1.0
 * @date 21.12.2025
 * @author sgiT Solution Engineering
 * ============================================================================
 */

require_once __DIR__ . '/auth_config.php';

/**
 * Verifiziert ein Passwort gegen den gespeicherten Hash
 * 
 * @param string $password Das zu prüfende Passwort
 * @return bool True wenn korrekt, sonst false
 */
function verifyAdminPassword($password) {
    // Legacy-Fallback für Migration (temporär!)
    if (defined('USE_LEGACY_AUTH') && USE_LEGACY_AUTH === true) {
        logAuthAttempt('legacy_mode_used', false);
        return $password === ADMIN_PASSWORD_LEGACY;
    }
    
    // Moderne Hash-Verifizierung
    $result = password_verify($password, ADMIN_PASSWORD_HASH);
    
    // Logging
    logAuthAttempt($result ? 'success' : 'failed', $result);
    
    return $result;
}

/**
 * Generiert einen neuen Passwort-Hash
 * 
 * @param string $password Das Passwort im Klartext
 * @return string Der generierte Hash
 */
function generatePasswordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Validiert Passwort-Stärke
 * 
 * @param string $password Das zu validierende Passwort
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    // Mindestlänge
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = sprintf('Passwort muss mindestens %d Zeichen lang sein', PASSWORD_MIN_LENGTH);
    }
    
    // Zahlen erforderlich?
    if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/\d/', $password)) {
        $errors[] = 'Passwort muss mindestens eine Zahl enthalten';
    }
    
    // Sonderzeichen erforderlich?
    if (defined('PASSWORD_REQUIRE_SPECIAL') && PASSWORD_REQUIRE_SPECIAL) {
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Passwort muss mindestens ein Sonderzeichen enthalten';
        }
    }
    
    // Großbuchstaben empfohlen (Warnung, kein Fehler)
    $warnings = [];
    if (!preg_match('/[A-Z]/', $password)) {
        $warnings[] = 'Empfehlung: Passwort sollte Großbuchstaben enthalten';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * Prüft ob Hash neu generiert werden sollte (Rehashing)
 * 
 * @param string $hash Der aktuelle Hash
 * @return bool True wenn Rehashing empfohlen
 */
function needsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_DEFAULT);
}

/**
 * Loggt Authentifizierungs-Versuche (optional)
 * 
 * @param string $event Art des Events (success, failed, etc.)
 * @param bool $success Erfolg/Misserfolg
 */
function logAuthAttempt($event, $success) {
    // Optional: Audit-Log in Datei oder DB
    $logFile = __DIR__ . '/../logs/auth_audit.log';
    
    if (!is_dir(dirname($logFile))) {
        @mkdir(dirname($logFile), 0755, true);
    }
    
    $entry = sprintf(
        "[%s] Event: %s | Success: %s | IP: %s | User-Agent: %s\n",
        date('Y-m-d H:i:s'),
        $event,
        $success ? 'YES' : 'NO',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100)
    );
    
    @file_put_contents($logFile, $entry, FILE_APPEND);
}

/**
 * Generiert einen sicheren Session-Token
 * 
 * @return string Ein kryptographisch sicherer Token
 */
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Setzt Admin-Session nach erfolgreicher Authentifizierung
 * 
 * @param string $sessionKey Der Session-Key (z.B. 'is_admin')
 */
function setAdminSession($sessionKey = 'is_admin') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION[$sessionKey] = true;
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Optional: Session-Token für zusätzliche Sicherheit
    if (!isset($_SESSION['admin_token'])) {
        $_SESSION['admin_token'] = generateSessionToken();
    }
}

/**
 * Prüft ob Admin-Session gültig ist
 * 
 * @param string $sessionKey Der Session-Key (z.B. 'is_admin')
 * @return bool True wenn gültige Session
 */
function isAdminLoggedIn($sessionKey = 'is_admin') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] === true;
}

/**
 * Beendet Admin-Session
 * 
 * @param string $sessionKey Der Session-Key (z.B. 'is_admin')
 */
function logoutAdmin($sessionKey = 'is_admin') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    unset($_SESSION[$sessionKey]);
    unset($_SESSION['admin_login_time']);
    unset($_SESSION['admin_ip']);
    unset($_SESSION['admin_token']);
    
    logAuthAttempt('logout', true);
}

/**
 * Gibt Metadaten über aktuelles Passwort zurück
 * 
 * @return array Passwort-Metadaten
 */
function getPasswordMetadata() {
    return [
        'last_changed' => PASSWORD_LAST_CHANGED,
        'changed_by' => PASSWORD_CHANGED_BY,
        'hash_algo' => 'bcrypt',
        'needs_rehash' => needsRehash(ADMIN_PASSWORD_HASH),
        'legacy_mode' => defined('USE_LEGACY_AUTH') && USE_LEGACY_AUTH === true
    ];
}

/**
 * Info-Funktion für Debugging (nur in Development!)
 * 
 * @return array System-Info
 */
function getAuthSystemInfo() {
    return [
        'version' => '1.0',
        'hash_algorithm' => PASSWORD_DEFAULT,
        'min_password_length' => PASSWORD_MIN_LENGTH,
        'require_numbers' => PASSWORD_REQUIRE_NUMBERS,
        'require_special' => PASSWORD_REQUIRE_SPECIAL ?? false,
        'session_active' => session_status() === PHP_SESSION_ACTIVE,
        'metadata' => getPasswordMetadata()
    ];
}
