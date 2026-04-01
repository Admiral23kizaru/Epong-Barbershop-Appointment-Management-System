<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$hashed = hashPassword('admin123');
$pdo->query("UPDATE admins SET password = '$hashed'");
