<?php
/**
 * ============================================================================
 * sgiT Education - AchievementManager
 * ============================================================================
 * 
 * Verwaltet das Achievement-System:
 * - Achievement-Definitionen (Badges, Meilensteine)
 * - Freischalten von Achievements
 * - Fortschritts-Tracking
 * - Sat-Rewards für Achievements
 * 
 * Kategorien:
 * - 🎓 learning: Lernfortschritt (Sessions, Fragen)
 * - 🔥 streak: Tägliche Aktivität
 * - ₿ sats: Sat-Meilensteine
 * - 📚 module: Modul-Meisterschaft
 * - ⭐ special: Besondere Achievements
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 02.12.2025
 * ============================================================================
 */

require_once __DIR__ . '/../db_config.php';

class AchievementManager {
    
    /** @var SQLite3 Datenbankverbindung */
    private $db;
    
    /** @var string Pfad zur Wallet-Datenbank */
    private const DB_PATH = __DIR__ . '/wallet.db';
    
    /** @var array Alle definierten Achievements */
    private static $achievements = [];
    
    // ========================================================================
    // KONSTRUKTOR
    // ========================================================================
    
    public function __construct() {
        $this->db = DatabaseConfig::getConnection(self::DB_PATH);
        
        if (!$this->db) {
            throw new Exception("Wallet-Datenbank nicht verfügbar.");
        }
        
        // Achievements laden
        self::initAchievements();
    }
    
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
    
    // ========================================================================
    // ACHIEVEMENT-DEFINITIONEN
    // ========================================================================
    
    /**
     * Initialisiert alle Achievement-Definitionen
     */
    private static function initAchievements(): void {
        if (!empty(self::$achievements)) return;
        
        self::$achievements = [
            
            // ================================================================
            // 🎓 LEARNING - Lernfortschritt
            // ================================================================
            
            'first_session' => [
                'key' => 'first_session',
                'name' => 'Erste Schritte',
                'description' => 'Schließe deine erste Lern-Session ab',
                'icon' => '🎓',
                'category' => 'learning',
                'reward_sats' => 10,
                'requirement' => ['type' => 'sessions', 'count' => 1],
                'tier' => 'bronze'
            ],
            
            'sessions_10' => [
                'key' => 'sessions_10',
                'name' => 'Fleißiger Schüler',
                'description' => 'Schließe 10 Sessions ab',
                'icon' => '📝',
                'category' => 'learning',
                'reward_sats' => 50,
                'requirement' => ['type' => 'sessions', 'count' => 10],
                'tier' => 'bronze'
            ],
            
            'sessions_50' => [
                'key' => 'sessions_50',
                'name' => 'Wissenshungrig',
                'description' => 'Schließe 50 Sessions ab',
                'icon' => '📚',
                'category' => 'learning',
                'reward_sats' => 100,
                'requirement' => ['type' => 'sessions', 'count' => 50],
                'tier' => 'silver'
            ],
            
            'sessions_100' => [
                'key' => 'sessions_100',
                'name' => 'Lern-Champion',
                'description' => 'Schließe 100 Sessions ab',
                'icon' => '🏆',
                'category' => 'learning',
                'reward_sats' => 250,
                'requirement' => ['type' => 'sessions', 'count' => 100],
                'tier' => 'gold'
            ],
            
            'sessions_500' => [
                'key' => 'sessions_500',
                'name' => 'Lern-Meister',
                'description' => 'Schließe 500 Sessions ab',
                'icon' => '👑',
                'category' => 'learning',
                'reward_sats' => 1000,
                'requirement' => ['type' => 'sessions', 'count' => 500],
                'tier' => 'master'
            ],
            
            'questions_100' => [
                'key' => 'questions_100',
                'name' => '100 Fragen',
                'description' => 'Beantworte 100 Fragen richtig',
                'icon' => '✅',
                'category' => 'learning',
                'reward_sats' => 25,
                'requirement' => ['type' => 'correct_answers', 'count' => 100],
                'tier' => 'bronze'
            ],
            
            'questions_500' => [
                'key' => 'questions_500',
                'name' => '500 Fragen',
                'description' => 'Beantworte 500 Fragen richtig',
                'icon' => '🎯',
                'category' => 'learning',
                'reward_sats' => 100,
                'requirement' => ['type' => 'correct_answers', 'count' => 500],
                'tier' => 'silver'
            ],
            
            'questions_1000' => [
                'key' => 'questions_1000',
                'name' => 'Fragenmeister',
                'description' => 'Beantworte 1.000 Fragen richtig',
                'icon' => '🌟',
                'category' => 'learning',
                'reward_sats' => 250,
                'requirement' => ['type' => 'correct_answers', 'count' => 1000],
                'tier' => 'gold'
            ],
            
            'perfect_score' => [
                'key' => 'perfect_score',
                'name' => 'Perfekt!',
                'description' => 'Erreiche 100% in einer Session',
                'icon' => '💯',
                'category' => 'learning',
                'reward_sats' => 15,
                'requirement' => ['type' => 'perfect_session', 'count' => 1],
                'tier' => 'bronze'
            ],
            
            'perfect_10' => [
                'key' => 'perfect_10',
                'name' => 'Perfektionist',
                'description' => 'Erreiche 10x 100% Score',
                'icon' => '🎖️',
                'category' => 'learning',
                'reward_sats' => 100,
                'requirement' => ['type' => 'perfect_session', 'count' => 10],
                'tier' => 'silver'
            ],
            
            // ================================================================
            // 🔥 STREAK - Tägliche Aktivität
            // ================================================================
            
            'streak_3' => [
                'key' => 'streak_3',
                'name' => '3-Tage-Streak',
                'description' => 'Lerne 3 Tage hintereinander',
                'icon' => '🔥',
                'category' => 'streak',
                'reward_sats' => 15,
                'requirement' => ['type' => 'streak', 'count' => 3],
                'tier' => 'bronze'
            ],
            
            'streak_7' => [
                'key' => 'streak_7',
                'name' => 'Wochenläufer',
                'description' => 'Lerne 7 Tage hintereinander',
                'icon' => '🗓️',
                'category' => 'streak',
                'reward_sats' => 50,
                'requirement' => ['type' => 'streak', 'count' => 7],
                'tier' => 'silver'
            ],
            
            'streak_14' => [
                'key' => 'streak_14',
                'name' => '2-Wochen-Champion',
                'description' => 'Lerne 14 Tage hintereinander',
                'icon' => '💪',
                'category' => 'streak',
                'reward_sats' => 100,
                'requirement' => ['type' => 'streak', 'count' => 14],
                'tier' => 'silver'
            ],
            
            'streak_30' => [
                'key' => 'streak_30',
                'name' => 'Monats-Meister',
                'description' => 'Lerne 30 Tage hintereinander',
                'icon' => '🏅',
                'category' => 'streak',
                'reward_sats' => 200,
                'requirement' => ['type' => 'streak', 'count' => 30],
                'tier' => 'gold'
            ],
            
            'streak_100' => [
                'key' => 'streak_100',
                'name' => '100-Tage-Legende',
                'description' => 'Lerne 100 Tage hintereinander',
                'icon' => '⚡',
                'category' => 'streak',
                'reward_sats' => 1000,
                'requirement' => ['type' => 'streak', 'count' => 100],
                'tier' => 'master'
            ],
            
            // ================================================================
            // ₿ SATS - Sat-Meilensteine
            // ================================================================
            
            'sats_100' => [
                'key' => 'sats_100',
                'name' => '100 Sats',
                'description' => 'Verdiene insgesamt 100 Sats',
                'icon' => '₿',
                'category' => 'sats',
                'reward_sats' => 10,
                'requirement' => ['type' => 'total_sats', 'count' => 100],
                'tier' => 'bronze'
            ],
            
            'sats_500' => [
                'key' => 'sats_500',
                'name' => '500 Sats',
                'description' => 'Verdiene insgesamt 500 Sats',
                'icon' => '💰',
                'category' => 'sats',
                'reward_sats' => 25,
                'requirement' => ['type' => 'total_sats', 'count' => 500],
                'tier' => 'bronze'
            ],
            
            'sats_1000' => [
                'key' => 'sats_1000',
                'name' => '1K Sats!',
                'description' => 'Verdiene insgesamt 1.000 Sats',
                'icon' => '🪙',
                'category' => 'sats',
                'reward_sats' => 50,
                'requirement' => ['type' => 'total_sats', 'count' => 1000],
                'tier' => 'silver'
            ],
            
            'sats_5000' => [
                'key' => 'sats_5000',
                'name' => '5K Sats!',
                'description' => 'Verdiene insgesamt 5.000 Sats',
                'icon' => '💎',
                'category' => 'sats',
                'reward_sats' => 100,
                'requirement' => ['type' => 'total_sats', 'count' => 5000],
                'tier' => 'gold'
            ],
            
            'sats_10000' => [
                'key' => 'sats_10000',
                'name' => '10K Sat-Millionär',
                'description' => 'Verdiene insgesamt 10.000 Sats',
                'icon' => '👑',
                'category' => 'sats',
                'reward_sats' => 250,
                'requirement' => ['type' => 'total_sats', 'count' => 10000],
                'tier' => 'master'
            ],
            
            // ================================================================
            // 📚 MODULE - Modul-Meisterschaft
            // ================================================================
            
            'module_mathematik' => [
                'key' => 'module_mathematik',
                'name' => 'Mathe-Held',
                'description' => 'Meistere 25 Fragen in Mathematik',
                'icon' => '🔢',
                'category' => 'module',
                'reward_sats' => 30,
                'requirement' => ['type' => 'module_mastery', 'module' => 'mathematik', 'count' => 25],
                'tier' => 'bronze'
            ],
            
            'module_englisch' => [
                'key' => 'module_englisch',
                'name' => 'English Pro',
                'description' => 'Meistere 25 Fragen in Englisch',
                'icon' => '🇬🇧',
                'category' => 'module',
                'reward_sats' => 30,
                'requirement' => ['type' => 'module_mastery', 'module' => 'englisch', 'count' => 25],
                'tier' => 'bronze'
            ],
            
            'module_bitcoin' => [
                'key' => 'module_bitcoin',
                'name' => 'Bitcoin-Experte',
                'description' => 'Meistere 25 Fragen zu Bitcoin',
                'icon' => '₿',
                'category' => 'module',
                'reward_sats' => 50,
                'requirement' => ['type' => 'module_mastery', 'module' => 'bitcoin', 'count' => 25],
                'tier' => 'silver'
            ],
            
            'module_programmieren' => [
                'key' => 'module_programmieren',
                'name' => 'Code Ninja',
                'description' => 'Meistere 25 Fragen in Programmieren',
                'icon' => '💻',
                'category' => 'module',
                'reward_sats' => 50,
                'requirement' => ['type' => 'module_mastery', 'module' => 'programmieren', 'count' => 25],
                'tier' => 'silver'
            ],
            
            'all_modules' => [
                'key' => 'all_modules',
                'name' => 'Universalgenie',
                'description' => 'Beantworte Fragen in allen 15 Modulen',
                'icon' => '🌈',
                'category' => 'module',
                'reward_sats' => 200,
                'requirement' => ['type' => 'all_modules', 'count' => 15],
                'tier' => 'gold'
            ],
            
            // ================================================================
            // ⭐ SPECIAL - Besondere Achievements
            // ================================================================
            
            'early_bird' => [
                'key' => 'early_bird',
                'name' => 'Frühaufsteher',
                'description' => 'Lerne vor 7 Uhr morgens',
                'icon' => '🌅',
                'category' => 'special',
                'reward_sats' => 25,
                'requirement' => ['type' => 'time_based', 'before' => '07:00'],
                'tier' => 'bronze'
            ],
            
            'night_owl' => [
                'key' => 'night_owl',
                'name' => 'Nachteule',
                'description' => 'Lerne nach 21 Uhr',
                'icon' => '🦉',
                'category' => 'special',
                'reward_sats' => 25,
                'requirement' => ['type' => 'time_based', 'after' => '21:00'],
                'tier' => 'bronze'
            ],
            
            'weekend_warrior' => [
                'key' => 'weekend_warrior',
                'name' => 'Wochenend-Krieger',
                'description' => 'Lerne am Wochenende',
                'icon' => '🎮',
                'category' => 'special',
                'reward_sats' => 20,
                'requirement' => ['type' => 'weekend'],
                'tier' => 'bronze'
            ],
            
            'comeback_kid' => [
                'key' => 'comeback_kid',
                'name' => 'Comeback Kid',
                'description' => 'Komme nach einer Pause zurück',
                'icon' => '🔄',
                'category' => 'special',
                'reward_sats' => 15,
                'requirement' => ['type' => 'comeback', 'days_away' => 7],
                'tier' => 'bronze'
            ],
            
            'birthday' => [
                'key' => 'birthday',
                'name' => 'Geburtstagskind',
                'description' => 'Lerne an deinem Geburtstag',
                'icon' => '🎂',
                'category' => 'special',
                'reward_sats' => 100,
                'requirement' => ['type' => 'birthday'],
                'tier' => 'gold'
            ],
        ];
    }
    
    // ========================================================================
    // ÖFFENTLICHE METHODEN
    // ========================================================================
    
    /**
     * Holt alle Achievement-Definitionen
     */
    public function getAllAchievementDefinitions(): array {
        return self::$achievements;
    }
    
    /**
     * Holt Achievements nach Kategorie
     */
    public function getAchievementsByCategory(string $category): array {
        return array_filter(self::$achievements, fn($a) => $a['category'] === $category);
    }
    
    /**
     * Prüft und schaltet Achievements frei
     * 
     * @param int $childId Kind-ID
     * @param array $context Aktueller Kontext (score, module, streak, etc.)
     * @return array Neu freigeschaltete Achievements
     */
    public function checkAndUnlock(int $childId, array $context = []): array {
        $unlocked = [];
        $alreadyUnlocked = $this->getUnlockedKeys($childId);
        $stats = $this->getChildStats($childId);
        
        foreach (self::$achievements as $key => $achievement) {
            // Bereits freigeschaltet?
            if (in_array($key, $alreadyUnlocked)) {
                continue;
            }
            
            // Anforderung prüfen
            if ($this->checkRequirement($achievement, $stats, $context)) {
                $result = $this->unlock($childId, $achievement);
                if ($result['success']) {
                    $unlocked[] = $achievement;
                }
            }
        }
        
        return $unlocked;
    }
    
    /**
     * Schaltet ein Achievement frei
     */
    public function unlock(int $childId, array $achievement): array {
        $this->db->exec("BEGIN TRANSACTION");
        
        try {
            // Achievement eintragen
            $stmt = $this->db->prepare("
                INSERT OR IGNORE INTO wallet_achievements 
                (child_id, achievement_key, achievement_name, achievement_icon, category, reward_sats)
                VALUES (:child_id, :key, :name, :icon, :category, :reward)
            ");
            
            $stmt->bindValue(':child_id', $childId, SQLITE3_INTEGER);
            $stmt->bindValue(':key', $achievement['key']);
            $stmt->bindValue(':name', $achievement['name']);
            $stmt->bindValue(':icon', $achievement['icon']);
            $stmt->bindValue(':category', $achievement['category']);
            $stmt->bindValue(':reward', $achievement['reward_sats'], SQLITE3_INTEGER);
            $stmt->execute();
            
            $inserted = $this->db->changes();
            
            if ($inserted > 0 && $achievement['reward_sats'] > 0) {
                // Bonus-Sats gutschreiben
                $stmt = $this->db->prepare("
                    UPDATE child_wallets 
                    SET balance_sats = balance_sats + :reward,
                        total_earned = total_earned + :reward,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $stmt->bindValue(':reward', $achievement['reward_sats'], SQLITE3_INTEGER);
                $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
                $stmt->execute();
                
                // Transaktion loggen
                $newBalance = $this->db->querySingle("SELECT balance_sats FROM child_wallets WHERE id = $childId");
                
                $stmt = $this->db->prepare("
                    INSERT INTO sat_transactions 
                    (child_id, type, amount_sats, reason, balance_after)
                    VALUES (:id, 'bonus', :amount, :reason, :balance)
                ");
                $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
                $stmt->bindValue(':amount', $achievement['reward_sats'], SQLITE3_INTEGER);
                $stmt->bindValue(':reason', "🏆 Achievement: " . $achievement['name']);
                $stmt->bindValue(':balance', $newBalance, SQLITE3_INTEGER);
                $stmt->execute();
            }
            
            $this->db->exec("COMMIT");
            
            return [
                'success' => $inserted > 0,
                'achievement' => $achievement,
                'reward' => $achievement['reward_sats']
            ];
            
        } catch (Exception $e) {
            $this->db->exec("ROLLBACK");
            error_log("AchievementManager::unlock Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Holt alle freigeschalteten Achievements eines Kindes
     */
    public function getUnlocked(int $childId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM wallet_achievements 
            WHERE child_id = :id 
            ORDER BY unlocked_at DESC
        ");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $achievements = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Vollständige Definition hinzufügen
            $key = $row['achievement_key'];
            if (isset(self::$achievements[$key])) {
                $row['definition'] = self::$achievements[$key];
            }
            $achievements[] = $row;
        }
        
        return $achievements;
    }
    
    /**
     * Holt nur die Keys der freigeschalteten Achievements
     */
    public function getUnlockedKeys(int $childId): array {
        $stmt = $this->db->prepare("SELECT achievement_key FROM wallet_achievements WHERE child_id = :id");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        
        $result = $stmt->execute();
        $keys = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $keys[] = $row['achievement_key'];
        }
        
        return $keys;
    }
    
    /**
     * Holt Achievement-Fortschritt für ein Kind
     */
    public function getProgress(int $childId): array {
        $unlocked = $this->getUnlockedKeys($childId);
        $stats = $this->getChildStats($childId);
        $progress = [];
        
        foreach (self::$achievements as $key => $achievement) {
            $isUnlocked = in_array($key, $unlocked);
            $currentProgress = $this->calculateProgress($achievement, $stats);
            
            $progress[$key] = [
                'achievement' => $achievement,
                'unlocked' => $isUnlocked,
                'current' => $currentProgress['current'],
                'required' => $currentProgress['required'],
                'percent' => $currentProgress['percent']
            ];
        }
        
        return $progress;
    }
    
    /**
     * Holt Statistiken für Achievement-Dashboard
     */
    public function getAchievementStats(int $childId): array {
        $unlocked = $this->getUnlocked($childId);
        $total = count(self::$achievements);
        $unlockedCount = count($unlocked);
        
        $totalRewards = array_sum(array_column($unlocked, 'reward_sats'));
        
        // Nach Kategorie
        $byCategory = [];
        foreach (['learning', 'streak', 'sats', 'module', 'special'] as $cat) {
            $catTotal = count(array_filter(self::$achievements, fn($a) => $a['category'] === $cat));
            $catUnlocked = count(array_filter($unlocked, fn($a) => $a['category'] === $cat));
            $byCategory[$cat] = [
                'total' => $catTotal,
                'unlocked' => $catUnlocked,
                'percent' => $catTotal > 0 ? round(($catUnlocked / $catTotal) * 100) : 0
            ];
        }
        
        // Nach Tier
        $byTier = [];
        foreach (['bronze', 'silver', 'gold', 'master'] as $tier) {
            $tierTotal = count(array_filter(self::$achievements, fn($a) => ($a['tier'] ?? 'bronze') === $tier));
            $tierUnlockedKeys = array_column(array_filter($unlocked, function($a) use ($tier) {
                $def = self::$achievements[$a['achievement_key']] ?? null;
                return $def && ($def['tier'] ?? 'bronze') === $tier;
            }), 'achievement_key');
            $byTier[$tier] = [
                'total' => $tierTotal,
                'unlocked' => count($tierUnlockedKeys)
            ];
        }
        
        return [
            'total' => $total,
            'unlocked' => $unlockedCount,
            'percent' => $total > 0 ? round(($unlockedCount / $total) * 100) : 0,
            'total_rewards' => $totalRewards,
            'by_category' => $byCategory,
            'by_tier' => $byTier,
            'recent' => array_slice($unlocked, 0, 5)
        ];
    }
    
    // ========================================================================
    // PRIVATE HILFSMETHODEN
    // ========================================================================
    
    /**
     * Holt Kind-Statistiken für Achievement-Prüfung
     */
    private function getChildStats(int $childId): array {
        // Basis-Stats aus child_wallets
        $stmt = $this->db->prepare("SELECT * FROM child_wallets WHERE id = :id");
        $stmt->bindValue(':id', $childId, SQLITE3_INTEGER);
        $childData = $stmt->execute()->fetchArray(SQLITE3_ASSOC) ?: [];
        
        // Sessions zählen
        $sessions = $this->db->querySingle("
            SELECT COUNT(*) FROM sat_transactions 
            WHERE child_id = $childId AND type = 'earn'
        ") ?: 0;
        
        // Richtige Antworten
        $correctAnswers = $this->db->querySingle("
            SELECT COALESCE(SUM(correct_answers), 0) FROM daily_stats 
            WHERE child_id = $childId
        ") ?: 0;
        
        // Perfect Sessions (score = max_score)
        $perfectSessions = $this->db->querySingle("
            SELECT COUNT(*) FROM sat_transactions 
            WHERE child_id = $childId AND type = 'earn' AND score = max_score AND max_score > 0
        ") ?: 0;
        
        // Module-Stats
        $moduleStats = [];
        $result = $this->db->query("
            SELECT module, COUNT(*) as count
            FROM sat_transactions 
            WHERE child_id = $childId AND type = 'earn' AND module IS NOT NULL
            GROUP BY LOWER(module)
        ");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $moduleStats[strtolower($row['module'])] = (int) $row['count'];
        }
        
        return [
            'sessions' => $sessions,
            'correct_answers' => $correctAnswers,
            'perfect_sessions' => $perfectSessions,
            'total_sats' => (int) ($childData['total_earned'] ?? 0),
            'current_streak' => (int) ($childData['current_streak'] ?? 0),
            'longest_streak' => (int) ($childData['longest_streak'] ?? 0),
            'modules' => $moduleStats,
            'modules_count' => count($moduleStats),
            'last_activity' => $childData['last_activity_date'] ?? null
        ];
    }
    
    /**
     * Prüft ob eine Anforderung erfüllt ist
     */
    private function checkRequirement(array $achievement, array $stats, array $context = []): bool {
        $req = $achievement['requirement'] ?? [];
        $type = $req['type'] ?? '';
        $count = $req['count'] ?? 0;
        
        switch ($type) {
            case 'sessions':
                return $stats['sessions'] >= $count;
                
            case 'correct_answers':
                return $stats['correct_answers'] >= $count;
                
            case 'perfect_session':
                return $stats['perfect_sessions'] >= $count;
                
            case 'streak':
                return max($stats['current_streak'], $stats['longest_streak']) >= $count;
                
            case 'total_sats':
                return $stats['total_sats'] >= $count;
                
            case 'module_mastery':
                $module = strtolower($req['module'] ?? '');
                return ($stats['modules'][$module] ?? 0) >= $count;
                
            case 'all_modules':
                return $stats['modules_count'] >= $count;
                
            case 'time_based':
                $hour = (int) date('H');
                if (isset($req['before'])) {
                    $beforeHour = (int) explode(':', $req['before'])[0];
                    return $hour < $beforeHour;
                }
                if (isset($req['after'])) {
                    $afterHour = (int) explode(':', $req['after'])[0];
                    return $hour >= $afterHour;
                }
                return false;
                
            case 'weekend':
                return in_array(date('N'), [6, 7]); // Samstag=6, Sonntag=7
                
            case 'comeback':
                $daysAway = $req['days_away'] ?? 7;
                if ($stats['last_activity']) {
                    $daysSince = (strtotime('today') - strtotime($stats['last_activity'])) / 86400;
                    return $daysSince >= $daysAway && isset($context['just_returned']);
                }
                return false;
                
            case 'birthday':
                // Muss vom System geprüft werden wenn Geburtsdatum bekannt
                return $context['is_birthday'] ?? false;
                
            default:
                return false;
        }
    }
    
    /**
     * Berechnet Fortschritt für ein Achievement
     */
    private function calculateProgress(array $achievement, array $stats): array {
        $req = $achievement['requirement'] ?? [];
        $type = $req['type'] ?? '';
        $required = $req['count'] ?? 1;
        $current = 0;
        
        switch ($type) {
            case 'sessions':
                $current = $stats['sessions'];
                break;
            case 'correct_answers':
                $current = $stats['correct_answers'];
                break;
            case 'perfect_session':
                $current = $stats['perfect_sessions'];
                break;
            case 'streak':
                $current = max($stats['current_streak'], $stats['longest_streak']);
                break;
            case 'total_sats':
                $current = $stats['total_sats'];
                break;
            case 'module_mastery':
                $module = strtolower($req['module'] ?? '');
                $current = $stats['modules'][$module] ?? 0;
                break;
            case 'all_modules':
                $current = $stats['modules_count'];
                break;
            default:
                $required = 1;
                $current = 0;
        }
        
        $percent = $required > 0 ? min(100, round(($current / $required) * 100)) : 0;
        
        return [
            'current' => $current,
            'required' => $required,
            'percent' => $percent
        ];
    }
}
