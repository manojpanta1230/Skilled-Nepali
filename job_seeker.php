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
<link rel="shortcut icon" href="img/Logo.png" type="image/x-icon">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Registered Job Seekers</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Poppins:wght@200;300;400;500;600&display=swap" rel="stylesheet">
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
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-md: 0 8px 24px rgba(0,0,0,0.12);
    --shadow-lg: 0 16px 48px rgba(0,0,0,0.18);
}

body {
    font-family: 'Poppins', 'Open Sans', sans-serif;
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

.job-card {
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

.card:nth-child(3n+2)::before {
    background: var(--gradient-2);
}

.card:nth-child(3n+3)::before {
    background: var(--gradient-3);
}

.card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: var(--shadow-lg);
}

.card-body {
    padding: 30px;
}

.card-title {
    color: #1a1a1a;
    font-weight: 700;
    font-size: 1.4rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-title i {
    color: var(--primary-color);
    font-size: 1.2rem;
}

.info-row {
    display: flex;
    align-items: flex-start;
    margin-bottom: 16px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.info-row:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.info-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
}

.info-icon i {
    color: white;
    font-size: 1rem;
}

.info-content {
    flex: 1;
}

.info-label {
    font-weight: 600;
    color: #666;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.info-text {
    color: #1a1a1a;
    font-size: 1rem;
    line-height: 1.6;
    margin: 0;
}

.experience-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #009088;
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.95rem;
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

.filter-chips {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
    margin-top: 20px;
}

.chip {
    padding: 8px 20px;
    border-radius: 25px;
    border: 2px solid var(--primary-color);
    background: white;
    color: var(--primary-color);
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    font-size: 0.9rem;
}

.chip:hover, .chip.active {
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
            <i class="fas fa-users-cog"></i> Job Seekers Dashboard
        </h1>
        <p class="dashboard-subtitle">Manage and review all registered candidates</p>
        
        <div class="stats-container">
            <div class="stat-card wow fadeInUp" data-wow-delay="0.1s">
                <div class="stat-number"><?= $result ? $result->num_rows : 0 ?></div>
                <div class="stat-label">Total Candidates</div>
            </div>
            <div class="stat-card wow fadeInUp" data-wow-delay="0.2s">
                <div class="stat-number">
                    <i class="fas fa-chart-line" style="font-size: 1.5rem;"></i>
                </div>
                <div class="stat-label">Active Today</div>
            </div>
            <div class="stat-card wow fadeInUp" data-wow-delay="0.3s">
                <div class="stat-number">
                    <i class="fas fa-clock" style="font-size: 1.5rem;"></i>
                </div>
                <div class="stat-label">Recent Updates</div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="search-section wow fadeInUp" data-wow-delay="0.2s">
        <div class="search-bar-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-bar" 
                   placeholder="Search by name, position, experience..." 
                   onkeyup="filterCards()">
        </div>
        
        <div class="filter-chips">
            <div class="chip active" onclick="filterByExperience('all')">
                <i class="fas fa-th"></i> All
            </div>
            <div class="chip" onclick="filterByExperience('0-2')">
                <i class="fas fa-seedling"></i> Entry Level
            </div>
            <div class="chip" onclick="filterByExperience('3-5')">
                <i class="fas fa-user-tie"></i> Mid Level
            </div>
            <div class="chip" onclick="filterByExperience('6+')">
                <i class="fas fa-crown"></i> Senior
            </div>
        </div>
    </div>

    <div class="row" id="jobSeekerList">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($user = $result->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6 job-card wow fadeInUp" 
                     data-wow-delay="0.3s" 
                     data-experience="<?= htmlspecialchars($user['experience_years'] ?? '0') ?>">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-user-circle"></i>
                                <?= htmlspecialchars($user['name']) ?>
                            </h5>
                            
                            <div class="info-row">
                                <div class="info-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Application For</div>
                                    <div class="info-text"><?= htmlspecialchars($user['application_for'] ?? 'N/A') ?></div>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-icon">
                                    <i class="fas fa-award"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Experience</div>
                                    <div class="info-text">
                                        <span class="experience-badge">
                                            <i class="fas fa-star"></i>
                                            <?= htmlspecialchars($user['experience_years'] ?? '0') ?> year(s)
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Past Experience</div>
                                    <div class="info-text"><?= nl2br(htmlspecialchars($user['past_experience'] ?? 'Not provided')) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="no-results">
                    <i class="fas fa-user-slash"></i>
                    <p class="no-results-text">No job seekers found</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterCards() {
    let input = document.getElementById('searchInput').value.toLowerCase();
    let cards = document.querySelectorAll('.job-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        if (card.innerText.toLowerCase().includes(input)) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
}

function filterByExperience(level) {
    // Update active chip
    document.querySelectorAll('.chip').forEach(chip => chip.classList.remove('active'));
    event.target.closest('.chip').classList.add('active');
    
    let cards = document.querySelectorAll('.job-card');
    
    cards.forEach(card => {
        let experience = parseInt(card.getAttribute('data-experience'));
        let show = false;
        
        if (level === 'all') {
            show = true;
        } else if (level === '0-2' && experience <= 2) {
            show = true;
        } else if (level === '3-5' && experience >= 3 && experience <= 5) {
            show = true;
        } else if (level === '6+' && experience >= 6) {
            show = true;
        }
        
        card.style.display = show ? '' : 'none';
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