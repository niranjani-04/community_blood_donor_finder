<?php
session_start();
require_once '../backend/db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 0;

    if ($user_id > 0) {
        $stmt = $conn->prepare("UPDATE users SET is_activated = ? WHERE user_id = ? AND role = 'donor'");
        if ($stmt->execute([$status, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'Donor security status updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid Donor ID.']);
    }
}
?>
