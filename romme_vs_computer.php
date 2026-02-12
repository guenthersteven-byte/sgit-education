<?php
/**
 * ============================================================================
 * sgiT Education - Romme vs Computer v1.0
 * ============================================================================
 *
 * Romme (Rummy) gegen KI-Gegner (3 Schwierigkeitsstufen)
 * Komplett client-seitig, kein Server-Roundtrip
 *
 * KI-Stufen:
 * 1 - Leicht: Zufaellige gueltige Aktionen
 * 2 - Mittel: Sucht aktiv nach Saetzen/Reihen, legt strategisch ab
 * 3 - Schwer: Plant voraus, merkt sich abgelegte Karten, optimiert Hand
 *
 * Regeln:
 * - 2x52 Karten + 2 Joker = 106 Karten
 * - Jeder Spieler bekommt 13 Karten
 * - Saetze: 3-4 gleiche Werte, verschiedene Farben
 * - Reihen: 3+ aufeinanderfolgende gleiche Farbe
 * - Erstauslage: min. 30 Punkte
 * - Danach: Anlegen an bestehende Kombinationen
 * - Gewonnen: Hand leer (letzte Karte ablegen)
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
require_once 'includes/version.php';
require_once __DIR__ . '/wallet/SessionManager.php';

$userName = '';
$userAvatar = '&#128512;';

if (SessionManager::isLoggedIn()) {
    $childData = SessionManager::getChild();
    if ($childData) {
        $userName = $childData['name'];
        $userAvatar = $childData['avatar'] ?? '&#128512;';
    }
} elseif (isset($_SESSION['wallet_child_id'])) {
    $userName = $_SESSION['user_name'] ?? $_SESSION['child_name'] ?? '';
    $userAvatar = $_SESSION['avatar'] ?? '&#128512;';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Romme vs Computer - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <style>
        :root { --card-white: #fffef5; }
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--mp-bg-dark) 0%, var(--mp-primary) 100%);
            min-height: 100vh; color: var(--mp-text); margin: 0; padding: 0;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 15px; }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 20px; background: var(--mp-bg-card); border-radius: 12px; margin-bottom: 20px;
        }
        .header h1 { font-size: 1.4rem; margin: 0; }
        .header h1 span { color: var(--mp-accent); }
        .back-link { color: var(--mp-accent); text-decoration: none; }
        .screen { display: none; }
        .screen.active { display: block; }

        /* Setup */
        .setup-container { max-width: 500px; margin: 30px auto; text-align: center; }
        .setup-card {
            background: var(--mp-bg-card); border-radius: 16px; padding: 25px; margin-bottom: 20px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }
        .setup-card h2 { color: var(--mp-accent); margin-bottom: 15px; }
        .difficulty-grid { display: flex; flex-direction: column; gap: 10px; margin: 15px 0; }
        .diff-option {
            background: var(--mp-bg-dark); border: 2px solid transparent; border-radius: 12px;
            padding: 15px; cursor: pointer; text-align: left; transition: all 0.2s;
        }
        .diff-option:hover { border-color: var(--mp-accent); transform: translateX(5px); }
        .diff-option.selected { border-color: var(--mp-accent); background: rgba(76, 175, 80, 0.1); }
        .diff-option .diff-name { font-weight: 600; font-size: 1.1rem; }
        .diff-option .diff-desc { font-size: 0.85rem; color: var(--mp-text-muted); margin-top: 4px; }
        .btn {
            background: var(--mp-accent); color: var(--mp-bg-dark); border: none;
            padding: 14px 28px; border-radius: 10px; font-size: 1rem; font-weight: 600;
            cursor: pointer; width: 100%; transition: all 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn.secondary { background: var(--mp-bg-card); color: var(--mp-text); border: 2px solid var(--mp-accent); }
        .btn.small { padding: 8px 16px; font-size: 0.9rem; width: auto; }
        .btn.tiny { padding: 4px 10px; font-size: 0.75rem; border-radius: 4px; width: auto; }

        /* Game Area */
        .game-container { display: grid; grid-template-columns: 1fr 240px; gap: 20px; }
        @media (max-width: 900px) { .game-container { grid-template-columns: 1fr; } }
        .play-area {
            background: var(--mp-bg-card); border-radius: 16px; padding: 20px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }

        /* Opponent */
        .opponent {
            background: var(--mp-bg-dark); border-radius: 12px; padding: 12px 20px; text-align: center;
            border: 2px solid transparent; transition: all 0.3s; margin-bottom: 15px;
        }
        .opponent.active { border-color: var(--mp-accent); box-shadow: 0 0 20px rgba(76,175,80,0.3); }
        .opponent .cards { display: flex; justify-content: center; margin-top: 8px; }
        .card-back-small {
            width: 30px; height: 45px; border-radius: 4px; border: 1px solid #555;
            margin-left: -15px; background-size: cover; background-position: center;
        }
        .card-back-small:first-child { margin-left: 0; }

        /* Melds Area */
        .melds-area {
            background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; margin-bottom: 15px; min-height: 100px;
        }
        .melds-area h3 { color: var(--mp-accent); font-size: 0.9rem; margin: 0 0 10px 0; }
        .melds-container { display: flex; gap: 12px; flex-wrap: wrap; min-height: 60px; align-items: flex-start; }
        .meld-group {
            display: flex; padding: 8px; background: rgba(0,0,0,0.3); border-radius: 8px;
            cursor: pointer; transition: all 0.2s; border: 2px solid transparent;
        }
        .meld-group:hover { border-color: var(--mp-accent); }
        .meld-group .meld-card { width: 45px; height: 68px; border-radius: 4px; margin-left: -10px; }
        .meld-group .meld-card:first-child { margin-left: 0; }
        .meld-owner { font-size: 0.65rem; color: var(--mp-text-muted); text-align: center; margin-top: 2px; }

        /* Draw/Discard Piles */
        .piles-area { display: flex; gap: 30px; justify-content: center; align-items: center; margin: 20px 0; }
        .pile { text-align: center; }
        .pile-label { font-size: 0.8rem; color: var(--mp-text-muted); margin-bottom: 5px; }
        .pile-cards { position: relative; width: 70px; height: 100px; margin: 0 auto; cursor: pointer; }
        .pile-cards img { width: 70px; height: 100px; position: absolute; border-radius: 6px; }
        .pile-cards img:nth-child(2) { top: 2px; left: 2px; }
        .pile-cards img:nth-child(3) { top: 4px; left: 4px; }
        .pile-count { font-size: 0.75rem; color: var(--mp-text-muted); margin-top: 5px; }
        .discard-pile-area { position: relative; width: 70px; height: 100px; cursor: pointer; }
        .discard-pile-area img { width: 70px; height: 100px; border-radius: 6px; }
        .discard-empty {
            width: 70px; height: 100px; border: 2px dashed #555; border-radius: 6px;
            display: flex; align-items: center; justify-content: center; color: #555; font-size: 0.7rem;
        }

        /* Hand */
        .hand-area { background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; margin-top: 15px; }
        .hand-area h3 { color: var(--mp-accent); font-size: 0.9rem; margin: 0 0 8px 0; }
        .sort-buttons { display: flex; gap: 8px; margin-bottom: 10px; justify-content: center; }
        .my-hand {
            display: flex; justify-content: center; flex-wrap: wrap; padding: 10px;
            min-height: 80px;
        }
        .card-slot {
            width: 65px; height: 98px; cursor: pointer; transition: all 0.2s;
            position: relative; margin-left: -18px; vertical-align: bottom; display: inline-block;
        }
        .card-slot:first-child { margin-left: 0; }
        .card-slot img {
            width: 65px; height: 98px; border-radius: 6px; pointer-events: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .card-slot:hover { transform: translateY(-8px); z-index: 10; }
        .card-slot.selected { transform: translateY(-18px); z-index: 11; }
        .card-slot.selected img { box-shadow: 0 0 15px var(--mp-accent); }

        /* Actions */
        .actions { display: flex; gap: 10px; justify-content: center; margin-top: 12px; flex-wrap: wrap; }

        /* Sidebar */
        .sidebar { display: flex; flex-direction: column; gap: 15px; }
        .info-card {
            background: var(--mp-bg-card); border-radius: 12px; padding: 15px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }
        .info-card h3 { color: var(--mp-accent); margin: 0 0 10px 0; font-size: 1rem; }
        .turn-info { text-align: center; padding: 15px; background: var(--mp-bg-dark); border-radius: 10px; }
        .turn-info.my-turn { border: 2px solid var(--mp-accent); }
        .turn-info .label { font-size: 0.85rem; color: var(--mp-text-muted); }
        .turn-info .name { font-size: 1.2rem; font-weight: bold; margin-top: 5px; }
        .turn-info .phase { font-size: 0.85rem; color: var(--mp-accent); margin-top: 3px; }
        .thinking { color: var(--mp-accent); font-style: italic; }
        .thinking::after { content: '...'; animation: dots 1.5s infinite; }
        @keyframes dots { 0%{content:'.'} 33%{content:'..'} 66%{content:'...'} }

        .player-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px; background: var(--mp-bg-dark); border-radius: 8px; margin-bottom: 6px; font-size: 0.9rem;
        }
        .player-row.active { border: 2px solid var(--mp-accent); }

        /* Toast */
        .toast {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            padding: 12px 20px; border-radius: 12px; font-weight: 600; z-index: 1000;
            animation: toastIn 0.3s ease-out;
        }
        .toast.success { background: var(--mp-accent); color: var(--mp-bg-dark); }
        .toast.error { background: var(--mp-error); color: white; }
        .toast.info { background: var(--mp-bg-card); border: 2px solid var(--mp-accent); color: var(--mp-text); }
        @keyframes toastIn { from { opacity: 0; transform: translateX(-50%) translateY(20px); } to { opacity: 1; transform: translateX(-50%) translateY(0); } }

        /* Result */
        .result-card {
            background: var(--mp-bg-card); border-radius: 16px; padding: 30px; text-align: center;
            border: 3px solid var(--mp-accent);
        }

        /* Modal */
        .modal {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 100;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--mp-bg-card); border-radius: 16px; padding: 25px; text-align: center;
            max-width: 400px; width: 90%;
        }
        .modal h2 { margin-bottom: 15px; color: var(--mp-accent); }
        .joker-assign-list { display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow-y: auto; }
        .joker-assign-option {
            background: var(--mp-bg-dark); border: 2px solid transparent; border-radius: 8px;
            padding: 10px; cursor: pointer; text-align: left; transition: all 0.2s;
        }
        .joker-assign-option:hover { border-color: var(--mp-accent); }

        /* Mobile */
        @media (max-width: 600px) {
            .card-slot { margin-left: -25px; }
            .card-slot img { width: 50px; height: 75px; }
            .card-slot { width: 50px; height: 75px; }
            .card-slot:hover { transform: translateY(-5px); }
            .card-slot.selected { transform: translateY(-10px); }
            .meld-group .meld-card { width: 35px; height: 53px; }
            .pile-cards, .pile-cards img { width: 55px; height: 80px; }
            .discard-pile-area, .discard-pile-area img { width: 55px; height: 80px; }
            .discard-empty { width: 55px; height: 80px; }
        }
        @media (max-width: 400px) {
            .card-slot img { width: 42px; height: 63px; }
            .card-slot { width: 42px; height: 63px; margin-left: -20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="romme.php" class="back-link">&larr; Romme</a>
                <h1>&#127924; <span>Romme</span> vs Computer</h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>

        <!-- SETUP -->
        <div id="setupScreen" class="screen active">
            <div class="setup-container">
                <div style="font-size: 4rem; margin-bottom: 10px;">&#127924;</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Romme vs Computer</h1>
                <p style="color: var(--mp-text-muted); margin-bottom: 25px;">Spiele Romme gegen eine KI</p>

                <div class="setup-card">
                    <h2>Schwierigkeit</h2>
                    <div class="difficulty-grid">
                        <div class="diff-option selected" onclick="selectDifficulty(1, this)">
                            <div class="diff-name">Leicht</div>
                            <div class="diff-desc">KI spielt zufaellige gueltige Aktionen</div>
                        </div>
                        <div class="diff-option" onclick="selectDifficulty(2, this)">
                            <div class="diff-name">Mittel</div>
                            <div class="diff-desc">KI sucht aktiv nach Saetzen und Reihen</div>
                        </div>
                        <div class="diff-option" onclick="selectDifficulty(3, this)">
                            <div class="diff-name">Schwer</div>
                            <div class="diff-desc">KI plant voraus, merkt sich Karten, optimiert</div>
                        </div>
                    </div>
                </div>

                <button class="btn" onclick="startGame()">Spiel starten</button>
                <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='romme.php'">&larr; Zurueck zu Multiplayer</button>
            </div>
        </div>

        <!-- GAME -->
        <div id="gameScreen" class="screen">
            <div class="game-container">
                <div class="play-area">
                    <!-- Opponent hand -->
                    <div class="opponent" id="opponentArea">
                        <div>&#129302; Computer</div>
                        <div class="cards" id="opponentCards"></div>
                        <div style="font-size: 0.8rem; color: var(--mp-text-muted);" id="opponentCount">13 Karten</div>
                    </div>

                    <!-- Table melds -->
                    <div class="melds-area">
                        <h3>Ausgelegte Kombinationen</h3>
                        <div class="melds-container" id="meldsContainer">
                            <span style="color:var(--mp-text-muted);font-size:0.85rem;">Noch keine Kombinationen</span>
                        </div>
                    </div>

                    <!-- Draw / Discard piles -->
                    <div class="piles-area">
                        <div class="pile">
                            <div class="pile-label">Stapel</div>
                            <div class="pile-cards" id="deckPile" onclick="playerDrawDeck()"></div>
                            <div class="pile-count" id="deckCount">0</div>
                        </div>
                        <div class="pile">
                            <div class="pile-label">Ablage</div>
                            <div class="discard-pile-area" id="discardPile" onclick="playerDrawDiscard()"></div>
                        </div>
                    </div>

                    <!-- Player hand -->
                    <div class="hand-area">
                        <h3>Deine Karten (<span id="handCount">0</span>)</h3>
                        <div class="sort-buttons">
                            <button class="btn tiny" onclick="sortHand('suit')">Farbe</button>
                            <button class="btn tiny" onclick="sortHand('value')">Wert</button>
                        </div>
                        <div class="my-hand" id="myHand"></div>
                        <div class="actions">
                            <button class="btn small" id="meldBtn" onclick="meldSelected()" disabled>Auslegen</button>
                            <button class="btn small" id="layoffBtn" onclick="showLayoffModal()" disabled>Anlegen</button>
                            <button class="btn small secondary" id="discardBtn" onclick="discardSelected()" disabled>Ablegen</button>
                            <button class="btn small secondary" onclick="clearSelection()">Abwaehlen</button>
                        </div>
                    </div>
                </div>

                <div class="sidebar">
                    <div class="info-card">
                        <div class="turn-info" id="turnInfo">
                            <div class="label">Am Zug:</div>
                            <div class="name" id="currentPlayerName">Du</div>
                            <div class="phase" id="phaseInfo">Ziehen</div>
                        </div>
                    </div>
                    <div class="info-card">
                        <h3>Spieler</h3>
                        <div id="playersList"></div>
                    </div>
                    <div class="info-card">
                        <h3>Info</h3>
                        <p style="font-size: 0.8rem; color: var(--mp-text-muted);" id="gameInfo">
                            Ziehe eine Karte um zu beginnen.
                        </p>
                    </div>
                    <div class="info-card">
                        <h3>Kurzregeln</h3>
                        <ul style="font-size: 0.8rem; color: var(--mp-text-muted); list-style: none; padding: 0; margin: 0;">
                            <li>&#127156; Ziehe 1 Karte (Stapel/Ablage)</li>
                            <li>&#128228; Saetze (3-4 gleiche) oder Reihen (3+ Folge)</li>
                            <li>&#128290; Erstauslage: min. 30 Punkte</li>
                            <li>&#10133; Danach: An Kombinationen anlegen</li>
                            <li>&#127919; Ziel: Alle Karten loswerden!</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESULT -->
        <div id="resultScreen" class="screen">
            <div class="setup-container">
                <div class="result-card">
                    <div style="font-size: 5rem;" id="resultEmoji">&#127942;</div>
                    <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                    <div id="scoresDisplay" style="margin: 20px 0;"></div>
                    <button class="btn" onclick="startGame()">Nochmal spielen</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="showScreen('setup')">Schwierigkeit aendern</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='romme.php'">&larr; Zurueck</button>
                </div>
            </div>
        </div>

        <!-- Layoff Modal -->
        <div class="modal" id="layoffModal">
            <div class="modal-content">
                <h2>An welche Kombination anlegen?</h2>
                <div class="joker-assign-list" id="layoffMeldList"></div>
                <button class="btn secondary" style="margin-top: 15px;" onclick="closeLayoffModal()">Abbrechen</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/playing-cards.js"></script>
    <script>
    (() => {
        /* =====================================================================
         *  CONSTANTS
         * =================================================================== */
        const SUITS = ['herz', 'karo', 'pik', 'kreuz'];
        const VALUES = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'bube', 'dame', 'koenig', 'ass'];
        const VALUE_ORDER = { '2':2,'3':3,'4':4,'5':5,'6':6,'7':7,'8':8,'9':9,'10':10,'bube':11,'dame':12,'koenig':13,'ass':14 };
        const SUIT_ORDER = { herz:0, karo:1, pik:2, kreuz:3 };
        const CARD_POINTS = { '2':2,'3':3,'4':4,'5':5,'6':6,'7':7,'8':8,'9':9,'10':10,'bube':10,'dame':10,'koenig':10,'ass':11,'joker':20 };

        let difficulty = 1;
        let game = null;
        let selectedCards = [];
        let sortMode = 'suit';
        let aiThinking = false;
        let playerName = '<?php echo addslashes($userName ?: "Du"); ?>';

        /* =====================================================================
         *  SCREENS / SETUP
         * =================================================================== */
        window.selectDifficulty = function(level, el) {
            difficulty = level;
            document.querySelectorAll('.diff-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
        };

        window.showScreen = function(name) {
            document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
            document.getElementById(name + 'Screen').classList.add('active');
        };

        /* =====================================================================
         *  DECK CREATION
         * =================================================================== */
        function createDeck() {
            const deck = [];
            let idCounter = 0;
            for (let copy = 0; copy < 2; copy++) {
                for (const suit of SUITS) {
                    for (const value of VALUES) {
                        deck.push({ id: suit + '_' + value + '_' + (idCounter++), color: suit, value: value });
                    }
                }
            }
            deck.push({ id: 'joker_1', color: 'joker', value: 'joker' });
            deck.push({ id: 'joker_2', color: 'joker', value: 'joker' });
            // Fisher-Yates shuffle
            for (let i = deck.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [deck[i], deck[j]] = [deck[j], deck[i]];
            }
            return deck;
        }

        function cardKey(card) {
            if (card.color === 'joker') return 'joker_red';
            return card.color + '_' + card.value;
        }

        function cardPoints(card) {
            if (card.color === 'joker') return 20;
            return CARD_POINTS[card.value] || 0;
        }

        function cardOrder(card) {
            if (card.color === 'joker') return 15;
            return VALUE_ORDER[card.value] || 0;
        }

        /* =====================================================================
         *  MELD VALIDATION
         * =================================================================== */
        function isValidSet(cards) {
            // 3-4 cards of same value, different suits (jokers allowed)
            const nonJokers = cards.filter(c => c.color !== 'joker');
            const jokers = cards.length - nonJokers.length;
            if (cards.length < 3 || cards.length > 4) return false;
            if (nonJokers.length === 0) return false;

            const targetValue = nonJokers[0].value;
            const suits = new Set();
            for (const c of nonJokers) {
                if (c.value !== targetValue) return false;
                if (suits.has(c.color)) return false;
                suits.add(c.color);
            }
            // Jokers cannot duplicate existing suits - they represent missing suits
            return (nonJokers.length + jokers) >= 3 && (nonJokers.length + jokers) <= 4;
        }

        function isValidRun(cards) {
            // 3+ consecutive cards of same suit (jokers fill gaps)
            if (cards.length < 3) return false;
            const nonJokers = cards.filter(c => c.color !== 'joker');
            const jokerCount = cards.length - nonJokers.length;
            if (nonJokers.length === 0) return false;

            const suit = nonJokers[0].color;
            for (const c of nonJokers) {
                if (c.color !== suit) return false;
            }

            // Sort by order
            const sorted = nonJokers.slice().sort((a, b) => cardOrder(a) - cardOrder(b));
            // Check for consecutive with jokers filling gaps
            let jokersUsed = 0;
            for (let i = 1; i < sorted.length; i++) {
                const gap = cardOrder(sorted[i]) - cardOrder(sorted[i - 1]) - 1;
                if (gap < 0) return false; // duplicate values
                if (gap === 0) return false; // same value in run
                jokersUsed += (gap - 0); // gap of 1 means consecutive, 0 jokers needed
                if (gap > 1) jokersUsed += (gap - 1);
            }
            // Recalculate: gap between consecutive should be 1 (no joker needed)
            jokersUsed = 0;
            for (let i = 1; i < sorted.length; i++) {
                const gap = cardOrder(sorted[i]) - cardOrder(sorted[i - 1]);
                if (gap === 0) return false;
                if (gap < 0) return false;
                jokersUsed += (gap - 1);
            }
            if (jokersUsed > jokerCount) return false;
            // Total cards in run = span covered
            const span = cardOrder(sorted[sorted.length - 1]) - cardOrder(sorted[0]) + 1;
            return span === cards.length && jokersUsed <= jokerCount;
        }

        function isValidMeld(cards) {
            return isValidSet(cards) || isValidRun(cards);
        }

        function meldPoints(cards) {
            let pts = 0;
            const nonJokers = cards.filter(c => c.color !== 'joker');
            for (const c of cards) {
                if (c.color === 'joker') {
                    // Joker takes value of what it represents
                    if (isValidSet(cards) && nonJokers.length > 0) {
                        pts += cardPoints(nonJokers[0]);
                    } else {
                        pts += 20;
                    }
                } else {
                    pts += cardPoints(c);
                }
            }
            return pts;
        }

        function canLayoff(card, meldCards) {
            // Try adding card to existing meld and check validity
            const extended = [...meldCards, card];
            return isValidSet(extended) || isValidRun(extended);
        }

        /* =====================================================================
         *  GAME START
         * =================================================================== */
        window.startGame = function() {
            const deck = createDeck();
            const playerHand = deck.splice(0, 13);
            const aiHand = deck.splice(0, 13);
            // Initial discard
            const firstDiscard = deck.shift();

            game = {
                deck: deck,
                discardPile: [firstDiscard],
                playerHand: playerHand,
                aiHand: aiHand,
                melds: [],          // { cards: [...], owner: 'player'|'ai' }
                currentPlayer: 'player',
                phase: 'draw',      // draw, play
                playerHasMelded: false,
                aiHasMelded: false,
                discardedCards: [firstDiscard], // for hard AI memory
                turnCount: 0
            };
            selectedCards = [];
            aiThinking = false;
            applySorting(game.playerHand);
            showScreen('game');
            render();
        };

        /* =====================================================================
         *  SORTING
         * =================================================================== */
        function applySorting(hand) {
            if (sortMode === 'suit') {
                hand.sort((a, b) => {
                    const sc = (SUIT_ORDER[a.color] ?? 5) - (SUIT_ORDER[b.color] ?? 5);
                    if (sc !== 0) return sc;
                    return cardOrder(a) - cardOrder(b);
                });
            } else {
                hand.sort((a, b) => {
                    const vc = cardOrder(a) - cardOrder(b);
                    if (vc !== 0) return vc;
                    return (SUIT_ORDER[a.color] ?? 5) - (SUIT_ORDER[b.color] ?? 5);
                });
            }
        }

        window.sortHand = function(mode) {
            sortMode = mode;
            applySorting(game.playerHand);
            selectedCards = [];
            render();
            showToast('Sortiert nach ' + (mode === 'suit' ? 'Farbe' : 'Wert'), 'info');
        };

        /* =====================================================================
         *  PLAYER ACTIONS
         * =================================================================== */
        window.toggleCard = function(id) {
            if (game.currentPlayer !== 'player' || game.phase !== 'play' || aiThinking) return;
            const idx = selectedCards.indexOf(id);
            if (idx >= 0) selectedCards.splice(idx, 1);
            else selectedCards.push(id);
            render();
        };

        window.clearSelection = function() {
            selectedCards = [];
            render();
        };

        window.playerDrawDeck = function() {
            if (game.currentPlayer !== 'player' || game.phase !== 'draw' || aiThinking) return;
            if (game.deck.length === 0) { reshuffleDeck(); if (game.deck.length === 0) return; }
            const card = game.deck.shift();
            game.playerHand.push(card);
            applySorting(game.playerHand);
            game.phase = 'play';
            showToast('Karte vom Stapel gezogen', 'info');
            render();
        };

        window.playerDrawDiscard = function() {
            if (game.currentPlayer !== 'player' || game.phase !== 'draw' || aiThinking) return;
            if (game.discardPile.length === 0) return;
            const card = game.discardPile.pop();
            game.playerHand.push(card);
            applySorting(game.playerHand);
            game.phase = 'play';
            showToast('Karte von der Ablage gezogen', 'info');
            render();
        };

        window.meldSelected = function() {
            if (game.currentPlayer !== 'player' || game.phase !== 'play') return;
            const cards = selectedCards.map(id => game.playerHand.find(c => c.id === id)).filter(Boolean);
            if (cards.length < 3) { showToast('Mindestens 3 Karten auswaehlen', 'error'); return; }
            if (!isValidMeld(cards)) { showToast('Keine gueltige Kombination', 'error'); return; }

            // First meld check: need 30+ points
            if (!game.playerHasMelded) {
                const pts = meldPoints(cards);
                if (pts < 30) {
                    showToast('Erstauslage braucht min. 30 Punkte (aktuell: ' + pts + ')', 'error');
                    return;
                }
            }

            // Remove cards from hand and add meld
            for (const c of cards) {
                const idx = game.playerHand.findIndex(h => h.id === c.id);
                if (idx >= 0) game.playerHand.splice(idx, 1);
            }
            game.melds.push({ cards: cards, owner: 'player' });
            game.playerHasMelded = true;
            selectedCards = [];
            applySorting(game.playerHand);

            if (game.playerHand.length === 0) { endGame('player'); return; }
            showToast('Kombination ausgelegt! (' + meldPoints(cards) + ' Punkte)', 'success');
            render();
        };

        window.showLayoffModal = function() {
            if (game.currentPlayer !== 'player' || game.phase !== 'play') return;
            if (selectedCards.length !== 1) { showToast('Genau 1 Karte zum Anlegen auswaehlen', 'error'); return; }
            if (!game.playerHasMelded) { showToast('Erst eigene Erstauslage machen', 'error'); return; }

            const card = game.playerHand.find(c => c.id === selectedCards[0]);
            if (!card) return;

            const list = document.getElementById('layoffMeldList');
            list.innerHTML = '';
            let hasOptions = false;

            game.melds.forEach((meld, i) => {
                if (canLayoff(card, meld.cards)) {
                    hasOptions = true;
                    const div = document.createElement('div');
                    div.className = 'joker-assign-option';
                    const preview = meld.cards.map(c => {
                        const k = cardKey(c);
                        return '<img src="' + PLAYING_CARD_SVGS[k] + '" style="width:35px;height:53px;border-radius:3px;margin-right:2px;">';
                    }).join('');
                    div.innerHTML = preview + ' <span style="color:var(--mp-text-muted);font-size:0.8rem;">(' + meld.owner + ')</span>';
                    div.onclick = function() { executeLayoff(i); };
                    list.appendChild(div);
                }
            });

            if (!hasOptions) { showToast('Karte passt an keine Kombination', 'error'); return; }
            document.getElementById('layoffModal').classList.add('active');
        };

        function executeLayoff(meldIndex) {
            const card = game.playerHand.find(c => c.id === selectedCards[0]);
            if (!card) return;
            const idx = game.playerHand.findIndex(c => c.id === card.id);
            if (idx >= 0) game.playerHand.splice(idx, 1);
            game.melds[meldIndex].cards.push(card);
            selectedCards = [];
            closeLayoffModal();
            applySorting(game.playerHand);
            if (game.playerHand.length === 0) { endGame('player'); return; }
            showToast('Karte angelegt!', 'success');
            render();
        }

        window.closeLayoffModal = function() {
            document.getElementById('layoffModal').classList.remove('active');
        };

        window.discardSelected = function() {
            if (game.currentPlayer !== 'player' || game.phase !== 'play') return;
            if (selectedCards.length !== 1) { showToast('Genau 1 Karte zum Ablegen auswaehlen', 'error'); return; }
            const card = game.playerHand.find(c => c.id === selectedCards[0]);
            if (!card) return;
            const idx = game.playerHand.findIndex(c => c.id === card.id);
            if (idx >= 0) game.playerHand.splice(idx, 1);
            game.discardPile.push(card);
            game.discardedCards.push(card);
            selectedCards = [];

            if (game.playerHand.length === 0) { endGame('player'); return; }

            // Next turn
            game.currentPlayer = 'ai';
            game.phase = 'draw';
            game.turnCount++;
            render();
            scheduleAiTurn();
        };

        /* =====================================================================
         *  DECK RESHUFFLE
         * =================================================================== */
        function reshuffleDeck() {
            if (game.discardPile.length <= 1) return;
            const top = game.discardPile.pop();
            game.deck = game.discardPile.slice();
            game.discardPile = [top];
            for (let i = game.deck.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [game.deck[i], game.deck[j]] = [game.deck[j], game.deck[i]];
            }
        }

        /* =====================================================================
         *  AI LOGIC
         * =================================================================== */
        function scheduleAiTurn() {
            aiThinking = true;
            render();
            const baseDelay = [0, 500, 800, 1200][difficulty];
            const delay = baseDelay + Math.random() * 800;
            setTimeout(aiTurn, delay);
        }

        function aiTurn() {
            if (game.currentPlayer !== 'ai') { aiThinking = false; render(); return; }

            // Phase 1: Draw
            aiDraw();
            render();

            // Phase 2: Play melds (with delay)
            setTimeout(() => {
                aiPlayMelds();
                render();

                // Phase 3: Layoff (with delay)
                setTimeout(() => {
                    aiLayoff();
                    render();

                    if (game.aiHand.length === 0) { aiThinking = false; endGame('ai'); return; }

                    // Phase 4: Discard (with delay)
                    setTimeout(() => {
                        aiDiscard();
                        aiThinking = false;

                        if (game.aiHand.length === 0) { endGame('ai'); return; }

                        game.currentPlayer = 'player';
                        game.phase = 'draw';
                        game.turnCount++;
                        render();
                    }, 400 + Math.random() * 400);
                }, 300 + Math.random() * 300);
            }, 400 + Math.random() * 400);
        }

        function aiDraw() {
            // Decide: draw from deck or discard?
            const topDiscard = game.discardPile.length > 0 ? game.discardPile[game.discardPile.length - 1] : null;

            if (difficulty === 1) {
                // Easy: random
                if (topDiscard && Math.random() > 0.5 && game.discardPile.length > 0) {
                    game.aiHand.push(game.discardPile.pop());
                } else {
                    if (game.deck.length === 0) reshuffleDeck();
                    if (game.deck.length > 0) game.aiHand.push(game.deck.shift());
                }
            } else {
                // Medium/Hard: check if discard card helps
                if (topDiscard && aiCardIsUseful(topDiscard, game.aiHand)) {
                    game.aiHand.push(game.discardPile.pop());
                } else {
                    if (game.deck.length === 0) reshuffleDeck();
                    if (game.deck.length > 0) game.aiHand.push(game.deck.shift());
                }
            }
            game.phase = 'play';
        }

        function aiCardIsUseful(card, hand) {
            // Check if card helps form a meld
            if (card.color === 'joker') return true;
            // Count same value cards in hand (for sets)
            const sameValue = hand.filter(c => c.value === card.value && c.color !== 'joker').length;
            if (sameValue >= 2) return true;
            // Check for run proximity
            const sameSuit = hand.filter(c => c.color === card.color && c.color !== 'joker');
            const order = cardOrder(card);
            let neighbors = 0;
            for (const c of sameSuit) {
                const diff = Math.abs(cardOrder(c) - order);
                if (diff === 1 || diff === 2) neighbors++;
            }
            if (neighbors >= 2) return true;
            if (difficulty >= 3 && neighbors >= 1 && sameValue >= 1) return true;
            return false;
        }

        function aiPlayMelds() {
            if (difficulty === 1) return; // Easy AI doesn't try to meld proactively

            const melds = findAllMelds(game.aiHand);
            if (melds.length === 0) return;

            if (!game.aiHasMelded) {
                // Need 30+ points for first meld
                const combo = findFirstMeldCombo(melds, 30);
                if (combo) {
                    for (const meld of combo) {
                        for (const c of meld) {
                            const idx = game.aiHand.findIndex(h => h.id === c.id);
                            if (idx >= 0) game.aiHand.splice(idx, 1);
                        }
                        game.melds.push({ cards: meld, owner: 'ai' });
                    }
                    game.aiHasMelded = true;
                    showToast('Computer legt Erstauslage!', 'info');
                    // Try further melds now that we have melded
                    aiPlayMelds();
                }
            } else {
                // Already melded: lay down any valid melds
                for (const meld of melds) {
                    // Verify all cards still in hand
                    if (meld.every(c => game.aiHand.some(h => h.id === c.id))) {
                        for (const c of meld) {
                            const idx = game.aiHand.findIndex(h => h.id === c.id);
                            if (idx >= 0) game.aiHand.splice(idx, 1);
                        }
                        game.melds.push({ cards: meld, owner: 'ai' });
                    }
                }
            }
        }

        function aiLayoff() {
            if (!game.aiHasMelded) return;
            if (difficulty === 1) return;

            let changed = true;
            while (changed) {
                changed = false;
                for (let ci = game.aiHand.length - 1; ci >= 0; ci--) {
                    const card = game.aiHand[ci];
                    for (let mi = 0; mi < game.melds.length; mi++) {
                        if (canLayoff(card, game.melds[mi].cards)) {
                            game.melds[mi].cards.push(card);
                            game.aiHand.splice(ci, 1);
                            changed = true;
                            break;
                        }
                    }
                    if (changed) break;
                }
            }
        }

        function aiDiscard() {
            if (game.aiHand.length === 0) return;
            let discardIdx = 0;

            if (difficulty === 1) {
                discardIdx = Math.floor(Math.random() * game.aiHand.length);
            } else if (difficulty === 2) {
                discardIdx = chooseBestDiscard(game.aiHand, false);
            } else {
                discardIdx = chooseBestDiscard(game.aiHand, true);
            }

            const card = game.aiHand.splice(discardIdx, 1)[0];
            game.discardPile.push(card);
            game.discardedCards.push(card);
        }

        /* =====================================================================
         *  AI: FIND MELDS
         * =================================================================== */
        function findAllMelds(hand) {
            const melds = [];

            // Find sets
            const byValue = {};
            hand.forEach(c => {
                if (c.color === 'joker') return;
                if (!byValue[c.value]) byValue[c.value] = [];
                byValue[c.value].push(c);
            });
            const jokers = hand.filter(c => c.color === 'joker');

            for (const val in byValue) {
                const group = byValue[val];
                // Ensure different suits
                const uniqueSuits = [];
                const used = new Set();
                for (const c of group) {
                    if (!used.has(c.color)) {
                        uniqueSuits.push(c);
                        used.add(c.color);
                    }
                }
                if (uniqueSuits.length >= 3) {
                    melds.push(uniqueSuits.slice(0, Math.min(4, uniqueSuits.length)));
                } else if (uniqueSuits.length === 2 && jokers.length > 0) {
                    melds.push([...uniqueSuits, jokers[0]]);
                }
            }

            // Find runs
            for (const suit of SUITS) {
                const suitCards = hand.filter(c => c.color === suit).sort((a, b) => cardOrder(a) - cardOrder(b));
                // Remove duplicates (keep first of each value)
                const unique = [];
                const seenVals = new Set();
                for (const c of suitCards) {
                    if (!seenVals.has(c.value)) {
                        unique.push(c);
                        seenVals.add(c.value);
                    }
                }
                if (unique.length < 2) continue;

                // Find consecutive sequences
                for (let start = 0; start < unique.length; start++) {
                    const run = [unique[start]];
                    let jUsed = 0;
                    for (let next = start + 1; next < unique.length; next++) {
                        const gap = cardOrder(unique[next]) - cardOrder(run[run.length - 1]) - 1;
                        if (gap === 0) {
                            run.push(unique[next]);
                        } else if (gap <= jokers.length - jUsed && gap > 0) {
                            // Fill with jokers
                            for (let j = 0; j < gap && jUsed < jokers.length; j++) {
                                run.push(jokers[jUsed++]);
                            }
                            run.push(unique[next]);
                        } else {
                            break;
                        }
                    }
                    if (run.length >= 3) {
                        melds.push(run.slice());
                    }
                }
            }

            return melds;
        }

        function findFirstMeldCombo(melds, minPoints) {
            // Try single melds first
            for (const m of melds) {
                if (meldPoints(m) >= minPoints) return [m];
            }
            // Try combinations of 2
            for (let i = 0; i < melds.length; i++) {
                for (let j = i + 1; j < melds.length; j++) {
                    // Check no overlapping cards
                    const idsI = new Set(melds[i].map(c => c.id));
                    if (melds[j].some(c => idsI.has(c.id))) continue;
                    const total = meldPoints(melds[i]) + meldPoints(melds[j]);
                    if (total >= minPoints) return [melds[i], melds[j]];
                }
            }
            // Try combinations of 3
            for (let i = 0; i < melds.length; i++) {
                for (let j = i + 1; j < melds.length; j++) {
                    const idsIJ = new Set([...melds[i], ...melds[j]].map(c => c.id));
                    if (melds[j].some(c => new Set(melds[i].map(x => x.id)).has(c.id))) continue;
                    for (let k = j + 1; k < melds.length; k++) {
                        if (melds[k].some(c => idsIJ.has(c.id))) continue;
                        const total = meldPoints(melds[i]) + meldPoints(melds[j]) + meldPoints(melds[k]);
                        if (total >= minPoints) return [melds[i], melds[j], melds[k]];
                    }
                }
            }
            return null;
        }

        /* =====================================================================
         *  AI: CHOOSE BEST DISCARD
         * =================================================================== */
        function chooseBestDiscard(hand, useMemory) {
            // Score each card: lower = better to discard
            let bestIdx = 0;
            let bestScore = Infinity;

            for (let i = 0; i < hand.length; i++) {
                const card = hand[i];
                let score = 0;

                if (card.color === 'joker') {
                    score = 1000; // Never discard joker if possible
                } else {
                    // Base: face value is bad to keep (penalty points)
                    score -= cardPoints(card) * 0.5;

                    // Same value neighbors (set potential)
                    const sameVal = hand.filter(c => c.value === card.value && c.id !== card.id && c.color !== 'joker');
                    score += sameVal.length * 30;

                    // Same suit neighbors (run potential)
                    const sameSuit = hand.filter(c => c.color === card.color && c.id !== card.id && c.color !== 'joker');
                    const order = cardOrder(card);
                    let runNeighbors = 0;
                    for (const c of sameSuit) {
                        const diff = Math.abs(cardOrder(c) - order);
                        if (diff === 1) runNeighbors += 2;
                        else if (diff === 2) runNeighbors += 1;
                    }
                    score += runNeighbors * 15;

                    // Hard AI: avoid discarding cards opponent might need
                    if (useMemory) {
                        const discardedSameVal = game.discardedCards.filter(c => c.value === card.value).length;
                        score -= discardedSameVal * 5; // More discarded = safer to discard
                        // If opponent drew from discard recently, be more cautious
                    }
                }

                if (score < bestScore) {
                    bestScore = score;
                    bestIdx = i;
                }
            }
            return bestIdx;
        }

        /* =====================================================================
         *  GAME END
         * =================================================================== */
        function endGame(winner) {
            // Calculate penalty points for loser's hand
            const loserHand = winner === 'player' ? game.aiHand : game.playerHand;
            let penalty = 0;
            for (const c of loserHand) penalty += cardPoints(c);

            const resultEmoji = document.getElementById('resultEmoji');
            const winnerText = document.getElementById('winnerText');
            const scoresDisplay = document.getElementById('scoresDisplay');

            if (winner === 'player') {
                resultEmoji.textContent = '\u{1F3C6}';
                winnerText.textContent = 'Du hast gewonnen!';
            } else {
                resultEmoji.textContent = '\u{1F614}';
                winnerText.textContent = 'Computer gewinnt!';
            }

            scoresDisplay.innerHTML = `
                <div style="padding:10px;background:var(--mp-bg-dark);border-radius:8px;margin:5px 0;display:flex;justify-content:space-between;">
                    <span>${playerName}</span>
                    <span style="color:${winner === 'player' ? 'var(--mp-accent)' : 'var(--mp-error)'}">
                        ${winner === 'player' ? '0' : penalty} Strafpunkte
                    </span>
                </div>
                <div style="padding:10px;background:var(--mp-bg-dark);border-radius:8px;margin:5px 0;display:flex;justify-content:space-between;">
                    <span>Computer</span>
                    <span style="color:${winner === 'ai' ? 'var(--mp-accent)' : 'var(--mp-error)'}">
                        ${winner === 'ai' ? '0' : penalty} Strafpunkte
                    </span>
                </div>
            `;

            setTimeout(() => showScreen('result'), 600);
        }

        /* =====================================================================
         *  RENDERING
         * =================================================================== */
        function render() {
            if (!game) return;
            const isMyTurn = game.currentPlayer === 'player' && !aiThinking;
            const backSrc = PLAYING_CARD_SVGS.back;

            // Opponent
            const oppArea = document.getElementById('opponentArea');
            oppArea.className = 'opponent' + (game.currentPlayer === 'ai' ? ' active' : '');
            const oppCards = document.getElementById('opponentCards');
            const showCount = Math.min(game.aiHand.length, 13);
            oppCards.innerHTML = Array(showCount).fill(0).map(() =>
                '<div class="card-back-small" style="background-image:url(\'' + backSrc + '\')"></div>'
            ).join('');
            document.getElementById('opponentCount').textContent = game.aiHand.length + ' Karten';

            // Deck
            const deckEl = document.getElementById('deckPile');
            if (game.deck.length > 0) {
                deckEl.innerHTML = '<img src="' + backSrc + '"><img src="' + backSrc + '"><img src="' + backSrc + '">';
            } else {
                deckEl.innerHTML = '<div style="width:70px;height:100px;border:2px dashed #555;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#555;font-size:0.7rem;">Leer</div>';
            }
            document.getElementById('deckCount').textContent = game.deck.length + ' Karten';

            // Discard pile
            const discardEl = document.getElementById('discardPile');
            if (game.discardPile.length > 0) {
                const topCard = game.discardPile[game.discardPile.length - 1];
                const topKey = cardKey(topCard);
                discardEl.innerHTML = '<img src="' + PLAYING_CARD_SVGS[topKey] + '">';
            } else {
                discardEl.innerHTML = '<div class="discard-empty">Leer</div>';
            }

            // Melds
            const meldsContainer = document.getElementById('meldsContainer');
            if (game.melds.length === 0) {
                meldsContainer.innerHTML = '<span style="color:var(--mp-text-muted);font-size:0.85rem;">Noch keine Kombinationen</span>';
            } else {
                meldsContainer.innerHTML = game.melds.map((meld, i) => {
                    const cardsHtml = meld.cards.map(c => {
                        const k = cardKey(c);
                        return '<img class="meld-card" src="' + PLAYING_CARD_SVGS[k] + '">';
                    }).join('');
                    const ownerLabel = meld.owner === 'player' ? playerName : 'Computer';
                    return '<div><div class="meld-group" onclick="selectMeldForLayoff(' + i + ')">' + cardsHtml + '</div>' +
                        '<div class="meld-owner">' + ownerLabel + '</div></div>';
                }).join('');
            }

            // Player hand
            document.getElementById('handCount').textContent = game.playerHand.length;
            const handEl = document.getElementById('myHand');
            handEl.innerHTML = game.playerHand.map(card => {
                const key = cardKey(card);
                const sel = selectedCards.includes(card.id);
                return '<div class="card-slot ' + (sel ? 'selected' : '') + '" onclick="toggleCard(\'' + card.id + '\')">' +
                    '<img src="' + PLAYING_CARD_SVGS[key] + '">' +
                    '</div>';
            }).join('');

            // Turn info
            const turnInfo = document.getElementById('turnInfo');
            turnInfo.className = 'turn-info' + (isMyTurn ? ' my-turn' : '');
            const nameEl = document.getElementById('currentPlayerName');
            if (aiThinking) {
                nameEl.innerHTML = '<span class="thinking">Computer denkt</span>';
            } else {
                nameEl.textContent = isMyTurn ? playerName : 'Computer';
            }
            const phaseEl = document.getElementById('phaseInfo');
            phaseEl.textContent = game.phase === 'draw' ? 'Ziehen' : 'Spielen / Ablegen';

            // Info
            const info = document.getElementById('gameInfo');
            if (aiThinking) {
                info.textContent = 'Computer ist am Zug...';
            } else if (isMyTurn && game.phase === 'draw') {
                info.textContent = 'Ziehe eine Karte vom Stapel oder Ablagestapel.';
            } else if (isMyTurn && game.phase === 'play') {
                info.textContent = 'Lege Kombinationen aus, lege an, oder lege eine Karte ab.';
            } else {
                info.textContent = 'Warte auf Computer...';
            }

            // Players sidebar
            document.getElementById('playersList').innerHTML = `
                <div class="player-row ${game.currentPlayer === 'player' ? 'active' : ''}">
                    <span>${playerName} ${game.playerHasMelded ? '(ausgelegt)' : ''}</span>
                    <span>${game.playerHand.length} Karten</span>
                </div>
                <div class="player-row ${game.currentPlayer === 'ai' ? 'active' : ''}">
                    <span>Computer ${game.aiHasMelded ? '(ausgelegt)' : ''}</span>
                    <span>${game.aiHand.length} Karten</span>
                </div>
            `;

            // Buttons state
            const canAct = isMyTurn && game.phase === 'play';
            document.getElementById('meldBtn').disabled = !canAct || selectedCards.length < 3;
            document.getElementById('layoffBtn').disabled = !canAct || selectedCards.length !== 1 || !game.playerHasMelded;
            document.getElementById('discardBtn').disabled = !canAct || selectedCards.length !== 1;
        }

        // Quick layoff by clicking meld group
        window.selectMeldForLayoff = function(meldIdx) {
            if (game.currentPlayer !== 'player' || game.phase !== 'play') return;
            if (selectedCards.length !== 1) return;
            if (!game.playerHasMelded) { showToast('Erst eigene Erstauslage machen', 'error'); return; }

            const card = game.playerHand.find(c => c.id === selectedCards[0]);
            if (!card) return;

            if (canLayoff(card, game.melds[meldIdx].cards)) {
                executeLayoff(meldIdx);
            } else {
                showToast('Karte passt nicht an diese Kombination', 'error');
            }
        };

        /* =====================================================================
         *  UTILITIES
         * =================================================================== */
        function showToast(msg, type) {
            const t = document.createElement('div');
            t.className = 'toast ' + type;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }
    })();
    </script>
</body>
</html>
