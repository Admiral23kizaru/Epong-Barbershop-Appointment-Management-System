<?php
// includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2 hours timeout
$timeout_duration = 7200; 

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'],
            'name' => $_SESSION['name'] ?? ''
        ];
    }
    return null;
}

function requireLogin($requiredRole = null) {
    global $pdo;
    
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/index.php");
        exit();
    }
    
    $currentRole = $_SESSION['role'];

    // Check Maintenance Mode
    try {
        if ($pdo) {
            $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
            $mMode = $stmt->fetchColumn();
            if ($mMode == '1' && $currentRole !== 'super_admin') {
                die("<html><body style='text-align:center; padding:100px; font-family:sans-serif;'><h2>We are currently undergoing maintenance.</h2><p>Please check back later.</p></body></html>");
            }
        }
    } catch (Exception $e) { }
    
    $currentRole = $_SESSION['role'];
    
    // Redirect if role does not match
    if ($requiredRole === 'customer' && $currentRole !== 'customer') {
        redirectBasedOnRole($currentRole);
    }
    
    if ($requiredRole === 'barber' && $currentRole !== 'barber') {
        redirectBasedOnRole($currentRole);
    }

    if ($requiredRole === 'admin' && !in_array($currentRole, ['admin', 'super_admin'])) {
        redirectBasedOnRole($currentRole);
    }
    
    if ($requiredRole === 'super_admin' && $currentRole !== 'super_admin') {
        redirectBasedOnRole($currentRole);
    }
}

function redirectBasedOnRole($role) {
    if ($role === 'customer') {
        header("Location: " . BASE_URL . "/customer/dashboard.php");
    } elseif ($role === 'barber') {
        header("Location: " . BASE_URL . "/barber/dashboard.php");
    } elseif ($role === 'super_admin') {
        header("Location: " . BASE_URL . "/superadmin/dashboard.php");
    } elseif ($role === 'admin') {
        header("Location: " . BASE_URL . "/admin/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "/index.php");
    }
    exit();
}

function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    session_destroy();
    if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'barber/') !== false){
        header("Location: " . BASE_URL . "/index.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}
?>
