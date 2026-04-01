<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('super_admin');
$user = getCurrentUser();

$profile_img = '../assets/images/default.jpg';
$stmt = $pdo->prepare("SELECT name, profile_img FROM admins WHERE id = ?");
$stmt->execute([$user['id']]);
$db_admin = $stmt->fetch();
if ($db_admin && !empty($db_admin['profile_img'])) $profile_img = '../' . $db_admin['profile_img'];
$user_name = $db_admin['name'] ?? 'Super Admin';

// 4 Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'admin'");
$total_admins = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM barbers");
$total_barbers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$total_customers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(s.price) FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.status = 'completed'");
$platform_revenue = $stmt->fetchColumn() ?: 0;

// Recent Activity
$stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10");
$recent_logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stat-card { display: flex; align-items: center; padding: 25px; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .stat-icon { font-size: 2em; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-right: 20px; }
        .stat-icon.admins { background: #e8f0fe; color: #1a73e8; }
        .stat-icon.barbers { background: #fef9e7; color: #d97706; }
        .stat-icon.customers { background: #f3f4f6; color: #4b5563; }
        .stat-icon.revenue { background: #d1fae5; color: #059669; }
        .stat-content h3 { margin: 0; font-size: 0.85em; color: #6b7280; font-weight: 600; text-transform: uppercase; }
        .stat-content .value { font-size: 1.8em; font-weight: 700; color: #111827; margin-top: 5px; }
        
        .top-navbar { display: flex; justify-content: space-between; align-items: center; padding-bottom: 25px; margin-bottom: 30px; border-bottom: 1px solid #eef0f3; }
        .top-right { display: flex; align-items: center; gap: 20px; font-weight: 600; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">Epong Platform</div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
                <li><a href="shops.php"><i class="fas fa-store"></i> Manage Shops</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> Platform Reports</a></li>
                <li><a href="audit-logs.php"><i class="fas fa-history"></i> Audit Logs</a></li>
                <li><a href="system-settings.php"><i class="fas fa-cog"></i> System Settings</a></li>
                <li><a href="about.php"><i class="fas fa-address-card"></i> About</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="top-navbar">
                <div>
                    <h2 style="margin: 0; font-size: 1.6em; font-weight: 700; color: #111827;">Super Admin Dashboard</h2>
                    <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 0.9em;">Platform Overview</p>
                </div>
                <div class="top-right">
                    <div style="display:flex; align-items:center; gap:10px; color:#4b5563;">
                        <img src="<?= htmlspecialchars($profile_img) ?>" style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
                        <span><?= htmlspecialchars($user_name) ?></span>
                    </div>
                    <form id="logout-form" action="../index.php" method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" style="background:none; border:none; color:#ef4444; font-weight:600; cursor:pointer; font-size:1em; display:flex; align-items:center; gap:6px;"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </form>
                </div>
            </div>
            
            <div class="grid-4" style="margin-bottom:35px;">
                <div class="stat-card">
                    <div class="stat-icon admins"><i class="fas fa-user-tie"></i></div>
                    <div class="stat-content">
                        <h3>Total Admins</h3>
                        <div class="value"><?= $total_admins ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon barbers"><i class="fas fa-cut"></i></div>
                    <div class="stat-content">
                        <h3>Total Barbers</h3>
                        <div class="value"><?= $total_barbers ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon customers"><i class="fas fa-users"></i></div>
                    <div class="stat-content">
                        <h3>Total Customers</h3>
                        <div class="value"><?= $total_customers ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-content">
                        <h3>Platform Revenue</h3>
                        <div class="value">₱<?= number_format($platform_revenue, 0) ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="margin:0; font-size: 1.25em; font-weight: 700; color: #111827;">Recent Platform Activity</h3>
                    <a href="audit-logs.php" class="btn-outline">View All Logs</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td style="color:#6b7280; font-size:0.9em;"><?= htmlspecialchars($log['created_at']) ?></td>
                                <td style="font-weight:500;"><?= htmlspecialchars($log['user_type']) ?> #<?= htmlspecialchars($log['user_id']) ?></td>
                                <td>
                                    <div style="font-weight:600; color:#374151; margin-bottom:4px;"><?= htmlspecialchars($log['action']) ?></div>
                                    <small style="color:#9ca3af;"><?= htmlspecialchars($log['details']) ?></small>
                                </td>
                                <td><span style="background:#f3f4f6; color:#6b7280; padding:4px 8px; border-radius:6px; font-size:0.85em; font-family:monospace;"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </main>
    </div>
</body>
</html>
