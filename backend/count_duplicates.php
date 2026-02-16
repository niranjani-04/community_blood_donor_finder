<?php
include 'db_connect.php';
$stmt = $conn->query("SELECT requester_id, blood_group, COUNT(*) as count FROM sos_alerts WHERE status = 'active' GROUP BY requester_id, blood_group");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "User ID: " . $row['requester_id'] . " | BG: " . $row['blood_group'] . " | Count: " . $row['count'] . "\n";
}
?>
