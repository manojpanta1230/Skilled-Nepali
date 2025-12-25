<?php
require './vendor/autoload.php'; // dompdf autoload
include 'config.php';
require_login();
 
use Dompdf\Dompdf;

$user_id = intval($_GET['id'] ?? 0);

// Fetch user data
$result = $mysqli->query("SELECT name, email, past_experience, applicant_type, country FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();
if (!$user) die("User not found.");

// Prepare HTML content for PDF
$html = '
<h1 style="text-align:center;">Curriculum Vitae</h1>
<hr>
<h2>Personal Information</h2>
<p><strong>Name:</strong> '.htmlspecialchars($user['name']).'</p>
<p><strong>Email:</strong> '.htmlspecialchars($user['email']).'</p>
<p><strong>Country:</strong> '.htmlspecialchars($user['country']).'</p>
<hr>
<h2>Experience</h2>
<p>'.nl2br(htmlspecialchars($user['past_experience'])).'</p>
<hr>
<h2>Additional Info</h2>
<p><strong>Applicant Type:</strong> '.htmlspecialchars($user['applicant_type']).'</p>
';

// Initialize dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Ensure uploads folder exists
$uploadDir = __DIR__ . '/uploads/cvs/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Generate unique filename
$filename = 'cv_' . $user_id . '_' . time() . '.pdf';
$filepath = $uploadDir . $filename;

// Save PDF on server
file_put_contents($filepath, $dompdf->output());

// Store **relative path** in DB
$relativePath = 'uploads/cvs/' . $filename;
$stmt = $mysqli->prepare("UPDATE users SET cv = ? WHERE id = ?");
$stmt->bind_param("si", $relativePath, $user_id);
$stmt->execute();
$stmt->close();

// ---------------------------
// Force download to browser
// ---------------------------
if (file_exists($filepath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));
    flush(); // Flush system output buffer
    readfile($filepath);
    exit;
} else {
    echo "Error: File not found.";
}
