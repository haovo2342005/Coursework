<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_message'] = "Please log in to view your profile.";
    header('Location: login.php');
    exit;
}

$user_details = [];
$user_posts = [];
$post_count = 0;
$comment_count = 0;

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user_id = $_SESSION['user_id'];

    $user_sql = "SELECT UserID, Username, Name, Email, Position, DateOfBirth, PhoneNumber 
                 FROM users WHERE UserID = :user_id";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $user_stmt->execute();
    $user_details = $user_stmt->fetch(PDO::FETCH_ASSOC);

    $post_count_sql = "SELECT COUNT(*) FROM posts WHERE UserID = :user_id";
    $post_count_stmt = $pdo->prepare($post_count_sql);
    $post_count_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $post_count_stmt->execute();
    $post_count = $post_count_stmt->fetchColumn();

    $comment_count_sql = "SELECT COUNT(*) FROM comments WHERE UserID = :user_id";
    $comment_count_stmt = $pdo->prepare($comment_count_sql);
    $comment_count_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $comment_count_stmt->execute();
    $comment_count = $comment_count_stmt->fetchColumn();

    $posts_sql = "SELECT p.PostID, p.Title, p.PostDate, p.ImagePath, m.ModuleName,
                         COUNT(c.CommentID) as CommentCount
                  FROM posts p
                  INNER JOIN modules m ON p.ModuleID = m.ModuleID
                  LEFT JOIN comments c ON p.PostID = c.PostID
                  WHERE p.UserID = :user_id
                  GROUP BY p.PostID
                  ORDER BY p.PostDate DESC";
    $posts_stmt = $pdo->prepare($posts_sql);
    $posts_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $posts_stmt->execute();
    $user_posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

include 'header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-primary text-white text-center">
                <h5 class="mb-0"><i class="fas fa-user-circle"></i> User Profile</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user fa-3x text-primary"></i>
                </div>
                <h4><?= htmlspecialchars($user_details['Name']) ?></h4>
                
                <div class="row text-start mt-4">
                    <div class="col-12 mb-2">
                        <strong><i class="fas fa-envelope text-primary"></i> Email:</strong><br>
                        <?= htmlspecialchars($user_details['Email']) ?>
                    </div>
                    <div class="col-12 mb-2">
                        <strong><i class="fas fa-briefcase text-primary"></i> Position:</strong><br>
                        <?= htmlspecialchars($user_details['Position'] ?: 'Not specified') ?>
                    </div>
                    <?php if ($user_details['DateOfBirth']): ?>
                    <div class="col-12 mb-2">
                        <strong><i class="fas fa-birthday-cake text-primary"></i> Date of Birth:</strong><br>
                        <?= date('M d, Y', strtotime($user_details['DateOfBirth'])) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($user_details['PhoneNumber']): ?>
                    <div class="col-12 mb-2">
                        <strong><i class="fas fa-phone text-primary"></i> Phone:</strong><br>
                        <?= htmlspecialchars($user_details['PhoneNumber']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="edit_profile.php" class="btn btn-primary">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-lg">
            <div class="card-header bg-info text-white text-center">
                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Activity Statistics</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-file-alt text-primary"></i> Posts Created:</span>
                    <span class="badge bg-primary"><?= $post_count ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-comments text-success"></i> Comments Made:</span>
                    <span class="badge bg-success"><?= $comment_count ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-calendar text-warning"></i> Member Since:</span>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-lg">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-alt"></i> My Posts (<?= $post_count ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($user_posts)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <p>You haven't created any posts yet.</p>
                        <a href="post_question.php" class="btn btn-primary">Create Your First Post</a>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($user_posts as $post): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="view_post.php?id=<?= $post['PostID'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($post['Title']) ?>
                                        </a>
                                    </h6>
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="badge bg-primary"><?= htmlspecialchars($post['ModuleName']) ?></span>
                                        <?php if (!empty($post['ImagePath'])): ?>
                                            <span class="badge bg-success"><i class="fas fa-image"></i> Has Image</span>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-comments"></i> <?= $post['CommentCount'] ?> comments
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        Posted on <?= date('M d, Y H:i', strtotime($post['PostDate'])) ?>
                                    </small>
                                </div>
                                <div class="btn-group btn-group-sm ms-3">
                                    <a href="view_post.php?id=<?= $post['PostID'] ?>" class="btn btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_post.php?id=<?= $post['PostID'] ?>" class="btn btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_post.php?id=<?= $post['PostID'] ?>" class="btn btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this post?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-lg mt-4">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <a href="post_question.php" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> Ask New Question
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="home.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-home"></i> Browse Questions
                        </a>
                    </div>
                </div>               
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>