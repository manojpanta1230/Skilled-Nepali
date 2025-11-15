<?php 
include 'portal_header.php'; 

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

// Job delete requests
if (isset($_GET['approve_delete_job'])) {
    $job_id = intval($_GET['approve_delete_job']);
    $mysqli->query("DELETE FROM jobs WHERE id=$job_id");
}
if (isset($_GET['cancel_delete_job'])) {
    $job_id = intval($_GET['cancel_delete_job']);
    $mysqli->query("UPDATE jobs SET delete_requested=0 WHERE id=$job_id");
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
if(isset($_POST['update_role'])){
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];

    $allowed_roles = ['jobseeker', 'employer', 'training_center', 'admin'];
    if(in_array($new_role, $allowed_roles)){
        $stmt = $mysqli->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->bind_param("si", $new_role, $user_id);
        $stmt->execute();
        $stmt->close();

        echo "<div class='alert alert-success'>✅ Role updated successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'> Invalid role selected.</div>";
    }
}
?>




<div class="container mt-4">
  <h2 class="mb-4 text-primary">Admin Control Panel</h2>

  <!-- NAV TABS -->
  <ul class="nav nav-tabs mb-4" id="adminTab" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#users">Users</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#jobs">Jobs</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#courses">Courses</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#applications">Applications</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#delete-requests">Delete Requests</button></li>
  </ul>

  <div class="tab-content">

    <!-- USERS TAB -->
    <div class="tab-pane fade show active" id="users">
      <!-- Pending Users -->
<div class="card mb-4">
  <div class="card-header bg-secondary text-white">
    <h5 class="mb-0">Pending User Approvals</h5>
  </div>
  <div class="card-body">
    <?php if ($users_pending->num_rows > 0): ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
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
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge bg-info text-dark"><?= $u['role'] ?></span></td>
            <td><?= htmlspecialchars($u['company']) ?></td>
            <td>
              <a href="?approve_user=<?= $u['id'] ?>" class="btn btn-success btn-sm">Approve</a>
              <a href="?decline_user=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')">Decline</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p class="text-muted">No pending users.</p>
    <?php endif; ?>
  </div>
</div>

      <!-- Approved Users -->
   <?php 
// Handle role update from admin

?>

<div class="card mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Approved Users</h5>
        <small class="text-light">Manage user roles</small>
    </div>

    <div class="card-body">
        <?php if ($users_approved->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Current Role</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Change Role</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while($u=$users_approved->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($u['role']) ?></span></td>
                        <td><?= htmlspecialchars($u['company']) ?></td>
                        <td><span class="badge bg-success">Approved</span></td>
                        <td>
                            <form method="post" class="d-flex">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="new_role" class="form-select form-select-sm me-2" required>
                                    <option value="">Select</option>
                                    <option value="jobseeker" <?= $u['role']=='jobseeker'?'selected':''; ?>>Jobseeker</option>
                                    <option value="employer" <?= $u['role']=='employer'?'selected':''; ?>>Employer</option>
                                    <option value="training_center" <?= $u['role']=='training_center'?'selected':''; ?>>Training Center</option>
                                    <option value="admin" <?= $u['role']=='admin'?'selected':''; ?>>Admin</option>
                                </select>
                                <button name="update_role" class="btn btn-primary btn-sm">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted">No approved users yet.</p>
        <?php endif; ?>
    </div>
</div>

    </div>

    <!-- JOBS TAB -->
    <div class="tab-pane fade" id="jobs">
      <!-- Pending Jobs -->
<div class="card mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="mb-0">Pending Job Approvals</h5>
  </div>
  <div class="card-body">
    <?php if ($jobs_pending->num_rows > 0): ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
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
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($j['title']) ?></td>
            <td>
              <?= nl2br(htmlspecialchars(substr($j['description'],0,50))) ?>...
              <!-- Eye icon to open modal -->
              <a href="#" data-bs-toggle="modal" data-bs-target="#jobDescModal<?= $j['id'] ?>">
                <i class="bi bi-eye-fill"></i>
              </a>

              <!-- Modal -->
              <div class="modal fade" id="jobDescModal<?= $j['id'] ?>" tabindex="-1" aria-labelledby="jobDescModalLabel<?= $j['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="jobDescModalLabel<?= $j['id'] ?>"><?= htmlspecialchars($j['title']) ?> - Full Description</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <p><?= nl2br(htmlspecialchars($j['description'])) ?></p>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                  </div>
                </div>
              </div>
            </td>
            <td><?= htmlspecialchars($j['salary']) ?></td>
            <td><?= htmlspecialchars($j['company']) ?></td>
            <td><?= htmlspecialchars($j['employer_name']) ?></td>
            

            <td>
              <a href="?approve_job=<?= $j['id'] ?>" class="btn btn-success btn-sm">Approve</a>
              <a href="?decline_job=<?= $j['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this job post?')">Decline</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p class="text-muted">No pending job posts.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Approved Jobs -->
<div class="card mb-4">
  <div class="card-header bg-success text-white">
    <h5 class="mb-0">Approved Jobs</h5>
  </div>
  <div class="card-body">
    <?php if ($jobs_approved->num_rows > 0): ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Job Title</th>
            <th>Description</th>
            <th>Salary</th>
            <th>Company</th>
            <th>Posted By</th>
            <th> Category</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php $i=1; while($j=$jobs_approved->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($j['title']) ?></td>
            <td><?= nl2br(htmlspecialchars(substr($j['description'],0,100))) ?>...</td>
            <td><?= htmlspecialchars($j['salary']) ?></td>
            <td><?= htmlspecialchars($j['company']) ?></td>
            <td><?= htmlspecialchars($j['employer_name']) ?></td>
            <td><?= htmlspecialchars($j['category']) ?></td>
            <td><span class="badge bg-success">Approved</span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p class="text-muted">No approved jobs yet.</p>
    <?php endif; ?>
  </div>
</div>

    </div>

    <!-- COURSES TAB -->
 <div class="tab-pane fade" id="courses">
<!-- ✅ Pending Course Approvals -->
<div class="card mb-4">
  <div class="card-header bg-warning text-dark">
    <h5 class="mb-0">Pending Course Approvals</h5>
  </div>
  <div class="card-body">
    <?php if ($courses_pending->num_rows > 0): ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
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
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($c['title']) ?></td>
            <td><?= nl2br(htmlspecialchars(substr($c['structure'],0,80))) ?>...</td>
            <td><?= htmlspecialchars($c['cost']) ?></td>
            <td><?= htmlspecialchars($c['center_name']) ?></td>
            <td>
              <a href="?approve_course=<?= $c['id'] ?>" class="btn btn-success btn-sm">Approve</a>
              <a href="?decline_course=<?= $c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this course?')">Decline</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p class="text-muted">No pending courses.</p>
    <?php endif; ?>
  </div>
</div>

<!-- ✅ Approved Courses -->
<div class="card mb-4">
  <div class="card-header bg-success text-white">
    <h5 class="mb-0">Approved Courses</h5>
  </div>
  <div class="card-body">
    <?php 
      // Fetch approved courses
      $courses_approved = $mysqli->query("
        SELECT c.*, u.name AS center_name 
        FROM courses c 
        JOIN users u ON c.training_center_id = u.id 
        WHERE c.status='approved'
        ORDER BY c.id DESC
      ");
    ?>

    <?php if ($courses_approved && $courses_approved->num_rows > 0): ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Course Title</th>
            <th>Structure</th>
            <th>Cost</th>
            <th>Training Center</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php $i=1; while($c=$courses_approved->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($c['title']) ?></td>
            <td><?= nl2br(htmlspecialchars(substr($c['structure'],0,100))) ?>...</td>
            <td><?= htmlspecialchars($c['cost']) ?></td>
            <td><?= htmlspecialchars($c['center_name']) ?></td>
            <td><span class="badge bg-success">Approved</span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p class="text-muted">No approved courses yet.</p>
    <?php endif; ?>
  </div>
</div>

</div>

    

    <!-- APPLICATIONS TAB -->
<div class="tab-pane fade" id="applications">
 <div class="card mb-4">
  <div class="card-header bg-warning text-dark">
    <h5 class="mb-0">Job Applications</h5>
  </div>
  <div class="card-body">
    <?php if($apps->num_rows > 0): ?>
      <div class="table-responsive"> <!-- Added this wrapper -->
        <table class="table table-bordered">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Job Title</th>
              <th>Applicant</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Address</th>
              <th>Resume / Cover Letter</th>
              <th>Photo</th>
              <th>Applied At</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php $i=1; while($a=$apps->fetch_assoc()): ?>
            <tr>
              <td><?= $i ?></td>
              <td><?= htmlspecialchars($a['job_title']) ?></td>
              <td><?= htmlspecialchars($a['jobseeker_name']) ?></td>
              <td><?= htmlspecialchars($a['email']) ?></td>
              <td><?= htmlspecialchars($a['phone']) ?></td>
              <td><?= htmlspecialchars($a['address'] ?? '') ?></td>
              <td>
                <?php if(!empty($a['resume'])): ?>
                  <a href="<?= htmlspecialchars($a['resume']) ?>" target="_blank">View Resume</a>
                <?php else: ?>
                  <span class="text-muted">No Resume</span>
                <?php endif; ?>
                <?php if(!empty($a['notes'])): ?>
                  <br><small>Notes: <?= nl2br(htmlspecialchars($a['notes'])) ?></small>
                <?php endif; ?>
              </td>
              <td>
                <?php if(!empty($a['photo'])): ?>
                  <img src="<?= htmlspecialchars($a['photo']) ?>" alt="Photo" width="50" class="rounded-circle">
                <?php else: ?>
                  <span class="text-muted">No Photo</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($a['created_at']) ?></td>
              <td>
                <button type="button" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#appModal<?= $i ?>">
                  <i class="fa-solid fa-eye"></i> View
                </button>
              </td>
            </tr>

            <!-- Modal -->
            <div class="modal fade" id="appModal<?= $i ?>" tabindex="-1" aria-labelledby="appModalLabel<?= $i ?>" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="appModalLabel<?= $i ?>">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row">
                      <div class="col-md-8">
                        <p><b>Name:</b> <?= htmlspecialchars($a['jobseeker_name']) ?></p>
                        <p><b>Email:</b> <?= htmlspecialchars($a['email']) ?></p>
                        <p><b>Phone:</b> <?= htmlspecialchars($a['phone']) ?></p>
                        <p><b>Address:</b> <?= htmlspecialchars($a['address'] ?? '') ?></p>
                        <p><b>Applied For:</b> <?= htmlspecialchars($a['job_title']) ?></p>
                        <?php if(!empty($a['notes'])): ?>
                          <p><b>Notes:</b> <?= nl2br(htmlspecialchars($a['notes'])) ?></p>
                        <?php endif; ?>
                        <?php if(!empty($a['resume'])): ?>
                          <p><a href="<?= htmlspecialchars($a['resume']) ?>" target="_blank" class="btn btn-outline-success btn-sm">View Resume</a></p>
                        <?php endif; ?>
                      </div>
                      <div class="col-md-4 text-center">
                        <?php if(!empty($a['photo'])): ?>
                          <img src="<?= htmlspecialchars($a['photo']) ?>" class="img-fluid rounded" alt="Photo">
                        <?php else: ?>
                          <span class="text-muted">No Photo</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
            <?php $i++; endwhile; ?>
          </tbody>
        </table>
      </div> <!-- end table-responsive -->
    <?php else: ?>
      <p class="text-muted">No applications yet.</p>
    <?php endif; ?>
  </div>
</div>

</div>


<!-- Bootstrap 5 JS & CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<!-- Make sure you have Bootstrap 5 CSS & JS included -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- DELETE REQUESTS TAB -->
    <div class="tab-pane fade" id="delete-requests">
      <div class="card mb-4">
        <div class="card-header bg-danger text-white">
          <h5 class="mb-0">Job Delete Requests (Pending Admin Approval)</h5>
        </div>
        <div class="card-body">
          <?php if ($delete_reqs->num_rows > 0): ?>
            <table class="table table-bordered align-middle">
              <thead class="table-light">
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
                  <td><?= $i++ ?></td>
                  <td><?= htmlspecialchars($d['title']) ?></td>
                  <td><?= htmlspecialchars($d['company']) ?></td>
                  <td><?= htmlspecialchars($d['employer_name']) ?></td>
                  <td><span class="badge bg-warning text-dark">Awaiting Deletion</span></td>
                  <td>
                    <a href="?approve_delete_job=<?= $d['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Approve deletion for this job?')">✅ Approve Delete</a>
                    <a href="?cancel_delete_job=<?= $d['id'] ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Cancel delete request?')">❌ Cancel</a>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p class="text-muted">No delete requests pending.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include 'portal_footer.php'; ?>
