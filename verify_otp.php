<?php
require 'config.php'; // Database connection
require 'vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_otp = trim($_POST['otp']);

    if (!isset($_SESSION['otp']) || time() > $_SESSION['otp_expiry']) {
        unset($_SESSION['otp'], $_SESSION['otp_user_id']);
        $errors[] = "OTP expired. Please register again.";
    } else {
        if ($entered_otp == $_SESSION['otp']) {
            $userId = $_SESSION['otp_user_id'];

            // Activate account
            $stmt = $mysqli->prepare("UPDATE users SET status='active' WHERE id=?");
            $stmt->bind_param("i", $userId);

            if ($stmt->execute()) {
                // ✅ Fetch user info for email
                $user_stmt = $mysqli->prepare("SELECT name, email FROM users WHERE id=?");
                $user_stmt->bind_param("i", $userId);
                $user_stmt->execute();
                $user = $user_stmt->get_result()->fetch_assoc();
                $user_stmt->close();

                // ✅ Send Thank You Email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'pantamanoj08@gmail.com'; // Replace with your Gmail
                    $mail->Password = 'qjms snqf uzjn pvdc';    // Replace with your App Password
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('pantamanoj08@gmail.com', 'Job Portal Team');
                    $mail->addAddress($user['email'], $user['name']);
                    $mail->isHTML(true);
                    $mail->Subject = ' Welcome to Job Portal - Registration Successful!';
                    $mail->Body = "
                        <p>Dear <strong>{$user['name']}</strong>,</p>
                        <p>Thank you for registering with <b>Job Portal</b>!</p>
                        <p>Your account has been successfully verified. You can now log in and start exploring job opportunities and training programs tailored for you.</p>
                        <p><a href='https://skillednepali.com/login.php' 
                            style='display:inline-block;background:#00A098;color:white;padding:10px 20px;text-decoration:none;border-radius:8px;margin-top:10px;'>Login Now</a></p>
                        <p>Find your dream job and get the best training to boost your career!</p>
                        <br>
                        <p>Best regards,<br><b>Job Portal Team</b></p>
                    ";
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Mailer Error: " . $mail->ErrorInfo);
                }

                // ✅ Success message & redirect
                $success = " OTP verified successfully! Your account is now active. Redirecting to login...";
                unset($_SESSION['otp'], $_SESSION['otp_user_id'], $_SESSION['otp_expiry']);
                header("refresh:4;url=login.php");
            } else {
                $errors[] = "Database error: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $errors[] = " Invalid OTP. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OTP Verification</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background:white;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
}

.card {
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    padding: 2rem;
    max-width: 400px;
    width: 100%;
    background-color: #fff;
    text-align: center;
    animation: fadeIn 1s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px);}
    to { opacity: 1; transform: translateY(0);}
}

.card h3 {
    margin-bottom: 1.5rem;
    color: #00A098;
    font-weight: 700;
}

input[name="otp"] {
    text-align: center;
    font-size: 1.5rem;
    letter-spacing: 5px;
    padding: 0.8rem;
    border-radius: 10px;
    border: 1px solid #00A098;
    transition: all 0.3s ease;
}

input[name="otp"]:focus {
    border-color: #FF7A00;
    box-shadow: 0 0 10px rgba(255, 122, 0, 0.5);
    outline: none;
}

.btn-primary {
    background-color: #00A098;
    border: none;
    padding: 0.7rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: #FF7A00;
    box-shadow: 0 5px 15px rgba(255, 122, 0, 0.4);
}
.alert {
    border-radius: 10px;
}
</style>
</head>
<body>

<div class="card">
    <h3>OTP Verification</h3>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php else: ?>
        <form method="post">
            <input type="text" name="otp" maxlength="6" class="form-control mb-3" placeholder="Enter OTP" required>
            <button type="submit" class="btn btn-primary w-100">Verify OTP</button>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
