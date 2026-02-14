<?php
/**
 * ============================================================================
 * sgiT Education - Statistik Dashboard
 * ============================================================================
 *
 * ZENTRALE STATISTIK-SEITE
 * Alle Daten: Fragen, Module, Wallet, Foxy, Bot-Ergebnisse
 *
 * Nutzt zentrale Versionsverwaltung via /includes/version.php
 *
 * @version Siehe SGIT_VERSION
 * @date Siehe SGIT_VERSION_DATE
 * ============================================================================
 */

session_start();

// Zentrale Versionsverwaltung
require_once __DIR__ . '/includes/version.php';

// ============================================================================
// DATEN SAMMELN
// ============================================================================

$stats = [
    'questions' => ['total' => 0, 'ai' => 0, 'csv' => 0, 'with_explanation' => 0],
    'modules' => [],
    'difficulty' => [],
    'ages' => [],
    'wallet' => null,
    'foxy' => null,
    'bots' => null
];

// QUESTIONS DB
$questionsDb = __DIR__ . '/AI/data/questions.db';
if (file_exists($questionsDb)) {
    try {
        $db = new PDO('sqlite:' . $questionsDb);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stats['questions']['total'] = $db->query("SELECT COUNT(*) FROM questions WHERE is_active = 1")->fetchColumn();
        $stats['questions']['ai'] = $db->query("SELECT COUNT(*) FROM questions WHERE ai_generated = 1 AND is_active = 1")->fetchColumn();
        $stats['questions']['csv'] = $db->query("SELECT COUNT(*) FROM questions WHERE source = 'csv_import' AND is_active = 1")->fetchColumn() ?: 0;
        $stats['questions']['with_explanation'] = $db->query("SELECT COUNT(*) FROM questions WHERE explanation IS NOT NULL AND explanation != '' AND is_active = 1")->fetchColumn();

        // Module
        $r = $db->query("SELECT module, COUNT(*) as cnt FROM questions WHERE is_active = 1 GROUP BY module ORDER BY cnt DESC");
        while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
            $stats['modules'][$row['module']] = (int)$row['cnt'];
        }

        // Schwierigkeit
        $r = $db->query("SELECT difficulty, COUNT(*) as cnt FROM questions WHERE is_active = 1 GROUP BY difficulty ORDER BY difficulty");
        while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
            $stats['difficulty'][$row['difficulty']] = (int)$row['cnt'];
        }

        // Altersgruppen - gruppiert nach Mindestalter fuer sinnvolle Darstellung
        $r = $db->query("
            SELECT
                CASE
                    WHEN age_min <= 5 THEN 'ab 5'
                    WHEN age_min = 6 THEN 'ab 6'
                    WHEN age_min = 7 THEN 'ab 7'
                    WHEN age_min = 8 THEN 'ab 8'
                    WHEN age_min = 9 THEN 'ab 9'
                    WHEN age_min = 10 THEN 'ab 10'
                    WHEN age_min BETWEEN 11 AND 13 THEN 'ab 11-13'
                    WHEN age_min BETWEEN 14 AND 17 THEN 'ab 14-17'
                    ELSE 'ab 18+'
                END as age_group,
                COUNT(*) as cnt
            FROM questions WHERE is_active = 1
            GROUP BY age_group
            ORDER BY MIN(age_min)
        ");
        while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
            $stats['ages'][$row['age_group']] = (int)$row['cnt'];
        }

    } catch (Exception $e) {
        error_log("Statistics Questions Error: " . $e->getMessage());
        $stats['questions']['error'] = 'Datenbankfehler';
    }
}

// WALLET DB
$walletDb = __DIR__ . '/wallet/wallet.db';
if (file_exists($walletDb)) {
    try {
        $db = new PDO('sqlite:' . $walletDb);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $totalEarned = $db->query("SELECT COALESCE(SUM(total_earned), 0) FROM child_wallets")->fetchColumn();

        $stats['wallet'] = [
            'children' => $db->query("SELECT COUNT(*) FROM child_wallets")->fetchColumn() ?: 0,
            'total_sats' => (int)$totalEarned,
            'transactions' => $db->query("SELECT COUNT(*) FROM sat_transactions")->fetchColumn() ?: 0,
            'achievements' => $db->query("SELECT COUNT(*) FROM wallet_achievements")->fetchColumn() ?: 0,
            'top_kids' => $db->query("SELECT child_name as name, balance_sats as balance FROM child_wallets ORDER BY balance_sats DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC),
            'recent_tx' => $db->query("SELECT child_id, amount_sats as amount, type, reason as description, created_at FROM sat_transactions ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC)
        ];
    } catch (Exception $e) {}
}

// FOXY DB
$foxyDb = __DIR__ . '/clippy/foxy_chat.db';
if (file_exists($foxyDb)) {
    try {
        $db = new PDO('sqlite:' . $foxyDb);
        $stats['foxy'] = [
            'responses' => $db->query("SELECT COUNT(*) FROM foxy_responses")->fetchColumn(),
            'chats' => $db->query("SELECT COUNT(*) FROM foxy_history")->fetchColumn(),
            'categories' => []
        ];
        $r = $db->query("SELECT category, COUNT(*) as cnt FROM foxy_responses GROUP BY category ORDER BY cnt DESC");
        while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
            $stats['foxy']['categories'][$row['category']] = (int)$row['cnt'];
        }
    } catch (Exception $e) {}
}

// BOT DB
$botDb = __DIR__ . '/bots/logs/bot_results.db';
if (file_exists($botDb)) {
    try {
        $db = new SQLite3($botDb);
        $stats['bots'] = [
            'runs' => $db->querySingle("SELECT COUNT(*) FROM bot_runs") ?: 0,
            'tests' => $db->querySingle("SELECT COALESCE(SUM(total_tests), 0) FROM bot_runs WHERE status='completed'") ?: 0,
            'passed' => $db->querySingle("SELECT COALESCE(SUM(passed), 0) FROM bot_runs WHERE status='completed'") ?: 0,
            'errors' => $db->querySingle("SELECT COALESCE(SUM(errors), 0) FROM bot_runs WHERE status='completed'") ?: 0
        ];
        $db->close();
    } catch (Exception $e) {}
}

// Modul-Icons - vollstaendig
$moduleIcons = [
    'mathematik' => '🔢', 'physik' => '⚛️', 'chemie' => '🧪', 'biologie' => '🧬',
    'erdkunde' => '🌍', 'geschichte' => '📜', 'kunst' => '🎨', 'musik' => '🎵',
    'computer' => '💻', 'programmieren' => '👨‍💻', 'bitcoin' => '₿', 'steuern' => '💰',
    'englisch' => '🇬🇧', 'lesen' => '📖', 'wissenschaft' => '🔬', 'verkehr' => '🚗',
    'sport' => '🏃', 'unnuetzes_wissen' => '🤯', 'finanzen' => '💵', 'kochen' => '🍳',
    'zeichnen' => '✏️', 'logik' => '🧩'
];

// Erklaerung-Coverage berechnen
$explPct = $stats['questions']['total'] > 0
    ? round(($stats['questions']['with_explanation'] / $stats['questions']['total']) * 100, 1)
    : 0;

// Bot pass-rate berechnen
$botPassRate = ($stats['bots'] && $stats['bots']['tests'] > 0)
    ? round(($stats['bots']['passed'] / $stats['bots']['tests']) * 100, 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; }

        .header {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            color: white;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header h1 { font-size: 1.2rem; color: #fff; margin: 0; display: flex; align-items: center; gap: 10px; }
        .header .version-tag {
            font-size: 0.7rem;
            background: var(--accent);
            color: #000;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }
        .header-nav { display: flex; gap: 8px; }
        .header-nav a {
            padding: 8px 14px;
            background: rgba(255,255,255,0.08);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.85rem;
            transition: background 0.2s;
        }
        .header-nav a:hover { background: rgba(255,255,255,0.18); }

        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        /* Stats Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px 16px;
            text-align: center;
            border-top: 3px solid var(--accent);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card.orange { border-top-color: var(--orange); }
        .stat-card.blue { border-top-color: #3498db; }
        .stat-card.bitcoin { border-top-color: var(--bitcoin); }
        .stat-card .icon { font-size: 1.6rem; margin-bottom: 8px; }
        .stat-card .value { font-size: 1.8rem; font-weight: 700; color: var(--accent); line-height: 1.1; }
        .stat-card .label { font-size: 0.78rem; color: var(--text-muted); margin-top: 6px; }
        .stat-card .sub { font-size: 0.7rem; color: var(--text-muted); margin-top: 2px; opacity: 0.7; }

        @media (max-width: 900px) {
            .stats-overview { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 600px) {
            .stats-overview { grid-template-columns: repeat(2, 1fr); }
        }

        /* Section */
        .section {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--accent);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-badge {
            background: var(--accent);
            color: #000;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        /* Grid */
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
        @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }

        /* Chart */
        .chart-container { height: 260px; position: relative; }

        /* Module List */
        .module-list { max-height: 400px; overflow-y: auto; }
        .module-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .module-item:last-child { border-bottom: none; }
        .module-icon { font-size: 1.2rem; width: 32px; text-align: center; }
        .module-name { flex: 1; font-weight: 500; color: var(--text); font-size: 0.9rem; }
        .module-bar { flex: 2; height: 8px; background: rgba(0,0,0,0.3); border-radius: 4px; margin: 0 12px; overflow: hidden; }
        .module-bar-fill { height: 100%; background: linear-gradient(90deg, var(--accent), var(--primary)); border-radius: 4px; transition: width 0.6s ease; }
        .module-count { font-weight: 600; color: var(--accent); min-width: 45px; text-align: right; font-size: 0.9rem; }

        /* Wallet Section */
        .wallet-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
        .wallet-stat {
            background: linear-gradient(135deg, var(--bitcoin), #E88A00);
            color: white;
            padding: 16px;
            border-radius: 10px;
            text-align: center;
        }
        .wallet-stat .value { font-size: 1.4rem; font-weight: 700; }
        .wallet-stat .label { font-size: 0.72rem; opacity: 0.9; margin-top: 4px; }
        @media (max-width: 768px) { .wallet-grid { grid-template-columns: repeat(2, 1fr); } }

        /* Top List */
        .top-list { list-style: none; padding: 0; margin: 0; }
        .top-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .top-item:last-child { border-bottom: none; }
        .top-rank {
            width: 28px;
            height: 28px;
            background: var(--accent);
            color: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .top-rank.gold { background: #FFD700; }
        .top-rank.silver { background: #C0C0C0; }
        .top-rank.bronze { background: #CD7F32; color: #fff; }
        .top-name { flex: 1; font-weight: 500; color: var(--text); }
        .top-value { font-weight: 600; color: var(--bitcoin); }

        /* Foxy Section */
        .foxy-header {
            background: linear-gradient(135deg, var(--orange), #d45a1a);
            color: white;
            padding: 16px 20px;
            border-radius: 12px 12px 0 0;
            margin: -20px -20px 16px -20px;
        }
        .foxy-header h3 { margin: 0; display: flex; align-items: center; gap: 8px; font-size: 1rem; }
        .foxy-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .foxy-stat {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
            padding: 14px;
            border-radius: 10px;
            text-align: center;
        }
        .foxy-stat .value { font-size: 1.4rem; font-weight: 700; color: var(--orange); }
        .foxy-stat .label { font-size: 0.78rem; color: var(--text-muted); }
        .foxy-categories { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 14px; }
        .foxy-cat-tag {
            background: rgba(232, 111, 44, 0.15);
            border: 1px solid rgba(232, 111, 44, 0.3);
            color: var(--text);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.78rem;
        }

        /* Transaction List */
        .tx-list { max-height: 280px; overflow-y: auto; }
        .tx-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.85rem;
            color: var(--text);
        }
        .tx-item:last-child { border-bottom: none; }
        .tx-desc { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-right: 12px; }
        .tx-amount { font-weight: 600; white-space: nowrap; }
        .tx-amount.positive { color: #6cff6c; }
        .tx-amount.negative { color: #ff6b6b; }

        /* Bot Section */
        .bot-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .bot-stat {
            background: var(--primary);
            border: 1px solid var(--border);
            color: white;
            padding: 16px;
            border-radius: 10px;
            text-align: center;
        }
        .bot-stat .value { font-size: 1.4rem; font-weight: 700; }
        .bot-stat .label { font-size: 0.72rem; opacity: 0.8; margin-top: 4px; }
        .bot-stat.success { background: rgba(40, 167, 69, 0.3); }
        .bot-stat.error { background: rgba(220, 53, 69, 0.3); }
        @media (max-width: 768px) { .bot-stats { grid-template-columns: repeat(2, 1fr); } }

        /* Sub-heading */
        .sub-heading { margin: 0 0 12px 0; color: var(--accent); font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 6px; }

        footer {
            text-align: center;
            padding: 16px;
            color: var(--text-muted);
            font-size: 0.8rem;
            border-top: 1px solid var(--border);
            margin-top: 10px;
        }
        footer a { color: var(--accent); text-decoration: none; }
    </style>
</head>
<body>
    <header class="header">
        <h1>Statistik Dashboard <span class="version-tag">v<?= SGIT_VERSION ?></span></h1>
        <nav class="header-nav">
            <a href="adaptive_learning.php">Lernen</a>
            <a href="admin_v4.php">Admin</a>
            <a href="clippy/test.php">Foxy</a>
        </nav>
    </header>

    <div class="container">
        <!-- Overview Stats -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="icon">📚</div>
                <div class="value"><?= number_format($stats['questions']['total']) ?></div>
                <div class="label">Fragen gesamt</div>
                <div class="sub"><?= count($stats['modules']) ?> Module</div>
            </div>
            <div class="stat-card orange">
                <div class="icon">🤖</div>
                <div class="value"><?= number_format($stats['questions']['ai']) ?></div>
                <div class="label">AI direkt in DB</div>
            </div>
            <div class="stat-card blue">
                <div class="icon">📄</div>
                <div class="value"><?= number_format($stats['questions']['csv']) ?></div>
                <div class="label">AI via CSV</div>
            </div>
            <div class="stat-card">
                <div class="icon">💡</div>
                <div class="value"><?= number_format($stats['questions']['with_explanation']) ?></div>
                <div class="label">Mit Erklaerung</div>
                <div class="sub"><?= $explPct ?>% Coverage</div>
            </div>
            <div class="stat-card bitcoin">
                <div class="icon">₿</div>
                <div class="value"><?= $stats['wallet'] ? number_format($stats['wallet']['total_sats']) : 0 ?></div>
                <div class="label">Sats verdient</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid-2">
            <div class="section">
                <div class="section-header">
                    <span class="section-title">Fragen pro Modul</span>
                    <span class="section-badge"><?= count($stats['modules']) ?> Module</span>
                </div>
                <div class="chart-container">
                    <canvas id="modulesChart"></canvas>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <span class="section-title">Schwierigkeitsgrade</span>
                </div>
                <div class="chart-container">
                    <canvas id="difficultyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Module List + Ages -->
        <div class="grid-2">
            <div class="section">
                <div class="section-header">
                    <span class="section-title">Modul-Uebersicht</span>
                </div>
                <div class="module-list">
                    <?php
                    $maxCount = max($stats['modules'] ?: [1]);
                    foreach ($stats['modules'] as $module => $count):
                        $pct = ($count / $maxCount) * 100;
                        $icon = $moduleIcons[strtolower($module)] ?? '📖';
                    ?>
                    <div class="module-item">
                        <span class="module-icon"><?= $icon ?></span>
                        <span class="module-name"><?= ucfirst(str_replace('_', ' ', $module)) ?></span>
                        <div class="module-bar"><div class="module-bar-fill" style="width:<?= $pct ?>%"></div></div>
                        <span class="module-count"><?= number_format($count) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <span class="section-title">Altersgruppen (Mindestalter)</span>
                </div>
                <div class="chart-container">
                    <canvas id="ageChart"></canvas>
                </div>
            </div>
        </div>

        <?php if ($stats['wallet']): ?>
        <!-- Wallet Section -->
        <div class="section">
            <div class="section-header">
                <span class="section-title">₿ Wallet & Sats</span>
            </div>

            <div class="wallet-grid">
                <div class="wallet-stat">
                    <div class="value"><?= $stats['wallet']['children'] ?></div>
                    <div class="label">Kinder</div>
                </div>
                <div class="wallet-stat">
                    <div class="value"><?= number_format($stats['wallet']['total_sats']) ?></div>
                    <div class="label">Sats verdient</div>
                </div>
                <div class="wallet-stat">
                    <div class="value"><?= number_format($stats['wallet']['transactions']) ?></div>
                    <div class="label">Transaktionen</div>
                </div>
                <div class="wallet-stat">
                    <div class="value"><?= $stats['wallet']['achievements'] ?></div>
                    <div class="label">Achievements</div>
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <h4 class="sub-heading">Top Lerner</h4>
                    <ul class="top-list">
                        <?php
                        $ranks = ['gold', 'silver', 'bronze', '', ''];
                        foreach ($stats['wallet']['top_kids'] as $i => $kid):
                        ?>
                        <li class="top-item">
                            <span class="top-rank <?= $ranks[$i] ?? '' ?>"><?= $i + 1 ?></span>
                            <span class="top-name"><?= htmlspecialchars($kid['name']) ?></span>
                            <span class="top-value"><?= number_format($kid['balance']) ?> ₿</span>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($stats['wallet']['top_kids'])): ?>
                        <li style="padding:20px;text-align:center;color:var(--text-muted);">Keine Daten</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div>
                    <h4 class="sub-heading">Letzte Transaktionen</h4>
                    <div class="tx-list">
                        <?php foreach ($stats['wallet']['recent_tx'] as $tx): ?>
                        <div class="tx-item">
                            <span class="tx-desc"><?= htmlspecialchars(substr($tx['description'] ?? $tx['type'], 0, 40)) ?></span>
                            <span class="tx-amount <?= $tx['amount'] >= 0 ? 'positive' : 'negative' ?>">
                                <?= $tx['amount'] >= 0 ? '+' : '' ?><?= number_format($tx['amount']) ?> sats
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($stats['wallet']['recent_tx'])): ?>
                        <div style="padding:20px;text-align:center;color:var(--text-muted);">Keine Transaktionen</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Foxy + Bots Row -->
        <div class="grid-2">
            <!-- Foxy -->
            <div class="section">
                <div class="foxy-header">
                    <h3>Foxy Lernassistent</h3>
                </div>
                <?php if ($stats['foxy']): ?>
                <div class="foxy-stats">
                    <div class="foxy-stat">
                        <div class="value"><?= number_format($stats['foxy']['responses']) ?></div>
                        <div class="label">Antworten in DB</div>
                    </div>
                    <div class="foxy-stat">
                        <div class="value"><?= number_format($stats['foxy']['chats']) ?></div>
                        <div class="label">Chat-Historie</div>
                    </div>
                </div>
                <?php if (!empty($stats['foxy']['categories'])): ?>
                <div class="foxy-categories">
                    <?php foreach ($stats['foxy']['categories'] as $cat => $cnt): ?>
                    <span class="foxy-cat-tag"><?= htmlspecialchars($cat) ?>: <?= $cnt ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div style="text-align:center;padding:30px;color:var(--text-muted);">
                    <p>Foxy-DB nicht gefunden</p>
                    <a href="clippy/seed_responses.php" style="color:var(--orange);margin-top:10px;display:inline-block;">DB erstellen</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Bots -->
            <div class="section">
                <div class="section-header">
                    <span class="section-title">Bot-Ergebnisse</span>
                    <?php if ($stats['bots'] && $stats['bots']['tests'] > 0): ?>
                    <span class="section-badge"><?= $botPassRate ?>% Pass-Rate</span>
                    <?php endif; ?>
                </div>
                <?php if ($stats['bots']): ?>
                <div class="bot-stats">
                    <div class="bot-stat">
                        <div class="value"><?= $stats['bots']['runs'] ?></div>
                        <div class="label">Durchlaeufe</div>
                    </div>
                    <div class="bot-stat">
                        <div class="value"><?= number_format($stats['bots']['tests']) ?></div>
                        <div class="label">Tests gesamt</div>
                    </div>
                    <div class="bot-stat success">
                        <div class="value"><?= number_format($stats['bots']['passed']) ?></div>
                        <div class="label">Bestanden</div>
                    </div>
                    <div class="bot-stat error">
                        <div class="value"><?= number_format($stats['bots']['errors']) ?></div>
                        <div class="label">Fehler</div>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align:center;padding:30px;color:var(--text-muted);">
                    <p>Noch keine Bot-Durchlaeufe</p>
                    <a href="bots/bot_summary.php" style="color:var(--accent);">Bots starten</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        sgiT Education v<?= SGIT_VERSION ?> | <a href="admin_v4.php">Admin</a> | <a href="https://sgit.space">sgit.space</a>
    </footer>

    <script>
        // Chart.js defaults fuer Dark Theme
        Chart.defaults.color = '#a0a0a0';
        Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';

        const accentColors = [
            '#43D240', '#E86F2C', '#3498db', '#F7931A', '#9b59b6',
            '#2ecc71', '#e74c3c', '#f39c12', '#1abc9c', '#e67e22',
            '#8e44ad', '#2980b9', '#d35400', '#27ae60', '#c0392b',
            '#16a085', '#f1c40f', '#7f8c8d', '#2c3e50'
        ];

        // Modules Chart
        new Chart(document.getElementById('modulesChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(fn($m) => ucfirst(str_replace('_', ' ', $m)), array_keys($stats['modules']))) ?>,
                datasets: [{
                    label: 'Fragen',
                    data: <?= json_encode(array_values($stats['modules'])) ?>,
                    backgroundColor: accentColors,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { maxRotation: 45, font: { size: 10 } } },
                    y: { beginAtZero: true }
                }
            }
        });

        // Difficulty Chart
        new Chart(document.getElementById('difficultyChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map(fn($d) => 'Level ' . $d, array_keys($stats['difficulty']))) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($stats['difficulty'])) ?>,
                    backgroundColor: ['#43D240', '#7FD97D', '#f39c12', '#E86F2C', '#e74c3c', '#9b59b6', '#3498db', '#8e44ad']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '50%',
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, padding: 10, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                return ctx.label + ': ' + ctx.parsed.toLocaleString() + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Age Chart
        new Chart(document.getElementById('ageChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($stats['ages'])) ?>,
                datasets: [{
                    label: 'Fragen',
                    data: <?= json_encode(array_values($stats['ages'])) ?>,
                    backgroundColor: '#1E3A5F',
                    borderColor: '#2980b9',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = ((ctx.parsed.x / total) * 100).toFixed(1);
                                return ctx.parsed.x.toLocaleString() + ' Fragen (' + pct + '%)';
                            }
                        }
                    }
                },
                scales: { x: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>
