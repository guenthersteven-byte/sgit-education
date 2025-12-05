<?php
/**
 * sgiT Education - XSS Security Fix Script
 * 
 * Behebt XSS-Schwachstellen in allen Modulen
 * 
 * @date 2024-12-01
 * @author Claude / sgiT
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='font-family: monospace; background: #1a1a2e; color: #00ff00; padding: 20px; margin: 20px;'>\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  🔒 sgiT Education - XSS SECURITY FIX                       ║\n";
echo "║  Datum: " . date('Y-m-d H:i:s') . "                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Alle Module
$modules = [
    'mathematik',
    'physik', 
    'chemie',
    'biologie',
    'erdkunde',
    'geschichte',
    'kunst',
    'musik',
    'computer',
    'bitcoin',
    'steuern',
    'englisch',
    'lesen',
    'wissenschaft'
];

$fixed = 0;
$errors = 0;
$skipped = 0;

foreach ($modules as $module) {
    $file = __DIR__ . "/$module/index.php";
    
    echo "📚 [$module] ";
    
    if (!file_exists($file)) {
        echo "❌ Datei nicht gefunden!\n";
        $errors++;
        continue;
    }
    
    $content = file_get_contents($file);
    $original = $content;
    $changes = [];
    
    // Fix 1: Unsicheren $_GET["feedback"] in class-Attribut
    // Von: <div class="feedback <?= $_GET["feedback"] ?>">
    // Zu:  <div class="feedback <?= isset($_GET["feedback"]) && in_array($_GET["feedback"], ["correct","wrong"]) ? $_GET["feedback"] : "" ?>">
    
    $pattern1 = '/<div class="feedback <\?=\s*\$_GET\["feedback"\]\s*\?>">/';
    $replacement1 = '<div class="feedback <?= isset($_GET["feedback"]) && in_array($_GET["feedback"], ["correct","wrong"]) ? htmlspecialchars($_GET["feedback"]) : "" ?>">';
    
    if (preg_match($pattern1, $content)) {
        $content = preg_replace($pattern1, $replacement1, $content);
        $changes[] = "class-Attribut gefixed";
    }
    
    // Fix 2: Alternative Pattern (mit einfachen Quotes)
    $pattern2 = '/<div class="feedback <\?=\s*\$_GET\[\'feedback\'\]\s*\?>">/';
    if (preg_match($pattern2, $content)) {
        $content = preg_replace($pattern2, $replacement1, $content);
        $changes[] = "class-Attribut gefixed (alt)";
    }
    
    // Fix 3: Unsicheren Vergleich in if-Statement (optional - macht Code robuster)
    // Prüfen ob $_GET["feedback"] == "correct" ohne isset()
    $pattern3 = '/\$_GET\["feedback"\]\s*==\s*"correct"/';
    $replacement3 = '(isset($_GET["feedback"]) && $_GET["feedback"] === "correct")';
    
    if (preg_match($pattern3, $content) && !preg_match('/isset\(\$_GET\["feedback"\]\)/', $content)) {
        $content = preg_replace($pattern3, $replacement3, $content);
        $changes[] = "Vergleichs-Pattern gefixed";
    }
    
    // Fix 4: Security-Include am Anfang hinzufügen (nach session_start)
    if (strpos($content, 'includes/security.php') === false) {
        $content = preg_replace(
            '/(session_start\(\);)/',
            "$1\n\n// Security functions\nrequire_once __DIR__ . '/../includes/security.php';",
            $content
        );
        $changes[] = "security.php eingebunden";
    }
    
    // Speichern wenn Änderungen
    if ($content !== $original) {
        // Backup erstellen
        $backup = $file . '.backup_' . date('Ymd_His');
        copy($file, $backup);
        
        // Neue Datei schreiben
        file_put_contents($file, $content);
        
        echo "✅ GEFIXED (" . implode(", ", $changes) . ")\n";
        echo "   📦 Backup: " . basename($backup) . "\n";
        $fixed++;
    } else {
        // Prüfen ob bereits sicher
        if (strpos($original, 'in_array($_GET["feedback"]') !== false || 
            strpos($original, 'validate_feedback') !== false) {
            echo "✓ Bereits sicher\n";
            $skipped++;
        } else {
            echo "⚠️ Keine Änderungen nötig oder Pattern nicht gefunden\n";
            $skipped++;
        }
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 ZUSAMMENFASSUNG\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   ✅ Gefixed:    $fixed\n";
echo "   ⏭️ Übersprungen: $skipped\n";
echo "   ❌ Fehler:     $errors\n";
echo "═══════════════════════════════════════════════════════════════\n";

if ($fixed > 0) {
    echo "\n🔒 XSS-Schwachstellen wurden behoben!\n";
    echo "💡 Führe den Security Bot erneut aus, um die Fixes zu verifizieren.\n";
}

echo "</pre>";

// Jetzt zusätzlich die Module einzeln prüfen und manuell fixen falls nötig
echo "<pre style='font-family: monospace; background: #2d3436; color: #74b9ff; padding: 20px; margin: 20px;'>\n";
echo "🔍 DETAILLIERTE PRÜFUNG...\n\n";

foreach ($modules as $module) {
    $file = __DIR__ . "/$module/index.php";
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    
    // Suche nach unsicheren Patterns
    $vulnerable = false;
    
    // Pattern 1: Direkter $_GET in HTML ohne Validierung
    if (preg_match('/<[^>]*<\?=\s*\$_GET\[/', $content)) {
        if (strpos($content, 'htmlspecialchars') === false || 
            strpos($content, 'in_array') === false) {
            echo "[$module] ⚠️ Möglicherweise unsicher: \$_GET in HTML\n";
            $vulnerable = true;
        }
    }
    
    // Pattern 2: $_GET["feedback"] im class Attribut
    if (preg_match('/class="[^"]*<\?=.*\$_GET\["feedback"\]/', $content)) {
        if (strpos($content, 'in_array($_GET["feedback"]') === false) {
            echo "[$module] ⚠️ Unsicher: feedback in class ohne Validierung\n";
            $vulnerable = true;
        }
    }
    
    if (!$vulnerable) {
        echo "[$module] ✅ OK\n";
    }
}

echo "\n</pre>";
?>
