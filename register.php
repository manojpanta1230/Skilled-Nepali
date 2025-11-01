<?php
include 'portal_header.php';
require 'vendor/autoload.php'; // PHPMailer

// Redirect logged-in users
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'];
    $company = trim($_POST['company'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $imagePath = null; // For training center image

    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = 'All fields are required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if (!in_array($role, ['jobseeker', 'employer', 'training_center'])) {
        $errors[] = 'Invalid role selection.';
    }

    // Company / Training Center name required
    if (($role === 'employer' || $role === 'training_center') && empty($company)) {
        $errors[] = ($role === 'employer') ? "Company name is required for employers." : "Training Center name is required.";
    }

    // Employer must provide designation
    if ($role === 'employer' && empty($designation)) {
        $errors[] = "Designation is required for employers.";
    }

    // Training center must upload image
    if ($role === 'training_center') {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = "Training Center must upload an image.";
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                $errors[] = "Only JPG, PNG, GIF images are allowed.";
            } else {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $newName = uniqid('tc_', true) . "." . $ext;
                $uploadDir = 'uploads/training_centers/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $imagePath = $uploadDir . $newName;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                    $errors[] = "Failed to upload image.";
                }
            }
        }
    }

    // Check duplicate email
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $errors[] = "Email is already registered.";
    $stmt->close();

    // Check duplicate company for employer
    if ($role === 'employer' && empty($errors)) {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE company=? AND role='employer'");
        $stmt->bind_param("s", $company);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors[] = "Company is already registered.";
        $stmt->close();
    }

    // Proceed if no errors
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($role === 'jobseeker') {
            // Jobseeker: insert into database as pending OTP verification
            $stmt = $mysqli->prepare("INSERT INTO users (name,email,password,role,status) VALUES (?,?,?,?, 'otp_pending')");
            $stmt->bind_param("ssss", $name, $email, $hash, $role);
            if ($stmt->execute()) {
                $userId = $stmt->insert_id;
                $_SESSION['otp_user_id'] = $userId;

                // Generate OTP
                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_expiry'] = time() + 300; // 5 minutes

                // Send OTP via PHPMailer
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'pantamanoj08@gmail.com';
                    $mail->Password = 'qjms snqf uzjn pvdc';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('pantamanoj08@gmail.com', 'Job Portal');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your OTP Code';
                    $mail->Body = "Hi $name, <br>Your OTP code is <b>$otp</b> (valid for 5 minutes).";

                    $mail->send();
                    header("Location: verify_otp.php"); // redirect to OTP page
                    exit;
                } catch (Exception $e) {
                    $errors[] = "Mailer error: " . $mail->ErrorInfo;
                }
            } else {
                $errors[] = "Database error: " . $mysqli->error;
            }
            $stmt->close();

        } else {
            // Employer / Training Center
           $stmt = $mysqli->prepare("INSERT INTO users (name,email,password,role,company,designation,image,status) VALUES (?,?,?,?,?,?,?, 'pending')");
$stmt->bind_param("sssssss", $name, $email, $hash, $role, $company, $designation, $imagePath);

if ($stmt->execute()) {
    $success = "✅ Registration successful! Wait for admin approval before logging in.";
    $_POST = [];

    // PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'pantamanoj08@gmail.com';
        $mail->Password = 'qjms snqf uzjn pvdc';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // 1️⃣ Send Welcome Email to User
        $mail->setFrom('pantamanoj08@gmail.com', 'Job Portal of Skilled Nepali');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Job Portal of Skilled Nepali';
        $mail->Body = "
            <p>Dear <strong>$name</strong>,</p>
            <p>Welcome to <strong>Job Portal of Skilled Nepali</strong>!</p>
            <p>You registered as <strong>$role</strong>.</p>
            <p>You will be notified shortly after admin approval.</p>
            <br>
            <p>Best regards,<br>Job Portal Team</p>
        ";
        $mail->send();

        // 2️⃣ Send Notification Email to Admin
        $adminEmail = 'pantamanoj08@gmail.com'; // Replace with actual admin email
        $mail->clearAllRecipients();
        $mail->addAddress($adminEmail, 'Admin');
        $mail->Subject = 'New '.$role.' Registration Pending Approval';
        $mail->Body = "
            <p>Hello Admin,</p>
            <p>A new <strong>$role</strong> has registered on the portal and is pending approval.</p>
            <p><b>Name:</b> $name<br>
               <b>Email:</b> $email<br>
               <b>Company:</b> $company<br>
               <b>Designation:</b> $designation</p>
            <p>Please review and approve the registration in the admin panel.</p>
            <br>
            <p>Best regards,<br>Job Portal System</p>
        ";
        $mail->send();

    } catch (Exception $e) {
        $errors[] = "Mailer error: " . $mail->ErrorInfo;
    }

} else {
    $errors[] = "Database error: " . $mysqli->error;
}
$stmt->close();

        }
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

  <form method="post" id="registerForm" enctype="multipart/form-data">
    <input name="name" class="form-control mb-2" placeholder="Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
    <input name="email" type="email" class="form-control mb-2" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    <input name="password" type="password" class="form-control mb-2" placeholder="Password" id="passwordField" required>

    <select name="role" id="roleSelect" class="form-control mb-2" onchange="toggleFields()" required>
      <option value="jobseeker" <?= (($_POST['role'] ?? '')==='jobseeker')?'selected':'' ?>>Jobseeker</option>
      <option value="employer" <?= (($_POST['role'] ?? '')==='employer')?'selected':'' ?>>Employer</option>
      <option value="training_center" <?= (($_POST['role'] ?? '')==='training_center')?'selected':'' ?>>Training Center</option>
    </select>

    <!-- Company / Training Center name -->
    <input name="company" id="companyField" class="form-control mb-2" 
           placeholder="Company / Center Name" 
           value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">

    <!-- Designation for Employer -->
    <input name="designation" id="designationField" class="form-control mb-2" 
           placeholder="Designation" 
           value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>">

    <!-- Image upload for Training Center -->
    <input type="file" name="image" id="imageField" class="form-control mb-3" accept="image/*" style="display:none;">

    <button class="btn btn-primary w-100" style="background-color:#00A098" id="submitBtn">Register</button>
  </form>
</div>

<script>
function toggleFields() {
    var role = document.getElementById('roleSelect').value;
    document.getElementById('companyField').style.display = (role === 'employer' || role === 'training_center') ? 'block' : 'none';
    document.getElementById('designationField').style.display = (role === 'employer') ? 'block' : 'none';
    document.getElementById('imageField').style.display = (role === 'training_center') ? 'block' : 'none';
}
toggleFields();
</script>

<?php include 'portal_footer.php'; ?>
