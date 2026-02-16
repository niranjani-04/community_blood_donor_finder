<?php
require 'backend/db_connect.php';

echo "<h3>Preloaded Students Sample</h3>";
$stmt = $conn->query("SELECT register_number, name, dob FROM preloaded_students LIMIT 5");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>"; print_r($students); echo "</pre>";

echo "<h3>Users Sample (Donors/Students)</h3>";
$stmt = $conn->query("SELECT user_id, register_number, role FROM users LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>"; print_r($users); echo "</pre>";

echo "<h3>Check specific registration number case-sensitivity and trimming</h3>";
$test_reg = "21UCS101"; // Just a guess
$stmt = $conn->prepare("SELECT register_number FROM preloaded_students WHERE register_number LIKE ?");
$stmt->execute(['%'.$test_reg.'%']);
$search = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>"; print_r($search); echo "</pre>";
?>
