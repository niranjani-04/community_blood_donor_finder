<?php
include 'backend/db_connect.php';
// Calculate Lives Saved (1 donation = 3 lives saved standard)
$donations_count = $conn->query("SELECT COUNT(*) FROM donation_history")->fetchColumn();
$lives_saved = ($donations_count * 3) + 1240; // Base count + real stats

// Fetch approved testimonials
$testimonials = $conn->query("SELECT * FROM testimonials WHERE status = 'approved' ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Community Blood Donor Finder</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#ff2d55">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="BHC Blood">
    <link rel="apple-touch-icon" href="https://img.icons8.com/color/192/000000/medical-heart.png">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff2d55;
            --primary-glow: rgba(255, 45, 85, 0.4);
            --secondary: #00d2ff;
            --secondary-glow: rgba(0, 210, 255, 0.3);
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
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            position: relative;
            color: #fff;
            padding: 40px 0;
        }

        /* Ambient Animated Mesh Background overlay */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7));
            z-index: -5;
        }

        /* Fluid Background Container */
        .fluid-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -4;
            filter: url('#goo');
            overflow: hidden;
            background: var(--bg-dark);
        }

        .fluid-blob {
            position: absolute;
            width: 400px;
            height: 400px;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0.15;
            filter: blur(40px);
            animation: move-fluid 25s infinite alternate ease-in-out;
        }

        .blob-1 { top: -10%; left: -10%; width: 600px; height: 600px; background: #ff2d55; animation-duration: 30s; }
        .blob-2 { bottom: -15%; right: -10%; width: 500px; height: 500px; background: #880000; animation-duration: 35s; animation-delay: -5s; }
        .blob-3 { top: 40%; left: 50%; width: 450px; height: 450px; background: #b31217; animation-duration: 40s; animation-delay: -10s; }

        @keyframes move-fluid {
            0% { transform: translate(0, 0) scale(1) rotate(0deg); }
            33% { transform: translate(100px, 50px) scale(1.1) rotate(45deg); }
            66% { transform: translate(-50px, 150px) scale(0.9) rotate(-45deg); }
            100% { transform: translate(0, 0) scale(1) rotate(0deg); }
        }

        /* Particle Stream (Layered over Fluid) */
        .blood-stream {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -3;
            overflow: hidden;
            pointer-events: none;
        }

        .blood-cell {
            position: absolute;
            background: radial-gradient(circle, rgba(255, 45, 85, 0.4) 0%, transparent 80%);
            border-radius: 50%;
            filter: blur(2px);
            animation: stream linear infinite;
        }

        @keyframes stream {
            0% { transform: translate(-100%, -100%) scale(0.5); opacity: 0; }
            50% { opacity: 0.2; }
            100% { transform: translate(120vw, 120vh) scale(1.2); opacity: 0; }
        }

        /* Premium Glass Card */
        .login-card {
            background: rgba(15, 15, 20, 0.4);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 60px 45px;
            width: 100%;
            max-width: 460px;
            box-shadow: 
                0 40px 100px rgba(0, 0, 0, 0.8),
                inset 0 0 20px rgba(255, 255, 255, 0.02);
            position: relative;
            z-index: 10;
            text-align: center;
            animation: cardEntrance 1s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: scale(0.9) translateY(40px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Typography */
        .text-gradient {
            background: linear-gradient(135deg, #fff 0%, #ff2d55 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 12px;
            letter-spacing: -1px;
        }

        .hero-subtitle {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-dim);
            letter-spacing: 2px;
            text-uppercase: uppercase;
            margin-bottom: 40px;
        }

        /* Form Controls */
        .input-group {
            position: relative;
            margin-bottom: 24px;
        }

        .form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            padding: 18px 25px;
            color: var(--text-main);
            font-size: 1.05rem;
            font-weight: 400;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--primary);
            box-shadow: 0 0 20px rgba(255, 45, 85, 0.2);
            outline: none;
            transform: translateY(-2px);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }

        /* Icon Overlay for Inputs */
        .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 1.1rem;
            pointer-events: none;
            transition: color 0.3s;
        }

        .form-input:focus + .input-icon {
            color: var(--primary);
        }

        /* Date Input Styling */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1) sepia(100%) saturate(1000%) hue-rotate(320deg);
            cursor: pointer;
            opacity: 0.4;
            position: absolute;
            right: 18px;
        }

        /* Premium Glow Button */
        .btn-glow {
            width: 100%;
            background: linear-gradient(135deg, #ff2d55 0%, #ff1744 100%);
            color: white;
            border: none;
            border-radius: 18px;
            padding: 20px;
            font-size: 1.15rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 10px 30px rgba(255, 45, 85, 0.3);
        }

        .btn-glow:hover {
            transform: scale(1.02) translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 45, 85, 0.5);
            background: linear-gradient(135deg, #ff4d6d 0%, #ff2d55 100%);
        }

        .btn-glow:active {
            transform: scale(0.98) translateY(0);
        }

        /* Footer Links */
        .login-footer {
            margin-top: 40px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .footer-msg {
            color: var(--text-dim);
            font-size: 0.95rem;
            font-weight: 400;
        }

        .admin-link {
            color: var(--text-dim);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            transition: all 0.3s;
        }

        .admin-link:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* 3D Heart Micro-animation */
        .heart-pulse {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 25px;
            display: inline-block;
            animation: heartBeat 1.5s infinite cubic-bezier(0.4, 0, 0.6, 1);
            filter: drop-shadow(0 0 15px var(--primary-glow));
        }

        @keyframes heartBeat {
            0% { transform: scale(1); }
            14% { transform: scale(1.15); }
            28% { transform: scale(1.05); }
            42% { transform: scale(1.2); }
            70% { transform: scale(1); }
        }

        /* Testimonial Styles */
        .testimonial-item {
            opacity: 0;
            transform: translateY(10px);
            visibility: hidden;
            pointer-events: none;
        }
        .testimonial-item.active {
            opacity: 1;
            transform: translateY(0);
            visibility: visible;
            pointer-events: auto;
        }
        .testimonial-item.hidden {
            opacity: 0;
            transform: translateY(-10px);
            visibility: hidden;
            pointer-events: none;
        }

    </style>
</head>
<body>
    <!-- SVG Filter for Gooey Effect -->
    <svg style="visibility: hidden; position: absolute;" width="0" height="0" xmlns="http://www.w3.org/2000/svg" version="1.1">
        <defs>
            <filter id="goo">
                <feGaussianBlur in="SourceGraphic" stdDeviation="15" result="blur" />
                <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 25 -10" result="goo" />
                <feComposite in="SourceGraphic" in2="goo" operator="atop"/>
            </filter>
        </defs>
    </svg>

    <!-- Fluid Background -->
    <div class="fluid-container">
        <div class="fluid-blob blob-1"></div>
        <div class="fluid-blob blob-2"></div>
        <div class="fluid-blob blob-3"></div>
    </div>

    <!-- Particle Layer -->
    <div class="blood-stream" id="bloodStream"></div>

    <div class="login-card">
        <!-- Impact Counter Ticker -->
        <div class="mb-4 d-flex align-items-center justify-content-center gap-2" style="background: rgba(255,45,85,0.1); border: 1px solid rgba(255,45,85,0.2); padding: 8px 16px; border-radius: 50px; animation: fadeIn 1.5s ease;">
            <span class="spinner-grow spinner-grow-sm text-danger" role="status"></span>
            <span class="text-xs fw-bold text-danger tracking-wide text-uppercase">
                <span id="impact-counter"><?php echo number_format($lives_saved); ?></span> LIVES SAVED IN THIS SESSION
            </span>
        </div>

        <div class="heart-pulse">
            <i class="fas fa-heart-pulse"></i>
        </div>
        
        <div class="login-header">
            <h1 class="hero-title text-gradient">Community Blood<br>Donor Finder</h1>
            <p class="hero-subtitle">Bishop Heber College</p>
        </div>
        
        <form action="backend/auth_login.php" method="POST">
            <div class="input-group">
                <input 
                    type="text" 
                    name="register_number" 
                    class="form-input" 
                    placeholder="Register Number" 
                    required
                    autocomplete="off"
                >
                <i class="fas fa-id-card input-icon"></i>
            </div>
            
            <div class="input-group">
                <input 
                    type="date" 
                    name="dob" 
                    class="form-input" 
                    required
                >
                <i class="fas fa-calendar-alt input-icon"></i>
            </div>
            
            <button type="submit" class="btn-glow">
                <span>SIGN IN</span>
                <i class="fas fa-arrow-right-to-bracket"></i>
            </button>
        </form>

        <!-- Testimonial Section -->
        <?php if(!empty($testimonials)): ?>
        <div class="mt-5 pt-4 border-top border-white border-opacity-10">
            <h6 class="text-xs fw-bold text-secondary mb-3 text-uppercase tracking-widest">Impact Stories</h6>
            <div id="testimonial-carousel" style="height: 100px; overflow: hidden; position: relative;">
                <?php foreach($testimonials as $idx => $t): ?>
                <div class="testimonial-item <?php echo $idx == 0 ? 'active' : 'hidden'; ?>" style="transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1); position: absolute; width: 100%; left: 0; top: 0;">
                    <p class="text-sm italic text-white-50 mb-1">"<?php echo htmlspecialchars($t['message']); ?>"</p>
                    <small class="text-xs text-danger">— <?php echo htmlspecialchars($t['author_info']); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="login-footer">
            <span class="footer-msg">Official Donor Portal Access</span>
            <div class="d-flex flex-column gap-2">
                <a href="admin/index.php" class="admin-link">
                    <i class="fas fa-user-shield"></i>
                    Administrator Login
                </a>
                <a href="hospital/index.php" class="admin-link" style="background: rgba(0, 136, 204, 0.05); border-color: rgba(0, 136, 204, 0.2); color: #00d2ff;">
                    <i class="fas fa-hospital-user"></i>
                    Hospital Sync Portal
                </a>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'invalid_credentials') {
                alert("Invalid Record: Please check your Register Number or DOB.");
            }
        };

        const stream = document.getElementById('bloodStream');
        const count = 25;

        // --- TESTIMONIAL CAROUSEL ---
        let testimonialIndex = 0;
        const items = document.querySelectorAll('.testimonial-item');
        if(items.length > 1) {
            setInterval(() => {
                items[testimonialIndex].classList.add('hidden');
                items[testimonialIndex].classList.remove('active');
                testimonialIndex = (testimonialIndex + 1) % items.length;
                items[testimonialIndex].classList.add('active');
                items[testimonialIndex].classList.remove('hidden');
            }, 5000);
        }

        // --- LIVE COUNTER ANIMATION ---
        const counterEl = document.getElementById('impact-counter');
        if(counterEl) {
            const target = parseInt(counterEl.innerText.replace(/,/g, ''));
            let current = target - 50;
            const timer = setInterval(() => {
                current++;
                counterEl.innerText = current.toLocaleString();
                if(current >= target) clearInterval(timer);
            }, 30);
        }

        for (let i = 0; i < count; i++) {
            const cell = document.createElement('div');
            cell.className = 'blood-cell';
            
            const size = Math.random() * 60 + 40;
            const duration = Math.random() * 20 + 15;
            const delay = Math.random() * -30;
            const left = Math.random() * 100;
            const top = Math.random() * 100;

            cell.style.width = `${size}px`;
            cell.style.height = `${size}px`;
            cell.style.left = `${left}%`;
            cell.style.top = `${top}%`;
            cell.style.animationDuration = `${duration}s`;
            cell.style.animationDelay = `${delay}s`;
            
            stream.appendChild(cell);
        }
    </script>
</body>
</html>
