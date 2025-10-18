<?php 
include 'portal_header.php'; 
require_login();

$uid = $_SESSION['user_id'];

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job'])) {
    $id = intval($_POST['job_id']);
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);

    $stmt = $mysqli->prepare("UPDATE jobs SET title=?, description=? WHERE id=? AND employer_id=?");
    $stmt->bind_param("ssii", $title, $desc, $id, $uid);
    $stmt->execute();
    $stmt->close();

    echo '<div class="alert alert-success text-center">✅ Job updated successfully!</div>';
}

// Handle delete request
if (isset($_GET['delete_request'])) {
    $job_id = intval($_GET['delete_request']);
    $mysqli->query("UPDATE jobs SET delete_requested=1 WHERE id=$job_id AND employer_id=$uid");
    echo '<div class="alert alert-warning text-center">🕓 Delete request sent to admin for approval.</div>';
}

// Fetch jobs
$res = $mysqli->query("SELECT * FROM jobs WHERE employer_id=$uid ORDER BY id DESC");
?>

<div class="container mt-4">
  <h3 class="mb-4">🧾 Manage Your Job Posts</h3>

  <div class="row">
    <?php while($r = $res->fetch_assoc()): ?>
      <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
          <div class="card-body d-flex flex-column">

            <!-- Title + Status -->
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="card-title mb-0"><?= htmlspecialchars($r['title']) ?></h5>
              <span class="badge <?= $r['status']=='active'?'bg-success':'bg-secondary' ?>">
                <?= ucfirst($r['status']) ?>
              </span>
            </div>

            <!-- Description -->
            <p class="card-text mb-3"><?= nl2br(htmlspecialchars(substr($r['description'],0,150))) ?>...</p>

            <!-- Action Buttons -->
            <div class="d-flex gap-3 mt-auto">
              <!-- Edit Button triggers collapse -->
              <button class="btn btn-primary btn-sm px-3" type="button" data-bs-toggle="collapse" data-bs-target="#editJob<?= $r['id'] ?>" aria-expanded="false" aria-controls="editJob<?= $r['id'] ?>">
                 Edit
              </button>

              <!-- Delete Request -->
              <?php if ($r['delete_requested']): ?>
                <span class="text-danger fw-bold align-self-center">🕓 Delete Requested</span>
              <?php else: ?>
                <a href="?delete_request=<?= $r['id'] ?>" class="btn btn-outline-danger btn-sm px-3"
                   onclick="return confirm('Are you sure you want to request deletion of this job?');">
                    Delete
                </a>
              <?php endif; ?>

              <!-- View Job -->
      

            <!-- Collapsible Edit Form -->
            <div class="collapse mt-3" id="editJob<?= $r['id'] ?>">
              <form method="post">
                <input type="hidden" name="job_id" value="<?= $r['id'] ?>">

                <div class="mb-2">
                  <label class="form-label fw-bold">Job Title:</label>
                  <input type="text" name="title" value="<?= htmlspecialchars($r['title']) ?>" class="form-control form-control-sm" required>
                </div>

                <div class="mb-2">
                  <label class="form-label fw-bold">Description:</label>
                  <textarea name="description" rows="3" class="form-control form-control-sm" required><?= htmlspecialchars($r['description']) ?></textarea>
                </div>

                <button type="submit" name="update_job" class="btn btn-success btn-sm px-3"> Save</button>
              </form>
            </div>

          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'portal_footer.php'; ?>
