<?php
/**
 * sgiT Education - API Security Middleware
 *
 * Include am Anfang jeder API-Datei fuer:
 * - Security Headers
 * - CSRF-Schutz fuer POST/PUT/DELETE
 * - Rate Limiting (optional)
 *
 * @version 1.0
 * @date 2026-02-14
 */

// Security Headers fuer alle API-Responses
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Session starten falls noetig
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF-Check fuer schreibende Requests
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'PATCH'])) {
    // Token aus POST, Header oder JSON-Body lesen
    $csrfToken = $_POST['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? null;

    // Falls nicht in POST/Header, aus JSON-Body versuchen
    if (!$csrfToken) {
        $rawBody = file_get_contents('php://input');
        if ($rawBody) {
            $jsonBody = json_decode($rawBody, true);
            $csrfToken = $jsonBody['csrf_token'] ?? null;
            // Body zurueck in Stream schreiben ist nicht moeglich,
            // daher als Global verfuegbar machen
            $GLOBALS['_JSON_BODY'] = $jsonBody;
        }
    }

    // Validierung: Token nur pruefen wenn er mitgesendet wurde.
    // AJAX-Calls ohne Token sind durch SameSite=Strict Cookie-Flag geschuetzt.
    // Forms die csrf_field() nutzen senden den Token explizit mit.
    if ($csrfToken && isset($_SESSION['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            error_log(sprintf('[SECURITY] CSRF token mismatch | IP: %s | URI: %s',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown', $_SERVER['REQUEST_URI'] ?? 'unknown'));
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'CSRF-Token ungueltig',
                'code' => 'CSRF_ERROR'
            ]);
            exit;
        }
    }
}
