<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php'; 

$post_id = $_GET['id'] ?? null;
$post = null;
$comments = [];
$message = '';
$error = '';

if (empty($post_id) || !is_numeric($post_id)) {
    $error = "Error: Invalid or missing Post ID.";
}

if (isset($_SESSION['post_success'])) {
    $message = $_SESSION['post_success'];
    unset($_SESSION['post_success']);
}

if (isset($_SESSION['comment_success'])) {
    $message = $_SESSION['comment_success'];
    unset($_SESSION['comment_success']);
}

try {
    $pdo = new PDO($dsn, $user, $pass); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!$error) {
        $sql_post = 'SELECT p.PostID, p.Title, p.Content, p.PostDate, p.ImagePath, p.UserID, u.Username AS AuthorName, m.ModuleName
                     FROM posts p
                     INNER JOIN users u ON p.UserID = u.UserID
                     INNER JOIN modules m ON p.ModuleID = m.ModuleID
                     WHERE p.PostID = :post_id';
        
        $stm_post = $pdo->prepare($sql_post);
        $stm_post->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stm_post->execute();
        $post = $stm_post->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            $error = "Error: Question not found.";
        }
        
        if ($post) {
            try {
                $sql_comments = 'SELECT c.Content, c.CommentDate, u.Username AS CommenterName
                                 FROM comments c
                                 INNER JOIN users u ON c.UserID = u.UserID
                                 WHERE c.PostID = :post_id
                                 ORDER BY c.CommentDate ASC';
                
                $stm_comments = $pdo->prepare($sql_comments);
                $stm_comments->bindParam(':post_id', $post_id, PDO::PARAM_INT);
                $stm_comments->execute();
                $comments = $stm_comments->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $comments = [];
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $post) {
        $comment_content = trim($_POST['comment_content'] ?? '');
        $user_id = $_SESSION['user_id'];

        if (empty($comment_content)) {
            $error = "Error: Comment content cannot be empty.";
        } else {
            try {
                $sql_insert_comment = "INSERT INTO comments (PostID, UserID, Content, CommentDate)
                                       VALUES (:post_id, :user_id, :content, NOW())";
                
                $stm_insert = $pdo->prepare($sql_insert_comment);
                $stm_insert->bindParam(':post_id', $post_id, PDO::PARAM_INT);
                $stm_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stm_insert->bindParam(':content', $comment_content);

                if ($stm_insert->execute()) {
                    $_SESSION['comment_success'] = "Your comment has been posted.";
                    header('Location: view_post.php?id=' . $post_id);
                    exit;
                } else {
                    $error = "Error: Could not save your comment.";
                }
            } catch (PDOException $e) {
                $error = "Error: Cannot post comment - comments system not available.";
            }
        }
    }

} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

if ($error) {
    $message = $error;
}

include 'header.php';
?>

<p><a href="home.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Discussions</a></p>

<?php if (isset($error) && $error): ?>
    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (isset($message) && $message && !$error): ?>
    <div class="alert alert-success text-center"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($post): ?>
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <span class="badge bg-light text-dark float-end"><?= htmlspecialchars($post['ModuleName']) ?></span>
            <h2 class="h4 mb-0"><?= htmlspecialchars($post['Title']) ?></h2>
        </div>
        <div class="card-body">
            <small class="text-muted">
                Posted by <strong><?= htmlspecialchars($post['AuthorName']) ?></strong> on <?= date('M d, Y H:i', strtotime($post['PostDate'])) ?>
            </small>
            
            <div class="post-content">
                <?= nl2br(htmlspecialchars($post['Content'])) ?>
                
                <?php if (!empty($post['ImagePath']) && file_exists($post['ImagePath'])): ?>
                    <div class="mt-3 text-center">
                        <img src="<?= htmlspecialchars($post['ImagePath']) ?>" 
                             class="post-image img-fluid rounded" 
                             alt="Attached image"
                             style="max-height: 500px;">
                        <br>
                        <small class="text-muted">Attached image</small>
                    </div>
                <?php elseif (!empty($post['ImagePath'])): ?>
                    <div class="mt-3 alert alert-warning">
                        <small>Image not found: <?= htmlspecialchars($post['ImagePath']) ?></small>
                    </div>
                <?php endif; ?>
            </div>

            <?php 
            $is_author = isset($_SESSION['user_id']) && ($post['UserID'] == $_SESSION['user_id']);
            $is_admin = $_SESSION['is_admin'] ?? false;
            ?>
            
            <?php if ($is_author || $is_admin): ?>
                <div class="mt-3 d-flex gap-2">
                    <a href="edit_post.php?id=<?= $post['PostID'] ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit Post
                    </a>
                    <a href="delete_post.php?id=<?= $post['PostID'] ?>" class="btn btn-danger btn-sm" 
                       onclick="return confirm('Are you sure you want to delete this post?')">
                        <i class="fas fa-trash"></i> Delete Post
                    </a>
                    <?php if ($is_admin && !$is_author): ?>
                        <span class="badge bg-info ms-2 align-self-center">Admin Mode</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <h3 class="h5 mt-5 mb-3">Answers/Comments (<?= count($comments) ?>)</h3>
    
    <div class="comments-list">
        <?php if (empty($comments)): ?>
            <p class="text-muted">No answers yet. Be the first to reply!</p>
        <?php endif; ?>

        <?php foreach ($comments as $comment): ?>
            <div class="card card-body mb-3 comment-box">
                <p class="comment-author mb-1 text-info">
                    <?= htmlspecialchars($comment['CommenterName']) ?> 
                    <small class="text-muted float-end"><?= date('M d, Y H:i', strtotime($comment['CommentDate'])) ?></small>
                </p>
                <p class="mb-0"><?= nl2br(htmlspecialchars($comment['Content'])) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <h4 class="h6 mt-4">Post Your Answer</h4>
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="card card-body bg-light">
            <form method="POST" action="view_post.php?id=<?= $post['PostID'] ?>">
                <div class="mb-3">
                    <textarea class="form-control" name="comment_content" rows="4" placeholder="Type your answer here..." required></textarea>
                </div>
                <button type="submit" class="btn btn-success">Submit Answer</button>
            </form>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">
            Please <a href="login.php">log in</a> to post an answer.
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php
include 'footer.php';
?>