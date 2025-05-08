<?php
/**
 * Update application status and record in history
 */
function updateApplicationStatus($applicationId, $status, $notes, $userId, $notifyUser = true) {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update main application status
        $updateSql = "UPDATE job_applications SET status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $status, $applicationId);
        $updateStmt->execute();
        
        // Record in history table
        $historySql = "INSERT INTO application_status_history 
                      (application_id, status, notes, changed_by) 
                      VALUES (?, ?, ?, ?)";
        $historyStmt = $conn->prepare($historySql);
        $historyStmt->bind_param("issi", $applicationId, $status, $notes, $userId);
        $historyStmt->execute();
        
        // Additional actions for specific statuses
        if ($status == 'hired') {
            // Mark the job as filled
            $jobSql = "UPDATE jobs j 
                        JOIN job_applications a ON j.id = a.job_id 
                        SET j.status = 'filled' 
                        WHERE a.id = ?";
            $jobStmt = $conn->prepare($jobSql);
            $jobStmt->bind_param("i", $applicationId);
            $jobStmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Roll back on error
        $conn->rollback();
        return false;
    }
}

/**
 * Get application status history
 */
function getApplicationStatusHistory($applicationId) {
    global $conn;
    
    $sql = "SELECT ash.*, u.username as changed_by_name 
            FROM application_status_history ash
            LEFT JOIN users u ON ash.changed_by = u.id
            WHERE ash.application_id = ?
            ORDER BY ash.timestamp DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    return $history;
}
?>