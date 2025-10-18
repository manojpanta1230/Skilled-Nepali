<?php include 'portal_header.php'; require_login();
$uid=$_SESSION['user_id'];
$res=$mysqli->query("SELECT * FROM jobs WHERE employer_id=$uid");
while($r=$res->fetch_assoc()): ?>
<div class="card mb-2"><div class="card-body">
  <h5><?php echo $r['title']; ?> (<?php echo $r['status']; ?>)</h5>
  <p><?php echo nl2br($r['description']); ?></p>
</div></div>
<?php endwhile; include 'portal_footer.php'; ?>

