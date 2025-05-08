<?php
/**
 * Get or create conversation between two users
 */
function getOrCreateConversation($user1Id, $user2Id) {
    global $conn;
    
    // Always make sure smaller ID is user1 for consistency
    if ($user1Id > $user2Id) {
        $temp = $user1Id;
        $user1Id = $user2Id;
        $user2Id = $temp;
    }
    
    // Check if conversation exists
    $sql = "SELECT id FROM conversations 
            WHERE (user1_id = ? AND user2_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user1Id, $user2Id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Return existing conversation
        $row = $result->fetch_assoc();
        return $row['id'];
    } else {
        // Create new conversation
        $sql = "INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user1Id, $user2Id);
        $stmt->execute();
        return $conn->insert_id;
    }
}

/**
 * Send message
 */
function sendMessage($conversationId, $senderId, $message) {
    global $conn;
    
    $sql = "INSERT INTO messages (conversation_id, sender_id, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $conversationId, $senderId, $message);
    
    if ($stmt->execute()) {
        // Update conversation timestamp
        $sql = "UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $updateStmt = $conn->prepare($sql);
        $updateStmt->bind_param("i", $conversationId);
        $updateStmt->execute();
        
        return $conn->insert_id;
    }
    
    return false;
}

/**
 * Get all conversations for a user
 */
function getUserConversations($userId) {
    global $conn;
    
    $sql = "SELECT c.*, 
            CASE 
                WHEN c.user1_id = ? THEN c.user2_id
                ELSE c.user1_id
            END as other_user_id,
            u.username as other_username,
            u.user_type as other_user_type,
            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = 0) as unread_count,
            (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
            FROM conversations c
            JOIN users u ON (
                (c.user1_id = ? AND c.user2_id = u.id) OR
                (c.user2_id = ? AND c.user1_id = u.id)
            )
            WHERE c.user1_id = ? OR c.user2_id = ?
            ORDER BY c.updated_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiii", $userId, $userId, $userId, $userId, $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row;
    }
    
    return $conversations;
}

/**
 * Get messages for a conversation
 */
function getConversationMessages($conversationId, $userId) {
    global $conn;
    
    // Mark messages as read
    $markRead = "UPDATE messages SET is_read = 1 
                 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0";
    $markStmt = $conn->prepare($markRead);
    $markStmt->bind_param("ii", $conversationId, $userId);
    $markStmt->execute();
    
    // Get messages
    $sql = "SELECT m.*, u.username as sender_name 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $conversationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    return $messages;
}

/**
 * Get unread message count for a user
 */
function getUnreadMessageCount($userId) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE m.is_read = 0 AND m.sender_id != ? 
            AND (c.user1_id = ? OR c.user2_id = ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $userId, $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Check if users are allowed to message each other
 */
function canMessageUser($currentUserId, $targetUserId) {
    global $conn;
    
    // Get user types
    $sql = "SELECT id, user_type FROM users WHERE id IN (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $currentUserId, $targetUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[$row['id']] = $row['user_type'];
    }
    
    // If both users are same type, they shouldn't message each other
    // (jobseeker to jobseeker or employer to employer)
    if ($users[$currentUserId] === $users[$targetUserId]) {
        return false;
    }
    
    return true;
}
?>