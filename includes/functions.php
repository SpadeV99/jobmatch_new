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

/**
 * Get basic job recommendations for a jobseeker
 * (Fallback when AHP recommendations are not available)
 * 
 * @param int $user_id The user ID
 * @param int $limit Maximum number of recommendations to return
 * @return array An array of job recommendations
 */
function getBasicJobRecommendations($user_id, $limit = 10) {
    global $conn;
    
    // Get job seeker profile info
    $sql = "SELECT js.skills, js.experience FROM jobseeker_profiles js WHERE js.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();
        $skills = !empty($profile['skills']) ? explode(',', $profile['skills']) : [];
        
        if (!empty($skills)) {
            // Build a query to find jobs that match some of the skills
            $sql = "SELECT j.*, ep.company_name 
                   FROM jobs j 
                   LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                   WHERE j.status = 'active' AND (";
            
            $conditions = [];
            $params = [];
            $types = "";
            
            foreach ($skills as $skill) {
                $conditions[] = "j.description LIKE ? OR j.requirements LIKE ? OR j.title LIKE ?";
                $skill_param = '%' . trim($skill) . '%';
                $params[] = $skill_param; // for description
                $params[] = $skill_param; // for requirements
                $params[] = $skill_param; // for title
                $types .= "sss";
            }
            
            $sql .= implode(' OR ', $conditions) . ") 
                   ORDER BY j.is_featured DESC, j.posted_date DESC 
                   LIMIT ?";
            $params[] = $limit;
            $types .= "i";
            
            $stmt = $conn->prepare($sql);
            
            // Bind all parameters dynamically
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $jobs = [];
            while ($job = $result->fetch_assoc()) {
                $jobs[] = $job;
            }
            
            return $jobs;
        }
    }
    
    // If no specific recommendations can be made, return recent active jobs
    $sql = "SELECT j.*, ep.company_name 
           FROM jobs j 
           LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
           WHERE j.status = 'active' 
           ORDER BY j.is_featured DESC, j.posted_date DESC 
           LIMIT ?";
           
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($job = $result->fetch_assoc()) {
        $jobs[] = $job;
    }
    
    return $jobs;
}

/**
 * Check if a job seeker profile is sufficiently complete for recommendations
 * 
 * @param int $user_id The user ID
 * @return bool True if profile is complete, false otherwise
 */
function isJobSeekerProfileComplete($user_id) {
    global $conn;
    
    $sql = "SELECT skills, experience, education, 
           preferred_job_type, preferred_locations
           FROM jobseeker_profiles 
           WHERE user_id = ?";
           
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();
        
        // Check if essential fields are filled
        $has_skills = !empty($profile['skills']);
        $has_experience = !empty($profile['experience']);
        $has_education = !empty($profile['education']);
        $has_job_preferences = !empty($profile['preferred_job_type']) || !empty($profile['preferred_locations']);
        
        return $has_skills && ($has_experience || $has_education) && $has_job_preferences;
    }
    
    return false;
}
?>