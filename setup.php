<?php
require_once 'config/db_connect.php';

// Create job categories table
$sql = "CREATE TABLE IF NOT EXISTS `job_categories` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Job categories table created successfully<br>";
} else {
    echo "Error creating job_categories table: " . $conn->error . "<br>";
}

// Check if categories already exist to avoid duplicates
$check = $conn->query("SELECT COUNT(*) as count FROM job_categories");
$row = $check->fetch_assoc();

if ($row['count'] == 0) {
    // Insert job categories
    $sql = "INSERT INTO `job_categories` (`id`, `name`) VALUES
    (1, 'Accounting & Finance'),
    /* ... existing categories ... */
    (47, 'Writing & Editing')";

    if ($conn->multi_query($sql) === TRUE) {
        echo "Job categories inserted successfully<br>";
    } else {
        echo "Error inserting job categories: " . $conn->error . "<br>";
    }
} else {
    echo "Job categories already exist in the database<br>";
}

// Create basic jobs table
$sql = "CREATE TABLE IF NOT EXISTS `jobs` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `category_id` INT NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `salary` VARCHAR(100),
  `employer_id` INT NOT NULL,
  `job_type` ENUM('full-time', 'part-time', 'contract', 'internship', 'temporary') NOT NULL,
  `experience_level` VARCHAR(50),
  `education_required` VARCHAR(255),
  `status` ENUM('active', 'filled', 'expired', 'draft') DEFAULT 'active',
  `posted_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `expires_date` DATE,
  `skills_required` TEXT,
  `responsibilities` TEXT,
  `benefits` TEXT,
  `remote_option` BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (category_id) REFERENCES job_categories(id),
  FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Jobs table created successfully<br>";
} else {
    echo "Error creating jobs table: " . $conn->error . "<br>";
}

// Create basic users table
$sql = "CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `user_type` ENUM('jobseeker', 'employer', 'admin') NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `email_verified` BOOLEAN DEFAULT FALSE,
  `verification_token` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create jobseeker profiles table
$sql = "CREATE TABLE IF NOT EXISTS `jobseeker_profiles` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `first_name` VARCHAR(100),
  `last_name` VARCHAR(100),
  `phone` VARCHAR(20),
  `address` VARCHAR(255),
  `city` VARCHAR(100),
  `state` VARCHAR(100),
  `zip_code` VARCHAR(20),
  `country` VARCHAR(100),
  `resume_path` VARCHAR(255),
  `skills` TEXT,
  `experience` TEXT,
  `education` TEXT,
  `preferred_location` VARCHAR(255),
  `expected_salary` VARCHAR(100),
  `profile_picture` VARCHAR(255),
  `headline` VARCHAR(255),
  `summary` TEXT,
  `availability` VARCHAR(50),
  `remote_preference` ENUM('remote', 'on-site', 'hybrid', 'flexible') DEFAULT 'flexible',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Jobseeker profiles table created successfully<br>";
} else {
    echo "Error creating jobseeker profiles table: " . $conn->error . "<br>";
}

// Create job applications table
$sql = "CREATE TABLE IF NOT EXISTS `job_applications` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `apply_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `resume_path` VARCHAR(255),
  `cover_letter` TEXT,
  `status` ENUM('pending', 'reviewing', 'shortlisted', 'interviewing', 'offered', 'rejected', 'withdrawn', 'hired') DEFAULT 'pending',
  `employer_notes` TEXT,
  `salary_expectation` VARCHAR(100),
  `notice_period` VARCHAR(100),
  `availability_date` DATE,
  `feedback` TEXT,
  `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Job applications table created successfully<br>";
} else {
    echo "Error creating job applications table: " . $conn->error . "<br>";
}

// Create saved jobs table
$sql = "CREATE TABLE IF NOT EXISTS `saved_jobs` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `date_saved` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  UNIQUE KEY `user_job_unique` (`user_id`, `job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Saved jobs table created successfully<br>";
} else {
    echo "Error creating saved jobs table: " . $conn->error . "<br>";
}

// Create employer profiles table
$sql = "CREATE TABLE IF NOT EXISTS `employer_profiles` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `company_name` VARCHAR(255) NOT NULL,
  `company_description` TEXT,
  `industry` VARCHAR(100),
  `website` VARCHAR(255),
  `phone` VARCHAR(20),
  `address` VARCHAR(255),
  `city` VARCHAR(100),
  `state` VARCHAR(100),
  `zip_code` VARCHAR(20),
  `country` VARCHAR(100),
  `logo_path` VARCHAR(255),
  `company_size` VARCHAR(50),
  `founded_year` INT,
  `social_linkedin` VARCHAR(255),
  `social_twitter` VARCHAR(255),
  `social_facebook` VARCHAR(255),
  `company_culture` TEXT,
  `benefits_offered` TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Employer profiles table created successfully<br>";
} else {
    echo "Error creating employer profiles table: " . $conn->error . "<br>";
}

// Create interviews table
$sql = "CREATE TABLE IF NOT EXISTS `interviews` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `job_id` INT NOT NULL,
  `jobseeker_id` INT NOT NULL,
  `application_id` INT NOT NULL,
  `interview_date` DATETIME NOT NULL,
  `duration_minutes` INT NOT NULL DEFAULT 30,
  `interview_type` ENUM('video', 'phone', 'in_person') NOT NULL,
  `location` TEXT,
  `meeting_link` VARCHAR(255),
  `notes` TEXT,
  `status` ENUM('scheduled', 'completed', 'cancelled', 'rescheduled', 'no_show') DEFAULT 'scheduled',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP,
  `employer_notes` TEXT,
  `jobseeker_notes` TEXT,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
  FOREIGN KEY (jobseeker_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Interviews table created successfully<br>";
} else {
    echo "Error creating interviews table: " . $conn->error . "<br>";
}

// Create interview reschedule requests table
$sql = "CREATE TABLE IF NOT EXISTS `interview_reschedule_requests` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `interview_id` INT NOT NULL,
  `requested_by` INT NOT NULL,
  `proposed_date` DATETIME NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
  `response_by` INT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (interview_id) REFERENCES interviews(id) ON DELETE CASCADE,
  FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (response_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Interview reschedule requests table created successfully<br>";
} else {
    echo "Error creating interview reschedule requests table: " . $conn->error . "<br>";
}

// Create interview feedback table
$sql = "CREATE TABLE IF NOT EXISTS `interview_feedback` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `interview_id` INT NOT NULL,
  `rating` INT NOT NULL,
  `strengths` TEXT,
  `weaknesses` TEXT,
  `technical_skills` TEXT,
  `communication_skills` TEXT,
  `cultural_fit` TEXT,
  `overall_notes` TEXT,
  `recommendation` ENUM('strong_hire', 'hire', 'maybe', 'do_not_hire') NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (interview_id) REFERENCES interviews(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Interview feedback table created successfully<br>";
} else {
    echo "Error creating interview feedback table: " . $conn->error . "<br>";
}

// Create assessments table
$sql = "CREATE TABLE IF NOT EXISTS `assessments` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `employer_id` INT NOT NULL,
  `job_id` INT,
  `time_limit` INT DEFAULT 0,
  `passing_score` INT,
  `status` ENUM('draft', 'active', 'archived') DEFAULT 'draft',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Assessments table created successfully<br>";
} else {
    echo "Error creating assessments table: " . $conn->error . "<br>";
}

// Create assessment questions table
$sql = "CREATE TABLE IF NOT EXISTS `assessment_questions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `assessment_id` INT NOT NULL,
  `question_type` ENUM('multiple_choice', 'true_false', 'short_answer', 'coding') NOT NULL,
  `question_text` TEXT NOT NULL,
  `options` TEXT,
  `correct_answer` TEXT,
  `points` INT DEFAULT 1,
  `order` INT,
  FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

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
  `score` INT,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME,
  `status` ENUM('in_progress', 'completed', 'expired') DEFAULT 'in_progress',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Assessment submissions table created successfully<br>";
} else {
    echo "Error creating assessment submissions table: " . $conn->error . "<br>";
}

// Create submission answers table
$sql = "CREATE TABLE IF NOT EXISTS `submission_answers` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `submission_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `answer` TEXT,
  `is_correct` BOOLEAN,
  `points_awarded` INT DEFAULT 0,
  FOREIGN KEY (submission_id) REFERENCES assessment_submissions(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES assessment_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Submission answers table created successfully<br>";
} else {
    echo "Error creating submission answers table: " . $conn->error . "<br>";
}

// Create notifications table
$sql = "CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50),
  `related_id` INT,
  `related_type` VARCHAR(50),
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Notifications table created successfully<br>";
} else {
    echo "Error creating notifications table: " . $conn->error . "<br>";
}

echo "<br>Setup complete! <a href='index.php'>Go to homepage</a>";

$conn->close();
?>