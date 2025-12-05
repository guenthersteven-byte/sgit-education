<?php
/**
 * BUG-018 Analyse: Altersverteilung der Fragen
 */

$dbPath = __DIR__ . '/AI/data/questions.db';
$db = new SQLite3($dbPath);

echo "=== BUG-018 ANALYSE: Altersfilterung ===\n\n";

// 1. age_min/age_max Verteilung für Mathematik
echo "MATHEMATIK - Altersverteilung:\n";
echo str_repeat("-", 50) . "\n";

$result = $db->query("
    SELECT age_min, age_max, COUNT(*) as cnt 
    FROM questions 
    WHERE module = 'mathematik'
    GROUP BY age_min, age_max
    ORDER BY age_min, age_max
");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    printf("age_min: %2d | age_max: %2d | Anzahl: %d\n", 
        $row['age_min'], $row['age_max'], $row['cnt']);
}

// 2. Fragen für Alter 39
echo "\n\nFRAGEN FÜR ALTER 39 (age_min <= 39 AND age_max >= 39):\n";
echo str_repeat("-", 50) . "\n";

$result = $db->query("
    SELECT COUNT(*) as cnt FROM questions 
    WHERE module = 'mathematik'
    AND age_min <= 39 AND age_max >= 39
");
$row = $result->fetchArray(SQLITE3_ASSOC);
echo "Gefunden: {$row['cnt']} Fragen\n";

// 3. Max age_max Wert
echo "\n\nMAX age_max PRO MODUL:\n";
echo str_repeat("-", 50) . "\n";

$result = $db->query("
    SELECT module, MAX(age_max) as max_age, COUNT(*) as cnt
    FROM questions 
    GROUP BY module
    ORDER BY max_age DESC
");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    printf("%-15s | max_age: %2d | Fragen: %d\n", 
        $row['module'], $row['max_age'], $row['cnt']);
}

// 4. Beispiel-Fragen die ein 39-Jähriger bekommt (Fallback)
echo "\n\nFALLBACK-FRAGEN (sortiert nach age_min ASC):\n";
echo str_repeat("-", 50) . "\n";

$result = $db->query("
    SELECT id, age_min, age_max, SUBSTR(question, 1, 60) as q
    FROM questions 
    WHERE module = 'mathematik'
    ORDER BY age_min ASC, RANDOM()
    LIMIT 5
");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    printf("#%d [%d-%d] %s...\n", 
        $row['id'], $row['age_min'], $row['age_max'], $row['q']);
}

echo "\n\n=== DIAGNOSE ===\n";
echo "Problem: Fallback sortiert nach age_min ASC\n";
echo "         -> Erwachsene bekommen die einfachsten Fragen!\n";
echo "Lösung:  Für User > 21 nach age_min DESC sortieren\n";

$db->close();
