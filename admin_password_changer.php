<?php
/**
 * ============================================================================
 * sgiT Education Platform - Admin Passwort Changer
 * ============================================================================
 * 
 * Ändert das Admin-Passwort in allen 7 betroffenen Dateien auf einmal.
 * 
 * VERWENDUNG:
 * 1. Dieses Skript im Browser aufrufen
 * 2. Altes Passwort eingeben (zur Verifizierung)
 * 3. Neues Passwort eingeben
 * 4. Alle Dateien werden automatisch aktualisiert
 * 
 * SICHERHEIT:
 * - Authentifizierung mit altem Passwort erforderlich
 * - Backup aller Dateien vor Änderung
 * - Rollback-Funktion bei Fehlern
 * - Automatische Validierung
 * 
 * @version 1.0
 * @date 21.12.2025
 * @author sgiT Solution Engineering
 * ============================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================================================
// KONFIGURATION
// ============================================================================

$files = [
    [
        'path' => __DIR__ . '/admin_v4.php',
        'line' => 26,
        'pattern' => "define('ADMIN_PASSWORD', '%s');",
        'description' => 'Haupt-Admin-Dashboard'
    ],
    [
        'path' => __DIR__ . '/admin_cleanup_flags.php',
        'line' => 27,
        'pattern' => "define('ADMIN_PASSWORD', '%s');",
        'description' => 'Flag Cleanup Admin'
    ],
    [
        'path' => __DIR__ . '/backup_config_admin.php',
        'line' => 13,
        'pattern' => "define('ADMIN_PASSWORD', '%s');",
        'description' => 'Backup Configuration'
    ],
    [
        'path' => __DIR__ . '/backup_manager.php',
        'line' => 28,
        'pattern' => "define('ADMIN_PASSWORD', '%s');",
        'description' => 'Backup Manager'
    ],
    [
        'path' => __DIR__ . '/debug_users.php',
        'line' => 26,
        'pattern' => "\$adminPassword = '%s';",
        'description' => 'Debug Users Interface'
    ],
    [
        'path' => __DIR__ . '/bots/bot_summary.php',
        'line' => 25,
        'pattern' => "\$adminPassword = '%s';",
        'description' => 'Bot Summary Dashboard'
    ],
    [
        'path' => __DIR__ . '/bots/scheduler/scheduler_ui.php',
        'line' => 25,
        'pattern' => "\$adminPassword = '%s';",
        'description' => 'Bot Scheduler UI'
    ]
];

$backupDir = __DIR__ . '/backups/password_change_' . date('Y-m-d_His');

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
            $backup_path = $backupDir . '/' . basename($file['path']);
            if (copy($file['path'], $backup_path)) {
                $backed_up[] = $backup_path;
            } else {
                return ['success' => false, 'error' => 'Backup fehlgeschlagen: ' . $file['path']];
            }
        }
    }
    
    return ['success' => true, 'files' => $backed_up];
}

function verifyOldPassword($files, $oldPassword) {
    // Prüfe erste Datei (admin_v4.php)
    $firstFile = $files[0]['path'];
    if (!file_exists($firstFile)) {
        return false;
    }
    
    $content = file_get_contents($firstFile);
    $expectedLine = sprintf($files[0]['pattern'], $oldPassword);
    
    return strpos($content, $expectedLine) !== false;
}

function changePassword($files, $oldPassword, $newPassword) {
    $results = [];
    $success_count = 0;
    
    foreach ($files as $file) {
        $result = [
            'file' => basename($file['path']),
            'description' => $file['description'],
            'success' => false,
            'message' => ''
        ];
        
        if (!file_exists($file['path'])) {
            $result['message'] = '❌ Datei nicht gefunden';
            $results[] = $result;
            continue;
        }
        
        $content = file_get_contents($file['path']);
        $oldLine = sprintf($file['pattern'], $oldPassword);
        $newLine = sprintf($file['pattern'], $newPassword);
        
        if (strpos($content, $oldLine) === false) {
            $result['message'] = '⚠️ Altes Passwort nicht gefunden (möglicherweise bereits geändert)';
            $results[] = $result;
            continue;
        }
        
        $newContent = str_replace($oldLine, $newLine, $content);
        
        if (file_put_contents($file['path'], $newContent) !== false) {
            $result['success'] = true;
            $result['message'] = '✅ Erfolgreich aktualisiert';
            $success_count++;
        } else {
            $result['message'] = '❌ Schreiben fehlgeschlagen';
        }
        
        $results[] = $result;
    }
    
    return [
        'success' => $success_count === count($files),
        'results' => $results,
        'updated' => $success_count,
        'total' => count($files)
    ];
}

function rollback($files, $backupDir) {
    $restored = 0;
    foreach ($files as $file) {
        $backup_path = $backupDir . '/' . basename($file['path']);
        if (file_exists($backup_path)) {
            if (copy($backup_path, $file['path'])) {
                $restored++;
            }
        }
    }
    return $restored;
}

// ============================================================================
// VERARBEITUNG
// ============================================================================

$message = null;
$messageType = null;
$results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validierung
    if (empty($oldPassword) || empty($newPassword)) {
        $message = '❌ Bitte alle Felder ausfüllen!';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = '❌ Neue Passwörter stimmen nicht überein!';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 8) {
        $message = '❌ Neues Passwort muss mindestens 8 Zeichen lang sein!';
        $messageType = 'error';
    } elseif (!verifyOldPassword($files, $oldPassword)) {
        $message = '❌ Altes Passwort ist falsch!';
        $messageType = 'error';
    } else {
        // Backup erstellen
        $backupResult = createBackup($files, $backupDir);
        
        if (!$backupResult['success']) {
            $message = $backupResult['error'];
            $messageType = 'error';
        } else {
            // Passwort ändern
            $changeResult = changePassword($files, $oldPassword, $newPassword);
            $results = $changeResult;
            
            if ($changeResult['success']) {
                $message = sprintf(
                    '✅ Passwort erfolgreich in allen %d Dateien geändert!<br>Backup: %s',
                    $changeResult['total'],
                    $backupDir
                );
                $messageType = 'success';
            } else {
                // Rollback bei Fehler
                $restored = rollback($files, $backupDir);
                $message = sprintf(
                    '⚠️ Fehler beim Ändern! %d von %d Dateien aktualisiert.<br>Rollback: %d Dateien wiederhergestellt.',
                    $changeResult['updated'],
                    $changeResult['total'],
                    $restored
                );
                $messageType = 'warning';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Passwort Changer - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, #1A3503 0%, #43D240 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: #1A3503;
            color: #43D240;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #ccc;
            font-size: 14px;
        }
        
        .content {
            padding: 40px;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        
        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .warning-box ul {
            color: #856404;
            margin-left: 20px;
            font-size: 14px;
        }
        
        .warning-box li {
            margin: 5px 0;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="password"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: #43D240;
        }
        
        .btn {
            background: #43D240;
            color: #1A3503;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #3bc236;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 210, 64, 0.3);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .message.warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .results {
            margin-top: 30px;
            border-top: 2px solid #eee;
            padding-top: 30px;
        }
        
        .results h3 {
            margin-bottom: 20px;
            color: #1A3503;
        }
        
        .result-item {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }
        
        .result-item.success {
            background: #f0f9f0;
            border-color: #43D240;
        }
        
        .result-item.failed {
            background: #fff5f5;
            border-color: #ff6b6b;
        }
        
        .result-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .result-message {
            font-size: 14px;
            color: #666;
        }
        
        .file-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .file-list h3 {
            color: #1A3503;
            margin-bottom: 15px;
        }
        
        .file-list ul {
            list-style: none;
        }
        
        .file-list li {
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
            color: #495057;
            font-size: 14px;
        }
        
        .file-list li:last-child {
            border-bottom: none;
        }
        
        .file-list .file-path {
            font-family: 'Courier New', monospace;
            color: #43D240;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Admin Passwort Changer</h1>
            <p>sgiT Education Platform v3.47.0</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="warning-box">
                <h3>⚠️ Wichtige Hinweise</h3>
                <ul>
                    <li>Dieses Tool ändert das Passwort in allen 7 Admin-Dateien</li>
                    <li>Ein Backup wird automatisch erstellt</li>
                    <li>Neues Passwort sollte mindestens 8 Zeichen haben</li>
                    <li>Alle Admin-Sessions werden nach Änderung ungültig</li>
                    <li>Status-Report muss manuell aktualisiert werden</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="old_password">🔑 Altes Passwort</label>
                    <input 
                        type="password" 
                        id="old_password" 
                        name="old_password" 
                        required 
                        placeholder="Aktuelles Admin-Passwort"
                        autocomplete="current-password"
                    >
                </div>
                
                <div class="form-group">
                    <label for="new_password">🆕 Neues Passwort</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        required 
                        minlength="8"
                        placeholder="Mindestens 8 Zeichen"
                        autocomplete="new-password"
                    >
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">✅ Neues Passwort bestätigen</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        minlength="8"
                        placeholder="Passwort wiederholen"
                        autocomplete="new-password"
                    >
                </div>
                
                <button type="submit" name="change_password" class="btn">
                    🔄 Passwort in allen Dateien ändern
                </button>
            </form>
            
            <?php if ($results): ?>
                <div class="results">
                    <h3>📊 Ergebnisse (<?php echo $results['updated']; ?>/<?php echo $results['total']; ?> erfolgreich)</h3>
                    <?php foreach ($results['results'] as $result): ?>
                        <div class="result-item <?php echo $result['success'] ? 'success' : 'failed'; ?>">
                            <div class="result-title">
                                <?php echo htmlspecialchars($result['description']); ?>
                            </div>
                            <div class="result-message">
                                <?php echo $result['file']; ?>: <?php echo $result['message']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="file-list">
                <h3>📂 Betroffene Dateien (<?php echo count($files); ?>)</h3>
                <ul>
                    <?php foreach ($files as $file): ?>
                        <li>
                            <span class="file-path"><?php echo basename($file['path']); ?></span>
                            <span style="color: #6c757d;"> - <?php echo $file['description']; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        // Password Match Validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = this.value;
            
            if (newPass !== confirmPass) {
                this.setCustomValidity('Passwörter stimmen nicht überein');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
