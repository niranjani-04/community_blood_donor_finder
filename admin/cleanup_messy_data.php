<?php
include '../backend/db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access Denied.");
}

try {
    // Delete students with clearly swapped data (Blood Group = 'GOOD' or Health = 'A+')
    $stmt = $conn->prepare("DELETE FROM preloaded_students WHERE blood_group = 'GOOD' OR health_eligibility IN ('A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-')");
    $stmt->execute();
    $count = $stmt->rowCount();
    
    $_SESSION['message'] = "✅ Cleanup Complete! Removed $count records with misaligned data rows. You can now re-upload your CSV.";
    $_SESSION['message_type'] = "success";
    header("Location: dashboard.php#registry");
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
