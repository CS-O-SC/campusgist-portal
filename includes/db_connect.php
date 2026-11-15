<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$username = "root";
$password = ""; // Default XAMPP MySQL password is empty
$database = "campus_events_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Helper function to prevent Cross-Site Scripting (XSS)
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

// Helper function to check login state
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to enforce authentication on all portal pages
function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: login.php?auth=required");
        exit;
    }
}

// Helper function to check Organizer or Admin privilege
function isStaff() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['organizer', 'admin']);
}
?>