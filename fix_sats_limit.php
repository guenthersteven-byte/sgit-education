<?php
/**
 * ============================================================================
 * sgiT Education - Sats-Limit Fix
 * ============================================================================
 * 
 * Einmal-Script um die Sats-Limits auf praktisch unbegrenzt zu setzen.
 * BUG-010: Kinder sollen unbegrenzt lernen können!
 * 
 * NACH AUSFÜHRUNG KANN DIESES SCRIPT GELÖSCHT WERDEN.
 * 
 * @date 03.12.2025
 * ============================================================================
 */

require_once __DIR__ . '/wallet/WalletManager.php';

echo "<h1>🔧 sgiT Sats-Limit Fix</h1>";
echo "<pre>";

try {
    $wallet = new WalletManager();
    
    // Alte Werte anzeigen
    echo "📊 ALTE WERTE:\n";
    echo "- daily_earn_limit:  " . $wallet->getConfig('daily_earn_limit', 'nicht gesetzt') . " Sats\n";
    echo "- weekly_earn_limit: " . $wallet->getConfig('weekly_earn_limit', 'nicht gesetzt') . " Sats\n\n";
    
    // Neue Werte setzen (praktisch unbegrenzt)
    $newDailyLimit = 999999;   // ~1 Million Sats/Tag
    $newWeeklyLimit = 9999999; // ~10 Millionen Sats/Woche
    
    $wallet->setConfig('daily_earn_limit', $newDailyLimit);
    $wallet->setConfig('weekly_earn_limit', $newWeeklyLimit);
    
    echo "✅ NEUE WERTE GESETZT:\n";
    echo "- daily_earn_limit:  " . number_format($newDailyLimit) . " Sats\n";
    echo "- weekly_earn_limit: " . number_format($newWeeklyLimit) . " Sats\n\n";
    
    echo "🎉 BUG-010 GELÖST!\n";
    echo "Kinder können jetzt unbegrenzt Sats verdienen!\n\n";
    
    echo "💡 TIPP: Dieses Script kann jetzt gelöscht werden.\n";
    echo "         Alternativ: Limits über wallet_admin.php konfigurieren.\n";
    
} catch (Exception $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='wallet/wallet_admin.php'>← Zurück zum Wallet Admin</a></p>";
?>
