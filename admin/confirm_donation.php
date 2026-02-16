<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access Denied");
}
include '../backend/db_connect.php';

if (isset($_GET['alert_id'])) {
    $alert_id = $_GET['alert_id'];
    
    // We need to know WHICH donor donated. For simplicity, we'll ask admin to select from those who accepted.
    // Fetch accepted donors for this alert
    $sql = "SELECT u.user_id, u.name FROM sos_responses r JOIN users u ON r.donor_id = u.user_id WHERE r.alert_id = ? AND r.status = 'accepted'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$alert_id]);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Hospitals for selection
    $hospitals = $conn->query("SELECT hospital_id, name FROM hospitals ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $alert_id = $_POST['alert_id'];
    $donor_id = $_POST['donor_id'];
    $hospital_id = $_POST['hospital_id'];

    if ($donor_id && $hospital_id) {
        $date = date('Y-m-d');
        
        // SELF-HEALING: Check if 'points' column exists to prevent crash
        $check = $conn->query("SHOW COLUMNS FROM users LIKE 'points'");
        if ($check->rowCount() == 0) {
            $conn->exec("ALTER TABLE users ADD points INT DEFAULT 0");
        }

        // 1. Update Donor Last Donation Date, Reset Availability, and ADD POINTS
        $upd = $conn->prepare("UPDATE users SET last_donation_date = ?, availability_status = 'On Deferral', points = points + 50 WHERE user_id = ?");
        $upd->execute([$date, $donor_id]);

        // 2. Mark Alert as Completed
        $upd2 = $conn->prepare("UPDATE sos_alerts SET status = 'completed' WHERE alert_id = ?");
        $upd2->execute([$alert_id]);

        // 3. Mark Response as Completed
        $upd3 = $conn->prepare("UPDATE sos_responses SET status = 'completed' WHERE alert_id = ? AND donor_id = ?");
        $upd3->execute([$alert_id, $donor_id]);

        // 4. Log in donation_history
        try {
            $stmt = $conn->prepare("SELECT requester_id, blood_group FROM sos_alerts WHERE alert_id = ?");
            $stmt->execute([$alert_id]);
            $alert_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $log = $conn->prepare("INSERT INTO donation_history (donor_id, requester_id, alert_id, blood_group, points_earned, hospital_id) VALUES (?, ?, ?, ?, 50, ?)");
            $log->execute([$donor_id, $alert_info['requester_id'], $alert_id, $alert_info['blood_group'], $hospital_id]);

            // 5. BROADCAST FULLFILLMENT (to "remove" the urgency)
            require_once '../backend/notification_helper.php';
            $bg = $alert_info['blood_group'];
            sendTelegramNotification("✅ <b>SOS FULFILLED!</b>\nThe request for <b>$bg</b> blood (Alert #$alert_id) has been successfully fulfilled. Thank you to all donors who responded! 🙏");
            
        } catch (Exception $e) { /* Log error silently */ }

        echo "<script>window.location.href='dashboard.php?success=donation_confirmed';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Donation - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .confirm-card {
            max-width: 500px;
            width: 100%;
        }
        .icon-box {
            width: 80px;
            height: 80px;
            background: rgba(229, 45, 39, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 1px solid rgba(229, 45, 39, 0.4);
        }
    </style>
</head>
<body>

<div class="confirm-card">
    <div class="glass-card text-center p-5">
        <div class="icon-box">
            <i class="fas fa-check-circle fa-3x text-danger"></i>
        </div>
        
        <h2 class="h4 fw-bold mb-2">Confirm Donation</h2>
        <p class="text-white-50 mb-4">Please select the donor who completed the request for Alert #<?php echo $alert_id; ?></p>
        
        <form method="POST">
            <input type="hidden" name="alert_id" value="<?php echo $alert_id; ?>">
            
            <div class="mb-3 text-start">
                <label class="small text-white-50 mb-2">Select Verified Donor</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <select name="donor_id" class="form-select" required>
                        <option value="">-- Choose Donor --</option>
                        <?php foreach ($donors as $d): ?>
                            <option value="<?php echo $d['user_id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4 text-start">
                <label class="small text-white-50 mb-2">Donated At (Hospital)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-hospital"></i></span>
                    <select name="hospital_id" class="form-select" required>
                        <option value="">-- Select Hospital --</option>
                        <?php foreach ($hospitals as $h): ?>
                            <option value="<?php echo $h['hospital_id']; ?>"><?php echo htmlspecialchars($h['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if(empty($donors)): ?>
                    <div class="alert alert-warning mt-3 bg-transparent border-warning text-warning small">
                        <i class="fas fa-exclamation-triangle"></i> No donors have accepted this alert yet.
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" <?php echo empty($donors) ? 'disabled' : ''; ?>>
                    <i class="fas fa-save me-2"></i> Finalize Donation
                </button>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm mt-3 pt-2 pb-2">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </form>
        
        <div class="mt-4 pt-3 border-top border-white-10">
            <small class="text-white-50">+50 Reward Points will be awarded to the donor.</small>
        </div>
    </div>
</div>

</body>
</html>
