<?php
/**
 * ============================================================================
 * sgiT Education - Foxy Chat Manager v1.2
 * ============================================================================
 * 
 * FIXES v1.2:
 * - Username statt "Kind" verwenden
 * - Bessere Modul-Erkennung
 * - Chat-Historie in DB speichern
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.2
 * @date 04.12.2025
 * ============================================================================
 */

class ClippyChat {
    
    private $ollamaUrl = 'http://localhost:11434/api/generate';
    private $model = 'tinyllama:latest';
    private $timeout = 30;
    private $maxHistoryLength = 4;
    private $db = null;
    
    public function __construct() {
        $this->model = 'tinyllama:latest';
        $this->initDatabase();
    }
    
    /**
     * Initialisiert die Datenbank für Chat-Historie
     */
    private function initDatabase() {
        try {
            $dbPath = __DIR__ . '/../database/foxy_chat.db';
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Tabelle für häufige Fragen/Antworten
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS foxy_responses (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    category TEXT NOT NULL,
                    trigger_words TEXT NOT NULL,
                    response TEXT NOT NULL,
                    usage_count INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Chat-Historie pro User
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS foxy_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_name TEXT,
                    user_message TEXT NOT NULL,
                    foxy_response TEXT NOT NULL,
                    module TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Standard-Antworten einfügen falls leer
            $this->seedDefaultResponses();
            
        } catch (Exception $e) {
            error_log("[FoxyChat] DB Error: " . $e->getMessage());
        }
    }
    
    /**
     * Fügt Standard-Antworten ein
     */
    private function seedDefaultResponses() {
        $count = $this->db->query("SELECT COUNT(*) FROM foxy_responses")->fetchColumn();
        if ($count > 0) return;
        
        $defaults = [
            // Witze (10 Stück)
            ['joke', 'witz,lustig,lachen', 'Warum können Füchse so gut in der Schule? Weil sie immer schlau sind! 🦊😄'],
            ['joke', 'witz,lustig,lachen', 'Was macht ein Fuchs am Computer? Er surft im Fuchsbook! 💻🦊'],
            ['joke', 'witz,lustig,lachen', 'Warum tragen Füchse keine Brillen? Weil sie schon Fuchs-Augen haben! 👀😂'],
            ['joke', 'witz,lustig,lachen', 'Was ist orange und kann rechnen? Ein Mathe-Fuchs! 🧮🦊'],
            ['joke', 'witz,lustig,lachen', 'Wie nennt man einen Fuchs, der Klavier spielt? Wolfgang Amadeus Fuchs! 🎹🦊'],
            ['joke', 'witz,lustig,lachen', 'Warum ging der Fuchs zur Schule? Um schlauer als die anderen zu werden! 📚🦊'],
            ['joke', 'witz,lustig,lachen', 'Was sagt ein Fuchs wenn er fertig ist? FUCHSTASTISCH! 🎉🦊'],
            ['joke', 'witz,lustig,lachen', 'Warum sind Füchse so gute Detektive? Sie haben einen Riecher! 🔍🦊'],
            ['joke', 'witz,lustig,lachen', 'Was ist das Lieblingsfach vom Fuchs? Fuchs-ik! ⚛️🦊'],
            ['joke', 'witz,lustig,lachen', 'Warum tanzt der Fuchs so gern? Er hat den Fox-Trott erfunden! 💃🦊'],
            
            // Aufmunterung (10 Stück)
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Kopf hoch! 💪 Jeder macht mal Fehler - so lernt man! Du schaffst das! 🦊🌟'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Du bist toll! 🌈 Auch wenn es schwer ist - ich glaube an dich! 🦊❤️'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Füchse geben nie auf! 🦊💪 Und du auch nicht! Weiter so!'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Das wird schon! 🌟 Kleine Schritte führen auch zum Ziel! 🦊'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Ich bin stolz auf dich! 🦊 Dass du es versuchst, ist schon super! 💪'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Ein Schritt nach dem anderen! Du rockst das! 🎸🦊'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Füchse fallen 7 mal hin und stehen 8 mal auf! 💪🦊'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Übung macht den Meister-Fuchs! 🏆🦊'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Du hast das Zeug zum Champion! 🥇🦊'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Du bist schlauer als du denkst! 🧠✨'],
            
            // Tipps (8 Stück)
            ['tip', 'tipp,rat,hilfe,erkläre,wie geht', '💡 Du bekommst Sats für richtige Antworten! Je mehr du lernst, desto mehr verdienst du! 🦊₿'],
            ['tip', 'tipp,rat,hilfe,erkläre,wie geht', '💡 Probier verschiedene Fächer aus! Abwechslung macht schlau! 📚🦊'],
            ['tip', 'tipp,rat,hilfe,erkläre,wie geht', '💡 Nach 10 Fragen bekommst du eine Zusammenfassung mit Belohnungen! 🎉'],
            ['tip', 'tipp,rat,hilfe,erkläre,wie geht', '💡 Lies die Frage immer zweimal! 📖🦊'],
            ['tip', 'tipp,rat,hilfe,erkläre,wie geht', '💡 Nutze den 50/50 Joker wenn du unsicher bist! 🦊'],
            ['tip', 'tipp,rat,hilfe,erkläre,wie geht', '💡 Jeden Tag 10 Fragen = Super Fortschritt! 📈🦊'],
            ['tip', 'tipp,rat,hilfe,erkläre,wie geht', '💡 Mach Pausen - dein Gehirn braucht sie! 🧠🦊'],
            ['tip', 'tipp,rat,hilfe,erkläre,wie geht', '💡 Falsche Antworten zeigen dir was du noch lernen kannst! 📚🦊'],
            
            // Danke
            ['thanks', 'danke,super,cool,toll,klasse', 'Gern geschehen! Du bist super! 🌟🦊'],
            ['thanks', 'danke,super,cool,toll,klasse', 'Immer für dich da! 🦊❤️'],
            ['thanks', 'danke,super,cool,toll,klasse', 'Das freut mich! Weiter so! 💪🦊'],
            ['thanks', 'danke,super,cool,toll,klasse', 'Füchse helfen gern! 🦊✨'],
            
            // Bitcoin
            ['bitcoin', 'bitcoin,sats,geld,wallet', '₿ Bitcoin ist digitales Geld! Lerne mehr im Bitcoin-Modul! 🦊💰'],
            ['bitcoin', 'bitcoin,sats,geld,wallet', '₿ Mit Sats kannst du später echtes Bitcoin bekommen! 🦊💰'],
            
            // Langweile / Motivation
            ['motivate', 'langeweile,langweilig,keine lust,was soll', '{name}Komm, nur noch eine Frage! Du schaffst das! 💪🦊'],
            ['motivate', 'langeweile,langweilig,keine lust,was soll', '{name}Was hältst du von einem Witz zur Auflockerung? 😄🦊'],
            ['motivate', 'langeweile,langweilig,keine lust,was soll', '{name}Mach 5 Minuten Pause und dann gehts weiter! ☕🦊'],
            ['motivate', 'langeweile,langweilig,keine lust,was soll', '{name}Bereit zum Lernen? Wähl oben ein Fach aus! 🦊💪'],
        ];
        
        $stmt = $this->db->prepare("INSERT INTO foxy_responses (category, trigger_words, response) VALUES (?, ?, ?)");
        foreach ($defaults as $row) {
            $stmt->execute($row);
        }
    }
    
    /**
     * Holt eine Antwort aus der Datenbank
     */
    private function getResponseFromDB(string $message, ?string $userName = null): ?string {
        $msg = strtolower($message);
        
        try {
            $stmt = $this->db->query("SELECT id, category, trigger_words, response FROM foxy_responses ORDER BY usage_count DESC");
            $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $matches = [];
            
            foreach ($responses as $row) {
                $triggers = explode(',', $row['trigger_words']);
                foreach ($triggers as $trigger) {
                    if (strpos($msg, trim($trigger)) !== false) {
                        $matches[] = $row;
                        break;
                    }
                }
            }
            
            if (!empty($matches)) {
                // Zufällige Antwort aus Matches
                $selected = $matches[array_rand($matches)];
                
                // Usage Count erhöhen
                $this->db->exec("UPDATE foxy_responses SET usage_count = usage_count + 1 WHERE id = " . $selected['id']);
                
                // {name} ersetzen
                $response = $selected['response'];
                if ($userName) {
                    $response = str_replace('{name}', $userName . ', ', $response);
                } else {
                    $response = str_replace('{name}', '', $response);
                }
                
                return $response;
            }
            
        } catch (Exception $e) {
            error_log("[FoxyChat] DB Query Error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Speichert Chat in Historie
     */
    private function saveChatHistory(string $userMessage, string $foxyResponse, ?string $userName = null, ?string $module = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO foxy_history (user_name, user_message, foxy_response, module) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userName, $userMessage, $foxyResponse, $module]);
        } catch (Exception $e) {
            error_log("[FoxyChat] Save History Error: " . $e->getMessage());
        }
    }
    
    /**
     * System-Prompt mit Username
     */
    private function buildSystemPrompt(int $age, ?string $userName = null, ?string $module = null): string {
        $style = $age <= 10 ? 'sehr einfache Sprache, kurze Sätze' : 'freundliche, lockere Sprache';
        $nameInfo = $userName ? "Der Name des Kindes ist {$userName}. Sprich es mit Namen an!" : "Du weißt den Namen nicht.";
        $moduleInfo = $module ? "Das Kind lernt gerade: {$module}" : "Das Kind hat noch kein Fach ausgewählt.";
        
        return <<<PROMPT
Du bist Foxy, ein freundlicher Fuchs-Assistent für Kinder auf einer Lernplattform.

REGELN:
1. Antworte IMMER auf Deutsch
2. Sei lustig, freundlich und ermutigend
3. Halte Antworten KURZ (max 2-3 Sätze)
4. Nutze Emojis 🦊
5. Verwende {$style}
6. {$nameInfo}

KONTEXT:
- Alter: {$age} Jahre
- {$moduleInfo}

DEINE AUFGABEN:
- Witze erzählen
- Kinder aufmuntern
- Tipps zur Lernplattform geben
- Zum Lernen motivieren
PROMPT;
    }
    
    /**
     * Chat mit Foxy - mit Username Support
     */
    public function chat(string $message, int $age, ?string $module = null, ?string $userName = null, ?string $currentQuestion = null, array $history = []): array {
        try {
            // Erst aus DB suchen (schnell!)
            $dbResponse = $this->getResponseFromDB($message, $userName);
            if ($dbResponse) {
                // In Historie speichern
                $this->saveChatHistory($message, $dbResponse, $userName, $module);
                
                return [
                    'success' => true,
                    'message' => $dbResponse,
                    'source' => 'database'
                ];
            }
            
            // AI für komplexere Anfragen
            $systemPrompt = $this->buildSystemPrompt($age, $userName, $module);
            
            $conversationContext = "";
            if (!empty($history)) {
                $conversationContext = "\n\nLetzte Nachrichten:\n";
                foreach (array_slice($history, -$this->maxHistoryLength) as $entry) {
                    $role = $entry['role'] === 'user' ? ($userName ?: 'Kind') : 'Foxy';
                    $conversationContext .= "{$role}: {$entry['content']}\n";
                }
            }
            
            $userLabel = $userName ?: 'Kind';
            $fullPrompt = $systemPrompt . $conversationContext . "\n\n{$userLabel}: " . $message . "\n\nFoxy:";
            
            $response = $this->callOllama($fullPrompt);
            
            if ($response['success']) {
                $cleanResponse = $this->cleanResponse($response['text'], $userName);
                
                // In Historie speichern
                $this->saveChatHistory($message, $cleanResponse, $userName, $module);
                
                return [
                    'success' => true,
                    'message' => $cleanResponse,
                    'source' => 'ai',
                    'model' => $this->model
                ];
            } else {
                // Fallback
                $fallback = $this->getFallbackResponse($message, $userName);
                $this->saveChatHistory($message, $fallback, $userName, $module);
                
                return [
                    'success' => true,
                    'message' => $fallback,
                    'source' => 'fallback'
                ];
            }
            
        } catch (Exception $e) {
            error_log("[FoxyChat] Error: " . $e->getMessage());
            return [
                'success' => true,
                'message' => $this->getFallbackResponse($message, $userName),
                'source' => 'error'
            ];
        }
    }
    
    /**
     * Ruft Ollama API auf
     */
    private function callOllama(string $prompt): array {
        $payload = json_encode([
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.8,
                'num_predict' => 100,
                'top_p' => 0.9
            ]
        ]);
        
        $ch = curl_init($this->ollamaUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200 || $error) {
            return ['success' => false, 'error' => $error];
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['response'])) {
            return ['success' => true, 'text' => $data['response']];
        }
        
        return ['success' => false, 'error' => 'Invalid response'];
    }
    
    /**
     * Bereinigt die AI-Antwort
     */
    private function cleanResponse(string $response, ?string $userName = null): string {
        $response = preg_replace('/^(Foxy:|Antwort:)\s*/i', '', trim($response));
        
        // Ersetze "Kind" durch den echten Namen wenn vorhanden
        if ($userName) {
            $response = preg_replace('/\bKind\b/i', $userName, $response);
        }
        
        if (strlen($response) > 200) {
            $response = substr($response, 0, 197) . '...';
        }
        
        return trim($response);
    }
    
    /**
     * Fallback-Antworten mit Username
     */
    private function getFallbackResponse(string $message, ?string $userName = null): string {
        $namePrefix = $userName ? "{$userName}, " : '';
        
        $responses = [
            "{$namePrefix}hmm, interessant! 🦊 Frag mich nach einem Witz oder Tipp!",
            "{$namePrefix}das ist eine gute Frage! 🦊 Probier mal die Buttons unten!",
            "{$namePrefix}cool! 🦊 Ich kann dir Witze erzählen oder Tipps geben!"
        ];
        return $responses[array_rand($responses)];
    }
    
    /**
     * Prüft Ollama Status
     */
    public function checkOllamaStatus(): array {
        $ch = curl_init('http://localhost:11434/api/tags');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $models = array_column($data['models'] ?? [], 'name');
            
            return [
                'online' => true,
                'model' => $this->model,
                'available_models' => $models
            ];
        }
        
        return ['online' => false, 'error' => 'Ollama nicht erreichbar'];
    }
    
    /**
     * Begrüßungsnachricht mit Username und Modul
     */
    public function getGreeting(int $age, ?string $module = null, ?string $userName = null): string {
        $nameGreeting = $userName ? "Hey {$userName}!" : "Hey!";
        
        // Kein Modul → zum Lernen motivieren
        if (!$module) {
            $motivations = [
                "{$nameGreeting} 🦊 Bereit zum Lernen? Wähl oben ein Fach aus und leg los! Du schaffst das! 💪",
                "{$nameGreeting} 🌟 Schön, dass du da bist! Such dir ein Fach aus und sammle Punkte! 🎯",
                "{$nameGreeting} 🦊 Lust auf ein Quiz? Klick oben auf ein Fach und zeig was du kannst! 🚀"
            ];
            return $motivations[array_rand($motivations)];
        }
        
        // Modul aktiv → darauf eingehen
        $moduleNames = [
            'mathematik' => 'Mathe', 'physik' => 'Physik', 'chemie' => 'Chemie',
            'biologie' => 'Bio', 'erdkunde' => 'Erdkunde', 'geschichte' => 'Geschichte',
            'kunst' => 'Kunst', 'musik' => 'Musik', 'computer' => 'Computer',
            'programmieren' => 'Programmieren', 'bitcoin' => 'Bitcoin', 'steuern' => 'Finanzen',
            'englisch' => 'Englisch', 'lesen' => 'Lesen', 'wissenschaft' => 'Wissenschaft',
            'verkehr' => 'Verkehr', 'unnuetzes_wissen' => 'Unnützes Wissen',
            'sport' => 'Sport'
        ];
        
        $moduleName = $moduleNames[strtolower($module)] ?? $module;
        
        $greetings = [
            "{$nameGreeting} 🦊 Ich sehe, du lernst {$moduleName}! Brauchst du einen Tipp? 💡",
            "{$nameGreeting} 🌟 {$moduleName} ist super! Bei Fragen bin ich hier! 🦊",
            "{$nameGreeting} 🦊 Cool, {$moduleName}! Wenn's mal schwer wird, frag mich! 💪"
        ];
        
        return $greetings[array_rand($greetings)];
    }
    
    /**
     * Fügt eine neue Antwort zur Datenbank hinzu
     */
    public function addResponse(string $category, string $triggers, string $response): bool {
        try {
            $stmt = $this->db->prepare("INSERT INTO foxy_responses (category, trigger_words, response) VALUES (?, ?, ?)");
            return $stmt->execute([$category, $triggers, $response]);
        } catch (Exception $e) {
            error_log("[FoxyChat] Add Response Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Holt Chat-Statistiken
     */
    public function getStats(): array {
        try {
            $totalChats = $this->db->query("SELECT COUNT(*) FROM foxy_history")->fetchColumn();
            $totalResponses = $this->db->query("SELECT COUNT(*) FROM foxy_responses")->fetchColumn();
            $topCategory = $this->db->query("SELECT category, SUM(usage_count) as total FROM foxy_responses GROUP BY category ORDER BY total DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_chats' => $totalChats,
                'total_responses' => $totalResponses,
                'top_category' => $topCategory['category'] ?? 'N/A'
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
