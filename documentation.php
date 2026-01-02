<?php
require_once 'config.php';
require_login();
if (!is_admin()) {
    header("Location: login.php?error=access_denied");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Documentation — SkilledNepali</title>
  <link rel="icon" type="image/png" href="/img/Logo.png">
  <style>
    :root {
      --primary: #00A098;
      --primary-dark: #00857d;
      --primary-light: #e6f5f4;
      --secondary: #6c757d;
      --success: #28a745;
      --info: #17a2b8;
      --warning: #ffc107;
      --danger: #dc3545;
      --light: #f8f9fa;
      --dark: #343a40;
      --gray-100: #f8f9fa;
      --gray-200: #e9ecef;
      --gray-300: #dee2e6;
      --gray-400: #ced4da;
      --gray-500: #adb5bd;
      --gray-600: #6c757d;
      --gray-700: #495057;
      --gray-800: #343a40;
      --gray-900: #212529;
      --border-radius: 8px;
      --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      --transition: all 0.3s ease;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: #f8fafc;
      color: var(--gray-800);
      line-height: 1.6;
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar Styles */
    .sidebar {
      width: 280px;
      background: white;
      border-right: 1px solid var(--gray-200);
      height: 100vh;
      position: fixed;
      left: 0;
      top: 0;
      overflow-y: auto;
      z-index: 1000;
      transition: var(--transition);
    }

    .sidebar-header {
      padding: 24px 20px;
      border-bottom: 1px solid var(--gray-200);
      background: white;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .sidebar-header h2 {
      color: var(--primary);
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .sidebar-header p {
      color: var(--gray-600);
      font-size: 0.875rem;
    }

    .nav-section {
      padding: 16px 0;
      border-bottom: 1px solid var(--gray-200);
    }

    .nav-section:last-child {
      border-bottom: none;
    }

    .nav-section-title {
      padding: 0 20px 12px 20px;
      color: var(--gray-700);
      font-size: 0.875rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .nav-links {
      list-style: none;
    }

    .nav-links li {
      margin-bottom: 2px;
    }

    .nav-links a {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: var(--gray-700);
      text-decoration: none;
      font-size: 0.95rem;
      transition: var(--transition);
      border-left: 3px solid transparent;
    }

    .nav-links a:hover {
      background: var(--primary-light);
      color: var(--primary-dark);
      border-left-color: var(--primary);
    }

    .nav-links a.active {
      background: var(--primary-light);
      color: var(--primary);
      border-left-color: var(--primary);
      font-weight: 500;
    }

    .nav-links a i {
      margin-right: 12px;
      width: 20px;
      text-align: center;
      font-size: 1.1rem;
    }

    /* Main Content Styles */
    .main-content {
      flex: 1;
      margin-left: 280px;
      padding: 24px;
      max-width: 100%;
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }
      .sidebar.active {
        transform: translateX(0);
      }
      .main-content {
        margin-left: 0;
      }
    }

    .mobile-menu-btn {
      display: none;
      position: fixed;
      top: 20px;
      right: 20px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: var(--border-radius);
      padding: 10px 14px;
      cursor: pointer;
      z-index: 1001;
      font-size: 1.2rem;
    }

    @media (max-width: 768px) {
      .mobile-menu-btn {
        display: block;
      }
    }

    /* Content Styles */
    .section {
      background: white;
      border-radius: var(--border-radius);
      padding: 32px;
      margin-bottom: 24px;
      box-shadow: var(--box-shadow);
      animation: fadeIn 0.5s ease;
      display: none;
    }

    .section.active {
      display: block;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .section h2 {
      color: var(--primary);
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--primary-light);
      font-size: 1.75rem;
    }

    .section h3 {
      color: var(--gray-800);
      margin: 24px 0 16px 0;
      font-size: 1.3rem;
    }

    .section h4 {
      color: var(--gray-700);
      margin: 20px 0 12px 0;
      font-size: 1.1rem;
    }

    .section p {
      margin-bottom: 16px;
      color: var(--gray-700);
    }

    .section ul, .section ol {
      margin-left: 24px;
      margin-bottom: 20px;
    }

    .section li {
      margin-bottom: 8px;
      color: var(--gray-700);
    }

    /* Code Styles */
    code {
      background: var(--gray-100);
      padding: 2px 6px;
      border-radius: 4px;
      font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
      font-size: 0.9em;
      color: var(--danger);
    }

    pre {
      background: var(--gray-900);
      color: var(--gray-100);
      padding: 20px;
      border-radius: var(--border-radius);
      overflow-x: auto;
      margin: 20px 0;
      font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
      font-size: 0.9rem;
      line-height: 1.5;
    }

    pre code {
      background: none;
      padding: 0;
      color: inherit;
    }

    /* Tables */
    .table-container {
      overflow-x: auto;
      margin: 20px 0;
      border-radius: var(--border-radius);
      border: 1px solid var(--gray-200);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 600px;
    }

    th {
      background: var(--primary);
      color: white;
      font-weight: 600;
      text-align: left;
      padding: 16px;
    }

    td {
      padding: 16px;
      border-bottom: 1px solid var(--gray-200);
      color: var(--gray-700);
    }

    tr:hover {
      background: var(--gray-100);
    }

    /* Badges */
    .badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.85em;
      font-weight: 600;
      margin-right: 8px;
      margin-bottom: 8px;
    }

    .badge-primary { background: var(--primary); color: white; }
    .badge-success { background: var(--success); color: white; }
    .badge-info { background: var(--info); color: white; }
    .badge-warning { background: var(--warning); color: var(--gray-900); }

    /* Cards */
    .feature-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin: 24px 0;
    }

    .feature-card {
      background: var(--gray-100);
      padding: 24px;
      border-radius: var(--border-radius);
      border-left: 4px solid var(--primary);
      transition: var(--transition);
    }

    .feature-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--box-shadow);
    }

    .feature-card h4 {
      color: var(--primary);
      margin-bottom: 12px;
      font-size: 1.1rem;
    }

    /* Alerts */
    .alert {
      padding: 16px 20px;
      border-radius: var(--border-radius);
      margin: 20px 0;
      border-left: 4px solid;
    }

    .alert-warning {
      background: #fff3cd;
      border-color: var(--warning);
      color: #856404;
    }

    .alert-info {
      background: #d1ecf1;
      border-color: var(--info);
      color: #0c5460;
    }

    .alert-success {
      background: #d4edda;
      border-color: var(--success);
      color: #155724;
    }

    /* Search */
    .search-box {
      padding: 20px;
      background: white;
      border-radius: var(--border-radius);
      margin-bottom: 24px;
      box-shadow: var(--box-shadow);
    }

    .search-box input {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid var(--gray-300);
      border-radius: var(--border-radius);
      font-size: 1rem;
      transition: var(--transition);
    }

    .search-box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(0, 160, 152, 0.1);
    }

    /* Back to top */
    .back-to-top {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: var(--primary);
      color: white;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      opacity: 0;
      visibility: hidden;
      transition: var(--transition);
      text-decoration: none;
      font-size: 1.2rem;
    }

    .back-to-top.visible {
      opacity: 1;
      visibility: visible;
    }

    .back-to-top:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }

    /* Footer */
    .main-footer {
      margin-top: 40px;
      padding-top: 20px;
      border-top: 1px solid var(--gray-200);
      color: var(--gray-600);
      font-size: 0.9rem;
      text-align: center;
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <!-- Mobile Menu Button -->
  <button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <h2><i class="fas fa-book me-2"></i>SkilledNepali Docs</h2>
      <p>Technical Documentation v1.0</p>
    </div>

    <div class="search-box">
      <input type="text" id="searchInput" placeholder="Search documentation...">
    </div>

    <nav>
      <div class="nav-section">
        <div class="nav-section-title">Getting Started</div>
        <ul class="nav-links">
          <li><a href="#overview" class="nav-link active" data-section="overview"><i class="fas fa-globe"></i> System Overview</a></li>
          <li><a href="#features" class="nav-link" data-section="features"><i class="fas fa-star"></i> Features</a></li>
          <li><a href="#user-roles" class="nav-link" data-section="user-roles"><i class="fas fa-users"></i> User Roles</a></li>
        </ul>
      </div>

      <div class="nav-section">
        <div class="nav-section-title">Technical Details</div>
        <ul class="nav-links">
          <li><a href="#file-structure" class="nav-link" data-section="file-structure"><i class="fas fa-folder"></i> File Structure</a></li>
          <li><a href="#database" class="nav-link" data-section="database"><i class="fas fa-database"></i> Database Schema</a></li>
          <li><a href="#authentication" class="nav-link" data-section="authentication"><i class="fas fa-lock"></i> Authentication</a></li>
        </ul>
      </div>

      <div class="nav-section">
        <div class="nav-section-title">Implementation</div>
        <ul class="nav-links">
          <li><a href="#functionality" class="nav-link" data-section="functionality"><i class="fas fa-cogs"></i> Core Functionality</a></li>
          <li><a href="#installation" class="nav-link" data-section="installation"><i class="fas fa-download"></i> Installation</a></li>
          <li><a href="#configuration" class="nav-link" data-section="configuration"><i class="fas fa-sliders-h"></i> Configuration</a></li>
        </ul>
      </div>

      <div class="nav-section">
        <div class="nav-section-title">References</div>
        <ul class="nav-links">
          <li><a href="#endpoints" class="nav-link" data-section="endpoints"><i class="fas fa-link"></i> Key Endpoints</a></li>
          <li><a href="#security" class="nav-link" data-section="security"><i class="fas fa-shield-alt"></i> Security</a></li>
          <li><a href="#support" class="nav-link" data-section="support"><i class="fas fa-life-ring"></i> Support</a></li>
        </ul>
      </div>
    </nav>

    <div class="sidebar-footer" style="padding: 20px; color: var(--gray-600); font-size: 0.85rem;">
      <p><i class="fas fa-history me-2"></i>Last updated: <span id="lastUpdated"></span></p>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content" id="mainContent">
    <!-- Overview Section -->
    <section id="overview" class="section active">
      <h2><i class="fas fa-globe me-2"></i>System Overview</h2>
      <p>
        <strong>SkilledNepali</strong> is a comprehensive web-based job and training portal designed to connect 
        Nepali professionals and trainees with employment and training opportunities across GCC (Gulf Cooperation Council) countries.
      </p>
      
      <div class="alert alert-info">
        <strong><i class="fas fa-info-circle me-2"></i>Technology Stack:</strong>
        <ul style="margin-top: 8px; margin-left: 24px;">
          <li><strong>Backend:</strong> PHP 7.4+</li>
          <li><strong>Database:</strong> MySQL (via MySQLi)</li>
          <li><strong>Frontend:</strong> HTML5, CSS3, JavaScript, Bootstrap 5</li>
          <li><strong>Libraries:</strong> dompdf (PDF generation), PHPMailer (email), WOW.js, Owl Carousel</li>
          <li><strong>Server:</strong> Apache (XAMPP recommended for development)</li>
        </ul>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section">
      <h2><i class="fas fa-star me-2"></i>Features</h2>
      <div class="feature-grid">
        <div class="feature-card">
          <h4><i class="fas fa-briefcase me-2"></i>Job Management</h4>
          <p>Employers can post, edit, and manage job listings. Jobs require admin approval before going live.</p>
        </div>
        <div class="feature-card">
          <h4><i class="fas fa-graduation-cap me-2"></i>Training Courses</h4>
          <p>Training centers can post courses and manage enrollments. Job seekers can browse and apply for training programs.</p>
        </div>
        <div class="feature-card">
          <h4><i class="fas fa-paper-plane me-2"></i>Application System</h4>
          <p>Job seekers can apply for jobs and training courses. Employers and training centers can review and manage applications.</p>
        </div>
        <div class="feature-card">
          <h4><i class="fas fa-file-pdf me-2"></i>CV Generation</h4>
          <p>Automated CV/Resume generation in PDF format using dompdf library. Users can download their generated CVs.</p>
        </div>
        <div class="feature-card">
          <h4><i class="fas fa-tachometer-alt me-2"></i>User Dashboards</h4>
          <p>Role-specific dashboards for admins, employers, job seekers, and training centers with relevant statistics and actions.</p>
        </div>
        <div class="feature-card">
          <h4><i class="fas fa-upload me-2"></i>File Uploads</h4>
          <p>Support for profile photos, CVs, company logos, and training center images with organized storage structure.</p>
        </div>
        <div class="feature-card">
          <h4><i class="fas fa-key me-2"></i>Authentication</h4>
          <p>Secure login, registration, password reset functionality with email verification support.</p>
        </div>
        <div class="feature-card">
          <h4><i class="fas fa-cogs me-2"></i>Admin Panel</h4>
          <p>Comprehensive admin interface for managing users, approving jobs/courses, and system oversight.</p>
        </div>
      </div>
    </section>

    <!-- User Roles Section -->
    <section id="user-roles" class="section">
      <h2><i class="fas fa-users me-2"></i>User Roles & Permissions</h2>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Role</th>
              <th>Permissions</th>
              <th>Access Level</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><span class="badge badge-primary">Admin</span></td>
              <td>
                <ul style="margin: 0; padding-left: 20px;">
                  <li>Approve/reject job postings</li>
                  <li>Approve/reject training courses</li>
                  <li>Manage all users</li>
                  <li>View system statistics</li>
                  <li>Full system access</li>
                </ul>
              </td>
              <td>Highest</td>
            </tr>
            <tr>
              <td><span class="badge badge-success">Employer</span></td>
              <td>
                <ul style="margin: 0; padding-left: 20px;">
                  <li>Post and manage job listings</li>
                  <li>View job applications</li>
                  <li>Manage company profile</li>
                  <li>Download applicant CVs</li>
                </ul>
              </td>
              <td>High</td>
            </tr>
            <tr>
              <td><span class="badge badge-info">Job Seeker</span></td>
              <td>
                <ul style="margin: 0; padding-left: 20px;">
                  <li>Browse and search jobs</li>
                  <li>Apply for jobs</li>
                  <li>Browse and apply for training courses</li>
                  <li>Generate and download CV</li>
                  <li>Manage profile</li>
                </ul>
              </td>
              <td>Standard</td>
            </tr>
            <tr>
              <td><span class="badge badge-warning">Training Center</span></td>
              <td>
                <ul style="margin: 0; padding-left: 20px;">
                  <li>Post and manage training courses</li>
                  <li>View course applications</li>
                  <li>Manage training center profile</li>
                </ul>
              </td>
              <td>High</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- File Structure Section -->
    <section id="file-structure" class="section">
      <h2><i class="fas fa-folder me-2"></i>File Structure</h2>
      <p>The system follows a well-organized directory structure:</p>
      <pre><code>Skilled Nepali/
├── component/          # Reusable components
│   ├── header.php     # Main site header
│   └── footer.php     # Main site footer
├── css/               # Stylesheets
│   ├── bootstrap.min.css
│   └── style.css
├── img/               # Images and assets
├── js/                # JavaScript files
│   └── main.js
├── lib/               # Third-party libraries
│   ├── animate/       # Animation library
│   ├── owlcarousel/   # Carousel library
│   └── ...
├── uploads/           # User-uploaded files
│   ├── cvs/          # Generated CVs
│   ├── employers/    # Employer logos
│   ├── photos/       # Profile photos
│   └── training_centers/
├── vendor/           # Composer dependencies
│   ├── dompdf/       # PDF generation
│   └── phpmailer/    # Email functionality
├── config.php        # Database & session config
├── index.php         # Homepage
├── login.php         # Login page
├── register.php      # Registration page
└── ...</code></pre>
    </section>

    <!-- Database Section -->
    <section id="database" class="section">
      <h2><i class="fas fa-database me-2"></i>Database Schema</h2>
      <p>The system uses MySQL database named <code>job_portal</code>. Key tables include:</p>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Table</th>
              <th>Purpose</th>
              <th>Key Fields</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>users</code></td>
              <td>Stores all user accounts</td>
              <td>id, name, email, password, role, status</td>
            </tr>
            <tr>
              <td><code>jobs</code></td>
              <td>Job postings</td>
              <td>id, employer_id, title, description, salary, country, status</td>
            </tr>
            <tr>
              <td><code>applications</code></td>
              <td>Job applications</td>
              <td>id, job_id, jobseeker_id, status, cv_path</td>
            </tr>
            <tr>
              <td><code>courses</code></td>
              <td>Training courses</td>
              <td>id, training_center_id, title, description, duration, status</td>
            </tr>
            <tr>
              <td><code>course_applications</code></td>
              <td>Training course applications</td>
              <td>id, course_id, jobseeker_id, status</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="alert alert-warning">
        <strong><i class="fas fa-exclamation-triangle me-2"></i>Note:</strong> 
        Import <code>database.sql</code> to set up the database schema. 
        Ensure MySQL is running and create the database before importing.
      </div>
    </section>

    <!-- Authentication Section -->
    <section id="authentication" class="section">
      <h2><i class="fas fa-lock me-2"></i>Authentication System</h2>
      <h3>Session Management</h3>
      <p>The system uses PHP sessions for user authentication. Key functions in <code>config.php</code>:</p>
      <pre><code>is_logged_in()        // Check if user is logged in
require_login()       // Redirect to login if not authenticated
current_user()        // Get current logged-in user data
is_admin()           // Check if user is admin
is_employer()        // Check if user is employer
is_jobseeker()       // Check if user is job seeker
is_training_center() // Check if user is training center</code></pre>
      
      <h3>Password Reset</h3>
      <p>The system includes password reset functionality with token-based verification:</p>
      <ul>
        <li><code>forgot_password.php</code> - Request password reset</li>
        <li><code>reset_password.php</code> - Reset password with token</li>
        <li>Uses <code>reset_token</code> and <code>reset_expires</code> fields in users table</li>
      </ul>
    </section>

    <!-- Functionality Section -->
    <section id="functionality" class="section">
      <h2><i class="fas fa-cogs me-2"></i>Core Functionality</h2>
      
      <h3>Job Management</h3>
      <ul>
        <li><strong>Posting:</strong> Employers create job posts via <code>post_job.php</code></li>
        <li><strong>Approval:</strong> Jobs require admin approval (status: 'pending' → 'approved')</li>
        <li><strong>Browsing:</strong> Job seekers browse approved jobs on <code>jobs.php</code></li>
        <li><strong>Application:</strong> Job seekers apply via <code>apply.php</code> with CV upload</li>
        <li><strong>Management:</strong> Employers view applications in their dashboard</li>
      </ul>

      <h3>Training Course Management</h3>
      <ul>
        <li><strong>Posting:</strong> Training centers post courses via <code>post_course.php</code></li>
        <li><strong>Approval:</strong> Courses require admin approval</li>
        <li><strong>Browsing:</strong> Available on <code>courses.php</code></li>
        <li><strong>Application:</strong> Job seekers apply via <code>apply_training.php</code></li>
      </ul>

      <h3>CV Generation</h3>
      <ul>
        <li>Uses <strong>dompdf</strong> library for PDF generation</li>
        <li>Accessible via <code>generate_cv.php</code></li>
        <li>Generated CVs saved in <code>uploads/cvs/</code></li>
        <li>Downloadable via <code>download_cv.php</code></li>
      </ul>
    </section>

    <!-- Installation Section -->
    <section id="installation" class="section">
      <h2><i class="fas fa-download me-2"></i>Installation & Setup</h2>
      <ol>
        <li><strong>Prerequisites:</strong>
          <ul>
            <li>XAMPP (or similar) with PHP 7.4+, MySQL, Apache</li>
            <li>Composer (for dependency management)</li>
          </ul>
        </li>
        <li><strong>Database Setup:</strong>
          <ul>
            <li>Create MySQL database: <code>job_portal</code></li>
            <li>Import <code>database.sql</code> to create tables</li>
            <li>Update database credentials in <code>config.php</code></li>
          </ul>
        </li>
        <li><strong>Dependencies:</strong>
          <ul>
            <li>Run <code>composer install</code> to install PHP dependencies</li>
            <li>Dependencies include: dompdf, PHPMailer</li>
          </ul>
        </li>
        <li><strong>Configuration:</strong>
          <ul>
            <li>Update <code>config.php</code> with database credentials</li>
            <li>Configure email settings for password reset</li>
            <li>Ensure <code>uploads/</code> directory is writable</li>
          </ul>
        </li>
        <li><strong>Access:</strong>
          <ul>
            <li>Navigate to <code>http://localhost/Skilled Nepali/</code></li>
            <li>Register as admin, employer, job seeker, or training center</li>
          </ul>
        </li>
      </ol>
    </section>

    <!-- Configuration Section -->
    <section id="configuration" class="section">
      <h2><i class="fas fa-sliders-h me-2"></i>Configuration</h2>
      <h3>Database Configuration</h3>
      <p>Edit <code>config.php</code> to configure database connection:</p>
      <pre><code>$mysqli = new mysqli("localhost", "root", "", "job_portal");</code></pre>
      <p>Update hostname, username, password, and database name as needed.</p>

      <h3>Session Configuration</h3>
      <p>Sessions are automatically started in <code>config.php</code>. Ensure PHP session directory is writable.</p>

      <h3>File Upload Configuration</h3>
      <p>Check PHP <code>upload_max_filesize</code> and <code>post_max_size</code> settings in <code>php.ini</code> for file upload limits.</p>
    </section>

    <!-- Endpoints Section -->
    <section id="endpoints" class="section">
      <h2><i class="fas fa-link me-2"></i>Key Pages & Endpoints</h2>
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Page/File</th>
              <th>Purpose</th>
              <th>Access</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code>index.php</code></td>
              <td>Homepage with job/training overview</td>
              <td>Public</td>
            </tr>
            <tr>
              <td><code>login.php</code></td>
              <td>User login</td>
              <td>Public</td>
            </tr>
            <tr>
              <td><code>register.php</code></td>
              <td>User registration</td>
              <td>Public</td>
            </tr>
            <tr>
              <td><code>jobs.php</code></td>
              <td>Browse all jobs</td>
              <td>Public</td>
            </tr>
            <tr>
              <td><code>courses.php</code></td>
              <td>Browse all training courses</td>
              <td>Public</td>
            </tr>
            <tr>
              <td><code>admin_panel.php</code></td>
              <td>Admin dashboard</td>
              <td>Admin only</td>
            </tr>
            <tr>
              <td><code>employer_dashboard.php</code></td>
              <td>Employer dashboard</td>
              <td>Employer only</td>
            </tr>
            <tr>
              <td><code>post_job.php</code></td>
              <td>Create/edit job posting</td>
              <td>Employer only</td>
            </tr>
            <tr>
              <td><code>generate_cv.php</code></td>
              <td>Generate CV in PDF format</td>
              <td>Job seeker only</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Security Section -->
    <section id="security" class="section">
      <h2><i class="fas fa-shield-alt me-2"></i>Security Considerations</h2>
      <div class="alert alert-warning">
        <strong><i class="fas fa-exclamation-circle me-2"></i>Important Security Notes:</strong>
        <ul style="margin-top: 8px;">
          <li>Always validate and sanitize user inputs</li>
          <li>Use prepared statements for database queries</li>
          <li>Implement CSRF protection for forms</li>
          <li>Hash passwords using <code>password_hash()</code></li>
          <li>Restrict file upload types and sizes</li>
          <li>Implement rate limiting for login attempts</li>
          <li>Use HTTPS in production</li>
          <li>Regularly update dependencies for security patches</li>
        </ul>
      </div>
    </section>

    <!-- Support Section -->
    <section id="support" class="section">
      <h2><i class="fas fa-life-ring me-2"></i>Support & Contact</h2>
      <p>
        For technical support, feature requests, or bug reports, please contact the development team 
        or refer to the <a href="aboutus.php">About Us</a> page for contact information.
      </p>
      <div class="alert alert-success">
        <strong><i class="fas fa-code-branch me-2"></i>Version:</strong> 1.0<br>
        <strong><i class="fas fa-calendar-alt me-2"></i>Last Updated:</strong> <span id="lastUpdatedContent"></span>
      </div>
    </section>

    <footer class="main-footer">
      <p>&copy; <span id="year"></span> SkilledNepali. All rights reserved.</p>
      <p>This documentation is maintained for internal and development purposes.</p>
    </footer>
  </main>

  <!-- Back to Top Button -->
  <a href="#" class="back-to-top" id="backToTop">
    <i class="fas fa-chevron-up"></i>
  </a>

  <script>
    // Set current year
    document.getElementById('year').textContent = new Date().getFullYear();
    const lastUpdated = new Date().toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    });
    document.getElementById('lastUpdated').textContent = lastUpdated;
    document.getElementById('lastUpdatedContent').textContent = lastUpdated;

    // Navigation functionality
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.section');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const searchInput = document.getElementById('searchInput');
    const backToTop = document.getElementById('backToTop');

    // Show first section by default
    function showSection(sectionId) {
      sections.forEach(section => {
        section.classList.remove('active');
      });
      
      navLinks.forEach(link => {
        link.classList.remove('active');
      });
      
      const activeSection = document.getElementById(sectionId);
      const activeLink = document.querySelector(`.nav-link[data-section="${sectionId}"]`);
      
      if (activeSection) {
        activeSection.classList.add('active');
      }
      
      if (activeLink) {
        activeLink.classList.add('active');
      }
      
      // Scroll to top of content
      document.getElementById('mainContent').scrollTop = 0;
      
      // Close mobile menu if open
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('active');
      }
    }

    // Navigation click handler
    navLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const sectionId = this.getAttribute('data-section');
        showSection(sectionId);
      });
    });

    // Mobile menu toggle
    mobileMenuBtn.addEventListener('click', function() {
      sidebar.classList.toggle('active');
      this.innerHTML = sidebar.classList.contains('active') 
        ? '<i class="fas fa-times"></i>' 
        : '<i class="fas fa-bars"></i>';
    });

    // Search functionality
    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      
      sections.forEach(section => {
        const content = section.textContent.toLowerCase();
        const sectionId = section.id;
        const navLink = document.querySelector(`.nav-link[data-section="${sectionId}"]`);
        
        if (content.includes(searchTerm) || sectionId.includes(searchTerm)) {
          section.style.display = 'block';
          if (navLink) navLink.parentElement.style.display = 'block';
        } else {
          section.style.display = 'none';
          if (navLink) navLink.parentElement.style.display = 'none';
        }
      });
    });

    // Back to top button
    window.addEventListener('scroll', function() {
      if (window.pageYOffset > 300) {
        backToTop.classList.add('visible');
      } else {
        backToTop.classList.remove('visible');
      }
    });

    backToTop.addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('mainContent').scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
      if (window.innerWidth <= 768 && 
          !sidebar.contains(e.target) && 
          !mobileMenuBtn.contains(e.target) &&
          sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
        mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
      }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
      // Ctrl+K or Cmd+K to focus search
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        searchInput.focus();
      }
      
      // Escape to clear search
      if (e.key === 'Escape') {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        searchInput.blur();
      }
    });
  </script>
</body>
</html>