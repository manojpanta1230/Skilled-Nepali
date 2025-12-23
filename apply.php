<?php
include 'portal_header.php';
require 'vendor/autoload.php'; // PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure session started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow jobseekers
if (!is_logged_in() || !is_jobseeker()) {
    echo '<div class="alert alert-warning text-center mt-5">
            ⚠️ Only jobseekers can apply for jobs. Please login as a jobseeker.
          </div>';
    include 'portal_footer.php';
    exit;
}

// Check if job ID is provided
if (empty($_GET['job_id'])) {
    echo '<div class="alert alert-danger text-center mt-5">Invalid job selected.</div>';
    include 'portal_footer.php';
    exit;
}

$job_id = intval($_GET['job_id']);
$job = $mysqli->query("SELECT * FROM jobs WHERE id=$job_id AND status='approved'")->fetch_assoc();

if (!$job) {
    echo '<div class="alert alert-danger text-center mt-5">Job not found or not available.</div>';
    include 'portal_footer.php';
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $mysqli->real_escape_string(trim($_POST['name']));
    $email   = $mysqli->real_escape_string(trim($_POST['email']));
$country_code = $mysqli->real_escape_string($_POST['country_code']);
$phone_number = $mysqli->real_escape_string(trim($_POST['phone']));
$phone = $country_code . ' ' . $phone_number;
    $address = $mysqli->real_escape_string(trim($_POST['address']));
    $notes   = $mysqli->real_escape_string(trim($_POST['notes'] ?? ''));

    // Ensure upload directories exist
    $resume_dir = 'uploads/resumes/';
    $photo_dir  = 'uploads/photos/';
    if (!is_dir($resume_dir)) mkdir($resume_dir, 0755, true);
    if (!is_dir($photo_dir)) mkdir($photo_dir, 0755, true);

    // Resume upload
    $resume_path = null;
    if (!empty($_FILES['resume']['name'])) {
        $resume_ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx'];
        if (in_array($resume_ext, $allowed)) {
            $resume_filename = uniqid('resume_') . '.' . $resume_ext;
            $resume_path = $resume_dir . $resume_filename;
            move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path);
        }
    }

    // Photo upload (optional)
    $photo_path = null;
    if (!empty($_FILES['photo']['name'])) {
        $photo_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_photo = ['jpg', 'jpeg', 'png'];
        if (in_array($photo_ext, $allowed_photo)) {
            $photo_filename = uniqid('photo_') . '.' . $photo_ext;
            $photo_path = $photo_dir . $photo_filename;
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
        }
    }

    // Insert application including address
    $stmt = $mysqli->prepare("
        INSERT INTO applications 
        (job_id, user_id, name, email, phone, address, resume, photo, notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iisssssss", $job_id, $_SESSION['user_id'], $name, $email, $phone, $address, $resume_path, $photo_path, $notes);

    if ($stmt->execute()) {
        echo '<div class="alert alert-success text-center mt-4">
                ✅ Your application has been submitted successfully!<br>
                ' . ($resume_path ? '<a href="'.$resume_path.'" target="_blank">View Uploaded Resume</a>' : '') . '<br>
                ' . ($photo_path ? '<a href="'.$photo_path.'" target="_blank">View Uploaded Photo</a>' : '') . '
              </div>';

        // --- Send Email Notification to Employer & Admin ---
        try {
            // Fetch employer email
            $emp_stmt = $mysqli->prepare("SELECT email, company FROM users WHERE id = ?");
            $emp_stmt->bind_param("i", $job['employer_id']);
            $emp_stmt->execute();
            $employer = $emp_stmt->get_result()->fetch_assoc();
            $emp_stmt->close();

            $admin_email = ""; // admin email

            $mail = new PHPMailer(true);
          $mail->isSMTP();
$mail->Host = 'skillednepali.com';
$mail->SMTPAuth = true;
$mail->Username = 'inquiry@skillednepali.com';
$mail->Password = 'adgjl@900';
$mail->SMTPSecure = 'ssl';
$mail->Port = 465;

$mail->setFrom('inquiry@skillednepali.com', 'Job Portal of Skilled Nepali');
$mail->addAddress($employer['email'], $employer['company']); // employer
$mail->addAddress($admin_email, 'Admin'); // admin


            $mail->isHTML(true);
            $mail->Subject = "New Job Application Received";
            $mail->Body = "
                <p>Dear {$employer['company']},</p>
                <p>A new application has been submitted for the job: <b>{$job['title']}</b>.</p>
                <p><b>Applicant Details:</b></p>
                <ul>
                    <li>Name: {$name}</li>
                    <li>Email: {$email}</li>
                    <li>Phone: {$phone}</li>
                    <li>Address: {$address}</li>
                </ul>
                " . ($notes ? "<p><b>Notes / Cover Letter:</b> {$notes}</p>" : "") . "
                <p>Please log in to your account to review the application.</p>
                <p>Best regards,<br>Job Portal System</p>
            ";

            $mail->send();

        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
        }

    } else {
        echo '<div class="alert alert-danger text-center mt-4">
                ❌ Failed to submit application. Please try again later.
              </div>';
    }
}
?>

<div class="container mt-5 mb-5">
    <h3 class="text-center text-primary mb-4">Apply for: <?= htmlspecialchars($job['title']) ?></h3>

    <form method="POST" enctype="multipart/form-data" class="p-4 shadow-lg bg-white rounded-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
            </div>
    <div class="col-md-6">
    <label class="form-label">Phone Number</label>
    <div class="input-group">
        <select name="country_code" class="form-select" style="max-width: 130px;" required>
            <option value="+977">🇳🇵 Nepal (+977)</option>
            <option value="+966">🇸🇦 Saudi Arabia (+966)</option>
            <option value="+971">🇦🇪 UAE (+971)</option>
            <option value="+974">🇶🇦 Qatar (+974)</option>
            <option value="+965">🇰🇼 Kuwait (+965)</option>
            <option value="+968">🇴🇲 Oman (+968)</option>
            <option value="+973">🇧🇭 Bahrain (+973)</option>
        </select>
        <input 
            type="text" 
            name="phone" 
            class="form-control" 
            placeholder="Enter phone number"
            required
        >
    </div>
</div>

            <div class="col-md-6">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Upload Resume (PDF, DOC, DOCX)</label>
                <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Upload Photo (optional)</label>
                <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png">
            </div>
            <div class="col-12">
                <label class="form-label">Notes / Cover Letter (optional)</label>
                <textarea name="notes" rows="4" class="form-control"></textarea>
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-success mt-3 w-50">
                    <i class="bi bi-send-fill"></i> Submit Application
                </button>
            </div>
        </div>
    </form>
</div>

<style>
form { max-width: 800px; margin: auto; }
input, textarea { border-radius: 10px; }
</style>

<?php include 'portal_footer.php'; ?>
