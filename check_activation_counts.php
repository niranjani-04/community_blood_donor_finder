<?php
require 'backend/db_connect.php';

$total_preloaded = $conn->query("SELECT COUNT(*) FROM preloaded_students")->fetchColumn();
$total_activated = $conn->query("SELECT COUNT(*) FROM users WHERE role='donor'")->fetchColumn();

echo "Total Preloaded: $total_preloaded\n";
echo "Total Activated : $total_activated\n";

$not_activated = $conn->query("SELECT COUNT(*) FROM preloaded_students p LEFT JOIN users u ON p.register_number = u.register_number WHERE u.user_id IS NULL")->fetchColumn();
echo "Students NOT yet activated: $not_activated\n";
?>
