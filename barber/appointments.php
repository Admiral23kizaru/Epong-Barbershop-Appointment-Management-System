<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('barber');
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = $_POST['action'];
    $id = $_POST['id'];

    $stmt = $pdo->prepare("SELECT a.*, c.name FROM appointments a JOIN users c ON a.customer_id = c.id WHERE a.id = ? AND a.barber_id = ?");
    $stmt->execute([$id, $user['id']]);
    $appointment = $stmt->fetch();

    if ($appointment) {
        $status = '';
        $msg = '';
        if ($action === 'confirm' && $appointment['status'] === 'pending') {
            $status = 'confirmed';
            $msg = 'Appointment confirmed.';
        } elseif ($action === 'complete' && $appointment['status'] === 'confirmed') {
            $status = 'completed';
            $msg = 'Appointment marked as completed.';
        } elseif ($action === 'cancel' && in_array($appointment['status'], ['pending', 'confirmed'])) {
            $status = 'cancelled';
            $msg = 'Appointment cancelled.';
        }

        if ($status) {
            $updateStmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            $updateStmt->execute([$status, $id]);
            
            createNotification($pdo, $appointment['customer_id'], 'customer', "Your appointment on {$appointment['appointment_date']} was {$status} by the barber.");
            logAudit($pdo, $user['id'], 'barber', "{$status}_appointment", "Appointment ID {$id} marked as {$status}");
            
            header("Location: dashboard.php?msg=" . urlencode($msg));
            exit();
        }
    }
}
header("Location: dashboard.php");
exit();
?>
