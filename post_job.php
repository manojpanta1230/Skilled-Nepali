<?php 

if(!is_employer()) die("<div class='alert alert-danger'>Not allowed.</div>");

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $salary = trim($_POST['salary']);
    $country = $_POST['country'];
    $category = $_POST['category']; 
    $uid = $_SESSION['user_id'];

    $currency = $_POST['currency'];
    $salary_full = $currency . ' ' . $salary;

    // Handle file upload
    $image_path = null;
    if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK){
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)){
            $image_path = 'uploads/' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
        }
    }

    if(!$error && $title && $description && $category){
        $stmt = $mysqli->prepare("
            INSERT INTO jobs (employer_id, title, description, salary, country, category, image, status)
            VALUES (?,?,?,?,?,?,?, 'pending')
        ");
        $stmt->bind_param("issssss", $uid, $title, $description, $salary_full, $country, $category, $image_path);
        if($stmt->execute()){
            $success = "✅ Job posted successfully (pending admin approval).";
            $job_id = $stmt->insert_id;

            // Use Mail Helper
            require_once 'mail_helper.php';

            // 1. Notify Admin (Existing Logic)
            $admin_email = "pantamanoj08@gmail.com"; 
            $employer_stmt = $mysqli->prepare("SELECT name,email,company FROM users WHERE id=?");
            $employer_stmt->bind_param("i", $uid);
            $employer_stmt->execute();
            $employer = $employer_stmt->get_result()->fetch_assoc();
            $employer_stmt->close();

            $admin_subject = 'New Job Posted - Admin Approval Needed';
            $admin_body = "
                <p>Dear Admin,</p>
                <p>A new job has been posted and is pending your approval:</p>
                <ul>
                    <li><strong>Title:</strong> {$title}</li>
                    <li><strong>Employer:</strong> {$employer['name']} ({$employer['company']})</li>
                    <li><strong>Category:</strong> {$category}</li>
                    <li><strong>Country:</strong> {$country}</li>
                    <li><strong>Salary:</strong> {$salary_full}</li>
                </ul>
                <p>Please log in to the admin panel to review and approve the job posting.</p>
                <p>Best regards,<br>Job Portal System</p>
            ";
            send_mail($admin_email, $admin_subject, $admin_body);

            // 2. Thank You Email to Employer
            // Check if this is their first job
            $check_first_stmt = $mysqli->prepare("SELECT COUNT(*) as job_count FROM jobs WHERE employer_id = ?");
            $check_first_stmt->bind_param("i", $uid);
            $check_first_stmt->execute();
            $job_count_res = $check_first_stmt->get_result()->fetch_assoc();
            $check_first_stmt->close();

            if ($job_count_res['job_count'] <= 1) { // Current job is already in DB
                $employer_subject = "Welcome to Skilled Nepali - Your First Vacancy Posted!";
                $employer_body = "
                    <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; line-height: 1.8; color: #333; max-width: 600px;'>
                        <p>Dear <strong>{$employer['name']}</strong>,</p>
                        <p>Thank you for registering your company on <strong>SkilledNepali.com</strong> and posting your first vacancy.</p>
                        <p>SkilledNepali is a new platform designed only for GCC employers, where Nepalese jobseekers in Nepal or within GCC can register and apply directly.</p>
                        <p> <strong>SkilledNepali</strong> is powered by <strong>SSK HR Services</strong> to make hiring easy for GCC employers.</p>
                        
                        <p><strong>What you get as a registered employer:</strong></p>
                        <ul style='list-style-type: none; padding-left: 0;'>
                            <li>i) Direct access to Nepalese jobseekers (Nepal + GCC) </li>
                            <li>ii) Job visibility and direct applications </li>
                            <li>iii) Support to source suitable candidates for any job category </li>
                            <li>iv) Dedicated Account Manager for smooth coordination </li>
                        </ul>

                        <p><strong>Next step (to speed up hiring):</strong></p>
                        <p>Please share your salary range, joining timeline, and required experience (if not mentioned in the Job Post), and our team will start supporting you immediately.</p>
                        
                        <div style='margin-top: 20px; padding: 15px; border-left: 4px solid #00A098; background-color: #f9f9f9;'>
                            <p style='margin: 0;'>📲 : <strong>+974 50077249</strong></p>
                            <p style='margin: 0;'>🌐 : <a href='https://SkilledNepali.com' style='color: #00A098; text-decoration: none;'>SkilledNepali.com</a></p>
                        </div>

                        <p style='margin-top: 30px;'>
                            Warm regards,<br>
                            <strong>SkilledNepali.com – Empowering Workforce</strong><br>
                            <small>Powered by SSK HR Services – Qatar</small>
                        </p>
                    </div>
                ";
            } else {
                $employer_subject = "Job Posting Received - Skilled Nepali";
                $employer_body = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                        <h2 style='color: #00A098;'>Thank You, {$employer['name']}!</h2>
                        <p>We've received your job posting for <strong>'{$title}'</strong>.</p>
                        <p>Our team will review your post shortly. Once approved, it will be visible to potential candidates.</p>
                        <p>Best regards,<br>Skilled Nepali Team</p>
                    </div>
                ";
            }
            send_mail($employer['email'], $employer_subject, $employer_body);

            // 3. Notify Job Seekers about new vacancy
            // We match based on category for better relevance
            $seeker_stmt = $mysqli->prepare("SELECT name, email FROM users WHERE role='jobseeker' AND status='active'");
            $seeker_stmt->execute();
            $seekers = $seeker_stmt->get_result();
            
            $vacancy_subject = "New Job Opportunity: {$title} in {$country}";
            while ($seeker = $seekers->fetch_assoc()) {
                $vacancy_body = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                        <h2 style='color: #00A098;'>Hello {$seeker['name']},</h2>
                        <p>A new job vacancy that might interest you has just been posted on Skilled Nepali!</p>
                        <hr>
                        <p><strong>Job Title:</strong> {$title}</p>
                        <p><strong>Category:</strong> {$category}</p>
                        <p><strong>Country:</strong> {$country}</p>
                        <p><strong>Salary:</strong> {$salary_full}</p>
                        <hr>
                        <p>Check out the full details and apply now to take the next step in your career.</p>
                        <p style='margin-top: 20px;'>
                            <a href='http://skillednepali.com/job_details.php?id={$job_id}' style='background-color: #00A098; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Job Details</a>
                        </p>
                        <br>
                        <p>Best regards,<br>Skilled Nepali Team</p>
                    </div>
                ";
                send_mail($seeker['email'], $vacancy_subject, $vacancy_body);
            }
            $seeker_stmt->close();

        } else {
            $error = "Database error: ".$mysqli->error;
        }
        $stmt->close();
    } elseif(!$title || !$description || !$category) {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Job | Employer Dashboard</title>
    <link rel="shortcut icon" href="img/Logo.png" type="image/x-icon">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #00a098;
            --primary-light: #4895ef;
            --secondary-color: #3a0ca3;
            --accent-color: #7209b7;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
            --gradient-primary: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --gradient-accent: linear-gradient(135deg, #7209b7 0%, #f72585 100%);
            --card-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 30px 60px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
      
        .post-job-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        
        /* Header Section */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }
        
        .page-header h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            color: #6c757d;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .header-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.3);
        }
        
        /* Form Container */
        .form-container {
            background: white;
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: var(--transition);
        }
        
        .form-container:hover {
            box-shadow: var(--card-hover-shadow);
        }
        
        .form-header {
            background: var(--primary-color);
            padding: 2rem;
            color: white;
            text-align: center;
        }
        
        .form-header h2 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.8rem;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .form-body {
            padding: 2.5rem;
        }
        
        @media (max-width: 768px) {
            .form-body {
                padding: 1.5rem;
            }
        }
        
        /* Form Groups */
        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }
        
        .form-label i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .required {
            color: #f72585;
        }
        
        .form-control, .form-select, .form-textarea {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.85rem 1.25rem;
            font-size: 1rem;
            transition: var(--transition);
            width: 100%;
        }
        
        .form-control:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.15);
            outline: none;
        }
        
        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        /* File Upload */
        .file-upload-container {
            border: 2px dashed #e9ecef;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            background: #f8f9fa;
        }
        
        .file-upload-container:hover {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.02);
        }
        
        .file-upload-container.drag-over {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.05);
        }
        
        .upload-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .file-input {
            display: none;
        }
        
        .file-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            display: none;
        }
        
        .file-preview img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        /* Currency & Salary Row */
        .currency-salary-row {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1rem;
        }
        
        @media (max-width: 576px) {
            .currency-salary-row {
                grid-template-columns: 1fr;
            }
        }
        
        /* Submit Button */
        .submit-container {
            text-align: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #f1f3f5;
        }
        
        .submit-btn {
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(-1px);
        }
        
        .submit-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .submit-btn:hover::after {
            left: 100%;
        }
        
        /* Alerts */
        .alert-container {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }
        
        .custom-alert {
            background: white;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-left: 5px solid var(--success-color);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .custom-alert.error {
            border-left-color: var(--warning-color);
        }
        
        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            flex-shrink: 0;
        }
        
        .custom-alert .alert-icon {
            background: var(--gradient-primary);
        }
        
        .custom-alert.error .alert-icon {
            background: var(--gradient-accent);
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #333;
        }
        
        .alert-message {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .alert-close {
            background: none;
            border: none;
            color: #adb5bd;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
            transition: var(--transition);
        }
        
        .alert-close:hover {
            color: #333;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
            padding: 0 1rem;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50px;
            right: 50px;
            height: 3px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .step-number {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }
        
        .step.active .step-number {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .step-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        /* Character Counter */
        .char-counter {
            text-align: right;
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .char-counter.warning {
            color: #f72585;
        }
        
        /* Form Sections */
        .form-section {
            margin-bottom: 2.5rem;
            padding-bottom: 2.5rem;
            border-bottom: 2px solid #f1f3f5;
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.4rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            font-size: 1.2rem;
        }
        
        /* Back Button */
        .back-btn {
            background: transparent;
            border: 2px solid #e9ecef;
            color: #6c757d;
            padding: 0.7rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            text-decoration: none;
            margin-right: 1rem;
        }
        
        .back-btn:hover {
            background: #f8f9fa;
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1.5rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Navbar -->


    <!-- Alert Container -->
    <div class="alert-container">
        <?php if($error): ?>
            <div class="custom-alert error" id="errorAlert">
                <div class="alert-icon">
                    <i class="fas fa-exclamation"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">Error!</div>
                    <div class="alert-message"><?= $error ?></div>
                </div>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="custom-alert" id="successAlert">
                <div class="alert-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">Success!</div>
                    <div class="alert-message"><?= $success ?></div>
                </div>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <h4>Submitting Job...</h4>
        <p>Please wait while we process your job posting</p>
    </div>

    <!-- Main Content -->
    <div class="post-job-container">
        <!-- Page Header -->
    

      

        <!-- Form Container -->
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-edit"></i> Job Information</h2>
            </div>
            
            <div class="form-body">
                <form method="post" enctype="multipart/form-data" id="jobForm">
                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-info-circle"></i> Basic Information</h3>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-heading"></i>
                                <span>Job Title <span class="required">*</span></span>
                            </label>
                            <input type="text" name="title" class="form-control" 
                                   placeholder="e.g., Senior Web Developer" 
                                   required
                                   onfocus="this.parentElement.classList.add('focused')"
                                   onblur="this.parentElement.classList.remove('focused')">
                            <div class="char-counter"><span id="titleCount">0</span>/100</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-align-left"></i>
                                <span>Job Description <span class="required">*</span></span>
                            </label>
                            <textarea name="description" class="form-control form-textarea" 
                                      placeholder="Describe the job responsibilities, requirements, benefits, and company culture..."
                                      required
                                      rows="6"
                                      onfocus="this.parentElement.classList.add('focused')"
                                      onblur="this.parentElement.classList.remove('focused')"></textarea>
                            <div class="char-counter"><span id="descCount">0</span>/2000</div>
                        </div>
                    </div>
                    
                    <!-- Compensation & Location Section -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-map-marker-alt"></i> Compensation & Location</h3>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Salary Details <span class="required">*</span></span>
                            </label>
                            <div class="currency-salary-row">
                                <select name="currency" class="form-select" required>
                                    <option value="">Currency</option>
                                    <option value="QAR">QAR (Qatari Riyal)</option>
                                    <option value="AED">AED (UAE Dirham)</option>
                                    <option value="KWD">KWD (Kuwaiti Dinar)</option>
                                    <option value="SAR">SAR (Saudi Riyal)</option>
                                    <option value="OMR">OMR (Omani Rial)</option>
                                    <option value="BHD">BHD (Bahraini Dinar)</option>
                                </select>
                                <input type="number" name="salary" class="form-control" 
                                       placeholder="Monthly Salary Amount" 
                                       required
                                       min="0"
                                       onfocus="this.parentElement.classList.add('focused')"
                                       onblur="this.parentElement.classList.remove('focused')">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-globe"></i>
                                        <span>Country <span class="required">*</span></span>
                                    </label>
                                    <select name="country" class="form-select" required>
                                        <option value="">Select Country</option>
                                        <option value="QATAR">Qatar</option>
                                        <option value="DUBAI">Dubai (UAE)</option>
                                        <option value="KUWAIT">Kuwait</option>
                                        <option value="SAUDI_ARABIA">Saudi Arabia</option>
                                        <option value="OMAN">Oman</option>
                                        <option value="BAHRAIN">Bahrain</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-tags"></i>
                                        <span>Category <span class="required">*</span></span>
                                    </label>
                                    <select name="category" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <option value="Service">Service Industry</option>
                                        <option value="Manufacture">Manufacturing</option>
                                        <option value="IT">Information Technology</option>
                                        <option value="Construction">Construction</option>
                                        <option value="Education">Education</option>
                                        <option value="Healthcare">Healthcare</option>
                                        <option value="Hospitality">Hospitality</option>
                                        <option value="Finance">Finance & Banking</option>
                                        <option value="Retail">Retail</option>
                                        <option value="Engineering">Engineering</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Logistics">Logistics & Supply</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Job Image Section -->
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-image"></i> Job Image (Optional)</h3>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-upload"></i>
                                <span>Upload Job Image</span>
                            </label>
                            <div class="file-upload-container" id="fileUploadContainer">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <h5>Drag & Drop Image Here</h5>
                                <p class="text-muted">or click to browse files</p>
                                <p class="small text-muted">Supports JPG, PNG, GIF, WEBP (Max 5MB)</p>
                                <input type="file" name="image" class="file-input" id="fileInput" accept=".jpg,.jpeg,.png,.gif,.webp">
                            </div>
                            <div class="file-preview" id="filePreview">
                                <img id="previewImage" src="" alt="Preview">
                                <div>
                                    <span id="fileName"></span>
                                    <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeImage()">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Section -->
                    <div class="submit-container">
                        <a href="employer_dashboard.php?tab=my_jobs" class="back-btn">
                            <i class="fas fa-arrow-left"></i> Back to Jobs
                        </a>
                        <button type="submit" class="submit-btn" onclick="showLoading()">
                            <i class="fas fa-paper-plane"></i>
                            <span>Submit for Approval</span>
                        </button>
                        <p class="text-muted mt-3">
                            <i class="fas fa-info-circle"></i>
                            Your job will be reviewed by admin before being published
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.custom-alert');
            alerts.forEach(alert => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100%)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
        
        // Character counters
        const titleInput = document.querySelector('input[name="title"]');
        const descInput = document.querySelector('textarea[name="description"]');
        const titleCount = document.getElementById('titleCount');
        const descCount = document.getElementById('descCount');
        
        titleInput.addEventListener('input', function() {
            titleCount.textContent = this.value.length;
            if (this.value.length > 90) {
                titleCount.classList.add('warning');
            } else {
                titleCount.classList.remove('warning');
            }
        });
        
        descInput.addEventListener('input', function() {
            descCount.textContent = this.value.length;
            if (this.value.length > 1900) {
                descCount.classList.add('warning');
            } else {
                descCount.classList.remove('warning');
            }
        });
        
        // File upload handling
        const fileInput = document.getElementById('fileInput');
        const fileUploadContainer = document.getElementById('fileUploadContainer');
        const filePreview = document.getElementById('filePreview');
        const previewImage = document.getElementById('previewImage');
        const fileName = document.getElementById('fileName');
        
        fileUploadContainer.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                if (file.size > 5 * 1024 * 1024) { // 5MB limit
                    alert('File size must be less than 5MB');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    fileName.textContent = file.name;
                    filePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadContainer.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadContainer.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadContainer.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            fileUploadContainer.classList.add('drag-over');
        }
        
        function unhighlight() {
            fileUploadContainer.classList.remove('drag-over');
        }
        
        fileUploadContainer.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change'));
        }
        
        function removeImage() {
            fileInput.value = '';
            filePreview.style.display = 'none';
        }
        
        // Form validation
        const form = document.getElementById('jobForm');
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#f72585';
                    setTimeout(() => {
                        field.style.borderColor = '';
                    }, 2000);
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields marked with *');
            }
        });
        
        // Loading overlay
        function showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');
            
            // Auto-hide after 5 seconds (in case submission hangs)
            setTimeout(() => {
                overlay.classList.remove('active');
            }, 5000);
        }
        
        // Form field focus effects
        const formControls = document.querySelectorAll('.form-control, .form-select, .form-textarea');
        formControls.forEach(control => {
            control.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            control.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
        
        // Initialize character counters
        titleInput.dispatchEvent(new Event('input'));
        descInput.dispatchEvent(new Event('input'));
    </script>
</body>
</html>