<?php
include '../backend/db_connect.php';

try {
    // Check for the header row mistakenly added as data
    $stmt = $conn->prepare("SELECT * FROM preloaded_students WHERE register_number = '0' OR name = 'NAME' OR blood_group = 'BLOOD_GROUP'");
    $stmt->execute();
    $badRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($badRows) > 0) {
        echo "Found " . count($badRows) . " bad row(s). Deleting...\n";
        $del = $conn->prepare("DELETE FROM preloaded_students WHERE register_number = '0' OR name = 'NAME' OR blood_group = 'BLOOD_GROUP'");
        $del->execute();
        echo "Bad rows deleted.\n";
    } else {
        echo "No header rows found in data.\n";
    }

    // List some real students to confirm
    $stmt = $conn->query("SELECT * FROM preloaded_students LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
