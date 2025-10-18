<?php

require 'db_connect.php';

// Get form data
$name    = $_POST['name'];
$email   = $_POST['email'];
$phone   = $_POST['phone'];
$apply   = $_POST['apply'];
$address = $_POST['address'];
$message = $_POST['message'];

// Prepare & Bind
$stmt = $conn->prepare("INSERT INTO messages (name, email, phone, apply, address, message) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $name, $email, $phone, $apply, $address, $message);

// Execute
if ($stmt->execute()) {
    echo "
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css'>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js'></script>
    <script>
        toastr.success('Message sent successfully!');
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 2000);
    </script>";
} else {
    echo "
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css'>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js'></script>
    <script>
        toastr.error('Error: " . addslashes($stmt->error) . "');
    </script>";
}

// Close
$stmt->close();
$conn->close();
?>
