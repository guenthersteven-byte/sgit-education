<?php
$db = new PDO('sqlite:C:\xampp\htdocs\Education\AI\data\questions.db');
$stmt = $db->query("SELECT DISTINCT module, COUNT(*) as count FROM questions GROUP BY module");
echo "Module in DB:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['module'] . " (" . $row['count'] . " Fragen)\n";
}
?>
