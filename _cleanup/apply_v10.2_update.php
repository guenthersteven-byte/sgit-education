<?php
/**
 * AUTO-UPDATE SCRIPT für v10.2
 * Ersetzt getGeneralPrompt Methode mit JSON-Support
 */

$file = __DIR__ . '/windows_ai_generator.php';

if (!file_exists($file)) {
    die("ERROR: windows_ai_generator.php nicht gefunden!\n");
}

// 1. Backup erstellen
$backup = $file . '.v10.1.backup';
copy($file, $backup);
echo "✅ Backup erstellt: $backup\n";

// 2. Datei einlesen
$content = file_get_contents($file);

// 3. Alte getGeneralPrompt Methode finden und ersetzen
$oldMethod = '    private function getGeneralPrompt($module, $age, $isGerman = true) {
        // VERBESSERTE MODUL-SPEZIFISCHE PROMPTS';

$newMethod = '    private function getGeneralPrompt($module, $age, $isGerman = true) {
        // LADE MODUL-DEFINITIONEN aus JSON
        $definitionsPath = __DIR__ . \'/AI/module_definitions.json\';
        
        if (file_exists($definitionsPath)) {
            $json = file_get_contents($definitionsPath);
            $definitions = json_decode($json, true);
            
            if (isset($definitions[$module])) {
                $def = $definitions[$module];
                
                // BAUE PRAEZISEN PROMPT aus Definition
                $prompt = "Erstelle eine {$def[\'name\']}-Frage auf DEUTSCH fuer Alter $age.\\n\\n";
                $prompt .= "WAS IST {$def[\'name\']}?\\n";
                $prompt .= "{$def[\'definition\']}\\n\\n";
                
                $prompt .= "ERLAUBTE THEMEN:\\n";
                foreach ($def[\'topics\'] as $topic) {
                    $prompt .= "- $topic\\n";
                }
                $prompt .= "\\n";
                
                $prompt .= "VERBOTENE THEMEN:\\n";
                foreach ($def[\'NOT_topics\'] as $not) {
                    $prompt .= "- $not\\n";
                }
                $prompt .= "\\n";
                
                $prompt .= "BEISPIEL-FRAGEN:\\n";
                foreach (array_slice($def[\'examples\'], 0, 2) as $ex) {
                    $prompt .= "- $ex\\n";
                }
                $prompt .= "\\n";
                
                $prompt .= "Format EXAKT:\\n";
                $prompt .= "Q: [Frage ueber {$def[\'name\']} auf Deutsch]\\n";
                $prompt .= "A: [Richtige Antwort]\\n";
                $prompt .= "W1: [Falsche aber plausible Antwort]\\n";
                $prompt .= "W2: [Falsche aber plausible Antwort]\\n";
                $prompt .= "W3: [Falsche aber plausible Antwort]\\n\\n";
                
                $prompt .= "KRITISCH WICHTIG:\\n";
                $prompt .= "- Frage MUSS ueber {$def[\'name\']} sein!\\n";
                $prompt .= "- KEINE Platzhalter wie [answer] oder {placeholder}!\\n";
                $prompt .= "- Altersgerecht fuer $age Jahre!\\n";
                $prompt .= "- Antworten muessen UNTERSCHIEDLICH sein!\\n";
                
                return $prompt;
            }
        }
        
        // FALLBACK wenn JSON nicht existiert oder Modul nicht gefunden
        $this->log("WARNING: Module definition not found for: $module", \'warning\');
        
        // Alte Physik/Biologie/Bitcoin Prompts als Fallback';

if (strpos($content, $oldMethod) === false) {
    die("ERROR: Alte Methode nicht gefunden! Vielleicht schon gepatcht?\n");
}

// Finde Ende der alten Methode (nächste "private function")
$startPos = strpos($content, $oldMethod);
$endPos = strpos($content, '    private function getAgeLevel', $startPos);

if ($endPos === false) {
    die("ERROR: Ende der Methode nicht gefunden!\n");
}

// Alte Methode extrahieren
$oldComplete = substr($content, $startPos, $endPos - $startPos);

// Neue komplette Methode (mit Fallbacks für Physik, etc.)
$newComplete = $newMethod . '
        if ($module == \'physik\') {
            return "Erstelle eine PHYSIK-Frage auf DEUTSCH fuer Alter $age.

WICHTIG: Die Frage MUSS ueber Physik sein!
Themen: Mechanik, Energie, Kraefte, Licht, Schall, Waerme, Elektrizitaet

Format EXAKT:
Q: [Frage auf Deutsch ueber Physik]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

NIEMALS Fragen ueber Erdkunde (Hauptstaedte, Laender)!
NIEMALS Platzhalter verwenden!";
        }
        
        if ($module == \'biologie\') {
            return "Erstelle eine BIOLOGIE-Frage auf DEUTSCH fuer Alter $age.

WICHTIG: Biologie = Lebewesen, Pflanzen, Tiere, Koerper, Zellen

Format EXAKT:
Q: [Frage auf Deutsch ueber Biologie]
A: [Richtige Antwort]
W1: [Falsche Antwort]
W2: [Falsche Antwort]
W3: [Falsche Antwort]

NIEMALS Fragen ueber Erdkunde!
NIEMALS Platzhalter verwenden!";
        }
        
        if ($module == \'bitcoin\') {
            return "Erstelle eine BITCOIN-Frage auf DEUTSCH fuer Alter $age.

Bitcoin-Grundlagen:
- Bitcoin ist digitales Geld
- Funktioniert ohne Banken (dezentral)
- Nur 21 Millionen Bitcoin existieren

Format EXAKT:
Q: [Frage auf Deutsch ueber Bitcoin]
A: [Richtige Antwort]
W1: [Falsche aber plausible Antwort]
W2: [Falsche aber plausible Antwort]
W3: [Falsche aber plausible Antwort]

Keine Platzhalter wie [answer]!
Altersgerecht formulieren!";
        }
        
        // Generischer Fallback
        $subject = $module;
        $lang = $isGerman ? "German" : "English";
        
        return "Create a $subject question in $lang for age $age.\\nDifficulty: $age years old student level.\\n\\nIMPORTANT: No placeholders! Real answers only!\\n\\nFormat:\\nQ: [question]\\nA: [correct answer]\\nW1: [wrong answer]\\nW2: [wrong answer]\\nW3: [wrong answer]";
    }

';

// Ersetzen
$newContent = str_replace($oldComplete, $newComplete, $content);

// 4. Version auf v10.2 ändern
$newContent = str_replace(
    'AI Generator v10.1 FIXED + DUPLICATE PROTECTION',
    'AI Generator v10.2 MODULE DEFINITIONS',
    $newContent
);

$newContent = str_replace(
    'v10.1 🔒 NO DUPLICATES',
    'v10.2 📚 MODULE DEFINITIONS',
    $newContent
);

$newContent = str_replace(
    'v10.1 ✅ FIXED + 🔒 NO DUPLICATES',
    'v10.2 ✅ FIXED + 📚 MODULE DEFINITIONS',
    $newContent
);

// 5. Speichern
file_put_contents($file, $newContent);

echo "✅ Datei aktualisiert!\n";
echo "✅ Version: v10.2 MODULE DEFINITIONS\n";
echo "✅ getGeneralPrompt mit JSON-Support ersetzt\n\n";

echo "NÄCHSTE SCHRITTE:\n";
echo "1. Cache leeren: Strg+Shift+R\n";
echo "2. Testen: http://localhost/Education/windows_ai_generator.php\n";
echo "3. Physik testen (5 Fragen)\n\n";

echo "Backup liegt in: $backup\n";
?>
