<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Redirect to compose.php with the same parameters to maintain compatibility
$query = http_build_query($_GET);
$redirect = "compose.php" . ($query ? "?$query" : "");
header("Location: $redirect");
exit();

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
$error_msg = '';
$success_msg = '';

// Get potential contacts based on user type
if ($user_type === 'jobseeker') {
    // Get employers from jobs the user has applied to
    $sql = "SELECT DISTINCT u.id, u.username, ep.company_name
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            JOIN users u ON j.employer_id = u.id
            JOIN employer_profiles ep ON u.id = ep.user_id
            WHERE ja.user_id = ?
            ORDER BY ep.company_name";
} else {
    // Get job seekers who have applied to employer's jobs
    $sql = "SELECT DISTINCT u.id, u.username, CONCAT(jp.first_name, ' ', jp.last_name) as full_name
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            JOIN users u ON ja.user_id = u.id
            JOIN jobseeker_profiles jp ON u.id = jp.user_id
            WHERE j.employer_id = ?
            ORDER BY full_name";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$contacts = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['recipient_id']) && isset($_POST['message']) && trim($_POST['message']) !== '') {
        $recipient_id = intval($_POST['recipient_id']);
        $message = trim($_POST['message']);
        
        // Check if recipient exists and is valid
        $sql = "SELECT id FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $recipient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Check if conversation already exists
            $sql = "SELECT id FROM conversations 
                    WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $user_id, $recipient_id, $recipient_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $conn->begin_transaction();
            
            try {
                if ($result->num_rows > 0) {
                    // Use existing conversation
                    $conversation_id = $result->fetch_assoc()['id'];
                } else {
                    // Create new conversation
                    $sql = "INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $user_id, $recipient_id);
                    $stmt->execute();
                    $conversation_id = $conn->insert_id;
                }
                
                // Add message
                $sql = "INSERT INTO messages (conversation_id, sender_id, message) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iis", $conversation_id, $user_id, $message);
                $stmt->execute();
                
                $conn->commit();
                
                // Redirect to the conversation
                header("Location: conversation.php?id=" . $conversation_id);
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_msg = "Failed to send message. Please try again.";
            }
        } else {
            $error_msg = "Invalid recipient selected.";
        }
    } else {
        $error_msg = "Please select a recipient and enter a message.";
    }
}

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
                    <h5 class="mb-0">New Message</h5>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Messages</a>
                </div>
                <div class="card-body">
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($contacts->num_rows > 0): ?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="recipient_id" class="form-label">Select Recipient</label>
                                <select class="form-select" id="recipient_id" name="recipient_id" required>
                                    <option value="">Choose...</option>
                                    <?php while ($contact = $contacts->fetch_assoc()): ?>
                                        <option value="<?php echo $contact['id']; ?>">
                                            <?php 
                                            if ($user_type === 'jobseeker') {
                                                echo htmlspecialchars($contact['company_name'] ?: $contact['username']);
                                            } else {
                                                echo htmlspecialchars($contact['full_name'] ?: $contact['username']);
                                            }
                                            ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">No contacts available yet.</h4>
                            <?php if ($user_type === 'jobseeker'): ?>
                                <p>Apply for jobs to connect with employers.</p>
                                <a href="../jobs/index.php" class="btn btn-primary">Browse Jobs</a>
                            <?php else: ?>
                                <p>You need job applicants to start messaging.</p>
                                <a href="../employer/post-job.php" class="btn btn-primary">Post a Job</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>