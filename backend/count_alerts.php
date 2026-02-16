<?php
include 'db_connect.php';
$cnt = $conn->query("SELECT COUNT(*) FROM sos_alerts")->fetchColumn();
echo "Total Alerts: $cnt\n";
?>
