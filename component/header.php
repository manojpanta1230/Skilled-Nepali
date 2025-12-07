<!-- Navbar & Hero Start -->
<?php 
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- Font Awesome -->

<div class="container-fluid nav-bar p-0">
  <nav class="navbar navbar-expand-lg navbar-light px-4 px-lg-5 py-3 py-lg-0" style="background-color:#00A098;">
    <a href="index.php" class="navbar-brand p-0 d-flex align-items-center">
      <img src="img/image-removebg-preview.png" class="img-fluid" alt="" width="300px">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"
      aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
      <span class="fa fa-bars text-white"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end align-items-center" id="navbarCollapse">
      <div class="navbar-nav d-flex align-items-center text-center">
        <a href="index.php" class="nav-item nav-link text-white mx-2">Home</a>
        <a href="company.php" class="nav-item nav-link text-white mx-2">Company</a>
        <a href="job_seeker.php" class="nav-item nav-link text-white mx-2">Candidate</a>
        <a href="jobs.php" class="nav-item nav-link text-white mx-2">View All Jobs</a>

        <!-- User Dropdown -->
        <div class="nav-item dropdown ms-3">
          <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown"
             role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-user-circle fa-2x text-white"></i>
          </a>

          <ul class="dropdown-menu dropdown-menu-end custom-dropdown" aria-labelledby="userDropdown">

            <?php if (!empty($_SESSION['user_id'])): ?>
              <li>
                <h6 class="dropdown-header">
                   Hello, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>
                </h6>
              </li>

              <!-- My Dashboard (for everyone logged in) -->
            

              <!-- Role based links -->
              <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'jobseeker'): ?>
                <li>
                  <a class="dropdown-item" href="jobseeker_panel.php">
                    <i class="fas fa-user me-2"></i> Jobseeker Panel
                  </a>
                </li>
              <?php elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'employer'): ?>
                <li>
                  <a class="dropdown-item" href="employer_dashboard.php">
                    <i class="fas fa-building me-2"></i> Employer Panel
                  </a>
                </li>
              <?php elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'training_center'): ?>
                <li>
                  <a class="dropdown-item" href="training_center_dashboard.php">
                    <i class="fas fa-school me-2"></i> Training Center Panel
                  </a>
                </li>
              <?php elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin' ): ?>
                <li>
                  <a class="dropdown-item" href="admin_panel.php">
                    <i class="fas fa-user-shield me-2"></i> Admin Panel
                  </a>
                </li>
              <?php endif; ?>

              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="logout.php">
                  <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
              </li>

            <?php else: ?>
              <li>
                <a class="dropdown-item" href="login.php">
                  <i class="fas fa-sign-in-alt me-2"></i> Sign In
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="register.php">
                  <i class="fas fa-user-plus me-2"></i> Sign Up
                </a>
              </li>
            <?php endif; ?>

          </ul>
        </div>

      </div>
    </div>
  </nav>
</div>

<!-- Custom Styles -->
<style>
  /* Prevent overflow */
  body, html {
    overflow-x: hidden;
    margin: 0;
    padding: 0;
  }

  /* Remove any default navbar margins */
  .container-fluid.nav-bar {
    margin: 0 !important;
    padding: 0 !important;
  }

  .navbar {
    margin: 0 !important;
  }

  .navbar-nav .nav-link {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    font-size: 16px;
    font-weight: 500;
    transition: color 0.3s ease;
    white-space: nowrap;
  }

  .navbar-nav .nav-link:hover {
    color: white !important;
  }

  .custom-dropdown {
    min-width: 200px;
    background-color: #fff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    padding: 10px 0;
  }

  .custom-dropdown .dropdown-item {
    font-size: 16px;
    padding: 10px 20px;
    color: #333;
    transition: all 0.2s ease-in-out;
  }

  .custom-dropdown .dropdown-item:hover {
    background-color: #00A098;
    color: white;
  }

  .custom-dropdown .dropdown-header {
    font-size: 14px;
    color: #666;
    padding: 8px 20px;
  }

  .fa-user-circle {
    cursor: pointer;
    transition: transform 0.2s ease;
  }

  .fa-user-circle:hover {
    transform: scale(1.1);
  }

  .dropdown-menu-end {
    right: 0 !important;
    left: auto !important;
  }
</style>

