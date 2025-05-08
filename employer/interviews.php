<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/interview_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get interviews
if ($filter === 'upcoming') {
    $interviews = getEmployerInterviews($user_id, 'scheduled');
} elseif ($filter === 'completed') {
    $interviews = getEmployerInterviews($user_id, 'completed');
} elseif ($filter === 'cancelled') {
    $interviews = array_merge(
        getEmployerInterviews($user_id, 'cancelled'),
        getEmployerInterviews($user_id, 'no_show')
    );
} else {
    $interviews = getEmployerInterviews($user_id);
}

$base_path = '../';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar navigation -->
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
        
        <!-- Main content -->
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Manage Interviews</h4>
                    <a href="interview-calendar.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-calendar3"></i> Calendar View
                    </a>
                </div>
                <div class="card-body">
                    <!-- Filter tabs -->
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="?filter=all">
                                All Interviews
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $filter === 'upcoming' ? 'active' : ''; ?>" href="?filter=upcoming">
                                Upcoming
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $filter === 'completed' ? 'active' : ''; ?>" href="?filter=completed">
                                Completed
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $filter === 'cancelled' ? 'active' : ''; ?>" href="?filter=cancelled">
                                Cancelled/No-Show
                            </a>
                        </li>
                    </ul>
                    
                    <?php if (empty($interviews)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar2-x" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">No interviews found</h5>
                            <p class="text-muted">Schedule interviews with your job applicants to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Position</th>
                                        <th>Applicant</th>
                                        <th>Date & Time</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($interviews as $interview): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($interview['job_title']); ?></td>
                                            <td><?php echo htmlspecialchars($interview['jobseeker_name'] ?: $interview['jobseeker_username']); ?></td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($interview['interview_date'])); ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo date('g:i A', strtotime($interview['interview_date'])); ?>
                                                    (<?php echo $interview['duration_minutes']; ?> mins)
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($interview['interview_type'] === 'video'): ?>
                                                    <span class="badge bg-primary"><i class="bi bi-camera-video"></i> Video</span>
                                                <?php elseif ($interview['interview_type'] === 'phone'): ?>
                                                    <span class="badge bg-info"><i class="bi bi-telephone"></i> Phone</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><i class="bi bi-building"></i> In-Person</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($interview['status'] === 'scheduled'): ?>
                                                    <span class="badge bg-primary">Scheduled</span>
                                                <?php elseif ($interview['status'] === 'completed'): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php elseif ($interview['status'] === 'cancelled'): ?>
                                                    <span class="badge bg-danger">Cancelled</span>
                                                <?php elseif ($interview['status'] === 'rescheduled'): ?>
                                                    <span class="badge bg-warning">Rescheduled</span>
                                                <?php elseif ($interview['status'] === 'no_show'): ?>
                                                    <span class="badge bg-dark">No Show</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="../interviews/view.php?id=<?php echo $interview['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>