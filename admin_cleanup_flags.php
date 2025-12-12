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
 * - Aktionen: Flag löschen, Frage EDITIEREN, Frage deaktivieren
 * - Hash-Management: Bei Edit wird alter Hash als "blocked" behalten
 * 
 * @version 2.0
 * @date 12.12.2025
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
        q.question_hash,
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
    <title>Cleanup: Gemeldete Fragen - sgiT Admin v2.0</title>
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <style>
        .header { background: rgba(0, 0, 0, 0.4); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid var(--border); }
        .brand h1 { font-size: 1.3rem; color: #fff; }
        .brand h1 small { font-size: 0.7rem; opacity: 0.8; margin-left: 8px; }
        .header-nav { display: flex; gap: 10px; }
        .header-nav a { padding: 10px 18px; border-radius: 10px; text-decoration: none; font-weight: 500; font-size: 0.9rem; }
        .nav-primary { background: var(--accent); color: #000; }
        .nav-secondary { background: rgba(255,255,255,0.1); color: white; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 25px; }
        
        .stats-row { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 20px; min-width: 150px; flex: 1; }
        .stat-card.danger { border-left: 4px solid var(--danger); }
        .stat-card.warning { border-left: 4px solid var(--warning); }
        .stat-value { font-size: 2rem; font-weight: bold; color: var(--accent); }
        .stat-label { font-size: 0.85rem; color: var(--text-muted); margin-top: 5px; }
        
        .filters { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 25px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 0.8rem; color: var(--text-muted); }
        .filter-group select { padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; min-width: 150px; background: rgba(0,0,0,0.3); color: var(--text); }
        .filter-btn { padding: 10px 20px; background: var(--accent); color: #000; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; align-self: flex-end; }
        .filter-clear { padding: 10px 20px; background: rgba(255,255,255,0.1); color: var(--text); border: none; border-radius: 8px; cursor: pointer; align-self: flex-end; text-decoration: none; }

        .table-container { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: rgba(0,0,0,0.3); font-weight: 600; color: var(--accent); font-size: 0.9rem; }
        tr:hover { background: rgba(67, 210, 64, 0.05); }
        
        .question-text { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text); }
        .flag-count { display: inline-flex; align-items: center; justify-content: center; background: var(--danger); color: white; padding: 4px 10px; border-radius: 20px; font-weight: 600; min-width: 30px; }
        .flag-count.low { background: var(--warning); color: #000; }
        
        .reason-tag { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; margin: 2px; background: rgba(23, 162, 184, 0.3); color: #17a2b8; }
        .reason-tag.wrong_answer { background: rgba(220, 53, 69, 0.3); color: #ff6b6b; }
        .reason-tag.unclear { background: rgba(255, 193, 7, 0.3); color: #ffc107; }
        .reason-tag.duplicate { background: rgba(40, 167, 69, 0.3); color: #6cff6c; }
        .reason-tag.inappropriate { background: rgba(220, 53, 69, 0.3); color: #ff6b6b; }
        
        .module-badge { display: inline-block; padding: 4px 10px; background: var(--accent); color: #000; border-radius: 6px; font-size: 0.8rem; }
        
        .action-btns { display: flex; gap: 8px; }
        .action-btn { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s; }
        .action-btn.view { background: rgba(23, 162, 184, 0.3); color: #17a2b8; }
        .action-btn.edit { background: rgba(67, 210, 64, 0.3); color: #43D240; }
        .action-btn.dismiss { background: rgba(255, 193, 7, 0.3); color: #ffc107; }
        .action-btn.delete { background: rgba(220, 53, 69, 0.3); color: #ff6b6b; }
        .action-btn:hover { transform: translateY(-1px); }
        
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state .icon { font-size: 4rem; margin-bottom: 15px; }

        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); justify-content: center; align-items: center; z-index: 1000; backdrop-filter: blur(4px); }
        .modal.active { display: flex; }
        .modal-content { background: var(--primary); border: 1px solid var(--border); border-radius: 16px; padding: 25px; max-width: 700px; width: 90%; max-height: 85vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { font-size: 1.2rem; color: var(--accent); }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); }
        .modal-close:hover { color: var(--danger); }
        
        .detail-row { padding: 12px 0; border-bottom: 1px solid var(--border); }
        .detail-label { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px; }
        .detail-value { font-weight: 500; color: var(--text); }
        .detail-value.question { background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; font-size: 0.95rem; border: 1px solid var(--border); }
        
        .flag-list { margin-top: 20px; }
        .flag-item { background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; margin-bottom: 10px; border-left: 3px solid var(--warning); }
        .flag-item .meta { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px; }
        .flag-item .comment { font-style: italic; color: var(--text); }
        
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; flex-wrap: wrap; }
        .modal-btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .modal-btn.secondary { background: rgba(255,255,255,0.1); color: var(--text); }
        .modal-btn.primary { background: var(--accent); color: #000; }
        .modal-btn.danger { background: var(--danger); color: white; }
        .modal-btn.warning { background: var(--warning); color: #000; }

        /* Edit Form Styles */
        .edit-form { display: flex; flex-direction: column; gap: 15px; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select {
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: rgba(0,0,0,0.3);
            color: var(--text);
            font-size: 0.95rem;
        }
        .form-group textarea { min-height: 80px; resize: vertical; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(67, 210, 64, 0.2);
        }
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .option-item { display: flex; align-items: center; gap: 8px; }
        .option-item input[type="radio"] { accent-color: var(--accent); }
        .option-item input[type="text"] { flex: 1; }
        .option-letter { font-weight: bold; color: var(--accent); min-width: 20px; }
        .hash-info { font-size: 0.75rem; color: var(--text-muted); background: rgba(0,0,0,0.2); padding: 8px; border-radius: 6px; font-family: monospace; }
        .hash-changed { color: var(--warning); font-weight: bold; }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">
            <h1>🚩 Cleanup: Gemeldete Fragen <small>v2.0 - <?= SGIT_VERSION ?></small></h1>
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
                                <button class="action-btn edit" onclick="editQuestion(<?= $q['question_id'] ?>)" title="Frage bearbeiten">✏️</button>
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
                <button class="modal-close" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div id="modalBody">Lade...</div>
            <div class="modal-actions">
                <button class="modal-btn secondary" onclick="closeModal('detailModal')">Schließen</button>
                <button class="modal-btn primary" id="modalEdit">✏️ Bearbeiten</button>
                <button class="modal-btn warning" id="modalDismiss">✓ Flags verwerfen</button>
                <button class="modal-btn danger" id="modalDelete">🚫 Deaktivieren</button>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Frage bearbeiten</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <div id="editBody">
                <form class="edit-form" id="editForm">
                    <input type="hidden" id="edit_question_id" name="question_id">
                    <input type="hidden" id="edit_old_hash" name="old_hash">
                    
                    <div class="form-group">
                        <label>📝 Frage</label>
                        <textarea id="edit_question" name="question" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>✅ Antwortoptionen (markiere die richtige Antwort)</label>
                        <div class="options-grid">
                            <div class="option-item">
                                <input type="radio" name="correct" value="0" id="correct_0">
                                <span class="option-letter">A:</span>
                                <input type="text" id="edit_option_0" name="option_0" required>
                            </div>
                            <div class="option-item">
                                <input type="radio" name="correct" value="1" id="correct_1">
                                <span class="option-letter">B:</span>
                                <input type="text" id="edit_option_1" name="option_1" required>
                            </div>
                            <div class="option-item">
                                <input type="radio" name="correct" value="2" id="correct_2">
                                <span class="option-letter">C:</span>
                                <input type="text" id="edit_option_2" name="option_2" required>
                            </div>
                            <div class="option-item">
                                <input type="radio" name="correct" value="3" id="correct_3">
                                <span class="option-letter">D:</span>
                                <input type="text" id="edit_option_3" name="option_3" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="hash-info" id="hashInfo">
                        <strong>Hash-Info:</strong> Wird beim Speichern berechnet
                    </div>
                </form>
            </div>
            <div class="modal-actions">
                <button class="modal-btn secondary" onclick="closeModal('editModal')">Abbrechen</button>
                <button class="modal-btn primary" onclick="saveQuestion()">💾 Speichern</button>
            </div>
        </div>
    </div>

    <script>
        let currentQuestionId = null;
        let currentQuestionData = null;
        
        // ============================================================
        // Detail Modal
        // ============================================================
        function showDetails(qid) {
            currentQuestionId = qid;
            document.getElementById('detailModal').classList.add('active');
            document.getElementById('modalBody').innerHTML = '<p>Lade Details...</p>';
            
            fetch('/api/flag_question.php?action=details&question_id=' + qid)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        const first = data.data[0];
                        currentQuestionData = first;
                        
                        let html = `
                            <div class="detail-row">
                                <div class="detail-label">Frage</div>
                                <div class="detail-value question">${escapeHtml(first.question)}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Korrekte Antwort</div>
                                <div class="detail-value" style="color: var(--accent);">${escapeHtml(first.answer || 'N/A')}</div>
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
                                    <div class="meta">${escapeHtml(f.user_name)} • ${f.created_at} • ${reasonLabel}</div>
                                    ${f.comment ? `<div class="comment">"${escapeHtml(f.comment)}"</div>` : ''}
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                        document.getElementById('modalBody').innerHTML = html;
                        
                        document.getElementById('modalEdit').onclick = () => { closeModal('detailModal'); editQuestion(qid); };
                        document.getElementById('modalDismiss').onclick = () => dismissFlags(qid);
                        document.getElementById('modalDelete').onclick = () => deleteQuestion(qid);
                    }
                });
        }

        // ============================================================
        // Edit Modal
        // ============================================================
        function editQuestion(qid) {
            currentQuestionId = qid;
            document.getElementById('editModal').classList.add('active');
            
            // Lade Frage-Details
            fetch('/api/flag_question.php?action=question&question_id=' + qid)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.question) {
                        const q = data.question;
                        let options = [];
                        try {
                            options = JSON.parse(q.options);
                        } catch(e) {
                            options = q.options.split(',');
                        }
                        
                        document.getElementById('edit_question_id').value = q.id;
                        document.getElementById('edit_old_hash').value = q.question_hash || '';
                        document.getElementById('edit_question').value = q.question;
                        
                        // Optionen setzen
                        for (let i = 0; i < 4; i++) {
                            document.getElementById('edit_option_' + i).value = options[i] || '';
                            // Richtige Antwort markieren
                            if (options[i] && options[i].trim() === (q.answer || '').trim()) {
                                document.getElementById('correct_' + i).checked = true;
                            }
                        }
                        
                        document.getElementById('hashInfo').innerHTML = 
                            `<strong>Aktueller Hash:</strong> <code>${q.question_hash || 'N/A'}</code>`;
                    }
                });
        }
        
        function saveQuestion() {
            const form = document.getElementById('editForm');
            const qid = document.getElementById('edit_question_id').value;
            const oldHash = document.getElementById('edit_old_hash').value;
            const question = document.getElementById('edit_question').value.trim();
            
            const options = [
                document.getElementById('edit_option_0').value.trim(),
                document.getElementById('edit_option_1').value.trim(),
                document.getElementById('edit_option_2').value.trim(),
                document.getElementById('edit_option_3').value.trim()
            ];
            
            const correctIdx = document.querySelector('input[name="correct"]:checked');
            if (!correctIdx) {
                alert('Bitte markiere die richtige Antwort!');
                return;
            }
            
            const answer = options[parseInt(correctIdx.value)];
            
            if (!question || options.some(o => !o)) {
                alert('Bitte fülle alle Felder aus!');
                return;
            }
            
            // Speichern via API
            fetch('/api/flag_question.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'edit_question',
                    question_id: parseInt(qid),
                    old_hash: oldHash,
                    question: question,
                    answer: answer,
                    options: options
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let msg = '✅ Frage gespeichert!';
                    if (data.hash_changed) {
                        msg += '\n\n⚠️ Hash wurde geändert!\nAlter Hash bleibt als "blocked" erhalten.';
                    }
                    alert(msg);
                    location.reload();
                } else {
                    alert('❌ Fehler: ' + (data.error || 'Unbekannt'));
                }
            })
            .catch(err => {
                alert('❌ Netzwerkfehler: ' + err.message);
            });
        }

        // ============================================================
        // Andere Aktionen
        // ============================================================
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            if (modalId === 'detailModal') {
                currentQuestionId = null;
                currentQuestionData = null;
            }
        }
        
        function dismissFlags(qid) {
            if (!confirm('Alle Flags für diese Frage verwerfen?\nDie Frage bleibt erhalten.')) return;
            
            fetch('/api/flag_question.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_flags', question_id: qid })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Flags verworfen!');
                    location.reload();
                } else {
                    alert('❌ Fehler: ' + (data.error || 'Unbekannt'));
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
                    alert('❌ Fehler: ' + (data.error || 'Unbekannt'));
                }
            });
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Close modal on backdrop click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
        
        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
            }
        });
    </script>
</body>
</html>
