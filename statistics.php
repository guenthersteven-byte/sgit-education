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
        
        $stats['questions']['total'] = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
        $stats['questions']['ai'] = $db->query("SELECT COUNT(*) FROM questions WHERE ai_generated = 1")->fetchColumn();
        $stats['questions']['csv'] = $db->query("SELECT COUNT(*) FROM questions WHERE source = 'csv_import'")->fetchColumn() ?: 0;
        $stats['questions']['with_explanation'] = $db->query("SELECT COUNT(*) FROM questions WHERE explanation IS NOT NULL AND explanation != ''")->fetchColumn();
        
        // Module
        $r = $db->query("SELECT module, COUNT(*) as cnt FROM questions GROUP BY module ORDER BY cnt DESC");
        while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
            $stats['modules'][$row['module']] = (int)$row['cnt'];
        }
        
        // Schwierigkeit
        $r = $db->query("SELECT difficulty, COUNT(*) as cnt FROM questions GROUP BY difficulty ORDER BY difficulty");
        while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
            $stats['difficulty'][$row['difficulty']] = (int)$row['cnt'];
        }
        
        // Altersgruppen
        $r = $db->query("SELECT age_min, age_max, COUNT(*) as cnt FROM questions GROUP BY age_min, age_max ORDER BY age_min");
        while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
            $label = $row['age_min'] . '-' . $row['age_max'];
            $stats['ages'][$label] = (int)$row['cnt'];
        }
        
    } catch (Exception $e) {
        $stats['questions']['error'] = $e->getMessage();
    }
}

// WALLET DB
$walletDb = __DIR__ . '/wallet/wallet.db';
if (file_exists($walletDb)) {
    try {
        $db = new PDO('sqlite:' . $walletDb);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // BUG-022 FIX: Korrigierter Query für Sats verteilt
        // Zählt alle verdienten Sats (earn, bonus) ODER nutzt total_earned aus child_wallets
        $totalEarned = $db->query("SELECT COALESCE(SUM(total_earned), 0) FROM child_wallets")->fetchColumn();
        
        $stats['wallet'] = [
            'children' => $db->query("SELECT COUNT(*) FROM child_wallets")->fetchColumn() ?: 0,
            'total_sats' => (int)$totalEarned,
            'transactions' => $db->query("SELECT COUNT(*) FROM sat_transactions")->fetchColumn() ?: 0,
            'achievements' => $db->query("SELECT COUNT(*) FROM wallet_achievements")->fetchColumn() ?: 0,
            'top_kids' => $db->query("SELECT child_name as name, balance_sats as balance FROM child_wallets ORDER BY balance_sats DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC),
            'recent_tx' => $db->query("SELECT child_id, amount_sats as amount, type, reason as description, created_at FROM sat_transactions ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC)
        ];
    } catch (Exception $e) {
        // Debug: error_log('Wallet DB Error: ' . $e->getMessage());
    }
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

// Modul-Icons
$moduleIcons = [
    'mathematik' => '🔢', 'physik' => '⚛️', 'chemie' => '🧪', 'biologie' => '🧬',
    'erdkunde' => '🌍', 'geschichte' => '📜', 'kunst' => '🎨', 'musik' => '🎵',
    'computer' => '💻', 'programmieren' => '👨‍💻', 'bitcoin' => '₿', 'steuern' => '💰',
    'englisch' => '🇬🇧', 'lesen' => '📖', 'wissenschaft' => '🔬', 'verkehr' => '🚗'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Statistik - sgiT Education</title>
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Stats-spezifische Overrides */
        .header { background: rgba(0, 0, 0, 0.4); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid var(--border); }
        .header h1 { font-size: 1.4rem; color: #fff; }
        .header-nav { display: flex; gap: 10px; }
        .header-nav a { padding: 10px 18px; background: rgba(255,255,255,0.1); color: white; text-decoration: none; border-radius: 8px; font-size: 0.9rem; }
        .header-nav a:hover { background: rgba(255,255,255,0.2); }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 25px; }
        
        /* Stats Overview */
        .stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px; padding: 25px; text-align: center; border-top: 4px solid var(--accent); }
        .stat-card.orange { border-top-color: var(--orange); }
        .stat-card.blue { border-top-color: #3498db; }
        .stat-card.bitcoin { border-top-color: var(--bitcoin); }
        .stat-card .icon { font-size: 2rem; margin-bottom: 10px; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: var(--accent); }
        .stat-card .label { font-size: 0.85rem; color: var(--text-muted); margin-top: 5px; }
        
        /* Section */
        .section { background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px; padding: 25px; margin-bottom: 25px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border); }
        .section-title { font-size: 1.1rem; font-weight: 600; color: var(--accent); display: flex; align-items: center; gap: 10px; }
        .section-badge { background: var(--accent); color: #000; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; }
        
        /* Grid */
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        @media (max-width: 900px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
        
        /* Chart */
        .chart-container { height: 280px; position: relative; }
        
        /* Module List */
        .module-list { max-height: 350px; overflow-y: auto; }
        .module-item { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .module-item:last-child { border-bottom: none; }
        .module-icon { font-size: 1.3rem; width: 35px; }
        .module-name { flex: 1; font-weight: 500; color: var(--text); }
        .module-bar { flex: 2; height: 10px; background: rgba(0,0,0,0.3); border-radius: 5px; margin: 0 15px; overflow: hidden; }
        .module-bar-fill { height: 100%; background: linear-gradient(90deg, var(--accent), var(--primary)); border-radius: 5px; }
        .module-count { font-weight: 600; color: var(--accent); min-width: 50px; text-align: right; }
        
        /* Wallet Section */
        .wallet-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .wallet-stat { background: linear-gradient(135deg, var(--bitcoin), #E88A00); color: white; padding: 20px; border-radius: 12px; text-align: center; }
        .wallet-stat .value { font-size: 1.5rem; font-weight: 700; }
        .wallet-stat .label { font-size: 0.75rem; opacity: 0.9; margin-top: 5px; }
        @media (max-width: 768px) { .wallet-grid { grid-template-columns: repeat(2, 1fr); } }
        
        /* Top List */
        .top-list { list-style: none; }
        .top-item { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .top-item:last-child { border-bottom: none; }
        .top-rank { width: 30px; height: 30px; background: var(--accent); color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; margin-right: 15px; }
        .top-rank.gold { background: #FFD700; color: #333; }
        .top-rank.silver { background: #C0C0C0; color: #333; }
        .top-rank.bronze { background: #CD7F32; }
        .top-name { flex: 1; font-weight: 500; color: var(--text); }
        .top-value { font-weight: 600; color: var(--bitcoin); }
        
        /* Foxy Section */
        .foxy-header { background: linear-gradient(135deg, var(--orange), #d45a1a); color: white; padding: 20px; border-radius: 12px 12px 0 0; margin: -25px -25px 20px -25px; }
        .foxy-header h3 { display: flex; align-items: center; gap: 10px; }
        .foxy-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .foxy-stat { background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 15px; border-radius: 10px; text-align: center; }
        .foxy-stat .value { font-size: 1.5rem; font-weight: 700; color: var(--orange); }
        .foxy-stat .label { font-size: 0.8rem; color: var(--text-muted); }
        
        /* Transaction List */
        .tx-list { max-height: 300px; overflow-y: auto; }
        .tx-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 0.9rem; color: var(--text); }
        .tx-item:last-child { border-bottom: none; }
        .tx-amount { font-weight: 600; }
        .tx-amount.positive { color: #6cff6c; }
        .tx-amount.negative { color: #ff6b6b; }
        
        /* Bot Section */
        .bot-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .bot-stat { background: var(--primary); border: 1px solid var(--border); color: white; padding: 20px; border-radius: 12px; text-align: center; }
        .bot-stat .value { font-size: 1.5rem; font-weight: 700; }
        .bot-stat .label { font-size: 0.75rem; opacity: 0.8; margin-top: 5px; }
        .bot-stat.success { background: rgba(40, 167, 69, 0.3); }
        .bot-stat.error { background: rgba(220, 53, 69, 0.3); }
        @media (max-width: 768px) { .bot-stats { grid-template-columns: repeat(2, 1fr); } }
        
        footer { text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.85rem; border-top: 1px solid var(--border); margin-top: 20px; }
        footer a { color: var(--accent); text-decoration: none; }
    </style>
</head>
<body>
    <header class="header">
        <h1>📊 Statistik Dashboard</h1>
        <nav class="header-nav">
            <a href="admin_v4.php">🏠 Admin</a>
            <a href="adaptive_learning.php">📚 Lernen</a>
            <a href="clippy/test.php">🦊 Foxy</a>
        </nav>
    </header>
    
    <div class="container">
        <!-- Overview Stats -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="icon">📚</div>
                <div class="value"><?= number_format($stats['questions']['total']) ?></div>
                <div class="label">Fragen gesamt</div>
            </div>
            <div class="stat-card orange">
                <div class="icon">🤖</div>
                <div class="value"><?= number_format($stats['questions']['ai']) ?></div>
                <div class="label">AI → direkt in DB</div>
            </div>
            <div class="stat-card blue">
                <div class="icon">📝</div>
                <div class="value"><?= number_format($stats['questions']['csv']) ?></div>
                <div class="label">AI → via CSV</div>
            </div>
            <div class="stat-card">
                <div class="icon">💡</div>
                <div class="value"><?= number_format($stats['questions']['with_explanation']) ?></div>
                <div class="label">Mit Erklärung</div>
            </div>
            <div class="stat-card bitcoin">
                <div class="icon">₿</div>
                <div class="value"><?= $stats['wallet'] ? number_format($stats['wallet']['total_sats']) : 0 ?></div>
                <div class="label">Sats verteilt</div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="grid-2">
            <div class="section">
                <div class="section-header">
                    <span class="section-title">📊 Fragen pro Modul</span>
                    <span class="section-badge"><?= count($stats['modules']) ?> Module</span>
                </div>
                <div class="chart-container">
                    <canvas id="modulesChart"></canvas>
                </div>
            </div>
            
            <div class="section">
                <div class="section-header">
                    <span class="section-title">🎯 Schwierigkeitsgrade</span>
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
                    <span class="section-title">📋 Modul-Übersicht</span>
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
                        <span class="module-name"><?= ucfirst($module) ?></span>
                        <div class="module-bar"><div class="module-bar-fill" style="width:<?= $pct ?>%"></div></div>
                        <span class="module-count"><?= number_format($count) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="section">
                <div class="section-header">
                    <span class="section-title">👶 Altersgruppen</span>
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
                    <div class="label">Sats gesamt</div>
                </div>
                <div class="wallet-stat">
                    <div class="value"><?= $stats['wallet']['transactions'] ?></div>
                    <div class="label">Transaktionen</div>
                </div>
                <div class="wallet-stat">
                    <div class="value"><?= $stats['wallet']['achievements'] ?></div>
                    <div class="label">Achievements</div>
                </div>
            </div>
            
            <div class="grid-2">
                <div>
                    <h4 style="margin-bottom:15px;color:var(--primary);">🏆 Top Lerner</h4>
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
                        <li style="padding:20px;text-align:center;color:#999;">Keine Daten</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div>
                    <h4 style="margin-bottom:15px;color:var(--primary);">📜 Letzte Transaktionen</h4>
                    <div class="tx-list">
                        <?php foreach ($stats['wallet']['recent_tx'] as $tx): ?>
                        <div class="tx-item">
                            <span><?= htmlspecialchars(substr($tx['description'] ?? $tx['type'], 0, 40)) ?></span>
                            <span class="tx-amount <?= $tx['amount'] >= 0 ? 'positive' : 'negative' ?>">
                                <?= $tx['amount'] >= 0 ? '+' : '' ?><?= number_format($tx['amount']) ?> sats
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($stats['wallet']['recent_tx'])): ?>
                        <div style="padding:20px;text-align:center;color:#999;">Keine Transaktionen</div>
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
                    <h3>🦊 Foxy Lernassistent</h3>
                </div>
                <?php if ($stats['foxy']): ?>
                <div class="foxy-stats">
                    <div class="foxy-stat">
                        <div class="value"><?= $stats['foxy']['responses'] ?></div>
                        <div class="label">Antworten in DB</div>
                    </div>
                    <div class="foxy-stat">
                        <div class="value"><?= $stats['foxy']['chats'] ?></div>
                        <div class="label">Chat-Historie</div>
                    </div>
                </div>
                <?php if (!empty($stats['foxy']['categories'])): ?>
                <h4 style="margin:20px 0 10px;color:var(--orange);">Kategorien</h4>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach ($stats['foxy']['categories'] as $cat => $cnt): ?>
                    <span style="background:#f0f0f0;padding:5px 12px;border-radius:15px;font-size:0.85rem;">
                        <?= htmlspecialchars($cat) ?>: <?= $cnt ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div style="text-align:center;padding:30px;color:#999;">
                    <p>Foxy-DB nicht gefunden</p>
                    <a href="clippy/seed_responses.php" style="color:var(--orange);margin-top:10px;display:inline-block;">→ DB erstellen</a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Bots -->
            <div class="section">
                <div class="section-header">
                    <span class="section-title">🤖 Bot-Ergebnisse</span>
                </div>
                <?php if ($stats['bots']): ?>
                <div class="bot-stats">
                    <div class="bot-stat">
                        <div class="value"><?= $stats['bots']['runs'] ?></div>
                        <div class="label">Durchläufe</div>
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
                <div style="text-align:center;padding:30px;color:#999;">
                    <p>Noch keine Bot-Durchläufe</p>
                    <a href="bots/bot_summary.php" style="color:var(--accent);margin-top:10px;display:inline-block;">→ Bots starten</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer>
        sgiT Education Platform | <a href="admin_v4.php">Admin</a> | <a href="https://sgit.space">sgit.space</a>
    </footer>
    
    <script>
        const colors = ['#43D240', '#E86F2C', '#1E3A5F', '#F7931A', '#9b59b6', '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#1abc9c'];
        
        // Modules Chart
        new Chart(document.getElementById('modulesChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map('ucfirst', array_keys($stats['modules']))) ?>,
                datasets: [{
                    label: 'Fragen',
                    data: <?= json_encode(array_values($stats['modules'])) ?>,
                    backgroundColor: colors,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
        
        // Difficulty Chart
        new Chart(document.getElementById('difficultyChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map(fn($d) => 'Level ' . $d, array_keys($stats['difficulty']))) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($stats['difficulty'])) ?>,
                    backgroundColor: ['#43D240', '#7FD97D', '#B5E8B4', '#E86F2C', '#F7931A', '#1E3A5F', '#2980b9', '#8e44ad', '#c0392b', '#16a085']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } }
            }
        });
        
        // Age Chart
        new Chart(document.getElementById('ageChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(fn($a) => $a . ' Jahre', array_keys($stats['ages']))) ?>,
                datasets: [{
                    label: 'Fragen',
                    data: <?= json_encode(array_values($stats['ages'])) ?>,
                    backgroundColor: '#1E3A5F',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>
