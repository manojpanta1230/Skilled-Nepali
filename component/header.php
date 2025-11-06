<!-- Navbar & Hero Start -->
 <?php 
 include 'config.php';
 ?>
<div class="container-fluid nav-bar p-0">
  <nav class="navbar navbar-expand-lg navbar-light px-4 px-lg-5 py-3 py-lg-0" style="background-color:#00A098;">
    <a href="#" class="navbar-brand p-0 d-flex align-items-center">
      <img src="img/image-removebg-preview.png" class="img-fluid" alt="" width="300px">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
      <span class="fa fa-bars text-white"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end align-items-center" id="navbarCollapse">
      <div class="navbar-nav d-flex align-items-center text-center">
        <a href="index.php" class="nav-item nav-link text-white mx-2">Home</a>
        <a href="company.php" class="nav-item nav-link text-white mx-2">Company</a>
        <a href="job_seeker.php" class="nav-item nav-link text-white mx-2">Candidate</a>
        <a href="#contact" class="nav-item nav-link text-white mx-2">Contact</a>
    <a href="jobs.php" class="nav-item nav-link text-white mx-2">View All Jobs</a>

        <!-- Orange "Post Ad" Button -->
      <a href="login.php" 
   class="btn btn-warning text-white fw-semibold ms-3 px-4 py-2 rounded-pill"
   style="background-color:#FF7A00; border:none; white-space:nowrap;"
   onclick="event.preventDefault(); alert('You must log in as Employer or Training Center to post an ad!'); window.location.href='login.php';">
   Post Ad
</a>


<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="nav-item dropdown ms-3">
  <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown"
     role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fa fa-user-circle fa-2x text-white"></i>
  </a>

<ul class="dropdown-menu dropdown-menu-end custom-dropdown" aria-labelledby="userDropdown">
  <?php if (!empty($_SESSION['user_id'])): ?>
    <li>
      <h6 class="dropdown-header">
        👋 Hello, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>
      </h6>
    </li>

    <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'jobseeker'): ?>
      <li><a class="dropdown-item" href="dashboard.php">My Dashboard</a></li>
    <?php elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'employer'): ?>
      <li><a class="dropdown-item" href="dashboard.php">Employer Panel</a></li>
    <?php elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'training_center'): ?>
      <li><a class="dropdown-item" href="dashboard.php">Training Center Panel</a></li>
    <?php endif; ?>

    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
  <?php else: ?>
    <li><a class="dropdown-item" href="login.php">Sign In</a></li>
    <li><a class="dropdown-item" href="register.php">Sign Up</a></li>
  <?php endif; ?>
</ul>

</div>

      </div>
    </div>
  </nav>
  <!-- Jobs Modal -->


</div>

<!-- Custom Styles -->
<style>
  /* Prevent overflow */
  body, html {
    overflow-x: hidden;
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

  .fa-user-circle {
    cursor: pointer;
    transition: transform 0.2s ease;
  }

  .fa-user-circle:hover {
    transform: scale(1.1);
  }

  

  .navbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
  }

  /* Make sure dropdown doesn't overflow screen */
  .dropdown-menu-end {
    right: 0 !important;
    left: auto !important;
  }
</style>

<!-- Font Awesome (for icons) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
