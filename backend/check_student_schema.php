<?php
include 'db_connect.php';
print_r($conn->query("DESCRIBE preloaded_students")->fetchAll(PDO::FETCH_ASSOC));
?>
