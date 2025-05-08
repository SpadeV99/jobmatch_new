<?php
// Include this file in your header.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/jobmatch_new/includes/notification_functions.php';

if (isset($_SESSION['user_id'])) {
    $unread_count = getUnreadNotificationCount($_SESSION['user_id']);
    $unread_messages = 0; 
    
    // If messaging_functions.php is included and the function exists
    if (function_exists('getUnreadMessageCount')) {
        $unread_messages = getUnreadMessageCount($_SESSION['user_id']);
    }
    
    // Total unread notifications
    $total_unread = $unread_count + $unread_messages;
}
?>

<!-- Add to navigation items in header.php -->
<?php if (isset($_SESSION['user_id'])): ?>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bell"></i>
            <?php if (isset($total_unread) && $total_unread > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $total_unread; ?>
                    <span class="visually-hidden">unread notifications</span>
                </span>
            <?php endif; ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
            <li><h6 class="dropdown-header">Notifications</h6></li>
            <?php if (isset($unread_count) && $unread_count > 0): ?>
                <li>
                    <a class="dropdown-item" href="<?php echo $base_path; ?>notifications/index.php">
                        <i class="bi bi-bell me-2"></i> <?php echo $unread_count; ?> unread notification(s)
                    </a>
                </li>
            <?php else: ?>
                <li><span class="dropdown-item">No new notifications</span></li>
            <?php endif; ?>
            
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header">Messages</h6></li>
            <?php if (isset($unread_messages) && $unread_messages > 0): ?>
                <li>
                    <a class="dropdown-item" href="<?php echo $base_path; ?>messages/index.php">
                        <i class="bi bi-envelope me-2"></i> <?php echo $unread_messages; ?> unread message(s)
                    </a>
                </li>
            <?php else: ?>
                <li><span class="dropdown-item">No new messages</span></li>
            <?php endif; ?>
            
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="<?php echo $base_path; ?>notifications/index.php">
                    View All Notifications
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="<?php echo $base_path; ?>messages/index.php">
                    View All Messages
                </a>
            </li>
        </ul>
    </li>
<?php endif; ?>