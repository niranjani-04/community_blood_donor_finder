<?php
include 'db_connect.php';
$stmt = $conn->query("DESCRIBE donation_history");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
