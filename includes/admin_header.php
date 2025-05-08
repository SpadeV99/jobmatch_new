<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobMatch Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: #ced4da;
            padding: .75rem 1rem;
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,.1);
        }
        .sidebar .nav-link:hover {
            color: #fff;
        }
        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
            padding: 1rem;
            color: #6c757d;
        }
        .content {
            padding-top: 1.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $base_path; ?>admin/index.php">JobMatch Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_path; ?>index.php" target="_blank">View Site</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>admin/profile.php">My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-none d-md-block sidebar">
                <div class="position-sticky">
                    <div class="sidebar-heading d-flex justify-content-between align-items-center">
                        <span>Main Menu</span>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'admin-dashboard') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/index.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'manage-accounts') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/manage-accounts.php">
                                <i class="bi bi-people me-2"></i> Manage Accounts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'approve-jobs') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/approve-jobs.php">
                                <i class="bi bi-check-square me-2"></i> Approve Job Listings
                            </a>
                        </li>
                    </ul>
                    
                    <div class="sidebar-heading d-flex justify-content-between align-items-center">
                        <span>System</span>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'site-settings') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/site-settings.php">
                                <i class="bi bi-gear me-2"></i> Site Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page == 'reports') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>admin/reports.php">
                                <i class="bi bi-bar-chart me-2"></i> Reports
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <main class="col-md-10 ms-sm-auto px-4 content">