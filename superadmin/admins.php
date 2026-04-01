<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('super_admin'); // Only super_admin can manage admins
$user = getCurrentUser();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $role = $_POST['role'] === 'super_admin' ? 'super_admin' : 'admin';
        
        $password = hashPassword('admin123');
        
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already in use.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $password, $phone, $role])) {
                $success = "Admin added successfully. Default password is 'admin123'.";
            } else {
                $error = "Failed to add admin.";
            }
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $role = $_POST['role'] === 'super_admin' ? 'super_admin' : 'admin';

        $stmt = $pdo->prepare("UPDATE admins SET name=?, phone=?, role=? WHERE id=?");
        if ($stmt->execute([$name, $phone, $role, $id])) {
            $success = "Admin profile updated.";
        } else {
            $error = "Failed to update admin.";
        }
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        
        if ($id == $user['id']) {
            $error = "You cannot deactivate your own account.";
        } else {
            $status = $_POST['status'];
            $new_status = ($status === 'active') ? 'inactive' : 'active';
            
            $stmt = $pdo->prepare("UPDATE admins SET status=? WHERE id=?");
            $stmt->execute([$new_status, $id]);
            $success = "Admin status updated to $new_status.";
        }
    }
}

// Fetch Admins
$stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
$adminsList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Admins - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">Epong Admin</div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="admins.php" class="active"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
                <li><a href="audit_logs.php"><i class="fas fa-history"></i> Audit Logs</a></li>
                <li><a href="about.php"><i class="fas fa-address-card"></i> About</a></li>
                <li><hr style="border:0; border-top:1px solid #eee; margin:15px 0;"></li>
                <li><a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color:#d93025;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            <form id="logout-form" action="../index.php" method="POST" style="display: none;">
                <input type="hidden" name="action" value="logout">
            </form>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2>Manage Admins</h2>
                <button class="btn-blue" onclick="openAddModal()"><i class="fas fa-plus"></i> Add New Admin</button>
            </div>
            
            <?php if (!empty($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminsList as $adminObj): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($adminObj['name']) ?></strong></td>
                                <td><?= htmlspecialchars($adminObj['email']) ?></td>
                                <td><?= htmlspecialchars($adminObj['phone'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if($adminObj['role'] === 'super_admin'): ?>
                                        <span class="badge" style="background:var(--primary-navy);color:#fff;">Super Admin</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#eee;color:#333;">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($adminObj['status'] === 'active'): ?>
                                        <span class="badge badge-done">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-cancelled">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-outline" style="padding:4px 8px; font-size:0.85em;" onclick='openEditModal(<?= json_encode($adminObj) ?>)'><i class="fas fa-edit"></i> Edit</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $adminObj['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $adminObj['status'] ?>">
                                        <?php if($adminObj['status'] === 'active'): ?>
                                            <button type="submit" class="btn-danger" style="padding:4px 8px; font-size:0.85em;" title="Deactivate" onclick="return confirm('Deactivate this admin?');" <?= $adminObj['id'] == $user['id'] ? 'disabled' : '' ?>><i class="fas fa-ban"></i></button>
                                        <?php else: ?>
                                            <button type="submit" class="btn-success" style="padding:4px 8px; font-size:0.85em;" title="Activate"><i class="fas fa-check-circle"></i></button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="modal-backdrop" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <div id="add-modal" style="display:none;">
                <h2>Add New Admin</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone"></div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="alert alert-success" style="font-size:0.85em;">Default password will be <strong>admin123</strong></div>
                    <button type="submit" class="btn-navy">Add Admin</button>
                </form>
            </div>

            <div id="edit-modal" style="display:none;">
                <h2>Edit Admin</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" id="edit_name" required></div>
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone" id="edit_phone"></div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="edit_role" required>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-navy">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const backdrop = document.getElementById('modal-backdrop');
        const addView = document.getElementById('add-modal');
        const editView = document.getElementById('edit-modal');
        
        function openAddModal() {
            addView.style.display = 'block';
            editView.style.display = 'none';
            backdrop.style.display = 'flex';
        }
        function openEditModal(adminObj) {
            document.getElementById('edit_id').value = adminObj.id;
            document.getElementById('edit_name').value = adminObj.name;
            document.getElementById('edit_phone').value = adminObj.phone || '';
            document.getElementById('edit_role').value = adminObj.role;
            
            addView.style.display = 'none';
            editView.style.display = 'block';
            backdrop.style.display = 'flex';
        }
        function closeModal() {
            backdrop.style.display = 'none';
        }
    </script>
</body>
</html>

