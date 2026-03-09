<?php
include 'db_connect.php';

try {
    // Disable foreign key checks to allow truncation
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    echo "Clearing SOS alerts and associated data...\n";
    
    // Clear SOS responses
    $conn->exec("TRUNCATE TABLE sos_responses");
    echo "- Truncated sos_responses\n";
    
    // Clear tracking history
    $conn->exec("TRUNCATE TABLE tracking");
    echo "- Truncated tracking\n";
    
    // Clear SOS alerts
    $conn->exec("TRUNCATE TABLE sos_alerts");
    echo "- Truncated sos_alerts\n";
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\nSUCCESS: All SOS alerts and tracking data have been removed.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
