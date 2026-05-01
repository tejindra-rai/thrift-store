<?php
require_once '../config.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

require_admin();

$db = Database::getInstance()->getConnection();

$stats = [];

$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_banned = 1");
$stats['banned_users'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM listings");
$stats['total_listings'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM listings WHERE status = 'active'");
$stats['active_listings'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM messages");
$stats['total_messages'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM messages WHERE is_read = 0");
$stats['unread_messages'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM reviews");
$stats['total_reviews'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

$stmt = $db->query("
    SELECT l.*, u.username,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM listings l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 5
");
$recent_listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">

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
                <a href="dashboard.php" class="active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin_users.php">
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Dashboard Overview</h1>
                <div>
                    <span class="text-muted">Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card users">
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card banned">
                    <div class="icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <h3><?php echo $stats['banned_users']; ?></h3>
                    <p>Banned Users</p>
                </div>
                <div class="stat-card listings">
                    <div class="icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <h3><?php echo $stats['total_listings']; ?></h3>
                    <p>Total Listings</p>
                </div>
                <div class="stat-card active">
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3><?php echo $stats['active_listings']; ?></h3>
                    <p>Active Listings</p>
                </div>
                <div class="stat-card messages">
                    <div class="icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3><?php echo $stats['total_messages']; ?></h3>
                    <p>Total Messages</p>
                    <?php if($stats['unread_messages'] > 0): ?>
                        <small class="text-danger"><?php echo $stats['unread_messages']; ?> unread</small>
                    <?php endif; ?>
                </div>
                <div class="stat-card reviews">
                    <div class="icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3><?php echo $stats['total_reviews']; ?></h3>
                    <p>Total Reviews</p>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-users"></i> Recent Users
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Location</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
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
                                        <td>
                                            <?php if($user['is_banned']): ?>
                                                <span class="badge bg-danger">Banned</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="admin_users.php?search=<?php echo urlencode($user['email']); ?>" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="admin_users.php" class="btn btn-outline-primary mt-3">View All Users</a>
                </div>
            </div>

            <!-- Recent Listings -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-box"></i> Recent Listings
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Seller</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_listings as $listing): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo get_image_url($listing['primary_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($listing['title']); ?>"
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($listing['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($listing['username']); ?></td>
                                        <td><?php echo format_price($listing['price']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $listing['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($listing['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($listing['created_at'])); ?></td>
                                        <td>
                                            <a href="admin_listings.php" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="admin_listings.php" class="btn btn-outline-primary mt-3">View All Listings</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin-mobile.js"></script>
</body>
</html>