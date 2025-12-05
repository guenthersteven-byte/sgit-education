<?php
/**
 * Update-Script: Fügt Erklärungen zu allen Fallback-Fragen hinzu
 * Einmalig ausführen: http://localhost/Education/update_fallback_erklaerungen.php
 */

$dbPath = __DIR__ . '/AI/data/questions.db';
$db = new SQLite3($dbPath);

// Erklärungs-Mapping für bestehende Fallback-Fragen
$erklaerungen = [
    // KUNST
    'Welche Farbe entsteht aus Rot und Gelb?' => 'Rot und Gelb sind warme Farben, gemischt ergeben sie Orange.',
    'Welche Farbe entsteht aus Rot und Blau?' => 'Rot und Blau gemischt ergibt die Farbe Lila (Violett).',
    'Wer malte die Mona Lisa?' => 'Leonardo da Vinci malte die Mona Lisa vor etwa 500 Jahren in Italien.',
    'Was sind die drei Primärfarben?' => 'Aus Rot, Gelb und Blau kann man alle anderen Farben mischen.',
    'Was ist ein Portrait?' => 'Ein Portrait zeigt das Gesicht oder die Gestalt einer Person.',
    
    // BIOLOGIE
    'Wie viele Beine hat eine Spinne?' => 'Spinnen sind keine Insekten, sie haben immer 8 Beine.',
    'Was trinken Pflanzen?' => 'Pflanzen nehmen Wasser durch ihre Wurzeln auf.',
    'Was brauchen Pflanzen zum Wachsen?' => 'Pflanzen brauchen Sonnenlicht für die Photosynthese und Wasser für Nährstoffe.',
    'Wie viele Knochen hat ein erwachsener Mensch?' => 'Erwachsene haben etwa 206 Knochen, Babys haben mehr.',
    'Wie heißt der Prozess wenn Pflanzen Sonnenlicht nutzen?' => 'Bei der Photosynthese nutzen Pflanzen Licht um Zucker herzustellen.',
    
    // CHEMIE
    'Was ist Wasser: fest, flüssig oder gasförmig?' => 'Bei normaler Temperatur ist Wasser flüssig.',
    'Was passiert wenn Wasser gefriert?' => 'Unter 0°C wird Wasser fest und wird zu Eis.',
    'Woraus besteht Wasser chemisch?' => 'H2O bedeutet 2 Wasserstoff-Atome und 1 Sauerstoff-Atom.',
    'Was ist das chemische Symbol für Gold?' => 'Au kommt vom lateinischen Wort Aurum für Gold.',
    'Was ist H2O?' => 'H2O ist die chemische Formel für Wasser.',
    
    // ERDKUNDE
    'Was ist die Hauptstadt von Deutschland?' => 'Berlin ist seit 1990 wieder die Hauptstadt von ganz Deutschland.',
    'Wie heißt der größte Ozean?' => 'Der Pazifik bedeckt fast ein Drittel der Erdoberfläche.',
    'Auf welchem Kontinent liegt Deutschland?' => 'Deutschland liegt in Mitteleuropa auf dem europäischen Kontinent.',
    'Wie viele Kontinente gibt es?' => 'Die 7 Kontinente sind Europa, Asien, Afrika, Nord- und Südamerika, Australien und Antarktis.',
    'Was ist der längste Fluss Europas?' => 'Die Wolga fließt durch Russland und ist 3.530 km lang.',
    
    // GESCHICHTE
    'Wer waren die Ritter?' => 'Ritter waren Krieger im Mittelalter, die auf Pferden kämpften.',
    'Wo lebten die alten Ägypter?' => 'Die Ägypter lebten am Fluss Nil in Nordafrika.',
    'Wann endete der Zweite Weltkrieg?' => 'Am 8. Mai 1945 kapitulierte Deutschland und der Krieg war zu Ende.',
    'Wer war der erste deutsche Bundeskanzler?' => 'Konrad Adenauer wurde 1949 erster Bundeskanzler der Bundesrepublik.',
    'Wann fiel die Berliner Mauer?' => 'Am 9. November 1989 öffneten sich die Grenzen zwischen Ost und West.',
    
    // MUSIK
    'Mit welchem Instrument macht man Musik mit Tasten?' => 'Das Klavier hat schwarze und weiße Tasten die man drückt.',
    'Wie viele Saiten hat eine Gitarre?' => 'Eine normale Gitarre hat 6 Saiten von dick bis dünn.',
    'Welches Instrument hat 88 Tasten?' => 'Ein Klavier hat 52 weiße und 36 schwarze Tasten, zusammen 88.',
    'Wie viele Noten gibt es in einer Oktave?' => 'Eine Oktave hat 8 Töne: Do, Re, Mi, Fa, Sol, La, Si, Do.',
    'Wer komponierte die 9. Symphonie mit der Ode an die Freude?' => 'Beethoven schrieb sie 1824, obwohl er schon taub war.',
    
    // COMPUTER
    'Womit klickt man am Computer?' => 'Mit der Maus bewegt man den Zeiger und klickt auf Dinge.',
    'Womit tippt man am Computer?' => 'Die Tastatur hat Buchstaben und Zahlen zum Tippen.',
    'Wofür steht CPU?' => 'CPU bedeutet Central Processing Unit, das Gehirn des Computers.',
    'Was ist RAM?' => 'RAM ist der Arbeitsspeicher, der Daten nur speichert solange der PC an ist.',
    'Was ist ein Browser?' => 'Browser wie Chrome oder Firefox zeigen Webseiten im Internet an.',
    
    // PROGRAMMIEREN
    'Was macht ein Computer mit Code?' => 'Der Computer liest den Code und macht genau was dort steht.',
    'Was ist ein Programm?' => 'Ein Programm ist eine Liste von Befehlen für den Computer.',
    'Was ist eine Variable?' => 'Eine Variable ist wie eine Box die einen Wert speichert.',
    'Welche Programmiersprache hat eine Schlange als Logo?' => 'Python ist nach Monty Python benannt und hat eine Schlange als Logo.',
    'Was macht eine For-Schleife?' => 'Eine For-Schleife wiederholt Befehle eine bestimmte Anzahl mal.',
    
    // BITCOIN
    'Was ist Bitcoin?' => 'Bitcoin ist digitales Geld das nur im Internet existiert.',
    'Kann man Bitcoin anfassen?' => 'Bitcoin existiert nur digital, man kann es nicht anfassen.',
    'Wie viele Bitcoin gibt es maximal?' => 'Es werden niemals mehr als 21 Millionen Bitcoin existieren.',
    'Braucht man eine Bank für Bitcoin?' => 'Bitcoin funktioniert ohne Banken, direkt von Person zu Person.',
    'Was ist ein Bitcoin-Wallet?' => 'Ein Wallet speichert die Schlüssel zu deinen Bitcoins.',
    
    // STEUERN
    'Was sind Steuern?' => 'Steuern sind Geld das Bürger an den Staat zahlen.',
    'Wer bekommt die Steuern?' => 'Der Staat sammelt Steuern und bezahlt damit öffentliche Dinge.',
    'Wofür werden Steuern verwendet?' => 'Mit Steuern werden Schulen, Straßen und Krankenhäuser bezahlt.',
    'Was ist Mehrwertsteuer?' => 'Die Mehrwertsteuer ist im Preis von Waren enthalten, meist 19%.',
    'Was ist Einkommensteuer?' => 'Die Einkommensteuer wird vom Gehalt oder Lohn abgezogen.',
    
    // ENGLISCH
    'What color is the sky?' => 'The sky appears blue because of how light scatters.',
    'What is "Hund" in English?' => 'Hund heißt auf Englisch dog, gesprochen wie dawg.',
    'How do you say "Danke" in English?' => 'Thank you ist die höfliche Art Danke zu sagen.',
    'What is the plural of "child"?' => 'Child ist unregelmäßig: child-children, nicht childs.',
    'What is the past tense of "go"?' => 'Go ist unregelmäßig: go-went-gone.',
    
    // LESEN
    'Welcher Buchstabe kommt nach A?' => 'Das Alphabet geht A-B-C, also kommt B nach A.',
    'Wie viele Buchstaben hat das Alphabet?' => 'Das deutsche Alphabet hat 26 Buchstaben von A bis Z.',
    'Wie viele Silben hat das Wort "Banane"?' => 'Ba-na-ne hat drei Silben, man kann klatschen zum Zählen.',
    'Was ist ein Nomen?' => 'Nomen sind Namenwörter wie Haus, Hund oder Blume.',
    'Was ist ein Verb?' => 'Verben sind Tunwörter wie laufen, spielen oder essen.',
    
    // WISSENSCHAFT
    'Warum fällt ein Apfel vom Baum?' => 'Die Schwerkraft der Erde zieht alle Dinge nach unten.',
    'Warum regnet es?' => 'Wassertropfen in Wolken werden zu schwer und fallen.',
    'Warum ist der Himmel blau?' => 'Sonnenlicht wird in der Atmosphäre gestreut, blau am stärksten.',
    'Was ist ein Experiment?' => 'Ein Experiment testet eine Vermutung durch Versuche.',
    'Was ist die wissenschaftliche Methode?' => 'Man beobachtet, stellt Vermutungen auf, testet sie und zieht Schlüsse.',
    
    // VERKEHR
    'Was tust du bei einer roten Ampel?' => 'Bei Rot müssen alle anhalten, bei Grün darf man gehen.',
    'Wer hat am Zebrastreifen Vorrang?' => 'Fußgänger haben am Zebrastreifen immer Vorrang vor Autos.',
    'Was bedeutet ein Stoppschild?' => 'Man muss komplett anhalten und schauen ob frei ist.',
    'Wie schnell darf man innerorts maximal fahren?' => 'In Städten und Dörfern gilt meist 50 km/h.',
    'Ab wann darf man begleitet Auto fahren?' => 'Mit 17 Jahren darf man mit Begleitperson fahren (BF17).'
];

// Spalte hinzufügen falls nicht vorhanden
try {
    $db->exec('ALTER TABLE questions ADD COLUMN erklaerung TEXT');
    echo "✅ Spalte 'erklaerung' hinzugefügt<br>";
} catch (Exception $e) {
    echo "ℹ️ Spalte 'erklaerung' existiert bereits<br>";
}

// Erklärungen einfügen
$updated = 0;
foreach ($erklaerungen as $frage => $erklaerung) {
    $stmt = $db->prepare('UPDATE questions SET erklaerung = :erklaerung WHERE question = :frage AND (erklaerung IS NULL OR erklaerung = "")');
    $stmt->bindValue(':erklaerung', $erklaerung, SQLITE3_TEXT);
    $stmt->bindValue(':frage', $frage, SQLITE3_TEXT);
    $result = $stmt->execute();
    if ($db->changes() > 0) {
        $updated++;
    }
}

echo "<h2>✅ Update abgeschlossen!</h2>";
echo "<p>$updated Fragen mit Erklärungen aktualisiert.</p>";
echo "<p><a href='windows_ai_generator.php'>→ Zurück zum Generator</a></p>";
