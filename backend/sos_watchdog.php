<?php
/**
 * SOS WATCHDOG
 * Automatically flags donors who stop updating their location.
 * This can be run via CLI or triggered by periodic requests.
 */

// If triggered via browser/AJAX, we need some security or just let it run (it's non-destructive, just flags stale)
if (php_sapi_name() !== 'cli' && (!isset($_GET['token']) || $_GET['token'] !== 'watchdog_secret_123')) {
    // In a real app, use a more secure token or restricted IP
    // die("Unauthorized access.");
}

require_once 'db_connect.php';
require_once 'notification_helper.php';

$stale_threshold = 300; // 5 minutes in seconds
$log_file = dirname(__FILE__) . '/watchdog_log.txt';
$ts = date('Y-m-d H:i:s');

try {
    // 1. Find accepted responses where the donor hasn't sent a GPS tracking ping for > 5 mins
    //    We check the tracking table for the last ping - if none in 5 mins, flag as stale.
    //    Fallback: if no tracking row at all AND accepted_at was > 5 mins ago, also stale.
    $sql = "SELECT r.response_id, r.alert_id, r.donor_id, u.name as donor_name,
                   req.name as requester_name, req.phone as requester_phone, req.email as requester_email,
                   (SELECT MAX(t.updated_at) FROM tracking t WHERE t.donor_id = r.donor_id AND t.alert_id = r.alert_id) as last_ping
            FROM sos_responses r
            JOIN users u ON r.donor_id = u.user_id
            JOIN sos_alerts s ON r.alert_id = s.alert_id
            JOIN users req ON s.requester_id = req.user_id
            WHERE r.status = 'accepted'
            AND (
                /* Has tracking entries but last one is old */
                (SELECT MAX(t.updated_at) FROM tracking t WHERE t.donor_id = r.donor_id AND t.alert_id = r.alert_id) < (NOW() - INTERVAL 5 MINUTE)
                OR
                /* No tracking entries at all and accepted > 5 mins ago */
                ((SELECT COUNT(*) FROM tracking t WHERE t.donor_id = r.donor_id AND t.alert_id = r.alert_id) = 0
                 AND r.accepted_at < (NOW() - INTERVAL 5 MINUTE))
            )";
    
    $stmt = $conn->query($sql);
    $stale_donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $flagged_count = 0;
    foreach ($stale_donors as $d) {
        $alert_id = $d['alert_id'];
        $donor_id = $d['donor_id'];
        $donor_name = $d['donor_name'];

        // 2. Mark as Stale
        $upd = $conn->prepare("UPDATE sos_responses SET status = 'stale' WHERE response_id = ?");
        if ($upd->execute([$d['response_id']])) {
            $flagged_count++;
            
            // 3. Notify Requester
            $msg = "🚨 AUTOMATIC ALERT: Donor $donor_name (Alert #$alert_id) has become unresponsive/stopped tracking. We recommend checking for other donors immediately.";
            
            sendSMSNotification($d['requester_phone'], $msg);
            sendWhatsAppNotification($d['requester_phone'], $msg);
            sendEmailNotification($d['requester_email'], "URGENT: Donor Status Alert", $msg);
            
            // 4. Telegram for Admin
            $admin_msg = "<b>🚨 AUTO-STALE DETECTED</b>\n\n" .
                         "<b>Donor:</b> $donor_name\n" .
                         "<b>Alert ID:</b> #$alert_id\n" .
                         "<b>Status:</b> Flagged as STALE (No GPS updates for 5 mins). Alert re-opened for others.";
            sendTelegramNotification($admin_msg);
            
            file_put_contents($log_file, "[$ts] Flagged Donor #$donor_id as STALE for Alert #$alert_id\n", FILE_APPEND);
        }
    }

    if ($flagged_count > 0) {
        echo json_encode(["status" => "success", "flagged" => $flagged_count, "message" => "Watchdog processed $flagged_count stale responses."]);
    } else {
         // No output if running via CLI usually, but for debug:
         // echo "No stale donors found.";
    }

} catch (Exception $e) {
    file_put_contents($log_file, "[$ts] Watchdog Error: " . $e->getMessage() . "\n", FILE_APPEND);
    if (php_sapi_name() !== 'cli') echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
