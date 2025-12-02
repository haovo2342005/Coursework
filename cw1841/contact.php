<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_message'] = "Please log in to contact admin.";
    header('Location: login.php');
    exit;
}

require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$result_message = '';
$to_admin_email = 'lbxqwe@gmail.com';

$default_sender_name = $_SESSION['name'] ?? $_SESSION['username'] ?? '';
$default_sender_email = $_SESSION['email'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sender_name = $_POST['sender_name'] ?? 'Anonymous User';
    $sender_email = $_POST['sender_email'] ?? '';
    $to = $to_admin_email; 
    $subject = $_POST["subject"] ?? 'Contact from Student Q&A';
    $message_body = $_POST["message"] ?? '';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lbxqwe@gmail.com'; 
        $mail->Password   = 'mivj qkdv otxx igpo';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('lbxqwe@gmail.com', 'Admin System');
        $mail->addAddress($to, 'Admin'); 
        $mail->addReplyTo($sender_email, $sender_name); 

        $mail->isHTML(false);
        $mail->Subject = "Contact: " . $subject;
        
        $full_message = "FROM: " . $sender_name . " (" . $sender_email . ")\n\n";
        $full_message .= "SUBJECT: " . $subject . "\n\n";
        $full_message .= "MESSAGE:\n" . $message_body;

        $mail->Body = $full_message;

        if ($mail->send()) {
            $result_message = '✅ Message sent to Admin successfully!';
        } else {
            $result_message = "❌ Error: Could not send message.";
        }
    } catch (Exception $e) {
        $result_message = "❌ Error: Could not send message. Mailer Error: {$mail->ErrorInfo}";
    }
}

include 'header.php';
?>

<div class="card shadow-lg">
    <div class="card-header text-center bg-primary text-white">
        <h4 class="mb-0">Contact Admin Support</h4>
        <small class="text-light">Logged in as: <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></small>
    </div>
    <div class="card-body">
        <?php if (isset($result_message) && $result_message): ?> 
            <div class="alert <?= strpos($result_message, '✅') !== false ? 'alert-success' : 'alert-danger' ?> text-center">
                <?= htmlspecialchars($result_message) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="contact.php">
            <input type="hidden" name="to" value="lbxqwe@gmail.com"> 

            <div class="mb-3">
                <label class="form-label">Your Name:</label>
                <input type="text" name="sender_name" class="form-control" required 
                       value="<?= htmlspecialchars($default_sender_name) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Your Email:</label>
                <input type="email" name="sender_email" class="form-control" required 
                       value="<?= htmlspecialchars($default_sender_email) ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Subject:</label>
                <input type="text" name="subject" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Message:</label>
                <textarea name="message" class="form-control" rows="6" required></textarea>
            </div>
            
            <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Email
                        </button>
            </div>
        </form>

        <div class="mt-5 pt-4 border-top">
            <h5 class="text-center mb-4">Other Ways to Connect</h5>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <h6 class="text-primary">Connect on Social Media:</h6>
                    <div class="d-flex flex-column gap-2">
                        <a href="https://www.facebook.com/haovoz.205" target="_blank" class="btn btn-outline-primary btn-sm d-flex align-items-center">
                            <i class="fab fa-facebook-f me-2"></i>Facebook
                        </a>
                        <a href="https://www.tiktok.com/@_hv.205" target="_blank" class="btn btn-outline-dark btn-sm d-flex align-items-center">
                            <i class="fab fa-tiktok me-2"></i>TikTok
                        </a>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <h6 class="text-primary">Other Contact Methods:</h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-phone me-2 text-muted"></i>
                            <span>Phone: +84 123 456 789</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock me-2 text-muted"></i>
                            <span>Response Time: 24-48 hours</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="home.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>