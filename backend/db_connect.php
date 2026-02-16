<?php
// Global Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Environment Variables (Railway) with Local Fallback (XAMPP)
$host = getenv('DB_HOST') ?: "127.0.0.1";
$port = getenv('DB_PORT') ?: "3307";
$db_name = getenv('DB_NAME') ?: "blood_sos_system";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASSWORD') ?: "";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // echo "Connected successfully"; 
} catch(PDOException $e) {
    // Log error instead of showing sensitive details in production
    error_log("Connection failed: " . $e->getMessage());
    echo "Database connection failed. Please try again later.";
    die();
}
?>
