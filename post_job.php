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
    $category = $_POST['category']; // New category field
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

    if(!$error && $title && $description && $category){
        $stmt = $mysqli->prepare("
            INSERT INTO jobs (employer_id, title, description, salary, country, category, image, status)
            VALUES (?,?,?,?,?,?,?, 'pending')
        ");
        $stmt->bind_param("issssss", $uid, $title, $description, $salary, $country, $category, $image_path);
        if($stmt->execute()){
            $success = "✅ Job posted successfully (pending admin approval).";

            // Notify admin via email
            require 'vendor/autoload.php'; // PHPMailer

            $admin_email = "pantamanoj08@gmail.com"; 
            $employer_stmt = $mysqli->prepare("SELECT name,email,company FROM users WHERE id=?");
            $employer_stmt->bind_param("i", $uid);
            $employer_stmt->execute();
            $employer = $employer_stmt->get_result()->fetch_assoc();
            $employer_stmt->close();

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
                $mail->addAddress($admin_email, 'Admin');
                $mail->isHTML(true);
                $mail->Subject = 'New Job Posted - Admin Approval Needed';
                $mail->Body = "
                    <p>Dear Admin,</p>
                    <p>A new job has been posted and is pending your approval:</p>
                    <ul>
                        <li><strong>Title:</strong> {$title}</li>
                        <li><strong>Employer:</strong> {$employer['name']} ({$employer['company']})</li>
                        <li><strong>Category:</strong> {$category}</li>
                        <li><strong>Country:</strong> {$country}</li>
                        <li><strong>Salary:</strong> {$salary}</li>
                    </ul>
                    <p>Please log in to the admin panel to review and approve the job posting.</p>
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
    } elseif(!$title || !$description || !$category) {
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
        <option value="DUBAI">Dubai</option>
        <option value="KUWAIT">Kuwait</option>
        <option value="SAUDI_ARABIA">Saudi Arabia</option>
      </select>
    </div>

    <div class="mb-2">
      <label for="category">Category</label>
      <select name="category" class="form-select" required>
        <option value="">Select Category</option>
        <option value="Service">Service</option>
        <option value="Manufacture">Manufacture</option>
        <option value="IT">IT</option>
        <option value="Construction">Construction</option>
        <option value="Education">Education</option>
        <!-- add more categories as needed -->
      </select>
    </div>



    <button class="btn btn-primary w-100">Submit</button>
  </form>
</div>

<?php include 'portal_footer.php'; ?>
