<?php
/**
 * ============================================================================
 * sgiT Education Platform - Migration zu gehashten Passwörtern
 * ============================================================================
 * 
 * Stellt alle 7 Admin-Dateien automatisch auf das neue gehashte System um.
 * 
 * ACHTUNG: Dieses Skript MUSS NUR EINMAL ausgeführt werden!
 * Nach erfolgreicher Migration wird es automatisch deaktiviert.
 * 
 * BACKUP: Alle Dateien werden vor Änderung gesichert!
 * 
 * @version 1.0
 * @date 21.12.2025
 * @author sgiT Solution Engineering
 * ============================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

// ============================================================================
// SICHERHEIT: Nur einmal ausführbar!
// ============================================================================

$lockFile = __DIR__ . '/migration_completed.lock';

if (file_exists($lockFile)) {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Migration bereits durchgeführt</title>
        <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
            body { font-family: Arial; background: #f8f9fa; padding: 50px; text-align: center; }
            .box { background: white; padding: 40px; border-radius: 10px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #43D240; }
            p { color: #666; line-height: 1.6; }
            a { color: #1A3503; text-decoration: none; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>✅ Migration bereits abgeschlossen!</h1>
            <p>Dieses Tool wurde bereits erfolgreich ausgeführt und ist nun gesperrt.</p>
            <p>Wenn du es erneut ausführen musst, lösche die Datei:</p>
            <code>migration_completed.lock</code>
            <br><br>
            <a href="admin_v4.php">→ Zum Admin Dashboard</a>
        </div>
    </body>
    </html>
    ');
}

// ============================================================================
// KONFIGURATION
// ============================================================================

$files = [
    [
        'path' => __DIR__ . '/admin_v4.php',
        'name' => 'admin_v4.php',
        'description' => 'Haupt-Admin-Dashboard',
        'changes' => [
            [
                'search' => "define('ADMIN_PASSWORD', 'sgit2025');",
                'replace' => "// Auth via zentrale Bibliothek (gehashed)\nrequire_once __DIR__ . '/includes/auth_functions.php';"
            ],
            [
                'search' => '$_POST[\'admin_password\'] === ADMIN_PASSWORD',
                'replace' => 'verifyAdminPassword($_POST[\'admin_password\'])'
            ],
            [
                'search' => '$_SESSION[\'is_admin\'] = true;',
                'replace' => 'setAdminSession(\'is_admin\');',
                'comment' => 'Setzt Session über zentrale Funktion'
            ]
        ]
    ],
    [
        'path' => __DIR__ . '/admin_cleanup_flags.php',
        'name' => 'admin_cleanup_flags.php',
        'description' => 'Flag Cleanup Admin',
        'changes' => [
            [
                'search' => "define('ADMIN_PASSWORD', 'sgit2025');",
                'replace' => "// Auth-Check via admin_v4.php Session (bereits gehashed)\n// Keine eigene Authentifizierung nötig"
            ]
        ]
    ],
    [
        'path' => __DIR__ . '/backup_config_admin.php',
        'name' => 'backup_config_admin.php',
        'description' => 'Backup Configuration',
        'changes' => [
            [
                'search' => "define('ADMIN_PASSWORD', 'sgit2025');",
                'replace' => "// Auth via zentrale Bibliothek (gehashed)\nrequire_once __DIR__ . '/includes/auth_functions.php';"
            ],
            [
                'search' => '$_POST[\'password\'] === ADMIN_PASSWORD',
                'replace' => 'verifyAdminPassword($_POST[\'password\'])'
            ]
        ]
    ],
    [
        'path' => __DIR__ . '/backup_manager.php',
        'name' => 'backup_manager.php',
        'description' => 'Backup Manager',
        'changes' => [
            [
                'search' => "define('ADMIN_PASSWORD', 'sgit2025');",
                'replace' => "// Auth via zentrale Bibliothek (gehashed)\nrequire_once __DIR__ . '/includes/auth_functions.php';"
            ],
            [
                'search' => '$_POST[\'password\'] === ADMIN_PASSWORD',
                'replace' => 'verifyAdminPassword($_POST[\'password\'])'
            ]
        ]
    ],
    [
        'path' => __DIR__ . '/debug_users.php',
        'name' => 'debug_users.php',
        'description' => 'Debug Users Interface',
        'changes' => [
            [
                'search' => '$adminPassword = \'sgit2025\';',
                'replace' => '// Auth via zentrale Bibliothek (gehashed)\nrequire_once __DIR__ . \'/includes/auth_functions.php\';'
            ],
            [
                'search' => '$_POST[\'password\'] === $adminPassword',
                'replace' => 'verifyAdminPassword($_POST[\'password\'])'
            ]
        ]
    ],
    [
        'path' => __DIR__ . '/bots/bot_summary.php',
        'name' => 'bots/bot_summary.php',
        'description' => 'Bot Summary Dashboard',
        'changes' => [
            [
                'search' => '$adminPassword = \'sgit2025\';',
                'replace' => '// Auth via zentrale Bibliothek (gehashed)\nrequire_once dirname(__DIR__) . \'/includes/auth_functions.php\';'
            ],
            [
                'search' => '$_POST[\'password\'] === $adminPassword',
                'replace' => 'verifyAdminPassword($_POST[\'password\'])'
            ]
        ]
    ],
    [
        'path' => __DIR__ . '/bots/scheduler/scheduler_ui.php',
        'name' => 'bots/scheduler/scheduler_ui.php',
        'description' => 'Bot Scheduler UI',
        'changes' => [
            [
                'search' => '$adminPassword = \'sgit2025\';',
                'replace' => '// Auth via zentrale Bibliothek (gehashed)\nrequire_once dirname(dirname(__DIR__)) . \'/includes/auth_functions.php\';'
            ],
            [
                'search' => '$_POST[\'password\'] === $adminPassword',
                'replace' => 'verifyAdminPassword($_POST[\'password\'])'
            ]
        ]
    ]
];

$backupDir = __DIR__ . '/backups/migration_v3.48.0_' . date('Y-m-d_His');

// ============================================================================
// FUNKTIONEN
// ============================================================================

function createBackup($files, $backupDir) {
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $backed_up = [];
    foreach ($files as $file) {
        if (file_exists($file['path'])) {
            $backup_path = $backupDir . '/' . str_replace('/', '_', $file['name']);
            if (copy($file['path'], $backup_path)) {
                $backed_up[] = $backup_path;
            } else {
                return ['success' => false, 'error' => 'Backup fehlgeschlagen: ' . $file['path']];
            }
        }
    }
    
    return ['success' => true, 'files' => $backed_up];
}

function migrateFile($file) {
    if (!file_exists($file['path'])) {
        return ['success' => false, 'message' => '❌ Datei nicht gefunden'];
    }
    
    $content = file_get_contents($file['path']);
    $original_content = $content;
    $changes_made = 0;
    
    foreach ($file['changes'] as $change) {
        if (strpos($content, $change['search']) !== false) {
            $content = str_replace($change['search'], $change['replace'], $content);
            $changes_made++;
        }
    }
    
    if ($changes_made === 0) {
        return ['success' => false, 'message' => '⚠️ Keine Änderungen nötig (bereits migriert?)'];
    }
    
    if (file_put_contents($file['path'], $content) === false) {
        return ['success' => false, 'message' => '❌ Schreibfehler'];
    }
    
    return [
        'success' => true, 
        'message' => "✅ $changes_made Änderung(en) erfolgreich",
        'changes' => $changes_made
    ];
}

// ============================================================================
// MIGRATION DURCHFÜHREN
// ============================================================================

$migrationStarted = isset($_POST['start_migration']);
$results = null;

if ($migrationStarted) {
    // Backup erstellen
    $backupResult = createBackup($files, $backupDir);
    
    if (!$backupResult['success']) {
        $error = $backupResult['error'];
    } else {
        // Migration durchführen
        $results = [];
        $total_changes = 0;
        $success_count = 0;
        
        foreach ($files as $file) {
            $result = migrateFile($file);
            $result['file'] = $file['name'];
            $result['description'] = $file['description'];
            $results[] = $result;
            
            if ($result['success']) {
                $success_count++;
                $total_changes += $result['changes'];
            }
        }
        
        // Lock-File erstellen bei Erfolg
        if ($success_count === count($files)) {
            file_put_contents($lockFile, json_encode([
                'completed_at' => date('Y-m-d H:i:s'),
                'backup_dir' => $backupDir,
                'files_migrated' => count($files),
                'total_changes' => $total_changes
            ], JSON_PRETTY_PRINT));
        }
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration zu gehashten Passwörtern - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, #1A3503 0%, #43D240 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            margin: 0 auto;
            overflow: hidden;
        }
        
        .header {
            background: #1A3503;
            color: #43D240;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { color: #ccc; font-size: 14px; }
        
        .content { padding: 40px; }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        
        .warning-box h3 { color: #856404; margin-bottom: 15px; font-size: 18px; }
        .warning-box ul { color: #856404; margin-left: 20px; }
        .warning-box li { margin: 8px 0; line-height: 1.6; }
        
        .info-box {
            background: #e7f5ff;
            border-left: 4px solid #1971c2;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        
        .info-box h3 { color: #1971c2; margin-bottom: 15px; }
        .info-box ul { color: #495057; margin-left: 20px; }
        
        .file-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        
        .file-list h3 { color: #1A3503; margin-bottom: 15px; }
        
        .file-item {
            background: white;
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 3px solid #43D240;
        }
        
        .file-name { font-weight: 600; color: #1A3503; }
        .file-desc { font-size: 14px; color: #6c757d; margin-top: 5px; }
        
        .btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .results {
            margin-top: 30px;
            border-top: 3px solid #43D240;
            padding-top: 30px;
        }
        
        .result-item {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
        }
        
        .result-item.success {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        
        .result-item.failed {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .result-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #1A3503;
        }
        
        .result-message { font-size: 14px; color: #495057; }
        
        .success-box {
            background: #d4edda;
            border: 2px solid #28a745;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
        }
        
        .success-box h2 { color: #155724; margin-bottom: 15px; }
        .success-box p { color: #155724; line-height: 1.6; }
        
        .next-steps {
            background: #e7f5ff;
            padding: 25px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .next-steps h4 { color: #1971c2; margin-bottom: 15px; }
        .next-steps ol { margin-left: 20px; color: #495057; }
        .next-steps li { margin: 10px 0; line-height: 1.6; }
        
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
        
        a.btn-link {
            display: inline-block;
            background: #43D240;
            color: #1A3503;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        a.btn-link:hover {
            background: #3bc236;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Migration zu gehashten Passwörtern</h1>
            <p>sgiT Education Platform v3.47.0 → v3.48.0</p>
        </div>
        
        <div class="content">
            <?php if (!$migrationStarted): ?>
                <!-- PRE-MIGRATION INFO -->
                <div class="warning-box">
                    <h3>⚠️ WICHTIG: Bitte vor Start lesen!</h3>
                    <ul>
                        <li><strong>Backup wird automatisch erstellt</strong> in: <code>/backups/</code></li>
                        <li><strong>Alle 7 Admin-Dateien</strong> werden umgestellt</li>
                        <li><strong>Passwort bleibt gleich</strong> (sgit2025), nur sicher gespeichert</li>
                        <li><strong>Einmalige Aktion</strong> - Skript sperrt sich nach Erfolg</li>
                        <li><strong>Docker neu starten</strong> nach Migration empfohlen</li>
                    </ul>
                </div>
                
                <div class="info-box">
                    <h3>ℹ️ Was wird geändert?</h3>
                    <ul>
                        <li>Klartext-Passwörter werden entfernt</li>
                        <li>Zentrale Auth-Bibliothek wird eingebunden</li>
                        <li>Passwort-Vergleiche nutzen <code>password_verify()</code></li>
                        <li>Passwort wird als Bcrypt-Hash gespeichert</li>
                    </ul>
                </div>
                
                <div class="file-list">
                    <h3>📂 Betroffene Dateien (<?php echo count($files); ?>)</h3>
                    <?php foreach ($files as $file): ?>
                        <div class="file-item">
                            <div class="file-name"><?php echo htmlspecialchars($file['name']); ?></div>
                            <div class="file-desc">
                                <?php echo htmlspecialchars($file['description']); ?> 
                                (<?php echo count($file['changes']); ?> Änderung<?php echo count($file['changes']) > 1 ? 'en' : ''; ?>)
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <form method="POST" onsubmit="return confirm('Migration jetzt starten? Backup wird automatisch erstellt.');">
                    <button type="submit" name="start_migration" class="btn">
                        🚀 Migration JETZT starten
                    </button>
                </form>
                
            <?php else: ?>
                <!-- POST-MIGRATION RESULTS -->
                <?php if (isset($error)): ?>
                    <div class="warning-box">
                        <h3>❌ Fehler beim Backup</h3>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php else: ?>
                    <?php 
                    $allSuccess = true;
                    foreach ($results as $r) {
                        if (!$r['success']) $allSuccess = false;
                    }
                    ?>
                    
                    <?php if ($allSuccess): ?>
                        <div class="success-box">
                            <h2>🎉 Migration erfolgreich abgeschlossen!</h2>
                            <p>Alle <?php echo count($files); ?> Dateien wurden erfolgreich auf das gehashte System umgestellt.</p>
                            <p>Backup erstellt in: <code><?php echo basename($backupDir); ?></code></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="results">
                        <h3>📊 Detaillierte Ergebnisse</h3>
                        <?php foreach ($results as $result): ?>
                            <div class="result-item <?php echo $result['success'] ? 'success' : 'failed'; ?>">
                                <div class="result-title"><?php echo htmlspecialchars($result['description']); ?></div>
                                <div class="result-message">
                                    <?php echo htmlspecialchars($result['file']); ?>: <?php echo $result['message']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($allSuccess): ?>
                        <div class="next-steps">
                            <h4>📝 Nächste Schritte:</h4>
                            <ol>
                                <li>
                                    <strong>Admin-Login testen:</strong><br>
                                    <a href="admin_v4.php">http://localhost:8080/admin_v4.php</a><br>
                                    Passwort: <code>sgit2025</code> (funktioniert weiterhin!)
                                </li>
                                <li>
                                    <strong>Optional - Passwort ändern:</strong><br>
                                    <a href="admin_password_hasher.php">Hash Generator Tool nutzen</a>
                                </li>
                                <li>
                                    <strong>.gitignore aktualisieren:</strong><br>
                                    <code>/includes/auth_config.php</code> hinzufügen
                                </li>
                                <li>
                                    <strong>Status-Report updaten:</strong><br>
                                    Version auf <code>v3.48.0</code> setzen
                                </li>
                                <li>
                                    <strong>Git Commit:</strong><br>
                                    Neue Auth-Dateien committen (ohne auth_config.php!)
                                </li>
                            </ol>
                        </div>
                        
                        <a href="admin_v4.php" class="btn-link">
                            ✅ Zum Admin Dashboard
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
