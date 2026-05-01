<?php
require_once '../config.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

require_admin();

$db = Database::getInstance()->getConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['ban_user'])) {
        $user_id = (int)$_POST['user_id'];
        $stmt = $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $message = '<div class="alert alert-success">User banned successfully!</div>';
        }
    }
    
    if (isset($_POST['unban_user'])) {
        $user_id = (int)$_POST['user_id'];
        $stmt = $db->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $message = '<div class="alert alert-success">User unbanned successfully!</div>';
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        $stmt = $db->prepare("SELECT profile_pic FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($user && $user['profile_pic']) {
            delete_image($user['profile_pic']);
        }
        
        $stmt = $db->prepare("
            SELECT image_path FROM listing_images 
            WHERE listing_id IN (SELECT id FROM listings WHERE user_id = ?)
        ");
        $stmt->execute([$user_id]);
        $images = $stmt->fetchAll();
        foreach($images as $img) {
            delete_image($img['image_path']);
        }
        
        $stmt = $db->prepare("DELETE FROM listing_images WHERE listing_id IN (SELECT id FROM listings WHERE user_id = ?)");
        $stmt->execute([$user_id]);
        
        $stmt = $db->prepare("DELETE FROM listings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $stmt = $db->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
        $stmt->execute([$user_id, $user_id]);
        
        $stmt = $db->prepare("DELETE FROM reviews WHERE from_user_id = ? OR to_user_id = ?");
        $stmt->execute([$user_id, $user_id]);
        
        $stmt = $db->prepare("DELETE FROM wishlists WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $message = '<div class="alert alert-success">User deleted successfully!</div>';
        }
    }
}

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$filter = isset($_GET['filter']) ? sanitize_input($_GET['filter']) : '';

$where_clauses = ["1=1"];
$params = [];

if ($search) {
    $where_clauses[] = "(username LIKE ? OR email LIKE ? OR location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter == 'banned') {
    $where_clauses[] = "is_banned = 1";
} elseif ($filter == 'active') {
    $where_clauses[] = "is_banned = 0";
} elseif ($filter == 'admin') {
    $where_clauses[] = "is_admin = 1";
}

$where_sql = implode(' AND ', $where_clauses);

$sql = "
    SELECT u.*, 
           (SELECT COUNT(*) FROM listings WHERE user_id = u.id) as listing_count,
           (SELECT COUNT(*) FROM reviews WHERE to_user_id = u.id) as review_count
    FROM users u
    WHERE $where_sql
    ORDER BY u.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_users.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> Admin Panel</h3>
                <small>Thrift Store Management</small>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin_users.php" class="active">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="admin_listings.php">
                    <i class="fas fa-box"></i>
                    <span>Manage Listings</span>
                </a>
                <a href="admin_categories.php">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="admin_messages.php">
                    <i class="fas fa-comments"></i>
                    <span>Messages</span>
                </a>
                <a href="admin_contact_messages.php">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Messages</span>
                </a>
                <a href="admin_reviews.php">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 20px 0;">
                <a href="../index.php">
                    <i class="fas fa-globe"></i>
                    <span>View Site</span>
                </a>
                <a href="../assets/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h1 class="mb-4">Manage Users</h1>

            <?php echo $message; ?>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="search" placeholder="Search by username, email, or location..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="filter">
                                <option value="">All Users</option>
                                <option value="active" <?php echo $filter == 'active' ? 'selected' : ''; ?>>Active Users</option>
                                <option value="banned" <?php echo $filter == 'banned' ? 'selected' : ''; ?>>Banned Users</option>
                                <option value="admin" <?php echo $filter == 'admin' ? 'selected' : ''; ?>>Admins</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-users"></i> All Users (<?php echo count($users); ?>)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Avatar</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Location</th>
                                    <th>Rating</th>
                                    <th>Listings</th>
                                    <th>Reviews</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $user): ?>
                                    <tr class="<?php echo $user['is_banned'] ? 'banned-row' : ''; ?>">
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <?php if(image_exists($user['profile_pic'])): ?>
                                                <img src="<?php echo get_image_url($user['profile_pic']); ?>" 
                                                     class="user-avatar" 
                                                     alt="<?php echo htmlspecialchars($user['username']); ?>">
                                            <?php else: ?>
                                                <div class="user-avatar bg-secondary d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if($user['is_admin']): ?>
                                                <span class="badge bg-danger ms-1">Admin</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['location'] ?? '-'); ?></td>
                                        <td>
                                            <span class="text-warning">
                                                <i class="fas fa-star"></i> <?php echo number_format($user['rating'], 1); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $user['listing_count']; ?></td>
                                        <td><?php echo $user['review_count']; ?></td>
                                        <td>
                                            <?php if($user['is_banned']): ?>
                                                <span class="badge bg-danger">Banned</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php if(!$user['is_banned']): ?>
                                                        <li>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" name="ban_user" class="dropdown-item text-warning" onclick="return confirm('Are you sure you want to ban this user?');">
                                                                    <i class="fas fa-ban"></i> Ban User
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php else: ?>
                                                        <li>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" name="unban_user" class="dropdown-item text-success">
                                                                    <i class="fas fa-check"></i> Unban User
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if(!$user['is_admin'] || $user['id'] != $_SESSION['user_id']): ?>
                                                        <li>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" name="delete_user" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this user? This will delete all their listings, messages, and reviews. This action cannot be undone.');">
                                                                    <i class="fas fa-trash"></i> Delete User
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin-mobile.js"></script>
</body>
</html>