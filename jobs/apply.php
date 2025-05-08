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
$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if job exists
$sql = "SELECT j.*, c.name as category_name, 
        u.username as employer_username, ep.company_name,
        (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
        FROM jobs j
        LEFT JOIN job_categories c ON j.category_id = c.id
        LEFT JOIN users u ON j.employer_id = u.id
        LEFT JOIN employer_profiles ep ON u.id = ep.user_id
        WHERE j.id = ? AND j.status = 'active'";

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
                
                <form action="process_application.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resume" class="form-label">Resume (PDF)</label>
                        <input type="file" class="form-control" id="resume" name="resume" accept=".pdf" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cover_letter" class="form-label">Cover Letter</label>
                        <textarea class="form-control" id="cover_letter" name="cover_letter" rows="4" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>