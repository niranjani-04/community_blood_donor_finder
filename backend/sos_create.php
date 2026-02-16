<?php
session_start();
require_once 'db_connect.php';
require_once 'notification_helper.php';
error_reporting(E_ALL);
ini_set('display_errors', 0); // Log to file instead of screen
ini_set('log_errors', 1);
set_time_limit(180); // Increase timeout for bulk notifications
ob_start(); // Start buffering to catch accidental output

// Check if user is logged in
if (isset($_GET['debug_ping'])) {
    ob_end_clean();
    exit(json_encode(['status' => 'success', 'message' => 'DEBUG: Script is reachable']));
}
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $log_file = dirname(__FILE__) . '/notification_log.txt';
    $ts = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$ts] INCOMING SOS POST: " . json_encode($_POST) . "\n", FILE_APPEND);

    $requester_id = $_SESSION['user_id'];
    $blood_group = $_POST['blood_group'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    // SECURITY: SOS Rate Limiting (1 SOS per 5 minutes per user)
    $sos_check = $conn->prepare("SELECT COUNT(*) FROM sos_alerts WHERE requester_id = ? AND created_at > (NOW() - INTERVAL 5 MINUTE)");
    $sos_check->execute([$requester_id]);
    if ($sos_check->fetchColumn() > 0) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Rate Limit: You have already sent an SOS recently. Please wait a few minutes.']);
        exit();
    }
    
    // Fetch requester's own info to exclude them from notifications
    $req_stmt = $conn->prepare("SELECT phone, email FROM users WHERE user_id = ?");
    $req_stmt->execute([$requester_id]);
    $requester_info = $req_stmt->fetch(PDO::FETCH_ASSOC);
    $my_phone = ltrim(preg_replace('/[^0-9]/', '', $requester_info['phone'] ?? ''), '0');
    if (strlen($my_phone) > 10) $my_phone = substr($my_phone, -10);
    $my_email = strtolower(trim($requester_info['email'] ?? ''));

    $log_file = dirname(__FILE__) . '/notification_log.txt';
    $ts = date('Y-m-d H:i:s');
    // Simplified location name for instant response
    $location_name = "Emergency Location at ($latitude, $longitude)";
    
    file_put_contents($log_file, "[$ts] STEP 1: Starting SOS for $blood_group (Instant Mode)\n", FILE_APPEND);

    try {
        // --- PREVENT DUPLICATES ---
        $deactivate_sql = "UPDATE sos_alerts s 
                           JOIN users u ON s.requester_id = u.user_id 
                           SET s.status = 'cancelled' 
                           WHERE (s.requester_id = ? OR u.phone = ? OR u.phone = ? OR u.email = ?) 
                           AND s.status = 'active'";
        $deactivate_stmt = $conn->prepare($deactivate_sql);
        $deactivate_stmt->execute([$requester_id, $requester_info['phone'], $my_phone, $my_email]);

        $sql = "INSERT INTO sos_alerts (requester_id, blood_group, latitude, longitude, location_name) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        file_put_contents($log_file, "[$ts] STEP 2: Inserting into DB...\n", FILE_APPEND);
        if ($stmt->execute([$requester_id, $blood_group, $latitude, $longitude, $location_name])) {
            $alert_id = $conn->lastInsertId();
            file_put_contents($log_file, "[$ts] STEP 3: DB Success (ID: $alert_id). Launching Asynchronous Worker...\n", FILE_APPEND);

            // --- LAUNCH ASYNC WORKER (Windows compatible) ---
            $php_path = "C:\\xampp\\php\\php.exe";
            $worker_path = dirname(__FILE__) . "\\sos_worker.php";
            $cmd = "start /B $php_path $worker_path $alert_id";
            
            pclose(popen($cmd, "r"));
            file_put_contents($log_file, "[$ts] STEP 4: Worker command sent: $cmd\n", FILE_APPEND);

            ob_end_clean();
            echo json_encode([
                "status" => "success", 
                "message" => "Emergency dispatch started! Donors are being notified in the background.", 
                "alert_id" => $alert_id, 
                "location" => $location_name
            ]);
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(["status" => "error", "message" => "Critical Error: " . $e->getMessage()]);
    }
} else {
    ob_end_clean();
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
