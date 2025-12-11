<?php
/**
 * TODO-006: Fragen-Flagging System - Setup Script
 * Erstellt die flagged_questions Tabelle
 */

$db = new PDO('sqlite:/var/www/html/AI/data/questions.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     TODO-006: Flagging System - Database Setup              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Prüfe ob Tabelle bereits existiert
$exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='flagged_questions'")->fetch();

if ($exists) {
    echo "⚠️  Tabelle 'flagged_questions' existiert bereits!\n";
    $count = $db->query("SELECT COUNT(*) FROM flagged_questions")->fetchColumn();
    echo "   Enthält $count Einträge.\n";
} else {
    // Tabelle erstellen
    $db->exec("
        CREATE TABLE flagged_questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            question_id INTEGER NOT NULL,
            user_name VARCHAR(100),
            reason VARCHAR(50) DEFAULT 'unspecified',
            comment TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
        )
    ");
    
    // Index für schnelle Abfragen
    $db->exec("CREATE INDEX idx_flagged_question_id ON flagged_questions(question_id)");
    $db->exec("CREATE INDEX idx_flagged_created ON flagged_questions(created_at)");
    
    echo "✅ Tabelle 'flagged_questions' erstellt!\n";
    echo "   - id (PRIMARY KEY)\n";
    echo "   - question_id (FK -> questions)\n";
    echo "   - user_name\n";
    echo "   - reason (wrong_answer, unclear, duplicate, other)\n";
    echo "   - comment (optional)\n";
    echo "   - created_at\n";
}

echo "\n═══ Aktuelle Tabellen ═══\n";
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
foreach ($tables as $t) {
    echo "  ✓ " . $t['name'] . "\n";
}

echo "\n✅ Setup abgeschlossen!\n";
