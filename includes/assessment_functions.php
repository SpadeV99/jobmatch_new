<?php
/**
 * Functions for handling job assessments
 */

/**
 * Create a new assessment submission or get existing in-progress one
 */
function createAssessmentSubmission($assessmentId, $userId) {
    global $conn;
    
    // Check if there's already an in-progress submission
    $sql = "SELECT id FROM assessment_submissions 
            WHERE assessment_id = ? AND user_id = ? AND status = 'in_progress'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $assessmentId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    // Create new submission
    $sql = "INSERT INTO assessment_submissions (assessment_id, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $assessmentId, $userId);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

/**
 * Get assessment questions with options
 */
function getAssessmentQuestions($assessmentId) {
    global $conn;
    
    $sql = "SELECT * FROM assessment_questions 
            WHERE assessment_id = ? 
            ORDER BY `order` ASC, id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assessmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $questions = [];
    while ($row = $result->fetch_assoc()) {
        // Parse options JSON if it exists
        if (!empty($row['options'])) {
            $row['options_array'] = json_decode($row['options'], true);
        } else {
            $row['options_array'] = [];
        }
        
        // Parse correct answer JSON if it's an array
        if (!empty($row['correct_answer']) && $row['question_type'] === 'checkbox') {
            $row['correct_answer_array'] = json_decode($row['correct_answer'], true);
        }
        
        $questions[] = $row;
    }
    
    return $questions;
}

/**
 * Save an answer for a question
 */
function saveAssessmentAnswer($submissionId, $questionId, $answer) {
    global $conn;
    
    // Get question details to check correctness
    $sql = "SELECT question_type, correct_answer, points FROM assessment_questions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $questionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $question = $result->fetch_assoc();
    
    // Determine if answer is correct
    $isCorrect = null;
    $pointsEarned = 0;
    
    // Only evaluate correctness for questions with correct answers
    if (!empty($question['correct_answer'])) {
        
        // For checkbox (multiple answers)
        if ($question['question_type'] === 'checkbox' && is_array($answer)) {
            $correctAnswers = json_decode($question['correct_answer'], true);
            sort($correctAnswers);
            
            $submittedAnswers = $answer;
            sort($submittedAnswers);
            
            // Convert arrays to JSON for comparison
            $isCorrect = (json_encode($correctAnswers) === json_encode($submittedAnswers)) ? 1 : 0;
            
            // For JSON storage
            $answer = json_encode($answer);
        }
        // For multiple choice, boolean, or text
        else {
            // For text questions, do a case-insensitive comparison
            if ($question['question_type'] === 'text') {
                $isCorrect = (strtolower(trim($answer)) === strtolower(trim($question['correct_answer']))) ? 1 : 0;
            } else {
                $isCorrect = ($answer == $question['correct_answer']) ? 1 : 0;
            }
        }
        
        // If correct, award points
        if ($isCorrect) {
            $pointsEarned = $question['points'];
        }
    }
    
    // Check if answer already exists
    $sql = "SELECT id FROM assessment_answers WHERE submission_id = ? AND question_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $submissionId, $questionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing answer
        $row = $result->fetch_assoc();
        $sql = "UPDATE assessment_answers 
                SET answer = ?, is_correct = ?, points_earned = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siii", $answer, $isCorrect, $pointsEarned, $row['id']);
    } else {
        // Insert new answer
        $sql = "INSERT INTO assessment_answers 
                (submission_id, question_id, answer, is_correct, points_earned) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisii", $submissionId, $questionId, $answer, $isCorrect, $pointsEarned);
    }
    
    return $stmt->execute();
}

/**
 * Complete an assessment submission and calculate score
 */
function completeAssessmentSubmission($submissionId) {
    global $conn;
    
    // Calculate total possible points for the assessment
    $sql = "SELECT 
                a.id, a.passing_score, SUM(q.points) as total_possible
            FROM 
                assessment_submissions s
                JOIN assessments a ON s.assessment_id = a.id
                JOIN assessment_questions q ON a.id = q.assessment_id
            WHERE s.id = ?
            GROUP BY a.id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $assessmentInfo = $result->fetch_assoc();
    $totalPossiblePoints = $assessmentInfo['total_possible'];
    $passingScore = $assessmentInfo['passing_score'];
    
    // Calculate points earned
    $sql = "SELECT COALESCE(SUM(points_earned), 0) as points_earned
            FROM assessment_answers
            WHERE submission_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $pointsEarned = $row['points_earned'];
    
    // Calculate score as percentage
    $score = ($totalPossiblePoints > 0) ? round(($pointsEarned / $totalPossiblePoints) * 100) : 0;
    
    // Determine if passed
    $passed = ($score >= $passingScore) ? 1 : 0;
    
    // Update submission
    $sql = "UPDATE assessment_submissions
            SET status = 'completed', end_time = CURRENT_TIMESTAMP,
                score = ?, passed = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $score, $passed, $submissionId);
    
    if ($stmt->execute()) {
        // If passed, notify the employer
        if ($passed) {
            notifyEmployerAboutPassedAssessment($submissionId);
        }
        return true;
    }
    
    return false;
}

/**
 * Get assessment submission details
 */
function getAssessmentSubmissionDetails($submissionId) {
    global $conn;
    
    $sql = "SELECT 
                s.*, a.title, a.description, a.passing_score, a.job_id,
                j.title as job_title, j.employer_id,
                ep.company_name,
                u.username as user_name,
                jp.first_name, jp.last_name
            FROM 
                assessment_submissions s
                JOIN assessments a ON s.assessment_id = a.id
                JOIN jobs j ON a.job_id = j.id
                LEFT JOIN users e ON j.employer_id = e.id
                LEFT JOIN employer_profiles ep ON e.id = ep.user_id
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN jobseeker_profiles jp ON s.user_id = jp.user_id
            WHERE s.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $submission = $result->fetch_assoc();
    
    // Get answers
    $sql = "SELECT 
                ans.*, q.question, q.question_type, q.options, q.correct_answer, q.points
            FROM 
                assessment_answers ans
                JOIN assessment_questions q ON ans.question_id = q.id
            WHERE ans.submission_id = ?
            ORDER BY q.order, q.id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $answers = [];
    while ($row = $result->fetch_assoc()) {
        // Parse options/answers if they're in JSON format
        if (!empty($row['options'])) {
            $row['options_array'] = json_decode($row['options'], true);
        }
        
        if ($row['question_type'] === 'checkbox' && !empty($row['answer'])) {
            $row['answer_array'] = json_decode($row['answer'], true);
        }
        
        if ($row['question_type'] === 'checkbox' && !empty($row['correct_answer'])) {
            $row['correct_answer_array'] = json_decode($row['correct_answer'], true);
        }
        
        $answers[] = $row;
    }
    
    $submission['answers'] = $answers;
    return $submission;
}

/**
 * Notify employer about passed assessment
 */
function notifyEmployerAboutPassedAssessment($submissionId) {
    global $conn;
    
    // Get submission details
    $submission = getAssessmentSubmissionDetails($submissionId);
    
    if (!$submission) {
        return false;
    }
    
    // Create notification
    if (function_exists('createNotification')) {
        $title = "Assessment Passed: " . $submission['title'];
        $name = !empty($submission['first_name']) ? 
                $submission['first_name'] . ' ' . $submission['last_name'] : 
                $submission['user_name'];
        
        $message = "$name has passed the assessment for job: " . $submission['job_title'] . 
                  " with a score of " . $submission['score'] . "%";
        
        return createNotification(
            $submission['employer_id'],
            'assessment',
            $title,
            $message,
            $submission['job_id'],
            "../employer/assessment-results.php?submission_id=$submissionId"
        );
    }
    
    return false;
}

/**
 * Get assessment list for a job
 */
function getJobAssessments($jobId) {
    global $conn;
    
    $sql = "SELECT a.*,
            (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as question_count,
            (SELECT COUNT(*) FROM assessment_submissions WHERE assessment_id = a.id) as submission_count,
            (SELECT COUNT(*) FROM assessment_submissions WHERE assessment_id = a.id AND passed = 1) as passed_count
            FROM assessments a
            WHERE a.job_id = ?
            ORDER BY a.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assessments = [];
    while ($row = $result->fetch_assoc()) {
        $assessments[] = $row;
    }
    
    return $assessments;
}

/**
 * Check if a user has passed any assessment for a job
 */
function hasUserPassedJobAssessment($userId, $jobId) {
    global $conn;
    
    $sql = "SELECT 1 FROM assessment_submissions s
            JOIN assessments a ON s.assessment_id = a.id
            WHERE s.user_id = ? AND a.job_id = ? AND s.passed = 1
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Get assessment performance data for a user across all jobs
 */
function getUserAssessmentStats($userId) {
    global $conn;
    
    $sql = "SELECT 
                COUNT(DISTINCT s.assessment_id) as total_assessments,
                SUM(CASE WHEN s.passed = 1 THEN 1 ELSE 0 END) as passed_count,
                AVG(s.score) as average_score
            FROM assessment_submissions s
            WHERE s.user_id = ? AND s.status = 'completed'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Check if a job requires an assessment
 * @param int $job_id The job ID to check
 * @return mixed False if no assessment required, or the assessment ID if required
 */
function jobRequiresAssessment($job_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id FROM assessments WHERE job_id = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    return false;
}

/**
 * Check if user has passed an assessment for a specific job
 * @param int $user_id The user ID
 * @param int $job_id The job ID
 * @return bool True if passed, false otherwise
 */
function hasPassedJobAssessment($user_id, $job_id) {
    global $conn;
    
    // Get the assessment ID for this job
    $assessment_id = jobRequiresAssessment($job_id);
    if (!$assessment_id) {
        return true; // No assessment required means automatically passed
    }
    
    // Check for a passing submission
    $stmt = $conn->prepare("SELECT s.id, s.score, a.passing_score 
                           FROM assessment_submissions s
                           JOIN assessments a ON s.assessment_id = a.id
                           WHERE s.user_id = ? AND s.assessment_id = ? 
                           AND s.score >= a.passing_score
                           LIMIT 1");
    
    $stmt->bind_param("ii", $user_id, $assessment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}
?>