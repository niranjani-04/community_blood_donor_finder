<?php
/**
 * SOS ALERTS STATUS UPDATE
 * Handles status updates for SOS alerts (Resolved/Pending/Cancelled)
 */

session_start();
require_once '../backend/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access: 2FA Required']);
    exit();
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }
    
    $alert_id = (int)($_POST['alert_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    
    // Validation
    if ($alert_id <= 0) {
        throw new Exception("Invalid alert ID");
    }
    
    $allowed_statuses = ['active', 'accepted', 'completed', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        throw new Exception("Invalid status value");
    }
    
    // Update alert status
    $sql = "UPDATE sos_alerts SET status = ?, updated_at = NOW() WHERE alert_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$new_status, $alert_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Alert status updated successfully',
            'new_status' => $new_status
        ]);
    } else {
        throw new Exception("No alert found with the given ID");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
