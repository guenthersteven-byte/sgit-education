<?php
/**
 * ============================================================================
 * sgiT Education - Bot Scheduler UI v1.0
 * ============================================================================
 * 
 * Web-Interface für den Bot Auto-Scheduler
 * - Jobs verwalten (hinzufügen, bearbeiten, löschen)
 * - Status überwachen
 * - Logs einsehen
 * - Manuell Jobs starten
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 11.12.2025
 * ============================================================================
 */

session_start();

require_once __DIR__ . '/BotScheduler.php';
require_once dirname(dirname(__DIR__)) . '/includes/version.php';

// Auth-Check
$adminPassword = 'sgit2025';
$isLoggedIn = isset($_SESSION['scheduler_admin']) && $_SESSION['scheduler_admin'] === true;

if (isset($_POST['password'])) {
    if ($_POST['password'] === $adminPassword) {
        $_SESSION['scheduler_admin'] = true;
        $isLoggedIn = true;
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['scheduler_admin']);
    header('Location: scheduler_ui.php');
    exit;
}

$scheduler = new BotScheduler();

// AJAX-Aktionen
if (isset($_GET['action']) && $isLoggedIn) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'status':
            echo json_encode($scheduler->getStatus());
            exit;
            
        case 'jobs':
            echo json_encode($scheduler->getJobs());
            exit;
            
        case 'run':
            $botKey = $_GET['bot'] ?? '';
            $result = $scheduler->runBot($botKey);
            echo json_encode($result);
            exit;
            
        case 'toggle':
            $jobId = $_GET['job'] ?? '';
            $enabled = ($_GET['enabled'] ?? '1') === '1';
            $result = $scheduler->toggleJob($jobId, $enabled);
            echo json_encode(['success' => $result]);
            exit;
            
        case 'delete':
            $jobId = $_GET['job'] ?? '';
            $result = $scheduler->removeJob($jobId);
            echo json_encode(['success' => $result]);
            exit;
            
        case 'logs':
            echo json_encode($scheduler->getLog(100));
            exit;
            
        case 'run_due':
            $results = $scheduler->runDueJobs();
            echo json_encode($results);
            exit;
    }
}

// POST: Neuen Job hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_job']) && $isLoggedIn) {
    $job = [
        'bot' => $_POST['bot'] ?? '',
        'schedule' => $_POST['schedule'] ?? 'daily',
        'time' => $_POST['time'] ?? '03:00',
        'day' => $_POST['day'] ?? 'sunday',
        'interval_hours' => (int)($_POST['interval_hours'] ?? 6),
        'enabled' => isset($_POST['enabled']),
        'notify_on_error' => isset($_POST['notify']),
        'description' => $_POST['description'] ?? ''
    ];
    $scheduler->addJob($job);
    header('Location: scheduler_ui.php?added=1');
    exit;
}

$jobs = $scheduler->getJobs();
$status = $scheduler->getStatus();
$bots = $scheduler->getAvailableBots();
$logs = $scheduler->getLog(30);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⏰ Bot Scheduler - sgiT Education</title>
    <link rel="stylesheet" href="../../assets/css/dark-theme.css">
    <style>
        /* Dark Theme Override für Scheduler */
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
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1A3503 100%);
            min-height: 100vh;
            color: var(--text-primary);
        }
        
        /* Header */
        .header {
            background: rgba(0,0,0,0.3);
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-green);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .header-nav a {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        .header-nav a:hover { background: rgba(67, 210, 64, 0.2); color: var(--accent); }
        
        /* Container */
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-green);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .stat-value { font-size: 36px; font-weight: bold; color: var(--accent); }
        .stat-label { color: var(--text-secondary); margin-top: 5px; }
        
        /* Cards */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-green);
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-header {
            background: rgba(0,0,0,0.2);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-green);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h2 { font-size: 18px; }
        .card-body { padding: 20px; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-green); }
        th { background: rgba(0,0,0,0.2); color: var(--text-secondary); font-size: 13px; }
        tr:hover { background: rgba(67, 210, 64, 0.1); }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary { background: var(--accent); color: #000; }
        .btn-primary:hover { background: #3ab837; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: rgba(255,255,255,0.1); color: var(--text-primary); }
        .btn-secondary:hover { background: rgba(255,255,255,0.2); }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        
        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        .badge-success { background: rgba(67, 210, 64, 0.2); color: var(--accent); }
        .badge-warning { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .badge-error { background: rgba(220, 53, 69, 0.2); color: #dc3545; }
        .badge-info { background: rgba(23, 162, 184, 0.2); color: #17a2b8; }
        
        /* Forms */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: var(--text-secondary); }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border-green);
            border-radius: 6px;
            color: var(--text-primary);
        }
        .form-control:focus { outline: none; border-color: var(--accent); }
        select.form-control { cursor: pointer; }
        
        /* Grid */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }
        
        /* Log */
        .log-container {
            background: rgba(0,0,0,0.4);
            border-radius: 8px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
        }
        .log-line { padding: 3px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .log-line.error { color: #dc3545; }
        .log-line.success { color: var(--accent); }
        .log-line.info { color: var(--text-secondary); }
        
        /* Login */
        .login-box {
            max-width: 400px;
            margin: 100px auto;
            background: var(--bg-card);
            border: 1px solid var(--border-green);
            border-radius: 12px;
            padding: 40px;
            text-align: center;
        }
        .login-box h1 { margin-bottom: 30px; }
        .login-box input {
            width: 100%;
            padding: 15px;
            margin-bottom: 20px;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border-green);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 16px;
        }
        
        /* Toggle Switch */
        .toggle { position: relative; display: inline-block; width: 50px; height: 26px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.1);
            border-radius: 26px;
            transition: 0.3s;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px; width: 20px;
            left: 3px; bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        .toggle input:checked + .toggle-slider { background: var(--accent); }
        .toggle input:checked + .toggle-slider:before { transform: translateX(24px); }
    </style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- Login -->
<div class="login-box">
    <h1>⏰ Bot Scheduler</h1>
    <form method="post">
        <input type="password" name="password" placeholder="Admin-Passwort" autofocus>
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;">🔓 Login</button>
    </form>
</div>

<?php else: ?>
<!-- Dashboard -->
<div class="header">
    <div>
        <h1>⏰ Bot Auto-Scheduler</h1>
        <span style="opacity: 0.7; font-size: 14px;">v1.0 | sgiT Education <?= SGIT_VERSION ?></span>
    </div>
    <div class="header-nav">
        <a href="../bot_summary.php">📊 Bot Summary</a>
        <a href="../../admin_v4.php">🏠 Admin</a>
        <a href="?logout=1">🚪 Logout</a>
    </div>
</div>

<div class="container">
    
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $status['total_jobs'] ?></div>
            <div class="stat-label">Jobs gesamt</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $status['enabled_jobs'] ?></div>
            <div class="stat-label">Jobs aktiv</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: <?= $status['due_jobs'] > 0 ? '#ffc107' : '#43D240' ?>">
                <?= $status['due_jobs'] ?>
            </div>
            <div class="stat-label">Jobs fällig</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="font-size: 20px;"><?= $status['settings']['timezone'] ?></div>
            <div class="stat-label">Zeitzone</div>
        </div>
    </div>
    
    <?php if ($status['due_jobs'] > 0): ?>
    <div style="background: rgba(255,193,7,0.2); border: 1px solid #ffc107; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
        ⚠️ <strong><?= $status['due_jobs'] ?> Job(s) sind fällig!</strong>
        <button onclick="runDueJobs()" class="btn btn-primary btn-sm" style="margin-left: 15px;">▶️ Jetzt ausführen</button>
    </div>
    <?php endif; ?>
    
    <div class="grid-2">
        
        <!-- Jobs Liste -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Geplante Jobs</h2>
                <button onclick="toggleAddForm()" class="btn btn-primary btn-sm">➕ Neuer Job</button>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($jobs)): ?>
                <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
                    Keine Jobs konfiguriert
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Bot</th>
                            <th>Zeitplan</th>
                            <th>Nächster Lauf</th>
                            <th>Status</th>
                            <th>Aktiv</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td>
                                <?= $bots[$job['bot']]['icon'] ?? '🤖' ?>
                                <strong><?= $bots[$job['bot']]['name'] ?? $job['bot'] ?></strong>
                            </td>
                            <td>
                                <?php
                                $scheduleText = match($job['schedule']) {
                                    'hourly' => 'Stündlich',
                                    'daily' => 'Täglich ' . ($job['time'] ?? '03:00'),
                                    'weekly' => ucfirst($job['day'] ?? 'Sonntag') . ' ' . ($job['time'] ?? '03:00'),
                                    'interval' => 'Alle ' . ($job['interval_hours'] ?? 6) . 'h',
                                    default => $job['schedule']
                                };
                                echo $scheduleText;
                                ?>
                            </td>
                            <td>
                                <?php if ($job['next_run']): ?>
                                    <?= date('d.m. H:i', strtotime($job['next_run'])) ?>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $statusBadge = match($job['last_status'] ?? null) {
                                    'success' => '<span class="badge badge-success">✓ OK</span>',
                                    'error' => '<span class="badge badge-error">✗ Fehler</span>',
                                    default => '<span class="badge badge-info">Ausstehend</span>'
                                };
                                echo $statusBadge;
                                ?>
                            </td>
                            <td>
                                <label class="toggle">
                                    <input type="checkbox" <?= $job['enabled'] ? 'checked' : '' ?> 
                                           onchange="toggleJob('<?= $job['id'] ?>', this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <button onclick="runJob('<?= $job['bot'] ?>')" class="btn btn-secondary btn-sm" title="Jetzt ausführen">▶️</button>
                                <button onclick="deleteJob('<?= $job['id'] ?>')" class="btn btn-danger btn-sm" title="Löschen">🗑️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Job Form & Logs -->
        <div>
            <!-- Add Job Form (versteckt) -->
            <div id="addJobForm" class="card" style="display: none;">
                <div class="card-header">
                    <h2>➕ Neuen Job hinzufügen</h2>
                    <button onclick="toggleAddForm()" class="btn btn-secondary btn-sm">✕</button>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="add_job" value="1">
                        
                        <div class="form-group">
                            <label>Bot auswählen</label>
                            <select name="bot" class="form-control" required>
                                <?php foreach ($bots as $key => $bot): ?>
                                <option value="<?= $key ?>"><?= $bot['icon'] ?> <?= $bot['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Zeitplan</label>
                            <select name="schedule" class="form-control" onchange="updateScheduleFields(this.value)">
                                <option value="daily">Täglich</option>
                                <option value="weekly">Wöchentlich</option>
                                <option value="hourly">Stündlich</option>
                                <option value="interval">Intervall (alle X Stunden)</option>
                            </select>
                        </div>
                        
                        <div id="timeField" class="form-group">
                            <label>Uhrzeit</label>
                            <input type="time" name="time" value="03:00" class="form-control">
                        </div>
                        
                        <div id="dayField" class="form-group" style="display: none;">
                            <label>Wochentag</label>
                            <select name="day" class="form-control">
                                <option value="monday">Montag</option>
                                <option value="tuesday">Dienstag</option>
                                <option value="wednesday">Mittwoch</option>
                                <option value="thursday">Donnerstag</option>
                                <option value="friday">Freitag</option>
                                <option value="saturday">Samstag</option>
                                <option value="sunday" selected>Sonntag</option>
                            </select>
                        </div>
                        
                        <div id="intervalField" class="form-group" style="display: none;">
                            <label>Intervall (Stunden)</label>
                            <input type="number" name="interval_hours" value="6" min="1" max="168" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Beschreibung (optional)</label>
                            <input type="text" name="description" class="form-control" placeholder="z.B. Täglicher Security-Check">
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="enabled" checked> Aktiviert
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="notify"> Bei Fehler benachrichtigen
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Job speichern</button>
                    </form>
                </div>
            </div>
            
            <!-- Logs -->
            <div class="card">
                <div class="card-header">
                    <h2>📜 Scheduler-Log</h2>
                    <button onclick="refreshLogs()" class="btn btn-secondary btn-sm">🔄 Aktualisieren</button>
                </div>
                <div class="card-body">
                    <div class="log-container" id="logContainer">
                        <?php if (empty($logs)): ?>
                        <div style="color: var(--text-secondary); text-align: center; padding: 20px;">
                            Noch keine Log-Einträge
                        </div>
                        <?php else: ?>
                        <?php foreach (array_reverse($logs) as $line): ?>
                        <div class="log-line <?= strpos($line, 'ERROR') !== false ? 'error' : (strpos($line, 'SUCCESS') !== false ? 'success' : 'info') ?>">
                            <?= htmlspecialchars($line) ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Run -->
            <div class="card">
                <div class="card-header">
                    <h2>🚀 Bot manuell starten</h2>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                        <?php foreach ($bots as $key => $bot): ?>
                        <button onclick="runJob('<?= $key ?>')" class="btn btn-secondary">
                            <?= $bot['icon'] ?> <?= $bot['name'] ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
// Form anzeigen/verstecken
function toggleAddForm() {
    const form = document.getElementById('addJobForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

// Schedule-Felder aktualisieren
function updateScheduleFields(schedule) {
    document.getElementById('timeField').style.display = 
        ['daily', 'weekly'].includes(schedule) ? 'block' : 'none';
    document.getElementById('dayField').style.display = 
        schedule === 'weekly' ? 'block' : 'none';
    document.getElementById('intervalField').style.display = 
        schedule === 'interval' ? 'block' : 'none';
}

// Job aktivieren/deaktivieren
function toggleJob(jobId, enabled) {
    fetch(`?action=toggle&job=${jobId}&enabled=${enabled ? '1' : '0'}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) alert('Fehler beim Ändern des Status');
        });
}

// Job löschen
function deleteJob(jobId) {
    if (!confirm('Job wirklich löschen?')) return;
    
    fetch(`?action=delete&job=${jobId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Fehler beim Löschen');
            }
        });
}

// Bot manuell starten
function runJob(botKey) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Läuft...';
    btn.disabled = true;
    
    fetch(`?action=run&bot=${botKey}`)
        .then(r => r.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            if (data.success) {
                alert(`✅ ${data.name} erfolgreich!\nDauer: ${data.duration_ms}ms`);
                refreshLogs();
            } else {
                alert(`❌ Fehler: ${data.error}`);
            }
        })
        .catch(err => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('Fehler: ' + err);
        });
}

// Fällige Jobs ausführen
function runDueJobs() {
    if (!confirm('Alle fälligen Jobs jetzt ausführen?')) return;
    
    fetch('?action=run_due')
        .then(r => r.json())
        .then(results => {
            let msg = 'Ergebnisse:\n';
            results.forEach(r => {
                msg += r.success 
                    ? `✅ ${r.name} (${r.duration_ms}ms)\n`
                    : `❌ ${r.name}: ${r.error}\n`;
            });
            alert(msg);
            location.reload();
        });
}

// Logs aktualisieren
function refreshLogs() {
    fetch('?action=logs')
        .then(r => r.json())
        .then(logs => {
            const container = document.getElementById('logContainer');
            if (logs.length === 0) {
                container.innerHTML = '<div style="color: var(--text-secondary); text-align: center; padding: 20px;">Noch keine Log-Einträge</div>';
                return;
            }
            
            container.innerHTML = logs.reverse().map(line => {
                let cls = 'info';
                if (line.includes('ERROR')) cls = 'error';
                else if (line.includes('SUCCESS')) cls = 'success';
                return `<div class="log-line ${cls}">${line}</div>`;
            }).join('');
        });
}

// Auto-Refresh alle 60 Sekunden
setInterval(() => {
    refreshLogs();
}, 60000);
</script>

<?php endif; ?>

</body>
</html>
