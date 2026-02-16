<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'backend/db_connect.php';

$user_id = $_SESSION['user_id'];
$role = trim(strtolower($_SESSION['role'] ?? ''));
// Fetch User Details
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u) {
    session_destroy();
    header("Location: login.php?error=session_expired");
    exit();
}

$user_name = $u['name']; 
$_SESSION['name'] = $u['name']; // Sync session for all modules

// --- SMART ELIGIBILITY TRACKER ---
$next_eligible_date = null;
$days_until_eligible = 0;
$is_eligible = true;

if ($role == 'donor' && !empty($u['last_donation_date'])) {
    $last_date = new DateTime($u['last_donation_date']);
    $today = new DateTime();
    $interval = $today->diff($last_date);
    $days_passed = $interval->days;
    
    // Eligibility threshold: 90 days
    $next_date = clone $last_date;
    $next_date->modify('+90 days');
    $next_eligible_date = $next_date->format('Y-m-d');
    
    if ($days_passed < 90) {
        $is_eligible = false;
        $diff = $today->diff($next_date);
        $days_until_eligible = $diff->invert ? 0 : $diff->days;
    } else {
        // AUTO-AVAILABILITY: Automatically reset status to 'Available' after 90 days
        if ($u['availability_status'] === 'On Deferral') {
            $conn->prepare("UPDATE users SET availability_status = 'Available' WHERE user_id = ?")->execute([$user_id]);
            $u['availability_status'] = 'Available'; // Update local variable
        }
    }
}

// Check for active SOS alert from this user
$stmt_sos = $conn->prepare("SELECT alert_id, blood_group FROM sos_alerts WHERE requester_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
$stmt_sos->execute([$user_id]);
$my_active_alert = $stmt_sos->fetch(PDO::FETCH_ASSOC);

// Fetch Stats for User
if ($role == 'donor') {
    $my_donations = $conn->query("SELECT COUNT(*) FROM donation_history WHERE donor_id = $user_id")->fetchColumn();
    $active_sos = $conn->query("SELECT COUNT(*) FROM sos_alerts WHERE status = 'active'")->fetchColumn();

    // Fetch Extra Data for Donor Dashboard
    $hospitals = $conn->query("SELECT * FROM hospitals ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    // Attach Stock Info to each hospital for UI display
    foreach($hospitals as &$h) {
        $h['stocks'] = $conn->query("SELECT blood_group, units FROM blood_inventory WHERE hospital_id = " . $h['hospital_id'] . " AND units > 0")->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($h); // Break reference
    $camps = $conn->query("SELECT * FROM blood_camps WHERE camp_date >= CURDATE() ORDER BY camp_date")->fetchAll(PDO::FETCH_ASSOC);
    $stocks = $conn->query("SELECT i.*, h.name as hospital_name, h.address, h.contact_phone, h.latitude, h.longitude 
                            FROM blood_inventory i 
                            JOIN hospitals h ON i.hospital_id = h.hospital_id 
                            WHERE i.units > 0 
                            ORDER BY h.name, i.blood_group")->fetchAll(PDO::FETCH_ASSOC);

    // FETCH LEADERBOARD
    $leaderboard = $conn->query("SELECT name, points, (SELECT COUNT(*) FROM donation_history WHERE donor_id = u.user_id) as d_count 
                                 FROM users u WHERE role = 'donor' AND points > 0 
                                 ORDER BY points DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // BADGE LOGIC
    $badges = [];
    if ($my_donations >= 1) $badges[] = ['name' => 'Life Saver', 'icon' => 'fa-award', 'color' => '#3b82f6'];
    if ($my_donations >= 5) $badges[] = ['name' => 'Hero', 'icon' => 'fa-shield-heart', 'color' => '#8b5cf6'];
    if ($my_donations >= 10) $badges[] = ['name' => 'Guardian', 'icon' => 'fa-crown', 'color' => '#f59e0b'];
    if ($my_donations >= 20) $badges[] = ['name' => 'Legend', 'icon' => 'fa-dragon', 'color' => '#ef4444'];
    if (($u['points'] ?? 0) >= 1000) $badges[] = ['name' => 'Centurion', 'icon' => 'fa-bolt', 'color' => '#fbbf24'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Community Blood Donor Finder - Bishop Heber College</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#ff2d55">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="BHC Blood">
    <link rel="apple-touch-icon" href="https://img.icons8.com/color/192/000000/medical-heart.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        :root {
            --primary: #ff2d55;
            --primary-glow: rgba(255, 45, 85, 0.4);
            --secondary: #94a3b8;
            --bg-body: #050505;
            --bg-card: rgba(20, 20, 25, 0.4);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --glass-border: rgba(255, 255, 255, 0.08);
        }
        
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)), url('bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        /* --- Top Navigation Bar (Converted from Sidebar) --- */
        .sidebar {
            width: 100%;
            height: auto;
            position: fixed;
            left: 0;
            top: 0;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            z-index: 1001;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            padding: 0 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        /* Hide top nav in fullscreen hero mode */
        body.hero-fullscreen .sidebar {
            transform: translateY(-100%);
            opacity: 0;
            pointer-events: none;
        }

        .sidebar-header {
            height: auto;
            display: flex;
            align-items: center;
            padding: 16px 0;
            border-bottom: none;
            border-right: 1px solid var(--glass-border);
            padding-right: 30px;
            margin-right: 30px;
        }

        .brand-text {
            font-size: 1.1rem;
            font-weight: 800;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.5px;
            white-space: nowrap;
        }

        .brand-text small {
            display: none; /* Hide subtitle in top nav */
        }

        /* Navigation Links Container */
        .sidebar > div:not(.sidebar-header) {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            flex-wrap: wrap;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.9rem;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            margin: 0;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, #b31217 100%);
            box-shadow: 0 4px 15px var(--primary-glow);
            color: #fff;
        }

        .nav-link i {
            width: auto;
            font-size: 1rem;
            margin-right: 8px;
            text-align: center;
        }

        /* Divider */
        .my-4.border-top {
            display: none; /* Hide dividers in horizontal layout */
        }

        /* --- Main Content --- */
        .main-content {
            margin-left: 0;
            position: relative;
            z-index: 10;
            padding: 0;
            padding-top: 80px; /* Space for top navigation */
            transition: all 0.3s ease;
        }

        /* Remove top padding in fullscreen mode */
        body.hero-fullscreen .main-content {
            padding-top: 0;
        }

        /* Top Header */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            padding: 20px 40px;
            border-bottom: 1px solid var(--glass-border);
            position: sticky;
            top: 0;
            z-index: 100;
            transition: all 0.3s ease;
        }

        /* Hide top header in fullscreen hero mode */
        body.hero-fullscreen .top-header {
            transform: translateY(-100%);
            opacity: 0;
        }
        
        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: #fff;
        }
        
        /* --- Cards --- */
        .card-box {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid var(--glass-border);
            padding: 2rem;
            height: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-box:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 45, 85, 0.2);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5);
        }

        .stat-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #fff;
        }
        
        .bg-gradient-danger { background: linear-gradient(135deg, var(--primary) 0%, #b31217 100%); box-shadow: 0 8px 20px var(--primary-glow); }
        .bg-gradient-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3); }
        .bg-gradient-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3); }

         /* Form Inputs Dark Mode */
        .form-control, .form-select {
            background-color: #101014;
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
        }
        .form-control:focus, .form-select:focus {
            background-color: #101014;
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(229, 83, 83, 0.25);
        }

        /* --- Utilities --- */
        .hidden { display: none !important; }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        .fw-bold { font-weight: 600 !important; }

        /* --- Hybrid Navigation: Dashboard Scroll + Tab Sections --- */
        html {
            scroll-behavior: smooth;
        }

        /* All content views hidden by default */
        .content-view {
            display: none;
        }

        /* Visible sections show */
        .content-view:not(.hidden) {
            display: block;
        }

        /* Dashboard starts visible (no hidden class initially) */
        #view-dashboard {
            display: block;
        }

        /* Section Titles */
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
            display: inline-block;
        }

        .section-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 40px;
        }

        /* --- Hero Section & Overlap Layout --- */
        .hero-section {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 100vh;
            z-index: 0;
            display: flex;
            align-items: center;
            padding: 0 60px;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(255, 45, 85, 0.5) 0%, rgba(10, 10, 15, 0.95) 100%);
        }

        /* Video Background */
        .hero-video-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover;
            z-index: -2;
            animation: videoZoom 20s ease-in-out infinite alternate;
        }

        @keyframes videoZoom {
            0% { transform: translate(-50%, -50%) scale(1); }
            100% { transform: translate(-50%, -50%) scale(1.05); }
        }

        /* Video Overlay */
        .hero-video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 45, 85, 0.6) 0%, rgba(10, 10, 15, 0.85) 100%);
            z-index: -1;
        }

        .hero-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%);
            top: -200px;
            right: -100px;
            opacity: 0.4;
            pointer-events: none;
        }

        .hero-text {
            position: relative;
            z-index: 2;
            max-width: 700px;
            animation: fadeInHero 1.2s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes fadeInHero {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 100px;
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 24px;
            backdrop-filter: blur(10px);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
            letter-spacing: -1.5px;
            color: #fff;
        }

        .hero-subtitle {
            font-size: 1.15rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 0;
        }

        .content-body {
            position: relative;
            z-index: 10;
            margin-top: 75vh;
            background: #08080a;
            border-radius: 40px 40px 0 0;
            padding: 50px 40px;
            box-shadow: 0 -30px 60px rgba(0, 0, 0, 0.6);
            border-top: 1px solid var(--glass-border);
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* Mobile Adjustments for Hero */
        @media (max-width: 991.98px) {
            .hero-section {
                left: 0;
                height: 100vh;
                padding: 0 30px;
            }
            .hero-title { font-size: 2.5rem; }
            .content-body { margin-top: 70vh; padding: 40px 20px; }
            .top-header { padding: 15px 25px; }
        }

        /* Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            animation: bounce 2s infinite;
        }

        .scroll-indicator i {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .scroll-indicator span {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
            40% { transform: translateX(-50%) translateY(-10px); }
            60% { transform: translateX(-50%) translateY(-5px); }
        }

        /* --- Page Transitions --- */
        @keyframes pageTransition {
            from {
                opacity: 0;
                transform: translateX(40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pageExit {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(-40px);
            }
        }

        .page-entering {
            display: block !important;
            animation: pageTransition 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        .page-exiting {
            display: block !important;
            animation: pageExit 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        /* Staggered Reveal */
        .content-view.page-entering > * {
            opacity: 0;
            transform: translateY(20px);
            animation: revealItem 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes revealItem {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content-view.page-entering > *:nth-child(1) { animation-delay: 0.1s; }
        .content-view.page-entering > *:nth-child(2) { animation-delay: 0.2s; }
        .content-view.page-entering > *:nth-child(3) { animation-delay: 0.3s; }
        .content-view.page-entering > *:nth-child(4) { animation-delay: 0.4s; }
        .content-view.page-entering > *:nth-child(5) { animation-delay: 0.5s; }
        .content-view.page-entering > *:nth-child(6) { animation-delay: 0.6s; }
        .content-view.page-entering > *:nth-child(7) { animation-delay: 0.7s; }
        .content-view.page-entering > *:nth-child(8) { animation-delay: 0.8s; }
        
        /* Mobile Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                padding: 0 20px;
                overflow-x: auto;
                overflow-y: hidden;
            }
            
            .sidebar-header {
                padding-right: 15px;
                margin-right: 15px;
            }
            
            .nav-link {
                padding: 10px 16px;
                font-size: 0.85rem;
            }
            
            .nav-link span {
                display: none; /* Show icons only on mobile */
            }
            
            .nav-link i {
                margin-right: 0;
            }
            
            .main-content { 
                margin-left: 0;
                padding-top: 70px; /* Space for top nav */
            }
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
            z-index: -2;
            filter: url('#goo');
            overflow: hidden;
            background: var(--bg-body);
        }

        .fluid-blob {
            position: absolute;
            width: 450px;
            height: 450px;
            background: var(--primary);
            border-radius: 50%;
            opacity: 0.12;
            filter: blur(40px);
            animation: move-fluid 30s infinite alternate ease-in-out;
        }

        .blob-1 { top: -10%; left: -10%; width: 700px; height: 700px; background: #ff2d55; animation-duration: 35s; }
        .blob-2 { bottom: -15%; right: -10%; width: 600px; height: 600px; background: #880000; animation-duration: 45s; animation-delay: -7s; }
        .blob-3 { top: 40%; left: 50%; width: 550px; height: 550px; background: #b31217; animation-duration: 50s; animation-delay: -15s; }

        @keyframes move-fluid {
            0% { transform: translate(0, 0) scale(1) rotate(0deg); }
            33% { transform: translate(120px, 60px) scale(1.15) rotate(60deg); }
            66% { transform: translate(-80px, 180px) scale(0.9) rotate(-60deg); }
            100% { transform: translate(0, 0) scale(1) rotate(0deg); }
        }

        /* --- Professional Emergency SOS Button --- */
        .sos-btn-container {
            position: relative;
            display: inline-block;
            padding: 30px;
        }

        .sos-btn-large {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 35% 35%, #ff4d4d 0%, #cc0000 50%, #800000 100%);
            border: 8px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 
                0 0 0 0 rgba(255, 45, 85, 0.6),
                0 20px 40px rgba(0, 0, 0, 0.5),
                inset 0 2px 10px rgba(255, 255, 255, 0.4);
            text-decoration: none !important;
            overflow: visible !important;
        }

        .sos-btn-large i {
            font-size: 3.5rem !important;
            margin-bottom: 5px;
            filter: drop-shadow(0 2px 10px rgba(0,0,0,0.5)) !important;
            animation: none !important;
        }

        .sos-btn-large .sos-text {
            font-size: 1.2rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .sos-btn-large:hover {
            transform: scale(1.05);
            background: radial-gradient(circle at 35% 35%, #ff6666 0%, #dd0000 50%, #a00000 100%);
        }

        .sos-btn-large:active {
            transform: scale(0.95);
        }

        /* Pulsating Ping Rings */
        .sos-ping {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: #ff2d55;
            opacity: 0.6;
            z-index: 1;
            pointer-events: none;
            animation: sos-pulse-pro 2s infinite;
        }

        .sos-ping-delayed {
            animation-delay: 1s;
        }

        @keyframes sos-pulse-pro {
            0% { transform: translate(-50%, -50%) scale(1); opacity: 0.6; }
            100% { transform: translate(-50%, -50%) scale(1.8); opacity: 0; }
        }

        .sos-status-line {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 25px;
        }

        .status-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 5px 14px;
            border-radius: 100px;
            font-size: 0.7rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pill.active {
            color: #4ade80;
            border-color: rgba(74, 222, 128, 0.3);
            background: rgba(74, 222, 128, 0.08);
        }

        .status-pill.active i {
            animation: fast-blink 1s infinite;
        }

        @keyframes fast-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.2; }
        }

        @keyframes sos-icon-pulse {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.5)); }
            50% { transform: scale(1.15); filter: drop-shadow(0 0 30px rgba(255, 255, 255, 1)); }
        }
        
        /* Clickable Card */
        .clickable-card {
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--glass-border) !important;
        }
        .clickable-card:hover {
            transform: translateY(-8px);
            background: rgba(255,255,255,0.06);
            border-color: var(--primary) !important;
            box-shadow: 0 20px 40px -10px rgba(255, 45, 85, 0.2);
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

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="#" class="brand-text">
            <div class="d-flex align-items-center mb-1">
                <i class="fas fa-heartbeat text-danger me-2"></i>
                <span style="font-size: 0.9em;">Community Blood Donor Finder</span>
            </div>
            <small class="text-secondary fw-normal" style="font-size: 0.65em; padding-left: 28px; text-transform: uppercase; letter-spacing: 1px;">Bishop Heber College</small>
        </a>
    </div>
    <div class="mt-4">
        <?php if ($role == 'donor'): ?>
        <a href="index.php" class="nav-link <?php echo (!isset($_GET['page']) || $_GET['page'] == 'dashboard') ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> <span>Dashboard</span>
        </a>
        <a href="index.php?page=sos" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] == 'sos') ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i> <span>Request SOS</span>
        </a>
        <a href="index.php?page=profile" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] == 'profile') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i> <span>My Profile</span>
        </a>
        <a href="#" class="nav-link" onclick="openIDCard()">
            <i class="fas fa-id-card"></i> <span>Digital ID Card</span>
        </a>
        <a href="index.php?page=hospitals" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] == 'hospitals') ? 'active' : ''; ?>">
            <i class="fas fa-hospital"></i> <span>Nearby Hospitals</span>
        </a>
        <a href="index.php?page=camps" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] == 'camps') ? 'active' : ''; ?>">
            <i class="fas fa-campground"></i> <span>Donation Camps</span>
        </a>
        <a href="index.php?page=stocks" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] == 'stocks') ? 'active' : ''; ?>">
            <i class="fas fa-tint"></i> <span>Blood Availability</span>
        </a>
        <?php endif; ?>

        <?php if ($role == 'requester' || $role == 'admin'): ?>
        <a href="index.php?page=sos" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] == 'sos') ? 'active' : ''; ?>">
            <i class="fas fa-broadcast-tower"></i> <span>Emergency SOS</span>
        </a>
        <?php endif; ?>

        <?php if ($role == 'admin'): ?>
        <a href="admin/dashboard.php" class="nav-link text-primary">
            <i class="fas fa-user-shield"></i> <span>Admin Portal</span>
        </a>
        <?php endif; ?>
        
        <div class="my-4 border-top border-secondary opacity-25 mx-3"></div>

        <a href="https://t.me/BHC_BloodSOS" target="_blank" class="nav-link" style="background: rgba(0, 136, 204, 0.1); color: #0088cc;">
            <i class="fab fa-telegram-plane"></i> <span>Join Telegram</span>
        </a>
        
        <a href="logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i> <span>Sign Out</span>
        </a>
    </div>
</nav>

<!-- MAIN CONTENT -->
<main class="main-content">
    
    <!-- HERO SECTION (Fixed Background) -->
    <?php if ($role == 'donor'): ?>
    <div class="hero-section">
        <!-- Video Background -->
        <video class="hero-video-bg" autoplay muted loop playsinline>
            <source src="hero section.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        
        <!-- Video Overlay for better text readability -->
        <div class="hero-video-overlay"></div>
        
        <div class="hero-glow"></div>
        <div class="hero-text">
            <div class="hero-badge">
                <i class="fas fa-heartbeat me-2"></i>
                Blood Donor Network
                <?php if($is_eligible): ?>
                    <span class="ms-2 px-2 py-1 bg-success rounded text-xs fw-bold"><i class="fas fa-check-circle"></i> ELIGIBLE</span>
                <?php else: ?>
                    <span class="ms-2 px-2 py-1 bg-warning text-dark rounded text-xs fw-bold"><i class="fas fa-clock"></i> NEXT: <?php echo $days_until_eligible; ?> DAYS</span>
                <?php endif; ?>
            </div>
            <h1 class="hero-title">Welcome Back,<br><?php echo htmlspecialchars($user_name); ?></h1>
            <p class="hero-subtitle">Your contribution saves lives. Track emergencies, manage donations, and connect with those in need through our community platform.</p>
        </div>
        <div class="scroll-indicator">
            <span>Scroll Down</span>
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- TOP HEADER -->
    <div class="top-header">
        <div>
            <!-- Removed Breadcrumb -->
            <h5 class="page-title mt-1" id="page-header">Overview</h5>
        </div>
        <div class="d-flex align-items-center">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none text-white">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-2 me-2 text-center" style="width:35px;height:35px;">
                        <i class="fas fa-user text-danger"></i>
                    </div>
                    <span class="d-none d-sm-inline font-weight-bold"><?php echo htmlspecialchars($user_name); ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- CONTENT BODY (Overlaps Hero) -->
    <div class="content-body">
    
    <?php 
    // Determine which page to show
    $current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    ?>
    
    <!-- 1. DASHBOARD VIEW (DONOR) -->
    <?php if ($role == 'donor'): ?>
    <div id="view-dashboard" class="content-view <?php echo ($current_page != 'dashboard') ? 'hidden' : ''; ?>">
        <h2 class="section-title">📊 Dashboard Overview</h2>
        
        <!-- Telegram Join Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card-box border-0" style="background: linear-gradient(90deg, #0088cc 0%, #00aaff 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-white p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fab fa-telegram-plane fa-lg text-primary"></i>
                            </div>
                            <div>
                                <h6 class="text-white fw-bold mb-1">Get Instant SOS Alerts on Telegram</h6>
                                <p class="text-white-50 text-xs mb-0">Join our official channel to get notifications even when you are offline.</p>
                            </div>
                        </div>
                        <a href="https://t.me/BHC_BloodSOS" target="_blank" class="btn btn-white btn-sm rounded-pill fw-bold bg-white text-primary px-4">
                            Join Channel
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card-box stat-card" style="border-left: 4px solid <?php echo $is_eligible ? '#10b981' : '#f59e0b'; ?> !important;">
                    <div>
                        <p class="text-sm mb-1 text-secondary font-weight-bold">Medical Status</p>
                        <h5 class="mb-0 fw-bold <?php echo $is_eligible ? 'text-success' : 'text-warning'; ?>">
                            <i class="fas <?php echo $is_eligible ? 'fa-check-circle' : 'fa-clock'; ?> me-2"></i>
                            <?php echo $is_eligible ? 'Eligible' : $days_until_eligible . ' Days Left'; ?>
                        </h5>
                    </div>
                    <div class="stat-icon <?php echo $is_eligible ? 'bg-gradient-success' : 'bg-gradient-warning'; ?>">
                        <i class="fas fa-heart"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card-box stat-card">
                    <div>
                        <p class="text-sm mb-1 text-secondary font-weight-bold">My Donations</p>
                        <h4 class="mb-0 fw-bold text-white"><?php echo $my_donations; ?></h4>
                    </div>
                    <div class="stat-icon bg-gradient-info">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12">
                <div class="card-box stat-card" style="border-right: 4px solid #fbbf24 !important;">
                    <div>
                        <p class="text-sm mb-1 text-secondary font-weight-bold">Reward Points</p>
                        <h4 class="mb-0 fw-bold text-warning"><?php echo number_format($u['points'] ?? 0); ?></h4>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                        <i class="fas fa-star text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access Services -->
        <h6 class="mb-3 ps-1 fw-bold text-white-50 text-uppercase text-xs tracking-wide">Services</h6>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card-box text-center py-4 clickable-card h-100" onclick="window.location.href='index.php?page=hospitals'">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 d-inline-flex mb-3 align-items-center justify-content-center" style="width: 70px; height: 70px;">
                        <i class="fas fa-hospital fa-2x text-danger"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-1">Nearby Hospitals</h5>
                    <p class="text-secondary text-xs mb-0">Find hospitals & navigation</p>
                </div>
            </div>
            <div class="col-md-4">
                 <div class="card-box text-center py-4 clickable-card h-100" onclick="window.location.href='index.php?page=camps'">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 d-inline-flex mb-3 align-items-center justify-content-center" style="width: 70px; height: 70px;">
                        <i class="fas fa-campground fa-2x text-warning"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-1">Donation Camps</h5>
                    <p class="text-secondary text-xs mb-0">Join upcoming events</p>
                </div>
            </div>
            <div class="col-md-4">
                 <div class="card-box text-center py-4 clickable-card h-100" onclick="window.location.href='index.php?page=stocks'">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 d-inline-flex mb-3 align-items-center justify-content-center" style="width: 70px; height: 70px;">
                        <i class="fas fa-tint fa-2x text-info"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-1">Blood Availability</h5>
                    <p class="text-secondary text-xs mb-0">Check live stock levels</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card-box">
                    <h6 class="mb-3 fw-bold text-danger">🔴 Live Blood Requests</h6>
                    <div id="sos-list" class="row g-3 mb-5">
                        <div class="col-12 text-center py-5">
                            <div class="spinner-border text-secondary mb-3" role="status"></div>
                            <p class="text-secondary">Scanning for nearby emergencies...</p>
                        </div>
                    </div>

                    <div class="mt-5 border-top border-white border-opacity-10 pt-5">
                        <div class="d-flex align-items-center mb-4">
                            <i class="fas fa-trophy fa-2x text-warning me-3"></i>
                            <div>
                                <h4 class="mb-0 fw-bold text-white">Donor Hall of Fame</h4>
                                <p class="text-secondary mb-0">Ranking based on life-saving contribution points</p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <?php foreach($leaderboard ?? [] as $index => $top): ?>
                            <div class="col-12">
                                <div class="card-box py-3 px-4 d-flex align-items-center bg-black bg-opacity-25 border border-white border-opacity-10 rounded-pill transition-all" style="border-left: 4px solid <?php echo ($index == 0) ? '#f59e0b' : (($index == 1) ? '#94a3b8' : (($index == 2) ? '#b45309' : 'transparent')); ?> !important;">
                                    <div class="fs-4 fw-bold me-4 text-center" style="width: 40px;">
                                        <?php if($index == 0): ?><i class="fas fa-crown text-warning"></i>
                                        <?php elseif($index == 1): ?><i class="fas fa-medal text-secondary"></i>
                                        <?php elseif($index == 2): ?><i class="fas fa-medal" style="color: #b45309;"></i>
                                        <?php else: ?><span class="text-secondary opacity-50">#<?php echo $index + 1; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-gradient-danger p-2 me-3 shadow-sm" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                                            <span class="text-white fw-bold"><?php echo strtoupper(substr($top['name'], 0, 1)); ?></span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-white"><?php echo htmlspecialchars($top['name']); ?></h6>
                                            <div class="text-xs text-secondary d-flex align-items-center">
                                                <i class="fas fa-check-circle text-success me-1"></i> <?php echo $top['d_count']; ?> life-saving donations
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-auto text-end">
                                        <div class="text-warning fw-bold fs-5 mb-0"><?php echo number_format($top['points']); ?> <small class="text-xs">pts</small></div>
                                        <div class="progress mt-1" style="height: 3px; width: 60px; background: rgba(255,255,255,0.05);">
                                            <div class="progress-bar bg-warning" style="width: <?php echo min(100, ($top['points'] / 1000) * 100); ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($leaderboard)): ?>
                                <div class="col-12 text-center py-4">
                                    <p class="text-secondary italic">Be the first to join the Hall of Fame!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2. SOS VIEW (SHARED) -->
    <div id="<?php echo ($role=='donor') ? 'view-sos' : 'view-dashboard'; ?>" class="content-view <?php echo ($role=='donor' && $current_page != 'sos') ? 'hidden' : ''; ?>">
        <?php if($role == 'donor'): ?>
        <h2 class="section-title">🚨 Request SOS</h2>
        <?php endif; ?>
        
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card-box text-center border-danger border-top border-4 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-gradient-danger opacity-10" style="z-index:0;"></div>
                    <div class="position-relative" style="z-index:1;">
                        <div class="mb-5 mt-4">
                            <div class="sos-btn-container">
                                <div class="sos-ping"></div>
                                <div class="sos-ping sos-ping-delayed"></div>
                                <button type="button" class="sos-btn-large" onclick="triggerSOS()">
                                    <i class="fas fa-hand-pointer"></i>
                                    <span class="sos-text">One Tap SOS</span>
                                </button>
                            </div>
                        </div>

                        <div class="sos-status-line mb-4">
                            <div class="status-pill active"><i class="fas fa-envelope"></i> Email</div>
                            <div class="status-pill active"><i class="fas fa-bell"></i> Push</div>
                        </div>

                        <h2 class="mb-2 fw-bold text-white">Emergency Dispatch</h2>
                        <p class="text-secondary mb-4">Tap to broadcast an instant emergency alert to nearby donors.</p>
                        
                        <form id="sosForm" onsubmit="event.preventDefault(); triggerSOS();">
                            <div class="mb-4 px-4">
                                <select id="blood_group" class="form-select form-select-lg text-center fw-bold text-white" style="border-radius: 50px; background: #101014; border-color: #333;" required>
                                    <option value="">-- Select Blood Group --</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-danger btn-lg w-100 py-3 fw-bold shadow-lg rounded-pill">
                                SEND ALERT NOW
                            </button>
                        </form>
                        <div id="sos-status" class="mt-4 border-top border-white border-opacity-10 pt-4">
                            <!-- THANK YOU NOTE SECTION (Impact Stories) -->
                            <div class="p-3 bg-black bg-opacity-25 rounded-4 border border-white border-opacity-5 mb-4">
                                <h6 class="text-white fw-bold mb-2 text-sm d-flex align-items-center justify-content-center">
                                    <i class="fas fa-heart text-danger me-2"></i> Share Your Impact Story
                                </h6>
                                <p class="text-xs text-secondary mb-3">Leave a "Thank You" note for our donors.</p>
                                <form action="backend/submit_testimonial.php" method="POST">
                                    <textarea name="message" class="form-control mb-2 text-xs bg-black border-secondary text-white" placeholder="E.g. Thank you for the quick response! Saved a life today..." rows="3" style="border-radius:12px;" required></textarea>
                                    <button type="submit" class="btn btn-xs btn-outline-danger rounded-pill px-4 text-xs">Submit Anonymous Note</button>
                                </form>
                            </div>
<?php if ($my_active_alert): ?>
                                <div class="alert alert-success mt-4 p-4 border-2 shadow" id="existing-alert-box">
                                    <h4 class="alert-heading fw-bold"><i class="fas fa-satellite-dish me-2"></i>Active SOS Alert</h4>
                                    <p class="mb-3">Your emergency request for <b><?php echo $my_active_alert['blood_group']; ?></b> is active. Donors are being tracked below.</p>
                                    
                                    <!-- LIVE ACCEPTORS SECTION -->
                                    <div id="acceptors-panel" class="mb-4">
                                        <h6 class="text-uppercase text-xs fw-bold text-success mb-3 tracking-wide">
                                            <span class="spinner-grow spinner-grow-sm me-1" role="status"></span> Live Responses
                                        </h6>
                                        <div id="acceptors-list" class="d-flex flex-column gap-2">
                                            <div class="text-secondary text-sm italic">Scanning for donors...</div>
                                        </div>
                                    </div>

                                    <hr>
                                    <a href="track.php?alert_id=<?php echo $my_active_alert['alert_id']; ?>" class="btn btn-warning btn-lg w-100 fw-bold rounded-pill">
                                        <i class="fas fa-map-marked-alt me-2"></i> Open GPS Fleet Tracker
                                    </a>
                                    
                                    <script>
                                        $(document).ready(function() {
                                            const alertId = <?php echo $my_active_alert['alert_id']; ?>;
                                            const acceptorTicker = setInterval(function() {
                                                $.get('backend/fetch_acceptors.php?alert_id=' + alertId, function(res) {
                                                    const acceptors = JSON.parse(res);
                                                    if(acceptors.length > 0) {
                                                        let html = '';
                                                        acceptors.forEach(acc => {
                                                            html += `
                                                                <div class="d-flex align-items-center p-3 bg-dark bg-opacity-50 rounded border border-success border-opacity-10">
                                                                    <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                                                                        <i class="fas fa-user-check text-success"></i>
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <div class="fw-bold text-white">${acc.name}</div>
                                                                        <div class="text-xs text-secondary">Group: ${acc.blood_group} • <a href="tel:${acc.phone}" class="text-success decoration-none">${acc.phone}</a></div>
                                                                    </div>
                                                                    <div class="badge bg-success bg-opacity-25 text-success rounded-pill px-3">On the way</div>
                                                                </div>
                                                            `;
                                                        });
                                                        $('#acceptors-list').html(html);
                                                    }
                                                });
                                            }, 3000);
                                            setTimeout(() => clearInterval(acceptorTicker), 600000);
                                        });
                                    </script>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. PROFILE VIEW (DONOR) -->
    <?php if ($role == 'donor'): ?>
    <div id="view-profile" class="content-view <?php echo ($current_page != 'profile') ? 'hidden' : ''; ?>">
        <h2 class="section-title">👤 My Profile</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-box">
                    <div class="d-flex align-items-center mb-5 pb-4 border-bottom border-white border-opacity-10">
                        <div class="bg-gradient-danger rounded-circle p-4 text-white me-4 shadow">
                            <span class="display-4"><i class="fas fa-user"></i></span>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-white">
                                <?php echo htmlspecialchars($u['name']); ?>
                                <span class="badge bg-warning text-dark rounded-pill ms-2" style="font-size: 0.8rem;">
                                    <i class="fas fa-star me-1"></i> <?php echo number_format($u['points'] ?? 0); ?> pts
                                </span>
                            </h3>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <span class="badge bg-secondary bg-opacity-25 border border-secondary text-secondary rounded-pill px-3 py-1">Registered Donor</span>
                                <?php foreach($badges as $badge): ?>
                                    <span class="badge rounded-pill px-3 py-1" style="background: <?php echo $badge['color']; ?>33; border: 1px solid <?php echo $badge['color']; ?>; color: <?php echo $badge['color']; ?>;">
                                        <i class="fas <?php echo $badge['icon']; ?> me-1"></i> <?php echo $badge['name']; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-danger rounded-pill px-4 py-3 fs-5"><?php echo htmlspecialchars($u['blood_group']); ?></span>
                        </div>
                    </div>
                    
                    <div class="row g-4 pt-2">
                        <div class="col-md-6">
                            <div class="p-3 border border-secondary border-opacity-25 rounded bg-dark">
                                <label class="text-xs text-secondary text-uppercase fw-bold mb-1">Register Number</label>
                                <h5 class="mb-0 text-white"><?php echo htmlspecialchars($u['register_number']); ?></h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border border-secondary border-opacity-25 rounded bg-dark">
                                <label class="text-xs text-secondary text-uppercase fw-bold mb-1">Phone Number</label>
                                <h5 class="mb-0 text-white"><?php echo htmlspecialchars($u['phone']); ?></h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border border-secondary border-opacity-25 rounded bg-dark">
                                <label class="text-xs text-secondary text-uppercase fw-bold mb-1">Email Address</label>
                                <h5 class="mb-0 text-white"><?php echo htmlspecialchars($u['email']); ?></h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border border-secondary border-opacity-25 rounded bg-dark">
                                <label class="text-xs text-secondary text-uppercase fw-bold mb-1">Availability</label>
                                <h5 class="mb-0 <?php echo $is_eligible ? 'text-success' : 'text-warning'; ?>">
                                    <?php echo htmlspecialchars($u['availability_status']); ?>
                                </h5>
                            </div>
                        </div>
                    </div>

                    <!-- Eligibility Tracker Section -->
                    <div class="mt-4 p-4 border border-info border-opacity-10 rounded bg-info bg-opacity-5">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info bg-opacity-20 text-info p-3 rounded-circle me-3">
                                <i class="fas fa-calendar-check fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="text-white fw-bold mb-0">Smart Eligibility Tracker</h5>
                                <p class="text-secondary text-sm">Medical safety gap: 90 days between donations</p>
                            </div>
                        </div>
                        
                        <?php if(!empty($u['last_donation_date'])): ?>
                            <div class="mb-2 d-flex justify-content-between text-sm">
                                <span class="text-secondary">Next eligible: <b class="text-white"><?php echo date('d M Y', strtotime($next_eligible_date)); ?></b></span>
                                <span class="text-<?php echo $is_eligible ? 'success' : 'info'; ?> fw-bold">
                                    <?php echo $is_eligible ? 'Ready to Save Lives!' : 'Resting: ' . $days_until_eligible . ' days left'; ?>
                                </span>
                            </div>
                            <div class="progress bg-black bg-opacity-50" style="height: 10px; border-radius: 10px;">
                                <?php 
                                    $percent = $is_eligible ? 100 : min(100, ( (90 - $days_until_eligible) / 90 ) * 100);
                                ?>
                                <div class="progress-bar bg-info progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info bg-transparent border-info border-opacity-25 text-info mb-0 py-2">
                                <i class="fas fa-info-circle me-2"></i> No previous donation recorded. You are eligible to donate now!
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Certificate Section -->
                    <div class="mt-5 p-4 border border-danger border-opacity-10 rounded bg-danger bg-opacity-5">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="text-white fw-bold mb-1">Donation Recognition</h5>
                                <p class="text-secondary text-sm mb-0">Total successful donations: <b class="text-white"><?php echo $my_donations; ?></b></p>
                                <?php if ($my_donations > 0): ?>
                                    <p class="text-xs text-success mt-1"><i class="fas fa-check-circle me-1"></i> You are eligible for a certificate of appreciation!</p>
                                <?php else: ?>
                                    <p class="text-xs text-warning mt-1"><i class="fas fa-info-circle me-1"></i> Donate blood to unlock your certificate.</p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <?php if ($my_donations > 0): ?>
                                    <a href="certificate.php" target="_blank" class="btn btn-danger rounded-pill px-4 py-2 fw-bold">
                                        <i class="fas fa-certificate me-2"></i> Download Certificate
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary rounded-pill px-4 py-2 fw-bold opacity-50 cursor-not-allowed" disabled>
                                        <i class="fas fa-lock me-2"></i> Download Locked
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 4. HOSPITALS VIEW (DONOR) -->
    <?php if ($role == 'donor'): ?>
    <div id="view-hospitals" class="content-view <?php echo ($current_page != 'hospitals') ? 'hidden' : ''; ?>">
        <h2 class="section-title">🏥 Nearby Hospitals</h2>
        
        <div class="card-box">
             <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0 fw-bold text-white">Find Nearby Hospitals</h5>
                <div class="input-group w-100 w-md-60">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fas fa-search"></i></span>
                    <input type="text" id="hospitalSearch" class="form-control" placeholder="Search saved hospitals..." onkeyup="filterHospitals()">
                    <button class="btn btn-danger" onclick="searchNearby()" title="Search within our registry">Registry Nearby</button>
                    <a href="https://www.google.com/maps/search/hospitals+near+me" target="_blank" class="btn btn-outline-warning">
                        <i class="fab fa-google me-1"></i> Google Maps
                    </a>
                </div>
            </div>
            
            <!-- MAP CONTAINER -->
            <div id="hospital-map" class="mb-4 bg-dark border border-secondary border-opacity-25" style="height: 400px; width: 100%; border-radius: 12px; z-index: 0;"></div>
            
            <div id="hospital-list-container" class="row g-4">
                <!-- Populated by JS -->
            </div>
            
            <!-- Hidden Data for JS -->
            <script>
                var hospitalsData = <?php echo json_encode($hospitals); ?>;
            </script>
        </div>
    </div>

    <!-- 5. CAMPS VIEW (DONOR) -->
    <div id="view-camps" class="content-view <?php echo ($current_page != 'camps') ? 'hidden' : ''; ?>">
        <h2 class="section-title">⛺ Donation Camps</h2>
        <div class="card-box">
            <h5 class="mb-4 fw-bold text-white">Upcoming Donation Camps</h5>
            <?php if(count($camps) > 0): ?>
            <div class="row g-4">
                <?php foreach($camps as $c): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="p-4 border border-secondary border-opacity-25 rounded bg-dark h-100 position-relative">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle me-3">
                                <i class="fas fa-campground fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-white"><?php echo htmlspecialchars($c['title']); ?></h6>
                                <small class="text-secondary"><?php echo htmlspecialchars($c['organized_by']); ?></small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-calendar-alt text-secondary w-25px"></i>
                                <span class="text-white"><?php echo date('d M Y', strtotime($c['camp_date'])); ?></span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-clock text-secondary w-25px"></i>
                                <span class="text-white"><?php echo date('h:i A', strtotime($c['start_time'])); ?></span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-map-marker-alt text-secondary w-25px"></i>
                                <span class="text-white"><?php echo htmlspecialchars($c['location']); ?></span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-phone text-secondary w-25px"></i>
                                <span class="text-white"><?php echo htmlspecialchars($c['contact_phone']); ?></span>
                            </div>
                        </div>
                         <?php if(!empty($c['description'])): ?>
                        <p class="text-sm text-secondary bg-black bg-opacity-25 p-2 rounded mb-0">
                            <?php echo htmlspecialchars($c['description']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-secondary mb-3"></i>
                    <p class="text-secondary">No upcoming camps scheduled at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 6. STOCKS VIEW (DONOR) -->
    <div id="view-stocks" class="content-view <?php echo ($current_page != 'stocks') ? 'hidden' : ''; ?>">
        <h2 class="section-title">💉 Blood Availability</h2>
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="fw-bold text-white mb-1">Blood Stock Availability</h5>
                <p class="text-secondary text-sm">Real-time inventory from connected hospitals</p>
            </div>
        </div>

        <?php 
        // Group Logic
        $grouped_stocks = [];
        foreach($stocks as $s) {
            $hid = $s['hospital_id'];
            if(!isset($grouped_stocks[$hid])) {
                $grouped_stocks[$hid] = [
                    'name' => $s['hospital_name'],
                    'address' => $s['address'],
                    'phone' => $s['contact_phone'],
                    'updated' => $s['last_updated'] ?? 'Just now',
                    'inventory' => []
                ];
            }
            $grouped_stocks[$hid]['inventory'][] = [
                'group' => $s['blood_group'],
                'units' => $s['units']
            ];
        }
        ?>

        <?php if(empty($grouped_stocks)): ?>
            <div class="card-box text-center py-5">
                <div class="bg-dark d-inline-block rounded-circle p-4 mb-3 border border-secondary border-opacity-25">
                    <i class="fas fa-box-open fa-3x text-secondary opacity-50"></i>
                </div>
                <h5 class="text-white">No Data Available</h5>
                <p class="text-secondary">Current blood stock information is unavailable.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach($grouped_stocks as $hospital): ?>
                <div class="col-md-6 col-xl-6">
                    <div class="card-box h-100 position-relative overflow-hidden border border-secondary border-opacity-10" style="background: linear-gradient(145deg, #1b1b21 0%, #23232a 100%);">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="fas fa-hospital-alt fa-lg"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-white mb-0"><?php echo htmlspecialchars($hospital['name']); ?></h5>
                                    <small class="text-secondary"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($hospital['address']); ?></small>
                                </div>
                            </div>
                            <?php if(!empty($hospital['phone'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($hospital['phone']); ?>" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
                                <i class="fas fa-phone-alt me-1"></i> Call
                            </a>
                            <?php endif; ?>
                        </div>

                        <hr class="border-secondary opacity-10 my-3">

                        <!-- Inventory Grid -->
                        <h6 class="text-secondary text-uppercase text-xs fw-bold mb-3 tracking-wide">Available Blood Groups</h6>
                        <div class="row g-2">
                            <?php foreach($hospital['inventory'] as $item): ?>
                            <div class="col-4 col-sm-3 col-lg-3">
                                <div class="p-2 rounded bg-dark border border-secondary border-opacity-25 text-center position-relative">
                                    <span class="d-block fw-bold text-white fs-5"><?php echo htmlspecialchars($item['group']); ?></span>
                                    <small class="text-<?php echo ($item['units'] < 5) ? 'warning' : 'success'; ?> fw-bold" style="font-size: 0.75rem;">
                                        <?php echo htmlspecialchars($item['units']); ?> Units
                                    </small>
                                    
                                    <!-- Low Stock Indicator -->
                                    <?php if($item['units'] < 3): ?>
                                    <span class="position-absolute top-0 end-0 translate-middle p-1 bg-danger border border-dark rounded-circle" title="Low Stock">
                                        <span class="visually-hidden">Low</span>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    </div> <!-- End content-body -->

</main>

<script>
    // Global variables for SOS
    var userLat = null;
    var userLng = null;
    var gpsReady = false;
    var filterNearbyOnly = false;

    // Tab Navigation Logic
    $(document).ready(function() {
        // Full-screen hero mode toggle based on scroll
        let role = "<?php echo $role; ?>";
        
        if(role === 'donor') {
            // Start in fullscreen mode for all donor pages
            $('body').addClass('hero-fullscreen');
            
            // Toggle fullscreen mode on scroll
            $(window).on('scroll', function() {
                let scrollTop = $(window).scrollTop();
                
                if(scrollTop > 100) {
                    // User scrolled down - show sidebar and header
                    $('body').removeClass('hero-fullscreen');
                } else {
                    // At top - hide sidebar and header for fullscreen hero
                    $('body').addClass('hero-fullscreen');
                }
            });
        }
        
        // URL-based navigation - no JavaScript needed for link handling!
        // PHP handles showing/hiding sections based on $_GET['page']
        
        // Page-specific initialization
        let currentPage = "<?php echo $current_page; ?>";
        if(currentPage === 'hospitals') {
            setTimeout(initHospitalMap, 500); 
            renderHospitals();
        }
        
        // Smooth scroll to content body for any sub-page
        if (currentPage !== 'dashboard' && role === 'donor') {
            $('html, body').animate({ scrollTop: $('.content-body').offset().top - 100 }, 800);
        }
        
        // GPS & Alerts Logic
        
        if ("geolocation" in navigator) {
            navigator.geolocation.watchPosition(function(position) {
                userLat = position.coords.latitude;
                userLng = position.coords.longitude;
                gpsReady = true;
                
                // Update User Marker on Map
                updateUserMarker();

                // If we are on hospitals tab, re-render list to show distance
                if(!document.getElementById('view-hospitals').classList.contains('hidden')) {
                    if(typeof renderHospitals === 'function') renderHospitals();
                }
            }, function(error) { console.log(error); });
        }
        
        // Polling
        if(role === 'donor') {
            setInterval(function() {
                if (gpsReady) $.post('backend/update_location.php', { latitude: userLat, longitude: userLng });
            }, 5000);
            
            setInterval(fetchAlerts, 4000);
            fetchAlerts();
        }
    });

    let lastAlertCount = 0;
    function fetchAlerts() {
        $.get('backend/fetch_alerts.php?t=' + new Date().getTime(), function(data) {
            const currentCount = (data.match(/card-box/g) || []).length;
            if (currentCount > lastAlertCount && lastAlertCount !== 0) {
                document.getElementById('emergencySound').play().catch(e => console.log("Sound play error:", e));
            }
            lastAlertCount = currentCount;
            $('#sos-list').html(data);
        });
    }

    function triggerSOS() {
         document.getElementById('dispatchSound').play().catch(e => {});
         var bg = $('#blood_group').val();
         var outputDiv = document.getElementById("sos-status");
         
         if(!bg) { 
            // Pulse the select box to draw attention
            $('#blood_group').addClass('is-invalid').focus();
            setTimeout(() => $('#blood_group').removeClass('is-invalid'), 2000);
            return; 
         }
         
         // Professional Dispatching State
         outputDiv.innerHTML = `
            <div class="mt-4 p-4 border border-danger border-opacity-25 rounded bg-dark">
                <div class="spinner-grow text-danger mb-3" role="status"></div>
                <h5 class="text-white fw-bold mb-3">Broadcasting Emergency Dispatch...</h5>
                <div class="d-flex justify-content-center gap-3 opacity-50">
                    <i class="fas fa-envelope"></i>
                    <i class="fas fa-satellite-dish"></i>
                </div>
            </div>
         `;
         
         // Fallback lat/lng if not ready
         if(!userLat) { userLat = 10.8211; userLng = 78.6934; } 

         $.post('backend/sos_create.php', { blood_group: bg, latitude: userLat, longitude: userLng }, function(res) {
            try {
                var data = JSON.parse(res);
                if(data.status === 'success') {
                    outputDiv.innerHTML = `
                        <div class="alert alert-success mt-4 p-4 border-2 shadow">
                            <h4 class="alert-heading fw-bold"><i class="fas fa-check-circle me-2"></i>Dispatch Successful!</h4>
                            <p class="mb-3">${data.message}</p>
                            
                            <!-- LIVE ACCEPTORS SECTION -->
                            <div id="acceptors-panel" class="mb-4">
                                <h6 class="text-uppercase text-xs fw-bold text-success mb-3 tracking-wide">
                                    <span class="spinner-grow spinner-grow-sm me-1" role="status"></span> Live Responses
                                </h6>
                                <div id="acceptors-list" class="d-flex flex-column gap-2">
                                    <div class="text-secondary text-sm italic">Waiting for donors to accept...</div>
                                </div>
                            </div>

                            <hr>
                            <a href="track.php?alert_id=${data.alert_id}" class="btn btn-warning btn-lg w-100 fw-bold rounded-pill">
                                <i class="fas fa-map-marked-alt me-2"></i> Open GPS Fleet Tracker
                            </a>
                        </div>
                    `;

                    // Start polling for acceptors
                    const acceptorTicker = setInterval(function() {
                        $.get('backend/fetch_acceptors.php?alert_id=' + data.alert_id, function(res) {
                            const acceptors = JSON.parse(res);
                            if(acceptors.length > 0) {
                                let html = '';
                                acceptors.forEach(acc => {
                                    html += `
                                        <div class="d-flex align-items-center p-3 bg-dark bg-opacity-50 rounded border border-success border-opacity-10">
                                            <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                                                <i class="fas fa-user-check text-success"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-white">${acc.name}</div>
                                                <div class="text-xs text-secondary">Group: ${acc.blood_group} • <a href="tel:${acc.phone}" class="text-success decoration-none">${acc.phone}</a></div>
                                            </div>
                                            <div class="badge bg-success bg-opacity-25 text-success rounded-pill px-3">On the way</div>
                                        </div>
                                    `;
                                });
                                $('#acceptors-list').html(html);
                            }
                        });
                    }, 3000);

                    // Stop polling after 10 minutes to save resources
                    setTimeout(() => clearInterval(acceptorTicker), 600000);

                    // Vibration feedback if supported
                    if ("vibrate" in navigator) navigator.vibrate([100, 30, 100]);
                } else {
                    outputDiv.innerHTML = '<div class="alert alert-danger mt-4 border-2"><i class="fas fa-exclamation-circle me-2"></i><b>Dispatch Error:</b> ' + data.message + '</div>';
                }
            } catch(e) {
                console.error("SOS Error Details:", res);
                outputDiv.innerHTML = '<div class="alert alert-danger mt-4 border-2"><b>Critical System Error:</b> The emergency server returned an invalid response. Contact Admin.</div>';
            }
         });
    }

    // Hospital Search & Render Logic
    var map;
    var userMarker;
    var hospitalMarkers = [];

    function initHospitalMap() {
        if(map) {
            map.invalidateSize();
            return;
        }

        // Default: Bishop Heber College
        map = L.map('hospital-map').setView([10.8211, 78.6934], 13);
        
        L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: 'OpenStreetMap',
            maxZoom: 19
        }).addTo(map);

        // Add Bishop Heber College Reference Marker
        var bhcIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        L.marker([10.8211, 78.6934], {icon: bhcIcon}).addTo(map).bindPopup("<b>Bishop Heber College</b><br>Reference Point");

        // Add Hospital Markers
        if(typeof hospitalsData !== 'undefined') {
            hospitalsData.forEach(h => {
                if(h.latitude && h.longitude) {
                    var m = L.marker([h.latitude, h.longitude])
                           .addTo(map)
                           .bindPopup("<b>" + h.name + "</b><br>" + h.address);
                    hospitalMarkers.push(m);
                }
            });
        }
        
        // Try update user marker if already ready
        updateUserMarker();
    }

    function updateUserMarker() {
        if(!map || !userLat || !userLng) return;
        
        if(userMarker) {
            userMarker.setLatLng([userLat, userLng]);
        } else {
             var blueIcon = new L.Icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
            userMarker = L.marker([userLat, userLng], {icon: blueIcon}).addTo(map).bindPopup("<b>You are Here</b>");
            
            // Fit bounds to include user and hospitals
            var group = new L.featureGroup([...hospitalMarkers, userMarker]);
            map.fitBounds(group.getBounds().pad(0.1));
        }
    }

    function panToHospital(lat, lng) {
        if(!map) return;
        map.flyTo([lat, lng], 16);
        document.getElementById('hospital-map').scrollIntoView({behavior: 'smooth'});
    }

    function filterHospitals() {
        filterNearbyOnly = false;
        renderHospitals();
    }

    function searchNearby() {
        $('#hospitalSearch').val('');
        filterNearbyOnly = true;
        
        // Fallback to Bishop Heber College if GPS not ready
        if(!userLat || !userLng) {
            console.log("GPS not ready, using Bishop Heber College as reference.");
            var refLat = 10.8211;
            var refLng = 78.6934;
            renderHospitals(refLat, refLng);
            
            // Show a small notification
            const status = document.createElement('div');
            status.className = 'alert alert-info py-1 px-3 mt-2 text-xs';
            status.innerHTML = '<i class="fas fa-info-circle me-1"></i> GPS not ready. Showing hospitals near Bishop Heber College.';
            document.getElementById('hospital-list-container').prepend(status);
        } else {
            renderHospitals();
        }
    }

    function renderHospitals(refLat = null, refLng = null) {
        if(typeof hospitalsData === 'undefined') return;
        
        var search = $('#hospitalSearch').val().toLowerCase();
        var container = $('#hospital-list-container');
        container.empty();
        
        var filtered = hospitalsData.filter(h => {
            let name = (h.name || '').toLowerCase();
            let address = (h.address || '').toLowerCase();
            return name.includes(search) || address.includes(search);
        });
        
        // Use either passed reference (fallback) or live user LatLng
        var trackLat = refLat || userLat;
        var trackLng = refLng || userLng;
        
        // Calculate distances if any location known
        if(trackLat && trackLng) {
            filtered.forEach(h => {
                if(h.latitude && h.longitude) {
                    h.distance = calculateDistance(trackLat, trackLng, h.latitude, h.longitude);
                } else {
                    h.distance = 999999;
                }
            });
            // Sort by distance
            filtered.sort((a,b) => a.distance - b.distance);
            
            // FILTER ONLY NEARBY (< 15km)
            if(filterNearbyOnly) {
                filtered = filtered.filter(h => h.distance <= 15);
            }
        }
        
        if(filtered.length === 0) {
            container.html(`
                <div class="col-12 text-center py-5">
                    <p class="text-secondary mb-3">No hospitals found in our database for this area.</p>
                    <a href="https://www.google.com/maps/search/hospitals+near+me" target="_blank" class="btn btn-warning rounded-pill px-4">
                        <i class="fab fa-google me-2"></i> Search all hospitals on Google Maps
                    </a>
                </div>
            `);
            return;
        }
        
        filtered.forEach(h => {
            var distStr = (h.distance && h.distance < 9999) ? '<span class="badge bg-warning text-dark mb-2"><i class="fas fa-location-arrow me-1"></i> ' + h.distance.toFixed(1) + ' km away</span>' : '';
            var mapBtn = (h.latitude && h.longitude) ? `<button onclick="panToHospital(${h.latitude}, ${h.longitude})" class="btn btn-outline-info btn-sm rounded-pill"><i class="fas fa-map me-1"></i> Map</button>` : '';

            // Stock display
            var stockHtml = '<div class="d-flex flex-wrap gap-1 mb-3">';
            if(h.stocks && h.stocks.length > 0) {
                h.stocks.forEach(s => {
                    stockHtml += `<span class="badge bg-dark border border-secondary text-xs" style="padding: 2px 6px;">${s.blood_group} <b class="text-danger">${s.units}</b></span>`;
                });
            } else {
                stockHtml += '<small class="text-muted text-xs">No stock data</small>';
            }
            stockHtml += '</div>';

            var card = `
                <div class="col-md-6 col-lg-4">
                    <div class="card-box h-100 border border-secondary border-opacity-25 bg-dark d-flex flex-column">
                        <div class="flex-grow-1">
                            ${distStr}
                            <h5 class="fw-bold text-white mb-2">${h.name}</h5>
                            <p class="text-secondary text-sm mb-3"><i class="fas fa-map-marker-alt me-2"></i> ${h.address}</p>
                            
                            <h6 class="text-xs text-uppercase text-secondary fw-bold mb-2">Availability</h6>
                            ${stockHtml}
                        </div>
                        
                        <div class="d-grid gap-2 mt-auto">
                             <a href="tel:${h.contact_phone}" class="btn btn-outline-success btn-sm rounded-pill"><i class="fas fa-phone me-1"></i> ${h.contact_phone}</a>
                             <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(h.name + ' ' + h.address)}" target="_blank" class="btn btn-danger btn-sm rounded-pill"><i class="fas fa-directions me-1"></i> Direction</a>
                             ${mapBtn}
                        </div>
                    </div>
                </div>
            `;
            container.append(card);
        });

        // Add a "Discover More" card at the end
        container.append(`
            <div class="col-md-6 col-lg-4">
                <div class="card-box h-100 border border-warning border-opacity-25 d-flex flex-column justify-content-center align-items-center text-center p-4" style="background: rgba(255, 193, 7, 0.02);">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 mb-3">
                        <i class="fab fa-google fa-2x text-warning"></i>
                    </div>
                    <h6 class="text-white fw-bold">Searching for more?</h6>
                    <p class="text-secondary text-xs mb-3">Find all local hospitals directly on Google Maps.</p>
                    <a href="https://www.google.com/maps/search/hospitals+near+me" target="_blank" class="btn btn-warning btn-sm rounded-pill w-100 fw-bold">
                        Search Google Maps
                    </a>
                </div>
            </div>
        `);
    }
    
    function calculateDistance(lat1, lon1, lat2, lon2) {
        var R = 6371; // Radius of the earth in km
        var dLat = deg2rad(lat2-lat1);  // deg2rad below
        var dLon = deg2rad(lon2-lon1); 
        var a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
            Math.sin(dLon/2) * Math.sin(dLon/2)
            ; 
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
        var d = R * c; // Distance in km
        return d;
    }

    function deg2rad(deg) {
        return deg * (Math.PI/180)
    }

    // Initial render
    $(document).ready(function() {
        if(typeof renderHospitals === 'function' && typeof hospitalsData !== 'undefined') {
            // Default to Bishop Heber College for initial load if GPS not yet ready
            renderHospitals(10.8211, 78.6934);
        }
    });

    function acceptRequest(alertId) {
        if(!confirm("Are you sure you want to accept this request and share your live location?")) return;
        
        $.post('backend/sos_accept.php', { alert_id: alertId }, function(data) {
            try {
                var resp = JSON.parse(data);
                if(resp.status === 'success') {
                    // Redirect immediately without annoying popups
                    window.location.href = 'track.php?alert_id=' + resp.alert_id;
                } else {
                    console.error("Acceptance Failed:", resp.message);
                }
            } catch(e) {
                console.error("Network or Parse Error during SOS acceptance");
            }
        });
    }

    // --- FIREBASE CLOUD MESSAGING (FCM) ---

    // --- REAL-TIME SOS LISTENER FOR DONORS (FREE ALTERNATIVE) ---
    function listenForEmergencies() {
        if ("<?= $role ?>" !== 'donor') return;
        
        $.get('backend/fetch_alerts.php', function(html) {
            // Check if there are active alerts (not the 'No alerts' message)
            if (html.includes('alert-card')) {
                // Visual Alert
                if (!$('.nav-link[href="index.php?page=sos"]').hasClass('pulse-red')) {
                    $('#page-header').html('<span class="text-danger fw-bold"><i class="fas fa-biohazard fa-beat me-2"></i>URGENT BLOOD NEEDED!</span>');
                    $('.nav-link[href="index.php?page=sos"]').addClass('bg-danger text-white pulse-red');
                    
                    // Play Siren Sound
                    try {
                        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3');
                        audio.volume = 0.5;
                        audio.play();
                    } catch(e) { console.log("Audio play blocked by browser."); }
                }
                
                if ($('#sos-alerts-container').length) {
                    $('#sos-alerts-container').html(html);
                }
            } else {
                // Clear alerts if none found
                $('.nav-link[href="index.php?page=sos"]').removeClass('bg-danger text-white pulse-red');
            }
        });
    }
    
    // Check every 10 seconds
    setInterval(listenForEmergencies, 10000);
    setTimeout(listenForEmergencies, 2000);

    // Update the Acceptors List polling to include WhatsApp icons
    function pollAcceptors(alertId) {
        $.get('backend/fetch_acceptors.php?alert_id=' + alertId, function(res) {
            const acceptors = JSON.parse(res);
            if(acceptors.length > 0) {
                let html = '';
                acceptors.forEach(acc => {
                    const maskedPhone = acc.phone.substring(0, 4) + ' ••• ••• ' + acc.phone.substring(acc.phone.length - 3);
                    const waLink = `https://wa.me/91${acc.phone}?text=Hello ${acc.name}, I am the blood requester from the SOS app. Thank you for accepting!`;
                    
                    html += `
                        <div class="d-flex align-items-center p-3 bg-dark bg-opacity-50 rounded border border-success border-opacity-10 mb-2 transition-all">
                            <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                                <i class="fas fa-user-check text-success"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-white">${acc.name}</div>
                                <div class="text-xs text-secondary">
                                    Group: ${acc.blood_group} • 
                                    <span class="phone-masked" id="phone-mask-${acc.user_id}">${maskedPhone}</span>
                                    <span class="phone-full hidden" id="phone-full-${acc.user_id}">
                                        <a href="tel:${acc.phone}" class="text-success decoration-none font-weight-bold">${acc.phone}</a>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                <button onclick="revealContact('${acc.user_id}')" id="btn-reveal-${acc.user_id}" class="btn btn-xs btn-outline-success rounded-pill px-2 py-0 text-xs">
                                    <i class="fas fa-eye me-1"></i> Contact
                                </button>
                                <a href="${waLink}" target="_blank" class="btn btn-xs btn-success rounded-pill px-2 py-0 text-xs hidden" id="wa-btn-${acc.user_id}">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                    `;
                });
                $('#acceptors-list').html(html);
            }
        });
    }

    function revealContact(userId) {
        $(`#phone-mask-${userId}`).addClass('hidden');
        $(`#phone-full-${userId}`).removeClass('hidden').addClass('animate__animated animate__fadeIn');
        $(`#btn-reveal-${userId}`).addClass('hidden');
        $(`#wa-btn-${userId}`).removeClass('hidden').addClass('animate__animated animate__bounceIn');
    }
    // --- BACKGROUND LOCATION TRACKING FOR DONORS ---
    if ("<?= $role ?>" === 'donor') {
        if ("geolocation" in navigator) {
            navigator.geolocation.watchPosition(
                function(p) {
                    $.post('backend/update_location.php', { 
                        latitude: p.coords.latitude, 
                        longitude: p.coords.longitude 
                    });
                }, 
                function(e) { console.log("Location Error:", e); },
                { enableHighAccuracy: true, maximumAge: 30000, timeout: 27000 }
            );
        }
    }
</script>

<style>
@keyframes pulse-red {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 45, 85, 0.7); }
    70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(255, 45, 85, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 45, 85, 0); }
}
.pulse-red {
    animation: pulse-red 1.5s infinite !important;
}
</style>

<!-- Firebase App (the core Firebase SDK) is always required and must be listed first -->
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js"></script>

<script>
    // REPLACE WITH YOUR FIREBASE CONFIG FROM CONSOLE
    const firebaseConfig = {
        apiKey: "AIzaSyCccrVRro89pfJKDTVSxwy6MjlhCJ4bZdA",
        authDomain: "bhc-blood-finder.firebaseapp.com",
        projectId: "bhc-blood-finder",
        storageBucket: "bhc-blood-finder.firebasestorage.app",
        messagingSenderId: "163130966032",
        appId: "1:163130966032:web:9bce71cc4a3461913cca95",
        measurementId: "G-TXNZTQDJMN"
    };

    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();

    // Register Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('firebase-messaging-sw.js')
        .then(function(registration) {
            console.log('Registration successful, scope is:', registration.scope);
            messaging.useServiceWorker(registration);
            
            // Get Token
            messaging.getToken({ vapidKey: 'BBaLDkB0Ou9d3iFmOEITE0OBDYW6UqGanzhZX6ZZvGNHJYdo8l' }).then((currentToken) => {
                if (currentToken) {
                    console.log('FCM Token:', currentToken);
                    $.post('backend/update_fcm_token.php', { fcm_token: currentToken });
                }
            });
        }).catch(function(err) {
            console.log('Service worker registration failed, error:', err);
        });
    }

    // --- PWA OFFLINE DETECTION ---
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'testimonial_sent') {
            const toast = document.createElement('div');
            toast.style = "position:fixed; bottom:20px; left:50%; transform:translateX(-50%); background:#3b82f6; color:white; padding:12px 24px; border-radius:50px; z-index:9999; font-weight:bold; box-shadow:0 10px 20px rgba(0,0,0,0.3);";
            toast.innerHTML = '<i class="fas fa-heart me-2"></i> Thank you! Your story helps motivate our donors.';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    };

    window.addEventListener('online', () => {
        const toast = document.createElement('div');
        toast.style = "position:fixed; bottom:20px; left:50%; transform:translateX(-50%); background:#10b981; color:white; padding:12px 24px; border-radius:50px; z-index:9999; font-weight:bold; box-shadow:0 10px 20px rgba(0,0,0,0.3);";
        toast.innerHTML = '<i class="fas fa-wifi me-2"></i> You are back online!';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    });

    window.addEventListener('offline', () => {
        const toast = document.createElement('div');
        toast.style = "position:fixed; bottom:20px; left:50%; transform:translateX(-50%); background:#f59e0b; color:white; padding:12px 24px; border-radius:50px; z-index:9999; font-weight:bold; box-shadow:0 10px 20px rgba(0,0,0,0.3);";
        toast.innerHTML = '<i class="fas fa-plug text-dark me-2"></i> Offline Mode: Using cached hospital data';
        document.body.appendChild(toast);
        // Toast persists
        setTimeout(() => toast.remove(), 5000);
    });

    // Handle Foreground Messages
    messaging.onMessage((payload) => {
        console.log('Message received. ', payload);
        document.getElementById('emergencySound').play().catch(e => {});
        alert("🚨 SOS ALERT: " + payload.notification.body);
    });
</script>

    <!-- Audio Elements -->
    <audio id="dispatchSound" src="https://www.soundjay.com/communication/sounds/pager-beep-1.mp3" preload="auto"></audio>
    <audio id="emergencySound" src="https://assets.mixkit.co/active_storage/sfx/995/995-preview.mp3" preload="auto"></audio>

    <!-- DIGITAL ID CARD MODAL -->
    <div id="idCardModal" class="modal-overlay" onclick="closeIDCard(event)">
        <div class="modal-glass" onclick="event.stopPropagation()">
            <button class="close-btn" onclick="closeIDCard()">&times;</button>
            
            <div class="id-card-header text-center">
                <i class="fas fa-heartbeat text-danger fa-2x mb-2"></i>
                <h3 class="text-white fw-bold mb-0">BHC Blood Donor</h3>
                <span class="text-xs text-secondary tracking-widest uppercase">Digital Identity Card</span>
            </div>

            <div class="id-card-body text-center mt-4">
                <div class="scan-frame">
                    <img id="qr-code-img" src="" alt="Donor QR Code" class="img-fluid rounded" style="width: 180px; height: 180px;">
                    <div class="scanner-line"></div>
                </div>
                <p class="text-xs text-secondary mt-3 mb-1">Scan to Verify Status</p>
                <h4 class="text-white fw-bold mb-0"><?php echo htmlspecialchars($user_name); ?></h4>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 mt-2 px-3">
                    ID: <?php echo str_pad($user_id, 4, '0', STR_PAD_LEFT); ?>
                </span>
            </div>
            
            <div class="id-card-footer mt-4 pt-3 border-top border-secondary border-opacity-25 text-center">
                <small class="text-secondary text-xs">Official Bishop Heber College Record</small>
            </div>
        </div>
    </div>

    <style>
    /* ID Card Modal Styles */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85); z-index: 2000;
        display: flex; align-items: center; justify-content: center;
        backdrop-filter: blur(8px);
        opacity: 0; pointer-events: none; transition: all 0.3s;
    }
    .modal-overlay.active { opacity: 1; pointer-events: auto; }

    .modal-glass {
        background: rgba(20, 20, 25, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        border-radius: 24px;
        padding: 30px;
        width: 90%; max-width: 340px;
        position: relative;
        transform: translateY(20px); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .modal-overlay.active .modal-glass { transform: translateY(0); }

    .scan-frame {
        position: relative;
        padding: 15px; background: #fff; border-radius: 16px;
        display: inline-block;
        box-shadow: 0 0 30px rgba(255, 45, 85, 0.2);
    }
    .scanner-line {
        position: absolute; top: 0; left: 0; width: 100%; height: 3px;
        background: #ff2d55;
        box-shadow: 0 0 10px #ff2d55;
        animation: scan 2s infinite linear;
    }
    @keyframes scan { 0% {top:0} 50% {top:100%} 100% {top:0} }

    .close-btn {
        position: absolute; top: 15px; right: 20px;
        background: none; border: none; color: #aaa; font-size: 1.5rem; cursor: pointer;
        z-index: 10;
    }
    </style>

    <script>
    function openIDCard() {
        const userId = <?php echo $user_id; ?>;
        // Use current domain for the verification URL
        const domain = window.location.origin + window.location.pathname.replace('index.php', '');
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${domain}verify_donor.php?id=${userId}`;
        
        document.getElementById('qr-code-img').src = qrUrl;
        document.getElementById('idCardModal').classList.add('active');
    }
    function closeIDCard(e) {
        // If e is present, check if click target is the overlay itself
        if(e && e.target !== e.currentTarget) return;
        document.getElementById('idCardModal').classList.remove('active');
    }
    </script>
</body>
</html>
</body>
</html>
