<?php
/**
 * ============================================================================
 * sgiT Education - Module CSV Generator v2.8
 * ============================================================================
 * 
 * KOMPLETT ÜBERARBEITETE VERSION mit besserem UX:
 * - Klarer Fortschrittsbalken
 * - Live-Status für jede Altersgruppe
 * - Echtzeit-Feedback während Generierung
 * - Transparente Fehleranzeige
 * - MODEL-SELECTOR für verschiedene AI-Modelle (v2.1)
 * - BUG-036 FIX: JSON-Reparatur + Retry-Logik (v2.2)
 * - BUG-037 FIX: Back-Link korrigiert (v2.3)
 * - BUG-038 FIX: Abbrechen-Button hinzugefügt (v2.3)
 * - Timer, CSV-Ordner Link, Modell-Warnung (v2.4)
 * - PROMPT v2.0: Few-Shot Learning mit altersgerechten Beispielen (v2.5)
 * - BUG-039 FIX: CSV-Ordner Modal mit Dateiliste + Windows-Pfad (v2.6)
 * - BUG-040 FIX: CSV-Format kompatibel mit CSVQuestionImporter (v2.7)
 *   - Deutsche Spalten: frage, antwort_a, antwort_b, etc.
 *   - Semikolon-Trennung
 *   - Antworten werden gemischt, richtig=A/B/C/D
 * - BUG-043 FIX: PHP-Fehler als JSON zurückgeben statt Raw-Output (v2.8)
 *   - Output Buffering für saubere JSON-Responses
 *   - Try-Catch für alle API-Aufrufe
 * 
 * @author Claude AI für sgiT
 * @version 2.8
 * @date 07.12.2025
 * ============================================================================
 */

set_time_limit(1800);  // 30 Minuten für vollständige Generierung mit Mistral
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ============================================================================
// KONFIGURATION
// ============================================================================

$ollamaUrl = 'http://ollama:11434';
$dbPath = dirname(__DIR__) . '/AI/data/questions.db';
$outputDir = __DIR__ . '/generated/';

// Output-Verzeichnis erstellen falls nicht vorhanden
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}


// Verfügbare Module
$modules = [
    'mathematik' => ['icon' => '🔢', 'name' => 'Mathematik', 'themen' => 'Grundrechenarten, Geometrie, Algebra, Brüche, Prozente, Gleichungen'],
    'englisch' => ['icon' => '🇬🇧', 'name' => 'Englisch', 'themen' => 'Vokabeln, Grammatik, Zeiten, Präpositionen, Redewendungen'],
    'lesen' => ['icon' => '📖', 'name' => 'Lesen', 'themen' => 'Buchstaben, Silben, Wortarten, Rechtschreibung, Textverständnis'],
    'physik' => ['icon' => '⚛️', 'name' => 'Physik', 'themen' => 'Mechanik, Optik, Elektrizität, Wärme, Energie, Newton'],
    'erdkunde' => ['icon' => '🌍', 'name' => 'Erdkunde', 'themen' => 'Kontinente, Länder, Hauptstädte, Geografie, Klima'],
    'wissenschaft' => ['icon' => '🔬', 'name' => 'Wissenschaft', 'themen' => 'Experimente, Hypothesen, Planeten, Naturgesetze'],
    'geschichte' => ['icon' => '📜', 'name' => 'Geschichte', 'themen' => 'Antike, Mittelalter, Neuzeit, Deutschland, Weltkriege'],
    'computer' => ['icon' => '💻', 'name' => 'Computer', 'themen' => 'Hardware, Software, Internet, Sicherheit, Dateien'],
    'chemie' => ['icon' => '⚗️', 'name' => 'Chemie', 'themen' => 'Elemente, Reaktionen, Atome, Moleküle, Periodensystem'],
    'biologie' => ['icon' => '🧬', 'name' => 'Biologie', 'themen' => 'Tiere, Pflanzen, Körper, Zellen, Evolution'],
    'musik' => ['icon' => '🎵', 'name' => 'Musik', 'themen' => 'Noten, Instrumente, Komponisten, Musiktheorie'],
    'programmieren' => ['icon' => '👨‍💻', 'name' => 'Programmieren', 'themen' => 'Variablen, Schleifen, Funktionen, Algorithmen'],
    'bitcoin' => ['icon' => '₿', 'name' => 'Bitcoin', 'themen' => 'Satoshi, Blockchain, Mining, Wallets, Dezentralisierung'],
    'finanzen' => ['icon' => '💰', 'name' => 'Finanzen', 'themen' => 'Geld, Sparen, Steuern, Zinsen, Wirtschaft'],
    'kunst' => ['icon' => '🎨', 'name' => 'Kunst', 'themen' => 'Farben, Techniken, Künstler, Epochen, Malerei'],
    'verkehr' => ['icon' => '🚗', 'name' => 'Verkehr', 'themen' => 'Verkehrszeichen, Regeln, Sicherheit, Fahrrad'],
    'sport' => ['icon' => '🏃', 'name' => 'Sport', 'themen' => 'Sportarten, Regeln, Olympia, Fitness, Gesundheit'],
    'unnuetzes_wissen' => ['icon' => '🤯', 'name' => 'Unnützes Wissen', 'themen' => 'Fun Facts, Kurioses, Rekorde, Überraschendes']
];

// Altersgruppen
$ageGroups = [
    ['id' => 1, 'name' => 'Kinder (5-8)', 'short' => 'Kinder', 'min' => 5, 'max' => 8, 'diff' => 1, 'count' => 5],
    ['id' => 2, 'name' => 'Grundschule (8-11)', 'short' => 'Grundschule', 'min' => 8, 'max' => 11, 'diff' => 2, 'count' => 5],
    ['id' => 3, 'name' => 'Mittelstufe (11-14)', 'short' => 'Mittelstufe', 'min' => 11, 'max' => 14, 'diff' => 3, 'count' => 5],
    ['id' => 4, 'name' => 'Oberstufe (14-18)', 'short' => 'Oberstufe', 'min' => 14, 'max' => 18, 'diff' => 4, 'count' => 5],
    ['id' => 5, 'name' => 'Erwachsene (18+)', 'short' => 'Erwachsene', 'min' => 18, 'max' => 99, 'diff' => 5, 'count' => 5]
];


// ============================================================================
// FUNKTIONEN
// ============================================================================

/**
 * Generiert einen eindeutigen Hash für Duplikat-Prüfung
 */
function generateHash($q, $a, $b, $c, $d) {
    return md5(strtolower(trim($q)) . '|' . strtolower(trim($a)) . '|' . 
               strtolower(trim($b)) . '|' . strtolower(trim($c)) . '|' . strtolower(trim($d)));
}

/**
 * Lädt existierende Hashes aus der Datenbank
 */
function loadExistingHashes($dbPath) {
    $hashes = [];
    if (file_exists($dbPath)) {
        try {
            $db = new SQLite3($dbPath);
            $result = $db->query("SELECT question_hash FROM questions WHERE question_hash IS NOT NULL");
            while ($row = $result->fetchArray()) {
                $hashes[$row[0]] = true;
            }
            $db->close();
        } catch (Exception $e) {
            // Ignore errors, return empty hashes
        }
    }
    return $hashes;
}

/**
 * Prüft Ollama-Verbindung
 */
function checkOllamaConnection($ollamaUrl) {
    $ch = curl_init($ollamaUrl . '/api/tags');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['connected' => false, 'error' => 'Ollama nicht erreichbar'];
    }
    
    $data = json_decode($response, true);
    $models = $data['models'] ?? [];
    
    if (empty($models)) {
        return ['connected' => true, 'model' => false, 'error' => 'Kein Modell installiert'];
    }
    
    return ['connected' => true, 'model' => true, 'models' => array_column($models, 'name')];
}


/**
 * Repariert häufige JSON-Fehler von LLMs (BUG-036 Fix)
 * @version 1.0 - 07.12.2025
 */
function repairJsonString($jsonStr) {
    // 1. Trailing Commas vor ] oder } entfernen
    $jsonStr = preg_replace('/,(\s*[\]\}])/', '$1', $jsonStr);
    
    // 2. Newlines in Strings durch Leerzeichen ersetzen
    $jsonStr = preg_replace_callback('/"([^"]*)"/', function($m) {
        return '"' . str_replace(["\n", "\r"], ' ', $m[1]) . '"';
    }, $jsonStr);
    
    // 3. Unescapte Quotes in Strings fixen (häufiger Fehler)
    // Vorsichtig: nur innerhalb von bereits gequoteten Strings
    $jsonStr = preg_replace('/(?<!\\\\)"([^"]*)"([^"]*)"/', '"$1\'$2"', $jsonStr);
    
    // 4. Fehlende Quotes um Keys hinzufügen
    $jsonStr = preg_replace('/(\{|\,)\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $jsonStr);
    
    // 5. Single Quotes zu Double Quotes (manche LLMs nutzen ')
    // Nur wenn es wie JSON-Key/Value aussieht
    $jsonStr = preg_replace("/'/", '"', $jsonStr);
    
    // 6. Doppelte Kommas entfernen
    $jsonStr = preg_replace('/,\s*,/', ',', $jsonStr);
    
    // 7. Komma vor schließender Klammer entfernen (nochmal sicherstellen)
    $jsonStr = preg_replace('/,\s*\]/', ']', $jsonStr);
    $jsonStr = preg_replace('/,\s*\}/', '}', $jsonStr);
    
    return $jsonStr;
}

/**
 * Extrahiert und validiert JSON aus LLM-Output
 * @version 1.0 - BUG-036 Fix
 */
function extractAndParseJson($rawResponse) {
    // Versuche JSON-Array zu finden
    preg_match('/\[[\s\S]*\]/', $rawResponse, $matches);
    
    if (empty($matches)) {
        return ['success' => false, 'error' => 'Kein JSON-Array gefunden', 'raw' => substr($rawResponse, 0, 300)];
    }
    
    $jsonStr = $matches[0];
    
    // Versuch 1: Direkt parsen
    $questions = json_decode($jsonStr, true);
    if (is_array($questions) && !empty($questions)) {
        return ['success' => true, 'questions' => $questions, 'repaired' => false];
    }
    
    // Versuch 2: Mit Reparatur
    $repairedJson = repairJsonString($jsonStr);
    $questions = json_decode($repairedJson, true);
    if (is_array($questions) && !empty($questions)) {
        return ['success' => true, 'questions' => $questions, 'repaired' => true];
    }
    
    // Versuch 3: Letzte Rettung - nur das erste vollständige Objekt extrahieren
    preg_match('/\{[^{}]*"question"[^{}]*\}/', $rawResponse, $singleMatch);
    if (!empty($singleMatch)) {
        $singleQuestion = json_decode('[' . repairJsonString($singleMatch[0]) . ']', true);
        if (is_array($singleQuestion) && !empty($singleQuestion)) {
            return ['success' => true, 'questions' => $singleQuestion, 'repaired' => true, 'partial' => true];
        }
    }
    
    // Fehlgeschlagen - Debug-Info zurückgeben
    $jsonError = json_last_error_msg();
    return [
        'success' => false, 
        'error' => "JSON Parse Fehler: $jsonError",
        'raw' => substr($jsonStr, 0, 500),
        'repaired_attempt' => substr($repairedJson, 0, 500)
    ];
}

/**
 * Fragt Ollama nach Fragen für ein Modul (EINZELNE Altersgruppe)
 * @version 2.0 - BUG-036 Fix mit Retry-Logik und JSON-Reparatur
 */
function generateQuestionsForAgeGroup($ollamaUrl, $module, $themen, $ageGroup, $model = 'gemma2:2b') {
    $count = $ageGroup['count'];
    $maxRetries = 3;
    
    // VERBESSERTES PROMPT v2.0 mit Few-Shot Learning
    // Altersgerechte Beispiele für jede Gruppe
    $ageExamples = [
        1 => [ // Kinder 5-8
            'level' => 'sehr einfach, kurze Saetze, bekannte Woerter',
            'example' => '{"question": "Wie viele Beine hat ein Hund?", "correct": "4", "wrong1": "2", "wrong2": "6", "wrong3": "8", "explanation": "Hunde haben vier Beine zum Laufen."}'
        ],
        2 => [ // Grundschule 8-11
            'level' => 'einfach, laengere Saetze, Grundwissen',
            'example' => '{"question": "Was ist die Hauptstadt von Deutschland?", "correct": "Berlin", "wrong1": "Hamburg", "wrong2": "Muenchen", "wrong3": "Koeln", "explanation": "Berlin ist seit 1990 die Hauptstadt."}'
        ],
        3 => [ // Mittelstufe 11-14
            'level' => 'mittel, Fachwissen, komplexere Zusammenhaenge',
            'example' => '{"question": "Welches Gas atmen Pflanzen bei der Fotosynthese ein?", "correct": "Kohlendioxid (CO2)", "wrong1": "Sauerstoff (O2)", "wrong2": "Stickstoff (N2)", "wrong3": "Wasserstoff (H2)", "explanation": "Pflanzen wandeln CO2 in Sauerstoff um."}'
        ],
        4 => [ // Oberstufe 14-18
            'level' => 'anspruchsvoll, Detailwissen, Fachbegriffe',
            'example' => '{"question": "Welcher Physiker formulierte die spezielle Relativitaetstheorie?", "correct": "Albert Einstein", "wrong1": "Isaac Newton", "wrong2": "Max Planck", "wrong3": "Niels Bohr", "explanation": "Einstein veroeffentlichte sie 1905."}'
        ],
        5 => [ // Erwachsene 18+
            'level' => 'komplex, Expertenwissen, Zusammenhaenge',
            'example' => '{"question": "Welches Wirtschaftsmodell beschreibt die oesterreichische Schule?", "correct": "Freie Marktwirtschaft", "wrong1": "Planwirtschaft", "wrong2": "Keynesianismus", "wrong3": "Merkantilismus", "explanation": "Sie betont spontane Ordnung und Unternehmertum."}'
        ]
    ];
    
    $ageData = $ageExamples[$ageGroup['id']] ?? $ageExamples[3];
    
    $prompt = "Du bist ein Experte fuer Bildung und erstellst Quiz-Fragen.

AUFGABE: Erstelle genau {$count} Quiz-Fragen zum Thema \"{$module}\" fuer {$ageGroup['name']}.
Themenbereich: {$themen}

SCHWIERIGKEITSGRAD: {$ageData['level']}

BEISPIEL fuer diese Altersgruppe:
{$ageData['example']}

WICHTIGE REGELN:
1. Fragen muessen fuer {$ageGroup['name']} verstaendlich sein
2. Falsche Antworten muessen PLAUSIBEL sein (keine offensichtlich falschen)
3. Alle Antworten sollten aehnlich lang sein
4. KEINE Umlaute: ae statt ae, oe statt oe, ue statt ue, ss statt ss
5. Erklaerung maximal 60 Zeichen, erklaert WARUM die Antwort richtig ist

AUSGABEFORMAT - NUR JSON, KEIN TEXT DAVOR ODER DANACH:
[
  {\"question\": \"...\", \"correct\": \"...\", \"wrong1\": \"...\", \"wrong2\": \"...\", \"wrong3\": \"...\", \"explanation\": \"...\"}
]

Generiere jetzt {$count} verschiedene, lehrreiche Fragen:";

    $data = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.8,  // Etwas mehr Kreativität
            'num_predict' => 3000, // Mehr Platz für gute Antworten
            'top_p' => 0.9
        ]
    ];

    $lastError = '';
    $allRawResponses = [];
    
    // Retry-Schleife
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        $ch = curl_init($ollamaUrl . '/api/generate');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 300  // 5 Minuten für große Modelle wie Mistral
        ]);
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $lastError = "Verbindungsfehler (Versuch $attempt): $curlError";
            continue;
        }
        
        $json = json_decode($response, true);
        if (!isset($json['response'])) {
            $lastError = "Keine Antwort von Ollama (Versuch $attempt)";
            continue;
        }
        
        $rawResponse = $json['response'];
        $allRawResponses[] = substr($rawResponse, 0, 200);
        
        // JSON extrahieren und parsen mit Reparatur-Logik
        $parseResult = extractAndParseJson($rawResponse);
        
        if ($parseResult['success']) {
            $result = [
                'success' => true, 
                'questions' => $parseResult['questions'], 
                'count' => count($parseResult['questions']),
                'attempt' => $attempt,
                'repaired' => $parseResult['repaired'] ?? false
            ];
            
            if (isset($parseResult['partial'])) {
                $result['partial'] = true;
                $result['warning'] = 'Nur teilweise extrahiert';
            }
            
            return $result;
        }
        
        $lastError = $parseResult['error'] ?? 'Unbekannter Parse-Fehler';
        
        // Bei letztem Versuch: warte kurz und erhöhe Temperature leicht
        if ($attempt < $maxRetries) {
            usleep(500000); // 0.5 Sekunden warten
            $data['options']['temperature'] = min(0.9, $data['options']['temperature'] + 0.1);
        }
    }
    
    // Alle Versuche fehlgeschlagen
    return [
        'success' => false, 
        'error' => "Nach $maxRetries Versuchen fehlgeschlagen: $lastError",
        'attempts' => $maxRetries,
        'raw_samples' => $allRawResponses
    ];
}


/**
 * Speichert Fragen als CSV im Format des CSVQuestionImporter
 * BUG-040 FIX: Korrektes Format mit deutschen Spalten und Semikolon-Trennung
 * 
 * Format: frage;antwort_a;antwort_b;antwort_c;antwort_d;richtig;schwierigkeit;min_alter;max_alter;erklaerung;typ
 */
function saveQuestionsToCSV($questions, $module, $ageGroup, $outputDir, $existingHashes) {
    $filename = "{$module}_age{$ageGroup['min']}-{$ageGroup['max']}_" . date('Ymd_His') . ".csv";
    $filepath = $outputDir . $filename;
    
    $fp = fopen($filepath, 'w');
    
    // BUG-040: Deutsches Format mit Semikolon-Trennung für CSVQuestionImporter
    // Header schreiben
    fwrite($fp, "frage;antwort_a;antwort_b;antwort_c;antwort_d;richtig;schwierigkeit;min_alter;max_alter;erklaerung;typ\n");
    
    $stats = ['new' => 0, 'duplicate' => 0, 'invalid' => 0];
    
    foreach ($questions as $q) {
        if (!isset($q['question']) || !isset($q['correct'])) {
            $stats['invalid']++;
            continue;
        }
        
        // Antworten sammeln und mischen
        $correct = trim($q['correct'] ?? '');
        $wrong1 = trim($q['wrong1'] ?? '');
        $wrong2 = trim($q['wrong2'] ?? '');
        $wrong3 = trim($q['wrong3'] ?? '');
        
        // Alle Antworten in Array, richtige zuerst markiert
        $answers = [
            ['text' => $correct, 'correct' => true],
            ['text' => $wrong1, 'correct' => false],
            ['text' => $wrong2, 'correct' => false],
            ['text' => $wrong3, 'correct' => false]
        ];
        
        // Mischen
        shuffle($answers);
        
        // Richtige Antwort finden (A, B, C oder D)
        $correctLetter = 'A';
        foreach ($answers as $idx => $ans) {
            if ($ans['correct']) {
                $correctLetter = chr(65 + $idx); // 0=A, 1=B, 2=C, 3=D
                break;
            }
        }
        
        // Hash für Duplikat-Prüfung (mit gemischten Antworten)
        $hash = generateHash(
            $q['question'], 
            $answers[0]['text'], 
            $answers[1]['text'], 
            $answers[2]['text'], 
            $answers[3]['text']
        );
        $isDuplicate = isset($existingHashes[$hash]);
        
        if ($isDuplicate) {
            $stats['duplicate']++;
            continue; // Duplikate nicht in CSV schreiben
        }
        
        // Escape für Semikolon-CSV (Anführungszeichen um Felder mit Semikolon/Newlines)
        $escapeCsv = function($str) {
            $str = str_replace('"', '""', $str); // Escape quotes
            if (strpos($str, ';') !== false || strpos($str, "\n") !== false || strpos($str, '"') !== false) {
                return '"' . $str . '"';
            }
            return $str;
        };
        
        // Zeile schreiben
        $line = implode(';', [
            $escapeCsv($q['question']),
            $escapeCsv($answers[0]['text']),
            $escapeCsv($answers[1]['text']),
            $escapeCsv($answers[2]['text']),
            $escapeCsv($answers[3]['text']),
            $correctLetter,
            $ageGroup['diff'],
            $ageGroup['min'],
            $ageGroup['max'],
            $escapeCsv($q['explanation'] ?? ''),
            'ai_generated'
        ]);
        
        fwrite($fp, $line . "\n");
        $stats['new']++;
    }
    
    fclose($fp);
    return ['filename' => $filename, 'stats' => $stats];
}

// ============================================================================
// AJAX API ENDPOINT
// ============================================================================

if (isset($_GET['api'])) {
    // BUG-043 FIX: Output Buffering um PHP-Fehler abzufangen
    ob_start();
    
    // Fehler-Handler für saubere JSON-Antworten
    set_error_handler(function($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    
    try {
        header('Content-Type: application/json');
        
        $action = $_GET['api'];
    
    // Status-Check
    if ($action === 'status') {
        $status = checkOllamaConnection($ollamaUrl);
        echo json_encode($status);
        exit;
    }
    
    // Einzelne Altersgruppe generieren
    if ($action === 'generate_single') {
        $module = $_GET['module'] ?? '';
        $ageGroupId = intval($_GET['age_group'] ?? 0);
        
        if (!isset($modules[$module])) {
            echo json_encode(['success' => false, 'error' => 'Ungültiges Modul']);
            exit;
        }
        
        $ageGroup = null;
        foreach ($ageGroups as $ag) {
            if ($ag['id'] === $ageGroupId) {
                $ageGroup = $ag;
                break;
            }
        }
        
        if (!$ageGroup) {
            echo json_encode(['success' => false, 'error' => 'Ungültige Altersgruppe']);
            exit;
        }
        
        $mod = $modules[$module];
        $existingHashes = loadExistingHashes($dbPath);
        
        // Modell aus Parameter holen, oder zentrale Config lesen
        $centralConfigFile = dirname(__DIR__) . '/AI/config/ollama_model.txt';
        $defaultModel = file_exists($centralConfigFile) ? trim(file_get_contents($centralConfigFile)) : 'gemma2:2b';
        $model = $_GET['model'] ?? $defaultModel;
        
        // Generieren mit gewähltem Modell
        $result = generateQuestionsForAgeGroup($ollamaUrl, $mod['name'], $mod['themen'], $ageGroup, $model);
        
        if (!$result['success']) {
            echo json_encode($result);
            exit;
        }
        
        // Speichern
        $csvResult = saveQuestionsToCSV($result['questions'], $module, $ageGroup, $outputDir, $existingHashes);
        
        echo json_encode([
            'success' => true,
            'age_group' => $ageGroup['name'],
            'questions_generated' => $result['count'],
            'filename' => $csvResult['filename'],
            'stats' => $csvResult['stats'],
            'model_used' => $model
        ]);
        exit;
    }
    
    // Modell installieren (pull)
    if ($action === 'pull_model') {
        $modelName = $_GET['model'] ?? '';
        if (empty($modelName)) {
            echo json_encode(['success' => false, 'error' => 'Kein Modell angegeben']);
            exit;
        }
        
        // Ollama pull starten (kann lange dauern!)
        $ch = curl_init($ollamaUrl . '/api/pull');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['name' => $modelName, 'stream' => false]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 600 // 10 Minuten für große Modelle
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo json_encode(['success' => false, 'error' => "Download-Fehler: $error"]);
            exit;
        }
        
        echo json_encode(['success' => true, 'message' => "Modell '$modelName' wurde installiert"]);
        exit;
    }
    
    // BUG-039 FIX: CSV-Dateien auflisten
    if ($action === 'list_csvs') {
        $files = [];
        $csvDir = __DIR__ . '/generated/';
        
        if (is_dir($csvDir)) {
            $csvFiles = glob($csvDir . '*.csv');
            foreach ($csvFiles as $file) {
                $filename = basename($file);
                $files[] = [
                    'name' => $filename,
                    'size' => round(filesize($file) / 1024, 1) . ' KB',
                    'date' => date('d.m.Y H:i', filemtime($file)),
                    'url' => '/questions/generated/' . $filename
                ];
            }
            // Neueste zuerst
            usort($files, function($a, $b) {
                return strcmp($b['date'], $a['date']);
            });
        }
        
        // Windows-Pfad für manuelles Öffnen
        $windowsPath = 'C:\\xampp\\htdocs\\Education\\questions\\generated\\';
        
        echo json_encode([
            'success' => true,
            'files' => $files,
            'count' => count($files),
            'windows_path' => $windowsPath
        ]);
        exit;
    }
    
    echo json_encode(['error' => 'Unbekannte API-Aktion']);
        exit;
        
    } catch (Exception $e) {
        // BUG-043: PHP-Fehler als JSON zurückgeben
        ob_end_clean(); // Buffer leeren falls PHP-Fehler drin sind
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Server-Fehler: ' . $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]);
        exit;
    } catch (Error $e) {
        // BUG-043: Fatale PHP-Fehler abfangen
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Fataler Fehler: ' . $e->getMessage()
        ]);
        exit;
    } finally {
        restore_error_handler();
        ob_end_flush();
    }
}


// ============================================================================
// HTML OUTPUT - KOMPLETT NEUES UI
// ============================================================================
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sgiT Education - CSV Generator v2.0</title>
    <style>
        :root {
            --dark-green: #1A3503;
            --neon-green: #43D240;
            --bg-dark: #0d1a02;
            --yellow: #FFD700;
            --red: #ff4444;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, #0d1a02 0%, #1A3503 100%);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { max-width: 900px; margin: 0 auto; }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(0,0,0,0.3);
            border-radius: 16px;
            border: 1px solid rgba(67, 210, 64, 0.3);
        }
        
        .header h1 {
            color: var(--neon-green);
            font-size: 1.8em;
            margin-bottom: 5px;
        }
        
        .header .version {
            color: #888;
            font-size: 0.9em;
        }
        
        /* Status Box */
        .status-box {
            background: var(--bg-dark);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid rgba(67, 210, 64, 0.2);
        }
        
        .status-indicator {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .status-indicator.online { background: var(--neon-green); }
        .status-indicator.offline { background: var(--red); animation: none; }
        .status-indicator.warning { background: var(--yellow); }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        
        /* Module Grid */
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 30px;
        }
        
        .module-btn {
            background: var(--bg-dark);
            border: 2px solid rgba(67, 210, 64, 0.3);
            border-radius: 12px;
            padding: 15px 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .module-btn:hover {
            border-color: var(--neon-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 210, 64, 0.2);
        }
        
        .module-btn.selected {
            border-color: var(--neon-green);
            background: rgba(67, 210, 64, 0.15);
        }
        
        .module-btn .icon { font-size: 2em; margin-bottom: 8px; }
        .module-btn .name { font-size: 0.85em; color: #ccc; }
        
        /* Progress Section */
        .progress-section {
            background: var(--bg-dark);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid rgba(67, 210, 64, 0.2);
            display: none;
        }
        
        .progress-section.active { display: block; }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .progress-title {
            font-size: 1.3em;
            color: var(--neon-green);
        }
        
        .progress-counter {
            font-size: 1.1em;
            color: #aaa;
        }
        
        /* Progress Bar */
        .progress-bar-container {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            height: 24px;
            margin-bottom: 25px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--neon-green), #2ecc71);
            height: 100%;
            width: 0%;
            transition: width 0.5s ease;
            border-radius: 10px;
        }
        
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: bold;
            font-size: 0.85em;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        }

        
        /* Age Group Items */
        .age-groups-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .age-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: rgba(255,255,255,0.03);
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }
        
        .age-item.active {
            border-color: var(--neon-green);
            background: rgba(67, 210, 64, 0.1);
        }
        
        .age-item.completed {
            border-color: var(--neon-green);
            background: rgba(67, 210, 64, 0.15);
        }
        
        .age-item.error {
            border-color: var(--red);
            background: rgba(255, 68, 68, 0.1);
        }
        
        .age-item-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2em;
            background: rgba(255,255,255,0.1);
        }
        
        .age-item.waiting .age-item-icon { background: rgba(255,255,255,0.05); color: #666; }
        .age-item.active .age-item-icon { background: var(--neon-green); color: #000; }
        .age-item.completed .age-item-icon { background: var(--neon-green); color: #000; }
        .age-item.error .age-item-icon { background: var(--red); color: #fff; }
        
        .age-item-content { flex: 1; }
        .age-item-name { font-weight: 600; margin-bottom: 3px; }
        .age-item-status { font-size: 0.85em; color: #888; }
        .age-item.active .age-item-status { color: var(--neon-green); }
        .age-item.completed .age-item-status { color: var(--neon-green); }
        .age-item.error .age-item-status { color: var(--red); }
        
        .age-item-result {
            text-align: right;
            font-size: 0.9em;
        }
        
        .age-item-result .count { font-size: 1.3em; font-weight: bold; color: var(--neon-green); }
        
        /* Spinner */
        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid rgba(67, 210, 64, 0.3);
            border-top-color: var(--neon-green);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Start Button */
        .start-btn {
            width: 100%;
            padding: 18px;
            font-size: 1.2em;
            font-weight: bold;
            background: linear-gradient(135deg, var(--neon-green), #2ecc71);
            color: #000;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .start-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 210, 64, 0.4);
        }
        
        .start-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        
        /* Results Summary */
        .results-summary {
            background: rgba(67, 210, 64, 0.1);
            border: 1px solid var(--neon-green);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }
        
        .results-summary.show { display: block; }
        
        .results-summary h3 {
            color: var(--neon-green);
            margin-bottom: 15px;
        }
        
        .results-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            text-align: center;
        }
        
        .stat-box {
            background: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: 8px;
        }
        
        .stat-box .number {
            font-size: 2em;
            font-weight: bold;
            color: var(--neon-green);
        }
        
        .stat-box .label { color: #aaa; font-size: 0.9em; }
        
        /* Back Link */
        .back-link {
            display: inline-block;
            color: var(--neon-green);
            text-decoration: none;
            margin-bottom: 20px;
            padding: 8px 16px;
            border: 1px solid var(--neon-green);
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            background: var(--neon-green);
            color: #000;
        }
        
        /* Log Area */
        .log-area {
            background: #000;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-family: 'Consolas', monospace;
            font-size: 0.85em;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
        
        .log-area.show { display: block; }
        .log-entry { margin-bottom: 5px; color: #888; }
        .log-entry.success { color: var(--neon-green); }
        .log-entry.error { color: var(--red); }
        .log-entry.info { color: var(--yellow); }
    </style>
</head>
<body>
    <!-- Generator Navigation Bar (TODO-008) -->
    <nav style="background: linear-gradient(135deg, #1A3503, #2d5a06); padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 0;">
        <div style="display: flex; align-items: center; gap: 10px; color: white;">
            <span style="font-weight: bold;">sgiT Generator</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="/bots/tests/AIGeneratorBot.php" style="padding: 8px 14px; background: rgba(255,255,255,0.15); color: white; text-decoration: none; border-radius: 6px; font-size: 0.85rem;">🤖 AI Generator</a>
            <a href="/questions/generate_module_csv.php" style="padding: 8px 14px; background: #43D240; color: white; text-decoration: none; border-radius: 6px; font-size: 0.85rem;">📝 CSV Generator</a>
            <a href="/batch_import.php" style="padding: 8px 14px; background: rgba(255,255,255,0.15); color: white; text-decoration: none; border-radius: 6px; font-size: 0.85rem;">📥 CSV Import</a>
            <a href="/admin_v4.php" style="padding: 8px 14px; background: rgba(255,255,255,0.15); color: white; text-decoration: none; border-radius: 6px; font-size: 0.85rem;">🏠 Admin</a>
        </div>
    </nav>
    
    <div class="container">
        <a href="/admin_v4.php" class="back-link">← Zurück zum Admin</a>
        
        <div class="header">
            <h1>🤖 AI Question Generator</h1>
            <div class="version">v2.5 - Fragen für Module generieren (Prompt v2.0)</div>
        </div>
        
        <!-- Status Box mit Model-Selector -->
        <div class="status-box" id="statusBox">
            <div class="status-indicator" id="statusIndicator"></div>
            <div id="statusText" style="flex:1;">Prüfe Ollama-Verbindung...</div>
            <div class="model-selector" id="modelSelector" style="display:none;">
                <label style="color:#888;margin-right:10px;">Modell:</label>
                <select id="modelSelect" onchange="updateModelInfo()">
                    <option value="tinyllama">🐰 TinyLlama (schnell, einfach)</option>
                </select>
                <button onclick="showModelManager()" style="margin-left:10px;padding:5px 10px;background:#333;border:1px solid var(--neon-green);color:var(--neon-green);border-radius:5px;cursor:pointer;">+ Modell</button>
            </div>
        </div>
        
        <!-- Model Manager Modal -->
        <div id="modelModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);z-index:1000;justify-content:center;align-items:center;">
            <div style="background:var(--bg-dark);border:1px solid var(--neon-green);border-radius:12px;padding:25px;max-width:500px;width:90%;">
                <h3 style="color:var(--neon-green);margin-bottom:20px;">🔧 Modell installieren</h3>
                <p style="color:#888;margin-bottom:15px;">Wähle ein Modell zum Installieren. Größere Modelle liefern bessere Antworten, sind aber langsamer.</p>
                <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
                    <button onclick="pullModel('llama3.2:1b')" class="model-install-btn">📦 Llama 3.2 1B (~1.3 GB) - Ausgewogen</button>
                    <button onclick="pullModel('llama3.2:3b')" class="model-install-btn">📦 Llama 3.2 3B (~2 GB) - Bessere Qualität</button>
                    <button onclick="pullModel('mistral')" class="model-install-btn">📦 Mistral 7B (~4 GB) - Sehr gut</button>
                    <button onclick="pullModel('gemma2:2b')" class="model-install-btn">📦 Gemma2 2B (~1.6 GB) - Google</button>
                </div>
                <div id="pullProgress" style="display:none;background:#000;padding:15px;border-radius:8px;margin-bottom:15px;">
                    <div style="color:var(--yellow);">⏳ Download läuft... Dies kann mehrere Minuten dauern!</div>
                </div>
                <button onclick="hideModelManager()" style="width:100%;padding:12px;background:#333;border:none;color:#fff;border-radius:8px;cursor:pointer;">Schließen</button>
            </div>
        </div>
        
        <style>
            .model-install-btn {
                padding:12px 15px;
                background:#1a1a1a;
                border:1px solid #444;
                color:#fff;
                border-radius:8px;
                cursor:pointer;
                text-align:left;
                transition:all 0.3s;
            }
            .model-install-btn:hover {
                border-color:var(--neon-green);
                background:#222;
            }
        </style>

        
        <!-- Module Selection -->
        <h2 style="color: var(--neon-green); margin-bottom: 15px;">📚 Modul auswählen</h2>
        <div class="module-grid" id="moduleGrid">
            <?php foreach ($modules as $key => $mod): ?>
            <div class="module-btn" data-module="<?= $key ?>" onclick="selectModule('<?= $key ?>')">
                <div class="icon"><?= $mod['icon'] ?></div>
                <div class="name"><?= $mod['name'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Progress Section -->
        <div class="progress-section" id="progressSection">
            <div class="progress-header">
                <div class="progress-title" id="progressTitle">🎯 Generiere Fragen...</div>
                <div class="progress-counter" id="progressCounter">0 / 5</div>
            </div>
            
            <div class="progress-bar-container">
                <div class="progress-bar" id="progressBar"></div>
                <div class="progress-text" id="progressText">0%</div>
            </div>
            
            <div class="age-groups-list" id="ageGroupsList">
                <?php foreach ($ageGroups as $ag): ?>
                <div class="age-item waiting" id="ageItem<?= $ag['id'] ?>" data-id="<?= $ag['id'] ?>">
                    <div class="age-item-icon">⏸️</div>
                    <div class="age-item-content">
                        <div class="age-item-name"><?= $ag['name'] ?></div>
                        <div class="age-item-status">Wartet...</div>
                    </div>
                    <div class="age-item-result"></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Results Summary -->
            <div class="results-summary" id="resultsSummary">
                <h3>✅ Generierung abgeschlossen!</h3>
                <div class="results-stats">
                    <div class="stat-box">
                        <div class="number" id="totalQuestions">0</div>
                        <div class="label">Fragen generiert</div>
                    </div>
                    <div class="stat-box">
                        <div class="number" id="newQuestions">0</div>
                        <div class="label">Neue Fragen</div>
                    </div>
                    <div class="stat-box">
                        <div class="number" id="duplicateQuestions">0</div>
                        <div class="label">Duplikate</div>
                    </div>
                </div>
            </div>
            
            <!-- Log Area -->
            <div class="log-area" id="logArea"></div>
            
            <!-- Timer + CSV-Ordner Link -->
            <div id="timerArea" style="display:none;margin-top:15px;padding:15px;background:rgba(0,0,0,0.3);border-radius:8px;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <span style="color:#888;">⏱️ Verstrichene Zeit:</span>
                    <span id="elapsedTime" style="color:var(--neon-green);font-weight:bold;font-size:1.2em;margin-left:10px;">0:00</span>
                </div>
                <button onclick="openCsvModal()" style="color:var(--neon-green);background:transparent;border:1px solid var(--neon-green);padding:8px 15px;border-radius:6px;cursor:pointer;">
                    📁 CSV-Ordner öffnen
                </button>
            </div>
            
            <!-- BUG-039: CSV Modal -->
            <div id="csvModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:1000;justify-content:center;align-items:center;">
                <div style="background:var(--dark-green);border:2px solid var(--neon-green);border-radius:12px;padding:25px;max-width:700px;width:90%;max-height:80vh;overflow-y:auto;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                        <h3 style="margin:0;color:var(--neon-green);">📁 Generierte CSV-Dateien</h3>
                        <button onclick="closeCsvModal()" style="background:none;border:none;color:#fff;font-size:1.5em;cursor:pointer;">✕</button>
                    </div>
                    
                    <div style="background:rgba(0,0,0,0.3);padding:12px;border-radius:8px;margin-bottom:20px;">
                        <div style="color:#888;font-size:0.85em;margin-bottom:5px;">📍 Windows Explorer Pfad (kopieren):</div>
                        <div style="display:flex;gap:10px;align-items:center;">
                            <input type="text" id="windowsPath" readonly style="flex:1;background:#111;border:1px solid #333;color:var(--neon-green);padding:8px;border-radius:4px;font-family:monospace;font-size:0.9em;">
                            <button onclick="copyPath()" style="background:var(--neon-green);color:#000;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;font-weight:bold;">📋 Kopieren</button>
                        </div>
                    </div>
                    
                    <div id="csvFileList" style="color:#ccc;">Lade Dateien...</div>
                </div>
            </div>
        </div>
        
        <!-- Start Button -->
        <button class="start-btn" id="startBtn" onclick="startGeneration()" disabled>
            Modul auswählen um zu starten
        </button>
        
        <!-- BUG-038: Abbrechen-Button -->
        <button class="start-btn" id="cancelBtn" onclick="cancelGeneration()" style="display:none;background:linear-gradient(135deg, #ff4444, #cc0000);margin-top:10px;">
            🛑 Generierung abbrechen
        </button>
    </div>

    
    <script>
    // ============================================================================
    // JAVASCRIPT - Interaktive Steuerung v2.1
    // ============================================================================
    
    let selectedModule = null;
    let isGenerating = false;
    let ollamaReady = false;
    let availableModels = [];
    let abortController = null; // BUG-038: Für Abbrechen-Funktion
    let timerInterval = null;   // Timer für verstrichene Zeit
    let startTime = null;       // Startzeit der Generierung
    
    // Zentrale Model-Config lesen
    const centralModel = <?php 
        $configFile = dirname(__DIR__) . '/AI/config/ollama_model.txt';
        echo json_encode(file_exists($configFile) ? trim(file_get_contents($configFile)) : 'gemma2:2b');
    ?>;
    
    const ageGroups = <?= json_encode($ageGroups) ?>;
    const modules = <?= json_encode($modules) ?>;
    
    // Model-Info für bessere UX
    const modelInfo = {
        'gemma2:2b': {name: '💎 Gemma2 2B - EMPFOHLEN', desc: 'Beste Qualität für CPU', quality: 5},
        'llama3.2:1b': {name: '🦙 Llama 3.2 1B', desc: 'Schnell, akzeptable Qualität', quality: 3},
        'llama3.2:3b': {name: '🦙 Llama 3.2 3B', desc: 'Gute Qualität', quality: 4},
        'tinyllama:latest': {name: '🐰 TinyLlama', desc: 'Sehr schnell, einfache Antworten', quality: 1},
        'tinyllama': {name: '🐰 TinyLlama', desc: 'Sehr schnell, einfache Antworten', quality: 1},
        'mistral:latest': {name: '⚠️ Mistral 7B', desc: 'NUR MIT GPU!', quality: 4},
        'mistral': {name: '⚠️ Mistral 7B', desc: 'NUR MIT GPU!', quality: 4}
    };
    
    // Status beim Laden prüfen
    document.addEventListener('DOMContentLoaded', checkStatus);
    
    async function checkStatus() {
        try {
            const response = await fetch('?api=status');
            const data = await response.json();
            
            const indicator = document.getElementById('statusIndicator');
            const text = document.getElementById('statusText');
            const selector = document.getElementById('modelSelector');
            const select = document.getElementById('modelSelect');
            
            if (!data.connected) {
                indicator.className = 'status-indicator offline';
                text.innerHTML = '❌ Ollama nicht erreichbar';
                ollamaReady = false;
            } else if (!data.model) {
                indicator.className = 'status-indicator warning';
                text.innerHTML = '⚠️ Kein Modell installiert';
                selector.style.display = 'flex';
                ollamaReady = false;
            } else {
                indicator.className = 'status-indicator online';
                text.innerHTML = '✅ Bereit';
                selector.style.display = 'flex';
                ollamaReady = true;
                
                // Modelle in Dropdown laden
                availableModels = data.models;
                select.innerHTML = '';
                data.models.forEach(model => {
                    const info = modelInfo[model] || {name: model, desc: '', quality: 2};
                    const opt = document.createElement('option');
                    opt.value = model;
                    opt.textContent = `${info.name} - ${info.desc}`;
                    select.appendChild(opt);
                });
                
                // Warnung für große Modelle prüfen
                setTimeout(checkModelWarning, 100);
            }
        } catch (e) {
            document.getElementById('statusIndicator').className = 'status-indicator offline';
            document.getElementById('statusText').innerHTML = '❌ Verbindungsfehler';
        }
    }
    
    function getSelectedModel() {
        return document.getElementById('modelSelect').value || centralModel;
    }
    
    function updateModelInfo() {
        const model = getSelectedModel();
        const info = modelInfo[model] || {quality: 2};
        checkModelWarning(); // Warnung für große Modelle prüfen
    }
    
    function showModelManager() {
        document.getElementById('modelModal').style.display = 'flex';
    }
    
    function hideModelManager() {
        document.getElementById('modelModal').style.display = 'none';
    }
    
    async function pullModel(modelName) {
        const progress = document.getElementById('pullProgress');
        progress.style.display = 'block';
        progress.innerHTML = `<div style="color:var(--yellow);">⏳ Lade ${modelName}... Dies kann 2-10 Minuten dauern!</div>`;
        
        try {
            const response = await fetch(`?api=pull_model&model=${encodeURIComponent(modelName)}`);
            const data = await response.json();
            
            if (data.success) {
                progress.innerHTML = `<div style="color:var(--neon-green);">✅ ${modelName} installiert!</div>`;
                setTimeout(() => {
                    hideModelManager();
                    checkStatus(); // Refresh models
                }, 1500);
            } else {
                progress.innerHTML = `<div style="color:var(--red);">❌ Fehler: ${data.error}</div>`;
            }
        } catch (e) {
            progress.innerHTML = `<div style="color:var(--red);">❌ Netzwerkfehler</div>`;
        }
    }
    
    function selectModule(moduleKey) {
        if (isGenerating) return;
        
        // Deselect all
        document.querySelectorAll('.module-btn').forEach(btn => btn.classList.remove('selected'));
        
        // Select new
        document.querySelector(`[data-module="${moduleKey}"]`).classList.add('selected');
        selectedModule = moduleKey;
        
        // Update button
        const btn = document.getElementById('startBtn');
        if (ollamaReady) {
            btn.disabled = false;
            btn.textContent = `🚀 ${modules[moduleKey].name} - 25 Fragen generieren`;
        } else {
            btn.disabled = true;
            btn.textContent = 'Ollama nicht bereit';
        }
        
        // Show progress section
        document.getElementById('progressSection').classList.add('active');
        document.getElementById('progressTitle').textContent = `🎯 ${modules[moduleKey].icon} ${modules[moduleKey].name}`;
        
        // Reset age groups
        resetAgeGroups();
    }
    
    function resetAgeGroups() {
        ageGroups.forEach(ag => {
            const item = document.getElementById('ageItem' + ag.id);
            item.className = 'age-item waiting';
            item.querySelector('.age-item-icon').textContent = '⏸️';
            item.querySelector('.age-item-status').textContent = 'Wartet...';
            item.querySelector('.age-item-result').innerHTML = '';
        });
        
        document.getElementById('progressBar').style.width = '0%';
        document.getElementById('progressText').textContent = '0%';
        document.getElementById('progressCounter').textContent = '0 / 5';
        document.getElementById('resultsSummary').classList.remove('show');
        document.getElementById('logArea').classList.remove('show');
        document.getElementById('logArea').innerHTML = '';
    }

    
    function addLog(message, type = 'info') {
        const log = document.getElementById('logArea');
        log.classList.add('show');
        const entry = document.createElement('div');
        entry.className = 'log-entry ' + type;
        entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
        log.appendChild(entry);
        log.scrollTop = log.scrollHeight;
    }
    
    async function startGeneration() {
        if (!selectedModule || isGenerating || !ollamaReady) return;
        
        isGenerating = true;
        abortController = new AbortController(); // BUG-038: Abort-Support
        
        const btn = document.getElementById('startBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        btn.disabled = true;
        btn.textContent = '⏳ Generierung läuft...';
        cancelBtn.style.display = 'block'; // BUG-038: Zeige Abbrechen-Button
        
        // Timer starten
        startTimer();
        
        const selectedModel = getSelectedModel();
        let totalQuestions = 0;
        let totalNew = 0;
        let totalDuplicates = 0;
        let completed = 0;
        
        const modelName = modelInfo[selectedModel]?.name || selectedModel;
        addLog(`Starte Generierung für ${modules[selectedModule].name} mit ${modelName}`, 'info');
        
        for (const ag of ageGroups) {
            // BUG-038: Prüfe ob abgebrochen
            if (abortController.signal.aborted) {
                addLog('⚠️ Generierung abgebrochen!', 'error');
                break;
            }
            
            const item = document.getElementById('ageItem' + ag.id);
            
            // Set active
            item.className = 'age-item active';
            item.querySelector('.age-item-icon').innerHTML = '<div class="spinner"></div>';
            item.querySelector('.age-item-status').textContent = 'Generiere Fragen...';
            
            addLog(`${ag.name}: Sende Anfrage an AI...`, 'info');
            
            try {
                const startTime = Date.now();
                const response = await fetch(`?api=generate_single&module=${selectedModule}&age_group=${ag.id}&model=${encodeURIComponent(selectedModel)}`, {
                    signal: abortController.signal // BUG-038: Abort-Signal
                });
                const data = await response.json();
                const duration = ((Date.now() - startTime) / 1000).toFixed(1);
                
                if (data.success) {
                    // Success
                    item.className = 'age-item completed';
                    item.querySelector('.age-item-icon').textContent = '✅';
                    item.querySelector('.age-item-status').textContent = `${data.questions_generated} Fragen in ${duration}s`;
                    item.querySelector('.age-item-result').innerHTML = `
                        <div class="count">${data.stats.new}</div>
                        <div style="color:#888;font-size:0.8em;">neue</div>
                    `;
                    
                    totalQuestions += data.questions_generated;
                    totalNew += data.stats.new;
                    totalDuplicates += data.stats.duplicate;
                    
                    addLog(`${ag.name}: ✅ ${data.questions_generated} Fragen generiert (${data.stats.new} neu, ${data.stats.duplicate} Duplikate) - ${data.filename}`, 'success');
                } else {
                    // Error
                    item.className = 'age-item error';
                    item.querySelector('.age-item-icon').textContent = '❌';
                    item.querySelector('.age-item-status').textContent = data.error || 'Fehler';
                    
                    addLog(`${ag.name}: ❌ ${data.error}`, 'error');
                }
            } catch (e) {
                // BUG-038: Unterscheide Abort von anderen Fehlern
                if (e.name === 'AbortError') {
                    item.className = 'age-item error';
                    item.querySelector('.age-item-icon').textContent = '⏹️';
                    item.querySelector('.age-item-status').textContent = 'Abgebrochen';
                    addLog(`${ag.name}: ⏹️ Abgebrochen`, 'error');
                    break;
                }
                
                item.className = 'age-item error';
                item.querySelector('.age-item-icon').textContent = '❌';
                item.querySelector('.age-item-status').textContent = 'Netzwerkfehler';
                
                addLog(`${ag.name}: ❌ Netzwerkfehler - ${e.message}`, 'error');
            }
            
            completed++;
            const percent = Math.round((completed / 5) * 100);
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressText').textContent = percent + '%';
            document.getElementById('progressCounter').textContent = `${completed} / 5`;
        }
        
        // Show results
        document.getElementById('totalQuestions').textContent = totalQuestions;
        document.getElementById('newQuestions').textContent = totalNew;
        document.getElementById('duplicateQuestions').textContent = totalDuplicates;
        document.getElementById('resultsSummary').classList.add('show');
        
        if (abortController.signal.aborted) {
            addLog(`Abgebrochen nach ${completed} Altersgruppen. ${totalQuestions} Fragen generiert.`, 'error');
        } else {
            addLog(`Fertig! ${totalQuestions} Fragen generiert.`, 'success');
        }
        
        isGenerating = false;
        abortController = null;
        stopTimer(); // Timer stoppen
        cancelBtn.style.display = 'none'; // BUG-038: Verstecke Abbrechen-Button
        btn.disabled = false;
        btn.textContent = `🔄 Nochmal generieren`;
    }
    
    // BUG-038: Abbrechen-Funktion
    function cancelGeneration() {
        if (abortController) {
            abortController.abort();
            addLog('🛑 Abbruch angefordert...', 'error');
        }
    }
    
    // Timer-Funktionen
    function startTimer() {
        startTime = Date.now();
        document.getElementById('timerArea').style.display = 'flex';
        timerInterval = setInterval(updateTimer, 1000);
    }
    
    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }
    
    function updateTimer() {
        if (!startTime) return;
        const elapsed = Math.floor((Date.now() - startTime) / 1000);
        const mins = Math.floor(elapsed / 60);
        const secs = elapsed % 60;
        document.getElementById('elapsedTime').textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
    }
    
    // Warnung für große Modelle
    function checkModelWarning() {
        const model = getSelectedModel();
        const warningDiv = document.getElementById('modelWarning');
        
        if (model.includes('mistral') || model.includes('7b') || model.includes('8b')) {
            if (!warningDiv) {
                const warning = document.createElement('div');
                warning.id = 'modelWarning';
                warning.style.cssText = 'background:#332200;border:1px solid #ffaa00;border-radius:8px;padding:12px;margin-top:10px;color:#ffcc00;font-size:0.9em;';
                warning.innerHTML = '⚠️ <strong>Hinweis:</strong> Große Modelle (7B+) benötigen eine GPU. Auf CPU kann eine Anfrage 10-30 Minuten dauern! Empfehlung: <strong>llama3.2:1b</strong> oder <strong>llama3.2:3b</strong> verwenden.';
                document.getElementById('statusBox').after(warning);
            }
        } else if (warningDiv) {
            warningDiv.remove();
        }
    }
    
    // BUG-039: CSV-Modal Funktionen
    async function openCsvModal() {
        const modal = document.getElementById('csvModal');
        const fileList = document.getElementById('csvFileList');
        const pathInput = document.getElementById('windowsPath');
        
        modal.style.display = 'flex';
        fileList.innerHTML = '<div style="text-align:center;padding:20px;">⏳ Lade Dateien...</div>';
        
        try {
            const response = await fetch('?api=list_csvs');
            const data = await response.json();
            
            if (data.success) {
                pathInput.value = data.windows_path;
                
                if (data.files.length === 0) {
                    fileList.innerHTML = '<div style="text-align:center;padding:20px;color:#888;">📭 Keine CSV-Dateien vorhanden.<br><small>Generiere zuerst Fragen für ein Modul.</small></div>';
                } else {
                    let html = '<div style="font-size:0.85em;color:#888;margin-bottom:10px;">📊 ' + data.count + ' Dateien gefunden:</div>';
                    html += '<div style="display:flex;flex-direction:column;gap:8px;">';
                    
                    for (const file of data.files) {
                        html += `
                            <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(0,0,0,0.3);padding:10px 12px;border-radius:6px;">
                                <div>
                                    <div style="color:var(--neon-green);font-size:0.95em;">📄 ${file.name}</div>
                                    <div style="color:#666;font-size:0.8em;">${file.size} • ${file.date}</div>
                                </div>
                                <a href="${file.url}" download style="background:var(--neon-green);color:#000;padding:5px 10px;border-radius:4px;text-decoration:none;font-size:0.85em;font-weight:bold;">⬇️ Download</a>
                            </div>
                        `;
                    }
                    html += '</div>';
                    fileList.innerHTML = html;
                }
            } else {
                fileList.innerHTML = '<div style="color:#ff4444;">❌ Fehler beim Laden der Dateien</div>';
            }
        } catch (e) {
            fileList.innerHTML = '<div style="color:#ff4444;">❌ Netzwerkfehler: ' + e.message + '</div>';
        }
    }
    
    function closeCsvModal() {
        document.getElementById('csvModal').style.display = 'none';
    }
    
    function copyPath() {
        const pathInput = document.getElementById('windowsPath');
        pathInput.select();
        document.execCommand('copy');
        
        // Visuelles Feedback
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = '✅ Kopiert!';
        btn.style.background = '#22cc22';
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = 'var(--neon-green)';
        }, 1500);
    }
    
    // Modal schließen bei Klick außerhalb
    document.getElementById('csvModal').addEventListener('click', function(e) {
        if (e.target === this) closeCsvModal();
    });
    </script>
</body>
</html>
