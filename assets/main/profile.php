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

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_listing_status'])) {
    $listing_id = (int)$_POST['listing_id'];
    $new_status = sanitize_input($_POST['status']);
    
    $stmt = $db->prepare("SELECT id FROM listings WHERE id = ? AND user_id = ?");
    $stmt->execute([$listing_id, $user_id]);
    
    if ($stmt->fetch()) {
        $stmt = $db->prepare("UPDATE listings SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $listing_id])) {
            $success = 'Listing status updated successfully!';
        } else {
            $error = 'Failed to update listing status.';
        }
    } else {
        $error = 'Unauthorized action.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_listing'])) {
    $listing_id = (int)$_POST['listing_id'];
    
    $stmt = $db->prepare("SELECT id FROM listings WHERE id = ? AND user_id = ?");
    $stmt->execute([$listing_id, $user_id]);
    
    if ($stmt->fetch()) {
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
            $success = 'Listing deleted successfully!';
        } else {
            $error = 'Failed to delete listing.';
        }
    } else {
        $error = 'Unauthorized action.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = sanitize_input($_POST['username']);
    $bio = sanitize_input($_POST['bio']);
    $location = sanitize_input($_POST['location']);
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $filename = upload_image($_FILES['profile_pic'], 'profile');
        
        if ($filename) {
            if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default.jpg') {
                delete_image($user['profile_pic']);
            }
            
            $stmt = $db->prepare("UPDATE users SET username = ?, bio = ?, location = ?, profile_pic = ? WHERE id = ?");
            if ($stmt->execute([$username, $bio, $location, $filename, $user_id])) {
                $success = 'Profile updated successfully!';
            } else {
                $error = 'Database update failed.';
            }
        } else {
            $error = 'Failed to upload profile picture. Please ensure the file is a valid image (JPG, PNG, GIF) under 5MB.';
        }
    } else {
        $stmt = $db->prepare("UPDATE users SET username = ?, bio = ?, location = ? WHERE id = ?");
        if ($stmt->execute([$username, $bio, $location, $user_id])) {
            $success = 'Profile updated successfully!';
        } else {
            $error = 'Failed to update profile.';
        }
    }
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} else {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

$stmt = $db->prepare("
    SELECT l.*, 
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           (SELECT COUNT(*) FROM wishlists WHERE listing_id = l.id) as wishlist_count
    FROM listings l 
    WHERE l.user_id = ?
    ORDER BY l.created_at DESC
");
$stmt->execute([$user_id]);
$user_listings = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT r.*, u.username, u.profile_pic, l.title as listing_title
    FROM reviews r 
    JOIN users u ON r.from_user_id = u.id
    JOIN listings l ON r.listing_id = l.id
    WHERE r.to_user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll();

$stmt = $db->prepare("SELECT COUNT(*) as total FROM listings WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_listings = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM listings WHERE user_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$active_listings = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM wishlists WHERE user_id = ?");
$stmt->execute([$user_id]);
$wishlist_count = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_messages = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="main_css/profile.css">
</head>
<body>
    <?php include '../../assets/includes/header.php'; ?>

    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center mb-3 mb-md-0">
                    <?php 
                    $has_profile_pic = !empty($user['profile_pic']) && 
                                      $user['profile_pic'] !== 'default.jpg' && 
                                      image_exists($user['profile_pic']);
                    ?>
                    <?php if($has_profile_pic): ?>
                        <img src="<?php echo get_image_url($user['profile_pic']); ?>" 
                             class="profile-avatar" alt="Profile Picture">
                    <?php else: ?>
                        <div class="profile-avatar d-flex align-items-center justify-content-center bg-white">
                            <i class="fas fa-user fa-4x" style="color: var(--primary-light);"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h1 class="mb-2"><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="mb-2">
                        <i class="fas fa-map-marker-alt"></i> 
                        <?php echo htmlspecialchars($user['location'] ?? 'Location not set'); ?>
                    </p>
                    <div class="rating-stars mb-2">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <i class="<?php echo $i <= round($user['rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                        <span class="ms-2"><?php echo number_format($user['rating'], 1); ?> / 5.0</span>
                    </div>
                    <?php if($user['bio']): ?>
                        <p class="mt-3 mb-0"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <div class="action-buttons">
                        <a href="wishlist.php" class="action-btn primary">
                            <i class="fas fa-heart"></i> Wishlist
                            <?php if($wishlist_count > 0): ?>
                                <span class="badge-notification"><?php echo $wishlist_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="messages.php" class="action-btn primary">
                            <i class="fas fa-comments"></i> Messages
                            <?php if($unread_messages > 0): ?>
                                <span class="badge-notification"><?php echo $unread_messages; ?></span>
                            <?php endif; ?>
                        </a>
                        <button class="action-btn outline" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                        <a href="<?php echo SITE_URL; ?>/assets/auth/logout.php" class="action-btn outline">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-box"></i>
                    <h3><?php echo $total_listings; ?></h3>
                    <p>Total Listings</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $active_listings; ?></h3>
                    <p>Active Listings</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-heart"></i>
                    <h3><?php echo $wishlist_count; ?></h3>
                    <p>Wishlisted Items</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card">
                    <i class="fas fa-star"></i>
                    <h3><?php echo count($reviews); ?></h3>
                    <p>Reviews</p>
                </div>
            </div>
        </div>

        <!-- My Listings -->
        <div class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="section-title mb-0">
                    <i class="fas fa-box"></i> My Listings
                </h3>
                <a href="sell.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Create New Listing
                </a>
            </div>
            
            <?php if(empty($user_listings)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>You haven't created any listings yet</p>
                    <a href="sell.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus-circle"></i> Create First Listing
                    </a>
                </div>
            <?php else: ?>
                <?php foreach($user_listings as $listing): ?>
                    <div class="listing-item">
                        <div class="row align-items-center">
                            <div class="col-md-2 col-3">
                                <img src="<?php echo get_image_url($listing['primary_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($listing['title']); ?>"
                                     class="listing-item-img">
                            </div>
                            <div class="col-md-5 col-9">
                                <h5 class="mb-1"><?php echo htmlspecialchars($listing['title']); ?></h5>
                                <div class="text-primary fw-bold"><?php echo format_price($listing['price']); ?></div>
                                <small class="text-muted">
                                    <i class="far fa-clock"></i> <?php echo time_ago($listing['created_at']); ?> • 
                                    <i class="far fa-heart"></i> <?php echo $listing['wishlist_count']; ?> wishlisted
                                </small>
                            </div>
                            <div class="col-md-5 col-12 mt-3 mt-md-0">
                                <div class="listing-actions">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                        <select name="status" class="form-select form-select-sm d-inline-block status-select" onchange="this.form.submit()">
                                            <option value="active" <?php echo $listing['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="sold" <?php echo $listing['status'] == 'sold' ? 'selected' : ''; ?>>Sold</option>
                                            <option value="inactive" <?php echo $listing['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                        <input type="hidden" name="update_listing_status" value="1">
                                    </form>
                                    
                                    <a href="listing_detail.php?id=<?php echo $listing['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this listing? This action cannot be undone.');">
                                        <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                        <button type="submit" name="delete_listing" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Reviews -->
        <div class="section-card">
            <h3 class="section-title">
                <i class="fas fa-star"></i> Reviews (<?php echo count($reviews); ?>)
            </h3>
            
            <?php if(empty($reviews)): ?>
                <p class="text-muted">No reviews yet</p>
            <?php else: ?>
                <?php foreach($reviews as $review): ?>
                    <div class="review-item">
                        <div class="d-flex align-items-center mb-2">
                            <?php 
                            $has_review_pic = !empty($review['profile_pic']) && 
                                            $review['profile_pic'] !== 'default.jpg' && 
                                            image_exists($review['profile_pic']);
                            ?>
                            <?php if($has_review_pic): ?>
                                <img src="<?php echo get_image_url($review['profile_pic']); ?>" 
                                     class="rounded-circle me-2" 
                                     style="width:40px;height:40px;object-fit:cover;">
                            <?php else: ?>
                                <div class="rounded-circle me-2 bg-secondary d-flex align-items-center justify-content-center" 
                                     style="width:40px;height:40px;">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                <div class="rating-stars">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <small class="text-muted"><?php echo time_ago($review['created_at']); ?></small>
                        </div>
                        <p class="mb-1"><?php echo htmlspecialchars($review['comment']); ?></p>
                        <small class="text-muted">For: <?php echo htmlspecialchars($review['listing_title']); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Profile Picture</label>
                            <?php if($user['profile_pic']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Current: <?php echo htmlspecialchars($user['profile_pic']); ?></small>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="profile_pic" accept="image/*">
                            <small class="text-muted">Allowed: JPG, PNG, GIF (Max 5MB)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../assets/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>