<?php 
require_once 'config.php';

if (is_logged_in()) {
    $u = current_user();
    if ($u['role'] === 'admin') header('Location: admin_panel.php');
    elseif ($u['role'] === 'jobseeker') header('Location: jobseeker_panel.php');
    elseif ($u['role'] === 'training_center') header('Location: training_dashboard.php');
    exit;
}

$error = '';
$email = '';

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

            $_SESSION['user_id'] = $u['id'];
             $_SESSION['name']    = $u['name'];
             $_SESSION['role']    = $u['role'];

            if ($u['role'] === 'admin') {
                header('Location: admin_panel.php');
            }
            elseif ($u['role'] === 'jobseeker') {
                header('Location: jobseeker_panel.php');
            }
            elseif ($u['role'] === 'training_center') {
                header('Location: training_dashboard.php');
            }
            else {
                header('Location: employer_dashboard.php');
            }
            exit;
        }
    else {
        $error = '❌ Invalid email or password.';
    }
}
?>

<?php include 'portal_header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="img/Logo.png" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Job Portal</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #00A098;
            --primary-dark: #00857D;
            --secondary-color: #667eea;
            --accent-color: #ff6b6b;
            --text-color: #333;
            --light-bg: #f8f9fa;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
      
        
        .login-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .login-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 28px;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        h3 {
            color: var(--text-color);
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .subtitle {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.2);
        }
        
        .form-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 45px 15px 20px;
            border: 2px solid #e1e5eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 160, 152, 0.2);
            background: white;
            outline: none;
        }
        
        .form-control-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            transition: color 0.3s ease;
            z-index: 10;
            font-size: 18px;
        }
        
        .toggle-password:hover {
            color: var(--primary-color);
        }
        
        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), #006B65);
            box-shadow: 0 8px 20px rgba(0, 160, 152, 0.3);
            transform: translateY(-2px);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .links-container {
            margin-top: 25px;
            text-align: center;
        }
        
        .link {
            display: inline-block;
            margin: 0 10px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .link:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }
        
        .link:hover {
            color: var(--primary-dark);
        }
        
        .link:hover:after {
            width: 100%;
        }
        
        .register-link {
            color: var(--accent-color);
            font-weight: 600;
        }
        
        .register-link:hover {
            color: #ff5252;
        }
        
        .register-link:after {
            background: var(--accent-color);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #999;
            font-size: 14px;
        }
        
        .divider:before,
        .divider:after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e1e5eb;
        }
        
        .divider:before {
            margin-right: 15px;
        }
        
        .divider:after {
            margin-left: 15px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .login-card {
                padding: 30px 25px;
            }
            
            h3 {
                font-size: 1.6rem;
            }
            
            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .form-control {
                padding: 14px 40px 14px 15px;
            }
            
            .links-container {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .link {
                margin: 0;
            }
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 25px 20px;
            }
            
            h3 {
                font-size: 1.4rem;
            }
            
            .logo-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h3>Welcome Back</h3>
                <p class="subtitle">Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <input 
                        name="email" 
                        type="email" 
                        class="form-control" 
                        placeholder="Email address" 
                        required
                        value="<?= htmlspecialchars($email ?? '') ?>"
                    >
                    <div class="form-control-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="password-wrapper">
                        <input 
                            name="password" 
                            id="password" 
                            type="password" 
                            class="form-control" 
                            placeholder="Password" 
                            required
                        >
                        
                        <button type="button" class="toggle-password" id="togglePassword">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="login-btn">
                    Login
                </button>
                
                <div class="divider">or</div>
                
                <div class="links-container">
                    <a href="forgot_password.php" class="link">
                        <i class="fas fa-key me-1"></i> Forgot Password?
                    </a>
                    <a href="register.php" class="link register-link">
                        <i class="fas fa-user-plus me-1"></i> Don't have an account? Sign Up
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle eye icon
            if (type === 'password') {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            } else {
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            }
        });
    </script>
</body>
</html>

<?php include 'portal_footer.php'; ?>