<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/assessment_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$assessment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success_message = '';
$error_message = '';

// Verify job belongs to this employer
if ($job_id > 0) {
    $sql = "SELECT id, title FROM jobs WHERE id = ? AND employer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $job_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: manage-jobs.php");
        exit();
    }
    
    $job = $result->fetch_assoc();
}

// Load assessment if editing
$assessment = null;
$questions = [];

if ($assessment_id > 0) {
    $sql = "SELECT a.* FROM assessments a
            JOIN jobs j ON a.job_id = j.id
            WHERE a.id = ? AND j.employer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $assessment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: manage-jobs.php");
        exit();
    }
    
    $assessment = $result->fetch_assoc();
    $job_id = $assessment['job_id'];
    
    // Get questions
    $questions = getAssessmentQuestions($assessment_id);
    
    // Get job info
    $sql = "SELECT id, title FROM jobs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $job = $result->fetch_assoc();
}

// Process assessment form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_assessment'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $time_limit = intval($_POST['time_limit']);
    $passing_score = intval($_POST['passing_score']);
    $status = $_POST['status'];
    
    if (empty($title)) {
        $error_message = "Assessment title is required.";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            if ($assessment_id > 0) {
                // Update existing assessment
                $sql = "UPDATE assessments SET 
                        title = ?, description = ?, time_limit = ?, 
                        passing_score = ?, status = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiiis", $title, $description, $time_limit, 
                                $passing_score, $status, $assessment_id);
                $stmt->execute();
            } else {
                // Create new assessment
                $sql = "INSERT INTO assessments (job_id, title, description, time_limit, passing_score, status) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ississ", $job_id, $title, $description, $time_limit, 
                                $passing_score, $status);
                $stmt->execute();
                $assessment_id = $conn->insert_id;
            }
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Assessment saved successfully! Now add some questions.";
            
            // Redirect to question editor
            header("Location: edit-assessment-questions.php?id=" . $assessment_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_message = "Error saving assessment: " . $e->getMessage();
        }
    }
}

$base_path = '../';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar navigation -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Employer Navigation</h5>
                </div>
                <div class="list-group list-group-flush">
    <a href="index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
    <a href="company-profile.php" class="list-group-item list-group-item-action <?php echo $active_page == 'profile' ? 'active' : ''; ?>">Company Profile</a>
    <a href="post-job.php" class="list-group-item list-group-item-action <?php echo $active_page == 'post' ? 'active' : ''; ?>">Post a Job</a>
    <a href="manage-jobs.php" class="list-group-item list-group-item-action <?php echo $active_page == 'jobs' ? 'active' : ''; ?>">Manage Jobs</a>
    <a href="manage-applications.php" class="list-group-item list-group-item-action <?php echo $active_page == 'applications' ? 'active' : ''; ?>">Applications</a>
    <a href="interviews.php" class="list-group-item list-group-item-action <?php echo $active_page == 'interviews' ? 'active' : ''; ?>">Interviews</a>
    <a href="../messages/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'messages' ? 'active' : ''; ?>">Messages</a>
    <a href="../notifications/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'notifications' ? 'active' : ''; ?>">Notifications</a>
</div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><?php echo $assessment_id ? 'Edit' : 'Create'; ?> Assessment</h4>
                    <a href="manage-jobs.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Jobs
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <strong>Creating assessment for:</strong> <?php echo htmlspecialchars($job['title']); ?>
                    </div>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="title" class="form-label">Assessment Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   placeholder="e.g., PHP Developer Skills Assessment" required
                                   value="<?php echo htmlspecialchars($assessment['title'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Assessment Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                     placeholder="Instructions for candidates taking this assessment"><?php echo htmlspecialchars($assessment['description'] ?? ''); ?></textarea>
                            <div class="form-text">Provide clear instructions about what the assessment covers.</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="time_limit" class="form-label">Time Limit (minutes)</label>
                                <input type="number" class="form-control" id="time_limit" name="time_limit" 
                                       min="0" max="180" value="<?php echo intval($assessment['time_limit'] ?? 30); ?>">
                                <div class="form-text">Set to 0 for no time limit</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="passing_score" class="form-label">Passing Score (%)</label>
                                <input type="number" class="form-control" id="passing_score" name="passing_score" 
                                       min="1" max="100" value="<?php echo intval($assessment['passing_score'] ?? 70); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Assessment Status</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_draft" 
                                       value="draft" <?php echo (!isset($assessment['status']) || $assessment['status'] === 'draft') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status_draft">
                                    Draft (not visible to candidates)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_active" 
                                       value="active" <?php echo (isset($assessment['status']) && $assessment['status'] === 'active') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status_active">
                                    Active (candidates can take this assessment)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_inactive" 
                                       value="inactive" <?php echo (isset($assessment['status']) && $assessment['status'] === 'inactive') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="status_inactive">
                                    Inactive (temporarily disabled)
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="save_assessment" class="btn btn-primary">
                                Save Assessment
                            </button>
                            <?php if ($assessment_id): ?>
                                <a href="edit-assessment-questions.php?id=<?php echo $assessment_id; ?>" class="btn btn-outline-primary">
                                    Manage Questions
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>