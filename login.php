<?php 
include 'portal_header.php';

if (is_logged_in()) {
    // If already logged in, redirect to correct place
    $u = current_user();
    if ($u['role'] === 'admin') header('Location: admin_panel.php');
    else header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Use prepared statements for security
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $u = $result->fetch_assoc();
    $stmt->close();

    if ($u && password_verify($password, $u['password'])) {
        if ($u['status'] !== 'active') {
            $error = '⏳ Your account is pending approval by admin.';
        } else {
            // Set session and redirect based on role
            $_SESSION['user_id'] = $u['id'];

            if ($u['role'] === 'admin') {
                header('Location: admin_panel.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        }
    } else {
        $error = '❌ Invalid email or password.';
    }
}
?>

  <div class="col-md-6 mx-auto mt-5">
  <h3 class="mb-3">Login</h3>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <input name="email" type="email" class="form-control mb-2" placeholder="Email" required>
    <input name="password" type="password" class="form-control mb-3" placeholder="Password" required>

    <!-- Forgot Password link on left with spacing -->
    <div class="mb-3">
      <a href="forgot_password.php" class="text-decoration-none" style="font-size: 0.9rem;">Forgot Password?</a>
    </div>

    <button class="btn btn-primary w-100" style="background-color:#00A098">Login</button>
  </form>
</div>


<?php include 'portal_footer.php'; ?>
