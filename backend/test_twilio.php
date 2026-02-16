<?php
include 'db_connect.php';
include 'notification_helper.php';

echo "<h2>Twilio WhatsApp Test</h2>";

$testNumber = "7708890703"; // Using the number seen in logs
$testMessage = "Test message from Blood SOS Twilio Integration at " . date('H:i:s');

echo "Attempting to send WhatsApp message to $testNumber...<br>";

if (sendWhatsAppNotification($testNumber, $testMessage)) {
    echo "<p style='color: green;'>✅ WhatsApp process completed. Check your Twilio logs or the registered phone.</p>";
} else {
    echo "<p style='color: red;'>❌ WhatsApp failed. Check the server error logs.</p>";
}

echo "<h3>Notification Log (Last 5 lines):</h3>";
$log = file('notification_log.txt');
$lastLines = array_slice($log, -5);
echo "<pre>" . implode("", $lastLines) . "</pre>";
?>
