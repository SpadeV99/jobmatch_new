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
    header("Location: ../login.php?redirect=employer/manage-applications.php");
    exit();
}

$employer_id = $_SESSION['user_id'];
$active_page = 'applications';
$base_path = '../';

// Handle application status changes
if (isset($_POST['action']) && isset($_POST['application_id'])) {
    $application_id = intval($_POST['application_id']);
    $action = $_POST['action'];
    $note = isset($_POST['note']) ? $_POST['note'] : '';
    
    // Verify the application is for a job owned by this employer
    $stmt = $conn->prepare("SELECT a.id FROM job_applications a 
                           JOIN jobs j ON a.job_id = j.id 
                           WHERE a.id = ? AND j.employer_id = ?");
    $stmt->bind_param("ii", $application_id, $employer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        switch ($action) {
            case 'shortlist':
                updateApplicationStatus($application_id, 'shortlisted', $note, $employer_id);
                $success_message = "Application shortlisted successfully.";
                break;
            case 'reject':
                updateApplicationStatus($application_id, 'rejected', $note, $employer_id);
                $success_message = "Application rejected successfully.";
                break;
            case 'interview':
                // Just mark for interview here, scheduling is done on another page
                updateApplicationStatus($application_id, 'interview', $note, $employer_id);
                $success_message = "Application marked for interview. <a href='schedule-interview.php?application_id=$application_id'>Schedule now</a>";
                break;
            case 'offer':
                updateApplicationStatus($application_id, 'offer', $note, $employer_id);
                $success_message = "Offer extended to applicant.";
                break;
            case 'hire':
                updateApplicationStatus($application_id, 'hired', $note, $employer_id);
                $success_message = "Applicant marked as hired.";
                break;
        }
    }
}

// Filter applications by job if specified
$job_filter = "";
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

if ($job_id > 0) {
    $job_filter = "AND j.id = $job_id";
    
    // Get job details
    $stmt = $conn->prepare("SELECT title FROM jobs WHERE id = ? AND employer_id = ?");
    $stmt->bind_param("ii", $job_id, $employer_id);
    $stmt->execute();
    $job_result = $stmt->get_result();
    if ($job_result->num_rows > 0) {
        $job_info = $job_result->fetch_assoc();
    }
}

// Filter by status if specified
$status_filter = "";
$status = isset($_GET['status']) ? $_GET['status'] : '';

if (!empty($status)) {
    $status_filter = "AND a.status = '$status'";
}

// Get employer's applications
$sql = "SELECT a.*, a.apply_date as applied_date, j.title as job_title, 
        j.location as job_location,
        u.username, u.email, /* Email from users table */
        jp.first_name, jp.last_name, jp.phone, jp.resume_path
        FROM job_applications a 
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.user_id = u.id
        JOIN jobseeker_profiles jp ON a.user_id = jp.user_id
        WHERE j.employer_id = ? $job_filter $status_filter
        ORDER BY a.apply_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();

$applications = [];
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}

// Get application counts by status
$sql = "SELECT a.status, COUNT(*) as count
        FROM job_applications a 
        JOIN jobs j ON a.job_id = j.id
        WHERE j.employer_id = ? $job_filter
        GROUP BY a.status";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$status_result = $stmt->get_result();

$status_counts = [
    'all' => 0,
    'new' => 0,
    'shortlisted' => 0,
    'rejected' => 0,
    'interview' => 0,
    'offer' => 0,
    'hired' => 0
];

while ($row = $status_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
    $status_counts['all'] += $row['count'];
}

// Get all jobs for filter dropdown
$sql = "SELECT id, title FROM jobs WHERE employer_id = ? ORDER BY posted_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$jobs_result = $stmt->get_result();

$jobs = [];
while ($row = $jobs_result->fetch_assoc()) {
    $jobs[] = $row;
}

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
                    <a href="index.php" class="list-group-item list-group-item-action">Dashboard</a>
                    <a href="company-profile.php" class="list-group-item list-group-item-action">Company Profile</a>
                    <a href="post-job.php" class="list-group-item list-group-item-action">Post a Job</a>
                    <a href="manage-jobs.php" class="list-group-item list-group-item-action">Manage Jobs</a>
                    <a href="manage-applications.php" class="list-group-item list-group-item-action active">Applications</a>
                    <a href="interviews.php" class="list-group-item list-group-item-action">Interviews</a>
                    <a href="../notifications/index.php" class="list-group-item list-group-item-action">Notifications</a>
                    <a href="../messages/index.php" class="list-group-item list-group-item-action">Messages</a>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <?php if (isset($job_info)): ?>
                            Applications for "<?php echo htmlspecialchars($job_info['title']); ?>"
                        <?php else: ?>
                            Manage Applications
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form method="get" class="d-flex">
                                <select name="job_id" class="form-select me-2">
                                    <option value="">All Jobs</option>
                                    <?php foreach ($jobs as $job): ?>
                                        <option value="<?php echo $job['id']; ?>" <?php echo $job_id == $job['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Status tabs -->
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link <?php echo empty($status) ? 'active' : ''; ?>" href="?job_id=<?php echo $job_id; ?>">
                                All <span class="badge bg-secondary rounded-pill"><?php echo $status_counts['all']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status === 'new' ? 'active' : ''; ?>" href="?job_id=<?php echo $job_id; ?>&status=new">
                                New <span class="badge bg-primary rounded-pill"><?php echo $status_counts['new']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status === 'shortlisted' ? 'active' : ''; ?>" href="?job_id=<?php echo $job_id; ?>&status=shortlisted">
                                Shortlisted <span class="badge bg-info rounded-pill"><?php echo $status_counts['shortlisted']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status === 'interview' ? 'active' : ''; ?>" href="?job_id=<?php echo $job_id; ?>&status=interview">
                                Interview <span class="badge bg-warning rounded-pill"><?php echo $status_counts['interview']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status === 'offer' ? 'active' : ''; ?>" href="?job_id=<?php echo $job_id; ?>&status=offer">
                                Offer <span class="badge bg-success rounded-pill"><?php echo $status_counts['offer']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status === 'hired' ? 'active' : ''; ?>" href="?job_id=<?php echo $job_id; ?>&status=hired">
                                Hired <span class="badge bg-success rounded-pill"><?php echo $status_counts['hired']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status === 'rejected' ? 'active' : ''; ?>" href="?job_id=<?php echo $job_id; ?>&status=rejected">
                                Rejected <span class="badge bg-danger rounded-pill"><?php echo $status_counts['rejected']; ?></span>
                            </a>
                        </li>
                    </ul>
                    
                    <?php if (empty($applications)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No applications found with the selected filters.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Job</th>
                                        <th>Applied</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <strong>
                                                            <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                                        </strong>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($app['email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($app['job_title']); ?>
                                                <div class="small text-muted">
                                                    <?php echo htmlspecialchars($app['job_location']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($app['applied_date'])); ?>
                                                <div class="small text-muted">
                                                    <?php 
                                                    $daysAgo = floor((time() - strtotime($app['applied_date'])) / (60 * 60 * 24));
                                                    echo $daysAgo == 0 ? 'Today' : $daysAgo . ' days ago'; 
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                               $status_badge = 'secondary';
                                               switch ($app['status']) {
                                                   case 'pending': $status_badge = 'primary'; break;
                                                   case 'shortlisted': $status_badge = 'info'; break;
                                                   case 'rejected': $status_badge = 'danger'; break;
                                                   case 'interview': $status_badge = 'warning'; break; // Changed from "interviewed" to "interview"
                                                   case 'interviewed': $status_badge = 'warning'; break; // Keep this for backward compatibility
                                                   case 'offer': $status_badge = 'success'; break; // Changed from "offered" to "offer"
                                                   case 'offered': $status_badge = 'success'; break; // Keep for backward compatibility
                                                   case 'hired': $status_badge = 'success'; break;
                                                   case 'withdrawn': $status_badge = 'secondary'; break;
                                                   default: $status_badge = 'secondary';
                                               }
                                                ?>
                                                <span class="badge bg-<?php echo $status_badge; ?>">
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="application-details.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="bi bi-gear"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <form method="post">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                                <input type="hidden" name="action" value="shortlist">
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="bi bi-star"></i> Shortlist
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="post">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                                <input type="hidden" name="action" value="interview">
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="bi bi-calendar"></i> Mark for Interview
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="schedule-interview.php?application_id=<?php echo $app['id']; ?>">
                                                                <i class="bi bi-calendar-plus"></i> Schedule Interview
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="post">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                                <input type="hidden" name="action" value="offer">
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="bi bi-envelope"></i> Extend Offer
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form method="post">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                                <input type="hidden" name="action" value="hire">
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="bi bi-check-circle"></i> Mark as Hired
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="post" onsubmit="return confirm('Are you sure you want to reject this application?');">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                                <input type="hidden" name="action" value="reject">
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="bi bi-x-circle"></i> Reject Application
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Application Management Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><strong>Respond quickly</strong> to new applications to keep candidates engaged</li>
                        <li>Use <strong>status updates</strong> to track your hiring pipeline</li>
                        <li>Download resumes and cover letters for <strong>offline review</strong></li>
                        <li><strong>Schedule interviews</strong> directly from the application view</li>
                        <li>Add <strong>notes and feedback</strong> to applications for team collaboration</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>