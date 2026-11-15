<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login.php");
    exit;
}
