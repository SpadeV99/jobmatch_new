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
$active_page = 'approve-jobs';

// Check if job ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: approve-jobs.php");
    exit();
}

$job_id = intval($_GET['id']);

// Get job details
$stmt = $conn->prepare("SELECT j.*, ep.company_name 
                       FROM jobs j 
                       LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                       WHERE j.id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: approve-jobs.php");
    exit();
}

$job = $result->fetch_assoc();

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $responsibilities = trim($_POST['responsibilities']);
    $location = trim($_POST['location']);
    $job_type = trim($_POST['job_type']);
    $salary_min = !empty($_POST['salary_min']) ? trim($_POST['salary_min']) : null;
    $salary_max = !empty($_POST['salary_max']) ? trim($_POST['salary_max']) : null;
    $status = trim($_POST['status']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Check if status column exists
    $check_status = $conn->query("SHOW COLUMNS FROM jobs LIKE 'status'");
    $has_status = $check_status->num_rows > 0;
    
    // Check if is_featured column exists
    $check_featured = $conn->query("SHOW COLUMNS FROM jobs LIKE 'is_featured'");
    $has_featured = $check_featured->num_rows > 0;
    
    // Validate input
    if (empty($title) || empty($description) || empty($location) || empty($job_type)) {
        $error = "Required fields cannot be empty.";
    } else {
        // Update job in database
        $sql = "UPDATE jobs SET 
                title = ?,
                description = ?,
                requirements = ?,
                responsibilities = ?,
                location = ?,
                job_type = ?,
                salary_min = ?,
                salary_max = ?";
        
        // Add status if column exists
        if ($has_status) {
            $sql .= ", status = ?";
        }
        
        // Add is_featured if column exists
        if ($has_featured) {
            $sql .= ", is_featured = ?";
        }
        
        $sql .= " WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        
        if ($has_status && $has_featured) {
            $stmt->bind_param("ssssssddsiis", $title, $description, $requirements, $responsibilities, 
                             $location, $job_type, $salary_min, $salary_max, $status, $is_featured, $job_id);
        } elseif ($has_status) {
            $stmt->bind_param("ssssssddss", $title, $description, $requirements, $responsibilities, 
                             $location, $job_type, $salary_min, $salary_max, $status, $job_id);
        } elseif ($has_featured) {
            $stmt->bind_param("ssssssddis", $title, $description, $requirements, $responsibilities, 
                             $location, $job_type, $salary_min, $salary_max, $is_featured, $job_id);
        } else {
            $stmt->bind_param("ssssssddis", $title, $description, $requirements, $responsibilities, 
                             $location, $job_type, $salary_min, $salary_max, $job_id);
        }
        
        if ($stmt->execute()) {
            $success = "Job updated successfully!";
            
            // Refresh job data
            $stmt = $conn->prepare("SELECT j.*, ep.company_name 
                                  FROM jobs j 
                                  LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                                  WHERE j.id = ?");
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $job = $result->fetch_assoc();
        } else {
            $error = "Error updating job: " . $stmt->error;
        }
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
                    <li class="breadcrumb-item"><a href="approve-jobs.php">Manage Jobs</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Job</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Job #<?php echo $job['id']; ?></h5>
                    <div>
                        <a href="approve-jobs.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to Jobs
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="title" class="form-label">Job Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="company_name" class="form-label">Company</label>
                                <input type="text" class="form-control" id="company_name" value="<?php echo htmlspecialchars($job['company_name'] ?? 'Unknown'); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($job['location']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="job_type" class="form-label">Job Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="job_type" name="job_type" required>
                                    <option value="full_time" <?php echo $job['job_type'] === 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                                    <option value="part_time" <?php echo $job['job_type'] === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                                    <option value="contract" <?php echo $job['job_type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                    <option value="internship" <?php echo $job['job_type'] === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                    <option value="remote" <?php echo $job['job_type'] === 'remote' ? 'selected' : ''; ?>>Remote</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="salary_min" class="form-label">Minimum Salary</label>
                                <input type="number" class="form-control" id="salary_min" name="salary_min" value="<?php echo htmlspecialchars($job['salary_min'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="salary_max" class="form-label">Maximum Salary</label>
                                <input type="number" class="form-control" id="salary_max" name="salary_max" value="<?php echo htmlspecialchars($job['salary_max'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Job Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="responsibilities" class="form-label">Responsibilities</label>
                            <textarea class="form-control" id="responsibilities" name="responsibilities" rows="4"><?php echo htmlspecialchars($job['responsibilities'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="requirements" class="form-label">Requirements</label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="4"><?php echo htmlspecialchars($job['requirements'] ?? ''); ?></textarea>
                        </div>
                        
                        <?php 
                        // Check if status column exists
                        $check_status = $conn->query("SHOW COLUMNS FROM jobs LIKE 'status'");
                        if ($check_status->num_rows > 0): 
                        ?>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending" <?php echo ($job['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="active" <?php echo ($job['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="rejected" <?php echo ($job['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="expired" <?php echo ($job['status'] ?? '') === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                <option value="filled" <?php echo ($job['status'] ?? '') === 'filled' ? 'selected' : ''; ?>>Filled</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        // Check if is_featured column exists
                        $check_featured = $conn->query("SHOW COLUMNS FROM jobs LIKE 'is_featured'");
                        if ($check_featured->num_rows > 0): 
                        ?>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" <?php echo ($job['is_featured'] ?? 0) == 1 ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">Featured Job</label>
                            <small class="text-muted d-block">Featured jobs appear at the top of job listings</small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="approve-jobs.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Job</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>