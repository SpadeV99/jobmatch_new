<?php
/**
 * Admin functions for JobMatch platform
 */

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
    
    // Check if job_applications table has created_at column
    $check_column = $conn->query("SHOW COLUMNS FROM job_applications LIKE 'created_at'");
    if ($check_column->num_rows > 0) {
        // Applications with created_at
        $sql = "SELECT 
                    COUNT(*) as total_applications,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as applications_this_month
                FROM job_applications";
    } else {
        // Applications without created_at
        $sql = "SELECT 
                    COUNT(*) as total_applications,
                    0 as applications_this_month
                FROM job_applications";
    }
    
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
    
    // Check if status column exists
    $check_column = $conn->query("SHOW COLUMNS FROM jobs LIKE 'status'");
    
    if ($check_column->num_rows > 0) {
        // If status column exists, include it in the query
        $sql = "SELECT j.id, j.title, j.posted_date, j.status, ep.company_name 
                FROM jobs j
                LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                ORDER BY j.posted_date DESC LIMIT ?";
    } else {
        // If status column doesn't exist, don't include it
        $sql = "SELECT j.id, j.title, j.posted_date, 'unknown' as status, ep.company_name 
                FROM jobs j
                LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                ORDER BY j.posted_date DESC LIMIT ?";
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
 * Get jobs for admin panel with filtering options
 */
function getJobsForAdmin($filter_status = '', $search_term = '', $limit = 20, $offset = 0) {
    global $conn;
    
    // Check if jobs table has status column
    $check_status = $conn->query("SHOW COLUMNS FROM jobs LIKE 'status'");
    $has_status = $check_status->num_rows > 0;
    
    // Check if jobs table has is_featured column
    $check_featured = $conn->query("SHOW COLUMNS FROM jobs LIKE 'is_featured'");
    $has_featured = $check_featured->num_rows > 0;
    
    // Query to select jobs with proper columns
    $sql = "SELECT j.id, j.title, j.location, j.employer_id, j.posted_date, 
            ep.company_name";
    
    // Add status and is_featured conditionally if they exist
    if ($has_status) {
        $sql .= ", j.status";
    }
    
    if ($has_featured) {
        $sql .= ", j.is_featured";
    }
    
    $sql .= " FROM jobs j
              LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id";
    
    // Initialize arrays for where clauses and parameters
    $where_clauses = [];
    $params = [];
    $types = "";
    
    // Add status filter if column exists and filter is provided
    if ($has_status && !empty($filter_status)) {
        $where_clauses[] = "j.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    
    // Add search term if provided
    if (!empty($search_term)) {
        $search_param = "%" . $search_term . "%";
        $where_clauses[] = "(j.title LIKE ? OR ep.company_name LIKE ? OR j.location LIKE ?)";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    // Add WHERE clause if there are any filters
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Order by posted_date
    $sql .= " ORDER BY j.posted_date DESC LIMIT ? OFFSET ?";
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
        // Add default values for missing columns
        if (!isset($row['status'])) {
            $row['status'] = 'unknown';
        }
        if (!isset($row['is_featured'])) {
            $row['is_featured'] = 0;
        }
        if (!isset($row['created_at'])) {
            $row['created_at'] = $row['posted_date'];
        }
        $jobs[] = $row;
    }
    
    return $jobs;
}

/**
 * Count jobs for pagination with the same filters as getJobsForAdmin
 */
function countJobsForAdmin($filter_status = '', $search_term = '') {
    global $conn;
    
    // Start the query
    $sql = "SELECT COUNT(*) as total FROM jobs j LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id";
    
    // Initialize arrays for where clauses and parameters
    $where_clauses = [];
    $params = [];
    $types = "";
    
    // Check if the status column exists
    $check_status = $conn->query("SHOW COLUMNS FROM jobs LIKE 'status'");
    $has_status = $check_status->num_rows > 0;
    
    // Add status filter if column exists and filter is provided
    if ($has_status && !empty($filter_status)) {
        $where_clauses[] = "j.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    
    // Add search term if provided
    if (!empty($search_term)) {
        $search_param = "%" . $search_term . "%";
        $where_clauses[] = "(j.title LIKE ? OR ep.company_name LIKE ? OR j.location LIKE ?)";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    // Add WHERE clause if there are any filters
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
    
    return $row['total'] ?? 0;
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

/**
 * Delete job listing
 */
function deleteJob($job_id) {
    global $conn;
    // First delete any applications for this job
    $stmt = $conn->prepare("DELETE FROM job_applications WHERE job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    
    // Then delete the job
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ?");
    $stmt->bind_param("i", $job_id);
    return $stmt->execute();
}
?>