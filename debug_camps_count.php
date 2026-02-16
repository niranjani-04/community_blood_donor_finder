<?php
include 'backend/db_connect.php';
$res = $conn->query('SELECT COUNT(*) FROM blood_camps')->fetchColumn();
echo "Total camps in DB: " . $res . "\n";
$res2 = $conn->query('SELECT * FROM blood_camps')->fetchAll(PDO::FETCH_ASSOC);
foreach($res2 as $row) {
    echo "ID: " . $row['camp_id'] . " | Date: " . $row['camp_date'] . " | Title: " . $row['title'] . "\n";
}
?>
