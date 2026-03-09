<?php
/**
 * BACKGROUND NOTIFICATION WORKER FOR ACCEPTANCE
 * This script is called by sos_accept.php to notify other donors
 * that someone has accepted the request, but they can still help.
 */

if (php_sapi_name() !== 'cli' && !isset($_GET['secret_key'])) {
    die("Direct access not allowed.");
}

$alert_id = $argv[1] ?? $_GET['alert_id'] ?? null;
$accepting_donor_id = $argv[2] ?? $_GET['accepting_donor_id'] ?? null;

if (!$alert_id || !$accepting_donor_id) die("Missing parameters.");

require_once 'db_connect.php';
require_once 'notification_helper.php';

$log_file = dirname(__FILE__) . '/notification_log.txt';
$ts = date('Y-m-d H:i:s');
file_put_contents($log_file, "[$ts] ASYNC ACCEPT WORKER STARTED for Alert #$alert_id by Donor #$accepting_donor_id\n", FILE_APPEND);

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

// 2. Fetch Accepting Donor Details
$donor_stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$donor_stmt->execute([$accepting_donor_id]);
$accepting_donor = $donor_stmt->fetch(PDO::FETCH_ASSOC);
$accepting_donor_name = $accepting_donor ? $accepting_donor['name'] : "A donor";

// 3. Fetch potential donors (excluding requester and accepting donor)
$stmt_users = $conn->prepare("SELECT user_id, name, email, phone, fcm_token FROM users WHERE role = 'donor' AND TRIM(blood_group) = ? AND user_id != ? AND user_id != ? AND availability_status = 'Available'");
$stmt_users->execute([$blood_group, $requester_id, $accepting_donor_id]);
$active_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// For preloaded students, there is no user_id, we can only exclude by phone/email later if necessary
$stmt_registry = $conn->prepare("SELECT name, email, phone FROM preloaded_students WHERE TRIM(blood_group) = ?");
$stmt_registry->execute([$blood_group]);
$registry_students = $stmt_registry->fetchAll(PDO::FETCH_ASSOC);

// Get requester info and accepting donor info to exclude them from notifications based on phone/email
$req_stmt = $conn->prepare("SELECT phone, email FROM users WHERE user_id IN (?, ?)");
$req_stmt->execute([$requester_id, $accepting_donor_id]);
$exclude_infos = $req_stmt->fetchAll(PDO::FETCH_ASSOC);

$exclude_phones = [];
$exclude_emails = [];
foreach ($exclude_infos as $info) {
    $clean_phone = ltrim(preg_replace('/[^0-9]/', '', (string)($info['phone'] ?? '')), '0');
    if (strlen($clean_phone) > 10) $clean_phone = substr($clean_phone, -10);
    if (!empty($clean_phone)) $exclude_phones[] = $clean_phone;
    
    $email = strtolower(trim($info['email'] ?? ''));
    if (!empty($email)) $exclude_emails[] = $email;
}

// 4. De-duplicate and filter
$final_recipients = [];
$seen_phones = $exclude_phones;
$seen_emails = $exclude_emails;

foreach (array_merge($active_users, $registry_students) as $person) {
    $raw_phone = $person['phone'] ?? '';
    $clean_phone = ltrim(preg_replace('/[^0-9]/', '', (string)$raw_phone), '0');
    if (strlen($clean_phone) > 10) $clean_phone = substr($clean_phone, -10);
    $email = strtolower(trim($person['email'] ?? ''));

    $is_duplicate_phone = !empty($clean_phone) && in_array($clean_phone, $seen_phones);
    $is_duplicate_email = !empty($email) && in_array($email, $seen_emails);

    if (!$is_duplicate_phone && !$is_duplicate_email) {
        if (!empty($clean_phone)) { $person['formatted_phone'] = $clean_phone; $seen_phones[] = $clean_phone; }
        if (!empty($email)) { $seen_emails[] = $email; }
        $final_recipients[] = $person;
    }
}

// 5. Send notifications
$notification_count = 0;
foreach ($final_recipients as $donor) {
    try {
        $msg = "UPDATE: $accepting_donor_name has accepted the SOS for Blood Group $blood_group at $location_name. You can still accept to help in case they are not available!";
        $target_phone = $donor['formatted_phone'] ?? '';

        if (!empty($target_phone)) {
            sendSMSNotification($target_phone, $msg);
            sendWhatsAppNotification($target_phone, $msg);
        }
        
        if (!empty($donor['email'])) {
            $email_body = "<h3>Update on Blood Request</h3>
                          <p>Hello <b>" . htmlspecialchars($donor['name']) . "</b>,</p>
                          <p><b>$accepting_donor_name</b> has just accepted the urgent request for your blood group (<b>$blood_group</b>) near <b>" . htmlspecialchars($location_name) . "</b>.</p>
                          <p>However, emergencies often require secondary backups. If you are still available, please keep your dashboard open or accept the request to assist if the primary donor becomes unavailable.</p>
                          <hr>
                          <p><small>Automated alert from Bishop Heber College Blood Finder.</small></p>";
            sendEmailNotification($donor['email'], "UPDATE: Blood Requested ($blood_group) Accepted", $email_body);
        }
        
        if (isset($donor['fcm_token']) && !empty($donor['fcm_token'])) {
            sendPushNotification($donor['fcm_token'], "🗣️ SOS Update: Donor responded", "$accepting_donor_name accepted the SOS. You can still help!");
        }
        $notification_count++;
        
        // Small sleep to prevent rate limits
        usleep(50000); // 50ms
    } catch (Exception $e) {
        file_put_contents($log_file, "[$ts] Notification Loop Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

file_put_contents($log_file, "[$ts] ASYNC ACCEPT WORKER FINISHED. Sent to $notification_count donors.\n", FILE_APPEND);
?>
