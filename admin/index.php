<?php
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin' && isset($_SESSION['2fa_verified']) && $_SESSION['2fa_verified'] === true) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Access - Community Blood Donor Finder</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff2d55;
            --primary-glow: rgba(255, 45, 85, 0.4);
            --bg-dark: #050505;
            --card-bg: rgba(15, 15, 20, 0.6);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Tech Background Grid */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -2;
        }

        /* Red Ambient Glow */
        .admin-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255, 45, 85, 0.08) 0%, transparent 70%);
            z-index: -1;
            animation: pulseGlow 10s infinite alternate;
        }

        @keyframes pulseGlow {
            from { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1.2); }
        }

        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 60px 45px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.8);
            text-align: center;
            animation: cardFadeIn 0.8s ease-out;
        }

        @keyframes cardFadeIn {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .admin-icon {
            width: 90px;
            height: 90px;
            background: rgba(255, 45, 85, 0.1);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            border: 1px solid rgba(255, 45, 85, 0.2);
            color: var(--primary);
            font-size: 2.5rem;
            animation: iconPulse 3s infinite;
        }

        @keyframes iconPulse {
            0% { transform: scale(1); box-shadow: 0 0 0 rgba(255, 45, 85, 0); }
            50% { transform: scale(1.05); box-shadow: 0 0 20px rgba(255, 45, 85, 0.2); }
            100% { transform: scale(1); box-shadow: 0 0 0 rgba(255, 45, 85, 0); }
        }

        .hero-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .hero-subtitle {
            color: var(--text-dim);
            font-size: 0.95rem;
            margin-bottom: 40px;
            letter-spacing: 1px;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
            text-align: left;
        }

        .input-label {
            color: var(--text-dim);
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            margin-left: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 16px 20px;
            color: var(--text-main);
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 15px rgba(255, 45, 85, 0.2);
        }

        .btn-authorize {
            width: 100%;
            background: linear-gradient(135deg, #ff2d55 0%, #ff1744 100%);
            color: white;
            border: none;
            border-radius: 16px;
            padding: 18px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 10px 30px rgba(255, 45, 85, 0.3);
        }

        .btn-authorize:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(255, 45, 85, 0.5);
            background: linear-gradient(135deg, #ff4d6d 0%, #ff2d55 100%);
        }

        .return-link {
            color: var(--text-dim);
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 30px;
            display: inline-block;
            transition: color 0.3s;
        }

        .return-link:hover {
            color: var(--text-main);
        }

    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">

<div class="admin-glow"></div>

<div class="glass-card">

    <div class="admin-icon">
        <i class="fas fa-shield-halved"></i>
    </div>
    
    <h2 class="hero-title">Admin Portal</h2>
    <p class="hero-subtitle">Secure Access Verification</p>

    <form action="auth_admin.php" method="POST">

        <div class="input-group">
            <span class="input-label">Administrator Email</span>
            <input type="email" name="email" class="form-input" placeholder="admin@heber.edu" required>
        </div>

        <div class="input-group">
            <span class="input-label">Secure Access Key</span>
            <input type="password" name="password" class="form-input" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-authorize">
            <i class="fas fa-lock"></i>
            <span>AUTHORIZE SESSION</span>
        </button>

        <a href="../index.php" class="return-link">
            <i class="fas fa-arrow-left me-2"></i>
            Return to Dashboard
        </a>

    </form>

</div>

</body>
</html>
