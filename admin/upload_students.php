<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access Denied.");
}
include '../backend/db_connect.php';

// Undo functionality
if (isset($_GET['undo'])) {
    try {
        $lastBatch = $conn->query("SELECT batch_id FROM preloaded_students WHERE batch_id IS NOT NULL ORDER BY created_at DESC LIMIT 1")->fetchColumn();
        if ($lastBatch) {
            $stmt = $conn->prepare("DELETE FROM preloaded_students WHERE batch_id = ?");
            $stmt->execute([$lastBatch]);
            $_SESSION['message'] = "✅ Last bulk upload (Batch: $lastBatch) has been undone.";
            $_SESSION['message_type'] = "warning";
        } else {
            $_SESSION['message'] = "ℹ️ No recent bulk uploads found to undo.";
            $_SESSION['message_type'] = "info";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "❌ Error: " . addslashes($e->getMessage());
        $_SESSION['message_type'] = "danger";
    }
    header("Location: dashboard.php#registry");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['student_csv'])) {
    ini_set('auto_detect_line_endings', TRUE);

    if ($_FILES['student_csv']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['message'] = "❌ Upload failed with error code: " . $_FILES['student_csv']['error'];
        $_SESSION['message_type'] = "danger";
        header("Location: dashboard.php#registry");
        exit;
    }

    $file = $_FILES['student_csv']['tmp_name'];
    $batch_id = 'BATCH_' . date('YmdHis');
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        $count = 0;
        $errors = 0;
        $row = 0;
        $firstError = null;
        
        while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
            $row++;
            
            // Skip header (Match User's "Reg_No")
            $firstCol = trim($data[0] ?? '');
            $firstCol = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $firstCol); 
            if ($row == 1 && (strtoupper($firstCol) == 'REG_NO' || strtoupper($firstCol) == 'REG NO')) continue;
            
            if (count($data) < 2) continue;

            // USER'S COLUMN FORMAT (from screenshot):
            // 0: Reg No
            // 1: Name
            // 2: Age
            // 3: Blood Group
            // 4: Health Condition (e.g. "BP Issue", "Good")
            // 5: Last Donation Date (Skip)
            // 6: Eligibility Status (Skip)
            // 7: Address (City)
            // 8: Email
            // 9: Phone

            $regNo = strtoupper(trim($data[0]));
            if (empty($regNo)) continue;

            $name = trim($data[1] ?? '');
            $age = intval(trim($data[2] ?? 0));
            $bloodGroup = strtoupper(trim($data[3] ?? ''));
            $healthElig = trim($data[4] ?? '');
            
            // Logic for Address/Email/Phone based on 10 column vs 8 column
            if (count($data) >= 10) {
                $address = trim($data[7] ?? '');
                $email = trim($data[8] ?? '');
                $phone = trim($data[9] ?? '');
            } else {
                // Fallback for 8 column template
                $address = trim($data[5] ?? '');
                $email = trim($data[6] ?? '');
                $phone = trim($data[7] ?? '');
            }

            // 1. Try to get DOB from column index 5 (if provided)
            $dobRaw = trim($data[5] ?? '');
            $dob = null;
            
            if (!empty($dobRaw)) {
                // Try parsing the date (handles formats like 18-Feb-2005)
                $parsedTime = strtotime($dobRaw);
                if ($parsedTime !== false) {
                    $dob = date('Y-m-d', $parsedTime);
                }
            }
            
            if (!$dob) {
                // FALLBACK: Calculate DOB from Age if not provided or unparseable
                $currentYear = date("Y");
                $birthYear = ($age > 0) ? ($currentYear - $age) : ($currentYear - 18);
                $dob = "$birthYear-01-01";
            }

            try {
                $sql = "INSERT INTO preloaded_students 
                        (register_number, name, age, dob, health_eligibility, blood_group, address, email, phone, batch_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE 
                        name=VALUES(name), age=VALUES(age), dob=VALUES(dob), 
                        health_eligibility=VALUES(health_eligibility), blood_group=VALUES(blood_group), 
                        address=VALUES(address), email=VALUES(email), phone=VALUES(phone), 
                        batch_id=VALUES(batch_id)";
                        
                $stmt = $conn->prepare($sql);
                $stmt->execute([$regNo, $name, $age, $dob, $healthElig, $bloodGroup, $address, $email, $phone, $batch_id]);
                $count++;
            } catch (PDOException $e) { 
                $errors++; 
                if (!$firstError) $firstError = $e->getMessage();
            }
        }
        fclose($handle);
        
        if ($count > 0) {
            $_SESSION['message'] = "✅ Success! $count students processed from your CSV. (Batch: $batch_id).";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "⚠️ No records imported. Error: " . ($firstError ?? "Invalid Format");
            $_SESSION['message_type'] = "warning";
        }
    }
    header("Location: dashboard.php#registry");
    exit();
}
?>
