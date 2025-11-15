<?php 
include 'portal_header.php';

if (is_logged_in()) {
    $u = current_user();
    if ($u['role'] === 'admin') header('Location: admin_panel.php');
    else header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

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
    <!-- Keep email value after login error -->
    <input 
        name="email" 
        type="email" 
        class="form-control mb-2" 
        placeholder="Email" 
        required
        value="<?= htmlspecialchars($email ?? '') ?>"
    >

    <!-- Password field with eye toggle -->
    <div style="position: relative;">
        <input 
            name="password" 
            id="password" 
            type="password" 
            class="form-control mb-3" 
            placeholder="Password" 
            required
        >
        <span id="togglePassword" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; color:black;">
            <i class="fa-solid fa-eye" id="eyeIcon"></i>
        </span>
    </div>

    <div class="mb-3">
      <a href="forgot_password.php" class="text-decoration-none" style="font-size: 0.9rem;">Forgot Password?</a>
    </div>

    <button class="btn btn-primary w-100" style="background-color:#00A098">Login</button>
</form>

</div>
<!-- Include Font Awesome if not already included -->
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
/>
<script>
// Password toggle


  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password'); // your password input
  const eyeIcon = document.getElementById('eyeIcon');

  togglePassword.addEventListener('click', () => {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);

    // Toggle icon
    eyeIcon.classList.toggle('fa-eye');
    eyeIcon.classList.toggle('fa-eye-slash');
  });

</script>

<?php include 'portal_footer.php'; ?>
