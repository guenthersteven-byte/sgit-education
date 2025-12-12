/**
 * sgiT Education - HSL Color Picker
 * 
 * Features:
 * - Farbkreis (Hue Ring)
 * - Sättigung/Helligkeit Quadrat
 * - Pipette-Tool (Eyedropper)
 * - Zuletzt verwendete Farben
 * - Hex/RGB Input
 * 
 * @version 1.0
 * @date 11.12.2025
 */

class SGITColorPicker {
    
    constructor(options = {}) {
        this.container = options.container || document.body;
        this.onChange = options.onChange || (() => {});
        this.currentColor = options.initialColor || '#43D240';
        this.recentColors = [];
        this.maxRecent = 8;
        this.pipetteActive = false;
        
        this.hue = 0;
        this.saturation = 100;
        this.lightness = 50;
        
        this._parseColor(this.currentColor);
        this._createUI();
        this._bindEvents();
    }
    
    // =====================================================
    // UI CREATION
    // =====================================================
    _createUI() {
        this.element = document.createElement('div');
        this.element.className = 'sgit-colorpicker';
        this.element.innerHTML = `
            <div class="cp-main">
                <!-- Hue Ring -->
                <div class="cp-hue-container">
                    <canvas class="cp-hue-ring" width="200" height="200"></canvas>
                    <canvas class="cp-sat-square" width="120" height="120"></canvas>
                    <div class="cp-hue-cursor"></div>
                    <div class="cp-sat-cursor"></div>
                </div>
                
                <!-- Preview -->
                <div class="cp-preview-row">
                    <div class="cp-preview" title="Aktuelle Farbe">
                        <div class="cp-preview-new"></div>
                        <div class="cp-preview-old"></div>
                    </div>
                    <div class="cp-values">
                        <input type="text" class="cp-hex-input" maxlength="7" placeholder="#RRGGBB">
                        <button class="cp-pipette" title="Pipette (Farbe aufnehmen)">💉</button>
                    </div>
                </div>
                
                <!-- Sliders -->
                <div class="cp-sliders">
                    <div class="cp-slider-row">
                        <label>H</label>
                        <input type="range" class="cp-slider cp-hue-slider" min="0" max="360" value="0">
                        <span class="cp-slider-val">0°</span>
                    </div>
                    <div class="cp-slider-row">
                        <label>S</label>
                        <input type="range" class="cp-slider cp-sat-slider" min="0" max="100" value="100">
                        <span class="cp-slider-val">100%</span>
                    </div>
                    <div class="cp-slider-row">
                        <label>L</label>
                        <input type="range" class="cp-slider cp-light-slider" min="0" max="100" value="50">
                        <span class="cp-slider-val">50%</span>
                    </div>
                </div>
                
                <!-- Recent Colors -->
                <div class="cp-recent">
                    <span class="cp-recent-label">Zuletzt:</span>
                    <div class="cp-recent-colors"></div>
                </div>
                
                <!-- Quick Colors -->
                <div class="cp-quick">
                    <div class="cp-quick-color" style="background:#000000" data-color="#000000"></div>
                    <div class="cp-quick-color" style="background:#FFFFFF" data-color="#FFFFFF"></div>
                    <div class="cp-quick-color" style="background:#FF0000" data-color="#FF0000"></div>
                    <div class="cp-quick-color" style="background:#00FF00" data-color="#00FF00"></div>
                    <div class="cp-quick-color" style="background:#0000FF" data-color="#0000FF"></div>
                    <div class="cp-quick-color" style="background:#FFFF00" data-color="#FFFF00"></div>
                    <div class="cp-quick-color" style="background:#FF00FF" data-color="#FF00FF"></div>
                    <div class="cp-quick-color" style="background:#00FFFF" data-color="#00FFFF"></div>
                    <div class="cp-quick-color sgit" style="background:#1A3503" data-color="#1A3503"></div>
                    <div class="cp-quick-color sgit" style="background:#43D240" data-color="#43D240"></div>
                    <div class="cp-quick-color sgit" style="background:#E86F2C" data-color="#E86F2C"></div>
                </div>
            </div>
        `;
        
        this._addStyles();
        this.container.appendChild(this.element);
        
        // Canvas Setup
        this.hueCanvas = this.element.querySelector('.cp-hue-ring');
        this.satCanvas = this.element.querySelector('.cp-sat-square');
        this.hueCtx = this.hueCanvas.getContext('2d');
        this.satCtx = this.satCanvas.getContext('2d');
        
        this._drawHueRing();
        this._drawSatSquare();
        this._updateUI();
    }
    
    _addStyles() {
        if (document.getElementById('sgit-colorpicker-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'sgit-colorpicker-styles';
        style.textContent = `
            .sgit-colorpicker {
                position: relative;
                background: #2a2a2a;
                border-radius: 0 0 10px 10px;
                padding: 0;
                z-index: 10000;
                font-family: 'Segoe UI', sans-serif;
                min-width: 280px;
            }
            .sgit-colorpicker.visible { display: block; }
            
            .cp-header {
                background: #1A3503;
                padding: 12px 15px;
                border-radius: 14px 14px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .cp-title { color: #43D240; font-weight: bold; }
            .cp-close {
                background: none;
                border: none;
                color: #888;
                font-size: 1.2em;
                cursor: pointer;
                padding: 0 5px;
            }
            .cp-close:hover { color: #fff; }
            
            .cp-main { padding: 15px; }
            
            .cp-hue-container {
                position: relative;
                width: 200px;
                height: 200px;
                margin: 0 auto 15px;
            }
            .cp-hue-ring, .cp-sat-square {
                position: absolute;
                cursor: crosshair;
            }
            .cp-hue-ring {
                top: 0; left: 0;
            }
            .cp-sat-square {
                top: 40px;
                left: 40px;
                border-radius: 4px;
            }
            .cp-hue-cursor {
                position: absolute;
                width: 14px;
                height: 14px;
                border: 3px solid white;
                border-radius: 50%;
                box-shadow: 0 0 5px rgba(0,0,0,0.5);
                pointer-events: none;
                transform: translate(-50%, -50%);
            }
            .cp-sat-cursor {
                position: absolute;
                width: 16px;
                height: 16px;
                border: 3px solid white;
                border-radius: 50%;
                box-shadow: 0 0 5px rgba(0,0,0,0.5), inset 0 0 3px rgba(0,0,0,0.3);
                pointer-events: none;
                transform: translate(-50%, -50%);
            }
            
            .cp-preview-row {
                display: flex;
                gap: 10px;
                align-items: center;
                margin-bottom: 12px;
            }
            .cp-preview {
                width: 60px;
                height: 40px;
                border-radius: 8px;
                overflow: hidden;
                border: 2px solid #444;
                display: flex;
            }
            .cp-preview-new, .cp-preview-old {
                flex: 1;
            }
            .cp-values {
                flex: 1;
                display: flex;
                gap: 8px;
            }
            .cp-hex-input {
                flex: 1;
                background: #333;
                border: 1px solid #555;
                color: #fff;
                padding: 8px 10px;
                border-radius: 6px;
                font-family: monospace;
                font-size: 1em;
            }
            .cp-hex-input:focus {
                outline: none;
                border-color: #43D240;
            }
            .cp-pipette {
                background: #333;
                border: 1px solid #555;
                border-radius: 6px;
                padding: 8px 12px;
                cursor: pointer;
                font-size: 1.1em;
            }
            .cp-pipette:hover { background: #444; }
            .cp-pipette.active { background: #43D240; }
            
            .cp-sliders { margin-bottom: 12px; }
            .cp-slider-row {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 6px;
            }
            .cp-slider-row label {
                color: #888;
                width: 15px;
                font-size: 0.85em;
            }
            .cp-slider {
                flex: 1;
                height: 8px;
                -webkit-appearance: none;
                background: #444;
                border-radius: 4px;
                cursor: pointer;
            }
            .cp-slider::-webkit-slider-thumb {
                -webkit-appearance: none;
                width: 16px;
                height: 16px;
                background: #43D240;
                border-radius: 50%;
                cursor: pointer;
            }
            .cp-hue-slider { background: linear-gradient(to right, #f00, #ff0, #0f0, #0ff, #00f, #f0f, #f00); }
            .cp-slider-val {
                color: #aaa;
                font-size: 0.8em;
                width: 40px;
                text-align: right;
            }
            
            .cp-recent {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 10px;
            }
            .cp-recent-label {
                color: #888;
                font-size: 0.85em;
            }
            .cp-recent-colors {
                display: flex;
                gap: 4px;
                flex: 1;
            }
            .cp-recent-color {
                width: 24px;
                height: 24px;
                border-radius: 4px;
                cursor: pointer;
                border: 2px solid #444;
            }
            .cp-recent-color:hover { border-color: #fff; transform: scale(1.1); }
            
            .cp-quick {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
            }
            .cp-quick-color {
                width: 22px;
                height: 22px;
                border-radius: 4px;
                cursor: pointer;
                border: 2px solid #444;
                transition: transform 0.1s;
            }
            .cp-quick-color:hover { transform: scale(1.15); border-color: #fff; }
            .cp-quick-color.sgit { border-color: #43D240; }
        `;
        document.head.appendChild(style);
    }
    
    // =====================================================
    // DRAWING
    // =====================================================
    _drawHueRing() {
        const ctx = this.hueCtx;
        const cx = 100, cy = 100;
        const outerR = 98, innerR = 75;
        
        ctx.clearRect(0, 0, 200, 200);
        
        for (let angle = 0; angle < 360; angle++) {
            const startAngle = (angle - 1) * Math.PI / 180;
            const endAngle = (angle + 1) * Math.PI / 180;
            
            ctx.beginPath();
            ctx.arc(cx, cy, outerR, startAngle, endAngle);
            ctx.arc(cx, cy, innerR, endAngle, startAngle, true);
            ctx.closePath();
            ctx.fillStyle = `hsl(${angle}, 100%, 50%)`;
            ctx.fill();
        }
        
        // Inner circle (transparent area)
        ctx.globalCompositeOperation = 'destination-out';
        ctx.beginPath();
        ctx.arc(cx, cy, innerR - 2, 0, Math.PI * 2);
        ctx.fill();
        ctx.globalCompositeOperation = 'source-over';
    }
    
    _drawSatSquare() {
        const ctx = this.satCtx;
        const size = 120;
        
        // Gradient für Sättigung (links->rechts: weiß->Farbe)
        const gradH = ctx.createLinearGradient(0, 0, size, 0);
        gradH.addColorStop(0, '#FFFFFF');
        gradH.addColorStop(1, `hsl(${this.hue}, 100%, 50%)`);
        ctx.fillStyle = gradH;
        ctx.fillRect(0, 0, size, size);
        
        // Gradient für Helligkeit (oben->unten: transparent->schwarz)
        const gradV = ctx.createLinearGradient(0, 0, 0, size);
        gradV.addColorStop(0, 'rgba(0,0,0,0)');
        gradV.addColorStop(1, 'rgba(0,0,0,1)');
        ctx.fillStyle = gradV;
        ctx.fillRect(0, 0, size, size);
    }
    
    // =====================================================
    // EVENTS
    // =====================================================
    _bindEvents() {
        // Hue ring click
        this.hueCanvas.addEventListener('mousedown', (e) => this._handleHueClick(e));
        this.hueCanvas.addEventListener('mousemove', (e) => {
            if (e.buttons === 1) this._handleHueClick(e);
        });
        
        // Saturation square click
        this.satCanvas.addEventListener('mousedown', (e) => this._handleSatClick(e));
        this.satCanvas.addEventListener('mousemove', (e) => {
            if (e.buttons === 1) this._handleSatClick(e);
        });
        
        // Sliders
        this.element.querySelector('.cp-hue-slider').addEventListener('input', (e) => {
            this.hue = parseInt(e.target.value);
            this._drawSatSquare();
            this._updateColor();
        });
        this.element.querySelector('.cp-sat-slider').addEventListener('input', (e) => {
            this.saturation = parseInt(e.target.value);
            this._updateColor();
        });
        this.element.querySelector('.cp-light-slider').addEventListener('input', (e) => {
            this.lightness = parseInt(e.target.value);
            this._updateColor();
        });
        
        // Hex input
        this.element.querySelector('.cp-hex-input').addEventListener('change', (e) => {
            const hex = e.target.value;
            if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                this._parseColor(hex);
                this._drawSatSquare();
                this._updateUI();
            }
        });
        
        // Quick colors
        this.element.querySelectorAll('.cp-quick-color').forEach(el => {
            el.addEventListener('click', () => {
                this._parseColor(el.dataset.color);
                this._drawSatSquare();
                this._updateUI();
                this._triggerChange();
            });
        });
        
        // Pipette
        this.element.querySelector('.cp-pipette').addEventListener('click', () => {
            this._activatePipette();
        });
    }
    
    _handleHueClick(e) {
        const rect = this.hueCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left - 100;
        const y = e.clientY - rect.top - 100;
        const dist = Math.sqrt(x * x + y * y);
        
        if (dist > 70 && dist < 100) {
            this.hue = Math.round(Math.atan2(y, x) * 180 / Math.PI + 180);
            this._drawSatSquare();
            this._updateColor();
        }
    }
    
    _handleSatClick(e) {
        const rect = this.satCanvas.getBoundingClientRect();
        const x = Math.max(0, Math.min(120, e.clientX - rect.left));
        const y = Math.max(0, Math.min(120, e.clientY - rect.top));
        
        this.saturation = Math.round(x / 120 * 100);
        this.lightness = Math.round(100 - y / 120 * 100);
        this._updateColor();
    }
    
    _activatePipette() {
        const btn = this.element.querySelector('.cp-pipette');
        btn.classList.add('active');
        
        if ('EyeDropper' in window) {
            const eyeDropper = new EyeDropper();
            eyeDropper.open().then(result => {
                this._parseColor(result.sRGBHex);
                this._drawSatSquare();
                this._updateUI();
                this._triggerChange();
            }).catch(() => {}).finally(() => {
                btn.classList.remove('active');
            });
        } else {
            alert('Pipette wird von diesem Browser nicht unterstützt.');
            btn.classList.remove('active');
        }
    }
    
    // =====================================================
    // COLOR CONVERSION
    // =====================================================
    _parseColor(hex) {
        const r = parseInt(hex.slice(1, 3), 16) / 255;
        const g = parseInt(hex.slice(3, 5), 16) / 255;
        const b = parseInt(hex.slice(5, 7), 16) / 255;
        
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        let h, s, l = (max + min) / 2;
        
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
    
    _hslToHex() {
        const h = this.hue / 360;
        const s = this.saturation / 100;
        const l = this.lightness / 100;
        
        let r, g, b;
        if (s === 0) {
            r = g = b = l;
        } else {
            const hue2rgb = (p, q, t) => {
                if (t < 0) t += 1;
                if (t > 1) t -= 1;
                if (t < 1/6) return p + (q - p) * 6 * t;
                if (t < 1/2) return q;
                if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
                return p;
            };
            const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            const p = 2 * l - q;
            r = hue2rgb(p, q, h + 1/3);
            g = hue2rgb(p, q, h);
            b = hue2rgb(p, q, h - 1/3);
        }
        
        const toHex = x => {
            const hex = Math.round(x * 255).toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        };
        
        return `#${toHex(r)}${toHex(g)}${toHex(b)}`.toUpperCase();
    }
    
    // =====================================================
    // UI UPDATE
    // =====================================================
    _updateColor() {
        this.currentColor = this._hslToHex();
        this._updateUI();
        this._triggerChange();
    }
    
    _updateUI() {
        // Preview
        this.element.querySelector('.cp-preview-new').style.background = this.currentColor;
        
        // Hex input
        this.element.querySelector('.cp-hex-input').value = this.currentColor;
        
        // Sliders
        this.element.querySelector('.cp-hue-slider').value = this.hue;
        this.element.querySelector('.cp-sat-slider').value = this.saturation;
        this.element.querySelector('.cp-light-slider').value = this.lightness;
        
        // Slider values
        const vals = this.element.querySelectorAll('.cp-slider-val');
        vals[0].textContent = this.hue + '°';
        vals[1].textContent = this.saturation + '%';
        vals[2].textContent = this.lightness + '%';
        
        // Cursors
        const hueAngle = (this.hue - 180) * Math.PI / 180;
        const hueCursor = this.element.querySelector('.cp-hue-cursor');
        hueCursor.style.left = (100 + Math.cos(hueAngle) * 86) + 'px';
        hueCursor.style.top = (100 + Math.sin(hueAngle) * 86) + 'px';
        hueCursor.style.background = `hsl(${this.hue}, 100%, 50%)`;
        
        const satCursor = this.element.querySelector('.cp-sat-cursor');
        satCursor.style.left = (40 + this.saturation / 100 * 120) + 'px';
        satCursor.style.top = (40 + (100 - this.lightness) / 100 * 120) + 'px';
        satCursor.style.background = this.currentColor;
    }
    
    _triggerChange() {
        this.onChange(this.currentColor);
    }
    
    _addToRecent(color) {
        if (!this.recentColors.includes(color)) {
            this.recentColors.unshift(color);
            if (this.recentColors.length > this.maxRecent) {
                this.recentColors.pop();
            }
            this._renderRecent();
        }
    }
    
    _renderRecent() {
        const container = this.element.querySelector('.cp-recent-colors');
        container.innerHTML = this.recentColors.map(c => 
            `<div class="cp-recent-color" style="background:${c}" data-color="${c}"></div>`
        ).join('');
        
        container.querySelectorAll('.cp-recent-color').forEach(el => {
            el.addEventListener('click', () => {
                this._parseColor(el.dataset.color);
                this._drawSatSquare();
                this._updateUI();
                this._triggerChange();
            });
        });
    }
    
    // =====================================================
    // PUBLIC API
    // =====================================================
    show(initialColor) {
        if (initialColor) {
            this.element.querySelector('.cp-preview-old').style.background = initialColor;
            this._parseColor(initialColor);
            this._drawSatSquare();
            this._updateUI();
        }
        this.element.classList.add('visible');
    }
    
    hide() {
        this._addToRecent(this.currentColor);
        this.element.classList.remove('visible');
    }
    
    getColor() {
        return this.currentColor;
    }
    
    setColor(hex) {
        this._parseColor(hex);
        this._drawSatSquare();
        this._updateUI();
    }
}

console.log('🎨 sgiT Color Picker loaded!');
