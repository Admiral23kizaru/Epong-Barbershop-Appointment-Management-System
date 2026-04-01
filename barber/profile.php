<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('barber');
$user = getCurrentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $bio = sanitize($_POST['bio']);
        $specialization = sanitize($_POST['specialization']);
        $experience_years = (int)$_POST['experience_years'];

        // File upload
        $profile_img = $_POST['current_img'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['photo']['tmp_name'];
            $name_file = basename($_FILES['photo']['name']);
            $ext = strtolower(pathinfo($name_file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $new_name = 'barber_' . $user['id'] . '_' . time() . '.' . $ext;
                $dest = __DIR__ . '/../assets/images/' . $new_name;
                if (move_uploaded_file($tmp_name, $dest)) {
                    $profile_img = 'assets/images/' . $new_name;
                }
            } else {
                $error = "Invalid file type. Only JPG, PNG allowed.";
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("SELECT id FROM barbers WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $error = "Email already in use.";
            } else {
                $stmt = $pdo->prepare("UPDATE barbers SET name=?, email=?, phone=?, bio=?, specialization=?, experience_years=?, profile_img=? WHERE id=?");
                if ($stmt->execute([$name, $email, $phone, $bio, $specialization, $experience_years, $profile_img, $user['id']])) {
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

        $stmt = $pdo->prepare("SELECT password FROM barbers WHERE id = ?");
        $stmt->execute([$user['id']]);
        $db_pass = $stmt->fetchColumn();

        if (!verifyPassword($current, $db_pass)) {
            $error = "Current password is incorrect.";
        } elseif ($new !== $confirm) {
            $error = "New passwords do not match.";
        } else {
            $hashed = hashPassword($new);
            $stmt = $pdo->prepare("UPDATE barbers SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $user['id']])) {
                $success = "Password changed successfully.";
            } else {
                $error = "Failed to change password.";
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM barbers WHERE id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE barber_id = ? AND status = 'completed'");
$stmt->execute([$user['id']]);
$completed_count = $stmt->fetchColumn();

// Earnings
$stmt = $pdo->prepare("SELECT SUM(s.price) FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.barber_id = ? AND a.status = 'completed'");
$stmt->execute([$user['id']]);
$total_earnings = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE barber_id = ? AND status IN ('pending', 'confirmed') AND appointment_date >= CURDATE()");
$stmt->execute([$user['id']]);
$upcoming_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barber Profile - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f7f9fc; margin: 0; font-family: 'Inter', sans-serif; color: #111827; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 8px; color: #374151; font-size: 0.9em; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; font-family: 'Inter', sans-serif; font-size: 0.95em; transition: border-color 0.2s; }
        .form-group input:focus, .form-group textarea:focus { border-color: #2563eb; outline: none; }
        .btn-blue { background: #2563eb; color: #ffffff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 0.95em; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-blue:hover { background: #1d4ed8; }
        .btn-navy { background: #111827; color: #ffffff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 0.95em; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-navy:hover { background: #000000; }
        .card { background: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 30px; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <header style="background: #ffffff; border-bottom: 1px solid #eef0f3; padding: 15px 50px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="margin: 0; font-size: 1.25em; font-weight: 700; color: #111827;">Barber Profile</h1>
            <p style="margin: 3px 0 0 0; font-size: 0.8em; color: #6b7280; font-weight: 400;">Manage your personal details</p>
        </div>
        <div style="display: flex; gap: 30px; align-items: center; font-size: 0.85em; font-weight: 600;">
            <a href="dashboard.php" style="color: #4b5563; text-decoration: none; display: flex; align-items: center; gap: 8px;"><i class="fas fa-home"></i> Dashboard</a>
            <a href="profile.php" style="color: #111827; text-decoration: none; display: flex; align-items: center; gap: 8px;"><i class="fas fa-user"></i> Profile</a>
            <a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color: #ef4444; text-decoration: none; display: flex; align-items: center; gap: 8px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <form id="logout-form" action="../index.php" method="POST" style="display: none;">
                <input type="hidden" name="action" value="logout">
            </form>
        </div>
    </header>

    <!-- Main Content Container -->
    <main style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
        
        <?php if (!empty($error)): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-size:0.9em; font-weight:500;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-size:0.9em; font-weight:500;">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 30px; align-items: start;">
            
            <!-- Profile Info Card -->
            <div class="card" style="text-align: center;">
                <img src="<?= $profile['profile_img'] ? '../'.htmlspecialchars($profile['profile_img']) : '../assets/images/default.jpg' ?>" alt="Profile" style="width: 130px; height: 130px; border-radius: 50%; background: #f3f4f6; object-fit: cover; margin-bottom: 20px; border: 4px solid #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h3 style="margin: 0 0 5px 0; color: #111827; font-size: 1.3em; font-weight: 700;"><?= htmlspecialchars($profile['name']) ?></h3>
                <p style="color: #6b7280; font-size: 0.9em; margin: 0 0 15px 0; font-weight: 500;"><?= htmlspecialchars($profile['specialization'] ?: 'Master Barber') ?></p>
                <div style="color: #f59e0b; font-size: 1.1em; margin-bottom: 25px;">
                    <i class="fas fa-star"></i> 4.9 <span style="color: #6b7280; font-size: 0.8em; font-weight: 500;">(<?= $completed_count ?> jobs)</span>
                </div>
                
                <div style="display: flex; justify-content: space-around; border-top: 1px solid #f3f4f6; padding-top: 25px; margin-top: 25px;">
                    <div>
                        <div style="font-size: 1.3em; font-weight: 700; color: #2563eb;"><?= $completed_count ?></div>
                        <div style="font-size: 0.8em; font-weight: 500; color: #6b7280; text-transform: uppercase; margin-top: 4px;">Completed</div>
                    </div>
                    <div>
                        <div style="font-size: 1.3em; font-weight: 700; color: #10b981;"><?= $upcoming_count ?></div>
                        <div style="font-size: 0.8em; font-weight: 500; color: #6b7280; text-transform: uppercase; margin-top: 4px;">Upcoming</div>
                    </div>
                    <div>
                        <div style="font-size: 1.3em; font-weight: 700; color: #111827;">₱<?= number_format($total_earnings, 0) ?></div>
                        <div style="font-size: 0.8em; font-weight: 500; color: #6b7280; text-transform: uppercase; margin-top: 4px;">Earnings</div>
                    </div>
                </div>
            </div>

            <!-- Edit Forms Container -->
            <div>
                <!-- Edit Profile Card -->
                <div class="card" style="margin-bottom: 30px;">
                    <h3 style="margin: 0 0 25px 0; font-size: 1.25em; font-weight: 600; color: #111827; border-bottom: 1px solid #f3f4f6; padding-bottom: 15px;">Edit Profile</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="current_img" value="<?= htmlspecialchars($profile['profile_img']) ?>">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0 20px;">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Experience (Years)</label>
                                <input type="number" name="experience_years" value="<?= htmlspecialchars($profile['experience_years']) ?>" min="0">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Specialization</label>
                            <input type="text" name="specialization" value="<?= htmlspecialchars($profile['specialization']) ?>" placeholder="e.g. Fades, Beards, Classic Cuts">
                        </div>

                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio" rows="4" placeholder="Tell your customers about yourself..."><?= htmlspecialchars($profile['bio']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Profile Photo</label>
                            <input type="file" name="photo" accept="image/png, image/jpeg, image/webp" style="padding: 10px; background: #f9fafb; border: 1px dashed #d1d5db; cursor: pointer;">
                        </div>

                        <div style="margin-top: 30px; text-align: right;">
                            <button type="submit" class="btn-blue">Save Profile Changes</button>
                        </div>
                    </form>
                </div>

                <!-- Password Card -->
                <div class="card">
                    <h3 style="margin: 0 0 25px 0; font-size: 1.25em; font-weight: 600; color: #111827; border-bottom: 1px solid #f3f4f6; padding-bottom: 15px;">Change Password</h3>
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
                        <div style="margin-top: 10px; text-align: right;">
                            <button type="submit" class="btn-navy">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
