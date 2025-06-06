<?php
require_once '../config/db_connect.php';

/**
 * Admin functions for JobMatch platform
 */

echo "<h2>Adding status column to jobs table</h2>";

// Check if the column already exists
$result = $conn->query("SHOW COLUMNS FROM jobs LIKE 'status'");
if ($result->num_rows == 0) {
    // Add the status column
    $sql = "ALTER TABLE jobs ADD COLUMN status ENUM('pending', 'active', 'rejected', 'expired', 'filled') NOT NULL DEFAULT 'pending'";
    if ($conn->query($sql)) {
        echo "<p>✅ Status column added successfully to jobs table</p>";
    } else {
        echo "<p>❌ Error adding status column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ Status column already exists in jobs table</p>";
}

// Make existing jobs active by default
$sql = "UPDATE jobs SET status = 'active' WHERE status = 'pending'";
if ($conn->query($sql)) {
    echo "<p>✅ Existing jobs marked as active</p>";
} else {
    echo "<p>❌ Error updating job statuses: " . $conn->error . "</p>";
}

// Add is_featured column if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM jobs LIKE 'is_featured'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE jobs ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0";
    if ($conn->query($sql)) {
        echo "<p>✅ is_featured column added successfully to jobs table</p>";
    } else {
        echo "<p>❌ Error adding is_featured column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ is_featured column already exists in jobs table</p>";
}

echo "<p><a href='index.php' class='btn btn-primary'>Return to Admin Dashboard</a></p>";

/**
 * Get statistics for admin dashboard
 */
function getAdminDashboardStats() {
    global $conn;
    $stats = [];

    // Total users
    $sql = "SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN user_type = 'jobseeker' THEN 1 END) as jobseekers,
                COUNT(CASE WHEN user_type = 'employer' THEN 1 END) as employers,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_this_month,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_this_week
            FROM users";
    $result = $conn->query($sql);
    if ($result) {
        $user_stats = $result->fetch_assoc();
        $stats = array_merge($stats, $user_stats);
    } else {
        $stats['total_users'] = 0;
        $stats['jobseekers'] = 0;
        $stats['employers'] = 0;
        $stats['new_users_this_month'] = 0;
        $stats['new_users_this_week'] = 0;
    }

    // Check if jobs table has a 'status' column
    $check_column = $conn->query("SHOW COLUMNS FROM jobs LIKE 'status'");
    if ($check_column->num_rows > 0) {
        // If status column exists, use it
        $sql = "SELECT 
                    COUNT(*) as total_jobs,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_jobs,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_jobs
                FROM jobs";
    } else {
        // Otherwise just count total jobs and set pending/active to 0
        $sql = "SELECT 
                    COUNT(*) as total_jobs,
                    0 as pending_jobs,
                    0 as active_jobs
                FROM jobs";
    }
    $result = $conn->query($sql);
    if ($result) {
        $job_stats = $result->fetch_assoc();
        $stats = array_merge($stats, $job_stats);
    } else {
        $stats['total_jobs'] = 0;
        $stats['pending_jobs'] = 0;
        $stats['active_jobs'] = 0;
    }

    // Applications
    $sql = "SELECT 
                COUNT(*) as total_applications,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as applications_this_month
            FROM job_applications";
    $result = $conn->query($sql);
    if ($result) {
        $application_stats = $result->fetch_assoc();
        $stats = array_merge($stats, $application_stats);
    } else {
        $stats['total_applications'] = 0;
        $stats['applications_this_month'] = 0;
    }

    return $stats;
}

/**
 * Get recent job listings
 */
function getRecentJobs($limit = 5) {
    global $conn;

    // Check if jobs table has a 'status' column
    $check_column = $conn->query("SHOW COLUMNS FROM jobs LIKE 'status'");
    if ($check_column->num_rows > 0) {
        // If status column exists, include it
        $sql = "SELECT j.*, ep.company_name 
                FROM jobs j
                LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                ORDER BY j.created_at DESC LIMIT ?";
    } else {
        // Otherwise exclude it
        $sql = "SELECT j.id, j.title, j.created_at, ep.company_name, 'unknown' as status
                FROM jobs j
                LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                ORDER BY j.created_at DESC LIMIT ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }

    return $jobs;
}

/**
 * Get recent users
 */
function getRecentUsers($limit = 5) {
    global $conn;

    $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    return $users;
}

/**
 * Get users with filtering and pagination
 */
function getUsers($filter_type = '', $search_term = '', $limit = 20, $offset = 0) {
    global $conn;

    $sql = "SELECT u.*, 
            CASE WHEN EXISTS (
                SELECT 1 FROM user_status us WHERE us.user_id = u.id AND us.status = 'inactive'
            ) THEN 'inactive' ELSE 'active' END as status
            FROM users u";
    
    $params = [];
    $types = "";
    
    $where_clauses = [];
    
    if (!empty($filter_type)) {
        $where_clauses[] = "u.user_type = ?";
        $params[] = $filter_type;
        $types .= "s";
    }
    
    if (!empty($search_term)) {
        $search_param = "%" . $search_term . "%";
        $where_clauses[] = "(u.username LIKE ? OR u.email LIKE ?)";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $sql .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    return $users;
}

/**
 * Count users for pagination
 */
function countUsers($filter_type = '', $search_term = '') {
    global $conn;

    $sql = "SELECT COUNT(*) as total FROM users";
    
    $params = [];
    $types = "";
    
    $where_clauses = [];
    
    if (!empty($filter_type)) {
        $where_clauses[] = "user_type = ?";
        $params[] = $filter_type;
        $types .= "s";
    }
    
    if (!empty($search_term)) {
        $search_param = "%" . $search_term . "%";
        $where_clauses[] = "(username LIKE ? OR email LIKE ?)";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['total'];
}

/**
 * Activate user account
 */
function activateUser($user_id) {
    global $conn;

    // Delete inactive status entry if exists
    $sql = "DELETE FROM user_status WHERE user_id = ? AND status = 'inactive'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    return $stmt->execute();
}

/**
 * Deactivate user account
 */
function deactivateUser($user_id) {
    global $conn;

    // Check if status record exists
    $sql = "SELECT * FROM user_status WHERE user_id = ? AND status = 'inactive'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Create new status record
        $sql = "INSERT INTO user_status (user_id, status) VALUES (?, 'inactive')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }

    return true;
}

/**
 * Delete user account
 */
function deleteUser($user_id) {
    global $conn;

    // Check if user exists and is not the current user
    if ($user_id == $_SESSION['user_id']) {
        return false;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete user
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

/**
 * Get jobs for admin with filtering and pagination
 */
function getJobsForAdmin($filter_status = '', $search_term = '', $limit = 20, $offset = 0) {
    global $conn;

    $sql = "SELECT j.*, ep.company_name 
            FROM jobs j
            LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id";
    
    $params = [];
    $types = "";
    
    $where_clauses = [];
    
    if (!empty($filter_status)) {
        $where_clauses[] = "j.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    
    if (!empty($search_term)) {
        $search_param = "%" . $search_term . "%";
        $where_clauses[] = "(j.title LIKE ? OR ep.company_name LIKE ? OR j.location LIKE ?)";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $sql .= " ORDER BY j.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }

    return $jobs;
}

/**
 * Count jobs for pagination
 */
function countJobsForAdmin($filter_status = '', $search_term = '') {
    global $conn;

    $sql = "SELECT COUNT(*) as total 
            FROM jobs j
            LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id";
    
    $params = [];
    $types = "";
    
    $where_clauses = [];
    
    if (!empty($filter_status)) {
        $where_clauses[] = "j.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    
    if (!empty($search_term)) {
        $search_param = "%" . $search_term . "%";
        $where_clauses[] = "(j.title LIKE ? OR ep.company_name LIKE ? OR j.location LIKE ?)";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['total'];
}

/**
 * Approve job listing
 */
function approveJob($job_id) {
    global $conn;

    $sql = "UPDATE jobs SET status = 'active' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);

    if ($stmt->execute()) {
        // Get employer ID for notification
        $sql = "SELECT employer_id, title FROM jobs WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $job = $result->fetch_assoc();
        
        // Create notification for employer
        if (function_exists('createNotification')) {
            createNotification(
                $job['employer_id'],
                'job_approval',
                'Job Listing Approved',
                'Your job listing "' . $job['title'] . '" has been reviewed and approved.',
                $job_id,
                '../employer/job-details.php?id=' . $job_id
            );
        }
        
        return true;
    }

    return false;
}

/**
 * Reject job listing
 */
function rejectJob($job_id, $reason = '') {
    global $conn;

    $conn->begin_transaction();

    try {
        // Update job status
        $sql = "UPDATE jobs SET status = 'rejected' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        
        // Store rejection reason
        if (!empty($reason)) {
            $sql = "INSERT INTO job_rejection_reasons (job_id, reason) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE reason = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $job_id, $reason, $reason);
            $stmt->execute();
        }
        
        // Get employer ID for notification
        $sql = "SELECT employer_id, title FROM jobs WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $job = $result->fetch_assoc();
        
        // Create notification for employer
        if (function_exists('createNotification')) {
            $message = 'Your job listing "' . $job['title'] . '" has been reviewed and rejected.';
            if (!empty($reason)) {
                $message .= ' Reason: ' . $reason;
            }
            
            createNotification(
                $job['employer_id'],
                'job_rejection',
                'Job Listing Rejected',
                $message,
                $job_id,
                '../employer/job-details.php?id=' . $job_id
            );
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

/**
 * Mark job as featured
 */
function featureJob($job_id) {
    global $conn;

    $sql = "UPDATE jobs SET is_featured = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);

    if ($stmt->execute()) {
        // Get employer ID for notification
        $sql = "SELECT employer_id, title FROM jobs WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $job = $result->fetch_assoc();
        
        // Create notification for employer
        if (function_exists('createNotification')) {
            createNotification(
                $job['employer_id'],
                'job_featured',
                'Job Listing Featured',
                'Congratulations! Your job listing "' . $job['title'] . '" has been featured and will receive extra visibility.',
                $job_id,
                '../employer/job-details.php?id=' . $job_id
            );
        }
        
        return true;
    }

    return false;
}

/**
 * Remove job from featured listings
 */
function unfeatureJob($job_id) {
    global $conn;

    $sql = "UPDATE jobs SET is_featured = 0 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);

    return $stmt->execute();
}
?>