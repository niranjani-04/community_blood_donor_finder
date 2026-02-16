<?php
 session_start();
 // Security Check
 if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
     header("Location: index.php");
     exit();
 }
 
 include '../backend/db_connect.php';
 
 $is_edit = false;
 $reg_no = '';
 $name = '';
 $dob = '';
 $age = '';
 $health_eligibility = '';
 $blood_group = '';
 $email = '';
 $phone = '';
 $address = '';
 $error = '';
 $success = '';
 
 // Handle DELETE action directly
 if (isset($_GET['delete'])) {
     $regNo = $_GET['delete'];
     try {
         $conn->beginTransaction();
         // Delete from Registry
         $stmt1 = $conn->prepare("DELETE FROM preloaded_students WHERE register_number = ?");
         $stmt1->execute([$regNo]);
         
         // Delete from Users (Activated Accounts)
         $stmt2 = $conn->prepare("DELETE FROM users WHERE register_number = ?");
         $stmt2->execute([$regNo]);
         
         $conn->commit();
         header("Location: dashboard.php#registry");
         exit();
     } catch (Exception $e) {
         $conn->rollBack();
         $error = "Error: " . $e->getMessage();
     }
 }
 
 // Handle EDIT mode - Fetch Data
 if (isset($_GET['edit'])) {
     $is_edit = true;
     $reg_no = $_GET['edit'];
     
     $stmt = $conn->prepare("SELECT * FROM preloaded_students WHERE register_number = ?");
     $stmt->execute([$reg_no]);
     $student = $stmt->fetch(PDO::FETCH_ASSOC);
     
     if ($student) {
         $name = $student['name'];
         $dob = $student['dob'];
         $age = $student['age'];
         $health_eligibility = $student['health_eligibility'];
         $blood_group = $student['blood_group'];
         $address = $student['address'];
         $email = $student['email'];
         $phone = $student['phone'];
     } else {
         $error = "Student not found!";
     }
 }
 
 // Handle FORM SUBMISSION
 if ($_SERVER["REQUEST_METHOD"] == "POST") {
     $action = $_POST['action'];
     $reg_no_input = strtoupper(trim($_POST['register_number']));
     $name_input = trim($_POST['name']);
     $dob_input = $_POST['dob'];
     $age_input = !empty($_POST['age']) ? intval($_POST['age']) : NULL;
     $health_input = trim($_POST['health_eligibility']);
     $blood_group_input = $_POST['blood_group'];
     $address_input = trim($_POST['address']);
     $email_input = trim($_POST['email']);
     $phone_input = trim($_POST['phone']);
     
     // Validate Age/DOB Sync if Age is provided but DOB is not changed?
     // Actually, if DOB is provided, we can auto-calc Age if not provided?
     // But let's stick to simple input saving for now.
     
     if(empty($age_input) && !empty($dob_input)) {
        // Auto calc age
        $birthDate = new DateTime($dob_input);
        $today = new DateTime('today');
        $age_input = $birthDate->diff($today)->y;
     }

     try {
         if ($action == 'edit') {
             // Update Registry
             $sql = "UPDATE preloaded_students SET name=?, dob=?, age=?, health_eligibility=?, blood_group=?, address=?, email=?, phone=? WHERE register_number=?";
             $stmt = $conn->prepare($sql);
             $stmt->execute([$name_input, $dob_input, $age_input, $health_input, $blood_group_input, $address_input, $email_input, $phone_input, $reg_no_input]);
             
             // Sync with Users table (if activated)
             $sync_stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, blood_group=? WHERE register_number=?");
             $sync_stmt->execute([$name_input, $email_input, $phone_input, $blood_group_input, $reg_no_input]);

             $success = "Student updated successfully (and synced with active account)!";
             // Update variables to reflect changes
             $name = $name_input; $dob = $dob_input; $age = $age_input; $health_eligibility = $health_input; $blood_group = $blood_group_input; $address = $address_input; $email = $email_input; $phone = $phone_input;
         } else {
             // Add Check
             $check = $conn->prepare("SELECT register_number FROM preloaded_students WHERE register_number = ?");
             $check->execute([$reg_no_input]);
             if ($check->rowCount() > 0) {
                 throw new Exception("Register Number already exists!");
             }
 
             // Insert
             $sql = "INSERT INTO preloaded_students (register_number, name, dob, age, health_eligibility, blood_group, address, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
             $stmt = $conn->prepare($sql);
             $stmt->execute([$reg_no_input, $name_input, $dob_input, $age_input, $health_input, $blood_group_input, $address_input, $email_input, $phone_input]);
             $success = "Student added successfully!";
             // Clear form
             if (!$is_edit) { $reg_no_input = ''; $name_input = ''; $dob_input = ''; $age_input=''; $health_input=''; $address_input=''; $email_input = ''; $phone_input = ''; }
         }
     } catch (Exception $e) {
         $error = $e->getMessage();
     }
 }
 ?>
 
 <!DOCTYPE html>
 <html lang="en">
 <head>
     <meta charset="UTF-8">
     <title><?php echo $is_edit ? 'Edit' : 'Add'; ?> Student - Admin</title>
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
             max-width: 700px;
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
         .text-label {
             font-size: 0.8rem;
             color: rgba(255,255,255,0.6);
             margin-bottom: 4px;
         }
     </style>
 </head>
 <body>
 
 <div class="form-card">
     <div class="d-flex justify-content-between align-items-center mb-4">
         <h4 class="mb-0"><?php echo $is_edit ? 'Edit Student' : 'Add New Student'; ?></h4>
         <a href="dashboard.php#registry" class="btn btn-outline-light btn-sm">Back</a>
     </div>
 
     <?php if ($error): ?>
         <div class="alert alert-danger bg-transparent text-danger border-danger"><?php echo $error; ?></div>
     <?php endif; ?>
 
     <?php if ($success): ?>
         <div class="alert alert-success bg-transparent text-success border-success">
             <?php echo $success; ?> 
             <a href="dashboard.php#registry" class="fw-bold text-success">Return to Dashboard</a>
         </div>
     <?php endif; ?>
 
     <form method="POST">
         <input type="hidden" name="action" value="<?php echo $is_edit ? 'edit' : 'add'; ?>">
         
         <div class="row mb-3">
             <div class="col-md-6">
                 <label class="text-label">Register Number (ID)</label>
                 <input type="text" name="register_number" class="form-control" value="<?php echo htmlspecialchars($reg_no); ?>" required <?php echo $is_edit ? 'readonly' : ''; ?>>
             </div>
             <div class="col-md-6">
                 <label class="text-label">Full Name</label>
                 <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
             </div>
         </div>
 
         <div class="row mb-3">
             <div class="col-md-6 mb-2">
                 <label class="text-label">Age</label>
                 <input type="number" name="age" class="form-control" value="<?php echo htmlspecialchars($age); ?>">
             </div>
             <div class="col-md-6 mb-2">
                 <label class="text-label">Date of Birth (For Login)</label>
                 <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($dob); ?>" required>
                 <small class="text-secondary" style="font-size: 0.7em;">Used as password</small>
             </div>
         </div>
         
         <div class="row mb-3">
            <div class="col-md-6">
                 <label class="text-label">Blood Group</label>
                 <select name="blood_group" class="form-select" required>
                     <option value="">Select</option>
                     <?php 
                     $groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                     foreach($groups as $g){
                         $selected = ($blood_group == $g) ? 'selected' : '';
                         echo "<option value='$g' $selected>$g</option>";
                     }
                     ?>
                 </select>
             </div>
             <div class="col-md-6">
                 <label class="text-label">Health Eligibility</label>
                 <input type="text" name="health_eligibility" class="form-control" value="<?php echo htmlspecialchars($health_eligibility); ?>" placeholder="e.g. Fit, Underweight, Medication">
             </div>
         </div>
 
         <div class="mb-3">
             <label class="text-label">Contact Address</label>
             <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($address); ?></textarea>
         </div>
 
         <div class="row mb-3">
             <div class="col-md-6">
                 <label class="text-label">Email Address (Mail ID)</label>
                 <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
             </div>
             <div class="col-md-6">
                 <label class="text-label">Phone Number (Contact Number)</label>
                 <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" required pattern="[0-9]{10}" title="10 digit mobile number">
             </div>
         </div>
 
         <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mt-2">
             <?php echo $is_edit ? 'Update Student' : 'Add Student'; ?>
         </button>
 
     </form>
 </div>
 
 </body>
 </html>
