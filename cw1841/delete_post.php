<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_message'] = "Please log in to delete a post.";
    header('Location: login.php');
    exit;
}

$post_id = $_GET['id'] ?? null;
if (empty($post_id) || !is_numeric($post_id)) {
    $_SESSION['post_error'] = "Error: Invalid or missing Post ID.";
    header('Location: home.php');
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql_post = 'SELECT p.PostID, p.Title, p.ImagePath, p.UserID
                 FROM posts p
                 WHERE p.PostID = :post_id';
    
    $stm_post = $pdo->prepare($sql_post);
    $stm_post->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stm_post->execute();
    $post = $stm_post->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        $_SESSION['post_error'] = "Error: Post not found.";
        header('Location: home.php');
        exit;
    }

    $is_author = ($post['UserID'] == $_SESSION['user_id']);
    $is_admin = ($_SESSION['is_admin'] ?? false);

    if (!$is_author && !$is_admin) {
        $_SESSION['post_error'] = "Error: You don't have permission to delete this post.";
        header('Location: home.php');
        exit;
    }

    $pdo->beginTransaction();

    try {
        $sql_delete_comments = "DELETE FROM comments WHERE PostID = :post_id";
        $stm_comments = $pdo->prepare($sql_delete_comments);
        $stm_comments->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stm_comments->execute();

        $sql_delete_post = "DELETE FROM posts WHERE PostID = :post_id";
        $stm_post = $pdo->prepare($sql_delete_post);
        $stm_post->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stm_post->execute();

        if (!empty($post['ImagePath'])) {
            $image_path = $post['ImagePath'];
            
            $allowed_dirs = ['uploads/', 'images/'];
            $is_valid_path = false;
            
            foreach ($allowed_dirs as $dir) {
                if (strpos($image_path, $dir) === 0) {
                    $is_valid_path = true;
                    break;
                }
            }
            
            if ($is_valid_path && file_exists($image_path)) {
                if (!unlink($image_path)) {
                    error_log("Warning: Could not delete image file: " . $image_path);
                }
            }
        }

        $pdo->commit();
        
        $_SESSION['post_success'] = "Post '" . htmlspecialchars($post['Title']) . "' has been deleted successfully!";

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    header('Location: home.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['post_error'] = "Error: Could not delete the post. " . $e->getMessage();
    header('Location: home.php');
    exit;
}
?>