<?php
require_once '../../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    $confirm_password = $_POST['confirm_password'];
    $location = sanitize_input($_POST['location']);
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $db = Database::getInstance()->getConnection();
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            // Check if username already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already taken';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, location) VALUES (?, ?, ?, ?)");
                
                if ($stmt->execute([$username, $email, $hashed_password, $location])) {
                    $success = 'Registration successful! Please login.';
                    header("refresh:2;url=login.php");
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="auth_css/register.css">

</head>
<body>
    <a href="../../index.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
    
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <h1>Join Us</h1>
                <p>Create an account to start buying and selling</p>
            </div>
            <div class="register-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" placeholder="City, Country">
                    </div>
                    
                    <button type="submit" class="btn btn-register">Create Account</button>
                </form>
                
                <div class="text-center mt-4">
                    <p>Already have an account? <a href="login.php" class="text-link">Login</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>