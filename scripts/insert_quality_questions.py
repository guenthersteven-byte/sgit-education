#!/usr/bin/env python3
"""Insert high-quality, manually curated questions for Sport and Unnuetzes Wissen."""

import sqlite3
import json
import hashlib
from pathlib import Path
from datetime import datetime

DB_PATH = Path(__file__).parent.parent / "AI" / "data" / "questions.db"

SPORT_QUESTIONS = [
    # === Kinder (5-8), difficulty 1-2 ===
    {"q": "Wie viele Spieler hat eine Fußballmannschaft auf dem Feld?", "a": "11", "opts": ["11", "9", "7", "15"], "e": "Eine Fußballmannschaft spielt mit 11 Spielern, inklusive Torwart.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Welche Farbe hat ein Tennisball meistens?", "a": "Gelb", "opts": ["Gelb", "Rot", "Blau", "Weiß"], "e": "Tennisbälle sind meistens gelb-grün, damit man sie besser sehen kann.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Was braucht man zum Schwimmen?", "a": "Wasser", "opts": ["Wasser", "Sand", "Schnee", "Wind"], "e": "Zum Schwimmen braucht man Wasser - im Schwimmbad, See oder Meer.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Welches Tier ist das Symbol der Olympischen Spiele NICHT?", "a": "Schlange", "opts": ["Schlange", "Adler", "Bär", "Hase"], "e": "Olympia-Maskottchen sind oft süße Tiere, aber Schlangen waren noch nie dabei.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Was macht man beim Weitsprung?", "a": "So weit wie möglich springen", "opts": ["So weit wie möglich springen", "So hoch wie möglich springen", "So schnell wie möglich laufen", "Einen Ball werfen"], "e": "Beim Weitsprung springt man nach einem Anlauf so weit wie möglich.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Womit spielt man Basketball?", "a": "Mit einem großen orangenen Ball", "opts": ["Mit einem großen orangenen Ball", "Mit einem kleinen weißen Ball", "Mit einem Schläger", "Mit einem Puck"], "e": "Basketball spielt man mit einem großen orangefarbenen Ball.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Was trägt man auf dem Kopf beim Fahrradfahren?", "a": "Einen Helm", "opts": ["Einen Helm", "Eine Mütze", "Einen Hut", "Nichts"], "e": "Ein Helm schützt den Kopf bei einem Sturz - immer tragen!", "d": 1, "amin": 5, "amax": 8},
    {"q": "Wie heißt es, wenn der Ball beim Fußball ins Tor geht?", "a": "Tor", "opts": ["Tor", "Punkt", "Korb", "Treffer"], "e": "Wenn der Ball die Torlinie überquert, ist es ein Tor!", "d": 1, "amin": 5, "amax": 8},
    {"q": "Welche Sportart macht man im Wasser?", "a": "Schwimmen", "opts": ["Schwimmen", "Tennis", "Fußball", "Turnen"], "e": "Schwimmen ist die bekannteste Sportart im Wasser.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Was benutzt man beim Tischtennis?", "a": "Einen kleinen Schläger und einen Ball", "opts": ["Einen kleinen Schläger und einen Ball", "Einen großen Schläger und einen Ball", "Nur die Hände", "Einen Stock"], "e": "Tischtennis spielt man mit kleinen Schlägern und einem leichten Plastikball.", "d": 2, "amin": 5, "amax": 8},
    {"q": "Wie viele Ringe hat das Olympische Symbol?", "a": "5", "opts": ["5", "4", "6", "3"], "e": "Die 5 Ringe stehen für die 5 Kontinente der Erde.", "d": 2, "amin": 5, "amax": 8},
    {"q": "Was ist ein Elfmeter beim Fußball?", "a": "Ein Strafstoß vom Elfmeterpunkt", "opts": ["Ein Strafstoß vom Elfmeterpunkt", "Ein Freistoß von der Mittellinie", "Ein Einwurf", "Ein Eckball"], "e": "Der Elfmeter wird von einem Punkt 11 Meter vor dem Tor geschossen.", "d": 2, "amin": 5, "amax": 8},
    {"q": "Welche Sportart macht man auf Eis mit Schlittschuhen?", "a": "Eislaufen", "opts": ["Eislaufen", "Skifahren", "Rodeln", "Snowboarden"], "e": "Beim Eislaufen gleitet man mit Schlittschuhen über die Eisfläche.", "d": 2, "amin": 5, "amax": 8},
    {"q": "Was ist das Ziel beim Wettlauf?", "a": "Als Erster im Ziel sein", "opts": ["Als Erster im Ziel sein", "Am langsamsten laufen", "Rückwärts laufen", "Stehen bleiben"], "e": "Beim Wettlauf gewinnt, wer als Erster die Ziellinie überquert.", "d": 1, "amin": 5, "amax": 8},

    # === Grundschule (8-11), difficulty 3-4 ===
    {"q": "Wie lang ist ein Marathon?", "a": "42,195 Kilometer", "opts": ["42,195 Kilometer", "30 Kilometer", "50 Kilometer", "21 Kilometer"], "e": "Ein Marathon ist genau 42,195 km lang - eine der härtesten Laufdisziplinen.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Welches Land hat die Fußball-WM 2014 gewonnen?", "a": "Deutschland", "opts": ["Deutschland", "Brasilien", "Argentinien", "Spanien"], "e": "Deutschland gewann 2014 in Brasilien mit 1:0 gegen Argentinien.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Wie heißt die höchste Spielklasse im deutschen Fußball?", "a": "Bundesliga", "opts": ["Bundesliga", "Regionalliga", "Champions League", "Kreisliga"], "e": "Die Bundesliga ist die erste und höchste deutsche Fußball-Liga.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Wie oft finden die Olympischen Sommerspiele statt?", "a": "Alle 4 Jahre", "opts": ["Alle 4 Jahre", "Jedes Jahr", "Alle 2 Jahre", "Alle 6 Jahre"], "e": "Olympische Sommerspiele finden alle 4 Jahre statt.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Welche Sportart wird mit einem Federball gespielt?", "a": "Badminton", "opts": ["Badminton", "Tennis", "Squash", "Volleyball"], "e": "Badminton spielt man mit einem Federball (Shuttlecock) und Schlägern.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Was bedeutet 'Abseits' beim Fußball?", "a": "Ein Spieler steht hinter dem letzten Verteidiger", "opts": ["Ein Spieler steht hinter dem letzten Verteidiger", "Der Ball ist im Aus", "Ein Spieler bekommt eine rote Karte", "Das Spiel wird unterbrochen"], "e": "Abseits ist, wenn ein Angreifer bei der Ballabgabe hinter dem letzten Verteidiger steht.", "d": 4, "amin": 8, "amax": 11},
    {"q": "Wie viele Sätze muss man beim Tennis gewinnen, um ein Match zu gewinnen (Herren, Grand Slam)?", "a": "3 von 5", "opts": ["3 von 5", "2 von 3", "4 von 6", "1 von 2"], "e": "Bei Grand-Slam-Turnieren der Herren gewinnt man mit 3 gewonnenen Sätzen.", "d": 4, "amin": 8, "amax": 11},
    {"q": "Welches Land ist für den Sport Cricket besonders bekannt?", "a": "England", "opts": ["England", "Deutschland", "Brasilien", "Kanada"], "e": "Cricket wurde in England erfunden und ist dort Nationalsport.", "d": 4, "amin": 8, "amax": 11},
    {"q": "Was ist ein Hattrick im Fußball?", "a": "Drei Tore von einem Spieler in einem Spiel", "opts": ["Drei Tore von einem Spieler in einem Spiel", "Drei Siege in Folge", "Drei rote Karten", "Drei Ecken hintereinander"], "e": "Ein Hattrick bedeutet, dass ein Spieler in einem Spiel dreimal trifft.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Wie heißt die Sportart, bei der man mit Pfeilen auf eine Scheibe wirft?", "a": "Darts", "opts": ["Darts", "Bogenschießen", "Speerwurf", "Diskuswurf"], "e": "Beim Darts wirft man kleine Pfeile auf eine runde Scheibe mit Zahlenfeldern.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Welcher Schwimmstil gilt als der schnellste?", "a": "Kraulschwimmen (Freistil)", "opts": ["Kraulschwimmen (Freistil)", "Brustschwimmen", "Rückenschwimmen", "Schmetterling"], "e": "Kraulen ist der schnellste Schwimmstil und wird bei Freistil-Rennen verwendet.", "d": 4, "amin": 8, "amax": 11},
    {"q": "Wie hoch ist ein Basketballkorb?", "a": "3,05 Meter", "opts": ["3,05 Meter", "2,50 Meter", "3,50 Meter", "4,00 Meter"], "e": "Der Basketballkorb hängt auf genau 3,05 Metern Höhe (10 Fuß).", "d": 4, "amin": 8, "amax": 11},
    {"q": "Aus welchem Land kommt die Sportart Judo?", "a": "Japan", "opts": ["Japan", "China", "Korea", "Thailand"], "e": "Judo wurde 1882 in Japan von Jigoro Kano entwickelt.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Was passiert bei einer 'Gelben Karte' im Fußball?", "a": "Der Spieler erhält eine Verwarnung", "opts": ["Der Spieler erhält eine Verwarnung", "Der Spieler muss das Feld verlassen", "Das Spiel wird abgebrochen", "Es gibt einen Elfmeter"], "e": "Die gelbe Karte ist eine Verwarnung. Bei zwei Gelben gibt es Rot.", "d": 3, "amin": 8, "amax": 11},

    # === Mittelstufe (11-14), difficulty 5-7 ===
    {"q": "Wer hält den Weltrekord im 100-Meter-Sprint (Stand 2024)?", "a": "Usain Bolt", "opts": ["Usain Bolt", "Carl Lewis", "Tyson Gay", "Yohan Blake"], "e": "Usain Bolt lief 2009 die 100 Meter in 9,58 Sekunden - Weltrekord.", "d": 5, "amin": 11, "amax": 14},
    {"q": "In welcher Stadt fanden die Olympischen Spiele 2024 statt?", "a": "Paris", "opts": ["Paris", "Los Angeles", "Tokio", "London"], "e": "Die Olympischen Sommerspiele 2024 fanden in Paris, Frankreich statt.", "d": 5, "amin": 11, "amax": 14},
    {"q": "Wie nennt man die Viererkette im Fußball?", "a": "Eine Abwehrformation mit 4 Verteidigern", "opts": ["Eine Abwehrformation mit 4 Verteidigern", "4 Stürmer nebeneinander", "4 Schiedsrichter auf dem Platz", "4 Auswechslungen pro Halbzeit"], "e": "Die Viererkette ist eine defensive Aufstellung mit 4 Verteidigern in einer Reihe.", "d": 6, "amin": 11, "amax": 14},
    {"q": "Welches Grand-Slam-Turnier wird auf Rasen gespielt?", "a": "Wimbledon", "opts": ["Wimbledon", "French Open", "Australian Open", "US Open"], "e": "Wimbledon in London ist das einzige Grand-Slam-Turnier auf Rasen.", "d": 5, "amin": 11, "amax": 14},
    {"q": "Was ist der BMI?", "a": "Body Mass Index - ein Maß für das Verhältnis von Gewicht zu Größe", "opts": ["Body Mass Index - ein Maß für das Verhältnis von Gewicht zu Größe", "Basic Movement Index - ein Fitnesstest", "Bundesliga Match Information", "Balance Measurement Indicator"], "e": "Der BMI berechnet sich aus Körpergewicht geteilt durch Körpergröße zum Quadrat.", "d": 6, "amin": 11, "amax": 14},
    {"q": "Welche Mannschaft hat die meisten Champions-League-Titel gewonnen?", "a": "Real Madrid", "opts": ["Real Madrid", "FC Barcelona", "AC Mailand", "FC Bayern München"], "e": "Real Madrid hat mit 15 Titeln die meisten Champions-League-Siege.", "d": 5, "amin": 11, "amax": 14},
    {"q": "Was ist VO2max?", "a": "Die maximale Sauerstoffaufnahme des Körpers", "opts": ["Die maximale Sauerstoffaufnahme des Körpers", "Die maximale Herzfrequenz", "Die maximale Laufgeschwindigkeit", "Der maximale Blutdruck"], "e": "VO2max misst, wie viel Sauerstoff der Körper bei maximaler Belastung nutzen kann.", "d": 7, "amin": 11, "amax": 14},
    {"q": "Wie viele Spieler stehen beim Volleyball auf jeder Seite?", "a": "6", "opts": ["6", "5", "7", "8"], "e": "Beim Volleyball spielen auf jeder Seite des Netzes 6 Spieler.", "d": 5, "amin": 11, "amax": 14},
    {"q": "Was bedeutet 'Doping' im Sport?", "a": "Die Einnahme verbotener leistungssteigernder Substanzen", "opts": ["Die Einnahme verbotener leistungssteigernder Substanzen", "Besonders hartes Training", "Eine spezielle Ernährungsform", "Mentales Training"], "e": "Doping ist die verbotene Nutzung von Substanzen zur Leistungssteigerung.", "d": 6, "amin": 11, "amax": 14},
    {"q": "Welcher Fußballspieler hat die meisten Tore in der Geschichte erzielt?", "a": "Cristiano Ronaldo", "opts": ["Cristiano Ronaldo", "Lionel Messi", "Pelé", "Robert Lewandowski"], "e": "Cristiano Ronaldo hält den Rekord für die meisten Tore im Profifußball.", "d": 6, "amin": 11, "amax": 14},
    {"q": "Was ist der Unterschied zwischen Sprint und Langstrecke?", "a": "Sprint ist kurz und schnell, Langstrecke ist lang und ausdauernd", "opts": ["Sprint ist kurz und schnell, Langstrecke ist lang und ausdauernd", "Sprint läuft man rückwärts", "Langstrecke läuft man im Kreis", "Es gibt keinen Unterschied"], "e": "Sprint umfasst Strecken bis 400m, Langstrecke beginnt ab 3000m.", "d": 5, "amin": 11, "amax": 14},
    {"q": "In welcher Sportart gibt es den Begriff 'Ace'?", "a": "Tennis", "opts": ["Tennis", "Fußball", "Basketball", "Schwimmen"], "e": "Ein Ace ist ein direkter Punktgewinn durch den Aufschlag beim Tennis.", "d": 5, "amin": 11, "amax": 14},
    {"q": "Wie lange dauert eine Halbzeit im Fußball?", "a": "45 Minuten", "opts": ["45 Minuten", "30 Minuten", "60 Minuten", "40 Minuten"], "e": "Eine Fußball-Halbzeit dauert 45 Minuten, dazu kommt die Nachspielzeit.", "d": 5, "amin": 11, "amax": 14},

    # === Oberstufe/Erwachsene (14+), difficulty 8-10 ===
    {"q": "Was ist die anaerobe Schwelle?", "a": "Der Punkt, ab dem der Körper mehr Laktat produziert als abbaut", "opts": ["Der Punkt, ab dem der Körper mehr Laktat produziert als abbaut", "Die maximale Herzfrequenz", "Der Ruhepuls eines Athleten", "Die Grenze der Sauerstoffaufnahme"], "e": "An der anaeroben Schwelle überschreitet die Laktatproduktion den Abbau.", "d": 8, "amin": 14, "amax": 99},
    {"q": "Welches Prinzip beschreibt die progressive Überlastung im Training?", "a": "Regelmäßige Steigerung der Trainingsbelastung für Leistungszuwachs", "opts": ["Regelmäßige Steigerung der Trainingsbelastung für Leistungszuwachs", "Immer gleiche Belastung beibehalten", "Belastung jede Woche halbieren", "Training nur an Wochenenden"], "e": "Progressive Überlastung bedeutet: Der Körper passt sich an und braucht stärkere Reize.", "d": 8, "amin": 14, "amax": 99},
    {"q": "Was ist Periodisierung im Sporttraining?", "a": "Die systematische Planung von Trainingsphasen über einen Zeitraum", "opts": ["Die systematische Planung von Trainingsphasen über einen Zeitraum", "Das tägliche Wechseln der Sportart", "Training nur in bestimmten Jahreszeiten", "Die Einteilung in Altersklassen"], "e": "Periodisierung teilt das Training in Phasen ein (Aufbau, Wettkampf, Erholung).", "d": 9, "amin": 14, "amax": 99},
    {"q": "Welcher Muskel ist der größte im menschlichen Körper?", "a": "Gluteus Maximus (Gesäßmuskel)", "opts": ["Gluteus Maximus (Gesäßmuskel)", "Bizeps", "Quadrizeps", "Latissimus Dorsi"], "e": "Der Gluteus Maximus (großer Gesäßmuskel) ist der größte und kräftigste Muskel.", "d": 8, "amin": 14, "amax": 99},
    {"q": "Was versteht man unter 'Superkompensation'?", "a": "Der Körper stellt nach Belastung ein höheres Leistungsniveau her", "opts": ["Der Körper stellt nach Belastung ein höheres Leistungsniveau her", "Extremes Übertraining", "Doppelte Dosis an Nahrungsergänzung", "Zwei Trainingseinheiten am Tag"], "e": "Bei Superkompensation erholt sich der Körper über das Ausgangsniveau hinaus.", "d": 9, "amin": 14, "amax": 99},
    {"q": "Wie viele Kalorien verbrennt man ungefähr bei 30 Minuten Joggen?", "a": "Etwa 250-350 kcal", "opts": ["Etwa 250-350 kcal", "Etwa 50-100 kcal", "Etwa 800-1000 kcal", "Etwa 500-700 kcal"], "e": "30 Minuten Joggen verbrennt je nach Intensität und Gewicht 250-350 kcal.", "d": 8, "amin": 14, "amax": 99},
    {"q": "Was ist HIIT-Training?", "a": "High Intensity Interval Training - Wechsel von hoher und niedriger Belastung", "opts": ["High Intensity Interval Training - Wechsel von hoher und niedriger Belastung", "High Impact Indoor Training", "Heavy Iron Isolation Technique", "Hauptsächlich isoliertes Intervalltraining"], "e": "HIIT wechselt kurze intensive Phasen mit Erholungspausen ab.", "d": 8, "amin": 14, "amax": 99},
]

UNNUETZES_WISSEN_QUESTIONS = [
    # === Kinder (5-8), difficulty 1-2 ===
    {"q": "Welches Tier kann nicht rückwärts laufen?", "a": "Känguru", "opts": ["Känguru", "Hund", "Katze", "Pferd"], "e": "Kängurus können wegen ihres schweren Schwanzes und der Beinform nicht rückwärts laufen.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Wie viele Beine hat eine Spinne?", "a": "8", "opts": ["8", "6", "10", "4"], "e": "Spinnen haben immer genau 8 Beine - deshalb sind sie keine Insekten.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Welches Tier schläft am meisten?", "a": "Koala (bis zu 22 Stunden am Tag)", "opts": ["Koala (bis zu 22 Stunden am Tag)", "Löwe (10 Stunden)", "Hund (12 Stunden)", "Katze (8 Stunden)"], "e": "Koalas schlafen bis zu 22 Stunden am Tag, weil Eukalyptus wenig Energie gibt.", "d": 2, "amin": 5, "amax": 8},
    {"q": "Welche Farbe hat das Blut von Hummern?", "a": "Blau", "opts": ["Blau", "Rot", "Grün", "Gelb"], "e": "Hummerblut enthält Kupfer statt Eisen und ist deshalb blau.", "d": 2, "amin": 5, "amax": 8},
    {"q": "Können Kühe Treppen hochgehen?", "a": "Ja, aber sie können nicht wieder runtergehen", "opts": ["Ja, aber sie können nicht wieder runtergehen", "Nein, gar nicht", "Ja, hoch und runter", "Nur junge Kühe können das"], "e": "Kühe können Treppen hoch, aber ihre Knie erlauben es nicht, sicher runterzugehen.", "d": 2, "amin": 5, "amax": 8},
    {"q": "Was ist das größte Tier der Welt?", "a": "Der Blauwal", "opts": ["Der Blauwal", "Der Elefant", "Die Giraffe", "Der Weißhai"], "e": "Blauwale werden bis zu 33 Meter lang und wiegen so viel wie 40 Elefanten.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Wie viele Nasen hat eine Schnecke?", "a": "4", "opts": ["4", "1", "2", "0"], "e": "Schnecken haben 4 Fühler - die oberen 2 sind Augen, die unteren 2 riechen.", "d": 2, "amin": 5, "amax": 8},
    {"q": "Können Goldfische ihre Augen schließen?", "a": "Nein", "opts": ["Nein", "Ja", "Nur nachts", "Nur unter Wasser"], "e": "Goldfische haben keine Augenlider und können deshalb nie die Augen schließen.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Welches Tier hat die längste Zunge?", "a": "Das Chamäleon", "opts": ["Das Chamäleon", "Der Frosch", "Die Giraffe", "Der Ameisenbär"], "e": "Die Zunge eines Chamäleons ist bis zu doppelt so lang wie sein Körper!", "d": 2, "amin": 5, "amax": 8},
    {"q": "Was ist schwerer: ein Kilo Federn oder ein Kilo Steine?", "a": "Beides wiegt gleich viel", "opts": ["Beides wiegt gleich viel", "Steine", "Federn", "Kommt auf die Größe an"], "e": "Ein Kilo ist immer ein Kilo - egal ob Federn oder Steine!", "d": 2, "amin": 5, "amax": 8},
    {"q": "Schlafen Delfine mit offenen Augen?", "a": "Ja, ein Auge bleibt offen", "opts": ["Ja, ein Auge bleibt offen", "Nein, beide Augen zu", "Sie schlafen gar nicht", "Nur Babydelfine"], "e": "Delfine lassen eine Gehirnhälfte schlafen und ein Auge offen - zur Sicherheit.", "d": 2, "amin": 5, "amax": 8},
    {"q": "Wie viele Herzen hat ein Oktopus?", "a": "3", "opts": ["3", "1", "2", "4"], "e": "Ein Oktopus hat 3 Herzen: eins für den Körper und zwei für die Kiemen.", "d": 2, "amin": 5, "amax": 8},
    {"q": "Welches Tier kann seine Farbe ändern?", "a": "Das Chamäleon", "opts": ["Das Chamäleon", "Der Frosch", "Die Schlange", "Der Papagei"], "e": "Chamäleons ändern ihre Farbe je nach Stimmung, Temperatur und Licht.", "d": 1, "amin": 5, "amax": 8},
    {"q": "Was ist das schnellste Landtier der Welt?", "a": "Der Gepard", "opts": ["Der Gepard", "Der Löwe", "Das Pferd", "Der Wolf"], "e": "Der Gepard erreicht bis zu 120 km/h - schneller als ein Auto in der Stadt!", "d": 1, "amin": 5, "amax": 8},

    # === Grundschule (8-11), difficulty 3-4 ===
    {"q": "Wie viel Prozent der Erde sind mit Wasser bedeckt?", "a": "Etwa 71%", "opts": ["Etwa 71%", "Etwa 50%", "Etwa 30%", "Etwa 90%"], "e": "Rund 71% der Erdoberfläche sind mit Wasser bedeckt, meist Salzwasser.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Welches Lebensmittel verdirbt niemals?", "a": "Honig", "opts": ["Honig", "Reis", "Schokolade", "Salz"], "e": "3000 Jahre alter Honig aus ägyptischen Gräbern war noch genießbar!", "d": 3, "amin": 8, "amax": 11},
    {"q": "Wie oft schlägt das menschliche Herz ungefähr am Tag?", "a": "Etwa 100.000 Mal", "opts": ["Etwa 100.000 Mal", "Etwa 10.000 Mal", "Etwa 1.000 Mal", "Etwa 1.000.000 Mal"], "e": "Das Herz schlägt rund 100.000 Mal am Tag - etwa 70 Mal pro Minute.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Welches Organ im menschlichen Körper ist am größten?", "a": "Die Haut", "opts": ["Die Haut", "Die Leber", "Das Gehirn", "Der Darm"], "e": "Die Haut ist mit ca. 2 Quadratmetern das größte Organ des Menschen.", "d": 4, "amin": 8, "amax": 11},
    {"q": "Können Strauße fliegen?", "a": "Nein, aber sie können sehr schnell laufen", "opts": ["Nein, aber sie können sehr schnell laufen", "Ja, kurze Strecken", "Nur weibliche Strauße", "Nur junge Strauße"], "e": "Strauße können bis zu 70 km/h laufen, sind aber zu schwer zum Fliegen.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Wie lange dauert es, bis Licht von der Sonne zur Erde kommt?", "a": "Etwa 8 Minuten", "opts": ["Etwa 8 Minuten", "Etwa 1 Sekunde", "Etwa 1 Stunde", "Etwa 24 Stunden"], "e": "Sonnenlicht braucht circa 8 Minuten und 20 Sekunden bis zur Erde.", "d": 4, "amin": 8, "amax": 11},
    {"q": "Welches Gas macht den Großteil der Luft aus?", "a": "Stickstoff (78%)", "opts": ["Stickstoff (78%)", "Sauerstoff (78%)", "CO2 (78%)", "Helium (78%)"], "e": "Die Luft besteht zu 78% aus Stickstoff und nur 21% aus Sauerstoff.", "d": 4, "amin": 8, "amax": 11},
    {"q": "Wie viele Knochen hat ein erwachsener Mensch?", "a": "206", "opts": ["206", "300", "150", "180"], "e": "Erwachsene haben 206 Knochen. Babys haben über 300, die zusammenwachsen.", "d": 4, "amin": 8, "amax": 11},
    {"q": "Welches Tier hat den höchsten Blutdruck?", "a": "Die Giraffe", "opts": ["Die Giraffe", "Der Elefant", "Das Pferd", "Der Blauwal"], "e": "Giraffen brauchen hohen Blutdruck, damit das Blut bis zum Kopf gepumpt wird.", "d": 4, "amin": 8, "amax": 11},
    {"q": "Was ist das härteste natürliche Material auf der Erde?", "a": "Diamant", "opts": ["Diamant", "Gold", "Eisen", "Granit"], "e": "Diamant ist das härteste natürliche Material - er kann fast alles zerkratzen.", "d": 3, "amin": 8, "amax": 11},
    {"q": "Welches Land hat die meisten Zeitzonen?", "a": "Frankreich (12 Zeitzonen)", "opts": ["Frankreich (12 Zeitzonen)", "Russland (11 Zeitzonen)", "USA (6 Zeitzonen)", "China (1 Zeitzone)"], "e": "Frankreich hat durch seine Überseegebiete 12 verschiedene Zeitzonen.", "d": 4, "amin": 8, "amax": 11},
    {"q": "Wie viel Wasser enthält eine Gurke ungefähr?", "a": "Etwa 96%", "opts": ["Etwa 96%", "Etwa 50%", "Etwa 75%", "Etwa 30%"], "e": "Gurken bestehen zu etwa 96% aus Wasser - noch mehr als eine Wassermelone!", "d": 3, "amin": 8, "amax": 11},
    {"q": "Welcher Planet in unserem Sonnensystem hat die meisten Monde?", "a": "Saturn", "opts": ["Saturn", "Jupiter", "Uranus", "Neptun"], "e": "Saturn hat über 140 bekannte Monde und damit mehr als jeder andere Planet.", "d": 4, "amin": 8, "amax": 11},

    # === Mittelstufe (11-14), difficulty 5-7 ===
    {"q": "Wie viel Prozent der DNA teilen Menschen mit Bananen?", "a": "Etwa 60%", "opts": ["Etwa 60%", "Etwa 10%", "Etwa 90%", "Etwa 30%"], "e": "Menschen teilen rund 60% ihrer Gene mit Bananen - Evolution verbindet alles.", "d": 5, "amin": 11, "amax": 14},
    {"q": "Welches Tier hat den größten Augapfel?", "a": "Der Koloss-Kalmar", "opts": ["Der Koloss-Kalmar", "Der Blauwal", "Der Elefant", "Das Pferd"], "e": "Der Koloss-Kalmar hat Augen so groß wie Fußbälle (bis 27 cm Durchmesser).", "d": 5, "amin": 11, "amax": 14},
    {"q": "Woraus bestehen Fingernägel?", "a": "Keratin (das gleiche Protein wie in Haaren)", "opts": ["Keratin (das gleiche Protein wie in Haaren)", "Kalzium (wie Knochen)", "Kollagen (wie Haut)", "Chitin (wie Insektenpanzer)"], "e": "Fingernägel bestehen aus Keratin - dem gleichen Protein wie Haare und Hufe.", "d": 6, "amin": 11, "amax": 14},
    {"q": "Wie viele verschiedene Gerüche kann die menschliche Nase unterscheiden?", "a": "Über 1 Billion", "opts": ["Über 1 Billion", "Etwa 10.000", "Etwa 1 Million", "Etwa 100"], "e": "Studien zeigen, dass die Nase über 1 Billion verschiedene Gerüche erkennen kann.", "d": 6, "amin": 11, "amax": 14},
    {"q": "Welches Land hat die Form eines Stiefels?", "a": "Italien", "opts": ["Italien", "Griechenland", "Spanien", "Portugal"], "e": "Italien sieht auf der Karte aus wie ein Stiefel, der einen Ball (Sizilien) tritt.", "d": 5, "amin": 11, "amax": 14},
    {"q": "Was passiert, wenn man im Weltraum weint?", "a": "Die Tränen bleiben als Blase am Auge kleben", "opts": ["Die Tränen bleiben als Blase am Auge kleben", "Die Tränen schweben davon", "Man kann im Weltraum nicht weinen", "Die Tränen verdampfen sofort"], "e": "Ohne Schwerkraft bilden Tränen eine Wasserblase, die am Auge haftet.", "d": 6, "amin": 11, "amax": 14},
    {"q": "Wie lang ist der längste je gemessene Schluckauf?", "a": "68 Jahre", "opts": ["68 Jahre", "10 Jahre", "3 Monate", "1 Jahr"], "e": "Charles Osborne hatte von 1922 bis 1990 ununterbrochen Schluckauf - 68 Jahre!", "d": 6, "amin": 11, "amax": 14},
    {"q": "Welches Tier kann am längsten ohne Wasser überleben?", "a": "Die Kängururatte (ihr ganzes Leben)", "opts": ["Die Kängururatte (ihr ganzes Leben)", "Das Kamel (2 Wochen)", "Die Schildkröte (1 Jahr)", "Der Elefant (5 Tage)"], "e": "Die Kängururatte gewinnt genug Wasser aus Samen und trinkt nie.", "d": 7, "amin": 11, "amax": 14},
    {"q": "Wie viele Liter Speichel produziert ein Mensch im Laufe seines Lebens?", "a": "Etwa 35.000 Liter", "opts": ["Etwa 35.000 Liter", "Etwa 500 Liter", "Etwa 5.000 Liter", "Etwa 100.000 Liter"], "e": "Im Schnitt produziert der Mensch 0,5-1 Liter Speichel am Tag.", "d": 6, "amin": 11, "amax": 14},
    {"q": "Warum ist der Himmel blau?", "a": "Weil blaues Licht stärker von der Atmosphäre gestreut wird", "opts": ["Weil blaues Licht stärker von der Atmosphäre gestreut wird", "Weil das Meer sich im Himmel spiegelt", "Weil die Sonne blaues Licht aussendet", "Weil die Ozonschicht blau ist"], "e": "Blaues Licht hat eine kurze Wellenlänge und wird stärker gestreut (Rayleigh-Streuung).", "d": 7, "amin": 11, "amax": 14},
    {"q": "Was ist die seltenste Blutgruppe?", "a": "AB negativ", "opts": ["AB negativ", "O negativ", "B positiv", "A negativ"], "e": "Nur etwa 1% der Weltbevölkerung hat die Blutgruppe AB negativ.", "d": 6, "amin": 11, "amax": 14},
    {"q": "Wie schnell wachsen Fingernägel pro Monat?", "a": "Etwa 3-4 Millimeter", "opts": ["Etwa 3-4 Millimeter", "Etwa 1 Zentimeter", "Etwa 0,5 Millimeter", "Etwa 1 Millimeter"], "e": "Fingernägel wachsen ca. 3-4 mm pro Monat. Fußnägel wachsen langsamer.", "d": 5, "amin": 11, "amax": 14},
    {"q": "Welcher Ozean ist der größte?", "a": "Der Pazifische Ozean", "opts": ["Der Pazifische Ozean", "Der Atlantische Ozean", "Der Indische Ozean", "Das Südpolarmeer"], "e": "Der Pazifik bedeckt mehr Fläche als alle Landmassen der Erde zusammen.", "d": 5, "amin": 11, "amax": 14},

    # === Oberstufe/Erwachsene (14+), difficulty 8-10 ===
    {"q": "Wie viel wiegt die Erdatmosphäre ungefähr?", "a": "Etwa 5,15 Billiarden Kilogramm", "opts": ["Etwa 5,15 Billiarden Kilogramm", "Etwa 1 Million Tonnen", "Etwa 100 Milliarden Kilogramm", "Etwa 5 Trillionen Kilogramm"], "e": "Die Atmosphäre wiegt ca. 5,15 x 10^18 kg und drückt mit 1 atm auf uns.", "d": 9, "amin": 14, "amax": 99},
    {"q": "Welches Element ist das häufigste im Universum?", "a": "Wasserstoff", "opts": ["Wasserstoff", "Helium", "Sauerstoff", "Kohlenstoff"], "e": "Wasserstoff macht etwa 75% der gesamten Masse im Universum aus.", "d": 8, "amin": 14, "amax": 99},
    {"q": "Wie alt ist das älteste bekannte Lebewesen der Erde?", "a": "Über 5.000 Jahre (eine Langlebige Kiefer)", "opts": ["Über 5.000 Jahre (eine Langlebige Kiefer)", "Etwa 1.000 Jahre (eine Eiche)", "Etwa 500 Jahre (eine Schildkröte)", "Etwa 10.000 Jahre (ein Pilz)"], "e": "Methuselah, eine Langlebige Kiefer in Kalifornien, ist über 5.000 Jahre alt.", "d": 8, "amin": 14, "amax": 99},
    {"q": "Wie viele Atome sind ungefähr in einem menschlichen Körper?", "a": "Etwa 7 Oktillionen (7 x 10^27)", "opts": ["Etwa 7 Oktillionen (7 x 10^27)", "Etwa 1 Billion", "Etwa 1 Billiarde", "Etwa 7 Trilliarden"], "e": "Der menschliche Körper besteht aus ca. 7.000.000.000.000.000.000.000.000.000 Atomen.", "d": 9, "amin": 14, "amax": 99},
    {"q": "Was ist das sogenannte 'Overview-Effekt'?", "a": "Die Bewusstseinsveränderung von Astronauten beim Anblick der Erde aus dem All", "opts": ["Die Bewusstseinsveränderung von Astronauten beim Anblick der Erde aus dem All", "Ein optischer Effekt bei Sonnenuntergängen", "Die Fähigkeit, große Datenmengen zu überblicken", "Ein Phänomen beim Tauchen in großer Tiefe"], "e": "Astronauten berichten von tiefgreifender Ehrfurcht beim Anblick der Erde aus dem Weltraum.", "d": 9, "amin": 14, "amax": 99},
    {"q": "Wie viele Bakterien leben ungefähr im menschlichen Körper?", "a": "Etwa 38 Billionen", "opts": ["Etwa 38 Billionen", "Etwa 1 Million", "Etwa 100 Milliarden", "Etwa 1 Billiarde"], "e": "Im Körper leben rund 38 Billionen Bakterien - mehr als menschliche Zellen.", "d": 8, "amin": 14, "amax": 99},
    {"q": "Was ist der Mpemba-Effekt?", "a": "Heißes Wasser kann unter bestimmten Bedingungen schneller gefrieren als kaltes", "opts": ["Heißes Wasser kann unter bestimmten Bedingungen schneller gefrieren als kaltes", "Kaltes Wasser siedet schneller als heißes", "Salzwasser friert schneller als Süßwasser", "Wasser dehnt sich beim Abkühlen immer aus"], "e": "Der Mpemba-Effekt ist ein physikalisches Phänomen, das noch nicht vollständig erklärt ist.", "d": 10, "amin": 14, "amax": 99},
    {"q": "Wie lang würde die menschliche DNA, wenn man sie aneinanderreihen würde?", "a": "Etwa 600 Mal von der Erde zur Sonne und zurück", "opts": ["Etwa 600 Mal von der Erde zur Sonne und zurück", "Etwa einmal um die Erde", "Etwa bis zum Mond", "Etwa 100 Kilometer"], "e": "Alle DNA eines Menschen aneinandergereiht wäre ca. 200 Milliarden km lang.", "d": 10, "amin": 14, "amax": 99},
]


def insert_questions(conn, module, questions):
    cur = conn.cursor()
    inserted = 0
    skipped = 0

    for q in questions:
        options_json = json.dumps(q["opts"], ensure_ascii=False)
        hash_str = hashlib.md5(
            (q["q"].lower() + "|" + "|".join(sorted(q["opts"]))).encode()
        ).hexdigest()

        # Check for duplicate (hash or question+module)
        cur.execute("SELECT id FROM questions WHERE question_hash = ? OR (question = ? AND module = ?)", (hash_str, q["q"], module))
        if cur.fetchone():
            skipped += 1
            continue

        cur.execute("""
            INSERT INTO questions (
                module, difficulty, age_min, age_max, question, answer, options,
                ai_generated, model_used, source, question_hash, erklaerung, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'claude-opus-4', 'manual_curated', ?, ?, 1)
        """, (module, q["d"], q["amin"], q["amax"], q["q"], q["a"], options_json, hash_str, q["e"]))
        inserted += 1

    return inserted, skipped


def main():
    conn = sqlite3.connect(DB_PATH)

    sport_ins, sport_skip = insert_questions(conn, "sport", SPORT_QUESTIONS)
    uw_ins, uw_skip = insert_questions(conn, "unnuetzes_wissen", UNNUETZES_WISSEN_QUESTIONS)

    conn.commit()

    # Final counts
    cur = conn.cursor()
    cur.execute("SELECT COUNT(*) FROM questions WHERE module='sport' AND is_active=1")
    sport_total = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM questions WHERE module='unnuetzes_wissen' AND is_active=1")
    uw_total = cur.fetchone()[0]

    print(f"Sport:            {sport_ins} eingefuegt, {sport_skip} uebersprungen (Duplikate)")
    print(f"Unnuetzes Wissen: {uw_ins} eingefuegt, {uw_skip} uebersprungen (Duplikate)")
    print(f"")
    print(f"Sport gesamt aktiv:            {sport_total}")
    print(f"Unnuetzes Wissen gesamt aktiv: {uw_total}")

    conn.close()


if __name__ == "__main__":
    main()
