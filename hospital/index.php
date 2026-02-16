<?php
session_start();
if (isset($_SESSION['hospital_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hospital Sync Portal - Blood SOS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --bg-body: #050505;
            --glass-card: rgba(20, 20, 25, 0.6);
            --glass-border: rgba(255, 255, 255, 0.1);
        }
        body {
            background: linear-gradient(135deg, #050505 0%, #1a1a2e 100%);
            color: #f8fafc;
            font-family: 'Outfit', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-card {
            background: var(--glass-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: white;
            border-radius: 12px;
            padding: 12px 16px;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
            box-shadow: none;
            color: white;
        }
        .btn-primary {
            background: var(--primary);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.5);
        }
        .logo-box {
            width: 60px;
            height: 60px;
            background: rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: var(--primary);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-box">
        <i class="fas fa-hospital-alt fa-2x"></i>
    </div>
    <h3 class="text-center fw-bold mb-1">Hospital Sync</h3>
    <p class="text-center text-secondary mb-4">Manage your real-time blood inventory</p>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger small text-center rounded-3">
            Invalid email or password.
        </div>
    <?php endif; ?>

    <form action="auth.php" method="POST">
        <div class="mb-3">
            <label class="small text-secondary fw-bold mb-1">HOSPITAL EMAIL</label>
            <input type="email" name="email" class="form-control" placeholder="hospital@example.com" required>
        </div>
        <div class="mb-4">
            <label class="small text-secondary fw-bold mb-1">PASSWORD</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">SIGN IN TO SYNC</button>
        </div>
    </form>
    
    <div class="mt-4 text-center">
        <a href="../index.php" class="text-secondary text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i> Back to Community Portal
        </a>
    </div>
</div>

</body>
</html>
