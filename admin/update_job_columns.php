<?php
// filepath: c:\laragon\www\jobmatch_new\admin\update_job_columns.php
require_once '../config/db_connect.php';

echo "<h2>Adding Missing Columns to Jobs Table</h2>";

// Array of columns to check and add
$columns = [
    'requirements' => "TEXT NULL",
    'responsibilities' => "TEXT NULL",
    'salary_min' => "DECIMAL(12,2) NULL",
    'salary_max' => "DECIMAL(12,2) NULL"
];

// Check and add each column
foreach ($columns as $column_name => $column_definition) {
    $result = $conn->query("SHOW COLUMNS FROM jobs LIKE '$column_name'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE jobs ADD COLUMN $column_name $column_definition";
        if ($conn->query($sql)) {
            echo "<p>✅ $column_name column added successfully to jobs table</p>";
        } else {
            echo "<p>❌ Error adding $column_name column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✅ $column_name column already exists in jobs table</p>";
    }
}

echo "<p><a href='approve-jobs.php' class='btn btn-primary'>Return to Jobs Management</a></p>";
?>