<?php
/**
 * ============================================================================
 * sgiT Education - Generator Header & Navigation
 * ============================================================================
 * 
 * Gemeinsamer Header für alle Generator/Import Seiten
 * Einheitliches CI wie admin_v4.php
 * 
 * Usage:
 *   $currentPage = 'ai_generator'; // ai_generator|csv_generator|csv_import
 *   $pageTitle = 'AI Generator';
 *   require_once __DIR__ . '/generator_header.php';
 * 
 * @version 1.0
 * @date 08.12.2025
 * ============================================================================
 */

// Zentrale Versionsverwaltung laden falls nicht schon geladen
if (!defined('SGIT_VERSION')) {
    require_once __DIR__ . '/version.php';
}

// Admin-Check
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: /admin_v4.php');
    exit;
}

// Default-Werte
$currentPage = $currentPage ?? 'ai_generator';
$pageTitle = $pageTitle ?? 'Generator';

// Navigation Items
$navItems = [
    'ai_generator' => [
        'icon' => '🤖',
        'title' => 'AI Generator',
        'url' => '/windows_ai_generator.php',
        'desc' => 'Direkt in DB'
    ],
    'auto_generator' => [
        'icon' => '⚡',
        'title' => 'Auto-Generator',
        'url' => '/auto_generator.php',
        'desc' => 'Zeitgesteuert'
    ],
    'csv_generator' => [
        'icon' => '📝',
        'title' => 'CSV Generator', 
        'url' => '/questions/generate_module_csv.php',
        'desc' => 'AI → CSV'
    ],
    'csv_import' => [
        'icon' => '📥',
        'title' => 'CSV Import',
        'url' => '/batch_import.php',
        'desc' => 'CSV → DB'
    ],
    'db_manager' => [
        'icon' => '🗄️',
        'title' => 'DB Manager',
        'url' => '/admin_questions.php',
        'desc' => 'Fragen verwalten'
    ]
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - sgiT Education</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #1A3503;
            --primary-light: #2d5a06;
            --accent: #43D240;
            --accent-hover: #35B035;
            --orange: #E86F2C;
            --bitcoin: #F7931A;
            --danger: #e74c3c;
            --bg: #f5f7fa;
            --card: #ffffff;
            --text: #333;
            --text-muted: #666;
            --border: #e0e0e0;
        }
        
        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            background: var(--bg); 
            min-height: 100vh;
            color: var(--text);
        }
        
        /* ============================================
           HEADER
           ============================================ */
        .gen-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .gen-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .gen-logo {
            width: 42px;
            height: 42px;
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .gen-brand h1 {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .gen-brand h1 small {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-left: 8px;
            font-weight: normal;
        }
        
        .gen-header-nav {
            display: flex;
            gap: 8px;
        }
        
        .gen-header-nav a {
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .gen-nav-admin { background: rgba(255,255,255,0.15); color: white; }
        .gen-nav-admin:hover { background: rgba(255,255,255,0.25); }
        .gen-nav-stats { background: var(--accent); color: white; }
        .gen-nav-stats:hover { background: var(--accent-hover); }
        
        /* ============================================
           TAB NAVIGATION
           ============================================ */
        .gen-tabs {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 0 25px;
            display: flex;
            gap: 5px;
            overflow-x: auto;
        }
        
        .gen-tab {
            padding: 15px 20px;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .gen-tab:hover {
            color: var(--primary);
            background: rgba(67, 210, 64, 0.05);
        }
        
        .gen-tab.active {
            color: var(--primary);
            border-bottom-color: var(--accent);
            background: rgba(67, 210, 64, 0.08);
        }
        
        .gen-tab-icon {
            font-size: 1.1rem;
        }
        
        .gen-tab-desc {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-left: 4px;
        }
        
        .gen-tab.active .gen-tab-desc {
            color: var(--accent);
        }
        
        /* ============================================
           CONTAINER
           ============================================ */
        .gen-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 25px;
        }
        
        /* ============================================
           CARDS & COMPONENTS
           ============================================ */
        .gen-card {
            background: var(--card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        
        .gen-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        
        .gen-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .gen-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .gen-badge-success { background: #d4edda; color: #155724; }
        .gen-badge-warning { background: #fff3cd; color: #856404; }
        .gen-badge-danger { background: #f8d7da; color: #721c24; }
        .gen-badge-info { background: #d1ecf1; color: #0c5460; }
        .gen-badge-primary { background: var(--accent); color: white; }
        
        /* ============================================
           BUTTONS
           ============================================ */
        .gen-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .gen-btn-primary {
            background: var(--accent);
            color: white;
        }
        .gen-btn-primary:hover { background: var(--accent-hover); }
        
        .gen-btn-secondary {
            background: var(--bg);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .gen-btn-secondary:hover { background: #e9ecef; }
        
        .gen-btn-danger {
            background: var(--danger);
            color: white;
        }
        .gen-btn-danger:hover { background: #c0392b; }
        
        .gen-btn-orange {
            background: var(--orange);
            color: white;
        }
        .gen-btn-orange:hover { background: #d45a1a; }
        
        /* ============================================
           FORMS
           ============================================ */
        .gen-input, .gen-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }
        
        .gen-input:focus, .gen-select:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        .gen-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text);
        }
        
        .gen-form-group {
            margin-bottom: 15px;
        }
        
        /* ============================================
           GRID
           ============================================ */
        .gen-grid {
            display: grid;
            gap: 20px;
        }
        
        .gen-grid-2 { grid-template-columns: repeat(2, 1fr); }
        .gen-grid-3 { grid-template-columns: repeat(3, 1fr); }
        .gen-grid-4 { grid-template-columns: repeat(4, 1fr); }
        
        @media (max-width: 900px) {
            .gen-grid-2, .gen-grid-3, .gen-grid-4 { 
                grid-template-columns: 1fr; 
            }
        }
        
        /* ============================================
           ALERTS
           ============================================ */
        .gen-alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .gen-alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .gen-alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .gen-alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .gen-alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        /* ============================================
           PROGRESS
           ============================================ */
        .gen-progress {
            height: 8px;
            background: var(--bg);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .gen-progress-bar {
            height: 100%;
            background: var(--accent);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        /* ============================================
           FOOTER
           ============================================ */
        .gen-footer {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        
        .gen-footer a {
            color: var(--accent);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="gen-header">
        <div class="gen-brand">
            <div class="gen-logo">sgiT</div>
            <h1><?= htmlspecialchars($pageTitle) ?> <small>v<?= SGIT_VERSION ?></small></h1>
        </div>
        <nav class="gen-header-nav">
            <a href="/admin_v4.php" class="gen-nav-admin">🏠 Admin</a>
            <a href="/statistics.php" class="gen-nav-stats">📊 Statistik</a>
        </nav>
    </header>
    
    <!-- Tab Navigation -->
    <nav class="gen-tabs">
        <?php foreach ($navItems as $key => $item): ?>
        <a href="<?= $item['url'] ?>" class="gen-tab <?= $currentPage === $key ? 'active' : '' ?>">
            <span class="gen-tab-icon"><?= $item['icon'] ?></span>
            <?= $item['title'] ?>
            <span class="gen-tab-desc">(<?= $item['desc'] ?>)</span>
        </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- Main Container Start -->
    <main class="gen-container">
