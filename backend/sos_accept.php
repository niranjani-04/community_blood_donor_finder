<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $donor_id = $_SESSION['user_id'];
    $alert_id = $_POST['alert_id'];

    // Check if already accepted
    // 1. CHECK IF DONOR BLOOD GROUP MATCHES ALERT
    $u_stmt = $conn->prepare("SELECT name, blood_group, availability_status FROM users WHERE user_id = ?");
    $u_stmt->execute([$donor_id]);
    $donor = $u_stmt->fetch(PDO::FETCH_ASSOC);

    $a_stmt = $conn->prepare("SELECT blood_group FROM sos_alerts WHERE alert_id = ?");
    $a_stmt->execute([$alert_id]);
    $alert = $a_stmt->fetch(PDO::FETCH_ASSOC);

    // Validate Matching
    if ($donor['blood_group'] != $alert['blood_group']) {
        echo json_encode(["status" => "error", "message" => "Error: Your blood group (" . $donor['blood_group'] . ") does not match the requested group (" . $alert['blood_group'] . ")."]);
        exit;
    }
    
    // Validate Availability
    if ($donor['availability_status'] != 'Available') {
        echo json_encode(["status" => "error", "message" => "Error: You are not marked as Available."]);
        exit;
    }

    // 3. CHECK IF ALREADY ACCEPTED OR IF DONOR IS REQUESTER
    $stmt_check_own = $conn->prepare("SELECT requester_id FROM sos_alerts WHERE alert_id = ?");
    $stmt_check_own->execute([$alert_id]);
    $alert_data = $stmt_check_own->fetch(PDO::FETCH_ASSOC);
    if ($alert_data && $alert_data['requester_id'] == $donor_id) {
        echo json_encode(["status" => "error", "message" => "You cannot accept your own SOS alert."]);
        exit;
    }

    $check = $conn->prepare("SELECT * FROM sos_responses WHERE alert_id = ? AND donor_id = ?");
    $check->execute([$alert_id, $donor_id]);
    if ($check->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "You have already accepted this request."]);
        exit;
    }

    // Insert Response
    $stmt = $conn->prepare("INSERT INTO sos_responses (alert_id, donor_id, status) VALUES (?, ?, 'accepted')");
    if ($stmt->execute([$alert_id, $donor_id])) {
        
        // --- NOTIFY REQUESTER & ADMIN ---
        include 'notification_helper.php';
        
        // Fetch Requester and Admin details
        $req_stmt = $conn->prepare("SELECT u.name, u.phone, u.email, s.blood_group 
                                   FROM sos_alerts s 
                                   JOIN users u ON s.requester_id = u.user_id 
                                   WHERE s.alert_id = ?");
        $req_stmt->execute([$alert_id]);
        $req_info = $req_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($req_info) {
            $msg = "HEARTBEAT: Donor {$donor['name']} ({$donor['blood_group']}) has accepted your SOS request for {$req_info['blood_group']}! They are on their way. Track them here: http://localhost/community/track.php?alert_id=$alert_id";
            
            // Notify Requester
            sendSMSNotification($req_info['phone'], $msg);
            sendWhatsAppNotification($req_info['phone'], $msg);
            sendEmailNotification($req_info['email'], "Donor on the way!", $msg);
            
            // Notify Admin (Broadcasting to Telegram)
            $admin_msg = "<b>📢 SOS ACCEPTED</b>\n\n" .
                         "<b>Donor:</b> {$donor['name']} ({$donor['blood_group']})\n" .
                         "<b>Requester:</b> {$req_info['name']}\n" .
                         "<b>Status:</b> On the way to help!";
            sendTelegramNotification($admin_msg);
        }
        
        echo json_encode(["status" => "success", "message" => "Request Accepted! Redirecting to tracker...", "alert_id" => $alert_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error accepting request."]);
    }
}
?>
