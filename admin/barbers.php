<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('admin');
$user = getCurrentUser();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $specialization = sanitize($_POST['specialization']);
        
        $password = hashPassword('admin123');
        
        $stmt = $pdo->prepare("SELECT id FROM barbers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already in use.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO barbers (name, email, password, phone, specialization) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $password, $phone, $specialization])) {
                $barber_id = $pdo->lastInsertId();
                $success = "Barber added successfully. Default password is 'admin123'.";
                
                // Seed default 9AM to 6PM schedule for every day so they can take bookings immediately
                $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                $schedStmt = $pdo->prepare("INSERT INTO barber_schedules (barber_id, day_of_week, start_time, end_time) VALUES (?, ?, '09:00:00', '18:00:00')");
                foreach($days as $day) {
                    $schedStmt->execute([$barber_id, $day]);
                }
            } else {
                $error = "Failed to add barber.";
            }
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $specialization = sanitize($_POST['specialization']);

        $stmt = $pdo->prepare("UPDATE barbers SET name=?, phone=?, specialization=? WHERE id=?");
        if ($stmt->execute([$name, $phone, $specialization, $id])) {
            $success = "Barber profile updated.";
        } else {
            $error = "Failed to update barber.";
        }
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $new_status = ($status === 'active') ? 'inactive' : 'active';
        
        $stmt = $pdo->prepare("UPDATE barbers SET status=? WHERE id=?");
        $stmt->execute([$new_status, $id]);
        $success = "Barber status updated to $new_status.";
    }
}

// Fetch Barbers
$stmt = $pdo->query("SELECT b.*, (SELECT COUNT(*) FROM appointments WHERE barber_id = b.id AND status='completed') as jobs_done FROM barbers b ORDER BY b.created_at DESC");
$barbers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Barbers - Epong Barbershop</title>
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
                <li><a href="barbers.php" class="active"><i class="fas fa-cut"></i> Manage Barbers</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="services.php"><i class="fas fa-list"></i> Services</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php if($user['role'] === 'super_admin'): ?>
                <li><hr style="border:0; border-top:1px solid #eee; margin:15px 0;"></li>
                <?php endif; ?>
                <li><a href="about.php"><i class="fas fa-address-card"></i> About</a></li>
                <li><a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color:#d93025;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h2>Manage Barbers</h2>
                <button class="btn-blue" onclick="openAddModal()"><i class="fas fa-plus"></i> Add New Barber</button>
            </div>
            
            <?php if (!empty($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email/Phone</th>
                                <th>Specialization</th>
                                <th>Jobs Done</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($barbers as $barber): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center;">
                                        <img src="<?= $barber['profile_img'] ? '../'.htmlentities($barber['profile_img']) : '../assets/images/default.jpg' ?>" alt="" style="width:40px;height:40px;border-radius:50%;margin-right:10px;">
                                        <strong><?= htmlspecialchars($barber['name']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($barber['email']) ?><br>
                                    <small style="color:#888;"><?= htmlspecialchars($barber['phone'] ?? 'N/A') ?></small>
                                </td>
                                <td><?= htmlspecialchars($barber['specialization'] ?: 'General') ?></td>
                                <td>
                                    <div style="display:inline-block; padding:3px 8px; background:#f0f0f0; border-radius:12px; font-size:0.9em; font-weight:600;">
                                        <?= $barber['jobs_done'] ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if($barber['status'] === 'active'): ?>
                                        <span class="badge badge-done">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-cancelled">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-outline" style="padding:4px 8px; font-size:0.85em;" onclick='openEditModal(<?= json_encode($barber) ?>)'><i class="fas fa-edit"></i> Edit</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $barber['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $barber['status'] ?>">
                                        <?php if($barber['status'] === 'active'): ?>
                                            <button type="submit" class="btn-danger" style="padding:4px 8px; font-size:0.85em;" title="Deactivate" onclick="return confirm('Deactivate this barber?');"><i class="fas fa-ban"></i></button>
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
                <h2>Add New Barber</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone"></div>
                    <div class="form-group"><label>Specialization</label><input type="text" name="specialization" placeholder="e.g. Fades"></div>
                    <div class="alert alert-success" style="font-size:0.85em;">Default password will be <strong>admin123</strong></div>
                    <button type="submit" class="btn-navy">Add Barber</button>
                </form>
            </div>

            <div id="edit-modal" style="display:none;">
                <h2>Edit Barber</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" id="edit_name" required></div>
                    <div class="form-group"><label>Specialization</label><input type="text" name="specialization" id="edit_spec"></div>
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone" id="edit_phone"></div>
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
        function openEditModal(barber) {
            document.getElementById('edit_id').value = barber.id;
            document.getElementById('edit_name').value = barber.name;
            document.getElementById('edit_spec').value = barber.specialization || '';
            document.getElementById('edit_phone').value = barber.phone || '';
            
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


