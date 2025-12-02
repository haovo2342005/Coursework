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
    $user_sql = "SELECT UserID, Username, Name, Email, Position, DateOfBirth, PhoneNumber 
                 FROM users WHERE UserID = :user_id";
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
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        $phone_number = trim($_POST['phone_number'] ?? '');

        // Basic validation
        if (empty($name) || empty($email)) {
            $message = "❌ Error: Name and Email are required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "❌ Error: Please enter a valid email address.";
        } else {
            // Check if email already exists (excluding current user)
            $email_check_sql = "SELECT UserID FROM users WHERE Email = :email AND UserID != :user_id";
            $email_check_stmt = $pdo->prepare($email_check_sql);
            $email_check_stmt->bindParam(':email', $email);
            $email_check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $email_check_stmt->execute();

            if ($email_check_stmt->rowCount() > 0) {
                $message = "❌ Error: This email address is already registered by another user.";
            } else {
                // Update user profile
                $update_sql = "UPDATE users SET 
                              Name = :name, 
                              Email = :email, 
                              Position = :position, 
                              DateOfBirth = :date_of_birth, 
                              PhoneNumber = :phone_number
                              WHERE UserID = :user_id";
                
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->bindParam(':name', $name);
                $update_stmt->bindParam(':email', $email);
                $update_stmt->bindParam(':position', $position);
                $update_stmt->bindParam(':date_of_birth', $date_of_birth);
                $update_stmt->bindParam(':phone_number', $phone_number);
                $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

                if ($update_stmt->execute()) {
                    $success = true;
                    $message = "✅ User profile updated successfully!";
                    
                    // Refresh user details
                    $user_stmt->execute();
                    $user_details = $user_stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = "❌ Error: Could not update user profile.";
                }
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
        <h4 class="mb-0"><i class="fas fa-user-edit"></i> Edit User Profile</h4>
        <small class="text-muted">Admin: <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></small>
    </div>
    <div class="card-body">
        <?php if (isset($message) && $message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You are editing profile of: <strong><?= htmlspecialchars($user_details['Name']) ?></strong> (@<?= htmlspecialchars($user_details['Username']) ?>)
        </div>

        <form method="POST" action="admin_edit_user.php?id=<?= $user_id ?>" id="profileForm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user_details['Username']) ?>" readonly>
                    <div class="form-text text-muted">Username cannot be changed</div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required 
                           value="<?= htmlspecialchars($user_details['Name']) ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required 
                           value="<?= htmlspecialchars($user_details['Email']) ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Position</label>
                    <input type="text" name="position" class="form-control" 
                           value="<?= htmlspecialchars($user_details['Position'] ?? '') ?>"
                           placeholder="E.g., Student, Teacher, etc.">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" 
                           value="<?= htmlspecialchars($user_details['DateOfBirth'] ?? '') ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone_number" class="form-control" 
                           value="<?= htmlspecialchars($user_details['PhoneNumber'] ?? '') ?>"
                           placeholder="Enter phone number">
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="manage_users.php" class="btn btn-secondary me-md-2">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update User Profile
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('profileForm').addEventListener('submit', function(e) {
    const name = document.querySelector('input[name="name"]').value.trim();
    const email = document.querySelector('input[name="email"]').value.trim();
    
    if (!name || !email) {
        e.preventDefault();
        alert('Please fill in all required fields (Name and Email).');
        return;
    }
});
</script>

<?php
include 'footer.php';
?>