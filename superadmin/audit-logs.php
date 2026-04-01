<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('super_admin');
$user = getCurrentUser();

$stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 100");
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Logs - Epong Barbershop</title>
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
                <li><a href="audit_logs.php" class="active"><i class="fas fa-history"></i> Audit Logs</a></li>
                <li><a href="about.php"><i class="fas fa-address-card"></i> About</a></li>
                <li><hr style="border:0; border-top:1px solid #eee; margin:15px 0;"></li>
                <li><a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color:#d93025;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h2>System Audit Logs</h2>
            </div>
            
            <div class="card">
                <p style="color:#666;">Showing the last 100 system events.</p>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User Type</th>
                                <th>User ID</th>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><small style="color:#666;"><?= htmlspecialchars($log['created_at']) ?></small></td>
                                <td><?= htmlspecialchars($log['user_type']) ?></td>
                                <td><?= htmlspecialchars($log['user_id']) ?></td>
                                <td><span class="badge" style="background:#eee;color:#333;"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td><small style="color:#888;"><?= htmlspecialchars($log['ip_address']) ?></small></td>
                                <td style="max-width:300px;"><?= htmlspecialchars($log['details']) ?></td>
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

