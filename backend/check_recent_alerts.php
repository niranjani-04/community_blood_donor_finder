<?php
include 'db_connect.php';
$stmt = $conn->query("SELECT alert_id, blood_group, created_at, status FROM sos_alerts ORDER BY created_at DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
