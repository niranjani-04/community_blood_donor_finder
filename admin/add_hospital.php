<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header("Location: otp_verify.php");
    exit();
}

include '../backend/db_connect.php';

$is_edit = false;
$id = '';
$name = '';
$address = '';
$phone = '';
$lat = '';
$lng = '';
$email = '';
$password = '';
$error = '';
$success = '';

// Handle DELETE
if (isset($_GET['delete'])) {
    $hospital_id = (int)$_GET['delete'];
    try {
        // Check for inventory
        $check = $conn->prepare("SELECT COUNT(*) FROM blood_inventory WHERE hospital_id = ?");
        $check->execute([$hospital_id]);
        if ($check->fetchColumn() > 0) {
            $error = "Cannot delete hospital with existing stock! Please delete blood inventory first.";
        } else {
            $stmt = $conn->prepare("DELETE FROM hospitals WHERE hospital_id = ?");
            $stmt->execute([$hospital_id]);
            header("Location: dashboard.php#hospitals");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle EDIT
if (isset($_GET['edit'])) {
    $is_edit = true;
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM hospitals WHERE hospital_id = ?");
    $stmt->execute([$id]);
    $h = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($h) {
        $name = $h['name'];
        $address = $h['address'];
        $phone = $h['contact_phone'];
        $lat = $h['latitude'];
        $lng = $h['longitude'];
        $email = $h['email'];
        $password = $h['password'];
    } else {
        $error = "Hospital not found!";
    }
}

// Handle POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $lat = !empty($_POST['lat']) ? $_POST['lat'] : null;
    $lng = !empty($_POST['lng']) ? $_POST['lng'] : null;
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $id = $_POST['id'] ?? '';

    try {
        if ($id) {
            // Update
            $sql = "UPDATE hospitals SET name=?, address=?, contact_phone=?, latitude=?, longitude=?, email=?, password=? WHERE hospital_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $address, $phone, $lat, $lng, $email, $password, $id]);
            $success = "Hospital updated successfully!";
        } else {
            // Add
            $sql = "INSERT INTO hospitals (name, address, contact_phone, latitude, longitude, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $address, $phone, $lat, $lng, $email, $password]);
            $success = "Hospital added successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Hospital - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #0f0f13;
            background-image: radial-gradient(circle at 10% 20%, rgba(229, 45, 39, 0.1) 0%, transparent 40%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 30px;
            width: 100%;
            max-width: 600px;
        }
        .form-control, .form-select {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(0, 0, 0, 0.5);
            border-color: #ff4d6d;
            color: #fff;
            box-shadow: none;
        }
    </style>
</head>
<body>

<div class="form-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><?php echo $is_edit ? 'Edit Hospital' : 'Add New Hospital'; ?></h4>
        <a href="dashboard.php#hospitals" class="btn btn-outline-light btn-sm">Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger bg-transparent text-danger border-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success bg-transparent text-success border-success">
            <?php echo $success; ?> 
            <a href="dashboard.php#hospitals" class="fw-bold text-success">Return to Dashboard</a>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
        
        <div class="mb-3">
            <label class="form-label small text-white-50">Hospital Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label small text-white-50">Address</label>
            <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($address); ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label small text-white-50">Hepline / Phone</label>
            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" required>
        </div>

        <div class="row mb-3">
            <div class="col-md-6 mb-3">
                <label class="form-label small text-white-50">Portal Email (for Sync)</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" placeholder="hospital@hebersos.com">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label small text-white-50">Portal Password</label>
                <input type="text" name="password" class="form-control" value="<?php echo htmlspecialchars($password); ?>" placeholder="Secure Password">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <label class="form-label small text-white-50">Pin Location on Map (Click to select)</label>
                <div id="map" style="height: 250px; border-radius: 12px; margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.1); z-index: 0;"></div>
            </div>
            <div class="col-6">
                <label class="form-label small text-white-50">Latitude</label>
                <input type="text" id="lat" name="lat" class="form-control" value="<?php echo htmlspecialchars($lat); ?>" placeholder="e.g. 10.8211">
            </div>
            <div class="col-6">
                <label class="form-label small text-white-50">Longitude</label>
                <input type="text" id="lng" name="lng" class="form-control" value="<?php echo htmlspecialchars($lng); ?>" placeholder="e.g. 78.6934">
            </div>
        </div>

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script>
            var defaultLat = <?php echo $lat ?: '10.8211'; ?>;
            var defaultLng = <?php echo $lng ?: '78.6934'; ?>;
            
            var map = L.map('map').setView([defaultLat, defaultLng], 14);
            L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            var marker;
            if(<?php echo $lat ? 'true' : 'false'; ?>) {
                marker = L.marker([defaultLat, defaultLng]).addTo(map);
            }

            map.on('click', function(e) {
                var lat = e.latlng.lat.toFixed(6);
                var lng = e.latlng.lng.toFixed(6);
                
                if (marker) {
                    marker.setLatLng(e.latlng);
                } else {
                    marker = L.marker(e.latlng).addTo(map);
                }
                
                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;
            });
        </script>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
            <?php echo $is_edit ? 'Update Hospital' : 'Add Hospital'; ?>
        </button>

    </form>
</div>

</body>
</html>
