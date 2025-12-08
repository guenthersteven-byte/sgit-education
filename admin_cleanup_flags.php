<?php
/**
 * ============================================================================
 * sgiT Education - Admin Cleanup: Gemeldete Fragen
 * ============================================================================
 * 
 * Verwaltung von geflaggten Fragen durch Lernende.
 * 
 * Features:
 * - Liste aller geflaggten Fragen mit Details
 * - Filter nach Modul, Grund, Anzahl Flags
 * - Aktionen: Flag löschen, Frage editieren, Frage löschen
 * - Bulk-Aktionen
 * 
 * @version 1.0
 * @date 08.12.2025
 * ============================================================================
 */

error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/version.php';

define('ADMIN_PASSWORD', 'sgit2025');

// Login prüfen
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin_v4.php');
    exit();
}

// Datenbank
try {
    $db = new PDO('sqlite:' . __DIR__ . '/AI/data/questions.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Statistiken laden
$stats = [
    'total_flags' => $db->query("SELECT COUNT(*) FROM flagged_questions")->fetchColumn(),
    'unique_questions' => $db->query("SELECT COUNT(DISTINCT question_id) FROM flagged_questions")->fetchColumn(),
    'by_reason' => []
];

$reasons = $db->query("SELECT reason, COUNT(*) as cnt FROM flagged_questions GROUP BY reason ORDER BY cnt DESC");
foreach ($reasons as $r) {
    $stats['by_reason'][$r['reason']] = (int)$r['cnt'];
}

// Geflaggte Fragen laden
$filter_module = $_GET['module'] ?? '';
$filter_reason = $_GET['reason'] ?? '';

$sql = "
    SELECT 
        q.id as question_id,
        q.question,
        q.answer as correct_answer,
        q.module,
        q.options,
        COUNT(f.id) as flag_count,
        GROUP_CONCAT(DISTINCT f.reason) as reasons,
        GROUP_CONCAT(f.comment, '|||') as comments,
        MAX(f.created_at) as last_flagged
    FROM flagged_questions f
    JOIN questions q ON f.question_id = q.id
";

$where = [];
$params = [];

if ($filter_module) {
    $where[] = "q.module = :module";
    $params[':module'] = $filter_module;
}

if ($filter_reason) {
    $where[] = "f.reason = :reason";
    $params[':reason'] = $filter_reason;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " GROUP BY f.question_id ORDER BY flag_count DESC, last_flagged DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$flagged_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Module für Filter
$modules = $db->query("SELECT DISTINCT module FROM questions ORDER BY module")->fetchAll(PDO::FETCH_COLUMN);

// Gründe für Filter
$all_reasons = ['wrong_answer', 'unclear', 'duplicate', 'inappropriate', 'other'];
$reason_labels = [
    'wrong_answer' => '❌ Falsche Antwort',
    'unclear' => '❓ Frage unklar',
    'duplicate' => '🔄 Doppelt',
    'inappropriate' => '⚠️ Unangemessen',
    'other' => '📝 Sonstiges'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup: Gemeldete Fragen - sgiT Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --danger: #e74c3c;
            --warning: #f39c12;
            --bg: #f5f7fa;
        }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); min-height: 100vh; }
        
        .header { background: linear-gradient(135deg, var(--primary), #2d5a06); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .brand h1 { font-size: 1.3rem; }
        .brand h1 small { font-size: 0.7rem; opacity: 0.8; margin-left: 8px; }
        .header-nav { display: flex; gap: 10px; }
        .header-nav a { padding: 10px 18px; border-radius: 10px; text-decoration: none; font-weight: 500; font-size: 0.9rem; }
        .nav-primary { background: var(--accent); color: white; }
        .nav-secondary { background: rgba(255,255,255,0.15); color: white; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 25px; }
        
        /* Stats Cards */
        .stats-row { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; min-width: 150px; flex: 1; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card.danger { border-left: 4px solid var(--danger); }
        .stat-card.warning { border-left: 4px solid var(--warning); }
        .stat-value { font-size: 2rem; font-weight: bold; color: var(--primary); }
        .stat-label { font-size: 0.85rem; color: #666; margin-top: 5px; }
        
        /* Filters */
        .filters { background: white; border-radius: 12px; padding: 20px; margin-bottom: 25px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 0.8rem; color: #666; }
        .filter-group select { padding: 8px 12px; border: 2px solid #ddd; border-radius: 8px; min-width: 150px; }
        .filter-btn { padding: 10px 20px; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; align-self: flex-end; }
        .filter-btn:hover { background: #35B035; }
        .filter-clear { padding: 10px 20px; background: #f0f0f0; color: #666; border: none; border-radius: 8px; cursor: pointer; align-self: flex-end; }
        
        /* Table */
        .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: var(--primary); font-size: 0.9rem; }
        tr:hover { background: #fafafa; }
        
        .question-text { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .flag-count { display: inline-flex; align-items: center; justify-content: center; background: var(--danger); color: white; padding: 4px 10px; border-radius: 20px; font-weight: 600; min-width: 30px; }
        .flag-count.low { background: var(--warning); }
        
        .reason-tag { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; margin: 2px; background: #e3f2fd; color: #1976d2; }
        .reason-tag.wrong_answer { background: #ffebee; color: #c62828; }
        .reason-tag.unclear { background: #fff3e0; color: #ef6c00; }
        .reason-tag.duplicate { background: #e8f5e9; color: #2e7d32; }
        .reason-tag.inappropriate { background: #fce4ec; color: #c2185b; }
        
        .module-badge { display: inline-block; padding: 4px 10px; background: var(--primary); color: white; border-radius: 6px; font-size: 0.8rem; }
        
        .action-btns { display: flex; gap: 8px; }
        .action-btn { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s; }
        .action-btn.view { background: #e3f2fd; color: #1976d2; }
        .action-btn.dismiss { background: #fff3e0; color: #ef6c00; }
        .action-btn.delete { background: #ffebee; color: #c62828; }
        .action-btn:hover { transform: translateY(-1px); }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
        .empty-state .icon { font-size: 4rem; margin-bottom: 15px; }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 1000; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 16px; padding: 25px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { font-size: 1.2rem; color: var(--primary); }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999; }
        
        .detail-row { padding: 12px 0; border-bottom: 1px solid #eee; }
        .detail-label { font-size: 0.85rem; color: #666; margin-bottom: 5px; }
        .detail-value { font-weight: 500; }
        .detail-value.question { background: #f8f9fa; padding: 12px; border-radius: 8px; font-size: 0.95rem; }
        
        .flag-list { margin-top: 20px; }
        .flag-item { background: #fafafa; padding: 12px; border-radius: 8px; margin-bottom: 10px; border-left: 3px solid var(--warning); }
        .flag-item .meta { font-size: 0.8rem; color: #666; margin-bottom: 5px; }
        .flag-item .comment { font-style: italic; color: #444; }
        
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; }
        .modal-btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .modal-btn.secondary { background: #f0f0f0; color: #666; }
        .modal-btn.danger { background: var(--danger); color: white; }
        .modal-btn.warning { background: var(--warning); color: white; }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">
            <h1>🚩 Cleanup: Gemeldete Fragen <small>v<?= SGIT_VERSION ?></small></h1>
        </div>
        <nav class="header-nav">
            <a href="admin_v4.php" class="nav-secondary">← Zurück</a>
            <a href="statistics.php" class="nav-secondary">📊 Statistik</a>
        </nav>
    </header>
    
    <div class="container">
        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card danger">
                <div class="stat-value"><?= $stats['total_flags'] ?></div>
                <div class="stat-label">Gesamt Meldungen</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?= $stats['unique_questions'] ?></div>
                <div class="stat-label">Betroffene Fragen</div>
            </div>
            <?php foreach ($stats['by_reason'] as $reason => $count): ?>
            <div class="stat-card">
                <div class="stat-value"><?= $count ?></div>
                <div class="stat-label"><?= $reason_labels[$reason] ?? $reason ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Filter -->
        <form class="filters" method="GET">
            <div class="filter-group">
                <label>Modul</label>
                <select name="module">
                    <option value="">Alle Module</option>
                    <?php foreach ($modules as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= $filter_module === $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Grund</label>
                <select name="reason">
                    <option value="">Alle Gründe</option>
                    <?php foreach ($all_reasons as $r): ?>
                    <option value="<?= $r ?>" <?= $filter_reason === $r ? 'selected' : '' ?>><?= $reason_labels[$r] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="filter-btn">🔍 Filtern</button>
            <a href="admin_cleanup_flags.php" class="filter-clear">✕ Reset</a>
        </form>
        
        <!-- Table -->
        <div class="table-container">
            <?php if (empty($flagged_questions)): ?>
            <div class="empty-state">
                <div class="icon">✅</div>
                <h3>Keine gemeldeten Fragen!</h3>
                <p>Alles sauber - keine Fragen wurden gemeldet.</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Flags</th>
                        <th>Modul</th>
                        <th>Frage</th>
                        <th>Gründe</th>
                        <th>Zuletzt</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flagged_questions as $q): ?>
                    <tr>
                        <td>
                            <span class="flag-count <?= $q['flag_count'] <= 1 ? 'low' : '' ?>"><?= $q['flag_count'] ?></span>
                        </td>
                        <td><span class="module-badge"><?= ucfirst($q['module']) ?></span></td>
                        <td class="question-text" title="<?= htmlspecialchars($q['question']) ?>"><?= htmlspecialchars($q['question']) ?></td>
                        <td>
                            <?php foreach (explode(',', $q['reasons']) as $r): ?>
                            <span class="reason-tag <?= trim($r) ?>"><?= $reason_labels[trim($r)] ?? trim($r) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($q['last_flagged'])) ?></td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn view" onclick="showDetails(<?= $q['question_id'] ?>)" title="Details anzeigen">👁️</button>
                                <button class="action-btn dismiss" onclick="dismissFlags(<?= $q['question_id'] ?>)" title="Flags verwerfen">✓</button>
                                <button class="action-btn delete" onclick="deleteQuestion(<?= $q['question_id'] ?>)" title="Frage deaktivieren">🚫</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Detail Modal -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📋 Frage-Details</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalBody">Lade...</div>
            <div class="modal-actions">
                <button class="modal-btn secondary" onclick="closeModal()">Schließen</button>
                <button class="modal-btn warning" id="modalDismiss">✓ Flags verwerfen</button>
                <button class="modal-btn danger" id="modalDelete">🚫 Frage deaktivieren</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentQuestionId = null;
        
        function showDetails(qid) {
            currentQuestionId = qid;
            document.getElementById('detailModal').classList.add('active');
            document.getElementById('modalBody').innerHTML = '<p>Lade Details...</p>';
            
            fetch('/api/flag_question.php?action=details&question_id=' + qid)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        const first = data.data[0];
                        let html = `
                            <div class="detail-row">
                                <div class="detail-label">Frage</div>
                                <div class="detail-value question">${first.question}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Korrekte Antwort</div>
                                <div class="detail-value">${first.answer || 'N/A'}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Modul</div>
                                <div class="detail-value">${first.module}</div>
                            </div>
                            <div class="flag-list">
                                <div class="detail-label">Meldungen (${data.data.length})</div>
                        `;
                        
                        data.data.forEach(f => {
                            const reasonLabel = {
                                'wrong_answer': '❌ Falsche Antwort',
                                'unclear': '❓ Unklar',
                                'duplicate': '🔄 Doppelt',
                                'inappropriate': '⚠️ Unangemessen',
                                'other': '📝 Sonstiges'
                            }[f.reason] || f.reason;
                            
                            html += `
                                <div class="flag-item">
                                    <div class="meta">${f.user_name} • ${f.created_at} • ${reasonLabel}</div>
                                    ${f.comment ? `<div class="comment">"${f.comment}"</div>` : ''}
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                        document.getElementById('modalBody').innerHTML = html;
                        
                        document.getElementById('modalDismiss').onclick = () => dismissFlags(qid);
                        document.getElementById('modalDelete').onclick = () => deleteQuestion(qid);
                    }
                });
        }
        
        function closeModal() {
            document.getElementById('detailModal').classList.remove('active');
            currentQuestionId = null;
        }
        
        function dismissFlags(qid) {
            if (!confirm('Alle Flags für diese Frage verwerfen? Die Frage bleibt erhalten.')) return;
            
            fetch('/api/flag_question.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_flags', question_id: qid })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Flags verworfen!');
                    location.reload();
                } else {
                    alert('Fehler: ' + (data.error || 'Unbekannt'));
                }
            });
        }
        
        function deleteQuestion(qid) {
            if (!confirm('🚫 Frage DEAKTIVIEREN?\n\nDie Frage wird nicht mehr angezeigt, aber der Hash bleibt erhalten.\nSo wird sie vom AI-Generator nicht erneut erzeugt.')) return;
            
            fetch('/api/flag_question.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_question', question_id: qid })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Frage deaktiviert!');
                    location.reload();
                } else {
                    alert('Fehler: ' + (data.error || 'Unbekannt'));
                }
            });
        }
    </script>
</body>
</html>
