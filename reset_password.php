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
        $message = "✅ Your password has been updated successfully! You will be redirected to the login page shortly.";

echo "<script>
    setTimeout(function() {
        window.location.href = 'login.php';
    }, 3000); // 3 seconds
</script>";

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Job Portal</title>
    
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
        

        
        .reset-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .reset-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .reset-card:before {
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
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 30px;
            text-align: center;
            line-height: 1.5;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 25px;
            padding: 15px;
            background: linear-gradient(135deg, rgba(0, 160, 152, 0.1), rgba(102, 126, 234, 0.1));
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
        }
        
        .user-info p {
            margin: 5px 0;
            color: #555;
            font-size: 0.9rem;
        }
        
        .user-info strong {
            color: var(--primary-color);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: center;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.2);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.2);
        }
        
        .alert-success a {
            color: white;
            text-decoration: underline;
            font-weight: 600;
        }
        
        .alert-success a:hover {
            color: #e6ffe6;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            color: #333;
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
        
        .reset-btn {
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
        }
        
        .reset-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), #006B65);
            box-shadow: 0 8px 20px rgba(0, 160, 152, 0.3);
            transform: translateY(-2px);
        }
        
        .reset-btn:active {
            transform: translateY(0);
        }
        
        .reset-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s ease;
        }
        
        .reset-btn:hover:before {
            left: 100%;
        }
        
        .links-container {
            margin-top: 25px;
            text-align: center;
        }
        
        .link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .link i {
            font-size: 14px;
        }
        
        .info-box {
            background: linear-gradient(135deg, rgba(0, 160, 152, 0.1), rgba(102, 126, 234, 0.1));
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-box p {
            color: #555;
            font-size: 0.9rem;
            margin: 0;
            line-height: 1.5;
        }
        
        .info-box i {
            color: var(--primary-color);
            margin-right: 8px;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .strength-text {
            color: #666;
        }
        
        .strength-bar {
            flex: 1;
            height: 4px;
            background: #e1e5eb;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            background: var(--accent-color);
            transition: all 0.3s ease;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .reset-card {
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
                padding: 14px 15px;
            }
        }
        
        @media (max-width: 480px) {
            .reset-card {
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
            
            .subtitle {
                font-size: 0.9rem;
            }
        }
        
        /* Loading state */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            vertical-align: middle;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h3>Create New Password</h3>
                <p class="subtitle">Enter your new password below. Make sure it's strong and secure.</p>
            </div>
            
            
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?= $message ?>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <p><i class="fas fa-info-circle"></i> Password must be at least 6 characters long</p>
                </div>
                
                <form method="post" id="resetForm">
                    <div class="form-group">
                        <input 
                            name="password" 
                            type="password" 
                            class="form-control" 
                            placeholder="Enter new password" 
                            required
                            id="password"
                        >
                        <div class="form-control-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="password-strength">
                            <span class="strength-text">Strength:</span>
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <input 
                            name="confirm_password" 
                            type="password" 
                            class="form-control" 
                            placeholder="Confirm new password" 
                            required
                            id="confirmPassword"
                        >
                        <div class="form-control-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="reset-btn" id="resetBtn">
                        Update Password
                    </button>
                    
                    <div class="links-container">
                        <a href="login.php" class="link">
                            <i class="fas fa-arrow-left"></i>
                            Back to Login
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const resetBtn = document.getElementById('resetBtn');
        const resetForm = document.getElementById('resetForm');
        
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            
            return Math.min(strength, 100);
        }
        
        function updateStrengthIndicator() {
            const password = passwordInput.value;
            const strength = checkPasswordStrength(password);
            
            strengthFill.style.width = strength + '%';
            
            // Change color based on strength
            if (strength < 50) {
                strengthFill.style.background = 'var(--accent-color)'; // Red
            } else if (strength < 75) {
                strengthFill.style.background = '#ffb347'; // Orange
            } else {
                strengthFill.style.background = '#10b981'; // Green
            }
        }
        
        passwordInput.addEventListener('input', updateStrengthIndicator);
        
        // Form submission with loading animation
        resetForm.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
            
            // Add loading animation
            resetBtn.disabled = true;
            resetBtn.innerHTML = '<span class="loading"></span> Updating...';
            resetBtn.style.opacity = '0.8';
            
            // Allow form to submit normally
            return true;
        });
        
        // Auto-remove loading state if page reloads
        window.addEventListener('pageshow', function() {
            if (resetBtn) {
                resetBtn.disabled = false;
                resetBtn.innerHTML = 'Update Password';
                resetBtn.style.opacity = '1';
            }
        });
        
        // Add input focus effects
        const inputs = document.querySelectorAll('.form-control');
        
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
        
        // Check password match in real-time
        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.style.borderColor = 'var(--accent-color)';
                this.style.boxShadow = '0 0 0 3px rgba(255, 107, 107, 0.2)';
            } else {
                this.style.borderColor = '#e1e5eb';
                this.style.boxShadow = 'none';
            }
        });
        
        // Initial strength indicator update
        updateStrengthIndicator();
    </script>
</body>
</html>

<?php include 'portal_footer.php'; ?>