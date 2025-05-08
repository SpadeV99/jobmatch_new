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
$success_message = '';
$error_message = '';

// Get employer profile data
$profile_query = "SELECT u.*, ep.company_name, ep.company_description, ep.industry, 
                 ep.website, ep.phone, ep.address, ep.city, ep.state, 
                 ep.zip_code, ep.country, ep.logo_path
                 FROM users u
                 LEFT JOIN employer_profiles ep ON u.id = ep.user_id
                 WHERE u.id = ?";
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employer = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $company_name = trim($_POST['company_name']);
    $company_description = trim($_POST['company_description']);
    $industry = trim($_POST['industry']);
    $website = trim($_POST['website']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    $country = trim($_POST['country']);
    
    // Handle logo upload
    $logo_path = isset($employer['logo_path']) ? $employer['logo_path'] : '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $upload_dir = '../uploads/logos/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $user_id . '_' . time() . '_' . $_FILES['logo']['name'];
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $file_path)) {
            $logo_path = $file_path;
        } else {
            $error_message = "Failed to upload company logo.";
        }
    }
    
    // Check if profile exists
    $check_profile = "SELECT user_id FROM employer_profiles WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_profile);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $profile_exists = $check_stmt->get_result()->num_rows > 0;
    
    if ($profile_exists) {
        // Update existing profile
        $update_sql = "UPDATE employer_profiles SET 
                      company_name = ?, company_description = ?, industry = ?,
                      website = ?, phone = ?, address = ?, city = ?,
                      state = ?, zip_code = ?, country = ?, logo_path = ?
                      WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssssssssssi", 
                                $company_name, $company_description, $industry,
                                $website, $phone, $address, $city,
                                $state, $zip_code, $country, $logo_path, $user_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Company profile updated successfully!";
        } else {
            $error_message = "Error updating profile: " . $conn->error;
        }
    } else {
        // Insert new profile
        $insert_sql = "INSERT INTO employer_profiles
                      (user_id, company_name, company_description, industry,
                       website, phone, address, city, state, zip_code, country, logo_path)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isssssssssss", 
                                $user_id, $company_name, $company_description, $industry,
                                $website, $phone, $address, $city, $state, $zip_code, $country, $logo_path);
        
        if ($insert_stmt->execute()) {
            $success_message = "Company profile created successfully!";
        } else {
            $error_message = "Error creating profile: " . $conn->error;
        }
    }
    
    // Refresh employer data
    $stmt->execute();
    $result = $stmt->get_result();
    $employer = $result->fetch_assoc();
}

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
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Company Profile</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Company Name</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" required 
                               value="<?php echo isset($employer['company_name']) ? htmlspecialchars($employer['company_name']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="industry" class="form-label">Industry</label>
                        <input type="text" class="form-control" id="industry" name="industry" 
                               value="<?php echo isset($employer['industry']) ? htmlspecialchars($employer['industry']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="company_description" class="form-label">Company Description</label>
                        <textarea class="form-control" id="company_description" name="company_description" rows="4"><?php echo isset($employer['company_description']) ? htmlspecialchars($employer['company_description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="website" class="form-label">Website</label>
                        <input type="url" class="form-control" id="website" name="website" 
                               value="<?php echo isset($employer['website']) ? htmlspecialchars($employer['website']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo isset($employer['phone']) ? htmlspecialchars($employer['phone']) : ''; ?>">
                    </div>
                    
                    <h5 class="mb-3 mt-4">Address</h5>
                    <div class="mb-3">
                        <label for="address" class="form-label">Street Address</label>
                        <input type="text" class="form-control" id="address" name="address" 
                               value="<?php echo isset($employer['address']) ? htmlspecialchars($employer['address']) : ''; ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?php echo isset($employer['city']) ? htmlspecialchars($employer['city']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="state" class="form-label">State/Province</label>
                            <input type="text" class="form-control" id="state" name="state" 
                                   value="<?php echo isset($employer['state']) ? htmlspecialchars($employer['state']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                            <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                   value="<?php echo isset($employer['zip_code']) ? htmlspecialchars($employer['zip_code']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" 
                               value="<?php echo isset($employer['country']) ? htmlspecialchars($employer['country']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="logo" class="form-label">Company Logo</label>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        <?php if (isset($employer['logo_path']) && !empty($employer['logo_path'])): ?>
                        <div class="mt-2">
                            <img src="<?php echo $employer['logo_path']; ?>" alt="Company Logo" class="img-thumbnail" style="max-width: 200px;">
                        </div>
                        <?php endif; ?>
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