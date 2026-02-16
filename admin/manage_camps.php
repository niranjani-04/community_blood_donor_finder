<?php
/**
 * BLOOD CAMP MANAGEMENT
 * Handles Add, Edit, Delete operations for blood donation camps
 */

session_start();
require_once '../backend/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
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
        $title = trim($_POST['camp_title']);
        $location = trim($_POST['camp_location']);
        $camp_date = $_POST['camp_date'];
        $start_time = $_POST['camp_start'];
        $end_time = $_POST['camp_end'] ?? null;
        $organized_by = trim($_POST['camp_org']);
        $contact_phone = trim($_POST['camp_phone']);
        $description = trim($_POST['camp_desc'] ?? '');
        
        // Validation
        if (empty($title) || empty($location) || empty($camp_date) || empty($start_time) || empty($organized_by) || empty($contact_phone)) {
            throw new Exception("All required fields must be filled!");
        }
        
        if (!preg_match('/^[0-9]{10}$/', $contact_phone)) {
            throw new Exception("Phone number must be 10 digits!");
        }
        
        // Validate date is not in the past
        if (strtotime($camp_date) < strtotime(date('Y-m-d'))) {
            throw new Exception("Camp date cannot be in the past!");
        }
        
        // ADD NEW CAMP
        if ($action == 'add_camp') {
            $sql = "INSERT INTO blood_camps (title, location, camp_date, start_time, end_time, organized_by, contact_phone, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$title, $location, $camp_date, $start_time, $end_time, $organized_by, $contact_phone, $description]);
            
            $message = "Blood camp added successfully!";
            $message_type = "success";
        }
        
        // EDIT EXISTING CAMP
        elseif ($action == 'edit_camp') {
            $camp_id = (int)$_POST['camp_id'];
            
            if ($camp_id <= 0) {
                throw new Exception("Invalid camp ID!");
            }
            
            $sql = "UPDATE blood_camps 
                    SET title = ?, location = ?, camp_date = ?, start_time = ?, end_time = ?, 
                        organized_by = ?, contact_phone = ?, description = ? 
                    WHERE camp_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$title, $location, $camp_date, $start_time, $end_time, $organized_by, $contact_phone, $description, $camp_id]);
            
            $message = "Blood camp updated successfully!";
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

if (isset($_GET['delete'])) {
    $camp_id = (int)$_GET['delete'];
    
    try {
        if ($camp_id <= 0) {
            throw new Exception("Invalid camp ID!");
        }
        
        $sql = "DELETE FROM blood_camps WHERE camp_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$camp_id]);
        
        $message = "Blood camp deleted successfully!";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Error deleting camp: " . $e->getMessage();
        $message_type = "error";
    }
}

// Redirect back to dashboard with message
if (!empty($message)) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type;
}

header("Location: dashboard.php#camps");
exit();
?>
