<?php
require 'backend/db_connect.php';
$users = $conn->query("SELECT user_id, name, role FROM users WHERE role IN ('admin', 'donor', 'requester') LIMIT 10")->fetchAll();
echo json_encode($users);
?>
