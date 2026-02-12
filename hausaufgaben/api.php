<?php
/**
 * ============================================================================
 * sgiT Education - Hausaufgaben REST-API
 * ============================================================================
 *
 * Endpoints:
 * - GET  ?action=list       - Uploads auflisten (mit Filtern)
 * - GET  ?action=detail&id= - Einzelnen Upload holen
 * - GET  ?action=stats      - Statistiken holen
 * - GET  ?action=school_years - Verfuegbare Schuljahre
 * - POST ?action=delete     - Upload loeschen (Soft-Delete)
 * - POST ?action=update_school_info - Schulinfo updaten
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

// Session-Check
if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt']);
    exit;
}

// Rate-Limit (30 Requests pro Minute)
RateLimiter::enforce('homework_api', 30, 60);

$childId = SessionManager::getChildId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $manager = new HausaufgabenManager();

    switch ($action) {
        // ==================================================================
        // GET: Uploads auflisten
        // ==================================================================
        case 'list':
            $filters = [];
            if (!empty($_GET['subject'])) $filters['subject'] = $_GET['subject'];
            if (!empty($_GET['school_year'])) $filters['school_year'] = $_GET['school_year'];
            if (!empty($_GET['grade_level'])) $filters['grade_level'] = (int) $_GET['grade_level'];

            $limit = min(100, max(1, (int) ($_GET['limit'] ?? 50)));
            $offset = max(0, (int) ($_GET['offset'] ?? 0));

            $uploads = $manager->getUploads($childId, $filters, $limit, $offset);

            echo json_encode([
                'success' => true,
                'uploads' => $uploads,
                'count' => count($uploads),
                'offset' => $offset,
                'limit' => $limit,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ==================================================================
        // GET: Einzelner Upload
        // ==================================================================
        case 'detail':
            $uploadId = (int) ($_GET['id'] ?? 0);
            if ($uploadId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Ungueltige ID']);
                break;
            }

            $upload = $manager->getUpload($uploadId, $childId);
            if (!$upload) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Upload nicht gefunden']);
                break;
            }

            echo json_encode(['success' => true, 'upload' => $upload], JSON_UNESCAPED_UNICODE);
            break;

        // ==================================================================
        // GET: Statistiken
        // ==================================================================
        case 'stats':
            $stats = $manager->getStats($childId);
            echo json_encode(['success' => true, 'stats' => $stats], JSON_UNESCAPED_UNICODE);
            break;

        // ==================================================================
        // GET: Schuljahre
        // ==================================================================
        case 'school_years':
            $years = $manager->getSchoolYears($childId);
            echo json_encode(['success' => true, 'school_years' => $years], JSON_UNESCAPED_UNICODE);
            break;

        // ==================================================================
        // POST: Upload loeschen
        // ==================================================================
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Nur POST erlaubt']);
                break;
            }

            $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!verify_csrf_token($csrfToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Ungueltiger CSRF-Token']);
                break;
            }

            $uploadId = (int) ($_POST['id'] ?? 0);
            if ($uploadId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Ungueltige ID']);
                break;
            }

            $deleted = $manager->deleteUpload($uploadId, $childId);
            echo json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Upload geloescht' : 'Upload nicht gefunden',
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ==================================================================
        // POST: Schulinfo aktualisieren
        // ==================================================================
        case 'update_school_info':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Nur POST erlaubt']);
                break;
            }

            $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!verify_csrf_token($csrfToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Ungueltiger CSRF-Token']);
                break;
            }

            $grade = (int) ($_POST['current_grade'] ?? 0);
            $schoolYear = $_POST['current_school_year'] ?? '';

            if (!HausaufgabenManager::isValidGrade($grade)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Ungueltige Klassenstufe (1-13)']);
                break;
            }
            if (!HausaufgabenManager::isValidSchoolYear($schoolYear)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Ungueltiges Schuljahr (z.B. 2025/2026)']);
                break;
            }

            require_once __DIR__ . '/../wallet/WalletManager.php';
            $wallet = new WalletManager();
            $updated = $wallet->updateChildWallet($childId, [
                'current_grade' => $grade,
                'current_school_year' => $schoolYear,
            ]);

            echo json_encode([
                'success' => (bool) $updated,
                'message' => $updated ? 'Schulinfo gespeichert' : 'Fehler beim Speichern',
                'grade' => $grade,
                'school_year' => $schoolYear,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ==================================================================
        // DEFAULT: Unbekannte Aktion
        // ==================================================================
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion: ' . $action]);
    }
} catch (Exception $e) {
    error_log("Hausaufgaben API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Serverfehler']);
}
