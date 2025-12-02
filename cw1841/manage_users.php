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
$users = [];
$search_term = '';
$user_stats = [
    'total_users' => 0,
    'active_today' => 0,
    'admin_count' => 0
];

if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $target_user_id = $_POST['user_id'] ?? null;
        
        if ($action === 'promote_to_admin') {
            $check_admin = $pdo->prepare("SELECT * FROM admins WHERE UserID = :user_id");
            $check_admin->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
            $check_admin->execute();
            
            if ($check_admin->rowCount() === 0) {
                $promote_stmt = $pdo->prepare("INSERT INTO admins (UserID, AccessLevel) VALUES (:user_id, 1)");
                $promote_stmt->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
                if ($promote_stmt->execute()) {
                    $message = "✅ User promoted to admin successfully!";
                }
            } else {
                $message = "⚠️ User is already an admin.";
            }
        } elseif ($action === 'demote_from_admin') {
            $demote_stmt = $pdo->prepare("DELETE FROM admins WHERE UserID = :user_id");
            $demote_stmt->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
            if ($demote_stmt->execute()) {
                $message = "✅ User demoted from admin successfully!";
            }
        } elseif ($action === 'delete_user') {
            if ($target_user_id == $_SESSION['user_id']) {
                $message = "❌ You cannot delete your own account!";
            } else {
                $pdo->beginTransaction();
                try {
                    $delete_comments = $pdo->prepare("DELETE FROM comments WHERE UserID = :user_id");
                    $delete_comments->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
                    $delete_comments->execute();
                    
                    $user_posts = $pdo->prepare("SELECT PostID, ImagePath FROM posts WHERE UserID = :user_id");
                    $user_posts->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
                    $user_posts->execute();
                    $posts = $user_posts->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($posts as $post) {
                        $delete_post_comments = $pdo->prepare("DELETE FROM comments WHERE PostID = :post_id");
                        $delete_post_comments->bindParam(':post_id', $post['PostID'], PDO::PARAM_INT);
                        $delete_post_comments->execute();

                        if (!empty($post['ImagePath']) && file_exists($post['ImagePath'])) {
                            unlink($post['ImagePath']);
                        }
                    }

                    $delete_posts = $pdo->prepare("DELETE FROM posts WHERE UserID = :user_id");
                    $delete_posts->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
                    $delete_posts->execute();

                    $delete_admin = $pdo->prepare("DELETE FROM admins WHERE UserID = :user_id");
                    $delete_admin->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
                    $delete_admin->execute();

                    $delete_user = $pdo->prepare("DELETE FROM users WHERE UserID = :user_id");
                    $delete_user->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
                    $delete_user->execute();
                    
                    $pdo->commit();
                    $message = "✅ User and all associated data deleted successfully!";
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = "❌ Error deleting user: " . $e->getMessage();
                }
            }
        }
    }

    $search_term = trim($_GET['search'] ?? '');
    $where_clause = "";
    $params = [];
    
    if (!empty($search_term)) {
        $where_clause = "WHERE u.Username LIKE :search OR u.Name LIKE :search OR u.Email LIKE :search";
        $params[':search'] = '%' . $search_term . '%';
    }

    $sql = "SELECT u.UserID, u.Username, u.Name, u.Email, u.Position, u.DateOfBirth, u.PhoneNumber,
                   COUNT(p.PostID) as PostCount,
                   COUNT(c.CommentID) as CommentCount,
                   CASE WHEN a.UserID IS NOT NULL THEN 1 ELSE 0 END as IsAdmin
            FROM users u
            LEFT JOIN posts p ON u.UserID = p.UserID
            LEFT JOIN comments c ON u.UserID = c.UserID
            LEFT JOIN admins a ON u.UserID = a.UserID
            $where_clause
            GROUP BY u.UserID
            ORDER BY u.UserID DESC";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $admin_count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    $active_today = $pdo->query("SELECT COUNT(DISTINCT UserID) FROM posts WHERE DATE(PostDate) = CURDATE()")->fetchColumn();
    
    $user_stats = [
        'total_users' => $total_users,
        'active_today' => $active_today,
        'admin_count' => $admin_count
    ];

} catch (PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
}

include 'header.php';
?>

<div class="card shadow-lg">
    <div class="card-header bg-primary text-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <h4 class="mb-0"><i class="fas fa-users-cog"></i> User Management</h4>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-light text-dark fs-6 py-2">Total: <?= $user_stats['total_users'] ?> users</span>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= $user_stats['total_users'] ?></h5>
                        <p class="card-text">Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= $user_stats['active_today'] ?></h5>
                        <p class="card-text">Active Today</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= $user_stats['admin_count'] ?></h5>
                        <p class="card-text">Admin Users</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="manage_users.php" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Search by username, name, or email..." 
                                   value="<?= htmlspecialchars($search_term) ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="manage_users.php" class="btn btn-secondary">Clear</a>
                            </div>
                            <a href="admin_register.php" class="btn btn-success">
                                <i class="fas fa-user-plus"></i> Add User
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div></div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>User Info</th>
                        <th>Position</th>
                        <th>Activity</th>
                        <th>Status</th>
                        <th width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                No users found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['UserID']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($user['Name']) ?></strong><br>
                                <small class="text-muted">@<?= htmlspecialchars($user['Username']) ?></small><br>
                                <small><?= htmlspecialchars($user['Email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($user['Position'] ?: 'N/A') ?></td>
                            <td>
                                <span class="badge bg-primary">Posts: <?= $user['PostCount'] ?></span>
                                <span class="badge bg-secondary">Comments: <?= $user['CommentCount'] ?></span>
                            </td>
                            <td>
                                <?php if ($user['IsAdmin']): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-shield-alt"></i> Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-success">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-nowrap gap-1 align-items-center">
                                    <!-- Edit User Button -->
                                    <a href="admin_edit_user.php?id=<?= $user['UserID'] ?>" 
                                       class="btn btn-warning btn-sm d-flex align-items-center justify-content-center" 
                                       style="width: 32px; height: 32px;" title="Edit User Profile">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- Change Password Button -->
                                    <a href="admin_change_password.php?id=<?= $user['UserID'] ?>" 
                                       class="btn btn-info btn-sm d-flex align-items-center justify-content-center" 
                                       style="width: 32px; height: 32px;" title="Change User Password">
                                        <i class="fas fa-key"></i>
                                    </a>

                                    <?php if ($user['IsAdmin']): ?>
                                        <form method="POST" class="d-inline m-0">
                                            <input type="hidden" name="user_id" value="<?= $user['UserID'] ?>">
                                            <input type="hidden" name="action" value="demote_from_admin">
                                            <button type="submit" class="btn btn-warning btn-sm d-flex align-items-center justify-content-center" 
                                                    style="width: 32px; height: 32px;"
                                                    onclick="return confirm('Demote <?= htmlspecialchars($user['Name']) ?> from admin?')"
                                                    title="Demote from Admin">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" class="d-inline m-0">
                                            <input type="hidden" name="user_id" value="<?= $user['UserID'] ?>">
                                            <input type="hidden" name="action" value="promote_to_admin">
                                            <button type="submit" class="btn btn-success btn-sm d-flex align-items-center justify-content-center" 
                                                    style="width: 32px; height: 32px;"
                                                    onclick="return confirm('Promote <?= htmlspecialchars($user['Name']) ?> to admin?')"
                                                    title="Promote to Admin">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['UserID'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="d-inline m-0">
                                            <input type="hidden" name="user_id" value="<?= $user['UserID'] ?>">
                                            <input type="hidden" name="action" value="delete_user">
                                            <button type="submit" class="btn btn-danger btn-sm d-flex align-items-center justify-content-center" 
                                                    style="width: 32px; height: 32px;"
                                                    onclick="return confirm('⚠️ DELETE USER: <?= htmlspecialchars($user['Name']) ?>? This will remove ALL their posts, comments, and data!')"
                                                    title="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center" 
                                                style="width: 32px; height: 32px;" disabled
                                                title="Current User">
                                            <i class="fas fa-user"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 p-3 bg-light rounded">
            <div class="card-header bg-light text-dark">
                <h5 class="mb-0"><span class="text-warning"><i class="fas fa-bolt"></i> Quick Actions</span></h5>
            </div>
            <div class="btn-group">
                <a href="manage_questions.php" class="btn btn-outline-primary">
                    <i class="fas fa-tasks"></i> Manage Questions
                </a>
                <a href="manage_modules.php" class="btn btn-outline-success">
                    <i class="fas fa-cubes"></i> Manage Modules
                </a>
                <a href="home.php" class="btn btn-outline-primary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
?>