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
$active_page = 'manage-accounts';

// Handle user actions
if (isset($_POST['action'])) {
    $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if ($target_user_id > 0) {
        switch ($_POST['action']) {
            case 'activate':
                activateUser($target_user_id);
                $_SESSION['admin_message'] = "User activated successfully.";
                break;
            case 'deactivate':
                deactivateUser($target_user_id);
                $_SESSION['admin_message'] = "User deactivated successfully.";
                break;
            case 'delete':
                deleteUser($target_user_id);
                $_SESSION['admin_message'] = "User deleted successfully.";
                break;
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtering
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Get users with filtering and pagination
$users = getUsers($filter_type, $search_term, $limit, $offset);
$total_users = countUsers($filter_type, $search_term);
$total_pages = ceil($total_users / $limit);

include '../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Manage User Accounts</h2>
                <a href="add-user.php" class="btn btn-primary">Add New User</a>
            </div>
        </div>
    </div>
    
    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
                echo $_SESSION['admin_message'];
                unset($_SESSION['admin_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="type" class="form-select">
                        <option value="">All User Types</option>
                        <option value="jobseeker" <?php echo ($filter_type == 'jobseeker') ? 'selected' : ''; ?>>Job Seekers</option>
                        <option value="employer" <?php echo ($filter_type == 'employer') ? 'selected' : ''; ?>>Employers</option>
                        <option value="admin" <?php echo ($filter_type == 'admin') ? 'selected' : ''; ?>>Administrators</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search by username or email" value="<?php echo htmlspecialchars($search_term); ?>">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                    </div>
                </div>
                <div class="col-md-2">
                    <a href="manage-accounts.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><a href="user-details.php?id=<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></a></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['user_type'] == 'jobseeker'): ?>
                                        <span class="badge bg-info">Job Seeker</span>
                                    <?php elseif ($user['user_type'] == 'employer'): ?>
                                        <span class="badge bg-primary">Employer</span>
                                    <?php else: ?>
                                        <span class="badge bg-dark">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($user['status']) && $user['status'] == 'inactive'): ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="user-details.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-primary">View</a>
                                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if (isset($user['status']) && $user['status'] == 'inactive'): ?>
                                                <li>
                                                    <form method="post">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="activate">
                                                        <button type="submit" class="dropdown-item" onclick="return confirm('Are you sure you want to activate this user?')">
                                                            Activate User
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php else: ?>
                                                <li>
                                                    <form method="post">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="deactivate">
                                                        <button type="submit" class="dropdown-item" onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                            Deactivate User
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="post">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                        Delete User
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($users) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&type=<?php echo $filter_type; ?>&search=<?php echo urlencode($search_term); ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $filter_type; ?>&search=<?php echo urlencode($search_term); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&type=<?php echo $filter_type; ?>&search=<?php echo urlencode($search_term); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>