<?php
session_start();
require "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $row['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Invalid username.";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Responsive Meta -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .card {
            border-radius: 20px;
            overflow: hidden;
            animation: fadeInUp 1s ease;
            width: 100%;
        }
        .card-body {
            padding: 2rem;
        }
        h3 {
            font-weight: bold;
            color: #203a43;
        }
        .btn-primary {
            background: linear-gradient(90deg, #0072ff, #00c6ff);
            border: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 114, 255, 0.3);
        }
        .btn-outline-secondary {
            transition: background 0.3s ease, color 0.3s ease;
        }
        .btn-outline-secondary:hover {
            background: #203a43;
            color: #fff;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
        }
        .alert {
            border-radius: 10px;
            animation: shakeX 0.7s;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Make card scale well on all devices */
        @media (min-width: 576px) {
            .login-card { max-width: 500px; }
        }
    </style>
</head>
<body>
    <div class="login-card w-100">
        <div class="card shadow-lg animate__animated animate__fadeInDown mx-auto">
            <div class="card-body">
                <h3 class="text-center mb-4">Admin Login</h3>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>

                <!-- Back to Home Button -->
                <div class="text-center mt-3">
                    <a href="index.html" class="btn btn-outline-secondary w-100">Back to Home</a>
                </div>
            </div>
        </div>
        <p class="text-center text-light mt-3 animate__animated animate__fadeInUp">&copy; <?= date("Y") ?> Admin Panel</p>
    </div>
</body>
</html>
