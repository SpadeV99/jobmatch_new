<?php
// filepath: c:\laragon\www\jobmatch_new\admin\update_jobs_table_complete.php
require_once 'config/db_connect.php';

echo "<h2>Updating Jobs Table Structure</h2>";

// 1. Check and add status column
$result = $conn->query("SHOW COLUMNS FROM jobs LIKE 'status'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE jobs ADD COLUMN status ENUM('pending', 'active', 'rejected', 'expired', 'filled') NOT NULL DEFAULT 'pending'";
    if ($conn->query($sql)) {
        echo "<p>✅ Status column added successfully to jobs table</p>";
    } else {
        echo "<p>❌ Error adding status column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ Status column already exists in jobs table</p>";
}

// 2. Check and add is_featured column
$result = $conn->query("SHOW COLUMNS FROM jobs LIKE 'is_featured'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE jobs ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0";
    if ($conn->query($sql)) {
        echo "<p>✅ is_featured column added successfully to jobs table</p>";
    } else {
        echo "<p>❌ Error adding is_featured column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ is_featured column already exists in jobs table</p>";
}

// 3. Check and add created_at column
$result = $conn->query("SHOW COLUMNS FROM jobs LIKE 'created_at'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE jobs ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    if ($conn->query($sql)) {
        echo "<p>✅ created_at column added successfully to jobs table</p>";
        
        // Update created_at to match posted_date for existing records
        $sql = "UPDATE jobs SET created_at = posted_date WHERE created_at IS NULL AND posted_date IS NOT NULL";
        if ($conn->query($sql)) {
            echo "<p>✅ Updated created_at values from posted_date</p>";
        }
    } else {
        echo "<p>❌ Error adding created_at column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ created_at column already exists in jobs table</p>";
}

// 4. Set default status for existing jobs
$sql = "UPDATE jobs SET status = 'active' WHERE status = 'pending' OR status = 'unknown'";
if ($conn->query($sql)) {
    echo "<p>✅ Existing jobs have been marked as active</p>";
}

echo "<p><a href='index.php' class='btn btn-primary'>Return to Admin Dashboard</a></p>";
?>