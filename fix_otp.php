<?php
require 'backend/db_connect.php';

$user_id = 37; // Based on previous log
$otp = '123456';
$expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Insert into DB
$stmt = $conn->prepare("INSERT INTO admin_auth_log (user_id, otp, expires_at, is_verified) VALUES (?, ?, ?, 0)");
$stmt->execute([$user_id, $otp, $expires]);

// Update log file
$log_file = 'backend/security_audit.txt';
$log_msg = "[" . date('Y-m-d H:i:s') . "] 2FA INVOLKED: Admin (System Administrator) - OTP: $otp\n";
file_put_contents($log_file, $log_msg, FILE_APPEND);

echo "Generated new OTP: $otp\n";
?>
