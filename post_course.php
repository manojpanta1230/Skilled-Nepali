<?php 
include 'portal_header.php'; 
require_login(); 

if(!is_training_center()) die("<div class='alert alert-danger'>Not allowed.</div>");

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $title = trim($_POST['title']); 
    $structure = trim($_POST['structure']); 
    $cost = trim($_POST['cost']);
    $uid = $_SESSION['user_id'];

    if(!$title || !$structure || !$cost){
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO courses (training_center_id,title,structure,cost,status) VALUES (?,?,?,?, 'pending')");
        $stmt->bind_param("isss",$uid,$title,$structure,$cost);

        if($stmt->execute()){
            $success = "✅ Course posted successfully! Awaiting admin approval.";

            // Send email notification to admin
            require 'vendor/autoload.php'; // PHPMailer

            $admin_email = "pantamanoj08@gmail.com"; // Replace with actual admin email

            $center_stmt = $mysqli->prepare("SELECT name,company,email FROM users WHERE id=?");
            $center_stmt->bind_param("i",$uid);
            $center_stmt->execute();
            $center = $center_stmt->get_result()->fetch_assoc();
            $center_stmt->close();

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'pantamanoj08@gmail.com'; // your email
                $mail->Password = 'qjms snqf uzjn pvdc';   // your app password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('pantamanoj08@gmail.com', 'Job Portal');
                $mail->addAddress($admin_email, 'Admin');
                $mail->isHTML(true);
                $mail->Subject = 'New Course Posted - Admin Approval Needed';
                $mail->Body = "
                    <p>Dear Admin,</p>
                    <p>A new course has been posted and is pending your approval:</p>
                    <ul>
                        <li><strong>Title:</strong> {$title}</li>
                        <li><strong>Training Center:</strong> {$center['name']} ({$center['company']})</li>
                        <li><strong>Cost:</strong> {$cost} USD</li>
                    </ul>
                    <p>Please log in to the admin panel to review and approve this course.</p>
                    <p>Best regards,<br>Job Portal System</p>
                ";
                $mail->send();
            } catch (Exception $e) {
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }

        } else {
            $error = "Database error: ".$mysqli->error;
        }
        $stmt->close();
    }
}
?>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card shadow-lg rounded-4 border-0">
        <div class="card-body p-4">
          <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>
          <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
          <?php endif; ?>

          <p class="text-muted text-center mb-4">
            Share details about your new course offering. Once submitted, it will be reviewed by the admin team before going live.
          </p>
          <form method="post">
            <div class="mb-3">
              <label class="form-label fw-semibold">Course Title</label>
              <input name="title" class="form-control" placeholder="e.g., Advanced Web Development" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Course Structure</label>
              <textarea name="structure" class="form-control" rows="5" placeholder="Outline the main topics, modules, or skills covered..." required></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Cost (in USD)</label>
              <input name="cost" type="number" min="0" step="0.01" class="form-control" placeholder="e.g., 199.99" required>
            </div>

            <div class="d-grid">
              <button class="btn btn-primary btn-lg rounded-pill">Submit Course</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'portal_footer.php'; ?>
