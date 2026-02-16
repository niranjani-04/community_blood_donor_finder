<?php
require 'backend/db_connect.php';

echo "<h3>Check for Trailing Spaces</h3>";
$stmt = $conn->query("SELECT user_id, register_number, LENGTH(register_number) as len FROM users WHERE role='donor' LIMIT 10");
foreach($stmt->fetchAll() as $r) {
    echo "User ID {$r['user_id']}: '{$r['register_number']}' (Length: {$r['len']})\n";
}

$stmt = $conn->query("SELECT register_number, LENGTH(register_number) as len FROM preloaded_students LIMIT 10");
foreach($stmt->fetchAll() as $r) {
    echo "Preloaded: '{$r['register_number']}' (Length: {$r['len']})\n";
}
?>
