<?php
/**
 * sgiT Education Platform - Security Functions
 * 
 * Zentrale Sicherheitsfunktionen für XSS-Schutz, Input-Validierung etc.
 * 
 * @package sgiT_Education
 * @version 1.0.0
 * @date 2024-12-01
 * @author deStevie / sgiT Solution Engineering & IT Services
 */

/**
 * Sichere Ausgabe eines Strings (XSS-Schutz)
 * 
 * @param string $string Der zu escapende String
 * @return string Der gesicherte String
 */
function esc($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sichere Ausgabe eines Strings für HTML-Attribute
 * 
 * @param string $string Der zu escapende String
 * @return string Der gesicherte String
 */
function esc_attr($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sichere Ausgabe eines Strings für URLs
 * 
 * @param string $string Der zu encodende String
 * @return string Der gesicherte String
 */
function esc_url($string) {
    return filter_var($string, FILTER_SANITIZE_URL);
}

/**
 * Validiert einen Feedback-Parameter (nur erlaubte Werte)
 * 
 * @param string $feedback Der Feedback-Wert
 * @return string|null Der validierte Wert oder null
 */
function validate_feedback($feedback) {
    $allowed = ['correct', 'wrong'];
    return in_array($feedback, $allowed) ? $feedback : null;
}

/**
 * Sichere Integer-Konvertierung
 * 
 * @param mixed $value Der Wert
 * @param int $default Standard-Wert
 * @return int Der Integer-Wert
 */
function safe_int($value, $default = 0) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : $default;
}

/**
 * Sicheres GET-Parameter abrufen
 * 
 * @param string $key Der Parameter-Name
 * @param mixed $default Standard-Wert
 * @return string Der gesicherte Wert
 */
function get_param($key, $default = '') {
    return isset($_GET[$key]) ? esc($_GET[$key]) : $default;
}

/**
 * Sicheres POST-Parameter abrufen
 * 
 * @param string $key Der Parameter-Name
 * @param mixed $default Standard-Wert
 * @return string Der gesicherte Wert
 */
function post_param($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

/**
 * Generiert einen CSRF-Token
 * 
 * @return string Der Token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Prüft einen CSRF-Token
 * 
 * @param string $token Der zu prüfende Token
 * @return bool True wenn gültig
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rendert ein verstecktes CSRF-Feld
 */
function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . esc_attr(generate_csrf_token()) . '">';
}

/**
 * Setzt sichere Session-Cookie-Parameter
 */
function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        // Sichere Cookie-Einstellungen
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

/**
 * Loggt einen Sicherheitsvorfall
 * 
 * @param string $type Typ des Vorfalls
 * @param string $message Beschreibung
 * @param array $context Zusätzlicher Kontext
 */
function log_security_event($type, $message, $context = []) {
    $log_entry = sprintf(
        "[%s] [%s] %s | IP: %s | Context: %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($type),
        $message,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        json_encode($context)
    );
    
    $log_file = __DIR__ . '/../bots/logs/security_events.log';
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
