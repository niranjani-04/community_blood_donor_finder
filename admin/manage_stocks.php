<?php
/**
 * BLOOD STOCK MANAGEMENT
 * Handles Add, Update, Delete operations for blood stocks in hospitals
 */

session_start();
require_once '../backend/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header("Location: otp_verify.php");
    exit();
}

$message = '';
$message_type = '';

// ============================================
// HANDLE FORM SUBMISSIONS
// ============================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // UPDATE/ADD STOCK
        if ($action == 'update_stock') {
            $hospital_id = (int)$_POST['hospital_id'];
            $blood_group = $_POST['blood_group'];
            $units = (int)$_POST['units'];

            if ($hospital_id <= 0) {
                throw new Exception("Invalid hospital selected!");
            }
            
            if ($units < 0) {
                throw new Exception("Units cannot be negative!");
            }

            // Check if stock entry exists
            $check = $conn->prepare("SELECT stock_id FROM blood_inventory WHERE hospital_id = ? AND blood_group = ?");
            $check->execute([$hospital_id, $blood_group]);
            
            if ($check->rowCount() > 0) {
                // Update existing
                $sql = "UPDATE blood_inventory SET units = ? WHERE hospital_id = ? AND blood_group = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$units, $hospital_id, $blood_group]);
                $message = "Stock updated successfully!";
            } else {
                // Insert new
                $sql = "INSERT INTO blood_inventory (hospital_id, blood_group, units) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$hospital_id, $blood_group, $units]);
                $message = "New stock entry added successfully!";
            }
            $message_type = "success";
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// ============================================
// HANDLE DELETE REQUEST
// ============================================

if (isset($_GET['delete_stock'])) {
    $stock_id = (int)$_GET['delete_stock'];
    
    try {
        if ($stock_id <= 0) {
            throw new Exception("Invalid stock ID!");
        }

        $stmt = $conn->prepare("DELETE FROM blood_inventory WHERE stock_id = ?");
        $stmt->execute([$stock_id]);
        
        $message = "Stock entry deleted successfully!";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Error deleting stock: " . $e->getMessage();
        $message_type = "error";
    }
}

// Redirect back to dashboard with message
if (!empty($message)) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type;
}

header("Location: dashboard.php#hospitals");
exit();
?>
