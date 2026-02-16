<?php
session_start();
if (!isset($_SESSION['hospital_id'])) {
    header("Location: index.php");
    exit();
}
include '../backend/db_connect.php';

$hid = $_SESSION['hospital_id'];
$hname = $_SESSION['hospital_name'];

// Fetch current inventory
$stmt = $conn->prepare("SELECT * FROM blood_inventory WHERE hospital_id = ?");
$stmt->execute([$hid]);
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map inventory by blood group for easy access
$stock = [];
foreach($inventory as $item) {
    $stock[$item['blood_group']] = $item['units'];
}

$blood_groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stock'])) {
    foreach ($blood_groups as $bg) {
        $units = (int)($_POST['units'][$bg] ?? 0);
        
        // Check if exists
        $check = $conn->prepare("SELECT stock_id FROM blood_inventory WHERE hospital_id = ? AND blood_group = ?");
        $check->execute([$hid, $bg]);
        
        if ($check->rowCount() > 0) {
            $upd = $conn->prepare("UPDATE blood_inventory SET units = ?, updated_at = CURRENT_TIMESTAMP WHERE hospital_id = ? AND blood_group = ?");
            $upd->execute([$units, $hid, $bg]);
        } else {
            $ins = $conn->prepare("INSERT INTO blood_inventory (hospital_id, blood_group, units) VALUES (?, ?, ?)");
            $ins->execute([$hid, $bg, $units]);
        }
    }
    header("Location: dashboard.php?success=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hospital Dashboard - Blood Sync</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --bg-body: #050505;
            --bg-card: rgba(20, 20, 25, 0.4);
            --glass-border: rgba(255, 255, 255, 0.08);
        }
        body {
            background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url('../bg.jpg');
            background-size: cover;
            background-attachment: fixed;
            color: #f8fafc;
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
        }
        .navbar {
            background: rgba(10, 10, 10, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
        }
        .main-container {
            padding: 100px 20px 40px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .card-box {
            background: var(--bg-card);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .blood-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        .blood-card:hover { border-color: var(--primary); transform: translateY(-3px); }
        .group-label {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        .form-control {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--glass-border);
            color: white;
            text-align: center;
            font-weight: bold;
        }
        .status-badge {
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 50px;
            text-transform: uppercase;
        }
        .status-normal { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-low { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .status-critical { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg border-bottom border-white border-opacity-10 fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold text-white d-flex align-items-center" href="#">
        <i class="fas fa-hospital-user text-primary me-2"></i> Hospital Sync Portal
    </a>
    <div class="ms-auto d-flex align-items-center">
        <span class="text-secondary small me-3 d-none d-sm-inline">Logged in as: <b class="text-white"><?php echo htmlspecialchars($hname); ?></b></span>
        <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout</a>
    </div>
  </div>
</nav>

<div class="main-container">
    <div class="card-box mb-4">
        <div class="d-flex align-items-center mb-4">
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                <i class="fas fa-sync-alt fa-lg text-primary"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">Real-Time Inventory Sync</h4>
                <p class="text-secondary mb-0">Update your blood units to notify the BHC Community</p>
            </div>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success bg-success bg-opacity-10 border-0 text-success rounded-3 mb-4">
                <i class="fas fa-check-circle me-1"></i> Inventory synchronized successfully!
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-4">
                <?php foreach($blood_groups as $bg): 
                    $units = $stock[$bg] ?? 0;
                    $status_class = 'status-normal';
                    $status_text = 'Normal';
                    if($units <= 0) { $status_class = 'status-critical'; $status_text = 'Critical'; }
                    elseif($units < 5) { $status_class = 'status-low'; $status_text = 'Low'; }
                ?>
                <div class="col-6 col-md-3">
                    <div class="blood-card text-center">
                        <div class="group-label"><?php echo $bg; ?></div>
                        <div class="mb-3">
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </div>
                        <input type="number" name="units[<?php echo $bg; ?>]" class="form-control" value="<?php echo $units; ?>" min="0">
                        <small class="text-secondary mt-2 d-block">Units</small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-5 text-center">
                <button type="submit" name="update_stock" class="btn btn-primary btn-lg px-5 rounded-pill shadow">
                    <i class="fas fa-cloud-upload-alt me-2"></i> Sync All Stocks
                </button>
            </div>
        </form>
    </div>

    <div class="alert alert-info bg-info bg-opacity-5 border-info border-opacity-10 text-secondary p-4 rounded-4">
        <div class="d-flex">
            <i class="fas fa-info-circle fa-lg text-info me-3 mt-1"></i>
            <div>
                <h6 class="text-info fw-bold mb-1">How it works</h6>
                <p class="mb-0 small">Updating your stock levels immediately informs student donors through the Community App. If a group falls to <b>Critical (0 units)</b>, an SOS notification might be triggered by the Admin.</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
