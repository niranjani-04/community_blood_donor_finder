<?php
include 'backend/db_connect.php';
$res = $conn->query('SELECT * FROM blood_camps')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
?>
