<?php
/**
 * ============================================================================
 * sgiT Education - CSV Fragen Import v1.0
 * ============================================================================
 * 
 * Admin-Tool zum Importieren von Fragen aus CSV-Dateien.
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 04.12.2025
 * ============================================================================
 */

session_start();

// Admin-Check (gleiche Session wie admin_v4.php)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin_v4.php');
    exit;
}

require_once __DIR__ . '/includes/CSVQuestionImporter.php';

$importer = new CSVQuestionImporter();
$message = '';
$messageType = '';
$importResult = null;

// ============================================================================
// ACTIONS
// ============================================================================

// CSV Import
if (isset($_POST['action']) && $_POST['action'] === 'import') {
    try {
        $module = $_POST['module'] ?? '';
        $dryRun = isset($_POST['dry_run']);
        
        if (empty($module)) {
            throw new Exception("Bitte wähle ein Modul aus.");
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Bitte wähle eine CSV-Datei aus.");
        }
        
        $uploadedFile = $_FILES['csv_file']['tmp_name'];
        $importResult = $importer->importFromCSV($uploadedFile, $module, $dryRun);
        
        if ($dryRun) {
            $message = "Validierung abgeschlossen: {$importResult['imported']} von {$importResult['total']} Fragen können importiert werden.";
            $messageType = 'info';
        } else {
            $message = "Import erfolgreich: {$importResult['imported']} Fragen importiert, {$importResult['duplicates']} Duplikate übersprungen.";
            $messageType = 'success';
        }
        
    } catch (Exception $e) {
        $message = "Fehler: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Batch löschen
if (isset($_POST['action']) && $_POST['action'] === 'delete_batch') {
    try {
        $batchId = $_POST['batch_id'] ?? '';
        if (empty($batchId)) {
            throw new Exception("Keine Batch-ID angegeben.");
        }
        
        $deleted = $importer->deleteBatch($batchId);
        $message = "$deleted Fragen aus Batch '$batchId' gelöscht.";
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = "Fehler: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Hashes aktualisieren
if (isset($_POST['action']) && $_POST['action'] === 'update_hashes') {
    $updated = $importer->updateMissingHashes();
    $message = "$updated Fragen mit Hash versehen.";
    $messageType = 'success';
}

// Migration
if (isset($_POST['action']) && $_POST['action'] === 'migrate') {
    $migrated = $importer->migrateExistingQuestions();
    $message = "$migrated bestehende Fragen migriert (source-Feld gesetzt).";
    $messageType = 'success';
}

// Daten laden
$batches = $importer->getImportBatches();
$sourceStats = $importer->getStatsBySource();

// Module-Liste
$modules = [
    'mathematik' => '🔢 Mathematik',
    'lesen' => '📖 Lesen',
    'englisch' => '🇬🇧 Englisch',
    'wissenschaft' => '🔬 Wissenschaft',
    'erdkunde' => '🌍 Erdkunde',
    'chemie' => '⚗️ Chemie',
    'physik' => '⚛️ Physik',
    'kunst' => '🎨 Kunst',
    'musik' => '🎵 Musik',
    'computer' => '💻 Computer',
    'bitcoin' => '₿ Bitcoin',
    'geschichte' => '📚 Geschichte',
    'biologie' => '🧬 Biologie',
    'steuern' => '💰 Steuern',
    'programmieren' => '👨‍💻 Programmieren',
    'verkehr' => '🚗 Verkehr'
];
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
        
        .header a {
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 5px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .message.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .message.info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary);
        }
        
        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            border-color: var(--accent);
            outline: none;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: var(--primary);
            color: white;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        .badge-ai { background: #17a2b8; color: white; }
        .badge-csv { background: #28a745; color: white; }
        .badge-manual { background: #ffc107; color: #333; }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .csv-format {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        
        .error-list {
            max-height: 200px;
            overflow-y: auto;
            background: #fff0f0;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .error-list li {
            margin: 5px 0;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📥 CSV Fragen Import</h1>
        <a href="admin_v4.php">← Zurück zum Dashboard</a>
    </div>
    
    <?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
        
        <?php if ($importResult && !empty($importResult['error_messages'])): ?>
        <div class="error-list">
            <strong>Fehler-Details:</strong>
            <ul>
                <?php foreach ($importResult['error_messages'] as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Statistiken -->
    <div class="card">
        <h2>📊 Fragen nach Quelle</h2>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value"><?php echo $sourceStats['ai_generated'] ?? 0; ?></div>
                <div class="stat-label"><span class="badge badge-ai">AI-generiert</span></div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $sourceStats['csv_import'] ?? 0; ?></div>
                <div class="stat-label"><span class="badge badge-csv">CSV-Import</span></div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $sourceStats['manual'] ?? 0; ?></div>
                <div class="stat-label"><span class="badge badge-manual">Manuell</span></div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo array_sum($sourceStats); ?></div>
                <div class="stat-label">Gesamt</div>
            </div>
        </div>
    </div>
    
    <!-- Import-Formular -->
    <div class="card">
        <h2>📤 CSV-Datei importieren</h2>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">
            
            <div class="form-group">
                <label>Modul auswählen:</label>
                <select name="module" required>
                    <option value="">-- Modul wählen --</option>
                    <?php foreach ($modules as $key => $name): ?>
                    <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>CSV-Datei:</label>
                <input type="file" name="csv_file" accept=".csv" required>
                <p class="help-text">UTF-8 kodiert, Semikolon (;) als Trennzeichen</p>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" name="dry_run" id="dry_run">
                <label for="dry_run" style="margin-bottom: 0;">Nur validieren (kein Import)</label>
            </div>
            
            <button type="submit" class="btn btn-primary">📥 Importieren</button>
        </form>
        
        <h3 style="margin-top: 25px; margin-bottom: 10px;">CSV-Format:</h3>
        <div class="csv-format">
frage;antwort_a;antwort_b;antwort_c;antwort_d;richtig;schwierigkeit;min_alter;max_alter;typ;erklaerung;bild_url
"3 + 5 = ?";"7";"8";"9";"6";"B";"1";"5";"8";"addition";"3 + 5 = 8";""
        </div>
        
        <p class="help-text" style="margin-top: 10px;">
            <strong>Pflichtfelder:</strong> frage, antwort_a-d, richtig (A/B/C/D), schwierigkeit (1-5), min_alter, max_alter (5-21)<br>
            <strong>Optional:</strong> typ, erklaerung, bild_url
        </p>
        
        <p style="margin-top: 15px;">
            <a href="docs/csv_import_template.csv" download class="btn btn-secondary">📋 Leere Vorlage herunterladen</a>
            <a href="docs/mathe_addition_subtraktion.csv" download class="btn btn-secondary">🔢 Mathe-Beispiel herunterladen</a>
        </p>
    </div>
    
    <!-- Import-Batches -->
    <div class="card">
        <h2>📦 Importierte Batches</h2>
        
        <?php if (empty($batches)): ?>
        <p style="color: #666;">Noch keine CSV-Imports vorhanden.</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Batch-ID</th>
                <th>Modul</th>
                <th>Fragen</th>
                <th>Importiert am</th>
                <th>Aktion</th>
            </tr>
            <?php foreach ($batches as $batch): ?>
            <tr>
                <td><code><?php echo htmlspecialchars($batch['batch_id']); ?></code></td>
                <td><?php echo htmlspecialchars($batch['module'] ?? '-'); ?></td>
                <td><?php echo $batch['question_count']; ?></td>
                <td><?php echo $batch['imported_at']; ?></td>
                <td>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Wirklich alle <?php echo $batch['question_count']; ?> Fragen aus diesem Batch löschen?');">
                        <input type="hidden" name="action" value="delete_batch">
                        <input type="hidden" name="batch_id" value="<?php echo htmlspecialchars($batch['batch_id']); ?>">
                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">🗑️ Löschen</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    
    <!-- Wartung -->
    <div class="card">
        <h2>🔧 Wartung</h2>
        
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="update_hashes">
                <button type="submit" class="btn btn-secondary">🔑 Fehlende Hashes berechnen</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="migrate">
                <button type="submit" class="btn btn-secondary">📦 Bestehende Fragen migrieren</button>
            </form>
            
            <a href="bug005_analyse.php" class="btn btn-secondary" target="_blank">📊 Fragen-Analyse öffnen</a>
        </div>
        
        <p class="help-text" style="margin-top: 15px;">
            <strong>Hashes berechnen:</strong> Fügt Duplikat-Hashes zu bestehenden Fragen hinzu.<br>
            <strong>Migrieren:</strong> Setzt das source-Feld für alle bestehenden Fragen.
        </p>
    </div>
    
    <!-- Schwierigkeitsstufen -->
    <div class="card">
        <h2>📈 Schwierigkeitsstufen (5-21 Jahre)</h2>
        
        <table>
            <tr>
                <th>Stufe</th>
                <th>Beschreibung</th>
                <th>Typisches Alter</th>
                <th>Schuljahr</th>
            </tr>
            <tr>
                <td><strong>1</strong></td>
                <td>Sehr einfach (Vorschule)</td>
                <td>5-6 Jahre</td>
                <td>Vorschule / 1. Klasse</td>
            </tr>
            <tr>
                <td><strong>2</strong></td>
                <td>Einfach</td>
                <td>6-8 Jahre</td>
                <td>1.-2. Klasse</td>
            </tr>
            <tr>
                <td><strong>3</strong></td>
                <td>Mittel</td>
                <td>8-10 Jahre</td>
                <td>3.-4. Klasse</td>
            </tr>
            <tr>
                <td><strong>4</strong></td>
                <td>Fortgeschritten</td>
                <td>10-14 Jahre</td>
                <td>5.-8. Klasse</td>
            </tr>
            <tr>
                <td><strong>5</strong></td>
                <td>Schwer (Oberstufe/Erwachsen)</td>
                <td>14-21 Jahre</td>
                <td>9. Klasse - Uni</td>
            </tr>
        </table>
    </div>
</body>
</html>
