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
$active_page = 'admin-dashboard';

// Get quick stats
$stats = getAdminDashboardStats();

include '../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <h2>Admin Dashboard</h2>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h2><?php echo $stats['total_users']; ?></h2>
                    <div>
                        <span>Job Seekers: <?php echo $stats['jobseekers']; ?></span><br>
                        <span>Employers: <?php echo $stats['employers']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Job Listings</h5>
                    <h2><?php echo $stats['total_jobs']; ?></h2>
                    <div>
                        <span>Pending Approval: <?php echo $stats['pending_jobs']; ?></span><br>
                        <span>Active: <?php echo $stats['active_jobs']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Applications</h5>
                    <h2><?php echo $stats['total_applications']; ?></h2>
                    <div>
                        <span>This month: <?php echo $stats['applications_this_month']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">New Registrations</h5>
                    <h2><?php echo $stats['new_users_this_month']; ?></h2>
                    <div>
                        <span>This week: <?php echo $stats['new_users_this_week']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Job Listings</h5>
                    <a href="approve-jobs.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php $recent_jobs = getRecentJobs(5); ?>
                    <?php if (count($recent_jobs) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Company</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_jobs as $job): ?>
                                        <tr>
                                            <td><a href="job-details.php?id=<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a></td>
                                            <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                            <td>
                                                <?php if($job['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php elseif($job['status'] == 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($job['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo !empty($job['posted_date']) ? date('M j, Y', strtotime($job['posted_date'])) : 'Not available'; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No recent job listings.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Users</h5>
                    <a href="manage-accounts.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php $recent_users = getRecentUsers(5); ?>
                    <?php if (count($recent_users) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Type</th>
                                        <th>Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_users as $user): ?>
                                        <tr>
                                            <td><a href="user-details.php?id=<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></a></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <?php if($user['user_type'] == 'jobseeker'): ?>
                                                    <span class="badge bg-info">Job Seeker</span>
                                                <?php elseif($user['user_type'] == 'employer'): ?>
                                                    <span class="badge bg-primary">Employer</span>
                                                <?php else: ?>
                                                    <span class="badge bg-dark">Admin</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No recent users.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>