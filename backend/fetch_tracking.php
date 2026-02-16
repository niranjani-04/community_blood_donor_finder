<?php
include 'db_connect.php';

$alert_id = $_GET['alert_id'];

// Get donors who accepted this alert
$sql = "SELECT u.name, u.phone, u.latitude, u.longitude, u.blood_group 
        FROM sos_responses r
        JOIN users u ON r.donor_id = u.user_id
        WHERE r.alert_id = ? AND r.status = 'accepted'";
$stmt = $conn->prepare($sql);
$stmt->execute([$alert_id]);
// Return JSON for Leaflet JS Map
header('Content-Type: application/json');
$donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($donors);
?>
