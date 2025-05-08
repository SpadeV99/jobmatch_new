<?php
// filepath: c:\laragon\www\jobmatch_new\user\update_salary_preferences.php
require_once '../config/db_connect.php';

echo "<h2>Adding Missing Salary Column to Jobseeker Profiles</h2>";

$columns = [
    'salary_expectation' => "DECIMAL(12,2) NULL",
    'preferred_salary_min' => "DECIMAL(12,2) NULL",
    'preferred_salary_max' => "DECIMAL(12,2) NULL"
];

// Check and add each column
foreach ($columns as $column_name => $column_definition) {
    $result = $conn->query("SHOW COLUMNS FROM jobseeker_profiles LIKE '$column_name'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE jobseeker_profiles ADD COLUMN $column_name $column_definition";
        if ($conn->query($sql)) {
            echo "<p>✅ $column_name column added successfully to jobseeker_profiles table</p>";
        } else {
            echo "<p>❌ Error adding $column_name column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✅ $column_name column already exists in jobseeker_profiles table</p>";
    }
}

echo "<p><a href='preferences.php' class='btn btn-primary'>Return to Preferences Page</a></p>";
?>