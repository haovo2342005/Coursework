<?php
// post_question.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php'; 

$message = '';
$modules = [];

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_message'] = "Please log in to post a question.";
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $pass); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT ModuleID, ModuleName FROM modules ORDER BY ModuleName");
    $stmt->execute();
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $_SESSION['user_id'];
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $module_id = $_POST['module_id'] ?? null;
        $image_path = null;
        
        if (empty($title) || empty($content) || empty($module_id)) {
            $message = "Error: Title, Content, and Module are required.";
        } else {
            $uploadOk = 1; // THÊM BIẾN NÀY
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $targetDir = "uploads/";
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                // Validate file type
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($fileInfo, $_FILES["image"]["tmp_name"]);
                finfo_close($fileInfo);
                
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileExtension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION)); // SỬA: THÊM DẤU ;
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($mimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
                    $message = "Error: Only JPG, JPEG, PNG, GIF and WebP files are allowed.";
                    $uploadOk = 0;
                }
                
                if ($_FILES["image"]["size"] > 2000000) {
                    $message = "Error: File is too large. Maximum size is 2MB.";
                    $uploadOk = 0;
                }
                
                if ($uploadOk && !isset($message)) { // SỬA ĐIỀU KIỆN
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
            
            if (empty($message)) { // SỬA ĐIỀU KIỆN
                $sql = "INSERT INTO posts (UserID, ModuleID, Title, Content, ImagePath, PostDate) 
                        VALUES (:user_id, :module_id, :title, :content, :image_path, NOW())";
                
                $stm = $pdo->prepare($sql);
                $stm->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stm->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                $stm->bindParam(':title', $title);
                $stm->bindParam(':content', $content);
                $stm->bindParam(':image_path', $image_path);
                
                if ($stm->execute()) {
                    $last_id = $pdo->lastInsertId();
                    $_SESSION['post_success'] = "Your question has been posted successfully!";
                    header('Location: view_post.php?id=' . $last_id);
                    exit;
                } else {
                    $message = "Error: Could not post the question to the database.";
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
    <div class="card-header text-center bg-primary text-white">
        <h4 class="mb-0">Post New Question</h4>
    </div>
    <div class="card-body">
        <?php if (isset($message) && $message): ?>
            <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="post_question.php" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Question Title:</label>
                <input type="text" name="title" class="form-control" required 
                       placeholder="Enter your question title...">
            </div>

            <div class="mb-3">
                <label class="form-label">Module:</label>
                <select name="module_id" class="form-select" required>
                    <option value="">-- Select Module --</option>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?= htmlspecialchars($module['ModuleID']) ?>">
                            <?= htmlspecialchars($module['ModuleName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Question Content:</label>
                <textarea name="content" class="form-control" rows="8" required 
                          placeholder="Describe your question in detail..."></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Attach Image (Optional):</label>
                <input type="file" name="image" class="form-control" 
                       accept="image/jpeg,image/png,image/gif,image/webp">
                <div class="form-text">Supported formats: JPG, PNG, GIF, WebP. Max size: 2MB</div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Post Question</button>
                <a href="home.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
include 'footer.php';
?>