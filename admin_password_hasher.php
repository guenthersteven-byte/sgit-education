<?php
/**
 * ============================================================================
 * sgiT Education Platform - Password Hash Generator
 * ============================================================================
 * 
 * Generiert sichere Passwort-Hashes für auth_config.php
 * 
 * VERWENDUNG:
 * 1. Tool im Browser öffnen
 * 2. Neues Passwort eingeben
 * 3. Hash wird generiert
 * 4. Hash in auth_config.php eintragen
 * 
 * @version 1.0
 * @date 21.12.2025
 * @author sgiT Solution Engineering
 * ============================================================================
 */

session_start();
require_once __DIR__ . '/includes/auth_functions.php';

// Auth-Check: Nur für eingeloggte Admins
$authenticated = isAdminLoggedIn('is_admin');

if (!$authenticated && isset($_POST['verify_password'])) {
    if (verifyAdminPassword($_POST['current_password'] ?? '')) {
        $authenticated = true;
        setAdminSession('password_hasher_auth');
    } else {
        $authError = '❌ Falsches Passwort!';
    }
}

$generatedHash = null;
$passwordInfo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_hash']) && $authenticated) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validierung
    $validation = validatePasswordStrength($newPassword);
    
    if (empty($newPassword)) {
        $error = '❌ Bitte Passwort eingeben!';
    } elseif ($newPassword !== $confirmPassword) {
        $error = '❌ Passwörter stimmen nicht überein!';
    } elseif (!$validation['valid']) {
        $error = '❌ ' . implode('<br>', $validation['errors']);
    } else {
        // Hash generieren
        $generatedHash = generatePasswordHash($newPassword);
        $passwordInfo = [
            'password' => $newPassword,
            'hash' => $generatedHash,
            'length' => strlen($newPassword),
            'validation' => $validation
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator - sgiT Education</title>
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
        
        .info-box {
            background: #e7f5ff;
            border-left: 4px solid #1971c2;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        
        .info-box h3 {
            color: #1971c2;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-box ul {
            color: #495057;
            margin-left: 20px;
            font-size: 14px;
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
        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: 'Courier New', monospace;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #43D240;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
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
        
        .btn-secondary {
            background: #868e96;
            color: white;
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background: #495057;
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
        
        .hash-result {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
            border: 2px solid #43D240;
        }
        
        .hash-result h3 {
            color: #1A3503;
            margin-bottom: 15px;
        }
        
        .hash-box {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #495057;
        }
        
        .copy-btn {
            background: #1A3503;
            color: #43D240;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .copy-btn:hover {
            background: #2d5a05;
        }
        
        .instruction-box {
            background: #fff9db;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #ffc107;
        }
        
        .instruction-box h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .instruction-box ol {
            margin-left: 20px;
            color: #856404;
        }
        
        .instruction-box code {
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: 600;
            color: #1A3503;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Password Hash Generator</h1>
            <p>Sichere Bcrypt-Hashes für sgiT Education Platform</p>
        </div>
        
        <div class="content">
            <?php if (!$authenticated): ?>
                <!-- AUTH-FORM -->
                <div class="info-box">
                    <h3>🔒 Authentifizierung erforderlich</h3>
                    <ul>
                        <li>Nur für Administratoren zugänglich</li>
                        <li>Bitte aktuelles Admin-Passwort eingeben</li>
                    </ul>
                </div>
                
                <?php if (isset($authError)): ?>
                    <div class="message error"><?php echo $authError; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Aktuelles Admin-Passwort</label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            required
                            placeholder="Zur Verifizierung"
                        >
                    </div>
                    
                    <button type="submit" name="verify_password" class="btn">
                        🔓 Zugriff freischalten
                    </button>
                </form>
                
            <?php else: ?>
                <!-- HASH GENERATOR -->
                <div class="info-box">
                    <h3>ℹ️ Wie funktioniert das?</h3>
                    <ul>
                        <li>Passwort eingeben → Sicherer Bcrypt-Hash wird generiert</li>
                        <li>Hash in <code>auth_config.php</code> eintragen</li>
                        <li>Passwort ist dann sicher gespeichert (nicht im Klartext!)</li>
                    </ul>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="new_password">🆕 Neues Passwort</label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            required
                            minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                            placeholder="Mindestens <?php echo PASSWORD_MIN_LENGTH; ?> Zeichen"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">✅ Passwort bestätigen</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                            placeholder="Passwort wiederholen"
                        >
                    </div>
                    
                    <button type="submit" name="generate_hash" class="btn">
                        🔄 Hash generieren
                    </button>
                </form>
                
                <?php if ($generatedHash): ?>
                    <div class="hash-result">
                        <h3>✅ Hash erfolgreich generiert!</h3>
                        
                        <div class="stats">
                            <div class="stat-item">
                                <div class="stat-label">Passwortlänge</div>
                                <div class="stat-value"><?php echo $passwordInfo['length']; ?> Zeichen</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Hash-Algorithmus</div>
                                <div class="stat-value">Bcrypt</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Sicherheit</div>
                                <div class="stat-value">⭐⭐⭐⭐⭐</div>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <label>🔑 Generierter Hash (KOPIEREN!):</label>
                            <textarea 
                                id="hash_output" 
                                readonly 
                                onclick="this.select()"
                            ><?php echo htmlspecialchars($generatedHash); ?></textarea>
                            <button class="copy-btn" onclick="copyHash()">📋 In Zwischenablage kopieren</button>
                        </div>
                        
                        <?php if (!empty($passwordInfo['validation']['warnings'])): ?>
                            <div class="message warning">
                                <strong>⚠️ Hinweise:</strong><br>
                                <?php echo implode('<br>', $passwordInfo['validation']['warnings']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="instruction-box">
                            <h4>📝 Nächste Schritte:</h4>
                            <ol>
                                <li>Hash kopieren (Button oben)</li>
                                <li>Datei öffnen: <code>/includes/auth_config.php</code></li>
                                <li>Zeile finden: <code>define('ADMIN_PASSWORD_HASH', '...');</code></li>
                                <li>Alten Hash durch neuen ersetzen</li>
                                <li>Datei speichern</li>
                                <li>FERTIG! Neues Passwort ist aktiv 🎉</li>
                            </ol>
                        </div>
                    </div>
                <?php endif; ?>
                
                <a href="admin_v4.php" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none; margin-top: 20px;">
                    ← Zurück zum Admin Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Passwort-Match Validierung
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = this.value;
            
            if (newPass !== confirmPass) {
                this.setCustomValidity('Passwörter stimmen nicht überein');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Hash kopieren
        function copyHash() {
            const hashField = document.getElementById('hash_output');
            hashField.select();
            document.execCommand('copy');
            
            // Feedback
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = '✅ Kopiert!';
            btn.style.background = '#43D240';
            btn.style.color = '#1A3503';
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '';
                btn.style.color = '';
            }, 2000);
        }
    </script>
</body>
</html>
