<?php
include 'db_connect.php';

try {
    // Disable foreign key checks to allow truncation if needed
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "Clearing preloaded_students...\n";
    $conn->exec("TRUNCATE TABLE preloaded_students");

    echo "Clearing SOS-related history (responses, tracking, alerts)...\n";
    $conn->exec("TRUNCATE TABLE sos_responses");
    $conn->exec("TRUNCATE TABLE tracking");
    $conn->exec("TRUNCATE TABLE sos_alerts");
    $conn->exec("TRUNCATE TABLE donation_history");

    echo "Removing all donor users (keeping admins)...\n";
    $conn->exec("DELETE FROM users WHERE role = 'donor'");

    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\nSUCCESS: All old donor and student data has been removed. You can now upload your new CSV.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
