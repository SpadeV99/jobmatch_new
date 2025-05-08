<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/ahp_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as jobseeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if profile and preferences are setup
$sql = "SELECT jp.* FROM jobseeker_profiles jp WHERE jp.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "Please complete your profile first.";
} else {
    $profile = $result->fetch_assoc();
    
    // Check if at least skills are added
    if (empty($profile['skills'])) {
        $error = "Please add some skills to your profile to get better recommendations.";
    }
}

// Get recommended jobs
$recommendations = getAHPJobRecommendations($user_id, 20);

$base_path = '../';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar navigation -->
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
        
        <!-- Main content -->
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Personalized Job Recommendations</h4>
                    <a href="preferences.php" class="btn btn-sm btn-outline-primary">Adjust Preferences</a>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-warning">
                            <?php echo $error; ?>
                            <a href="profile.php" class="btn btn-primary btn-sm ms-3">Complete Profile</a>
                        </div>
                    <?php elseif (empty($recommendations)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-search" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">No matching jobs found</h5>
                            <p>Try updating your skills and preferences to get better recommendations.</p>
                            <div class="mt-3">
                                <a href="preferences.php" class="btn btn-primary">Update Preferences</a>
                                <a href="../jobs/index.php" class="btn btn-outline-secondary ms-2">Browse All Jobs</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <p class="text-muted">
                                These jobs are recommended based on your profile, skills, and preferences.
                                The higher the match percentage, the better the job aligns with your criteria.
                            </p>
                        </div>
                        
                        <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                            <?php foreach ($recommendations as $job): ?>
                                <div class="col">
                                    <div class="card h-100 job-card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span class="badge bg-success match-badge"><?php echo $job['match_score']; ?>% Match</span>
                                            <small class="text-muted"><?php echo time_elapsed_string($job['posted_date'] ?? date('Y-m-d H:i:s')); ?></small>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($job['company'] ?? 'Company'); ?></h6>
                                            <div class="mb-3">
                                                <div><i class="bi bi-geo-alt me-1"></i> <?php echo htmlspecialchars($job['location']); ?></div>
                                                <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                                                    <div><i class="bi bi-cash me-1"></i> $<?php echo number_format($job['salary_min']); ?> - $<?php echo number_format($job['salary_max']); ?></div>
                                                <?php endif; ?>
                                                <div><i class="bi bi-briefcase me-1"></i> <?php echo htmlspecialchars($job['job_type'] ?? 'Not specified'); ?></div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="small text-muted mb-1">Key skills matched:</div>
                                                <div class="skill-tags">
                                                    <?php 
                                                    $skills = explode(',', $job['skills'] ?? '');
                                                    $skills = array_slice($skills, 0, 5); // Show top 5 skills
                                                    foreach ($skills as $skill): 
                                                    ?>
                                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="../jobs/view.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                                <div class="btn-group" role="group">
                                                    <a href="../jobs/save-job.php?job_id=<?php echo $job['id']; ?>" class="btn btn-outline-secondary btn-sm" title="Save Job">
                                                        <i class="bi bi-bookmark"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="../jobs/index.php" class="btn btn-primary">Browse All Jobs</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.match-badge {
    font-size: 0.9rem;
    padding: 0.3rem 0.6rem;
}

.skill-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
}

.job-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid rgba(0,0,0,0.125);
}

.job-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<?php 
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

include '../includes/footer.php'; 
?>