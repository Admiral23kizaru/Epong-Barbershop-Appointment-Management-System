<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('super_admin');
$user = getCurrentUser();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_shop') {
        $shop_name = sanitize($_POST['shop_name']);
        $address = sanitize($_POST['address']);
        $contact = sanitize($_POST['contact']);
        $email = sanitize($_POST['email']);
        $description = sanitize($_POST['description']);
        
        $stmt = $pdo->prepare("UPDATE shop_settings SET shop_name=?, address=?, contact=?, email=?, description=? WHERE id=1");
        if ($stmt->execute([$shop_name, $address, $contact, $email, $description])) {
            $success = "Shop settings updated.";
        } else {
            $error = "Failed to update shop settings.";
        }
    }
}

// Fetch shop info
$stmt = $pdo->query("SELECT * FROM shop_settings WHERE id = 1");
$shop = $stmt->fetch();
if (!$shop) {
    // Basic fallback if missing
    $pdo->query("INSERT INTO shop_settings (shop_name) VALUES ('Epong Barbershop')");
    $shop = ['shop_name'=>'Epong Barbershop', 'address'=>'', 'contact'=>'', 'email'=>'', 'description'=>''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">Epong Admin</div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
                <li><a href="audit_logs.php"><i class="fas fa-history"></i> Audit Logs</a></li>
                <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
                <li><hr style="border:0; border-top:1px solid #eee; margin:15px 0;"></li>
                <li><a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color:#d93025;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h2>System Settings</h2>
            </div>
            
            <?php if (!empty($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <div class="card" style="max-width:800px;">
                <h3>Shop Information</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_shop">
                    <div class="form-group">
                        <label>Shop Name</label>
                        <input type="text" name="shop_name" value="<?= htmlspecialchars($shop['shop_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact" value="<?= htmlspecialchars($shop['contact'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Public Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($shop['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Complete Address</label>
                        <textarea name="address" rows="3"><?= htmlspecialchars($shop['address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>About / Description</label>
                        <textarea name="description" rows="4"><?= htmlspecialchars($shop['description'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary btn-navy">Save Setup</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
