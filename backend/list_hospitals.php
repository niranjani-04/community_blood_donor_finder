<?php
include 'db_connect.php';
$res = $conn->query('SELECT * FROM hospitals');
$hospitals = $res->fetchAll(PDO::FETCH_ASSOC);
print_r($hospitals);
?>
