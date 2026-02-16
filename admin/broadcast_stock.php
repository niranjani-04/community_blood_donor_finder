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

    $message = "📢 <b>URGENT: BLOOD STOCK LOW</b>\n$h_name is critically low on <b>$bg</b> blood. If you are eligible, please visit the hospital or check your dashboard for details. Your help is needed! 🙏";

    // 1. Telegram Broadcast
    sendTelegramNotification($message);

    // 2. Filter Top Donors with this blood group for Push/SMS (to avoid spam, let's just do everyone for this demo)
    $stmt = $conn->prepare("SELECT fcm_token, phone FROM users WHERE blood_group = ? AND availability_status = 'Available' AND role = 'donor'");
    $stmt->execute([$bg]);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach($donors as $d) {
        if(!empty($d['fcm_token'])) {
            sendPushNotification($d['fcm_token'], "💉 Stock Request: $bg", "$h_name needs $bg blood urgently.");
        }
        $count++;
    }

    header("Location: dashboard.php?success=broadcast_sent&count=$count");
    exit();
}
?>
