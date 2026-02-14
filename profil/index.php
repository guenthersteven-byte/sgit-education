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

// Level basierend auf Gesamtpunktzahl
$level = 'Anfaenger';
if ($totalScore >= 100) $level = 'Fortgeschritten';
if ($totalScore >= 500) $level = 'Experte';
if ($totalScore >= 1000) $level = 'Meister';
if ($totalScore >= 2000) $level = 'Grossmeister';

$achievements = [];
if ($totalScore >= 10) $achievements[] = ['icon' => '&#11088;', 'name' => 'Erste Schritte', 'color' => '#FFD700'];
if ($totalScore >= 50) $achievements[] = ['icon' => '&#11088;', 'name' => 'Fleissiger Schueler', 'color' => '#C0C0C0'];
if ($totalScore >= 100) $achievements[] = ['icon' => '&#127942;', 'name' => 'Top-Lerner', 'color' => '#FFD700'];
if ($totalScore >= 500) $achievements[] = ['icon' => '&#128081;', 'name' => 'Lern-Koenig', 'color' => '#9C27B0'];
if ($totalScore >= 1000) $achievements[] = ['icon' => '&#128142;', 'name' => 'Diamant-Schueler', 'color' => '#00BCD4'];
if (($_SESSION['scores']['math'] ?? 0) >= 100) $achievements[] = ['icon' => '&#128290;', 'name' => 'Mathe-Genie', 'color' => '#43D240'];
if (($_SESSION['scores']['reading'] ?? 0) >= 100) $achievements[] = ['icon' => '&#128218;', 'name' => 'Buecherwurm', 'color' => '#2196F3'];
if (($_SESSION['scores']['science'] ?? 0) >= 100) $achievements[] = ['icon' => '&#128300;', 'name' => 'Forscher', 'color' => '#E86F2C'];
if (($_SESSION['scores']['geography'] ?? 0) >= 100) $achievements[] = ['icon' => '&#127758;', 'name' => 'Weltentdecker', 'color' => '#F44336'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?= htmlspecialchars($username) ?> - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <style>
        .profile-header {
            text-align: center;
            padding: 40px 20px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--card);
            border: 3px solid var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
            margin: 0 auto 15px;
            box-shadow: var(--shadow-glow);
        }
        .profile-name {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--text);
        }
        .profile-age {
            font-size: 1.1em;
            color: var(--accent);
            margin-top: 5px;
        }

        .score-hero {
            text-align: center;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 30px;
            margin: 20px auto;
            max-width: 800px;
            box-shadow: var(--shadow);
        }
        .score-hero h3 { color: var(--text-muted); font-size: 1em; margin-bottom: 10px; }
        .score-value { font-size: 2.5em; font-weight: 700; color: var(--accent); }
        .score-level { margin-top: 8px; font-size: 1.1em; color: var(--text-muted); }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 25px;
            margin: 20px auto;
            max-width: 800px;
            box-shadow: var(--shadow);
        }
        .card h3 {
            color: var(--text);
            font-size: 1.2em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .subject-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .subject-label { font-size: 1.05em; color: var(--text); }
        .subject-score { font-weight: 700; color: var(--accent); }
        .progress-track {
            width: 100%;
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            margin-top: 6px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent), #35B035);
            border-radius: 4px;
            transition: width 0.6s ease;
        }

        .achievements-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }
        .achievement {
            padding: 12px 18px;
            border-radius: 12px;
            text-align: center;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            min-width: 110px;
        }
        .achievement-icon { font-size: 1.8em; }
        .achievement-name { font-size: 0.85em; margin-top: 5px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            border: 1px solid rgba(67,210,64,0.15);
        }
        .stat-icon { font-size: 1.8em; }
        .stat-label { color: var(--text-muted); font-size: 0.9em; margin-top: 8px; }
        .stat-value { font-weight: 700; color: var(--text); margin-top: 4px; }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1em;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.3); }
        .btn-reset { background: linear-gradient(135deg, #E86F2C, #d35400); }
        .btn-logout { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .btn-back {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            border: 1px solid var(--border);
        }

        .motivation-card {
            text-align: center;
            background: linear-gradient(135deg, rgba(67,210,64,0.1), rgba(26,53,3,0.3));
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 25px;
            margin: 20px auto;
            max-width: 800px;
        }
        .motivation-text { font-size: 1.2em; color: var(--accent); margin-top: 10px; }

        .back-nav {
            text-align: center;
            margin: 20px auto;
            max-width: 800px;
        }
        .back-nav a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        .back-nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="profile-header">
        <div class="profile-avatar">&#128100;</div>
        <div class="profile-name"><?= htmlspecialchars($username) ?></div>
        <div class="profile-age"><?= (int)$userAge ?> Jahre alt</div>
    </div>

    <div class="score-hero">
        <h3>Gesamtpunktzahl</h3>
        <div class="score-value">&#127942; <?= (int)$totalScore ?> Punkte</div>
        <div class="score-level">Level: <strong><?= htmlspecialchars($level) ?></strong></div>
    </div>

    <div class="card">
        <h3>Punkte pro Fach</h3>
        <?php
        $subjects = [
            ['key' => 'math', 'icon' => '&#128290;', 'name' => 'Mathematik'],
            ['key' => 'reading', 'icon' => '&#128214;', 'name' => 'Lesen'],
            ['key' => 'science', 'icon' => '&#128300;', 'name' => 'Wissenschaft'],
            ['key' => 'geography', 'icon' => '&#127758;', 'name' => 'Erdkunde'],
        ];
        foreach ($subjects as $sub):
            $score = (int)($_SESSION['scores'][$sub['key']] ?? 0);
            $pct = min(100, $score / 10);
        ?>
        <div style="margin-bottom: 18px;">
            <div class="subject-row">
                <span class="subject-label"><?= $sub['icon'] ?> <?= $sub['name'] ?></span>
                <span class="subject-score"><?= $score ?> Punkte</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" style="width: <?= $pct ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h3>Erfolge</h3>
        <div class="achievements-grid">
            <?php if (empty($achievements)): ?>
                <p style="color: var(--text-muted);">Sammle Punkte um Erfolge freizuschalten!</p>
            <?php else: ?>
                <?php foreach ($achievements as $a): ?>
                <div class="achievement" style="background: <?= $a['color'] ?>;">
                    <div class="achievement-icon"><?= $a['icon'] ?></div>
                    <div class="achievement-name"><?= htmlspecialchars($a['name']) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h3>Statistiken</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-icon">&#128197;</div>
                <div class="stat-label">Dabei seit</div>
                <div class="stat-value">Heute</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">&#127919;</div>
                <div class="stat-label">Beste Streak</div>
                <div class="stat-value"><?= (int)($_SESSION['best_streak'] ?? 0) ?> Tage</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">&#128200;</div>
                <div class="stat-label">Level</div>
                <div class="stat-value"><?= htmlspecialchars($level) ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">&#127941;</div>
                <div class="stat-label">Erfolge</div>
                <div class="stat-value"><?= count($achievements) ?></div>
            </div>
        </div>
    </div>

    <div class="card" style="text-align: center;">
        <h3>Aktionen</h3>
        <div class="actions">
            <form method="POST">
                <button type="submit" name="reset_scores" class="btn-action btn-reset"
                        onclick="return confirm('Bist du sicher? Alle Punkte werden auf 0 zurueckgesetzt!');">
                    &#128260; Punkte zuruecksetzen
                </button>
            </form>
            <form method="POST">
                <button type="submit" name="logout" class="btn-action btn-logout">
                    &#128682; Abmelden
                </button>
            </form>
        </div>
    </div>

    <?php
    $motivations = [
        "Du machst das grossartig, " . htmlspecialchars($username) . "!",
        "Bleib dran, du wirst immer besser!",
        "Lernen ist wie ein Abenteuer!",
        "Mit jedem Tag wirst du schlauer!",
        "Du bist ein echter Lern-Champion!",
        "Dein Fleiss wird sich auszahlen!",
        "Jeder Punkt bringt dich weiter!"
    ];
    ?>
    <div class="motivation-card">
        <h3>Motivation</h3>
        <div class="motivation-text"><?= $motivations[array_rand($motivations)] ?></div>
    </div>

    <div class="back-nav">
        <a href="../adaptive_learning.php">&larr; Zurueck zur Plattform</a>
    </div>
</body>
</html>
