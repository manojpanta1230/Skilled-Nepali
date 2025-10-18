<?php 
include 'portal_header.php'; 
require_login(); 
if(!is_employer()) die("<div class='alert alert-danger'>Not allowed.</div>");

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $salary = trim($_POST['salary']);
    $country = $_POST['country'];
    $uid = $_SESSION['user_id'];

  
    // Handle file upload
$image_path = null;
if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK){
    $allowed = ['jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if(in_array($ext, $allowed)){
        $image_path = 'uploads/' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }
}

    if(!$error && $title && $description){
        $stmt = $mysqli->prepare("
            INSERT INTO jobs (employer_id, title, description, salary, country, image, status)
            VALUES (?,?,?,?,?,?, 'pending')
        ");
        $stmt->bind_param("isssss", $uid, $title, $description, $salary, $country, $image_path);
        if($stmt->execute()){
            $success = "✅ Job posted successfully (pending admin approval).";
        } else {
            $error = "Database error: ".$mysqli->error;
        }
        $stmt->close();
    } elseif(!$title || !$description) {
        $error = "Please fill in all required fields.";
    }
}
?>

<div class="col-md-8 mx-auto mt-4">
  <h3 class="mb-3">Post a New Job</h3>

  <?php if($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>
  <?php if($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <input name="title" class="form-control mb-2" placeholder="Job Title" required>
    
    <textarea name="description" class="form-control mb-2" rows="6" placeholder="Job Description" required></textarea>

    <input name="salary" class="form-control mb-2" placeholder="Salary">

    <div class="mb-2">
      <label for="country">Country</label>
      <select name="country" class="form-select" required>
        <option value="">Select Country</option>
        <option value="QATAR">Qatar</option>
        <option value="dubai">Dubai</option>
        <option value="Kuwait"> Kuwait</option>
        <option value="saudi_arabia"> Saudi Arabia</option>
      
      </select>
    </div>

    <div class="mb-3">
      <label for="image">Job Image / Company Logo (optional)</label>
      <input type="file" name="image" class="form-control">
    </div>

    <button class="btn btn-primary w-100">Submit</button>
  </form>
</div>

<?php include 'portal_footer.php'; ?>
