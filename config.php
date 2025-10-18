<?php
$mysqli = new mysqli("localhost", "root", "", "job_portal");
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}
session_start();

function is_logged_in() { return isset($_SESSION['user_id']); }
function require_login() { if(!is_logged_in()){ header("Location: login.php"); exit; } }

function current_user() {
    global $mysqli;
    if(!is_logged_in()) return null;
    $id = $_SESSION['user_id'];
    $res = $mysqli->query("SELECT * FROM users WHERE id=$id");
    return $res->fetch_assoc();
}

function is_admin(){ $u=current_user(); return $u && $u['role']==='admin'; }
function is_employer(){ $u=current_user(); return $u && $u['role']==='employer'; }
function is_training_center(){ $u=current_user(); return $u && $u['role']==='training_center'; }
function is_jobseeker() {
    $u = current_user();
    return $u && $u['role'] === 'jobseeker';
}
?>
