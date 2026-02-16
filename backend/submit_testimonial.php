<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['message'])) {
    $message = trim($_POST['message']);
    
    // Auto-approve for this demo, or set to 'pending' if you want admin review
    $stmt = $conn->prepare("INSERT INTO testimonials (message, author_info, status) VALUES (?, 'BHC Student', 'approved')");
    $stmt->execute([$message]);
    
    header("Location: ../index.php?status=testimonial_sent");
    exit();
}
header("Location: ../index.php");
exit();
?>
