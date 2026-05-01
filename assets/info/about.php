<?php
require_once '../../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Just+Another+Hand&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="info_css/about.css">
</head>
<body>
    <?php include '../../assets/includes/header.php'; ?>

    <div class="hero-section">
        <div class="container">
            <h1>About JML Thrift Store</h1>
            <p class="lead">Empowering Sustainable Fashion, One Pre-Loved Item at a Time</p>
        </div>
    </div>

    <div class="content-section">
        <div class="container">
            <div class="row align-items-center mb-5">
                <div class="col-md-6">
                    <h2 class="mb-4 section-title">Our Story</h2>
                    <p class="mission-text">
                        Welcome to JML Thrift Store, where style meets sustainability! We are more than just a fashion destination—we are a community that celebrates your unique self-expression through pre-loved fashion.
                    </p>
                    <p class="mission-text">
                        Our journey began with a passion for design, quality, and the belief that everyone deserves to look and feel their best while being kind to our planet. Whether you're chasing the latest trends, curating a timeless wardrobe, or exploring bold new looks, we're here to make it happen.
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="image-content">
                        <img src="<?php echo SITE_URL; ?>/images/women/js.jpg" alt="Our Story" onerror="this.src='https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?w=800'">
                    </div>
                </div>
            </div>

            <div class="row align-items-center mb-5">
                <div class="col-md-6 order-md-2">
                    <h2 class="mb-4 section-title">Our Mission</h2>
                    <p class="mission-text">
                        Each piece in our collection is thoughtfully curated to reflect modern elegance, comfort, and versatility. Sustainability is at the heart of what we do, ensuring that our fashion not only empowers your confidence but also respects the planet.
                    </p>
                    <p class="mission-text">
                        From everyday essentials to statement pieces, JML is committed to bringing you styles that inspire. We believe in giving fashion a second life and creating a circular economy that benefits everyone.
                    </p>
                </div>
                <div class="col-md-6 order-md-1">
                    <div class="image-content">
                        <img src="<?php echo SITE_URL; ?>/images/men/25.jpg" alt="Our Mission" onerror="this.src='https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800'">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6 stat-item mb-4">
                    <h2><i class="fas fa-users"></i></h2>
                    <h2>1000+</h2>
                    <p>Happy Users</p>
                </div>
                <div class="col-md-3 col-6 stat-item mb-4">
                    <h2><i class="fas fa-tshirt"></i></h2>
                    <h2>5000+</h2>
                    <p>Items Listed</p>
                </div>
                <div class="col-md-3 col-6 stat-item mb-4">
                    <h2><i class="fas fa-recycle"></i></h2>
                    <h2>3000+</h2>
                    <p>Items Rehomed</p>
                </div>
                <div class="col-md-3 col-6 stat-item mb-4">
                    <h2><i class="fas fa-leaf"></i></h2>
                    <h2>100%</h2>
                    <p>Sustainable</p>
                </div>
            </div>
        </div>
    </div>

    <div class="content-section">
        <div class="container">
            <h2 class="text-center mb-5 section-title">Why Choose Us?</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-leaf"></i>
                        <h3>Eco-Friendly</h3>
                        <p>Reduce fashion waste by giving pre-loved clothes a second chance. Every purchase helps save our planet.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-hand-holding-usd"></i>
                        <h3>Affordable Fashion</h3>
                        <p>Get premium quality clothing at a fraction of the original price. Style doesn't have to be expensive.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-gem"></i>
                        <h3>Unique Finds</h3>
                        <p>Discover one-of-a-kind vintage pieces and rare items you won't find anywhere else.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Secure Platform</h3>
                        <p>Shop with confidence knowing your transactions are safe and your data is protected.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-users"></i>
                        <h3>Community</h3>
                        <p>Join a community of fashion lovers who care about style and sustainability.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-star"></i>
                        <h3>Quality Assured</h3>
                        <p>Every item is carefully checked for quality before being listed on our platform.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="cta-section">
        <div class="container text-center">
            <h2 class="mb-4">Join Us in Making a Difference</h2>
            <p class="lead mb-4">Let's redefine fashion—together! Discover the art of dressing your best while caring for our planet.</p>
            <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                <a href="<?php echo SITE_URL; ?>/assets/main/sell.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus"></i> Get Started
                </a>
                <a href="<?php echo SITE_URL; ?>/assets/main/listings.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-shopping-bag"></i> Browse Items
                </a>
            </div>
        </div>
    </div>

    <?php include '../../assets/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
