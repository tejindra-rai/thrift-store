<?php
require_once '../../config.php';
$db = Database::getInstance()->getConnection();

$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if ($category_id <= 0) {
    redirect('listings.php');
}

$stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    redirect('listings.php');
}

$is_main_category = ($category['parent_id'] === NULL);

if ($is_main_category) {
    $main_category = $category;
    $category_name = htmlspecialchars($main_category['name']);
    
    $stmt = $db->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name");
    $stmt->execute([$category_id]);
    $subcategories = $stmt->fetchAll();
    
    $organized_items = [];
    
    foreach ($subcategories as $subcat) {
        $stmt = $db->prepare("
            SELECT l.*, 
                   (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
                   u.username, u.location, u.rating
            FROM listings l 
            JOIN users u ON l.user_id = u.id
            WHERE l.category_id = ? AND l.status = 'active'
            ORDER BY l.created_at DESC
            LIMIT 12
        ");
        $stmt->execute([$subcat['id']]);
        $items = $stmt->fetchAll();
        
        if (!empty($items)) {
            $organized_items[$subcat['name']] = $items;
        }
    }
} else {
    $category_name = htmlspecialchars($category['name']);
    
    $stmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category['parent_id']]);
    $parent = $stmt->fetch();
    $parent_name = $parent ? htmlspecialchars($parent['name']) : '';
    
    $stmt = $db->prepare("
        SELECT l.*, 
               (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
               u.username, u.location, u.rating
        FROM listings l 
        JOIN users u ON l.user_id = u.id
        WHERE l.category_id = ? AND l.status = 'active'
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$category_id]);
    $items = $stmt->fetchAll();
    
    $organized_items = [];
    if (!empty($items)) {
        $organized_items[$category_name] = $items;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category_name; ?> Collection - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="main_css/category-listing.css">
    <style>

    </style>
</head>
<body>
    <?php include '../../assets/includes/header.php'; ?>

    <div class="content">
        <h1><?php echo $category_name; ?> Collection</h1>
        
        <?php if (empty($organized_items)): ?>
            <div class="no-items">
                <i class="fas fa-box-open"></i>
                <h3>No items available yet</h3>
                <p>This category is waiting for amazing items. Check back soon!</p>
                <a href="listings.php" class="btn btn-primary btn-lg mt-3">
                    <i class="fas fa-th"></i> Browse All Items
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($organized_items as $subcat_name => $items): ?>
                <div class="items">
                    <h3><?php echo htmlspecialchars($subcat_name); ?></h3>
                    
                    <div class="inner-items">
                        <div class="clothes">
                            <?php foreach ($items as $item): ?>
                                <div class="item">
                                    <a href="listing_detail.php?id=<?php echo $item['id']; ?>">
                                        <img src="<?php echo $item['primary_image'] ? SITE_URL.'/uploads/'.$item['primary_image'] : 'https://via.placeholder.com/300x400/cccccc/666666?text=No+Image'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                                        <div class="item-info">
                                            <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                                            <p class="price"><?php echo format_price($item['price']); ?></p>
                                            <div class="item-meta">
                                                <span class="badge badge-condition"><?php echo htmlspecialchars($item['condition']); ?></span>
                                                <?php if($item['size']): ?>
                                                    <span class="badge badge-size">Size: <?php echo htmlspecialchars($item['size']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location'] ?? 'Nepal'); ?>
                                            </small>
                                        </div>
                                    </a>
                                    <?php if(is_logged_in()): ?>
                                        <?php
                                        $stmt_check = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND listing_id = ?");
                                        $stmt_check->execute([get_current_user_id(), $item['id']]);
                                        $is_wishlisted = $stmt_check->fetch() ? true : false;
                                        ?>
                                        <button class="wishlist-btn <?php echo $is_wishlisted ? 'active' : ''; ?>" 
                                                onclick="toggleWishlist(<?php echo $item['id']; ?>, event)">
                                            <i class="<?php echo $is_wishlisted ? 'fas' : 'far'; ?> fa-heart"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include '../../assets/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>        function showToast(message, type = 'success') {
            const existingToast = document.querySelector('.toast-notification');
            if (existingToast) {
                existingToast.remove();
            }
            
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                </div>
                <div class="toast-message">${message}</div>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('hiding');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        function toggleWishlist(listingId, event) {
            event.preventDefault();
            event.stopPropagation();
            
            const button = event.currentTarget;
            const icon = button.querySelector('i');
            
            fetch('../ajax/toggle_wishlist.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({listing_id: listingId})
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    icon.classList.toggle('fas');
                    icon.classList.toggle('far');
                    button.classList.toggle('active');
                    
                    if (data.action === 'added') {
                        showToast('Item added to wishlist! ❤️', 'success');
                    } else {
                        showToast('Item removed from wishlist', 'success');
                    }
                } else {
                    showToast(data.message || 'Failed to update wishlist', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            });
        }
    </script>
</body>
</html>