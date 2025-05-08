<?php
require_once 'config/db_connect.php';

// Add employer_id column to jobs table
$sql = "ALTER TABLE jobs ADD COLUMN employer_id INT";
if ($conn->query($sql) === TRUE) {
    echo "employer_id column added to jobs table<br>";
    
    // Add foreign key constraint
    $sql = "ALTER TABLE jobs 
            ADD CONSTRAINT fk_employer_id 
            FOREIGN KEY (employer_id) REFERENCES users(id)";
    if ($conn->query($sql) === TRUE) {
        echo "Foreign key constraint added successfully<br>";
    } else {
        echo "Error adding foreign key: " . $conn->error . "<br>";
    }
} else {
    echo "Error adding employer_id column: " . $conn->error . "<br>";
}

echo "<a href='index.php'>Return to homepage</a>";
$conn->close();
?>