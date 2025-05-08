<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/ahp_functions.php';

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
$success_message = '';
$error_message = '';

// Get current user preferences
$sql = "SELECT jp.* FROM jobseeker_profiles jp WHERE jp.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error_message = "Please complete your profile first.";
    header("Location: profile.php?error=complete_profile");
    exit();
}

$profile = $result->fetch_assoc();

// Get preference weights
$sql = "SELECT * FROM user_preference_weights WHERE user_id = ? ORDER BY criteria_order";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$weights = [];
while ($row = $result->fetch_assoc()) {
    $weights[$row['criteria_name']] = [
        'weight' => $row['weight'],
        'order' => $row['criteria_order']
    ];
}

// Default weights if user hasn't set them
if (empty($weights)) {
    $weights = [
        'skills' => ['weight' => 40, 'order' => 1],
        'location' => ['weight' => 20, 'order' => 2],
        'salary' => ['weight' => 20, 'order' => 3],
        'job_type' => ['weight' => 10, 'order' => 4],
        'experience' => ['weight' => 10, 'order' => 5]
    ];
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update job preferences
    $preferred_locations = isset($_POST['preferred_locations']) ? $_POST['preferred_locations'] : '';
    $preferred_job_types = isset($_POST['preferred_job_types']) ? implode(',', $_POST['preferred_job_types']) : '';
    $salary_expectation = isset($_POST['salary_expectation']) ? $_POST['salary_expectation'] : null;
    
    // Update profile preferences
    $sql = "UPDATE jobseeker_profiles SET 
        preferred_locations = ?, 
        preferred_job_type = ?, 
        salary_expectation = ? 
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssdi", $preferred_locations, $preferred_job_types, $salary_expectation, $user_id);
    
    if ($stmt->execute()) {
        // Process criteria weights
        // Check if weights table already has entries for this user
        $sql = "SELECT COUNT(*) as count FROM user_preference_weights WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            // Update existing weights
            foreach ($_POST['criteria'] as $name => $value) {
                $weight = intval($value);
                $order = intval($_POST['criteria_order'][$name]);
                
                $sql = "UPDATE user_preference_weights 
                        SET weight = ?, criteria_order = ? 
                        WHERE user_id = ? AND criteria_name = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("diis", $weight, $order, $user_id, $name);
                $stmt->execute();
            }
        } else {
            // Insert new weights
            foreach ($_POST['criteria'] as $name => $value) {
                $weight = intval($value);
                $order = intval($_POST['criteria_order'][$name]);
                
                $sql = "INSERT INTO user_preference_weights 
                        (user_id, criteria_name, weight, criteria_order) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isdi", $user_id, $name, $weight, $order);
                $stmt->execute();
            }
        }
        
        $success_message = "Preferences updated successfully!";
        
        // Refresh weights data
        $sql = "SELECT * FROM user_preference_weights WHERE user_id = ? ORDER BY criteria_order";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $weights = [];
        while ($row = $result->fetch_assoc()) {
            $weights[$row['criteria_name']] = [
                'weight' => $row['weight'],
                'order' => $row['criteria_order']
            ];
        }
        
        // Refresh profile data
        $sql = "SELECT * FROM jobseeker_profiles WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
    } else {
        $error_message = "Error updating preferences: " . $conn->error;
    }
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
                    <h5 class="mb-0">Navigation</h5>
                </div>
                <div class="list-group list-group-flush">
    <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo $active_page == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
    <a href="profile.php" class="list-group-item list-group-item-action <?php echo $active_page == 'profile' ? 'active' : ''; ?>">My Profile</a>
    <a href="applications.php" class="list-group-item list-group-item-action <?php echo $active_page == 'applications' ? 'active' : ''; ?>">Job Applications</a>
    <a href="interviews.php" class="list-group-item list-group-item-action <?php echo $active_page == 'interviews' ? 'active' : ''; ?>">Interviews</a>
    <a href="saved-jobs.php" class="list-group-item list-group-item-action <?php echo $active_page == 'saved' ? 'active' : ''; ?>">Saved Jobs</a>
    <a href="recommendations.php" class="list-group-item list-group-item-action <?php echo $active_page == 'recommendations' ? 'active' : ''; ?>">Recommendations</a>
    <a href="preferences.php" class="list-group-item list-group-item-action <?php echo $active_page == 'preferences' ? 'active' : ''; ?>">Job Preferences</a>
    <a href="../messages/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'messages' ? 'active' : ''; ?>">Messages</a>
    <a href="../notifications/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'notifications' ? 'active' : ''; ?>">Notifications</a>
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
            
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Job Matching Preferences</h4>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-4">
                            <h5>Job Criteria Priority</h5>
                            <p class="text-muted">Adjust the importance of each factor in your job matches. Total should equal 100%.</p>
                            
                            <div class="row">
                                <div class="col-md-8 offset-md-2">
                                    <div class="mb-3">
                                        <label for="criteria-skills" class="form-label">Skills Match</label>
                                        <div class="input-group">
                                            <input type="range" class="form-range" min="5" max="70" value="<?php echo $weights['skills']['weight']; ?>" id="criteria-skills" name="criteria[skills]">
                                            <span class="ms-2 weight-display"><?php echo $weights['skills']['weight']; ?>%</span>
                                            <input type="hidden" name="criteria_order[skills]" value="1">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="criteria-location" class="form-label">Location</label>
                                        <div class="input-group">
                                            <input type="range" class="form-range" min="5" max="70" value="<?php echo $weights['location']['weight']; ?>" id="criteria-location" name="criteria[location]">
                                            <span class="ms-2 weight-display"><?php echo $weights['location']['weight']; ?>%</span>
                                            <input type="hidden" name="criteria_order[location]" value="2">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="criteria-salary" class="form-label">Salary</label>
                                        <div class="input-group">
                                            <input type="range" class="form-range" min="5" max="70" value="<?php echo $weights['salary']['weight']; ?>" id="criteria-salary" name="criteria[salary]">
                                            <span class="ms-2 weight-display"><?php echo $weights['salary']['weight']; ?>%</span>
                                            <input type="hidden" name="criteria_order[salary]" value="3">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="criteria-job_type" class="form-label">Job Type</label>
                                        <div class="input-group">
                                            <input type="range" class="form-range" min="5" max="70" value="<?php echo $weights['job_type']['weight']; ?>" id="criteria-job_type" name="criteria[job_type]">
                                            <span class="ms-2 weight-display"><?php echo $weights['job_type']['weight']; ?>%</span>
                                            <input type="hidden" name="criteria_order[job_type]" value="4">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="criteria-experience" class="form-label">Experience</label>
                                        <div class="input-group">
                                            <input type="range" class="form-range" min="5" max="70" value="<?php echo $weights['experience']['weight']; ?>" id="criteria-experience" name="criteria[experience]">
                                            <span class="ms-2 weight-display"><?php echo $weights['experience']['weight']; ?>%</span>
                                            <input type="hidden" name="criteria_order[experience]" value="5">
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info mt-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Total weight:</span>
                                            <span id="total-weight"><?php echo array_sum(array_column($weights, 'weight')); ?>%</span>
                                        </div>
                                        <div id="weight-warning" class="text-danger" style="display: none;">
                                            Total must equal 100%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5>Job Preferences</h5>
                            
                            <div class="mb-3">
                                <label for="preferred_locations" class="form-label">Preferred Locations</label>
                                <input type="text" class="form-control" id="preferred_locations" name="preferred_locations" placeholder="e.g., New York, Remote, San Francisco" value="<?php echo htmlspecialchars($profile['preferred_locations'] ?? ''); ?>">
                                <div class="form-text">Separate multiple locations with commas</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Preferred Job Types</label>
                                <div class="form-check">
    <input class="form-check-input" type="checkbox" name="preferred_job_types[]" value="full-time" id="job-type-fulltime"
        <?php echo (strpos($profile['preferred_job_type'] ?? '', 'full-time') !== false) ? 'checked' : ''; ?>>
    <label class="form-check-label" for="job-type-fulltime">Full Time</label>
</div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="preferred_job_types[]" value="part-time" id="job-type-parttime"
                                        <?php echo (strpos($profile['preferred_job_type'] ?? '', 'part-time') !== false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="job-type-parttime">Part Time</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="preferred_job_types[]" value="contract" id="job-type-contract"
                                        <?php echo (strpos($profile['preferred_job_type'] ?? '', 'contract') !== false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="job-type-contract">Contract</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="preferred_job_types[]" value="internship" id="job-type-internship"
                                        <?php echo (strpos($profile['preferred_job_type'] ?? '', 'internship') !== false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="job-type-internship">Internship</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="preferred_job_types[]" value="remote" id="job-type-remote"
                                        <?php echo (strpos($profile['preferred_job_type'] ?? '', 'remote') !== false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="job-type-remote">Remote</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="salary_expectation" class="form-label">Salary Expectation (Annual)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="salary_expectation" name="salary_expectation" placeholder="e.g. 70000" value="<?php echo htmlspecialchars($profile['salary_expectation'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary" id="submit-btn">Save Preferences</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show slider values
    const sliders = document.querySelectorAll('.form-range');
    const totalWeightDisplay = document.getElementById('total-weight');
    const weightWarning = document.getElementById('weight-warning');
    const submitBtn = document.getElementById('submit-btn');
    
    function updateTotal() {
        let total = 0;
        sliders.forEach(slider => {
            total += parseInt(slider.value);
        });
        
        totalWeightDisplay.textContent = total + '%';
        
        if (total !== 100) {
            weightWarning.style.display = 'block';
            submitBtn.disabled = true;
        } else {
            weightWarning.style.display = 'none';
            submitBtn.disabled = false;
        }
    }
    
    sliders.forEach(slider => {
        const output = slider.nextElementSibling;
        
        slider.oninput = function() {
            output.textContent = this.value + '%';
            updateTotal();
        };
    });
    
    // Run once on page load
    updateTotal();
});
</script>

<?php include '../includes/footer.php'; ?>