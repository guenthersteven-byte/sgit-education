<?php
/**
 * ============================================================================
 * sgiT Education - Foxy Integration Include v1.4
 * ============================================================================
 * 
 * Einfache Integration in beliebige PHP-Seiten:
 * <?php include 'clippy/include.php'; ?>
 * 
 * FIXES v1.4 (06.12.2025):
 * - BUG-025 FIX v2: Document Root Erkennung statt Port-Check
 *   (Docker intern Port 80, nicht 8080!)
 * 
 * FIXES v1.3 (06.12.2025):
 * - BUG-025 FIX: Dynamischer Pfad für Docker und XAMPP
 * - Docker verwendet /clippy, XAMPP verwendet /Education/clippy
 * 
 * FIXES v1.2:
 * - Modul wird korrekt aus Session UND lokaler Variable gelesen
 * - Username wird korrekt übergeben
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.4
 * @date 06.12.2025
 * ============================================================================
 */

// Session starten falls noch nicht geschehen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kontext ermitteln - in dieser Reihenfolge prüfen:
// 1. Lokale Variable $currentModule (von adaptive_learning.php)
// 2. Session-Variable
// 3. null (kein Modul aktiv)

$foxyAge = $_SESSION['user_age'] ?? 10;

// Modul: Erst lokale Variable, dann Session
$foxyModule = null;
if (isset($currentModule) && !empty($currentModule)) {
    $foxyModule = $currentModule;
} elseif (isset($_SESSION['current_module']) && !empty($_SESSION['current_module'])) {
    $foxyModule = $_SESSION['current_module'];
}

// Username: Aus Session holen
$foxyUserName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? null;

// ============================================================================
// BUG-025 FIX v2: Dynamischer Base-Pfad für Docker vs XAMPP
// ============================================================================
// Docker: Document Root ist /var/www/html (clippy liegt direkt drin)
// XAMPP: Document Root ist C:/xampp/htdocs (clippy liegt in /Education/clippy)
//
// Erkennung: In Docker ist $_SERVER['DOCUMENT_ROOT'] = '/var/www/html'
//            In XAMPP ist $_SERVER['DOCUMENT_ROOT'] = 'C:/xampp/htdocs'
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
$isDocker = (strpos($documentRoot, '/var/www/html') !== false);
$foxyBasePath = $isDocker ? '/clippy' : '/Education/clippy';
?>
<!-- ============================================================================
     Foxy Lernassistent v1.2
     ============================================================================ -->
<link rel="stylesheet" href="<?= $foxyBasePath ?>/clippy.css">
<script>
    // Foxy Kontext - wird beim Laden gesetzt
    window.userAge = <?= json_encode((int)$foxyAge) ?>;
    window.currentModule = <?= json_encode($foxyModule) ?>;
    window.userName = <?= json_encode($foxyUserName) ?>;
    
    // Debug-Log
    console.log('🦊 Foxy Kontext:', {
        age: window.userAge,
        module: window.currentModule,
        userName: window.userName
    });
</script>
<script src="<?= $foxyBasePath ?>/clippy.js"></script>
<!-- /Foxy -->
