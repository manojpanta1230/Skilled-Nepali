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
$showOtpModal = false; // Flag to trigger OTP modal

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ===============================
    // Handle OTP verification first
    // ===============================
    if (isset($_POST['verify_otp'])) {
        $entered_otp = trim($_POST['otp']);

        if (!isset($_SESSION['otp']) || time() > $_SESSION['otp_expiry']) {
            unset($_SESSION['otp'], $_SESSION['otp_user_id']);
            $errors[] = "OTP expired. Please register again.";
        } else {
            if ($entered_otp == $_SESSION['otp']) {
                $userId = $_SESSION['otp_user_id'];
                $stmt = $mysqli->prepare("UPDATE users SET status='active' WHERE id=?");
                $stmt->bind_param("i", $userId);
                if ($stmt->execute()) {
                    $success = "OTP verified successfully! Your account is now active. Redirecting...";
                    unset($_SESSION['otp'], $_SESSION['otp_user_id'], $_SESSION['otp_expiry']);
                    header("refresh:4;url=login.php");
                } else {
                    $errors[] = "Database error: " . $mysqli->error;
                    $showOtpModal = true;
                }
                $stmt->close();
            } else {
                $errors[] = "Invalid OTP. Try again.";
                $showOtpModal = true;
            }
        }
    } 
    // ===============================
    // Handle Registration
    // ===============================
    else if (isset($_POST['register'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'];
        $company = trim($_POST['company'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $imagePath = null;

        // Jobseeker fields
        $application_for = $_POST['application_for'] ?? '';
        $experience_years = intval($_POST['experience_years'] ?? 0);
        $past_experience = trim($_POST['past_experience'] ?? '');
        $applicant_type = trim($_POST['applicant_type'] ?? '');

        // Basic validation
        if (empty($name) || empty($email) || empty($password)) $errors[] = 'All fields are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
        if (!in_array($role, ['jobseeker', 'employer', 'training_center'])) $errors[] = 'Invalid role selection.';

        // Role-specific validation
        if (($role === 'employer' || $role === 'training_center') && empty($company)) $errors[] = ($role === 'employer') ? "Company name is required for employers." : "Training Center name is required.";
        if ($role === 'employer' && empty($designation)) $errors[] = "Designation is required for employers.";

        // Image upload for employer and training center
        if (in_array($role, ['employer', 'training_center']) && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                $errors[] = "Only JPG, PNG, or GIF images are allowed.";
            } else {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $newName = uniqid($role . '_', true) . "." . $ext;
                $uploadDir = 'uploads/' . $role . 's/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                $imagePath = $uploadDir . $newName;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                    $errors[] = "Failed to upload image.";
                }
            }
        }

        // Jobseeker validation
        if ($role === 'jobseeker') {
            if (empty($application_for)) $errors[] = "Please select application type.";
            if (empty($applicant_type)) $errors[] = "Please select applicant type.";
            if ($experience_years < 0) $errors[] = "Years of experience must be a valid number.";
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

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($role === 'jobseeker') {
                // Insert Jobseeker
                $stmt = $mysqli->prepare("
                    INSERT INTO users 
                    (name,email,password,role,status,application_for,experience_years,past_experience,applicant_type) 
                    VALUES (?,?,?,?, 'otp_pending',?,?,?,?)
                ");
                $stmt->bind_param(
                    "ssssisss",
                    $name, $email, $hash, $role, $application_for, $experience_years, $past_experience, $applicant_type
                );

                if ($stmt->execute()) {
                    $userId = $stmt->insert_id;
                    $_SESSION['otp_user_id'] = $userId;
                    $otp = rand(100000, 999999);
                    $_SESSION['otp'] = $otp;
                    $_SESSION['otp_expiry'] = time() + 300; // 5 mins
                    $showOtpModal = true;

                    // Send OTP email
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'skillednepali.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'inquiry@skillednepali.com';
                        $mail->Password = 'adgjl@900';
                        $mail->SMTPSecure = 'ssl';
                        $mail->Port = 465;

                        $mail->setFrom('inquiry@skillednepali.com', 'Job Portal of Skilled Nepali');
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = 'Your OTP Code';
                        $mail->Body = "Hi $name,<br>Your OTP code is <b>$otp</b> (valid for 5 minutes).";

                        $mail->send();
                    } catch (Exception $e) {
                        $errors[] = "Mailer error: " . $mail->ErrorInfo;
                    }
                } else {
                    $errors[] = "Database error: " . $mysqli->error;
                }
                $stmt->close();
            } else {
                // Employer / Training Center
                $stmt = $mysqli->prepare("
                    INSERT INTO users 
                    (name,email,password,role,company,designation,image,status) 
                    VALUES (?,?,?,?,?,?,?, 'pending')
                ");
                $stmt->bind_param("sssssss", $name, $email, $hash, $role, $company, $designation, $imagePath);

                if ($stmt->execute()) {
                    $success = "✅ Registration successful! Wait for admin approval before logging in.";
                    $_POST = [];
                }
                $stmt->close();
            }
        }
    }
}
?>

<div class="col-md-6 mx-auto">
    <h3 class="mb-3 text-center">Register</h3>

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
        <input name="password" type="password" class="form-control mb-2" placeholder="Password" required>

        <select name="role" id="roleSelect" class="form-control mb-2" onchange="toggleFields()" required>
            <option value="" disabled <?= empty($_POST['role']) ? 'selected' : '' ?>>Select your role</option>
            <option value="jobseeker" <?= (($_POST['role'] ?? '') === 'jobseeker') ? 'selected' : '' ?>>Jobseeker</option>
            <option value="employer" <?= (($_POST['role'] ?? '') === 'employer') ? 'selected' : '' ?>>Employer</option>
            <option value="training_center" <?= (($_POST['role'] ?? '') === 'training_center') ? 'selected' : '' ?>>Training Center</option>
        </select>

        <!-- Employer Fields -->
        <input name="company" id="companyField" class="form-control mb-2" placeholder="Company / Center Name" value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
        <input name="designation" id="designationField" class="form-control mb-2" placeholder="Designation" value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>">
        <label for="imageField" class="btn btn-primary mb-3" id="imageLabel" style="display:none;">Upload Company Logo</label>
        <input type="file" name="image" id="imageField" accept="image/*" style="display:none;">

        <!-- Jobseeker Fields -->
        <div id="jobseekerFields" style="display:none;">
            <select name="application_for" class="form-control mb-2">
                <option value="">Select Job Category</option>
                <option value="IT Service" <?= (($_POST['application_for'] ?? '') === 'IT Service') ? 'selected' : '' ?>>IT Service</option>
                <option value="Manufacturing" <?= (($_POST['application_for'] ?? '') === 'Manufacturing') ? 'selected' : '' ?>>Manufacturing</option>
                <option value="Hospitality" <?= (($_POST['application_for'] ?? '') === 'Hospitality') ? 'selected' : '' ?>>Hospitality</option>
                <option value="Construction" <?= (($_POST['application_for'] ?? '') === 'Construction') ? 'selected' : '' ?>>Construction</option>
            </select>
 <div class="mb-3" id="applicantTypeField">
                <select name="applicant_type" class="form-control">
                    <option value="">Select Applicant Type</option>
                    <option value="Fresh Graduate / Entry Level">Fresh Graduate / Entry Level</option>
                    <option value="Nepal-Based Experienced">Nepal-Based Experienced</option>
                    <option value="GCC-Returned">GCC-Returned</option>
                    <option value="Currently Working in GCC (Transferable)">Currently Working in GCC (Transferable)</option>
                    <option value="Skilled & Certified Professional">Skilled & Certified Professional</option>
                    <option value="Semi-Skilled (Blue Collar)">Semi-Skilled (Blue Collar)</option>
                    <option value="White Collar (Admin/Office)">White Collar (Admin/Office)</option>
                    <option value="Overseas Ready (Passport/Police Clearance)">Overseas Ready (Passport/Police Clearance)</option>
                    <option value="SkilledNepali Training Completed">SkilledNepali Training Completed</option>
                    <option value="Short-Term / Part-Time">Short-Term / Part-Time</option>
                </select>
            </div>
            <input name="experience_years" type="number" min="0" class="form-control mb-2" placeholder="Years of Experience" value="<?= htmlspecialchars($_POST['experience_years'] ?? '') ?>">
            <textarea name="past_experience" class="form-control mb-3" placeholder="Past Experience"><?= htmlspecialchars($_POST['past_experience'] ?? '') ?></textarea>
        </div>

        <button class="btn btn-primary w-100" name="register" style="background-color:#00A098">Register</button>
    </form>
</div>

<!-- OTP Modal -->
<div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title" id="otpModalLabel">OTP Verification</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-center mb-2">OTP sending in  <span id="otpTimer">01:00</span></p>
        <form method="post" id="otpForm">
          <input type="text" name="otp" class="form-control mb-3 text-center" maxlength="6" placeholder="Enter OTP" required>
          <button type="submit" class="btn btn-success w-100" name="verify_otp" id="verifyBtn">Verify OTP</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
function toggleFields() {
    var role = document.getElementById('roleSelect').value;
    document.getElementById('companyField').style.display = (role === 'employer' || role === 'training_center') ? 'block' : 'none';
    document.getElementById('designationField').style.display = (role === 'employer') ? 'block' : 'none';
    document.getElementById('imageLabel').style.display = (role === 'employer' || role === 'training_center') ? 'inline-block' : 'none';
    document.getElementById('jobseekerFields').style.display = (role === 'jobseeker') ? 'block' : 'none';
}
toggleFields();

// Show OTP modal if registration for jobseeker was successful

<?php if ($showOtpModal): ?>
var otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
otpModal.show();

var timerDuration = 60; // 60 seconds
var display = document.getElementById('otpTimer');
var verifyBtn = document.getElementById('verifyBtn');

var otpCountdown = setInterval(function() {
    var minutes = Math.floor(timerDuration / 60);
    var seconds = timerDuration % 60;
    display.textContent = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
    timerDuration--;

    if (timerDuration < 0) {
        clearInterval(otpCountdown);
        display.textContent = '00:00';
        verifyBtn.disabled = false;
        display.textContent = 'OTP has sent. Please type correct OTP.';
         display.style.color = 'red';
    }
}, 1000);
<?php endif; ?>
</script>

<?php include 'portal_footer.php'; ?>
