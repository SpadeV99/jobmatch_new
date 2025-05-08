<?php
require_once 'config/db_connect.php';

// Create assessments table
$sql = "CREATE TABLE IF NOT EXISTS `assessments` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `job_id` INT NOT NULL,
  `employer_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `time_limit` INT DEFAULT 0,
  `passing_score` INT DEFAULT 70,
  `is_required` TINYINT DEFAULT 1,
  `status` ENUM('draft', 'active', 'inactive') DEFAULT 'draft',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (employer_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Assessments table created successfully<br>";
} else {
    echo "Error creating assessments table: " . $conn->error . "<br>";
}

// Create assessment questions table
$sql = "CREATE TABLE IF NOT EXISTS `assessment_questions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `assessment_id` INT NOT NULL,
  `question` TEXT NOT NULL,
  `question_type` ENUM('multiple_choice', 'checkbox', 'text', 'boolean') DEFAULT 'multiple_choice',
  `options` TEXT NULL,
  `correct_answer` TEXT NULL,
  `points` INT DEFAULT 1,
  `order` INT DEFAULT 0,
  FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Assessment questions table created successfully<br>";
} else {
    echo "Error creating assessment questions table: " . $conn->error . "<br>";
}

// Create assessment submissions table
$sql = "CREATE TABLE IF NOT EXISTS `assessment_submissions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `assessment_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `job_application_id` INT NULL,
  `start_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `end_time` DATETIME NULL,
  `score` INT DEFAULT 0,
  `status` ENUM('in_progress', 'completed', 'expired') DEFAULT 'in_progress',
  `is_passed` TINYINT DEFAULT 0,
  FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (job_application_id) REFERENCES job_applications(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Assessment submissions table created successfully<br>";
} else {
    echo "Error creating assessment submissions table: " . $conn->error . "<br>";
}

// Create assessment answers table
$sql = "CREATE TABLE IF NOT EXISTS `assessment_answers` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `submission_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `answer` TEXT,
  `is_correct` TINYINT DEFAULT 0,
  `points_awarded` INT DEFAULT 0,
  FOREIGN KEY (submission_id) REFERENCES assessment_submissions(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES assessment_questions(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Assessment answers table created successfully<br>";
} else {
    echo "Error creating assessment answers table: " . $conn->error . "<br>";
}

echo "<a href='index.php'>Return to homepage</a>";
$conn->close();
?>