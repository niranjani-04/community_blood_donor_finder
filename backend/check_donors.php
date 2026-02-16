<?php
include 'db_connect.php';

echo "<h2>Donor Status Check (A+)</h2>";

$blood_group = 'A+';
$requester_id = 999; // Dummy ID to avoid excluding self

$sql = "SELECT user_id, name, email, role, blood_group, availability_status FROM users WHERE blood_group = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$blood_group]);
$all_donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Found " . count($all_donors) . " total donors with A+ in `users` table:</h3>";
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Role</th><th>Status</th></tr>";
foreach ($all_donors as $d) {
    echo "<tr><td>{$d['user_id']}</td><td>{$d['name']}</td><td>{$d['role']}</td><td>{$d['availability_status']}</td></tr>";
}
echo "</table>";

echo "<h3>Applying Filter (role='donor' AND availability_status='Available'):</h3>";
$sql_filtered = "SELECT name FROM users WHERE role = 'donor' AND blood_group = ? AND availability_status = 'Available'";
$stmt_f = $conn->prepare($sql_filtered);
$stmt_f->execute([$blood_group]);
$filtered = $stmt_f->fetchAll(PDO::FETCH_ASSOC);

if (count($filtered) > 0) {
    echo "<p style='color: green;'>✅ Success: " . count($filtered) . " donors are ready to be notified.</p>";
} else {
    echo "<p style='color: red;'>❌ Error: 0 donors matched the search criteria.</p>";
    echo "<p><b>Possible Reasons:</b><br>
          1. Role is not exactly 'donor' (it might be 'admin' or something else).<br>
          2. Status is not exactly 'Available' (it might be 'Busy' or empty).<br>
          3. The blood group in the database might have a trailing space (e.g., 'A+ ').</p>";
}
?>
