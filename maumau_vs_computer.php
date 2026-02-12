<?php
/**
 * ============================================================================
 * sgiT Education - Mau Mau vs Computer v1.0
 * ============================================================================
 *
 * Mau Mau gegen KI-Gegner (3 Schwierigkeitsstufen)
 * Komplett client-seitig, kein Server-Roundtrip
 *
 * KI-Stufen:
 * 1 - Leicht: Spielt erste passende Karte
 * 2 - Mittel: Priorisiert Sonderkarten (7er, Bube, Ass)
 * 3 - Schwer: Zaehlt Karten, optimale Farbwahl bei Bube
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
require_once 'includes/version.php';
require_once __DIR__ . '/wallet/SessionManager.php';

$userName = '';
$userAvatar = '😀';

if (SessionManager::isLoggedIn()) {
    $childData = SessionManager::getChild();
    if ($childData) {
        $userName = $childData['name'];
        $userAvatar = $childData['avatar'] ?? '😀';
    }
} elseif (isset($_SESSION['wallet_child_id'])) {
    $userName = $_SESSION['user_name'] ?? $_SESSION['child_name'] ?? '';
    $userAvatar = $_SESSION['avatar'] ?? '😀';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🃏 Mau Mau vs Computer - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <style>
        :root { --card-white: #fffef5; }
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--mp-bg-dark) 0%, var(--mp-primary) 100%);
            min-height: 100vh; color: var(--mp-text); margin: 0; padding: 0;
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 15px; }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 20px; background: var(--mp-bg-card); border-radius: 12px; margin-bottom: 20px;
        }
        .header h1 { font-size: 1.4rem; }
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
            cursor: pointer; width: 100%;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn.secondary { background: var(--mp-bg-card); color: var(--mp-text); border: 2px solid var(--mp-accent); }
        .btn.mau { background: #f39c12; animation: pulse 0.5s infinite; }
        @keyframes pulse { 50% { transform: scale(1.05); } }

        /* Game Area */
        .game-container { display: grid; grid-template-columns: 1fr 250px; gap: 20px; }
        @media (max-width: 800px) { .game-container { grid-template-columns: 1fr; } }
        .play-area {
            background: var(--mp-bg-card); border-radius: 16px; padding: 20px; min-height: 500px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }
        .table-area { display: flex; flex-direction: column; align-items: center; gap: 20px; }

        .opponent {
            background: var(--mp-bg-dark); border-radius: 12px; padding: 12px 20px; text-align: center;
            border: 2px solid transparent; transition: all 0.3s;
        }
        .opponent.active { border-color: var(--mp-accent); box-shadow: 0 0 20px rgba(76,175,80,0.3); }
        .opponent .cards { display: flex; justify-content: center; margin-top: 8px; }
        .card-back-small {
            width: 30px; height: 45px; border-radius: 4px; border: 1px solid #555;
            margin-left: -15px; background-size: cover; background-position: center;
        }
        .card-back-small:first-child { margin-left: 0; }

        .center-pile {
            display: flex; gap: 20px; align-items: center; justify-content: center;
            padding: 30px; background: rgba(0,0,0,0.2); border-radius: 20px;
        }
        .deck-pile, .discard-pile { position: relative; width: 80px; height: 120px; }
        .deck-pile { cursor: pointer; }
        .deck-pile img { width: 80px; height: 120px; position: absolute; border-radius: 8px; }
        .deck-pile img:nth-child(2) { top: 2px; left: 2px; }
        .deck-pile img:nth-child(3) { top: 4px; left: 4px; }
        .deck-count { position: absolute; bottom: -25px; left: 50%; transform: translateX(-50%); font-size: 0.8rem; color: var(--mp-text-muted); }
        .discard-pile img { width: 80px; height: 120px; border-radius: 8px; }

        .card-slot {
            width: 80px; height: 120px; display: inline-flex; flex-direction: column;
            align-items: center; justify-content: center; cursor: pointer;
            transition: all 0.2s; position: relative; margin-left: -20px; vertical-align: bottom;
        }
        .card-slot:first-child { margin-left: 0; }
        .card-slot img { width: 80px; height: 120px; border-radius: 8px; pointer-events: none; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
        .card-slot:hover { transform: translateY(-10px); }
        .card-slot.playable img { box-shadow: 0 0 15px var(--mp-accent-glow); }
        .card-slot.playable { animation: mp-fieldPulse 1.5s ease infinite; }
        .card-slot.selected { transform: translateY(-20px); }
        .card-slot.selected img { box-shadow: 0 0 20px #f39c12; }

        .my-hand {
            display: flex; justify-content: center; flex-wrap: wrap;
            padding: 20px; background: rgba(0,0,0,0.2); border-radius: 15px; margin-top: 20px;
        }

        /* Sidebar */
        .sidebar { display: flex; flex-direction: column; gap: 15px; }
        .info-card {
            background: var(--mp-bg-card); border-radius: 12px; padding: 15px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05);
        }
        .info-card h3 { color: var(--mp-accent); margin-bottom: 10px; font-size: 1rem; }
        .turn-info { text-align: center; padding: 15px; background: var(--mp-bg-dark); border-radius: 10px; }
        .turn-info.my-turn { border: 2px solid var(--mp-accent); }
        .turn-info .label { font-size: 0.85rem; color: var(--mp-text-muted); }
        .turn-info .name { font-size: 1.2rem; font-weight: bold; margin-top: 5px; }
        .special-info { background: var(--mp-bg-dark); border-radius: 10px; padding: 10px; margin-top: 10px; text-align: center; }
        .special-info.warning { border: 2px solid #f39c12; }
        .special-info.danger { border: 2px solid var(--mp-error); }
        .thinking { color: var(--mp-accent); font-style: italic; }
        .thinking::after { content: '...'; animation: dots 1.5s infinite; }
        @keyframes dots { 0%{content:'.'} 33%{content:'..'} 66%{content:'...'} }

        /* Wish Color Modal */
        .modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 100; }
        .modal.active { display: flex; }
        .modal-content { background: var(--mp-bg-card); border-radius: 16px; padding: 25px; text-align: center; max-width: 350px; }
        .modal h2 { margin-bottom: 20px; color: var(--mp-accent); }
        .color-choice { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .color-btn { width: 60px; height: 60px; border-radius: 50%; font-size: 2rem; border: 3px solid transparent; cursor: pointer; transition: all 0.2s; }
        .color-btn:hover { transform: scale(1.1); }
        .color-btn.herz { background: #fee; color: #e74c3c; }
        .color-btn.karo { background: #fee; color: #e74c3c; }
        .color-btn.pik { background: #eee; color: #2c3e50; }
        .color-btn.kreuz { background: #eee; color: #2c3e50; }

        .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 15px 25px; border-radius: 12px; font-weight: 600; z-index: 1000; }
        .toast.success { background: var(--mp-accent); color: var(--mp-bg-dark); }
        .toast.error { background: var(--mp-error); color: white; }
        .toast.info { background: var(--mp-bg-card); border: 2px solid var(--mp-accent); color: var(--mp-text); }

        /* Result */
        .result-card { background: var(--mp-bg-card); border-radius: 16px; padding: 30px; text-align: center; border: 3px solid var(--mp-accent); }

        @media (max-width: 600px) {
            .card-slot { margin-left: -30px; }
            .card-slot img { width: 55px; height: 85px; }
            .card-slot:hover { transform: translateY(-5px); }
            .card-slot.selected { transform: translateY(-10px); }
            .my-hand { padding: 10px; }
            .center-pile { gap: 15px; padding: 15px 20px; }
            .deck-pile img, .discard-pile img { width: 60px; height: 92px; }
            .deck-pile, .discard-pile { width: 60px; height: 92px; }
            .card-back-small { width: 25px; height: 38px; margin-left: -12px; }
        }
        @media (max-width: 400px) {
            .card-slot img { width: 45px; height: 70px; }
            .card-slot { margin-left: -25px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="maumau.php" class="back-link">← Mau Mau</a>
                <h1>🃏 <span>Mau Mau</span> vs Computer</h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>

        <!-- SETUP -->
        <div id="setupScreen" class="screen active">
            <div class="setup-container">
                <div style="font-size: 4rem; margin-bottom: 10px;">🃏</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Mau Mau vs Computer</h1>
                <p style="color: var(--mp-text-muted); margin-bottom: 25px;">Spiele gegen eine KI</p>

                <div class="setup-card">
                    <h2>🎯 Schwierigkeit</h2>
                    <div class="difficulty-grid">
                        <div class="diff-option selected" onclick="selectDifficulty(1, this)">
                            <div class="diff-name">😊 Leicht</div>
                            <div class="diff-desc">KI spielt die erste passende Karte</div>
                        </div>
                        <div class="diff-option" onclick="selectDifficulty(2, this)">
                            <div class="diff-name">🤔 Mittel</div>
                            <div class="diff-desc">KI priorisiert Sonderkarten (7er, Bube, Ass)</div>
                        </div>
                        <div class="diff-option" onclick="selectDifficulty(3, this)">
                            <div class="diff-name">😈 Schwer</div>
                            <div class="diff-desc">KI zaehlt Karten und optimiert Farbwahl</div>
                        </div>
                    </div>
                </div>

                <button class="btn" onclick="startGame()">Spiel starten</button>
                <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='maumau.php'">← Zurueck zu Multiplayer</button>
            </div>
        </div>

        <!-- GAME -->
        <div id="gameScreen" class="screen">
            <div class="game-container">
                <div class="play-area">
                    <div class="table-area">
                        <div class="opponent" id="opponentArea">
                            <div>🤖 Computer</div>
                            <div class="cards" id="opponentCards"></div>
                            <div style="font-size: 0.8rem; color: var(--mp-text-muted);" id="opponentCount">5 Karten</div>
                        </div>

                        <div class="center-pile">
                            <div class="deck-pile" id="deckPile" onclick="playerDraw()"></div>
                            <div class="discard-pile" id="discardPile"></div>
                        </div>

                        <div class="my-hand" id="myHand"></div>
                    </div>
                </div>

                <div class="sidebar">
                    <div class="info-card">
                        <div class="turn-info" id="turnInfo">
                            <div class="label">Am Zug:</div>
                            <div class="name" id="currentPlayerName">Du</div>
                        </div>
                        <div class="special-info" id="specialInfo" style="display: none;"></div>
                    </div>
                    <div class="info-card">
                        <h3>📊 Karten</h3>
                        <div id="cardCounts"></div>
                    </div>
                    <button class="btn mau" id="mauBtn" onclick="sayMau()" style="display: none;">📢 MAU!</button>
                    <div class="info-card">
                        <h3>📜 Regeln</h3>
                        <ul style="font-size: 0.85rem; color: var(--mp-text-muted); list-style: none; padding: 0;">
                            <li>🎴 Gleiche Farbe oder Zahl legen</li>
                            <li>7️⃣ = Naechster zieht 2</li>
                            <li>8️⃣ = Naechster setzt aus</li>
                            <li>🃏 Bube = Wunschfarbe</li>
                            <li>🅰️ Ass = Aussetzen (bei 2 Spielern)</li>
                            <li>📢 Bei 2 Karten: "Mau!" rufen</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- RESULT -->
        <div id="resultScreen" class="screen">
            <div class="setup-container">
                <div class="result-card">
                    <div style="font-size: 5rem;" id="resultEmoji">🏆</div>
                    <h1 style="margin: 20px 0;" id="winnerText">Gewinner!</h1>
                    <button class="btn" onclick="startGame()">🔄 Nochmal spielen</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="showScreen('setup')">⚙️ Schwierigkeit aendern</button>
                    <button class="btn secondary" style="margin-top: 10px;" onclick="location.href='maumau.php'">← Zurueck</button>
                </div>
            </div>
        </div>

        <!-- Wish Color Modal -->
        <div class="modal" id="wishModal">
            <div class="modal-content">
                <h2>🎨 Wunschfarbe waehlen</h2>
                <div class="color-choice">
                    <button class="color-btn herz" onclick="selectWishColor('herz')">♥</button>
                    <button class="color-btn karo" onclick="selectWishColor('karo')">♦</button>
                    <button class="color-btn pik" onclick="selectWishColor('pik')">♠</button>
                    <button class="color-btn kreuz" onclick="selectWishColor('kreuz')">♣</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/playing-cards.js"></script>
    <script>
    (() => {
        const SUITS = ['herz', 'karo', 'pik', 'kreuz'];
        const VALUES = ['7', '8', '9', '10', 'bube', 'dame', 'koenig', 'ass'];
        const SYMBOLS = { herz: '♥', karo: '♦', pik: '♠', kreuz: '♣' };

        let difficulty = 1;
        let game = null;
        let selectedCard = null;
        let pendingWishIndex = null;
        let saidMau = false;
        let aiThinking = false;
        let playerName = '<?php echo addslashes($userName ?: "Du"); ?>';

        // ===== SETUP =====
        window.selectDifficulty = function(level, el) {
            difficulty = level;
            document.querySelectorAll('.diff-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
        };

        window.showScreen = function(name) {
            document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
            document.getElementById(name + 'Screen').classList.add('active');
        };

        // ===== GAME LOGIC =====
        function createDeck() {
            const deck = [];
            for (const suit of SUITS) {
                for (const value of VALUES) {
                    deck.push({ color: suit, value });
                }
            }
            // Shuffle (Fisher-Yates)
            for (let i = deck.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [deck[i], deck[j]] = [deck[j], deck[i]];
            }
            return deck;
        }

        window.startGame = function() {
            const deck = createDeck();
            const playerHand = deck.splice(0, 5);
            const aiHand = deck.splice(0, 5);

            // Find valid start card (no special cards)
            let startCardIdx = deck.findIndex(c => !['7', '8', 'bube', 'ass'].includes(c.value));
            if (startCardIdx === -1) startCardIdx = 0;
            const startCard = deck.splice(startCardIdx, 1)[0];

            game = {
                deck,
                discardPile: [startCard],
                playerHand,
                aiHand,
                currentPlayer: 'player', // player or ai
                drawStack: 0,
                wishColor: null,
                direction: 1,
                playedCards: [startCard] // for card counting AI
            };
            selectedCard = null;
            pendingWishIndex = null;
            saidMau = false;
            aiThinking = false;

            showScreen('game');
            render();
        };

        function getTopCard() {
            return game.discardPile[game.discardPile.length - 1];
        }

        function canPlay(card, topCard, wishColor, drawStack) {
            if (drawStack > 0 && card.value !== '7') return false;
            if (card.value === 'bube' && topCard.value !== 'bube') return true;
            if (wishColor) return card.color === wishColor || card.value === 'bube';
            return card.color === topCard.color || card.value === topCard.value;
        }

        function refillDeck() {
            if (game.deck.length === 0 && game.discardPile.length > 1) {
                const top = game.discardPile.pop();
                game.deck = game.discardPile;
                game.discardPile = [top];
                // Shuffle
                for (let i = game.deck.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [game.deck[i], game.deck[j]] = [game.deck[j], game.deck[i]];
                }
            }
        }

        function drawCards(hand, count) {
            const drawn = [];
            for (let i = 0; i < count; i++) {
                refillDeck();
                if (game.deck.length === 0) break;
                const card = game.deck.shift();
                hand.push(card);
                drawn.push(card);
            }
            return drawn;
        }

        function nextTurn(skip) {
            if (skip) {
                // Skip = stay on same player (2-player: "aussetzen" means nochmal)
                // Actually in 2-player, skip means the OTHER player is skipped, so current goes again
                // Current player already played, so next is the other player, but they get skipped
                // => effectively current player goes again = no change
            } else {
                game.currentPlayer = game.currentPlayer === 'player' ? 'ai' : 'player';
            }
        }

        function playCardAction(hand, cardIndex, wishColor) {
            const card = hand[cardIndex];
            const topCard = getTopCard();

            if (!canPlay(card, topCard, game.wishColor, game.drawStack)) return false;

            hand.splice(cardIndex, 1);
            game.discardPile.push(card);
            game.playedCards.push(card);

            let skip = false;
            game.wishColor = null;

            switch (card.value) {
                case '7':
                    game.drawStack += 2;
                    break;
                case '8':
                    skip = true;
                    break;
                case 'bube':
                    game.wishColor = wishColor;
                    break;
                case 'ass':
                    skip = true; // 2-player: skip opponent
                    break;
            }

            nextTurn(skip);
            return true;
        }

        // ===== PLAYER ACTIONS =====
        window.selectCard = function(index) {
            if (game.currentPlayer !== 'player' || aiThinking) return;
            if (selectedCard === index) {
                // Double click = play
                const card = game.playerHand[index];
                const topCard = getTopCard();
                if (!canPlay(card, topCard, game.wishColor, game.drawStack)) {
                    showToast('Karte passt nicht!', 'error');
                    return;
                }
                if (card.value === 'bube' && topCard.value !== 'bube') {
                    pendingWishIndex = index;
                    document.getElementById('wishModal').classList.add('active');
                    return;
                }
                executePlayerPlay(index, null);
            } else {
                selectedCard = index;
                render();
            }
        };

        window.selectWishColor = function(color) {
            document.getElementById('wishModal').classList.remove('active');
            if (pendingWishIndex !== null) {
                executePlayerPlay(pendingWishIndex, color);
                pendingWishIndex = null;
            }
        };

        function executePlayerPlay(cardIndex, wishColor) {
            // Mau penalty check: if player has 2 cards and didn't say Mau before playing
            if (game.playerHand.length === 2 && !saidMau) {
                // Penalty: draw 2 cards
                drawCards(game.playerHand, 2);
                showToast('Mau vergessen! +2 Strafkarten', 'error');
                saidMau = false;
            }

            if (playCardAction(game.playerHand, cardIndex, wishColor)) {
                selectedCard = null;
                saidMau = false;

                if (game.playerHand.length === 0) {
                    render();
                    endGame('player');
                    return;
                }

                render();
                if (game.currentPlayer === 'ai') {
                    scheduleAiTurn();
                }
            }
        }

        window.playerDraw = function() {
            if (game.currentPlayer !== 'player' || aiThinking) return;
            const count = Math.max(1, game.drawStack);
            const drawn = drawCards(game.playerHand, count);
            game.drawStack = 0;
            game.wishColor = null;
            saidMau = false;

            showToast(`${drawn.length} Karte(n) gezogen`, 'info');
            game.currentPlayer = 'ai';
            selectedCard = null;
            render();
            scheduleAiTurn();
        };

        window.sayMau = function() {
            if (game.playerHand.length === 2) {
                saidMau = true;
                showToast('Mau! 🎉', 'success');
                render();
            }
        };

        // ===== AI LOGIC =====
        function scheduleAiTurn() {
            aiThinking = true;
            render();
            const delay = [0, 800, 1200, 1500][difficulty] + Math.random() * 500;
            setTimeout(aiTurn, delay);
        }

        function aiTurn() {
            if (game.currentPlayer !== 'ai') { aiThinking = false; render(); return; }

            const topCard = getTopCard();
            const playableIndices = [];
            for (let i = 0; i < game.aiHand.length; i++) {
                if (canPlay(game.aiHand[i], topCard, game.wishColor, game.drawStack)) {
                    playableIndices.push(i);
                }
            }

            if (playableIndices.length === 0) {
                // Must draw
                const count = Math.max(1, game.drawStack);
                drawCards(game.aiHand, count);
                game.drawStack = 0;
                game.wishColor = null;
                game.currentPlayer = 'player';
                aiThinking = false;
                showToast(`Computer zieht ${count} Karte(n)`, 'info');
                render();
                return;
            }

            let chosenIndex;
            let wishColor = null;

            if (difficulty === 1) {
                chosenIndex = aiEasy(playableIndices);
            } else if (difficulty === 2) {
                chosenIndex = aiMedium(playableIndices);
            } else {
                chosenIndex = aiHard(playableIndices);
            }

            const chosenCard = game.aiHand[chosenIndex];

            // Choose wish color for Bube
            if (chosenCard.value === 'bube') {
                wishColor = aiChooseWishColor();
            }

            playCardAction(game.aiHand, chosenIndex, wishColor);
            aiThinking = false;

            if (game.aiHand.length === 0) {
                render();
                endGame('ai');
                return;
            }

            render();

            // If AI gets another turn (skip), schedule again
            if (game.currentPlayer === 'ai') {
                scheduleAiTurn();
            }
        }

        function aiEasy(playableIndices) {
            // Play first playable card
            return playableIndices[0];
        }

        function aiMedium(playableIndices) {
            // Prioritize special cards: 7 > Bube > Ass > 8 > rest
            const priorities = { '7': 5, 'bube': 4, 'ass': 3, '8': 2 };
            let best = playableIndices[0];
            let bestPriority = 0;
            for (const idx of playableIndices) {
                const p = priorities[game.aiHand[idx].value] || 1;
                if (p > bestPriority) {
                    bestPriority = p;
                    best = idx;
                }
            }
            return best;
        }

        function aiHard(playableIndices) {
            // Count remaining cards per suit
            const suitCount = { herz: 0, karo: 0, pik: 0, kreuz: 0 };
            for (const card of game.aiHand) {
                suitCount[card.color]++;
            }

            let best = playableIndices[0];
            let bestScore = -999;

            for (const idx of playableIndices) {
                const card = game.aiHand[idx];
                let score = 0;

                // Prefer playing cards of suits we have many of
                score += suitCount[card.color] * 2;

                // Prefer special cards when advantageous
                if (card.value === '7' && game.playerHand.length <= 3) score += 10;
                if (card.value === '8') score += 3;
                if (card.value === 'ass') score += 3;

                // Save Buben for later unless we're running low
                if (card.value === 'bube') {
                    if (game.aiHand.length <= 3) score += 8;
                    else score -= 2;
                }

                // Prefer getting rid of isolated suits
                if (suitCount[card.color] === 1 && card.value !== 'bube') score += 4;

                if (score > bestScore) {
                    bestScore = score;
                    best = idx;
                }
            }
            return best;
        }

        function aiChooseWishColor() {
            if (difficulty <= 1) {
                // Random
                return SUITS[Math.floor(Math.random() * 4)];
            }
            // Choose suit with most cards in hand
            const suitCount = { herz: 0, karo: 0, pik: 0, kreuz: 0 };
            for (const card of game.aiHand) {
                if (card.value !== 'bube') suitCount[card.color]++;
            }
            // For hard difficulty, also consider played cards
            if (difficulty === 3) {
                for (const card of game.playedCards) {
                    suitCount[card.color] -= 0.3; // Slight penalty for depleted suits
                }
            }
            let best = 'herz';
            let bestCount = -1;
            for (const suit of SUITS) {
                if (suitCount[suit] > bestCount) {
                    bestCount = suitCount[suit];
                    best = suit;
                }
            }
            return best;
        }

        function endGame(winner) {
            const resultEmoji = document.getElementById('resultEmoji');
            const winnerText = document.getElementById('winnerText');
            if (winner === 'player') {
                resultEmoji.textContent = '🏆';
                winnerText.textContent = 'Du hast gewonnen!';
            } else {
                resultEmoji.textContent = '😔';
                winnerText.textContent = 'Computer gewinnt!';
            }
            setTimeout(() => showScreen('result'), 500);
        }

        // ===== RENDERING =====
        function render() {
            if (!game) return;
            const topCard = getTopCard();
            const isMyTurn = game.currentPlayer === 'player' && !aiThinking;

            // Opponent
            const oppArea = document.getElementById('opponentArea');
            oppArea.className = 'opponent' + (game.currentPlayer === 'ai' ? ' active' : '');
            const oppCards = document.getElementById('opponentCards');
            const backSrc = PLAYING_CARD_SVGS.back;
            oppCards.innerHTML = Array(Math.min(game.aiHand.length, 10)).fill(0).map(() =>
                `<div class="card-back-small" style="background-image:url('${backSrc}')"></div>`
            ).join('');
            document.getElementById('opponentCount').textContent = `${game.aiHand.length} Karten`;

            // Deck
            const deckEl = document.getElementById('deckPile');
            deckEl.innerHTML = `<img src="${backSrc}"><img src="${backSrc}"><img src="${backSrc}">` +
                `<span class="deck-count">${game.deck.length}</span>`;

            // Discard
            const discardEl = document.getElementById('discardPile');
            const topKey = PLAYING_CARD_SVGS.getKey(topCard);
            discardEl.innerHTML = `<img src="${PLAYING_CARD_SVGS[topKey]}">`;

            // My hand
            const handEl = document.getElementById('myHand');
            handEl.innerHTML = game.playerHand.map((card, i) => {
                const playable = isMyTurn && canPlay(card, topCard, game.wishColor, game.drawStack);
                const sel = selectedCard === i;
                const key = PLAYING_CARD_SVGS.getKey(card);
                return `<div class="card-slot ${playable ? 'playable' : ''} ${sel ? 'selected' : ''}" onclick="selectCard(${i})">
                    <img src="${PLAYING_CARD_SVGS[key]}">
                </div>`;
            }).join('');

            // Turn info
            const turnInfo = document.getElementById('turnInfo');
            turnInfo.className = 'turn-info' + (isMyTurn ? ' my-turn' : '');
            const nameEl = document.getElementById('currentPlayerName');
            if (aiThinking) {
                nameEl.innerHTML = '<span class="thinking">🤖 Computer denkt</span>';
            } else {
                nameEl.textContent = isMyTurn ? `😊 ${playerName}` : '🤖 Computer';
            }

            // Special info
            const specialInfo = document.getElementById('specialInfo');
            if (game.drawStack > 0) {
                specialInfo.style.display = 'block';
                specialInfo.className = 'special-info danger';
                specialInfo.innerHTML = `⚠️ ${game.drawStack} Karten ziehen oder 7 legen!`;
            } else if (game.wishColor) {
                specialInfo.style.display = 'block';
                specialInfo.className = 'special-info warning';
                specialInfo.innerHTML = `🎨 Wunsch: ${SYMBOLS[game.wishColor]}`;
            } else {
                specialInfo.style.display = 'none';
            }

            // Card counts
            document.getElementById('cardCounts').innerHTML = `
                <div style="display:flex;justify-content:space-between;padding:5px 0;${isMyTurn ? 'color:var(--mp-accent);' : ''}">
                    <span>😊 ${playerName}</span><span>${game.playerHand.length} 🃏</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:5px 0;${!isMyTurn ? 'color:var(--mp-accent);' : ''}">
                    <span>🤖 Computer</span><span>${game.aiHand.length} 🃏</span>
                </div>`;

            // Mau button
            document.getElementById('mauBtn').style.display =
                (game.playerHand.length === 2 && isMyTurn && !saidMau) ? 'block' : 'none';
        }

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
