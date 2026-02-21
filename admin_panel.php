    <?php 
    include 'config.php';
    require_login(); 
    if (!is_admin()) die("<div class='alert alert-danger'>Access Denied: Admins only.</div>");

    // Include PHPMailer
    require 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    // Helper function to send email
    function sendEmail($to, $name, $subject, $body) {
        global $mysqli;
        
        try {
            $admin_email = "pantamanoj08@gmail.com"; // admin email

            // ✅ Create PHPMailer instance
            $mail = new PHPMailer(true);

            // ✅ SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'skillednepali.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'inquiry@skillednepali.com';
            $mail->Password = 'adgjl@900';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('inquiry@skillednepali.com', 'Job Portal of Skilled Nepali');
            $mail->addAddress($to, $name); // recipient
            $mail->addAddress($admin_email, 'Admin'); // admin

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }

    // ------------------------ ACTION HANDLERS ------------------------

if (isset($_POST['update_course_limit'])) {
    $userId = (int) $_POST['user_id'];
    $maxCourses = (int) $_POST['max_courses_allowed'];

    if ($maxCourses >= 0) {
        $stmt = $mysqli->prepare("
            UPDATE users 
            SET max_courses_allowed = ?
            WHERE id = ? AND role = 'training_center'
        ");
        $stmt->bind_param("ii", $maxCourses, $userId);
        $stmt->execute();
        $stmt->close();
    }
}


    // Users
    if (isset($_GET['approve_user'])) {
        $user_id = intval($_GET['approve_user']);
        if($mysqli->query("UPDATE users SET status='active' WHERE id=$user_id")) {
            $user = $mysqli->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
            $subject = "Your Account is Approved!";
            $body = "
                <p>Dear <strong>{$user['name']}</strong>,</p>
                <p>Your account on <strong>Job Portal of Skilled Nepali</strong> has been approved by the admin.</p>
                <p>You can now log in and start posting jobs or courses.</p>
                <br>
                <p>Best regards,<br>Job Portal Team</p>
            ";
            sendEmail($user['email'], $user['name'], $subject, $body);
        }
    }
    if (isset($_GET['decline_user'])) {
        $mysqli->query("DELETE FROM users WHERE id=" . intval($_GET['decline_user']));
    }

    // Jobs
    if (isset($_GET['approve_job'])) {
        $job_id = intval($_GET['approve_job']);
        if($mysqli->query("UPDATE jobs SET status='approved' WHERE id=$job_id")) {
            $job = $mysqli->query("SELECT j.*, u.name AS employer_name, u.email AS employer_email FROM jobs j JOIN users u ON j.employer_id=u.id WHERE j.id=$job_id")->fetch_assoc();
            $subject = "Your Job Post is Approved!";
            $body = "
                <p>Dear <strong>{$job['employer_name']}</strong>,</p>
                <p>Your job post titled <strong>{$job['title']}</strong> has been approved by the admin.</p>
                <p>It is now visible to job seekers on the portal.</p>
                <br>
                <p>Best regards,<br>Job Portal Team</p>
            ";
            sendEmail($job['employer_email'], $job['employer_name'], $subject, $body);
        }
    }
    if (isset($_GET['decline_job'])) {
        $mysqli->query("DELETE FROM jobs WHERE id=" . intval($_GET['decline_job']));
    }

    // Handle job editing by admin
    if (isset($_POST['admin_edit_job'])) {
        $job_id = intval($_POST['job_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $salary = trim($_POST['salary']);
        $country = trim($_POST['country']);
        $category = trim($_POST['category']);
        $status = trim($_POST['status']);

        $stmt = $mysqli->prepare("UPDATE jobs SET title=?, description=?, salary=?, country=?, category=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssi", $title, $description, $salary, $country, $category, $status, $job_id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "✅ Job updated successfully!";
        } else {
            $_SESSION['error_msg'] = "❌ Failed to update job.";
        }
        $stmt->close();
        header("Location: admin_panel.php?tab=jobs");
        exit();
    }

    // Courses
    if (isset($_GET['approve_course'])) {
        $course_id = intval($_GET['approve_course']);
        if($mysqli->query("UPDATE courses SET status='approved' WHERE id=$course_id")) {
            $course = $mysqli->query("SELECT c.*, u.name AS center_name, u.email AS center_email FROM courses c JOIN users u ON c.training_center_id=u.id WHERE c.id=$course_id")->fetch_assoc();
            $subject = "Your Course is Approved!";
            $body = "
                <p>Dear <strong>{$course['center_name']}</strong>,</p>
                <p>Your course titled <strong>{$course['title']}</strong> has been approved by the admin.</p>
                <p>It is now visible to job seekers on the portal.</p>
                <br>
                <p>Best regards,<br>Job Portal Team</p>
            ";
            sendEmail($course['center_email'], $course['center_name'], $subject, $body);
        }
    }
    if (isset($_GET['decline_course'])) {
        $mysqli->query("DELETE FROM courses WHERE id=" . intval($_GET['decline_course']));
    }

    // Handle course editing by admin
    if (isset($_POST['admin_edit_course'])) {
        $course_id = intval($_POST['course_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $cost = trim($_POST['cost']);
        $duration = trim($_POST['duration']);
        $structure = trim($_POST['structure']);
        $prerequisites = trim($_POST['prerequisites']);
        $status = trim($_POST['status']);

        $stmt = $mysqli->prepare("UPDATE courses SET title=?, description=?, cost=?, duration=?, structure=?, prerequisites=?, status=? WHERE id=?");
        $stmt->bind_param("sssssssi", $title, $description, $cost, $duration, $structure, $prerequisites, $status, $course_id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "✅ Course updated successfully!";
        } else {
            $_SESSION['error_msg'] = "❌ Failed to update course.";
        }
        $stmt->close();
        header("Location: admin_panel.php?tab=courses");
        exit();
    }

    // Handle course deletion by admin (Transactional)
    if (isset($_GET['admin_delete_course'])) {
        $course_id = intval($_GET['admin_delete_course']);
        
        $mysqli->begin_transaction();
        try {
            // Delete course applications first
            $mysqli->query("DELETE FROM course_applications WHERE course_id = $course_id");
            // Delete the course
            $mysqli->query("DELETE FROM courses WHERE id = $course_id");
            
            $mysqli->commit();
            $_SESSION['success_msg'] = "✅ Course and associated applications deleted.";
        } catch (Exception $e) {
            $mysqli->rollback();
            $_SESSION['error_msg'] = "❌ Failed to delete course.";
        }
        header("Location: admin_panel.php?tab=courses");
        exit();
    }

    // Handle job deletion by admin (Transactional)
    if (isset($_GET['admin_delete_job'])) {
        $job_id = intval($_GET['admin_delete_job']);
        
        $mysqli->begin_transaction();
        try {
            // Delete job applications first
            $mysqli->query("DELETE FROM applications WHERE job_id = $job_id");
            // Delete the job
            $mysqli->query("DELETE FROM jobs WHERE id = $job_id");
            
            $mysqli->commit();
            $_SESSION['success_msg'] = "✅ Job and associated applications deleted.";
        } catch (Exception $e) {
            $mysqli->rollback();
            $_SESSION['error_msg'] = "❌ Failed to delete job.";
        }
        header("Location: admin_panel.php?tab=jobs");
        exit();
    }

    // Handle logo/image change by admin
    if (isset($_POST['admin_change_logo'])) {
        $user_id = intval($_POST['user_id']);
        $file = $_FILES['logo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

        if (in_array($file['type'], $allowed_types) && $file['error'] === 0) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'uploads/profile_' . $user_id . '_' . time() . '.' . $ext;
            
            if (move_uploaded_file($file['tmp_name'], $new_name)) {
                $stmt = $mysqli->prepare("UPDATE users SET image = ? WHERE id = ?");
                $stmt->bind_param("si", $new_name, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = "✅ Logo updated successfully!";
                } else {
                    $_SESSION['error_msg'] = "❌ Database update failed.";
                }
                $stmt->close();
            } else {
                $_SESSION['error_msg'] = "❌ File upload failed.";
            }
        } else {
            $_SESSION['error_msg'] = "❌ Invalid file type or error.";
        }
        header("Location: admin_panel.php?tab=users");
        exit();
    }

    // ------------------------ FETCH DATA ------------------------
    // Users
    $users_pending = $mysqli->query("SELECT * FROM users WHERE status='pending'");
    $users_approved = $mysqli->query("SELECT * FROM users WHERE status='active'");

    // Jobs
    $jobs_pending = $mysqli->query("SELECT j.*, u.name AS employer_name, u.company FROM jobs j JOIN users u ON j.employer_id=u.id WHERE j.status='pending'");
    $jobs_approved = $mysqli->query("SELECT j.*, u.name AS employer_name, u.company FROM jobs j JOIN users u ON j.employer_id=u.id WHERE j.status='approved'");

    // Courses
    $courses_pending = $mysqli->query("SELECT c.*, u.name AS center_name FROM courses c JOIN users u ON c.training_center_id=u.id WHERE c.status='pending'");

    // Job Applications
    $apps = $mysqli->query("
    SELECT a.*, u.name AS jobseeker_name, j.title AS job_title, u.company AS jobseeker_company
    FROM applications a 
    JOIN users u ON a.user_id=u.id
    JOIN jobs j ON a.job_id=j.id
    ORDER BY a.id DESC
    ");

    // Job Delete Requests
    $delete_reqs = $mysqli->query("
    SELECT j.*, u.name AS employer_name, u.company 
    FROM jobs j 
    JOIN users u ON j.employer_id=u.id 
    WHERE j.delete_requested=1
    ");

    // Stats for dashboard
    $stats = [
        'pending_users' => $users_pending->num_rows,
        'approved_users' => $users_approved->num_rows,
        'pending_jobs' => $jobs_pending->num_rows,
        'approved_jobs' => $jobs_approved->num_rows,
        'pending_courses' => $courses_pending->num_rows,
        'total_applications' => $apps->num_rows,
        'delete_requests' => $delete_reqs->num_rows,
    ];

    if(isset($_POST['update_role'])){
        $user_id = (int)$_POST['user_id'];
        $new_role = $_POST['new_role'];

        $allowed_roles = ['jobseeker', 'employer', 'training_center', 'admin'];
        if(in_array($new_role, $allowed_roles)){
            $stmt = $mysqli->prepare("UPDATE users SET role=? WHERE id=?");
            $stmt->bind_param("si", $new_role, $user_id);
            $stmt->execute();
            $stmt->close();

            echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>✅ Role updated successfully!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
        } else {
            echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>❌ Invalid role selected.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
        }
    }

    // Handle user editing
    if (isset($_POST['admin_edit_user'])) {
        $user_id = intval($_POST['user_id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $company = trim($_POST['company']);
        $designation = trim($_POST['designation']);
        $country = trim($_POST['country']);
        $role = trim($_POST['role']);
        $status = trim($_POST['status']);
        $application_for = trim($_POST['application_for'] ?? '');
        $past_experience = trim($_POST['past_experience'] ?? '');
        $applicant_type = trim($_POST['applicant_type'] ?? '');

        $stmt = $mysqli->prepare("UPDATE users SET name=?, email=?, company=?, designation=?, country=?, role=?, status=?, application_for=?, past_experience=?, applicant_type=? WHERE id=?");
        $stmt->bind_param("ssssssssssi", $name, $email, $company, $designation, $country, $role, $status, $application_for, $past_experience, $applicant_type, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "✅ User details updated successfully!";
        } else {
            $_SESSION['error_msg'] = "❌ Failed to update user details.";
        }
        $stmt->close();
        header("Location: admin_panel.php?tab=users");
        exit();
    }

    // At the top of your PHP file, handle deletion
    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];

        $mysqli->begin_transaction();

        try {
            // Delete applications for jobs posted by this employer (BEFORE deleting jobs)
            $stmt = $mysqli->prepare("
                DELETE a FROM applications a
                INNER JOIN jobs j ON a.job_id = j.id
                WHERE j.employer_id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Delete job applications by this user
            $stmt = $mysqli->prepare("DELETE FROM applications WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Now delete the jobs
            $stmt = $mysqli->prepare("DELETE FROM jobs WHERE employer_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Delete applications for courses by this training center (BEFORE deleting courses)
            $stmt = $mysqli->prepare("
                DELETE ca FROM course_applications ca
                INNER JOIN courses c ON ca.course_id = c.id
                WHERE c.training_center_id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Delete course applications by this user
            $stmt = $mysqli->prepare("DELETE FROM course_applications WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Now delete the courses
            $stmt = $mysqli->prepare("DELETE FROM courses WHERE training_center_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            // Finally delete the user
            $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();

            $mysqli->commit();

            // Set session message and reload
            $_SESSION['delete_success'] = true;
            
            // Redirect to same page to see changes
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();

        } catch (Exception $e) {
            $mysqli->rollback();
            $_SESSION['delete_error'] = $e->getMessage();
            
            // Redirect even on error
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Display alerts if they exist
    // Display alerts if they exist
    if (isset($_SESSION['delete_success'])) {
        echo "<div class='alert alert-success alert-dismissible fade show delete-alert' role='alert'>
                ✅ User and all associated data deleted successfully!
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
        unset($_SESSION['delete_success']);
    }

    if (isset($_SESSION['delete_error'])) {
        echo "<div class='alert alert-danger alert-dismissible fade show delete-alert' role='alert'>
                ❌ Error deleting user: " . htmlspecialchars($_SESSION['delete_error']) . "
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
        unset($_SESSION['delete_error']);
    }

    // Set active tab from URL parameter
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Admin Dashboard</title>
        <link rel="shortcut icon" href="img/Logo.png" type="image/x-icon">
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
            --header-bg: #ffffff;
            --content-bg: #f8fafc;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--content-bg);
            overflow-x: hidden;
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
        
        .sidebar-menu {
            padding: 1rem 0;
            overflow-y: auto;
            height: calc(100vh - 120px);
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
            background: var(--header-bg);
            padding: 1rem 2rem;
            box-shadow: var(--card-shadow);
            position: sticky;
            top: 0;
            z-index: 1040;
        }
        .delete-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        width: 260px;         /* small size */
        font-size: 14px;      /* smaller text */
        z-index: 9999;        /* stays above everything */
        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #3b82f6;
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
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: #64748b;
            margin-bottom: 0;
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
            border-left: 4px solid var(--sidebar-active);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.pending { border-left-color: #f59e0b; }
        .stat-card.approved { border-left-color: #10b981; }
        .stat-card.danger { border-left-color: #ef4444; }
        .stat-card.info { border-left-color: #8b5cf6; }
        
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
        
        .stat-icon.pending { background: #fef3c7; color: #f59e0b; }
        .stat-icon.approved { background: #d1fae5; color: #10b981; }
        .stat-icon.danger { background: #fee2e2; color: #ef4444; }
        .stat-icon.info { background: #ede9fe; color: #8b5cf6; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.25rem;
            color: #1e293b;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        /* Tables */
        .card {
            border: none;
            box-shadow: var(--card-shadow);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: #1e293b;
        }
        
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
        
        .badge-modern {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.75rem;
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
        
        /* Modal */
        .modal.modern-modal .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
        
        /* Empty States */
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
            margin-bottom: 0;
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
                    <h3><i class="fas fa-shield-alt me-2"></i>Admin Panel</h3>
                    <small class="text-light opacity-75">Job Portal Management</small>
                </div>
                <button class="sidebar-close" onclick="toggleSidebar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="sidebar-menu">
                <div class="sidebar-item">
                    <a href="?tab=users" class="sidebar-link <?= $active_tab == 'users' ? 'active' : '' ?>" onclick="closeSidebarOnMobile()">
                        <i class="fas fa-users"></i>
                        <span>Users Management</span>
                        <?php if($stats['pending_users'] > 0): ?>
                        <span class="badge bg-warning rounded-pill"><?= $stats['pending_users'] ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <div class="sidebar-item">
                    <a href="?tab=jobs" class="sidebar-link <?= $active_tab == 'jobs' ? 'active' : '' ?>" onclick="closeSidebarOnMobile()">
                        <i class="fas fa-briefcase"></i>
                        <span>Jobs Management</span>
                        <?php if($stats['pending_jobs'] > 0): ?>
                        <span class="badge bg-warning rounded-pill"><?= $stats['pending_jobs'] ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <div class="sidebar-item">
                    <a href="?tab=courses" class="sidebar-link <?= $active_tab == 'courses' ? 'active' : '' ?>" onclick="closeSidebarOnMobile()">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Courses Management</span>
                        <?php if($stats['pending_courses'] > 0): ?>
                        <span class="badge bg-warning rounded-pill"><?= $stats['pending_courses'] ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <div class="sidebar-item">
                    <a href="?tab=applications" class="sidebar-link <?= $active_tab == 'applications' ? 'active' : '' ?>" onclick="closeSidebarOnMobile()">
                        <i class="fas fa-file-alt"></i>
                        <span>Applications</span>
                        <?php if($stats['total_applications'] > 0): ?>
                        <span class="badge bg-info rounded-pill"><?= $stats['total_applications'] ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <div class="sidebar-item">
                    <a href="?tab=delete-requests" class="sidebar-link <?= $active_tab == 'delete-requests' ? 'active' : '' ?>" onclick="closeSidebarOnMobile()">
                        <i class="fas fa-trash-alt"></i>
                        <span>Delete Requests</span>
                        <?php if($stats['delete_requests'] > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $stats['delete_requests'] ?></span>
                        <?php endif; ?>
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
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button class="sidebar-toggle me-3">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div>
                            <h4 class="mb-0 fw-bold">
                                <?php 
                                    $page_titles = [
                                        'users' => 'Users Management',
                                        'jobs' => 'Jobs Management',
                                        'courses' => 'Courses Management',
                                        'applications' => 'Applications',
                                        'delete-requests' => 'Delete Requests'
                                    ];
                                    echo $page_titles[$active_tab] ?? 'Admin Dashboard';
                                ?>
                            </h4>
                            <small class="text-muted">Manage portal content and users</small>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill px-3 py-2">
                            <i class="fas fa-user-shield me-1"></i> <span class="d-none d-md-inline">Administrator</span><span class="d-md-none">Admin</span>
                        </span>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#trainingCenterLimitModal">
                            <i class="fas fa-graduation-cap me-1"></i> <span class="d-none d-md-inline">Manage Course Limits</span><span class="d-md-none">Limits</span>
                        </button>
                        <a href="documentation.php" class="btn btn-info btn-sm">
                            <i class="fas fa-book me-1"></i> <span class="d-none d-md-inline">View Documentation</span><span class="d-md-none">Docs</span>
                        </a>
                    </div>
                </div>
            </div>
<!--mODAL FOR TRAINING CENTER LIMIT-->
<div class="modal fade" id="trainingCenterLimitModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-graduation-cap me-2"></i> Training Center Course Limits
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Training Center</th>
              <th>Email</th>
              <th>Max Courses</th>
              <th>Posted</th>
              <th>Remaining</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>

          <?php
          $stmt = $mysqli->prepare("
              SELECT id, company, email, max_courses_allowed, post_count
              FROM users
              WHERE role = 'training_center'
          ");
          $stmt->execute();
          $result = $stmt->get_result();
          $i = 1;

          while ($row = $result->fetch_assoc()):
              $remaining = max(0, $row['max_courses_allowed'] - $row['post_count']);
          ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($row['company']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>

              <td>
                <form method="POST" class="d-flex gap-2">
                  <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                  <input type="number"
                         name="max_courses_allowed"
                         class="form-control form-control-sm"
                         min="0"
                         value="<?= $row['max_courses_allowed'] ?>"
                         required>
              </td>

              <td><?= $row['post_count'] ?></td>

              <td>
                <span class="badge bg-<?= $remaining > 0 ? 'success' : 'danger' ?>">
                  <?= $remaining ?>
                </span>
              </td>

              <td>
                  <button type="submit" name="update_course_limit" class="btn btn-sm btn-primary">
                    Save
                  </button>
                </form>
              </td>
            </tr>
          <?php endwhile; $stmt->close(); ?>

          </tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

            <!-- Content -->
            <div class="content-wrapper">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card pending">
                        <div class="stat-icon pending">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?= $stats['pending_users'] ?></div>
                        <div class="stat-label">Pending Users</div>
                    </div>

                    <div class="stat-card approved">
                        <div class="stat-icon approved">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-value"><?= $stats['approved_users'] ?></div>
                        <div class="stat-label">Approved Users</div>
                    </div>

                    <div class="stat-card pending">
                        <div class="stat-icon pending">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="stat-value"><?= $stats['pending_jobs'] ?></div>
                        <div class="stat-label">Pending Jobs</div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-icon info">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="stat-value"><?= $stats['pending_courses'] ?></div>
                        <div class="stat-label">Pending Courses</div>
                    </div>

                    <div class="stat-card danger">
                        <div class="stat-icon danger">
                            <i class="fas fa-trash-alt"></i>
                        </div>
                        <div class="stat-value"><?= $stats['delete_requests'] ?></div>
                        <div class="stat-label">Delete Requests</div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-icon info">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-value"><?= $stats['total_applications'] ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if(isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success_msg']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_msg']); ?>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error_msg']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_msg']); ?>
                <?php endif; ?>

                <!-- Content based on active tab -->
                <?php if($active_tab == 'users'): ?>
        
        <!-- USERS MANAGEMENT -->
        <div class="row">
            <div class="col-12">
                <!-- Pending Users (All roles together) -->
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-clock text-warning me-2"></i>
                        <h5 class="mb-0">Pending User Approvals</h5>
                        <span class="badge bg-warning rounded-pill ms-2"><?= $stats['pending_users'] ?></span>
                    </div>
                    <div class="card-body">
                        <?php 
                        // Reset the pending users query to fetch all again
                        $users_pending = $mysqli->query("SELECT * FROM users WHERE status='pending' ORDER BY role, id");
                        
                        if ($users_pending->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Company</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php $i=1; while($u=$users_pending->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= $i++ ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-medium"><?= htmlspecialchars($u['name']) ?></div>
                                                    <small class="text-muted">ID: <?= $u['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td>
                                            <?php 
                                            $role_badge_color = [
                                                'jobseeker' => 'primary',
                                                'employer' => 'success',
                                                'training_center' => 'info',
                                                'admin' => 'danger'
                                            ];
                                            $color = $role_badge_color[$u['role']] ?? 'secondary';
                                            ?>
                                            <span class="badge-modern bg-<?= $color ?>"><?= $u['role'] ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($u['company']) ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="?approve_user=<?= $u['id'] ?>" class="btn btn-modern btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Approve
                                                </a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button name="delete_user" class="btn btn-modern btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No pending users</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Approved Users with Tabs -->
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-user-check text-success me-2"></i>
                        <h5 class="mb-0">Approved Users</h5>
                        <span class="badge bg-success rounded-pill ms-2"><?= $stats['approved_users'] ?></span>
                    </div>
                    
                    <div class="card-body p-0">
                        <!-- Tab Navigation -->
                        <nav>
                            <div class="nav nav-tabs border-bottom-0 px-3 pt-3" id="nav-tab" role="tablist">
                                <?php 
                                // Get counts for each role
                                $jobseekers_count = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE status='active' AND role='jobseeker'")->fetch_assoc()['count'];
                                $employers_count = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE status='active' AND role='employer'")->fetch_assoc()['count'];
                                $training_centers_count = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE status='active' AND role='training_center'")->fetch_assoc()['count'];
                                $admins_count = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE status='active' AND role='admin'")->fetch_assoc()['count'];
                                ?>
                                
                                <button class="nav-link active" id="nav-jobseekers-tab" data-bs-toggle="tab" data-bs-target="#nav-jobseekers" type="button" role="tab">
                                    <i class="fas fa-user-graduate me-1"></i> Job Seekers
                                    <span class="badge bg-primary ms-1"><?= $jobseekers_count ?></span>
                                </button>
                                
                                <button class="nav-link" id="nav-employers-tab" data-bs-toggle="tab" data-bs-target="#nav-employers" type="button" role="tab">
                                    <i class="fas fa-briefcase me-1"></i> Employers
                                    <span class="badge bg-success ms-1"><?= $employers_count ?></span>
                                </button>
                                
                                <button class="nav-link" id="nav-training-centers-tab" data-bs-toggle="tab" data-bs-target="#nav-training-centers" type="button" role="tab">
                                    <i class="fas fa-university me-1"></i> Training Centers
                                    <span class="badge bg-info ms-1"><?= $training_centers_count ?></span>
                                </button>
                                
                                <button class="nav-link" id="nav-admins-tab" data-bs-toggle="tab" data-bs-target="#nav-admins" type="button" role="tab">
                                    <i class="fas fa-user-shield me-1"></i> Admins
                                    <span class="badge bg-danger ms-1"><?= $admins_count ?></span>
                                </button>
                            </div>
                        </nav>

                        <!-- Tab Content -->
                        <div class="tab-content p-3" id="nav-tabContent">
                            <!-- Job Seekers Tab -->
                            <div class="tab-pane fade show active" id="nav-jobseekers" role="tabpanel">
                                <?php 
                                $jobseekers = $mysqli->query("SELECT * FROM users WHERE status='active' AND role='jobseeker' ORDER BY name");
                                
                                if ($jobseekers->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-modern">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Company</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php $i=1; while($u=$jobseekers->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?= $i++ ?></strong></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-primary rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-medium"><?= htmlspecialchars($u['name']) ?></div>
                                                            <small class="text-muted">ID: <?= $u['id'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($u['email']) ?></td>
                                                <td><?= htmlspecialchars($u['company']) ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-modern btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                        <button name="delete_user" class="btn btn-modern btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-user-graduate"></i>
                                        <p>No job seekers found</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Employers Tab -->
                            <div class="tab-pane fade" id="nav-employers" role="tabpanel">
                                <?php 
                                $employers = $mysqli->query("SELECT * FROM users WHERE status='active' AND role='employer' ORDER BY name");
                                
                                if ($employers->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-modern">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Company</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php $i=1; while($u=$employers->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?= $i++ ?></strong></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; position: relative;">
                                                            <?php if(!empty($u['image'])): ?>
                                                                <img src="<?= $u['image'] ?>" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                                                            <?php else: ?>
                                                                <div class="bg-success rounded-circle" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-medium"><?= htmlspecialchars($u['name']) ?></div>
                                                            <small class="text-muted">ID: <?= $u['id'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($u['email']) ?></td>
                                                <td><?= htmlspecialchars($u['company']) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-modern btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button type="button" class="btn btn-modern btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#changeLogoModal<?= $u['id'] ?>">
                                                            <i class="fas fa-image"></i> Logo
                                                        </button>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                            <button name="delete_user" class="btn btn-modern btn-danger btn-sm">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>

                                                    <!-- Change Logo Modal -->
                                                    <div class="modal fade modern-modal" id="changeLogoModal<?= $u['id'] ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Change Logo: <?= htmlspecialchars($u['name']) ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form action="" method="POST" enctype="multipart/form-data">
                                                                    <div class="modal-body text-center">
                                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                                        <div class="mb-4">
                                                                            <?php if(!empty($u['image'])): ?>
                                                                                <img src="<?= $u['image'] ?>" class="rounded shadow-sm" style="max-width: 150px; max-height: 150px;">
                                                                            <?php else: ?>
                                                                                <div class="bg-light d-inline-block p-4 rounded text-muted">No Logo</div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="mb-3 text-start">
                                                                            <label class="form-label fw-bold">Select New Logo</label>
                                                                            <input type="file" name="logo" class="form-control" required>
                                                                            <small class="text-muted">JPG, PNG, WEBP only.</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="admin_change_logo" class="btn btn-primary">Update Logo</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-briefcase"></i>
                                        <p>No employers found</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Training Centers Tab -->
                            <div class="tab-pane fade" id="nav-training-centers" role="tabpanel">
                                <?php 
                                $training_centers = $mysqli->query("SELECT * FROM users WHERE status='active' AND role='training_center' ORDER BY name");
                                
                                if ($training_centers->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-modern">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Company</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php $i=1; while($u=$training_centers->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?= $i++ ?></strong></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                    <div class="me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; position: relative;">
                                                        <?php if(!empty($u['image'])): ?>
                                                            <img src="<?= $u['image'] ?>" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-info rounded-circle" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium"><?= htmlspecialchars($u['name']) ?></div>
                                                        <small class="text-muted">ID: <?= $u['id'] ?></small>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($u['email']) ?></td>
                                                <td><?= htmlspecialchars($u['company']) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-modern btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button type="button" class="btn btn-modern btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#changeLogoModalTC<?= $u['id'] ?>">
                                                            <i class="fas fa-image"></i> Logo
                                                        </button>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                            <button name="delete_user" class="btn btn-modern btn-danger btn-sm">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>

                                                    <!-- Change Logo Modal TC -->
                                                    <div class="modal fade modern-modal" id="changeLogoModalTC<?= $u['id'] ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Change Logo: <?= htmlspecialchars($u['name']) ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form action="" method="POST" enctype="multipart/form-data">
                                                                    <div class="modal-body text-center">
                                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                                        <div class="mb-4">
                                                                            <?php if(!empty($u['image'])): ?>
                                                                                <img src="<?= $u['image'] ?>" class="rounded shadow-sm" style="max-width: 150px; max-height: 150px;">
                                                                            <?php else: ?>
                                                                                <div class="bg-light d-inline-block p-4 rounded text-muted">No Logo</div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="mb-3 text-start">
                                                                            <label class="form-label fw-bold">Select New Logo</label>
                                                                            <input type="file" name="logo" class="form-control" required>
                                                                            <small class="text-muted">JPG, PNG, WEBP only.</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="admin_change_logo" class="btn btn-primary">Update Logo</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-university"></i>
                                        <p>No training centers found</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Admins Tab -->
                            <div class="tab-pane fade" id="nav-admins" role="tabpanel">
                                <?php 
                                $admins = $mysqli->query("SELECT * FROM users WHERE status='active' AND role='admin' ORDER BY name");
                                
                                if ($admins->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-modern">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Company</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php $i=1; while($u=$admins->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?= $i++ ?></strong></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-danger rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-medium"><?= htmlspecialchars($u['name']) ?></div>
                                                            <small class="text-muted">ID: <?= $u['id'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($u['email']) ?></td>
                                                <td><?= htmlspecialchars($u['company']) ?></td>
                                                <td>
                                                    <form method="post" class="d-flex gap-2 align-items-center">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                        <select name="new_role" class="form-select form-select-sm" style="min-width: 140px;" required>
                                                            <option value="jobseeker">Jobseeker</option>
                                                            <option value="employer">Employer</option>
                                                            <option value="training_center">Training Center</option>
                                                            <option value="admin" selected>Admin</option>
                                                        </select>
                                                        <button name="update_role" class="btn btn-modern btn-primary btn-sm">
                                                            <i class="fas fa-save"></i>
                                                        </button>
                                                        <button name="delete_user" class="btn btn-modern btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-user-shield"></i>
                                        <p>No admin users found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Edit User Modals -->
            <?php 
            // Re-fetch all users for modals
            $all_users_for_modal = $mysqli->query("SELECT * FROM users WHERE status='active' ORDER BY role, name");
            while($user = $all_users_for_modal->fetch_assoc()): 
            ?>
            <div class="modal fade modern-modal" id="editUserModal<?= $user['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="post" action="admin_panel.php?tab=users">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit User: <?= htmlspecialchars($user['name']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Full Name</label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Email Address</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Company/Organization</label>
                                        <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($user['company'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Designation</label>
                                        <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($user['designation'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Country</label>
                                        <select name="country" class="form-select">
                                            <option value="">Select Country</option>
                                            <?php foreach(['Saudi Arabia','UAE','Kuwait','Bahrain','Qatar','Oman','Nepal','India','Pakistan'] as $c): ?>
                                                <option value="<?= $c ?>" <?= ($user['country']==$c)?'selected':'' ?>><?= $c ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Role</label>
                                        <select name="role" class="form-select" required>
                                            <option value="jobseeker" <?= ($user['role']=='jobseeker')?'selected':'' ?>>Job Seeker</option>
                                            <option value="employer" <?= ($user['role']=='employer')?'selected':'' ?>>Employer</option>
                                            <option value="training_center" <?= ($user['role']=='training_center')?'selected':'' ?>>Training Center</option>
                                            <option value="admin" <?= ($user['role']=='admin')?'selected':'' ?>>Admin</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Account Status</label>
                                    <select name="status" class="form-select" required>
                                        <option value="active" <?= ($user['status']=='active')?'selected':'' ?>>Active</option>
                                        <option value="pending" <?= ($user['status']=='pending')?'selected':'' ?>>Pending</option>
                                    </select>
                                </div>
                                
                                <!-- Jobseeker-specific fields -->
                                <?php if($user['role'] == 'jobseeker'): ?>
                                <hr class="my-3">
                                <h6 class="text-primary mb-3"><i class="fas fa-user-graduate me-2"></i>Job Seeker Details</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Application For</label>
                                    <input type="text" name="application_for" class="form-control" value="<?= htmlspecialchars($user['application_for'] ?? '') ?>" placeholder="e.g., Software Developer, Nurse, Engineer">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Past Experience</label>
                                    <textarea name="past_experience" rows="3" class="form-control" placeholder="Describe your past work experience..."><?= htmlspecialchars($user['past_experience'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Applicant Type</label>
                                    <select name="applicant_type" class="form-select">
                                        <option value="">Select Type</option>
                                        <option value="Fresher" <?= ($user['applicant_type']=='Fresher')?'selected':'' ?>>Fresher</option>
                                        <option value="Experienced" <?= ($user['applicant_type']=='Experienced')?'selected':'' ?>>Experienced</option>
                                    </select>
                                </div>
                                <?php else: ?>
                                    <input type="hidden" name="application_for" value="">
                                    <input type="hidden" name="past_experience" value="">
                                    <input type="hidden" name="applicant_type" value="">
                                <?php endif; ?>
                                
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>User ID:</strong> <?= $user['id'] ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="admin_edit_user" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            
                                </div>
                <?php elseif($active_tab == 'jobs'): ?>
                    <!-- JOBS MANAGEMENT -->
                    <div class="row">
                        <div class="col-12">
                            <!-- Pending Jobs -->
                            <div class="card mb-4">
                                <div class="card-header d-flex align-items-center">
                                    <i class="fas fa-clock text-warning me-2"></i>
                                    <h5 class="mb-0">Pending Job Approvals</h5>
                                    <span class="badge bg-warning rounded-pill ms-2"><?= $stats['pending_jobs'] ?></span>
                                </div>
                                <div class="card-body">
                                    <?php if ($jobs_pending->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-modern">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Job Title</th>
                                                    <th>Description</th>
                                                    <th>Salary</th>
                                                    <th>Company</th>
                                                    <th>Posted By</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php $i=1; while($j=$jobs_pending->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong><?= $i++ ?></strong></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($j['title']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <?= nl2br(htmlspecialchars(substr($j['description'],0,50))) ?>...
                                                        <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#jobDescModal<?= $j['id'] ?>">
                                                            <i class="fas fa-eye ms-2"></i> View
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <span class="badge-modern bg-success">
                                                            <?= htmlspecialchars($j['salary']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($j['company']) ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-user me-2 text-muted"></i>
                                                            <?= htmlspecialchars($j['employer_name']) ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="?approve_job=<?= $j['id'] ?>" class="btn btn-modern btn-success btn-sm">
                                                                <i class="fas fa-check"></i> Approve
                                                            </a>
                                                            <a href="?admin_delete_job=<?= $j['id'] ?>" class="btn btn-modern btn-danger btn-sm" onclick="return confirm('Careful! This will delete the job and all applications. Proceed?')">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Modal -->
                                                <div class="modal fade modern-modal" id="jobDescModal<?= $j['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"><?= htmlspecialchars($j['title']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <h6>Full Description:</h6>
                                                                <p class="lead"><?= nl2br(htmlspecialchars($j['description'])) ?></p>
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
                                            <i class="fas fa-briefcase"></i>
                                            <p>No pending job posts</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Approved Jobs -->
                            <div class="card">
                                <div class="card-header d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <h5 class="mb-0">Approved Jobs</h5>
                                    <span class="badge bg-success rounded-pill ms-2"><?= $stats['approved_jobs'] ?></span>
                                </div>
                                <div class="card-body">
                                    <?php if ($jobs_approved->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-modern">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Job Title</th>
                                                    <th>Description</th>
                                                    <th>Salary</th>
                                                    <th>Company</th>
                                                    <th>Posted By</th>
                                                    <th>Category</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php $i=1; while($j=$jobs_approved->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong><?= $i++ ?></strong></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($j['title']) ?></strong>
                                                    </td>
                                                    <td><?= nl2br(htmlspecialchars(substr($j['description'],0,100))) ?>...</td>
                                                    <td>
                                                        <span class="badge-modern bg-success">
                                                            <?= htmlspecialchars($j['salary']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($j['company']) ?></td>
                                                    <td><?= htmlspecialchars($j['employer_name']) ?></td>
                                                    <td>
                                                        <span class="badge-modern bg-info">
                                                            <?= htmlspecialchars($j['category']) ?>
                                                        </span>
                                                    </td>
                                                     <td>
                                                         <div class="btn-group" role="group">
                                                             <button type="button" class="btn btn-modern btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#adminEditJobModal<?= $j['id'] ?>">
                                                                 <i class="fas fa-edit"></i> Edit
                                                             </button>
                                                             <a href="?admin_delete_job=<?= $j['id'] ?>" class="btn btn-modern btn-danger btn-sm" onclick="return confirm('Careful! This will delete the job and all applications. Proceed?')">
                                                                 <i class="fas fa-trash"></i> Delete
                                                             </a>
                                                         </div>
                                                     </td>
                                                </tr>
                                            <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="fas fa-check-circle"></i>
                                            <p>No approved jobs yet</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Edit Job Modals for Approved Jobs -->
                            <?php 
                            // Re-fetch jobs for modals
                            $jobs_for_modal = $mysqli->query("SELECT j.*, u.name AS employer_name, u.company FROM jobs j JOIN users u ON j.employer_id=u.id WHERE j.status='approved'");
                            while($job = $jobs_for_modal->fetch_assoc()): 
                            ?>
                            <div class="modal fade modern-modal" id="adminEditJobModal<?= $job['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="post" action="admin_panel.php?tab=jobs">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Job: <?= htmlspecialchars($job['title']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Job Title</label>
                                                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($job['title']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Description</label>
                                                    <textarea name="description" rows="5" class="form-control" required><?= htmlspecialchars($job['description']) ?></textarea>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label fw-bold">Country</label>
                                                        <select name="country" class="form-select" required>
                                                            <?php foreach(['Saudi Arabia','UAE','Kuwait','Bahrain','Qatar','Oman'] as $c): ?>
                                                                <option value="<?= $c ?>" <?= ($job['country']==$c)?'selected':'' ?>><?= $c ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label fw-bold">Salary</label>
                                                        <input type="text" name="salary" class="form-control" value="<?= htmlspecialchars($job['salary'] ?? '') ?>" required>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label fw-bold">Category</label>
                                                        <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($job['category']) ?>" required>
                                                    </div>
                                                    
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label fw-bold">Status</label>
                                                        <select name="status" class="form-select" required>
                                                            <option value="approved" <?= ($job['status']=='approved')?'selected':'' ?>>Approved</option>
                                                            <option value="pending" <?= ($job['status']=='pending')?'selected':'' ?>>Pending</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="alert alert-info mb-0">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <strong>Posted by:</strong> <?= htmlspecialchars($job['employer_name']) ?> (<?= htmlspecialchars($job['company']) ?>)
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="admin_edit_job" class="btn btn-success">
                                                    <i class="fas fa-save me-2"></i>Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            
                        </div>
                    </div>

                <?php elseif($active_tab == 'courses'): ?>
                    <!-- COURSES MANAGEMENT -->
                    <div class="row">
                        <div class="col-12">
                            <!-- Pending Courses -->
                            <div class="card mb-4">
                                <div class="card-header d-flex align-items-center">
                                    <i class="fas fa-clock text-warning me-2"></i>
                                    <h5 class="mb-0">Pending Course Approvals</h5>
                                    <span class="badge bg-warning rounded-pill ms-2"><?= $stats['pending_courses'] ?></span>
                                </div>
                                <div class="card-body">
                                    <?php if ($courses_pending->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-modern">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Course Title</th>
                                                    <th>Structure</th>
                                                    <th>Cost</th>
                                                    <th>Training Center</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php $i=1; while($c=$courses_pending->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong><?= $i++ ?></strong></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($c['title']) ?></strong>
                                                    </td>
                                                    <td><?= nl2br(htmlspecialchars(substr($c['structure'],0,80))) ?>...</td>
                                                     <td>
                                                         <span class="badge-modern bg-success p-2">
                                                             <?php
                                                             if (is_numeric($c['cost'])) {
                                                                 echo number_format((float)$c['cost'], 2);
                                                             } else {
                                                                 echo htmlspecialchars($c['cost']);
                                                             }
                                                             ?>
                                                         </span>
                                                     </td>
                                                    <td><?= htmlspecialchars($c['center_name']) ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-modern btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#adminCourseViewModal<?= $c['id'] ?>">
                                                                <i class="fas fa-eye"></i> View
                                                            </button>
                                                            <button type="button" class="btn btn-modern btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#adminEditCourseModal<?= $c['id'] ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                            <a href="?approve_course=<?= $c['id'] ?>" class="btn btn-modern btn-success btn-sm">
                                                                <i class="fas fa-check"></i> Approve
                                                            </a>
                                                            <a href="?admin_delete_course=<?= $c['id'] ?>" class="btn btn-modern btn-danger btn-sm" onclick="return confirm('Careful! This will delete the course and all applications. Proceed?')">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <!-- Admin Edit Course Modal -->
                                                <div class="modal fade modern-modal" id="adminEditCourseModal<?= $c['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-warning">
                                                                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Course: <?= htmlspecialchars($c['title']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form action="" method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                                                    <div class="row">
                                                                        <div class="col-md-12 mb-3">
                                                                            <label class="form-label fw-bold">Course Title</label>
                                                                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($c['title']) ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label fw-bold">Cost</label>
                                                                            <input type="text" name="cost" class="form-control" value="<?= htmlspecialchars($c['cost']) ?>" required>
                                                                        </div>
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label fw-bold">Duration (weeks)</label>
                                                                            <input type="number" name="duration" class="form-control" value="<?= htmlspecialchars($c['duration']) ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">Structure</label>
                                                                        <textarea name="structure" class="form-control" rows="3" required><?= htmlspecialchars($c['structure']) ?></textarea>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">Full Description</label>
                                                                        <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($c['description']) ?></textarea>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">Prerequisites</label>
                                                                        <input type="text" name="prerequisites" class="form-control" value="<?= htmlspecialchars($c['prerequisites']) ?>">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold">Status</label>
                                                                        <select name="status" class="form-select" required>
                                                                            <option value="pending" <?= ($c['status']=='pending')?'selected':'' ?>>Pending</option>
                                                                            <option value="approved" <?= ($c['status']=='approved')?'selected':'' ?>>Approved</option>
                                                                            <option value="rejected" <?= ($c['status']=='rejected')?'selected':'' ?>>Rejected</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="admin_edit_course" class="btn btn-warning">
                                                                        <i class="fas fa-save me-2"></i>Save Changes
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="fas fa-graduation-cap"></i>
                                            <p>No pending courses</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Approved Courses -->
                            <?php 
                                $courses_approved = $mysqli->query("
                                    SELECT c.*, u.name AS center_name 
                                    FROM courses c 
                                    JOIN users u ON c.training_center_id = u.id 
                                    WHERE c.status='approved'
                                    ORDER BY c.id DESC
                                ");
                                $approved_courses_count = $courses_approved ? $courses_approved->num_rows : 0;
                            ?>
                            
                            <div class="card">
                                <div class="card-header d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <h5 class="mb-0">Approved Courses</h5>
                                    <span class="badge bg-success rounded-pill ms-2"><?= $approved_courses_count ?></span>
                                </div>
                                <div class="card-body">
                                    <?php if ($courses_approved && $approved_courses_count > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-modern">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Course Title</th>
                                                    <th>Structure</th>
                                                    <th>Cost</th>
                                                    <th>Training Center</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php $i=1; while($c=$courses_approved->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong><?= $i++ ?></strong></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($c['title']) ?></strong>
                                                    </td>
                                                    <td><?= nl2br(htmlspecialchars(substr($c['structure'],0,100))) ?>...</td>
                                                     <td>
                                                         <span class="badge-modern bg-success">
                                                             <?php
                                                             if (is_numeric($c['cost'])) {
                                                                 echo number_format((float)$c['cost'], 2);
                                                             } else {
                                                                 echo htmlspecialchars($c['cost']);
                                                             }
                                                             ?>
                                                         </span>
                                                     </td>
                                                     <td>
                                                         <div class="d-flex align-items-center">
                                                             <i class="fas fa-university me-2 text-muted"></i>
                                                             <?= htmlspecialchars($c['center_name']) ?>
                                                         </div>
                                                     </td>
                                                     <td>
                                                         <div class="btn-group" role="group">
                                                             <button type="button" class="btn btn-modern btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#adminCourseViewModalApproved<?= $c['id'] ?>">
                                                                 <i class="fas fa-eye"></i> View
                                                             </button>
                                                             <button type="button" class="btn btn-modern btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#adminEditCourseModalApproved<?= $c['id'] ?>">
                                                                 <i class="fas fa-edit"></i> Edit
                                                             </button>
                                                             <a href="?admin_delete_course=<?= $c['id'] ?>" class="btn btn-modern btn-danger btn-sm" onclick="return confirm('Careful! This will delete the course and all applications. Proceed?')">
                                                                 <i class="fas fa-trash"></i> Delete
                                                             </a>
                                                            
                                                         </div>
                                                     </td>
                                                 </tr>

                                                 <!-- Course View Modal -->
                                                 <div class="modal fade modern-modal" id="adminCourseViewModalApproved<?= $c['id'] ?>" tabindex="-1">
                                                     <div class="modal-dialog modal-lg">
                                                         <div class="modal-content">
                                                             <div class="modal-header bg-info text-white">
                                                                 <h5 class="modal-title"><i class="fas fa-graduation-cap me-2"></i>Course Details: <?= htmlspecialchars($c['title']) ?></h5>
                                                                 <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                             </div>
                                                             <div class="modal-body">
                                                                 <div class="row mb-4">
                                                                     <div class="col-md-6">
                                                                         <h6 class="fw-bold">Course Title:</h6>
                                                                         <p class="lead text-primary"><?= htmlspecialchars($c['title']) ?></p>
                                                                     </div>
                                                                     <div class="col-md-6">
                                                                         <h6 class="fw-bold">Training Center:</h6>
                                                                         <p><i class="fas fa-university me-2 text-muted"></i><?= htmlspecialchars($c['center_name']) ?></p>
                                                                     </div>
                                                                 </div>
                                                                 <div class="row mb-3">
                                                                     <div class="col-md-6">
                                                                         <h6 class="fw-bold text-success">Cost:</h6>
                                                                         <p class="fw-bold">
                                                                             <?php
                                                                             if (is_numeric($c['cost'])) {
                                                                                 echo number_format((float)$c['cost'], 2);
                                                                             } else {
                                                                                 echo htmlspecialchars($c['cost']);
                                                                             }
                                                                             ?>
                                                                         </p>
                                                                     </div>
                                                                     <div class="col-md-6">
                                                                         <h6 class="fw-bold text-info">Duration:</h6>
                                                                         <p><?= htmlspecialchars($c['duration']) ?> weeks</p>
                                                                     </div>
                                                                 </div>
                                                                 <hr>
                                                                 <div class="mb-4">
                                                                     <h6 class="fw-bold">Course Structure:</h6>
                                                                     <div class="bg-light p-3 rounded">
                                                                         <?= nl2br(htmlspecialchars($c['structure'])) ?>
                                                                     </div>
                                                                 </div>
                                                                 <div class="mb-4">
                                                                     <h6 class="fw-bold">Full Description:</h6>
                                                                     <p><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                                                                 </div>
                                                                 <?php if (!empty($c['prerequisites'])): ?>
                                                                 <div class="mb-3">
                                                                     <h6 class="fw-bold text-warning">Prerequisites:</h6>
                                                                     <p><?= htmlspecialchars($c['prerequisites']) ?></p>
                                                                 </div>
                                                                 <?php endif; ?>
                                                             </div>
                                                             <div class="modal-footer">
                                                                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                 <span class="badge bg-success py-2 px-3"><i class="fas fa-check-circle me-1"></i> Already Approved</span>
                                                             </div>
                                                         </div>
                                                     </div>
                                                 </div>

                                                 <!-- Admin Edit Course Modal Approved -->
                                                 <div class="modal fade modern-modal" id="adminEditCourseModalApproved<?= $c['id'] ?>" tabindex="-1">
                                                     <div class="modal-dialog modal-lg">
                                                         <div class="modal-content">
                                                             <div class="modal-header bg-warning">
                                                                 <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Course: <?= htmlspecialchars($c['title']) ?></h5>
                                                                 <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                             </div>
                                                             <form action="" method="POST">
                                                                 <div class="modal-body">
                                                                     <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                                                                     <div class="row">
                                                                         <div class="col-md-12 mb-3">
                                                                             <label class="form-label fw-bold">Course Title</label>
                                                                             <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($c['title']) ?>" required>
                                                                         </div>
                                                                     </div>
                                                                     <div class="row">
                                                                         <div class="col-md-6 mb-3">
                                                                             <label class="form-label fw-bold">Cost</label>
                                                                             <input type="text" name="cost" class="form-control" value="<?= htmlspecialchars($c['cost']) ?>" required>
                                                                         </div>
                                                                         <div class="col-md-6 mb-3">
                                                                             <label class="form-label fw-bold">Duration (weeks)</label>
                                                                             <input type="number" name="duration" class="form-control" value="<?= htmlspecialchars($c['duration']) ?>" required>
                                                                         </div>
                                                                     </div>
                                                                     <div class="mb-3">
                                                                         <label class="form-label fw-bold">Structure</label>
                                                                         <textarea name="structure" class="form-control" rows="3" required><?= htmlspecialchars($c['structure']) ?></textarea>
                                                                     </div>
                                                                     <div class="mb-3">
                                                                         <label class="form-label fw-bold">Full Description</label>
                                                                         <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($c['description']) ?></textarea>
                                                                     </div>
                                                                     <div class="mb-3">
                                                                         <label class="form-label fw-bold">Prerequisites</label>
                                                                         <input type="text" name="prerequisites" class="form-control" value="<?= htmlspecialchars($c['prerequisites']) ?>">
                                                                     </div>
                                                                     <div class="mb-3">
                                                                         <label class="form-label fw-bold">Status</label>
                                                                         <select name="status" class="form-select" required>
                                                                             <option value="pending" <?= ($c['status']=='pending')?'selected':'' ?>>Pending</option>
                                                                             <option value="approved" <?= ($c['status']=='approved')?'selected':'' ?>>Approved</option>
                                                                             <option value="rejected" <?= ($c['status']=='rejected')?'selected':'' ?>>Rejected</option>
                                                                         </select>
                                                                     </div>
                                                                 </div>
                                                                 <div class="modal-footer">
                                                                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                     <button type="submit" name="admin_edit_course" class="btn btn-warning">
                                                                         <i class="fas fa-save me-2"></i>Save Changes
                                                                     </button>
                                                                 </div>
                                                             </form>
                                                         </div>
                                                     </div>
                                                 </div>
                                            <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="fas fa-check-circle"></i>
                                            <p>No approved courses yet</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif($active_tab == 'applications'): ?>
                    <!-- APPLICATIONS -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center">
                                    <i class="fas fa-file-alt text-info me-2"></i>
                                    <h5 class="mb-0">Job Applications</h5>
                                    <span class="badge bg-info rounded-pill ms-2"><?= $stats['total_applications'] ?></span>
                                </div>
                                <div class="card-body">
                                    <?php if($apps->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-modern">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Job Title</th>
                                                        <th>Applicant</th>
                                                        <th>Email</th>
                                                        <th>Phone</th>
                                                        <th>Resume</th>
                                                        <th>Applied At</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php $i=1; while($a=$apps->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><strong><?= $i ?></strong></td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($a['job_title']) ?></strong>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if(!empty($a['photo'])): ?>
                                                                    <img src="<?= htmlspecialchars($a['photo']) ?>" alt="Photo" width="40" height="40" class="rounded-circle me-2 object-fit-cover">
                                                                <?php else: ?>
                                                                    <div class="bg-primary rounded-circle me-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                                        <?= strtoupper(substr($a['jobseeker_name'], 0, 1)) ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?= htmlspecialchars($a['jobseeker_name']) ?>
                                                            </div>
                                                        </td>
                                                        <td><?= htmlspecialchars($a['email']) ?></td>
                                                        <td><?= htmlspecialchars($a['phone']) ?></td>
                                                        <td>
                                                            <?php if(!empty($a['resume'])): ?>
                                                                <a href="<?= htmlspecialchars($a['resume']) ?>" target="_blank" class="btn btn-modern btn-outline-primary btn-sm">
                                                                    <i class="fas fa-download"></i> Resume
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-muted">No Resume</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge-modern bg-secondary">
                                                                <?= date('M d, Y', strtotime($a['created_at'])) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-modern btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#appModal<?= $i ?>">
                                                                <i class="fas fa-eye"></i> View
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <!-- Modal -->
                                                    <div class="modal fade modern-modal" id="appModal<?= $i ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Application Details</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-4 text-center mb-4">
                                                                            <?php if(!empty($a['photo'])): ?>
                                                                                <img src="<?= htmlspecialchars($a['photo']) ?>" class="img-fluid rounded-circle object-fit-cover" style="width: 150px; height: 150px;" alt="Photo">
                                                                            <?php else: ?>
                                                                                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                                                                                    <span class="text-white display-4"><?= strtoupper(substr($a['jobseeker_name'], 0, 1)) ?></span>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="col-md-8">
                                                                            <h4><?= htmlspecialchars($a['jobseeker_name']) ?></h4>
                                                                            <p class="text-muted mb-4">Applied for: <strong><?= htmlspecialchars($a['job_title']) ?></strong></p>
                                                                            
                                                                            <div class="row mb-3">
                                                                                <div class="col-md-6">
                                                                                    <p><i class="fas fa-envelope me-2 text-primary"></i> <strong>Email:</strong> <?= htmlspecialchars($a['email']) ?></p>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <p><i class="fas fa-phone me-2 text-primary"></i> <strong>Phone:</strong> <?= htmlspecialchars($a['phone']) ?></p>
                                                                                </div>
                                                                            </div>
                                                                            
                                                                            <div class="row mb-3">
                                                                                <div class="col-md-12">
                                                                                    <p><i class="fas fa-map-marker-alt me-2 text-primary"></i> <strong>Address:</strong> <?= htmlspecialchars($a['address'] ?? 'Not provided') ?></p>
                                                                                </div>
                                                                            </div>
                                                                            
                                                                            <?php if(!empty($a['notes'])): ?>
                                                                                <div class="card bg-light">
                                                                                    <div class="card-body">
                                                                                        <h6><i class="fas fa-sticky-note me-2"></i> Applicant Notes:</h6>
                                                                                        <p class="mb-0"><?= nl2br(htmlspecialchars($a['notes'])) ?></p>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            
                                                                            <?php if(!empty($a['resume'])): ?>
                                                                                <div class="mt-4">
                                                                                    <a href="<?= htmlspecialchars($a['resume']) ?>" target="_blank" class="btn btn-modern btn-primary">
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
                                            <i class="fas fa-file-alt"></i>
                                            <p>No applications yet</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif($active_tab == 'delete-requests'): ?>
                    <!-- DELETE REQUESTS -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center">
                                    <i class="fas fa-trash-alt text-danger me-2"></i>
                                    <h5 class="mb-0">Job Delete Requests</h5>
                                    <span class="badge bg-danger rounded-pill ms-2"><?= $stats['delete_requests'] ?></span>
                                </div>
                                <div class="card-body">
                                    <?php if ($delete_reqs->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-modern">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Job Title</th>
                                                        <th>Company</th>
                                                        <th>Employer</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php $i=1; while($d=$delete_reqs->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><strong><?= $i++ ?></strong></td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($d['title']) ?></strong>
                                                        </td>
                                                        <td><?= htmlspecialchars($d['company']) ?></td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-user me-2 text-muted"></i>
                                                                <?= htmlspecialchars($d['employer_name']) ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge-modern bg-warning">
                                                                <i class="fas fa-clock me-1"></i> Awaiting Deletion
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <a href="?approve_delete_job=<?= $d['id'] ?>" class="btn btn-modern btn-danger btn-sm" onclick="return confirm('Approve deletion for this job?')">
                                                                    <i class="fas fa-check"></i> Approve Delete
                                                                </a>
                                                                <a href="?cancel_delete_job=<?= $d['id'] ?>" class="btn btn-modern btn-secondary btn-sm" onclick="return confirm('Cancel delete request?')">
                                                                    <i class="fas fa-times"></i> Cancel
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="fas fa-trash-alt"></i>
                                            <p>No delete requests pending</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php endif; ?>
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