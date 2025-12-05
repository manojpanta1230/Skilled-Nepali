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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/x-icon" href="img/bg-logo.jpg">
<title>Registered Companies</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="lib/animate/animate.min.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">

<style>
:root {
    --primary-color: #00A098;
    --primary-dark: #008c84;
    --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-4: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-md: 0 8px 24px rgba(0,0,0,0.12);
    --shadow-lg: 0 16px 48px rgba(0,0,0,0.18);
}

body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.dashboard-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    padding: 40px 0 80px;
    margin-bottom: -50px;
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.1;
}

.dashboard-title {
    color: white;
    font-weight: 800;
    font-size: 2.5rem;
    margin: 0;
    position: relative;
    z-index: 1;
}

.dashboard-subtitle {
    color: rgba(255,255,255,0.9);
    font-size: 1.1rem;
    margin-top: 10px;
    position: relative;
    z-index: 1;
}

.stats-container {
    display: flex;
    gap: 20px;
    margin-top: 30px;
    position: relative;
    z-index: 1;
    flex-wrap: wrap;
}

.stat-card {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    padding: 20px 30px;
    border-radius: 15px;
    flex: 1;
    min-width: 150px;
    box-shadow: var(--shadow-sm);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
    line-height: 1;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
    margin-top: 5px;
}

.container { margin-top: 0; padding-top: 20px; }

.search-section {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: var(--shadow-md);
    margin-bottom: 30px;
}

.search-bar-wrapper {
    position: relative;
    max-width: 600px;
    margin: 0 auto;
}

.search-bar {
    border-radius: 50px;
    padding: 16px 24px 16px 56px;
    border: 2px solid #e0e0e0;
    width: 100%;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.search-bar:focus {
    outline: none;
    border-color: var(--primary-color);
    background: white;
    box-shadow: 0 4px 12px rgba(0,160,152,0.15);
}

.search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 1.2rem;
}

#noResults {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
    padding: 20px;
    border-radius: 15px;
    font-weight: 600;
    box-shadow: var(--shadow-sm);
    margin: 20px 0;
    display: none;
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.company-card {
    margin-bottom: 24px;
    transition: all 0.3s ease;
}

.card {
    border-radius: 20px;
    border: none;
    box-shadow: var(--shadow-sm);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    overflow: hidden;
    background: white;
    height: 100%;
    position: relative;
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: var(--gradient-1);
}

.card:nth-child(4n+2)::before {
    background: var(--gradient-2);
}

.card:nth-child(4n+3)::before {
    background: var(--gradient-3);
}

.card:nth-child(4n+4)::before {
    background: var(--gradient-4);
}

.card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: var(--shadow-lg);
}

.card-img-wrapper {
    position: relative;
    padding: 30px 30px 20px;
    background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
}

.card img {
    border-radius: 15px;
    width: 100%;
    height: 200px;
    object-fit: contain;
    background: white;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.card:hover img {
    transform: scale(1.05);
}

.card-body {
    padding: 25px 30px 30px;
}

.card-title {
    color: #1a1a1a;
    font-weight: 700;
    font-size: 1.3rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-title i {
    color: var(--primary-color);
    font-size: 1.1rem;
}

.status-active {
    background: linear-gradient(135deg, #52c234 0%, #61d345 100%);
    color: white;
    padding: 8px 20px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(82,194,52,0.3);
}

.status-active i {
    font-size: 0.8rem;
}

.status-inactive {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
    padding: 8px 20px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(255,107,107,0.3);
}

.status-inactive i {
    font-size: 0.8rem;
}

.company-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.no-results {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 20px;
    box-shadow: var(--shadow-sm);
}

.no-results i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
}

.no-results-text {
    color: #999;
    font-size: 1.2rem;
}

.view-details-btn {
    width: 100%;
    margin-top: 15px;
    padding: 12px;
    border: 2px solid var(--primary-color);
    background: white;
    color: var(--primary-color);
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
}

.view-details-btn:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,160,152,0.3);
}

@media (max-width: 768px) {
    .dashboard-title {
        font-size: 1.8rem;
    }
    
    .stats-container {
        flex-direction: column;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .card img {
        height: 160px;
    }
}

/* Loading animation */
@keyframes shimmer {
    0% { background-position: -1000px 0; }
    100% { background-position: 1000px 0; }
}

.skeleton {
    animation: shimmer 2s infinite;
    background: linear-gradient(to right, #f0f0f0 4%, #e0e0e0 25%, #f0f0f0 36%);
    background-size: 1000px 100%;
}
</style>
</head>
<body>

<div class="dashboard-header">
    <div class="container">
        <h1 class="dashboard-title">
            <i class="fas fa-building"></i> Companies Dashboard
        </h1>
        <p class="dashboard-subtitle">Browse registered companies and their profiles</p>
        
        <div class="stats-container">
            <div class="stat-card wow fadeInUp" data-wow-delay="0.1s">
                <div class="stat-number"><?= $result ? $result->num_rows : 0 ?></div>
                <div class="stat-label">Active Companies</div>
            </div>
            <div class="stat-card wow fadeInUp" data-wow-delay="0.2s">
                <div class="stat-number">
                    <i class="fas fa-chart-pie" style="font-size: 1.5rem;"></i>
                </div>
                <div class="stat-label">Industries</div>
            </div>
            <div class="stat-card wow fadeInUp" data-wow-delay="0.3s">
                <div class="stat-number">
                    <i class="fas fa-briefcase" style="font-size: 1.5rem;"></i>
                </div>
                <div class="stat-label">Job Openings</div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="search-section wow fadeInUp" data-wow-delay="0.2s">
        <div class="search-bar-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-bar" 
                   placeholder="Search by company name, email or service..." 
                   onkeyup="filterCards()">
        </div>
    </div>

    <!-- No results message -->
    <p id="noResults">
        <i class="fas fa-exclamation-circle"></i> No matching companies found. Try a different search term.
    </p>

    <div class="row" id="companyList">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php 
            $delay = 0.3;
            while ($company = $result->fetch_assoc()): 
            ?>
                <div class="col-lg-4 col-md-6 company-card wow fadeInUp" data-wow-delay="<?= $delay ?>s">
                    <div class="card">
                        <div class="card-img-wrapper">
                            <span class="company-badge">
                                <i class="fas fa-certificate"></i> Verified
                            </span>
                            <?php if (!empty($company['company_logo']) && file_exists($company['company_logo'])): ?>
                                <img src="<?= htmlspecialchars($company['company_logo']) ?>" 
                                     alt="<?= htmlspecialchars($company['company_name']) ?>">
                            <?php else: ?>
                                <img src="img/image.png" alt="No Logo">
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-building"></i>
                                <?= htmlspecialchars($company['company_name'] ?? $company['name']) ?>
                            </h5>
                            
                            <span class="<?= $company['status'] == 'active' ? 'status-active' : 'status-inactive' ?>">
                                <i class="fas fa-circle"></i>
                                <?= ucfirst($company['status']) ?>
                            </span>
                            
                            <button class="view-details-btn">
                                <i class="fas fa-info-circle"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
            <?php 
                $delay += 0.1;
                if ($delay > 0.6) $delay = 0.3;
            endwhile; 
            ?>
        <?php else: ?>
            <div class="col-12">
                <div class="no-results">
                    <i class="fas fa-building-slash"></i>
                    <p class="no-results-text">No companies found</p>
                </div>
            </div>
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

    // Show/hide "No record found" message
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