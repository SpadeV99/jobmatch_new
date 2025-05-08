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
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p class="lead">Manage your job search from your personalized dashboard.</p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>My Applications</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">You haven't applied to any jobs yet.</p>
                        <a href="../jobs/index.php" class="btn btn-primary">Browse Jobs</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Recommended Jobs</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Complete your profile to get job recommendations.</p>
                        <a href="profile.php" class="btn btn-primary">Update Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>