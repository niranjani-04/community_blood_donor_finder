<?php
$host = "127.0.0.1";
$db_name = "blood_sos_system";
$username = "root";
$password = "";

echo "Testing 3307...\n";
try {
    $conn1 = new PDO("mysql:host=localhost;port=3307;dbname=$db_name", $username, $password);
    echo "Success on 3307!\n";
} catch(PDOException $e) {
    echo "Failed on 3307: " . $e->getMessage() . "\n";
}

echo "Testing 3306...\n";
try {
    $conn2 = new PDO("mysql:host=localhost;port=3306;dbname=$db_name", $username, $password);
    echo "Success on 3306!\n";
} catch(PDOException $e) {
    echo "Failed on 3306: " . $e->getMessage() . "\n";
}
?>
