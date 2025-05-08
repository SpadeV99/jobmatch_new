<?php
require_once '../config/db_connect.php';

echo "<h2>Updating Job Applications Table</h2>";

// Check if created_at column exists in job_applications table
$result = $conn->query("SHOW COLUMNS FROM job_applications LIKE 'created_at'");
if ($result->num_rows == 0) {
    // Add created_at column
    $sql = "ALTER TABLE job_applications 
            ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    
    if ($conn->query($sql)) {
        echo "<p>✅ created_at column added successfully to job_applications table</p>";
    } else {
        echo "<p>❌ Error adding created_at column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ created_at column already exists in job_applications table</p>";
}

echo "<p><a href='index.php' class='btn btn-primary'>Return to Admin Dashboard</a></p>";
?>