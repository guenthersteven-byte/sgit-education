<?php
/**
 * sgiT Education Platform - Backup Configuration Admin
 * 
 * Konfigurierbare Backup-Pfade für Admin
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 06.12.2025
 */

session_start();
define('ADMIN_PASSWORD', 'sgit2025');
define('CONFIG_FILE', __DIR__ . '/config/backup_config.json');

// Umgebungserkennung
$isDocker = is_dir('/var/www/html') && !is_dir('C:/xampp');

// ================================================================
// AUTHENTIFIZIERUNG
// ================================================================
$authenticated = isset($_SESSION['backup_config_auth']) && $_SESSION['backup_config_auth'] === true;

if (isset($_POST['password']) && !isset($_POST['save_config'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['backup_config_auth'] = true;
        $authenticated = true;
    } else {
        $loginError = 'Falsches Passwort!';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['backup_config_auth']);
    header('Location: backup_config_admin.php');
    exit;
}

// ================================================================
// KONFIGURATION LADEN/SPEICHERN
// ================================================================

function loadConfig() {
    if (!file_exists(CONFIG_FILE)) {
        return getDefaultConfig();
    }
    $json = file_get_contents(CONFIG_FILE);
    return json_decode($json, true) ?: getDefaultConfig();
}

function saveConfig($config) {
    $config['last_updated'] = date('c');
    $config['updated_by'] = 'admin';
    
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents(CONFIG_FILE, $json) !== false;
}

function getDefaultConfig() {
    return [
        'backup_paths' => [
            'local' => [
                'enabled' => true,
                'path_docker' => '/var/www/html/backups',
                'path_windows' => 'C:/xampp/htdocs/Education/backups',
                'description' => 'Lokales Backup-Verzeichnis'
            ],
            'onedrive' => [
                'enabled' => true,
                'path_docker' => '/mnt/onedrive-backup',
                'path_windows' => 'C:/Users/SG/OneDrive/sgiT/sgiT/WebsiteSourcefiles_sgitspace/backups',
                'description' => 'OneDrive Cloud-Backup'
            ],
            'custom' => [
                'enabled' => false,
                'path_docker' => '',
                'path_windows' => '',
                'description' => 'Benutzerdefinierter Backup-Pfad'
            ]
        ],
        'settings' => [
            'max_backups' => 5,
            'include_ai_data' => true,
            'include_wallet_db' => true,
            'exclude_patterns' => ['_DISABLED_', '.git', 'node_modules', '*.log'],
            'compression_level' => 9
        ]
    ];
}

$config = loadConfig();
$saveMessage = '';
$saveError = '';

// Speichern bei POST
if ($authenticated && isset($_POST['save_config'])) {
    // Backup Paths
    foreach (['local', 'onedrive', 'custom'] as $key) {
        $config['backup_paths'][$key]['enabled'] = isset($_POST["path_{$key}_enabled"]);
        $config['backup_paths'][$key]['path_docker'] = trim($_POST["path_{$key}_docker"] ?? '');
        $config['backup_paths'][$key]['path_windows'] = trim($_POST["path_{$key}_windows"] ?? '');
        $config['backup_paths'][$key]['description'] = trim($_POST["path_{$key}_desc"] ?? '');
    }
    
    // Settings
    $config['settings']['max_backups'] = max(1, min(20, intval($_POST['max_backups'] ?? 5)));
    $config['settings']['include_ai_data'] = isset($_POST['include_ai_data']);
    $config['settings']['include_wallet_db'] = isset($_POST['include_wallet_db']);
    $config['settings']['compression_level'] = max(1, min(9, intval($_POST['compression_level'] ?? 9)));
    
    // Exclude Patterns
    $patterns = array_filter(array_map('trim', explode("\n", $_POST['exclude_patterns'] ?? '')));
    $config['settings']['exclude_patterns'] = $patterns;
    
    if (saveConfig($config)) {
        $saveMessage = 'Konfiguration erfolgreich gespeichert!';
    } else {
        $saveError = 'Fehler beim Speichern der Konfiguration!';
    }
}

// Pfad-Status prüfen
function checkPathStatus($path) {
    if (empty($path)) return ['status' => 'disabled', 'icon' => '⚪', 'text' => 'Nicht konfiguriert'];
    if (!is_dir($path)) return ['status' => 'error', 'icon' => '🔴', 'text' => 'Pfad existiert nicht'];
    if (!is_writable($path)) return ['status' => 'warning', 'icon' => '🟡', 'text' => 'Nicht beschreibbar'];
    return ['status' => 'ok', 'icon' => '🟢', 'text' => 'OK'];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sgiT Education - Backup Konfiguration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bitcoin: #F7931A;
            --danger: #dc3545;
            --success: #28a745;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .header h1 {
            color: var(--primary);
            font-size: 24px;
        }
        .header-links a {
            color: var(--primary);
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 15px;
            border-radius: 8px;
            background: #f0f0f0;
            transition: all 0.3s;
        }
        .header-links a:hover {
            background: var(--accent);
            color: white;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .card h2 {
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent);
        }
        .path-config {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .path-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .path-title {
            font-weight: bold;
            font-size: 16px;
            color: var(--primary);
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-ok { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-disabled { background: #e9ecef; color: #6c757d; }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--accent);
            outline: none;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
            font-family: monospace;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
        }
        .path-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        @media (max-width: 600px) {
            .path-grid { grid-template-columns: 1fr; }
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .env-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .env-docker { background: #0db7ed; color: white; }
        .env-windows { background: #0078d4; color: white; }
        .login-box {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
        }
        .login-box h2 {
            color: var(--primary);
            margin-bottom: 20px;
        }
        .login-box input[type="password"] {
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
        }
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$authenticated): ?>
        <!-- LOGIN -->
        <div class="login-box">
            <h2>🔐 Admin Login</h2>
            <p style="margin-bottom: 20px; color: #666;">Backup-Konfiguration</p>
            <?php if (isset($loginError)): ?>
                <div class="alert alert-error"><?= $loginError ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="password" name="password" placeholder="Admin-Passwort" autofocus>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Anmelden</button>
            </form>
        </div>
        <?php else: ?>
        
        <!-- HEADER -->
        <div class="header">
            <div>
                <h1>⚙️ Backup Konfiguration</h1>
                <span class="env-badge <?= $isDocker ? 'env-docker' : 'env-windows' ?>">
                    <?= $isDocker ? '🐳 Docker' : '🪟 Windows/XAMPP' ?>
                </span>
            </div>
            <div class="header-links">
                <a href="backup_manager.php">📦 Backup Manager</a>
                <a href="admin_v4.php">🏠 Admin</a>
                <a href="?logout=1">🚪 Logout</a>
            </div>
        </div>
        
        <?php if ($saveMessage): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($saveMessage) ?></div>
        <?php endif; ?>
        <?php if ($saveError): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($saveError) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="save_config" value="1">
            
            <!-- BACKUP PFADE -->
            <div class="card">
                <h2>📁 Backup-Pfade</h2>
                
                <?php foreach ($config['backup_paths'] as $key => $path): 
                    $currentPath = $isDocker ? $path['path_docker'] : $path['path_windows'];
                    $status = $path['enabled'] ? checkPathStatus($currentPath) : ['status' => 'disabled', 'icon' => '⚪', 'text' => 'Deaktiviert'];
                ?>
                <div class="path-config">
                    <div class="path-header">
                        <div class="checkbox-group">
                            <input type="checkbox" name="path_<?= $key ?>_enabled" id="path_<?= $key ?>_enabled"
                                   <?= $path['enabled'] ? 'checked' : '' ?>>
                            <label for="path_<?= $key ?>_enabled" class="path-title">
                                <?php 
                                    $icons = ['local' => '💾', 'onedrive' => '☁️', 'custom' => '📂'];
                                    echo ($icons[$key] ?? '📁') . ' ' . ucfirst($key);
                                ?>
                            </label>
                        </div>
                        <span class="status-badge status-<?= $status['status'] ?>">
                            <?= $status['icon'] ?> <?= $status['text'] ?>
                        </span>
                    </div>
                    
                    <div class="form-group">
                        <label>Beschreibung</label>
                        <input type="text" name="path_<?= $key ?>_desc" 
                               value="<?= htmlspecialchars($path['description']) ?>">
                    </div>
                    
                    <div class="path-grid">
                        <div class="form-group">
                            <label>🐳 Docker-Pfad</label>
                            <input type="text" name="path_<?= $key ?>_docker" 
                                   value="<?= htmlspecialchars($path['path_docker']) ?>"
                                   placeholder="/var/www/html/backups">
                            <div class="info-text">Pfad innerhalb des Docker-Containers</div>
                        </div>
                        <div class="form-group">
                            <label>🪟 Windows-Pfad</label>
                            <input type="text" name="path_<?= $key ?>_windows" 
                                   value="<?= htmlspecialchars($path['path_windows']) ?>"
                                   placeholder="C:/path/to/backups">
                            <div class="info-text">Pfad für XAMPP/Windows-Umgebung</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- EINSTELLUNGEN -->
            <div class="card">
                <h2>⚙️ Backup-Einstellungen</h2>
                
                <div class="settings-grid">
                    <div class="form-group">
                        <label>Maximale Backups behalten</label>
                        <input type="number" name="max_backups" min="1" max="20"
                               value="<?= $config['settings']['max_backups'] ?>">
                        <div class="info-text">Ältere Backups werden automatisch gelöscht</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Kompressionsgrad (1-9)</label>
                        <input type="number" name="compression_level" min="1" max="9"
                               value="<?= $config['settings']['compression_level'] ?>">
                        <div class="info-text">9 = beste Kompression, 1 = schnellste</div>
                    </div>
                </div>
                
                <div class="settings-grid" style="margin-top: 20px;">
                    <div class="checkbox-group">
                        <input type="checkbox" name="include_ai_data" id="include_ai_data"
                               <?= $config['settings']['include_ai_data'] ? 'checked' : '' ?>>
                        <label for="include_ai_data">🤖 AI-Daten einschließen (questions.db)</label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="include_wallet_db" id="include_wallet_db"
                               <?= $config['settings']['include_wallet_db'] ? 'checked' : '' ?>>
                        <label for="include_wallet_db">💰 Wallet-Datenbank einschließen</label>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label>Ausschluss-Muster (je Zeile eines)</label>
                    <textarea name="exclude_patterns" rows="4"><?= htmlspecialchars(implode("\n", $config['settings']['exclude_patterns'])) ?></textarea>
                    <div class="info-text">Dateien/Ordner die mit diesen Mustern beginnen werden ausgeschlossen</div>
                </div>
            </div>
            
            <!-- AKTIONEN -->
            <div class="card">
                <div style="display: flex; gap: 15px; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="color: #666; font-size: 14px;">
                            Zuletzt gespeichert: <?= date('d.m.Y H:i', strtotime($config['last_updated'] ?? 'now')) ?>
                            von <?= htmlspecialchars($config['updated_by'] ?? 'system') ?>
                        </span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="backup_manager.php" class="btn btn-secondary">📦 Zum Backup Manager</a>
                        <button type="submit" class="btn btn-primary">💾 Konfiguration speichern</button>
                    </div>
                </div>
            </div>
        </form>
        
        <?php endif; ?>
    </div>
</body>
</html>
