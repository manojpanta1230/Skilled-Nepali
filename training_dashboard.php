
<?php
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if (!is_training_center()) die("<div class='alert alert-danger'>Access Denied: Training Centers only.</div>");

$u = current_user();
$center_id = $u['id'];

// Fetch training center's posting limits
// Fetch training center's posting limits with only approved courses count
// Fetch training center's posting limits - Count both approved AND pending courses
$limit_query = $mysqli->query("
    SELECT 
        u.max_courses_allowed, 
        u.can_post,
        COALESCE((
            SELECT COUNT(*) 
            FROM courses c 
            WHERE c.training_center_id = u.id 
            AND (c.status = 'approved' OR c.status = 'pending')  -- Changed this line
        ), 0) as approved_count
    FROM users u 
    WHERE u.id = $center_id
");

$limit_data = $limit_query->fetch_assoc();
$max_allowed = $limit_data['max_courses_allowed'];
$can_post = $limit_data['can_post'];
$approved_count = $limit_data['approved_count']; // Only approved courses
$remaining_posts = $max_allowed - $approved_count;

// For backward compatibility, keep post_count as approved_count
$post_count = $approved_count;
function sendMail($toEmail, $toName, $subject, $htmlBody)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'skillednepali.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'inquiry@skillednepali.com';
        $mail->Password   = 'adgjl@900';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        $mail->setFrom('inquiry@skillednepali.com', 'Job Portal of Skilled Nepali');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}
function getApprovedCourseCount($center_id, $mysqli)
{
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM courses WHERE training_center_id = ? AND (status = 'approved' OR status = 'pending')");  // Changed this line
    $stmt->bind_param("i", $center_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['count'];
}

// Use it after posting a course:
// $post_count = getApprovedCourseCount($center_id, $mysqli);
// $remaining_posts = $max_allowed - $post_count;
// Handle image upload
if (isset($_POST['upload_image'])) {
    $file = $_FILES['profile_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

    if (in_array($file['type'], $allowed_types) && $file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = 'uploads/profile_' . $u['id'] . '_' . time() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $new_name)) {
            $stmt = $mysqli->prepare("UPDATE users SET image = ? WHERE id = ?");
            $stmt->bind_param("si", $new_name, $u['id']);
            if ($stmt->execute()) {
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
    header("Location: training_dashboard.php");
    exit();
}
// Handle profile update for training center
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $company = trim($_POST['company']);
    $stmt = $mysqli->prepare("UPDATE users SET name=?, email=?, phone=?, address=?, company=? WHERE id=?");
    $stmt->bind_param("sssssi", $name, $email, $phone, $address, $company, $u['id']);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        // Update $u for current session
        $u['name'] = $name;
        $u['email'] = $email;
        $u['phone'] = $phone;
        $u['address'] = $address;
        $u['company'] = $company;
    } else {
        $_SESSION['error_message'] = "Failed to update profile.";
    }
    $stmt->close();
    header("Location: training_dashboard.php");
    exit();
}
// Handle course posting WITH LIMIT CHECK
// Handle course posting WITH LIMIT CHECK
if (isset($_POST['submit_course'])) {
    // Check if training center can post more courses
    if (!$can_post) {
        $_SESSION['error_message'] = "❌ Your posting permission has been disabled. Please contact admin.";
        header("Location: training_dashboard.php?tab=post_course");
        exit();
    }

    // Check posting limit
    if ($post_count >= $max_allowed) {
        $_SESSION['error_message'] = "❌ Course posting limit reached! You have posted {$post_count} out of {$max_allowed} allowed courses.";
        header("Location: training_dashboard.php?tab=post_course");
        exit();
    }

    $title = trim($_POST['title']);
    $structure = trim($_POST['structure']);
    $cost = trim($_POST['cost']);
    $description = trim($_POST['description'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $prerequisites = trim($_POST['prerequisites'] ?? '');

    if (!$title || !$structure || !$cost) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
    } else {
        // Start transaction
        $mysqli->begin_transaction();

        try {
            // Insert course
            $stmt = $mysqli->prepare("INSERT INTO courses (training_center_id, title, structure, cost, description, duration, prerequisites, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("issssss", $center_id, $title, $structure, $cost, $description, $duration, $prerequisites);

            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $mysqli->error);
            }

            $course_id = $stmt->insert_id;
            $stmt->close();

            // INCREMENT post_count in users table immediately
            $update_stmt = $mysqli->prepare("UPDATE users SET post_count = post_count + 1 WHERE id = ?");
            $update_stmt->bind_param("i", $center_id);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update post count: " . $mysqli->error);
            }
            
            $update_stmt->close();

            // Commit transaction
            $mysqli->commit();

            // Refresh post count data
            $limit_query = $mysqli->query("SELECT post_count, max_courses_allowed FROM users WHERE id = $center_id");
            $limit_data = $limit_query->fetch_assoc();
            $post_count = $limit_data['post_count'];
            $max_allowed = $limit_data['max_courses_allowed'];
            $remaining_posts = $max_allowed - $post_count;

            // Email details
            $admin_email = "pantamanoj08@gmail.com";
            $training_center_email = $u['email'];
            $training_center_name = $u['name'];

            $subject = "New Course Posted - Awaiting Approval";

            // Admin email body
            $admin_body = "
                <h3>New Course Posted - Awaiting Approval</h3>
                <p>A training center has submitted a new course for review.</p>
                <hr>
                <p><strong>Training Center:</strong> {$training_center_name}</p>
                <p><strong>Email:</strong> {$training_center_email}</p>
                <p><strong>Course Title:</strong> {$title}</p>
                <p><strong>Course Structure:</strong> {$structure}</p>
                <p><strong>Cost:</strong> {$cost}</p>
                <p><strong>Duration:</strong> {$duration}</p>
                <p><strong>Description:</strong> {$description}</p>
                <p><strong>Prerequisites:</strong> {$prerequisites}</p>
                <p><strong>Center's Posting Status:</strong> {$post_count}/{$max_allowed} total courses (pending approval)</p>
                <hr>
                <p>Please review and approve/decline this course from the admin panel.</p>
                <br>
                <p>Job Portal of Skilled Nepali</p>
            ";

            // Training center confirmation email
            $center_body = "
                <h3>Course Submission Confirmation</h3>
                <p>Dear <strong>{$training_center_name}</strong>,</p>
                <p>Your course has been submitted successfully and is awaiting admin approval.</p>
                <hr>
                <p><strong>Course Details:</strong></p>
                <p><strong>Title:</strong> {$title}</p>
                <p><strong>Structure:</strong> {$structure}</p>
                <p><strong>Cost:</strong> {$cost}</p>
                <p><strong>Duration:</strong> {$duration}</p>
                <p><strong>Description:</strong> {$description}</p>
                <p><strong>Status:</strong> <span style='color: orange;'><strong>Pending Approval</strong></span></p>
                <p><strong>Important Note:</strong> Your post count has been updated immediately.</p>
                <p><strong>Your Posting Status:</strong> You have used <strong>{$post_count}</strong> out of <strong>{$max_allowed}</strong> allowed posts. <strong>{$remaining_posts}</strong> posts remaining.</p>
                <p><strong>If Approved:</strong> Course will go live</p>
                <p><strong>If Declined:</strong> Your post count will decrease by 1</p>
                <hr>
                <p>You will be notified once the admin reviews your course.</p>
                <br>
                <p>Best regards,<br>Job Portal Team</p>
            ";

            // Send emails
            sendMail($admin_email, "Admin", $subject, $admin_body);
            sendMail($training_center_email, $training_center_name, $subject, $center_body);

            $_SESSION['success_message'] = "✅ Course posted successfully! Awaiting admin approval. You have {$remaining_posts} posts remaining.";
        } catch (Exception $e) {
            $mysqli->rollback();
            $_SESSION['error_message'] = "❌ Error: " . $e->getMessage();
        }
    }
    header("Location: training_dashboard.php?tab=my_courses");
    exit();
}

// Handle course update
if (isset($_POST['update_course'])) {
    $course_id = intval($_POST['edit_course_id']);
    $title = trim($_POST['edit_title']);
    $cost = trim($_POST['edit_cost']);
    $duration = trim($_POST['edit_duration']);
    $description = trim($_POST['edit_description']);
    $structure = trim($_POST['edit_structure']);
    $prerequisites = trim($_POST['edit_prerequisites']);

    if (!$title || !$structure || !$cost) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
    } else {
        $stmt = $mysqli->prepare("UPDATE courses SET title = ?, cost = ?, duration = ?, description = ?, structure = ?, prerequisites = ?, status = CASE WHEN status = 'approved' THEN 'approved' ELSE 'pending' END WHERE id = ? AND training_center_id = ?");
        $stmt->bind_param("ssssssii", $title, $cost, $duration, $description, $structure, $prerequisites, $course_id, $center_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "✅ Course updated successfully!";
        } else {
            $_SESSION['error_message'] = "❌ Error updating course: " . $mysqli->error;
        }
        $stmt->close();
    }
    header("Location: training_dashboard.php?tab=my_courses");
    exit();
}

// Handle application status update
if (isset($_POST['update_application_status'])) {
    $application_id = intval($_POST['application_id']);
    $status = trim($_POST['status']);

    $stmt = $mysqli->prepare("UPDATE course_applications SET status = ? WHERE id = ? AND EXISTS (SELECT 1 FROM courses c WHERE c.id = course_applications.course_id AND c.training_center_id = ?)");
    $stmt->bind_param("sii", $status, $application_id, $center_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Application status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update application status.";
    }
    $stmt->close();
    header("Location: training_dashboard.php?tab=applications");
    exit();
}

// Fetch training center statistics
$total_courses = $mysqli->query("SELECT COUNT(*) as total FROM courses WHERE training_center_id = $center_id")->fetch_assoc()['total'];
$active_courses = $mysqli->query("SELECT COUNT(*) as active FROM courses WHERE training_center_id = $center_id AND status='approved'")->fetch_assoc()['active'];
$pending_courses = $mysqli->query("SELECT COUNT(*) as pending FROM courses WHERE training_center_id = $center_id AND status='pending'")->fetch_assoc()['pending'];

// Recent applications (last 10)
$sql = "
    SELECT ca.*, c.title AS course_title, u.name AS student_name, u.email
    FROM course_applications ca
    JOIN courses c ON ca.course_id = c.id
    JOIN users u ON ca.user_id = u.id
    WHERE c.training_center_id = ?
    ORDER BY ca.created_at DESC
    LIMIT 10
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $center_id);
$stmt->execute();
$recent_applications = $stmt->get_result();

// Total applications
$total_applications = $mysqli->query("
    SELECT COUNT(*) as total FROM course_applications ca
    JOIN courses c ON ca.course_id = c.id
    WHERE c.training_center_id = $center_id
")->fetch_assoc()['total'];

// Today's applications
$today = date('Y-m-d');
$today_applications = $mysqli->query("
    SELECT COUNT(*) as today FROM course_applications ca
    JOIN courses c ON ca.course_id = c.id
    WHERE c.training_center_id = $center_id AND DATE(ca.created_at) = '$today'
")->fetch_assoc()['today'];

// Fetch all courses for my_courses tab
$courses_sql = "SELECT * FROM courses WHERE training_center_id = ?";
$courses_stmt = $mysqli->prepare($courses_sql);
$courses_stmt->bind_param("i", $center_id);
$courses_stmt->execute();
$courses = $courses_stmt->get_result();

// Fetch all applications for applications tab
$all_applications_sql = "
    SELECT ca.*, c.title AS course_title, u.name AS student_name, u.email
    FROM course_applications ca
    JOIN courses c ON ca.course_id = c.id
    JOIN users u ON ca.user_id = u.id
    WHERE c.training_center_id = ?
    ORDER BY ca.created_at DESC
";
$all_applications_stmt = $mysqli->prepare($all_applications_sql);
$all_applications_stmt->bind_param("i", $center_id);
$all_applications_stmt->execute();
$all_applications = $all_applications_stmt->get_result();

// Set active tab from URL parameter
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'training_dashboard';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="img/Logo.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Center Dashboard</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --sidebar-bg: #1e293b;
            --sidebar-active: #10b981;
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
            background: var(--success-color);
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
            color: var(--success-color);
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

        .stat-card.total-courses {
            border-left-color: var(--secondary-color);
        }

        .stat-card.active-courses {
            border-left-color: var(--success-color);
        }

        .stat-card.pending-courses {
            border-left-color: var(--warning-color);
        }

        .stat-card.total-applications {
            border-left-color: var(--info-color);
        }

        .stat-card.today-applications {
            border-left-color: var(--primary-color);
        }

        .stat-card.posting-limit {
            border-left-color: var(--danger-color);
        }

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

        .stat-icon.total-courses {
            background: rgba(139, 92, 246, 0.1);
            color: var(--secondary-color);
        }

        .stat-icon.active-courses {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .stat-icon.pending-courses {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .stat-icon.total-applications {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
        }

        .stat-icon.today-applications {
            background: rgba(0, 160, 152, 0.1);
            color: var(--primary-color);
        }

        .stat-icon.posting-limit {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

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
            background: linear-gradient(135deg, var(--success-color), #0da674);
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
            color: var(--success-color);
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
            color: var(--success-color);
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
            color: var(--success-color);
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
            background: rgba(16, 185, 129, 0.05);
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
            background: linear-gradient(135deg, var(--success-color), #0da674);
            color: white;
        }

        .btn-outline-modern {
            border: 2px solid var(--success-color);
            color: var(--success-color);
            background: transparent;
        }

        .btn-outline-modern:hover {
            background: var(--success-color);
            color: white;
        }

        .btn-danger-modern {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
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
            border-left: 4px solid var(--secondary-color);
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .action-card.post-course {
            border-left-color: var(--secondary-color);
        }

        .action-card.view-courses {
            border-left-color: var(--success-color);
        }

        .action-card.manage-profile {
            border-left-color: var(--warning-color);
        }

        .action-card.view-applications {
            border-left-color: var(--info-color);
        }

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

        .action-card.post-course .action-icon {
            background: rgba(139, 92, 246, 0.1);
            color: var(--secondary-color);
        }

        .action-card.view-courses .action-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .action-card.manage-profile .action-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .action-card.view-applications .action-icon {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
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

        .alert-modern.alert-warning {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: white;
        }

        .alert-modern.alert-info {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
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

        /* Course specific styles */
        .course-status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Application specific styles */
        .application-status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .application-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .application-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .application-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Limit Alert */
        .limit-alert {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            z-index: 1100;
            width: 90%;
            max-width: 500px;
            overflow: hidden;
            display: none;
        }

        .limit-alert.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        .limit-alert-header {
            background: linear-gradient(135deg, #ef4444, #f87171);
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .limit-alert-body {
            padding: 2rem;
            text-align: center;
        }

        .limit-alert-footer {
            padding: 1.5rem;
            text-align: center;
            border-top: 1px solid var(--border-color);
        }

        .limit-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1099;
        }

        .limit-overlay.show {
            display: block;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }

            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        /* Progress bar */
        .progress-container {
            margin: 1.5rem 0;
        }

        .progress {
            height: 12px;
            border-radius: 6px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 6px;
            transition: width 0.3s ease;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        /* Contact Admin Card */
        .contact-admin-card {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .contact-admin-card h5 {
            color: #92400e;
            margin-bottom: 0.5rem;
        }

        .contact-admin-card p {
            color: #92400e;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Limit Reached Modal -->
    <div class="limit-overlay" id="limitOverlay"></div>
    <div class="limit-alert" id="limitAlert">
        <div class="limit-alert-header">
            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
            <h3>Course Posting Limit Reached!</h3>
        </div>
        <div class="limit-alert-body">
            <h4 class="mb-3">You have reached your maximum course posting limit.</h4>
            <p class="text-muted mb-4">
                You have posted <strong><?= $post_count ?></strong> out of <strong><?= $max_allowed ?></strong> allowed courses.
            </p>

            <div class="progress-container">
                <div class="progress-label">
                    <span>Posting Progress</span>
                    <span><?= $post_count ?>/<?= $max_allowed ?></span>
                </div>
                <div class="progress">
                    <?php
                    $percentage = ($post_count / $max_allowed) * 100;
                    $progress_color = $percentage >= 100 ? 'bg-danger' : ($percentage >= 80 ? 'bg-warning' : 'bg-success');
                    ?>
                    <div class="progress-bar <?= $progress_color ?>" style="width: <?= min($percentage, 100) ?>%"></div>
                </div>
            </div>

            <div class="contact-admin-card">
                <h5><i class="fas fa-headset me-2"></i> Need More Posts?</h5>
                <p>Contact the admin team to increase your posting limit.</p>
                <div class="d-grid">
                    <button class="btn btn-danger-modern btn-modern" onclick="closeLimitModal()">
                        <i class="fas fa-envelope me-2"></i> Contact Admin
                    </button>
                </div>
            </div>
        </div>
                                            <!-- Edit Course Modal -->
                                            <!-- <div class="modal fade" id="editCourseModal<?= $course['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="post" action="training_dashboard.php?tab=my_courses">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h5 class="modal-title">Edit Course Details</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="edit_course_id" value="<?= $course['id'] ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold">Course Title</label>
                                                                    <input type="text" name="edit_title" class="form-control" value="<?= htmlspecialchars($course['title']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold">Cost (in USD)</label>
                                                                    <input type="text" name="edit_cost" class="form-control" value="<?= htmlspecialchars($course['cost']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold">Duration</label>
                                                                    <input type="text" name="edit_duration" class="form-control" value="<?= htmlspecialchars($course['duration']) ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold">Course Description</label>
                                                                    <textarea name="edit_description" class="form-control" rows="4"><?= htmlspecialchars($course['description']) ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold">Course Structure</label>
                                                                    <textarea name="edit_structure" class="form-control" rows="6" required><?= htmlspecialchars($course['structure']) ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold">Prerequisites</label>
                                                                    <input type="text" name="edit_prerequisites" class="form-control" value="<?= htmlspecialchars($course['prerequisites']) ?>">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="update_course" class="btn btn-success">
                                                                    <i class="fas fa-save me-2"></i>Save Changes
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div> -->
        <div class="limit-alert-footer">
            <button class="btn btn-outline-modern btn-modern" onclick="closeLimitModal()">
                <i class="fas fa-times me-2"></i> Close
            </button>
        </div>
    </div>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <div>
                <h3><i class="fas fa-graduation-cap me-2"></i>Training Center</h3>
                <small class="text-light opacity-75">Course Management</small>
            </div>
            <button class="sidebar-close" onclick="toggleSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <div class="welcome-avatar">
                    <?php if (!empty($u['image'])): ?>
                        <img src="<?= htmlspecialchars($u['image']) ?>" alt="Profile Picture">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="welcome-text">
                    <h4><?= htmlspecialchars($u['company'] ?? $u['name']) ?></h4>
                    <p>Training Center Dashboard</p>
                    <small class="opacity-75">
                        <i class="fas fa-chart-bar me-1"></i>
                        <?= $post_count ?>/<?= $max_allowed ?> courses posted
                    </small>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="sidebar-menu">
            <!-- Dashboard Tab -->
            <div class="sidebar-item">
                <a href="training_dashboard.php?tab=training_dashboard"
                    class="sidebar-link <?= $active_tab == 'training_dashboard' ? 'active' : '' ?>"
                    onclick="closeSidebarOnMobile()">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <!-- Post Course Tab -->
            <div class="sidebar-item">
                <a href="training_dashboard.php?tab=post_course"
                    class="sidebar-link <?= $active_tab == 'post_course' ? 'active' : '' ?>"
                    onclick="closeSidebarOnMobile()">
                    <i class="fas fa-plus-circle"></i>
                    <span>Post New Course</span>
                    <?php if ($remaining_posts > 0): ?>
                        <span class="badge bg-success rounded-pill"><?= $remaining_posts ?> left</span>
                    <?php else: ?>
                        <span class="badge bg-danger rounded-pill">Limit Reached</span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- My Courses Tab -->
            <div class="sidebar-item">
                <a href="training_dashboard.php?tab=my_courses"
                    class="sidebar-link <?= $active_tab == 'my_courses' ? 'active' : '' ?>"
                    onclick="closeSidebarOnMobile()">
                    <i class="fas fa-book"></i>
                    <span>My Courses</span>
                    <?php if ($total_courses > 0): ?>
                        <span class="badge bg-info rounded-pill"><?= $total_courses ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Applications Tab -->
            <div class="sidebar-item">
                <a href="training_dashboard.php?tab=applications"
                    class="sidebar-link <?= $active_tab == 'applications' ? 'active' : '' ?>"
                    onclick="closeSidebarOnMobile()">
                    <i class="fas fa-file-alt"></i>
                    <span>Applications</span>
                    <?php if ($total_applications > 0): ?>
                        <span class="badge bg-success rounded-pill"><?= $total_applications ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Logout -->
            <div class="sidebar-item">
                <a href="#" class="sidebar-link" data-bs-toggle="modal" data-bs-target="#manageProfileModal">
                    <i class="fas fa-user-cog"></i>
                    <span>Manage Profile</span>
                </a>
            </div>

            <!-- Logout -->
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
                    <h4>Training Center Dashboard</h4>
                    <small>Manage your courses and student applications</small>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-success rounded-pill px-3 py-2">
                    <i class="fas fa-graduation-cap me-1"></i> <span class="d-none d-md-inline">Training Center Account</span><span class="d-md-none">Training</span>
                </span>
                <span class="badge <?= $remaining_posts > 0 ? 'bg-info' : 'bg-danger' ?> rounded-pill px-3 py-2">
                    <i class="fas fa-chart-bar me-1"></i>
                    <?= $post_count ?>/<?= $max_allowed ?> <span class="d-none d-md-inline">Courses</span>
                </span>
                <a href="documentation.php" class="btn btn-info btn-sm">
                    <i class="fas fa-book me-1"></i> <span class="d-none d-md-inline">View Documentation</span><span class="d-md-none">Docs</span>
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="content-wrapper">
            <!-- Posting Limit Warning -->
            <?php if (!$can_post): ?>
                <div class="alert-modern alert-danger">
                    <i class="fas fa-ban"></i>
                    <div>
                        <strong>Posting Disabled!</strong> Your course posting permission has been disabled.
                        Please contact the administrator to enable posting.
                    </div>
                </div>
            <?php elseif ($post_count >= $max_allowed): ?>
                <div class="alert-modern alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Course Posting Limit Reached!</strong>
                        You have posted <?= $post_count ?> out of <?= $max_allowed ?> allowed courses.
                        <a href="#" class="text-white text-decoration-underline fw-bold" onclick="showLimitModal()">Click here</a> to contact admin for limit increase.
                    </div>
                </div>
            <?php elseif ($remaining_posts <= 2): ?>
                <div class="alert-modern alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Posting Limit Alert:</strong>
                        You have <?= $remaining_posts ?> course posting<?= $remaining_posts == 1 ? '' : 's' ?> remaining.
                        You can post up to <?= $max_allowed ?> courses.
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($active_tab == 'training_dashboard'): ?>
                <!-- Quick Actions -->
                <div class="quick-actions mb-4">
                    <a href="training_dashboard.php?tab=post_course"
                        class="action-card post-course <?= !$can_post || $post_count >= $max_allowed ? 'disabled' : '' ?>"
                        <?php if (!$can_post || $post_count >= $max_allowed): ?>
                        onclick="showLimitModal(); return false;"
                        <?php endif; ?>>
                        <div class="action-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="action-content">
                            <h4>Post New Course</h4>
                            <p>Create a new course offering</p>
                            <small class="<?= $remaining_posts <= 2 ? 'text-warning' : 'text-success' ?>">
                                <?= $remaining_posts ?> posting<?= $remaining_posts == 1 ? '' : 's' ?> remaining
                            </small>
                        </div>
                        <i class="fas fa-chevron-right ms-auto"></i>
                    </a>

                    <a href="training_dashboard.php?tab=my_courses" class="action-card view-courses">
                        <div class="action-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="action-content">
                            <h4>View My Courses</h4>
                            <p>Manage existing courses</p>
                        </div>
                        <i class="fas fa-chevron-right ms-auto"></i>
                    </a>

                    <a href="training_dashboard.php?tab=applications" class="action-card view-applications">
                        <div class="action-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="action-content">
                            <h4>View Applications</h4>
                            <p>Manage student applications</p>
                        </div>
                        <i class="fas fa-chevron-right ms-auto"></i>
                    </a>

                    <a href="#" class="action-card manage-profile" data-bs-toggle="modal" data-bs-target="#manageProfileModal">
                        <div class="action-icon">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <div class="action-content">
                            <h4>Manage Profile</h4>
                            <p>Update your center details</p>
                        </div>
                        <i class="fas fa-chevron-right ms-auto"></i>
                    </a>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card total-courses">
                        <div class="stat-icon total-courses">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $total_courses ?></div>
                            <div class="stat-label">Total Courses</div>
                        </div>
                    </div>

                    <div class="stat-card active-courses">
                        <div class="stat-icon active-courses">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $active_courses ?></div>
                            <div class="stat-label">Active Courses</div>
                        </div>
                    </div>

                    <div class="stat-card pending-courses">
                        <div class="stat-icon pending-courses">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $pending_courses ?></div>
                            <div class="stat-label">Pending Approval</div>
                        </div>
                    </div>

                    <div class="stat-card total-applications">
                        <div class="stat-icon total-applications">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $total_applications ?></div>
                            <div class="stat-label">Total Applications</div>
                        </div>
                    </div>

                    <div class="stat-card today-applications">
                        <div class="stat-icon today-applications">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $today_applications ?></div>
                            <div class="stat-label">Today's Applications</div>
                        </div>
                    </div>

                    <div class="stat-card posting-limit">
                        <div class="stat-icon posting-limit">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?= $post_count ?>/<?= $max_allowed ?></div>
                            <div class="stat-label">Posting Limit</div>
                            <small class="<?= $remaining_posts <= 2 ? 'text-warning' : 'text-success' ?>">
                                <?= $remaining_posts ?> posts remaining
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Grid -->
                <div class="dashboard-grid">
                    <!-- Profile Card -->
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <?php if (!empty($u['image'])): ?>
                                    <img src="<?= htmlspecialchars($u['image']) ?>" alt="Profile Picture">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="profile-name"><?= htmlspecialchars($u['company'] ?? $u['name']) ?></div>
                            <div class="profile-role">Training Center</div>
                            <div class="mt-2">
                                <span class="badge <?= $can_post ? 'bg-success' : 'bg-danger' ?>">
                                    <i class="fas <?= $can_post ? 'fa-check' : 'fa-ban' ?> me-1"></i>
                                    Posting <?= $can_post ? 'Enabled' : 'Disabled' ?>
                                </span>
                            </div>
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

                                <?php if (!empty($u['phone'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-phone"></i>
                                        <div>
                                            <div class="info-label">Phone</div>
                                            <div class="info-value"><?= htmlspecialchars($u['phone']) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($u['address'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <div>
                                            <div class="info-label">Address</div>
                                            <div class="info-value"><?= htmlspecialchars($u['address']) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="info-item">
                                    <i class="fas fa-chart-bar"></i>
                                    <div>
                                        <div class="info-label">Posting Status</div>
                                        <div class="info-value">
                                            <strong><?= $post_count ?>/<?= $max_allowed ?></strong> courses posted
                                            <?php if ($remaining_posts > 0): ?>
                                                <span class="text-success">(<?= $remaining_posts ?> remaining)</span>
                                            <?php else: ?>
                                                <span class="text-danger">(Limit reached)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
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
                            <a href="training_dashboard.php?tab=applications" class="btn btn-outline-modern btn-modern">
                                <i class="fas fa-eye"></i> View All
                            </a>
                        </div>

                        <div class="card-body">
                            <?php if ($recent_applications->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-modern">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Course</th>
                                                <th>Applied Date</th>
                                                
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $i = 1;
                                            while ($app = $recent_applications->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-placeholder rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                                <?= strtoupper(substr($app['student_name'], 0, 1)) ?>
                                                            </div>
                                                            <div>
                                                                <div class="fw-medium"><?= htmlspecialchars($app['student_name']) ?></div>
                                                                <small class="text-muted"><?= htmlspecialchars($app['email']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="fw-medium"><?= htmlspecialchars($app['course_title']) ?></div>
                                                    </td>
                                                    <td><?= date('M d, Y', strtotime($app['created_at'])) ?></td>
                                                 
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-modern" data-bs-toggle="modal" data-bs-target="#appModal<?= $i ?>">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Application Modal -->
                                                <div class="modal fade" id="appModal<?= $i ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Application Details</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row mb-3">
                                                                    <div class="col-12">
                                                                        <h6 class="text-muted mb-1">Student Information</h6>
                                                                        <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($app['student_name']) ?></p>
                                                                        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($app['email']) ?></p>
                                                                    </div>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <div class="col-12">
                                                                        <h6 class="text-muted mb-1">Course Information</h6>
                                                                        <p class="mb-1"><strong>Course:</strong> <?= htmlspecialchars($app['course_title']) ?></p>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php $i++;
                                            endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i clasFs="fas fa-file-alt"></i>
                                    <p>No applications yet for your courses</p>
                                    <?php if ($can_post && $post_count < $max_allowed): ?>
                                        <a href="training_dashboard.php?tab=post_course" class="btn btn-primary-modern btn-modern">
                                            <i class="fas fa-plus-circle me-2"></i> Post Your First Course
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-primary-modern btn-modern" onclick="showLimitModal()">
                                            <i class="fas fa-plus-circle me-2"></i> Post Your First Course
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php elseif ($active_tab == 'post_course'): ?>
                <!-- Post Course Form -->
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Post New Course</h4>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-chart-bar me-1"></i>
                                <?= $post_count ?>/<?= $max_allowed ?> posted (<?= $remaining_posts ?> remaining)
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4">

                        <?php if (!$can_post): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-ban me-2"></i>
                                <strong>Posting Disabled!</strong> Your course posting permission has been disabled.
                                Please contact the administrator to enable posting.
                            </div>
                        <?php elseif ($post_count >= $max_allowed): ?>
                            <div class="alert alert-warning">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                    <div>
                                        <h5 class="mb-1">Course Posting Limit Reached!</h5>
                                        <p class="mb-0">You have posted <?= $post_count ?> out of <?= $max_allowed ?> allowed courses.</p>
                                        <p class="mb-0">Please contact the admin to increase your posting limit.</p>
                                        <button class="btn btn-danger btn-sm mt-2" onclick="showLimitModal()">
                                            <i class="fas fa-envelope me-1"></i> Contact Admin
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Posting Status:</strong> You can post <strong><?= $remaining_posts ?></strong> more course<?= $remaining_posts == 1 ? '' : 's' ?>.
                                <div class="progress mt-2" style="height: 8px;">
                                    <div class="progress-bar bg-info" style="width: <?= ($post_count / $max_allowed) * 100 ?>%"></div>
                                </div>
                            </div>

                            <p class="text-muted mb-4">
                                Share details about your new course offering. Once submitted, it will be reviewed by the admin team before going live.
                            </p>

                            <form method="post" id="courseForm">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">Course Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" placeholder="e.g., Advanced Web Development" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Cost (in USD) <span class="text-danger">*</span></label>
                                        <input type="text" name="cost" class="form-control" placeholder="e.g., 199.99 or Free" required>
                                        <small class="text-muted">Enter amount or "Free" for no-cost courses</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Duration (Time Period)</label>
                                        <input type="text" name="duration" class="form-control" placeholder="e.g., 12 weeks, 3 months, 60 hours">
                                        <small class="text-muted">Specify the course duration period</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Course Description</label>
                                    <textarea name="description" class="form-control" rows="4" placeholder="Detailed description of the course, objectives, and benefits..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Course Structure <span class="text-danger">*</span></label>
                                    <textarea name="structure" class="form-control" rows="6" placeholder="Outline the main topics, modules, or skills covered..." required></textarea>
                                    <small class="text-muted">Please provide detailed course structure including modules, topics, and learning outcomes</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Prerequisites</label>
                                        <input type="text" name="prerequisites" class="form-control" placeholder="e.g., Basic programming knowledge, High school diploma">
                                        <small class="text-muted">Required knowledge or qualifications before taking this course</small>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="reset" class="btn btn-outline-secondary btn-modern">
                                        <i class="fas fa-redo me-2"></i> Reset
                                    </button>
                                    <button type="submit" name="submit_course" class="btn btn-primary-modern btn-modern">
                                        <i class="fas fa-paper-plane me-2"></i> Submit Course
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($active_tab == 'my_courses'): ?>
                <!-- My Courses Tab -->
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-book me-2"></i> My Courses</h4>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-chart-bar me-1"></i>
                                <?= $post_count ?>/<?= $max_allowed ?> posted
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($courses->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>Course Title</th>
                                            <th>Structure</th>
                                            <th>Cost</th>
                                            <th>Status</th>

                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($course = $courses->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($course['title']) ?></strong>
                                                </td>
                                                <td>
                                                    <?= nl2br(htmlspecialchars(substr($course['structure'], 0, 100))) ?>...
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <?php
                                                        if (is_numeric($course['cost'])) {
                                                            echo number_format((float)$course['cost'], 2);
                                                        } else {
                                                            echo htmlspecialchars($course['cost']);
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = 'status-pending';
                                                    if ($course['status'] == 'approved') {
                                                        $status_class = 'status-approved';
                                                    } elseif ($course['status'] == 'rejected') {
                                                        $status_class = 'status-rejected';
                                                    }
                                                    ?>
                                                    <span class="course-status-badge <?= $status_class ?>">
                                                        <?= ucfirst($course['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info me-1" data-bs-toggle="modal" data-bs-target="#courseViewModal<?= $course['id'] ?>">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCourseModal<?= $course['id'] ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Course Modals -->
                            <?php 
                            $courses->data_seek(0);
                            while ($course = $courses->fetch_assoc()): 
                            ?>
                                <div class="modal fade" id="editCourseModal<?= $course['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content text-dark">
                                            <form method="post" action="training_dashboard.php?tab=my_courses">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">Edit Course Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="edit_course_id" value="<?= $course['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Course Title</label>
                                                        <input type="text" name="edit_title" class="form-control" value="<?= htmlspecialchars($course['title']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Cost</label>
                                                        <input type="text" name="edit_cost" class="form-control" value="<?= htmlspecialchars($course['cost']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Duration</label>
                                                        <input type="text" name="edit_duration" class="form-control" value="<?= htmlspecialchars($course['duration']) ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Course Description</label>
                                                        <textarea name="edit_description" class="form-control" rows="4"><?= htmlspecialchars($course['description']) ?></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Course Structure</label>
                                                        <textarea name="edit_structure" class="form-control" rows="6" required><?= htmlspecialchars($course['structure']) ?></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Prerequisites</label>
                                                        <input type="text" name="edit_prerequisites" class="form-control" value="<?= htmlspecialchars($course['prerequisites']) ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="update_course" class="btn btn-success">
                                                        <i class="fas fa-save me-2"></i>Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Course View Modal -->
                                <div class="modal fade" id="courseViewModal<?= $course['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content text-dark">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Course Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-4">
                                                    <h4 class="text-primary"><?= htmlspecialchars($course['title']) ?></h4>
                                                    <?php
                                                    $status_class = 'status-pending';
                                                    if ($course['status'] == 'approved') {
                                                        $status_class = 'status-approved';
                                                    } elseif ($course['status'] == 'rejected') {
                                                        $status_class = 'status-rejected';
                                                    }
                                                    ?>
                                                    <span class="course-status-badge <?= $status_class ?>">
                                                        <?= ucfirst($course['status']) ?>
                                                    </span>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <h6><strong>Cost:</strong></h6>
                                                        <p class="text-success fw-bold">
                                                            <?php
                                                            if (is_numeric($course['cost'])) {
                                                                echo number_format((float)$course['cost'], 2);
                                                            } else {
                                                                echo htmlspecialchars($course['cost']);
                                                            }
                                                            ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6><strong>Duration:</strong></h6>
                                                        <p><?= htmlspecialchars($course['duration']) ?> weeks</p>
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    <h6><strong>Course Structure:</strong></h6>
                                                    <div class="bg-light p-3 rounded">
                                                        <?= nl2br(htmlspecialchars($course['structure'])) ?>
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    <h6><strong>Course Description:</strong></h6>
                                                    <p><?= nl2br(htmlspecialchars($course['description'])) ?></p>
                                                </div>
                                                <?php if (!empty($course['prerequisites'])): ?>
                                                <div class="mb-4">
                                                    <h6><strong>Prerequisites:</strong></h6>
                                                    <p><?= htmlspecialchars($course['prerequisites']) ?></p>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <p>You haven't posted any courses yet</p>
                        <?php if ($can_post && $post_count < $max_allowed): ?>
                            <a href="training_dashboard.php?tab=post_course" class="btn btn-primary-modern btn-modern">
                                <i class="fas fa-plus-circle me-2"></i> Post Your First Course
                            </a>
                        <?php else: ?>
                            <button class="btn btn-primary-modern btn-modern" onclick="showLimitModal()">
                                <i class="fas fa-plus-circle me-2"></i> Post Your First Course
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                </div>
        </div>

    <?php elseif ($active_tab == 'applications'): ?>
        <!-- Applications Tab -->
        <div class="card shadow-lg border-0">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i> Student Applications</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($all_applications->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Application Date</th>
                                  
                                   
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($app = $all_applications->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-placeholder rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                    <?= strtoupper(substr($app['student_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-medium"><?= htmlspecialchars($app['student_name']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($app['email']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($app['course_title']) ?></div>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($app['created_at'])) ?></td>

                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <p>No student applications yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

    <!-- Display Alerts -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-modern alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i>
            <?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert-modern alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i>
            <?= $_SESSION['error_message'] ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    </div>
    </main>

    <!-- Manage Profile Modal -->
    <div class="modal fade" id="manageProfileModal" tabindex="-1" aria-labelledby="manageProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content text-dark">
                <form method="post" action="training_dashboard.php">
                    <div class="modal-header bg-primary text-white">
                        <h4 class="modal-title" id="manageProfileModalLabel"><i class="fas fa-user-cog me-2"></i>Manage Profile</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($u['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($u['phone'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Address</label>
                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($u['address'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Company/Organization</label>
                            <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($u['company'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                    const alert = e.target.closest('.alert-modern, .alert');
                    if (alert) {
                        this.closeAlert(alert);
                    }
                }
            });
        },

        closeAllAlerts() {
            document.querySelectorAll('.alert-modern, .alert').forEach(alert => {
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

    // Limit Modal Management
    const limitModalManager = {
        overlay: null,
        modal: null,

        init() {
            this.overlay = document.getElementById('limitOverlay');
            this.modal = document.getElementById('limitAlert');

            // Check if limit is reached on page load
            setTimeout(() => {
                this.checkLimitOnLoad();
            }, 500);
        },

        checkLimitOnLoad() {
            const remainingPosts = <?= $remaining_posts ?>;
            const canPost = <?= $can_post ? 'true' : 'false' ?>;
            const activeTab = '<?= $active_tab ?>';

            // Show modal if posting is disabled or limit is reached
            if (!canPost || remainingPosts <= 0) {
                if (activeTab === 'post_course') {
                    this.show();
                }
            }
        },

        show() {
            if (this.overlay && this.modal) {
                this.overlay.classList.add('show');
                this.modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        },

        close() {
            if (this.overlay && this.modal) {
                this.overlay.classList.remove('show');
                this.modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
    };

    // Form validation for course posting
    function validateCourseForm() {
        const form = document.getElementById('courseForm');
        if (!form) return true;

        const remainingPosts = <?= $remaining_posts ?>;
        const canPost = <?= $can_post ? 'true' : 'false' ?>;

        if (!canPost) {
            alert('Your posting permission has been disabled. Please contact admin.');
            return false;
        }

        if (remainingPosts <= 0) {
            showLimitModal();
            return false;
        }

        return true;
    }

    // Global helper functions
    window.closeSidebarOnMobile = function() {
        if (window.innerWidth <= 992) {
            sidebarManager.close();
        }
    };

    window.showLimitModal = function() {
        limitModalManager.show();
    };

    window.closeLimitModal = function() {
        limitModalManager.close();
    };

    window.toggleSidebar = function() {
        sidebarManager.toggle();
    };

    window.contactAdmin = function() {
        // You can implement email functionality here
        const email = 'admin@skillednepali.com';
        const subject = 'Request to Increase Course Posting Limit';
        const body = `Dear Admin,\n\nI am requesting to increase my course posting limit.\n\nCurrent Status: <?= $post_count ?>/<?= $max_allowed ?> courses posted\nTraining Center: <?= htmlspecialchars($u['name']) ?>\nEmail: <?= htmlspecialchars($u['email']) ?>\n\nPlease let me know the procedure to increase my posting limit.\n\nThank you.`;

        window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        limitModalManager.close();
    };

    // Initialize everything when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize sidebar
        sidebarManager.init();
        
        // Initialize alerts
        alertManager.init();
        
        // Initialize limit modal
        limitModalManager.init();
        
        // Add form validation
        const courseForm = document.getElementById('courseForm');
        if (courseForm) {
            courseForm.addEventListener('submit', function(e) {
                if (!validateCourseForm()) {
                    e.preventDefault();
                    return false;
                }
            });
        }
        
        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && 
                sidebarManager.isOpen() && 
                !e.target.closest('.sidebar') && 
                !e.target.closest('.sidebar-toggle')) {
                sidebarManager.close();
            }
        });
        
        // Close limit modal when clicking overlay
        document.getElementById('limitOverlay')?.addEventListener('click', closeLimitModal);
    });
</script>
</body>

</html>