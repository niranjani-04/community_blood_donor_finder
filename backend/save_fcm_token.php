<?php
session_start();
include 'db_connect.php';

if (isset($_POST['token']) && isset($_SESSION['user_id'])) {
    $token = $_POST['token'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE users SET fcm_token = ? WHERE user_id = ?");
    if ($stmt->execute([$token, $user_id])) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
} else {
    echo json_encode(["status" => "unauthorized"]);
}
?>
