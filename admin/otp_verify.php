<?php
session_start();
include '../backend/db_connect.php';

if (!isset($_SESSION['admin_pre_auth']) || $_SESSION['admin_pre_auth'] !== true) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp_input = $_POST['otp'];
    $admin_id = $_SESSION['admin_id'];

    $stmt = $conn->prepare("SELECT * FROM admin_auth_log WHERE user_id = ? AND otp = ? AND is_verified = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$admin_id, $otp_input]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($entry) {
        // Mark as verified
        $conn->prepare("UPDATE admin_auth_log SET is_verified = 1 WHERE id = ?")->execute([$entry['id']]);
        
        // Complete fully authenticated session
        unset($_SESSION['admin_pre_auth']);
        unset($_SESSION['admin_otp']);
        
        $_SESSION['user_id'] = $_SESSION['admin_id'];
        $_SESSION['name'] = $_SESSION['admin_name'];
        $_SESSION['role'] = "admin";
        $_SESSION['2fa_verified'] = true;

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid or expired passcode. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2FA Verification - Admin Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff2d55;
            --bg-dark: #050505;
            --card-bg: rgba(15, 15, 20, 0.6);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
        }

        body {
            background-color: var(--bg-dark);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            color: #fff;
            margin: 0;
            overflow: hidden;
        }

        .admin-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 45, 85, 0.1) 0%, transparent 70%);
            z-index: -1;
        }

        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 50px 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.8);
        }

        .otp-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .hero-title { font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; }
        .hero-subtitle { color: var(--text-dim); font-size: 0.9rem; margin-bottom: 30px; }

        .otp-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            color: #fff;
            font-size: 1.5rem;
            letter-spacing: 10px;
            text-align: center;
            margin-bottom: 25px;
        }

        .otp-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(255, 45, 85, 0.2);
        }

        .btn-verify {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-verify:hover { transform: translateY(-2px); opacity: 0.9; }
        
        .error-msg { color: #ff5252; font-size: 0.85rem; margin-top: 15px; }

        .audit-info {
            margin-top: 30px;
            font-size: 0.75rem;
            color: #555;
            background: rgba(0,0,0,0.3);
            padding: 10px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="admin-glow"></div>

<div class="glass-card">
    <div class="otp-icon">
        <i class="fas fa-key-skeleton"></i>
    </div>
    <h2 class="hero-title">Security Check</h2>
    <p class="hero-subtitle">Enter the 6-digit passcode sent to the administrator's secure terminal.</p>

    <form method="POST">
        <input type="text" name="otp" class="otp-input" placeholder="000000" maxlength="6" required autocomplete="off">
        <button type="submit" class="btn-verify">VERIFY IDENTITY</button>
        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
    </form>

    <div class="audit-info">
        <i class="fas fa-info-circle me-1"></i> Audit Trail: Check <code>backend/security_audit.txt</code> for the OTP.
    </div>

    <a href="logout.php" style="color: var(--text-dim); text-decoration: none; font-size: 0.8rem; display: block; margin-top: 20px;">
        Cancel and Sign Out
    </a>
</div>

</body>
</html>
