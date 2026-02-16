<?php
include 'db_connect.php';
print_r($conn->query("DESCRIBE sos_responses")->fetchAll(PDO::FETCH_ASSOC));
?>
