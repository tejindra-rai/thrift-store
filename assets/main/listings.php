<?php
require_once '../../config.php';
$db = Database::getInstance()->getConnection();

$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$condition_filter = isset($_GET['condition']) ? sanitize_input($_GET['condition']) : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' && (float)$_GET['max_price'] > 0 ? (float)$_GET['max_price'] : 999999;
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'newest';

$where_clauses = ["l.status = 'active'"];
$params = [];

if ($category_filter > 0) {
    $stmt = $db->prepare("SELECT parent_id FROM categories WHERE id = ?");
    $stmt->execute([$category_filter]);
    $cat_info = $stmt->fetch();
    
    if ($cat_info && $cat_info['parent_id'] === null) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE parent_id = ?");
        $stmt->execute([$category_filter]);
        $subcats = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($subcats)) {
            $placeholders = implode(',', array_fill(0, count($subcats), '?'));
            $where_clauses[] = "l.category_id IN ($placeholders)";
            $params = array_merge($params, $subcats);
        }
    } else {
        $where_clauses[] = "l.category_id = ?";
        $params[] = $category_filter;
    }
}

if (!empty($search_query)) {
    $where_clauses[] = "(l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

if (!empty($condition_filter)) {
    $where_clauses[] = "l.condition = ?";
    $params[] = $condition_filter;
}

if ($min_price > 0) {
    $where_clauses[] = "l.price >= ?";
    $params[] = $min_price;
}

if ($max_price < 999999) {
    $where_clauses[] = "l.price <= ?";
    $params[] = $max_price;
}

$where_sql = implode(' AND ', $where_clauses);

$order_by = match($sort) {
    'price_low' => 'l.price ASC',
    'price_high' => 'l.price DESC',
    'oldest' => 'l.created_at ASC',
    default => 'l.created_at DESC'
};

$sql = "
    SELECT l.*, u.username, u.location, u.rating,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           c.name as category_name
    FROM listings l 
    JOIN users u ON l.user_id = u.id 
    JOIN categories c ON l.category_id = c.id
    WHERE $where_sql
    ORDER BY $order_by
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$main_categories = $stmt->fetchAll();

$subcategories_by_parent = [];
foreach($main_categories as $main_cat) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name");
    $stmt->execute([$main_cat['id']]);
    $subcategories_by_parent[$main_cat['id']] = $stmt->fetchAll();
}

$conditions = ['Like New', 'Excellent', 'Good', 'Fair', 'Vintage'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Listings - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="main_css/listing.css">
</head>
<body>
    <?php include '../../assets/includes/header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-store"></i> Browse All Items</h1>
            <p class="mb-0">Discover unique pre-loved fashion pieces</p>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Filters -->
        <div class="filter-section">
            <h5 class="filter-title"><i class="fas fa-filter"></i> Filter & Search</h5>
            <form method="GET" action="" id="filterForm">
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <input type="text" class="form-control" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    
                    <div class="col-lg-2 col-md-6 mb-3">
                        <select class="form-select" id="mainCategoryFilter" onchange="updateCategoryFilter()">
                            <option value="">All Categories</option>
                            <?php foreach($main_categories as $main_cat): ?>
                                <option value="<?php echo $main_cat['id']; ?>"><?php echo htmlspecialchars($main_cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-2 col-md-6 mb-3">
                        <select class="form-select" name="category" id="subcategoryFilter">
                            <option value="">All Subcategories</option>
                        </select>
                    </div>
                    
                    <div class="col-lg-2 col-md-6 mb-3">
                        <select class="form-select" name="condition">
                            <option value="">All Conditions</option>
                            <?php foreach($conditions as $cond): ?>
                                <option value="<?php echo $cond; ?>" <?php echo $condition_filter == $cond ? 'selected' : ''; ?>>
                                    <?php echo $cond; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-lg-2 col-md-6 mb-3">
                        <input type="number" class="form-control" name="min_price" placeholder="Min Price" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                    </div>
                    
                    <div class="col-lg-2 col-md-6 mb-3">
                        <input type="number" class="form-control" name="max_price" placeholder="Max Price" value="<?php echo $max_price < 999999 ? $max_price : ''; ?>">
                    </div>
                    
                    <div class="col-lg-1 col-md-6 mb-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <select class="form-select" name="sort" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Count -->
        <div class="results-count">
            <strong><?php echo count($listings); ?></strong> items found
        </div>

        <!-- Listings Grid -->
        <?php if(empty($listings)): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>No items found</h3>
                <p>Try adjusting your filters or search terms</p>
                <a href="listings.php" class="btn btn-primary">Clear Filters</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach($listings as $listing): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card listing-card">
                            <div class="position-relative">
                                <img src="<?php echo $listing['primary_image'] ? SITE_URL.'/uploads/'.$listing['primary_image'] : 'https://via.placeholder.com/300x280/cccccc/666666?text=No+Image'; ?>"
                                     class="card-img-top" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                                <?php if(is_logged_in()): ?>
                                    <?php
                                    $stmt_check = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND listing_id = ?");
                                    $stmt_check->execute([get_current_user_id(), $listing['id']]);
                                    $is_wishlisted = $stmt_check->fetch() ? true : false;
                                    ?>
                                    <button class="wishlist-btn <?php echo $is_wishlisted ? 'active' : ''; ?>" 
                                            onclick="toggleWishlist(<?php echo $listing['id']; ?>, event)">
                                        <i class="<?php echo $is_wishlisted ? 'fas' : 'far'; ?> fa-heart"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="listing-title"><?php echo htmlspecialchars($listing['title']); ?></h5>
                                <div class="listing-price"><?php echo format_price($listing['price']); ?></div>
                                <div class="mb-2">
                                    <span class="badge badge-condition"><?php echo htmlspecialchars($listing['condition']); ?></span>
                                    <?php if($listing['size']): ?>
                                        <span class="badge bg-secondary ms-1">Size: <?php echo htmlspecialchars($listing['size']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small mb-2">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($listing['location'] ?? 'Nepal'); ?>
                                    <span class="ms-2">
                                        <i class="fas fa-star text-warning"></i> <?php echo number_format($listing['rating'], 1); ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <i class="far fa-clock"></i> <?php echo time_ago($listing['created_at']); ?>
                                </small>
                                <a href="listing_detail.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-primary w-100 mt-3">View Details</a>
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
        function showToast(message, type = 'success') {
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
        
        const subcategoriesData = <?php echo json_encode($subcategories_by_parent); ?>;
        const currentCategoryFilter = <?php echo $category_filter; ?>;
        
        function updateCategoryFilter() {
            const mainCategoryId = document.getElementById('mainCategoryFilter').value;
            const subcategorySelect = document.getElementById('subcategoryFilter');
            
            subcategorySelect.innerHTML = '<option value="">All Subcategories</option>';
            
            if (mainCategoryId) {
                const mainOption = document.createElement('option');
                mainOption.value = mainCategoryId;
                mainOption.textContent = 'All ' + document.querySelector('#mainCategoryFilter option:checked').textContent;
                subcategorySelect.appendChild(mainOption);
                
                if (subcategoriesData[mainCategoryId]) {
                    subcategoriesData[mainCategoryId].forEach(subcat => {
                        const option = document.createElement('option');
                        option.value = subcat.id;
                        option.textContent = subcat.name;
                        subcategorySelect.appendChild(option);
                    });
                }
            }
            
            if (mainCategoryId && subcategorySelect.value === '') {
                subcategorySelect.value = mainCategoryId;
            }
        }
        
        window.addEventListener('DOMContentLoaded', function() {
            if (currentCategoryFilter > 0) {
                const mainCatOptions = Array.from(document.getElementById('mainCategoryFilter').options);
                const isMainCategory = mainCatOptions.some(opt => opt.value == currentCategoryFilter);
                
                if (isMainCategory) {
                    document.getElementById('mainCategoryFilter').value = currentCategoryFilter;
                    updateCategoryFilter();
                    document.getElementById('subcategoryFilter').value = currentCategoryFilter;
                } else {
                    for (const [mainCatId, subcats] of Object.entries(subcategoriesData)) {
                        if (subcats.some(sub => sub.id == currentCategoryFilter)) {
                            document.getElementById('mainCategoryFilter').value = mainCatId;
                            updateCategoryFilter();
                            document.getElementById('subcategoryFilter').value = currentCategoryFilter;
                            break;
                        }
                    }
                }
            }
        });
        
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
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                    button.classList.toggle('active');
                    
                    if (data.action === 'added') {
                        showToast('Item added to wishlist!', 'success');
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