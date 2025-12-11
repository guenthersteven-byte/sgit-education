/**
 * sgiT Education - HSL Color Picker mit Pipette
 * 
 * Features:
 * - HSL-Farbkreis (Hue Ring)
 * - Sättigung/Helligkeit Quadrat
 * - Pipette (Eyedropper) Tool
 * - Zuletzt verwendete Farben
 * - Hex/RGB Input
 * 
 * @version 1.0
 * @date 11.12.2025
 */

class SGITColorPicker {
    
    constructor(options = {}) {
        this.container = null;
        this.currentColor = options.initialColor || '#43D240';
        this.recentColors = options.recentColors || [];
        this.maxRecent = options.maxRecent || 12;
        this.onChange = options.onChange || (() => {});
        this.onPipetteStart = options.onPipetteStart || (() => {});
        this.onPipetteEnd = options.onPipetteEnd || (() => {});
        
        // HSL values
        this.hue = 0;
        this.saturation = 100;
        this.lightness = 50;
        
        // Parse initial color
        this._parseColor(this.currentColor);
        
        // Canvas refs
        this.hueCanvas = null;
        this.slCanvas = null;
        
        // State
        this.isDraggingHue = false;
        this.isDraggingSL = false;
        this.pipetteActive = false;
    }
    
    // =========================================
    // PUBLIC API
    // =========================================
    
    render(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('ColorPicker container not found:', containerId);
            return;
        }
        
        this.container.innerHTML = this._getHTML();
        this._initCanvases();
        this._bindEvents();
        this._updateDisplay();
    }
    
    setColor(color) {
        this._parseColor(color);
        this._updateDisplay();
    }
    
    getColor() {
        return this.currentColor;
    }
    
    addRecentColor(color) {
        if (!this.recentColors.includes(color)) {
            this.recentColors.unshift(color);
            if (this.recentColors.length > this.maxRecent) {
                this.recentColors.pop();
            }
            this._renderRecentColors();
        }
    }
    
    startPipette() {
        this.pipetteActive = true;
        document.body.style.cursor = 'crosshair';
        this.onPipetteStart();
    }
    
    stopPipette() {
        this.pipetteActive = false;
        document.body.style.cursor = 'default';
        this.onPipetteEnd();
    }
    
    // =========================================
    // HTML TEMPLATE
    // =========================================
    
    _getHTML() {
        return `
            <div class="sgit-colorpicker">
                <div class="cp-main">
                    <!-- Hue Ring -->
                    <div class="cp-hue-container">
                        <canvas class="cp-hue-ring" width="200" height="200"></canvas>
                        <div class="cp-hue-pointer"></div>
                        
                        <!-- SL Square inside ring -->
                        <div class="cp-sl-container">
                            <canvas class="cp-sl-square" width="100" height="100"></canvas>
                            <div class="cp-sl-pointer"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Controls -->
                <div class="cp-controls">
                    <!-- Preview & Pipette -->
                    <div class="cp-preview-row">
                        <div class="cp-preview" title="Aktuelle Farbe"></div>
                        <button class="cp-pipette-btn" title="Pipette (Farbe aufnehmen)">
                            <span>🎯</span>
                        </button>
                    </div>
                    
                    <!-- Hex Input -->
                    <div class="cp-input-row">
                        <label>HEX</label>
                        <input type="text" class="cp-hex-input" maxlength="7" placeholder="#43D240">
                    </div>
                    
                    <!-- RGB Sliders -->
                    <div class="cp-slider-row">
                        <label>H</label>
                        <input type="range" class="cp-slider cp-hue-slider" min="0" max="360" value="0">
                        <span class="cp-value cp-hue-value">0°</span>
                    </div>
                    <div class="cp-slider-row">
                        <label>S</label>
                        <input type="range" class="cp-slider cp-sat-slider" min="0" max="100" value="100">
                        <span class="cp-value cp-sat-value">100%</span>
                    </div>
                    <div class="cp-slider-row">
                        <label>L</label>
                        <input type="range" class="cp-slider cp-light-slider" min="0" max="100" value="50">
                        <span class="cp-value cp-light-value">50%</span>
                    </div>
                </div>
                
                <!-- Recent Colors -->
                <div class="cp-recent">
                    <label>Zuletzt verwendet:</label>
                    <div class="cp-recent-colors"></div>
                </div>
                
                <!-- Quick Colors -->
                <div class="cp-quick">
                    <label>sgiT Farben:</label>
                    <div class="cp-quick-colors">
                        <button class="cp-quick-color" data-color="#1A3503" style="background:#1A3503" title="sgiT Dunkelgrün"></button>
                        <button class="cp-quick-color" data-color="#43D240" style="background:#43D240" title="sgiT Neon-Grün"></button>
                        <button class="cp-quick-color" data-color="#E86F2C" style="background:#E86F2C" title="sgiT Orange"></button>
                        <button class="cp-quick-color" data-color="#FFFFFF" style="background:#FFFFFF;border:1px solid #444" title="Weiß"></button>
                        <button class="cp-quick-color" data-color="#000000" style="background:#000000" title="Schwarz"></button>
                    </div>
                </div>
            </div>
        `;
    }
    
    // =========================================
    // CANVAS RENDERING
    // =========================================
    
    _initCanvases() {
        // Hue Ring Canvas
        this.hueCanvas = this.container.querySelector('.cp-hue-ring');
        this._drawHueRing();
        
        // SL Square Canvas
        this.slCanvas = this.container.querySelector('.cp-sl-square');
        this._drawSLSquare();
    }
    
    _drawHueRing() {
        const ctx = this.hueCanvas.getContext('2d');
        const w = this.hueCanvas.width;
        const h = this.hueCanvas.height;
        const cx = w / 2;
        const cy = h / 2;
        const outerRadius = 95;
        const innerRadius = 70;
        
        ctx.clearRect(0, 0, w, h);
        
        // Draw hue ring
        for (let angle = 0; angle < 360; angle++) {
            const startAngle = (angle - 1) * Math.PI / 180;
            const endAngle = (angle + 1) * Math.PI / 180;
            
            ctx.beginPath();
            ctx.arc(cx, cy, outerRadius, startAngle, endAngle);
            ctx.arc(cx, cy, innerRadius, endAngle, startAngle, true);
            ctx.closePath();
            
            ctx.fillStyle = `hsl(${angle}, 100%, 50%)`;
            ctx.fill();
        }
    }
    
    _drawSLSquare() {
        const ctx = this.slCanvas.getContext('2d');
        const w = this.slCanvas.width;
        const h = this.slCanvas.height;
        
        // Create gradient for saturation (left to right)
        const satGradient = ctx.createLinearGradient(0, 0, w, 0);
        satGradient.addColorStop(0, `hsl(${this.hue}, 0%, 50%)`);
        satGradient.addColorStop(1, `hsl(${this.hue}, 100%, 50%)`);
        
        ctx.fillStyle = satGradient;
        ctx.fillRect(0, 0, w, h);
        
        // Create gradient for lightness (top to bottom)
        const lightGradient = ctx.createLinearGradient(0, 0, 0, h);
        lightGradient.addColorStop(0, 'rgba(255,255,255,1)');
        lightGradient.addColorStop(0.5, 'rgba(255,255,255,0)');
        lightGradient.addColorStop(0.5, 'rgba(0,0,0,0)');
        lightGradient.addColorStop(1, 'rgba(0,0,0,1)');
        
        ctx.fillStyle = lightGradient;
        ctx.fillRect(0, 0, w, h);
    }

    // =========================================
    // EVENT BINDING
    // =========================================
    
    _bindEvents() {
        // Hue Ring Events
        const hueContainer = this.container.querySelector('.cp-hue-container');
        hueContainer.addEventListener('mousedown', (e) => this._onHueMouseDown(e));
        document.addEventListener('mousemove', (e) => this._onHueMouseMove(e));
        document.addEventListener('mouseup', () => this._onHueMouseUp());
        
        // SL Square Events
        const slContainer = this.container.querySelector('.cp-sl-container');
        slContainer.addEventListener('mousedown', (e) => this._onSLMouseDown(e));
        document.addEventListener('mousemove', (e) => this._onSLMouseMove(e));
        document.addEventListener('mouseup', () => this._onSLMouseUp());
        
        // Sliders
        this.container.querySelector('.cp-hue-slider').addEventListener('input', (e) => {
            this.hue = parseInt(e.target.value);
            this._updateFromHSL();
        });
        this.container.querySelector('.cp-sat-slider').addEventListener('input', (e) => {
            this.saturation = parseInt(e.target.value);
            this._updateFromHSL();
        });
        this.container.querySelector('.cp-light-slider').addEventListener('input', (e) => {
            this.lightness = parseInt(e.target.value);
            this._updateFromHSL();
        });
        
        // Hex Input
        this.container.querySelector('.cp-hex-input').addEventListener('change', (e) => {
            const hex = e.target.value;
            if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                this._parseColor(hex);
                this._updateDisplay();
                this.onChange(this.currentColor);
            }
        });
        
        // Pipette Button
        this.container.querySelector('.cp-pipette-btn').addEventListener('click', () => {
            this.startPipette();
        });
        
        // Quick Colors
        this.container.querySelectorAll('.cp-quick-color').forEach(btn => {
            btn.addEventListener('click', () => {
                const color = btn.dataset.color;
                this._parseColor(color);
                this._updateDisplay();
                this.onChange(this.currentColor);
            });
        });
    }
    
    _onHueMouseDown(e) {
        const rect = this.hueCanvas.getBoundingClientRect();
        const cx = rect.width / 2;
        const cy = rect.height / 2;
        const x = e.clientX - rect.left - cx;
        const y = e.clientY - rect.top - cy;
        const dist = Math.sqrt(x * x + y * y);
        
        // Check if click is on the ring (between inner and outer radius)
        if (dist > 60 && dist < 100) {
            this.isDraggingHue = true;
            this._updateHueFromMouse(e);
        }
    }
    
    _onHueMouseMove(e) {
        if (this.isDraggingHue) {
            this._updateHueFromMouse(e);
        }
    }
    
    _onHueMouseUp() {
        this.isDraggingHue = false;
    }
    
    _updateHueFromMouse(e) {
        const rect = this.hueCanvas.getBoundingClientRect();
        const cx = rect.width / 2;
        const cy = rect.height / 2;
        const x = e.clientX - rect.left - cx;
        const y = e.clientY - rect.top - cy;
        
        let angle = Math.atan2(y, x) * 180 / Math.PI;
        angle = (angle + 360) % 360;
        
        this.hue = Math.round(angle);
        this._updateFromHSL();
    }
    
    _onSLMouseDown(e) {
        this.isDraggingSL = true;
        this._updateSLFromMouse(e);
    }
    
    _onSLMouseMove(e) {
        if (this.isDraggingSL) {
            this._updateSLFromMouse(e);
        }
    }
    
    _onSLMouseUp() {
        this.isDraggingSL = false;
    }
    
    _updateSLFromMouse(e) {
        const rect = this.slCanvas.getBoundingClientRect();
        let x = e.clientX - rect.left;
        let y = e.clientY - rect.top;
        
        // Clamp to bounds
        x = Math.max(0, Math.min(rect.width, x));
        y = Math.max(0, Math.min(rect.height, y));
        
        this.saturation = Math.round((x / rect.width) * 100);
        this.lightness = Math.round(100 - (y / rect.height) * 100);
        
        this._updateFromHSL();
    }
    
    // =========================================
    // COLOR CONVERSION
    // =========================================
    
    _parseColor(hex) {
        // Convert hex to HSL
        const r = parseInt(hex.slice(1, 3), 16) / 255;
        const g = parseInt(hex.slice(3, 5), 16) / 255;
        const b = parseInt(hex.slice(5, 7), 16) / 255;
        
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        const l = (max + min) / 2;
        
        let h, s;
        
        if (max === min) {
            h = s = 0;
        } else {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            
            switch (max) {
                case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
                case g: h = ((b - r) / d + 2) / 6; break;
                case b: h = ((r - g) / d + 4) / 6; break;
            }
        }
        
        this.hue = Math.round(h * 360);
        this.saturation = Math.round(s * 100);
        this.lightness = Math.round(l * 100);
        this.currentColor = hex;
    }
    
    _hslToHex(h, s, l) {
        s /= 100;
        l /= 100;
        
        const c = (1 - Math.abs(2 * l - 1)) * s;
        const x = c * (1 - Math.abs((h / 60) % 2 - 1));
        const m = l - c / 2;
        
        let r, g, b;
        
        if (h < 60) { r = c; g = x; b = 0; }
        else if (h < 120) { r = x; g = c; b = 0; }
        else if (h < 180) { r = 0; g = c; b = x; }
        else if (h < 240) { r = 0; g = x; b = c; }
        else if (h < 300) { r = x; g = 0; b = c; }
        else { r = c; g = 0; b = x; }
        
        r = Math.round((r + m) * 255);
        g = Math.round((g + m) * 255);
        b = Math.round((b + m) * 255);
        
        return '#' + [r, g, b].map(x => x.toString(16).padStart(2, '0')).join('').toUpperCase();
    }
    
    _updateFromHSL() {
        this.currentColor = this._hslToHex(this.hue, this.saturation, this.lightness);
        this._drawSLSquare();
        this._updateDisplay();
        this.onChange(this.currentColor);
    }
    
    // =========================================
    // DISPLAY UPDATE
    // =========================================
    
    _updateDisplay() {
        // Update preview
        this.container.querySelector('.cp-preview').style.background = this.currentColor;
        
        // Update hex input
        this.container.querySelector('.cp-hex-input').value = this.currentColor;
        
        // Update sliders
        this.container.querySelector('.cp-hue-slider').value = this.hue;
        this.container.querySelector('.cp-sat-slider').value = this.saturation;
        this.container.querySelector('.cp-light-slider').value = this.lightness;
        
        // Update slider values
        this.container.querySelector('.cp-hue-value').textContent = this.hue + '°';
        this.container.querySelector('.cp-sat-value').textContent = this.saturation + '%';
        this.container.querySelector('.cp-light-value').textContent = this.lightness + '%';
        
        // Update hue pointer position
        const huePointer = this.container.querySelector('.cp-hue-pointer');
        const angle = this.hue * Math.PI / 180;
        const radius = 82;
        const cx = 100, cy = 100;
        huePointer.style.left = (cx + Math.cos(angle) * radius - 6) + 'px';
        huePointer.style.top = (cy + Math.sin(angle) * radius - 6) + 'px';
        
        // Update SL pointer position
        const slPointer = this.container.querySelector('.cp-sl-pointer');
        slPointer.style.left = (this.saturation - 5) + '%';
        slPointer.style.top = (100 - this.lightness - 5) + '%';
    }
    
    _renderRecentColors() {
        const container = this.container.querySelector('.cp-recent-colors');
        container.innerHTML = this.recentColors.map(color => `
            <button class="cp-recent-color" data-color="${color}" style="background:${color}" title="${color}"></button>
        `).join('');
        
        container.querySelectorAll('.cp-recent-color').forEach(btn => {
            btn.addEventListener('click', () => {
                const color = btn.dataset.color;
                this._parseColor(color);
                this._updateDisplay();
                this.onChange(this.currentColor);
            });
        });
    }
}

// =========================================
// CSS STYLES (injected)
// =========================================
const colorPickerStyles = `
.sgit-colorpicker {
    background: #252525;
    border-radius: 12px;
    padding: 15px;
    width: 240px;
    font-family: 'Segoe UI', sans-serif;
    color: #fff;
}

.cp-main {
    display: flex;
    justify-content: center;
    margin-bottom: 15px;
}

.cp-hue-container {
    position: relative;
    width: 200px;
    height: 200px;
}

.cp-hue-ring {
    cursor: crosshair;
}

.cp-hue-pointer {
    position: absolute;
    width: 12px;
    height: 12px;
    border: 2px solid white;
    border-radius: 50%;
    box-shadow: 0 0 3px rgba(0,0,0,0.5);
    pointer-events: none;
}

.cp-sl-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100px;
    height: 100px;
    border-radius: 4px;
    overflow: hidden;
}

.cp-sl-square {
    cursor: crosshair;
    border-radius: 4px;
}

.cp-sl-pointer {
    position: absolute;
    width: 10px;
    height: 10px;
    border: 2px solid white;
    border-radius: 50%;
    box-shadow: 0 0 3px rgba(0,0,0,0.5);
    pointer-events: none;
    transform: translate(-50%, -50%);
}

.cp-controls {
    border-top: 1px solid #333;
    padding-top: 12px;
}

.cp-preview-row {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}

.cp-preview {
    flex: 1;
    height: 36px;
    border-radius: 6px;
    border: 2px solid #444;
}

.cp-pipette-btn {
    width: 36px;
    height: 36px;
    background: #333;
    border: 2px solid #444;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.2s;
}

.cp-pipette-btn:hover {
    background: #43D240;
    border-color: #43D240;
}

.cp-input-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.cp-input-row label {
    width: 30px;
    font-size: 11px;
    color: #888;
}

.cp-hex-input {
    flex: 1;
    background: #333;
    border: 1px solid #444;
    border-radius: 4px;
    padding: 6px 10px;
    color: #fff;
    font-family: monospace;
    font-size: 13px;
}

.cp-slider-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.cp-slider-row label {
    width: 15px;
    font-size: 11px;
    color: #888;
}

.cp-slider {
    flex: 1;
    height: 6px;
    -webkit-appearance: none;
    background: #333;
    border-radius: 3px;
    outline: none;
}

.cp-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 14px;
    height: 14px;
    background: #43D240;
    border-radius: 50%;
    cursor: pointer;
}

.cp-value {
    width: 35px;
    font-size: 11px;
    color: #888;
    text-align: right;
}

.cp-recent, .cp-quick {
    border-top: 1px solid #333;
    padding-top: 10px;
    margin-top: 10px;
}

.cp-recent label, .cp-quick label {
    display: block;
    font-size: 11px;
    color: #888;
    margin-bottom: 8px;
}

.cp-recent-colors, .cp-quick-colors {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.cp-recent-color, .cp-quick-color {
    width: 24px;
    height: 24px;
    border: 2px solid #444;
    border-radius: 4px;
    cursor: pointer;
    transition: transform 0.1s;
}

.cp-recent-color:hover, .cp-quick-color:hover {
    transform: scale(1.15);
    border-color: #fff;
}
`;

// Inject styles
if (!document.getElementById('sgit-colorpicker-styles')) {
    const styleSheet = document.createElement('style');
    styleSheet.id = 'sgit-colorpicker-styles';
    styleSheet.textContent = colorPickerStyles;
    document.head.appendChild(styleSheet);
}

console.log('🎨 sgiT ColorPicker loaded!');
