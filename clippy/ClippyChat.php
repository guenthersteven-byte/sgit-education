<?php
/**
 * ============================================================================
 * sgiT Education - Foxy Chat Manager v3.0 (DB-Only)
 * ============================================================================
 *
 * v3.0 (14.02.2026):
 * - Ollama/AI komplett entfernt (N100 zu schwach fuer Inference)
 * - Rein datenbank-basiert: Trigger-Words + Templates
 * - Erklaerungen kommen aus questions.explanation Feld
 * - Hints aus vordefinierten Workflows
 * - Wissensfragen aus Fragen-DB
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 3.0
 * @date 14.02.2026
 * ============================================================================
 */

class ClippyChat {

    private $db = null;
    private $questionsDb = null;
    private $maxHistoryLength = 4;

    public function __construct() {
        $this->initDatabase();
        $this->initQuestionsDb();
    }

    /**
     * Initialisiert die Foxy-Datenbank
     */
    private function initDatabase() {
        try {
            $dbPath = __DIR__ . '/foxy_chat.db';
            // Fallback: Legacy-Pfad
            if (!file_exists($dbPath)) {
                $dbPath = __DIR__ . '/../database/foxy_chat.db';
            }

            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

            $this->seedDefaultResponses();

        } catch (Exception $e) {
            error_log("[FoxyChat] DB Error: " . $e->getMessage());
        }
    }

    /**
     * Verbindet zur Fragen-DB fuer Erklaerungen und Wissen
     */
    private function initQuestionsDb() {
        $path = __DIR__ . '/../AI/data/questions.db';
        if (file_exists($path)) {
            try {
                $this->questionsDb = new PDO('sqlite:' . $path);
                $this->questionsDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (Exception $e) {
                error_log("[FoxyChat] Questions DB Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Standard-Antworten seeden
     */
    private function seedDefaultResponses() {
        $count = $this->db->query("SELECT COUNT(*) FROM foxy_responses")->fetchColumn();
        if ($count > 0) return;

        $defaults = [
            ['joke', 'witz,lustig,lachen', 'Warum koennen Fuechse so gut in der Schule? Weil sie immer schlau sind! 🦊😄'],
            ['joke', 'witz,lustig,lachen', 'Was macht ein Fuchs am Computer? Er surft im Fuchsbook! 💻🦊'],
            ['joke', 'witz,lustig,lachen', 'Warum tragen Fuechse keine Brillen? Weil sie schon Fuchs-Augen haben! 👀😂'],
            ['joke', 'witz,lustig,lachen', 'Was ist orange und kann rechnen? Ein Mathe-Fuchs! 🧮🦊'],
            ['joke', 'witz,lustig,lachen', 'Wie nennt man einen Fuchs der Klavier spielt? Wolfgang Amadeus Fuchs! 🎹🦊'],
            ['joke', 'witz,lustig,lachen', 'Warum ging der Fuchs zur Schule? Um schlauer als die anderen zu werden! 📚🦊'],
            ['joke', 'witz,lustig,lachen', 'Was sagt ein Fuchs wenn er fertig ist? FUCHSTASTISCH! 🎉🦊'],
            ['joke', 'witz,lustig,lachen', 'Warum sind Fuechse so gute Detektive? Sie haben einen Riecher! 🔍🦊'],
            ['joke', 'witz,lustig,lachen', 'Was ist das Lieblingsfach vom Fuchs? Fuchs-ik! ⚛️🦊'],
            ['joke', 'witz,lustig,lachen', 'Warum tanzt der Fuchs so gern? Er hat den Fox-Trott erfunden! 💃🦊'],

            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Kopf hoch! 💪 Jeder macht mal Fehler - so lernt man! Du schaffst das! 🦊🌟'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Du bist toll! 🌈 Auch wenn es schwer ist - ich glaube an dich! 🦊❤️'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Fuechse geben nie auf! 🦊💪 Und du auch nicht! Weiter so!'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Das wird schon! 🌟 Kleine Schritte fuehren auch zum Ziel! 🦊'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Ich bin stolz auf dich! 🦊 Dass du es versuchst ist schon super! 💪'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Ein Schritt nach dem anderen! Du rockst das! 🎸🦊'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Fuechse fallen 7 mal hin und stehen 8 mal auf! 💪🦊'],
            ['cheer', 'aufmunter,traurig,schaff,schwer,schwierig,kann nicht,hilf', '{name}Uebung macht den Meister-Fuchs! 🏆🦊'],

            ['tip', 'tipp,rat,hilfe,erklaere,wie geht', '💡 Du bekommst Sats fuer richtige Antworten! Je mehr du lernst desto mehr verdienst du! 🦊₿'],
            ['tip', 'tipp,rat,hilfe,erklaere,wie geht', '💡 Probier verschiedene Faecher aus! Abwechslung macht schlau! 📚🦊'],
            ['tip', 'tipp,rat,hilfe,erklaere,wie geht', '💡 Nach 10 Fragen bekommst du eine Zusammenfassung! 🎉'],
            ['tip', 'tipp,rat,hilfe,erklaere,wie geht', '💡 Lies die Frage immer zweimal! 📖🦊'],
            ['tip', 'tipp,rat,hilfe,erklaere,wie geht', '💡 Nutze den 50/50 Joker wenn du unsicher bist! 🦊'],
            ['tip', 'tipp,rat,hilfe,erklaere,wie geht', '💡 Jeden Tag 10 Fragen = Super Fortschritt! 📈🦊'],
            ['tip', 'tipp,rat,hilfe,erklaere,wie geht', '💡 Mach Pausen - dein Gehirn braucht sie! 🧠🦊'],
            ['tip', 'tipp,rat,hilfe,erklaere,wie geht', '💡 Falsche Antworten zeigen dir was du noch lernen kannst! 📚🦊'],

            ['thanks', 'danke,super,cool,toll,klasse,prima', 'Gern geschehen! Du bist super! 🌟🦊'],
            ['thanks', 'danke,super,cool,toll,klasse,prima', 'Immer fuer dich da! 🦊❤️'],
            ['thanks', 'danke,super,cool,toll,klasse,prima', 'Das freut mich! Weiter so! 💪🦊'],
            ['thanks', 'danke,super,cool,toll,klasse,prima', 'Fuechse helfen gern! 🦊✨'],

            ['bitcoin', 'bitcoin,sats,geld,wallet,btc', '₿ Bitcoin ist digitales Geld! Lerne mehr im Bitcoin-Modul! 🦊💰'],
            ['bitcoin', 'bitcoin,sats,geld,wallet,btc', '₿ Mit Sats kannst du spaeter echtes Bitcoin bekommen! 🦊💰'],
            ['bitcoin', 'bitcoin,sats,geld,wallet,btc', '₿ Jede richtige Antwort bringt dir Test-Sats! Fleissig lernen lohnt sich! 🦊'],

            ['motivate', 'langeweile,langweilig,keine lust,was soll,kein bock', '{name}Komm nur noch eine Frage! Du schaffst das! 💪🦊'],
            ['motivate', 'langeweile,langweilig,keine lust,was soll,kein bock', '{name}Mach 5 Minuten Pause und dann gehts weiter! ☕🦊'],
            ['motivate', 'langeweile,langweilig,keine lust,was soll,kein bock', '{name}Bereit zum Lernen? Waehl oben ein Fach aus! 🦊💪'],

            ['greeting', 'hallo,hi,hey,moin,guten tag,servus', '{name}Hey! 🦊 Schoen dass du da bist! Was moechtest du lernen?'],
            ['greeting', 'hallo,hi,hey,moin,guten tag,servus', '{name}Moin! 🦊 Bereit fuer ein Quiz? Ich helfe dir gerne!'],

            ['bye', 'tschuess,bye,ciao,bis bald', '{name}Bis bald! 🦊 Du hast super gelernt heute! 🌟'],
            ['bye', 'tschuess,bye,ciao,bis bald', '{name}Machs gut! 🦊 Komm bald wieder zum Lernen! 💪'],
        ];

        $stmt = $this->db->prepare("INSERT INTO foxy_responses (category, trigger_words, response) VALUES (?, ?, ?)");
        foreach ($defaults as $row) {
            $stmt->execute($row);
        }
    }

    // ========================================================================
    // CHAT
    // ========================================================================

    /**
     * Chat mit Foxy - DB-basiert mit Trigger-Matching
     */
    public function chat(string $message, int $age, ?string $module = null, ?string $userName = null, ?string $currentQuestion = null, array $history = []): array {
        try {
            $dbResponse = $this->getResponseFromDB($message, $userName);
            if ($dbResponse) {
                $this->saveChatHistory($message, $dbResponse, $userName, $module);
                return [
                    'success' => true,
                    'message' => $dbResponse,
                    'source' => 'database'
                ];
            }

            // Kein Trigger-Match: generische Antwort
            $fallback = $this->getFallbackResponse($message, $userName);
            $this->saveChatHistory($message, $fallback, $userName, $module);

            return [
                'success' => true,
                'message' => $fallback,
                'source' => 'fallback'
            ];

        } catch (Exception $e) {
            error_log("[FoxyChat] Error: " . $e->getMessage());
            return [
                'success' => true,
                'message' => $this->getFallbackResponse($message, $userName),
                'source' => 'error'
            ];
        }
    }

    // ========================================================================
    // EXPLAIN - Erklaerung aus Fragen-DB
    // ========================================================================

    /**
     * Erklaert warum eine Antwort richtig/falsch ist
     * Nutzt das explanation-Feld aus der Fragen-DB
     */
    public function explainAnswer(string $question, string $correctAnswer, string $userAnswer, int $age, ?string $userName = null): array {
        $isCorrect = ($userAnswer === $correctAnswer);
        $namePrefix = $userName ? "{$userName}, " : '';

        // Erklaerung aus Fragen-DB holen
        $explanation = $this->getExplanationFromDB($question);

        if ($explanation) {
            $prefix = $isCorrect
                ? "{$namePrefix}Super! 🌟 "
                : "{$namePrefix}Nicht ganz - die richtige Antwort ist '{$correctAnswer}'. ";

            return [
                'success' => true,
                'message' => $prefix . $explanation . ' 🦊',
                'correct' => $isCorrect,
                'source' => 'database'
            ];
        }

        // Fallback ohne DB-Erklaerung
        $message = $isCorrect
            ? "{$namePrefix}Richtig! Die Antwort '{$correctAnswer}' stimmt! Gut gemacht! 🦊🌟"
            : "{$namePrefix}Die richtige Antwort war '{$correctAnswer}'. Beim naechsten Mal klappts! 🦊💪";

        return [
            'success' => true,
            'message' => $message,
            'correct' => $isCorrect,
            'source' => 'fallback'
        ];
    }

    // ========================================================================
    // HINT - Hinweis ohne Loesung
    // ========================================================================

    /**
     * Gibt einen Hinweis basierend auf der Frage
     */
    public function getHint(string $question, string $correctAnswer, array $options, int $age, ?string $userName = null): array {
        $namePrefix = $userName ? "{$userName}, " : '';

        // Strategie: Eine falsche Antwort eliminieren
        $wrongOptions = array_values(array_filter($options, fn($o) => $o !== $correctAnswer));

        $hints = [
            "{$namePrefix}Hmm, ich bin mir ziemlich sicher dass '{$wrongOptions[0]}' NICHT die Antwort ist! 🦊💡",
            "{$namePrefix}Lies die Frage nochmal genau durch! Der Hinweis steckt oft im Detail! 📖🦊",
            "{$namePrefix}Tipp: Schliess erst die Antworten aus die sicher falsch sind! 🎯🦊",
        ];

        if (count($wrongOptions) >= 2) {
            $hints[] = "{$namePrefix}Ich wuerde '{$wrongOptions[0]}' und '{$wrongOptions[1]}' ausschliessen! 🦊🤔";
        }

        if (strlen($correctAnswer) <= 5 && is_numeric($correctAnswer)) {
            $hints[] = "{$namePrefix}Die Antwort ist eine Zahl... denk nochmal nach! 🔢🦊";
        }

        $selected = $hints[array_rand($hints)];

        return [
            'success' => true,
            'message' => $selected,
            'source' => 'workflow'
        ];
    }

    // ========================================================================
    // ASK - Wissensfragen aus Fragen-DB
    // ========================================================================

    /**
     * Beantwortet Wissensfragen aus der Fragen-Datenbank
     */
    public function askKnowledge(string $question, int $age, ?string $userName = null, ?string $module = null): array {
        $namePrefix = $userName ? "{$userName}, " : '';

        // In Fragen-DB suchen
        if ($this->questionsDb) {
            try {
                $searchTerms = $this->extractKeywords($question);

                if (!empty($searchTerms)) {
                    $where = [];
                    $params = [];
                    foreach ($searchTerms as $i => $term) {
                        $where[] = "(question LIKE :t{$i} OR explanation LIKE :t{$i})";
                        $params[":t{$i}"] = "%{$term}%";
                    }
                    $params[':age'] = $age;

                    $sql = "SELECT question, correct_answer, explanation FROM questions
                            WHERE is_active = 1
                            AND age_min <= :age
                            AND (" . implode(' OR ', $where) . ")
                            AND explanation IS NOT NULL AND explanation != ''
                            ORDER BY RANDOM() LIMIT 1";

                    $stmt = $this->questionsDb->prepare($sql);
                    $stmt->execute($params);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($row && $row['explanation']) {
                        $msg = "{$namePrefix}Gute Frage! 🦊 " . $row['explanation'];
                        $this->saveChatHistory($question, $msg, $userName, $module);
                        return [
                            'success' => true,
                            'message' => $msg,
                            'source' => 'database'
                        ];
                    }
                }
            } catch (Exception $e) {
                error_log("[FoxyChat] Ask Error: " . $e->getMessage());
            }
        }

        $message = "{$namePrefix}Das ist eine tolle Frage! 🦊 Leider weiss ich das gerade nicht. Probier doch mal das passende Modul aus - da lernst du bestimmt die Antwort! 📚💪";
        $this->saveChatHistory($question, $message, $userName, $module);

        return [
            'success' => true,
            'message' => $message,
            'source' => 'fallback'
        ];
    }

    // ========================================================================
    // HELPER
    // ========================================================================

    /**
     * Sucht passende Antwort in der Foxy-DB via Trigger-Words
     */
    private function getResponseFromDB(string $message, ?string $userName = null): ?string {
        $msg = strtolower($message);

        try {
            $stmt = $this->db->query("SELECT id, category, trigger_words, response FROM foxy_responses");
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
                $selected = $matches[array_rand($matches)];
                $this->db->exec("UPDATE foxy_responses SET usage_count = usage_count + 1 WHERE id = " . (int)$selected['id']);

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
     * Holt Erklaerung aus der Fragen-DB
     */
    private function getExplanationFromDB(string $question): ?string {
        if (!$this->questionsDb) return null;

        try {
            $stmt = $this->questionsDb->prepare(
                "SELECT explanation FROM questions WHERE question = :q AND explanation IS NOT NULL AND explanation != '' LIMIT 1"
            );
            $stmt->execute([':q' => $question]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['explanation'] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Extrahiert Suchbegriffe aus einer Frage
     */
    private function extractKeywords(string $question): array {
        $stopWords = ['was', 'ist', 'wie', 'warum', 'wer', 'wo', 'wann', 'der', 'die', 'das',
                       'ein', 'eine', 'und', 'oder', 'nicht', 'von', 'mit', 'fuer', 'auf', 'den',
                       'dem', 'des', 'ich', 'du', 'er', 'sie', 'es', 'wir', 'ihr', 'kann', 'sind',
                       'hat', 'haben', 'wird', 'werden', 'auch', 'noch', 'schon', 'mal', 'mir'];
        $words = preg_split('/\s+/', strtolower(trim($question)));
        $words = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
        return array_values(array_slice($words, 0, 4));
    }

    /**
     * Speichert Chat in Historie
     */
    private function saveChatHistory(string $userMessage, string $foxyResponse, ?string $userName = null, ?string $module = null) {
        try {
            $stmt = $this->db->prepare("INSERT INTO foxy_history (user_name, user_message, foxy_response, module) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userName, $userMessage, $foxyResponse, $module]);
        } catch (Exception $e) {
            error_log("[FoxyChat] Save History Error: " . $e->getMessage());
        }
    }

    /**
     * Fallback-Antworten
     */
    private function getFallbackResponse(string $message, ?string $userName = null): string {
        $namePrefix = $userName ? "{$userName}, " : '';
        $responses = [
            "{$namePrefix}hmm, interessant! 🦊 Frag mich nach einem Witz oder Tipp!",
            "{$namePrefix}das ist eine gute Frage! 🦊 Ich kann dir Witze erzaehlen oder Tipps geben!",
            "{$namePrefix}cool! 🦊 Sag 'Witz' fuer einen Fuchswitz oder 'Tipp' fuer Lerntipps!",
            "{$namePrefix}ich bin Foxy! 🦊 Ich kann: Witze, Tipps, Aufmunterung und Bitcoin-Infos!",
        ];
        return $responses[array_rand($responses)];
    }

    /**
     * Begruessung
     */
    public function getGreeting(int $age, ?string $module = null, ?string $userName = null): string {
        $nameGreeting = $userName ? "Hey {$userName}!" : "Hey!";

        if (!$module) {
            $motivations = [
                "{$nameGreeting} 🦊 Bereit zum Lernen? Waehl oben ein Fach aus und leg los! 💪",
                "{$nameGreeting} 🌟 Schoen dass du da bist! Such dir ein Fach aus und sammle Punkte! 🎯",
                "{$nameGreeting} 🦊 Lust auf ein Quiz? Klick oben auf ein Fach und zeig was du kannst! 🚀"
            ];
            return $motivations[array_rand($motivations)];
        }

        $moduleNames = [
            'mathematik' => 'Mathe', 'physik' => 'Physik', 'chemie' => 'Chemie',
            'biologie' => 'Bio', 'erdkunde' => 'Erdkunde', 'geschichte' => 'Geschichte',
            'kunst' => 'Kunst', 'musik' => 'Musik', 'computer' => 'Computer',
            'programmieren' => 'Programmieren', 'bitcoin' => 'Bitcoin', 'steuern' => 'Finanzen',
            'englisch' => 'Englisch', 'lesen' => 'Lesen', 'wissenschaft' => 'Wissenschaft',
            'verkehr' => 'Verkehr', 'unnuetzes_wissen' => 'Unnuetzes Wissen',
            'sport' => 'Sport', 'finanzen' => 'Finanzen', 'kochen' => 'Kochen'
        ];

        $moduleName = $moduleNames[strtolower($module)] ?? ucfirst($module);

        $greetings = [
            "{$nameGreeting} 🦊 Ich sehe du lernst {$moduleName}! Brauchst du einen Tipp? 💡",
            "{$nameGreeting} 🌟 {$moduleName} ist super! Bei Fragen bin ich hier! 🦊",
            "{$nameGreeting} 🦊 Cool, {$moduleName}! Wenns mal schwer wird frag mich! 💪"
        ];

        return $greetings[array_rand($greetings)];
    }

    /**
     * Status - ohne Ollama
     */
    public function checkOllamaStatus(): array {
        return [
            'online' => true,
            'mode' => 'database',
            'available_models' => [],
            'info' => 'Foxy nutzt die Fragen-Datenbank (kein AI-Model)'
        ];
    }

    /**
     * Neue Antwort hinzufuegen
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
     * Chat-Statistiken
     */
    public function getStats(): array {
        try {
            return [
                'total_chats' => $this->db->query("SELECT COUNT(*) FROM foxy_history")->fetchColumn(),
                'total_responses' => $this->db->query("SELECT COUNT(*) FROM foxy_responses")->fetchColumn(),
                'top_category' => $this->db->query("SELECT category FROM foxy_responses ORDER BY usage_count DESC LIMIT 1")->fetchColumn() ?: 'N/A'
            ];
        } catch (Exception $e) {
            return ['error' => 'Datenbankfehler'];
        }
    }
}
