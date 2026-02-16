<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header("Location: otp_verify.php"); // Check 2FA or Login
    exit();
}

include '../backend/db_connect.php';

// --- DATA FETCHING ---

// 1. Stats
$active_alerts_count = $conn->query("SELECT COUNT(*) FROM sos_alerts WHERE status = 'active'")->fetchColumn();
$total_camps = $conn->query("SELECT COUNT(*) FROM blood_camps")->fetchColumn();
$total_students = $conn->query("SELECT COUNT(*) FROM preloaded_students")->fetchColumn();

// 2. Fetch Hospitals
$hospitals = $conn->query("SELECT * FROM hospitals ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Camps
$camps = $conn->query("SELECT * FROM blood_camps ORDER BY camp_date")->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch Active Alerts
$alerts = $conn->query("SELECT a.*, u.name as requester_name, u.phone as requester_phone 
                        FROM sos_alerts a 
                        JOIN users u ON a.requester_id = u.user_id 
                        WHERE a.status = 'active' 
                        ORDER BY a.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// 5. Fetch Registry (Limit 1000)
$registry = $conn->query("SELECT * FROM preloaded_students ORDER BY created_at DESC LIMIT 1000")->fetchAll(PDO::FETCH_ASSOC);

// 6. Fetch Completed Donations
$history = $conn->query("SELECT dh.*, u.name as donor_name, u.blood_group, h.name as hospital_name 
                         FROM donation_history dh 
                         JOIN users u ON dh.donor_id = u.user_id 
                         LEFT JOIN hospitals h ON dh.hospital_id = h.hospital_id
                         ORDER BY dh.completed_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

// 7. Fetch Active Donors
$donors_list = $conn->query("SELECT * FROM users WHERE role = 'donor' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Community Blood Donor Finder - Bishop Heber College</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary: #ff2d55;
            --primary-glow: rgba(255, 45, 85, 0.4);
            --secondary: #94a3b8;
            --bg-body: #050505;
            --bg-card: rgba(20, 20, 25, 0.4);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --sidebar-width: 280px;
            --glass-border: rgba(255, 255, 255, 0.08);
        }
        
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)), url('../bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: rgba(10, 10, 10, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            height: 90px;
            display: flex;
            align-items: center;
            padding: 0 2rem;
            border-bottom: 1px solid var(--glass-border);
        }

        .brand-text {
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            letter-spacing: -0.5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 16px 24px;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 1rem;
            border-radius: 16px;
            margin: 10px 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.03);
            color: #fff;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, #b31217 100%);
            box-shadow: 0 10px 25px var(--primary-glow);
            color: #fff;
        }

        .nav-link i {
            width: 28px;
            font-size: 1.2rem;
            margin-right: 12px;
            text-align: center;
        }

        /* --- Main Content --- */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        /* Top Header */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            padding: 24px 32px;
            border-radius: 24px;
            border: 1px solid var(--glass-border);
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

        /* Tables */
        .table {
            color: var(--text-secondary);
            --bs-table-bg: transparent;
            --bs-table-color: #fff;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .table thead th {
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 1px;
            color: #8a93a2;
            border: none;
            padding: 1.2rem 1rem;
            background-color: transparent !important;
            font-weight: 600;
        }
        .table tbody tr {
            background-color: rgba(255, 255, 255, 0.02);
            transition: all 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
            transform: scale(1.002);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .table tbody td {
            color: #fff !important;
            padding: 1.2rem 1rem;
            border: none;
            vertical-align: middle;
            background-color: transparent !important;
        }
        .table tbody tr td:first-child { border-radius: 12px 0 0 12px; }
        .table tbody tr td:last-child { border-radius: 0 12px 12px 0; }
        
        .badge {
            padding: 0.5em 0.8em;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        .badge.bg-danger {
            background: linear-gradient(135deg, #ff4d6d, #ff1744) !important;
        }
        .badge.bg-dark {
            background: rgba(255,255,255,0.05) !important;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.1) !important;
        }
        
        .table-responsive {
            background: transparent;
            padding: 5px;
        }
        
        /* Mobile */
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); box-shadow: none; }
            .sidebar.show { transform: translateX(0); box-shadow: 0 0 50px rgba(0,0,0,0.5); }
            .main-content { margin-left: 0; }
        }
        
        /* Helper Classes */
        .text-white-50 { color: rgba(255,255,255,0.5) !important; }
        .text-secondary { color: #8a93a2 !important; }
        .hidden { display: none !important; }

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
        
        /* Ensure file input button is visible */
        .form-control::file-selector-button {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: none;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.375rem 0.75rem;
            margin-right: 0.75rem;
            border-radius: 0;
            transition: all 0.2s;
        }
        .form-control::file-selector-button:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

    </style>
</head>
<body>

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
        <a class="nav-link active" data-target="dashboard">
            <i class="fas fa-th-large"></i> <span>Dashboard</span>
        </a>
        <a class="nav-link" data-target="hospitals">
            <i class="fas fa-hospital"></i> <span>Hospital Stocks</span>
        </a>
        <a class="nav-link" data-target="camps">
            <i class="fas fa-campground"></i> <span>Donation Camps</span>
        </a>
        <a class="nav-link" data-target="alerts">
            <i class="fas fa-exclamation-circle"></i> <span>Active Alerts</span>
        </a>
        <a class="nav-link" data-target="registry">
            <i class="fas fa-users"></i> <span>Student Registry</span>
        </a>
        <a class="nav-link" data-target="donors">
            <i class="fas fa-user-check"></i> <span>Manage Donors</span>
        </a>
        <a class="nav-link" data-target="donations">
            <i class="fas fa-file-medical"></i> <span>All Donations</span>
        </a>
        <a class="nav-link" data-target="logs">
            <i class="fas fa-terminal"></i> <span>System Logs</span>
        </a>
        
        <div class="my-4 border-top border-secondary opacity-25 mx-3"></div>
        
        <a href="../logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i> <span>Sign Out</span>
        </a>
    </div>
</nav>

<!-- MAIN CONTENT -->
<main class="main-content">
    
    <!-- TOP HEADER -->
    <div class="top-header">
        <div>
            <!-- Removed Breadcrumb -->
            <h5 class="page-title mt-1" id="page-header">Overview</h5>
        </div>
        <div class="d-flex align-items-center">
             <!-- Mobile Toggle -->
            <button class="btn btn-link text-white d-lg-none p-0 me-3" onclick="$('#sidebar').toggleClass('show')">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none text-white">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-2 me-2">
                         <i class="fas fa-user-shield text-danger"></i>
                    </div>
                    <span class="d-none d-sm-inline font-weight-bold">Administrator</span>
                </a>
            </div>
        </div>
    </div>

    <!-- NOTIFICATIONS AREA -->
    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show text-white" role="alert" style="background: rgba(var(--bs-<?php echo $_SESSION['message_type'] ?? 'info'; ?>-rgb), 0.2); border-color: rgba(var(--bs-<?php echo $_SESSION['message_type'] ?? 'info'; ?>-rgb), 0.4);">
        <strong><?php echo $_SESSION['message_type'] == 'success' ? 'Success!' : 'Notice:'; ?></strong> <?php echo $_SESSION['message']; ?>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php 
        unset($_SESSION['message']); 
        unset($_SESSION['message_type']);
    endif; 
    ?>

    <!-- 1. DASHBOARD VIEW -->
    <div id="view-dashboard" class="content-view">
        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card-box stat-card">
                    <div>
                        <p class="text-sm mb-1 text-secondary font-weight-bold">Active Alerts</p>
                        <h3 class="mb-0 fw-bold text-white"><?php echo $active_alerts_count; ?></h3>
                    </div>
                    <div class="stat-icon bg-gradient-danger">
                        <i class="fas fa-bell"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card-box stat-card">
                    <div>
                        <p class="text-sm mb-1 text-secondary font-weight-bold">Total Students</p>
                        <h3 class="mb-0 fw-bold text-white"><?php echo $total_students; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10">
                        <i class="fas fa-id-card text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card-box stat-card">
                    <div>
                        <p class="text-sm mb-1 text-secondary font-weight-bold">Registered Camps</p>
                        <h3 class="mb-0 fw-bold text-white"><?php echo $total_camps; ?></h3>
                    </div>
                    <div class="stat-icon bg-gradient-info">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Blood Stock Overview -->
            <div class="col-lg-8">
                <div class="card-box">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0 fw-bold text-white">Blood Stock Availability</h5>
                        <button onclick="$('.nav-link[data-target=\'hospitals\']').click()" class="btn btn-sm btn-outline-light rounded-pill px-3">Manage</button>
                    </div>
                    <div class="row g-3">
                        <?php 
                        $groups = ['A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-', 'AB-'];
                        foreach($groups as $bg):
                            $count = $conn->query("SELECT IFNULL(SUM(units), 0) FROM blood_inventory WHERE blood_group = '$bg'")->fetchColumn();
                        ?>
                        <div class="col-6 col-md-3 text-center">
                            <div class="p-3 border border-secondary border-opacity-25 rounded" style="background: rgba(255,255,255,0.02);">
                                <h4 class="mb-1 fw-bold text-white"><?php echo $count; ?></h4>
                                <small class="text-danger fw-bold"><?php echo $bg; ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card-box">
                    <h5 class="mb-4 fw-bold text-white">Quick Actions</h5>
                    <div class="d-grid gap-3">
                        <a href="add_student.php" class="btn btn-outline-success text-start rounded-pill py-2">
                            <i class="fas fa-user-plus me-2"></i> Add New Student
                        </a>
                        <button onclick="$('#addCampModal').fadeIn()" class="btn btn-outline-info text-start rounded-pill py-2">
                            <i class="fas fa-calendar-plus me-2"></i> Schedule Camp
                        </button>
                        <button onclick="$('.nav-link[data-target=\'registry\']').click()" class="btn btn-outline-light text-start rounded-pill py-2">
                            <i class="fas fa-search me-2"></i> Search Registry
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 2. HOSPITAL STOCKS VIEW -->
    <div id="view-hospitals" class="content-view hidden">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-box">
                    <h5 class="mb-4 fw-bold text-white">Update Stock</h5>
                    <form id="stockForm" action="manage_stocks.php" method="POST">
                        <input type="hidden" name="action" value="update_stock">
                        <div class="mb-3">
                            <label class="form-label text-sm text-secondary">Hospital</label>
                            <select name="hospital_id" class="form-select" required>
                                <?php foreach($hospitals as $h): ?>
                                <option value="<?php echo $h['hospital_id']; ?>"><?php echo htmlspecialchars($h['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label text-sm text-secondary">Group</label>
                                <select name="blood_group" class="form-select">
                                    <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                                    <option>O+</option><option>O-</option><option>AB+</option><option>AB-</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-sm text-secondary">Units</label>
                                <input type="number" name="units" class="form-control" required min="0">
                            </div>
                        </div>
                        <button class="btn btn-danger w-100 rounded-pill fw-bold">Update Stock</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card-box pb-0">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0 text-white">Hospital Inventory</h5>
                        <a href="add_hospital.php" class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="fas fa-plus me-1"></i> Add Hospital
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead><tr><th>Hospital</th><th>Stock Levels</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($hospitals as $h): 
                                    $stocks = $conn->query("SELECT * FROM blood_inventory WHERE hospital_id=".$h['hospital_id'])->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <tr>
                                    <td>
                                        <h6 class="mb-0 text-white"><?php echo htmlspecialchars($h['name']); ?></h6>
                                        <small class="text-secondary"><?php echo $h['contact_phone']; ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach($stocks as $s): 
                                                $is_low = ($s['units'] < 3);
                                            ?>
                                                <div class="badge <?php echo $is_low ? 'bg-danger bg-opacity-10 border-danger text-danger' : 'bg-dark border-secondary text-white'; ?> border d-flex align-items-center gap-2 p-2 px-3">
                                                    <span class="fw-bold"><?php echo $s['blood_group']; ?>: <?php echo $s['units']; ?></span>
                                                    <div class="d-flex gap-2 border-start border-secondary ps-2">
                                                        <?php if($is_low): ?>
                                                            <form action="broadcast_stock.php" method="POST" class="d-inline" onsubmit="return confirm('Broadcast Stock Request for <?php echo $s['blood_group']; ?> to all donors?')">
                                                                <input type="hidden" name="blood_group" value="<?php echo $s['blood_group']; ?>">
                                                                <input type="hidden" name="hospital_id" value="<?php echo $h['hospital_id']; ?>">
                                                                <button type="submit" class="text-warning border-0 bg-transparent p-0" title="Broadcast Stock Request">
                                                                    <i class="fas fa-bullhorn"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <a href="javascript:void(0)" onclick="editStock('<?php echo $h['hospital_id']; ?>', '<?php echo $s['blood_group']; ?>', '<?php echo $s['units']; ?>')" class="text-info text-xs" title="Edit Stock">
                                                            <i class="fas fa-pencil-alt"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if(!empty($stocks)): ?>
                                            <div class="text-xs text-secondary mt-2">
                                                <i class="fas fa-clock me-1"></i> Last Sync: <?php echo $stocks[0]['updated_at']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="add_hospital.php?edit=<?php echo $h['hospital_id']; ?>" class="text-info me-2"><i class="fas fa-edit"></i></a>
                                        <a href="add_hospital.php?delete=<?php echo $h['hospital_id']; ?>" class="text-danger" onclick="return confirm('Delete this hospital?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 3. CAMPS VIEW -->
    <div id="view-camps" class="content-view hidden">
        <div class="card-box">
             <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0 fw-bold text-white">Donation Camps</h5>
                <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="$('#addCampModal').fadeIn()">
                    <i class="fas fa-plus me-1"></i> Add Camp
                </button>
            </div>
            <div class="table-responsive">
                <table class="table align-items-center mb-0">
                    <thead><tr><th>Date</th><th>Title</th><th>Location</th><th>Organizer</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($camps as $c): ?>
                        <tr>
                            <td>
                                <h6 class="mb-0 text-sm text-danger"><?php echo $c['camp_date']; ?></h6>
                                <p class="text-xs text-secondary mb-0"><?php echo date('h:i A', strtotime($c['start_time'])); ?></p>
                            </td>
                            <td><span class="text-sm font-weight-bold text-white"><?php echo htmlspecialchars($c['title']); ?></span></td>
                            <td><span class="text-xs text-white"><?php echo htmlspecialchars($c['location']); ?></span></td>
                            <td><span class="text-xs text-secondary"><?php echo htmlspecialchars($c['organized_by']); ?></span></td>
                            <td>
                                <a href="manage_camps.php?delete=<?php echo $c['camp_id']; ?>" class="text-danger text-xs font-weight-bold p-2" onclick="return confirm('Cancel?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- 4. ALERTS VIEW -->
    <div id="view-alerts" class="content-view hidden">
        <div class="row g-4">
            <?php foreach($alerts as $a): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-box border-start border-4 border-danger position-relative">
                    <span class="position-absolute top-0 end-0 m-3 badge bg-danger animate-pulse">LIVE SOS</span>
                    <h5 class="fw-bold text-danger mb-1"><?php echo $a['blood_group']; ?> Needed</h5>
                    <div class="mb-2">
                        <small class="text-white d-block"><i class="fas fa-map-pin text-danger me-1"></i> <?php echo htmlspecialchars($a['location_name'] ?? 'Location not available'); ?></small>
                        <small class="text-secondary">Req ID: #<?php echo $a['alert_id']; ?></small>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-dark border border-secondary p-2 me-3 text-center" style="width:40px;height:40px;">
                            <i class="fas fa-user text-secondary"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-sm text-white"><?php echo $a['requester_name']; ?></h6>
                            <p class="text-xs mb-0 text-secondary"><?php echo $a['requester_phone']; ?></p>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="../track.php?alert_id=<?php echo $a['alert_id']; ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-pill">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i> Track Donors
                        </a>
                        <a href="confirm_donation.php?alert_id=<?php echo $a['alert_id']; ?>" class="btn btn-danger btn-sm rounded-pill">
                            Mark as Fulfilled
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
                <div class="card-box">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0 text-white">Live SOS Responses</h5>
                        <button onclick="location.reload()" class="btn btn-outline-secondary btn-sm rounded-pill">
                            <i class="fas fa-sync-alt me-1"></i> Refresh Board
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0 text-white">
                            <thead>
                                <tr class="text-secondary">
                                    <th>Donor Name</th>
                                    <th>Blood Group</th>
                                    <th>Responding To</th>
                                    <th>Status</th>
                                    <th>Accepted At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $responses = $conn->query("SELECT r.*, u.name as donor_name, u.blood_group as donor_bg, req.name as requester_name, s.blood_group as req_bg 
                                                           FROM sos_responses r 
                                                           JOIN users u ON r.donor_id = u.user_id 
                                                           JOIN sos_alerts s ON r.alert_id = s.alert_id 
                                                           JOIN users req ON s.requester_id = req.user_id 
                                                           WHERE r.status = 'accepted' 
                                                           ORDER BY r.accepted_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
                                if(empty($responses)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-secondary">No active responses at the moment.</td></tr>
                                <?php else: 
                                    foreach($responses as $res): ?>
                                    <tr>
                                        <td><b class="text-white"><?php echo htmlspecialchars($res['donor_name']); ?></b></td>
                                        <td><span class="badge bg-danger"><?php echo $res['donor_bg']; ?></span></td>
                                        <td>
                                            <small class="text-secondary">#<?php echo $res['alert_id']; ?>: <?php echo htmlspecialchars($res['requester_name']); ?></small><br>
                                            <span class="text-xs">Needs: <b><?php echo $res['req_bg']; ?></b></span>
                                        </td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">On the way</span></td>
                                        <td><span class="text-xs text-secondary"><?php echo date('H:i', strtotime($res['accepted_at'])); ?></span></td>
                                        <td>
                                            <a href="../track.php?alert_id=<?php echo $res['alert_id']; ?>" target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-3 me-1">
                                                <i class="fas fa-map-marker-alt"></i> Track
                                            </a>
                                            <a href="confirm_donation.php?alert_id=<?php echo $res['alert_id']; ?>" class="btn btn-sm btn-success rounded-pill px-3">
                                                Complete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 5. REGISTRY VIEW -->
    <div id="view-registry" class="content-view hidden">
        <div class="card-box">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0 fw-bold text-white">Student Registry</h5>
                <div class="d-flex align-items-center">
                    <div class="input-group input-group-sm me-3" style="width: 250px;">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fas fa-search"></i></span>
                        <input type="text" id="registrySearch" class="form-control" placeholder="Search by name or reg no...">
                    </div>
                    <a href="cleanup_messy_data.php" class="btn btn-outline-danger btn-sm rounded-pill me-2" onclick="return confirm('⚠️ Remove all students with misaligned data rows? This helps fix messed up CSV uploads.')">
                        <i class="fas fa-broom me-1"></i> Cleanup Data
                    </a>
                    <a href="upload_students.php?undo=1" class="btn btn-outline-warning btn-sm rounded-pill me-2" onclick="return confirm('⚠️ Undo last bulk upload? This will DELETE all students from the last batch.')">
                        <i class="fas fa-undo me-1"></i> Undo Last Upload
                    </a>
                    <button class="btn btn-primary btn-sm rounded-pill" onclick="$('#uploadModal').fadeIn()">
                        <i class="fas fa-file-upload me-1"></i> Bulk Upload CSV
                    </button>
                    <a href="csv_template.php" class="btn btn-outline-secondary btn-sm rounded-pill ms-2" title="Download Template">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table align-items-center mb-0 text-center" id="registryTable">
                    <thead>
                        <tr>
                            <th class="text-start">Reg No</th>
                            <th class="text-start">Name</th>
                            <th>DOB</th>
                            <th>Age</th>
                            <th class="text-start">Health Eligibility</th>
                            <th>Blood Group</th>
                            <th class="text-start">Contact Details</th>
                            <th class="text-start">Mail ID</th>
                            <th>Contact Number</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($registry as $s): ?>
                        <tr>
                            <td class="text-start"><span class="text-white fw-bold"><?php echo $s['register_number']; ?></span></td>
                            <td class="text-start"><h6 class="mb-0 text-sm text-white"><?php echo htmlspecialchars($s['name']); ?></h6></td>
                            <td><span class="badge bg-dark border border-secondary text-xs"><?php echo date('d-M-Y', strtotime($s['dob'])); ?></span></td>
                            <td><span class="text-secondary"><?php echo htmlspecialchars($s['age'] ?? '-'); ?></span></td>
                            
                            <td class="text-start">
                                <?php if(!empty($s['health_eligibility'])): ?>
                                    <span class="badge bg-dark border border-secondary"><?php echo htmlspecialchars($s['health_eligibility']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted text-xs">-</span>
                                <?php endif; ?>
                            </td>
                            
                            <td><span class="badge bg-danger"><?php echo $s['blood_group']; ?></span></td>
                            
                            <td class="text-start"><small class="text-secondary"><?php echo htmlspecialchars($s['address'] ?? '-'); ?></small></td>
                            
                            <td class="text-start"><span class="text-xs text-white"><?php echo $s['email']; ?></span></td>
                            <td><span class="text-xs text-white"><?php echo $s['phone']; ?></span></td>
                            
                            <td>
                                <a href="add_student.php?edit=<?php echo $s['register_number']; ?>" class="text-info me-2"><i class="fas fa-edit"></i></a>
                                <a href="add_student.php?delete=<?php echo $s['register_number']; ?>" class="text-danger" onclick="return confirm('Delete this student?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 5.1 MANAGE DONORS VIEW -->
    <div id="view-donors" class="content-view hidden">
        <div class="card-box">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0 fw-bold text-white">Activated Donors (Active Accounts)</h5>
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fas fa-search"></i></span>
                    <input type="text" id="donorSearch" class="form-control" placeholder="Search by name or reg no...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-items-center mb-0" id="donorTable">
                    <thead>
                        <tr>
                            <th>Reg No</th>
                            <th>Name</th>
                            <th>Blood Group</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($donors_list)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-secondary">No activated donors found.</td></tr>
                        <?php else: foreach($donors_list as $donor): ?>
                        <tr>
                            <td><span class="text-white fw-bold"><?php echo $donor['register_number'] ?? '-'; ?></span></td>
                            <td><span class="text-white"><?php echo htmlspecialchars($donor['name']); ?></span></td>
                            <td><span class="badge bg-danger"><?php echo $donor['blood_group']; ?></span></td>
                            <td><small class="text-white-50"><?php echo $donor['email']; ?></small></td>
                            <td><small class="text-white-50"><?php echo $donor['phone']; ?></small></td>
                            <td><span class="text-success fw-bold"><?php echo $donor['points']; ?> pts</span></td>
                            <td><span class="badge bg-dark"><?php echo $donor['availability_status']; ?></span></td>
                            <td>
                                <a href="add_student.php?edit=<?php echo $donor['register_number']; ?>" class="text-info me-2"><i class="fas fa-edit"></i></a>
                                <a href="add_student.php?delete=<?php echo $donor['register_number']; ?>" class="text-danger" onclick="return confirm('Delete this donor account and registry record?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 7. SYSTEM LOGS VIEW -->
    <div id="view-logs" class="content-view hidden">
        <div class="card-box">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-1 fw-bold text-white">📡 Communication & System Logs</h5>
                    <p class="text-secondary text-sm mb-0">Monitor SMS API responses and background worker tasks</p>
                </div>
                <button class="btn btn-outline-info btn-sm rounded-pill px-3" onclick="loadLogs()">
                    <i class="fas fa-sync-alt me-1"></i> Refresh Logs
                </button>
            </div>
            <div id="logContent" class="bg-black bg-opacity-50 rounded p-3" style="max-height: 600px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.05);">
                <div class="text-center py-4"><div class="spinner-border text-info spinner-border-sm"></div> Loading logs...</div>
            </div>
        </div>
    </div>

    <!-- 6. ALL DONATIONS VIEW -->
    <div id="view-donations" class="content-view hidden">
        <div class="card-box">
            <h5 class="mb-4 fw-bold text-white">All Successful Donations</h5>
            <div class="table-responsive">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Donor Name</th>
                            <th>Group</th>
                            <th>SOS ID</th>
                            <th>Donated At</th>
                            <th>Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($history)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-secondary">No donation records found.</td></tr>
                        <?php else: foreach($history as $h_item): ?>
                        <tr>
                            <td><span class="text-white text-sm"><?php echo date('d-M-Y H:i', strtotime($h_item['completed_at'])); ?></span></td>
                            <td><b class="text-white"><?php echo htmlspecialchars($h_item['donor_name']); ?></b></td>
                            <td><span class="badge bg-danger"><?php echo $h_item['blood_group']; ?></span></td>
                            <td><span class="text-secondary">#<?php echo $h_item['alert_id'] ?? '-'; ?></span></td>
                            <td><small class="text-white"><?php echo htmlspecialchars($h_item['hospital_name'] ?? 'Hospital Donation'); ?></small></td>
                            <td><span class="badge bg-success bg-opacity-10 text-success">+<?php echo $h_item['points_earned'] ?? '50'; ?> pts</span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- UPLOAD MODAL -->
    <div id="uploadModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:2000; backdrop-filter: blur(5px);">
        <div class="card border-0 shadow-lg p-0 bg-dark" style="width:500px; margin: 50px auto; max-width:90%; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
            <div class="card-header border-bottom border-secondary p-3 d-flex justify-content-between align-items-center">
                 <h5 class="mb-0 fw-bold text-white">Bulk Student Upload</h5>
                 <button type="button" class="btn-close btn-close-white" onclick="$('#uploadModal').fadeOut()"></button>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info bg-transparent border-info text-info text-xs mb-3">
                    <i class="fas fa-info-circle me-1"></i> 
                    <strong>Required CSV Format:</strong><br>
                    Reg_No, Name, Age, Blood Group, Health Condition, <b>DOB (e.g., 18-Feb-2005)</b>, Status, Address, Email, Phone
                </div>
                
                <form action="upload_students.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label text-white">Select CSV File</label>
                        <input type="file" name="student_csv" class="form-control" accept=".csv" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success rounded-pill fw-bold">
                            <i class="fas fa-upload me-2"></i> Upload Students
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</main>

<!-- ADD CAMP MODAL -->
<div id="addCampModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:2000; backdrop-filter: blur(5px);">
    <div class="card border-0 shadow-lg p-0 bg-dark" style="width:500px; margin: 50px auto; max-width:90%; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
        <div class="card-header border-bottom border-secondary p-3">
             <h5 class="mb-0 fw-bold text-white">Schedule Donation Camp</h5>
        </div>
        <div class="card-body p-4">
            <form action="manage_camps.php" method="POST">
                <input type="hidden" name="action" value="add_camp">
                <div class="mb-3">
                    <label class="form-label text-white text-sm">Camp Title</label>
                    <input type="text" name="camp_title" class="form-control" placeholder="e.g. Heber Blood Drive 2026" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-white text-sm">Location</label>
                    <input type="text" name="camp_location" class="form-control" placeholder="e.g. GJ Block Ground" required>
                </div>
                <div class="row mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label text-white text-sm">Date</label>
                        <input type="date" name="camp_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label text-white text-sm">Start Time</label>
                        <input type="time" name="camp_start" class="form-control" required>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label text-white text-sm">End Time</label>
                        <input type="time" name="camp_end" class="form-control">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-white text-sm">Organized By</label>
                        <input type="text" name="camp_org" class="form-control" placeholder="e.g. NCC / Red Cross" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white text-sm">Contact Phone</label>
                        <input type="tel" name="camp_phone" class="form-control" placeholder="10 digit number" pattern="[0-9]{10}" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-white text-sm">Description (Optional)</label>
                    <textarea name="camp_desc" class="form-control" rows="2" placeholder="Brief details about the camp..."></textarea>
                </div>
                <button class="btn btn-danger w-100 rounded-pill fw-bold">Schedule Event</button>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Simple Tab Switcher with Fluid Transitions
        $('.nav-link').click(function() {
            var $newLink = $(this);
            var target = $newLink.data('target');
            if(target) {
                var $currentView = $('.content-view:not(.hidden)');
                var $newView = $('#view-' + target);

                if ($currentView.attr('id') === 'view-' + target) return;

                // 1. Exit Animation for Current View
                $currentView.removeClass('page-entering').addClass('page-exiting');
                
                // 2. Navigation State Update (Visual Feedback)
                $('.nav-link').removeClass('active');
                $newLink.addClass('active');

                // 3. Wait for Exit Animation (0.8s) + 0.6s Delay
                setTimeout(function() {
                    $currentView.addClass('hidden').removeClass('page-exiting');
                    
                    setTimeout(function() {
                        // 4. Entrance Animation for New View
                        $newView.removeClass('hidden').addClass('page-entering');
                        
                        // Update headers
                        var title = $newLink.find('span').text();
                        $('#page-header').text(title);
                        $('#page-breadcrumb').text(title);
                        
                        if($(window).width() < 992) $('#sidebar').removeClass('show');

                        // Cleanup animation class
                        setTimeout(function() {
                            $newView.removeClass('page-entering');
                        }, 1000);
                    }, 600); // 0.6s Delay between pages
                }, 800); // 0.8s Exit Animation
            }
        });
        // Auto-refresh Live Responses every 20 seconds
        setInterval(function() {
            if($('#view-alerts').is(':visible')) {
                // We reload the page quietly or just fetch the table if we had a dedicated endpoint.
                // For now, since it's PHP integrated, we'll suggest a manual refresh or 
                // we can add a simple location.reload() if on that tab.
                // Better: Let's add a "Refresh" button to the header instead of auto-reloading the whole page.
            }
        }, 20000);
    });

    function editStock(hospitalId, group, units) {
        let form = $('#stockForm');
        form.find('select[name="hospital_id"]').val(hospitalId);
        form.find('select[name="blood_group"]').val(group);
        form.find('input[name="units"]').val(units);
        
        // Scroll to form and highlight
        $('html, body').animate({
            scrollTop: $("#stockForm").offset().top - 100
        }, 500);
        
        $("#stockForm").parent().addClass('border-info').css('box-shadow', '0 0 15px rgba(0, 150, 255, 0.3)');
        setTimeout(() => {
            $("#stockForm").parent().removeClass('border-info').css('box-shadow', '');
        }, 2000);
    }

    // Registry Search Filter
    $('#registrySearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#registryTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Donor Search Filter
    $('#donorSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#donorTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    function loadLogs() {
        $('#logContent').load('get_system_logs.php');
    }

    // Nav Link Click Handling with log loading
    $('.nav-link').on('click', function() {
        const target = $(this).data('target');
        if(target === 'logs') loadLogs();
    });
</script>

</body>
</html>
