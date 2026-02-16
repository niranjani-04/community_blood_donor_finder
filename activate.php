<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activate Dashboard - Community Blood Donor Finder</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-red: #ff3e5e;
            --primary-red-hover: #ff1a40;
            --bg-dark: #0a0a0a;
            --card-bg: rgba(20, 20, 25, 0.7);
            --text-muted: #888;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            padding: 40px 20px;
        }

        /* Vignette Effect */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, transparent 30%, rgba(0,0,0,0.9) 100%);
            pointer-events: none;
            z-index: -1;
        }

        /* Blood Flow Animation Background */
        .blood-stream {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
            background: var(--bg-dark);
        }

        .blood-cell {
            position: absolute;
            background: radial-gradient(circle, rgba(180, 0, 0, 0.3) 0%, rgba(100, 0, 0, 0.1) 60%, transparent 80%);
            border-radius: 50%;
            filter: blur(8px);
            animation: flow linear infinite;
            pointer-events: none;
            opacity: 0;
        }

        .blood-cell::after {
            content: '';
            position: absolute;
            top: 25%;
            left: 25%;
            width: 50%;
            height: 50%;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 50%;
            filter: blur(5px);
        }

        @keyframes flow {
            0% { transform: translate(-150%, -150%) rotate(0deg) scale(0.8); opacity: 0; }
            10% { opacity: 0.3; }
            90% { opacity: 0.3; }
            100% { transform: translate(110vw, 110vh) rotate(360deg) scale(1.2); opacity: 0; }
        }

        .activate-card {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 50px 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.6);
            z-index: 10;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header h1 {
            color: var(--primary-red);
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 35px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }

        .form-label {
            display: block;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 8px;
            margin-left: 12px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 16px 20px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 62, 94, 0.5);
            outline: none;
            box-shadow: 0 0 0 4px rgba(255, 62, 94, 0.1);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }

        .form-control::-webkit-calendar-picker-indicator {
            filter: invert(1) sepia(100%) saturate(500%) hue-rotate(300deg);
            cursor: pointer;
            opacity: 0.6;
        }

        .btn-activate {
            background: var(--primary-red);
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 18px;
            width: 100%;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 15px;
            box-shadow: 0 10px 20px rgba(255, 62, 94, 0.2);
        }

        .btn-activate:hover {
            background: var(--primary-red-hover);
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(255, 62, 94, 0.4);
        }

        .footer-text {
            margin-top: 35px;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .login-link {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .login-link:hover {
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="blood-stream" id="bloodStream"></div>

    <div class="activate-card">
        <div class="header">
            <h1>Activate Account</h1>
            <p>Official Donor Registration</p>
        </div>
        
        <form action="backend/auth_activate.php" method="POST">
            <div class="form-group">
                <label class="form-label">College Record</label>
                <input type="text" name="register_number" class="form-control" placeholder="Register Number" required autocomplete="off" value="<?= isset($_GET['reg']) ? htmlspecialchars($_GET['reg']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Identity Verification</label>
                <input type="date" name="dob" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Contact Details</label>
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
            </div>
            
            <div class="form-group">
                <input type="tel" name="phone" class="form-control" placeholder="Phone Number" required pattern="[0-9]{10}" maxlength="10">
            </div>
            
            <button type="submit" class="btn-activate">Verify & Activate</button>
        </form>
        
        <div class="footer-text">
            Already a registered donor? <a href="login.php" class="login-link">Sign In</a>
        </div>
    </div>

    <script>
        const stream = document.getElementById('bloodStream');
        const count = 18;
        for (let i = 0; i < count; i++) {
            const cell = document.createElement('div');
            cell.className = 'blood-cell';
            const size = Math.random() * 80 + 60;
            const duration = Math.random() * 30 + 30;
            const delay = Math.random() * -60;
            cell.style.width = `${size}px`;
            cell.style.height = `${size}px`;
            cell.style.left = `${Math.random() * 100}%`;
            cell.style.top = `${Math.random() * 100}%`;
            cell.style.animationDuration = `${duration}s`;
            cell.style.animationDelay = `${delay}s`;
            stream.appendChild(cell);
        }
    </script>
</body>
</html>
