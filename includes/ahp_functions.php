<?php
/**
 * AHP (Analytic Hierarchy Process) functions for job matching
 */

/**
 * Calculate AHP weights from a comparison matrix
 */
function calculateAHPWeights($matrix) {
    $n = count($matrix);
    $rowSums = [];
    $weights = [];
    $totalSum = 0;
    
    // Step 1: Calculate sum of each row
    for ($i = 0; $i < $n; $i++) {
        $rowSum = 0;
        for ($j = 0; $j < $n; $j++) {
            $rowSum += $matrix[$i][$j];
        }
        $rowSums[$i] = $rowSum;
        $totalSum += $rowSum;
    }
    
    // Step 2: Normalize to get weights
    for ($i = 0; $i < $n; $i++) {
        $weights[$i] = $rowSums[$i] / $totalSum;
    }
    
    return $weights;
}

/**
 * Calculate job match score for a specific job and user
 */
function calculateJobScore($jobData, $userData, $weights) {
    $score = 0;
    
    // Calculate score for each criterion
    // Skills match - weight[0]
    $skillMatch = calculateSkillMatch($jobData['skills'], $userData['skills']);
    
    // Location match - weight[1]
    $locationMatch = calculateLocationMatch($jobData['location'], $userData['preferred_locations']);
    
    // Salary match - weight[2]
    $salaryMatch = calculateSalaryMatch($jobData['salary_min'], $jobData['salary_max'], $userData['salary_expectation']);
    
    // Job type match - weight[3]
    $jobTypeMatch = calculateJobTypeMatch($jobData['job_type'], $userData['preferred_job_types']);
    
    // Experience match - weight[4]
    $experienceMatch = calculateExperienceMatch($jobData['required_experience'], $userData['experience']);
    
    // Combine scores using weights
    $score = ($skillMatch * $weights[0]) +
             ($locationMatch * $weights[1]) +
             ($salaryMatch * $weights[2]) +
             ($jobTypeMatch * $weights[3]) +
             ($experienceMatch * $weights[4]);
    
    return $score;
}

/**
 * Calculate skill match percentage
 */
function calculateSkillMatch($jobSkills, $userSkills) {
    if (empty($jobSkills) || empty($userSkills)) {
        return 0;
    }
    
    $jobSkillsArray = is_array($jobSkills) ? $jobSkills : explode(',', $jobSkills);
    $userSkillsArray = is_array($userSkills) ? $userSkills : explode(',', $userSkills);
    
    // Clean up arrays
    $jobSkillsArray = array_map('trim', $jobSkillsArray);
    $jobSkillsArray = array_map('strtolower', $jobSkillsArray);
    
    $userSkillsArray = array_map('trim', $userSkillsArray);
    $userSkillsArray = array_map('strtolower', $userSkillsArray);
    
    // Count matches
    $matchCount = 0;
    foreach ($jobSkillsArray as $skill) {
        if (in_array($skill, $userSkillsArray)) {
            $matchCount++;
        }
    }
    
    // Calculate percentage
    $totalSkills = count($jobSkillsArray);
    return $totalSkills > 0 ? ($matchCount / $totalSkills) : 0;
}

/**
 * Calculate location match (1 if exact match, 0.5 if same city, 0 otherwise)
 */
function calculateLocationMatch($jobLocation, $userLocations) {
    if (empty($jobLocation) || empty($userLocations)) {
        return 0;
    }
    
    $userLocationsArray = is_array($userLocations) ? $userLocations : explode(',', $userLocations);
    $userLocationsArray = array_map('trim', $userLocationsArray);
    $userLocationsArray = array_map('strtolower', $userLocationsArray);
    
    $jobLocation = strtolower(trim($jobLocation));
    
    // Check for exact match
    if (in_array($jobLocation, $userLocationsArray)) {
        return 1.0;
    }
    
    // Check for partial match (e.g., city only)
    foreach ($userLocationsArray as $location) {
        if (strpos($jobLocation, $location) !== false || strpos($location, $jobLocation) !== false) {
            return 0.5;
        }
    }
    
    return 0;
}

/**
 * Calculate salary match
 */
function calculateSalaryMatch($minSalary, $maxSalary, $userExpectation) {
    // If user hasn't set expectation, neutral score
    if (empty($userExpectation)) {
        return 0.5;
    }
    
    // If job doesn't specify salary, neutral score
    if (empty($minSalary) && empty($maxSalary)) {
        return 0.5;
    }
    
    // Clean inputs
    $minSalary = is_numeric($minSalary) ? $minSalary : 0;
    $maxSalary = is_numeric($maxSalary) ? $maxSalary : $minSalary * 1.5;
    $userExpectation = is_numeric($userExpectation) ? $userExpectation : 0;
    
    // Perfect match: within range
    if ($userExpectation >= $minSalary && $userExpectation <= $maxSalary) {
        return 1.0;
    }
    
    // Below range
    if ($userExpectation < $minSalary) {
        // Calculate how far below (max score reduction of 0.5)
        $ratio = $userExpectation / $minSalary;
        return max(0.5, $ratio);
    }
    
    // Above range
    if ($userExpectation > $maxSalary) {
        // Calculate how far above (max score reduction of 0.5)
        $difference = $userExpectation - $maxSalary;
        $ratio = min(1, $difference / $maxSalary);
        return max(0, 1 - $ratio);
    }
    
    return 0.5; // Default fallback
}

/**
 * Calculate job type match
 */
function calculateJobTypeMatch($jobType, $preferredTypes) {
    if (empty($preferredTypes)) {
        return 0.5; // Neutral if user has no preferences
    }
    
    $preferredTypesArray = is_array($preferredTypes) ? $preferredTypes : explode(',', $preferredTypes);
    $preferredTypesArray = array_map('trim', $preferredTypesArray);
    $preferredTypesArray = array_map('strtolower', $preferredTypesArray);
    
    $jobType = strtolower(trim($jobType));
    
    if (in_array($jobType, $preferredTypesArray)) {
        return 1.0;
    }
    
    return 0.3; // Low match if not in preferred types
}

/**
 * Calculate experience match
 */
function calculateExperienceMatch($requiredExperience, $userExperience) {
    if (empty($requiredExperience) || !is_numeric($requiredExperience)) {
        return 0.5; // Neutral if job doesn't specify
    }
    
    if (!is_numeric($userExperience)) {
        return 0.5; // Neutral if user hasn't specified
    }
    
    // Exact match or more experience than required
    if ($userExperience >= $requiredExperience) {
        return 1.0;
    }
    
    // Calculate partial match
    $ratio = $userExperience / $requiredExperience;
    return max(0, $ratio);
}

/**
 * Calculate job match score between a job seeker and job using AHP criteria
 * 
 * @param array $jobseeker_data The job seeker's profile and preferences
 * @param array $job The job listing data
 * @param array $weights The calculated AHP weights for different criteria
 * @return float Match score between 0-100
 */
function calculateJobMatchScore($jobseeker_data, $job, $weights) {
    $score = 0;
    $max_score = 0;
    
    // Skills match - compare job seeker skills with job requirements
    if (isset($weights['skills']) && $weights['skills'] > 0) {
        $skills_weight = $weights['skills'];
        $max_score += $skills_weight * 100;
        
        if (!empty($jobseeker_data['skills']) && !empty($job['requirements'])) {
            $jobseeker_skills = explode(',', strtolower($jobseeker_data['skills']));
            $job_skills = explode(',', strtolower($job['requirements']));
            
            $matched_skills = 0;
            foreach ($jobseeker_skills as $skill) {
                $skill = trim($skill);
                if (!empty($skill)) {
                    foreach ($job_skills as $job_skill) {
                        if (strpos(trim($job_skill), $skill) !== false) {
                            $matched_skills++;
                            break;
                        }
                    }
                }
            }
            
            $skills_score = ($matched_skills > 0 && count($job_skills) > 0) ? 
                            ($matched_skills / count($job_skills)) * 100 * $skills_weight : 0;
            $score += $skills_score;
        }
    }
    
    // Location match
    if (isset($weights['location']) && $weights['location'] > 0) {
        $location_weight = $weights['location'];
        $max_score += $location_weight * 100;
        
        if (!empty($jobseeker_data['preferred_locations']) && !empty($job['location'])) {
            $preferred_locations = explode(',', strtolower($jobseeker_data['preferred_locations']));
            $job_location = strtolower($job['location']);
            
            $location_match = false;
            foreach ($preferred_locations as $location) {
                if (strpos($job_location, trim($location)) !== false) {
                    $location_match = true;
                    break;
                }
            }
            
            $location_score = $location_match ? 100 * $location_weight : 0;
            $score += $location_score;
        }
    }
    
    // Job type match
    if (isset($weights['job_type']) && $weights['job_type'] > 0) {
        $job_type_weight = $weights['job_type'];
        $max_score += $job_type_weight * 100;
        
        if (!empty($jobseeker_data['preferred_job_type']) && !empty($job['job_type'])) {
            $job_type_score = ($jobseeker_data['preferred_job_type'] === $job['job_type']) ? 
                               100 * $job_type_weight : 0;
            $score += $job_type_score;
        }
    }
    
    // Salary match
    if (isset($weights['salary']) && $weights['salary'] > 0) {
        $salary_weight = $weights['salary'];
        $max_score += $salary_weight * 100;
        
        if (!empty($jobseeker_data['preferred_salary_min']) && 
            (!empty($job['salary_min']) || !empty($job['salary_max']))) {
            
            $preferred_min = floatval($jobseeker_data['preferred_salary_min']);
            $job_min = !empty($job['salary_min']) ? floatval($job['salary_min']) : 0;
            $job_max = !empty($job['salary_max']) ? floatval($job['salary_max']) : $job_min * 1.5;
            
            // Calculate how well the job's salary matches the job seeker's preferences
            if ($job_max >= $preferred_min) {
                $salary_score = 100 * $salary_weight;
            } else {
                $salary_ratio = $job_max / $preferred_min;
                $salary_score = min(100, $salary_ratio * 100) * $salary_weight;
            }
            
            $score += $salary_score;
        }
    }
    
    // Industry match
    if (isset($weights['industry']) && $weights['industry'] > 0) {
        $industry_weight = $weights['industry'];
        $max_score += $industry_weight * 100;
        
        if (!empty($jobseeker_data['preferred_industry']) && !empty($job['industry'])) {
            $industry_score = ($jobseeker_data['preferred_industry'] === $job['industry']) ? 
                              100 * $industry_weight : 0;
            $score += $industry_score;
        }
    }
    
    // Calculate final percentage score
    $final_score = ($max_score > 0) ? ($score / $max_score) * 100 : 50;
    
    // Ensure the score is between 0 and 100
    return max(0, min(100, $final_score));
}

/**
 * Get job recommendations for a user based on AHP algorithm
 */
function getAHPJobRecommendations($userId, $limit = 10) {
    global $conn;

    // Get user preferences
    $userPreferences = getUserPreferencesForAHP($userId);
    
    if (!$userPreferences) {
        return [];
    }
    
    // Get jobs - removed filtering by status completely
    $sql = "SELECT j.*, 
            e.company_name, e.industry, e.logo_path
            FROM jobs j
            LEFT JOIN employer_profiles e ON j.employer_id = e.user_id
            ORDER BY j.posted_date DESC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        return [];
    }
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    
    // Calculate matching scores
    $scores = [];
    foreach ($jobs as $job) {
        $score = calculateJobMatchScore($userPreferences, $job);
        if ($score > 0) {
            $scores[$job['id']] = $score;
        }
    }
    
    // Sort jobs by score (descending)
    arsort($scores);
    
    // Get top matches
    $result = [];
    $count = 0;
    foreach ($scores as $jobId => $score) {
        foreach ($jobs as $job) {
            if ($job['id'] == $jobId) {
                $job['match_score'] = round($score * 100); // Convert to percentage
                $result[] = $job;
                $count++;
                break;
            }
        }
        
        if ($count >= $limit) {
            break;
        }
    }
    
    return $result;
}

/**
 * Get user preferences for AHP algorithm
 */
function getUserPreferencesForAHP($userId) {
    global $conn;
    
    // Get user basic info - updated to use existing columns
    $sql = "SELECT u.id, u.username, jp.first_name, jp.last_name, jp.skills, 
        jp.experience as experience, jp.preferred_location, 
        jp.expected_salary as salary_expectation
        FROM users u
        LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
        WHERE u.id = ? AND u.user_type = 'jobseeker'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $userData = $result->fetch_assoc();
    
    // Add default values for missing fields
    $userData['preferred_locations'] = $userData['preferred_location']; // Ensure consistent naming
    $userData['preferred_job_types'] = 'full-time'; // Default if column doesn't exist
    
    // Check if user_preference_weights table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'user_preference_weights'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Table exists, try to get weights
        $sql = "SELECT criteria_name, weight 
                FROM user_preference_weights 
                WHERE user_id = ?
                ORDER BY criteria_order";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $weights = array();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $weights[] = $row['weight'];
            }
            $userData['preference_weights'] = $weights;
        }
    }
    
    // Default weights if not set above
    if (!isset($userData['preference_weights']) || empty($userData['preference_weights'])) {
        $userData['preference_weights'] = [0.4, 0.2, 0.2, 0.1, 0.1]; // Default weights
    }
    
    return $userData;
}
?>