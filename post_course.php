<?php include 'portal_header.php'; require_login(); if(!is_training_center()) die("Not allowed.");
if($_POST){
  $title=$_POST['title']; $structure=$_POST['structure']; $cost=$_POST['cost'];
  $uid=$_SESSION['user_id'];
  $stmt=$mysqli->prepare("INSERT INTO courses (training_center_id,title,structure,cost,status) VALUES (?,?,?,?, 'pending')");
  $stmt->bind_param("isss",$uid,$title,$structure,$cost);
  $stmt->execute();
  echo "<div class='alert alert-success'>Course posted (pending approval)</div>";
}
?>
<form method="post">
  <h3>Post Course</h3>
  <input name="title" class="form-control mb-2" placeholder="Course Title">
  <textarea name="structure" class="form-control mb-2" placeholder="Course Structure"></textarea>
  <input name="cost" class="form-control mb-2" placeholder="Cost">
  <button class="btn btn-primary">Submit</button>
</form>
<?php include 'portal_footer.php'; ?>
