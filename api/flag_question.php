<?php
/**
 * ============================================================================
 * sgiT Education - Flagging API
 * ============================================================================
 * 
 * Endpoint zum Flaggen von fehlerhaften Fragen durch Lernende.
 * 
 * POST /api/flag_question.php
 * Body: { question_id: int, reason: string, comment?: string }
 * 
 * @version 1.0
 * @date 08.12.2025
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Session für User-Name
session_start();

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Datenbank verbinden
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../AI/data/questions.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}


// ============================================================================
// GET: Statistiken abrufen (für Admin)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'stats';
    
    if ($action === 'stats') {
        // Gesamtstatistiken
        $stats = [
            'total_flags' => $db->query("SELECT COUNT(*) FROM flagged_questions")->fetchColumn(),
            'unique_questions' => $db->query("SELECT COUNT(DISTINCT question_id) FROM flagged_questions")->fetchColumn(),
            'by_reason' => []
        ];
        
        $reasons = $db->query("SELECT reason, COUNT(*) as cnt FROM flagged_questions GROUP BY reason ORDER BY cnt DESC");
        foreach ($reasons as $r) {
            $stats['by_reason'][$r['reason']] = (int)$r['cnt'];
        }
        
        echo json_encode(['success' => true, 'data' => $stats]);
        exit;
    }
    
    if ($action === 'list') {
        // Liste aller geflaggten Fragen mit Details
        $stmt = $db->query("
            SELECT 
                q.id as question_id,
                q.question,
                q.answer,
                q.module,
                COUNT(f.id) as flag_count,
                GROUP_CONCAT(DISTINCT f.reason) as reasons,
                MAX(f.created_at) as last_flagged
            FROM flagged_questions f
            JOIN questions q ON f.question_id = q.id
            GROUP BY f.question_id
            ORDER BY flag_count DESC, last_flagged DESC
        ");
        
        $flagged = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $flagged]);
        exit;
    }
    
    if ($action === 'details' && isset($_GET['question_id'])) {
        // Details zu einer spezifischen Frage
        $qid = (int)$_GET['question_id'];
        
        $stmt = $db->prepare("
            SELECT f.*, q.question, q.answer, q.module
            FROM flagged_questions f
            JOIN questions q ON f.question_id = q.id
            WHERE f.question_id = :qid
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([':qid' => $qid]);
        
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}


// ============================================================================
// POST: Frage flaggen
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $questionId = isset($input['question_id']) ? (int)$input['question_id'] : 0;
    $reason = $input['reason'] ?? 'unspecified';
    $comment = $input['comment'] ?? null;
    $userName = $_SESSION['user_name'] ?? ($input['user_name'] ?? 'anonymous');
    
    // Validierung
    if ($questionId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid question_id']);
        exit;
    }
    
    // Gültige Gründe
    $validReasons = ['wrong_answer', 'unclear', 'duplicate', 'inappropriate', 'other', 'unspecified'];
    if (!in_array($reason, $validReasons)) {
        $reason = 'other';
    }
    
    // Prüfen ob Frage existiert
    $stmt = $db->prepare("SELECT id FROM questions WHERE id = :id");
    $stmt->execute([':id' => $questionId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Question not found']);
        exit;
    }
    
    // Duplikat-Check: Hat dieser User diese Frage heute schon geflaggt?
    $stmt = $db->prepare("
        SELECT id FROM flagged_questions 
        WHERE question_id = :qid 
        AND user_name = :user 
        AND date(created_at) = date('now')
    ");
    $stmt->execute([':qid' => $questionId, ':user' => $userName]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Already flagged today', 'code' => 'DUPLICATE']);
        exit;
    }
    
    // Flag speichern
    $stmt = $db->prepare("
        INSERT INTO flagged_questions (question_id, user_name, reason, comment)
        VALUES (:qid, :user, :reason, :comment)
    ");
    $stmt->execute([
        ':qid' => $questionId,
        ':user' => $userName,
        ':reason' => $reason,
        ':comment' => $comment
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Frage wurde gemeldet. Danke!',
        'flag_id' => $db->lastInsertId()
    ]);
    exit;
}


// ============================================================================
// DELETE: Flag oder Frage löschen (Admin)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'delete_flags') {
        // Nur Flags löschen (Frage behalten)
        $qid = (int)($input['question_id'] ?? 0);
        if ($qid > 0) {
            $stmt = $db->prepare("DELETE FROM flagged_questions WHERE question_id = :qid");
            $stmt->execute([':qid' => $qid]);
            echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
            exit;
        }
    }
    
    if ($action === 'delete_question') {
        // SOFT-DELETE: Frage deaktivieren (Hash bleibt erhalten!)
        // Verhindert Loop: AI Generator würde sonst dieselbe Frage wieder erzeugen
        $qid = (int)($input['question_id'] ?? 0);
        if ($qid > 0) {
            $db->beginTransaction();
            // Flags löschen
            $db->prepare("DELETE FROM flagged_questions WHERE question_id = :qid")->execute([':qid' => $qid]);
            // Frage DEAKTIVIEREN statt löschen (Hash bleibt!)
            $db->prepare("UPDATE questions SET is_active = 0 WHERE id = :qid")->execute([':qid' => $qid]);
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Question deactivated (hash preserved)']);
            exit;
        }
    }
    
    if ($action === 'clear_all') {
        // Alle Flags löschen (Fragen behalten)
        $db->exec("DELETE FROM flagged_questions");
        echo json_encode(['success' => true, 'message' => 'All flags cleared']);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Method not allowed']);
