<?php
require_once 'db_connect.php';

try {
    $stmt = $conn->query("SELECT hospital_id, name, latitude, longitude FROM hospitals");
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($hospitals) . " hospitals.\n";
    foreach ($hospitals as $h) {
        echo "ID: " . $h['hospital_id'] . " | Name: " . $h['name'] . " | Lat: " . ($h['latitude'] ?? 'NULL') . " | Lng: " . ($h['longitude'] ?? 'NULL') . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
