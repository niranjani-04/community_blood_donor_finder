<?php
include_once __DIR__ . '/db_connect.php';

function getLeaderboard($conn) {
    try {
        // SELF-HEALING: Ensure points column exists
        $check = $conn->query("SHOW COLUMNS FROM users LIKE 'points'");
        if ($check->rowCount() == 0) {
            $conn->exec("ALTER TABLE users ADD points INT DEFAULT 0");
        }
        
        $stmt = $conn->query("SELECT name, points, blood_group FROM users WHERE role = 'donor' ORDER BY points DESC LIMIT 5");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getHospitalCount($conn) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM hospitals");
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}
?>
