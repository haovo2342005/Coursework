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
$posts = [];
$search_term = '';
$module_filter = '';
$user_filter = '';

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $selected_posts = $_POST['selected_posts'] ?? [];

        $validated_posts = array_filter($selected_posts, 'is_numeric');
        $validated_posts = array_map('intval', $validated_posts);
        
        if (!empty($validated_posts) && $action) {
            $placeholders = implode(',', array_fill(0, count($validated_posts), '?'));
            
            switch ($action) {
                case 'delete_selected':
                    $pdo->beginTransaction();
                    try {
                        $get_images = $pdo->prepare("SELECT ImagePath FROM posts WHERE PostID IN ($placeholders)");
                        $get_images->execute($validated_posts);
                        $images = $get_images->fetchAll(PDO::FETCH_COLUMN);

                        $delete_comments = $pdo->prepare("DELETE FROM comments WHERE PostID IN ($placeholders)");
                        $delete_comments->execute($validated_posts);

                        $delete_posts = $pdo->prepare("DELETE FROM posts WHERE PostID IN ($placeholders)");
                        $delete_posts->execute($validated_posts);

                        foreach ($images as $image_path) {
                            if (!empty($image_path) && file_exists($image_path)) {
                                unlink($image_path);
                            }
                        }
                        
                        $pdo->commit();
                        $message = "✅ " . count($validated_posts) . " posts deleted successfully!";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $message = "❌ Error deleting posts: " . $e->getMessage();
                    }
                    break;                   
            }
        } else {
            $message = "❌ Please select posts to perform action.";
        }
    }

    $search_term = trim($_GET['search'] ?? '');
    $module_filter = $_GET['module_id'] ?? '';
    $user_filter = $_GET['user_id'] ?? '';
    
    $where_clauses = [];
    $params = [];
    
    if (!empty($search_term)) {
        $where_clauses[] = "(p.Title LIKE :search OR p.Content LIKE :search)";
        $params[':search'] = '%' . $search_term . '%';
    }
    
    if (!empty($module_filter) && is_numeric($module_filter)) {
        $where_clauses[] = "p.ModuleID = :module_id";
        $params[':module_id'] = $module_filter;
    }
    
    if (!empty($user_filter) && is_numeric($user_filter)) {
        $where_clauses[] = "p.UserID = :user_id";
        $params[':user_id'] = $user_filter;
    }
    
    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    $sql = "SELECT p.PostID, p.Title, p.Content, p.PostDate, p.ImagePath, 
                   u.UserID, u.Username, u.Name AS AuthorName,
                   m.ModuleID, m.ModuleName,
                   COUNT(c.CommentID) as CommentCount
            FROM posts p
            INNER JOIN users u ON p.UserID = u.UserID
            INNER JOIN modules m ON p.ModuleID = m.ModuleID
            LEFT JOIN comments c ON p.PostID = c.PostID
            $where_sql
            GROUP BY p.PostID
            ORDER BY p.PostDate DESC";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $modules = $pdo->query("SELECT ModuleID, ModuleName FROM modules ORDER BY ModuleName")->fetchAll();

    $users = $pdo->query("SELECT UserID, Username, Name FROM users ORDER BY Name")->fetchAll();

} catch (PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
}

include 'header.php';
?>

<div class="card shadow-lg">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-tasks"></i> Manage Questions</h4>
        <span class="badge bg-light text-dark"><?= count($posts) ?> posts</span>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="manage_questions.php" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search Questions</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search title or content..." 
                                   value="<?= htmlspecialchars($search_term) ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Filter by Module</label>
                        <select class="form-select" name="module_id">
                            <option value="">All Modules</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?= $module['ModuleID'] ?>" 
                                        <?= $module_filter == $module['ModuleID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($module['ModuleName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Filter by User</label>
                        <select class="form-select" name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['UserID'] ?>" 
                                        <?= $user_filter == $user['UserID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['Name'] ?: $user['Username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Apply</button>
                        <a href="manage_questions.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <form method="POST" action="manage_questions.php" id="bulkForm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="btn-group">
                    <select name="action" class="form-select me-2" style="width: auto;">
                        <option value="">Bulk Actions</option>
                        <option value="delete_selected">Delete Selected</option>
                    </select>
                    <button type="submit" class="btn btn-warning" onclick="return confirmBulkAction()">
                        Apply
                    </button>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">Select All</label>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="40">
                            </th>
                            <th>Question</th>
                            <th>Author</th>
                            <th>Module</th>
                            <th>Comments</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-question-circle fa-2x mb-2 d-block"></i>
                                    No questions found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_posts[]" 
                                           value="<?= $post['PostID'] ?>" class="post-checkbox">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($post['Title']) ?></strong>
                                    <?php if (!empty($post['ImagePath'])): ?>
                                        <span class="badge bg-success ms-1"><i class="fas fa-image"></i></span>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted text-truncate d-block" style="max-width: 300px;">
                                        <?= htmlspecialchars(substr($post['Content'], 0, 100)) ?>...
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        <strong><?= htmlspecialchars($post['AuthorName']) ?></strong><br>
                                        <span class="text-muted">@<?= htmlspecialchars($post['Username']) ?></span>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?= htmlspecialchars($post['ModuleName']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= $post['CommentCount'] ?></span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M d, Y', strtotime($post['PostDate'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view_post.php?id=<?= $post['PostID'] ?>" 
                                           class="btn btn-info btn-sm" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_post.php?id=<?= $post['PostID'] ?>" 
                                           class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_post.php?id=<?= $post['PostID'] ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Delete this question?')"
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <div class="mt-4">
            <a href="manage_users.php" class="btn btn-outline-primary">
                <i class="fas fa-users"></i> Manage Users
            </a>
            <a href="manage_modules.php" class="btn btn-outline-success">
                <i class="fas fa-cubes"></i> Manage Modules
            </a>
            <a href="home.php" class="btn btn-outline-secondary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</div>

<script>

document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.post-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    headerCheckbox.checked = this.checked;
});

function confirmBulkAction() {
    const selectedCount = document.querySelectorAll('.post-checkbox:checked').length;
    const action = document.querySelector('select[name="action"]').value;
    
    if (!action) {
        alert('Please select a bulk action.');
        return false;
    }
    
    if (selectedCount === 0) {
        alert('Please select at least one post.');
        return false;
    }
    
    if (action === 'delete_selected') {
        return confirm(`Are you sure you want to delete ${selectedCount} selected posts? This action cannot be undone!`);
    }
    
    return true;
}
</script>

<?php
include 'footer.php';
?>