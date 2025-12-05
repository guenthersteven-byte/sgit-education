<?php
/**
 * ============================================================================
 * sgiT Education - Foxy Test & Konfiguration v1.4
 * ============================================================================
 */

session_start();
$_SESSION['user_age'] = 10;
$_SESSION['user_name'] = 'TestKind';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🦊 Foxy Konfiguration - sgiT Education</title>
    
    <style>
        :root {
            --primary: #1A3503;
            --secondary: #43D240;
            --fox-orange: #E86F2C;
            --fox-blue: #1E3A5F;
            --fox-beige: #F5E6D3;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, var(--fox-beige) 0%, #fff 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        
        .container { max-width: 1000px; margin: 0 auto; }
        
        header { text-align: center; margin-bottom: 25px; }
        h1 { color: var(--fox-blue); font-size: 2rem; margin-bottom: 8px; }
        h1 span { color: var(--fox-orange); }
        .subtitle { color: #666; }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
        }
        
        .card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .card h2 {
            color: var(--fox-blue);
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Foxy Preview */
        .foxy-preview {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
            background: var(--fox-beige);
            border-radius: 12px;
            margin-bottom: 15px;
        }
        
        .foxy-preview-button {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(145deg, var(--fox-beige) 0%, #e8d9c8 100%);
            border: 4px solid var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 12px;
        }
        
        .foxy-preview-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 30px rgba(232, 111, 44, 0.4);
        }
        
        /* Config Controls */
        .config-group {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .config-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .config-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .config-label {
            color: var(--fox-blue);
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        /* Toggle Switch */
        .toggle {
            position: relative;
            width: 50px;
            height: 26px;
        }
        
        .toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 26px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        
        .toggle input:checked + .toggle-slider {
            background-color: var(--secondary);
        }
        
        .toggle input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        
        /* Range Slider */
        .range-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .range-slider {
            flex: 1;
            height: 6px;
            -webkit-appearance: none;
            background: #ddd;
            border-radius: 3px;
            outline: none;
        }
        
        .range-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            background: var(--fox-orange);
            border-radius: 50%;
            cursor: pointer;
            transition: 0.2s;
        }
        
        .range-slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
        }
        
        .range-value {
            min-width: 35px;
            text-align: center;
            font-weight: 600;
            color: var(--fox-blue);
        }
        
        /* Buttons */
        .btn-row {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            flex: 1;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: var(--fox-blue);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Status */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .status-item {
            background: var(--fox-beige);
            padding: 12px;
            border-radius: 10px;
            text-align: center;
        }
        
        .status-item .icon { font-size: 1.5rem; }
        .status-item .label { font-size: 0.75rem; color: #666; margin-top: 4px; }
        .status-item .value { font-size: 0.85rem; font-weight: 600; color: var(--fox-blue); }
        
        /* Animation Legend */
        .anim-legend {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 10px;
        }
        
        .anim-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: #555;
        }
        
        .anim-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .anim-dot.ears { background: #E86F2C; }
        .anim-dot.eyes { background: #1E3A5F; }
        .anim-dot.nose { background: #333; }
        .anim-dot.idle { background: var(--secondary); }
        
        footer {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }
        
        footer a { color: var(--secondary); text-decoration: none; }
    </style>
    
    <link rel="stylesheet" href="clippy.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🦊 <span>Foxy</span> Konfiguration</h1>
            <p class="subtitle">Animationen anpassen und testen</p>
        </header>
        
        <div class="grid">
            <!-- Linke Spalte: Preview -->
            <div class="card">
                <h2>👀 Vorschau</h2>
                
                <div class="foxy-preview">
                    <div class="foxy-preview-button" id="foxy-demo">
                        <!-- SVG wird per JS eingefügt -->
                    </div>
                </div>
                
                <div class="anim-legend">
                    <div class="anim-item"><span class="anim-dot ears"></span> Ohren zucken</div>
                    <div class="anim-item"><span class="anim-dot eyes"></span> Augen blinzeln</div>
                    <div class="anim-item"><span class="anim-dot nose"></span> Nase wackelt</div>
                    <div class="anim-item"><span class="anim-dot idle"></span> Idle schweben</div>
                </div>
                
                <div class="status-grid" style="margin-top: 15px;">
                    <div class="status-item">
                        <div class="icon">🤖</div>
                        <div class="label">Ollama</div>
                        <div class="value" id="ollama-status">...</div>
                    </div>
                    <div class="status-item">
                        <div class="icon">💬</div>
                        <div class="label">Antworten</div>
                        <div class="value" id="response-count">...</div>
                    </div>
                    <div class="status-item">
                        <div class="icon">👤</div>
                        <div class="label">User</div>
                        <div class="value">TestKind</div>
                    </div>
                    <div class="status-item">
                        <div class="icon">🎨</div>
                        <div class="label">Version</div>
                        <div class="value">v1.4</div>
                    </div>
                </div>
            </div>
            
            <!-- Rechte Spalte: Konfiguration -->
            <div class="card">
                <h2>⚙️ Animation Einstellungen</h2>
                
                <!-- Animationen Ein/Aus -->
                <div class="config-group">
                    <div class="config-row">
                        <span class="config-label">👂 Ohren Animation</span>
                        <label class="toggle">
                            <input type="checkbox" id="cfg-ear" checked onchange="updateConfig('earAnimation', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="config-row">
                        <span class="config-label">👀 Augen Animation</span>
                        <label class="toggle">
                            <input type="checkbox" id="cfg-eye" checked onchange="updateConfig('eyeAnimation', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="config-row">
                        <span class="config-label">👃 Nase Animation</span>
                        <label class="toggle">
                            <input type="checkbox" id="cfg-nose" checked onchange="updateConfig('noseAnimation', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="config-row">
                        <span class="config-label">🌊 Idle Animation</span>
                        <label class="toggle">
                            <input type="checkbox" id="cfg-idle" checked onchange="updateConfig('idleAnimation', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <!-- Geschwindigkeiten -->
                <div class="config-group">
                    <div class="config-row">
                        <span class="config-label">👂 Ohren Geschw.</span>
                        <div class="range-container">
                            <input type="range" class="range-slider" id="cfg-ear-speed" min="1" max="8" value="4" 
                                   oninput="updateConfig('earSpeed', this.value); document.getElementById('ear-speed-val').textContent = this.value + 's'">
                            <span class="range-value" id="ear-speed-val">4s</span>
                        </div>
                    </div>
                    
                    <div class="config-row">
                        <span class="config-label">👀 Augen Geschw.</span>
                        <div class="range-container">
                            <input type="range" class="range-slider" id="cfg-eye-speed" min="1" max="8" value="4"
                                   oninput="updateConfig('eyeSpeed', this.value); document.getElementById('eye-speed-val').textContent = this.value + 's'">
                            <span class="range-value" id="eye-speed-val">4s</span>
                        </div>
                    </div>
                    
                    <div class="config-row">
                        <span class="config-label">👃 Nase Geschw.</span>
                        <div class="range-container">
                            <input type="range" class="range-slider" id="cfg-nose-speed" min="1" max="8" value="3"
                                   oninput="updateConfig('noseSpeed', this.value); document.getElementById('nose-speed-val').textContent = this.value + 's'">
                            <span class="range-value" id="nose-speed-val">3s</span>
                        </div>
                    </div>
                    
                    <div class="config-row">
                        <span class="config-label">🌊 Idle Geschw.</span>
                        <div class="range-container">
                            <input type="range" class="range-slider" id="cfg-idle-speed" min="1" max="8" value="3"
                                   oninput="updateConfig('idleSpeed', this.value); document.getElementById('idle-speed-val').textContent = this.value + 's'">
                            <span class="range-value" id="idle-speed-val">3s</span>
                        </div>
                    </div>
                </div>
                
                <div class="btn-row">
                    <button class="btn btn-primary" onclick="resetConfig()">🔄 Zurücksetzen</button>
                    <button class="btn btn-secondary" onclick="testFoxy()">🧪 Chat testen</button>
                </div>
            </div>
        </div>
        
        <footer>
            <p>sgiT Education Platform v3.0.0</p>
            <p><a href="../adaptive_learning.php">← Zur Lernplattform</a> | <a href="seed_responses.php">DB Seeden</a></p>
        </footer>
    </div>
    
    <script>
        window.userAge = 10;
        window.currentModule = null;
        window.userName = 'TestKind';
    </script>
    <script src="clippy.js"></script>
    
    <script>
        // Demo Foxy einsetzen
        document.addEventListener('DOMContentLoaded', () => {
            if (window.Foxy) {
                document.getElementById('foxy-demo').innerHTML = window.Foxy.getFoxySVG('large');
                loadConfigUI();
            }
            checkStatus();
        });
        
        // Konfig in UI laden
        function loadConfigUI() {
            if (!window.Foxy) return;
            const cfg = window.Foxy.getConfig();
            
            document.getElementById('cfg-ear').checked = cfg.earAnimation;
            document.getElementById('cfg-eye').checked = cfg.eyeAnimation;
            document.getElementById('cfg-nose').checked = cfg.noseAnimation;
            document.getElementById('cfg-idle').checked = cfg.idleAnimation;
            
            document.getElementById('cfg-ear-speed').value = cfg.earSpeed;
            document.getElementById('cfg-eye-speed').value = cfg.eyeSpeed;
            document.getElementById('cfg-nose-speed').value = cfg.noseSpeed;
            document.getElementById('cfg-idle-speed').value = cfg.idleSpeed;
            
            document.getElementById('ear-speed-val').textContent = cfg.earSpeed + 's';
            document.getElementById('eye-speed-val').textContent = cfg.eyeSpeed + 's';
            document.getElementById('nose-speed-val').textContent = cfg.noseSpeed + 's';
            document.getElementById('idle-speed-val').textContent = cfg.idleSpeed + 's';
        }
        
        // Konfig aktualisieren
        function updateConfig(key, value) {
            if (window.Foxy) {
                window.Foxy.setConfig(key, key.includes('Speed') ? parseInt(value) : value);
            }
        }
        
        // Reset
        function resetConfig() {
            if (window.Foxy) {
                window.Foxy.config = {
                    earAnimation: true,
                    eyeAnimation: true,
                    noseAnimation: true,
                    idleAnimation: true,
                    earSpeed: 4,
                    eyeSpeed: 4,
                    noseSpeed: 3,
                    idleSpeed: 3
                };
                window.Foxy.saveConfig();
                window.Foxy.updateAnimations();
                loadConfigUI();
            }
        }
        
        // Chat öffnen
        function testFoxy() {
            if (window.Foxy) {
                window.Foxy.open();
            }
        }
        
        // Status prüfen
        async function checkStatus() {
            try {
                const response = await fetch('api.php?action=status');
                const data = await response.json();
                document.getElementById('ollama-status').textContent = data.online ? '✅ Online' : '⚡ Lokal';
                
                const statsRes = await fetch('api.php?action=stats');
                const stats = await statsRes.json();
                document.getElementById('response-count').textContent = stats.stats?.total_responses || '~25';
            } catch (e) {
                document.getElementById('ollama-status').textContent = '❌';
            }
        }
    </script>
</body>
</html>
