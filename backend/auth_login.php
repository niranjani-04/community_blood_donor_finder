<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Check for rate limiting
    $rate_stmt = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
    $rate_stmt->execute([$ip]);
    $rate = $rate_stmt->fetch(PDO::FETCH_ASSOC);

    if ($rate && $rate['attempts'] >= 5 && (time() - strtotime($rate['last_attempt'])) < 900) {
        die("Too many login attempts. Please try again after 15 minutes.");
    }

    $reg_no = $_POST['register_number'];
    $dob = $_POST['dob'];

    // 1. First check if they exist in preloaded_students at all
    $reg_stmt = $conn->prepare("SELECT * FROM preloaded_students WHERE TRIM(UPPER(register_number)) = TRIM(UPPER(?)) AND dob = ?");
    $reg_stmt->execute([$reg_no, $dob]);
    $preloaded = $reg_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$preloaded) {
        // Log failed attempt
        if ($rate) {
            $conn->prepare("UPDATE login_attempts SET attempts = attempts + 1 WHERE ip_address = ?")->execute([$ip]);
        } else {
            $conn->prepare("INSERT INTO login_attempts (ip_address, attempts) VALUES (?, 1)")->execute([$ip]);
        }
        echo "<script>alert('Invalid Register Number or Date of Birth. Please check your data or contact Admin.'); window.location.href='../login.php';</script>";
        exit();
    }

    // 2. Check if user is already activated
    $stmt = $conn->prepare("SELECT u.* FROM users u WHERE TRIM(UPPER(u.register_number)) = TRIM(UPPER(?))");
    $stmt->execute([$reg_no]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // AUTO-ACTIVATE: Add to users table automatically for easier testing.
        $insert_stmt = $conn->prepare("INSERT INTO users (register_number, name, email, phone, blood_group, role, points, availability_status) 
                                      VALUES (?, ?, ?, ?, ?, 'donor', 100, 'Available')");
        $insert_stmt->execute([
            $preloaded['register_number'], 
            $preloaded['name'], 
            $preloaded['email'] ?? ($preloaded['register_number'] . "@student.college.edu"), 
            $preloaded['phone'] ?? "1234567890", 
            $preloaded['blood_group'],
        ]);
        
        // Final retrieve
        $stmt->execute([$reg_no]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($user) {
        // Reset rate limit
        $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
        
        // Secure Session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $preloaded['name']; 
        header("Location: ../index.php");
        exit();
    }
} else {
    // Log failed attempt
    if ($rate) {
        $conn->prepare("UPDATE login_attempts SET attempts = attempts + 1 WHERE ip_address = ?")->execute([$ip]);
    } else {
        $conn->prepare("INSERT INTO login_attempts (ip_address, attempts) VALUES (?, 1)")->execute([$ip]);
    }
    header("Location: ../login.php?status=invalid_credentials");
    exit();
}
?>
