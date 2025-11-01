<?php
include 'portal_header.php';
require 'vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ Please enter a valid email address.";
    } else {
        // Check if user exists
        $stmt = $mysqli->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Store token in DB
            $stmt = $mysqli->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->bind_param("ssi", $token, $expires, $user['id']);
            $stmt->execute();
            $stmt->close();

            // Create reset link
            $resetLink = "http://localhost/visa-immigration-website-template/reset_password.php?token=$token"; // Change domain

            // Send email via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'pantamanoj08@gmail.com';
                $mail->Password = 'qjms snqf uzjn pvdc'; // your app password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('pantamanoj08@gmail.com', 'Job Portal');
                $mail->addAddress($email, $user['name']);

                $mail->isHTML(false);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "Hi ".$user['name'].",\n\nClick the link below to reset your password:\n$resetLink\n\nThe link expires in 1 hour.";

                $mail->send();
                $message = "✅ If this email exists, a password reset link has been sent.";
            } catch (Exception $e) {
                $error = "❌ Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }

        } else {
            $message = "✅ If this email exists, a password reset link has been sent.";
        }
    }
}
?>

<div class="col-md-6 mx-auto mt-5">
    <h3 class="mb-3">Forgot Password</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <form method="post">
        <input name="email" type="email" class="form-control mb-3" placeholder="Enter your email" required>
        <button class="btn btn-primary w-100" style="background-color:#00A098">Send Reset Link</button>
    </form>

    <div class="mt-3 text-start">
        <a href="login.php" class="text-decoration-none">&larr; Back to Login</a>
    </div>
</div>

<?php include 'portal_footer.php'; ?>
