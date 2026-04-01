<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('customer');
$user = getCurrentUser();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = $_POST['service_id'] ?? null;
    $barber_id = $_POST['barber_id'] ?? null;
    $date = $_POST['appointment_date'] ?? null;
    $time = $_POST['appointment_time'] ?? null;
    $notes = $_POST['notes'] ?? '';

    if ($service_id && $barber_id && $date && $time) {
        $stmt = $pdo->prepare("INSERT INTO appointments (customer_id, barber_id, service_id, appointment_date, appointment_time, status, notes) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$user['id'], $barber_id, $service_id, $date, $time, $notes]);
        $appointment_id = $pdo->lastInsertId();
        
        // Notification
        createNotification($pdo, $barber_id, 'barber', "New appointment booked by {$user['name']} on {$date} at {$time}");
        logAudit($pdo, $user['id'], 'customer', 'book_appointment', "Booked appointment ID {$appointment_id}");
        
        header("Location: booking-summary.php?id=" . $appointment_id);
        exit();
    }
}

// Fetch Services
$servicesStmt = $pdo->query("SELECT * FROM services WHERE status = 'active'");
$services = $servicesStmt->fetchAll();

// Fetch Barbers
$barbersStmt = $pdo->query("SELECT b.*, (SELECT COUNT(*) FROM appointments WHERE barber_id = b.id AND status='completed') as jobs_done FROM barbers b WHERE b.status = 'active'");
$barbers = $barbersStmt->fetchAll();

// Fetch Barber Schedules & Blocked Dates for JS
$schedulesStmt = $pdo->query("SELECT * FROM barber_schedules WHERE is_available = 1");
$schedules = $schedulesStmt->fetchAll();

$blockedStmt = $pdo->query("SELECT * FROM blocked_dates");
$blockedDates = $blockedStmt->fetchAll();

// Existing appointments to block timeslots
$appsStmt = $pdo->query("SELECT barber_id, appointment_date, appointment_time, s.duration_minutes 
                         FROM appointments a JOIN services s ON a.service_id = s.id 
                         WHERE a.status IN ('pending', 'confirmed') AND a.appointment_date >= CURDATE()");
$existingAppointments = $appsStmt->fetchAll();

// Encode to pass to JS
$jsServices = json_encode($services);
$jsBarbers = json_encode($barbers);
$jsSchedules = json_encode($schedules);
$jsBlocked = json_encode($blockedDates);
$jsAppointments = json_encode($existingAppointments);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Appointment - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .step-container { display: none; }
        .step-container.active { display: block; }
        .grid-card {
            border: 1px solid #ddd; padding: 15px; border-radius: 8px; cursor: pointer; text-align: center;
        }
        .grid-card:hover { border-color: var(--primary-blue); background: #f0f8ff; }
        .grid-card.selected { border-color: var(--primary-navy); background: #e6f0fa; font-weight: bold; }
        .time-slot {
            padding: 10px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; text-align: center;
        }
        .time-slot.selected { background: var(--primary-navy); color: #fff; }
        .time-slot.disabled { background: #eee; color: #aaa; cursor: not-allowed; text-decoration: line-through; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">Epong Barbershop</div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="booking.php" class="active"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../index.php" style="color:#d93025;" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            <form id="logout-form" action="../index.php" method="POST" style="display: none;">
                <input type="hidden" name="action" value="logout">
            </form>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h2>Book highly skilled professional</h2>
            </div>
            
            <div class="card">
                <form id="bookingForm" method="POST">
                    <input type="hidden" name="service_id" id="service_id" required>
                    <input type="hidden" name="barber_id" id="barber_id" required>
                    <input type="hidden" name="appointment_date" id="appointment_date" required>
                    <input type="hidden" name="appointment_time" id="appointment_time" required>

                    <!-- STEP 1: SERVICE -->
                    <div id="step-1" class="step-container active">
                        <h3>Step 1: Choose a Service</h3>
                        <?php if (count($services) > 0): ?>
                            <div class="grid-3">
                                <?php foreach ($services as $service): ?>
                                    <div class="grid-card service-card" data-id="<?= $service['id'] ?>">
                                        <h4><?= htmlspecialchars($service['name']) ?></h4>
                                        <p>₱<?= number_format($service['price'], 2) ?></p>
                                        <p style="font-size:0.9em; color:#666;"><i class="fas fa-clock"></i> <?= $service['duration_minutes'] ?> mins</p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top:20px; text-align:right;">
                                <button type="button" class="btn-primary btn-blue" id="next-1" disabled>Next <i class="fas fa-arrow-right"></i></button>
                            </div>
                        <?php else: ?>
                            <p style="color:#666; padding: 20px; text-align: center; border: 1px dashed #ccc; border-radius: 8px;">No services are currently available. Please check back later.</p>
                        <?php endif; ?>
                    </div>

                    <!-- STEP 2: BARBER -->
                    <div id="step-2" class="step-container">
                        <h3>Step 2: Choose a Barber</h3>
                        <?php if (count($barbers) > 0): ?>
                            <div class="grid-3">
                                <?php foreach ($barbers as $barber): ?>
                                    <div class="grid-card barber-card" data-id="<?= $barber['id'] ?>">
                                        <img src="<?= $barber['profile_img'] ? '../'.htmlspecialchars($barber['profile_img']) : '../assets/images/default.jpg' ?>" alt="" style="width:80px;height:80px;border-radius:50%;margin-bottom:10px;">
                                        <h4><?= htmlspecialchars($barber['name']) ?></h4>
                                        <p style="font-size:0.9em; color:#666;"><?= htmlspecialchars($barber['specialization']) ?></p>
                                        <p style="font-size:0.85em; color:#f5b041;"><i class="fas fa-star"></i> 4.9 (<?= $barber['jobs_done'] ?> jobs)</p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top:20px; display:flex; justify-content:space-between;">
                                <button type="button" class="btn-outline back-btn" data-step="1"><i class="fas fa-arrow-left"></i> Back</button>
                                <button type="button" class="btn-primary btn-blue" id="next-2" disabled>Next <i class="fas fa-arrow-right"></i></button>
                            </div>
                        <?php else: ?>
                            <p style="color:#666; padding: 20px; text-align: center; border: 1px dashed #ccc; border-radius: 8px;">No barbers are currently available. Please check back later.</p>
                            <div style="margin-top:20px; text-align:left;">
                                <button type="button" class="btn-outline back-btn" data-step="1"><i class="fas fa-arrow-left"></i> Back</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- STEP 3: DATE & TIME -->
                    <div id="step-3" class="step-container">
                        <h3>Step 3: Select Date & Time</h3>
                        <div style="margin-bottom:20px;">
                            <label style="font-weight:bold;margin-bottom:10px;display:block;">Select Date:</label>
                            <input type="date" id="date_picker" min="<?= date('Y-m-d') ?>" class="form-group" style="padding:10px; width:100%; max-width:300px;">
                        </div>
                        <div style="margin-bottom:20px;">
                            <label style="font-weight:bold;margin-bottom:10px;display:block;">Available Time Slots:</label>
                            <div id="time_slots" class="grid-4">
                                <p style="color:#666;">Please select a date first.</p>
                            </div>
                        </div>
                        <div style="margin-top:20px; display:flex; justify-content:space-between;">
                            <button type="button" class="btn-outline back-btn" data-step="2"><i class="fas fa-arrow-left"></i> Back</button>
                            <button type="button" class="btn-primary btn-blue" id="next-3" disabled>Next <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>

                    <!-- STEP 4: CONFIRMATION -->
                    <div id="step-4" class="step-container">
                        <h3>Step 4: Confirm Booking</h3>
                        <div class="card" style="background:#f9f9f9; border:1px solid #ddd;">
                            <p><strong>Service:</strong> <span id="conf-service"></span></p>
                            <p><strong>Barber:</strong> <span id="conf-barber"></span></p>
                            <p><strong>Date:</strong> <span id="conf-date"></span></p>
                            <p><strong>Time:</strong> <span id="conf-time"></span></p>
                            <div class="form-group" style="margin-top:15px;">
                                <label>Notes (Optional):</label>
                                <textarea name="notes" rows="3" placeholder="Tell us if you have specific requests..."></textarea>
                            </div>
                        </div>
                        <div style="margin-top:20px; display:flex; justify-content:space-between;">
                            <button type="button" class="btn-outline back-btn" data-step="3"><i class="fas fa-arrow-left"></i> Back</button>
                            <button type="submit" class="btn-navy">Confirm Booking</button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        const services = <?= $jsServices ?>;
        const barbers = <?= $jsBarbers ?>;
        const schedules = <?= $jsSchedules ?>;
        const blocked = <?= $jsBlocked ?>;
        const appts = <?= $jsAppointments ?>;

        let selectedService = null;
        let selectedBarber = null;
        let selectedDate = null;
        let selectedTime = null;

        // Step 1: Service Selection
        document.querySelectorAll('.service-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedService = services.find(s => s.id == this.dataset.id);
                document.getElementById('service_id').value = selectedService.id;
                document.getElementById('next-1').disabled = false;
            });
        });

        // Step 2: Barber Selection
        document.querySelectorAll('.barber-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.barber-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedBarber = barbers.find(b => b.id == this.dataset.id);
                document.getElementById('barber_id').value = selectedBarber.id;
                document.getElementById('next-2').disabled = false;
                
                // reset date & time
                document.getElementById('date_picker').value = '';
                document.getElementById('time_slots').innerHTML = '<p style="color:#666;">Please select a date first.</p>';
                selectedDate = null; selectedTime = null;
                document.getElementById('next-3').disabled = true;
            });
        });

        // Step 3: Date & Time Setup
        const datePicker = document.getElementById('date_picker');
        datePicker.addEventListener('change', function() {
            selectedDate = this.value;
            generateTimeSlots();
        });

        function generateTimeSlots() {
            if(!selectedDate || !selectedBarber) return;
            const container = document.getElementById('time_slots');
            container.innerHTML = '';
            
            // check blocked
            const isBlocked = blocked.some(b => b.barber_id == selectedBarber.id && b.blocked_date == selectedDate);
            if(isBlocked) {
                container.innerHTML = '<p style="color:#d93025;">Barber is not available on this date.</p>';
                return;
            }

            const days = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
            const dateObj = new Date(selectedDate);
            const dayOfWeek = days[dateObj.getDay()];

            // find schedule for this day
            const sched = schedules.find(s => s.barber_id == selectedBarber.id && s.day_of_week === dayOfWeek);
            
            if(!sched || !sched.start_time || !sched.end_time || sched.is_available == 0) {
                container.innerHTML = '<p style="color:#d93025;">Barber is not taking appointments on this day.</p>';
                return;
            }

            // Generate slots (e.g., every 30 mins)
            let startParts = sched.start_time.split(':');
            let endParts = sched.end_time.split(':');
            let currentDate = new Date(selectedDate);
            
            let currentTemp = new Date(selectedDate);
            currentTemp.setHours(parseInt(startParts[0]), parseInt(startParts[1]), 0);
            
            let endTemp = new Date(selectedDate);
            endTemp.setHours(parseInt(endParts[0]), parseInt(endParts[1]), 0);

            const duration = selectedService ? parseInt(selectedService.duration_minutes) : 30;

            let hasSlots = false;
            while(currentTemp.getTime() + (duration * 60000) <= endTemp.getTime()) {
                const hh = currentTemp.getHours().toString().padStart(2, '0');
                const mm = currentTemp.getMinutes().toString().padStart(2, '0');
                const timeStr = `${hh}:${mm}:00`;
                const displayTime = currentTemp.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

                // Check collisions with appts
                let isTaken = false;
                appts.forEach(ap => {
                    if(ap.barber_id == selectedBarber.id && ap.appointment_date == selectedDate) {
                        let apStart = new Date(selectedDate + 'T' + ap.appointment_time);
                        let apEnd = new Date(apStart.getTime() + parseInt(ap.duration_minutes)*60000);
                        
                        let myStart = new Date(currentTemp.getTime());
                        let myEnd = new Date(currentTemp.getTime() + duration*60000);

                        // overlap logic
                        if((myStart < apEnd && myEnd > apStart)) {
                            isTaken = true;
                        }
                    }
                });

                // Create slot element
                const div = document.createElement('div');
                div.className = `time-slot ${isTaken ? 'disabled' : ''}`;
                div.innerText = displayTime;
                
                if(!isTaken) {
                    div.dataset.time = timeStr;
                    div.addEventListener('click', function() {
                        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedTime = this.dataset.time;
                        document.getElementById('appointment_date').value = selectedDate;
                        document.getElementById('appointment_time').value = selectedTime;
                        document.getElementById('next-3').disabled = false;
                    });
                    hasSlots = true;
                }
                
                container.appendChild(div);

                // increment by 30 mins
                currentTemp.setMinutes(currentTemp.getMinutes() + 30);
            }

            if(!hasSlots) {
                container.innerHTML = '<p style="color:#d93025;">Fully booked on this date.</p>';
            }
        }

        // Navigation
        document.getElementById('next-1').addEventListener('click', () => switchStep(2));
        document.getElementById('next-2').addEventListener('click', () => switchStep(3));
        document.getElementById('next-3').addEventListener('click', () => {
            document.getElementById('conf-service').innerText = selectedService.name;
            document.getElementById('conf-barber').innerText = selectedBarber.name;
            document.getElementById('conf-date').innerText = selectedDate;
            document.getElementById('conf-time').innerText = selectedTime;
            switchStep(4);
        });

        document.querySelectorAll('.back-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                switchStep(this.dataset.step);
            });
        });

        function switchStep(step) {
            document.querySelectorAll('.step-container').forEach(c => c.classList.remove('active'));
            document.getElementById('step-' + step).classList.add('active');
        }
    </script>
</body>
</html>
