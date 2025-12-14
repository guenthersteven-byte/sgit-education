<?php
// Lade Session-Management  
require_once '../session.php';

// Login-Check
requireLogin();

$username = getUsername();
$historyScore = getScore('history');

// Initialisiere Session für Geschichte
initQuestionSession('history');

// Check ob neue Session gestartet werden soll
if (isset($_POST['new_session'])) {
    resetQuestionSession('history');
    header('Location: index.php');
    exit();
}

// Check ob Session beendet ist
$sessionComplete = isSessionComplete('history');
$sessionStats = getSessionStats('history');

// Deutsche Geschichte Fragen - kindgerecht
$questions = [
    // Frühgeschichte
    [
        'question' => 'Wer waren die Germanen?',
        'options' => ['Frühe Bewohner Deutschlands', 'Römische Soldaten', 'Griechische Händler', 'Wikinger'],
        'answer' => 'Frühe Bewohner Deutschlands',
        'explanation' => 'Die Germanen waren die Vorfahren der Deutschen und lebten vor über 2000 Jahren hier!',
        'emoji' => '⚔️'
    ],
    [
        'question' => 'Was war die Varusschlacht?',
        'options' => ['Germanen besiegen Römer', 'Römer erobern Germanien', 'Wikinger greifen an', 'Franzosen kämpfen'],
        'answer' => 'Germanen besiegen Römer',
        'explanation' => 'Im Jahr 9 n.Chr. besiegte Arminius die Römer im Teutoburger Wald!',
        'emoji' => '🛡️'
    ],
    
    // Mittelalter
    [
        'question' => 'Wer war Karl der Große?',
        'options' => ['Kaiser des Frankenreichs', 'Römischer Soldat', 'Englischer König', 'Spanischer Ritter'],
        'answer' => 'Kaiser des Frankenreichs',
        'explanation' => 'Karl der Große wurde im Jahr 800 zum Kaiser gekrönt und vereinte große Teile Europas!',
        'emoji' => '👑'
    ],
    [
        'question' => 'Was war das Heilige Römische Reich?',
        'options' => ['Ein deutsches Kaiserreich', 'Das alte Rom', 'Frankreich', 'England'],
        'answer' => 'Ein deutsches Kaiserreich',
        'explanation' => 'Das Heilige Römische Reich Deutscher Nation bestand fast 1000 Jahre lang!',
        'emoji' => '🏰'
    ],
    [
        'question' => 'Wer waren die Ritter?',
        'options' => ['Gepanzerte Krieger', 'Bauern', 'Händler', 'Mönche'],
        'answer' => 'Gepanzerte Krieger',
        'explanation' => 'Ritter waren adlige Krieger in schwerer Rüstung, die auf Burgen lebten!',
        'emoji' => '🗡️'
    ],
    [
        'question' => 'Was war die Hanse?',
        'options' => ['Ein Handelsbund', 'Eine Armee', 'Ein Königreich', 'Eine Religion'],
        'answer' => 'Ein Handelsbund',
        'explanation' => 'Die Hanse war ein mächtiger Bund von Handelsstädten an Nord- und Ostsee!',
        'emoji' => '⚓'
    ],
    
    // Reformation
    [
        'question' => 'Wer war Martin Luther?',
        'options' => ['Ein Reformator', 'Ein Kaiser', 'Ein Erfinder', 'Ein Entdecker'],
        'answer' => 'Ein Reformator',
        'explanation' => 'Martin Luther übersetzte die Bibel ins Deutsche und reformierte die Kirche!',
        'emoji' => '📖'
    ],
    [
        'question' => 'Was erfand Johannes Gutenberg?',
        'options' => ['Den Buchdruck', 'Das Auto', 'Das Telefon', 'Den Computer'],
        'answer' => 'Den Buchdruck',
        'explanation' => 'Um 1450 erfand Gutenberg den Buchdruck mit beweglichen Lettern!',
        'emoji' => '📚'
    ],
    
    // Neuzeit
    [
        'question' => 'Was war der Dreißigjährige Krieg?',
        'options' => ['Ein langer Religionskrieg', 'Ein Krieg gegen Frankreich', 'Ein Krieg gegen England', 'Ein Bürgerkrieg'],
        'answer' => 'Ein langer Religionskrieg',
        'explanation' => 'Von 1618-1648 tobte ein schrecklicher Krieg in Deutschland zwischen Katholiken und Protestanten!',
        'emoji' => '⛪'
    ],
    [
        'question' => 'Wer war Friedrich der Große?',
        'options' => ['König von Preußen', 'Kaiser von Österreich', 'König von Bayern', 'Herzog von Sachsen'],
        'answer' => 'König von Preußen',
        'explanation' => 'Friedrich II. machte Preußen zu einer Großmacht und liebte Kunst und Musik!',
        'emoji' => '🎭'
    ],
    
    // 19. Jahrhundert
    [
        'question' => 'Was waren die Befreiungskriege?',
        'options' => ['Kampf gegen Napoleon', 'Krieg gegen England', 'Krieg gegen Russland', 'Bürgerkrieg'],
        'answer' => 'Kampf gegen Napoleon',
        'explanation' => '1813-1815 kämpften die Deutschen gegen Napoleon und besiegten ihn!',
        'emoji' => '🇫🇷'
    ],
    [
        'question' => 'Wann wurde das Deutsche Reich gegründet?',
        'options' => ['1871', '1789', '1914', '1945'],
        'answer' => '1871',
        'explanation' => 'Im Spiegelsaal von Versailles wurde 1871 das Deutsche Kaiserreich gegründet!',
        'emoji' => '🏛️'
    ],
    [
        'question' => 'Wer war Otto von Bismarck?',
        'options' => ['Der erste Reichskanzler', 'Ein Kaiser', 'Ein Erfinder', 'Ein Komponist'],
        'answer' => 'Der erste Reichskanzler',
        'explanation' => 'Bismarck vereinte Deutschland und wurde der "Eiserne Kanzler" genannt!',
        'emoji' => '🎩'
    ],
    
    // 20. Jahrhundert
    [
        'question' => 'Was war die Weimarer Republik?',
        'options' => ['Erste deutsche Demokratie', 'Ein Königreich', 'Ein Bundesland', 'Eine Stadt'],
        'answer' => 'Erste deutsche Demokratie',
        'explanation' => 'Nach dem Ersten Weltkrieg wurde Deutschland 1918 erstmals eine Demokratie!',
        'emoji' => '🗳️'
    ],
    [
        'question' => 'Was passierte am 9. November 1989?',
        'options' => ['Die Berliner Mauer fiel', 'Der Krieg begann', 'Deutschland wurde geteilt', 'Die EU wurde gegründet'],
        'answer' => 'Die Berliner Mauer fiel',
        'explanation' => 'Nach 28 Jahren fiel die Mauer - Deutschland war wieder vereint!',
        'emoji' => '🧱'
    ],
    [
        'question' => 'Wann wurde die Bundesrepublik Deutschland gegründet?',
        'options' => ['1949', '1871', '1918', '1990'],
        'answer' => '1949',
        'explanation' => 'Am 23. Mai 1949 wurde mit dem Grundgesetz die Bundesrepublik gegründet!',
        'emoji' => '📜'
    ],
    
    // Kultur & Erfindungen
    [
        'question' => 'Wer komponierte die 9. Sinfonie?',
        'options' => ['Beethoven', 'Mozart', 'Bach', 'Wagner'],
        'answer' => 'Beethoven',
        'explanation' => 'Ludwig van Beethoven schrieb die berühmte "Ode an die Freude"!',
        'emoji' => '🎵'
    ],
    [
        'question' => 'Wer erfand das Auto?',
        'options' => ['Carl Benz', 'Henry Ford', 'Rudolf Diesel', 'Gottlieb Daimler'],
        'answer' => 'Carl Benz',
        'explanation' => '1885 baute Carl Benz das erste Automobil mit Benzinmotor!',
        'emoji' => '🚗'
    ],
    [
        'question' => 'Wer schrieb Grimms Märchen?',
        'options' => ['Die Gebrüder Grimm', 'Goethe', 'Schiller', 'Heine'],
        'answer' => 'Die Gebrüder Grimm',
        'explanation' => 'Jacob und Wilhelm Grimm sammelten deutsche Volksmärchen wie Hänsel und Gretel!',
        'emoji' => '📖'
    ],
    [
        'question' => 'Was ist das Brandenburger Tor?',
        'options' => ['Wahrzeichen Berlins', 'Eine Brücke', 'Ein Schloss', 'Eine Kirche'],
        'answer' => 'Wahrzeichen Berlins',
        'explanation' => 'Das Brandenburger Tor ist das berühmteste Wahrzeichen Deutschlands!',
        'emoji' => '🏛️'
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
    if (isset($_POST['answer']) && isset($_SESSION['current_history_question'])) {
        if ($_POST['answer'] === $_SESSION['current_history_question']['answer']) {
            addAnsweredQuestion('history', true);
            addScore('history');
            increaseStreak();
            $feedback = "🎉 Richtig! " . $_SESSION['current_history_question']['explanation'];
            $feedbackClass = 'correct';
        } else {
            addAnsweredQuestion('history', false);
            resetStreak();
            $feedback = "Das war leider nicht richtig. " . $_SESSION['current_history_question']['explanation'];
            $feedbackClass = 'incorrect';
        }
        
        if (!isSessionComplete('history')) {
            $_SESSION['current_history_question'] = getRandomQuestion($questions);
        }
        
        $sessionStats = getSessionStats('history');
    } elseif (!isset($_SESSION['current_history_question'])) {
        $_SESSION['current_history_question'] = getRandomQuestion($questions);
    }
}

$currentQuestion = $_SESSION['current_history_question'] ?? null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geschichte - sgiT Education</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .history-bg {
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>sgiT Education</h1>
        <div class="subtitle">Deutsche Geschichte entdecken! 📜</div>
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
        <a href="../geschichte/" class="nav-button active">📜 Geschichte</a>
        <a href="../biologie/" class="nav-button">🧬 Biologie</a>
        <a href="../steuern/" class="nav-button">💰 Steuern</a>
        <a href="../profil/" class="nav-button">👤 <?= $username ?></a>
    </nav>

    <div class="container">
        <?php if (!$sessionComplete): ?>
            <div class="score-display history-bg">
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
                <h2>📜 Geschichte-Frage</h2>
                
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

            <div class="content-box" style="background: linear-gradient(135deg, #F5DEB3 0%, #FFE4B5 100%); margin-top: 20px;">
                <h3>📚 Geschichts-Wissen</h3>
                <?php
                $facts = [
                    "🏰 Deutschland hat über 20.000 Burgen und Schlösser!",
                    "📖 Die deutsche Sprache gibt es seit über 1000 Jahren!",
                    "🎭 Deutschland wird oft 'Land der Dichter und Denker' genannt!",
                    "🚗 Das Auto wurde in Deutschland erfunden!",
                    "📚 Der Buchdruck revolutionierte die Welt!",
                    "🎵 Deutschland hat viele berühmte Komponisten hervorgebracht!",
                    "⚔️ Die Germanen besiegten die mächtigen Römer!",
                    "🏛️ Das Brandenburger Tor ist über 200 Jahre alt!",
                    "👑 Karl der Große regierte ein riesiges Reich!",
                    "🧱 Die Berliner Mauer stand 28 Jahre lang!"
                ];
                echo "<p>" . $facts[array_rand($facts)] . "</p>";
                ?>
            </div>

        <?php else: ?>
            <div class="content-box" style="text-align: center;">
                <h2>🎉 Prima! Alle 10 Geschichte-Fragen beantwortet!</h2>
                
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
                        echo "🏆 Hervorragend! Du bist ein Geschichts-Experte!";
                    } elseif ($sessionStats['percentage'] >= 70) {
                        echo "📚 Sehr gut! Du kennst dich in Geschichte aus!";
                    } elseif ($sessionStats['percentage'] >= 50) {
                        echo "👍 Gut gemacht! Geschichte ist spannend!";
                    } else {
                        echo "💪 Weiter üben! Geschichte wird dein Freund!";
                    }
                    ?>
                </div>

                <div style="margin-top: 40px;">
                    <h3>Was möchtest du als nächstes tun?</h3>
                    <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="new_session" value="1" class="btn history-bg">
                                🔄 Weitere 10 Geschichte-Fragen
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
            saveStats('history', $sessionStats);
            ?>
        <?php endif; ?>

        <div class="content-box" style="margin-top: 30px;">
            <h3>Deine Geschichte-Gesamtpunkte</h3>
            <div style="text-align: center; font-size: 2em; color: var(--sgit-dark-green);">
                ⭐ <?= getScore('history') ?> Punkte
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