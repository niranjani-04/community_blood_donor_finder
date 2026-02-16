<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit("Unauthorized");
}

$log_file = '../backend/notification_log.txt';
if (!file_exists($log_file)) {
    echo '<div class="text-secondary p-4">No log file found.</div>';
    exit();
}

// Read last 100 lines
$lines = file($log_file);
$last_lines = array_slice($lines, -100);
$last_lines = array_reverse($last_lines);

foreach ($last_lines as $line) {
    $class = "text-white-50";
    if (strpos($line, 'FAILED') !== false || strpos($line, 'REJECTED') !== false || strpos($line, 'ERROR') !== false) {
        $class = "text-danger fw-bold";
    } elseif (strpos($line, 'SUCCESS') !== false || strpos($line, 'Sent to') !== false) {
        $class = "text-success";
    } elseif (strpos($line, 'INFOBIP') !== false || strpos($line, 'FAST2SMS') !== false || strpos($line, 'TWILIO') !== false) {
        $class = "text-info";
    }
    
    echo '<div class="py-1 border-bottom border-white border-opacity-5 ' . $class . '" style="font-family: monospace; font-size: 0.85rem;">' . htmlspecialchars($line) . '</div>';
}
?>
