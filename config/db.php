<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'campusride_user';
$pass = getenv('DB_PASS') ?: 'CampusRide_Strong_2026!';
$name = getenv('DB_NAME') ?: 'campusride_db';

$conn = @mysqli_connect($host, $user, $pass, $name);

// Fallback to root with empty password if running in local dev environments like XAMPP
if (!$conn && $user !== 'root') {
    $conn = @mysqli_connect($host, 'root', '', $name);
}

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
