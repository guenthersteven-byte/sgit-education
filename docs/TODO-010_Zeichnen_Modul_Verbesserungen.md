# TODO-010: Zeichnen-Modul Verbesserungen

**Version:** 3.23.2 → 3.24.0  
**Datum:** 11. Dezember 2025  
**Priorität:** HOCH  
**Verzeichnis:** `/zeichnen/`

---

## 📊 Aktueller Stand (Analyse)

### ✅ Bereits implementiert:
| Feature | Status | Details |
|---------|--------|---------|
| Canvas (Fabric.js 5.3.1) | ✅ | 900x600px, responsive |
| Undo/Redo | ✅ | 20 Schritte History |
| Stift-Tool | ✅ | PencilBrush |
| Pinsel-Tool | ✅ | CircleBrush |
| Spray-Tool | ✅ | SprayBrush |
| Radierer | ✅ | Weiß übermalen |
| Linie | ✅ | Formen-Tool |
| Rechteck | ✅ | Formen-Tool |
| Kreis | ✅ | Formen-Tool |
| Dreieck | ✅ | Formen-Tool |
| Farbpalette | ✅ | Altersbasiert (9-25 Farben) |
| Größenregler | ✅ | 1-50px |
| Speichern | ✅ | PNG Export, Sats-Belohnung |
| Tutorials | ✅ | 20 JSON-Tutorials vorhanden |
| Keyboard Shortcuts | ✅ | Strg+Z/Y/S |
| Touch-Support | ✅ | Tablets |
| Altersbasierte Features | ✅ | Werkzeuge nach Alter |

### ⏳ Noch zu implementieren:
| Feature | Aufwand | Priorität |
|---------|---------|-----------|
| 🖌️ Erweiterte Brushes (Airbrush, Marker, Kreide) | ~2-3h | MITTEL |
| 📐 Ebenen-System (Layer) | ~4-5h | HOCH |
| 🎨 HSL-Farbkreis + Pipette | ~2h | MITTEL |
| 💾 Galerie: Laden & Weiterbearbeiten | ~2-3h | HOCH |
| 📏 Polygon-Tool | ~1h | NIEDRIG |
| 🔤 Text-Tool | ~2h | MITTEL |
| 🖼️ Vorlagen/Ausmalbilder | ~2h | NIEDRIG |
| 🌈 Farbverlauf-Tool | ~2h | NIEDRIG |

---

## 🎯 Implementierungsreihenfolge

### Phase 1: Quick Wins (~4h)
1. **🖌️ Erweiterte Brushes** - Airbrush, Marker, Kreide hinzufügen
2. **🎨 HSL-Farbkreis** - Besserer Color Picker mit Pipette

### Phase 2: Core Features (~6h)
3. **📐 Ebenen-System** - Layer-Panel mit Add/Remove/Reorder
4. **💾 Galerie-Integration** - Gespeicherte Bilder laden & bearbeiten

### Phase 3: Extras (~4h)
5. **🔤 Text-Tool** - Schrift auf Canvas
6. **🖼️ Vorlagen** - Ausmalbilder für Kinder
7. **📏 Polygon-Tool** - Freihand-Polygone

---

## 📁 Betroffene Dateien

| Datei | Änderung |
|-------|----------|
| `/zeichnen/canvas.php` | Hauptänderungen |
| `/zeichnen/js/brushes.js` | NEU: Erweiterte Pinsel |
| `/zeichnen/js/layers.js` | NEU: Ebenen-System |
| `/zeichnen/js/colorpicker.js` | NEU: HSL-Picker |
| `/zeichnen/css/drawing.css` | NEU: Styling |
| `/zeichnen/gallery.php` | Laden-Feature |
| `/zeichnen/templates/` | NEU: Ausmalbilder |

---

## 🔧 Technische Details

### Fabric.js Custom Brushes
```javascript
// Airbrush (weiche Kanten)
fabric.AirbrushBrush = fabric.util.createClass(fabric.BaseBrush, {...});

// Marker (halbtransparent)
fabric.MarkerBrush = fabric.util.createClass(fabric.PencilBrush, {...});

// Kreide (texturiert)
fabric.ChalkBrush = fabric.util.createClass(fabric.PencilBrush, {...});
```

### Ebenen-System Konzept
```javascript
const layers = [
  { id: 1, name: 'Hintergrund', visible: true, locked: false, objects: [...] },
  { id: 2, name: 'Ebene 1', visible: true, locked: false, objects: [...] }
];
```

---

*Dokumentation erstellt am 11.12.2025*
