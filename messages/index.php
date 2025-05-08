<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

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
$base_path = '../';
$active_page = 'messages';

// Get all conversations for the current user
$sql = "SELECT c.*, 
        CASE 
            WHEN c.user1_id = ? THEN c.user2_id
            ELSE c.user1_id
        END AS other_user_id,
        ANY_VALUE(u.username) as other_username,
        ANY_VALUE(u.user_type) as other_user_type,
        ANY_VALUE(m.message) as last_message,
        ANY_VALUE(m.created_at) as last_message_time,
        COUNT(CASE WHEN m.sender_id != ? AND m.is_read = 0 THEN 1 END) as unread_count
        FROM conversations c
        LEFT JOIN messages m ON m.id = (
            SELECT MAX(id) FROM messages WHERE conversation_id = c.id
        )
        JOIN users u ON u.id = CASE 
            WHEN c.user1_id = ? THEN c.user2_id
            ELSE c.user1_id
        END
        WHERE c.user1_id = ? OR c.user2_id = ?
        GROUP BY c.id
        ORDER BY ANY_VALUE(m.created_at) DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result();

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
                    <?php if ($user_type === 'jobseeker'): ?>
                        <a href="../user/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="../user/profile.php" class="list-group-item list-group-item-action">Profile</a>
                        <a href="../user/applications.php" class="list-group-item list-group-item-action">Applications</a>
                    <?php else: ?>
                        <a href="../employer/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="../employer/profile.php" class="list-group-item list-group-item-action">Company Profile</a>
                        <a href="../employer/manage-jobs.php" class="list-group-item list-group-item-action">Manage Jobs</a>
                    <?php endif; ?>
                    <a href="index.php" class="list-group-item list-group-item-action active">Messages</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Conversations</h5>
                    <a href="new-message.php" class="btn btn-primary btn-sm">New Message</a>
                </div>
                <div class="card-body">
                    <?php if ($conversations->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while($conv = $conversations->fetch_assoc()): ?>
                                <a href="conversation.php?id=<?php echo $conv['id']; ?>" class="list-group-item list-group-item-action 
                                    <?php echo ($conv['unread_count'] > 0) ? 'fw-bold' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <?php echo htmlspecialchars($conv['other_username']); ?>
                                            <?php if ($conv['other_user_type'] === 'employer'): ?>
                                                <span class="badge bg-primary">Employer</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Job Seeker</span>
                                            <?php endif; ?>
                                        </h5>
                                        <small class="text-muted"><?php echo timeAgo($conv['last_message_time']); ?></small>
                                    </div>
                                    <p class="mb-1 text-truncate"><?php echo htmlspecialchars($conv['last_message'] ?? 'No messages yet'); ?></p>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?php echo $conv['unread_count']; ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">You have no conversations yet.</h4>
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

<?php include '../includes/footer.php'; ?>