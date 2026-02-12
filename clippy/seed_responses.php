<?php
/**
 * ============================================================================
 * sgiT Education - Foxy DB Setup + Seeder v2.0
 * ============================================================================
 * 
 * Erstellt die Foxy-Datenbank und fügt Antworten hinzu
 * 
 * @version 2.0
 * @date 04.12.2025
 * ============================================================================
 */

// DB im clippy-Ordner, nicht in /database/
$dbPath = __DIR__ . '/foxy_chat.db';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Foxy Seeder</title>";
echo "<link rel='stylesheet' href='/assets/css/fonts.css'>";
echo "<style>body{font-family:'Space Grotesk',system-ui,sans-serif;background:#1a1a1a;color:#fff;padding:30px;max-width:900px;margin:0 auto;}";
echo "h1{color:#E86F2C;}pre{background:#2a2a2a;padding:20px;border-radius:10px;overflow:auto;max-height:500px;}";
echo ".success{color:#43D240;}.skip{color:#888;}.error{color:#F44336;}";
echo "a{color:#E86F2C;text-decoration:none;padding:10px 20px;background:#333;border-radius:8px;display:inline-block;margin-top:20px;}";
echo "</style></head><body>";

echo "<h1>🦊 Foxy Seeder v2.0</h1>";
echo "<p>DB-Pfad: <code>{$dbPath}</code></p>";

try {
    // DB erstellen/öffnen
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tabellen erstellen falls nicht vorhanden
    $db->exec("CREATE TABLE IF NOT EXISTS foxy_responses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category TEXT NOT NULL,
        trigger_words TEXT,
        response TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS foxy_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        user_message TEXT,
        foxy_response TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "<p class='success'>✅ Tabellen erstellt/geprüft</p>";
    
    echo "<pre>";
    
    // Zähle bestehende Antworten
    $countBefore = $db->query("SELECT COUNT(*) FROM foxy_responses")->fetchColumn();
    echo "Antworten vorher: {$countBefore}\n\n";
    
    // Antworten
    $responses = [
        // WITZE
        ['joke', 'witz,lustig,lachen,spaß', 'Warum ging der Fuchs zur Schule? Um ein schlauer Fuchs zu werden! 🦊📚'],
        ['joke', 'witz,lustig,lachen,spaß', 'Was sagt ein Fuchs wenn er stolpert? Ach du meine Pfote! 🦊😄'],
        ['joke', 'witz,lustig,lachen,spaß', 'Warum mögen Füchse keine Mathe-Tests? Weil sie lieber Hühner zählen! 🐔🦊'],
        ['joke', 'witz,lustig,lachen,spaß', 'Was ist das Lieblingsfach von Füchsen? Schleich-Kunde! 🦊😂'],
        ['joke', 'witz,lustig,lachen,spaß', 'Wie nennt man einen Fuchs mit Brille? Einen Durchblicker! 👓🦊'],
        ['joke', 'witz,lustig,lachen,spaß', 'Was trinkt ein Fuchs am liebsten? Fuchstee! 🍵🦊'],
        ['joke', 'witz,lustig,lachen,spaß', 'Warum sind Füchse so gute Freunde? Weil sie immer ein offenes Ohr haben! 👂🦊'],
        ['joke', 'witz,lustig,lachen,spaß', 'Wie grüßt ein Fuchs auf Englisch? What does the fox say! 🦊🎵'],
        ['joke', 'witz,lustig,lachen,spaß', 'Was ist orange und versteckt sich im Wald? Ein Ninja-Fuchs! 🥷🦊'],
        
        // AUFMUNTERUNG
        ['cheer', 'aufmunter,traurig,schaff,schwer,müde,fehler', 'Jeder Fehler ist ein Schritt zum Erfolg! Du lernst dabei! 🌟🦊'],
        ['cheer', 'aufmunter,traurig,schaff,schwer,müde,fehler', 'Auch der cleverste Fuchs hat mal klein angefangen! 🦊💪'],
        ['cheer', 'aufmunter,traurig,schaff,schwer,müde,fehler', 'Du bist mutiger als du glaubst! Weiter so! 🦁🦊'],
        ['cheer', 'aufmunter,traurig,schaff,schwer,müde,fehler', 'Nach dem Regen kommt Sonnenschein! ☀️🦊'],
        ['cheer', 'aufmunter,traurig,schaff,schwer,müde,fehler', 'Ich bin stolz auf dich! Du gibst nicht auf! 🏆🦊'],
        ['cheer', 'aufmunter,traurig,schaff,schwer,müde,fehler', 'Dein Gehirn wächst mit jeder Herausforderung! 🧠✨'],
        
        // MODUL-TIPPS
        ['tip_mathe', 'mathe,rechnen,zahlen,plus,minus', '💡 Mathe-Tipp: Bei schweren Aufgaben erst die einfachen Schritte! 🧮🦊'],
        ['tip_erdkunde', 'erdkunde,länder,hauptstadt,kontinent', '💡 Erdkunde-Tipp: Schau dir eine Weltkarte an! 🌍🦊'],
        ['tip_englisch', 'englisch,english,vokabel', '💡 Englisch-Tipp: Lerne jeden Tag 5 neue Wörter! 📚🦊'],
        ['tip_bitcoin', 'bitcoin,btc,sats,satoshi,geld', '💡 Bitcoin-Tipp: 1 Bitcoin = 100 Millionen Satoshis! ₿🦊'],
        ['tip_physik', 'physik,kraft,energie', '💡 Physik-Tipp: Physik ist überall - beobachte die Welt! 🔬🦊'],
        ['tip_chemie', 'chemie,element,atom', '💡 Chemie-Tipp: H2O ist Wasser - Chemie ist im Alltag! 💧🦊'],
        ['tip_programmieren', 'programmieren,code,coding', '💡 Coding-Tipp: Fehler sind normal - debuggen gehört dazu! 🐛🦊'],
        
        // MOTIVATION
        ['motivate', 'lernen,anfangen,start,loslegen', 'Los gehts! 🦊 Wähl ein Fach und zeig was du kannst! 🚀'],
        ['motivate', 'lernen,anfangen,start,loslegen', 'Jede Lernminute macht dich schlauer! Fang an! 🧠🦊'],
        ['motivate', 'lernen,anfangen,start,loslegen', 'Die beste Zeit zum Lernen ist JETZT! ⏰🦊'],
        
        // TIPPS
        ['tip', 'tipp,hilfe,wie,was', '💡 Tipp: Je mehr richtige Antworten, desto mehr Sats! 🦊₿'],
        ['tip', 'tipp,hilfe,wie,was', '💡 Tipp: Regelmäßig kurz lernen ist besser als selten lang! ⏱️🦊'],
        ['tip', 'tipp,hilfe,wie,was', '💡 Tipp: Achievements bringen Extra-Sats! Sammle sie alle! 🏆'],
        
        // BEGRÜSSUNGEN
        ['greeting', 'hallo,hi,hey,moin,servus,guten', 'Hey! 🦊 Schön dich zu sehen! Was kann ich für dich tun?'],
        ['greeting', 'hallo,hi,hey,moin,servus,guten', 'Hallo! 👋 Foxy hier! Bereit zum Helfen! 🦊'],
        ['greeting', 'hallo,hi,hey,moin,servus,guten', 'Moin! 🦊 Lass uns gemeinsam lernen! 📚'],
        
        // VERABSCHIEDUNG
        ['bye', 'tschüss,bye,ciao,bis bald', 'Bis bald! 🦊👋 Lern fleißig weiter!'],
        ['bye', 'tschüss,bye,ciao,bis bald', 'Tschüss! 🌟 Du machst das super! 🦊'],
        
        // DANK
        ['thanks', 'danke,super,toll,cool,klasse', 'Gern geschehen! 🦊 Du bist der/die Beste! 🌟'],
        ['thanks', 'danke,super,toll,cool,klasse', 'Immer wieder gerne! 🦊 Weiter so! 💪'],
        
        // ÜBER FOXY
        ['about', 'wer bist du,was bist du,foxy,name', 'Ich bin Foxy! 🦊 Dein Lern-Maskottchen! Ich helfe dir beim Lernen! 💪'],
    ];
    
    $stmt = $db->prepare("INSERT INTO foxy_responses (category, trigger_words, response) VALUES (?, ?, ?)");
    $added = 0;
    
    foreach ($responses as $row) {
        $check = $db->prepare("SELECT COUNT(*) FROM foxy_responses WHERE response = ?");
        $check->execute([$row[2]]);
        
        if ($check->fetchColumn() == 0) {
            $stmt->execute($row);
            $added++;
            echo "<span class='success'>✅ " . substr($row[2], 0, 60) . "...</span>\n";
        } else {
            echo "<span class='skip'>⏭️ (existiert) " . substr($row[2], 0, 40) . "...</span>\n";
        }
    }
    
    $countAfter = $db->query("SELECT COUNT(*) FROM foxy_responses")->fetchColumn();
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "<span class='success'>📊 ERGEBNIS:\n";
    echo "   Vorher: {$countBefore} Antworten\n";
    echo "   Hinzugefügt: {$added} Antworten\n";
    echo "   Nachher: {$countAfter} Antworten</span>\n";
    
    echo "\n📁 KATEGORIEN:\n";
    $cats = $db->query("SELECT category, COUNT(*) as cnt FROM foxy_responses GROUP BY category ORDER BY cnt DESC");
    foreach ($cats as $cat) {
        echo "   {$cat['category']}: {$cat['cnt']}\n";
    }
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
}

echo "<a href='test.php'>← Zurück zum Test</a>";
echo "<a href='../admin_v4.php' style='margin-left:10px;'>← Admin Dashboard</a>";
echo "</body></html>";
