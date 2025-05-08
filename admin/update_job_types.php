<?php
// filepath: c:\laragon\www\jobmatch_new\admin\update_job_types.php
require_once '../config/db_connect.php';

echo "<h2>Adding job_type column to jobs table</h2>";

// Check if the column already exists
$result = $conn->query("SHOW COLUMNS FROM jobs LIKE 'job_type'");
if ($result->num_rows == 0) {
    // Add the job_type column
    $sql = "ALTER TABLE jobs ADD COLUMN job_type ENUM('full_time', 'part_time', 'contract', 'internship', 'remote') NOT NULL DEFAULT 'full_time'";
    if ($conn->query($sql)) {
        echo "<p>✅ job_type column added successfully to jobs table</p>";
    } else {
        echo "<p>❌ Error adding job_type column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ job_type column already exists in jobs table</p>";
}

echo "<p><a href='approve-jobs.php' class='btn btn-primary'>Return to Jobs Management</a></p>";
?>