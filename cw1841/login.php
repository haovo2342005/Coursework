<?php
// login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php'; 

$error_message = '';
$success_message = '';

if (isset($_SESSION['registration_success'])) {
    $success_message = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']); 
}

$_SESSION['is_admin'] = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? ''; 
    $password = $_POST['password'] ?? ''; 

    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            $pdo = new PDO($dsn, $user, $pass); 
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stm = $pdo->prepare('SELECT UserID, Password, Name, Email, Position FROM users WHERE Username = :username'); 
            $stm->bindParam(':username', $username);
            $stm->execute();
            $user_data = $stm->fetch(PDO::FETCH_ASSOC);

            if ($user_data) {
                if ($password === $user_data['Password']) { 
                    $_SESSION['user_id'] = $user_data['UserID'];
                    $_SESSION['username'] = $username;
                    $_SESSION['name'] = $user_data['Name']; 
                    $_SESSION['email'] = $user_data['Email'];
                    $_SESSION['position'] = $user_data['Position'];
                    
                    $admin_stm = $pdo->prepare('SELECT AccessLevel FROM admins WHERE UserID = :user_id');
                    $admin_stm->bindParam(':user_id', $user_data['UserID']);
                    $admin_stm->execute();
                    $admin_data = $admin_stm->fetch(PDO::FETCH_ASSOC);

                    if ($admin_data && $admin_data['AccessLevel'] >= 1) { 
                        $_SESSION['is_admin'] = true;
                        $_SESSION['access_level'] = $admin_data['AccessLevel'];
                    } else {
                        $_SESSION['is_admin'] = false;
                    }
                    
                    header('Location: home.php');
                    exit;
                } else {
                    $error_message = "Invalid username or password.";
                }
            } else {
                $error_message = "Invalid username or password.";
            }

        } catch (PDOException $e) {
            $error_message = "A database error occurred. Try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Q&A</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { max-width: 400px; margin-top: 10vh; }
    </style>
</head>
<body>

<div class="container login-container">
    <div class="card shadow-lg">
        <div class="card-header text-center bg-primary text-white">
            <h4 class="mb-0">Student Q&A Login</h4>
        </div>
        <div class="card-body">
            <?php if (isset($error_message) && $error_message): ?> 
                <div class="alert alert-danger text-center"><?= htmlspecialchars($error_message) ?></div>
                <!-- Hiển thị link quên mật khẩu khi đăng nhập sai -->
                <div class="text-center mb-3">
                    <a href="forgot_password.php" class="text-decoration-none">Forgot your password?</a>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_message) && $success_message): ?> 
                <div class="alert alert-success text-center"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="POST"> 
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success">Login</button>
                </div>
            </form>
            
            <p class="text-center mt-3">
                <a href="register.php" class="text-decoration-none">Create an Account</a>
            </p>
            <p class="text-center">
                <a href="home.php" class="text-decoration-none">← Back to Questions</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>