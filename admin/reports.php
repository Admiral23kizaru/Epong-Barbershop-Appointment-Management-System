<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('admin');
$user = getCurrentUser();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . $startDate . '_to_' . $endDate . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Appointment ID', 'Date', 'Time', 'Customer', 'Barber', 'Service', 'Duration', 'Price', 'Status']);
    
    $stmt = $pdo->prepare("SELECT a.id, a.appointment_date, a.appointment_time, c.name as customer_name, b.name as barber_name, s.name as service_name, s.duration_minutes, s.price, a.status 
                           FROM appointments a 
                           JOIN users c ON a.customer_id = c.id 
                           JOIN barbers b ON a.barber_id = b.id 
                           JOIN services s ON a.service_id = s.id 
                           WHERE a.appointment_date BETWEEN ? AND ? 
                           ORDER BY a.appointment_date ASC, a.appointment_time ASC");
    $stmt->execute([$startDate, $endDate]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'], $row['appointment_date'], $row['appointment_time'],
            $row['customer_name'], $row['barber_name'], $row['service_name'],
            $row['duration_minutes'], $row['price'], $row['status']
        ]);
    }
    fclose($output);
    exit();
}

// Fetch Report Data
$stmt = $pdo->prepare("SELECT a.*, c.name as customer_name, b.name as barber_name, s.name as service_name, s.price 
                       FROM appointments a 
                       JOIN users c ON a.customer_id = c.id 
                       JOIN barbers b ON a.barber_id = b.id 
                       JOIN services s ON a.service_id = s.id 
                       WHERE a.appointment_date BETWEEN ? AND ? 
                       ORDER BY a.appointment_date ASC");
$stmt->execute([$startDate, $endDate]);
$appointments = $stmt->fetchAll();

$total_bookings = count($appointments);
$completed = 0;
$cancelled = 0;
$revenue = 0;

foreach ($appointments as $app) {
    if ($app['status'] === 'completed') {
        $completed++;
        $revenue += $app['price'];
    } elseif ($app['status'] === 'cancelled') {
        $cancelled++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .report-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
        .filter-form { display: flex; gap: 15px; align-items: flex-end; }
        @media print {
            .sidebar, .filter-form, .btn-primary, .btn-outline, .btn-blue, .dashboard-header { display: none !important; }
            .dashboard-layout { display: block; }
            .main-content { padding: 0; margin: 0; }
            .card { box-shadow: none; border: 1px solid #ddd; }
            body { background: #fff; }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">Epong Admin</div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="barbers.php"><i class="fas fa-cut"></i> Manage Barbers</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="services.php"><i class="fas fa-list"></i> Services</a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php if($user['role'] === 'super_admin'): ?>
                <li><hr style="border:0; border-top:1px solid #eee; margin:15px 0;"></li>
                <?php endif; ?>
                <li><a href="about.php"><i class="fas fa-address-card"></i> About</a></li>
                <li><a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color:#d93025;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h2>Reports & Analytics</h2>
            </div>
            
            <div class="card" style="margin-bottom: 20px;">
                <div class="report-header">
                    <form method="GET" class="filter-form">
                        <div>
                            <label style="display:block; font-size:0.9em; margin-bottom:5px;">Start Date</label>
                            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-group" style="margin:0; padding:8px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.9em; margin-bottom:5px;">End Date</label>
                            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-group" style="margin:0; padding:8px;">
                        </div>
                        <button type="submit" class="btn-navy" style="width:auto; padding:8px 16px;"><i class="fas fa-filter"></i> Filter</button>
                    </form>
                    <div style="display:flex; gap:10px;">
                        <button class="btn-outline" onclick="window.print()"><i class="fas fa-file-pdf"></i> PDF View</button>
                        <a href="?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&export=csv" class="btn-blue" style="text-decoration:none;"><i class="fas fa-file-csv"></i> Export CSV</a>
                    </div>
                </div>
            </div>

            <div class="grid-card grid-4" style="margin-bottom:20px; border:none; background:transparent;">
                <div class="card" style="text-align:center;">
                    <h3 style="margin-top:0; color:#666; font-size:1em;">Total Bookings</h3>
                    <div style="font-size:2em; font-weight:bold; color:var(--primary-navy);"><?= $total_bookings ?></div>
                </div>
                <div class="card" style="text-align:center;">
                    <h3 style="margin-top:0; color:#666; font-size:1em;">Completed</h3>
                    <div style="font-size:2em; font-weight:bold; color:#1e8e3e;"><?= $completed ?></div>
                </div>
                <div class="card" style="text-align:center;">
                    <h3 style="margin-top:0; color:#666; font-size:1em;">Cancelled</h3>
                    <div style="font-size:2em; font-weight:bold; color:#d93025;"><?= $cancelled ?></div>
                </div>
                <div class="card" style="text-align:center;">
                    <h3 style="margin-top:0; color:#666; font-size:1em;">Revenue</h3>
                    <div style="font-size:2em; font-weight:bold; color:#1a73e8;">₱<?= number_format($revenue, 2) ?></div>
                </div>
            </div>

            <div class="card">
                <h3>Detailed Report (<?= htmlspecialchars($startDate) ?> to <?= htmlspecialchars($endDate) ?>)</h3>
                <?php if(count($appointments) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Barber</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $app): ?>
                            <tr>
                                <td><?= htmlspecialchars($app['appointment_date']) ?> <br><small><?= htmlspecialchars($app['appointment_time']) ?></small></td>
                                <td><?= htmlspecialchars($app['customer_name']) ?></td>
                                <td><?= htmlspecialchars($app['barber_name']) ?></td>
                                <td><?= htmlspecialchars($app['service_name']) ?></td>
                                <td>
                                    <?php
                                        $badgeClass = 'badge-pending';
                                        if($app['status'] == 'confirmed') $badgeClass = 'badge-confirmed';
                                        if($app['status'] == 'completed') $badgeClass = 'badge-done';
                                        if($app['status'] == 'cancelled') $badgeClass = 'badge-cancelled';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($app['status']) ?></span>
                                </td>
                                <td><?= $app['status']==='completed' ? '₱'.number_format($app['price'], 2) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p style="color:#666; text-align:center; padding:20px;">No data found for this period.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>


