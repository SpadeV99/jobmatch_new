<?php
require_once '../config/db_connect.php';

echo "<h2>Updating Database Tables for Admin System</h2>";

// 1. Update job_applications table
echo "<h3>Updating job_applications table</h3>";
$result = $conn->query("SHOW COLUMNS FROM job_applications LIKE 'created_at'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE job_applications ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    if ($conn->query($sql)) {
        echo "<p>✅ created_at column added successfully to job_applications table</p>";
    } else {
        echo "<p>❌ Error adding created_at column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ created_at column already exists in job_applications table</p>";
}

// 2. Update jobs table
echo "<h3>Updating jobs table</h3>";
$result = $conn->query("SHOW COLUMNS FROM jobs LIKE 'created_at'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE jobs ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    if ($conn->query($sql)) {
        echo "<p>✅ created_at column added successfully to jobs table</p>";
    } else {
        echo "<p>❌ Error adding created_at column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ created_at column already exists in jobs table</p>";
}

echo "<p><a href='index.php' class='btn btn-primary'>Return to Admin Dashboard</a></p>";
?>