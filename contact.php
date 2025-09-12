<?php



$servername = "localhost"; 
$username   = "root";       // change if needed
$password   = "";           // change if needed
$dbname     = "skillednepali";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);

}


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
