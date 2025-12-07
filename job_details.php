<?php
include 'portal_header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id'])) {
    die("Invalid Job Request");
}

$job_id = intval($_GET['id']);

$job = $mysqli->query("
    SELECT j.*, u.company, u.image AS logo
    FROM jobs j
    JOIN users u ON j.employer_id = u.id
    WHERE j.id=$job_id
")->fetch_assoc();

if (!$job) {
    die("Job Not Found");
}

$logo = (!empty($job['logo']) && file_exists($job['logo'])) 
        ? $job['logo'] : "assets/default_logo.png";

// Function to check if string is empty
function isEmptyString($str) {
    return empty(trim($str));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link rel="icon" href="/img/Logo.png?v=1" type="image/png">


<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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

.job-header {
    position: relative;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    padding: 80px 0 100px;
    margin-bottom: -60px;
    overflow: hidden;
}

.job-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.1;
}

.job-header-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 30px;
}

.company-logo-wrapper {
    flex-shrink: 0;
}

.company-logo {
    width: 120px;
    height: 120px;
    object-fit: contain;
    border-radius: 20px;
    background: white;
    padding: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    border: 4px solid rgba(255,255,255,0.3);
}

.job-info {
    flex: 1;
    color: white;
}

.job-title {
    font-size: 2.8rem;
    font-weight: 800;
    margin: 0 0 15px 0;
    color: white;
    line-height: 1.2;
}

.company-name {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: rgba(255,255,255,0.95);
}

.badge-category {
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 0.95rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 2px solid rgba(255,255,255,0.3);
}

.job-meta-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: var(--shadow-md);
    margin-bottom: 24px;
    position: sticky;
    top: 20px;
}

.section-title {
    color: #1a1a1a;
    font-weight: 700;
    font-size: 1.5rem;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 3px solid var(--primary-color);
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: var(--primary-color);
}

.job-meta-item {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 15px;
    transition: all 0.3s ease;
}

.job-meta-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.job-meta-item:last-child {
    margin-bottom: 0;
}

.meta-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 18px;
    flex-shrink: 0;
    color: white;
    font-size: 1.2rem;
}

.meta-content h6 {
    margin: 0 0 5px 0;
    font-size: 0.8rem;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.meta-content p {
    margin: 0;
    font-weight: 700;
    color: #1a1a1a;
    font-size: 1.05rem;
}

.content-card {
    background: white;
    border-radius: 20px;
    padding: 35px;
    margin-bottom: 25px;
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: hidden;
}

.content-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient-1);
}

.content-card:nth-child(2)::before {
    background: var(--gradient-2);
}

.content-card:nth-child(3)::before {
    background: var(--gradient-3);
}

.content-card:nth-child(4)::before {
    background: var(--gradient-4);
}

.content-card p {
    color: #4a5568;
    line-height: 1.8;
    margin-bottom: 1rem;
    font-size: 1.05rem;
}

.content-card ul, .content-card ol {
    padding-left: 25px;
    margin-bottom: 1.5rem;
}

.content-card li {
    margin-bottom: 12px;
    color: #4a5568;
    line-height: 1.7;
    font-size: 1.05rem;
}

.content-card li::marker {
    color: var(--primary-color);
    font-weight: bold;
}

.skills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.skill-badge {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #dee2e6;
    color: #495057;
    padding: 12px 20px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.skill-badge:hover {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,160,152,0.3);
}

.skill-badge i {
    font-size: 0.8rem;
}

.apply-section {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: var(--shadow-lg);
    text-align: center;
    margin: 40px 0;
    position: relative;
    overflow: hidden;
}

.apply-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
}

.apply-btn {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    border: none;
    padding: 18px 50px;
    font-size: 1.2rem;
    font-weight: 700;
    border-radius: 50px;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 8px 24px rgba(255,107,107,0.4);
    display: inline-flex;
    align-items: center;
    gap: 12px;
}

.apply-btn:hover {
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 12px 32px rgba(255,107,107,0.5);
}

.login-btn {
    background: white;
    color: var(--primary-color);
    border: 3px solid var(--primary-color);
    padding: 18px 50px;
    font-size: 1.2rem;
    font-weight: 700;
    border-radius: 50px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 12px;
}

.login-btn:hover {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    border-color: var(--primary-color);
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,160,152,0.4);
}

.apply-note {
    margin-top: 20px;
    color: #666;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.apply-note i {
    color: var(--primary-color);
}

.back-button {
    position: fixed;
    top: 100px;
    left: 20px;
    background: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-md);
    color: var(--primary-color);
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 1000;
    text-decoration: none;
}

.back-button:hover {
    background: var(--primary-color);
    color: white;
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .job-header {
        padding: 50px 0 80px;
    }
    
    .job-header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .company-logo {
        width: 100px;
        height: 100px;
    }
    
    .job-title {
        font-size: 2rem;
    }
    
    .company-name {
        font-size: 1.2rem;
    }
    
    .job-meta-card {
        position: relative;
        top: 0;
    }
    
    .back-button {
        top: 80px;
        left: 15px;
        width: 45px;
        height: 45px;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-in {
    animation: fadeInUp 0.6s ease-out;
}
</style>
</head>
<body>



<!-- Job Header -->
<div class="job-header">
    <div class="container">
        <div class="job-header-content">
            <div class="company-logo-wrapper">
                <img src="<?= $logo ?>" 
                     alt="<?= htmlspecialchars($job['company']) ?> Logo" 
                     class="company-logo">
            </div>
            <div class="job-info">
                <h1 class="job-title"><?= htmlspecialchars($job['title']) ?></h1>
                <div class="company-name">
                    <i class="fas fa-building"></i>
                    <?= htmlspecialchars($job['company']) ?>
                </div>
                <?php if (!empty($job['category'])): ?>
                    <span class="badge-category">
                        <i class="fas fa-tag"></i>
                        <?= htmlspecialchars($job['category']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <!-- Left Column: Job Meta -->
        <div class="col-lg-4">
            <div class="job-meta-card animate-in">
                <h3 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Job Details
                </h3>
                
                <!-- Location -->
                <div class="job-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="meta-content">
                        <h6>Location</h6>
                        <p><?= htmlspecialchars($job['country']) ?></p>
                    </div>
                </div>
                
                <!-- Salary -->
                <?php if (!isEmptyString($job['salary'])): ?>
                <div class="job-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="meta-content">
                        <h6>Salary Range</h6>
                        <p><?= htmlspecialchars($job['salary']) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Category -->
                <?php if (!isEmptyString($job['category'])): ?>
                <div class="job-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="meta-content">
                        <h6>Category</h6>
                        <p><?= htmlspecialchars($job['category']) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Experience Level -->
                <?php if (!empty($job['experience_level'])): ?>
                <div class="job-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="meta-content">
                        <h6>Experience Level</h6>
                        <p><?= htmlspecialchars($job['experience_level']) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Posted Date -->
                <div class="job-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="meta-content">
                        <h6>Posted</h6>
                        <p>Recently</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Job Content -->
        <div class="col-lg-8">
            <!-- Job Description -->
            <div class="content-card animate-in">
                <h3 class="section-title">
                    <i class="fas fa-file-alt"></i>
                    Job Description
                </h3>
                <?php if (!isEmptyString($job['description'])): ?>
                    <div class="job-description">
                        <?= nl2br(htmlspecialchars($job['description'])) ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No job description provided.</p>
                <?php endif; ?>
            </div>
            
            <!-- Requirements -->
            <?php if (!empty($job['requirements'])): ?>
            <div class="content-card animate-in">
                <h3 class="section-title">
                    <i class="fas fa-list-check"></i>
                    Requirements
                </h3>
                <div class="requirements">
                    <?= nl2br(htmlspecialchars($job['requirements'])) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Benefits -->
            <?php if (!empty($job['benefits'])): ?>
            <div class="content-card animate-in">
                <h3 class="section-title">
                    <i class="fas fa-gift"></i>
                    Benefits & Perks
                </h3>
                <div class="benefits">
                    <?= nl2br(htmlspecialchars($job['benefits'])) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Skills -->
            <?php if (!empty($job['skills'])): ?>
            <div class="content-card animate-in">
                <h3 class="section-title">
                    <i class="fas fa-tools"></i>
                    Required Skills
                </h3>
                <div class="skills-container">
                    <?php 
                    $skills = explode(',', $job['skills']);
                    foreach ($skills as $skill):
                        $skill = trim($skill);
                        if (!empty($skill)):
                    ?>
                        <span class="skill-badge">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($skill) ?>
                        </span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Application Section -->
            <div class="apply-section animate-in">
                <?php if (is_jobseeker()): ?>
                    <h3 style="margin-bottom: 25px; color: #1a1a1a; font-weight: 700;">
                        Ready to Apply?
                    </h3>
                    <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn apply-btn">
                        <i class="fas fa-paper-plane"></i>
                        Apply for this Position
                    </a>
                    <p class="apply-note">
                        <i class="fas fa-info-circle"></i>
                        You'll be redirected to the application form
                    </p>
                <?php else: ?>
                    <h3 style="margin-bottom: 25px; color: #1a1a1a; font-weight: 700;">
                        Interested in this Position?
                    </h3>
                    <a href="login.php" class="btn login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Login to Apply
                    </a>
                    <p class="apply-note">
                        <i class="fas fa-user-circle"></i>
                        You need to login as a job seeker to apply
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include 'portal_footer.php'; ?>