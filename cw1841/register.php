<?php
// register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";

if (isset($_POST['username'], $_POST['password'], $_POST['repassword'])){
    $username = $_POST['username'];
    $password = $_POST['password'];
    $repassword = $_POST['repassword'];
    $name = $_POST['name'];
    $dateofbirth = $_POST['dateofbirth'];
    $phonenumber = $_POST['phonenumber'];
    $email = $_POST['email'];
    $position = $_POST['position'];

    if ($password !== $repassword) {
        echo "Error: Password and Re-enter password do not match.";
    } else {
        try {
            $pdo = new PDO($dsn, $user, $pass);
            $stm = $pdo->prepare("INSERT INTO `users` (`Username`, `Password`, `Name`, `DateOfBirth`, `PhoneNumber`, `Email`, `Position`) VALUES (:username, :password, :name, :dateofbirth, :phonenumber, :email, :position)");
            $stm->bindValue(':username', $username);
            $stm->bindValue(':password', $password);
            $stm->bindValue(':name', $name);
            $stm->bindValue(':dateofbirth', $dateofbirth);
            $stm->bindValue(':phonenumber', $phonenumber);
            $stm->bindValue(':email', $email);
            $stm->bindValue(':position', $position);
            
            if ($stm->execute()) {
                $_SESSION['registration_success'] = "Account created successfully! Please log in.";
                header("Location: login.php");
                exit;
            } else {
                echo "Error: Could not execute statement.";
            }
        } catch (PDOException $e) {
            echo "Database Error: " . $e->getMessage();
        }
    }
} else {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register - Student Q&A</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background-color: #f8f9fa; }
            .register-container { max-width: 600px; margin-top: 5vh; margin-bottom: 5vh;}
        </style>
    </head>
    <body>
    <div class="container register-container">
        <div class="card shadow-lg">
            <div class="card-header text-center bg-primary text-white">
                <h4 class="mb-0">Create New Student Q&A Account</h4>
            </div>
            <div class="card-body">
                <form action="register.php" method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="repassword" class="form-label">Re-enter password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="repassword" name="repassword" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="dateofbirth" class="form-label">Date Of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="dateofbirth" name="dateofbirth" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phonenumber" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phonenumber" name="phonenumber">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" class="form-control" id="position" name="position">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-success btn-lg">Create Account</button>
                    </div>
                </form>
                
                <p class="text-center mt-3">
                    Already have an account? <a href="login.php" class="text-decoration-none">Login here</a>
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
    <?php
}
?>