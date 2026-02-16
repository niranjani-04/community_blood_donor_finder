<?php
include '../backend/db_connect.php';

try {
    $stmt = $conn->query("DESCRIBE preloaded_students");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in preloaded_students: " . implode(", ", $columns) . "\n";
    
    // Check if batch_id is present
    if (!in_array('batch_id', $columns)) {
        echo "MISSING: batch_id\n";
        // Try to add it
        $conn->exec("ALTER TABLE preloaded_students ADD COLUMN IF NOT EXISTS batch_id VARCHAR(50) DEFAULT NULL");
        echo "Attempted to add batch_id.\n";
    }

    if (!in_array('age', $columns)) {
        $conn->exec("ALTER TABLE preloaded_students ADD COLUMN IF NOT EXISTS age INT DEFAULT NULL");
        echo "Added age.\n";
    }
    
    if (!in_array('health_eligibility', $columns)) {
        $conn->exec("ALTER TABLE preloaded_students ADD COLUMN IF NOT EXISTS health_eligibility TEXT DEFAULT NULL");
         echo "Added health_eligibility.\n";
    }

    if (!in_array('address', $columns)) {
        $conn->exec("ALTER TABLE preloaded_students ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL");
        echo "Added address.\n";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
