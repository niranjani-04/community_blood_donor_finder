<?php
include 'db_connect.php';
$stmt = $conn->prepare("SELECT user_id, name, phone, email, blood_group FROM users WHERE phone LIKE ? OR email LIKE ?");
$term = "%7708890703%";
$email_term = "%abi%";
$stmt->execute([$term, $email_term]);
echo "Matching Users:\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
?>
