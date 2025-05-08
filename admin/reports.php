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
$active_page = 'reports';

// Get some basic stats for demonstration
$stats = getAdminDashboardStats();

include '../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <h2>Reports</h2>
            <p class="text-muted">View system statistics and analytics</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Advanced reporting features are coming soon. Below are some basic statistics.
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>User Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th>Total Users</th>
                                    <td><?php echo $stats['total_users']; ?></td>
                                </tr>
                                <tr>
                                    <th>Job Seekers</th>
                                    <td><?php echo $stats['jobseekers']; ?></td>
                                </tr>
                                <tr>
                                    <th>Employers</th>
                                    <td><?php echo $stats['employers']; ?></td>
                                </tr>
                                <tr>
                                    <th>New Users (This Month)</th>
                                    <td><?php echo $stats['new_users_this_month']; ?></td>
                                </tr>
                                <tr>
                                    <th>New Users (This Week)</th>
                                    <td><?php echo $stats['new_users_this_week']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Job & Application Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th>Total Job Listings</th>
                                    <td><?php echo $stats['total_jobs']; ?></td>
                                </tr>
                                <tr>
                                    <th>Active Job Listings</th>
                                    <td><?php echo $stats['active_jobs']; ?></td>
                                </tr>
                                <tr>
                                    <th>Pending Job Listings</th>
                                    <td><?php echo $stats['pending_jobs']; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Applications</th>
                                    <td><?php echo $stats['total_applications']; ?></td>
                                </tr>
                                <tr>
                                    <th>Applications (This Month)</th>
                                    <td><?php echo $stats['applications_this_month']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Export Options</h5>
                </div>
                <div class="card-body">
                    <p>Download system data for offline analysis.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#" class="btn btn-outline-primary disabled">
                            <i class="bi bi-download"></i> Export Users
                        </a>
                        <a href="#" class="btn btn-outline-primary disabled">
                            <i class="bi bi-download"></i> Export Jobs
                        </a>
                        <a href="#" class="btn btn-outline-primary disabled">
                            <i class="bi bi-download"></i> Export Applications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>