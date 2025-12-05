<?php
session_start();

// Redirect wenn kein Benutzer eingeloggt
if (!isset($_SESSION['username'])) {
    header('Location: /Education/index.php');
    exit();
}

$username = $_SESSION['username'];
$userAge = $_SESSION['user_age'] ?? 7;
$totalScore = array_sum($_SESSION['scores'] ?? []);

// Logout Funktion
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: /Education/index.php');
    exit();
}

// Reset Scores
if (isset($_POST['reset_scores'])) {
    $_SESSION['scores'] = [
        'math' => 0,
        'reading' => 0,
        'science' => 0,
        'geography' => 0
    ];
    header('Location: profile.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?= $username ?> - sgiT Education</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <!-- Einheitlicher Header -->
    <header class="header">
        <h1>sgiT Education</h1>
        <div class="subtitle">Dein Profil</div>
    </header>

    <!-- Navigation -->
    <nav class="navigation">
        <a href="index.php" class="nav-button">🏠 Start</a>
        <a href="math.php" class="nav-button">🔢 Mathematik</a>
        <a href="reading.php" class="nav-button">📖 Lesen</a>
        <a href="science.php" class="nav-button">🔬 Wissenschaft</a>
        <a href="geography.php" class="nav-button">🌍 Erdkunde</a>
        <a href="profile.php" class="nav-button active">👤 <?= $username ?></a>
    </nav>

    <div class="container">
        <!-- User Info Box -->
        <div class="content-box">
            <div style="text-align: center;">
                <div style="font-size: 5em; margin: 20px 0;">
                    👤
                </div>
                <h2><?= $username ?></h2>
                <p style="font-size: 1.2em; color: var(--sgit-neon-green);">
                    <?= $userAge ?> Jahre alt
                </p>
            </div>
        </div>

        <!-- Total Score Display -->
        <div class="score-display">
            <h3>Gesamtpunktzahl</h3>
            <div class="score-value">
                🏆 <?= $totalScore ?> Punkte
            </div>
            <?php
            // Level basierend auf Gesamtpunktzahl
            $level = 'Anfänger';
            if ($totalScore >= 100) $level = 'Fortgeschritten';
            if ($totalScore >= 500) $level = 'Experte';
            if ($totalScore >= 1000) $level = 'Meister';
            if ($totalScore >= 2000) $level = 'Großmeister';
            ?>
            <div style="margin-top: 10px; font-size: 1.2em;">
                Level: <strong><?= $level ?></strong>
            </div>
        </div>

        <!-- Detailed Scores -->
        <div class="content-box">
            <h3>Deine Punkte pro Fach</h3>
            
            <div style="margin: 20px 0;">
                <!-- Mathematik -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 1.2em;">🔢 Mathematik</span>
                        <span style="font-weight: bold; color: var(--sgit-dark-green);">
                            <?= $_SESSION['scores']['math'] ?? 0 ?> Punkte
                        </span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= min(100, ($_SESSION['scores']['math'] ?? 0) / 10) ?>%">
                            <?= round(($_SESSION['scores']['math'] ?? 0) / 10) ?>%
                        </div>
                    </div>
                </div>
                
                <!-- Lesen -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 1.2em;">📖 Lesen</span>
                        <span style="font-weight: bold; color: var(--sgit-dark-green);">
                            <?= $_SESSION['scores']['reading'] ?? 0 ?> Punkte
                        </span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= min(100, ($_SESSION['scores']['reading'] ?? 0) / 10) ?>%">
                            <?= round(($_SESSION['scores']['reading'] ?? 0) / 10) ?>%
                        </div>
                    </div>
                </div>
                
                <!-- Wissenschaft -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 1.2em;">🔬 Wissenschaft</span>
                        <span style="font-weight: bold; color: var(--sgit-dark-green);">
                            <?= $_SESSION['scores']['science'] ?? 0 ?> Punkte
                        </span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= min(100, ($_SESSION['scores']['science'] ?? 0) / 10) ?>%">
                            <?= round(($_SESSION['scores']['science'] ?? 0) / 10) ?>%
                        </div>
                    </div>
                </div>
                
                <!-- Erdkunde -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 1.2em;">🌍 Erdkunde</span>
                        <span style="font-weight: bold; color: var(--sgit-dark-green);">
                            <?= $_SESSION['scores']['geography'] ?? 0 ?> Punkte
                        </span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= min(100, ($_SESSION['scores']['geography'] ?? 0) / 10) ?>%">
                            <?= round(($_SESSION['scores']['geography'] ?? 0) / 10) ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Achievements -->
        <div class="content-box">
            <h3>🏅 Deine Erfolge</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin-top: 20px;">
                <?php
                $achievements = [];
                
                // Punkte-basierte Erfolge
                if ($totalScore >= 10) $achievements[] = ['🌟', 'Erste Schritte', 'gold'];
                if ($totalScore >= 50) $achievements[] = ['⭐', 'Fleißiger Schüler', 'silver'];
                if ($totalScore >= 100) $achievements[] = ['🏆', 'Top-Lerner', 'gold'];
                if ($totalScore >= 500) $achievements[] = ['👑', 'Lern-König', 'purple'];
                if ($totalScore >= 1000) $achievements[] = ['💎', 'Diamant-Schüler', 'cyan'];
                
                // Fach-spezifische Erfolge
                if (($_SESSION['scores']['math'] ?? 0) >= 100) $achievements[] = ['🔢', 'Mathe-Genie', 'green'];
                if (($_SESSION['scores']['reading'] ?? 0) >= 100) $achievements[] = ['📚', 'Bücherwurm', 'blue'];
                if (($_SESSION['scores']['science'] ?? 0) >= 100) $achievements[] = ['🔬', 'Forscher', 'orange'];
                if (($_SESSION['scores']['geography'] ?? 0) >= 100) $achievements[] = ['🌍', 'Weltentdecker', 'red'];
                
                foreach ($achievements as $achievement) {
                    $color = $achievement[2] === 'gold' ? '#FFD700' : 
                            ($achievement[2] === 'silver' ? '#C0C0C0' : 
                            ($achievement[2] === 'purple' ? '#9C27B0' :
                            ($achievement[2] === 'cyan' ? '#00BCD4' :
                            ($achievement[2] === 'green' ? '#4CAF50' :
                            ($achievement[2] === 'blue' ? '#2196F3' :
                            ($achievement[2] === 'orange' ? '#FF9800' : '#F44336'))))));
                    
                    echo '<div style="background: ' . $color . '; color: white; padding: 15px 20px; border-radius: 15px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.2);">';
                    echo '<div style="font-size: 2em;">' . $achievement[0] . '</div>';
                    echo '<div style="font-size: 0.9em; margin-top: 5px;">' . $achievement[1] . '</div>';
                    echo '</div>';
                }
                
                if (empty($achievements)) {
                    echo '<p>Sammle Punkte um Erfolge freizuschalten!</p>';
                }
                ?>
            </div>
        </div>

        <!-- Statistics -->
        <div class="content-box">
            <h3>📊 Statistiken</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="text-align: center; padding: 15px; background: var(--sgit-bg-light); border-radius: 10px;">
                    <div style="font-size: 2em;">📅</div>
                    <div style="margin-top: 10px;">Dabei seit</div>
                    <div style="font-weight: bold;">Heute</div>
                </div>
                <div style="text-align: center; padding: 15px; background: var(--sgit-bg-light); border-radius: 10px;">
                    <div style="font-size: 2em;">🎯</div>
                    <div style="margin-top: 10px;">Beste Streak</div>
                    <div style="font-weight: bold;"><?= $_SESSION['best_streak'] ?? 0 ?> Tage</div>
                </div>
                <div style="text-align: center; padding: 15px; background: var(--sgit-bg-light); border-radius: 10px;">
                    <div style="font-size: 2em;">📈</div>
                    <div style="margin-top: 10px;">Level</div>
                    <div style="font-weight: bold;"><?= $level ?></div>
                </div>
                <div style="text-align: center; padding: 15px; background: var(--sgit-bg-light); border-radius: 10px;">
                    <div style="font-size: 2em;">🏅</div>
                    <div style="margin-top: 10px;">Erfolge</div>
                    <div style="font-weight: bold;"><?= count($achievements) ?></div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="content-box" style="text-align: center;">
            <h3>Aktionen</h3>
            
            <!-- Reset Scores Button -->
            <form method="POST" style="display: inline-block; margin: 10px;">
                <button type="submit" name="reset_scores" class="btn" 
                        style="background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);"
                        onclick="return confirm('Bist du sicher? Alle Punkte werden auf 0 zurückgesetzt!');">
                    🔄 Punkte zurücksetzen
                </button>
            </form>
            
            <!-- Logout Button -->
            <form method="POST" style="display: inline-block; margin: 10px;">
                <button type="submit" name="logout" class="btn" 
                        style="background: linear-gradient(135deg, #F44336 0%, #D32F2F 100%);">
                    🚪 Abmelden
                </button>
            </form>
        </div>

        <!-- Motivational Message -->
        <div class="content-box" style="text-align: center; background: linear-gradient(135deg, #E1F5FE 0%, #B3E5FC 100%);">
            <h3>💪 Motivation</h3>
            <?php
            $motivations = [
                "Du machst das großartig, $username!",
                "Bleib dran, du wirst immer besser!",
                "Lernen ist wie ein Abenteuer!",
                "Mit jedem Tag wirst du schlauer!",
                "Du bist ein echter Lern-Champion!",
                "Dein Fleiß wird sich auszahlen!",
                "Jeder Punkt bringt dich weiter!"
            ];
            echo '<p style="font-size: 1.3em; color: var(--sgit-dark-green); margin: 20px 0;">';
            echo $motivations[array_rand($motivations)];
            echo '</p>';
            ?>
        </div>
    </div>

    <script>
    // Animations
    document.addEventListener('DOMContentLoaded', function() {
        // Animate achievements on load
        const achievements = document.querySelectorAll('.content-box div[style*="background:"]');
        achievements.forEach((achievement, index) => {
            setTimeout(() => {
                achievement.style.animation = 'slideIn 0.5s ease forwards';
            }, index * 100);
        });
    });
    </script>
</body>
</html>