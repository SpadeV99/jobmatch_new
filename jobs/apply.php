<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/assessment_functions.php'; // Add this line

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

// Check if job exists
$sql = "SELECT j.*, e.company_name, e.logo_path,
        c.name as category_name
        FROM jobs j 
        LEFT JOIN employer_profiles e ON j.employer_id = e.user_id
        LEFT JOIN job_categories c ON j.category_id = c.id
        WHERE j.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$job = $result->fetch_assoc();

// Check if already applied
$sql = "SELECT id, status FROM job_applications WHERE job_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $application = $result->fetch_assoc();
    header("Location: ../user/applications.php?message=already_applied");
    exit();
}

// Check if job requires assessment
$required_assessment_id = jobRequiresAssessment($job_id);
if ($required_assessment_id !== false) {
    // Check if user has passed the assessment
    $passed_submission = hasPassedJobAssessment($user_id, $job_id);
    
    if ($passed_submission === false) {
        // Redirect to take assessment first
        header("Location: ../assessments/take.php?id=" . $required_assessment_id);
        exit();
    }
}

// Process form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form processing code here...
    $cover_letter = trim($_POST['cover_letter']);
    
    // Simple validation
    if (empty($cover_letter)) {
        $error_message = "Please provide a cover letter.";
    } else {
        // Insert application
        $sql = "INSERT INTO job_applications (user_id, job_id, cover_letter, apply_date, status) 
                VALUES (?, ?, ?, NOW(), 'pending')";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $user_id, $job_id, $cover_letter);
        
        if ($stmt->execute()) {
            $application_id = $conn->insert_id;
            
            // Create notification for employer
            if (function_exists('createNotification')) {
                $title = "New Job Application";
                $message = "A new application has been submitted for \"{$job['title']}\".";
                
                createNotification(
                    $job['employer_id'],
                    'application',
                    $title,
                    $message,
                    $application_id,
                    "../employer/view-application.php?id=" . $application_id
                );
            }
            
            header("Location: ../user/applications.php?message=application_submitted");
            exit();
        } else {
            $error_message = "Error submitting your application. Please try again.";
        }
    }
}

$base_path = '../';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mb-4">
        <h1>Apply for Job</h1>
        <div class="card mb-4">
            <div class="card-body">
                <h4><?php echo htmlspecialchars($job["title"]); ?></h4>
                <h6 class="text-muted"><?php echo htmlspecialchars($job["category_name"]); ?></h6>
                <hr>
                <div class="mb-3">
                    <strong>Location:</strong> <?php echo htmlspecialchars($job["location"]); ?>
                </div>
                <div class="mb-3">
                    <strong>Salary:</strong> <?php echo htmlspecialchars($job["salary"]); ?>
                </div>
                <div class="mb-3">
                    <strong>Description:</strong>
                    <p><?php echo nl2br(htmlspecialchars($job["description"])); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Application Form</h5>
                <p class="text-muted">Please login first to apply for this job</p>
                
                <form action="<?php echo $_SERVER['PHP_SELF'] . '?job_id=' . $job_id; ?>" method="post">
                    <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                    
                    <div class="mb-3">
                        <label for="cover_letter" class="form-label">Cover Letter</label>
                        <textarea class="form-control" id="cover_letter" name="cover_letter" rows="6" required 
                            placeholder="Explain why you're a good fit for this position..."><?php echo isset($_POST['cover_letter']) ? htmlspecialchars($_POST['cover_letter']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Submit Application</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>