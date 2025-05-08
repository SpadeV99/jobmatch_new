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
?>