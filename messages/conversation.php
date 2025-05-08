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

// Check if conversation ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$conversation_id = intval($_GET['id']);

// Verify the user is part of this conversation
$sql = "SELECT * FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
$stmt->execute();
$conversation = $stmt->get_result()->fetch_assoc();

if (!$conversation) {
    header("Location: index.php");
    exit();
}

// Get other user's details
$other_user_id = ($conversation['user1_id'] == $user_id) ? $conversation['user2_id'] : $conversation['user1_id'];

$sql = "SELECT u.id, u.username, u.user_type, 
        CASE 
            WHEN u.user_type = 'employer' THEN ep.company_name
            ELSE CONCAT(jp.first_name, ' ', jp.last_name)
        END as display_name
        FROM users u
        LEFT JOIN employer_profiles ep ON u.id = ep.user_id AND u.user_type = 'employer'
        LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id AND u.user_type = 'jobseeker'
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $other_user_id);
$stmt->execute();
$other_user = $stmt->get_result()->fetch_assoc();

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && trim($_POST['message']) !== '') {
    $message = trim($_POST['message']);
    
    $sql = "INSERT INTO messages (conversation_id, sender_id, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $conversation_id, $user_id, $message);
    
    if ($stmt->execute()) {
        // Update conversation timestamp
        $sql = "UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $conversation_id);
        $stmt->execute();
    }
    
    // Redirect to prevent form resubmission
    header("Location: conversation.php?id=" . $conversation_id);
    exit();
}

// Mark messages as read
$sql = "UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $conversation_id, $user_id);
$stmt->execute();

// Get messages for this conversation
$sql = "SELECT m.*, u.username, u.user_type FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $conversation_id);
$stmt->execute();
$messages = $stmt->get_result();

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
                    <h5 class="mb-0">
                        Conversation with <?php echo htmlspecialchars($other_user['display_name'] ?: $other_user['username']); ?>
                        <?php if ($other_user['user_type'] === 'employer'): ?>
                            <span class="badge bg-primary">Employer</span>
                        <?php else: ?>
                            <span class="badge bg-success">Job Seeker</span>
                        <?php endif; ?>
                    </h5>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Messages</a>
                </div>
                <div class="card-body">
                    <div class="message-container" style="height: 400px; overflow-y: auto;">
                        <?php if ($messages->num_rows > 0): ?>
                            <?php while($msg = $messages->fetch_assoc()): ?>
                                <div class="message mb-3 <?php echo ($msg['sender_id'] == $user_id) ? 'text-end' : ''; ?>">
                                    <div class="message-content d-inline-block p-3 rounded 
                                        <?php echo ($msg['sender_id'] == $user_id) ? 'bg-primary text-white' : 'bg-light'; ?>" 
                                        style="max-width: 80%;">
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    </div>
                                    <div class="message-meta small text-muted">
                                        <?php echo date('M j, Y g:i a', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <p>No messages yet. Start the conversation!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="post">
                        <div class="form-group">
                            <label for="message">New Message</label>
                            <textarea class="form-control mb-2" id="message" name="message" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-scroll to bottom of messages when page loads
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.querySelector('.message-container');
    messageContainer.scrollTop = messageContainer.scrollHeight;
});
</script>

<?php include '../includes/footer.php'; ?>