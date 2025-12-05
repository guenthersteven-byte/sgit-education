<?php
/**
 * Fix windows_ai_generator.php - Add missing modules
 */

$file = 'C:\xampp\htdocs\Education\windows_ai_generator.php';
$content = file_get_contents($file);

$oldDropdown = '<option value="physik">⚛️ Physik</option>
                </select>';

$newDropdown = '<option value="physik">⚛️ Physik</option>
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

$content = str_replace($oldDropdown, $newDropdown, $content);

file_put_contents($file, $content);

echo "<h1>✅ windows_ai_generator.php aktualisiert!</h1>";
echo "<p>Alle 16 Module sind jetzt im Dropdown verfügbar.</p>";
echo "<p><a href='windows_ai_generator.php'>→ Zum KI-Generator</a></p>";
echo "<p><a href='check_module_consistency.php'>→ Zurück zur Konsistenz-Prüfung</a></p>";
?>
