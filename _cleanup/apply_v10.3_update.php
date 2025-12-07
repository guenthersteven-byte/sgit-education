<?php
/**
 * AUTO-UPDATE SCRIPT für v10.3 - ENGLISCHE PROMPTS
 * Ersetzt getGeneralPrompt() für bessere Tinyllama-Kompatibilität
 */

$file = __DIR__ . '/windows_ai_generator.php';

if (!file_exists($file)) {
    die("ERROR: windows_ai_generator.php nicht gefunden!\n");
}

// 1. Backup erstellen
$backup = $file . '.v10.2.backup';
if (file_exists($file . '.v10.1.backup')) {
    $backup = $file . '.v10.2b.backup';  // Falls v10.1 Backup schon existiert
}
copy($file, $backup);
echo "✅ Backup erstellt: $backup\n";

// 2. Datei einlesen
$content = file_get_contents($file);

// 3. Alte getGeneralPrompt Methode ersetzen
// Finde Start
$startMarker = '    private function getGeneralPrompt($module, $age, $isGerman = true) {';
$startPos = strpos($content, $startMarker);

if ($startPos === false) {
    die("ERROR: getGeneralPrompt nicht gefunden!\n");
}

// Finde Ende (nächste private function)
$endMarker = '    private function getAgeLevel';
$endPos = strpos($content, $endMarker, $startPos);

if ($endPos === false) {
    die("ERROR: Ende der Methode nicht gefunden!\n");
}

// Alte Methode extrahieren
$oldMethod = substr($content, $startPos, $endPos - $startPos);

// NEUE Methode - ENGLISCHE PROMPTS!
$newMethod = '    private function getGeneralPrompt($module, $age, $isGerman = true) {
        // LADE ENGLISCHE MODUL-DEFINITIONEN
        $definitionsPath = __DIR__ . \'/AI/module_definitions_english.json\';
        
        if (file_exists($definitionsPath)) {
            $json = file_get_contents($definitionsPath);
            $definitions = json_decode($json, true);
            
            if (isset($definitions[$module])) {
                $def = $definitions[$module];
                
                // Bestimme Output-Sprache
                $outputLang = $def[\'output_language\'] ?? \'GERMAN\';
                
                // BAUE ENGLISCHEN PROMPT mit deutscher/englischer Ausgabe
                $prompt = "Create a {$def[\'name\']} question for age $age.\n\n";
                $prompt .= "WHAT IS {$def[\'name\']}?\n";
                $prompt .= "{$def[\'definition\']}\n\n";
                
                $prompt .= "ALLOWED TOPICS:\n";
                foreach ($def[\'topics\'] as $topic) {
                    $prompt .= "- $topic\n";
                }
                $prompt .= "\n";
                
                $prompt .= "FORBIDDEN TOPICS:\n";
                foreach ($def[\'NOT_topics\'] as $not) {
                    $prompt .= "- $not\n";
                }
                $prompt .= "\n";
                
                // Deutsche oder englische Beispiele
                if ($outputLang == \'GERMAN\' && isset($def[\'examples_german\'])) {
                    $prompt .= "EXAMPLE QUESTIONS (in German):\n";
                    foreach (array_slice($def[\'examples_german\'], 0, 2) as $ex) {
                        $prompt .= "- $ex\n";
                    }
                } elseif ($outputLang == \'ENGLISH\' && isset($def[\'examples_english\'])) {
                    $prompt .= "EXAMPLE QUESTIONS (in English):\n";
                    foreach (array_slice($def[\'examples_english\'], 0, 2) as $ex) {
                        $prompt .= "- $ex\n";
                    }
                }
                $prompt .= "\n";
                
                $prompt .= "Format EXACTLY:\n";
                if ($outputLang == \'GERMAN\') {
                    $prompt .= "Q: [Question about {$def[\'name\']} IN GERMAN LANGUAGE]\n";
                    $prompt .= "A: [Correct answer IN GERMAN LANGUAGE]\n";
                    $prompt .= "W1: [Wrong but plausible answer IN GERMAN LANGUAGE]\n";
                    $prompt .= "W2: [Wrong but plausible answer IN GERMAN LANGUAGE]\n";
                    $prompt .= "W3: [Wrong but plausible answer IN GERMAN LANGUAGE]\n\n";
                } else {
                    $prompt .= "Q: [Question about {$def[\'name\']} IN ENGLISH]\n";
                    $prompt .= "A: [Correct answer IN ENGLISH]\n";
                    $prompt .= "W1: [Wrong but plausible answer IN ENGLISH]\n";
                    $prompt .= "W2: [Wrong but plausible answer IN ENGLISH]\n";
                    $prompt .= "W3: [Wrong but plausible answer IN ENGLISH]\n\n";
                }
                
                $prompt .= "CRITICAL REQUIREMENTS:\n";
                $prompt .= "- Question MUST be about {$def[\'name\']}!\n";
                $prompt .= "- ALL text MUST be in {$outputLang} language!\n";
                $prompt .= "- NO placeholders like [answer] or {placeholder}!\n";
                $prompt .= "- Age-appropriate for $age years!\n";
                $prompt .= "- Answers must be DIFFERENT from each other!\n";
                
                return $prompt;
            }
        }
        
        // FALLBACK wenn JSON nicht existiert
        $this->log("WARNING: English module definitions not found for: $module", \'warning\');
        
        // Alte Fallbacks (Physik, Biologie, Bitcoin)
        if ($module == \'physik\') {
            return "Create a PHYSICS question IN GERMAN for age $age.

IMPORTANT: Question MUST be about physics!
Topics: Mechanics, Energy, Forces, Light, Sound, Heat, Electricity

Format EXACTLY (ALL IN GERMAN):
Q: [Question about physics IN GERMAN]
A: [Correct answer IN GERMAN]
W1: [Wrong answer IN GERMAN]
W2: [Wrong answer IN GERMAN]
W3: [Wrong answer IN GERMAN]

NEVER questions about geography (capitals, countries)!
NEVER use placeholders!";
        }
        
        if ($module == \'biologie\') {
            return "Create a BIOLOGY question IN GERMAN for age $age.

IMPORTANT: Biology = living things, plants, animals, body, cells

Format EXACTLY (ALL IN GERMAN):
Q: [Question about biology IN GERMAN]
A: [Correct answer IN GERMAN]
W1: [Wrong answer IN GERMAN]
W2: [Wrong answer IN GERMAN]
W3: [Wrong answer IN GERMAN]

NEVER geography questions!
NEVER placeholders!";
        }
        
        if ($module == \'bitcoin\') {
            return "Create a BITCOIN question IN GERMAN for age $age.

Bitcoin basics:
- Bitcoin is digital money
- Works without banks (decentralized)
- Only 21 million Bitcoin exist

Format EXACTLY (ALL IN GERMAN):
Q: [Question about Bitcoin IN GERMAN]
A: [Correct answer IN GERMAN]
W1: [Wrong but plausible answer IN GERMAN]
W2: [Wrong but plausible answer IN GERMAN]
W3: [Wrong but plausible answer IN GERMAN]

No placeholders like [answer]!
Age-appropriate!";
        }
        
        // Generischer Fallback
        $subject = $module;
        $lang = $isGerman ? "GERMAN" : "ENGLISH";
        
        return "Create a $subject question IN $lang for age $age.\\nDifficulty: $age years old student level.\\n\\nIMPORTANT: No placeholders! Real answers only! ALL text in $lang!\\n\\nFormat:\\nQ: [question in $lang]\\nA: [correct answer in $lang]\\nW1: [wrong answer in $lang]\\nW2: [wrong answer in $lang]\\nW3: [wrong answer in $lang]";
    }

';

// Ersetzen
$newContent = str_replace($oldMethod, $newMethod, $content);

// 4. Version auf v10.3 ändern
$newContent = str_replace(
    'AI Generator v10.2 MODULE DEFINITIONS',
    'AI Generator v10.3 ENGLISH PROMPTS',
    $newContent
);

$newContent = str_replace(
    'v10.2 📚 MODULE DEFINITIONS',
    'v10.3 🇬🇧→🇩🇪 ENGLISH PROMPTS',
    $newContent
);

$newContent = str_replace(
    'v10.2 ✅ FIXED + 📚 MODULE DEFINITIONS',
    'v10.3 ✅ FIXED + 🇬🇧→🇩🇪 ENGLISH PROMPTS',
    $newContent
);

// 5. Speichern
file_put_contents($file, $newContent);

echo "✅ Datei aktualisiert!\n";
echo "✅ Version: v10.3 ENGLISH PROMPTS\n";
echo "✅ getGeneralPrompt mit englischen Prompts ersetzt\n";
echo "✅ Output: Deutsche Antworten (IN GERMAN)\n\n";

echo "WICHTIG:\n";
echo "Die neue JSON-Datei module_definitions_english.json wird automatisch geladen!\n\n";

echo "NÄCHSTE SCHRITTE:\n";
echo "1. Cache leeren: Strg+Shift+R\n";
echo "2. DB leeren (falls alte Fragen drin): reset_db_completely.php\n";
echo "3. Testen: http://localhost/Education/windows_ai_generator.php\n";
echo "4. Physik testen (5 Fragen)\n";
echo "5. Prüfen: Alles auf DEUTSCH? Kategorie korrekt?\n\n";

echo "Backup liegt in: $backup\n";
?>
