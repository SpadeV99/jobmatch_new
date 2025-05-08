<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

$base_path = '../';
include '../includes/header.php';

// Get filter parameters
$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;

// Build query
$sql = "SELECT j.id, j.title, j.description, j.location, j.salary, j.posted_date, c.name as category_name 
        FROM jobs j
        JOIN job_categories c ON j.category_id = c.id";

if ($category_id) {
    $sql .= " WHERE j.category_id = $category_id";
}

$sql .= " ORDER BY j.posted_date DESC";
$result = $conn->query($sql);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>Available Jobs</h1>
    </div>
    <div class="col-md-4">
        <form action="" method="get" class="mb-4">
            <div class="mb-3">
                <label for="category" class="form-label">Filter by Category</label>
                <?php echo getJobCategoryDropdown('category', $category_id); ?>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if($category_id): ?>
            <a href="index.php" class="btn btn-outline-secondary">Clear Filter</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="row">
    <?php
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($row["title"]); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($row["category_name"]); ?></h6>
                        <p class="card-text"><?php echo substr(htmlspecialchars($row["description"]), 0, 150) . '...'; ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span><strong>Location:</strong> <?php echo htmlspecialchars($row["location"]); ?></span>
                            <span><strong>Salary:</strong> <?php echo htmlspecialchars($row["salary"]); ?></span>
                        </div>
                        <hr>
                        <a href="apply.php?job_id=<?php echo $row["id"]; ?>" class="btn btn-primary">Apply Now</a>
                    </div>
                    <div class="card-footer text-muted">
                        Posted: <?php echo date('F j, Y', strtotime($row["posted_date"])); ?>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<div class="col-12"><div class="alert alert-info">No jobs found.</div></div>';
    }
    ?>
</div>

<?php include '../includes/footer.php'; ?>