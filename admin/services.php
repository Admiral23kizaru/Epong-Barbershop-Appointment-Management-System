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
        $description = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $duration = (int)$_POST['duration'];
        
        $stmt = $pdo->prepare("INSERT INTO services (name, description, price, duration_minutes) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $price, $duration])) {
            $success = "Service added successfully.";
        } else {
            $error = "Failed to add service.";
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $duration = (int)$_POST['duration'];

        $stmt = $pdo->prepare("UPDATE services SET name=?, description=?, price=?, duration_minutes=? WHERE id=?");
        if ($stmt->execute([$name, $description, $price, $duration, $id])) {
            $success = "Service updated.";
        } else {
            $error = "Failed to update service.";
        }
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $new_status = ($status === 'active') ? 'inactive' : 'active';
        
        $stmt = $pdo->prepare("UPDATE services SET status=? WHERE id=?");
        $stmt->execute([$new_status, $id]);
        $success = "Service status changed to $new_status.";
    }
}

// Fetch Services
$stmt = $pdo->query("SELECT * FROM services ORDER BY name ASC");
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Services - Epong Barbershop</title>
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
                <li><a href="barbers.php"><i class="fas fa-cut"></i> Manage Barbers</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="services.php" class="active"><i class="fas fa-list"></i> Services</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php if($user['role'] === 'super_admin'): ?>
                <li><hr style="border:0; border-top:1px solid #eee; margin:15px 0;"></li>
                <?php endif; ?>
                <li><a href="about.php"><i class="fas fa-address-card"></i> About</a></li>
                <li><a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color:#d93025;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2>Manage Services</h2>
                <button class="btn-blue" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Service</button>
            </div>
            
            <?php if (!empty($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <div class="card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $srv): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($srv['name']) ?></strong></td>
                                <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= htmlspecialchars($srv['description']) ?>
                                </td>
                                <td><i class="fas fa-clock" style="color:#888;"></i> <?= $srv['duration_minutes'] ?> mins</td>
                                <td>₱<?= number_format($srv['price'], 2) ?></td>
                                <td>
                                    <?php if($srv['status'] === 'active'): ?>
                                        <span class="badge badge-done">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-cancelled">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-outline" style="padding:4px 8px; font-size:0.85em;" onclick='openEditModal(<?= json_encode($srv) ?>)'><i class="fas fa-edit"></i> Edit</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $srv['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $srv['status'] ?>">
                                        <?php if($srv['status'] === 'active'): ?>
                                            <button type="submit" class="btn-danger" style="padding:4px 8px; font-size:0.85em;" title="Deactivate" onclick="return confirm('Deactivate this service?');"><i class="fas fa-ban"></i></button>
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
                <h2>Add Service</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group"><label>Service Name</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
                    <div class="form-group"><label>Price (PHP)</label><input type="number" step="0.01" name="price" required></div>
                    <div class="form-group"><label>Duration (Minutes)</label><input type="number" name="duration" required></div>
                    <button type="submit" class="btn-navy">Save Service</button>
                </form>
            </div>

            <div id="edit-modal" style="display:none;">
                <h2>Edit Service</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group"><label>Service Name</label><input type="text" name="name" id="edit_name" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" id="edit_desc" rows="3"></textarea></div>
                    <div class="form-group"><label>Price (PHP)</label><input type="number" step="0.01" name="price" id="edit_price" required></div>
                    <div class="form-group"><label>Duration (Minutes)</label><input type="number" name="duration" id="edit_duration" required></div>
                    <button type="submit" class="btn-navy">Update Service</button>
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
        function openEditModal(srv) {
            document.getElementById('edit_id').value = srv.id;
            document.getElementById('edit_name').value = srv.name;
            document.getElementById('edit_desc').value = srv.description || '';
            document.getElementById('edit_price').value = srv.price;
            document.getElementById('edit_duration').value = srv.duration_minutes;
            
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


