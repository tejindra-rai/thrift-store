<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT l.*, 
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM listings l 
    WHERE l.status = 'active' 
    ORDER BY l.created_at DESC 
    LIMIT 6
");
$stmt->execute();
$featured_listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>fashion-store-website</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Jua&family=Just+Another+Hand&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index_css/index.css">
</head>
<body>

    <?php include 'assets/includes/header.php'; ?>

    <div class="slider-container">
        <div class="slide">
            <div class="first-line">
                <p class="fashion-an">Fashion<br>An</p>
            </div>
            <img src="images/men/n1.png" class="model-img" alt="Fashion Model">
            <div class="second-line">
                <p class="is-art">Is<br>Art</p>
            </div>
        </div>
    </div>

    <div class="second-content">
        <h1>Men's</h1>
        <div class="images-men-grid">
            <img src="images/men/41.jpeg" class="men-tall-left" onclick="window.location.href='assets/main/listings.php?category=1'" alt="Men's Fashion">
            <img src="images/men/8.jpeg" class="men-middle" onclick="window.location.href='assets/main/listings.php?category=1'" alt="Men's Fashion">
            <img src="images/men/0.jpeg" class="men-tall-right" onclick="window.location.href='assets/main/listings.php?category=1'" alt="Men's Fashion">
        </div>
        <div class="buttons">
            <button onclick="window.location.href='assets/main/listings.php?category=1'">Shop Now</button>
            <button onclick="window.location.href='assets/main/listings.php?category=1'">View All</button>
        </div>
    </div>

        <div class="women-hero-container">
            <div class="women-hero-slide">
                <div class="women-left-side">
                    <p class="women-slogan-left">Style<br>That</p>
                </div>
                <img src="images/women/jes.png" class="women-model-img" alt="Women's Fashion">
                <div class="women-right-side">
                    <p class="women-slogan-right">Lives<br>Again</p>
                </div>
            </div>
        </div>

    <div class="fourth-content">
        <h1>Women's</h1>
        <div class="image2">
            <img src="images/women/51.jpeg" class="im1" onclick="window.location.href='assets/main/listings.php?category=2'" alt="Women's Collection">
            <img src="images/women/46.jpeg" class="im2" onclick="window.location.href='assets/main/listings.php?category=2'" alt="Women's Collection">
            <img src="images/women/53.jpeg" class="im1" onclick="window.location.href='assets/main/listings.php?category=2'" alt="Women's Collection">
        </div>

        <div class="buttons">
            <button onclick="window.location.href='assets/main/listings.php?category=2'">Shop Now</button>
            <button onclick="window.location.href='assets/main/listings.php?category=2'">View All</button>
        </div>
    </div>

    <?php include 'assets/includes/footer.php'; ?>

</body>
</html>