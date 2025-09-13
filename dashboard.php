<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require "db_connect.php";
// Count total messages
$msg_result = $conn->query("SELECT COUNT(*) AS total_messages FROM messages");
$total_messages = $msg_result->fetch_assoc()['total_messages'];

// Count unique apply criteria
$criteria_result = $conn->query("SELECT COUNT(DISTINCT apply) AS total_criteria FROM messages");
$total_criteria = $criteria_result->fetch_assoc()['total_criteria'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- important for mobile -->
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: #00A098;
        }
        .dashboard-card {
            border-radius: 15px;
            transition: transform 0.2s ease-in-out;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark px-3">
    <a class="navbar-brand fw-bold text-white" href="#">⚙️ Admin Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" 
        aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Collapsible links -->
    <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a href="messages.php" class="nav-link btn  text-light btn-sm me-2 my-2 my-lg-0 text-center">View Messages</a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link btn  text-light btn-sm my-2 my-lg-0 text-center">Logout</a>
            </li>
        </ul>
    </div>
</nav>


    <!-- Main Content -->
    <div class="container py-5">
        <h2 class="mb-4 text-center fw-bold">Welcome, Admin 🎉</h2>

        <div class="row g-4">
            <div class="col-12 col-md-6">
                <div class="card dashboard-card shadow-sm text-center p-4">
                    <h4>Total Messages</h4>
                    <p class="display-5 text-primary fw-bold"><?php echo $total_messages; ?></p>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card dashboard-card shadow-sm text-center p-4">
                    <h4>Total Criteria</h4>
                    <p class="display-5 text-success fw-bold"><?php echo $total_criteria; ?></p>
                </div>
            </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


