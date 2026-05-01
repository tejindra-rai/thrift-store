<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $subject = sanitize_input($_POST['subject'] ?? '');
        $message = sanitize_input($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($message)) {
            $error = 'Please fill in all required fields (Name, Email, and Message)';
        } 
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } 
        else {
            $db = Database::getInstance()->getConnection();
            
            $createTable = "CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50) DEFAULT NULL,
                subject VARCHAR(255) DEFAULT NULL,
                message TEXT NOT NULL,
                status ENUM('new', 'read', 'replied') DEFAULT 'new',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created (created_at),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $db->exec($createTable);
            
            $stmt = $db->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $phone, $subject, $message])) {
                $success = 'Thank you for contacting us! We will get back to you soon.';
                
                $admin_email = 'tejindrarai100@gmail.com';
                $email_subject = "New Contact: " . ($subject ?: 'No Subject');
                $email_body = "New contact form submission:\n\n";
                $email_body .= "Name: $name\n";
                $email_body .= "Email: $email\n";
                $email_body .= "Phone: $phone\n";
                $email_body .= "Subject: $subject\n\n";
                $email_body .= "Message:\n$message\n";
                $headers = "From: noreply@yoursite.com\r\n";
                $headers .= "Reply-To: $email\r\n";
                
                @mail($admin_email, $email_subject, $email_body, $headers);
                
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                exit;
            } else {
                $error = 'Failed to send message. Please try again.';
            }
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
        error_log("Contact form error: " . $e->getMessage());
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        error_log("Contact form error: " . $e->getMessage());
    }
}

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = 'Thank you for contacting us! We will get back to you soon.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="info_css/contact.css">
</head>
<body>
    <?php include '../../assets/includes/header.php'; ?>

    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-envelope"></i> Contact Us</h1>
            <p class="lead">We'd love to hear from you! Get in touch with us.</p>
        </div>
    </div>

    <div class="contact-info-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h4>Phone</h4>
                        <p><a href="tel:+9779745521836" style="color: inherit; text-decoration: none;">+977 9745521836</a></p>
                        <small class="text-muted">Mon-Fri 9am-6pm</small>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Email</h4>
                        <p><a href="mailto:tejindrarai100@gmail.com" style="color: inherit; text-decoration: none;">tejindrarai100@gmail.com</a></p>
                        <small class="text-muted">24/7 Support</small>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <h4>WhatsApp</h4>
                        <p><a href="https://wa.me/9779745521836" target="_blank" style="color: inherit; text-decoration: none;">+977 9745521836</a></p>
                        <small class="text-muted">Quick Response</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="contact-form-section">
        <div class="container">
            <div class="form-container">
                <h2 class="text-center mb-4" style="color: var(--primary-dark);">Send Us a Message</h2>
                
                <?php if($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Success!</strong> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Error!</strong> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="contactForm" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="name" 
                                required 
                                placeholder="John Doe"
                                value="<?php echo isset($_POST['name']) && !$success ? htmlspecialchars($_POST['name']) : ''; ?>"
                            >
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input 
                                type="email" 
                                class="form-control" 
                                name="email" 
                                required 
                                placeholder="john@example.com"
                                value="<?php echo isset($_POST['email']) && !$success ? htmlspecialchars($_POST['email']) : ''; ?>"
                            >
                            <div class="invalid-feedback">Please enter a valid email.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Phone</label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                name="phone" 
                                placeholder="+977 9800000000"
                                value="<?php echo isset($_POST['phone']) && !$success ? htmlspecialchars($_POST['phone']) : ''; ?>"
                            >
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Subject</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                name="subject" 
                                placeholder="How can we help?"
                                value="<?php echo isset($_POST['subject']) && !$success ? htmlspecialchars($_POST['subject']) : ''; ?>"
                            >
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea 
                            class="form-control" 
                            name="message" 
                            rows="6" 
                            required 
                            placeholder="Write your message here..."
                        ><?php echo isset($_POST['message']) && !$success ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        <div class="invalid-feedback">Please enter your message.</div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-submit">
                            <i class="fas fa-paper-plane me-2"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="map-section">
        <div class="container">
            <h2 class="text-center mb-4" style="color: var(--primary-dark);">Visit Us</h2>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.738783074879!2d85.32418931502834!3d27.694529682792955!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb19a3e4c0f91b%3A0x35e6c2ee2d5f4b8a!2sKathmandu%2C%20Nepal!5e0!3m2!1sen!2s!4v1234567890"
                        width="100%" 
                        height="450" 
                        style="border:0; border-radius:15px;" 
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                </div>
            </div>
            <div class="text-center mt-4">
                <p class="text-muted">
                    <i class="fas fa-map-marker-alt me-2"></i> 
                    Kathmandu, Nepal
                </p>
            </div>
        </div>
    </div>

    <?php include '../../assets/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap form validation
        (function() {
            'use strict';
            
            const form = document.getElementById('contactForm');
            
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        })();
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>