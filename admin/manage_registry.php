<?php
 /**
  * COLLEGE REGISTRY MANAGEMENT
  * Handles Add, Edit, Delete operations for preloaded students
  */
 
 session_start();
 require_once '../backend/db_connect.php';
 
 // Check if user is admin
 if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
     header("Location: ../login.php");
     exit();
 }
 
 $message = '';
 $message_type = '';
 
 // ============================================
 // HANDLE FORM SUBMISSIONS
 // ============================================
 
 if ($_SERVER['REQUEST_METHOD'] == 'POST') {
     $action = $_POST['action'] ?? '';
     
     try {
         // ADD NEW STUDENT
         if ($action == 'add') {
             $register_number = trim($_POST['register_number']);
             $name = trim($_POST['name']);
             $dob = $_POST['dob'];
             $age = !empty($_POST['age']) ? intval($_POST['age']) : NULL;
             $health = $_POST['health_eligibility'] ?? NULL;
             $blood_group = $_POST['blood_group'];
             $address = $_POST['address'] ?? NULL;
             $email = trim($_POST['email']);
             $phone = trim($_POST['phone']);
             
             // Validation
             if (empty($register_number) || empty($name) || empty($dob) || empty($blood_group) || empty($email) || empty($phone)) {
                 throw new Exception("All fields are required!");
             }
             
             if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                 throw new Exception("Invalid email format!");
             }
             
             if (!preg_match('/^[0-9]{10}$/', $phone)) {
                 throw new Exception("Phone number must be 10 digits!");
             }
             
             // Check if register number already exists
             $check_stmt = $conn->prepare("SELECT register_number FROM preloaded_students WHERE register_number = ?");
             $check_stmt->execute([$register_number]);
             if ($check_stmt->rowCount() > 0) {
                 throw new Exception("Register number already exists!");
             }
             
             // Insert new student
             $sql = "INSERT INTO preloaded_students (register_number, name, dob, age, health_eligibility, blood_group, address, email, phone) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
             $stmt = $conn->prepare($sql);
             $stmt->execute([$register_number, $name, $dob, $age, $health, $blood_group, $address, $email, $phone]);
             
             $message = "Student added successfully!";
             $message_type = "success";
         }
         
         // EDIT EXISTING STUDENT
         elseif ($action == 'edit') {
             $register_number = trim($_POST['register_number']);
             $name = trim($_POST['name']);
             $dob = $_POST['dob'];
             $age = !empty($_POST['age']) ? intval($_POST['age']) : NULL;
             $health = $_POST['health_eligibility'] ?? NULL;
             $blood_group = $_POST['blood_group'];
             $address = $_POST['address'] ?? NULL;
             $email = trim($_POST['email']);
             $phone = trim($_POST['phone']);
             
             // Validation
             if (empty($register_number) || empty($name) || empty($dob) || empty($blood_group) || empty($email) || empty($phone)) {
                 throw new Exception("All fields are required!");
             }
             
             if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                 throw new Exception("Invalid email format!");
             }
             
             if (!preg_match('/^[0-9]{10}$/', $phone)) {
                 throw new Exception("Phone number must be 10 digits!");
             }
             
             // Update student
             $sql = "UPDATE preloaded_students 
                     SET name = ?, dob = ?, age = ?, health_eligibility = ?, blood_group = ?, address = ?, email = ?, phone = ? 
                     WHERE register_number = ?";
             $stmt = $conn->prepare($sql);
             $stmt->execute([$name, $dob, $age, $health, $blood_group, $address, $email, $phone, $register_number]);
             
             $message = "Student updated successfully!";
             $message_type = "success";
         }
         
     } catch (Exception $e) {
         $message = $e->getMessage();
         $message_type = "error";
     }
 }
 
 // ============================================
 // HANDLE DELETE REQUEST
 // ============================================
 
 if (isset($_GET['delete'])) {
     $register_number = $_GET['delete'];
     
     try {
         $sql = "DELETE FROM preloaded_students WHERE register_number = ?";
         $stmt = $conn->prepare($sql);
         $stmt->execute([$register_number]);
         
         $message = "Student deleted successfully!";
         $message_type = "success";
     } catch (Exception $e) {
         $message = "Error deleting student: " . $e->getMessage();
         $message_type = "error";
     }
 }
 
 // Redirect back to dashboard with message
 if (!empty($message)) {
     $_SESSION['message'] = $message;
     $_SESSION['message_type'] = $message_type;
 }
 
 header("Location: dashboard.php#registry");
 exit();
 ?>
