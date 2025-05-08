<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

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
$success_message = '';
$error_message = '';

// Handle application withdrawal
if (isset($_POST['withdraw']) && isset($_POST['application_id'])) {
    $application_id = intval($_POST['application_id']);
    
    // Verify the application belongs to this user
    $verify_sql = "SELECT id FROM job_applications WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $application_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        // Update application status to 'withdrawn'
        $update_sql = "UPDATE job_applications SET status = 'withdrawn' WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $application_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Application withdrawn successfully.";
        } else {
            $error_message = "Error withdrawing application: " . $conn->error;
        }
    } else {
        $error_message = "Invalid application ID.";
    }
}

// Get all user applications
$applications_query = "SELECT ja.id, ja.apply_date, ja.status, ja.cover_letter,
                      j.title as job_title, j.location as job_location, j.salary as job_salary,
                      ep.company_name as company_name
                      FROM job_applications ja
                      JOIN jobs j ON ja.job_id = j.id
                      LEFT JOIN users e ON j.employer_id = e.id
                      LEFT JOIN employer_profiles ep ON e.id = ep.user_id
                      WHERE ja.user_id = ?
                      ORDER BY ja.apply_date DESC";
$stmt = $conn->prepare($applications_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications = $stmt->get_result();

$base_path = '../';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Navigation</h5>
            </div>
            <div class="list-group list-group-flush">
    <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo $active_page == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
    <a href="profile.php" class="list-group-item list-group-item-action <?php echo $active_page == 'profile' ? 'active' : ''; ?>">My Profile</a>
    <a href="applications.php" class="list-group-item list-group-item-action <?php echo $active_page == 'applications' ? 'active' : ''; ?>">Job Applications</a>
    <a href="interviews.php" class="list-group-item list-group-item-action <?php echo $active_page == 'interviews' ? 'active' : ''; ?>">Interviews</a>
    <a href="saved-jobs.php" class="list-group-item list-group-item-action <?php echo $active_page == 'saved' ? 'active' : ''; ?>">Saved Jobs</a>
    <a href="recommendations.php" class="list-group-item list-group-item-action <?php echo $active_page == 'recommendations' ? 'active' : ''; ?>">Recommendations</a>
    <a href="preferences.php" class="list-group-item list-group-item-action <?php echo $active_page == 'preferences' ? 'active' : ''; ?>">Job Preferences</a>
    <a href="../messages/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'messages' ? 'active' : ''; ?>">Messages</a>
    <a href="../notifications/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'notifications' ? 'active' : ''; ?>">Notifications</a>
</div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">My Applications</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <?php if ($applications->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Location</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($app = $applications->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                    <td><?php echo htmlspecialchars($app['company_name'] ?? 'Unknown Company'); ?></td>
                                    <td><?php echo htmlspecialchars($app['job_location']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($app['apply_date'])); ?></td>
                                    <td>
                                        <?php if ($app['status'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif ($app['status'] == 'shortlisted'): ?>
                                            <span class="badge bg-info">Shortlisted</span>
                                        <?php elseif ($app['status'] == 'interviewed'): ?>
                                            <span class="badge bg-primary">Interviewed</span>
                                        <?php elseif ($app['status'] == 'offered'): ?>
                                            <span class="badge bg-success">Offered</span>
                                        <?php elseif ($app['status'] == 'rejected'): ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php elseif ($app['status'] == 'withdrawn'): ?>
                                            <span class="badge bg-secondary">Withdrawn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $app['id']; ?>">
                                            View
                                        </button>
                                        <?php if ($app['status'] != 'withdrawn'): ?>
                                        <form method="post" action="" class="d-inline">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <button type="submit" name="withdraw" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to withdraw this application?')">
                                                Withdraw
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- View Application Modal -->
                                <div class="modal fade" id="viewModal<?php echo $app['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Application Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h6>Job: <?php echo htmlspecialchars($app['job_title']); ?></h6>
                                                <p><strong>Company:</strong> <?php echo htmlspecialchars($app['company_name'] ?? 'Unknown Company'); ?></p>
                                                <p><strong>Location:</strong> <?php echo htmlspecialchars($app['job_location']); ?></p>
                                                <p><strong>Salary:</strong> <?php echo htmlspecialchars($app['job_salary']); ?></p>
                                                <p><strong>Applied On:</strong> <?php echo date('F d, Y', strtotime($app['apply_date'])); ?></p>
                                                <p><strong>Status:</strong> <?php echo ucfirst($app['status']); ?></p>
                                                
                                                <h6 class="mt-4">Cover Letter</h6>
                                                <div class="card">
                                                    <div class="card-body">
                                                        <?php echo nl2br(htmlspecialchars($app['cover_letter'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>You haven't applied to any jobs yet.</p>
                        <a href="../jobs/index.php" class="btn btn-primary mt-2">Browse Jobs</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>