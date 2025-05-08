<?php
require_once 'config/db_connect.php';

// Create application status history table
$sql = "CREATE TABLE IF NOT EXISTS `application_status_history` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `application_id` INT NOT NULL,
  `status` ENUM('pending', 'shortlisted', 'interviewed', 'offered', 'rejected', 'withdrawn') NOT NULL,
  `notes` TEXT,
  `changed_by` INT NOT NULL,
  `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Application status history table created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

echo "<a href='index.php'>Return to homepage</a>";
$conn->close();
?>