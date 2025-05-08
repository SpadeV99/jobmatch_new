<?php
require_once 'config/db_connect.php';

echo "<h2>Adding Application Status History Table</h2>";

// Create the application status history table
$sql = "CREATE TABLE IF NOT EXISTS `application_status_history` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `application_id` INT NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `notes` TEXT,
  `changed_by` INT,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Application status history table created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

echo "<br><a href='index.php'>Return to home page</a>";

$conn->close();
?>