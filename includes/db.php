<?php
// includes/db.php

// ---------------------------------------------------------
// SYSTEM CONFIGURATION - EDIT THIS BLOCK FOR INFINITYFREE
// ---------------------------------------------------------

// Database Settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barber_system');

// Email/SMTP Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'noreply@epong.com');
define('SMTP_FROM_NAME', 'Epong Barbershop');

// ---------------------------------------------------------

require_once __DIR__ . '/../vendor/autoload.php';

// Dynamically determine the base URL so redirections never fail
$doc_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$app_dir = str_replace('\\', '/', dirname(__DIR__));
$base_url_path = str_replace($doc_root, '', $app_dir);
define('BASE_URL', $base_url_path);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
