<?php
include "config.php"; // DB connection
$user_id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'view'; // default action = view

// Get CV filename from DB
$result = $mysqli->query("SELECT cv FROM users WHERE id = $user_id");
$cv_data = $result ? $result->fetch_assoc() : null;

if (!$cv_data || empty($cv_data['cv'])) {
    die("CV not uploaded.");
}

$file_path = __DIR__ . "/" . $cv_data['cv'];

if (!file_exists($file_path)) {
    die("File not found on server.");
}

// Determine MIME type
$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$mime = ($ext === 'pdf') ? 'application/pdf' :
        (($ext === 'docx') ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/octet-stream');

// Headers
if ($action === 'download') {
    // Force download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
} else {
    // View in browser
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));
}

// Output file
readfile($file_path);
exit;
