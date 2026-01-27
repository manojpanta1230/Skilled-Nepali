<?php
include 'portal_header.php';
require 'vendor/autoload.php'; // PHPMailer

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

   
    // ===============================
    // Handle Registration
    // ===============================
     if (isset($_POST['register'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'];
         $country = trim($_POST['country'] ?? ''); 
        $company = trim($_POST['company'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $imagePath = null;

        // Jobseeker fields
       $application_for = $_POST['application_for'] ?? '';
$other_job_category = trim($_POST['other_job_category'] ?? '');

if ($application_for === 'Others' && !empty($other_job_category)) {
    $application_for = $other_job_category;
}

        $experience_years = intval($_POST['experience_years'] ?? 0);
        $past_experience = trim($_POST['past_experience'] ?? '');
        $applicant_type = trim($_POST['applicant_type'] ?? '');

        // Basic validation
        if (empty($name) || empty($email) || empty($password)) $errors[] = 'All fields are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
        if (!in_array($role, ['jobseeker', 'employer', 'training_center'])) $errors[] = 'Invalid role selection.';

        // Role-specific validation
        if (($role === 'employer' || $role === 'training_center') && empty($company)) $errors[] = ($role === 'employer') ? "Company name is required for employers." : "Training Center name is required.";
        if ($role === 'employer' && empty($designation)) $errors[] = "Designation is required for employers.";

        // Image upload for employer and training center
        if (in_array($role, ['employer', 'training_center']) && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                $errors[] = "Only JPG, PNG, or GIF images are allowed.";
            } else {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $newName = uniqid($role . '_', true) . "." . $ext;
                $uploadDir = 'uploads/' . $role . 's/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $imagePath = $uploadDir . $newName;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                    $errors[] = "Failed to upload image.";
                }
            }
        }

        // Jobseeker validation
        if ($role === 'jobseeker') {
            if (empty($application_for)) $errors[] = "Please select application type.";
            if (empty($applicant_type)) $errors[] = "Please select applicant type.";
            if ($experience_years < 0) $errors[] = "Years of experience must be a valid number.";
             if (empty($application_for)) $errors[] = "Please select application type.";
            if ($_POST['application_for'] === 'Others' && empty($other_job_category)) {
        $errors[] = "Please specify your job category.";
    }
        }

        // Check duplicate email
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors[] = "Email is already registered.";
        $stmt->close();

        // Check duplicate company for employer
        if ($role === 'employer' && empty($errors)) {
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE company=? AND role='employer'");
            $stmt->bind_param("s", $company);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = "Company is already registered.";
            $stmt->close();
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
// Replace your jobseeker INSERT query with this fixed version:

if ($role === 'jobseeker') {
    // Handle CV upload
    $cvPath = null;
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($_FILES['cv']['type'], $allowedTypes)) {
            $errors[] = "Only PDF, DOC, or DOCX files are allowed for CV.";
        } else {
            $ext = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
            $newName = uniqid('cv_', true) . "." . $ext;
            $uploadDir = 'uploads/cvs/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $cvPath = $uploadDir . $newName;

            if (!move_uploaded_file($_FILES['cv']['tmp_name'], $cvPath)) {
                $errors[] = "Failed to upload CV.";
            }
        }
    }

    if (!$errors) {
        $stmt = $mysqli->prepare("
            INSERT INTO users 
            (name, email, password, role, country, status, application_for, experience_years, past_experience, applicant_type, cv) 
            VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssisss",
            $name,
            $email,
            $hash,
            $role,
            $country,
            $application_for,
            $experience_years,
            $past_experience,
            $applicant_type,
            $cvPath
        );

        if ($stmt->execute()) {
            $success = "Registration successful! Redirecting to login page ...";
            
            // Send Welcome Email
            require_once 'mail_helper.php';
            $first_name = explode(' ', trim($name))[0];
            $subject = "Welcome to SkilledNepali  Your GCC Career Journey Starts Here!";
            $body = "
                <div style='font-family: Arial, sans-serif; line-height: 1.8; color: #333; max-width: 600px;'>
                    <p>Dear <strong>{$first_name}</strong>,</p>
                    <p>Thank you for registering on <strong>SkilledNepali – Empowering Workforce</strong>.</p>
                    <p>We’re excited to welcome you to a platform built only for Nepalese job seekers who want to grow their careers in the GCC (Qatar, UAE, Saudi Arabia, Kuwait, Oman, Bahrain).</p>
                    
                    <p><strong> What happens next?</strong></p>
                    <p>Now that your registration is complete, here are your next steps:</p>
                    <ol>
                        <li><strong>Complete your profile (100%)</strong><br>Add your work experience, skills, education, and preferred job role.</li>
                        <li><strong>Upload your updated CV</strong><br>A strong CV increases your chance of getting shortlisted faster.</li>
                        <li><strong>Add your preferred GCC country & job category</strong><br>So employers can find you easily.</li>
                        <li><strong>Stay active & apply to vacancies</strong><br>New jobs are posted regularly—keep checking and applying.</li>
                    </ol>
 
                    <p><strong> Why SkilledNepali?</strong></p>
                    <p>With SkilledNepali, you get:</p>
                    <ul>
                        <li>Direct visibility to GCC employers </li>
                        <li>Verified job opportunities </li>
                        <li>Faster shortlisting support </li>
                        <li>Career-focused platform for Nepalese talent only </li>
                    </ul>
 
                    <p style='background-color: #f0fdfa; padding: 10px; border-left: 4px solid #00A098;'>
                        📌 <strong>Tip:</strong> Candidates with a complete profile get more calls and interview requests.
                    </p>

                    <p>Welcome again, and all the best for your GCC career journey!</p>
                    
                    <p style='margin-top: 30px;'>
                        Warm regards,<br>
                        <strong>SkilledNepali Team</strong><br>
                        🌐 <a href='https://www.skillednepali.com' style='color: #00A098; text-decoration: none;'>www.skillednepali.com</a>
                    </p>
                </div>
            ";
            send_mail($email, $subject, $body);

            $_POST = [];
            // Redirect removed in favor of JS toast redirect
        } else {
            $errors[] = "Database error: " . $mysqli->error;
        }

        $stmt->close();
    }
}

 else {
                // Employer / Training Center
                $stmt = $mysqli->prepare("
                    INSERT INTO users 
                    (name,email,password,role,company,designation,image,status,country) 
                    VALUES (?,?,?,?,?,?,?, 'active', ?)
                ");
                $stmt->bind_param("ssssssss", $name, $email, $hash, $role, $company, $designation, $imagePath, $country);

                if ($stmt->execute()) {
                    $success = "Registration successful! You can now log in.";

                    // Send Employer Welcome Email
                    require_once 'mail_helper.php';
                    $receiver_name = !empty($company) ? $company : $name;
                    $subject = "Welcome to SkilledNepali – Hiring Support for GCC Employers";
                    $body = "
                        <div style='font-family: Arial, sans-serif; line-height: 1.8; color: #333; max-width: 600px;'>
                            <p>Dear <strong>{$receiver_name}</strong>,</p>
                            <p>Thank you for registering on <strong>SkilledNepali.com</strong>.</p>
                            <p>Welcome to <strong>SkilledNepali – Empowering Workforce</strong>, a new platform created to connect GCC employers with Nepalese job seekers across all industries and job categories.</p>
                            
                            <p>Since SkilledNepali is new in the market, our candidate pool is growing every day. However, we want to assure you that your hiring requirements can still be supported quickly through our recruitment network.</p>
                            
                            <p><strong> How we can support your hiring</strong></p>
                            <p>As a registered employer, you can:</p>
                            <ol>
                                <li><strong>Post your job vacancies</strong><br>Share your job details (position, quantity, salary, duty hours, joining timeline) and start receiving applications.</li>
                                <li><strong>Request candidate sourcing support</strong><br>If suitable profiles are not immediately available on the platform, we can still arrange candidates through our sourcing system.</li>
                                <li><strong>Access trained manpower through our partners</strong><br>We work with professional sourcing and training partners in Nepal, including training centers that can provide trained candidates based on your specific requirements.</li>
                            </ol>
 
                            <p><strong> Why employers choose SkilledNepali</strong></p>
                            <ul>
                                <li>Nepal-focused recruitment support for GCC </li>
                                <li>Platform + sourcing network combined </li>
                                <li>Option for trained manpower as per job requirement </li>
                                <li>Faster shortlisting and deployment guidance </li>
                            </ul>

                            <p style='background-color: #f9f9f9; padding: 15px; border-left: 4px solid #00A098;'>
                                📲 To share your current manpower requirements, please send details via <strong>WhatsApp at +974 50077249</strong> and our team will assist you immediately.
                            </p>

                            <p style='margin-top: 30px;'>
                                Warm regards,<br>
                                <strong>SkilledNepali Team</strong><br>
                                <small>Powered by SSK HR Services</small><br>
                                🌐 <a href='https://www.skillednepali.com' style='color: #00A098; text-decoration: none;'>www.skillednepali.com</a><br>
                                📩 Email: <a href='mailto:support@skillednepali.com' style='color: #00A098; text-decoration: none;'>support@skillednepali.com</a><br>
                                📞 WhatsApp: +974 50077249
                            </p>
                        </div>
                    ";
                    send_mail($email, $subject, $body);

                    $_POST = [];
                    // Redirect removed in favor of JS toast redirect
               
                } else {
                    $errors[] = "Database error: " . $mysqli->error;
                }
                $stmt->close();
            }
        }
    }
 
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="img/Logo.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Job Portal</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #00A098;
            --primary-dark: #00857D;
            --secondary-color: #667eea;
            --accent-color: #ff6b6b;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
      
        
        .register-container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 40px;
            position: relative;
            overflow: hidden;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .register-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 28px;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        h3 {
            color: var(--text-color);
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.2);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.2);
        }
        
        .alert ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        
        .alert li {
            margin-bottom: 5px;
        }
        
        .alert li:last-child {
            margin-bottom: 0;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            color: #333;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 160, 152, 0.2);
            background: white;
            outline: none;
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 16px;
            padding-right: 45px;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .file-upload {
            position: relative;
            margin-bottom: 20px;
        }
        
        .file-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-label:hover {
            background: linear-gradient(135deg, var(--primary-dark), #006B65);
            box-shadow: 0 8px 20px rgba(0, 160, 152, 0.3);
            transform: translateY(-2px);
        }
        
        .file-label i {
            font-size: 18px;
        }
        
        .file-input {
            position: absolute;
            width: 0;
            height: 0;
            opacity: 0;
        }
        
        .register-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .register-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), #006B65);
            box-shadow: 0 8px 20px rgba(0, 160, 152, 0.3);
            transform: translateY(-2px);
        }
        
        .register-btn:active {
            transform: translateY(0);
        }
        
        .role-option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #e1e5eb;
            border-radius: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .role-option:hover {
            border-color: var(--primary-color);
            background: white;
            transform: translateY(-2px);
        }
        
        .role-option.selected {
            border-color: var(--primary-color);
            background: rgba(0, 160, 152, 0.1);
            box-shadow: 0 5px 15px rgba(0, 160, 152, 0.1);
        }
        
        .role-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 18px;
        }
        
        .role-info h5 {
            margin: 0;
            color: #333;
            font-weight: 600;
            font-size: 16px;
        }
        
        .role-info p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        
        .field-group {
            margin-bottom: 20px;
        }
        
        .field-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #999;
            font-size: 14px;
        }
        
        .divider:before,
        .divider:after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e1e5eb;
        }
        
        .divider:before {
            margin-right: 15px;
        }
        
        .divider:after {
            margin-left: 15px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .login-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .login-link a i {
            font-size: 14px;
        }
        
       .jobseeker-card {
    background: white;
    border-radius: 20px;
    box-shadow: var(--shadow);
    padding: 25px 30px;
    margin-top: 20px;
    border-top: 5px solid var(--primary-color);
    transition: all 0.3s ease;
}

.jobseeker-card:hover {
    box-shadow: var(--shadow-hover);
}

.jobseeker-card h4 {
    color: var(--text-color);
    font-weight: 700;
}

        
        /* Responsive Design */
        @media (max-width: 768px) {
            .register-card {
                padding: 30px 25px;
            }
            
            h3 {
                font-size: 1.6rem;
            }
            
            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .form-control {
                padding: 14px 15px;
            }
            
            .role-option {
                padding: 12px;
            }
            
            .otp-modal .modal-dialog {
                margin: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .register-card {
                padding: 25px 20px;
            }
            
            h3 {
                font-size: 1.4rem;
            }
            
            .logo-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
            
            .role-option {
                padding: 12px;
            }
            
            .role-icon {
                width: 35px;
                height: 35px;
                font-size: 16px;
                margin-right: 10px;
            }
        }

        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }

        .toast-msg {
            background: #10b981;
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-bottom: 10px;
            min-width: 300px;
        }

        .toast-msg.show {
            transform: translateX(0);
        }

        .toast-msg i {
            font-size: 20px;
        }

        .toast-msg .toast-content {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>Create Account</h3>
                <p class="subtitle">Join our job portal community</p>
            </div>
            
            <div class="toast-container" id="toastContainer"></div>
            
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
                    </ul>
                </div>
            <?php endif; ?>

           

            <form method="post" id="registerForm" enctype="multipart/form-data">
                <input type="hidden" name="role" id="roleSelect" value="<?= htmlspecialchars($_POST['role'] ?? '') ?>" required>
                
                <div class="field-group">
                    <label for="name">Full Name</label>
                    <div class="form-group">
                        <input name="name" id="name" class="form-control" placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="field-group">
                    <label for="email">Email Address</label>
                    <div class="form-group">
                        <input name="email" type="email" id="email" class="form-control" placeholder="Enter your email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="field-group">
                    <label for="password">Password</label>
                    <div class="form-group">
                        <input name="password" type="password" id="password" class="form-control" placeholder="Create a password" required>
                    </div>
                </div>

                <div class="field-group">
                    <label for="country">Country</label>
                    <div class="form-group">
                        <select name="country" id="country" class="form-control" required>
                            <option value="">Select Country</option>
                            <option value="Nepal" <?= (($_POST['country'] ?? '') === 'Nepal') ? 'selected' : '' ?>>Nepal</option>
                            <option value="India" <?= (($_POST['country'] ?? '') === 'India') ? 'selected' : '' ?>>India</option>
                            <option value="Bangladesh" <?= (($_POST['country'] ?? '') === 'Bangladesh') ? 'selected' : '' ?>>Bangladesh</option>
                            <option value="Pakistan" <?= (($_POST['country'] ?? '') === 'Pakistan') ? 'selected' : '' ?>>Pakistan</option>
                            <option value="Sri Lanka" <?= (($_POST['country'] ?? '') === 'Sri Lanka') ? 'selected' : '' ?>>Sri Lanka</option>
                            <option value="Afghanistan" <?= (($_POST['country'] ?? '') === 'Afghanistan') ? 'selected' : '' ?>>Afghanistan</option>
                            <option value="Bhutan" <?= (($_POST['country'] ?? '') === 'Bhutan') ? 'selected' : '' ?>>Bhutan</option>
                            <option value="Maldives" <?= (($_POST['country'] ?? '') === 'Maldives') ? 'selected' : '' ?>>Maldives</option>
                            
                            <!-- GCC Countries -->
                            <optgroup label="GCC Countries">
                                <option value="Saudi Arabia" <?= (($_POST['country'] ?? '') === 'Saudi Arabia') ? 'selected' : '' ?>>Saudi Arabia</option>
                                <option value="UAE" <?= (($_POST['country'] ?? '') === 'UAE') ? 'selected' : '' ?>>United Arab Emirates (UAE)</option>
                                <option value="Qatar" <?= (($_POST['country'] ?? '') === 'Qatar') ? 'selected' : '' ?>>Qatar</option>
                                <option value="Kuwait" <?= (($_POST['country'] ?? '') === 'Kuwait') ? 'selected' : '' ?>>Kuwait</option>
                                <option value="Bahrain" <?= (($_POST['country'] ?? '') === 'Bahrain') ? 'selected' : '' ?>>Bahrain</option>
                                <option value="Oman" <?= (($_POST['country'] ?? '') === 'Oman') ? 'selected' : '' ?>>Oman</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
                
                <div class="field-group">
                    <label>Select Your Role</label>
                    <div class="role-options">
                        <div class="role-option <?= (($_POST['role'] ?? '') === 'jobseeker') ? 'selected' : '' ?>" onclick="selectRole('jobseeker', this)">
                            <div class="role-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="role-info">
                                <h5>Jobseeker</h5>
                                <p>Looking for job opportunities</p>
                            </div>
                        </div>
                        
                        <div class="role-option <?= (($_POST['role'] ?? '') === 'employer') ? 'selected' : '' ?>" onclick="selectRole('employer', this)">
                            <div class="role-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="role-info">
                                <h5>Employer</h5>
                                <p>Post jobs and hire talent</p>
                            </div>
                        </div>
                        
                        <div class="role-option <?= (($_POST['role'] ?? '') === 'training_center') ? 'selected' : '' ?>" onclick="selectRole('training_center', this)">
                            <div class="role-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="role-info">
                                <h5>Training Center</h5>
                                <p>Offer courses and training</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Employer/Training Center Fields -->
                <div id="employerFields" style="display: <?= in_array(($_POST['role'] ?? ''), ['employer', 'training_center']) ? 'block' : 'none' ?>;">
                    <div class="field-group">
                        <label for="company"><?= (($_POST['role'] ?? '') === 'training_center') ? 'Training Center Name' : 'Company Name' ?></label>
                        <div class="form-group">
                            <input name="company" id="companyField" class="form-control" placeholder="Enter <?= (($_POST['role'] ?? '') === 'training_center') ? 'training center' : 'company' ?> name" value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="field-group">
                        <label for="designation">Designation</label>
                        <div class="form-group">
                            <input name="designation" id="designationField" class="form-control" placeholder="Your position in the company" value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>">
                        </div>
                    </div>
                    
                 <div class="field-group">
    <div class="file-upload">
        <input type="file" name="image" id="imageField" accept="image/*" class="file-input">
        <label for="imageField" class="file-label" style="color: white;">
            <i class="fas fa-cloud-upload-alt"></i>
            <?= (($_POST['role'] ?? '') === 'training_center') ? 'Upload Center Logo' : 'Upload Company Logo' ?>
        </label>
    </div>

    <!-- LIVE PREVIEW IMAGE -->
    <div id="previewContainer" style="display:none; margin-top:10px; text-align:center;">
        <img id="previewImage" src="" 
             style="max-width:140px; border-radius:10px; border:2px solid #fff;">
    </div>
</div>

                    </div>
                </div>
                
                <!-- Jobseeker Fields -->
<!-- Jobseeker Fields -->
<!-- Jobseeker Fields -->
<div id="jobseekerFields" style="display: <?= (($_POST['role'] ?? '') === 'jobseeker') ? 'block' : 'none' ?>;">
    <div class="jobseeker-card">
        <h4 style="text-align:center; margin-bottom:20px;">Jobseeker Details</h4>
   <div class="field-group">
    <label for="application_for">Job Category</label>
    <div class="form-group">
      <select name="application_for" id="application_for" class="form-control">
    <option value="">Select Job Category</option>
    <?php
  $jobs = [
    "A/C Mechanic","Assistant Bakers","Assistant Cook","Assistant Tailors","Barbers","Bakers",
    "Beautician","Bell man","Block Makers","Building Worker","Cabling Technician","Carpenter",
    "Cashier","Ceramic Worker","Cleaner","Clerk","Construction","Construction Equipment Operators",
    "Construction Supervisor","Construction Worker","Correspondent","Cook","Digger","Driver Heavy",
    "Driver Heavy GCC","Driver Light GCC","Drivers Light","Electricians","Gardeners","Helper",
    "Labour","Laundry Man","Machine Operator","Mason","Mechanical helper","Messenger",
    "Office Boy","Overseer","Painter","Photographer","Pipe Fitter","Plant Operators",
    "Plaster Worker","Plumber","Reinforce Fitter","Representative","Room Attendant",
    "Salesman","Scaffolder","Scaffolding Supervisor","Security Guard","Shop Assistant",
    "Steel Fixture","Store Keeper","Sweeper","Tailors","Telephone Operator",
    "Tile Fixture","Typist","Waiter","Washing Worker","Washer Man","Watchman","Welder"
];

sort($jobs, SORT_STRING | SORT_FLAG_CASE);


    foreach($jobs as $job){
        $selected = (($_POST['application_for'] ?? '') === $job) ? 'selected' : '';
        echo "<option value=\"$job\" $selected>$job</option>";
    }

    // Add Others option
    $selected = (($_POST['application_for'] ?? '') === 'Others') ? 'selected' : '';
    echo "<option value=\"Others\" $selected>Others</option>";
    ?>
</select>

    </div>
    <!-- Other job category input (hidden by default) -->
<div class="field-group" id="otherJobCategoryGroup" style="display:none;">
    <label for="other_job_category">Please specify Job Category</label>
    <div class="form-group">
        <input type="text" name="other_job_category" id="other_job_category" class="form-control" placeholder="Enter job category">
    </div>
</div>

</div>
    
        <div class="field-group">
            <label for="applicant_type">Applicant Type</label>
            <div class="form-group">
                <select name="applicant_type" id="applicant_type" class="form-control">
                    <option value="">Select Applicant Type</option>
                    <option value="Fresh Graduate / Entry Level" <?= (($_POST['applicant_type'] ?? '') === 'Fresh Graduate / Entry Level') ? 'selected' : '' ?>>Fresh Graduate / Entry Level</option>
                    <option value="Nepal-Based Experienced" <?= (($_POST['applicant_type'] ?? '') === 'Nepal-Based Experienced') ? 'selected' : '' ?>>Nepal-Based Experienced</option>
                    <option value="GCC-Returned" <?= (($_POST['applicant_type'] ?? '') === 'GCC-Returned') ? 'selected' : '' ?>>GCC-Returned</option>
                    <option value="Currently Working in GCC (Transferable)" <?= (($_POST['applicant_type'] ?? '') === 'Currently Working in GCC (Transferable)') ? 'selected' : '' ?>>Currently Working in GCC (Transferable)</option>
                    <option value="Skilled & Certified Professional" <?= (($_POST['applicant_type'] ?? '') === 'Skilled & Certified Professional') ? 'selected' : '' ?>>Skilled & Certified Professional</option>
                    <option value="Semi-Skilled (Blue Collar)" <?= (($_POST['applicant_type'] ?? '') === 'Semi-Skilled (Blue Collar') ? 'selected' : '' ?>>Semi-Skilled (Blue Collar)</option>
                    <option value="White Collar (Admin/Office)" <?= (($_POST['applicant_type'] ?? '') === 'White Collar (Admin/Office') ? 'selected' : '' ?>>White Collar (Admin/Office)</option>
                    <option value="Overseas Ready (Passport/Police Clearance)" <?= (($_POST['applicant_type'] ?? '') === 'Overseas Ready (Passport/Police Clearance') ? 'selected' : '' ?>>Overseas Ready (Passport/Police Clearance)</option>
                    <option value="SkilledNepali Training Completed" <?= (($_POST['applicant_type'] ?? '') === 'SkilledNepali Training Completed') ? 'selected' : '' ?>>SkilledNepali Training Completed</option>
                    <option value="Short-Term / Part-Time" <?= (($_POST['applicant_type'] ?? '') === 'Short-Term / Part-Time') ? 'selected' : '' ?>>Short-Term / Part-Time</option>
                </select>
            </div>
        </div>
        
        <div class="field-group">
            <label for="experience_years">Years of Experience</label>
            <div class="form-group">
                <input name="experience_years" type="number" id="experience_years" min="0" class="form-control" placeholder="Enter years of experience" value="<?= htmlspecialchars($_POST['experience_years'] ?? '') ?>">
            </div>
        </div>
        
        <div class="field-group">
            <label for="past_experience">Past Experience</label>
            <div class="form-group">
                <textarea name="past_experience" id="past_experience" class="form-control" placeholder="Describe your past work experience"><?= htmlspecialchars($_POST['past_experience'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="field-group">
            <label for="cv">Upload CV/Resume (PDF, DOC, DOCX)</label>
            <div class="file-upload">
                <input type="file" name="cv" id="cvField" accept=".pdf,.doc,.docx" class="file-input">
                <label for="cvField" class="file-label" style="color: white;">
                    <i class="fas fa-file-upload"></i>
                    Upload Your CV
                </label>
            </div>
            
            <!-- CV FILE NAME DISPLAY -->
            <div id="cvFileName" style="display:none; margin-top:10px; padding:10px; background:#f0f0f0; border-radius:8px; text-align:center;">
                <i class="fas fa-file-pdf" style="color:#00A098; margin-right:8px;"></i>
                <span id="cvName" style="color:#333; font-weight:500;"></span>
            </div>
        </div>
    </div>
</div>

                
                <button type="submit" class="register-btn" name="register">
                    Create Account
                </button>
                
                <div class="divider">or</div>
                
                <div class="login-link">
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt"></i>
                        Already have an account? Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- OTP Modal -->


    <script>
        function selectRole(role, element) {
            // Update hidden input
            document.getElementById('roleSelect').value = role;
            
            // Update UI for selected role
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            if (element) element.classList.add('selected');
            
            // Toggle fields based on role
            toggleFields();
        }
        document.getElementById('cvField').addEventListener('change', function(event) {
    const file = event.target.files[0];

    if (file) {
        const fileNameDisplay = document.getElementById('cvFileName');
        const fileNameText = document.getElementById('cvName');
        
        fileNameText.textContent = file.name;
        fileNameDisplay.style.display = 'block';
    }
});
        function toggleFields() {
            const role = document.getElementById('roleSelect').value;
            const jobseekerFields = document.getElementById('jobseekerFields');
            const employerFields = document.getElementById('employerFields');
            
            // Hide all fields first
            jobseekerFields.style.display = 'none';
            employerFields.style.display = 'none';
            
            // Show relevant fields
            if (role === 'jobseeker') {
                jobseekerFields.style.display = 'block';
                // Update labels for jobseeker
                document.getElementById('companyField').placeholder = "Your current company (optional)";
            } else if (role === 'employer' || role === 'training_center') {
                employerFields.style.display = 'block';
                // Update labels for employer/training center
                const label = role === 'training_center' ? 'training center' : 'company';
                document.getElementById('companyField').placeholder = `Enter ${label} name`;
                const fileLabel = document.querySelector('.file-label');
                if (fileLabel) {
                    fileLabel.innerHTML = `<i class="fas fa-cloud-upload-alt"></i> Upload ${label === 'training center' ? 'Center' : 'Company'} Logo`;
                }
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // If role is already selected from POST data, make sure UI reflects it
            const selectedRole = document.getElementById('roleSelect').value;
            if (selectedRole) {
                toggleFields();
            }
            
            // Add form validation
            const form = document.getElementById('registerForm');
            form.addEventListener('submit', function(e) {
                const role = document.getElementById('roleSelect').value;
                if (!role) {
                    e.preventDefault();
                    alert('Please select your role');
                    return false;
                }
                return true;
            });
            
        });
        
document.getElementById('imageField').addEventListener('change', function(event) {
    const file = event.target.files[0];

    if (file) {
        const reader = new FileReader();

        reader.onload = function(e) {
            const img = document.getElementById('previewImage');
            const container = document.getElementById('previewContainer');

            img.src = e.target.result;
            container.style.display = "block";
        };

        reader.readAsDataURL(file);
    }
});
document.getElementById('application_for').addEventListener('change', function () {
    const otherGroup = document.getElementById('otherJobCategoryGroup');
    
    if (this.value === 'Others') {
        otherGroup.style.display = 'block';
    } else {
        otherGroup.style.display = 'none';
        document.getElementById('other_job_category').value = '';
    }
});


    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function showToast(message) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast-msg';
            toast.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <div class="toast-content">
                    <div style="font-weight: 600;">Success!</div>
                    <div style="font-size: 14px; opacity: 0.9;">${message}</div>
                </div>
            `;
            container.appendChild(toast);
            
            // Force reflow
            toast.offsetHeight;
            
            // Show toast
            toast.classList.add('show');
            
            // Remove after 5 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 5000);
        }

        <?php if ($success): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showToast("<?= str_replace('✅ ', '', addslashes($success)) ?>");
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 3500);
            });
        <?php endif; ?>
    </script>
</body>
</html>

<?php include 'portal_footer.php'; ?>