<?php 
include 'portal_header.php'; 
if (is_logged_in()) header('Location: dashboard.php');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize input
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $company = trim($_POST['company'] ?? ''); // Only for employer

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = 'All fields are required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if (!in_array($role, ['employer', 'jobseeker', 'training_center'])) {
        $errors[] = 'Invalid role selection.';
    }

    // If role is employer, require company name
    if ($role === 'employer') {
        if (empty($company)) {
            $errors[] = "Company name is required for employers.";
        } else {
            // Check duplicate company name
            $checkCompany = $mysqli->prepare("SELECT id FROM users WHERE company = ? AND role='employer'");
            $checkCompany->bind_param("s", $company);
            $checkCompany->execute();
            $checkCompany->store_result();
            if ($checkCompany->num_rows > 0) {
                $errors[] = "This company is already registered by another employer.";
            }
            $checkCompany->close();
        }
    }

    // Check duplicate email
    $check = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $errors[] = "This email is already registered.";
    }
    $check->close();

    // Insert if valid
  // Insert if valid
if (!$errors) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("INSERT INTO users (name,email,password,role,company,status) VALUES (?,?,?,?,?, 'pending')");
    $stmt->bind_param("sssss", $name, $email, $hash, $role, $company);
    if ($stmt->execute()) {
        $success = "✅ Registration successful! Wait for admin approval before logging in.";
        // Reset form fields
        $_POST = [];
    } else {
        $errors[] = "Database error: " . $mysqli->error;
    }
    $stmt->close();
}

}
?>

<div class="col-md-6 mx-auto">
  <h3 class="mb-3">Register</h3>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>

  <form method="post">
    <input name="name" class="form-control mb-2" placeholder="Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    <input name="email" type="email" class="form-control mb-2" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    <input name="password" type="password" class="form-control mb-2" placeholder="Password">

    <select name="role" id="roleSelect" class="form-control mb-2" onchange="toggleCompanyField()">
      <option value="jobseeker" <?= (($_POST['role'] ?? '')==='jobseeker')?'selected':'' ?>>Jobseeker</option>
      <option value="employer" <?= (($_POST['role'] ?? '')==='employer')?'selected':'' ?>>Employer</option>
      <option value="training_center" <?= (($_POST['role'] ?? '')==='training_center')?'selected':'' ?>>Training Center</option>
    </select>

    <!-- Company name input, only for employer -->
    <input name="company" id="companyField" class="form-control mb-3" placeholder="Company Name" value="<?= htmlspecialchars($_POST['company'] ?? '') ?>" style="display:none;">

    <button class="btn btn-primary w-100" style="background-color:#00A098">Register</button>
  </form>
</div>

<script>
function toggleCompanyField() {
    var role = document.getElementById('roleSelect').value;
    var companyField = document.getElementById('companyField');
    companyField.style.display = (role === 'employer') ? 'block' : 'none';
}
// Initialize display on page load
toggleCompanyField();
</script>

<?php include 'portal_footer.php'; ?>
