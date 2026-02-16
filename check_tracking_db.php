<?php
include 'backend/db_connect.php';

$tables = ['sos_alerts', 'sos_responses', 'tracking', 'users'];

foreach ($tables as $table) {
    echo "\n--- Table: $table ---\n";
    try {
        $stmt = $conn->query("DESCRIBE $table");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $row) {
            echo "{$row['Field']} ({$row['Type']})\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
