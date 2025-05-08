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

$recipient_id = isset($_GET['recipient_id']) ? intval($_GET['recipient_id']) : 0;
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$error_message = "";
$success_message = "";

// Get recipient information
if ($recipient_id > 0) {
    // Check if recipient exists
    $sql = "SELECT id, username, user_type FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $recipient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error_message = "Recipient not found.";
        $recipient_id = 0;
    } else {
        $recipient = $result->fetch_assoc();
        
        // Get additional recipient info based on user type
        if ($recipient['user_type'] === 'employer') {
            $sql = "SELECT company_name FROM employer_profiles WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $recipient_id);
            $stmt->execute();
            $company_result = $stmt->get_result();
            
            if ($company_result->num_rows > 0) {
                $company = $company_result->fetch_assoc();
                $recipient['display_name'] = $company['company_name'];
            } else {
                $recipient['display_name'] = $recipient['username'];
            }
        } else {
            $sql = "SELECT first_name, last_name FROM jobseeker_profiles WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $recipient_id);
            $stmt->execute();
            $profile_result = $stmt->get_result();
            
            if ($profile_result->num_rows > 0) {
                $profile = $profile_result->fetch_assoc();
                $recipient['display_name'] = $profile['first_name'] . ' ' . $profile['last_name'];
            } else {
                $recipient['display_name'] = $recipient['username'];
            }
        }
    }
}

// Get job information if provided
$job_title = '';
if ($job_id > 0) {
    $sql = "SELECT title FROM jobs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $job = $result->fetch_assoc();
        $job_title = $job['title'];
    }
}

// Process message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $to_user_id = intval($_POST['recipient_id']);
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);
    
    // Basic validation
    if (empty($subject) || empty($message_text) || $to_user_id === 0) {
        $error_message = "Please fill all required fields.";
    } else {
        // Check if there's an existing conversation
        $sql = "SELECT id FROM conversations 
                WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $user_id, $to_user_id, $to_user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $conversation = $result->fetch_assoc();
            $conversation_id = $conversation['id'];
        } else {
            // Create a new conversation
            $sql = "INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $to_user_id);
            $stmt->execute();
            $conversation_id = $conn->insert_id;
        }
        
        // Add the message to the conversation
        $sql = "INSERT INTO messages (conversation_id, sender_id, recipient_id, subject, message, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiss", $conversation_id, $user_id, $to_user_id, $subject, $message_text);
        
        if ($stmt->execute()) {
            $success_message = "Message sent successfully!";
            
            // Redirect to the conversation
            header("Location: conversation.php?id=" . $conversation_id);
            exit();
        } else {
            $error_message = "Error sending message: " . $conn->error;
        }
    }
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
                    <?php if ($user_type === 'jobseeker'): ?>
                        <a href="../user/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="../user/profile.php" class="list-group-item list-group-item-action">My Profile</a>
                        <a href="../user/applications.php" class="list-group-item list-group-item-action">Job Applications</a>
                        <a href="../user/interviews.php" class="list-group-item list-group-item-action">Interviews</a>
                        <a href="../user/saved-jobs.php" class="list-group-item list-group-item-action">Saved Jobs</a>
                        <a href="../user/recommendations.php" class="list-group-item list-group-item-action">Recommendations</a>
                        <a href="../user/preferences.php" class="list-group-item list-group-item-action">Job Preferences</a>
                    <?php else: ?>
                        <a href="../employer/index.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="../employer/company-profile.php" class="list-group-item list-group-item-action">Company Profile</a>
                        <a href="../employer/post-job.php" class="list-group-item list-group-item-action">Post a Job</a>
                        <a href="../employer/manage-jobs.php" class="list-group-item list-group-item-action">Manage Jobs</a>
                        <a href="../employer/manage-applications.php" class="list-group-item list-group-item-action">Applications</a>
                        <a href="../employer/interviews.php" class="list-group-item list-group-item-action">Interviews</a>
                    <?php endif; ?>
                    <a href="index.php" class="list-group-item list-group-item-action active">Messages</a>
                    <a href="../notifications/index.php" class="list-group-item list-group-item-action">Notifications</a>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Compose Message</h5>
                    <a href="index.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Messages
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="recipient_id" class="form-label">To</label>
                            <?php if ($recipient_id > 0): ?>
                                <input type="hidden" name="recipient_id" value="<?php echo $recipient_id; ?>">
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($recipient['display_name']); ?>" readonly>
                            <?php else: ?>
                                <select class="form-select" id="recipient_id" name="recipient_id" required>
                                    <option value="">Select Recipient</option>
                                    <?php
                                    // Get potential recipients based on user type
                                    if ($user_type === 'jobseeker') {
                                        // For job seekers, show employers they've applied to
                                        $sql = "SELECT DISTINCT e.id, e.username, ep.company_name 
                                                FROM job_applications ja
                                                JOIN jobs j ON ja.job_id = j.id
                                                JOIN users e ON j.employer_id = e.id
                                                LEFT JOIN employer_profiles ep ON e.id = ep.user_id
                                                WHERE ja.user_id = ?
                                                ORDER BY ep.company_name";
                                    } else {
                                        // For employers, show job seekers who applied to their jobs
                                        $sql = "SELECT DISTINCT js.id, js.username, jp.first_name, jp.last_name
                                                FROM job_applications ja
                                                JOIN jobs j ON ja.job_id = j.id
                                                JOIN users js ON ja.user_id = js.id
                                                LEFT JOIN jobseeker_profiles jp ON js.id = jp.user_id
                                                WHERE j.employer_id = ?
                                                ORDER BY jp.first_name, jp.last_name";
                                    }
                                    
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $recipients = $stmt->get_result();
                                    
                                    while ($row = $recipients->fetch_assoc()):
                                        $display_name = '';
                                        if ($user_type === 'jobseeker') {
                                            $display_name = !empty($row['company_name']) ? $row['company_name'] : $row['username'];
                                        } else {
                                            $display_name = !empty($row['first_name']) ? 
                                                $row['first_name'] . ' ' . $row['last_name'] : $row['username'];
                                        }
                                    ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($display_name); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required
                                   value="<?php echo !empty($job_title) ? 'RE: ' . htmlspecialchars($job_title) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="bi bi-send"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>