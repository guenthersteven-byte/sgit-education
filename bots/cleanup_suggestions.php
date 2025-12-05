<?php
/**
 * sgiT Education - Bot Suggestions Cleanup
 * 
 * Bereinigt veraltete Suggestions aus der Bot-Datenbank
 * 
 * @author sgiT Solution Engineering & IT Services
 * @date 01.12.2025
 */

$dbPath = __DIR__ . '/logs/bot_results.db';

if (!file_exists($dbPath)) {
    die("❌ Datenbank nicht gefunden: $dbPath");
}

$db = new SQLite3($dbPath);
$db->enableExceptions(true);

// Aktion verarbeiten
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$message = '';
$messageType = '';

if ($action === 'mark_resolved') {
    // Alle XSS-Suggestions als resolved markieren
    $result = $db->exec("UPDATE bot_suggestions SET status = 'resolved' WHERE title LIKE '%XSS%' AND status = 'open'");
    $count = $db->changes();
    $message = "✅ $count XSS-Suggestions als 'resolved' markiert";
    $messageType = 'success';
}

if ($action === 'delete_xss') {
    // Alle XSS-Suggestions löschen
    $result = $db->exec("DELETE FROM bot_suggestions WHERE title LIKE '%XSS%'");
    $count = $db->changes();
    $message = "🗑️ $count XSS-Suggestions gelöscht";
    $messageType = 'success';
}

if ($action === 'delete_all_suggestions') {
    // Alle Suggestions löschen
    $result = $db->exec("DELETE FROM bot_suggestions");
    $count = $db->changes();
    $message = "🗑️ Alle $count Suggestions gelöscht";
    $messageType = 'success';
}

if ($action === 'delete_old_runs') {
    // Alte v1.0 Runs löschen
    $result = $db->exec("DELETE FROM bot_runs WHERE bot_name LIKE '%v1.0%'");
    $countRuns = $db->changes();
    
    // Orphaned results und suggestions aufräumen
    $db->exec("DELETE FROM bot_results WHERE run_id NOT IN (SELECT id FROM bot_runs)");
    $db->exec("DELETE FROM bot_suggestions WHERE run_id NOT IN (SELECT id FROM bot_runs)");
    
    $message = "🗑️ $countRuns alte v1.0 Runs gelöscht + zugehörige Daten bereinigt";
    $messageType = 'success';
}

// Aktuelle Statistiken
$openSuggestions = $db->querySingle("SELECT COUNT(*) FROM bot_suggestions WHERE status = 'open'");
$xssSuggestions = $db->querySingle("SELECT COUNT(*) FROM bot_suggestions WHERE title LIKE '%XSS%' AND status = 'open'");
$totalRuns = $db->querySingle("SELECT COUNT(*) FROM bot_runs");
$v10Runs = $db->querySingle("SELECT COUNT(*) FROM bot_runs WHERE bot_name LIKE '%v1.0%'");

// Suggestions laden
$suggestions = [];
$query = $db->query("SELECT * FROM bot_suggestions WHERE status = 'open' ORDER BY priority, created_at DESC LIMIT 50");
while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
    $suggestions[] = $row;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>🧹 Bot Cleanup - sgiT Education</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: linear-gradient(135deg, #1A3503, #2d5a06); 
            padding: 20px; 
            min-height: 100vh;
            margin: 0;
        }
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 20px; 
            padding: 40px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
        }
        h1 { 
            color: #1A3503; 
            border-bottom: 3px solid #43D240; 
            padding-bottom: 15px; 
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-box .number {
            font-size: 32px;
            font-weight: bold;
            color: #1A3503;
        }
        .stat-box.warning .number { color: #ffc107; }
        .stat-box.danger .number { color: #dc3545; }
        .stat-box .label {
            color: #666;
            font-size: 14px;
        }
        .action-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .action-section h3 {
            margin-top: 0;
            color: #1A3503;
        }
        button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin: 5px;
        }
        .btn-primary { background: #43D240; color: white; }
        .btn-primary:hover { background: #3ab837; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th { background: #f8f9fa; font-size: 13px; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge.critical { background: #dc3545; color: white; }
        .badge.high { background: #fd7e14; color: white; }
        .badge.medium { background: #ffc107; color: #333; }
        .nav-links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .nav-links a {
            color: #1A3503;
            text-decoration: none;
            margin-right: 20px;
        }
        .nav-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧹 Bot Suggestions Cleanup</h1>
    
    <?php if ($message): ?>
        <div class="message <?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-box">
            <div class="number"><?= $totalRuns ?></div>
            <div class="label">Gesamt Runs</div>
        </div>
        <div class="stat-box <?= $v10Runs > 0 ? 'warning' : '' ?>">
            <div class="number"><?= $v10Runs ?></div>
            <div class="label">v1.0 Runs (alt)</div>
        </div>
        <div class="stat-box <?= $openSuggestions > 0 ? 'warning' : '' ?>">
            <div class="number"><?= $openSuggestions ?></div>
            <div class="label">Offene Suggestions</div>
        </div>
        <div class="stat-box <?= $xssSuggestions > 0 ? 'danger' : '' ?>">
            <div class="number"><?= $xssSuggestions ?></div>
            <div class="label">XSS Suggestions</div>
        </div>
    </div>
    
    <div class="action-section">
        <h3>🔧 Aktionen</h3>
        <p>Die XSS-Findings aus Security Bot v1.0 waren <strong>False Positives</strong>. Sie können sicher entfernt werden.</p>
        
        <form method="POST" style="display: inline;">
            <input type="hidden" name="action" value="mark_resolved">
            <button type="submit" class="btn-primary">✅ XSS als "resolved" markieren</button>
        </form>
        
        <form method="POST" style="display: inline;" onsubmit="return confirm('XSS-Suggestions wirklich löschen?')">
            <input type="hidden" name="action" value="delete_xss">
            <button type="submit" class="btn-warning">🗑️ XSS-Suggestions löschen</button>
        </form>
        
        <form method="POST" style="display: inline;" onsubmit="return confirm('ALLE Suggestions löschen?')">
            <input type="hidden" name="action" value="delete_all_suggestions">
            <button type="submit" class="btn-danger">⚠️ ALLE Suggestions löschen</button>
        </form>
        
        <form method="POST" style="display: inline;" onsubmit="return confirm('Alte v1.0 Runs und deren Daten löschen?')">
            <input type="hidden" name="action" value="delete_old_runs">
            <button type="submit" class="btn-secondary">🧹 Alte v1.0 Runs entfernen</button>
        </form>
    </div>
    
    <?php if (!empty($suggestions)): ?>
    <h3>📋 Offene Suggestions (<?= count($suggestions) ?>)</h3>
    <table>
        <thead>
            <tr>
                <th>Priorität</th>
                <th>Titel</th>
                <th>Betroffene Dateien</th>
                <th>Erstellt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suggestions as $sug): ?>
            <tr>
                <td><span class="badge <?= $sug['priority'] ?>"><?= strtoupper($sug['priority']) ?></span></td>
                <td><?= htmlspecialchars($sug['title']) ?></td>
                <td><small><?= htmlspecialchars($sug['affected_files'] ?? '-') ?></small></td>
                <td><small><?= date('d.m. H:i', strtotime($sug['created_at'])) ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    
    <div class="nav-links">
        <a href="bot_summary.php">← Zurück zum Dashboard</a>
        <a href="../admin_v4.php">Admin Dashboard</a>
        <a href="tests/SecurityBot.php">Security Bot</a>
    </div>
</div>
</body>
</html>
