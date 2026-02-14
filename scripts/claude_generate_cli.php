<?php
/**
 * Claude Fragen-Generator CLI
 *
 * Generiert Quiz-Fragen via Claude API und speichert sie in der Datenbank.
 *
 * Verwendung:
 *   php claude_generate_cli.php --module=mathematik --count=10
 *   php claude_generate_cli.php --module=physik --count=5 --age-min=11 --age-max=14 --difficulty=4
 *   php claude_generate_cli.php --list-modules
 */

// Pfade relativ zum Script
$baseDir = dirname(__DIR__);

// Config laden
$configPath = $baseDir . '/includes/claude_config.php';
if (!file_exists($configPath)) {
    fwrite(STDERR, "FEHLER: includes/claude_config.php nicht gefunden.\n");
    fwrite(STDERR, "Erstelle die Datei mit deinem ANTHROPIC_API_KEY.\n");
    exit(1);
}
require_once $configPath;

if (!defined('ANTHROPIC_API_KEY') || ANTHROPIC_API_KEY === '') {
    fwrite(STDERR, "FEHLER: ANTHROPIC_API_KEY ist leer.\n");
    exit(1);
}

require_once $baseDir . '/includes/ClaudeClient.php';

// Argumente parsen
$options = getopt('', ['module:', 'count:', 'age-min:', 'age-max:', 'difficulty:', 'list-modules', 'dry-run', 'help']);

if (isset($options['help']) || ($argc <= 1)) {
    echo "Claude Fragen-Generator CLI\n";
    echo "===========================\n\n";
    echo "Verwendung:\n";
    echo "  php {$argv[0]} --module=mathematik --count=10\n";
    echo "  php {$argv[0]} --module=physik --count=5 --age-min=11 --age-max=14 --difficulty=4\n";
    echo "  php {$argv[0]} --list-modules\n\n";
    echo "Optionen:\n";
    echo "  --module=NAME       Modul-Name (z.B. mathematik, physik)\n";
    echo "  --count=N           Anzahl Fragen (1-20, Standard: 5)\n";
    echo "  --age-min=N         Mindestalter (Standard: 8)\n";
    echo "  --age-max=N         Hoechstalter (Standard: 12)\n";
    echo "  --difficulty=N      Schwierigkeit 1-5 (Standard: 3)\n";
    echo "  --dry-run           Nur generieren, nicht speichern\n";
    echo "  --list-modules      Alle verfuegbaren Module anzeigen\n";
    echo "  --help              Diese Hilfe anzeigen\n";
    exit(0);
}

$client = new ClaudeClient(ANTHROPIC_API_KEY, defined('CLAUDE_MODEL') ? CLAUDE_MODEL : 'claude-sonnet-4-5-20250929');

// Module auflisten
if (isset($options['list-modules'])) {
    $modules = $client->getModules();
    echo "Verfuegbare Module (" . count($modules) . "):\n";
    echo str_repeat('-', 40) . "\n";
    foreach ($modules as $key => $mod) {
        echo sprintf("  %-20s %s\n", $key, $mod['name']);
    }
    exit(0);
}

// Pflichtparameter pruefen
if (empty($options['module'])) {
    fwrite(STDERR, "FEHLER: --module ist erforderlich. Nutze --list-modules fuer verfuegbare Module.\n");
    exit(1);
}

$module = $options['module'];
$count = max(1, min(20, intval($options['count'] ?? 5)));
$ageMin = max(5, intval($options['age-min'] ?? 8));
$ageMax = max($ageMin, intval($options['age-max'] ?? 12));
$difficulty = max(1, min(5, intval($options['difficulty'] ?? 3)));
$dryRun = isset($options['dry-run']);

echo "Claude Fragen-Generator\n";
echo "=======================\n";
echo "Modul:        {$module}\n";
echo "Anzahl:       {$count}\n";
echo "Alter:        {$ageMin}-{$ageMax} Jahre\n";
echo "Schwierigkeit: {$difficulty}/5\n";
echo "Modell:       " . (defined('CLAUDE_MODEL') ? CLAUDE_MODEL : 'claude-sonnet-4-5-20250929') . "\n";
if ($dryRun) echo "Modus:        DRY-RUN (nicht speichern)\n";
echo str_repeat('-', 40) . "\n\n";

echo "Generiere Fragen...\n";

$result = $client->generate($module, $count, $ageMin, $ageMax, $difficulty);

if (!$result['success']) {
    fwrite(STDERR, "FEHLER: " . $result['error'] . "\n");
    exit(1);
}

echo "{$result['raw_count']} Fragen generiert.\n";

if ($result['usage']) {
    echo "Tokens: {$result['usage']['input_tokens']} Input + {$result['usage']['output_tokens']} Output\n";
}
if ($result['cost_estimate']) {
    echo "Kosten: ~{$result['cost_estimate']}\n";
}
echo "\n";

// Fragen anzeigen
foreach ($result['questions'] as $i => $q) {
    echo ($i + 1) . ". {$q['question']}\n";
    echo "   Richtig: {$q['correct']}\n";
    foreach ($q['wrong'] as $j => $w) {
        echo "   Falsch " . ($j + 1) . ": {$w}\n";
    }
    if (!empty($q['explanation'])) {
        echo "   Erklaerung: {$q['explanation']}\n";
    }
    echo "\n";
}

// Speichern
if (!$dryRun) {
    $dbPath = $baseDir . '/AI/data/questions.db';
    if (!file_exists($dbPath)) {
        fwrite(STDERR, "FEHLER: Datenbank nicht gefunden: {$dbPath}\n");
        exit(1);
    }

    $saved = $client->saveToDatabase($dbPath, $module, $result['questions'], $difficulty, $ageMin, $ageMax);
    echo "Ergebnis: {$saved}/{$result['raw_count']} Fragen in DB gespeichert.\n";

    if ($saved < $result['raw_count']) {
        echo "(" . ($result['raw_count'] - $saved) . " Duplikate uebersprungen)\n";
    }
} else {
    echo "[DRY-RUN] Keine Fragen gespeichert.\n";
}
