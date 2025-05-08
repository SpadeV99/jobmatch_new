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
$active_page = 'site-settings';

include '../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <h2>Site Settings</h2>
            <p class="text-muted">Manage your JobMatch platform settings</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> This feature is coming soon. You'll be able to configure site settings, email templates, and more.
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>General Settings</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">These settings are currently managed directly in the configuration files.</p>
                    <a href="#" class="btn btn-outline-primary disabled">Manage General Settings</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Email Templates</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Email template management will be available in a future update.</p>
                    <a href="#" class="btn btn-outline-primary disabled">Manage Email Templates</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>