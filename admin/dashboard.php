<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('admin');
$user = getCurrentUser();

// Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total_customers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM barbers WHERE status = 'active'");
$total_barbers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
$todays_bookings = $stmt->fetchColumn();

// Monthly Revenue
$stmt = $pdo->query("SELECT SUM(s.price) FROM appointments a JOIN services s ON a.service_id = s.id 
                     WHERE a.status = 'completed' AND MONTH(a.appointment_date) = MONTH(CURDATE()) AND YEAR(a.appointment_date) = YEAR(CURDATE())");
$monthly_revenue = $stmt->fetchColumn() ?: 0;

// Recent Bookings
$stmt = $pdo->query("SELECT a.*, c.name as customer_name, b.name as barber_name, s.name as service_name 
                     FROM appointments a 
                     JOIN users c ON a.customer_id = c.id 
                     JOIN barbers b ON a.barber_id = b.id 
                     JOIN services s ON a.service_id = s.id 
                     ORDER BY a.created_at DESC LIMIT 5");
$recent_bookings = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stat-card { display: flex; align-items: center; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .stat-icon { font-size: 2.5em; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 12px; margin-right: 20px; }
        .stat-icon.customers { background: #e8f0fe; color: #1a73e8; }
        .stat-icon.barbers { background: #fef9e7; color: #f29900; }
        .stat-icon.bookings { background: #e8f5e9; color: #1e8e3e; }
        .stat-icon.revenue { background: #fde8e8; color: #d93025; }
        .stat-content h3 { margin: 0; font-size: 0.9em; color: #666; font-weight: 500; }
        .stat-content .value { font-size: 1.8em; font-weight: 700; color: var(--primary-navy); margin-top: 5px; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">Epong Admin</div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="barbers.php"><i class="fas fa-cut"></i> Manage Barbers</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="services.php"><i class="fas fa-list"></i> Services</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php if($user['role'] === 'super_admin'): ?>
                <li><hr style="border:0; border-top:1px solid #eee; margin:15px 0;"></li>
                <?php endif; ?>
                <li><a href="about.php"><i class="fas fa-address-card"></i> About</a></li>
                <li><a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color:#d93025;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            <form id="logout-form" action="../index.php" method="POST" style="display: none;">
                <input type="hidden" name="action" value="logout">
            </form>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h2>Admin Dashboard</h2>
            </div>
            
            <div class="grid-4" style="margin-bottom:30px;">
                <div class="stat-card">
                    <div class="stat-icon customers"><i class="fas fa-users"></i></div>
                    <div class="stat-content">
                        <h3>Total Customers</h3>
                        <div class="value"><?= $total_customers ?></div>
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
                    <div class="stat-icon bookings"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-content">
                        <h3>Today's Bookings</h3>
                        <div class="value"><?= $todays_bookings ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue"><i class="fas fa-peso-sign"></i></div>
                    <div class="stat-content">
                        <h3>Monthly Revenue</h3>
                        <div class="value">₱<?= number_format($monthly_revenue, 2) ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="margin:0;">Recent Bookings</h3>
                    <a href="reports.php" class="btn-outline">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Barber</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                                <td><?= htmlspecialchars($booking['barber_name']) ?></td>
                                <td><?= htmlspecialchars($booking['service_name']) ?></td>
                                <td><?= htmlspecialchars($booking['appointment_date']) ?> <br><small style="color:#888;"><?= htmlspecialchars($booking['appointment_time']) ?></small></td>
                                <td>
                                    <?php
                                        $badgeClass = 'badge-pending';
                                        if($booking['status'] == 'confirmed') $badgeClass = 'badge-confirmed';
                                        if($booking['status'] == 'completed') $badgeClass = 'badge-done';
                                        if($booking['status'] == 'cancelled') $badgeClass = 'badge-cancelled';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($booking['status']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="grid-3" style="margin-top:30px;">
                <div class="card">
                    <h3 style="margin-top:0;">Quick Links</h3>
                    <ul style="list-style:none; padding:0; margin:0;">
                        <li style="margin-bottom:10px;"><a href="barbers.php" class="btn-outline" style="display:block; text-align:center;">Add New Barber</a></li>
                        <li style="margin-bottom:10px;"><a href="services.php" class="btn-outline" style="display:block; text-align:center;">Manage Pricing</a></li>
                        <li><a href="reports.php" class="btn-outline" style="display:block; text-align:center;">Generate Report</a></li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>


