<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('customer');
$user = getCurrentUser();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND customer_id = ? AND status IN ('pending', 'confirmed')");
    $stmt->execute([$id, $user['id']]);
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT barber_id FROM appointments WHERE id = ?");
        $stmt->execute([$id]);
        $barber = $stmt->fetchColumn();
        createNotification($pdo, $barber, 'barber', "Appointment ID {$id} was cancelled by {$user['name']}.");
        logAudit($pdo, $user['id'], 'customer', 'cancel_appointment', "Cancelled appointment ID {$id}");
        $success = "Appointment cancelled successfully.";
    } else {
        $error = "Unable to cancel appointment.";
    }
}

$stmt = $pdo->prepare("SELECT a.*, b.name as barber_name, s.name as service_name, s.duration_minutes, s.price 
                       FROM appointments a 
                       JOIN barbers b ON a.barber_id = b.id 
                       JOIN services s ON a.service_id = s.id 
                       WHERE a.id = ? AND a.customer_id = ?");
$stmt->execute([$id, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Summary - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">Epong Barbershop</div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
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
                <h2>Booking Summary #<?= htmlspecialchars($booking['id']) ?></h2>
            </div>
            
            <?php if (!empty($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <div class="card" style="max-width: 600px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="margin:0;">Details</h3>
                    <span class="badge badge-<?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
                </div>
                
                <table style="width:100%;">
                    <tr><td style="padding:10px 0; border-bottom:1px solid #eee;"><strong>Service:</strong></td><td style="text-align:right; border-bottom:1px solid #eee;"><?= htmlspecialchars($booking['service_name']) ?></td></tr>
                    <tr><td style="padding:10px 0; border-bottom:1px solid #eee;"><strong>Barber:</strong></td><td style="text-align:right; border-bottom:1px solid #eee;"><?= htmlspecialchars($booking['barber_name']) ?></td></tr>
                    <tr><td style="padding:10px 0; border-bottom:1px solid #eee;"><strong>Date:</strong></td><td style="text-align:right; border-bottom:1px solid #eee;"><?= htmlspecialchars($booking['appointment_date']) ?></td></tr>
                    <tr><td style="padding:10px 0; border-bottom:1px solid #eee;"><strong>Time:</strong></td><td style="text-align:right; border-bottom:1px solid #eee;"><?= htmlspecialchars($booking['appointment_time']) ?></td></tr>
                    <tr><td style="padding:10px 0; border-bottom:1px solid #eee;"><strong>Duration:</strong></td><td style="text-align:right; border-bottom:1px solid #eee;"><?= htmlspecialchars($booking['duration_minutes']) ?> mins</td></tr>
                    <tr><td style="padding:10px 0; border-bottom:1px solid #eee;"><strong>Price:</strong></td><td style="text-align:right; border-bottom:1px solid #eee;">₱<?= number_format($booking['price'], 2) ?></td></tr>
                </table>
                <div style="margin-top:20px;">
                    <strong>Notes:</strong><br>
                    <p style="color:#666; margin-top:5px;"><?= nl2br(htmlspecialchars($booking['notes'] ?: 'No notes provided.')) ?></p>
                </div>

                <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                    <form method="POST" style="margin-top:20px; text-align:right;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn-danger"><i class="fas fa-times"></i> Cancel Booking</button>
                    </form>
                <?php endif; ?>
            </div>
            <div style="margin-top:20px;">
                <a href="dashboard.php" class="btn-outline"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </main>
    </div>
</body>
</html>
