<?php 
include 'config.php';
require_login(); 
if(!is_employer()) die("<div class='alert alert-danger'>Access Denied: Employers only.</div>");

$u = current_user();
$employer_id = $u['id'];

// Fetch employer statistics
$total_jobs = $mysqli->query("SELECT COUNT(*) as total FROM jobs WHERE employer_id = $employer_id")->fetch_assoc()['total'];
$active_jobs = $mysqli->query("SELECT COUNT(*) as active FROM jobs WHERE employer_id = $employer_id AND status='approved'")->fetch_assoc()['active'];
$pending_jobs = $mysqli->query("SELECT COUNT(*) as pending FROM jobs WHERE employer_id = $employer_id AND status='pending'")->fetch_assoc()['pending'];

// Recent applications (last 10)
$sql = "
    SELECT a.*, j.title AS job_title, u.name AS jobseeker_name, u.email, a.phone, a.address, a.photo 
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    WHERE j.employer_id = ?
    ORDER BY a.created_at DESC
    LIMIT 10
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$recent_apps = $stmt->get_result();

// Total applications
$total_apps = $mysqli->query("
    SELECT COUNT(*) as total FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE j.employer_id = $employer_id
")->fetch_assoc()['total'];

// Today's applications
$today = date('Y-m-d');
$today_apps = $mysqli->query("
    SELECT COUNT(*) as today FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE j.employer_id = $employer_id AND DATE(a.created_at) = '$today'
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
    header("Location: dashboard.php");
    exit();
}

// Set active tab from URL parameter
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard</title>
    
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
        --secondary-color: #FF6B6B;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --info-color: #3b82f6;
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
        background: var(--primary-color);
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
    
    .stat-card.total-jobs { border-left-color: var(--info-color); }
    .stat-card.active-jobs { border-left-color: var(--success-color); }
    .stat-card.pending-jobs { border-left-color: var(--warning-color); }
    .stat-card.total-apps { border-left-color: var(--primary-color); }
    .stat-card.today-apps { border-left-color: var(--secondary-color); }
    
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
    
    .stat-icon.total-jobs { background: rgba(59, 130, 246, 0.1); color: var(--info-color); }
    .stat-icon.active-jobs { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
    .stat-icon.pending-jobs { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
    .stat-icon.total-apps { background: rgba(0, 160, 152, 0.1); color: var(--primary-color); }
    .stat-icon.today-apps { background: rgba(255, 107, 107, 0.1); color: var(--secondary-color); }
    
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
    
    /* Dashboard Grid */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    @media (max-width: 992px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* Profile Card */
    .profile-card {
        background: white;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        height: fit-content;
    }
    
    .profile-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        padding: 2rem;
        text-align: center;
        color: white;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid white;
        overflow: hidden;
        margin: 0 auto 1rem;
        background: white;
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
        background: var(--light-color);
        color: var(--primary-color);
        font-size: 3rem;
    }
    
    .profile-name {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .profile-role {
        opacity: 0.9;
        font-size: 0.9rem;
    }
    
    .profile-body {
        padding: 1.5rem;
    }
    
    .info-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .info-item i {
        width: 24px;
        color: var(--primary-color);
        margin-right: 0.75rem;
        margin-top: 0.25rem;
    }
    
    .info-label {
        font-size: 0.875rem;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    
    .info-value {
        font-weight: 500;
    }
    
    /* Applications Card */
    .applications-card {
        background: white;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
    }
    
    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: white;
    }
    
    .card-header h3 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .card-header h3 i {
        color: var(--primary-color);
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    /* Table Styles */
    .table-modern {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table-modern thead {
        background: var(--light-color);
    }
    
    .table-modern th {
        padding: 1rem;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid var(--border-color);
    }
    
    .table-modern td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-color);
    }
    
    .table-modern tbody tr {
        transition: var(--transition);
    }
    
    .table-modern tbody tr:hover {
        background: rgba(0, 160, 152, 0.05);
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
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
    }
    
    .btn-outline-modern {
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
        background: transparent;
    }
    
    .btn-outline-modern:hover {
        background: var(--primary-color);
        color: white;
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
    
    /* Quick Actions */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .action-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: var(--transition);
        text-decoration: none;
        color: inherit;
        border-left: 4px solid var(--info-color);
    }
    
    .action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    
    .action-card.post-job { border-left-color: var(--info-color); }
    .action-card.view-jobs { border-left-color: var(--success-color); }
    .action-card.manage-profile { border-left-color: var(--warning-color); }
    .action-card.view-apps { border-left-color: var(--primary-color); }
    
    .action-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .action-card.post-job .action-icon {
        background: rgba(59, 130, 246, 0.1);
        color: var(--info-color);
    }
    
    .action-card.view-jobs .action-icon {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success-color);
    }
    
    .action-card.manage-profile .action-icon {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning-color);
    }
    
    .action-card.view-apps .action-icon {
        background: rgba(0, 160, 152, 0.1);
        color: var(--primary-color);
    }
    
    .action-content h4 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
    }
    
    .action-content p {
        margin: 0.25rem 0 0;
        font-size: 0.875rem;
        color: #64748b;
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
        
        .quick-actions {
            grid-template-columns: 1fr;
        }
        
        .stat-value {
            font-size: 1.75rem;
        }
        
        .table-responsive {
            font-size: 0.875rem;
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
                <h3><i class="fas fa-building me-2"></i>Employer Panel</h3>
                <small class="text-light opacity-75">Job Portal Management</small>
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
                    <h4><?= htmlspecialchars($u['company'] ?? $u['name']) ?></h4>
                    <p>Employer Dashboard</p>
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
                <a href="post_job.php" class="sidebar-link" onclick="closeSidebarOnMobile()">
                    <i class="fas fa-plus-circle"></i>
                    <span>Post New Job</span>
                </a>
            </div>
            
            <div class="sidebar-item">
                <a href="my_jobs.php" class="sidebar-link" onclick="closeSidebarOnMobile()">
                    <i class="fas fa-briefcase"></i>
                    <span>My Jobs</span>
                    <?php if($total_jobs > 0): ?>
                    <span class="badge bg-info rounded-pill"><?= $total_jobs ?></span>
                    <?php endif; ?>
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
                    <h4>Employer Dashboard</h4>
                    <small>Manage your job posts and applications</small>
                </div>
            </div>
            <div>
                <span class="badge bg-primary rounded-pill px-3 py-2">
                    <i class="fas fa-building me-1"></i> Employer Account
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

            <!-- Quick Actions -->
            <div class="quick-actions mb-4">
                <a href="post_job.php" class="action-card post-job">
                    <div class="action-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="action-content">
                        <h4>Post New Job</h4>
                        <p>Create a new job listing</p>
                    </div>
                    <i class="fas fa-chevron-right ms-auto"></i>
                </a>
                
                <a href="my_jobs.php" class="action-card view-jobs">
                    <div class="action-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="action-content">
                        <h4>View My Jobs</h4>
                        <p>Manage existing job posts</p>
                    </div>
                    <i class="fas fa-chevron-right ms-auto"></i>
                </a>
                
             
                
              
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card total-jobs">
                    <div class="stat-icon total-jobs">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $total_jobs ?></div>
                        <div class="stat-label">Total Jobs</div>
                    </div>
                </div>
                
                <div class="stat-card active-jobs">
                    <div class="stat-icon active-jobs">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $active_jobs ?></div>
                        <div class="stat-label">Active Jobs</div>
                    </div>
                </div>
                
                <div class="stat-card pending-jobs">
                    <div class="stat-icon pending-jobs">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $pending_jobs ?></div>
                        <div class="stat-label">Pending Approval</div>
                    </div>
                </div>
                
                <div class="stat-card total-apps">
                    <div class="stat-icon total-apps">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $total_apps ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                </div>
                
                <div class="stat-card today-apps">
                    <div class="stat-icon today-apps">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $today_apps ?></div>
                        <div class="stat-label">Today's Applications</div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php if(!empty($u['image'])): ?>
                                <img src="<?= htmlspecialchars($u['image']) ?>" alt="Profile Picture">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="profile-name"><?= htmlspecialchars($u['company'] ?? $u['name']) ?></div>
                        <div class="profile-role">Employer</div>
                    </div>
                    
                    <div class="profile-body">
                        <div class="profile-info">
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?= htmlspecialchars($u['email']) ?></div>
                                </div>
                            </div>
                            
                            <?php if(!empty($u['phone'])): ?>
                            <div class="info-item">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <div class="info-label">Phone</div>
                                    <div class="info-value"><?= htmlspecialchars($u['phone']) ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($u['address'])): ?>
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <div class="info-label">Address</div>
                                    <div class="info-value"><?= htmlspecialchars($u['address']) ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Image Upload Form -->
                        <form method="post" enctype="multipart/form-data" class="mt-4">
                            <div class="mb-3">
                                <label class="form-label small text-muted">Update Profile Image</label>
                                <input type="file" class="form-control form-control-sm" name="profile_image" accept="image/*" required>
                            </div>
                            <button type="submit" name="upload_image" class="btn btn-primary-modern btn-modern w-100">
                                <i class="fas fa-cloud-upload-alt"></i> Upload Image
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Recent Applications Card -->
                <div class="applications-card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt"></i> Recent Applications</h3>
                        <a href="applications.php" class="btn btn-outline-modern btn-modern btn-sm">
                            <i class="fas fa-external-link-alt"></i> View All
                        </a>
                    </div>
                    
                    <div class="card-body">
                        <?php if($recent_apps->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Job Title</th>
                                            <th>Applied</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i=1; while($app = $recent_apps->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="applicant-avatar">
                                                        <?php if(!empty($app['photo'])): ?>
                                                            <img src="<?= htmlspecialchars($app['photo']) ?>" alt="Applicant Photo">
                                                        <?php else: ?>
                                                            <div class="avatar-placeholder">
                                                                <?= strtoupper(substr($app['jobseeker_name'], 0, 1)) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium"><?= htmlspecialchars($app['jobseeker_name']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($app['email']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-medium"><?= htmlspecialchars($app['job_title']) ?></div>
                                            </td>
                                            <td>
                                                <?= date('M d, Y', strtotime($app['created_at'])) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">Applied</span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-modern" data-bs-toggle="modal" data-bs-target="#appModal<?= $i ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Application Modal -->
                                        <div class="modal fade" id="appModal<?= $i ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Application Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-4 text-center mb-4">
                                                                <?php if(!empty($app['photo'])): ?>
                                                                    <img src="<?= htmlspecialchars($app['photo']) ?>" class="img-fluid rounded-circle" alt="Photo">
                                                                <?php else: ?>
                                                                    <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                                                                        <span class="text-white display-4"><?= strtoupper(substr($app['jobseeker_name'], 0, 1)) ?></span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="col-md-8">
                                                                <h4><?= htmlspecialchars($app['jobseeker_name']) ?></h4>
                                                                <p class="text-muted mb-4">Applied for: <strong><?= htmlspecialchars($app['job_title']) ?></strong></p>
                                                                
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <p><i class="fas fa-envelope me-2 text-primary"></i> <strong>Email:</strong> <?= htmlspecialchars($app['email']) ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p><i class="fas fa-phone me-2 text-primary"></i> <strong>Phone:</strong> <?= htmlspecialchars($app['phone']) ?></p>
                                                                    </div>
                                                                </div>
                                                                
                                                                <?php if(!empty($app['address'])): ?>
                                                                <div class="row mb-3">
                                                                    <div class="col-md-12">
                                                                        <p><i class="fas fa-map-marker-alt me-2 text-primary"></i> <strong>Address:</strong> <?= htmlspecialchars($app['address']) ?></p>
                                                                    </div>
                                                                </div>
                                                                <?php endif; ?>
                                                                
                                                                <?php if(!empty($app['notes'])): ?>
                                                                <div class="card bg-light mb-3">
                                                                    <div class="card-body">
                                                                        <h6><i class="fas fa-sticky-note me-2"></i> Applicant Notes:</h6>
                                                                        <p class="mb-0"><?= nl2br(htmlspecialchars($app['notes'])) ?></p>
                                                                    </div>
                                                                </div>
                                                                <?php endif; ?>
                                                                
                                                                <?php if(!empty($app['resume'])): ?>
                                                                <div class="mt-4">
                                                                    <a href="<?= htmlspecialchars($app['resume']) ?>" target="_blank" class="btn btn-primary-modern btn-modern">
                                                                        <i class="fas fa-download me-2"></i> Download Resume
                                                                    </a>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $i++; endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No applications yet for your jobs</p>
                                <a href="post_job.php" class="btn btn-primary-modern btn-modern">
                                    <i class="fas fa-plus-circle me-2"></i> Post Your First Job
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
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

<?php include 'portal_footer.php'; ?>