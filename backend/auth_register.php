<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $blood_group = ($role == 'donor') ? $_POST['blood_group'] : NULL;

    // Check if email exists
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Email already exists!'); window.location.href='../register.php';</script>";
        exit();
    }

    // Insert User
    $sql = "INSERT INTO users (name, email, phone, password, role, blood_group) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    try {
        if ($stmt->execute([$name, $email, $phone, $password, $role, $blood_group])) {
            echo "<script>alert('Registration Successful! Please Login.'); window.location.href='../login.php';</script>";
        } else {
            echo "Error registering user.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
