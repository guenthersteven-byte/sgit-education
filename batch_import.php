<?php
/**
 * ============================================================================
 * sgiT Education - Batch CSV Import v2.0
 * ============================================================================
 * 
 * Importiert CSV-Dateien mit Fragen in die questions.db.
 * 
 * v2.0 ÄNDERUNGEN (03.12.2025) - BUG-008 FIX:
 * - NEU: CSV-Upload Funktion
 * - NEU: Template-Download
 * - NEU: Vorschau vor Import
 * - NEU: Einzeldatei-Import mit Modul-Auswahl
 * - VERBESSERT: Tabs für verschiedene Import-Modi
 * 
 * Features:
 * - Upload eigener CSV-Dateien
 * - Automatisches Mapping CSV → Modul
 * - Dry-Run Option (nur validieren)
 * - Detaillierte Fortschrittsanzeige
 * - Template-Download mit Beispieldaten
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 2.0
 * @date 03.12.2025
 * ============================================================================
 */

session_start();

// Admin-Check
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin_v4.php');
    exit;
}

require_once __DIR__ . '/includes/CSVQuestionImporter.php';

// ============================================================================
// KONFIGURATION
// ============================================================================
$uploadDir = __DIR__ . '/uploads/csv/';
$docsPath = __DIR__ . '/docs/';

// Upload-Verzeichnis erstellen falls nicht vorhanden
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

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
    'steuern' => ['icon' => '💰', 'name' => 'Finanzen/Steuern'],
    'programmieren' => ['icon' => '👨‍💻', 'name' => 'Programmieren'],
    'verkehr' => ['icon' => '🚗', 'name' => 'Verkehr'],
    'unnuetzes_wissen' => ['icon' => '🤯', 'name' => 'Unnützes Wissen'],
    'sport' => ['icon' => '🏃', 'name' => 'Sport']
];

// CSV-Mapping für Batch-Import aus docs/
$csvMapping = [
    'mathe_addition_subtraktion.csv' => 'mathematik',
    'lesen_grundlagen.csv' => 'lesen',
    'englisch_grundlagen.csv' => 'englisch',
    'wissenschaft_grundlagen.csv' => 'wissenschaft',
    'erdkunde_grundlagen.csv' => 'erdkunde',
    'chemie_grundlagen.csv' => 'chemie',
    'physik_grundlagen.csv' => 'physik',
    'kunst_grundlagen.csv' => 'kunst',
    'musik_grundlagen.csv' => 'musik',
    'computer_grundlagen.csv' => 'computer',
    'bitcoin_grundlagen.csv' => 'bitcoin',
    'geschichte_grundlagen.csv' => 'geschichte',
    'biologie_grundlagen.csv' => 'biologie',
    'finanzen_grundlagen.csv' => 'steuern',
    'programmieren_grundlagen.csv' => 'programmieren'
];

// ============================================================================
// TEMPLATE DOWNLOAD
// ============================================================================
if (isset($_GET['download_template'])) {
    $template = "frage;antwort_a;antwort_b;antwort_c;antwort_d;richtig;schwierigkeit;min_alter;max_alter;erklaerung;typ
Was ist 2 + 2?;3;4;5;6;B;1;5;8;2 + 2 ergibt 4, weil man zwei Einheiten zu zwei anderen hinzufügt.;basic
Wie viele Tage hat eine Woche?;5;6;7;8;C;1;5;10;Eine Woche hat immer 7 Tage.;basic
Was ist die Hauptstadt von Deutschland?;München;Hamburg;Berlin;Frankfurt;C;2;8;15;Berlin ist seit der Wiedervereinigung 1990 die Hauptstadt Deutschlands.;geography
";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sgit_fragen_template.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM für Excel
    echo $template;
    exit;
}

// ============================================================================
// VERARBEITUNG
// ============================================================================
$results = [];
$message = null;
$messageType = 'info';
$previewData = null;
$totalImported = 0;
$totalDuplicates = 0;
$totalErrors = 0;
$totalQuestions = 0;

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$activeTab = $_POST['tab'] ?? $_GET['tab'] ?? 'upload';

// --- UPLOAD & PREVIEW ---
if ($action === 'upload_preview' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $module = $_POST['module'] ?? '';
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = '❌ Upload-Fehler: ' . $file['error'];
        $messageType = 'error';
    } elseif (!in_array($module, array_keys($modules))) {
        $message = '❌ Bitte wähle ein gültiges Modul!';
        $messageType = 'error';
    } else {
        // Datei speichern
        $filename = 'upload_' . date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $file['name']);
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Preview generieren
            $previewData = [
                'filepath' => $filepath,
                'filename' => $filename,
                'module' => $module,
                'rows' => []
            ];
            
            // CSV lesen
            $content = file_get_contents($filepath);
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // BOM entfernen
            $lines = explode("\n", $content);
            $header = null;
            
            foreach ($lines as $lineNum => $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                if ($header === null) {
                    $header = str_getcsv($line, ';');
                    $header = array_map('trim', $header);
                    $previewData['header'] = $header;
                    continue;
                }
                
                $values = str_getcsv($line, ';');
                if (count($values) === count($header)) {
                    $previewData['rows'][] = array_combine($header, $values);
                }
                
                // Max 10 Zeilen für Preview
                if (count($previewData['rows']) >= 10) break;
            }
            
            $previewData['total_rows'] = count($lines) - 1; // -1 für Header
            $activeTab = 'preview';
        } else {
            $message = '❌ Fehler beim Speichern der Datei!';
            $messageType = 'error';
        }
    }
}

// --- IMPORT NACH PREVIEW ---
if ($action === 'import_uploaded') {
    $filepath = $_POST['filepath'] ?? '';
    $module = $_POST['module'] ?? '';
    $dryRun = isset($_POST['dry_run']);
    
    if (file_exists($filepath) && in_array($module, array_keys($modules))) {
        try {
            $importer = new CSVQuestionImporter();
            $result = $importer->importFromCSV($filepath, $module, $dryRun);
            
            $results[] = [
                'file' => basename($filepath),
                'module' => $module,
                'status' => 'success',
                'imported' => $result['imported'],
                'duplicates' => $result['duplicates'],
                'errors' => $result['errors'],
                'total' => $result['total'],
                'error_messages' => $result['error_messages'] ?? [],
                'batch_id' => $result['batch_id'] ?? ''
            ];
            
            $totalImported = $result['imported'];
            $totalDuplicates = $result['duplicates'];
            $totalErrors = $result['errors'];
            $totalQuestions = $result['total'];
            
            // Datei nach Import löschen
            if (!$dryRun) {
                unlink($filepath);
            }
            
            $activeTab = 'results';
        } catch (Exception $e) {
            $message = '❌ Import-Fehler: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = '❌ Datei oder Modul ungültig!';
        $messageType = 'error';
    }
}

// --- BATCH IMPORT (alle docs/ CSVs) ---
if ($action === 'batch_import') {
    $dryRun = isset($_POST['dry_run']);
    $importer = new CSVQuestionImporter();
    
    foreach ($csvMapping as $csvFile => $module) {
        $csvPath = $docsPath . $csvFile;
        
        $result = [
            'file' => $csvFile,
            'module' => $module,
            'status' => 'pending',
            'imported' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'total' => 0,
            'error_messages' => []
        ];
        
        if (!file_exists($csvPath)) {
            $result['status'] = 'missing';
            $result['error_messages'][] = "Datei nicht gefunden";
            $results[] = $result;
            continue;
        }
        
        try {
            $importResult = $importer->importFromCSV($csvPath, $module, $dryRun);
            
            $result['status'] = 'success';
            $result['imported'] = $importResult['imported'];
            $result['duplicates'] = $importResult['duplicates'];
            $result['errors'] = $importResult['errors'];
            $result['total'] = $importResult['total'];
            $result['error_messages'] = $importResult['error_messages'] ?? [];
            $result['batch_id'] = $importResult['batch_id'] ?? '';
            
            $totalImported += $importResult['imported'];
            $totalDuplicates += $importResult['duplicates'];
            $totalErrors += $importResult['errors'];
            $totalQuestions += $importResult['total'];
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['error_messages'][] = $e->getMessage();
            $totalErrors++;
        }
        
        $results[] = $result;
    }
    
    $activeTab = 'results';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Import - sgiT Education Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --bitcoin: #F7931A;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .header {
            background: var(--primary);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 { font-size: 24px; }
        
        .header-links {
            display: flex;
            gap: 10px;
        }
        
        .header a {
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .header a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 12px 25px;
            background: white;
            border: none;
            border-radius: 10px 10px 0 0;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab:hover {
            background: #e9ecef;
        }
        
        .tab.active {
            background: var(--accent);
            color: white;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent);
        }
        
        .card h3 {
            color: var(--primary);
            margin: 20px 0 10px;
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            border-color: var(--accent);
            outline: none;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary);
        }
        
        .btn-warning {
            background: var(--warning);
            color: #333;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-info {
            background: var(--info);
            color: white;
        }
        
        .btn-large {
            padding: 18px 35px;
            font-size: 18px;
        }
        
        /* Upload Zone */
        .upload-zone {
            border: 3px dashed #ccc;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            background: #fafafa;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: var(--accent);
            background: #f0fff0;
        }
        
        .upload-zone .icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .upload-zone p {
            color: #666;
            margin: 10px 0;
        }
        
        .upload-zone input[type="file"] {
            display: none;
        }
        
        /* Info Boxes */
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .success-box {
            background: #d4edda;
            border: 1px solid #28a745;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .error-box {
            background: #f8d7da;
            border: 1px solid #dc3545;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* Preview Table */
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 15px;
        }
        
        .preview-table th,
        .preview-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .preview-table th {
            background: var(--primary);
            color: white;
            font-size: 12px;
        }
        
        .preview-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .preview-table tr:hover {
            background: #e9ecef;
        }
        
        /* Results Table */
        .results-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .results-table th,
        .results-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .results-table th {
            background: var(--primary);
            color: white;
        }
        
        .results-table tr:hover {
            background: #f8f9fa;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-success { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-missing { background: #fff3cd; color: #856404; }
        .status-pending { background: #e2e3e5; color: #383d41; }
        
        /* Summary */
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .summary-box {
            background: linear-gradient(135deg, var(--primary), #2d5a08);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }
        
        .summary-box.success { background: linear-gradient(135deg, var(--success), #1e7e34); }
        .summary-box.warning { background: linear-gradient(135deg, var(--warning), #d39e00); color: #333; }
        .summary-box.danger { background: linear-gradient(135deg, var(--danger), #bd2130); }
        
        .summary-value {
            font-size: 42px;
            font-weight: bold;
        }
        
        .summary-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* File List */
        .file-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .file-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            border-left: 4px solid var(--accent);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .file-item.missing {
            border-left-color: var(--danger);
            background: #fff0f0;
        }
        
        .file-item .icon {
            font-size: 24px;
        }
        
        .file-item .details {
            flex: 1;
        }
        
        .file-item .module {
            font-weight: 600;
            color: var(--primary);
        }
        
        .file-item .filename {
            font-size: 11px;
            color: #666;
            font-family: monospace;
        }
        
        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        
        /* Template Info */
        .template-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .template-info h4 {
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .template-info code {
            display: block;
            background: #1A3503;
            color: #43D240;
            padding: 15px;
            border-radius: 8px;
            font-size: 12px;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .template-info ul {
            margin: 10px 0 10px 20px;
        }
        
        .template-info li {
            margin: 5px 0;
            color: #555;
        }
        
        /* Error List */
        .error-list {
            background: #fff0f0;
            border-radius: 5px;
            padding: 10px;
            font-size: 12px;
            max-height: 100px;
            overflow-y: auto;
            list-style: none;
        }
        
        .error-list li {
            color: var(--danger);
            margin: 3px 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .summary {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .tabs {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 CSV Fragen-Import</h1>
        <div class="header-links">
            <a href="?download_template">📥 Template</a>
            <a href="admin_v4.php">← Dashboard</a>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="<?php echo $messageType === 'error' ? 'error-box' : ($messageType === 'success' ? 'success-box' : 'info-box'); ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <!-- TABS -->
    <div class="tabs">
        <button class="tab <?php echo $activeTab === 'upload' ? 'active' : ''; ?>" onclick="location.href='?tab=upload'">
            📤 CSV Hochladen
        </button>
        <button class="tab <?php echo $activeTab === 'batch' ? 'active' : ''; ?>" onclick="location.href='?tab=batch'">
            📦 Batch Import (docs/)
        </button>
        <?php if ($activeTab === 'preview'): ?>
        <button class="tab active">👁️ Vorschau</button>
        <?php endif; ?>
        <?php if ($activeTab === 'results'): ?>
        <button class="tab active">✅ Ergebnisse</button>
        <?php endif; ?>
    </div>
    
    <!-- TAB: UPLOAD -->
    <?php if ($activeTab === 'upload'): ?>
    <div class="card">
        <h2>📤 CSV-Datei hochladen</h2>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="action" value="upload_preview">
            <input type="hidden" name="tab" value="upload">
            
            <div class="form-group">
                <label>📁 Modul auswählen</label>
                <select name="module" required>
                    <option value="">-- Bitte wählen --</option>
                    <?php foreach ($modules as $key => $mod): ?>
                    <option value="<?php echo $key; ?>"><?php echo $mod['icon'] . ' ' . $mod['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('csvFile').click();">
                <div class="icon">📄</div>
                <p><strong>CSV-Datei hier ablegen</strong></p>
                <p>oder klicken zum Auswählen</p>
                <p style="font-size: 12px; color: #999;">Format: CSV mit Semikolon-Trennung (;)</p>
                <input type="file" name="csv_file" id="csvFile" accept=".csv" required>
            </div>
            
            <p id="selectedFile" style="margin: 10px 0; font-weight: 600; color: var(--primary);"></p>
            
            <button type="submit" class="btn btn-primary btn-large">
                👁️ Vorschau anzeigen
            </button>
        </form>
        
        <div class="template-info">
            <h4>📋 CSV-Format</h4>
            <code>frage;antwort_a;antwort_b;antwort_c;antwort_d;richtig;schwierigkeit;min_alter;max_alter;erklaerung;typ</code>
            
            <h4 style="margin-top: 15px;">Pflichtfelder:</h4>
            <ul>
                <li><strong>frage</strong> - Die Frage</li>
                <li><strong>antwort_a, b, c, d</strong> - Die 4 Antwortmöglichkeiten</li>
                <li><strong>richtig</strong> - Buchstabe der richtigen Antwort (A, B, C oder D)</li>
                <li><strong>schwierigkeit</strong> - 1-5 (1=sehr leicht, 5=sehr schwer)</li>
                <li><strong>min_alter, max_alter</strong> - Altersbereich 5-21</li>
            </ul>
            
            <h4 style="margin-top: 15px;">Optionale Felder:</h4>
            <ul>
                <li><strong>erklaerung</strong> - Erklärung zur richtigen Antwort</li>
                <li><strong>typ</strong> - Fragentyp (z.B. basic, advanced)</li>
            </ul>
            
            <p style="margin-top: 15px;">
                <a href="?download_template" class="btn btn-info">📥 Template herunterladen</a>
            </p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- TAB: PREVIEW -->
    <?php if ($activeTab === 'preview' && $previewData): ?>
    <div class="card">
        <h2>👁️ Vorschau: <?php echo htmlspecialchars($previewData['filename']); ?></h2>
        
        <div class="info-box">
            <strong>Modul:</strong> <?php echo $modules[$previewData['module']]['icon'] . ' ' . $modules[$previewData['module']]['name']; ?><br>
            <strong>Zeilen gefunden:</strong> <?php echo $previewData['total_rows']; ?> Fragen (zeige max. 10)
        </div>
        
        <div style="overflow-x: auto;">
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <?php foreach (['frage', 'antwort_a', 'antwort_b', 'antwort_c', 'antwort_d', 'richtig', 'schwierigkeit', 'min_alter', 'max_alter'] as $col): ?>
                        <th><?php echo $col; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewData['rows'] as $i => $row): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <?php foreach (['frage', 'antwort_a', 'antwort_b', 'antwort_c', 'antwort_d', 'richtig', 'schwierigkeit', 'min_alter', 'max_alter'] as $col): ?>
                        <td><?php echo htmlspecialchars(substr($row[$col] ?? '', 0, 50)); ?><?php echo strlen($row[$col] ?? '') > 50 ? '...' : ''; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <form method="POST" style="margin-top: 20px;">
            <input type="hidden" name="action" value="import_uploaded">
            <input type="hidden" name="filepath" value="<?php echo htmlspecialchars($previewData['filepath']); ?>">
            <input type="hidden" name="module" value="<?php echo htmlspecialchars($previewData['module']); ?>">
            
            <div class="checkbox-group">
                <input type="checkbox" name="dry_run" id="dry_run">
                <label for="dry_run"><strong>Dry-Run:</strong> Nur validieren, NICHT importieren</label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-large">
                ✅ Jetzt importieren (<?php echo $previewData['total_rows']; ?> Fragen)
            </button>
            <a href="?tab=upload" class="btn btn-danger">❌ Abbrechen</a>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- TAB: BATCH -->
    <?php if ($activeTab === 'batch'): ?>
    <div class="card">
        <h2>📦 Batch Import aus /docs/</h2>
        
        <p style="margin-bottom: 15px;">
            Importiert alle vorbereiteten CSV-Dateien aus dem <code>/docs/</code> Verzeichnis.
        </p>
        
        <h3>📋 Verfügbare CSV-Dateien (<?php echo count($csvMapping); ?>)</h3>
        
        <div class="file-list">
            <?php foreach ($csvMapping as $csvFile => $module): ?>
                <?php 
                    $exists = file_exists($docsPath . $csvFile);
                    $icon = $modules[$module]['icon'] ?? '📄';
                ?>
                <div class="file-item <?php echo $exists ? '' : 'missing'; ?>">
                    <span class="icon"><?php echo $icon; ?></span>
                    <div class="details">
                        <div class="module"><?php echo $modules[$module]['name'] ?? ucfirst($module); ?></div>
                        <div class="filename"><?php echo $csvFile; ?></div>
                        <?php if (!$exists): ?>
                        <span class="status-badge status-missing">FEHLT</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="warning-box" style="margin-top: 20px;">
            <strong>⚠️ Hinweis:</strong> Duplikate werden automatisch übersprungen (Hash-Vergleich).
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="batch_import">
            <input type="hidden" name="tab" value="batch">
            
            <div class="checkbox-group">
                <input type="checkbox" name="dry_run" id="dry_run_batch">
                <label for="dry_run_batch"><strong>Dry-Run:</strong> Nur validieren, NICHT importieren</label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-large">
                📦 Alle <?php echo count($csvMapping); ?> CSVs importieren
            </button>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- TAB: RESULTS -->
    <?php if ($activeTab === 'results' && !empty($results)): ?>
    
    <div class="summary">
        <div class="summary-box">
            <div class="summary-value"><?php echo $totalQuestions; ?></div>
            <div class="summary-label">Fragen geprüft</div>
        </div>
        <div class="summary-box success">
            <div class="summary-value"><?php echo $totalImported; ?></div>
            <div class="summary-label"><?php echo isset($_POST['dry_run']) ? 'Bereit zum Import' : 'Importiert'; ?></div>
        </div>
        <div class="summary-box warning">
            <div class="summary-value"><?php echo $totalDuplicates; ?></div>
            <div class="summary-label">Duplikate übersprungen</div>
        </div>
        <div class="summary-box danger">
            <div class="summary-value"><?php echo $totalErrors; ?></div>
            <div class="summary-label">Fehler</div>
        </div>
    </div>
    
    <?php if (isset($_POST['dry_run'])): ?>
    <div class="warning-box">
        <strong>🔍 DRY-RUN:</strong> Es wurden keine Fragen importiert. Dies war nur eine Validierung.
    </div>
    <?php else: ?>
    <div class="success-box">
        <strong>✅ Import abgeschlossen!</strong> <?php echo $totalImported; ?> neue Fragen wurden importiert.
    </div>
    <?php endif; ?>
    
    <div class="card">
        <h2>📊 Ergebnisse</h2>
        
        <table class="results-table">
            <thead>
                <tr>
                    <th>Modul</th>
                    <th>Datei</th>
                    <th>Status</th>
                    <th>Importiert</th>
                    <th>Duplikate</th>
                    <th>Fehler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                <tr>
                    <td>
                        <span style="font-size: 20px;"><?php echo $modules[$r['module']]['icon'] ?? '📄'; ?></span>
                        <strong><?php echo $modules[$r['module']]['name'] ?? ucfirst($r['module']); ?></strong>
                    </td>
                    <td><code><?php echo htmlspecialchars($r['file']); ?></code></td>
                    <td>
                        <?php
                        $statusClass = 'status-' . $r['status'];
                        $statusText = [
                            'success' => '✅ OK',
                            'error' => '❌ Fehler',
                            'missing' => '⚠️ Fehlt',
                            'pending' => '⏳'
                        ][$r['status']] ?? $r['status'];
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </td>
                    <td style="color: var(--success); font-weight: bold;">
                        <?php echo $r['imported']; ?> / <?php echo $r['total']; ?>
                    </td>
                    <td style="color: var(--warning);"><?php echo $r['duplicates']; ?></td>
                    <td style="color: var(--danger);">
                        <?php echo $r['errors']; ?>
                        <?php if (!empty($r['error_messages'])): ?>
                        <ul class="error-list">
                            <?php foreach (array_slice($r['error_messages'], 0, 3) as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h2>🔄 Weiter?</h2>
        <a href="?tab=upload" class="btn btn-primary">📤 Weitere CSV hochladen</a>
        <a href="?tab=batch" class="btn btn-warning">📦 Batch Import</a>
        <a href="adaptive_learning.php" class="btn btn-info">🧪 Testen</a>
        <a href="admin_v4.php" class="btn btn-primary">← Dashboard</a>
    </div>
    
    <?php endif; ?>
    
    <script>
    // Drag & Drop für Upload-Zone
    const uploadZone = document.getElementById('uploadZone');
    const csvFile = document.getElementById('csvFile');
    const selectedFile = document.getElementById('selectedFile');
    
    if (uploadZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            });
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadZone.addEventListener(eventName, () => uploadZone.classList.add('dragover'));
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, () => uploadZone.classList.remove('dragover'));
        });
        
        uploadZone.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].name.endsWith('.csv')) {
                csvFile.files = files;
                selectedFile.textContent = '📄 ' + files[0].name;
            }
        });
        
        csvFile.addEventListener('change', () => {
            if (csvFile.files.length > 0) {
                selectedFile.textContent = '📄 ' + csvFile.files[0].name;
            }
        });
    }
    </script>
</body>
</html>
