<?php
require_once 'config/db_connect.php';

echo "<h2>JobMatch Database Repair Tool</h2>";

// Check if employer_id exists in interviews table
$result = $conn->query("SHOW COLUMNS FROM interviews LIKE 'employer_id'");
if ($result && $result->num_rows == 0) {
    // Add the missing column
    $sql = "ALTER TABLE interviews ADD COLUMN employer_id INT AFTER application_id";
    if ($conn->query($sql) === TRUE) {
        echo "Added missing employer_id column to interviews table.<br>";
        
        // Populate the column with data from jobs table
        $sql = "UPDATE interviews i 
                JOIN jobs j ON i.job_id = j.id 
                SET i.employer_id = j.employer_id";
        if ($conn->query($sql) === TRUE) {
            echo "Populated employer_id column with data from jobs table.<br>";
        } else {
            echo "Error populating employer_id: " . $conn->error . "<br>";
        }
    } else {
        echo "Error adding employer_id column: " . $conn->error . "<br>";
    }
}

// Check for other database issues
echo "<br>Database check complete. <a href='index.php'>Return to homepage</a>";
$conn->close();
?>  