<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/assessment_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as jobseeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$assessment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';
$success_message = '';

// Verify assessment exists and is active
$sql = "SELECT a.*, j.title as job_title, j.id as job_id, 
        ep.company_name as company_name
        FROM assessments a
        JOIN jobs j ON a.job_id = j.id
        LEFT JOIN users u ON j.employer_id = u.id
        LEFT JOIN employer_profiles ep ON u.id = ep.user_id
        WHERE a.id = ? AND a.status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assessment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../jobs/index.php");
    exit();
}

$assessment = $result->fetch_assoc();

// Check if user already completed this assessment
$sql = "SELECT * FROM assessment_submissions 
        WHERE assessment_id = ? AND user_id = ? AND status = 'completed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $assessment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $submission = $result->fetch_assoc();
    header("Location: results.php?id=" . $submission['id']);
    exit();
}

// Create or get in-progress submission
$submission_id = getOrCreateSubmission($assessment_id, $user_id);
if (!$submission_id) {
    $error_message = "Error creating assessment submission.";
}

// Get questions
$questions = getAssessmentQuestions($assessment_id);
if (empty($questions)) {
    $error_message = "No questions found for this assessment.";
}

// Process answers
$success = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_assessment'])) {
    // Calculate end time
    $end_time = date('Y-m-d H:i:s');
    
    // Get total possible points
    $total_points = 0;
    foreach ($questions as $question) {
        $total_points += $question['points'];
    }
    
    // Process answers and calculate score
    $score = 0;
    $answers = [];
    
    foreach ($questions as $question) {
        $question_id = $question['id'];
        $answer = null;
        $is_correct = 0;
        $points_awarded = 0;
        
        // Get answer based on question type
        if ($question['question_type'] === 'multiple_choice') {
            if (isset($_POST['q_' . $question_id])) {
                $answer = $_POST['q_' . $question_id];
                if ($answer === $question['correct_answer']) {
                    $is_correct = 1;
                    $points_awarded = $question['points'];
                    $score += $points_awarded;
                }
            }
        } elseif ($question['question_type'] === 'checkbox') {
            if (isset($_POST['q_' . $question_id]) && is_array($_POST['q_' . $question_id])) {
                $selected_answers = $_POST['q_' . $question_id];
                $answer = json_encode($selected_answers);
                
                // Compare with correct answers
                $correct_answers = json_decode($question['correct_answer'], true);
                $correct_count = 0;
                
                // Count correct selections
                foreach ($selected_answers as $selected) {
                    if (in_array($selected, $correct_answers)) {
                        $correct_count++;
                    }
                }
                
                // Calculate partial credit
                if (count($correct_answers) > 0 && $correct_count > 0) {
                    $percentage_correct = $correct_count / count($correct_answers);
                    $points_awarded = round($question['points'] * $percentage_correct);
                    $score += $points_awarded;
                    
                    if ($percentage_correct >= 0.5) {
                        $is_correct = 1;
                    }
                }
            }
        } elseif ($question['question_type'] === 'boolean') {
            if (isset($_POST['q_' . $question_id])) {
                $answer = $_POST['q_' . $question_id];
                if ($answer === $question['correct_answer']) {
                    $is_correct = 1;
                    $points_awarded = $question['points'];
                    $score += $points_awarded;
                }
            }
        } elseif ($question['question_type'] === 'text') {
            if (isset($_POST['q_' . $question_id])) {
                $answer = $_POST['q_' . $question_id];
                
                // Case insensitive match for text answers
                if (strtolower(trim($answer)) === strtolower(trim($question['correct_answer']))) {
                    $is_correct = 1;
                    $points_awarded = $question['points'];
                    $score += $points_awarded;
                }
            }
        }
        
        // Save answer
        $answers[] = [
            'question_id' => $question_id,
            'answer' => $answer,
            'is_correct' => $is_correct,
            'points_awarded' => $points_awarded
        ];
    }
    
    // Calculate percentage score
    $percentage_score = ($total_points > 0) ? round(($score / $total_points) * 100) : 0;
    
    // Determine if passed based on assessment passing score
    $is_passed = ($percentage_score >= $assessment['passing_score']) ? 1 : 0;
    
    // Update submission
    $success = completeAssessment($submission_id, $end_time, $score, $is_passed, $answers);
    
    if ($success) {
        // Create notification for user
        if (function_exists('createNotification')) {
            $title = "Assessment Completed";
            $message = "You have completed the assessment for \"{$assessment['job_title']}\". ";
            $message .= $is_passed ? "Congratulations! You passed with a score of $percentage_score%." : "Your score was $percentage_score%. The minimum required was {$assessment['passing_score']}%.";
            
            createNotification($user_id, 'assessment', $title, $message, $assessment['job_id'], "../jobs/view.php?id=" . $assessment['job_id']);
        }
        
        // Redirect to results page
        header("Location: results.php?id=" . $submission_id);
        exit();
    } else {
        $error_message = "Error saving your answers. Please try again.";
    }
}

$base_path = '../';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><?php echo htmlspecialchars($assessment['title']); ?></h3>
                </div>
                
                <div class="card-body">
                    <div class="assessment-info mb-4">
                        <p><strong>Job:</strong> <?php echo htmlspecialchars($assessment['job_title']); ?></p>
                        <p><strong>Company:</strong> <?php echo htmlspecialchars($assessment['company_name'] ?? 'Company'); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($assessment['description'])); ?></p>
                        
                        <?php if ($assessment['time_limit'] > 0): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-clock"></i> Time Limit: <?php echo $assessment['time_limit']; ?> minutes
                                <div id="timer" class="mt-2 fw-bold"></div>
                            </div>
                        <?php endif; ?>
                        
                        <p><strong>Passing Score:</strong> <?php echo $assessment['passing_score']; ?>%</p>
                    </div>
                    
                    <?php if (!empty($questions)): ?>
                        <form id="assessment-form" method="post" action="">
                            <?php foreach ($questions as $index => $question): ?>
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">
                                            Question <?php echo $index + 1; ?> 
                                            <small class="text-muted">(<?php echo $question['points']; ?> point<?php echo $question['points'] > 1 ? 's' : ''; ?>)</small>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="question-text"><?php echo nl2br(htmlspecialchars($question['question'])); ?></p>
                                        
                                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                            <?php $options = json_decode($question['options'], true); ?>
                                            <?php if (is_array($options)): ?>
                                                <?php foreach ($options as $option): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="q_<?php echo $question['id']; ?>" id="option_<?php echo $question['id'] . '_' . md5($option); ?>" value="<?php echo htmlspecialchars($option); ?>" required>
                                                        <label class="form-check-label" for="option_<?php echo $question['id'] . '_' . md5($option); ?>">
                                                            <?php echo htmlspecialchars($option); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        <?php elseif ($question['question_type'] === 'checkbox'): ?>
                                            <?php $options = json_decode($question['options'], true); ?>
                                            <?php if (is_array($options)): ?>
                                                <?php foreach ($options as $option): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="q_<?php echo $question['id']; ?>[]" id="option_<?php echo $question['id'] . '_' . md5($option); ?>" value="<?php echo htmlspecialchars($option); ?>">
                                                        <label class="form-check-label" for="option_<?php echo $question['id'] . '_' . md5($option); ?>">
                                                            <?php echo htmlspecialchars($option); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        <?php elseif ($question['question_type'] === 'boolean'): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="q_<?php echo $question['id']; ?>" id="option_<?php echo $question['id']; ?>_true" value="True" required>
                                                <label class="form-check-label" for="option_<?php echo $question['id']; ?>_true">
                                                    True
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="q_<?php echo $question['id']; ?>" id="option_<?php echo $question['id']; ?>_false" value="False" required>
                                                <label class="form-check-label" for="option_<?php echo $question['id']; ?>_false">
                                                    False
                                                </label>
                                            </div>
                                        <?php elseif ($question['question_type'] === 'text'): ?>
                                            <div class="form-group">
                                                <input type="text" class="form-control" name="q_<?php echo $question['id']; ?>" placeholder="Your answer" required>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="submit_assessment" class="btn btn-primary btn-lg">
                                    Submit Assessment
                                </button>
                                <a href="../jobs/view.php?id=<?php echo $assessment['job_id']; ?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            This assessment does not have any questions yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($assessment['time_limit'] > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set timer
    const timeLimit = <?php echo $assessment['time_limit']; ?> * 60; // Convert to seconds
    let timeRemaining = timeLimit;
    const timerDisplay = document.getElementById('timer');
    
    function updateTimer() {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        
        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')} remaining`;
        
        if (timeRemaining <= 300) { // 5 minutes or less
            timerDisplay.classList.add('text-danger');
        }
        
        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            document.getElementById('assessment-form').submit();
        }
        
        timeRemaining--;
    }
    
    updateTimer();
    const timerInterval = setInterval(updateTimer, 1000);
    
    // Warn before leaving page
    window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = '';
    });
    
    // Don't warn when submitting form
    document.getElementById('assessment-form').addEventListener('submit', function() {
        window.removeEventListener('beforeunload', function() {});
    });
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>