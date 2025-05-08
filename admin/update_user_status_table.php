<?php
require_once '../config/db_connect.php';

echo "<h2>Creating User Status Table</h2>";

// Check if user_status table exists
$result = $conn->query("SHOW TABLES LIKE 'user_status'");
if ($result->num_rows == 0) {
    // Create the user_status table
    $sql = "CREATE TABLE user_status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        status ENUM('active', 'inactive', 'banned', 'pending') NOT NULL DEFAULT 'active',
        reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ User status table created successfully</p>";
        
        // Set all existing users to active by default
        $sql = "INSERT INTO user_status (user_id, status) 
                SELECT id, 'active' FROM users";
        if ($conn->query($sql)) {
            echo "<p>✅ All existing users set to active status</p>";
        } else {
            echo "<p>❌ Error setting default status: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>❌ Error creating user status table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ User status table already exists</p>";
}

// Check if there are any users without a status entry
$sql = "SELECT u.id FROM users u 
        LEFT JOIN user_status us ON u.id = us.user_id 
        WHERE us.id IS NULL";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<p>Found {$result->num_rows} users without status entries. Adding default status...</p>";
    
    while ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
        $insert = $conn->query("INSERT INTO user_status (user_id, status) VALUES ($user_id, 'active')");
        if (!$insert) {
            echo "<p>❌ Error adding status for user #$user_id: " . $conn->error . "</p>";
        }
    }
    
    echo "<p>✅ Added status entries for all users</p>";
}

echo "<p><a href='manage-accounts.php' class='btn btn-primary'>Go to Manage Accounts</a></p>";
?>