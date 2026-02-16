<?php
include 'backend/db_connect.php';

// Get Donor ID from URL (e.g., verify_donor.php?id=123)
$donor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($donor_id <= 0) {
    die("Invalid Donor ID");
}

// Fetch Donor Details
$stmt = $conn->prepare("SELECT user_id, name, blood_group, is_activated, availability_status, created_at FROM users WHERE user_id = ?");
$stmt->execute([$donor_id]);
$donor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donor) {
    die("Donor not found.");
}

// Fetch Donation History Summary
$hist_stmt = $conn->prepare("SELECT COUNT(*) as total_donations, MAX(donation_date) as last_donation FROM donation_history WHERE donor_id = ? AND status = 'completed'");
$hist_stmt->execute([$donor_id]);
$history = $hist_stmt->fetch(PDO::FETCH_ASSOC);

$is_verified = ($donor['is_activated'] == 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verified Donor Profile - <?php echo htmlspecialchars($donor['name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff2d55;
            --bg-dark: #050505;
            --card-bg: rgba(20, 20, 25, 0.8);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
        }
        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .profile-card {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px 30px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .verified-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .donor-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--primary);
            border: 2px solid var(--primary);
            box-shadow: 0 0 20px rgba(255, 45, 85, 0.3);
        }
        .donor-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .donor-id {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-bottom: 25px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 16px;
        }
        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }
        .stat-label {
            font-size: 0.75rem;
            color: #94a3b8;
            text-transform: uppercase;
        }
        .footer-note {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>

<div class="profile-card">
    <?php if ($is_verified): ?>
        <div class="verified-badge">
            <i class="fas fa-check-circle"></i> VERIFIED
        </div>
    <?php endif; ?>

    <div class="donor-avatar">
        <i class="fas fa-user"></i>
    </div>
    
    <h1 class="donor-name"><?php echo htmlspecialchars($donor['name']); ?></h1>
    <p class="donor-id">ID: BHC-<?php echo str_pad($donor['user_id'], 4, '0', STR_PAD_LEFT); ?></p>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-value"><?php echo htmlspecialchars($donor['blood_group']); ?></div>
            <div class="stat-label">Blood Group</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo $history['total_donations'] ?? 0; ?></div>
            <div class="stat-label">Donations</div>
        </div>
    </div>
    
    <div class="stat-box" style="margin-bottom: 0;">
        <div class="stat-value" style="font-size: 1rem; color: #fff;">
            <?php echo $history['last_donation'] ? date('d M Y', strtotime($history['last_donation'])) : 'None'; ?>
        </div>
        <div class="stat-label">Last Donation</div>
    </div>

    <div class="footer-note">
        <i class="fas fa-shield-alt me-1"></i> Official BHC Donation Record<br>
        Scan to verify authenticity.
    </div>
</div>

</body>
</html>
