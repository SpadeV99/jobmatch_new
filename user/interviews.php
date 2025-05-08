<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/interview_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as jobseeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$active_page = 'interviews';

// Get interviews for the jobseeker
$upcoming_interviews = getJobseekerInterviews($user_id, 'upcoming');
$past_interviews = getJobseekerInterviews($user_id, 'past');

$base_path = '../';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar navigation -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Jobseeker Navigation</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                    <a href="profile.php" class="list-group-item list-group-item-action">My Profile</a>
                    <a href="applications.php" class="list-group-item list-group-item-action">My Applications</a>
                    <a href="interviews.php" class="list-group-item list-group-item-action active">Interviews</a>
                    <a href="saved-jobs.php" class="list-group-item list-group-item-action">Saved Jobs</a>
                    <a href="preferences.php" class="list-group-item list-group-item-action">Job Preferences</a>
                    <a href="../notifications/index.php" class="list-group-item list-group-item-action">Notifications</a>
                    <a href="../messages/index.php" class="list-group-item list-group-item-action">Messages</a>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">My Interviews</h4>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="interviewTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">
                                <i class="bi bi-calendar-event"></i> Upcoming Interviews
                                <?php if (count($upcoming_interviews) > 0): ?>
                                    <span class="badge bg-primary ms-2"><?php echo count($upcoming_interviews); ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab" aria-controls="past" aria-selected="false">
                                <i class="bi bi-calendar-check"></i> Past Interviews
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content pt-3" id="interviewTabsContent">
                        <!-- Upcoming Interviews Tab -->
                        <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                            <?php if (count($upcoming_interviews) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Job Position</th>
                                                <th>Company</th>
                                                <th>Date & Time</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcoming_interviews as $interview): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($interview['job_title']); ?></td>
                                                    <td><?php echo htmlspecialchars($interview['company_name']); ?></td>
                                                    <td>
                                                        <strong><?php echo date('M j, Y', strtotime($interview['interview_date'])); ?></strong><br>
                                                        <?php echo date('g:i A', strtotime($interview['interview_date'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($interview['interview_type'] === 'video'): ?>
                                                            <span class="badge bg-primary">Video</span>
                                                        <?php elseif ($interview['interview_type'] === 'phone'): ?>
                                                            <span class="badge bg-info">Phone</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">In-Person</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($interview['status'] === 'scheduled'): ?>
                                                            <span class="badge bg-success">Scheduled</span>
                                                        <?php elseif ($interview['status'] === 'rescheduled'): ?>
                                                            <span class="badge bg-warning">Rescheduled</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="../interviews/view.php?id=<?php echo $interview['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-eye"></i> View Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i> You don't have any upcoming interviews scheduled.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Past Interviews Tab -->
                        <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                            <?php if (count($past_interviews) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Job Position</th>
                                                <th>Company</th>
                                                <th>Date & Time</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($past_interviews as $interview): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($interview['job_title']); ?></td>
                                                    <td><?php echo htmlspecialchars($interview['company_name']); ?></td>
                                                    <td>
                                                        <strong><?php echo date('M j, Y', strtotime($interview['interview_date'])); ?></strong><br>
                                                        <?php echo date('g:i A', strtotime($interview['interview_date'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($interview['interview_type'] === 'video'): ?>
                                                            <span class="badge bg-primary">Video</span>
                                                        <?php elseif ($interview['interview_type'] === 'phone'): ?>
                                                            <span class="badge bg-info">Phone</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">In-Person</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($interview['status'] === 'completed'): ?>
                                                            <span class="badge bg-success">Completed</span>
                                                        <?php elseif ($interview['status'] === 'cancelled'): ?>
                                                            <span class="badge bg-danger">Cancelled</span>
                                                        <?php elseif ($interview['status'] === 'no_show'): ?>
                                                            <span class="badge bg-dark">No Show</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="../interviews/view.php?id=<?php echo $interview['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-eye"></i> View Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i> You don't have any past interviews.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tips for Interviews Card -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Interview Tips</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Before the Interview</h6>
                            <ul>
                                <li>Research the company thoroughly</li>
                                <li>Practice common interview questions</li>
                                <li>Prepare questions to ask the interviewer</li>
                                <li>Plan your outfit and travel arrangements</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>During the Interview</h6>
                            <ul>
                                <li>Arrive 10-15 minutes early</li>
                                <li>Make positive first impressions</li>
                                <li>Use the STAR method (Situation, Task, Action, Result)</li>
                                <li>Be confident and authentic</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Add this function to interview_functions.php if it doesn't exist already
if (!function_exists('getJobseekerInterviews')) {
    function getJobseekerInterviews($user_id, $type = 'upcoming') {
        global $conn;
        
        $user_id = intval($user_id);
        $current_date = date('Y-m-d H:i:s');
        
        if ($type === 'upcoming') {
            $condition = "i.interview_date >= '$current_date' AND i.status IN ('scheduled', 'rescheduled')";
            $order = "i.interview_date ASC";
        } else {
            $condition = "i.interview_date < '$current_date' OR i.status IN ('completed', 'cancelled', 'no_show')";
            $order = "i.interview_date DESC";
        }
        
        $sql = "SELECT i.*, j.title AS job_title, e.company_name 
                FROM interviews i
                JOIN jobs j ON i.job_id = j.id
                JOIN job_applications a ON i.application_id = a.id
                JOIN employer_profiles e ON j.employer_id = e.user_id
                WHERE i.jobseeker_id = ? AND ($condition)
                ORDER BY $order";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $interviews = [];
        while ($row = $result->fetch_assoc()) {
            $interviews[] = $row;
        }
        
        return $interviews;
    }
}

include '../includes/footer.php';
?>