<?php
/**
 * Interview management functions
 */

/**
 * Schedule a new interview
 */
function scheduleInterview($job_id, $application_id, $employer_id, $jobseeker_id, $interview_date, $duration_minutes, $interview_type, $location, $meeting_link) {
    global $conn;
    
    $sql = "INSERT INTO interviews 
            (job_id, job_application_id, employer_id, jobseeker_id, interview_date, 
            duration_minutes, interview_type, location, meeting_link) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiisisss", $job_id, $application_id, $employer_id, 
                    $jobseeker_id, $interview_date, $duration_minutes, 
                    $interview_type, $location, $meeting_link);
    
    if ($stmt->execute()) {
        $interview_id = $conn->insert_id;
        
        // Update application status
        $sql = "UPDATE job_applications SET status = 'interview' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        
        // Create notification for jobseeker
        if (function_exists('createNotification')) {
            $job_sql = "SELECT title FROM jobs WHERE id = ?";
            $job_stmt = $conn->prepare($job_sql);
            $job_stmt->bind_param("i", $job_id);
            $job_stmt->execute();
            $job_result = $job_stmt->get_result();
            $job = $job_result->fetch_assoc();
            
            $title = "Interview Scheduled";
            $message = "You have been invited to an interview for the position of \"{$job['title']}\". ";
            $message .= "The interview is scheduled for " . date('F j, Y \a\t g:i a', strtotime($interview_date)) . ".";
            
            createNotification(
                $jobseeker_id,
                'interview',
                $title,
                $message,
                $interview_id,
                "../interviews/view.php?id=" . $interview_id
            );
        }
        
        return $interview_id;
    }
    
    return false;
}

/**
 * Get interview details
 */
function getInterviewDetails($interview_id) {
    global $conn;
    
    $sql = "SELECT i.*, 
            j.title as job_title, 
            ep.company_name,
            CONCAT(jp.first_name, ' ', jp.last_name) as jobseeker_name,
            jp.resume_path,
            u_jobseeker.username as jobseeker_username,
            u_employer.username as employer_username
            FROM interviews i 
            JOIN jobs j ON i.job_id = j.id
            JOIN users u_jobseeker ON i.jobseeker_id = u_jobseeker.id
            JOIN users u_employer ON i.employer_id = u_employer.id
            LEFT JOIN jobseeker_profiles jp ON i.jobseeker_id = jp.user_id
            LEFT JOIN employer_profiles ep ON i.employer_id = ep.user_id
            WHERE i.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $interview_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * Get interviews for employer
 */
function getEmployerInterviews($employer_id, $status = null) {
    global $conn;
    
    $sql = "SELECT i.*, 
            j.title as job_title, 
            CONCAT(jp.first_name, ' ', jp.last_name) as jobseeker_name,
            u_jobseeker.username as jobseeker_username
            FROM interviews i 
            JOIN jobs j ON i.job_id = j.id
            JOIN users u_jobseeker ON i.jobseeker_id = u_jobseeker.id
            LEFT JOIN jobseeker_profiles jp ON i.jobseeker_id = jp.user_id
            WHERE i.employer_id = ?";
    
    if ($status) {
        $sql .= " AND i.status = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $employer_id, $status);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $employer_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $interviews = [];
    while ($row = $result->fetch_assoc()) {
        $interviews[] = $row;
    }
    
    return $interviews;
}

/**
 * Get interviews for jobseeker
 */
function getJobseekerInterviews($jobseeker_id, $status = null) {
    global $conn;
    
    $sql = "SELECT i.*, 
            j.title as job_title, 
            ep.company_name,
            u_employer.username as employer_username
            FROM interviews i 
            JOIN jobs j ON i.job_id = j.id
            JOIN users u_employer ON i.employer_id = u_employer.id
            LEFT JOIN employer_profiles ep ON i.employer_id = ep.user_id
            WHERE i.jobseeker_id = ?";
    
    if ($status) {
        $sql .= " AND i.status = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $jobseeker_id, $status);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $jobseeker_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $interviews = [];
    while ($row = $result->fetch_assoc()) {
        $interviews[] = $row;
    }
    
    return $interviews;
}

/**
 * Update interview status
 */
function updateInterviewStatus($interview_id, $status) {
    global $conn;
    
    $sql = "UPDATE interviews SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $interview_id);
    
    return $stmt->execute();
}

/**
 * Request interview reschedule
 */
function requestInterviewReschedule($interview_id, $user_id, $proposed_date, $reason) {
    global $conn;
    
    $sql = "INSERT INTO interview_reschedule_requests 
            (interview_id, requested_by, proposed_date, reason) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $interview_id, $user_id, $proposed_date, $reason);
    
    if ($stmt->execute()) {
        $request_id = $conn->insert_id;
        
        // Get interview details to create notification
        $interview = getInterviewDetails($interview_id);
        
        if ($interview) {
            // Determine recipient (if jobseeker requested, notify employer and vice versa)
            $recipient_id = ($user_id == $interview['jobseeker_id']) ? 
                            $interview['employer_id'] : $interview['jobseeker_id'];
            
            // Create notification
            if (function_exists('createNotification')) {
                $title = "Interview Reschedule Request";
                $message = "A request to reschedule the interview for \"{$interview['job_title']}\" has been submitted. ";
                $message .= "Proposed new date: " . date('F j, Y \a\t g:i a', strtotime($proposed_date));
                
                createNotification(
                    $recipient_id,
                    'interview',
                    $title,
                    $message,
                    $interview_id,
                    "../interviews/view.php?id=" . $interview_id
                );
            }
        }
        
        return $request_id;
    }
    
    return false;
}

/**
 * Get reschedule requests for interview
 */
function getInterviewRescheduleRequests($interview_id) {
    global $conn;
    
    $sql = "SELECT r.*, 
            CASE 
                WHEN u.user_type = 'employer' THEN ep.company_name
                WHEN u.user_type = 'jobseeker' THEN CONCAT(jp.first_name, ' ', jp.last_name)
                ELSE u.username
            END as requester_name,
            u.user_type as requester_type
            FROM interview_reschedule_requests r
            JOIN users u ON r.requested_by = u.id
            LEFT JOIN employer_profiles ep ON r.requested_by = ep.user_id AND u.user_type = 'employer'
            LEFT JOIN jobseeker_profiles jp ON r.requested_by = jp.user_id AND u.user_type = 'jobseeker'
            WHERE r.interview_id = ?
            ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $interview_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    return $requests;
}

/**
 * Respond to reschedule request
 */
function respondToRescheduleRequest($request_id, $status, $user_id) {
    global $conn;
    
    $sql = "UPDATE interview_reschedule_requests SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $request_id);
    
    if (!$stmt->execute()) {
        return false;
    }
    
    // If accepted, update the interview date
    if ($status === 'accepted') {
        $sql = "SELECT r.interview_id, r.proposed_date, r.requested_by, i.jobseeker_id, i.employer_id 
                FROM interview_reschedule_requests r
                JOIN interviews i ON r.interview_id = i.id
                WHERE r.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        
        if ($request) {
            // Update interview date
            $sql = "UPDATE interviews SET interview_date = ?, status = 'rescheduled' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $request['proposed_date'], $request['interview_id']);
            $stmt->execute();
            
            // Create notification for requester
            if (function_exists('createNotification')) {
                // Get recipient (the person who requested the reschedule)
                $recipient_id = $request['requested_by'];
                
                // Get interview details
                $interview = getInterviewDetails($request['interview_id']);
                
                $title = "Reschedule Request Accepted";
                $message = "Your request to reschedule the interview for \"{$interview['job_title']}\" has been accepted. ";
                $message .= "The interview is now scheduled for " . date('F j, Y \a\t g:i a', strtotime($request['proposed_date'])) . ".";
                
                createNotification(
                    $recipient_id,
                    'interview',
                    $title,
                    $message,
                    $request['interview_id'],
                    "../interviews/view.php?id=" . $request['interview_id']
                );
            }
        }
    } else if ($status === 'rejected') {
        // Create notification for rejection
        $sql = "SELECT r.interview_id, r.requested_by, i.job_id 
                FROM interview_reschedule_requests r
                JOIN interviews i ON r.interview_id = i.id
                WHERE r.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        
        if ($request && function_exists('createNotification')) {
            // Get interview details
            $interview = getInterviewDetails($request['interview_id']);
            
            $title = "Reschedule Request Rejected";
            $message = "Your request to reschedule the interview for \"{$interview['job_title']}\" has been rejected.";
            
            createNotification(
                $request['requested_by'],
                'interview',
                $title,
                $message,
                $request['interview_id'],
                "../interviews/view.php?id=" . $request['interview_id']
            );
        }
    }
    
    return true;
}

/**
 * Save interview feedback
 */
function saveInterviewFeedback($interview_id, $rating, $strengths, $weaknesses, $technical_skills, $communication_skills, $cultural_fit, $overall_notes, $recommendation) {
    global $conn;
    
    $sql = "INSERT INTO interview_feedback 
            (interview_id, rating, strengths, weaknesses, technical_skills, 
            communication_skills, cultural_fit, overall_notes, recommendation) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssss", $interview_id, $rating, $strengths, $weaknesses, 
                    $technical_skills, $communication_skills, $cultural_fit, 
                    $overall_notes, $recommendation);
    
    return $stmt->execute();
}

/**
 * Get interview feedback
 */
function getInterviewFeedback($interview_id) {
    global $conn;
    
    $sql = "SELECT * FROM interview_feedback WHERE interview_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $interview_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * Get upcoming interviews
 */
function getUpcomingInterviews($user_id, $user_type, $limit = 5) {
    global $conn;
    
    $field = ($user_type === 'employer') ? 'employer_id' : 'jobseeker_id';
    
    $sql = "SELECT i.*, 
            j.title as job_title,
            CASE 
                WHEN ? = 'employer' THEN CONCAT(jp.first_name, ' ', jp.last_name)
                ELSE ep.company_name
            END as other_party
            FROM interviews i
            JOIN jobs j ON i.job_id = j.id
            LEFT JOIN employer_profiles ep ON i.employer_id = ep.user_id
            LEFT JOIN jobseeker_profiles jp ON i.jobseeker_id = jp.user_id
            WHERE i.$field = ? 
            AND i.status IN ('scheduled', 'rescheduled')
            AND i.interview_date > NOW()
            ORDER BY i.interview_date ASC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $user_type, $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $interviews = [];
    while ($row = $result->fetch_assoc()) {
        $interviews[] = $row;
    }
    
    return $interviews;
}

/**
 * Get calendar data for user
 */
function getInterviewCalendarData($user_id, $user_type, $start_date, $end_date) {
    global $conn;
    
    $field = ($user_type === 'employer') ? 'employer_id' : 'jobseeker_id';
    
    $sql = "SELECT i.*, 
            j.title as job_title,
            CASE 
                WHEN ? = 'employer' THEN CONCAT(jp.first_name, ' ', jp.last_name)
                ELSE ep.company_name
            END as other_party
            FROM interviews i
            JOIN jobs j ON i.job_id = j.id
            LEFT JOIN employer_profiles ep ON i.employer_id = ep.user_id
            LEFT JOIN jobseeker_profiles jp ON i.jobseeker_id = jp.user_id
            WHERE i.$field = ? 
            AND i.interview_date BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siss", $user_type, $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate end time based on duration
        $start_datetime = new DateTime($row['interview_date']);
        $end_datetime = clone $start_datetime;
        $end_datetime->add(new DateInterval('PT' . $row['duration_minutes'] . 'M'));
        
        // Determine event color based on status
        $color = '#3788d8'; // default blue
        if ($row['status'] === 'completed') {
            $color = '#28a745'; // green
        } else if ($row['status'] === 'cancelled') {
            $color = '#dc3545'; // red
        } else if ($row['status'] === 'rescheduled') {
            $color = '#fd7e14'; // orange
        } else if ($row['status'] === 'no_show') {
            $color = '#6c757d'; // gray
        }
        
        $events[] = [
            'id' => $row['id'],
            'title' => $row['job_title'] . ' - ' . $row['other_party'],
            'start' => $start_datetime->format('Y-m-d\TH:i:s'),
            'end' => $end_datetime->format('Y-m-d\TH:i:s'),
            'color' => $color,
            'url' => '../interviews/view.php?id=' . $row['id'],
            'extendedProps' => [
                'status' => $row['status'],
                'type' => $row['interview_type']
            ]
        ];
    }
    
    return $events;
}
?>