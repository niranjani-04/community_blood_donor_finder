<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Access Denied: Please login.");
}

$role = trim(strtolower($_SESSION['role'] ?? ''));
if ($role != 'donor' && $role != 'admin') {
    die("Access Denied: Role restricted.");
}

// Get current user info to check eligibility and matching
$id = $_SESSION['user_id'];
$u_stmt = $conn->prepare("SELECT blood_group, availability_status, phone FROM users WHERE user_id = ?");
$u_stmt->execute([$id]);
$me = $u_stmt->fetch(PDO::FETCH_ASSOC);

if (!$me) {
    die("Session Expired: User record not found. Please log in again.");
}

$my_phone = trim($me['phone']);

if ($me['availability_status'] != 'Available') {
    echo '<p class="text-white">Status: <strong>' . htmlspecialchars($me['availability_status']) . '</strong>. You are currently not eligible to receive alerts.</p>';
    exit;
}

// Fetch active alerts ONLY matching user's blood group (AND NOT OWNED BY ME or MY PHONE)
$bg = trim($me['blood_group']);
$sql = "SELECT s.*, u.name as requester_name, u.phone 
        FROM sos_alerts s 
        JOIN users u ON s.requester_id = u.user_id 
        WHERE s.status = 'active' 
        AND TRIM(s.blood_group) = TRIM(?) 
        AND s.requester_id != ?
        AND u.phone != ?
        ORDER BY s.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$bg, $id, $my_phone]);
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($alerts) > 0) {
    foreach ($alerts as $row) {
        echo '<div class="alert-card">';
        echo '<h3>🩸 Needed: ' . htmlspecialchars($row['blood_group']) . '</h3>';
        echo '<p><strong>Requester:</strong> ' . htmlspecialchars($row['requester_name']) . '</p>';
        echo '<p><strong>Phone:</strong> ' . htmlspecialchars($row['phone']) . '</p>';
        // Link to view location on map could be added here
        echo '<button class="btn btn-update" onclick="acceptRequest(' . $row['alert_id'] . ')">Acccept & Track Me</button>';
        echo '</div>';
    }
} else {
    echo '<p class="text-white">No active SOS alerts nearby.</p>';
}
?>
