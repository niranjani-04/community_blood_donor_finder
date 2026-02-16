<?php
include 'db_connect.php';

// Keep only the latest active SOS for each user
$sql = "UPDATE sos_alerts 
        SET status = 'cancelled' 
        WHERE status = 'active' 
        AND alert_id NOT IN (
            SELECT max_id FROM (
                SELECT MAX(alert_id) as max_id 
                FROM sos_alerts 
                WHERE status = 'active' 
                GROUP BY requester_id
            ) as tmp
        )";

$count = $conn->exec($sql);
echo "Cleaned up $count duplicate alerts.\n";
?>
