<?php
/**
 * ============================================================================
 * sgiT Education - BTCPay Server Setup
 * ============================================================================
 * 
 * Admin-Seite für BTCPay Server Konfiguration:
 * - Netzwerk-Discovery (StartOS, Umbrel, etc.)
 * - Manuelle Konfiguration
 * - Verbindungstest
 * - Lokale Installation falls nötig
 * 
 * @author sgiT Solution Engineering
 * @version 1.0
 * @date 02.12.2025
 * ============================================================================
 */

session_start();

// Admin-Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_v4.php');
    exit;
}

require_once __DIR__ . '/BTCPayManager.php';

$btcpay = null;
$error = null;
$success = null;
$discoveredServers = [];
$connectionTest = null;

try {
    $btcpay = new BTCPayManager();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Actions verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'discover':
            // Netzwerk scannen
            if ($btcpay) {
                $discoveredServers = $btcpay->discoverServers();
            }
            break;
            
        case 'test':
            // Verbindung testen
            if ($btcpay) {
                $connectionTest = $btcpay->testConnection();
            }
            break;
            
        case 'save_config':
            // Konfiguration speichern
            $mode = $_POST['mode'] ?? 'auto';
            $externalHost = trim($_POST['external_host'] ?? '');
            $externalApiKey = trim($_POST['external_api_key'] ?? '');
            $externalStoreId = trim($_POST['external_store_id'] ?? '');
            $externalNetwork = $_POST['external_network'] ?? 'mainnet';
            $enabled = isset($_POST['enabled']);
            
            // Config-Datei lesen und aktualisieren
            $configPath = __DIR__ . '/btcpay_config.php';
            $config = require $configPath;
            
            $config['mode'] = $mode;
            $config['enabled'] = $enabled;
            $config['external']['host'] = $externalHost;
            $config['external']['api_key'] = $externalApiKey;
            $config['external']['store_id'] = $externalStoreId;
            $config['external']['network'] = $externalNetwork;
            
            // PHP-Array als Code speichern
            $configCode = "<?php\n/**\n * BTCPay Konfiguration - Automatisch generiert\n * @date " . date('Y-m-d H:i:s') . "\n */\n\nreturn " . var_export($config, true) . ";\n";
            
            if (file_put_contents($configPath, $configCode)) {
                $success = 'Konfiguration gespeichert!';
                // Cache löschen damit neue Config verwendet wird
                if ($btcpay) {
                    $btcpay->clearCache();
                }
                // Neu laden
                $btcpay = new BTCPayManager();
            } else {
                $error = 'Fehler beim Speichern der Konfiguration';
            }
            break;
            
        case 'clear_cache':
            if ($btcpay) {
                $btcpay->clearCache();
                $success = 'Cache gelöscht!';
            }
            break;
    }
}

// Aktuelle Config laden
$configPath = __DIR__ . '/btcpay_config.php';
$currentConfig = file_exists($configPath) ? require $configPath : [];

// Status
$status = $btcpay ? $btcpay->getStatus() : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>₿ BTCPay Setup - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bitcoin: #F7931A;
            --lightning: #792DE4;
            --bg: #0d1117;
            --card: #161b22;
            --border: #30363d;
            --text: #e6edf3;
            --text-muted: #8b949e;
            --success: #43D240;
            --error: #ff4444;
            --warning: #ffc107;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Space Grotesk', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { max-width: 900px; margin: 0 auto; }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .back-btn {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .status-item {
            background: var(--bg);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .status-item .label {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 5px;
        }
        
        .status-item .value {
            font-size: 16px;
            font-weight: bold;
        }
        
        .status-item .value.success { color: var(--success); }
        .status-item .value.error { color: var(--error); }
        .status-item .value.warning { color: var(--warning); }
        
        .message-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message-box.success {
            background: rgba(67, 210, 64, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }
        
        .message-box.error {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid var(--error);
            color: var(--error);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--bitcoin);
        }
        
        .form-group small {
            color: var(--text-muted);
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--bitcoin), #e88a00);
            color: white;
        }
        
        .btn-secondary {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #2ea62e);
            color: white;
        }
        
        .btn:hover { opacity: 0.9; }
        
        .server-list {
            list-style: none;
        }
        
        .server-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--bg);
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .server-item .server-info { flex: 1; }
        .server-item .server-name { font-weight: bold; }
        .server-item .server-host { font-size: 12px; color: var(--text-muted); }
        
        .server-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .server-status.online {
            background: rgba(67, 210, 64, 0.2);
            color: var(--success);
        }
        
        .server-status.offline {
            background: rgba(255, 68, 68, 0.2);
            color: var(--error);
        }
        
        .mode-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .mode-option {
            padding: 20px;
            background: var(--bg);
            border: 2px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: border-color 0.2s;
        }
        
        .mode-option:hover {
            border-color: var(--bitcoin);
        }
        
        .mode-option.selected {
            border-color: var(--bitcoin);
            background: rgba(247, 147, 26, 0.1);
        }
        
        .mode-option input {
            display: none;
        }
        
        .mode-option .icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .mode-option .title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .mode-option .desc {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .section-divider {
            border-top: 1px solid var(--border);
            margin: 25px 0;
        }
        
        .test-result {
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .test-result.success {
            background: rgba(67, 210, 64, 0.1);
            border: 1px solid var(--success);
        }
        
        .test-result.error {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid var(--error);
        }
        
        .info-box {
            background: rgba(121, 45, 228, 0.1);
            border: 1px solid var(--lightning);
            color: var(--text);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>₿ BTCPay Server Setup</h1>
            <div class="btn-group">
                <a href="wallet_admin.php" class="back-btn">← Wallet Admin</a>
                <a href="../admin_v4.php" class="back-btn">← Dashboard</a>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="message-box success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message-box error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Aktueller Status -->
        <div class="card">
            <h2 class="card-title">📊 Aktueller Status</h2>
            
            <?php if ($status): ?>
                <div class="status-grid">
                    <div class="status-item">
                        <div class="label">System</div>
                        <div class="value <?= $status['enabled'] ? 'success' : 'warning' ?>">
                            <?= $status['enabled'] ? '✅ Aktiv' : '⏸️ Deaktiviert' ?>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="label">Modus</div>
                        <div class="value">
                            <?php
                                switch ($status['mode']) {
                                    case 'external': echo '🌐 Extern'; break;
                                    case 'local': echo '💻 Lokal'; break;
                                    case 'none': echo '❌ Kein Server'; break;
                                    default: echo $status['mode'];
                                }
                            ?>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="label">Netzwerk</div>
                        <div class="value <?= $status['network'] === 'mainnet' ? 'success' : 'warning' ?>">
                            <?= strtoupper($status['network'] ?: '-') ?>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="label">Host</div>
                        <div class="value" style="font-size: 12px;">
                            <?= htmlspecialchars($status['host'] ?: 'Nicht konfiguriert') ?>
                        </div>
                    </div>
                </div>
                
                <!-- Verbindungstest -->
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="test">
                    <button type="submit" class="btn btn-secondary">🔌 Verbindung testen</button>
                </form>
                
                <?php if ($connectionTest): ?>
                    <div class="test-result <?= $connectionTest['success'] ? 'success' : 'error' ?>">
                        <?php if ($connectionTest['success']): ?>
                            <strong>✅ Verbindung erfolgreich!</strong><br>
                            Store: <?= htmlspecialchars($connectionTest['store_name'] ?? '-') ?><br>
                            Netzwerk: <?= strtoupper($connectionTest['network'] ?? '-') ?><br>
                            Modus: <?= $connectionTest['mode'] ?? '-' ?>
                        <?php else: ?>
                            <strong>❌ Verbindung fehlgeschlagen</strong><br>
                            <?= htmlspecialchars($connectionTest['error'] ?? 'Unbekannter Fehler') ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="color: var(--text-muted);">BTCPay Manager konnte nicht geladen werden.</p>
            <?php endif; ?>
        </div>
        
        <!-- Netzwerk-Discovery -->
        <div class="card">
            <h2 class="card-title">🔍 Netzwerk-Erkennung</h2>
            
            <div class="info-box">
                ℹ️ <strong>StartOS / Umbrel Nutzer:</strong><br>
                Wenn du bereits einen BTCPay Server im Netzwerk hast (z.B. auf StartOS), 
                kannst du diesen hier erkennen und verbinden. Keine lokale Installation nötig!
            </div>
            
            <form method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="discover">
                <button type="submit" class="btn btn-primary">🔍 Netzwerk scannen</button>
            </form>
            
            <?php if (!empty($discoveredServers)): ?>
                <ul class="server-list">
                    <?php foreach ($discoveredServers as $server): ?>
                        <li class="server-item">
                            <div class="server-info">
                                <div class="server-name"><?= htmlspecialchars($server['name']) ?></div>
                                <div class="server-host"><?= htmlspecialchars($server['host']) ?></div>
                                <?php if ($server['reachable'] && $server['response_time']): ?>
                                    <div class="server-host">Antwortzeit: <?= $server['response_time'] ?>ms</div>
                                <?php endif; ?>
                            </div>
                            <span class="server-status <?= $server['reachable'] ? 'online' : 'offline' ?>">
                                <?= $server['reachable'] ? '🟢 Online' : '🔴 Offline' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <!-- Konfiguration -->
        <div class="card">
            <h2 class="card-title">⚙️ Konfiguration</h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_config">
                
                <!-- Modus-Auswahl -->
                <div class="form-group">
                    <label>Verbindungsmodus</label>
                    <div class="mode-selector">
                        <label class="mode-option <?= ($currentConfig['mode'] ?? 'auto') === 'external' ? 'selected' : '' ?>">
                            <input type="radio" name="mode" value="external" 
                                   <?= ($currentConfig['mode'] ?? 'auto') === 'external' ? 'checked' : '' ?>>
                            <div class="icon">🌐</div>
                            <div class="title">Extern</div>
                            <div class="desc">StartOS, Umbrel, etc.</div>
                        </label>
                        <label class="mode-option <?= ($currentConfig['mode'] ?? 'auto') === 'local' ? 'selected' : '' ?>">
                            <input type="radio" name="mode" value="local"
                                   <?= ($currentConfig['mode'] ?? 'auto') === 'local' ? 'checked' : '' ?>>
                            <div class="icon">💻</div>
                            <div class="title">Lokal</div>
                            <div class="desc">Docker auf diesem PC</div>
                        </label>
                        <label class="mode-option <?= ($currentConfig['mode'] ?? 'auto') === 'auto' ? 'selected' : '' ?>">
                            <input type="radio" name="mode" value="auto"
                                   <?= ($currentConfig['mode'] ?? 'auto') === 'auto' ? 'checked' : '' ?>>
                            <div class="icon">🔄</div>
                            <div class="title">Auto</div>
                            <div class="desc">Erst extern, dann lokal</div>
                        </label>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- Externer Server -->
                <h3 style="margin-bottom: 15px; color: var(--bitcoin);">🌐 Externer Server (StartOS/Umbrel)</h3>
                
                <div class="form-group">
                    <label for="external_host">BTCPay Server URL</label>
                    <input type="text" id="external_host" name="external_host" 
                           placeholder="https://btcpay.local oder https://192.168.x.x:3003"
                           value="<?= htmlspecialchars($currentConfig['external']['host'] ?? '') ?>">
                    <small>Die URL deines BTCPay Servers im Netzwerk</small>
                </div>
                
                <div class="form-group">
                    <label for="external_api_key">API Key</label>
                    <input type="password" id="external_api_key" name="external_api_key" 
                           placeholder="Dein API Key"
                           value="<?= htmlspecialchars($currentConfig['external']['api_key'] ?? '') ?>">
                    <small>Generieren unter: BTCPay → Settings → Access Tokens</small>
                </div>
                
                <div class="form-group">
                    <label for="external_store_id">Store ID</label>
                    <input type="text" id="external_store_id" name="external_store_id" 
                           placeholder="Store ID"
                           value="<?= htmlspecialchars($currentConfig['external']['store_id'] ?? '') ?>">
                    <small>Zu finden unter: BTCPay → Settings → General</small>
                </div>
                
                <div class="form-group">
                    <label for="external_network">Netzwerk</label>
                    <select id="external_network" name="external_network">
                        <option value="mainnet" <?= ($currentConfig['external']['network'] ?? '') === 'mainnet' ? 'selected' : '' ?>>
                            Mainnet (Echte Sats)
                        </option>
                        <option value="testnet" <?= ($currentConfig['external']['network'] ?? '') === 'testnet' ? 'selected' : '' ?>>
                            Testnet
                        </option>
                        <option value="signet" <?= ($currentConfig['external']['network'] ?? '') === 'signet' ? 'selected' : '' ?>>
                            Signet
                        </option>
                    </select>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- System aktivieren -->
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="enabled" name="enabled" 
                               <?= ($currentConfig['enabled'] ?? false) ? 'checked' : '' ?>>
                        <label for="enabled">
                            <strong>BTCPay Integration aktivieren</strong><br>
                            <small style="color: var(--text-muted);">Wenn aktiviert, werden echte Sats verwendet statt Test-Sats</small>
                        </label>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-success">💾 Speichern</button>
                    <button type="button" class="btn btn-secondary" onclick="document.querySelector('form[action=clear_cache]').submit();">
                        🗑️ Cache löschen
                    </button>
                </div>
            </form>
            
            <form method="POST" style="display: none;">
                <input type="hidden" name="action" value="clear_cache">
            </form>
        </div>
        
        <!-- Lokale Installation -->
        <div class="card">
            <h2 class="card-title">💻 Lokale Installation (falls kein Server im Netzwerk)</h2>
            
            <p style="margin-bottom: 15px; color: var(--text-muted);">
                Falls du keinen BTCPay Server im Netzwerk hast, kannst du einen lokalen Server 
                mit Docker installieren. Ideal für Entwicklung und Tests.
            </p>
            
            <div class="info-box">
                <strong>Voraussetzungen:</strong><br>
                • Windows 10/11 mit WSL2<br>
                • Docker Desktop<br>
                • ~20GB freier Speicher
            </div>
            
            <p style="margin-bottom: 15px;">
                <strong>Setup-Skript ausführen:</strong>
            </p>
            
            <pre style="background: var(--bg); padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px;">
# PowerShell als Administrator öffnen
cd C:\xampp\htdocs\Education\scripts
Set-ExecutionPolicy Bypass -Scope Process -Force
.\install_btcpay.ps1</pre>
            
            <p style="margin-top: 15px; color: var(--text-muted); font-size: 14px;">
                Das Skript installiert WSL2, Docker und BTCPay Server automatisch.
            </p>
        </div>
    </div>
    
    <script>
        // Mode-Selector Styling
        document.querySelectorAll('.mode-option input').forEach(input => {
            input.addEventListener('change', function() {
                document.querySelectorAll('.mode-option').forEach(opt => opt.classList.remove('selected'));
                this.closest('.mode-option').classList.add('selected');
            });
        });
    </script>
</body>
</html>
