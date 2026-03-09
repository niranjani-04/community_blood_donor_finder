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
$u_stmt = $conn->prepare("SELECT blood_group, availability_status, phone, is_activated FROM users WHERE user_id = ?");
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

// Security Check: Only activated donors can receive alerts
if ($me['is_activated'] == 0) {
    echo '<div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger p-3 rounded-4 mb-0">';
    echo '<h6 class="fw-bold mb-1"><i class="fas fa-shield-alt me-2"></i> Security Verification Required</h6>';
    echo '<p class="text-xs mb-0">Your account is not yet activated for security reasons. Please contact the administrator or verify your student record to enable life-saving features.</p>';
    echo '</div>';
    exit;
}

// Ensure location_name column exists (safe migration)
try {
    $conn->exec("ALTER TABLE sos_alerts ADD COLUMN IF NOT EXISTS location_name TEXT NULL");
} catch (Exception $e) { /* Column may already exist */ }

// Fetch active alerts ONLY matching user's blood group (AND NOT OWNED BY ME or MY PHONE)
$bg = trim($me['blood_group']);
$sql = "SELECT s.*, u.name as requester_name, u.phone, 
        (SELECT COUNT(*) FROM sos_responses r WHERE r.alert_id = s.alert_id AND r.status = 'accepted') as responder_count,
        (SELECT COUNT(*) FROM sos_responses r WHERE r.alert_id = s.alert_id AND r.donor_id = ? AND r.status = 'accepted') as my_response_count
        FROM sos_alerts s 
        JOIN users u ON s.requester_id = u.user_id 
        WHERE s.status = 'active' 
        AND TRIM(s.blood_group) = TRIM(?) 
        AND s.requester_id != ?
        AND u.phone != ?
        ORDER BY s.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$id, $bg, $id, $my_phone]);
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($alerts) > 0) {
    foreach ($alerts as $row) {
        $responder_count = (int)$row['responder_count'];
        $status_class = $responder_count > 0 ? 'text-warning' : 'text-success';
        $status_text = $responder_count > 0 ? "🏃 $responder_count Donor(s) already En Route" : "🚨 No responders yet - Be the first!";

        echo '<div class="alert-card p-4 rounded-4 mb-3" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">';
        echo '<div class="d-flex justify-content-between align-items-start mb-3">';
        echo '<div><h3 class="h5 fw-bold mb-1 text-danger">🩸 Needed: ' . htmlspecialchars($row['blood_group']) . '</h3>';
        echo '<p class="text-xs ' . $status_class . ' fw-bold uppercase mb-0" style="letter-spacing:1px">' . $status_text . '</p></div>';
        echo '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">URGENT</span>';
        echo '</div>';
        
        echo '<div class="small mb-4 opacity-75">';
        echo '<div class="mb-1"><i class="fas fa-user-circle me-2"></i><strong>Requester:</strong> ' . htmlspecialchars($row['requester_name']) . '</div>';
        echo '<div class="mb-1"><i class="fas fa-phone-alt me-2"></i><strong>Contact:</strong> ' . htmlspecialchars($row['phone']) . '</div>';

        // Show place name if available, otherwise coordinates as fallback
        if (!empty($row['location_name'])) {
            $loc_display = htmlspecialchars($row['location_name']);
        } else {
            $loc_display = '(' . round($row['latitude'], 4) . '°, ' . round($row['longitude'], 4) . '°)';
        }
        echo '<div><i class="fas fa-map-marker-alt me-2 text-danger"></i><strong>Location:</strong> ' . $loc_display . '</div>';
        echo '</div>';

        if ($row['my_response_count'] > 0) {
            echo '<button class="btn btn-secondary w-100 py-2 fw-bold" disabled>';
            echo '<i class="fas fa-check-circle me-2"></i> Already Accepted';
            echo '</button>';
        } else {
            echo '<button class="btn btn-danger w-100 py-2 fw-bold" onclick="acceptRequest(this, ' . $row['alert_id'] . ')">';
            echo '<i class="fas fa- ambulance me-2"></i> ' . ($responder_count > 0 ? 'Join Response' : 'Accept & Track Me');
            echo '</button>';
        }
        echo '</div>';
    }
} else {
    echo '<div class="text-center py-5 opacity-50">';
    echo '<i class="fas fa-satellite-dish fa-3x mb-3"></i>';
    echo '<p>No active SOS alerts nearby matches your blood group.</p>';
    echo '</div>';
}
?>
