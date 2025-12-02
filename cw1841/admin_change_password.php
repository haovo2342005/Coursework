<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? false)) {
    $_SESSION['redirect_message'] = "Access denied. Admin privileges required.";
    header('Location: login.php');
    exit;
}

$message = '';
$success = false;
$user_details = [];

$user_id = $_GET['id'] ?? null;
if (empty($user_id) || !is_numeric($user_id)) {
    $message = "❌ Error: Invalid user ID.";
    include 'header.php';
    echo "<div class='alert alert-danger text-center'>$message</div>";
    include 'footer.php';
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user details
    $user_sql = "SELECT UserID, Username, Name FROM users WHERE UserID = :user_id";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $user_stmt->execute();
    $user_details = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_details) {
        $message = "❌ Error: User not found.";
        include 'header.php';
        echo "<div class='alert alert-danger text-center'>$message</div>";
        include 'footer.php';
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($new_password) || empty($confirm_password)) {
            $message = "❌ Error: Both password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $message = "❌ Error: New password and confirmation do not match.";
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
                $message = "✅ User password changed successfully!";
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
    <div class="card-header text-center bg-info text-white">
        <h4 class="mb-0"><i class="fas fa-key"></i> Change User Password</h4>
        <small class="text-light">Admin: <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></small>
    </div>
    <div class="card-body">
        <?php if (isset($message) && $message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> You are changing password for: <strong><?= htmlspecialchars($user_details['Name']) ?></strong> (@<?= htmlspecialchars($user_details['Username']) ?>)
        </div>

        <form method="POST" action="admin_change_password.php?id=<?= $user_id ?>" id="passwordForm">
            <div class="row justify-content-center">
                <div class="col-md-8">
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
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-key"></i> Change User Password
                        </button>
                        <a href="manage_users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to User Management
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.querySelector('input[name="new_password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    
    if (!newPassword || !confirmPassword) {
        e.preventDefault();
        alert('Please fill in both password fields.');
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