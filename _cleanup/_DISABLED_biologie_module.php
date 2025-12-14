<?php
// Lade Session-Management  
require_once '../session.php';

// Login-Check
requireLogin();

$username = getUsername();
$biologyScore = getScore('biology');

// Initialisiere Session für Biologie
initQuestionSession('biology');

// Check ob neue Session gestartet werden soll
if (isset($_POST['new_session'])) {
    resetQuestionSession('biology');
    header('Location: index.php');
    exit();
}

// Check ob Session beendet ist
$sessionComplete = isSessionComplete('biology');
$sessionStats = getSessionStats('biology');

// Biologie Fragen - kindgerecht
$questions = [
    // Menschlicher Körper
    [
        'question' => 'Wie viele Knochen hat ein erwachsener Mensch?',
        'options' => ['50', '100', '206', '500'],
        'answer' => '206',
        'explanation' => 'Ein Erwachsener hat 206 Knochen - Babys haben sogar über 300!',
        'emoji' => '🦴'
    ],
    [
        'question' => 'Welches ist das größte Organ des Menschen?',
        'options' => ['Das Herz', 'Die Lunge', 'Die Haut', 'Der Magen'],
        'answer' => 'Die Haut',
        'explanation' => 'Die Haut ist unser größtes Organ und wiegt etwa 3-4 Kilogramm!',
        'emoji' => '👤'
    ],
    [
        'question' => 'Wie oft schlägt das Herz pro Tag?',
        'options' => ['1.000 mal', '10.000 mal', '100.000 mal', '1 Million mal'],
        'answer' => '100.000 mal',
        'explanation' => 'Das Herz schlägt etwa 100.000 mal am Tag und pumpt dabei 7.000 Liter Blut!',
        'emoji' => '❤️'
    ],
    [
        'question' => 'Was sind rote Blutkörperchen?',
        'options' => ['Sie bekämpfen Krankheiten', 'Sie transportieren Sauerstoff', 'Sie verdauen Essen', 'Sie produzieren Hormone'],
        'answer' => 'Sie transportieren Sauerstoff',
        'explanation' => 'Rote Blutkörperchen bringen Sauerstoff zu allen Zellen im Körper!',
        'emoji' => '🩸'
    ],
    
    // Tiere
    [
        'question' => 'Welches ist das größte Tier der Welt?',
        'options' => ['Elefant', 'Giraffe', 'Blauwal', 'Dinosaurier'],
        'answer' => 'Blauwal',
        'explanation' => 'Der Blauwal kann über 30 Meter lang werden - so lang wie drei Busse!',
        'emoji' => '🐋'
    ],
    [
        'question' => 'Wie nennt man Tiere, die nur Pflanzen essen?',
        'options' => ['Fleischfresser', 'Pflanzenfresser', 'Allesfresser', 'Raubtiere'],
        'answer' => 'Pflanzenfresser',
        'explanation' => 'Pflanzenfresser (Herbivoren) wie Kühe, Pferde und Kaninchen essen nur Pflanzen!',
        'emoji' => '🐮'
    ],
    [
        'question' => 'Was ist Winterschlaf?',
        'options' => ['Tiere schlafen den ganzen Winter', 'Tiere wandern', 'Tiere frieren ein', 'Tiere verstecken sich'],
        'answer' => 'Tiere schlafen den ganzen Winter',
        'explanation' => 'Beim Winterschlaf senken Tiere ihre Körpertemperatur und schlafen monatelang!',
        'emoji' => '🐻'
    ],
    [
        'question' => 'Welches Tier kann seinen Kopf um 270 Grad drehen?',
        'options' => ['Katze', 'Eule', 'Schlange', 'Giraffe'],
        'answer' => 'Eule',
        'explanation' => 'Eulen können ihren Kopf fast ganz herumdrehen - das hilft beim Jagen!',
        'emoji' => '🦉'
    ],
    
    // Pflanzen
    [
        'question' => 'Was brauchen Pflanzen zum Wachsen?',
        'options' => ['Nur Wasser', 'Nur Licht', 'Wasser, Licht und Nährstoffe', 'Nur Erde'],
        'answer' => 'Wasser, Licht und Nährstoffe',
        'explanation' => 'Pflanzen brauchen Wasser, Sonnenlicht und Nährstoffe aus der Erde!',
        'emoji' => '🌱'
    ],
    [
        'question' => 'Was ist Photosynthese?',
        'options' => ['Pflanzen machen Sauerstoff', 'Pflanzen schlafen', 'Pflanzen sterben', 'Pflanzen bewegen sich'],
        'answer' => 'Pflanzen machen Sauerstoff',
        'explanation' => 'Bei der Photosynthese verwandeln Pflanzen CO2 in Sauerstoff - den wir atmen!',
        'emoji' => '🌿'
    ],
    [
        'question' => 'Welcher Baum verliert im Winter seine Blätter?',
        'options' => ['Tanne', 'Fichte', 'Laubbaum', 'Kaktus'],
        'answer' => 'Laubbaum',
        'explanation' => 'Laubbäume wie Eichen und Buchen verlieren im Herbst ihre Blätter!',
        'emoji' => '🍂'
    ],
    
    // Zellen & DNA
    [
        'question' => 'Was ist die kleinste Einheit des Lebens?',
        'options' => ['Das Atom', 'Die Zelle', 'Das Molekül', 'Das Organ'],
        'answer' => 'Die Zelle',
        'explanation' => 'Die Zelle ist der Grundbaustein allen Lebens!',
        'emoji' => '🔬'
    ],
    [
        'question' => 'Was ist DNA?',
        'options' => ['Ein Vitamin', 'Der Bauplan des Lebens', 'Ein Organ', 'Ein Knochen'],
        'answer' => 'Der Bauplan des Lebens',
        'explanation' => 'Die DNA enthält alle Informationen, die uns zu dem machen, was wir sind!',
        'emoji' => '🧬'
    ],
    
    // Ökosysteme
    [
        'question' => 'Was ist eine Nahrungskette?',
        'options' => ['Essen im Supermarkt', 'Wer wen frisst', 'Eine Kette aus Essen', 'Ein Restaurant'],
        'answer' => 'Wer wen frisst',
        'explanation' => 'Die Nahrungskette zeigt, wer wen in der Natur frisst - vom Gras zur Maus zur Eule!',
        'emoji' => '🦅'
    ],
    [
        'question' => 'Was produzieren Bienen?',
        'options' => ['Milch', 'Honig', 'Zucker', 'Butter'],
        'answer' => 'Honig',
        'explanation' => 'Bienen sammeln Nektar und machen daraus leckeren Honig!',
        'emoji' => '🐝'
    ],
    [
        'question' => 'Warum sind Wälder wichtig?',
        'options' => ['Sie sehen schön aus', 'Sie produzieren Sauerstoff', 'Sie sind groß', 'Sie sind grün'],
        'answer' => 'Sie produzieren Sauerstoff',
        'explanation' => 'Wälder sind die "Lunge der Erde" - sie produzieren den Sauerstoff, den wir atmen!',
        'emoji' => '🌲'
    ],
    
    // Evolution & Anpassung
    [
        'question' => 'Warum haben Eisbären weißes Fell?',
        'options' => ['Es sieht schön aus', 'Zur Tarnung im Schnee', 'Es ist wärmer', 'Zufall'],
        'answer' => 'Zur Tarnung im Schnee',
        'explanation' => 'Das weiße Fell tarnt Eisbären im Schnee - perfekt zum Jagen!',
        'emoji' => '🐻‍❄️'
    ],
    [
        'question' => 'Was sind Dinosaurier?',
        'options' => ['Lebende Tiere', 'Ausgestorbene Reptilien', 'Fantasietiere', 'Vögel'],
        'answer' => 'Ausgestorbene Reptilien',
        'explanation' => 'Dinosaurier lebten vor Millionen Jahren und sind die Vorfahren der heutigen Vögel!',
        'emoji' => '🦕'
    ],
    [
        'question' => 'Wie atmen Fische?',
        'options' => ['Mit der Lunge', 'Mit Kiemen', 'Durch die Haut', 'Gar nicht'],
        'answer' => 'Mit Kiemen',
        'explanation' => 'Fische haben Kiemen, mit denen sie Sauerstoff aus dem Wasser filtern!',
        'emoji' => '🐠'
    ],
    [
        'question' => 'Was ist Metamorphose?',
        'options' => ['Eine Krankheit', 'Die Verwandlung von Tieren', 'Ein Spiel', 'Eine Pflanze'],
        'answer' => 'Die Verwandlung von Tieren',
        'explanation' => 'Bei der Metamorphose verwandelt sich z.B. eine Raupe in einen Schmetterling!',
        'emoji' => '🦋'
    ]
];

// Zufällige Frage auswählen
function getRandomQuestion($questions) {
    return $questions[array_rand($questions)];
}

// Antwort überprüfen
$feedback = '';
$feedbackClass = '';

if (!$sessionComplete) {
    if (isset($_POST['answer']) && isset($_SESSION['current_biology_question'])) {
        if ($_POST['answer'] === $_SESSION['current_biology_question']['answer']) {
            addAnsweredQuestion('biology', true);
            addScore('biology');
            increaseStreak();
            $feedback = "🎉 Richtig! " . $_SESSION['current_biology_question']['explanation'];
            $feedbackClass = 'correct';
        } else {
            addAnsweredQuestion('biology', false);
            resetStreak();
            $feedback = "Das war leider nicht richtig. " . $_SESSION['current_biology_question']['explanation'];
            $feedbackClass = 'incorrect';
        }
        
        if (!isSessionComplete('biology')) {
            $_SESSION['current_biology_question'] = getRandomQuestion($questions);
        }
        
        $sessionStats = getSessionStats('biology');
    } elseif (!isset($_SESSION['current_biology_question'])) {
        $_SESSION['current_biology_question'] = getRandomQuestion($questions);
    }
}

$currentQuestion = $_SESSION['current_biology_question'] ?? null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biologie - sgiT Education</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .biology-bg {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>sgiT Education</h1>
        <div class="subtitle">Leben erforschen! 🧬</div>
    </header>

    <nav class="navigation">
        <a href="../index.php" class="nav-button">🏠 Start</a>
        <a href="../mathe/" class="nav-button">🔢 Mathematik</a>
        <a href="../lesen/" class="nav-button">📖 Lesen</a>
        <a href="../wissenschaft/" class="nav-button">🔬 Wissenschaft</a>
        <a href="../erdkunde/" class="nav-button">🌍 Erdkunde</a>
        <a href="../englisch/" class="nav-button">🇬🇧 Englisch</a>
        <a href="../chemie/" class="nav-button">⚗️ Chemie</a>
        <a href="../physik/" class="nav-button">⚛️ Physik</a>
        <a href="../kunst/" class="nav-button">🎨 Kunst</a>
        <a href="../musik/" class="nav-button">🎵 Musik</a>
        <a href="../computer/" class="nav-button">💻 Computer</a>
        <a href="../bitcoin/" class="nav-button">₿ Bitcoin</a>
        <a href="../geschichte/" class="nav-button">📜 Geschichte</a>
        <a href="../biologie/" class="nav-button active">🧬 Biologie</a>
        <a href="../steuern/" class="nav-button">💰 Steuern</a>
        <a href="../profil/" class="nav-button">👤 <?= $username ?></a>
    </nav>

    <div class="container">
        <?php if (!$sessionComplete): ?>
            <div class="score-display biology-bg">
                <h3>Fortschritt: Frage <?= $sessionStats['total'] + 1 ?> von 10</h3>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?= ($sessionStats['total'] * 10) ?>%">
                        <?= $sessionStats['total'] ?>/10 Fragen
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    ✅ Richtig: <?= $sessionStats['correct'] ?> | 
                    ❌ Falsch: <?= $sessionStats['wrong'] ?>
                </div>
            </div>

            <div class="exercise-container">
                <h2>🧬 Biologie-Frage</h2>
                
                <?php if ($feedback): ?>
                    <div class="feedback <?= $feedbackClass ?>">
                        <?= $feedback ?>
                    </div>
                <?php endif; ?>

                <?php if ($currentQuestion): ?>
                    <div class="quiz-question">
                        <div style="font-size: 4em; margin: 20px 0;">
                            <?= $currentQuestion['emoji'] ?>
                        </div>
                        
                        <h3 style="font-size: 1.8em; color: var(--sgit-dark-green); margin: 20px 0;">
                            <?= $currentQuestion['question'] ?>
                        </h3>
                        
                        <form method="POST">
                            <div class="level-selection" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; max-width: 500px; margin: 0 auto;">
                                <?php foreach ($currentQuestion['options'] as $option): ?>
                                    <button type="submit" name="answer" value="<?= $option ?>" 
                                            class="level-btn" style="margin: 0;">
                                        <?= $option ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="content-box" style="background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%); margin-top: 20px;">
                <h3>🌿 Bio-Wissen</h3>
                <?php
                $facts = [
                    "🦴 Ein Baby hat bei der Geburt über 300 Knochen!",
                    "🧠 Das Gehirn besteht zu 75% aus Wasser!",
                    "👁️ Wir blinzeln etwa 15-20 mal pro Minute!",
                    "🦷 Zähne sind das härteste Material im Körper!",
                    "🌳 Ein großer Baum produziert Sauerstoff für 2 Menschen pro Jahr!",
                    "🐛 Es gibt mehr Insektenarten als alle anderen Tiere zusammen!",
                    "🦋 Schmetterlinge schmecken mit den Füßen!",
                    "🐙 Oktopusse haben 3 Herzen und blaues Blut!",
                    "🌻 Sonnenblumen drehen sich immer zur Sonne!",
                    "🐘 Elefanten können nicht springen!"
                ];
                echo "<p>" . $facts[array_rand($facts)] . "</p>";
                ?>
            </div>

        <?php else: ?>
            <div class="content-box" style="text-align: center;">
                <h2>🎉 Toll! Alle 10 Biologie-Fragen beantwortet!</h2>
                
                <div class="score-display" style="margin: 30px auto; max-width: 500px;">
                    <h3>Deine Ergebnisse:</h3>
                    <div style="font-size: 3em; margin: 20px 0;">
                        <?= $sessionStats['correct'] ?>/10
                    </div>
                    <div style="font-size: 1.5em;">
                        <?= $sessionStats['percentage'] ?>% richtig
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <p>✅ Richtige Antworten: <?= $sessionStats['correct'] ?></p>
                        <p>❌ Falsche Antworten: <?= $sessionStats['wrong'] ?></p>
                        <p>⭐ Punkte erhalten: <?= $sessionStats['points_earned'] ?></p>
                    </div>
                </div>

                <div style="margin: 30px 0; font-size: 1.3em;">
                    <?php
                    if ($sessionStats['percentage'] >= 90) {
                        echo "🏆 Fantastisch! Du bist ein Biologie-Experte!";
                    } elseif ($sessionStats['percentage'] >= 70) {
                        echo "🌿 Sehr gut! Du verstehst das Leben!";
                    } elseif ($sessionStats['percentage'] >= 50) {
                        echo "👍 Gut gemacht! Die Natur ist faszinierend!";
                    } else {
                        echo "💪 Weiter üben! Biologie ist überall um uns!";
                    }
                    ?>
                </div>

                <div style="margin-top: 40px;">
                    <h3>Was möchtest du als nächstes tun?</h3>
                    <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="new_session" value="1" class="btn biology-bg">
                                🔄 Weitere 10 Biologie-Fragen
                            </button>
                        </form>
                        <a href="../" class="btn" 
                           style="background: linear-gradient(135deg, #2196F3, #1976D2);">
                            📚 Anderes Fach wählen
                        </a>
                        <a href="../profil/" class="btn" 
                           style="background: linear-gradient(135deg, #9C27B0, #7B1FA2);">
                            👤 Mein Profil ansehen
                        </a>
                    </div>
                </div>
            </div>
            
            <?php
            saveStats('biology', $sessionStats);
            ?>
        <?php endif; ?>

        <div class="content-box" style="margin-top: 30px;">
            <h3>Deine Biologie-Gesamtpunkte</h3>
            <div style="text-align: center; font-size: 2em; color: var(--sgit-dark-green);">
                ⭐ <?= getScore('biology') ?> Punkte
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($feedbackClass === 'correct'): ?>
        const scoreDisplay = document.querySelector('.score-value');
        if (scoreDisplay) {
            scoreDisplay.classList.add('success-animation');
        }
        <?php endif; ?>
    });
    </script>
</body>
</html>