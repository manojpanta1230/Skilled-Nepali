<?php
include 'component/header.php';

// Fetch all employers (companies)
$query = "SELECT id, name, email, company as company_name, image as company_logo, status
          FROM users 
          WHERE role = 'employer' AND status = 'active'
          ORDER BY id DESC";

$result = $mysqli->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="img/bg-logo.jpg">

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registered Companies</title>

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

.card img {
    border-radius: 12px;
    width: 100%;
    height: 180px;
    object-fit: contain;
    background-color: #f4f4f4;
    padding: 10px;
}

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

.card-text { color: #444; }
</style>
</head>
<body>

<div class="container wow fadeInUp" data-wow-delay="0.2s">
    <h2 class="text-center mb-4" style="color:#00A098; font-weight:700;">Registered Companies</h2>

    <!-- Search bar -->
    <div class="mb-4 text-center">
        <input type="text" id="searchInput" class="search-bar" 
               placeholder="Search by company name, email or service..." 
               onkeyup="filterCards()">
    </div>

    <!-- No results message -->
    <p id="noResults" class="text-center fw-bold" style="color: red; display: none;">
        No such record found.
    </p>

    <div class="row" id="companyList">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($company = $result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4 company-card wow fadeInUp" data-wow-delay="0.3s">
                    <div class="card p-3 h-100">
                        <?php if (!empty($company['company_logo']) && file_exists($company['company_logo'])): ?>
                            <img src="<?= htmlspecialchars($company['company_logo']) ?>" alt="<?= htmlspecialchars($company['company_name']) ?>">
                        <?php else: ?>
                            <img src="img/image.png" alt="No Logo">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($company['company_name'] ?? $company['name']) ?></h5>
                            <p class="card-text mb-1"><strong>Email:</strong> <?= htmlspecialchars($company['email']) ?></p>
                            <span class="<?= $company['status'] == 'active' ? 'status-active' : 'status-inactive' ?>">
                                <?= ucfirst($company['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center text-muted">No companies found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function filterCards() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.company-card');
    const noResults = document.getElementById('noResults');
    let visibleCount = 0;

    cards.forEach(card => {
        const match = card.innerText.toLowerCase().includes(input);
        card.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });

    // Show red "No record found" message if no cards are visible
    noResults.style.display = (visibleCount === 0 && input.length > 0) ? 'block' : 'none';
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
