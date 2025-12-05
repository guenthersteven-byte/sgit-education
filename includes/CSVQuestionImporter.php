<?php
/**
 * ============================================================================
 * sgiT Education - CSV Question Importer v1.1
 * ============================================================================
 * 
 * Importiert Fragen aus CSV-Dateien in die questions.db Datenbank.
 * 
 * Features:
 * - Duplikat-Erkennung via Hash UND (question, module)
 * - Source-Flagging (csv_import, ai_generated, manual)
 * - Batch-ID für Gruppenoperationen
 * - Validierung aller Pflichtfelder
 * - UTF-8 Unterstützung
 * - Robustes Error-Handling (UNIQUE constraint)
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.1
 * @date 02.12.2025
 * 
 * Changelog v1.1:
 * - FIX: Duplikat-Check auch auf (question, module), nicht nur Hash
 * - FIX: UNIQUE constraint Fehler werden als Duplikate gezählt
 * - FIX: Fehler pro Zeile statt Abbruch des ganzen Imports
 * ============================================================================
 */

class CSVQuestionImporter {
    
    /** @var SQLite3 Datenbankverbindung */
    private $db;
    
    /** @var string Pfad zur Datenbank */
    private $dbPath;
    
    /** @var array Import-Statistiken */
    private $stats = [
        'total' => 0,
        'imported' => 0,
        'duplicates' => 0,
        'errors' => 0,
        'error_messages' => []
    ];
    
    /** @var string Aktuelle Batch-ID */
    private $batchId;
    
    // ========================================================================
    // KONSTRUKTOR
    // ========================================================================
    
    public function __construct() {
        $this->dbPath = dirname(__DIR__) . '/AI/data/questions.db';
        $this->initDatabase();
    }
    
    /**
     * Initialisiert die Datenbankverbindung und erweitert Schema falls nötig
     */
    private function initDatabase(): void {
        $this->db = new SQLite3($this->dbPath);
        // v1.1: Exceptions NICHT global aktivieren - wir fangen sie manuell
        $this->db->enableExceptions(false);
        
        $this->extendSchema();
    }
    
    /**
     * Erweitert das Datenbank-Schema um neue Felder
     */
    private function extendSchema(): void {
        $result = $this->db->query("PRAGMA table_info(questions)");
        $columns = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $row['name'];
        }
        
        if (!in_array('source', $columns)) {
            $this->db->exec("ALTER TABLE questions ADD COLUMN source TEXT DEFAULT 'ai_generated'");
        }
        
        if (!in_array('imported_at', $columns)) {
            $this->db->exec("ALTER TABLE questions ADD COLUMN imported_at DATETIME");
        }
        
        if (!in_array('batch_id', $columns)) {
            $this->db->exec("ALTER TABLE questions ADD COLUMN batch_id TEXT");
        }
        
        if (!in_array('question_hash', $columns)) {
            $this->db->exec("ALTER TABLE questions ADD COLUMN question_hash TEXT");
        }
        
        if (!in_array('explanation', $columns)) {
            $this->db->exec("ALTER TABLE questions ADD COLUMN explanation TEXT");
        }
        
        if (!in_array('question_type', $columns)) {
            $this->db->exec("ALTER TABLE questions ADD COLUMN question_type TEXT");
        }
        
        if (!in_array('image_url', $columns)) {
            $this->db->exec("ALTER TABLE questions ADD COLUMN image_url TEXT");
        }
        
        // Indices
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_question_hash ON questions(question_hash)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_batch_id ON questions(batch_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_source ON questions(source)");
    }
    
    // ========================================================================
    // IMPORT
    // ========================================================================
    
    /**
     * Importiert Fragen aus einer CSV-Datei
     * 
     * @param string $csvPath Pfad zur CSV-Datei
     * @param string $module Ziel-Modul (z.B. "mathematik")
     * @param bool $dryRun Nur validieren, nicht importieren
     * @return array Import-Statistiken
     */
    public function importFromCSV(string $csvPath, string $module, bool $dryRun = false): array {
        // Reset Stats
        $this->stats = [
            'total' => 0,
            'imported' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'error_messages' => [],
            'dry_run' => $dryRun
        ];
        
        if (!file_exists($csvPath)) {
            throw new Exception("CSV-Datei nicht gefunden: $csvPath");
        }
        
        // Batch-ID generieren
        $this->batchId = $module . '_import_' . date('Ymd_His');
        $this->stats['batch_id'] = $this->batchId;
        
        // CSV öffnen (UTF-8 BOM entfernen)
        $content = file_get_contents($csvPath);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        $lines = explode("\n", $content);
        $header = null;
        
        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Header-Zeile
            if ($header === null) {
                $header = str_getcsv($line, ';');
                $header = array_map('trim', $header);
                
                $required = ['frage', 'antwort_a', 'antwort_b', 'antwort_c', 'antwort_d', 'richtig', 'schwierigkeit', 'min_alter', 'max_alter'];
                foreach ($required as $field) {
                    if (!in_array($field, $header)) {
                        throw new Exception("Pflichtfeld '$field' fehlt in CSV-Header");
                    }
                }
                continue;
            }
            
            $this->stats['total']++;
            
            // Zeile parsen
            $values = str_getcsv($line, ';');
            if (count($values) !== count($header)) {
                $this->stats['errors']++;
                $this->stats['error_messages'][] = "Zeile " . ($lineNum + 1) . ": Spaltenanzahl stimmt nicht überein";
                continue;
            }
            
            $row = array_combine($header, $values);
            
            // Validieren
            $validationError = $this->validateRow($row, $lineNum + 1);
            if ($validationError) {
                $this->stats['errors']++;
                $this->stats['error_messages'][] = $validationError;
                continue;
            }
            
            // v1.1: Erweiterter Duplikat-Check (Hash UND question+module)
            $hash = $this->generateQuestionHash($row['frage'], $row);
            $questionText = trim($row['frage']);
            
            if ($this->isDuplicate($hash, $questionText, $module)) {
                $this->stats['duplicates']++;
                continue;
            }
            
            // Importieren
            if (!$dryRun) {
                $insertResult = $this->insertQuestion($module, $row, $hash);
                
                if ($insertResult === 'duplicate') {
                    // v1.1: UNIQUE constraint Fehler = Duplikat
                    $this->stats['duplicates']++;
                    $this->stats['imported']--; // Korrektur
                } elseif ($insertResult === 'error') {
                    $this->stats['errors']++;
                    $this->stats['error_messages'][] = "Zeile " . ($lineNum + 1) . ": DB-Fehler beim Einfügen";
                    $this->stats['imported']--; // Korrektur
                }
            }
            
            $this->stats['imported']++;
        }
        
        return $this->stats;
    }
    
    /**
     * Validiert eine CSV-Zeile
     */
    private function validateRow(array $row, int $lineNum): ?string {
        if (empty(trim($row['frage']))) {
            return "Zeile $lineNum: Frage ist leer";
        }
        
        foreach (['antwort_a', 'antwort_b', 'antwort_c', 'antwort_d'] as $field) {
            if (empty(trim($row[$field]))) {
                return "Zeile $lineNum: $field ist leer";
            }
        }
        
        $richtig = strtoupper(trim($row['richtig']));
        if (!in_array($richtig, ['A', 'B', 'C', 'D'])) {
            return "Zeile $lineNum: 'richtig' muss A, B, C oder D sein (ist: {$row['richtig']})";
        }
        
        $schwierigkeit = (int) $row['schwierigkeit'];
        if ($schwierigkeit < 1 || $schwierigkeit > 5) {
            return "Zeile $lineNum: 'schwierigkeit' muss 1-5 sein (ist: {$row['schwierigkeit']})";
        }
        
        $minAlter = (int) $row['min_alter'];
        $maxAlter = (int) $row['max_alter'];
        
        if ($minAlter < 5 || $minAlter > 21) {
            return "Zeile $lineNum: 'min_alter' muss 5-21 sein (ist: {$row['min_alter']})";
        }
        
        if ($maxAlter < 5 || $maxAlter > 21) {
            return "Zeile $lineNum: 'max_alter' muss 5-21 sein (ist: {$row['max_alter']})";
        }
        
        if ($minAlter > $maxAlter) {
            return "Zeile $lineNum: 'min_alter' ($minAlter) darf nicht größer als 'max_alter' ($maxAlter) sein";
        }
        
        return null;
    }
    
    /**
     * Generiert einen Hash für Duplikat-Erkennung
     */
    private function generateQuestionHash(string $question, array $row): string {
        $data = strtolower(trim($question));
        $data .= '|' . strtolower(trim($row['antwort_a']));
        $data .= '|' . strtolower(trim($row['antwort_b']));
        $data .= '|' . strtolower(trim($row['antwort_c']));
        $data .= '|' . strtolower(trim($row['antwort_d']));
        
        return md5($data);
    }
    
    /**
     * v1.1: Erweiterter Duplikat-Check
     * Prüft auf Hash UND (question, module) Kombination
     */
    private function isDuplicate(string $hash, string $question, string $module): bool {
        // Check 1: Hash
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM questions WHERE question_hash = :hash");
        $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
        $result = $stmt->execute();
        if ($result && (int) $result->fetchArray()[0] > 0) {
            return true;
        }
        
        // Check 2: Gleiche Frage im gleichen Modul (case-insensitive)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM questions 
            WHERE LOWER(question) = LOWER(:question) 
            AND LOWER(module) = LOWER(:module)
        ");
        $stmt->bindValue(':question', $question, SQLITE3_TEXT);
        $stmt->bindValue(':module', $module, SQLITE3_TEXT);
        $result = $stmt->execute();
        if ($result && (int) $result->fetchArray()[0] > 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * v1.1: Fügt eine Frage ein mit Error-Handling
     * @return string 'success', 'duplicate', or 'error'
     */
    private function insertQuestion(string $module, array $row, string $hash): string {
        $answerMap = [
            'A' => $row['antwort_a'],
            'B' => $row['antwort_b'],
            'C' => $row['antwort_c'],
            'D' => $row['antwort_d']
        ];
        $correctAnswer = $answerMap[strtoupper(trim($row['richtig']))];
        
        $options = json_encode([
            trim($row['antwort_a']),
            trim($row['antwort_b']),
            trim($row['antwort_c']),
            trim($row['antwort_d'])
        ], JSON_UNESCAPED_UNICODE);
        
        $stmt = $this->db->prepare("
            INSERT INTO questions (
                module, question, answer, options, difficulty,
                age_min, age_max, ai_generated, source, imported_at,
                batch_id, question_hash, question_type, explanation, image_url
            ) VALUES (
                :module, :question, :answer, :options, :difficulty,
                :age_min, :age_max, 0, 'csv_import', datetime('now'),
                :batch_id, :hash, :type, :explanation, :image_url
            )
        ");
        
        if (!$stmt) {
            return 'error';
        }
        
        $stmt->bindValue(':module', $module, SQLITE3_TEXT);
        $stmt->bindValue(':question', trim($row['frage']), SQLITE3_TEXT);
        $stmt->bindValue(':answer', $correctAnswer, SQLITE3_TEXT);
        $stmt->bindValue(':options', $options, SQLITE3_TEXT);
        $stmt->bindValue(':difficulty', (int) $row['schwierigkeit'], SQLITE3_INTEGER);
        $stmt->bindValue(':age_min', (int) $row['min_alter'], SQLITE3_INTEGER);
        $stmt->bindValue(':age_max', (int) $row['max_alter'], SQLITE3_INTEGER);
        $stmt->bindValue(':batch_id', $this->batchId, SQLITE3_TEXT);
        $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':type', $row['typ'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':explanation', $row['erklaerung'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':image_url', $row['bild_url'] ?? null, SQLITE3_TEXT);
        
        $result = @$stmt->execute();
        
        if (!$result) {
            $error = $this->db->lastErrorMsg();
            // v1.1: UNIQUE constraint = Duplikat, nicht Fehler
            if (strpos($error, 'UNIQUE constraint') !== false) {
                return 'duplicate';
            }
            return 'error';
        }
        
        return 'success';
    }
    
    // ========================================================================
    // BATCH MANAGEMENT
    // ========================================================================
    
    public function getImportBatches(): array {
        $result = $this->db->query("
            SELECT batch_id, 
                   COUNT(*) as question_count,
                   MIN(imported_at) as imported_at,
                   module
            FROM questions 
            WHERE source = 'csv_import' AND batch_id IS NOT NULL
            GROUP BY batch_id
            ORDER BY imported_at DESC
        ");
        
        $batches = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $batches[] = $row;
        }
        return $batches;
    }
    
    public function deleteBatch(string $batchId): int {
        $stmt = $this->db->prepare("DELETE FROM questions WHERE batch_id = :batch_id");
        $stmt->bindValue(':batch_id', $batchId, SQLITE3_TEXT);
        $stmt->execute();
        
        return $this->db->changes();
    }
    
    // ========================================================================
    // STATISTIKEN
    // ========================================================================
    
    public function getStatsBySource(): array {
        $result = $this->db->query("
            SELECT 
                COALESCE(source, 'ai_generated') as source,
                COUNT(*) as count
            FROM questions 
            GROUP BY source
        ");
        
        $stats = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $stats[$row['source']] = (int) $row['count'];
        }
        return $stats;
    }
    
    public function updateMissingHashes(): int {
        $result = $this->db->query("
            SELECT id, question, options 
            FROM questions 
            WHERE question_hash IS NULL OR question_hash = ''
        ");
        
        $updated = 0;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $options = json_decode($row['options'], true);
            if (!is_array($options) || count($options) < 4) continue;
            
            $fakeRow = [
                'antwort_a' => $options[0] ?? '',
                'antwort_b' => $options[1] ?? '',
                'antwort_c' => $options[2] ?? '',
                'antwort_d' => $options[3] ?? ''
            ];
            
            $hash = $this->generateQuestionHash($row['question'], $fakeRow);
            
            $stmt = $this->db->prepare("UPDATE questions SET question_hash = :hash WHERE id = :id");
            $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
            $stmt->bindValue(':id', $row['id'], SQLITE3_INTEGER);
            $stmt->execute();
            
            $updated++;
        }
        
        return $updated;
    }
    
    public function migrateExistingQuestions(): int {
        $this->db->exec("UPDATE questions SET source = 'ai_generated' WHERE ai_generated = 1 AND (source IS NULL OR source = '')");
        $ai = $this->db->changes();
        
        $this->db->exec("UPDATE questions SET source = 'manual' WHERE ai_generated = 0 AND (source IS NULL OR source = '')");
        $manual = $this->db->changes();
        
        return $ai + $manual;
    }
}
