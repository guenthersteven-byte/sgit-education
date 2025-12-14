<?php
/**
 * ============================================================================
 * sgiT Education - Bot Summary Dashboard
 * ============================================================================
 * 
 * Zeigt alle Bot-Ergebnisse, Fehler, Warnungen und Verbesserungsvorschläge
 * 
 * Nutzt zentrale Versionsverwaltung via /includes/version.php
 * 
 * @version Siehe SGIT_VERSION
 * @date Siehe SGIT_VERSION_DATE
 * @author sgiT Solution Engineering & IT Services
 * ============================================================================
 */

session_start();

require_once dirname(__DIR__) . '/includes/version.php';
require_once __DIR__ . '/bot_logger.php';

// ============================================================================
// AUTH-CHECK: Bot Dashboard nur für eingeloggte Admins
// ============================================================================
$adminPassword = 'sgit2025';
$isLoggedIn = isset($_SESSION['bot_admin_logged_in']) && $_SESSION['bot_admin_logged_in'] === true;

// Login-Versuch
if (isset($_POST['password'])) {
    if ($_POST['password'] === $adminPassword) {
        $_SESSION['bot_admin_logged_in'] = true;
        $isLoggedIn = true;
    } else {
        $loginError = 'Falsches Passwort!';
    }
}

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['bot_admin_logged_in']);
    header('Location: bot_summary.php');
    exit;
}

// Wenn nicht eingeloggt, Login-Formular zeigen
if (!$isLoggedIn) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>🔐 Bot Dashboard - Login</title>
        <style>
            /* BUG-058 FIX: Dark Mode für Login */
            body { 
                font-family: 'Segoe UI', Arial, sans-serif; 
                background: linear-gradient(135deg, #0d1a02, #1A3503); 
                min-height: 100vh; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                margin: 0; 
            }
            .login-box { 
                background: rgba(0, 0, 0, 0.4); 
                border: 1px solid rgba(67, 210, 64, 0.3);
                padding: 40px; 
                border-radius: 20px; 
                box-shadow: 0 20px 60px rgba(0,0,0,0.5); 
                text-align: center; 
                max-width: 400px; 
            }
            h1 { color: #43D240; margin-bottom: 30px; }
            input[type="password"] { 
                width: 100%; 
                padding: 15px; 
                border: 2px solid rgba(67, 210, 64, 0.3); 
                border-radius: 8px; 
                font-size: 16px; 
                margin-bottom: 20px; 
                background: rgba(0, 0, 0, 0.3);
                color: #ffffff;
            }
            input[type="password"]::placeholder { color: #aaa; }
            input[type="password"]:focus { 
                outline: none; 
                border-color: #43D240; 
                box-shadow: 0 0 10px rgba(67, 210, 64, 0.3);
            }
            button { 
                background: linear-gradient(135deg, #43D240, #35B035); 
                color: white; 
                border: none; 
                padding: 15px 40px; 
                border-radius: 8px; 
                font-size: 16px; 
                cursor: pointer; 
                transition: all 0.3s;
            }
            button:hover { 
                background: linear-gradient(135deg, #35B035, #2d9a2d); 
                transform: translateY(-2px);
                box-shadow: 0 5px 20px rgba(67, 210, 64, 0.3);
            }
            .error { 
                color: #e74c3c; 
                margin-bottom: 20px; 
                padding: 10px;
                background: rgba(231, 76, 60, 0.1);
                border-radius: 8px;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🤖 Bot Dashboard</h1>
            <?php if (isset($loginError)): ?>
                <div class="error"><?= htmlspecialchars($loginError) ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="password" name="password" placeholder="Admin-Passwort" autofocus>
                <button type="submit">🔓 Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}
// ============================================================================

// AJAX-Aktionen für Suggestions
if (isset($_GET['action']) && $isLoggedIn) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'resolve_suggestion':
            $id = intval($_GET['id'] ?? 0);
            $result = BotLogger::resolveSuggestion($id);
            echo json_encode(['success' => $result]);
            exit;
            
        case 'resolve_all':
            $result = BotLogger::resolveAllSuggestions();
            echo json_encode(['success' => $result]);
            exit;
            
        case 'delete_suggestion':
            $id = intval($_GET['id'] ?? 0);
            $result = BotLogger::deleteSuggestion($id);
            echo json_encode(['success' => $result]);
            exit;
    }
}

// Daten laden (mit Fehlerbehandlung)
try {
    $stats = BotLogger::getStatistics();
    $runs = BotLogger::getAllRuns(20);
    $suggestions = BotLogger::getSuggestions('open');
} catch (Exception $e) {
    $stats = [
        'total_runs' => 0,
        'total_tests' => 0,
        'total_passed' => 0,
        'total_errors' => 0,
        'avg_success_rate' => 0,
        'by_bot_type' => [],
        'recent_errors' => []
    ];
    $runs = [];
    $suggestions = [];
}

// Filter
$selectedRun = isset($_GET['run']) ? intval($_GET['run']) : null;

// Wenn ein Run ausgewählt wurde, dessen Details laden
$runDetails = null;
if ($selectedRun) {
    try {
        $runDetails = BotLogger::getRunResults($selectedRun);
    } catch (Exception $e) {
        $runDetails = [];
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 Bot Summary - sgiT Education</title>
    <link rel="stylesheet" href="../assets/css/dark-theme.css">
    <style>
        /* Bot Summary Specific Styles - Dark Theme */
        :root {
            --bg-dark: #0d1a02;
            --bg-card: rgba(26, 53, 3, 0.4);
            --border-green: rgba(67, 210, 64, 0.3);
            --text-primary: #e8f5e9;
            --text-secondary: #a5d6a7;
            --accent: #43D240;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1A3503 100%);
            min-height: 100vh;
            color: var(--text-primary);
        }
        
        /* Header */
        .header {
            background: rgba(0,0,0,0.3);
            border-bottom: 1px solid var(--border-green);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { font-size: 24px; }
        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            margin-left: 10px;
        }
        .header-actions a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Stats Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-green);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-value {
            font-size: 42px;
            font-weight: bold;
            color: var(--accent);
        }
        .stat-value.success { color: #43D240; }
        .stat-value.error { color: #dc3545; }
        .stat-value.warning { color: #ffc107; }
        .stat-label {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 5px;
        }
        .stat-bar {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            margin-top: 15px;
            overflow: hidden;
        }
        .stat-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #43D240, #28a745);
            border-radius: 4px;
        }
        
        /* Section */
        .section {
            background: var(--bg-card);
            border: 1px solid var(--border-green);
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .section-header {
            background: rgba(0,0,0,0.2);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-green);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-header h2 {
            font-size: 18px;
            color: var(--text-primary);
        }
        .section-content {
            padding: 20px;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-green);
        }
        th {
            background: rgba(0,0,0,0.2);
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 13px;
            text-transform: uppercase;
        }
        tr:hover {
            background: rgba(67, 210, 64, 0.1);
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success { background: rgba(67, 210, 64, 0.2); color: #43D240; }
        .badge-info { background: rgba(23, 162, 184, 0.2); color: #17a2b8; }
        .badge-warning { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .badge-error { background: rgba(220, 53, 69, 0.2); color: #dc3545; }
        .badge-critical { background: #dc3545; color: white; }
        
        /* Priority Badges */
        .priority-critical { background: #dc3545; color: white; }
        .priority-high { background: #fd7e14; color: white; }
        .priority-medium { background: #ffc107; color: #333; }
        .priority-low { background: #6c757d; color: white; }
        
        /* Bot Cards */
        .bot-card {
            background: var(--bg-card);
            border: 1px solid var(--border-green);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        .bot-card:hover {
            box-shadow: 0 4px 15px rgba(67, 210, 64, 0.2);
            transform: translateY(-2px);
        }
        .bot-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .bot-icon { font-size: 24px; }
        .bot-name { font-weight: bold; font-size: 16px; color: var(--text-primary); }
        .bot-desc { color: var(--text-secondary); font-size: 13px; margin-bottom: 15px; }
        .bot-actions { display: flex; gap: 10px; margin-bottom: 10px; }
        .btn-start { flex: 1; text-align: center; }
        .btn-stop { 
            background: #6c757d; 
            padding: 8px 12px;
        }
        .btn-stop:hover { background: #5a6268; }
        .bot-status {
            font-size: 12px;
            color: var(--text-secondary);
            text-align: center;
            padding-top: 5px;
            border-top: 1px solid var(--border-green);
        }
        
        /* Status */
        .status-completed { color: #28a745; }
        .status-running { color: #ffc107; }
        .status-failed { color: #dc3545; }
        
        /* Suggestions */
        .suggestion-card {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .suggestion-card.critical {
            background: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.3);
        }
        .suggestion-card.high {
            background: rgba(253, 126, 20, 0.1);
            border-color: rgba(253, 126, 20, 0.3);
        }
        .suggestion-title {
            font-weight: bold;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        .suggestion-files {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 10px;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #43D240;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #3ab837; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .btn-outline {
            background: transparent;
            color: var(--accent);
            border: 1px solid var(--accent);
        }
        .btn-outline:hover {
            background: rgba(67, 210, 64, 0.1);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        /* Details Panel */
        .details-panel {
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            font-size: 13px;
        }
        .details-panel pre {
            background: #1a1a1a;
            color: #f8f8f2;
            padding: 10px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 11px;
        }
        
        /* Grid Layout */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 900px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
        
        /* Log Entry */
        .log-entry {
            padding: 10px 15px;
            border-bottom: 1px solid var(--border-green);
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        .log-entry:last-child { border-bottom: none; }
        .log-entry .icon { font-size: 20px; }
        .log-entry .content { flex: 1; }
        .log-entry .time { color: var(--text-secondary); font-size: 12px; }
        .log-entry .module-tag {
            display: inline-block;
            background: rgba(67, 210, 64, 0.2);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 10px;
        }
        
        /* Welcome Box */
        .welcome-box {
            background: var(--bg-card);
            border: 1px solid var(--border-green);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        .welcome-box h2 {
            color: var(--text-primary);
            margin-bottom: 15px;
        }
        .welcome-box p {
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1>🤖 Bot Testing Dashboard</h1>
        <span style="opacity: 0.8; font-size: 14px;">sgiT Education Quality Assurance</span>
    </div>
    <div class="header-actions">
        <a href="scheduler/scheduler_ui.php">⏰ Scheduler</a>
        <a href="tests/AIGeneratorBot.php">▶️ AI Bot starten</a>
        <a href="../index.php">🏠 Zur Plattform</a>
    </div>
</div>

<div class="container">

    <?php if ($stats['total_runs'] == 0): ?>
    <!-- Welcome Box wenn noch keine Runs -->
    <div class="welcome-box">
        <div style="font-size: 64px;">🤖</div>
        <h2>Willkommen zum Bot Testing Dashboard!</h2>
        <p>Noch keine Bot-Runs durchgeführt. Starte deinen ersten Bot um Testergebnisse zu sehen.</p>
        <a href="tests/AIGeneratorBot.php" class="btn" style="font-size: 18px; padding: 15px 30px;">
            🚀 Ersten Bot starten
        </a>
    </div>
    <?php endif; ?>

    <!-- Stats Overview -->
    <div class="stats-overview">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_runs'] ?></div>
            <div class="stat-label">Bot-Runs</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_tests'] ?></div>
            <div class="stat-label">Tests durchgeführt</div>
        </div>
        <div class="stat-card">
            <div class="stat-value success"><?= $stats['total_passed'] ?></div>
            <div class="stat-label">Erfolgreich</div>
        </div>
        <div class="stat-card">
            <div class="stat-value error"><?= $stats['total_errors'] ?></div>
            <div class="stat-label">Fehler</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['avg_success_rate'] ?>%</div>
            <div class="stat-label">Erfolgsrate</div>
            <div class="stat-bar">
                <div class="stat-bar-fill" style="width: <?= $stats['avg_success_rate'] ?>%"></div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        
        <!-- Recent Runs -->
        <div class="section">
            <div class="section-header">
                <h2>📋 Letzte Bot-Runs</h2>
                <span style="font-size: 13px; color: #666;">Klicke für Details</span>
            </div>
            <div class="section-content" style="padding: 0;">
                <?php if (empty($runs)): ?>
                    <div class="empty-state">
                        <div class="icon">🤖</div>
                        <p>Noch keine Bot-Runs durchgeführt</p>
                        <a href="tests/AIGeneratorBot.php" class="btn" style="margin-top: 15px;">Ersten Bot starten</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Bot</th>
                                <th>Ergebnis</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($runs as $run): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($run['bot_name'] ?? $run['bot_type'] ?? 'Unbekannt') ?></strong><br>
                                        <small style="color: #666;"><?= date('d.m.Y H:i', strtotime($run['started_at'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-success"><?= $run['passed'] ?? 0 ?> ✓</span>
                                        <?php if (($run['warnings'] ?? 0) > 0): ?>
                                            <span class="badge badge-warning"><?= $run['warnings'] ?> ⚠</span>
                                        <?php endif; ?>
                                        <?php if (($run['errors'] ?? 0) > 0): ?>
                                            <span class="badge badge-error"><?= $run['errors'] ?> ✗</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-<?= $run['status'] ?? 'unknown' ?>">
                                            <?= ($run['status'] ?? '') == 'completed' ? '✓' : (($run['status'] ?? '') == 'running' ? '⏳' : '✗') ?>
                                            <?= ucfirst($run['status'] ?? 'Unbekannt') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?run=<?= $run['id'] ?>" class="btn btn-sm btn-outline">Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Open Suggestions -->
        <div class="section">
            <div class="section-header">
                <h2>💡 Verbesserungsvorschläge</h2>
                <div>
                    <span style="font-size: 13px; color: var(--text-secondary); margin-right: 15px;"><?= count($suggestions) ?> offen</span>
                    <?php if (!empty($suggestions)): ?>
                    <button onclick="resolveAllSuggestions()" class="btn btn-sm btn-outline">✓ Alle erledigt</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="section-content">
                <?php if (empty($suggestions)): ?>
                    <div class="empty-state">
                        <div class="icon">✨</div>
                        <p>Keine offenen Vorschläge</p>
                        <small>Starte einen Bot um Vorschläge zu generieren</small>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($suggestions, 0, 5) as $sug): ?>
                        <div class="suggestion-card <?= $sug['priority'] ?? 'medium' ?>" id="suggestion-<?= $sug['id'] ?>">
                            <div class="suggestion-title" style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <span class="badge priority-<?= $sug['priority'] ?? 'medium' ?>"><?= strtoupper($sug['priority'] ?? 'MEDIUM') ?></span>
                                    <?= htmlspecialchars($sug['title'] ?? '') ?>
                                </div>
                                <div>
                                    <button onclick="resolveSuggestion(<?= $sug['id'] ?>)" class="btn btn-sm" title="Als erledigt markieren">✓</button>
                                    <button onclick="deleteSuggestion(<?= $sug['id'] ?>)" class="btn btn-sm btn-outline" style="color: #dc3545; border-color: #dc3545;" title="Löschen">🗑️</button>
                                </div>
                            </div>
                            <p style="font-size: 14px; color: var(--text-secondary);"><?= htmlspecialchars($sug['description'] ?? '') ?></p>
                            <?php if (!empty($sug['affected_files'])): ?>
                                <div class="suggestion-files">
                                    📁 Betroffene Dateien: <code><?= htmlspecialchars($sug['affected_files']) ?></code>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($suggestions) > 5): ?>
                        <p style="text-align: center; color: var(--text-secondary); margin-top: 15px;">
                            + <?= count($suggestions) - 5 ?> weitere Vorschläge
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    
    <!-- Recent Errors -->
    <div class="section">
        <div class="section-header">
            <h2>❌ Letzte Fehler & Kritische Meldungen</h2>
        </div>
        <div class="section-content" style="padding: 0;">
            <?php if (empty($stats['recent_errors'])): ?>
                <div class="empty-state">
                    <div class="icon">🎉</div>
                    <p>Keine Fehler gefunden!</p>
                    <small>Führe Bot-Tests durch um Fehler zu erkennen</small>
                </div>
            <?php else: ?>
                <?php foreach ($stats['recent_errors'] as $error): ?>
                    <div class="log-entry">
                        <div class="icon"><?= ($error['level'] ?? '') == 'critical' ? '🔴' : '❌' ?></div>
                        <div class="content">
                            <strong><?= htmlspecialchars($error['message'] ?? '') ?></strong>
                            <?php if (!empty($error['module'])): ?>
                                <span class="module-tag"><?= htmlspecialchars($error['module']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($error['suggestion'])): ?>
                                <p style="font-size: 13px; color: #666; margin-top: 5px;">
                                    💡 <?= htmlspecialchars($error['suggestion']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="time"><?= isset($error['timestamp']) ? date('d.m. H:i', strtotime($error['timestamp'])) : '' ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($selectedRun && !empty($runDetails)): ?>
    <!-- Run Details -->
    <div class="section">
        <div class="section-header">
            <h2>📊 Details für Run #<?= $selectedRun ?></h2>
            <a href="?" class="btn btn-sm btn-outline">← Zurück</a>
        </div>
        <div class="section-content" style="padding: 0; max-height: 500px; overflow-y: auto;">
            <?php foreach ($runDetails as $result): ?>
                <div class="log-entry">
                    <div class="icon">
                        <?php
                        $icons = [
                            'success' => '✅',
                            'info' => 'ℹ️',
                            'warning' => '⚠️',
                            'error' => '❌',
                            'critical' => '🔴'
                        ];
                        echo $icons[$result['level'] ?? ''] ?? '•';
                        ?>
                    </div>
                    <div class="content">
                        <span class="badge badge-<?= $result['level'] ?? 'info' ?>"><?= strtoupper($result['level'] ?? 'INFO') ?></span>
                        <?php if (!empty($result['module'])): ?>
                            <span class="module-tag"><?= htmlspecialchars($result['module']) ?></span>
                        <?php endif; ?>
                        <br>
                        <strong><?= htmlspecialchars($result['message'] ?? '') ?></strong>
                        <?php if (!empty($result['details'])): ?>
                            <div class="details-panel">
                                <pre><?php 
                                    $details = $result['details'];
                                    if (is_string($details)) {
                                        $decoded = json_decode($details, true);
                                        if ($decoded) {
                                            echo htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                        } else {
                                            echo htmlspecialchars($details);
                                        }
                                    } else {
                                        echo htmlspecialchars(print_r($details, true));
                                    }
                                ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="time">
                        <?= isset($result['timestamp']) ? date('H:i:s', strtotime($result['timestamp'])) : '' ?>
                        <?php if (!empty($result['duration_ms'])): ?>
                            <br><small><?= $result['duration_ms'] ?>ms</small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div class="section">
        <div class="section-header">
            <h2>🚀 Bots starten</h2>
        </div>
        <div class="section-content">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
                
                <!-- AI Generator Bot -->
                <div class="bot-card">
                    <div class="bot-header">
                        <span class="bot-icon">🤖</span>
                        <span class="bot-name">AI Generator Bot</span>
                    </div>
                    <p class="bot-desc">Generiert Fragen via Ollama AI</p>
                    <div class="bot-actions">
                        <a href="tests/AIGeneratorBot.php" class="btn btn-start">▶️ Start</a>
                        <button onclick="stopBot('ai')" class="btn btn-stop">⏹️ Stop</button>
                    </div>
                    <div class="bot-status" id="status-ai">
                        <?= file_exists(__DIR__ . '/logs/STOP_AI_BOT') ? '🔴 Gestoppt' : '🟢 Bereit' ?>
                    </div>
                </div>
                
                <!-- Function Test Bot -->
                <div class="bot-card">
                    <div class="bot-header">
                        <span class="bot-icon">🧪</span>
                        <span class="bot-name">Function Test Bot</span>
                    </div>
                    <p class="bot-desc">Testet alle Modul-Funktionen</p>
                    <div class="bot-actions">
                        <a href="tests/FunctionTestBot.php" class="btn btn-start" style="background: #17a2b8;">▶️ Start</a>
                        <button onclick="stopBot('function')" class="btn btn-stop">⏹️ Stop</button>
                    </div>
                    <div class="bot-status" id="status-function">
                        <?= file_exists(__DIR__ . '/logs/STOP_FUNCTION_BOT') ? '🔴 Gestoppt' : '🟢 Bereit' ?>
                    </div>
                </div>
                
                <!-- Security Bot -->
                <div class="bot-card">
                    <div class="bot-header">
                        <span class="bot-icon">🔒</span>
                        <span class="bot-name">Security Bot</span>
                    </div>
                    <p class="bot-desc">Prüft Sicherheitslücken</p>
                    <div class="bot-actions">
                        <a href="tests/SecurityBot.php" class="btn btn-start" style="background: #dc3545;">▶️ Start</a>
                        <button onclick="stopBot('security')" class="btn btn-stop">⏹️ Stop</button>
                    </div>
                    <div class="bot-status" id="status-security">
                        <?= file_exists(__DIR__ . '/logs/STOP_SECURITY_BOT') ? '🔴 Gestoppt' : '🟢 Bereit' ?>
                    </div>
                </div>
                
                <!-- Load Test Bot -->
                <div class="bot-card">
                    <div class="bot-header">
                        <span class="bot-icon">⚡</span>
                        <span class="bot-name">Load Test Bot</span>
                    </div>
                    <p class="bot-desc">Simuliert mehrere User</p>
                    <div class="bot-actions">
                        <a href="tests/LoadTestBot.php" class="btn btn-start" style="background: #fd7e14;">▶️ Start</a>
                        <button onclick="stopBot('load')" class="btn btn-stop">⏹️ Stop</button>
                    </div>
                    <div class="bot-status" id="status-load">
                        <?= file_exists(__DIR__ . '/logs/STOP_LOAD_BOT') ? '🔴 Gestoppt' : '🟢 Bereit' ?>
                    </div>
                </div>
                
                <!-- Dependency Check Bot -->
                <div class="bot-card">
                    <div class="bot-header">
                        <span class="bot-icon">🔍</span>
                        <span class="bot-name">Dependency Check Bot</span>
                    </div>
                    <p class="bot-desc">Findet toten Code & Abhängigkeiten</p>
                    <div class="bot-actions">
                        <a href="run_dependency_check.php" class="btn btn-start" style="background: #6f42c1;">▶️ Start</a>
                        <button onclick="stopBot('dependency')" class="btn btn-stop">⏹️ Stop</button>
                    </div>
                    <div class="bot-status" id="status-dependency">
                        <?= file_exists(__DIR__ . '/logs/STOP_DEPENDENCY_BOT') ? '🔴 Gestoppt' : '🟢 Bereit' ?>
                    </div>
                </div>
                
            </div>
            <p style="margin-top: 20px; color: #666; font-size: 14px;">
                💡 <strong>Tipp:</strong> AI Generator läuft im Dauermodus. Dependency Check analysiert alle PHP-Dateien auf ungenutzen Code.
            </p>
        </div>
    </div>
    
</div>

<script>
// Auto-refresh alle 60 Sekunden
setInterval(() => {
    // Nur refreshen wenn kein Run-Detail angezeigt wird
    if (!window.location.search.includes('run=')) {
        location.reload();
    }
}, 60000);

// Bot stoppen via AJAX
function stopBot(botType) {
    if (!confirm('Bot "' + botType + '" wirklich stoppen?')) return;
    
    fetch('bot_control.php?action=stop&bot=' + botType)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('status-' + botType).innerHTML = '🔴 Gestoppt';
                alert('✅ ' + data.message);
            } else {
                alert('❌ Fehler: ' + data.message);
            }
        })
        .catch(err => {
            alert('❌ Fehler beim Stoppen: ' + err);
        });
}

// Bot starten (löscht Stop-Flag)
function clearStopFlag(botType) {
    fetch('bot_control.php?action=clear&bot=' + botType)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('status-' + botType).innerHTML = '🟢 Bereit';
            }
        });
}

// Einzelne Suggestion als erledigt markieren
function resolveSuggestion(id) {
    fetch('?action=resolve_suggestion&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const card = document.getElementById('suggestion-' + id);
                if (card) {
                    card.style.opacity = '0.5';
                    card.style.textDecoration = 'line-through';
                    setTimeout(() => card.remove(), 500);
                }
            }
        });
}

// Alle Suggestions als erledigt markieren
function resolveAllSuggestions() {
    if (!confirm('Alle Vorschläge als erledigt markieren?')) return;
    
    fetch('?action=resolve_all')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
}

// Suggestion löschen
function deleteSuggestion(id) {
    if (!confirm('Vorschlag wirklich löschen?')) return;
    
    fetch('?action=delete_suggestion&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const card = document.getElementById('suggestion-' + id);
                if (card) card.remove();
            }
        });
}
</script>

</body>
</html>
