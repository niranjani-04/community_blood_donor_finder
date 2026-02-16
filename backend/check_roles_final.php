<?php
include 'db_connect.php';
$stmt = $conn->query("SELECT DISTINCT role FROM users");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "[" . $row['role'] . "]\n";
}
?>
