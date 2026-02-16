<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $regNo = strtoupper(trim($_POST['register_number']));

    // 1. Check Preloaded Registry
    $stmt = $conn->prepare("SELECT name FROM preloaded_students WHERE register_number = ?");
    $stmt->execute([$regNo]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['status' => 'error', 'message' => 'Identity not found in college registry.']);
        exit();
    }

    // 2. Check if already activated in users table
    $stmt = $conn->prepare("SELECT is_activated FROM users WHERE register_number = ?");
    $stmt->execute([$regNo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['is_activated']) {
        echo json_encode(['status' => 'error', 'message' => 'Account already active. Please login.']);
        exit();
    }

    // Success
    echo json_encode(['status' => 'success', 'name' => $student['name']]);
}
?>
