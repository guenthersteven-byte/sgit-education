<?php
/**
 * ClaudeClient - Anthropic Claude API Client fuer Fragengenerierung
 *
 * Nutzt die Anthropic Messages API um Quiz-Fragen zu generieren.
 * Parsing im gleichen Format wie auto_generator.php (Q:/A:/W1:/W2:/W3:/E:)
 */
class ClaudeClient {

    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $apiVersion = '2023-06-01';
    private ?array $moduleDefinitions = null;
    private ?array $lastUsage = null;

    public function __construct(string $apiKey, string $model = 'claude-sonnet-4-5-20250929') {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    /**
     * Testet ob die API erreichbar ist und der Key gueltig
     */
    public function testConnection(): array {
        $response = $this->callAPI(
            'Du bist ein Test-Assistent.',
            'Antworte nur mit: OK',
            50
        );

        if ($response['success']) {
            return ['online' => true, 'model' => $this->model];
        }

        return ['online' => false, 'error' => $response['error'] ?? 'Unbekannter Fehler'];
    }

    /**
     * Generiert Quiz-Fragen fuer ein Modul
     */
    public function generate(string $module, int $count = 5, int $ageMin = 8, int $ageMax = 12, int $difficulty = 3): array {
        $moduleDef = $this->getModuleDefinition($module);

        if (!$moduleDef) {
            return ['success' => false, 'error' => "Modul '{$module}' nicht gefunden", 'questions' => []];
        }

        $systemPrompt = $this->buildSystemPrompt($moduleDef);
        $userPrompt = $this->buildUserPrompt($moduleDef, $count, $ageMin, $ageMax, $difficulty);

        $maxTokens = min(4096, $count * 300);
        $response = $this->callAPI($systemPrompt, $userPrompt, $maxTokens);

        if (!$response['success']) {
            return ['success' => false, 'error' => $response['error'], 'questions' => []];
        }

        $questions = $this->parseQuestions($response['content']);

        return [
            'success' => count($questions) > 0,
            'questions' => $questions,
            'raw_count' => count($questions),
            'usage' => $this->lastUsage,
            'cost_estimate' => $this->estimateCost()
        ];
    }

    /**
     * Speichert Fragen in die SQLite-Datenbank
     */
    public function saveToDatabase(string $dbPath, string $module, array $questions, int $difficulty, int $ageMin, int $ageMax): int {
        $db = new SQLite3($dbPath);
        $db->exec("PRAGMA journal_mode=WAL");
        $db->exec("PRAGMA busy_timeout=5000");
        $saved = 0;

        foreach ($questions as $q) {
            $allAnswers = array_merge([$q['correct']], $q['wrong']);
            sort($allAnswers);
            $hash = md5(strtolower($q['question']) . '|' . implode('|', array_map('strtolower', $allAnswers)));

            // Duplikat-Check
            $stmt = $db->prepare("SELECT id FROM questions WHERE question_hash = :hash");
            $stmt->bindValue(':hash', $hash);
            $result = $stmt->execute();
            if ($result->fetchArray()) {
                continue;
            }

            // Antworten mischen
            $options = array_merge([$q['correct']], array_slice($q['wrong'], 0, 3));
            shuffle($options);
            $correctIndex = array_search($q['correct'], $options);

            $stmt = $db->prepare("INSERT INTO questions
                (module, question, answer, correct_answer, options, explanation, difficulty,
                 age_min, age_max, ai_generated, question_hash, source, is_active, created_at)
                VALUES (:module, :question, :answer, :correct, :options, :explanation, :diff,
                        :min, :max, 1, :hash, 'claude_generator', 1, datetime('now'))");

            $stmt->bindValue(':module', $module);
            $stmt->bindValue(':question', $q['question']);
            $stmt->bindValue(':answer', chr(65 + $correctIndex));
            $stmt->bindValue(':correct', $q['correct']);
            $stmt->bindValue(':options', json_encode($options));
            $stmt->bindValue(':explanation', $q['explanation'] ?? '');
            $stmt->bindValue(':diff', $difficulty);
            $stmt->bindValue(':min', $ageMin);
            $stmt->bindValue(':max', $ageMax);
            $stmt->bindValue(':hash', $hash);

            $result = $stmt->execute();
            if ($result !== false) {
                $saved++;
            }
        }

        $db->close();
        return $saved;
    }

    /**
     * Gibt Token-Usage der letzten Anfrage zurueck
     */
    public function getLastUsage(): ?array {
        return $this->lastUsage;
    }

    // =========================================================================
    // PRIVATE METHODEN
    // =========================================================================

    private function callAPI(string $systemPrompt, string $userPrompt, int $maxTokens = 4096): array {
        $payload = [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt]
            ]
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . $this->apiVersion
            ],
            CURLOPT_TIMEOUT => 120
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'cURL: ' . $curlError];
        }

        $json = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMsg = $json['error']['message'] ?? "HTTP {$httpCode}";
            return ['success' => false, 'error' => $errorMsg];
        }

        if (!isset($json['content'][0]['text'])) {
            return ['success' => false, 'error' => 'Keine Textantwort erhalten'];
        }

        $this->lastUsage = $json['usage'] ?? null;

        return [
            'success' => true,
            'content' => $json['content'][0]['text']
        ];
    }

    private function getModuleDefinition(string $module): ?array {
        if ($this->moduleDefinitions === null) {
            $path = __DIR__ . '/../AI/module_definitions.json';
            if (file_exists($path)) {
                $this->moduleDefinitions = json_decode(file_get_contents($path), true) ?? [];
            } else {
                $this->moduleDefinitions = [];
            }
        }

        return $this->moduleDefinitions[$module] ?? null;
    }

    /**
     * Gibt alle verfuegbaren Module zurueck
     */
    public function getModules(): array {
        if ($this->moduleDefinitions === null) {
            $this->getModuleDefinition('_init');
        }
        return $this->moduleDefinitions;
    }

    private function buildSystemPrompt(array $moduleDef): string {
        $topics = implode("\n- ", $moduleDef['topics'] ?? []);
        $notTopics = implode("\n- ", $moduleDef['NOT_topics'] ?? []);

        return "Du bist ein Quiz-Ersteller fuer 'sgiT Education', eine Lernplattform fuer Kinder und Jugendliche.

Modul: {$moduleDef['name']}
Definition: {$moduleDef['definition']}

Erlaubte Themen:
- {$topics}

VERBOTEN (gehoert zu anderen Modulen):
- {$notTopics}

QUALITAETSREGELN:
- Alle Fakten muessen korrekt sein - KEINE Erfindungen!
- Falsche Antworten muessen plausibel klingen, aber eindeutig falsch sein
- Fragen muessen lehrreich und interessant sein
- Erklaerungen helfen beim Lernen (kurz aber informativ)
- Sprache: Deutsch
- Keine Umlaute verwenden (ae statt ae, oe statt oe, ue statt ue, ss statt ss)";
    }

    private function buildUserPrompt(array $moduleDef, int $count, int $ageMin, int $ageMax, int $difficulty): string {
        $examples = '';
        if (!empty($moduleDef['examples'])) {
            $examples = "\nBeispiel-Fragen (als Orientierung):\n- " . implode("\n- ", array_slice($moduleDef['examples'], 0, 3));
        }

        $diffDesc = match($difficulty) {
            1 => 'Sehr leicht - Grundwissen, einfache Fakten',
            2 => 'Leicht - Basiswissen mit etwas Nachdenken',
            3 => 'Mittel - Solides Schulwissen erforderlich',
            4 => 'Schwer - Vertieftes Wissen, Zusammenhaenge erkennen',
            5 => 'Sehr schwer - Expertenwissen, komplexe Zusammenhaenge',
            default => 'Mittel'
        };

        return "Generiere genau {$count} Quiz-Fragen zum Thema \"{$moduleDef['name']}\".

Zielgruppe: {$ageMin}-{$ageMax} Jahre
Schwierigkeit: Stufe {$difficulty}/5 ({$diffDesc})
{$examples}

ANTWORT-FORMAT (EXAKT einhalten, eine Frage nach der anderen):

Q: [Frage auf Deutsch]
A: [Richtige Antwort - kurz und praezise]
W1: [Plausible falsche Antwort 1]
W2: [Plausible falsche Antwort 2]
W3: [Plausible falsche Antwort 3]
E: [Erklaerung warum die Antwort richtig ist, max 80 Zeichen]

Generiere jetzt genau {$count} Fragen:";
    }

    private function parseQuestions(string $text): array {
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

    private function estimateCost(): ?string {
        if (!$this->lastUsage) return null;

        $inputTokens = $this->lastUsage['input_tokens'] ?? 0;
        $outputTokens = $this->lastUsage['output_tokens'] ?? 0;

        // Sonnet pricing: $3/1M input, $15/1M output
        $cost = ($inputTokens * 3 / 1000000) + ($outputTokens * 15 / 1000000);

        return '$' . number_format($cost, 4);
    }
}
