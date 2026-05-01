<?php
$base_path = dirname(dirname(dirname(__FILE__)));
$relative_to_root = str_replace($_SERVER['DOCUMENT_ROOT'], '', $base_path);

if (!isset($db)) {
    require_once $base_path . '/config.php';
    $db = Database::getInstance()->getConnection();
}

$stmt = $db->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$main_categories = $stmt->fetchAll();

$subcategories_by_parent = [];
foreach($main_categories as $main_cat) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name");
    $stmt->execute([$main_cat['id']]);
    $subcategories_by_parent[$main_cat['id']] = $stmt->fetchAll();
}

$unread_count = 0;
if (is_logged_in()) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([get_current_user_id()]);
    $unread_count = $stmt->fetch()['count'];
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/includes/include_css/header.css">


<div class="nav-wrapper">
    <div class="desktop-nav d-none d-lg-flex">
        <div class="left-nav">
            <a href="<?php echo SITE_URL; ?>/index.php">Home</a>
            
            <div class="categories-dropdown">
                <span class="categories-link">Categories</span>
                <div class="dropdown-menu-custom">
                    <?php if(!empty($main_categories)): ?>
                        <?php foreach($main_categories as $main_cat): ?>
                            <div class="has-submenu-parent">
                                <a href="<?php echo SITE_URL; ?>/assets/main/listings.php?category=<?php echo $main_cat['id']; ?>" 
                                   class="dropdown-item-custom">
                                    <?php echo htmlspecialchars($main_cat['name']); ?>
                                </a>
                                <?php if(!empty($subcategories_by_parent[$main_cat['id']])): ?>
                                    <div class="submenu-custom">
                                        <?php foreach($subcategories_by_parent[$main_cat['id']] as $sub_cat): ?>
                                            <a href="<?php echo SITE_URL; ?>/assets/main/listings.php?category=<?php echo $sub_cat['id']; ?>" 
                                               class="dropdown-item-custom">
                                                <?php echo htmlspecialchars($sub_cat['name']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <hr style="margin: 10px 0;">
                    <a href="<?php echo SITE_URL; ?>/assets/main/listings.php" class="dropdown-item-custom">
                        <strong>View All Items</strong>
                    </a>
                </div>
            </div>
            
            <a href="<?php echo SITE_URL; ?>/assets/info/about.php">About Us</a>
            <a href="<?php echo SITE_URL; ?>/assets/info/contact.php">Contact</a>
        </div>
        
        <div class="middle">
            <a href="<?php echo SITE_URL; ?>/index.php">
                <img src="<?php echo SITE_URL; ?>/images/icon/logo.png" alt="JML">
            </a>
        </div>
        
        <div class="right-nav">
            <div class="text-hover">
                <a href="<?php echo SITE_URL; ?>/assets/main/sell.php" title="Sell Item">
                    <i class="fas fa-plus-circle"></i>
                </a>
            </div>
            <div class="text-hover">
                <a href="<?php echo SITE_URL; ?>/assets/main/wishlist.php" title="Wishlist">
                    <i class="fa-regular fa-heart"></i>
                </a>
            </div>

            <?php if(is_logged_in()): ?>
                <?php if(is_admin()): ?>
                    <div class="text-hover">
                        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" title="Admin Panel" style="background: rgba(255,215,0,0.2);">
                            <i class="fas fa-shield-alt" style="color: #FFD700;"></i>
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="text-hover">
                    <a href="<?php echo SITE_URL; ?>/assets/main/messages.php" title="Messages">
                        <i class="fa-regular fa-comments"></i>
                        <?php if($unread_count > 0): ?>
                            <span class="message-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="text-hover">
                    <a href="<?php echo SITE_URL; ?>/assets/main/profile.php" title="Profile">
                        <i class="fa-regular fa-user"></i>
                    </a>
                </div>
                <div class="text-hover">
                    <a href="<?php echo SITE_URL; ?>/assets/auth/logout.php" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="text-hover">
                    <a href="<?php echo SITE_URL; ?>/assets/main/listings.php" title="Browse">
                        <i class="fas fa-shopping-bag"></i>
                    </a>
                </div>
                <div class="text-hover">
                    <a href="<?php echo SITE_URL; ?>/assets/auth/login.php" title="Login">
                        <i class="fa-regular fa-user"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mobile-nav d-lg-none">
        <div class="mobile-logo">
            <a href="<?php echo SITE_URL; ?>/index.php">
                <img src="<?php echo SITE_URL; ?>/images/icon/logo.png" alt="JML">
            </a>
        </div>
        <label for="mobile-menu-toggle" class="hamburger-label">
            <i class="fas fa-bars"></i>
        </label>
    </div>
</div>

<input type="checkbox" id="mobile-menu-toggle">
<label for="mobile-menu-toggle" class="mobile-overlay"></label>
<div class="mobile-menu">
    <div class="mobile-menu-header">
        <h5 class="m-0">Menu</h5>
        <label for="mobile-menu-toggle" class="close-menu-label">
            <i class="fas fa-times"></i>
        </label>
    </div>
    
    <div class="mobile-menu-body">
        <a href="<?php echo SITE_URL; ?>/index.php" class="mobile-menu-item">
            <span><i class="fas fa-home"></i> Home</span>
        </a>
        
        <input type="checkbox" id="categories-toggle" class="submenu-toggle">
        <label for="categories-toggle" class="submenu-toggle-label">
            <span><i class="fas fa-th-large"></i> Categories</span>
            <i class="fas fa-chevron-down"></i>
        </label>
        <div class="mobile-submenu">
            <?php if(!empty($main_categories)): ?>
                <?php foreach($main_categories as $index => $main_cat): ?>
                    <input type="checkbox" id="cat-toggle-<?php echo $index; ?>" class="nested-toggle">
                    <label for="cat-toggle-<?php echo $index; ?>" class="nested-toggle-label">
                        <span><?php echo htmlspecialchars($main_cat['name']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </label>
                    <div class="mobile-nested-submenu">
                        <?php if(!empty($subcategories_by_parent[$main_cat['id']])): ?>
                            <?php foreach($subcategories_by_parent[$main_cat['id']] as $sub_cat): ?>
                                <a href="<?php echo SITE_URL; ?>/assets/main/listings.php?category=<?php echo $sub_cat['id']; ?>" 
                                   class="mobile-submenu-item">
                                    <?php echo htmlspecialchars($sub_cat['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/assets/main/listings.php" class="mobile-submenu-item">
                <strong>View All Items</strong>
            </a>
        </div>
        
        <a href="<?php echo SITE_URL; ?>/assets/info/about.php" class="mobile-menu-item">
            <span><i class="fas fa-info-circle"></i> About Us</span>
        </a>
        
        <a href="<?php echo SITE_URL; ?>/assets/info/contact.php" class="mobile-menu-item">
            <span><i class="fas fa-envelope"></i> Contact</span>
        </a>
        
        <a href="<?php echo SITE_URL; ?>/assets/main/sell.php" class="mobile-menu-item">
            <span><i class="fas fa-plus-circle"></i> Sell Item</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/assets/main/wishlist.php" class="mobile-menu-item">
            <span><i class="fa-regular fa-heart"></i> Wishlist</span>
        </a>

        <?php if(is_logged_in()): ?>
            <?php if(is_admin()): ?>
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="mobile-menu-item" style="background: rgba(255,215,0,0.1); border-left: 4px solid #FFD700;">
                    <span><i class="fas fa-shield-alt" style="color: #FFD700;"></i> Admin Panel</span>
                </a>
            <?php endif; ?>
            
            <a href="<?php echo SITE_URL; ?>/assets/main/messages.php" class="mobile-menu-item">
                <span><i class="fa-regular fa-comments"></i> Messages</span>
                <?php if($unread_count > 0): ?>
                    <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo SITE_URL; ?>/assets/main/profile.php" class="mobile-menu-item">
                <span><i class="fa-regular fa-user"></i> My Profile</span>
            </a>
        <?php endif; ?>
        
        <div class="mobile-actions">
            <?php if(is_logged_in()): ?>
                <a href="<?php echo SITE_URL; ?>/assets/main/sell.php" class="mobile-action-btn">
                    <i class="fas fa-plus-circle"></i> Sell Item
                </a>
                <a href="<?php echo SITE_URL; ?>/assets/auth/logout.php" class="mobile-action-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/assets/auth/login.php" class="mobile-action-btn">
                    <i class="fa-regular fa-user"></i> Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>