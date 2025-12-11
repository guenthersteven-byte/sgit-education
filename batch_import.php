<?php
/**
 * ============================================================================
 * sgiT Education - CSV Import v4.0 (TODO-005)
 * ============================================================================
 * 
 * DRAG & DROP MULTI-FILE IMPORT mit Auto-Modul-Erkennung
 * 
 * v4.0: TODO-005 - Multi-File, Drag & Drop, Auto-Modul, AJAX Progress
 * v3.0: Neues CI mit gemeinsamer Navigation (TODO-008)
 * v2.0: CSV-Upload, Template, Vorschau, Einzeldatei-Import
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 4.0
 * @date 09.12.2025
 * ============================================================================
 */

session_start();

// Zentrale Versionsverwaltung
require_once __DIR__ . '/includes/version.php';
require_once __DIR__ . '/includes/CSVQuestionImporter.php';

// ============================================================================
// KONFIGURATION
// ============================================================================
$uploadDir = __DIR__ . '/uploads/csv/';
$generatedPath = __DIR__ . '/questions/generated/';

// Upload-Verzeichnis erstellen falls nicht vorhanden
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Module mit Icons
$modules = [
    'mathematik' => ['icon' => '🔢', 'name' => 'Mathematik'],
    'lesen' => ['icon' => '📖', 'name' => 'Lesen'],
    'englisch' => ['icon' => '🇬🇧', 'name' => 'Englisch'],
    'wissenschaft' => ['icon' => '🔬', 'name' => 'Wissenschaft'],
    'erdkunde' => ['icon' => '🌍', 'name' => 'Erdkunde'],
    'chemie' => ['icon' => '⚗️', 'name' => 'Chemie'],
    'physik' => ['icon' => '⚛️', 'name' => 'Physik'],
    'kunst' => ['icon' => '🎨', 'name' => 'Kunst'],
    'musik' => ['icon' => '🎵', 'name' => 'Musik'],
    'computer' => ['icon' => '💻', 'name' => 'Computer'],
    'bitcoin' => ['icon' => '₿', 'name' => 'Bitcoin'],
    'geschichte' => ['icon' => '📚', 'name' => 'Geschichte'],
    'biologie' => ['icon' => '🧬', 'name' => 'Biologie'],
    'finanzen' => ['icon' => '💰', 'name' => 'Finanzen'],
    'programmieren' => ['icon' => '👨‍💻', 'name' => 'Programmieren'],
    'verkehr' => ['icon' => '🚗', 'name' => 'Verkehr'],
    'unnuetzes_wissen' => ['icon' => '🤯', 'name' => 'Unnützes Wissen'],
    'sport' => ['icon' => '🏃', 'name' => 'Sport']
];


// ============================================================================
// HILFSFUNKTIONEN
// ============================================================================

/**
 * Erkennt das Modul automatisch aus dem Dateinamen
 * z.B. "mathematik_age5-8_20251209.csv" → "mathematik"
 */
function detectModuleFromFilename($filename, $availableModules) {
    $filename = strtolower(basename($filename));
    
    // Sortiere Module nach Länge (längste zuerst) für besseres Matching
    $moduleKeys = array_keys($availableModules);
    usort($moduleKeys, fn($a, $b) => strlen($b) - strlen($a));
    
    foreach ($moduleKeys as $module) {
        // Prüfe ob Dateiname mit Modulnamen beginnt
        if (strpos($filename, $module) === 0) {
            return $module;
        }
        // Prüfe auch mit Unterstrich nach Modulname
        if (strpos($filename, $module . '_') !== false) {
            return $module;
        }
    }
    
    return null; // Nicht erkannt
}

/**
 * Zählt Zeilen in einer CSV-Datei (ohne Header)
 */
function countCsvRows($filepath) {
    $count = 0;
    if (($handle = fopen($filepath, 'r')) !== false) {
        fgetcsv($handle, 0, ';'); // Skip header
        while (fgetcsv($handle, 0, ';') !== false) {
            $count++;
        }
        fclose($handle);
    }
    return $count;
}

// ============================================================================
// API ENDPOINT FÜR AJAX-IMPORT
// ============================================================================

if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $action = $_GET['api'];
    
    // IMPORT SINGLE FILE
    if ($action === 'import_single' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Datei aus Upload oder Pfad
            $filepath = '';
            $module = $_POST['module'] ?? '';
            $dryRun = isset($_POST['dry_run']) && $_POST['dry_run'] === '1';
            
            // Option 1: Neue Datei hochgeladen
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['csv_file'];
                $filename = 'upload_' . date('Ymd_His') . '_' . basename($file['name']);
                $filepath = $uploadDir . $filename;
                
                if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                    throw new Exception('Fehler beim Speichern der Datei');
                }
                
                // Auto-Erkennung wenn kein Modul angegeben
                if (empty($module)) {
                    $module = detectModuleFromFilename($file['name'], $modules);
                }
            }
            // Option 2: Bestehende Datei (Pfad übergeben)
            elseif (!empty($_POST['filepath'])) {
                $filepath = $_POST['filepath'];
                if (!file_exists($filepath)) {
                    throw new Exception('Datei nicht gefunden');
                }
                
                // Auto-Erkennung wenn kein Modul angegeben
                if (empty($module)) {
                    $module = detectModuleFromFilename($filepath, $modules);
                }
            }
            else {
                throw new Exception('Keine Datei angegeben');
            }
            
            // Modul validieren
            if (empty($module) || !isset($modules[$module])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Modul nicht erkannt. Bitte manuell auswählen.',
                    'detected_module' => null,
                    'filename' => basename($filepath)
                ]);
                exit;
            }
            
            // Import durchführen
            $importer = new CSVQuestionImporter();
            $result = $importer->importFromCSV($filepath, $module, $dryRun);
            
            echo json_encode([
                'success' => true,
                'filename' => basename($filepath),
                'module' => $module,
                'module_name' => $modules[$module]['name'],
                'module_icon' => $modules[$module]['icon'],
                'imported' => $result['imported'],
                'duplicates' => $result['duplicates'],
                'errors' => $result['errors'],
                'total' => $result['total'],
                'dry_run' => $dryRun,
                'error_messages' => $result['error_messages'] ?? []
            ]);
            exit;
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    // DETECT MODULE
    if ($action === 'detect_module') {
        $filename = $_GET['filename'] ?? '';
        $detected = detectModuleFromFilename($filename, $modules);
        
        echo json_encode([
            'detected' => $detected !== null,
            'module' => $detected,
            'module_name' => $detected ? $modules[$detected]['name'] : null,
            'module_icon' => $detected ? $modules[$detected]['icon'] : null
        ]);
        exit;
    }
    
    // LIST GENERATED FILES
    if ($action === 'list_generated') {
        $files = [];
        if (is_dir($generatedPath)) {
            $csvFiles = glob($generatedPath . '*.csv');
            foreach ($csvFiles as $file) {
                $filename = basename($file);
                $detectedModule = detectModuleFromFilename($filename, $modules);
                
                if ($detectedModule) {
                    $files[] = [
                        'path' => $file,
                        'filename' => $filename,
                        'module' => $detectedModule,
                        'module_name' => $modules[$detectedModule]['name'],
                        'module_icon' => $modules[$detectedModule]['icon'],
                        'size' => filesize($file),
                        'rows' => countCsvRows($file),
                        'date' => filemtime($file)
                    ];
                }
            }
            usort($files, fn($a, $b) => $b['date'] - $a['date']);
        }
        
        echo json_encode(['success' => true, 'files' => $files, 'count' => count($files)]);
        exit;
    }
    
    echo json_encode(['error' => 'Unbekannte API-Aktion']);
    exit;
}


// ============================================================================
// TEMPLATE DOWNLOAD
// ============================================================================
if (isset($_GET['download_template'])) {
    $template = "frage;antwort_a;antwort_b;antwort_c;antwort_d;richtig;schwierigkeit;min_alter;max_alter;erklaerung;typ
Was ist 2 + 2?;3;4;5;6;B;1;5;8;2 + 2 ergibt 4;basic
Wie viele Tage hat eine Woche?;5;6;7;8;C;1;5;10;Eine Woche hat 7 Tage;basic
";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="fragen_template.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo $template;
    exit;
}

// ============================================================================
// HTML OUTPUT
// ============================================================================

// Header einbinden
$currentPage = 'csv_import';
$pageTitle = 'CSV Import';
require_once __DIR__ . '/includes/generator_header.php';
?>

<!-- TODO-005: Enhanced Drag & Drop Multi-File Import -->
<style>
    /* Drop Zone */
    .drop-zone {
        border: 3px dashed var(--border);
        border-radius: 16px;
        padding: 50px 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: rgba(0,0,0,0.2);
        position: relative;
    }
    
    .drop-zone:hover, .drop-zone.dragover {
        border-color: var(--accent);
        background: rgba(67,210,64,0.1);
        transform: scale(1.01);
    }
    
    .drop-zone.dragover::after {
        content: '📥 Dateien hier ablegen!';
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(67,210,64,0.2);
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--accent);
        border-radius: 14px;
    }
    
    .drop-zone-icon { font-size: 4rem; margin-bottom: 15px; }
    .drop-zone-title { font-size: 1.3rem; font-weight: 600; color: var(--accent); margin-bottom: 8px; }
    .drop-zone-hint { color: var(--text-muted); font-size: 0.9rem; }
    .drop-zone input[type="file"] { display: none; }
    
    /* File Queue */
    .file-queue { margin-top: 25px; }
    .file-queue-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .file-queue-title { font-weight: 600; color: var(--accent); }
    
    .file-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: rgba(0,0,0,0.3);
        border: 1px solid var(--border);
        border-radius: 10px;
        margin-bottom: 10px;
        transition: all 0.2s;
    }
    
    .file-item:hover { border-color: var(--accent); }
    .file-item.importing { background: rgba(67,210,64,0.15); border-color: var(--accent); }
    .file-item.success { background: rgba(40, 167, 69, 0.2); border-color: #28a745; }
    .file-item.error { background: rgba(220, 53, 69, 0.2); border-color: #dc3545; }
    
    .file-icon { font-size: 2rem; }
    .file-details { flex: 1; }
    .file-name { font-weight: 600; font-size: 0.95rem; color: #fff; }
    .file-meta { font-size: 0.8rem; color: var(--text-muted); margin-top: 3px; }
    .file-module {
        padding: 4px 12px;
        background: var(--accent);
        color: #000;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .file-module.unknown { background: #ffc107; color: #333; }
    
    .file-progress {
        width: 100%;
        height: 4px;
        background: rgba(0,0,0,0.3);
        border-radius: 2px;
        margin-top: 8px;
        overflow: hidden;
    }
    .file-progress-bar {
        height: 100%;
        background: var(--accent);
        transition: width 0.3s;
    }
    
    .file-result { text-align: right; min-width: 120px; }
    .file-result-stat { font-size: 0.8rem; }
    .file-result-stat.imported { color: #6cff6c; }
    .file-result-stat.duplicates { color: #ffc107; }
    .file-result-stat.errors { color: #ff6b6b; }
    
    .file-actions { display: flex; gap: 8px; }
    .file-btn {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.2s;
    }
    .file-btn-remove { background: rgba(220, 53, 69, 0.3); color: #ff6b6b; }
    .file-btn-remove:hover { background: #dc3545; color: white; }
    
    /* Import Controls */
    .import-controls {
        display: flex;
        gap: 15px;
        align-items: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--border);
    }
    
    /* Summary */
    .import-summary {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .summary-card {
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        color: white;
        border: 1px solid var(--border);
    }
    .summary-card.total { background: rgba(26, 53, 3, 0.8); }
    .summary-card.success { background: rgba(40, 167, 69, 0.3); }
    .summary-card.warning { background: rgba(255, 193, 7, 0.3); color: #ffc107; }
    .summary-card.danger { background: rgba(220, 53, 69, 0.3); color: #ff6b6b; }
    .summary-value { font-size: 2rem; font-weight: bold; }
    .summary-label { font-size: 0.85rem; opacity: 0.9; }
    
    /* Tabs */
    .import-tabs { display: flex; gap: 5px; margin-bottom: 20px; }
    .import-tab {
        padding: 12px 24px;
        background: rgba(0,0,0,0.3);
        border: 1px solid var(--border);
        border-radius: 8px 8px 0 0;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--text-muted);
        transition: all 0.2s;
        text-decoration: none;
    }
    .import-tab:hover { background: rgba(67, 210, 64, 0.1); color: #fff; }
    .import-tab.active { background: var(--accent); color: #000; border-color: var(--accent); }
    
    @media (max-width: 768px) {
        .import-summary { grid-template-columns: repeat(2, 1fr); }
        .file-item { flex-wrap: wrap; }
        .file-result { width: 100%; text-align: left; margin-top: 10px; }
    }
</style>


<!-- Tabs -->
<div class="import-tabs">
    <button class="import-tab active" data-tab="upload">📥 Drag & Drop Import</button>
    <button class="import-tab" data-tab="generated">📁 Generierte CSVs</button>
    <button class="import-tab" data-tab="template">📋 Template & Hilfe</button>
</div>

<!-- TAB: UPLOAD (Drag & Drop) -->
<div id="tab-upload" class="tab-content">
    <div class="gen-card">
        <div class="gen-card-header">
            <span class="gen-card-title">📥 CSV-Dateien importieren</span>
            <a href="?download_template" class="gen-btn gen-btn-secondary">📥 Template</a>
        </div>
        
        <!-- Drop Zone -->
        <div class="drop-zone" id="dropZone">
            <div class="drop-zone-icon">📄</div>
            <div class="drop-zone-title">CSV-Dateien hier ablegen</div>
            <div class="drop-zone-hint">
                oder klicken zum Auswählen • Mehrere Dateien möglich<br>
                <strong>Auto-Erkennung:</strong> mathematik_*.csv → Mathematik
            </div>
            <input type="file" id="fileInput" accept=".csv" multiple>
        </div>
        
        <!-- File Queue -->
        <div class="file-queue" id="fileQueue" style="display: none;">
            <div class="file-queue-header">
                <span class="file-queue-title">📋 Dateien zum Import (<span id="fileCount">0</span>)</span>
                <button class="gen-btn gen-btn-danger" id="clearQueue" style="padding: 6px 12px; font-size: 0.8rem;">
                    🗑️ Alle entfernen
                </button>
            </div>
            <div id="fileList"></div>
            
            <div class="import-controls">
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="dryRunCheck">
                    <span>🔍 Dry-Run (nur validieren)</span>
                </label>
                <div style="flex: 1;"></div>
                <button class="gen-btn gen-btn-primary" id="startImport" style="padding: 12px 30px;">
                    🚀 Import starten
                </button>
            </div>
        </div>
        
        <!-- Results Summary (nach Import) -->
        <div class="import-summary" id="importSummary" style="display: none;">
            <div class="summary-card total">
                <div class="summary-value" id="summaryTotal">0</div>
                <div class="summary-label">Fragen geprüft</div>
            </div>
            <div class="summary-card success">
                <div class="summary-value" id="summaryImported">0</div>
                <div class="summary-label">Importiert</div>
            </div>
            <div class="summary-card warning">
                <div class="summary-value" id="summaryDuplicates">0</div>
                <div class="summary-label">Duplikate</div>
            </div>
            <div class="summary-card danger">
                <div class="summary-value" id="summaryErrors">0</div>
                <div class="summary-label">Fehler</div>
            </div>
        </div>
    </div>
</div>

<!-- TAB: GENERATED FILES -->
<div id="tab-generated" class="tab-content" style="display: none;">
    <div class="gen-card">
        <div class="gen-card-header">
            <span class="gen-card-title">📁 Generierte CSV-Dateien</span>
            <button class="gen-btn gen-btn-secondary" id="refreshGenerated">🔄 Aktualisieren</button>
        </div>
        <p style="margin-bottom: 15px; color: var(--text-muted);">
            Klicke auf eine Datei um sie zu importieren. Das Modul wird automatisch erkannt.
        </p>
        <div id="generatedFilesList">Lade...</div>
    </div>
</div>

<!-- TAB: TEMPLATE -->
<div id="tab-template" class="tab-content" style="display: none;">
    <div class="gen-card">
        <div class="gen-card-header">
            <span class="gen-card-title">📋 CSV-Format & Hilfe</span>
        </div>
        
        <h4 style="color: var(--primary); margin-bottom: 10px;">Format (Semikolon-getrennt):</h4>
        <code style="display: block; background: var(--primary); color: var(--accent); padding: 15px; border-radius: 8px; font-size: 0.85rem; overflow-x: auto;">
frage;antwort_a;antwort_b;antwort_c;antwort_d;richtig;schwierigkeit;min_alter;max_alter;erklaerung;typ
        </code>
        
        <div class="gen-grid gen-grid-2" style="margin-top: 20px;">
            <div>
                <h4 style="color: var(--primary); margin-bottom: 10px;">📌 Pflichtfelder:</h4>
                <ul style="margin-left: 20px; color: var(--text-muted); line-height: 1.8;">
                    <li><strong>frage</strong> - Die Frage</li>
                    <li><strong>antwort_a, b, c, d</strong> - 4 Antworten</li>
                    <li><strong>richtig</strong> - A, B, C oder D</li>
                    <li><strong>schwierigkeit</strong> - 1-5</li>
                    <li><strong>min_alter, max_alter</strong> - z.B. 5-8</li>
                </ul>
            </div>
            <div>
                <h4 style="color: var(--primary); margin-bottom: 10px;">🔍 Auto-Modul-Erkennung:</h4>
                <ul style="margin-left: 20px; color: var(--text-muted); line-height: 1.8;">
                    <li><code>mathematik_*.csv</code> → Mathematik</li>
                    <li><code>englisch_age5-8.csv</code> → Englisch</li>
                    <li><code>physik_questions.csv</code> → Physik</li>
                </ul>
                <p style="margin-top: 10px; font-size: 0.85rem;">
                    Dateiname muss mit Modulnamen beginnen!
                </p>
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="?download_template" class="gen-btn gen-btn-primary">📥 Template herunterladen</a>
        </div>
    </div>
</div>


<script>
// ============================================================================
// TODO-005: Multi-File Drag & Drop Import
// ============================================================================

const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileQueue = document.getElementById('fileQueue');
const fileList = document.getElementById('fileList');
const fileCount = document.getElementById('fileCount');
const startImport = document.getElementById('startImport');
const clearQueue = document.getElementById('clearQueue');
const dryRunCheck = document.getElementById('dryRunCheck');

// Module für manuelle Auswahl
const modules = <?= json_encode($modules) ?>;

// File Queue
let filesToImport = [];

// Tab Switching
document.querySelectorAll('.import-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.import-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).style.display = 'block';
        
        // Load generated files when tab is opened
        if (tab.dataset.tab === 'generated') loadGeneratedFiles();
    });
});

// ============================================================================
// DRAG & DROP
// ============================================================================

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => {
    dropZone.addEventListener(e, ev => { ev.preventDefault(); ev.stopPropagation(); });
});

['dragenter', 'dragover'].forEach(e => {
    dropZone.addEventListener(e, () => dropZone.classList.add('dragover'));
});

['dragleave', 'drop'].forEach(e => {
    dropZone.addEventListener(e, () => dropZone.classList.remove('dragover'));
});

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('drop', e => {
    const files = Array.from(e.dataTransfer.files).filter(f => f.name.endsWith('.csv'));
    if (files.length > 0) addFilesToQueue(files);
});

fileInput.addEventListener('change', () => {
    const files = Array.from(fileInput.files);
    if (files.length > 0) addFilesToQueue(files);
    fileInput.value = ''; // Reset for re-selection
});

// ============================================================================
// FILE QUEUE MANAGEMENT
// ============================================================================

async function addFilesToQueue(files) {
    for (const file of files) {
        // Skip duplicates
        if (filesToImport.find(f => f.file.name === file.name)) continue;
        
        // Detect module
        const response = await fetch(`?api=detect_module&filename=${encodeURIComponent(file.name)}`);
        const detection = await response.json();
        
        filesToImport.push({
            file: file,
            module: detection.module,
            moduleName: detection.module_name,
            moduleIcon: detection.module_icon,
            detected: detection.detected,
            status: 'pending' // pending, importing, success, error
        });
    }
    
    renderFileQueue();
}

function renderFileQueue() {
    if (filesToImport.length === 0) {
        fileQueue.style.display = 'none';
        return;
    }
    
    fileQueue.style.display = 'block';
    fileCount.textContent = filesToImport.length;
    
    fileList.innerHTML = filesToImport.map((item, index) => `
        <div class="file-item ${item.status}" data-index="${index}">
            <div class="file-icon">${item.moduleIcon || '📄'}</div>
            <div class="file-details">
                <div class="file-name">${item.file.name}</div>
                <div class="file-meta">${formatFileSize(item.file.size)}</div>
                ${item.status === 'importing' ? '<div class="file-progress"><div class="file-progress-bar" style="width: 50%;"></div></div>' : ''}
            </div>
            <span class="file-module ${item.detected ? '' : 'unknown'}">
                ${item.detected ? item.moduleName : '❓ Nicht erkannt'}
            </span>
            ${!item.detected && item.status === 'pending' ? `
                <select class="gen-select" style="width: 150px;" onchange="setModule(${index}, this.value)">
                    <option value="">Modul wählen...</option>
                    ${Object.entries(modules).map(([key, m]) => `<option value="${key}">${m.icon} ${m.name}</option>`).join('')}
                </select>
            ` : ''}
            ${item.status === 'success' ? `
                <div class="file-result">
                    <div class="file-result-stat imported">✅ ${item.result.imported} → <strong>${item.moduleName}</strong></div>
                    <div class="file-result-stat duplicates">⚠️ ${item.result.duplicates} Duplikate</div>
                </div>
            ` : ''}
            ${item.status === 'error' ? `
                <div class="file-result">
                    <div class="file-result-stat errors">❌ ${item.error}</div>
                </div>
            ` : ''}
            ${item.status === 'pending' ? `
                <div class="file-actions">
                    <button class="file-btn file-btn-remove" onclick="removeFromQueue(${index})">✕</button>
                </div>
            ` : ''}
        </div>
    `).join('');
    
    // Enable/disable import button
    const canImport = filesToImport.some(f => f.status === 'pending' && f.module);
    startImport.disabled = !canImport;
}

function setModule(index, module) {
    if (filesToImport[index]) {
        filesToImport[index].module = module;
        filesToImport[index].moduleName = modules[module]?.name || '';
        filesToImport[index].moduleIcon = modules[module]?.icon || '📄';
        filesToImport[index].detected = !!module;
        renderFileQueue();
    }
}

function removeFromQueue(index) {
    filesToImport.splice(index, 1);
    renderFileQueue();
}

clearQueue.addEventListener('click', () => {
    filesToImport = [];
    renderFileQueue();
    document.getElementById('importSummary').style.display = 'none';
});

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}


// ============================================================================
// IMPORT PROCESS
// ============================================================================

startImport.addEventListener('click', async () => {
    const dryRun = dryRunCheck.checked;
    const toImport = filesToImport.filter(f => f.status === 'pending' && f.module);
    
    if (toImport.length === 0) return;
    
    startImport.disabled = true;
    startImport.textContent = '⏳ Importiere...';
    
    let totalStats = { total: 0, imported: 0, duplicates: 0, errors: 0 };
    
    for (const item of toImport) {
        const index = filesToImport.indexOf(item);
        item.status = 'importing';
        renderFileQueue();
        
        try {
            const formData = new FormData();
            formData.append('csv_file', item.file);
            formData.append('module', item.module);
            formData.append('dry_run', dryRun ? '1' : '0');
            
            const response = await fetch('?api=import_single', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                item.status = 'success';
                item.result = result;
                totalStats.total += result.total;
                totalStats.imported += result.imported;
                totalStats.duplicates += result.duplicates;
                totalStats.errors += result.errors;
            } else {
                item.status = 'error';
                item.error = result.error || 'Import fehlgeschlagen';
            }
        } catch (e) {
            item.status = 'error';
            item.error = 'Netzwerkfehler';
        }
        
        renderFileQueue();
    }
    
    // Show summary
    const moduleSummary = filesToImport
        .filter(f => f.status === 'success' && f.result?.imported > 0)
        .map(f => `${f.moduleIcon} ${f.moduleName}: ${f.result.imported}`)
        .join(' | ');
    
    document.getElementById('summaryTotal').textContent = totalStats.total;
    document.getElementById('summaryImported').textContent = totalStats.imported;
    document.getElementById('summaryDuplicates').textContent = totalStats.duplicates;
    document.getElementById('summaryErrors').textContent = totalStats.errors;
    
    // Module-Übersicht hinzufügen
    const summaryDiv = document.getElementById('importSummary');
    let moduleInfo = summaryDiv.querySelector('.module-import-info');
    if (!moduleInfo) {
        moduleInfo = document.createElement('div');
        moduleInfo.className = 'module-import-info';
        moduleInfo.style.cssText = 'grid-column: 1 / -1; text-align: center; margin-top: 10px; padding: 10px; background: rgba(26,53,3,0.05); border-radius: 8px; font-size: 0.9rem;';
        summaryDiv.appendChild(moduleInfo);
    }
    moduleInfo.innerHTML = moduleSummary ? `<strong>📦 Module:</strong> ${moduleSummary}` : '';
    
    document.getElementById('importSummary').style.display = 'grid';
    
    startImport.disabled = false;
    startImport.textContent = '🚀 Import starten';
});

// ============================================================================
// GENERATED FILES
// ============================================================================

async function loadGeneratedFiles() {
    const container = document.getElementById('generatedFilesList');
    container.innerHTML = '<p>Lade...</p>';
    
    try {
        const response = await fetch('?api=list_generated');
        const data = await response.json();
        
        if (data.files.length === 0) {
            container.innerHTML = `
                <div class="gen-alert gen-alert-info">
                    Keine generierten CSV-Dateien gefunden. 
                    Nutze den <a href="/questions/generate_module_csv.php">CSV Generator</a> um welche zu erstellen.
                </div>
            `;
            return;
        }
        
        container.innerHTML = `
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
                ${data.files.slice(0, 30).map(file => `
                    <div class="file-item" style="cursor: pointer;" onclick="importGeneratedFile('${file.path}', '${file.module}')">
                        <div class="file-icon">${file.module_icon}</div>
                        <div class="file-details">
                            <div class="file-name">${file.module_name}</div>
                            <div class="file-meta">${file.filename}</div>
                            <div class="file-meta">${file.rows} Fragen • ${formatFileSize(file.size)}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    } catch (e) {
        container.innerHTML = '<div class="gen-alert gen-alert-danger">Fehler beim Laden</div>';
    }
}

async function importGeneratedFile(filepath, module) {
    if (!confirm(`Datei in Modul "${module}" importieren?`)) return;
    
    try {
        const formData = new FormData();
        formData.append('filepath', filepath);
        formData.append('module', module);
        formData.append('dry_run', '0');
        
        const response = await fetch('?api=import_single', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ Import erfolgreich!\n\n${result.imported} importiert\n${result.duplicates} Duplikate\n${result.errors} Fehler`);
            loadGeneratedFiles(); // Refresh list
        } else {
            alert('❌ Fehler: ' + (result.error || 'Unbekannter Fehler'));
        }
    } catch (e) {
        alert('❌ Netzwerkfehler');
    }
}

document.getElementById('refreshGenerated').addEventListener('click', loadGeneratedFiles);
</script>

<?php require_once __DIR__ . '/includes/generator_footer.php'; ?>
