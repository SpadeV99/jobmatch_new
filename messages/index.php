<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/messaging_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get conversations
$conversations = getUserConversations($user_id);

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
                    <?php if ($user_type === 'jobseeker'): ?>
                        <a href="../user/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="../user/profile.php" class="list-group-item list-group-item-action">My Profile</a>
                        <a href="../user/applications.php" class="list-group-item list-group-item-action">Job Applications</a>
                        <a href="interviews.php" class="list-group-item list-group-item-action <?php echo $active_page == 'interviews' ? 'active' : ''; ?>">Interviews</a>
                        <a href="saved-jobs.php" class="list-group-item list-group-item-action <?php echo $active_page == 'saved' ? 'active' : ''; ?>">Saved Jobs</a>
                        <a href="recommendations.php" class="list-group-item list-group-item-action <?php echo $active_page == 'recommendations' ? 'active' : ''; ?>">Recommendations</a>
                        <a href="preferences.php" class="list-group-item list-group-item-action <?php echo $active_page == 'preferences' ? 'active' : ''; ?>">Job Preferences</a>
                        <a href="../messages/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'messages' ? 'active' : ''; ?>">Messages</a>
                        <a href="../notifications/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'notifications' ? 'active' : ''; ?>">Notifications</a>
                    <?php else: ?>
                        <a href="../employer/index.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="../employer/post-job.php" class="list-group-item list-group-item-action">Post a Job</a>
                        <a href="../employer/manage-applications.php" class="list-group-item list-group-item-action">Applications</a>
                    <?php endif; ?>
                    <a href="index.php" class="list-group-item list-group-item-action active">Messages</a>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Messages</h4>
                </div>
                <div class="card-body p-0">
                    <?php if (count($conversations) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($conversations as $conversation): ?>
                                <a href="conversation.php?id=<?php echo $conversation['id']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo $conversation['unread_count'] > 0 ? 'bg-light' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <?php echo htmlspecialchars($conversation['other_username']); ?>
                                            <span class="badge <?php echo $conversation['other_user_type'] === 'employer' ? 'bg-primary' : 'bg-success'; ?> ms-2">
                                                <?php echo ucfirst($conversation['other_user_type']); ?>
                                            </span>
                                        </h5>
                                        <small><?php echo timeAgo($conversation['updated_at']); ?></small>
                                    </div>
                                    <p class="mb-1 text-truncate"><?php echo htmlspecialchars($conversation['last_message'] ?? 'No messages yet'); ?></p>
                                    <?php if ($conversation['unread_count'] > 0): ?>
                                        <span class="badge bg-danger"><?php echo $conversation['unread_count']; ?> new</span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <p>You have no conversations yet.</p>
                            <?php if ($user_type === 'jobseeker'): ?>
                                <p>Apply for jobs to connect with employers.</p>
                                <a href="../jobs/index.php" class="btn btn-primary">Browse Jobs</a>
                            <?php else: ?>
                                <p>Engage with applicants to start conversations.</p>
                                <a href="../employer/manage-applications.php" class="btn btn-primary">View Applications</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to display time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . " min" . ($minutes > 1 ? 's' : '') . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? 's' : '') . " ago";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . " day" . ($days > 1 ? 's' : '') . " ago";
    } else {
        return date("M j, Y", $timestamp);
    }
}

include '../includes/footer.php';
?>