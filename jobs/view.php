<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if job ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$job_id = intval($_GET['id']);

// Get job details
$stmt = $conn->prepare("SELECT j.*, e.company_name, e.company_logo, e.website, e.about, u.email 
                        FROM jobs j 
                        JOIN employer_profiles e ON j.employer_id = e.user_id 
                        JOIN users u ON j.employer_id = u.id
                        WHERE j.id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$job = $result->fetch_assoc();
$base_path = '../';
$active_page = 'jobs';

// Check if user has already applied
$has_applied = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'jobseeker') {
    $stmt = $conn->prepare("SELECT id FROM job_applications 
                           WHERE job_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
    $stmt->execute();
    $has_applied = ($stmt->get_result()->num_rows > 0);
}

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h2>
                    <p class="text-muted">Posted on <?php echo date('F j, Y', strtotime($job['created_at'])); ?></p>
                    
                    <div class="mb-4">
                        <h5>Job Description</h5>
                        <div><?php echo nl2br(htmlspecialchars($job['description'])); ?></div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Requirements</h5>
                        <div><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></div>
                    </div>
                    
                    <?php if ($job['responsibilities']): ?>
                    <div class="mb-4">
                        <h5>Responsibilities</h5>
                        <div><?php echo nl2br(htmlspecialchars($job['responsibilities'])); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'jobseeker'): ?>
                        <div class="mt-4">
                            <?php if ($has_applied): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle-fill"></i> You have already applied for this position.
                                </div>
                            <?php else: ?>
                                <a href="../user/apply.php?job_id=<?php echo $job_id; ?>" class="btn btn-primary">Apply Now</a>
                                <a href="../messages/new-message.php?recipient_id=<?php echo $job['employer_id']; ?>" class="btn btn-outline-primary ms-2">
                                    <i class="bi bi-chat-dots"></i> Contact Employer
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-info mt-4">
                            <a href="../login.php">Login</a> or <a href="../register.php">Register</a> as a job seeker to apply for this position.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Job Details</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></li>
                        <li class="list-group-item"><strong>Type:</strong> <?php echo htmlspecialchars($job['employment_type']); ?></li>
                        <?php if ($job['salary']): ?>
                        <li class="list-group-item"><strong>Salary:</strong> <?php echo htmlspecialchars($job['salary']); ?></li>
                        <?php endif; ?>
                        <li class="list-group-item"><strong>Category:</strong> <?php echo htmlspecialchars($job['category']); ?></li>
                        <li class="list-group-item"><strong>Experience:</strong> <?php echo htmlspecialchars($job['experience_level']); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">About the Company</h5>
                    <?php if ($job['company_logo']): ?>
                    <div class="text-center mb-3">
                        <img src="<?php echo $base_path . 'uploads/logos/' . $job['company_logo']; ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?>" class="img-fluid" style="max-height: 100px;">
                    </div>
                    <?php endif; ?>
                    <h6><?php echo htmlspecialchars($job['company_name']); ?></h6>
                    <p><?php echo nl2br(htmlspecialchars($job['about'])); ?></p>
                    <?php if ($job['website']): ?>
                    <a href="<?php echo htmlspecialchars($job['website']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-globe"></i> Visit Website
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>