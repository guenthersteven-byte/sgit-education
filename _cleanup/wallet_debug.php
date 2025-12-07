<?php
/**
 * Quick Wallet DB Analysis
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== WALLET DB ANALYSE ===\n\n";

$walletDb = 'C:\xampp\htdocs\Education\wallet\wallet.db';

if (!file_exists($walletDb)) {
    die("❌ wallet.db nicht gefunden!");
}

$db = new PDO('sqlite:' . $walletDb);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Tabellen
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
echo "TABELLEN: " . implode(", ", $tables) . "\n\n";

// sat_transactions Schema
echo "=== sat_transactions SCHEMA ===\n";
$schema = $db->query("PRAGMA table_info(sat_transactions)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($schema as $col) {
    echo "  {$col['name']} ({$col['type']})\n";
}

// sat_transactions Daten
echo "\n=== sat_transactions DATEN ===\n";
$count = $db->query("SELECT COUNT(*) FROM sat_transactions")->fetchColumn();
echo "Anzahl: $count\n\n";

if ($count > 0) {
    // Alle type-Werte
    echo "Alle TYPE-Werte:\n";
    $types = $db->query("SELECT type, COUNT(*) as cnt, SUM(amount_sats) as total FROM sat_transactions GROUP BY type")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($types as $t) {
        echo "  {$t['type']}: {$t['cnt']} Einträge, Summe: {$t['total']} sats\n";
    }
    
    echo "\nBeispiel-Transaktionen (letzte 10):\n";
    $samples = $db->query("SELECT * FROM sat_transactions ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($samples as $s) {
        echo "  ID:{$s['id']} | Type:{$s['type']} | Amount:{$s['amount_sats']} | {$s['reason']}\n";
    }
}

// child_wallets
echo "\n=== child_wallets ===\n";
$kids = $db->query("SELECT id, name, balance FROM child_wallets")->fetchAll(PDO::FETCH_ASSOC);
foreach ($kids as $k) {
    echo "  {$k['name']}: {$k['balance']} sats\n";
}

$totalBalance = $db->query("SELECT SUM(balance) FROM child_wallets")->fetchColumn();
echo "\nSumme aller Balances: $totalBalance sats\n";

// Rewards spezifisch
echo "\n=== REWARD-SUCHE ===\n";
$rewardTypes = ['reward', 'Reward', 'REWARD', 'earn', 'earned', 'question', 'learning', 'correct'];
foreach ($rewardTypes as $rt) {
    $cnt = $db->query("SELECT COUNT(*) FROM sat_transactions WHERE type = '$rt'")->fetchColumn();
    $sum = $db->query("SELECT COALESCE(SUM(amount_sats), 0) FROM sat_transactions WHERE type = '$rt'")->fetchColumn();
    if ($cnt > 0) {
        echo "  '$rt': $cnt Einträge, Summe: $sum sats\n";
    }
}

// LIKE Suche
echo "\nLIKE '%reward%':\n";
$likeReward = $db->query("SELECT COUNT(*), COALESCE(SUM(amount_sats),0) FROM sat_transactions WHERE type LIKE '%reward%'")->fetch(PDO::FETCH_NUM);
echo "  {$likeReward[0]} Einträge, Summe: {$likeReward[1]} sats\n";

echo "\nLIKE '%earn%':\n";
$likeEarn = $db->query("SELECT COUNT(*), COALESCE(SUM(amount_sats),0) FROM sat_transactions WHERE type LIKE '%earn%'")->fetch(PDO::FETCH_NUM);
echo "  {$likeEarn[0]} Einträge, Summe: {$likeEarn[1]} sats\n";

echo "\n=== POSITIVE TRANSAKTIONEN (amount_sats > 0) ===\n";
$positive = $db->query("SELECT COUNT(*), SUM(amount_sats) FROM sat_transactions WHERE amount_sats > 0")->fetch(PDO::FETCH_NUM);
echo "Anzahl: {$positive[0]}, Summe: {$positive[1]} sats\n";

echo "\nDone!";
