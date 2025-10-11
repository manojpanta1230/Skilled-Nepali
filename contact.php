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
    echo "<script>alert('Message sent successfully!'); window.location.href='index.html';</script>";
} else {
    echo "Error: " . $stmt->error;
}

// Close
$stmt->close();
$conn->close();
?>