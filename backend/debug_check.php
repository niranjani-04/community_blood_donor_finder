<?php
include 'db_connect.php';

echo "Database connection check...\n";

try {
    $stmt = $conn->query("SELECT 1");
    echo "Connection successful.\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

$email = 'admin@hebert.edu';
$password = 'admin123';

echo "Checking for user: $email\n";

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "User found. ID: " . $user['user_id'] . ", Role: " . $user['role'] . "\n";
    echo "Stored Hash: " . $user['password'] . "\n";
    
    if (password_verify($password, $user['password'])) {
        echo "Password verification: SUCCESS\n";
    } else {
        echo "Password verification: FAILED\n";
        
        // Generate new hash
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        echo "Correct hash should be: $newHash\n";
        
        // Fix it
        $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update->execute([$newHash, $user['user_id']]);
        echo "Password updated to match 'admin123'.\n";
    }
} else {
    echo "User NOT found.\n";
    
    // Create admin user
    echo "Creating admin user...\n";
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, phone, password, role, blood_group) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['Super Admin', $email, '9998887776', $hashed_password, 'admin', 'O+']);
    echo "Admin user created.\n";
}
?>
