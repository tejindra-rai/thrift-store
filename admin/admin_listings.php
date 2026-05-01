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
    if (isset($_POST['delete_listing'])) {
        $listing_id = (int)$_POST['listing_id'];
        
        $stmt = $db->prepare("SELECT image_path FROM listing_images WHERE listing_id = ?");
        $stmt->execute([$listing_id]);
        $images = $stmt->fetchAll();
        
        foreach($images as $img) {
            delete_image($img['image_path']);
        }
        
        $stmt = $db->prepare("DELETE FROM listing_images WHERE listing_id = ?");
        $stmt->execute([$listing_id]);
        
        $stmt = $db->prepare("DELETE FROM listings WHERE id = ?");
        if ($stmt->execute([$listing_id])) {
            $message = '<div class="alert alert-success">Listing deleted successfully!</div>';
        }
    }
    
    if (isset($_POST['change_status'])) {
        $listing_id = (int)$_POST['listing_id'];
        $status = sanitize_input($_POST['status']);
        
        $stmt = $db->prepare("UPDATE listings SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $listing_id])) {
            $message = '<div class="alert alert-success">Status updated successfully!</div>';
        }
    }
}

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$where_clauses = ["1=1"];
$params = [];

if ($search) {
    $where_clauses[] = "(l.title LIKE ? OR l.description LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_clauses[] = "l.status = ?";
    $params[] = $status_filter;
}

if ($category_filter > 0) {
    $where_clauses[] = "l.category_id = ?";
    $params[] = $category_filter;
}

$where_sql = implode(' AND ', $where_clauses);

$sql = "
    SELECT l.*, u.username, u.email,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as category_name,
           (SELECT COUNT(*) FROM wishlists WHERE listing_id = l.id) as wishlist_count
    FROM listings l
    JOIN users u ON l.user_id = u.id
    JOIN categories c ON l.category_id = c.id
    WHERE $where_sql
    ORDER BY l.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Listings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_listings.css">


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
                <a href="admin_users.php">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="admin_listings.php" class="active">
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
            <h1 class="mb-4">Manage Listings</h1>

            <?php echo $message; ?>

            <!-- Filters -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search listings or users..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="sold" <?php echo $status_filter == 'sold' ? 'selected' : ''; ?>>Sold</option>
                            <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-box"></i> All Listings (<?php echo count($listings); ?>)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Seller</th>
                                    <th>Price</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Wishlisted</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($listings as $listing): ?>
                                    <tr>
                                        <td><?php echo $listing['id']; ?></td>
                                        <td>
                                            <img src="<?php echo get_image_url($listing['primary_image']); ?>" 
                                                 class="listing-image" 
                                                 alt="<?php echo htmlspecialchars($listing['title']); ?>">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($listing['title']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($listing['description'], 0, 50)); ?>...</small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($listing['username']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($listing['email']); ?></small>
                                        </td>
                                        <td><?php echo format_price($listing['price']); ?></td>
                                        <td><?php echo htmlspecialchars($listing['category_name']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                                <select name="status" class="form-select form-select-sm status-badge bg-<?php echo $listing['status'] == 'active' ? 'success' : ($listing['status'] == 'sold' ? 'warning' : 'secondary'); ?> text-white" onchange="this.form.submit()">
                                                    <option value="active" <?php echo $listing['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="sold" <?php echo $listing['status'] == 'sold' ? 'selected' : ''; ?>>Sold</option>
                                                    <option value="inactive" <?php echo $listing['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                                <input type="hidden" name="change_status" value="1">
                                            </form>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="fas fa-heart"></i> <?php echo $listing['wishlist_count']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($listing['created_at'])); ?></td>
                                        <td>
                                            <a href="../assets/main/listing_detail.php?id=<?php echo $listing['id']; ?>" 
                                               class="btn btn-sm btn-primary mb-1" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this listing? This action cannot be undone.');">
                                                <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                                <button type="submit" name="delete_listing" class="btn btn-sm btn-danger mb-1">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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