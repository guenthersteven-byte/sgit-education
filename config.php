<?php
// Security Headers zuerst laden (vor jeglicher Ausgabe)
require_once __DIR__ . '/includes/security_headers.php';

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Session-Konfiguration
ini_set('session.gc_maxlifetime', 3600);

// Konstanten
define('BASE_PATH', dirname(__FILE__));
define('DEFAULT_AGE', 7);
define('POINTS_PER_ANSWER', 10);

// Session initialisieren
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['scores'])) {
        $_SESSION['scores'] = [
            'math' => 0,
            'reading' => 0,
            'science' => 0,
            'geography' => 0
        ];
    }
}
?>
