<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get employer data
$employer_query = "SELECT u.*, ep.company_name, ep.company_description, ep.industry, 
                  ep.website, ep.phone, ep.address, ep.city, ep.state, 
                  ep.zip_code, ep.country, ep.logo_path
                  FROM users u
                  LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                  WHERE u.id = ?";
$stmt = $conn->prepare($employer_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employer = $result->fetch_assoc();

// Get count of posted jobs
$jobs_query = "SELECT COUNT(*) as job_count FROM jobs WHERE employer_id = ?";
$stmt = $conn->prepare($jobs_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$jobs_result = $stmt->get_result();
$job_count = $jobs_result->fetch_assoc()['job_count'];

// Get count of applications
$applications_query = "SELECT COUNT(*) as application_count 
                      FROM job_applications ja
                      JOIN jobs j ON ja.job_id = j.id
                      WHERE j.employer_id = ?";
$stmt = $conn->prepare($applications_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications_result = $stmt->get_result();
$application_count = $applications_result->fetch_assoc()['application_count'];

// Define base path for header
$base_path = '../';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Employer Navigation</h5>
            </div>
            <div class="list-group list-group-flush">
    <a href="index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
    <a href="company-profile.php" class="list-group-item list-group-item-action <?php echo $active_page == 'profile' ? 'active' : ''; ?>">Company Profile</a>
    <a href="post-job.php" class="list-group-item list-group-item-action <?php echo $active_page == 'post' ? 'active' : ''; ?>">Post a Job</a>
    <a href="manage-jobs.php" class="list-group-item list-group-item-action <?php echo $active_page == 'jobs' ? 'active' : ''; ?>">Manage Jobs</a>
    <a href="manage-applications.php" class="list-group-item list-group-item-action <?php echo $active_page == 'applications' ? 'active' : ''; ?>">Applications</a>
    <a href="interviews.php" class="list-group-item list-group-item-action <?php echo $active_page == 'interviews' ? 'active' : ''; ?>">Interviews</a>
    <a href="../messages/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'messages' ? 'active' : ''; ?>">Messages</a>
    <a href="../notifications/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'notifications' ? 'active' : ''; ?>">Notifications</a>
</div>
        </div>
    </div>
    
    <div class="col-md-9">
        <h2>Employer Dashboard</h2>
        <p class="lead">Welcome to your employer dashboard, <?php echo isset($employer['company_name']) ? htmlspecialchars($employer['company_name']) : htmlspecialchars($_SESSION['username']); ?>!</p>
        
        <?php if (!isset($employer['company_name']) || empty($employer['company_name'])): ?>
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> Complete Your Profile</h5>
            <p>You haven't completed your company profile yet. Adding your company information helps job seekers learn about your organization.</p>
            <a href="company-profile.php" class="btn btn-warning">Complete Profile</a>
        </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-center mb-4">
                    <div class="card-body">
                        <h1 class="display-4"><?php echo $job_count; ?></h1>
                        <p class="card-text">Jobs Posted</p>
                        <a href="manage-jobs.php" class="btn btn-primary">Manage Jobs</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card text-center mb-4">
                    <div class="card-body">
                        <h1 class="display-4"><?php echo $application_count; ?></h1>
                        <p class="card-text">Applications Received</p>
                        <a href="applications.php" class="btn btn-primary">View Applications</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card text-center mb-4">
                    <div class="card-body">
                        <h1 class="display-4"><i class="bi bi-plus-lg"></i></h1>
                        <p class="card-text">Create New Job</p>
                        <a href="post-job.php" class="btn btn-success">Post a Job</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Recent Applications</h5>
            </div>
            <div class="card-body">
                <?php
                // Get recent applications
                $recent_applications_query = "SELECT ja.id, ja.apply_date, ja.status, 
                                           j.title as job_title,
                                           u.username as applicant_name
                                           FROM job_applications ja
                                           JOIN jobs j ON ja.job_id = j.id
                                           JOIN users u ON ja.user_id = u.id
                                           WHERE j.employer_id = ?
                                           ORDER BY ja.apply_date DESC
                                           LIMIT 5";
                $stmt = $conn->prepare($recent_applications_query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $recent_applications = $stmt->get_result();
                
                if ($recent_applications->num_rows > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Job</th>
                                <th>Applicant</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($app = $recent_applications->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($app['apply_date'])); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        if ($app['status'] == 'pending') echo 'bg-warning text-dark';
                                        elseif ($app['status'] == 'shortlisted') echo 'bg-info';
                                        elseif ($app['status'] == 'interviewed') echo 'bg-primary';
                                        elseif ($app['status'] == 'offered') echo 'bg-success';
                                        elseif ($app['status'] == 'rejected') echo 'bg-danger';
                                        else echo 'bg-secondary';
                                    ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view-application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-end">
                    <a href="applications.php" class="btn btn-link">View All Applications</a>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    No applications received yet.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>