<?php
/**
 * ============================================================================
 * sgiT Education Platform - Multiplayer Game Hub
 * ============================================================================
 *
 * Kindgerechter Spiele-Hub mit Kategorien, grossen Spielkarten und
 * direktem Zugang zu allen Multiplayer-Spielen.
 *
 * Flow: [Dashboard] -> [Game Hub] -> [Spiel] (2 Klicks)
 *
 * @author sgiT Solution Engineering & IT Services
 * @version 2.0
 * @date 2026-02-14
 */

require_once __DIR__ . '/includes/game_header.php';

// Wallet-DB fuer Sats-Anzeige
$walletBalance = 0;
if ($walletChildId) {
    try {
        $walletDb = new PDO('sqlite:' . __DIR__ . '/wallet/wallet.db');
        $walletDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $walletDb->prepare("SELECT balance_sats, elo_rating, matches_played, matches_won FROM child_wallets WHERE id = ?");
        $stmt->execute([$walletChildId]);
        $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userStats) {
            $walletBalance = $userStats['balance_sats'] ?? 0;
            $userAvatar = $userStats['avatar'] ?? $userAvatar;
        }
    } catch (Exception $e) {
        // Wallet nicht verfuegbar - kein Abbruch
    }
}

// Spiele-Definitionen mit Kategorien
$games = [
    'brettspiele' => [
        'label' => 'Brettspiele',
        'icon' => "\u{1F3B2}",
        'games' => [
            [
                'name' => "Mensch \u{00E4}rgere dich nicht",
                'icon' => "\u{1F3B2}",
                'players' => '2-4 Spieler',
                'href' => '/madn.php',
                'computer' => '/madn.php?mode=computer',
                'description' => 'Der Klassiker!',
            ],
            [
                'name' => 'Dame',
                'icon' => "\u{26AB}",
                'players' => '2 Spieler',
                'href' => '/dame.php',
                'computer' => '/dame_vs_computer.php',
                'description' => 'Strategisch ziehen & schlagen',
            ],
            [
                'name' => 'Schach',
                'icon' => "\u{265F}\u{FE0F}",
                'players' => '2 Spieler',
                'href' => '/schach_pvp.php',
                'computer' => '/schach_pvp.php?mode=computer',
                'description' => "Das k\u{00F6}nigliche Spiel",
            ],
        ],
    ],
    'kartenspiele' => [
        'label' => 'Kartenspiele',
        'icon' => "\u{1F0CF}",
        'games' => [
            [
                'name' => 'Mau Mau',
                'icon' => "\u{1F0CF}",
                'players' => '2-4 Spieler',
                'href' => '/maumau.php',
                'computer' => '/maumau.php?mode=computer',
                'description' => 'Karten ablegen & gewinnen!',
            ],
            [
                'name' => "Romm\u{00E9}",
                'icon' => "\u{1F3B4}",
                'players' => '2-4 Spieler',
                'href' => '/romme.php',
                'computer' => '/romme.php?mode=computer',
                'description' => "S\u{00E4}tze & Reihen bilden",
            ],
            [
                'name' => 'Poker',
                'icon' => "\u{1F3B0}",
                'players' => '2-8 Spieler',
                'href' => '/poker.php',
                'computer' => '/poker.php?mode=computer',
                'description' => "Texas Hold'em",
            ],
        ],
    ],
    'kreativ' => [
        'label' => 'Kreativ & Quiz',
        'icon' => "\u{1F3A8}",
        'games' => [
            [
                'name' => 'Montagsmaler',
                'icon' => "\u{1F3A8}",
                'players' => '2-8 Spieler',
                'href' => '/montagsmaler.php',
                'computer' => null,
                'description' => 'Zeichne & Rate!',
            ],
            [
                'name' => 'Quiz-Match',
                'icon' => "\u{2694}\u{FE0F}",
                'players' => '2-4 Spieler',
                'href' => '/multiplayer.php?view=quiz',
                'computer' => null,
                'description' => 'Wissen duellieren!',
            ],
        ],
    ],
];

// Wenn Quiz-View direkt angefragt wird -> zeige Quiz-Modus
$showQuiz = isset($_GET['view']) && $_GET['view'] === 'quiz';

// Module fuer Quiz
$modules = [
    'mathematik' => ['icon' => "\u{1F522}", 'name' => 'Mathematik'],
    'englisch' => ['icon' => "\u{1F1EC}\u{1F1E7}", 'name' => 'Englisch'],
    'lesen' => ['icon' => "\u{1F4D6}", 'name' => 'Lesen'],
    'wissenschaft' => ['icon' => "\u{1F52C}", 'name' => 'Wissenschaft'],
    'erdkunde' => ['icon' => "\u{1F30D}", 'name' => 'Erdkunde'],
    'chemie' => ['icon' => "\u{2697}\u{FE0F}", 'name' => 'Chemie'],
    'physik' => ['icon' => "\u{269B}\u{FE0F}", 'name' => 'Physik'],
    'geschichte' => ['icon' => "\u{1F4DA}", 'name' => 'Geschichte'],
    'biologie' => ['icon' => "\u{1F9EC}", 'name' => 'Biologie'],
    'computer' => ['icon' => "\u{1F4BB}", 'name' => 'Computer'],
    'bitcoin' => ['icon' => "\u{20BF}", 'name' => 'Bitcoin'],
    'finanzen' => ['icon' => "\u{1F4B0}", 'name' => 'Finanzen'],
    'kunst' => ['icon' => "\u{1F3A8}", 'name' => 'Kunst'],
    'musik' => ['icon' => "\u{1F3B5}", 'name' => 'Musik'],
    'programmieren' => ['icon' => "\u{1F468}\u{200D}\u{1F4BB}", 'name' => 'Programmieren'],
    'verkehr' => ['icon' => "\u{1F697}", 'name' => 'Verkehr'],
    'sport' => ['icon' => "\u{1F3C3}", 'name' => 'Sport'],
    'unnuetzes_wissen' => ['icon' => "\u{1F92F}", 'name' => "Unn\u{00FC}tzes Wissen"],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $showQuiz ? "\u{2694}\u{FE0F} Quiz-Match" : "\u{1F3AE} Spiele-Hub"; ?> - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/multiplayer-theme.css">
    <?php if ($showQuiz): ?>
    <style>
        /* Quiz-spezifische Styles (nur bei Quiz-View) */
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, var(--mp-bg-dark) 0%, var(--mp-primary) 100%);
            min-height: 100vh;
            color: var(--mp-text);
            margin: 0; padding: 0;
        }
        .view { display: none; }
        .view.active { display: block; }
        .quiz-header-bar {
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(0,0,0,0.3); padding: 15px 20px; border-radius: 12px; margin-bottom: 20px;
        }
        .timer { font-size: 32px; font-weight: 700; color: var(--mp-accent); }
        .timer.warning { color: var(--mp-orange); }
        .timer.danger { color: #e74c3c; animation: pulse 0.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .question-box {
            background: var(--mp-bg-card); border: 1px solid var(--mp-border);
            border-radius: 16px; padding: 30px; margin-bottom: 20px; text-align: center;
        }
        .question-number { color: var(--mp-text-muted); font-size: 14px; margin-bottom: 10px; }
        .question-text { font-size: 22px; font-weight: 600; line-height: 1.5; }
        .options-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; }
        .option-btn {
            background: var(--mp-bg-card); border: 2px solid var(--mp-border); border-radius: 12px;
            padding: 20px; font-size: 16px; color: var(--mp-text); cursor: pointer; transition: all 0.3s;
        }
        .option-btn:hover:not(:disabled) { border-color: var(--mp-accent); background: rgba(67, 210, 64, 0.1); }
        .option-btn.selected { border-color: var(--mp-accent); background: rgba(67, 210, 64, 0.2); }
        .option-btn.correct { border-color: var(--mp-accent); background: rgba(67, 210, 64, 0.3); }
        .option-btn.wrong { border-color: #e74c3c; background: rgba(231, 76, 60, 0.3); }
        .option-btn:disabled { cursor: not-allowed; opacity: 0.7; }
        .scoreboard {
            display: flex; justify-content: space-around; background: rgba(0,0,0,0.3);
            padding: 15px; border-radius: 12px; margin-top: 20px;
        }
        .score-item { text-align: center; }
        .score-item .avatar { font-size: 24px; }
        .score-item .name { font-size: 14px; margin: 5px 0; }
        .score-item .score { font-size: 20px; font-weight: 700; color: var(--mp-accent); }
        .result-box { text-align: center; padding: 40px; }
        .result-icon { font-size: 80px; margin-bottom: 20px; }
        .result-title { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .result-subtitle { color: var(--mp-text-muted); margin-bottom: 30px; }
        .result-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin: 30px 0; }
        .result-stat { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; }
        .result-stat .value { font-size: 28px; font-weight: 700; color: var(--mp-accent); }
        .result-stat .label { font-size: 14px; color: var(--mp-text-muted); }
        .final-scores { margin: 30px 0; }
        .final-score-row {
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(0,0,0,0.2); padding: 15px 20px; border-radius: 12px; margin-bottom: 10px;
        }
        .final-score-row.winner { border: 2px solid var(--mp-accent); background: rgba(67, 210, 64, 0.1); }
        .final-score-row .player { display: flex; align-items: center; gap: 10px; }
        .final-score-row .score { font-size: 24px; font-weight: 700; }
        .toast {
            position: fixed; bottom: 20px; right: 20px; background: var(--mp-bg-card);
            border: 1px solid var(--mp-border); border-radius: 12px; padding: 15px 20px;
            display: none; z-index: 1000; animation: slideIn 0.3s;
        }
        .toast.show { display: flex; align-items: center; gap: 10px; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .loading { text-align: center; padding: 40px; color: var(--mp-text-muted); }
        .spinner { width: 40px; height: 40px; border: 4px solid var(--mp-border); border-top-color: var(--mp-accent); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 15px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (max-width: 600px) {
            .options-grid { grid-template-columns: 1fr; }
        }
    </style>
    <?php endif; ?>
</head>
<body class="mp-game-body">
    <?php if ($showQuiz): ?>
    <!-- ============================================ -->
    <!-- QUIZ-MATCH MODUS (eigener View)              -->
    <!-- ============================================ -->
    <div class="mp-game-container" style="max-width: 1200px;">
        <!-- Header -->
        <div class="mp-game-header">
            <div>
                <a href="/multiplayer.php" class="mp-game-header__back">&larr; Spiele-Hub</a>
                <h1>&#x2694;&#xFE0F; <span>Quiz-Match</span></h1>
            </div>
            <div class="mp-user-info">
                <span class="mp-user-info__avatar"><?= htmlspecialchars($userAvatar) ?></span>
                <span class="mp-user-info__name"><?= htmlspecialchars($userName ?: 'Gast') ?></span>
                <?php if (isset($userStats['elo_rating'])): ?>
                <span class="mp-user-info__elo">&#x1F3C6; <?= $userStats['elo_rating'] ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$walletChildId): ?>
        <div class="mp-lobby-card" style="text-align: center; max-width: 500px; margin: 50px auto;">
            <div style="font-size: 48px; margin-bottom: 20px;">&#x1F512;</div>
            <h2>Wallet-Login erforderlich</h2>
            <p style="color: var(--mp-text-muted); margin: 20px 0;">Um Quiz-Match zu spielen, musst du eingeloggt sein.</p>
            <a href="/adaptive_learning.php" class="mp-game-btn" style="width: auto; display: inline-flex;">Zum Login &rarr;</a>
        </div>
        <?php else: ?>

        <!-- MENU VIEW -->
        <div id="menuView" class="view active">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">
                <div class="mp-lobby-card" style="cursor: pointer;" onclick="showView('createView')">
                    <div style="font-size: 48px;">&#x1F3AE;</div>
                    <h2>Match erstellen</h2>
                    <p style="color: var(--mp-text-muted);">Erstelle ein Match und lade Freunde ein</p>
                </div>
                <div class="mp-lobby-card" style="cursor: pointer;" onclick="showView('joinView')">
                    <div style="font-size: 48px;">&#x1F3AB;</div>
                    <h2>Match beitreten</h2>
                    <p style="color: var(--mp-text-muted);">Tritt einem Match mit Code bei</p>
                </div>
            </div>
        </div>

        <!-- CREATE VIEW -->
        <div id="createView" class="view">
            <div class="mp-lobby-card" style="max-width: 600px; margin: 20px auto;">
                <h2>&#x1F3AE; Neues Match erstellen</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="mp-lobby-input-group">
                        <label>Spielmodus</label>
                        <select id="matchType" class="mp-select">
                            <option value="1v1">&#x2694;&#xFE0F; 1 vs 1 - Duell</option>
                            <option value="2v2">&#x1F465; 2 vs 2 - Team</option>
                            <option value="coop">&#x1F91D; Coop - Zusammen</option>
                        </select>
                    </div>
                    <div class="mp-lobby-input-group">
                        <label>Modul</label>
                        <select id="moduleSelect" class="mp-select">
                            <?php foreach ($modules as $key => $mod): ?>
                            <option value="<?= $key ?>"><?= $mod['icon'] ?> <?= $mod['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="mp-lobby-input-group">
                        <label>Fragen</label>
                        <select id="questionsCount" class="mp-select">
                            <option value="5">5 Fragen</option>
                            <option value="10" selected>10 Fragen</option>
                            <option value="15">15 Fragen</option>
                            <option value="20">20 Fragen</option>
                        </select>
                    </div>
                    <div class="mp-lobby-input-group">
                        <label>Zeit pro Frage</label>
                        <select id="timePerQuestion" class="mp-select">
                            <option value="10">10 Sekunden</option>
                            <option value="15" selected>15 Sekunden</option>
                            <option value="20">20 Sekunden</option>
                            <option value="30">30 Sekunden</option>
                        </select>
                    </div>
                </div>
                <div class="mp-lobby-input-group">
                    <label>&#x1F3B0; Sats-Einsatz (0 = kein Einsatz)</label>
                    <input type="number" id="satsBet" value="0" min="0" max="100" step="5">
                    <small style="color: var(--mp-text-muted);">Dein Guthaben: <?= $walletBalance ?> Sats</small>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button class="mp-game-btn mp-game-btn--secondary" onclick="showView('menuView')" style="flex:1;">Abbrechen</button>
                    <button class="mp-game-btn" onclick="createMatch()" style="flex:2;">&#x1F3AE; Match erstellen</button>
                </div>
            </div>
        </div>

        <!-- JOIN VIEW -->
        <div id="joinView" class="view">
            <div class="mp-lobby-card" style="max-width: 500px; margin: 20px auto;">
                <h2>&#x1F3AB; Match beitreten</h2>
                <div class="mp-lobby-input-group">
                    <label>Match-Code eingeben</label>
                    <input type="text" id="joinCode" class="mp-lobby-code-input" maxlength="6" placeholder="ABC123"
                           oninput="this.value = this.value.toUpperCase()">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button class="mp-game-btn mp-game-btn--secondary" onclick="showView('menuView')" style="flex:1;">Abbrechen</button>
                    <button class="mp-game-btn" onclick="joinMatch()" style="flex:2;">&#x1F3AB; Beitreten</button>
                </div>
            </div>
        </div>

        <!-- LOBBY VIEW -->
        <div id="lobbyView" class="view">
            <div class="mp-lobby-card" style="max-width: 600px; margin: 20px auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 style="margin: 0;">&#x1F3DF;&#xFE0F; Match-Lobby</h2>
                    <div class="mp-lobby-code-display" style="margin: 0; padding: 10px 20px;">
                        <div style="font-size: 0.7rem; color: var(--mp-text-muted);">Code</div>
                        <div class="mp-lobby-code" id="lobbyCode" style="font-size: 1.5rem;">------</div>
                    </div>
                </div>
                <div id="matchInfo" style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin: 15px 0;"></div>
                <div id="playersGrid" class="mp-lobby-players"></div>
                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                    <button class="mp-game-btn mp-game-btn--secondary mp-game-btn--small" onclick="leaveMatch()">Verlassen</button>
                    <button class="mp-game-btn mp-game-btn--small" id="readyBtn" onclick="toggleReady()">&#x2705; Bereit</button>
                    <button class="mp-game-btn mp-game-btn--small" id="startBtn" onclick="startMatch()" style="display: none;">&#x1F680; Start</button>
                </div>
            </div>
        </div>

        <!-- QUIZ VIEW -->
        <div id="quizView" class="view">
            <div class="quiz-header-bar">
                <div><span id="quizModuleIcon">&#x1F522;</span> <span id="quizModuleName">Mathematik</span></div>
                <div class="timer" id="timer">15</div>
                <div>Frage <span id="questionNum">1</span>/<span id="questionTotal">10</span></div>
            </div>
            <div class="question-box">
                <div class="question-number" id="questionLabel">Frage 1 von 10</div>
                <div class="question-text" id="questionText">Lade Frage...</div>
            </div>
            <div class="options-grid" id="optionsGrid"></div>
            <div class="scoreboard" id="scoreboard"></div>
        </div>

        <!-- RESULT VIEW -->
        <div id="resultView" class="view">
            <div class="mp-lobby-card" style="max-width: 600px; margin: 20px auto;">
                <div class="result-box">
                    <div class="result-icon" id="resultIcon">&#x1F3C6;</div>
                    <div class="result-title" id="resultTitle">Match beendet!</div>
                    <div class="result-subtitle" id="resultSubtitle">Gut gespielt!</div>
                    <div class="final-scores" id="finalScores"></div>
                    <div class="result-stats" id="resultStats"></div>
                    <div style="margin-top: 30px;">
                        <button class="mp-game-btn" onclick="showView('menuView'); resetMatch();">&#x1F3E0; Neues Match</button>
                        <a href="/multiplayer.php" class="mp-game-btn mp-game-btn--secondary" style="margin-top: 10px;">&#x1F3AE; Spiele-Hub</a>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast">
        <span id="toastIcon">&#x2139;&#xFE0F;</span>
        <span id="toastText">Nachricht</span>
    </div>

    <script>
    // Quiz-Match JavaScript (nur bei Quiz-View geladen)
    let currentMatchId = null;
    let currentMatchCode = null;
    let isHost = false;
    let isReady = false;
    let pollInterval = null;
    let timerInterval = null;
    let currentQuestion = null;
    let timeLeft = 15;
    let questionStartTime = 0;
    let hasAnswered = false;

    const modules = <?= json_encode($modules) ?>;
    const userId = <?= $walletChildId ?: 'null' ?>;

    function showView(viewId) {
        document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
        document.getElementById(viewId)?.classList.add('active');
        if (viewId !== 'lobbyView' && viewId !== 'quizView') stopPolling();
    }

    function showToast(message, icon = '\u2139\uFE0F') {
        const toast = document.getElementById('toast');
        document.getElementById('toastIcon').textContent = icon;
        document.getElementById('toastText').textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    async function apiCall(action, data = {}) {
        try {
            const response = await fetch('/api/match.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ...data })
            });
            const text = await response.text();
            try { return JSON.parse(text); }
            catch (e) { return { success: false, error: 'JSON Parse Error' }; }
        } catch (err) {
            return { success: false, error: 'Netzwerkfehler: ' + err.message };
        }
    }

    async function apiGet(action, params = {}) {
        const query = new URLSearchParams({ action, ...params }).toString();
        try {
            const response = await fetch(`/api/match.php?${query}`);
            const text = await response.text();
            try { return JSON.parse(text); }
            catch (e) { return { success: false, error: 'JSON Parse Error' }; }
        } catch (err) {
            return { success: false, error: 'Netzwerkfehler' };
        }
    }

    async function createMatch() {
        const data = {
            match_type: document.getElementById('matchType').value,
            module: document.getElementById('moduleSelect').value,
            questions: parseInt(document.getElementById('questionsCount').value),
            time: parseInt(document.getElementById('timePerQuestion').value),
            sats_bet: parseInt(document.getElementById('satsBet').value)
        };
        const result = await apiCall('create', data);
        if (result.success) {
            currentMatchId = result.match_id;
            currentMatchCode = result.match_code;
            isHost = true;
            const codeEl = document.getElementById('lobbyCode');
            if (codeEl) codeEl.textContent = result.match_code || 'NO CODE';
            showToast('Match erstellt! Code: ' + result.match_code, '\u{1F3AE}');
            showView('lobbyView');
            startPolling();
        } else {
            showToast(result.error || 'Fehler beim Erstellen', '\u274C');
        }
    }

    async function joinMatch() {
        const code = document.getElementById('joinCode').value.trim().toUpperCase();
        if (code.length !== 6) { showToast('Code muss 6 Zeichen haben', '\u26A0\uFE0F'); return; }
        const result = await apiCall('join', { code });
        if (result.success) {
            currentMatchId = result.match_id;
            currentMatchCode = result.match_code || code;
            isHost = false;
            showToast('Match beigetreten!', '\u{1F3AB}');
            showView('lobbyView');
            startPolling();
        } else {
            showToast(result.error || 'Fehler beim Beitreten', '\u274C');
        }
    }

    async function leaveMatch() {
        if (!currentMatchId) return;
        await apiCall('leave', { match_id: currentMatchId });
        resetMatch();
        showView('menuView');
        showToast('Match verlassen', '\u{1F44B}');
    }

    function resetMatch() {
        currentMatchId = null; currentMatchCode = null;
        isHost = false; isReady = false;
        stopPolling(); stopTimer();
    }

    async function toggleReady() {
        if (!currentMatchId) return;
        const result = await apiCall('ready', { match_id: currentMatchId });
        if (result.success) {
            isReady = result.is_ready;
            const btn = document.getElementById('readyBtn');
            btn.textContent = isReady ? '\u23F3 Warten...' : '\u2705 Bereit';
        }
    }

    async function startMatch() {
        if (!currentMatchId || !isHost) return;
        const result = await apiCall('start', { match_id: currentMatchId });
        if (result.success) showToast('Match startet!', '\u{1F680}');
        else showToast(result.error || 'Fehler beim Starten', '\u274C');
    }

    function startPolling() {
        stopPolling();
        pollMatch();
        pollInterval = setInterval(pollMatch, 500);
    }

    function stopPolling() {
        if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    }

    async function pollMatch() {
        if (!currentMatchId) return;
        const result = await apiGet('status', { match_id: currentMatchId });
        if (!result.success || !result.match) return;
        const match = result.match;
        if (match.status === 'waiting' || match.status === 'ready') updateLobby(result);
        else if (match.status === 'running') {
            if (document.getElementById('lobbyView').classList.contains('active')) {
                showView('quizView'); initQuiz(result);
            }
            updateQuiz(result);
        } else if (match.status === 'finished') { stopPolling(); showResult(result); }
        else if (match.status === 'cancelled') {
            stopPolling(); showToast('Match abgebrochen', '\u274C');
            resetMatch(); showView('menuView');
        }
    }

    function updateLobby(data) {
        if (!data || !data.match) return;
        const match = data.match;
        const players = data.players || [];
        document.getElementById('lobbyCode').textContent = match.code || currentMatchCode;
        currentMatchCode = match.code || currentMatchCode;
        const mod = modules[match.module] || { icon: '\u2753', name: match.module };
        document.getElementById('matchInfo').innerHTML =
            `<span class="mp-badge mp-badge--playing">${mod.icon} ${mod.name}</span>` +
            `<span class="mp-badge mp-badge--waiting">\u2753 ${match.questions_total} Fragen</span>` +
            `<span class="mp-badge mp-badge--waiting">\u23F1\uFE0F ${match.time_per_question}s</span>` +
            `<span class="mp-badge mp-badge--playing">\u{1F3AE} ${match.type}</span>` +
            (match.sats_pool > 0 ? `<span class="mp-badge mp-badge--ready">\u{1F3B0} ${match.sats_pool} Sats</span>` : '');
        const maxPlayers = match.type === '2v2' ? 4 : 2;
        let html = '';
        for (let i = 0; i < maxPlayers; i++) {
            const p = players[i];
            if (p) {
                const isMe = p.player_id == userId;
                html += `<div class="mp-lobby-player-slot filled">${p.is_ready ? '\u2705 ' : ''}${p.avatar} <strong>${p.child_name}${isMe ? ' (Du)' : ''}</strong><br><small>\u{1F3C6} ${p.elo_rating}</small></div>`;
            } else {
                html += `<div class="mp-lobby-player-slot">\u2753<br>Wartet...</div>`;
            }
        }
        document.getElementById('playersGrid').innerHTML = html;
        isHost = data.is_host;
        const startBtn = document.getElementById('startBtn');
        const allReady = players.length >= 2 && players.every(p => p.is_ready);
        if (isHost && players.length >= 2) { startBtn.style.display = 'inline-flex'; startBtn.disabled = !allReady; }
        else { startBtn.style.display = 'none'; }
    }

    function initQuiz(data) {
        const match = data.match;
        const mod = modules[match.module] || { icon: '\u2753', name: match.module };
        document.getElementById('quizModuleIcon').textContent = mod.icon;
        document.getElementById('quizModuleName').textContent = mod.name;
        document.getElementById('questionTotal').textContent = match.questions_total;
        timeLeft = match.time_per_question;
        hasAnswered = false;
    }

    function updateQuiz(data) {
        const match = data.match;
        const question = data.current_question;
        const players = data.players || [];
        const answers = data.answers_given || [];
        if (question && question.index !== currentQuestion?.index) {
            currentQuestion = question;
            hasAnswered = false;
            questionStartTime = Date.now();
            document.getElementById('questionNum').textContent = question.index;
            document.getElementById('questionLabel').textContent = `Frage ${question.index} von ${match.questions_total}`;
            document.getElementById('questionText').textContent = question.question;
            let optHtml = '';
            question.options.forEach(opt => {
                optHtml += `<button class="option-btn" onclick="submitAnswer('${opt.replace(/'/g, "\\'")}')">${opt}</button>`;
            });
            document.getElementById('optionsGrid').innerHTML = optHtml;
            timeLeft = match.time_per_question;
            startTimer();
        }
        const myAnswer = answers.find(a => a.player_id == userId);
        if (myAnswer && !hasAnswered) {
            hasAnswered = true;
            document.querySelectorAll('.option-btn').forEach(btn => btn.disabled = true);
        }
        let scoreHtml = '';
        players.forEach(p => {
            const answered = answers.some(a => a.player_id == p.player_id);
            scoreHtml += `<div class="score-item"><div class="avatar">${p.avatar}</div><div class="name">${p.child_name} ${answered ? '\u2705' : '\u23F3'}</div><div class="score">${p.score}</div></div>`;
        });
        document.getElementById('scoreboard').innerHTML = scoreHtml;
    }

    function startTimer() {
        stopTimer(); updateTimerDisplay();
        timerInterval = setInterval(() => {
            timeLeft--;
            updateTimerDisplay();
            if (timeLeft <= 0) { stopTimer(); if (!hasAnswered) submitAnswer(''); }
        }, 1000);
    }

    function stopTimer() { if (timerInterval) { clearInterval(timerInterval); timerInterval = null; } }

    function updateTimerDisplay() {
        const timer = document.getElementById('timer');
        timer.textContent = timeLeft;
        timer.classList.remove('warning', 'danger');
        if (timeLeft <= 5) timer.classList.add('danger');
        else if (timeLeft <= 10) timer.classList.add('warning');
    }

    async function submitAnswer(answer) {
        if (hasAnswered || !currentMatchId) return;
        hasAnswered = true; stopTimer();
        const timeTaken = Date.now() - questionStartTime;
        document.querySelectorAll('.option-btn').forEach(btn => {
            btn.disabled = true;
            if (btn.textContent === answer) btn.classList.add('selected');
        });
        const result = await apiCall('answer', { match_id: currentMatchId, answer, time_taken_ms: timeTaken });
        if (result.success) {
            document.querySelectorAll('.option-btn').forEach(btn => {
                if (btn.textContent === result.correct_answer) btn.classList.add('correct');
                if (btn.textContent === answer && !result.is_correct) btn.classList.add('wrong');
            });
            showToast(result.is_correct ? `+${result.points_earned} Punkte!` : 'Leider falsch!', result.is_correct ? '\u2705' : '\u274C');
        }
    }

    function showResult(data) {
        showView('resultView'); stopTimer();
        const match = data.match;
        const players = data.players || [];
        const userPlayer = data.user_player;
        const isWinner = match.winner_id && match.winner_id == userId;
        const isDraw = !match.winner_id && match.status === 'finished';
        let icon = '\u{1F3AE}', title = 'Match beendet!', subtitle = 'Gut gespielt!';
        if (isWinner) { icon = '\u{1F3C6}'; title = 'Du hast gewonnen!'; subtitle = match.sats_pool > 0 ? `+${match.sats_pool} Sats!` : 'Herzlichen Gl\u00FCckwunsch!'; }
        else if (isDraw) { icon = '\u{1F91D}'; title = 'Unentschieden!'; subtitle = 'Was ein Kopf-an-Kopf-Rennen!'; }
        else if (match.winner_id) { icon = '\u{1F622}'; title = 'Leider verloren...'; subtitle = 'N\u00E4chstes Mal klappt es!'; }
        document.getElementById('resultIcon').textContent = icon;
        document.getElementById('resultTitle').textContent = title;
        document.getElementById('resultSubtitle').textContent = subtitle;
        const sorted = [...players].sort((a, b) => b.score - a.score);
        let scoresHtml = '';
        sorted.forEach((p, i) => {
            const isMe = p.player_id == userId;
            const winner = p.player_id == match.winner_id;
            scoresHtml += `<div class="final-score-row ${winner ? 'winner' : ''}"><div class="player"><span>${i + 1}.</span> <span>${p.avatar}</span> <span>${p.child_name}${isMe ? ' (Du)' : ''}</span></div><div class="score">${p.score}</div></div>`;
        });
        document.getElementById('finalScores').innerHTML = scoresHtml;
        if (userPlayer) {
            document.getElementById('resultStats').innerHTML =
                `<div class="result-stat"><div class="value">${userPlayer.correct_answers}/${match.questions_total}</div><div class="label">Richtige Antworten</div></div>` +
                `<div class="result-stat"><div class="value">${userPlayer.score}</div><div class="label">Punkte</div></div>` +
                `<div class="result-stat"><div class="value">${(userPlayer.total_time_ms / 1000).toFixed(1)}s</div><div class="label">Gesamt-Zeit</div></div>`;
        }
    }

    // URL Parameter check
    window.addEventListener('load', () => {
        const params = new URLSearchParams(window.location.search);
        const code = params.get('code');
        if (code && code.length === 6) {
            document.getElementById('joinCode').value = code.toUpperCase();
            showView('joinView');
        }
    });
    </script>

    <?php else: ?>
    <!-- ============================================ -->
    <!-- GAME HUB (Standard-Ansicht)                  -->
    <!-- ============================================ -->
    <div class="mp-game-container" style="max-width: 900px;">
        <!-- Header -->
        <div class="mp-game-header">
            <div>
                <a href="/adaptive_learning.php" class="mp-game-header__back">&larr; Dashboard</a>
                <h1>&#x1F3AE; <span>Spiele-Hub</span></h1>
            </div>
            <div class="mp-user-info">
                <span class="mp-user-info__avatar"><?= htmlspecialchars($userAvatar) ?></span>
                <span class="mp-user-info__name"><?= htmlspecialchars($userName ?: 'Gast') ?></span>
                <?php if ($walletChildId && $walletBalance > 0): ?>
                <span class="mp-user-info__elo"><?= $walletBalance ?> Sats</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$walletChildId): ?>
        <!-- Login-Hinweis -->
        <div class="mp-lobby-card" style="text-align: center; max-width: 500px; margin: 50px auto;">
            <div style="font-size: 48px; margin-bottom: 20px;">&#x1F3AE;</div>
            <h2>Willkommen bei den Spielen!</h2>
            <p style="color: var(--mp-text-muted); margin: 20px 0;">
                Logge dich ein um Multiplayer-Spiele mit Freunden zu spielen und Sats zu sammeln!
            </p>
            <a href="/adaptive_learning.php" class="mp-game-btn" style="width: auto; display: inline-flex;">Zum Login &rarr;</a>
            <p style="color: var(--mp-text-muted); margin-top: 15px; font-size: 0.85rem;">
                Oder starte direkt ein Spiel gegen den Computer:
            </p>
        </div>
        <?php endif; ?>

        <!-- Game Categories -->
        <?php foreach ($games as $catKey => $category): ?>
        <div class="mp-hub-category">
            <div class="mp-hub-category__title">
                <?= $category['icon'] ?> <?= $category['label'] ?>
            </div>
            <div class="mp-hub-grid">
                <?php foreach ($category['games'] as $game): ?>
                <div class="mp-game-card">
                    <span class="mp-game-card__icon"><?= $game['icon'] ?></span>
                    <div class="mp-game-card__name"><?= $game['name'] ?></div>
                    <div class="mp-game-card__players"><?= $game['players'] ?></div>
                    <div class="mp-game-card__actions">
                        <a href="<?= $game['href'] ?>" class="mp-game-card__action mp-game-card__action--friends">
                            &#x1F465; Freunde
                        </a>
                        <?php if ($game['computer']): ?>
                        <a href="<?= $game['computer'] ?>" class="mp-game-card__action mp-game-card__action--computer">
                            &#x1F916; Computer
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</body>
</html>
