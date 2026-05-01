<?php
require_once '../../config.php';
if (!isset($_GET['id'])) {
    redirect('index.php');
}

$listing_id = (int)$_GET['id'];
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT l.*, u.id as seller_id, u.username, u.location as seller_location, u.rating, u.profile_pic,
           c.name as category_name
    FROM listings l 
    JOIN users u ON l.user_id = u.id 
    JOIN categories c ON l.category_id = c.id
    WHERE l.id = ?
");
$stmt->execute([$listing_id]);
$listing = $stmt->fetch();

if (!$listing) {
    redirect('index.php');
}

$stmt = $db->prepare("SELECT * FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC");
$stmt->execute([$listing_id]);
$images = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT r.*, u.username, u.profile_pic 
    FROM reviews r 
    JOIN users u ON r.from_user_id = u.id 
    WHERE r.to_user_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$stmt->execute([$listing['seller_id']]);
$reviews = $stmt->fetchAll();

$in_wishlist = false;
if (is_logged_in()) {
    $stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND listing_id = ?");
    $stmt->execute([get_current_user_id(), $listing_id]);
    $in_wishlist = $stmt->fetch() ? true : false;
}

include '../../assets/includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Image Gallery -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div id="listingCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php if(empty($images)): ?>
                            <div class="carousel-item active">
                                <img src="https://via.placeholder.com/600x400/cccccc/666666?text=No+Image" class="d-block w-100" style="height:500px; object-fit:cover;" alt="No image">
                            </div>
                        <?php else: ?>
                            <?php foreach($images as $index => $image): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $image['image_path']; ?>" class="d-block w-100" style="height:500px; object-fit:cover;" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if(count($images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#listingCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#listingCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Listing Details -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4">
                <h1 class="h2 mb-3"><?php echo htmlspecialchars($listing['title']); ?></h1>
                <div class="h3 text-primary mb-3"><?php echo format_price($listing['price']); ?></div>
                
                <div class="mb-4">
                    <span class="badge bg-info me-2"><?php echo htmlspecialchars($listing['category_name']); ?></span>
                    <span class="badge bg-success me-2"><?php echo htmlspecialchars($listing['condition']); ?></span>
                    <?php if($listing['size']): ?>
                        <span class="badge bg-secondary">Size: <?php echo htmlspecialchars($listing['size']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <h5>Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
                </div>

                <div class="mb-4">
                    <small class="text-muted">
                        <i class="far fa-clock"></i> Posted <?php echo time_ago($listing['created_at']); ?>
                    </small>
                </div>

                <?php if(is_logged_in() && get_current_user_id() != $listing['seller_id']): ?>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#messageModal">
                            <i class="far fa-comment"></i> Contact Seller
                        </button>
                        <button class="btn btn-outline-danger" onclick="toggleWishlist(<?php echo $listing_id; ?>)">
                            <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                            <?php echo $in_wishlist ? 'Remove from' : 'Add to'; ?> Wishlist
                        </button>
                    </div>
                <?php elseif(!is_logged_in()): ?>
                    <div class="alert alert-info">
                        <a href="login.php">Login</a> to contact the seller
                    </div>
                <?php endif; ?>
            </div>

            <!-- Seller Info -->
            <div class="card border-0 shadow-sm p-4 mt-4">
                <h5 class="mb-3">Seller Information</h5>
                <div class="d-flex align-items-center">
                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $listing['profile_pic']; ?>" 
                         class="rounded-circle me-3" 
                         style="width:60px; height:60px; object-fit:cover;"
                         onerror="this.src='assets/default-avatar.jpg'">
                    <div>
                        <h6 class="mb-0"><?php echo htmlspecialchars($listing['username']); ?></h6>
                        <div class="text-warning">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="<?php echo $i <= round($listing['rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                            <span class="text-muted ms-1">(<?php echo number_format($listing['rating'], 1); ?>)</span>
                        </div>
                        <?php if($listing['seller_location']): ?>
                            <small class="text-muted">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($listing['seller_location']); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <?php if(!empty($reviews)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="mb-4">Seller Reviews</h3>
                <?php foreach($reviews as $review): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $review['profile_pic']; ?>" 
                                     class="rounded-circle me-2" 
                                     style="width:40px; height:40px; object-fit:cover;"
                                     onerror="this.src='assets/default-avatar.jpg'">
                                <div>
                                    <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                    <div class="text-warning">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small class="text-muted ms-auto"><?php echo time_ago($review['created_at']); ?></small>
                            </div>
                            <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contact Seller</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="messageForm">
                <div class="modal-body">
                    <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
                    <input type="hidden" name="receiver_id" value="<?php echo $listing['seller_id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="4" required placeholder="Hi, I'm interested in this item..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('messageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../ajax/send_message.php', {  
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Message sent successfully!');
            bootstrap.Modal.getInstance(document.getElementById('messageModal')).hide();
            this.reset();
        } else {
            alert('Failed to send message: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to send message. Please try again.');
    });
});

function toggleWishlist(listingId) {
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
            alert('Failed to update wishlist: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update wishlist. Please try again.');
    });
}
</script>

<?php include '../../assets/includes/footer.php'; ?>