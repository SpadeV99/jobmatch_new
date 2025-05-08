<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/assessment_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify access to this submission
if ($user_type === 'jobseeker') {
    $sql = "SELECT s.*, a.title as assessment_title, a.passing_score, j.id as job_id, j.title as job_title,
            ep.company_name
            FROM assessment_submissions s
            JOIN assessments a ON s.assessment_id = a.id
            JOIN jobs j ON a.job_id = j.id
            LEFT JOIN users u ON j.employer_id = u.id
            LEFT JOIN employer_profiles ep ON u.id = ep.user_id
            WHERE s.id = ? AND s.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $submission_id, $user_id);
} else if ($user_type === 'employer') {
    $sql = "SELECT s.*, a.title as assessment_title, a.passing_score, j.id as job_id, j.title as job_title,
            u.username as applicant_name, CONCAT(jp.first_name, ' ', jp.last_name) as applicant_fullname,
            ep.company_name
            FROM assessment_submissions s
            JOIN assessments a ON s.assessment_id = a.id
            JOIN jobs j ON a.job_id = j.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
            LEFT JOIN users e ON j.employer_id = e.id
            LEFT JOIN employer_profiles ep ON e.id = ep.user_id
            WHERE s.id = ? AND j.employer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $submission_id, $user_id);
} else {
    header("Location: ../index.php");
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php");
    exit();
}

$submission = $result->fetch_assoc();

// Get answers and questions
$answers = getAssessmentAnswers($submission_id);

$base_path = '../';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Assessment Results</h3>
                </div>
                
                <div class="card-body">
                    <div class="assessment-info mb-4">
                        <h4><?php echo htmlspecialchars($submission['assessment_title']); ?></h4>
                        <p><strong>Job:</strong> <?php echo htmlspecialchars($submission['job_title']); ?></p>
                        <p><strong>Company:</strong> <?php echo htmlspecialchars($submission['company_name'] ?? 'Company'); ?></p>
                        
                        <?php if ($user_type === 'employer'): ?>
                            <p><strong>Applicant:</strong> <?php echo htmlspecialchars($submission['applicant_fullname'] ? $submission['applicant_fullname'] : $submission['applicant_name']); ?></p>
                        <?php endif; ?>
                        
                        <p><strong>Completed on:</strong> <?php echo date('F j, Y, g:i a', strtotime($submission['end_time'])); ?></p>
                        
                        <div class="result-summary p-3 border rounded mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Your Score: <?php echo $submission['score']; ?></h5>
                                    <div class="progress mt-2">
                                        <?php 
                                        $score_percentage = round(($submission['score'] / array_sum(array_column($answers, 'max_points'))) * 100);
                                        $score_class = $submission['is_passed'] ? 'bg-success' : 'bg-danger';
                                        ?>
                                        <div class="progress-bar <?php echo $score_class; ?>" role="progressbar" 
                                             style="width: <?php echo $score_percentage; ?>%" 
                                             aria-valuenow="<?php echo $score_percentage; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $score_percentage; ?>%
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h5>
                                        <?php if ($submission['is_passed']): ?>
                                            <span class="badge bg-success">PASSED</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">FAILED</span>
                                        <?php endif; ?>
                                    </h5>
                                    <p>Passing score: <?php echo $submission['passing_score']; ?>%</p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($user_type === 'jobseeker' && !$submission['is_passed']): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> You did not pass this assessment. Please review your answers below.
                                <?php if ($submission['status'] !== 'expired'): ?>
                                    <div class="mt-2">
                                        <a href="../jobs/view.php?id=<?php echo $submission['job_id']; ?>" class="btn btn-primary">
                                            View Job Details
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($user_type === 'jobseeker' && $submission['is_passed']): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> Congratulations! You passed the assessment.
                                <div class="mt-2">
                                    <a href="../jobs/view.php?id=<?php echo $submission['job_id']; ?>" class="btn btn-primary">
                                        View Job Details
                                    </a>
                                    <a href="../jobs/apply.php?id=<?php echo $submission['job_id']; ?>" class="btn btn-outline-success ms-2">
                                        Apply Now
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($answers)): ?>
                        <h5 class="mb-3">Question Details</h5>
                        
                        <?php foreach ($answers as $index => $answer): ?>
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                    <span>Question <?php echo $index + 1; ?></span>
                                    <div>
                                        <?php if ($answer['is_correct']): ?>
                                            <span class="badge bg-success">Correct</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Incorrect</span>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary ms-1"><?php echo $answer['points_awarded']; ?>/<?php echo $answer['max_points']; ?> points</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p><?php echo nl2br(htmlspecialchars($answer['question'])); ?></p>
                                    
                                    <div class="mt-3">
                                        <h6>Your Answer:</h6>
                                        <?php if ($answer['question_type'] === 'multiple_choice' || $answer['question_type'] === 'boolean'): ?>
                                            <p><?php echo htmlspecialchars($answer['answer'] ?? 'No answer'); ?></p>
                                        <?php elseif ($answer['question_type'] === 'checkbox'): ?>
                                            <?php 
                                            $selected = json_decode($answer['answer'], true);
                                            if (!empty($selected) && is_array($selected)): 
                                            ?>
                                                <ul>
                                                    <?php foreach ($selected as $option): ?>
                                                        <li><?php echo htmlspecialchars($option); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p>No answer</p>
                                            <?php endif; ?>
                                        <?php elseif ($answer['question_type'] === 'text'): ?>
                                            <p><?php echo htmlspecialchars($answer['answer'] ?? 'No answer'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!$answer['is_correct']): ?>
                                        <div class="mt-3">
                                            <h6>Correct Answer:</h6>
                                            <?php if ($answer['question_type'] === 'multiple_choice' || $answer['question_type'] === 'boolean' || $answer['question_type'] === 'text'): ?>
                                                <p><?php echo htmlspecialchars($answer['correct_answer']); ?></p>
                                            <?php elseif ($answer['question_type'] === 'checkbox'): ?>
                                                <?php 
                                                $correct = json_decode($answer['correct_answer'], true);
                                                if (!empty($correct) && is_array($correct)): 
                                                ?>
                                                    <ul>
                                                        <?php foreach ($correct as $option): ?>
                                                            <li><?php echo htmlspecialchars($option); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="mt-4 d-flex justify-content-between">
                        <?php if ($user_type === 'jobseeker'): ?>
                            <a href="../jobs/index.php" class="btn btn-outline-primary">Back to Jobs</a>
                            <?php if ($submission['is_passed']): ?>
                                <a href="../jobs/apply.php?id=<?php echo $submission['job_id']; ?>" class="btn btn-success">
                                    Apply for Job
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="../employer/assessment-results.php?assessment_id=<?php echo $submission['assessment_id']; ?>" class="btn btn-outline-primary">
                                Back to Assessment Results
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>