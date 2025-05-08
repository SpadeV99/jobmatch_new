<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$base_path = '../';
$active_page = 'manage-accounts';

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage-accounts.php");
    exit();
}

$target_user_id = intval($_GET['id']);

// Get user details
$stmt = $conn->prepare("SELECT u.*, 
                        CASE WHEN EXISTS (
                            SELECT 1 FROM user_status us WHERE us.user_id = u.id AND us.status = 'inactive'
                        ) THEN 'inactive' ELSE 'active' END as status
                        FROM users u 
                        WHERE u.id = ?");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage-accounts.php");
    exit();
}

$user = $result->fetch_assoc();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'activate':
                activateUser($target_user_id);
                $_SESSION['admin_message'] = "User activated successfully.";
                // Refresh user data
                header("Location: user-details.php?id=" . $target_user_id . "&success=activated");
                exit();
                break;
                
            case 'deactivate':
                deactivateUser($target_user_id);
                $_SESSION['admin_message'] = "User deactivated successfully.";
                header("Location: user-details.php?id=" . $target_user_id . "&success=deactivated");
                exit();
                break;
                
            case 'delete':
                if (deleteUser($target_user_id)) {
                    $_SESSION['admin_message'] = "User deleted successfully.";
                    header("Location: manage-accounts.php?success=deleted");
                    exit();
                } else {
                    $_SESSION['admin_message'] = "Error deleting user.";
                    header("Location: user-details.php?id=" . $target_user_id . "&error=delete_failed");
                    exit();
                }
                break;
        }
    }
}

// Get additional profile details based on user type
$profile_details = [];

if ($user['user_type'] === 'jobseeker') {
    $stmt = $conn->prepare("SELECT * FROM jobseeker_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $target_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $profile_details = $result->fetch_assoc();
    }
} elseif ($user['user_type'] === 'employer') {
    $stmt = $conn->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $target_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $profile_details = $result->fetch_assoc();
    }
}

include '../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="manage-accounts.php">Manage Accounts</a></li>
                    <li class="breadcrumb-item active" aria-current="page">User Details</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
                echo $_SESSION['admin_message'];
                unset($_SESSION['admin_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php if ($_GET['success'] === 'activated'): ?>
                User has been successfully activated.
            <?php elseif ($_GET['success'] === 'deactivated'): ?>
                User has been successfully deactivated.
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php if ($_GET['error'] === 'delete_failed'): ?>
                Failed to delete user. Please try again.
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 text-center">
                        <div class="avatar-circle mb-3">
                            <?php if ($user['user_type'] === 'jobseeker'): ?>
                                <i class="bi bi-person-circle display-1"></i>
                            <?php elseif ($user['user_type'] === 'employer'): ?>
                                <i class="bi bi-building display-1"></i>
                            <?php else: ?>
                                <i class="bi bi-person-badge display-1"></i>
                            <?php endif; ?>
                        </div>
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                        <span class="badge bg-<?php 
                            echo $user['user_type'] === 'jobseeker' ? 'info' : 
                                ($user['user_type'] === 'employer' ? 'primary' : 'dark'); 
                        ?>">
                            <?php echo ucfirst($user['user_type']); ?>
                        </span>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>ID:</span>
                            <span class="text-muted">#<?php echo $user['id']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Email:</span>
                            <span class="text-muted"><?php echo htmlspecialchars($user['email']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Registration Date:</span>
                            <span class="text-muted">
                                <?php echo !empty($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : 'Unknown'; ?>
                            </span>
                        </li>
                    </ul>
                    
                    <div class="mt-3">
                        <div class="d-grid gap-2">
                            <?php if ($user['status'] === 'inactive'): ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="activate">
                                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to activate this user?')">
                                        <i class="bi bi-check-circle"></i> Activate User
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="deactivate">
                                    <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Are you sure you want to deactivate this user?')">
                                        <i class="bi bi-x-circle"></i> Deactivate User
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="post">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to DELETE this user? This action cannot be undone!')">
                                    <i class="bi bi-trash"></i> Delete User
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if ($user['user_type'] === 'jobseeker'): ?>
                <!-- Jobseeker Profile Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Jobseeker Profile</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($profile_details)): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Personal Information</h6>
                                    <ul class="list-group list-group-flush mb-3">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Name:</span>
                                            <span class="text-muted">
                                                <?php echo htmlspecialchars($profile_details['first_name'] . ' ' . $profile_details['last_name']); ?>
                                            </span>
                                        </li>
                                        <?php if (!empty($profile_details['phone'])): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Phone:</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($profile_details['phone']); ?></span>
                                        </li>
                                        <?php endif; ?>
                                        <?php if (!empty($profile_details['location'])): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Location:</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($profile_details['location']); ?></span>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Professional Information</h6>
                                    <ul class="list-group list-group-flush mb-3">
                                        <?php if (!empty($profile_details['headline'])): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Headline:</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($profile_details['headline']); ?></span>
                                        </li>
                                        <?php endif; ?>
                                        <?php if (!empty($profile_details['skills'])): ?>
                                        <li class="list-group-item">
                                            <span>Skills:</span>
                                            <div class="mt-1">
                                                <?php 
                                                $skills = explode(',', $profile_details['skills']);
                                                foreach ($skills as $skill): 
                                                    $skill = trim($skill);
                                                    if (!empty($skill)):
                                                ?>
                                                    <span class="badge bg-light text-dark me-1 mb-1"><?php echo htmlspecialchars($skill); ?></span>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </div>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                            
                            <?php if (!empty($profile_details['about'])): ?>
                            <div class="mt-3">
                                <h6>About</h6>
                                <p><?php echo nl2br(htmlspecialchars($profile_details['about'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($profile_details['resume'])): ?>
                            <div class="mt-3">
                                <h6>Resume</h6>
                                <a href="<?php echo $base_path; ?>uploads/resumes/<?php echo $profile_details['resume']; ?>" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-file-earmark-text"></i> View Resume
                                </a>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                This user has not completed their jobseeker profile yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Applications Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Job Applications</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $conn->prepare("SELECT ja.*, j.title as job_title, ep.company_name
                                              FROM job_applications ja
                                              LEFT JOIN jobs j ON ja.job_id = j.id
                                              LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                                              WHERE ja.user_id = ?
                                              ORDER BY ja.id DESC");
                        $stmt->bind_param("i", $target_user_id);
                        $stmt->execute();
                        $applications = $stmt->get_result();
                        ?>
                        
                        <?php if ($applications->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Job Title</th>
                                            <th>Company</th>
                                            <th>Status</th>
                                            <th>Applied</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($app = $applications->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $app['id']; ?></td>
                                                <td>
                                                    <?php if (!empty($app['job_title'])): ?>
                                                        <a href="<?php echo $base_path; ?>jobs/view.php?id=<?php echo $app['job_id']; ?>" target="_blank">
                                                            <?php echo htmlspecialchars($app['job_title']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <em class="text-muted">Job no longer available</em>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo !empty($app['company_name']) ? htmlspecialchars($app['company_name']) : '-'; ?></td>
                                                <td>
                                                    <?php if (!isset($app['status']) || $app['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php elseif ($app['status'] === 'reviewed'): ?>
                                                        <span class="badge bg-info">Reviewed</span>
                                                    <?php elseif ($app['status'] === 'interview'): ?>
                                                        <span class="badge bg-primary">Interview</span>
                                                    <?php elseif ($app['status'] === 'hired'): ?>
                                                        <span class="badge bg-success">Hired</span>
                                                    <?php elseif ($app['status'] === 'rejected'): ?>
                                                        <span class="badge bg-danger">Rejected</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?php echo ucfirst($app['status']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (isset($app['created_at'])) {
                                                        echo date('M j, Y', strtotime($app['created_at']));
                                                    } else {
                                                        echo "-";
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                This user has not submitted any job applications.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($user['user_type'] === 'employer'): ?>
                <!-- Employer Profile Card -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Employer Profile</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($profile_details)): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Company Information</h6>
                                    <ul class="list-group list-group-flush mb-3">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Company Name:</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($profile_details['company_name']); ?></span>
                                        </li>
                                        <?php if (!empty($profile_details['industry'])): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Industry:</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($profile_details['industry']); ?></span>
                                        </li>
                                        <?php endif; ?>
                                        <?php if (!empty($profile_details['company_size'])): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Company Size:</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($profile_details['company_size']); ?></span>
                                        </li>
                                        <?php endif; ?>
                                        <?php if (!empty($profile_details['location'])): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Location:</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($profile_details['location']); ?></span>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Contact Information</h6>
                                    <ul class="list-group list-group-flush mb-3">
                                        <?php if (!empty($profile_details['website'])): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Website:</span>
                                            <span class="text-muted">
                                                <a href="<?php echo htmlspecialchars($profile_details['website']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($profile_details['website']); ?>
                                                </a>
                                            </span>
                                        </li>
                                        <?php endif; ?>
                                        <?php if (!empty($profile_details['contact_phone'])): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Phone:</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($profile_details['contact_phone']); ?></span>
                                        </li>
                                        <?php endif; ?>
                                        <?php if (!empty($profile_details['contact_email'])): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Email:</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($profile_details['contact_email']); ?></span>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                            
                            <?php if (!empty($profile_details['company_logo'])): ?>
                            <div class="mt-3">
                                <h6>Company Logo</h6>
                                <img src="<?php echo $base_path; ?>uploads/logos/<?php echo $profile_details['company_logo']; ?>" 
                                     alt="<?php echo htmlspecialchars($profile_details['company_name']); ?>" 
                                     class="img-fluid" style="max-height: 100px;">
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($profile_details['about'])): ?>
                            <div class="mt-3">
                                <h6>About Company</h6>
                                <p><?php echo nl2br(htmlspecialchars($profile_details['about'])); ?></p>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                This user has not completed their employer profile yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Job Listings Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Job Listings</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM jobs WHERE employer_id = ? ORDER BY id DESC");
                        $stmt->bind_param("i", $target_user_id);
                        $stmt->execute();
                        $jobs = $stmt->get_result();
                        ?>
                        
                        <?php if ($jobs->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                            <th>Applications</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($job = $jobs->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $job['id']; ?></td>
                                                <td>
                                                    <a href="<?php echo $base_path; ?>jobs/view.php?id=<?php echo $job['id']; ?>" target="_blank">
                                                        <?php echo htmlspecialchars($job['title']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                                <td>
                                                    <?php if (!isset($job['status']) || $job['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php elseif ($job['status'] === 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php elseif ($job['status'] === 'rejected'): ?>
                                                        <span class="badge bg-danger">Rejected</span>
                                                    <?php elseif ($job['status'] === 'expired'): ?>
                                                        <span class="badge bg-secondary">Expired</span>
                                                    <?php elseif ($job['status'] === 'filled'): ?>
                                                        <span class="badge bg-info">Filled</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Unknown</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $app_stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_applications WHERE job_id = ?");
                                                    $app_stmt->bind_param("i", $job['id']);
                                                    $app_stmt->execute();
                                                    $app_result = $app_stmt->get_result();
                                                    $app_count = $app_result->fetch_assoc()['count'];
                                                    echo $app_count;
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                This employer has not posted any job listings yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Admin user view -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Admin Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            This is an administrator account with full system access.
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>