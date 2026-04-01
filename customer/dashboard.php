<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('customer');
$user = getCurrentUser();

// Fetch upcoming appointments
$stmt = $pdo->prepare("SELECT a.*, b.name as barber_name, s.name as service_name, s.duration_minutes 
                       FROM appointments a 
                       JOIN barbers b ON a.barber_id = b.id 
                       JOIN services s ON a.service_id = s.id 
                       WHERE a.customer_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ('pending', 'confirmed')
                       ORDER BY a.appointment_date, a.appointment_time LIMIT 5");
$stmt->execute([$user['id']]);
$upcoming = $stmt->fetchAll();

// Fetch past appointments
$stmt = $pdo->prepare("SELECT a.*, b.name as barber_name, s.name as service_name, s.duration_minutes 
                       FROM appointments a 
                       JOIN barbers b ON a.barber_id = b.id 
                       JOIN services s ON a.service_id = s.id 
                       WHERE a.customer_id = ? AND a.status IN ('completed', 'cancelled')
                       ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 5");
$stmt->execute([$user['id']]);
$past = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">Epong Barbershop</div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="booking.php"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color:#d93025;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            <form id="logout-form" action="../index.php" method="POST" style="display: none;">
                <input type="hidden" name="action" value="logout">
            </form>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h2>Welcome, <?= htmlspecialchars($user['name']) ?>!</h2>
                <a href="booking.php" class="btn-blue">Book Appointment</a>
            </div>
            
            <div class="card" style="margin-bottom: 20px;">
                <h3>Upcoming Appointments</h3>
                <?php if (count($upcoming) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Barber</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming as $app): ?>
                                <tr>
                                    <td><?= htmlspecialchars($app['service_name']) ?></td>
                                    <td><?= htmlspecialchars($app['barber_name']) ?></td>
                                    <td><?= htmlspecialchars($app['appointment_date']) ?></td>
                                    <td><?= htmlspecialchars($app['appointment_time']) ?></td>
                                    <td><span class="badge badge-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span></td>
                                    <td><a href="booking-summary.php?id=<?= $app['id'] ?>" class="btn-outline">View</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:#666;">No upcoming appointments. Treat yourself to a fresh cut!</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Booking History</h3>
                <?php if (count($past) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Barber</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($past as $app): ?>
                                <tr>
                                    <td><?= htmlspecialchars($app['service_name']) ?></td>
                                    <td><?= htmlspecialchars($app['barber_name']) ?></td>
                                    <td><?= htmlspecialchars($app['appointment_date']) ?></td>
                                    <td><span class="badge badge-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span></td>
                                    <td><a href="booking-summary.php?id=<?= $app['id'] ?>" class="btn-outline">View</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:#666;">No past bookings found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
