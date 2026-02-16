<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require dirname(__FILE__) . '/../libs/PHPMailer/Exception.php';
require dirname(__FILE__) . '/../libs/PHPMailer/PHPMailer.php';
require dirname(__FILE__) . '/../libs/PHPMailer/SMTP.php';

// --- CONFIGURATION ---
// IMPORTANT: For production, set these as Environment Variables.
// Local fallbacks are provided for development but must be set securely in production.

// Gmail Configuration
define('SMTP_USER', getenv('SMTP_USER') ?: 'niranjani7890@gmail.com'); 
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'mmff wisd iqtb ayoz'); 

// Fast2SMS API Key
define('FAST2SMS_KEY', getenv('FAST2SMS_KEY') ?: '');

// Twilio WhatsApp Configuration
define('TWILIO_SID', getenv('TWILIO_SID') ?: '');
define('TWILIO_TOKEN', getenv('TWILIO_TOKEN') ?: '');
define('TWILIO_WHATSAPP_FROM', getenv('TWILIO_WHATSAPP_FROM') ?: '');

// Infobip Configuration
define('INFOBIP_API_KEY', getenv('INFOBIP_API_KEY') ?: '');
define('INFOBIP_BASE_URL', getenv('INFOBIP_BASE_URL') ?: '');

// Telegram Configuration
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: ''); 
define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: ''); 

// FCM Configuration
define('FCM_SERVER_KEY', getenv('FCM_SERVER_KEY') ?: ''); 
// ----------------------

function sendEmailNotification($toEmail, $subject, $body) {
    if (SMTP_USER === 'your-email@gmail.com') {
        // Fallback to log if not configured
        error_log("Email to $toEmail NOT SENT: Gmail not configured in notification_helper.php");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom(SMTP_USER, 'Blood SOS Notifications');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        $log_file = dirname(__FILE__) . '/notification_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[$timestamp] EMAIL SENT SUCCESS TO: $toEmail\n", FILE_APPEND);
        return true;
    } catch (Exception $e) {
        $log_file = dirname(__FILE__) . '/notification_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[$timestamp] EMAIL ERROR TO: $toEmail | ERROR: {$mail->ErrorInfo}\n", FILE_APPEND);
        return false;
    }
}

/**
 * Send an SMS notification via Fast2SMS
 */
function sendSMSNotification($toPhone, $message) {
    // 1. LOG FOR RECORD KEEPING
    $log_file = dirname(__FILE__) . '/notification_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] SMS TO: $toPhone | MSG: $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);

    // 2. CHECK IF API KEY IS SET
    if (empty(FAST2SMS_KEY)) {
        return true; 
    }

    // 3. SEND REAL SMS VIA FAST2SMS
    $fields = array(
        "message" => $message,
        "language" => "english",
        "route" => "q", // "q" for Quick SMS (Best for bypass DLT in some cases)
        "numbers" => $toPhone,
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($fields),
        CURLOPT_HTTPHEADER => array(
            "authorization: " . FAST2SMS_KEY,
            "accept: */*",
            "cache-control: no-cache",
            "content-type: application/json"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err || strpos($response, '999') !== false) {
        file_put_contents($log_file, "[$timestamp] FAST2SMS FAILED. Attempting Infobip Fallback...\n", FILE_APPEND);
        // Fallback to Infobip
        return sendInfobipSMS($toPhone, $message);
    } else {
        file_put_contents($log_file, "[$timestamp] FAST2SMS RESPONSE: $response\n", FILE_APPEND);
        return true;
    }
}

/**
 * Send SMS via Infobip
 */
function sendInfobipSMS($toPhone, $message) {
    if (empty(INFOBIP_API_KEY)) {
        return sendTwilioSMS($toPhone, $message); // Final fallback to Twilio
    }

    $log_file = dirname(__FILE__) . '/notification_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    
    $to = (strpos($toPhone, '91') === 0 ? $toPhone : "91" . $toPhone);
    $url = INFOBIP_BASE_URL . "/sms/2/text/advanced";
    
    $data = array(
        'messages' => array(
            array(
                'destinations' => array(array('to' => $to)),
                'from' => 'BHC Blood',
                'text' => $message
            )
        )
    );

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Authorization: App " . INFOBIP_API_KEY,
        "Content-Type: application/json",
        "Accept: application/json"
    ));
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    $res_data = json_decode($response, true);
    $is_rejected = false;
    if (isset($res_data['messages'][0]['status']['groupName'])) {
        $status = $res_data['messages'][0]['status']['groupName'];
        if ($status === 'REJECTED' || $status === 'FAILED') {
            $is_rejected = true;
        }
    }

    if ($err || $is_rejected) {
        file_put_contents($log_file, "[$timestamp] INFOBIP FAILED/REJECTED: " . ($err ?: $response) . ". Attempting Twilio Fallback...\n", FILE_APPEND);
        return sendTwilioSMS($toPhone, $message);
    } else {
        file_put_contents($log_file, "[$timestamp] INFOBIP SUCCESS: $response\n", FILE_APPEND);
        return true;
    }
}

/**
 * Fallback SMS via Twilio
 */
function sendTwilioSMS($toPhone, $message) {
    if (empty(TWILIO_SID)) {
         return false;
    }
    $log_file = dirname(__FILE__) . '/notification_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    
    $to = (strpos($toPhone, '+') === 0 ? $toPhone : "+91" . $toPhone);
    $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_SID . "/Messages.json";
    $data = array('From' => '+18149830541', 'To' => $to, 'Body' => $message); // Use your Twilio SMS number

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($curl);
    curl_close($curl);
    
    file_put_contents($log_file, "[$timestamp] TWILIO SMS FALLBACK RESPONSE: $response\n", FILE_APPEND);
    return true;
}
/**
 * Send a real WhatsApp message via Twilio
 */
function sendWhatsAppNotification($toPhone, $message) {
    // 1. LOG FOR RECORD KEEPING
    $log_file = dirname(__FILE__) . '/notification_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] WHATSAPP (Twilio) TO: $toPhone | MSG: $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);

    // 2. CHECK IF TWILIO IS CONFIGURED
    if (empty(TWILIO_SID)) {
        return true; // Just logged if not configured
    }

    // 3. PREPARE DATA
    // Format number for WhatsApp
    $to = "whatsapp:" . (strpos($toPhone, '+') === 0 ? $toPhone : "+91" . $toPhone);
    $from = "whatsapp:" . TWILIO_WHATSAPP_FROM;

    $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_SID . "/Messages.json";
    $data = array(
        'From' => $from,
        'To' => $to,
        'Body' => $message
    );

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    // LOG FULL RESPONSE FOR DEBUGGING
    file_put_contents($log_file, "[$timestamp] TWILIO RESPONSE: " . $response . "\n", FILE_APPEND);

    if ($err) {
        error_log("Twilio WhatsApp Error: " . $err);
        return false;
    } else {
        $res = json_decode($response, true);
        if (isset($res['error_message'])) {
            error_log("Twilio API Error: " . $res['error_message']);
            return false;
        }
        return true;
    }
}

/**
 * Send a message via Telegram Bot API (FREE)
 */
function sendTelegramNotification($message) {
    // 1. LOG FOR RECORD KEEPING
    $log_file = dirname(__FILE__) . '/notification_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] TELEGRAM BROADCAST | MSG: $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);

    // 2. CHECK IF TELEGRAM IS CONFIGURED
    if (empty(TELEGRAM_BOT_TOKEN)) {
        return true; // Just logged if not configured
    }

    // 3. SEND REQUEST
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        file_put_contents($log_file, "[$timestamp] TELEGRAM CURL ERROR: $err\n", FILE_APPEND);
        return false;
    } else {
        file_put_contents($log_file, "[$timestamp] TELEGRAM RESPONSE: $response\n", FILE_APPEND);
        $res = json_decode($response, true);
        if (!$res['ok']) {
            return false;
        }
        return true;
    }
}

/**
 * Send a web push notification via FCM (FREE)
 */
function sendPushNotification($toToken, $title, $body) {
    if (empty(FCM_SERVER_KEY) || empty($toToken)) {
        return false;
    }

    $url = 'https://fcm.googleapis.com/fcm/send';
    
    $payload = [
        'to' => $toToken,
        'notification' => [
            'title' => $title,
            'body' => $body,
            'icon' => 'https://yourprojecturl.com/assets/img/icon.png',
            'click_action' => 'https://yourprojecturl.com/index.php'
        ],
        'priority' => 'high'
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: key=' . FCM_SERVER_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($curl, CURLOPT_TIMEOUT, 10); // 10 second timeout
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("FCM Error: " . $err);
        return false;
    }
    return true;
}
