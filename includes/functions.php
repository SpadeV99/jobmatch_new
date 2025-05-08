<?php
// Get job category dropdown
function getJobCategoryDropdown($name = 'job_category', $selected = null) {
    global $conn;
    
    $sql = "SELECT id, name FROM job_categories ORDER BY id ASC";
    $result = $conn->query($sql);
    
    $dropdown = '<select name="' . $name . '" class="form-select">';
    $dropdown .= '<option value="">Select Job Category</option>';
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $isSelected = ($selected == $row['id']) ? 'selected' : '';
            $dropdown .= '<option value="' . $row['id'] . '" ' . $isSelected . '>' . htmlspecialchars($row['name']) . '</option>';
        }
    }
    
    $dropdown .= '</select>';
    
    return $dropdown;
}

// Get category name by ID
function getCategoryName($category_id) {
    global $conn;
    
    $sql = "SELECT name FROM job_categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['name'];
    }
    
    return "Unknown Category";
}

/**
 * Convert a timestamp to a human-readable relative time format (e.g., "5 minutes ago")
 * 
 * @param string $timestamp MySQL timestamp
 * @return string Human-readable time difference
 */
function timeAgo($timestamp) {
    if (!$timestamp) return 'Never';
    
    $time_difference = time() - strtotime($timestamp);

    if ($time_difference < 1) { return 'just now'; }
    
    $condition = array(
        12 * 30 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60      => 'month',
        24 * 60 * 60           => 'day',
        60 * 60                => 'hour',
        60                     => 'minute',
        1                      => 'second'
    );

    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;
        
        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
        }
    }
}
?>