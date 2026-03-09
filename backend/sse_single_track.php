<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require 'db_connect.php';

$alert_id = $_GET['alert_id'] ?? null;
if (!$alert_id) {
    die("data: {\"error\": \"No alert_id\"}\n\n");
}

set_time_limit(0);

while (true) {
    // TRIGGER WATCHDOG (Automatic Mechanism)
    // We execute it internally to check for stale responders
    include 'sos_watchdog.php';

    // Fetch Donors for this specific alert (include stale - means accepted but GPS went idle)
    $stmt = $conn->prepare("SELECT u.name, u.phone, u.blood_group, u.latitude, u.longitude, u.gps_accuracy as accuracy, u.updated_at, r.status as response_status
                            FROM users u 
                            JOIN sos_responses r ON u.user_id = r.donor_id 
                            WHERE r.alert_id = ? AND r.status IN ('accepted','stale')");
    $stmt->execute([$alert_id]);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "data: " . json_encode($donors) . "\n\n";
    
    if (ob_get_level() > 0) ob_flush();
    flush();
    
    sleep(1);
}
