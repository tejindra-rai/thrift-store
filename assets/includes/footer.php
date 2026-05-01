<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/includes/include_css/footer.css">
<footer class="foot-bar">
    <div class="first-foot-bar">
        <div class="icons">
            <a href="#" target="_blank">
                <i class="fa-brands fa-facebook-f"></i>
            </a>
        </div>
        <div class="icons">
            <a href="#" target="_blank">
                <i class="fa-brands fa-x-twitter"></i>
            </a>
        </div>
        <div class="icons">
            <a href="#" target="_blank">
                <i class="fa-brands fa-instagram"></i>
            </a>
        </div>
        <div class="icons">
            <a href="#" target="_blank">
                <i class="fa-brands fa-youtube"></i>
            </a>
        </div>
    </div>

    <div class="second-foot-bar">
        <div class="left-section">
            <div class="top-info">
                <p><a href="#">Help & Information</a></p>
                <p><a href="<?php echo SITE_URL; ?>/assets/info/contact.php">Contact Us</a></p>
                <p><a href="<?php echo SITE_URL; ?>/assets/info/about.php">About Us</a></p>
            </div>
            <div class="buttom-info">
                <p>Powered By Tejindra Rai</p>
            </div>
        </div>
        <div class="center-section">
            <p>Copyright Â© 2024, JML</p>
        </div>
        <div class="last-section">
            <div class="payment">
                <p>Payment Methods</p>
            </div>
            <div class="payment-icons">
                <div class="pyicon">
                    <img src="<?php echo SITE_URL; ?>/images/icon/fonepay.png" class="picons" alt="Fonepay" onerror="this.style.display='none'">
                </div>
                <div class="pyicon">
                    <img src="<?php echo SITE_URL; ?>/images/icon/esewa.png" class="picons" alt="eSewa" onerror="this.style.display='none'">
                </div>
                <div class="pyicon">
                    <img src="<?php echo SITE_URL; ?>/images/icon/khalti.png" class="picons" alt="Khalti" onerror="this.style.display='none'">
                </div>
                <div class="pyicon">
                    <img src="<?php echo SITE_URL; ?>/images/icon/paypal.png" class="picons" alt="PayPal" onerror="this.style.display='none'">
                </div>
            </div>
            <div class="terms-part">
                <p><a href="#">Terms & Conditions</a></p>
                <p><a href="#">Privacy & Cookies</a></p>
            </div>
        </div>
    </div>
</footer>

<button class="back-to-top" id="backToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
window.addEventListener('scroll', function() {
    const backToTop = document.getElementById('backToTop');
    if (window.pageYOffset > 300) {
        backToTop.classList.add('show');
    } else {
        backToTop.classList.remove('show');
    }
});

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}
</script>