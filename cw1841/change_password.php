<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_message'] = "Please log in to change your password.";
    header('Location: login.php');
    exit;
}

$message = '';
$success = false;

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user_id = $_SESSION['user_id'];

    // Get current user password for verification
    $user_sql = "SELECT Password, Username FROM users WHERE UserID = :user_id";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $user_stmt->execute();
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        $message = "Error: User not found.";
        include 'header.php';
        echo "<div class='alert alert-danger text-center'>$message</div>";
        include 'footer.php';
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = "❌ Error: All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $message = "❌ Error: New password and confirmation do not match.";
        } elseif ($current_password !== $user_data['Password']) {
            $message = "❌ Error: Current password is incorrect.";
        } elseif (strlen($new_password) < 3) {
            $message = "❌ Error: New password must be at least 3 characters long.";
        } else {
            // Update password
            $update_sql = "UPDATE users SET Password = :password WHERE UserID = :user_id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->bindParam(':password', $new_password);
            $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            
            if ($update_stmt->execute()) {
                $success = true;
                $message = "✅ Password changed successfully!";
            } else {
                $message = "❌ Error: Could not change password.";
            }
        }
    }

} catch (PDOException $e) {
    $message = "❌ Database Error: " . $e->getMessage();
}

include 'header.php';
?>

<div class="card shadow-lg">
    <div class="card-header text-center bg-warning text-dark">
        <h4 class="mb-0"><i class="fas fa-key"></i> Change Password</h4>
        <small class="text-muted">User: <?= htmlspecialchars($user_data['Username']) ?></small>
    </div>
    <div class="card-body">
        <?php if (isset($message) && $message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="change_password.php" id="passwordForm">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" required 
                               placeholder="Enter your current password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" required 
                               placeholder="Enter new password" minlength="3">
                        <div class="form-text">Minimum 3 characters</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" required 
                               placeholder="Confirm new password">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                        <a href="edit_profile.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </form>
        
        <div class="mt-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle"></i> Password Security Tips</h6>
                    <ul class="small mb-0">
                        <li>Use a combination of letters, numbers, and symbols</li>
                        <li>Avoid using easily guessable information</li>
                        <li>Don't reuse passwords from other sites</li>
                        <li>Consider using a password manager</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const currentPassword = document.querySelector('input[name="current_password"]').value;
    const newPassword = document.querySelector('input[name="new_password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    
    if (!currentPassword || !newPassword || !confirmPassword) {
        e.preventDefault();
        alert('Please fill in all password fields.');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New password and confirmation do not match.');
        return;
    }
    
    if (newPassword.length < 3) {
        e.preventDefault();
        alert('New password must be at least 3 characters long.');
        return;
    }
});
</script>

<?php
include 'footer.php';
?>