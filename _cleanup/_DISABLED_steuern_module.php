<?php
// Lade Session-Management  
require_once '../session.php';

// Login-Check
requireLogin();

$username = getUsername();
$taxesScore = getScore('taxes');

// Initialisiere Session für Steuern
initQuestionSession('taxes');

// Check ob neue Session gestartet werden soll
if (isset($_POST['new_session'])) {
    resetQuestionSession('taxes');
    header('Location: index.php');
    exit();
}

// Check ob Session beendet ist
$sessionComplete = isSessionComplete('taxes');
$sessionStats = getSessionStats('taxes');

// Steuern & Finanzbildung Fragen - kindgerecht
$questions = [
    // Was sind Steuern?
    [
        'question' => 'Was sind Steuern?',
        'options' => ['Geschenke', 'Geld für den Staat', 'Spielgeld', 'Taschengeld'],
        'answer' => 'Geld für den Staat',
        'explanation' => 'Steuern sind Geld, das Menschen an den Staat zahlen, damit er Straßen, Schulen und Krankenhäuser bauen kann!',
        'emoji' => '🏛️'
    ],
    [
        'question' => 'Wofür werden Steuern verwendet?',
        'options' => ['Nur für Politiker', 'Für Schulen, Straßen, Polizei', 'Zum Sparen', 'Für Süßigkeiten'],
        'answer' => 'Für Schulen, Straßen, Polizei',
        'explanation' => 'Mit Steuergeldern werden wichtige Dinge bezahlt: Schulen, Straßen, Polizei, Feuerwehr und vieles mehr!',
        'emoji' => '🚓'
    ],
    [
        'question' => 'Was ist die Mehrwertsteuer?',
        'options' => ['Steuer beim Einkaufen', 'Steuer fürs Auto', 'Steuer für Häuser', 'Keine Steuer'],
        'answer' => 'Steuer beim Einkaufen',
        'explanation' => 'Die Mehrwertsteuer zahlst du automatisch mit, wenn du etwas kaufst - sie ist im Preis versteckt!',
        'emoji' => '🛒'
    ],
    
    // Arten von Steuern
    [
        'question' => 'Was ist die Einkommensteuer?',
        'options' => ['Steuer auf Einkommen', 'Steuer auf Autos', 'Steuer auf Häuser', 'Steuer auf Essen'],
        'answer' => 'Steuer auf Einkommen',
        'explanation' => 'Wer arbeitet und Geld verdient, muss einen Teil davon als Einkommensteuer abgeben!',
        'emoji' => '💼'
    ],
    [
        'question' => 'Wie viel Prozent Mehrwertsteuer zahlen wir in Deutschland?',
        'options' => ['5%', '10%', '19%', '50%'],
        'answer' => '19%',
        'explanation' => 'In Deutschland beträgt die normale Mehrwertsteuer 19% - für Lebensmittel nur 7%!',
        'emoji' => '🧮'
    ],
    
    // Sparen und Haushalten
    [
        'question' => 'Was bedeutet Sparen?',
        'options' => ['Alles ausgeben', 'Geld zurücklegen', 'Geld verschenken', 'Geld verlieren'],
        'answer' => 'Geld zurücklegen',
        'explanation' => 'Sparen bedeutet, Geld nicht sofort auszugeben, sondern für später aufzubewahren!',
        'emoji' => '🐖'
    ],
    [
        'question' => 'Was ist ein Budget?',
        'options' => ['Ein Plan für dein Geld', 'Ein Spielzeug', 'Eine Steuer', 'Ein Geschenk'],
        'answer' => 'Ein Plan für dein Geld',
        'explanation' => 'Ein Budget ist ein Plan, wie viel Geld du hast und wofür du es ausgeben willst!',
        'emoji' => '📊'
    ],
    [
        'question' => 'Warum ist Sparen wichtig?',
        'options' => ['Für Notfälle und Wünsche', 'Es ist nicht wichtig', 'Nur für Reiche', 'Zum Angeben'],
        'answer' => 'Für Notfälle und Wünsche',
        'explanation' => 'Wer spart, kann sich später größere Wünsche erfüllen und ist für Notfälle vorbereitet!',
        'emoji' => '💰'
    ],
    
    // Geld verdienen
    [
        'question' => 'Was ist ein Gehalt?',
        'options' => ['Geld für Arbeit', 'Geschenktes Geld', 'Gefundenes Geld', 'Geliehenes Geld'],
        'answer' => 'Geld für Arbeit',
        'explanation' => 'Ein Gehalt ist das Geld, das man für seine Arbeit bekommt!',
        'emoji' => '💵'
    ],
    [
        'question' => 'Was ist Taschengeld?',
        'options' => ['Geld von Eltern für Kinder', 'Steuer', 'Gehalt', 'Kredit'],
        'answer' => 'Geld von Eltern für Kinder',
        'explanation' => 'Taschengeld bekommen Kinder von ihren Eltern, um den Umgang mit Geld zu lernen!',
        'emoji' => '👶'
    ],
    
    // Wirtschaft Grundlagen
    [
        'question' => 'Was bedeutet "teuer"?',
        'options' => ['Kostet viel Geld', 'Kostet wenig Geld', 'Ist umsonst', 'Ist kaputt'],
        'answer' => 'Kostet viel Geld',
        'explanation' => 'Wenn etwas teuer ist, muss man viel Geld dafür bezahlen!',
        'emoji' => '💎'
    ],
    [
        'question' => 'Was ist Inflation?',
        'options' => ['Alles wird teurer', 'Alles wird billiger', 'Nichts ändert sich', 'Geld verschwindet'],
        'answer' => 'Alles wird teurer',
        'explanation' => 'Bei Inflation werden Dinge mit der Zeit teurer - das Geld verliert an Wert!',
        'emoji' => '📈'
    ],
    [
        'question' => 'Was ist ein Kredit?',
        'options' => ['Geliehenes Geld', 'Geschenktes Geld', 'Gefundenes Geld', 'Gespartes Geld'],
        'answer' => 'Geliehenes Geld',
        'explanation' => 'Ein Kredit ist Geld, das man sich leiht und später zurückzahlen muss - mit Zinsen!',
        'emoji' => '🏦'
    ],
    
    // Verantwortung mit Geld
    [
        'question' => 'Was solltest du mit deinem Taschengeld machen?',
        'options' => ['Alles sofort ausgeben', 'Einen Teil sparen', 'Verlieren', 'Verschenken'],
        'answer' => 'Einen Teil sparen',
        'explanation' => 'Klug ist es, einen Teil zu sparen und den Rest für Dinge auszugeben, die dir wichtig sind!',
        'emoji' => '🎯'
    ],
    [
        'question' => 'Was ist wichtiger: Bedürfnisse oder Wünsche?',
        'options' => ['Bedürfnisse', 'Wünsche', 'Beides gleich', 'Keines'],
        'answer' => 'Bedürfnisse',
        'explanation' => 'Bedürfnisse wie Essen und Kleidung sind wichtiger als Wünsche wie Spielzeug!',
        'emoji' => '🍎'
    ],
    
    // Steuergerechtigkeit
    [
        'question' => 'Warum zahlen alle Steuern?',
        'options' => ['Damit alle profitieren', 'Aus Spaß', 'Weil sie müssen', 'Für den König'],
        'answer' => 'Damit alle profitieren',
        'explanation' => 'Steuern sorgen dafür, dass alle von guten Straßen, Schulen und Sicherheit profitieren!',
        'emoji' => '🤝'
    ],
    [
        'question' => 'Was passiert mit Steuerhinterziehung?',
        'options' => ['Man bekommt Strafe', 'Nichts', 'Man wird belohnt', 'Man wird reich'],
        'answer' => 'Man bekommt Strafe',
        'explanation' => 'Wer keine Steuern zahlt, obwohl er muss, kann bestraft werden - das ist unfair gegenüber allen anderen!',
        'emoji' => '⚖️'
    ],
    
    // Unternehmertum
    [
        'question' => 'Was ist ein Unternehmer?',
        'options' => ['Jemand mit eigener Firma', 'Ein Angestellter', 'Ein Schüler', 'Ein Rentner'],
        'answer' => 'Jemand mit eigener Firma',
        'explanation' => 'Unternehmer haben ihre eigene Firma und schaffen oft Arbeitsplätze für andere!',
        'emoji' => '🏢'
    ],
    [
        'question' => 'Was braucht man für ein Geschäft?',
        'options' => ['Eine gute Idee und Fleiß', 'Nur Glück', 'Nur Geld', 'Nichts'],
        'answer' => 'Eine gute Idee und Fleiß',
        'explanation' => 'Ein erfolgreiches Geschäft braucht eine gute Idee, harte Arbeit und kluges Wirtschaften!',
        'emoji' => '💡'
    ],
    [
        'question' => 'Was ist Gewinn?',
        'options' => ['Mehr einnehmen als ausgeben', 'Alles ausgeben', 'Geld verlieren', 'Geld finden'],
        'answer' => 'Mehr einnehmen als ausgeben',
        'explanation' => 'Gewinn macht man, wenn man mehr Geld einnimmt als man ausgibt!',
        'emoji' => '📊'
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
    if (isset($_POST['answer']) && isset($_SESSION['current_taxes_question'])) {
        if ($_POST['answer'] === $_SESSION['current_taxes_question']['answer']) {
            addAnsweredQuestion('taxes', true);
            addScore('taxes');
            increaseStreak();
            $feedback = "🎉 Richtig! " . $_SESSION['current_taxes_question']['explanation'];
            $feedbackClass = 'correct';
        } else {
            addAnsweredQuestion('taxes', false);
            resetStreak();
            $feedback = "Das war leider nicht richtig. " . $_SESSION['current_taxes_question']['explanation'];
            $feedbackClass = 'incorrect';
        }
        
        if (!isSessionComplete('taxes')) {
            $_SESSION['current_taxes_question'] = getRandomQuestion($questions);
        }
        
        $sessionStats = getSessionStats('taxes');
    } elseif (!isset($_SESSION['current_taxes_question'])) {
        $_SESSION['current_taxes_question'] = getRandomQuestion($questions);
    }
}

$currentQuestion = $_SESSION['current_taxes_question'] ?? null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Steuern & Finanzen - sgiT Education</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .taxes-bg {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>sgiT Education</h1>
        <div class="subtitle">Finanzen verstehen! 💰</div>
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
        <a href="../biologie/" class="nav-button">🧬 Biologie</a>
        <a href="../steuern/" class="nav-button active">💰 Steuern</a>
        <a href="../profil/" class="nav-button">👤 <?= $username ?></a>
    </nav>

    <div class="container">
        <?php if (!$sessionComplete): ?>
            <div class="score-display taxes-bg">
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
                <h2>💰 Steuern & Finanz-Frage</h2>
                
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

            <div class="content-box" style="background: linear-gradient(135deg, #FFF8DC 0%, #FFEFD5 100%); margin-top: 20px;">
                <h3>💡 Finanz-Tipp</h3>
                <?php
                $tips = [
                    "🐖 Spare 10% von deinem Taschengeld - du wirst staunen wie schnell es wächst!",
                    "📝 Schreibe auf, wofür du Geld ausgibst - so behältst du den Überblick!",
                    "🎯 Setze dir Sparziele - für was sparst du?",
                    "💭 Überlege vor dem Kauf: Brauche ich das wirklich?",
                    "🏦 Ein Sparschwein ist deine erste Bank!",
                    "📚 Bildung ist die beste Investition in deine Zukunft!",
                    "🤝 Teilen macht Freude - aber spare auch für dich!",
                    "⏰ Zeit ist Geld - nutze sie weise!",
                    "💰 Reich wird man nicht durch viel verdienen, sondern durch wenig ausgeben!",
                    "🌱 Kleine Beträge werden mit der Zeit groß!"
                ];
                echo "<p>" . $tips[array_rand($tips)] . "</p>";
                ?>
            </div>

        <?php else: ?>
            <div class="content-box" style="text-align: center;">
                <h2>🎉 Klasse! Alle 10 Finanz-Fragen gemeistert!</h2>
                
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
                        echo "🏆 Ausgezeichnet! Du bist ein Finanz-Experte!";
                    } elseif ($sessionStats['percentage'] >= 70) {
                        echo "💵 Sehr gut! Du verstehst Geld und Steuern!";
                    } elseif ($sessionStats['percentage'] >= 50) {
                        echo "👍 Gut gemacht! Finanzen sind wichtig!";
                    } else {
                        echo "💪 Weiter üben! Finanzwissen ist fürs Leben!";
                    }
                    ?>
                </div>

                <div style="margin-top: 40px;">
                    <h3>Was möchtest du als nächstes tun?</h3>
                    <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="new_session" value="1" class="btn taxes-bg">
                                🔄 Weitere 10 Finanz-Fragen
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
            saveStats('taxes', $sessionStats);
            ?>
        <?php endif; ?>

        <div class="content-box" style="margin-top: 30px;">
            <h3>Deine Finanz-Gesamtpunkte</h3>
            <div style="text-align: center; font-size: 2em; color: #FFD700;">
                💰 <?= getScore('taxes') ?> Punkte
            </div>
        </div>
        
        <!-- Finanz-Weisheit -->
        <div class="content-box" style="margin-top: 20px; background: #FFFACD;">
            <h4>💡 Wichtige Finanz-Regel:</h4>
            <p style="font-style: italic; color: #8B7500;">
                "Nicht wieviel du verdienst macht dich reich, sondern wieviel du behältst und klug anlegst!"
            </p>
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