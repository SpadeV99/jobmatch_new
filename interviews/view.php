<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/interview_functions.php';

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
$interview_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get interview details
$interview = getInterviewDetails($interview_id);

// Verify access rights
if (!$interview || ($user_type === 'employer' && $interview['employer_id'] != $user_id) || 
    ($user_type === 'jobseeker' && $interview['jobseeker_id'] != $user_id)) {
    header("Location: ../" . $user_type . "/index.php");
    exit();
}

// Get reschedule requests
$reschedule_requests = getInterviewRescheduleRequests($interview_id);

// Process status update
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle status updates
    if (isset($_POST['update_status'])) {
        $new_status = $_POST['status'];
        $allowed_statuses = ['completed', 'cancelled', 'no_show'];
        
        if (in_array($new_status, $allowed_statuses)) {
            if (updateInterviewStatus($interview_id, $new_status)) {
                $success_message = "Interview status updated successfully.";
                $interview['status'] = $new_status;
            } else {
                $error_message = "Failed to update status.";
            }
        }
    }
    
    // Handle reschedule request
    if (isset($_POST['request_reschedule'])) {
        $proposed_date = $_POST['proposed_date'] . ' ' . $_POST['proposed_time'];
        $reason = trim($_POST['reschedule_reason']);
        
        if (empty($reason)) {
            $error_message = "Please provide a reason for the reschedule.";
        } else if (empty($proposed_date)) {
            $error_message = "Please select a proposed date and time.";
        } else {
            $request_id = requestInterviewReschedule($interview_id, $user_id, $proposed_date, $reason);
            
            if ($request_id) {
                $success_message = "Reschedule request submitted successfully.";
                $reschedule_requests = getInterviewRescheduleRequests($interview_id);
            } else {
                $error_message = "Failed to submit reschedule request.";
            }
        }
    }
    
    // Handle reschedule response
    if (isset($_POST['respond_to_reschedule'])) {
        $request_id = intval($_POST['request_id']);
        $response = $_POST['response'];
        
        if ($response === 'accept' || $response === 'reject') {
            $status = ($response === 'accept') ? 'accepted' : 'rejected';
            
            if (respondToRescheduleRequest($request_id, $status, $user_id)) {
                $success_message = "You have " . ($status === 'accepted' ? 'accepted' : 'rejected') . " the reschedule request.";
                
                // Refresh data
                $interview = getInterviewDetails($interview_id);
                $reschedule_requests = getInterviewRescheduleRequests($interview_id);
            } else {
                $error_message = "Failed to process your response.";
            }
        }
    }
    
    // Handle feedback submission (employer only)
    if (isset($_POST['submit_feedback']) && $user_type === 'employer') {
        $rating = intval($_POST['rating']);
        $strengths = trim($_POST['strengths']);
        $weaknesses = trim($_POST['weaknesses']);
        $technical_skills = trim($_POST['technical_skills']);
        $communication_skills = trim($_POST['communication_skills']);
        $cultural_fit = trim($_POST['cultural_fit']);
        $overall_notes = trim($_POST['overall_notes']);
        $recommendation = $_POST['recommendation'];
        
        if ($rating < 1 || $rating > 5) {
            $error_message = "Rating must be between 1 and 5.";
        } else if (empty($recommendation)) {
            $error_message = "Please select a hiring recommendation.";
        } else {
            if (saveInterviewFeedback($interview_id, $rating, $strengths, $weaknesses, $technical_skills,
                                     $communication_skills, $cultural_fit, $overall_notes, $recommendation)) {
                $success_message = "Interview feedback saved successfully.";
                
                // Update interview status to completed
                if ($interview['status'] !== 'completed') {
                    updateInterviewStatus($interview_id, 'completed');
                    $interview['status'] = 'completed';
                }
            } else {
                $error_message = "Failed to save interview feedback.";
            }
        }
    }
}

// Get feedback if interview is completed
$feedback = null;
if ($interview['status'] === 'completed') {
    $feedback = getInterviewFeedback($interview_id);
}

// Format interview date
$interview_date = new DateTime($interview['interview_date']);
$end_time = clone $interview_date;
$end_time->add(new DateInterval('PT' . $interview['duration_minutes'] . 'M'));

$base_path = '../';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar navigation -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo ucfirst($user_type); ?> Navigation</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if ($user_type === 'employer'): ?>
                        <a href="../employer/index.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="../employer/manage-jobs.php" class="list-group-item list-group-item-action">Manage Jobs</a>
                        <a href="../employer/manage-applications.php" class="list-group-item list-group-item-action">Applications</a>
                        <a href="../employer/interviews.php" class="list-group-item list-group-item-action active">Interviews</a>
                        <a href="../employer/company-profile.php" class="list-group-item list-group-item-action">Company Profile</a>
                    <?php else: ?>
                        <a href="../user/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                        <a href="../user/profile.php" class="list-group-item list-group-item-action">My Profile</a>
                        <a href="../user/applications.php" class="list-group-item list-group-item-action">My Applications</a>
                        <a href="../user/interviews.php" class="list-group-item list-group-item-action active">Interviews</a>
                        <a href="../user/saved-jobs.php" class="list-group-item list-group-item-action">Saved Jobs</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h4 class="mb-0">Interview Details</h4>
                    <?php if ($user_type === 'employer'): ?>
                        <a href="../employer/interviews.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to Interviews
                        </a>
                    <?php else: ?>
                        <a href="../user/interviews.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to Interviews
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Job Information</h5>
                            <p><strong>Position:</strong> <?php echo htmlspecialchars($interview['job_title']); ?></p>
                            <p>
                                <strong>Company:</strong> 
                                <?php echo htmlspecialchars($interview['company_name'] ?: 'Company'); ?>
                            </p>
                            <?php if ($user_type === 'employer'): ?>
                                <p>
                                    <strong>Candidate:</strong> 
                                    <?php echo htmlspecialchars($interview['jobseeker_name'] ?: $interview['jobseeker_username']); ?>
                                </p>
                                <?php if (!empty($interview['resume_path'])): ?>
                                    <p>
                                        <a href="<?php echo '../' . $interview['resume_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-earmark-text"></i> View Resume
                                        </a>
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Interview Schedule</h5>
                                </div>
                                <div class="card-body">
                                    <p>
                                        <strong>Date & Time:</strong><br>
                                        <?php echo $interview_date->format('l, F j, Y'); ?><br>
                                        <?php echo $interview_date->format('g:i A'); ?> - <?php echo $end_time->format('g:i A'); ?>
                                    </p>
                                    <p>
                                        <strong>Duration:</strong> <?php echo $interview['duration_minutes']; ?> minutes
                                    </p>
                                    <p>
                                        <strong>Type:</strong>
                                        <?php if ($interview['interview_type'] === 'video'): ?>
                                            <span class="badge bg-primary"><i class="bi bi-camera-video"></i> Video Interview</span>
                                        <?php elseif ($interview['interview_type'] === 'phone'): ?>
                                            <span class="badge bg-info"><i class="bi bi-telephone"></i> Phone Interview</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-building"></i> In-Person Interview</span>
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($interview['interview_type'] === 'video' && !empty($interview['meeting_link'])): ?>
                                        <p>
                                            <strong>Meeting Link:</strong><br>
                                            <a href="<?php echo htmlspecialchars($interview['meeting_link']); ?>" target="_blank" class="btn btn-primary btn-sm mt-1">
                                                <i class="bi bi-box-arrow-up-right"></i> Join Meeting
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($interview['interview_type'] === 'in_person' && !empty($interview['location'])): ?>
                                        <p>
                                            <strong>Location:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($interview['location'])); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p>
                                        <strong>Status:</strong>
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
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional action buttons based on status -->
                    <?php if ($interview['status'] === 'scheduled' || $interview['status'] === 'rescheduled'): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5>Actions</h5>
                                        <div class="d-flex flex-wrap gap-2">
                                            <!-- Add to Calendar -->
                                            <a href="#" class="btn btn-outline-primary add-to-calendar" 
                                               data-title="Interview for <?php echo htmlspecialchars($interview['job_title']); ?>"
                                               data-start="<?php echo $interview_date->format('Y-m-d\TH:i:s'); ?>"
                                               data-end="<?php echo $end_time->format('Y-m-d\TH:i:s'); ?>"
                                               data-location="<?php echo htmlspecialchars($interview['location'] ?: $interview['meeting_link'] ?: ''); ?>">
                                                <i class="bi bi-calendar-plus"></i> Add to Calendar
                                            </a>
                                            
                                            <?php if (new DateTime() < $interview_date): ?>
                                                <!-- Request reschedule button -->
                                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#rescheduleModal">
                                                    <i class="bi bi-calendar-check"></i> Request Reschedule
                                                </button>
                                                
                                                <!-- Update status buttons for employer -->
                                                <?php if ($user_type === 'employer'): ?>
                                                    <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to mark this interview as cancelled?')">
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit" name="update_status" class="btn btn-outline-danger">
                                                            <i class="bi bi-x-circle"></i> Cancel Interview
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($user_type === 'employer' && new DateTime() > $interview_date): ?>
                                                <!-- Mark as completed or no-show (employer only) -->
                                                <form method="post" action="" class="d-inline">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" name="update_status" class="btn btn-outline-success">
                                                        <i class="bi bi-check-circle"></i> Mark as Completed
                                                    </button>
                                                </form>
                                                <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to mark this as no-show?')">
                                                    <input type="hidden" name="status" value="no_show">
                                                    <button type="submit" name="update_status" class="btn btn-outline-secondary">
                                                        <i class="bi bi-person-x"></i> Mark as No-Show
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Reschedule Requests Section -->
                    <?php if (!empty($reschedule_requests)): ?>
                        <div class="mb-4">
                            <h5>Reschedule Requests</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Requested By</th>
                                            <th>Proposed Date</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reschedule_requests as $request): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($request['proposed_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($request['reason']); ?></td>
                                                <td>
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php elseif ($request['status'] === 'accepted'): ?>
                                                        <span class="badge bg-success">Accepted</span>
                                                    <?php elseif ($request['status'] === 'rejected'): ?>
                                                        <span class="badge bg-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($request['status'] === 'pending' && $request['requested_by'] != $user_id): ?>
                                                        <form method="post" action="" class="d-inline">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                            <input type="hidden" name="response" value="accept">
                                                            <button type="submit" name="respond_to_reschedule" class="btn btn-sm btn-success">
                                                                <i class="bi bi-check-circle"></i> Accept
                                                            </button>
                                                        </form>
                                                        <form method="post" action="" class="d-inline">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                            <input type="hidden" name="response" value="reject">
                                                            <button type="submit" name="respond_to_reschedule" class="btn btn-sm btn-danger">
                                                                <i class="bi bi-x-circle"></i> Reject
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Interview Notes -->
                    <?php if (!empty($interview['employer_notes']) || !empty($interview['jobseeker_notes'])): ?>
                        <div class="mb-4">
                            <h5>Notes</h5>
                            
                            <?php if ($user_type === 'employer' && !empty($interview['employer_notes'])): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-light">Your Notes</div>
                                    <div class="card-body">
                                        <?php echo nl2br(htmlspecialchars($interview['employer_notes'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($user_type === 'jobseeker' && !empty($interview['jobseeker_notes'])): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-light">Your Notes</div>
                                    <div class="card-body">
                                        <?php echo nl2br(htmlspecialchars($interview['jobseeker_notes'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Interview Feedback Section (for employers on completed interviews) -->
                    <?php if ($interview['status'] === 'completed' && $user_type === 'employer'): ?>
                        <div class="mb-4">
                            <h5>Interview Feedback</h5>
                            
                            <?php if ($feedback): ?>
                                <!-- Show existing feedback -->
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Candidate Evaluation</span>
                                            <div>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $feedback['rating']): ?>
                                                        <i class="bi bi-star-fill text-warning"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-star text-muted"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <h6>Recommendation</h6>
                                            <?php if ($feedback['recommendation'] === 'strong_hire'): ?>
                                                <span class="badge bg-success">Strong Hire</span>
                                            <?php elseif ($feedback['recommendation'] === 'hire'): ?>
                                                <span class="badge bg-primary">Hire</span>
                                            <?php elseif ($feedback['recommendation'] === 'maybe'): ?>
                                                <span class="badge bg-warning">Maybe</span>
                                            <?php elseif ($feedback['recommendation'] === 'do_not_hire'): ?>
                                                <span class="badge bg-danger">Do Not Hire</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <h6>Strengths</h6>
                                                <p><?php echo nl2br(htmlspecialchars($feedback['strengths'] ?: 'None specified')); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Areas for Improvement</h6>
                                                <p><?php echo nl2br(htmlspecialchars($feedback['weaknesses'] ?: 'None specified')); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <h6>Technical Skills</h6>
                                                <p><?php echo nl2br(htmlspecialchars($feedback['technical_skills'] ?: 'None specified')); ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <h6>Communication Skills</h6>
                                                <p><?php echo nl2br(htmlspecialchars($feedback['communication_skills'] ?: 'None specified')); ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <h6>Cultural Fit</h6>
                                                <p><?php echo nl2br(htmlspecialchars($feedback['cultural_fit'] ?: 'None specified')); ?></p>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($feedback['overall_notes'])): ?>
                                            <div class="mb-3">
                                                <h6>Overall Notes</h6>
                                                <p><?php echo nl2br(htmlspecialchars($feedback['overall_notes'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-muted small">
                                            Feedback submitted on <?php echo date('F j, Y, g:i a', strtotime($feedback['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="card">
                                    <div class="card-body">
                                        <p>You haven't provided feedback for this interview yet.</p>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                                            <i class="bi bi-clipboard-check"></i> Submit Feedback
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reschedule Request Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="rescheduleModalLabel">Request Reschedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="proposed_date" class="form-label">Proposed Date</label>
                        <input type="date" class="form-control" id="proposed_date" name="proposed_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="proposed_time" class="form-label">Proposed Time</label>
                        <input type="time" class="form-control" id="proposed_time" name="proposed_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="reschedule_reason" class="form-label">Reason for Reschedule</label>
                        <textarea class="form-control" id="reschedule_reason" name="reschedule_reason" rows="3" required></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Your reschedule request will be sent to the other party for approval.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="request_reschedule" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Interview Feedback Modal -->
<?php if ($user_type === 'employer' && $interview['status'] === 'completed' && !$feedback): ?>
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalLabel">Interview Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rating" class="form-label">Overall Rating <span class="text-danger">*</span></label>
                        <div class="rating">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating1" value="1" required>
                                <label class="form-check-label" for="rating1">1</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating2" value="2">
                                <label class="form-check-label" for="rating2">2</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating3" value="3">
                                <label class="form-check-label" for="rating3">3</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating4" value="4">
                                <label class="form-check-label" for="rating4">4</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating5" value="5">
                                <label class="form-check-label" for="rating5">5</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="strengths" class="form-label">Strengths</label>
                            <textarea class="form-control" id="strengths" name="strengths" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="weaknesses" class="form-label">Areas for Improvement</label>
                            <textarea class="form-control" id="weaknesses" name="weaknesses" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="technical_skills" class="form-label">Technical Skills</label>
                            <textarea class="form-control" id="technical_skills" name="technical_skills" rows="3"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="communication_skills" class="form-label">Communication Skills</label>
                            <textarea class="form-control" id="communication_skills" name="communication_skills" rows="3"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="cultural_fit" class="form-label">Cultural Fit</label>
                            <textarea class="form-control" id="cultural_fit" name="cultural_fit" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="overall_notes" class="form-label">Overall Notes</label>
                        <textarea class="form-control" id="overall_notes" name="overall_notes" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="recommendation" class="form-label">Hiring Recommendation <span class="text-danger">*</span></label>
                        <select class="form-select" id="recommendation" name="recommendation" required>
                            <option value="">Select recommendation</option>
                            <option value="strong_hire">Strong Hire</option>
                            <option value="hire">Hire</option>
                            <option value="maybe">Maybe</option>
                            <option value="do_not_hire">Do Not Hire</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to tomorrow for reschedule requests
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    if (document.getElementById('proposed_date')) {
        document.getElementById('proposed_date').min = tomorrow.toISOString().split('T')[0];
    }
    
    // Add to Calendar functionality
    const addToCalendarButtons = document.querySelectorAll('.add-to-calendar');
    addToCalendarButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const title = this.getAttribute('data-title');
            const start = this.getAttribute('data-start');
            const end = this.getAttribute('data-end');
            const location = this.getAttribute('data-location') || '';
            
            // Format for Google Calendar
            const googleUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE' +
                '&text=' + encodeURIComponent(title) +
                '&dates=' + start.replace(/[-:]/g, '') + '/' + end.replace(/[-:]/g, '') +
                '&details=' + encodeURIComponent('Job interview scheduled through JobMatch') +
                '&location=' + encodeURIComponent(location);
            
            // Open in a new tab
            window.open(googleUrl, '_blank');
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>