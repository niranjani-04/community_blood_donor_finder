<?php
include 'db_connect.php';

$tables = ['sos_alerts', 'sos_responses', 'tracking'];

foreach ($tables as $table) {
    $stmt = $conn->query("SELECT COUNT(*) FROM $table");
    $count = $stmt->fetchColumn();
    echo "Table $table: $count rows\n";
}
?>
