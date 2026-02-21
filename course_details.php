<?php
include 'portal_header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id'])) {
    die("Invalid Course Request");
}

$course_id = intval($_GET['id']);

$course = $mysqli->query("
    SELECT c.*, u.name AS training_center_name, u.image AS logo
    FROM courses c
    JOIN users u ON c.training_center_id = u.id
    WHERE c.id = $course_id
")->fetch_assoc();

if (!$course) {
    die("Course Not Found");
}

$logo = (!empty($course['logo']) && file_exists($course['logo'])) 
        ? $course['logo'] : "assets/default_logo.png";

// Check if user can apply (must be jobseeker and course is approved)
$can_apply = is_jobseeker() && $course['status'] === 'approved';

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
   
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.course-header {
    position: relative;
    background-color:#008c84;
    padding: 80px 0 100px;
    margin-bottom: -60px;
    overflow: hidden;
}

.course-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.1;
}

.course-header-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 30px;
}

.center-logo-wrapper {
    flex-shrink: 0;
}

.center-logo {
    width: 120px;
    height: 120px;
    object-fit: contain;
    border-radius: 20px;
    background: white;
    padding: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    border: 4px solid rgba(255,255,255,0.3);
}

.course-info {
    flex: 1;
    color: white;
}

.course-title {
    font-size: 2.8rem;
    font-weight: 800;
    margin: 0 0 15px 0;
    color: white;
    line-height: 1.2;
}

.center-name {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: rgba(255,255,255,0.95);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-top: 10px;
}

.status-approved {
    background: rgba(46, 204, 113, 0.2);
    color: #27ae60;
    border: 2px solid rgba(46, 204, 113, 0.3);
}

.status-pending {
    background: rgba(241, 196, 15, 0.2);
    color: #f39c12;
    border: 2px solid rgba(241, 196, 15, 0.3);
}

.course-meta-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: var(--shadow-md);
    margin-bottom: 24px;
    position: sticky;
    top: 20px;
}

.section-title {
    color: #00a098;
    font-weight: 700;
    font-size: 1.5rem;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 3px solid #8a2be2;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: #00a098;
}

.course-meta-item {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 15px;
    transition: all 0.3s ease;
}

.course-meta-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.course-meta-item:last-child {
    margin-bottom: 0;
}

.meta-icon {
    width: 50px;
    height: 50px;
    background: #00a098;
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
    background: #00a098;
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
    color: #8a2be2;
    font-weight: bold;
}

.structure-container {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 25px;
    margin: 20px 0;
}

.structure-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;
}

.structure-item:last-child {
    border-bottom: none;
}

.structure-item i {
    color: #8a2be2;
    margin-right: 15px;
    font-size: 1.1rem;
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
    background: var(--primary-color);
    border: none;
    padding: 18px 50px;
    font-size: 1.2rem;
    font-weight: 700;
    border-radius: 50px;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 8px 24px rgba(138, 43, 226, 0.4);
    display: inline-flex;
    align-items: center;
    gap: 12px;
}

.apply-btn:hover {
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 12px 32px rgba(138, 43, 226, 0.5);
}

.login-btn {
    background: #00a098;
    color: white;
    
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
    color: white;
    border: 1px solid black;
    transform: translateY(-4px);
    background-color: #666;
 
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
    color: #8a2be2;
}

.disabled-btn {
    background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
    border: none;
    padding: 18px 50px;
    font-size: 1.2rem;
    font-weight: 700;
    border-radius: 50px;
    color: white;
    cursor: not-allowed;
    opacity: 0.7;
    display: inline-flex;
    align-items: center;
    gap: 12px;
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
    color: #8a2be2;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 1000;
    text-decoration: none;
}

.back-button:hover {
    background: #8a2be2;
    color: white;
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .course-header {
        padding: 50px 0 80px;
    }
    
    .course-header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .center-logo {
        width: 100px;
        height: 100px;
    }
    
    .course-title {
        font-size: 2rem;
    }
    
    .center-name {
        font-size: 1.2rem;
    }
    
    .course-meta-card {
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


<!-- Course Header -->
<div class="course-header">
    <div class="container">
        <div class="course-header-content">
            <div class="center-logo-wrapper">
                <img src="<?= $logo ?>" 
                     alt="<?= htmlspecialchars($course['training_center_name']) ?> Logo" 
                     class="center-logo">
            </div>
            <div class="course-info">
                <h1 class="course-title"><?= htmlspecialchars($course['title']) ?></h1>
                <div class="center-name">
                    <i class="fas fa-university"></i>
                    <?= htmlspecialchars($course['training_center_name']) ?>
                </div>
               
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <!-- Left Column: Course Meta -->
        <div class="col-lg-4">
            <div class="course-meta-card animate-in">
                <h3 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Course Details
                </h3>
                
                <!-- Cost -->
                <div class="course-meta-item">
                    <div class="meta-icon">
                       <i style="font-size:24px" class="fa">&#xf0d6;</i>
                    </div>
                    <div class="meta-content">
                        <h6>Course Fee</h6>
                        <p><?= htmlspecialchars($course['cost']) ?></p>
                    </div>
                </div>
                
                <!-- Duration -->
                <?php if (!isEmptyString($course['duration'])): ?>
                <div class="course-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="meta-content">
                        <h6>Duration</h6>
                        <p><?= htmlspecialchars($course['duration']) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Prerequisites -->
                <?php if (!isEmptyString($course['prerequisites'])): ?>
                <div class="course-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="meta-content">
                        <h6>Prerequisites</h6>
                        <p><?= htmlspecialchars($course['prerequisites']) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Training Center -->
                <div class="course-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="meta-content">
                        <h6>Training Center</h6>
                        <p><?= htmlspecialchars($course['training_center_name']) ?></p>
                    </div>
                </div>
                
                <!-- Status -->
                <div class="course-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-<?= $course['status'] === 'approved' ? 'check-circle' : 'clock' ?>"></i>
                    </div>
                    <div class="meta-content">
                        <h6>Status</h6>
                        <p style="color: <?= $course['status'] === 'approved' ? '#27ae60' : '#f39c12' ?>;">
                            <?= ucfirst($course['status']) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Course Content -->
        <div class="col-lg-8">
            <!-- Course Description -->
            <div class="content-card animate-in">
                <h3 class="section-title">
                    <i class="fas fa-file-alt"></i>
                    Course Description
                </h3>
                <?php if (!isEmptyString($course['description'])): ?>
                    <div class="course-description">
                        <?= nl2br(htmlspecialchars($course['description'])) ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No course description provided.</p>
                <?php endif; ?>
            </div>
            
            <!-- Course Structure -->
            <?php if (!empty($course['structure'])): ?>
            <div class="content-card animate-in">
                <h3 class="section-title">
                    <i class="fas fa-sitemap"></i>
                    Course Structure
                </h3>
                <div class="structure-container">
                    <?= nl2br(htmlspecialchars($course['structure'])) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Prerequisites (Detailed) -->
            <?php if (!empty($course['prerequisites']) && strlen(trim($course['prerequisites'])) > 50): ?>
            <div class="content-card animate-in">
                <h3 class="section-title">
                    <i class="fas fa-graduation-cap"></i>
                    Prerequisites
                </h3>
                <div class="prerequisites">
                    <?= nl2br(htmlspecialchars($course['prerequisites'])) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Application Section -->
            <div class="apply-section animate-in">
                <?php if ($course['status'] !== 'approved'): ?>
                    <h3 style="margin-bottom: 25px; color: #1a1a1a; font-weight: 700;">
                        <i class="fas fa-clock text-warning"></i>
                        Course Pending Approval
                    </h3>
                    <button class="btn disabled-btn" disabled>
                        <i class="fas fa-lock"></i>
                        Applications Closed
                    </button>
                    <p class="apply-note">
                        <i class="fas fa-info-circle"></i>
                        This course is awaiting admin approval. Please check back later.
                    </p>
                
                <?php elseif ($can_apply): ?>
                    <h3 style="margin-bottom: 25px; color: #1a1a1a; font-weight: 700;">
                        Ready to Enroll?
                    </h3>
                    <a href="apply_training.php?course_id=<?= $course['id'] ?>" class="btn apply-btn">
                        <i class="fas fa-user-plus"></i>
                        Enroll in this Course
                    </a>
                    <p class="apply-note">
                        <i class="fas fa-info-circle"></i>
                        You'll be redirected to the enrollment form
                    </p>
                
                <?php elseif (is_logged_in() && !is_jobseeker()): ?>
                    <h3 style="margin-bottom: 25px; color: #1a1a1a; font-weight: 700;">
                        Enrollment Restricted
                    </h3>
                    <button class="btn disabled-btn" disabled>
                        <i class="fas fa-user-tie"></i>
                        Job Seekers Only
                    </button>
                    <p class="apply-note">
                        <i class="fas fa-info-circle"></i>
                        Only job seekers can enroll in courses
                    </p>
                
                <?php else: ?>
                    <h3 style="margin-bottom: 25px; color: #1a1a1a; font-weight: 700;">
                        Interested in this Course?
                    </h3>
                    <a href="login.php?redirect=course_detail.php?id=<?= $course['id'] ?>" class="btn login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Login to Enroll
                    </a>
                    <p class="apply-note">
                        <i class="fas fa-user-circle"></i>
                        You need to login as a job seeker to enroll
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