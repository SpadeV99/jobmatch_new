<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/notification_functions.php';

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
$active_page = 'notifications';

// Handle actions
$success_message = '';
$error_message = '';

// Handle mark as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = intval($_POST['notification_id']);
    if (markNotificationAsRead($notification_id, $user_id)) {
        $success_message = "Notification marked as read.";
    } else {
        $error_message = "Failed to mark notification as read.";
    }
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    if (markAllNotificationsAsRead($user_id)) {
        $success_message = "All notifications marked as read.";
    } else {
        $error_message = "Failed to mark all notifications as read.";
    }
}

// Handle delete notification
if (isset($_POST['delete']) && isset($_POST['notification_id'])) {
    $notification_id = intval($_POST['notification_id']);
    if (deleteNotification($notification_id, $user_id)) {
        $success_message = "Notification deleted.";
    } else {
        $error_message = "Failed to delete notification.";
    }
}

// Handle delete all notifications
if (isset($_POST['delete_all'])) {
    if (deleteAllNotifications($user_id)) {
        $success_message = "All notifications deleted.";
    } else {
        $error_message = "Failed to delete all notifications.";
    }
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter
$showUnreadOnly = isset($_GET['unread']) && $_GET['unread'] == '1';

// Get notifications
$notifications = getUserNotifications($user_id, $limit, $offset, $showUnreadOnly);

// Get total count for pagination
$total_notifications = getTotalNotificationsCount($user_id, $showUnreadOnly);
$total_pages = ceil($total_notifications / $limit);

// Get unread count
$unread_count = getUnreadNotificationCount($user_id);

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
                        <a href="../user/interviews.php" class="list-group-item list-group-item-action">Interviews</a>
                        <a href="../user/saved-jobs.php" class="list-group-item list-group-item-action">Saved Jobs</a>
                    <?php else: ?>
                        <a href="../employer/index.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="../employer/company-profile.php" class="list-group-item list-group-item-action">Company Profile</a>
                        <a href="../employer/post-job.php" class="list-group-item list-group-item-action">Post a Job</a>
                        <a href="../employer/manage-jobs.php" class="list-group-item list-group-item-action">Manage Jobs</a>
                        <a href="../employer/manage-applications.php" class="list-group-item list-group-item-action">Applications</a>
                        <a href="../employer/interviews.php" class="list-group-item list-group-item-action">Interviews</a>
                    <?php endif; ?>
                    <a href="index.php" class="list-group-item list-group-item-action active">Notifications</a>
                    <a href="../messages/index.php" class="list-group-item list-group-item-action">Messages</a>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        Notifications
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-light text-primary ms-2"><?php echo $unread_count; ?> new</span>
                        <?php endif; ?>
                    </h4>
                    <div>
                        <?php if ($total_notifications > 0): ?>
                            <form method="post" action="" class="d-inline">
                                <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-light">
                                    <i class="bi bi-check-all"></i> Mark All as Read
                                </button>
                            </form>
                            <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete all notifications?');">
                                <button type="submit" name="delete_all" class="btn btn-sm btn-outline-light">
                                    <i class="bi bi-trash"></i> Clear All
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <a href="?<?php echo $showUnreadOnly ? '' : 'unread=1'; ?>" class="btn btn-sm <?php echo $showUnreadOnly ? 'btn-outline-primary' : 'btn-primary'; ?>">
                                <?php echo $showUnreadOnly ? 'Show All' : 'Show Unread Only'; ?>
                            </a>
                        </div>
                    </div>
                    
                    <?php if (empty($notifications)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-bell"></i> 
                            <?php echo $showUnreadOnly ? 'No unread notifications.' : 'No notifications to display.'; ?>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'bg-light'; ?> d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge bg-primary rounded-pill float-end">New</span>
                                        <?php endif; ?>
                                        <div class="fw-bold"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?php echo time_elapsed_string($notification['created_at']); ?>
                                        </small>
                                        
                                        <?php if (!empty($notification['related_id']) && !empty($notification['related_type'])): ?>
                                            <div class="mt-2">
                                                <?php
                                                $link = '#';
                                                $btn_text = 'View';
                                                
                                                switch ($notification['related_type']) {
                                                    case 'job':
                                                        $link = "../jobs/view.php?id=" . $notification['related_id'];
                                                        $btn_text = 'View Job';
                                                        break;
                                                    case 'application':
                                                        if ($user_type === 'jobseeker') {
                                                            $link = "../user/applications.php";
                                                        } else {
                                                            $link = "../employer/application-details.php?id=" . $notification['related_id'];
                                                        }
                                                        $btn_text = 'View Application';
                                                        break;
                                                    case 'interview':
                                                        $link = "../interviews/view.php?id=" . $notification['related_id'];
                                                        $btn_text = 'View Interview';
                                                        break;
                                                    case 'message':
                                                        $link = "../messages/conversation.php?id=" . $notification['related_id'];
                                                        $btn_text = 'View Message';
                                                        break;
                                                    case 'assessment':
                                                        if ($user_type === 'jobseeker') {
                                                            $link = "../assessments/take.php?id=" . $notification['related_id'];
                                                            $btn_text = 'Take Assessment';
                                                        } else {
                                                            $link = "../employer/edit-assessment.php?id=" . $notification['related_id'];
                                                            $btn_text = 'View Assessment';
                                                        }
                                                        break;
                                                }
                                                ?>
                                                <a href="<?php echo $link; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-arrow-right"></i> <?php echo $btn_text; ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="post" action="" class="me-1">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" name="mark_read" class="btn btn-sm btn-outline-primary" title="Mark as Read">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="" onsubmit="return confirm('Delete this notification?');">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Notification pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $showUnreadOnly ? '&unread=1' : ''; ?>" tabindex="-1">Previous</a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $showUnreadOnly ? '&unread=1' : ''; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $showUnreadOnly ? '&unread=1' : ''; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tips Card -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> About Notifications</h5>
                </div>
                <div class="card-body">
                    <p>The notification system keeps you informed about important activities:</p>
                    <ul>
                        <li><strong>Job Applications:</strong> Updates about application status changes</li>
                        <li><strong>Interviews:</strong> New interview schedules, reschedule requests, and reminders</li>
                        <li><strong>Messages:</strong> New message notifications</li>
                        <li><strong>Assessments:</strong> Information about assessments assigned to you</li>
                        <li><strong>Job Matches:</strong> Updates about new jobs matching your profile</li>
                    </ul>
                    <p class="mb-0">Manage your notification preferences and stay updated on your job search or recruitment activities.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Add this function to notification_functions.php if it doesn't exist
if (!function_exists('time_elapsed_string')) {
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
}

// Make sure these functions exist in notification_functions.php
if (!function_exists('getTotalNotificationsCount')) {
    function getTotalNotificationsCount($user_id, $unread_only = false) {
        global $conn;
        
        $user_id = intval($user_id);
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ?";
        
        if ($unread_only) {
            $sql .= " AND is_read = 0";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'];
    }
}

include '../includes/footer.php';
?>