<?php
require 'backend/db_connect.php';

try {
    $stmt = $conn->query("SELECT * FROM admin_auth_log ORDER BY id DESC LIMIT 5");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Recent OTP Logs:\n";
    foreach ($logs as $log) {
        echo "ID: " . $log['id'] . " | User ID: " . $log['user_id'] . " | OTP: " . $log['otp'] . " | Expires: " . $log['expires_at'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
