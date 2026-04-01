<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('barber');
$user = getCurrentUser();

$stmt = $pdo->prepare("SELECT a.*, c.name as customer_name, c.email as customer_email, s.name as service_name, s.duration_minutes 
                       FROM appointments a 
                       JOIN users c ON a.customer_id = c.id 
                       JOIN services s ON a.service_id = s.id 
                       WHERE a.barber_id = ? 
                       ORDER BY a.appointment_date ASC, a.appointment_time ASC");
$stmt->execute([$user['id']]);
$appointments = $stmt->fetchAll();

$pending = array_filter($appointments, fn($a) => $a['status'] === 'pending');
$confirmed = array_filter($appointments, fn($a) => $a['status'] === 'confirmed');
$completed = array_filter($appointments, fn($a) => $a['status'] === 'completed');

$jsAppointments = json_encode($appointments);

function renderAppTable($apps, $title) {
    if(count($apps) === 0) {
        return "<div style='max-width:1000px; margin:0 auto;'><h3 style='font-size:1.2em; font-weight:700; color:#111827; margin:0 0 20px 0;'>$title</h3><p style='color:#9ca3af; text-align:center; padding: 40px 0;'>No appointments found.</p></div>";
    }
    
    $html = '<div style="max-width:1000px; margin:0 auto;">';
    $html .= '<h3 style="font-size:1.2em; font-weight:700; color:#111827; margin:0 0 20px 0;">'.$title.'</h3>';
    $html .= '<div style="display:flex; flex-direction:column; gap:16px;">';
    
    foreach($apps as $app) {
        $badgeColor = '#d97706'; $badgeBg = '#fef3c7'; // pending
        if($app['status'] == 'confirmed') { $badgeColor = '#2563eb'; $badgeBg = '#dbeafe'; }
        if($app['status'] == 'completed') { $badgeColor = '#059669'; $badgeBg = '#d1fae5'; } // done badge
        if($app['status'] == 'cancelled') { $badgeColor = '#dc2626'; $badgeBg = '#fee2e2'; }
        
        $statusText = $app['status'] === 'completed' ? 'done' : $app['status'];

        $html .= '<div style="background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:24px; position:relative; box-shadow:0 1px 3px rgba(0,0,0,0.02)">';
        
        // Top row
        $html .= '<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">';
        $html .= '<span style="background:#f3f4f6; color:#9ca3af; font-size:0.75em; padding:4px 8px; border-radius:6px; font-weight:600;">#'.$app['id'].'</span>';
        
        // Right section (Badge + Actions)
        $html .= '<div style="display:flex; gap:15px; align-items:center;">';
        $html .= '<span style="background:'.$badgeBg.'; color:'.$badgeColor.'; padding:6px 14px; border-radius:9999px; font-size:0.75em; font-weight:600;">'.strtolower($statusText).'</span>';
        
        $html .= '<form method="POST" action="appointments.php" style="display:flex; gap:8px; margin:0;">';
        $html .= '<input type="hidden" name="id" value="'.$app['id'].'">';
        if($app['status'] === 'pending') {
            $html .= '<button type="submit" name="action" value="confirm" style="background:#10b981; color:#fff; border:none; border-radius:6px; padding:6px 12px; cursor:pointer;" title="Confirm"><i class="fas fa-check"></i></button>';
        }
        if($app['status'] === 'confirmed') {
            $html .= '<button type="submit" name="action" value="complete" style="background:#2563eb; color:#fff; border:none; border-radius:6px; padding:6px 12px; cursor:pointer;" title="Mark Completed"><i class="fas fa-flag-checkered"></i></button>';
        }
        if(in_array($app['status'], ['pending','confirmed'])) {
            $html .= '<button type="submit" name="action" value="cancel" style="background:#ef4444; color:#fff; border:none; border-radius:6px; padding:6px 12px; cursor:pointer;" onclick="return confirm(\'Cancel this appointment?\')" title="Cancel"><i class="fas fa-times"></i></button>';
        }
        $html .= '</form>';
        $html .= '</div>';
        $html .= '</div>';

        // Content
        $html .= '<h3 style="margin:0 0 6px 0; font-size:1em; font-weight:700; color:#111827;">'.htmlspecialchars($app['customer_name']).'</h3>';
        $html .= '<p style="margin:0 0 12px 0; font-size:0.85em; color:#9ca3af;">'.htmlspecialchars($app['customer_email']).'</p>';
        $html .= '<p style="margin:0 0 16px 0; font-size:0.9em; color:#4b5563; font-weight:500;">'.htmlspecialchars($app['service_name']).'</p>';
        
        // Footer date and time
        $dateStr = date('n/j/Y', strtotime($app['appointment_date'])); 
        $timeStr = date('g:i A', strtotime($app['appointment_time'])); 
        
        $html .= '<div style="display:flex; gap:16px; font-size:0.85em; color:#6b7280; font-weight:500; align-items:center;">';
        $html .= '<span><i class="far fa-calendar" style="margin-right:6px; color:#9ca3af; font-size:1.1em;"></i>'.$dateStr.'</span>';
        $html .= '<span><i class="far fa-clock" style="margin-right:6px; color:#9ca3af; font-size:1.1em;"></i>'.$timeStr.' <span style="margin:0 6px; color:#d1d5db;">•</span> '.$app['duration_minutes'].' mins</span>';
        $html .= '</div>';
        
        $html .= '</div>';
    }
    $html .= '</div></div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barber Dashboard - Epong Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f7f9fc; margin: 0; font-family: 'Inter', sans-serif; color: #111827; }
        .nav-tab { background: #ffffff; color: #4b5563; border: 1px solid #e5e7eb; padding: 10px 20px; border-radius: 8px; font-size: 0.9em; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; white-space: nowrap; outline: none; }
        .nav-tab:hover { border-color: #d1d5db; background: #f9fafb; }
        .nav-tab.active { background: #111827; color: #ffffff; border-color: #111827; }
        
        .view-section { display: none; }
        .view-section.active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        .cal-day { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; margin: 0 auto; border-radius: 8px; cursor: pointer; font-weight:500; font-size: 0.9em; }
        .cal-day:hover:not(.empty) { background: #f3f4f6; }
        .cal-day.past { color: #d1d5db; font-weight: 400; }
        .cal-day.future { color: #111827; font-weight: 500; }
        .cal-day.selected { background: #111827 !important; color: #ffffff !important; font-weight: 600 !important; }
        .cal-day.has-appt { position: relative; }
        .cal-day.has-appt::after { content: ''; position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; background: #2563eb; border-radius: 50%; }
        .cal-day.selected.has-appt::after { background: #fff; }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <header style="background: #ffffff; border-bottom: 1px solid #eef0f3; padding: 15px 50px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="margin: 0; font-size: 1.25em; font-weight: 700; color: #111827;">Barber Dashboard</h1>
            <p style="margin: 3px 0 0 0; font-size: 0.8em; color: #6b7280; font-weight: 400;">Manage your appointments</p>
        </div>
        <div style="display: flex; gap: 30px; align-items: center; font-size: 0.85em; font-weight: 600;">
            <a href="profile.php" style="color: #4b5563; text-decoration: none; display: flex; align-items: center; gap: 8px;"><i class="far fa-user"></i> Profile</a>
            <a href="../includes/auth.php?action=logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="color: #ef4444; text-decoration: none; display: flex; align-items: center; gap: 8px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <form id="logout-form" action="../index.php" method="POST" style="display: none;">
                <input type="hidden" name="action" value="logout">
            </form>
        </div>
    </header>

    <!-- Main Content Container -->
    <main style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
        
        <?php if (isset($_GET['msg'])): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-size:0.9em; font-weight:500;">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- Filter Tabs Row -->
        <div style="display: flex; gap: 12px; margin-bottom: 40px; overflow-x: auto; padding-bottom:5px;">
            <button class="nav-tab active" data-target="tab-calendar"><i class="far fa-calendar"></i> Calendar</button>
            <button class="nav-tab" data-target="tab-all"><i class="fas fa-inbox"></i> All Appointments</button>
            <button class="nav-tab" data-target="tab-pending">Pending</button>
            <button class="nav-tab" data-target="tab-confirmed">Confirmed</button>
            <button class="nav-tab" data-target="tab-completed">Completed</button>
        </div>

        <!-- Sections -->
        
        <!-- Calendar Section -->
        <div id="tab-calendar" class="view-section active">
            <div style="background: #ffffff; border-radius: 16px; border: 1px solid #f3f4f6; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.03), 0 4px 6px -2px rgba(0,0,0,0.02); max-width: 650px; margin: 0 auto; padding: 30px;">
                <!-- Calendar Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
                    <div style="font-weight: 600; color: #111827; font-size: 1.1em; display: flex; align-items: center; gap: 10px;"><i class="far fa-calendar"></i> Calendar</div>
                    <div style="display: flex; align-items: center; gap: 20px; font-size: 0.9em; font-weight: 600; color: #374151;">
                        <i class="fas fa-arrow-left" style="cursor: pointer; color: #9ca3af; padding:5px;" onclick="changeMonth(-1)"></i>
                        <span id="currentMonthYear"></span>
                        <i class="fas fa-arrow-right" style="cursor: pointer; color: #9ca3af; padding:5px;" onclick="changeMonth(1)"></i>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 15px 10px; text-align: center; font-size: 0.85em; margin-bottom: 20px;">
                    <div style="color: #9ca3af; font-weight: 500;">Su</div>
                    <div style="color: #9ca3af; font-weight: 500;">Mo</div>
                    <div style="color: #9ca3af; font-weight: 500;">Tu</div>
                    <div style="color: #9ca3af; font-weight: 500;">We</div>
                    <div style="color: #9ca3af; font-weight: 500;">Th</div>
                    <div style="color: #9ca3af; font-weight: 500;">Fr</div>
                    <div style="color: #9ca3af; font-weight: 500;">Sa</div>
                </div>
                
                <div id="calendar-body" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 15px 10px; text-align: center;">
                    <!-- JS Grid injected here. -->
                </div>
                
                <!-- Appointments for the Selected Day -->
                <div style="margin-top: 40px; text-align: left; border-top: 1px solid #f3f4f6; padding-top: 25px;">
                    <h4 style="margin:0 0 15px 0; font-size:0.95em; color:#111827;">Scheduled for <span id="selected-date-text">...</span></h4>
                    <div id="day-list"><p style="color:#9ca3af; font-size: 0.9em;">Select a date to view appointments.</p></div>
                </div>
            </div>
        </div>

        <div id="tab-all" class="view-section"><?= renderAppTable($appointments, 'All Appointments') ?></div>
        <div id="tab-pending" class="view-section"><?= renderAppTable($pending, 'Pending Appointments') ?></div>
        <div id="tab-confirmed" class="view-section"><?= renderAppTable($confirmed, 'Confirmed Appointments') ?></div>
        <div id="tab-completed" class="view-section"><?= renderAppTable($completed, 'Completed Appointments') ?></div>

    </main>

    <script>
        // Tab routing
        const tabs = document.querySelectorAll('.nav-tab');
        const sections = document.querySelectorAll('.view-section');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                sections.forEach(s => s.classList.remove('active'));
                document.getElementById(tab.dataset.target).classList.add('active');
            });
        });

        // Calendar Logic
        const appointments = <?= $jsAppointments ?>;
        const today = new Date();
        // Zero-out time on today for safe comparisons
        const todayZero = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        
        let currentMonth = today.getMonth();
        let currentYear = today.getFullYear();
        let selectedDateStr = null;
        
        function changeMonth(offset) {
            currentMonth += offset;
            if (currentMonth > 11) { currentMonth = 0; currentYear++; }
            else if (currentMonth < 0) { currentMonth = 11; currentYear--; }
            renderCalendar();
        }

        function renderCalendar() {
            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            
            document.getElementById('currentMonthYear').innerText = monthNames[currentMonth] + " " + currentYear;
            
            let html = "";
            for (let i = 0; i < firstDay; i++) {
                html += `<div class="cal-day empty"></div>`;
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const dateStr = `${currentYear}-${String(currentMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                const hasAppt = appointments.some(a => a.appointment_date === dateStr);
                
                let stateClass = '';
                const iterDate = new Date(currentYear, currentMonth, d);
                
                if (iterDate < todayZero) {
                    stateClass = 'past';
                } else {
                    stateClass = 'future';
                }

                if (selectedDateStr === dateStr) {
                    stateClass += ' selected';
                }

                if (hasAppt) {
                    stateClass += ' has-appt';
                }
                
                html += `<div class="cal-day ${stateClass}" onclick="selectDate('${dateStr}')">${d}</div>`;
            }
            
            document.getElementById('calendar-body').innerHTML = html;
        }

        function selectDate(dateStr) {
            selectedDateStr = dateStr;
            renderCalendar(); // re-render to apply 'selected' class
            
            // Format to something like "Sunday, March 29"
            const dateObj = new Date(dateStr + 'T00:00:00'); // Explicit time to avoid timezone offset shifts
            document.getElementById('selected-date-text').innerText = dateObj.toLocaleDateString('en-US', {weekday: 'long', month: 'long', day: 'numeric'});
            
            // Render the appointments for this date
            const apps = appointments.filter(a => a.appointment_date === dateStr);
            let html = '';
            if(apps.length === 0) {
                html = '<p style="color:#9ca3af; font-size:0.9em;">No appointments scheduled for this date.</p>';
            } else {
                apps.forEach(app => {
                    let badgeColor = '#f59e0b'; let badgeBg = '#fef3c7';
                    if(app.status == 'confirmed') { badgeColor = '#2563eb'; badgeBg = '#dbeafe'; }
                    if(app.status == 'completed') { badgeColor = '#10b981'; badgeBg = '#d1fae5'; }
                    if(app.status == 'cancelled') { badgeColor = '#ef4444'; badgeBg = '#fee2e2'; }

                    html += `
                        <div style="padding:15px; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong style="color:#111827; font-size:1.05em;">${app.appointment_time.substring(0,5)}</strong> <span style="color:#6b7280; font-size:0.9em; margin-left:8px;">- ${app.service_name} (${app.duration_minutes}m)</span>
                                <div style="margin-top:4px; font-size:0.9em; font-weight:500; color:#374151;">${app.customer_name}</div>
                            </div>
                            <span style="background:${badgeBg}; color:${badgeColor}; padding:4px 10px; border-radius:9999px; font-size:0.75em; font-weight:600;">${app.status.toUpperCase()}</span>
                        </div>
                    `;
                });
            }
            document.getElementById('day-list').innerHTML = html;
        }

        document.addEventListener('DOMContentLoaded', () => {
            selectedDateStr = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;
            renderCalendar();
            selectDate(selectedDateStr);
        });
    </script>
</body>
</html>
