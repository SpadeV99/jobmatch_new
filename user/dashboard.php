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
$active_page = 'dashboard';
$base_path = '../';

// Get user's recent job applications
$sql = "SELECT a.*, j.title as job_title, j.location, e.company_name
        FROM job_applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN employer_profiles e ON j.employer_id = e.user_id
        WHERE a.user_id = ?
        ORDER BY a.apply_date DESC
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications_result = $stmt->get_result();

// Get recent job recommendations
$sql = "SELECT j.*, e.company_name 
        FROM jobs j
        JOIN employer_profiles e ON j.employer_id = e.user_id
        LEFT JOIN job_applications a ON j.id = a.job_id AND a.user_id = ?
        WHERE a.id IS NULL
        ORDER BY j.posted_date DESC
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recommended_jobs = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Navigation</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
                    <a href="profile.php" class="list-group-item list-group-item-action">My Profile</a>
                    <a href="applications.php" class="list-group-item list-group-item-action">Job Applications</a>
                    <a href="interviews.php" class="list-group-item list-group-item-action">Interviews</a>
                    <a href="saved-jobs.php" class="list-group-item list-group-item-action">Saved Jobs</a>
                    <a href="recommendations.php" class="list-group-item list-group-item-action">Recommendations</a>
                    <a href="preferences.php" class="list-group-item list-group-item-action">Job Preferences</a>
                    <a href="../messages/index.php" class="list-group-item list-group-item-action">Messages</a>
                    <a href="../notifications/index.php" class="list-group-item list-group-item-action">Notifications</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p class="lead">Manage your job search from your personalized dashboard.</p>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>My Applications</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($applications_result->num_rows > 0): ?>
                                <ul class="list-group">
                                    <?php while ($app = $applications_result->fetch_assoc()): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold"><?php echo htmlspecialchars($app['job_title']); ?></div>
                                                <?php echo htmlspecialchars($app['company_name']); ?>
                                                <span class="d-block small text-muted">
                                                    Applied: <?php echo date('M d, Y', strtotime($app['apply_date'])); ?>
                                                </span>
                                            </div>
                                            <span class="badge bg-<?php 
                                                switch($app['status']) {
                                                    case 'pending': echo 'primary'; break;
                                                    case 'shortlisted': echo 'info'; break;
                                                    case 'interviewed': echo 'warning'; break;
                                                    case 'offered': echo 'success'; break;
                                                    case 'rejected': echo 'danger'; break;
                                                    default: echo 'secondary';
                                                }
                                            ?> rounded-pill">
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                                <div class="mt-3">
                                    <a href="applications.php" class="btn btn-outline-primary btn-sm">View All</a>
                                </div>
                            <?php else: ?>
                                <p class="card-text">You haven't applied to any jobs yet.</p>
                                <a href="../jobs/index.php" class="btn btn-primary">Browse Jobs</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Recommended Jobs</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($recommended_jobs->num_rows > 0): ?>
                                <ul class="list-group">
                                    <?php while ($job = $recommended_jobs->fetch_assoc()): ?>
                                        <li class="list-group-item">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h6>
                                            <p class="mb-1 small"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted"><?php echo htmlspecialchars($job['location']); ?></small>
                                                <a href="../jobs/apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">Apply</a>
                                            </div>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                                <div class="mt-3">
                                    <a href="recommendations.php" class="btn btn-outline-primary btn-sm">View More</a>
                                </div>
                            <?php else: ?>
                                <p class="card-text">Complete your profile to get job recommendations.</p>
                                <a href="profile.php" class="btn btn-primary">Update Profile</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>