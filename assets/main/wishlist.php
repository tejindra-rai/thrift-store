<?php
require_once '../../config.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!is_logged_in()) {
    header("Location: " . SITE_URL . "/assets/auth/login.php");
    exit();
}

$user_id = get_current_user_id();
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT l.*, u.username, u.location, u.rating,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as category_name
    FROM wishlists w
    JOIN listings l ON w.listing_id = l.id
    JOIN users u ON l.user_id = u.id
    JOIN categories c ON l.category_id = c.id
    WHERE w.user_id = ? AND l.status = 'active'
    ORDER BY w.id DESC
");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="main_css/wishlist.css">
 
</head>
<body>
    <?php include '../../assets/includes/header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-heart"></i> My Wishlist</h1>
            <p class="mb-0"><?php echo count($wishlist_items); ?> items saved</p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if(empty($wishlist_items)): ?>
            <div class="empty-wishlist">
                <i class="far fa-heart"></i>
                <h3>Your wishlist is empty</h3>
                <p>Start adding items you love!</p>
                <a href="listings.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i> Browse Items
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach($wishlist_items as $item): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="card wishlist-card">
                            <div class="position-relative">
                                <img src="<?php echo $item['primary_image'] ? SITE_URL.'/uploads/'.$item['primary_image'] : 'https://via.placeholder.com/300x280/cccccc/666666?text=No+Image'; ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <button class="remove-btn" onclick="removeFromWishlist(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                                <div class="h5 text-primary mb-2"><?php echo format_price($item['price']); ?></div>
                                <div class="mb-2">
                                    <span class="badge bg-success"><?php echo htmlspecialchars($item['condition']); ?></span>
                                    <?php if($item['size']): ?>
                                        <span class="badge bg-secondary ms-1">Size: <?php echo htmlspecialchars($item['size']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small mb-3">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location'] ?? 'Nepal'); ?>
                                </div>
                                <a href="listing_detail.php?id=<?php echo $item['id']; ?>" class="btn btn-primary w-100">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../assets/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function removeFromWishlist(listingId) {
            if(confirm('Remove this item from your wishlist?')) {
                fetch('../ajax/toggle_wishlist.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({listing_id: listingId})
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to remove item'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to remove item from wishlist');
                });
            }
        }
    </script>
</body>
</html>