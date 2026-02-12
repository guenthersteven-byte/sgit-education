<?php
/**
 * ============================================================================
 * sgiT Education - Hausaufgaben Hauptseite
 * ============================================================================
 *
 * Upload-Formular + Galerie + Filter + Historie
 * Mobile-first, Dark Theme
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 12.02.2026
 * ============================================================================
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/version.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../wallet/SessionManager.php';
require_once __DIR__ . '/../wallet/WalletManager.php';
require_once __DIR__ . '/HausaufgabenManager.php';

// Login erforderlich
SessionManager::requireLogin('/Education/wallet/login.php?redirect=' . urlencode('/Education/hausaufgaben/'));

$childId = SessionManager::getChildId();
$child = SessionManager::getChild();

// Wallet-Daten laden
$wallet = new WalletManager();
$childWallet = $wallet->getChildWallet($childId);
$balance = $childWallet['balance_sats'] ?? 0;
$currentGrade = $childWallet['current_grade'] ?? null;
$currentSchoolYear = $childWallet['current_school_year'] ?? null;

// Aktuelles Schuljahr berechnen (Default)
$month = (int) date('n');
$year = (int) date('Y');
if ($month >= 8) {
    $defaultSchoolYear = $year . '/' . ($year + 1);
} else {
    $defaultSchoolYear = ($year - 1) . '/' . $year;
}

$csrfToken = generate_csrf_token();

// Verfuegbare Schuljahre fuer Dropdown
$schoolYears = [];
for ($y = 2024; $y <= $year + 1; $y++) {
    $schoolYears[] = $y . '/' . ($y + 1);
}
$schoolYears = array_reverse($schoolYears);

$subjects = HausaufgabenManager::SUBJECTS;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Hausaufgaben - sgiT Education v<?php echo SGIT_VERSION; ?></title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/hausaufgaben.css">
</head>
<body class="hw-page">
    <div class="hw-container">

        <!-- ============================================================ -->
        <!-- HEADER -->
        <!-- ============================================================ -->
        <div class="hw-header">
            <div class="hw-header-left">
                <a href="../adaptive_learning.php" class="hw-back-btn" title="Zurueck">&larr;</a>
                <h1 class="hw-title">Hausaufgaben</h1>
            </div>
            <div class="hw-user-badge">
                <span class="avatar"><?php echo htmlspecialchars($child['avatar'] ?? ''); ?></span>
                <span class="name"><?php echo htmlspecialchars($child['name'] ?? ''); ?></span>
                <span class="sats" id="hw-balance"><?php echo $balance; ?></span>
                <span>Sats</span>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- SCHULINFO BANNER -->
        <!-- ============================================================ -->
        <div class="hw-school-banner">
            <h3>Deine Schulinfo</h3>

            <?php if ($currentGrade && $currentSchoolYear): ?>
            <!-- Schulinfo vorhanden: Anzeige -->
            <div class="hw-school-current" id="hw-school-display">
                <span class="info-chip grade-chip"><?php echo (int)$currentGrade; ?>. Klasse</span>
                <span class="info-chip year-chip"><?php echo htmlspecialchars($currentSchoolYear); ?></span>
                <button class="edit-btn" id="hw-school-edit">Aendern</button>
            </div>
            <!-- Bearbeitungsformular (versteckt) -->
            <div class="hw-school-form" id="hw-school-form" style="display:none;">
            <?php else: ?>
            <!-- Noch keine Schulinfo: Formular direkt anzeigen -->
            <div class="hw-school-current" id="hw-school-display" style="display:none;"></div>
            <div class="hw-school-form" id="hw-school-form">
            <?php endif; ?>
                <div class="field">
                    <label for="hw-school-grade">Klassenstufe</label>
                    <select id="hw-school-grade">
                        <option value="">Waehlen...</option>
                        <?php for ($i = 1; $i <= 13; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($currentGrade == $i) ? 'selected' : ''; ?>><?php echo $i; ?>. Klasse</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="hw-school-year-input">Schuljahr</label>
                    <select id="hw-school-year-input">
                        <option value="">Waehlen...</option>
                        <?php foreach ($schoolYears as $sy): ?>
                        <option value="<?php echo $sy; ?>" <?php echo (($currentSchoolYear ?? $defaultSchoolYear) === $sy) ? 'selected' : ''; ?>><?php echo $sy; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="hw-school-save" id="hw-school-save">Speichern</button>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- UPLOAD CARD -->
        <!-- ============================================================ -->
        <div class="hw-upload-card">
            <h3>Hausaufgabe hochladen</h3>

            <!-- Foto-Buttons -->
            <div class="hw-photo-buttons">
                <label class="hw-photo-btn">
                    <span class="icon">📸</span>
                    <span>Kamera</span>
                    <input type="file" id="hw-camera-input" accept="image/*" capture="environment">
                </label>
                <label class="hw-photo-btn">
                    <span class="icon">🖼️</span>
                    <span>Galerie</span>
                    <input type="file" id="hw-gallery-input" accept="image/*">
                </label>
            </div>

            <!-- Vorschau -->
            <div class="hw-preview-container" id="hw-preview-container">
                <img class="hw-preview-img" id="hw-preview-img" alt="Vorschau">
                <button class="hw-preview-remove" id="hw-preview-remove" title="Entfernen">&times;</button>
            </div>

            <!-- Formularfelder -->
            <div class="hw-form-fields">
                <div class="hw-form-row">
                    <div class="field">
                        <label for="hw-subject">Fach *</label>
                        <select id="hw-subject" required>
                            <option value="">Fach waehlen...</option>
                            <?php foreach ($subjects as $key => $name): ?>
                            <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="hw-form-row">
                    <div class="field">
                        <label for="hw-grade-level">Klasse *</label>
                        <select id="hw-grade-level" required>
                            <option value="">Klasse...</option>
                            <?php for ($i = 1; $i <= 13; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($currentGrade == $i) ? 'selected' : ''; ?>><?php echo $i; ?>.</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="hw-school-year">Schuljahr *</label>
                        <select id="hw-school-year" required>
                            <?php foreach ($schoolYears as $sy): ?>
                            <option value="<?php echo $sy; ?>" <?php echo (($currentSchoolYear ?? $defaultSchoolYear) === $sy) ? 'selected' : ''; ?>><?php echo $sy; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="hw-description">Notiz (optional)</label>
                    <textarea id="hw-description" rows="2" placeholder="z.B. Seite 42, Aufgabe 3..."></textarea>
                </div>
            </div>

            <!-- Upload Button -->
            <button class="hw-upload-btn" id="hw-upload-btn" disabled>
                <span>Hochladen</span>
                <span class="sats-badge">+15 Sats</span>
            </button>

            <!-- Progress -->
            <div class="hw-upload-progress" id="hw-upload-progress">
                <div class="hw-progress-bar">
                    <div class="hw-progress-fill" id="hw-progress-fill"></div>
                </div>
                <div class="hw-progress-text" id="hw-progress-text">0%</div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- STATS -->
        <!-- ============================================================ -->
        <div class="hw-stats">
            <div class="hw-stat-item">
                <div class="hw-stat-value" id="hw-stat-total">-</div>
                <div class="hw-stat-label">Gesamt</div>
            </div>
            <div class="hw-stat-item">
                <div class="hw-stat-value" id="hw-stat-month">-</div>
                <div class="hw-stat-label">Diesen Monat</div>
            </div>
            <div class="hw-stat-item">
                <div class="hw-stat-value" id="hw-stat-subjects">-</div>
                <div class="hw-stat-label">Faecher</div>
            </div>
            <div class="hw-stat-item">
                <div class="hw-stat-value" id="hw-stat-size">-</div>
                <div class="hw-stat-label">Speicher</div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- FILTER BAR -->
        <!-- ============================================================ -->
        <div class="hw-filters">
            <button class="hw-filter-pill active" data-subject="">Alle</button>
            <?php
            $popularSubjects = ['mathematik', 'deutsch', 'englisch', 'sachkunde', 'biologie', 'physik'];
            foreach ($popularSubjects as $subKey):
            ?>
            <button class="hw-filter-pill" data-subject="<?php echo $subKey; ?>"><?php echo htmlspecialchars($subjects[$subKey]); ?></button>
            <?php endforeach; ?>

            <select class="hw-filter-select" id="hw-filter-year">
                <option value="">Alle Schuljahre</option>
                <?php foreach ($schoolYears as $sy): ?>
                <option value="<?php echo $sy; ?>"><?php echo $sy; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ============================================================ -->
        <!-- GALLERY -->
        <!-- ============================================================ -->
        <div class="hw-gallery" id="hw-gallery">
            <div class="hw-empty" style="grid-column: 1/-1;">
                <div class="icon">📝</div>
                <p>Lade Hausaufgaben...</p>
            </div>
        </div>

    </div><!-- /hw-container -->

    <!-- ================================================================ -->
    <!-- DETAIL MODAL -->
    <!-- ================================================================ -->
    <div class="hw-modal-overlay" id="hw-modal-overlay">
        <div class="hw-modal">
            <div class="hw-modal-header">
                <h3 id="hw-modal-subject">-</h3>
                <button class="hw-modal-close" id="hw-modal-close">&times;</button>
            </div>
            <img class="hw-modal-img" id="hw-modal-image" alt="Hausaufgabe">
            <div class="hw-modal-meta" id="hw-modal-meta"></div>
            <div class="hw-modal-ocr" id="hw-modal-ocr" style="display:none;">
                <h4>Erkannter Text <span class="confidence"></span></h4>
                <pre></pre>
            </div>
            <div class="hw-modal-actions">
                <button class="hw-modal-delete" id="hw-modal-delete-btn">Loeschen</button>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- SATS POPUP -->
    <!-- ================================================================ -->
    <div class="hw-sats-popup" id="hw-sats-popup">
        <div class="amount">+15</div>
        <div class="label">Sats verdient!</div>
        <div class="message"></div>
    </div>

    <!-- ================================================================ -->
    <!-- JAVASCRIPT -->
    <!-- ================================================================ -->
    <script src="../assets/js/hausaufgaben.js"></script>
    <script>
        // Enable upload button when file is selected
        const cameraInput = document.getElementById('hw-camera-input');
        const galleryInput = document.getElementById('hw-gallery-input');
        const uploadBtn = document.getElementById('hw-upload-btn');

        function enableUpload() {
            uploadBtn.disabled = false;
        }
        if (cameraInput) cameraInput.addEventListener('change', enableUpload);
        if (galleryInput) galleryInput.addEventListener('change', enableUpload);

        document.getElementById('hw-preview-remove').addEventListener('click', function() {
            uploadBtn.disabled = true;
        });

        // Init app
        HausaufgabenApp.init({
            csrfToken: '<?php echo $csrfToken; ?>',
            baseUrl: '../'
        });
    </script>
</body>
</html>
