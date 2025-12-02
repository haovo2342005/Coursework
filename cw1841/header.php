<?php
// header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch user post count if not already set
if (isset($_SESSION['user_id']) && !isset($post_count)) {
    include 'config.php';
    try {
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $count_stm = $pdo->prepare('SELECT COUNT(PostID) AS post_count FROM posts WHERE UserID = :user_id');
        $count_stm->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $count_stm->execute();
        $count_result = $count_stm->fetch(PDO::FETCH_ASSOC);
        $post_count = $count_result['post_count'];
    } catch (PDOException $e) {
        $post_count = 0;
    }
}

// Xác định trang hiện tại cho active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Q&A</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body { background-color: #e9ecef; }
        .app-header { background-color: #212529; }
        .app-header .navbar-brand { color: white; }
        .app-header .nav-link { 
            color: rgba(255, 255, 255, 0.9);
        }
        .app-header .nav-link:hover, 
        .app-header .nav-link:focus, 
        .app-header .nav-link.active { 
            color: white !important; 
            background-color: rgba(255, 255, 255, 0.1); 
            border-bottom: 3px solid #17a2b8;
        }
        .user-info-header {
            padding: 10px 15px;
            background-color: #f1f1f1;
            border-bottom: 1px solid #dee2e6;
            font-size: 0.9rem;
            color: #333;
        }
        .user-info-header strong {
            display: block;
            font-size: 1rem;
            color: #000;
        }
        .user-stat {
            padding: 5px 15px;
            font-size: 0.85rem;
            color: #6c757d;
        }
        .user-stat i {
            width: 15px;
        }
        .post-grid-card { 
            min-height: 250px; 
            transition: box-shadow 0.3s, transform 0.3s; 
            border: none;
        }
        .post-grid-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            transform: translateY(-5px);
        }
        .module-badge { font-size: 0.75rem; font-weight: 600; }
        .question-title-grid { font-size: 1.1rem; font-weight: 700; color: #007bff; }
        .post-image { max-width: 100%; height: auto; border-radius: 8px; margin-top: 15px; }
        .comment-box { border-left: 3px solid #0dcaf0; padding-left: 15px; margin-bottom: 10px; }
        .comment-author { font-size: 0.9em; font-weight: bold; }
        .post-content { white-space: pre-wrap; margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee; }
    </style>
</head>
<body>

<header class="app-header">
    <div class="container">
        <nav class="navbar navbar-expand-lg">
            <a class="navbar-brand fs-4" href="home.php">Student Q&A</a>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'home.php' ? 'active' : '' ?>" href="home.php">
                            Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'post_question.php' ? 'active' : '' ?>" href="post_question.php">
                            Ask New
                        </a>
                    </li>
                    
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">Management</a>
                            <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                <li><a class="dropdown-item" href="manage_questions.php"><i class="fas fa-tasks"></i> Manage Questions</a></li>
                                <li><a class="dropdown-item" href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                                <li><a class="dropdown-item" href="manage_modules.php"><i class="fas fa-cubes"></i> Manage Modules</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'contact.php' ? 'active' : '' ?>" href="contact.php">
                            Contact Admin
                        </a>
                    </li>

                    <?php if (isset($_SESSION['username'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle btn btn-info btn-sm ms-3" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" style="color: white !important;">
                                <i class="fas fa-user-circle me-2"></i>
                                <?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['username']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li class="user-info-header text-center">
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" 
                                        style="width: 60px; height: 60px;">
                                        <i class="fas fa-user fa-2x text-light"></i>
                                    </div>
                                    <strong><?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['username']) ?></strong><br>
                                </li>
                                <li><a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user-circle"></i> My Profile & Posts
                                </a></li>
                                <li><a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-info btn-sm ms-3" href="login.php">Login</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </div>
</header>

<main class="container py-5">