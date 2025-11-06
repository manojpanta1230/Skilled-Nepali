<?php
include 'component/header.php';

// Fetch only job seekers
$query = "SELECT id, name, email, application_for, experience_years, past_experience, status 
          FROM users 
          WHERE role = 'jobseeker' 
          ORDER BY id DESC";
$result = $mysqli->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registered Job Seekers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Include main CSS and animations -->
<link href="lib/animate/animate.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">

<style>
.container { margin-top: 80px; }
.card {
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
}
.card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.15); }
.card-title { color: #00A098; font-weight: 700; }
.status-active {
    background-color: #d4edda; color: #155724;
    padding: 5px 12px; border-radius: 20px; font-weight: 600;
}
.status-inactive {
    background-color: #f8d7da; color: #721c24;
    padding: 5px 12px; border-radius: 20px; font-weight: 600;
}
.search-bar {
    border-radius: 30px;
    padding: 10px 20px;
    border: 1px solid #00A098;
    width: 100%; max-width: 400px;
}
</style>
</head>
<body>

<div class="container wow fadeInUp" data-wow-delay="0.2s">
    <h2 class="text-center mb-4" style="color:#00A098; font-weight:700;">Registered Job Seekers</h2>

    <div class="mb-4 text-center">
        <input type="text" id="searchInput" class="search-bar" placeholder="Search by name, email or application..." onkeyup="filterCards()">
    </div>

    <div class="row" id="jobSeekerList">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($user = $result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4 job-card wow fadeInUp" data-wow-delay="0.3s">
                    <div class="card p-3 h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($user['name']) ?></h5>
                            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                            <p><strong>Application For:</strong> <?= htmlspecialchars($user['application_for'] ?? 'N/A') ?></p>
                            <p><strong>Experience:</strong> <?= htmlspecialchars($user['experience_years'] ?? '0') ?> year(s)</p>
                            <p><strong>Past Experience:</strong><br><?= nl2br(htmlspecialchars($user['past_experience'] ?? 'Not provided')) ?></p>
                         
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center text-muted">No job seekers found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function filterCards() {
  let input = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('.job-card').forEach(card => {
    card.style.display = card.innerText.toLowerCase().includes(input) ? '' : 'none';
  });
}
</script>

<!-- Animation JS -->
<script src="lib/wow/wow.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="js/main.js"></script>
<script>
  new WOW().init();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
 