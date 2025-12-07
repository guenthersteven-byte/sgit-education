<?php
/**
 * sgiT Education - Dependency Check Bot Runner
 * 
 * Web-Interface für den Dependency Check Bot
 * Analysiert PHP-Abhängigkeiten und findet toten Code
 * 
 * @version 1.0
 * @date 08.12.2025
 */

require_once __DIR__ . '/bot_output_helper.php';
require_once __DIR__ . '/tests/DependencyCheckBot.php';

// Live-Output aktivieren
BotOutputHelper::init();

// HTML-Header
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Dependency Check - sgiT Education</title>
    <style>
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bg: #0d1f02;
            --card-bg: #1e3a08;
            --text: #ffffff;
            --text-muted: #a0a0a0;
            --warning: #F7931A;
            --error: #dc3545;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Consolas', 'Monaco', monospace;
            background: var(--bg);
            color: var(--text);
            padding: 20px;
            min-height: 100vh;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: var(--accent); margin-bottom: 20px; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .back-link {
            color: var(--accent);
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
        .output-box {
            background: var(--card-bg);
            border: 1px solid var(--accent);
            border-radius: 8px;
            padding: 20px;
            white-space: pre-wrap;
            font-size: 13px;
            line-height: 1.6;
            max-height: 70vh;
            overflow-y: auto;
        }
        .success { color: var(--accent); }
        .warning { color: var(--warning); }
        .error { color: var(--error); }
        .info { color: var(--text-muted); }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: var(--card-bg);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box .value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent);
        }
        .stat-box .label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .stat-box.warning .value { color: var(--warning); }
        .stat-box.error .value { color: var(--error); }
        .file-list {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .file-list h3 {
            color: var(--warning);
            margin-bottom: 10px;
        }
        .file-list ul {
            list-style: none;
            max-height: 300px;
            overflow-y: auto;
        }
        .file-list li {
            padding: 5px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 0.9rem;
        }
        .file-list li:hover {
            background: rgba(67, 210, 64, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Dependency Check Bot</h1>
            <a href="/admin_v4.php" class="back-link">← Zurück zum Admin</a>
        </div>
        
        <p style="color: var(--text-muted); margin-bottom: 20px;">
            Analysiert PHP-Abhängigkeiten und findet ungenutzte Dateien...
        </p>
        
        <div class="output-box" id="output">
<?php
// Bot ausführen
$bot = new DependencyCheckBot();
$results = $bot->run();

// Graph exportieren
$graphFile = $bot->exportGraph();
?>
        </div>
        
        <!-- Statistiken -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="value"><?= $results['stats']['php_files'] ?></div>
                <div class="label">PHP-Dateien</div>
            </div>
            <div class="stat-box">
                <div class="value"><?= $results['stats']['total_dependencies'] ?></div>
                <div class="label">Abhängigkeiten</div>
            </div>
            <div class="stat-box <?= $results['stats']['unused_files'] > 0 ? 'warning' : '' ?>">
                <div class="value"><?= $results['stats']['unused_files'] ?></div>
                <div class="label">Ungenutzt</div>
            </div>
            <div class="stat-box <?= $results['stats']['missing_files'] > 0 ? 'error' : '' ?>">
                <div class="value"><?= $results['stats']['missing_files'] ?></div>
                <div class="label">Fehlend</div>
            </div>
            <div class="stat-box <?= $results['stats']['circular_deps'] > 0 ? 'warning' : '' ?>">
                <div class="value"><?= $results['stats']['circular_deps'] ?></div>
                <div class="label">Zirkulär</div>
            </div>
        </div>
        
        <?php if (!empty($results['unused_files'])): ?>
        <div class="file-list">
            <h3>🗑️ Ungenutzte Dateien (<?= count($results['unused_files']) ?>)</h3>
            <p style="color: var(--text-muted); margin-bottom: 10px; font-size: 0.85rem;">
                Diese Dateien werden nirgends per require/include eingebunden. 
                Prüfe manuell ob sie per AJAX/URL aufgerufen werden!
            </p>
            <ul>
                <?php foreach ($results['unused_files'] as $file): ?>
                <li>📄 <?= htmlspecialchars($file) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($results['missing_files'])): ?>
        <div class="file-list">
            <h3 style="color: var(--error);">❓ Fehlende Dateien</h3>
            <ul>
                <?php foreach ($results['missing_files'] as $file => $missing): ?>
                <li>
                    <strong><?= htmlspecialchars($file) ?></strong> referenziert:
                    <ul style="margin-left: 20px;">
                        <?php foreach ($missing as $m): ?>
                        <li style="color: var(--error);">→ <?= htmlspecialchars($m) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px; color: var(--text-muted); font-size: 0.85rem;">
            📊 Graph exportiert: <code><?= htmlspecialchars($graphFile) ?></code>
        </div>
    </div>
</body>
</html>
