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
    :root{--primary:#00A098;--muted:#666;--bg:#f7faf9;--border:#e0e0e0}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,'Helvetica Neue',Arial;margin:0;color:#222;background:var(--bg);line-height:1.6}
    .container{max-width:1000px;margin:36px auto;padding:24px;background:#fff;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,0.04)}
    h1{margin-top:0;color:var(--primary);border-bottom:3px solid var(--primary);padding-bottom:12px}
    h2{color:var(--primary);margin-top:32px;margin-bottom:16px;font-size:1.5rem}
    h3{color:#333;margin-top:24px;margin-bottom:12px;font-size:1.2rem}
    p,li{line-height:1.8;color:var(--muted);margin-bottom:12px}
    ul,ol{margin-left:20px;margin-bottom:20px}
    code{background:#f4f4f4;padding:2px 6px;border-radius:4px;font-family:'Courier New',monospace;font-size:0.9em;color:#d63384}
    pre{background:#f4f4f4;padding:16px;border-radius:8px;overflow-x:auto;border-left:4px solid var(--primary)}
    pre code{background:none;padding:0}
    .section{margin-bottom:40px;padding-bottom:32px;border-bottom:1px solid var(--border)}
    .section:last-child{border-bottom:none}
    .feature-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;margin:20px 0}
    .feature-card{background:#f8fafb;padding:20px;border-radius:8px;border-left:4px solid var(--primary)}
    .feature-card h4{margin-top:0;color:var(--primary)}
    .table-wrapper{overflow-x:auto;margin:20px 0}
    table{width:100%;border-collapse:collapse;margin:20px 0}
    th,td{padding:12px;text-align:left;border-bottom:1px solid var(--border)}
    th{background:var(--primary);color:#fff;font-weight:600}
    tr:hover{background:#f8fafb}
    .badge{display:inline-block;padding:4px 12px;border-radius:12px;font-size:0.85em;font-weight:600;margin:4px}
    .badge-primary{background:var(--primary);color:#fff}
    .badge-success{background:#28a745;color:#fff}
    .badge-info{background:#17a2b8;color:#fff}
    .badge-warning{background:#ffc107;color:#000}
    footer{margin-top:40px;font-size:0.9rem;color:#999;text-align:center;padding-top:20px;border-top:1px solid var(--border)}
    .toc{background:#f8fafb;padding:20px;border-radius:8px;margin-bottom:32px;border-left:4px solid var(--primary)}
    .toc h3{margin-top:0;color:var(--primary)}
    .toc ul{list-style:none;margin-left:0}
    .toc a{color:var(--primary);text-decoration:none}
    .toc a:hover{text-decoration:underline}
    .warning{background:#fff3cd;border-left:4px solid #ffc107;padding:16px;margin:20px 0;border-radius:4px}
    .info{background:#d1ecf1;border-left:4px solid #17a2b8;padding:16px;margin:20px 0;border-radius:4px}
  </style>
</head>
<body>
  <main class="container">
    <h1>SkilledNepali Documentation</h1>
    <p style="font-size:1.1em;color:#555;margin-bottom:32px">
      Complete technical documentation for the SkilledNepali job and training portal system. 
      This guide covers system architecture, features, user roles, and implementation details.
    </p>

    <div class="toc">
      <h3>Table of Contents</h3>
      <ul>
        <li><a href="#overview">1. System Overview</a></li>
        <li><a href="#features">2. Features</a></li>
        <li><a href="#user-roles">3. User Roles & Permissions</a></li>
        <li><a href="#file-structure">4. File Structure</a></li>
        <li><a href="#database">5. Database Schema</a></li>
        <li><a href="#authentication">6. Authentication System</a></li>
        <li><a href="#functionality">7. Core Functionality</a></li>
        <li><a href="#installation">8. Installation & Setup</a></li>
        <li><a href="#configuration">9. Configuration</a></li>
        <li><a href="#api-endpoints">10. Key Pages & Endpoints</a></li>
      </ul>
    </div>

    <section id="overview" class="section">
      <h2>1. System Overview</h2>
      <p>
        <strong>SkilledNepali</strong> is a comprehensive web-based job and training portal designed to connect 
        Nepali professionals and trainees with employment and training opportunities across GCC (Gulf Cooperation Council) countries. 
        The platform facilitates job postings, training course listings, application management, and CV generation.
      </p>
      <div class="info">
        <strong>Technology Stack:</strong>
        <ul style="margin-top:8px">
          <li><strong>Backend:</strong> PHP 7.4+</li>
          <li><strong>Database:</strong> MySQL (via MySQLi)</li>
          <li><strong>Frontend:</strong> HTML5, CSS3, JavaScript, Bootstrap 5</li>
          <li><strong>Libraries:</strong> dompdf (PDF generation), PHPMailer (email), WOW.js, Owl Carousel</li>
          <li><strong>Server:</strong> Apache (XAMPP recommended for development)</li>
        </ul>
      </div>
    </section>

    <section id="features" class="section">
      <h2>2. Features</h2>
      <div class="feature-grid">
        <div class="feature-card">
          <h4>Job Management</h4>
          <p>Employers can post, edit, and manage job listings. Jobs require admin approval before going live.</p>
        </div>
        <div class="feature-card">
          <h4>Training Courses</h4>
          <p>Training centers can post courses and manage enrollments. Job seekers can browse and apply for training programs.</p>
        </div>
        <div class="feature-card">
          <h4>Application System</h4>
          <p>Job seekers can apply for jobs and training courses. Employers and training centers can review and manage applications.</p>
        </div>
        <div class="feature-card">
          <h4>CV Generation</h4>
          <p>Automated CV/Resume generation in PDF format using dompdf library. Users can download their generated CVs.</p>
        </div>
        <div class="feature-card">
          <h4>User Dashboards</h4>
          <p>Role-specific dashboards for admins, employers, job seekers, and training centers with relevant statistics and actions.</p>
        </div>
        <div class="feature-card">
          <h4>File Uploads</h4>
          <p>Support for profile photos, CVs, company logos, and training center images with organized storage structure.</p>
        </div>
        <div class="feature-card">
          <h4>Authentication</h4>
          <p>Secure login, registration, password reset functionality with email verification support.</p>
        </div>
        <div class="feature-card">
          <h4>Admin Panel</h4>
          <p>Comprehensive admin interface for managing users, approving jobs/courses, and system oversight.</p>
        </div>
      </div>
    </section>

    <section id="user-roles" class="section">
      <h2>3. User Roles & Permissions</h2>
      <div class="table-wrapper">
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
                <ul style="margin:0;padding-left:20px">
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
                <ul style="margin:0;padding-left:20px">
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
                <ul style="margin:0;padding-left:20px">
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
                <ul style="margin:0;padding-left:20px">
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

    <section id="file-structure" class="section">
      <h2>4. File Structure</h2>
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
├── jobs.php          # Job listings
├── courses.php       # Training courses
├── admin_panel.php   # Admin dashboard
├── employer_dashboard.php
├── jobseeker_panel.php
├── training_dashboard.php
├── post_job.php      # Job posting form
├── post_course.php   # Course posting form
├── apply.php         # Job application
├── apply_training.php # Training application
├── generate_cv.php   # CV generation
├── database.sql      # Database schema
└── ...</code></pre>
    </section>

    <section id="database" class="section">
      <h2>5. Database Schema</h2>
      <p>The system uses MySQL database named <code>job_portal</code>. Key tables include:</p>
      <div class="table-wrapper">
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
      <div class="warning">
        <strong>Note:</strong> Import <code>database.sql</code> to set up the database schema. 
        Ensure MySQL is running and create the database before importing.
      </div>
    </section>

    <section id="authentication" class="section">
      <h2>6. Authentication System</h2>
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

    <section id="functionality" class="section">
      <h2>7. Core Functionality</h2>
      
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

      <h3>File Upload System</h3>
      <p>Organized upload structure:</p>
      <ul>
        <li><code>uploads/photos/</code> - Profile photos</li>
        <li><code>uploads/cvs/</code> - Generated and uploaded CVs</li>
        <li><code>uploads/employers/</code> - Company logos</li>
        <li><code>uploads/training_centers/</code> - Training center images</li>
      </ul>
    </section>

    <section id="installation" class="section">
      <h2>8. Installation & Setup</h2>
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
            <li>Configure email settings for password reset (if using PHPMailer)</li>
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

    <section id="configuration" class="section">
      <h2>9. Configuration</h2>
      <h3>Database Configuration</h3>
      <p>Edit <code>config.php</code> to configure database connection:</p>
      <pre><code>$mysqli = new mysqli("localhost", "root", "", "job_portal");</code></pre>
      <p>Update hostname, username, password, and database name as needed.</p>

      <h3>Session Configuration</h3>
      <p>Sessions are automatically started in <code>config.php</code>. Ensure PHP session directory is writable.</p>

      <h3>File Upload Configuration</h3>
      <p>Check PHP <code>upload_max_filesize</code> and <code>post_max_size</code> settings in <code>php.ini</code> for file upload limits.</p>
    </section>

    <section id="api-endpoints" class="section">
      <h2>10. Key Pages & Endpoints</h2>
      <div class="table-wrapper">
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
              <td><code>jobseeker_panel.php</code></td>
              <td>Job seeker dashboard</td>
              <td>Job seeker only</td>
            </tr>
            <tr>
              <td><code>training_dashboard.php</code></td>
              <td>Training center dashboard</td>
              <td>Training center only</td>
            </tr>
            <tr>
              <td><code>post_job.php</code></td>
              <td>Create/edit job posting</td>
              <td>Employer only</td>
            </tr>
            <tr>
              <td><code>post_course.php</code></td>
              <td>Create/edit training course</td>
              <td>Training center only</td>
            </tr>
            <tr>
              <td><code>apply.php</code></td>
              <td>Apply for a job</td>
              <td>Job seeker only</td>
            </tr>
            <tr>
              <td><code>generate_cv.php</code></td>
              <td>Generate CV in PDF format</td>
              <td>Job seeker only</td>
            </tr>
            <tr>
              <td><code>logout.php</code></td>
              <td>Logout and destroy session</td>
              <td>Authenticated users</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="section">
      <h2>Security Considerations</h2>
      <div class="warning">
        <strong>Important Security Notes:</strong>
        <ul style="margin-top:8px">
          <li>Always validate and sanitize user inputs</li>
          <li>Use prepared statements for database queries (currently using direct queries in some places)</li>
          <li>Implement CSRF protection for forms</li>
          <li>Hash passwords using <code>password_hash()</code> and verify with <code>password_verify()</code></li>
          <li>Restrict file upload types and sizes</li>
          <li>Implement rate limiting for login attempts</li>
          <li>Use HTTPS in production</li>
          <li>Regularly update dependencies for security patches</li>
        </ul>
      </div>
    </section>

    <section class="section">
      <h2>Support & Contact</h2>
      <p>
        For technical support, feature requests, or bug reports, please contact the development team 
        or refer to the <a href="aboutus.php">About Us</a> page for contact information.
      </p>
      <p>
        <strong>Version:</strong> 1.0<br>
        <strong>Last Updated:</strong> <span id="lastUpdated"></span>
      </p>
    </section>

    <footer>
      &copy; <span id="year"></span> SkilledNepali. All rights reserved.<br>
      This documentation is maintained for internal and development purposes.
    </footer>
  </main>
<script>
  document.getElementById('year').textContent = new Date().getFullYear();
  document.getElementById('lastUpdated').textContent = new Date().toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
</script>
</body>
</html>

