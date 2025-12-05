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

            /**************************************
             *  GENERATE NEW RESET TOKEN ALWAYS
             **************************************/
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // valid for 1 hour

            // Update token in database
            $stmt = $mysqli->prepare(
                "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?"
            );
            $stmt->bind_param("ssi", $token, $expires, $user['id']);
            $stmt->execute();
            $stmt->close();

            // Reset link
            $resetLink = "https://skillednepali.com/reset_password.php?token=$token";

            // Send email via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'skillednepali.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'inquiry@skillednepali.com';
                $mail->Password = 'adgjl@900';
                $mail->SMTPSecure = 'ssl';
                $mail->Port = 465;

                $mail->setFrom('inquiry@skillednepali.com', 'Job Portal');
                $mail->addAddress($email, $user['name']);

                $mail->isHTML(false);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "Hi " . $user['name'] . ",\n\n"
                    . "Click the link below to reset your password:\n"
                    . "$resetLink\n\n"
                    . "This link expires in 1 hour.\n\n"
                    . "If you did not request this, please ignore this email.";

                $mail->send();
                $message = "✅ If this email exists, a password reset link has been sent.";
            } catch (Exception $e) {
                $error = "❌ Email could not be sent. Error: {$mail->ErrorInfo}";
            }

        } else {
            // Fake success to avoid email enumeration
            $message = "✅ If this email exists, a password reset link has been sent.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Job Portal</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --primary-color: #00A098;
            --primary-dark: #00857D;
            --secondary-color: #667eea;
            --accent-color: #ff6b6b;
            --text-color: #333;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .forgot-container {
            width: 100%;
            max-width: 450px;
            margin: 40px auto;
            padding: 20px;
        }

        .forgot-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
        }

        .logo-container {
            text-align: center;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            margin: 0 auto 15px;
            font-size: 28px;
        }

        h3 {
            text-align: center;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .alert {
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-danger {
            background: #ff6b6b;
            color: #fff;
        }

        .alert-success {
            background: #10b981;
            color: #fff;
        }

        .info-box {
            padding: 12px;
            background: #eef8f7;
            border-left: 4px solid var(--primary-color);
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-control {
            width: 100%;
            padding: 14px;
            border: 2px solid #e1e5eb;
            border-radius: 12px;
            margin-bottom: 10px;
        }

        .reset-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="forgot-container">
        <div class="forgot-card">

            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h3>Reset Password</h3>
                <p style="text-align:center;color:#666;font-size:14px;">
                    Enter your email address to receive a reset link.
                </p>
            </div>

            <div class="info-box">
                <p><i class="fas fa-info-circle"></i> The reset link will be valid for 1 hour.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="post" id="forgotForm">
                <input 
                    type="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="Enter your email" 
                    required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >

                <button type="submit" class="reset-btn" id="resetBtn">Send Reset Link</button>

                <div style="text-align:center;margin-top:15px;">
                    <a href="login.php" style="color:var(--primary-color);text-decoration:none;">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            </form>

        </div>
    </div>

    <script>
        const form = document.getElementById("forgotForm");
        const btn = document.getElementById("resetBtn");

        form.addEventListener("submit", function() {
            btn.disabled = true;
            btn.innerHTML = "<span class='loading'></span> Sending...";
        });
    </script>

</body>
</html>

<?php include 'portal_footer.php'; ?>
