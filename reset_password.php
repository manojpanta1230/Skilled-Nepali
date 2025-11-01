<?php
include 'portal_header.php';

$error = '';
$message = '';

// Get token from URL
$token = $_GET['token'] ?? '';

if (!$token) {
    echo "<div class='alert alert-danger mt-5'>Invalid or missing token.</div>";
    exit;
}

// Check if token exists and is not expired
$stmt = $mysqli->prepare("SELECT id, name, reset_expires FROM users WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || strtotime($user['reset_expires']) < time()) {
    echo "<div class='alert alert-danger mt-5'>This password reset link is invalid or has expired.</div>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = "❌ Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "❌ Passwords do not match.";
    } else {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Update database and clear token
        $stmt = $mysqli->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->bind_param("si", $password_hash, $user['id']);
        $stmt->execute();
        $stmt->close();

        $message = "✅ Your password has been updated successfully. You can now <a href='login.php'>login</a>.";
    }
}
?>

<div class="col-md-6 mx-auto mt-5">
    <h3 class="mb-3">Reset Password</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php else: ?>
        <form method="post">
            <input name="password" type="password" class="form-control mb-2" placeholder="New Password" required>
            <input name="confirm_password" type="password" class="form-control mb-3" placeholder="Confirm Password" required>
            <button class="btn btn-primary w-100" style="background-color:#00A098">Update Password</button>
        </form>
    <?php endif; ?>
</div>

<?php include 'portal_footer.php'; ?>
