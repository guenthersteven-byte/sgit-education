<?php
/**
 * ClaudeClient - Anthropic Claude API Client fuer Fragengenerierung
 *
 * Nutzt die Anthropic Messages API um Quiz-Fragen zu generieren.
 * Qualitaetssicherung: JSON-Output, Validierung, Duplikat-Erkennung,
 * bestehende Fragen als Kontext, Anti-Pattern Prompting.
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
            'Antworte nur mit dem Wort: OK',
            50
        );

        if ($response['success']) {
            return ['online' => true, 'model' => $this->model];
        }

        return ['online' => false, 'error' => $response['error'] ?? 'Unbekannter Fehler'];
    }

    /**
     * Generiert Quiz-Fragen fuer ein Modul mit Qualitaetssicherung
     */
    public function generate(string $module, int $count = 5, int $ageMin = 8, int $ageMax = 12, int $difficulty = 3): array {
        $moduleDef = $this->getModuleDefinition($module);

        if (!$moduleDef) {
            return ['success' => false, 'error' => "Modul '{$module}' nicht gefunden", 'questions' => []];
        }

        // Bestehende Fragen laden um Wiederholungen zu vermeiden
        $existingQuestions = $this->loadExistingQuestions($module, 10);

        $systemPrompt = $this->buildSystemPrompt($moduleDef);
        $userPrompt = $this->buildUserPrompt($moduleDef, $count, $ageMin, $ageMax, $difficulty, $existingQuestions);

        $maxTokens = min(4096, $count * 350);
        $response = $this->callAPI($systemPrompt, $userPrompt, $maxTokens);

        if (!$response['success']) {
            return ['success' => false, 'error' => $response['error'], 'questions' => []];
        }

        $questions = $this->parseQuestions($response['content']);

        // Qualitaetspruefung fuer jede Frage
        $questions = $this->validateQuestions($questions, $module);

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

    /**
     * Gibt alle verfuegbaren Module zurueck
     */
    public function getModules(): array {
        if ($this->moduleDefinitions === null) {
            $this->getModuleDefinition('_init');
        }
        return $this->moduleDefinitions;
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
     * Laedt bestehende Fragen aus der DB um Wiederholungen zu vermeiden
     */
    private function loadExistingQuestions(string $module, int $limit = 10): array {
        $dbPath = __DIR__ . '/../AI/data/questions.db';
        if (!file_exists($dbPath)) return [];

        try {
            $db = new SQLite3($dbPath);
            $stmt = $db->prepare(
                "SELECT question FROM questions WHERE module = :module AND is_active = 1 ORDER BY RANDOM() LIMIT :limit"
            );
            $stmt->bindValue(':module', $module);
            $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
            $result = $stmt->execute();

            $questions = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $questions[] = $row['question'];
            }
            $db->close();
            return $questions;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function buildSystemPrompt(array $moduleDef): string {
        $topics = implode("\n- ", $moduleDef['topics'] ?? []);
        $notTopics = implode("\n- ", $moduleDef['NOT_topics'] ?? []);

        return "Du bist ein erfahrener Paedagoge und Quiz-Ersteller fuer 'sgiT Education', eine Lernplattform fuer Kinder und Jugendliche.

Modul: {$moduleDef['name']}
Definition: {$moduleDef['definition']}

Erlaubte Themen:
- {$topics}

VERBOTEN (gehoert zu anderen Modulen):
- {$notTopics}

QUALITAETSREGELN:
1. FAKTEN-CHECK: Alle Fakten muessen 100% korrekt und verifizierbar sein. Lieber eine Frage weniger als eine falsche Frage!
2. EINDEUTIGKEIT: Es darf nur EINE korrekte Antwort geben. Keine mehrdeutigen Fragen.
3. PLAUSIBLE FALSCHE ANTWORTEN: Falsche Antworten muessen glaubwuerdig klingen, duerfen aber nicht versehentlich auch richtig sein.
4. ALLE ANTWORTEN UNTERSCHIEDLICH: Keine zwei Antworten duerfen gleich oder fast gleich sein.
5. ERKLAERUNGEN: Jede Erklaerung muss lehrreich sein und das WARUM erklaeren, nicht nur die Antwort wiederholen.
6. SPRACHE: Deutsch. Keine Umlaute (ae statt ae, oe statt oe, ue statt ue, ss statt ss).
7. KEINE BUCHSTABEN-ANTWORTEN: Antworte niemals mit 'A', 'B', 'C', 'D' als Antworttext.
8. LAENGE: Frage min. 10 Zeichen. Antworten min. 2 Zeichen. Erklaerung min. 15 Zeichen.

ANTI-PATTERNS (NIEMALS SO):
- Frage: 'Welche Antwort ist richtig?' → ZU VAGE
- Antwort: 'A' oder 'B' → BUCHSTABEN statt Text
- Erklaerung: 'Weil es so ist' → NUTZLOS
- Falsche Antwort die auch richtig sein koennte → MEHRDEUTIG
- Alle falschen Antworten offensichtlich falsch → ZU EINFACH
- Frage hat nichts mit dem Modul zu tun → FALSCHES MODUL

ANTWORT-FORMAT: Antworte AUSSCHLIESSLICH mit einem JSON-Array. Kein anderer Text davor oder danach.";
    }

    private function buildUserPrompt(array $moduleDef, int $count, int $ageMin, int $ageMax, int $difficulty, array $existingQuestions = []): string {
        $diffDesc = match($difficulty) {
            1 => 'Sehr leicht - Grundwissen, einfache Fakten, kurze Antworten',
            2 => 'Leicht - Basiswissen mit etwas Nachdenken',
            3 => 'Mittel - Solides Schulwissen erforderlich',
            4 => 'Schwer - Vertieftes Wissen, Zusammenhaenge erkennen',
            5 => 'Sehr schwer - Expertenwissen, komplexe Zusammenhaenge',
            default => 'Mittel'
        };

        $existingContext = '';
        if (!empty($existingQuestions)) {
            $existingList = implode("\n", array_map(fn($q) => "- {$q}", $existingQuestions));
            $existingContext = "\n\nBEREITS VORHANDENE FRAGEN (NICHT wiederholen, andere Themen/Aspekte waehlen!):\n{$existingList}";
        }

        $examples = '';
        if (!empty($moduleDef['examples'])) {
            $examples = "\nBeispiel-Fragen (als Orientierung fuer Stil und Schwierigkeit):\n- " . implode("\n- ", array_slice($moduleDef['examples'], 0, 3));
        }

        return "Generiere genau {$count} Quiz-Fragen zum Thema \"{$moduleDef['name']}\".

Zielgruppe: {$ageMin}-{$ageMax} Jahre
Schwierigkeit: Stufe {$difficulty}/5 ({$diffDesc})
{$examples}{$existingContext}

Antworte NUR mit einem JSON-Array in diesem Format:
[
  {
    \"question\": \"Die Frage auf Deutsch\",
    \"correct\": \"Die richtige Antwort\",
    \"wrong\": [\"Falsche Antwort 1\", \"Falsche Antwort 2\", \"Falsche Antwort 3\"],
    \"explanation\": \"Erklaerung warum die Antwort richtig ist (lehrreich, 15-100 Zeichen)\"
  }
]

CHECKLISTE vor dem Absenden:
- Sind alle Fakten korrekt?
- Hat jede Frage genau 1 richtige und 3 falsche Antworten?
- Sind die falschen Antworten plausibel aber eindeutig falsch?
- Ist die Erklaerung lehrreich (nicht nur 'Weil es richtig ist')?
- Sind die Fragen altersgerecht fuer {$ageMin}-{$ageMax} Jahre?
- Sind alle Fragen unterschiedlich und zu verschiedenen Themen?

Generiere jetzt das JSON-Array mit genau {$count} Fragen:";
    }

    /**
     * Parst die Claude-Antwort (JSON oder Fallback auf Q:/A: Format)
     */
    private function parseQuestions(string $text): array {
        // Versuche JSON-Parsing zuerst
        $jsonQuestions = $this->parseJSON($text);
        if (!empty($jsonQuestions)) {
            return $jsonQuestions;
        }

        // Fallback: Q:/A:/W1:/W2:/W3:/E: Format
        return $this->parseLineFormat($text);
    }

    /**
     * Parst JSON-Array aus der Antwort
     */
    private function parseJSON(string $text): array {
        // JSON-Block extrahieren (kann in ```json ... ``` oder direkt sein)
        if (preg_match('/\[[\s\S]*\]/m', $text, $match)) {
            $json = json_decode($match[0], true);
            if (is_array($json)) {
                $valid = [];
                foreach ($json as $q) {
                    if (isset($q['question'], $q['correct'], $q['wrong']) && is_array($q['wrong'])) {
                        $valid[] = [
                            'question' => trim($q['question']),
                            'correct' => trim($q['correct']),
                            'wrong' => array_map('trim', array_slice($q['wrong'], 0, 3)),
                            'explanation' => trim($q['explanation'] ?? '')
                        ];
                    }
                }
                return $valid;
            }
        }
        return [];
    }

    /**
     * Fallback-Parser fuer Q:/A:/W1:/W2:/W3:/E: Format
     */
    private function parseLineFormat(string $text): array {
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

        $valid = [];
        foreach ($questions as $q) {
            if (!empty($q['question']) && !empty($q['correct']) && count($q['wrong']) >= 2) {
                $valid[] = $q;
            }
        }

        return $valid;
    }

    /**
     * Qualitaetspruefung fuer jede Frage - setzt Warnungen und filtert Muell
     */
    private function validateQuestions(array $questions, string $module): array {
        $validated = [];

        foreach ($questions as $q) {
            $warnings = [];
            $reject = false;

            // 1. Buchstaben-Antwort Check (A, B, C, D)
            if (preg_match('/^[A-D]$/i', trim($q['correct']))) {
                $reject = true;
                continue;
            }

            // 2. Frage zu kurz
            if (mb_strlen($q['question']) < 10) {
                $warnings[] = 'Frage sehr kurz';
            }

            // 3. Antwort zu kurz
            if (mb_strlen($q['correct']) < 2) {
                $warnings[] = 'Antwort zu kurz';
            }

            // 4. Erklaerung fehlt oder zu kurz
            if (empty($q['explanation']) || mb_strlen($q['explanation']) < 10) {
                $warnings[] = 'Erklaerung fehlt/zu kurz';
            }

            // 5. Erklaerung wiederholt nur die Antwort
            if (!empty($q['explanation']) && strtolower(trim($q['explanation'])) === strtolower(trim($q['correct']))) {
                $warnings[] = 'Erklaerung = Antwort';
            }

            // 6. Doppelte Antworten pruefen
            $allAnswers = array_merge([$q['correct']], $q['wrong']);
            $lowerAnswers = array_map(fn($a) => strtolower(trim($a)), $allAnswers);
            if (count($lowerAnswers) !== count(array_unique($lowerAnswers))) {
                $warnings[] = 'Doppelte Antworten';
            }

            // 7. Korrekte Antwort ist auch in den falschen
            $lowerCorrect = strtolower(trim($q['correct']));
            foreach ($q['wrong'] as $w) {
                if (strtolower(trim($w)) === $lowerCorrect) {
                    $reject = true;
                    break;
                }
            }
            if ($reject) continue;

            // 8. Nicht genug falsche Antworten
            if (count($q['wrong']) < 3) {
                $warnings[] = 'Nur ' . count($q['wrong']) . ' falsche Antworten';
            }

            // 9. Frage endet nicht mit Fragezeichen (leichte Warnung)
            if (!preg_match('/[?\.]$/', trim($q['question']))) {
                $warnings[] = 'Kein Fragezeichen';
            }

            // 10. Zu wenig falsche Antworten → auffuellen
            while (count($q['wrong']) < 3) {
                $q['wrong'][] = '(Keine Antwort)';
            }

            // Qualitaets-Score berechnen (0-100)
            $score = 100;
            $score -= count($warnings) * 15;
            $score = max(0, $score);

            $q['quality_score'] = $score;
            $q['quality_warnings'] = $warnings;

            $validated[] = $q;
        }

        return $validated;
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
