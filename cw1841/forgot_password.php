<?php
// forgot_password.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra xem PHPMailer có tồn tại không
$phpmailer_path = __DIR__ . '/PHPMailer/src/PHPMailer.php';
if (!file_exists($phpmailer_path)) {
    die("PHPMailer not found. Please install PHPMailer first.");
}

require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

include 'config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    
    if (empty($username)) {
        $message = "Please enter your username.";
    } else {
        try {
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Tìm user theo username
            $stm = $pdo->prepare('SELECT Username, Password, Email, Name FROM users WHERE Username = :username');
            $stm->bindParam(':username', $username);
            $stm->execute();
            $user_data = $stm->fetch(PDO::FETCH_ASSOC);

            if ($user_data) {
                $user_email = $user_data['Email'];
                $user_password = $user_data['Password'];
                $user_name = $user_data['Name'];
                
                if (!empty($user_email)) {
                    // Gửi email với mật khẩu
                    $mail = new PHPMailer(true);

                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'lbxqwe@gmail.com'; // Thay bằng email của bạn
                        $mail->Password   = 'mivj qkdv otxx igpo'; // THAY BẰNG APP PASSWORD
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        
                        // Debug (tạm thời bật debug để xem lỗi)
                        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                        $mail->SMTPDebug = 0; // Tắt debug khi chạy ổn
                        
                        // Recipients
                        $mail->setFrom('lbxqwe@gmail.com', 'Student Q&A System');
                        $mail->addAddress($user_email, $user_name);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Recovery - Student Q&A';
                        
                        $email_body = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background: #007bff; color: white; padding: 15px; text-align: center; }
                                .content { padding: 20px; background: #f9f9f9; }
                                .password-box { background: #fff; border: 2px dashed #007bff; padding: 15px; margin: 15px 0; text-align: center; font-size: 18px; font-weight: bold; }
                                .footer { text-align: center; padding: 15px; font-size: 12px; color: #666; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h2>Student Q&A - Password Recovery</h2>
                                </div>
                                <div class='content'>
                                    <p>Hello <strong>{$user_name}</strong>,</p>
                                    <p>You have requested to recover your password for your account:</p>
                                    <p><strong>Username:</strong> {$username}</p>
                                    <p>Your password is:</p>
                                    <div class='password-box'>{$user_password}</div>
                                    <p>Please use this password to log in to your account.</p>
                                    <p>For security reasons, we recommend changing your password after logging in.</p>
                                </div>
                                <div class='footer'>
                                    <p>This is an automated message. Please do not reply to this email.</p>
                                    <p>&copy; 2025 Student Q&A System. All rights reserved.</p>
                                </div>
                            </div>
                        </body>
                        </html>";

                        $mail->Body = $email_body;
                        
                        // Alternative plain text version
                        $mail->AltBody = "Password Recovery - Student Q&A\n\n" .
                                        "Hello {$user_name},\n\n" .
                                        "You have requested to recover your password for your account:\n" .
                                        "Username: {$username}\n" .
                                        "Your password is: {$user_password}\n\n" .
                                        "Please use this password to log in to your account.\n" .
                                        "For security reasons, we recommend changing your password after logging in.";

                        if ($mail->send()) {
                            $success = true;
                            $message = "✅ Password has been sent to your email address: " . htmlspecialchars($user_email);
                        } else {
                            $message = "❌ Error: Could not send password recovery email.";
                        }
                    } catch (Exception $e) {
                        $message = "❌ Error: Could not send email. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $message = "❌ Error: No email address found for this username.";
                }
            } else {
                $message = "❌ Error: Username not found.";
            }

        } catch (PDOException $e) {
            $message = "❌ Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Student Q&A</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .forgot-container { max-width: 500px; margin-top: 10vh; }
        .instruction-box { background: #e7f3ff; border-left: 4px solid #007bff; }
    </style>
</head>
<body>

<div class="container forgot-container">
    <div class="card shadow-lg">
        <div class="card-header text-center bg-warning text-dark">
            <h4 class="mb-0"><i class="fas fa-key"></i> Password Recovery</h4>
        </div>
        <div class="card-body">
            <?php if (isset($message) && $message): ?>
                <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <div class="alert alert-info instruction-box">
                    <h6><i class="fas fa-info-circle"></i> How to recover your password:</h6>
                    <ol class="mb-0">
                        <li>Enter your username below</li>
                        <li>We will send your password to the email associated with your account</li>
                        <li>Check your email and use the password to log in</li>
                    </ol>
                </div>

                <form method="POST" action="forgot_password.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Enter your username" required
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-paper-plane"></i> Send Password to Email
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
                <a href="home.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>