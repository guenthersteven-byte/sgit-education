<?php
/**
 * FIX: Korrigiert die Antworten im Modul unnuetzes_wissen
 * Ändert Buchstaben (A,B,C,D) zu den tatsächlichen Antworttexten
 */

echo "=== FIX: Unnützes Wissen Antworten korrigieren ===\n\n";

$dbPath = '/var/www/html/AI/data/questions.db';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Alle Fragen mit Buchstaben-Antworten holen
    $stmt = $db->query("SELECT id, question, answer, options FROM questions WHERE module = 'unnuetzes_wissen'");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fixed = 0;
    $skipped = 0;
    
    foreach ($questions as $q) {
        $answer = trim($q['answer']);
        $options = json_decode($q['options'], true);
        
        // Prüfen ob Antwort ein Buchstabe ist (A, B, C, D)
        if (in_array($answer, ['A', 'B', 'C', 'D']) && isset($options[$answer])) {
            $correctText = $options[$answer];
            
            // Update
            $update = $db->prepare("UPDATE questions SET answer = ? WHERE id = ?");
            $update->execute([$correctText, $q['id']]);
            
            echo "✓ ID {$q['id']}: '$answer' → '$correctText'\n";
            $fixed++;
        } else {
            $skipped++;
        }
    }
    
    echo "\n=== ERGEBNIS ===\n";
    echo "✓ Korrigiert: $fixed\n";
    echo "⊘ Übersprungen: $skipped\n";
    
} catch (PDOException $e) {
    die("DB-Fehler: " . $e->getMessage() . "\n");
}
