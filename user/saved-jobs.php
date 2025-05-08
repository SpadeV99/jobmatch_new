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

// Handle save/unsave job actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_job']) && isset($_POST['job_id'])) {
        $job_id = intval($_POST['job_id']);
        
        // Delete the saved job
        $delete_sql = "DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $user_id, $job_id);
        
        if ($delete_stmt->execute()) {
            $success_message = "Job removed from saved list.";
        } else {
            $error_message = "Error removing job: " . $conn->error;
        }
    }
}

// Get all saved jobs
$saved_jobs_query = "SELECT sj.id as saved_id, sj.date_saved,
                    j.id as job_id, j.title, j.description, j.location, j.salary, j.posted_date,
                    c.name as category_name, e.username as employer_username,
                    ep.company_name
                    FROM saved_jobs sj
                    JOIN jobs j ON sj.job_id = j.id
                    JOIN job_categories c ON j.category_id = c.id
                    LEFT JOIN users e ON j.employer_id = e.id
                    LEFT JOIN employer_profiles ep ON e.id = ep.user_id
                    WHERE sj.user_id = ?
                    ORDER BY sj.date_saved DESC";
$stmt = $conn->prepare($saved_jobs_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$saved_jobs = $stmt->get_result();

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
                <h4 class="mb-0">Saved Jobs</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <?php if ($saved_jobs->num_rows > 0): ?>
                    <div class="row row-cols-1 g-4">
                        <?php while ($job = $saved_jobs->fetch_assoc()): ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <?php echo htmlspecialchars($job['company_name'] ?? $job['employer_username']); ?>
                                        </h6>
                                        
                                        <div class="d-flex mb-2 small text-muted">
                                            <div class="me-3">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($job['location']); ?>
                                            </div>
                                            <div class="me-3">
                                                <i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($job['category_name']); ?>
                                            </div>
                                            <div>
                                                <i class="bi bi-cash"></i> <?php echo htmlspecialchars($job['salary']); ?>
                                            </div>
                                        </div>
                                        
                                        <p class="card-text">
                                            <?php echo substr(htmlspecialchars($job['description']), 0, 150) . '...'; ?>
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <small class="text-muted">Saved on <?php echo date('M d, Y', strtotime($job['date_saved'])); ?></small>
                                            <div>
                                                <a href="../jobs/apply.php?job_id=<?php echo $job['job_id']; ?>" class="btn btn-primary btn-sm">Apply Now</a>
                                                
                                                <form method="post" action="" class="d-inline">
                                                    <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                                    <button type="submit" name="remove_job" class="btn btn-outline-danger btn-sm" 
                                                            onclick="return confirm('Remove this job from your saved list?')">
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>You haven't saved any jobs yet.</p>
                        <a href="../jobs/index.php" class="btn btn-primary mt-2">Browse Jobs</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>