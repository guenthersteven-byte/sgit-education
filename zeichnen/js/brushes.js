/**
 * sgiT Education - Erweiterte Brushes für Zeichnen-Modul
 * 
 * Custom Fabric.js Brushes:
 * - Airbrush (weiche Kanten, Spray-Effekt)
 * - Marker (halbtransparent, breite Striche)
 * - Kreide (texturiert, raue Kanten)
 * - Neon (leuchtend, Glow-Effekt)
 * - Aquarell (verlaufend, wässrig)
 * 
 * @version 1.0
 * @date 11.12.2025
 */

// =====================================================
// AIRBRUSH - Weiche Kanten mit Spray-Effekt
// =====================================================
fabric.AirbrushBrush = fabric.util.createClass(fabric.BaseBrush, {
    
    width: 30,
    density: 20,
    dotWidthVariance: 5,
    randomOpacity: true,
    
    initialize: function(canvas) {
        this.canvas = canvas;
        this.points = [];
    },
    
    onMouseDown: function(pointer) {
        this.points = [];
        this._addPoint(pointer);
    },
    
    onMouseMove: function(pointer) {
        this._addPoint(pointer);
        this._render();
    },
    
    onMouseUp: function() {
        this._finalizeAndAddPath();
    },
    
    _addPoint: function(pointer) {
        this.points.push({ x: pointer.x, y: pointer.y });
    },
    
    _render: function() {
        const ctx = this.canvas.contextTop;
        const point = this.points[this.points.length - 1];
        
        ctx.fillStyle = this.color;
        
        for (let i = 0; i < this.density; i++) {
            const radius = Math.random() * this.width;
            const angle = Math.random() * Math.PI * 2;
            const x = point.x + Math.cos(angle) * radius;
            const y = point.y + Math.sin(angle) * radius;
            const dotSize = Math.random() * this.dotWidthVariance + 1;
            
            ctx.globalAlpha = this.randomOpacity ? Math.random() * 0.3 + 0.1 : 0.3;
            ctx.beginPath();
            ctx.arc(x, y, dotSize, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.globalAlpha = 1;
    },
    
    _finalizeAndAddPath: function() {
        const ctx = this.canvas.contextTop;
        const pathData = ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
        
        // Als Bild zum Canvas hinzufügen
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = this.canvas.width;
        tempCanvas.height = this.canvas.height;
        tempCanvas.getContext('2d').putImageData(pathData, 0, 0);
        
        fabric.Image.fromURL(tempCanvas.toDataURL(), (img) => {
            img.selectable = false;
            img.evented = false;
            this.canvas.add(img);
            this.canvas.clearContext(this.canvas.contextTop);
            this.canvas.renderAll();
        });
    }
});

// =====================================================
// MARKER - Halbtransparent, breite Striche
// =====================================================
fabric.MarkerBrush = fabric.util.createClass(fabric.PencilBrush, {
    
    type: 'MarkerBrush',
    opacity: 0.6,
    
    initialize: function(canvas) {
        this.callSuper('initialize', canvas);
        this.width = 20;
    },
    
    _render: function() {
        const ctx = this.canvas.contextTop;
        const points = this._points;
        
        if (points.length < 2) return;
        
        ctx.save();
        ctx.globalAlpha = this.opacity;
        ctx.lineCap = 'square';
        ctx.lineJoin = 'miter';
        ctx.strokeStyle = this.color;
        ctx.lineWidth = this.width;
        
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        
        for (let i = 1; i < points.length; i++) {
            ctx.lineTo(points[i].x, points[i].y);
        }
        
        ctx.stroke();
        ctx.restore();
    },
    
    convertPointsToSVGPath: function(points) {
        const path = this.callSuper('convertPointsToSVGPath', points);
        return path;
    },
    
    createPath: function(pathData) {
        const path = this.callSuper('createPath', pathData);
        path.set({
            opacity: this.opacity,
            strokeLineCap: 'square',
            strokeLineJoin: 'miter'
        });
        return path;
    }
});

// =====================================================
// KREIDE - Texturiert, raue Kanten
// =====================================================
fabric.ChalkBrush = fabric.util.createClass(fabric.PencilBrush, {
    
    type: 'ChalkBrush',
    roughness: 3,
    
    initialize: function(canvas) {
        this.callSuper('initialize', canvas);
        this.width = 8;
    },
    
    onMouseMove: function(pointer, options) {
        if (!this._isDown) return;
        
        const point = this.addPoint(pointer);
        const ctx = this.canvas.contextTop;
        
        ctx.fillStyle = this.color;
        
        // Kreide-Effekt: Viele kleine Punkte mit Variation
        for (let i = 0; i < 8; i++) {
            const offsetX = (Math.random() - 0.5) * this.width * this.roughness;
            const offsetY = (Math.random() - 0.5) * this.width * this.roughness;
            const size = Math.random() * 3 + 1;
            
            ctx.globalAlpha = Math.random() * 0.5 + 0.3;
            ctx.beginPath();
            ctx.arc(point.x + offsetX, point.y + offsetY, size, 0, Math.PI * 2);
            ctx.fill();
        }
        ctx.globalAlpha = 1;
    }
});

// =====================================================
// NEON - Leuchtend mit Glow-Effekt
// =====================================================
fabric.NeonBrush = fabric.util.createClass(fabric.PencilBrush, {
    
    type: 'NeonBrush',
    glowSize: 15,
    
    initialize: function(canvas) {
        this.callSuper('initialize', canvas);
        this.width = 4;
    },
    
    _render: function() {
        const ctx = this.canvas.contextTop;
        const points = this._points;
        
        if (points.length < 2) return;
        
        // Äußerer Glow
        ctx.save();
        ctx.shadowColor = this.color;
        ctx.shadowBlur = this.glowSize;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = this.color;
        ctx.lineWidth = this.width;
        ctx.globalAlpha = 0.8;
        
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        for (let i = 1; i < points.length; i++) {
            ctx.lineTo(points[i].x, points[i].y);
        }
        ctx.stroke();
        
        // Innerer heller Kern
        ctx.shadowBlur = 0;
        ctx.strokeStyle = '#FFFFFF';
        ctx.lineWidth = this.width / 3;
        ctx.globalAlpha = 0.9;
        ctx.stroke();
        
        ctx.restore();
    },
    
    createPath: function(pathData) {
        const path = this.callSuper('createPath', pathData);
        path.set({
            shadow: new fabric.Shadow({
                color: this.color,
                blur: this.glowSize,
                offsetX: 0,
                offsetY: 0
            })
        });
        return path;
    }
});

// =====================================================
// AQUARELL - Verlaufend, wässrig
// =====================================================
fabric.WatercolorBrush = fabric.util.createClass(fabric.BaseBrush, {
    
    width: 40,
    wetness: 0.7,
    
    initialize: function(canvas) {
        this.canvas = canvas;
        this.points = [];
    },
    
    onMouseDown: function(pointer) {
        this.points = [pointer];
    },
    
    onMouseMove: function(pointer) {
        this.points.push(pointer);
        this._render();
    },
    
    onMouseUp: function() {
        this._finalizeAndAddPath();
    },
    
    _render: function() {
        const ctx = this.canvas.contextTop;
        const points = this.points;
        
        if (points.length < 2) return;
        
        const lastPoint = points[points.length - 1];
        const prevPoint = points[points.length - 2];
        
        // Aquarell-Effekt: Mehrere überlappende Kreise
        const dist = Math.sqrt(
            Math.pow(lastPoint.x - prevPoint.x, 2) + 
            Math.pow(lastPoint.y - prevPoint.y, 2)
        );
        const steps = Math.max(Math.floor(dist / 5), 1);
        
        for (let i = 0; i <= steps; i++) {
            const t = i / steps;
            const x = prevPoint.x + (lastPoint.x - prevPoint.x) * t;
            const y = prevPoint.y + (lastPoint.y - prevPoint.y) * t;
            
            // Mehrere überlappende Kreise für Aquarell-Effekt
            for (let j = 0; j < 3; j++) {
                const offsetX = (Math.random() - 0.5) * this.width * 0.3;
                const offsetY = (Math.random() - 0.5) * this.width * 0.3;
                const radius = this.width / 2 + Math.random() * 10;
                
                const gradient = ctx.createRadialGradient(
                    x + offsetX, y + offsetY, 0,
                    x + offsetX, y + offsetY, radius
                );
                gradient.addColorStop(0, this._hexToRgba(this.color, 0.15));
                gradient.addColorStop(0.5, this._hexToRgba(this.color, 0.08));
                gradient.addColorStop(1, this._hexToRgba(this.color, 0));
                
                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(x + offsetX, y + offsetY, radius, 0, Math.PI * 2);
                ctx.fill();
            }
        }
    },
    
    _hexToRgba: function(hex, alpha) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    },
    
    _finalizeAndAddPath: function() {
        const ctx = this.canvas.contextTop;
        const pathData = ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
        
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = this.canvas.width;
        tempCanvas.height = this.canvas.height;
        tempCanvas.getContext('2d').putImageData(pathData, 0, 0);
        
        fabric.Image.fromURL(tempCanvas.toDataURL(), (img) => {
            img.selectable = false;
            img.evented = false;
            this.canvas.add(img);
            this.canvas.clearContext(this.canvas.contextTop);
            this.canvas.renderAll();
        });
    }
});

// =====================================================
// HELPER: Brush Factory
// =====================================================
const BrushFactory = {
    
    brushes: {
        'pencil': { name: 'Stift', icon: '✏️', class: 'PencilBrush' },
        'brush': { name: 'Pinsel', icon: '🖌️', class: 'CircleBrush' },
        'marker': { name: 'Marker', icon: '🖍️', class: 'MarkerBrush' },
        'chalk': { name: 'Kreide', icon: '🪨', class: 'ChalkBrush' },
        'neon': { name: 'Neon', icon: '✨', class: 'NeonBrush' },
        'watercolor': { name: 'Aquarell', icon: '💧', class: 'WatercolorBrush' },
        'airbrush': { name: 'Airbrush', icon: '💨', class: 'AirbrushBrush' },
        'spray': { name: 'Spray', icon: '🎨', class: 'SprayBrush' }
    },
    
    create: function(canvas, brushType) {
        const brushInfo = this.brushes[brushType];
        if (!brushInfo) return new fabric.PencilBrush(canvas);
        
        switch (brushInfo.class) {
            case 'PencilBrush': return new fabric.PencilBrush(canvas);
            case 'CircleBrush': return new fabric.CircleBrush(canvas);
            case 'SprayBrush': return new fabric.SprayBrush(canvas);
            case 'MarkerBrush': return new fabric.MarkerBrush(canvas);
            case 'ChalkBrush': return new fabric.ChalkBrush(canvas);
            case 'NeonBrush': return new fabric.NeonBrush(canvas);
            case 'WatercolorBrush': return new fabric.WatercolorBrush(canvas);
            case 'AirbrushBrush': return new fabric.AirbrushBrush(canvas);
            default: return new fabric.PencilBrush(canvas);
        }
    },
    
    getBrushList: function() {
        return Object.entries(this.brushes).map(([key, val]) => ({
            id: key,
            ...val
        }));
    }
};

console.log('🖌️ sgiT Extended Brushes loaded!');
