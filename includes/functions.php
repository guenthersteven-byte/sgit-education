<?php
/**
 * sgiT Education Platform - Hilfsfunktionen
 * 
 * Sammlung von wiederverwendbaren Hilfsfunktionen für die gesamte Platform.
 * 
 * @package sgiT_Education
 * @version 1.0.0
 * @author deStevie / sgiT Solution Engineering & IT Services
 */

/**
 * Rendert den HTML-Head mit allen notwendigen Meta-Tags und Styles
 * 
 * @param string $title Seitentitel
 * @param string $description Meta-Beschreibung
 */
function renderHead($title = '', $description = '') {
    $fullTitle = !empty($title) ? $title . ' - ' . BRAND_NAME : BRAND_NAME;
    $metaDescription = !empty($description) ? $description : BRAND_TAGLINE;
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="author" content="sgiT Solution Engineering & IT Services">
    <meta name="robots" content="noindex, nofollow"> <!-- Lernplattform nicht indexieren -->
    <title><?php echo htmlspecialchars($fullTitle); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo ASSETS_URL; ?>images/favicon.svg">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/kids.css">
    
    <!-- Preload wichtiger Ressourcen -->
    <link rel="preload" href="<?php echo ASSETS_URL; ?>css/main.css" as="style">
    
    <!-- CSS Variablen für Brand Colors -->
    <style>
        :root {
            --color-primary-dark: <?php echo COLOR_PRIMARY_DARK; ?>;
            --color-primary-bright: <?php echo COLOR_PRIMARY_BRIGHT; ?>;
            --color-background: <?php echo COLOR_BACKGROUND; ?>;
            --color-text: <?php echo COLOR_TEXT; ?>;
            --color-success: <?php echo COLOR_SUCCESS; ?>;
            --color-error: <?php echo COLOR_ERROR; ?>;
            --color-warning: <?php echo COLOR_WARNING; ?>;
            --color-info: <?php echo COLOR_INFO; ?>;
        }
    </style>
</head>
<body>
    <?php
}

/**
 * Rendert den Header mit Navigation
 * 
 * @param bool $showNav Ob Navigation angezeigt werden soll
 * @param bool $showUserInfo Ob Benutzerinfo angezeigt werden soll
 */
function renderHeader($showNav = true, $showUserInfo = true) {
    ?>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <a href="<?php echo BASE_URL; ?>" class="logo-link">
                        <div class="logo-cube"></div>
                        <div class="brand-text">
                            <h1><?php echo BRAND_NAME; ?></h1>
                            <p class="tagline"><?php echo BRAND_TAGLINE; ?></p>
                        </div>
                    </a>
                </div>
                
                <?php if ($showUserInfo && isUserLoggedIn()): ?>
                <div class="user-info">
                    <div class="user-welcome">
                        Hallo <strong><?php echo htmlspecialchars(getUserName()); ?></strong>! 👋
                    </div>
                    <div class="user-points">
                        <span class="points-label">Punkte:</span>
                        <span class="points-value"><?php echo getUserPoints(); ?></span>
                        <span class="points-icon">⭐</span>
                    </div>
                    <?php if (getCurrentStreak() > 0): ?>
                    <div class="user-streak">
                        <span class="streak-icon">🔥</span>
                        <span class="streak-value"><?php echo getCurrentStreak(); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($showNav && isUserLoggedIn()): ?>
            <nav class="main-nav">
                <a href="<?php echo BASE_URL; ?>" class="nav-link">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Start</span>
                </a>
                <a href="<?php echo BASE_URL; ?>mathe/" class="nav-link">
                    <span class="nav-icon">🔢</span>
                    <span class="nav-text">Mathe</span>
                </a>
                <a href="<?php echo BASE_URL; ?>?logout=1" class="nav-link nav-logout">
                    <span class="nav-icon">👋</span>
                    <span class="nav-text">Tschüss</span>
                </a>
            </nav>
            <?php endif; ?>
        </div>
    </header>
    <main class="main-content">
        <div class="container">
    <?php
}

/**
 * Rendert den Footer
 */
function renderFooter() {
    ?>
        </div>
    </main>
    <footer class="main-footer">
        <div class="container">
            <p class="footer-text">
                <?php echo BRAND_NAME; ?> &copy; <?php echo date('Y'); ?> 
                | <a href="https://sgit.space" target="_blank">sgiT Solution Engineering</a>
                | <?php echo BRAND_MOTTO; ?>
            </p>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="<?php echo ASSETS_URL; ?>js/main.js"></script>
</body>
</html>
    <?php
}

/**
 * Rendert eine Erfolgsmeldung
 * 
 * @param string $message Nachricht
 */
function renderSuccessMessage($message) {
    ?>
    <div class="message message-success">
        <span class="message-icon">✅</span>
        <span class="message-text"><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php
}

/**
 * Rendert eine Fehlermeldung
 * 
 * @param string $message Nachricht
 */
function renderErrorMessage($message) {
    ?>
    <div class="message message-error">
        <span class="message-icon">❌</span>
        <span class="message-text"><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php
}

/**
 * Rendert eine Info-Meldung
 * 
 * @param string $message Nachricht
 */
function renderInfoMessage($message) {
    ?>
    <div class="message message-info">
        <span class="message-icon">ℹ️</span>
        <span class="message-text"><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php
}

/**
 * Rendert eine Modul-Karte
 * 
 * @param string $title Titel
 * @param string $description Beschreibung
 * @param string $icon Icon
 * @param string $link Link-URL
 * @param string $color Akzentfarbe
 */
function renderModuleCard($title, $description, $icon, $link, $color = COLOR_PRIMARY_BRIGHT) {
    ?>
    <a href="<?php echo htmlspecialchars($link); ?>" class="module-card" style="--accent-color: <?php echo $color; ?>">
        <div class="module-icon"><?php echo $icon; ?></div>
        <h3 class="module-title"><?php echo htmlspecialchars($title); ?></h3>
        <p class="module-description"><?php echo htmlspecialchars($description); ?></p>
        <div class="module-arrow">→</div>
    </a>
    <?php
}

/**
 * Rendert einen Zurück-Button
 * 
 * @param string $url Zurück-URL
 * @param string $label Button-Text
 */
function renderBackButton($url, $label = 'Zurück') {
    ?>
    <a href="<?php echo htmlspecialchars($url); ?>" class="btn btn-back">
        <span class="btn-icon">←</span>
        <span class="btn-text"><?php echo htmlspecialchars($label); ?></span>
    </a>
    <?php
}

/**
 * Rendert einen Button
 * 
 * @param string $text Button-Text
 * @param string $type Button-Typ (primary, secondary, success, danger)
 * @param string $onclick Optional onclick Handler
 * @param bool $disabled Ob Button deaktiviert sein soll
 */
function renderButton($text, $type = 'primary', $onclick = '', $disabled = false) {
    $disabledAttr = $disabled ? 'disabled' : '';
    $onclickAttr = !empty($onclick) ? 'onclick="' . htmlspecialchars($onclick) . '"' : '';
    ?>
    <button class="btn btn-<?php echo $type; ?>" <?php echo $onclickAttr; ?> <?php echo $disabledAttr; ?>>
        <?php echo htmlspecialchars($text); ?>
    </button>
    <?php
}

/**
 * Rendert einen Fortschrittsbalken
 * 
 * @param int $current Aktueller Wert
 * @param int $total Gesamt-Wert
 * @param string $label Optional Label
 */
function renderProgressBar($current, $total, $label = '') {
    $percentage = $total > 0 ? round(($current / $total) * 100) : 0;
    ?>
    <div class="progress-container">
        <?php if (!empty($label)): ?>
        <div class="progress-label"><?php echo htmlspecialchars($label); ?></div>
        <?php endif; ?>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $percentage; ?>%">
                <span class="progress-text"><?php echo $current; ?> / <?php echo $total; ?></span>
            </div>
        </div>
        <div class="progress-percentage"><?php echo $percentage; ?>%</div>
    </div>
    <?php
}

/**
 * Rendert eine Level-Auswahl
 */
function renderLevelSelection() {
    $levels = getAgeLevels();
    ?>
    <div class="level-selection">
        <h3>Wie alt bist du?</h3>
        <div class="level-buttons">
            <?php foreach ($levels as $levelNum => $levelData): ?>
            <button class="level-btn" data-level="<?php echo $levelNum; ?>">
                <span class="level-age"><?php echo $levelData['age']; ?> Jahre</span>
                <span class="level-description"><?php echo $levelData['description']; ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Formatiert eine Zeitdauer in Minuten und Sekunden
 * 
 * @param int $seconds Sekunden
 * @return string Formatierte Zeit
 */
function formatDuration($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    
    if ($minutes > 0) {
        return sprintf('%d Min %d Sek', $minutes, $remainingSeconds);
    } else {
        return sprintf('%d Sekunden', $remainingSeconds);
    }
}

/**
 * Leitet auf eine andere Seite um
 * 
 * @param string $url Ziel-URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Prüft ob ein POST-Request vorliegt
 * 
 * @return bool True wenn POST, sonst false
 */
function isPostRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Gibt JSON-Response aus und beendet Skript
 * 
 * @param array $data Daten
 * @param int $statusCode HTTP Status Code
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Loggt einen Fehler (für Debugging)
 * 
 * @param string $message Fehlermeldung
 * @param array $context Zusätzlicher Kontext
 */
function logError($message, $context = []) {
    if (DEBUG_MODE) {
        error_log(sprintf(
            "[sgiT Education] %s | Context: %s",
            $message,
            json_encode($context)
        ));
    }
}

/**
 * Generiert eine zufällige Farbe aus dem Brand-Farbschema
 * 
 * @return string Hex-Farbcode
 */
function getRandomBrandColor() {
    $colors = [
        COLOR_PRIMARY_BRIGHT,
        COLOR_SUCCESS,
        COLOR_INFO,
        COLOR_WARNING
    ];
    return $colors[array_rand($colors)];
}

/**
 * Rendert ein Statistik-Widget
 * 
 * @param string $label Label
 * @param mixed $value Wert
 * @param string $icon Optional Icon
 * @param string $color Optional Farbe
 */
function renderStatWidget($label, $value, $icon = '', $color = COLOR_PRIMARY_BRIGHT) {
    ?>
    <div class="stat-widget" style="--widget-color: <?php echo $color; ?>">
        <?php if (!empty($icon)): ?>
        <div class="stat-icon"><?php echo $icon; ?></div>
        <?php endif; ?>
        <div class="stat-content">
            <div class="stat-value"><?php echo htmlspecialchars($value); ?></div>
            <div class="stat-label"><?php echo htmlspecialchars($label); ?></div>
        </div>
    </div>
    <?php
}

?>
