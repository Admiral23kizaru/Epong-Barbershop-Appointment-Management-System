<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$success = '';

if (!isset($_GET['token']) && empty($_POST['token'])) {
    die("Invalid request");
}

$token = $_GET['token'] ?? $_POST['token'];

// Check token
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    die("Token is invalid or expired.");
}

$email = $reset['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($password) || $password !== $confirm) {
        $error = "Passwords do not match or empty.";
    } else {
        $hashed = hashPassword($password);
        
        // Update user
        $updated = false;
        
        // Admins
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE email = ?");
        $stmt->execute([$hashed, $email]);
        if ($stmt->rowCount() > 0) $updated = true;

        if (!$updated) {
            $stmt = $pdo->prepare("UPDATE barbers SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $email]);
            if ($stmt->rowCount() > 0) $updated = true;
        }
        
        if (!$updated) {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $email]);
            if ($stmt->rowCount() > 0) $updated = true;
        }

        if ($updated) {
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $stmt->execute([$reset['id']]);
            $success = "Password reset successfully. You can now login.";
        } else {
            $error = "Failed to reset password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .container { max-width: 400px; margin-top: 100px; }
    </style>
</head>
<body>
    <div class="container card">
        <h2>Reset Password</h2>
        <?php if (!empty($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <a href="index.php" class="btn-primary" style="display:block; text-align:center;">Go to Login</a>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-navy">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
