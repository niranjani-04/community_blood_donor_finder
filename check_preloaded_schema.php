<?php
include 'backend/db_connect.php';
$table = 'preloaded_students';
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
?>
