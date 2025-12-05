<?php
/**
 * sgiT Education Platform - Backup & Restore Manager v2.2
 * 
 * VOLLSTÄNDIGES Backup-System mit:
 * - Komplettes Projekt-Backup (ohne _DISABLED_)
 * - Lokale Speicherung + OneDrive Fallback
 * - One-Click Restore
 * - KONFIGURIERBARE PFADE (NEU in v2.2)
 * 
 * v2.2: Konfigurierbare Backup-Pfade via backup_config.json
 * v2.1: BUG-021 FIX - Docker-kompatible OneDrive-Pfade
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 2.2
 * @date 06.12.2025
 */

// ================================================================
// KONFIGURATION LADEN
// ================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0);
set_time_limit(600); // 10 Minuten für große Backups
ini_set('memory_limit', '512M');

define('ADMIN_PASSWORD', 'sgit2025');
define('SOURCE_PATH', __DIR__);
define('CONFIG_FILE', __DIR__ . '/config/backup_config.json');

// Konfiguration laden
function loadBackupConfig() {
    if (file_exists(CONFIG_FILE)) {
        $config = json_decode(file_get_contents(CONFIG_FILE), true);
        if ($config) return $config;
    }
    // Fallback zu Default-Werten
    return [
        'backup_paths' => [
            'local' => ['enabled' => true, 'path_docker' => '/var/www/html/backups', 'path_windows' => 'C:/xampp/htdocs/Education/backups'],
            'onedrive' => ['enabled' => true, 'path_docker' => '/mnt/onedrive-backup', 'path_windows' => 'C:/Users/SG/OneDrive/sgiT/sgiT/WebsiteSourcefiles_sgitspace/backups'],
            'custom' => ['enabled' => false, 'path_docker' => '', 'path_windows' => '']
        ],
        'settings' => ['max_backups' => 5, 'exclude_patterns' => ['_DISABLED_', '.git', 'node_modules']]
    ];
}

$backupConfig = loadBackupConfig();

// Umgebungserkennung
$isDocker = is_dir('/var/www/html') && !is_dir('C:/xampp');
define('IS_DOCKER', $isDocker);

// Pfade aus Konfiguration ermitteln
function getActivePath($pathConfig) {
    global $isDocker;
    if (!$pathConfig['enabled']) return null;
    return $isDocker ? $pathConfig['path_docker'] : $pathConfig['path_windows'];
}

$localPath = getActivePath($backupConfig['backup_paths']['local']);
$onedrivePath = getActivePath($backupConfig['backup_paths']['onedrive']);
$customPath = getActivePath($backupConfig['backup_paths']['custom']);

// Primärer Backup-Pfad (lokal oder custom)
define('BACKUP_PATH_LOCAL', $localPath ?: __DIR__ . '/backups');
define('BACKUP_PATH_ONEDRIVE', $onedrivePath ?: '');
define('BACKUP_PATH_CUSTOM', $customPath ?: '');
define('MAX_BACKUPS', $backupConfig['settings']['max_backups'] ?? 5);

session_start();

// ================================================================
// AUTHENTIFIZIERUNG
// ================================================================

$authenticated = isset($_SESSION['backup_auth']) && $_SESSION['backup_auth'] === true;

if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['backup_auth'] = true;
        $authenticated = true;
    } else {
        $loginError = 'Falsches Passwort!';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['backup_auth']);
    header('Location: backup_manager.php');
    exit;
}

// ================================================================
// HELPER FUNKTIONEN
// ================================================================

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

function getProjectVersion() {
    $statusFile = SOURCE_PATH . '/sgit_education_status_report.md';
    if (file_exists($statusFile)) {
        $content = file_get_contents($statusFile);
        if (preg_match('/Version:\*\*\s*([\d.]+)/', $content, $matches)) {
            return $matches[1];
        }
    }
    return '2.5.x';
}

/**
 * Berechnet die Gesamtgröße eines Verzeichnisses
 */
function getDirectorySize($path, $excludePatterns = []) {
    $size = 0;
    
    if (!is_dir($path)) return 0;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filePath = $file->getPathname();
            $skip = false;
            
            foreach ($excludePatterns as $pattern) {
                if (strpos($filePath, $pattern) !== false) {
                    $skip = true;
                    break;
                }
            }
            
            if (!$skip) {
                $size += $file->getSize();
            }
        }
    }
    
    return $size;
}

/**
 * Listet alle Backups (lokal + OneDrive)
 */
function listBackups() {
    $backups = ['local' => [], 'onedrive' => []];
    
    // Lokale Backups
    if (is_dir(BACKUP_PATH_LOCAL)) {
        $files = glob(BACKUP_PATH_LOCAL . '/sgit-edu-*.zip');
        foreach ($files as $file) {
            $backups['local'][] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'created' => filemtime($file),
                'location' => 'local'
            ];
        }
    }
    
    // OneDrive Backups
    if (is_dir(BACKUP_PATH_ONEDRIVE)) {
        $dirs = glob(BACKUP_PATH_ONEDRIVE . '/sgit-EDU-Fullbackup_*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $zipFile = $dir . '/backup.zip';
            if (file_exists($zipFile)) {
                $backups['onedrive'][] = [
                    'filename' => basename($dir),
                    'path' => $dir,
                    'size' => filesize($zipFile),
                    'created' => filemtime($dir),
                    'location' => 'onedrive'
                ];
            }
        }
    }
    
    // Sortieren nach Datum
    usort($backups['local'], fn($a, $b) => $b['created'] - $a['created']);
    usort($backups['onedrive'], fn($a, $b) => $b['created'] - $a['created']);
    
    return $backups;
}

/**
 * Erstellt ein VOLLSTÄNDIGES Backup
 */
function createFullBackup() {
    $result = [
        'success' => false,
        'message' => '',
        'local' => null,
        'onedrive' => null,
        'total_files' => 0,
        'total_size' => 0,
        'duration' => 0
    ];
    
    $startTime = microtime(true);
    $timestamp = date('Y-m-d_H-i-s');
    $dateForFolder = date('Y-m-d');
    
    // ================================================================
    // EXCLUDE PATTERNS - Was NICHT ins Backup soll
    // ================================================================
    $excludePatterns = [
        '_DISABLED_',           // Deaktivierte Module
        'backups',              // Backup-Verzeichnis selbst
        '.git',                 // Git
        '.idea',                // IDE
        '*.log',                // Log-Dateien (werden neu erstellt)
        '*.db.old*',            // Alte DB-Backups
        '*.bak',                // Backup-Dateien
        '*.tmp',                // Temp-Dateien
        'node_modules',         // Node (falls vorhanden)
        '__pycache__',          // Python Cache
    ];
    
    // ================================================================
    // 1. LOKALES BACKUP
    // ================================================================
    if (!is_dir(BACKUP_PATH_LOCAL)) {
        mkdir(BACKUP_PATH_LOCAL, 0755, true);
    }
    
    $localZipName = "sgit-edu-fullbackup-{$timestamp}.zip";
    $localZipPath = BACKUP_PATH_LOCAL . '/' . $localZipName;
    
    $zip = new ZipArchive();
    if ($zip->open($localZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        $result['message'] = 'Konnte lokales ZIP nicht erstellen!';
        return $result;
    }
    
    $filesAdded = 0;
    $totalSize = 0;
    
    // Rekursiv alle Dateien hinzufügen
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(SOURCE_PATH, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        $filePath = $file->getPathname();
        $relativePath = str_replace(SOURCE_PATH . DIRECTORY_SEPARATOR, '', $filePath);
        $relativePath = str_replace('\\', '/', $relativePath);
        
        // Exclude-Check
        $skip = false;
        foreach ($excludePatterns as $pattern) {
            if (strpos($relativePath, $pattern) !== false || 
                fnmatch($pattern, basename($filePath))) {
                $skip = true;
                break;
            }
        }
        
        if ($skip) continue;
        
        if ($file->isFile()) {
            $zip->addFile($filePath, $relativePath);
            $filesAdded++;
            $totalSize += $file->getSize();
        } elseif ($file->isDir()) {
            $zip->addEmptyDir($relativePath);
        }
    }
    
    // Manifest hinzufügen
    $manifest = [
        'backup_info' => [
            'date' => $timestamp,
            'type' => 'full',
            'version' => '2.0',
            'created_by' => 'backup_manager.php'
        ],
        'project' => [
            'name' => 'sgiT Education Platform',
            'version' => getProjectVersion(),
            'source_path' => SOURCE_PATH
        ],
        'statistics' => [
            'files_count' => $filesAdded,
            'total_size_bytes' => $totalSize,
            'total_size_human' => formatBytes($totalSize),
            'excluded_patterns' => $excludePatterns
        ],
        'system' => [
            'hostname' => gethostname(),
            'php_version' => PHP_VERSION,
            'created_at' => date('c')
        ],
        'restore_instructions' => [
            'step1' => 'Entpacke das ZIP in ein leeres Verzeichnis',
            'step2' => 'Kopiere alle Dateien nach C:\\xampp\\htdocs\\Education\\',
            'step3' => 'Starte Apache in XAMPP',
            'step4' => 'Öffne http://localhost/Education/'
        ]
    ];
    
    $zip->addFromString('BACKUP_MANIFEST.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $zip->addFromString('README_RESTORE.txt', 
        "=== sgiT Education Backup ===\n" .
        "Datum: " . date('d.m.Y H:i:s') . "\n" .
        "Version: " . getProjectVersion() . "\n" .
        "Dateien: $filesAdded\n" .
        "Größe: " . formatBytes($totalSize) . "\n\n" .
        "=== RESTORE ANLEITUNG ===\n" .
        "1. Entpacke dieses ZIP\n" .
        "2. Kopiere ALLE Dateien nach: C:\\xampp\\htdocs\\Education\\\n" .
        "3. Starte XAMPP (Apache)\n" .
        "4. Öffne: http://localhost/Education/\n" .
        "5. Fertig!\n"
    );
    
    $zip->close();
    
    $localSize = filesize($localZipPath);
    $result['local'] = [
        'path' => $localZipPath,
        'filename' => $localZipName,
        'size' => $localSize
    ];
    
    // ================================================================
    // 2. ONEDRIVE BACKUP (Fallback)
    // ================================================================
    $oneDriveSuccess = false;
    $oneDrivePath = BACKUP_PATH_ONEDRIVE . "/sgit-EDU-Fullbackup_{$dateForFolder}";
    
    if (is_dir(dirname(BACKUP_PATH_ONEDRIVE))) {
        // OneDrive-Verzeichnis erstellen
        if (!is_dir(BACKUP_PATH_ONEDRIVE)) {
            @mkdir(BACKUP_PATH_ONEDRIVE, 0755, true);
        }
        
        if (!is_dir($oneDrivePath)) {
            @mkdir($oneDrivePath, 0755, true);
        }
        
        if (is_dir($oneDrivePath)) {
            // ZIP dorthin kopieren
            $oneDriveZip = $oneDrivePath . '/backup.zip';
            if (copy($localZipPath, $oneDriveZip)) {
                // Manifest separat speichern
                file_put_contents($oneDrivePath . '/BACKUP_MANIFEST.json', 
                    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                file_put_contents($oneDrivePath . '/README_RESTORE.txt',
                    "=== sgiT Education Backup ===\n" .
                    "Datum: " . date('d.m.Y H:i:s') . "\n" .
                    "Dieses Backup wird automatisch mit OneDrive synchronisiert.\n"
                );
                
                $oneDriveSuccess = true;
                $result['onedrive'] = [
                    'path' => $oneDrivePath,
                    'size' => filesize($oneDriveZip)
                ];
            }
        }
    }
    
    // ================================================================
    // 3. ALTE BACKUPS AUFRÄUMEN
    // ================================================================
    cleanupOldBackups();
    
    // ================================================================
    // ERGEBNIS
    // ================================================================
    $result['success'] = true;
    $result['total_files'] = $filesAdded;
    $result['total_size'] = $totalSize;
    $result['duration'] = round(microtime(true) - $startTime, 2);
    $result['message'] = "Backup erfolgreich! $filesAdded Dateien (" . formatBytes($totalSize) . ")";
    
    if (!$oneDriveSuccess) {
        $result['message'] .= " ⚠️ OneDrive-Backup fehlgeschlagen!";
    }
    
    return $result;
}

/**
 * Löscht alte Backups
 */
function cleanupOldBackups() {
    // Lokale Backups
    $localBackups = glob(BACKUP_PATH_LOCAL . '/sgit-edu-*.zip');
    usort($localBackups, fn($a, $b) => filemtime($b) - filemtime($a));
    
    if (count($localBackups) > MAX_BACKUPS) {
        foreach (array_slice($localBackups, MAX_BACKUPS) as $old) {
            @unlink($old);
        }
    }
    
    // OneDrive Backups
    if (is_dir(BACKUP_PATH_ONEDRIVE)) {
        $oneDriveBackups = glob(BACKUP_PATH_ONEDRIVE . '/sgit-EDU-Fullbackup_*', GLOB_ONLYDIR);
        usort($oneDriveBackups, fn($a, $b) => filemtime($b) - filemtime($a));
        
        if (count($oneDriveBackups) > MAX_BACKUPS) {
            foreach (array_slice($oneDriveBackups, MAX_BACKUPS) as $old) {
                // Verzeichnis rekursiv löschen
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($old, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $file) {
                    $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath());
                }
                @rmdir($old);
            }
        }
    }
}

/**
 * Löscht ein einzelnes Backup
 */
function deleteBackup($filename, $location) {
    if ($location === 'local') {
        $path = BACKUP_PATH_LOCAL . '/' . basename($filename);
        if (file_exists($path) && strpos($filename, 'sgit-edu-') === 0) {
            return @unlink($path);
        }
    } elseif ($location === 'onedrive') {
        $path = BACKUP_PATH_ONEDRIVE . '/' . basename($filename);
        if (is_dir($path)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath());
            }
            return @rmdir($path);
        }
    }
    return false;
}

/**
 * Gibt System-Status zurück
 */
function getSystemStatus() {
    $status = [];
    
    // Projekt-Größe berechnen (ohne excludes)
    $excludePatterns = ['_DISABLED_', 'backups', '.git'];
    $projectSize = getDirectorySize(SOURCE_PATH, $excludePatterns);
    
    // Datenbanken
    $databases = [
        'Fragen-DB' => SOURCE_PATH . '/AI/data/questions.db',
        'Wallet-DB' => SOURCE_PATH . '/wallet/wallet.db',
        'Bot-DB' => SOURCE_PATH . '/bots/logs/bot_results.db'
    ];
    
    $dbTotalSize = 0;
    foreach ($databases as $name => $path) {
        $size = file_exists($path) ? filesize($path) : 0;
        $dbTotalSize += $size;
        $status['databases'][$name] = [
            'exists' => file_exists($path),
            'size' => $size
        ];
    }
    
    // Speicherorte prüfen
    $status['storage'] = [
        'local' => [
            'path' => BACKUP_PATH_LOCAL,
            'exists' => is_dir(BACKUP_PATH_LOCAL) || is_writable(dirname(BACKUP_PATH_LOCAL)),
            'free_space' => disk_free_space(dirname(BACKUP_PATH_LOCAL))
        ],
        'onedrive' => [
            'path' => BACKUP_PATH_ONEDRIVE,
            'exists' => is_dir(dirname(BACKUP_PATH_ONEDRIVE)),
            'accessible' => is_writable(dirname(BACKUP_PATH_ONEDRIVE)) || is_dir(BACKUP_PATH_ONEDRIVE)
        ]
    ];
    
    // Projekt-Infos
    $status['project'] = [
        'version' => getProjectVersion(),
        'total_size' => $projectSize,
        'db_size' => $dbTotalSize,
        'source_size' => $projectSize - $dbTotalSize
    ];
    
    return $status;
}

// ================================================================
// AJAX HANDLER - MUSS VOR HTML KOMMEN!
// ================================================================

if (isset($_GET['action']) && $authenticated) {
    // Fehler als JSON ausgeben
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });
    
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($_GET['action']) {
            case 'create':
                $result = createFullBackup();
                echo json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
                
            case 'list':
                echo json_encode(listBackups(), JSON_UNESCAPED_UNICODE);
                break;
                
            case 'delete':
                $filename = $_GET['file'] ?? '';
                $location = $_GET['location'] ?? 'local';
                echo json_encode(['success' => deleteBackup($filename, $location)]);
                break;
                
            case 'status':
                echo json_encode(getSystemStatus(), JSON_UNESCAPED_UNICODE);
                break;
                
            case 'download':
                $filename = basename($_GET['file'] ?? '');
                $path = BACKUP_PATH_LOCAL . '/' . $filename;
                if (file_exists($path) && strpos($filename, 'sgit-edu-') === 0) {
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Content-Length: ' . filesize($path));
                    readfile($path);
                }
                break;
                
            default:
                echo json_encode(['error' => 'Unbekannte Aktion']);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => true,
            'message' => 'PHP Fehler: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    } catch (Error $e) {
        echo json_encode([
            'success' => false,
            'error' => true,
            'message' => 'Fataler Fehler: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// ================================================================
// HTML OUTPUT
// ================================================================
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💾 Backup Manager v2.1 - sgiT Education</title>
    <style>
        :root {
            --primary: #1A3503;
            --accent: #43D240;
            --accent-hover: #3ab837;
            --danger: #dc3545;
            --warning: #ffc107;
            --success: #28a745;
            --info: #17a2b8;
            --bg-dark: #0d1f02;
            --bg-light: #f8f9fa;
            --text-light: #ffffff;
            --text-dark: #333333;
            --border: #dee2e6;
            --shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, var(--primary) 50%, #2d5a06 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--text-dark);
        }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 30px 40px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 2em;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-actions { display: flex; gap: 15px; align-items: center; }
        
        .version-badge {
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .card {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--accent);
        }
        
        .card-header h2 {
            color: var(--primary);
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #2d8a2a);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(67, 210, 64, 0.4);
        }
        
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #c82333; }
        
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        .btn-info { background: var(--info); color: white; }
        
        .btn-sm { padding: 8px 16px; font-size: 0.9em; }
        
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }
        
        /* Status Grid - Projekt-Übersicht */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .status-item {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid var(--accent);
        }
        
        .status-item .label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 8px;
        }
        
        .status-item .value {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--primary);
        }
        
        .status-item .value.success { color: var(--success); }
        .status-item .value.warning { color: var(--warning); }
        .status-item .value.danger { color: var(--danger); }
        .status-item .value.large { font-size: 1.8em; }
        
        /* Storage Locations */
        .storage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .storage-card {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid var(--info);
        }
        
        .storage-card.onedrive { border-left-color: #0078d4; }
        .storage-card h4 { color: var(--primary); margin-bottom: 10px; }
        .storage-card .path { font-family: monospace; font-size: 0.85em; color: #666; word-break: break-all; }
        .storage-status { margin-top: 10px; font-weight: 600; }
        .storage-status.ok { color: var(--success); }
        .storage-status.error { color: var(--danger); }
        
        /* Progress */
        .progress-container { display: none; margin-top: 20px; }
        .progress-container.active { display: block; }
        
        .progress-bar {
            height: 30px;
            background: var(--bg-light);
            border-radius: 15px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent), #2d8a2a);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-fill.indeterminate {
            width: 100%;
            background: linear-gradient(90deg, var(--accent), #2d8a2a, var(--accent));
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .progress-text {
            text-align: center;
            margin-top: 10px;
            font-weight: 600;
            color: var(--primary);
        }
        
        /* Result Box */
        .result-box {
            display: none;
            margin-top: 20px;
            padding: 20px;
            border-radius: 12px;
        }
        
        .result-box.show { display: block; animation: fadeIn 0.3s ease; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .result-box.success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
            border: 2px solid var(--success);
        }
        
        .result-box.error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
            border: 2px solid var(--danger);
        }
        
        .result-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .result-stat {
            text-align: center;
            padding: 15px;
            background: rgba(255,255,255,0.7);
            border-radius: 8px;
        }
        
        .result-stat .value { font-size: 1.3em; font-weight: bold; color: var(--primary); }
        .result-stat .label { font-size: 0.85em; color: #666; }
        
        .backup-locations {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255,255,255,0.5);
            border-radius: 10px;
        }
        
        .backup-locations h4 { margin-bottom: 10px; color: var(--primary); }
        
        .location-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .location-item:last-child { border-bottom: none; }
        .location-item .icon { font-size: 1.2em; }
        .location-item .path { font-family: monospace; font-size: 0.85em; flex: 1; }
        .location-item .status { font-weight: 600; }
        
        /* Backup Liste */
        .backup-section { margin-bottom: 25px; }
        .backup-section h3 { color: var(--primary); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        
        .backup-list {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .backup-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s;
        }
        
        .backup-item:last-child { border-bottom: none; }
        .backup-item:hover { background: var(--bg-light); }
        
        .backup-item.latest {
            background: linear-gradient(90deg, rgba(67, 210, 64, 0.1), transparent);
            border-left: 4px solid var(--accent);
        }
        
        .backup-info { flex: 1; }
        .backup-name { font-weight: 600; color: var(--primary); margin-bottom: 4px; }
        .backup-meta { font-size: 0.85em; color: #666; display: flex; gap: 20px; }
        .backup-actions { display: flex; gap: 10px; }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state .emoji { font-size: 3em; margin-bottom: 15px; }
        
        /* Login */
        .login-container { max-width: 400px; margin: 100px auto; }
        
        .login-form {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
            text-align: center;
        }
        
        .login-form h2 { color: var(--primary); margin-bottom: 10px; }
        .login-form p { color: #666; margin-bottom: 30px; }
        
        .login-form input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 1em;
            margin-bottom: 20px;
        }
        
        .login-form input:focus { outline: none; border-color: var(--accent); }
        
        .login-form .error {
            color: var(--danger);
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 8px;
        }
        
        .nav-links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .nav-links a:hover { color: var(--accent); }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 20px; text-align: center; }
            .backup-item { flex-direction: column; align-items: flex-start; gap: 15px; }
            .backup-actions { width: 100%; }
        }
        
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<?php if (!$authenticated): ?>
    <div class="login-container">
        <form class="login-form" method="POST">
            <h2>🔐 Backup Manager</h2>
            <p>Bitte Admin-Passwort eingeben</p>
            <?php if (isset($loginError)): ?>
                <div class="error"><?= htmlspecialchars($loginError) ?></div>
            <?php endif; ?>
            <input type="password" name="password" placeholder="Admin-Passwort" autofocus required>
            <button type="submit" class="btn btn-primary" style="width: 100%;">🔓 Anmelden</button>
        </form>
    </div>

<?php else: ?>
    <div class="container">
        <div class="header">
            <h1>💾 Backup Manager</h1>
            <div class="header-actions">
                <span class="version-badge">v2.2</span>
                <a href="backup_config_admin.php" class="btn btn-info btn-sm">⚙️ Pfade konfigurieren</a>
                <a href="?logout=1" class="btn btn-secondary btn-sm">🚪 Abmelden</a>
            </div>
        </div>
        
        <!-- Neues Backup erstellen -->
        <div class="card">
            <div class="card-header">
                <h2>📦 Vollbackup erstellen</h2>
            </div>
            
            <!-- Projekt-Größe Übersicht -->
            <div class="status-grid" id="systemStatus">
                <div class="status-item">
                    <div class="label">📁 Projekt gesamt</div>
                    <div class="value large" id="status-project">--</div>
                </div>
                <div class="status-item">
                    <div class="label">🗄️ Datenbanken</div>
                    <div class="value" id="status-databases">--</div>
                </div>
                <div class="status-item">
                    <div class="label">📄 Quellcode</div>
                    <div class="value" id="status-source">--</div>
                </div>
                <div class="status-item">
                    <div class="label">💾 Freier Speicher</div>
                    <div class="value" id="status-space">--</div>
                </div>
            </div>
            
            <!-- Speicherorte -->
            <div class="storage-grid">
                <div class="storage-card">
                    <h4>📂 Lokales Backup</h4>
                    <div class="path"><?= BACKUP_PATH_LOCAL ?></div>
                    <div class="storage-status ok" id="storage-local">✅ Bereit</div>
                </div>
                <div class="storage-card onedrive">
                    <h4>☁️ OneDrive Fallback</h4>
                    <div class="path"><?= BACKUP_PATH_ONEDRIVE ?></div>
                    <div class="storage-status" id="storage-onedrive">Prüfe...</div>
                </div>
            </div>
            
            <button class="btn btn-primary" id="createBackupBtn" onclick="createBackup()">
                <span id="createBtnIcon">📦</span>
                <span id="createBtnText">Vollbackup erstellen</span>
            </button>
            
            <div class="progress-container" id="progressContainer">
                <div class="progress-bar">
                    <div class="progress-fill indeterminate" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">Backup wird erstellt... Dies kann einige Minuten dauern.</div>
            </div>
            
            <div class="result-box" id="resultBox">
                <strong id="resultTitle"></strong>
                <div class="result-stats" id="resultStats"></div>
                <div class="backup-locations" id="backupLocations"></div>
            </div>
        </div>
        
        <!-- Vorhandene Backups -->
        <div class="card">
            <div class="card-header">
                <h2>📁 Vorhandene Backups</h2>
                <button class="btn btn-secondary btn-sm" onclick="loadBackups()">🔄 Aktualisieren</button>
            </div>
            
            <!-- Lokale Backups -->
            <div class="backup-section">
                <h3>📂 Lokal</h3>
                <div id="backupListLocal">
                    <div class="empty-state"><div class="emoji">⏳</div><p>Lade...</p></div>
                </div>
            </div>
            
            <!-- OneDrive Backups -->
            <div class="backup-section">
                <h3>☁️ OneDrive</h3>
                <div id="backupListOnedrive">
                    <div class="empty-state"><div class="emoji">⏳</div><p>Lade...</p></div>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="card">
            <div class="nav-links">
                <a href="admin_v4.php">← Admin Dashboard</a>
                <a href="index.php">🏠 Startseite</a>
                <a href="adaptive_learning.php">📚 Lernplattform</a>
                <a href="db_config.php">🗄️ DB-Status</a>
            </div>
        </div>
    </div>
    
    <script>
        async function loadSystemStatus() {
            try {
                const response = await fetch('?action=status');
                const status = await response.json();
                
                // Projekt-Größe
                document.getElementById('status-project').textContent = formatBytes(status.project.total_size);
                document.getElementById('status-databases').textContent = formatBytes(status.project.db_size);
                document.getElementById('status-source').textContent = formatBytes(status.project.source_size);
                document.getElementById('status-space').textContent = formatBytes(status.storage.local.free_space);
                
                // OneDrive Status
                const oneDriveStatus = document.getElementById('storage-onedrive');
                if (status.storage.onedrive.exists) {
                    oneDriveStatus.textContent = '✅ Verfügbar';
                    oneDriveStatus.className = 'storage-status ok';
                } else {
                    oneDriveStatus.textContent = '⚠️ Nicht erreichbar';
                    oneDriveStatus.className = 'storage-status error';
                }
            } catch (error) {
                console.error('Status laden fehlgeschlagen:', error);
            }
        }
        
        async function loadBackups() {
            try {
                const response = await fetch('?action=list');
                const backups = await response.json();
                
                // Lokale Backups
                renderBackupList('backupListLocal', backups.local, 'local');
                
                // OneDrive Backups
                renderBackupList('backupListOnedrive', backups.onedrive, 'onedrive');
            } catch (error) {
                console.error('Backups laden fehlgeschlagen:', error);
            }
        }
        
        function renderBackupList(containerId, backups, location) {
            const container = document.getElementById(containerId);
            
            if (!backups || backups.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="emoji">📭</div>
                        <p>Keine Backups vorhanden</p>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="backup-list">';
            
            backups.forEach((backup, index) => {
                const isLatest = index === 0;
                const date = new Date(backup.created * 1000);
                
                html += `
                    <div class="backup-item ${isLatest ? 'latest' : ''}">
                        <div class="backup-info">
                            <div class="backup-name">${isLatest ? '⭐ ' : ''}${backup.filename}</div>
                            <div class="backup-meta">
                                <span>📅 ${date.toLocaleString('de-DE')}</span>
                                <span>📦 ${formatBytes(backup.size)}</span>
                            </div>
                        </div>
                        <div class="backup-actions">
                            ${location === 'local' ? `
                                <a href="?action=download&file=${encodeURIComponent(backup.filename)}" class="btn btn-primary btn-sm">⬇️ Download</a>
                            ` : ''}
                            <button class="btn btn-danger btn-sm" onclick="deleteBackup('${backup.filename}', '${location}')">🗑️ Löschen</button>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }
        
        async function createBackup() {
            const btn = document.getElementById('createBackupBtn');
            const btnIcon = document.getElementById('createBtnIcon');
            const btnText = document.getElementById('createBtnText');
            const progressContainer = document.getElementById('progressContainer');
            const resultBox = document.getElementById('resultBox');
            
            btn.disabled = true;
            btnIcon.innerHTML = '<span class="spin">⏳</span>';
            btnText.textContent = 'Erstelle Vollbackup...';
            progressContainer.classList.add('active');
            resultBox.className = 'result-box';
            
            try {
                const response = await fetch('?action=create');
                const text = await response.text();
                
                // Debug: Zeige Rohtext bei Problemen
                console.log('Server Antwort:', text);
                
                let result;
                try {
                    result = JSON.parse(text);
                } catch (parseError) {
                    throw new Error('Ungültige Server-Antwort: ' + text.substring(0, 200));
                }
                
                progressContainer.classList.remove('active');
                
                if (result.success) {
                    resultBox.className = 'result-box success show';
                    document.getElementById('resultTitle').textContent = '✅ ' + result.message;
                    
                    document.getElementById('resultStats').innerHTML = `
                        <div class="result-stat">
                            <div class="value">${result.total_files}</div>
                            <div class="label">Dateien</div>
                        </div>
                        <div class="result-stat">
                            <div class="value">${formatBytes(result.total_size)}</div>
                            <div class="label">Größe</div>
                        </div>
                        <div class="result-stat">
                            <div class="value">${result.duration}s</div>
                            <div class="label">Dauer</div>
                        </div>
                    `;
                    
                    // Speicherorte anzeigen
                    let locationsHtml = '<h4>Gespeichert in:</h4>';
                    if (result.local) {
                        locationsHtml += `<div class="location-item"><span class="icon">📂</span><span class="path">${result.local.path}</span><span class="status" style="color: var(--success);">✅ ${formatBytes(result.local.size)}</span></div>`;
                    }
                    if (result.onedrive) {
                        locationsHtml += `<div class="location-item"><span class="icon">☁️</span><span class="path">${result.onedrive.path}</span><span class="status" style="color: var(--success);">✅ ${formatBytes(result.onedrive.size)}</span></div>`;
                    } else {
                        locationsHtml += `<div class="location-item"><span class="icon">☁️</span><span class="path">OneDrive</span><span class="status" style="color: var(--danger);">⚠️ Nicht verfügbar</span></div>`;
                    }
                    document.getElementById('backupLocations').innerHTML = locationsHtml;
                    
                    loadBackups();
                } else {
                    resultBox.className = 'result-box error show';
                    document.getElementById('resultTitle').textContent = '❌ ' + (result.message || 'Backup fehlgeschlagen');
                    document.getElementById('resultStats').innerHTML = result.file ? `<p>Datei: ${result.file}:${result.line}</p>` : '';
                    document.getElementById('backupLocations').innerHTML = '';
                }
            } catch (error) {
                progressContainer.classList.remove('active');
                resultBox.className = 'result-box error show';
                document.getElementById('resultTitle').textContent = '❌ ' + error.message;
                document.getElementById('resultStats').innerHTML = '<p style="word-break: break-all; font-size: 0.8em;">Prüfe die Browser-Konsole (F12) für Details</p>';
                document.getElementById('backupLocations').innerHTML = '';
                console.error('Backup Fehler:', error);
            }
            
            btn.disabled = false;
            btnIcon.textContent = '📦';
            btnText.textContent = 'Vollbackup erstellen';
        }
        
        async function deleteBackup(filename, location) {
            if (!confirm(`Backup "${filename}" wirklich löschen?`)) return;
            
            try {
                const response = await fetch(`?action=delete&file=${encodeURIComponent(filename)}&location=${location}`);
                const result = await response.json();
                
                if (result.success) {
                    loadBackups();
                } else {
                    alert('Löschen fehlgeschlagen!');
                }
            } catch (error) {
                alert('Fehler: ' + error.message);
            }
        }
        
        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            loadSystemStatus();
            loadBackups();
        });
    </script>

<?php endif; ?>

</body>
</html>
