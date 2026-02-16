<?php
/**
 * BACKGROUND NOTIFICATION WORKER
 * This script is called by sos_create.php to handle the slow part (sending messages/emails)
 * so the requester doesn't have to wait.
 */

if (php_sapi_name() !== 'cli' && !isset($_GET['secret_key'])) {
    die("Direct access not allowed.");
}

// Get alert_id from arguments
$alert_id = $argv[1] ?? $_GET['alert_id'] ?? null;
if (!$alert_id) die("No alert_id provided.");

require_once 'db_connect.php';
require_once 'notification_helper.php';

$log_file = dirname(__FILE__) . '/notification_log.txt';
$ts = date('Y-m-d H:i:s');
file_put_contents($log_file, "[$ts] ASYNC WORKER STARTED for Alert #$alert_id\n", FILE_APPEND);

// 1. Fetch Alert Details
$stmt = $conn->prepare("SELECT * FROM sos_alerts WHERE alert_id = ?");
$stmt->execute([$alert_id]);
$alert = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alert) {
    file_put_contents($log_file, "[$ts] ASYNC ERROR: Alert #$alert_id not found.\n", FILE_APPEND);
    exit();
}

$blood_group = $alert['blood_group'];
$requester_id = $alert['requester_id'];
$location_name = $alert['location_name'];
$latitude = $alert['latitude'];
$longitude = $alert['longitude'];

// 2. Geocode if it was skipped or generic (optional - let's keep it if not already done)
if (strpos($location_name, 'Emergency Location at') === 0) {
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$latitude&lon=$longitude&zoom=18&addressdetails=1";
    $opts = [
        "http" => ["header" => "User-Agent: BloodSOSApp/1.0\r\n", "timeout" => 5]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $result = json_decode($response, true);
        if (isset($result['display_name'])) {
            $location_name = $result['display_name'];
            // Update DB with cleaner name
            $conn->prepare("UPDATE sos_alerts SET location_name = ? WHERE alert_id = ?")->execute([$location_name, $alert_id]);
        }
    }
}

// 3. Fetch Requester info to exclude them
$req_stmt = $conn->prepare("SELECT phone, email FROM users WHERE user_id = ?");
$req_stmt->execute([$requester_id]);
$requester_info = $req_stmt->fetch(PDO::FETCH_ASSOC);
$my_phone = ltrim(preg_replace('/[^0-9]/', '', $requester_info['phone'] ?? ''), '0');
if (strlen($my_phone) > 10) $my_phone = substr($my_phone, -10);
$my_email = strtolower(trim($requester_info['email'] ?? ''));

// 4. Fetch potential donors
$stmt_users = $conn->prepare("SELECT name, email, phone, fcm_token FROM users WHERE role = 'donor' AND TRIM(blood_group) = ? AND user_id != ? AND availability_status = 'Available'");
$stmt_users->execute([$blood_group, $requester_id]);
$active_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

$stmt_registry = $conn->prepare("SELECT name, email, phone FROM preloaded_students WHERE TRIM(blood_group) = ?");
$stmt_registry->execute([$blood_group]);
$registry_students = $stmt_registry->fetchAll(PDO::FETCH_ASSOC);

// 5. De-duplicate and filter
$final_recipients = [];
$seen_phones = [];
$seen_emails = [];

foreach (array_merge($active_users, $registry_students) as $person) {
    $raw_phone = $person['phone'] ?? '';
    $clean_phone = ltrim(preg_replace('/[^0-9]/', '', (string)$raw_phone), '0');
    if (strlen($clean_phone) > 10) $clean_phone = substr($clean_phone, -10);
    $email = strtolower(trim($person['email'] ?? ''));

    if ($clean_phone === $my_phone || (!empty($email) && $email === $my_email)) continue;

    $is_duplicate_phone = !empty($clean_phone) && in_array($clean_phone, $seen_phones);
    $is_duplicate_email = !empty($email) && in_array($email, $seen_emails);

    if (!$is_duplicate_phone && !$is_duplicate_email) {
        if (!empty($clean_phone)) { $person['formatted_phone'] = $clean_phone; $seen_phones[] = $clean_phone; }
        if (!empty($email)) { $seen_emails[] = $email; }
        $final_recipients[] = $person;
    }
}

// 6. Send notifications
$notification_count = 0;
foreach ($final_recipients as $donor) {
    try {
        $msg = "URGENT SOS: Blood Group $blood_group needed at $location_name. Please check your dashboard to help!";
        $target_phone = $donor['formatted_phone'];

        sendSMSNotification($target_phone, $msg);
        sendWhatsAppNotification($target_phone, $msg);
        
        $email_body = "<h3>Urgent Blood Request</h3>
                      <p>Hello <b>" . htmlspecialchars($donor['name']) . "</b>,</p>
                      <p>There is an urgent request for your blood group (<b>$blood_group</b>) near <b>" . htmlspecialchars($location_name) . "</b>.</p>
                      <p>Please respond to this emergency if possible.</p>
                      <hr>
                      <p><small>Automated alert from Bishop Heber College Blood Finder.</small></p>";
        sendEmailNotification($donor['email'], "URGENT: $blood_group Blood Needed!", $email_body);
        
        if (isset($donor['fcm_token']) && !empty($donor['fcm_token'])) {
            sendPushNotification($donor['fcm_token'], "🚨 URGENT BLOOD SOS!", "Group $blood_group needed at $location_name");
        }
        $notification_count++;
        
        // Small sleep to prevent rate limits
        usleep(50000); // 50ms
    } catch (Exception $e) {
        file_put_contents($log_file, "[$ts] Notification Loop Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// 7. Telegram Broadcast
try {
    $telegram_msg = "<b>🚨 URGENT BLOOD SOS 🚨</b>\n\n" .
                    "<b>Blood Group:</b> $blood_group\n" .
                    "<b>Location:</b> " . htmlspecialchars($location_name) . "\n\n" .
                    "Please help if you can! <a href='http://localhost/community/index.php'>Open Dashboard</a>";
    sendTelegramNotification($telegram_msg);
} catch(Exception $te) {}

file_put_contents($log_file, "[$ts] ASYNC WORKER FINISHED. Sent to $notification_count donors.\n", FILE_APPEND);
?>
