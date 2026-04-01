<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('barber');
$user = getCurrentUser();

$success = '';
$error = '';

$days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_schedule') {
        foreach ($days as $day) {
            $is_avail = isset($_POST["avail_$day"]) ? 1 : 0;
            $start = $_POST["start_$day"] ?? '09:00:00';
            $end = $_POST["end_$day"] ?? '17:00:00';

            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM barber_schedules WHERE barber_id = ? AND day_of_week = ?");
            $stmt->execute([$user['id'], $day]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE barber_schedules SET is_available = ?, start_time = ?, end_time = ? WHERE barber_id = ? AND day_of_week = ?");
                $stmt->execute([$is_avail, $start, $end, $user['id'], $day]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO barber_schedules (barber_id, day_of_week, is_available, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user['id'], $day, $is_avail, $start, $end]);
            }
        }
        $success = "Weekly schedule updated.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'block_date') {
        $date = $_POST['blocked_date'];
        $reason = sanitize($_POST['reason']);
        
        $stmt = $pdo->prepare("INSERT INTO blocked_dates (barber_id, blocked_date, reason) VALUES (?, ?, ?)");
        if ($stmt->execute([$user['id'], $date, $reason])) {
            $success = "Date blocked successfully.";
        } else {
            $error = "Failed to block date.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'unblock_date') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM blocked_dates WHERE id = ? AND barber_id = ?");
        $stmt->execute([$id, $user['id']]);
        $success = "Date unblocked.";
    }
}


// Workaround for PDO::FETCH_KEY_PAIR requiring 2 columns
$stmt = $pdo->prepare("SELECT day_of_week, is_available, start_time, end_time FROM barber_schedules WHERE barber_id = ?");
$stmt->execute([$user['id']]);
$scheds = [];
while ($row = $stmt->fetch()) {
    $scheds[$row['day_of_week']] = $row;
}

$stmt = $pdo->prepare("SELECT * FROM blocked_dates WHERE barber_id = ? AND blocked_date >= CURDATE() ORDER BY blocked_date ASC");
$stmt->execute([$user['id']]);
$blocked = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Schedule - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sched-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .sched-row:last-child { border-bottom: none; }
        .toggle-switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary-blue); }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">Epong Barbershop</div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="schedule.php" class="active"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color:#d93025;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h2>Manage Availability</h2>
            </div>
            
            <?php if (!empty($error)): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <div class="grid-card grid-3" style="border:none; cursor:default; background:transparent;">
                <div class="card" style="grid-column: span 2;">
                    <h3>Weekly Schedule</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_schedule">
                        <?php foreach($days as $day): 
                            $s = $scheds[$day] ?? ['is_available' => 0, 'start_time'=>'09:00', 'end_time'=>'17:00'];
                        ?>
                            <div class="sched-row">
                                <div style="width:100px; font-weight:600; text-transform:capitalize;"><?= $day ?></div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="avail_<?= $day ?>" <?= $s['is_available'] ? 'checked' : '' ?> onchange="toggleTime('<?= $day ?>')">
                                    <span class="slider"></span>
                                </label>
                                <div id="time_<?= $day ?>" style="<?= $s['is_available'] ? '' : 'opacity:0.3; pointer-events:none;' ?>">
                                    <input type="time" name="start_<?= $day ?>" value="<?= substr($s['start_time'],0,5) ?>" class="form-group" style="width:auto; display:inline; padding:5px;">
                                    to
                                    <input type="time" name="end_<?= $day ?>" value="<?= substr($s['end_time'],0,5) ?>" class="form-group" style="width:auto; display:inline; padding:5px;">
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div style="margin-top:20px; text-align:right;">
                            <button type="submit" class="btn-primary btn-blue">Save Changes</button>
                        </div>
                    </form>
                </div>

                <div class="card" style="grid-column: span 1;">
                    <h3>Block Dates</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="block_date">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="blocked_date" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Reason (Optional)</label>
                            <input type="text" name="reason" placeholder="e.g. Vacation">
                        </div>
                        <button type="submit" class="btn-primary btn-outline" style="width:100%;">Block Date</button>
                    </form>

                    <h4 style="margin-top:30px;">Upcoming Blocked Dates</h4>
                    <?php if(count($blocked) > 0): ?>
                        <ul style="list-style:none; padding:0; margin:0;">
                            <?php foreach($blocked as $b): ?>
                                <li style="padding:10px; border:1px solid #ddd; margin-bottom:10px; border-radius:6px; display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <strong><?= htmlspecialchars($b['blocked_date']) ?></strong><br>
                                        <small style="color:#666;"><?= htmlspecialchars($b['reason']) ?></small>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="unblock_date">
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="btn-danger" style="padding:5px 10px;" title="Unblock"><i class="fas fa-trash"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="color:#666; font-size:0.9em;">No dates blocked.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleTime(day) {
            const timeDiv = document.getElementById('time_' + day);
            if (event.target.checked) {
                timeDiv.style.opacity = '1';
                timeDiv.style.pointerEvents = 'auto';
            } else {
                timeDiv.style.opacity = '0.3';
                timeDiv.style.pointerEvents = 'none';
            }
        }
    </script>
</body>
</html>
