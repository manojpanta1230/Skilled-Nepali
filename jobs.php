<?php
include 'portal_header.php';

// ✅ Ensure session started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------
// Fetch approved jobs
// --------------------------
$res = $mysqli->query("
    SELECT j.*, u.name AS employer, u.company 
    FROM jobs j 
    JOIN users u ON j.employer_id = u.id 
    WHERE j.status='approved'
    ORDER BY j.id DESC
");
?>

<div class="container mt-5">
    <h2 class="mb-4 text-primary text-center">Available Jobs</h2>
    <div class="row g-4">
        <?php if ($res->num_rows > 0): ?>
            <?php while($r = $res->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <div class="job-card p-3 h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-briefcase-fill text-primary"></i> <?= htmlspecialchars($r['title']) ?>
                            </h5>

                            <p class="card-text">
                                <i class="bi bi-card-text text-success"></i> <?= nl2br(htmlspecialchars($r['description'])) ?>
                            </p>

                            <p class="mb-1">
                                <i class="bi bi-currency-dollar text-warning"></i> <b>Salary:</b> <?= htmlspecialchars($r['salary']) ?>
                            </p>

                            <p class="mb-3">
                                <i class="bi bi-building text-info"></i> <b>Company:</b> <?= htmlspecialchars($r['company']) ?>
                            </p>

                            <?php
                            // ✅ Normalize role check
                            if (is_jobseeker()):

                            ?>
                                <a href="apply.php?job_id=<?= $r['id'] ?>" class="btn btn-success w-100">
                                    <i class="bi bi-check-circle-fill"></i> Apply Now
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-secondary w-100">
                                    <i class="bi bi-lock-fill"></i> Login as Jobseeker to Apply
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">No jobs available at the moment. Please check back later.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- AOS Library for scroll animations -->
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({
    duration: 900,
    easing: 'ease-in-out',
});
</script>

<!-- Custom CSS for 3D-like cards -->
<style>
.job-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.job-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 15px 30px rgba(0,0,0,0.25);
}
.card-title i, .card-text i, .mb-1 i, .mb-3 i {
    margin-right: 8px;
}
.btn i {
    margin-right: 5px;
}
</style>

<?php include 'portal_footer.php'; ?>
