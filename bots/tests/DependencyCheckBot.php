<?php
/**
 * ============================================================================
 * sgiT Education - Dependency Check Bot
 * ============================================================================
 * 
 * Analysiert PHP-Dateien und findet:
 * - Alle require/include Abhängigkeiten
 * - Ungenutzte Dateien (werden nirgends referenziert)
 * - Zirkuläre Abhängigkeiten
 * - Fehlende Dateien (referenziert aber nicht vorhanden)
 * 
 * Output:
 * - Dependency-Graph als JSON
 * - Liste "sicher löschbar" Dateien
 * - Verbesserungsvorschläge
 * 
 * @version 1.0
 * @date 08.12.2025
 * @author sgiT Solution Engineering & IT Services
 * ============================================================================
 */

require_once dirname(__DIR__) . '/bot_logger.php';
require_once dirname(__DIR__) . '/bot_output_helper.php';

class DependencyCheckBot {
    
    private $logger;
    private $projectRoot;
    private $stopFile;
    
    // Ergebnisse
    private $allFiles = [];
    private $dependencies = [];      // file => [dependencies]
    private $dependents = [];        // file => [files that depend on it]
    private $missingFiles = [];      // Referenziert aber nicht vorhanden
    private $unusedFiles = [];       // Nirgends referenziert
    private $circularDeps = [];      // Zirkuläre Abhängigkeiten
    
    // Statistiken
    private $stats = [
        'total_files' => 0,
        'php_files' => 0,
        'total_dependencies' => 0,
        'unused_files' => 0,
        'missing_files' => 0,
        'circular_deps' => 0
    ];
    
    // Konfiguration
    private $config = [
        'excludeDirs' => [
            '.git',
            'vendor',
            'node_modules',
            '_DISABLED_',
            'backups',
            'logs'
        ],
        'excludeFiles' => [
            'test_',
            'debug_',
            'fix_',
            'emergency_'
        ],
        'coreFiles' => [
            // Diese Dateien sind Entry-Points und gelten nicht als "ungenutzt"
            'index.php',
            'adaptive_learning.php',
            'admin_v4.php',
            'statistics.php',
            'leaderboard.php',
            'batch_import.php',
            'batch_ai_generator.php',
            'windows_ai_generator.php'
        ]
    ];
    
    /**
     * Konstruktor
     */
    public function __construct($projectRoot = null) {
        $this->projectRoot = $projectRoot ?? dirname(dirname(__DIR__));
        $this->logger = new BotLogger('dependency');
        $this->stopFile = dirname(__DIR__) . '/logs/STOP_DEPENDENCY_BOT';
    }
    
    /**
     * Hauptmethode - Führt komplette Analyse durch
     */
    public function run() {
        if (file_exists($this->stopFile)) {
            unlink($this->stopFile);
        }
        
        $this->logger->startRun('Dependency Check Bot v1.0', $this->config);
        $startTime = microtime(true);
        
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("🔍 DEPENDENCY CHECK BOT GESTARTET");
        $this->logger->info("   Projekt: " . $this->projectRoot);
        $this->logger->info("═══════════════════════════════════════════════════════");
        
        // Phase 1: Alle PHP-Dateien sammeln
        $this->logger->info("");
        $this->logger->info("📁 Phase 1: PHP-Dateien scannen...");
        $this->scanAllFiles();
        $this->logger->success("   Gefunden: {$this->stats['php_files']} PHP-Dateien");
        
        // Phase 2: Abhängigkeiten analysieren
        $this->logger->info("");
        $this->logger->info("🔗 Phase 2: Abhängigkeiten analysieren...");
        $this->analyzeDependencies();
        $this->logger->success("   Gefunden: {$this->stats['total_dependencies']} Abhängigkeiten");
        
        // Phase 3: Ungenutzte Dateien finden
        $this->logger->info("");
        $this->logger->info("🗑️ Phase 3: Ungenutzte Dateien suchen...");
        $this->findUnusedFiles();
        $this->logger->success("   Gefunden: {$this->stats['unused_files']} ungenutzte Dateien");
        
        // Phase 4: Fehlende Dateien finden
        $this->logger->info("");
        $this->logger->info("❓ Phase 4: Fehlende Dateien prüfen...");
        $this->findMissingFiles();
        if ($this->stats['missing_files'] > 0) {
            $this->logger->warning("   Gefunden: {$this->stats['missing_files']} fehlende Dateien");
        } else {
            $this->logger->success("   Keine fehlenden Dateien");
        }
        
        // Phase 5: Zirkuläre Abhängigkeiten
        $this->logger->info("");
        $this->logger->info("🔄 Phase 5: Zirkuläre Abhängigkeiten prüfen...");
        $this->findCircularDependencies();
        if ($this->stats['circular_deps'] > 0) {
            $this->logger->warning("   Gefunden: {$this->stats['circular_deps']} zirkuläre Abhängigkeiten");
        } else {
            $this->logger->success("   Keine zirkulären Abhängigkeiten");
        }
        
        // Ergebnisse ausgeben
        $totalTime = round((microtime(true) - $startTime), 2);
        $this->generateReport($totalTime);
        
        $this->logger->endRun("Analyse abgeschlossen in {$totalTime}s");
        
        return $this->getResults();
    }
    
    /**
     * Scannt alle PHP-Dateien im Projekt
     */
    private function scanAllFiles() {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            // Verzeichnisse überspringen die excluded sind
            $relativePath = $this->getRelativePath($file->getPathname());
            
            if ($this->isExcluded($relativePath)) {
                continue;
            }
            
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->allFiles[] = $relativePath;
                $this->stats['php_files']++;
                $this->dependencies[$relativePath] = [];
                $this->dependents[$relativePath] = [];
            }
            
            $this->stats['total_files']++;
        }
        
        sort($this->allFiles);
    }
    
    /**
     * Analysiert Abhängigkeiten in allen Dateien
     */
    private function analyzeDependencies() {
        foreach ($this->allFiles as $file) {
            $fullPath = $this->projectRoot . '/' . $file;
            
            if (!file_exists($fullPath)) continue;
            
            $content = file_get_contents($fullPath);
            $deps = $this->extractDependencies($content, $file);
            
            $this->dependencies[$file] = $deps;
            $this->stats['total_dependencies'] += count($deps);
            
            // Rückwärts-Referenzen aufbauen
            foreach ($deps as $dep) {
                if (!isset($this->dependents[$dep])) {
                    $this->dependents[$dep] = [];
                }
                $this->dependents[$dep][] = $file;
            }
        }
    }
    
    /**
     * Extrahiert require/include aus Dateiinhalt
     */
    private function extractDependencies($content, $currentFile) {
        $deps = [];
        $currentDir = dirname($currentFile);
        
        // Patterns für require/include
        $patterns = [
            '/require_once\s*[\(\s]*[\'"]([^\'"]+)[\'"]\s*[\)]?\s*;/i',
            '/require\s*[\(\s]*[\'"]([^\'"]+)[\'"]\s*[\)]?\s*;/i',
            '/include_once\s*[\(\s]*[\'"]([^\'"]+)[\'"]\s*[\)]?\s*;/i',
            '/include\s*[\(\s]*[\'"]([^\'"]+)[\'"]\s*[\)]?\s*;/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $resolvedPath = $this->resolvePath($match, $currentDir);
                    if ($resolvedPath && !in_array($resolvedPath, $deps)) {
                        $deps[] = $resolvedPath;
                    }
                }
            }
        }
        
        return $deps;
    }
    
    /**
     * Löst relativen Pfad auf
     */
    private function resolvePath($path, $currentDir) {
        // __DIR__ und dirname() Konstrukte ersetzen
        $path = preg_replace('/\s*\.\s*/', '', $path);
        $path = str_replace(['__DIR__', '__FILE__'], '', $path);
        
        // Wenn Pfad mit / beginnt, ist es relativ zum Projekt-Root
        if (strpos($path, '/') === 0) {
            $path = ltrim($path, '/');
        }
        // dirname(__DIR__) Pattern
        elseif (preg_match('/dirname\s*\(\s*__DIR__\s*\)/', $path)) {
            $path = preg_replace('/dirname\s*\(\s*__DIR__\s*\)\s*\.\s*/', '', $path);
            $path = dirname($currentDir) . '/' . ltrim($path, '/');
        }
        // dirname(dirname(__DIR__)) Pattern  
        elseif (preg_match('/dirname\s*\(\s*dirname\s*\(\s*__DIR__\s*\)\s*\)/', $path)) {
            $path = preg_replace('/dirname\s*\(\s*dirname\s*\(\s*__DIR__\s*\)\s*\)\s*\.\s*/', '', $path);
            $path = dirname(dirname($currentDir)) . '/' . ltrim($path, '/');
        }
        // Relative Pfade (../)
        elseif (strpos($path, '../') !== false || strpos($path, './') !== false) {
            $path = $this->resolveRelativePath($currentDir . '/' . $path);
        }
        // Einfacher Dateiname
        else {
            $path = $currentDir . '/' . $path;
        }
        
        // Normalisieren
        $path = $this->normalizePath($path);
        
        // Prüfen ob Datei in unserem Projekt ist
        if (in_array($path, $this->allFiles)) {
            return $path;
        }
        
        // Versuche mit .php Endung
        if (!str_ends_with($path, '.php')) {
            $pathWithPhp = $path . '.php';
            if (in_array($pathWithPhp, $this->allFiles)) {
                return $pathWithPhp;
            }
        }
        
        return null;
    }
    
    /**
     * Löst ../ und ./ Pfade auf
     */
    private function resolveRelativePath($path) {
        $parts = explode('/', $path);
        $result = [];
        
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($result);
            } elseif ($part !== '.' && $part !== '') {
                $result[] = $part;
            }
        }
        
        return implode('/', $result);
    }
    
    /**
     * Normalisiert Pfad
     */
    private function normalizePath($path) {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);
        $path = trim($path, '/');
        return $path;
    }
    
    /**
     * Findet ungenutzte Dateien
     */
    private function findUnusedFiles() {
        foreach ($this->allFiles as $file) {
            // Entry-Points überspringen
            $basename = basename($file);
            if (in_array($basename, $this->config['coreFiles'])) {
                continue;
            }
            
            // Prüfen ob Datei irgendwo referenziert wird
            $isReferenced = false;
            foreach ($this->dependencies as $deps) {
                if (in_array($file, $deps)) {
                    $isReferenced = true;
                    break;
                }
            }
            
            if (!$isReferenced) {
                // Zusätzlicher Check: Ist es ein Entry-Point (wird direkt aufgerufen)?
                if (!$this->isEntryPoint($file)) {
                    $this->unusedFiles[] = $file;
                }
            }
        }
        
        $this->stats['unused_files'] = count($this->unusedFiles);
        sort($this->unusedFiles);
    }
    
    /**
     * Prüft ob Datei ein Entry-Point ist
     */
    private function isEntryPoint($file) {
        $basename = basename($file);
        
        // Core Files
        if (in_array($basename, $this->config['coreFiles'])) {
            return true;
        }
        
        // index.php in Unterordnern
        if ($basename === 'index.php') {
            return true;
        }
        
        // Bot-Dateien
        if (strpos($file, 'bots/') !== false && str_ends_with($file, 'Bot.php')) {
            return true;
        }
        
        // Runner/Starter Dateien
        if (strpos($basename, 'runner') !== false || strpos($basename, 'run_') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Findet fehlende Dateien (referenziert aber nicht vorhanden)
     */
    private function findMissingFiles() {
        foreach ($this->allFiles as $file) {
            $fullPath = $this->projectRoot . '/' . $file;
            if (!file_exists($fullPath)) continue;
            
            $content = file_get_contents($fullPath);
            
            // Alle require/include extrahieren (auch die die nicht aufgelöst werden konnten)
            $patterns = [
                '/require_once\s*[\(\s]*[\'"]([^\'"]+)[\'"]/i',
                '/require\s*[\(\s]*[\'"]([^\'"]+)[\'"]/i',
                '/include_once\s*[\(\s]*[\'"]([^\'"]+)[\'"]/i',
                '/include\s*[\(\s]*[\'"]([^\'"]+)[\'"]/i'
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $match) {
                        // Komplexe Pfade mit Variablen überspringen
                        if (strpos($match, '$') !== false) continue;
                        if (strpos($match, '__DIR__') !== false) continue;
                        
                        $resolvedPath = $this->resolvePath($match, dirname($file));
                        
                        // Wenn nicht aufgelöst werden konnte, ist es evtl. fehlend
                        if (!$resolvedPath) {
                            $potentialPath = dirname($file) . '/' . $match;
                            $fullPotentialPath = $this->projectRoot . '/' . $this->normalizePath($potentialPath);
                            
                            if (!file_exists($fullPotentialPath) && !file_exists($fullPotentialPath . '.php')) {
                                $this->missingFiles[$file][] = $match;
                            }
                        }
                    }
                }
            }
        }
        
        $this->stats['missing_files'] = count($this->missingFiles);
    }
    
    /**
     * Findet zirkuläre Abhängigkeiten
     */
    private function findCircularDependencies() {
        foreach ($this->allFiles as $file) {
            $visited = [];
            $this->detectCycle($file, $visited, []);
        }
        
        $this->stats['circular_deps'] = count($this->circularDeps);
    }
    
    /**
     * DFS für Zykluserkennung
     */
    private function detectCycle($file, &$visited, $path) {
        if (in_array($file, $path)) {
            // Zyklus gefunden!
            $cycleStart = array_search($file, $path);
            $cycle = array_slice($path, $cycleStart);
            $cycle[] = $file;
            
            $cycleKey = implode(' -> ', $cycle);
            if (!in_array($cycleKey, $this->circularDeps)) {
                $this->circularDeps[] = $cycleKey;
            }
            return;
        }
        
        if (isset($visited[$file])) {
            return;
        }
        
        $visited[$file] = true;
        $path[] = $file;
        
        if (isset($this->dependencies[$file])) {
            foreach ($this->dependencies[$file] as $dep) {
                $this->detectCycle($dep, $visited, $path);
            }
        }
    }
    
    /**
     * Prüft ob Pfad excluded ist
     */
    private function isExcluded($path) {
        foreach ($this->config['excludeDirs'] as $exclude) {
            if (strpos($path, $exclude) !== false) {
                return true;
            }
        }
        
        $basename = basename($path);
        foreach ($this->config['excludeFiles'] as $exclude) {
            if (strpos($basename, $exclude) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Gibt relativen Pfad zurück
     */
    private function getRelativePath($fullPath) {
        $path = str_replace($this->projectRoot, '', $fullPath);
        $path = str_replace('\\', '/', $path);
        return ltrim($path, '/');
    }
    
    /**
     * Generiert den Abschlussbericht
     */
    private function generateReport($totalTime) {
        $this->logger->info("");
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("📊 ANALYSE-ERGEBNIS");
        $this->logger->info("═══════════════════════════════════════════════════════");
        
        $this->logger->info("");
        $this->logger->info("📈 Statistiken:");
        $this->logger->info("   PHP-Dateien:       {$this->stats['php_files']}");
        $this->logger->info("   Abhängigkeiten:    {$this->stats['total_dependencies']}");
        $this->logger->info("   Ungenutzte Dateien: {$this->stats['unused_files']}");
        $this->logger->info("   Fehlende Dateien:  {$this->stats['missing_files']}");
        $this->logger->info("   Zirkuläre Deps:    {$this->stats['circular_deps']}");
        
        // Ungenutzte Dateien auflisten
        if (!empty($this->unusedFiles)) {
            $this->logger->info("");
            $this->logger->info("🗑️ UNGENUTZTE DATEIEN (möglicherweise löschbar):");
            $this->logger->info("───────────────────────────────────────────────────────");
            
            foreach ($this->unusedFiles as $file) {
                $this->logger->warning("   ⚠️ $file");
            }
            
            $this->logger->info("");
            $this->logger->info("   💡 Tipp: Prüfe diese Dateien manuell bevor du sie löschst!");
            $this->logger->info("      Manche könnten per AJAX/URL direkt aufgerufen werden.");
        }
        
        // Fehlende Dateien auflisten
        if (!empty($this->missingFiles)) {
            $this->logger->info("");
            $this->logger->info("❓ FEHLENDE DATEIEN (werden referenziert aber nicht gefunden):");
            $this->logger->info("───────────────────────────────────────────────────────");
            
            foreach ($this->missingFiles as $file => $missing) {
                $this->logger->error("   In $file:");
                foreach ($missing as $m) {
                    $this->logger->error("      → $m");
                }
            }
        }
        
        // Zirkuläre Abhängigkeiten
        if (!empty($this->circularDeps)) {
            $this->logger->info("");
            $this->logger->info("🔄 ZIRKULÄRE ABHÄNGIGKEITEN:");
            $this->logger->info("───────────────────────────────────────────────────────");
            
            foreach ($this->circularDeps as $cycle) {
                $this->logger->warning("   ⚠️ $cycle");
            }
        }
        
        $this->logger->info("");
        $this->logger->info("═══════════════════════════════════════════════════════");
        $this->logger->info("✅ Analyse abgeschlossen in {$totalTime}s");
        $this->logger->info("═══════════════════════════════════════════════════════");
    }
    
    /**
     * Gibt alle Ergebnisse zurück
     */
    public function getResults() {
        return [
            'stats' => $this->stats,
            'unused_files' => $this->unusedFiles,
            'missing_files' => $this->missingFiles,
            'circular_deps' => $this->circularDeps,
            'dependencies' => $this->dependencies,
            'dependents' => $this->dependents
        ];
    }
    
    /**
     * Exportiert Dependency-Graph als JSON
     */
    public function exportGraph($filename = null) {
        $filename = $filename ?? dirname(__DIR__) . '/logs/dependency_graph.json';
        
        $graph = [
            'generated' => date('Y-m-d H:i:s'),
            'stats' => $this->stats,
            'nodes' => [],
            'edges' => []
        ];
        
        // Nodes (alle Dateien)
        foreach ($this->allFiles as $file) {
            $graph['nodes'][] = [
                'id' => $file,
                'label' => basename($file),
                'unused' => in_array($file, $this->unusedFiles),
                'dependencies' => count($this->dependencies[$file] ?? []),
                'dependents' => count($this->dependents[$file] ?? [])
            ];
        }
        
        // Edges (Abhängigkeiten)
        foreach ($this->dependencies as $file => $deps) {
            foreach ($deps as $dep) {
                $graph['edges'][] = [
                    'from' => $file,
                    'to' => $dep
                ];
            }
        }
        
        file_put_contents($filename, json_encode($graph, JSON_PRETTY_PRINT));
        
        return $filename;
    }
}

// CLI-Ausführung
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === basename(__FILE__)) {
    $bot = new DependencyCheckBot();
    $results = $bot->run();
    $bot->exportGraph();
}
