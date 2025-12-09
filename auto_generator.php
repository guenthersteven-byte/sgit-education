<?php
/**
 * ============================================================================
 * sgiT Education - Auto-Generator v1.0 (TODO-007)
 * ============================================================================
 * 
 * ZEITGESTEUERTER AUTO-GENERATOR für alle Module
 * 
 * Features:
 * - Ein-Klick-Start für alle 18 Quiz-Module
 * - Konfigurierbare Zeitlimits (1h, 2h, 3h, 4h, 12h, 24h)
 * - Konfigurierbare Fragen pro Modul
 * - Auto-Rotation durch alle Module
 * - Live Progress-Dashboard
 * - Pause/Resume Funktionalität
 * - Output: Direkt DB oder CSV
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 09.12.2025
 * ============================================================================
 */

session_start();

// Zentrale Versionsverwaltung
require_once __DIR__ . '/includes/version.php';

// ============================================================================
// KONFIGURATION
// ============================================================================

$ollamaUrl = 'http://ollama:11434';
$dbPath = __DIR__ . '/AI/data/questions.db';
$csvOutputDir = __DIR__ . '/questions/generated/';

// Alle Quiz-Module (18 Stück)
$quizModules = [
    'mathematik' => ['icon' => '🔢', 'name' => 'Mathematik'],
    'englisch' => ['icon' => '🇬🇧', 'name' => 'Englisch'],
    'lesen' => ['icon' => '📖', 'name' => 'Lesen'],
    'physik' => ['icon' => '⚛️', 'name' => 'Physik'],
    'erdkunde' => ['icon' => '🌍', 'name' => 'Erdkunde'],
    'wissenschaft' => ['icon' => '🔬', 'name' => 'Wissenschaft'],
    'geschichte' => ['icon' => '📜', 'name' => 'Geschichte'],
    'computer' => ['icon' => '💻', 'name' => 'Computer'],
    'chemie' => ['icon' => '⚗️', 'name' => 'Chemie'],
    'biologie' => ['icon' => '🧬', 'name' => 'Biologie'],
    'musik' => ['icon' => '🎵', 'name' => 'Musik'],
    'programmieren' => ['icon' => '👨‍💻', 'name' => 'Programmieren'],
    'bitcoin' => ['icon' => '₿', 'name' => 'Bitcoin'],
    'finanzen' => ['icon' => '💰', 'name' => 'Finanzen'],
    'kunst' => ['icon' => '🎨', 'name' => 'Kunst'],
    'verkehr' => ['icon' => '🚗', 'name' => 'Verkehr'],
    'sport' => ['icon' => '🏃', 'name' => 'Sport'],
    'unnuetzes_wissen' => ['icon' => '🤯', 'name' => 'Unnützes Wissen']
];

// Zeitlimit-Optionen (in Sekunden)
$timeLimits = [
    3600 => '1 Stunde',
    7200 => '2 Stunden',
    10800 => '3 Stunden',
    14400 => '4 Stunden',
    43200 => '12 Stunden',
    86400 => '24 Stunden'
];

// Fragen pro Modul Optionen
$questionsPerModuleOptions = [5, 10, 15, 20, 25, 30];


// ============================================================================
// HILFSFUNKTIONEN
// ============================================================================

/**
 * Initialisiert oder gibt Session-State zurück
 */
function getSessionState() {
    if (!isset($_SESSION['auto_gen'])) {
        $_SESSION['auto_gen'] = [
            'active' => false,
            'paused' => false,
            'start_time' => 0,
            'end_time' => 0,
            'time_limit' => 3600,
            'questions_per_module' => 10,
            'output_mode' => 'db',
            'current_module_index' => 0,
            'current_module_progress' => 0,
            'modules_completed' => [],
            'module_stats' => [],
            'total_generated' => 0,
            'total_errors' => 0,
            'rounds_completed' => 0,
            'last_error' => null
        ];
    }
    return $_SESSION['auto_gen'];
}

/**
 * Speichert Session-State
 */
function saveSessionState($state) {
    $_SESSION['auto_gen'] = $state;
}

/**
 * Prüft Ollama-Verbindung und Model
 */
function checkOllama($ollamaUrl) {
    $ch = curl_init($ollamaUrl . '/api/tags');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['online' => false, 'model' => null];
    }
    
    $data = json_decode($response, true);
    $models = $data['models'] ?? [];
    
    // Bevorzugt gemma2:2b
    foreach ($models as $m) {
        if (strpos($m['name'], 'gemma2:2b') !== false) {
            return ['online' => true, 'model' => 'gemma2:2b'];
        }
    }
    
    // Fallback auf erstes verfügbares
    if (!empty($models)) {
        return ['online' => true, 'model' => $models[0]['name']];
    }
    
    return ['online' => true, 'model' => null];
}

/**
 * Generiert Fragen für ein Modul via Ollama
 */
function generateQuestions($ollamaUrl, $module, $moduleName, $count, $model) {
    $prompt = "Du bist ein Quiz-Ersteller. Generiere genau {$count} Quiz-Fragen zum Thema \"{$moduleName}\" auf Deutsch.

Format für JEDE Frage (eine pro Zeile):
Q: [Frage]
A: [Richtige Antwort]
W1: [Falsche Antwort 1]
W2: [Falsche Antwort 2]
W3: [Falsche Antwort 3]
E: [Kurze Erklärung warum die Antwort richtig ist]

WICHTIG:
- Fragen müssen lehrreich und interessant sein
- Falsche Antworten müssen plausibel sein
- Keine Umlaute (ae statt ä, oe statt ö, ue statt ü, ss statt ß)
- Erklärung maximal 60 Zeichen

Generiere jetzt {$count} Fragen:";

    $data = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.8,
            'num_predict' => 2000
        ]
    ];

    $ch = curl_init($ollamaUrl . '/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 180
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => $error, 'questions' => []];
    }
    
    $json = json_decode($response, true);
    if (!isset($json['response'])) {
        return ['success' => false, 'error' => 'Keine Antwort', 'questions' => []];
    }
    
    // Parse Questions
    $questions = parseQuestions($json['response']);
    
    return [
        'success' => count($questions) > 0,
        'questions' => $questions,
        'raw_count' => count($questions)
    ];
}

/**
 * Parst AI-Output in Fragen-Array
 */
function parseQuestions($text) {
    $questions = [];
    $lines = explode("\n", $text);
    
    $current = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        if (preg_match('/^Q:\s*(.+)$/i', $line, $m)) {
            if (!empty($current['question'])) {
                $questions[] = $current;
            }
            $current = ['question' => $m[1], 'correct' => '', 'wrong' => [], 'explanation' => ''];
        } elseif (preg_match('/^A:\s*(.+)$/i', $line, $m)) {
            $current['correct'] = $m[1];
        } elseif (preg_match('/^W[123]:\s*(.+)$/i', $line, $m)) {
            $current['wrong'][] = $m[1];
        } elseif (preg_match('/^E:\s*(.+)$/i', $line, $m)) {
            $current['explanation'] = $m[1];
        }
    }
    
    // Letzte Frage hinzufügen
    if (!empty($current['question'])) {
        $questions[] = $current;
    }
    
    // Validieren
    $valid = [];
    foreach ($questions as $q) {
        if (!empty($q['question']) && !empty($q['correct']) && count($q['wrong']) >= 2) {
            $valid[] = $q;
        }
    }
    
    return $valid;
}

/**
 * Speichert Fragen in DB
 */
function saveToDatabase($dbPath, $module, $questions) {
    $db = new SQLite3($dbPath);
    $saved = 0;
    
    foreach ($questions as $q) {
        // Hash für Duplikat-Check
        $allAnswers = array_merge([$q['correct']], $q['wrong']);
        sort($allAnswers);
        $hash = md5(strtolower($q['question']) . '|' . implode('|', array_map('strtolower', $allAnswers)));
        
        // Duplikat-Check
        $stmt = $db->prepare("SELECT id FROM questions WHERE question_hash = :hash");
        $stmt->bindValue(':hash', $hash);
        $result = $stmt->execute();
        if ($result->fetchArray()) {
            continue; // Duplikat
        }
        
        // Antworten mischen
        $options = array_merge([$q['correct']], array_slice($q['wrong'], 0, 3));
        shuffle($options);
        $correctIndex = array_search($q['correct'], $options);
        
        $stmt = $db->prepare("INSERT INTO questions 
            (module, question, answer, options, erklaerung, difficulty, age_min, age_max, 
             ai_generated, question_hash, source, is_active, created_at) 
            VALUES (:module, :question, :answer, :options, :erklaerung, :diff, :min, :max, 
                    1, :hash, 'auto_generator', 1, datetime('now'))");
        
        $stmt->bindValue(':module', $module);
        $stmt->bindValue(':question', $q['question']);
        $stmt->bindValue(':answer', chr(65 + $correctIndex)); // A, B, C, D
        $stmt->bindValue(':options', json_encode($options));
        $stmt->bindValue(':erklaerung', $q['explanation'] ?? '');
        $stmt->bindValue(':diff', 3);
        $stmt->bindValue(':min', 8);
        $stmt->bindValue(':max', 99);
        $stmt->bindValue(':hash', $hash);
        
        if ($stmt->execute()) {
            $saved++;
        }
    }
    
    $db->close();
    return $saved;
}


// ============================================================================
// API ENDPOINTS
// ============================================================================

if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $action = $_GET['api'];
    $state = getSessionState();
    
    // STATUS - Aktuellen Zustand abrufen
    if ($action === 'status') {
        $moduleKeys = array_keys($GLOBALS['quizModules']);
        $currentModule = $moduleKeys[$state['current_module_index']] ?? null;
        
        $timeRemaining = 0;
        if ($state['active'] && !$state['paused'] && $state['end_time'] > 0) {
            $timeRemaining = max(0, $state['end_time'] - time());
        }
        
        echo json_encode([
            'active' => $state['active'],
            'paused' => $state['paused'],
            'time_remaining' => $timeRemaining,
            'time_limit' => $state['time_limit'],
            'current_module' => $currentModule,
            'current_module_index' => $state['current_module_index'],
            'current_module_progress' => $state['current_module_progress'],
            'questions_per_module' => $state['questions_per_module'],
            'modules_completed' => $state['modules_completed'],
            'module_stats' => $state['module_stats'],
            'total_generated' => $state['total_generated'],
            'total_errors' => $state['total_errors'],
            'rounds_completed' => $state['rounds_completed'],
            'last_error' => $state['last_error'],
            'output_mode' => $state['output_mode']
        ]);
        exit;
    }
    
    // START - Neue Session starten
    if ($action === 'start') {
        $timeLimit = intval($_POST['time_limit'] ?? 3600);
        $questionsPerModule = intval($_POST['questions_per_module'] ?? 10);
        $outputMode = $_POST['output_mode'] ?? 'db';
        
        // Validierung
        if (!in_array($timeLimit, array_keys($GLOBALS['timeLimits']))) {
            $timeLimit = 3600;
        }
        if ($questionsPerModule < 5 || $questionsPerModule > 30) {
            $questionsPerModule = 10;
        }
        
        $state = [
            'active' => true,
            'paused' => false,
            'start_time' => time(),
            'end_time' => time() + $timeLimit,
            'time_limit' => $timeLimit,
            'questions_per_module' => $questionsPerModule,
            'output_mode' => $outputMode,
            'current_module_index' => 0,
            'current_module_progress' => 0,
            'modules_completed' => [],
            'module_stats' => [],
            'total_generated' => 0,
            'total_errors' => 0,
            'rounds_completed' => 0,
            'last_error' => null
        ];
        
        saveSessionState($state);
        
        echo json_encode(['success' => true, 'message' => 'Generator gestartet']);
        exit;
    }
    
    // GENERATE - Nächsten Batch generieren
    if ($action === 'generate') {
        if (!$state['active']) {
            echo json_encode(['success' => false, 'error' => 'Generator nicht aktiv']);
            exit;
        }
        
        if ($state['paused']) {
            echo json_encode(['success' => false, 'error' => 'Generator pausiert', 'paused' => true]);
            exit;
        }
        
        // Zeit abgelaufen?
        if (time() >= $state['end_time']) {
            $state['active'] = false;
            saveSessionState($state);
            echo json_encode(['success' => false, 'error' => 'Zeit abgelaufen', 'finished' => true]);
            exit;
        }
        
        $moduleKeys = array_keys($GLOBALS['quizModules']);
        $currentModule = $moduleKeys[$state['current_module_index']];
        $moduleInfo = $GLOBALS['quizModules'][$currentModule];
        
        // Ollama Check
        $ollamaCheck = checkOllama($GLOBALS['ollamaUrl']);
        if (!$ollamaCheck['online'] || !$ollamaCheck['model']) {
            $state['last_error'] = 'Ollama nicht verfügbar';
            $state['total_errors']++;
            saveSessionState($state);
            echo json_encode(['success' => false, 'error' => 'Ollama offline']);
            exit;
        }
        
        // Fragen generieren (Batch von 5)
        $batchSize = min(5, $state['questions_per_module'] - $state['current_module_progress']);
        
        $result = generateQuestions(
            $GLOBALS['ollamaUrl'],
            $currentModule,
            $moduleInfo['name'],
            $batchSize,
            $ollamaCheck['model']
        );
        
        $generated = 0;
        if ($result['success'] && !empty($result['questions'])) {
            if ($state['output_mode'] === 'db') {
                $generated = saveToDatabase($GLOBALS['dbPath'], $currentModule, $result['questions']);
            } else {
                // CSV-Mode: Für später implementieren
                $generated = count($result['questions']);
            }
            
            $state['current_module_progress'] += $generated;
            $state['total_generated'] += $generated;
            
            // Modul-Stats aktualisieren
            if (!isset($state['module_stats'][$currentModule])) {
                $state['module_stats'][$currentModule] = 0;
            }
            $state['module_stats'][$currentModule] += $generated;
        } else {
            $state['last_error'] = $result['error'] ?? 'Generierung fehlgeschlagen';
            $state['total_errors']++;
        }
        
        // Modul fertig?
        if ($state['current_module_progress'] >= $state['questions_per_module']) {
            $state['modules_completed'][] = $currentModule;
            $state['current_module_index']++;
            $state['current_module_progress'] = 0;
            
            // Alle Module durch?
            if ($state['current_module_index'] >= count($moduleKeys)) {
                $state['current_module_index'] = 0;
                $state['rounds_completed']++;
            }
        }
        
        saveSessionState($state);
        
        echo json_encode([
            'success' => true,
            'generated' => $generated,
            'module' => $currentModule,
            'module_progress' => $state['current_module_progress'],
            'total' => $state['total_generated']
        ]);
        exit;
    }
    
    // PAUSE
    if ($action === 'pause') {
        $state['paused'] = true;
        // Pausenzeit merken für Resume
        $state['pause_time'] = time();
        saveSessionState($state);
        echo json_encode(['success' => true, 'message' => 'Pausiert']);
        exit;
    }
    
    // RESUME
    if ($action === 'resume') {
        if (isset($state['pause_time'])) {
            // End-Zeit um Pausendauer verlängern
            $pauseDuration = time() - $state['pause_time'];
            $state['end_time'] += $pauseDuration;
            unset($state['pause_time']);
        }
        $state['paused'] = false;
        saveSessionState($state);
        echo json_encode(['success' => true, 'message' => 'Fortgesetzt']);
        exit;
    }
    
    // STOP
    if ($action === 'stop') {
        $finalStats = [
            'total_generated' => $state['total_generated'],
            'modules_completed' => count($state['modules_completed']),
            'rounds_completed' => $state['rounds_completed'],
            'runtime' => time() - $state['start_time']
        ];
        
        // Reset State
        $_SESSION['auto_gen'] = null;
        unset($_SESSION['auto_gen']);
        
        echo json_encode(['success' => true, 'message' => 'Gestoppt', 'stats' => $finalStats]);
        exit;
    }
    
    // CHECK OLLAMA
    if ($action === 'check_ollama') {
        $check = checkOllama($ollamaUrl);
        echo json_encode($check);
        exit;
    }
    
    echo json_encode(['error' => 'Unbekannte API-Aktion']);
    exit;
}


// ============================================================================
// HTML OUTPUT
// ============================================================================

// Header einbinden
$currentPage = 'auto_generator';
$pageTitle = 'Auto-Generator';

// Check if generator_header exists, otherwise simple header
$headerFile = __DIR__ . '/includes/generator_header.php';
if (file_exists($headerFile)) {
    require_once $headerFile;
} else {
    // Fallback Header
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Auto-Generator</title></head><body>';
}

$ollamaStatus = checkOllama($ollamaUrl);
$sessionState = getSessionState();
?>

<style>
    .auto-gen-container { max-width: 1200px; margin: 0 auto; }
    
    /* Config Panel */
    .config-panel {
        background: var(--card, #fff);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    
    .config-row {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    
    .config-group { flex: 1; min-width: 200px; }
    .config-label { 
        display: block; 
        margin-bottom: 8px; 
        font-weight: 600; 
        color: var(--primary, #1A3503); 
    }
    
    /* Option Buttons */
    .option-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
    .option-btn {
        padding: 10px 18px;
        border: 2px solid var(--border, #e0e0e0);
        background: white;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }
    .option-btn:hover { border-color: var(--accent, #43D240); }
    .option-btn.selected { 
        background: var(--accent, #43D240); 
        border-color: var(--accent, #43D240); 
        color: white; 
    }
    
    /* Status Panel */
    .status-panel {
        background: linear-gradient(135deg, var(--primary, #1A3503), #2d5a08);
        border-radius: 12px;
        padding: 25px;
        color: white;
        margin-bottom: 20px;
    }
    
    .status-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .status-item { text-align: center; }
    .status-value { font-size: 2rem; font-weight: bold; }
    .status-label { font-size: 0.85rem; opacity: 0.8; }
    
    /* Progress Bar */
    .main-progress {
        background: rgba(255,255,255,0.2);
        border-radius: 10px;
        height: 20px;
        overflow: hidden;
        margin-bottom: 15px;
    }
    .main-progress-bar {
        height: 100%;
        background: var(--accent, #43D240);
        transition: width 0.5s;
        border-radius: 10px;
    }
    
    .current-activity {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.1rem;
    }
    .current-activity .spinner {
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    
    /* Module Grid */
    .module-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
        margin-top: 20px;
    }
    
    .module-item {
        background: var(--card, #fff);
        border: 2px solid var(--border, #e0e0e0);
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        transition: all 0.2s;
    }
    .module-item.completed { 
        border-color: #28a745; 
        background: #d4edda; 
    }
    .module-item.active { 
        border-color: var(--accent, #43D240); 
        background: rgba(67,210,64,0.1);
        animation: pulse-border 1s infinite;
    }
    .module-item.pending { 
        opacity: 0.85; 
        border-style: dashed;
    }
    .module-item {
        cursor: default;
        user-select: none;
    }
    
    @keyframes pulse-border {
        0%, 100% { box-shadow: 0 0 0 0 rgba(67,210,64,0.4); }
        50% { box-shadow: 0 0 0 8px rgba(67,210,64,0); }
    }
    
    .module-icon { font-size: 1.8rem; margin-bottom: 5px; }
    .module-name { font-size: 0.85rem; font-weight: 500; }
    .module-progress { font-size: 0.75rem; color: var(--text-muted, #666); margin-top: 5px; }
    
    /* Control Buttons */
    .control-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 25px;
    }
    
    .ctrl-btn {
        padding: 15px 40px;
        font-size: 1.1rem;
        font-weight: bold;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .ctrl-btn-start { background: var(--accent, #43D240); color: white; }
    .ctrl-btn-start:hover { background: #35B035; transform: translateY(-2px); }
    
    .ctrl-btn-pause { background: #ffc107; color: #333; }
    .ctrl-btn-pause:hover { background: #e0a800; }
    
    .ctrl-btn-stop { background: #dc3545; color: white; }
    .ctrl-btn-stop:hover { background: #c82333; }
    
    .ctrl-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
    
    /* Ollama Status */
    .ollama-status {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .ollama-status.online { background: #d4edda; color: #155724; }
    .ollama-status.offline { background: #f8d7da; color: #721c24; }
    .status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    .status-dot.online { background: #28a745; }
    .status-dot.offline { background: #dc3545; }
    
    @media (max-width: 768px) {
        .status-grid { grid-template-columns: repeat(2, 1fr); }
        .config-row { flex-direction: column; }
    }
</style>

<div class="auto-gen-container">
    
    <!-- Ollama Status -->
    <div class="ollama-status <?= $ollamaStatus['online'] ? 'online' : 'offline' ?>">
        <span class="status-dot <?= $ollamaStatus['online'] ? 'online' : 'offline' ?>"></span>
        <span>
            <?php if ($ollamaStatus['online']): ?>
                Ollama Online • Modell: <strong><?= htmlspecialchars($ollamaStatus['model'] ?? 'Keins') ?></strong>
            <?php else: ?>
                ⚠️ Ollama Offline - Bitte Docker starten!
            <?php endif; ?>
        </span>
    </div>


    <!-- Config Panel (nur wenn nicht aktiv) -->
    <div class="config-panel" id="configPanel">
        <h2 style="margin-bottom: 20px; color: var(--primary, #1A3503);">⚙️ Konfiguration</h2>
        
        <div class="config-row">
            <div class="config-group">
                <label class="config-label">⏱️ Zeitlimit</label>
                <div class="option-buttons" id="timeLimitOptions">
                    <?php foreach ($timeLimits as $seconds => $label): ?>
                    <button type="button" class="option-btn <?= $seconds === 3600 ? 'selected' : '' ?>" 
                            data-value="<?= $seconds ?>"><?= $label ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="config-group">
                <label class="config-label">📊 Fragen pro Modul</label>
                <div class="option-buttons" id="questionsOptions">
                    <?php foreach ($questionsPerModuleOptions as $num): ?>
                    <button type="button" class="option-btn <?= $num === 10 ? 'selected' : '' ?>" 
                            data-value="<?= $num ?>"><?= $num ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="config-row">
            <div class="config-group">
                <label class="config-label">💾 Output-Modus</label>
                <div class="option-buttons" id="outputOptions">
                    <button type="button" class="option-btn selected" data-value="db">🗄️ Direkt in DB</button>
                    <button type="button" class="option-btn" data-value="csv">📄 CSV-Dateien</button>
                </div>
            </div>
        </div>
        
        <p style="color: var(--text-muted, #666); font-size: 0.9rem; margin-top: 15px;">
            ℹ️ Der Generator rotiert durch alle <strong>18 Quiz-Module</strong> und generiert 
            kontinuierlich Fragen bis das Zeitlimit erreicht ist.
        </p>
    </div>
    
    <!-- Status Panel (während Generierung) -->
    <div class="status-panel" id="statusPanel" style="display: none;">
        <div class="status-grid">
            <div class="status-item">
                <div class="status-value" id="timeRemaining">00:00:00</div>
                <div class="status-label">⏱️ Verbleibend</div>
            </div>
            <div class="status-item">
                <div class="status-value" id="totalGenerated">0</div>
                <div class="status-label">📝 Generiert</div>
            </div>
            <div class="status-item">
                <div class="status-value" id="modulesCompleted">0/18</div>
                <div class="status-label">📦 Module</div>
            </div>
            <div class="status-item">
                <div class="status-value" id="roundsCompleted">0</div>
                <div class="status-label">🔄 Runden</div>
            </div>
        </div>
        
        <div class="main-progress">
            <div class="main-progress-bar" id="mainProgressBar" style="width: 0%;"></div>
        </div>
        
        <div class="current-activity" id="currentActivity">
            <div class="spinner"></div>
            <span>Starte...</span>
        </div>
    </div>
    
    <!-- Module Grid -->
    <div class="gen-card">
        <h3 style="margin-bottom: 5px; color: var(--primary, #1A3503);">📚 Module</h3>
        <p style="color: var(--text-muted, #666); font-size: 0.85rem; margin-bottom: 15px;">
            ℹ️ Statusanzeige - Module werden automatisch der Reihe nach abgearbeitet
        </p>
        <div class="module-grid" id="moduleGrid">
            <?php foreach ($quizModules as $key => $mod): ?>
            <div class="module-item pending" data-module="<?= $key ?>" title="<?= $mod['name'] ?> - wird automatisch bearbeitet">
                <div class="module-icon"><?= $mod['icon'] ?></div>
                <div class="module-name"><?= $mod['name'] ?></div>
                <div class="module-progress">0/0</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Control Buttons -->
    <div class="control-buttons">
        <button class="ctrl-btn ctrl-btn-start" id="btnStart" <?= !$ollamaStatus['online'] ? 'disabled' : '' ?>>
            ▶️ Starten
        </button>
        <button class="ctrl-btn ctrl-btn-pause" id="btnPause" style="display: none;">
            ⏸️ Pause
        </button>
        <button class="ctrl-btn ctrl-btn-stop" id="btnStop" style="display: none;">
            ⏹️ Stopp
        </button>
    </div>
    
    <!-- Results (nach Ende) -->
    <div class="gen-card" id="resultsPanel" style="display: none; margin-top: 20px;">
        <h3 style="color: var(--primary, #1A3503);">📊 Ergebnis</h3>
        <div id="resultsContent"></div>
    </div>
    
</div>


<script>
// ============================================================================
// AUTO-GENERATOR FRONTEND
// ============================================================================

const modules = <?= json_encode($quizModules) ?>;
const moduleKeys = Object.keys(modules);

// Config State
let config = {
    timeLimit: 3600,
    questionsPerModule: 10,
    outputMode: 'db'
};

// Runtime State
let isRunning = false;
let isPaused = false;
let pollInterval = null;

// DOM Elements
const configPanel = document.getElementById('configPanel');
const statusPanel = document.getElementById('statusPanel');
const btnStart = document.getElementById('btnStart');
const btnPause = document.getElementById('btnPause');
const btnStop = document.getElementById('btnStop');

// ============================================================================
// OPTION BUTTON HANDLERS
// ============================================================================

document.querySelectorAll('#timeLimitOptions .option-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#timeLimitOptions .option-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        config.timeLimit = parseInt(btn.dataset.value);
    });
});

document.querySelectorAll('#questionsOptions .option-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#questionsOptions .option-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        config.questionsPerModule = parseInt(btn.dataset.value);
    });
});

document.querySelectorAll('#outputOptions .option-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#outputOptions .option-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        config.outputMode = btn.dataset.value;
    });
});

// ============================================================================
// CONTROL HANDLERS
// ============================================================================

btnStart.addEventListener('click', startGenerator);
btnPause.addEventListener('click', togglePause);
btnStop.addEventListener('click', stopGenerator);

async function startGenerator() {
    const formData = new FormData();
    formData.append('time_limit', config.timeLimit);
    formData.append('questions_per_module', config.questionsPerModule);
    formData.append('output_mode', config.outputMode);
    
    const response = await fetch('?api=start', { method: 'POST', body: formData });
    const result = await response.json();
    
    if (result.success) {
        isRunning = true;
        isPaused = false;
        
        // UI Update
        configPanel.style.display = 'none';
        statusPanel.style.display = 'block';
        btnStart.style.display = 'none';
        btnPause.style.display = 'inline-flex';
        btnStop.style.display = 'inline-flex';
        btnPause.textContent = '⏸️ Pause';
        
        // Reset Module Grid
        document.querySelectorAll('.module-item').forEach(el => {
            el.classList.remove('completed', 'active');
            el.classList.add('pending');
            el.querySelector('.module-progress').textContent = `0/${config.questionsPerModule}`;
        });
        
        // Start Polling
        startPolling();
    }
}

async function togglePause() {
    const action = isPaused ? 'resume' : 'pause';
    await fetch(`?api=${action}`);
    isPaused = !isPaused;
    btnPause.textContent = isPaused ? '▶️ Weiter' : '⏸️ Pause';
    
    if (isPaused) {
        document.getElementById('currentActivity').innerHTML = '<span>⏸️ Pausiert</span>';
    }
}

async function stopGenerator() {
    if (!confirm('Generator wirklich stoppen?')) return;
    
    const response = await fetch('?api=stop');
    const result = await response.json();
    
    isRunning = false;
    stopPolling();
    
    // UI Reset
    configPanel.style.display = 'block';
    statusPanel.style.display = 'none';
    btnStart.style.display = 'inline-flex';
    btnPause.style.display = 'none';
    btnStop.style.display = 'none';
    
    // Show Results
    if (result.stats) {
        document.getElementById('resultsPanel').style.display = 'block';
        document.getElementById('resultsContent').innerHTML = `
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; text-align: center;">
                <div><strong style="font-size: 1.5rem; color: var(--accent);">${result.stats.total_generated}</strong><br>Fragen generiert</div>
                <div><strong style="font-size: 1.5rem;">${result.stats.modules_completed}</strong><br>Module abgeschlossen</div>
                <div><strong style="font-size: 1.5rem;">${result.stats.rounds_completed}</strong><br>Runden</div>
                <div><strong style="font-size: 1.5rem;">${formatTime(result.stats.runtime)}</strong><br>Laufzeit</div>
            </div>
            <div style="margin-top: 20px; text-align: center;">
                <a href="/admin_v4.php" class="gen-btn gen-btn-primary">🏠 Zum Admin</a>
                <a href="/adaptive_learning.php" class="gen-btn gen-btn-secondary">🧪 Testen</a>
            </div>
        `;
    }
}

// ============================================================================
// POLLING & STATUS UPDATE
// ============================================================================

function startPolling() {
    // Sofort ersten Generate-Call
    generateNext();
    
    // Status-Polling alle 2 Sekunden
    pollInterval = setInterval(updateStatus, 2000);
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

async function generateNext() {
    if (!isRunning || isPaused) return;
    
    try {
        const response = await fetch('?api=generate');
        const result = await response.json();
        
        if (result.finished) {
            stopGenerator();
            return;
        }
        
        if (result.paused) return;
        
        // Nächste Generierung nach kurzer Pause
        if (isRunning && !isPaused) {
            setTimeout(generateNext, 1000);
        }
    } catch (e) {
        console.error('Generate error:', e);
        setTimeout(generateNext, 3000);
    }
}

async function updateStatus() {
    if (!isRunning) return;
    
    try {
        const response = await fetch('?api=status');
        const status = await response.json();
        
        // Time
        document.getElementById('timeRemaining').textContent = formatTime(status.time_remaining);
        document.getElementById('totalGenerated').textContent = status.total_generated;
        document.getElementById('modulesCompleted').textContent = 
            `${status.modules_completed.length}/${moduleKeys.length}`;
        document.getElementById('roundsCompleted').textContent = status.rounds_completed;
        
        // Progress Bar
        const totalExpected = moduleKeys.length * status.questions_per_module;
        const progressInRound = status.modules_completed.length * status.questions_per_module + status.current_module_progress;
        const progressPercent = Math.min(100, (progressInRound / totalExpected) * 100);
        document.getElementById('mainProgressBar').style.width = progressPercent + '%';
        
        // Current Activity
        if (!status.paused && status.current_module) {
            const mod = modules[status.current_module];
            document.getElementById('currentActivity').innerHTML = `
                <div class="spinner"></div>
                <span>${mod.icon} ${mod.name} (${status.current_module_progress}/${status.questions_per_module})</span>
            `;
        }
        
        // Module Grid Update
        document.querySelectorAll('.module-item').forEach(el => {
            const modKey = el.dataset.module;
            el.classList.remove('completed', 'active', 'pending');
            
            if (status.modules_completed.includes(modKey)) {
                el.classList.add('completed');
                el.querySelector('.module-progress').textContent = `✅ ${status.module_stats[modKey] || status.questions_per_module}`;
            } else if (modKey === status.current_module) {
                el.classList.add('active');
                el.querySelector('.module-progress').textContent = `${status.current_module_progress}/${status.questions_per_module}`;
            } else {
                el.classList.add('pending');
                el.querySelector('.module-progress').textContent = `0/${status.questions_per_module}`;
            }
        });
        
    } catch (e) {
        console.error('Status error:', e);
    }
}

function formatTime(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
}

// Check if already running on page load
(async function() {
    const response = await fetch('?api=status');
    const status = await response.json();
    
    if (status.active) {
        isRunning = true;
        isPaused = status.paused;
        config.questionsPerModule = status.questions_per_module;
        
        configPanel.style.display = 'none';
        statusPanel.style.display = 'block';
        btnStart.style.display = 'none';
        btnPause.style.display = 'inline-flex';
        btnStop.style.display = 'inline-flex';
        btnPause.textContent = isPaused ? '▶️ Weiter' : '⏸️ Pause';
        
        startPolling();
        if (!isPaused) generateNext();
    }
})();
</script>

<?php 
// Footer einbinden
$footerFile = __DIR__ . '/includes/generator_footer.php';
if (file_exists($footerFile)) {
    require_once $footerFile;
} else {
    echo '</body></html>';
}
?>
