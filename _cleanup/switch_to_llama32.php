<?php
/**
 * WECHSEL ZU LLAMA3.2
 * Ändert windows_ai_generator.php auf llama3.2
 */

$file = __DIR__ . '/windows_ai_generator.php';

if (!file_exists($file)) {
    die("ERROR: windows_ai_generator.php nicht gefunden!\n");
}

// Backup
$backup = $file . '.v10.3-tinyllama.backup';
copy($file, $backup);
echo "✅ Backup: $backup\n\n";

// Einlesen
$content = file_get_contents($file);

// MODELL WECHSELN
// Finde: private $model = 'tinyllama:latest';
// Ersetze mit: private $model = 'llama3.2:latest';

if (strpos($content, "private \$model = 'tinyllama:latest';") !== false) {
    $content = str_replace(
        "private \$model = 'tinyllama:latest';",
        "private \$model = 'llama3.2:latest';",
        $content
    );
    echo "✅ Modell geändert: tinyllama -> llama3.2\n";
} else {
    echo "⚠️  tinyllama:latest nicht gefunden, suche Alternativen...\n";
    
    // Versuche andere Varianten
    if (preg_match("/private \\\$model = '([^']+)';/", $content, $matches)) {
        $oldModel = $matches[1];
        $content = str_replace(
            "private \$model = '$oldModel';",
            "private \$model = 'llama3.2:latest';",
            $content
        );
        echo "✅ Modell geändert: $oldModel -> llama3.2\n";
    }
}

// VERSION ÄNDERN
$content = str_replace(
    'v10.3 🇬🇧→🇩🇪 ENGLISH PROMPTS',
    'v10.4 🚀 LLAMA3.2 (3B)',
    $content
);

$content = str_replace(
    'v10.3 ✅ FIXED + 🇬🇧→🇩🇪 ENGLISH PROMPTS',
    'v10.4 ✅ FIXED + 🚀 LLAMA3.2',
    $content
);

$content = str_replace(
    'AI Generator v10.3 ENGLISH PROMPTS',
    'AI Generator v10.4 LLAMA3.2',
    $content
);

// Info-Text ändern
$content = str_replace(
    'TinyLlama = Schnelle Generierung',
    'Llama3.2 (3B) = Bessere Qualität',
    $content
);

// Speichern
file_put_contents($file, $content);

echo "✅ Version: v10.4 LLAMA3.2\n";
echo "✅ Datei gespeichert!\n\n";

echo "═══════════════════════════════════════\n";
echo "WICHTIG - MODELL PRÜFEN:\n";
echo "═══════════════════════════════════════\n\n";

// Prüfe ob llama3.2 installiert ist
$ch = curl_init('http://localhost:11434/api/tags');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$hasLlama32 = false;
$availableModel = '';

if (isset($data['models'])) {
    foreach ($data['models'] as $model) {
        $name = $model['name'] ?? '';
        if (strpos($name, 'llama3.2') !== false) {
            $hasLlama32 = true;
            $availableModel = $name;
            break;
        }
    }
}

if ($hasLlama32) {
    echo "✅ llama3.2 IST INSTALLIERT: $availableModel\n\n";
    echo "PERFEKT! Direkt testen:\n";
    echo "http://localhost/Education/windows_ai_generator.php\n\n";
} else {
    echo "❌ llama3.2 NICHT GEFUNDEN!\n\n";
    echo "INSTALLIERE ES ZUERST:\n\n";
    echo "Öffne CMD und:\n";
    echo "  ollama pull llama3.2\n\n";
    echo "ODER kleinere Version (1B statt 3B):\n";
    echo "  ollama pull llama3.2:1b\n\n";
    echo "Dann Generator anpassen:\n";
    echo "  Öffne windows_ai_generator.php\n";
    echo "  Suche: private \$model = 'llama3.2:latest';\n";
    echo "  Ändere zu: private \$model = 'llama3.2:1b';\n\n";
}

echo "Backup liegt in: $backup\n";
?>
