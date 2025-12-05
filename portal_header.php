<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <link rel="icon" type="image/x-icon" href="img/Logo.png">
  <title>Job Portal</title>
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <style>
    .navbar {
      background-color: #00A098;
      position: sticky;
      top: 0;
      z-index: 1050;
      transition: all 0.3s ease;
    }

    .navbar-brand img {
      height: 100px;
      width: auto;
      object-fit: contain;
      transition: 0.3s;
      margin-left: -10px;
    }

    .navbar .container-fluid {
      padding-left: 110px;
    }

    @media (max-width: 992px) {
      .navbar-brand img {
        height: 70px;
        margin-left: 0;
      }
      .navbar .container-fluid {
        padding-left: 15px;
      }
      .navbar-collapse {
        background-color: #00A098;
        border-radius: 0 0 10px 10px;
      }
      /* hide desktop login/register in mobile */
      .desktop-auth {
        display: none !important;
      }
      /* mobile auth div styles */
      #mobileAuth {
        background-color: #00A098;
        display: none;
        padding: 15px;
        z-index: 1000;
        text-align: center;
        border-top: 1px solid rgba(255,255,255,0.2);
        gap: 20px;
      }
      #mobileAuth .btn {
        width: 80%;
        margin-bottom: 10px;
      }
    }

    @media (min-width: 993px) {
      #mobileAuth { display: none !important; }
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php">
      <img src="img/Logo.png" alt="JobPortal Logo">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Desktop menu -->
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-lg-center">
        <?php if(is_logged_in()):
              $user = current_user();
              $displayName = !empty($user['company']) ? $user['company'] : $user['name'];
              
              // Determine which dashboard link to show based on user role
              $dashboard_link = "dashboard.php"; // Default
              
              if (is_admin()) {
                  $dashboard_link = "admin_panel.php";
              } elseif (is_employer()) {
                  $dashboard_link = "dashboard_employer.php";
              } elseif (is_training_center()) {
                  $dashboard_link = "dashboard_training.php";
              } elseif (is_jobseeker()) {
                  $dashboard_link = "jobseeker_panel.php";
              }
        ?>
          <li class="nav-item me-2">
            <span class="nav-link text-white">Welcome, <b><?= htmlspecialchars($displayName) ?></b></span>
          </li>
          <li class="nav-item me-2 desktop-auth">
            <a href="<?= $dashboard_link ?>" class="btn btn-outline-light btn-sm">Dashboard</a>
          </li>
          <li class="nav-item desktop-auth">
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item me-2 desktop-auth">
            <a href="login.php" class="btn btn-light btn-sm px-3 rounded-pill">Login</a>
          </li>
          <li class="nav-item desktop-auth">
            <a href="register.php" class="btn btn-warning btn-sm px-3 rounded-pill">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- ✅ Separate mobile div for login/register -->
<div id="mobileAuth">
  <?php if(is_logged_in()):
        $user = current_user();
        $displayName = !empty($user['company']) ? $user['company'] : $user['name'];
        
        // Determine which dashboard link to show based on user role
        $dashboard_link = "dashboard.php"; // Default
        
        if (is_admin()) {
            $dashboard_link = "admin_panel.php";
        } elseif (is_employer()) {
            $dashboard_link = "dashboard_employer.php";
        } elseif (is_training_center()) {
            $dashboard_link = "dashboard_training.php";
        } elseif (is_jobseeker()) {
            $dashboard_link = "jobseeker_panel.php";
        }
  ?>
    <a href="<?= $dashboard_link ?>" class="btn btn-outline-light btn-sm">Dashboard</a>
    <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
  <?php else: ?>
    <a href="login.php" class="btn btn-light btn-sm px-3 rounded-pill">Login</a>
    <a href="register.php" class="btn btn-warning btn-sm px-3 rounded-pill">Register</a>
  <?php endif; ?>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const toggler = document.querySelector(".navbar-toggler");
  const mobileAuth = document.getElementById("mobileAuth");

  toggler.addEventListener("click", () => {
    // Toggle visibility on each click
    if (mobileAuth.style.display === "flex") {
      mobileAuth.style.display = "none";
    } else {
      mobileAuth.style.display = "flex";
    }
  });

  // Optional: hide mobileAuth when a link inside it is clicked
  document.querySelectorAll("#mobileAuth a").forEach(link => {
    link.addEventListener("click", () => {
      mobileAuth.style.display = "none";
    });
  });
});
</script>



</body>
</html>