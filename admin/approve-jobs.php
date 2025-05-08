<?php
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

// Handle job actions
if (isset($_POST['action']) && isset($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);
    
    switch ($_POST['action']) {
        case 'approve':
            approveJob($job_id);
            $_SESSION['admin_message'] = "Job listing approved successfully.";
            break;
            
        case 'reject':
            $reason = isset($_POST['rejection_reason']) ? $_POST['rejection_reason'] : '';
            rejectJob($job_id, $reason);
            $_SESSION['admin_message'] = "Job listing rejected.";
            break;
            
        case 'feature':
            featureJob($job_id);
            $_SESSION['admin_message'] = "Job listing marked as featured.";
            break;
            
        case 'unfeature':
            unfeatureJob($job_id);
            $_SESSION['admin_message'] = "Job listing removed from featured listings.";
            break;
    }
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtering
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Get jobs with filtering and pagination
$jobs = getJobsForAdmin($filter_status, $search_term, $limit, $offset);
$total_jobs = countJobsForAdmin($filter_status, $search_term);
$total_pages = ceil($total_jobs / $limit);

include '../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <h2>Manage Job Listings</h2>
        </div>
    </div>
    
    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
                echo $_SESSION['admin_message'];
                unset($_SESSION['admin_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="active" <?php echo ($filter_status == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="rejected" <?php echo ($filter_status == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        <option value="expired" <?php echo ($filter_status == 'expired') ? 'selected' : ''; ?>>Expired</option>
                        <option value="filled" <?php echo ($filter_status == 'filled') ? 'selected' : ''; ?>>Filled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search by title, company, or location" value="<?php echo htmlspecialchars($search_term); ?>">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                    </div>
                </div>
                <div class="col-md-2">
                    <a href="approve-jobs.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><?php echo $job['id']; ?></td>
                                <td><a href="job-details.php?id=<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a></td>
                                <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                <td>
                                    <?php if ($job['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($job['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php elseif ($job['status'] == 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php elseif ($job['status'] == 'expired'): ?>
                                        <span class="badge bg-secondary">Expired</span>
                                    <?php elseif ($job['status'] == 'filled'): ?>
                                        <span class="badge bg-info">Filled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($job['is_featured']) && $job['is_featured']): ?>
                                        <span class="badge bg-success">Featured</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary">View</a>
                                        
                                        <?php if ($job['status'] == 'pending'): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-outline-success" onclick="return confirm('Are you sure you want to approve this job listing?')">
                                                    Approve
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $job['id']; ?>">
                                                Reject
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php if (!isset($job['is_featured']) || !$job['is_featured']): ?>
                                                    <li>
                                                        <form method="post">
                                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                            <input type="hidden" name="action" value="feature">
                                                            <button type="submit" class="dropdown-item">
                                                                Mark as Featured
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php else: ?>
                                                    <li>
                                                        <form method="post">
                                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                            <input type="hidden" name="action" value="unfeature">
                                                            <button type="submit" class="dropdown-item">
                                                                Remove from Featured
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="dropdown-item">
                                                        Edit Job
                                                    </a>
                                                </li>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal<?php echo $job['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Job Listing</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <div class="mb-3">
                                                            <label for="rejection_reason" class="form-label">Reason for rejection:</label>
                                                            <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="3" required></textarea>
                                                            <div class="form-text">This will be sent to the employer.</div>
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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($jobs) == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center">No job listings found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_term); ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_term); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_term); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>