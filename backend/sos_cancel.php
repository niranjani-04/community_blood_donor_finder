<?php
session_start();
include 'db_connect.php';
include 'notification_helper.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $alert_id = $_POST['alert_id'];

    try {
        // 1. Verify user owns the alert or is admin
        $stmt = $conn->prepare("SELECT requester_id, status FROM sos_alerts WHERE alert_id = ?");
        $stmt->execute([$alert_id]);
        $alert = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$alert) {
            echo json_encode(["status" => "error", "message" => "Alert not found."]);
            exit;
        }

        if ($alert['requester_id'] != $user_id && $_SESSION['role'] != 'admin') {
            echo json_encode(["status" => "error", "message" => "Unauthorized to cancel this alert."]);
            exit;
        }

        if ($alert['status'] == 'cancelled') {
            echo json_encode(["status" => "success", "message" => "Alert is already cancelled."]);
            exit;
        }

        // 2. Update alert status to 'cancelled'
        $update = $conn->prepare("UPDATE sos_alerts SET status = 'cancelled' WHERE alert_id = ?");
        if ($update->execute([$alert_id])) {
            
            // 3. Notify ANY accepted donors that it's cancelled
            $donors = $conn->prepare("SELECT u.phone, u.name FROM users u 
                                    JOIN sos_responses r ON u.user_id = r.donor_id 
                                    WHERE r.alert_id = ? AND r.status = 'accepted'");
            $donors->execute([$alert_id]);
            
            while($d = $donors->fetch(PDO::FETCH_ASSOC)) {
                $cancel_msg = "HEARTBEAT: The SOS alert #$alert_id you accepted has been cancelled by the requester. Thank you for your readiness!";
                sendSMSNotification($d['phone'], $cancel_msg);
                sendWhatsAppNotification($d['phone'], $cancel_msg);
            }

            // 4. Update all active responses to cancelled/completed status
            // No specific enum for response cancellation in original database.sql, we'll keep it simple for now or skip.
            
            echo json_encode(["status" => "success", "message" => "Emergency alert cancelled successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to cancel alert."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request or unauthorized."]);
}
?>
