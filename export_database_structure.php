<?php
// Include database connection
require_once 'config/db_connect.php';

// File to save the export
$filename = 'database_structure_' . date('Y-m-d_H-i-s') . '.sql';
$filepath = __DIR__ . '/' . $filename;

// Function to get and save database structure
function exportDatabaseStructure($conn, $filepath) {
    // Start output buffering
    ob_start();
    
    // Add header comments
    echo "-- JobMatch Database Structure Export\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Get all tables
    $tables_result = $conn->query("SHOW TABLES");
    while ($table_row = $tables_result->fetch_row()) {
        $table_name = $table_row[0];
        
        echo "-- Table structure for table `$table_name`\n";
        
        // Get CREATE TABLE statement
        $create_table_result = $conn->query("SHOW CREATE TABLE `$table_name`");
        $create_table_row = $create_table_result->fetch_row();
        $create_table_sql = $create_table_row[1];
        
        echo $create_table_sql . ";\n\n";
        
        // Get table columns for reference
        echo "-- Columns in `$table_name`\n";
        $columns_result = $conn->query("SHOW COLUMNS FROM `$table_name`");
        $columns = [];
        while ($column_row = $columns_result->fetch_assoc()) {
            $columns[] = $column_row['Field'] . ' (' . $column_row['Type'] . ')';
        }
        echo "-- " . implode(", ", $columns) . "\n\n";
    }
    
    // Get content from buffer and save to file
    $sql_content = ob_get_clean();
    file_put_contents($filepath, $sql_content);
    
    return strlen($sql_content);
}

// Try to export the database structure
try {
    $bytes = exportDatabaseStructure($conn, $filepath);
    
    echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">';
    echo '<h1>Database Structure Exported Successfully</h1>';
    echo '<div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
    echo '<p><strong>Success!</strong> Database structure exported to: ' . $filename . '</p>';
    echo '<p>File size: ' . round($bytes / 1024, 2) . ' KB</p>';
    echo '</div>';
    
    echo '<h2>Download</h2>';
    echo '<p><a href="' . $filename . '" download style="background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">Download SQL File</a></p>';
    
    echo '<h2>Next Steps</h2>';
    echo '<ul>';
    echo '<li>You can use this file to recreate your database structure in another environment</li>';
    echo '<li>This export contains only the structure (tables, columns, indexes) - no data is included</li>';
    echo '<li>Run this script whenever you make changes to your database structure</li>';
    echo '</ul>';
    
    echo '<p><a href="index.php">Return to homepage</a></p>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">';
    echo '<h1>Database Export Failed</h1>';
    echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    echo '<p><a href="index.php">Return to homepage</a></p>';
    echo '</div>';
}
?>