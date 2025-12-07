<?php
$uid = $_SESSION['user_id'] ?? 0;
if (!$uid) die("Please login to access this page.");

// Store message for toast
$toast_message = '';
$toast_class = '';

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job'])) {
    $id      = intval($_POST['job_id']);
    $title   = trim($_POST['title']);
    $desc    = trim($_POST['description']);
    $country = trim($_POST['country']);
    $salary  = trim($_POST['salary']);
    $status  = trim($_POST['status']);

    $stmt = $mysqli->prepare("UPDATE jobs SET title=?, description=?, country=?, salary=?, status=? WHERE id=? AND employer_id=?");
    $stmt->bind_param("sssssii", $title, $desc, $country, $salary, $status, $id, $uid);
    if ($stmt->execute()) {
        $toast_message = "✅ Job updated successfully!";
        $toast_class = "bg-success text-white";
    } else {
        $toast_message = "❌ Failed to update job.";
        $toast_class = "bg-danger text-white";
    }
    $stmt->close();
}

// Handle delete request
if (isset($_GET['delete_request'])) {
    $job_id = intval($_GET['delete_request']);
    $stmt = $mysqli->prepare("UPDATE jobs SET delete_requested=1 WHERE id=? AND employer_id=?");
    $stmt->bind_param("ii", $job_id, $uid);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $toast_message = " Delete request sent. Waiting for admin approval.";
        $toast_class = "bg-warning text-dark";
    } else {
        $toast_message = "❌ Failed to send delete request.";
        $toast_class = "bg-danger text-white";
    }
    $stmt->close();
   header("Location: " .empty($_SERVER['HTTP_REFERER']) ? 'employer_dashboard.php?tab=my_jobs' : $_SERVER['HTTP_REFERER']);
    exit();
    
}


// Fetch jobs
$res = $mysqli->query("SELECT * FROM jobs WHERE employer_id=$uid ORDER BY id DESC");



?>

<!-- Toast Container -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
    <div id="toastNotification" class="toast align-items-center <?= $toast_class ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <?= htmlspecialchars($toast_message) ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<link rel="shortcut icon" href="img/Logo.png" type="image/x-icon">
<div class="container mt-5">
    <h3 class="mb-4 text-center">Manage Your Job Posts</h3>

    <div class="row row-cols-1 row-cols-md-2 g-4">
        <?php while($r = $res->fetch_assoc()): ?>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title mb-0"><?= htmlspecialchars($r['title']) ?></h5>
                        <span class="badge <?= $r['status']=='approved'?'bg-success':'bg-warning text-dark' ?>"><?= ucfirst($r['status']) ?></span>
                    </div>
                    <p class="card-text mb-3"><?= nl2br(htmlspecialchars(substr($r['description'],0,120))) ?>...<br>
                        <small class="text-muted"><?= htmlspecialchars($r['country'] ?? '') ?> | $<?= htmlspecialchars($r['salary'] ?? '') ?></small>
                    </p>
                    <div class="mt-auto d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" data-bs-toggle="modal" data-bs-target="#editJobModal<?= $r['id'] ?>">Edit</button>
                        <?php if($r['delete_requested']==1): ?>
                            <button class="btn btn-warning btn-sm flex-grow-1" disabled>Delete Pending</button>
                        <?php else: ?>
                            <a href="?tab=my_jobs&delete_request=<?= $r['id'] ?>" class="btn btn-outline-danger btn-sm flex-grow-1"
                               onclick="return confirm('⚠️ Request deletion of this job?\nAdmin will review your request.');">Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editJobModal<?= $r['id'] ?>" tabindex="-1" aria-labelledby="editJobLabel<?= $r['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="post">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Job: <?= htmlspecialchars($r['title']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="job_id" value="<?= $r['id'] ?>">
                            <div class="mb-3"><label class="form-label fw-bold">Job Title</label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($r['title']) ?>" required></div>
                            <div class="mb-3"><label class="form-label fw-bold">Description</label>
                                <textarea name="description" rows="5" class="form-control" required><?= htmlspecialchars($r['description']) ?></textarea></div>
                            <div class="mb-3"><label class="form-label fw-bold">Country</label>
                                <select name="country" class="form-select" required>
                                    <?php foreach(['Saudi Arabia','UAE','Kuwait','Bahrain','Qatar','Oman'] as $c): ?>
                                        <option value="<?= $c ?>" <?= ($r['country']==$c)?'selected':'' ?>><?= $c ?></option>
                                    <?php endforeach; ?>
                                </select></div>
                            <div class="mb-3"><label class="form-label fw-bold">Salary</label>
                                <input type="text" name="salary" class="form-control" value="<?= htmlspecialchars($r['salary'] ?? '') ?>"></div>
                            <div class="mb-3"><label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="approved" <?= ($r['status']=='approved')?'selected':'' ?>>Approved</option>
                                    <option value="pending" <?= ($r['status']=='pending')?'selected':'' ?>>Pending</option>
                                    <option value="inactive" <?= ($r['status']=='inactive')?'selected':'' ?>>Inactive</option>
                                </select></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_job" class="btn btn-success">💾 Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php endwhile; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toastEl = document.getElementById('toastNotification');
    if(toastEl && toastEl.innerText.trim() !== '') {
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }
});
</script>

<?php include 'portal_footer.php'; ?>
