<?php
include 'db_connect.php';

$hospitals = [
    1 => [10.8242, 78.6835], // Dr. Shri Ramya
    3 => [10.8234, 78.6811], // Sundaram
    4 => [10.8256, 78.6888], // Kauvery
    5 => [10.8266, 78.6841]  // Githanjali
];

try {
    foreach ($hospitals as $id => $coords) {
        $stmt = $conn->prepare("UPDATE hospitals SET latitude = ?, longitude = ? WHERE hospital_id = ?");
        $stmt->execute([$coords[0], $coords[1], $id]);
        echo "Updated hospital ID $id\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
