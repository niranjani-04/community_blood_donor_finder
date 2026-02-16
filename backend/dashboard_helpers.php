<?php
// backend/dashboard_helpers.php
// Standard PHP Helpers for Dashboard Logic

function getActiveSOS($conn, $user_id) {
    if (!$conn) return null;
    $stmt = $conn->prepare("SELECT alert_id, blood_group, status FROM sos_alerts WHERE requester_id = ? AND status IN ('active', 'accepted') ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserProfile($conn, $user_id) {
    if (!$conn) return null;
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getLeaderboardData($conn) {
    if (!$conn) return [];
    $stmt = $conn->prepare("SELECT name, blood_group, points FROM users WHERE role = 'student' ORDER BY points DESC LIMIT 5");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAdminStats($conn) {
    if (!$conn) return ['count' => 0];
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sos_alerts WHERE status = 'active'");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getInventory($conn) {
    if (!$conn) return [];
    return $conn->query("SELECT * FROM blood_inventory")->fetchAll(PDO::FETCH_ASSOC);
}

function getLiveAlerts($conn) {
    if (!$conn) return [];
    return $conn->query("SELECT s.*, u.name, u.phone FROM sos_alerts s JOIN users u ON s.requester_id = u.user_id WHERE s.status IN ('active', 'accepted') ORDER BY s.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
