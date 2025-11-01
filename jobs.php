<?php
include 'portal_header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch approved jobs
$jobs = $mysqli->query("
    SELECT j.*, u.name AS employer, u.company, j.category, j.country
    FROM jobs j 
    JOIN users u ON j.employer_id = u.id 
    WHERE j.status='approved'
    ORDER BY j.id DESC
");

// Fetch approved training courses (added u.image)
$courses = $mysqli->query("
    SELECT c.*, u.name AS trainer, u.company, u.image
    FROM courses c
    JOIN users u ON c.training_center_id = u.id
    WHERE c.status='approved'
    ORDER BY c.id DESC
");
?>

<div class="container mt-5">
    <h2 class="mb-4 text-primary text-center">Opportunities</h2>

    <!-- Nav Tabs -->
    <ul class="nav nav-tabs justify-content-center mb-4" id="opportunityTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs" type="button" role="tab">
                Jobs
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="trainings-tab" data-bs-toggle="tab" data-bs-target="#trainings" type="button" role="tab">
                Training Centers
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="opportunityTabsContent">
        <!-- Jobs Tab -->
        <div class="tab-pane fade show active" id="jobs" role="tabpanel">
            <div class="row g-4">
                <?php if ($jobs->num_rows > 0): ?>
                    <?php while($job = $jobs->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4" data-aos="fade-up">
                            <div class="job-card p-3 h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="bi bi-briefcase-fill text-primary"></i> <?= htmlspecialchars($job['title']) ?></h5>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                                    <p><i class="bi bi-currency-dollar text-warning"></i> <b>Salary:</b> <?= htmlspecialchars($job['salary']) ?></p>
                                    <p><i class="bi bi-building text-info"></i> <b>Company:</b> <?= htmlspecialchars($job['company']) ?></p>
                                    <p><i class="bi bi-tags text-success"></i> <b>Category:</b> <?= htmlspecialchars($job['category']) ?></p>
                                    <p><i class="bi bi-geo-alt text-primary"></i> <b>Location:</b> <?= htmlspecialchars($job['country']) ?></p>

                                    <?php if (is_jobseeker()): ?>
                                        <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-success w-100 mt-2">
                                            <i class="bi bi-check-circle-fill"></i> Apply Now
                                        </a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-secondary w-100 mt-2">
                                            <i class="bi bi-lock-fill"></i> Login as Jobseeker
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">No jobs available currently.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Trainings Tab -->
        <div class="tab-pane fade" id="trainings" role="tabpanel">
            <div class="row g-4">
                <?php if ($courses->num_rows > 0): ?>
                    <?php while($course = $courses->fetch_assoc()): 
                        $logo = !empty($course['image']) && file_exists($course['image']) ? $course['image'] : 'assets/default_logo.png';
                    ?>
                        <div class="col-md-6 col-lg-4" data-aos="fade-up">
                            <div class="training-card p-3 h-100 position-relative">
                                <!-- Trainer Logo -->
                                <div class="text-center mb-3">
                                    <img src="<?= htmlspecialchars($logo) ?>" 
                                         alt="<?= htmlspecialchars($course['company']) ?> Logo"
                                         class="trainer-logo img-fluid rounded-circle shadow-sm"
                                         style="width:80px;height:80px;object-fit:cover;">
                                </div>

                                <div class="card-body">
                                    <h5 class="card-title text-success"><i class="bi bi-journal-code"></i> <?= htmlspecialchars($course['title']) ?></h5>
                                    <p class="card-text"><?= nl2br(htmlspecialchars(substr($course['structure'], 0, 100))) ?>...</p>
                                    <p><b>Trainer:</b> <?= htmlspecialchars($course['company']) ?></p>
                                    <p><b>Cost:</b> $<?= htmlspecialchars($course['cost']) ?></p>

                                    <?php if (is_jobseeker()): ?>
                                        <a href="apply_training.php?course_id=<?= $course['id'] ?>" class="btn btn-success w-100 mt-2">
                                            <i class="bi bi-check-circle-fill"></i> Apply Now
                                        </a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-secondary w-100 mt-2">
                                            <i class="bi bi-lock-fill"></i> Login as Jobseeker
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">No training courses available currently.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS + AOS -->
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ duration: 900, easing: 'ease-in-out' });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
.job-card, .training-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
}
.job-card:hover, .training-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 15px 30px rgba(0,0,0,0.25);
}

</style>

<?php include 'portal_footer.php'; ?>
