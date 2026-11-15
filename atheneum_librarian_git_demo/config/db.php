<?php
$host = getenv("LIBRARY_DB_HOST") ?: "127.0.0.1";
$user = getenv("LIBRARY_DB_USER") ?: "library_user";
$password = getenv("LIBRARY_DB_PASSWORD") ?: "";
$database = getenv("LIBRARY_DB_NAME") ?: "atheneum_demo";
$port = (int)(getenv("LIBRARY_DB_PORT") ?: 3306);

$conn = new mysqli($host, $user, $password, $database, $port);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    exit("Database unavailable.");
}
$conn->set_charset("utf8mb4");
?>
