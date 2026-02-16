<?php
include 'db_connect.php';

// Group by phone to catch multi-account duplicates
$sql = "UPDATE sos_alerts 
        SET status = 'cancelled' 
        WHERE status = 'active' 
        AND alert_id NOT IN (
            SELECT max_id FROM (
                SELECT MAX(s.alert_id) as max_id 
                FROM sos_alerts s
                JOIN users u ON s.requester_id = u.user_id
                WHERE s.status = 'active' 
                GROUP BY u.phone
            ) as tmp
        )";

$count = $conn->exec($sql);
echo "Phone cleanup: Cancelled $count duplicate alerts across shared phone numbers.\n";
?>
