<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require 'db_connect.php';

// Prevent timeout
set_time_limit(0);

function getGlobalData($conn) {
    // 1. Fetch Active Alerts
    $alerts = $conn->query("SELECT a.alert_id, a.blood_group, a.latitude, a.longitude, u.name as requester 
                            FROM sos_alerts a 
                            JOIN users u ON a.requester_id = u.user_id 
                            WHERE a.status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Fetch Responding Donors (those with accepted responses)
    $donors = $conn->query("SELECT u.user_id, u.name, u.phone, u.blood_group, u.latitude, u.longitude, r.alert_id 
                            FROM users u 
                            JOIN sos_responses r ON u.user_id = r.donor_id 
                            WHERE r.status = 'accepted'")->fetchAll(PDO::FETCH_ASSOC);
                            
    // 3. Fetch Hospitals for reference
    $hospitals = $conn->query("SELECT name, latitude, longitude FROM hospitals")->fetchAll(PDO::FETCH_ASSOC);

    return [
        'alerts' => $alerts,
        'donors' => $donors,
        'hospitals' => $hospitals,
        'timestamp' => date('H:i:s')
    ];
}

while (true) {
    $data = getGlobalData($conn);
    echo "data: " . json_encode($data) . "\n\n";
    
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
    
    // Wait for 1 second before next update
    sleep(1);
}
