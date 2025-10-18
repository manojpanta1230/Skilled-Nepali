<?php 
include 'config.php'; // must be included first
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Portal</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    /* Keep navbar height stable */
    .navbar {
      position: sticky;
      top: 0;
      z-index: 1000;
      height: 70px; /* fixed navbar height */
      overflow: visible; /* allows logo to appear bigger */
    }

    /* ✅ Large logo without stretching navbar */
    .navbar-brand {
      position: relative;
      display: flex;
      align-items: center;
    }

    .navbar-brand img {
      height: 120px;        /* big logo */
      width: auto;
      object-fit: contain;
      position: absolute;   /* pull it out of navbar height */
      top: 50%;
      transform: translateY(-50%);
      left: 0;
    }

    
    .navbar .container-fluid {
      padding-left: 130px; /* give room for big logo */
    }

    
    .navbar-brand img {
      image-rendering: -webkit-optimize-contrast;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm"  style="background-color:#00A098;">
  <div class="container-fluid">
    <!-- ✅ Fixed logo on the left -->
    <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
      <img src="img/Logo.png" alt="JobPortal Logo">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center">
        <?php if(is_logged_in()): 
              $user = current_user();
              $displayName = !empty($user['company']) ? $user['company'] : $user['name'];
        ?>
          <!-- 👋 Welcome message inside navbar -->
          <li class="nav-item me-2">
            <span class="nav-link text-white">Welcome, <b><?= htmlspecialchars($displayName) ?></b></span>
          </li>

          <li class="nav-item me-2">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
          </li>

          <li class="nav-item">
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
          </li>

        <?php else: ?>
          <li class="nav-item me-2">
            <a href="login.php" class="btn btn-light btn-sm px-3 rounded-pill">Login</a>
          </li>
          <li class="nav-item">
            <a href="register.php" class="btn btn-warning btn-sm px-3 rounded-pill">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Main content -->
<div class="container mt-5">
  <?php if(is_logged_in()): ?>
 
  <?php endif; ?>

  <h2 class="text-center mt-4">Latest Jobs</h2>
  <p class="text-center text-muted">Your dream career starts here.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
