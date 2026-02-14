<?php
/**
 * Export all questions to JSON for review
 */

require_once __DIR__ . '/../db_config.php';

$db = DatabaseConfig::getMainDB();

if (!$db) {
    die("Database connection failed\n");
}

// Get all questions
$result = $db->query("SELECT * FROM questions WHERE is_active = 1 ORDER BY module, id");

$questions = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $questions[] = $row;
}

echo json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$db->close();
