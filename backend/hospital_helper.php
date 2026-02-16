<?php
include_once __DIR__ . '/db_connect.php';

function getBloodStocks() {
    global $conn;
    $stmt = $conn->query("SELECT h.name as hospital_name, s.blood_group, s.units, s.last_updated 
                          FROM blood_stocks s 
                          JOIN hospitals h ON s.hospital_id = h.hospital_id 
                          ORDER BY h.name, s.blood_group");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUpcomingCamps() {
    global $conn;
    $stmt = $conn->query("SELECT * FROM blood_camps WHERE camp_date >= CURDATE() ORDER BY camp_date ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
