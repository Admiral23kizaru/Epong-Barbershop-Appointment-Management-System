<?php
// includes/functions.php

require_once __DIR__ . '/db.php';

function hashPassword($pass) {
    return password_hash($pass, PASSWORD_BCRYPT);
}

function verifyPassword($pass, $hash) {
    return password_verify($pass, $hash);
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    return date('M d, Y h:i A', strtotime($date));
}

function createNotification($pdo, $user_id, $user_type, $message) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, user_type, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $user_type, $message]);
}

function logAudit($pdo, $user_id, $user_type, $action, $details) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, user_type, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_type, $action, $details, $ip]);
}
?>
