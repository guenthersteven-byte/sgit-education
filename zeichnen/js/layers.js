/**
 * sgiT Education - Ebenen-System (Layer Manager)
 * 
 * Features:
 * - Mehrere Ebenen erstellen/löschen
 * - Ebenen ein-/ausblenden
 * - Ebenen sperren
 * - Ebenen umbenennen
 * - Ebenen neu anordnen (Drag & Drop)
 * - Transparenz pro Ebene
 * - Aktive Ebene markieren
 * - Ebenen zusammenführen
 * 
 * @version 1.0
 * @date 11.12.2025
 */

class SGITLayerManager {
    
    constructor(canvas, options = {}) {
        this.canvas = canvas;
        this.container = options.container || null;
        this.onChange = options.onChange || (() => {});
        this.onLayerSelect = options.onLayerSelect || (() => {});
        
        this.layers = [];
        this.activeLayerId = null;
        this.nextLayerId = 1;
        this.maxLayers = options.maxLayers || 10;
        
        // Standard-Hintergrund-Ebene erstellen
        this._createBackgroundLayer();
        // Erste Zeichen-Ebene
        this.addLayer('Ebene 1');
        
        if (this.container) {
            this._createUI();
            this._bindEvents();
        }
    }
    
    // =====================================================
    // LAYER MANAGEMENT
    // =====================================================
    
    _createBackgroundLayer() {
        const bgLayer = {
            id: 0,
            name: 'Hintergrund',
            visible: true,
            locked: true,
            opacity: 1,
            objects: [],
            isBackground: true
        };
        this.layers.unshift(bgLayer);
    }
    
    addLayer(name = null) {
        if (this.layers.length >= this.maxLayers) {
            this._showToast('⚠️ Maximum ' + this.maxLayers + ' Ebenen erreicht!');
            return null;
        }
        
        const layer = {
            id: this.nextLayerId++,
            name: name || 'Ebene ' + this.nextLayerId,
            visible: true,
            locked: false,
            opacity: 1,
            objects: []
        };
        
        this.layers.push(layer);
        this.activeLayerId = layer.id;
        
        this._renderUI();
        this.onChange(this.layers);
        this._showToast('✅ ' + layer.name + ' erstellt');
        
        return layer;
    }
    
    removeLayer(layerId) {
        if (layerId === 0) {
            this._showToast('⚠️ Hintergrund kann nicht gelöscht werden!');
            return false;
        }
        
        const index = this.layers.findIndex(l => l.id === layerId);
        if (index === -1) return false;
        
        const layer = this.layers[index];
        
        // Objekte der Ebene vom Canvas entfernen
        layer.objects.forEach(obj => {
            this.canvas.remove(obj);
        });
        
        this.layers.splice(index, 1);
        
        // Neue aktive Ebene wählen
        if (this.activeLayerId === layerId) {
            const newActive = this.layers.find(l => !l.isBackground);
            this.activeLayerId = newActive ? newActive.id : null;
        }
        
        this._renderUI();
        this.canvas.renderAll();
        this.onChange(this.layers);
        this._showToast('🗑️ ' + layer.name + ' gelöscht');
        
        return true;
    }
    
    selectLayer(layerId) {
        const layer = this.layers.find(l => l.id === layerId);
        if (!layer || layer.locked) return;
        
        this.activeLayerId = layerId;
        this._renderUI();
        this.onLayerSelect(layer);
    }
    
    toggleVisibility(layerId) {
        const layer = this.layers.find(l => l.id === layerId);
        if (!layer) return;
        
        layer.visible = !layer.visible;
        
        // Objekte ein-/ausblenden
        layer.objects.forEach(obj => {
            obj.visible = layer.visible;
        });
        
        this._renderUI();
        this.canvas.renderAll();
        this.onChange(this.layers);
    }
    
    toggleLock(layerId) {
        if (layerId === 0) return; // Hintergrund immer gesperrt
        
        const layer = this.layers.find(l => l.id === layerId);
        if (!layer) return;
        
        layer.locked = !layer.locked;
        
        // Objekte sperren/entsperren
        layer.objects.forEach(obj => {
            obj.selectable = !layer.locked;
            obj.evented = !layer.locked;
        });
        
        this._renderUI();
        this.canvas.renderAll();
        this.onChange(this.layers);
    }
    
    setOpacity(layerId, opacity) {
        const layer = this.layers.find(l => l.id === layerId);
        if (!layer) return;
        
        layer.opacity = Math.max(0, Math.min(1, opacity));
        
        layer.objects.forEach(obj => {
            obj.opacity = layer.opacity;
        });
        
        this._renderUI();
        this.canvas.renderAll();
        this.onChange(this.layers);
    }
    
    renameLayer(layerId, newName) {
        if (layerId === 0) return; // Hintergrund nicht umbenennen
        
        const layer = this.layers.find(l => l.id === layerId);
        if (!layer) return;
        
        layer.name = newName.substring(0, 20); // Max 20 Zeichen
        this._renderUI();
        this.onChange(this.layers);
    }
    
    moveLayer(layerId, direction) {
        const index = this.layers.findIndex(l => l.id === layerId);
        if (index === -1 || layerId === 0) return;
        
        const newIndex = direction === 'up' ? index + 1 : index - 1;
        
        // Nicht unter Hintergrund oder über Maximum
        if (newIndex < 1 || newIndex >= this.layers.length) return;
        
        // Tauschen
        [this.layers[index], this.layers[newIndex]] = [this.layers[newIndex], this.layers[index]];
        
        this._reorderCanvasObjects();
        this._renderUI();
        this.onChange(this.layers);
    }
    
    mergeLayers(sourceId, targetId) {
        const source = this.layers.find(l => l.id === sourceId);
        const target = this.layers.find(l => l.id === targetId);
        
        if (!source || !target || source.isBackground || target.locked) return;
        
        // Objekte zur Ziel-Ebene verschieben
        source.objects.forEach(obj => {
            obj._layerId = targetId;
            target.objects.push(obj);
        });
        
        // Quell-Ebene entfernen
        this.removeLayer(sourceId);
        this._showToast('🔗 Ebenen zusammengeführt');
    }
    
    duplicateLayer(layerId) {
        const source = this.layers.find(l => l.id === layerId);
        if (!source || source.isBackground) return;
        
        const newLayer = this.addLayer(source.name + ' (Kopie)');
        if (!newLayer) return;
        
        // Objekte kopieren
        source.objects.forEach(obj => {
            obj.clone((cloned) => {
                cloned._layerId = newLayer.id;
                cloned.set({
                    left: obj.left + 20,
                    top: obj.top + 20
                });
                newLayer.objects.push(cloned);
                this.canvas.add(cloned);
            });
        });
        
        this.canvas.renderAll();
        this._showToast('📋 Ebene dupliziert');
    }
    
    // =====================================================
    // OBJECT-LAYER BINDING
    // =====================================================
    
    addObjectToActiveLayer(obj) {
        if (!this.activeLayerId) {
            // Automatisch erste nicht-gesperrte Ebene wählen
            const layer = this.layers.find(l => !l.locked && !l.isBackground);
            if (layer) this.activeLayerId = layer.id;
            else return false;
        }
        
        const layer = this.layers.find(l => l.id === this.activeLayerId);
        if (!layer || layer.locked) return false;
        
        obj._layerId = layer.id;
        obj.opacity = layer.opacity;
        layer.objects.push(obj);
        
        return true;
    }
    
    removeObjectFromLayer(obj) {
        const layerId = obj._layerId;
        if (layerId === undefined) return;
        
        const layer = this.layers.find(l => l.id === layerId);
        if (!layer) return;
        
        const index = layer.objects.indexOf(obj);
        if (index > -1) {
            layer.objects.splice(index, 1);
        }
    }
    
    getActiveLayer() {
        return this.layers.find(l => l.id === this.activeLayerId);
    }
    
    isActiveLayerDrawable() {
        const layer = this.getActiveLayer();
        return layer && !layer.locked && layer.visible;
    }
    
    _reorderCanvasObjects() {
        // Canvas-Objekte nach Ebenen-Reihenfolge sortieren
        const allObjects = [];
        this.layers.forEach(layer => {
            layer.objects.forEach(obj => allObjects.push(obj));
        });
        
        // Canvas leeren und neu hinzufügen
        this.canvas.getObjects().forEach(obj => {
            if (obj._layerId !== undefined) {
                this.canvas.remove(obj);
            }
        });
        
        allObjects.forEach(obj => this.canvas.add(obj));
        this.canvas.renderAll();
    }
    
    // =====================================================
    // UI
    // =====================================================
    
    _createUI() {
        this.element = document.createElement('div');
        this.element.className = 'sgit-layers';
        this.element.innerHTML = `
            <div class="layers-header">
                <span class="layers-title">📚 Ebenen</span>
                <div class="layers-actions">
                    <button class="layer-btn add" onclick="layerManager.addLayer()" title="Neue Ebene">➕</button>
                </div>
            </div>
            <div class="layers-list"></div>
            <div class="layers-footer">
                <span class="layer-count"></span>
            </div>
        `;
        
        this._addStyles();
        this.container.appendChild(this.element);
        this._renderUI();
    }
    
    _addStyles() {
        if (document.getElementById('sgit-layers-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'sgit-layers-styles';
        style.textContent = `
            .sgit-layers {
                background: #252525;
                border-radius: 10px;
                overflow: hidden;
                font-family: 'Segoe UI', sans-serif;
                width: 100%;
            }
            
            .layers-header {
                background: #1A3503;
                padding: 10px 12px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .layers-title {
                color: #43D240;
                font-weight: bold;
                font-size: 0.95em;
            }
            .layers-actions {
                display: flex;
                gap: 5px;
            }
            .layer-btn {
                background: #333;
                border: none;
                color: white;
                width: 28px;
                height: 28px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 0.9em;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .layer-btn:hover { background: #444; }
            .layer-btn.add { background: #43D240; color: #1A3503; }
            
            .layers-list {
                max-height: 300px;
                overflow-y: auto;
            }
            
            .layer-item {
                display: flex;
                align-items: center;
                padding: 8px 10px;
                border-bottom: 1px solid #333;
                cursor: pointer;
                transition: background 0.2s;
                gap: 8px;
            }
            .layer-item:hover { background: #2a2a2a; }
            .layer-item.active {
                background: rgba(67, 210, 64, 0.15);
                border-left: 3px solid #43D240;
            }
            .layer-item.locked { opacity: 0.6; }
            .layer-item.hidden { opacity: 0.4; }
            
            .layer-visibility {
                font-size: 1.1em;
                cursor: pointer;
                padding: 2px;
            }
            .layer-visibility:hover { transform: scale(1.2); }
            
            .layer-thumb {
                width: 32px;
                height: 32px;
                background: #444;
                border-radius: 4px;
                border: 1px solid #555;
                overflow: hidden;
            }
            .layer-thumb canvas {
                width: 100%;
                height: 100%;
            }
            
            .layer-info {
                flex: 1;
                min-width: 0;
            }
            .layer-name {
                color: white;
                font-size: 0.85em;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .layer-name input {
                background: #333;
                border: 1px solid #43D240;
                color: white;
                padding: 2px 5px;
                border-radius: 3px;
                width: 100%;
                font-size: 0.85em;
            }
            .layer-meta {
                color: #666;
                font-size: 0.7em;
            }
            
            .layer-controls {
                display: flex;
                gap: 3px;
            }
            .layer-ctrl {
                background: none;
                border: none;
                color: #888;
                cursor: pointer;
                padding: 3px;
                font-size: 0.85em;
            }
            .layer-ctrl:hover { color: white; }
            .layer-ctrl.locked { color: #f39c12; }
            
            .layer-opacity {
                width: 50px;
                height: 4px;
                -webkit-appearance: none;
                background: #444;
                border-radius: 2px;
                cursor: pointer;
            }
            .layer-opacity::-webkit-slider-thumb {
                -webkit-appearance: none;
                width: 10px;
                height: 10px;
                background: #43D240;
                border-radius: 50%;
            }
            
            .layers-footer {
                padding: 8px 12px;
                background: #1a1a1a;
                border-top: 1px solid #333;
            }
            .layer-count {
                color: #666;
                font-size: 0.75em;
            }
            
            /* Context Menu */
            .layer-context-menu {
                position: fixed;
                background: #2a2a2a;
                border: 1px solid #43D240;
                border-radius: 8px;
                padding: 5px 0;
                z-index: 10000;
                min-width: 150px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.5);
            }
            .layer-context-menu button {
                display: block;
                width: 100%;
                padding: 8px 15px;
                background: none;
                border: none;
                color: white;
                text-align: left;
                cursor: pointer;
                font-size: 0.85em;
            }
            .layer-context-menu button:hover {
                background: rgba(67, 210, 64, 0.2);
            }
            .layer-context-menu button.danger { color: #e74c3c; }
            .layer-context-menu hr {
                border: none;
                border-top: 1px solid #444;
                margin: 5px 0;
            }
        `;
        document.head.appendChild(style);
    }
    
    _renderUI() {
        if (!this.element) return;
        
        const list = this.element.querySelector('.layers-list');
        
        // Ebenen in umgekehrter Reihenfolge anzeigen (oberste zuerst)
        const reversedLayers = [...this.layers].reverse();
        
        list.innerHTML = reversedLayers.map(layer => `
            <div class="layer-item ${layer.id === this.activeLayerId ? 'active' : ''} ${layer.locked ? 'locked' : ''} ${!layer.visible ? 'hidden' : ''}"
                 data-layer-id="${layer.id}"
                 onclick="layerManager.selectLayer(${layer.id})"
                 oncontextmenu="layerManager._showContextMenu(event, ${layer.id})">
                
                <span class="layer-visibility" onclick="event.stopPropagation(); layerManager.toggleVisibility(${layer.id})">
                    ${layer.visible ? '👁️' : '👁️‍🗨️'}
                </span>
                
                <div class="layer-thumb" id="thumb-${layer.id}"></div>
                
                <div class="layer-info">
                    <div class="layer-name" ondblclick="event.stopPropagation(); layerManager._startRename(${layer.id})">
                        ${layer.isBackground ? '🖼️ ' : ''}${this._escapeHtml(layer.name)}
                    </div>
                    <div class="layer-meta">${layer.objects.length} Objekte</div>
                </div>
                
                <input type="range" class="layer-opacity" min="0" max="100" value="${layer.opacity * 100}"
                       onclick="event.stopPropagation()"
                       onchange="layerManager.setOpacity(${layer.id}, this.value / 100)"
                       title="Transparenz: ${Math.round(layer.opacity * 100)}%">
                
                <div class="layer-controls">
                    ${!layer.isBackground ? `
                        <button class="layer-ctrl ${layer.locked ? 'locked' : ''}" 
                                onclick="event.stopPropagation(); layerManager.toggleLock(${layer.id})"
                                title="${layer.locked ? 'Entsperren' : 'Sperren'}">
                            ${layer.locked ? '🔒' : '🔓'}
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
        
        // Ebenen-Zähler aktualisieren
        this.element.querySelector('.layer-count').textContent = 
            `${this.layers.length - 1}/${this.maxLayers - 1} Ebenen`;
        
        // Thumbnails generieren
        this._updateThumbnails();
    }
    
    _updateThumbnails() {
        // Vereinfachte Thumbnails - später mit echten Canvas-Previews
        this.layers.forEach(layer => {
            const thumb = document.getElementById('thumb-' + layer.id);
            if (!thumb) return;
            
            if (layer.isBackground) {
                thumb.style.background = '#FFFFFF';
            } else if (layer.objects.length === 0) {
                thumb.style.background = 'repeating-linear-gradient(45deg, #444, #444 5px, #555 5px, #555 10px)';
            } else {
                thumb.style.background = '#666';
            }
        });
    }
    
    _startRename(layerId) {
        if (layerId === 0) return;
        
        const layer = this.layers.find(l => l.id === layerId);
        const item = this.element.querySelector(`[data-layer-id="${layerId}"] .layer-name`);
        
        const input = document.createElement('input');
        input.type = 'text';
        input.value = layer.name;
        input.maxLength = 20;
        
        input.onblur = () => {
            this.renameLayer(layerId, input.value);
        };
        input.onkeydown = (e) => {
            if (e.key === 'Enter') input.blur();
            if (e.key === 'Escape') {
                input.value = layer.name;
                input.blur();
            }
        };
        
        item.innerHTML = '';
        item.appendChild(input);
        input.focus();
        input.select();
    }
    
    _showContextMenu(event, layerId) {
        event.preventDefault();
        event.stopPropagation();
        
        // Altes Menü entfernen
        document.querySelectorAll('.layer-context-menu').forEach(m => m.remove());
        
        const layer = this.layers.find(l => l.id === layerId);
        if (!layer) return;
        
        const menu = document.createElement('div');
        menu.className = 'layer-context-menu';
        menu.style.left = event.pageX + 'px';
        menu.style.top = event.pageY + 'px';
        
        menu.innerHTML = `
            ${!layer.isBackground ? `
                <button onclick="layerManager.selectLayer(${layerId}); this.parentElement.remove()">✏️ Bearbeiten</button>
                <button onclick="layerManager._startRename(${layerId}); this.parentElement.remove()">📝 Umbenennen</button>
                <button onclick="layerManager.duplicateLayer(${layerId}); this.parentElement.remove()">📋 Duplizieren</button>
                <hr>
                <button onclick="layerManager.moveLayer(${layerId}, 'up'); this.parentElement.remove()">⬆️ Nach oben</button>
                <button onclick="layerManager.moveLayer(${layerId}, 'down'); this.parentElement.remove()">⬇️ Nach unten</button>
                <hr>
                <button onclick="layerManager.toggleLock(${layerId}); this.parentElement.remove()">
                    ${layer.locked ? '🔓 Entsperren' : '🔒 Sperren'}
                </button>
                <button class="danger" onclick="layerManager.removeLayer(${layerId}); this.parentElement.remove()">🗑️ Löschen</button>
            ` : `
                <button disabled>🖼️ Hintergrund-Ebene</button>
            `}
        `;
        
        document.body.appendChild(menu);
        
        // Schließen bei Klick außerhalb
        setTimeout(() => {
            document.addEventListener('click', function close() {
                menu.remove();
                document.removeEventListener('click', close);
            });
        }, 10);
    }
    
    _bindEvents() {
        // Canvas-Events für Layer-Binding
        this.canvas.on('object:added', (e) => {
            if (e.target._layerId === undefined) {
                this.addObjectToActiveLayer(e.target);
            }
        });
        
        this.canvas.on('object:removed', (e) => {
            this.removeObjectFromLayer(e.target);
        });
    }
    
    _showToast(message) {
        const existing = document.querySelector('.layer-toast');
        if (existing) existing.remove();
        
        const toast = document.createElement('div');
        toast.className = 'layer-toast';
        toast.style.cssText = `
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: #1A3503;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 0.9em;
            z-index: 9999;
            animation: fadeInUp 0.3s ease;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    }
    
    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // =====================================================
    // SERIALIZATION
    // =====================================================
    
    toJSON() {
        return {
            layers: this.layers.map(l => ({
                id: l.id,
                name: l.name,
                visible: l.visible,
                locked: l.locked,
                opacity: l.opacity,
                isBackground: l.isBackground
            })),
            activeLayerId: this.activeLayerId,
            nextLayerId: this.nextLayerId
        };
    }
    
    fromJSON(data) {
        // Implementierung für Laden
    }
}

console.log('📚 sgiT Layer Manager loaded!');
