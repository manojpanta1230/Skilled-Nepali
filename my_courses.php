<?php 
include 'portal_header.php'; 
require_login();

$uid = $_SESSION['user_id'];

// Fetch all courses for this training center
$res = $mysqli->query("SELECT * FROM courses WHERE training_center_id = $uid ORDER BY id DESC");

// Separate by status
$pending_courses = [];
$approved_courses = [];

while($r = $res->fetch_assoc()) {
  if($r['status'] == 'pending') {
    $pending_courses[] = $r;
  } elseif($r['status'] == 'approved') {
    $approved_courses[] = $r;
  }
}
?>

<div class="container mt-5">

  <!-- Navigation Tabs -->
  <ul class="nav nav-tabs justify-content-center mb-4" id="courseTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active fw-semibold" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab">Approved</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-semibold" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">Pending</button>
    </li>
  </ul>

  <!-- Tab Content -->
  <div class="tab-content" id="courseTabsContent">
    <!-- Approved Tab -->
    <div class="tab-pane fade show active" id="approved" role="tabpanel" aria-labelledby="approved-tab">
      <?php if(count($approved_courses) > 0): ?>
        <div class="row">
          <?php foreach($approved_courses as $course): ?>
            <div class="col-md-6 col-lg-4 mb-4">
              <div class="card shadow-sm border-success rounded-4 h-100">
                <div class="card-body">
                  <h5 class="card-title text-success fw-bold"><?php echo htmlspecialchars($course['title']); ?></h5>
                  <p class="card-text small text-muted mb-2">
                    <?php echo nl2br(htmlspecialchars($course['structure'])); ?>
                  </p>
                  <p class="mb-1"><strong>Cost:</strong> $<?php echo htmlspecialchars($course['cost']); ?></p>
                  <span class="badge bg-success">Approved</span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-secondary text-center">No approved courses yet.</div>
      <?php endif; ?>
    </div>

    <!-- Pending Tab -->
    <div class="tab-pane fade" id="pending" role="tabpanel" aria-labelledby="pending-tab">
      <?php if(count($pending_courses) > 0): ?>
        <div class="row mt-3">
          <?php foreach($pending_courses as $course): ?>
            <div class="col-md-6 col-lg-4 mb-4">
              <div class="card shadow-sm border-warning rounded-4 h-100">
                <div class="card-body">
                  <h5 class="card-title text-warning fw-bold"><?php echo htmlspecialchars($course['title']); ?></h5>
                  <p class="card-text small text-muted mb-2">
                    <?php echo nl2br(htmlspecialchars($course['structure'])); ?>
                  </p>
                  <p class="mb-1"><strong>Cost:</strong> $<?php echo htmlspecialchars($course['cost']); ?></p>
                  <span class="badge bg-warning text-dark">Pending</span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-secondary text-center mt-3">No pending courses.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'portal_footer.php'; ?>
