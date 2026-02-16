<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reg_no = $_POST['register_number'];
    $dob = $_POST['dob'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    // 1. Verify student exists in preloaded registry
    $stmt = $conn->prepare("SELECT * FROM preloaded_students WHERE register_number = ? AND dob = ?");
    $stmt->execute([$reg_no, $dob]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        // 2. Check if already activated
        $check_stmt = $conn->prepare("SELECT * FROM users WHERE register_number = ?");
        $check_stmt->execute([$reg_no]);
        
        if ($check_stmt->rowCount() > 0) {
            echo "<script>alert('Account already activated! Please login.'); window.location.href='../login.php';</script>";
        } else {
            // 3. Create new user account
            $insert_stmt = $conn->prepare("INSERT INTO users (register_number, name, email, phone, blood_group, role, points, availability_status) 
                                          VALUES (?, ?, ?, ?, ?, 'donor', 100, 'Available')");
            
            if ($insert_stmt->execute([$reg_no, $student['name'], $email, $phone, $student['blood_group']])) {
                echo "<script>alert('Activation successful! You can now login.'); window.location.href='../login.php';</script>";
            } else {
                echo "<script>alert('Error during activation. Please try again.'); window.location.href='../activate.php';</script>";
            }
        }
    } else {
        echo "<script>alert('Student details not found in registry. Please verify your Register Number and Date of Birth (YYYY-MM-DD).'); window.location.href='../activate.php';</script>";
    }
}
?>
