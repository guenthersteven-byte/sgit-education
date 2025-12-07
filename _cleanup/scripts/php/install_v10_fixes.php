<?php
/**
 * sgiT Education - INSTALLER für v10.0 Fixes
 * Führe diese Datei aus um die Verbesserungen zu installieren
 */

echo "<!DOCTYPE html>
<html><head>
<meta charset='UTF-8'>
<title>sgiT Generator v10.0 Installer</title>
<style>
body { font-family: Arial; padding: 40px; background: linear-gradient(135deg, #667eea, #764ba2); }
.container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 20px; }
h1 { color: #1A3503; }
.step { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 10px; }
.success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
.code { background: #2d2d2d; color: #f8f8f2; padding: 20px; border-radius: 5px; overflow-x: auto; }
button { background: #43D240; color: white; border: none; padding: 15px 30px; border-radius: 8px; cursor: pointer; font-size: 16px; }
button:hover { background: #3ab837; }
</style>
</head><body>
<div class='container'>
<h1>🚀 sgiT Generator v10.0 Installer</h1>
";

$step = isset($_GET['step']) ? $_GET['step'] : 1;

if ($step == 1) {
    echo "<div class='step'>
    <h2>Schritt 1: Backup erstellen</h2>
    <p>Sichere die aktuelle Version bevor wir fortfahren.</p>";
    
    if (file_exists('windows_ai_generator.php')) {
        $backupFile = 'windows_ai_generator_v9_backup_' . date('Y-m-d_His') . '.php';
        if (copy('windows_ai_generator.php', $backupFile)) {
            echo "<div class='success'>✅ Backup erstellt: $backupFile</div>";
            echo "<button onclick='location.href=\"?step=2\"'>Weiter zu Schritt 2</button>";
        } else {
            echo "<div class='error'>❌ Backup fehlgeschlagen!</div>";
        }
    } else {
        echo "<div class='error'>❌ windows_ai_generator.php nicht gefunden!</div>";
    }
    
    echo "</div>";
}

elseif ($step == 2) {
    echo "<div class='step'>
    <h2>Schritt 2: Aktuelle Probleme prüfen</h2>";
    
    require_once 'windows_ai_generator.php';
    $gen = new AIQuestionGeneratorComplete();
    
    echo "<p>Teste 3 Fragen mit aktuellem Generator...</p>";
    
    $testModules = ['physik', 'biologie', 'bitcoin'];
    $problems = [];
    
    foreach ($testModules as $mod) {
        $q = @$gen->generateQuestion($mod, 5, 10, false);
        if ($q) {
            // Prüfe Platzhalter
            if (preg_match('/\[.*?\]/', $q['q']) || preg_match('/\[.*?\]/', implode('', $q['options']))) {
                $problems[] = "$mod: Platzhalter gefunden";
            }
            // Prüfe Kategorisierung
            if (stripos($q['q'], 'hauptstadt') !== false) {
                $problems[] = "$mod: Falsche Kategorisierung (Erdkunde)";
            }
        }
    }
    
    if (empty($problems)) {
        echo "<div class='success'>✅ Keine Probleme gefunden! (Evtl. nur Glück - v10 ist trotzdem besser)</div>";
    } else {
        echo "<div class='error'>";
        echo "❌ Gefundene Probleme:<br>";
        foreach ($problems as $p) echo "• $p<br>";
        echo "</div>";
    }
    
    $stats = $gen->getStatistics();
    echo "<p>Aktuelle DB: " . ($stats['total_questions'] ?? 0) . " Fragen</p>";
    
    echo "<button onclick='location.href=\"?step=3\"'>Installiere v10.0 Fixes</button>";
    echo "</div>";
}

elseif ($step == 3) {
    echo "<div class='step'>
    <h2>Schritt 3: Installation der v10.0 Fixes</h2>
    <p><strong>Hinweis:</strong> Die v10.0-Datei muss manuell erstellt werden.</p>
    
    <div class='code'>";
    
    echo "Die v10.0-Version mit allen Fixes wurde bereits vorbereitet:<br><br>";
    
    $files = [
        'windows_ai_generator_v10.php',
        'test_generator_prompts.php',
        'batch_generate_all_modules.php',
        'AI_GENERATOR_ANALYSE_UND_FIX.md',
        'IMPLEMENTIERUNGS_ANLEITUNG.md',
        'FIX_ZUSAMMENFASSUNG_KOMPLETT.md'
    ];
    
    echo "<strong>Diese Dateien sollten vorhanden sein:</strong><br>";
    foreach ($files as $file) {
        $exists = file_exists($file);
        echo ($exists ? '✅' : '❌') . " $file<br>";
    }
    
    echo "</div>";
    
    echo "<div class='error'>
    <strong>⚠️ WICHTIG:</strong> Die Dateien wurden von Claude vorbereitet,<br>
    müssen aber noch von dir ins Education-Verzeichnis kopiert werden.<br><br>
    
    <strong>Nächster Schritt:</strong><br>
    1. Öffne die Dateien die Claude erstellt hat<br>
    2. Speichere sie in C:\\xampp\\htdocs\\Education\\<br>
    3. Dann hier weiter klicken<br>
    </div>";
    
    echo "<button onclick='location.href=\"?step=4\"'>Ich habe die Dateien kopiert</button>";
    echo "</div>";
}

elseif ($step == 4) {
    echo "<div class='step'>
    <h2>Schritt 4: v10.0 aktivieren</h2>";
    
    if (file_exists('windows_ai_generator_v10.php')) {
        // Ersetze alte Version durch neue
        if (copy('windows_ai_generator_v10.php', 'windows_ai_generator.php')) {
            echo "<div class='success'>✅ v10.0 aktiviert!</div>";
            echo "<button onclick='location.href=\"?step=5\"'>Teste die neue Version</button>";
        } else {
            echo "<div class='error'>❌ Aktivierung fehlgeschlagen!</div>";
        }
    } else {
        echo "<div class='error'>❌ windows_ai_generator_v10.php nicht gefunden!<br>
        Bitte kopiere erst die Datei ins Verzeichnis.</div>";
    }
    
    echo "</div>";
}

elseif ($step == 5) {
    echo "<div class='step'>
    <h2>Schritt 5: Test der v10.0</h2>";
    
    require_once 'windows_ai_generator.php';
    $gen = new AIQuestionGeneratorComplete();
    
    echo "<p>Teste mit verbesserter Version...</p>";
    
    $testModules = ['physik' => '⚛️', 'biologie' => '🧬', 'bitcoin' => '₿'];
    $success = 0;
    
    foreach ($testModules as $mod => $icon) {
        echo "<p>$icon $mod... ";
        $q = @$gen->generateQuestion($mod, 5, 10, true); // Force new
        
        if ($q) {
            $hasProblems = (
                preg_match('/\[.*?\]/', $q['q']) ||
                preg_match('/\[.*?\]/', implode('', $q['options'])) ||
                stripos($q['q'], 'hauptstadt') !== false
            );
            
            if ($hasProblems) {
                echo "❌ Noch Probleme</p>";
            } else {
                echo "✅ OK<br><small>" . htmlspecialchars(substr($q['q'], 0, 60)) . "...</small></p>";
                $success++;
            }
        } else {
            echo "❌ Keine Frage</p>";
        }
    }
    
    echo "<h3>Ergebnis: $success/3 erfolgreich</h3>";
    
    if ($success >= 2) {
        echo "<div class='success'>
        🎉 Installation erfolgreich!<br><br>
        <strong>Nächster Schritt:</strong> Generiere mehr Fragen!<br>
        <button onclick='location.href=\"batch_generate_all_modules.php\"'>Starte Massen-Generierung</button>
        </div>";
    } else {
        echo "<div class='error'>
        ⚠️ Noch Probleme vorhanden.<br>
        Prüfe die Logs: AI/logs/generator.log
        </div>";
    }
    
    echo "</div>";
}

echo "</div></body></html>";
?>
