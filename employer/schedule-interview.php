<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/interview_functions.php';

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
$application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;
$success_message = '';
$error_message = '';

// Get application details
$sql = "SELECT a.*, j.title as job_title, j.id as job_id, 
        CONCAT(jp.first_name, ' ', jp.last_name) as applicant_name,
        u.id as applicant_id, u.email as applicant_email
        FROM job_applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.user_id = u.id
        LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
        WHERE a.id = ? AND j.employer_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $application_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage-applications.php");
    exit();
}

$application = $result->fetch_assoc();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_interview'])) {
    $interview_date = $_POST['interview_date'] . ' ' . $_POST['interview_time'];
    $duration_minutes = intval($_POST['duration_minutes']);
    $interview_type = $_POST['interview_type'];
    $location = isset($_POST['location']) ? trim($_POST['location']) : null;
    $meeting_link = isset($_POST['meeting_link']) ? trim($_POST['meeting_link']) : null;
    
    // Validate inputs
    $errors = [];
    
    if (empty($interview_date)) {
        $errors[] = "Interview date and time are required.";
    } else {
        $datetime = new DateTime($interview_date);
        $now = new DateTime();
        
        if ($datetime <= $now) {
            $errors[] = "Interview date must be in the future.";
        }
    }
    
    if ($duration_minutes < 15 || $duration_minutes > 180) {
        $errors[] = "Interview duration must be between 15 and 180 minutes.";
    }
    
    if ($interview_type === 'in_person' && empty($location)) {
        $errors[] = "Location is required for in-person interviews.";
    }
    
    if ($interview_type === 'video' && empty($meeting_link)) {
        $errors[] = "Meeting link is required for video interviews.";
    }
    
    if (empty($errors)) {
        // Schedule the interview
        $interview_id = scheduleInterview(
            $application['job_id'],
            $application_id,
            $user_id,
            $application['applicant_id'],
            $interview_date,
            $duration_minutes,
            $interview_type,
            $location,
            $meeting_link
        );
        
        if ($interview_id) {
            $success_message = "Interview scheduled successfully! The candidate has been notified.";
        } else {
            $error_message = "Failed to schedule interview. Please try again.";
        }
    } else {
        $error_message = implode("<br>", $errors);
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Schedule Interview</h4>
                    <a href="view-application.php?id=<?php echo $application_id; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Application
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <div class="text-center mt-3 mb-3">
                            <a href="interviews.php" class="btn btn-primary">View All Interviews</a>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading">Application Details</h5>
                            <p><strong>Job:</strong> <?php echo htmlspecialchars($application['job_title']); ?></p>
                            <p><strong>Applicant:</strong> <?php echo htmlspecialchars($application['applicant_name'] ?: 'Applicant'); ?></p>
                            <p><strong>Application Date:</strong> <?php echo date('F j, Y', strtotime($application['apply_date'])); ?></p>
                        </div>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label class="form-label">Interview Type <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input interview-type" type="radio" name="interview_type" id="type-video" value="video" checked>
                                    <label class="form-check-label" for="type-video">
                                        <i class="bi bi-camera-video me-1"></i> Video Interview
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input interview-type" type="radio" name="interview_type" id="type-phone" value="phone">
                                    <label class="form-check-label" for="type-phone">
                                        <i class="bi bi-telephone me-1"></i> Phone Interview
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input interview-type" type="radio" name="interview_type" id="type-inperson" value="in_person">
                                    <label class="form-check-label" for="type-inperson">
                                        <i class="bi bi-building me-1"></i> In-Person Interview
                                    </label>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="interview_date" class="form-label">Interview Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="interview_date" name="interview_date" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="interview_time" class="form-label">Interview Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="interview_time" name="interview_time" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="duration_minutes" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                                <select class="form-select" id="duration_minutes" name="duration_minutes" required>
                                    <option value="15">15 minutes</option>
                                    <option value="30" selected>30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">1 hour</option>
                                    <option value="90">1.5 hours</option>
                                    <option value="120">2 hours</option>
                                </select>
                            </div>
                            
                            <div id="location-field" class="mb-3" style="display: none;">
                                <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="location" name="location" rows="2" placeholder="Enter the full address for the interview"></textarea>
                                <div class="form-text">Please provide complete address details including any special instructions.</div>
                            </div>
                            
                            <div id="meeting-link-field" class="mb-3">
                                <label for="meeting_link" class="form-label">Meeting Link <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="meeting_link" name="meeting_link" placeholder="e.g. https://zoom.us/j/123456789">
                                <div class="form-text">Provide the URL for Zoom, Microsoft Teams, Google Meet, etc.</div>
                            </div>
                            
                            <div class="alert alert-warning mb-4">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> Once scheduled, the candidate will receive a notification. Make sure all details are correct before submitting.
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="schedule_interview" class="btn btn-primary">
                                    <i class="bi bi-calendar-plus"></i> Schedule Interview
                                </button>
                                <a href="view-application.php?id=<?php echo $application_id; ?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('interview_date').min = tomorrow.toISOString().split('T')[0];
    
    // Handle interview type changes
    const interviewTypeRadios = document.querySelectorAll('.interview-type');
    const locationField = document.getElementById('location-field');
    const meetingLinkField = document.getElementById('meeting-link-field');
    
    interviewTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'in_person') {
                locationField.style.display = 'block';
                meetingLinkField.style.display = 'none';
                document.getElementById('meeting_link').value = '';
            } else if (this.value === 'video') {
                locationField.style.display = 'none';
                meetingLinkField.style.display = 'block';
                document.getElementById('location').value = '';
            } else {
                locationField.style.display = 'none';
                meetingLinkField.style.display = 'none';
                document.getElementById('location').value = '';
                document.getElementById('meeting_link').value = '';
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>