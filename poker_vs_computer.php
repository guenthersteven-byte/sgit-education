<?php
/**
 * ============================================================================
 * sgiT Education - Poker vs Computer (Texas Hold'em) v1.0
 * ============================================================================
 *
 * Texas Hold'em gegen 2-4 KI-Gegner (komplett clientseitig)
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * ============================================================================
 */

session_start();
require_once 'includes/version.php';
require_once __DIR__ . '/wallet/SessionManager.php';

$userName = '';
$walletChildId = 0;
$userAvatar = '😀';

if (SessionManager::isLoggedIn()) {
    $childData = SessionManager::getChild();
    if ($childData) {
        $walletChildId = $childData['id'];
        $userName = $childData['name'];
        $userAvatar = $childData['avatar'] ?? '😀';
    }
} elseif (isset($_SESSION['wallet_child_id'])) {
    $walletChildId = $_SESSION['wallet_child_id'];
    $userName = $_SESSION['user_name'] ?? $_SESSION['child_name'] ?? '';
    $userAvatar = $_SESSION['avatar'] ?? '😀';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poker vs Computer - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <style>
        /* ===========================================
           Poker vs Computer - Styles
           =========================================== */
        :root {
            --card-white: #fffef5;
            --table-green: #1a6b3a;
            --table-border: #5d3a1a;
            --felt-dark: #0d4f24;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--mp-bg-dark) 0%, var(--mp-primary) 100%);
            min-height: 100vh;
            color: var(--mp-text);
            margin: 0; padding: 0;
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 15px; }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 20px; background: var(--mp-bg-card); border-radius: 12px;
            margin-bottom: 20px; border: 1px solid var(--mp-border);
        }
        .header h1 { font-size: 1.4rem; margin: 0; }
        .header h1 span { color: var(--mp-accent); }
        .back-link { color: var(--mp-accent); text-decoration: none; font-size: 0.9rem; }
        .back-link:hover { text-decoration: underline; }

        .screen { display: none; }
        .screen.active { display: block; animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

        /* --- Setup Screen --- */
        .setup-container { max-width: 500px; margin: 30px auto; text-align: center; }
        .setup-card {
            background: var(--mp-bg-card); border-radius: 16px; padding: 25px;
            margin-bottom: 20px; border: 1px solid var(--mp-border);
        }
        .setup-card h2 { color: var(--mp-accent); margin: 0 0 20px 0; }
        .setup-row { margin-bottom: 18px; text-align: left; }
        .setup-row label { display: block; margin-bottom: 6px; color: var(--mp-text-muted); font-size: 0.9rem; }
        .setup-row select, .setup-row input[type="text"] {
            width: 100%; padding: 12px; border: 2px solid transparent; border-radius: 10px;
            background: var(--mp-bg-input); color: var(--mp-text); font-size: 1rem;
            box-sizing: border-box;
        }
        .setup-row select:focus, .setup-row input:focus { outline: none; border-color: var(--mp-accent); }

        .btn {
            background: var(--mp-accent); color: var(--mp-primary); border: none;
            padding: 12px 24px; border-radius: 10px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: var(--mp-transition);
        }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--mp-shadow-glow); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn.full { width: 100%; }
        .btn.danger { background: var(--mp-error); color: white; }
        .btn.secondary { background: var(--mp-bg-card); color: var(--mp-text); border: 2px solid var(--mp-accent); }
        .btn.gold { background: var(--mp-gold); color: #333; }
        .btn.small { padding: 8px 16px; font-size: 0.9rem; }

        /* --- Game Screen --- */
        .table-wrapper {
            background: var(--mp-bg-card); border-radius: 16px; padding: 20px;
            border: 1px solid var(--mp-border); position: relative;
        }

        .poker-table {
            background: linear-gradient(135deg, var(--felt-dark), var(--table-green), var(--felt-dark));
            border: 12px solid var(--table-border);
            border-radius: 150px;
            min-height: 380px;
            position: relative;
            box-shadow: inset 0 0 50px rgba(0,0,0,0.4), 0 8px 32px rgba(0,0,0,0.5);
        }

        /* Community cards center */
        .community-area {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); text-align: center; z-index: 5;
        }
        .community-cards { display: flex; gap: 8px; justify-content: center; margin-bottom: 12px; }
        .community-cards img {
            width: 55px; height: 80px; border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.4);
            transition: transform 0.3s;
        }
        .community-cards img:hover { transform: translateY(-4px); }
        .community-cards .card-placeholder {
            width: 55px; height: 80px; border-radius: 5px;
            border: 2px dashed rgba(255,255,255,0.2); background: rgba(0,0,0,0.15);
        }

        .pot-display {
            background: rgba(0,0,0,0.6); padding: 8px 22px; border-radius: 20px;
            display: inline-block; backdrop-filter: blur(4px);
        }
        .pot-display .label { color: var(--mp-text-muted); font-size: 0.8rem; }
        .pot-display .amount { color: var(--mp-gold); font-size: 1.3rem; font-weight: bold; }

        /* Stage indicator */
        .stage-indicator {
            position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
            background: rgba(0,0,0,0.5); padding: 4px 14px; border-radius: 12px;
            font-size: 0.8rem; color: var(--mp-accent); font-weight: 600; z-index: 10;
            backdrop-filter: blur(4px);
        }

        /* --- Player seats --- */
        .seat {
            position: absolute; text-align: center;
            transform: translate(-50%, -50%); z-index: 6;
        }
        /* Positions for 2-5 total players (player + 1-4 bots) */
        .seats-2 .seat-0 { top: 92%; left: 50%; }
        .seats-2 .seat-1 { top: 8%; left: 50%; }

        .seats-3 .seat-0 { top: 92%; left: 50%; }
        .seats-3 .seat-1 { top: 25%; left: 12%; }
        .seats-3 .seat-2 { top: 25%; left: 88%; }

        .seats-4 .seat-0 { top: 92%; left: 50%; }
        .seats-4 .seat-1 { top: 45%; left: 7%; }
        .seats-4 .seat-2 { top: 8%; left: 50%; }
        .seats-4 .seat-3 { top: 45%; left: 93%; }

        .seats-5 .seat-0 { top: 92%; left: 50%; }
        .seats-5 .seat-1 { top: 65%; left: 8%; }
        .seats-5 .seat-2 { top: 15%; left: 18%; }
        .seats-5 .seat-3 { top: 15%; left: 82%; }
        .seats-5 .seat-4 { top: 65%; left: 92%; }

        .player-seat {
            background: rgba(30, 58, 8, 0.9); border-radius: 12px; padding: 8px 10px;
            border: 2px solid transparent; transition: all 0.3s; min-width: 100px;
            backdrop-filter: blur(6px); position: relative;
        }
        .player-seat.active-turn {
            border-color: var(--mp-accent);
            box-shadow: 0 0 18px rgba(67,210,64,0.5);
            animation: pulseGlow 1.5s ease-in-out infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 12px rgba(67,210,64,0.4); }
            50% { box-shadow: 0 0 24px rgba(67,210,64,0.7); }
        }
        .player-seat.folded { opacity: 0.4; }
        .player-seat.is-me { border-color: var(--mp-gold); }
        .player-seat.is-me.active-turn { border-color: var(--mp-gold); box-shadow: 0 0 18px rgba(255,215,0,0.5); }
        .player-seat .avatar { font-size: 1.5rem; }
        .player-seat .name {
            font-size: 0.78rem; white-space: nowrap; overflow: hidden;
            text-overflow: ellipsis; max-width: 90px; margin: 0 auto;
        }
        .player-seat .chips-display { font-size: 0.72rem; color: var(--mp-gold); margin-top: 2px; }
        .player-seat .action-label {
            font-size: 0.65rem; color: var(--mp-accent); margin-top: 2px;
            font-weight: 600; min-height: 14px;
        }
        .player-seat .action-label.fold-label { color: var(--mp-error); }
        .player-seat .action-label.raise-label { color: var(--mp-orange); }

        .player-seat .bet-badge {
            position: absolute; font-size: 0.7rem;
            background: rgba(0,0,0,0.75); padding: 2px 8px; border-radius: 10px;
            color: var(--mp-gold); white-space: nowrap; backdrop-filter: blur(4px);
        }
        /* Bet badge positions depend on seat location - handled via JS */
        .player-seat .dealer-chip {
            position: absolute; top: -8px; right: -8px;
            background: white; color: #333; width: 22px; height: 22px;
            border-radius: 50%; font-size: 0.65rem; font-weight: bold;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.4);
        }

        .hole-cards {
            display: flex; gap: 4px; justify-content: center; margin-top: 5px;
        }
        .hole-cards img {
            width: 40px; height: 58px; border-radius: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }

        /* --- Actions panel --- */
        .actions-panel {
            background: var(--mp-bg-card); border-radius: 12px; padding: 15px;
            margin-top: 15px; display: flex; gap: 10px; justify-content: center;
            flex-wrap: wrap; align-items: center; border: 1px solid var(--mp-border);
        }
        .actions-panel.hidden { display: none; }

        .raise-group { display: flex; align-items: center; gap: 8px; }
        .raise-slider {
            width: 140px; accent-color: var(--mp-accent); cursor: pointer;
        }
        .raise-amount-label {
            background: var(--mp-bg-input); padding: 6px 12px; border-radius: 8px;
            font-weight: 600; color: var(--mp-gold); font-size: 0.9rem; min-width: 50px;
            text-align: center;
        }

        /* --- My cards panel --- */
        .my-cards-panel {
            background: var(--mp-bg-card); border-radius: 12px; padding: 15px;
            margin-top: 15px; text-align: center; border: 1px solid var(--mp-border);
        }
        .my-cards-panel h3 { color: var(--mp-accent); margin: 0 0 10px 0; font-size: 0.95rem; }
        .my-big-cards { display: flex; gap: 12px; justify-content: center; align-items: center; }
        .my-big-cards img {
            width: 70px; height: 100px; border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.4); transition: transform 0.2s;
        }
        .my-big-cards img:hover { transform: translateY(-6px) scale(1.05); }
        .hand-name { color: var(--mp-gold); font-weight: 600; margin-top: 8px; font-size: 0.9rem; }

        /* --- Info bar --- */
        .info-bar {
            display: flex; gap: 15px; margin-top: 15px; flex-wrap: wrap;
        }
        .info-chip {
            background: var(--mp-bg-card); border-radius: 10px; padding: 10px 16px;
            border: 1px solid var(--mp-border); flex: 1; min-width: 140px; text-align: center;
        }
        .info-chip .label { color: var(--mp-text-muted); font-size: 0.8rem; }
        .info-chip .val { font-weight: 700; font-size: 1.1rem; }
        .info-chip .val.gold { color: var(--mp-gold); }

        /* --- Game log --- */
        .game-log {
            background: var(--mp-bg-card); border-radius: 12px; padding: 15px;
            margin-top: 15px; max-height: 180px; overflow-y: auto;
            border: 1px solid var(--mp-border); font-size: 0.82rem;
        }
        .game-log h3 { color: var(--mp-accent); margin: 0 0 8px 0; font-size: 0.9rem; }
        .game-log .log-entry { padding: 3px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .game-log .log-entry:last-child { border-bottom: none; }
        .game-log .log-time { color: var(--mp-text-muted); margin-right: 6px; }

        /* --- Showdown overlay --- */
        .showdown-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); z-index: 100; justify-content: center; align-items: center;
            backdrop-filter: blur(4px);
        }
        .showdown-overlay.active { display: flex; }
        .showdown-box {
            background: var(--mp-bg-card); border-radius: 16px; padding: 30px;
            max-width: 600px; width: 90%; text-align: center; border: 2px solid var(--mp-accent);
            box-shadow: 0 0 40px rgba(67,210,64,0.3);
        }
        .showdown-box h2 { color: var(--mp-accent); margin: 0 0 20px 0; }
        .showdown-players { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
        .showdown-player {
            display: flex; align-items: center; gap: 12px; padding: 10px 15px;
            background: rgba(0,0,0,0.2); border-radius: 10px; text-align: left;
        }
        .showdown-player.winner { border: 2px solid var(--mp-gold); background: rgba(255,215,0,0.1); }
        .showdown-player .sp-name { font-weight: 600; min-width: 80px; }
        .showdown-player .sp-hand { color: var(--mp-gold); font-weight: 600; flex: 1; }
        .showdown-player .sp-cards { display: flex; gap: 4px; }
        .showdown-player .sp-cards img { width: 38px; height: 55px; border-radius: 3px; }
        .showdown-player .sp-winnings { color: var(--mp-accent); font-weight: 700; }

        /* --- Toast --- */
        .toast {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            padding: 12px 24px; border-radius: 12px; font-weight: 600; z-index: 200;
            animation: toastIn 0.3s ease-out;
        }
        @keyframes toastIn { from { opacity: 0; transform: translateX(-50%) translateY(20px); } to { opacity: 1; transform: translateX(-50%); } }
        .toast.info { background: var(--mp-bg-card); border: 2px solid var(--mp-accent); color: var(--mp-text); }
        .toast.success { background: var(--mp-accent); color: var(--mp-primary); }
        .toast.error { background: var(--mp-error); color: white; }

        /* --- Responsive --- */
        @media (max-width: 700px) {
            .poker-table { min-height: 280px; border-width: 8px; border-radius: 100px; }
            .community-cards img { width: 42px; height: 60px; }
            .community-cards .card-placeholder { width: 42px; height: 60px; }
            .hole-cards img { width: 32px; height: 46px; }
            .player-seat { min-width: 80px; padding: 6px 8px; }
            .player-seat .name { font-size: 0.7rem; max-width: 70px; }
            .my-big-cards img { width: 55px; height: 78px; }
            .actions-panel { padding: 10px; gap: 8px; }
            .btn { padding: 10px 16px; font-size: 0.9rem; }
            .raise-slider { width: 100px; }
        }
        @media (max-width: 450px) {
            .community-cards img { width: 34px; height: 50px; }
            .community-cards .card-placeholder { width: 34px; height: 50px; }
            .hole-cards img { width: 28px; height: 40px; }
            .player-seat { min-width: 65px; padding: 4px 6px; }
            .my-big-cards img { width: 45px; height: 64px; }
            .poker-table { min-height: 240px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="multiplayer.php" class="back-link">&#8592; Multiplayer</a>
                <h1>Poker <span>vs Computer</span></h1>
            </div>
            <span><?php echo $userAvatar . ' ' . htmlspecialchars($userName ?: 'Gast'); ?></span>
        </div>

        <!-- ======== SETUP SCREEN ======== -->
        <div id="setupScreen" class="screen active">
            <div class="setup-container">
                <div style="font-size: 3.5rem; margin-bottom: 10px;">&#127920;</div>
                <h1 style="font-size: 1.8rem; margin-bottom: 5px;">Texas Hold'em</h1>
                <p style="color: var(--mp-text-muted); margin-bottom: 25px;">Spiele gegen KI-Gegner</p>

                <div class="setup-card">
                    <h2>Spiel konfigurieren</h2>

                    <div class="setup-row" id="nameRow" style="<?php echo $userName ? 'display:none' : ''; ?>">
                        <label>Dein Name</label>
                        <input type="text" id="playerNameInput" placeholder="Name eingeben..." maxlength="20"
                               value="<?php echo htmlspecialchars($userName); ?>">
                    </div>

                    <div class="setup-row">
                        <label>Anzahl Gegner</label>
                        <select id="opponentCount">
                            <option value="1">1 Gegner (Heads-Up)</option>
                            <option value="2" selected>2 Gegner</option>
                            <option value="3">3 Gegner</option>
                            <option value="4">4 Gegner</option>
                        </select>
                    </div>

                    <div class="setup-row">
                        <label>Schwierigkeit</label>
                        <select id="difficultySelect">
                            <option value="1">Leicht (Loose-Passive)</option>
                            <option value="2" selected>Mittel (Tight-Aggressive)</option>
                            <option value="3">Schwer (Advanced)</option>
                        </select>
                    </div>

                    <button class="btn full" onclick="startGame()">Spiel starten</button>
                </div>
            </div>
        </div>

        <!-- ======== GAME SCREEN ======== -->
        <div id="gameScreen" class="screen">
            <div class="table-wrapper">
                <div class="poker-table" id="pokerTable">
                    <div class="stage-indicator" id="stageIndicator">Pre-Flop</div>
                    <div class="community-area" id="communityArea">
                        <div class="community-cards" id="communityCards"></div>
                        <div class="pot-display">
                            <span class="label">Pot: </span>
                            <span class="amount" id="potAmount">0</span>
                        </div>
                    </div>
                    <!-- Seats injected by JS -->
                </div>
            </div>

            <!-- Player actions -->
            <div class="actions-panel hidden" id="actionsPanel">
                <button class="btn danger" id="btnFold" onclick="playerAction('fold')">Fold</button>
                <button class="btn secondary" id="btnCall" onclick="playerAction('call')">Call</button>
                <div class="raise-group">
                    <button class="btn gold" id="btnRaise" onclick="playerAction('raise')">Raise</button>
                    <input type="range" class="raise-slider" id="raiseSlider" min="0" max="1000" step="10" value="40"
                           oninput="updateRaiseLabel()">
                    <span class="raise-amount-label" id="raiseLabel">40</span>
                </div>
            </div>

            <!-- My cards large -->
            <div class="my-cards-panel" id="myCardsPanel">
                <h3>Deine Karten</h3>
                <div class="my-big-cards" id="myBigCards"></div>
                <div class="hand-name" id="handName"></div>
            </div>

            <!-- Info bar -->
            <div class="info-bar">
                <div class="info-chip">
                    <div class="label">Deine Chips</div>
                    <div class="val gold" id="myChipsDisplay">1000</div>
                </div>
                <div class="info-chip">
                    <div class="label">Runde</div>
                    <div class="val" id="roundDisplay">1</div>
                </div>
                <div class="info-chip">
                    <div class="label">Blinds</div>
                    <div class="val" id="blindsDisplay">10 / 20</div>
                </div>
                <div class="info-chip" style="flex: 0 0 auto;">
                    <button class="btn small danger" onclick="endGame()">Spiel beenden</button>
                </div>
            </div>

            <!-- Game log -->
            <div class="game-log" id="gameLog">
                <h3>Spielverlauf</h3>
                <div id="logEntries"></div>
            </div>
        </div>

        <!-- ======== SHOWDOWN OVERLAY ======== -->
        <div class="showdown-overlay" id="showdownOverlay">
            <div class="showdown-box">
                <h2 id="showdownTitle">Showdown</h2>
                <div class="showdown-players" id="showdownPlayers"></div>
                <button class="btn full" id="btnNextRound" onclick="nextRound()">Naechste Runde</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/playing-cards.js"></script>
    <script src="/assets/js/poker-ai.js"></script>
    <script>
    'use strict';

    /* =============================================================
       GAME STATE
       ============================================================= */
    const BOT_PROFILES = [
        { name: 'Bot Alex',   avatar: '\uD83E\uDD16' },
        { name: 'Bot Sarah',  avatar: '\uD83E\uDDD1\u200D\uD83D\uDCBB' },
        { name: 'Bot Max',    avatar: '\uD83D\uDE08' },
        { name: 'Bot Luna',   avatar: '\uD83E\uDDDA' }
    ];

    const SUITS  = ['herz', 'karo', 'pik', 'kreuz'];
    const VALUES = ['2','3','4','5','6','7','8','9','10','bube','dame','koenig','ass'];

    let G = {}; // game state

    function freshState() {
        return {
            players: [],       // { name, avatar, chips, holeCards, folded, isBot, difficulty, currentBet, allIn }
            deck: [],
            community: [],
            pot: 0,
            sidePots: [],
            stage: 'preflop', // preflop | flop | turn | river | showdown
            dealerIdx: 0,
            currentIdx: 0,
            roundNum: 0,
            smallBlind: 10,
            bigBlind: 20,
            currentBetLevel: 0, // highest bet this round of betting
            lastRaiser: -1,
            actionsThisRound: 0,
            playerName: '',
            difficulty: 2,
            running: false,
            animating: false
        };
    }

    /* =============================================================
       DECK
       ============================================================= */
    function createDeck() {
        const d = [];
        for (const color of SUITS)
            for (const value of VALUES)
                d.push({ color, value });
        return d;
    }

    function shuffleDeck(deck) {
        for (let i = deck.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [deck[i], deck[j]] = [deck[j], deck[i]];
        }
        return deck;
    }

    function dealCard() {
        return G.deck.pop();
    }

    /* =============================================================
       CARD RENDERING
       ============================================================= */
    function cardImgSrc(card) {
        const key = `${card.color}_${card.value}`;
        return PLAYING_CARD_SVGS[key] || PLAYING_CARD_SVGS.back;
    }

    function cardBackSrc() {
        return PLAYING_CARD_SVGS.back;
    }

    function createCardImg(card, cls) {
        const img = document.createElement('img');
        img.src = cardImgSrc(card);
        img.alt = `${card.color}_${card.value}`;
        img.draggable = false;
        if (cls) img.className = cls;
        return img;
    }

    /* =============================================================
       UI HELPERS
       ============================================================= */
    function $(id) { return document.getElementById(id); }

    function showScreen(id) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        $(id).classList.add('active');
    }

    function toast(msg, type) {
        const el = document.createElement('div');
        el.className = 'toast ' + (type || 'info');
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 2500);
    }

    function log(msg) {
        const entries = $('logEntries');
        const now = new Date();
        const time = now.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const div = document.createElement('div');
        div.className = 'log-entry';
        div.innerHTML = `<span class="log-time">${time}</span>${msg}`;
        entries.prepend(div);
        // Keep max 80 entries
        while (entries.children.length > 80) entries.removeChild(entries.lastChild);
    }

    function updateRaiseLabel() {
        $('raiseLabel').textContent = $('raiseSlider').value;
    }

    /* =============================================================
       START GAME
       ============================================================= */
    function startGame() {
        const nameInput = $('playerNameInput');
        const name = nameInput ? nameInput.value.trim() : '';
        const playerName = name || '<?php echo addslashes($userName ?: "Spieler"); ?>';
        const numBots = parseInt($('opponentCount').value);
        const difficulty = parseInt($('difficultySelect').value);

        G = freshState();
        G.playerName = playerName;
        G.difficulty = difficulty;

        // Player at index 0
        G.players.push({
            name: playerName,
            avatar: '<?php echo $userAvatar; ?>',
            chips: 1000,
            holeCards: [],
            folded: false,
            isBot: false,
            difficulty: 0,
            currentBet: 0,
            allIn: false
        });

        // Bots
        for (let i = 0; i < numBots; i++) {
            const bot = BOT_PROFILES[i];
            G.players.push({
                name: bot.name,
                avatar: bot.avatar,
                chips: 1000,
                holeCards: [],
                folded: false,
                isBot: true,
                difficulty: difficulty,
                currentBet: 0,
                allIn: false
            });
        }

        G.running = true;
        G.dealerIdx = Math.floor(Math.random() * G.players.length);

        showScreen('gameScreen');
        buildSeats();
        startNewRound();
    }

    /* =============================================================
       SEAT RENDERING
       ============================================================= */
    function buildSeats() {
        const table = $('pokerTable');
        // Remove old seats
        table.querySelectorAll('.seat').forEach(s => s.remove());
        table.className = 'poker-table seats-' + G.players.length;

        G.players.forEach((p, idx) => {
            const seat = document.createElement('div');
            seat.className = `seat seat-${idx}`;
            seat.id = `seat-${idx}`;
            seat.innerHTML = `
                <div class="player-seat ${idx === 0 ? 'is-me' : ''}" id="pseat-${idx}">
                    <div class="dealer-chip" id="dealer-${idx}" style="display:none;">D</div>
                    <div class="avatar">${p.avatar}</div>
                    <div class="name">${escHtml(p.name)}</div>
                    <div class="chips-display" id="chips-${idx}">${p.chips}</div>
                    <div class="action-label" id="action-${idx}"></div>
                    <div class="hole-cards" id="hole-${idx}"></div>
                    <div class="bet-badge" id="bet-${idx}" style="display:none;"></div>
                </div>
            `;
            table.appendChild(seat);
        });
    }

    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function updateAllSeats() {
        G.players.forEach((p, idx) => {
            const ps = $(`pseat-${idx}`);
            if (!ps) return;

            // Chips
            $(`chips-${idx}`).textContent = p.chips;

            // Dealer button
            $(`dealer-${idx}`).style.display = idx === G.dealerIdx ? 'flex' : 'none';

            // Folded
            ps.classList.toggle('folded', p.folded);

            // Active turn
            ps.classList.toggle('active-turn', idx === G.currentIdx && G.stage !== 'showdown');

            // Hole cards
            const holeEl = $(`hole-${idx}`);
            holeEl.innerHTML = '';
            if (p.holeCards.length > 0) {
                if (idx === 0 || G.stage === 'showdown') {
                    // Show real cards for player and during showdown (if not folded)
                    if (idx === 0 || !p.folded) {
                        p.holeCards.forEach(c => {
                            const img = document.createElement('img');
                            img.src = cardImgSrc(c);
                            img.alt = 'card';
                            img.draggable = false;
                            holeEl.appendChild(img);
                        });
                    }
                } else {
                    // Card backs for bots during play
                    if (!p.folded) {
                        for (let i = 0; i < 2; i++) {
                            const img = document.createElement('img');
                            img.src = cardBackSrc();
                            img.alt = 'back';
                            img.draggable = false;
                            holeEl.appendChild(img);
                        }
                    }
                }
            }

            // Bet badge
            const betEl = $(`bet-${idx}`);
            if (p.currentBet > 0) {
                betEl.textContent = p.currentBet;
                betEl.style.display = 'block';
                // Position the badge towards the center
                betEl.style.bottom = '-18px';
                betEl.style.left = '50%';
                betEl.style.transform = 'translateX(-50%)';
            } else {
                betEl.style.display = 'none';
            }
        });

        // Pot
        $('potAmount').textContent = G.pot;
        $('myChipsDisplay').textContent = G.players[0].chips;
        $('roundDisplay').textContent = G.roundNum;
        $('blindsDisplay').textContent = G.smallBlind + ' / ' + G.bigBlind;

        // Stage
        const stageNames = { preflop: 'Pre-Flop', flop: 'Flop', turn: 'Turn', river: 'River', showdown: 'Showdown' };
        $('stageIndicator').textContent = stageNames[G.stage] || G.stage;

        // Community cards
        renderCommunity();

        // Player's big cards
        renderMyCards();
    }

    function renderCommunity() {
        const el = $('communityCards');
        el.innerHTML = '';
        for (let i = 0; i < 5; i++) {
            if (i < G.community.length) {
                const img = document.createElement('img');
                img.src = cardImgSrc(G.community[i]);
                img.alt = 'community';
                img.draggable = false;
                el.appendChild(img);
            } else {
                const ph = document.createElement('div');
                ph.className = 'card-placeholder';
                el.appendChild(ph);
            }
        }
    }

    function renderMyCards() {
        const el = $('myBigCards');
        el.innerHTML = '';
        const p = G.players[0];
        if (p && p.holeCards.length > 0) {
            p.holeCards.forEach(c => {
                const img = document.createElement('img');
                img.src = cardImgSrc(c);
                img.alt = 'my card';
                img.draggable = false;
                el.appendChild(img);
            });
        }

        // Hand name
        const hn = $('handName');
        if (p && p.holeCards.length === 2) {
            const hand = POKER_AI.evaluateHand(p.holeCards, G.community);
            hn.textContent = hand.name;
        } else {
            hn.textContent = '';
        }
    }

    /* =============================================================
       ROUND LOGIC
       ============================================================= */
    function startNewRound() {
        G.roundNum++;

        // Remove eliminated players (0 chips)
        const eliminated = G.players.filter((p, i) => i > 0 && p.chips <= 0);
        eliminated.forEach(p => {
            log(`<strong>${escHtml(p.name)}</strong> ist ausgeschieden!`);
        });
        G.players = G.players.filter((p, i) => i === 0 || p.chips > 0);

        // Check if player is out
        if (G.players[0].chips <= 0) {
            toast('Du hast alle Chips verloren!', 'error');
            log('Du bist ausgeschieden. Spiel vorbei.');
            endGame();
            return;
        }

        // Check if only player left
        if (G.players.length < 2) {
            toast('Du hast gewonnen! Alle Gegner eliminiert!', 'success');
            log('Alle Gegner sind eliminiert. Du gewinnst!');
            endGame();
            return;
        }

        // Rebuild seats if players removed
        buildSeats();

        // Advance dealer
        G.dealerIdx = (G.dealerIdx) % G.players.length;

        // Reset round state
        G.deck = shuffleDeck(createDeck());
        G.community = [];
        G.pot = 0;
        G.stage = 'preflop';
        G.currentBetLevel = 0;
        G.lastRaiser = -1;
        G.actionsThisRound = 0;

        G.players.forEach(p => {
            p.holeCards = [];
            p.folded = false;
            p.currentBet = 0;
            p.allIn = false;
        });

        // Blinds
        const sbIdx = nextActivePlayer(G.dealerIdx);
        const bbIdx = nextActivePlayer(sbIdx);

        placeBet(sbIdx, Math.min(G.smallBlind, G.players[sbIdx].chips));
        log(`${escHtml(G.players[sbIdx].name)} setzt Small Blind (${G.smallBlind})`);

        placeBet(bbIdx, Math.min(G.bigBlind, G.players[bbIdx].chips));
        log(`${escHtml(G.players[bbIdx].name)} setzt Big Blind (${G.bigBlind})`);

        G.currentBetLevel = G.bigBlind;

        // Deal hole cards
        for (let round = 0; round < 2; round++) {
            for (let i = 0; i < G.players.length; i++) {
                G.players[i].holeCards.push(dealCard());
            }
        }

        log(`--- Runde ${G.roundNum} gestartet ---`);

        // First to act preflop = after big blind
        G.currentIdx = nextActivePlayer(bbIdx);
        G.lastRaiser = bbIdx;
        G.actionsThisRound = 0;

        clearActionLabels();
        updateAllSeats();
        handleTurn();
    }

    function clearActionLabels() {
        G.players.forEach((_, i) => {
            const el = $(`action-${i}`);
            if (el) { el.textContent = ''; el.className = 'action-label'; }
        });
    }

    /* =============================================================
       BETTING
       ============================================================= */
    function placeBet(playerIdx, amount) {
        const p = G.players[playerIdx];
        const actual = Math.min(amount, p.chips);
        p.chips -= actual;
        p.currentBet += actual;
        G.pot += actual;
        if (p.chips === 0) p.allIn = true;
    }

    function nextActivePlayer(fromIdx) {
        let idx = (fromIdx + 1) % G.players.length;
        let safety = 0;
        while ((G.players[idx].folded || G.players[idx].allIn) && safety < G.players.length) {
            idx = (idx + 1) % G.players.length;
            safety++;
        }
        return idx;
    }

    function activePlayers() {
        return G.players.filter(p => !p.folded);
    }

    function activeNonAllIn() {
        return G.players.filter(p => !p.folded && !p.allIn);
    }

    function handleTurn() {
        if (G.animating) return;

        // Check: only 1 player left not folded
        if (activePlayers().length <= 1) {
            awardPotToLastStanding();
            return;
        }

        // Check: betting round complete
        if (isBettingComplete()) {
            advanceStage();
            return;
        }

        const p = G.players[G.currentIdx];

        // Skip folded or all-in
        if (p.folded || p.allIn) {
            G.currentIdx = nextActivePlayer(G.currentIdx);
            G.actionsThisRound++;
            setTimeout(() => handleTurn(), 50);
            return;
        }

        updateAllSeats();

        if (p.isBot) {
            // AI turn with delay
            G.animating = true;
            setTimeout(() => {
                executeBotTurn(G.currentIdx);
                G.animating = false;
            }, 800 + Math.random() * 600);
        } else {
            // Human turn - show controls
            showActionsPanel();
        }
    }

    function isBettingComplete() {
        const active = activeNonAllIn();
        if (active.length <= 1 && activePlayers().length >= 1) {
            // All active are all-in except maybe one
            const nonAllInActive = active.filter(p => !p.allIn);
            if (nonAllInActive.length <= 1) {
                // Check if everyone has acted or matched
                const allMatched = activePlayers().every(p => p.currentBet === G.currentBetLevel || p.allIn);
                if (allMatched && G.actionsThisRound >= activePlayers().length) return true;
            }
        }

        // Normal: everyone has matched the current bet level and had a chance to act
        const everyoneMatched = activePlayers().every(p => p.currentBet === G.currentBetLevel || p.allIn);
        return everyoneMatched && G.actionsThisRound >= activePlayers().length;
    }

    function showActionsPanel() {
        const panel = $('actionsPanel');
        panel.classList.remove('hidden');

        const p = G.players[0];
        const toCall = G.currentBetLevel - p.currentBet;

        // Update button labels
        $('btnCall').textContent = toCall > 0 ? `Call (${toCall})` : 'Check';
        $('btnFold').style.display = toCall > 0 ? '' : 'none';

        // Raise slider
        const minRaise = Math.max(G.bigBlind, G.currentBetLevel + G.bigBlind);
        const maxRaise = p.chips + p.currentBet;
        const slider = $('raiseSlider');
        slider.min = Math.min(minRaise, maxRaise);
        slider.max = maxRaise;
        slider.value = Math.min(minRaise, maxRaise);
        slider.step = G.bigBlind;
        updateRaiseLabel();

        $('btnRaise').textContent = toCall > 0 ? 'Raise' : 'Bet';
    }

    function hideActionsPanel() {
        $('actionsPanel').classList.add('hidden');
    }

    function playerAction(action) {
        if (G.currentIdx !== 0 || G.animating) return;

        const p = G.players[0];
        const toCall = G.currentBetLevel - p.currentBet;

        hideActionsPanel();

        if (action === 'fold') {
            p.folded = true;
            setActionLabel(0, 'Fold', 'fold-label');
            log(`<strong>${escHtml(p.name)}</strong> foldet.`);
        } else if (action === 'call') {
            if (toCall > 0) {
                const amt = Math.min(toCall, p.chips);
                placeBet(0, amt);
                setActionLabel(0, amt >= p.chips + toCall ? 'All-In' : 'Call ' + amt, '');
                log(`<strong>${escHtml(p.name)}</strong> callt ${amt}.`);
            } else {
                setActionLabel(0, 'Check', '');
                log(`<strong>${escHtml(p.name)}</strong> checkt.`);
            }
        } else if (action === 'raise') {
            const raiseTotal = parseInt($('raiseSlider').value);
            const raiseAmount = raiseTotal - p.currentBet;
            if (raiseAmount > 0) {
                placeBet(0, Math.min(raiseAmount, p.chips));
                G.currentBetLevel = p.currentBet;
                G.lastRaiser = 0;
                G.actionsThisRound = 0; // Reset so everyone gets to act again
                const label = p.allIn ? 'All-In!' : 'Raise ' + p.currentBet;
                setActionLabel(0, label, 'raise-label');
                log(`<strong>${escHtml(p.name)}</strong> raist auf ${p.currentBet}.`);
            }
        }

        G.actionsThisRound++;
        G.currentIdx = nextActivePlayer(G.currentIdx);
        updateAllSeats();
        setTimeout(() => handleTurn(), 300);
    }

    function setActionLabel(idx, text, cls) {
        const el = $(`action-${idx}`);
        if (el) {
            el.textContent = text;
            el.className = 'action-label ' + (cls || '');
        }
    }

    /* =============================================================
       BOT TURN
       ============================================================= */
    function executeBotTurn(idx) {
        const p = G.players[idx];
        if (p.folded || p.allIn) {
            G.actionsThisRound++;
            G.currentIdx = nextActivePlayer(idx);
            handleTurn();
            return;
        }

        const toCall = G.currentBetLevel - p.currentBet;

        // Compute strength
        let strength;
        if (G.stage === 'preflop') {
            strength = POKER_AI.preFlopStrength(p.holeCards);
        } else {
            strength = POKER_AI.calculateHandStrength(p.holeCards, G.community);
        }

        const potOdds = toCall > 0 ? toCall / (G.pot + toCall) : 0;
        const minRaise = Math.max(G.bigBlind, G.currentBetLevel + G.bigBlind);

        const decision = POKER_AI.decideBetAction(strength, potOdds, p.difficulty, G.stage, {
            callAmount: toCall,
            pot: G.pot,
            chips: p.chips,
            minRaise: minRaise,
            bigBlind: G.bigBlind
        });

        if (decision.action === 'fold') {
            if (toCall === 0) {
                // Don't fold when you can check
                setActionLabel(idx, 'Check', '');
                log(`<strong>${escHtml(p.name)}</strong> checkt.`);
            } else {
                p.folded = true;
                setActionLabel(idx, 'Fold', 'fold-label');
                log(`<strong>${escHtml(p.name)}</strong> foldet.`);
            }
        } else if (decision.action === 'call') {
            if (toCall > 0) {
                const amt = Math.min(toCall, p.chips);
                placeBet(idx, amt);
                setActionLabel(idx, p.allIn ? 'All-In' : 'Call', '');
                log(`<strong>${escHtml(p.name)}</strong> callt ${amt}.`);
            } else {
                setActionLabel(idx, 'Check', '');
                log(`<strong>${escHtml(p.name)}</strong> checkt.`);
            }
        } else if (decision.action === 'raise') {
            let raiseTotal = Math.max(decision.amount, minRaise);
            raiseTotal = Math.min(raiseTotal, p.chips + p.currentBet);
            const raiseAmount = raiseTotal - p.currentBet;
            if (raiseAmount > toCall && raiseAmount > 0) {
                placeBet(idx, Math.min(raiseAmount, p.chips));
                G.currentBetLevel = p.currentBet;
                G.lastRaiser = idx;
                G.actionsThisRound = 0;
                const label = p.allIn ? 'All-In!' : 'Raise ' + p.currentBet;
                setActionLabel(idx, label, 'raise-label');
                log(`<strong>${escHtml(p.name)}</strong> raist auf ${p.currentBet}.`);
            } else {
                // Can't raise enough, just call
                if (toCall > 0) {
                    placeBet(idx, Math.min(toCall, p.chips));
                    setActionLabel(idx, p.allIn ? 'All-In' : 'Call', '');
                    log(`<strong>${escHtml(p.name)}</strong> callt ${toCall}.`);
                } else {
                    setActionLabel(idx, 'Check', '');
                    log(`<strong>${escHtml(p.name)}</strong> checkt.`);
                }
            }
        }

        G.actionsThisRound++;
        G.currentIdx = nextActivePlayer(idx);
        updateAllSeats();
        setTimeout(() => handleTurn(), 300);
    }

    /* =============================================================
       STAGE PROGRESSION
       ============================================================= */
    function advanceStage() {
        // Reset bets for new street
        G.players.forEach(p => { p.currentBet = 0; });
        G.currentBetLevel = 0;
        G.lastRaiser = -1;
        G.actionsThisRound = 0;
        clearActionLabels();

        if (G.stage === 'preflop') {
            G.stage = 'flop';
            G.community.push(dealCard(), dealCard(), dealCard());
            log('--- Flop: ' + communityStr() + ' ---');
        } else if (G.stage === 'flop') {
            G.stage = 'turn';
            G.community.push(dealCard());
            log('--- Turn: ' + communityStr() + ' ---');
        } else if (G.stage === 'turn') {
            G.stage = 'river';
            G.community.push(dealCard());
            log('--- River: ' + communityStr() + ' ---');
        } else if (G.stage === 'river') {
            G.stage = 'showdown';
            doShowdown();
            return;
        }

        // Next to act after dealer (post-flop)
        G.currentIdx = nextActivePlayer(G.dealerIdx);

        // If only all-in players remain, run out board
        if (activeNonAllIn().length <= 1 && activePlayers().length > 1) {
            updateAllSeats();
            setTimeout(() => advanceStage(), 800);
            return;
        }

        updateAllSeats();
        setTimeout(() => handleTurn(), 400);
    }

    function communityStr() {
        return G.community.map(c => c.color + '_' + c.value).join(', ');
    }

    /* =============================================================
       SHOWDOWN & POT AWARD
       ============================================================= */
    function doShowdown() {
        const contenders = activePlayers();

        if (contenders.length <= 1) {
            awardPotToLastStanding();
            return;
        }

        // Evaluate hands
        const results = contenders.map(p => {
            const hand = POKER_AI.evaluateHand(p.holeCards, G.community);
            return { player: p, hand };
        });

        // Sort best first
        results.sort((a, b) => {
            const cmp = POKER_AI.compareHands(a.hand, b.hand);
            return -cmp; // descending
        });

        // Find winner(s) (could be ties)
        const winners = [results[0]];
        for (let i = 1; i < results.length; i++) {
            if (POKER_AI.compareHands(results[i].hand, results[0].hand) === 0) {
                winners.push(results[i]);
            } else break;
        }

        // Split pot
        const share = Math.floor(G.pot / winners.length);
        const remainder = G.pot - share * winners.length;
        winners.forEach((w, i) => {
            w.player.chips += share + (i === 0 ? remainder : 0);
            w.winnings = share + (i === 0 ? remainder : 0);
        });

        // Mark non-winners
        results.forEach(r => { if (!r.winnings) r.winnings = 0; });

        G.pot = 0;
        updateAllSeats();
        showShowdownOverlay(results, winners);
    }

    function awardPotToLastStanding() {
        const winner = activePlayers()[0];
        if (winner) {
            winner.chips += G.pot;
            log(`<strong>${escHtml(winner.name)}</strong> gewinnt ${G.pot} Chips (alle anderen gefoldet).`);
            toast(`${winner.name} gewinnt ${G.pot} Chips!`, 'success');
        }
        G.pot = 0;
        updateAllSeats();

        // Short pause then next round
        setTimeout(() => {
            advanceDealer();
            startNewRound();
        }, 2000);
    }

    function advanceDealer() {
        G.dealerIdx = (G.dealerIdx + 1) % G.players.length;
    }

    function showShowdownOverlay(results, winners) {
        const overlay = $('showdownOverlay');
        const container = $('showdownPlayers');
        container.innerHTML = '';

        const winnerNames = winners.map(w => w.player.name);

        if (winners.length === 1) {
            $('showdownTitle').textContent = `${winners[0].player.name} gewinnt!`;
        } else {
            $('showdownTitle').textContent = 'Unentschieden!';
        }

        results.forEach(r => {
            const isWinner = winnerNames.includes(r.player.name);
            const div = document.createElement('div');
            div.className = 'showdown-player' + (isWinner ? ' winner' : '');

            const cardsHtml = r.player.holeCards.map(c =>
                `<img src="${cardImgSrc(c)}" alt="card" draggable="false">`
            ).join('');

            div.innerHTML = `
                <span class="sp-name">${r.player.avatar} ${escHtml(r.player.name)}</span>
                <span class="sp-hand">${r.hand.name}</span>
                <span class="sp-cards">${cardsHtml}</span>
                <span class="sp-winnings">${isWinner ? '+' + r.winnings : ''}</span>
            `;
            container.appendChild(div);
        });

        winners.forEach(w => {
            log(`<strong>${escHtml(w.player.name)}</strong> gewinnt ${w.winnings} Chips mit ${w.hand.name}!`);
        });

        overlay.classList.add('active');
    }

    function nextRound() {
        $('showdownOverlay').classList.remove('active');
        advanceDealer();
        startNewRound();
    }

    /* =============================================================
       END GAME
       ============================================================= */
    function endGame() {
        G.running = false;
        hideActionsPanel();
        $('showdownOverlay').classList.remove('active');
        showScreen('setupScreen');
        toast('Spiel beendet', 'info');
    }
    </script>
</body>
</html>
