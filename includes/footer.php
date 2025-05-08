</div> <!-- Close main container -->

<footer class="mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <div class="d-flex align-items-center mb-3">
                    <img src="<?php echo isset($base_path) ? $base_path : ''; ?>img/logo.jpg" alt="JobMatch Logo" height="40" class="me-2">
                    <h4 class="mb-0 text-white">JobMatch</h4>
                </div>
                <p>Finding the perfect match between talent and opportunity with our intelligent AHP system.</p>
                <div class="social-icons mt-4">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-twitter"></i></a>
                    <a href="#"><i class="bi bi-linkedin"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
            
            <div class="col-6 col-md-2 mb-4 mb-md-0">
                <h5 class="text-accent">For Job Seekers</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo isset($base_path) ? $base_path : ''; ?>jobs/index.php">Browse Jobs</a></li>
                    <li><a href="<?php echo isset($base_path) ? $base_path : ''; ?>user/profile.php">Create Profile</a></li>
                    <li><a href="<?php echo isset($base_path) ? $base_path : ''; ?>user/saved-jobs.php">Saved Jobs</a></li>
                    <li><a href="<?php echo isset($base_path) ? $base_path : ''; ?>register.php?type=jobseeker">Register</a></li>
                </ul>
            </div>
            
            <div class="col-6 col-md-2 mb-4 mb-md-0">
                <h5 class="text-accent">For Employers</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo isset($base_path) ? $base_path : ''; ?>employer/post-job.php">Post a Job</a></li>
                    <li><a href="<?php echo isset($base_path) ? $base_path : ''; ?>employer/dashboard.php">Employer Dashboard</a></li>
                    <li><a href="<?php echo isset($base_path) ? $base_path : ''; ?>employer/applications.php">View Applications</a></li>
                    <li><a href="<?php echo isset($base_path) ? $base_path : ''; ?>register.php?type=employer">Register</a></li>
                </ul>
            </div>
            
            <div class="col-md-4">
                <h5 class="text-accent">Contact Us</h5>
                <ul class="list-unstyled">
                    <li class="d-flex mb-3">
                        <i class="bi bi-geo-alt me-2 mt-1"></i>
                        <span>123 Career Avenue, Professional District<br>Employment City, EC 12345</span>
                    </li>
                    <li class="d-flex mb-3">
                        <i class="bi bi-envelope me-2 mt-1"></i>
                        <span>info@jobmatch.com</span>
                    </li>
                    <li class="d-flex">
                        <i class="bi bi-telephone me-2 mt-1"></i>
                        <span>(123) 456-7890</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <hr class="mt-4" style="border-color: rgba(255,255,255,0.1);">
        
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-md-0">© <?php echo date('Y'); ?> JobMatch. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item"><a href="#">Privacy Policy</a></li>
                    <li class="list-inline-item"><span class="mx-2">•</span></li>
                    <li class="list-inline-item"><a href="#">Terms of Service</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
    // Add active class to current nav item
    document.addEventListener('DOMContentLoaded', function() {
        const currentLocation = location.pathname;
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        
        navLinks.forEach(link => {
            if (link.getAttribute('href') && currentLocation.includes(link.getAttribute('href'))) {
                link.classList.add('active');
            }
        });
    });
</script>
</body>
</html>