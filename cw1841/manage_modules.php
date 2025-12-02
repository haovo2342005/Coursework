<?php
// manage_modules.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? false)) {
    $_SESSION['redirect_message'] = "Access denied. Admin privileges required.";
    header('Location: login.php');
    exit;
}

$message = '';
$modules = [];
$edit_mode = false;
$editing_module = null;

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $module_name = trim($_POST['module_name'] ?? '');
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_module' || $action === 'edit_module') {
            if (empty($module_name)) {
                $message = "❌ Module name is required!";
            } else {
                // Check for duplicate module name
                $check_sql = "SELECT ModuleID FROM modules WHERE ModuleName = :module_name";
                if ($action === 'edit_module') {
                    $module_id = $_POST['module_id'] ?? null;
                    $check_sql .= " AND ModuleID != :module_id";
                }
                
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->bindParam(':module_name', $module_name);
                if ($action === 'edit_module') {
                    $check_stmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                }
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $message = "❌ A module with this name already exists!";
                } else {
                    if ($action === 'add_module') {
                        // Add new module
                        $sql = "INSERT INTO modules (ModuleName) VALUES (:name)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':name', $module_name);
                        
                        if ($stmt->execute()) {
                            $message = "✅ Module '$module_name' added successfully!";
                        } else {
                            $message = "❌ Error adding module.";
                        }
                    } elseif ($action === 'edit_module') {
                        // Update existing module
                        $module_id = $_POST['module_id'] ?? null;
                        if ($module_id && is_numeric($module_id)) {
                            $sql = "UPDATE modules SET ModuleName = :name WHERE ModuleID = :id";
                            $stmt = $pdo->prepare($sql);
                            $stmt->bindParam(':name', $module_name);
                            $stmt->bindParam(':id', $module_id, PDO::PARAM_INT);
                            
                            if ($stmt->execute()) {
                                $message = "✅ Module updated successfully!";
                                $edit_mode = false;
                                $editing_module = null;
                            } else {
                                $message = "❌ Error updating module.";
                            }
                        }
                    }
                }
            }
        } elseif ($action === 'delete_module') {
            $module_id = $_POST['module_id'] ?? null;
            
            if ($module_id && is_numeric($module_id)) {
                // Check if module has posts
                $check_posts = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE ModuleID = :module_id");
                $check_posts->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                $check_posts->execute();
                $post_count = $check_posts->fetchColumn();
                
                if ($post_count > 0) {
                    $message = "❌ Cannot delete module: There are $post_count posts associated with this module. Reassign or delete those posts first.";
                } else {
                    $delete_stmt = $pdo->prepare("DELETE FROM modules WHERE ModuleID = :module_id");
                    $delete_stmt->bindParam(':module_id', $module_id, PDO::PARAM_INT);
                    
                    if ($delete_stmt->execute()) {
                        $message = "✅ Module deleted successfully!";
                    } else {
                        $message = "❌ Error deleting module.";
                    }
                }
            }
        } elseif ($action === 'cancel_edit') {
            $edit_mode = false;
            $editing_module = null;
        }
    }
    
    // Handle edit request
    if (isset($_GET['edit'])) {
        $edit_id = $_GET['edit'];
        if (is_numeric($edit_id)) {
            $edit_stmt = $pdo->prepare("SELECT * FROM modules WHERE ModuleID = :id");
            $edit_stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
            $edit_stmt->execute();
            $editing_module = $edit_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($editing_module) {
                $edit_mode = true;
            }
        }
    }

    // Fetch all modules with post counts
    $sql = "SELECT m.ModuleID, m.ModuleName, 
                   COUNT(p.PostID) as PostCount,
                   MAX(p.PostDate) as LastActivity
            FROM modules m
            LEFT JOIN posts p ON m.ModuleID = p.ModuleID
            GROUP BY m.ModuleID
            ORDER BY m.ModuleName";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
}

include 'header.php';
?>

<div class="row">
    <!-- Add/Edit Module Form -->
    <div class="col-md-4">
        <div class="card shadow-lg mb-4">
            <div class="card-header <?= $edit_mode ? 'bg-warning' : 'bg-success' ?> text-white">
                <h5 class="mb-0">
                    <i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                    <?= $edit_mode ? 'Edit Module' : 'Add New Module' ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="manage_modules.php">
                    <?php if ($edit_mode && $editing_module): ?>
                        <input type="hidden" name="module_id" value="<?= $editing_module['ModuleID'] ?>">
                        <input type="hidden" name="action" value="edit_module">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add_module">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Module Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="module_name" required
                               value="<?= $edit_mode ? htmlspecialchars($editing_module['ModuleName']) : '' ?>"
                               placeholder="Enter module name...">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if ($edit_mode): ?>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Update Module
                            </button>
                            <button type="submit" name="action" value="cancel_edit" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add Module
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card bg-light">
            <div class="card-body">
                <h6><i class="fas fa-chart-bar"></i> Module Statistics</h6>
                <ul class="list-unstyled small">
                    <li><strong>Total Modules:</strong> <?= count($modules) ?></li>
                    <li><strong>Active Modules:</strong> <?= count(array_filter($modules, fn($m) => $m['PostCount'] > 0)) ?></li>
                    <li><strong>Total Posts:</strong> <?= array_sum(array_column($modules, 'PostCount')) ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modules List -->
    <div class="col-md-8">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-cubes"></i> All Modules</h5>
                <span class="badge bg-light text-dark"><?= count($modules) ?> modules</span>
            </div>
            <div class="card-body">
                <?php if (empty($modules)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-cube fa-3x mb-3 d-block"></i>
                        <p>No modules found. Add your first module!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Module Name</th>
                                    <th>Posts</th>
                                    <th>Last Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modules as $module): ?>
                                <tr>
                                    <td><?= $module['ModuleID'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($module['ModuleName']) ?></strong>
                                        <?php if ($module['PostCount'] == 0): ?>
                                            <span class="badge bg-secondary ms-1">New</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $module['PostCount'] ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= $module['LastActivity'] ? date('M d, Y', strtotime($module['LastActivity'])) : 'Never' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="manage_modules.php?edit=<?= $module['ModuleID'] ?>" 
                                               class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($module['PostCount'] == 0): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="module_id" value="<?= $module['ModuleID'] ?>">
                                                    <input type="hidden" name="action" value="delete_module">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Delete module: <?= htmlspecialchars($module['ModuleName']) ?>?')"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary btn-sm" disabled
                                                        title="Cannot delete - has <?= $module['PostCount'] ?> posts">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <a href="home.php?module_id=<?= $module['ModuleID'] ?>" 
                                               class="btn btn-info btn-sm" title="View Posts">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <!-- Navigation -->
                <div class="mt-3">
                    <div class="card-header bg-light text-dark">
                        <h5 class="mb-0"><span class="text-warning"><i class="fas fa-bolt"></i> Quick Actions</span></h5>
                    </div>
                    <a href="manage_users.php" class="btn btn-outline-primary">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a href="manage_questions.php" class="btn btn-outline-primary">
                        <i class="fas fa-tasks"></i> Manage Questions
                    </a>
                    <a href="home.php" class="btn btn-outline-secondary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>