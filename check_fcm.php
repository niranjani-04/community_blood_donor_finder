<?php
require 'backend/db_connect.php';
$stmt = $conn->query("SELECT name, fcm_token FROM users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
$tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($tokens);
?>
