<?php
require_once '../config.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

require_admin();

$db = Database::getInstance()->getConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_review'])) {
    $review_id = (int)$_POST['review_id'];
    
    $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
    if ($stmt->execute([$review_id])) {
        $message = '<div class="alert alert-success">Review deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to delete review.</div>';
    }
}

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;

$where_clauses = ["1=1"];
$params = [];

if ($search) {
    $where_clauses[] = "(from_user.username LIKE ? OR to_user.username LIKE ? OR r.comment LIKE ? OR l.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($rating_filter > 0) {
    $where_clauses[] = "r.rating = ?";
    $params[] = $rating_filter;
}

$where_sql = implode(' AND ', $where_clauses);

$sql = "
    SELECT r.*, 
           from_user.username as reviewer_name,
           from_user.profile_pic as reviewer_pic,
           to_user.username as reviewed_name,
           l.title as listing_title
    FROM reviews r
    JOIN users from_user ON r.from_user_id = from_user.id
    JOIN users to_user ON r.to_user_id = to_user.id
    JOIN listings l ON r.listing_id = l.id
    WHERE $where_sql
    ORDER BY r.rating ASC, r.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$stmt = $db->query("SELECT COUNT(*) as total FROM reviews");
$total_reviews = $stmt->fetch()['total'];

$stmt = $db->query("SELECT AVG(rating) as avg_rating FROM reviews");
$avg_rating = $stmt->fetch()['avg_rating'] ?? 0;

$stmt = $db->query("SELECT COUNT(*) as total FROM reviews WHERE rating = 5");
$five_star = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM reviews WHERE rating = 1");
$one_star = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_reviews.css">

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
                <a href="admin_reviews.php" class="active">
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
            <h1 class="mb-4">Manage Reviews</h1>

            <?php echo $message; ?>

            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-box">
                    <h3><?php echo $total_reviews; ?></h3>
                    <p class="text-muted mb-0">Total Reviews</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo number_format($avg_rating, 1); ?> <i class="fas fa-star text-warning"></i></h3>
                    <p class="text-muted mb-0">Average Rating</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo $five_star; ?></h3>
                    <p class="text-muted mb-0">5-Star Reviews</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo $one_star; ?></h3>
                    <p class="text-muted mb-0">1-Star Reviews</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="search" placeholder="Search reviews, users, or listings..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="rating">
                                <option value="">All Ratings</option>
                                <option value="5" <?php echo $rating_filter == 5 ? 'selected' : ''; ?>>5 Stars</option>
                                <option value="4" <?php echo $rating_filter == 4 ? 'selected' : ''; ?>>4 Stars</option>
                                <option value="3" <?php echo $rating_filter == 3 ? 'selected' : ''; ?>>3 Stars</option>
                                <option value="2" <?php echo $rating_filter == 2 ? 'selected' : ''; ?>>2 Stars</option>
                                <option value="1" <?php echo $rating_filter == 1 ? 'selected' : ''; ?>>1 Star</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-star"></i> All Reviews (<?php echo count($reviews); ?>)
                </div>
                <div class="card-body">
                    <?php if(empty($reviews)): ?>
                        <p class="text-muted text-center">No reviews found</p>
                    <?php else: ?>
                        <?php foreach($reviews as $review): ?>
                            <div class="review-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center">
                                        <?php if(image_exists($review['reviewer_pic'])): ?>
                                            <img src="<?php echo get_image_url($review['reviewer_pic']); ?>" 
                                                 class="reviewer-avatar me-3" 
                                                 alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>">
                                        <?php else: ?>
                                            <div class="reviewer-avatar bg-secondary d-flex align-items-center justify-content-center me-3">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong>
                                            <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                            <strong><?php echo htmlspecialchars($review['reviewed_name']); ?></strong>
                                            <div class="rating-stars">
                                                <?php for($i=1; $i<=5; $i++): ?>
                                                    <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" name="delete_review" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-box"></i> <?php echo htmlspecialchars($review['listing_title']); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="far fa-clock"></i> <?php echo time_ago($review['created_at']); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin-mobile.js"></script>

</body>
</html>