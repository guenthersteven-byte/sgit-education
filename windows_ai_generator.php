<?php
/**
 * ============================================================================
 * sgiT Education - AI Generator
 * ============================================================================
 * 
 * AI-gesteuerte Fragen-Generierung via Ollama
 * - Cloud-Modelle Support (deepseek, qwen3, gpt-oss, etc.)
 * - Erklärungsfeld (E:) - kindgerechte Erläuterungen
 * - OptimizedPrompts mit Seed-Topics für Variabilität
 * - 4 Altersgruppen (young/medium/advanced/expert)
 * 
 * Nutzt zentrale Versionsverwaltung via /includes/version.php
 * 
 * @version Siehe SGIT_VERSION
 * @date Siehe SGIT_VERSION_DATE
 * @author sgiT Solution Engineering & IT Services
 * ============================================================================
 */

// Zentrale Versionsverwaltung
require_once __DIR__ . '/includes/version.php';

/*
 * ✅ NEU #6: Verbessertes Parsing für Q:/A:/W1:/W2:/W3: Format
 * 
 * WICHTIG: Prompts sind auf ENGLISCH für bessere LLM-Performance!
 *          Output (Fragen + Antworten) ist auf DEUTSCH.
 * 
 * ============================================
 * CHANGELOG v10.8 (02.12.2025):
 * ============================================
 * ✅ NEU #1: generateQuestionHash() - MD5-Hash für Duplikat-Erkennung
 * ✅ NEU #2: saveQuestionToDB() setzt jetzt question_hash und source
 * ✅ NEU #3: Kompatibel mit CSV-Import Duplikat-System
 * 
 * ============================================
 * CHANGELOG v10.7 (01.12.2025):
 * ============================================
 * ✅ NEU #1: Zentrale Model-Config via AI/config/ollama_model.txt
 * ✅ NEU #2: setConfiguredModel() - Model über Admin-Dashboard setzen
 * ✅ NEU #3: getConfiguredModel() - Aktuelles Model abrufen
 * ✅ NEU #4: getAvailableModels() - Liste aller Ollama-Modelle
 * ✅ NEU #5: detectBestModel() liest Config ZUERST → Alle Bots nutzen dasselbe Model!
 * 
 * ============================================
 * CHANGELOG v10.6 (30.11.2025):
 * ============================================
 * ✅ FIX #1: parseAIResponse() komplett überarbeitet - flexibleres Parsing
 * ✅ FIX #2: validateQuestion() weniger aggressiv - weniger Falsch-Positive
 * ✅ FIX #3: getEmergencyFallback() - modul-spezifische Fallbacks für ALLE 15 Module
 * ✅ FIX #4: generateWithAI() mit vollständigem Debug-Logging
 * ✅ FIX #5: Ollama-Parameter optimiert (temperature 0.7, num_predict 300, timeout 90s)
 * ✅ FIX #6: Logging-Level 'debug' hinzugefügt für detaillierte Analyse
 * ✅ FIX #7: Model-Priorität: TinyLlama zuerst (schneller), dann llama3.2
 * ✅ FIX #8: Timeout auf 90 Sekunden erhöht für langsamere Hardware
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 11.1 + ERKLÄRUNGEN
 */

// v10.9: Optimierte Prompts laden
require_once __DIR__ . '/includes/OptimizedPrompts.php';

class AIQuestionGeneratorComplete {
    
    private $ollamaUrl = 'http://localhost:11434';
    private $db;
    private $availableModel = null;
    private $modelChecked = false;
    private $logFile;
    private $debugMode = true;
    
    // v11.0: Cloud-Modelle werden aus Config geladen
    private static $cloudModels = null;
    private static $cloudConfig = null;
    
    /**
     * v11.0: Lädt Cloud-Konfiguration
     */
    private static function loadCloudConfig() {
        if (self::$cloudConfig === null) {
            $configFile = dirname(__FILE__) . '/AI/config/ollama_cloud.php';
            if (file_exists($configFile)) {
                self::$cloudConfig = include $configFile;
                self::$cloudModels = self::$cloudConfig['cloud_models'] ?? [];
            } else {
                self::$cloudConfig = [];
                self::$cloudModels = [];
            }
        }
        return self::$cloudConfig;
    }
    
    public function __construct() {
        $this->createDirectories();
        $this->initDatabase();
        $this->detectBestModel();
        $this->logFile = __DIR__ . '/AI/logs/generator.log';
    }
    
    private function createDirectories() {
        $dirs = [
            __DIR__ . '/AI',
            __DIR__ . '/AI/data',
            __DIR__ . '/AI/cache',
            __DIR__ . '/AI/logs',
            __DIR__ . '/AI/users',
            __DIR__ . '/AI/config'  // v10.7: Zentrale Config
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }
    
    private function initDatabase() {
        try {
            $dbPath = __DIR__ . '/AI/data/questions.db';
            $this->db = new SQLite3($dbPath);
            
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS questions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    module TEXT NOT NULL,
                    difficulty INTEGER NOT NULL DEFAULT 5,
                    age_min INTEGER DEFAULT 5,
                    age_max INTEGER DEFAULT 15,
                    question TEXT NOT NULL,
                    answer TEXT NOT NULL,
                    options TEXT NOT NULL,
                    erklaerung TEXT,
                    ai_generated INTEGER DEFAULT 0,
                    model_used TEXT,
                    generation_time REAL,
                    times_used INTEGER DEFAULT 0,
                    correct_answers INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ');
            
            // v11.1: Spalte erklaerung hinzufügen falls nicht vorhanden
            try {
                $this->db->exec('ALTER TABLE questions ADD COLUMN erklaerung TEXT');
            } catch (Exception $e) {
                // Spalte existiert bereits - ignorieren
            }
            
            $this->db->exec('CREATE INDEX IF NOT EXISTS idx_module_age ON questions(module, age_min, age_max)');
            $this->db->exec('CREATE INDEX IF NOT EXISTS idx_ai_gen ON questions(ai_generated)');
            $this->db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_unique_question ON questions(question, module)');
            
            $this->log("Database initialized successfully with duplicate protection");
            
        } catch (Exception $e) {
            $this->log("Database error: " . $e->getMessage(), 'error');
            $this->db = null;
        }
    }
    
    private function detectBestModel() {
        if ($this->modelChecked) return;
        
        // ========================================
        // v10.7: ZUERST Config-Datei prüfen!
        // v11.0: Cloud-Modelle immer akzeptieren
        // ========================================
        $configFile = __DIR__ . '/AI/config/ollama_model.txt';
        if (file_exists($configFile)) {
            $configuredModel = trim(file_get_contents($configFile));
            if (!empty($configuredModel)) {
                // v11.0: Cloud-Modelle immer akzeptieren (keine lokale Prüfung)
                if ($this->isCloudModel($configuredModel)) {
                    $this->availableModel = $configuredModel;
                    $this->log("✅ Using CLOUD model: " . $configuredModel);
                    $this->modelChecked = true;
                    return;
                }
                
                // Lokale Modelle: Prüfen ob verfügbar
                $availableModels = $this->getAvailableModelsFromOllama();
                if (in_array($configuredModel, $availableModels)) {
                    $this->availableModel = $configuredModel;
                    $this->log("✅ Using CONFIGURED model: " . $configuredModel . " (from config file)");
                    $this->modelChecked = true;
                    return;
                } else {
                    $this->log("⚠️ Configured model '$configuredModel' not available, falling back to auto-detect", 'warning');
                }
            }
        }
        
        // ========================================
        // FALLBACK: Automatische Erkennung (wie bisher)
        // ========================================
        try {
            $availableModels = $this->getAvailableModelsFromOllama();
            
            if (count($availableModels) > 0) {
                // v11.1: llama3.2 als Standard (TinyLlama zu schwach für Q/A/W1/W2/W3 Format!)
                $preferredModels = [
                    'llama3.2:latest',
                    'llama3.2',
                    'llama2:latest',
                    'llama2'
                ];
                
                foreach ($preferredModels as $preferred) {
                    if (in_array($preferred, $availableModels)) {
                        $this->availableModel = $preferred;
                        $this->log("✅ Using model (auto-detect): " . $preferred);
                        break;
                    }
                }
                
                if (!$this->availableModel && count($availableModels) > 0) {
                    $this->availableModel = $availableModels[0];
                    $this->log("Using fallback model: " . $this->availableModel);
                }
            }
        } catch (Exception $e) {
            $this->log("Model detection error: " . $e->getMessage(), 'error');
        }
        
        $this->modelChecked = true;
    }
    
    /**
     * v10.7: Holt Liste aller verfügbaren Ollama-Modelle (LOKAL)
     */
    private function getAvailableModelsFromOllama() {
        $models = [];
        try {
            $ch = curl_init($this->ollamaUrl . '/api/tags');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['models']) && count($data['models']) > 0) {
                    $models = array_column($data['models'], 'name');
                }
            }
        } catch (Exception $e) {
            $this->log("getAvailableModelsFromOllama error: " . $e->getMessage(), 'error');
        }
        return $models;
    }
    
    /**
     * v11.0: Prüft ob ein Modell ein Cloud-Modell ist
     * Cloud-Modelle haben größere Parameter (>7B) und sind in der Config gelistet
     */
    public function isCloudModel($model) {
        self::loadCloudConfig();
        return isset(self::$cloudModels[$model]);
    }
    
    /**
     * v11.0: Gibt Liste der Cloud-Modelle zurück
     */
    public static function getCloudModels() {
        self::loadCloudConfig();
        return self::$cloudModels;
    }
    
    /**
     * v11.0: Gibt Cloud-Konfiguration zurück (für Admin-Dashboard)
     */
    public static function getCloudConfig() {
        return self::loadCloudConfig();
    }
    
    /**
     * v10.7: Öffentliche Methode - Liste aller Ollama-Modelle (LOKAL)
     */
    public function getAvailableModels() {
        return $this->getAvailableModelsFromOllama();
    }
    
    /**
     * v11.0: Kombinierte Liste aller Modelle (Lokal + Cloud)
     * Wird vom Admin-Dashboard verwendet
     */
    public function getAllModels() {
        self::loadCloudConfig();
        
        $result = [
            'local' => [],
            'cloud' => [],
            'cloud_config' => self::$cloudConfig
        ];
        
        // Lokale Modelle
        $localModels = $this->getAvailableModelsFromOllama();
        foreach ($localModels as $model) {
            $result['local'][] = [
                'id' => $model,
                'name' => $model,
                'type' => 'local'
            ];
        }
        
        // Cloud-Modelle (nur empfohlene zuerst, dann Rest)
        $recommended = [];
        $others = [];
        
        foreach (self::$cloudModels as $id => $info) {
            $entry = [
                'id' => $id,
                'name' => $info['name'],
                'size' => $info['size'],
                'recommended' => $info['recommended'] ?? false,
                'type' => 'cloud'
            ];
            
            if ($info['recommended'] ?? false) {
                $recommended[] = $entry;
            } else {
                $others[] = $entry;
            }
        }
        
        $result['cloud'] = array_merge($recommended, $others);
        
        return $result;
    }
    
    /**
     * v10.7: Setzt das konfigurierte Model (statisch für Admin-Dashboard)
     * WICHTIG: Alle Bots lesen diese Config beim Start!
     */
    public static function setConfiguredModel($model) {
        $configDir = dirname(__FILE__) . '/AI/config';
        if (!file_exists($configDir)) {
            mkdir($configDir, 0777, true);
        }
        $result = file_put_contents($configDir . '/ollama_model.txt', trim($model));
        return $result !== false;
    }
    
    /**
     * v10.7: Liest das aktuell konfigurierte Model (statisch)
     */
    public static function getConfiguredModel() {
        $configFile = dirname(__FILE__) . '/AI/config/ollama_model.txt';
        if (file_exists($configFile)) {
            return trim(file_get_contents($configFile));
        }
        return null; // Kein Model konfiguriert → Auto-Detect
    }
    
    public function generateQuestion($module = 'mathematik', $difficulty = 5, $age = 10, $forceGenerate = false) {
        $startTime = microtime(true);
        $module = strtolower(trim($module));
        
        $this->log("generateQuestion START: module=$module, age=$age, force=$forceGenerate");
        
        if (!$forceGenerate) {
            $dbQuestion = $this->getQuestionFromDB($module, $age);
            if ($dbQuestion) {
                $this->log("Retrieved question from DB for $module (age $age)");
                return $dbQuestion;
            }
        }
        
        if ($this->availableModel) {
            $aiQuestion = $this->generateWithAI($module, $difficulty, $age);
            if ($aiQuestion) {
                $genTime = microtime(true) - $startTime;
                $this->saveQuestionToDB($module, $difficulty, $age, $aiQuestion, true, $genTime);
                $this->log("✅ Generated AI question for $module in " . round($genTime, 2) . "s");
                return $aiQuestion;
            }
        }
        
        $this->log("⚠️ Using emergency fallback for $module");
        $fallback = $this->getEmergencyFallback($module, $age);
        
        if (!$this->isQuestionInDB($fallback['q'], $module)) {
            $this->saveQuestionToDB($module, $difficulty, $age, $fallback, false, 0);
        }
        
        return $fallback;
    }
    
    private function generateWithAI($module, $difficulty, $age) {
        if (!$this->availableModel) {
            $this->log("generateWithAI: No model available!", 'error');
            return null;
        }
        
        $this->log("=== generateWithAI START: module=$module, age=$age ===", 'debug');
        
        $prompt = $this->createAgeAppropriatePrompt($module, $age, $difficulty);
        $this->log("PROMPT (first 300 chars): " . substr(str_replace("\n", " ", $prompt), 0, 300), 'debug');
        
        // v11.0: Dynamischer Timeout - Cloud-Modelle brauchen länger
        $timeout = $this->isCloudModel($this->availableModel) ? 180 : 90;
        $this->log("Using timeout: {$timeout}s for model: " . $this->availableModel, 'debug');
        
        try {
            $ch = curl_init($this->ollamaUrl . '/api/generate');
            
            $postData = json_encode([
                'model' => $this->availableModel,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'num_predict' => 400,  // v11.0: Erhöht für komplexere Antworten
                    'top_p' => 0.9,
                    'seed' => rand(1, 999999)
                ]
            ]);
            
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);  // v11.0: Dynamischer Timeout
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            $this->log("OLLAMA HTTP: $httpCode, Response Length: " . strlen($response) . " bytes", 'debug');
            
            if ($curlError) {
                $this->log("CURL ERROR: $curlError", 'error');
                return null;
            }
            
            if ($httpCode == 200 && $response) {
                $data = json_decode($response, true);
                
                if (isset($data['response'])) {
                    $this->log("OLLAMA RAW:\n" . $data['response'], 'debug');
                    
                    $parsed = $this->parseAIResponse($data['response']);
                    
                    if ($parsed) {
                        $this->log("PARSED: Q='" . substr($parsed['q'], 0, 50) . "...'", 'debug');
                        
                        if ($this->validateQuestion($parsed)) {
                            $this->log("=== generateWithAI SUCCESS ===", 'debug');
                            return $parsed;
                        } else {
                            $this->log("VALIDATION FAILED for parsed question", 'warning');
                        }
                    } else {
                        $this->log("PARSING RETURNED NULL - check parseAIResponse()", 'warning');
                    }
                } else {
                    $this->log("NO 'response' FIELD IN OLLAMA JSON", 'warning');
                }
            } else {
                $this->log("OLLAMA BAD RESPONSE: HTTP $httpCode", 'error');
            }
        } catch (Exception $e) {
            $this->log("EXCEPTION in generateWithAI: " . $e->getMessage(), 'error');
        }
        
        $this->log("=== generateWithAI FAILED - returning NULL ===", 'warning');
        return null;
    }
    
    /**
     * v10.9: Nutzt jetzt OptimizedPrompts-Klasse
     * Basierend auf den 750 CSV-Fragen optimiert
     */
    private function createAgeAppropriatePrompt($module, $age, $difficulty) {
        // v10.9: Neue optimierte Prompts verwenden
        if (class_exists('OptimizedPrompts')) {
            $prompt = OptimizedPrompts::getPrompt($module, $age);
            $this->log("Using OptimizedPrompts for $module (age $age)", 'debug');
            return $prompt;
        }
        
        // Fallback auf alte Methode falls Klasse nicht geladen
        $this->log("OptimizedPrompts not found, using legacy prompts", 'warning');
        $isGerman = !in_array($module, ['englisch', 'english']);
        
        if ($module == 'mathematik') {
            return $this->getMathPrompt($age, $difficulty);
        } elseif (in_array($module, ['englisch', 'english'])) {
            return $this->getEnglishPrompt($age, $difficulty);
        } else {
            return $this->getGeneralPrompt($module, $age, $isGerman);
        }
    }
    
    private function getMathPrompt($age, $difficulty) {
        $examples = [
            5 => "Addition bis 10: 3+2=5",
            7 => "Addition bis 20: 12+7=19",
            10 => "Multiplikation: 7×8=56",
            12 => "Brüche: 1/2 + 1/4 = 3/4",
            15 => "Algebra: 2x + 5 = 13, x=4"
        ];
        
        $ageKey = min(max($age, 5), 15);
        $example = $examples[$ageKey] ?? $examples[10];
        
        return "Create a math problem for age $age (difficulty $difficulty/10).
Example level: $example

IMPORTANT: Output MUST be in GERMAN language!

Format your response EXACTLY like this:
Q: [Question in German]
A: [Correct answer]
W1: [Wrong answer 1]
W2: [Wrong answer 2]
W3: [Wrong answer 3]

Example:
Q: Was ist 7 mal 8?
A: 56
W1: 54
W2: 58
W3: 48

Now create a NEW, DIFFERENT math question:";
    }
    
    private function getEnglishPrompt($age, $difficulty) {
        $topics = [
            5 => "colors, animals, numbers",
            7 => "family, school, days",
            10 => "hobbies, weather, time",
            12 => "grammar, tenses, vocabulary",
            15 => "idioms, complex grammar, literature"
        ];
        
        $ageKey = min(max($age, 5), 15);
        $topic = $topics[$ageKey] ?? $topics[10];
        
        return "Create an English learning question for age $age about: $topic.

The question should test English vocabulary or grammar.

Format your response EXACTLY like this:
Q: [Question in English]
A: [Correct answer]
W1: [Wrong answer 1]
W2: [Wrong answer 2]
W3: [Wrong answer 3]

Example:
Q: What color is the sky?
A: Blue
W1: Red
W2: Green
W3: Yellow

Now create a NEW question:";
    }
    
    private function getGeneralPrompt($module, $age, $isGerman = true) {
        $definitionsPath = __DIR__ . '/AI/module_definitions_english.json';
        
        if (file_exists($definitionsPath)) {
            $json = file_get_contents($definitionsPath);
            $definitions = json_decode($json, true);
            
            $this->log("JSON loaded, checking for module: $module", 'debug');
            
            if (isset($definitions[$module])) {
                $def = $definitions[$module];
                $this->log("Found definition for $module: {$def['name']}", 'debug');
                
                $outputLang = $def['output_language'] ?? 'GERMAN';
                
                $prompt = "You are a teacher creating a quiz question.\n\n";
                $prompt .= "SUBJECT: {$def['name']}\n";
                $prompt .= "DEFINITION: {$def['definition']}\n\n";
                
                $prompt .= "ALLOWED TOPICS:\n";
                foreach ($def['topics'] as $topic) {
                    $prompt .= "- $topic\n";
                }
                $prompt .= "\n";
                
                $prompt .= "FORBIDDEN TOPICS (NEVER use these!):\n";
                foreach ($def['NOT_topics'] as $not) {
                    $prompt .= "- $not\n";
                }
                $prompt .= "\n";
                
                if ($outputLang == 'GERMAN' && isset($def['examples_german'])) {
                    $prompt .= "EXAMPLE QUESTIONS (for inspiration):\n";
                    foreach (array_slice($def['examples_german'], 0, 2) as $ex) {
                        $prompt .= "- $ex\n";
                    }
                } elseif ($outputLang == 'ENGLISH' && isset($def['examples_english'])) {
                    $prompt .= "EXAMPLE QUESTIONS (for inspiration):\n";
                    foreach (array_slice($def['examples_english'], 0, 2) as $ex) {
                        $prompt .= "- $ex\n";
                    }
                }
                $prompt .= "\n";
                
                $prompt .= "=== YOUR TASK ===\n";
                $prompt .= "Create ONE quiz question about {$def['name']} for a $age year old student.\n\n";
                
                $prompt .= "OUTPUT FORMAT (follow EXACTLY):\n";
                if ($outputLang == 'GERMAN') {
                    $prompt .= "Q: [Your question IN GERMAN]\n";
                    $prompt .= "A: [Correct answer IN GERMAN]\n";
                    $prompt .= "W1: [Wrong answer IN GERMAN]\n";
                    $prompt .= "W2: [Wrong answer IN GERMAN]\n";
                    $prompt .= "W3: [Wrong answer IN GERMAN]\n\n";
                } else {
                    $prompt .= "Q: [Your question IN ENGLISH]\n";
                    $prompt .= "A: [Correct answer IN ENGLISH]\n";
                    $prompt .= "W1: [Wrong answer IN ENGLISH]\n";
                    $prompt .= "W2: [Wrong answer IN ENGLISH]\n";
                    $prompt .= "W3: [Wrong answer IN ENGLISH]\n\n";
                }
                
                $prompt .= "CRITICAL RULES:\n";
                $prompt .= "1. Question MUST be about {$def['name']} only!\n";
                $prompt .= "2. ALL text MUST be in {$outputLang}!\n";
                $prompt .= "3. NO placeholders like [answer] or {something}!\n";
                $prompt .= "4. Use REAL, concrete answers!\n";
                $prompt .= "5. Age-appropriate for $age years!\n\n";
                
                $prompt .= "Now generate ONE question:";
                
                return $prompt;
            } else {
                $this->log("Module '$module' NOT FOUND in JSON definitions!", 'warning');
            }
        } else {
            $this->log("JSON file not found: $definitionsPath", 'warning');
        }
        
        $this->log("Using hardcoded fallback prompt for: $module", 'warning');
        
        $fallbackPrompts = [
            'physik' => "Create a PHYSICS question IN GERMAN for age $age.

Physics is about: forces, energy, motion, light, sound, electricity, magnetism.

Format EXACTLY:
Q: [Physics question in German]
A: [Correct answer in German]
W1: [Wrong answer in German]
W2: [Wrong answer in German]
W3: [Wrong answer in German]

NEVER geography (capitals, countries)!
Example: Q: Was ist Schwerkraft?

Generate ONE physics question:",

            'kunst' => "Create an ART question IN GERMAN for age $age.

Art is about: colors, painting, drawing, famous artists, sculptures.

Format EXACTLY:
Q: [Art question in German]
A: [Correct answer in German]
W1: [Wrong answer in German]
W2: [Wrong answer in German]
W3: [Wrong answer in German]

Example: Q: Welche Farbe entsteht aus Rot und Blau?

Generate ONE art question:",

            'biologie' => "Create a BIOLOGY question IN GERMAN for age $age.

Biology is about: animals, plants, human body, cells, nature.

Format EXACTLY:
Q: [Biology question in German]
A: [Correct answer in German]
W1: [Wrong answer in German]
W2: [Wrong answer in German]
W3: [Wrong answer in German]

Example: Q: Wie viele Beine hat eine Spinne?

Generate ONE biology question:",

            'bitcoin' => "Create a BITCOIN question IN GERMAN for age $age.

Bitcoin basics: digital money, decentralized, only 21 million exist, no banks needed.

Format EXACTLY:
Q: [Bitcoin question in German]
A: [Correct answer in German]
W1: [Wrong answer in German]
W2: [Wrong answer in German]
W3: [Wrong answer in German]

Example: Q: Wie viele Bitcoin gibt es maximal?

Generate ONE Bitcoin question:"
        ];
        
        if (isset($fallbackPrompts[$module])) {
            return $fallbackPrompts[$module];
        }
        
        $lang = $isGerman ? "GERMAN" : "ENGLISH";
        $subject = ucfirst($module);
        
        return "Create a $subject question IN $lang for age $age.

Format EXACTLY:
Q: [Question in $lang]
A: [Correct answer in $lang]
W1: [Wrong answer in $lang]
W2: [Wrong answer in $lang]
W3: [Wrong answer in $lang]

No placeholders! Real answers only!

Generate ONE question:";
    }

    private function getAgeLevel($age) {
        if ($age <= 6) return 'kindergarten';
        if ($age <= 10) return 'elementary';
        if ($age <= 14) return 'middle';
        return 'high';
    }
    
    /**
     * Parse AI Response - v11.1 MIT ERKLÄRUNGSFELD
     * Angepasst an das neue Q:/A:/B:/C:/D:/E: Format
     */
    private function parseAIResponse($response) {
        $this->log("parseAIResponse INPUT length: " . strlen($response), 'debug');
        
        $response = trim($response);
        $response = preg_replace('/```[a-z]*\n?/', '', $response);
        $response = preg_replace('/```/', '', $response);
        
        // v10.9: Wenn Antwort direkt mit der Frage beginnt (ohne Q:), präfix hinzufügen
        if (!preg_match('/^\s*(Q|Frage|Question)\s*:/i', $response)) {
            $response = 'Q: ' . $response;
            $this->log("Added Q: prefix to response", 'debug');
        }
        
        $question = '';
        $answer = '';
        $wrong = [];
        $erklaerung = '';  // v11.1: Erklärungsfeld
        
        $lines = preg_split('/\r\n|\r|\n/', $response);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (preg_match('/^(?:Q|Frage|Question|F)\s*[:\-]\s*(.+)$/iu', $line, $matches)) {
                $question = trim($matches[1]);
                $this->log("Found Q: " . substr($question, 0, 50), 'debug');
            }
            elseif (preg_match('/^(?:A|Antwort|Answer|Correct|Richtig|R)\s*[:\-]\s*(.+)$/iu', $line, $matches)) {
                $answer = trim($matches[1]);
                $this->log("Found A: $answer", 'debug');
            }
            // v11.1: Erklärung erkennen (E: oder Erklärung:)
            elseif (preg_match('/^(?:E|Erklärung|Explanation|Warum)\s*[:\-]\s*(.+)$/iu', $line, $matches)) {
                $erklaerung = trim($matches[1]);
                $this->log("Found E: " . substr($erklaerung, 0, 50), 'debug');
            }
            // v4.2: Auch B, C, D als Alternativen erkennen (neben W1, W2, W3)
            elseif (preg_match('/^(?:W\d?|Wrong\s*\d?|Falsch\s*\d?|F\d|B|C|D)\s*[:\-]\s*(.+)$/iu', $line, $matches)) {
                $wrongAnswer = trim($matches[1]);
                if (!empty($wrongAnswer)) {
                    $wrong[] = $wrongAnswer;
                    $this->log("Found alternative: $wrongAnswer", 'debug');
                }
            }
        }
        
        if (empty($question) || empty($answer) || count($wrong) < 3) {
            $this->log("Line-based parsing incomplete, trying regex method", 'debug');
            
            if (empty($question)) {
                if (preg_match('/(?:Q|Frage|Question)[:\-]\s*(.+?)(?=\n\s*(?:A|Antwort|Answer|W\d|Wrong|Falsch)[:\-]|$)/si', $response, $m)) {
                    $question = trim($m[1]);
                    $this->log("Regex found Q: " . substr($question, 0, 50), 'debug');
                }
            }
            
            if (empty($answer)) {
                if (preg_match('/(?:A|Antwort|Answer|Correct)[:\-]\s*(.+?)(?=\n\s*(?:W\d|Wrong|Falsch)[:\-]|$)/si', $response, $m)) {
                    $answer = trim($m[1]);
                    $this->log("Regex found A: $answer", 'debug');
                }
            }
            
            // v4.2: Auch B, C, D im Regex-Fallback
            if (count($wrong) < 3) {
                if (preg_match_all('/(?:W\d|Wrong\s*\d?|Falsch\s*\d?|B|C|D)[:\-]\s*(.+?)(?=\n|$)/si', $response, $matches)) {
                    foreach ($matches[1] as $w) {
                        $w = trim($w);
                        if (!empty($w) && !in_array($w, $wrong)) {
                            $wrong[] = $w;
                        }
                    }
                    $this->log("Regex found " . count($wrong) . " alternatives", 'debug');
                }
            }
        }
        
        $this->log("Parse result: Q=" . (bool)$question . " A=" . (bool)$answer . " W=" . count($wrong), 'debug');
        
        if ($question && $answer && count($wrong) >= 3) {
            $question = trim($question, '"\'');
            $answer = trim($answer, '"\'');
            $wrong = array_map(function($w) { return trim($w, '"\''); }, $wrong);
            
            $options = array_merge([$answer], array_slice($wrong, 0, 3));
            shuffle($options);
            
            $result = [
                'q' => $question,
                'a' => $answer,
                'options' => $options,
                'erklaerung' => $erklaerung  // v11.1: Erklärung hinzufügen
            ];
            
            $this->log("✅ parseAIResponse SUCCESS (mit Erklärung: " . (!empty($erklaerung) ? 'JA' : 'NEIN') . ")", 'debug');
            return $result;
        }
        
        $this->log("❌ parseAIResponse FAILED - insufficient data", 'warning');
        return null;
    }
    
    /**
     * Validiere generierte Frage - v10.5 WENIGER AGGRESSIV
     */
    private function validateQuestion($q) {
        if (!isset($q['q']) || !isset($q['a']) || !isset($q['options'])) {
            $this->log("Validation failed: Missing required fields", 'warning');
            return false;
        }
        
        if (count($q['options']) < 4) {
            $this->log("Validation failed: Only " . count($q['options']) . " options (need 4)", 'warning');
            return false;
        }
        
        $placeholderPatterns = [
            '/^\[.+\]$/',
            '/^\{.+\}$/',
            '/^<.+>$/',
            '/^(Option|Wrong|Falsch|W)\s*\d*$/i',
            '/^placeholder$/i',
            '/^todo$/i',
            '/^\[.*(answer|antwort|option|wrong|falsch|richtig|correct).*\]$/i'
        ];
        
        foreach ($q['options'] as $idx => $option) {
            $option = trim($option);
            
            if (empty($option)) {
                $this->log("Validation failed: Option $idx is empty", 'warning');
                return false;
            }
            
            if (strlen($option) < 1) {
                $this->log("Validation failed: Option $idx too short", 'warning');
                return false;
            }
            
            foreach ($placeholderPatterns as $pattern) {
                if (preg_match($pattern, $option)) {
                    $this->log("Validation failed: Placeholder detected in option: $option", 'warning');
                    return false;
                }
            }
        }
        
        if (!in_array($q['a'], $q['options'])) {
            $this->log("Validation failed: Answer not in options array", 'warning');
            return false;
        }
        
        if (strlen($q['q']) < 10) {
            $this->log("Validation failed: Question too short (" . strlen($q['q']) . " chars)", 'warning');
            return false;
        }
        
        $this->log("✅ Validation passed", 'debug');
        return true;
    }
    
    private function getQuestionFromDB($module, $age) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare('
                SELECT question, answer, options, erklaerung 
                FROM questions 
                WHERE module = :module 
                AND :age BETWEEN age_min AND age_max
                ORDER BY RANDOM() 
                LIMIT 1
            ');
            
            $stmt->bindValue(':module', $module, SQLITE3_TEXT);
            $stmt->bindValue(':age', $age, SQLITE3_INTEGER);
            
            $result = $stmt->execute();
            if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $escapedQ = SQLite3::escapeString($row['question']);
                $this->db->exec("UPDATE questions SET times_used = times_used + 1 WHERE question = '$escapedQ'");
                
                return [
                    'q' => $row['question'],
                    'a' => $row['answer'],
                    'options' => json_decode($row['options'], true),
                    'erklaerung' => $row['erklaerung'] ?? ''  // v11.1
                ];
            }
        } catch (Exception $e) {
            $this->log("DB fetch error: " . $e->getMessage(), 'error');
        }
        
        return null;
    }
    
    /**
     * v10.8: Generiert Hash für Duplikat-Erkennung (kompatibel mit CSV-Import)
     */
    private function generateQuestionHash($question, $options) {
        $data = strtolower(trim($question));
        if (is_array($options)) {
            foreach ($options as $opt) {
                $data .= '|' . strtolower(trim($opt));
            }
        }
        return md5($data);
    }
    
    private function saveQuestionToDB($module, $difficulty, $age, $question, $isAI = false, $genTime = 0) {
        if (!$this->db || !$question) return;
        
        try {
            if ($this->isQuestionInDB($question['q'], $module)) {
                $this->log("Question already exists, skipping: " . substr($question['q'], 0, 50) . "...");
                return;
            }
            
            // v10.8: Hash generieren für Konsistenz mit CSV-Import
            $hash = $this->generateQuestionHash($question['q'], $question['options']);
            
            $stmt = $this->db->prepare('
                INSERT INTO questions (module, difficulty, age_min, age_max, question, answer, options, erklaerung, ai_generated, model_used, generation_time, question_hash, source)
                VALUES (:module, :difficulty, :age_min, :age_max, :question, :answer, :options, :erklaerung, :ai, :model, :time, :hash, :source)
            ');
            
            $ageMin = max(5, $age - 2);
            $ageMax = min(15, $age + 2);
            
            $stmt->bindValue(':module', $module, SQLITE3_TEXT);
            $stmt->bindValue(':difficulty', $difficulty, SQLITE3_INTEGER);
            $stmt->bindValue(':age_min', $ageMin, SQLITE3_INTEGER);
            $stmt->bindValue(':age_max', $ageMax, SQLITE3_INTEGER);
            $stmt->bindValue(':question', $question['q'], SQLITE3_TEXT);
            $stmt->bindValue(':answer', $question['a'], SQLITE3_TEXT);
            $stmt->bindValue(':options', json_encode($question['options']), SQLITE3_TEXT);
            $stmt->bindValue(':erklaerung', $question['erklaerung'] ?? null, SQLITE3_TEXT);  // v11.1
            $stmt->bindValue(':ai', $isAI ? 1 : 0, SQLITE3_INTEGER);
            $stmt->bindValue(':model', $isAI ? $this->availableModel : null, SQLITE3_TEXT);
            $stmt->bindValue(':time', $genTime, SQLITE3_FLOAT);
            $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
            $stmt->bindValue(':source', $isAI ? 'ai_generated' : 'fallback', SQLITE3_TEXT);
            
            $stmt->execute();
            
            $aiLabel = $isAI ? "🤖 AI" : "📋 Fallback";
            $this->log("✅ NEW question saved to DB ($aiLabel): " . substr($question['q'], 0, 50) . "...");
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $this->log("Duplicate question prevented: " . substr($question['q'], 0, 50) . "...");
            } else {
                $this->log("DB save error: " . $e->getMessage(), 'error');
            }
        }
    }
    
    private function isQuestionInDB($question, $module) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare('SELECT id FROM questions WHERE question = :q AND module = :m LIMIT 1');
            $stmt->bindValue(':q', $question, SQLITE3_TEXT);
            $stmt->bindValue(':m', $module, SQLITE3_TEXT);
            $result = $stmt->execute();
            
            return ($result->fetchArray() !== false);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Emergency Fallback - v11.1 MIT ERKLÄRUNGEN
     */
    private function getEmergencyFallback($module, $age) {
        $questions = [
            'mathematik' => [
                5 => ['q' => 'Was ist 2 + 2?', 'a' => '4', 'options' => ['3', '4', '5', '6'], 'erklaerung' => 'Wenn du 2 Finger hast und noch 2 dazu nimmst, hast du 4 Finger.'],
                7 => ['q' => 'Was ist 3 × 4?', 'a' => '12', 'options' => ['10', '12', '14', '16'], 'erklaerung' => '3 mal 4 bedeutet 4+4+4, und das ergibt 12.'],
                10 => ['q' => 'Was ist 5 × 6?', 'a' => '30', 'options' => ['25', '30', '35', '40'], 'erklaerung' => '5 mal 6 ist wie 5 sechser Gruppen, also 30.'],
                12 => ['q' => 'Was ist 144 ÷ 12?', 'a' => '12', 'options' => ['10', '12', '14', '16'], 'erklaerung' => '144 geteilt durch 12 ergibt 12, denn 12×12=144.'],
                15 => ['q' => 'Was ist x wenn 2x = 10?', 'a' => '5', 'options' => ['3', '4', '5', '6'], 'erklaerung' => 'Teile beide Seiten durch 2: x = 10÷2 = 5.']
            ],
            'physik' => [
                5 => ['q' => 'Was zieht dich nach unten wenn du springst?', 'a' => 'Die Schwerkraft', 'options' => ['Der Wind', 'Die Schwerkraft', 'Die Luft', 'Der Boden'], 'erklaerung' => 'Die Erde zieht alles zu sich, das nennt man Schwerkraft.'],
                7 => ['q' => 'Was ist schneller: Licht oder Schall?', 'a' => 'Licht', 'options' => ['Schall', 'Licht', 'Beide gleich', 'Keines'], 'erklaerung' => 'Licht reist mit 300.000 km pro Sekunde, Schall nur 343 m/s.'],
                10 => ['q' => 'Was braucht Licht um von A nach B zu kommen?', 'a' => 'Nichts, es ist am schnellsten', 'options' => ['Ein Kabel', 'Nichts, es ist am schnellsten', 'Wasser', 'Strom'], 'erklaerung' => 'Licht braucht kein Medium und ist das Schnellste im Universum.'],
                12 => ['q' => 'Was ist die Einheit für Kraft?', 'a' => 'Newton', 'options' => ['Watt', 'Newton', 'Volt', 'Ampere'], 'erklaerung' => 'Newton ist nach Isaac Newton benannt, der die Gravitationsgesetze entdeckte.'],
                15 => ['q' => 'Was ist die Einheit für elektrische Spannung?', 'a' => 'Volt', 'options' => ['Ampere', 'Volt', 'Watt', 'Ohm'], 'erklaerung' => 'Volt misst die elektrische Spannung, benannt nach Alessandro Volta.']
            ],
            'kunst' => [
                5 => ['q' => 'Welche Farbe entsteht aus Rot und Gelb?', 'a' => 'Orange', 'options' => ['Grün', 'Orange', 'Lila', 'Braun']],
                7 => ['q' => 'Welche Farbe entsteht aus Rot und Blau?', 'a' => 'Lila', 'options' => ['Grün', 'Orange', 'Lila', 'Braun']],
                10 => ['q' => 'Wer malte die Mona Lisa?', 'a' => 'Leonardo da Vinci', 'options' => ['Picasso', 'Leonardo da Vinci', 'Van Gogh', 'Rembrandt']],
                12 => ['q' => 'Was sind die drei Primärfarben?', 'a' => 'Rot, Gelb, Blau', 'options' => ['Rot, Grün, Blau', 'Rot, Gelb, Blau', 'Rot, Orange, Gelb', 'Blau, Grün, Gelb']],
                15 => ['q' => 'Was ist ein Portrait?', 'a' => 'Ein Bild einer Person', 'options' => ['Ein Landschaftsbild', 'Ein Bild einer Person', 'Ein abstraktes Bild', 'Ein Stillleben']]
            ],
            'biologie' => [
                5 => ['q' => 'Wie viele Beine hat eine Spinne?', 'a' => '8', 'options' => ['6', '8', '4', '10']],
                7 => ['q' => 'Was trinken Pflanzen?', 'a' => 'Wasser', 'options' => ['Milch', 'Wasser', 'Saft', 'Limonade']],
                10 => ['q' => 'Was brauchen Pflanzen zum Wachsen?', 'a' => 'Licht und Wasser', 'options' => ['Nur Erde', 'Licht und Wasser', 'Nur Luft', 'Feuer']],
                12 => ['q' => 'Wie viele Knochen hat ein erwachsener Mensch?', 'a' => 'Etwa 206', 'options' => ['Etwa 100', 'Etwa 206', 'Etwa 350', 'Etwa 500']],
                15 => ['q' => 'Wie heißt der Prozess wenn Pflanzen Sonnenlicht nutzen?', 'a' => 'Photosynthese', 'options' => ['Zellteilung', 'Photosynthese', 'Verdauung', 'Atmung']]
            ],
            'chemie' => [
                5 => ['q' => 'Was ist Wasser: fest, flüssig oder gasförmig?', 'a' => 'Flüssig', 'options' => ['Fest', 'Flüssig', 'Gasförmig', 'Alles davon']],
                7 => ['q' => 'Was passiert wenn Wasser gefriert?', 'a' => 'Es wird zu Eis', 'options' => ['Es verschwindet', 'Es wird zu Eis', 'Es wird zu Dampf', 'Es wird bunt']],
                10 => ['q' => 'Woraus besteht Wasser chemisch?', 'a' => 'Wasserstoff und Sauerstoff', 'options' => ['Nur Sauerstoff', 'Wasserstoff und Sauerstoff', 'Kohlenstoff', 'Stickstoff']],
                12 => ['q' => 'Was ist das chemische Symbol für Gold?', 'a' => 'Au', 'options' => ['Go', 'Au', 'Gd', 'Ag']],
                15 => ['q' => 'Was ist H2O?', 'a' => 'Wasser', 'options' => ['Salz', 'Wasser', 'Kohlendioxid', 'Zucker']]
            ],
            'erdkunde' => [
                5 => ['q' => 'Was ist die Hauptstadt von Deutschland?', 'a' => 'Berlin', 'options' => ['München', 'Berlin', 'Hamburg', 'Köln']],
                7 => ['q' => 'Wie heißt der größte Ozean?', 'a' => 'Pazifik', 'options' => ['Atlantik', 'Pazifik', 'Indischer Ozean', 'Arktis']],
                10 => ['q' => 'Auf welchem Kontinent liegt Deutschland?', 'a' => 'Europa', 'options' => ['Asien', 'Europa', 'Amerika', 'Afrika']],
                12 => ['q' => 'Wie viele Kontinente gibt es?', 'a' => '7', 'options' => ['5', '6', '7', '8']],
                15 => ['q' => 'Was ist der längste Fluss Europas?', 'a' => 'Wolga', 'options' => ['Donau', 'Wolga', 'Rhein', 'Elbe']]
            ],
            'geschichte' => [
                5 => ['q' => 'Wer waren die Ritter?', 'a' => 'Kämpfer im Mittelalter', 'options' => ['Piraten', 'Kämpfer im Mittelalter', 'Astronauten', 'Köche']],
                7 => ['q' => 'Wo lebten die alten Ägypter?', 'a' => 'Am Nil in Afrika', 'options' => ['In Europa', 'Am Nil in Afrika', 'In Amerika', 'In Australien']],
                10 => ['q' => 'Wann endete der Zweite Weltkrieg?', 'a' => '1945', 'options' => ['1918', '1945', '1989', '1914']],
                12 => ['q' => 'Wer war der erste deutsche Bundeskanzler?', 'a' => 'Konrad Adenauer', 'options' => ['Willy Brandt', 'Konrad Adenauer', 'Helmut Kohl', 'Ludwig Erhard']],
                15 => ['q' => 'Wann fiel die Berliner Mauer?', 'a' => '1989', 'options' => ['1945', '1961', '1989', '1990']]
            ],
            'musik' => [
                5 => ['q' => 'Mit welchem Instrument macht man Musik mit Tasten?', 'a' => 'Klavier', 'options' => ['Gitarre', 'Klavier', 'Trompete', 'Geige']],
                7 => ['q' => 'Wie viele Saiten hat eine Gitarre?', 'a' => '6', 'options' => ['4', '6', '8', '10']],
                10 => ['q' => 'Welches Instrument hat 88 Tasten?', 'a' => 'Klavier', 'options' => ['Gitarre', 'Klavier', 'Trompete', 'Geige']],
                12 => ['q' => 'Wie viele Noten gibt es in einer Oktave?', 'a' => '8', 'options' => ['6', '7', '8', '12']],
                15 => ['q' => 'Wer komponierte die 9. Symphonie mit der Ode an die Freude?', 'a' => 'Beethoven', 'options' => ['Mozart', 'Beethoven', 'Bach', 'Haydn']]
            ],
            'computer' => [
                5 => ['q' => 'Womit klickt man am Computer?', 'a' => 'Maus', 'options' => ['Tastatur', 'Maus', 'Bildschirm', 'Drucker']],
                7 => ['q' => 'Womit tippt man am Computer?', 'a' => 'Tastatur', 'options' => ['Maus', 'Tastatur', 'Bildschirm', 'Lautsprecher']],
                10 => ['q' => 'Wofür steht CPU?', 'a' => 'Central Processing Unit', 'options' => ['Computer Power Unit', 'Central Processing Unit', 'Computer Program Use', 'Central Power Unit']],
                12 => ['q' => 'Was ist RAM?', 'a' => 'Arbeitsspeicher', 'options' => ['Festplatte', 'Arbeitsspeicher', 'Grafikkarte', 'Prozessor']],
                15 => ['q' => 'Was ist ein Browser?', 'a' => 'Programm zum Surfen im Internet', 'options' => ['Ein Spiel', 'Programm zum Surfen im Internet', 'Ein Drucker', 'Eine Tastatur']]
            ],
            'programmieren' => [
                5 => ['q' => 'Was macht ein Computer mit Code?', 'a' => 'Er führt Befehle aus', 'options' => ['Er isst ihn', 'Er führt Befehle aus', 'Er malt ihn', 'Er singt ihn']],
                7 => ['q' => 'Was ist ein Programm?', 'a' => 'Anweisungen für den Computer', 'options' => ['Ein Bild', 'Anweisungen für den Computer', 'Ein Lied', 'Ein Spiel']],
                10 => ['q' => 'Was ist eine Variable?', 'a' => 'Ein Speicherplatz für Werte', 'options' => ['Ein Spiel', 'Ein Speicherplatz für Werte', 'Ein Computer', 'Eine Taste']],
                12 => ['q' => 'Welche Programmiersprache hat eine Schlange als Logo?', 'a' => 'Python', 'options' => ['Java', 'Python', 'JavaScript', 'C++']],
                15 => ['q' => 'Was macht eine For-Schleife?', 'a' => 'Wiederholt Code mehrmals', 'options' => ['Beendet das Programm', 'Wiederholt Code mehrmals', 'Startet den Computer', 'Speichert Daten']]
            ],
            'bitcoin' => [
                5 => ['q' => 'Was ist Bitcoin?', 'a' => 'Digitales Geld', 'options' => ['Eine Münze', 'Digitales Geld', 'Ein Spiel', 'Ein Computer']],
                7 => ['q' => 'Kann man Bitcoin anfassen?', 'a' => 'Nein, es ist digital', 'options' => ['Ja', 'Nein, es ist digital', 'Manchmal', 'Nur mit Handschuhen']],
                10 => ['q' => 'Wie viele Bitcoin gibt es maximal?', 'a' => '21 Millionen', 'options' => ['100 Millionen', '21 Millionen', 'Unendlich', '1 Million']],
                12 => ['q' => 'Braucht man eine Bank für Bitcoin?', 'a' => 'Nein, Bitcoin ist dezentral', 'options' => ['Ja immer', 'Nein, Bitcoin ist dezentral', 'Nur manchmal', 'Nur in Deutschland']],
                15 => ['q' => 'Was ist ein Bitcoin-Wallet?', 'a' => 'Eine digitale Brieftasche', 'options' => ['Eine echte Geldbörse', 'Eine digitale Brieftasche', 'Ein Bankschalter', 'Ein Computer']]
            ],
            'steuern' => [
                5 => ['q' => 'Was sind Steuern?', 'a' => 'Geld für den Staat', 'options' => ['Spielgeld', 'Geld für den Staat', 'Geschenke', 'Süßigkeiten']],
                7 => ['q' => 'Wer bekommt die Steuern?', 'a' => 'Der Staat', 'options' => ['Die Bank', 'Der Staat', 'Die Nachbarn', 'Der Supermarkt']],
                10 => ['q' => 'Wofür werden Steuern verwendet?', 'a' => 'Schulen, Straßen, Krankenhäuser', 'options' => ['Nur für Politiker', 'Schulen, Straßen, Krankenhäuser', 'Für nichts', 'Nur fürs Militär']],
                12 => ['q' => 'Was ist Mehrwertsteuer?', 'a' => 'Steuer auf gekaufte Waren', 'options' => ['Steuer auf Arbeit', 'Steuer auf gekaufte Waren', 'Steuer auf Gewinne', 'Steuer auf Häuser']],
                15 => ['q' => 'Was ist Einkommensteuer?', 'a' => 'Steuer auf verdientes Geld', 'options' => ['Steuer auf Essen', 'Steuer auf verdientes Geld', 'Steuer auf Spiele', 'Steuer auf Benzin']]
            ],
            'englisch' => [
                5 => ['q' => 'What color is the sky?', 'a' => 'Blue', 'options' => ['Red', 'Blue', 'Green', 'Yellow']],
                7 => ['q' => 'What is "Hund" in English?', 'a' => 'Dog', 'options' => ['Cat', 'Dog', 'Bird', 'Fish']],
                10 => ['q' => 'How do you say "Danke" in English?', 'a' => 'Thank you', 'options' => ['Hello', 'Thank you', 'Goodbye', 'Please']],
                12 => ['q' => 'What is the plural of "child"?', 'a' => 'Children', 'options' => ['Childs', 'Children', 'Childes', 'Child']],
                15 => ['q' => 'What is the past tense of "go"?', 'a' => 'Went', 'options' => ['Goed', 'Went', 'Gone', 'Going']]
            ],
            'lesen' => [
                5 => ['q' => 'Welcher Buchstabe kommt nach A?', 'a' => 'B', 'options' => ['C', 'B', 'D', 'E']],
                7 => ['q' => 'Wie viele Buchstaben hat das Alphabet?', 'a' => '26', 'options' => ['24', '25', '26', '27']],
                10 => ['q' => 'Wie viele Silben hat das Wort "Banane"?', 'a' => '3', 'options' => ['2', '3', '4', '5']],
                12 => ['q' => 'Was ist ein Nomen?', 'a' => 'Ein Namenwort', 'options' => ['Ein Tunwort', 'Ein Namenwort', 'Ein Wiewort', 'Ein Bindewort']],
                15 => ['q' => 'Was ist ein Verb?', 'a' => 'Ein Tunwort/Tätigkeitswort', 'options' => ['Ein Namenwort', 'Ein Tunwort/Tätigkeitswort', 'Ein Eigenschaftswort', 'Ein Fürwort']]
            ],
            'wissenschaft' => [
                5 => ['q' => 'Warum fällt ein Apfel vom Baum?', 'a' => 'Wegen der Schwerkraft', 'options' => ['Wegen dem Wind', 'Wegen der Schwerkraft', 'Weil er reif ist', 'Weil er schwer ist']],
                7 => ['q' => 'Warum regnet es?', 'a' => 'Wolken werden zu schwer', 'options' => ['Wolken weinen', 'Wolken werden zu schwer', 'Der Himmel ist traurig', 'Wegen der Sonne']],
                10 => ['q' => 'Warum ist der Himmel blau?', 'a' => 'Wegen der Lichtstreuung', 'options' => ['Wegen dem Meer', 'Wegen der Lichtstreuung', 'Weil Gott es so wollte', 'Wegen den Wolken']],
                12 => ['q' => 'Was ist ein Experiment?', 'a' => 'Ein wissenschaftlicher Versuch', 'options' => ['Ein Spiel', 'Ein wissenschaftlicher Versuch', 'Ein Wunsch', 'Ein Traum']],
                15 => ['q' => 'Was ist die wissenschaftliche Methode?', 'a' => 'Beobachten, Hypothese, Testen, Schlussfolgern', 'options' => ['Raten', 'Beobachten, Hypothese, Testen, Schlussfolgern', 'Nur lesen', 'Nur rechnen']]
            ],
            'verkehr' => [
                5 => ['q' => 'Was tust du bei einer roten Ampel?', 'a' => 'Stehen bleiben', 'options' => ['Schnell laufen', 'Stehen bleiben', 'Langsam gehen', 'Tanzen']],
                7 => ['q' => 'Wer hat am Zebrastreifen Vorrang?', 'a' => 'Fußgänger', 'options' => ['Autos', 'Fußgänger', 'Fahrräder', 'Niemand']],
                10 => ['q' => 'Was bedeutet ein Stoppschild?', 'a' => 'Anhalten und schauen', 'options' => ['Schneller fahren', 'Anhalten und schauen', 'Hupen', 'Blinken']],
                12 => ['q' => 'Wie schnell darf man innerorts maximal fahren?', 'a' => '50 km/h', 'options' => ['30 km/h', '50 km/h', '70 km/h', '100 km/h']],
                15 => ['q' => 'Ab wann darf man begleitet Auto fahren?', 'a' => '17 Jahre', 'options' => ['16 Jahre', '17 Jahre', '18 Jahre', '15 Jahre']]
            ],
            'unnuetzes_wissen' => [
                5 => ['q' => 'Welches Tier kann nicht rückwärts laufen?', 'a' => 'Känguru', 'options' => ['Hund', 'Känguru', 'Katze', 'Elefant']],
                7 => ['q' => 'Wie viele Nasen hat eine Schnecke?', 'a' => '4', 'options' => ['1', '2', '4', 'Keine']],
                10 => ['q' => 'Welche Farbe hat das Blut eines Oktopus?', 'a' => 'Blau', 'options' => ['Rot', 'Blau', 'Grün', 'Gelb']],
                12 => ['q' => 'Was ist das einzige Lebensmittel das niemals verdirbt?', 'a' => 'Honig', 'options' => ['Salz', 'Honig', 'Reis', 'Zucker']],
                15 => ['q' => 'Wie viel Prozent der DNA teilen Menschen mit Bananen?', 'a' => '60%', 'options' => ['10%', '30%', '60%', '0%']]
            ],
            'sport' => [
                5 => ['q' => 'Wie viele Spieler hat eine Fußballmannschaft?', 'a' => '11', 'options' => ['9', '10', '11', '12']],
                7 => ['q' => 'Was braucht man zum Schwimmen?', 'a' => 'Wasser', 'options' => ['Einen Ball', 'Wasser', 'Schuhe', 'Einen Schläger']],
                10 => ['q' => 'Wie viele Ringe hat das Olympia-Symbol?', 'a' => '5', 'options' => ['3', '4', '5', '6']],
                12 => ['q' => 'Was ist ein Elfmeter?', 'a' => 'Ein Strafstoß vom Elfmeterpunkt', 'options' => ['Ein Freistoß', 'Ein Strafstoß vom Elfmeterpunkt', 'Ein Einwurf', 'Ein Eckball']],
                15 => ['q' => 'Welches Land hat die meisten Fußball-WM Titel?', 'a' => 'Brasilien', 'options' => ['Deutschland', 'Argentinien', 'Brasilien', 'Italien']]
            ],
            'default' => [
                'q' => 'Was ist 2 + 2?',
                'a' => '4',
                'options' => ['3', '4', '5', '6'],
                'erklaerung' => '2 plus 2 ergibt 4, zähle 2 Finger und nochmal 2 dazu.'
            ]
        ];
        
        if (isset($questions[$module])) {
            foreach ([15, 12, 10, 7, 5] as $maxAge) {
                if ($age >= $maxAge && isset($questions[$module][$maxAge])) {
                    $this->log("Fallback: Using $module question for age $maxAge", 'debug');
                    return $questions[$module][$maxAge];
                }
            }
            return reset($questions[$module]);
        }
        
        $this->log("Fallback: Module '$module' not found, using default", 'warning');
        return $questions['default'];
    }
    
    public function generateBatch($module, $count, $minAge = 5, $maxAge = 15, $forceGenerate = true) {
        $results = ['generated' => 0, 'failed' => 0, 'from_db' => 0, 'time_total' => 0];
        $startTime = microtime(true);
        
        for ($i = 0; $i < $count; $i++) {
            $age = rand($minAge, $maxAge);
            $difficulty = $this->getDifficultyForAge($age);
            $question = $this->generateQuestion($module, $difficulty, $age, $forceGenerate);
            
            if ($question) {
                $results['generated']++;
            } else {
                $results['failed']++;
            }
            usleep(100000);
        }
        
        $results['time_total'] = microtime(true) - $startTime;
        $this->log("Batch generation completed: " . json_encode($results));
        return $results;
    }
    
    private function getDifficultyForAge($age) {
        if ($age <= 7) return 3;
        if ($age <= 10) return 5;
        if ($age <= 13) return 7;
        return 9;
    }
    
    public function getStatistics() {
        if (!$this->db) return [];
        
        try {
            $stats = [];
            $result = $this->db->query('SELECT COUNT(*) as total FROM questions');
            $stats['total_questions'] = $result->fetchArray()['total'];
            
            $result = $this->db->query('SELECT COUNT(*) as ai FROM questions WHERE ai_generated = 1');
            $stats['ai_questions'] = $result->fetchArray()['ai'];
            
            $result = $this->db->query('SELECT module, COUNT(*) as cnt, SUM(ai_generated) as ai_cnt FROM questions GROUP BY module');
            $stats['by_module'] = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $stats['by_module'][$row['module']] = ['total' => $row['cnt'], 'ai' => $row['ai_cnt']];
            }
            
            $result = $this->db->query('SELECT AVG(generation_time) as avg_time FROM questions WHERE ai_generated = 1 AND generation_time > 0');
            $stats['avg_generation_time'] = round($result->fetchArray()['avg_time'] ?? 0, 2);
            
            return $stats;
        } catch (Exception $e) {
            $this->log("Stats error: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    private function log($message, $level = 'info') {
        if ($level == 'debug' && !$this->debugMode) return;
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";
        
        if ($this->logFile) {
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        }
        
        if ($level == 'error') {
            error_log($logMessage);
        }
    }
    
    public function setDebugMode($enabled) {
        $this->debugMode = $enabled;
        $this->log("Debug mode: " . ($enabled ? "ENABLED" : "DISABLED"));
    }
    
    public function checkOllama() { return $this->availableModel !== null; }
    
    public function getOllamaStatus() {
        $configuredModel = self::getConfiguredModel();
        return [
            'running' => $this->availableModel !== null, 
            'model' => $this->availableModel, 
            'configured' => $configuredModel,  // v10.7: Zeigt konfiguriertes Model
            'is_configured' => ($configuredModel !== null && $configuredModel === $this->availableModel),
            'available_models' => $this->getAvailableModels(),
            'recommended' => 'tinyllama:latest (schneller)'
        ];
    }
    
    public function clearDatabase() {
        if (!$this->db) return false;
        try {
            $this->db->exec('DELETE FROM questions');
            $this->log("⚠️ Database cleared!");
            return true;
        } catch (Exception $e) {
            $this->log("Clear DB error: " . $e->getMessage(), 'error');
            return false;
        }
    }
}

// TEST-UI
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>sgiT AI Generator v<?= SGIT_VERSION ?> 🚀</title>
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <style>
        body { padding: 0; }
        .container { max-width: 1200px; margin: 0 auto; background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 40px; }
        h1 { color: var(--accent); border-bottom: 3px solid var(--accent); padding-bottom: 10px; }
        h2 { color: var(--accent); }
        .status-box { padding: 20px; border-radius: 10px; margin: 20px 0; font-weight: bold; }
        .status-online { background: rgba(40, 167, 69, 0.2); color: #6cff6c; border: 1px solid rgba(40, 167, 69, 0.4); }
        .status-offline { background: rgba(220, 53, 69, 0.2); color: #ff6b6b; border: 1px solid rgba(220, 53, 69, 0.4); }
        button { background: var(--accent); color: #000; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-size: 16px; margin: 5px; font-weight: 600; }
        button:hover { background: var(--accent-hover); }
        button.danger { background: var(--danger); color: white; }
        button.danger:hover { background: #c82333; }
        .result { background: rgba(0,0,0,0.3); padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid var(--accent); border: 1px solid var(--border); }
        .result.success { border-left-color: #28a745; }
        .result.warning { border-left-color: #ffc107; }
        .question { font-size: 24px; margin-bottom: 20px; color: var(--text); }
        .options { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .option { padding: 15px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 8px; text-align: center; color: var(--text); }
        .option.correct { background: rgba(40, 167, 69, 0.2); border-color: #28a745; font-weight: bold; color: #6cff6c; }
        select, input { padding: 10px; font-size: 16px; margin: 10px; border: 1px solid var(--border); border-radius: 5px; background: rgba(0,0,0,0.3); color: var(--text); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .stat-card { background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 15px; border-radius: 8px; text-align: center; }
        .stat-value { font-size: 32px; font-weight: bold; color: var(--accent); }
        .stat-label { color: var(--text-muted); font-size: 14px; }
        .version-badge { background: var(--accent); color: #000; padding: 5px 15px; border-radius: 20px; font-size: 14px; margin-left: 10px; }
        .fix-badge { background: rgba(40, 167, 69, 0.3); color: #6cff6c; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        pre { background: rgba(0,0,0,0.5); color: #f8f8f2; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 12px; border: 1px solid var(--border); }
    </style>
</head>
<body>
<!-- Generator Navigation Bar (TODO-008) -->
<nav style="background: rgba(0,0,0,0.4); padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(67, 210, 64, 0.3);">
    <div style="display: flex; align-items: center; gap: 10px; color: white;">
        <span style="font-weight: bold;">sgiT Generator</span>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="/windows_ai_generator.php" style="padding: 8px 14px; background: #43D240; color: #000; text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 500;">🤖 AI Generator</a>
        <a href="/questions/generate_module_csv.php" style="padding: 8px 14px; background: rgba(255,255,255,0.1); color: white; text-decoration: none; border-radius: 6px; font-size: 0.85rem;">📝 CSV Generator</a>
        <a href="/batch_import.php" style="padding: 8px 14px; background: rgba(255,255,255,0.1); color: white; text-decoration: none; border-radius: 6px; font-size: 0.85rem;">📥 CSV Import</a>
        <a href="/admin_v4.php" style="padding: 8px 14px; background: rgba(255,255,255,0.1); color: white; text-decoration: none; border-radius: 6px; font-size: 0.85rem;">🏠 Admin</a>
    </div>
</nav>

<div class="container" style="margin-top: 25px;">
    <h1>🤖 sgiT AI Question Generator <span class="version-badge">v10.6 PERFORMANCE</span></h1>
    <p>
        <span class="fix-badge">✅ FIX #1</span> Flexibleres Parsing
        <span class="fix-badge">✅ FIX #2</span> Bessere Validation
        <span class="fix-badge">✅ FIX #3</span> Alle 15 Module Fallbacks
        <span class="fix-badge">✅ FIX #4</span> Debug-Logging
    </p>
    
    <?php
    $generator = new AIQuestionGeneratorComplete();
    $status = $generator->getOllamaStatus();
    $stats = $generator->getStatistics();
    ?>
    
    <div class="status-box <?php echo $status['running'] ? 'status-online' : 'status-offline'; ?>">
        <?php if ($status['running']): ?>
            ✅ Ollama läuft mit Modell: <strong><?php echo htmlspecialchars($status['model']); ?></strong><br>KI-Generierung aktiv!
        <?php else: ?>
            ⚠️ Ollama nicht gefunden!<br><small>Starte Ollama: <code>ollama pull llama3.2</code></small>
        <?php endif; ?>
    </div>
    
    <div class="stats">
        <div class="stat-card"><div class="stat-label">Gesamt Fragen</div><div class="stat-value"><?php echo $stats['total_questions'] ?? 0; ?></div></div>
        <div class="stat-card"><div class="stat-label">KI-Generiert</div><div class="stat-value"><?php echo $stats['ai_questions'] ?? 0; ?></div></div>
        <div class="stat-card"><div class="stat-label">Ø Gen-Zeit</div><div class="stat-value"><?php echo $stats['avg_generation_time'] ?? 0; ?>s</div></div>
    </div>
    
    <h2>🧪 Test-Generierung</h2>
    <form method="post">
        <label>Modul: <select name="module">
            <option value="mathematik">🔢 Mathematik</option>
            <option value="lesen">📖 Lesen</option>
            <option value="englisch">🇬🇧 Englisch</option>
            <option value="wissenschaft">🔬 Wissenschaft</option>
            <option value="erdkunde">🌍 Erdkunde</option>
            <option value="chemie">⚗️ Chemie</option>
            <option value="physik" selected>⚛️ Physik</option>
            <option value="kunst">🎨 Kunst</option>
            <option value="musik">🎵 Musik</option>
            <option value="computer">💻 Computer</option>
            <option value="programmieren">👨‍💻 Programmieren</option>
            <option value="bitcoin">₿ Bitcoin</option>
            <option value="geschichte">📚 Geschichte</option>
            <option value="biologie">🧬 Biologie</option>
            <option value="steuern">💰 Steuern</option>
            <option value="verkehr">🚦 Verkehr</option>
            <option value="unnuetzes_wissen">🤯 Unnützes Wissen</option>
            <option value="sport">🏃 Sport</option>
        </select></label>
        <label>Alter: <input type="number" name="age" min="5" max="15" value="10"></label>
        <br><br>
        <button type="submit" name="generate_single">🎲 Eine Frage generieren (Force AI)</button>
        <button type="submit" name="generate_batch">📦 10 Fragen generieren</button>
        <button type="submit" name="clear_db" class="danger">🗑️ DB leeren</button>
    </form>
    
    <?php
    if (isset($_POST['clear_db'])) { $generator->clearDatabase(); echo '<div class="result warning">⚠️ Datenbank wurde geleert!</div>'; }
    
    if (isset($_POST['generate_single'])) {
        $module = $_POST['module'] ?? 'physik';
        $age = intval($_POST['age'] ?? 10);
        $startTime = microtime(true);
        $question = $generator->generateQuestion($module, 5, $age, true);
        $genTime = round(microtime(true) - $startTime, 2);
        
        if ($question) {
            $isModuleSpecific = (stripos($question['q'], 'hauptstadt') === false && stripos($question['q'], 'capital') === false);
            $resultClass = $isModuleSpecific ? 'success' : 'warning';
            echo '<div class="result ' . $resultClass . '">';
            echo '<small>Modul: ' . htmlspecialchars($module) . ' | Zeit: ' . $genTime . 's</small>';
            echo '<div class="question">📝 ' . htmlspecialchars($question['q']) . '</div>';
            echo '<div class="options">';
            foreach ($question['options'] as $opt) {
                $class = ($opt === $question['a']) ? 'option correct' : 'option';
                echo '<div class="' . $class . '">' . htmlspecialchars($opt) . '</div>';
            }
            echo '</div>';
            echo $isModuleSpecific ? '<p style="color:green;">✅ Modul-spezifische Frage!</p>' : '<p style="color:orange;">⚠️ Fallback - prüfe Logs</p>';
            echo '</div>';
        }
    }
    
    if (isset($_POST['generate_batch'])) {
        $results = $generator->generateBatch($_POST['module'] ?? 'physik', 10, intval($_POST['age'] ?? 10) - 2, intval($_POST['age'] ?? 10) + 2, true);
        echo '<div class="result"><h3>📦 Batch fertig!</h3><p>✅ ' . $results['generated'] . ' | ❌ ' . $results['failed'] . ' | ⏱️ ' . round($results['time_total'], 2) . 's</p></div>';
    }
    ?>
    
    <h2>📋 Logs (letzte 20 Zeilen)</h2>
    <pre><?php
    $logFile = __DIR__ . '/AI/logs/generator.log';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        echo htmlspecialchars(implode('', array_slice($lines, -20)));
    } else {
        echo "Noch keine Logs.";
    }
    ?></pre>
</div>
</body>
</html>
<?php } ?>
