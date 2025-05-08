<?php
require_once '../config/db_connect.php';

// Set a secret key to protect this page
define('ADMIN_CREATION_KEY', 'jobmatch2025');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$message_type = '';

// Check if an admin already exists
$stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE user_type = 'admin'");
$stmt->execute();
$result = $stmt->get_result();
$admin_count = $result->fetch_assoc()['admin_count'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify the secret key
    if (!isset($_POST['secret_key']) || $_POST['secret_key'] !== ADMIN_CREATION_KEY) {
        $message = "Invalid secret key. Access denied.";
        $message_type = "danger";
    } 
    // Check if we already have admins and verify the override checkbox
    elseif ($admin_count > 0 && (!isset($_POST['override']) || $_POST['override'] !== 'yes')) {
        $message = "An administrator account already exists. Check the override box if you want to create another one.";
        $message_type = "warning";
    } 
    // Process the form
    else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            $message = "All fields are required.";
            $message_type = "danger";
        } elseif ($password !== $confirm_password) {
            $message = "Passwords do not match.";
            $message_type = "danger";
        } else {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = "Username or email already exists.";
                $message_type = "danger";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert the new admin
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, 'admin')");
                $stmt->bind_param("sss", $username, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    $message = "Administrator account created successfully! ID: " . $conn->insert_id;
                    $message_type = "success";
                } else {
                    $message = "Error creating administrator: " . $conn->error;
                    $message_type = "danger";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create JobMatch Administrator</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .security-notice {
            background-color: #fff3cd;
            padding: 10px;
            border-left: 5px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="text-center mb-4">
                <h2>Create JobMatch Administrator</h2>
                <p class="text-muted">Set up your admin account</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="security-notice mb-4">
                <p class="mb-0"><strong>Security Notice:</strong> Delete this file immediately after creating your administrator account.</p>
            </div>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="secret_key" class="form-label">Secret Key</label>
                    <input type="password" class="form-control" id="secret_key" name="secret_key" required>
                    <div class="form-text">Enter the secret key defined in this script.</div>
                </div>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <?php if ($admin_count > 0): ?>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="override" name="override" value="yes">
                    <label class="form-check-label" for="override">
                        I understand that an admin account already exists (<?php echo $admin_count; ?> total), but I want to create another one.
                    </label>
                </div>
                <?php endif; ?>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Create Administrator</button>
                    <a href="../index.php" class="btn btn-outline-secondary">Return to Home</a>
                </div>
            </form>
            
            <div class="mt-4 text-center">
                <p class="text-danger">
                    <strong>Important:</strong> Delete this file after creating your admin account!
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>