<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Simple job submission processing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['job_category'];
    $location = $_POST['location'];
    $salary = $_POST['salary'];
    
    $sql = "INSERT INTO jobs (title, description, category_id, location, salary) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiss", $title, $description, $category_id, $location, $salary);
    
    if ($stmt->execute()) {
        $success_message = "Job posted successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
}

$base_path = '../';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <h1>Admin - Manage Jobs</h1>
        
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Add New Job</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="title" class="form-label">Job Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="job_category" class="form-label">Job Category</label>
                        <?php echo getJobCategoryDropdown('job_category'); ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="salary" class="form-label">Salary</label>
                        <input type="text" class="form-control" id="salary" name="salary" placeholder="e.g. $50,000 - $70,000">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Job Description</label>
                        <textarea class="form-control" id="description" name="description" rows="6" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Post Job</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <h3>Recently Added Jobs</h3>
        
        <?php
        $sql = "SELECT j.id, j.title, c.name as category 
                FROM jobs j
                JOIN job_categories c ON j.category_id = c.id
                ORDER BY j.posted_date DESC LIMIT 5";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            echo '<ul class="list-group">';
            while($row = $result->fetch_assoc()) {
                echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                        ' . htmlspecialchars($row["title"]) . '
                        <span class="badge bg-primary rounded-pill">' . htmlspecialchars($row["category"]) . '</span>
                      </li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="alert alert-info">No jobs added yet.</div>';
        }
        ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>