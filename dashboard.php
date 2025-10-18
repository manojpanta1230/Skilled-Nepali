<?php 
include 'portal_header.php'; 
require_login(); 
$u = current_user(); 
?>

<div class="container mt-5">
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <h2 class="mb-3 text-primary">
        Welcome, <?= htmlspecialchars($u['company']); ?>!
      </h2>
      <h5 class="text-muted mb-4">
        Role: <span class="badge bg-info text-dark"><?= htmlspecialchars($u['role']); ?></span>
      </h5>

      <!-- ADMIN SECTION -->
      <?php if (is_admin()): ?>
        <div class="card mb-4 border-0 shadow-sm">
          <div class="card-header bg-warning text-dark fw-bold">
            Admin Controls
          </div>
          <div class="card-body">
            <p class="text-muted">Manage users, job posts, and training center courses.</p>
            <a href="admin_panel.php" class="btn btn-warning">
              <i class="bi bi-gear-fill"></i> Go to Admin Panel
            </a>
          </div>
        </div>
      <?php endif; ?>

      <!-- EMPLOYER SECTION -->
      <?php if (is_employer()): 
        // Fetch applications for jobs posted by this employer
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
        <div class="card mb-4 border-0 shadow-sm">
          <div class="card-header bg-primary text-white fw-bold">
            Employer Dashboard
          </div>
          <div class="card-body">
            <p class="text-muted">Manage your company’s job postings and see all applications received.</p>
            <a href="post_job.php" class="btn btn-success me-2">
              <i class="bi bi-plus-circle"></i> Post a New Job
            </a>
            <a href="my_jobs.php" class="btn btn-outline-primary me-2">
              <i class="bi bi-briefcase"></i> View My Jobs
            </a>

            <!-- Applications Table -->
            <div class="card mt-3">
              <div class="card-header bg-warning text-dark fw-bold">
                Job Applications Received
              </div>
              <div class="card-body">
                <?php if($apps->num_rows > 0): ?>
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
                          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#appModal<?= $i ?>">
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
                <?php else: ?>
                  <p class="text-muted">No applications yet for your jobs.</p>
                <?php endif; ?>
              </div>
            </div>

          </div>
        </div>
      <?php endif; ?>

      <!-- TRAINING CENTER SECTION -->
      <?php if (is_training_center()): ?>
        <div class="card mb-4 border-0 shadow-sm">
          <div class="card-header bg-success text-white fw-bold">
            Training Center Dashboard
          </div>
          <div class="card-body">
            <p class="text-muted">Add and manage your available courses and training programs.</p>
            <a href="post_course.php" class="btn btn-success me-2">
              <i class="bi bi-journal-plus"></i> Add New Course
            </a>
            <a href="my_courses.php" class="btn btn-outline-success">
              <i class="bi bi-book"></i> My Courses
            </a>
          </div>
        </div>
      <?php endif; ?>

      <!-- JOBSEEKER SECTION -->
      <?php if (is_jobseeker()): ?>
        <div class="card mb-4 border-0 shadow-sm">
          <div class="card-header bg-info text-white fw-bold">
            Jobseeker Dashboard
          </div>
          <div class="card-body">
            <p class="text-muted">Browse job listings and apply for opportunities that fit your skills.</p>
            <a href="jobs.php" class="btn btn-primary">
              <i class="bi bi-search"></i> Browse Jobs
            </a>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include 'portal_footer.php'; ?>
