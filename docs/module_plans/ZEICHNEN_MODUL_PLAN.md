# 🎨 Zeichnen-Modul - Planungsdokument

**Erstellt:** 06. Dezember 2025  
**Version:** 1.0 (Planungsphase)  
**Autor:** Claude AI / Steven Günther  
**Status:** 📋 PLANUNG

---

## 📋 INHALTSVERZEICHNIS

1. [Übersicht](#übersicht)
2. [Docker-Komponenten](#docker-komponenten)
3. [Frontend-Libraries](#frontend-libraries)
4. [Modulstruktur](#modulstruktur)
5. [Lernziele nach Alter](#lernziele-nach-alter)
6. [Technische Implementierung](#technische-implementierung)
7. [Integration in bestehendes System](#integration)
8. [Zeitplan & Aufwand](#zeitplan)

---

## 1. 📌 ÜBERSICHT

### Modulziel
Ein interaktives Zeichenmodul für die sgiT Education Platform, das:
- Grundlegende Zeichentechniken vermittelt
- Altersgerechte Übungen bietet (5-21 Jahre)
- Kreativität fördert
- In das Satoshi-Belohnungssystem integriert ist

### Kernfunktionen
| Funktion | Beschreibung |
|----------|-------------|
| **Freies Zeichnen** | Canvas zum freien Malen |
| **Geführte Übungen** | Schritt-für-Schritt Tutorials |
| **Formenerkennung** | AI-gestützte Bewertung |
| **Galerie** | Speichern & Teilen von Werken |
| **Challenges** | Tägliche/wöchentliche Aufgaben |

---

## 2. 🐳 DOCKER-KOMPONENTEN

### Option A: Lightweight (Empfohlen für Start)

```yaml
# Nur Frontend-Canvas, keine zusätzlichen Container nötig
# Nutzt bestehende nginx/PHP-FPM Infrastruktur
```

**Vorteile:**
- ✅ Keine zusätzliche Container-Last
- ✅ Schnelle Implementierung (2-4h)
- ✅ JavaScript-basiert (Browser nativ)

**Geeignete Libraries:**
| Library | Größe | Features | Eignung |
|---------|-------|----------|---------|
| **Fabric.js** | ~300KB | Objekte, Filter, Export | ⭐⭐⭐⭐⭐ |
| **Konva.js** | ~150KB | Shapes, Events, Performance | ⭐⭐⭐⭐ |
| **p5.js** | ~800KB | Creative Coding, Animationen | ⭐⭐⭐⭐ |
| **Paper.js** | ~200KB | Vektor, Kurven, Pfade | ⭐⭐⭐ |

**Empfehlung:** **Fabric.js** - beste Balance aus Features & Größe

---

### Option B: Mit Backend-Bildverarbeitung

```yaml
# docker-compose.yml Erweiterung
services:
  sgit_imagemagick:
    image: dpokidov/imagemagick:latest
    container_name: sgit_imagemagick
    volumes:
      - ../uploads/drawings:/data
    networks:
      - sgit_network
```

**Wann sinnvoll:**
- Bildkonvertierung (PNG → SVG, PDF Export)
- Thumbnails für Galerie
- Wasserzeichen für geteilte Werke

---

### Option C: Mit AI-Bilderkennung (Fortgeschritten)

```yaml
# Für Formenerkennung & Bewertung
services:
  sgit_ml:
    image: tensorflow/tensorflow:latest-gpu
    container_name: sgit_tensorflow
    volumes:
      - ./ml_models:/models
    environment:
      - NVIDIA_VISIBLE_DEVICES=all
    deploy:
      resources:
        reservations:
          devices:
            - driver: nvidia
              count: 1
              capabilities: [gpu]
```

**Alternativ ohne GPU:**
```yaml
  sgit_ml_cpu:
    image: tensorflow/tensorflow:latest
    container_name: sgit_tensorflow_cpu
    volumes:
      - ./ml_models:/models
```

**Anwendungsfälle:**
- Erkennung ob Kreis/Quadrat korrekt gezeichnet
- Bewertung von Symmetrie
- Handschrifterkennung
- Style-Transfer für kreative Effekte

---

### Option D: Excalidraw (Whiteboard-Style)

```yaml
  sgit_excalidraw:
    image: excalidraw/excalidraw:latest
    container_name: sgit_excalidraw
    ports:
      - "3030:80"
    networks:
      - sgit_network
```

**Features:**
- ✅ Fertiges Whiteboard-Tool
- ✅ Kollaboratives Zeichnen
- ✅ Export als PNG/SVG
- ❌ Weniger Lernspiel-Charakter

---

## 3. 🖌️ FRONTEND-LIBRARIES IM DETAIL

### Fabric.js (Empfohlen)

```javascript
// Beispiel: Einfacher Zeichenbereich
const canvas = new fabric.Canvas('drawing-canvas', {
    isDrawingMode: true,
    width: 800,
    height: 600
});

// Pinsel konfigurieren
canvas.freeDrawingBrush.width = 5;
canvas.freeDrawingBrush.color = '#43D240'; // sgiT Grün!

// Formen hinzufügen
const circle = new fabric.Circle({
    radius: 50,
    fill: 'transparent',
    stroke: '#1A3503',
    strokeWidth: 2
});
canvas.add(circle);

// Als PNG exportieren
const dataURL = canvas.toDataURL({ format: 'png' });
```

**Integration via CDN:**
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
```

---

### Alternative: tldraw (Modern & React-basiert)

```yaml
  sgit_tldraw:
    image: node:20-alpine
    container_name: sgit_tldraw
    working_dir: /app
    command: npm run start
    volumes:
      - ./tldraw-app:/app
    ports:
      - "3031:3000"
```

**Vorteile:**
- Modernes UI
- Undo/Redo eingebaut
- Multiplayer-fähig
- React-Komponente

---

## 4. 📚 MODULSTRUKTUR

### Dateistruktur

```
C:\xampp\htdocs\Education\
├── 📁 zeichnen/                    # Neues Modul
│   ├── index.php                   # Hauptseite
│   ├── canvas.php                  # Zeichenbereich
│   ├── tutorials.php               # Schritt-für-Schritt
│   ├── gallery.php                 # Gespeicherte Werke
│   ├── challenges.php              # Tägliche Aufgaben
│   ├── 📁 js/
│   │   ├── drawing-tools.js        # Fabric.js Wrapper
│   │   ├── tutorials.js            # Tutorial-Logik
│   │   └── shape-validator.js      # Formenerkennung
│   ├── 📁 css/
│   │   └── zeichnen.css           # Modul-spezifisches CSS
│   ├── 📁 tutorials/
│   │   ├── 01_kreis.json          # Tutorial-Daten
│   │   ├── 02_quadrat.json
│   │   ├── 03_stern.json
│   │   └── ...
│   └── 📁 assets/
│       ├── brushes/               # Pinsel-Texturen
│       └── templates/             # Vorlagen zum Nachzeichnen
├── 📁 uploads/
│   └── 📁 drawings/               # Benutzer-Zeichnungen
│       └── 📁 {user_id}/          # Pro User getrennt
└── 📁 AI/data/
    └── drawing_progress.db        # Fortschritt-Tracking (SQLite)
```

### Datenbank-Erweiterung

```sql
-- Neue Tabelle für Zeichnungen
CREATE TABLE IF NOT EXISTS drawings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    thumbnail TEXT,
    tutorial_id TEXT,           -- NULL bei freiem Zeichnen
    score INTEGER DEFAULT 0,    -- Bewertung (0-100)
    sats_earned INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Fortschritt bei Tutorials
CREATE TABLE IF NOT EXISTS drawing_tutorials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    tutorial_id TEXT NOT NULL,
    completed BOOLEAN DEFAULT 0,
    best_score INTEGER DEFAULT 0,
    attempts INTEGER DEFAULT 0,
    completed_at DATETIME,
    UNIQUE(user_id, tutorial_id)
);
```

---

## 5. 👶👦👨 LERNZIELE NACH ALTER

### Altersgruppe 5-7 Jahre (Kindergarten/Vorschule)

| Übung | Beschreibung | Sats |
|-------|--------------|------|
| 🔴 Kreis malen | Großer Kreis nachzeichnen | 5 |
| 🟦 Quadrat malen | Einfaches Quadrat | 5 |
| 🌈 Farben lernen | Bereiche ausmalen | 3 |
| 😊 Gesicht malen | Einfaches Smiley | 8 |
| 🌳 Baum zeichnen | Stamm + Krone | 10 |
| 🏠 Haus zeichnen | Einfaches Haus | 12 |

**Werkzeuge:** Große Pinsel, leuchtende Farben, wenige Optionen

---

### Altersgruppe 8-12 Jahre (Grundschule)

| Übung | Beschreibung | Sats |
|-------|--------------|------|
| ⭐ Stern zeichnen | 5-zackiger Stern | 10 |
| 🦋 Symmetrie | Schmetterlingsflügel | 15 |
| 🎨 Farbmischung | Primär → Sekundärfarben | 12 |
| 🏔️ Landschaft | Berg, Sonne, Wiese | 20 |
| 🐱 Tiere | Schritt-für-Schritt Katze | 25 |
| 📐 Perspektive | Einfache 1-Punkt-Perspektive | 30 |

**Werkzeuge:** Mehr Pinselgrößen, Radierer, Formen-Tool

---

### Altersgruppe 13-17 Jahre (Teenager)

| Übung | Beschreibung | Sats |
|-------|--------------|------|
| 👤 Porträt | Proportionen des Gesichts | 35 |
| 🏛️ Architektur | 2-Punkt-Perspektive | 40 |
| 🎭 Emotionen | Gesichtsausdrücke | 30 |
| 💡 Schattierung | Licht & Schatten | 45 |
| 🖼️ Stilkopie | Impressionismus nachahmen | 50 |
| ✏️ Skizzieren | Schnelle Sketches | 25 |

**Werkzeuge:** Voll ausgestattetes Toolkit, Layer, Deckkraft

---

### Altersgruppe 18-21 Jahre (Erwachsene)

| Übung | Beschreibung | Sats |
|-------|--------------|------|
| 🎨 Kunstgeschichte | Epochen-Challenge | 50 |
| 👁️ Realismus | Fotorealistische Objekte | 75 |
| 🔮 Digital Art | Tablet-Techniken | 60 |
| 📱 UI Design | Interface-Elemente | 55 |
| 🖌️ Freie Kunst | Kreativ-Challenge | 40 |

---

## 6. 🔧 TECHNISCHE IMPLEMENTIERUNG

### Phase 1: Basis-Canvas (2-4 Stunden)

```php
// zeichnen/canvas.php
<?php
require_once '../includes/session.php';
require_once '../includes/wallet_manager.php';

$user = getCurrentUser();
$age = calculateAge($user['birthdate']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>🎨 Zeichnen - sgiT Education</title>
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/zeichnen/css/zeichnen.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
</head>
<body>
    <div class="drawing-container">
        <!-- Werkzeugleiste -->
        <div class="toolbar" id="toolbar">
            <button onclick="setTool('pencil')" class="active">✏️ Stift</button>
            <button onclick="setTool('brush')">🖌️ Pinsel</button>
            <button onclick="setTool('eraser')">🧽 Radierer</button>
            <input type="color" id="colorPicker" value="#43D240">
            <input type="range" id="brushSize" min="1" max="50" value="5">
            <button onclick="clearCanvas()">🗑️ Löschen</button>
            <button onclick="saveDrawing()">💾 Speichern</button>
        </div>
        
        <!-- Canvas -->
        <canvas id="drawing-canvas"></canvas>
        
        <!-- Aktuelle Aufgabe -->
        <div class="task-panel" id="taskPanel">
            <!-- Dynamisch geladen -->
        </div>
    </div>
    
    <script src="/zeichnen/js/drawing-tools.js"></script>
</body>
</html>
```

### Phase 2: Tutorials (4-6 Stunden)

**Tutorial-Format (JSON):**
```json
{
    "id": "circle_basic",
    "title": "Einen Kreis zeichnen",
    "age_min": 5,
    "age_max": 8,
    "sats_reward": 5,
    "steps": [
        {
            "instruction": "Setze deinen Stift in die Mitte",
            "highlight": { "x": 400, "y": 300 },
            "audio": "audio/circle_step1.mp3"
        },
        {
            "instruction": "Zeichne einen großen Bogen nach oben",
            "template": "templates/circle_step2.svg"
        }
    ],
    "validation": {
        "type": "shape_match",
        "target": "circle",
        "tolerance": 0.7
    }
}
```

### Phase 3: Formen-Validierung (6-8 Stunden)

```javascript
// zeichnen/js/shape-validator.js
class ShapeValidator {
    
    // Einfache Kreis-Erkennung ohne AI
    validateCircle(points) {
        if (points.length < 20) return { valid: false, score: 0 };
        
        // Mittelpunkt berechnen
        const center = this.calculateCenter(points);
        
        // Durchschnittlicher Radius
        const avgRadius = this.calculateAverageRadius(points, center);
        
        // Abweichung vom perfekten Kreis
        let totalDeviation = 0;
        points.forEach(p => {
            const dist = this.distance(p, center);
            totalDeviation += Math.abs(dist - avgRadius);
        });
        
        const avgDeviation = totalDeviation / points.length;
        const score = Math.max(0, 100 - (avgDeviation / avgRadius * 100));
        
        return {
            valid: score > 60,
            score: Math.round(score),
            feedback: this.getCircleFeedback(score)
        };
    }
    
    getCircleFeedback(score) {
        if (score >= 90) return "🎉 Perfekt! Ein wunderschöner Kreis!";
        if (score >= 75) return "👍 Sehr gut! Fast rund!";
        if (score >= 60) return "😊 Gut gemacht! Übung macht den Meister!";
        return "🔄 Versuch es nochmal - zeichne langsamer!";
    }
}
```

---

## 7. 🔗 INTEGRATION IN BESTEHENDES SYSTEM

### Wallet-Integration

```php
// Bei erfolgreicher Übung
function awardDrawingSats($userId, $tutorialId, $score) {
    $wallet = new WalletManager($userId);
    
    // Basis-Sats aus Tutorial
    $tutorial = getTutorialById($tutorialId);
    $baseSats = $tutorial['sats_reward'];
    
    // Bonus für hohen Score
    $bonus = floor(($score - 60) / 10); // +1 Sat pro 10% über 60%
    $totalSats = $baseSats + $bonus;
    
    $wallet->addSats($totalSats, "Zeichnen: " . $tutorial['title']);
    
    return $totalSats;
}
```

### Achievements

| Achievement | Bedingung | Sats Bonus |
|-------------|-----------|-----------|
| 🎨 Erster Strich | Erste Zeichnung gespeichert | +10 |
| 🔵 Kreis-Meister | 10 Kreise mit >80% | +25 |
| 🌈 Farbenkünstler | Alle Farben verwendet | +15 |
| 📚 Tutorial-König | Alle Basis-Tutorials | +50 |
| 🖼️ Galerie-Star | 50 Zeichnungen gespeichert | +100 |

### Navigation

```php
// In adaptive_learning.php hinzufügen:
$modules[] = [
    'id' => 'zeichnen',
    'name' => 'Zeichnen',
    'icon' => '🎨',
    'description' => 'Lerne zeichnen!',
    'url' => '/zeichnen/index.php'
];
```

---

## 8. ⏱️ ZEITPLAN & AUFWAND

### Implementierungs-Phasen

| Phase | Beschreibung | Aufwand | Priorität |
|-------|-------------|---------|-----------|
| **1** | Basis-Canvas mit Fabric.js | 2-4h | 🔴 HOCH |
| **2** | Speichern & Laden | 2h | 🔴 HOCH |
| **3** | Werkzeugleiste (Farben, Größen) | 2h | 🔴 HOCH |
| **4** | 5 Basis-Tutorials | 4h | 🟡 MITTEL |
| **5** | Formen-Validierung (ohne AI) | 4h | 🟡 MITTEL |
| **6** | Wallet-Integration | 1h | 🟡 MITTEL |
| **7** | Galerie-Ansicht | 3h | 🟢 NIEDRIG |
| **8** | Weitere Tutorials (20+) | 8h | 🟢 NIEDRIG |
| **9** | AI-Bildanalyse (optional) | 8-12h | 🟢 OPTIONAL |

**Gesamt Minimum Viable Product:** ~12-15 Stunden

---

## 9. 📊 EMPFEHLUNG

### Für den Start (MVP)

| Komponente | Empfehlung |
|------------|------------|
| **Frontend** | Fabric.js (CDN) |
| **Backend** | Bestehendes PHP/SQLite |
| **Docker** | KEINE zusätzlichen Container nötig |
| **Speicher** | Lokaler Upload-Ordner |

### Spätere Erweiterungen

| Feature | Docker-Komponente | Wann |
|---------|------------------|------|
| Thumbnails | ImageMagick Container | Bei >100 Zeichnungen |
| AI-Bewertung | TensorFlow Container | Wenn Basis läuft |
| Kollaboration | Excalidraw/tldraw | Wenn Multiplayer gewünscht |
| PDF-Export | Inkscape CLI | Für Zertifikate |

---

## 10. 🎯 NÄCHSTE SCHRITTE

1. **Entscheidung:** Welche Phase zuerst?
2. **Fabric.js einbinden:** CDN oder lokale Kopie
3. **Datenbankschema erstellen:** drawings.db
4. **Erste Seite:** `/zeichnen/index.php`
5. **Canvas-Komponente:** Basis-Zeichenfläche
6. **Test:** Erstes Tutorial (Kreis zeichnen)

---

## 📝 ZUSAMMENFASSUNG

| Aspekt | Empfehlung |
|--------|------------|
| **Docker-Komponenten** | Keine zusätzlichen für MVP |
| **Frontend-Library** | Fabric.js ⭐ |
| **Aufwand MVP** | 12-15 Stunden |
| **Integration** | Wallet ✅, Achievements ✅, Navigation ✅ |
| **Altersgruppen** | 4 Stufen (5-7, 8-12, 13-17, 18-21) |
| **Satoshi-System** | 3-75 Sats pro Übung |

---

**Dokument-Status:** ✅ VOLLSTÄNDIG  
**Bereit zur Implementierung:** JA  
**Nächster Schritt:** Phase 1 starten?

---

*Erstellt für sgiT Education Platform v3.8.0*  
*Branding: #1A3503 / #43D240*
