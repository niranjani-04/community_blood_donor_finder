<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $donor_id = $_SESSION['user_id'];
    $alert_id = $_POST['alert_id'];

    // Check if already accepted
    // 1. CHECK IF DONOR BLOOD GROUP MATCHES ALERT
    $u_stmt = $conn->prepare("SELECT name, blood_group, availability_status, is_activated FROM users WHERE user_id = ?");
    $u_stmt->execute([$donor_id]);
    $donor = $u_stmt->fetch(PDO::FETCH_ASSOC);

    // SECURITY: Only activated donors can accept SOS alerts
    if ($donor['is_activated'] == 0) {
        echo json_encode(["status" => "error", "message" => "Security Alert: Your donor account must be activated by an administrator before you can respond to emergencies."]);
        exit;
    }

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
        
        // Count total responders now
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM sos_responses WHERE alert_id = ? AND status = 'accepted'");
        $count_stmt->execute([$alert_id]);
        $total_responders = $count_stmt->fetchColumn();

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
            $ordinal = ($total_responders == 1) ? "First" : "Additional";
            $msg = "HEARTBEAT: $ordinal Donor {$donor['name']} ({$donor['blood_group']}) has accepted your SOS request for {$req_info['blood_group']}! They are on their way. Total responders: $total_responders. Track here: http://localhost/community/track.php?alert_id=$alert_id";
            
            // Notify Requester
            sendSMSNotification($req_info['phone'], $msg);
            sendWhatsAppNotification($req_info['phone'], $msg);
            sendEmailNotification($req_info['email'], "$ordinal Donor on the way!", $msg);
            
            // Notify Admin (Broadcasting to Telegram)
            $admin_msg = "<b>📢 SOS RESPONSE #$total_responders</b>\n\n" .
                         "<b>Donor:</b> {$donor['name']} ({$donor['blood_group']})\n" .
                         "<b>Requester:</b> {$req_info['name']}\n" .
                         "<b>Status:</b> Intercepting SOS Alert #$alert_id";
            sendTelegramNotification($admin_msg);
        }
        
        // --- LAUNCH ASYNC ACCEPT WORKER ---
        $php_path = "C:\\xampp\\php\\php.exe";
        $worker_path = dirname(__FILE__) . "\\sos_accept_worker.php";
        $cmd = "start /B $php_path $worker_path $alert_id $donor_id";
        pclose(popen($cmd, "r"));

        $success_msg = ($total_responders == 1) 
            ? "Request Accepted! You are the first responder. Redirecting to tracker..." 
            : "Request Accepted! You are donor #$total_responders joining the response. Redirecting...";

        echo json_encode(["status" => "success", "message" => $success_msg, "alert_id" => $alert_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error accepting request."]);
    }
}
?>
