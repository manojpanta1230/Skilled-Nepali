<?php include 'header.php';
$res=$mysqli->query("SELECT c.*,u.name as center FROM courses c JOIN users u ON c.training_center_id=u.id WHERE c.status='approved'");
while($r=$res->fetch_assoc()): ?>
<div class="card mb-3"><div class="card-body">
  <h5><?php echo $r['title']; ?></h5>
  <p><?php echo nl2br($r['structure']); ?></p>
  <p><b>Cost:</b> <?php echo $r['cost']; ?></p>
  <small>Training Center: <?php echo $r['center']; ?></small>
</div></div>
<?php endwhile; include 'footer.php'; ?>
