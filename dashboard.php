<?php 
include 'portal_header.php'; 
require_login(); 
$u = current_user(); 
?>

<div class="container mt-4">
    <!-- Header Card -->
    <div class="card border-0 shadow-lg mb-4" style="background: linear-gradient(135deg, #2A4365 0%, #1A365D 100%); border-radius: 16px;">
      
           
             
                <?php 
                $user_img = !empty($u['image']) ? $u['image'] : null;
                if ($user_img): ?>
                    
                <?php endif; ?>
           
        </div>
  

    <div class="row g-4">
        <!-- Left Column: Profile Section -->
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 pt-4 pb-3" style="border-radius: 16px 16px 0 0;">
                    <h4 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-person-circle text-primary me-2"></i>
                        Profile Details
                    </h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if ($user_img): ?>
                            <img src="<?= htmlspecialchars($user_img) ?>" 
                                 alt="Profile Image" 
                                 class="rounded-circle shadow mb-3" 
                                 width="120" height="120" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3 shadow" 
                                 style="width: 120px; height: 120px;">
                                <i class="bi bi-person-fill text-secondary" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <h5 class="fw-bold text-dark mb-1">
                            <?= htmlspecialchars($u['company'] ?? $u['name']) ?>
                        </h5>
                        <p class="text-muted mb-4"><?= htmlspecialchars($u['role']); ?></p>
                    </div>

                    <div class="profile-info">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-envelope-fill text-primary"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Email</small>
                                <span class="fw-medium"><?= htmlspecialchars($u['email']) ?></span>
                            </div>
                        </div>

                        <?php if(!empty($u['phone'])): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-telephone-fill text-success"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Phone</small>
                                <span class="fw-medium"><?= htmlspecialchars($u['phone']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($u['address'])): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-light p-2 me-3">
                                <i class="bi bi-geo-alt-fill text-danger"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Address</small>
                                <span class="fw-medium"><?= htmlspecialchars($u['address']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($u['bio'])): ?>
                        <div class="mt-4">
                            <h6 class="text-dark fw-bold mb-2"><i class="bi bi-file-text me-2"></i>Bio</h6>
                            <p class="text-muted bg-light p-3 rounded" style="font-size: 0.9rem;">
                                <?= nl2br(htmlspecialchars($u['bio'])) ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Upload form for Employer & Training Center -->
                    <?php if (is_employer() || is_training_center()): ?>
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="text-dark fw-bold mb-3">
                                <i class="bi bi-camera me-2"></i>Update Profile Image
                            </h6>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <input class="form-control form-control-sm" type="file" name="profile_image" id="profile_image" accept="image/*" required>
                                    <small class="text-muted">Supports: JPG, PNG, WEBP</small>
                                </div>
                                <button type="submit" name="upload_image" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-cloud-upload me-1"></i> Upload Image
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Dashboard Sections -->
        <div class="col-lg-8">
            <?php if (is_admin()): ?>
                <!-- Admin Section -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; border-left: 4px solid #F59E0B;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle p-2 me-3" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                                <i class="bi bi-shield-check text-white"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Admin Controls</h5>
                                <p class="text-muted mb-0">Manage system users and content</p>
                            </div>
                        </div>
                        <p class="text-muted mb-3">Full access to manage users, job posts, training centers, and system settings.</p>
                        <a href="admin_panel.php" class="btn btn-warning fw-medium px-4" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); border: none;">
                            <i class="bi bi-gear-fill me-2"></i>Go to Admin Panel
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- EMPLOYER SECTION -->
            <?php if (is_employer()): 
                $employer_id = $u['id'];
                $sql = "
                    SELECT a.*, j.title AS job_title, u.name AS jobseeker_name 
                    FROM applications a
                    JOIN jobs j ON a.job_id = j.id
                    JOIN users u ON a.user_id = u.id
                    WHERE j.employer_id = ?
                    ORDER BY a.created_at DESC
                ";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("i", $employer_id);
                $stmt->execute();
                $apps = $stmt->get_result();
            ?>
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; border-left: 4px solid #3B82F6;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-2 me-3" style="background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);">
                                    <i class="bi bi-building text-white"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Employer Dashboard</h5>
                                    <p class="text-muted mb-0">Manage jobs and applications</p>
                                </div>
                            </div>
                            <div>
                                <a href="post_job.php" class="btn btn-primary btn-sm me-2" style="background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%); border: none;">
                                    <i class="bi bi-plus-circle me-1"></i>Post Job
                                </a>
                                <a href="my_jobs.php" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-briefcase me-1"></i>My Jobs
                                </a>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <div class="bg-light rounded p-3 text-center">
                                    <h3 class="text-primary mb-1 fw-bold">
                                        <?php 
                                            $total_jobs = $mysqli->query("SELECT COUNT(*) as total FROM jobs WHERE employer_id = $employer_id")->fetch_assoc()['total'];
                                            echo $total_jobs;
                                        ?>
                                    </h3>
                                    <small class="text-muted">Active Jobs</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="bg-light rounded p-3 text-center">
                                    <h3 class="text-success mb-1 fw-bold"><?= $apps->num_rows ?></h3>
                                    <small class="text-muted">Total Applications</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="bg-light rounded p-3 text-center">
                                    <h3 class="text-warning mb-1 fw-bold">
                                        <?php 
                                            $today = date('Y-m-d');
                                            $today_apps = $mysqli->query("SELECT COUNT(*) as today FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.employer_id = $employer_id AND DATE(a.created_at) = '$today'")->fetch_assoc()['today'];
                                            echo $today_apps;
                                        ?>
                                    </h3>
                                    <small class="text-muted">Today's Applications</small>
                                </div>
                            </div>
                        </div>

                        <!-- Applications Table -->
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-people-fill me-2"></i>Recent Applications
                        </h6>
                        <?php if($apps->num_rows > 0): ?>
                            <div class="table-responsive rounded" style="border: 1px solid #e9ecef;">
                                <table class="table table-hover mb-0">
                                    <thead style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                        <tr>
                                            <th class="border-0 py-3">Applicant</th>
                                            <th class="border-0 py-3">Job Title</th>
                                            <th class="border-0 py-3">Applied</th>
                                            <th class="border-0 py-3 text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i=1; while($a=$apps->fetch_assoc()): ?>
                                        <tr class="border-bottom">
                                            <td class="py-3">
                                                <div class="d-flex align-items-center">
                                                    <?php if(!empty($a['photo'])): ?>
                                                        <img src="<?= htmlspecialchars($a['photo']) ?>" alt="Photo" width="32" class="rounded-circle me-2">
                                                    <?php else: ?>
                                                        <div class="rounded-circle bg-secondary me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                            <i class="bi bi-person text-white" style="font-size: 0.8rem;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="fw-medium"><?= htmlspecialchars($a['jobseeker_name']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($a['email']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                <div class="fw-medium"><?= htmlspecialchars($a['job_title']) ?></div>
                                                <small class="text-muted">
                                                    <?php if(!empty($a['phone'])): ?>
                                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($a['phone']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td class="py-3">
                                                <small class="text-muted">
                                                    <?= date('M d, Y', strtotime($a['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td class="py-3 text-end">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#appModal<?= $i ?>">
                                                    <i class="bi bi-eye me-1"></i>View
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Modal -->
                                        <div class="modal fade" id="appModal<?= $i ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                                                    <div class="modal-header border-0 pb-0 pt-4 px-4">
                                                        <h5 class="modal-title fw-bold text-dark">Application Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <div class="d-flex align-items-center mb-4">
                                                                    <?php if(!empty($a['photo'])): ?>
                                                                        <img src="<?= htmlspecialchars($a['photo']) ?>" alt="Photo" width="64" class="rounded-circle me-3">
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($a['jobseeker_name']) ?></h6>
                                                                        <p class="text-muted mb-1"><?= htmlspecialchars($a['email']) ?></p>
                                                                        <small class="text-muted">
                                                                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($a['phone']) ?>
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <h6 class="text-dark fw-bold mb-2">Applied For</h6>
                                                                    <span class="badge bg-primary px-3 py-2"><?= htmlspecialchars($a['job_title']) ?></span>
                                                                </div>
                                                                <?php if(!empty($a['address'])): ?>
                                                                <div class="mb-3">
                                                                    <h6 class="text-dark fw-bold mb-2">Address</h6>
                                                                    <p class="text-muted mb-0"><?= htmlspecialchars($a['address']) ?></p>
                                                                </div>
                                                                <?php endif; ?>
                                                                <?php if(!empty($a['notes'])): ?>
                                                                <div class="mb-3">
                                                                    <h6 class="text-dark fw-bold mb-2">Notes</h6>
                                                                    <p class="text-muted bg-light p-3 rounded"><?= nl2br(htmlspecialchars($a['notes'])) ?></p>
                                                                </div>
                                                                <?php endif; ?>
                                                                <?php if(!empty($a['resume'])): ?>
                                                                <div class="mt-4">
                                                                    <a href="<?= htmlspecialchars($a['resume']) ?>" target="_blank" class="btn btn-outline-primary">
                                                                        <i class="bi bi-file-earmark-text me-1"></i>View Resume
                                                                    </a>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="col-md-4 text-center">
                                                                <?php if(!empty($a['photo'])): ?>
                                                                    <img src="<?= htmlspecialchars($a['photo']) ?>" class="img-fluid rounded shadow mb-3" alt="Photo">
                                                                <?php endif; ?>
                                                                <p class="text-muted">
                                                                    Applied: <?= date('F j, Y', strtotime($a['created_at'])) ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0 pb-4 px-4">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $i++; endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 bg-light rounded">
                                <i class="bi bi-people display-6 text-muted mb-3"></i>
                                <p class="text-muted mb-0">No applications yet for your jobs</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TRAINING CENTER SECTION -->
            <?php if (is_training_center()): ?>
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; border-left: 4px solid #10B981;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-2 me-3" style="background: linear-gradient(135deg, #10B981 0%, #047857 100%);">
                                    <i class="bi bi-mortarboard text-white"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Training Center Dashboard</h5>
                                    <p class="text-muted mb-0">Manage courses and programs</p>
                                </div>
                            </div>
                            <div>
                                <a href="post_course.php" class="btn btn-success btn-sm me-2" style="background: linear-gradient(135deg, #10B981 0%, #047857 100%); border: none;">
                                    <i class="bi bi-plus-circle me-1"></i>Add Course
                                </a>
                                <a href="my_courses.php" class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-book me-1"></i>My Courses
                                </a>
                            </div>
                        </div>
                        <p class="text-muted">Add and manage your training programs, track applications, and update course information.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- JOBSEEKER SECTION -->
            <?php if (is_jobseeker()): 
                $user_id = $u['id'];
                $sql_jobs = "
                    SELECT a.*, j.title AS job_title, j.employer_id
                    FROM applications a
                    JOIN jobs j ON a.job_id = j.id
                    WHERE a.user_id = ?
                    ORDER BY a.created_at DESC
                ";
                $stmt = $mysqli->prepare($sql_jobs);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $applied_jobs = $stmt->get_result();
                $stmt->close();

                $sql_courses = "
                    SELECT ca.*, c.title AS course_title, c.training_center_id
                    FROM course_applications ca
                    JOIN courses c ON ca.course_id = c.id
                    WHERE ca.user_id = ?
                    ORDER BY ca.created_at DESC
                ";
                $stmt2 = $mysqli->prepare($sql_courses);
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();
                $applied_courses = $stmt2->get_result();
                $stmt2->close();
            ?>
                <div class="card border-0 shadow-sm" style="border-radius: 16px; border-left: 4px solid #8B5CF6;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle p-2 me-3" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);">
                                    <i class="bi bi-person-workspace text-white"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Jobseeker Dashboard</h5>
                                    <p class="text-muted mb-0">Your job applications and training enrollments</p>
                                </div>
                            </div>
                            <div>
                                <a href="jobs.php" class="btn btn-primary btn-sm me-2" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); border: none;">
                                    <i class="bi bi-search me-1"></i>Browse Jobs
                                </a>
                                <a href="jobs.php#trainings" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-journal-code me-1"></i>Browse Trainings
                                </a>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="bg-light rounded p-4 text-center">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <div class="rounded-circle bg-primary p-2 me-3">
                                            <i class="bi bi-briefcase-fill text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-primary mb-0 fw-bold"><?= $applied_jobs->num_rows ?></h3>
                                            <small class="text-muted">Jobs Applied</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="bg-light rounded p-4 text-center">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <div class="rounded-circle bg-success p-2 me-3">
                                            <i class="bi bi-mortarboard-fill text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-success mb-0 fw-bold"><?= $applied_courses->num_rows ?></h3>
                                            <small class="text-muted">Trainings Enrolled</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Applied Jobs -->
                        <div class="mb-5">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="bi bi-briefcase-fill me-2"></i>Recent Job Applications
                            </h6>
                            <?php if($applied_jobs->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                                <th class="py-3">Job Title</th>
                                                <th class="py-3">Company</th>
                                                <th class="py-3">Applied Date</th>
                                                <th class="py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($a=$applied_jobs->fetch_assoc()): 
                                                $stmt3 = $mysqli->prepare("SELECT company FROM users WHERE id=?");
                                                $stmt3->bind_param("i", $a['employer_id']);
                                                $stmt3->execute();
                                                $employer_res = $stmt3->get_result()->fetch_assoc();
                                                $stmt3->close();
                                            ?>
                                                <tr>
                                                    <td class="py-3">
                                                        <div class="fw-medium"><?= htmlspecialchars($a['job_title']) ?></div>
                                                        <?php if(!empty($a['resume'])): ?>
                                                            <a href="<?= htmlspecialchars($a['resume']) ?>" target="_blank" class="text-primary small">
                                                                <i class="bi bi-file-earmark-text me-1"></i>View Resume
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3">
                                                        <div class="fw-medium"><?= htmlspecialchars($employer_res['company'] ?? 'N/A') ?></div>
                                                    </td>
                                                    <td class="py-3">
                                                        <?= date('M d, Y', strtotime($a['created_at'])) ?>
                                                    </td>
                                                    <td class="py-3">
                                                        <span class="badge bg-info px-3 py-2">Applied</span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 bg-light rounded">
                                    <i class="bi bi-briefcase display-6 text-muted mb-3"></i>
                                    <p class="text-muted mb-0">You haven't applied to any jobs yet</p>
                                    <a href="jobs.php" class="btn btn-primary btn-sm mt-2">Browse Available Jobs</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Applied Courses -->
                        <div>
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="bi bi-journal-check me-2"></i>Training Enrollments
                            </h6>
                            <?php if($applied_courses->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                                <th class="py-3">Course Title</th>
                                                <th class="py-3">Training Center</th>
                                                <th class="py-3">Enrolled Date</th>
                                                <th class="py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($c=$applied_courses->fetch_assoc()): 
                                                $stmt4 = $mysqli->prepare("SELECT company FROM users WHERE id=?");
                                                $stmt4->bind_param("i", $c['training_center_id']);
                                                $stmt4->execute();
                                                $center_res = $stmt4->get_result()->fetch_assoc();
                                                $stmt4->close();
                                            ?>
                                                <tr>
                                                    <td class="py-3">
                                                        <div class="fw-medium"><?= htmlspecialchars($c['course_title']) ?></div>
                                                        <?php if(!empty($c['resume'])): ?>
                                                            <a href="<?= htmlspecialchars($c['resume']) ?>" target="_blank" class="text-primary small">
                                                                <i class="bi bi-file-earmark-text me-1"></i>View Resume
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3">
                                                        <div class="fw-medium"><?= htmlspecialchars($center_res['company'] ?? 'N/A') ?></div>
                                                    </td>
                                                    <td class="py-3">
                                                        <?= date('M d, Y', strtotime($c['created_at'])) ?>
                                                    </td>
                                                    <td class="py-3">
                                                        <span class="badge bg-success px-3 py-2">Enrolled</span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 bg-light rounded">
                                    <i class="bi bi-journal display-6 text-muted mb-3"></i>
                                    <p class="text-muted mb-0">You haven't enrolled in any training courses yet</p>
                                    <a href="jobs.php#trainings" class="btn btn-success btn-sm mt-2">Browse Training Courses</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Handle the image upload
if (isset($_POST['upload_image']) && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    $allowed_types = ['image/jpeg','image/png','image/jpg','image/webp'];
    
    if (in_array($file['type'], $allowed_types) && $file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = 'uploads/profile_' . $u['id'] . '_' . time() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $new_name)) {
            $stmt = $mysqli->prepare("UPDATE users SET image = ? WHERE id = ?");
            $stmt->bind_param("si", $new_name, $u['id']);
            if ($stmt->execute()) {
                echo '<div class="alert alert-success alert-dismissible fade show mt-3 mx-4" style="border-radius: 12px;" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Profile image updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
                $u['image'] = $new_name;
            }
            $stmt->close();
        } else {
            echo '<div class="alert alert-danger alert-dismissible fade show mt-3 mx-4" style="border-radius: 12px;" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Failed to upload image. Try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        }
    } else {
        echo '<div class="alert alert-danger alert-dismissible fade show mt-3 mx-4" style="border-radius: 12px;" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Invalid file type. Only JPG, PNG, WEBP allowed.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
}
?>

<?php include 'portal_footer.php'; ?>

<style>
.card {
    transition: transform 0.3s ease;
}
.card:hover {
    transform: translateY(-5px);
}
.badge {
    font-weight: 500;
}
.table th {
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.table td {
    vertical-align: middle;
}
.modal-content {
    border: none;
}
.btn {
    border-radius: 8px;
    font-weight: 500;
}
.form-control {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.form-control:focus {
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.rounded-circle {
    object-fit: cover;
}
</style>