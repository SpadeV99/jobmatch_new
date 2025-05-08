<?php
// filepath: c:\laragon\www\jobmatch_new\admin\job-details.php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$base_path = '../';
$active_page = 'approve-jobs';

// Check if job ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: approve-jobs.php");
    exit();
}

$job_id = intval($_GET['id']);

// Get job details
$stmt = $conn->prepare("SELECT j.*, ep.company_name, 
                       u.email as employer_email
                       FROM jobs j 
                       LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                       LEFT JOIN users u ON j.employer_id = u.id
                       WHERE j.id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: approve-jobs.php");
    exit();
}

$job = $result->fetch_assoc();

// Handle job actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'approve':
            approveJob($job_id);
            $_SESSION['admin_message'] = "Job listing approved successfully.";
            header("Location: job-details.php?id=$job_id&success=approved");
            exit();
            break;
            
        case 'reject':
            $reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';
            rejectJob($job_id, $reason);
            $_SESSION['admin_message'] = "Job listing rejected successfully.";
            header("Location: job-details.php?id=$job_id&success=rejected");
            exit();
            break;
            
        case 'feature':
            featureJob($job_id);
            $_SESSION['admin_message'] = "Job has been featured successfully.";
            header("Location: job-details.php?id=$job_id&success=featured");
            exit();
            break;
            
        case 'unfeature':
            unfeatureJob($job_id);
            $_SESSION['admin_message'] = "Job has been unfeatured successfully.";
            header("Location: job-details.php?id=$job_id&success=unfeatured");
            exit();
            break;
            
        case 'delete':
            deleteJob($job_id);
            $_SESSION['admin_message'] = "Job listing deleted successfully.";
            header("Location: approve-jobs.php");
            exit();
            break;
    }
}

include '../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="approve-jobs.php">Manage Jobs</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Job Details</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php if ($_GET['success'] === 'approved'): ?>
                Job has been approved and is now visible to job seekers.
            <?php elseif ($_GET['success'] === 'rejected'): ?>
                Job has been rejected. The employer has been notified.
            <?php elseif ($_GET['success'] === 'featured'): ?>
                Job has been featured and will appear at the top of job listings.
            <?php elseif ($_GET['success'] === 'unfeatured'): ?>
                Job has been removed from featured listings.
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
                echo $_SESSION['admin_message'];
                unset($_SESSION['admin_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Job Details</h5>
                    <div>
                        <a href="edit-job.php?id=<?php echo $job_id; ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="../jobs/view.php?id=<?php echo $job_id; ?>" class="btn btn-secondary btn-sm" target="_blank">
                            <i class="bi bi-eye"></i> Public View
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                    <p class="text-muted">
                        <i class="bi bi-building"></i> <?php echo htmlspecialchars($job['company_name'] ?? 'Unknown Company'); ?> |
                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($job['location']); ?> |
                        <i class="bi bi-clock"></i> <?php echo ucfirst(str_replace('_', ' ', $job['job_type'] ?? 'Unknown')); ?>
                    </p>
                    
                    <div class="mb-4">
                        <span class="badge bg-<?php 
                            if (!isset($job['status']) || $job['status'] == 'pending') echo 'warning';
                            elseif ($job['status'] == 'active') echo 'success';
                            elseif ($job['status'] == 'rejected') echo 'danger';
                            elseif ($job['status'] == 'expired') echo 'secondary';
                            elseif ($job['status'] == 'filled') echo 'info';
                            else echo 'secondary';
                        ?>">
                            <?php echo ucfirst($job['status'] ?? 'pending'); ?>
                        </span>
                        
                        <?php if (!empty($job['is_featured']) && $job['is_featured'] == 1): ?>
                            <span class="badge bg-primary">Featured</span>
                        <?php endif; ?>
                        
                        <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                            <span class="badge bg-light text-dark">
                                <?php 
                                if (!empty($job['salary_min']) && !empty($job['salary_max'])) {
                                    echo '₱' . number_format($job['salary_min']) . ' - ₱' . number_format($job['salary_max']);
                                } elseif (!empty($job['salary_min'])) {
                                    echo 'From ₱' . number_format($job['salary_min']);
                                } elseif (!empty($job['salary_max'])) {
                                    echo 'Up to ₱' . number_format($job['salary_max']);
                                }
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Job Description</h6>
                        <div class="card-text">
                            <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($job['responsibilities'])): ?>
                    <div class="mb-4">
                        <h6>Responsibilities</h6>
                        <div class="card-text">
                            <?php echo nl2br(htmlspecialchars($job['responsibilities'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($job['requirements'])): ?>
                    <div class="mb-4">
                        <h6>Requirements</h6>
                        <div class="card-text">
                            <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <h6>Posted Date</h6>
                        <p><?php echo date('F j, Y', strtotime($job['posted_date'])); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Job Applications -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Applications</h5>
                </div>
                <div class="card-body">
                    <?php
                    $app_stmt = $conn->prepare("SELECT ja.*, u.username, u.email, 
                                              CONCAT(jp.first_name, ' ', jp.last_name) as applicant_name
                                              FROM job_applications ja
                                              LEFT JOIN users u ON ja.user_id = u.id
                                              LEFT JOIN jobseeker_profiles jp ON ja.user_id = jp.user_id
                                              WHERE ja.job_id = ?
                                              ORDER BY ja.id DESC");
                    $app_stmt->bind_param("i", $job_id);
                    $app_stmt->execute();
                    $applications = $app_stmt->get_result();
                    ?>
                    
                    <?php if ($applications->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Applicant</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Applied On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($app = $applications->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $app['id']; ?></td>
                                            <td>
                                                <a href="user-details.php?id=<?php echo $app['user_id']; ?>">
                                                    <?php echo htmlspecialchars($app['applicant_name'] ?? $app['username']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($app['email']); ?></td>
                                            <td>
                                                <?php if (!isset($app['status']) || $app['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php elseif ($app['status'] === 'reviewed'): ?>
                                                    <span class="badge bg-info">Reviewed</span>
                                                <?php elseif ($app['status'] === 'interview'): ?>
                                                    <span class="badge bg-primary">Interview</span>
                                                <?php elseif ($app['status'] === 'hired'): ?>
                                                    <span class="badge bg-success">Hired</span>
                                                <?php elseif ($app['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($app['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($app['created_at'] ?? $app['application_date'])); ?>
                                            </td>
                                            <td>
                                                <a href="application-details.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No applications have been submitted for this job yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Actions Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <?php if (!isset($job['status']) || $job['status'] == 'pending'): ?>
                        <form method="post" class="mb-2">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle"></i> Approve Job
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle"></i> Reject Job
                        </button>
                    <?php endif; ?>
                    
                    <?php if (isset($job['status']) && $job['status'] == 'active'): ?>
                        <form method="post" class="mb-2">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-x-circle"></i> Unpublish Job
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if (isset($job['status']) && $job['status'] == 'rejected'): ?>
                        <form method="post" class="mb-2">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle"></i> Approve Job
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if (empty($job['is_featured']) || $job['is_featured'] == 0): ?>
                        <form method="post" class="mb-2">
                            <input type="hidden" name="action" value="feature">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-star"></i> Feature Job
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post" class="mb-2">
                            <input type="hidden" name="action" value="unfeature">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-star"></i> Remove Featured Status
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="bi bi-trash"></i> Delete Job
                    </button>
                </div>
            </div>
            
            <!-- Employer Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Employer Information</h5>
                </div>
                <div class="card-body">
                    <h6><?php echo htmlspecialchars($job['company_name'] ?? 'Unknown Company'); ?></h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Employer ID:</span>
                            <a href="user-details.php?id=<?php echo $job['employer_id']; ?>">
                                #<?php echo $job['employer_id']; ?>
                            </a>
                        </li>
                        <?php if (!empty($job['employer_email'])): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Email:</span>
                            <span><?php echo htmlspecialchars($job['employer_email']); ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($job['company_location'])): ?>
                            <li class="list-group-item d-flex justify-content-between">
    <span>Location:</span>
    <span><?php echo htmlspecialchars($job['location']); ?></span>
</li>
                        <?php endif; ?>
                        
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="reject">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Job Listing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for rejection (optional):</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" placeholder="Enter reason for rejection"></textarea>
                        <small class="text-muted">This will be sent to the employer.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Job</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Job Listing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to permanently delete this job listing? This action cannot be undone.</p>
                    <p class="text-danger"><strong>Warning:</strong> This will also delete all associated applications.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Job</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>