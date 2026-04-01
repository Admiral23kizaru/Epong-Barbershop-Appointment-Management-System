<?php
require_once __DIR__ . '/includes/db.php';
$barbers = $pdo->query("SELECT id FROM barbers")->fetchAll();
$days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
$schedStmt = $pdo->prepare("INSERT IGNORE INTO barber_schedules (barber_id, day_of_week, start_time, end_time) VALUES (?, ?, '09:00:00', '18:00:00')");
foreach($barbers as $b) {
    foreach($days as $day) {
        $schedStmt->execute([$b['id'], $day]);
    }
}
echo "Barber schedules seeded!";
