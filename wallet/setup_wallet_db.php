<?php
/**
 * ============================================================================
 * sgiT Education - Bitcoin Wallet System Setup
 * ============================================================================
 * 
 * Erstellt die Datenbank-Tabellen für das Sat-Belohnungssystem.
 * Führe dieses Script EINMAL aus!
 * 
 * Tabellen:
 * - family_wallet: Eltern-Sparschwein (Gesamtpool)
 * - child_wallets: Individuelle Kind-Wallets
 * - sat_transactions: Alle Transaktionen (Earn/Withdraw/Bonus)
 * - achievements: Freigeschaltete Achievements
 * - reward_config: Konfiguration der Belohnungen
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 02.12.2025
 * ============================================================================
 */

// Lade zentrale DB-Konfiguration
require_once __DIR__ . '/../db_config.php';

// ============================================================================
// HTML HEADER
// ============================================================================
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ Wallet Setup - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, #1A3503 0%, #2d5a06 50%, #f7931a 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #1A3503;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        h1 .btc-icon {
            color: #f7931a;
            font-size: 36px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #43D240;
        }
        
        .step {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #ddd;
        }
        
        .step.success {
            border-left-color: #43D240;
            background: #d4edda;
        }
        
        .step.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .step.info {
            border-left-color: #f7931a;
            background: #fff3cd;
        }
        
        .step-title {
            font-weight: bold;
            color: #1A3503;
            margin-bottom: 8px;
        }
        
        .step-detail {
            font-size: 14px;
            color: #555;
        }
        
        .schema-box {
            background: #1A3503;
            color: #43D240;
            font-family: 'Consolas', monospace;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f7931a, #e8850f);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
        }
        
        .stat-card .label {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 5px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #43D240;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3ab837;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #f7931a;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #e8850f;
        }
        
        .nav-links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        
        .checkmark { color: #43D240; }
        .crossmark { color: #dc3545; }
        .btc-color { color: #f7931a; }
    </style>
</head>
<body>
<div class="container">
    <h1>
        <span class="btc-icon">₿</span>
        Wallet System Setup
    </h1>
    <p class="subtitle">Bitcoin Reward System für sgiT Education Platform</p>

<?php
// ============================================================================
// SETUP LOGIC
// ============================================================================

$dbPath = __DIR__ . '/wallet.db';
$errors = [];
$success = [];

try {
    // Verbindung mit WAL-Modus
    $db = DatabaseConfig::getConnection($dbPath);
    
    if (!$db) {
        throw new Exception("Konnte Datenbank nicht erstellen/öffnen");
    }
    
    $success[] = "Datenbank-Verbindung hergestellt";
    $success[] = "WAL-Modus: " . $db->querySingle("PRAGMA journal_mode");
    
    // ========================================================================
    // TABELLE 1: Family Wallet (Eltern-Sparschwein)
    // ========================================================================
    $sql_family = "
    CREATE TABLE IF NOT EXISTS family_wallet (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        
        -- Finanzen
        balance_sats INTEGER DEFAULT 0,
        total_deposited INTEGER DEFAULT 0,
        total_distributed INTEGER DEFAULT 0,
        
        -- Limits
        daily_limit INTEGER DEFAULT 100,
        weekly_limit INTEGER DEFAULT 500,
        min_score_for_reward INTEGER DEFAULT 60,
        
        -- Sicherheit
        pin_hash TEXT,
        
        -- Timestamps
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql_family);
    $success[] = "Tabelle 'family_wallet' erstellt";
    
    // ========================================================================
    // TABELLE 2: Child Wallets (Kind-Wallets)
    // ========================================================================
    $sql_children = "
    CREATE TABLE IF NOT EXISTS child_wallets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        
        -- Identifikation
        child_name TEXT NOT NULL UNIQUE,
        avatar TEXT DEFAULT '👧',
        age INTEGER,
        pin_hash TEXT,
        
        -- Finanzen
        balance_sats INTEGER DEFAULT 0,
        total_earned INTEGER DEFAULT 0,
        total_withdrawn INTEGER DEFAULT 0,
        
        -- Streak Tracking
        current_streak INTEGER DEFAULT 0,
        longest_streak INTEGER DEFAULT 0,
        last_activity_date DATE,
        
        -- Bitcoin-Adresse für Auszahlungen
        btc_address TEXT,
        lightning_address TEXT,
        
        -- Status
        is_active INTEGER DEFAULT 1,
        
        -- Timestamps
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql_children);
    $success[] = "Tabelle 'child_wallets' erstellt (mit age + pin_hash)";
    
    // ========================================================================
    // TABELLE 3: Sat Transactions (Alle Bewegungen)
    // ========================================================================
    $sql_transactions = "
    CREATE TABLE IF NOT EXISTS sat_transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        
        -- Referenzen
        child_id INTEGER NOT NULL,
        
        -- Transaktion
        type TEXT NOT NULL CHECK(type IN ('earn', 'withdraw', 'bonus', 'deposit', 'penalty')),
        amount_sats INTEGER NOT NULL,
        
        -- Kontext
        reason TEXT,
        module TEXT,
        session_id TEXT,
        score INTEGER,
        max_score INTEGER,
        
        -- Balance nach Transaktion
        balance_after INTEGER,
        
        -- Timestamps
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        
        -- Foreign Key
        FOREIGN KEY (child_id) REFERENCES child_wallets(id) ON DELETE CASCADE
    )";
    
    $db->exec($sql_transactions);
    $success[] = "Tabelle 'sat_transactions' erstellt";
    
    // Index für schnelle Abfragen
    $db->exec("CREATE INDEX IF NOT EXISTS idx_transactions_child ON sat_transactions(child_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_transactions_date ON sat_transactions(created_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_transactions_type ON sat_transactions(type)");
    $success[] = "Indizes für Transaktionen erstellt";
    
    // ========================================================================
    // TABELLE 4: Achievements
    // ========================================================================
    $sql_achievements = "
    CREATE TABLE IF NOT EXISTS wallet_achievements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        
        -- Referenzen
        child_id INTEGER NOT NULL,
        
        -- Achievement Details
        achievement_key TEXT NOT NULL,
        achievement_name TEXT NOT NULL,
        achievement_icon TEXT DEFAULT '🏆',
        category TEXT,
        
        -- Belohnung
        reward_sats INTEGER DEFAULT 0,
        
        -- Status
        unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        
        -- Unique Constraint: Ein Kind kann jedes Achievement nur einmal haben
        UNIQUE(child_id, achievement_key),
        
        -- Foreign Key
        FOREIGN KEY (child_id) REFERENCES child_wallets(id) ON DELETE CASCADE
    )";
    
    $db->exec($sql_achievements);
    $success[] = "Tabelle 'wallet_achievements' erstellt";
    
    // ========================================================================
    // TABELLE 5: Reward Configuration
    // ========================================================================
    $sql_config = "
    CREATE TABLE IF NOT EXISTS reward_config (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        config_key TEXT UNIQUE NOT NULL,
        config_value TEXT NOT NULL,
        config_type TEXT DEFAULT 'string',
        description TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql_config);
    $success[] = "Tabelle 'reward_config' erstellt";
    
    // ========================================================================
    // TABELLE 6: Daily Stats (für Limit-Tracking)
    // ========================================================================
    $sql_daily = "
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
    )";
    
    $db->exec($sql_daily);
    $success[] = "Tabelle 'daily_stats' erstellt";
    
    // ========================================================================
    // DEFAULT KONFIGURATION EINFÜGEN
    // ========================================================================
    $defaultConfig = [
        // Basis-Rewards
        ['base_sats_per_session', '10', 'int', 'Basis-Sats pro abgeschlossener Session'],
        ['sats_per_correct_answer', '3', 'int', 'Sats pro richtige Antwort'],
        ['perfect_score_bonus', '25', 'int', 'Bonus für 100% Score'],
        ['daily_login_bonus', '5', 'int', 'Bonus für tägliches Einloggen'],
        
        // Streak Boni
        ['streak_7_days_bonus', '50', 'int', 'Bonus für 7-Tage-Streak'],
        ['streak_30_days_bonus', '200', 'int', 'Bonus für 30-Tage-Streak'],
        
        // Achievement Rewards
        ['achievement_bronze', '100', 'int', 'Sats für Bronze-Achievement'],
        ['achievement_silver', '250', 'int', 'Sats für Silber-Achievement'],
        ['achievement_gold', '500', 'int', 'Sats für Gold-Achievement'],
        ['achievement_master', '1000', 'int', 'Sats für Meister-Achievement'],
        
        // Limits
        ['daily_earn_limit', '100', 'int', 'Max. Sats pro Tag'],
        ['weekly_earn_limit', '500', 'int', 'Max. Sats pro Woche'],
        ['min_score_percent', '60', 'int', 'Mindest-Score für Reward (%)'],
        
        // System
        ['system_enabled', '1', 'bool', 'Reward-System aktiv'],
    ];
    
    $stmt = $db->prepare("INSERT OR IGNORE INTO reward_config (config_key, config_value, config_type, description) VALUES (:key, :value, :type, :desc)");
    
    foreach ($defaultConfig as $cfg) {
        $stmt->bindValue(':key', $cfg[0]);
        $stmt->bindValue(':value', $cfg[1]);
        $stmt->bindValue(':type', $cfg[2]);
        $stmt->bindValue(':desc', $cfg[3]);
        $stmt->execute();
        $stmt->reset();
    }
    
    $success[] = "Standard-Konfiguration eingefügt (" . count($defaultConfig) . " Einträge)";
    
    // ========================================================================
    // INITIAL FAMILY WALLET ERSTELLEN (falls nicht vorhanden)
    // ========================================================================
    $existingWallet = $db->querySingle("SELECT id FROM family_wallet LIMIT 1");
    if (!$existingWallet) {
        $db->exec("INSERT INTO family_wallet (balance_sats, daily_limit, weekly_limit) VALUES (0, 100, 500)");
        $success[] = "Family Wallet initialisiert (Balance: 0 Sats)";
    }
    
    // ========================================================================
    // STATISTIKEN
    // ========================================================================
    $tableCount = $db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table'");
    $configCount = $db->querySingle("SELECT COUNT(*) FROM reward_config");
    
    $db->close();
    
} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

// ============================================================================
// OUTPUT
// ============================================================================

// Erfolge anzeigen
foreach ($success as $msg) {
    echo "<div class='step success'>
        <div class='step-title'><span class='checkmark'>✓</span> Erfolgreich</div>
        <div class='step-detail'>$msg</div>
    </div>";
}

// Fehler anzeigen
foreach ($errors as $msg) {
    echo "<div class='step error'>
        <div class='step-title'><span class='crossmark'>✗</span> Fehler</div>
        <div class='step-detail'>$msg</div>
    </div>";
}

if (empty($errors)) {
    // Schema Übersicht
    echo "<div class='step info'>
        <div class='step-title'>📊 Datenbank-Schema</div>
    </div>";
    
    echo "<div class='schema-box'>
<strong>family_wallet</strong>
├── balance_sats, total_deposited, total_distributed
├── daily_limit, weekly_limit, min_score_for_reward
└── pin_hash, created_at, updated_at

<strong>child_wallets</strong>
├── child_name, avatar, birth_year
├── balance_sats, total_earned, total_withdrawn
├── current_streak, longest_streak, last_activity_date
└── btc_address, lightning_address

<strong>sat_transactions</strong>
├── child_id, type, amount_sats
├── reason, module, session_id, score
└── balance_after, created_at

<strong>wallet_achievements</strong>
├── child_id, achievement_key, achievement_name
├── achievement_icon, category, reward_sats
└── unlocked_at

<strong>reward_config</strong>
├── config_key, config_value, config_type
└── description

<strong>daily_stats</strong>
├── child_id, stat_date
├── sats_earned, sessions_completed
└── questions_answered, correct_answers
</div>";

    // Statistiken
    echo "<div class='stats-grid'>
        <div class='stat-card'>
            <div class='value'>6</div>
            <div class='label'>Tabellen erstellt</div>
        </div>
        <div class='stat-card'>
            <div class='value'>$configCount</div>
            <div class='label'>Config-Einträge</div>
        </div>
        <div class='stat-card'>
            <div class='value'>0</div>
            <div class='label'>Sats Balance</div>
        </div>
        <div class='stat-card'>
            <div class='value'>✓</div>
            <div class='label'>Bereit</div>
        </div>
    </div>";
    
    echo "<div class='step success'>
        <div class='step-title'>🎉 Setup abgeschlossen!</div>
        <div class='step-detail'>
            Das Bitcoin Reward System ist einsatzbereit.<br>
            <strong>Nächster Schritt:</strong> Kind-Profile anlegen und Family Wallet aufladen.
        </div>
    </div>";
}
?>

    <div class="nav-links">
        <a href="wallet_dashboard.php" class="btn btn-secondary">₿ Wallet Dashboard</a>
        <a href="../admin_v4.php" class="btn btn-primary">← Admin Dashboard</a>
        <a href="../index.php" class="btn btn-primary">🏠 Hauptseite</a>
    </div>
</div>
</body>
</html>
