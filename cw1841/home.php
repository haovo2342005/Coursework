<?php
include 'config.php'; 
$posts = [];
$modules = [];
$message = '';
$user_details = [];
$post_count = 0;
$has_filters = false;

try {
    $pdo = new PDO($dsn, $user, $pass); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        $user_stm = $pdo->prepare('SELECT Name, Email, Position, DateOfBirth, Username FROM users WHERE UserID = :user_id');
        $user_stm->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $user_stm->execute();
        $user_details = $user_stm->fetch(PDO::FETCH_ASSOC);
        
        if ($user_details) {
            $_SESSION['email'] = $user_details['Email'];
            $_SESSION['position'] = $user_details['Position'];
        }

        $count_stm = $pdo->prepare('SELECT COUNT(PostID) AS post_count FROM posts WHERE UserID = :user_id');
        $count_stm->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $count_stm->execute();
        $count_result = $count_stm->fetch(PDO::FETCH_ASSOC);
        $post_count = $count_result['post_count'];
    }

    $stmt = $pdo->prepare("SELECT ModuleID, ModuleName FROM modules ORDER BY ModuleName");
    $stmt->execute();
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql_base = 'SELECT p.PostID, p.Title, p.Content, p.PostDate, p.ImagePath, p.UserID, u.Username, m.ModuleName 
                FROM posts p 
                INNER JOIN users u ON p.UserID = u.UserID
                INNER JOIN modules m ON p.ModuleID = m.ModuleID'; 
    
    $where_clauses = [];
    $bind_params = [];
    $search_info = [];

    $module_id = $_GET['module_id'] ?? '';
    if (!empty($module_id) && is_numeric($module_id)) {
        $where_clauses[] = 'p.ModuleID = :module_id';
        $bind_params[':module_id'] = $module_id;
        $has_filters = true;
        
        $selected_module = array_filter($modules, fn($m) => $m['ModuleID'] == $module_id);
        if ($selected_module) {
            $search_info[] = 'Module: ' . htmlspecialchars(reset($selected_module)['ModuleName']);
        }
    }

    $search_term = trim($_GET['search_term'] ?? '');
    if (!empty($search_term)) {
        $where_clauses[] = '(p.Title LIKE :search_term OR p.Content LIKE :search_term)';
        $bind_params[':search_term'] = '%' . $search_term . '%';
        $has_filters = true;
        $search_info[] = 'Search Term: "' . htmlspecialchars($search_term) . '"';
    }

    $sql_full = $sql_base;
    if (!empty($where_clauses)) {
        $sql_full .= ' WHERE ' . implode(' AND ', $where_clauses);
    }
    
    $sql_full .= ' ORDER BY p.PostDate DESC';
            
    $stmt = $pdo->prepare($sql_full); 
    
    foreach ($bind_params as $param_key => $param_value) {
        $param_type = ($param_key === ':module_id') ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindParam($param_key, $bind_params[$param_key], $param_type);
    }

    $stmt->execute(); 
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($posts) > 0) {
        if ($has_filters) {
            $message = "Found " . count($posts) . " result(s). ";
            if (!empty($search_info)) {
                $message .= "Criteria: (" . implode(', ', $search_info) . ")";
            }
        }
    } else {
        $message = "No questions were found matching your criteria.";
        if (!empty($search_info)) {
            $message .= " Criteria: (" . implode(', ', $search_info) . ")";
        }
    }
    
} catch (PDOException $e) {
    $message = "Database connection or query error! Please check your config.php and your MySQL database status."; 
    $posts = [];
    $modules = [];
    $user_details = [];
}

include 'header.php';
?>

<div class="main-content-header d-flex justify-content-between align-items-center">
    <h1 class="h3 text-secondary">Latest Discussions & Threads</h1>
    <a href="post_question.php" class="btn btn-primary btn-lg">
        <i class="fas fa-plus"></i> Post New Question
    </a>
</div>

<div class="mb-4 p-3 bg-white shadow-sm rounded">
    <form method="GET" action="home.php" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label for="search_term" class="form-label small text-muted">Search Questions (Title/Content)</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="search_term" name="search_term" placeholder="E.g., Database normalization" 
                       value="<?= htmlspecialchars($_GET['search_term'] ?? '') ?>">
            </div>
        </div>

        <div class="col-md-4">
            <label for="module_id" class="form-label small text-muted">Filter by Module</label>
            <select class="form-select" id="module_id" name="module_id">
                <option value="">All Modules</option>
                <?php foreach ($modules as $module): ?>
                    <option value="<?= htmlspecialchars($module['ModuleID']) ?>"
                            <?= ($_GET['module_id'] ?? '') == $module['ModuleID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($module['ModuleName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
            <a href="home.php" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<?php if (isset($message) && $message): ?> 
    <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="row g-4">
    <?php if (count($posts) > 0): ?>
        <?php foreach ($posts as $post): ?>
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card shadow post-grid-card">
                <div class="card-body">
                    <span class="badge bg-primary module-badge">
                        <?= htmlspecialchars($post['ModuleName'] ?? 'Module N/A') ?>
                    </span>
                    <?php if (!empty($post['ImagePath'])): ?>
                        <span class="badge bg-success module-badge ms-1">
                            <i class="fas fa-image"></i> Has Image
                        </span>
                    <?php endif; ?>
                    
                    <h5 class="question-title-grid mt-2">
                        <a href="view_post.php?id=<?= $post['PostID'] ?>" class="text-decoration-none text-primary">
                            <?= htmlspecialchars($post['Title'] ?? 'No Title') ?>
                        </a>
                    </h5>
                    
                    <p class="card-text small text-muted text-truncate" style="max-height: 60px;">
                        <?= htmlspecialchars($post['Content'] ?? 'No content available.') ?>
                    </p>
                    
                    <small class="text-secondary d-block mt-3">Author: <?= htmlspecialchars($post['Username'] ?? 'Unknown User') ?></small>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <span>Posted: <?= date('M d, Y', strtotime($post['PostDate'] ?? 'now')) ?></span>
                    <div class="d-flex gap-1">
                        <a href="view_post.php?id=<?= $post['PostID'] ?>" class="btn btn-info btn-sm" title="Read More">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
    <?php endif; ?>
</div>

<?php
include 'footer.php';
?>