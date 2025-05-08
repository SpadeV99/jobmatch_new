<?php
/**
 * Notification system functions
 */

/**
 * Create a new notification
 *
 * @param int $user_id Recipient user ID
 * @param string $type Notification type (job, application, interview, message, assessment)
 * @param string $title Notification title
 * @param string $message Notification message
 * @param int|null $related_id ID of related entity (job, application, etc.)
 * @param string|null $related_type Type of related entity
 * @return int|bool Returns the notification ID if successful, false otherwise
 */
function createNotification($user_id, $type, $title, $message, $related_id = null, $related_type = null) {
    global $conn;
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $user_id, $title, $message, $type, $related_id, $related_type);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

/**
 * Get notifications for a user
 *
 * @param int $user_id User ID
 * @param int $limit Maximum number of notifications to retrieve
 * @param int $offset Pagination offset
 * @param bool $unread_only Whether to retrieve only unread notifications
 * @return array Array of notifications
 */
function getUserNotifications($user_id, $limit = 10, $offset = 0, $unread_only = false) {
    global $conn;
    
    $user_id = intval($user_id);
    $limit = intval($limit);
    $offset = intval($offset);
    
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    
    if ($unread_only) {
        $sql .= " AND is_read = 0";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

/**
 * Mark a notification as read
 *
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for security)
 * @return bool Success status
 */
function markNotificationAsRead($notification_id, $user_id) {
    global $conn;
    
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    
    return $stmt->execute() && $stmt->affected_rows > 0;
}

/**
 * Mark all notifications as read for a user
 *
 * @param int $user_id User ID
 * @return bool Success status
 */
function markAllNotificationsAsRead($user_id) {
    global $conn;
    
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    return $stmt->execute();
}

/**
 * Delete a notification
 *
 * @param int $notification_id Notification ID
 * @param int $user_id User ID (for security)
 * @return bool Success status
 */
function deleteNotification($notification_id, $user_id) {
    global $conn;
    
    $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    
    return $stmt->execute() && $stmt->affected_rows > 0;
}

/**
 * Delete all notifications for a user
 *
 * @param int $user_id User ID
 * @return bool Success status
 */
function deleteAllNotifications($user_id) {
    global $conn;
    
    $sql = "DELETE FROM notifications WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    return $stmt->execute();
}

/**
 * Get count of unread notifications for a user
 *
 * @param int $user_id User ID
 * @return int Count of unread notifications
 */
function getUnreadNotificationCount($user_id) {
    global $conn;
    
    $user_id = intval($user_id);
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Get total count of notifications for a user (for pagination)
 *
 * @param int $user_id User ID
 * @param bool $unread_only Whether to count only unread notifications
 * @return int Total notification count
 */
function getTotalNotificationsCount($user_id, $unread_only = false) {
    global $conn;
    
    $user_id = intval($user_id);
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ?";
    
    if ($unread_only) {
        $sql .= " AND is_read = 0";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Format a datetime string as "time ago" text
 *
 * @param string $datetime Datetime string
 * @param bool $full Whether to show full date or just most significant unit
 * @return string Formatted time ago string
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

/**
 * Add notification bell with dropdown to header
 *
 * @param int $user_id User ID
 * @return string HTML for notification bell
 */
function getNotificationBell($user_id) {
    $unread_count = getUnreadNotificationCount($user_id);
    $notifications = getUserNotifications($user_id, 5, 0, false);
    
    $html = '<div class="dropdown nav-item">';
    $html .= '<a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
    $html .= '<i class="bi bi-bell"></i>';
    
    if ($unread_count > 0) {
        $html .= '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' . $unread_count . '</span>';
    }
    
    $html .= '</a>';
    $html .= '<ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown" style="width: 300px; max-height: 400px; overflow-y: auto;">';
    
    if (count($notifications) > 0) {
        foreach ($notifications as $notification) {
            $html .= '<li>';
            $html .= '<a class="dropdown-item py-2 ' . ($notification['is_read'] ? '' : 'bg-light') . '" href="../notifications/index.php">';
            $html .= '<div class="d-flex w-100 justify-content-between">';
            $html .= '<h6 class="mb-1">' . htmlspecialchars($notification['title']) . '</h6>';
            $html .= '<small class="text-muted">' . time_elapsed_string($notification['created_at']) . '</small>';
            $html .= '</div>';
            $html .= '<p class="mb-1 small text-truncate">' . htmlspecialchars($notification['message']) . '</p>';
            $html .= '</a>';
            $html .= '</li>';
        }
        
        $html .= '<li><hr class="dropdown-divider"></li>';
        $html .= '<li><a class="dropdown-item text-center" href="../notifications/index.php">View all notifications</a></li>';
    } else {
        $html .= '<li><span class="dropdown-item text-center">No notifications</span></li>';
    }
    
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Notify about application status changes
 */
function notifyApplicationStatusChange($applicationId, $newStatus, $notes = '') {
    global $conn;
    
    // Get application details
    $sql = "SELECT ja.user_id as applicant_id, j.employer_id, j.title as job_title,
            u1.username as applicant_name, u2.username as employer_name
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            JOIN users u1 ON ja.user_id = u1.id
            JOIN users u2 ON j.employer_id = u2.id
            WHERE ja.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $application = $result->fetch_assoc();
    $statusText = getReadableStatus($newStatus);
    
    // Notify applicant
    $title = "Application Update: $statusText";
    $message = "Your application for '{$application['job_title']}' has been updated to $statusText.";
    if (!empty($notes)) {
        $message .= " Notes: $notes";
    }
    
    createNotification(
        $application['applicant_id'],
        'status_update',
        $title,
        $message,
        $applicationId,
        "../user/applications.php"
    );
    
    return true;
}

/**
 * Notify about new messages
 */
function notifyNewMessage($conversationId, $senderId, $message) {
    global $conn;
    
    // Get conversation details
    $sql = "SELECT 
            CASE 
                WHEN c.user1_id = ? THEN c.user2_id
                ELSE c.user1_id
            END as recipient_id,
            u.username as sender_name
            FROM conversations c
            JOIN users u ON u.id = ?
            WHERE c.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $senderId, $senderId, $conversationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $conversation = $result->fetch_assoc();
    
    // Create notification
    $title = "New message from {$conversation['sender_name']}";
    $shortMessage = strlen($message) > 100 ? substr($message, 0, 97) . '...' : $message;
    
    createNotification(
        $conversation['recipient_id'],
        'message',
        $title,
        $shortMessage,
        $conversationId,
        "../messages/conversation.php?id=$conversationId"
    );
    
    return true;
}

/**
 * Helper function to get readable status name
 */
function getReadableStatus($status) {
    $statusNames = [
        'pending' => 'Pending Review',
        'shortlisted' => 'Shortlisted',
        'interviewed' => 'Interviewed',
        'offered' => 'Job Offered',
        'rejected' => 'Not Selected',
        'withdrawn' => 'Withdrawn',
    ];
    
    return isset($statusNames[$status]) ? $statusNames[$status] : ucfirst($status);
}

/**
 * Get notification icon by type
 */
function getNotificationIcon($type) {
    switch ($type) {
        case 'application':
            return 'bi-file-earmark-text';
        case 'message':
            return 'bi-envelope';
        case 'interview':
            return 'bi-calendar-event';
        case 'offer':
            return 'bi-award';
        case 'status_update':
            return 'bi-arrow-clockwise';
        case 'profile':
            return 'bi-person';
        default:
            return 'bi-bell';
    }
}
?>