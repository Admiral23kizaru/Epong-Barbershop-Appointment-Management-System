<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('super_admin');
$user = getCurrentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        
        $profile_img = $_POST['current_img'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['photo']['tmp_name'];
            $name_file = basename($_FILES['photo']['name']);
            $ext = strtolower(pathinfo($name_file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $new_name = 'sadmin_' . $user['id'] . '_' . time() . '.' . $ext;
                $dest = __DIR__ . '/../assets/images/' . $new_name;
                if (move_uploaded_file($tmp_name, $dest)) {
                    $profile_img = 'assets/images/' . $new_name;
                }
            } else {
                $error = "Invalid file type. Only JPG, PNG allowed.";
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $error = "Email already in use.";
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET name=?, email=?, profile_img=? WHERE id=?");
                if ($stmt->execute([$name, $email, $profile_img, $user['id']])) {
                    $_SESSION['name'] = $name;
                    $success = "Profile updated successfully.";
                } else {
                    $error = "Failed to update profile.";
                }
            }
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$user['id']]);
        $db_pass = $stmt->fetchColumn();

        if (!verifyPassword($current, $db_pass)) {
            $error = "Current password is incorrect.";
        } elseif ($new !== $confirm) {
            $error = "New passwords do not match.";
        } else {
            $hashed = hashPassword($new);
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $user['id']])) {
                $success = "Password changed successfully.";
            } else {
                $error = "Failed to change password.";
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Profile - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">Super Admin</div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
                <li><a href="audit_logs.php"><i class="fas fa-history"></i> Audit Logs</a></li>
                <li><a href="about.php" class="active"><i class="fas fa-address-card"></i> About</a></li>
                <li><hr style="border:0; border-top:1px solid #eee; margin:15px 0;"></li>
                <li><a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            <form id="logout-form" action="../index.php" method="POST" style="display: none;">
                <input type="hidden" name="action" value="logout">
            </form>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h2>Super Admin Profile</h2>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <div class="grid-3" style="align-items: start;">
                <!-- Profile Image Card -->
                <div class="card" style="text-align: center;">
                    <img src="<?= !empty($profile['profile_img']) ? '../'.htmlspecialchars($profile['profile_img']) : '../assets/images/default.jpg' ?>" alt="Profile" style="width: 140px; height: 140px; border-radius: 50%; background: #f3f4f6; object-fit: cover; margin-bottom: 20px; border: 4px solid #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 5px 0; color: #111827; font-size: 1.3em; font-weight: 700;"><?= htmlspecialchars($profile['name']) ?></h3>
                    <p style="color: #6b7280; font-size: 0.9em; margin: 0; font-weight: 500;">Lead Administrator</p>
                </div>

                <!-- Forms Container -->
                <div style="grid-column: span 2;">
                    <!-- Details Card -->
                    <div class="card" style="margin-bottom: 30px;">
                        <h3 style="margin: 0 0 25px 0; font-size: 1.25em; font-weight: 600; color: #111827; border-bottom: 1px solid #f3f4f6; padding-bottom: 15px;">Update Details</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" name="current_img" value="<?= htmlspecialchars($profile['profile_img'] ?? '') ?>">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0 20px;">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Profile Photo</label>
                                <input type="file" name="photo" accept="image/png, image/jpeg, image/webp" style="padding: 10px; background: #f9fafb; border: 1px dashed #d1d5db; cursor: pointer;">
                            </div>

                            <div style="margin-top: 25px; text-align: right;">
                                <button type="submit" class="btn-blue">Save Changes</button>
                            </div>
                        </form>
                    </div>

                    <!-- Password Card -->
                    <div class="card">
                        <h3 style="margin: 0 0 25px 0; font-size: 1.25em; font-weight: 600; color: #111827; border-bottom: 1px solid #f3f4f6; padding-bottom: 15px;">Security</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" required>
                                </div>
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" required>
                                </div>
                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <input type="password" name="confirm_password" required>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <button type="submit" class="btn-navy">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
        </main>
    </div>
</body>
</html>
