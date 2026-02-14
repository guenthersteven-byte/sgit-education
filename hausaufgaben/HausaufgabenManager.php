<?php
/**
 * ============================================================================
 * sgiT Education - HausaufgabenManager v1.0
 * ============================================================================
 *
 * Business-Logic fuer das Hausaufgaben-Upload-System:
 * - DB-Setup (auto-create)
 * - Upload-Verarbeitung (EXIF-Rotation, Skalierung, JPEG-Kompression)
 * - OCR via Tesseract (Deutsch + Englisch)
 * - CRUD-Operationen
 * - Statistiken fuer Achievements
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 12.02.2026
 * ============================================================================
 */

require_once __DIR__ . '/../db_config.php';

class HausaufgabenManager {

    /** @var SQLite3 */
    private $db;

    /** @var string */
    private const DB_PATH = __DIR__ . '/hausaufgaben.db';

    /** @var string Upload-Basisverzeichnis */
    private const UPLOAD_BASE = __DIR__ . '/../uploads/hausaufgaben';

    /** @var array Erlaubte Faecher */
    public const SUBJECTS = [
        'mathematik' => 'Mathematik',
        'deutsch' => 'Deutsch',
        'englisch' => 'Englisch',
        'sachkunde' => 'Sachkunde',
        'biologie' => 'Biologie',
        'physik' => 'Physik',
        'chemie' => 'Chemie',
        'geschichte' => 'Geschichte',
        'erdkunde' => 'Erdkunde',
        'kunst' => 'Kunst',
        'musik' => 'Musik',
        'sport' => 'Sport',
        'religion_ethik' => 'Religion/Ethik',
        'informatik' => 'Informatik',
        'sonstige' => 'Sonstige',
    ];

    // ========================================================================
    // KONSTRUKTOR
    // ========================================================================

    public function __construct() {
        $this->db = DatabaseConfig::getConnection(self::DB_PATH);

        if (!$this->db) {
            throw new Exception("Hausaufgaben-Datenbank nicht verfuegbar.");
        }

        $this->ensureTables();
    }

    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }

    // ========================================================================
    // AUTO-SETUP
    // ========================================================================

    private function ensureTables(): void {
        $tableExists = $this->db->querySingle(
            "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='homework_uploads'"
        );

        if (!$tableExists) {
            $this->createTables();
        }
    }

    private function createTables(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS homework_uploads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                child_id INTEGER NOT NULL,
                subject TEXT NOT NULL,
                grade_level INTEGER NOT NULL CHECK(grade_level BETWEEN 1 AND 13),
                school_year TEXT NOT NULL,
                file_path TEXT NOT NULL,
                file_size INTEGER NOT NULL,
                original_filename TEXT NOT NULL,
                original_size INTEGER DEFAULT 0,
                mime_type TEXT NOT NULL DEFAULT 'image/jpeg',
                width INTEGER DEFAULT 0,
                height INTEGER DEFAULT 0,
                description TEXT DEFAULT '',
                sats_earned INTEGER DEFAULT 0,
                ocr_text TEXT DEFAULT NULL,
                ocr_confidence REAL DEFAULT 0,
                ai_analysis_status TEXT DEFAULT 'pending',
                ai_notes TEXT DEFAULT NULL,
                ai_analyzed_at DATETIME DEFAULT NULL,
                is_deleted INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_hw_child ON homework_uploads(child_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_hw_child_subject ON homework_uploads(child_id, subject)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_hw_child_year ON homework_uploads(child_id, school_year)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_hw_created ON homework_uploads(created_at)");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS homework_config (
                config_key TEXT PRIMARY KEY,
                config_value TEXT NOT NULL
            )
        ");

        $defaults = [
            'sats_per_upload' => '15',
            'daily_upload_limit' => '10',
            'max_file_size_mb' => '5',
            'jpeg_quality' => '85',
            'max_width' => '1920',
        ];

        $stmt = $this->db->prepare("INSERT OR IGNORE INTO homework_config VALUES (:key, :value)");
        foreach ($defaults as $key => $value) {
            $stmt->bindValue(':key', $key);
            $stmt->bindValue(':value', $value);
            $stmt->execute();
            $stmt->reset();
        }

        error_log("HausaufgabenManager: Tabellen erstellt");
    }

    // ========================================================================
    // KONFIGURATION
    // ========================================================================

    public function getConfig(string $key, string $default = ''): string {
        $stmt = $this->db->prepare("SELECT config_value FROM homework_config WHERE config_key = :key");
        $stmt->bindValue(':key', $key);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['config_value'] : $default;
    }

    public function getSatsPerUpload(): int {
        return (int) $this->getConfig('sats_per_upload', '15');
    }

    public function getDailyUploadLimit(): int {
        return (int) $this->getConfig('daily_upload_limit', '10');
    }

    // ========================================================================
    // VALIDIERUNG
    // ========================================================================

    public static function isValidSubject(string $subject): bool {
        return array_key_exists($subject, self::SUBJECTS);
    }

    public static function isValidGrade(int $grade): bool {
        return $grade >= 1 && $grade <= 13;
    }

    public static function isValidSchoolYear(string $year): bool {
        return (bool) preg_match('/^\d{4}\/\d{4}$/', $year);
    }

    // ========================================================================
    // UPLOAD-VERARBEITUNG
    // ========================================================================

    /**
     * Verarbeitet einen Foto-Upload
     *
     * @return array {success, upload_id, sats_earned, file_path, message, ocr_text}
     */
    public function processUpload(int $childId, array $file, string $subject, int $gradeLevel, string $schoolYear, string $description = ''): array {
        // Check if GD extension is loaded
        if (!extension_loaded('gd')) {
            error_log("HausaufgabenManager: PHP GD extension not loaded - cannot process images");
            return ['success' => false, 'error' => 'Bildverarbeitung nicht verfuegbar. Bitte Administrator kontaktieren.'];
        }

        // Check if upload base directory exists and is writable
        if (!is_dir(self::UPLOAD_BASE)) {
            if (!@mkdir(self::UPLOAD_BASE, 0755, true)) {
                error_log("HausaufgabenManager: Cannot create upload directory: " . self::UPLOAD_BASE);
                return ['success' => false, 'error' => 'Upload-Verzeichnis nicht verfuegbar'];
            }
        }
        if (!is_writable(self::UPLOAD_BASE)) {
            error_log("HausaufgabenManager: Upload directory not writable: " . self::UPLOAD_BASE);
            return ['success' => false, 'error' => 'Upload-Verzeichnis nicht beschreibbar'];
        }

        // Validierung
        if (!self::isValidSubject($subject)) {
            return ['success' => false, 'error' => 'Ungueltiges Fach'];
        }
        if (!self::isValidGrade($gradeLevel)) {
            return ['success' => false, 'error' => 'Ungueltige Klassenstufe'];
        }
        if (!self::isValidSchoolYear($schoolYear)) {
            return ['success' => false, 'error' => 'Ungueltiges Schuljahr'];
        }

        // Daily Limit pruefen
        $todayCount = $this->getUploadsToday($childId);
        $limit = $this->getDailyUploadLimit();
        if ($todayCount >= $limit) {
            return ['success' => false, 'error' => "Tageslimit erreicht ({$limit} Uploads pro Tag)"];
        }

        // Datei validieren
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        $originalSize = $file['size'];
        $originalFilename = $file['name'];

        // Bild verarbeiten (EXIF-Rotation, Skalierung, Kompression)
        $processed = $this->processImage($file['tmp_name']);
        if (!$processed['success']) {
            return ['success' => false, 'error' => 'Bildverarbeitung fehlgeschlagen: ' . $processed['error']];
        }

        // Speicherpfad erstellen (Path-Traversal-Schutz)
        $yearDir = preg_replace('/[^0-9\-\/]/', '', $schoolYear);
        $yearDir = str_replace(['/', '\\', '..'], '-', $yearDir);
        $safeChildId = (int)$childId;
        if ($safeChildId <= 0) {
            return ['success' => false, 'error' => 'Ungueltige Child-ID'];
        }
        if (!array_key_exists($subject, self::SUBJECTS)) {
            return ['success' => false, 'error' => 'Ungueltiges Fach'];
        }
        $uploadDir = self::UPLOAD_BASE . "/{$safeChildId}/{$yearDir}/{$subject}";
        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0755, true)) {
                imagedestroy($processed['image']);
                $lastError = error_get_last();
                $errorMsg = $lastError ? $lastError['message'] : 'Unbekannter Fehler';
                error_log("HausaufgabenManager: Cannot create directory {$uploadDir}: {$errorMsg}");
                return ['success' => false, 'error' => 'Upload-Verzeichnis konnte nicht erstellt werden'];
            }
        }

        $filename = date('Y-m-d_His') . '_' . bin2hex(random_bytes(3)) . '.jpg';
        $filePath = $uploadDir . '/' . $filename;

        // Bild speichern
        if (!imagejpeg($processed['image'], $filePath, (int) $this->getConfig('jpeg_quality', '85'))) {
            imagedestroy($processed['image']);
            return ['success' => false, 'error' => 'Speichern fehlgeschlagen'];
        }

        $fileSize = filesize($filePath);
        $width = imagesx($processed['image']);
        $height = imagesy($processed['image']);
        imagedestroy($processed['image']);

        // Relativer Pfad fuer DB
        $relativePath = "uploads/hausaufgaben/{$childId}/{$yearDir}/{$subject}/{$filename}";

        // OCR
        $ocrResult = $this->extractText($filePath);

        // DB-Eintrag
        $stmt = $this->db->prepare("
            INSERT INTO homework_uploads
            (child_id, subject, grade_level, school_year, file_path, file_size,
             original_filename, original_size, mime_type, width, height,
             description, ocr_text, ocr_confidence)
            VALUES (:child_id, :subject, :grade, :year, :path, :size,
                    :orig_name, :orig_size, 'image/jpeg', :width, :height,
                    :desc, :ocr_text, :ocr_conf)
        ");
        $stmt->bindValue(':child_id', $childId, SQLITE3_INTEGER);
        $stmt->bindValue(':subject', $subject);
        $stmt->bindValue(':grade', $gradeLevel, SQLITE3_INTEGER);
        $stmt->bindValue(':year', $schoolYear);
        $stmt->bindValue(':path', $relativePath);
        $stmt->bindValue(':size', $fileSize, SQLITE3_INTEGER);
        $stmt->bindValue(':orig_name', $originalFilename);
        $stmt->bindValue(':orig_size', $originalSize, SQLITE3_INTEGER);
        $stmt->bindValue(':width', $width, SQLITE3_INTEGER);
        $stmt->bindValue(':height', $height, SQLITE3_INTEGER);
        $stmt->bindValue(':desc', $description);
        $stmt->bindValue(':ocr_text', $ocrResult['text']);
        $stmt->bindValue(':ocr_conf', $ocrResult['confidence'], SQLITE3_FLOAT);

        if (!$stmt->execute()) {
            return ['success' => false, 'error' => 'Datenbank-Eintrag fehlgeschlagen'];
        }

        $uploadId = $this->db->lastInsertRowID();

        // SATs vergeben
        $satsPerUpload = $this->getSatsPerUpload();
        $satsResult = null;
        try {
            require_once __DIR__ . '/../wallet/WalletManager.php';
            $wallet = new WalletManager();
            $satsResult = $wallet->creditSats($childId, $satsPerUpload, 'Hausaufgabe hochgeladen: ' . self::SUBJECTS[$subject], 'hausaufgaben');
        } catch (Exception $e) {
            error_log("HausaufgabenManager: SATs-Vergabe fehlgeschlagen: " . $e->getMessage());
        }

        $satsEarned = ($satsResult && $satsResult['success']) ? $satsResult['sats'] : 0;

        // SATs in Upload-Eintrag speichern
        if ($satsEarned > 0) {
            $stmt = $this->db->prepare("UPDATE homework_uploads SET sats_earned = :sats WHERE id = :id");
            $stmt->bindValue(':sats', $satsEarned, SQLITE3_INTEGER);
            $stmt->bindValue(':id', $uploadId, SQLITE3_INTEGER);
            $stmt->execute();
        }

        // Achievement-Check
        try {
            require_once __DIR__ . '/../wallet/AchievementManager.php';
            $achievementMgr = new AchievementManager();
            $achievementMgr->checkAndUnlock($childId, ['module' => 'hausaufgaben']);
        } catch (Exception $e) {
            error_log("HausaufgabenManager: Achievement-Check fehlgeschlagen: " . $e->getMessage());
        }

        return [
            'success' => true,
            'upload_id' => $uploadId,
            'sats_earned' => $satsEarned,
            'new_balance' => $satsResult['new_balance'] ?? null,
            'file_path' => $relativePath,
            'ocr_text' => $ocrResult['text'],
            'message' => 'Hausaufgabe erfolgreich hochgeladen!'
        ];
    }

    /**
     * Validiert eine Upload-Datei
     */
    private function validateFile(array $file): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'Datei zu gross (Server-Limit)',
                UPLOAD_ERR_FORM_SIZE => 'Datei zu gross (Formular-Limit)',
                UPLOAD_ERR_PARTIAL => 'Upload unvollstaendig',
                UPLOAD_ERR_NO_FILE => 'Keine Datei ausgewaehlt',
            ];
            return ['valid' => false, 'error' => $errors[$file['error']] ?? 'Upload-Fehler'];
        }

        // Groesse pruefen
        $maxSize = ((int) $this->getConfig('max_file_size_mb', '5')) * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'Datei zu gross (max ' . $this->getConfig('max_file_size_mb', '5') . 'MB)'];
        }

        // MIME-Type per finfo pruefen (nicht Upload-Header vertrauen)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
        if (!in_array($mime, $allowedMimes)) {
            return ['valid' => false, 'error' => 'Nur Bilder erlaubt (JPEG, PNG, WebP)'];
        }

        // getimagesize als zweite Validierung
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            return ['valid' => false, 'error' => 'Ungueltige Bilddatei'];
        }

        return ['valid' => true];
    }

    /**
     * Verarbeitet ein Bild: EXIF-Rotation, Skalierung
     *
     * @return array {success, image (GdImage), error}
     */
    private function processImage(string $tmpPath): array {
        // Check if GD library is available
        if (!extension_loaded('gd')) {
            error_log("HausaufgabenManager: PHP GD extension not loaded");
            return ['success' => false, 'error' => 'Bildverarbeitung nicht verfuegbar (GD Extension fehlt)'];
        }

        $imageInfo = @getimagesize($tmpPath);
        if (!$imageInfo) {
            return ['success' => false, 'error' => 'Kein gueltiges Bild'];
        }

        $mime = $imageInfo['mime'];

        // Bild laden
        switch ($mime) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($tmpPath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($tmpPath);
                break;
            case 'image/webp':
                $image = @imagecreatefromwebp($tmpPath);
                break;
            default:
                return ['success' => false, 'error' => 'Nicht unterstuetztes Format'];
        }

        if (!$image) {
            $lastError = error_get_last();
            $errorMsg = $lastError ? $lastError['message'] : 'Unbekannter Fehler';
            error_log("HausaufgabenManager: Image creation failed - " . $errorMsg);
            return ['success' => false, 'error' => 'Bild konnte nicht geladen werden'];
        }

        // EXIF-Rotation korrigieren (nur JPEG)
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmpPath);
            if ($exif && isset($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                }
            }
        }

        // Skalieren auf max_width
        $maxWidth = (int) $this->getConfig('max_width', '1920');
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $newHeight = (int) round($height * $ratio);
            $resized = imagecreatetruecolor($maxWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        return ['success' => true, 'image' => $image];
    }

    // ========================================================================
    // OCR (TESSERACT)
    // ========================================================================

    /**
     * Extrahiert Text aus einem Bild via Tesseract
     *
     * @return array {text, confidence}
     */
    public function extractText(string $imagePath): array {
        // Pruefen ob Tesseract verfuegbar ist
        $tesseractPath = trim(shell_exec('which tesseract 2>/dev/null') ?? '');
        if (empty($tesseractPath)) {
            return ['text' => null, 'confidence' => 0];
        }

        // Path-Validation: Nur Dateien im Upload-Verzeichnis erlauben
        $realPath = realpath($imagePath);
        $realBase = realpath(self::UPLOAD_BASE);
        if (!$realPath || !$realBase || !str_starts_with($realPath, $realBase)) {
            error_log("HausaufgabenManager: OCR path traversal blocked: {$imagePath}");
            return ['text' => null, 'confidence' => 0];
        }

        // OCR Text extrahieren
        $cmd = sprintf('tesseract %s stdout -l deu+eng 2>/dev/null', escapeshellarg($realPath));
        $ocrText = shell_exec($cmd);
        $ocrText = trim($ocrText ?? '');

        if (empty($ocrText)) {
            return ['text' => null, 'confidence' => 0];
        }

        // Confidence berechnen via TSV-Output
        $confidence = $this->getOcrConfidence($imagePath);

        return [
            'text' => $ocrText,
            'confidence' => $confidence,
        ];
    }

    private function getOcrConfidence(string $imagePath): float {
        // Path bereits in extractText() validiert, nochmal pruefen
        $realPath = realpath($imagePath);
        $realBase = realpath(self::UPLOAD_BASE);
        if (!$realPath || !$realBase || !str_starts_with($realPath, $realBase)) {
            return 0;
        }
        $cmd = sprintf('tesseract %s stdout -l deu+eng --psm 6 tsv 2>/dev/null', escapeshellarg($realPath));
        $tsv = shell_exec($cmd);

        if (empty($tsv)) {
            return 0;
        }

        $lines = explode("\n", trim($tsv));
        $confidences = [];

        foreach ($lines as $i => $line) {
            if ($i === 0) continue; // Header
            $cols = explode("\t", $line);
            // Spalte 11 = conf, Spalte 12 = text (TSV format)
            if (count($cols) >= 12 && (int) $cols[10] > -1 && trim($cols[11] ?? '') !== '') {
                $confidences[] = (float) $cols[10];
            }
        }

        if (empty($confidences)) {
            return 0;
        }

        return round(array_sum($confidences) / count($confidences), 1);
    }

    // ========================================================================
    // CRUD
    // ========================================================================

    /**
     * Holt einen einzelnen Upload
     */
    public function getUpload(int $uploadId, int $childId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM homework_uploads
            WHERE id = :id AND child_id = :child_id AND is_deleted = 0
        ");
        $stmt->bindValue(':id', $uploadId, SQLITE3_INTEGER);
        $stmt->bindValue(':child_id', $childId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC) ?: null;
    }

    /**
     * Holt Uploads mit optionalem Filter
     */
    public function getUploads(int $childId, array $filters = [], int $limit = 50, int $offset = 0): array {
        $where = ["child_id = :child_id", "is_deleted = 0"];
        $params = [':child_id' => $childId];

        if (!empty($filters['subject'])) {
            $where[] = "subject = :subject";
            $params[':subject'] = $filters['subject'];
        }
        if (!empty($filters['school_year'])) {
            $where[] = "school_year = :school_year";
            $params[':school_year'] = $filters['school_year'];
        }
        if (!empty($filters['grade_level'])) {
            $where[] = "grade_level = :grade_level";
            $params[':grade_level'] = (int) $filters['grade_level'];
        }

        $sql = "SELECT * FROM homework_uploads WHERE " . implode(' AND ', $where)
             . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
        }
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $uploads = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $uploads[] = $row;
        }
        return $uploads;
    }

    /**
     * Soft-Delete eines Uploads
     */
    public function deleteUpload(int $uploadId, int $childId): bool {
        $stmt = $this->db->prepare("
            UPDATE homework_uploads SET is_deleted = 1
            WHERE id = :id AND child_id = :child_id
        ");
        $stmt->bindValue(':id', $uploadId, SQLITE3_INTEGER);
        $stmt->bindValue(':child_id', $childId, SQLITE3_INTEGER);
        $stmt->execute();
        return $this->db->changes() > 0;
    }

    // ========================================================================
    // STATISTIKEN
    // ========================================================================

    public function getUploadsToday(int $childId): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM homework_uploads
            WHERE child_id = :id AND DATE(created_at) = DATE('now') AND is_deleted = 0
        ");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        return (int) $stmt->execute()->fetchArray()[0];
    }

    public function getStats(int $childId): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN DATE(created_at) >= DATE('now', 'start of month') THEN 1 END) as this_month,
                COUNT(DISTINCT subject) as subjects,
                COALESCE(SUM(file_size), 0) as total_size
            FROM homework_uploads
            WHERE child_id = :id AND is_deleted = 0
        ");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        return [
            'total' => (int) $row['total'],
            'this_month' => (int) $row['this_month'],
            'subjects' => (int) $row['subjects'],
            'total_size' => (int) $row['total_size'],
            'total_size_formatted' => $this->formatSize((int) $row['total_size']),
        ];
    }

    public function getSchoolYears(int $childId): array {
        $stmt = $this->db->prepare("
            SELECT DISTINCT school_year FROM homework_uploads
            WHERE child_id = :id AND is_deleted = 0
            ORDER BY school_year DESC
        ");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $years = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $years[] = $row['school_year'];
        }
        return $years;
    }

    /**
     * Stats fuer Achievement-System (Cross-DB Query)
     */
    public static function getStatsForAchievements(int $childId): array {
        try {
            $db = DatabaseConfig::getConnection(self::DB_PATH);
            if (!$db) {
                return ['total_uploads' => 0, 'distinct_subjects' => 0, 'consecutive_days' => 0];
            }

            $stmt = $db->prepare("SELECT COUNT(*) FROM homework_uploads WHERE child_id = :cid AND is_deleted = 0");
            $stmt->bindValue(':cid', $childId, SQLITE3_INTEGER);
            $totalUploads = (int)($stmt->execute()->fetchArray()[0] ?? 0);

            $stmt = $db->prepare("SELECT COUNT(DISTINCT subject) FROM homework_uploads WHERE child_id = :cid AND is_deleted = 0");
            $stmt->bindValue(':cid', $childId, SQLITE3_INTEGER);
            $distinctSubjects = (int)($stmt->execute()->fetchArray()[0] ?? 0);

            // Aufeinanderfolgende Tage mit Uploads berechnen
            $consecutiveDays = 0;
            $stmt = $db->prepare(
                "SELECT DISTINCT DATE(created_at) as upload_date FROM homework_uploads
                 WHERE child_id = :cid AND is_deleted = 0
                 ORDER BY upload_date DESC"
            );
            $stmt->bindValue(':cid', $childId, SQLITE3_INTEGER);
            $result = $stmt->execute();

            $dates = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $dates[] = $row['upload_date'];
            }

            if (!empty($dates)) {
                $consecutiveDays = 1;
                for ($i = 1; $i < count($dates); $i++) {
                    $diff = (strtotime($dates[$i - 1]) - strtotime($dates[$i])) / 86400;
                    if ($diff == 1) {
                        $consecutiveDays++;
                    } else {
                        break;
                    }
                }
            }

            $db->close();

            return [
                'total_uploads' => $totalUploads,
                'distinct_subjects' => $distinctSubjects,
                'consecutive_days' => $consecutiveDays,
            ];
        } catch (Exception $e) {
            error_log("HausaufgabenManager::getStatsForAchievements Error: " . $e->getMessage());
            return ['total_uploads' => 0, 'distinct_subjects' => 0, 'consecutive_days' => 0];
        }
    }

    // ========================================================================
    // HILFSFUNKTIONEN
    // ========================================================================

    private function formatSize(int $bytes): string {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 1) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
