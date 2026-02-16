<?php
require 'backend/db_connect.php';

$reg_no = '245213201';
$dob = '2004-11-01';

$stmt = $conn->prepare("SELECT u.*, p.name as student_name 
                        FROM users u 
                        JOIN preloaded_students p ON u.register_number = p.register_number 
                        WHERE TRIM(UPPER(u.register_number)) = TRIM(UPPER(?)) AND p.dob = ?");
$stmt->execute([$reg_no, $dob]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "SUCCESS: Found user " . $user['student_name'] . "\n";
} else {
    echo "FAILED: User not found with JOIN.\n";
    
    // Check missing part
    $stmt1 = $conn->prepare("SELECT * FROM users WHERE register_number = ?");
    $stmt1->execute([$reg_no]);
    $u = $stmt1->fetch();
    echo "Users table has record: " . ($u ? "YES" : "NO") . "\n";
    
    $stmt2 = $conn->prepare("SELECT * FROM preloaded_students WHERE register_number = ?");
    $stmt2->execute([$reg_no]);
    $p = $stmt2->fetch();
    echo "Preloaded table has record: " . ($p ? "YES" : "NO") . "\n";
    
    if ($p) echo "Preloaded DOB: " . $p['dob'] . " vs Input DOB: " . $dob . "\n";
}
?>
