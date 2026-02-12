<?php
/**
 * ============================================================================
 * sgiT Education - WalletManager v1.4
 * ============================================================================
 * 
 * Zentrale Klasse für alle Wallet-Operationen:
 * - Family Wallet verwalten (Eltern-Sparschwein)
 * - Kind-Wallets erstellen und verwalten
 * - Sats verdienen, transferieren, auszahlen
 * - Transaktions-Historie
 * - Limit-Kontrolle
 * 
 * v1.5 ÄNDERUNGEN (05.12.2025) - BUG-017 FIX:
 * - FIX: runMigrations() Methode hinzugefügt - läuft bei JEDEM Start
 * - FIX: birthdate Migration wird jetzt auch bei bestehenden DBs ausgeführt
 * 
 * v1.4 ÄNDERUNGEN (03.12.2025):
 * - NEU: birthdate Feld für "Geburtstags-Lerner" Achievement
 * - Migration: Automatisches Hinzufügen der birthdate Spalte für bestehende DBs
 * - createChildWallet() um birthdate Parameter erweitert
 * 
 * v1.3 ÄNDERUNGEN (03.12.2025):
 * - deleteChild() Methode zum Löschen von Kindern
 * - Sats-Rückerstattung zum Family Wallet beim Löschen
 * 
 * v1.2 ÄNDERUNGEN (03.12.2025):
 * - getWeeklySummary() für wöchentliche Berichte
 * - getAllWeeklySummaries() und getWeeklyTrend()
 * 
 * v1.1 ÄNDERUNGEN:
 * - Auto-Setup: Erstellt Tabellen automatisch wenn sie fehlen
 * - getChildByName() Methode hinzugefügt
 * - earnSats() Integration für adaptive_learning.php
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.5
 * @date 05.12.2025
 * ============================================================================
 */

require_once __DIR__ . '/../db_config.php';

class WalletManager {
    
    /** @var SQLite3 Datenbankverbindung */
    private $db;
    
    /** @var string Pfad zur Wallet-Datenbank */
    private const DB_PATH = __DIR__ . '/wallet.db';
    
    /** @var array Gecachte Konfiguration */
    private $configCache = [];
    
    // ========================================================================
    // KONSTRUKTOR / DESTRUKTOR
    // ========================================================================
    
    /**
     * Konstruktor - Stellt Datenbankverbindung her
     * Erstellt Tabellen automatisch wenn sie fehlen!
     * 
     * @throws Exception wenn Datenbank nicht verfügbar
     */
    public function __construct() {
        $this->db = DatabaseConfig::getConnection(self::DB_PATH);
        
        if (!$this->db) {
            throw new Exception("Wallet-Datenbank nicht verfügbar.");
        }
        
        // Tabellen prüfen und ggf. erstellen
        $this->ensureTables();
        
        // Config cachen
        $this->loadConfig();
    }
    
    /**
     * Destruktor - Schließt Datenbankverbindung
     */
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
    
    // ========================================================================
    // AUTO-SETUP: TABELLEN ERSTELLEN WENN SIE FEHLEN
    // ========================================================================
    
    /**
     * Prüft ob alle Tabellen existieren und erstellt sie ggf.
     */
    private function ensureTables(): void {
        // Prüfen ob reward_config existiert (als Indikator)
        $tableExists = $this->db->querySingle(
            "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='reward_config'"
        );
        
        if (!$tableExists) {
            $this->createAllTables();
        }
        
        // IMMER Migrationen ausführen (auch bei bestehenden DBs!)
        $this->runMigrations();
    }
    
    /**
     * Führt alle Datenbank-Migrationen aus
     * Wird bei JEDEM Start ausgeführt, prüft selbst ob Migration nötig ist
     * 
     * @since v1.5 (05.12.2025) - BUG-017 Fix
     */
    private function runMigrations(): void {
        // Migration 1: birthdate Spalte für "Geburtstags-Lerner" Achievement (v1.4)
        $columnExists = $this->db->querySingle(
            "SELECT COUNT(*) FROM pragma_table_info('child_wallets') WHERE name='birthdate'"
        );
        if (!$columnExists) {
            $this->db->exec("ALTER TABLE child_wallets ADD COLUMN birthdate DATE");
            error_log("WalletManager Migration: birthdate Spalte hinzugefügt (BUG-017 Fix)");
        }

        // Migration 2: current_grade Spalte für Hausaufgaben-System (v1.6)
        $columnExists = $this->db->querySingle(
            "SELECT COUNT(*) FROM pragma_table_info('child_wallets') WHERE name='current_grade'"
        );
        if (!$columnExists) {
            $this->db->exec("ALTER TABLE child_wallets ADD COLUMN current_grade INTEGER DEFAULT NULL");
            error_log("WalletManager Migration: current_grade Spalte hinzugefügt (v1.6)");
        }

        // Migration 3: current_school_year Spalte für Hausaufgaben-System (v1.6)
        $columnExists = $this->db->querySingle(
            "SELECT COUNT(*) FROM pragma_table_info('child_wallets') WHERE name='current_school_year'"
        );
        if (!$columnExists) {
            $this->db->exec("ALTER TABLE child_wallets ADD COLUMN current_school_year TEXT DEFAULT NULL");
            error_log("WalletManager Migration: current_school_year Spalte hinzugefügt (v1.6)");
        }
    }
    
    /**
     * Erstellt alle benötigten Tabellen
     */
    private function createAllTables(): void {
        // ====================================================================
        // TABELLE 1: Family Wallet
        // ====================================================================
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS family_wallet (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                balance_sats INTEGER DEFAULT 0,
                total_deposited INTEGER DEFAULT 0,
                total_distributed INTEGER DEFAULT 0,
                daily_limit INTEGER DEFAULT 100,
                weekly_limit INTEGER DEFAULT 500,
                min_score_for_reward INTEGER DEFAULT 60,
                pin_hash TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // ====================================================================
        // TABELLE 2: Child Wallets
        // v1.4: birthdate Feld für "Geburtstags-Lerner" Achievement
        // ====================================================================
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS child_wallets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                child_name TEXT NOT NULL UNIQUE,
                avatar TEXT DEFAULT '👧',
                age INTEGER,
                birthdate DATE,
                pin_hash TEXT,
                balance_sats INTEGER DEFAULT 0,
                total_earned INTEGER DEFAULT 0,
                total_withdrawn INTEGER DEFAULT 0,
                current_streak INTEGER DEFAULT 0,
                longest_streak INTEGER DEFAULT 0,
                last_activity_date DATE,
                btc_address TEXT,
                lightning_address TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Migration: birthdate Feld hinzufügen falls es fehlt (für bestehende DBs)
        // Prüfen ob Spalte existiert, um Fehler zu vermeiden
        $columnExists = $this->db->querySingle(
            "SELECT COUNT(*) FROM pragma_table_info('child_wallets') WHERE name='birthdate'"
        );
        if (!$columnExists) {
            $this->db->exec("ALTER TABLE child_wallets ADD COLUMN birthdate DATE");
            error_log("WalletManager: birthdate Spalte hinzugefügt");
        }
        
        // ====================================================================
        // TABELLE 3: Sat Transactions
        // ====================================================================
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS sat_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                child_id INTEGER NOT NULL,
                type TEXT NOT NULL CHECK(type IN ('earn', 'withdraw', 'bonus', 'deposit', 'penalty', 'achievement')),
                amount_sats INTEGER NOT NULL,
                reason TEXT,
                module TEXT,
                session_id TEXT,
                score INTEGER,
                max_score INTEGER,
                balance_after INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (child_id) REFERENCES child_wallets(id) ON DELETE CASCADE
            )
        ");
        
        // Indizes für schnelle Abfragen
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_transactions_child ON sat_transactions(child_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_transactions_date ON sat_transactions(created_at)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_transactions_type ON sat_transactions(type)");
        
        // ====================================================================
        // TABELLE 4: Achievements
        // ====================================================================
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS wallet_achievements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                child_id INTEGER NOT NULL,
                achievement_key TEXT NOT NULL,
                achievement_name TEXT NOT NULL,
                achievement_icon TEXT DEFAULT '🏆',
                category TEXT,
                reward_sats INTEGER DEFAULT 0,
                unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(child_id, achievement_key),
                FOREIGN KEY (child_id) REFERENCES child_wallets(id) ON DELETE CASCADE
            )
        ");
        
        // ====================================================================
        // TABELLE 5: Reward Configuration
        // ====================================================================
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS reward_config (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                config_key TEXT UNIQUE NOT NULL,
                config_value TEXT NOT NULL,
                config_type TEXT DEFAULT 'string',
                description TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // ====================================================================
        // TABELLE 6: Daily Stats
        // ====================================================================
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS daily_stats (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                child_id INTEGER NOT NULL,
                stat_date DATE NOT NULL,
                sats_earned INTEGER DEFAULT 0,
                sessions_completed INTEGER DEFAULT 0,
                questions_answered INTEGER DEFAULT 0,
                correct_answers INTEGER DEFAULT 0,
                UNIQUE(child_id, stat_date),
                FOREIGN KEY (child_id) REFERENCES child_wallets(id) ON DELETE CASCADE
            )
        ");
        
        // ====================================================================
        // DEFAULT KONFIGURATION
        // ====================================================================
        $defaultConfig = [
            ['base_sats_per_session', '10', 'int', 'Basis-Sats pro abgeschlossener Session'],
            ['sats_per_correct_answer', '3', 'int', 'Sats pro richtige Antwort'],
            ['perfect_score_bonus', '25', 'int', 'Bonus für 100% Score'],
            ['daily_login_bonus', '5', 'int', 'Bonus für tägliches Einloggen'],
            ['streak_7_days_bonus', '50', 'int', 'Bonus für 7-Tage-Streak'],
            ['streak_30_days_bonus', '200', 'int', 'Bonus für 30-Tage-Streak'],
            ['achievement_bronze', '100', 'int', 'Sats für Bronze-Achievement'],
            ['achievement_silver', '250', 'int', 'Sats für Silber-Achievement'],
            ['achievement_gold', '500', 'int', 'Sats für Gold-Achievement'],
            ['achievement_master', '1000', 'int', 'Sats für Meister-Achievement'],
            ['daily_earn_limit', '100', 'int', 'Max. Sats pro Tag'],
            ['weekly_earn_limit', '500', 'int', 'Max. Sats pro Woche'],
            ['min_score_percent', '60', 'int', 'Mindest-Score für Reward (%)'],
            ['system_enabled', '1', 'bool', 'Reward-System aktiv'],
        ];
        
        $stmt = $this->db->prepare(
            "INSERT OR IGNORE INTO reward_config (config_key, config_value, config_type, description) 
             VALUES (:key, :value, :type, :desc)"
        );
        
        foreach ($defaultConfig as $cfg) {
            $stmt->bindValue(':key', $cfg[0]);
            $stmt->bindValue(':value', $cfg[1]);
            $stmt->bindValue(':type', $cfg[2]);
            $stmt->bindValue(':desc', $cfg[3]);
            $stmt->execute();
            $stmt->reset();
        }
        
        // ====================================================================
        // INITIAL FAMILY WALLET
        // ====================================================================
        $existingWallet = $this->db->querySingle("SELECT id FROM family_wallet LIMIT 1");
        if (!$existingWallet) {
            // Starte mit 10.000 Test-Sats für sofortiges Testen
            $this->db->exec("INSERT INTO family_wallet (balance_sats, daily_limit, weekly_limit) VALUES (10000, 100, 500)");
        }
        
        error_log("WalletManager: Alle Tabellen automatisch erstellt!");
    }
    
    // ========================================================================
    // KONFIGURATION
    // ========================================================================
    
    /**
     * Lädt alle Konfigurationswerte in den Cache
     */
    private function loadConfig(): void {
        try {
            $result = $this->db->query("SELECT config_key, config_value, config_type FROM reward_config");
            
            if ($result) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $value = $row['config_value'];
                    
                    switch ($row['config_type']) {
                        case 'int':
                            $value = (int) $value;
                            break;
                        case 'float':
                            $value = (float) $value;
                            break;
                        case 'bool':
                            $value = (bool) $value;
                            break;
                    }
                    
                    $this->configCache[$row['config_key']] = $value;
                }
            }
        } catch (Exception $e) {
            error_log("WalletManager::loadConfig Error: " . $e->getMessage());
        }
    }
    
    /**
     * Holt einen Konfigurationswert
     */
    public function getConfig(string $key, $default = null) {
        return $this->configCache[$key] ?? $default;
    }
    
    /**
     * Setzt einen Konfigurationswert
     */
    public function setConfig(string $key, $value): bool {
        $stmt = $this->db->prepare("
            UPDATE reward_config 
            SET config_value = :value, updated_at = CURRENT_TIMESTAMP 
            WHERE config_key = :key
        ");
        
        $stmt->bindValue(':key', $key);
        $stmt->bindValue(':value', (string) $value);
        
        if ($stmt->execute()) {
            $this->configCache[$key] = $value;
            return true;
        }
        
        return false;
    }
    
    // ========================================================================
    // FAMILY WALLET (Eltern-Sparschwein)
    // ========================================================================
    
    /**
     * Holt den aktuellen Family Wallet Status
     */
    public function getFamilyWallet(): ?array {
        $result = $this->db->query("SELECT * FROM family_wallet LIMIT 1");
        return $result->fetchArray(SQLITE3_ASSOC) ?: null;
    }
    
    /**
     * Lädt Sats in das Family Wallet (Eltern-Einzahlung)
     */
    public function depositToFamily(int $amount, string $note = ''): bool {
        if ($amount <= 0) {
            return false;
        }
        
        $this->db->exec("BEGIN TRANSACTION");
        
        try {
            $stmt = $this->db->prepare("
                UPDATE family_wallet 
                SET balance_sats = balance_sats + :amount,
                    total_deposited = total_deposited + :amount,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->bindValue(':amount', $amount, SQLITE3_INTEGER);
            $stmt->execute();
            
            $this->db->exec("COMMIT");
            return true;
            
        } catch (Exception $e) {
            $this->db->exec("ROLLBACK");
            error_log("WalletManager::depositToFamily Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Holt die aktuelle Family Balance
     */
    public function getFamilyBalance(): int {
        return (int) $this->db->querySingle("SELECT balance_sats FROM family_wallet LIMIT 1") ?: 0;
    }
    
    // ========================================================================
    // KIND-WALLETS
    // ========================================================================
    
    /**
     * Erstellt ein neues Kind-Wallet
     * 
     * v1.4: birthdate Parameter hinzugefügt für "Geburtstags-Lerner" Achievement
     * 
     * @param string $name Name des Kindes
     * @param string $avatar Avatar-Emoji
     * @param int|null $age Alter (berechnet aus birthdate)
     * @param string|null $pin 4-stellige PIN
     * @param string|null $birthdate Geburtsdatum (YYYY-MM-DD)
     * @return int|false Child-ID oder false bei Fehler
     */
    public function createChildWallet(string $name, string $avatar = '👧', ?int $age = null, ?string $pin = null, ?string $birthdate = null) {
        $pinHash = $pin ? password_hash($pin, PASSWORD_DEFAULT) : null;
        
        $stmt = $this->db->prepare("
            INSERT INTO child_wallets (child_name, avatar, age, pin_hash, birthdate)
            VALUES (:name, :avatar, :age, :pin_hash, :birthdate)
        ");
        
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':avatar', $avatar);
        $stmt->bindValue(':age', $age, SQLITE3_INTEGER);
        $stmt->bindValue(':pin_hash', $pinHash);
        $stmt->bindValue(':birthdate', $birthdate);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertRowID();
        }
        
        return false;
    }
    
    /**
     * Prüft ob ein Name bereits existiert
     */
    public function nameExists(string $name): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM child_wallets WHERE LOWER(child_name) = LOWER(:name)");
        $stmt->bindValue(':name', $name);
        $result = $stmt->execute();
        return (int) $result->fetchArray()[0] > 0;
    }
    
    /**
     * Verifiziert die PIN eines Kindes
     */
    public function verifyPin(int $childId, string $pin): bool {
        $stmt = $this->db->prepare("SELECT pin_hash FROM child_wallets WHERE id = :id");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$row || empty($row['pin_hash'])) {
            return false;
        }
        
        return password_verify($pin, $row['pin_hash']);
    }
    
    /**
     * Holt ein Kind anhand des Namens
     * 
     * @param string $name
     * @return array|null
     */
    public function getChildByName(string $name): ?array {
        $stmt = $this->db->prepare("SELECT * FROM child_wallets WHERE LOWER(child_name) = LOWER(:name) AND is_active = 1");
        $stmt->bindValue(':name', $name);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC) ?: null;
    }
    
    /**
     * Holt alle Kind-Wallets
     */
    public function getChildWallets(bool $activeOnly = true): array {
        $sql = "SELECT * FROM child_wallets";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY child_name ASC";
        
        $result = $this->db->query($sql);
        $children = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $children[] = $row;
        }
        
        return $children;
    }
    
    /**
     * Holt ein einzelnes Kind-Wallet
     */
    public function getChildWallet(int $childId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM child_wallets WHERE id = :id");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC) ?: null;
    }
    
    /**
     * Aktualisiert Kind-Wallet Daten
     */
    public function updateChildWallet(int $childId, array $data): bool {
        $allowedFields = ['child_name', 'avatar', 'birth_year', 'btc_address', 'lightning_address', 'is_active', 'current_grade', 'current_school_year'];
        $updates = [];
        $values = [':id' => $childId];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = :$key";
                $values[":$key"] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE child_wallets SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        foreach ($values as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Löscht ein Kind-Wallet und alle zugehörigen Daten
     * 
     * ACHTUNG: Diese Aktion ist NICHT rückgängig zu machen!
     * Löscht: Kind, Transaktionen, Achievements, Daily Stats
     * 
     * @param int $childId Kind-ID
     * @return array ['success' => bool, 'error' => string|null, 'deleted_name' => string|null]
     */
    public function deleteChild(int $childId): array {
        // Kind-Daten holen (für Rückmeldung)
        $child = $this->getChildWallet($childId);
        if (!$child) {
            return ['success' => false, 'error' => 'Kind nicht gefunden'];
        }
        
        $childName = $child['child_name'];
        $childBalance = (int) $child['balance_sats'];
        
        $this->db->exec("BEGIN TRANSACTION");
        
        try {
            // Sats zurück zum Family Wallet (falls vorhanden)
            if ($childBalance > 0) {
                $stmt = $this->db->prepare("
                    UPDATE family_wallet 
                    SET balance_sats = balance_sats + :sats,
                        updated_at = CURRENT_TIMESTAMP
                ");
                $stmt->bindValue(':sats', $childBalance, SQLITE3_INTEGER);
                $stmt->execute();
            }
            
            // Löschen - CASCADE löscht automatisch:
            // - sat_transactions (FOREIGN KEY)
            // - wallet_achievements (FOREIGN KEY)
            // - daily_stats (FOREIGN KEY)
            $stmt = $this->db->prepare("DELETE FROM child_wallets WHERE id = :id");
            $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
            $stmt->execute();
            
            $this->db->exec("COMMIT");
            
            return [
                'success' => true,
                'deleted_name' => $childName,
                'refunded_sats' => $childBalance
            ];
            
        } catch (Exception $e) {
            $this->db->exec("ROLLBACK");
            error_log("WalletManager::deleteChild Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Holt die Balance eines Kindes
     */
    public function getChildBalance(int $childId): int {
        $stmt = $this->db->prepare("SELECT balance_sats FROM child_wallets WHERE id = :id");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        return $row ? (int) $row['balance_sats'] : 0;
    }
    
    // ========================================================================
    // SATS VERDIENEN (REWARD SYSTEM)
    // ========================================================================
    
    /**
     * Berechnet den Reward für eine abgeschlossene Session
     */
    public function calculateReward(int $score, int $maxScore, string $module): array {
        $breakdown = [];
        $totalSats = 0;
        
        $percent = $maxScore > 0 ? ($score / $maxScore) * 100 : 0;
        
        $minScore = $this->getConfig('min_score_percent', 60);
        if ($percent < $minScore) {
            return [
                'sats' => 0,
                'breakdown' => ['Mindest-Score nicht erreicht (' . round($percent) . '% < ' . $minScore . '%)'],
                'eligible' => false
            ];
        }
        
        // 1. Basis-Sats
        $baseSats = $this->getConfig('base_sats_per_session', 10);
        $totalSats += $baseSats;
        $breakdown[] = "Basis: +{$baseSats} Sats";
        
        // 2. Pro richtige Antwort
        $perCorrect = $this->getConfig('sats_per_correct_answer', 3);
        $correctBonus = $score * $perCorrect;
        $totalSats += $correctBonus;
        $breakdown[] = "Richtige Antworten ({$score}x): +{$correctBonus} Sats";
        
        // 3. Perfect Score Bonus
        if ($score === $maxScore && $maxScore > 0) {
            $perfectBonus = $this->getConfig('perfect_score_bonus', 25);
            $totalSats += $perfectBonus;
            $breakdown[] = "🎯 100% Bonus: +{$perfectBonus} Sats";
        }
        
        return [
            'sats' => $totalSats,
            'breakdown' => $breakdown,
            'eligible' => true,
            'percent' => round($percent, 1)
        ];
    }
    
    /**
     * Gibt Sats an ein Kind für eine abgeschlossene Session
     */
    public function earnSats(int $childId, int $score, int $maxScore, string $module, ?string $sessionId = null): array {
        // System aktiv?
        if (!$this->getConfig('system_enabled', true)) {
            return ['success' => false, 'error' => 'Reward-System ist deaktiviert'];
        }
        
        // Reward berechnen
        $reward = $this->calculateReward($score, $maxScore, $module);
        
        if (!$reward['eligible']) {
            return [
                'success' => false, 
                'sats' => 0,
                'error' => $reward['breakdown'][0] ?? 'Nicht qualifiziert',
                'breakdown' => $reward['breakdown']
            ];
        }
        
        $sats = $reward['sats'];
        
        // Daily Limit prüfen
        $dailyLimit = $this->getConfig('daily_earn_limit', 100);
        $earnedToday = $this->getEarnedToday($childId);
        
        if ($earnedToday + $sats > $dailyLimit) {
            $sats = max(0, $dailyLimit - $earnedToday);
            if ($sats === 0) {
                return [
                    'success' => false,
                    'sats' => 0,
                    'error' => 'Tageslimit erreicht (' . $dailyLimit . ' Sats)',
                    'breakdown' => $reward['breakdown']
                ];
            }
            $reward['breakdown'][] = "⚠️ Tageslimit: auf {$sats} Sats reduziert";
        }
        
        // Family Wallet Balance prüfen
        $familyBalance = $this->getFamilyBalance();
        if ($familyBalance < $sats) {
            return [
                'success' => false,
                'sats' => 0,
                'error' => 'Family Wallet leer! Eltern müssen aufladen.',
                'breakdown' => $reward['breakdown']
            ];
        }
        
        // Transaktion durchführen
        $this->db->exec("BEGIN TRANSACTION");
        
        try {
            // 1. Family Wallet reduzieren
            $stmt = $this->db->prepare("
                UPDATE family_wallet 
                SET balance_sats = balance_sats - :sats,
                    total_distributed = total_distributed + :sats,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->bindValue(':sats', $sats, SQLITE3_INTEGER);
            $stmt->execute();
            
            // 2. Kind-Wallet erhöhen
            $stmt = $this->db->prepare("
                UPDATE child_wallets 
                SET balance_sats = balance_sats + :sats,
                    total_earned = total_earned + :sats,
                    last_activity_date = DATE('now'),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->bindValue(':sats', $sats, SQLITE3_INTEGER);
            $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
            $stmt->execute();
            
            $newBalance = $this->getChildBalance($childId);
            
            // 3. Transaktion loggen
            $stmt = $this->db->prepare("
                INSERT INTO sat_transactions 
                (child_id, type, amount_sats, reason, module, session_id, score, max_score, balance_after)
                VALUES (:child_id, 'earn', :sats, :reason, :module, :session_id, :score, :max_score, :balance)
            ");
            $stmt->bindValue(':child_id', $childId, SQLITE3_INTEGER);
            $stmt->bindValue(':sats', $sats, SQLITE3_INTEGER);
            $stmt->bindValue(':reason', "Session abgeschlossen: {$score}/{$maxScore}");
            $stmt->bindValue(':module', $module);
            $stmt->bindValue(':session_id', $sessionId);
            $stmt->bindValue(':score', $score, SQLITE3_INTEGER);
            $stmt->bindValue(':max_score', $maxScore, SQLITE3_INTEGER);
            $stmt->bindValue(':balance', $newBalance, SQLITE3_INTEGER);
            $stmt->execute();
            
            // 4. Daily Stats updaten
            $this->updateDailyStats($childId, $sats, $score, $maxScore);
            
            // 5. Streak updaten
            $this->updateStreak($childId);
            
            $this->db->exec("COMMIT");
            
            return [
                'success' => true,
                'sats' => $sats,
                'new_balance' => $newBalance,
                'breakdown' => $reward['breakdown'],
                'percent' => $reward['percent']
            ];
            
        } catch (Exception $e) {
            $this->db->exec("ROLLBACK");
            error_log("WalletManager::earnSats Error: " . $e->getMessage());
            return [
                'success' => false,
                'sats' => 0,
                'error' => 'Datenbankfehler: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Vergibt einen festen Sat-Betrag (umgeht calculateReward)
     *
     * Fuer Module wie Hausaufgaben, die einen festen Betrag vergeben
     * statt den Score-basierten Reward aus calculateReward().
     * Folgt dem Transaction-Pattern aus earnSats().
     *
     * @param int $childId Kind-ID
     * @param int $amount Fester Sat-Betrag
     * @param string $reason Grund fuer die Gutschrift
     * @param string $module Modul-Name (z.B. 'hausaufgaben')
     * @return array Ergebnis mit success, sats, new_balance
     */
    public function creditSats(int $childId, int $amount, string $reason = '', string $module = ''): array {
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Ungueltiger Betrag'];
        }

        // System aktiv?
        if (!$this->getConfig('system_enabled', true)) {
            return ['success' => false, 'error' => 'Reward-System ist deaktiviert'];
        }

        // Family Wallet Balance pruefen
        $familyBalance = $this->getFamilyBalance();
        if ($familyBalance < $amount) {
            return [
                'success' => false,
                'sats' => 0,
                'error' => 'Family Wallet leer! Eltern muessen aufladen.'
            ];
        }

        $this->db->exec("BEGIN TRANSACTION");

        try {
            // 1. Family Wallet reduzieren
            $stmt = $this->db->prepare("
                UPDATE family_wallet
                SET balance_sats = balance_sats - :sats,
                    total_distributed = total_distributed + :sats,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->bindValue(':sats', $amount, SQLITE3_INTEGER);
            $stmt->execute();

            // 2. Kind-Wallet erhoehen
            $stmt = $this->db->prepare("
                UPDATE child_wallets
                SET balance_sats = balance_sats + :sats,
                    total_earned = total_earned + :sats,
                    last_activity_date = DATE('now'),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->bindValue(':sats', $amount, SQLITE3_INTEGER);
            $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
            $stmt->execute();

            $newBalance = $this->getChildBalance($childId);

            // 3. Transaktion loggen
            $stmt = $this->db->prepare("
                INSERT INTO sat_transactions
                (child_id, type, amount_sats, reason, module, balance_after)
                VALUES (:child_id, 'earn', :sats, :reason, :module, :balance)
            ");
            $stmt->bindValue(':child_id', $childId, SQLITE3_INTEGER);
            $stmt->bindValue(':sats', $amount, SQLITE3_INTEGER);
            $stmt->bindValue(':reason', $reason);
            $stmt->bindValue(':module', $module);
            $stmt->bindValue(':balance', $newBalance, SQLITE3_INTEGER);
            $stmt->execute();

            // 4. Daily Stats updaten
            $this->updateDailyStats($childId, $amount, 0, 0);

            // 5. Streak updaten
            $this->updateStreak($childId);

            $this->db->exec("COMMIT");

            return [
                'success' => true,
                'sats' => $amount,
                'new_balance' => $newBalance
            ];

        } catch (Exception $e) {
            $this->db->exec("ROLLBACK");
            error_log("WalletManager::creditSats Error: " . $e->getMessage());
            return [
                'success' => false,
                'sats' => 0,
                'error' => 'Datenbankfehler: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Holt die heute verdienten Sats eines Kindes
     */
    public function getEarnedToday(int $childId): int {
        $stmt = $this->db->prepare("
            SELECT COALESCE(sats_earned, 0) as earned
            FROM daily_stats 
            WHERE child_id = :id AND stat_date = DATE('now')
        ");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        return $row ? (int) $row['earned'] : 0;
    }
    
    /**
     * Aktualisiert die täglichen Statistiken
     */
    private function updateDailyStats(int $childId, int $satsEarned, int $correct, int $total): void {
        $stmt = $this->db->prepare("
            INSERT INTO daily_stats (child_id, stat_date, sats_earned, sessions_completed, questions_answered, correct_answers)
            VALUES (:id, DATE('now'), :sats, 1, :total, :correct)
            ON CONFLICT(child_id, stat_date) DO UPDATE SET
                sats_earned = sats_earned + :sats,
                sessions_completed = sessions_completed + 1,
                questions_answered = questions_answered + :total,
                correct_answers = correct_answers + :correct
        ");
        
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $stmt->bindValue(':sats', $satsEarned, SQLITE3_INTEGER);
        $stmt->bindValue(':total', $total, SQLITE3_INTEGER);
        $stmt->bindValue(':correct', $correct, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    /**
     * Aktualisiert den Streak eines Kindes
     */
    private function updateStreak(int $childId): void {
        $child = $this->getChildWallet($childId);
        if (!$child) return;
        
        $lastDate = $child['last_activity_date'];
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $newStreak = 1;
        
        if ($lastDate === $yesterday) {
            $newStreak = $child['current_streak'] + 1;
        } elseif ($lastDate === $today) {
            $newStreak = $child['current_streak'];
        }
        
        $longestStreak = max($child['longest_streak'], $newStreak);
        
        $stmt = $this->db->prepare("
            UPDATE child_wallets 
            SET current_streak = :streak, longest_streak = :longest
            WHERE id = :id
        ");
        $stmt->bindValue(':streak', $newStreak, SQLITE3_INTEGER);
        $stmt->bindValue(':longest', $longestStreak, SQLITE3_INTEGER);
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // ========================================================================
    // TRANSAKTIONEN & HISTORIE
    // ========================================================================
    
    /**
     * Holt Transaktions-Historie eines Kindes
     */
    public function getTransactions(int $childId, int $limit = 50): array {
        $stmt = $this->db->prepare("
            SELECT * FROM sat_transactions 
            WHERE child_id = :id 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $transactions = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $transactions[] = $row;
        }
        
        return $transactions;
    }
    
    /**
     * Holt alle Transaktionen (für Eltern-Übersicht)
     */
    public function getAllTransactions(int $limit = 100): array {
        $stmt = $this->db->prepare("
            SELECT t.*, c.child_name, c.avatar
            FROM sat_transactions t
            JOIN child_wallets c ON t.child_id = c.id
            ORDER BY t.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $transactions = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $transactions[] = $row;
        }
        
        return $transactions;
    }
    
    // ========================================================================
    // STATISTIKEN
    // ========================================================================
    
    /**
     * Holt Gesamtstatistiken
     */
    public function getStats(): array {
        $family = $this->getFamilyWallet();
        
        $totalChildren = $this->db->querySingle("SELECT COUNT(*) FROM child_wallets WHERE is_active = 1");
        $totalTransactions = $this->db->querySingle("SELECT COUNT(*) FROM sat_transactions");
        $totalEarned = $this->db->querySingle("SELECT COALESCE(SUM(amount_sats), 0) FROM sat_transactions WHERE type = 'earn'");
        
        $topEarner = $this->db->query("
            SELECT c.child_name, c.avatar, SUM(t.amount_sats) as weekly_sats
            FROM sat_transactions t
            JOIN child_wallets c ON t.child_id = c.id
            WHERE t.type = 'earn' AND t.created_at >= DATE('now', '-7 days')
            GROUP BY t.child_id
            ORDER BY weekly_sats DESC
            LIMIT 1
        ")->fetchArray(SQLITE3_ASSOC);
        
        return [
            'family_balance' => $family['balance_sats'] ?? 0,
            'total_deposited' => $family['total_deposited'] ?? 0,
            'total_distributed' => $family['total_distributed'] ?? 0,
            'active_children' => $totalChildren,
            'total_transactions' => $totalTransactions,
            'total_earned' => $totalEarned,
            'top_earner' => $topEarner
        ];
    }
    
    // ========================================================================
    // AUSZAHLUNG (Withdraw)
    // ========================================================================
    
    // ========================================================================
    // WÖCHENTLICHE ZUSAMMENFASSUNG (v1.2)
    // ========================================================================
    
    /**
     * Holt die wöchentliche Zusammenfassung für ein Kind
     * 
     * @param int $childId Kind-ID
     * @param string $weekStart Start der Woche (YYYY-MM-DD), default: Montag dieser Woche
     * @return array Wöchentliche Statistiken
     */
    public function getWeeklySummary(int $childId, ?string $weekStart = null): array {
        // Wochenstart bestimmen (Montag)
        if (!$weekStart) {
            $weekStart = date('Y-m-d', strtotime('monday this week'));
        }
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        
        // Kind-Daten
        $child = $this->getChildWallet($childId);
        if (!$child) {
            return ['error' => 'Kind nicht gefunden'];
        }
        
        // Basis-Stats aus daily_stats
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(sats_earned), 0) as total_sats,
                COALESCE(SUM(sessions_completed), 0) as total_sessions,
                COALESCE(SUM(questions_answered), 0) as total_questions,
                COALESCE(SUM(correct_answers), 0) as total_correct,
                COUNT(DISTINCT stat_date) as active_days
            FROM daily_stats 
            WHERE child_id = :id 
            AND stat_date BETWEEN :start AND :end
        ");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $stmt->bindValue(':start', $weekStart);
        $stmt->bindValue(':end', $weekEnd);
        $result = $stmt->execute();
        $weekStats = $result->fetchArray(SQLITE3_ASSOC);
        
        // Tägliche Aufschlüsselung
        $stmt = $this->db->prepare("
            SELECT stat_date, sats_earned, sessions_completed, questions_answered, correct_answers
            FROM daily_stats 
            WHERE child_id = :id 
            AND stat_date BETWEEN :start AND :end
            ORDER BY stat_date ASC
        ");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $stmt->bindValue(':start', $weekStart);
        $stmt->bindValue(':end', $weekEnd);
        $result = $stmt->execute();
        
        $dailyBreakdown = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $dailyBreakdown[$row['stat_date']] = $row;
        }
        
        // Modul-Verteilung
        $stmt = $this->db->prepare("
            SELECT module, COUNT(*) as count, SUM(amount_sats) as sats
            FROM sat_transactions 
            WHERE child_id = :id 
            AND type = 'earn'
            AND DATE(created_at) BETWEEN :start AND :end
            AND module IS NOT NULL
            GROUP BY LOWER(module)
            ORDER BY count DESC
        ");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $stmt->bindValue(':start', $weekStart);
        $stmt->bindValue(':end', $weekEnd);
        $result = $stmt->execute();
        
        $moduleBreakdown = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $moduleBreakdown[] = $row;
        }
        
        // Achievements diese Woche
        $stmt = $this->db->prepare("
            SELECT achievement_key, achievement_name, achievement_icon, reward_sats, unlocked_at
            FROM wallet_achievements 
            WHERE child_id = :id 
            AND DATE(unlocked_at) BETWEEN :start AND :end
            ORDER BY unlocked_at DESC
        ");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $stmt->bindValue(':start', $weekStart);
        $stmt->bindValue(':end', $weekEnd);
        $result = $stmt->execute();
        
        $achievements = [];
        $achievementSats = 0;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $achievements[] = $row;
            $achievementSats += (int) $row['reward_sats'];
        }
        
        // Vorwoche zum Vergleich
        $prevWeekStart = date('Y-m-d', strtotime($weekStart . ' -7 days'));
        $prevWeekEnd = date('Y-m-d', strtotime($weekStart . ' -1 day'));
        
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(sats_earned), 0) as total_sats,
                COALESCE(SUM(sessions_completed), 0) as total_sessions,
                COUNT(DISTINCT stat_date) as active_days
            FROM daily_stats 
            WHERE child_id = :id 
            AND stat_date BETWEEN :start AND :end
        ");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $stmt->bindValue(':start', $prevWeekStart);
        $stmt->bindValue(':end', $prevWeekEnd);
        $result = $stmt->execute();
        $prevWeekStats = $result->fetchArray(SQLITE3_ASSOC);
        
        // Erfolgsquote berechnen
        $successRate = 0;
        if ($weekStats['total_questions'] > 0) {
            $successRate = round(($weekStats['total_correct'] / $weekStats['total_questions']) * 100, 1);
        }
        
        // Vergleiche berechnen
        $satsDiff = (int)$weekStats['total_sats'] - (int)$prevWeekStats['total_sats'];
        $sessionsDiff = (int)$weekStats['total_sessions'] - (int)$prevWeekStats['total_sessions'];
        
        return [
            'child' => $child,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'stats' => [
                'total_sats' => (int) $weekStats['total_sats'],
                'total_sessions' => (int) $weekStats['total_sessions'],
                'total_questions' => (int) $weekStats['total_questions'],
                'total_correct' => (int) $weekStats['total_correct'],
                'success_rate' => $successRate,
                'active_days' => (int) $weekStats['active_days'],
                'achievement_sats' => $achievementSats
            ],
            'comparison' => [
                'sats_diff' => $satsDiff,
                'sats_trend' => $satsDiff > 0 ? 'up' : ($satsDiff < 0 ? 'down' : 'same'),
                'sessions_diff' => $sessionsDiff,
                'sessions_trend' => $sessionsDiff > 0 ? 'up' : ($sessionsDiff < 0 ? 'down' : 'same'),
                'prev_week_sats' => (int) $prevWeekStats['total_sats'],
                'prev_week_sessions' => (int) $prevWeekStats['total_sessions']
            ],
            'daily_breakdown' => $dailyBreakdown,
            'module_breakdown' => $moduleBreakdown,
            'achievements' => $achievements,
            'streak' => [
                'current' => (int) $child['current_streak'],
                'longest' => (int) $child['longest_streak']
            ]
        ];
    }
    
    /**
     * Holt wöchentliche Zusammenfassung für ALLE Kinder
     */
    public function getAllWeeklySummaries(?string $weekStart = null): array {
        $children = $this->getChildWallets();
        $summaries = [];
        
        foreach ($children as $child) {
            $summaries[$child['id']] = $this->getWeeklySummary($child['id'], $weekStart);
        }
        
        return $summaries;
    }
    
    /**
     * Holt die letzten N Wochen für Trend-Analyse
     */
    public function getWeeklyTrend(int $childId, int $weeks = 4): array {
        $trend = [];
        
        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = date('Y-m-d', strtotime("monday this week -" . ($i * 7) . " days"));
            $summary = $this->getWeeklySummary($childId, $weekStart);
            
            $trend[] = [
                'week_start' => $weekStart,
                'week_label' => $i === 0 ? 'Diese Woche' : ($i === 1 ? 'Letzte Woche' : 'Vor ' . $i . ' Wochen'),
                'sats' => $summary['stats']['total_sats'],
                'sessions' => $summary['stats']['total_sessions'],
                'active_days' => $summary['stats']['active_days']
            ];
        }
        
        return array_reverse($trend); // Älteste zuerst
    }
    
    // ========================================================================
    // AUSZAHLUNG (Withdraw)
    // ========================================================================
    
    /**
     * Zieht Sats vom Kind-Wallet ab (für echte BTC-Auszahlung)
     */
    public function withdraw(int $childId, int $amount, string $note = 'Auszahlung'): array {
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Ungültiger Betrag'];
        }
        
        $balance = $this->getChildBalance($childId);
        if ($balance < $amount) {
            return ['success' => false, 'error' => 'Nicht genügend Sats'];
        }
        
        $this->db->exec("BEGIN TRANSACTION");
        
        try {
            $stmt = $this->db->prepare("
                UPDATE child_wallets 
                SET balance_sats = balance_sats - :amount,
                    total_withdrawn = total_withdrawn + :amount,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->bindValue(':amount', $amount, SQLITE3_INTEGER);
            $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
            $stmt->execute();
            
            $newBalance = $this->getChildBalance($childId);
            
            $stmt = $this->db->prepare("
                INSERT INTO sat_transactions 
                (child_id, type, amount_sats, reason, balance_after)
                VALUES (:id, 'withdraw', :amount, :reason, :balance)
            ");
            $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
            $stmt->bindValue(':amount', $amount, SQLITE3_INTEGER);
            $stmt->bindValue(':reason', $note);
            $stmt->bindValue(':balance', $newBalance, SQLITE3_INTEGER);
            $stmt->execute();
            
            $this->db->exec("COMMIT");
            
            return [
                'success' => true,
                'amount' => $amount,
                'new_balance' => $newBalance
            ];
            
        } catch (Exception $e) {
            $this->db->exec("ROLLBACK");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
