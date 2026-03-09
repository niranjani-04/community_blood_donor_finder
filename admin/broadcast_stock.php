<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied");
}
include '../backend/db_connect.php';
require_once '../backend/notification_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['blood_group'])) {
    $bg = $_POST['blood_group'];
    $hospital_id = $_POST['hospital_id'];
    
    // Fetch Hospital Name
    $h_stmt = $conn->prepare("SELECT name FROM hospitals WHERE hospital_id = ?");
    $h_stmt->execute([$hospital_id]);
    $h_name = $h_stmt->fetchColumn();

    // Use centralized broadcast helper
    $count = broadcastStockAlert($conn, $hospital_id, $bg);

    header("Location: dashboard.php?success=broadcast_sent&count=$count");
    exit();
}
?>
