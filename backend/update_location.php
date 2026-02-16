session_start();
include 'db_connect.php';
header('Content-Type: text/plain');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    // Update User Table
    $sql = "UPDATE users SET latitude = ?, longitude = ?, updated_at = NOW() WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$latitude, $longitude, $user_id])) {
        // Find the most recent active alert this donor has accepted
        $alert_stmt = $conn->prepare("SELECT alert_id FROM sos_responses WHERE donor_id = ? AND status = 'accepted' ORDER BY accepted_at DESC LIMIT 1");
        $alert_stmt->execute([$user_id]);
        $alert_row = $alert_stmt->fetch(PDO::FETCH_ASSOC);
        $active_alert_id = $alert_row ? $alert_row['alert_id'] : null;

        // Also Log to Tracking History
        $t_sql = "INSERT INTO tracking (donor_id, alert_id, latitude, longitude) VALUES (?, ?, ?, ?)";
        $t_stmt = $conn->prepare($t_sql);
        $t_stmt->execute([$user_id, $active_alert_id, $latitude, $longitude]);

        echo "Location Updated Successfully";
    } else {
        echo "Error updating location.";
    }
}
?>
