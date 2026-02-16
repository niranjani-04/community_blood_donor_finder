<?php
session_start();
include 'db_connect.php';

if (!isset($_GET['alert_id'])) {
    exit(json_encode([]));
}

$alert_id = $_GET['alert_id'];

// Fetch donors who accepted this alert
$sql = "SELECT u.name, u.blood_group, u.phone, r.status, r.accepted_at 
        FROM sos_responses r 
        JOIN users u ON r.donor_id = u.user_id 
        WHERE r.alert_id = ? 
        ORDER BY r.accepted_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$alert_id]);
$acceptors = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($acceptors);
?>
