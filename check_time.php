<?php
require 'backend/db_connect.php';
$res = $conn->query("SELECT NOW() as db_now")->fetch();
echo "DB NOW: " . $res['db_now'] . "\n";
echo "PHP NOW: " . date('Y-m-d H:i:s') . "\n";
?>
