<?php
include 'db_connect.php';
echo "--- USERS ---\n";
print_r($conn->query("SELECT user_id, name, role, register_number FROM users")->fetchAll(PDO::FETCH_ASSOC));
echo "\n--- PRELOADED STUDENTS ---\n";
print_r($conn->query("SELECT COUNT(*) FROM preloaded_students")->fetchColumn());
echo " students found.\n";
?>
