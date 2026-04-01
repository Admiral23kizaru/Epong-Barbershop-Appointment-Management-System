<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'forgot_password') {
    $email = sanitize($_POST['email']);
    
    // Check if email exists in any table
    $found = false;
    $stmt1 = $pdo->prepare("SELECT id FROM users WHERE email = ?"); $stmt1->execute([$email]);
    $stmt2 = $pdo->prepare("SELECT id FROM barbers WHERE email = ?"); $stmt2->execute([$email]);
    $stmt3 = $pdo->prepare("SELECT id FROM admins WHERE email = ?"); $stmt3->execute([$email]);
    
    if ($stmt1->fetch() || $stmt2->fetch() || $stmt3->fetch()) {
        $found = true;
    }
    
    if ($found) {
        $token = generateToken();
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);
        
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/barber/reset-password.php?token=" . $token;
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST; 
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER; 
            $mail->Password   = SMTP_PASS; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "Hello,<br><br>Click the link below to reset your password:<br><a href='{$resetLink}'>{$resetLink}</a><br><br>Link expires in 1 hour.";
            
            $mail->send();
            echo "<script>alert('Password reset link has been sent to your email.'); window.location.href='../index.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}'); window.location.href='../index.php';</script>";
        }
    } else {
        echo "<script>alert('Email not found.'); window.location.href='../index.php';</script>";
    }
}
?>
