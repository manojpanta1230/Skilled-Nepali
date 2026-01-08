<?php
include './component/header.php';


/* -------------------------
    FILTER VALUES
------------------------- */
$search     = isset($_GET['search']) ? $mysqli->real_escape_string($_GET['search']) : '';
$location   = isset($_GET['country']) ? $mysqli->real_escape_string($_GET['country']) : '';
$type       = isset($_GET['type']) ? $mysqli->real_escape_string($_GET['type']) : '';
$category   = isset($_GET['category']) ? $mysqli->real_escape_string($_GET['category']) : '';
$duration   = isset($_GET['duration']) ? $mysqli->real_escape_string($_GET['duration']) : '';

/* -------------------------
    FETCH CATEGORIES
------------------------- */
$categories = $mysqli->query("
    SELECT DISTINCT category 
    FROM jobs 
    WHERE status='approved'
");

/* -------------------------
    JOB QUERY
------------------------- */
$jobQuery = "
    SELECT j.*, u.name AS employer, u.company, u.image AS logo
    FROM jobs j
    JOIN users u ON j.employer_id = u.id
    WHERE j.status='approved'
";

if ($search !== '') {
    $jobQuery .= " AND j.title LIKE '%$search%'";
}

if ($location !== '') {
    $jobQuery .= " AND j.country LIKE '%$location%'";
}

if ($category !== '') {
    $jobQuery .= " AND j.category = '$category'";
}

$jobQuery .= " ORDER BY j.id DESC";
$jobs = $mysqli->query($jobQuery);
$totalJobs = $mysqli->query("SELECT COUNT(*) as total FROM jobs WHERE status='approved'")->fetch_assoc()['total'];

/* -------------------------
    TRAINING QUERY
------------------------- */
$courseFilters = [];

// Filter by search keyword only (no country)
if ($search !== '') {
    $courseFilters[] = "c.title LIKE '%$search%'";
}

// Start building the query
$courseQuery = "
    SELECT c.*, u.company, u.image AS logo
    FROM courses c
    JOIN users u ON c.training_center_id = u.id
    WHERE c.status='approved'
";

// Add search filters dynamically
if (count($courseFilters) > 0) {
    $courseQuery .= " AND " . implode(' AND ', $courseFilters);
}

$courseQuery .= " ORDER BY c.id DESC";

$courses = $mysqli->query($courseQuery);
$totalCourses = $mysqli->query("SELECT COUNT(*) as total FROM courses WHERE status='approved'")->fetch_assoc()['total'];


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title> All Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<link href="lib/animate/animate.min.css" rel="stylesheet"></style>
<link rel="icon" href="img/Logo.png" type="image/x-icon">
<style>
:root {
    --primary-color: #00A098;
    --primary-dark: #008c84;
    --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-4: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-md: 0 8px 24px rgba(0,0,0,0.12);
    --shadow-lg: 0 16px 48px rgba(0,0,0,0.18);
}

body{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
.nav-bar {
    margin-bottom: 0 !important;
}
.hero-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    padding: 50px 0 100px;
    margin: 0 0 -60px 0; /* Changed this line */
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.hero-title {
    color: white;
    font-weight: 800;
    font-size: 2.8rem;
    margin: 0;
    position: relative;
    z-index: 1;
    text-align: center;
}

.hero-subtitle {
    color: rgba(255,255,255,0.9);
    font-size: 1.2rem;
    margin-top: 15px;
    position: relative;
    z-index: 1;
    text-align: center;
}

.stats-row {
    display: flex;
    gap: 20px;
    margin-top: 40px;
    justify-content: center;
    position: relative;
    z-index: 1;
    flex-wrap: wrap;
}

.stat-box {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    padding: 25px 40px;
    border-radius: 15px;
    text-align: center;
    box-shadow: var(--shadow-sm);
}

.stat-number {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--primary-color);
    line-height: 1;
}

.stat-label {
    color: #666;
    font-size: 0.95rem;
    margin-top: 8px;
}

.filter-section {
    background: white;
    padding: 35px;
    border-radius: 20px;
    box-shadow: var(--shadow-md);
    margin-bottom: 40px;
    position: relative;
}

.filter-section .form-control,
.filter-section .form-select {
    border-radius: 12px;
    padding: 14px 18px;
    border: 2px solid #e0e0e0;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.filter-section .form-control:focus,
.filter-section .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(0,160,152,0.15);
    background: white;
    outline: none;
}

.btn-search {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border: none;
    border-radius: 12px;
    padding: 14px 24px;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,160,152,0.3);
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,160,152,0.4);
}

.tabs-container {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 40px;
}

.tab-btn {
    background: white;
    border: 2px solid #e0e0e0;
    padding: 15px 40px;
    border-radius: 15px;
    font-weight: 600;
    color: #666;
    transition: all 0.3s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: var(--shadow-sm);
}

.tab-btn:hover {
    transform: translateY(-2px);
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.tab-btn.active {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-color: var(--primary-color);
    color: white;
    box-shadow: 0 6px 20px rgba(0,160,152,0.3);
}

.card-box {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: var(--shadow-sm);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.card-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient-1);
}

.card-box:nth-child(4n+2)::before {
    background: var(--gradient-2);
}

.card-box:nth-child(4n+3)::before {
    background: var(--gradient-3);
}

.card-box:nth-child(4n+4)::before {
    background: var(--gradient-4);
}

.card-box:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: var(--shadow-lg);
}

.company-logo {
    width: 80px;
    height: 80px;
    object-fit: contain;
    border-radius: 15px;
    border: 2px solid #f0f0f0;
    padding: 8px;
    background: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.card-box:hover .company-logo {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}

.flex-box {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.job-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 12px;
    line-height: 1.3;
}

.training-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #10b981;
    margin-bottom: 12px;
    line-height: 1.3;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.info-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.info-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.info-text {
    color: #1a1a1a;
    font-size: 0.95rem;
    font-weight: 500;
}

.company-name {
    font-weight: 700;
    color: var(--primary-color);
}

.training-description {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px solid #f0f0f0;
    color: #666;
    line-height: 1.6;
    font-size: 0.95rem;
}

.badge-new {
    position: absolute;
    top: 15px;
    right: 15px;
    background: orange;
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(255,107,107,0.3);
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

.salary-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #009991;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-top: 10px;
}

.cost-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-top: 10px;
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .stats-row {
        flex-direction: column;
    }
    
    .flex-box {
        flex-direction: column;
        text-align: center;
    }
    
    .company-logo {
        margin: 0 auto;
    }
}
</style>
</head>
<body>

<div class="hero-section">
    <div class="container">
        <h1 class="hero-title">
            <i class="fas fa-briefcase"></i> Discover Opportunities
        </h1>
        <p class="hero-subtitle">Find your dream job or enhance your skills with top training programs</p>
        
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-number"><?= $totalJobs ?></div>
                <div class="stat-label">Active Jobs</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $totalCourses ?></div>
                <div class="stat-label">Training Programs</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <i class="fas fa-globe" style="font-size: 1.6rem;"></i>
                </div>
                <div class="stat-label">GCC Countries</div>
            </div>
        </div>
    </div>
</div>

<div class="container" style="margin-top: 30px;">
    
    <!-- FILTER SECTION -->
    <div class="filter-section">
        <form method="GET">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control"
                               placeholder="Search opportunities..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

             <?php if ($type != 'training'): ?>
<div class="col-lg-3 col-md-6">
    <select name="country" class="form-select">
        <option value="">📍 All Locations</option>
        <?php
        $gcc_countries = ['United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Oman', 'Bahrain'];
        foreach ($gcc_countries as $country):
        ?>
            <option value="<?= $country ?>" <?= ($location == $country) ? 'selected' : '' ?>>
                <?= $country ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php endif; ?>


                <div class="col-lg-2 col-md-6">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php 
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?= $cat['category'] ?>" 
                                <?= ($category == $cat['category']) ? 'selected' : '' ?>>
                                <?= $cat['category'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <select name="type" class="form-select">
                        <option value=""> All Types</option>
                        <option value="job" <?= ($type == "job" ? "selected" : "") ?>>Jobs</option>
                        <option value="training" <?= ($type == "training" ? "selected" : "") ?>>Training</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-12">
                    <button type="submit" class="btn btn-search w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- TABS -->
    <div class="tabs-container">
        <button class="tab-btn <?= ($type != 'training') ? 'active' : '' ?>"
                onclick="window.location='?type=job'">
            <i class="fas fa-briefcase"></i>
            <span>Jobs (<?= $totalJobs ?>)</span>
        </button>
        <button class="tab-btn <?= ($type == 'training') ? 'active' : '' ?>"
                onclick="window.location='?type=training'">
            <i class="fas fa-graduation-cap"></i>
            <span>Training (<?= $totalCourses ?>)</span>
        </button>
    </div>

    <!-- RESULTS -->
    <div class="row g-4">

    <?php
    /* ===========================
        JOB CARDS
    ============================ */
    if ($type != "training"):

        if ($jobs->num_rows > 0):
            while ($job = $jobs->fetch_assoc()):
                $logo = (!empty($job['logo']) && file_exists($job['logo'])) ? $job['logo'] : 'assets/default_logo.png';
    ?>
        <div class="col-lg-4 col-md-6">
            <div class="card-box" onclick="window.location='job_details.php?id=<?= $job['id'] ?>'">
                <span class="badge-new">
                    <i class="fas fa-star"></i> New
                </span>
                
                <div class="flex-box">
                    <img src="<?= $logo ?>" class="company-logo" alt="<?= $job['company'] ?>">

                    <div style="flex: 1;">
                        <h5 class="job-title"><?= $job['title'] ?></h5>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <span class="info-text company-name"><?= $job['company'] ?></span>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <span class="info-text"><?= $job['country'] ?></span>
                        </div>

                        <div class="salary-badge">
                          
                            <?= $job['salary'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
            endwhile;
        else:
            echo '<div class="col-12">
                    <div class="no-results">
                        <i class="fas fa-briefcase"></i>
                        <p class="no-results-text">No jobs found matching your criteria</p>
                    </div>
                  </div>';
        endif;

    endif;


    /* ===========================
        TRAINING CARDS
    ============================ */
    if ($type == "training"):

        if ($courses->num_rows > 0):
            while ($course = $courses->fetch_assoc()):
                $logo = (!empty($course['logo']) && file_exists($course['logo'])) ? $course['logo'] : 'assets/default_logo.png';
    ?>
    <div class="col-lg-4 col-md-6">
    <a href="course_details.php?id=<?= $course['id'] ?>" class="card-link" style="text-decoration: none; color: inherit;">
        <div class="card-box">
            <div class="flex-box">
                <img src="<?= $logo ?>" class="company-logo" alt="<?= $course['company'] ?>">

                <div style="flex: 1;">
                    <h5 class="training-title"><?= $course['title'] ?></h5>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <span class="info-text company-name"><?= $course['company'] ?></span>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <span class="info-text"><?= $course['duration'] ?></span>
                    </div>

                    <div class="cost-badge">
                        <i class="fas fa-tag"></i>
                        $<?= $course['cost'] ?>
                    </div>
                </div>
            </div>

            <div class="training-description">
                <?= substr($course['structure'], 0, 120) ?>...
            </div>
        </div>
    </a>
</div>
    <?php
            endwhile;
        else:
            echo '<div class="col-12">
                    <div class="no-results">
                        <i class="fas fa-graduation-cap"></i>
                        <p class="no-results-text">No training programs found matching your criteria</p>
                    </div>
                  </div>';
        endif;

    endif;
    ?>

    </div>
</div>

</body>
</html>

<?php include 'portal_footer.php'; ?>