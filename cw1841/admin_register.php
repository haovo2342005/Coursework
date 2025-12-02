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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $repassword = $_POST['repassword'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $dateofbirth = $_POST['dateofbirth'] ?? '';
    $phonenumber = trim($_POST['phonenumber'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $position = trim($_POST['position'] ?? '');

    // Validation
    if (empty($username) || empty($password) || empty($name) || empty($email)) {
        $message = "❌ Error: Username, Password, Name, and Email are required fields.";
    } elseif ($password !== $repassword) {
        $message = "❌ Error: Password and Re-enter password do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Error: Please enter a valid email address.";
    } else {
        try {
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if username or email already exists
            $check_stmt = $pdo->prepare("SELECT UserID FROM users WHERE Username = :username OR Email = :email");
            $check_stmt->bindParam(':username', $username);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                $message = "❌ Error: Username or email already exists!";
            } else {
                // Insert new user
                $insert_stmt = $pdo->prepare("INSERT INTO users (Username, Password, Name, DateOfBirth, PhoneNumber, Email, Position) 
                                            VALUES (:username, :password, :name, :dateofbirth, :phonenumber, :email, :position)");
                $insert_stmt->bindParam(':username', $username);
                $insert_stmt->bindParam(':password', $password);
                $insert_stmt->bindParam(':name', $name);
                $insert_stmt->bindParam(':dateofbirth', $dateofbirth);
                $insert_stmt->bindParam(':phonenumber', $phonenumber);
                $insert_stmt->bindParam(':email', $email);
                $insert_stmt->bindParam(':position', $position);
                
                if ($insert_stmt->execute()) {
                    $_SESSION['admin_message'] = "✅ User account created successfully!";
                    header('Location: manage_users.php');
                    exit;
                } else {
                    $message = "❌ Error: Could not create user account.";
                }
            }
        } catch (PDOException $e) {
            $message = "❌ Database Error: " . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="card shadow-lg">
    <div class="card-header text-center bg-success text-white">
        <h4 class="mb-0"><i class="fas fa-user-plus"></i> Create New User Account</h4>
        <small class="text-light">Admin: <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></small>
    </div>
    <div class="card-body">
        <?php if (isset($message) && $message): ?>
            <div class="alert alert-danger text-center">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="admin_register.php">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Enter username">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="Enter email address">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required
                           placeholder="Enter password">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="repassword" class="form-label">Re-enter Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="repassword" name="repassword" required
                           placeholder="Re-enter password">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                           placeholder="Enter full name">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="dateofbirth" class="form-label">Date Of Birth</label>
                    <input type="date" class="form-control" id="dateofbirth" name="dateofbirth"
                           value="<?= htmlspecialchars($_POST['dateofbirth'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phonenumber" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="phonenumber" name="phonenumber"
                           value="<?= htmlspecialchars($_POST['phonenumber'] ?? '') ?>"
                           placeholder="Enter phone number">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="position" class="form-label">Position</label>
                    <input type="text" class="form-control" id="position" name="position"
                           value="<?= htmlspecialchars($_POST['position'] ?? '') ?>"
                           placeholder="E.g., Student, Teacher, etc.">
                </div>
            </div>
            
            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-user-plus"></i> Create User Account
                </button>
                <a href="manage_users.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to User Management
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Password confirmation validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const repassword = document.getElementById('repassword').value;
    
    if (password !== repassword) {
        e.preventDefault();
        alert('Error: Password and Re-enter password do not match!');
        return false;
    }
    
    if (password.length < 3) {
        e.preventDefault();
        alert('Error: Password must be at least 3 characters long!');
        return false;
    }
});
</script>

<?php
include 'footer.php';
?>