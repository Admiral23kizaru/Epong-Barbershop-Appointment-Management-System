<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    logout();
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirectBasedOnRole($_SESSION['role']);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
        } else {
            // Check admins
            $stmt = $pdo->prepare("SELECT id, name, password, role FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && verifyPassword($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['role'] = $admin['role'];
                $_SESSION['name'] = $admin['name'];
                redirectBasedOnRole($admin['role']);
            }

            // Check barbers
            $stmt = $pdo->prepare("SELECT id, name, password, status FROM barbers WHERE email = ?");
            $stmt->execute([$email]);
            $barber = $stmt->fetch();

            if ($barber && verifyPassword($password, $barber['password'])) {
                if ($barber['status'] !== 'active') {
                    $error = "Your account is inactive.";
                } else {
                    $_SESSION['user_id'] = $barber['id'];
                    $_SESSION['role'] = 'barber';
                    $_SESSION['name'] = $barber['name'];
                    redirectBasedOnRole('barber');
                }
            }

            // Check users (customers)
            if (empty($error)) {
                $stmt = $pdo->prepare("SELECT id, name, password, status FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && verifyPassword($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $error = "Your account is inactive.";
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = 'customer';
                        $_SESSION['name'] = $user['name'];
                        redirectBasedOnRole('customer');
                    }
                } else if (!$admin && !$barber) {
                    $error = "Invalid email or password.";
                }
            }
        }
    } elseif ($action === 'register') {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $role_reg = $_POST['role_reg'] ?? 'customer';
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($name) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // check if email exists in any table
            $stmt1 = $pdo->prepare("SELECT id FROM users WHERE email = ?"); $stmt1->execute([$email]);
            $stmt2 = $pdo->prepare("SELECT id FROM barbers WHERE email = ?"); $stmt2->execute([$email]);
            $stmt3 = $pdo->prepare("SELECT id FROM admins WHERE email = ?"); $stmt3->execute([$email]);

            if ($stmt1->fetch() || $stmt2->fetch() || $stmt3->fetch()) {
                $error = "Email is already registered.";
            } else {
                $hashed_password = hashPassword($password);
                if ($role_reg === 'barber') {
                    // Removed barber registration logic
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $email, $hashed_password]);
                    $success = "Account created. You can now login.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Epong Barbershop</title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="landing-page" style="background-color: #2b2b2b; color: #ffffff; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; min-height: 100vh; margin: 0; border-top: none;">

    <div style="text-align: center; max-width: 600px; padding: 40px 20px;">
        <h1 style="font-size: 2.2em; font-weight: 600; margin-bottom: 5px;">Epong Barbershop</h1>
        <p style="font-size: 1em; font-style: italic; color: #bbbbbb; margin-top: 0; margin-bottom: 20px;">"Clean cuts. Fresh confidence."</p>

        <p style="font-size: 0.85em; color: #aaaaaa; line-height: 1.5; margin-bottom: 30px;">
            Experience professional grooming with skilled barbers, modern styles,<br>and a relaxing atmosphere.
        </p>

        <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; flex-wrap: wrap;">
            <div style="background-color: #404040; border-radius: 8px; padding: 20px; width: 130px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #777; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                    <i class="fas fa-cut" style="color: #fff;"></i>
                </div>
                <div style="font-size: 0.8em; color: #ddd;">Skilled Barbers</div>
            </div>
            
            <div style="background-color: #404040; border-radius: 8px; padding: 20px; width: 130px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #777; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                    <i class="fas fa-bolt" style="color: #fff;"></i>
                </div>
                <div style="font-size: 0.8em; color: #ddd;">Fast & Clean<br>Service</div>
            </div>
            
            <div style="background-color: #404040; border-radius: 8px; padding: 20px; width: 130px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="width: 40px; height: 40px; border-radius: 50%; border: 1px solid #777; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                    <i class="fas fa-check-circle" style="color: #fff;"></i>
                </div>
                <div style="font-size: 0.8em; color: #ddd;">Affordable Price<br>(₱70 only)</div>
            </div>
        </div>

        <button onclick="openModal('login-view')" style="background-color: #ffffff; color: #2b2b2b; border: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; font-size: 0.9em; cursor: pointer; transition: background 0.2s; margin-bottom: 30px; width: 200px;">
            Get Started
        </button>

        <div style="background-color: #4a4a4a; border-radius: 8px; padding: 20px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 40px;">
            <p style="font-size: 0.85em; font-style: italic; color: #dddddd; margin-top: 0; margin-bottom: 10px;">"Best barbershop in Labuyo! Clean cuts and friendly staff."</p>
            <div style="color: #f5b041; font-size: 1em;">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
            </div>
        </div>
    </div>

    <footer style="text-align: center; padding-bottom: 30px;">
        <div style="font-size: 0.8em; font-weight: 600; color: #dddddd; margin-bottom: 5px;">Epong Barbershop</div>
        <div style="font-size: 0.75em; color: #aaaaaa;">© 2022 Epong Barbershop. All rights reserved.</div>
    </footer>

    <!-- Modal -->
    <div class="modal" id="auth-modal" <?= (!empty($error) || !empty($success)) ? 'style="display:flex;"' : '' ?>>
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <!-- Login View -->
            <div id="login-view">
                <h2 style="margin-top:0;">Welcome Back</h2>
                <p style="color:#666; margin-bottom: 20px;">Sign in to continue</p>
                <form method="POST" action="index.php">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="login-password" required>
                            <i class="fas fa-eye eye-toggle" onclick="togglePassword('login-password')"></i>
                        </div>
                    </div>
                    <div style="text-align:right; margin-bottom:20px;">
                        <a href="#" onclick="switchView('forgot-view')" style="font-size:0.9em;">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn-navy">Sign In</button>
                </form>
                <div style="text-align:center; margin-top: 20px; font-size:0.9em;">
                    Don't have an account? <a href="#" onclick="switchView('register-view')">Sign up</a>
                </div>
            </div>

            <!-- Register View -->
            <div id="register-view" style="display:none;">
                <h2 style="margin-top:0;">Create Account</h2>
                <p style="color:#666; margin-bottom: 20px;">Sign up to get started</p>
                <form method="POST" action="index.php">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Register as</label>
                        <select name="role_reg">
                            <option value="customer">Customer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="reg-password" required>
                            <i class="fas fa-eye eye-toggle" onclick="togglePassword('reg-password')"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="reg-confirm-password" required>
                            <i class="fas fa-eye eye-toggle" onclick="togglePassword('reg-confirm-password')"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn-navy">Create Account</button>
                </form>
                <div style="text-align:center; margin-top: 20px; font-size:0.9em;">
                    Already have an account? <a href="#" onclick="switchView('login-view')">Sign in</a>
                </div>
            </div>

            <!-- Forgot Password View -->
            <div id="forgot-view" style="display:none;">
                <a href="#" onclick="switchView('login-view')" style="display:inline-block; margin-bottom:15px; color:#666;"><i class="fas fa-arrow-left"></i> Back to login</a>
                <h2 style="margin-top:0;">Reset Password</h2>
                <p style="color:#666; margin-bottom: 20px;">Enter your email to receive a reset link</p>
                <form method="POST" action="includes/mailer.php">
                    <input type="hidden" name="action" value="forgot_password">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required>
                    </div>
                    <button type="submit" class="btn-navy">Send Reset Link</button>
                </form>
            </div>

        </div>
    </div>

    <script>
        const modal = document.getElementById('auth-modal');
        function openModal(viewId) {
            modal.style.display = 'flex';
            switchView(viewId);
        }
        function closeModal() {
            modal.style.display = 'none';
            // Only hide modal, prevent wiping out context
        }
        function switchView(viewId) {
            document.getElementById('login-view').style.display = 'none';
            document.getElementById('register-view').style.display = 'none';
            document.getElementById('forgot-view').style.display = 'none';
            document.getElementById(viewId).style.display = 'block';
        }
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
            } else {
                input.type = "password";
            }
        }
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register' && !empty($error)): ?>
            switchView('register-view');
        <?php elseif (!empty($success)): ?>
            alert("<?= addslashes($success) ?>");
        <?php endif; ?>
    </script>
</body>
</html>
