<?php
require_once 'config/db_connect.php';
require_once 'includes/functions.php';
include_once 'includes/header.php';
?>

<!-- Hero Section with Background Image -->
<div class="hero-section position-relative overflow-hidden text-white mb-5">
    <div class="hero-overlay"></div>
    <div class="container position-relative py-5 my-5">
        <div class="row py-5">
            <div class="col-lg-7 mx-auto text-center">
                <h1 class="display-3 fw-bold mb-3">Find Your Perfect <span class="text-accent">Job Match</span></h1>
                <p class="lead mb-4 fs-4">Our intelligent AHP system matches your skills and preferences with the perfect job opportunities.</p>
                <div class="d-grid gap-3 d-sm-flex justify-content-sm-center mt-4">
                    <a href="jobs/index.php" class="btn btn-primary btn-lg px-5 py-3 fw-bold">
                        <i class="bi bi-search me-2"></i>Browse Jobs
                    </a>
                    <a href="register.php" class="btn btn-light btn-lg px-5 py-3 fw-bold">
                        <i class="bi bi-person-plus me-2"></i>Sign Up
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="container mb-5">
    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="stat-card py-4 rounded shadow-sm">
                <i class="bi bi-briefcase fs-1 mb-2"></i>
                <h3 class="fw-bold">
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as count FROM jobs");
                    echo $result->fetch_assoc()['count'];
                    ?>
                </h3>
                <p class="text-muted mb-0">Available Jobs</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card py-4 rounded shadow-sm">
                <i class="bi bi-building fs-1 mb-2"></i>
                <h3 class="fw-bold">
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type='employer'");
                    echo $result->fetch_assoc()['count'];
                    ?>
                </h3>
                <p class="text-muted mb-0">Partner Companies</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card py-4 rounded shadow-sm">
                <i class="bi bi-people fs-1 mb-2"></i>
                <h3 class="fw-bold">
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type='jobseeker'");
                    echo $result->fetch_assoc()['count'];
                    ?>
                </h3>
                <p class="text-muted mb-0">Active Job Seekers</p>
            </div>
        </div>
    </div>
</div>

<!-- Category Section -->
<div class="container mb-5">
    <div class="section-header">
        <h2>Browse Job Categories</h2>
        <p class="text-muted mb-4">Find your perfect role in one of these professional fields</p>
    </div>

    <div class="row g-4">
        <?php
        $sql = "SELECT id, name FROM job_categories ORDER BY name LIMIT 6";
        $result = $conn->query($sql);
        
        // Array of Bootstrap icons for categories
        $category_icons = [
            "Accounting" => "bi-cash-coin",
            "Administrative" => "bi-clipboard-check",
            "Marketing" => "bi-megaphone",
            "Agriculture" => "bi-tree",
            "Design" => "bi-palette",
            "Automotive" => "bi-car-front",
            "Banking" => "bi-bank",
            "Biotech" => "bi-lungs",
            "Business" => "bi-graph-up-arrow",
            "Construction" => "bi-building",
            "Consulting" => "bi-people",
            "Customer" => "bi-headset",
            "Education" => "bi-mortarboard",
            "Engineering" => "bi-gear",
            "Technology" => "bi-cpu",
            "Software" => "bi-code-slash"
        ];
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Choose an icon based on category name, or use default
                $icon = "bi-briefcase"; // Default icon
                foreach($category_icons as $key => $value) {
                    if (stripos($row["name"], $key) !== false) {
                        $icon = $value;
                        break;
                    }
                }
                
                echo '<div class="col-md-4">
                    <a href="jobs/index.php?category=' . $row["id"] . '" class="text-decoration-none">
                        <div class="card h-100 category-card">
                            <div class="card-body text-center p-4">
                                <div class="category-icon-wrapper">
                                    <i class="bi ' . $icon . ' fs-1"></i>
                                </div>
                                <h4 class="fw-bold mb-3">' . htmlspecialchars($row["name"]) . '</h4>
                                <div class="btn btn-sm btn-outline-primary mt-2">Browse Jobs</div>
                            </div>
                        </div>
                    </a>
                </div>';
            }
        } else {
            echo "<div class='col-12 text-center'><p>No categories found</p></div>";
        }
        ?>
    </div>
</div>

<!-- How It Works Section -->
<div class="bg-light-custom py-5 mb-5">
    <div class="container">
        <div class="section-header">
            <h2>How JobMatch Works</h2>
            <p class="text-muted mb-4">Our AHP algorithm provides smarter job recommendations</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-person-vcard fs-1"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Create Your Profile</h4>
                        <p class="text-muted">Build your professional profile with your skills and preferences</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-sliders fs-1"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Set Your Priorities</h4>
                        <p class="text-muted">Tell us what matters most in your ideal job</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-check2-circle fs-1"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Get Matched</h4>
                        <p class="text-muted">Receive personalized job recommendations tailored to your profile</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="container mb-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card border-0 rounded-3 bg-primary text-white overflow-hidden">
                <div class="row g-0">
                    <div class="col-md-7 p-5">
                        <h2 class="fw-bold mb-3">Ready to find your dream job?</h2>
                        <p class="lead mb-4">Create your profile today and let our AI-powered system match you with the perfect opportunities.</p>
                        <a href="register.php" class="btn btn-light btn-lg px-4">Get Started Now</a>
                    </div>
                    <div class="col-md-5 d-none d-md-block">
                        <div class="cta-image h-100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>