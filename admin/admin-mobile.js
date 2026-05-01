document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'mobile-menu-toggle';
    toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
    toggleBtn.setAttribute('aria-label', 'Toggle Menu');
    document.body.insertBefore(toggleBtn, document.body.firstChild);
    
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.insertBefore(overlay, document.body.firstChild);
    
    const sidebar = document.querySelector('.sidebar');
    
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    });
    
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    const menuLinks = sidebar.querySelectorAll('.sidebar-menu a');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }, 250);
    });
});