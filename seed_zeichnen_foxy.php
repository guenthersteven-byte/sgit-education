<?php
/**
 * ============================================================================
 * sgiT Education - Foxy Zeichnen-Modul Seeder
 * ============================================================================
 * 
 * Fügt Foxy-Antworten für das Zeichnen-Modul hinzu
 * 
 * @version 1.0
 * @date 07.12.2025
 * ============================================================================
 */

$dbPath = __DIR__ . '/clippy/foxy_chat.db';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Foxy Zeichnen Seeder</title>";
echo "<style>body{font-family:'Segoe UI',sans-serif;background:#1a1a1a;color:#fff;padding:30px;max-width:900px;margin:0 auto;}";
echo "h1{color:#E86F2C;}pre{background:#2a2a2a;padding:20px;border-radius:10px;overflow:auto;max-height:600px;}";
echo ".success{color:#43D240;}.skip{color:#888;}.error{color:#F44336;}.info{color:#4FC3F7;}";
echo "a{color:#E86F2C;text-decoration:none;padding:10px 20px;background:#333;border-radius:8px;display:inline-block;margin-top:20px;}";
echo "</style></head><body>";

echo "<h1>🎨 Foxy Zeichnen-Modul Seeder</h1>";

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<pre>";
    $countBefore = $db->query("SELECT COUNT(*) FROM foxy_responses")->fetchColumn();
    echo "<span class='info'>Antworten vorher: {$countBefore}</span>\n\n";
    
    // =====================================================
    // ZEICHNEN-MODUL ANTWORTEN
    // =====================================================
    $responses = [
        // ALLGEMEINE ZEICHNEN-TIPPS
        ['tip_zeichnen', 'zeichnen,malen,zeichnung,bild,kunst,pinsel', '🎨 Zeichnen-Tipp: Fang mit einfachen Formen an - Kreise, Quadrate, Dreiecke! 🦊'],
        ['tip_zeichnen', 'zeichnen,malen,zeichnung,bild,kunst,pinsel', '🖌️ Tipp: Halte den Stift locker! Entspannte Hand = bessere Linien! 🦊'],
        ['tip_zeichnen', 'zeichnen,malen,zeichnung,bild,kunst,pinsel', '✏️ Übung macht den Meister! Jeden Tag 10 Minuten zeichnen hilft enorm! 🦊'],
        ['tip_zeichnen', 'zeichnen,malen,zeichnung,bild,kunst,pinsel', '🎨 Tipp: Schau dir Dinge genau an bevor du sie zeichnest! Beobachten ist wichtig! 🦊'],
        ['tip_zeichnen', 'zeichnen,malen,zeichnung,bild,kunst,pinsel', '💡 Wusstest du? Jeder kann zeichnen lernen - es braucht nur Übung! 🦊🎨'],
        
        // FARBEN
        ['tip_farben', 'farbe,farben,bunt,rot,blau,gelb,grün,mischen', '🌈 Farben-Tipp: Rot + Gelb = Orange! Blau + Gelb = Grün! 🎨🦊'],
        ['tip_farben', 'farbe,farben,bunt,rot,blau,gelb,grün,mischen', '🎨 Die drei Grundfarben sind Rot, Gelb und Blau - daraus kannst du alle anderen mischen! 🦊'],
        ['tip_farben', 'farbe,farben,bunt,rot,blau,gelb,grün,mischen', '💜 Rot + Blau = Lila! Probier es aus im Zeichnen-Modul! 🦊'],
        ['tip_farben', 'farbe,farben,bunt,rot,blau,gelb,grün,mischen', '🖌️ Warme Farben (Rot, Orange, Gelb) wirken nah - Kalte (Blau, Grün) wirken weit weg! 🦊'],
        
        // FORMEN
        ['tip_formen', 'kreis,quadrat,dreieck,form,formen,rund,eckig', '⭕ Wusstest du? Fast alles besteht aus Grundformen! Ein Gesicht? Kreis + Dreiecke + Ovale! 🦊'],
        ['tip_formen', 'kreis,quadrat,dreieck,form,formen,rund,eckig', '🔷 Tipp für Quadrate: Zeichne erst eine Linie, dann rechtwinklig die nächste! 📐🦊'],
        ['tip_formen', 'kreis,quadrat,dreieck,form,formen,rund,eckig', '⭐ Ein Stern besteht aus 5 Dreiecken! Probier mal das Stern-Tutorial! 🦊'],
        ['tip_formen', 'kreis,quadrat,dreieck,form,formen,rund,eckig', '🏠 Ein Haus? Quadrat + Dreieck oben drauf! So einfach kann es sein! 🦊'],
        
        // TUTORIALS
        ['tutorial_info', 'tutorial,anleitung,lernen,üben,schritt', '📚 Im Zeichnen-Modul gibt es Tutorials für jedes Alter! Von Kreis bis Porträt! 🦊'],
        ['tutorial_info', 'tutorial,anleitung,lernen,üben,schritt', '🎯 Tutorials geben dir Schritt-für-Schritt Anleitungen - perfekt zum Lernen! 🦊'],
        ['tutorial_info', 'tutorial,anleitung,lernen,üben,schritt', '⭐ Für jedes fertige Tutorial bekommst du Sats! Je schwerer, desto mehr! 🦊₿'],
        
        // MOTIVATION ZEICHNEN
        ['motivate_zeichnen', 'kann nicht,schwer,schlecht,hässlich,geht nicht', '🦊 Hey, jeder fängt mal an! Dein erstes Bild muss nicht perfekt sein! 💪'],
        ['motivate_zeichnen', 'kann nicht,schwer,schlecht,hässlich,geht nicht', '✨ Auch Picasso hat mit Strichmännchen angefangen! Weitermachen! 🦊'],
        ['motivate_zeichnen', 'kann nicht,schwer,schlecht,hässlich,geht nicht', '🎨 Fehler sind keine Fehler - sie sind Übung! Jeder Strich macht dich besser! 🦊'],
        ['motivate_zeichnen', 'kann nicht,schwer,schlecht,hässlich,geht nicht', '💡 Vergleich dich nicht mit anderen! Vergleich dich mit dir von gestern! 🦊🌟'],
        
        // WERKZEUGE
        ['tool_info', 'stift,pinsel,radierer,werkzeug,tool', '✏️ Der Stift ist perfekt für feine Linien und Details! 🦊'],
        ['tool_info', 'stift,pinsel,radierer,werkzeug,tool', '🖌️ Der Pinsel macht dickere, weichere Striche - toll zum Ausmalen! 🦊'],
        ['tool_info', 'stift,pinsel,radierer,werkzeug,tool', '🧽 Der Radierer ist dein Freund! Keine Angst vor Fehlern! 🦊'],
        ['tool_info', 'stift,pinsel,radierer,werkzeug,tool', '⭕ Mit dem Kreis-Tool kannst du perfekte Kreise zeichnen! 🦊'],
        
        // GALERIE
        ['gallery_info', 'galerie,speichern,bild,bilder,sammlung', '🖼️ In deiner Galerie werden alle deine Kunstwerke gespeichert! 🦊'],
        ['gallery_info', 'galerie,speichern,bild,bilder,sammlung', '💾 Drück Strg+S oder den Speichern-Button um dein Bild zu sichern! 🦊'],
        ['gallery_info', 'galerie,speichern,bild,bilder,sammlung', '🎨 Je mehr du zeichnest, desto voller wird deine Galerie! Sammle sie alle! 🦊'],
        
        // SATS & BELOHNUNGEN
        ['sats_zeichnen', 'sats,satoshi,verdienen,belohnung,punkte', '₿ Freies Zeichnen: 5 Sats | Tutorials: 5-75 Sats je nach Schwierigkeit! 🦊'],
        ['sats_zeichnen', 'sats,satoshi,verdienen,belohnung,punkte', '🎯 Tipp: Schließe Tutorials ab für mehr Sats als beim freien Zeichnen! 🦊₿'],
        ['sats_zeichnen', 'sats,satoshi,verdienen,belohnung,punkte', '⭐ Für kleine Künstler (5-7 Jahre) gibt es +2 Bonus-Sats! 🦊'],
        
        // TECHNIKEN
        ['technik', 'technik,schatten,schattieren,licht,3d', '💡 Schattierung macht deine Bilder 3D! Licht kommt von einer Seite! 🦊'],
        ['technik', 'technik,schatten,schattieren,licht,3d', '🎨 Für Schatten: Drück leichter oder nimm eine dunklere Farbe! 🦊'],
        ['technik', 'technik,symmetrie,spiegeln,gleich', '🦋 Symmetrie-Tipp: Zeichne eine Mittellinie und mach beide Seiten gleich! 🦊'],
        ['technik', 'technik,perspektive,tiefe,weit,nah', '📐 Dinge weiter weg sind kleiner! Das nennt man Perspektive! 🦊'],
        
        // TIERE ZEICHNEN
        ['tiere', 'tier,tiere,katze,hund,vogel,fuchs', '🐱 Katzen-Tipp: Kopf = Kreis, Ohren = Dreiecke, fertig ist die Grundform! 🦊'],
        ['tiere', 'tier,tiere,katze,hund,vogel,fuchs', '🦊 Willst du einen Fuchs zeichnen? Orange + spitze Ohren + buschiger Schwanz! 🎨'],
        ['tiere', 'tier,tiere,katze,hund,vogel,fuchs', '🐕 Hunde haben runde Schnauzen, Katzen spitze - achte auf die Details! 🦊'],
    ];
    
    $stmt = $db->prepare("INSERT INTO foxy_responses (category, trigger_words, response) VALUES (?, ?, ?)");
    $added = 0;
    
    foreach ($responses as $row) {
        $check = $db->prepare("SELECT COUNT(*) FROM foxy_responses WHERE response = ?");
        $check->execute([$row[2]]);
        
        if ($check->fetchColumn() == 0) {
            $stmt->execute($row);
            $added++;
            echo "<span class='success'>✅ [{$row[0]}] " . substr($row[2], 0, 60) . "...</span>\n";
        } else {
            echo "<span class='skip'>⏭️ (existiert) " . substr($row[2], 0, 50) . "...</span>\n";
        }
    }
    
    $countAfter = $db->query("SELECT COUNT(*) FROM foxy_responses")->fetchColumn();
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "<span class='success'>📊 ERGEBNIS:\n";
    echo "   Vorher: {$countBefore} Antworten\n";
    echo "   Hinzugefügt: {$added} Zeichnen-Antworten\n";
    echo "   Nachher: {$countAfter} Antworten</span>\n";
    
    // Kategorien anzeigen
    echo "\n📁 NEUE ZEICHNEN-KATEGORIEN:\n";
    $cats = $db->query("SELECT category, COUNT(*) as cnt FROM foxy_responses WHERE category LIKE '%zeichnen%' OR category LIKE '%farben%' OR category LIKE '%formen%' OR category LIKE '%tutorial%' OR category LIKE '%tool%' OR category LIKE '%gallery%' OR category LIKE '%technik%' OR category LIKE '%tiere%' OR category LIKE '%sats_zeichnen%' OR category LIKE '%motivate_zeichnen%' GROUP BY category ORDER BY cnt DESC");
    foreach ($cats as $cat) {
        echo "   🎨 {$cat['category']}: {$cat['cnt']} Antworten\n";
    }
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
}

echo "<a href='zeichnen/'>🎨 Zum Zeichnen-Modul</a>";
echo "<a href='admin_v4.php' style='margin-left:10px;'>← Admin Dashboard</a>";
echo "</body></html>";
