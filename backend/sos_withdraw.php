<?php
session_start();
include 'db_connect.php';
include 'notification_helper.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $donor_id = $_SESSION['user_id'];
    $alert_id = $_POST['alert_id'];

    try {
        // 1. Verify existence of the response
        $check = $conn->prepare("SELECT response_id FROM sos_responses WHERE alert_id = ? AND donor_id = ? AND status = 'accepted'");
        $check->execute([$alert_id, $donor_id]);
        
        if ($check->rowCount() == 0) {
            echo json_encode(["status" => "error", "message" => "No active response found to withdraw."]);
            exit;
        }

        // 2. Update status to 'withdrawn'
        $stmt = $conn->prepare("UPDATE sos_responses SET status = 'withdrawn' WHERE alert_id = ? AND donor_id = ?");
        if ($stmt->execute([$alert_id, $donor_id])) {
            
            // 3. Notify Requester and Admin
            $donor_stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
            $donor_stmt->execute([$donor_id]);
            $donor_name = $donor_stmt->fetchColumn();

            $req_stmt = $conn->prepare("SELECT u.name, u.phone, u.email, s.blood_group 
                                       FROM sos_alerts s 
                                       JOIN users u ON s.requester_id = u.user_id 
                                       WHERE s.alert_id = ?");
            $req_stmt->execute([$alert_id]);
            $req_info = $req_stmt->fetch(PDO::FETCH_ASSOC);

            if ($req_info) {
                $msg = "HEARTBEAT UPDATE: Donor $donor_name has withdrawn their response for Alert #$alert_id. We are still looking for other donors!";
                
                sendSMSNotification($req_info['phone'], $msg);
                sendWhatsAppNotification($req_info['phone'], $msg);
                sendEmailNotification($req_info['email'], "Donor Withdrawal Update", $msg);
                
                $admin_msg = "<b>⚠️ SOS WITHDRAWAL</b>\n\n" .
                             "<b>Donor:</b> $donor_name\n" .
                             "<b>Alert ID:</b> #$alert_id\n" .
                             "<b>Status:</b> Donor had to cancel. Seeking replacement.";
                sendTelegramNotification($admin_msg);
            }

            echo json_encode(["status" => "success", "message" => "Response withdrawn successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update response status."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request or unauthorized."]);
}
?>
