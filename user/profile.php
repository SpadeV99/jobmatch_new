<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user profile data
$profile_query = "SELECT u.*, jp.first_name, jp.last_name, jp.phone, jp.address, jp.city, 
                 jp.state, jp.zip_code, jp.country, jp.resume_path, jp.skills, jp.experience,
                 jp.education, jp.preferred_location, jp.expected_salary
                 FROM users u
                 LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                 WHERE u.id = ?";
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $country = trim($_POST['country']);
    $zip_code = trim($_POST['zip_code']);
    $skills = trim($_POST['skills']);
    $experience = trim($_POST['experience']);
    $education = trim($_POST['education']);
    $preferred_location = trim($_POST['preferred_location']);
    $expected_salary = trim($_POST['expected_salary']);
    
    // Handle resume upload
    $resume_path = isset($user['resume_path']) ? $user['resume_path'] : '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === 0) {
        $upload_dir = '../uploads/resumes/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $user_id . '_' . time() . '_' . $_FILES['resume']['name'];
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $file_path)) {
            $resume_path = $file_path;
        } else {
            $error_message = "Failed to upload resume.";
        }
    }
    
    // Check if profile exists
    $check_profile = "SELECT user_id FROM jobseeker_profiles WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_profile);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $profile_exists = $check_stmt->get_result()->num_rows > 0;
    
    if ($profile_exists) {
        // Update existing profile
        $update_sql = "UPDATE jobseeker_profiles SET 
                      first_name = ?, last_name = ?, phone = ?, address = ?, city = ?,
                      state = ?, zip_code = ?, country = ?, resume_path = ?, skills = ?,
                      experience = ?, education = ?, preferred_location = ?, expected_salary = ?
                      WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssssssssssssi", 
                               $first_name, $last_name, $phone, $address, $city,
                               $state, $zip_code, $country, $resume_path, $skills,
                               $experience, $education, $preferred_location, $expected_salary, $user_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Error updating profile: " . $conn->error;
        }
    } else {
        // Insert new profile
        $insert_sql = "INSERT INTO jobseeker_profiles
                      (user_id, first_name, last_name, phone, address, city, state, 
                       zip_code, country, resume_path, skills, experience, education, 
                       preferred_location, expected_salary)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("issssssssssssss", 
                               $user_id, $first_name, $last_name, $phone, $address, $city,
                               $state, $zip_code, $country, $resume_path, $skills,
                               $experience, $education, $preferred_location, $expected_salary);
        
        if ($insert_stmt->execute()) {
            $success_message = "Profile created successfully!";
        } else {
            $error_message = "Error creating profile: " . $conn->error;
        }
    }
    
    // Refresh user data
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}

$base_path = '../';
include '../includes/header.php';
?>

<div class="row">
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
    
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">My Profile</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form action="" method="post" enctype="multipart/form-data">
                    <h5 class="mb-3">Personal Information</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo isset($user['first_name']) ? htmlspecialchars($user['first_name']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo isset($user['last_name']) ? htmlspecialchars($user['last_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>">
                    </div>
                    
                    <h5 class="mb-3 mt-4">Address</h5>
                    <div class="mb-3">
                        <label for="address" class="form-label">Street Address</label>
                        <input type="text" class="form-control" id="address" name="address" 
                               value="<?php echo isset($user['address']) ? htmlspecialchars($user['address']) : ''; ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?php echo isset($user['city']) ? htmlspecialchars($user['city']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="state" class="form-label">State/Province</label>
                            <input type="text" class="form-control" id="state" name="state" 
                                   value="<?php echo isset($user['state']) ? htmlspecialchars($user['state']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                   value="<?php echo isset($user['zip_code']) ? htmlspecialchars($user['zip_code']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" 
                               value="<?php echo isset($user['country']) ? htmlspecialchars($user['country']) : ''; ?>">
                    </div>
                    
                    <h5 class="mb-3 mt-4">Professional Information</h5>
                    <div class="mb-3">
                        <label for="skills" class="form-label">Skills (separate with commas)</label>
                        <textarea class="form-control" id="skills" name="skills" rows="3"><?php echo isset($user['skills']) ? htmlspecialchars($user['skills']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="experience" class="form-label">Work Experience</label>
                        <textarea class="form-control" id="experience" name="experience" rows="4"><?php echo isset($user['experience']) ? htmlspecialchars($user['experience']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="education" class="form-label">Education</label>
                        <textarea class="form-control" id="education" name="education" rows="3"><?php echo isset($user['education']) ? htmlspecialchars($user['education']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resume" class="form-label">Resume/CV (PDF)</label>
                        <input type="file" class="form-control" id="resume" name="resume" accept=".pdf,.doc,.docx">
                        <?php if (isset($user['resume_path']) && !empty($user['resume_path'])): ?>
                        <div class="mt-2">
                            <span class="text-success">Resume uploaded</span>
                            <a href="<?php echo $user['resume_path']; ?>" class="ms-2" target="_blank">View current resume</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <h5 class="mb-3 mt-4">Job Preferences</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="preferred_location" class="form-label">Preferred Location</label>
                            <input type="text" class="form-control" id="preferred_location" name="preferred_location" 
                                   value="<?php echo isset($user['preferred_location']) ? htmlspecialchars($user['preferred_location']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="expected_salary" class="form-label">Expected Salary</label>
                            <input type="text" class="form-control" id="expected_salary" name="expected_salary" 
                                   value="<?php echo isset($user['expected_salary']) ? htmlspecialchars($user['expected_salary']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>