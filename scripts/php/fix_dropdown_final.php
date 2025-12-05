<?php
$file = 'windows_ai_generator.php';
$content = file_get_contents($file);

// Find and replace the dropdown
$old = '                    <option value="physik">⚛️ Physik</option>
                </select>';

$new = '                    <option value="physik">⚛️ Physik</option>
                    <option value="kunst">🎨 Kunst</option>
                    <option value="musik">🎵 Musik</option>
                    <option value="computer">💻 Computer</option>
                    <option value="bitcoin">₿ Bitcoin</option>
                    <option value="geschichte">📚 Geschichte</option>
                    <option value="biologie">🧬 Biologie</option>
                    <option value="steuern">💰 Steuern</option>
                    <option value="programmieren">👨‍💻 Programmieren</option>
                    <option value="verkehr">🚗 Verkehr</option>
                </select>';

$newContent = str_replace($old, $new, $content);

if ($newContent !== $content) {
    file_put_contents($file, $newContent);
    echo "✅ ERFOLG! Dropdown aktualisiert mit allen 16 Modulen!\n\n";
    echo "<a href='windows_ai_generator.php'>→ Zum Generator</a>";
} else {
    echo "❌ FEHLER: Dropdown nicht gefunden!\n\n";
    echo "Suche nach: " . htmlspecialchars($old);
}
?>
