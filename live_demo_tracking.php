<?php
require 'backend/db_connect.php';

// 1. Create a fresh SOS Alert
$requester_id = 37; // Admin
$blood_group = 'O+';
$sos_lat = 10.8211; // Bishop Heber College
$sos_lng = 78.6934;

$stmt = $conn->prepare("INSERT INTO sos_alerts (requester_id, blood_group, latitude, longitude, status) VALUES (?, ?, ?, ?, 'active')");
$stmt->execute([$requester_id, $blood_group, $sos_lat, $sos_lng]);
$alert_id = $conn->lastInsertId();

// 2. Create a Donor Response
$donor_id = 46; // Niranjani (Donor)
$stmt = $conn->prepare("INSERT INTO sos_responses (alert_id, donor_id, status) VALUES (?, ?, 'accepted')");
$stmt->execute([$alert_id, $donor_id]);

echo "Starting movement simulation (60 steps)...\n";

// Waypoints to follow roads - Start much closer for a quick demo
$waypoints = [
    ['lat' => 10.8195, 'lng' => 78.6915], // Start closer
    ['lat' => 10.8195, 'lng' => 78.6934], // Turn Corner
    ['lat' => 10.8211, 'lng' => 78.6934]  // Destination (SOS Point)
];

function getDistancePHP($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

$total_steps = 40;
$steps_per_segment = $total_steps / (count($waypoints) - 1);

for ($i = 0; $i <= $total_steps; $i++) {
    $segment = min(floor($i / $steps_per_segment), count($waypoints) - 2);
    $segment_progress = ($i % $steps_per_segment) / $steps_per_segment;
    
    $start = $waypoints[$segment];
    $end = $waypoints[$segment + 1];
    
    $current_lat = $start['lat'] + (($end['lat'] - $start['lat']) * $segment_progress);
    $current_lng = $start['lng'] + (($end['lng'] - $start['lng']) * $segment_progress);

    $dist = getDistancePHP($sos_lat, $sos_lng, $current_lat, $current_lng);
    $eta = ceil(($dist / 20) * 60); // min
    $distStr = ($dist < 1) ? round($dist * 1000) . "m" : round($dist, 2) . "km";

    $upd = $conn->prepare("UPDATE users SET latitude = ?, longitude = ? WHERE user_id = ?");
    $upd->execute([$current_lat, $current_lng, $donor_id]);

    echo "[$i/$total_steps] Pos: $current_lat, $current_lng | Dist: $distStr | ETA: $eta min\n";
    usleep(300000); // Faster updates for smoother demo
}

echo "\nSimulation Complete. Donor has arrived at the SOS point.\n";
echo "Visit: http://localhost/community/track.php?alert_id=$alert_id\n";
?>
