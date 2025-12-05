<?php
$db = new PDO('sqlite:C:\xampp\htdocs\Education\AI\data\questions.db');
$result = $db->query("PRAGMA table_info(questions)");
echo "<pre>";
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
echo "</pre>";
?>
