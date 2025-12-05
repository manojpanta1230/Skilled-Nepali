<?php 
include 'config.php';
require_login(); 
if(!is_jobseeker()) die("<div class='alert alert-danger'>Access Denied: Jobseekers only.</div>");

$u = current_user();
$user_id = $u['id'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// ========== FETCH STATISTICS ==========
// Job applications
$applied_jobs_count = $mysqli->query("SELECT COUNT(*) as total FROM applications WHERE user_id = $user_id")->fetch_assoc()['total'];

// Training enrollments
$applied_courses_count = $mysqli->query("SELECT COUNT(*) as total FROM course_applications WHERE user_id = $user_id")->fetch_assoc()['total'];

// Recent job applications (last 5)
$recent_jobs_sql = "
    SELECT a.*, j.title AS job_title, j.employer_id, j.country, j.salary
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5
";
$stmt = $mysqli->prepare($recent_jobs_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_jobs = $stmt->get_result();

// Recent training enrollments (last 5)
$recent_courses_sql = "
    SELECT ca.*, c.title AS course_title, c.training_center_id, c.cost
    FROM course_applications ca
    JOIN courses c ON ca.course_id = c.id
    WHERE ca.user_id = ?
    ORDER BY ca.created_at DESC
    LIMIT 5
";
$stmt2 = $mysqli->prepare($recent_courses_sql);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$recent_courses = $stmt2->get_result();

// Profile completeness calculation
$profile_fields = ['name', 'email', 'phone', 'address', 'bio', 'image', 'resume'];
$complete_fields = 0;
foreach($profile_fields as $field) {
    if(!empty($u[$field])) $complete_fields++;
}
$profile_completeness = round(($complete_fields/count($profile_fields))*100, 0);

// Today's applications
$today = date('Y-m-d');
$today_apps = $mysqli->query("
    SELECT COUNT(*) as today FROM applications 
    WHERE user_id = $user_id AND DATE(created_at) = '$today'
")->fetch_assoc()['today'];

// Handle image upload
if(isset($_POST['upload_image'])) {
    $file = $_FILES['profile_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    
    if(in_array($file['type'], $allowed_types) && $file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = 'uploads/profile_' . $u['id'] . '_' . time() . '.' . $ext;
        if(move_uploaded_file($file['tmp_name'], $new_name)) {
            $stmt = $mysqli->prepare("UPDATE users SET image = ? WHERE id = ?");
            $stmt->bind_param("si", $new_name, $u['id']);
            if($stmt->execute()) {
                $_SESSION['success_message'] = "Profile image updated successfully!";
                $u['image'] = $new_name;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Failed to upload image. Try again.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid file type. Only JPG, PNG, WEBP allowed.";
    }
}

// Handle resume upload
if(isset($_POST['upload_resume'])) {
    $file = $_FILES['resume'];
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
    if(in_array($file['type'], $allowed_types) && $file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = 'uploads/resume_' . $u['id'] . '_' . time() . '.' . $ext;
        if(move_uploaded_file($file['tmp_name'], $new_name)) {
            $stmt = $mysqli->prepare("UPDATE users SET resume = ? WHERE id = ?");
            $stmt->bind_param("si", $new_name, $u['id']);
            if($stmt->execute()) {
                $_SESSION['success_message'] = "Resume uploaded successfully!";
                $u['resume'] = $new_name;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Failed to upload resume. Try again.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid file type. Only PDF, DOC, DOCX allowed.";
    }
}

// Handle profile update
if(isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $bio = trim($_POST['bio']);
    
    $stmt = $mysqli->prepare("UPDATE users SET name=?, phone=?, address=?, bio=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $phone, $address, $bio, $user_id);
    if($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        $u['name'] = $name;
        $u['phone'] = $phone;
        $u['address'] = $address;
        $u['bio'] = $bio;
    }
    $stmt->close();
}

// Fetch all applied jobs for applications tab
if($active_tab == 'applications') {
    $all_jobs_sql = "
        SELECT a.*, j.title AS job_title, j.employer_id, j.country, j.salary, 
               u.company AS employer_company
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON j.employer_id = u.id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC
    ";
    $stmt = $mysqli->prepare($all_jobs_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $all_applied_jobs = $stmt->get_result();
    
    $all_courses_sql = "
        SELECT ca.*, c.title AS course_title, c.training_center_id, c.cost,
               u.company AS center_name
        FROM course_applications ca
        JOIN courses c ON ca.course_id = c.id
        JOIN users u ON c.training_center_id = u.id
        WHERE ca.user_id = ?
        ORDER BY ca.created_at DESC
    ";
    $stmt2 = $mysqli->prepare($all_courses_sql);
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $all_applied_courses = $stmt2->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobseeker Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    :root {
        --sidebar-bg: #1e293b;
        --sidebar-active: #3b82f6;
        --sidebar-hover: #334155;
        --primary-color: #00A098;
        --primary-dark: #00857C;
        --secondary-color: #8B5CF6;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --info-color: #3b82f6;
        --danger-color: #ef4444;
        --light-color: #f8fafc;
        --dark-color: #1e293b;
        --border-color: #e2e8f0;
        --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --transition: all 0.3s ease;
    }
    
    body {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: var(--light-color);
        color: var(--dark-color);
        overflow-x: hidden;
        margin: 0;
        padding: 0;
    }
    
    /* Sidebar Styles */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 280px;
        background: var(--sidebar-bg);
        color: white;
        transition: var(--transition);
        z-index: 1050;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        transform: translateX(0);
    }
    
    .sidebar-brand {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .sidebar-brand h3 {
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 0;
        color: white;
    }
    
    .sidebar-brand small {
        font-size: 0.8rem;
        opacity: 0.8;
    }
    
    .sidebar-close {
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    /* Welcome Section in Sidebar */
    .welcome-section {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
    }
    
    .welcome-content {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .welcome-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .welcome-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .welcome-avatar .avatar-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--secondary-color);
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
    }
    
    .welcome-text h4 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .welcome-text p {
        margin: 0.25rem 0 0;
        font-size: 0.85rem;
        opacity: 0.8;
    }
    
    .sidebar-menu {
        padding: 1rem 0;
        overflow-y: auto;
        height: calc(100vh - 250px);
    }
    
    .sidebar-item {
        margin-bottom: 0.5rem;
    }
    
    .sidebar-link {
        display: flex;
        align-items: center;
        padding: 0.875rem 1.5rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: var(--transition);
        border-left: 3px solid transparent;
    }
    
    .sidebar-link:hover {
        background: var(--sidebar-hover);
        color: white;
        border-left-color: var(--sidebar-active);
    }
    
    .sidebar-link.active {
        background: var(--sidebar-hover);
        color: white;
        border-left-color: var(--sidebar-active);
        font-weight: 600;
    }
    
    .sidebar-link i {
        width: 24px;
        font-size: 1.1rem;
        margin-right: 0.75rem;
    }
    
    .sidebar-link .badge {
        margin-left: auto;
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* Main Content Styles */
    .main-content {
        margin-left: 280px;
        padding: 0;
        min-height: 100vh;
        transition: var(--transition);
    }
    
    .topbar {
        background: white;
        padding: 1rem 2rem;
        box-shadow: var(--card-shadow);
        position: sticky;
        top: 0;
        z-index: 1040;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .topbar-left h4 {
        margin: 0;
        font-weight: 700;
        color: var(--dark-color);
    }
    
    .topbar-left small {
        color: #64748b;
        font-size: 0.875rem;
    }
    
    .sidebar-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--info-color);
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 6px;
        transition: var(--transition);
    }
    
    .sidebar-toggle:hover {
        background: #f1f5f9;
    }
    
    .content-wrapper {
        padding: 2rem;
    }
    
    /* Tab Navigation */
    .nav-tabs.modern-tabs {
        border: none;
        background: white;
        border-radius: 12px;
        padding: 0.5rem;
        box-shadow: var(--card-shadow);
        margin-bottom: 2rem;
        display: flex;
        overflow-x: auto;
    }
    
    .modern-tabs .nav-link {
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        color: #64748b;
        transition: var(--transition);
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .modern-tabs .nav-link:hover {
        color: var(--secondary-color);
        background: rgba(139, 92, 246, 0.1);
    }
    
    .modern-tabs .nav-link.active {
        background: linear-gradient(135deg, var(--secondary-color), #7C3AED);
        color: white;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }
    
    /* Tab Content */
    .tab-content {
        background: white;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        padding: 2rem;
    }
    
    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        border: none;
        transition: var(--transition);
        border-left: 4px solid var(--info-color);
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .stat-card.jobs-applied { border-left-color: var(--info-color); }
    .stat-card.trainings-enrolled { border-left-color: var(--success-color); }
    .stat-card.profile-complete { border-left-color: var(--warning-color); }
    .stat-card.today-applied { border-left-color: var(--primary-color); }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }
    
    .stat-icon.jobs-applied { background: rgba(59, 130, 246, 0.1); color: var(--info-color); }
    .stat-icon.trainings-enrolled { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
    .stat-icon.profile-complete { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
    .stat-icon.today-applied { background: rgba(0, 160, 152, 0.1); color: var(--primary-color); }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.25rem;
        color: var(--dark-color);
    }
    
    .stat-label {
        font-size: 0.875rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    
    /* Progress Bar */
    .progress-ring {
        width: 100px;
        height: 100px;
        margin: 0 auto 1rem;
    }
    
    .progress-ring-circle {
        stroke-width: 8;
        fill: transparent;
        stroke-linecap: round;
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
    }
    
    /* Job Cards */
    .job-card {
        background: white;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: var(--card-shadow);
        transition: var(--transition);
        margin-bottom: 1rem;
    }
    
    .job-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-color: var(--secondary-color);
    }
    
    .job-card-header {
        padding: 1.25rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .job-card-body {
        padding: 1.25rem;
    }
    
    /* Alerts */
    .alert-modern {
        border-radius: 12px;
        border: none;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        box-shadow: var(--card-shadow);
    }
    
    .alert-modern.alert-success {
        background: linear-gradient(135deg, var(--success-color), #34d399);
        color: white;
    }
    
    .alert-modern.alert-danger {
        background: linear-gradient(135deg, #ef4444, #f87171);
        color: white;
    }
    
    .alert-modern.alert-info {
        background: linear-gradient(135deg, var(--info-color), #60a5fa);
        color: white;
    }
    
    /* Buttons */
    .btn-modern {
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: var(--transition);
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .btn-primary-modern {
        background: linear-gradient(135deg, var(--secondary-color), #7C3AED);
        color: white;
    }
    
    .btn-outline-modern {
        border: 2px solid var(--secondary-color);
        color: var(--secondary-color);
        background: transparent;
    }
    
    .btn-outline-modern:hover {
        background: var(--secondary-color);
        color: white;
    }
    
    /* Profile Section */
    .profile-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto 1rem;
        border: 4px solid white;
        box-shadow: var(--card-shadow);
    }
    
    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profile-avatar .avatar-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--secondary-color);
        color: white;
        font-size: 3rem;
    }
    
    /* Forms */
    .form-modern .form-control,
    .form-modern .form-select {
        border: 2px solid var(--border-color);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: var(--transition);
    }
    
    .form-modern .form-control:focus,
    .form-modern .form-select:focus {
        border-color: var(--secondary-color);
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }
    
    .form-modern textarea {
        min-height: 120px;
        resize: vertical;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    
    .empty-state p {
        color: #64748b;
        margin-bottom: 1.5rem;
    }
    
    /* Table */
    .table-modern {
        margin-bottom: 0;
    }
    
    .table-modern thead {
        background: #f1f5f9;
    }
    
    .table-modern th {
        border: none;
        padding: 1rem;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    
    .table-modern td {
        padding: 1rem;
        vertical-align: middle;
        border-color: #f1f5f9;
    }
    
    .table-modern tbody tr {
        transition: var(--transition);
    }
    
    .table-modern tbody tr:hover {
        background: #f8fafc;
    }
    
    /* Mobile Responsive */
    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-100%);
            width: 280px;
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0;
        }
        
        .sidebar-toggle {
            display: block;
        }
        
        .sidebar-close {
            display: block;
        }
        
        .content-wrapper {
            padding: 1rem;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .topbar {
            padding: 1rem;
        }
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .modern-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
        }
        
        .modern-tabs .nav-link {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
        }
    }
    
    /* Overlay for mobile sidebar */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
    
    /* Custom Scrollbar */
    .sidebar-menu::-webkit-scrollbar {
        width: 5px;
    }
    
    .sidebar-menu::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
    }
    
    .sidebar-menu::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    /* Backdrop when sidebar is open on mobile */
    body.sidebar-open {
        overflow: hidden;
    }
    
    body.sidebar-open .sidebar-overlay {
        display: block;
    }
    </style>
</head>
<body>
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <div>
                <h3><i class="fas fa-user-tie me-2"></i>Jobseeker Panel</h3>
                <small class="text-light opacity-75">Find Jobs & Trainings</small>
            </div>
            <button class="sidebar-close" onclick="toggleSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <div class="welcome-avatar">
                    <?php if(!empty($u['image'])): ?>
                        <img src="<?= htmlspecialchars($u['image']) ?>" alt="Profile Picture">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="welcome-text">
                    <h4><?= htmlspecialchars($u['name']) ?></h4>
                    <p>Jobseeker Dashboard</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <div class="sidebar-menu">
            <div class="sidebar-item">
                <a href="?tab=dashboard" class="sidebar-link <?= $active_tab == 'dashboard' ? 'active' : '' ?>" onclick="closeSidebarOnMobile()">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="sidebar-item">
                <a href="?tab=applications" class="sidebar-link <?= $active_tab == 'applications' ? 'active' : '' ?>" onclick="closeSidebarOnMobile()">
                    <i class="fas fa-file-alt"></i>
                    <span>My Applications</span>
                    <?php if($applied_jobs_count > 0): ?>
                    <span class="badge bg-info rounded-pill"><?= $applied_jobs_count ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
        
            
            <div class="sidebar-item">
                <a href="jobs.php" class="sidebar-link" onclick="closeSidebarOnMobile()">
                    <i class="fas fa-search"></i>
                    <span>Browse Jobs</span>
                </a>
            </div>
            
            <div class="sidebar-item">
                <a href="jobs.php#trainings" class="sidebar-link" onclick="closeSidebarOnMobile()">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Browse Trainings</span>
                </a>
            </div>
            
            <div class="sidebar-item mt-4">
                <a href="index.php" class="sidebar-link" onclick="closeSidebarOnMobile()">
                    <i class="fas fa-home"></i>
                    <span>Back to Home</span>
                </a>
            </div>
            
            <div class="sidebar-item">
                <a href="logout.php" class="sidebar-link text-danger" onclick="closeSidebarOnMobile()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button class="sidebar-toggle me-3">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-left">
                    <h4>Jobseeker Dashboard</h4>
                    <small>Track applications and find opportunities</small>
                </div>
            </div>
            <div>
                <span class="badge bg-secondary-color rounded-pill px-3 py-2" style="background: var(--secondary-color);">
                    <i class="fas fa-user-graduate me-1"></i> Jobseeker Account
                </span>
            </div>
        </div>

        <!-- Content -->
        <div class="content-wrapper">
            <!-- Display Alerts -->
            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert-modern alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i>
                    <?= $_SESSION['success_message'] ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert-modern alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $_SESSION['error_message'] ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs modern-tabs">
                <li class="nav-item">
                    <a class="nav-link <?= $active_tab == 'dashboard' ? 'active' : '' ?>" href="?tab=dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_tab == 'applications' ? 'active' : '' ?>" href="?tab=applications">
                        <i class="fas fa-file-alt"></i> My Applications
                        <?php if($applied_jobs_count > 0): ?>
                        <span class="badge bg-secondary ms-1"><?= $applied_jobs_count + $applied_courses_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <?php if($active_tab == 'dashboard'): ?>
                    <!-- DASHBOARD TAB -->
                    <div class="tab-pane fade show active">
                        <h4 class="mb-4">Dashboard Overview</h4>
                        
                        <!-- Stats Cards -->
                        <div class="stats-grid mb-4">
                            <div class="stat-card jobs-applied">
                                <div class="stat-icon jobs-applied">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value"><?= $applied_jobs_count ?></div>
                                    <div class="stat-label">Jobs Applied</div>
                                </div>
                            </div>
                            
                            <div class="stat-card trainings-enrolled">
                                <div class="stat-icon trainings-enrolled">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value"><?= $applied_courses_count ?></div>
                                    <div class="stat-label">Trainings Enrolled</div>
                                </div>
                            </div>
                            
                 
                            
                            <div class="stat-card today-applied">
                                <div class="stat-icon today-applied">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-value"><?= $today_apps ?></div>
                                    <div class="stat-label">Today's Applications</div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                

                        <!-- Recent Applications -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-white border-0 pb-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-briefcase text-primary me-2"></i>Recent Job Applications
                                            </h5>
                                            <a href="?tab=applications" class="btn btn-sm btn-outline-modern col-auto">View All</a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if($recent_jobs->num_rows > 0): ?>
                                            <div class="list-group list-group-flush">
                                                <?php while($job = $recent_jobs->fetch_assoc()): 
                                                    $employer = $mysqli->query("SELECT company FROM users WHERE id={$job['employer_id']}")->fetch_assoc();
                                                ?>
                                                <div class="list-group-item border-0 px-0 py-3">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <div class="fw-semibold"><?= htmlspecialchars($job['job_title']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($employer['company'] ?? 'Company') ?></small>
                                                            <div class="mt-2">
                                                                <small class="badge bg-secondary ms-1"><?= htmlspecialchars($job['country']) ?></small>
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="text-muted small"><?= date('M d', strtotime($job['created_at'])) ?></div>
                                                            <span class="badge bg-success">Applied</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="empty-state py-3">
                                                <i class="fas fa-briefcase text-muted"></i>
                                                <p class="mb-2">No job applications yet</p>
                                                <a href="jobs.php" class="btn btn-sm btn-outline-modern">Browse Jobs</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-white border-0 pb-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-graduation-cap text-success me-2 "></i>Recent Training Enrollments
                                            </h5>
                                            <a href="?tab=applications" class="btn btn-sm btn-outline-modern col-auto">View All</a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if($recent_courses->num_rows > 0): ?>
                                            <div class="list-group list-group-flush">
                                                <?php while($course = $recent_courses->fetch_assoc()): 
                                                    $center = $mysqli->query("SELECT company FROM users WHERE id={$course['training_center_id']}")->fetch_assoc();
                                                ?>
                                                <div class="list-group-item border-0 px-0 py-3">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <div class="fw-semibold"><?= htmlspecialchars($course['course_title']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($center['company'] ?? 'Training Center') ?></small>
                                                            <div class="mt-2">
                                                                <?php if($course['cost']): ?>
                                                                <small class="badge bg-warning ms-1"><?= htmlspecialchars($course['cost']) ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="text-muted small"><?= date('M d', strtotime($course['created_at'])) ?></div>
                                                            <span class="badge bg-info">Enrolled</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="empty-state py-3">
                                                <i class="fas fa-graduation-cap text-muted"></i>
                                                <p class="mb-2">No training enrollments yet</p>
                                                <a href="jobs.php#trainings" class="btn btn-sm btn-outline-modern">Browse Trainings</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif($active_tab == 'applications'): ?>
                    <!-- APPLICATIONS TAB -->
                    <div class="tab-pane fade show active">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">My Applications</h4>
                            <div>
                                <a href="jobs.php" class="btn btn-primary-modern btn-modern me-2">
                                    <i class="fas fa-search me-2"></i>Find More Jobs
                                </a>
                                <a href="jobs.php#trainings" class="btn btn-outline-modern btn-modern">
                                    <i class="fas fa-graduation-cap me-2"></i>Browse Trainings
                                </a>
                            </div>
                        </div>
                        
                        <!-- Job Applications -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-0">
                                <h5 class="mb-0">
                                    <i class="fas fa-briefcase text-primary me-2"></i>
                                    Job Applications (<?= $applied_jobs_count ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if($all_applied_jobs->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-modern">
                                            <thead>
                                                <tr>
                                                    <th>Job Title</th>
                                                    <th>Company</th>
                                                    <th>Location</th>
                                                    <th>Salary</th>
                                                    <th>Applied Date</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($job = $all_applied_jobs->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-medium"><?= htmlspecialchars($job['job_title']) ?></div>
                                                    </td>
                                                    <td><?= htmlspecialchars($job['employer_company']) ?></td>
                                                    <td>
                                                    </td>
                                                    <td><?= htmlspecialchars($job['country']) ?></td>
                                                    <td>
                                                        <?php if($job['salary']): ?>
                                                            <span class="text-success"><?= htmlspecialchars($job['salary']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Negotiable</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-success">Applied</span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-modern" data-bs-toggle="modal" data-bs-target="#viewJobModal<?= $job['id'] ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                
                                                <!-- View Job Modal -->
                                                <div class="modal fade" id="viewJobModal<?= $job['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content border-0 shadow-lg">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold">Application Details</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <h6 class="fw-bold mb-3">Job: <?= htmlspecialchars($job['job_title']) ?></h6>
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <p><strong>Company:</strong> <?= htmlspecialchars($job['employer_company']) ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p><strong>Location:</strong> <?= htmlspecialchars($job['country']) ?></p>
                                                                        <p><strong>Salary:</strong> <?= htmlspecialchars($job['salary'] ?? 'Negotiable') ?></p>
                                                                    </div>
                                                                </div>
                                                                <p><strong>Applied On:</strong> <?= date('F j, Y, h:i A', strtotime($job['created_at'])) ?></p>
                                                                <?php if(!empty($job['notes'])): ?>
                                                                <div class="card bg-light">
                                                                    <div class="card-body">
                                                                        <h6>Your Notes:</h6>
                                                                        <p class="mb-0"><?= nl2br(htmlspecialchars($job['notes'])) ?></p>
                                                                    </div>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                        <h5>No Job Applications Yet</h5>
                                        <p class="text-muted">You haven't applied to any jobs yet. Start your job search today!</p>
                                        <a href="jobs.php" class="btn btn-primary-modern">
                                            <i class="fas fa-search me-2"></i>Browse Available Jobs
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Training Applications -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0">
                                <h5 class="mb-0">
                                    <i class="fas fa-graduation-cap text-success me-2"></i>
                                    Training Enrollments (<?= $applied_courses_count ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if($all_applied_courses->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-modern">
                                            <thead>
                                                <tr>
                                                    <th>Course Title</th>
                                                    <th>Training Center</th>
                                                    <th>Cost</th>
                                                    <th>Enrolled Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($course = $all_applied_courses->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-medium"><?= htmlspecialchars($course['course_title']) ?></div>
                                                    </td>
                                                    <td><?= htmlspecialchars($course['center_name']) ?></td>
                                                    <td>
                                                    </td>
                                                    <td>
                                                        <?php if($course['cost']): ?>
                                                            <span class="text-success fw-medium"><?= htmlspecialchars($course['cost']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Free</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= date('M d, Y', strtotime($course['created_at'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-info">Enrolled</span>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                        <h5>No Training Enrollments Yet</h5>
                                        <p class="text-muted">You haven't enrolled in any training courses yet. Enhance your skills!</p>
                                        <a href="jobs.php#trainings" class="btn btn-success">
                                            <i class="fas fa-graduation-cap me-2"></i>Browse Training Courses
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <?php elseif($active_tab == 'profile'): ?>
                    <!-- PROFILE TAB -->
                    <div class="tab-pane fade show active">
                        <h4 class="mb-4">Profile Settings</h4>
                        
                        <div class="row">
                            <!-- Left Column: Profile Info -->
                            <div class="col-lg-4">
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-body text-center py-4">
                                        <div class="profile-header">
                                            <div class="profile-avatar">
                                                <?php if(!empty($u['image'])): ?>
                                                    <img src="<?= htmlspecialchars($u['image']) ?>" alt="Profile">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder">
                                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($u['name']) ?></h4>
                                            <div class="badge bg-secondary-color mb-3" style="background: var(--secondary-color);">Jobseeker</div>
                                            
                                            <!-- Progress Ring -->
                                            <div class="progress-ring">
                                                <svg viewBox="0 0 100 100">
                                                    <circle class="progress-ring-circle" 
                                                            stroke="url(#gradient)" 
                                                            stroke-dasharray="<?= 2 * 3.14 * 45 * $profile_completeness / 100 ?>, 283" 
                                                            r="45" cx="50" cy="50">
                                                    </circle>
                                                    <defs>
                                                        <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                                            <stop offset="0%" style="stop-color: var(--secondary-color);" />
                                                            <stop offset="100%" style="stop-color: var(--primary-color);" />
                                                        </linearGradient>
                                                    </defs>
                                                </svg>
                                                <div class="position-absolute top-50 start-50 translate-middle">
                                                    <div class="h3 fw-bold mb-0"><?= $profile_completeness ?>%</div>
                                                    <small class="text-muted">Complete</small>
                                                </div>
                                            </div>
                                            
                                            <!-- Upload Image Form -->
                                            <div class="mt-4">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <div class="input-group mb-2">
                                                        <input type="file" name="profile_image" class="form-control form-control-sm" accept="image/*">
                                                        <button type="submit" name="upload_image" class="btn btn-sm btn-primary-modern">
                                                            <i class="fas fa-upload"></i>
                                                        </button>
                                                    </div>
                                                    <small class="text-muted">Max 2MB (JPG, PNG, WEBP)</small>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Resume Upload -->
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-3">
                                            <i class="fas fa-file-pdf text-danger me-2"></i>
                                            Resume / CV
                                        </h6>
                                        <?php if(!empty($u['resume'])): ?>
                                            <div class="alert alert-success p-3 mb-3">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Resume uploaded successfully!
                                                <div class="mt-2">
                                                    <a href="<?= htmlspecialchars($u['resume']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-download me-1"></i>View Resume
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="input-group mb-2">
                                                <input type="file" name="resume" class="form-control form-control-sm" accept=".pdf,.doc,.docx">
                                                <button type="submit" name="upload_resume" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-upload"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">PDF, DOC, DOCX (Max 5MB)</small>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column: Edit Profile -->
                            <div class="col-lg-8">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="fw-bold mb-4">
                                            <i class="fas fa-edit text-primary me-2"></i>
                                            Edit Profile Information
                                        </h5>
                                        
                                        <form method="POST" class="form-modern">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold">Full Name *</label>
                                                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($u['name']) ?>" required>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold">Email</label>
                                                    <input type="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" disabled>
                                                    <small class="text-muted">Email cannot be changed</small>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold">Phone Number</label>
                                                    <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($u['phone'] ?? '') ?>">
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-semibold">Location</label>
                                                    <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($u['address'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Bio / About Me</label>
                                                <textarea class="form-control" name="bio" rows="5"><?= htmlspecialchars($u['bio'] ?? '') ?></textarea>
                                                <small class="text-muted">Tell employers about your skills, experience, and career goals</small>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <button type="submit" name="update_profile" class="btn btn-primary-modern btn-modern">
                                                    <i class="fas fa-save me-2"></i>Update Profile
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar management
const sidebarManager = {
    sidebar: null,
    overlay: null,
    body: null,
    toggleBtn: null,
    closeBtn: null,
    
    init() {
        this.sidebar = document.querySelector('.sidebar');
        this.overlay = document.querySelector('.sidebar-overlay');
        this.body = document.body;
        this.toggleBtn = document.querySelector('.sidebar-toggle');
        this.closeBtn = document.querySelector('.sidebar-close');
        
        if (!this.sidebar) return;
        
        this.setupEventListeners();
        this.setupResizeHandler();
    },
    
    setupEventListeners() {
        // Toggle button
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggle();
            });
        }
        
        // Close button
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.close();
            });
        }
        
        // Overlay click
        if (this.overlay) {
            this.overlay.addEventListener('click', () => {
                this.close();
            });
        }
        
        // Sidebar links for mobile
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 992) {
                    setTimeout(() => this.close(), 100);
                }
            });
        });
        
        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });
    },
    
    setupResizeHandler() {
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (window.innerWidth > 992 && this.isOpen()) {
                    this.close();
                }
            }, 250);
        });
    },
    
    toggle() {
        this.sidebar.classList.toggle('active');
        if (this.overlay) this.overlay.classList.toggle('active');
        this.body.classList.toggle('sidebar-open');
    },
    
    open() {
        this.sidebar.classList.add('active');
        if (this.overlay) this.overlay.classList.add('active');
        this.body.classList.add('sidebar-open');
    },
    
    close() {
        this.sidebar.classList.remove('active');
        if (this.overlay) this.overlay.classList.remove('active');
        this.body.classList.remove('sidebar-open');
    },
    
    isOpen() {
        return this.sidebar.classList.contains('active');
    }
};

// Alert management
const alertManager = {
    init() {
        // Auto-close alerts after 5 seconds
        setTimeout(() => {
            this.closeAllAlerts();
        }, 5000);
        
        // Manual close buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-close') || 
                e.target.closest('.btn-close')) {
                const alert = e.target.closest('.alert');
                if (alert) {
                    this.closeAlert(alert);
                }
            }
        });
    },
    
    closeAllAlerts() {
        document.querySelectorAll('.alert').forEach(alert => {
            this.closeAlert(alert);
        });
    },
    
    closeAlert(alert) {
        alert.classList.add('fade');
        alert.classList.remove('show');
        
        // Remove from DOM after animation
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 300);
    }
};

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize sidebar
    sidebarManager.init();
    
    // Initialize alerts
    alertManager.init();
    
    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 992 && 
            sidebarManager.isOpen() && 
            !e.target.closest('.sidebar') && 
            !e.target.closest('.sidebar-toggle')) {
            sidebarManager.close();
        }
    });
});

// Global helper functions
window.closeSidebarOnMobile = function() {
    if (window.innerWidth <= 992) {
        sidebarManager.close();
    }
};
</script>
</body>
</html>