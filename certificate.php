<?php
/**
 * CERTIFICATE GENERATION
 * Generates a PDF certificate for donors with donation history
 * Uses TCPDF library (assumed installed or using a simple HTML fallback)
 */

session_start();
require_once 'backend/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user and latest donation details
$sql = "SELECT u.name, u.register_number, u.blood_group, MAX(dh.completed_at) as last_donation, COUNT(dh.id) as total_donations
        FROM users u
        LEFT JOIN donation_history dh ON u.user_id = dh.donor_id
        WHERE u.user_id = ?
        GROUP BY u.user_id";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['total_donations'] == 0) {
    die("No donation history found. You must donate blood to earn a certificate.");
}

// Simple HTML Certificate Generation (if TCPDF is not available)
// Ideally, you would use a library like TCPDF or FPDF here.
// For this project, we'll generate a printable HTML page.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Appreciation</title>
    <style>
        body { font-family: 'Georgia', serif; background: #f0f0f0; text-align: center; padding: 50px; }
        .certificate {
            width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 50px;
            border: 20px solid #781c1c;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            position: relative;
        }
        .header { font-size: 50px; font-weight: bold; color: #781c1c; margin-bottom: 20px; text-transform: uppercase; }
        .subheader { font-size: 25px; margin-bottom: 50px; color: #333; }
        .name { font-size: 40px; font-weight: bold; border-bottom: 2px solid #333; display: inline-block; padding: 0 20px; margin: 20px 0; color: #000; }
        .details { font-size: 20px; margin: 30px 0; line-height: 1.6; color: #555; }
        .signature { margin-top: 60px; display: flex; justify-content: space-between; padding: 0 50px; }
        .sig-line { border-top: 1px solid #333; width: 200px; padding-top: 10px; font-weight: bold; }
        .print-btn { margin-top: 30px; padding: 10px 20px; background: #333; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 5px; }
        .print-btn:hover { background: #555; }
        @media print {
            body { background: white; padding: 0; }
            .certificate { border: 5px solid #781c1c; box-shadow: none; width: 100%; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

    <div class="certificate">
        <!-- Decorative Corners -->
        <div style="position: absolute; top: 10px; left: 10px; border-top: 5px solid #e55353; border-left: 5px solid #e55353; width: 50px; height: 50px;"></div>
        <div style="position: absolute; top: 10px; right: 10px; border-top: 5px solid #e55353; border-right: 5px solid #e55353; width: 50px; height: 50px;"></div>
        <div style="position: absolute; bottom: 10px; left: 10px; border-bottom: 5px solid #e55353; border-left: 5px solid #e55353; width: 50px; height: 50px;"></div>
        <div style="position: absolute; bottom: 10px; right: 10px; border-bottom: 5px solid #e55353; border-right: 5px solid #e55353; width: 50px; height: 50px;"></div>

        <div class="header" style="font-family: 'Roboto', sans-serif; letter-spacing: 2px;">Certificate of Appreciation</div>
        <div class="subheader">Presented with gratitude to</div>
        
        <div class="name"><?php echo strtoupper(htmlspecialchars($user['name'])); ?></div>
        
        <div class="details" style="font-size: 24px; margin: 40px auto; max-width: 600px;">
            In recognition of your voluntary contribution and 
            <span style="color: #e55353; font-weight: bold;">Noble Act of Blood Donation</span> <br>
            bearing Blood Group <span style="color: #e55353; font-weight: bold;"><?php echo htmlspecialchars($user['blood_group']); ?></span>.
        </div>

        <div class="details" style="color: #333;">
            <p>Your selfless contribution has played a vital role in <br> <b>Saving Lives</b> and supporting the community.</p>
        </div>
        
        <div class="row" style="display: flex; justify-content: space-around; margin-top: 20px;">
            <div style="text-align: left;">
                <small style="color: #8a93a2;">Date Issued:</small><br>
                <b><?php echo date('d M Y', strtotime($user['last_donation'])); ?></b>
            </div>
            <div style="text-align: right;">
                <small style="color: #8a93a2;">Certificate ID:</small><br>
                <b>BHC-DON-<?php echo str_pad($user_id, 4, '0', STR_PAD_LEFT); ?>-<?php echo date('Y'); ?></b>
            </div>
        </div>
        
        <div class="signature">
            <div style="text-align: center;">
                <div style="height: 40px;"></div>
                <div class="sig-line">NSS Coordinator</div>
                <small>Bishop Heber College</small>
            </div>
            <div>
                <img src="https://via.placeholder.com/100x100?text=SEAL" alt="College Seal" style="height: 80px; filter: grayscale(1); opacity: 0.3;">
            </div>
            <div style="text-align: center;">
                <div style="height: 40px;"></div>
                <div class="sig-line">Principal</div>
                <small>Bishop Heber College</small>
            </div>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()">Print Certificate</button>
    <br><br>
    <a href="index.php" style="color: #781c1c;">Back to Dashboard</a>

</body>
</html>
