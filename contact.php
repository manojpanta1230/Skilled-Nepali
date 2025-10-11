<?php
require 'db_connect.php';

// Get form data
$name    = $_POST['name'] ?? '';
$email   = $_POST['email'] ?? '';
$phone   = $_POST['phone'] ?? '';
$apply   = $_POST['apply'] ?? '';
$address = $_POST['address'] ?? '';
$message = $_POST['message'] ?? '';

// Initialize variables for toast
$toastType = '';
$toastMessage = '';

// Prepare & Bind
$stmt = $conn->prepare("INSERT INTO messages (name, email, phone, apply, address, message) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $name, $email, $phone, $apply, $address, $message);

// Execute
if ($stmt->execute()) {
    $toastType = 'bg-success';
    $toastMessage = 'Message sent successfully!';
} else {
    $toastType = 'bg-danger';
    $toastMessage = 'Error: ' . $stmt->error;
}

// Close
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Submission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Toast Container -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055">
    <div id="toast" class="toast align-items-center text-white <?php echo $toastType; ?> border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <?php echo $toastMessage; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Show toast on page load
    const toastEl = document.getElementById('toast');
    const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
    toast.show();

    // Redirect after toast disappears (optional, e.g., 5 seconds)
    setTimeout(() => {
        window.location.href = 'index.php';
    });
</script>

</body>
</html>
