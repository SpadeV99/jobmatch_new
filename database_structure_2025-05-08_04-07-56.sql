-- JobMatch Database Structure Export
-- Generated: 2025-05-08 04:07:56

-- Table structure for table `application_status_history`
CREATE TABLE `application_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `application_id` int NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `changed_by` int DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `application_status_history_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `application_status_history`
-- id (int), application_id (int), status (varchar(50)), notes (text), changed_by (int), timestamp (timestamp)

-- Table structure for table `assessment_questions`
CREATE TABLE `assessment_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assessment_id` int NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','coding') COLLATE utf8mb4_general_ci NOT NULL,
  `question_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `options` text COLLATE utf8mb4_general_ci,
  `correct_answer` text COLLATE utf8mb4_general_ci,
  `points` int DEFAULT '1',
  `order` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assessment_id` (`assessment_id`),
  CONSTRAINT `assessment_questions_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `assessment_questions`
-- id (int), assessment_id (int), question_type (enum('multiple_choice','true_false','short_answer','coding')), question_text (text), options (text), correct_answer (text), points (int), order (int)

-- Table structure for table `assessment_submissions`
CREATE TABLE `assessment_submissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assessment_id` int NOT NULL,
  `user_id` int NOT NULL,
  `score` int DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('in_progress','completed','expired') COLLATE utf8mb4_general_ci DEFAULT 'in_progress',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `assessment_id` (`assessment_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `assessment_submissions_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `assessment_submissions`
-- id (int), assessment_id (int), user_id (int), score (int), start_time (datetime), end_time (datetime), status (enum('in_progress','completed','expired')), created_at (datetime), updated_at (datetime)

-- Table structure for table `assessments`
CREATE TABLE `assessments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `employer_id` int NOT NULL,
  `job_id` int DEFAULT NULL,
  `time_limit` int DEFAULT '0',
  `passing_score` int DEFAULT NULL,
  `status` enum('draft','active','archived') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employer_id` (`employer_id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessments_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `assessments`
-- id (int), title (varchar(255)), description (text), employer_id (int), job_id (int), time_limit (int), passing_score (int), status (enum('draft','active','archived')), created_at (datetime), updated_at (datetime)

-- Table structure for table `conversations`
CREATE TABLE `conversations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user1_id` int NOT NULL,
  `user2_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_conversation` (`user1_id`,`user2_id`),
  KEY `user2_id` (`user2_id`),
  CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Columns in `conversations`
-- id (int), user1_id (int), user2_id (int), created_at (datetime), updated_at (datetime)

-- Table structure for table `employer_profiles`
CREATE TABLE `employer_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `company_description` text COLLATE utf8mb4_general_ci,
  `industry` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zip_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `employer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `employer_profiles`
-- id (int), user_id (int), company_name (varchar(255)), company_description (text), industry (varchar(100)), website (varchar(255)), phone (varchar(20)), address (varchar(255)), city (varchar(100)), state (varchar(100)), zip_code (varchar(20)), country (varchar(100)), logo_path (varchar(255))

-- Table structure for table `interview_feedback`
CREATE TABLE `interview_feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `interview_id` int NOT NULL,
  `rating` int NOT NULL,
  `strengths` text COLLATE utf8mb4_general_ci,
  `weaknesses` text COLLATE utf8mb4_general_ci,
  `technical_skills` text COLLATE utf8mb4_general_ci,
  `communication_skills` text COLLATE utf8mb4_general_ci,
  `cultural_fit` text COLLATE utf8mb4_general_ci,
  `overall_notes` text COLLATE utf8mb4_general_ci,
  `recommendation` enum('strong_hire','hire','maybe','do_not_hire') COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `interview_id` (`interview_id`),
  CONSTRAINT `interview_feedback_ibfk_1` FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `interview_feedback`
-- id (int), interview_id (int), rating (int), strengths (text), weaknesses (text), technical_skills (text), communication_skills (text), cultural_fit (text), overall_notes (text), recommendation (enum('strong_hire','hire','maybe','do_not_hire')), created_at (datetime), updated_at (datetime)

-- Table structure for table `interview_reschedule_requests`
CREATE TABLE `interview_reschedule_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `interview_id` int NOT NULL,
  `requested_by` int NOT NULL,
  `proposed_date` datetime NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','accepted','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `response_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `interview_id` (`interview_id`),
  KEY `requested_by` (`requested_by`),
  KEY `response_by` (`response_by`),
  CONSTRAINT `interview_reschedule_requests_ibfk_1` FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interview_reschedule_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interview_reschedule_requests_ibfk_3` FOREIGN KEY (`response_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `interview_reschedule_requests`
-- id (int), interview_id (int), requested_by (int), proposed_date (datetime), reason (text), status (enum('pending','accepted','rejected')), response_by (int), created_at (datetime), updated_at (datetime)

-- Table structure for table `interviews`
CREATE TABLE `interviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_id` int NOT NULL,
  `jobseeker_id` int NOT NULL,
  `application_id` int NOT NULL,
  `employer_id` int DEFAULT NULL,
  `interview_date` datetime NOT NULL,
  `duration_minutes` int NOT NULL DEFAULT '30',
  `interview_type` enum('video','phone','in_person') COLLATE utf8mb4_general_ci NOT NULL,
  `location` text COLLATE utf8mb4_general_ci,
  `meeting_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `status` enum('scheduled','completed','cancelled','rescheduled','no_show') COLLATE utf8mb4_general_ci DEFAULT 'scheduled',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `employer_notes` text COLLATE utf8mb4_general_ci,
  `jobseeker_notes` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `application_id` (`application_id`),
  KEY `jobseeker_id` (`jobseeker_id`),
  CONSTRAINT `interviews_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interviews_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interviews_ibfk_3` FOREIGN KEY (`jobseeker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `interviews`
-- id (int), job_id (int), jobseeker_id (int), application_id (int), employer_id (int), interview_date (datetime), duration_minutes (int), interview_type (enum('video','phone','in_person')), location (text), meeting_link (varchar(255)), notes (text), status (enum('scheduled','completed','cancelled','rescheduled','no_show')), created_at (datetime), updated_at (datetime), employer_notes (text), jobseeker_notes (text)

-- Table structure for table `job_applications`
CREATE TABLE `job_applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `job_id` int NOT NULL,
  `apply_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `resume_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cover_letter` text COLLATE utf8mb4_general_ci,
  `status` enum('pending','shortlisted','interviewed','offered','rejected','withdrawn') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `job_applications`
-- id (int), user_id (int), job_id (int), apply_date (datetime), resume_path (varchar(255)), cover_letter (text), status (enum('pending','shortlisted','interviewed','offered','rejected','withdrawn'))

-- Table structure for table `job_categories`
CREATE TABLE `job_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `job_categories`
-- id (int), name (varchar(255))

-- Table structure for table `job_skills`
CREATE TABLE `job_skills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_id` int NOT NULL,
  `skill_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `job_skills_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `job_skills`
-- id (int), job_id (int), skill_name (varchar(255))

-- Table structure for table `jobs`
CREATE TABLE `jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `job_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Full-time',
  `salary` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `posted_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `active` tinyint(1) DEFAULT '1',
  `employer_id` int DEFAULT NULL,
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `required_experience` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `fk_employer_id` (`employer_id`),
  CONSTRAINT `fk_employer_id` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `job_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `jobs`
-- id (int), title (varchar(255)), description (text), category_id (int), location (varchar(255)), job_type (varchar(50)), salary (varchar(100)), posted_date (datetime), active (tinyint(1)), employer_id (int), salary_min (decimal(10,2)), salary_max (decimal(10,2)), required_experience (int)

-- Table structure for table `jobseeker_profiles`
CREATE TABLE `jobseeker_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zip_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resume_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `skills` text COLLATE utf8mb4_general_ci,
  `experience` text COLLATE utf8mb4_general_ci,
  `education` text COLLATE utf8mb4_general_ci,
  `preferred_location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expected_salary` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `experience_years` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `jobseeker_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `jobseeker_profiles`
-- id (int), user_id (int), first_name (varchar(100)), last_name (varchar(100)), phone (varchar(20)), address (varchar(255)), city (varchar(100)), state (varchar(100)), zip_code (varchar(20)), country (varchar(100)), resume_path (varchar(255)), skills (text), experience (text), education (text), preferred_location (varchar(255)), expected_salary (varchar(100)), experience_years (int)

-- Table structure for table `messages`
CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `conversation_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Columns in `messages`
-- id (int), conversation_id (int), sender_id (int), message (text), is_read (tinyint), created_at (datetime)

-- Table structure for table `notifications`
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `related_id` int DEFAULT NULL,
  `related_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `notifications`
-- id (int), user_id (int), title (varchar(255)), message (text), type (varchar(50)), related_id (int), related_type (varchar(50)), is_read (tinyint(1)), created_at (datetime)

-- Table structure for table `saved_jobs`
CREATE TABLE `saved_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `job_id` int NOT NULL,
  `date_saved` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_job_unique` (`user_id`,`job_id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `saved_jobs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_jobs_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `saved_jobs`
-- id (int), user_id (int), job_id (int), date_saved (datetime)

-- Table structure for table `submission_answers`
CREATE TABLE `submission_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `submission_id` int NOT NULL,
  `question_id` int NOT NULL,
  `answer` text COLLATE utf8mb4_general_ci,
  `is_correct` tinyint(1) DEFAULT NULL,
  `points_awarded` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `submission_answers_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `assessment_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submission_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `submission_answers`
-- id (int), submission_id (int), question_id (int), answer (text), is_correct (tinyint(1)), points_awarded (int)

-- Table structure for table `user_preference_weights`
CREATE TABLE `user_preference_weights` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `criteria_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `weight` float NOT NULL,
  `criteria_order` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_preference_weights_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `user_preference_weights`
-- id (int), user_id (int), criteria_name (varchar(255)), weight (float), criteria_order (int)

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `user_type` enum('jobseeker','employer','admin') COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Columns in `users`
-- id (int), username (varchar(50)), password (varchar(255)), email (varchar(100)), user_type (enum('jobseeker','employer','admin')), created_at (datetime)

