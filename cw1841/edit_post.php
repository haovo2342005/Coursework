<?php
// edit_post.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

$message = '';
$modules = [];
$post = null;

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_message'] = "Please log in to edit a post.";
    header('Location: login.php');
    exit;
}

$post_id = $_GET['id'] ?? null;
if (empty($post_id) || !is_numeric($post_id)) {
    $message = "Error: Invalid or missing Post ID.";
    include 'header.php';
    echo "<div class='alert alert-danger text-center'>$message</div>";
    include 'footer.php';
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get post details with JOIN to get author info
    $sql_post = 'SELECT p.PostID, p.Title, p.Content, p.ImagePath, p.UserID, p.PostDate, 
                         u.Username, u.Name AS AuthorName, u.Email AS AuthorEmail,
                         m.ModuleID, m.ModuleName
                 FROM posts p
                 INNER JOIN users u ON p.UserID = u.UserID
                 INNER JOIN modules m ON p.ModuleID = m.ModuleID
                 WHERE p.PostID = :post_id';
    
    $stm_post = $pdo->prepare($sql_post);
    $stm_post->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stm_post->execute();
    $post = $stm_post->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        $message = "Error: Post not found.";
        include 'header.php';
        echo "<div class='alert alert-danger text-center'>$message</div>";
        include 'footer.php';
        exit;
    }

    // Check permission: user must be the author OR admin
    $is_author = ($post['UserID'] == $_SESSION['user_id']);
    $is_admin = ($_SESSION['is_admin'] ?? false);

    if (!$is_author && !$is_admin) {
        $message = "Error: You don't have permission to edit this post.";
        include 'header.php';
        echo "<div class='alert alert-danger text-center'>$message</div>";
        include 'footer.php';
        exit;
    }

    // Get modules for dropdown
    $stmt = $pdo->prepare("SELECT ModuleID, ModuleName FROM modules ORDER BY ModuleName");
    $stmt->execute();
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $module_id = $_POST['module_id'] ?? null;
        $delete_image = isset($_POST['delete_image']);
        $image_path = $post['ImagePath'];

        // Validate input
        if (empty($title) || empty($content) || empty($module_id)) {
            $message = "Error: Title, Content, and Module are required.";
        } else {
            // Sanitize input to prevent XSS
            $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

            // Handle image deletion
            if ($delete_image && !empty($image_path) && file_exists($image_path)) {
                unlink($image_path);
                $image_path = null;
            }

            // Handle new image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $targetDir = "uploads/";
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                // Delete old image if exists
                if (!empty($image_path) && file_exists($image_path)) {
                    unlink($image_path);
                }

                // Enhanced file validation using finfo (as shown in tutorial)
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($fileInfo, $_FILES["image"]["tmp_name"]);
                finfo_close($fileInfo);
                
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileExtension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($mimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
                    $message = "Error: Only JPG, JPEG, PNG, GIF and WebP files are allowed.";
                } elseif ($_FILES["image"]["size"] > 2000000) {
                    $message = "Error: File is too large. Maximum size is 2MB.";
                } else {
                    // Create safe filename
                    $fileName = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
                    $safeFileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fileName);
                    $targetFile = $targetDir . $safeFileName . '_' . time() . '.' . $fileExtension;
                    
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                        $image_path = $targetFile;
                    } else {
                        $message = "Error: There was an error uploading your file.";
                    }
                }
            }

            if (empty($message)) { // SỬA: DÙNG empty() THAY VÌ !
                // Update post using prepared statements with bound values
                $sql = "UPDATE posts 
                        SET Title = :title, Content = :content, ModuleID = :module_id, ImagePath = :image_path 
                        WHERE PostID = :post_id";
                
                $stm = $pdo->prepare($sql);
                $stm->bindParam(':title', $title);
                $stm->bindParam(':content', $content);
                $stm->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                $stm->bindParam(':image_path', $image_path);
                $stm->bindParam(':post_id', $post_id, PDO::PARAM_INT);
                
                if ($stm->execute()) {
                    $_SESSION['post_success'] = "Your post has been updated successfully!";
                    header('Location: view_post.php?id=' . $post_id);
                    exit;
                } else {
                    $message = "Error: Could not update the post.";
                }
            }
        }
    }
} catch (PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
}

include 'header.php';
?>

<div class="card shadow-lg">
    <div class="card-header text-center bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Edit Post</h4>
        <a href="delete_post.php?id=<?= $post['PostID'] ?>" class="btn btn-danger btn-sm" 
           onclick="return confirm('Are you sure you want to delete this post?')">
            <i class="fas fa-trash"></i> Delete Post
        </a>
    </div>
    <div class="card-body">
        <?php if (isset($message) && $message): ?>
            <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($is_admin && !$is_author): ?>
            <div class="alert alert-info">
                <i class="fas fa-shield-alt"></i> <strong>Admin Mode:</strong> You are editing a post created by <?= htmlspecialchars($post['AuthorName']) ?>
            </div>
        <?php endif; ?>

        <!-- Post Information Card -->
        <div class="card mb-4 bg-light">
            <div class="card-body">
                <h6 class="card-title">Post Information</h6>
                <div class="row small text-muted">
                    <div class="col-md-6">
                        <strong>Original Author:</strong> <?= htmlspecialchars($post['AuthorName']) ?><br>
                        <strong>Username:</strong> <?= htmlspecialchars($post['Username']) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Created Date:</strong> <?= date('M d, Y H:i', strtotime($post['PostDate'])) ?><br>
                        <strong>Current Module:</strong> <?= htmlspecialchars($post['ModuleName']) ?>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="edit_post.php?id=<?= $post['PostID'] ?>" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Post Title:</label>
                <input type="text" name="title" class="form-control" required 
                       value="<?= htmlspecialchars($post['Title']) ?>" 
                       placeholder="Enter your post title...">
            </div>

            <div class="mb-3">
                <label class="form-label">Module:</label>
                <select name="module_id" class="form-select" required>
                    <option value="">-- Select Module --</option>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?= htmlspecialchars($module['ModuleID']) ?>" 
                                <?= ($module['ModuleID'] == $post['ModuleID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($module['ModuleName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Post Content:</label>
                <textarea name="content" class="form-control" rows="8" required 
                          placeholder="Describe your post in detail..."><?= htmlspecialchars($post['Content']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Image:</label>
                <?php if (!empty($post['ImagePath']) && file_exists($post['ImagePath'])): ?>
                    <div class="mb-2">
                        <img src="<?= htmlspecialchars($post['ImagePath']) ?>" 
                             class="img-fluid rounded border" 
                             alt="Current image"
                             style="max-height: 200px;">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="delete_image" id="delete_image">
                            <label class="form-check-label text-danger" for="delete_image">
                                <i class="fas fa-trash"></i> Delete current image
                            </label>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted"><i class="fas fa-image"></i> No image attached</p>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Replace Image (Optional):</label>
                <input type="file" name="image" class="form-control" 
                       accept="image/jpeg,image/png,image/gif,image/webp">
                <div class="form-text">
                    <i class="fas fa-info-circle"></i> Supported formats: JPG, PNG, GIF, WebP. Max size: 2MB
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="view_post.php?id=<?= $post['PostID'] ?>" class="btn btn-secondary me-md-2">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Post
                </button>
            </div>
        </form>
    </div>
</div>

<?php
include 'footer.php';
?>