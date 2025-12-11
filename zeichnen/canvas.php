<?php
/**
 * sgiT Education - Zeichnen Canvas v3.0
 * Hauptzeichenfläche mit Fabric.js
 * 
 * NEUERUNGEN v3.0 (11.12.2025):
 * - Erweiterte Brushes: Marker, Kreide, Neon, Aquarell, Airbrush
 * - HSL-Farbkreis mit Pipette
 * - Verbesserte Brush-Auswahl mit Icons
 * 
 * NEUERUNGEN v2.0 (07.12.2025):
 * - Undo/Redo History (bis zu 20 Schritte)
 * - Erweiterte Farbpalette
 * - Bessere Tutorial-Anzeige
 * - Touch-Support für Tablets
 * - Vorlagen/Templates
 * 
 * @version 3.0
 * @date 11.12.2025
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /adaptive_learning.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Künstler';
$userAge = $_SESSION['user_age'] ?? 10;

$mode = $_GET['mode'] ?? 'free';
$tutorialId = $_GET['tutorial'] ?? null;

// Tutorial-Daten laden
$tutorialData = null;
if ($tutorialId) {
    $tutorialFile = __DIR__ . "/tutorials/{$tutorialId}.json";
    if (file_exists($tutorialFile)) {
        $tutorialData = json_decode(file_get_contents($tutorialFile), true);
    }
}

// Werkzeuge basierend auf Alter - ERWEITERT v3.0
$basicTools = ['pencil', 'brush', 'marker', 'eraser'];
$advancedTools = ['chalk', 'neon', 'line', 'rectangle', 'circle', 'triangle'];
$proTools = ['watercolor', 'airbrush', 'text', 'fill'];

$tools = $basicTools;
if ($userAge >= 8) $tools = array_merge($tools, $advancedTools);
if ($userAge >= 12) $tools = array_merge($tools, $proTools);

// Farbpalette nach Alter
$basicColors = ['#000000', '#FF0000', '#FF8000', '#FFFF00', '#00FF00', '#00FFFF', '#0000FF', '#FF00FF', '#FFFFFF'];
$extendedColors = ['#8B4513', '#FFC0CB', '#FFD700', '#90EE90', '#ADD8E6', '#DDA0DD', '#F0E68C', '#E6E6FA'];
$proColors = ['#2F4F4F', '#800000', '#008080', '#4B0082', '#FF6347', '#7B68EE', '#3CB371', '#DC143C'];

$colors = $basicColors;
if ($userAge >= 8) $colors = array_merge($colors, $extendedColors);
if ($userAge >= 12) $colors = array_merge($colors, $proColors);

// sgiT Farben immer dabei
$sgitColors = ['#1A3503', '#43D240', '#E86F2C'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>🎨 <?= $tutorialData ? htmlspecialchars($tutorialData['title']) : 'Freies Zeichnen' ?> - sgiT</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <script src="/zeichnen/js/brushes.js"></script>
    <script src="/zeichnen/js/colorpicker.js"></script>
    <style>
        :root {
            --sgit-dark: #1A3503;
            --sgit-green: #43D240;
            --sgit-orange: #E86F2C;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #1a1a1a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Header */
        .header {
            background: var(--sgit-dark);
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .header h1 { font-size: 1.2em; white-space: nowrap; }
        .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .header-actions button, .header-actions a {
            background: var(--sgit-green);
            color: var(--sgit-dark);
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.85em;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .header-actions button:hover { opacity: 0.9; }
        .header-actions .undo-redo { background: #444; color: white; }
        .header-actions .undo-redo:disabled { opacity: 0.4; cursor: not-allowed; }
        
        /* Main Layout */
        .main-content {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        
        /* Sidebar Toolbar */
        .sidebar {
            width: 70px;
            background: #252525;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            overflow-y: auto;
        }
        .sidebar .tool-btn {
            width: 50px;
            height: 50px;
            background: #333;
            border: 2px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.4em;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .sidebar .tool-btn:hover { background: #444; }
        .sidebar .tool-btn.active {
            background: var(--sgit-green);
            border-color: white;
        }
        .sidebar .divider {
            height: 1px;
            background: #444;
            margin: 5px 0;
        }
        
        /* Canvas Area */
        .canvas-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #2a2a2a;
        }
        
        /* Color & Size Bar */
        .options-bar {
            background: #333;
            padding: 10px 15px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .option-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .option-group label {
            color: #aaa;
            font-size: 0.85em;
        }
        .color-palette {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            max-width: 300px;
        }
        .color-btn {
            width: 24px;
            height: 24px;
            border: 2px solid #555;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.1s;
        }
        .color-btn:hover { transform: scale(1.15); }
        .color-btn.active { border-color: white; transform: scale(1.2); }
        .size-slider {
            width: 100px;
            accent-color: var(--sgit-green);
        }
        .size-preview {
            width: 30px;
            height: 30px;
            background: #222;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .size-dot {
            background: var(--sgit-green);
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        /* Canvas Container */
        .canvas-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
            overflow: auto;
        }
        #drawing-canvas {
            border: 3px solid var(--sgit-green);
            border-radius: 8px;
            box-shadow: 0 0 30px rgba(67, 210, 64, 0.2);
        }
        
        /* Tutorial Panel */
        .tutorial-panel {
            width: 300px;
            background: #252525;
            padding: 15px;
            overflow-y: auto;
            border-left: 2px solid #333;
        }
        .tutorial-panel h3 {
            color: white;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tutorial-panel .reward {
            background: var(--sgit-orange);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .step-card {
            background: #333;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 3px solid #555;
            transition: all 0.3s;
        }
        .step-card.active {
            background: #3a3a3a;
            border-left-color: var(--sgit-green);
        }
        .step-card.completed {
            border-left-color: var(--sgit-green);
            opacity: 0.7;
        }
        .step-card .step-num {
            color: var(--sgit-green);
            font-weight: bold;
            font-size: 0.85em;
        }
        .step-card .instruction {
            color: white;
            margin: 5px 0;
            line-height: 1.4;
        }
        .step-card .tip {
            color: #888;
            font-size: 0.85em;
            font-style: italic;
        }
        .step-nav {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .step-nav button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .step-nav .prev { background: #444; color: white; }
        .step-nav .next { background: var(--sgit-green); color: var(--sgit-dark); }
        .step-nav button:disabled { opacity: 0.4; cursor: not-allowed; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.85);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            padding: 35px;
            border-radius: 20px;
            text-align: center;
            max-width: 420px;
            animation: modalPop 0.3s ease;
        }
        @keyframes modalPop {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-content h2 { color: var(--sgit-dark); margin-bottom: 15px; }
        .modal-content .emoji { font-size: 4em; margin-bottom: 15px; }
        .modal-content .sats-earned {
            font-size: 2.5em;
            color: var(--sgit-orange);
            font-weight: bold;
            margin: 15px 0;
        }
        .modal-content .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        .modal-content button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
        }
        .modal-content .btn-primary { background: var(--sgit-green); color: var(--sgit-dark); }
        .modal-content .btn-secondary { background: #eee; color: #333; }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--sgit-dark);
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: bold;
            z-index: 999;
            animation: toastSlide 0.3s ease;
        }
        @keyframes toastSlide {
            from { transform: translateX(-50%) translateY(50px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        
        /* Keyboard Shortcuts Hint */
        .shortcuts-hint {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: #888;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.75em;
        }
        .shortcuts-hint kbd {
            background: #444;
            padding: 2px 6px;
            border-radius: 3px;
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>🎨 <?= $tutorialData ? htmlspecialchars($tutorialData['title']) : 'Freies Zeichnen' ?></h1>
        <div class="header-actions">
            <button class="undo-redo" onclick="undo()" id="undoBtn" disabled title="Rückgängig (Strg+Z)">↩️</button>
            <button class="undo-redo" onclick="redo()" id="redoBtn" disabled title="Wiederholen (Strg+Y)">↪️</button>
            <button onclick="clearCanvas()" title="Alles löschen">🗑️ Löschen</button>
            <button onclick="saveDrawing()" style="background: var(--sgit-orange);">💾 Speichern</button>
            <a href="index.php">← Zurück</a>
        </div>
    </div>
    
    <div class="main-content">
        <!-- Sidebar Tools - v3.0 erweitert -->
        <div class="sidebar">
            <!-- Zeichenwerkzeuge -->
            <?php if (in_array('pencil', $tools)): ?>
            <button class="tool-btn active" onclick="setTool('pencil')" id="tool-pencil" title="Stift">✏️</button>
            <?php endif; ?>
            <?php if (in_array('brush', $tools)): ?>
            <button class="tool-btn" onclick="setTool('brush')" id="tool-brush" title="Pinsel">🖌️</button>
            <?php endif; ?>
            <?php if (in_array('marker', $tools)): ?>
            <button class="tool-btn" onclick="setTool('marker')" id="tool-marker" title="Marker">🖍️</button>
            <?php endif; ?>
            <?php if (in_array('chalk', $tools)): ?>
            <button class="tool-btn" onclick="setTool('chalk')" id="tool-chalk" title="Kreide">🪨</button>
            <?php endif; ?>
            <?php if (in_array('neon', $tools)): ?>
            <button class="tool-btn" onclick="setTool('neon')" id="tool-neon" title="Neon">✨</button>
            <?php endif; ?>
            <?php if (in_array('watercolor', $tools)): ?>
            <button class="tool-btn" onclick="setTool('watercolor')" id="tool-watercolor" title="Aquarell">💧</button>
            <?php endif; ?>
            <?php if (in_array('airbrush', $tools)): ?>
            <button class="tool-btn" onclick="setTool('airbrush')" id="tool-airbrush" title="Airbrush">💨</button>
            <?php endif; ?>
            
            <div class="divider"></div>
            
            <!-- Radierer -->
            <?php if (in_array('eraser', $tools)): ?>
            <button class="tool-btn" onclick="setTool('eraser')" id="tool-eraser" title="Radierer">🧽</button>
            <?php endif; ?>
            
            <div class="divider"></div>
            
            <!-- Formen -->
            <?php if (in_array('line', $tools)): ?>
            <button class="tool-btn" onclick="setTool('line')" id="tool-line" title="Linie">📏</button>
            <?php endif; ?>
            <?php if (in_array('rectangle', $tools)): ?>
            <button class="tool-btn" onclick="setTool('rectangle')" id="tool-rectangle" title="Rechteck">⬜</button>
            <?php endif; ?>
            <?php if (in_array('circle', $tools)): ?>
            <button class="tool-btn" onclick="setTool('circle')" id="tool-circle" title="Kreis">⭕</button>
            <?php endif; ?>
            <?php if (in_array('triangle', $tools)): ?>
            <button class="tool-btn" onclick="setTool('triangle')" id="tool-triangle" title="Dreieck">🔺</button>
            <?php endif; ?>
            
            <div class="divider"></div>
            
            <!-- Color Picker Toggle -->
            <button class="tool-btn" onclick="toggleColorPicker()" id="tool-colorpicker" title="Farbkreis">🎨</button>
        </div>
        
        <!-- Canvas Area -->
        <div class="canvas-area">
            <!-- Options Bar -->
            <div class="options-bar">
                <div class="option-group">
                    <label>Farbe:</label>
                    <div class="color-palette">
                        <?php foreach ($colors as $i => $color): ?>
                        <button class="color-btn <?= $i === 0 ? 'active' : '' ?>" 
                                style="background: <?= $color ?>" 
                                onclick="setColor('<?= $color ?>')"
                                data-color="<?= $color ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="color" id="customColor" value="#43D240" 
                           onchange="setColor(this.value)" title="Eigene Farbe" 
                           style="width:30px;height:30px;border:none;cursor:pointer;">
                </div>
                <div class="option-group">
                    <label>Größe:</label>
                    <input type="range" class="size-slider" id="brushSize" 
                           min="1" max="50" value="5" oninput="setBrushSize(this.value)">
                    <div class="size-preview">
                        <div class="size-dot" id="sizeDot" style="width:5px;height:5px;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Canvas -->
            <div class="canvas-container">
                <canvas id="drawing-canvas"></canvas>
            </div>
        </div>
        
        <?php if ($tutorialData): ?>
        <!-- Tutorial Panel -->
        <div class="tutorial-panel">
            <h3>
                📚 Anleitung
                <span class="reward">+<?= $tutorialData['sats_reward'] ?? 10 ?> Sats</span>
            </h3>
            <?php foreach ($tutorialData['steps'] ?? [] as $i => $step): ?>
            <div class="step-card <?= $i === 0 ? 'active' : '' ?>" id="step-<?= $i ?>">
                <div class="step-num">Schritt <?= $i + 1 ?></div>
                <div class="instruction"><?= htmlspecialchars($step['instruction']) ?></div>
                <?php if (!empty($step['tip'])): ?>
                <div class="tip">💡 <?= htmlspecialchars($step['tip']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="step-nav">
                <button class="prev" onclick="prevStep()" id="prevStepBtn" disabled>← Zurück</button>
                <button class="next" onclick="nextStep()" id="nextStepBtn">Weiter →</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Save Modal -->
    <div class="modal" id="saveModal">
        <div class="modal-content">
            <div class="emoji">🎉</div>
            <h2>Super gemacht!</h2>
            <p>Dein Kunstwerk wurde gespeichert!</p>
            <div class="sats-earned" id="satsEarned">+10 Sats</div>
            <div class="actions">
                <button class="btn-secondary" onclick="closeModal()">Weiter zeichnen</button>
                <button class="btn-primary" onclick="location.href='gallery.php'">Zur Galerie</button>
            </div>
        </div>
    </div>
    
    <!-- Shortcuts Hint -->
    <div class="shortcuts-hint">
        <kbd>Strg</kbd>+<kbd>Z</kbd> Rückgängig | 
        <kbd>Strg</kbd>+<kbd>S</kbd> Speichern
    </div>
    
    <!-- Color Picker Popup v3.0 -->
    <div class="colorpicker-popup" id="colorPickerPopup" style="display:none; position:fixed; top:100px; left:80px; z-index:1000;">
        <div class="colorpicker-header" style="background:#1A3503; color:white; padding:8px 12px; border-radius:12px 12px 0 0; display:flex; justify-content:space-between; align-items:center; cursor:move;">
            <span>🎨 Farbkreis</span>
            <button onclick="toggleColorPicker()" style="background:none; border:none; color:white; font-size:18px; cursor:pointer;">✕</button>
        </div>
        <div id="colorPickerContainer"></div>
    </div>
    
    <script>
        // =====================================================
        // CANVAS SETUP
        // =====================================================
        const canvasWidth = Math.min(window.innerWidth - <?= $tutorialData ? 450 : 150 ?>, 900);
        const canvasHeight = Math.min(window.innerHeight - 180, 600);
        
        const canvas = new fabric.Canvas('drawing-canvas', {
            isDrawingMode: true,
            width: canvasWidth,
            height: canvasHeight,
            backgroundColor: '#FFFFFF'
        });
        
        // Standard-Pinsel
        canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
        canvas.freeDrawingBrush.width = 5;
        canvas.freeDrawingBrush.color = '#000000';
        
        // =====================================================
        // STATE
        // =====================================================
        let currentTool = 'pencil';
        let currentColor = '#000000';
        let brushSize = 5;
        let currentStep = 0;
        const totalSteps = <?= count($tutorialData['steps'] ?? []) ?>;
        
        // Undo/Redo History
        const history = [];
        const redoStack = [];
        const maxHistory = 20;
        
        // Save state after each action
        canvas.on('object:added', saveState);
        canvas.on('object:modified', saveState);
        canvas.on('object:removed', saveState);
        
        function saveState() {
            if (history.length >= maxHistory) history.shift();
            history.push(JSON.stringify(canvas.toJSON()));
            redoStack.length = 0;
            updateUndoRedoButtons();
        }
        
        function undo() {
            if (history.length <= 1) return;
            redoStack.push(history.pop());
            const state = history[history.length - 1];
            canvas.loadFromJSON(state, () => {
                canvas.renderAll();
                updateUndoRedoButtons();
            });
        }
        
        function redo() {
            if (redoStack.length === 0) return;
            const state = redoStack.pop();
            history.push(state);
            canvas.loadFromJSON(state, () => {
                canvas.renderAll();
                updateUndoRedoButtons();
            });
        }
        
        function updateUndoRedoButtons() {
            document.getElementById('undoBtn').disabled = history.length <= 1;
            document.getElementById('redoBtn').disabled = redoStack.length === 0;
        }
        
        // Initial state
        saveState();
        
        // =====================================================
        // TOOLS - v3.0 mit erweiterten Brushes
        // =====================================================
        const drawingTools = ['pencil', 'brush', 'marker', 'chalk', 'neon', 'watercolor', 'airbrush'];
        
        function setTool(tool) {
            document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tool-' + tool)?.classList.add('active');
            currentTool = tool;
            
            if (drawingTools.includes(tool)) {
                canvas.isDrawingMode = true;
                
                // Brush Factory verwenden für erweiterte Brushes
                switch(tool) {
                    case 'pencil':
                        canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
                        break;
                    case 'brush':
                        canvas.freeDrawingBrush = new fabric.CircleBrush(canvas);
                        break;
                    case 'marker':
                        canvas.freeDrawingBrush = new fabric.MarkerBrush(canvas);
                        break;
                    case 'chalk':
                        canvas.freeDrawingBrush = new fabric.ChalkBrush(canvas);
                        break;
                    case 'neon':
                        canvas.freeDrawingBrush = new fabric.NeonBrush(canvas);
                        break;
                    case 'watercolor':
                        canvas.freeDrawingBrush = new fabric.WatercolorBrush(canvas);
                        break;
                    case 'airbrush':
                        canvas.freeDrawingBrush = new fabric.AirbrushBrush(canvas);
                        break;
                    default:
                        canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
                }
                
                canvas.freeDrawingBrush.width = brushSize;
                canvas.freeDrawingBrush.color = currentColor;
            } else if (tool === 'eraser') {
                canvas.isDrawingMode = true;
                canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
                canvas.freeDrawingBrush.width = brushSize * 3;
                canvas.freeDrawingBrush.color = '#FFFFFF';
            } else {
                canvas.isDrawingMode = false;
            }
        }
        
        function setColor(color) {
            currentColor = color;
            document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('active'));
            document.querySelector(`.color-btn[data-color="${color}"]`)?.classList.add('active');
            document.getElementById('customColor').value = color;
            
            if (canvas.isDrawingMode && currentTool !== 'eraser') {
                canvas.freeDrawingBrush.color = color;
            }
        }
        
        function setBrushSize(size) {
            brushSize = parseInt(size);
            if (canvas.isDrawingMode) {
                canvas.freeDrawingBrush.width = currentTool === 'eraser' ? brushSize * 3 : brushSize;
            }
            const dot = document.getElementById('sizeDot');
            const displaySize = Math.min(size, 25);
            dot.style.width = displaySize + 'px';
            dot.style.height = displaySize + 'px';
        }
        
        // =====================================================
        // ACTIONS
        // =====================================================
        function clearCanvas() {
            if (confirm('Wirklich alles löschen?')) {
                canvas.clear();
                canvas.backgroundColor = '#FFFFFF';
                canvas.renderAll();
                history.length = 0;
                redoStack.length = 0;
                saveState();
                showToast('🗑️ Canvas gelöscht!');
            }
        }
        
        async function saveDrawing() {
            const dataURL = canvas.toDataURL({ format: 'png', quality: 0.9 });
            
            try {
                const response = await fetch('save_drawing.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        image: dataURL,
                        tutorial: '<?= $tutorialId ?? '' ?>',
                        mode: '<?= $mode ?>'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('satsEarned').textContent = '+' + result.sats + ' Sats';
                    document.getElementById('saveModal').classList.add('show');
                } else {
                    showToast('❌ Fehler: ' + result.error);
                }
            } catch (err) {
                showToast('❌ Fehler: ' + err.message);
            }
        }
        
        function closeModal() {
            document.getElementById('saveModal').classList.remove('show');
        }
        
        function showToast(message) {
            const existing = document.querySelector('.toast');
            if (existing) existing.remove();
            
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        // =====================================================
        // TUTORIAL STEPS
        // =====================================================
        <?php if ($tutorialData): ?>
        function updateStepDisplay() {
            document.querySelectorAll('.step-card').forEach((card, i) => {
                card.classList.remove('active', 'completed');
                if (i < currentStep) card.classList.add('completed');
                if (i === currentStep) card.classList.add('active');
            });
            document.getElementById('prevStepBtn').disabled = currentStep === 0;
            document.getElementById('nextStepBtn').textContent = 
                currentStep >= totalSteps - 1 ? '✓ Fertig!' : 'Weiter →';
        }
        
        function nextStep() {
            if (currentStep < totalSteps - 1) {
                currentStep++;
                updateStepDisplay();
            } else {
                showToast('🎉 Tutorial abgeschlossen!');
            }
        }
        
        function prevStep() {
            if (currentStep > 0) {
                currentStep--;
                updateStepDisplay();
            }
        }
        <?php endif; ?>
        
        // =====================================================
        // KEYBOARD SHORTCUTS
        // =====================================================
        document.addEventListener('keydown', (e) => {
            // Strg+Z = Undo
            if (e.ctrlKey && e.key === 'z') {
                e.preventDefault();
                undo();
            }
            // Strg+Y = Redo
            if (e.ctrlKey && e.key === 'y') {
                e.preventDefault();
                redo();
            }
            // Strg+S = Speichern
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                saveDrawing();
            }
            // 1-9 = Werkzeuge (optional)
            const toolKeys = {'1': 'pencil', '2': 'brush', '3': 'eraser'};
            if (toolKeys[e.key] && document.getElementById('tool-' + toolKeys[e.key])) {
                setTool(toolKeys[e.key]);
            }
        });
        
        // =====================================================
        // SHAPE DRAWING (für Line, Rectangle, Circle, Triangle)
        // =====================================================
        let isDrawingShape = false;
        let shapeStartX, shapeStartY;
        let currentShape = null;
        
        canvas.on('mouse:down', function(opt) {
            if (['line', 'rectangle', 'circle', 'triangle'].includes(currentTool)) {
                isDrawingShape = true;
                const pointer = canvas.getPointer(opt.e);
                shapeStartX = pointer.x;
                shapeStartY = pointer.y;
                
                if (currentTool === 'line') {
                    currentShape = new fabric.Line([shapeStartX, shapeStartY, shapeStartX, shapeStartY], {
                        stroke: currentColor, strokeWidth: brushSize, selectable: false
                    });
                } else if (currentTool === 'rectangle') {
                    currentShape = new fabric.Rect({
                        left: shapeStartX, top: shapeStartY, width: 0, height: 0,
                        fill: 'transparent', stroke: currentColor, strokeWidth: brushSize, selectable: false
                    });
                } else if (currentTool === 'circle') {
                    currentShape = new fabric.Circle({
                        left: shapeStartX, top: shapeStartY, radius: 0,
                        fill: 'transparent', stroke: currentColor, strokeWidth: brushSize, selectable: false
                    });
                } else if (currentTool === 'triangle') {
                    currentShape = new fabric.Triangle({
                        left: shapeStartX, top: shapeStartY, width: 0, height: 0,
                        fill: 'transparent', stroke: currentColor, strokeWidth: brushSize, selectable: false
                    });
                }
                canvas.add(currentShape);
            }
        });
        
        canvas.on('mouse:move', function(opt) {
            if (!isDrawingShape || !currentShape) return;
            const pointer = canvas.getPointer(opt.e);
            
            if (currentTool === 'line') {
                currentShape.set({ x2: pointer.x, y2: pointer.y });
            } else if (currentTool === 'rectangle' || currentTool === 'triangle') {
                const width = Math.abs(pointer.x - shapeStartX);
                const height = Math.abs(pointer.y - shapeStartY);
                currentShape.set({
                    left: Math.min(pointer.x, shapeStartX),
                    top: Math.min(pointer.y, shapeStartY),
                    width: width, height: height
                });
            } else if (currentTool === 'circle') {
                const radius = Math.sqrt(Math.pow(pointer.x - shapeStartX, 2) + Math.pow(pointer.y - shapeStartY, 2)) / 2;
                currentShape.set({ radius: radius });
            }
            canvas.renderAll();
        });
        
        canvas.on('mouse:up', function() {
            isDrawingShape = false;
            currentShape = null;
        });
        
        // Responsive
        window.addEventListener('resize', () => {
            const newWidth = Math.min(window.innerWidth - <?= $tutorialData ? 450 : 150 ?>, 900);
            const newHeight = Math.min(window.innerHeight - 180, 600);
            canvas.setWidth(newWidth);
            canvas.setHeight(newHeight);
            canvas.renderAll();
        });
        
        // =====================================================
        // COLOR PICKER v3.0
        // =====================================================
        let colorPickerVisible = false;
        let colorPicker = null;
        
        function initColorPicker() {
            colorPicker = new SGITColorPicker({
                initialColor: currentColor,
                onChange: (color) => {
                    setColor(color);
                    colorPicker.addRecentColor(color);
                },
                onPipetteStart: () => {
                    canvas.isDrawingMode = false;
                    document.body.style.cursor = 'crosshair';
                },
                onPipetteEnd: () => {
                    setTool(currentTool);
                }
            });
            colorPicker.render('colorPickerContainer');
            
            // Draggable machen
            makeDraggable(document.getElementById('colorPickerPopup'));
        }
        
        function toggleColorPicker() {
            const popup = document.getElementById('colorPickerPopup');
            colorPickerVisible = !colorPickerVisible;
            popup.style.display = colorPickerVisible ? 'block' : 'none';
            
            if (colorPickerVisible && !colorPicker) {
                initColorPicker();
            }
            
            // Button-Status aktualisieren
            document.getElementById('tool-colorpicker')?.classList.toggle('active', colorPickerVisible);
        }
        
        // Draggable Helper
        function makeDraggable(element) {
            const header = element.querySelector('.colorpicker-header');
            let isDragging = false;
            let offsetX, offsetY;
            
            header.addEventListener('mousedown', (e) => {
                isDragging = true;
                offsetX = e.clientX - element.offsetLeft;
                offsetY = e.clientY - element.offsetTop;
            });
            
            document.addEventListener('mousemove', (e) => {
                if (isDragging) {
                    element.style.left = (e.clientX - offsetX) + 'px';
                    element.style.top = (e.clientY - offsetY) + 'px';
                }
            });
            
            document.addEventListener('mouseup', () => {
                isDragging = false;
            });
        }
        
        // Pipette-Funktionalität für Canvas
        canvas.on('mouse:down', function(opt) {
            if (colorPicker && colorPicker.pipetteActive) {
                const pointer = canvas.getPointer(opt.e);
                const ctx = canvas.getContext();
                const pixel = ctx.getImageData(pointer.x, pointer.y, 1, 1).data;
                const hex = '#' + [pixel[0], pixel[1], pixel[2]].map(x => x.toString(16).padStart(2, '0')).join('').toUpperCase();
                
                colorPicker.setColor(hex);
                colorPicker.stopPipette();
                setColor(hex);
            }
        });
    </script>
</body>
</html>
