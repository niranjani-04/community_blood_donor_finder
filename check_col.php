<?php
include 'backend/db_connect.php';
$stmt = $conn->query("SHOW COLUMNS FROM sos_responses LIKE 'accepted_at'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($row);
?>
