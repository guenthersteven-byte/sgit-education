<?php
// Setup Script für user_sessions Tabelle
// Führe dieses Script EINMAL aus!

echo "<!DOCTYPE html>
<html>
<head>
    <title>SQL Setup - sgiT Education</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 600px;
        }
        h1 {
            color: #1A3503;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        button {
            background: #43D240;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
        button:hover {
            background: #1A3503;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔧 SQL Setup für sgiT Education</h1>";

// Prüfen ob die Datenbank existiert
$db_path = 'AI/data/questions.db';

if (!file_exists($db_path)) {
    // Erstelle Verzeichnis falls nicht vorhanden
    $dir = dirname($db_path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "<div class='info'>📁 Verzeichnis erstellt: $dir</div>";
    }
}

try {
    // Verbindung zur SQLite Datenbank
    $db = new SQLite3($db_path);
    echo "<div class='success'>✅ Datenbank-Verbindung hergestellt</div>";
    
    // WAL-Modus aktivieren für bessere Performance
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA synchronous = NORMAL');
    $db->exec('PRAGMA cache_size = -64000');  // 64MB Cache
    $db->exec('PRAGMA temp_store = MEMORY');
    $db->exec('PRAGMA busy_timeout = 5000');
    
    $journalMode = $db->querySingle('PRAGMA journal_mode');
    echo "<div class='success'>✅ WAL-Modus aktiviert (Journal: $journalMode)</div>";
    
    // Erstelle user_sessions Tabelle
    $sql_sessions = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        session_id TEXT,
        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
        module TEXT,
        ip_address TEXT,
        user_agent TEXT
    )";
    
    if ($db->exec($sql_sessions)) {
        echo "<div class='success'>✅ Tabelle 'user_sessions' erstellt/geprüft</div>";
    }
    
    // Erstelle users Tabelle falls nicht vorhanden
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT,
        age INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME,
        total_score INTEGER DEFAULT 0,
        level INTEGER DEFAULT 1
    )";
    
    if ($db->exec($sql_users)) {
        echo "<div class='success'>✅ Tabelle 'users' erstellt/geprüft</div>";
    }
    
    // Erstelle questions Tabelle falls nicht vorhanden
    $sql_questions = "CREATE TABLE IF NOT EXISTS questions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        module TEXT NOT NULL,
        question TEXT NOT NULL,
        answer TEXT,
        correct_answer TEXT NOT NULL,
        wrong_answers TEXT,
        options TEXT,
        difficulty INTEGER DEFAULT 1,
        age_group INTEGER,
        age_min INTEGER,
        age_max INTEGER,
        ai_generated INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        explanation TEXT,
        times_used INTEGER DEFAULT 0,
        question_hash TEXT,
        source TEXT DEFAULT 'ai_generated',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($db->exec($sql_questions)) {
        echo "<div class='success'>✅ Tabelle 'questions' erstellt/geprüft</div>";
    }
    
    // Erstelle user_answers Tabelle
    $sql_answers = "CREATE TABLE IF NOT EXISTS user_answers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        question_id INTEGER,
        module TEXT,
        user_answer TEXT,
        is_correct INTEGER,
        time_taken INTEGER,
        answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id),
        FOREIGN KEY (question_id) REFERENCES questions (id)
    )";
    
    if ($db->exec($sql_answers)) {
        echo "<div class='success'>✅ Tabelle 'user_answers' erstellt/geprüft</div>";
    }
    
    // Erstelle achievements Tabelle
    $sql_achievements = "CREATE TABLE IF NOT EXISTS achievements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        achievement_type TEXT,
        achievement_name TEXT,
        earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id)
    )";
    
    if ($db->exec($sql_achievements)) {
        echo "<div class='success'>✅ Tabelle 'achievements' erstellt/geprüft</div>";
    }
    
    // Erstelle bot_activity Tabelle für Javis Bots
    $sql_bots = "CREATE TABLE IF NOT EXISTS bot_activity (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bot_name TEXT,
        user_id INTEGER,
        module TEXT,
        questions_answered INTEGER,
        correct_answers INTEGER,
        session_start DATETIME,
        session_end DATETIME,
        status TEXT DEFAULT 'active'
    )";
    
    if ($db->exec($sql_bots)) {
        echo "<div class='success'>✅ Tabelle 'bot_activity' erstellt/geprüft</div>";
    }
    
    // Zeige Tabellen-Struktur
    echo "<div class='info'><strong>📊 Datenbank-Struktur:</strong></div>";
    
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    echo "<pre>";
    while ($table = $tables->fetchArray()) {
        $tableName = $table['name'];
        echo "\n<strong>Tabelle: $tableName</strong>\n";
        
        // Zeige Spalten
        $columns = $db->query("PRAGMA table_info($tableName)");
        while ($column = $columns->fetchArray()) {
            echo "  - " . $column['name'] . " (" . $column['type'] . ")\n";
        }
    }
    echo "</pre>";
    
    // Statistiken
    $user_count = $db->querySingle("SELECT COUNT(*) FROM users");
    $question_count = $db->querySingle("SELECT COUNT(*) FROM questions");
    $session_count = $db->querySingle("SELECT COUNT(*) FROM user_sessions");
    
    echo "<div class='info'>
        <strong>📈 Aktuelle Statistiken:</strong><br>
        - Nutzer: $user_count<br>
        - Fragen: $question_count<br>
        - Sessions: $session_count
    </div>";
    
    // Füge Test-Daten ein falls Tabellen leer
    if ($user_count == 0) {
        $db->exec("INSERT INTO users (username, age, email) VALUES ('TestUser', 10, 'test@sgit.space')");
        echo "<div class='info'>Test-User erstellt</div>";
    }
    
    echo "<div class='success'><h3>✨ Alle Tabellen sind bereit!</h3></div>";
    
    $db->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Fehler: " . $e->getMessage() . "</div>";
}

echo "<button onclick='window.location.href=\"admin_dashboard.php\"'>📊 Zum Admin Dashboard</button>";
echo "<button onclick='window.location.href=\"index.php\"'>🏠 Zur Hauptseite</button>";

echo "</div></body></html>";
?>
