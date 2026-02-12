<?php
/**
 * ============================================================================
 * sgiT Education - Hausaufgaben Upload Endpoint
 * ============================================================================
 *
 * POST-Endpoint: Foto empfangen, verarbeiten, speichern, SATs vergeben
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 12.02.2026
 * ============================================================================
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../wallet/SessionManager.php';
require_once __DIR__ . '/HausaufgabenManager.php';

header('Content-Type: application/json; charset=utf-8');

// Nur POST erlaubt
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Nur POST erlaubt']);
    exit;
}

// Session-Check
if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt']);
    exit;
}

// CSRF-Token pruefen
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Ungueltiger CSRF-Token']);
    exit;
}

// Rate-Limit (max 10 Uploads pro 5 Minuten)
RateLimiter::enforce('homework_upload', 10, 300);

$childId = SessionManager::getChildId();

// Parameter validieren
$subject = $_POST['subject'] ?? '';
$gradeLevel = (int) ($_POST['grade_level'] ?? 0);
$schoolYear = $_POST['school_year'] ?? '';
$description = trim($_POST['description'] ?? '');

if (empty($subject) || $gradeLevel === 0 || empty($schoolYear)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Fach, Klassenstufe und Schuljahr sind Pflichtfelder']);
    exit;
}

// Datei pruefen
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Kein Foto ausgewaehlt']);
    exit;
}

// Upload verarbeiten
try {
    $manager = new HausaufgabenManager();
    $result = $manager->processUpload($childId, $_FILES['photo'], $subject, $gradeLevel, $schoolYear, $description);

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Upload Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Serverfehler beim Upload']);
}
