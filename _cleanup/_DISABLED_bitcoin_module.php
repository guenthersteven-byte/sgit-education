<?php
// Lade Session-Management  
require_once '../session.php';

// Login-Check
requireLogin();

$username = getUsername();
$bitcoinScore = getScore('bitcoin');

// Initialisiere Session für Bitcoin
initQuestionSession('bitcoin');

// Check ob neue Session gestartet werden soll
if (isset($_POST['new_session'])) {
    resetQuestionSession('bitcoin');
    header('Location: index.php');
    exit();
}

// Check ob Session beendet ist
$sessionComplete = isSessionComplete('bitcoin');
$sessionStats = getSessionStats('bitcoin');

// Bitcoin & Geld Fragen - kindgerecht erklärt
$questions = [
    // Was ist Geld?
    [
        'question' => 'Was ist die wichtigste Eigenschaft von gutem Geld?',
        'options' => ['Es ist bunt', 'Es behält seinen Wert', 'Es ist aus Papier', 'Es glänzt'],
        'answer' => 'Es behält seinen Wert',
        'explanation' => 'Gutes Geld sollte seinen Wert über Zeit behalten, wie Gold es Jahrtausende lang getan hat!',
        'emoji' => '💰'
    ],
    [
        'question' => 'Was passiert wenn man zu viel Geld druckt?',
        'options' => ['Alle werden reich', 'Das Geld wird wertvoller', 'Das Geld verliert an Wert', 'Nichts'],
        'answer' => 'Das Geld verliert an Wert',
        'explanation' => 'Wenn zu viel Geld gedruckt wird, wird es weniger wert - das nennt man Inflation!',
        'emoji' => '📉'
    ],
    [
        'question' => 'Was ist Fiat-Geld?',
        'options' => ['Gold-Münzen', 'Geld ohne echten Wert dahinter', 'Bitcoin', 'Muscheln'],
        'answer' => 'Geld ohne echten Wert dahinter',
        'explanation' => 'Fiat-Geld ist Papiergeld, das nur wertvoll ist, weil die Regierung es sagt!',
        'emoji' => '💵'
    ],
    
    // Bitcoin Grundlagen
    [
        'question' => 'Wer hat Bitcoin erfunden?',
        'options' => ['Bill Gates', 'Satoshi Nakamoto', 'Elon Musk', 'Steve Jobs'],
        'answer' => 'Satoshi Nakamoto',
        'explanation' => 'Satoshi Nakamoto ist ein Pseudonym - niemand weiß, wer es wirklich war!',
        'emoji' => '🤔'
    ],
    [
        'question' => 'Wann wurde Bitcoin erfunden?',
        'options' => ['1999', '2009', '2015', '2020'],
        'answer' => '2009',
        'explanation' => 'Bitcoin wurde 2009 gestartet, nach der großen Finanzkrise von 2008!',
        'emoji' => '📅'
    ],
    [
        'question' => 'Wie viele Bitcoin wird es maximal geben?',
        'options' => ['1 Million', '21 Millionen', '100 Millionen', 'Unendlich viele'],
        'answer' => '21 Millionen',
        'explanation' => 'Es wird niemals mehr als 21 Millionen Bitcoin geben - das macht es knapp und wertvoll!',
        'emoji' => '🔢'
    ],
    [
        'question' => 'Was ist das Bitcoin-Halving?',
        'options' => ['Bitcoin wird halbiert', 'Die Belohnung für Miner wird halbiert', 'Der Preis halbiert sich', 'Die Geschwindigkeit halbiert sich'],
        'answer' => 'Die Belohnung für Miner wird halbiert',
        'explanation' => 'Alle 4 Jahre wird die Belohnung für Bitcoin-Miner halbiert - das macht Bitcoin knapper!',
        'emoji' => '⛏️'
    ],
    
    // Österreichische Schule
    [
        'question' => 'Was sagt die Österreichische Schule über gutes Geld?',
        'options' => ['Es sollte von der Regierung kontrolliert werden', 'Es sollte frei gewählt werden können', 'Es sollte aus Papier sein', 'Es sollte digital sein'],
        'answer' => 'Es sollte frei gewählt werden können',
        'explanation' => 'Die Österreichische Schule glaubt, dass Menschen selbst wählen sollten, welches Geld sie verwenden!',
        'emoji' => '🇦🇹'
    ],
    [
        'question' => 'Warum ist hartes Geld besser als weiches Geld?',
        'options' => ['Es ist härter anzufassen', 'Es kann nicht beliebig vermehrt werden', 'Es ist aus Metall', 'Es ist bunter'],
        'answer' => 'Es kann nicht beliebig vermehrt werden',
        'explanation' => 'Hartes Geld wie Gold oder Bitcoin kann nicht einfach gedruckt werden - das schützt vor Inflation!',
        'emoji' => '🪙'
    ],
    
    // Bitcoin & Freiheit
    [
        'question' => 'Warum bedeutet Bitcoin Freiheit?',
        'options' => ['Man kann damit fliegen', 'Niemand kann es dir wegnehmen', 'Es ist kostenlos', 'Es macht dich zum König'],
        'answer' => 'Niemand kann es dir wegnehmen',
        'explanation' => 'Mit deinem privaten Schlüssel bist nur DU der Besitzer deiner Bitcoin - keine Bank, keine Regierung!',
        'emoji' => '🔐'
    ],
    [
        'question' => 'Was ist ein privater Schlüssel?',
        'options' => ['Ein Türschlüssel', 'Ein Passwort für deine Bitcoin', 'Ein Autoschlüssel', 'Ein Tresorschlüssel'],
        'answer' => 'Ein Passwort für deine Bitcoin',
        'explanation' => 'Der private Schlüssel ist wie ein super-geheimes Passwort - wer ihn hat, kontrolliert die Bitcoin!',
        'emoji' => '🔑'
    ],
    [
        'question' => 'Was bedeutet "Be your own bank"?',
        'options' => ['Eine Bank gründen', 'Dein eigenes Geld kontrollieren', 'In einer Bank arbeiten', 'Geld drucken'],
        'answer' => 'Dein eigenes Geld kontrollieren',
        'explanation' => 'Mit Bitcoin bist du deine eigene Bank - du brauchst niemanden um dein Geld zu verwalten!',
        'emoji' => '🏦'
    ],
    
    // Spieltheorie & Konzepte
    [
        'question' => 'Was ist das "Gefangenendilemma" bei Geld?',
        'options' => ['Geld ist im Gefängnis', 'Länder drucken Geld um Vorteile zu haben', 'Bitcoin ist gefangen', 'Banken sind Gefängnisse'],
        'answer' => 'Länder drucken Geld um Vorteile zu haben',
        'explanation' => 'Länder drucken oft Geld um kurzfristige Vorteile zu haben - aber alle verlieren langfristig!',
        'emoji' => '🎮'
    ],
    [
        'question' => 'Was bedeutet "HODL" in der Bitcoin-Welt?',
        'options' => ['Schnell verkaufen', 'Langfristig behalten', 'Geld ausgeben', 'Bitcoin tauschen'],
        'answer' => 'Langfristig behalten',
        'explanation' => 'HODL bedeutet seine Bitcoin zu behalten und nicht zu verkaufen - es war ursprünglich ein Tippfehler für "HOLD"!',
        'emoji' => '💎'
    ],
    [
        'question' => 'Warum sparen Menschen in Bitcoin?',
        'options' => ['Es ist billig', 'Es schützt vor Inflation', 'Es ist einfach', 'Es ist alt'],
        'answer' => 'Es schützt vor Inflation',
        'explanation' => 'Bitcoin kann nicht einfach vermehrt werden wie Papiergeld - das schützt Ersparnisse vor Wertverlust!',
        'emoji' => '🐖'
    ],
    
    // Philosophie
    [
        'question' => 'Was ist dezentrales Geld?',
        'options' => ['Geld ohne Zentrum', 'Geld mit vielen Zentren', 'Niemand kontrolliert es allein', 'Alles zusammen'],
        'answer' => 'Niemand kontrolliert es allein',
        'explanation' => 'Bitcoin ist dezentral - kein einzelner Mensch oder Staat kann es kontrollieren!',
        'emoji' => '🌐'
    ],
    [
        'question' => 'Warum ist Knappheit wichtig für Wert?',
        'options' => ['Seltene Dinge sind wertvoller', 'Es sieht besser aus', 'Es ist leichter zu zählen', 'Es macht Spaß'],
        'answer' => 'Seltene Dinge sind wertvoller',
        'explanation' => 'Wie Diamanten oder Gold - je seltener etwas ist, desto wertvoller wird es!',
        'emoji' => '💎'
    ],
    [
        'question' => 'Was ist Proof of Work?',
        'options' => ['Ein Arbeitsnachweis', 'Computer lösen schwere Rätsel', 'Energie wird in Sicherheit umgewandelt', 'Alles davon'],
        'answer' => 'Alles davon',
        'explanation' => 'Proof of Work bedeutet, dass Computer Energie aufwenden um Bitcoin sicher zu machen!',
        'emoji' => '⚡'
    ],
    
    // Geschichte des Geldes
    [
        'question' => 'Was wurde früher als Geld verwendet?',
        'options' => ['Nur Gold', 'Muscheln, Steine, Salz', 'Nur Papier', 'Nur Münzen'],
        'answer' => 'Muscheln, Steine, Salz',
        'explanation' => 'Menschen haben viele Dinge als Geld benutzt - sogar riesige Steine auf der Insel Yap!',
        'emoji' => '🐚'
    ],
    [
        'question' => 'Warum ist Bitcoin "digitales Gold"?',
        'options' => ['Es glänzt', 'Es ist gelb', 'Es ist knapp und wertvoll', 'Es ist schwer'],
        'answer' => 'Es ist knapp und wertvoll',
        'explanation' => 'Wie Gold ist Bitcoin begrenzt und kann nicht einfach vermehrt werden!',
        'emoji' => '⚜️'
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
    if (isset($_POST['answer']) && isset($_SESSION['current_bitcoin_question'])) {
        if ($_POST['answer'] === $_SESSION['current_bitcoin_question']['answer']) {
            addAnsweredQuestion('bitcoin', true);
            addScore('bitcoin');
            increaseStreak();
            $feedback = "🎉 Richtig! " . $_SESSION['current_bitcoin_question']['explanation'];
            $feedbackClass = 'correct';
        } else {
            addAnsweredQuestion('bitcoin', false);
            resetStreak();
            $feedback = "Das war leider nicht richtig. " . $_SESSION['current_bitcoin_question']['explanation'];
            $feedbackClass = 'incorrect';
        }
        
        if (!isSessionComplete('bitcoin')) {
            $_SESSION['current_bitcoin_question'] = getRandomQuestion($questions);
        }
        
        $sessionStats = getSessionStats('bitcoin');
    } elseif (!isset($_SESSION['current_bitcoin_question'])) {
        $_SESSION['current_bitcoin_question'] = getRandomQuestion($questions);
    }
}

$currentQuestion = $_SESSION['current_bitcoin_question'] ?? null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitcoin & Geld - sgiT Education</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .bitcoin-bg {
            background: linear-gradient(135deg, #F7931A 0%, #FDB93C 100%);
        }
        .bitcoin-fact {
            background: linear-gradient(135deg, #FFE4B5 0%, #FFDEAD 100%);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>sgiT Education</h1>
        <div class="subtitle">Bitcoin & Hartes Geld verstehen! ₿</div>
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
        <a href="../bitcoin/" class="nav-button active">₿ Bitcoin</a>
        <a href="../profil/" class="nav-button">👤 <?= $username ?></a>
    </nav>

    <div class="container">
        <?php if (!$sessionComplete): ?>
            <div class="score-display bitcoin-bg">
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
                <h2>₿ Bitcoin & Geld-Frage</h2>
                
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

            <div class="bitcoin-fact">
                <h3>₿ Bitcoin-Wissen</h3>
                <?php
                $facts = [
                    "🏦 'Sei deine eigene Bank' - Mit Bitcoin kontrollierst DU dein Geld!",
                    "⚡ Das Lightning-Netzwerk macht Bitcoin-Zahlungen blitzschnell!",
                    "🌍 Bitcoin funktioniert überall auf der Welt - ohne Grenzen!",
                    "🔒 Dein privater Schlüssel ist wie ein Zauberwort - verliere ihn nie!",
                    "📚 Die Österreichische Schule lehrt uns über ehrliches Geld!",
                    "💰 Gutes Geld sollte: Selten, Teilbar, Haltbar und Transportierbar sein!",
                    "🎯 Bitcoin löst das Problem des doppelten Ausgebens digital!",
                    "🌱 Je früher du über Geld lernst, desto besser für deine Zukunft!",
                    "🗽 Bitcoin ist Freiheitstechnologie - niemand kann es stoppen!",
                    "⏰ Alle 10 Minuten wird ein neuer Bitcoin-Block gefunden!"
                ];
                echo "<p>" . $facts[array_rand($facts)] . "</p>";
                ?>
            </div>

        <?php else: ?>
            <div class="content-box" style="text-align: center;">
                <h2>🎉 Super! Alle 10 Bitcoin-Fragen beantwortet!</h2>
                
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
                        echo "🏆 Exzellent! Du bist ein Bitcoin-Experte!";
                    } elseif ($sessionStats['percentage'] >= 70) {
                        echo "⚡ Sehr gut! Du verstehst hartes Geld!";
                    } elseif ($sessionStats['percentage'] >= 50) {
                        echo "💪 Gut gemacht! Weiter lernen über Geld!";
                    } else {
                        echo "📚 Übung macht den Meister! Bitcoin ist spannend!";
                    }
                    ?>
                </div>

                <div style="margin-top: 40px;">
                    <h3>Was möchtest du als nächstes tun?</h3>
                    <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="new_session" value="1" class="btn bitcoin-bg">
                                🔄 Weitere 10 Bitcoin-Fragen
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
            saveStats('bitcoin', $sessionStats);
            ?>
        <?php endif; ?>

        <div class="content-box" style="margin-top: 30px;">
            <h3>Deine Bitcoin-Gesamtpunkte</h3>
            <div style="text-align: center; font-size: 2em; color: #F7931A;">
                ₿ <?= getScore('bitcoin') ?> Punkte
            </div>
        </div>
        
        <!-- Bitcoin Weisheit -->
        <div class="content-box" style="margin-top: 20px; background: #FFF8DC;">
            <h4>💡 Wichtige Bitcoin-Regel:</h4>
            <p style="font-style: italic; color: #8B4513;">
                "Not your keys, not your coins!" - Nur wenn du den privaten Schlüssel hast, gehören dir die Bitcoin wirklich!
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