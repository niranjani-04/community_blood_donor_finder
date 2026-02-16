<?php
// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_upload_template.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, array('Register Number', 'Name', 'Age', 'Health Eligibility', 'Blood Group', 'Address', 'Email', 'Phone'));

// Output sample data
fputcsv($output, array('REG101', 'John Doe', '20', 'Fit', 'O+', '123 Main St, City', 'john@example.com', '9876543210'));
fputcsv($output, array('REG102', 'Jane Smith', '19', 'Underweight', 'A-', '456 College Rd, Town', 'jane@example.com', '9123456780'));

// Close the file pointer
fclose($output);
?>
