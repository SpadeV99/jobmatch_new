<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header("Location: ../login.php?redirect=employer/manage-jobs.php");
    exit();
}

$employer_id = $_SESSION['user_id'];
$active_page = 'manage_jobs';
$base_path = '../';

// Handle job status changes
if (isset($_POST['action']) && isset($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);
    
    // Verify the job belongs to this employer
    $stmt = $conn->prepare("SELECT id FROM jobs WHERE id = ? AND employer_id = ?");
    $stmt->bind_param("ii", $job_id, $employer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        if ($_POST['action'] === 'activate') {
            $conn->query("UPDATE jobs SET active = 1 WHERE id = $job_id");
            $success_message = "Job activated successfully.";
        } elseif ($_POST['action'] === 'deactivate') {
            $conn->query("UPDATE jobs SET active = 0 WHERE id = $job_id");
            $success_message = "Job deactivated successfully.";
        } elseif ($_POST['action'] === 'delete') {
            // Check if there are any applications for this job
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_applications WHERE job_id = ?");
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
            $count_result = $stmt->get_result()->fetch_assoc();
            
            if ($count_result['count'] > 0) {
                $error_message = "Cannot delete job with existing applications. Deactivate it instead.";
            } else {
                $conn->query("DELETE FROM jobs WHERE id = $job_id");
                $success_message = "Job deleted successfully.";
            }
        }
    }
}

// Get employer's jobs
$sql = "SELECT j.*, 
       (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
       FROM jobs j 
       WHERE j.employer_id = ? 
       ORDER BY j.posted_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
while ($row = $result->fetch_assoc()) {
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
                    <a href="manage-jobs.php" class="list-group-item list-group-item-action active">Manage Jobs</a>
                    <a href="manage-applications.php" class="list-group-item list-group-item-action">Applications</a>
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
                    <h4 class="mb-0">Manage Job Postings</h4>
                    <a href="post-job.php" class="btn btn-light btn-sm">
                        <i class="bi bi-plus-circle"></i> Post New Job
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($jobs)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> You haven't posted any jobs yet. 
                            <a href="post-job.php" class="alert-link">Post your first job</a> to start receiving applications.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Posted</th>
                                        <th>Status</th>
                                        <th>Applications</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                                <div class="small text-muted">
                                                    <?php echo htmlspecialchars($job['location']); ?> | 
                                                    <?php echo htmlspecialchars($job['job_type']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                                                <div class="small text-muted">
                                                    <?php 
                                                    $daysAgo = floor((time() - strtotime($job['posted_date'])) / (60 * 60 * 24));
                                                    echo $daysAgo == 0 ? 'Today' : $daysAgo . ' days ago'; 
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($job['active']) && $job['active'] == 1): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="manage-applications.php?job_id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                                    <span class="badge bg-primary rounded-pill"><?php echo $job['application_count']; ?></span>
                                                    <?php echo $job['application_count'] == 1 ? 'Application' : 'Applications'; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="../jobs/view.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    
                                                    <?php if (!empty($job['active']) && $job['active'] == 1): ?>
                                                        <form method="post" style="display: inline;">
                                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                            <input type="hidden" name="action" value="deactivate">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Deactivate">
                                                                <i class="bi bi-pause-circle"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="post" style="display: inline;">
                                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                            <input type="hidden" name="action" value="activate">
                                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Activate">
                                                                <i class="bi bi-play-circle"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($job['application_count'] == 0): ?>
                                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this job posting? This cannot be undone.');">
                                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
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
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Job Posting Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><strong>Detailed job descriptions</strong> attract more qualified candidates</li>
                        <li><strong>Clear requirements</strong> help candidates self-select appropriately</li>
                        <li>Using <strong>relevant skills and keywords</strong> improves visibility in search results</li>
                        <li>You can <strong>deactivate jobs</strong> temporarily instead of deleting them</li>
                        <li>Regularly <strong>review and update</strong> your job postings for better results</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>