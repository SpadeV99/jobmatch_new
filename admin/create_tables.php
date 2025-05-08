<?php
require_once '../config/db_connect.php';

echo "<h2>Creating Admin System Tables</h2>";

// Create user_status table
$sql = "CREATE TABLE IF NOT EXISTS `user_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `status` enum('inactive','suspended') NOT NULL DEFAULT 'inactive',
  `reason` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_status_unique` (`user_id`, `status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "<p>✅ User status table created successfully</p>";
} else {
    echo "<p>❌ Error creating user status table: " . $conn->error . "</p>";
}

// Create job_rejection_reasons table
$sql = "CREATE TABLE IF NOT EXISTS `job_rejection_reasons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_id` int NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_rejection_unique` (`job_id`),
  FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "<p>✅ Job rejection reasons table created successfully</p>";
} else {
    echo "<p>❌ Error creating job rejection reasons table: " . $conn->error . "</p>";
}

echo "<p><a href='../index.php' class='btn btn-primary'>Return to Home</a></p>";
?>