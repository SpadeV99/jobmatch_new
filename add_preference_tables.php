<?php
require_once 'config/db_connect.php';

echo "<h2>Adding User Preference Tables</h2>";

// Check if user_preference_weights table exists
$result = $conn->query("SHOW TABLES LIKE 'user_preference_weights'");
if ($result && $result->num_rows == 0) {
    // Create the preference weights table
    $sql = "CREATE TABLE IF NOT EXISTS `user_preference_weights` (
      `id` INT PRIMARY KEY AUTO_INCREMENT,
      `user_id` INT NOT NULL,
      `criteria_name` VARCHAR(255) NOT NULL,
      `weight` FLOAT NOT NULL,
      `criteria_order` INT NOT NULL,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "User preference weights table created successfully<br>";
    } else {
        echo "Error creating user preference weights table: " . $conn->error . "<br>";
    }
}

// Add job_skills table if it doesn't exist (referenced in AHP functions)
$result = $conn->query("SHOW TABLES LIKE 'job_skills'");
if ($result && $result->num_rows == 0) {
    // Create the job_skills table
    $sql = "CREATE TABLE IF NOT EXISTS `job_skills` (
      `id` INT PRIMARY KEY AUTO_INCREMENT,
      `job_id` INT NOT NULL,
      `skill_name` VARCHAR(255) NOT NULL,
      FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "Job skills table created successfully<br>";
    } else {
        echo "Error creating job skills table: " . $conn->error . "<br>";
    }
}

// Check if salary_min and salary_max columns exist in jobs table
$result = $conn->query("SHOW COLUMNS FROM jobs LIKE 'salary_min'");
if ($result && $result->num_rows == 0) {
    // Add salary columns - REMOVED reference to experience_level
    $sql = "ALTER TABLE jobs 
            ADD COLUMN `salary_min` DECIMAL(10,2),
            ADD COLUMN `salary_max` DECIMAL(10,2),
            ADD COLUMN `required_experience` INT DEFAULT 0";
    
    if ($conn->query($sql) === TRUE) {
        echo "Added salary_min, salary_max and required_experience columns to jobs table<br>";
        
        // Parse existing salary data into min/max
        $sql = "UPDATE jobs SET salary_min = SUBSTRING_INDEX(salary, '-', 1), 
                salary_max = SUBSTRING_INDEX(salary, '-', -1) 
                WHERE salary LIKE '%-%'";
        if ($conn->query($sql) === TRUE) {
            echo "Parsed existing salary data into min/max columns<br>";
        }
    } else {
        echo "Error adding columns to jobs table: " . $conn->error . "<br>";
    }
}

echo "<br>Preference tables setup complete. <a href='index.php'>Return to homepage</a>";

$conn->close();
?>