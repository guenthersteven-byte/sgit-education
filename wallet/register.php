<?php
/**
 * ============================================================================
 * sgiT Education - User Registrierung v1.2
 * ============================================================================
 * 
 * Registrierungsformular für Lernende.
 * Felder: Name, Geburtsdatum, Avatar, 4-stellige PIN
 * 
 * v1.2 ÄNDERUNGEN (04.12.2025):
 * - FIX: Altersbereich erweitert auf 5-99 Jahre (für alle Altersgruppen)
 * - FIX: Nur noch ein Link zum Admin Center
 * 
 * v1.1 ÄNDERUNGEN (03.12.2025):
 * - NEU: Geburtsdatum statt nur Alter (für Achievement "Geburtstags-Lerner")
 * - NEU: Alter wird automatisch aus Geburtsdatum berechnet
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.2
 * @date 04.12.2025
 * ============================================================================
 */

require_once __DIR__ . '/WalletManager.php';
require_once __DIR__ . '/SessionManager.php';

// ============================================================================
// FORMULAR VERARBEITEN
// ============================================================================
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $birthdate = $_POST['birthdate'] ?? '';
    $avatar = $_POST['avatar'] ?? '👧';
    $pin = $_POST['pin'] ?? '';
    $pinConfirm = $_POST['pin_confirm'] ?? '';
    
    // Alter aus Geburtsdatum berechnen
    $age = 0;
    if (!empty($birthdate)) {
        $birthDate = new DateTime($birthdate);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
    }
    
    // Validierung
    if (empty($name)) {
        $error = 'Bitte gib deinen Namen ein!';
    } elseif (strlen($name) < 2 || strlen($name) > 30) {
        $error = 'Der Name muss zwischen 2 und 30 Zeichen lang sein.';
    } elseif (empty($birthdate)) {
        $error = 'Bitte gib dein Geburtsdatum ein!';
    } elseif ($age < 5 || $age > 99) {
        $error = 'Du musst mindestens 5 Jahre alt sein. (Du bist ' . $age . ' Jahre alt)';
    } elseif (strlen($pin) !== 4 || !ctype_digit($pin)) {
        $error = 'Die PIN muss genau 4 Ziffern haben!';
    } elseif ($pin !== $pinConfirm) {
        $error = 'Die PINs stimmen nicht überein!';
    } else {
        try {
            $wallet = new WalletManager();
            
            // Prüfen ob Name schon existiert
            if ($wallet->nameExists($name)) {
                $error = 'Dieser Name ist schon vergeben. Wähle einen anderen!';
            } else {
                // User erstellen mit Geburtsdatum
                $childId = $wallet->createChildWallet($name, $avatar, $age, $pin, $birthdate);
                
                if ($childId) {
                    // Direkt einloggen
                    $child = $wallet->getChildWallet($childId);
                    SessionManager::login($child);
                    
                    // Redirect zur Startseite
                    header('Location: ../index.php');
                    exit;
                } else {
                    $error = 'Fehler beim Erstellen des Accounts. Bitte versuche es erneut.';
                }
            }
        } catch (Exception $e) {
            $error = 'Systemfehler: ' . $e->getMessage();
        }
    }
}

// ============================================================================
// SCHWIERIGKEITS-INFO
// ============================================================================
function getAgeInfo($age) {
    if ($age <= 7) {
        return ['level' => 'Leicht', 'icon' => '🌱', 'color' => '#43D240'];
    } elseif ($age <= 12) {
        return ['level' => 'Mittel', 'icon' => '🌿', 'color' => '#f7931a'];
    } elseif ($age <= 16) {
        return ['level' => 'Fortgeschritten', 'icon' => '🌳', 'color' => '#1A3503'];
    } else {
        return ['level' => 'Experte', 'icon' => '🎓', 'color' => '#6f42c1'];
    }
}

// Min/Max Geburtsdatum berechnen (5-99 Jahre)
$maxDate = date('Y-m-d', strtotime('-5 years'));   // Mindestens 5 Jahre alt
$minDate = date('Y-m-d', strtotime('-99 years'));  // Kein Maximalalter
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📝 Registrierung - sgiT Education</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1A3503 0%, #2d5a06 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 25px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header .logo {
            font-size: 60px;
            margin-bottom: 10px;
        }
        
        .header h1 {
            color: #1A3503;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1A3503;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            border-color: #43D240;
            outline: none;
            box-shadow: 0 0 0 4px rgba(67, 210, 64, 0.1);
        }
        
        .form-group input[type="number"] {
            -moz-appearance: textfield;
        }
        
        .form-group input::-webkit-outer-spin-button,
        .form-group input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        /* Avatar Picker */
        .avatar-picker {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
        }
        
        .avatar-picker label {
            cursor: pointer;
            display: block;
        }
        
        .avatar-picker input {
            display: none;
        }
        
        .avatar-picker span {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            font-size: 28px;
            border: 3px solid #e0e0e0;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .avatar-picker input:checked + span {
            border-color: #43D240;
            background: #d4edda;
            transform: scale(1.1);
        }
        
        .avatar-picker span:hover {
            border-color: #43D240;
            transform: scale(1.05);
        }
        
        /* Age Info */
        .age-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px 15px;
            margin-top: 10px;
            display: none;
            align-items: center;
            gap: 10px;
        }
        
        .age-info.show {
            display: flex;
        }
        
        .age-info .icon {
            font-size: 24px;
        }
        
        .age-info .text {
            font-size: 13px;
            color: #555;
        }
        
        .age-info .level {
            font-weight: bold;
        }
        
        /* PIN Input */
        .pin-container {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .pin-container input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
        }
        
        .pin-container input:focus {
            border-color: #43D240;
            outline: none;
        }
        
        /* Hidden full PIN fields */
        .pin-hidden {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        
        /* Buttons */
        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #43D240, #3ab837);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(67, 210, 64, 0.4);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #1A3503;
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            color: #999;
            font-size: 13px;
        }
        
        /* Quick Links */
        .quick-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .quick-links a {
            color: #1A3503;
            text-decoration: none;
            font-size: 13px;
        }
        
        .quick-links a:hover {
            color: #43D240;
        }
        
        /* Date Input Styling */
        input[type="date"] {
            position: relative;
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            padding: 5px;
            margin-right: -5px;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            .avatar-picker {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
<div class="container">
    
    <div class="header">
        <div class="logo">🎓</div>
        <h1>Willkommen!</h1>
        <p>Erstelle deinen Account und starte mit dem Lernen</p>
    </div>
    
    <?php if ($error): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="post" id="registerForm">
        
        <!-- Name -->
        <div class="form-group">
            <label for="name">👋 Wie heißt du?</label>
            <input type="text" id="name" name="name" 
                   placeholder="Dein Name" 
                   minlength="2" maxlength="30" required
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        
        <!-- Geburtsdatum (NEU!) -->
        <div class="form-group">
            <label for="birthdate">🎂 Wann hast du Geburtstag?</label>
            <input type="date" id="birthdate" name="birthdate" 
                   min="<?= $minDate ?>" 
                   max="<?= $maxDate ?>" 
                   required
                   value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>">
            <div class="age-info" id="ageInfo">
                <span class="icon" id="ageIcon">🌱</span>
                <div class="text">
                    Du bist <strong id="ageYears">?</strong> Jahre alt!<br>
                    Schwierigkeit: <span class="level" id="ageLevel">Leicht</span>
                </div>
            </div>
        </div>
        
        <!-- Avatar -->
        <div class="form-group">
            <label>🎨 Wähle deinen Avatar</label>
            <div class="avatar-picker">
                <label><input type="radio" name="avatar" value="👧" checked><span>👧</span></label>
                <label><input type="radio" name="avatar" value="👦"><span>👦</span></label>
                <label><input type="radio" name="avatar" value="🧒"><span>🧒</span></label>
                <label><input type="radio" name="avatar" value="🧑"><span>🧑</span></label>
                <label><input type="radio" name="avatar" value="🦸"><span>🦸</span></label>
                <label><input type="radio" name="avatar" value="🧙"><span>🧙</span></label>
                <label><input type="radio" name="avatar" value="🦊"><span>🦊</span></label>
                <label><input type="radio" name="avatar" value="🐱"><span>🐱</span></label>
                <label><input type="radio" name="avatar" value="🐶"><span>🐶</span></label>
                <label><input type="radio" name="avatar" value="🦁"><span>🦁</span></label>
                <label><input type="radio" name="avatar" value="🐼"><span>🐼</span></label>
                <label><input type="radio" name="avatar" value="🦄"><span>🦄</span></label>
            </div>
        </div>
        
        <!-- PIN -->
        <div class="form-group">
            <label>🔐 Wähle eine 4-stellige PIN</label>
            <p style="font-size: 12px; color: #666; margin-bottom: 10px;">
                Damit nur DU an deine Sats kommst!
            </p>
            <div class="pin-container">
                <input type="password" maxlength="1" class="pin-digit" data-index="0" inputmode="numeric" pattern="[0-9]">
                <input type="password" maxlength="1" class="pin-digit" data-index="1" inputmode="numeric" pattern="[0-9]">
                <input type="password" maxlength="1" class="pin-digit" data-index="2" inputmode="numeric" pattern="[0-9]">
                <input type="password" maxlength="1" class="pin-digit" data-index="3" inputmode="numeric" pattern="[0-9]">
            </div>
            <input type="hidden" name="pin" id="pinFull">
        </div>
        
        <!-- PIN Bestätigung -->
        <div class="form-group">
            <label>🔐 PIN wiederholen</label>
            <div class="pin-container">
                <input type="password" maxlength="1" class="pin-confirm-digit" data-index="0" inputmode="numeric" pattern="[0-9]">
                <input type="password" maxlength="1" class="pin-confirm-digit" data-index="1" inputmode="numeric" pattern="[0-9]">
                <input type="password" maxlength="1" class="pin-confirm-digit" data-index="2" inputmode="numeric" pattern="[0-9]">
                <input type="password" maxlength="1" class="pin-confirm-digit" data-index="3" inputmode="numeric" pattern="[0-9]">
            </div>
            <input type="hidden" name="pin_confirm" id="pinConfirmFull">
        </div>
        
        <button type="submit" class="btn btn-primary">
            ✨ Account erstellen
        </button>
        
    </form>
    
    <div class="divider">Schon registriert?</div>
    
    <a href="login.php" class="btn btn-secondary" style="text-decoration: none; text-align: center;">
        🔑 Zum Login
    </a>
    
    <!-- Quick Links -->
    <div class="quick-links">
        <a href="../admin_v4.php">🔧 Admin Center</a>
    </div>
    
</div>

<script>
// Geburtsdatum → Alter berechnen
const birthdateInput = document.getElementById('birthdate');
const ageInfo = document.getElementById('ageInfo');
const ageIcon = document.getElementById('ageIcon');
const ageLevel = document.getElementById('ageLevel');
const ageYears = document.getElementById('ageYears');

birthdateInput.addEventListener('change', function() {
    const birthdate = new Date(this.value);
    const today = new Date();
    
    let age = today.getFullYear() - birthdate.getFullYear();
    const monthDiff = today.getMonth() - birthdate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
        age--;
    }
    
    if (age >= 5) {
        ageInfo.classList.add('show');
        ageYears.textContent = age;
        
        if (age <= 7) {
            ageIcon.textContent = '🌱';
            ageLevel.textContent = 'Leicht';
            ageLevel.style.color = '#43D240';
        } else if (age <= 12) {
            ageIcon.textContent = '🌿';
            ageLevel.textContent = 'Mittel';
            ageLevel.style.color = '#f7931a';
        } else if (age <= 16) {
            ageIcon.textContent = '🌳';
            ageLevel.textContent = 'Fortgeschritten';
            ageLevel.style.color = '#1A3503';
        } else {
            ageIcon.textContent = '🎓';
            ageLevel.textContent = 'Experte';
            ageLevel.style.color = '#6f42c1';
        }
    } else {
        ageInfo.classList.remove('show');
    }
});

// PIN Input Handling
function setupPinInputs(containerClass, hiddenFieldId) {
    const inputs = document.querySelectorAll('.' + containerClass);
    const hiddenField = document.getElementById(hiddenFieldId);
    
    inputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            // Nur Zahlen erlauben
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto-Focus zum nächsten
            if (this.value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            
            // Hidden Field updaten
            updateHiddenPin();
        });
        
        input.addEventListener('keydown', function(e) {
            // Backspace: Zum vorherigen
            if (e.key === 'Backspace' && !this.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
        
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const digits = paste.replace(/[^0-9]/g, '').slice(0, 4);
            
            digits.split('').forEach((digit, i) => {
                if (inputs[i]) {
                    inputs[i].value = digit;
                }
            });
            
            updateHiddenPin();
            
            if (digits.length === 4 && inputs[3]) {
                inputs[3].focus();
            }
        });
    });
    
    function updateHiddenPin() {
        let pin = '';
        inputs.forEach(input => {
            pin += input.value;
        });
        hiddenField.value = pin;
    }
}

setupPinInputs('pin-digit', 'pinFull');
setupPinInputs('pin-confirm-digit', 'pinConfirmFull');

// Trigger age info if birthdate exists
if (birthdateInput.value) {
    birthdateInput.dispatchEvent(new Event('change'));
}
</script>

</body>
</html>
