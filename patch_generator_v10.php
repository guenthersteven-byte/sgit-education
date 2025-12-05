<?php
/**
 * sgiT Education - SMART PATCH v10.0
 * Improved Prompts direkt in die existierende Datei patchen
 * 
 * FÜHRE DIESE DATEI EINMAL AUS: php patch_generator_v10.php
 */

echo "=================================================================\n";
echo "sgiT Generator SMART PATCH v10.0\n";
echo "=================================================================\n\n";

$targetFile = 'windows_ai_generator.php';

if (!file_exists($targetFile)) {
    die("❌ Fehler: $targetFile nicht gefunden!\n");
}

echo "✅ Zieldatei gefunden: $targetFile\n";

// Backup
$backupFile = 'windows_ai_generator_v9_backup_' . date('Ymd_His') . '.php';
if (copy($targetFile, $backupFile)) {
    echo "✅ Backup erstellt: $backupFile\n\n";
} else {
    die("❌ Backup fehlgeschlagen!\n");
}

// Lese aktuelle Datei
$content = file_get_contents($targetFile);

echo "Patche Datei...\n\n";

// PATCH 1: Füge Force-Parameter hinzu
if (strpos($content, 'public function generateQuestion($module = \'mathematik\', $difficulty = 5, $age = 10)') !== false) {
    $content = str_replace(
        'public function generateQuestion($module = \'mathematik\', $difficulty = 5, $age = 10)',
        'public function generateQuestion($module = \'mathematik\', $difficulty = 5, $age = 10, $forceGenerate = false)',
        $content
    );
    echo "✅ PATCH 1: Force-Parameter hinzugefügt\n";
}

// PATCH 2: Ändere DB-Check um Force zu respektieren
$oldDbCheck = '// 1. Versuche aus DB zu holen (schnellster Weg)
        $dbQuestion = $this->getQuestionFromDB($module, $age);
        if ($dbQuestion) {';

$newDbCheck = '// 1. Wenn NICHT forced → Versuche aus DB
        if (!$forceGenerate) {
            $dbQuestion = $this->getQuestionFromDB($module, $age);
            if ($dbQuestion) {';

if (strpos($content, $oldDbCheck) !== false) {
    $content = str_replace($oldDbCheck, $newDbCheck, $content);
    // Füge schließende Klammer hinzu
    $content = str_replace(
        'return $dbQuestion;
        }',
        'return $dbQuestion;
            }
        }',
        $content,
        $count
    );
    if ($count > 0) {
        echo "✅ PATCH 2: Force-Generate Logik eingefügt\n";
    }
}

// PATCH 3: Verbessere validateQuestion
$oldValidate = 'if (preg_match(\'/^(Option|Wrong|Falsch|W)\d*$/i\', $option)) return false;';
$newValidate = '// Erweiterte Platzhalter-Erkennung
            $placeholders = [\'/\[.*?\]/\', \'/\{.*?\}/\', \'/^(Option|Wrong|Falsch|W)\d*$/i\', \'/placeholder/i\', \'/todo/i\'];
            foreach ($placeholders as $pattern) {
                if (preg_match($pattern, $option)) return false;
            }';

if (strpos($content, $oldValidate) !== false) {
    $content = str_replace($oldValidate, $newValidate, $content);
    echo "✅ PATCH 3: Erweiterte Validierung eingefügt\n";
}

// PATCH 4: Verbessere Physik-Prompt
$oldPhysikPrompt = 'return "Create a $subject question in $lang for age $age.
Difficulty: $age years old student level.

Format:
Q: [question]
A: [correct answer]
W1: [wrong answer]
W2: [wrong answer]
W3: [wrong answer]";';

$newPhysikPrompt = 'if ($module == \'physik\') {
            return "Erstelle eine PHYSIK-Frage auf DEUTSCH für Alter $age.

WICHTIG: Die Frage MUSS über Physik sein!
Themen: Mechanik, Energie, Kräfte, Licht, Schall, Wärme

Format EXAKT:
Q: [Frage auf Deutsch über Physik]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

NIEMALS Fragen über Erdkunde (Hauptstädte, Länder)!";
        }
        
        return "Create a $subject question in $lang for age $age.
Difficulty: $age years old student level.

Format:
Q: [question]
A: [correct answer]
W1: [wrong answer]
W2: [wrong answer]
W3: [wrong answer]";';

if (strpos($content, $oldPhysikPrompt) !== false) {
    $content = str_replace($oldPhysikPrompt, $newPhysikPrompt, $content);
    echo "✅ PATCH 4: Verbesserten Physik-Prompt eingefügt\n";
}

// PATCH 5: Füge Batch mit Force hinzu
$oldBatch = 'public function generateBatch($module, $count, $minAge = 5, $maxAge = 15) {';
$newBatch = 'public function generateBatch($module, $count, $minAge = 5, $maxAge = 15, $forceGenerate = true) {';

if (strpos($content, $oldBatch) !== false) {
    $content = str_replace($oldBatch, $newBatch, $content);
    
    // Ändere auch den Aufruf
    $content = str_replace(
        '$question = $this->generateQuestion($module, $difficulty, $age);',
        '$question = $this->generateQuestion($module, $difficulty, $age, $forceGenerate);',
        $content
    );
    
    echo "✅ PATCH 5: Batch mit Force-Parameter aktualisiert\n";
}

// Schreibe gepatchte Datei
if (file_put_contents($targetFile, $content)) {
    echo "\n✅ PATCH ERFOLGREICH ANGEWENDET!\n\n";
    
    echo "=================================================================\n";
    echo "NEXT STEPS\n";
    echo "=================================================================\n\n";
    
    echo "1. Teste die gepatchte Version:\n";
    echo "   php test_generator_quick.php\n\n";
    
    echo "2. Wenn OK: Generiere mehr Fragen:\n";
    echo "   php windows_ai_generator.php\n\n";
    
    echo "3. Backup liegt hier: $backupFile\n";
    echo "   (Falls etwas schiefgeht, einfach zurückkopieren)\n\n";
    
} else {
    echo "\n❌ FEHLER beim Schreiben der Datei!\n";
    echo "Backup liegt in: $backupFile\n";
}

echo "=================================================================\n";
?>
