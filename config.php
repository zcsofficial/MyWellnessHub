<?php
// Database configuration
define('DB_HOST', 'localhost');        // Database host (usually localhost for local dev)
define('DB_USER', 'adnan');             // MySQL username (default for XAMPP/WAMP)
define('DB_PASS', 'Adnan@66202');                 // MySQL password (default empty for XAMPP/WAMP)
define('DB_NAME', 'wellness_hub');     // Your database name

// Establish database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to avoid encoding issues
mysqli_set_charset($conn, "utf8");

?>