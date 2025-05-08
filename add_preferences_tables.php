<?php
require_once 'config/db_connect.php';

// Create user preference weights table
$sql = "CREATE TABLE IF NOT EXISTS `user_preference_weights` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `criteria_name` VARCHAR(50) NOT NULL,
  `weight` DECIMAL(5,2) NOT NULL,
  `criteria_order` INT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY `unique_user_criteria` (`user_id`, `criteria_name`)
)";

if ($conn->query($sql) === TRUE) {
    echo "User preference weights table created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Add columns to jobseeker_profiles if they don't exist
$columns = [
    ['preferred_locations', 'VARCHAR(255)'],
    ['preferred_job_types', 'VARCHAR(255)'],
    ['salary_expectation', 'DECIMAL(12,2)']
];

foreach ($columns as $column) {
    $sql = "SHOW COLUMNS FROM `jobseeker_profiles` LIKE '{$column[0]}'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows === 0) {
        $sql = "ALTER TABLE `jobseeker_profiles` ADD COLUMN `{$column[0]}` {$column[1]}";
        
        if ($conn->query($sql) === TRUE) {
            echo "Column {$column[0]} added to jobseeker_profiles table<br>";
        } else {
            echo "Error adding column {$column[0]}: " . $conn->error . "<br>";
        }
    }
}

// Create job_skills table for better skill matching
$sql = "CREATE TABLE IF NOT EXISTS `job_skills` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `job_id` INT NOT NULL,
  `skill_name` VARCHAR(100) NOT NULL,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  UNIQUE KEY `unique_job_skill` (`job_id`, `skill_name`)
)";

if ($conn->query($sql) === TRUE) {
    echo "Job skills table created successfully<br>";
} else {
    echo "Error creating job skills table: " . $conn->error . "<br>";
}

echo "<a href='index.php'>Return to homepage</a>";
$conn->close();
?>