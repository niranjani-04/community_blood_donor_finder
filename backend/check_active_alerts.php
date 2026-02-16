<?php
include 'db_connect.php';
$stmt = $conn->query("SELECT * FROM sos_alerts WHERE status = 'active'");
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($alerts, JSON_PRETTY_PRINT);
?>
