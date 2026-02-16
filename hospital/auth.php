<?php
session_start();
include '../backend/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // For a real app, use password_verify. For this demo, we'll check plain text if not hashed.
    $stmt = $conn->prepare("SELECT * FROM hospitals WHERE email = ?");
    $stmt->execute([$email]);
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($hospital) {
        // Simple plain text check for demo, or verify hash if you want to be professional
        if ($password === $hospital['password'] || (password_get_info($hospital['password'])['algo'] && password_verify($password, $hospital['password']))) {
            $_SESSION['hospital_id'] = $hospital['hospital_id'];
            $_SESSION['hospital_name'] = $hospital['name'];
            header("Location: dashboard.php");
            exit();
        }
    }
    header("Location: index.php?error=1");
    exit();
}
?>
