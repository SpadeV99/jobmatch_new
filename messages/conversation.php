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
$conversation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify user has access to this conversation
$sql = "SELECT * FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Redirect if conversation doesn't exist or user doesn't have access
    header("Location: index.php");
    exit();
}

$conversation = $result->fetch_assoc();

// Identify the other user in conversation
$other_user_id = ($conversation['user1_id'] == $user_id) ? 
                  $conversation['user2_id'] : 
                  $conversation['user1_id'];

// Get other user's details
$sql = "SELECT u.username, u.user_type,
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
$result = $stmt->get_result();
$other_user = $result->fetch_assoc();

// Process new message submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $message_text = trim($_POST['message']);
    
    if (!empty($message_text)) {
        if (sendMessage($conversation_id, $user_id, $message_text)) {
            // Success - no need for message as we'll just update the chat display
            // Redirect to prevent form resubmission
            header("Location: conversation.php?id=" . $conversation_id);
            exit();
        } else {
            $error_message = "Failed to send message. Please try again.";
        }
    } else {
        $error_message = "Message cannot be empty.";
    }
}

// Get messages
$messages = getConversationMessages($conversation_id, $user_id);

$base_path = '../';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar navigation -->
        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Navigation</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if ($user_type === 'jobseeker'): ?>
                        <a href="../user/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="../user/profile.php" class="list-group-item list-group-item-action">My Profile</a>
                        <a href="../user/applications.php" class="list-group-item list-group-item-action">Job Applications</a>
                    <?php else: ?>
                        <a href="../employer/index.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="../employer/post-job.php" class="list-group-item list-group-item-action">Post a Job</a>
                        <a href="../employer/manage-applications.php" class="list-group-item list-group-item-action">Applications</a>
                    <?php endif; ?>
                    <a href="index.php" class="list-group-item list-group-item-action active">Messages</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Conversations</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php 
                        $all_conversations = getUserConversations($user_id);
                        foreach ($all_conversations as $conv): 
                        ?>
                            <a href="conversation.php?id=<?php echo $conv['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo $conv['id'] == $conversation_id ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($conv['other_username']); ?>
                                <?php if ($conv['unread_count'] > 0 && $conv['id'] != $conversation_id): ?>
                                    <span class="badge bg-danger float-end"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                        
                        <?php if (count($all_conversations) === 0): ?>
                            <div class="list-group-item">No conversations</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content - chat -->
        <div class="col-md-9">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">
                            <?php echo htmlspecialchars($other_user['display_name'] ?? $other_user['username']); ?>
                        </h4>
                        <small class="text-muted">
                            <?php echo ucfirst($other_user['user_type']); ?>
                        </small>
                    </div>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Messages
                    </a>
                </div>
                <div class="card-body">
                    <!-- Message history -->
                    <div class="chat-container mb-4" style="height: 400px; overflow-y: auto; display: flex; flex-direction: column-reverse;">
                        <div class="chat-messages">
                            <?php if (count($messages) === 0): ?>
                                <div class="text-center text-muted my-4">
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="mb-3 <?php echo $message['sender_id'] == $user_id ? 'text-end' : ''; ?>">
                                        <div class="d-inline-block p-2 px-3 rounded-3 
                                            <?php echo $message['sender_id'] == $user_id ? 
                                                'bg-primary text-white' : 'bg-light'; ?>">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <?php echo date('M d, h:i A', strtotime($message['created_at'])); ?>
                                            <?php if ($message['sender_id'] == $user_id && $message['is_read']): ?>
                                                <i class="bi bi-check-all"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Send message form -->
                    <form method="post" action="">
                        <div class="input-group">
                            <textarea class="form-control" name="message" placeholder="Type your message..." rows="2"></textarea>
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="bi bi-send"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Scroll to bottom of chat history on page load
document.addEventListener('DOMContentLoaded', function() {
    const chatContainer = document.querySelector('.chat-container');
    if (chatContainer) {
        chatContainer.scrollTop = 0; // Scroll to top which is actually the bottom due to flex-direction: column-reverse
    }
});
</script>

<?php include '../includes/footer.php'; ?>