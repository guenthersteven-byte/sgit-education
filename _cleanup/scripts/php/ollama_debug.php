<?php
/**
 * sgiT Education - AI Generator DEBUG Version
 * Zeigt genau was Ollama macht/nicht macht
 */

// Wenn direkt aufgerufen, zeige Debug-Interface
if (basename($_SERVER['PHP_SELF']) == 'ollama_debug.php') {
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Ollama Debug Tool</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #0f0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { color: #43D240; }
        .test-box {
            background: #2a2a2a;
            border: 1px solid #43D240;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #ff0; }
        pre {
            background: #000;
            padding: 15px;
            overflow: auto;
            border-radius: 5px;
        }
        button {
            background: #43D240;
            color: black;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Ollama Debug Tool - sgiT Education</h1>
        
        <?php
        echo "<div class='test-box'>";
        echo "<h2>Test 1: Ollama Verfügbarkeit</h2>";
        
        // Test 1: Basis-Check
        $ch = curl_init('http://localhost:11434');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($code1 == 200 || $code1 == 404) {
            echo "<p class='success'>✅ Ollama Server läuft auf localhost:11434</p>";
        } else {
            echo "<p class='error'>❌ Ollama Server nicht erreichbar (HTTP $code1)</p>";
        }
        
        // Test 2: API Check
        echo "<h2>Test 2: API Endpoint Check</h2>";
        
        $ch = curl_init('http://localhost:11434/api/tags');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($code2 == 200) {
            echo "<p class='success'>✅ API Endpoint erreichbar</p>";
            $models = json_decode($response, true);
            if (isset($models['models'])) {
                echo "<p class='info'>📋 Installierte Modelle:</p><ul>";
                foreach ($models['models'] as $model) {
                    echo "<li>" . htmlspecialchars($model['name']) . " - " . 
                         round($model['size'] / 1024 / 1024 / 1024, 2) . " GB</li>";
                }
                echo "</ul>";
                
                // Check ob llama2 dabei ist
                $hasLlama2 = false;
                foreach ($models['models'] as $model) {
                    if (strpos($model['name'], 'llama2') !== false || 
                        strpos($model['name'], 'llama') !== false) {
                        $hasLlama2 = true;
                        $llamaModel = $model['name'];
                    }
                }
                
                if (!$hasLlama2) {
                    echo "<p class='error'>⚠️ WICHTIG: Kein llama2 Modell gefunden!</p>";
                    echo "<p class='info'>Installiere es mit: <code>ollama pull llama2</code></p>";
                } else {
                    echo "<p class='success'>✅ Llama Modell gefunden: $llamaModel</p>";
                }
                
            } else {
                echo "<p class='error'>❌ Keine Modelle installiert!</p>";
                echo "<p class='info'>Installiere llama2 mit: <code>ollama pull llama2</code></p>";
            }
        } else {
            echo "<p class='error'>❌ API nicht erreichbar (HTTP $code2)</p>";
        }
        echo "</div>";
        
        // Test 3: Generierung testen
        if (isset($_POST['test_generation'])) {
            echo "<div class='test-box'>";
            echo "<h2>Test 3: KI-Generierung</h2>";
            
            $module = $_POST['module'] ?? 'mathematik';
            $modelName = $_POST['model'] ?? 'llama2';
            
            $prompt = "Erstelle eine $module-Frage für ein 10-jähriges Kind.\n\n";
            $prompt .= "Antworte NUR in diesem Format:\n";
            $prompt .= "Frage: [Deine Frage]\n";
            $prompt .= "Richtig: [Die richtige Antwort]\n";
            $prompt .= "Falsch1: [Eine falsche Antwort]\n";
            $prompt .= "Falsch2: [Eine andere falsche Antwort]\n";
            $prompt .= "Falsch3: [Noch eine falsche Antwort]\n\n";
            $prompt .= "Beispiel:\n";
            $prompt .= "Frage: Was ist 5 + 3?\n";
            $prompt .= "Richtig: 8\n";
            $prompt .= "Falsch1: 7\n";
            $prompt .= "Falsch2: 9\n";
            $prompt .= "Falsch3: 10";
            
            echo "<p class='info'>📝 Prompt:</p>";
            echo "<pre>" . htmlspecialchars($prompt) . "</pre>";
            
            echo "<p class='info'>⏳ Sende an Ollama (Model: $modelName)...</p>";
            
            $startTime = microtime(true);
            
            $ch = curl_init('http://localhost:11434/api/generate');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => $modelName,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'num_predict' => 500,
                    'top_p' => 0.9,
                    'top_k' => 40
                ]
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 Sekunden Timeout!
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            $duration = round(microtime(true) - $startTime, 2);
            
            echo "<p class='info'>⏱️ Dauer: $duration Sekunden</p>";
            
            if ($curlError) {
                echo "<p class='error'>❌ CURL Error: " . htmlspecialchars($curlError) . "</p>";
            } elseif ($httpCode != 200) {
                echo "<p class='error'>❌ HTTP Error: $httpCode</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            } else {
                echo "<p class='success'>✅ Antwort erhalten (HTTP 200)</p>";
                
                $data = json_decode($response, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo "<p class='error'>❌ JSON Parse Error: " . json_last_error_msg() . "</p>";
                    echo "<pre>" . htmlspecialchars($response) . "</pre>";
                } else {
                    echo "<p class='info'>📦 Response Struktur:</p>";
                    echo "<pre>" . print_r(array_keys($data), true) . "</pre>";
                    
                    if (isset($data['response'])) {
                        echo "<p class='success'>✅ KI-Antwort erhalten:</p>";
                        echo "<pre style='background: #001100;'>" . htmlspecialchars($data['response']) . "</pre>";
                        
                        // Parse die Antwort
                        echo "<p class='info'>🔍 Parsing der Antwort:</p>";
                        
                        $lines = explode("\n", $data['response']);
                        $question = '';
                        $answer = '';
                        $wrong = [];
                        
                        foreach ($lines as $line) {
                            if (stripos($line, 'Frage:') !== false) {
                                $question = trim(str_ireplace('Frage:', '', $line));
                                echo "<p class='success'>Frage gefunden: " . htmlspecialchars($question) . "</p>";
                            } elseif (stripos($line, 'Richtig:') !== false) {
                                $answer = trim(str_ireplace('Richtig:', '', $line));
                                echo "<p class='success'>Antwort gefunden: " . htmlspecialchars($answer) . "</p>";
                            } elseif (stripos($line, 'Falsch') !== false) {
                                $w = trim(preg_replace('/Falsch\d?:?/i', '', $line));
                                if ($w) {
                                    $wrong[] = $w;
                                    echo "<p class='info'>Falsche Antwort: " . htmlspecialchars($w) . "</p>";
                                }
                            }
                        }
                        
                        if ($question && $answer && count($wrong) >= 3) {
                            echo "<div style='background: #003300; padding: 10px; margin: 10px 0;'>";
                            echo "<p class='success'>✅ ERFOLG! Vollständige Frage generiert:</p>";
                            echo "<p><strong>Frage:</strong> " . htmlspecialchars($question) . "</p>";
                            echo "<p><strong>Richtig:</strong> " . htmlspecialchars($answer) . "</p>";
                            echo "<p><strong>Optionen:</strong> " . htmlspecialchars(implode(', ', array_merge([$answer], $wrong))) . "</p>";
                            echo "</div>";
                        } else {
                            echo "<p class='error'>❌ Parsing fehlgeschlagen!</p>";
                            echo "<p>Frage: " . ($question ?: 'FEHLT') . "</p>";
                            echo "<p>Antwort: " . ($answer ?: 'FEHLT') . "</p>";
                            echo "<p>Falsche Antworten: " . count($wrong) . " (brauche 3)</p>";
                        }
                        
                    } else {
                        echo "<p class='error'>❌ Keine 'response' im JSON gefunden</p>";
                        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
                    }
                }
            }
            
            echo "</div>";
        }
        ?>
        
        <div class="test-box">
            <h2>Test-Generierung starten</h2>
            <form method="post">
                <p>
                    <label>Modul: 
                        <select name="module">
                            <option value="mathematik">Mathematik</option>
                            <option value="lesen">Lesen</option>
                            <option value="englisch">Englisch</option>
                            <option value="wissenschaft">Wissenschaft</option>
                        </select>
                    </label>
                </p>
                <p>
                    <label>Model: 
                        <input type="text" name="model" value="llama2" placeholder="z.B. llama2, llama2:13b, mistral">
                    </label>
                </p>
                <button type="submit" name="test_generation" value="1">🚀 Test starten</button>
            </form>
        </div>
        
        <div class="test-box">
            <h2>Hilfe</h2>
            <p>Falls Ollama nicht läuft:</p>
            <pre>
# Terminal 1: Ollama starten
ollama serve

# Terminal 2: Modell installieren
ollama pull llama2

# Oder ein kleineres Modell:
ollama pull tinyllama

# Teste ob es läuft:
ollama run llama2 "Hallo Welt"
            </pre>
        </div>
    </div>
</body>
</html>
    <?php
    exit;
}
?>