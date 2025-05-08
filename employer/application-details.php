<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/application_functions.php';

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
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success_message = '';
$error_message = '';

// Process status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    if (updateApplicationStatus($application_id, $new_status, $notes, $user_id)) {
        $success_message = "Application status updated successfully.";
    } else {
        $error_message = "Error updating application status.";
    }
}

// Get application details
$query = "SELECT ja.*, j.title as job_title, j.location as job_location, 
          js.first_name, js.last_name, u.email, js.phone, js.resume_path,
          js.skills, js.experience, js.education
          FROM job_applications ja
          JOIN jobs j ON ja.job_id = j.id
          JOIN jobseeker_profiles js ON ja.user_id = js.user_id
          JOIN users u ON ja.user_id = u.id
          WHERE ja.id = ? AND j.employer_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $application_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Application not found or doesn't belong to this employer
    header("Location: manage-applications.php");
    exit();
}

$application = $result->fetch_assoc();

// Get status history
$history = getApplicationStatusHistory($application_id);

$base_path = '../';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-3">
            <!-- Navigation sidebar -->
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
        
        <div class="col-md-9">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Application Details</h4>
                    <span class="badge bg-<?php echo getStatusBadgeClass($application['status']); ?>"><?php echo ucfirst($application['status']); ?></span>
                </div>
                <div class="card-body">
                    <h5 class="card-title">
                        <?php 
                        // Construct name from first_name and last_name
                        $applicant_name = '';
                        if (isset($application['first_name'])) {
                            $applicant_name .= $application['first_name'];
                        }
                        if (isset($application['last_name'])) {
                            $applicant_name .= ' ' . $application['last_name'];
                        }
                        echo htmlspecialchars(trim($applicant_name) ?: 'Applicant');
                        ?>
                    </h5>
                    <p class="text-muted">
                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($application['job_location']); ?> | 
                        <li class="list-group-item">
                            <strong>Salary:</strong>
                            <?php 
                            if (isset($job['salary']) && $job['salary']) {
                                echo htmlspecialchars($job['salary']);
                            } else {
                                echo '<span class="text-muted">Not specified</span>';
                            }
                            ?>
                        </li>
                    </p>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Applicant Information</h5>
                            <p><strong>Name:</strong> <?php 
    $applicant_name = '';
    if (isset($application['first_name'])) {
        $applicant_name .= $application['first_name'];
    }
    if (isset($application['last_name'])) {
        $applicant_name .= ' ' . $application['last_name'];
    }
    echo htmlspecialchars(trim($applicant_name) ?: 'N/A'); 
?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($application['email'] ?? 'N/A'); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($application['phone'] ?? 'N/A'); ?></p>
                            <?php if (!empty($application['resume_path'])): ?>
                                <p><a href="<?php echo '../' . $application['resume_path']; ?>" target="_blank" class="btn btn-outline-primary">View Resume</a></p>
                            <?php endif; ?>
                            <?php if (!empty($application['user_id'])): ?>
                                <a href="../messages/compose.php?recipient_id=<?php echo $application['user_id']; ?>" class="btn btn-outline-primary mb-3">
                                    <i class="bi bi-chat-dots"></i> Message Applicant
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h5>Application Details</h5>
                            <p><strong>Applied on:</strong> <?php echo date('M d, Y', strtotime($application['apply_date'])); ?></p>
                            <p><strong>Status:</strong> <?php echo ucfirst($application['status']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5>Cover Letter</h5>
                        <div class="border rounded p-3 bg-light">
                            <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5>Update Status</h5>
                        <form method="post" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <select name="status" class="form-select" required>
                                        <option value="" disabled selected>Select new status</option>
                                        <option value="pending">Pending</option>
                                        <option value="shortlisted">Shortlisted</option>
                                        <option value="interviewed">Interviewed</option>
                                        <option value="offered">Offered</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                </div>
                                <div class="col-12">
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Notes about this status change (optional)"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="mt-4">
                        <h5>Status History</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Updated By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $entry): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($entry['timestamp'])); ?></td>
                                        <td><span class="badge bg-<?php echo getStatusBadgeClass($entry['status']); ?>"><?php echo ucfirst($entry['status']); ?></span></td>
                                        <td><?php echo !empty($entry['notes']) ? nl2br(htmlspecialchars($entry['notes'])) : 'No notes'; ?></td>
                                        <td><?php echo htmlspecialchars($entry['changed_by_name']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($history)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No status history found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Add helper function to get appropriate badge color based on status
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'secondary';
        case 'shortlisted':
            return 'primary';
        case 'interviewed':
            return 'info';
        case 'offered':
            return 'success';
        case 'rejected':
            return 'danger';
        case 'withdrawn':
            return 'warning';
        default:
            return 'secondary';
    }
}

include '../includes/footer.php';
?>