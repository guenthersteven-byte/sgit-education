<?php
/**
 * ============================================================================
 * sgiT Education Platform - Multiplayer Migration
 * ============================================================================
 * 
 * Erstellt die Tabellen für das Multiplayer-Quiz System:
 * - matches: Match-Grunddaten
 * - match_players: Teilnehmer pro Match
 * - match_answers: Antworten pro Frage
 * - Erweitert child_wallets um Statistik-Spalten
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 2025-12-12
 */

echo "=== Multiplayer Migration v1.0 ===\n\n";

$dbPath = __DIR__ . '/../wallet/wallet.db';

if (!file_exists($dbPath)) {
    die("❌ wallet.db nicht gefunden: $dbPath\n");
}

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Datenbank verbunden\n\n";
} catch (PDOException $e) {
    die("❌ DB-Fehler: " . $e->getMessage() . "\n");
}

// ================================================================
// Tabelle: matches
// ================================================================
echo "📦 Erstelle Tabelle: matches...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS matches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    match_code TEXT UNIQUE NOT NULL,
    match_type TEXT DEFAULT '1v1' CHECK(match_type IN ('1v1', '2v2', 'coop')),
    module TEXT NOT NULL,
    questions_total INTEGER DEFAULT 10,
    time_per_question INTEGER DEFAULT 15,
    current_question INTEGER DEFAULT 0,
    status TEXT DEFAULT 'waiting' CHECK(status IN ('waiting', 'ready', 'running', 'finished', 'cancelled')),
    
    -- Sats-System
    sats_bet INTEGER DEFAULT 0,
    sats_pool INTEGER DEFAULT 0,
    
    -- Ergebnis
    winner_id INTEGER REFERENCES child_wallets(id),
    winner_team INTEGER,
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME,
    finished_at DATETIME,
    
    -- Host
    created_by INTEGER REFERENCES child_wallets(id)
);
");
echo "   ✅ matches erstellt\n";

// ================================================================
// Tabelle: match_players
// ================================================================
echo "📦 Erstelle Tabelle: match_players...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS match_players (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    match_id INTEGER NOT NULL REFERENCES matches(id) ON DELETE CASCADE,
    player_id INTEGER NOT NULL REFERENCES child_wallets(id),
    team INTEGER DEFAULT 1,
    score INTEGER DEFAULT 0,
    correct_answers INTEGER DEFAULT 0,
    total_time_ms INTEGER DEFAULT 0,
    joker_used INTEGER DEFAULT 0,
    is_ready INTEGER DEFAULT 0,
    is_host INTEGER DEFAULT 0,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(match_id, player_id)
);
");
echo "   ✅ match_players erstellt\n";

// ================================================================
// Tabelle: match_answers
// ================================================================
echo "📦 Erstelle Tabelle: match_answers...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS match_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    match_id INTEGER NOT NULL REFERENCES matches(id) ON DELETE CASCADE,
    player_id INTEGER NOT NULL REFERENCES child_wallets(id),
    question_index INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    answer_given TEXT,
    correct_answer TEXT,
    is_correct INTEGER DEFAULT 0,
    time_taken_ms INTEGER DEFAULT 0,
    points_earned INTEGER DEFAULT 0,
    answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(match_id, player_id, question_index)
);
");
echo "   ✅ match_answers erstellt\n";

// ================================================================
// Tabelle: match_questions (Cache für Match-Fragen)
// ================================================================
echo "📦 Erstelle Tabelle: match_questions...\n";
$db->exec("
CREATE TABLE IF NOT EXISTS match_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    match_id INTEGER NOT NULL REFERENCES matches(id) ON DELETE CASCADE,
    question_index INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    correct_answer TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    
    UNIQUE(match_id, question_index)
);
");
echo "   ✅ match_questions erstellt\n";

// ================================================================
// Erweiterung: child_wallets (Statistiken + Elo)
// ================================================================
echo "\n📦 Erweitere child_wallets...\n";

$columnsToAdd = [
    'matches_played' => 'INTEGER DEFAULT 0',
    'matches_won' => 'INTEGER DEFAULT 0',
    'matches_lost' => 'INTEGER DEFAULT 0',
    'matches_draw' => 'INTEGER DEFAULT 0',
    'elo_rating' => 'INTEGER DEFAULT 1000',
    'elo_peak' => 'INTEGER DEFAULT 1000',
];

// Prüfen welche Spalten schon existieren
$existingCols = [];
$result = $db->query("PRAGMA table_info(child_wallets)");
foreach ($result as $row) {
    $existingCols[] = $row['name'];
}

foreach ($columnsToAdd as $col => $type) {
    if (!in_array($col, $existingCols)) {
        $db->exec("ALTER TABLE child_wallets ADD COLUMN $col $type");
        echo "   ✅ Spalte '$col' hinzugefügt\n";
    } else {
        echo "   ⏭️ Spalte '$col' existiert bereits\n";
    }
}

// ================================================================
// Indizes für Performance
// ================================================================
echo "\n📦 Erstelle Indizes...\n";

$indices = [
    'idx_matches_code' => 'CREATE INDEX IF NOT EXISTS idx_matches_code ON matches(match_code)',
    'idx_matches_status' => 'CREATE INDEX IF NOT EXISTS idx_matches_status ON matches(status)',
    'idx_match_players_match' => 'CREATE INDEX IF NOT EXISTS idx_match_players_match ON match_players(match_id)',
    'idx_match_players_player' => 'CREATE INDEX IF NOT EXISTS idx_match_players_player ON match_players(player_id)',
    'idx_match_answers_match' => 'CREATE INDEX IF NOT EXISTS idx_match_answers_match ON match_answers(match_id)',
];

foreach ($indices as $name => $sql) {
    $db->exec($sql);
    echo "   ✅ Index '$name'\n";
}

// ================================================================
// Zusammenfassung
// ================================================================
echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ MIGRATION ERFOLGREICH!\n";
echo str_repeat("=", 50) . "\n\n";

// Tabellen zählen
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'match%'")->fetchAll(PDO::FETCH_COLUMN);
echo "📊 Neue Tabellen: " . count($tables) . "\n";
foreach ($tables as $t) {
    echo "   - $t\n";
}

// Spalten in child_wallets prüfen
echo "\n📊 child_wallets Erweiterungen:\n";
$result = $db->query("PRAGMA table_info(child_wallets)");
$matchCols = [];
foreach ($result as $row) {
    if (str_starts_with($row['name'], 'matches_') || str_starts_with($row['name'], 'elo_')) {
        $matchCols[] = $row['name'];
    }
}
foreach ($matchCols as $col) {
    echo "   - $col\n";
}

echo "\n🎮 Multiplayer-System bereit!\n";
