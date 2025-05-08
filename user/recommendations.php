<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/ahp_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$base_path = '../';
$active_page = 'recommendations';

// Determine if user has completed their profile
$profile_complete = isJobSeekerProfileComplete($user_id);

// Get recommended jobs using AHP method if profile is complete
$recommended_jobs = [];
if ($profile_complete) {
    try {
        $recommended_jobs = getAHPJobRecommendations($user_id, 20);
    } catch (Exception $e) {
        // Log error and continue with empty recommendations
        error_log("Error getting job recommendations: " . $e->getMessage());
    }
}

// If no AHP recommendations, get basic recommendations
if (empty($recommended_jobs)) {
    $recommended_jobs = getBasicJobRecommendations($user_id, 20);
}

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
                    <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                    <a href="profile.php" class="list-group-item list-group-item-action">My Profile</a>
                    <a href="applications.php" class="list-group-item list-group-item-action">Job Applications</a>
                    <a href="interviews.php" class="list-group-item list-group-item-action">Interviews</a>
                    <a href="saved-jobs.php" class="list-group-item list-group-item-action">Saved Jobs</a>
                    <a href="recommendations.php" class="list-group-item list-group-item-action active">Recommendations</a>
                    <a href="preferences.php" class="list-group-item list-group-item-action">Job Preferences</a>
                    <a href="../messages/index.php" class="list-group-item list-group-item-action">Messages</a>
                    <a href="../notifications/index.php" class="list-group-item list-group-item-action">Notifications</a>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recommended Jobs</h5>
                </div>
                <div class="card-body">
                    <?php if (!$profile_complete): ?>
                        <div class="alert alert-info">
                            <h5><i class="bi bi-info-circle"></i> Complete your profile for better recommendations</h5>
                            <p>We need more information about your skills, experience, and job preferences to provide personalized recommendations.</p>
                            <a href="profile.php" class="btn btn-primary">Complete Profile</a>
                            <a href="preferences.php" class="btn btn-outline-primary">Set Job Preferences</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (count($recommended_jobs) > 0): ?>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php foreach ($recommended_jobs as $job): ?>
                                <div class="col">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="../jobs/view.php?id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($job['title']); ?>
                                                </a>
                                            </h5>
                                            <h6 class="card-subtitle mb-2 text-muted">
                                                <?php echo htmlspecialchars($job['company_name'] ?? 'Unknown Company'); ?>
                                            </h6>
                                            <p class="card-text mb-1">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($job['location']); ?>
                                            </p>
                                            <p class="card-text mb-1">
                                                <i class="bi bi-briefcase"></i> 
                                                <?php echo ucwords(str_replace('_', ' ', $job['job_type'] ?? 'Not specified')); ?>
                                            </p>
                                            <?php if (!empty($job['match_score'])): ?>
                                                <div class="mt-2 mb-3">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small>Match Score:</small>
                                                        <small><?php echo round($job['match_score']); ?>%</small>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-success" role="progressbar" 
                                                             style="width: <?php echo $job['match_score']; ?>%"
                                                             aria-valuenow="<?php echo $job['match_score']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-white border-top-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    Posted <?php echo timeAgo($job['posted_date']); ?>
                                                </small>
                                                <div>
                                                    <a href="../jobs/view.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-search" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">No recommended jobs found</h4>
                            <p>We'll notify you when jobs matching your profile become available.</p>
                            <a href="../jobs/index.php" class="btn btn-primary">Browse All Jobs</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>