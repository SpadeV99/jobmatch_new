
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobMatch - Find Your Perfect Job</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="<?php echo $base_path ?? ''; ?>assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <?php
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo isset($base_path) ? $base_path : ''; ?>index.php">
                <img src="<?php echo isset($base_path) ? $base_path : ''; ?>img/logo.jpg" alt="JobMatch Logo" height="30" class="me-2">
                JobMatch
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($base_path) ? $base_path : ''; ?>index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($base_path) ? $base_path : ''; ?>jobs/index.php">Browse Jobs</a>
                    </li>
                    
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'employer'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($base_path) ? $base_path : ''; ?>employer/post-job.php">Post a Job</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <?php if ($_SESSION['user_type'] === 'jobseeker'): ?>
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>user/dashboard.php">My Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>user/profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>user/applications.php">My Applications</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>user/saved-jobs.php">Saved Jobs</a></li>
                            <?php elseif ($_SESSION['user_type'] === 'employer'): ?>
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>employer/dashboard.php">Employer Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>employer/manage-jobs.php">Manage Jobs</a></li>
                            <?php elseif ($_SESSION['user_type'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>admin/index.php">Admin Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>admin/jobs.php">Manage Jobs</a></li>
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>admin/users.php">Manage Users</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo isset($base_path) ? $base_path : ''; ?>logout.php">Logout</a></li>
                        </ul>
                    </li>
                    
                    <!-- REMOVED: Old notification bell -->
                    
                    <!-- Messages icon -->
                    <li class="nav-item mx-1">
                        <a class="nav-link position-relative" href="<?php echo $base_path; ?>messages/index.php">
                            <i class="bi bi-envelope"></i>
                            <?php if (function_exists('getUnreadMessageCount') && getUnreadMessageCount($_SESSION['user_id']) > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo getUnreadMessageCount($_SESSION['user_id']); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- Notification bell with dropdown -->
                    <?php if (function_exists('getNotificationBell')): ?>
                        <?php echo getNotificationBell($_SESSION['user_id']); ?>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($base_path) ? $base_path : ''; ?>login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($base_path) ? $base_path : ''; ?>register.php">Register</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">