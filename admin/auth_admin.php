<?php
session_start();

include '../backend/db_connect.php';

// CONSTANT ADMIN CREDENTIALS for Security
define('ADMIN_EMAIL', 'admin@heber.edu');
define('ADMIN_PASS', 'admin123');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Check for rate limiting
    $rate_stmt = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
    $rate_stmt->execute([$ip]);
    $rate = $rate_stmt->fetch(PDO::FETCH_ASSOC);

    if ($rate && $rate['attempts'] >= 5 && (time() - strtotime($rate['last_attempt'])) < 900) {
        die("Too many login attempts. Please try again after 15 minutes.");
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($email === ADMIN_EMAIL && $password === ADMIN_PASS) {
        // Reset rate limit
        $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
        
        // FIX: Ensure Admin exists in DB to satisfy Foreign Keys
        $stmt = $conn->prepare("SELECT user_id, name FROM users WHERE email = ?");
        $stmt->execute([ADMIN_EMAIL]);
        $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($adminUser) {
            $real_id = $adminUser['user_id'];
            $real_name = $adminUser['name'];
        } else {
            // Auto-create Admin in DB if missing
            $sql = "INSERT INTO users (register_number, name, email, phone, password, role, blood_group, is_activated, availability_status) 
                    VALUES (NULL, 'System Administrator', ?, '0000000000', ?, 'admin', 'O+', 1, 'Available')";
            $stmt = $conn->prepare($sql);
            // Use dummy hash for password column constraint
            $dummyHash = password_hash(ADMIN_PASS, PASSWORD_DEFAULT);
            $stmt->execute([ADMIN_EMAIL, $dummyHash]);
            $real_id = $conn->lastInsertId();
            $real_name = "System Administrator";
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        $log_stmt = $conn->prepare("INSERT INTO admin_auth_log (user_id, otp, expires_at) VALUES (?, ?, ?)");
        $log_stmt->execute([$real_id, $otp, $expires]);

        // Secure Session (Partial auth)
        session_regenerate_id(true);
        $_SESSION['admin_pre_auth'] = true;
        $_SESSION['admin_id'] = $real_id;
        $_SESSION['admin_name'] = $real_name;
        $_SESSION['admin_otp'] = $otp; // For simulation/log
        
        // Log to security console (simulated)
        $log_file = '../backend/security_audit.txt';
        $log_msg = "[" . date('Y-m-d H:i:s') . "] 2FA INVOLKED: Admin ($real_name) - OTP: $otp\n";
        file_put_contents($log_file, $log_msg, FILE_APPEND);

        header("Location: otp_verify.php");
        exit();
    } else {
        // Log failed attempt
        if ($rate) {
            $conn->prepare("UPDATE login_attempts SET attempts = attempts + 1 WHERE ip_address = ?")->execute([$ip]);
        } else {
            $conn->prepare("INSERT INTO login_attempts (ip_address, attempts) VALUES (?, 1)")->execute([$ip]);
        }
        echo "<script>alert('❌ ACCESS DENIED: Invalid Credentials'); window.location.href='index.php';</script>";
        exit();
    }
}
?>
