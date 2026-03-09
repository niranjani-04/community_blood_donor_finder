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
            // 3. Create new user account - starts as LOCKED, admin must activate it
            $insert_stmt = $conn->prepare("INSERT INTO users (register_number, name, email, phone, blood_group, role, points, availability_status, is_activated) 
                                          VALUES (?, ?, ?, ?, ?, 'donor', 100, 'Available', 0)");
            
            if ($insert_stmt->execute([$reg_no, $student['name'], $email, $phone, $student['blood_group']])) {
                echo "<script>alert('Registration successful! Your account is pending admin activation. You can login now but SOS features will be restricted until an administrator activates your account.'); window.location.href='../login.php';</script>";
            } else {
                echo "<script>alert('Error during registration. Please try again.'); window.location.href='../activate.php';</script>";
            }
        }
    } else {
        echo "<script>alert('Verification failed: Student record not found.'); window.location.href='../activate.php';</script>";
    }
}
?>
